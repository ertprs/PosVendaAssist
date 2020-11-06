<?
echo $REMOTE_ADDR;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


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
		$sql = "SELECT  tbl_admin.oid, tbl_admin.* FROM tbl_admin
				WHERE  lower (tbl_admin.login) = lower ('$xlogin')
				AND    lower (tbl_admin.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 1) {
			setcookie ("cook_admin",pg_result ($res,0,oid));
			setcookie ("cook_posto_fabrica");
			$cook_login = pg_result ($res,0,oid);
			header ("Location: admin/menu_cadastro.php");
			exit;
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
		<META NAME="Author" CONTENT="Telecontrol networking Ltda.">
		<META NAME="Keywords" CONTENT="assistência técnica, website, sistemas, design, elétrica, eletricidade, eletrônica, manutenção">
		<META NAME="Description" CONTENT="Sistema para gerenciamento de Ordens de Serviço para fabricantes de equipamentos eletro-eletrônicos">
		<META NAME="copyright" CONTENT="Message Digital Design Ltd" />
		<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1" />
		<META NAME="copyright" CONTENT="Telecontrol Networking Ltd" />

	<!-- LINK PARA O CSS -->
	<link href="css/basico.css" rel="stylesheet" type="text/css" />
</HEAD>

<BODY id="homePage">

<DIV id="wrapper">
	<a href="index.php" accesskey="1"><img src="image/logo_telecontrol.gif" id="logo" alt="Vai para a página Principal" /></a><br>

	<div id="topNav" class="clear">
		<!-- insira aqui a barra de navegação -->
	</div>
	<div>
		<div id="mainBranding">
			<div class="inline"><img src="image/imagem_principal_eletro.jpg" alt="Message team posing against blue sky" width="505" height="150" />
		</div>
	</div>

		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_login" method="post" action="<? $PHP_SELF ?>">
			<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>CNPJ ou Login</b></font><br>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="text" name="login" maxlength="20" value="<? echo $login ?>"></b></font><br>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>Senha</b></font><br>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="password" name="senha" maxlength="10"></b></font><br><br>
			<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input type="submit" name="btnAcao" value="Enviar"></b></font>
			<hr><font face="Verdana, Arial, Helvetica, sans-serif" size="1"><? if (strlen($msg) > 0) { ?><b><font color="#FF0000"><? echo $msg ?></font></b></font><? } ?>
		</form>

</div>

<div id="leftCol">
	<div class="contentBlockLeft">
		<!-- Insira aqui o texto de sua escolha -->
		<h4>&nbsp;</h4>
	</div>
	<div class="contentBlockLeft">
		<!-- Insira aqui o texto de sua escolha -->

	</div>
</div>

<div id="middleCol">

	<div class="contentBlockMiddle">
		<!-- Insira aqui o texto de sua escolha -->
		<a href="#"><IMG SRC="image/tit_md_assistencia_tecnica.gif" ALT=""></a>
		<h3>Aqui os Postos Autorizados podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.</h3>
	</div>
	<div class="contentBlockMiddle">
		<!-- Insira aqui o texto de sua escolha -->
		<a href="mailto:sac@telecontrol.com.br"><IMG SRC="image/img_sac.gif" ALT=""></a>
		<h3>Tem alguma dúvida sobre o sistema? Quer alguma informação técnica? 
		<a href="mailto:sac@telecontrol.com.br">Escreva-nos.</a> Estamos dispostos a esclarecer todas as suas dúvidas.</h3>

	</div>
</div>

<div id="rightCol">
	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
		<a href="http://www.telecontrol.com.br"><img src="image/parceiro.jpg" alt=""></a>
		<h3>A Telecontrol desenvolve sistemas totalmente destinados à Internet, com isto você tem acesso às informações de sua empresa de qualquer lugar, podendo tomar decisões gerenciais com total segurança. 
</h3>
	</div>
	<div class="contentBlockRight">
		<!-- Insira aqui o texto de sua escolha -->
	</div>
</div>

<div id="footer">
	<div id="copyright"><h3>Copyright &copy; 2004</h3></div>
		<ul id="bottomNav">
			<li class="first"><a  href="#">www.telecontrol.com.br</a></li>
<!-- 			<li><a href="#" accesskey=""></a></li>
			<li><a href="#" accesskey="">3</a></li>
			<li><a href="#" accesskey="">4</a></li>
 -->
		</ul>
	</div>
</DIV>
</div>
</BODY>

</HTML>