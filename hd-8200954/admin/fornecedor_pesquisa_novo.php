<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Fornecedores.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens_admin/pesquisa_fornecedor.gif">
<br>

<?

$tipo = trim (strtolower ($_GET['tipo']));

if ($tipo == "nome") {

  if (strlen($_GET["campo"]) > 0) {
	$nome = strtoupper (trim ($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do Fornecedor</b>: <i>$nome</i></font>";
	echo "<p>";

/*	$sql = "SELECT      *
			FROM        tbl_fornecedor
			WHERE       nome ILIKE '%$nome%'
			ORDER BY    nome"; */
	$sql = "SELECT 	tbl_fornecedor.*,
					tbl_cidade.*
			FROM	tbl_fornecedor
			LEFT OUTER JOIN	tbl_cidade 
			ON 		tbl_cidade.cidade = tbl_fornecedor.cidade
			WHERE	tbl_cidade.nome ILIKE '%$nome%'
			ORDER BY    tbl_fornecedor.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>Fornecedor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}
  }
}

if ($tipo == "cnpj") {

  if (strlen($_GET["campo"]) > 0) {
	$cnpj = strtoupper (trim ($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Fornecedor</b>: <i>$cnpj</i></font>";
	echo "<p>";

/*	$sql = "SELECT      *
			FROM        tbl_fornecedor
			WHERE       cnpj ILIKE '%$cnpj%'
			ORDER BY    nome"; */

	$sql = "SELECT 	tbl_fornecedor.*,
					tbl_cidade.*
			FROM	tbl_fornecedor
			LEFT OUTER JOIN	tbl_cidade 
			ON 		tbl_cidade.cidade = tbl_fornecedor.cidade
			WHERE	tbl_fornecedor.cnpj ILIKE '%$cnpj%'
			ORDER BY    tbl_fornecedor.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>CNPJ '$cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}
  }
}

if ($tipo == "ie") {

  if (strlen($_GET["campo"]) > 0) {
	$ie = strtoupper (trim ($_GET["campo"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>IE do Fornecedor</b>: <i>$ie</i></font>";
	echo "<p>";

/*	$sql = "SELECT      *
			FROM        tbl_fornecedor
			WHERE       ie ILIKE '%$ie%'
			ORDER BY    nome"; */
	$sql = "SELECT 	tbl_fornecedor.*,
					tbl_cidade.*
			FROM	tbl_fornecedor
			LEFT OUTER JOIN	tbl_cidade 
			ON 		tbl_cidade.cidade = tbl_fornecedor.cidade
			WHERE	tbl_fornecedor.ie ILIKE '%$ie%'
			ORDER BY    tbl_fornecedor.nome";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<h1>IE '$ie' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}
  }
}

if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0'>\n";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$fornecedor		= pg_result ($res,0,fornecedor);
		$nome			= pg_result ($res,0,nome);
		$endereco		= pg_result ($res,0,endereco);
		$numero			= pg_result ($res,0,numero);
		$bairro			= pg_result ($res,0,bairro);
		$complemento	= pg_result ($res,0,complemento);
		$cidade			= pg_result ($res,0,cidade);
		$estado			= pg_result ($res,0,estado);
		$fone1			= pg_result ($res,0,fone1);
		$fone2			= pg_result ($res,0,fone2);
		$cnpj			= str_replace ("-","",pg_result ($res,0,cnpj));
		$cnpj			= str_replace (".","",$cnpj);
		$cnpj			= str_replace ("/","",$cnpj);
		$cnpj			= substr ($cnpj,0,14);
		$ie				= pg_result ($res,0,ie);
		$fax			= pg_result ($res,0,fax);
		$email			= pg_result ($res,0,email);
		$site			= pg_result ($res,0,site);

		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<a href=\"javascript: fornecedor.value = '" . $fornecedor . "'; nome.value = '" . $nome . "'; endereco.value = '" . $endereco . "'; numero.value = '" . $numero . "'; bairro.value = '" . $bairro . "'; complemento.value = '" . $complemento . "'; cidade.value = '" . $cidade . "'; estado.value = '" . $estado . "'; fone1.value = '" . $fone1 . "'; fone2.value = '" . $fone2 . "'; cnpj.value = '" . $cnpj . "'; ie.value = '" . $ie . "'; fax.value = '" . $fax . "'; email.value = '" . $email . "'; site.value = '" . $site . "' ; this.close(); \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";

		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>