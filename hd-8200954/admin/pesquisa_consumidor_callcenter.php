<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["nome"]) > 0) {
	$nome = strtoupper (trim ($_GET["nome"]));
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>: <i>$nome</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_cliente.*                 ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado             
			FROM        tbl_cliente 
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE       tbl_cliente.nome ILIKE '%$nome%'
			ORDER BY    tbl_cliente.nome";
	$sql = "SELECT	id              , 
					nome            ,
					endereco        , 
					numero          , 
					complemento     , 
					bairro          , 
					cep             , 
					cidade          , 
					fone            , 
					cpf_cnpj        , 
					rg              ,
					email           ,
					nome_cidade     , 
					estado          ,
					tipo
				FROM (
						(
						SELECT tbl_cliente.cliente as id ,
								tbl_cliente.nome         ,
								tbl_cliente.endereco     ,
								tbl_cliente.numero       ,
								tbl_cliente.complemento  , 
								tbl_cliente.bairro       ,
								tbl_cliente.cep          ,
								tbl_cliente.cidade       ,
								tbl_cliente.fone         ,
								tbl_cliente.cpf as cpf_cnpj ,
								tbl_cliente.rg           ,
								tbl_cliente.email        ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado        ,
								'C' as tipo
						FROM tbl_cliente 
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE tbl_cliente.nome ILIKE '%$nome%'
						)union(
						SELECT tbl_revenda.revenda as id , 
								tbl_revenda.nome         , 
								tbl_revenda.endereco     , 
								tbl_revenda.numero       , 
								tbl_revenda.complemento  , 
								tbl_revenda.bairro       , 
								tbl_revenda.cep          , 
								tbl_revenda.cidade       , 
								tbl_revenda.fone         , 
								tbl_revenda.cnpj  as cpf_cnpj, 
								'' as rg                 ,
								tbl_revenda.email        ,
								tbl_cidade.nome AS nome_cidade, 
								tbl_cidade.estado        ,
								'R' as tipo
						FROM tbl_revenda 
						LEFT JOIN tbl_cidade USING (cidade) 
						WHERE tbl_revenda.nome ILIKE '%$nome%'
						)
					) as X";
	$res = pg_exec ($con,$sql);
	//echo $sql;

	if (pg_numrows ($res) == 0) {
		echo "<h1>Consumidor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($_GET["cpf"]) > 0) {
	$cpf = strtoupper (trim ($_GET["cpf"]));
	$cpf = str_replace (".","",$cpf);
	$cpf = str_replace ("-","",$cpf);
	$cpf = str_replace ("/","",$cpf);
	$cpf = str_replace (" ","",$cpf);
	
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CPF do consumidor</b>: <i>$cpf</i></font>";
	echo "<p>";
	
	$sql = "SELECT      tbl_cliente.*                 ,
						tbl_cidade.nome AS nome_cidade,
						tbl_cidade.estado             
			FROM        tbl_cliente 
			LEFT JOIN   tbl_cidade USING (cidade)
			WHERE       tbl_cliente.cpf = '$cpf'
			ORDER BY    tbl_cliente.nome";


	$sql = "SELECT	id              , 
					nome            ,
					endereco        , 
					numero          , 
					complemento     , 
					bairro          , 
					cep             , 
					cidade          , 
					fone            , 
					cpf_cnpj        , 
					rg              ,
					email           ,
					nome_cidade     , 
					estado          ,
					tipo
				FROM (
						(
						SELECT tbl_cliente.cliente as id ,
								tbl_cliente.nome         ,
								tbl_cliente.endereco     ,
								tbl_cliente.numero       ,
								tbl_cliente.complemento  , 
								tbl_cliente.bairro       ,
								tbl_cliente.cep          ,
								tbl_cliente.cidade       ,
								tbl_cliente.fone         ,
								tbl_cliente.cpf as cpf_cnpj ,
								tbl_cliente.rg           ,
								tbl_cliente.email        ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado        ,
								'C' as tipo
						FROM tbl_cliente 
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE tbl_cliente.cpf = '$cpf'
						)union(
						SELECT tbl_revenda.revenda as id , 
								tbl_revenda.nome         , 
								tbl_revenda.endereco     , 
								tbl_revenda.numero       , 
								tbl_revenda.complemento  , 
								tbl_revenda.bairro       , 
								tbl_revenda.cep          , 
								tbl_revenda.cidade       , 
								tbl_revenda.fone         , 
								tbl_revenda.cnpj  as cpf_cnpj, 
								'' as rg                 ,
								tbl_revenda.email        ,
								tbl_cidade.nome AS nome_cidade, 
								tbl_cidade.estado        ,
								'R' as tipo
						FROM tbl_revenda 
						LEFT JOIN tbl_cidade USING (cidade) 
						WHERE tbl_revenda.cnpj = '$cpf'
						)
					) as X";
//echo $sql;
	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<h1>CPF/CNPJ '$cpf' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

/*if (pg_numrows($res) == 1) {
	echo "<script language='javascript'>";
	echo "cliente.value     ='".pg_result($res,0,cliente)."'; ";
	echo "nome.value        ='".str_replace("'","",pg_result($res,0,nome))."'; ";
	echo "cpf.value         ='".pg_result($res,0,cpf)."'; ";
	echo "rg.value          ='".pg_result($res,0,rg)."'; ";
	echo "cidade.value      ='".pg_result($res,0,nome_cidade)."'; ";
	echo "fone.value        ='".pg_result($res,0,fone)."'; ";
	echo "endereco.value    ='".str_replace("'","",pg_result($res,0,endereco))."'; ";
	echo "numero.value      ='".pg_result($res,0,numero)."'; ";
	echo "complemento.value ='".pg_result($res,0,complemento)."'; ";
	echo "bairro.value      ='".pg_result($res,0,bairro)."'; ";
	echo "cep.value         ='".pg_result($res,0,cep)."'; ";
	echo "estado.value      ='".pg_result($res,0,estado)."'; ";
	if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}
*/
if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";
	
	echo "<table width='100%' border='0'>\n";
	
		echo "<TR bgcolor='#CCCCCC'>";
			echo "<TD><B>CPF</B></TD>";
			echo "<TD><B>Nome</B></TD>";
			echo "<TD><B>Tipo</B></TD>";
		echo "</TR>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$cliente     = trim(pg_result($res,$i,id));
		$nome        = str_replace("'","",trim(pg_result($res,$i,nome)));
		$cpf         = trim(pg_result($res,$i,cpf_cnpj));
		$endereco    = str_replace ("'","",trim(pg_result($res,$i,endereco)));
		$numero      = trim(pg_result($res,$i,numero));
		$complemento = trim(pg_result($res,$i,complemento));
		$bairro      = trim(pg_result($res,$i,bairro));
		$cep         = trim(pg_result($res,$i,cep));
		$cidade      = trim(pg_result($res,$i,cidade));
		$fone        = trim(pg_result($res,$i,fone));
		$rg          = trim(pg_result($res,$i,rg));
		$email       = trim(pg_result($res,$i,email));
		$nome_cidade = trim(pg_result($res,$i,nome_cidade));
		$estado      = trim(pg_result($res,$i,estado));
		$tipo        = trim(pg_result($res,$i,tipo));
		if($tipo=="C"){
				$xtipo="Consumidor";
		}else{
				$xtipo="Revenda";
		}
		echo "<tr>\n";
		
		echo "<td>\n";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cpf</font>\n";
		echo "</td>\n";
		
		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "<a href=\"javascript: opener.document.location = '$retorno?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: cliente.value='$cliente' ; nome.value='$nome' ; cpf.value = '$cpf' ; rg.value='$rg'; cidade.value='$nome_cidade' ; fone.value='$fone' ; endereco.value='$endereco' ; numero.value='$numero' ; complemento.value='$complemento' ; bairro.value='$bairro' ; cep.value='$cep' ; estado.value='$estado' ; tipo.value='tipo'; email.value = '$email';";
			if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
			echo "this.close(); \">\n";
		}
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
		echo "</a>\n";
		echo "</td>\n";
		echo "<td>$xtipo</td>";

		echo "</tr>";
	}
	echo "</table>\n";
}

?>


</body>
</html>