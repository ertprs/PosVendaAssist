<?php
/**
 *
 * gera-pedido.php
 *
 * Gera��o de pedidos de pecas com base na OS
 *
 * @author  Ronald Santos
 * @version 2012.08.29
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produ��o ou algo assim

try {

	include dirname(__FILE__) . '/../dbconfig_pg.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['fabrica'] 		= 52;
    $data['fabrica_nome'] 	= 'fricon';
    $data['arquivo_log'] 	= 'gera-pedido-consignado';
    $data['log'] 			= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 					= array();
    $logs_erro				= array();
    $logs_cliente			= array();
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
		$aux_arr = array('gaspar.lucas@telecontrol.com.br', 'felipe.vaz@telecontrol.com.br');

		$data['dest'] 		  = 'gaspar.lucas@telecontrol.com.br';
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
    	$logs[] = "Erro ao gerar o pedido do tipo 'CONSIGNA��O'";
    	$erro   = true;
    }

    $dia = date('d');

    switch ($dia) {
    	case '07':
    		$posto_estado = " AND tbl_posto_fabrica.contato_estado IN('BA', 'MG', 'SE', 'DF', 'MS', 'MT', 'AM', 'MA', 'RO', 'AP', 'RR', 'TO', 'AC')";
    		break;
    	case '14':
    		$posto_estado = " AND tbl_posto_fabrica.contato_estado IN('PE', 'RN', 'PI')";
    		break;
    	case '21':
    		$posto_estado = " AND tbl_posto_fabrica.contato_estado IN('SP', 'RJ', 'PR', 'RS', 'GO', 'PA', 'SC', 'AL')";
    		break;
    	case '28':
    		$posto_estado = " AND tbl_posto_fabrica.contato_estado IN('CE', 'ES', 'PB')";
    		break;
    }
   
	$sql = "SELECT DISTINCT tbl_posto.posto
				INTO TEMP tmp_fricon_gera_posto
			FROM  tbl_os_item
				JOIN tbl_os_produto     on tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_os             on tbl_os.os                 = tbl_os_produto.os
				JOIN tbl_posto          on tbl_posto.posto           = tbl_os.posto
				JOIN tbl_posto_fabrica  on tbl_posto_fabrica.posto   = tbl_posto.posto and tbl_posto_fabrica.fabrica = $fabrica
				JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica=$fabrica
				JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca and tbl_peca.fabrica = $fabrica
			WHERE   tbl_os.validada    IS NOT NULL
				AND tbl_os.excluida    IS NOT TRUE
				AND tbl_os.fabrica     = $fabrica
				AND tbl_os.posto    <> 6359
				$posto_estado
				AND tbl_os.troca_garantia       IS NULL
				AND tbl_os.troca_garantia_admin IS NULL
				AND     ( tbl_servico_realizado.peca_estoque IS TRUE AND tbl_os_item.pedido isnull )
			    AND     tbl_os_item.digitacao_item > current_timestamp - interval '1 months'
				AND (credenciamento = 'CREDENCIADO' OR credenciamento = 'EM DESCREDENCIAMENTO');

			SELECT DISTINCT posto FROM tmp_fricon_gera_posto;";

    $res = pg_query($con, $sql);
    
    if(pg_last_error($con)){
    	$logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro ao identificar os postos com pedidos pendentes";
    	$logs_erro[] = $sql;
    	$erro   = true;
    	throw new Exception ($msg_erro);
    }


    for ($i=0; $i < pg_num_rows($res); $i++) { 
      
      $posto = pg_result($res,$i,'posto');   

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
		  WHERE   	  tbl_os.validada    IS NOT NULL
			  AND     tbl_os.excluida    IS NOT TRUE
			  AND     tbl_os.fabrica    = $fabrica
			  AND     tbl_os.posto      = $posto
			  AND     tbl_os.troca_garantia       IS NULL
			  AND     tbl_os.troca_garantia_admin IS NULL
			  AND     ( tbl_servico_realizado.peca_estoque IS TRUE AND tbl_os_item.pedido isnull )
			  AND      tbl_os_item.digitacao_item > current_timestamp - interval '1 months'
			  ORDER BY tbl_os.os ASC;

          SELECT peca, sum(qtde) as qtde, linha, referencia, os_item,os FROM tmp_os_item_fricon_{$posto} GROUP BY peca,linha,referencia,os_item,os;

		  ";

          //SELECT peca, sum(qtde) as qtde, linha FROM tmp_os_item_fricon_{$posto} GROUP BY peca,linha;
	      $res_peca = pg_query($con, $sql);  

	      extract(pg_fetch_assoc($res_peca));

	      if(pg_last_error($con)){
			  $logs[] = $msg_erro = Date("Y-m-d H:i:s")." - Erro ao identificar os itens dos pedidos";
			  $logs_erro[] = $sql;
			  $erro   = true;
			  throw new Exception ($msg_erro);
	      }

	      $condicao = "1394";
	    if(pg_num_rows($res_peca) > 0){
		  pg_query($con, "BEGIN TRANSACTION");
		  $erro_pedido = false;

		  	$sql     = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$fabrica." and lower(descricao) = 'consignado';";
		  	$res_pedido_consignado = pg_query($con,$sql);
			if(pg_last_error($con)){
				$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao consultar os pedidos do tipo 'CONSIGNA��O'";
				$logs_erro[] 		= $sql;
				$erro   		= true;
				$erro_pedido	= true;
			}else{
				$tipo_pedido_consignado = pg_fetch_result($res_pedido_consignado,0,'tipo_pedido');

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
						  $tipo_pedido_consignado,
						  1           ,
						  't'         ,
						  $linha
					    ) RETURNING pedido;";

				$res_pedido = pg_query($con, $sql);
				if(pg_last_error($con)){
					$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao gerar o(s) pedido(s) de garantia";
					$logs_erro[] 		= $sql;
					$erro   		= true;
					$erro_pedido	= true;
				}else{
					$pedido = pg_fetch_result($res_pedido, 0,0);
				}
			}

			for ($x=0; $x < pg_num_rows($res_peca); $x++) { 
                $os_item = pg_result($res_peca,$x,'os_item');
				$peca = pg_result($res_peca,$x,'peca');
				$qtde = pg_result($res_peca,$x,'qtde');
				$referencia = pg_result($res_peca,$x,'referencia');

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

					$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao salvar os itens do pedido <br> OS: {$os} - Posto: {$nome_posto} - Pe�a: {$referencia} - Qtd: {$qtde}";
					$logs_erro[] 		= $sql;
					$erro   		= true;
					$erro_pedido	= true;
					break;
				}else{
			      	$pedido_item = pg_fetch_result($res_pedido_item, 0);
				}				  

				$sql = "SELECT 
						  fn_atualiza_os_item_pedido_item ($os_item,$pedido,$pedido_item,$fabrica)
						  ";
				$res_atualiza_pedido_item = pg_query($con, $sql);
				if(pg_last_error($con)){

					$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao atualizar os itens do pedido <br> OS: {$os} - Posto: {$nome_posto} - Pe�a: {$referencia} - Qtd: {$qtde}";
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

					$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao atualizar o pedido <br> OS: {$os} - Posto: {$nome_posto} - Pe�a: {$referencia} - Qtd: {$qtde}";
					$logs_erro[] 	= $sql;
					$erro   		= true;
					$erro_pedido	= true;
				}


			}

			if(!$erro_pedido) {
				$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica);";
				$res_atualiza_pedido_item = pg_query($con, $sql);
				if(pg_last_error($con)){

					$logs[] 		= $msg_erro = Date("Y-m-d H:i:s")." - Erro ao finalizar o pedido <br> OS: {$os} - Posto: {$nome_posto} - Pe�a: {$referencia} - Qtd: {$qtde}";
					$logs_erro[] 	= $sql;
					$logs[] 		= pg_last_error($con);
					$logs_cliente[] 	= Array(
											  'posto'	=> $nome_posto, 
											  'peca'	=> $referencia,
											  'erro'	=> "Erro ao finalizar o pedido <br> OS: {$os} - Posto: {$nome_posto} - Pe�a: {$referencia} - Qtd: {$qtde}"
									  );
					$erro   		= true;
					$erro_pedido	= true;
				}
			}

			if(!$erro_pedido){
				$logs[] = "SUCESSO => Posto: '{$codigo_posto} - {$nome_posto}' - Pedido {$pedido} gerado com sucesso!";
				pg_query($con, "COMMIT TRANSACTION");
			}else{	    	
				$logs[] = "ERRO => Posto: '{$codigo_posto} - {$nome_posto}' - N�o gerou pedido!";
				pg_query($con, "ROLLBACK TRANSACTION");	
			}

		}
	}

    if(count($logs_cliente)){
    	$msg = array();
    	$msg[] = "Erro na gera��o de pedido<br />";

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

    		$msg[] = "O Posto '{$codigo_posto} - {$nome}' n�o gerou pedido!";
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

		$mailer->Subject = Date('d/m/Y')." - Erro na gera��o de pedido";
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
			$mail->Subject = Date('d/m/Y')." - Erro na gera��o de pedido";
		    $mail->Body = $mensagem;
		    $mail->AddAddress($dest);
		    if(file_exists($arquivo_err) AND filesize($arquivo_err) > 0)
		    	$mail->AddAttachment($arquivo_err);
		    $mail->Send();
    	}
    }

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descri��o do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - fricon - Erro na gera��o de pedido", $msg);
}?>
