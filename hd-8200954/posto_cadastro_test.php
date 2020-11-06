<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

/*
29/11/2008 09:07:04 Nenhuma função de 'funcoes.php' é usada no programa até hoje.
include 'funcoes.php';
*/

$msg_erro  = "";
$msg_debug = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {
	$email				= trim($_POST ['email']);
	$fone				= trim($_POST ['fone']);
	$fax				= trim($_POST ['fax']);
	$nome_fantasia		= trim($_POST ['nome_fantasia']);
	$capital_interior	= trim($_POST ['capital_interior']);
	$endereco			= trim($_POST ['endereco']);
	$numero				= trim($_POST ['numero']);
	$complemento		= trim($_POST ['complemento']);
	$bairro				= trim($_POST ['bairro']);
	$cep				= trim($_POST ['cep']);
	$contato			= trim($_POST ['contato']);
	if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90 ){
		$banco              = trim($_POST ['banco']);
		$agencia            = trim($_POST ['agencia']);
		$conta              = trim($_POST ['conta']);
		$nomebanco          = trim($_POST ['nomebanco']);
		$favorecido_conta   = trim($_POST ['favorecido_conta']);
		$conta_operacao     = trim($_POST ['conta_operacao']);//HD 8190 5/12/2007 Gustavo
		$cpf_conta          = trim($_POST ['cpf_conta']);
		$tipo_conta         = trim($_POST ['tipo_conta']);
		$obs_conta          = trim($_POST ['obs_conta']);
	}
	//hd 11308 14/1/2008
	if($login_fabrica == 15){
		$im		= trim($_POST ['im']);

		if (strlen($im) > 0)
			$xim = "'".$im."'";
		else
			$xim = 'null';
	}

	if (strlen($email) > 0)
		$xemail = "'".$email."'";
	else
		$xemail = 'null';

	if (strlen($fone) > 0)
		$xfone = "'".$fone."'";
	else
		$xfone = 'null';

	if (strlen($fax) > 0)
		$xfax = "'".$fax."'";
	else
		$xfax = 'null';

	if (strlen($nome_fantasia) > 0)
		$xnome_fantasia = "'".$nome_fantasia."'";
	else
		$xnome_fantasia = 'null';

	if (strlen($capital_interior) > 0)
		$xcapital_interior = "'".$capital_interior."'";
	else
		$xcapital_interior = 'null';
	
	if (strlen($endereco) > 0)
		$xendereco = "'".$endereco."'";
	else
		$xendereco = 'null';

	if (strlen($numero) > 0)
		$xnumero = "'".$numero."'";
	else
		$xnumero = 'null';

	if (strlen($complemento) > 0)
		$xcomplemento = "'".$complemento."'";
	else
		$xcomplemento = 'null';

	if (strlen($bairro) > 0)
		$xbairro = "'".$bairro."'";
	else
		$xbairro = 'null';

	if (strlen($cep) > 0){
		$xcep = str_replace (".","",$cep);
		$xcep = str_replace ("-","",$xcep);
		$xcep = str_replace (" ","",$xcep);
		$xcep = "'".$xcep."'";
	}else{
		$xcep = 'null';
	}

	if (strlen($contato) > 0)
		$xcontato = "'".$contato."'";
	else
		$xcontato = 'null';

	//email Ronaldo 12/01/2010
	if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90){
		if (strlen($banco) > 0) {
			$xbanco = "'".$banco."'";
			$sqlB = "SELECT nome FROM tbl_banco WHERE codigo = '$banco'";
			$resB = @pg_exec($con,$sqlB);
			if (@pg_numrows($resB) == 1) {
				$xnomebanco = "'" . trim(@pg_result($resB,0,0)) . "'";
			}else{
				$xnomebanco = "null";
			}
		}else{
			$xbanco     = "null";
			$xnomebanco = "null";
		}

		$xagencia          = (strlen($agencia) > 0) ? "'".$agencia."'" : 'null';
		$xconta            = (strlen($conta) > 0) ? "'".$conta."'" : 'null';
		$xfavorecido_conta = (strlen($favorecido_conta) > 0) ? "'".$favorecido_conta."'" : 'null';
		$xconta_operacao   = (strlen($conta_operacao) > 0) ? "'".$conta_operacao."'" : 'null';
		$xtipo_conta       = (strlen($tipo_conta) > 0) ? "'".$tipo_conta."'" : 'null';
		if($tipo_conta!='Conta jurídica'){
			$msg_erro = "A conta tem que ser somente JURÍDICA!";
		}
		$cpf_conta = str_replace (".","",$cpf_conta);
		$cpf_conta = str_replace ("-","",$cpf_conta);
		$cpf_conta = str_replace ("/","",$cpf_conta);
		$cpf_conta = str_replace (" ","",$cpf_conta);

		if (strlen($cpf_conta) <> 14 AND $tipo_conta == 'Conta jurídica'){
			$msg_erro = "CNPJ da Conta jurídica inválida";
		}

		$xcpf_conta               = (strlen($cpf_conta) > 0) ? "'".$cpf_conta."'" : 'null';
		$xobs_conta               = (strlen($obs_conta) > 0) ? "'".$obs_conta."'" : 'null';
		$sqlvalida = "SELECT fn_valida_cnpj_cpf('$cpf_conta')";
		$resvalida = @pg_exec($con,$sqlvalida);
		if(strlen(pg_errormessage($con)) > 0){
			$msg_erro = "CNPJ Inválido!";
		}
	}
	//email do Ronaldo
	if(strlen($msg_erro)==0){
	#----------------------------- Alteração de Dados ---------------------
		if (strlen ($login_posto) > 0 and $login_fabrica<>11 and $login_fabrica<>1  and $login_fabrica<>3 and $login_fabrica<>50) {
			$sql = "UPDATE tbl_posto SET
						contato			= $xcontato                        ,
						contato_endereco        = $xendereco               ,
						contato_numero          = $xnumero                 ,
						contato_complemento     = $xcomplemento            ,
						contato_bairro          = $xbairro                 ,
						contato_cidade          = $xcidade                 ,
						contato_cep             = $xcep                    ,
						contato_estado          = $xestado                 ,
						nome_fantasia           = $xnome_fantasia          ,
						contato_email           = $xemail                  
					WHERE tbl_posto.posto           = $login_posto
					AND	  tbl_posto.posto           = tbl_posto_fabrica.posto
					AND	  tbl_posto_fabrica.fabrica = $login_fabrica ";

			$sql = "UPDATE tbl_posto SET
						contato			= $xcontato                        ,
						email           = $xemail                  ";
			if($login_fabrica==15){
				$sql  .=",   im         = $xim ";
			}
			$sql .= "WHERE tbl_posto.posto           = $login_posto
					AND	  tbl_posto.posto           = tbl_posto_fabrica.posto
					AND	  tbl_posto_fabrica.fabrica = $login_fabrica ";
			#		$sql = "UPDATE tbl_posto SET
			#					email			= $xemail";

			#		$sql .=" WHERE tbl_posto.posto       = $login_posto
			#		AND	  tbl_posto.posto           = tbl_posto_fabrica.posto
			#			AND	  tbl_posto_fabrica.fabrica = $login_fabrica ";

			$res = pg_exec($con,$sql);
			if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);
		}

		#---------------------- Alteração de Dados para Britânia  ---------------------
		#29/11/2008	MLG - HD 53598	O Posto da Bosch (20) pode também alterar seu cadastro
		#  			Alteração:
		#				if (strlen ($login_posto) > 0 and 8 <---- $login_fabrica==3 ---->8 ) {

		if (strlen ($login_posto) > 0 and ($login_fabrica==3 OR $login_fabrica == 20 OR $login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 24 or $login_fabrica == 15 or $login_fabrica ==90)) {
			if (strlen($msg_erro) == 0) {
				//$res = pg_exec ($con,"BEGIN TRANSACTION");
				$sql = "SELECT
						contato_endereco   ,
						contato_numero     ,
						contato_complemento,
						contato_bairro     ,
						contato_cep        ,
						contato_email      ,
						capital_interior   ,
						tbl_posto.contato            ,
						tbl_posto.capital_interior   ,
						tbl_posto_fabrica.contato_fone_comercial as fone,
						tbl_posto_fabrica.contato_fax            as fax,
						tbl_posto.nome_fantasia
						FROM tbl_posto_fabrica
						JOIN tbl_posto USING(posto)
						WHERE tbl_posto_fabrica.posto = $login_posto
						AND  tbl_posto_fabrica.fabrica = $login_fabrica";
				$res = pg_exec($con,$sql);

				if (@pg_numrows ($res) > 0) {
					$bendereco            = trim(pg_result($res,0,contato_endereco));
					$bnumero              = trim(pg_result($res,0,contato_numero));
					$bcomplemento         = trim(pg_result($res,0,contato_complemento));
					$bcapital_interior    = trim(pg_result($res,0,capital_interior));
					$bbairro              = trim(pg_result($res,0,contato_bairro));
					$bcep                 = trim(pg_result($res,0,contato_cep));
					$bemail               = trim(pg_result($res,0,contato_email));
					$bcontato             = trim(pg_result($res,0,contato));
					$bfone                = trim(pg_result($res,0,fone));
					$bfax                 = trim(pg_result($res,0,fax));
					$bnome_fantasia       = trim(pg_result($res,0,nome_fantasia));
				}

				$sql = "UPDATE tbl_posto_fabrica SET
							contato_endereco        = $xendereco               ,
							contato_numero          = $xnumero                 ,
							contato_complemento     = $xcomplemento            ,
							contato_bairro          = $xbairro                 ,
							contato_cep             = $xcep                    ,
							contato_fone_comercial  = $xfone                   ,
							contato_fax             = $xfax                    ,
							nome_fantasia           = $xnome_fantasia          ,
							atualizacao             = current_timestamp        ,
							contato_email           = $xemail                  ";
				if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90){
					$sql .= ",
							banco                   = $xbanco                  ,
							agencia                 = $xagencia                ,
							conta                   = $xconta                  ,
							nomebanco               = $xnomebanco              ,
							favorecido_conta        = $xfavorecido_conta       ,
							conta_operacao          = $xconta_operacao         , 
							cpf_conta               = $xcpf_conta              ,
							tipo_conta              = $xtipo_conta              ,
							obs_conta               = $xobs_conta              ";
				}
				$sql .= " WHERE tbl_posto_fabrica.posto = $login_posto
						AND	  tbl_posto_fabrica.fabrica = $login_fabrica ";
						//echo $sql; exit;
				$res = pg_exec($con,$sql);
				if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);
				
				if(strlen($msg_erro)==0){
					$sql = "UPDATE tbl_posto SET
							capital_interior = upper ($xcapital_interior)      ,
							contato			= $xcontato                        ,
							fone			= $xfone                           ,
							fax				= $xfax                            ,
							nome_fantasia	= $xnome_fantasia                  
							WHERE tbl_posto.posto = $login_posto";
					$res = pg_exec($con,$sql);
					if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);
				}
			}

			/*if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");
				header ("Location: $PHP_SELF");
				exit;
			}else{
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}*/

		}
	}
	// grava posto_fabrica
	if (strlen($msg_erro) == 0){
		$senha                = trim ($_POST['senha']);
		$senha2               = trim ($_POST['senha2']);

		if ($senha <> $senha2)
			$msg_erro = traduz("as.senhas.nao.sao.iguais.redigite",$con,$cook_idioma);


		//Wellington 31/08/2006 - MINIMO 6 CARACTERES SENDO UM MINIMO DE 2 LETRAS E MINIMO DE 2 NUMEROS
		if (strlen($senha) > 0) {
			if (strlen(trim($senha)) >= 6) {
				//- verifica qtd de letras e numeros da senha digitada -//
				$count_letras  = 0;
				$count_numeros = 0;
				$letras  = 'abcdefghijklmnopqrstuvwxyz';
				$numeros = '0123456789';

				for ($i = 0; $i <= strlen($senha); $i++) {
					if ( strpos($letras, substr($senha, $i, 1)) !== false)
						$count_letras++;
					
					if ( strpos ($numeros, substr($senha, $i, 1)) !== false)
						$count_numeros++;
				}

				if ($count_letras < 2) {
					$msg_erro = traduz("senha.invalida.a.senha.deve.ter.pelo.menos.2.letras",$con,$cook_idioma);
				}
				if ($count_numeros < 2) {
					$msg_erro = traduz("senha.invalida.a.senha.deve.ter.pelo.menos.2.numeros",$con,$cook_idioma);
				}
			}else{
				$msg_erro = traduz("a.senha.deve.conter.um.minimo.de.6.caracteres",$con,$cook_idioma);
			}

			$xsenha = "'".$senha."'";
		}else if($login_fabrica<>3 and $login_fabrica<>81 and $login_fabrica <> 90 and $login_fabrica <> 24 and $login_fabrica <> 15){
			$msg_erro = traduz("digite.uma.senha",$con,$cook_idioma);
		}

		// verifica se o posto já tem codigo e senha cadastrados para alguma outra fábrica
		$sql = "SELECT tbl_posto_fabrica.fabrica
			FROM   tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.posto   = $login_posto
			AND    tbl_posto_fabrica.senha   = '$senha'
			AND    tbl_posto_fabrica.fabrica <> $login_fabrica";
		$res = @pg_exec($con,$sql);
		
		if (@pg_numrows ($res) > 0) {
			$msg_erro = traduz("senha.invalida.por.favor.digite.uma.nova.senha.para.esta.fabrica",$con,$cook_idioma);
		}


		if (strlen($msg_erro) == 0){
			$sql = "SELECT	* 
					FROM	tbl_posto_fabrica
					WHERE	posto   = $login_posto
					AND		fabrica = $login_fabrica ";
			$res = pg_exec($con,$sql);
			$total_rows = pg_numrows($res);

			if (pg_numrows ($res) > 0) {
				if (strlen($senha) > 0){
					$sql = "UPDATE tbl_posto_fabrica SET 
							senha                = '$senha',
							data_expira_senha = current_date + interval '90day'
						WHERE tbl_posto_fabrica.posto   = $login_posto
						AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
					$res = pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
				}
			}
		}
	}


	if ($login_fabrica == 24) {
		$sql = "SELECT * FROM tbl_linha WHERE fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

			
			$linha = pg_fetch_result ($res,$i,linha);
			$atende       = $_POST ['atende_'       . $linha];
		
			if (strlen ($atende) == 0) {
				echo $sql = "DELETE FROM tbl_posto_linha WHERE posto = $login_posto AND linha = $linha";
				$resX = pg_query ($con,$sql);
			} else {
				$sql = "SELECT * FROM tbl_posto_linha WHERE posto = $login_posto AND linha = $linha";
				
				$resX = pg_query ($con,$sql);
				if (pg_num_rows ($resX) == 0) {
						$sql = "INSERT INTO tbl_posto_linha (
									posto   ,
									linha   
								) VALUES (
									$login_posto   ,
									$linha   
								)";
						$resX = pg_query ($con,$sql);
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		# ENVIA EMAIL 
		$sql = "SELECT	email_gerente
			FROM	tbl_fabrica
			WHERE	fabrica = $login_fabrica
			AND     email_gerente notnull;";
		$resw = pg_exec($con,$sql);

		if (pg_numrows($resw) > 0 OR $login_fabrica==3){

			if($login_fabrica==3){
				//gustavo@telecontrol.com.br
				$email_britania = "lidia.prestes@britania.com.br";
			}else{
				$email_gerente = pg_result($resw,0,0);
				$email = explode (";",$email_gerente);
			}

			$sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql);
			$codigo_posto = pg_result ($res,0,0);
	
			#'------------ Manda email para GERENTE -------------
			if($login_fabrica==3 AND ($nome_fantasia<>$bnome_fantasia OR $email<>$bemail OR $endereco<>$bendereco OR $numero<>$bnumero
				OR $complemento<>$bcomplemento OR $bairro<>$bbairro OR $cep<>$bcep OR $fone<>$bfone OR $fax<>$bfax OR $contato<>$bcontato OR $capital_interior<>$bcapital_interior)){
				$text .= "<table width='600'>";
					$text .= "<tr><td colspan='2'>Houve alteração no cadastro do posto $codigo_posto - $login_nome.</td></tr>";
					$text .= "<tr><td colspan='2'>";
					$text .= "<BR>";
					$text .= "</td></tr>";
					if($nome_fantasia<>$bnome_fantasia){
					$text .= "<tr><td colspan='2'>";
					$text .= "Nome Fantasia = ".$nome_fantasia;
					$text .= "</td></tr>";
					}
					if($email<>$bemail){
					$text .= "<tr><td colspan='2'>";
					$text .= "Email = ".$email;
					$text .= "</td></tr>";
					}
					if($endereco<>$bendereco){
					$text .= "<tr><td colspan='2'>";
					$text .= "Endereço = ".$endereco;
					$text .= "</td></tr>";
					}
					if($numero<>$bnumero){
					$text .= "<tr><td colspan='2'>";
					$text .= "Endereço = ".$numero;
					$text .= "</td></tr>";
					}
					if($complemento<>$bcomplemento){
					$text .= "<tr><td colspan='2'>";
					$text .= "Complemento = ".$complemento;
					$text .= "</td></tr>";
					}
					if($bairro<>$bbairro){
					$text .= "<tr><td colspan='2'>";
					$text .= "Bairro = ".$bairro;
					$text .= "</td></tr>";
					}
					if($cep<>$bcep){
					$text .= "<tr><td colspan='2'>";
					$text .= "Cep = ".$cep;
					$text .= "</td></tr>";
					}
					if($fone<>$bfone){
					$text .= "<tr><td colspan='2'>";
					$text .= "Fone = ".$fone;
					$text .= "</td></tr>";
					}
					if($fax<>$bfax){
					$text .= "<tr><td colspan='2'>";
					$text .= "Fax = ".$fax;
					$text .= "</td></tr>";
					}
					if($contato<>$bcontato){
					$text .= "<tr><td colspan='2'>";
					$text .= "Contato = ".$contato;
					$text .= "</td></tr>";
					}
					if($capital_interior<>$bcapital_interior){
					$text .= "<tr><td colspan='2'>";
					$text .= "Capital/Interior = ".$capital_interior;
					$text .= "</td></tr>";
					}
					$text .= "</table>";
			}else if($login_fabrica<>3){
				$text .= "<table width='600'>";
				if ($sistema_lingua=='ES') $text .= "<tr><td colspan='2'><font size='2' face='verdana'>Fue cambiado el catastro del servicio $codigo_posto - $login_nome. Confira el sistema interno con el site.</font></td></tr>";
				else $text .= "<tr><td colspan='2'><font size='2' face='verdana'>Houve alteração no cadastro do posto $codigo_posto - $login_nome. Confira o sistema interno com o site.</font></td></tr>";
				$text .= "</table>";
			}


			
			if (strlen($text) > 0) {
				if ($sistema_lingua=='ES') $subject    = "Cambio em el catastro del servicio.";
				else $subject    = "Alteração no Cadastro do Posto.";
				
				$from_nome  = "Telecontrol";
				$from_email = "telecontrol@telecontrol.com.br";

				$cabecalho .= "From: $from_nome <$from_email>\n";

//				$cabecalho .= "Return-Path: <$from_email>\n";
				$cabecalho .= "MIME-version: 1.0\n"; 
				$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n"; 
				
				if($login_fabrica==3){
					mail ($email_britania, stripslashes($subject), "$text" , "$cabecalho");
				}else{
					for ($i=0 ; $i < count($email); $i++){
						mail ($email[$i] , stripslashes($subject), "$text" , "$cabecalho");
						//echo "enviou $i";
					}
				}
				
				$from_nome  = "";
				$from_email = "";
				$to_email   = "";
				$cc_nome    = "";
				$cc_email   = "";
				$subject    = "";
				$cabecalho  = "";
			}
		}
		#fim
		header ("Location: $PHP_SELF");
		exit;
	}

}

