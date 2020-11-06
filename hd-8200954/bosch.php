<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);


if($_GET["l"]=='es'){
	setcookie("lingua","es");
	header ("Location: $PHP_SELF");
}
elseif($_GET["l"]=='br'){
	setcookie("lingua","br");
	header ("Location: $PHP_SELF");
}
elseif($_GET["l"]=='in'){
	setcookie("lingua","in");
	header ("Location: $PHP_SELF");
}
if(strlen($_COOKIE["lingua"])==0){
	setcookie("lingua","br");
	header ("Location: $PHP_SELF");

}


$cook_lingua = $_COOKIE["lingua"];

if($cook_lingua == 'es'){
	$lingua_area_restrita            = "AREA RESTRICTA";
	$lingua_login                    = "Login";
	$lingua_senha                    = "Clave";
	$lingua_primeiro_acesso          = "Primero aceso";
	$lingua_primeiro_acesso_mensagem = "Para obtener y crear uma clave de acceso, digite su Identificación";
	$lingua_mensagem_telecontrol     = "<b><center>ASSIST TELECONTROL</center></b><p>Bienvenido al sistema online de Asistencia Técnica de <font color='#003399'>Bosch</font>.<br>Para acceder digite su login y clave al lado y haga un clic en OK.<p>";

	$lingua_erro1 = "Informe su Identificación y login";
	$lingua_erro2 = "Informe su clave";

}
if($cook_lingua == 'br'){
	$lingua_area_restrita            = "AREA RESTRITA";
	$lingua_login                    = "Login";
	$lingua_senha                    = "Senha";
	$lingua_primeiro_acesso          = "Primeiro Acesso";
	$lingua_primeiro_acesso_mensagem = "Para obter seu login e criar uma senha de acesso, digite seu CNPJ.";
	$lingua_mensagem_telecontrol     = "<b><center>ASSIST TELECONTROL</center></b><p>Seja bem-vindo ao sistema online de Assistência Técnica da <font color='#003399'>Bosch</font>.<br> Para poder acessar basta digitar seu login e sua senha ao lado e clicar em OK.<p>";

	$lingua_erro1 = "Informe seu CNPJ ou Login!";
	$lingua_erro2 = "Informe sua senha";
}
if($cook_lingua == 'in'){
	$lingua_area_restrita            = "RESTRICT AREA";
	$lingua_login                    = "Login";
	$lingua_senha                    = "Password";
	$lingua_primeiro_acesso          = "First Access";
	$lingua_primeiro_acesso_mensagem = "To get a login and  password, inform your ID";
	$lingua_mensagem_telecontrol     = "<b><center>ASSIST TELECONTROL</center></b><p>Welcome to <font color='#003399'>Bosch Service</font> online system.<br> To access please inform login and password <p>";

	$lingua_erro1 = "Inform your ID or Login";
	$lingua_erro2 = "Inform your password";
}



if (strlen($_POST["btnAcao"]) > 0) {
	$btnAcao = trim($_POST["btnAcao"]);
}

if (strlen($_POST["id"]) > 0) {
	$id = trim($_POST["id"]);
}
if (strlen($_POST["id2"]) > 0) {
	$id2 = trim($_POST["id2"]);
}
if (strlen($_POST["key1"]) > 0) {
	$key1 = trim($_POST["key1"]);
}
if (strlen($_POST["key2"]) > 0) {
	$key2 = trim($_POST["key2"]);
}
if($key1 == md5($id) AND $key2 == md5($id2)){
	if(strlen($id)>0 AND strlen($id2)>0 AND strlen($key1)>0 AND strlen($key2)>0 ){

		$sql = "SELECT tbl_admin.admin,hd_chamado,login,senha
				FROM tbl_hd_chamado 
				JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
				WHERE hd_chamado     = $id
				AND  tbl_admin.admin = $id2
				AND  status          = 'Resolvido'
				AND  resolvido IS NULL";

		$res = pg_exec ($con,$sql);
			
		if (pg_numrows ($res) == 1) {
			$hd_chamado = pg_result ($res,0,hd_chamado);
			$admin      = pg_result ($res,0,admin);
			$hd_login   = pg_result ($res,0,login);
			$hd_senha   = pg_result ($res,0,senha);
			$hd = "OK";
			
		}
	}
}

