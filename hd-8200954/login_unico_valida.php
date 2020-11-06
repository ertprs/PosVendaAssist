<?php
include './dbconfig.php';
include './includes/dbconnect-inc.php';
include ('./helpdesk/mlg_funciones.php');
include 'token_cookie.php';

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

if (!function_exists('ttext')) {
	include 'trad_site/fn_ttext.php';
}

$login       = trim($_POST["login"]);
$senha       = trim($_POST["senha"]);
$acao_unico  = trim($_POST['acao_unico']);
$cook_idioma = (isset($cookie_login['idioma']))?$cookie_login['idioma']:"pt-br";

/*  Tradução do Login Único */
include_once ('trad_site/fn_ttext.php');

if(strlen($acao_unico)>0){

	if (strlen($msg) == 0) {
		$login = preg_replace('/(\.|\/|-)/', '', strtolower($login));
		$senha = strtolower($senha);

		#------------- Pesquisa posto pelo Login ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica ,
						tbl_posto_fabrica.posto,
						tbl_posto_fabrica.fabrica,
						tbl_posto_fabrica.credenciamento,
						tbl_posto_fabrica.login_provisorio
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE  lower (tbl_posto_fabrica.codigo_posto) = '$login'
				AND    lower (tbl_posto_fabrica.senha)		  = '$senha'";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			extract(pg_fetch_assoc($res, 0));
			if ($credenciamento == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			} elseif ($login_provisorio == 't' AND 1==2 ) {
				$msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
			}else{

				setcookie ('cook_posto_fabrica', $posto_fabrica, null, '/assist', '.telecontrol.com.br');
				setcookie ('cook_posto'        , $posto        , null, '/assist', '.telecontrol.com.br');
				setcookie ('cook_fabrica'      , $fabrica      , null, '/assist', '.telecontrol.com.br');
				setcookie ('cook_login_unico'  , 'temporario'  , null, '/assist', '.telecontrol.com.br');

				header ("Location: http://posvenda.telecontrol.com.br/assist/login_unico_cadastro.php");
				exit;
			}
		}

		#------------- Pesquisa posto pelo CNPJ ---------------#
		$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
						tbl_posto_fabrica.posto,
						tbl_posto_fabrica.fabrica ,
						tbl_posto_fabrica.credenciamento
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_posto.posto
				WHERE tbl_posto.cnpj                 = '$login'
				AND   LOWER(tbl_posto_fabrica.senha) = '$senha'";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			extract(pg_fetch_assoc($res, 0));
			if ($credenciamento == 'DESCREDENCIADO') {
				$msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
			}else{
				//Wellington - Trocar aqui por "if (pg_fetch_result($res,0,fabrica)==11)" no dia 04/01 após atualizar os códigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
				//if ($posto <> 6359 and $fabrica)<>11) {

				setcookie ('cook_posto_fabrica', $posto_fabrica, null, '/assist', '.telecontrol.com.br');
				setcookie ('cook_posto'        , $posto        , null, '/assist', '.telecontrol.com.br');
				setcookie ('cook_fabrica'      , $fabrica      , null, '/assist', '.telecontrol.com.br');
				setcookie ('cook_login_unico'  , 'temporario'  , null, '/assist', '.telecontrol.com.br');

				header ("Location: http://posvenda.telecontrol.com.br/assist/login_unico_cadastro.php");
				exit;
			/*	}else{
					$sql = "SELECT codigo_posto
							FROM   tbl_posto_fabrica
							WHERE  posto   = $posto
							AND    fabrica = $fabrica";
					$res = pg_query ($con,$sql);
					$novo_login = pg_fetch_result($res,0,0);
					$msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
				}*/
			}
		}
		$msg= ttext($a_trad_LU,'login_invalido');
	}
}

$html_titulo = ttext($a_trad_LU,'lu_access');

$body_options = "javascript:document.login_unico.login.focus();";
include "inc_header.php";
?>
<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" language="JavaScript">
    function abreManualLU() {
			var xx=480;
			var y=10;
			var x=window.screen.availWidth;
			var yy=window.screen.availHeight;
				x=(parseInt(x)/2)-(xx/2);// Calcula a posição do centro horiz. da janela
				yy=(parseInt(yy) - 40);
				y=(parseInt(window.screen.availHeight) - yy)/2;
			var winopts="toolbar=0,status=1,menubar=0,resizable=1,";
			    winopts=winopts+"scrollbars=1,width="+xx+",height="+yy+",top="+y+",left="+x;
	window.open("/lu_man.html","_blank",winopts);
	}
	jQuery().ready(function ($) {
		$('#mensagem').click(function () {$(this).hide('fast');});
	});
</script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<style>
/* 29/12/2008 - MLG	*/
a#manual:visited,a#manual:link {
	color:#F00;
	text-decoration:none;
    font-weight: bold;
}

a#manual:hover {text-decoration:underline;}
/* Fim  */
</style>

<div id='conteiner'>
	<div id='conteudo'>
<?
if(md5($_GET["id"])==$_GET["key1"]){
	$lu_id = $_GET["id"];
	$sql = "UPDATE tbl_login_unico SET email_autenticado = current_timestamp WHERE login_unico = $lu_id";
	$res = pg_query($con, $sql);

	if(pg_affected_rows($res) == 1) {
		echo "<div class='msg' id='mensagem'>E-mail Validado, Clique <a href='http://www.telecontrol.com.br'>aqui</a> para logar</div>\n";
	} else {
	    echo "<div class='erro' id='mensagem'>".ttext($a_trad_LU, "erro_gravar_auth").
			 "<a href='mailto:helpdesk@telecontrol.com.br'>".ttext($a_trad_header, "Suporte")."</a>.</div>\n";
	}
	exit;
}
?>
		<h2  id='t_lu'><?=ttext($a_trad_LU, "login_unico") ?></h2>
		<p>&nbsp;</p>
		<ol style="list-style:disc inside">
			<p><?=ttext($a_trad_LU, "lu_list_1") ?></p>
		</ol>
		<p>&nbsp;</p>
		<p><?=ttext($a_trad_LU, "lu_text_3") ?></p>
		<p>&nbsp;</p>
		<form  name='login_unico' id='lu' method='POST'>
			<fieldset>
			<legend><?=ttext($a_trad_LU, "Cadastre-se") ?></legend>
			<p>
				<input type='hidden' name='acao_unico' value='ok'>
				<label><?=ttext($a_trad_LU, "login_fabrica") ?>:</label>
				<input name="login" id="login" size="20" maxlength="50" value="" type="text" />
				<br />
				<label><?=ttext($a_trad_LU, "senha_fabrica") ?>:</label>
				<input type='password' name='senha' id='senha' size='20' />
				<span>
<?				if($msg==1) $pmsg .= ttext($a_trad_LU, "login_invalido");
				echo $pmsg;
?>
				&nbsp;</span>
				<button type='submit' name='acao' value='Acessar'>
				    <?=ttext($a_trad_LU, "acessar") ?>
				</button>
			</p>
			</fieldset>
			<p>&nbsp;</p>
			<?=ttext($a_trad_LU, "no_conf") ?>&nbsp;
			<a href='login_unico_envio_email.php?keepThis=true&TB_iframe=true&height=500&width=750'
			  style='font-weight:bold'><?=ttext($a_trad_LU, "Clique_aqui") ?></a>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
	        <a id='manual' href="javascript:abreManualLU();" style='text-decoration: blink;'>
				<?=ttext($a_trad_LU, "abrir_manual") ?><img src='../imagens/ext.gif' /></a>
		</form>
	</div>
</div>
<?
//include "inc_footer.php";
?>
