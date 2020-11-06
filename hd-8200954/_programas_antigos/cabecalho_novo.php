<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$cpu_inicio = microtime(1);

?>

<html>

<head>

	<title><? echo $title ?></title>

	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

<!-- 	<link type="text/css" rel="stylesheet" href="css/css.css"> -->

<style type='text/css'>

body {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	margin: 0px,0px,0px,0px;
	padding: 0px,0px,0px,0px;
}

img {
	border: 0px;
}

</style>

<script>
/*****************************************************************
Nome da Função : displayText
		Apresenta em um campo as informações de ajuda de onde 
		o cursor estiver posicionado.
******************************************************************/
	function displayText( sText ) {
		document.getElementById("displayArea").innerHTML = sText;
	}

</script>
</head>

<body>

<!--================== MENU DO SISTEMA ASSIST =======================-->
<!-- PARÂMETRO A SER PASSADO $layout_menu  "passa a opção em destaque-->

<div id="menu"> 
	<p>
	<?
	switch ($layout_menu) {

/*--================== $layout_menu = os =======================-*/
	case "os":
		echo "<img src='imagens/btn_os.gif' usemap='#menu_map'>";
		include 'submenu_os.php';
		break;

/*--================== $layout_menu = preco ====================-*/
	case "preco":
		echo "<img src='imagens/btn_preco.gif' usemap='#menu_map'>";
		include 'submenu_preco.php';
		break;

/*--================== $layout_menu = pedido ===================-*/
	case "pedido":
		echo "<img src='imagens/btn_pedidos.gif' usemap='#menu_map'>";
		include 'submenu_pedido.php';
		break;

/*--================== $layout_menu = tecnica ===================-*/
 	case "tecnica":
		echo "<img src='imagens/btn_tecnica.gif' usemap='#menu_map'>";
		include 'submenu_tecnica.php';
		break;

/*--================== $layout_menu = cadastro =================-*/
	case "cadastro":
		echo "<img src='imagens/btn_cadastro.gif' usemap='#menu_map'>";
		include 'submenu_cadastro.php';
		break;

/*--================== $layout_menu = auditoria ===================-*/
	case "auditoria":
		echo "<img src='imagens_admin/btn_auditoria.gif' usemap='#menu_map'>";
		include 'submenu_auditoria.php';
		break;

/*--================== $layout_menu = padrao =======================-*/
	default:
		echo "<img src='imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
		break;
	}
	?>

<!--============== MAPA DE IMAGEM DA BARRA DE MENU ============-->
	<map name="menu_map">
		<area shape="rect" coords="014,0,090,24" href="menu_os.php">
		<area shape="rect" coords="100,0,176,24" href="menu_preco.php">
		<area shape="rect" coords="190,0,263,24" href="menu_pedido.php">
 		<area shape="rect" coords="276,0,353,24" href="menu_tecnica.php">
		<area shape='rect' coords='362,0,439,24' href="menu_cadastro.php">
<!-- 		<area shape="rect" coords="450,0,527,24" href="#"> -->
		<area shape="rect" coords="541,0,622,24" href="http://www.telecontrol.com.br/assist">
	</map>
</div>


<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<div id="subBanner">
	<h1>
		<? echo "$title" ?>
	</h1>
	<div class="frm-on-os" id="displayArea">&nbsp;</div>
</div>
