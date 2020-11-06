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
	
	setcookie ("cook_posto_fabrica");
	setcookie ("cook_posto");
	setcookie ("cook_fabrica");
	setcookie ("cook_login_posto");
	setcookie ("cook_login_nome");
	setcookie ("cook_login_cnpj");
	setcookie ("cook_login_fabrica");
	setcookie ("cook_login_fabrica_nome");
	setcookie ("cook_login_pede_peca_garantia");
	setcookie ("cook_login_tipo_posto");
	setcookie ("cook_login_e_distribuidor");
	setcookie ("cook_login_distribuidor");
	setcookie ("cook_pedido_via_distribuidor");

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
		$sql = "SELECT  tbl_posto_fabrica.oid , tbl_posto_fabrica.posto, tbl_posto_fabrica.fabrica , tbl_posto_fabrica.credenciamento
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  tbl_posto.cnpj          = '$xlogin'
				AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 1) {
			if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			}else{
				setcookie ("cook_posto_fabrica",pg_result ($res,0,oid));
				setcookie ("cook_posto",pg_result ($res,0,posto));
				setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				header ("Location: login.php");
				exit;
			}
		}
		
		
		#------------- Pesquisa posto pelo Login ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid , tbl_posto_fabrica.posto, tbl_posto_fabrica.fabrica, tbl_posto_fabrica.credenciamento
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin')
				AND    lower (tbl_posto_fabrica.senha) = lower ('$senha')";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows ($res) == 1) {
			if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			}else{
				setcookie ("cook_posto_fabrica",pg_result ($res,0,oid));
				setcookie ("cook_posto",pg_result ($res,0,posto));
				setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
				header ("Location: login.php");
				exit;
			}
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
			
			setcookie ("cook_admin",pg_result ($res,0,admin));
			setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
			setcookie ("cook_posto_fabrica");
			setcookie ("cook_posto");
			
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

		if (strlen ($msg) == 0) {
			$msg = "<!--OFFLINE-I-->Login ou senha inválidos !!!<!--OFFLINE-F-->";
		}
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
		<META NAME="Author" CONTENT="Marcos Teruo Ouchi - Telecontrol <c>2004">
		<META NAME="Keywords" CONTENT="assistência técnica, website, sistemas, design, elétrica, eletricidade, eletrônica, manutenção">
		<META NAME="Description" CONTENT="Sistema para gerenciamento de Ordens de Serviço para fabricantes de equipamentos eletro-eletrônicos">
		<META NAME="copyright" CONTENT="Message Digital Design Ltd" />
		<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=iso-8859-1" />
		<META NAME="copyright" CONTENT="Telecontrol Networking Ltd" />

	<!-- LINK PARA O CSS -->
	<link href="css/basico.css" rel="stylesheet" type="text/css" />
	<link type="text/css" rel="stylesheet" href="css/x_basico.css">

</HEAD>

<BODY onload='javascript: frm_login.login.focus();'>
<table cellpadding='0' cellspacing='0' align='center'>
<tr>
<td>
<!-- ========================== CABECALHO ================================ -->
<? include 'x_cabecalho.php' ?>

<!-- ====================== TITULO DA PÁGINA ============================= -->
<table width="100%" cellpadding='0' cellspacing='0' bgcolor='#FFFFFF'  align='center'>
<tr>
	<td width='100%' align='center'><img src="x_imagens/assist_cabecalho.gif" alt=""></td><!-- 283x44px -->
	<td><img src="x_imagens/idx_imagem_2.jpg" alt=""></td><!-- 375x55px -->
</tr>
</table>


	<DIV id="wrapper">
<!-- 		<a href="index2.php" accesskey="1"><img src="image/logo_telecontrol.gif" id="logo" alt="Vai para a página Principal" /></a><br> -->

		<div id="topNav" class="clear">
			<!-- insira aqui a barra de navegação -->
		</div>