if (trim($_POST["btnAcao"]) == "OK") {
	
	$cnpj = trim($_POST["cnpj"]);

	if (strlen($_POST["cnpj"]) > 0) {
		$aux_cnpj = trim($_POST["cnpj"]);
		$aux_cnpj = str_replace(".","",$aux_cnpj);
		$aux_cnpj = str_replace("/","",$aux_cnpj);
		$aux_cnpj = str_replace("-","",$aux_cnpj);
		$aux_cnpj = str_replace(" ","",$aux_cnpj);
		header("Location: cadastra_senha.php?cnpj=$aux_cnpj");
		exit;
	}else{
		$msg_erro = "Digite seu CNPJ.";
	}
	
}

if (trim($HTTP_POST_VARS["btnAcao"]) == "Enviar"  OR $hd=="OK") {
	$login = trim($HTTP_POST_VARS["login"]);
	$senha = trim($HTTP_POST_VARS["senha"]);
	$msg='';
	if($hd=='OK'){
		$login = $hd_login   ;
		$senha = $hd_senha   ;
	}

	
	// setcookie ("cook_posto_fabrica");
	// setcookie ("cook_posto");
	// setcookie ("cook_fabrica");
	// setcookie ("cook_login_posto");
	// setcookie ("cook_login_nome");
	// setcookie ("cook_login_cnpj");
	// setcookie ("cook_login_fabrica");
	// setcookie ("cook_login_fabrica_nome");
	// setcookie ("cook_login_pede_peca_garantia");
	// setcookie ("cook_login_tipo_posto");
	// setcookie ("cook_login_e_distribuidor");
	// setcookie ("cook_login_distribuidor");
	// setcookie ("cook_pedido_via_distribuidor");

	/* HD 20640 - Controle Para quando perder o LOGIN, ser redirecionado para a página da Bosch */
	setcookie("cook_bosch", 'bosch', time()+60*60*24*2);  /* expira em 2 dia */

	if (strlen($login) == 0) {
		$msg = $lingua_erro1;
	}else{
		if (strlen($senha) == 0) {
			$msg = $lingua_erro2;
		}
	}
	
	if (strlen($msg) == 0) {
		$xlogin = str_replace(".","",$login);
		$xlogin = str_replace("/","",$xlogin);
		$xlogin = str_replace("-","",$xlogin);
		$xlogin = strtolower ($xlogin);
		
		$xsenha = strtolower($senha);


		#------------- Pesquisa posto pelo Login ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica , tbl_posto_fabrica.posto, tbl_posto_fabrica.fabrica, tbl_posto_fabrica.credenciamento
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
				AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 1) {
			if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			}else{
				add_cookie($cookie_login,"cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
				add_cookie($cookie_login,"cook_posto",pg_result ($res,0,posto));
				add_cookie($cookie_login,"cook_fabrica",pg_result ($res,0,fabrica));				
				set_cookie_login($token_cookie,$cookie_login);

				// setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
				// setcookie ("cook_posto",pg_result ($res,0,posto));
				// setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				header ("Location: login.php");
				exit;
			}
		}

		#------------- Pesquisa posto pelo CNPJ ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica, 
						tbl_posto_fabrica.posto, 
						tbl_posto_fabrica.fabrica , 
						tbl_posto_fabrica.credenciamento
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
										AND tbl_posto_fabrica.fabrica = 11
				WHERE tbl_posto.cnpj                  = '$xlogin'
				AND   LOWER(tbl_posto_fabrica.senha) = LOWER('$senha')";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			}else{
				//Wellington - Trocar aqui por "if (pg_result($res,0,fabrica)==11)" no dia 04/01 após atualizar os códigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
				if ( pg_result($res,0,posto)<>6359 and pg_result($res,0,fabrica)<>11 ) {
					
					add_cookie($cookie_login,"cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
					add_cookie($cookie_login,"cook_posto",pg_result ($res,0,posto));
					add_cookie($cookie_login,"cook_fabrica",pg_result ($res,0,fabrica));
					set_cookie_login($token_cookie,$cookie_login);

					// setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
					// setcookie ("cook_posto",pg_result ($res,0,posto));
					// setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
					header ("Location: login.php");
					exit;
				} else {
					$sql = "SELECT codigo_posto
							FROM   tbl_posto_fabrica
							WHERE  posto   =". pg_result($res,0,posto)."
							AND    fabrica =". pg_result($res,0,fabrica);
					$res = pg_exec ($con,$sql);
					$novo_login = pg_result($res,0,0);
					$msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
				}
			}
		}

		
		#------------------- Pesquisa acesso ADMIN ------------------
		$sql = "SELECT  tbl_admin.admin       ,
						tbl_admin.fabrica     ,
						tbl_admin.login       ,
						tbl_admin.senha       ,
						tbl_admin.privilegios ,
						tbl_admin.pais
						FROM tbl_admin
				WHERE  lower (tbl_admin.login) = lower ('$xlogin')
				AND    lower (tbl_admin.senha) = lower ('$senha')
				AND    ativo IS TRUE";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 1) {
			if (strtolower('$xlogin') == "luis") {
				if (pg_result ($res,0,fabrica) == 6) {
					if (
						$_SERVER['REMOTE_ADDR'] <> '201.0.9.216'     AND
						$_SERVER['REMOTE_ADDR'] <> '200.247.64.130'  AND
						$_SERVER['REMOTE_ADDR'] <> '200.204.201.218' AND
						$_SERVER['REMOTE_ADDR'] <> '200.205.138.115'
					) {
					
					$ip = $_SERVER['REMOTE_ADDR'];
					echo "<h1>IP Invalido para ADMIN: $ip</h1>";
					exit;
					}
				}
			}
			
			$pais  = pg_result ($res,0,pais) ;
			$admin = pg_result ($res,0,admin);
			$ip    = $_SERVER['REMOTE_ADDR'] ;
			$sql2 = "UPDATE tbl_admin SET
					 ultimo_ip = '$ip' ,
					 ultimo_acesso = CURRENT_TIMESTAMP
				WHERE admin = $admin";

			$res2 = pg_exec($con,$sql2);
			
			if ($pais<>'BR'){
				add_cookie($cookie_login,"cook_admin_es",pg_result ($res,0,admin));	
			} else{
				add_cookie($cookie_login,"cook_admin",pg_result ($res,0,admin))   ;
				
			}
			
			
			add_cookie($cookie_login,"cook_fabrica",pg_result ($res,0,fabrica));
			add_cookie($cookie_login,"cook_posto_fabrica");
			add_cookie($cookie_login,"cook_posto");
			set_cookie_login($token_cookie,$cookie_login);

			// setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
			// setcookie ("cook_posto_fabrica");
			// setcookie ("cook_posto");

			
			$privilegios = pg_result ($res,0,privilegios);
			$acesso = explode(",",$privilegios);

			if($hd=='OK'){
				header("Location: helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado");
				exit;
			}

