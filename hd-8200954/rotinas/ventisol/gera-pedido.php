<?php
define('ENV','teste');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';

    $data['login_fabrica'] 	= 139;
    $data['fabrica_nome'] 	= 'ventisol';
    $data['arquivo_log'] 	= 'gera-pedido-os';
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erros 			= array();
    $logs_cliente			= array();
    $erro 					= false;

    /* Log */
    $log = new Log2();
    $log->adicionaLog(array("titulo" => "Log erro Geração de Pedidos Ventisol")); // Titulo

	if (ENV == 'producao' ) {
		$log->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
    	$log->adicionaEmail("guilherme.curcio@telecontrol.com.br");
    }

    extract($data);

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    $sql = "SET DateStyle TO 'SQL,EUROPEAN';";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs_erros[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    }

    $sql = "SELECT to_char(current_date, 'd')::integer;";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$log->adicionaLog("Erro ao pegar dia da semana");
        $log->adicionaLog("linha");
    	throw new Exception ($msg_erro);
    }else{
    	$dia_semana = pg_fetch_result($res, 0);
    }


// ####################################################
// INTERVENCAO OS REINCIDENTE
// ####################################################
$sql = "SELECT interv_reinc.os
			INTO TEMP tmp_interv_reinc
			FROM (
				SELECT
				ultima_reinc.os,
				(
					SELECT status_os
					FROM tbl_os_status
					WHERE tbl_os_status.os = ultima_reinc.os
					AND   tbl_os_status.fabrica_status= $login_fabrica
					AND   status_os IN (70, 19,139)
					ORDER BY os_status DESC LIMIT 1
				) AS ultimo_reinc_status

				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE tbl_os_status.fabrica_status= $login_fabrica
					AND   status_os IN (70, 19,139)
				) ultima_reinc
			) interv_reinc
			WHERE interv_reinc.ultimo_reinc_status IN (70);
	";
$res = pg_query($con,$sql);

// ####################################################
// INTERVENCAO FABRICA
// ####################################################
$sql = "SELECT interv_fabrica.os
			INTO TEMP tmp_interv_fabrica
			FROM (
				SELECT
				ultima_interv_fabrica.os,
				(
					SELECT status_os
					FROM tbl_os_status
					WHERE tbl_os_status.os = ultima_interv_fabrica.os
					AND   tbl_os_status.fabrica_status= $login_fabrica
					AND   status_os IN (62,64)
					ORDER BY os_status DESC LIMIT 1
				) AS ultimo_status

				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE tbl_os_status.fabrica_status= $login_fabrica
					AND   status_os IN (62,64)
				) ultima_interv_fabrica
			) interv_fabrica
			WHERE interv_fabrica.ultimo_status IN (62);
	";
$res = pg_query($con,$sql);


// ####################################################
// INTERVENCAO DE VALORES ADICIONAIS
// ####################################################
$sql = "SELECT  interv_valores_adicionais.os
   INTO TEMP    tmp_interv_valores_adicionais
        FROM    (
                    SELECT  ultima_interv_valores_adicionais.os,
                            (
                                SELECT  status_os
                                FROM    tbl_os_status
                                WHERE   tbl_os_status.os             = ultima_interv_valores_adicionais.os
                                AND     tbl_os_status.fabrica_status = $login_fabrica
                                AND     status_os IN (171,172,173)
                          ORDER BY      os_status DESC
                                LIMIT   1
                            ) AS ultimo_status
                    FROM    (
                                SELECT  DISTINCT
                                        os
                                FROM    tbl_os_status
                                WHERE   tbl_os_status.fabrica_status = $login_fabrica
                                AND     status_os IN (171,172,173)
                            ) ultima_interv_valores_adicionais
                ) interv_valores_adicionais
        WHERE   interv_valores_adicionais.ultimo_status IN (171,173);";
$res = pg_query($con, $sql);

// ####################################################
// INTERVENCAO NOVAS AUDITORIAS
// ####################################################
$sql = "SELECT DISTINCT	tbl_auditoria_os.os
   		INTO TEMP tmp_interv_nova_aud
		FROM tbl_auditoria_os
   		JOIN tbl_os USING(os)
	    WHERE fabrica = $login_fabrica
	    AND tbl_os.finalizada IS NULL
		AND tbl_os.excluida IS NOT TRUE
	    AND (tbl_auditoria_os.bloqueio_pedido IS TRUE OR tbl_auditoria_os.cancelada IS NOT NULL OR tbl_auditoria_os.reprovada IS NOT NULL);";
