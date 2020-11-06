<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

// include 'cabecalho_pop_revendas.php';
?><!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
<html>
<head>
    <title>Telecontrol - Pesquisa Revenda Autorizadas... </title>
    <meta name="Author" content="">
    <meta name="Keywords" content="">
    <meta name="Description" content="">
    <meta http-equiv='pragma' content='no-cache'>

<style type="text/css">
body {margin:0;text-align:left}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
	margin: 1ex 1em;
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
	<img src='imagens_admin/pesquisa_revenda.gif'>
    <br>
<?
$tipo = trim (strtolower ($_GET['tipo']));

//  HD 234135 19/08/2010 - Usar tbl_revenda_fabrica...
//                         Para fazer com que uma fábrica use a tbl_revenda_fabrica, adicionar ao array
$usa_rev_fabrica = in_array($login_fabrica, array(3));

if ($tipo == "nome") {
	$nome = trim($_GET["campo"]);
	
	//echo "<h4>Pesquisando por <b>Nome do Posto</b>: <i>$nome</i></h4>";
	//echo "<p>";
	
	$sql = "SELECT   tbl_revenda.revenda,
			tbl_revenda.nome           ,
			tbl_revenda.cnpj           ,
			tbl_cidade.nome AS cidade  ,
			tbl_cidade.estado          
			FROM     tbl_revenda
			JOIN     tbl_cidade USING(cidade)
			WHERE    tbl_revenda.nome ILIKE '%$nome%' 
			ORDER BY tbl_revenda.nome";
    if ($usa_rev_fabrica) $sql = "SELECT
			LPAD(cnpj, 14, '0') AS cnpj ,
			contato_razao_social AS nome ,
			tbl_cidade.nome AS cidade,
			tbl_cidade.estado AS estado,
			revenda
			FROM tbl_revenda_fabrica
			JOIN tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
			WHERE contato_razao_social ~* '^$nome'
			AND tbl_revenda_fabrica.fabrica = $login_fabrica
			ORDER BY estado, cidade, nome;";

    $res = @pg_query($con,$sql);
	if (is_resource($res)) $tot = pg_num_rows($res);

	if ($tot == 0) {
		echo "<h1>Revenda '$nome' não encontrada</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

if ($tipo == "cnpj") {
	$cnpj = trim(preg_replace('/\D/', '', $_GET["campo"]));

	//echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CNPJ do Posto</b>: <i>$cnpj</i></font>";
	//echo "<p>";
	$sql = "SELECT   tbl_revenda.revenda,
			tbl_revenda.nome           ,
			tbl_revenda.cnpj           ,
			tbl_cidade.nome AS cidade  ,
			tbl_cidade.estado          
			FROM     tbl_revenda
			JOIN     tbl_cidade USING(cidade)
			WHERE    tbl_revenda.cnpj LIKE '%$cnpj%';";
    if ($usa_rev_fabrica) $sql = "SELECT
                    LPAD(cnpj, 14, '0')  AS cnpj  ,
                    contato_razao_social AS nome  ,
                    tbl_cidade.nome      AS cidade,
                    tbl_cidade.estado      AS estado,
                    revenda
			 FROM	tbl_revenda_fabrica
		     JOIN   tbl_cidade ON tbl_cidade.cidade = tbl_revenda_fabrica.cidade
			WHERE    cnpj LIKE '$cnpj%'
			AND tbl_revenda_fabrica.fabrica = $login_fabrica
         ORDER BY   estado, tbl_cidade.nome, tbl_cidade.estado, nome";

	$res = pg_query($con, $sql);
	if (is_resource($res)) $tot = pg_num_rows($res);

	if ($tot == 0) {
		echo "<h1>CNPJ '$cnpj' não encontrado</h1>";
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
	if($tipo=="nome")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>Nome da Revenda</b>: $nome</font></td></tr>";
	if($tipo=="cnpj")
		echo "<tr class='titulo_tabela'><td colspan='4'><font style='font-size:14px;'>Pesquisando por <b>CNPJ da Revenda</b>: $cnpj</font></td></tr>";

	echo "<tr class='titulo_coluna'><td>CNPJ</td><td>Nome</td><td>Cidade</td><td>UF</td>";

	for ($i = 0; $i < $tot; $i++) {
		$revenda    = trim(pg_fetch_result($res, $i, 'revenda'));
		$nome       = trim(pg_fetch_result($res, $i, 'nome'));
		$cnpj       = trim(pg_fetch_result($res, $i, 'cnpj'));
		$cidade     = trim(pg_fetch_result($res, $i, 'cidade'));
		$estado     = trim(pg_fetch_result($res, $i, 'estado'));

		$nome = str_replace('"','',$nome);
		$cnpj = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
		$cor = ($i%2==0) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor'>\n";
		echo "<td>$cnpj</td>\n";
		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			echo "<a href=\"javascript:opener.document.location = retorno + '?revenda=$revenda' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: nome.value = '$nome' ; cnpj.value = '$cnpj' ; this.close() ; \" >";
		}
		echo "$nome</a></td>\n";
		echo "<td>$cidade</td>\n";
		echo "<td>$estado</td>\n";
		echo "</tr>\n";
	}
	echo "</table>\n";
?>

</body>
</html>
