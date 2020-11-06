<?php

	define('APP','Gera Pedido  - Famastil'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {
		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';

		$fabrica     = 86;
		$data 		 = date('d-m-Y');
		
		$phpCron = new PHPCron($fabrica, __FILE__); 
		$phpCron->inicio();

		$vet['fabrica'] = 'famastil';
		$vet['tipo']    = 'pedido';
		
		if(ENV == "producao"){
			$vet['dest'][0]    = 'helpdesk@telecontrol.com.br';
			$vet['dest'][1]    = 'cassiano.weirich@famastil.com.br';
			$vet['dest'][2]    = 'at@famastil.com.br';
		} else {
			$vet['dest']   = 'ronald.santos@telecontrol.com.br';
		}
		
		$vet['log']     = 1;

		$msg_erro = array();


		$sql = "SELECT  tbl_os.posto ,
                        tbl_produto.linha ,
                        tbl_os_item.peca ,
                        tbl_os_item.os_item ,
                        tbl_os_item.qtde ,
                        tbl_os.sua_os ,
                        tbl_os.admin,
                        tbl_os.os ,
                        (
                            SELECT  MAX(os_status) 
                            FROM    tbl_os_status 
                            WHERE   tbl_os_status.os    = tbl_os.os 
                            AND     status_os           IN (62,64) 
                        ) as os_status
           INTO TEMP    tmp_pedido_famastil
				FROM    tbl_os_item
				JOIN    tbl_servico_realizado   USING (servico_realizado)
				JOIN    tbl_os_produto          USING (os_produto)
				JOIN    tbl_os                  USING (os)
				JOIN    tbl_posto               USING (posto)
				JOIN    tbl_produto             ON  tbl_os.produto              = tbl_produto.produto
				JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto     = tbl_os.posto 
                                                AND tbl_posto_fabrica.fabrica   = tbl_os.fabrica
				WHERE   tbl_os_item.pedido                  IS NULL
				AND     tbl_os.validada                     IS NOT NULL
				AND     tbl_os.excluida                     IS NOT TRUE
				AND     tbl_os.posto                        <> 6359
				AND     (
                            tbl_os_item.liberacao_pedido    IS TRUE 
                        OR  tbl_os.admin                    IS NOT NULL
                        )
				AND     tbl_os.fabrica                      = $fabrica
				AND     tbl_os.troca_garantia               IS NULL
				AND     tbl_os.troca_garantia_admin         IS NULL
				AND     (
                            tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                        OR  tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO'
                        )
				AND     tbl_servico_realizado.gera_pedido   IS TRUE;
        ";
		$res = pg_query($con,$sql);
// if(pg_last_error()){
    // echo pg_last_error();
    // exit;
// }
		$sql2 = "		SELECT  DISTINCT 
                        posto
                FROM    tmp_pedido_famastil
           LEFT JOIN    tbl_os_status ON tmp_pedido_famastil.os_status = tbl_os_status.os_status
				WHERE   (
                            tbl_os_status.status_os <> 62 
                        OR  tmp_pedido_famastil.admin IS NOT NULL
                        );
        ";
		$res2 = pg_query($con,$sql2);
#echo pg_num_rows($res2);exit;
		if(pg_num_rows($res2) > 0){

			for($i = 0; $i < pg_num_rows($res2); $i++){
				$posto 	= pg_result($res2,$i,'posto');
				
				unset($msg_erro);
				
				$resultX = pg_query($con,"BEGIN");

				$sql = "select condicao from tbl_condicao where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
				$resultX = pg_query($con,$sql);
				if(strlen(pg_last_error($con)) > 0 OR pg_num_rows($resultX) == 0){
					$msg_erro[] = "Erro ao encontrar condição de pagamento";
				} else {
					$condicao = pg_result($resultX,0,'condicao');		
				}


				$sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
				$resultX = pg_query($con,$sql);
				if(strlen(pg_last_error($con)) > 0 OR pg_num_rows($resultX) == 0){
					$msg_erro[] = "Erro ao encontrar tipo de pedido";
				} else {
					$tipo_pedido = pg_result($resultX,0,'tipo_pedido');
				}
				

				if(count($msg_erro) == 0){
					$sql = "INSERT INTO tbl_pedido (
							posto        ,
							fabrica      ,
							condicao     ,
							tipo_pedido  ,
							status_pedido
						) VALUES (
							$posto      ,
							$fabrica    ,
							$condicao   ,
							$tipo_pedido,
							1
						) RETURNING pedido;";
					$resultX = pg_query($con,$sql);
					if(strlen(pg_last_error($con)) > 0){
						$msg_erro[] = "Erro ao gravar número do pedido";
					} else {
						$pedido = pg_result($resultX,0,0);
					}

					if(count($msg_erro) == 0){
						$sql_item = "
                                    SELECT  peca    ,
                                            qtde    ,
                                            os_item
									FROM    tmp_pedido_famastil
                               LEFT JOIN    tbl_os_status   ON  tmp_pedido_famastil.os_status   = tbl_os_status.os_status 
                                                            AND tbl_os_status.os                = tmp_pedido_famastil.os
                                    WHERE   posto = $posto
                                    AND     (
                                                ( 
                                                    tbl_os_status.status_os = 64 
                                                OR  tbl_os_status.status_os IS NULL 
                                                ) 
                                            OR  tmp_pedido_famastil.admin IS NOT NULL
                                            )
                        ";
						
						$resultY = pg_query($con,$sql_item);

						if(pg_num_rows($resultY) > 0){

							for($j = 0; $j < pg_num_rows($resultY); $j++){

								$peca 	 = pg_result($resultY,$j,'peca');
								$qtde 	 = pg_result($resultY,$j,'qtde');
								$os_item = pg_result($resultY,$j,'os_item');

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
								if(strlen(pg_last_error($con)) > 0){
									$msg_erro[] = "Erro ao gravar número do item do pedido";
								} else {
									$pedido_item = pg_result($resultX,0,0);
								}

								if(count($msg_erro) == 0){
									$sql = "SELECT fn_atualiza_os_item_pedido_item($os_item ,$pedido,$pedido_item, $fabrica)";
									$resultX = pg_query($con,$sql);
									if(strlen(pg_last_error($con)) > 0){
										$msg_erro[] = "Erro ao atualizar item do pedido";
									}
								}
							}

						}
					}

					if(count($msg_erro) == 0){
						$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
						$resultX = pg_query($con,$sql);
						if(strlen(pg_last_error($con)) > 0){
							$msg_erro[] = "Erro ao finalizar o pedido <br>".pg_errormessage($con);
						}
					}
				}

				
				if(count($msg_erro) == 0){
					$resX = pg_query($con,"COMMIT");
				} else {
					$resX = pg_query($con,"ROLLBACK");

					
					$sqlY = "SELECT DISTINCT codigo_posto,
								tmp_pedido_famastil.sua_os,
								referencia,
								qtde,
								tbl_tabela_item.preco
								FROM
								tmp_pedido_famastil
								JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_pedido_famastil.posto and tbl_posto_fabrica.fabrica = $fabrica
								JOIN tbl_peca USING(peca)
								JOIN tbl_posto_linha    ON tbl_posto_linha.posto     = tmp_pedido_famastil.posto
								JOIN tbl_tabela_item    ON tbl_tabela_item.peca      = tmp_pedido_famastil.peca and tbl_tabela_item.tabela    = tbl_posto_linha.tabela
								JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.fabrica = $fabrica
								WHERE tmp_pedido_famastil.posto = $posto
								AND tmp_pedido_famastil.linha = $linha";
					$result = pg_query($con,$sqlY);

					if(pg_num_rows($result) > 0){
						$msg_erro[] = "OSs que n&atilde;o geraram pedido";
						for($x = 0; $x < pg_num_rows($result); $x++){

							$msg_erro[] = " Posto:".pg_result($result,$x,'codigo_posto')." - OS:".pg_result($result,$x,'sua_os')." - Pe&ccedil;a:".pg_result($result,$x,'referencia')." - Qtde:".pg_result($result,$x,'qtde')." - Pre&ccedil;o:".pg_result($result,$x,'preco');
							
						}

					}
				}

				if(count($msg_erro) > 0){
					foreach($msg_erro AS $erro) {
							$log .= $erro."<br>";
					}
					$log .= "<br><br>";
				}

			}

		}

		if(!empty($log)){
			$fp = fopen("/tmp/famastil/pedidos/pedidos_erro.err","w");
			fwrite($fp,$log);
			fclose($fp);
		
			Log::envia_email($vet,APP, $log);
		}
		
		$phpCron->termino();

	}
	catch (Exception $e) {

		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );

	}
