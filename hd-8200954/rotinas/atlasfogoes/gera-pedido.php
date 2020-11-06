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
define('ENV','dev');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 	= 74;
    $data['fabrica_nome'] 	= 'atlas';
    $data['arquivo_log'] 	= 'gera-pedido-os';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $pedido_pecas			= array();
    $erro 					= false;

	$fabrica = 74;
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	if (ENV == 'producao' ) {
		$data['dest'] = 'helpdesk@telecontrol.com.br';
    } else {
		$data['dest'] = 'rafael.macedo@telecontrol.com.br';
    }

    extract($data);

    $arquivo_err = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    // ####################################################
	// Auditoria de KM
	// ####################################################
    $sql_auditoria_km = "SELECT os_auditoria_km.os
			  INTO TEMP tmp_auditoria_km
			  FROM (
					SELECT auditoria_km.os, (
							SELECT status_os
							  FROM tbl_os_status
							 WHERE tbl_os_status.os             = auditoria_km.os
							   AND tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (98,99,100,101)
					 ORDER BY os_status DESC LIMIT 1) AS auditoria_km_status
					  FROM (
							SELECT DISTINCT os
							  FROM tbl_os_status
							 WHERE tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (98,99,100,101)
					  ) auditoria_km) os_auditoria_km
			 WHERE os_auditoria_km.auditoria_km_status IN (98);";
	$res_auditoria_km = pg_query($con, $sql_auditoria_km);

	if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Auditoria Km  (98)'";
    	$logs_erro[] = $sql;
    	$logs[]      = pg_last_error($con);
    	$erro        = true;
    	throw new Exception ($msg_erro);
    }

    // ####################################################
	// INTERVENCAO OS Aberta a mais de 30 OU 70 DIAS
	// ####################################################
	$sql = "SELECT revenda.os
			  INTO TEMP tmp_revenda
			  FROM (
					SELECT ultima_serie.os, (
							SELECT status_os
							  FROM tbl_os_status
							 WHERE tbl_os_status.os             = ultima_serie.os
							   AND tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (176,177)
					 ORDER BY os_status DESC LIMIT 1) AS ultimo_serie_status
					  FROM (
							SELECT DISTINCT os
							  FROM tbl_os_status
							 WHERE tbl_os_status.fabrica_status = $fabrica
							   AND status_os IN (176,177)
					  ) ultima_serie) revenda
			 WHERE revenda.ultimo_serie_status IN (176);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
    	$logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'OS Revenda (176)'";
    	$logs_erro[] = $sql;
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
						tbl_os.sua_os       ,
						tbl_os.os
						INTO TEMP tmp_pedido_atlas
				FROM    tbl_os_item
				JOIN    tbl_servico_realizado USING (servico_realizado)
				JOIN    tbl_os_produto USING (os_produto)
				JOIN    tbl_os         USING (os)
				JOIN    tbl_posto      USING (posto)
				JOIN    tbl_produto          ON tbl_os.produto          = tbl_produto.produto
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				WHERE   tbl_os.data_digitacao > '2013-09-02 00:00:00'
				AND     tbl_os_item.pedido IS NULL
				AND     tbl_os.validada    IS NOT NULL
				AND     tbl_os.excluida    IS NOT TRUE
				AND 	tbl_os.cancelada IS NOT TRUE
				AND     tbl_os.posto       <> 6359
				AND     tbl_os.fabrica    = $login_fabrica
				AND     tbl_os.troca_garantia       IS NULL
				AND     tbl_os.troca_garantia_admin IS NULL
				AND    (tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					OR  tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO')
				AND     tbl_servico_realizado.gera_pedido
				AND     tbl_servico_realizado.peca_estoque IS NOT TRUE
				AND     tbl_os.os NOT IN ( SELECT os FROM tmp_revenda )
				AND     tbl_os.os NOT IN ( SELECT os FROM tmp_auditoria_km );

				SELECT DISTINCT posto, linha from tmp_pedido_atlas;

				";

    $resP = pg_query($con, $sql);

	if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Posto'";
    	$logs_erro[] = $sql;
    	$logs[	] = pg_last_error($con);
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }

    /* Estoque */

    for ($i=0; $i < pg_num_rows($resP); $i++) {

		$posto = pg_result($resP,$i,'posto');
		$linha = pg_result($resP,$i,'linha');

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
							qtde    ,
							os_item ,
							os
						FROM tmp_pedido_atlas
						WHERE posto = $posto
						AND linha = $linha";

			$result2 = pg_query($con,$sql_item);

			for ($x=0; $x < pg_num_rows($result2); $x++) {
				$peca 		= pg_result($result2,$x,'peca');
				$qtde 		= pg_result($result2,$x,'qtde');
				$os_item 	= pg_result($result2,$x,'os_item');
				$os 		= pg_result($result2,$x,'os');

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

			$sql = "SELECT count(pedido_item) AS itens FROM tbl_pedido_item WHERE pedido = $pedido";
			$resultP = pg_query($con,$sql);
			$itens = pg_fetch_result($resultP, 0, 'itens');

			if($itens > 0){
				$sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
				$resultX = pg_query($con,$sql);
				if(pg_last_error($con)){
					$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Pedido Item'";
					$logs_erro[] = $sql;
					$logs[] = pg_last_error($con);
					$erro = "*";
				}
			}else{
				$sql = "DELETE FROM tbl_pedido WHERE pedido = $pedido";
				$resultX = pg_query($con,$sql);
				if(pg_last_error($con)){
					$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Deletar Pedido'";
					$logs_erro[] = $sql;
					$logs[] = pg_last_error($con);
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
			$mail->AddReplyTo("ronald.santos@telecontrol.com.br", "Suporte Telecontrol");
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
    Log::envia_email($data,Date('d/m/Y H:i:s')." - ATLAS FOGOES - Erro na geração de pedido(gera-pedido.php)", $msg);
}?>
