<?
#header("Expires: 0");
#header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
#header("Pragma: no-cache, public");

$cpu_inicio = microtime(1);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<!-- AQUI COMEÇA O HTML DO MENU -->

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

	<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

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

<script language="javascript" src="js/assist.js"></script>

<body onLoad="fnc_preload();">


<? if ($sem_menu == false OR strlen ($sem_menu) == 0 ) { ?>
  <div id="menu"> 
    <p>
	<?
	switch ($layout_menu) {
	case "gerencia":
		echo "<img src='imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
		include 'submenu_gerencia.php';
		break;
	case "callcenter":
		echo "<img src='imagens_admin/btn_callcenter.gif' usemap='#menu_map'>";
		include 'submenu_callcenter.php';
		break;
	case "cadastro":
		echo "<img src='imagens_admin/btn_cadastro.gif' usemap='#menu_map'>";
		include 'submenu_cadastro.php';
		break;
	case "tecnica":
		echo "<img src='imagens_admin/btn_tecnica.gif' usemap='#menu_map'>";
		include 'submenu_tecnica.php';
		break;
	case "financeiro":
		echo "<img src='imagens_admin/btn_financeiro.gif' usemap='#menu_map'>";
		include 'submenu_financeiro.php';
		break;
	case "auditoria":
		echo "<img src='imagens_admin/btn_auditoria.gif' usemap='#menu_map'>";
		include 'submenu_auditoria.php';
		break;
	default:
		echo "<img src='imagens_admin/btn_gerencia.gif' usemap='#menu_map'>";
		break;
	}
	?>
      <map name="menu_map">
        <area shape="rect" coords="014,0,090,24" href="menu_gerencia.php">
        <area shape="rect" coords="100,0,176,24" href="menu_callcenter.php">
        <area shape="rect" coords="190,0,263,24" href="menu_cadastro.php">
        <area shape="rect" coords="276,0,353,24" href="menu_tecnica.php">
        <area shape="rect" coords="362,0,439,24" href="menu_financeiro.php">
        <area shape="rect" coords="450,0,527,24" href="menu_auditoria.php">
        <area shape="rect" coords="541,0,622,24" href="http://www.telecontrol.com.br/assist">
      </map>
  </div>

<? } ?>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->
<div id="subBanner">
	<h1>
		<? echo "$title" ?>
	</h1>
	<div class="frm-on-os" id="displayArea">&nbsp;</div>
</div>
