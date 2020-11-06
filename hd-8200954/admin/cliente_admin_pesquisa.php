<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_postos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa de Clientes </title>
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

	$sql = "SELECT   tbl_cliente_admin.cliente_admin,
			tbl_cliente_admin.nome           ,
			tbl_cliente_admin.cnpj           ,
			tbl_cliente_admin.codigo         ,
			tbl_cliente_admin.cidade         ,
			tbl_cliente_admin.estado
			FROM     tbl_cliente_admin
			WHERE    tbl_cliente_admin.nome ILIKE '%$nome%'
			AND fabrica = $login_fabrica
			ORDER BY tbl_cliente_admin.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Posto '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "codigo") {
	$codigo = trim (strtoupper($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CODIGO do cliente admin</b>: <i>$codigo</i></font>";
	echo "<p>";

	$sql = "SELECT   tbl_cliente_admin.cliente_admin,
			tbl_cliente_admin.nome           ,
			tbl_cliente_admin.cnpj           ,
			tbl_cliente_admin.codigo         ,
			tbl_cliente_admin.cidade         ,
			tbl_cliente_admin.estado
			FROM     tbl_cliente_admin
			WHERE    tbl_cliente_admin.codigo ILIKE '%$codigo%'
			AND fabrica = $login_fabrica;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>CODIGO '$codigo' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";

		exit;
	}
}

if ($tipo == "cnpj") {
	$cnpj = trim (strtoupper($_GET["campo"]));

	$cnpj = str_replace ('.','',$cnpj);
	$cnpj = str_replace ('/','',$cnpj);
	$cnpj = str_replace ('-','',$cnpj);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do cliente admin</b>: <i>$cnpj</i></font>";
	echo "<p>";

	$sql = "SELECT   tbl_cliente_admin.cliente_admin,
			tbl_cliente_admin.nome           ,
			tbl_cliente_admin.cnpj           ,
			tbl_cliente_admin.codigo         ,
			tbl_cliente_admin.cidade         ,
			tbl_cliente_admin.estado
			FROM     tbl_cliente_admin
			WHERE    tbl_cliente_admin.cnpj ILIKE '%$cnpj%'
			AND fabrica = $login_fabrica;";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
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
		$revenda    = trim(pg_result($res,$i,cliente_admin));
		$nome       = trim(pg_result($res,$i,nome));
		$cnpj       = trim(pg_result($res,$i,cnpj));
		$codigo     = trim(pg_result($res,$i,codigo));
		$cidade     = trim(pg_result($res,$i,cidade));
		$estado     = trim(pg_result($res,$i,estado));

		$nome = str_replace ('"','',$nome);



		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$codigo</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?cliente_admin=$revenda' ; this.close() ;\" > " ;
		}else if ($_GET['forma'] == 'reduzida') {
			echo "<a href=\"javascript: nome.value = '$nome' ; cliente_admin.value = '$revenda' ; this.close() ; \" >";
		}else{
			echo "<a href=\"javascript: nome.value = '$nome' ; codigo_cliente_admin.value = '$codigo' ; this.close() ; \" >";
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
