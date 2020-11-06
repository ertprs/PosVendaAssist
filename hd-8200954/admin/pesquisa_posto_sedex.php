<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<title> Pesquisa Postos Autorizados... </title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<script language="JavaScript">
window.moveTo (100,100);
function retorno(codigo, nome, posto) {
	if (posto == 'origem') {
		opener.window.document.frmdespesa.posto_origem.value      = codigo;
		opener.window.document.frmdespesa.nome_posto_origem.value = nome;
	} else {
		opener.window.document.frmdespesa.posto_destino.value      = codigo;
		opener.window.document.frmdespesa.nome_posto_destino.value = nome;
	}
	window.close();
}
</script>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font: bold 11px "Arial";
	border-collapse: collapse;
	border:1px solid #596d9b;
}


</style>
</head>

<body>

<div id="menu"> 
	<img src='imagens_admin/pesquisa_postos.gif'>
</div>

<br>

<?

$tipo = $_GET["tipo"];

if ($tipo == "codigo") {
	$codigo = trim($_GET["campo"]);
	
	echo "<div class='titulo_tabela'>Pesquisando por <b>Código</b>: $código</div>";
	

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
	
	echo "<div class='titulo_tabela'>Pesquisando por <b>Nome</b>: $nome</div>";

	
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
	
	echo "<table width='100%' border='0' class='tabela'>";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$codigo      = trim(pg_result ($res,$i,codigo_posto));
		$nome        = trim(pg_result ($res,$i,nome));

		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";

		echo "<tr bgcolor='$cor'>";
		
		echo "<td>";
		echo "<a href=\"javascript: retorno('$codigo', '$nome', '".$_GET['posto']."')\">";
		echo "$codigo";
		echo "</a>";
		echo "</td>";
		
		echo "<td>";
		echo "$nome";
		echo "</td>";
		
		echo "</tr>";
	}
	echo "</table>";
}
?>

</body>
</html>