<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa grupo_empresa.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["grupo_descricao"]) > 0) {
	$grupo_empresa_descricao = strtoupper (trim ($_GET["grupo_descricao"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do grupo</b>: <i>$grupo_descricao</i></font>";
	echo "<p>";
	$sql = "SELECT  tbl_grupo_empresa.grupo_empresa,
					tbl_grupo_empresa.nome_grupo   ,
					tbl_grupo_empresa.descricao
			FROM  tbl_grupo_empresa
			WHERE tbl_grupo_empresa.descricao     ILIKE '%$grupo_descricao%'
			AND   tbl_grupo_empresa.fabrica = $login_fabrica
			ORDER BY tbl_grupo_empresa.descricao";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Grupo '$grupo_descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($_GET["nome_grupo"]) > 0) {
	$nome_grupo = strtoupper (trim ($_GET["nome_grupo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Nome do Grupo</b>: <i>$nome_grupo</i></font>";
	echo "<p>";
	$sql = "SELECT  tbl_grupo_empresa.grupo_empresa          ,
					tbl_grupo_empresa.nome_grupo   ,
					tbl_grupo_empresa.descricao
			FROM  tbl_grupo_empresa
			WHERE  tbl_grupo_empresa.nome_grupo      ILIKE '%$nome_grupo%'
			AND   tbl_grupo_empresa.fabrica = $login_fabrica
			ORDER BY tbl_grupo_empresa.descricao";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Nome '$nome_grupo' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1 ) {
	echo "<script language='javascript'>";
	echo "grupo_empresa.value     ='".pg_result($res,0,grupo_empresa)."'; ";
	echo "nome_grupo.value        ='".str_replace("'","",pg_result($res,0,nome_grupo))."'; ";
	echo "grupo_descricao.value   ='".pg_result($res,0,descricao)."'; ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$grupo_empresa    = trim(pg_result($res,$i,grupo_empresa));
		$nome_grupo       = trim(pg_result($res,$i,nome_grupo));
		$descricao        = trim(pg_result($res,$i,descricao));

		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$nome_grupo</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: grupo_empresa.value='$grupo_empresa' ;  nome_grupo.value='$nome_grupo' ; grupo_descricao.value='$descricao' ; ";
		echo "this.close(); \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$descricao</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
}

?>


</body>
</html>