<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Posto </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

<script language="JavaScript">
	window.moveTo (100,100);

function retorno(codigo, nome, posto){
	if (posto == 'origem'){
		opener.window.document.frmdespesa.posto_origem_codigo.value = codigo;
		opener.window.document.frmdespesa.posto_origem_nome.value   = nome;
	}else{
		opener.window.document.frmdespesa.posto_destino_codigo.value = codigo;
		opener.window.document.frmdespesa.posto_destino_nome.value   = nome;
	}
	window.close();
}
</script>

</head>

<body>
<br>

<?

$tipo = $_GET["tipo"];

if ($tipo == "codigo") {
	$codigo = trim($_GET["campo"]);
	
	echo "Pesquisando por <b>Código</b>: <i>$código</i>";
	echo "<p>";

	$sql = "SELECT   tbl_posto.nome,
					 tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    tbl_posto_fabrica.codigo_posto ilike '%$codigo%'
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Código '$codigo' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2000);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "nome") {
	$nome = strtoupper($_GET["campo"]);
	
	echo "Pesquisando por <b>Nome</b>: <i>$nome</i>";
	echo "<p>";
	
	$sql = "SELECT   tbl_posto.nome,
					 tbl_posto_fabrica.codigo_posto
			FROM     tbl_posto
			JOIN     tbl_posto_fabrica USING (posto)
			WHERE    (tbl_posto.nome ilike '%$nome%' OR tbl_posto.nome_fantasia ILIKE '%$nome%')
			AND      tbl_posto_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>Nome '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2000);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows ($res) == 1 ) {
	$codigo      = trim(pg_result ($res,0,codigo_posto));
	$nome        = trim(pg_result ($res,0,nome));

	if ($_GET['posto'] == 'origem'){
		echo "<script language=\"JavaScript\">\n";
		echo "opener.window.document.frmdespesa.posto_origem.value             = '$codigo';      \n";
		echo "opener.window.document.frmdespesa.nome_posto_origem.value        = '$nome';        \n";
		echo "window.close();\n";
		echo "</script>\n";
	}else{
		echo "<script language=\"JavaScript\">\n";
		echo "opener.window.document.frmdespesa.posto_destino.value             = '$codigo';      \n";
		echo "opener.window.document.frmdespesa.nome_posto_destino.value        = '$nome';        \n";
		echo "window.close();\n";
		echo "</script>\n";
	}
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$codigo      = trim(pg_result ($res,$i,codigo_posto));
		$nome        = trim(pg_result ($res,$i,nome));

		echo "<tr>";
		
		echo "<td>";
		echo "<a href=\"javascript: retorno('$codigo', '$nome', '".$_GET['posto']."')\">";
		echo "<font size='-1'>$codigo</font>";
		echo "</a>";
		echo "</td>";
		
		echo "<td>";
		echo "<font size='-1'>$nome</font>";
		echo "</td>";
		
		echo "</tr>";
	}
	echo "</table>";
}
?>

</body>
</html>