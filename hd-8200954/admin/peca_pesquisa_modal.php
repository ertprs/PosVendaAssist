<div style="float:left;width:97%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
</div>
<div style="float:right;width:3%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
<a href="#" onclick="closeMessage()" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
</div>
<div style="background:transparent;position: relative; height: 460px;width:100%;overflow:auto">
<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />
</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">

<br>
<div style="float:left;color:#596d9b;width:100%;background:;height:27px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
<?
$tipo = trim (strtolower ($_GET['tipo']));
if ($tipo == "descricao") {

	$descricao = trim (strtoupper($_GET["campo"]));
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></font>";
	echo "<p>";
	
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
			WHERE    tbl_peca.descricao ilike '%$descricao%'
			AND      tbl_peca.fabrica = $login_fabrica
			ORDER BY tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);

	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$descricao' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "referencia") {

	$referencia = trim (strtoupper($_GET["campo"]));
	$referencia = str_replace (".","",$referencia);
	$referencia = str_replace ("-","",$referencia);
	$referencia = str_replace ("/","",$referencia);
	$referencia = str_replace (" ","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	echo "<p>";

	//where tbl_peca.referencia_pesquisa ilike '%$referencia%'
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

	if (@pg_numrows ($res) == 0) {
		echo "<h1>Peça '$referencia' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}
echo "</div>";
echo "<script language='JavaScript'>\n";
echo "<!--\n";
echo "this.focus();\n";
echo "// -->\n";
echo "</script>\n";
?>
	<table width='99%' cellpadding="0" cellspacing="0" border="0" class="display" id="modal_peca">
		<thead>
		<tr style="text-align: left;background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;">
			<td>Código</td>
			<td>Descrição</td>
		</tr>
		</thead>
		<tbody>
<?php
for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$peca       = trim(pg_result($res,$i,peca));
	$referencia = trim(pg_result($res,$i,referencia));
	$descricao  = trim(pg_result($res,$i,descricao));
	$cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';
	
	$descricao = str_replace ('"','',$descricao);
	echo "<tr bgcolor='$cor'>\n";
	
	echo "<td>\n";
	echo "<a href='#' onclick='retorna_dados_peca(\"$referencia\",\"$descricao\");'>";
	echo "$referencia\n";
	echo "</a>";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<a href='#' onclick='retorna_dados_peca(\"$referencia\",\"$descricao\");'>";
	echo "$descricao\n";
	echo "</a>\n";
	echo "</td>\n";
	echo "</tr>";
}
echo "</tbody></table>\n";

?>

</body>
</html>