//--=== ADMINS AMÉRICA LATINA ========================RAPHAEL===============--\\
			if($pais<>'BR'){

				header("Location: admin_es/menu_gerencia.php");
				exit;
			}
//--========================================================================--\\

			for($i=0; $i < count($acesso); $i++){
				if(strlen($acesso[$i]) > 0){
					if ($acesso[$i] == "gerencia"){
						header("Location: admin/menu_gerencia.php");
					}elseif ($acesso[$i] == "call_center"){
						header("Location: admin/menu_callcenter.php");
					}elseif ($acesso[$i] == "cadastros"){
						header("Location: admin/menu_cadastro.php");
					}elseif ($acesso[$i] == "info_tecnica"){
						header("Location: admin/menu_tecnica.php");
					}elseif ($acesso[$i] == "financeiro"){
						header("Location: admin/menu_financeiro.php");
					}elseif ($acesso[$i] == "auditoria"){
						header("Location: admin/menu_auditoria.php");
					}elseif ($acesso[$i] == "*"){
						header("Location: admin/menu_cadastro.php");
					}
					exit;
				}
			}
		}

		if (strlen ($msg) == 0) {
			$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
		}
		// setcookie ("cook_posto_fabrica");
		// setcookie ("cook_admin");
	}
}

$title = "Seja bem vindo ao Sistema Assist Telecontrol - BOSCH ";

