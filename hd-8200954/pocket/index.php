<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (strlen($_POST["btnAcao"]) > 0) {
	$btnAcao = trim($_POST["btnAcao"]);
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

if (trim($HTTP_POST_VARS["btnAcao"]) == "Enviar") {
	$login = trim($HTTP_POST_VARS["login"]);
	$senha = trim($HTTP_POST_VARS["senha"]);
	
	if (strlen($login) == 0) {
		$msg = "Informe seu CNPJ ou Login !!!";
	}else{
		if (strlen($senha) == 0) {
			$msg = "Informe sua senha !!!";
		}
	}
	
	if (strlen($msg) == 0) {
		$xlogin = str_replace(".","",$login);
		$xlogin = str_replace("/","",$xlogin);
		$xlogin = str_replace("-","",$xlogin);
		$xlogin = strtolower ($xlogin);
		
		$xsenha = strtolower($senha);


		#------------- Pesquisa posto pelo CNPJ ---------------#
 		$sql = "SELECT  tbl_posto_fabrica.oid
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  tbl_posto.cnpj          = '$xlogin'
				AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
		setcookie ("cook_posto_fabrica",pg_result ($res,0,oid));
			#setcookie ("cook_admin");
			$cook_login = pg_result ($res,0,oid);
			header ("Location: login.php");
			exit;
		} 
		
		
		#------------- Pesquisa posto pelo Login ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
				AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 1) {
			setcookie ("cook_posto_fabrica",pg_result ($res,0,oid));
			#setcookie ("cook_admin");
			$cook_login = pg_result ($res,0,oid);
			header ("Location: login.php");
			exit;
		}

		#------------------- Pesquisa acesso ADMIN ------------------
		$sql = "SELECT  tbl_admin.oid    ,
						tbl_admin.admin  ,
						tbl_admin.fabrica,
						tbl_admin.login  ,
						tbl_admin.senha  ,
						tbl_admin.privilegios
						FROM tbl_admin
				WHERE  lower (tbl_admin.login) = lower ('$xlogin')
				AND    lower (tbl_admin.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 1) {
			setcookie ("cook_admin",pg_result ($res,0,oid));
			setcookie ("cook_posto_fabrica");
			$cook_login  = pg_result ($res,0,oid);
			
			$privilegios = pg_result ($res,0,privilegios);

			$acesso = explode(",",$privilegios);

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

		$msg = "Login ou senha inválidos !!!";
		setcookie ("cook_posto_fabrica");
		setcookie ("cook_admin");
	}
}

$title = "Assistência Técnica - Login";

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<HTML>

<HEAD>

	<TITLE> Telecontrol NewLogin </TITLE>
		<META NAME="Generator" CONTENT="EditPlus">
		<META NAME="Author" CONTENT="Telecontrol <c>2004">
		<META NAME="Keywords" CONTENT="assistência técnica, website, sistemas, design, elétrica, eletricidade, eletrônica, manutenção">
		<META NAME="Description" CONTENT="Sistema para gerenciamento de Ordens de Serviço para fabricantes de equipamentos eletro-eletrônicos">
		<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1" />
		<META NAME="copyright" CONTENT="Telecontrol Networking Ltd" />


</HEAD>

<BODY>


<table width='100%' height='30' border='0' cellpadding='0' cellspacing='0' bgcolor='#000000'>
<tr>
	<td align='center'><font color='ffcc00' face='arial' size='2' align='center'><b>Telecontrol Networking</b></font></td>
<tr>

</table>
<hr>

<table cellpadding='0' cellspacing='0' align='center'>
<tr>
<td>


<!-- ====================== TITULO DA PÁGINA ============================= -->



	<!-- NOVO ACESSO -->
	<div id="rightCol">

		<div class="contentBlockRight">
			<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_login" method="post" action="<? $PHP_SELF ?>">
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>Login</b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="text" name="login" maxlength="20" value="<? echo $login ?>"></b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>Senha</b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="password" name="senha" maxlength="10"></b></font><br><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input type="submit" name="btnAcao" value="Enviar"></b></font>
				<hr><font face="Verdana, Arial, Helvetica, sans-serif" size="1"><? if (strlen($msg) > 0) { ?><b><font color="#FF0000"><? echo $msg ?></font></b></font><? } ?>
			</form>
		</div>
</td>

</table>

<!-- ========================== RODAPÉ ============================== -->
	</div>
</form>

</BODY>

</HTML>