#-------------------- Pesquisa Posto -----------------
if(strlen($msg_erro)==0){
	$sql = "SELECT  tbl_posto_fabrica.posto               ,
					tbl_posto_fabrica.codigo_posto        ,
					tbl_posto_fabrica.tipo_posto          ,
					tbl_posto_fabrica.transportadora_nome ,
					tbl_posto_fabrica.transportadora      ,
					tbl_posto_fabrica.cobranca_endereco   ,
					tbl_posto_fabrica.cobranca_numero     ,
					tbl_posto_fabrica.cobranca_complemento,
					tbl_posto_fabrica.cobranca_bairro     ,
					tbl_posto_fabrica.cobranca_cep        ,
					tbl_posto_fabrica.cobranca_cidade     ,
					tbl_posto_fabrica.cobranca_estado     ,
					tbl_posto_fabrica.obs                 ,
					tbl_posto_fabrica.atualizacao         ,
					tbl_posto.nome                        ,
					tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto.im                          ,";
			if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90 ){
				$sql .= "
					tbl_posto_fabrica.banco               ,
					tbl_posto_fabrica.agencia             ,
					tbl_posto_fabrica.conta               ,
					tbl_posto_fabrica.nomebanco           ,
					tbl_posto_fabrica.favorecido_conta    ,
					tbl_posto_fabrica.conta_operacao      ,
					tbl_posto_fabrica.cpf_conta           ,
					tbl_posto_fabrica.atendimento         ,
					tbl_posto_fabrica.tipo_conta          ,
					tbl_posto_fabrica.obs_conta           ,";
			}
			$sql .= "tbl_posto_fabrica.contato_endereco       AS endereco,
					tbl_posto_fabrica.contato_numero         AS numero,
					tbl_posto_fabrica.contato_complemento    AS complemento,
					tbl_posto_fabrica.contato_bairro         AS bairro,
					tbl_posto_fabrica.contato_cep            AS cep,
					tbl_posto_fabrica.contato_cidade         AS cidade,
					tbl_posto_fabrica.contato_estado         AS estado,
					tbl_posto_fabrica.contato_email          AS email,
					tbl_posto_fabrica.contato_fone_comercial AS fone,
					tbl_posto_fabrica.contato_fax            AS fax,
					tbl_posto.contato                     ,
					tbl_posto.capital_interior            ,
					tbl_posto.nome_fantasia               ,
					tbl_posto_fabrica.senha               ,
					tbl_posto_fabrica.desconto            
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.posto   = $login_posto ";
	$res = pg_exec ($con,$sql);
		
	if (@pg_numrows ($res) > 0) {
		$codigo           = trim(pg_result($res,0,codigo_posto));
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$ie               = trim(pg_result($res,0,ie));
		$atualizacao      = trim(pg_result($res,0,atualizacao));
		$im               = trim(pg_result($res,0,im));
		if (strlen($cnpj) == 14) {
			$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		}
		if (strlen($cnpj) == 11) {
			$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		}
		$endereco         = trim(pg_result($res,0,endereco));
		$endereco         = str_replace("\"","",$endereco);
		$numero           = trim(pg_result($res,0,numero));
		$complemento      = trim(pg_result($res,0,complemento));
		$bairro           = trim(pg_result($res,0,bairro));
		$cep              = trim(pg_result($res,0,cep));
		$cidade           = trim(pg_result($res,0,cidade));
		$estado           = trim(pg_result($res,0,estado));
		$email            = trim(pg_result($res,0,email));
		$fone             = trim(pg_result($res,0,fone));
		$fax              = trim(pg_result($res,0,fax));
		$contato          = trim(pg_result($res,0,contato));
		$obs              = trim(pg_result($res,0,obs));
		$capital_interior = trim(pg_result($res,0,capital_interior));
		$senha            = trim(pg_result($res,0,senha));
		$nome_fantasia    = trim(pg_result($res,0,nome_fantasia));
		
		if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90){
			$banco            = trim(pg_result($res,0,banco));
			$agencia          = trim(pg_result($res,0,agencia));
			$conta            = trim(pg_result($res,0,conta));
			$nomeconta        = trim(pg_result($res,0,nomebanco));
			$favorecido_conta = trim(pg_result($res,0,favorecido_conta));
			$cpf_conta        = trim(pg_result($res,0,cpf_conta));
			$tipo_conta       = trim(pg_result($res,0,tipo_conta));
			$obs_conta        = trim(pg_result($res,0,obs_conta));
		}
		$cobranca_endereco    = trim(pg_result($res,0,cobranca_endereco));
		$cobranca_numero      = trim(pg_result($res,0,cobranca_numero));
		$cobranca_complemento = trim(pg_result($res,0,cobranca_complemento));
		$cobranca_bairro      = trim(pg_result($res,0,cobranca_bairro));
		$cobranca_cep         = trim(pg_result($res,0,cobranca_cep));
		$cobranca_cidade      = trim(pg_result($res,0,cobranca_cidade));
		$cobranca_estado      = trim(pg_result($res,0,cobranca_estado));
	}
}

