<?php
/**
 *
 * igera-pedido-doacao.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Éderson Sandre
 * @version 2012.04.12
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../dbconfig_pg.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['fabrica'] 		= 50;
    $data['fabrica_nome'] 	= 'colormaq';
    $data['arquivo_log'] 	= 'gera-pedido-doacao';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $erro 					= false;
	
	$fabrica = "50";
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	if (ENV == 'producao' ) {
		$data['dest'] 		= 'helpdesk@telecontrol.com.br';
		$data['dest_cliente']   = 'posvendafaturamento@colormaq.com.br,antoniocarlos@colormaq.com.br';
    } else {
    	$data['dest'] 			= 'ronald.santos@telecontrol.com.br';
    	$data['dest_cliente'] 		= 'ronald.santos@telecontrol.com.br,ederson.sandre@telecontrol.com.br';
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

    $sql = "SELECT interv_km.os
		  		INTO TEMP tmp_interv_km
			FROM (
				SELECT
				ultima_km.os,
				(SELECT status_os 
				FROM tbl_os_status 
				WHERE tbl_os_status.os = ultima_km.os 
					AND   tbl_os_status.fabrica_status= $fabrica 
					AND   status_os IN (98,99,100) 
				ORDER BY os_status DESC LIMIT 1) AS ultimo_km_status
				FROM (SELECT DISTINCT os 
						FROM tbl_os_status 
						WHERE tbl_os_status.fabrica_status= $fabrica 
						AND   status_os IN (98,99,100) ) ultima_km
				) interv_km
			WHERE interv_km.ultimo_km_status IN (98);";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'intervenção de KM (98,99,100)'";
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }

    $sql = "SELECT interv_reinc.os
		  		INTO TEMP tmp_interv_reinc
			FROM (
				SELECT
				ultima_reinc.os,
				(SELECT status_os 
					FROM tbl_os_status 
					WHERE tbl_os_status.os = ultima_reinc.os 
					AND   tbl_os_status.fabrica_status= $fabrica 
					AND   status_os IN (13,19,68,67,70,115,118) 
					ORDER BY os_status DESC LIMIT 1) AS ultimo_reinc_status
					FROM (SELECT DISTINCT os 
							FROM tbl_os_status 
							WHERE tbl_os_status.fabrica_status= $fabrica 
							AND   status_os IN (13,19,68,67,70,115,118) ) ultima_reinc
				) interv_reinc
			WHERE interv_reinc.ultimo_reinc_status IN (13,68,67,70,115,118);";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Intervenção Reincidente (13,19,68,67,70,115,118)'";
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }

	$sql = "SELECT interv_serie.os
		  		INTO TEMP tmp_interv_serie
			FROM (
				SELECT
					ultima_serie.os,
					(SELECT status_os 
					FROM tbl_os_status 
					WHERE tbl_os_status.os = ultima_serie.os 
					AND   tbl_os_status.fabrica_status= $fabrica 
					AND   status_os IN (102,103,104) 
					ORDER BY os_status DESC LIMIT 1) AS ultimo_serie_status
					FROM (SELECT DISTINCT os 
							FROM tbl_os_status 
							WHERE tbl_os_status.fabrica_status= $fabrica 
							AND   status_os IN (102,103,104) ) ultima_serie
					) interv_serie
			WHERE interv_serie.ultimo_serie_status IN (102,104);";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Intervenção de Série (102,103,104)'";
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }
    
	$sql = "SELECT DISTINCT tbl_posto.posto, tbl_os.os
				INTO TEMP tmp_colormaq_gera_posto
			FROM  tbl_os_item
				JOIN tbl_os_produto     on tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_os             on tbl_os.os                 = tbl_os_produto.os
				JOIN tbl_posto          on tbl_posto.posto = tbl_os.posto
				JOIN tbl_posto_fabrica  on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $fabrica
				JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica=$fabrica
				JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca and tbl_peca.fabrica = $fabrica
			WHERE   tbl_os_item.pedido IS NULL
				AND tbl_os_item.garantia_antecipada IS NOT TRUE
				AND tbl_os.validada    IS NOT NULL
				AND tbl_os.excluida    IS NOT TRUE
				AND tbl_os.fabrica     = $fabrica
				AND tbl_posto.posto    <> 6359
				AND tbl_os.troca_garantia       IS NULL
				AND tbl_os.troca_garantia_admin IS NULL
				AND tbl_servico_realizado.peca_estoque IS TRUE
				AND (credenciamento = 'CREDENCIADO' OR credenciamento = 'EM DESCREDENCIAMENTO')
				AND tbl_os.os NOT IN ( select os from tmp_interv_km )
				AND tbl_os.os NOT IN ( select os from tmp_interv_reinc )
				AND tbl_os.os NOT IN ( select os from tmp_interv_serie )
			ORDER BY tbl_os.os ASC ;

			SELECT DISTINCT posto FROM tmp_colormaq_gera_posto;";
    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: 'Posto'";
    	$logs_erro[] = $sql;
    	$logs[] = pg_last_error($con);
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }


    for ($i=0; $i < pg_num_rows($res); $i++) { 

		$posto = pg_result($res,$i,'posto');
		for($j = 0; $j < 2; $j++){
	    $dev_obrig = ($j == 0) ? " AND tbl_peca.devolucao_obrigatoria IS TRUE " : " AND tbl_peca.devolucao_obrigatoria IS NOT TRUE ";
		$sql = "SELECT tbl_os_item.peca,
				tbl_os_item.qtde, 
				tbl_os.os, 
				tbl_os.posto,
				tbl_peca.referencia,
				tbl_os_item.os_item,
				(SELECT DISTINCT tbl_produto.linha
					FROM tbl_lista_basica
						JOIN tbl_produto USING(produto)
					WHERE tbl_lista_basica.peca = tbl_peca.peca
						AND tbl_os.produto = tbl_produto.produto
					ORDER BY linha 
					LIMIT 1) AS linha,
				0 as pedido
			INTO TEMP tmp_os_item_colormaq_{$posto}_{$j}
		FROM  tbl_os_item
			JOIN tbl_os_produto     on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_os             on tbl_os.os                 = tbl_os_produto.os
			JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = $fabrica
			JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca and tbl_peca.fabrica = $fabrica
		WHERE   tbl_os_item.pedido IS NULL
			AND tbl_os_item.garantia_antecipada IS NOT TRUE
			AND     tbl_os.validada    IS NOT NULL
			AND     tbl_os.excluida    IS NOT TRUE
			AND     tbl_os.fabrica    = $fabrica
			AND     tbl_os.posto      = $posto
			AND     tbl_os.troca_garantia       IS NULL
			AND     tbl_os.troca_garantia_admin IS NULL
			AND     tbl_servico_realizado.peca_estoque
			$dev_obrig
			AND     tbl_os.os NOT IN ( select os from tmp_interv_km )
			AND     tbl_os.os NOT IN ( select os from tmp_interv_reinc )
			AND     tbl_os.os NOT IN ( select os from tmp_interv_serie )
			ORDER BY tbl_os.os ASC;

		SELECT peca, sum(qtde) as qtde, linha FROM tmp_os_item_colormaq_{$posto}_{$j} GROUP BY peca,linha;";
	    $res_peca = pg_query($con, $sql);
	    extract(pg_fetch_assoc($res_peca));
	    if(pg_last_error($con)){
	    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: OS Item 'Peça, Quantidade e Linha'";
	    	$logs_erro[] = $sql;
	    	$logs[] = pg_last_error($con);
	    	$erro   = true;
	    	throw new Exception ($msg_erro);
	    }

	    $condicao = "1025";
		$tipo_pedido = ($j == 0) ? 129 : 173;
		
		if(pg_numrows($res_peca) > 0){
			pg_query($con, "BEGIN TRANSACTION");
			$erro_pedido = false;
			$linha = !empty($linha)  ?  $linha : "null";
			
			$sql = "INSERT INTO tbl_pedido (
						posto        ,
						fabrica      ,
						condicao     ,
						tipo_pedido  ,
						status_pedido,
						pedido_os    ,
						linha
					) VALUES (
						$posto      ,
						$fabrica    ,
						$condicao   ,
						$tipo_pedido,
						1           ,
						't'         ,
						$linha
					) RETURNING pedido;";
			$res_pedido = pg_query($con, $sql);
			if(pg_last_error($con)){
				$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO";
				$logs_erro[] 		= $sql;
				$logs[] 		= pg_last_error($con);
				$erro   		= true;
				$erro_pedido	= true;
			}else
				$pedido = pg_fetch_result($res_pedido, 0,0);
			
			for ($x=0; $x < pg_num_rows($res_peca); $x++) { 
				
				$peca = pg_result($res_peca,$x,'peca');
				$qtde = pg_result($res_peca,$x,'qtde');
				$os_item = pg_result($res_peca,$x,'os_item');
				
				if(!empty($pedido)){
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
								0
							) RETURNING pedido_item;";
					$res_pedido_item = pg_query($con, $sql);
					if(pg_last_error($con)){
						$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: INSERT PEDIDO ITEM (OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde})";
						$logs_erro[] 		= $sql;
						$logs[] 		= pg_last_error($con);
						$erro   		= true;
						$erro_pedido	= true;
						echo "0";
					}else
						$pedido_item = pg_fetch_result($res_pedido_item, 0,0);

					$sql = "UPDATE tmp_os_item_colormaq_{$posto}_{$j}
								SET pedido = $pedido
							WHERE os_item
									IN(
										SELECT os_item
										FROM tmp_os_item_colormaq_{$posto}_{$j}
										WHERE peca =  $peca
									);";
					$res_atualiza_pedido_item = pg_query($con, $sql);
					if(pg_last_error($con)){
						$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: TMP atualiza pedido (OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde})";
						$logs_erro[] 	= $sql;
						$logs[] 		= pg_last_error($con);
						$erro   		= true;
						$erro_pedido	= true;
					}

					$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
					$res_atualiza_pedido_item = pg_query($con, $sql);

					if(pg_last_error($con)){
						$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: Função Finaliza Pedido (OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde})";
						$logs_erro[] 	= $sql;
						$logs[] 		= pg_last_error($con);
						$logs_cliente[] 	= Array(
													'posto'	=> $posto, 
													'peca'	=> $peca,
													'erro'	=> pg_last_error($con)
											);
						$erro   		= true;
						$erro_pedido	= true;
					}
				}
			}
			
			#Os pedidos não terão amarração com a OS
			$sql = "UPDATE tbl_os_item SET garantia_antecipada = TRUE FROM tmp_os_item_colormaq_{$posto}_{$j} WHERE tbl_os_item.os_item = tmp_os_item_colormaq_{$posto}_{$j}.os_item";
			$res_atualiza_os_item = pg_query($con, $sql);
			if(pg_last_error($con)){
				$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro SQL: Função atualiza OS item (OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde})";
				$logs_erro[] 	= $sql;
				$logs[] 		= pg_last_error($con);
				$erro   		= true;
				$erro_pedido	= true;
			}

			$sql_posto = "
					SELECT 
						tbl_posto.nome, 
						tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica} 
					WHERE 
						tbl_posto.posto = {$posto}
					LIMIT 1";
			$res_posto = pg_query($con, $sql_posto);
			$codigo_posto = pg_fetch_result($res_posto, 0, 'codigo_posto');
			$nome_posto   = pg_fetch_result($res_posto, 0, 'nome');
			
			if(!$erro_pedido){
				$logs[] = "SUCESSO => Posto: '{$codigo_posto} - {$nome_posto}' - Pedido {$pedido} gerado com sucesso!";
				pg_query($con, "COMMIT TRANSACTION");
				//pg_query($con, "ROLLBACK TRANSACTION");
			}else{	    	
				$logs[] = "ERRO => Posto: '{$codigo_posto} - {$nome_posto}' - Não gerou pedido!";
				pg_query($con, "ROLLBACK TRANSACTION");	
			}
			}
		}
    }

    if(count($logs_cliente)){
    	$msg = array();
    	$msg[] = "Erro na geração de pedido<br />";

    	foreach ($logs_cliente AS $log) {
    		$posto 	= $log['posto'];
    		$peca 	= $log['peca'];
    		$error 	= $log['erro'];

    		$sql = "SELECT 
    						tbl_posto_fabrica.codigo_posto, 
    						tbl_posto.nome
    					FROM tbl_posto_fabrica 
    						JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
    					WHERE tbl_posto_fabrica.posto = {$posto} 
    						AND tbl_posto_fabrica.fabrica = {$fabrica} LIMIT 1";
    		$res = pg_query($con, $sql);
    		$codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');
    		$nome 	      = pg_fetch_result($res, 0, 'nome');

    		$msg[] = "O Posto '{$codigo_posto} - {$nome}' não gerou pedido!";
    		$msg[] = "- $error<br>";
    	}
    	$msg[] = "<br>Att.<br>Telecontrol Networking";

    	$mailer = new PHPMailer();
		$mailer->IsSMTP();
		$mailer->IsHTML();
		$mailer->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");

		$emails = explode(",", $dest_cliente);
		if(count($emails)){
		    foreach ($emails as $email) {
		        $mailer->AddAddress($email);
		    }
		}else{
		    $mailer->AddAddress($dest_cliente);
		}

		$mensagem  = implode("<br />", $msg);

		$mailer->Subject = Date('d/m/Y')." - Erro na geração de pedido";
	    $mailer->Body = $mensagem;
	    $mailer->Send();
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
			$mail->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");
			$mail->Subject = Date('d/m/Y')." - Erro na geração de pedido (gera-pedido-doacao.php)";
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

    Log::envia_email($data,Date('d/m/Y H:i:s')." - COLORMAQ - Erro na geração de pedido(gera-pedido-doacao.php)", $msg);
}?>
