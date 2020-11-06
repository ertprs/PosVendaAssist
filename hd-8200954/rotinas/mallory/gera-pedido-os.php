<?php
/**
 *
 * gera-pedido.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Ronald Santos
 * @version 2012.10.26
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 	= 72;
    $data['fabrica_nome'] 	= 'mallory';
    $data['arquivo_log'] 	= 'gera-pedido-os';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $fabrica     = 72;

    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;

	if (ENV == 'producao' ) {
		$data['dest'] 		= 'ksilva@mallory.com.br , informatica@mallory.com.br , marisa.silvana@telecontrol.com.br';
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
    }else{
    	$dia_semana = pg_fetch_result($res, 0);
    }


    // ####################################################
		// INTERVENCAO OS Intervenção de Fábrica
		// ####################################################
		$sql_inter_fabrica = "SELECT revenda.os
			  INTO TEMP tmp_intervencao_fabrica
			  FROM (
					SELECT ultima_serie.os, (
							SELECT status_os
							  FROM tbl_os_status
							 WHERE tbl_os_status.os             = ultima_serie.os
							   AND tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (62,64,147)
					 ORDER BY os_status DESC LIMIT 1) AS ultimo_serie_status
					  FROM (
							SELECT DISTINCT os
							  FROM tbl_os_status
							 WHERE tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (62,64,147)
					  ) ultima_serie) revenda
			 WHERE revenda.ultimo_serie_status IN (62,147);";
    $res_inter_fabrica = pg_query($con, $sql_inter_fabrica);

    if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'OS Intervenção (Intervenção fabrica)'";
    	$logs_erro[] = $sql_inter_fabrica;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }

    // ####################################################
		// INTERVENCAO OS Intervenção de Peças Excedentes
		// ####################################################
		$sql_excedentes = "SELECT revenda.os
			  INTO TEMP tmp_intervencao_pecas_excedentes
			  FROM (
					SELECT ultima_serie.os, (
							SELECT status_os
							  FROM tbl_os_status
							 WHERE tbl_os_status.os             = ultima_serie.os
							   AND tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (118,187,185)
					 ORDER BY os_status DESC LIMIT 1) AS ultimo_serie_status
					  FROM (
							SELECT DISTINCT os
							  FROM tbl_os_status
							 WHERE tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (118,187,185)
					  ) ultima_serie) revenda
			 WHERE revenda.ultimo_serie_status IN (118,185);";
    $res_excedentes = pg_query($con, $sql_excedentes);

    if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'OS Intervenção (Peças Excedentes)'";
    	$logs_erro[] = $sql_excedentes;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }

		$sql = "SELECT
					tbl_os.posto        ,
					tbl_produto.linha   ,
					tbl_os_item.peca    ,
					tbl_os_item.os_item ,
					tbl_os_item.qtde    ,
					tbl_os.sua_os
				INTO TEMP tmp_pedido_mallory
				FROM    tbl_os_item
				JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica=$fabrica
				JOIN    tbl_os_produto USING (os_produto)
				JOIN    tbl_os         ON tbl_os.os = tbl_os_produto.os and tbl_os.fabrica=$fabrica
				JOIN    tbl_posto      USING (posto)
				JOIN    tbl_produto ON tbl_os.produto=tbl_produto.produto AND tbl_produto.fabrica_i=$fabrica
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				WHERE   tbl_os_item.pedido IS NULL
				AND     tbl_os_item.fabrica_i = $fabrica
				AND     tbl_os.validada    IS NOT NULL
				AND     tbl_os.excluida    IS NOT TRUE
                AND     tbl_os.cancelada    IS NOT TRUE
				AND     tbl_os.posto       <> 6359
				AND     tbl_os.troca_garantia       IS NULL
				AND     tbl_os.troca_garantia_admin IS NULL
				AND    (tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					OR  tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
				AND     tbl_servico_realizado.gera_pedido
				AND tbl_os.os NOT IN ( SELECT os FROM tmp_intervencao_fabrica)
				AND tbl_os.os NOT IN (SELECT os FROM tmp_intervencao_pecas_excedentes);

				SELECT DISTINCT posto,linha,sua_os from tmp_pedido_mallory ;
				";
    $resP = pg_query($con, $sql);

    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Posto'";
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    }

	for ($i=0; $i < pg_num_rows($resP); $i++) {

		$posto = pg_result($resP,$i,'posto');
		$linha = pg_result($resP,$i,'linha');
		$sua_os = pg_fetch_result($resP,$i,'sua_os');
		$erro = " ";
		$res = pg_query($con, "BEGIN TRANSACTION");

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
						sum(qtde) AS qtde
							from
						tmp_pedido_mallory
						WHERE posto = $posto
						AND linha = $linha
						AND sua_os  = '$sua_os'
						group by peca";
			$result2 = pg_query($con,$sql_item);
			$msg_erro = pg_errormessage($con);

			for ($x=0; $x < pg_num_rows($result2); $x++) {
				$peca = pg_result($result2,$x,'peca');
				$qtde = pg_result($result2,$x,'qtde');

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

					$sql = "SELECT fn_atualiza_os_item_pedido_item(os_item ,$pedido,$pedido_item, $fabrica)
								FROM    tmp_pedido_mallory
								WHERE    tmp_pedido_mallory.peca  = $peca
								AND     tmp_pedido_mallory.posto = $posto
								AND     tmp_pedido_mallory.linha = $linha
								AND     tmp_pedido_mallory.sua_os    = '$sua_os'";
					$resultX = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

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
	        	if(count($logs_erro) > 0){
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



} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - MALLORY - Erro na geração de pedido(gera-pedido.php)", $msg);
}?>
