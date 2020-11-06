<?php
	error_reporting(E_ALL ^ E_NOTICE);
	define('APP','Importa Pedido Cancelado - Esmaltec'); // Nome da rotina, para ser enviado por e-mail
	define('ENV','producao'); // Alterar para produção ou algo assim

	try {

		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';

		$fabrica     = 30;
		$data 	     = date('d-m-Y');
		
		$phpCron = new PHPCron($fabrica, __FILE__); 
		$phpCron->inicio();

		$vet['fabrica'] = 'esmaltec';
		$vet['tipo']    = 'importa-pedido';
		$vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
		$vet['log']     = 1;
		$logs           = array();

		$file = 'telecontrol-pedido-cancelado.txt';

		if ( ENV == 'testes' ) {
			$dir 	= '/home/ronald/perl/esmaltec/entrada/';
		} else {
			$dir 	= '/home/' . $vet['fabrica'] . '/' . $vet['fabrica'] . '-telecontrol/';
		}


		if ( !file_exists($dir.$file) ) {
			$logs[] = "ARQUIVO DE CANCELAMENTO DE PEDIDOS NÃO ENCONTRADO";
		}else{
			
			$sql = "DROP TABLE IF EXISTS ".$vet['fabrica']."_cancelado";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			if(strlen($msg_erro) > 0){
				$logs[] = "ERRO AO DELETAR A TABELA ".$vet['fabrica']."_cancelado";
			}else{
				$sql = "CREATE TABLE ".$vet['fabrica']."_cancelado 
                                (header_id text, txt_pedido_item text, txt_status text, txt_referencia text, txt_qtde text)";
        	                $res = pg_query($con,$sql);
	                        $msg_erro = pg_errormessage($con);
				
				if(strlen($msg_erro) > 0){
					$logs[] = "ERRO AO CRIAR A TABELA ".$vet['fabrica']."_cancelado";
				}else{

					$arquivo_conteudo = explode("\n", file_get_contents($dir.$file));
					$arquivo_conteudo = array_filter($arquivo_conteudo);

					foreach($arquivo_conteudo as $linha_numero => $linha_conteudo) {
			                        
						$linha_erro = false;

						list(
							$header_id,
							$pedido_item,
							$status,
							$referencia,
							$qtde
						) = explode(";", $linha_conteudo);

						$header_id         = trim($header_id);
						$pedido_item	   = trim($pedido_item);
						$status 	   = trim($status);
						$referencia        = trim($referencia);
						$qtde              = trim($qtde);

						if (!strlen($header_id)) {
							$logs[] = "CANCELAMENTO LINHA ".($linha_numero + 1).": - HEADERO INFORMADO";
							$linha_erro = true;
						}

						if (!strlen($pedido_item)) {
							$logs[] = "CANCELAMENTO LINHA ".($linha_numero + 1).": - NÚMERO DO PEDIDO ITEM NÃO INFORMADO";
							$linha_erro = true;
						}

						if (!strlen($referencia)) {
							$logs[] = "CANCELAMENTO LINHA ".($linha_numero + 1).": - PEÇA NÃO INFORMADA";
							$linha_erro = true;
						}else{
							$sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$fabrica} AND referencia = '{$referencia}'";
							$res = pg_query($con,$sql);
							
							if(pg_num_rows($res) == 0){
								$logs[] = "CANCELAMENTO LINHA ".($linha_numero + 1).": - PEÇA {$referencia} NÃO CADASTRADA";
								$linha_erro = true;
							}
						}

						if (!strlen($qtde)) {
							$logs[] = "CANCELAMENTO LINHA ".($linha_numero + 1).": - QUANTIDADE NÃO INFORMADA";
							$linha_erro = true;
						}	

						if($qtde == 0){
							$logs[] = "CANCELAMENTO LINHA ".($linha_numero + 1).": - QUANTIDADE NÃO PODE SER ZERO";
							$linha_erro = true;
						}
						if($linha_erro === false){

							$res = pg_query($con,"BEGIN");

							$sql = "INSERT INTO ".$vet['fabrica']."_cancelado(
													  header_id,
													  txt_pedido_item,
													  txt_status,
													  txt_referencia,
													  txt_qtde
													 )VALUES(
													  '{$header_id}',
													  '{$pedido_item}',
													  '{$status}',
													  '{$referencia}',
													  '{$qtde}'
													 )";
							$res = pg_query($con,$sql);
							$msg_erro = pg_errormessage($con);

							if(strlen($msg_erro) > 0){
								$logs[] = "CANCELAMENTO LINHA ".($linha_numero + 1).": - ERRO AO INSERIR REGISTRO NA TABELA ".$vet['fabrica']."_cancelado";
								$linha_erro = true;
							
							}

							if($linha_erro === false){
								$res = pg_query($con,"COMMIT");
							}else{
								$res = pg_query($con,"ROLLBACK");
							}
							

						}

						
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN qtde FLOAT";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO QTDE NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN pedido INT4";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO PEDIDO NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN pedido_item INT4";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO PEDIDO ITEM NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN posto INT4";
                                        $res = pg_query($con,$sql);
                                        $msg_erro = pg_errormessage($con);

                                        if(strlen($msg_erro) > 0){
                                            $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO POSTO NA TABELA ".$vet['fabrica']."_cancelado";
                                        }


					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN peca INT4";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO PECA NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN os INT4";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO OS NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN ipi INT4";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO IPI NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN tabela INT4";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO TABELA NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "ALTER TABLE ".$vet['fabrica']."_cancelado ADD COLUMN preco INT4";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CRIAR O CAMPO PRECO NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "UPDATE ".$vet['fabrica']."_cancelado SET txt_qtde = REPLACE (txt_qtde,'.','')";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR O CAMPO TXT QTDE NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "UPDATE ".$vet['fabrica']."_cancelado SET txt_qtde = REPLACE (txt_qtde,',','.')";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR O CAMPO TXT QTDE NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "UPDATE ".$vet['fabrica']."_cancelado SET qtde = txt_qtde::integer";
                                        $res = pg_query($con,$sql);
                                        $msg_erro = pg_errormessage($con);

                                        if(strlen($msg_erro) > 0){
                                            $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR O CAMPO QTDE NA TABELA ".$vet['fabrica']."_cancelado";
                                        }

					$sql = "UPDATE ".$vet['fabrica']."_cancelado SET 
									pedido_item = tbl_pedido_item.pedido_item,
									pedido      = tbl_pedido_item.pedido,
									posto       = tbl_pedido.posto,
									tabela      = tbl_pedido.tabela
							FROM   tbl_pedido_item, tbl_pedido
							WHERE ".$vet['fabrica']."_cancelado.txt_pedido_item::integer = tbl_pedido_item.pedido_item
							AND   tbl_pedido_item.pedido = tbl_pedido.pedido
							AND   tbl_pedido.fabrica = $fabrica";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR OS CAMPOS PEDIDO, PEDIDO ITEM E POSTO NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "UPDATE ".$vet['fabrica']."_cancelado SET os = tbl_os_produto.os
								FROM   tbl_os_produto, tbl_os_item
								WHERE ".$vet['fabrica']."_cancelado.pedido_item = tbl_os_item.pedido_item
								AND   ".$vet['fabrica']."_cancelado.pedido     = tbl_os_item.pedido
								AND   tbl_os_item.os_produto = tbl_os_produto.os_produto";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR O CAMPO ORDEM DE SERVICO NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "UPDATE ".$vet['fabrica']."_cancelado SET peca = tbl_peca.peca, ipi = tbl_peca.ipi
							FROM tbl_peca
							WHERE ".$vet['fabrica']."_cancelado.txt_referencia = tbl_peca.referencia
							AND   tbl_peca.fabrica = $fabrica";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR O CAMPO PECA NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$sql = "DELETE FROM ".$vet['fabrica']."_cancelado WHERE qtde IS NULL";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO CANCELAR OS ITENS DA TABELA ".$vet['fabrica']."_cancelado ONDE A QTDE É NULA";
					}

					$sql = "DROP TABLE IF EXISTS ".$vet['fabrica']."_cancelado_sem_peca";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO TENTAR APAGAR A TABELA ".$vet['fabrica']."_cancelado_sem_peca ";
					}

					$sql = "SELECT * INTO ".$vet['fabrica']."_cancelado_sem_peca FROM ".$vet['fabrica']."_cancelado WHERE peca IS NULL";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO GRAVAR REGISTROS NA TABELA ".$vet['fabrica']."_cancelado_sem_peca ";
					}

					$sql = "DELETE FROM ".$vet['fabrica']."_cancelado WHERE peca IS NULL";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO APAGAR REGISTROS NA TABELA ".$vet['fabrica']."_cancelado QUE NÃO POSSUEM PEÇA";
					}

					$sql = "DROP TABLE IF EXISTS ".$vet['fabrica']."_cancelado_sem_pedido";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO TENTAR APAGAR A TABELA ".$vet['fabrica']."_cancelado_sem_pedido ";
					}

					$sql = "SELECT * INTO ".$vet['fabrica']."_cancelado_sem_pedido FROM ".$vet['fabrica']."_cancelado WHERE pedido IS NULL";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO GRAVAR REGISTROS NA TABELA ".$vet['fabrica']."_cancelado_sem_pedido ";
					}

					$sql = "DELETE FROM ".$vet['fabrica']."_cancelado WHERE pedido IS NULL";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO APAGAR REGISTROS NA TABELA ".$vet['fabrica']."_cancelado QUE NÃO POSSUEM PEDIDO";
					}

					$sql = "UPDATE ".$vet['fabrica']."_cancelado SET preco = tbl_tabela_item.preco
							FROM tbl_tabela_item
							WHERE ".$vet['fabrica']."_cancelado.peca = tbl_tabela_item.peca
							AND   tbl_tabela_item.tabela = ".$vet['fabrica']."_cancelado.tabela";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR O CAMPO PRECO NA TABELA ".$vet['fabrica']."_cancelado";
					}

					$res = pg_query($con,"BEGIN");

					$sql = "INSERT INTO tbl_pedido_cancelado (fabrica, data, posto, pedido, pedido_item, peca, qtde, motivo,os) 
						(SELECT $fabrica,CURRENT_DATE, posto, pedido, pedido_item, peca, qtde, 'Item cancelado pela Fábrica', os FROM ".$vet['fabrica']."_cancelado WHERE txt_status = 'CANCELLED') ";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO INSERIR REGISTROS NA TABELA tbl_pedido_cancelado";
					}
					
					$sql = "INSERT INTO tbl_pedido_item (pedido, peca, qtde, preco, ipi, pedido_item_atendido, obs, tabela) 
						(SELECT pedido, peca, qtde, preco, ipi, pedido_item, 'Item inserido pela fabrica', tabela FROM ".$vet['fabrica']."_cancelado WHERE txt_status = 'AWAITING_SHIPPING') ";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO GRAVAR NOVOS ITENS REGISTROS NA TABELA tbl_pedido_item";
					}

					$sql = "SELECT fn_atualiza_pedido_item_cancelado (tbl_pedido_item.peca, tbl_pedido_item.pedido, tbl_pedido_item.pedido_item, ((tbl_pedido_item.qtde_cancelada + ".$vet['fabrica']."_cancelado.qtde)::integer))
							FROM ".$vet['fabrica']."_cancelado
							JOIN tbl_pedido_item ON tbl_pedido_item.pedido = ".$vet['fabrica']."_cancelado.pedido AND tbl_pedido_item.peca   = ".$vet['fabrica']."_cancelado.peca AND tbl_pedido_item.pedido_item = " . $vet['fabrica'] . "_cancelado.pedido_item
							AND ".$vet['fabrica']."_cancelado.txt_status = 'CANCELLED' ";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR OS ITENS DO PEDIDO";
					}

					$sql = "SELECT fn_atualiza_status_pedido($fabrica, ".$vet['fabrica']."_cancelado.pedido ) FROM ".$vet['fabrica']."_cancelado WHERE ".$vet['fabrica']."_cancelado.txt_status = 'CANCELLED'";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);

					if(strlen($msg_erro) > 0){
					    $logs[] = "CANCELAMENTO - ERRO AO ATUALIZAR OS STATUS DO PEDIDO";
					}

					if(strlen($msg_erro) > 0){
						$res = pg_query($con,"ROLLBACK");
					}else{
						$res = pg_query($con,"COMMIT");

						$sql = "SELECT header_id, pedido_item FROM ".$vet['fabrica']."_cancelado";
						$res = pg_query($con,$sql);

						if(pg_num_rows($res) > 0){
							$data = date("Y-m-d-H-i");
							$confirmacao = "/tmp/esmaltec/pedidos/confirma-pedido-cancelado-".$data.".txt";
							$fp = fopen("$confirmacao","w");

							for($i = 0; $i < pg_num_rows($res); $i++){

								$linha = pg_fetch_result($res,$i,'header_id') .";".pg_fetch_result($res,$i,'pedido_item')."\n";
								fwrite($fp, $linha);

							}

							fclose($fp);

							system("cp $confirmacao {$dir}confirma-pedido-cancelado.txt");
						}
					}

				}

			}

		}

		system ("mv ".$dir.$file." /tmp/".$vet['fabrica']."/pedidos/telecontrol-pedido-cancelado-$data.txt");
	
		if(count($logs) > 0){
			
			$header  = "MIME-Version: 1.0\n";
	        	$header .= "Content-type: text/html; charset=iso-8859-1\n";
        		$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";	
		
			mail("ronald.santos@telecontrol.com.br,marisa.silvana@telecontrol.com.br", "TELECONTROL / ESMALTEC ({$data}) - CANCELAMENTO DE PEDIDO", implode("<br />", $logs), $header);
			
			$fp = fopen("/tmp/esmaltec/pedidos/telecontrol-pedido-cancelado-$data.err","w");
			fwrite($fp,implode("<br />", $logs));
			fclose($fp);
			
		}
	
		$phpCron->termino();
		
	}
	catch (Exception $e) {

		echo $e->getMessage() , "\n\n";

		$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
		Log::envia_email($vet,APP, $msg );

	}
