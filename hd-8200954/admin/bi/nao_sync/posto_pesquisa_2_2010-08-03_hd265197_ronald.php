<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include 'autentica_admin.php';

include '../cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Postos... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body onblur="setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "nome") {
	$nome = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>nome do posto</b>: <i>$nome</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Posto '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "codigo") {
	$codigo_posto = trim (strtoupper($_GET["campo"]));
	$codigo_posto = str_replace (".","",$codigo_posto);
	$codigo_posto = str_replace (",","",$codigo_posto);
	$codigo_posto = str_replace ("-","",$codigo_posto);
	$codigo_posto = str_replace ("/","",$codigo_posto);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>código do posto</b>: <i>$codigo_posto</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto_fabrica.codigo_posto ilike '%$codigo_posto%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Posto '$codigo_posto' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {
	echo "<script language='javascript'>";
	echo "nome.value   = '".str_replace ('"','',trim(pg_result($res,0,nome)))."';";
	echo "codigo.value = '".trim(pg_result($res,0,codigo_posto))."';";
	if ($_GET["proximo"] == "t") echo "proximo.focus();";
	echo "this.close();";
	echo "</script>";
	exit;
}

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$codigo_posto=trim(pg_result($res,$i,codigo_posto));
		$posto      = trim(pg_result($res,$i,posto));
		$nome       = trim(pg_result($res,$i,nome));
		$cnpj       = trim(pg_result($res,$i,cnpj));
		$cidade     = trim(pg_result($res,$i,cidade));
		$estado     = trim(pg_result($res,$i,estado));
		
		$nome = str_replace ('"','',$nome);
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);


		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: nome.value = '$nome'; codigo.value = '$codigo_posto';";
		if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
		echo "this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$estado</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>