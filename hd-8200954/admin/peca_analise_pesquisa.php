<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

//include 'cabecalho_pop_pecas_analise.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Peças... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

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

<script language="JavaScript">
<!--
function retornox(peca, referencia, descricao) {
	opener.document.frm_peca_analise.referencia.value = referencia;
	opener.document.frm_peca_analise.descricao.value  = descricao;
	opener.document.frm_peca_analise.descricao.focus()
	window.close();
}
// -->
</script>

<br>

<?

$tipo = trim(strtolower($_GET['tipo']));
if($tipo == "descricao"){
	$descricao = trim(strtoupper($_GET['campo']));
	
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>descrição da peça</b>: <i>$descricao</i></font>";
	//echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_peca
			WHERE    trim(tbl_peca.descricao) ilike '%$descricao%'
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

if($tipo == "referencia"){
	$referencia = trim(strtoupper($_GET['campo']));
	
	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>referência da peça</b>: <i>$referencia</i></font>";
	//echo "<p>";
	
	$sql = "SELECT   *
			FROM     tbl_peca
			WHERE    trim(tbl_peca.referencia) ilike '%$referencia%'
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


	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";
	if($tipo == "descricao" or $tipo == "descricao_pai")
		echo "<tr class='titulo_tabela'><td colspan='7'><font style='font-size:14px;'>Pesquisando por <b>Descrição da Peça</b>: $descricao</font></td></tr>";
	if($tipo == "referencia" or $tipo == "referencia_pai")
		echo "<tr class='titulo_tabela'><td colspan='7'><font style='font-size:14px;'>Pesquisando por <b>Referência da Peça</b>: $referencia</font></td></tr>";

	echo "<tr class='titulo_coluna'><td>Código</td><td>Nome</td>";
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$peca       = trim(pg_result($res,$i,peca));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));

		$descricao		= str_replace ('"','',$descricao);
		$referencia		= str_replace ('"','',$referencia);
		
		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
	
		echo "<tr bgcolor='$cor'>\n";
		
		echo "<td>\n";
		echo "$referencia";
		echo "</td>\n";
		
		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?peca_fora_linha=$peca' ;\" > " ;
		}else{
			echo "<a href=\"javascript: retornox('$peca', '$referencia', '$descricao') ; this.close() ; \" >";
		}

		echo "$descricao";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
//}
?>

</body>
</html>