$title = "Informações do Posto";
$layout_menu = "cadastro";

include 'cabecalho.php';

?>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/ jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<style type="text/css">

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}



.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
	text-transform:capitalize;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #ACACAC;
	empty-cells:show;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
</style>

<script language='javascript'>
//função p/ digitar só numero
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
	}
}

function isNumberKey ( evt ){  
	var charCode = ( evt.which ) ? evt.which : event.keyCode;  
	if ( charCode > 31 && (charCode < 48 || charCode > 57) ) return false;  
	return true;  
}  

//adiciona mascara de cep
function MascaraCep(cep){
	if(mascaraInteiro(cep)==false){
		event.returnValue = false;
	}
	return formataCampo(cep, '00.000-000', event);
}
	var hora = new Date();
	var engana = hora.getTime();
	$().ready(function() {
		$('#banco_nome').autocomplete("autocomplete_banco_ajax.php?engana=" + engana,{
			minChars: 3,
			delay: 150,
			width: 450,
			scroll: true,
			scrollHeight: 200,
			matchContains: false,
			highlightItem: false,
			formatItem: function (row)   {return row[0]+" - "+row[1]},
			formatResult: function(row)  {return row[0];}
		});
		$('#banco_nome').result(function(event, data, formatted) {
			//alert(data[0]);
			$("#banco_nome").val(data[0] + '-' + data[1]);
			//HD 344430: O banco deve ser recuperado e gravado pelo campo tbl_banco.codigo e não por tbl_banco.banco
			$("#banco").val(data[0]);
		});
	})

