<?php
/**
 *
 * gera-pedido.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Ronald Santos
 * @version 2012.08.29
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['fabrica'] 		= 52;
    $data['fabrica_nome'] 	= 'fricon';
    $data['arquivo_log'] 	= 'gera-pedido-os';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
    $logs_peca_sem_preco	= array();
    $erro 					= false;

	if (ENV == 'producao' ) {
		$aux_sql = "SELECT email FROM tbl_admin WHERE fabrica = 52 AND ativo IS TRUE AND privilegios = '*' AND email IS NOT NULL";
		$aux_res = pg_query($con, $aux_sql);
		$aux_row = pg_num_rows($aux_res);
		$aux_arr = array('helpdesk@telecontrol.com.br');

		for ($yz = 0; $yz < $aux_row; $yz++) { 
			$aux_arr[] = pg_fetch_result($aux_res, $yz, 'email');
		}

		$data['dest'] 		  = 'helpdesk@telecontrol.com.br';
		$data['dest_cliente'] = implode(",", $aux_arr);
    } else {
    	$aux_sql = "SELECT email FROM tbl_admin WHERE fabrica = 52 AND ativo IS TRUE AND privilegios = '*' AND email IS NOT NULL";
		$aux_res = pg_query($con, $aux_sql);
		$aux_row = pg_num_rows($aux_res);
		$aux_arr = array('gustavo.paulo@telecontrol.com.br', 'felipe.vaz@telecontrol.com.br');

		$data['dest'] 		  = 'gustavo.paulo@telecontrol.com.br';
		$data['dest_cliente'] = implode(",", $aux_arr);
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

	$sql = "SELECT interv_reinc.os
			INTO TEMP tmp_interv_reinc_fab
			FROM(
					SELECT
					ultima_reinc.os,
					(
						SELECT status_os 
						FROM tbl_os_status 
						WHERE tbl_os_status.os = ultima_reinc.os 
						AND   tbl_os_status.fabrica_status= $fabrica 
						AND   status_os IN (62) 
						ORDER BY os_status DESC LIMIT 1

					) AS ultimo_reinc_status

					FROM(SELECT DISTINCT os 
								FROM tbl_os_status 
								WHERE tbl_os_status.fabrica_status= $fabrica
								AND   status_os IN (62,64) 
						) ultima_reinc
				) interv_reinc
			WHERE interv_reinc.ultimo_reinc_status IN (62);
	";

	$res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Pedido com intervenção da fábrica";
    	$logs_erro[] = $sql;
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }
    
	$sql = "SELECT DISTINCT tbl_os.posto
				INTO TEMP tmp_fricon_gera_posto
			FROM  tbl_os_item
				JOIN tbl_os_produto     on tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_os             on tbl_os.os                 = tbl_os_produto.os
				JOIN tbl_posto_fabrica  on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $fabrica
				JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica=$fabrica
			WHERE   tbl_os_item.pedido IS NULL
				AND tbl_os.validada    IS NOT NULL
				AND tbl_os.excluida    IS NOT TRUE
				AND tbl_os.fabrica     = $fabrica
				AND tbl_os.posto    <> 6359
				AND tbl_os.troca_garantia       IS NULL
				and tbl_os.data_digitacao > '2013-07-30 00:00:00'
				AND tbl_os.troca_garantia_admin IS NULL
				AND tbl_servico_realizado.gera_pedido IS TRUE
				AND (credenciamento = 'CREDENCIADO' OR credenciamento = 'EM DESCREDENCIAMENTO')
				AND tbl_os.os NOT IN ( select os from tmp_interv_reinc_fab)
			;

			SELECT DISTINCT posto FROM tmp_fricon_gera_posto;";

    $res = pg_query($con, $sql);
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro ao identificar os postos com pedidos pendentes";
    	$logs_erro[] = $sql;
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }
   

    for ($i=0; $i < pg_num_rows($res); $i++) { 
      
		$posto = pg_fetch_result($res,$i,'posto');
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

	 	$sql = "SELECT tbl_os_item.peca,
				  tbl_os_item.qtde, 
				  tbl_os.os, 
				  tbl_os.posto,
				  tbl_peca.referencia,
				  tbl_os_item.os_item,
				  tbl_os_item.servico_realizado,
				  (SELECT DISTINCT tbl_produto.linha
					  FROM tbl_lista_basica
						  JOIN tbl_produto USING(produto)
					  WHERE tbl_lista_basica.peca = tbl_peca.peca
						  AND tbl_os.produto = tbl_produto.produto
					  ORDER BY linha 
					  LIMIT 1) AS linha,
				  0 as pedido
			  INTO TEMP tmp_os_item_fricon_{$posto}
		  FROM  tbl_os_item
			  JOIN tbl_os_produto     on tbl_os_produto.os_produto = tbl_os_item.os_produto
			  JOIN tbl_os             on tbl_os.os                 = tbl_os_produto.os
			  JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.fabrica = $fabrica
			  JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca and tbl_peca.fabrica = $fabrica
		  WHERE   tbl_os_item.pedido IS NULL
			  AND     tbl_os.validada    IS NOT NULL
			  AND     tbl_os.excluida    IS NOT TRUE
			  AND     tbl_os.fabrica    = $fabrica
			  AND     tbl_os.posto      = $posto
			  AND     tbl_os.troca_garantia       IS NULL
			  AND     tbl_os.troca_garantia_admin IS NULL
				and tbl_os.data_digitacao > '2013-07-30 00:00:00'
			  AND     tbl_servico_realizado.gera_pedido
			  AND tbl_os.os NOT IN ( select os from tmp_interv_reinc_fab)
			  ORDER BY tbl_os.os ASC;

		  SELECT peca, sum(qtde) as qtde, linha, os_item,os FROM tmp_os_item_fricon_{$posto} GROUP BY peca,linha,os_item,os;";

	      $res_peca = pg_query($con, $sql);
	      extract(pg_fetch_assoc($res_peca));
	      if(pg_last_error($con)){
		  $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro ao localizar as peças dos pedidos e suas respectivas quantidades do posto $codigo_posto - $nome_postoe";
		  $logs_erro[] = $sql;
		  $erro   = true;
		  throw new Exception ($msg_erro);
	      }
	   
	      $condicao = "1394";
		  $tipo_pedido = "151";

		  pg_query($con, "BEGIN TRANSACTION");
		  $erro_pedido = false;

		  $sqlPosto = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		  					FROM tbl_posto
		  					JOIN tbl_posto_fabrica USING(posto)
		  					WHERE tbl_posto.posto = $posto
		  					AND tbl_posto_fabrica.fabrica = $fabrica";
		  $resPosto = pg_query($con,$sqlPosto);
		  if(pg_last_error($con)){
				$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao localizar o posto '$posto'";
				$logs_erro[] 		= $sqlTabela;
				$erro   		= true;
				$erro_pedido	= true;
			}else{
				$codigo_posto 	= pg_fetch_result($resPosto, 0, 'codigo_posto');
				$nome_posto 	= pg_fetch_result($resPosto, 0, 'nome');
			}

			for ($x=0; $x < pg_num_rows($res_peca); $x++) {

				$peca 		= pg_fetch_result($res_peca,$x,'peca');
				$qtde 		= pg_fetch_result($res_peca,$x,'qtde');
				$os_item 	= pg_fetch_result($res_peca,$x,'os_item');
				$os 		= pg_fetch_result($res_peca,$x,'os');
				$linha 		= pg_fetch_result($res_peca,$x,'linha');

				$sqlTabela = "SELECT tabela FROM tbl_posto_linha JOIN tbl_linha USING(linha) WHERE linha = $linha AND posto = $posto and fabrica = $fabrica";
				$resTabela = pg_query($con,$sqlTabela);
				if(pg_last_error($con)){
					$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro com a tabela de preço da OS {$os} <br> Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde}";
					$logs_erro[] 		= $sqlTabela;
					$erro   		= true;
					$erro_pedido	= true;
				}else{
					$tabela = pg_fetch_result($resTabela, 0, 'tabela');

					$sqlPreco = "SELECT preco FROM tbl_tabela_item 
									JOIN tbl_tabela USING(tabela) 
									WHERE tbl_tabela_item.peca = $peca
									AND tbl_tabela_item.tabela = $tabela
									AND tbl_tabela.fabrica = $fabrica";
			
					$resPreco = pg_query($con,$sqlPreco);
					if(pg_num_rows($resPreco) == 0){
						$sqlPeca = "SELECT referencia,descricao FROM tbl_peca WHERE peca = $peca AND fabrica = $fabrica";
						$resPeca = pg_query($con,$sqlPeca);
						$peca_referencia 	= pg_fetch_result($resPeca, 0, 'referencia');
						$peca_descricao		= pg_fetch_result($resPeca, 0, 'descricao');

						$logs_peca_sem_preco[]= "A peça <b>$peca_referencia - $peca_descricao</b> está sem preço cadastrado. Favor verificar a tabela de preço que o Posto <b>$codigo_posto - $nome_posto atende</b><br>";
						$erro_pedido = true;
					}
				}
				
				$sqlEstoque = "SELECT peca FROM tbl_estoque_posto WHERE fabrica = $fabrica AND posto = $posto AND peca = $peca AND qtde >= $qtde";
					$resEstoque = pg_query($con,$sqlEstoque);
					if(pg_num_rows($resEstoque) > 0){
						$sqlAtualizaEstoque = "UPDATE tbl_estoque_posto SET qtde = qtde - $qtde WHERE fabrica = $fabrica AND posto = $posto AND peca = $peca";
						$resAtualizaEstoque = pg_query($con,$sqlAtualizaEstoque);
						if(pg_last_error($con)){
							$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao atualizar o estoque do posto {$posto} <br> OS: {$os} - Peça: {$peca} - Qtd: {$qtde}";
							$logs_erro[] 		= $sqlAtualizaEstoque;
							$erro   		= true;
							$erro_pedido	= true;
						}

						$sqtTrocaServico = "SELECT fn_atualiza_servico_os_item($os_item, servico_realizado, $fabrica) FROM tbl_servico_realizado WHERE fabrica = $fabrica AND peca_estoque IS TRUE";
						$resTrocaServico = pg_query($con,$sqtTrocaServico);
						if(pg_last_error($con)){
							$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao atualizar o serviço realizado da OS {$os} <br> OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde}";
							$logs_erro[] 		= $sqtTrocaServico;
							$erro   		= true;
							$erro_pedido	= true;
						}

						$sql4 = "INSERT INTO tbl_estoque_posto_movimento(
																		fabrica,
																		posto,
																		os,
																		peca,
																		data,
																		qtde_saida,
																		obs,
																		tipo) VALUES(
																		$fabrica,
																		$posto,
																		$os,
																		$peca,
																		CURRENT_DATE,
																		$qtde,
																		'Saída de estoque',
																		'Doação/Garantia'
																		)
																		";
						$res4 = pg_query($con,$sql4);

						$sql5 = "UPDATE tmp_os_item_fricon_{$posto} SET servico_realizado = tbl_servico_realizado.servico_realizado
						FROM tbl_servico_realizado
						WHERE tmp_os_item_fricon_{$posto}.os_item = $os_item
						AND   tbl_servico_realizado.peca_estoque IS TRUE
						AND tbl_servico_realizado.fabrica = $fabrica";
						$res5 = pg_query($con,$sql5);
					}
		    }

			$sql = "SELECT os_item, peca, qtde, tmp_os_item_fricon_{$posto}.linha 
					FROM tmp_os_item_fricon_{$posto} 
					JOIN tbl_servico_realizado ON tmp_os_item_fricon_{$posto}.servico_realizado = tbl_servico_realizado.servico_realizado 
					AND tbl_servico_realizado.fabrica = $fabrica 
					AND tbl_servico_realizado.peca_estoque IS NOT TRUE";
			$res_pecas = pg_query($con, $sql);
			for ($z=0; $z < pg_num_rows($res_pecas); $z++) { 

			  	$os_item = pg_fetch_result($res_pecas,$z,'os_item');
			  	$peca = pg_fetch_result($res_pecas,$z,'peca');
			  	$qtde = pg_fetch_result($res_pecas,$z,'qtde');
			  	
				  if($z == 0){
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
						  $logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao salvar o pedido";
						  $logs_erro[] 		= $sql;
						  $erro   		= true;
						  $erro_pedido	= true;
					  }else{
					  	$pedido = pg_fetch_result($res_pedido, 0);

					  }
				  }
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
					  $logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao salvar os itens do pedido <br> OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde}";
					  $logs_erro[] 		= $sql;
					  $erro   		= true;
					  $erro_pedido	= true;
			      }else{

			      	$pedido_item = pg_fetch_result($res_pedido_item, 0);

			      }
				  
				  $sql = "SELECT 
							  fn_atualiza_os_item_pedido_item ($os_item,$pedido,$pedido_item,$fabrica)";
			      $res_atualiza_pedido_item = pg_query($con, $sql);
			      if(pg_last_error($con)){
				  $logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao salvar os itens do pedido <br> OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde}";
				  $logs_erro[] 	= $sql;
				  $erro   		= true;
				  $erro_pedido	= true;
			      }

				  $sql = "UPDATE tmp_os_item_fricon_{$posto}
							  SET pedido = $pedido
						  WHERE os_item
								  IN(
									  SELECT os_item
									  FROM tmp_os_item_fricon_{$posto}
									  WHERE peca =  $peca
								  );";
			      $res_atualiza_pedido_item = pg_query($con, $sql);
			      if(pg_last_error($con)){
				  $logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao atualizar o status do pedido <br> OS: {$os} - Posto: {$posto} - Peça: {$peca} - Qtd: {$qtde}";
				  $logs_erro[] 	= $sql;
				  $erro   		= true;
				  $erro_pedido	= true;
			      }
		}

	     if($pedido){
	      	$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica);";
	     	$res_atualiza_pedido_item = pg_query($con, $sql);
	     }

		if(pg_last_error($con)){
			$erro   		= true;
			$erro_pedido	= true;
		}
		
			
			if(!$erro_pedido){
				$logs[] = "SUCESSO => Posto: '{$codigo_posto} - {$nome_posto}' - Pedido {$pedido} gerado com sucesso!";
				pg_query($con, "COMMIT TRANSACTION");
			}else{	    	
				pg_query($con, "ROLLBACK TRANSACTION");	
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
		$mailer->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");

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

     if(count($logs_peca_sem_preco)){
    	
    	$logs_peca_sem_preco[] = "<br>Att.<br>Telecontrol Networking";

    	$mailer = new PHPMailer();
		$mailer->IsSMTP();
		$mailer->IsHTML();
		$mailer->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
		$mailer->AddAddress('dspv6@fricon.com.br');
		$mailer->AddAddress('dspv.expedicao1@fricon.com.br');
		//$mailer->AddAddress('rodrigo@telecontrol.com.br');
		

		$mensagem  = implode("<br />", $logs_peca_sem_preco);

		$mailer->Subject = Date('d/m/Y')." - Peças sem preço";
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
			$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
			$mail->Subject = Date('d/m/Y')." - Erro na geração de pedido";
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

    Log::envia_email($data,Date('d/m/Y H:i:s')." - fricon - Erro na geração de pedido", $msg);
}?>
