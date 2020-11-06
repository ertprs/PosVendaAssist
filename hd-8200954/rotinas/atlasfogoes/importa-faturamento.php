<?php

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $ambiente = "dev";

	$fabrica  = "74";
	$origem   = "/home/atlas/atlas-telecontrol";
	$destino  = "/home/atlas/atlas-telecontrol/bkp";
	$arquivos = "/tmp/atlas/faturamento";

	/* Testes
        $origem   = "atlas_testes";
        $destino  = "atlas_testes";
        $arquivos = "atlas_testes";
    #*/

	/* Inicio Processo */
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	function validaData($data){
		$data = explode('-', $data);
		if(checkdate($data[1], $data[2], $data[0])){
			return 0;
		}
		else{
			return 1;
		}
	}

	if(file_exists("$origem/faturamento.txt")){

		$sql = "DROP TABLE IF EXISTS atlas_nf;";
		$res = pg_query($con, $sql);
		if(strlen(trim(pg_last_error($con)))>0){
			$msg_erro_interno .= "Falha ao excluir tabela atlas_nf ".pg_last_error($con). "<Br>";
		}else{
			$sql = "CREATE TABLE atlas_nf
				(
				txt_cnpj     text,
				nota_fiscal  text,
				serie        text,
				txt_emissao  text,
				cfop         text,
				txt_total    text,
				txt_ipi      text,
				txt_icms     text,
				transp       text,
				txt_natureza text,
				conhecimento text
				)
				";
			$res =pg_query($sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Falha ao criar tabela atlas_nf ".pg_last_error($con). "<Br>";
			}
		}
			$dados_faturamento = file("$origem/faturamento.txt");
			$num_linha = 1;
			foreach($dados_faturamento as $linha){

				$valores = explode("\t", $linha);
				$log_erro ="";

				if(strlen(trim($linha))>0){
					$txt_cnpj 		= trim($valores[0]);
					$nota_fiscal	= trim($valores[1]);
					$serie			= trim($valores[2]);
					$txt_emissao	= trim($valores[3]);
					$cfop			= trim($valores[4]);
					$txt_total		= trim($valores[5]);
					$txt_ipi		= trim($valores[6]);
					$txt_icms		= trim($valores[7]);
					$transp			= trim($valores[8]);
					$txt_natureza	= trim($valores[9]);
					$conhecimento	= trim($valores[10]);

			//Valida se posto existe
					/*$sql_posto = "select * from tbl_posto where cnpj = '$txt_cnpj'";
					$res_posto = pg_query($con, $sql_posto);
					if(pg_num_rows($res_posto)==0){
						$log .= "Arquivo faturamento linha = $num_linha.  O posto de CNPJ $txt_cnpj não foi encontrado \n\n";
						$log_erro = "ok";
					}*/

			//valida se a data é valida.
					if(validaData($txt_emissao)){
						$log .= "A data $txt_emissao não é uma data válida. \n\n";
						$log_erro = "ok";
					}

					if(strlen(trim($conhecimento))==0){
						$conhecimento = 'null';
					}else{
						$conhecimento = "'$conhecimento'";
					}

					if(strlen(trim($log_erro))==0){
						$sql = "INSERT INTO atlas_nf (txt_cnpj,
											nota_fiscal,
											serie,
											txt_emissao,
											cfop,
											txt_total,
											txt_ipi,
											txt_icms,
											transp,
											txt_natureza,
											conhecimento) values (
												'$txt_cnpj',
												'$nota_fiscal',
												'$serie',
												'$txt_emissao',
												'$cfop',
												'$txt_total',
												'$txt_ipi', '$txt_icms', '$transp', '$txt_natureza', $conhecimento)";
						$res = pg_query($con, $sql);
					}
				}
				$num_linha ++;
			}

			$sql = "ALTER TABLE atlas_nf ADD COLUMN total FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna total".pg_last_error($con). "<Br>";
			}

			$sql = "ALTER TABLE atlas_nf ADD COLUMN posto INT4";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna posto ".pg_last_error($con). "<Br>";
			}

			$sql = "UPDATE atlas_nf SET total = REPLACE(txt_total,',','.')::numeric";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar o campo total (replace)". pg_last_error($con). "\n";
			}


			$sql = "UPDATE atlas_nf SET posto =
			(
				SELECT tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
									AND   tbl_posto_fabrica.fabrica = $fabrica
				WHERE atlas_nf.txt_cnpj = tbl_posto.cnpj
			)";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo posto". pg_last_error($con). "\n";

			}

			#------------ IDENTIFICAR POSTOS NAO ENCONTRADOS PELO CNPJ --------------#
			$sql = "DROP TABLE IF EXISTS atlas_nf_sem_posto";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao apagar tabela atlas_nf_sem_posto". pg_last_error($con). "\n";
			}

			#print "select post null \n";
			$sql = "SELECT * INTO atlas_nf_sem_posto
					FROM atlas_nf
						WHERE posto IS NULL
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao fazer busca na tabela atlas_nf_sem_posto". pg_last_error($con). "\n";
			}


	#---------------------------- Importa ITENS das Notas Fiscais -------------------------------

			$sql = "DROP TABLE IF EXISTS atlas_nf_item";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao apagar tabela atlas_nf_item". pg_last_error($con). "\n";

			}

			$sql = "CREATE TABLE atlas_nf_item
					(
						txt_cnpj            text,
						nota_fiscal         text,
						serie               text,
						referencia_atendida text,
						txt_pedido          text,
						txt_pedido_item		text,
						txt_qtde            text,
						txt_unitario        text,
						txt_aliq_ipi        text,
						txt_aliq_icms       text,
						txt_valor_ipi       text,
						txt_valor_icms      text,
						txt_valor_sub_icms  text,
						txt_base_ipi        text,
						txt_base_icms       text,
						txt_base_sub_icms  text
					)
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao criar tabela atlas_nf_item". pg_last_error($con). "\n";
			}

			$dados_faturamento_item = file("$origem/faturamento_item.txt");

			if(!file_exists("$origem/faturamento_item.txt")){
				$log .= "Arquivo de Itens do faturamento não encontrado. <br>";
			}
			$num_linha = 1;

			foreach($dados_faturamento_item as $linha_item){
				$valores_itens = explode("\t", $linha_item);

				$log_erro ="";

				if(strlen(trim($linha_item))>0){

					$txt_cnpj 				= trim($valores_itens[0]);
					$nota_fiscal 			= trim($valores_itens[1]);
					$serie 					= trim($valores_itens[2]);
					$referencia_atendida 	= trim($valores_itens[3]);
					$txt_pedido 			= trim($valores_itens[4]);
					$txt_pedido             = preg_replace("/\D/","",$txt_pedido);
					$txt_pedido_item 		= trim($valores_itens[5]);
					$txt_pedido_item        = preg_replace("/\D/","",$txt_pedido_item);
					$txt_qtde 				= trim($valores_itens[6]);
					$txt_unitario 			= trim($valores_itens[7]);
					$txt_aliq_ipi 			= trim($valores_itens[8]);
					$txt_aliq_icms 			= trim($valores_itens[9]);
					$txt_valor_ipi 			= trim($valores_itens[10]);
					$txt_valor_icms 		= trim($valores_itens[11]);
					$txt_valor_sub_icms 	= trim($valores_itens[12]);
					$txt_base_ipi 			= trim($valores_itens[13]);
					$txt_base_icms 			= trim($valores_itens[14]);
					$txt_base_sub_icms 		= trim($valores_itens[15]);


				//Valida se posto existe
					/*$sql_posto = "select * from tbl_posto where cnpj = '$txt_cnpj'";
					$res_posto = pg_query($con, $sql_posto);
					if(pg_num_rows($res_posto)==0){
						$log .= "Arquivo faturamento_item linha = $num_linha. O posto de CNPJ $txt_cnpj não foi encontrado \n\n";
						$log_erro = "ok";
					}*/

					if($log_erro == ""){

						$sql = "INSERT INTO atlas_nf_item (txt_cnpj,
											nota_fiscal,
											serie,
											referencia_atendida,
											txt_pedido,
											txt_pedido_item,
											txt_qtde,
											txt_unitario,
											txt_aliq_ipi,
											txt_aliq_icms,
											txt_valor_ipi,
											txt_valor_icms,
											txt_valor_sub_icms,
											txt_base_ipi,
											txt_base_icms,
											txt_base_sub_icms)
											values('$txt_cnpj',
											'$nota_fiscal',
											'$serie',
											'$referencia_atendida',
											'$txt_pedido',
											'$txt_pedido_item',
											'$txt_qtde',
											'$txt_unitario',
											'$txt_aliq_ipi',
											'$txt_aliq_icms' ,
											'$txt_valor_ipi',
											'$txt_valor_icms',
											'$txt_valor_sub_icms',
											'$txt_base_ipi',
											'$txt_base_icms',
											'$txt_base_sub_icms')";
						$res = pg_query($con, $sql);
					}
				}
				$num_linha ++;
			}



			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN posto INT4";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna posto na tabela atlas_nf_item". pg_last_error($con). "\n";

			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN peca INT4";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna peça na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna peça na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN qtde FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna qtde na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna qtde na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN pedido INT4";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna pedido na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna pedido na tabela atlas_nf_item. <Br>";
			}


			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN pedido_item INT4";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna pedido item na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna pedido item na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN unitario FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna unitario na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna unitario na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN aliq_ipi FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna aliq_ipi na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna aliq_ipi na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN valor_ipi FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna valor_ipi na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna valor_ipi na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN valor_icms FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna valor_icms na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna valor_icms na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN base_icms FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna base_icms na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna base_icms na tabela atlas_nf_item. <Br>";
			}

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN aliq_icms FLOAT";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna aliq_icms na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna aliq_icms na tabela atlas_nf_item. <Br>";
			}

			// HD-2782600
	            $sql = "ALTER TABLE atlas_nf_item ADD COLUMN garantia_antecipada BOOLEAN";
	            $res = pg_query($con, $sql);

	            if(strlen(trim(pg_last_error($con)))>0){
	                $msg_erro_interno .= "Erro ao alterar coluna aliq_icms na tabela atlas_nf_item". pg_last_error($con). "\n";
	                //$msg_erro_cliente .= "Erro ao alterar coluna aliq_icms na tabela atlas_nf_item. <Br>";
	            }
	        // FIM - HD-2782600

			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN tipo_pedido int4";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao alterar coluna tipo_pedido na tabela atlas_nf_item". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao alterar coluna tipo_pedido na tabela atlas_nf_item. <Br>";
			}

			$sql = "UPDATE atlas_nf_item SET
						qtde       = txt_qtde::numeric                        ,
						unitario   = REPLACE(case when length(txt_unitario)    = 0 then '0' else txt_unitario end   ,',','.')::numeric   ,
						aliq_ipi   = REPLACE(case when length(txt_aliq_ipi )   = 0 then '0' else txt_aliq_ipi end   ,',','.')::numeric   ,
						valor_ipi  = REPLACE(case when length(txt_valor_ipi )  = 0 then '0' else txt_valor_ipi end  ,',','.')::numeric  ,
						valor_icms = REPLACE(case when length(txt_valor_icms ) = 0 then '0' else txt_valor_icms end ,',','.')::numeric ,
						base_icms  = REPLACE(case when length(txt_base_icms )  = 0 then '0' else txt_base_icms end  ,',','.')::numeric ,
						aliq_icms  = REPLACE(case when length(txt_aliq_icms )  = 0 then '0' else txt_aliq_icms end  ,',','.')::numeric ";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar tabela atlas_nf_item (replace)". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao atualizar tabela atlas_nf_item (replace). <Br>";
			}

			#print "update posto \n";
			$sql = "UPDATE atlas_nf_item SET posto = (
						SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
											AND   tbl_posto_fabrica.fabrica = $fabrica
						WHERE atlas_nf_item.txt_cnpj = tbl_posto.cnpj
					)";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar posto na tabela atlas_nf_item". pg_last_error($con). "\n";
			}

			#print "update pedido \n";
			$sql = "UPDATE atlas_nf_item
					SET pedido = tbl_pedido.pedido
					FROM tbl_pedido
					WHERE atlas_nf_item.txt_pedido::numeric = tbl_pedido.pedido
					AND tbl_pedido.fabrica = $fabrica
					AND (txt_pedido is not null and length(trim (txt_pedido))> 0);
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo pedido na tabela atlas_nf_item". pg_last_error($con). "\n";
			}

			$sql = "UPDATE atlas_nf_item
					SET pedido_item = tbl_pedido_item.pedido_item
					FROM tbl_pedido_item
					WHERE atlas_nf_item.txt_pedido_item::numeric = tbl_pedido_item.pedido_item
					AND tbl_pedido_item.pedido = atlas_nf_item.pedido
					AND (txt_pedido_item is not null and length(trim (txt_pedido_item))> 0);
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo pedido item na tabela atlas_nf_item". pg_last_error($con). "\n";
			}

			$sql_pega_posto_pedido_cliente = "UPDATE atlas_nf_item SET posto = tbl_pedido.posto from tbl_pedido WHERE atlas_nf_item.txt_pedido::numeric = tbl_pedido.pedido AND tbl_pedido.fabrica = $fabrica AND (txt_pedido is not null and length(trim (txt_pedido))> 0) AND atlas_nf_item.posto is null";
			$res_pega_posto_pedido_cliente = pg_query($con, $sql_pega_posto_pedido_cliente);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo posto na tabela atlas_nf_item". pg_last_error($con). "\n";
			}

			$sql = "UPDATE atlas_nf SET posto = atlas_nf_item.posto 
					from atlas_nf_item 
					WHERE atlas_nf_item.txt_cnpj = atlas_nf.txt_cnpj 
					AND atlas_nf.nota_fiscal = atlas_nf_item.nota_fiscal   
					AND atlas_nf.posto is null";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo posto na tabela atlas_nf_item". pg_last_error($con). "\n";
			}


			$sql = "DELETE FROM atlas_nf
				WHERE posto IS NULL";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao deletar dados sem posto". pg_last_error($con). "\n";
			}


			$sql = "UPDATE atlas_nf_item
					SET peca = tbl_peca.peca
					FROM  tbl_peca
					WHERE atlas_nf_item.referencia_atendida = tbl_peca.referencia
					AND tbl_peca.fabrica = $fabrica";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo peça na tabela atlas_nf_item". pg_last_error($con). "\n";
			}

			$sql = "UPDATE atlas_nf_item SET tipo_pedido = (
						SELECT tbl_pedido.tipo_pedido
						FROM tbl_pedido
						WHERE atlas_nf_item.pedido = tbl_pedido.pedido
					)";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo tipo_pedido na tabela atlas_nf_item". pg_last_error($con). "\n";
			}

			$sql = "UPDATE atlas_nf_item SET garantia_antecipada = tbl_tipo_pedido.garantia_antecipada 
				FROM tbl_tipo_pedido 
				WHERE atlas_nf_item.tipo_pedido = tbl_tipo_pedido.tipo_pedido 
				AND tbl_tipo_pedido.fabrica = $fabrica";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar o campo garantia_antecipada na tabela atlas_nf_item ".pg_last_error($con)."\n";
			}


			#------------ Desconsidera Notas ja Importadas ------------------
			#print "Já importadas \n";

			$sql = "DELETE FROM atlas_nf_item
						WHERE pedido is null;
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao deletar dados sem pedido". pg_last_error($con). "\n";
			}

			$sql_update = "UPDATE tbl_faturamento SET conhecimento = atlas_nf.conhecimento from atlas_nf
						   WHERE atlas_nf.nota_fiscal    = tbl_faturamento.nota_fiscal
							AND   atlas_nf.serie          = tbl_faturamento.serie
							AND   tbl_faturamento.posto   = atlas_nf.posto
							AND   tbl_faturamento.fabrica = $fabrica
							AND   tbl_faturamento.conhecimento is null";
			$res_update = pg_query($con, $sql_update);

			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atulizar dados da tabela faturamento ". pg_last_error($con). "\n";
			}

			$sql = "DELETE FROM atlas_nf
					USING tbl_faturamento
					WHERE atlas_nf.nota_fiscal    = tbl_faturamento.nota_fiscal
					AND   atlas_nf.serie          = tbl_faturamento.serie
					AND   tbl_faturamento.posto   = atlas_nf.posto
					AND   tbl_faturamento.fabrica = $fabrica
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao excluir  dados da tabela atlas_nf (faturamento) ". pg_last_error($con). "\n";
			}

			$sql = "DELETE FROM atlas_nf_item
				USING tbl_faturamento
				JOIN  tbl_faturamento_item USING(faturamento)
				WHERE atlas_nf_item.nota_fiscal         = tbl_faturamento.nota_fiscal
				AND   atlas_nf_item.serie               = tbl_faturamento.serie
				AND   tbl_faturamento_item.pedido_item = atlas_nf_item.pedido_item
				AND   tbl_faturamento_item.peca = atlas_nf_item.peca
				AND   tbl_faturamento.fabrica = $fabrica
				";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao excluir dados da tabela atlas_nf_item (faturamento) ". pg_last_error($con). "\n";
			}

			#------------ NFs sem Itens --------------#
			$sql = "DROP TABLE IF EXISTS atlas_nf_sem_itens";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao apagar a tabela atlas_nf_sem_itens ". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao apagar a tabela atlas_nf_sem_itens  . <Br>";
			}


			$sql = "SELECT atlas_nf.*
					INTO atlas_nf_sem_itens
					FROM atlas_nf
					LEFT JOIN atlas_nf_item ON atlas_nf.nota_fiscal = atlas_nf_item.nota_fiscal
					WHERE atlas_nf_item.nota_fiscal IS NULL
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao fazer busca na tabela atlas_nf (sem nota fiscal)  ". pg_last_error($con). "\n";
			}


			$sql = "DELETE FROM atlas_nf
					USING atlas_nf_sem_itens
					WHERE atlas_nf.nota_fiscal = atlas_nf_sem_itens.nota_fiscal
					AND   atlas_nf.serie       = atlas_nf_sem_itens.serie
					AND   atlas_nf.posto       = atlas_nf_sem_itens.posto
					";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro deletar dados da tabela atlas_nf (nota, serie, posto)  ". pg_last_error($con). "\n";
			}

			#----------------- Importa REALMENTE ------------
			$sql = "
					INSERT INTO tbl_faturamento
					(
						fabrica     ,
						emissao     ,
						saida       ,
						transp      ,
						posto       ,
						total_nota  ,
						cfop        ,
						nota_fiscal ,
						serie,
						conhecimento
					)
						SELECT  $fabrica,
								atlas_nf.txt_emissao::date         ,
								atlas_nf.txt_emissao::date         ,
								substring(atlas_nf.transp, 1,30),
								atlas_nf.posto           ,
								atlas_nf.total           ,
								atlas_nf.cfop        ,
								atlas_nf.nota_fiscal ,
								atlas_nf.serie,
								atlas_nf.conhecimento
						FROM atlas_nf
						LEFT JOIN tbl_faturamento ON  atlas_nf.nota_fiscal   = tbl_faturamento.nota_fiscal
												 AND  atlas_nf.serie         = tbl_faturamento.serie
												 AND  tbl_faturamento.fabrica      = $fabrica
												 AND  tbl_faturamento.distribuidor IS NULL
						WHERE tbl_faturamento.faturamento IS NULL
					";
			$res = pg_query($con, $sql);

			$importa_realmente = $sql;
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao inserir faturamento  ". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao inserir faturamento   . <Br>";
			}


			$sql = "ALTER TABLE atlas_nf_item ADD COLUMN faturamento INT4";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao altera coluna faturamento na tabela atlas_nf_item  ". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao altera coluna faturamento na tabela atlas_nf_item   . <Br>";
			}

			$sql = "UPDATE atlas_nf_item
					SET faturamento = tbl_faturamento.faturamento
					FROM tbl_faturamento
					WHERE tbl_faturamento.fabrica     = $fabrica
					AND   tbl_faturamento.nota_fiscal = atlas_nf_item.nota_fiscal
					AND   tbl_faturamento.serie       = atlas_nf_item.serie";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao atualizar campo faturamento na tabela atlas_nf_item ". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao atualizar campo faturamento na tabela atlas_nf_item. <Br>";
			}

			#------ Tratar itens sem nota ------

			$sql = "DELETE FROM atlas_nf_item
			WHERE faturamento IS NULL";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao excluir dados da tabela atlas_nf_item (sem faturamento)". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao excluir dados da tabela atlas_nf_item (sem faturamento). <Br>";
			}

			$sql = "DROP TABLE IF EXISTS atlas_pedido_faturado ";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "$sql". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "$sql. <Br>";
			}

			$sql = "SELECT tbl_faturamento_item.pedido, tbl_faturamento_item.pedido_item
					INTO atlas_pedido_faturado
					FROM tbl_faturamento_item
					JOIN atlas_nf_item ON tbl_faturamento_item.pedido = atlas_nf_item.pedido
					AND tbl_faturamento_item.pedido_item = atlas_nf_item.pedido_item";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "$sql". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "$sql. <Br>";
			}

			$sql = "UPDATE tbl_faturamento_item SET pedido = null
			FROM atlas_pedido_faturado
			WHERE tbl_faturamento_item.pedido = atlas_pedido_faturado.pedido
			AND tbl_faturamento_item.pedido_item = atlas_pedido_faturado.pedido_item";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "$sql". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "$sql. <Br>";
			}

			$sql = "UPDATE tbl_pedido_item SET qtde_faturada = 0, qtde_cancelada = 0
					FROM atlas_pedido_faturado
					WHERE tbl_pedido_item.pedido = atlas_pedido_faturado.pedido
					AND tbl_pedido_item.pedido_item = atlas_pedido_faturado.pedido_item";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "$sql". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "$sql. <Br>";
			}

			$sql = "UPDATE tbl_pedido SET status_pedido = 2
					FROM atlas_nf_item
					WHERE tbl_pedido.pedido = atlas_nf_item.pedido
					AND tbl_pedido.status_pedido = 14";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "$sql". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "$sql. <Br>";
			}

			$sql = "DELETE FROM tbl_pedido_cancelado
					USING atlas_pedido_faturado
					WHERE tbl_pedido_cancelado.pedido = atlas_pedido_faturado.pedido";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "$sql". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "$sql. <Br>";
			}

			$sql = "DROP TABLE atlas_nf_item_sem_peca ";
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao apagar tabela atlas_nf_item_sem_peca". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao apagar tabela atlas_nf_item_sem_peca. <Br>";
			}

			$sql = "SELECT * INTO atlas_nf_item_sem_peca
					FROM atlas_nf_item
						WHERE peca IS NULL" ;
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao buscar dados da tabela atlas_nf_item_sem_peca. ". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao buscar dados da tabela atlas_nf_item_sem_peca. <Br>";
			}

			$sql = "DELETE FROM atlas_nf_item WHERE peca IS NULL" ;
			$res = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao deletar dados da tabela atlas_nf_item (sem peça). ". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao deletar dados da tabela atlas_nf_item (sem peça). <Br>";
			}

			$sql = "SELECT  DISTINCT faturamento,
							pedido     ,
							pedido_item,
							peca       ,
							qtde as qtde_fat,
							unitario   ,
							aliq_ipi   ,
							aliq_icms  ,
							valor_ipi  ,
							valor_icms ,
							base_icms  ,
							referencia_atendida
					FROM atlas_nf_item
					WHERE pedido NOTNULL
					AND   pedido_item NOTNULL
					AND   peca NOTNULL";
			$resx = pg_query($con, $sql);
			if(strlen(trim(pg_last_error($con)))>0){
				$msg_erro_interno .= "Erro ao buscar dados da tabela atlas_nf_item (faturamento). ". pg_last_error($con). "\n";
				//$msg_erro_cliente .= "Erro ao buscar dados da tabela atlas_nf_item (faturamento). <Br>";
			}else{
				for($i=0; $i<pg_num_rows($resx); $i++){
					$pedido          = pg_fetch_result($resx, $i, 'pedido');
					$pedido_item     = pg_fetch_result($resx, $i, 'pedido_item');
					$faturamento     = pg_fetch_result($resx, $i, 'faturamento');
					$peca            = pg_fetch_result($resx, $i, 'peca');
					$qtde_fat        = pg_fetch_result($resx, $i, 'qtde_fat');
					$unitario        = pg_fetch_result($resx, $i, 'unitario');
					$aliq_ipi        = pg_fetch_result($resx, $i, 'aliq_ipi');
					$valor_ipi       = pg_fetch_result($resx, $i, 'valor_ipi');
					$valor_icms      = pg_fetch_result($resx, $i, 'valor_icms');
					$base_icms       = pg_fetch_result($resx, $i, 'base_icms');
					$aliq_icms       = pg_fetch_result($resx, $i, 'aliq_icms');
					$referencia_atendida  = pg_fetch_result($resx, $i, 'referencia_atendida');

					$sql = "INSERT INTO tbl_faturamento_item
							(
								faturamento,
								pedido     ,
								pedido_item,
								peca       ,
								qtde       ,
								preco      ,
								aliq_ipi   ,
								valor_ipi  ,
								valor_icms ,
								aliq_icms  ,
								base_icms
							)
							VALUES(
								$faturamento,
								$pedido     ,
								$pedido_item,
								$peca       ,
								$qtde_fat   ,
								$unitario   ,
								$aliq_ipi   ,
								$valor_ipi  ,
								$valor_icms ,
								$aliq_icms  ,
								$base_icms
							)";
					$res = pg_query($con, $sql);

					if(strlen(trim(pg_last_error($con)))>0){
						$msg_erro_interno .= "Erro ao inserir faturamento item. ". pg_last_error($con). "\n";
					}

					$sql = "SELECT	pedido_item
							FROM tbl_pedido_item
							WHERE pedido_item = $pedido_item
								AND peca = $peca
								AND qtde > qtde_faturada";
					$res2 = pg_query($con, $sql);

					if(strlen(trim(pg_last_error($con)))>0){
						$msg_erro_interno .= "Erro ao fazer busca na tabela tbl_pedido_item. ". pg_last_error($con). "\n";
						//$msg_erro_cliente .= "Erro ao fazer busca na tabela tbl_pedido_item. <Br>";
					}
					if (pg_num_rows($res2) > 0) {
						for($a=0; $a<pg_num_rows($res2); $a++) {
							$pedido_item = pg_fetch_result($res2, $a, 'pedido_item');

							$sql = "SELECT fn_atualiza_pedido_item($peca,$pedido,$pedido_item,$qtde_fat);";
							$res = pg_query($con, $sql);
							if(strlen(trim(pg_last_error($con)))>0){
								$msg_erro_interno .= "Erro ao executar a função fn_atualiza_pedido_item. ". pg_last_error($con). "\n";
								//$msg_erro_cliente .= "Erro ao executar a função fn_atualiza_pedido_item. <Br>";
							}
						}
					} else {
						$msg_erro_interno .= " Não foi encontrado o item para atualizar: \n Pedido: $pedido \n Peça: $referencia_atendida \n Qtd.Fat: $qtde_fat \n ou peça já faturada.  \n\n";
					}

					$sql = "SELECT fn_atualiza_pedido_recebido_fabrica($pedido,$fabrica,current_date);";
					$res = pg_query($con, $sql);
					if(strlen(trim(pg_last_error($con)))>0){
						$msg_erro_interno .= "Erro ao executar função fn_atualiza_pedido_recebido_fabrica. ". pg_last_error($con). "\n";
						//$msg_erro_cliente .= "Erro ao executar função fn_atualiza_pedido_recebido_fabrica. <Br>";
					}

					#seta como faturado integral se quantidade faturada é igual qtde pedida
					$sql = "SELECT fn_atualiza_status_pedido($fabrica,$pedido);";
					$res = pg_query($con, $sql);
					if(strlen(trim(pg_last_error($con)))>0){
						$msg_erro_interno .= "Erro ao executar função fn_atualiza_status_pedido. ". pg_last_error($con). "\n";
						//$msg_erro_cliente .= "Erro ao executar função fn_atualiza_status_pedido. <Br>";
					}
				}

				if ($fabrica == 74) { #HD 384011

					$sql = "DROP TABLE IF EXISTS atlas_estoque_movimento ";
	                $res = pg_query($con, $sql);
	                if(strlen(trim(pg_last_error($con)))>0){
	                    $msg_erro_interno .= "$sql". pg_last_error($con). "\n";
	                    //$msg_erro_cliente .= "$sql. <Br>";
	                }

					$sql = "SELECT  $fabrica AS fabrica,
                                atlas_nf.posto::integer AS posto ,
                                CURRENT_DATE AS data,
                                SUM(atlas_nf_item.qtde) AS qtde_item,
                                atlas_nf_item.faturamento                       AS faturamento,
                                atlas_nf_item.peca                              AS peca,
                                atlas_nf_item.pedido::integer                   AS pedido,
                                atlas_nf.nota_fiscal                            AS nf
                        INTO TEMP atlas_estoque_movimento
                        FROM    atlas_nf_item
                        JOIN    atlas_nf ON (atlas_nf_item.nota_fiscal = atlas_nf.nota_fiscal
                                AND atlas_nf_item.serie   = atlas_nf.serie
                                AND atlas_nf_item.garantia_antecipada IS TRUE)
                        LEFT JOIN atlas_pedido_faturado ON atlas_nf_item.pedido = atlas_pedido_faturado.pedido
                                AND atlas_nf_item.pedido_item = atlas_pedido_faturado.pedido_item
                        JOIN    tbl_faturamento
                                ON  (atlas_nf.nota_fiscal = tbl_faturamento.nota_fiscal
                                AND atlas_nf.serie        = tbl_faturamento.serie
                                AND tbl_faturamento.fabrica           = $fabrica
                                AND tbl_faturamento.distribuidor IS NULL)
                        JOIN    tbl_peca ON atlas_nf_item.peca = tbl_peca.peca
                                AND tbl_peca.fabrica = $fabrica
                                AND tbl_peca.controla_saldo IS TRUE
                        WHERE   atlas_pedido_faturado.pedido IS NULL
                        GROUP BY
                                atlas_nf.posto::integer,
                                atlas_nf_item.faturamento,
                                atlas_nf_item.pedido::integer,
                                atlas_nf.nota_fiscal,
                                atlas_nf_item.peca;";

					$res = pg_query($con, $sql);
					if(strlen(trim(pg_last_error($con)))>0){
						$msg_erro_interno .= "Erro ao fazer busca na tabela atlas_nf_item (temp atlas_estoque_movimento)". pg_last_error($con). "\n";
						//$msg_erro_cliente .= "Erro ao fazer busca na tabela atlas_nf_item (temp atlas_estoque_movimento). <Br>";
					}

					$sql = "INSERT INTO tbl_estoque_posto_movimento (
									fabrica,
									posto,
									data,
									qtde_entrada,
									faturamento,
									peca,
									pedido,
									nf,
									tipo,
									obs)
							(SELECT tbl_posto_fabrica.fabrica,
									posto,
									data,
									qtde_item,
									faturamento,
									peca,
									pedido,
									nf,
									'estoque',
									'Abastecimento do estoque'
							FROM 	atlas_estoque_movimento
							JOIN 	tbl_posto_fabrica USING(posto,fabrica)
							WHERE 	tbl_posto_fabrica.controla_estoque IS TRUE)";
					$res = pg_query($con, $sql);
					if(strlen(trim(pg_last_error($con)))>0){
						$msg_erro_interno .= "Erro ao inserir movimento de estoque". pg_last_error($con). "\n";
						$msg_erro_cliente .= "Erro ao inserir movimento de estoque. <Br>";
					}

					#Verifica se tem posto com peça abaixo do estoque mínimo, se tiver faz o pedido
					$sql = "SELECT posto,
									peca,
									SUM(qtde_item) AS qtde
							FROM 	atlas_estoque_movimento
							JOIN 	tbl_posto_fabrica USING(posto, fabrica)
							WHERE 	tbl_posto_fabrica.controla_estoque IS TRUE
							GROUP BY
									posto,
									peca";
					$result2 = pg_query($con, $sql);
					if(strlen(trim(pg_last_error($con)))>0){
						$msg_erro_interno .= "Erro ao fazer busca na tabela atlas_estoque_movimento". pg_last_error($con). "\n";
						//$msg_erro_cliente .= "Erro ao fazer busca na tabela atlas_estoque_movimento. <Br>";
					}

					for($b=0; $b<pg_num_rows($result2); $b++){
						$posto 	= pg_fetch_result($result2, $b, 'posto');
						$peca 	= pg_fetch_result($result2, $b, 'peca');
						$qtde 	= pg_fetch_result($result2, $b, 'qtde');
						$tipo 	= pg_fetch_result($result2, $b, 'tipo');

						#Verifica se tem posto com peça abaixo do estoque mínimo, se tiver faz o pedido
						$sql = "SELECT posto,peca,qtde FROM tbl_estoque_posto
								WHERE tbl_estoque_posto.fabrica = $fabrica
								AND tbl_estoque_posto.posto = $posto
								AND tbl_estoque_posto.peca = $peca
								AND tbl_estoque_posto.tipo = 'estoque';";

						$resultE = pg_query($con, $sql);
						if(strlen(trim(pg_last_error($con)))>0){
							$msg_erro_interno .= "Erro ao fazer busca na tabela tbl_estoque_posto". pg_last_error($con). "\n";
							//$msg_erro_cliente .= "Erro ao fazer busca na tabela tbl_estoque_posto. <Br>";
						}

						if (pg_num_rows($resultE) > 0) {
							$sql = "UPDATE tbl_estoque_posto
									SET    qtde = tbl_estoque_posto.qtde + $qtde
									WHERE  tbl_estoque_posto.fabrica = $fabrica
									AND    tbl_estoque_posto.posto   = $posto
									AND    tbl_estoque_posto.peca    = $peca
									AND    tbl_estoque_posto.tipo    = 'estoque';
									";

							$res = pg_query($con, $sql);
							if(strlen(trim(pg_last_error($con)))>0){
								$msg_erro_interno .= "Erro ao atualizar campo qtde na tabela tbl_estoque_posto". pg_last_error($con). "\n";
								//$msg_erro_cliente .= "Erro ao atualizar campo qtde na tabela tbl_estoque_posto. <Br>";
							}
						} else {

							$sql = "INSERT INTO tbl_estoque_posto
									(
										fabrica,
										posto,
										peca,
										qtde,
										tipo
									) VALUES (
										$fabrica,
										$posto,
										$peca,
										$qtde,
										'estoque'
									)";
							$res = pg_query($con, $sql);
							if(strlen(trim(pg_last_error($con)))>0){
								$msg_erro_interno .= "Erro ao inserir estoque do posto". pg_last_error($con). "\n";
								//$msg_erro_cliente .= "Erro ao inserir estoque do posto. <Br>";
							}
						}
					}

					/* hd_chamado=2782600 inicio 
		                $sql = "SELECT tbl_os_produto.os AS id_os,
		                                tbl_os_item.qtde AS qtd_faturada,
		                                atlas_nf_item.posto AS id_posto,
		                                atlas_nf_item.peca AS id_peca,
		                                tbl_faturamento.emissao AS data_emissao,
		                                tbl_faturamento.faturamento AS id_faturamento,
		                                atlas_nf_item.pedido AS id_pedido,
		                                atlas_nf_item.nota_fiscal AS id_nota_fiscal
		                        FROM atlas_nf_item
		                        JOIN tbl_faturamento ON tbl_faturamento.faturamento = atlas_nf_item.faturamento AND tbl_faturamento.fabrica = 74
		                        JOIN tbl_os_item ON tbl_os_item.pedido_item = atlas_nf_item.pedido_item
		                        JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		                        ";
		                $res = pg_query($con, $sql);

		                if(pg_num_rows($res) > 0){
		                    $linha_for = pg_num_rows($res);

		                    for ($x=0; $x < $linha_for; $x++) {
		                        $id_os          = pg_fetch_result($res, $x, 'id_os');
		                        $qtd_faturada   = pg_fetch_result($res, $x, 'qtd_faturada');
		                        $id_posto       = pg_fetch_result($res, $x, 'id_posto');
		                        $id_peca        = pg_fetch_result($res, $x, 'id_peca');
		                        $data_emissao   = pg_fetch_result($res, $x, 'data_emissao');
		                        $id_faturamento = pg_fetch_result($res, $x, 'id_faturamento');
		                        $id_pedido      = pg_fetch_result($res, $x, 'id_pedido');
					$id_nota_fiscal = pg_fetch_result($res, $x, 'id_nota_fiscal');


					$sql_into = "INSERT INTO tbl_estoque_posto_movimento
						(
							fabrica,
							posto,
							data,
							qtde_entrada,
							faturamento,
							os,
							peca,
							pedido,
							nf,
							tipo,
							obs
						)
						VALUES(
							$fabrica,
							$id_posto,
							'$data_emissao',
							'$qtd_faturada',
							$id_faturamento,
							$id_os,
							$id_peca,
							$id_pedido,
							$id_nota_fiscal,
							'estoque',
							'Peça solicitada na Ordem de Serviço {$id_os}'
						)
						";
					$res_into = pg_query($con, $sql_into);

		                        $sql_into = "INSERT INTO tbl_estoque_posto_movimento
		                                        (
		                                            fabrica,
		                                            posto,
		                                            data,
		                                            qtde_saida,
		                                            faturamento,
		                                            os,
		                                            peca,
		                                            pedido,
		                                            nf,
		                                            tipo,
		                                            obs
		                                        )
		                                    VALUES(
		                                            $fabrica,
		                                            $id_posto,
		                                            '$data_emissao',
		                                            '$qtd_faturada',
		                                            $id_faturamento,
		                                            $id_os,
		                                            $id_peca,
		                                            $id_pedido,
		                                            $id_nota_fiscal,
		                                            'estoque',
		                                            'Peça utilizada na Ordem de Serviço {$id_os}'
		                                        )
		                                    ";
		                        $res_into = pg_query($con, $sql_into);

		                        #retirando peça do estoque
		                        $sql_up = "UPDATE tbl_estoque_posto
										SET    qtde = tbl_estoque_posto.qtde - $qtd_faturada
										WHERE  tbl_estoque_posto.fabrica = $fabrica
										AND    tbl_estoque_posto.posto   = $id_posto
										AND    tbl_estoque_posto.peca    = $id_peca
										AND    tbl_estoque_posto.tipo    = 'estoque';
										";

								$res_up = pg_query($con, $sql_up);
								if(strlen(trim(pg_last_error($con)))>0){
									$msg_erro_interno .= "Erro ao atualizar campo qtde na tabela tbl_estoque_posto". pg_last_error($con). "\n";
									//$msg_erro_cliente .= "Erro ao atualizar campo qtde na tabela tbl_estoque_posto. <Br>";
								}
		                    }
		                }
	                 hd_chamado=2782600 fim */



				}#HD 384011 - FIM
			}
	}else{
		$msg_erro_interno .= "Arquivo não encontrado.";
	}

	$phpCron->termino();