</script>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='700' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<font color='RED'><b>$msg_erro</b></font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<p>
<table width='700' align='center' border='0' class="texto_avulso">
	<tr>
		<td align='center'>
			<? fecho ("para.alterar.os.outros.campos.entre.em.contato.com.o.fabricante",$con,$cook_idioma);?>
		</td>
	</tr>
</table>
<?php

if ($login_fabrica == 24 || $login_fabrica == 15) {
	
	if ($login_fabrica == 24) {
		$data_hora = '2010-06-09 09:36:39.548903';
	} else if ($login_fabrica == 15) {
		$data_hora = '2010-08-10 09:36:39.548903';
	}

	$sql = "SELECT CASE WHEN '$atualizacao' <= '$data_hora' THEN 'sim' ELSE 'NAO' END";

	$res = pg_exec($con,$sql);

	if (pg_num_rows($res) > 0) {
		$resposta = pg_result($res,0,0);
	}

	if ($resposta == 'sim') {?>
		<br />
		<table width='700' align='center' border='0' bgcolor='#FF3333'>
			<tr>
				<td align='center'>
					<font face='arial, verdana' color='#FFFFFF' size='-1'>
						Por favor, para continuar é necessário atualizar os dados, se todos dados estiverem corretos, clique no botão Gravar. Após isso para continuar acesse a Aba Ordem de Serviço no canto superior esquerdo da tela.
					</font>
				</td>
			</tr>
		</table><?php
	}

}
?>

