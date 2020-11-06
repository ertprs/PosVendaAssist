<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if($login_fabrica == 1){
	include("os_revenda_blackedecker.php");
	exit;
}

include "funcoes.php";

$sql = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);

$msg_erro = "";

$qtde_item = 20;
if (strlen($_POST['qtde_item']) > 0)   $qtde_item = $_POST['qtde_item'];
if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

//if ($ip == '201.0.9.216') echo "Itens: $qtde_item | $qtde_linhas<br><br>";

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

//echo 'Ponto1 :'.$os_revenda;

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica
				AND    tbl_os_revenda.posto      = $login_posto";
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

if ($btn_acao == "gravar") {
	if (strlen($_POST['sua_os']) > 0){
		$xsua_os = $_POST['sua_os'] ;
		if ($login_fabrica <> 11 and $login_fabrica <> 5) {
			$xsua_os = "000000" . trim ($xsua_os);
			$xsua_os = substr ($xsua_os, strlen ($xsua_os) - 7 , 7) ;
		}
		$xsua_os = "'". $xsua_os ."'";
	}else{
		$xsua_os = "null";
	}

	$xdata_abertura = fnc_formata_data_pg($_POST['data_abertura']);
	$xdata_nf       = fnc_formata_data_pg($_POST['data_nf']);

	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';
	}else{
		if ($login_posto == 14254 and $login_fabrica == 11) {
			$nota_fiscal = trim ($nota_fiscal);
			$nota_fiscal = str_replace (".","",$nota_fiscal);
			$nota_fiscal = str_replace (" ","",$nota_fiscal);
			$nota_fiscal = str_replace ("-","",$nota_fiscal);
			$nota_fiscal = "000000000000" . $nota_fiscal;
			$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-12,12);
			$xnota_fiscal = "'" . $nota_fiscal . "'" ;
		} else {
			$nota_fiscal = trim ($nota_fiscal);
			$nota_fiscal = str_replace (".","",$nota_fiscal);
			$nota_fiscal = str_replace (" ","",$nota_fiscal);
			$nota_fiscal = str_replace ("-","",$nota_fiscal);
			$nota_fiscal = "000000" . $nota_fiscal;
			$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-6,6);
			$xnota_fiscal = "'" . $nota_fiscal . "'" ;
		}
	}

	if (strlen($_POST['revenda_cnpj']) > 0) {
		$revenda_cnpj  = $_POST['revenda_cnpj'];
		$revenda_cnpj  = str_replace (".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace (" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
	}else{
		$xrevenda_cnpj = "null";
	}

	if (strlen($_POST['consumidor_cnpj']) > 0) {
		$consumidor_cnpj  = $_POST['consumidor_cnpj'];
		$consumidor_cnpj  = str_replace (".","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace ("-","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace ("/","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace (" ","",$consumidor_cnpj);
		$xconsumidor_cnpj = "'". $consumidor_cnpj ."'";
	}else{
		$xconsumidor_cnpj = "null";
	}

	if (strlen($_POST['taxa_visita']) > 0)
		$xtaxa_visita = "'". $_POST['taxa_visita'] ."'";
	else
		$xtaxa_visita = "null";

	if (strlen($_POST['regulagem_peso_padrao']) > 0)
		$xregulagem_peso_padrao = "'". $_POST['regulagem_peso_padrao'] ."'";
	else
		$xregulagem_peso_padrao = "null";

	if (strlen($_POST['certificado_conformidade']) > 0)
		$xcertificado_conformidade = "'". $_POST['certificado_conformidade'] ."'";
	else
		$xcertificado_conformidade = "null";

	$os_reincidente = "'f'";

	// Verificação se o nº de série é reincidente
	if ($login_fabrica == 6 and 1 == 2) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0)." 00:00:00";

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0)." 23:59:59";

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$produto_serie = $_POST["produto_serie_".$i];

			if (strlen($produto_serie) > 0) {
				$sql = "SELECT  tbl_os.os            ,
								tbl_os.sua_os        ,
								tbl_os.data_digitacao,
								tbl_os_extra.extrato
						FROM    tbl_os
						JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
						WHERE   tbl_os.serie   = '$produto_serie'
						AND     tbl_os.fabrica = $login_fabrica
						AND     tbl_os.posto   = $login_posto
						AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
						ORDER BY tbl_os.data_digitacao DESC
						LIMIT 1";
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					$xxxos      = trim(pg_result($res,0,os));
					$xxxsua_os  = trim(pg_result($res,0,sua_os));
					$xxxextrato = trim(pg_result($res,0,extrato));

					if (strlen($xxxextrato) == 0) {
						$msg_erro .= "Nº de Série $produto_serie digitado é reincidente.<br>
						Favor consultar a ordem de serviço $xxxsua_os e acrescentar itens.<br>
						Em caso de dúvida, entre em contato com a Fábrica.";
						$linha_erro = $i;
					}else{
						$os_reincidente = "'t'";
					}
				}
			}
		}
	}

	if ($xrevenda_cnpj <> "null") {
		$sql =	"SELECT *
				FROM    tbl_revenda
				WHERE   cnpj = $xrevenda_cnpj";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0){
			if($login_fabrica<>19)$msg_erro = "CNPJ da revenda não cadastrado";
			else $msg_erro = "CNPJ do atacado não cadastrado";
		}else{
			$revenda		= trim(pg_result($res,0,revenda));
			$nome			= trim(pg_result($res,0,nome));
			$endereco		= trim(pg_result($res,0,endereco));
			$numero			= trim(pg_result($res,0,numero));
			$complemento	= trim(pg_result($res,0,complemento));
			$bairro			= trim(pg_result($res,0,bairro));
			$cep			= trim(pg_result($res,0,cep));
			$cidade			= trim(pg_result($res,0,cidade));
			$fone			= trim(pg_result($res,0,fone));
			$cnpj			= trim(pg_result($res,0,cnpj));

			if (strlen($revenda) > 0)
				$xrevenda = "'". $revenda ."'";
			else
				$xrevenda = "null";

			if (strlen($nome) > 0)
				$xnome = "'". $nome ."'";
			else
				$xnome = "null";

			if (strlen($endereco) > 0)
				$xendereco = "'". $endereco ."'";
			else
				$xendereco = "null";

			if (strlen($numero) > 0)
				$xnumero = "'". $numero ."'";
			else
				$xnumero = "null";

			if (strlen($complemento) > 0)
				$xcomplemento = "'". $complemento ."'";
			else
				$xcomplemento = "null";

			if (strlen($bairro) > 0)
				$xbairro = "'". $bairro ."'";
			else
				$xbairro = "null";

			if (strlen($cidade) > 0)
				$xcidade = "'". $cidade ."'";
			else
				$xcidade = "null";

			if (strlen($cep) > 0)
				$xcep = "'". $cep ."'";
			else
				$xcep = "null";

			if (strlen($fone) > 0)
				$xfone = "'". $fone ."'";
			else
				$xfone = "null";
			if (strlen($cnpj) > 0)
				$xcnpj = "'". $cnpj ."'";
			else
				$xcnpj = "null";

			$sql = "SELECT cliente
					FROM   tbl_cliente
					WHERE  cpf = $xrevenda_cnpj";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) == 0){
				// insere dados
				$sql = "INSERT INTO tbl_cliente (
							nome       ,
							endereco   ,
							numero     ,
							complemento,
							bairro     ,
							cep        ,
							cidade     ,
							fone       ,
							cpf
						)VALUES(
							$xnome       ,
							$xendereco   ,
							$xnumero     ,
							$xcomplemento,
							$xbairro     ,
							$xcep        ,
							$xcidade     ,
							$xfone       ,
							$xcnpj
						)";
				// pega valor de cliente

				$res     = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (strlen($msg_erro) == 0 and strlen($cliente) == 0) {
					$res     = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro) == 0) $cliente = pg_result ($res,0,0);
				}

			}else{
				// pega valor de cliente
				$cliente = pg_result($res,0,cliente);
			}
		}
	}else{
		$validacao = 'SIM';
		
		if ($login_fabrica == 14) {
			if ($login_posto == 7214 or $login_posto == 13562) {
				$validacao = 'NAO';
				
				if (strlen($cliente) == 0) $cliente = "null";
				if (strlen($revenda) == 0) $revenda = "null";
			}
		}
		
		if ($validacao == 'SIM') {
			$msg_erro = "CNPJ não informado";
		}
	}
