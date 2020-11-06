<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$linha = $_GET['linha'];
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
<title> Pesquisa Peças... </title>
<link type="text/css" rel="stylesheet" href="css/css.css">

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
	<img src='imagens_admin/pesquisa_pecas.gif'>
</div>

<script language="JavaScript">
	window.moveTo (100,100);
</script>


<script language="JavaScript">
<!--
function retorno(referencia, descricao) {
	opener.window.document.frmdespesa.peca_referencia_<? echo $linha; ?>.value = referencia;
	opener.window.document.frmdespesa.peca_descricao_<? echo $linha; ?>.value  = descricao;
	opener.window.document.frmdespesa.peca_qtde_<? echo $linha; ?>.focus();
	window.close();
}
// -->
</script>

<br>

<?

if (strlen($referencia) > 0) {
	echo "<div class='titulo_tabela'>Pesquisando por <b>Referência</b>: $referencia</div>";

	
	$referencia = strtoupper(trim($referencia));
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace (" ","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (".","",$referencia);

	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_peca.origem,
					tbl_peca.estoque,
					tbl_peca.unidade,
					tbl_peca.ativo
			FROM     tbl_peca
			JOIN     tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
			WHERE    tbl_peca.referencia_pesquisa ilike '%$referencia%'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>Referência '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2000);";
		echo "</script>";
		exit;
	}
}


if (strlen($nome) > 0) {
	echo "<div class='titulo_tabela'>Pesquisando por <b>Nome</b>: $nome</div>";
	
	
	$nome = strtoupper($nome);
	
	$sql = "SELECT  tbl_peca.peca      ,
					tbl_peca.referencia,
					tbl_peca.descricao
			FROM    tbl_peca
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
			WHERE   tbl_fabrica.fabrica = $login_fabrica
			AND     tbl_peca.descricao ilike '%$nome%'
			ORDER BY tbl_peca.descricao;";
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
	$codigo = trim(pg_result ($res,0,referencia));
	$nome   = trim(pg_result ($res,0,descricao));
	$nome   = str_replace("'","", $nome);
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.frmdespesa.peca_referencia_$linha.value = '$codigo'; \n";
	echo "opener.window.document.frmdespesa.peca_descricao_$linha.value  = '$nome';   \n";
	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0' class='tabela'>";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$codigo = trim(pg_result ($res,$i,referencia));
		$nome   = trim(pg_result ($res,$i,descricao));

		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		
		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>";
		echo "<a href=\"javascript: retorno('$codigo', '$nome')\">";
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
