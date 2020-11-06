<?
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
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

	<link type="text/css" rel="stylesheet" href="css/css_britania.css">

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
<table width='700' border='1' cellspacing='0' cellpadding='0'>
<tr>
<td>
<table class='menu' border='0' cellspacing='0' cellpadding='0'>
<tr>
	<td class='menu_item'>CADASTROS</td>
</tr>
<tr>
	<td class='menu_item'>GERÊNCIA</td>
</tr>
<tr>
	<td class='menu_item'>INFO TÉCNICA</td>
</tr>
<tr>
	<td class='menu_item'>FINANCEIRO</td>
</tr>
<tr>
	<td class='menu_item'>AUDITORIA</td>
</tr>
<tr>
	<td class='menu_item'>CALL-CENTER</td>
</tr>
</table>
</td>

<td>
<table class='a' width='100%' border='1' cellspacing='0' cellpadding='0'>
<tr>
	<td>tst</td>
	<td></td>
</tr>
<tr>
	<td></td>
	<td></td>
</tr>
</table>
</td>
</tr>
</table>