//PARA LORENZETTI
	if($login_fabrica == 19){
		if ($xconsumidor_cnpj <> "null") {
			$sql =	"SELECT *
					FROM    tbl_revenda
					WHERE   cnpj = $xconsumidor_cnpj";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0){
				$consumidor_revenda		= trim(pg_result($res,0,revenda));
				$consumidor_nome		= trim(pg_result($res,0,nome));
				$consumidor_cnpj		= trim(pg_result($res,0,cnpj));
			}
		}
	}

	if (strlen($consumidor) > 0)
		$cliente = "'". $consumidor_revenda ."'";
	else
		$xconsumidor = "null";

	if (strlen($nome) > 0)
		$xconsumidor_nome = "'". $consumidor_nome ."'";
	else
		$xconsumidor_nome = "null";

	if (strlen($cnpj) > 0)
		$xconsumidor_cnpj = "'". $consumidor_cnpj ."'";
	else
		$xconsumidor_cnpj = "null";
//--========================================--

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	}else{
		$xrevenda_fone = "null";
	}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	}else{
		$xrevenda_email = "null";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen (trim ($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen ($os_revenda) == 0) {
			#-------------- insere ------------
			$sql = "INSERT INTO tbl_os_revenda (
						fabrica          ,
						sua_os           ,
						data_abertura    ,
						data_nf          ,
						nota_fiscal      ,
						cliente          ,
						revenda          ,
						obs              ,
						digitacao        ,
						posto            ,
						tipo_atendimento ,
						contrato         ,
						consumidor_nome  ,
						consumidor_cnpj  
					) VALUES (
						$login_fabrica                    ,
						$xsua_os                          ,
						$xdata_abertura                   ,
						$xdata_nf                         ,
						$xnota_fiscal                     ,
						$cliente                          ,
						$revenda                          ,
						$xobs                             ,
						current_timestamp                 ,
						$login_posto                      ,
						$tipo_atendimento                 ,
						$xcontrato                        ,
						$xconsumidor_nome                 ,
						$xconsumidor_cnpj              
					)";
		}else{
			$sql = "UPDATE tbl_os_revenda SET
						fabrica          = $login_fabrica                   ,
						sua_os           = $xsua_os                         ,
						data_abertura    = $xdata_abertura                  ,
						data_nf          = $xdata_nf                        ,
						nota_fiscal      = $xnota_fiscal                    ,
						cliente          = $cliente                         ,
						revenda          = $revenda                         ,
						obs              = $xobs                            ,
						posto            = $login_posto                     ,
						tipo_atendimento = $tipo_atendimento                ,
						contrato         = $xcontrato                       ,
						consumidor_nome  = $xconsumidor_nome                ,
						consumidor_cnpj  = $xconsumidor_cnpj                
					WHERE os_revenda     = $os_revenda
					AND	 posto           = $login_posto
					AND	 fabrica         = $login_fabrica ";
		}
//if ($ip == '201.42.45.176') echo "SQL : $sql<BR><BR>";
//echo $sql;
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_result ($res,0,0);
			$msg_erro   = pg_errormessage($con);

			// se nao foi cadastrado número da OS Fabricante (Sua_OS)
			if ($xsua_os == 'null' AND strlen($msg_erro) == 0 and strlen($os_revenda) <> 0) {
				//WELLINGTON ALTERAR 04/01
				if ($login_fabrica <> 1 and $login_fabrica <> 11) {
					$sql = "UPDATE tbl_os_revenda SET
									sua_os = '$os_revenda'
							WHERE tbl_os_revenda.os_revenda  = $os_revenda
							AND   tbl_os_revenda.posto       = $login_posto
							AND   tbl_os_revenda.fabrica     = $login_fabrica ";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if (strlen ($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET tbl_cliente.contrato = $xcontrato
						WHERE  tbl_cliente.cliente  = $revenda";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen ($msg_erro) > 0) {
				break ;
			}
		}

		if (strlen($msg_erro) == 0) {
			//$qtde_item = $_POST['qtde_item'];
			$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
//if ($ip == '201.0.9.216') echo "Itens (2): $qtde_item | $qtde_linhas<br><br>";

			for ($i = 0 ; $i < $qtde_item ; $i++) {
//if ($ip == '201.0.9.216') echo "Itens (3 - $i): $qtde_item | $qtde_linhas<br><br>";

				$referencia               = trim($_POST["produto_referencia_".$i]);
				$serie                    = trim($_POST["produto_serie_".$i]);
				$capacidade               = $_POST["produto_capacidade_".$i];
				$type                     = $_POST["type_".$i];
				$embalagem_original       = $_POST["embalagem_original_".$i];
				$sinal_de_uso             = $_POST["sinal_de_uso_".$i];
				//takashi 27/06
				$aux_nota_fiscal          = trim($_POST["aux_nota_fiscal_".$i]);
				$aux_qtde                 = trim($_POST["aux_qtde_".$i]);

				if (strlen($embalagem_original) == 0) $embalagem_original = "f";
				if (strlen($sinal_de_uso) == 0)       $sinal_de_uso = "f";
				//echo "Qtde: $aux_qtde";
				if ($login_fabrica == 19) {
					if (($aux_qtde) == 0 ) {
						if(strlen($referencia)>0) $msg_erro = "Favor indicar quantidade de produtos";
					}
				}else{
					if (strlen($aux_qtde) == 0) $aux_qtde = "1";
				}

				if ($login_fabrica == 6 AND strlen($serie) > 0 AND strlen($referencia) == 0) {
					$serie_pesquisa = substr($serie,0,3);
					$sqlX = "SELECT tbl_produto.referencia
							FROM tbl_produto
							JOIN tbl_linha USING (linha)
							WHERE tbl_produto.radical_serie = $serie_pesquisa
							AND tbl_linha.fabrica = $login_fabrica;";
//if ($ip == '201.0.9.216') echo "$i : $sqlX<BR><BR>";
					$resX = pg_exec($con,$sqlX);
					if (pg_numrows($resX) == 1) {
						$referencia = trim(pg_result($resX,0,0));
//if ($ip == '201.0.9.216') echo "Ref. : $referencia<BR><BR>";
					}else{
						$msg_erro .= " Número de série é inválido. ";
					}
				}

				if (strlen($serie) == 0 OR $login_fabrica==19)	$serie = "null";
				else						$serie = "'". $serie ."'";

				if (strlen($type) == 0)		$type = "null";
				else						$type = "'". $type ."'";

				$xxxos = 'null';
//if ($ip == '201.0.9.216') echo "Erro XX ($i): $msg_erro<br><br>";

				if ($login_fabrica == 6 and strlen($referencia) > 0) {

					$os_reincidente = "'f'";

					$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
					$resX = pg_exec($con,$sqlX);
					$data_inicial = pg_result($resX,0,0)." 00:00:00";

					$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
					$resX = pg_exec($con,$sqlX);
					$data_final = pg_result($resX,0,0)." 23:59:59";

					if (strlen($serie) > 0) {
						$sql = "SELECT  tbl_os.os            ,
										tbl_os.sua_os        ,
										tbl_os.data_digitacao,
										tbl_os_extra.extrato
								FROM    tbl_os
								JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
								WHERE   tbl_os.serie   = $serie
								AND     tbl_os.fabrica = $login_fabrica
								AND     tbl_os.posto   = $login_posto
								AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
								ORDER BY tbl_os.data_digitacao DESC
								LIMIT 1";
						$resZ = pg_exec($con,$sql);
//if ($ip == '201.0.9.216') echo "SQL ($i): $sql<br><br>";

						if (pg_numrows($resZ) > 0) {
							$xxxos      = trim(pg_result($resZ,0,os));
							$xxxsua_os  = trim(pg_result($resZ,0,sua_os));
							$xxxextrato = trim(pg_result($resZ,0,extrato));

							if (strlen($xxxextrato) == 0) {
								$msg_erro_serie .= "Nº de Série $serie digitado é reincidente.<br>
								Favor consultar a ordem de serviço $xxxsua_os e acrescentar itens.<br>
								Em caso de dúvida, entre em contato com a Fábrica.<BR><BR>";
								$linha_erro = $i;
//if ($ip == '201.0.9.216') echo "Erro interno ($i): $msg_erro_serie<br><br>";
							}else{
								$os_reincidente = "'t'";
							}
						}
					}
				}
//if ($ip == '201.0.9.216') echo "Erro interno ($i): $msg_erro_serie<br><br>";

				if (strlen($msg_erro_serie) > 0) {
					$msg_erro = $msg_erro_serie;
					break ;
				}

//if ($ip == '201.0.9.216') echo "Erro ($i): $msg_erro<br><br>";

				if (strlen($msg_erro) == 0) {
//if ($ip == '201.0.9.216') echo "Referencia ($i): $referencia + erro: $msg_erro<br><br>";

					if (strlen ($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace ("-","",$referencia);
						$referencia = str_replace (".","",$referencia);
						$referencia = str_replace ("/","",$referencia);
						$referencia = str_replace (" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql = "SELECT  produto
								FROM    tbl_produto
								JOIN    tbl_linha USING (linha)
								WHERE   upper(referencia_pesquisa) = $referencia
								AND     tbl_linha.fabrica = $login_fabrica
								AND     tbl_produto.ativo IS TRUE";
						$res = pg_exec ($con,$sql);

						if (pg_numrows ($res) == 0) {
							$msg_erro = "Produto $referencia não cadastrado";
							$linha_erro = $i;
						}else{
							$produto   = pg_result ($res,0,produto);
						}
						if($login_fabrica==19){
							$sql = "SELECT  *
									FROM    tbl_tipo_atendimento_mao_obra
									WHERE   produto = $produto
									AND     mao_de_obra>0
									AND     tipo_atendimento = 6";

							$res = pg_exec ($con,$sql);
							if (pg_numrows ($res) == 0) {
								$msg_erro = "Produto $referencia com valor de mão de obra para troca não cadastrado";
								$linha_erro = $i;
							}
						}
						if (strlen($capacidade) == 0)	
							$xcapacidade = 'null';
						else
							$xcapacidade = "'".$capacidade."'";
						
						
						if(strlen($aux_nota_fiscal)==0) 
							$aux_nota_fiscal=$xnota_fiscal;
						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_revenda_item (
										os_revenda            ,
										produto               ,
										serie                 ,
										nota_fiscal           ,
										data_nf               ,
										capacidade            ,
										type                  ,
										embalagem_original    ,
										sinal_de_uso          ,
										os_reincidente        ,
										qtde                  ,
										reincidente_os
									) VALUES (
										$os_revenda           ,
										$produto              ,
										$serie                ,
										$aux_nota_fiscal      ,
										$xdata_nf             ,
										$xcapacidade          ,
										$type                 ,
										'$embalagem_original' ,
										'$sinal_de_uso'       ,
										$os_reincidente       ,
										$aux_qtde             ,
										$xxxos
									)";
//echo "Insert item $i : $sql<BR><BR>";

							$res = pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);
							if (strlen ($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}

			if (strlen($msg_erro) == 0){
				$sql = "SELECT fn_valida_os_revenda($os_revenda,$login_posto,$login_fabrica)";
//if ($ip == '201.0.9.216') echo "Funcao : $sql<BR><BR>";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	}else{
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		$os_revenda = '';
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if(strlen($msg_erro) == 0 AND strlen($os_revenda) > 0){
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_revenda.nome  AS revenda_nome                                    ,
					tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					tbl_revenda.fone  AS revenda_fone                                    ,
					tbl_revenda.email AS revenda_email                                   ,
					tbl_os_revenda.explodida                                             ,
					tbl_os_revenda.tipo_atendimento                                      ,
					tbl_os_revenda_item.os_revenda_item
			FROM	tbl_os_revenda
			LEFt JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
			LEFT JOIN tbl_revenda         ON tbl_os_revenda.revenda         = tbl_revenda.revenda
			JOIN	tbl_fabrica USING (fabrica)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	tbl_os_revenda.os_revenda = $os_revenda
			AND		tbl_os_revenda.posto      = $login_posto
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);
	
//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql)."<br>".pg_numrows($res); exit;

	if (pg_numrows($res) > 0){
		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_nf          = pg_result($res,0,data_nf);
		$nota_fiscal      = pg_result($res,0,nota_fiscal);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$contrato         = pg_result($res,0,contrato);
		$explodida        = pg_result($res,0,explodida);
		$os_revenda_item  = pg_result($res,0,os_revenda_item);
		$tipo_atendimento = pg_result($res,0,tipo_atendimento);

		if (strlen($explodida) > 0 and strlen($os_revenda_item) > 0){
			header("Location:os_revenda_parametros.php");
			exit;
		}

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'
				AND    posto   = $login_posto
				AND    fabrica = $login_fabrica";
		$resX = pg_exec($sql);

		if (pg_numrows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda.nota_fiscal,
						to_char(tbl_os_revenda.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.posto      = $login_posto
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_exec($con, $sql);

		if (pg_numrows($res) > 0){
			$nota_fiscal = pg_result($res,0,nota_fiscal);
			$data_nf     = pg_result($res,0,data_nf);
		}
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda";
$layout_menu	= 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$digita_os = pg_result ($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>Sem permissão de acesso.</H4>";
	exit;
}

include "javascript_pesquisas.php";
?>

<script language="JavaScript">
function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;
	janela.focus();
}

function fnc_pesquisa_revenda_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cnpj			= document.frm_os.consumidor_cnpj;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.email		= document.frm_os.consumidor_email;
	janela.focus();
}

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_produto (campo, campo2, campo3, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.voltagem		= campo3;
		janela.focus();
	}
}

function fnc_pesquisa_produto_serie (campo,campo2,campo3) {
	if (campo3.value != "") {
		var url = "";
		url = "produto_serie_pesquisa2.php?campo=" + campo3.value ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.serie	= campo3;
		janela.focus();
	}
}

</script>


<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
		</font></b>
	</td>
</tr>
</table>
<?
}
//echo $msg_debug;
?>
<?
if ($ip <> "201.0.9.216" and $ip <> "200.140.205.237" and 1==2) {
?>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> A PÁGINA FOI RETIRADA DO AR PARA QUE POSSAMOS MELHORAR A PERFORMANCE DE LANÇAMENTO.</font></td>
	</tr>
</table>

<? exit; ?>

<? } ?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr class="menu_top">
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> AS ORDENS DE SERVIÇO DIGITADAS NESTE MÓDULO SÓ SERÃO VÁLIDAS APÓS O CLIQUE EM GRAVAR E DEPOIS EM EXPLODIR.</font></td>
	</tr>
</table>

<?
//Wellington - O AVISO APARECERÁ NAS 5 PRIMEIRAS OSs LANÇADAS PELO POSTO APÓS A LIBERAÇÃO DO NOVO MÉTODO
$sql = "SELECT count(*)
		FROM tbl_os
		WHERE fabrica = $login_fabrica
		AND posto = $login_posto
		AND data_digitacao > '2007-01-04 08:49:23.107909-02'
		HAVING count(*) > 5
		LIMIT 1";
$res = pg_exec($con,$sql);
//$login_posto==6359 and 
if ($login_fabrica==11 and pg_numrows($res)==0) {
	echo "<center><table width=650><tr><td>";
	echo "<font face='Verdana' size='2' color='#0000FF'><left><b>A partir de 04/01/2007 o número da OS Fabricante não será digitado pelo posto autorizado, este número será gerado automaticamente pelo sistema quando o botão <i><u>Gravar</i></u> for clicado. A via de papel não deverá mais ser preenchida a mão, ela deve ser impressa pelo site e enviada ao fabricante.<br>Em caso de dúvidas entrar em contato com o fabricante.</b></left></font>";
	echo "</td></tr></table><center>";
}
?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr >
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">

			<!--------------- Formulário ------------------->
			<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
<?
if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);
?>
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>

			<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
				<? if ($login_fabrica == 19) { ?>
				<tr class="menu_top">
					<td nowrap colspan='3'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo de Atendimento</font>
					</td>
				</tr>
				<tr>
					<td nowrap align='center' colspan='3'>
						<font size="2" face="Geneva, Arial, Helvetica, san-serif">
							<INPUT TYPE='hidden' name="tipo_atendimento" value='6'>6-Troca
							
								
						<?
						/*echo '<select name="tipo_atendimento" size="1" class="frm">';
						if ($login_fabrica<>19){
						$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
//						$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 ORDER BY tipo_atendimento";
						$res = pg_exec ($con,$sql) ;
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
							echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
							echo pg_result ($res,$i,tipo_atendimento) . " - " . pg_result ($res,$i,descricao) ;
							echo "</option>\n";
							}
						}else{
							echo "<option  value='6'SELECTED DISABLED>6 - Troca</option>";
						}
						echo '<'select>';*/
						?>
							
						</font>
					</td>
				</tr>
				<? } ?>
				<tr class="menu_top">
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
					</td>
					<? } ?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
					</td>
		
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
					</td>
		
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Nota</font>
					</td>
				</tr>
				<tr>
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap align='center'>
						<input name="sua_os" class="frm" type="text" size="10" maxlength="10" value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
					</td>
					<? } ?>
					<td nowrap align='center'>
<!-- 						<input name="data_abertura" size="12" maxlength="10" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" type="text" class="frm" tabindex="0" <? if ($login_fabrica == 1) echo " readonly";?> > <font face='arial' size='1'> Ex.: <? echo date("d/m/Y"); ?></font> -->
						<input name="data_abertura" size="11" maxlength="10" value="
<?
				if (strlen($data_abertura) == 0 and $login_fabrica <> 1) $data_abertura = date("d/m/Y"); 
				echo $data_abertura; 
?>" type="text" ><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			
					</td>
		
					<td nowrap align='center'>
						<? if ($login_posto == 14254 and $login_fabrica == 11) 
								echo "<input name='nota_fiscal' size='12' maxlength='12' value='$nota_fiscal' type='text' class='frm' tabindex='0' >";
							else 
								echo "<input name='nota_fiscal' size='6' maxlength='6'value='$nota_fiscal' type='text' class='frm' tabindex='0' >";
						?>
					</td>
		
					<td nowrap align='center'>
						<input name="data_nf" size="11" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: 25/10/2004</font>
					</td>
				</tr>

			</table>
			<? if($login_fabrica == 19){ $revenda_aux = "Atacado";}else $revenda_aux = "Revenda";?>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome <?=$revenda_aux;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ <?=$revenda_aux;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone <?=$revenda_aux;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">e-Mail <?=$revenda_aux;?></font>
					</td>
				</tr>
			<!-- Foi modificado por Fernando. Foi colcoado o readonly nos campos Fone e e-mail 
				por ser apenas de leitura caso haja necessidade de alteração tem que ir em
				cadastro para alterar os dados da revenda. -->
				<tr>
					<td align='center'><input class="frm" type="text" name="revenda_nome" size="25"
maxlength="50" value="<? echo $revenda_nome ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0'
align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")'
style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_cnpj" size="14" maxlength="14" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input readonly class="frm" type="text" name="revenda_fone" size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td align='center'>
						<input readonly class="frm" type="text" name="revenda_email" size="11" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
				</tr>
			</table>

<input type="hidden" name="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" value="">
<input type="hidden" name="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" value="">
<?if($login_fabrica == 19 ){?>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
					</td>
					<td>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ <?if($login_fabrica == 19
){echo " COMPLETO ";} ?>Revenda</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
		
		<!--TAKASHI 24-10 DESABILITAMOS NOME DA REVENDA, POIS ESTAVA PEGANDO REVENDAS QUE NAO ERAM DA LORENZETTI,
AUTORIZADO POR NATANAEL E SAMUEL -->
		<input class="frm" type="text" name="consumidor_nome" size="28" maxlength="50" value="<? echo
		$consumidor_nome ?>" <?if($login_fabrica == 99 ){ echo "disabled";}?>>&nbsp;<?if($login_fabrica <> 99 ){
?><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript:
		fnc_pesquisa_revenda_consumidor (document.frm_os.consumidor_nome, "nome")' style='cursor:pointer;'> <? } ?>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="consumidor_cnpj" size="20" maxlength="14" value="<? echo $consumidor_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda_consumidor (document.frm_os.consumidor_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
				</tr>
			</table>


<?}?>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
<?
	if($login_fabrica == 7){
?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Contrato</font>
					</td>
<?
}
?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Qtde. Linhas</font>
					</td>
				</tr>
				<tr>
<?
	if($login_fabrica == 7){
?>
					<td align='center'>
						<input type="checkbox" name="contrato" value="t" <? if ($contrato == 't') echo " checked"?>>
					</td>
<?
}
if(strlen($qtde_linhas)==0){$qtde_linhas = '05'; $qtde_item='05';}

?>
					<td align='center'>
						<input class="frm" type="text" name="obs" size="68" value="<? echo $obs ?>">
					</td>
					<td align='center'>
						<select size='1' class="frm" name='qtde_linhas' onChange="javascript: document.frm_os.submit(); ">
							<option value='05' <? if ($qtde_linhas == 05) echo 'selected'; ?>>05</option>
							<option value='10' <? if ($qtde_linhas == 10) echo 'selected'; ?>>10</option>
							<option value='20' <? if ($qtde_linhas == 20) echo 'selected'; ?>>20</option>
							<option value='30' <? if ($qtde_linhas == 30) echo 'selected'; ?>>30</option>
							<option value='40' <? if ($qtde_linhas == 40) echo 'selected'; ?>>40</option>
							<? 
							if ($login_fabrica == 11) {
								echo "<option value='300'"; 
								if ($qtde_linhas == 300) echo 'selected';
								echo ">300</option>";
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_exec ($con,$sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

for ($i=0; $i<$qtde_item; $i++) {

	$novo               = 't';
	$os_revenda_item    = "";
	$referencia_produto = "";
	$serie              = "";
	$produto_descricao  = "";
	$capacidade         = "";
	$type               = "";
	$embalagem_original = "";
	$sinal_de_uso       = "";
	$aux_nota_fiscal    = "";

	if ($i % 20 == 0) {
		#if ($i > 0) {
		#	echo "<tr>";
		#	echo "<td colspan='5' align='center'>";
		#	echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";

		#	if (strlen ($os_revenda) > 0 AND strlen($exclui) > 0) {
		#		echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
		#	}

		#	echo "</td>";
		#	echo "</tr>";
		#	echo "</table>";
		#}

		echo "<table width='650' border='0' cellpadding='1' cellspacing='2' align='center' bgcolor='#ffffff'>";
		echo "<tr class='menu_top'>";
		if($login_fabrica<>19){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número de série</font></td>";
		}
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do produto</font></td>";
		
		//takashi27/06
  		if($login_fabrica<>19){
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Nota Fiscal</font></td>";
		}
		if ($login_fabrica == 19){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Qtde</font></td>";
		}
		if ($login_fabrica == 7) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Capacidade - Kg</font></td>";
		}

		if ($login_fabrica == 1 ) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Type</font></td>\n";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Embalagem Original</font></td>\n";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Sinal de Uso</font></td>\n";
		}

		echo "</tr>";
	}

	if (strlen($os_revenda) > 0){
		if (@pg_numrows($res_os) > 0) {
			$produto = trim(@pg_result($res_os,$i,produto));
		}

		if(strlen($produto) > 0){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item ,
							 tbl_os_revenda_item.serie              ,
							 tbl_os_revenda_item.capacidade         ,
							 tbl_os_revenda_item.nota_fiscal        ,
							 tbl_os_revenda_item.type               ,
							 tbl_os_revenda_item.embalagem_original ,
							 tbl_os_revenda_item.sinal_de_uso       ,
							 tbl_os_revenda_item.qtde               ,
							 tbl_produto.referencia                 ,
							 tbl_produto.descricao
					FROM	 tbl_os_revenda
					JOIN	 tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	 tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	 tbl_os_revenda_item.os_revenda = $os_revenda";
//echo $sql;
			$res = pg_exec($con, $sql);

			if (@pg_numrows($res) == 0) {
				$novo               = 't';
				$os_revenda_item    = $_POST["item_".$i];
				$referencia_produto = $_POST["produto_referencia_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$produto_descricao  = $_POST["produto_descricao_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
				$aux_nota_fiscal    = $_POST["aux_nota_fiscal_".$i];
				$aux_qtde           = $_POST["aux_qtde_".$i];
			}else{
				$novo               = 'f';
				$os_revenda_item    = pg_result($res,$i,os_revenda_item);
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$serie              = pg_result($res,$i,serie);
				$capacidade         = pg_result($res,$i,capacidade);
				$type               = pg_result($res,$i,type);
				$embalagem_original = pg_result($res,$i,embalagem_original);
				$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
				$aux_nota_fiscal    = pg_result($res,$i,nota_fiscal);
				$aux_qtde           = pg_result($res,$i,qtde);
			}
		}else{
			$novo               = 't';
		}
	}else{
		$novo               = 't';
		$os_revenda_item    = $_POST["item_".$i];
		$referencia_produto = $_POST["produto_referencia_".$i];
		$serie              = $_POST["produto_serie_".$i];
		$produto_descricao  = $_POST["produto_descricao_".$i];
		$capacidade         = $_POST["produto_capacidade_".$i];
		$type               = $_POST["type_".$i];
		$embalagem_original = $_POST["embalagem_original_".$i];
		$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
		$aux_nota_fiscal    = $_POST["aux_nota_fiscal_".$i];
		$aux_qtde           = $_POST["aux_qtde_".$i];
//echo $aux_qtde;
//echo $os_revenda;
	}

	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";

	echo "<tr "; if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo ">\n";
	echo "<input type='hidden' name='voltagem_$i' value=''>";
	if($login_fabrica<>19){
		echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='8'  maxlength='20'  value='$serie'>&nbsp;";
		if($login_fabrica<>24){ 
			echo "<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i)\" style='cursor:pointer;'>";
		}
		echo "</td>\n";
	}
	echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i' size='12' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i, document.frm_os.voltagem_$i,\"referencia\")' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' size='40' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i, document.frm_os.voltagem_$i,\"descricao\")' style='cursor:pointer;'></td>\n";
	if($login_fabrica<>19){
		if ($login_posto == 14254 and $login_fabrica == 11) 
			echo "<td align='center'><input class='frm' type='text' name='aux_nota_fiscal_$i'  size='12'  maxlength='12'  value='$aux_nota_fiscal'></td>\n";
		else
			echo "<td align='center'><input class='frm' type='text' name='aux_nota_fiscal_$i'  size='6'  maxlength='6'  value='$aux_nota_fiscal'></td>\n";
	}
	if($login_fabrica==19){
		echo "<td align='center'><input class='frm' type='text' name='aux_qtde_$i'  size='6'  maxlength='6'  value='$aux_qtde'></td>\n";
		$aux_qtde='';
	}

	if ($login_fabrica == 7) {
		echo "<td align='center'><input class='frm' type='text' name='produto_capacidade_$i'  size='9' maxlength='20' value='$capacidade'></td>\n";
	}

	if ($login_fabrica == 1) {
	?>
		<td align='center' nowrap>
		&nbsp;
		<select name='type_<? echo $i ?>' class='frm'>
			<? if(strlen($type) == 0) { ?><option value='' selected></option><? } ?>
			<option value='Tipo 1' <? if($type == 'Tipo 1') echo "selected"; ?>>Tipo 1</option>
			<option value='Tipo 2' <? if($type == 'Tipo 2') echo "selected"; ?>>Tipo 2</option>
			<option value='Tipo 3' <? if($type == 'Tipo 3') echo "selected"; ?>>Tipo 3</option>
			<option value='Tipo 4' <? if($type == 'Tipo 4') echo "selected"; ?>>Tipo 4</option>
			<option value='Tipo 5' <? if($type == 'Tipo 5') echo "selected"; ?>>Tipo 5</option>
			<option value='Tipo 6' <? if($type == 'Tipo 6') echo "selected"; ?>>Tipo 6</option>
			<option value='Tipo 7' <? if($type == 'Tipo 7') echo "selected"; ?>>Tipo 7</option>
			<option value='Tipo 8' <? if($type == 'Tipo 8') echo "selected"; ?>>Tipo 8</option>
			<option value='Tipo 9' <? if($type == 'Tipo 9') echo "selected"; ?>>Tipo 9</option>
			<option value='Tipo 10' <? if($type == 'Tipo 10') echo "selected"; ?>>Tipo 10</option>
		</select>
		&nbsp;
		</td>
		<td align='center' nowrap>
			&nbsp;
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="t" <? if ($embalagem_original == 't' OR strlen($embalagem_original) == 0) echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</b></font>
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="f" <? if ($embalagem_original == 'f') echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</b></font>
			&nbsp;
		</td>
		<td align='center' nowrap>
			&nbsp;
			<input class='frm' type="radio" name="sinal_de_uso_<? echo $i ?>" value="t" <? if ($sinal_de_uso == 't') echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</font>
			<input class='frm' type="radio" name="sinal_de_uso_<? echo $i ?>" value="f" <? if ($sinal_de_uso == 'f'  OR strlen($sinal_de_uso) == 0) echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</font>
			&nbsp;
		</td>
	<?
	}

	echo "</tr>\n";

	// limpa as variaveis
	$novo               = '';
	$os_revenda_item    = '';
	$referencia_produto = '';
	$serie              = '';
	$produto_descricao  = '';
	$capacidade         = '';

}

echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<br>";
//echo "<input type='hidden' name='btn_acao' value=''>";
echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";


if (strlen ($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
?>
</form>

<br>

<? include "rodape.php";?>
