<?php

try
{
	include      dirname(__FILE__) . '/../../dbconfig.php';
	include      dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$fabrica = "3";
	$origem  = "/www/cgi-bin/britania/entrada";
	$data    = date('Y-m-d-H');
	$arquivo = $origem."/lista_basica.txt";
	$bkp_arquivo = "/tmp/britania/lista_basica-" . $data . ".txt.bkp";

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	$vet["fabrica"] = "britania";
	$vet["tipo"]    = "lista_basica";
	$vet["dest"][0] = "airton.garcia@britania.com.br";
	$vet["dest"][1] = "ricardo.cividanes@britania.com.br";
	$vet["dest"][2] = "helpdesk@telecontrol.com.br";
	$vet["log"]     = 2;

	function msgErro($msg = "")
	{
		$hoje = date("d/m/Y - H:i:s");

		if (!empty($msg))
		{
			$retorno .= "(" . $hoje . ")  " . $msg . "<br />";
			return $retorno;
		}
	}

	if (file_exists($arquivo))
	{
		copy($arquivo, $bkp_arquivo);

		$sql = "CREATE TEMP TABLE 
					tmp_britania_lb
					(
						ref_prod varchar(20), 
						ref_peca varchar(20),
						qtde double precision,
						pos varchar(50)
					)";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
//			$rollback = pg_query($con, "ROLLBACK");
		}

		$conteudo = file_get_contents($arquivo);
		$conteudo_array = explode("\n", $conteudo);
		$i = 0;
        
        	#-------------- Obtém todos os registros marcados como somente_kit = 't' -------#
        if($fabrica == 3){
            $todosSomenteKit = "SELECT produto,
                                   peca,
                                   fabrica
                            INTO TEMP tmp_lista_somente_kit
                            FROM tbl_lista_basica
                            WHERE fabrica = $fabrica AND
                                  somente_kit is true";
            $resSomenteKit = pg_query($con, $todosSomenteKit);
	    
            if (strlen(pg_last_error($con)) > 0) {
                $linha_erro = msgErro("Erro na verificação dos registros somente_kit !");
            }
        }


		foreach ($conteudo_array as $linha)
		{
			$linha = trim($linha);

			if (!empty($linha))
			{
				$colunas = explode(";", $linha);
				$count   = count($colunas);

				if ($count > 2 and $count < 5)
				{
					list($ref_prod, $ref_peca, $qtde, $pos) = explode(";", $linha);
					$ref_prod = trim($ref_prod);
					$ref_peca = trim($ref_peca);
					$qtde     = trim($qtde);
					$qtde     = str_replace(",",".",$qtde);
					$pos      = trim($pos);

					//echo $string   = $ref_prod . "\t" . $ref_peca . "\t" . $qtde . "\t" . $pos . "\n";

					if (strlen($ref_prod) > 0 and strlen($ref_peca) > 0 and strlen($qtde) > 0)
					{
						$sql = "INSERT INTO
									tmp_britania_lb
									(
										ref_prod,
										ref_peca,
										qtde,
										pos
									)
								VALUES
									(
										'$ref_prod',
										'$ref_peca',
										'$qtde',
										'$pos'
									)";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) 
						{
							$linha_erro = msgErro("Linha $i - Erro inesperado na importaçaõ !");
						}
					}
					else
					{
						$linha_erro = msgErro("Linha $i - Layout Incorreto, não importada !");
					}
				}
				else
				{
					$linha_erro = msgErro("Linha $i - Layout Incorreto, não importada !");
				}
			}

			$i++;
		}

//		$begin = pg_query($con,"BEGIN");

		$sql = "ALTER TABLE 
					tmp_britania_lb 
				ADD 
					COLUMN produto int4";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "ALTER TABLE 
					tmp_britania_lb 
				ADD 
					COLUMN peca int4";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "ALTER TABLE 
					tmp_britania_lb 
				ADD 
					COLUMN origem varchar(3)";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);
			
//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "UPDATE 
					tmp_britania_lb
				SET
					produto = tbl_produto.produto,
					origem = upper(tbl_produto.origem)
				FROM
					tbl_produto
				JOIN 
					tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE
					UPPER(TRIM(tmp_britania_lb.ref_prod)) = tbl_produto.referencia
					AND 
						tbl_linha.linha = tbl_produto.linha
					AND 
						tbl_linha.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "UPDATE 
					tmp_britania_lb 
				SET 
					peca = tbl_peca.peca
				FROM 
					tbl_peca
				WHERE 
					UPPER(TRIM(tmp_britania_lb.ref_peca)) = UPPER(TRIM(tbl_peca.referencia))
					AND 
						tbl_peca.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "DELETE FROM
					tmp_britania_lb
				WHERE
					((origem <> 'IMP' AND origem <> 'ASI') OR origem IS NULL)";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "DROP TABLE IF EXISTS britania_lista_basica_backup";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "DROP TABLE IF EXISTS britania_lista_basica_backup_bkp";
		$res = pg_query($con, $sql);

		$sql = "SELECT 
					* 
				INTO 
					britania_lista_basica_backup_bkp
				FROM
					tbl_lista_basica
				WHERE
					tbl_lista_basica.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

//			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "SELECT 
					tbl_lista_basica.lista_basica,
					tbl_lista_basica.produto,
					tbl_lista_basica.peca,
					tbl_lista_basica.qtde,
					tbl_lista_basica.fabrica,
					tbl_lista_basica.parte,
					tbl_lista_basica.garantia,
					tbl_lista_basica.serie_inicial,
					tbl_lista_basica.serie_final,
					tbl_lista_basica.nivel, 
					tbl_lista_basica.posicao,
					tbl_lista_basica.type,
					tbl_lista_basica.ordem,
					tbl_lista_basica.ativo, 
					tbl_lista_basica.peca_pai,
					tbl_lista_basica.admin,
					tbl_lista_basica.data_alteracao
				INTO 
					britania_lista_basica_backup
				FROM 
					tbl_lista_basica 
				JOIN 
					tbl_produto 
					ON 
						tbl_produto.produto = tbl_lista_basica.produto
				JOIN   
					tbl_linha 
					ON 
						tbl_linha.linha = tbl_produto.linha
				WHERE 
					tbl_lista_basica.fabrica = $fabrica
					AND
						tbl_linha.linha = tbl_produto.linha
					AND
						tbl_produto.referencia ILIKE '%AT%'
					AND 
						tbl_linha.fabrica = $fabrica 
					AND 
						(upper(tbl_produto.origem) = 'IMP' OR upper(tbl_produto.origem) = 'ASI')";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

//			$rollback = pg_query($con, "ROLLBACK");
		}

		$begin = pg_query($con,"BEGIN");

		$sql = "DELETE FROM
					tbl_lista_basica
				WHERE
					fabrica = $fabrica
					AND
						produto IN (
										SELECT 
											produto 
										FROM 
											tbl_produto 
										JOIN 
											tbl_linha 
											ON 
												tbl_linha.linha = tbl_produto.linha
										WHERE
											tbl_linha.linha = tbl_produto.linha
											AND
												tbl_produto.referencia ILIKE '%AT%'
											AND
												tbl_linha.fabrica = $fabrica
											AND
												(upper(tbl_produto.origem) = 'IMP' OR upper(tbl_produto.origem) = 'ASI')
								   )";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "SELECT
					*
				INTO TEMP
					tmp_britania_lb_falha
				FROM
					tmp_britania_lb
				WHERE
					(tmp_britania_lb.produto IS NULL OR tmp_britania_lb.peca IS NULL)";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "DELETE FROM 
					tmp_britania_lb
				WHERE
					(tmp_britania_lb.produto IS NULL OR tmp_britania_lb.peca IS NULL)";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "UPDATE
					tbl_lista_basica
				SET
					qtde = tmp_britania_lb.qtde::float
				FROM
					tmp_britania_lb
				WHERE
					tbl_lista_basica.produto = tmp_britania_lb.produto
					AND
						tbl_lista_basica.peca = tmp_britania_lb.peca
					AND
						tbl_lista_basica.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "UPDATE
					tbl_lista_basica
				SET
					posicao = tmp_britania_lb.pos
				FROM
					tmp_britania_lb
				WHERE
					tbl_lista_basica.produto = tmp_britania_lb.produto
					AND
						tbl_lista_basica.peca = tmp_britania_lb.peca
					AND
						LENGTH(TRIM(tmp_britania_lb.pos)) > 0
					AND
						tbl_lista_basica.fabrica = $fabrica";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "DELETE FROM
					tmp_britania_lb
				USING 
					tbl_lista_basica
				WHERE
					tbl_lista_basica.produto = tmp_britania_lb.produto
					AND
						tbl_lista_basica.peca = tmp_britania_lb.peca";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}

		$sql = "INSERT INTO
					tbl_lista_basica
					(
						fabrica,
						produto,
						peca,
						qtde,
						posicao
					)
				SELECT DISTINCT
					$fabrica,
					tmp_britania_lb.produto,
					tmp_britania_lb.peca,
					tmp_britania_lb.qtde,
					tmp_britania_lb.pos
				FROM
					tmp_britania_lb
				ORDER BY
					tmp_britania_lb.produto, tmp_britania_lb.peca";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}
        #---------- Seta os registros em somente_kit de acordo com a tbl temporária ----------#
        if($fabrica == 3){
            $updateSomenteKit = "UPDATE tbl_lista_basica set somente_kit = 't' 
                             FROM tmp_lista_somente_kit 
                             WHERE tbl_lista_basica.produto = tmp_lista_somente_kit.produto AND
                             tbl_lista_basica.peca = tmp_lista_somente_kit.peca AND
                             tbl_lista_basica.fabrica = tmp_lista_somente_kit.fabrica;";
            $resUpdateSomenteKit = pg_query($updateSomenteKit);
	
            if (strlen(pg_last_error($conn)) > 0) {
                $linha_erro = msgErro("Erro no UPDATE dos registros somente_kit !");
                $msg_erro = msgErro(pg_last_error());
                Log::log2($vet, $msg_erro);
                throw new Exception($msg_erro);

                $rollback = pg_query($con, "ROLLBACK");
            }
        }
		$sql = "SELECT  
					ref_prod, 
					ref_peca,
					qtde,
					pos
				FROM 
					tmp_britania_lb_falha";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) 
		{
			$msg_erro = msgErro(pg_last_error());
			Log::log2($vet, $msg_erro);
			throw new Exception($msg_erro);

			$rollback = pg_query($con, "ROLLBACK");
		}

		$rows = pg_num_rows($res);

		if ($rows > 0)
		{
			for ($i = 0; $i < $rows; $i++)
			{
				$ref_prod = pg_result($res,$i,"ref_prod");
				$ref_peca = pg_result($res,$i,"ref_peca");
				$qtde     = pg_result($res,$i,"qtde");
				$pos      = pg_result($res,$i,"pos");

				$erro .= $ref_prod . ";" . $ref_peca . ";" . $qtde . ";" . $pos . "<br />";
			}

			Log::envia_email($vet, "BRITÂNIA - Importação Lista Básica, peças não importadas", $erro);
		}

		if (strlen($linha_erro) > 0)
		{
			$hoje = date("d/m/Y - H:i:s");
			$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) Erro em algumas linhas na importação de Lista Básica (As demais foram importadas)</h2><br />";
			$msg .= $linha_erro;
			$msg .= "</div>";
			Log::envia_email($vet, "BRITÂNIA - Erro na importação de Lista Básica", $msg);
		}

		$commit = pg_query($con, "COMMIT");
	}
	
	$phpCron->termino();
}
catch (Exception $e)
{
	$arq_erro = '/tmp/' . $vet['fabrica'] . '/importa-lista_basica-' . $data . '.erro';

		if (file_exists($arq_erro))
		{
			$hoje = date("d/m/Y - H:i:s");
			$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) Erro na importação de Lista Básica</h2><br />";
			$msg .= file_get_contents($arq_erro);
			$msg .= "</div>";
			Log::envia_email($vet, "BRITÂNIA - Erro na importação de Lista Básica", $msg);
		}

	echo $e->getMessage();
}

?>
