<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<? if ($sistema_lingua=='ES') { ?>
	<title>Busca Consumidores.. </title>
<? } else { ?>
	<title>Pesquisa Consumidores.. </title>
<? } ?>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor<? if($sistema_lingua == "ES") echo "_es"; ?>.gif">

<?

if (strlen($_GET["nome"]) > 4) {
	$nome = strtoupper (trim ($_GET["nome"]));
	
	echo "<br>";
	if($sistema_lingua == "ES") { 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por : ";
	}else{ 	
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>:  ";
	}
	echo "<i>$nome</i></font>";
	echo "<p>";
	$sql = "SELECT      tbl_cliente.*                 ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.nome AS consumidor_cidade,
						tbl_cidade.estado             ,
						tbl_cliente_contato.fone      
			FROM        tbl_cliente 
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_cliente_contato USING (cliente)
			WHERE       tbl_cliente.nome ILIKE '%$nome%' 
			ORDER BY    tbl_cliente.nome";

	#echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Consumidor '$nome' no encuentrado</h1>";
		else echo "<h1>Consumidor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('nome.value=\"\",cpf.value=\"\",window.close();',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($_GET["cpf"]) > 10) {
	$cpf = strtoupper (trim ($_GET["cpf"]));
	$cpf = str_replace ("-","",$cpf);
	$cpf = str_replace (".","",$cpf);
	$cpf = str_replace ("/","",$cpf);
	$cpf = str_replace (" ","",$cpf);

	if($sistema_lingua == "ES") { 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por : ";
	}else{ 	
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CPF do consumidor</b>: ";
	}
	echo " <i>$cpf</i></font>";
	echo "<p>";

	$sql = "SELECT      tbl_cliente.*                 ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.nome AS consumidor_cidade,
						tbl_cidade.estado             ,
						tbl_cliente_contato.fone      
			FROM        tbl_cliente 
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_cliente_contato USING (cliente)
			WHERE       tbl_cliente.cpf = '$cpf'
			ORDER BY    tbl_cliente.nome";

	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Identificación '$cpf' no encuentrada</h1>";
		else echo "<h1>C.P.F. '$cpf' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($_GET["fone"]) > 10) {
	$fone = trim ($_GET["fone"]);

	if($sistema_lingua == "ES") { 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por : ";
	}else{ 	
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>TELEFONE do consumidor</b>: ";
	}
	echo " <i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_cliente.*                 ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.nome AS consumidor_cidade,
						tbl_cidade.estado             ,
						tbl_cliente_contato.fone      
			FROM        tbl_cliente 
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_cliente_contato USING (cliente)
			WHERE       tbl_cliente.fone = '$fone'
			ORDER BY    tbl_cliente.nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Telefone '$fone' no encuentrada</h1>";
		else echo "<h1>Telefone '$fone' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close();',2500);";
		echo "</script>";
		exit;
	}
}else{

	if($sistema_lingua == "ES") { 
	echo "<h2>Digite al minus 5 letras para buscar  por nombre, o 11 dígitos para la Identificación</h2>";
	exit;
	}else{ 	
	echo "<h2>Digite ao menos 5 letras para pesquisar por nome, ou 11 dígitos para o CPF</h2>";
	exit;
	}

	if($sistema_lingua == "ES") { 
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Buscando por : ";
	}else{ 	
		echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>Consumidor</b>: ";
	}
	echo " <i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_cliente.*                 ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.nome AS consumidor_cidade,
						tbl_cidade.estado             ,
						tbl_cliente_contato.fone      
			FROM        tbl_cliente 
			LEFT JOIN   tbl_cidade USING (cidade)
			LEFT JOIN   tbl_cliente_contato USING (cliente)
			ORDER BY    tbl_cliente.nome";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Consumidor no encuentrado</h1>";
		else echo "<h1>Consumidor não encontrado</h1>";
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
		$cliente    = trim(pg_result($res,$i,cliente));
		$nome       = trim(pg_result($res,$i,nome));
		$cpf        = trim(pg_result($res,$i,cpf));
		
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cpf</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "<a href=\"javascript: opener.document.location = '$retorno?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
			
			echo "<a href=\"javascript: cliente.value='" . pg_result ($res,$i,cliente) . "' ;  nome.value='" . pg_result ($res,$i,nome) . "' ; cpf.value = '" . pg_result ($res,$i,cpf) . "' ; cidade.value='" . pg_result ($res,$i,consumidor_cidade) . "' ; fone.value='" . pg_result ($res,$i,fone) . "' ; endereco.value='" .str_replace("\"","",str_replace ("'","",pg_result ($res,$i,endereco))) . "' ; numero.value='" . pg_result ($res,$i,numero) . "' ; complemento.value='" . str_replace("\"","",str_replace ("'","",pg_result ($res,$i,complemento))) . "' ; bairro.value='" . str_replace("\"","",str_replace ("'","",pg_result ($res,$i,bairro))) . "' ; cep.value='" . pg_result ($res,$i,cep) . "' ; estado.value='" . pg_result ($res,$i,estado) . "' ;this.close(); \">\n";
		//echo "<a href=\"javascript: cliente.value='" . pg_result ($res,$i,cliente) . "' ;  nome.value='" . pg_result ($res,$i,nome) . "' ; cpf.value = '" . pg_result ($res,$i,cpf) . "' ; rg.value='" . pg_result ($res,$i,rg) . "'; cidade.value='" . pg_result ($res,$i,nome_cidade) . "' ; fone.value='" . pg_result ($res,$i,fone) . "' ; endereco.value='" . pg_result ($res,$i,endereco) . "' ; numero.value='" . pg_result ($res,$i,numero) . "' ; complemento.value='" . pg_result ($res,$i,complemento) . "' ; bairro.value='" . pg_result ($res,$i,bairro) . "' ; cep.value='" . pg_result ($res,$i,cep) . "' ; estado.value='" . pg_result ($res,$i,estado) . "' ; this.close(); \">\n";
		}
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";

		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-3' color='#000000'>" . pg_result ($res,$i,consumidor_cidade) . "-" . pg_result ($res,$i,estado) . "</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
	}
	echo "</table>\n";
}

?>


</body>
</html>
