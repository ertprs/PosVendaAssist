<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa OS</title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<?

if (strlen($_GET["sua_os"]) > 0) {
	$sua_os = trim ($_GET["sua_os"]);
	
	echo "<br>";
	echo "<CENTER><font face='Verdana, Times, Sans' size='2'>Pesquisando por <b>OS número</b>: <i>$sua_os</i></font></CENTER>";
	echo "<p>";

	
	$sql = "SELECT	to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura    ,
					tbl_os.consumidor_nome                                         ,
					tbl_os.revenda_nome                                            ,
					tbl_os.serie                               AS produto_serie    ,
					tbl_produto.descricao                      AS produto_descricao
			FROM	tbl_os
			JOIN	tbl_produto USING(produto)
			WHERE	tbl_os.fabrica = $login_fabrica
			AND		tbl_os.sua_os  = '$sua_os'";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 0) {
		echo "<CENTER><h1>OS '$sua_os' não encontrada.</h1></CENTER>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2000);";
		echo "</script>";
	}else{
		echo "<script language='JavaScript'>";
		echo "<!--\n";
		echo "this.focus();\n";
		echo "// -->\n";
		echo "</script>\n";
	
		echo "<table width='90%' border='0' align='center' bgcolor='#eeeeee' cellspacing='1' cellpadding='1'>\n";
		echo "<tr>\n";
		echo "<td colspan='2'><font face='Verdana, Times, Sans' size='-1' color='#000000'><B><CENTER>Order de Serviço já digitada no sistema.</CENTER></B></font><BR></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'><B>Data</B></font></td>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,0,data_abertura)."</font></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'><B>Cliente</B></font></td>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,0,consumidor_nome)."</font></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'><B>Revenda</B></font></td>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,0,revenda_nome)."</font></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'><B>Produto</B></font></td>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,0,produto_descricao)."</font></td>\n";
		echo "</tr>\n";
		echo "<tr>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'><B>Série</B></font></td>\n";
		echo "<td><font face='Verdana, Times, Sans' size='-1' color='#000000'>".pg_result($res,0,produto_serie)."</font></td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		echo "<CENTER>";
		echo "<a href='javascript: window.opener.document.forms[0].sua_os.focus(); window.close(); '><font face='Verdana, Times, Sans' size='1' color='#000099'>Fechar</font></a>";
		echo "</CENTER>";

	}
}

?>

</body>
</html>