//Pegar os emails.
	$sql = "select email_cadastros from tbl_fabrica where fabrica = $fabrica ";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$para = pg_fetch_result($res, 0, 'email_cadastros');
	}

//Enviar emails
	if(strlen(trim($msg_erro_interno))>0) {
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";
	   	//$para = "paulos\@atlas.ind.br, cicero\@atlas.ind.br, alaelcio\@atlas.ind.br, helpdesk\@telecontrol.com.br";

	    $assunto   = "ATLAS - ERRO de Importação no Faturamento";
		$mensagem  = "Favor verificar URGENTE a falha deste processamento de atualização de faturamento!!! \n ".$msg_erro_interno;
		mail($para, $assunto, $mensagem, $headers);
	}

	if(strlen(trim($log))>0) {
		$headers = 'From: Telecontrol helpdesk\@telecontrol.com.br' . "\r\n" .
	    'Reply-To: webmaster@example.com' . "\r\n";
	   	//$para = "paulos\@atlas.ind.br, cicero\@atlas.ind.br, alaelcio\@atlas.ind.br, helpdesk\@telecontrol.com.br";

	    $assunto   = "ATLAS - LOG de Importação no Faturamento";
		$mensagem  = "Dados da importação de Faturamento. \n\n $log ";
		mail($para, $assunto, $mensagem, $headers);
	}

	$data = date('Y-m-d-h-s');

	if (file_exists("/home/atlas/atlas-telecontrol/faturamento.txt")) {
        system("cp /home/atlas/atlas-telecontrol/faturamento.txt /home/atlas/atlas-telecontrol/bkp/faturamento_$data.txt");
		system("mv /home/atlas/atlas-telecontrol/faturamento.txt  /tmp/atlas/faturamento_$data.txt");
	}

	if (file_exists("/home/atlas/atlas-telecontrol/faturamento_item.txt")) {
		system("cp /home/atlas/atlas-telecontrol/faturamento_item.txt /home/atlas/atlas-telecontrol/bkp/faturamento_item_$data.txt");
		system("mv /home/atlas/atlas-telecontrol/faturamento_item.txt  /tmp/atlas/faturamento_item_$data.txt");
	}

?>
