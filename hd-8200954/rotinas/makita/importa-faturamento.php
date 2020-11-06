<?php
	
	try{

		include dirname(__FILE__).'/../../dbconfig.php';
		include dirname(__FILE__).'/../../includes/dbconnect-inc.php';
		require_once dirname(__FILE__).'/../funcoes.php';

		$fabrica  = 42;
		$vet['fabrica'] = 'makita';
		$vet['tipo'] = 'importa-faturamento';
		$vet['log'] = 2;

		$data = date('Y-m-d-H');
		// $origem   = "/home/makita/makita-telecontrol";
		$origem   = "test-makita";
		$arquivos = "/tmp/makita/faturamento";

		$arquivo_erro = '/tmp/'.$vet['fabrica']."/".$vet['tipo']."-".$data.".erro";

		system ("rm -rf $origem/faturamento-cab.txt");
		system ("cat $origem/CABNF*.txt >> $origem/faturamento-cab.txt");

		system ("rm -rf $origem/faturamento-ite.txt");
		system ("cat $origem/ITNF*.txt >> $origem/faturamento-ite.txt");

		$arquivo = $origem."/faturamento-cab.txt";

		$arquivo_item = $origem."/faturamento-ite.txt";

		if(file_exists($arquivo) and filesize($arquivo) > 0){

			pg_query($con, "BEGIN");

			$sql = "CREATE TEMP TABLE tmp_makita_nf 
			(
				cnpj           text,
				nota           text,
				serie          text,
				emissao        text,
				cfop           text,
				total_nota     text,
				total_ipi      text,
				total_icms     text,
				transportadora text,
				desc_nat_ope   text,
				empresa        text,
				vencimento1	   text,
				parcela1	   text,
				vencimento2    text,
				parcela2	   text,
				vencimento3	   text,
				parcela3	   text
			)";

			$res = pg_query($con, $sql);

			if(pg_last_error()){

				Log::log2($vet, pg_last_error());

				throw new Exception(pg_last_error().$sql);

			}

			$conteudo = file_get_contents($arquivo);
			$conteudo_array = explode("\n", $conteudo);

			foreach ($conteudo_array as $linha){
				list($cnpj, $nota, $serie, $emissao, $cfop, $total_nota, $total_ipi, $total_icms, $transportadora, 
					$desc_nat_ope, $empresa, $vencimento1, $parcela1, $vencimento2, $parcela2, $vencimento3, $parcela3) = explode("\t", $linha);

				$cnpj           = trim($cnpj);
				$nota           = trim($nota);
				$serie          = trim($serie);
				$emissao        = trim($emissao);
				$cfop           = trim($cfop);
				$total_nota     = trim($total_nota);
				$total_ipi      = trim($total_ipi);
				$total_icms     = trim($total_icms);
				$transportadora = trim($transportadora);
				$desc_nat_ope   = trim($desc_nat_ope);
				$empresa        = trim($empresa);
				$vencimento1    = trim($vencimento1);
				$parcela1       = trim($parcela1);
				$vencimento2    = trim($vencimento2);
				$parcela2       = trim($parcela2);
				$vencimento3    = trim($vencimento3);
				$parcela3       = trim($parcela3);

				$sql = "INSERT INTO tmp_makita_nf 
						(cnpj, nota, serie, emissao, cfop, total_nota, total_ipi, total_icms, transportadora, desc_nat_ope, empresa, vencimento1, parcela1, vencimento2, parcela2, vencimento3, parcela3)
				        VALUES 
				        ('$cnpj', '$nota', '$serie', '$emissao', '$cfop', '$total_nota', '$total_ipi', '$total_icms', '$transportadora', '$desc_nat_ope', '$empresa', '$vencimento1', '$parcela1', '$vencimento2', '$parcela2', '$vencimento3', '$parcela3')";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

			}

			$sql = "ALTER TABLE tmp_makita_nf ADD COLUMN posto INT4";
			$res = pg_query($con, $sql);

			if(pg_last_error()){

				Log::log2($vet, pg_last_error());

				throw new Exception(pg_last_error().$sql);

			}

			$sql = "UPDATE tmp_makita_nf 
			SET posto = (
			SELECT tbl_posto.posto
				FROM  tbl_posto
				JOIN  tbl_posto_fabrica
				ON    tbl_posto.posto           = tbl_posto_fabrica.posto
				AND   tbl_posto_fabrica.fabrica = $fabrica
				WHERE tmp_makita_nf.cnpj        = tbl_posto.cnpj
			)";

			$res = pg_query($con, $sql);

			if(pg_last_error()){

				Log::log2($vet, pg_last_error());

				throw new Exception(pg_last_error().$sql);

			}

			$sql = "DELETE FROM tmp_makita_nf WHERE posto IS NULL";

			$res = pg_query($con, $sql);

			if(pg_last_error()){

				Log::log2($vet, pg_last_error());

				throw new Exception(pg_last_error().$sql);

			}

			if(file_exists($arquivo_item) and filesize($arquivo_item) > 0){

				$sql = "CREATE TEMP TABLE tmp_makita_nf_item 
				(
					cnpj           text,
					nota           text,
					serie          text,
					referencia     text,
					pedido 		   int4,
					qtde           text,
					unitario       text,
					aliq_ipi       text,
					aliq_icms      text,
					valor_ipi	   text,
					valor_icms     text,
					valor_sub_icms text,
					base_ipi       text,
					base_icms      text,
					base_sub_icms  text
				)";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$conteudo = file_get_contents($arquivo_item);
				$conteudo_array = explode("\n", $conteudo);

				foreach ($conteudo_array as $linha){
					list($cnpj, $nota, $serie, $referencia, $pedido, $qtde, $unitario, $aliq_ipi, $aliq_icms,
						$valor_ipi, $valor_icms, $valor_sub_icms, $base_ipi, $base_icms, $base_sub_icms) = explode("\t", $linha);

					$cnpj           = trim($cnpj);
					$nota           = trim($nota);
					$serie			= trim($serie);
					$referencia		= trim($referencia);
					$pedido			= str_replace("-", "", str_replace ("_", "", trim($pedido)));
					$qtde			= trim($qtde);
					$unitario		= trim($unitario);
					$aliq_ipi		= trim($aliq_ipi);
					$aliq_icms		= trim($aliq_icms);
					$valor_ipi		= trim($valor_ipi);
					$valor_icms		= trim($valor_icms);
					$valor_sub_icms	= trim($valor_sub_icms);
					$base_ipi		= trim($base_ipi);
					$base_icms		= trim($base_icms);
					$base_sub_icms	= trim($base_sub_icms);

					if(is_numeric($pedido)){

						$sql = "INSERT INTO tmp_makita_nf_item 
								(cnpj, nota, serie, referencia, pedido, qtde, unitario, aliq_ipi, aliq_icms, valor_ipi, valor_icms, valor_sub_icms, base_ipi, base_icms, base_sub_icms)
						        VALUES 
						        ('$cnpj', '$nota', '$serie', '$referencia', '$pedido', '$qtde', '$unitario', '$aliq_ipi', '$aliq_icms', '$valor_ipi', '$valor_icms', '$valor_sub_icms', '$base_ipi', '$base_icms', '$base_sub_icms')";

						$res = pg_query($con, $sql);

						if(pg_last_error()){

							Log::log2($vet, pg_last_error());

							throw new Exception(pg_last_error().$sql);

						}

					}


				}

				$sql = "DELETE FROM tmp_makita_nf_item WHERE pedido IS NULL";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "ALTER TABLE tmp_makita_nf_item ADD COLUMN posto INT4";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "ALTER TABLE tmp_makita_nf_item ADD COLUMN peca_atendida INT4";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "UPDATE tmp_makita_nf_item SET posto = tbl_posto.posto 
				FROM tbl_posto JOIN tbl_posto_fabrica 
				ON tbl_posto_fabrica.posto  = tbl_posto.posto 
				WHERE tmp_makita_nf_item.cnpj = tbl_posto.cnpj 
				AND tbl_posto_fabrica.fabrica = $fabrica";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "UPDATE tmp_makita_nf_item SET peca_atendida = tbl_peca.peca 
				FROM tbl_peca WHERE tmp_makita_nf_item.referencia = tbl_peca.referencia 
				AND tbl_peca.fabrica = $fabrica";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				#---------------- Grava produto acabado como se fosse peça ----------------------#

				$sql = "INSERT INTO tbl_peca (fabrica, referencia, descricao, origem, produto_acabado)
				SELECT DISTINCT 42, tbl_produto.referencia, tbl_produto.descricao, 'NAC',true
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				JOIN tmp_makita_nf_item 
				ON tbl_produto.referencia = tmp_makita_nf_item.referencia
				WHERE tbl_linha.fabrica = $fabrica
				AND   tmp_makita_nf_item.peca_atendida IS NULL
				";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "UPDATE tmp_makita_nf_item 
				SET peca_atendida = tbl_peca.peca
				FROM tbl_peca
				WHERE tmp_makita_nf_item.referencia = tbl_peca.referencia
				AND tbl_peca.fabrica = $fabrica
				";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "SELECT DISTINCT pedido,referencia
				FROM  tmp_makita_nf_item
				WHERE tmp_makita_nf_item.peca_atendida IS NULL
				";

				$resx = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				if(pg_num_rows($resx) > 0){

					$sql = "INSERT INTO tbl_peca
					(fabrica,
						referencia,
						descricao,
						origem)
					(SELECT DISTINCT $fabrica,
									referencia,
									'',
									'NAC'
					FROM  tmp_makita_nf_item
					WHERE tmp_makita_nf_item.peca_atendida IS NULL)
					";

					$res = pg_query($con, $sql);

					if(pg_last_error()){

						Log::log2($vet, pg_last_error());

						throw new Exception(pg_last_error().$sql);

					}
					 
					// Para quem vai ser enviado o email
					$para = "helpdesk@telecontrol.com.br";

					$assunto = "MAKITA - Faturamento do pedido com peças não cadastradas no sistema";
					 
					// cabeçalho do email
					$headers  = "MIME-Version: 1.0\n";
					$headers .= "Content-Type: multipart/mixed; ";
					 
					// email
					$mensagem  = "<font face='arial' color='#000000' size='2'>\n";
					$mensagem .= "Content-Type: text/html; charset='utf-8'\n";
					$mensagem .= "As peças abaixos foram cadastradas automaticamente pelo Telecontrol, porque não foram encontradas no sistema.\n\r\n";
					$mensagem .= "Por favor, alterar a descrição e outros dados de peças.\n</font> <br />";
					
					while($result = pg_fetch_object($resx)){

						$mensagem  .= "<b>Pedido</b> : ".$result->pedido." <br /> \n"; 
						$mensagem  .= "<b>Peça</b> : ".$result->referencia." <br /> \n"; 

					}

					// enviar o email
					mail($para, $assunto, $mensagem, $headers);

				}

				$sql = "UPDATE tmp_makita_nf_item 
				SET peca_atendida = tbl_peca.peca
				FROM tbl_peca
				WHERE tmp_makita_nf_item.referencia = tbl_peca.referencia
				AND tbl_peca.fabrica = $fabrica
				";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "UPDATE tmp_makita_nf_item 
						SET pedido = tbl_pedido.pedido
						FROM tbl_pedido 
						WHERE tmp_makita_nf_item.pedido = tbl_pedido.pedido
						AND tbl_pedido.fabrica = $fabrica
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "DELETE FROM tmp_makita_nf_item 
						WHERE (pedido IS NULL or peca_atendida is null)
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "DELETE FROM tmp_makita_nf_item
						WHERE  length(qtde)       = 0
							OR length(unitario)   = 0
							OR length(valor_icms) = 0
							OR length(base_icms)  = 0
							OR length(aliq_ipi)   = 0
							OR length(valor_ipi)  = 0
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "UPDATE tmp_makita_nf_item 
							SET qtde       = qtde          ,
							unitario       = unitario      ,
							aliq_ipi       = aliq_ipi       ,
							aliq_icms      = aliq_icms     ,
							valor_ipi      = valor_ipi     ,
							valor_icms     = valor_icms   ,
							valor_sub_icms = valor_sub_icms ,
							base_ipi       = base_ipi       ,
							base_icms      = base_icms    ,
							base_sub_icms  = base_sub_icms
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "DELETE FROM tmp_makita_nf 
						USING tbl_faturamento 
						WHERE tmp_makita_nf.nota      = tbl_faturamento.nota_fiscal 
						AND   tmp_makita_nf.serie     = tbl_faturamento.serie 
						AND   tmp_makita_nf.empresa   = tbl_faturamento.empresa 
						AND   tbl_faturamento.fabrica = $fabrica 
						AND   tbl_faturamento.distribuidor IS NULL
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "DELETE FROM tmp_makita_nf_item
						USING tbl_faturamento 
						JOIN tbl_faturamento_item 
						ON tbl_faturamento.faturamento  = tbl_faturamento_item.faturamento
						WHERE tmp_makita_nf_item.nota   = tbl_faturamento.nota_fiscal
						AND   tmp_makita_nf_item.serie  = tbl_faturamento.serie
						AND   tbl_faturamento.fabrica   = $fabrica
						AND   tbl_faturamento.distribuidor IS NULL
						AND   tbl_faturamento_item.peca =tmp_makita_nf_item.peca_atendida 
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				#------------ NFs sem Itens --------------#

				$sql = "CREATE TEMP TABLE tmp_makita_nf_sem_itens 
				(
					cnpj           text,
					nota           text,
					serie          text,
					emissao        text,
					cfop           text,
					total_nota     text,
					total_ipi      text,
					total_icms     text,
					transportadora text,
					desc_nat_ope   text,
					empresa        text,
					vencimento1	   text,
					parcela1	   text,
					vencimento2    text,
					parcela2	   text,
					vencimento3	   text,
					parcela3	   text,
					posto 		   int4
				)";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "SELECT tmp_makita_nf.* INTO tmp_makita_nf_sem_itens
						FROM tmp_makita_nf
						LEFT JOIN tmp_makita_nf_item 
						ON tmp_makita_nf.nota     = tmp_makita_nf_item.nota 
						AND tmp_makita_nf.serie   = tmp_makita_nf_item.serie
						WHERE tmp_makita_nf_item.nota IS NULL
					";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "DELETE FROM tmp_makita_nf 
				USING tmp_makita_nf_sem_itens
				WHERE tmp_makita_nf.nota    = tmp_makita_nf_sem_itens.nota
				AND   tmp_makita_nf.serie   = tmp_makita_nf_sem_itens.serie
				AND   tmp_makita_nf.empresa = tmp_makita_nf_sem_itens.empresa
				";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "INSERT INTO tbl_faturamento (fabrica, emissao, saida, transp, posto, total_nota, cfop, nota_fiscal, serie)
				(SELECT $fabrica, tmp_makita_nf.emissao, tmp_makita_nf.emissao, tmp_makita_nf.transportadora, tmp_makita_nf.posto, tmp_makita_nf.cfop, tmp_makita_nf.nota
				FROM tmp_makita_nf
				LEFT JOIN tbl_faturamento
				ON  tmp_makita_nf.nota      = tbl_faturamento.nota_fiscal
				AND tmp_makita_nf.serie     = tbl_faturamento.serie
				AND tmp_makita_nf.empresa   = tbl_faturamento.empresa
				AND tbl_faturamento.fabrica = $fabrica
				AND tbl_faturamento.distribuidor IS NULL
				WHERE tbl_faturamento.faturamento    IS NULL 
				)";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "ALTER TABLE tmp_makita_nf_item ADD COLUMN faturamento INT4";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "UPDATE tmp_makita_nf_item 
						SET faturamento                    = tbl_faturamento.faturamento
						FROM   tbl_faturamento
						WHERE tbl_faturamento.fabrica      = $fabrica
						AND   tbl_faturamento.nota_fiscal  = tmp_makita_nf_item.nota
						AND   tbl_faturamento.serie        = tmp_makita_nf_item.serie
						AND   tbl_faturamento.distribuidor IS NULL 
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "DELETE FROM tmp_makita_nf_item 
						WHERE faturamento IS NULL
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "INSERT INTO tbl_faturamento_parcela (faturamento, vencimento, parcela)
               			(
	                       	SELECT nfi.faturamento, nf.vencimento1, nf.parcela1
	                        FROM tmp_makita_nf nf
	                        JOIN tmp_makita_nf_item nfi ON nfi.nota = nf.nota AND nfi.serie = nf.serie
	                        WHERE nfi.faturamento IS NOT NULL
	                        AND nf.vencimento1 IS NOT NULL
	                        AND nf.parcela1 IS NOT NULL
	                        GROUP BY nfi.faturamento, nf.vencimento1, nf.parcela1
               			)
						";

				$sql = "SELECT nfi.faturamento, nf.vencimento1, nf.parcela1
	                    FROM tmp_makita_nf nf
	                    JOIN tmp_makita_nf_item nfi ON nfi.nota = nf.nota AND nfi.serie = nf.serie
	                    WHERE nfi.faturamento IS NOT NULL
	                    /*AND nf.vencimento1 IS NOT NULL
	                    AND nf.parcela1 IS NOT NULL*/
	                    GROUP BY nfi.faturamento, nf.vencimento1, nf.parcela1";

	            $sql = "SELECT nf.nota AS nf, nf.serie AS s
	            		FROM tmp_makita_nf nf LIMIT 3";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				while ($result = pg_fetch_object($res)) {
					//echo "{$result->faturamento} - {$result->vencimento1} - {$result->parcela1}\n";
					echo "{$result->nf} - {$result->s} \n";
				}


				echo "\nEND\n\n";

				pg_query($con, "ROLLBACK"); exit;

				$sql = "CREATE TEMP TABLE tmp_makita_nf_item_sem_peca 
				(
					cnpj           text,
					nota           text,
					serie          text,
					referencia     text,
					pedido 		   text,
					qtde           text,
					unitario       text,
					aliq_ipi       text,
					aliq_icms      text,
					valor_ipi	   text,
					valor_icms     text,
					valor_sub_icms text,
					base_ipi       text,
					base_icms      text,
					base_sub_icms  text,
					posto          int4,
					peca_atendida  int4,
					unitario	   float
				)";

				$sql = "SELECT * INTO tmp_makita_nf_item_sem_peca 
						FROM tmp_makita_nf_item 
						WHERE peca_atendida IS NULL
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				######### VERIFICAR NOMES ###########
				$sql = "INSERT INTO tbl_faturamento_item 
						(faturamento  ,
							peca      ,
							qtde      ,
							preco     ,
							valor_icms,
							base_icms ,
							aliq_ipi  ,
							aliq_icms  ,
							valor_ipi ,
							pedido)
						(SELECT DISTINCT
							faturamento, 
							peca_atendida,
							qtde         ,
							unitario     ,
							valor_icms   ,
							base_icms    ,
							aliq_ipi     ,
							aliq_icms    ,
							valor_ipi    ,
							pedido 
							FROM tmp_makita_nf_item)
						";
				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "SELECT fn_atualiza_pedido_recebido_fabrica 
						(tbl_pedido.pedido,tbl_pedido.fabrica,current_date)
						FROM tbl_pedido
						JOIN tmp_makita_nf_item 
						ON tbl_pedido.pedido = tmp_makita_nf_item.pedido
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "SELECT fn_atualiza_pedido_item (null,x.pedido,null,null)
					FROM (SELECT DISTINCT tbl_pedido.pedido 
							FROM tbl_pedido 
							JOIN tmp_makita_nf_item
							ON tbl_pedido.pedido = tmp_makita_nf_item.pedido) as x; ";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

				$sql = "SELECT fn_atualiza_status_pedido (tbl_pedido.fabrica,tbl_pedido.pedido)
						FROM tbl_pedido
						WHERE fabrica = $fabrica
						AND pedido IN 
							(SELECT distinct pedido 
								from tmp_makita_nf_item)
						";

				$res = pg_query($con, $sql);

				if(pg_last_error()){

					Log::log2($vet, pg_last_error());

					throw new Exception(pg_last_error().$sql);

				}

			}

			if(file_exists($arquivo_erro)){

				// Para quem vai ser enviado o email
				$para = "helpdesk@telecontrol.com.br";

				$assunto = "MAKITA - Erros no Faturamento";
				 
				// cabeçalho do email
				$headers  = "MIME-Version: 1.0\n";
				$headers .= "Content-Type: multipart/mixed; ";
				 
				// email
				$mensagem  = "<font face='arial' color='#000000' size='2'>\n";
				$mensagem .= "Content-Type: text/html; charset='utf-8'\n";
				$mensagem .= "Algumas notas fiscais não foram importadas corretamente.\n</font> <br />";
				$mensagem .= file_get_contents($arquivo_erro);

				// enviar o email
				mail($para, $assunto, $mensagem, $headers);

			}

			system("mv $origem/faturamento-cab.txt  $arquivos/faturamento_$data.txt");
			system("mv $origem/faturamento-ite.txt  $arquivos/faturamento_item_$data.txt");

			system ("rm -rf $origem/CABNF*.TXT");
			system ("rm -rf $origem/ITNF*.TXT");

		}

	}catch(Exception $e){

		echo $e->getMessage();

		pg_query($con, "ROLLBACK"); exit;
		
		if(file_exists($arquivo_erro)){

			// Para quem vai ser enviado o email
			$para = "helpdesk@telecontrol.com.br";

			$assunto = "MAKITA - Erros no Faturamento";
			 
			// cabeçalho do email
			$headers  = "MIME-Version: 1.0\n";
			$headers .= "Content-Type: multipart/mixed; ";
			 
			// email
			$mensagem  = "<font face='arial' color='#000000' size='2'>\n";
			$mensagem .= "Content-Type: text/html; charset='utf-8'\n";
			$mensagem .= "Algumas notas fiscais não foram importadas corretamente.\n</font> <br />";
			$mensagem .= file_get_contents($arquivo_erro);

			// enviar o email
			mail($para, $assunto, $mensagem, $headers);

		}

	}

?>