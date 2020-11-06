<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

include 'cabecalho_pop_produtos.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Posto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body>

<script language="JavaScript">
<!--
function retorno(cnpj, nome) {
	opener.document.frm_excecao.posto.value      = posto;
	opener.document.frm_excecao.posto_cnpj.value = cnpj;
	opener.document.frm_excecao.posto_nome.value = nome;
	opener.document.frm_excecao.posto_nome.focus()
	window.close();
}
// -->
</script>

<br>

<?
if (strlen($HTTP_GET_VARS["cnpj"]) > 0) {
	$cnpj = trim($HTTP_GET_VARS["cnpj"]);
	
	echo "<h4>Pesquisando por <b>CNPJ do Posto</b>: <i>$cnpj</i></h4>";
	echo "<p>";
	
	$sql = "SELECT      tbl_posto.posto,
						tbl_posto.nome ,
						tbl_posto.cnpj
			FROM        tbl_posto
			JOIN        tbl_posto_fabrica USING (posto)
			WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
			AND         tbl_posto.cnpj = (SELECT fnc_so_numeros('$cnpj'))::varchar(14);";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>CNPJ '$cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('opener.window.document.frm_excecao.posto_cnpj.focus()',2500);";
		echo "</script>";
		exit;
	}
}


if (strlen($HTTP_GET_VARS["nome"]) > 0) {
	$nome = strtoupper($HTTP_GET_VARS["nome"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência do produto</b>: <i>$referencia</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_posto.posto,
						tbl_posto.nome ,
						tbl_posto.cnpj
			FROM        tbl_posto
			JOIN        tbl_posto_fabrica USING (posto)
			WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
			AND         tbl_posto.nome ilike '%nome%';";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Nome '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('opener.window.document.frm_excecao.posto_nome.focus()',2500);";
		echo "</script>";
		
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$posto      = trim(pg_result($res,0,posto));
	$posto_cnpj = trim(pg_result($res,0,cnpj));
	$posto_cnpj = substr($posto_cnpj,0,2) .".". substr($posto_cnpj,2,3) .".". substr($posto_cnpj,5,3) ."/". substr($posto_cnpj,8,4) ."-". substr($posto_cnpj,12,2);
	$posto_nome = trim(pg_result($res,0,nome));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.frm_excecao.posto.value      = '$posto'; \n";
	echo "opener.window.document.frm_excecao.posto_cnpj.value = '$posto_cnpj'; \n";
	echo "opener.window.document.frm_excecao.posto_nome.value = '$posto_nome'; \n";
	echo "opener.window.document.frm_excecao.posto_nome.focus();\n";
	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$posto      = trim(pg_result($res,$i,posto));
		$posto_cnpj = trim(pg_result($res,$i,posto_cnpj));
		$posto_cnpj = substr($posto_cnpj,0,2) .".". substr($posto_cnpj,2,3) .".". substr($posto_cnpj,5,3) ."/". substr($posto_cnpj,8,4) ."-". substr($posto_cnpj,12,2);
		$posto_nome = trim(pg_result($res,$i,posto_nome));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$posto_cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$posto','$posto_cnpj','$posto_nome')\">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$posto_nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>