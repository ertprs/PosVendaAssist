<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<? if (!strpos($_SERVER['PHP_SELF'], 'login_unico') and !strpos($_SERVER['PHP_SELF'], 'verifica')) { ?>
	<link rel='stylesheet' type='text/css' charset="utf-8" href='site/login.css' />
<?}?>
	<link rel="stylesheet" type="text/css" charset="utf-8" href="css/login_unico.css">
	<link rel="stylesheet" type="text/css" charset="utf-8" href="site/contato.css">
	<link rel='stylesheet' type='text/css' charset="utf-8" href="site/posvenda.css" />
	<link rel="stylesheet" type="text/css" charset="utf-8" href="site/style.css">
	<link rel="stylesheet" type="text/css" charset="utf-8" href="site/style1.css">
	<!-- <link rel="stylesheet" type="text/css" charset="utf-8" href="css/bootstrap.min.css"> -->


	<title>Telecontrol - <?=$html_titulo?></title>

	<style type="text/css">
	.blank_footer {height: 128px}
	.comunicado{
		font-weight: bold;
		color: #FF0000 !important;
	}
	</style>

	<script src=" ../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script type='text/javascript' src='https://code.jquery.com/jquery-1.8.3.min.js'></script>

	<script type='text/javascript' src='js/browser_detect2.js'></script>
	<script type='text/javascript' src='js/bootstrap.min.js'></script>
	<script type="text/javascript" src="js/login_local.js"></script>
	<script type="text/javascript" src="js/login_unico2.js"     charset="utf-8"></script>
	<script type="text/javascript" src="js/primeiro_acesso.js" charset="utf-8"></script>
	<script type="text/javascript" src="js/esqueceu_senha.js"  charset="utf-8"></script>
</head>
<body id='top' class="home blog logged-in admin-bar" >
<div id="headwrap">
	<!-- ###################################################################### -->
	<div id="head">
	<!-- ###################################################################### -->

		<h2 class="logo ie6fix ">
			<a href="http://www.telecontrol.com.br/"><img class="ie6fix" src="img/logo_tc_2009_texto.png" alt="Telecontrol Networking, Ltda."></a>
		</h2>
		<div class="nav_wrapper">

		<!-- Navigation for Pages starts here -->
		<div class="menu-first-container"><ul id="menu-first" class="nav"><li id="menu-item-1041" class="menu-item menu-item-type-custom menu-item-object-custom current-menu-item current_page_item menu-item-home menu-item-1041"><a href="http://www.telecontrol.com.br/">Home</a></li>
<li id="menu-item-1032" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1032"><a href="http://www.telecontrol.com.br/empresa/">Empresa</a></li>
<li id="menu-item-1031" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1031"><a href="http://www.telecontrol.com.br/pos-venda/">Pós-Venda</a></li>
<li id="menu-item-1547" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1547"><a href="http://www.telecontrol.com.br/pedido-web/">Pedido-Web</a></li>
<li id="menu-item-1028" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1028"><a href="http://www.telecontrol.com.br/contato-tc/">Contato</a></li>
<li id="menu-item-1029" class="menu-item menu-item-type-post_type menu-item-object-page menu-item-1029"><a href="http://www.telecontrol.com.br/trabalhe-conosco/">Trabalhe conosco</a></li>
</ul></div>
		</div><!-- end nav_wrapper -->


		<div class="catnav_wrapper">
		<!-- Navigation for Categories starts here -->
		<div class="menu-second-container"><ul id="menu-second" class="catnav"><li id="menu-item-1549" class="menu-item menu-item-type-post_type menu-item-object-page"><a href="http://www.telecontrol.com.br/pos-venda/" style="height: 34px; "><strong>Pós-Venda</strong></a></li>
<li id="menu-item-1548" class="menu-item menu-item-type-post_type menu-item-object-page"><a href="http://www.telecontrol.com.br/pedido-web/" style="height: 34px; "><strong>Pedido-Web</strong></a></li>
<li id="menu-item-1035" class="menu-item menu-item-type-taxonomy menu-item-object-category"><a href="http://www.telecontrol.com.br/category/infraestrutura/" style="height: 34px; "><strong>Infraestrutura</strong></a></li>
<li id="menu-item-1036" class="menu-item menu-item-type-taxonomy menu-item-object-category"><a href="http://www.telecontrol.com.br/category/seguranca/" style="height: 34px; "><strong>Segurança</strong></a></li>
<li id="menu-item-1037" class="menu-item menu-item-type-taxonomy menu-item-object-category"><a href="http://www.telecontrol.com.br/category/documentacao/" style="height: 34px; "><strong>Documentação</strong></a></li>
<li id="menu-item-1038" class="menu-item menu-item-type-taxonomy menu-item-object-category"><a href="http://www.telecontrol.com.br/category/regras-de-negocios/" style="height: 34px; "><strong>Negócios</strong></a></li>
<li id="menu-item-1827" class="menu-item menu-item-type-custom menu-item-object-custom noborder"><a href="http://posvenda.telecontrol.com.br/assist/externos/autocredenciamento.php" style="height: 34px; "><strong>Credenciamento</strong></a></li>
</ul></div>			<!-- end catnav_wrapper: -->
		</div>

<!-- 			<div id="headextras" class="rounded">
			<iframe frameborder="0" width="310" height="156" style="position: absolute;top:0;right:10px;border: 0 solid transparent;width:310px;overflow-y:hidden;height:156px;margin:0;background:transparent;" scrolling="no" allowtransparency="true" src="http://www.telecontrol.com.br/login_posvenda.php"></iframe>
		</div>	 -->

	<div id="headextras" class="rounded" style='width: auto; right: 50px; top: 20px;'>
			<a href="http://www.telecontrol.com.br/contato-tc/?page_id=25" border="1" style=""><img src="imagens/chat.png" style="float:left; margin-left:350px;margin-top:25px;border:0px;z-index:300;"></a>
		</div>

		<!-- ###################################################################### -->
		</div><!-- end head -->
		<!-- ###################################################################### -->

	<!-- ###################################################################### -->
</div>
<div id="contentwrap">
