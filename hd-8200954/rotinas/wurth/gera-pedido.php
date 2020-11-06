<?php
/**
 *
 * gera-pedido.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Ronald Santos
 * @version 2013.04.02
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	require dirname(__FILE__) . '/../../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 	= 122;
    $data['fabrica_nome'] 	= 'wurth';
    $data['arquivo_log'] 	= 'gera-pedido-os';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

	$fabrica = 122;
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	if (ENV == 'producao' ) {
		$data['dest'] 		= 'helpdesk@telecontrol.com.br';
    } else {
    	$data['dest'] 			= 'ronald.santos@telecontrol.com.br';
    }

    extract($data);

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    $sql = "SET DateStyle TO 'SQL,EUROPEAN';";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    }

    $sql = "SELECT to_char(current_date, 'd')::integer;";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'dia da semana'";
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }else{
    	$dia_semana = pg_fetch_result($res, 0);
    }

	$sql1 = "SELECT interv_produto_critico.os
		INTO TEMP tmp_interv_os
		FROM (
				SELECT
				ultima_produto_critico.os,
				(
					SELECT status_os
					FROM tbl_os_status
					WHERE tbl_os_status.os = ultima_produto_critico.os
					AND   tbl_os_status.fabrica_status= $fabrica
					AND   status_os IN (13,19,62,64)
					ORDER BY os_status DESC LIMIT 1
				) AS ultimo_produto_critico_status

				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE tbl_os_status.fabrica_status= $fabrica
					AND   status_os IN (13,19,62,64)
				) ultima_produto_critico
			) interv_produto_critico
			WHERE interv_produto_critico.ultimo_produto_critico_status IN (13,62);
	";
	$res1 = pg_query($con,$sql1);

	$sql1 = "SELECT interv_produto_critico.os
		INTO TEMP tmp_interv_peca
		FROM (
				SELECT
				ultima_produto_critico.os,
				(
					SELECT status_os
					FROM tbl_os_status
					WHERE tbl_os_status.os = ultima_produto_critico.os
					AND   tbl_os_status.fabrica_status= $fabrica
					AND   status_os IN (118,185,187)
					ORDER BY os_status DESC LIMIT 1
				) AS ultimo_produto_critico_status

				FROM (
					SELECT DISTINCT os
					FROM tbl_os_status
					WHERE tbl_os_status.fabrica_status= $fabrica
					AND   status_os IN (118,185,187)
				) ultima_produto_critico
			) interv_produto_critico
			WHERE interv_produto_critico.ultimo_produto_critico_status IN (118,185);
	";
	$res1 = pg_query($con,$sql1);

    $sql = "SELECT
						tbl_os.posto        ,
						tbl_produto.linha   ,
						tbl_os_item.peca    ,
						tbl_os_item.os_item ,
						tbl_os_item.qtde    ,
						tbl_os.sua_os      ,
					       tbl_os.os
						INTO TEMP tmp_pedido_wurth
				FROM    tbl_os_item
				JOIN    tbl_servico_realizado USING (servico_realizado)
				JOIN    tbl_os_produto USING (os_produto)
				JOIN    tbl_os         USING (os)
				JOIN    tbl_posto      USING (posto)
				JOIN    tbl_produto          ON tbl_os.produto          = tbl_produto.produto
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				WHERE   tbl_os_item.pedido IS NULL
				AND     tbl_os.validada    IS NOT NULL
				AND     tbl_os.excluida    IS NOT TRUE
				AND     tbl_os.fabrica    = $login_fabrica
				AND     tbl_os.troca_garantia       IS NULL
				AND     tbl_os.troca_garantia_admin IS NULL
				AND    (tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					OR  tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
				AND     tbl_servico_realizado.gera_pedido
				AND     tbl_servico_realizado.peca_estoque IS NOT TRUE
				AND		tbl_os.os not in (SELECT os FROM tmp_interv_os)
				AND		tbl_os.os not in (SELECT os FROM tmp_interv_peca);

				SELECT DISTINCT posto,os, linha from tmp_pedido_wurth ;


				";




    $resP = pg_query($con, $sql);

	if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Posto'";
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }

    #Garantia
	$sql = "select condicao from tbl_condicao where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
	$resultG = pg_query($con, $sql);
	if(pg_last_error($con)){
		$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Condição Pagamento'";
		$logs_erro[] = $sql;
		$logs[] = pg_last_error($con);
		$erro = "*";
	}else{
		$condicao = pg_result($resultG,0,'condicao');
	}

	#Tipo_pedido
	$sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
	$resultP = pg_query($con, $sql);
	if(pg_last_error($con)){
		$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Tipo Pedido'";
		$logs_erro[] = $sql;
		$logs[] = pg_last_error($con);
		$erro = "*";
	}else{
		$tipo_pedido = pg_result($resultP,0,'tipo_pedido');
	}

	for ($i=0; $i < pg_num_rows($resP); $i++) {

		$posto  = pg_result($resP,$i,'posto');
		$os	= pg_result($resP,$i,'os');
		$linha  = pg_result($resP,$i,'linha');

		$sql_auditoria = "SELECT auditoria_os
				FROM tbl_auditoria_os 
				WHERE os = $os
				AND (bloqueio_pedido IS TRUE OR cancelada IS NOT NULL OR reprovada IS NOT NULL)
				ORDER BY auditoria_os DESC ";
		$res_auditoria = pg_query($con, $sql_auditoria);
		
		if(pg_num_rows($res_auditoria)>0){
			continue;
		}

		$erro = " ";
		$res = pg_query($con, "BEGIN TRANSACTION");


		$sql = "INSERT INTO tbl_pedido (
					posto        ,
					fabrica      ,
					condicao     ,
					tipo_pedido  ,
					linha        ,
					status_pedido
				) VALUES (
					$posto      ,
					$login_fabrica    ,
					$condicao   ,
					$tipo_pedido,
					$linha      ,
					1
				) RETURNING pedido;";
		$resultP = pg_query($con, $sql);
		if(pg_last_error($con)){
			$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido'";
			$logs_erro[] = $sql;
			$logs[] = pg_last_error($con);
			$erro = "*";
		}else{

			$pedido = pg_result($resultP,0,0);

			$sql_item = "SELECT
						peca    ,
						qtde,
						os_item
							from
						tmp_pedido_wurth
						WHERE posto = $posto
						AND os = $os";

			$result2 = pg_query($con,$sql_item);

			for ($x=0; $x < pg_num_rows($result2); $x++) {
				$peca = pg_result($result2,$x,'peca');
				$qtde = pg_result($result2,$x,'qtde');
				$os_item = pg_result($result2,$x,'os_item');

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
					$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido Item'";
					$logs_erro[] = $sql;
					$logs[] = pg_last_error($con);
					$erro = "*";
				}else{
					$pedido_item = pg_result($resultX,0,0);

					$sql = "SELECT fn_atualiza_os_item_pedido_item($os_item,$pedido,$pedido_item,$login_fabrica)";
					$resultX = pg_query($con,$sql);
					if(pg_last_error($con)){
						$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Atualiza OS Item'";
						$logs_erro[] = $sql;
						$logs[] = pg_last_error($con);
						$erro = "*";
					}
				}
			}

			$sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
			$resultX = pg_query($con,$sql);

			if(pg_last_error($con)){
				$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido Item'";
				$logs_erro[] = $sql;
				$logs[] = pg_last_error($con);
				$erro = "*";
				echo pg_last_error($con);
			}

			/*for ($k=0; $k < pg_num_rows($result2); $k++) {


				 esta validação precisou ser criada após a
				   execução da função fn_atualiza_os_item_pedido_item
				   e fn_pedido_finaliza, por conta dos status
				   hd-6035345
				

				$peca = pg_result($result2,$k,'peca');
				$qtde = pg_result($result2,$k,'qtde');
				$os_item = pg_result($result2,$k,'os_item');

				$sql_estoque = "SELECT qtde FROM tbl_posto_estoque 
								WHERE posto = 4311
								AND peca = $peca
								AND qtde >= $qtde";
				$res_estoque = pg_query($con, $sql_estoque);

				if (pg_num_rows($res_estoque) == 0) {

					atualiza_status_checkpoint($os, 'Aguard. Abastecimento Estoque');

				}
			}*/

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
				//$resultX = pg_query($con,"ROLLBACK TRANSACTION");
			}
		}
	}

    if(count($logs) > 0){
    	$file_log = fopen($arquivo_log,"w+");
        	fputs($file_log,implode("\r\n", $logs));
        fclose ($file_log);
    }

    //envia email para HelpDESK
    if($erro){
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
    }

    $phpCron->termino();

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - wurth - Erro na geração de pedido(gera-pedido.php)", $msg);
}?>
