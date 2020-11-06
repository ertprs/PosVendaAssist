<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";
include 'autentica_admin.php';

if ($login_fabrica == 1) {
	include("os_revenda_blackedecker_testb.php");
	exit;
}

include 'funcoes.php';

$sql = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
$pedir_sua_os = pg_result($res,0,pedir_sua_os);

$msg_erro  = "";
$qtde_item = 20;

if (strlen($_POST['qtde_item']) > 0) $qtde_item = $_POST['qtde_item'];

if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if ($btn_acao == "gravar")
{
	if (strlen($_POST['sua_os']) > 0){
			$xsua_os = $_POST['sua_os'] ;
		if ($login_fabrica <> 11 and $login_fabrica <> 5 and $login_fabrica<>3) {
			$xsua_os = "000000" . trim($xsua_os);
			$xsua_os = substr($xsua_os, strlen($xsua_os) - 7 , 7) ;
		}
			$xsua_os = "'". $xsua_os ."'";
	} else {
		$xsua_os = "null";
	}
	
	if($_POST['data_abertura']){
		$dat = explode("/", $_POST['data_abertura'] );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if($_POST['data_digitacao']){
		$dat = explode("/", $_POST['data_digitacao'] );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if($_POST['data_nf']){
		$dat = explode("/", $_POST['data_nf'] );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
	$xdata_abertura  = fnc_formata_data_pg($_POST['data_abertura']);
	$xdata_digitacao = fnc_formata_data_pg($_POST['data_digitacao']);
	$xdata_nf        = fnc_formata_data_pg($_POST['data_nf']);

	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';
	} else {
		$nota_fiscal = trim($nota_fiscal);
		$nota_fiscal = str_replace(".","",$nota_fiscal);
		$nota_fiscal = str_replace(" ","",$nota_fiscal);
		$nota_fiscal = str_replace("-","",$nota_fiscal);
		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr($nota_fiscal,strlen($nota_fiscal)-6,6);
		$xnota_fiscal = "'" . $nota_fiscal . "'" ;
	}

	if (strlen($_POST['revenda_cnpj']) > 0) {
		$revenda_cnpj  = $_POST['revenda_cnpj'];
		$revenda_cnpj  = str_replace(".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace(" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
	} else {
		$xrevenda_cnpj = "null";
	}

	if ($xrevenda_cnpj <> "null") {
		$sql = "SELECT *
				FROM   tbl_revenda
				WHERE  cnpj = $xrevenda_cnpj";
		$res = pg_exec($con,$sql);

		if (pg_numrows ($res) == 0){
			$msg_erro = "CNPJ da revenda não cadastrado";
		} else {
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
			$res = pg_exec($con,$sql);

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

				$res      = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (strlen($msg_erro) == 0 and strlen($cliente) == 0) {
					$res     = pg_exec($con,"SELECT CURRVAL ('seq_cliente')");
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro) == 0) $cliente = pg_result($res,0,0);
				}

			} else {
				// pega valor de cliente
				$cliente = pg_result($res,0,cliente);
			}
		}
	} else {
		$msg_erro = "CNPJ não informado";
	}

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	} else {
		$xrevenda_fone = "null";
	}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	} else {
		$xrevenda_email = "null";
	}

	if (strlen($_POST['posto_codigo']) > 0) {
		$posto_codigo = trim($_POST['posto_codigo']);
		$posto_codigo = str_replace("-","",$posto_codigo);
		$posto_codigo = str_replace(".","",$posto_codigo);
		$posto_codigo = str_replace("/","",$posto_codigo);
		$posto_codigo = substr($posto_codigo,0,14);

		$res = pg_exec($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'");
		$posto = pg_result($res,0,0);

	} else {
		$posto = "null";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	} else {
		$xobs = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	} else {
		$xcontrato = "'f'";
	}

	// Localizar a última OS para cadastro da Black & Decker
	//LIBERAR WELLINGTON 03/01/2007
	if (($login_fabrica == 1 or $login_fabrica == 11) and strlen($os_revenda) == 0) {
		if ($posto == "null") {
			$msg_erro .= " Digite o Código do Posto.";
		} else {
			if (strlen($posto) == 0) {
				$msg_erro .= " Posto digitado não foi encontrado.";
			} else {
				$sql = "SELECT MAX(sua_os) FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto;";
				$res = pg_exec($con,$sql);
				if (pg_numrows($res) == 1) {
					$xsua_os = pg_result($res,0,0) + 1;
					$xsua_os = "00000".$xsua_os;
					if ($login_fabrica==1)  $xsua_os = substr($xsua_os, strlen($xsua_os)-5 , 5) ;
					if ($login_fabrica==11) $xsua_os = substr($xsua_os, strlen($xsua_os)-11 , 11) ;
					$xsua_os = "'".$xsua_os."'";
				}
			}
		}
	}
	}

	if (strlen($msg_erro) == 0) {

		$res = pg_exec($con,"BEGIN TRANSACTION");
		
		if (strlen($os_revenda) == 0) {

			#-------------- insere pedido ------------
			$sql = "INSERT INTO tbl_os_revenda (
						fabrica      ,
						sua_os       ,
						data_abertura,
						cliente      ,
						revenda      ,
						obs          ,
						digitacao    ,
						posto        ,
						contrato     
					) VALUES (
						$login_fabrica   ,
						$xsua_os         ,
						$xdata_abertura  ,
						$cliente         ,
						$revenda         ,
						$xobs            ,
						current_timestamp,
						$posto           ,
						$xcontrato       
					)";
		} else {
//digitacao     = current_timestamp                ,
			$sql = "UPDATE tbl_os_revenda SET
						fabrica       = $login_fabrica ,
						sua_os        = $xsua_os       ,
						data_abertura = $xdata_abertura,
						cliente       = $cliente       ,
						revenda       = $revenda       ,
						obs           = $xobs          ,
						posto         = $posto         ,
						contrato      = $xcontrato     
					WHERE os_revenda = $os_revenda
					AND   fabrica    = $login_fabrica";
		}
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

