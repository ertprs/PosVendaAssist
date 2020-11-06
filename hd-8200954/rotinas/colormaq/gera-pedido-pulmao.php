<?php

/**
 *
 * gera-pedido-pulmao.php
 *
 * Geração de pedidos de pecas com base no estoque Pulmão
 *
 * @author  Guilherme Silva
 * @version 2015.05.28
 *
*/

ini_set('max_execution_time', 300);
error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$data['login_fabrica'] 	= 50;
    $data['fabrica_nome'] 	= 'colormaq';
    $data['arquivo_log'] 	= 'gera-pedido-pulmao';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $pedido_pecas			= array();
    $erro 					= false;

    extract($data);

	$phpCron = new PHPCron($login_fabrica, __FILE__);
	$phpCron->inicio();

	if (ENV == 'producao') {
		$data['dest'] 		= 'helpdesk@telecontrol.com.br';
    } else {
    	$data['dest'] 		= 'pedidos@telecontrol.com.br';
    }

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    /* Seleciona a condição do Pedido */
	$sql_condicao = "SELECT condicao FROM tbl_condicao WHERE fabrica = $login_fabrica AND codigo_condicao = '003'";
	$res_condicao = pg_query($con, $sql_condicao);

	if(pg_last_error($con)){
		$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Condição Pagamento'";
		$logs_erro[] = $sql_condicao;
		$logs[] = pg_last_error($con);
		$erro = "*";
	}else{
		$condicao = pg_result($res_condicao, 0, 'condicao');
	}

	/* Seleciona o tipo do Pedido */
	$sql_tipo_pedido = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND codigo = '004' AND garantia_antecipada IS TRUE";
	$res_tipo_pedido = pg_query($con, $sql_tipo_pedido);

	if(pg_last_error($con)){
		$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Tipo do Pedido'";
		$logs_erro[] = $sql_tipo_pedido;
		$logs[] = pg_last_error($con);
		$erro = "*";
	}else{
		$tipo_pedido = pg_result($res_tipo_pedido, 0, 'tipo_pedido');
	}

    /* Seleciona todos os Postos que controlam estoque */

    $sql_postos = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND controla_estoque IS TRUE";
    if (ENV == 'teste') {
    	$sql_postos .= " AND posto = 11276";
    }
    $res_postos = pg_query($con, $sql_postos);

    if(pg_last_error($con)){
		$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Selecionar Postos'";
		$logs_erro[] = $sql_postos;
		$logs[] = pg_last_error($con);
		$erro = "*";
	}else{

	    if(pg_num_rows($res_postos) > 0){

	    	while($posto = pg_fetch_object($res_postos)){

	    		$erro = "";

	    		pg_query($con, 'BEGIN');

	    		/* Código dos Postos */
	    		$posto = $posto->posto;

	    		/* Seleciona as peças da Fabrica para verifica se há no estoque dos postos */

			    $sql_pecas = "SELECT tbl_estoque_posto.peca, (tbl_estoque_posto.estoque_maximo - case when tbl_estoque_posto.qtde isnull then 0 else tbl_estoque_posto.qtde end ) AS qtde_pedido 
			    			FROM tbl_estoque_posto 
			    			JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca 
			    			WHERE tbl_peca.fabrica = $login_fabrica 
			    			AND tbl_peca.ativo IS TRUE 
			    			AND tbl_estoque_posto.posto = $posto 
			    			AND tbl_estoque_posto.fabrica = $login_fabrica 
			    			AND tbl_estoque_posto.tipo = 'pulmao' 
			    			AND tbl_estoque_posto.estoque_maximo > 0
			    			AND tbl_estoque_posto.estoque_maximo >= tbl_estoque_posto.estoque_minimo
			    			AND (tbl_estoque_posto.qtde <= tbl_estoque_posto.estoque_minimo) ";
			    $res_pecas = pg_query($con, $sql_pecas);

				if(pg_last_error($con)){
					$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Selecionar Peças'";
					$logs_erro[] = $sql_pecas;
					$logs[] = pg_last_error($con);
					$erro = "*";
				}else{

				    if(pg_num_rows($res_pecas) > 0){

				    	/* Realiza o Pedido */
						$sql_pedido = "INSERT INTO tbl_pedido (
									posto        ,
									fabrica      ,
									condicao     ,
									tipo_pedido  ,
									status_pedido
								) VALUES (
									$posto      ,
									$login_fabrica,
									$condicao   ,
									$tipo_pedido,
									1
								) RETURNING pedido";
						$res_pedido = pg_query($con, $sql_pedido);

						if(pg_last_error($con)){
							$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Gravar Pedido'";
							$logs_erro[] = $sql_pedido;
							$logs[] = pg_last_error($con);
							$erro = "*";
						}else{

							$pedido = pg_fetch_result($res_pedido, 0, 0);

							for($i = 0; $i < pg_num_rows($res_pecas); $i++){

					    		$peca 	= pg_fetch_result($res_pecas, $i, 'peca');
								$qtde 	= pg_fetch_result($res_pecas, $i, 'qtde_pedido');

                                $sql_depara = "SELECT peca_para FROM tbl_depara WHERE peca_de = $peca";
                                $qry_depara = pg_query($con, $sql_depara);

                                if (pg_num_rows($qry_depara) > 0) {
                                    $sqlEstoqueDe = "SELECT estoque_minimo, estoque_maximo
                                                        FROM tbl_estoque_posto
                                                        WHERE peca = $peca
                                                        AND posto = $posto";
                                    $qryEstoqueDe = pg_query($con, $sqlEstoqueDe);

                                    $minimo = pg_fetch_result($qryEstoqueDe, 0, 'estoque_minimo');
                                    $maximo = pg_fetch_result($qryEstoqueDe, 0, 'estoque_maximo');

									$minimo = (strlen($minimo) == 0) ? 0 : $minimo;
									$maximo = (strlen($maximo) == 0) ? 0 : $maximo;

                                    $sqlZeraDe = "UPDATE tbl_estoque_posto SET
                                                    estoque_minimo = 0,
                                                    estoque_maximo = 0
                                                WHERE peca = $peca
                                                AND posto = $posto";
                                    $qryZeraDe = pg_query($con, $sqlZeraDe);

                                    $peca = pg_fetch_result($qry_depara, 0, 'peca_para');

									$sqlPara = "select peca
												from tbl_estoque_posto
												where posto = $posto
												and peca = $peca
											   and tipo = 'pulmao'	";
									$resPara = pg_query($con,$sqlPara);

									if(pg_num_rows($resPara) == 0) {
										$sqlEstoquePara = "INSERT INTO tbl_estoque_posto (
																fabrica,
																posto,
																peca,
																tipo,
																qtde,
																estoque_minimo,
																estoque_maximo
															) VALUES (
																$login_fabrica,
																$posto,
																$peca,
																'pulmao',
																0,
																$minimo,
																$maximo
															)";
									
										$qryEstoquePara = pg_query($con, $sqlEstoquePara);
									}

                                }

								$sqlx = "SELECT pedido
										FROM tbl_pedido
										JOIN tbl_pedido_item USING(pedido)
										WHERE fabrica = $login_fabrica
										AND   peca = $peca
										AND   posto = $posto
										AND   (tbl_pedido.status_pedido in (1,2,5) or tbl_pedido.status_pedido isnull)
										AND		tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada 
										AND   tbl_pedido.tipo_pedido = $tipo_pedido ";
								$resx = pg_query($con,$sqlx);
								if(pg_num_rows($resx) > 0) {
									continue;
								}

					    		$sql_pedido_item = "INSERT INTO tbl_pedido_item (
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
                                        0      )";
								$res_pedido_item = pg_query($con,$sql_pedido_item);

								if(pg_last_error($con)){
									$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Inserir Pedido Item $sql_pedido_item'";
									$logs_erro[] = $sql_pedido_item;
									$logs[] = pg_last_error($con);
									$erro = "*";
								}

					    	}

					    	$sql_finaliza = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
							$res_finaliza = pg_query($con,$sql_finaliza);
							if(pg_last_error($con)){
								$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Finaliza Pedido'";
								$logs_erro[] = $sql_finaliza;
								$logs[] = pg_last_error($con);
								$erro = "*";
							}

						}

				    }

				}

				if($erro == "*"){
					pg_query($con, 'ROLLBACK');
				}else{
					pg_query($con, 'COMMIT');
				}

	    	}

	    }

	}

	/* Grava os Logs */
	if(count($logs) > 0){
    	$file_log = fopen($arquivo_log,"w+");
        	fputs($file_log,implode("\r\n", $logs));
        fclose ($file_log);
    }

    //envia email para HelpDESK

    if(count($logs_erro) > 0){
    	$file_log = fopen($arquivo_err,"w+");
        	fputs($file_log,implode("\r\n", $logs));
        	if(count($logs_erro) > 0){
        		fputs($file_log,"\r\n ####################### SQL ####################### \r\n");
        		fputs($file_log,implode("\r\n", $logs_erro));
        	}
        fclose ($file_log);

        $mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->IsHTML();
		$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
		$mail->Subject = Date('d/m/Y')." - Erro na geração de pedido (gera-pedido-os.php)";
	    $mail->Body = $mensagem;
	    $mail->AddAddress($dest);
	    if(file_exists($arquivo_err) AND filesize($arquivo_err) > 0)
	    	$mail->AddAttachment($arquivo_err);
	    $mail->Send();
	}

    $phpCron->termino();

}catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);
    Log::envia_email($data,Date('d/m/Y H:i:s')." - Colormaq - Erro na geração de pedido Pulmão(gera-pedido-pulmao.php)", $msg);
}

?>
