<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'menu.php';
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

	<link type="text/css" rel="stylesheet" href="css/css_<? echo strtolower($login_fabrica_nome) ?>.css">

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

<table width='100%' border='0' cellspacing='0' cellpadding='0'>
<tr>

	<td class='menu_up'>&nbsp;</td>
	<td class='menu_up'>&nbsp;</td>
	<td class='menu_up'>&nbsp;</td>
	<td class='menu_up'>&nbsp;</td>
	<td class='menu_up'>&nbsp;</td>
	<td class='menu_up'>&nbsp;</td>
	<td class='menu_txt' rowspan='2'>
		<?
		echo "<a href='$login_fabrica_site' target='_new'>";
		echo "<IMG SRC='/assist/logos/$login_fabrica_logo' ALT='$login_fabrica_site'>";
		echo "</a>";
		?>
	</td>
	<td class='menu_txt' rowspan='2'>
		<table width='100%' border='0' cellspacing='0' cellpadding='0'>
		<tr>
			<td class='cab_txt'>USER:</td>
			<td class='cab_txt'><? echo strtoupper($login_login) ?></td>
		</tr>
		<tr>
			<td class='cab_txt'>LEVEL:</td>
			<td class='cab_txt'>Administrador</td>
		</tr>
		<tr>
			<td class='cab_txt'>DATE:</td>
			<td class='cab_txt'><? echo date("d/m/Y  - H:i") ?></td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td class='menu_dw'><? echo $titulo_menu_1 ?></td>
	<td class='menu_dw'><? echo $titulo_menu_2 ?></td>
	<td class='menu_dw'><? echo $titulo_menu_3 ?></td>
	<td class='menu_dw'><? echo $titulo_menu_4 ?></td>
	<td class='menu_dw'><? echo $titulo_menu_5 ?></td>
	<td class='menu_dw'><? echo $titulo_menu_6 ?></td>
</tr>
</table>

<table width='100%' border='0' cellspacing='0' cellpadding='0'>
<tr class='sub_menu'>
	<td nowrap><? echo $titulo_submenu_1 ?></td>
	<td nowrap><? echo $titulo_submenu_2 ?></td>
	<td nowrap><? echo $titulo_submenu_3 ?></td>
	<td nowrap><? echo $titulo_submenu_4 ?></td>
	<td nowrap><? echo $titulo_submenu_5 ?></td>
	<td nowrap><? echo $titulo_submenu_6 ?></td>
	<td nowrap><? echo $titulo_submenu_7 ?></td>
<tr>
</table>