<form name="frm_posto" method="post" action="<?=$PHP_SELF?>">
<table width='700' align='center' border='0' cellpadding="3" cellspacing="1" class="formulario">
	<tr>
		<td colspan="6" class="titulo_tabela">
			<? echo traduz("informacoes.cadastrais",$con,$cook_idioma);?>
		</td>
	</tr>

	<tr class='subtitulo'>
		<td><? if($cook_idioma=='ES') echo "ID servicio 1";else fecho ("cnpj.cpf",$con,$cook_idioma);?></td>
		<td><? if($cook_idioma=='ES') echo "ID servicio 2";else fecho ("ie",$con,$cook_idioma);?></td>
		<? if($login_fabrica == 15){ echo "<td>".traduz("ie",$con,$cook_idioma)."</td>"; } ?>
		<td><? echo traduz("fone",$con,$cook_idioma);?></td>
		<td><? echo traduz("fax",$con,$cook_idioma);?></td>
		
	</tr>

	<tr >
		<td><input type="text" style="width:100%" value="<?echo $cnpj ?>" disabled=""></td>
		<td><input type="text" style="width:100%" value="<? echo $ie ?>" disabled=""></td>
		<? if($login_fabrica==15){?>
		<td><input type="text" name="im" size="15" maxlength="20" value="<? echo $im ?>" onblur="checarNumero(this);">&nbsp;<span class='text_curto'> <a href='#' title='Digite apenas números neste campo.' class='ajuda'>?</a></span></td>
		<?}?>
		<td><input type="text" name="fone" style="width:100%;text-align:left" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" style="width:100%;text-align:left" value="<? echo $fax ?>"></td>
		
	</tr>
	
	<tr  class='subtitulo'>
		<td colspan="100%"><? echo traduz("contato",$con,$cook_idioma);?></td>
	</tr>

	<tr>
		<td colspan="100%"><input type="text" name="contato" style="width:100%" value="<? echo $contato ?>" style="width:100px"></td>
	</tr>
	
	<tr class='subtitulo'>
		<td colspan="1"><? echo traduz("codigo",$con,$cook_idioma);?></td>
		<td colspan="5"><? echo traduz("razao.social",$con,$cook_idioma);?></td>
	</tr>

	<tr>
		<td colspan="1"><input type="text" style="width:100%" value="<? echo $codigo ?>" disabled=""></td>
		<td colspan="5"><input type="text" style="width:100%" value="<? echo $nome ?>" disabled=""></td>
	</tr>