/*
		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_exec($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_result($res,0,0);
			$msg_erro   = pg_errormessage($con);

			$sql = "SELECT fn_valida_os_revenda($os_revenda,$posto,$login_fabrica)";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
*/

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_exec($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_result($res,0,0);
			$msg_erro   = pg_errormessage($con);

			// se nao foi cadastrado número da OS Fabricante (Sua_OS)
			if ($xsua_os == 'null' AND strlen($msg_erro) == 0 and strlen($os_revenda) <> 0) {
				if ($login_fabrica <> 1 and $login_fabrica <> 11 and $login_fabrica<>3) {
					$sql = "UPDATE tbl_os_revenda SET
								sua_os        = '$os_revenda'
							WHERE os_revenda  = $os_revenda
							AND	 posto        = $posto
							AND	 fabrica      = $login_fabrica ";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}

			if (strlen($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET
							contrato = $xcontrato
						WHERE cliente  = $revenda";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro) > 0) {
				break ;
			}
		}

		if (strlen($msg_erro) == 0) {
			//$qtde_item = $_POST['qtde_item'];

			for ($i = 0 ; $i < $qtde_item ; $i++) 
			{
				$novo       = $_POST["novo_".$i];
				$item       = $_POST["item_".$i];

				$referencia         = $_POST["produto_referencia_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];

				$tipo_atendimento    = $_POST["tipo_atendimento_".$i];
				if (strlen(trim($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

				//SOMENTE A DYNACOM PODE COLOCAR UM DATA E NOTA PARA CADA PRODUTO
				if($login_fabrica==2){
/*					$xdata_nf        = fnc_formata_data_pg($_POST['data_nf_'.$i]);

					$nota_fiscal = $_POST["nota_fiscal_".$i];
					if (strlen($nota_fiscal) == 0) {
						$xnota_fiscal = 'null';
					} else {
						$nota_fiscal = trim($nota_fiscal);
						$nota_fiscal = str_replace(".","",$nota_fiscal);
						$nota_fiscal = str_replace(" ","",$nota_fiscal);
						$nota_fiscal = str_replace("-","",$nota_fiscal);
						$nota_fiscal = "000000" . $nota_fiscal;
						$nota_fiscal = substr($nota_fiscal,strlen($nota_fiscal)-6,6);
						$xnota_fiscal = "'" . $nota_fiscal . "'" ;
					}
				*/}

				if (strlen($serie) == 0) {
					$serie = "null";
				} else {
					$serie = "'". $serie ."'";
				}

				if (strlen($type) == 0) $type = "null";
				else                    $type = "'".$type."'";

				if (strlen($embalagem_original) == 0) $embalagem_original = "null";
				else                                  $embalagem_original = "'".$embalagem_original."'";

				if (strlen($sinal_de_uso) == 0) $sinal_de_uso = "null";
				else                            $sinal_de_uso = "'".$sinal_de_uso."'";

				if (strlen($item) > 0 AND $novo == 'f') {
					$sql = "DELETE FROM tbl_os_revenda_item
							WHERE  os_revenda = $os_revenda
							AND    os_revenda_item = $item";
					//$res = @pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

				if (strlen($msg_erro) == 0) {
					if (strlen($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace("-","",$referencia);
						$referencia = str_replace(".","",$referencia);
						$referencia = str_replace("/","",$referencia);
						$referencia = str_replace(" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql = "SELECT  produto
								FROM    tbl_produto
								WHERE   upper(referencia_pesquisa) = $referencia";

						$res = pg_exec($con,$sql);
						if (pg_numrows ($res) == 0) {
							$msg_erro = "Produto $referencia não cadastrado";
							$linha_erro = $i;
						} else {
							$produto   = pg_result($res,0,produto);
						}

						if (strlen($capacidade) == 0) {
							$xcapacidade = 'null';
						} else {
							$xcapacidade = "'".$capacidade."'";
						}
						
						if (strlen($msg_erro) == 0) {
							if ((strlen($os_revenda) == 0) OR ($novo == 't')){
								$sql =	"INSERT INTO tbl_os_revenda_item (
											os_revenda         ,
											produto            ,
											nota_fiscal        ,
											data_nf            ,
											serie              ,
											type               ,
											embalagem_original ,
											sinal_de_uso       
										) VALUES (
											$os_revenda           ,
											$produto              ,
											$xnota_fiscal         ,
											$xdata_nf             ,
											$serie                ,
											$type                 ,
											$embalagem_original   ,
											$sinal_de_uso         
										)";
							} else {
								$sql =	"UPDATE tbl_os_revenda_item SET
											produto            = '$produto'            ,
											nota_fiscal        = $xnota_fiscal         ,
											data_nf            = $xdata_nf             ,
											serie              = $serie                ,
											type               = $type                 ,
											embalagem_original = $embalagem_original   ,
											sinal_de_uso       = $sinal_de_uso         
										WHERE  os_revenda      = $os_revenda
										AND    os_revenda_item = $item";
							}
							$res = @pg_exec($con,$sql);
							$msg_erro = pg_errormessage($con);
							if (strlen($msg_erro) > 0) {
								break ;
							} else {
//								$sql = "SELECT fn_valida_os_item_revenda($os_revenda,$login_fabrica,$produto)";
//								$res = @pg_exec($con,$sql);
//								$msg_erro = pg_errormessage($con);
								
								if (strlen($msg_erro) > 0) {
									$linha_erro = $i;
									break ;
								}
							}
						}
					}
				}
			}

			$sql = "SELECT fn_valida_os_revenda($os_revenda,$posto,$login_fabrica)";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	} else {
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do Fabricante já Esta Cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		$os_revenda = '';
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica";
		$res = pg_exec($con,$sql);
		
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		
		if (strlen($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

if((strlen($msg_erro) == 0) && (strlen($os_revenda) > 0)){
	// seleciona do banco de dados
	$sql = "SELECT  OS.sua_os                                                ,
					OS.obs                                                   ,
					OS.contrato                                              ,
					to_char(OS.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(OS.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					RE.nome                                AS revenda_nome   ,
					RE.cnpj                                AS revenda_cnpj   ,
					RE.fone                                AS revenda_fone   ,
					RE.email                               AS revenda_email  ,
					PF.codigo_posto                        AS posto_codigo   ,
					PO.nome                                AS posto_nome
			FROM       tbl_os_revenda   OS
			JOIN       tbl_revenda      RE ON OS.revenda = RE.revenda
			JOIN       tbl_fabrica      FA ON FA.fabrica = OS.fabrica
			LEFT JOIN tbl_posto         PO ON PO.posto   = OS.posto
			LEFT JOIN tbl_posto_fabrica PF ON PF.posto   = PO.posto AND   PF.fabrica = FA.fabrica
			WHERE OS.os_revenda = $os_revenda
			AND   OS.fabrica    = $login_fabrica";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os         = pg_result($res,0,sua_os);
		$data_abertura  = pg_result($res,0,data_abertura);
		$data_digitacao = pg_result($res,0,data_digitacao);
		$revenda_nome   = pg_result($res,0,revenda_nome);
		$revenda_cnpj   = pg_result($res,0,revenda_cnpj);
		$revenda_fone   = pg_result($res,0,revenda_fone);
		$revenda_email  = pg_result($res,0,revenda_email);
		$obs            = pg_result($res,0,obs);
		$posto_codigo   = pg_result($res,0,posto_codigo);
		$posto_nome     = pg_result($res,0,posto_nome);
		$contrato       = pg_result($res,0,contrato);
		
		$sql = "SELECT *
				FROM   tbl_os
				WHERE  fabrica = $login_fabrica
				AND (
					   tbl_os.sua_os = '$sua_os'         OR tbl_os.sua_os = '0$sua_os'
					OR tbl_os.sua_os = '00$sua_os'       OR tbl_os.sua_os = '000$sua_os'
					OR tbl_os.sua_os = '0000$sua_os'     OR tbl_os.sua_os = '00000$sua_os'
					OR tbl_os.sua_os = '000000$sua_os'   OR tbl_os.sua_os = '0000000$sua_os'
					OR tbl_os.sua_os = '00000000$sua_os'
					OR tbl_os.sua_os = '$sua_os-01'      OR tbl_os.sua_os = '$sua_os-02'
					OR tbl_os.sua_os = '$sua_os-03'      OR tbl_os.sua_os = '$sua_os-04'
					OR tbl_os.sua_os = '$sua_os-05'      OR tbl_os.sua_os = '$sua_os-06'
					OR tbl_os.sua_os = '$sua_os-07'      OR tbl_os.sua_os = '$sua_os-08'
					OR tbl_os.sua_os = '$sua_os-09'      OR ";

		$suas_oss = "";
		for ($x=1;$x<=300;$x++) {
			$suas_oss .= "tbl_os.sua_os = '$sua_os-$x' OR ";
		}
		$sql .= $suas_oss;


		$sql .= "tbl_os.sua_os = '0$sua_os-01' OR
				 tbl_os.sua_os = '0$sua_os-02' OR
				 tbl_os.sua_os = '0$sua_os-03' OR
				 tbl_os.sua_os = '0$sua_os-04' OR
				 tbl_os.sua_os = '0$sua_os-05' OR
				 tbl_os.sua_os = '0$sua_os-06' OR
				 tbl_os.sua_os = '0$sua_os-07' OR
				 tbl_os.sua_os = '0$sua_os-08' OR
				 tbl_os.sua_os = '0$sua_os-09' OR ";

		$suas_oss = "";
		for ($x=1;$x<=40;$x++) {
			$suas_oss .= " tbl_os.sua_os = '0$sua_os-$x' OR ";
		}
		$sql .= $suas_oss;


		$sql .= "tbl_os.sua_os = '00$sua_os-01' OR
				 tbl_os.sua_os = '00$sua_os-02' OR
				 tbl_os.sua_os = '00$sua_os-03' OR
				 tbl_os.sua_os = '00$sua_os-04' OR
				 tbl_os.sua_os = '00$sua_os-05' OR
				 tbl_os.sua_os = '00$sua_os-06' OR
				 tbl_os.sua_os = '00$sua_os-07' OR
				 tbl_os.sua_os = '00$sua_os-08' OR
				 tbl_os.sua_os = '00$sua_os-09' OR ";

		$suas_oss = "";
		for ($x=1;$x<=40;$x++) {
			$suas_oss .= "tbl_os.sua_os = '00$sua_os-$x' OR ";
		}
		$sql .= $suas_oss;

		$sql .= "tbl_os.sua_os = '000$sua_os-01' OR
				 tbl_os.sua_os = '000$sua_os-02' OR
				 tbl_os.sua_os = '000$sua_os-03' OR
				 tbl_os.sua_os = '000$sua_os-04' OR
				 tbl_os.sua_os = '000$sua_os-05' OR
				 tbl_os.sua_os = '000$sua_os-06' OR
				 tbl_os.sua_os = '000$sua_os-07' OR
				 tbl_os.sua_os = '000$sua_os-08' OR
				 tbl_os.sua_os = '000$sua_os-09' OR ";

		$suas_oss = "";
		for ($x=1;$x<=40;$x++) {
			$suas_oss .= "tbl_os.sua_os = '000$sua_os-$x' OR ";
		}
		$sql .= $suas_oss;

		//apenas para terminar o OR
		$sql .= "tbl_os.sua_os = '000$sua_os-40'"; 


			$sql .= ")
					";
//echo nl2br($sql);
		$resX = @pg_exec($con, $sql);
		
		if (@pg_numrows($resX) == 0) $exclui = 1;
		
		$sql = "SELECT  tbl_os_revenda_item.nota_fiscal,
						to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf 
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_exec($con, $sql);
		
		if (pg_numrows($res) > 0){
			$nota_fiscal = pg_result($res,0,nota_fiscal);
			$data_nf     = pg_result($res,0,data_nf);
		}
	} else {
		header('Location: os_revenda.php?msg=Gravado com Sucesso!');
		exit;
	}
}
$msg = $_GET['msg'];
$title			= "CADASTRO DE ORDEM DE SERVIÇO - REVENDA"; 
$layout_menu	= "callcenter";

include "cabecalho.php";
include "javascript_pesquisas.php";
include "javascript_calendario.php";

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
	if(campo.value!=""){
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
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}
}

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_produto (campo, campo2, tipo) {
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
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
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
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa!");
	}
}


/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;
		
		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}

//INICIO DA FUNCAO DATA
function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

//Coloca NF
var ok = false;
function TodosNF() {
	f = document.frm_os;
	if (!ok) {
		for (i=0; i<<?echo $qtde_item?>; i++){
			myREF = "produto_referencia_" + i;
			myNF  = "produto_nf_0";
			myNFF = "produto_nf_" + i;
			if ((f.elements[myREF].type == "text") && (f.elements[myREF].value != "")){
				f.elements[myNFF].value = f.elements[myNF].value;
				//alert(i);
			}
			ok = true;
		}
	} else {
		for (i=1; i<<?echo $qtde_item?>; i++){
			myNFF = "produto_nf_" + i;
			f.elements[myNFF].value = "";
		}
		ok = false;
	}

}

<? if($login_fabrica == 14 OR $login_fabrica == 30) {?>
	function char(nota_fiscal){
		try{var element = nota_fiscal.which	}catch(er){};
		try{var element = event.keyCode	}catch(er){};
		if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
		return false
	}
	window.onload = function(){
		document.getElementById('nota_fiscal').onkeypress = char;
	}
<? }?>

$(function() {
		$("#data_abertura").maskedinput("99/99/9999");
		$("#data_nf").maskedinput("99/99/9999");
		$("#revenda_fone").maskedinput("(99)9999-9999");
		
		for (var i = 0; i < document.getElementById('qtde_item').value; i++) {
			var nome = '#data_nf_' + i;
			$(nome).maskedinput("99/99/9999");
		}
	});
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

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>


<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->
<? if ($ip <> "189.47.44.88" AND 1==2) { ?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> A PÁGINA FOI RETIRADA DO AR PARA QUE POSSAMOS MELHORAR A PERFORMANCE DE LANÇAMENTO.</font></td>
	</tr>
</table>

<? exit; ?>

<? } ?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class="formulario">
	<? if(strlen($msg_erro)>0){ ?>
			<tr class='msg_erro'>
				<td colspan='6'>
					<? echo $msg_erro ?>
				</td>
			</tr>
	<? } ?>

	<? if(strlen($msg)>0){ ?>
			<tr class='sucesso'>
				<td colspan='6'>
					<? echo $msg ?>
				</td>
			</tr>
	<? } ?>

	<tr class='titulo_tabela'><td colspan='6'>Cadastrar OS Revenda</td></tr>
	<tr>
		<td valign="top" align="left">
			<!--------------- Formulário ------------------->
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
			<input type='hidden' name='sua_os' value='<? echo $sua_os; ?>'>
				<tr>
					<td width='50'>&nbsp;</td>
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap>
						OS Fabricante
					</td>
					<? } ?>
					<td nowrap>
						Data Abertura
					</td>
					<td nowrap>
						Nota Fiscal
					</td>
					<td nowrap>
						Data Nota
					</td>
					<td width='50'>&nbsp;</td>
				</tr>
				<tr>
					<td width='50'>&nbsp;</td>
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap>
						<input  name="sua_os" class="frm" type="text" size="10" <?if ($login_fabrica==5) { echo " maxlength='6' ";} else { echo " maxlength='10' ";}?> value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
					</td>
					<? } ?>
					<td nowrap>
						<input name="data_abertura" id="data_abertura" size="12" maxlength="10"value="<? echo $data_abertura ?>" type="text" class="frm" tabindex="0" >
					</td>
					<?	if($login_fabrica ==45){ // HD 31076
							$maxlength = "14";
						} else {
							$maxlength = "6";
						}
					?>
					<td nowrap>
						<input name="nota_fiscal" id="nota_fiscal" size="12" maxlength="<? echo $maxlength; ?>"value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
					</td>
					<td nowrap>
						<input name="data_nf" id="data_nf" size="12" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" > 
					</td>
					<td width='50'>&nbsp;</td>
				</tr>
				
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr>
					<td width='50'>&nbsp;</td>
					<td>
						CNPJ Revenda

					</td>
					<td>
						Nome Revenda
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr>
					<td width='50'>&nbsp;</td>
					<td>
						<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onKeyUp="formata_cnpj(this.value, 'frm_os')" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, 'cnpj')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td>
						<input class="frm" type="text" name="revenda_nome" size="50" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, 'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					
					<td width='10'>&nbsp;</td>
				</tr>

				<tr>
					<td width='50'>&nbsp;</td>
					<td>
						Fone Revenda
					</td>
					<td>
						E-mail Revenda
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
				
				<tr>
					<td width='50'>&nbsp;</td>
					<td>
						<input class="frm" type="text" name="revenda_fone" id="revenda_fone" size="15"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td>
						<input class="frm" type="text" name="revenda_email" size="50" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
			</table>

			<input type="hidden" name="revenda_cidade" value="">
			<input type="hidden" name="revenda_estado" value="">
			<input type="hidden" name="revenda_endereco" value="">
			<input type="hidden" name="revenda_cep" value="">
			<input type="hidden" name="revenda_numero" value="">
			<input type="hidden" name="revenda_complemento" value="">
			<input type="hidden" name="revenda_bairro" value="">


			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr>
					<td width='50'>&nbsp;</td>
					<td width='198'>
						Código do Posto
					</td>
					<td>
						Nome do Posto
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr>
					<td width='50'>&nbsp;</td>
					<td width='198'>
						<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" style="cursor:pointer;"></A>
					</td>
					<td>
						<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr>
				<td width='50'>&nbsp;</td>
<?
	if($login_fabrica == 7){
?>
					<td nowrap>
						Contrato
					</td>
<?
	}
?>
					<td>
						Observações
					</td>
					<td>
						Qtde. Linhas
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr>
				<td width='50'>&nbsp;</td>
<?
	if($login_fabrica == 7){
?>
					<td nowrap align='center'>
						<input type="checkbox" name="contrato" value="t" <? if ($contrato == 't') echo " checked"?>>
					</td>
<?
	}
?>
					<td width="540">
						<input class="frm" type="text" name="obs" size="84" value="<? echo $obs ?>">
					</td>
					<td>
						<select size='1' class="frm" name='qtde_linhas' onChange="javascript: document.frm_os.submit(); ">
							<option value='20' <? if ($qtde_linhas == 20) echo 'selected'; ?>>20</option>
							<option value='30' <? if ($qtde_linhas == 30) echo 'selected'; ?>>30</option>
							<option value='40' <? if ($qtde_linhas == 40) echo 'selected'; ?>>40</option>					
						</select>
					</td>
					<td width='10'>&nbsp;</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<br>

<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_exec($con,$sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' id='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

for ($i=0; $i<$qtde_item; $i++) {
	if ($i % 20 == 0) {
		#if ($i > 0) {
		#	echo "<tr>";
		#	echo "<td colspan='5'>";
		#	echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";
			
		#	if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
		#		echo "<img src='imagens_admin/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
		#	}
			
		#	echo "</td>";
		#	echo "</tr>";
		#	echo "</table>";
		#}
		
		echo "<table width='700' border='0' cellpadding='0' cellspacing='1' align='center' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td align='center'>";
			if($login_fabrica==35){
				echo "PO#";
			} else {
				echo "Número Série";
			}
		echo "</td>";
		echo "<td align='center'>Produto</td>";
		echo "<td align='center'>Descrição do Produto</td>";
#		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da NF</font></td>";
#		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número da NF</font> <br> <img src='imagens/selecione_todas.gif' border=0 onclick=\"javascript:TodosNF()\" ALT='Selecionar todas' style='cursor:pointer;'></td>";
		if ($login_fabrica == 1) {
			echo "<td align='center'>Type</td>";
			echo "<td align='center'>Embalagem Original</td>";
			echo "<td align='center'>Sinal de Uso</td>";
		}
//MUDAR PARA DYNACOM
		if ($login_fabrica == 10) {
			echo "<td align='center'>Data NF</td>";
			echo "<td align='center'>Número NF </td>";
		}
		echo "</tr>";
	}
	
	if (strlen($os_revenda) > 0){
		if (@pg_numrows($res_os) > 0) {
			$produto = trim(@pg_result($res_os,$i,produto));
		}
		
		if(strlen($produto) > 0){
			// seleciona do banco de dados
			$sql =	"SELECT tbl_os_revenda_item.os_revenda_item                          ,
							tbl_os_revenda_item.serie                                    ,
							tbl_os_revenda_item.nota_fiscal                              ,
							to_char(tbl_os_revenda_item.data_nf,'DD/MM/YYYY') AS data_nf ,
							tbl_os_revenda_item.capacidade                               ,
							tbl_os_revenda_item.type                                     ,
							tbl_os_revenda_item.embalagem_original                       ,
							tbl_os_revenda_item.sinal_de_uso                             ,
							tbl_produto.referencia                                       ,
							tbl_produto.descricao                                        
					FROM	tbl_os_revenda
					JOIN	tbl_os_revenda_item
					ON		tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	tbl_produto
					ON		tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	tbl_os_revenda_item.os_revenda = $os_revenda";
			$res = pg_exec($con, $sql);
			
			if (@pg_numrows($res) == 0) {
				$novo               = 't';
				$os_revenda_item    = $_POST["item_".$i];
				$referencia_produto = $_POST["produto_referencia_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$produto_descricao  = $_POST["produto_descricao_".$i];
#				$nota_fiscal        = $_POST["produto_nf_".$i];
#				$data_nf            = $_POST["data_nf_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
			} else {
				$novo               = 'f';
				$os_revenda_item    = pg_result($res,$i,os_revenda_item);
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$serie              = pg_result($res,$i,serie);
#				$nota_fiscal        = pg_result($res,$i,nota_fiscal);
#				$data_nf            = pg_result($res,$i,data_nf);
				$capacidade         = pg_result($res,$i,capacidade);
				$type               = pg_result($res,$i,type);
				$embalagem_original = pg_result($res,$i,embalagem_original);
				$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
			}
		} else {
			$novo = 't';
			$os_revenda_item    = $_POST["item_".$i];
			$referencia_produto = $_POST["produto_referencia_".$i];
			$serie              = $_POST["produto_serie_".$i];
			$produto_descricao  = $_POST["produto_descricao_".$i];
#			$nota_fiscal        = $_POST["produto_nf_".$i];
#			$data_nf            = $_POST["data_nf_".$i];
			$capacidade         = $_POST["produto_capacidade_".$i];
			$type               = $_POST["type_".$i];
			$embalagem_original = $_POST["embalagem_original_".$i];
			$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
		}
	} else {
		$novo               = 't';
		$os_revenda_item    = $_POST["item_".$i];
		$referencia_produto = $_POST["produto_referencia_".$i];
		$serie              = $_POST["produto_serie_".$i];
		$produto_descricao  = $_POST["produto_descricao_".$i];
#		$nota_fiscal        = $_POST["produto_nf_".$i];
#		$data_nf            = $_POST["data_nf_".$i];
		$capacidade         = $_POST["produto_capacidade_".$i];
		$type               = $_POST["type_".$i];
		$embalagem_original = $_POST["embalagem_original_".$i];
		$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
	}
	
	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";
	
	echo "<tr "; if ($linha_erro == $i AND strlen($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo "bgcolor='#D9E2EF'>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='10'  maxlength='20' value='$serie'";  if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i)\""; echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i)\" style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'";  if ($login_fabrica == 5) echo " onblur='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"referencia\")'"; echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"referencia\")' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' size='35' maxlength='50' value='$produto_descricao'";  if ($login_fabrica == 5) echo " onblur='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"descricao\")'"; echo ">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"descricao\")' style='cursor:pointer;'></td>\n";
#	echo "<td align='center'><input class='frm' type='text' name='data_nf_$i'  size='12'  maxlength='10'  value='$data_nf'></td>";
#	echo "<td align='center'><input class='frm' type='text' name='produto_nf_$i' size='9' maxlength='20' value='$nota_fiscal'>";
	if ($login_fabrica == 1) {
		echo "<td align='center' nowrap>\n";
		echo " &nbsp; <select name='type_$i' class='frm'>";
		if(strlen($type) == 0) { echo "<option value='' selected></option>"; }
		echo "<option value='Tipo 1'"; if($type == 'Tipo 1') echo " selected"; echo ">Tipo 1</option>";
		echo "<option value='Tipo 2'"; if($type == 'Tipo 2') echo " selected"; echo ">Tipo 2</option>";
		echo "<option value='Tipo 3'"; if($type == 'Tipo 3') echo " selected"; echo ">Tipo 3</option>";
		echo "<option value='Tipo 4'"; if($type == 'Tipo 4') echo " selected"; echo ">Tipo 4</option>";
		echo "<option value='Tipo 5'"; if($type == 'Tipo 5') echo " selected"; echo ">Tipo 5</option>";
		echo "<option value='Tipo 6'"; if($type == 'Tipo 6') echo " selected"; echo ">Tipo 6</option>";
		echo "<option value='Tipo 7'"; if($type == 'Tipo 7') echo " selected"; echo ">Tipo 7</option>";
		echo "<option value='Tipo 8'"; if($type == 'Tipo 8') echo " selected"; echo ">Tipo 8</option>";
		echo "<option value='Tipo 9'"; if($type == 'Tipo 9') echo " selected"; echo ">Tipo 9</option>";
		echo "<option value='Tipo 10'"; if($type == 'Tipo 10') echo " selected"; echo ">Tipo 10</option>";
		echo "</select> &nbsp; ";
		echo "</td>\n";
		echo "<td align='center' nowrap>\n";
		echo " &nbsp; <input class='frm' type='radio' name='embalagem_original_$i' value='t'"; if ($embalagem_original == 't' OR strlen($embalagem_original) == 0) echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</b></font> ";
		echo "<input class='frm' type='radio' name='embalagem_original_$i' value='f'"; if ($embalagem_original == 'f') echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</b></font> &nbsp; ";
		echo "</td>\n";
		echo "<td align='center' nowrap>\n";
		echo " &nbsp; <input class='frm' type='radio' name='sinal_de_uso_$i' value='t'"; if ($sinal_de_uso == 't') echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</font> ";
		echo "<input class='frm' type='radio' name='sinal_de_uso_$i' value='f'"; if ($sinal_de_uso == 'f'  OR strlen($sinal_de_uso) == 0) echo " checked"; echo ">";
		echo " <font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</font> &nbsp; ";
		echo "</td>\n";
	}
	if($login_fabrica==10){
		echo "<td nowrap align='center'><input name='data_nf_$i' id='data_nf_$i' size='12' maxlength='10' value='$data_nf_$i' type='text' class='frm' tabindex='0' > <font face='arial' size='1'></font></td>";
		echo "<td nowrap align='center'>";
		echo "<input name='nota_fiscal' size='8' maxlength='6'value='$nota_fiscal ' type='text' class='frm' tabindex='0' ></td>";
			
	}
	echo "</tr>\n";
}
echo "<tr>\n";
?>
<tr bgcolor="#D9E2EF"><td colspan='6'>&nbsp;</td></tr>
<tr>
	<td colspan='6' align='center' bgcolor="#D9E2EF">
		<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT='Gravar' border='0' >
	
	<? if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) { ?>
		&nbsp;&nbsp;<input type="button" style="background:url(imagens_admin/btn_apagar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.') }" ALT='Apagar a Ordem de Serviço' border='0'>
	<? } ?>
	</td>
</tr>
</table>


</table>

</form>

<br>

<? include 'rodape.php'; ?>
