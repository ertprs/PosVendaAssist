<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Transportadora.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_transportadora.gif">

<?

$tipo = $_GET["tipo"];
if ($tipo == 'nome')   $nome   = strtoupper (trim ($_GET['campo']));
if ($tipo == 'cnpj')   $cnpj   = strtoupper (trim ($_GET['campo']));
if ($tipo == 'codigo') $codigo = strtoupper (trim ($_GET['campo']));

if (strlen($nome) > 0) {
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome da Transportadora</b>: <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_transportadora.*, tbl_transportadora_fabrica.codigo_interno
			FROM        tbl_transportadora
			JOIN        tbl_transportadora_fabrica USING (transportadora)
			WHERE       tbl_transportadora_fabrica.fabrica = $login_fabrica
			AND         tbl_transportadora.nome ILIKE '%$nome%'
			ORDER BY    tbl_transportadora.nome";
	$res = pg_query ($con,$sql);
	
	if (pg_num_rows ($res) == 0) {
		echo "<h1>Transportadora '$nome' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('transportadora.value=\"\"; codigo.value=\"\"; nome.value=\"\"; cnpj.value=\"\"; window.close();',2500);";
		echo "</script>";
		exit;
	}

}elseif (strlen($cnpj) > 0) {# HD 289285
	$cnpj = str_replace (" ","",$cnpj);
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace ("-","",$cnpj);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ da Transportadora</b>: <i>$cnpj</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_transportadora.*, tbl_transportadora_fabrica.codigo_interno
			FROM        tbl_transportadora
			JOIN        tbl_transportadora_fabrica USING (transportadora)
			WHERE       tbl_transportadora.cnpj LIKE '%$cnpj%'
			AND         tbl_transportadora_fabrica.fabrica = $login_fabrica
			ORDER BY    tbl_transportadora.nome";
	$res = pg_query ($con,$sql);
	
	if (pg_num_rows ($res) == 0) {
		echo "<h1>CNPJ $cnpj não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('transportadora.value=\"\"; codigo.value=\"\"; nome.value=\"\"; cnpj.value=\"\"; window.close();',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($codigo) > 0) {
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Código da Transportadora</b>: <i>$codigo</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_transportadora.*, tbl_transportadora_fabrica.codigo_interno
			FROM        tbl_transportadora
			JOIN        tbl_transportadora_fabrica USING (transportadora)
			WHERE       tbl_transportadora_fabrica.codigo_interno = '$codigo'
			AND         tbl_transportadora_fabrica.fabrica = $login_fabrica
			ORDER BY    tbl_transportadora.nome";

	$res = pg_query ($con,$sql);
	
	if (pg_num_rows ($res) == 0) {
		echo "<h1>Código '$codigo' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('transportadora.value=\"\"; codigo.value=\"\"; nome.value=\"\"; cnpj.value=\"\"; window.close();',2500);";
		echo "</script>";
		exit;
	}
}else{
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando todas as transportadoras</font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_transportadora.*, tbl_transportadora_fabrica.codigo_interno
			FROM        tbl_transportadora
			JOIN        tbl_transportadora_fabrica USING (transportadora)
			WHERE       tbl_transportadora_fabrica.fabrica = $login_fabrica
			ORDER BY    tbl_transportadora.nome";

	$res = pg_query ($con,$sql);
	
	if (pg_num_rows ($res) == 0) {
		echo "<h1>Nenhuma Transportadora encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('transportadora.value=\"\"; codigo.value=\"\"; nome.value=\"\"; cnpj.value=\"\"; window.close();',2500);";
		echo "</script>";
		exit;
	}

}
/*
if (pg_num_rows ($res) == 1 ) {
	$transportadora   = trim(pg_fetch_result($res,0,transportadora));
	$nome             = trim(pg_fetch_result($res,0,nome));
	$cnpj             = trim(pg_fetch_result($res,0,cnpj));
	$fantasia         = trim(pg_fetch_result($res,0,fantasia));
	$codigo_interno   = trim(pg_fetch_result($res,0,codigo_interno));

	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "transportadora.value = '$transportadora' ; \n";
	echo "codigo.value='$codigo_interno' ; \n";
	echo "nome.value='$nome'; \n";
	echo "cnpj.value = '$cnpj'; \n";
	echo "this.close() ; \n";
	echo "-->\n";
	echo "</script>\n";
	exit;
}
*/
if (pg_num_rows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
		$transportadora   = trim(pg_fetch_result($res,$i,transportadora));
		$nome             = trim(pg_fetch_result($res,$i,nome));
		$cnpj             = trim(pg_fetch_result($res,$i,cnpj));
		$fantasia         = trim(pg_fetch_result($res,$i,fantasia));
		$codigo_interno   = trim(pg_fetch_result($res,$i,codigo_interno));

		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$codigo_interno</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: transportadora.value = '$transportadora' ; codigo.value='$codigo_interno' ; nome.value='$nome'; cnpj.value = '$cnpj';  this.close() \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>