</table>



<table class="formulario" width='700' align='center' border='0' cellpadding="3" cellspacing="1">
	<tr class='subtitulo'>
		<td colspan="2" width="50%"><? echo traduz("endereco",$con,$cook_idioma);?></td>
		<td><? echo traduz("numero",$con,$cook_idioma);?></td>
		<td colspan="2"><? echo traduz("complemento",$con,$cook_idioma);?></td>
	</tr>
	<tr >
		<? //HD 24581
		if ($login_fabrica==30){
			echo "<td colspan='2' class='borda'>$endereco</td>";
			echo "<td class='borda'>$numero</td>";
			echo "<td colspan='2' class='borda'>$complemento</td>";
		}else{ ?>
			<td colspan="2"><input type="text" name="endereco" style="width:100%" value="<? echo $endereco ?>"></td>
			<td><input type="text" name="numero" style="width:100%" value="<? echo $numero ?>"></td>
			<td colspan="2"><input type="text" name="complemento" style="width:100%" value="<? echo $complemento ?>"></td>
		<? } ?>
	</tr>
	<tr class='subtitulo'>
		<td colspan="2" style='width:220px'><? if($sistema_lingua) echo "";else echo traduz("bairro",$con,$cook_idioma);?></td>
		<td><? echo traduz("cep",$con,$cook_idioma);?></td>
		<td><? echo traduz("cidade",$con,$cook_idioma);?></td>
		<td><? echo traduz("estado",$con,$cook_idioma);?></td>
	</tr>
	<tr >
		<? //HD 24581
		if ($login_fabrica==30){
			echo "<td colspan='2' class='borda' style='width:100%'>$bairro</td>";
			echo "<td class='borda'>$cep</td>";
		}else{ ?>
			<td colspan="2"><input style='width:100%' type="text" name="bairro" size="40" maxlength="20" value="<? echo $bairro ?>"></td>
			<td>
				<input type="text" name="cep" size="8" maxlength="8" style='width:100%' value="<? echo $cep ?>" <? if($login_fabrica==50) echo "onblur=\"checarNumero(this);\"";?>>
			</td>
		<? } ?>
		<td><input type="text" name="cidade" style='width:100%' value="<? echo $cidade ?>" disabled=""></td>
		<td width="30px"><input type="text" name="cidade" style='width:100%' value="<? echo $estado ?>" disabled=""></td>
	</tr>
</table>