$res = pg_query($con, $sql);

    $sql = "SELECT
						tbl_os.posto        ,
						tbl_produto.linha   ,
						tbl_os_item.peca    ,
						tbl_os_item.os_item ,
						tbl_os_item.qtde    ,
						tbl_os.sua_os      ,
					       tbl_os.os	   ,
					    tbl_posto_fabrica.codigo_posto,
					    tbl_posto.nome AS posto_nome
				INTO TEMP tmp_pedido_{$fabrica_nome}
				FROM    tbl_os_item
				JOIN    tbl_servico_realizado USING (servico_realizado)
				JOIN    tbl_os_produto USING (os_produto)
				JOIN    tbl_os         USING (os)
				JOIN    tbl_posto      ON tbl_os.posto = tbl_posto.posto
				JOIN    tbl_produto          ON tbl_os.produto          = tbl_produto.produto
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				JOIN 	tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
						AND tbl_tipo_posto.posto_interno IS NOT TRUE
				WHERE   tbl_os_item.pedido IS NULL
				/*AND     tbl_os.posto = 6359*/
				AND     tbl_os.validada    IS NOT NULL
				AND     tbl_os.finalizada  IS NULL
				AND     tbl_os.excluida    IS NOT TRUE
				AND     tbl_os.fabrica    = $login_fabrica
				AND     tbl_os.troca_garantia       IS NULL
				AND     tbl_os.troca_garantia_admin IS NULL
				AND    (tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					OR  tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
				AND     tbl_servico_realizado.gera_pedido
				AND     tbl_servico_realizado.peca_estoque IS NOT TRUE
				AND     tbl_os.os NOT IN ( select os from tmp_interv_reinc )
				AND     tbl_os.os NOT IN ( select os from tmp_interv_fabrica )
				AND     tbl_os.os NOT IN ( select os from tmp_interv_valores_adicionais )
				AND     tbl_os.os NOT IN ( select os from tmp_interv_nova_aud );

				SELECT DISTINCT posto, codigo_posto, posto_nome from tmp_pedido_{$fabrica_nome}";
    $resP = pg_query($con, $sql);

	if(pg_last_error($con)){
		$log_erros[] = "Erro ao listar os Postos que irão gerar pedido";
    		$log->adicionaLog("Erro ao listar os Postos que irão gerar pedido");
        	$log->adicionaLog("linha");
    	throw new Exception ($msg_erro);
    }

    #Garantia
	$sql = "select condicao from tbl_condicao where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
	$resultG = pg_query($con, $sql);
	if(pg_last_error($con)){
		$log_erros[] = "Erro por falta de condição de pagamento 'GARANTIA'";
		$log->adicionaLog("Erro por falta de condição de pagamento 'GARANTIA'");
        	$log->adicionaLog("linha");
        throw new Exception ($msg_erro);
	}else{
		$condicao = pg_result($resultG,0,'condicao');
	}

	#Tipo_pedido
	$sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
	$resultP = pg_query($con, $sql);
	if(pg_last_error($con)){
		$log_erros[] = "Erro por falta de tipo de pedido 'GARANTIA'";
		$log->adicionaLog("Erro por falta de tipo de pedido 'GARANTIA'");
        	$log->adicionaLog("linha");
        throw new Exception ($msg_erro);
	}else{
		$tipo_pedido = pg_result($resultP,0,'tipo_pedido');
	}

	for ($i=0; $i < pg_num_rows($resP); $i++) {
		$posto      	= pg_result($resP,$i,'posto');
		$codigo_posto  	= pg_result($resP,$i,'codigo_posto');
		$os				= pg_result($resP,$i,'os');
		$posto		 	= pg_result($resP,$i,'posto');
		$nome_posto  	= pg_result($resP,$i,'posto_nome');

		$erro = " ";
		$res = pg_query($con, "BEGIN TRANSACTION");

		$sql_item = "SELECT peca,
							qtde,
							os_item,
							os
						FROM
						tmp_pedido_{$fabrica_nome}
						WHERE posto = $posto";

		$result2 = pg_query($con,$sql_item);

		$pedido = "";

		$select_distribuidor = "SELECT tbl_posto_linha.distribuidor
									FROM tbl_linha
									INNER JOIN tbl_posto_linha USING(linha)
									WHERE tbl_posto_linha.posto = $posto
									AND tbl_linha.fabrica = $login_fabrica
									AND tbl_posto_linha.distribuidor notnull
									LIMIT 1";
			$res_distribuidor = pg_query($con, $select_distribuidor);

			if (pg_num_rows($res_distribuidor) == 0) {
				$log_erros[] = "Erro ao gravar pedido para o Posto: $codigo_posto - $nome_posto, posto não tem distribuidor";
				$log->adicionaLog("Erro ao gravar pedido para o Posto: {$codigo_posto} - {$nome_posto}, posto não tem distribuidor");
    				$log->adicionaLog("linha");
    				$erro = "*";
			} else {
				$distribuidor = pg_fetch_result($res_distribuidor, 0, "distribuidor");

				if(empty($pedido)){
					$sql = "INSERT INTO tbl_pedido (
								posto        ,
								fabrica      ,
								condicao     ,
								tipo_pedido  ,
								status_pedido,
								distribuidor
							) VALUES (
								$posto      ,
								$login_fabrica    ,
								$condicao   ,
								$tipo_pedido,
								1,
								$distribuidor
							) RETURNING pedido;";
					$resultP = pg_query($con, $sql);

					if(pg_last_error($con)){
						$log_erros[] = "Erro ao gravar pedido para o Posto: $codigo_posto - $nome_posto";
						$log->adicionaLog("Erro ao gravar pedido para o Posto: {$codigo_posto} - {$nome_posto}");
	        				$log->adicionaLog("linha");
	        				$erro = "*";
					}else{
						$pedido = pg_result($resultP,0,0);
					}
				}
			}

		for ($x=0; $x < pg_num_rows($result2); $x++) {
			$peca = pg_result($result2,$x,'peca');
			$qtde = pg_result($result2,$x,'qtde');
			$os_item = pg_result($result2,$x,'os_item');
			$os = pg_result($result2,$x,'os');


			$sql = "INSERT INTO tbl_pedido_item (
					pedido,
					peca  ,
					qtde  ,
					qtde_faturada,
					qtde_cancelada
				) VALUES (
					$pedido,
					$peca  ,
					$qtde  ,
					0      ,
					0      ) RETURNING pedido_item";
			$resultX = pg_query($con,$sql);


			if(pg_last_error($con)){
				$log_erros[] = "Erro ao gravar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto";
				$log->adicionaLog("Erro ao gravar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto");
        			$log->adicionaLog("linha");
        			$erro = "*";
			}else{
				$pedido_item = pg_result($resultX,0,0);

				$sql = "SELECT fn_atualiza_os_item_pedido_item($os_item,$pedido,$pedido_item,$login_fabrica)";
				$resultX = pg_query($con,$sql);
				if(pg_last_error($con)){
					$log_erros[] = "Erro ao atualizar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto";
					$log->adicionaLog("Erro ao atualizar itens do pedido $pedido para o Posto: $codigo_posto - $nome_posto");
        				$log->adicionaLog("linha");
        				$erro = "*";
				}
			}	
		}

		if(!empty($pedido)){
			$sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
			$resultX = pg_query($con,$sql);

			if(pg_last_error($con)){
				$log_erros[] = "Erro ao finalizar o pedido $pedido para o Posto: $codigo_posto - $nome_posto";
				$log->adicionaLog("Erro ao finalizar o pedido $pedido para o Posto: $codigo_posto - $nome_posto");
        		$log->adicionaLog("linha");
        		$erro = "*";
			}
		}

		if ($erro == "*") {
			$resultX = pg_query($con,"ROLLBACK TRANSACTION");
		}else{
			$sql_posto = "
			  SELECT
				  tbl_posto.nome,
				  tbl_posto_fabrica.codigo_posto
			  FROM tbl_posto
				  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			  WHERE
				  tbl_posto.posto = {$posto}
			  LIMIT 1";
			$res_posto = pg_query($con, $sql_posto);
			$codigo_posto = pg_fetch_result($res_posto, 0, 'codigo_posto');
			$nome_posto   = pg_fetch_result($res_posto, 0, 'nome');

			$logs[] = "SUCESSO => Posto: '{$codigo_posto} - {$nome_posto}' - Pedido {$pedido} gerado com sucesso!";

			$resultX = pg_query($con,"COMMIT TRANSACTION");
		}

	}

	if(count($logs) > 0){
    	$file_log = fopen($arquivo_log,"w+");
       	fputs($file_log,implode("\r\n", $logs));
        fclose ($file_log);
    }

    if(count($log_erros) > 0){
    	$file_log = fopen($arquivo_err,"w+");
       	fputs($file_log,implode("\r\n", $log_erros));
        fclose ($file_log);
    }

    //envia email para HelpDESK
    if($erro){
	    if($log->enviaEmails() == "200"){
      #    echo "Log de erro enviado com Sucesso!";
        }else{
          $log->enviaEmails();
        }
    }

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    $log->adicionaLog($msg);

    if($log->enviaEmails() == "200"){
     # echo "Log de erro enviado com Sucesso!";
    }else{
      $log->enviaEmails();
    }
}?>
