<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title> Pesquisa Representantes </title>
	<meta http-equiv=pragma content=no-cache>
	<link type="text/css" rel="stylesheet" href="admin/css/css.css">
	<link href="admin/css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css"  rel="stylesheet" type="text/css" />
	<style type="text/css">
		titulo_tabela{
			background-color:#596d9b;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}

		.titulo_coluna{
			background-color:#596d9b;
			font: bold 11px "Arial";
			color:#FFFFFF;
			text-align:center;
		}

		table.tabela tr td{
			font-family: verdana;
			font: bold 11px "Arial";
			border-collapse: collapse;
			border:1px solid #596d9b;
		}

		body {
			margin:0;
		}
	</style>
</head>

<body onblur="setTimeout('window.close()',2500);">
<div id="menu"> 
	<img src='admin/imagens_admin/pesquisa_revenda.gif'>
</div>

<br /><?php

$os    = trim(strtolower($_GET['os']));
$tipo  = trim(strtolower($_GET['tipo']));
$campo = trim(strtoupper($_GET["campo"]));
$fabrica 	= trim(strtoupper($_GET["fabrica"]));

if ($fabrica > 0) {
	$where .= " and fabrica =  $fabrica ";
}

if ($tipo == "nome") {
	$where .= " and (nome ilike '%$campo%' or contato ilike '%$campo%')";
}

if ($tipo == "codigo") {
	$where .= " and codigo ilike '%$campo%'";
}


$sql = "SELECT * FROM tbl_representante WHERE representante > 0 and ativo and fabrica = $login_fabrica  $where";
$res = pg_exec($con,$sql);

if (@pg_numrows($res) == 0) {
	echo "<h1>Representante '$campo' não encontrado</h1>";
	echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
}

if (pg_numrows($res) == 1) {

	echo "<script language='javascript'>";
		echo "nome.value   = '".str_replace('"','',trim(pg_result($res,0,'nome')))."';";
		echo "codigo.value = '".trim(pg_result($res,0,'codigo'))."';";
		echo "representante.value = '".trim(pg_result($res,0,'representante'))."';";
		echo "this.close();";
	echo "</script>";
	//exit;

}

echo "<script type='text/javascript'>\n";
echo "<!--\n";
	echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";

echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";

	if ($tipo == "nome")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>Nome do Representante</b>: <i>$campo</font></td></tr>";

	if ($tipo == "codigo")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>Código</b>: $campo</font></td></tr>";

	echo "<tr class='titulo_coluna'>";
		echo "<td>CNPJ</td>";
		echo "<td>Nome</td>";
		echo "<td>Cidade</td>";
		echo "<td>UF</td>";
	echo "</tr>\n";

	$total = pg_numrows($res);

	for ($i = 0; $i < $total; $i++ ) {
		$representante = trim(pg_result($res, $i, 'representante'));
		$codigo        = trim(pg_result($res, $i, 'codigo'));
		$nome          = trim(pg_result($res, $i, 'nome'));
		$cnpj          = trim(pg_result($res, $i, 'cnpj'));
		$cidade        = trim(pg_result($res, $i, 'cidade'));
		$estado        = trim(pg_result($res, $i, 'estado'));

		$nome = str_replace('"','',$nome);
		$cnpj = substr($cnpj,0,2) . "." . substr($cnpj,2,3) . "." . substr($cnpj,5,3) . "/" . substr($cnpj,8,4) . "-" . substr($cnpj,12,2);

		$cor = ($i % 2 == 0) ?  "#F7F5F0" : $cor = "#F1F4FA";

		echo "<tr bgcolor='$cor'>\n";

			$link = "javascript:codigo.value='$codigo'; nome.value='$nome'; representante.value='$representante'; this.close();";

			echo "<td><a href=\"$link\">$cnpj</a></td>\n";
			echo "<td><a href=\"$link\">$codigo - $nome</a></td>\n";
			echo "<td><a href=\"$link\">$cidade</a></td>\n";
			echo "<td><a href=\"$link\">$estado</a></td>\n";

		echo "</tr>\n";

	}

echo "</table>\n";?>

</body>
</html>
