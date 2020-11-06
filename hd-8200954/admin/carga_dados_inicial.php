<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";


$layout_menu = "cadastro";
$title = "Carga de dados";

include "cabecalho_new.php";

$plugins = array(
	"multiselect"
);

include("plugin_loader.php");

##### A T U A L I Z A R #####
if (strlen($_POST["btn_acao"]) > 0) {

    $tipo     	  = $_POST["tipo"];
    $camposNew    = $_POST["camposNew"];
    $arrayTipo    = ["int4","float8","bool"];
    $arraySim     = ["s","sim","t"];
    $arrayNao     = ["n","nao","f"];
    
    $pecasC = [];
    $pecasCTipo = [];

    foreach ($_POST["camposPeca_bd"] as $key => $value) {
    	unset($v);
    	$v = explode("|", $value);
    	$pecasC[] = $v[0];
    	$pecasCTipo[] = $v[1];
    }

    $produtosC = []; 
    $produtosCTipo = [];

    foreach ($_POST["camposProduto_bd"] as $key => $value) {
    	unset($v);
    	$v = explode("|", $value);
    	$produtosC[] = $v[0];
    	$produtosCTipo[] = $v[1];	
    }

    $postoC = [];
    $postoCTipo = [];

    foreach ($_POST["camposPosto_bd"] as $key => $value) {
    	unset($v);
    	$v = explode("|", $value);
    	$postoC[] = $v[0];
    	$postoCTipo[] = $v[1];		
    }

    $caminho  	  = "/tmp/";
    $msg_erro 	  = array();
    $msg      	  = '';
    $nome_arquivo = $tipo;

    if (empty($tipo)) {
    	$msg_erro['msg'][] = "Selecione o tipo do arquivo";
    }

    $arquivo = isset($_FILES["arquivo_zip"]) ? $_FILES["arquivo_zip"] : FALSE;

    if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none" AND strpos($arquivo["type"], 'text') === false) {
    	$msg_erro['msg'][] = "Arquivo no formato incorreto";
    }

    if (strlen($arquivo["tmp_name"]) > 0 AND $arquivo["tmp_name"] != "none" AND count($msg_erro)==0){

        $config["tamanho"] = 2048000;

        if ($arquivo["size"] > $config["tamanho"]){
            $msg_erro['msg'][] = "Arquivo em tamanho muito grande! Deve ser de no máximo 2MB.";
        }

        if (count($msg_erro) == 0) {
            system ("rm -f {$caminho}{$nome_arquivo}");

            $dat = date("yyyy-mm-dd");
            $nome_arquivo_aux = $nome_arquivo;
            $nome_arquivo = $caminho.$nome_arquivo.".txt";

            if (!copy($arquivo["tmp_name"], $nome_arquivo)) {
                $msg_erro['msg'][] = "Arquivo '".$arquivo['name']."' não foi enviado!!!";
			}else{
				$conteudo = file_get_contents($nome_arquivo);
				$conteudo = explode("\n", $conteudo);

				switch($tipo) {
				case 'fadr':
					foreach ($conteudo as $linha) {
						if (!empty($linha)) {
							list (
								$familia,
								$reclamado,
							) = explode ("\t",$linha);

							$original = array(
								$familia,
								$reclamado,
							);

							$familia = str_replace("'","\'", $familia);
							$sql = "select familia from tbl_familia where fabrica = $login_fabrica  and upper(regexp_replace(descricao,'\\W','','g')) = upper(regexp_replace(E'$familia','\\W','','g'))";
							$res = pg_query($con,$sql);
							$id_familia = pg_fetch_result($res,0 , 0);
								if(empty($id_familia)) {
								$sql = "insert into tbl_familia(fabrica, descricao) values ($login_fabrica, trim('$familia')) returning familia ;";
								$res = pg_query($con, $sql);
								$id_familia = pg_fetch_result($res,0 , 0);

							}

							$sql = "select defeito_reclamado from tbl_defeito_reclamado where fabrica = $login_fabrica  and descricao = trim('$reclamado')";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0) {
								$id_reclamado = pg_fetch_result($res,0 , 0);
							}else{
								$sql = "insert into tbl_defeito_reclamado(fabrica, descricao) values ($login_fabrica, trim('$reclamado')) returning defeito_reclamado ;";
								$res = pg_query($con, $sql);
								$id_reclamado = pg_fetch_result($res,0 , 0);
							}
							if(empty($id_reclamado)) continue;

							$sql = "select * from tbl_diagnostico where fabrica = $login_fabrica and familia = $id_familia and defeito_reclamado = $id_reclamado ";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) == 0) {
							$sql = " insert into tbl_diagnostico(fabrica, familia, defeito_reclamado) values ($login_fabrica, $id_familia, $id_reclamado) "; 
							$res = pg_query($con,$sql);
							}
						
							if (pg_last_error()) {
								$msg_erro['msg'][] = "Erro ao Inserir Registros $linha - $reclamado";
							}
						}
					} 
				break;
				case 'fadc':
					foreach ($conteudo as $linha) {
						if (!empty($linha)) {
							list (
								$familia,
								$constatado,
							) = explode ("\t",$linha);

							$original = array(
								$familia,
								$constatado,
							);

							$familia = str_replace("'","\'", $familia);
							$sql = "select familia from tbl_familia where fabrica = $login_fabrica  and upper(regexp_replace(descricao,'\\W','','g')) = upper(regexp_replace(E'$familia','\\W','','g'))";
							$res = pg_query($con,$sql);
							$id_familia = pg_fetch_result($res,0 , 0);
							if(empty($id_familia)) continue;

							$sql = "select defeito_constatado from tbl_defeito_constatado where fabrica = $login_fabrica  and descricao = trim('$constatado')";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) > 0) {
								$id_constatado = pg_fetch_result($res,0 , 0);
							}else{
								$sql = "insert into tbl_defeito_constatado(fabrica, descricao) values ($login_fabrica, trim('$constatado')) returning defeito_constatado ;";
								$res = pg_query($con, $sql);
								$id_constatado = pg_fetch_result($res,0 , 0);
							}
							if(empty($id_constatado)) continue;

							$sql = "select * from tbl_diagnostico where fabrica = $login_fabrica and familia = $id_familia and defeito_constatado = $id_constatado ";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) == 0) {
								$sql = " insert into tbl_diagnostico(fabrica, familia, defeito_constatado) values ($login_fabrica, $id_familia, $id_constatado) "; 
								$res = pg_query($con,$sql);
							}

							if (pg_last_error()) {
								$msg_erro['msg'][] = "Erro ao Inserir Registros $familia - $constatado";
							}
						}
					}
				break;
				case 'familia': 
					foreach ($conteudo as $linha) {
						if (!empty($linha)) {
							list (
								$codigo,
								$familia
							) = explode ("\t",$linha);


							$familia = str_replace("'","\'", $familia);
							$sql = "select familia from tbl_familia where fabrica = $login_fabrica  and upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais(E'$familia'))";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) == 0) {
								$familia = str_replace("'","\'", $familia);
								$sql = " insert into tbl_familia(fabrica, descricao, codigo_familia) values ($login_fabrica, E'$familia', '$codigo') "; 
								$res = pg_query($con,$sql);
							}

							if (pg_last_error()) {
								$msg_erro['msg'][] = "Erro ao Inserir Registros $codigo - $familia";
							}
						}
					}

				break;
				case 'posto_linha': 
					foreach ($conteudo as $linha) {
						if (!empty($linha)) {
							list (
								$cnpj,
								$linha,
								$tabela
							) = explode ("\t",$linha);

							$cnpj = str_pad($cnpj, 14, "0", STR_PAD_LEFT);
							$cnpj = preg_replace("/[^0-9]/", "", $cnpj);
							$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");
							if (pg_last_error()) {
								$msg_erro['msg'][] = "CNPJ Invalido $cnpj - ".pg_last_error();
								continue;
							}

							$sql_posto = "SELECT tbl_posto.posto FROM tbl_posto JOIN tbl_posto_fabrica USING(posto) WHERE tbl_posto.cnpj = '$cnpj' AND fabrica = $login_fabrica";
							$query_posto = pg_query($con, $sql_posto);
							if (pg_num_rows($query_posto) == 0) {
								$msg_erro['msg'][] = "Posto não encontrado $cnpj ";
								continue;
							} else {
								$id_posto = pg_fetch_result($query_posto,0 , 0);
							}

							$sql = "select linha from tbl_linha where fabrica = $login_fabrica  and upper(fn_retira_especiais(nome)) = upper(fn_retira_especiais(E'$linha'))";
							$res = pg_query($con,$sql);
							$id_linha = pg_fetch_result($res,0 , 0);
							if(empty($id_linha)) {
								$msg_erro['msg'][] = "Linha não encontrado $cnpj ";
								 continue;
							}

							$sql_tbl = "SELECT tabela FROM tbl_tabela WHERE upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais(E'$tabela')) AND fabrica = $login_fabrica";
							$query_tbl = pg_query($con, $sql_tbl);
							if (pg_num_rows($query_tbl) == 0) {
								$msg_erro['msg'][] = "Tabela não encontrado $tabela ";
								continue;
							} else {
								$id_tabela = pg_fetch_result($query_tbl,0 , 0);
							}

							$sql = "select posto from tbl_posto_linha where posto = $id_posto AND linha = $id_linha AND tabela = $id_tabela ";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) == 0) {
								$sql = " insert into tbl_posto_linha(posto, linha, tabela) values ($id_posto, $id_linha, $id_tabela)"; 
								$res = pg_query($con,$sql);
							}

							if (pg_last_error()) {
								$msg_erro['msg'][] = "Erro ao Inserir Registros $cnpj";
							}
						}
					}
				break;
				case 'lbm': 
					foreach ($conteudo as $linha) {
						if (!empty($linha)) {
							list (
								$produto,
								$peca,
								$qtde
							) = explode ("\t",$linha);

							$qtde = str_replace(",",".",$qtde);
							$sql = "select produto from tbl_produto where fabrica_i = $login_fabrica  and referencia = '$produto'";
							$res = pg_query($con,$sql);
							$id_produto = pg_fetch_result($res,0 , 0);
							if(empty($id_produto)) continue;

							$sql = "select peca from tbl_peca where fabrica = $login_fabrica  and referencia = '$peca'";
							$res = pg_query($con,$sql);
							$id_peca = pg_fetch_result($res,0 , 0);
							if(empty($id_peca)) continue;

							$sql = "select * from tbl_lista_basica where produto = $id_produto and peca = $id_peca "; 
							$res = pg_query($con,$sql);
							if(pg_num_rows($res) == 0) {
								$sql = " insert into tbl_lista_basica(fabrica, produto, peca, qtde ) values ($login_fabrica, $id_produto, $id_peca, $qtde) "; 
								$res = pg_query($con,$sql);

							}

							if (pg_last_error()) {
								$msg_erro['msg'][] = "Erro ao Inserir Registros $produto";
							}
						}
					}

				break;
				case 'posto':

					if ($camposNew == "sim") {
						$posicaoCnpj = '';
						$pCod_posto = '';
						$pFone = '';
						$pEndereco = '';
						$pNumero = '';
						$pComplemento = '';
						$pBairro = '';
						$pCep = '';
						$pCidade = '';
						$pEstado = '';
						$pEmail = '';
						$pContato = '';

						foreach ($postoC as $y => $e) {
							if ($e == "cnpj") {
								$posicaoCnpj = $y;
							}

							if ($e == "codigo_posto") {
								$pCod_posto = $y;
								unset($postoC[$y]);
							}

							if ($e == "fone") {
								$pFone = $y;
							}

							if ($e == "endereco") {
								$pEndereco = $y;
							}

							if ($e == "numero") {
								$pNumero = $y;
							}

							if ($e == "complemento") {
								$pComplemento = $y;
							}

							if ($e == "bairro") {
								$pBairro = $y;
							}

							if ($e == "cep") {
								$pCep = $y;
							}

							if ($e == "cidade") {
								$pCidade = $y;
							}

							if ($e == "estado") {
								$pEstado = $y;
							}

							if ($e == "email") {
								$pEmail = $y;
							}

							if ($e == "contato") {
								$pContato = $y;
							}
						}


						$camposInsert = implode(",", $postoC);

						if (count($msg_erro) == 0) {

							foreach ($conteudo as $linha) {
								if (!empty($linha)) {

									$valores = [];
									$valoresInsert = "";
									$cnpj = "";
									$codigo_posto = '';
									$telefone = '';
									$endereco = '';
									$numero = '';
									$complemento = '';
									$bairro = '';
									$cep = '';
									$cidade = '';
									$estado = '';
									$email = '';
									$contato = '';
									
									$camposLinha = explode ("\t",$linha);

									foreach ($postoCTipo as $k => $tp) {
										if (in_array(trim($tp), $arrayTipo)) {
											if (in_array(trim(strtolower($camposLinha[$k])), $arraySim)) {
												$valores[] = 'true';
											} else if (in_array(trim(strtolower($camposLinha[$k])), $arrayNao)) {
												$valores[] = 'false';
											} else {
												$valores[] = trim($camposLinha[$k]);
											}
										} else {
											$valores[] = "'".trim($camposLinha[$k])."'";
										}
									}
									
									if (!empty($posicaoCnpj)) {
										$cnpj = trim($camposLinha[$posicaoCnpj]);
									}

									if (!empty($pCod_posto)) {
										$codigo_posto = trim($camposLinha[$pCod_posto]);
										unset($valores[$pCod_posto]);
									}

									if (!empty($pFone)) {
										$telefone = trim($camposLinha[$pFone]);
									}

									if (!empty($pEndereco)) {
										$endereco = trim($camposLinha[$pEndereco]);
									}

									if (!empty($pNumero)) {
										$numero = trim($camposLinha[$pNumero]);
									}

									if (!empty($pComplemento)) {
										$complemento = trim($camposLinha[$pComplemento]);
									}

									if (!empty($pBairro)) {
										$bairro = trim($camposLinha[$pBairro]);
									}

									if (!empty($pCep)) {
										$cep = trim($camposLinha[$pCep]);
									}

									if (!empty($pCidade)) {
										$cidade = trim($camposLinha[$pCidade]);
									}

									if (!empty($pEstado)) {
										$estado = trim($camposLinha[$pEstado]);
									}

									if (!empty($pEmail)) {
										$email = trim($camposLinha[$pEmail]);
									}

									if (!empty($pContato)) {
										$contato = trim($camposLinha[$pContato]);
									}

									$valoresInsert = implode(",", $valores);

									$cnpj = str_pad($cnpj, 14, "0", STR_PAD_LEFT);
									$cnpj = preg_replace("/[^0-9]/", "", $cnpj);  
									$cep  = str_pad($cep, 8, "0", STR_PAD_LEFT);
									$cep = preg_replace("/[^0-9]/", "", $cep);  
									$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");
									if (pg_last_error()) {
										$msg_erro['msg'][] = pg_last_error();
										continue;
									}

									$sql_posto = "SELECT tbl_posto.nome, tbl_posto.ie, tbl_posto.posto FROM tbl_posto WHERE tbl_posto.cnpj = '$cnpj'";
									$query_posto = pg_query($con, $sql_posto);

									if (pg_num_rows($query_posto) == 0) {
										$sql = "INSERT INTO tbl_posto ($camposInsert) VALUES ($valoresInsert) returning posto";
										$query = pg_query($con, $sql);

										if (pg_last_error()) {
											$msg_erro['msg'][] = pg_last_error();
											continue;
										}

										$posto = pg_fetch_result($query, 0, 'posto');
									}else{
										$posto = pg_fetch_result($query_posto, 0, 'posto');
									}
									$sql = "SELECT 
												tbl_posto_fabrica.posto
											FROM   tbl_posto_fabrica
											WHERE  tbl_posto_fabrica.posto   = $posto
											AND    tbl_posto_fabrica.fabrica = $login_fabrica";
									$query = pg_query($con, $sql);

									if (pg_last_error()) {
										continue;
									}

									if (pg_num_rows($query) == 0) {
										$sql = "INSERT INTO tbl_posto_fabrica (
																	posto,
																	fabrica,
																	senha,
																	tipo_posto,
																	login_provisorio,
																	codigo_posto,
																	credenciamento,
																	contato_fone_comercial,
																	contato_endereco ,
																	contato_numero,
																	contato_complemento,
																	contato_bairro,
																	contato_cep,
																	contato_cidade,
																	contato_estado,
																	contato_email,
																	contato_nome
																) VALUES (
																	$posto,
																	$login_fabrica,
																	'',
																	(select tipo_posto from tbl_tipo_posto where fabrica = $login_fabrica and descricao ~*'autoriza' limit 1),
																	null,
																	'$codigo_posto',
																	'CREDENCIADO',
																	'$telefone',
																	'$endereco',
																	'$numero',
																	'$complemento',
																	(E'$bairro'),
																	'$cep',
																	(E'$cidade'),
																	'$estado',
																	'$email',
																	(E'$contato')
																)";
									} else {
										$sql = "UPDATE tbl_posto_fabrica SET
															codigo_posto           = '$codigo_posto',
															contato_endereco       = '$endereco',
															contato_bairro         = (E'$bairro'),
															contato_cep            = '$cep',
															contato_cidade         = (E'$cidade'),
															contato_estado         = '$estado',
															contato_fone_comercial = '$telefone',
															contato_email          = '$email'
													WHERE tbl_posto_fabrica.posto = $posto
													AND tbl_posto_fabrica.fabrica = $login_fabrica";
									}

									$query = pg_query($con, $sql);
								
									if (pg_last_error()) {
										$msg_erro['msg'][] = "Erro ao Inserir Registros $codigo_posto";
									}
								}
							}
						}
					} else {
						foreach ($conteudo as $linha) {
							if (!empty($linha)) {
								list (
									$codigo_posto,
									$razao,
									$nome_fantasia,
									$cnpj,
									$ie,
									$endereco,
									$numero,
									$complemento,
									$bairro,
									$cep,
									$cidade,
									$estado,
									$email,
									$telefone,
									$contato,
									$capital_interior
								) = explode ("\t",$linha);
								$cnpj = preg_replace("/[^0-9]/", "", $cnpj);
								$cep  = preg_replace("/[^0-9]/", "", $cep);
								$cnpj = str_pad($cnpj, 14, "0", STR_PAD_LEFT);  
								$cep  = str_pad($cep, 8, "0", STR_PAD_LEFT);  
								$valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");
								if (pg_last_error()) {

										echo pg_last_error();
									continue;
								}

								$sql_posto = "SELECT tbl_posto.nome, tbl_posto.ie, tbl_posto.posto FROM tbl_posto WHERE tbl_posto.cnpj = '$cnpj'";
								$query_posto = pg_query($con, $sql_posto);

								if (pg_num_rows($query_posto) == 0) {
									$sql = "INSERT INTO tbl_posto (
										nome,
										nome_fantasia,
										cnpj,
										ie,
										endereco,
										numero,
										bairro,
										cep,
										cidade,
										estado,
										email,
										fone,
										contato,
										capital_interior
									) VALUES (
										(E'$razao'),
												(E'$nome_fantasia'),
												'$cnpj',
												'$ie',
												'$endereco',
												'$numero',
												'$bairro',
												'$cep',
												'$cidade',
												'$estado',
												'$email',
												'$telefone',
												'$contato',
												'$capital_interior'
											) returning posto";
									$query = pg_query($con, $sql);

									if (pg_last_error()) {
										echo pg_last_error();
										continue;
									}

									$posto = pg_fetch_result($query, 0, 'posto');
								}else{
									$posto = pg_fetch_result($query_posto, 0, 'posto');
								}
								$sql = "SELECT 
											tbl_posto_fabrica.posto
										FROM   tbl_posto_fabrica
										WHERE  tbl_posto_fabrica.posto   = $posto
										AND    tbl_posto_fabrica.fabrica = $login_fabrica";
								$query = pg_query($con, $sql);

								if (pg_last_error()) {
									continue;
								}

								if (pg_num_rows($query) == 0) {
									$sql = "INSERT INTO tbl_posto_fabrica (
																posto,
																fabrica,
																senha,
																tipo_posto,
																login_provisorio,
																codigo_posto,
																credenciamento,
																contato_fone_comercial,
																contato_endereco ,
																contato_numero,
																contato_complemento,
																contato_bairro,
																contato_cep,
																contato_cidade,
																contato_estado,
																contato_email,
																contato_nome
															) VALUES (
																$posto,
																$login_fabrica,
																'',
																(select tipo_posto from tbl_tipo_posto where fabrica = $login_fabrica and descricao ~*'autoriza' limit 1),
																null,
																'$codigo_posto',
																'CREDENCIADO',
																'$telefone',
																'$endereco',
																'$numero',
																'$complemento',
																(E'$bairro'),
																'$cep',
																(E'$cidade'),
																'$estado',
																'$email',
																(E'$contato')
															)";
								} else {
									$sql = "UPDATE tbl_posto_fabrica SET
														codigo_posto           = '$codigo_posto',
														contato_endereco       = '$endereco',
														contato_bairro         = (E'$bairro'),
														contato_cep            = '$cep',
														contato_cidade         = (E'$cidade'),
														contato_estado         = '$estado',
														contato_fone_comercial = '$telefone',
														contato_email          = '$email'
												WHERE tbl_posto_fabrica.posto = $posto
												AND tbl_posto_fabrica.fabrica = $login_fabrica";
								}

								$query = pg_query($con, $sql);
							
								if (pg_last_error()) {
									$msg_erro['msg'][] = "Erro ao Inserir Registros $codigo_posto";
								}
							}
						}
					}
				break;
				case 'peca':

					if ($camposNew == "sim") {
						$camposInsert = implode(",", $pecasC);

						$posicaoRef  = '';
						$posicaoDesc = '';

						foreach ($pecasC as $y => $e) {
							if ($e == "referencia") {
								$posicaoRef = $y;
							}

							if ($e == "descricao") {
								$posicaoDesc = $y;
							}
						}

						if (empty($posicaoRef) || empty($posicaoDesc)) {
							$msg_erro['msg'][] = "Campos obrigatórios não informados";
						}

						if (count($msg_erro) == 0) {

							foreach ($conteudo as $linha) {
								if (!empty($linha)) {

									$valores = [];
									$valoresInsert = "";
									$referencia = "";
									$descricao = "";
									
									$camposLinha = explode ("\t",$linha);

									foreach ($pecasCTipo as $k => $tp) {
										if (in_array(trim($tp), $arrayTipo)) {
											if (in_array(trim(strtolower($camposLinha[$k])), $arraySim)) {
												$valores[] = 'true';
											} else if (in_array(trim(strtolower($camposLinha[$k])), $arrayNao)) {
												$valores[] = 'false';
											} else {
												$valores[] = trim($camposLinha[$k]);
											}
										} else {
											$valores[] = "'".trim($camposLinha[$k])."'";
										}
									}

									$referencia = trim($camposLinha[$posicaoRef]);
									$descricao  = trim($camposLinha[$posicaoDesc]);

									$valoresInsert = implode(",", $valores);

									$sql = "select peca from tbl_peca where fabrica = $login_fabrica AND upper(fn_retira_especiais(referencia)) = upper(fn_retira_especiais('$referencia')) AND upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais('$descricao'))";
									$res = pg_query($con,$sql);
									$id_peca = pg_fetch_result($res,0 , 0);
									if(empty($id_peca)) {
										$sql = "insert into tbl_peca ($camposInsert) values ($valoresInsert) returning peca ;";
										$res = pg_query($con, $sql);
										$id_peca = pg_fetch_result($res,0 , 0);
									}

									if (pg_last_error()) {
										$msg_erro['msg'][] = "Erro ao Inserir Registros $referencia - $descricao";
									}
								}
							}
						}
					} else {
						foreach ($conteudo as $linha) {
							if (!empty($linha)) {
								list (
									$referencia,
									$descricao,
									$origem,
									$acessorio
								) = explode ("\t",$linha);

								$sql = "select peca from tbl_peca where fabrica = $login_fabrica AND upper(fn_retira_especiais(referencia)) = upper(fn_retira_especiais('$referencia')) AND upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais('$descricao'))";
								$res = pg_query($con,$sql);
								$id_peca = pg_fetch_result($res,0 , 0);
								if(empty($id_peca)) {
									$sql = "insert into tbl_peca (fabrica, referencia, descricao, origem, acessorio) values ($login_fabrica, trim('$referencia'), trim('$descricao'), trim('$origem'), $acessorio) returning peca ;";
									$res = pg_query($con, $sql);
									$id_peca = pg_fetch_result($res,0 , 0);
								}
								
								if (pg_last_error()) {
									$msg_erro['msg'][] = "Erro ao Inserir Registros $referencia - $descricao";
								}
							}
						}
					}
				break;
				case 'produto':

					if ($camposNew == "sim") {
						$camposInsert = implode(",", $produtosC);

						$posicaoRef  = '';
						$posicaoDesc = '';
						$posicaoLinha = '';
						$posicaoFamilia = '';
						$posicaoOrigem = '';

						foreach ($produtosC as $y => $e) {
							if ($e == "referencia") {
								$posicaoRef = $y;
							}

							if ($e == "descricao") {
								$posicaoDesc = $y;
							}

							if ($e == "linha") {
								$posicaoLinha = $y;
							}

							if ($e == "familia") {
								$posicaoFamilia = $y;
							}

							if ($e == "origem") {
								$posicaoOrigem = $y;
							}

						}

						if (empty($posicaoRef) || empty($posicaoDesc)) {
							$msg_erro['msg'][] = "Campos obrigatórios não informados";
						}

						if (count($msg_erro) == 0) {

							foreach ($conteudo as $linhas) {
								
								if (!empty($linhas)) {

									$valores = [];
									$valoresInsert = "";
									$referencia = "";
									$descricao = "";
									$linha = "";
									$familia = "";
									
									$camposLinha = explode ("\t",$linhas);

									foreach ($produtosCTipo as $k => $tp) {
										if (in_array(trim($tp), $arrayTipo)) {
											if (in_array(trim(strtolower($camposLinha[$k])), $arraySim)) {
												$valores[] = 'true';
											} else if (in_array(trim(strtolower($camposLinha[$k])), $arrayNao)) {
												$valores[] = 'false';
											} else {
												$valores[] = trim($camposLinha[$k]);
											}
										} else {
											$valores[] = "'".trim($camposLinha[$k])."'";
										}
									}

									$referencia = trim($camposLinha[$posicaoRef]);
									$descricao  = trim($camposLinha[$posicaoDesc]);
									$linha      = trim($camposLinha[$posicaoLinha]);

									/* Caso não achar linha na consulta por encoding
									$termo = 'Ferramentas';

									$pattern = '/' . $termo . '/';
									
									if (preg_match($pattern, $linha)) {
									  $ln = 1828;
									} else {
									  $ln = 1829;
									}
									*/

									$familia    = trim($camposLinha[$posicaoFamilia]);									

									$sql = "select produto, ativo from tbl_produto where fabrica_i = $login_fabrica AND upper(fn_retira_especiais(referencia)) = upper(fn_retira_especiais('$referencia')) AND upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais('$descricao'))";
									$res = pg_query($con,$sql);
									$id_produto    = pg_fetch_result($res,0 , 'produto');
									$ativo_produto = pg_fetch_result($res,0 , 'ativo');
									if(empty($id_produto)) {
										$sqlLinha = "SELECT linha FROM tbl_linha WHERE upper(fn_retira_especiais(nome)) = upper(fn_retira_especiais('$linha')) AND fabrica = $login_fabrica LIMIT 1";
										$resLinha = pg_query($con, $sqlLinha);
										if (pg_num_rows($resLinha) > 0) {
											$idLinha = pg_fetch_result($resLinha, 0, 'linha');

											$sqlFamilia = "SELECT familia FROM tbl_familia WHERE fabrica = $login_fabrica AND upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais('$familia')) LIMIT 1 ";
											$resFamilia = pg_query($con, $sqlFamilia);
											if (pg_num_rows($resFamilia) > 0) {
												$idFamilia = pg_fetch_result($resFamilia, 0, 'familia');
											} else {
												$sqlF = "INSERT INTO tbl_familia (fabrica, descricao) VALUES ($login_fabrica, '$familia') returning familia ";
												$resF = pg_query($con, $sqlF);
												$idFamilia = pg_fetch_result($resF, 0, 'familia');
											}

											if (!empty($idLinha)) {
												$valores[$posicaoLinha] = $idLinha;
											}

											if (!empty($idFamilia)) {
												$valores[$posicaoFamilia] = $idFamilia;	
											} else {
												$valores[$posicaoFamilia] = '';	
											}

											if (!empty($posicaoOrigem)) {
												$valores[$posicaoOrigem] = "'".substr($valores[$posicaoOrigem], 1, 3)."'";		
											}

											$valoresInsert = implode(",", $valores);

											$sqlProd = "INSERT INTO tbl_produto ($camposInsert) VALUES ($valoresInsert)";
											$resProd = pg_query($con, $sqlProd);
											if (pg_last_error()) {
												$msg_erro['msg'][] = " Erro ao Inserir o Produto $referencia. <br> ".pg_last_error()."<br>";
											}
										} else {
											$msg_erro['msg'][] = "Linha $linha não encontrada ! <br>";
										}
									} else if (!$ativo_produto) {
										$sqlP = "UPDATE tbl_produto SET ativo = true WHERE produto = $id_produto AND fabrica = $login_fabrica";
										$resP = pg_query($con, $sqlP);
									}
								
									if (pg_last_error()) {
										$msg_erro['msg'][] = "Erro ao Inserir Registros $referencia - $descricao";
									}
								}
							}
						}
					} else {
						foreach ($conteudo as $linha) {
							if (!empty($linha)) {
								list (
									$referencia,
									$descricao,
									$linha,
									$familia,
									$voltagem,
									$nomeComercial,
									$nsObri,
									$origem,
									$refFab,
									$garantia
								) = explode ("\t",$linha);

								$sql = "select produto, ativo from tbl_produto where fabrica_i = $login_fabrica AND upper(fn_retira_especiais(referencia)) = upper(fn_retira_especiais('$referencia')) AND upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais('$descricao'))";
								$res = pg_query($con,$sql);
								$id_produto    = pg_fetch_result($res,0 , 'produto');
								$ativo_produto = pg_fetch_result($res,0 , 'ativo');
								if(empty($id_produto)) {
									$sqlLinha = "SELECT linha FROM tbl_linha WHERE upper(fn_retira_especiais(nome)) = upper(fn_retira_especiais('$linha')) AND fabrica = $login_fabrica LIMIT 1";
									$resLinha = pg_query($con, $sqlLinha);
									if (pg_num_rows($resLinha) > 0) {
										$idLinha = pg_fetch_result($resLinha, 0, 'linha');

										$sqlFamilia = "SELECT familia FROM tbl_familia WHERE fabrica = $login_fabrica AND upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais('$familia')) LIMIT 1 ";
										$resFamilia = pg_query($con, $sqlFamilia);
										if (pg_num_rows($resFamilia) > 0) {
											$idFamilia = pg_fetch_result($resFamilia, 0, 'familia');
										} else {
											$sqlF = "INSERT INTO tbl_familia (fabrica, descricao) VALUES ($login_fabrica, '$familia') returning familia ";
											$resF = pg_query($con, $sqlF);
											$idFamilia = pg_fetch_result($resF, 0, 'familia');
										}

										$sqlProd = "INSERT INTO tbl_produto (fabrica_i, mao_de_obra, mao_de_obra_admin, referencia, descricao, linha, familia, voltagem, nome_comercial, numero_serie_obrigatorio, origem, referencia_fabrica,  referencia_pesquisa, garantia) VALUES ($login_fabrica, 0, 0, '$referencia', '$descricao', $idLinha, $idFamilia, '$voltagem', '$nome_comercial', $nsObri, '$origem', '$refFab', '$refFab', $garantia)";
										$resProd = pg_query($con, $sqlProd);
										if (pg_last_error()) {
											$msg_erro['msg'][] = " Erro ao Inserir o Produto $referencia. <br>";
										}
									} else {
										$msg_erro['msg'][] = "Linha $linha não encontrada ! <br>";
									}
								} else if (!$ativo_produto) {
									$sqlP = "UPDATE tbl_produto SET ativo = true WHERE produto = $id_produto AND fabrica = $login_fabrica";
									$resP = pg_query($con, $sqlP);
								}
							
								if (pg_last_error()) {
									$msg_erro['msg'][] = "Erro ao Inserir Registros $referencia - $descricao";
								}
							}
						}
					}
				break;
			}
        }
		}
    }else{
    	if (count($msg_erro)==0) {
    		$msg_erro['msg'][] = "Arquivo não selecionado";
    		$msg_erro['campo'] = "arquivo";
    	}
    }
}
?>
<script type="text/javascript">
	var msg_preco = '<br />Preço não pode ter separador de milhar!';
	var table = '<table class="table table-large" style="height: 152px;">';

	$(function() {

		$(".ocultaSelect").change(function (){
			let classSelect = '.camposPosto_bd';
			let nome_arquivo = 'posto.txt';

			if ($('input[name=tipo]:checked', '#form_upload').val() == "peca") {
				classSelect = '.camposPeca_bd';
				nome_arquivo = 'peca.txt';
			} else if ($('input[name=tipo]:checked', '#form_upload').val() == "produto") {
				classSelect = '.camposProduto_bd';
				nome_arquivo = 'produto.txt';
			}
			 
			montaModelo(classSelect, nome_arquivo);
		});

		$(".bd_sel").multiselect();

		$('.radio').on('change', function() {
			var nome_arquivo, layout, exemplo;
			switch($('input[name=tipo]:checked', '#form_upload').val()){
				case 'peca':
					nome_arquivo = 'peca.txt';
					layout       = 'Referencia, Descrição, Origem, Acessório';
					exemplo      = '2601115057   ETIQUETA  NAC	false '+msg_preco;
					$(".div_monta_campos").show();
					if ($('input[name=camposNew]:checked', '#form_upload').val() == "sim") {
						$(".ocultaSelect").hide();
						$(".div_campos_peca").show();
						layout       = 'Referencia, Descrição, Origem';
						exemplo      = '2601115057  ETIQUETA  NAC';
					} else {
						$(".div_campos_peca").hide();
					}
					break;
				case 'preco':
					$(".ocultaDiv").hide();
					nome_arquivo = 'peca.txt';
					layout       = 'Referencia, Preço, Sigla Tabela de Preço e IPI';
					exemplo      = '0601190025 6259.00 8'+msg_preco;
					break;
				case 'produto':
					nome_arquivo = 'produto.txt';
					layout       = 'Referencia, Descrição, Linha, Familia, Voltagem, Nome comercial, Numero Serie Obrigatorio, Origem, Referencia Fábrica, Garantia';
					exemplo      = 'REF000145   DESC PRODUTO  Branca  Lavadora 	127 V  		GSR 6-25 	true   	Imp 	3601D413D0 	12';
					$(".div_monta_campos").show();
					if ($('input[name=camposNew]:checked', '#form_upload').val() == "sim") {
						$(".ocultaSelect").hide();
						$(".div_campos_produto").show();
						layout       = 'Referencia, Descrição, Linha, Garantia, Mão de Obra';
						exemplo      = 'REF000145   DESC PRODUTO  Branca 	12 		25.32'+msg_preco;
					} else {
						$(".div_campos_produto").hide();
					}
					break;
				case 'produto-pais':
					$(".ocultaDiv").hide();
					nome_arquivo = 'produto-pais.txt';
					layout       = 'Referencia, País e Garantia';
					exemplo      = '0601121103  AR  12';
					break;
				case 'peca-al':
					$(".ocultaDiv").hide();
					nome_arquivo = 'peca-al.txt';
					layout       = 'Referencia, Descrição e Acessório';
					exemplo      = 'F000600113 PORTA CARBONES  false';
					break;
				case 'peca-preco-al':
					$(".ocultaDiv").hide();
					nome_arquivo = 'peca-preco-al.txt';
					layout       = 'Referencia, Preço e País';
					exemplo      = 'F000600076    43.5    GT'+msg_preco;
					break;
				case 'lbm':
					$(".ocultaDiv").hide();
					nome_arquivo = 'lbm.txt';
					layout       = 'Referencia Produto, Referencia Peça, e Quantidade';
					exemplo      = '0601247612 F000610026  1';
					break;
				case 'posto':
					nome_arquivo = 'posto.txt';
					layout       = 'Código do Posto, Razão Social, Fantasia, CNPJ, I.E, Endereço, Número, Complemento, Bairro, CEP, Cidade, Estado, E-Mail, Fone, Contato e Capital/Interior';
					exemplo      = '123098	Nome Razão 	Nome Fantasia 	11234567000111 	111222333	Rua		32 	Complemento 	Centro 	10000222 	Marilia		SP 	teste@teste.com 	1433333333 	Nome Contato 	INTERIOR';
					$(".div_monta_campos").show();
					if ($('input[name=camposNew]:checked', '#form_upload').val() == "sim") {
						$(".ocultaSelect").hide();
						$(".div_campos_posto").show();
						layout       = 'Razão Social';
						exemplo      = 'Nome Razão';
					} else {
						$(".div_campos_posto").hide();
					}
					break;
				case 'preco-produto':
					$(".ocultaDiv").hide();
					nome_arquivo = 'preco-produto.txt';
					layout       = 'Referencia, País, Garantia e Preço';
					exemplo      = '0601824290 BO  16  3,20';
					break;
				case 'categoria-mao-obra':
					$(".ocultaDiv").hide();
					nome_arquivo = 'categoria-mao-obra.txt';
					layout       = 'Categoria, País e Valor';
					exemplo      = 'NOME DA CATEGORIA BR  55.20<br> Separador de centavos DEVE ser ponto (.)';
					break;
				case 'fadr':
					$(".ocultaDiv").hide();
					nome_arquivo = 'familia_reclamado.txt';
					layout       = 'Família e Defeito Reclamado';
					exemplo      = 'Lavadora	Quebrada';
					break;
				case 'fadc':
					$(".ocultaDiv").hide();
					nome_arquivo = 'familia_constatado.txt';
					layout       = 'Família e Defeito Constatado';
					exemplo      = 'Lavadora	Motor queimado';
					break;
				case 'familia':
					$(".ocultaDiv").hide();
					nome_arquivo = 'familia.txt';
					layout       = 'Código e Família';
					exemplo      = '123	Lavadora';
					break;
				case 'posto_linha':
					$(".ocultaDiv").hide();
					nome_arquivo = 'posto_linha.txt';
					layout       = 'CNPJ 	Linha 	Tabela';
					exemplo      = '123456879512	Ferramentas	Garantia';
					break;
			}
			$('#layout').html(
				table+' <tbody>\
							<tr>\
								<td width="20%" height="47px;" style="border-bottom: solid 1px black;"><strong>Nome do arquivo:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+nome_arquivo+'</td>\
							</tr>\
							<tr>\
								<td height="47px;" style="border-bottom: solid 1px black;"><strong>Layout:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+layout+'</td>\
							</tr>\
							<tr>\
								<td height="47px;" style="border-bottom: solid 1px black;"><strong>Exemplo:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+exemplo+'</td>\
							</tr>\
						</tbody></table>');
		});
		$('.radio').trigger('change');
	});

	function montaModelo(classSelect, nome_arquivo_N) {
		let msg_preco_N = '<br />Preço não pode ter separador de milhar!';
		let table_N = '<table class="table table-large" style="height: 152px;">';
		let layout_N = '';
		let exemplo_N = '';

		$(classSelect+" option:selected").each(function() {
		   
		   layout_N += $(this).data("tipocampo")+"  ";
		   exemplo_N += $(this).data("tipocampo")+"  "; 
		   
		});

		$('#layout').html(
				table_N+' <tbody>\
							<tr>\
								<td width="20%" height="47px;" style="border-bottom: solid 1px black;"><strong>Nome do arquivo:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+nome_arquivo_N+'</td>\
							</tr>\
							<tr>\
								<td height="47px;" style="border-bottom: solid 1px black;"><strong>Layout:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+layout_N+'</td>\
							</tr>\
							<tr>\
								<td height="47px;" style="border-bottom: solid 1px black;"><strong>Exemplo:</strong></td>\
								<td style="border-bottom: solid 1px black;">'+exemplo_N.toUpperCase()+'</td>\
							</tr>\
						</tbody></table>'
						);
	}

</script>
<?php if (count($msg_erro) > 0) { ?>
<div class='alert alert-error'>
	<h4><?=implode('<br />', $msg_erro['msg']);?></h4>
</div>
<?php }elseif (strlen($msg) > 0) { ?>
<div class='alert alert-success'>
	<h4><?=$msg;?></h4>
</div>
<?php } ?>
<div class='alert alert-warning'>
	<h4 id='msg_layout'>O formato do arquivo deverá ser .txt e os dados separados por TAB</h4>
</div>
<div class="row"><b class="obrigatorio pull-right">  * Campos obrigatórios </b></div>
<form method='POST' action='<?=$PHP_SELF;?>' enctype='multipart/form-data' class='form-search form-inline tc_formulario' id='form_upload'>
	<input type='hidden' name='btn_acao' value=''>
	<div class="titulo_tabela">Carga de dados inicial</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='peca' <?=($tipo=='peca'|| empty($tipo)) ? 'checked' : '';?>> Importar Peças
		</div>
		<div class="span6">
			<input type='radio' class='radio' name='tipo' value='produto'<?=($tipo=='produto') ? 'checked' : '';?>> Importar produtos
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='fadr' <?=($tipo=='fadr') ? 'checked' : '';?>>Família X Defeito Reclamado(Caso não tem reclamado cadastrado, será feito)
		</div>
		<div class="span6">
			<input type='radio' class='radio' name='tipo' value='fadc' <?=($tipo=='fadc') ? 'checked' : '';?>>Família X Defeito Constatado(Caso não tem reclamado cadastrado, será feito)
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='lbm' <?=($tipo=='lbm') ? 'checked' : '';?>> Importar Lista Básica
		</div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='posto' <?=($tipo=='posto') ? 'checked' : '';?>> Importar Posto
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='familia' <?=($tipo=='familia') ? 'checked' : '';?>> Importar Familia
		</div>
		<div class="span5">
			<input type='radio' class='radio' name='tipo' value='posto_linha' <?=($tipo=='posto_linha') ? 'checked' : '';?>> Posto X Linha
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span5 div_monta_campos ocultaDiv" style="display: none;">
			<label>Montar Campos Manualmente: </label>
			<input type='radio' class='radio' name='camposNew' value='sim' <?=($camposNew == 'sim') ? 'checked' : '';?>> Sim
			<input type='radio' class='radio' name='camposNew' value='nao' <?=($camposNew == 'nao' || empty($camposNew)) ? 'checked' : '';?>> Não
		</div>
		<div class="span5 div_campos_peca ocultaDiv ocultaSelect" style="display: none;">
			<div class="control-group" id="camposPeca_bd">
				<label class='control-label'><?=traduz('Modelo Campos')?></label>
				<select name='camposPeca_bd[]' id='camposPeca_bd' class='span12 bd_sel camposPeca_bd' multiple="multiple">
					<?php
					$sql = " SELECT column_name AS campo, udt_name AS tipo   
   							 FROM information_schema.columns 
   							 WHERE table_name = 'tbl_peca'";

					$res = pg_query($con,$sql);

					if (pg_numrows($res) > 0) {
						foreach (pg_fetch_all($res) as $key => $value) {
							
							$campoValue = $value["campo"];
							$tipoCampo  = $value["tipo"];

							if ($campoValue == 'peca') {
								continue;
							}

							$selectedPeca = "";

							$obrigPeca = ["referencia", "descricao", "origem", "mao_de_obra_admin"];

							if (in_array($campoValue, $pecasC) || in_array($campoValue, $obrigPeca)) {
								$selectedPeca = "selected";
							}

						?>
							<option <?=$selectedPeca?> data-tipoCampo="<?=$campoValue?>" value="<?=$campoValue.'|'.$tipoCampo?>" ><?=str_replace("_", " ", ucfirst($campoValue))?></option>
						<?php
						}
					}
					?>
				</select>
			</div>
		</div>
		<div class="span5 div_campos_produto ocultaDiv ocultaSelect" style="display: none;">
			<div class="control-group" id="camposProduto_bd">
				<label class='control-label'><?=traduz('Modelo Campos')?></label>
				<select name='camposProduto_bd[]' id='camposProduto_bd' class='span12 bd_sel camposProduto_bd' multiple="multiple">
					<?php
					$sql = " SELECT column_name AS campo, udt_name AS tipo   
   							 FROM information_schema.columns 
   							 WHERE table_name = 'tbl_produto'";

					$res = pg_query($con,$sql);

					if (pg_numrows($res) > 0) {
						foreach (pg_fetch_all($res) as $key => $value) {
							
							$campoValue = $value["campo"];
							$tipoCampo  = $value["tipo"];

							if ($campoValue == 'produto') {
								continue;
							}

							$selectedProduto = "";

							$obrigProduto = ["linha","referencia", "descricao", "garantia", "mao_de_obra"];

							if (in_array($campoValue, $produtosC) || in_array($campoValue, $obrigProduto)) {
								$selectedProduto = "selected";
							}

						?>
							<option <?=$selectedProduto?> data-tipoCampo="<?=$campoValue?>" value="<?=$campoValue.'|'.$tipoCampo?>" ><?=str_replace("_", " ", ucfirst($campoValue))?></option>
						<?php
						}
					}

					?>
				</select>
			</div>
		</div>
		<div class="span5 div_campos_posto ocultaDiv ocultaSelect" style="display: none;">
			<div class="control-group" id="camposPosto_bd">
				<label class='control-label'><?=traduz('Modelo Campos')?></label>
				<select name='camposPosto_bd[]' id='camposPosto_bd' class='span12 bd_sel camposPosto_bd' multiple="multiple">
					<?php
					$sql = " SELECT column_name AS campo, udt_name AS tipo   
   							 FROM information_schema.columns 
   							 WHERE table_name = 'tbl_posto'";

					$res = pg_query($con,$sql);

					if (pg_numrows($res) > 0) {
						$dados = pg_fetch_all($res);

						$cod_posto = ["campo"=>"codigo_posto", "tipo"=>"varchar"];
						array_push($dados, $cod_posto);
						
						foreach ($dados as $key => $value) {
							
							$campoValue = $value["campo"];
							$tipoCampo  = $value["tipo"];

							if ($campoValue == 'posto') {
								continue;
							}

							$selectedPosto = "";

							$obrigPosto = ["nome"];

							if (in_array($campoValue, $postoC) || in_array($campoValue, $obrigPosto)) {
								$selectedPosto = "selected";
							}

						?>
							<option <?=$selectedPosto?> data-tipoCampo="<?=$campoValue?>" value="<?=$campoValue.'|'.$tipoCampo?>" ><?=str_replace("_", " ", ucfirst($campoValue))?></option>
						<?php
						}
					}
					?>
				</select>
			</div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span10">
			<div id='layout'></div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span4"></div>
		<div class="span2" style="text-align: center;">
			<div class="control-group <?=(in_array($msg_erro['campo'], array('arquivo')))? 'error' : '';?>">
                <div class="controls controls-row">
                    <div class="inptc8">
                        <h5 class="asteristico">*</h5>
                        <input type='file' name='arquivo_zip' size='30'>
                    </div>
                </div>
            </div>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span12" style="text-align: center;">
			<button class='btn' onclick="javascript: if (document.forms[0].btn_acao.value == '' ) { document.forms[0].btn_acao.value='gravar'; document.forms[0].submit(); } else { alert ('Aguarde submissão') }" ALT="Gravar Formulario" >Gravar</button>
		</div>
	</div>
</form>

<script>
	$(document).ready(function() {
		if (($('input[name=tipo]:checked', '#form_upload').val() == 'peca' || $('input[name=tipo]:checked', '#form_upload').val() == 'produto' || $('input[name=tipo]:checked', '#form_upload').val() == 'posto') && $('input[name=camposNew]:checked', '#form_upload').val() == "sim") {
			$(".ocultaSelect").trigger("change");
		}
	});
</script>

<?php include "rodape.php"; ?>
