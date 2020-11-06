<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_postos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Busca Servicio... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">
<img src="../imagens/pesquisa_posto_es.gif">
<br>

<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "nome") {
	$nome = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Buscando por <b>nombre del servicio</b>: <i>$nome</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ilike '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%' OR tbl_posto.fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			AND      tbl_posto.pais            = '$login_pais'
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Servicio '$nome' no encuentrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "cnpj") {
	$cnpj = trim (strtoupper($_GET["campo"]));
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("-","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace (" ","",$cnpj);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por <b>Identificación del servicio</b>: <i>$cnpj</i></font>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.cnpj ILIKE '%$cnpj%' OR tbl_posto_fabrica.codigo_posto ILIKE '%$cnpj%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			AND      tbl_posto.pais            = '$login_pais'
			ORDER BY tbl_posto.nome";
	
	/*$sql = "SELECT      tbl_posto.posto ,
						tbl_posto.nome  ,
						tbl_posto.cnpj  ,
						tbl_posto.cidade,
						tbl_posto.estado
			FROM        tbl_posto
			JOIN        tbl_posto_fabrica USING (posto)
			WHERE      (tbl_posto.cnpj ILIKE '%$cnpj%' OR tbl_posto_fabrica.codigo_posto ILIKE '%$cnpj%')
			GROUP BY	tbl_posto.posto ,
						tbl_posto.nome  ,
						tbl_posto.cnpj  ,
						tbl_posto.cidade,
						tbl_posto.estado
			ORDER BY tbl_posto.nome";*/
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>CNPJ '$cnpj' não encuentrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		
		exit;
	}
}

$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "codigo") {
	$codigo = trim (strtoupper($_GET["campo"]));
	
	echo "<h4>Buscando por <b>Código del Servicio</b>: <i>$codigo</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto_fabrica.codigo_posto ilike '%$codigo%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			AND      tbl_posto.pais            = '$login_pais'
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Servicio '$codigo' no encuentrado</h1>";
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

echo "<table width='100%' border='1'>\n";

for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$posto      = trim(pg_result($res,$i,posto));
	$nome       = trim(pg_result($res,$i,nome));
	$cnpj       = trim(pg_result($res,$i,cnpj));
	$cidade     = trim(pg_result($res,$i,cidade));
	$estado     = trim(pg_result($res,$i,estado));
	$fantasia   = trim(pg_result($res,$i,fantasia)) . " " . trim(pg_result($res,$i,nome_fantasia));
	
	$nome = str_replace ('"','',$nome);
	$cidade = str_replace ('"','',$cidade);
	$estado = str_replace ('"','',$estado);

	echo "<tr>\n";
	
	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
	echo "</td>\n";
	
	echo "<td>\n";
	if ($_GET['forma'] == 'reload') {
		echo "<a href=\"javascript: janela = opener.document.location.href ; posicao = janela.lastIndexOf('.') ; janela = janela.substring(0,posicao+4) ; opener.document.location = janela + '?posto=$posto' ; this.close() ;\" > " ;
	}else{
		echo "<a href=\"javascript: nome.value = '$nome' ; cnpj.value = '$cnpj' ; this.close() ; \" >";
	}
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
	echo "</a>\n";
	if (strlen (trim ($fantasia)) > 0) echo "<br><font color='#808080' size='-1'>$fantasia</font>";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade</font>\n";
	echo "</td>\n";
/*	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$estado</font>\n";
	echo "</td>\n";
*/	
	echo "</tr>\n";
}
echo "</table>\n";

?>

</body>
</html>
