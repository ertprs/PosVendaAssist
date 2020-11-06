<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Fornecedores.. </title>
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

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">
<img src="imagens_admin/pesquisa_fornecedor.gif">
<br>

<?

$tipo = trim (strtolower ($_GET['tipo']));

if ($tipo == "nome") {
	$nome = strtoupper (trim ($_GET["campo"]));

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do Fornecedor</b>: <i>$nome</i></font>";
	//echo "<p>";

	$sql = "SELECT 	tbl_fornecedor.*,
					tbl_cidade.nome as nome_cidade,
					tbl_cidade.estado as nome_estado
			FROM	tbl_fornecedor
			JOIN	tbl_fornecedor_fabrica USING (fornecedor)
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE	tbl_fornecedor.nome ILIKE '%$nome%' 
			AND     tbl_fornecedor_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_fornecedor.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Fornecedor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}

}//IF NOME

if ($tipo == "cnpj") {

	$cnpj = strtoupper (trim ($_GET["campo"]));
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("-","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace (" ","",$cnpj);

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Fornecedor</b>: <i>$cnpj</i></font>";
	//echo "<p>";

	$sql = "SELECT   *
			FROM     tbl_fornecedor
			JOIN     tbl_fornecedor_fabrica USING (fornecedor)
			WHERE    tbl_fornecedor.cnpj ILIKE '%$cnpj%'
			AND      tbl_fornecedor_fabrica.fabrica = $login_fabrica
			ORDER BY tbl_fornecedor.nome";
	
	
	$sql = "SELECT      tbl_fornecedor.fornecedor ,
						tbl_fornecedor.nome  ,
						tbl_fornecedor.cnpj  ,
						tbl_cidade.nome as nome_cidade,
						tbl_cidade.estado as nome_estado
			FROM        tbl_fornecedor
			JOIN        tbl_fornecedor_fabrica USING (fornecedor)
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE       tbl_fornecedor.cnpj ILIKE '%$cnpj%'
			AND         tbl_fornecedor_fabrica.fabrica = $login_fabrica
			GROUP BY	tbl_fornecedor.fornecedor ,
						tbl_fornecedor.nome  ,
						tbl_fornecedor.cnpj  ,
						tbl_cidade.nome      ,
						tbl_cidade.estado    
			ORDER BY tbl_fornecedor.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>CNPJ '$cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}

}//IF CNPJ

	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0' cellspacing='1' class='tabela'>\n";
	if($tipo=="nome")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>nome do Fornecedor</b>: $nome</font></td></tr>";
	if($tipo=="cnpj")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>CNPJ do Fornecedor</b>: $cnpj</font></td></tr>";

	echo "<tr class='titulo_coluna'><td>Cnpj</td><td>Nome</td>";
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$fornecedor		= pg_result ($res,$i,fornecedor);
		$nome			= pg_result ($res,$i,nome);
		$cnpj           = trim(pg_result($res,$i,cnpj));
		$cidade         = trim(pg_result($res,$i,nome_cidade));
		
	
		$nome = str_replace ('"','',$nome);
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		
		if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
		echo "<tr bgcolor='$cor'>\n";

		echo "<td>\n";
		echo "$cnpj";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?fornecedor=$fornecedor' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: nome.value = '$nome' ; cnpj.value = '$cnpj' ; this.close() ; \" >";
		}
		echo "$nome";
		echo "</a>\n";
		echo "</td>\n";
		
		//echo "<td>\n";
		//echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade</font>\n";
		//echo "</td>\n";

		echo "</tr>\n";
	}//FOR
	echo "</table>\n";
?>

</body>
</html>