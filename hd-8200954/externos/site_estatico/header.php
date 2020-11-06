<!DOCTYPE html>
<html itemscope itemtype="http://schema.org/Product" xmlns="http://www.w3.org/1999/xhtml" lang="pt-br">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?php // $url = "http://ideiasfmc.com/Clientes/telecontrol/"; ?>
<?php
	$url = "https://telecontrol.com.br/";
?>
<meta name="viewport" content="initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
<title>Telecontrol - <?php echo $pagetitle; ?></title>
<meta name="description" content="Acompanhamento completo de reparo do produto para seus clientes." />
<meta name="keywords" content="acompanhamento de produtos, reparo, controle de reparo" />
<meta name="robots" content="index,follow" />
<meta name="Googlebot" content="index,follow" />
<meta name="geo.region" content="BR-SP" />
<meta name="geo.placename" content="Marília - SP" />
<link rel="shortcut icon" href="https://www.telecontrol.com.br/images/favicon.png" />
<link href='https://fonts.googleapis.com/css?family=Roboto:500,100,300,400' rel='stylesheet' type='text/css'>
<script src="site_estatico/js/jquery-1.10.1.min.js"></script>
<script src="site_estatico/js/slider.min.js"></script>
<!--
<script src="http://www.telecontrol.com.br/js/jquery-1.10.1.min.js"></script>
<script src="http://www.telecontrol.com.br/js/slider.min.js"></script>

-->
<script type='text/javascript' src='js/browser_detect2.js'></script>
<script type="text/javascript" src="js/login_local2.js?v=<?= date("Ymdhis") ?>"></script>
<script type="text/javascript" src="js/login_unico2.js"     charset="utf-8"></script>
<script type="text/javascript" src="js/primeiro_acesso.js?v=2" charset="utf-8"></script>
<script type="text/javascript" src="js/esqueceu_senha.js"  charset="utf-8"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

<link rel="stylesheet" href="site_estatico/font-awesome/css/font-awesome.min.css">
<link href="site_estatico/swiper.min.css" rel="stylesheet" type="text/css" />
<link href="site_estatico/style.css" rel="stylesheet" type="text/css" />
<link href="site_estatico/images/telecontrol/styles.css" rel="stylesheet" type="text/css" />
<!--
<link rel="stylesheet" href="<?=$url?>font-awesome/css/font-awesome.min.css">
<link href="<?=$url?>swiper.min.css" rel="stylesheet" type="text/css" />
<link href="<?=$url?>style.css" rel="stylesheet" type="text/css" />
<link href="<?=$url?>images/telecontrol/styles.css" rel="stylesheet" type="text/css" />
-->
<script>
var $device_width = $(window).width();
var $mobile = $device_width<768;
if($mobile) {
document.write('<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">');
} else {
document.write('<meta name="viewport" content="width=1050">');
}
</script>
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<link rel="apple-touch-icon" href="<?=$url?>images/webapp.png" />

</head>

<body>

<div class="menu-fade"></div>

<?php

    $page = basename($_SERVER['SCRIPT_NAME']);
    if ($page == 'index.php' || $page == "/" || $page == ''):
		$menulink = "";
   	else:
		$menulink = $url."";
   	endif;
?>

<div class="menu"><a><i class="fa fa-navicon"></i></a></div>

<div class="menu-tab">
	<ul class="itens">
		<li class="homelink"><h1><a href="<?php echo $url; ?>"><i class="fa fa-home"></i>Home</a></h1></li>
		<li><h2><a href="https://posvenda.telecontrol.com.br/assist/externos/login_posvenda_new.php"><i class="fa fa-lock"></i>Acesse o Sistema</a></h2></li>
		<li><h1><a href="<?php echo $menulink; ?>#quem-somos"><i class="fa fa-building"></i>Quem Somos</a></h1></li>
		<li><h1><a href="<?php echo $url; ?>software.php"><i class="fa fa-gear"></i>Software</a></h1></li>
		<li><h1><a href="<?php echo $url; ?>modulos.php"><i class="fa fa-code"></i>Módulos</a></h1></li>
		<li><h1><a href="<?php echo $url; ?>servicos.php"><i class="fa fa-desktop"></i>Serviços</a></h1></li>
		<li><h1><a href="<?php echo $url; ?>contato.php"><i class="fa fa-phone"></i>Contato</a></h1></li>
		<li>
			<h1><a href="<?php echo $url; ?>trabalhe-conosco.php"><i class="fa fa-users"></i>Trabalhe Conosco</a></h1>
		</li>
		<li>
			<h1><a href="../externos/autocredenciamento_new.php"><i class="icon icon-autocredenciamento"></i>Auto-Credenciamento</a></h1>
		</li>
	</ul>
</div>

