<?php
define('ENV','teste');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $vet['fabrica'] = 'ventisol';
    $vet['tipo'] 	= 'pedido';
    $vet['log'] 	= 2;
	$fabrica 		= 139;
    $data_sistema	= Date('Y-m-d');
    $logs_erro				= array();

	/* Log */
    $log = new Log2();
    $log->adicionaLog(array("titulo" => "Log erro Geração de Pedidos Ventisol")); // Titulo
    
	if (ENV == 'producao' ) {
		$log->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
    	$log->adicionaEmail("guilherme.curcio@telecontrol.com.br");
    }

    $arquivo_err = "/tmp/ventisol/gera-pedido-troca-{$data_sistema}.err";
    $arquivo_log = "/tmp/ventisol/gera-pedido-troca-{$data_sistema}.log";
    system ("mkdir /tmp/ventisol/ 2> /dev/null ; chmod 777 /tmp/ventisol/" );

    
    $sql = "SELECT  DISTINCT
				tbl_posto.posto   ,
				tbl_produto.linha,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			FROM    tbl_os_item
			JOIN    tbl_servico_realizado USING (servico_realizado)
			JOIN    tbl_os_produto        USING (os_produto)
			JOIN    tbl_os                USING (os)
			JOIN    tbl_posto             USING (posto)
			JOIN    tbl_produto           ON tbl_os.produto            = tbl_produto.produto
			JOIN    tbl_posto_fabrica     ON tbl_posto_fabrica.posto   = tbl_os.posto 
					AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
			JOIN 	tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto 
					AND tbl_tipo_posto.posto_interno IS NOT TRUE
			JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
			WHERE   tbl_os_item.pedido        IS NULL
			AND     tbl_os.excluida           IS NOT TRUE
			AND     tbl_os.validada           IS NOT NULL
			/*AND     tbl_posto.posto           <> 6359*/
			AND     tbl_os_troca.gerar_pedido IS TRUE
			AND     tbl_os.fabrica      = $fabrica";
	$res = pg_query($con, $sql);

	if(pg_last_error($con)){
    	$log->adicionaLog("Erro ao listar os Postos que irão gerar pedido");
        $log->adicionaLog("linha");
    	throw new Exception ($msg_erro.pg_last_error());
    }

    #Garantia 
	$sql = "select condicao from tbl_condicao where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
	$resultG = pg_query($con, $sql);
	if(pg_last_error($con)){
		$logs_erro[] = "Erro por falta de condição de pagamento 'GARANTIA'";
		$log->adicionaLog("Erro por falta de condição de pagamento 'GARANTIA'");
        $log->adicionaLog("linha");
        throw new Exception ($msg_erro);
	}else{
		$condicao = pg_result($resultG,0,'condicao');
	}

	#Tipo_pedido
	$sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
	$resultP = pg_query($con, $sql);
	if(pg_last_error($con)){
		$logs_erro[] = "Erro por falta de tipo de pedido 'GARANTIA'";
		$log->adicionaLog("Erro por falta de tipo de pedido 'GARANTIA'");
        $log->adicionaLog("linha");
        throw new Exception ($msg_erro);
	}else{
		$tipo_pedido = pg_result($resultP,0,'tipo_pedido');
	}

	if(pg_num_rows($res) > 0 AND count($logs_erro) == 0){
		
		for($i = 0; $i < pg_num_rows($res); $i++){
			$posto = pg_result($res,$i,'posto');
			$linha = pg_result($res,$i,'linha');
			$codigo_posto = pg_result($res,$i,'codigo_posto');
			$nome_posto = pg_result($res,$i,'nome');

			unset($logs_erro);
			
			$resultX = pg_query($con,"BEGIN TRANSACTION");

			$sql = "SELECT  tbl_os_troca.peca,
						tbl_os.os
					FROM    tbl_os
					JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
					JOIN    tbl_produto           ON tbl_os.produto  = tbl_produto.produto
					WHERE   tbl_os_troca.gerar_pedido IS TRUE
					AND     tbl_os_troca.pedido       IS NULL
					AND     tbl_os.fabrica    = $fabrica
					AND     tbl_os.posto      = $posto
					AND     tbl_produto.linha = $linha ";
			$result = pg_query($con, $sql);

			if(pg_last_error($con)){
				$logs_erro[] = $sql."<br>".pg_last_error($con);
			}
    
			if(pg_num_rows($result) > 0 AND count($logs_erro) == 0){
				
				for($x = 0; $x < pg_num_rows($result); $x++){
					$peca = pg_result($result,$x,'peca');
					$os   = pg_result($result,$x,'os');

					$select_distribuidor = "SELECT tbl_posto_linha.distribuidor 
											FROM tbl_linha
											INNER JOIN tbl_posto_linha USING(linha)
											WHERE tbl_posto_linha.posto = $posto
											AND tbl_linha.fabrica = $fabrica
											LIMIT 1";
					$res_distribuidor = pg_query($con, $select_distribuidor);

					echo pg_last_error();

					if (pg_num_rows($res_distribuidor) == 0) {
						$log_erros[] = "Erro ao gravar pedido para o Posto: $codigo_posto - $nome_posto, posto não tem distribuidor";
						$log->adicionaLog("Erro ao gravar pedido para o Posto: {$codigo_posto} - {$nome_posto}, posto não tem distribuidor");
		    				$log->adicionaLog("linha");
		    				$erro = "*";
					} else {
						$distribuidor = pg_fetch_result($res_distribuidor, 0, "distribuidor");

						$sql = "INSERT INTO tbl_pedido (
														posto     ,
														fabrica   ,
														linha     ,
														condicao  ,
														tipo_pedido,
														troca      ,
														total,
														distribuidor
													) VALUES (
														$posto    ,
														$fabrica  ,
														$linha    ,
														$condicao ,
														'$tipo_pedido'     ,
														TRUE      ,
														0,
														$distribuidor
													) RETURNING pedido;";					
						$resultX = pg_query($con, $sql);
						if(pg_last_error($con)){
							$logs_erro[] = "Erro ao gravar pedido para o Posto: $codigo_posto - $nome_posto";
							$log->adicionaLog("Erro ao gravar pedido para o Posto: $codigo_posto - $nome_posto");
		        			$log->adicionaLog("linha");
		        			$erro = "*";
						}else{					
							$pedido = pg_result($resultX,0,0);

							$sql = "SELECT total_troca FROM tbl_os_troca WHERE os = $os";
							$resultX = pg_query($con, $sql);

							if(pg_num_rows($resultX) > 0){
								$total_troca = pg_result($resultX,0,'total_troca');
							}

							$sql = "INSERT INTO tbl_pedido_item (
																pedido,
																peca  ,
																qtde  ,
																qtde_faturada,
																qtde_cancelada,
																troca_produto
															) VALUES (
																$pedido,
																$peca  ,
																1      ,
																0      ,
																0      ,
																't'
															) RETURNING pedido_item";
							$resultX = pg_query($con, $sql);

							if(pg_last_error($con)){
								$logs_erro[] = "Erro ao gravar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto";
								$log->adicionaLog("Erro ao gravar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto");
				        		$log->adicionaLog("linha");
				        		$erro = "*";
							} else {
								$pedido_item = pg_result($resultX,0,0);

								$sql = "UPDATE tbl_os_troca SET pedido = $pedido, pedido_item = $pedido_item WHERE os = $os";
								$resultX = pg_query($con, $sql);
								if(pg_last_error($con)){
									$logs_erro[] = $sql."<br>".pg_last_error($con);
								}


								$sql = "SELECT fn_atualiza_os_item_pedido_item (os_item,$pedido,$pedido_item,$fabrica)
										FROM tbl_os_item
										WHERE peca = $peca
										AND os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
								$resultX = pg_query($con, $sql);
								if(pg_last_error($con)){
									$logs_erro[] = "Erro ao atualizar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto";
									$log->adicionaLog("Erro ao atualizar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto");
				        			$log->adicionaLog("linha");
				        			$erro = "*";
								}

								$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
								$resultX = pg_query($con, $sql);
								
								if(pg_last_error($con)){
									$logs_erro[] = "Erro ao finalizar o pedido $pedido para o Posto: $codigo_posto - $nome_posto";
									$log->adicionaLog("Erro ao finalizar o pedido $pedido para o Posto: $codigo_posto - $nome_posto");
					        		$log->adicionaLog("linha");
					        		$erro = "*";
								}
							}
						}
					}
				}
			}
			
			if ($erro == "*") {
				$resultX = pg_query($con, "ROLLBACK TRANSACTION");
			}else{
				$resultX = pg_query($con,"COMMIT TRANSACTION");
			}
		}
	}

	if(count($log_erro) > 0){
    	$file_log = fopen($arquivo_err,"w+");
       	fputs($file_log,implode("\r\n", $log_erro));
        fclose ($file_log);
    }

	 //envia email para HelpDESK
    if($erro){
	    if($log->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          echo $log->enviaEmails();
        }
    } 

    
} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    $log->adicionaLog($msg);

    if($log->enviaEmails() == "200"){
      echo "Log de erro enviado com Sucesso!";
    }else{
      echo $log->enviaEmails();
    }
}