<table class="formulario" width='700' align='center' border='0' cellpadding="3" cellspacing="1">
	<tr class='subtitulo'>
		<td width='50%'><? echo traduz("nome.fantasia",$con,$cook_idioma);?></td>
		<td><? echo traduz("email",$con,$cook_idioma);?></td>
		<td width="30px"><? echo traduz("capital.interior",$con,$cook_idioma);?></td>
	</tr>
	<tr >
		<td class='borda'>
			<input type="text" name="nome_fantasia" style='width:100%' size="40" maxlength="30" value="<? echo $nome_fantasia ?>" >
		</td>
		<td class='borda'>
			<input type="text" name="email" style='width:100%' size="30" maxlength="50" value="<? echo $email ?>">
		</td>
		<td class='borda'>
			<? //HD 24581 
			if ($login_fabrica==30){
				echo "$capital_interior";
			}else{ ?>
				<select name='capital_interior' size='1' style='width:100%'>
					<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> ><? echo traduz("capital",$con,$cook_idioma);?></option>
					<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> ><? echo traduz("interior",$con,$cook_idioma);?></option>
				</select>
			<? } ?>
		</td>
	</tr>
	<tr class='subtitulo'>
		<td colspan="3"><? fecho("observacoes",$con,$cook_idioma);?></td>
	</tr>
	<tr>
		<td colspan="3"><textarea style="width:100%" disabled=""><? echo $obs ?></textarea></td>
	</tr>
</table>

<p>
<!-- ---------------------------  Informações Bancárias ------------------------- -->
<? if($login_fabrica == 81 or $login_fabrica == 40 or $login_fabrica == 90){ ?>
	<table class="formulario" width='700' align='center' border='0' cellpadding="3" cellspacing="1">
		<tr><td colspan='4' class='titulo_tabela'>Informações Bancarias</td></tr>
		<tr class='subtitulo'>
			<td width = '33%'>CNPJ Empresa</td>
			<td colspan=3>Nome da Empresa <font size='1'><b>(somente conta jurídica)</b></font></td>
		</tr>
		<tr>
			<td width = '33%'>
			<input type="text" name="cpf_conta" size="14" maxlength="19" onkeypress='return isNumberKey(event)' value="<? echo $cpf_conta ?>"
			<?php
			if (strlen($cpf_conta)>0){
				echo $readonly;
			}
			?>></td>
			<td colspan=3>
			<input type="text" name="favorecido_conta" size="60" maxlength="50" value="<? echo $favorecido_conta ?>"
			<?php
			if (strlen($favorecido_conta)>0){
				echo $readonly;
			}
			?>></td>
		</tr>

		<tr class="subtitulo">
			<td colspan='4' width = '100%'>Banco</td>
		</tr>


		<tr >
			<td colspan='4' class='borda'>
				<?php
				if (strlen($banco) > 0) {
					//HD 344430: o banco deve ser recuperado e gravado sempre pelo campo tbl_banco.codigo
					$sql_banco = "SELECT codigo,
								nome, banco
							FROM tbl_banco
							WHERE codigo = '$banco'";
					$rs_banco = pg_exec($con, $sql_banco);
					$banco_nome = pg_result($rs_banco,0,codigo) . ' - ' . pg_result($rs_banco,0,nome);
				} else {
					$banco      = '';
					$banco_nome = '';
				}?>
				<input id="banco_nome" name="banco_nome" type="text" size="90" class="Caixa" maxlength="20" title="Digite o nome/código do banco."  value="<? echo $banco_nome ?>">
				<input id="banco" name="banco" type="hidden" value="<?=$banco?>" />

<!--				<?
				$sqlB =	"SELECT codigo, nome
						FROM tbl_banco
						ORDER BY nome";
				$resB = pg_exec($con,$sqlB);
				if (pg_numrows($resB) > 0) {
					echo "<select name='banco' size='1'";
					if (isset($readonly) and strlen($banco)>0){ // HD 85519
						echo " onfocus='defaultValue=this.value' onchange='this.value=defaultValue' ";
					}
					echo ">";
					echo "<option value=''></option>";
					for ($x = 0 ; $x < pg_numrows($resB) ; $x++) {
						$aux_banco     = pg_result($resB,$x,codigo);
						$aux_banconome = pg_result($resB,$x,nome);
						echo "<option value='" . $aux_banco . "'";
						if ($banco == $aux_banco) echo " selected";
						echo ">" . $aux_banco . " - " . $aux_banconome . "</option>";
					}
					echo "</select>";
				}
				?>
-->
			</td>
		</tr>
		<tr class="subtitulo">
			<td width = '33%'>Tipo de Conta</td>
			<td width = '33%'>Agência</td>
			<td width = '34%'>Conta</td>
			<? if($login_fabrica == 45 ){?>
			<td width = '34%'>Operação</td>
			<?}?>
		</tr>
		<tr>
			<td width = '33%' class='borda'>
				<select name='tipo_conta'
				<?php
					if (isset($readonly) and strlen($tipo_conta)>0){
						echo " DISABLED";
					} ?>>
					<option selected></option>
					<option value='Conta conjunta'   <? if ($tipo_conta == 'Conta conjunta')   echo "selected"; ?>>Conta conjunta</option>
					<option value='Conta corrente'   <? if ($tipo_conta == 'Conta corrente')   echo "selected"; ?>>Conta corrente</option>
					<option value='Conta individual' <? if ($tipo_conta == 'Conta individual') echo "selected"; ?>>Conta individual</option>
					<option value='Conta jurídica'   <? if ($tipo_conta == 'Conta jurídica')   echo "selected"; ?>>Conta jurídica</option>
					<option value='Conta poupança'   <? if ($tipo_conta == 'Conta poupança')   echo "selected"; ?>>Conta poupança</option>
				</select>
			</td>
			<td width = '33%' class='borda'>
			<input type="text" name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>"
			<?php
			if (strlen($agencia)>0){
				echo $readonly;
			}
			?>></td>
			<td width = '34%' class='borda'>
			<input type="text" name="conta" size="15" maxlength="15" value="<? echo $conta ?>"
			<?php
			if (strlen($conta)>0){
				echo $readonly;
			}
			?>></td>
		</tr>
		<tr class="subtitulo">
			<td colspan="4">Observações</td>
		</tr>
		<tr>
			<td colspan="4" class='borda'>
				<textarea name="obs_conta" cols="83" rows="2"
				<?php
				if (strlen($obs_conta)>0){
					echo $readonly;
				}?>><? echo $obs_conta; ?></textarea>
			</td>
		</tr>
	</table>
<?}?>
<!-- ---------------------------  Cobranca ------------------------- -->

