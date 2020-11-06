<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peça... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
</head>

<body>

<script language="JavaScript">
<!--
function retorno(codigo, referencia, nome) {
	opener.document.frm_estoque.codigo_peca.value     = codigo;
	opener.document.frm_estoque.referencia_peca.value = referencia;
	opener.document.frm_estoque.nome_peca.value       = nome;
	opener.document.frm_estoque.btnAcao.focus();
	window.close();
}
// -->
</script>

<br>

<?
if (strlen($HTTP_GET_VARS["nome"]) > 0) {
	$nome = strtoupper($HTTP_GET_VARS["nome"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome da peça</b>: <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT  tbl_peca.peca      ,
					tbl_peca.referencia,
					tbl_peca.descricao
			FROM    tbl_peca
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica
			AND     tbl_peca.descricao ilike '%$nome%'
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('opener.window.document.frm_estoque.nome_peca.value=\"\",opener.window.document.frm_estoque.codigo_peca.focus()',2500);";
		echo "</script>";
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$codigo_peca = trim(pg_result($res,0,peca));
	$referencia  = trim(pg_result($res,0,referencia));
	$nome_peca   = trim(pg_result($res,0,descricao));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.frm_estoque.codigo_peca.value     = '$codigo_peca'; \n";
	echo "opener.window.document.frm_estoque.referencia_peca.value = '$referencia'; \n";
	echo "opener.window.document.frm_estoque.nome_peca.value       = '$nome_peca'; \n";
	echo "opener.window.document.frm_estoque.btnAcao.focus();\n";
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
		$codigo_peca = trim(pg_result($res,$i,peca));
		$referencia  = trim(pg_result($res,$i,referencia));
		$nome_peca   = trim(pg_result($res,$i,descricao));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$referencia</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$codigo_peca', '$referencia', '$nome_peca')\">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$nome_peca</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>