?>
<HTML>
<HEAD>
<TITLE><?=$title;?></TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1">
<style>
.Texto {
font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;
	color: #777777;
	text-decoration: none;
}
.msg_erro{
	color: #FF0000;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;

}
.rodape{
	color: #FFFFFF;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 9px;
	background-color: #FF9900;
	font-weight: bold;
}

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Titulo {
	font-family: Verdana;
	font-size: 10px;
	/*font-weight: bold;*/
	color:#333333;
}
img{ border:0px;
}
</style>
</HEAD>
<BODY BGCOLOR=#FFFFFF LEFTMARGIN=0 TOPMARGIN=0 MARGINWIDTH=0 MARGINHEIGHT=0>
<table width='100%' height='100%' border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td valign='top'  align='left' height='20' class='Titulo'>
			<img src='logos/top_bosch.jpg' align='top'><br>
			&nbsp;<a href='<?=$PHP_SELF?>?l=es'><img src='imagens/bandeira-espanha.gif'>Español</a>&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;<a href='<?=$PHP_SELF?>?l=br'><img src='imagens/bandeira-brasil.gif' >Português - BR</a>
			&nbsp;<a href='<?=$PHP_SELF?>?l=in'><img src='imagens/bandeira-eua.jpeg'>English</a>&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
		<td align='right'>
			<img src='logos/bosch_service.gif' align='absmiddle'>
		</td>
	</tr>
	<tr>
		<td align='center' valign='top' colspan='2'>
			<table><tr><td  width="160" align='center'>


				<form name="frm_login" method="post" action="<? $PHP_SELF ?>">
					<table border="0" cellpadding="0" cellspacing="0" width="157" height='122' background='imagens_login/login_fundo.gif' style='background-repeat:no-repeat'>
					<tr>
 						<td colspan='2' ><img src='imagens_login/login_abre.gif' width="157"></td>
					</tr>
					<tr>
						<td width="157"  colspan='2' class='Titulo' ><b><center><?=$lingua_area_restrita?></center> </b></td>
					</tr>
					<tr >
						<td style='padding-top:8px' class='Texto' align='right' width='50'><?=$lingua_login?>&nbsp;</td>
						<td style='padding-top:8px' width='107'><input name="login" type="text" class="Caixa" size="12"  value="<? echo $login ?>" ></td>
					</tr>
					<tr>
						<td style='padding-top:8px' class='Texto' align='right' width='50'><?=$lingua_senha?>&nbsp;</td>
						<td style='padding-top:8px' width='107'><input name="senha" type="password" class="Caixa" size="12"></td>
					</tr>
					<tr>
						<td colspan='2' align='right'><IMG SRC="imagens_login/btn_ok.gif"  name="btnAcao" value="Enviar" onclick='document.frm_login.submit(); ' style='cursor: hand;' alt='Clique aqui para logar!'>&nbsp;</td>
					</tr>
					<tr>
						<td colspan='2' style='padding:0 4px' class="msg_erro"><? if (strlen($msg) > 2)  echo "&nbsp;$msg"; else "&nbsp; <br> .a&nbsp;"; ?></td>
					</tr>
					<tr>
						<td colspan='2'><img src='imagens_login/login_fecha.gif' width="157"></td>
					</tr>
					</table>
					<INPUT TYPE="hidden" name="btnAcao" value="Enviar">
				</form>
	
				<form name="frm_cnpj" method="post" action='<?=$PHP_SELF?>'>
					<table border="0" cellpadding="0" cellspacing="0" width="157" height='122' background='imagens_login/login_fundo.gif' style='background-repeat:no-repeat'>
					<tr>
						<td><img src='imagens_login/login_abre.gif' width="157"></td>
					</tr>
					<tr>
						<td width="157" class='Titulo'><b><center><?=$lingua_primeiro_acesso?></center> </b></td>
					</tr>
					<tr >
						<td class='Texto' align='justify' width="155" style='padding:0 5px'><?=$lingua_primeiro_acesso_mensagem?></td>
					</tr>
					<tr>
						<td  class='Texto' align='right' >
							<input type="text" name="cnpj" maxlength="20" size='15' value="<? echo $cnpj ?>" class='Caixa'>
							<input type="hidden" name="btnAcao" value="OK"><IMG SRC="imagens_login/btn_ok.gif"  name="btnAcao" value="Enviar" onclick='document.frm_cnpj.submit(); ' style='cursor: hand;' 	alt='Clique aqui para logar!' align='absmiddle'>&nbsp;
						</td>
					</tr>
					<tr>
						<td align='right' style='padding:0 5px' class="msg_erro"><? if (strlen($msg_erro) > 0)  echo $msg_erro; else "&nbsp;<br>&nbsp;";?></td>
					</tr>
					<tr>
						<td ><img src='imagens_login/login_fecha.gif' width="157"></td>
					</tr>
				</table>
				</form>
			</td>
			
				
			<td width="95%"  valign='top' class='Titulo'><br>
				<table style=' border: #DDDDDD 1px solid; background-color: #FDFDFD ' width='280'><tr><td class='Titulo' align='justify'><?=$lingua_mensagem_telecontrol?></td></tr></table>
			<br>
			</td>
		</tr></table>
		</td> 
	</tr>

	<tr>
		<td colspan='2' height='5' class='rodape' background='admin/imagens_admin/laranja.gif'>Telecontrol Networking Ltda - <? echo date("Y"); ?> - www.telecontrol.com.br - Deus é o Provedor</td>
	</tr>
</table>
</BODY>
</HTML>