<!-- 		<div>
			<div id="mainBranding">
				<div class="inline"><img src="image/imagem_principal_eletro.jpg" alt="Message team posing against blue sky" width="505" height="150" />
			</div>
		</div> -->



	</div>

	<div id="leftCol">

		<div class="contentBlockLeft">
			<!-- Insira aqui o texto de sua escolha -->
			<img src='imagens/conf_01.gif'><img src='imagens/conf_02.gif' onclick="window.open('configuracao.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')" style='cursor: pointer;'><img src='imagens/conf_03.gif' onclick="window.open('configuracao_ns.php','janela','toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=400,top=18,left=0')" style='cursor: pointer;'>
			<h3>Clique no logotipo de seu navegador para saber mais sobre as configurações necessárias.</h3>
		</div>
	</div>

	<div id="middleCol">
		<div class="contentBlockMiddle">
			<CENTER><h1>A V I S O &nbsp;&nbsp; I M P O R T A N T E</h1></CENTER>
			<div align='justify'>
			<font face='verdana, arial' size='2' color='#ff0000'>
				O programa Telecontrol Assist <B>"offline"</B> está em <B>fase final de teste.</B><br>
				Os lançamentos não estão sendo considerados válidos.<br>
				Portanto, é necessário efetuar os lançamentos no sistema <B>"online"</B>.
			</font>
			</div>
		</div>

		<div class="contentBlockMiddle">
			<!-- Insira aqui o texto de sua escolha -->
			<IMG SRC="image/tit_md_assistencia_tecnica.gif" ALT="">
			<h3>Aqui os Postos Autorizados podem efetuar o lançamento de Ordens de Serviço em garantia, conferir seu extrato financeiro, visualizar e imprimir vistas explodidas, contatar a empresa através do Fale Conosco, ficar a par de lançamentos de produtos e promoções entre outros recursos de grande utilidade para agilizar todo o processo de controle de Ordens de Serviço.</h3>
		</div>
		<div class="contentBlockMiddle">

			<!-- Insira aqui o texto de sua escolha -->
			<a href="http://www.telecontrol.com.br"><img src="image/parceiro.jpg" alt=""></a>
			<h3>A Telecontrol desenvolve sistemas totalmente destinados à Internet, com isto você tem acesso às informações de sua empresa de qualquer lugar, podendo tomar decisões gerenciais com total segurança. </h3>


		</div>
	</div>

	<!-- NOVO ACESSO -->
	<div id="rightCol">

		<div class="contentBlockRight">
			<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_login" method="post" action="<? $PHP_SELF ?>">
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>Login</b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="text" name="login" maxlength="20" value="<? echo $login ?>"></b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b>Senha</b></font><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input class="frm" type="password" name="senha" value="" maxlength="10"></b></font><br><br>
				<font face="Verdana, Arial, Helvetica, sans-serif" size="1"><b><input type="submit" name="btnAcao" value="Enviar"></b></font>
				<hr><font face="Verdana, Arial, Helvetica, sans-serif" size="1"><? if (strlen($msg) > 0) { ?><b><font color="#FF0000"><? echo $msg ?></font></b></font><? } ?>
			</form>
		</div>

		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_cnpj" method="post" action='<? echo $PHP_SELF?>'>
			<div class="contentBlockRight">
				<!-- Insira aqui o texto de sua escolha -->
				<a href="#"><img src="imagens/cadastre.gif" alt=""></a>
				<h3>Para obter seu login e criar uma senha de acesso, digite seu CNPJ. <br />Não se esqueça de desabilitar qualquer tipo de ANTI-POP-UP que você tiver. <br />&nbsp;<br /><center><b><input class="frm" type="text" name="cnpj" maxlength="20" value="<? echo $cnpj ?>"><input type="submit" name="btnAcao" value="OK">
				<? if (strlen($msg_erro) > 0) { ?><b><font color="#FF0000"><? echo $msg_erro ?></font></b></font><? } ?>
				</b></center><br />ex.: 01.297.216/0001-11</h3>
			</div>
		</form>




	</div>
</td>
</tr>
</table>

	
<!-- ========================== RODAPÉ ============================== -->
<? include 'x_rodape.php' ?>
	</div>
</form>

</BODY>

</HTML>
<?
if ($_GET['s'] == 1){
echo "<script> alert('Seus dados de acesso foram enviados para seu e-Mail');</script>";
}
?>
