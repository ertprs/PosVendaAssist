<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Posto... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>
</head>

<body>

<script language="JavaScript">
<!--
function retorno(posto, codigo, nome, cnpj, endereco, numero, complemento, bairro, cep, cidade, estado, email, fone, contato, capital_interior, senha) {
	opener.window.document.frmposto.posto.value            = posto;
	opener.window.document.frmposto.codigo.value           = codigo;
	opener.window.document.frmposto.nome.value             = nome;
	opener.window.document.frmposto.cnpj.value             = cnpj;
	opener.window.document.frmposto.endereco.value         = endereco;
	opener.window.document.frmposto.numero.value           = numero;
	opener.window.document.frmposto.complemento.value      = complemento;
	opener.window.document.frmposto.bairro.value           = bairro;
	opener.window.document.frmposto.cep.value              = cep;
	opener.window.document.frmposto.cidade.value           = cidade;
	opener.window.document.frmposto.estado.value           = estado;
	opener.window.document.frmposto.email.value            = email;
	opener.window.document.frmposto.fone.value             = fone;
	opener.window.document.frmposto.contato.value          = contato;
	opener.window.document.frmposto.capital_interior.value = capital_interior;
	opener.window.document.frmposto.senha.value            = senha;
	window.close();
}
// -->
</script>

<br>

<?
if (strlen($HTTP_GET_VARS["codigo"]) > 0) {
	$codposto = strtoupper($HTTP_GET_VARS["codigo"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do posto</b>: <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT  tbl_posto_fabrica.posto       ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.cnpj                ,
					tbl_posto_fabrica.contato_endereco    AS endereco       ,
					tbl_posto_fabrica.contato_numero      AS numero         ,
					tbl_posto_fabrica.contato_complemento AS complemento    ,
					tbl_posto_fabrica.contato_bairro      AS bairro         ,
					tbl_posto_fabrica.contato_cep         AS cep            ,
					tbl_posto_fabrica.contato_cidade      AS cidade         ,
					tbl_posto_fabrica.contato_estado      AS estado         ,
					tbl_posto_fabrica.contato_email       AS email          ,
					tbl_posto.fone                ,
					tbl_posto.contato             ,
					tbl_posto.capital_interior    ,
					tbl_posto_fabrica.senha
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.codigo_posto = '$codposto'
			ORDER BY tbl_posto.nome;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Código '$codposto' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('opener.window.document.frmposto.nome_posto.value=\"\",opener.window.document.frmposto.codigo_posto.focus()',2500);";
		echo "</script>";
		exit;
	}
}

if (strlen($HTTP_GET_VARS["nome"]) > 0) {
	$nome = strtoupper($HTTP_GET_VARS["nome"]);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do posto</b>: <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT  tbl_posto_fabrica.posto       ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.cnpj                ,
					tbl_posto_fabrica.contato_endereco    AS endereco   ,
					tbl_posto_fabrica.contato_numero      AS numero     ,
					tbl_posto_fabrica.contato_complemento AS complemento,
					tbl_posto_fabrica.contato_bairro      AS bairro     ,
					tbl_posto_fabrica.contato_cep         AS cep        ,
					tbl_posto_fabrica.contato_cidade      AS cidade     ,
					tbl_posto_fabrica.contato_estado      AS estado     ,
					tbl_posto_fabrica.contato_email       AS email      ,
					tbl_posto.fone                ,
					tbl_posto.contato             ,
					tbl_posto.capital_interior    ,
					tbl_posto_fabrica.senha
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.nome ilike '%$nome%'
			ORDER BY tbl_posto.nome;";
	$res = pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) == 0) {
		echo "<h1>Posto '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('opener.window.document.frmposto.nome_posto.value=\"\",opener.window.document.frmposto.codigo_posto.focus()',2500);";
		echo "</script>";
		exit;
	}
}


if (@pg_numrows ($res) == 1 ) {
	$posto            = trim(pg_result($res,0,posto));
	$codigo           = trim(pg_result($res,0,codigo_posto));
	$nome             = trim(pg_result($res,0,nome));
	$cnpj             = trim(pg_result($res,0,cnpj));
	$cnpj             = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
	$endereco         = trim(pg_result($res,0,endereco));
	$numero           = trim(pg_result($res,0,numero));
	$complemento      = trim(pg_result($res,0,complemento));
	$bairro           = trim(pg_result($res,0,bairro));
	$cep              = trim(pg_result($res,0,cep));
	$cidade           = trim(pg_result($res,0,cidade));
	$estado           = trim(pg_result($res,0,estado));
	$email            = trim(pg_result($res,0,email));
	$fone             = trim(pg_result($res,0,fone));
	$contato          = trim(pg_result($res,0,contato));
	$capital_interior = trim(pg_result($res,0,capital_interior));
	$senha            = trim(pg_result($res,0,senha));
	
	echo "<script language=\"JavaScript\">\n";
	echo "<!--\n";
	echo "opener.window.document.frmposto.posto.value            = '$posto'; \n";
	echo "opener.window.document.frmposto.codigo.value           = '$codigo'; \n";
	echo "opener.window.document.frmposto.nome.value             = '$nome'; \n";
	echo "opener.window.document.frmposto.cnpj.value             = '$cnpj'; \n";
	echo "opener.window.document.frmposto.endereco.value         = '$endereco'; \n";
	echo "opener.window.document.frmposto.numero.value           = '$numero'; \n";
	echo "opener.window.document.frmposto.complemento.value      = '$complemento'; \n";
	echo "opener.window.document.frmposto.bairro.value           = '$bairro'; \n";
	echo "opener.window.document.frmposto.cep.value              = '$cep'; \n";
	echo "opener.window.document.frmposto.cidade.value           = '$cidade'; \n";
	echo "opener.window.document.frmposto.estado.value           = '$estado'; \n";
	echo "opener.window.document.frmposto.email.value            = '$email'; \n";
	echo "opener.window.document.frmposto.fone.value             = '$fone'; \n";
	echo "opener.window.document.frmposto.contato.value          = '$contato'; \n";
	echo "opener.window.document.frmposto.capital_interior.value = '$capital_interior'; \n";
	echo "opener.window.document.frmposto.senha.value            = '$senha'; \n";
	echo "window.close();\n";
	echo "// -->\n";
	echo "</script>\n";
}else{
	echo "<script language='JavaScript'>\n";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$posto            = trim(pg_result($res,$i,posto));
		$codigo           = trim(pg_result($res,$i,codigo_posto));
		$nome             = trim(pg_result($res,$i,nome));
		$cnpj             = trim(pg_result($res,$i,cnpj));
		$cnpj             = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		$endereco         = trim(pg_result($res,$i,endereco));
		$numero           = trim(pg_result($res,$i,numero));
		$complemento      = trim(pg_result($res,$i,complemento));
		$bairro           = trim(pg_result($res,$i,bairro));
		$cep              = trim(pg_result($res,$i,cep));
		$cidade           = trim(pg_result($res,$i,cidade));
		$estado           = trim(pg_result($res,$i,estado));
		$email            = trim(pg_result($res,$i,email));
		$fone             = trim(pg_result($res,$i,fone));
		$contato          = trim(pg_result($res,$i,contato));
		$capital_interior = trim(pg_result($res,$i,capital_interior));
		$senha            = trim(pg_result($res,$i,senha));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$codigo</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: retorno('$posto', '$codigo', '$nome', '$cnpj', '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$cidade', '$estado', '$email', '$fone', '$contato', '$capital_interior', '$senha')\">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>