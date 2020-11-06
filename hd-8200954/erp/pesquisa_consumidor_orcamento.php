<?
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include "autentica_usuario_empresa.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>Pesquisa Clientes.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="../imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["nome"]) > 3) {
	$nome = strtoupper (trim ($_GET["nome"]));
	
	echo "<br>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>:  <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_pessoa.*
			FROM        tbl_pessoa 
			LEFT JOIN   tbl_pessoa_cliente USING(pessoa)
			WHERE       tbl_pessoa.nome ILIKE '%$nome%'
			AND         tbl_pessoa_cliente.empresa=$login_empresa
			ORDER BY    tbl_pessoa.nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>Cliente '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('nome.value=\"\",cnpj.value=\"\",window.close();',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen(trim($_GET["cnpj"])) > 10) {
	$cnpj = strtoupper (trim ($_GET["cnpj"]));
	$cnpj = str_replace ("-","",$cnpj);
	$cnpj = str_replace (".","",$cnpj);
	$cnpj = str_replace ("/","",$cnpj);
	$cnpj = str_replace (" ","",$cnpj);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CPF/CNPJ</b> do cliente: <i>$cnpj</i> </font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_pessoa.*
			FROM        tbl_pessoa 
			LEFT JOIN   tbl_pessoa_cliente USING (pessoa)
			WHERE       tbl_pessoa.cnpj = '$cnpj'
			AND         tbl_pessoa_cliente.empresa=$login_empresa
			ORDER BY    tbl_pessoa.nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>CPF/CNPJ. '$cnpj' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}
}else{

	echo "<h2>Digite ao menos 4 letras para pesquisar por nome, ou 11 dígitos para o CPF/CNPJ</h2>";
	exit;

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Cliente</b>: ";

	echo " <i>$cnpj</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_pessoa.*
			FROM        tbl_pessoa 
			LEFT JOIN   tbl_pessoa_empresa USING (pessoa)
			WHERE tbl_pessoa_empresa.empresa=$login_empresa
			ORDER BY    tbl_pessoa.nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>Cliente não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
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
		$cliente_cliente     = trim (pg_result ($res,$i,pessoa));
		$cliente_empresa     = trim (pg_result ($res,$i,empresa));
		$cliente_nome        = trim (pg_result ($res,$i,nome));
		$cliente_endereco    = trim (pg_result ($res,$i,endereco));
		$cliente_cnpj        = trim (pg_result ($res,$i,cnpj));
		$cliente_endereco    = trim (pg_result ($res,$i,endereco));
		$cliente_numero      = trim (pg_result ($res,$i,numero));
		$cliente_complemento = trim (pg_result ($res,$i,complemento));
		$cliente_bairro      = trim (pg_result ($res,$i,bairro));
		$cliente_cidade      = trim (pg_result ($res,$i,cidade));
		$cliente_estado      = trim (pg_result ($res,$i,estado));
		$cliente_pais        = trim (pg_result ($res,$i,pais));
		$cliente_fone_residencial    = trim (pg_result ($res,$i,fone_residencial));
		$cliente_fone_comercial      = trim (pg_result ($res,$i,fone_comercial));
		$cliente_cel          = trim (pg_result ($res,$i,cel));
		$cliente_fax          = trim (pg_result ($res,$i,fax));
		$cliente_email        = trim (pg_result ($res,$i,email));
		$cliente_nome_fantasia= trim (pg_result ($res,$i,nome_fantasia));
		$cliente_ie           = trim (pg_result ($res,$i,ie));
		$cliente_cep          = trim (pg_result ($res,$i,cep));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "<a href=\"javascript: opener.document.location = '$retorno?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
		echo "<a href=\"javascript: cliente.value='$cliente_cliente' ;  nome.value='$cliente_nome' ; cnpj.value = '$cliente_cnpj' ; cidade.value='$cliente_cidade' ; fone1.value='$cliente_fone_residencial' ; fone2.value='$cliente_fone_comercial' ; fone3.value='$cliente_cel' ; fone4.value='$cliente_fax' ; endereco.value='$cliente_endereco' ; numero.value='$cliente_numero' ; complemento.value='$cliente_complemento' ; bairro.value='$cliente_bairro' ; cep.value='$cliente_cep' ; estado.value='$cliente_estado' ; email.value='$cliente_email' ; this.close(); \">\n";
		
		}
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$cliente_nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}

?>


</body>
</html>