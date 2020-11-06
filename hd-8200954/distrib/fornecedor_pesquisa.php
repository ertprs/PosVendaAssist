<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Fornecedores.. </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">
<img src="../admin/imagens_admin/pesquisa_fornecedor.gif">
<br>

<?

$tipo = trim (strtolower ($_GET['tipo']));

if ($tipo == "nome") {
	$nome = strtoupper (trim ($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do Fornecedor</b>: <i>$nome</i></font>";
	echo "<p>";

	$sql = "SELECT 	tbl_fornecedor.*,
					tbl_cidade.nome as nome_cidade,
					tbl_cidade.estado as nome_estado
			FROM	tbl_fornecedor
			JOIN	tbl_fornecedor_fabrica USING (fornecedor)
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE	tbl_fornecedor.nome ILIKE '%$nome%' 
			AND     tbl_fornecedor_fabrica.fabrica IN (".implode(",", $fabricas).")
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

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Fornecedor</b>: <i>$cnpj</i></font>";
	echo "<p>";

	$sql = "SELECT   *
			FROM     tbl_fornecedor
			JOIN     tbl_fornecedor_fabrica USING (fornecedor)
			WHERE    tbl_fornecedor.cnpj ILIKE '%$cnpj%'
			AND      tbl_fornecedor_fabrica.fabrica IN (".implode(",", $fabricas).")
			ORDER BY tbl_fornecedor.nome";
	
	
	$sql = "SELECT      tbl_fornecedor.fornecedor        ,
						tbl_fornecedor.nome              ,
						tbl_fornecedor.cnpj              ,
						tbl_fornecedor.endereco          ,
						tbl_fornecedor.ie                ,
						tbl_fornecedor.email             ,
						tbl_fornecedor.numero            ,
						tbl_fornecedor.bairro            ,
						tbl_fornecedor.cep               ,
						tbl_fornecedor.fone1             ,
						tbl_fornecedor.fone2             ,
						tbl_fornecedor.fax               ,
						tbl_fornecedor.site              ,
						tbl_cidade.nome as nome_cidade   ,
						tbl_cidade.estado as nome_estado
			FROM        tbl_fornecedor
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE       tbl_fornecedor.cnpj ILIKE '%$cnpj%'
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

if ($tipo == "cnpj" OR $tipo == "nome") {

	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$fornecedor     = trim(pg_result($res,$i,fornecedor));
		$nome           = trim(pg_result($res,$i,nome));
		$cnpj           = trim(pg_result($res,$i,cnpj));
		$endereco       = trim(pg_result($res,$i,endereco));
		$nome_cidade    = trim(pg_result($res,$i,nome_cidade));
		$estado         = trim(pg_result($res,$i,nome_estado));
		$ie             = trim(pg_result($res,$i,ie));
		$email          = trim(pg_result($res,$i,email));
		$numero         = trim(pg_result($res,$i,numero));
		$bairro         = trim(pg_result($res,$i,bairro));
		$cep            = trim(pg_result($res,$i,cep));
		$fone1          = trim(pg_result($res,$i,fone1));
		$fone2          = trim(pg_result($res,$i,fone2));
		$fax            = trim(pg_result($res,$i,fax));
		$site           = trim(pg_result($res,$i,site));
		
	
		$nome = str_replace ('"','',$nome);
		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		
		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?fornecedor=$fornecedor'; bairro.value = '$bairro' ; numero.value = '$numero' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: nome.value = '$nome' ; ie.value = '$ie' ; cnpj.value = '$cnpj' ; endereco.value = '$endereco'; cidade.value = '$nome_cidade' ; estado.value = '$estado'; bairro.value = '$bairro' ; numero.value = '$numero' ; cep.value = '$cep' ; fone1.value = '$fone1' ; fone2.value = '$fone2' ; fax.value = '$fax' ; email.value = '$email' ; site.value = '$site' ; this.close() ; \" >";
		}
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		//echo "<td>\n";
		//echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade</font>\n";
		//echo "</td>\n";

		echo "</tr>\n";
	}//FOR
	echo "</table>\n";
}

if ($tipo == "cnpj2") {

	$cnpj = strtoupper (trim ($_GET["campo"]));
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("-","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace (" ","",$cnpj);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Fornecedor</b>: <i>$cnpj</i></font>";
	echo "<p>";

	$sql = "SELECT      tbl_fornecedor.fornecedor        ,
						tbl_fornecedor.nome              ,
						tbl_fornecedor.cnpj
			FROM        tbl_fornecedor
			WHERE       tbl_fornecedor.cnpj ILIKE '%$cnpj%'
			ORDER BY tbl_fornecedor.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>CNPJ '$cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}else{
		echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$fornecedor     = trim(pg_result($res,$i,fornecedor));
			$nome           = trim(pg_result($res,$i,nome));
			$cnpj           = trim(pg_result($res,$i,cnpj));

			$nome = str_replace ('"','',$nome);
			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			
			echo "<tr>\n";

			echo "<td>\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
			echo "</td>\n";

			echo "<td>\n";
				echo "<a href=\"javascript: fornecedor.value = '$fornecedor' ; nome.value = '$nome' ; cnpj.value = '$cnpj' ; this.close() ; \" >";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
			echo "</a>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}//FOR
	echo "</table>\n";

	}
}//IF CNPJ

if ($tipo == "nome2") {

	$nome = strtoupper (trim ($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Fornecedor</b>: <i>$cnpj</i></font>";
	echo "<p>";

	$sql = "SELECT      tbl_posto.posto        ,
						tbl_posto.nome         
			FROM        tbl_posto
			WHERE       tbl_posto.nome ILIKE '%$nome%'
			ORDER BY tbl_posto.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Fornecedor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}else{
		echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$fornecedor     = trim(pg_result($res,$i,posto));
			$nome           = trim(pg_result($res,$i,nome));

			$nome = str_replace ('"','',$nome);
//			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			
			echo "<tr>\n";

			echo "<td>\n";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
			echo "</td>\n";

			echo "<td>\n";
				echo "<a href=\"javascript: nome.value = '$nome' ; fornecedor.value = '$fornecedor' ; this.close() ; \" >";
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
			echo "</a>\n";
			echo "</td>\n";
			
			echo "</tr>\n";
		}//FOR
	echo "</table>\n";

	}
}//IF CNPJ

?>

</body>
</html>