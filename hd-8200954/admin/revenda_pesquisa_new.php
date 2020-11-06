<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_revendas.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Revenda Autorizadas... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body style="margin: 0px 0px 0px 0px;" >

<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "nome") {
	$nome = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>Nome do Posto</b>: <i>$nome</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   tbl_revenda.revenda,
			tbl_revenda.nome           ,
			tbl_revenda.cnpj           ,
			tbl_cidade.nome AS cidade  ,
			tbl_cidade.estado          
			FROM     tbl_revenda
			JOIN     tbl_cidade USING(cidade)
			WHERE    tbl_revenda.nome ILIKE '%$nome%' 
			ORDER BY tbl_revenda.nome";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Posto '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "cnpj") {
	$cnpj = trim (strtoupper($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Posto</b>: <i>$cnpj</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_revenda.revenda,
			tbl_revenda.nome           ,
			tbl_revenda.cnpj           ,
			tbl_cidade.nome AS cidade  ,
			tbl_cidade.estado          
			FROM     tbl_revenda
			JOIN     tbl_cidade USING(cidade)
			WHERE    tbl_revenda.cnpj ILIKE '%$cnpj%';"; 

	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>CNPJ '$cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}
}

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$revenda    = trim(pg_result($res,$i,revenda));
		$nome       = trim(pg_result($res,$i,nome));
		$cnpj       = trim(pg_result($res,$i,cnpj));
		$cidade     = trim(pg_result($res,$i,cidade));
		$estado     = trim(pg_result($res,$i,estado));
		
		$nome = str_replace ('"','',$nome);
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$cnpj = str_replace ('"','',$cnpj);
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?revenda=$revenda' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: nome.value = '$nome' ; cnpj.value = '$cnpj' ; this.close() ; \" >";
		}
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