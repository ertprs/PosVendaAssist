<?
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";
include "autentica_usuario_assist.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<? if ($sistema_lingua=='ES') { ?>
	<title>Pesquisa distribuidores.. </title>
<? } else { ?>
	<title>Pesquisa Revendedores.. </title>
<? } ?>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_revenda<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<?

if (strlen($HTTP_GET_VARS["nome"]) > 3) {
	$nome = strtoupper (trim ($HTTP_GET_VARS["nome"]));
	
	echo "<br><font face='Arial, Verdana, Times, Sans' size='2'>";
	if($sistema_lingua == "ES") { 
		echo "Resultado de la busca: ";
	}else{ 	
		echo "Pesquisando por <b>nome da Revenda</b>: ";
	}
	echo "<i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
						tbl_revenda.cnpj              ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado
			FROM        tbl_revenda
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_estado using(estado)
			WHERE       tbl_revenda.nome ILIKE '%$nome%' ";
				if ($login_fabrica == 20) $sql .= " AND tbl_revenda.pais='$login_pais'";
			 $sql .= "  ORDER BY    tbl_cidade.nome,tbl_revenda.bairro,tbl_revenda.nome";

	$res = pg_exec ($con,$sql);
	//echo $sql;
	if (pg_numrows ($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Distribuidor '$nome' no encuentrado</h1>";
		else echo "<h1>Revenda '$nome' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('nome.value=\"\",cnpj.value=\"\",',2500);";
		echo "</script>";
		exit;
	}

}elseif (strlen($HTTP_GET_VARS["cnpj"]) > 8) {
	$nome = strtoupper (trim ($HTTP_GET_VARS["cnpj"]));
	$nome = str_replace ("-","",$nome);
	$nome = str_replace (".","",$nome);
	$nome = str_replace ("/","",$nome);
	$nome = str_replace (" ","",$nome);
	
	echo "<br><font face='Arial, Verdana, Times, Sans' size='2'>";
	if($sistema_lingua == "ES") { 
		echo "Resultado de la busca: ";
	}else{ 
		echo "Pesquisando por <b>CNPJ da Revenda</b>:";
	}
	echo " <i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
						tbl_revenda.cnpj              ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado
			FROM        tbl_revenda
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_estado using(estado)
			WHERE       tbl_revenda.cnpj ILIKE '%$nome%' ";
				if ($login_fabrica == 20) $sql .= " AND tbl_estado.pais='$login_pais'";
			 $sql .= " ORDER BY    tbl_revenda.nome";
	$res = pg_exec ($con,$sql);
	// echo $sql;
	if (pg_numrows ($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Identificación '$nome' no encuentrada</h1>";
		else echo "<h1>C.N.P.J. '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('nome.value=\"\",cpf.value=\"\",',2500);";
		echo "</script>";
		exit;
	}
}else{
	if($sistema_lingua == "ES") echo "<h2>Digite al minus 4 letras para buscar por nombre o los 8 primeros dígitos de la identificación.</h2>";
	else                        echo "<h2>Digite ao menos 4 letras para pesquisar por nome, ou os 8 primeiros dígitos do CNPJ</h2>";
	exit;
	
	echo "<br><font face='Arial, Verdana, Times, Sans' size='2'>";
if($sistema_lingua == "ES")  echo "Resultado de la busca: ";
	else echo "Pesquisando por <b>Revenda</b>:";
 	echo "<i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_revenda.nome              ,
						tbl_revenda.revenda           ,
						tbl_revenda.cnpj              ,
						tbl_revenda.cidade            ,
						tbl_revenda.fone              ,
						tbl_revenda.endereco          ,
						tbl_revenda.numero            ,
						tbl_revenda.complemento       ,
						tbl_revenda.bairro            ,
						tbl_revenda.cep               ,
						tbl_revenda.email             ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado
			FROM        tbl_revenda
			LEFT JOIN   tbl_cidade USING (cidade)
			ORDER BY    tbl_revenda.nome";

	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Distribuidor no encuentrado</h1>";
		else echo "<h1>Revenda não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout(\"nome.value='',cpf.value='',window.close();\",2500);";
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
		$revenda    = trim(pg_result($res,$i,revenda));
		$nome       = trim(pg_result($res,$i,nome));
		$cnpj       = trim(pg_result($res,$i,cnpj));
		$bairro     = trim(pg_result($res,$i,bairro));
		$cidade     = trim(pg_result($res,$i,nome_cidade));

		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript: opener.document.location = retorno + '?revenda=$revenda' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: nome.value='" . pg_result ($res,$i,nome) . "' ; cnpj.value = '" . pg_result ($res,$i,cnpj) . "' ; cidade.value='" . $cidade . "' ; fone.value='" . pg_result ($res,$i,fone) . "' ; endereco.value='" . pg_result ($res,$i,endereco) . "' ; numero.value='" . pg_result ($res,$i,numero) . "' ; complemento.value='" . pg_result ($res,$i,complemento) . "' ; bairro.value='" . pg_result ($res,$i,bairro) . "' ; cep.value='" . pg_result ($res,$i,cep) . "' ; estado.value='" . pg_result ($res,$i,estado) . "'; email.value='" . pg_result ($res,$i,email) . "' ; this.close(); \">\n";
		}
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$bairro</font>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cidade</font>\n";
		echo "</td>\n";


		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>

</body>
</html>