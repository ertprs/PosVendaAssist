<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Distribuidores.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="../imagens/pesquisa_revenda_es.gif">

<?

if (strlen($HTTP_GET_VARS["nome"]) > 0) {
	$nome = strtoupper (trim ($HTTP_GET_VARS["nome"]));
	
	echo "<br><font face='Arial, Verdana, Times, Sans' size='2'>";
	echo "Resultado de la busca: ";
	echo " <i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_revenda.nome                       ,
						tbl_revenda.revenda                    ,
						tbl_revenda.cnpj                       ,
						tbl_revenda.cidade                     ,
						tbl_revenda.fone                       ,
						tbl_revenda.endereco                   ,
						tbl_revenda.numero                     ,
						tbl_revenda.complemento                ,
						tbl_revenda.bairro                     ,
						tbl_revenda.cep                        ,
						tbl_revenda.email                      ,
						tbl_cidade.nome         AS nome_cidade ,
						tbl_cidade.estado                      
			FROM        tbl_revenda
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_estado using(estado)
			WHERE       tbl_revenda.nome ILIKE '%$nome%'
			AND 		tbl_estado.pais = '$login_pais'
			ORDER BY    tbl_cidade.estado,tbl_cidade.nome,tbl_revenda.bairro,tbl_revenda.nome";
	$res = pg_exec ($con,$sql);
	//if($ip=="201.68.13.116") echo $sql;
	if (pg_numrows ($res) == 0) {
		echo "<h1>Revenda '$nome' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

}elseif (strlen($HTTP_GET_VARS["cnpj"]) > 0) {
	$nome = strtoupper (trim ($HTTP_GET_VARS["cnpj"]));
	
	echo "<br><font face='Arial, Verdana, Times, Sans' size='2'>";
	echo "Resultado de la busca: ";
	echo " <i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_revenda.nome              ,
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
			WHERE       tbl_revenda.nome ILIKE '%$nome%'
			AND tbl_estado.pais='$login_pais'
			ORDER BY    tbl_revenda.nome";
echo $sql;
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<h1>Identificación '$nome' no encuentrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}else{
	echo "<br><font face='Arial, Verdana, Times, Sans' size='2'>";
	echo "Resultado de la busca: ";
 	echo "<i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_revenda.nome              ,
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
			WHERE       tbl_revenda.cnpj ILIKE '%$cpf%'
			AND tbl_estado.pais='$login_pais'
			ORDER BY    tbl_revenda.nome";
	$res = pg_exec ($con,$sql);
	echo $sql;
	if (pg_numrows($res) == 0) {
		echo "<h1>Distribuidor no encuentrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if (pg_numrows($res) == 1) {
	echo "<script language='JavaScript'>";
	echo "nome.value        ='".pg_result($res,0,nome)."'; ";
	echo "cnpj.value        ='".pg_result($res,0,cnpj)."'; ";
	echo "cidade.value      ='".pg_result($res,0,cidade)."'; ";
	echo "fone.value        ='".pg_result($res,0,fone)."'; ";
	echo "endereco.value    ='".pg_result($res,0,endereco)."'; ";
	echo "numero.value      ='".pg_result($res,0,numero)."'; ";
	echo "complemento.value ='".pg_result($res,0,complemento)."'; ";
	echo "bairro.value      ='".pg_result($res,0,bairro)."'; ";
	echo "cep.value         ='".pg_result($res,0,cep)."'; ";
	echo "estado.value      ='".pg_result($res,0,estado)."'; ";
	echo "email.value       ='".pg_result($res,0,email)."'; ";
	if ($_GET["proximo"] == "t" ) echo "proximo.focus(); ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}

if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$nome        = trim(pg_result($res,$i,nome));
		$cnpj        = trim(pg_result($res,$i,cnpj));
		$cidade      = trim(pg_result($res,$i,nome_cidade));
		$fone        = trim(pg_result($res,$i,fone));
		$endereco    = trim(pg_result($res,$i,endereco));
		$numero      = trim(pg_result($res,$i,numero));
		$complemento = trim(pg_result($res,$i,complemento));
		$bairro      = trim(pg_result($res,$i,bairro));
		$cep         = trim(pg_result($res,$i,cep));
		$estado      = trim(pg_result($res,$i,estado));
		$email       = trim(pg_result($res,$i,email));

		echo "<tr>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-2' color='#000000'>$cnpj</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		echo "<a href=\"javascript: nome.value='$nome'; cnpj.value='$cnpj'; cidade.value='$cidade'; fone.value='$fone'; endereco.value='$endereco'; numero.value='$numero'; complemento.value='$complemento'; bairro.value='$bairro'; cep.value='$cep'; estado.value='$estado'; email.value='$email'; ";
		if ($_GET["proximo"] == "t" ) echo "proximo.focus(); ";
		echo "this.close(); \">\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
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