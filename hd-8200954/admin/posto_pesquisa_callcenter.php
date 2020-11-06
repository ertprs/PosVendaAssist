<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'cabecalho_pop_produtos.php';?>

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

<body onblur="setTimeout('window.close()',6500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>

<br><?php

$tipo = trim(strtolower($_GET['tipo']));
if ($tipo == "nome") {
	$nome = trim(strtoupper($_GET["campo"]));
	
	echo "<h4>Pesquisando por <b>nome do posto</b>: <i>$nome</i></h4>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ILIKE '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";
	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>Posto '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "codigo") {
	$posto_codigo = trim(strtoupper($_GET["campo"]));
	$posto_codigo = str_replace(".","",$posto_codigo);
	$posto_codigo = str_replace(",","",$posto_codigo);
	$posto_codigo = str_replace("-","",$posto_codigo);
	$posto_codigo = str_replace("/","",$posto_codigo);


	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>código do posto</b>: <i>$codigo_posto</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto_fabrica.codigo_posto ilike '%$posto_codigo%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>Posto '$posto_codigo' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "cidade") {
	$posto_cidade = trim(strtoupper($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Cidade</b>: <i>$posto_cidade</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto.cidade ilike '%$posto_cidade%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>CNPJ '$posto_cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "estado") {
	$posto_estado = trim(strtoupper($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do posto</b>: <i>CNPJ</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto.estado ilike '%$posto_estado%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>CNPJ '$posto_estado' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "bairro") {
	$posto_bairro = trim(strtoupper($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Bairro</b>: <i>$posto_bairro</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto.bairro ilike '%$posto_bairro%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>Bairro '$posto_bairro' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}


if ($tipo == "cnpj") {
	$posto_cnpj = trim(strtoupper($_GET["campo"]));
	$posto_cnpj = str_replace(".","",$posto_cnpj);
	$posto_cnpj = str_replace(",","",$posto_cnpj);
	$posto_cnpj = str_replace("-","",$posto_cnpj);
	$posto_cnpj = str_replace("/","",$posto_cnpj);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do posto</b>: <i>CNPJ</i></font>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.*, tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto.cnpj ilike '%$posto_cnpj%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";

	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>CNPJ '$posto_cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}



if ($tipo == "linha") {
	$posto_linha = trim(strtoupper($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Linha</b>: <i>$posto_linha</i></font>";
	echo "<p>";
	
	$sql = "SELECT tbl_posto.*, tbl_posto_fabrica.codigo_posto, tbl_linha.nome AS nome_linha
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			JOIN     tbl_posto_linha using(posto)
			JOIN     tbl_linha using(linha)
			WHERE    tbl_linha.nome ilike '%$posto_linha%'
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome ";

	$res = pg_exec($con,$sql);
	
	if (@pg_numrows($res) == 0) {
		echo "<h1>Linha '$posto_linha' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}



/*
Retorna para a página as informações quando encontra apenas um registro.

if (pg_numrows($res) == 1) {
	echo "<script language='javascript'>";
	echo "nome.value   = '".str_replace('"','',trim(pg_result($res,0,nome)))."';";
	echo "codigo.value = '".trim(pg_result($res,0,codigo_posto))."';";
	echo "cidade.value = '".trim(pg_result($res,0,cidade))."';";
	echo "bairro.value = '".trim(pg_result($res,0,bairro))."';";
	echo "estado.value = '".trim(pg_result($res,0,estado))."';";
	echo "cnpj.value   = '".trim(pg_result($res,0,cnpj))."';";
	if ($_GET["proximo"] == "t") echo "proximo.focus();";
	echo "this.close();";
	echo "</script>";
	exit;
}
*/

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0' style='font-size: 11px; font-family: verdana;'>\n";
	
	for ( $i = 0 ; $i < pg_numrows($res) ; $i++ ) {
		$posto_codigo      =trim(pg_result($res,$i,codigo_posto));
		$posto_posto       = trim(pg_result($res,$i,posto));
		$posto_nome        = trim(pg_result($res,$i,nome));
		$posto_cnpj        = trim(pg_result($res,$i,cnpj));
		$posto_cidade      = trim(pg_result($res,$i,cidade));
		$posto_estado      = trim(pg_result($res,$i,estado));
		$posto_bairro      = trim(pg_result($res,$i,bairro));
		$posto_endereco    = trim(pg_result($res,$i,endereco));
		$posto_fone        = trim(pg_result($res,$i,fone));
		if($tipo == 'linha'){
			$posto_linha       = trim(pg_result($res,$i,nome_linha));
		}
		$posto_nome = str_replace('"','',$posto_nome);
		$posto_cnpj = substr ($posto_cnpj,0,2) . "." . substr ($posto_cnpj,2,3) . "." . substr ($posto_cnpj,5,3) . "/" . substr ($posto_cnpj,8,4) . "-" . substr ($posto_cnpj,12,2);


		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' color='#000000'>$posto_cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td colspan='2'>\n";
		echo "<a href=\"javascript: nome.value = '$posto_nome'; codigo.value = '$posto_codigo'; cidade.value = '$posto_cidade'; bairro.value = '$posto_bairro'; estado.value = '$posto_estado'; cnpj.value = '$posto_cnpj'; linha.value = '$posto_linha;' ";
		if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
		echo "this.close() ; \" >";
		echo "<font face='Arial, Verdana, Times, Sans' color='#0000FF'>$posto_nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' color='#000000'>$posto_cidade - $posto_estado</font>\n";
		echo "</td>\n";
		echo "</tr>";
		
		echo "<tr style='color:#9B9B9B; font-size: 10px;'>";
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans'>$posto_bairro</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans'>$posto_endereco</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans'>$posto_fone</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans'>$posto_linha</font>\n";
		echo "</td>\n";

		echo "</tr>";

		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>