<?php

try
{
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$fabrica  = "42" ;
	$data     = date('Y-m-d-H');
	
	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	$vet['fabrica'] = 'makita';
	$vet['tipo']    = 'produto';
	$vet['dest'][0] = 'helpdesk@telecontrol.com.br';
	$vet['dest'][1] = 'renan@makita.com.br';
	$vet['dest'][2] = 'vjunior@makita.com.br';
	$vet['log']     = 2;

	$origens   = "/www/cgi-bin/makita/entrada/";
        $arquivo     = $origens."telecontrol-produto.txt";
	$bkp_arquivo = "/tmp/makita/telecontrol-produto-" . $data . ".txt.bkp";

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

		$conteudo = file_get_contents($arquivo);
		$conteudo_array = explode("\n", $conteudo);

		$i = 0;

		$arq_erro = "/tmp/" . $vet["fabrica"] . "/importa-produto-" . $data . ".erro";
		echo ` rm $arq_erro `;
		$arq_log = "/tmp/" . $vet["fabrica"] . "/importa-produto-" . $data . ".log";
		$fp = fopen($arq_log, "w");

		foreach ($conteudo_array as $linha)
		{
			$linha = trim($linha);

			if (!empty($linha))
			{
				$colunas = explode("\t", $linha);
				$count   = count($colunas);

				if ($count == 11)
				{
					list($referencia, $descricao, $origem, $descricao_familia, $cod_linha, $voltagem, $garantia, $mao_de_obra, $mao_de_obra_admin, $numero_serie_obrigatorio, $ncm) = explode("\t", $linha);

					$referencia        = trim($referencia);
					$descricao         = trim($descricao);
					$origem            = trim(substr($origem),0,3);
					$descricao_familia = trim($descricao_familia);
					$cod_linha         = trim($cod_linha);
					$voltagem          = trim($voltagem);
					$garantia          = trim($garantia);
					$mao_de_obra       = trim($mao_de_obra);
					$mao_de_obra_admin = trim($mao_de_obra_admin);
					$ncm               = trim($ncm);

					if (strlen($referencia) > 0 and strlen($descricao) > 0 and strlen($cod_linha) > 0 and strlen($descricao_familia) > 0 and strlen($mao_de_obra) > 0 and strlen($garantia) > 0 and strlen($mao_de_obra_admin) > 0)
					{
						$begin = pg_query($con, "BEGIN");

						$sql = "SELECT 
									linha 
								FROM 
									tbl_linha 
								WHERE 
									codigo_linha = '$cod_linha'
									AND 
										fabrica = $fabrica 
								LIMIT 1";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0)
						{
							$msg_erro = msgErro(pg_last_error());
							Log::log2($vet, $msg_erro);
							$i++;
							$rollback = pg_query($con, "ROLLBACK");
							continue;
						}
						else
						{
							$linha_id = pg_result($res, 0, "linha");

						}

						$sql = "SELECT 
									familia 
								FROM 
									tbl_familia 
								WHERE 
									descricao = '$descricao_familia' 
									AND 
										fabrica = $fabrica 
								LIMIT 1";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0)
						{
							$msg_erro = msgErro(pg_last_error());
							Log::log2($vet, $msg_erro);
							$i++;
							$rollback = pg_query($con, "ROLLBACK");
							continue;
						}
						else
						{
							if (pg_num_rows($res) == 0)
							{
								$sql = " INSERT INTO 
											tbl_familia
											(
												linha,
												fabrica,
												descricao,
												ativo
											)
											VALUES
											(
												$linha_id,
												$fabrica,
												'$descricao_familia',
												TRUE
											)";
								$res = pg_query($con, $sql);

								if (strlen(pg_last_error()) > 0) 
								{
									$msg_erro = msgErro(pg_last_error());
									Log::log2($vet, $msg_erro);
									$i++;
									$rollback = pg_query($con, "ROLLBACK");
									continue;
								}
								else
								{
									$sql = "SELECT currval ('seq_familia')";
									$res = pg_query($con, $sql);
									$familia = pg_result($res, 0, 0);
									fputs($fp, "Linha $i - Familia inserida $familia - $descricao_familia <br />");
								}
							}
							else
							{
								$familia = pg_result($res, 0, "familia");
							}
						}

						$sql = "SELECT 
									tbl_produto.produto 
								FROM 
									tbl_produto 
								JOIN 
									tbl_linha 
									ON
										tbl_linha.linha = tbl_produto.linha 
								WHERE 
									tbl_produto.referencia = '$referencia' 
									AND 
										tbl_linha.fabrica = $fabrica";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) 
						{
							$msg_erro = msgErro(pg_last_error());
							Log::log2($vet, $msg_erro);
							$i++;
							$rollback = pg_query($con, "ROLLBACK");
							continue;
						}
						else
						{
							if (pg_num_rows($res) == 0)
							{
								$sql = "INSERT INTO 
											tbl_produto 
											(
												linha,
												familia,
												referencia,
												descricao,
												origem,
												voltagem,
												garantia,
												mao_de_obra,
												mao_de_obra_admin,
												numero_serie_obrigatorio,
												ativo,
												classificacao_fiscal
											)
										VALUES
											(
												$linha_id,
												$familia,
												'$referencia',
												'$descricao',
												'$origem',
												'$voltagem',
												$garantia,
												$mao_de_obra,
												$mao_de_obra_admin,
												'$numero_serie_obrigatorio',
												'f',
												'$ncm'
											)";
								$res = pg_query($con, $sql);

								if (strlen(pg_last_error()) > 0) 
								{
									$msg_erro = msgErro(pg_last_error());
									Log::log2($vet, $msg_erro);
									$i++;
									$rollback = pg_query($con, "ROLLBACK");
									continue;
								}
								else
								{
									$sql = "INSERT INTO 
												tbl_peca
												(
													fabrica,
													referencia,
													descricao,
													origem,
													produto_acabado
												)
												VALUES
												(
													$fabrica,
													'$referencia',
													'$descricao',
													'$origem',
													't'
												)";
									$res = pg_query($con, $sql);

									if (strlen(pg_last_error()) > 0) 
									{
										$msg_erro = msgErro(pg_last_error());
										Log::log2($vet, $msg_erro);
										$i++;
										$rollback = pg_query($con, "ROLLBACK");
										continue;
									}
									else
									{
										$sql = "SELECT currval ('seq_produto')";
										$res = pg_query($con, $sql);

										$produto = pg_result($res, 0, "produto");
										fputs($fp, "Linha $i - Produto $produto - $descricao - $descricao_linha - $descricao_familia  - inserido com sucesso <br />");

										$commit = pg_query($con, "COMMIT");
									}
								}
							}
							else
							{
								$produto = pg_result($res, 0, "produto");
				
								$sql = "UPDATE 
											tbl_produto 
										SET
											descricao                = '$descricao',
											numero_serie_obrigatorio = '$numero_serie_obrigatorio',
											classificacao_fiscal     = '$ncm'
										WHERE 
											tbl_produto.produto = $produto";
								$res = pg_query($con, $sql);

								if (strlen(pg_last_error()) > 0) 
								{
									$produto = "";
									$msg_erro = msgErro(pg_last_error());
									Log::log2($vet, $msg_erro);
									$i++;
									$rollback = pg_query($con, "ROLLBACK");
									continue;
								}
								else
								{
									$sql = "SELECT 
												peca 
											FROM 
												tbl_peca 
											WHERE 
												fabrica =$fabrica 
												AND 
													referencia = '$referencia'";
									$res = pg_query($con, $sql);

									if (pg_num_rows($res) == 0) 
									{
										$sql = "INSERT INTO 
													tbl_peca
													(
														fabrica,
														referencia,
														descricao,
														origem,
														produto_acabado
													)
												VALUES
													(
														$fabrica,
														'$referencia',
														'$descricao',
														'$origem',
														't'
													)";
										$res = pg_query($con, $sql);

										if (strlen(pg_last_error()) > 0)
										{
											$produto = "";
											$msg_erro = msgErro(pg_last_error());
											Log::log2($vet, $msg_erro);
											$i++;
											$rollback = pg_query($con, "ROLLBACK");
											continue;
										}
									}
									else
									{
										$peca = pg_result($res, 0, "peca");

										$sql = "UPDATE 
													tbl_peca 
												SET
													referencia = '$referencia',
													descricao  = '$descricao'
												WHERE 
													peca = $peca";
										$res = pg_query($con, $sql);

										if (strlen(pg_last_error()) > 0) 
										{
											$produto = "";
											$msg_erro = msgErro(pg_last_error());
											Log::log2($vet, $msg_erro);
											$i++;
											$rollback = pg_query($con, "ROLLBACK");
											continue;
										}
										else
										{
											$commit = pg_query($con, "COMMIT");
										}
									}
								}
							}
						}
					}
					else
					{
						$msg_erro = msgErro("Linha $i está com o layout incorreto e não foi importada <br />");
						Log::log2($vet, $msg_erro);
					}
				}
				else
				{
					$msg_erro = msgErro("Linha $i está com o layout incorreto e não foi importada <br />");
					Log::log2($vet, $msg_erro);
				}
			}

			$i++;
		}

		fclose($fp);

		if (file_exists($arq_erro))
		{
			$hoje = date("d/m/Y - H:i:s");
			$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) Erro na importação de Produtos</h2><br />";
			$msg .= file_get_contents($arq_erro);
			$msg .= "</div>";
			Log::envia_email($vet, "MAKITA - Erro na importação de Produtos", $msg);
		}

		if (file_exists($arq_log))
		{
			$msg_log = file_get_contents($arq_log);

			if (!empty($msg_log))
			{
				$hoje = date("d/m/Y - H:i:s");
				$msg  = "<div style='background-color: #eeeecc; border: 1px solid #000;'><h2 style='color: #4441FF;'>($hoje) LOG da importação de Produtos</h2><br />";
				$msg .= $msg_log;
				$msg .= "</div>";
				Log::envia_email($vet, "MAKITA - LOG da importação de Produtos", $msg);
			}
		}
	}
	
	$phpCron->termino();
}
catch (Exception $e)
{
	echo $e->getMessage();
}

?>
