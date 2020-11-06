<?php
include_once "funcoes.php";
include_once 'regras/menu_posto/menu.helper.php';
$navbar = include(MENU_DIR . 'menu_array.php');

// $micro_time_start = getmicrotime();

$menu = new MenuPosto(reset($navbar), 'FMC');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title><?=$page_title?></title>
	<link href="imagens/tc_2009.ico" rel="shortcut icon">
	<?php if ($menu->fw == 'BS3'): ?>
		<link rel="stylesheet" href="externos/bootstrap3/css/bootstrap.min.css">
		<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
		<script src="externos/bootstrap3/js/bootstrap.min.js"></script>
		<style>
			body {margin-top: 60px;}
			.navbar-nav {
			  float: initial;
			  text-align: center;
			}
			.navbar-nav>li {
			  float: initial;
			}
			.nav>li {
			  display: inline-block;
			}
		</style>
	<?php endif; ?>

	<?php if ($menu->fw == 'FMC'): ?>
		<link href='https://fonts.googleapis.com/css?family=Roboto:400,300,300italic,400italic,500italic,700,700italic,500' rel='stylesheet' type='text/css'>
		<link href="fmc/css/styles.css" rel="stylesheet" type="text/css">
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<style type="text/css">
			/* Retirar o margin 0  e o text-align left do '*' no css da FMC, está quebrando muita coisa. */

			.nav h1.header {
			  color: white;
			  text-transform: uppercase;
			  padding: 8px 9px;
			  font-family: Roboto,
			  font-size: 12px;
			  font-weight: bold;
			}
			.header .nav {
			  z-index: 101;
			}
			.header .nav .main2 {
			  width: 1000px;
			}
			.nav ul {
			  text-align: left;
			}
			.nav ul ul {
			  box-shadow: 1px 2px 12px rgba(0, 0, 0, .7);
			}
			.main, .main2 {text-align: left}
			.main2 > .title {
				padding-top: 10px;
				/* width: 600px; */
			}
			.nav ul > li a{
			  text-decoration: none;
			}
			.nav ul > li > ul li:hover a {
			  background-color: #fac819;
			  color: #373865;
			  text-decoration: none;
			}
			.nav ul > li > ul li a {
			  padding: 10px;
			  text-align: left;
			}
			.nav ul h1 {
			  font-size: 14px;
			}
			.nav ul:hover ul {
				width: inherit;
				max-width: 200px;
				white-space: nowrap;
			}
			.nav ul > li h1 {
			    display: table;
			    width: 100%;
			    height: 100%;
			    line-height: 19.6px;
				margin-top: 0px;
			    margin-right: 0px;
			    margin-bottom: 0px;
			    margin-left: 0px;
			}
			.nav ul > li a {
			    display: table-cell;
			    vertical-align: middle;
			    color: #ffffff;
			    text-align: center;
			    width: 100%;
			    height: 100%;
			}
			.nav .fa-fw {
			    margin-right: 4px;
			}
			html, body {
				height: auto;
			}
			.input-append .add-on, .input-prepend .add-on{
				height: 30px !important;
			}

			.cabecalho_logo{
				float: left;
    			margin-left: 27%;
			}
		</style>
	<?php endif;?>
</head>
<body style="margin-top: 45px;">
<?php
echo $menu->setFw($menuFw)->navBar()->HTML;
?>
<div class="clearfix">&nbsp;</div>
<?php if($desabilita_tela):?>
	<div class="alerts">
		<div class="alert danger info margin-top">
			<?=$desabilita_tela?>
		</div>
	</div>
<?php
	include_once('rodape.php');
	exit;
endif; ?>

