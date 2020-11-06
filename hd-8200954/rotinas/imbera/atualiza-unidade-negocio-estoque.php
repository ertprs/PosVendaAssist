<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

try {
	    pg_query($con, "BEGIN");
	        
	        $fabrica = 158;
	    $conteudo = file_get_contents("saldo_devolucao_terceiro_imbera.csv");

	    echo "\n";

	    foreach (explode("\n", $conteudo) as $linha) {
		    $linha = str_replace("'", "", $linha);
		    $linha = str_replace("\r", "", $linha);

		    if (empty($linha)) {
			continue;
		    }
			            list(
					                $codigo_posto,
							            $nome,
								                $referencia,
										            $nf,
											                $tipo,
													            $saldo_devolucao,
														                $unidade_negocio
															) = explode(";", $linha);

				    if ($tipo == 'FORA GARANTIA') {
					    $nf = "0000".$nf;
				    }
				            
				            $sqlPosto = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND codigo_posto NOT IN('TESTEIMBERA') AND codigo_posto::integer = {$codigo_posto}";
				            $resPosto = pg_query($con, $sqlPosto);
					            
					            if (!pg_num_rows($resPosto)) {
							                throw new Exception("Posto nãencontrado\n$linha");
									        }
					            
					            $resPosto = pg_fetch_assoc($resPosto);
					            $posto = $resPosto["posto"];
						            
						            $sqlPeca = "SELECT peca FROM tbl_peca WHERE fabrica = {$fabrica} AND referencia = '{$referencia}'";
						            $resPeca = pg_query($con, $sqlPeca);
							            
							            if (!pg_num_rows($resPeca)) {
									                throw new Exception("Peçnãencontrada\n$linha");
											        }
							            
							            $resPeca = pg_fetch_assoc($resPeca);
							            $peca = $resPeca["peca"];
								            
								            $update = "
										                UPDATE tbl_estoque_posto_movimento SET
												                parametros_adicionais = '{\"unidadeNegocio\":\"{$unidade_negocio}\"}'
														            WHERE fabrica = {$fabrica}
															                AND posto = {$posto}
																	            AND peca = {$peca}
																		                AND nf = '{$nf}'
																				            AND qtde_entrada IS NOT NULL
																					            ";
								            $resUpdate = pg_query($con, $update);
								            
								    if (strlen(pg_last_error()) > 0 || pg_affected_rows($resUpdate) == 0) {
									    echo pg_last_error();
										                throw new Exception("Erro ao atualizar\n$linha");
												        }
									        }
		    
		    pg_query($con, "COMMIT");
} catch (Exception $e) {
	    pg_query($con, "ROLLBACK");
	        echo $e->getMessage()."\n";
}