<br />

<table class="formulario" width='700' align='center' border='0' cellpadding="3" cellspacing="1">
	<tr>
		<td colspan='4' class="titulo_tabela" style="text-transform:none;">
				Informações <?=strtolower("para")?> Cobrança
		</td>
	</tr>
	<tr class='subtitulo' >
		<td colspan="2" width="50%"><? echo traduz("endereco",$con,$cook_idioma);?></td>
		<td><? echo traduz("numero",$con,$cook_idioma);?></td>
		<td><? echo traduz("complemento",$con,$cook_idioma);?></td>
	</tr>
	<tr>
		<td colspan="2"><input type="text" value="<? echo $cobranca_endereco ?>" disabled="" style="width:100%"></td>
		<td><input type="text" value="<? echo $cobranca_numero ?>" style="width:100%" disabled=""></td>
		<td><input type="text" value="<? echo $cobranca_complemento ?>" style="width:100%" disabled=""></td>
	</tr>
	<tr class='subtitulo'>
		<td><? echo traduz("bairro",$con,$cook_idioma);?></td>
		<td><? echo traduz("cep",$con,$cook_idioma);?></td>
		<td><? echo traduz("cidade",$con,$cook_idioma);?></td>
		<td><? echo traduz("estado",$con,$cook_idioma);?></td>
	</tr>
	<tr >
		<td class='borda'><input type="text" value="<? echo $cobranca_bairro ?>" style="width:100%" disabled=""></td>
		<td class='borda'><input type="text" value="<? echo $cobranca_cep ?>" style="width:100%" disabled=""></td>
		<td class='borda'><input type="text" value="<? echo $cobranca_cidade ?>" style="width:100%" disabled=""></td>
		<td class='borda'><input type="text" value="<? echo $cobranca_estado ?>" style="width:100%" disabled=""></td>
	</tr>
</table>

<br>

<? if ($login_fabrica == 24) { 

?>

<table class="formulario" width='700' align='center' border='0' cellpadding="3" cellspacing="1">
	<tr>
		<td colspan='4' class="menu_top" align='center'>
			<font color='#36425C'><? echo "Linhas";?></td>
	</tr>
	<tr>
		<td>Linha</td>
		<td>Atende</td>
	</tr>
<?
	$sql = "SELECT  tbl_linha.linha,
						tbl_linha.nome
				FROM	tbl_linha
				WHERE	tbl_linha.fabrica = $login_fabrica ";
		$res = pg_query ($con,$sql);
	
	if (pg_num_rows($res)>0) {
		for ($i=0;$i<pg_num_rows($res);$i++) {
			$linha = pg_result($res,$i,linha);
			$nome = pg_result($res,$i,nome);

			$sqlX = "SELECT * FROM tbl_posto_linha WHERE posto = $login_posto AND linha = $linha";

			$resX = pg_query ($con,$sqlX);

			if (pg_num_rows ($resX) == 1) {
				$check        = " CHECKED ";
			}

			echo "<tr>
				<td class='borda'>$nome</td>
				<td class='borda'><input type='checkbox' value='$linha' name='atende_$linha' $check></td>
			</tr>";
			$check = '';
		}
	}
?>
</table>
<? }?>
<!-- // senha -->
<table class="formulario" width='700' align='center' border='0' cellpadding="3" cellspacing="1 " >
	<tr class="titulo_tabela">
		<td colspan="2" height='20' align="center" style="text-transform:none;">
		Digite a Senha Somente se for Alterá-la</td>
	</tr>
	<tr>
		<td width='50%' align="right" style='text-transform:capitalize;'><? echo traduz("nova senha",$con,$cook_idioma);?></td>
		<td><input type="password" class='frm' name="senha" size="10" maxlength="10" value=""></td>
	</tr>
	<br>
	<tr align="left">
		<?
		echo "<td align='right' width='50%' style='text-transform:capitalize;' >";
		echo traduz("repita a nova senha",$con,$cook_idioma);
		echo "</td>"
		?>
		<td>
			<input type="password" class='frm' name="senha2" size="10" maxlength="10" value="">
		</td>
	</tr>
</table>

<br />

<!-- ============================ Botoes de Acao ========================= -->
<center>
<? if ($sistema_lingua == 'ES') { ?>
<INPUT TYPE="hidden" name="btn_acao" value="">
<input type="button" value="Gravar" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="GUARDAR FORMULARIOS" >
<? }else{ ?>
<INPUT TYPE="hidden" name="btn_acao" value="">
<input type="button" value="Gravar" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário">
<?}?>
</center>

</form>

<?
	//hd chamado - 3505
	//hd chamado - 18385
	if ($login_fabrica==11 or $login_fabrica==1 or $login_fabrica==50) 
		echo "  <script language='javascript'>
					var formi = document.frm_posto;
					for (i=0;i<formi.length;i++) {
						if (formi.elements[i].type == 'text' || formi.elements[i].type == 'select-one')
							formi.elements[i].disabled = true;
					}
				</script>";
?>


<p>

<? include "rodape.php"; ?>
