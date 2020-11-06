<?php

	/*
	 	* gera-pedido.php
	 	* @author  Guilherme Henrique da Silva
	 	* @version 21/06/2013
	*/

	error_reporting(E_ALL ^ E_NOTICE);
	define('ENV','producao');  // definição de envio de email

	try{

		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';
		include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	    $vet['fabrica'] = 'dellar';
	    $vet['tipo'] 	= 'pedido';
	    $vet['log'] 	= 2;
		$login_fabrica 		= 98;
	    $data_sistema	= Date('Y-m-d');
	    $logs_erro		= array();
		
		$phpCron = new PHPCron($login_fabrica, __FILE__); 
		$phpCron->inicio();
		
	    if(ENV == 'producao'){
			$vet['dest'] 		= 'helpdesk@telecontrol.com.br';
			$vet['dest'] 		= 'flavia@dellar.com.br';
			$vet['dest'] 		= 'marcos@dellar.com.br';
			$vet['dest']            = 'marisa.silvana@telecontrol.com.br';
	    }else{
	    	$vet['dest'] 		= 'ronald.santos@telecontrol.com.br';
	    	// $vet['dest'] 		= 'rodrigo.perina@telecontrol.com.br';
	    }

	    $arquivo_err = "/tmp/dellar/gera-pedido-{$data_sistema}.txt";
	    $arquivo_log = "/tmp/dellar/gera-pedido-{$data_sistema}.log";
	    system ("mkdir /tmp/dellar/ 2> /dev/null ; chmod 777 /tmp/dellar/" );

	    $sql = "
	    		SELECT  
					tbl_os.posto        ,
					tbl_produto.linha   ,
					tbl_os_item.peca    ,
					tbl_os_item.os_item ,
					tbl_os_item.qtde    ,
					tbl_os.sua_os       
				INTO TEMP 
					tmp_pedido_dellar
				FROM    
					tbl_os_item
				JOIN    
					tbl_servico_realizado USING (servico_realizado)
				JOIN    
					tbl_os_produto USING (os_produto)
				JOIN    
					tbl_os USING (os)
				JOIN    
					tbl_posto USING (posto)
				JOIN    
					tbl_produto ON tbl_os.produto = tbl_produto.produto
				JOIN    
					tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
				LEFT JOIN 
					tbl_os_status ON tbl_os.os=tbl_os_status.os
				AND 
					tbl_os_status.os_status = (SELECT MAX(os_status) FROM tbl_os_status WHERE tbl_os_status.os=tbl_os.os AND tbl_os_status.status_os IN (62,147,64)) 
				WHERE   
					tbl_os_item.pedido IS NULL
					AND tbl_os.validada IS NOT NULL
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.posto <> 6359
					AND tbl_os.fabrica = $login_fabrica
					AND tbl_os.troca_garantia IS NULL
					AND tbl_os.troca_garantia_admin IS NULL
					AND (tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					OR  tbl_posto_fabrica.credenciamento = 'EM DESCREDENCIAMENTO') 
					AND tbl_servico_realizado.gera_pedido 
					AND (tbl_os_status.status_os=64 OR tbl_os_status.status_os IS NULL);
				
				SELECT DISTINCT posto,linha from tmp_pedido_dellar;
	    	";
	   
		$res = pg_query($con, $sql);

		if(pg_last_error($con)){
	    		$logs_erro[] = "Query (1) ".pg_last_error($con);
	    }

	    if(pg_num_rows($res) > 0 AND count($logs_erro) == 0){
		
			while($data = pg_fetch_object($res)){ // WHILE

				$posto = $data->posto;
				$linha = $data->linha;

				$erro = "";
				$resultX = pg_query($con,"BEGIN TRANSACTION");

				/* Condição */
				$sql = "select condicao from tbl_condicao where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
				$result = pg_query($con, $sql);

				if(pg_last_error($con)){
					$erro .= "Erro Condição Pagamento ".pg_last_error($con);
				}

				while($data2 = pg_fetch_object($result)){
					$condicao = $data2->condicao;
				}

				/* Tipo do Pedido */
				$sql = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$login_fabrica." and lower(descricao) = 'garantia';";
				$result = pg_query($con, $sql);

				if(pg_last_error($con)){
					$erro .= "Erro Tipo de pedido ".pg_last_error($con);
				}

				while($data2 = pg_fetch_object($result)){
					$tipo_pedido = $data2->tipo_pedido;
				}

				/* * */
				$sql = "
					INSERT INTO tbl_pedido (
						posto        ,
						fabrica      ,
						condicao     ,
						tipo_pedido  ,
						linha        ,
						status_pedido
					) VALUES (
						$posto      	,
						$login_fabrica	,
						$condicao   	,
						$tipo_pedido 	,
						$linha      	,
						1
					);
				";
				$result = pg_query($con, $sql);

				if(pg_last_error($con)){
					$erro .= "Erro ao Inserir pedido ".pg_last_error($con);
				}

				/* * */
				$sql = "SELECT currval ('seq_pedido') as pedido";
				$result = pg_query($con, $sql);

				if(pg_last_error($con)){
					$erro .= "Erro na sequencia do pedido ".pg_last_error($con);
				}

				while($data2 = pg_fetch_object($result)){
					$pedido = $data2->pedido;
				}

				/* * */
				$sql = "
					SELECT  
						peca    ,
						os_item, 
						sum(qtde) as total
					from 
						tmp_pedido_dellar 
					WHERE posto = $posto 
						AND linha = $linha
					group by peca,os_item
				";
				$result2 = pg_query($con, $sql);

				if(pg_last_error($con)){
					$erro .= "Query (2) ".pg_last_error($con);
				}

				while($data2 = pg_fetch_object($result2)){

					$peca = $data2->peca;
					$os_item = $data2->os_item;
					$qtde = $data2->total;

					$sql = "
						INSERT INTO tbl_pedido_item (
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
							0      )
					";
					$result = pg_query($con, $sql);

					if(pg_last_error($con)){
						$erro .= "Query (3) ".pg_last_error($con);
					}

					$sql = "SELECT CURRVAL ('seq_pedido_item') as seq_pedido_item";
					$result = pg_query($con, $sql);

					if(pg_last_error($con)){
						$erro .= "Query (4) ".pg_last_error($con);
					}

					while($data3 = pg_fetch_object($result)){
						$pedido_item = $data3->seq_pedido_item;
					}

					$sql = "
						SELECT fn_atualiza_os_item_pedido_item(os_item ,$pedido,$pedido_item, $login_fabrica)
						FROM    tmp_pedido_dellar
						WHERE    tmp_pedido_dellar.peca  = $peca
						AND     tmp_pedido_dellar.posto = $posto
						AND     tmp_pedido_dellar.linha = $linha
						AND	tmp_pedido_dellar.os_item = $os_item
					";
					$result = pg_query($con, $sql);

					if(pg_last_error($con)){
						$erro .= "Query (5) ".pg_last_error($con);
					}

					// Adiciona Items
					$itens_gerados = $itens_gerados + 1;

				}

				$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
				$result = pg_query($con, $sql);

				if(pg_last_error($con)){
					$erro .= "Erro ao finalizar o pedido ".pg_last_error($con);
				}

				/* Se houver erro */
				if(strlen($erro) > 0){

					$sql = "
						SELECT 
							DISTINCT codigo_posto,
							tmp_pedido_dellar.sua_os,
							referencia,
							qtde,
							tbl_tabela_item.preco
						FROM 
							tmp_pedido_dellar 
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tmp_pedido_dellar.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_peca USING(peca)
						JOIN tbl_posto_linha ON tbl_posto_linha.posto = tmp_pedido_dellar.posto
						JOIN tbl_tabela_item ON tbl_tabela_item.peca = tmp_pedido_dellar.peca and tbl_tabela_item.tabela = tbl_posto_linha.tabela
						JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.fabrica = $login_fabrica
						WHERE tbl_tabela_item.posto = $posto
					";
					#$result = pg_query($con, $sql);

					$erro = preg_replace('/ERROR: /','',$erro);
					$erro = preg_replace('/CONTEXT:  .+\nPL.+/','',$erro);

					$resultX = pg_query($con, "ROLLBACK TRANSACTION");

					$erro = str_replace("ç", "c", $erro);

					$logs_erro[] = $erro;
			    	
					$erro = "";
				}else{

					$resultX= pg_query($con,"COMMIT TRANSACTION");
				}

			} // FIM WHILE

			
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
				$mail->Subject = Date('d/m/Y')." - Erro ao gerar pedido - Dellar";
				$mail->Body = "Erro ao gerar pedido";
				$mail->AddAddress($vet['dest']);
				if(file_exists($arquivo_err) AND filesize($arquivo_err) > 0)
				$mail->AddAttachment($arquivo_err);
				$mail->Send();
			}

		} // FIM IF
		
		$phpCron->termino();

	}catch(Exception $e){

		$e->getMessage();
    	$msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    	Log::envia_email($data,Date('d/m/Y H:i:s')." - DELLAR - Erro na geração de pedido(gera-pedido.php)", $msg);

	}


?>
