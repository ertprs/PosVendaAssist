<?php
header('Content-Type: text/html; charset=utf-8');

/*	Seleção de idioma	*/
$cook_idioma = $_COOKIE['cook_idioma'];
$arquivo_url = substr($PHP_SELF, strrpos($PHP_SELF, "/"));

if (($dir_idioma = ($cook_idioma == "pt-br") ? "":$cook_idioma) != "") {	// Se for pt-BR não precisa de diretório...
	if (file_exists($dir_idioma.$arquivo_url)) header("Location: $dir_idioma"."$arquivo_url");
}

/*  Tradução do cabeçalho:    */
include_once "./trad_site/fn_ttext.php";
include "./trad_site/trad_inc_header.php";

if ($html_titulo=="") $html_titulo = "Gest&atilde;o de P&oacute;s-Venda";
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?=ttext($a_trad_header, "titulo") . " - $html_titulo"?></title>
<?	/*	A URL do <base> define a "pasta de trabalho", se tirar o 'new' já vai direcionar tudo para '/'	*/?>

	<link rel="stylesheet"		type="text/css"		href="css/tc09_layout.css"	  charset="utf-8" />
	<link rel="shortcut icon"	type="image/x-icon" href="img/favicon.ico"		  charset="utf-8" />
	<!--[if IE]>
		<style>
		input[type=button], input[type=submit], input[type=reset], .button, button {
			padding: 2px 5px 5px;
		}
		</style>
		<script type="text/javascript" src="js/IE8.js"></script>
	<![endif]-->
<!--
	<script type="text/javascript" src="js/MenuMatic_0.68.3.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/AC_RunActiveContent.js"></script>
-->
	<script type="text/javascript" src="js/jquery.min.js"></script>

	<script type="text/javascript">
// 		alert ("<?=$temp_idioma.$arquivo_url?>");
		function setCookie(c_name,value,domain,path,expiredays) {
			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			var expireDate = (expiredays==null) ? "" : ";expires="+exdate.toGMTString();
			var c_path     = (path == null || path == "") ? "" : ";path="+path;
			var c_domain   = (domain == null || domain == "") ? "" : ";domain="+domain;
			document.cookie=c_name+ "=" +escape(value)+c_domain+c_path+expireDate;
		}
	</script>
</head>

<body <?=$body_options?>>
<div id="geral">
<div id="header">
	<a href="http://www.telecontrol.com.br/"><h1 id="logo">Telecontrol</h1></a>
	<div id="LoginTipos">
		<p class="loginAcesso">
			<a class="loginUnico"     target='_parent' href="login_unico.php">Login Único</a>
			<a class="primeiroAcesso" target='_parent' href="primeiro_acesso.php">Primeiro Acesso</a>
		</p>
	</div>
<!--nav-->
<!--Mensagens de erro e status-->
