<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Pesquisa Postos Autorizados... </title>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>


	<link href="css/estilo_cad_prod.css" rel="stylesheet" type="text/css" />
	<link href="css/posicionamento.css" rel="stylesheet" type="text/css" />

</head>

<body style="margin: 0px 0px 0px 0px;" >

<br>

<?
$sql="SELECT cnpj, nome,endereco,numero,bairro, cidade, estado,cep, fone
		FROM tbl_posto
		JOIN tbl_posto_fabrica using(posto)
		WHERE fabrica=$login_fabrica
		AND credenciamento <> 'DESCREDENCIADO'
		ORDER by nome, estado, cidade,cnpj ";

$res=pg_exec($con,$sql);
echo "<table width='100%' border='1'>\n";

for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$nome       = trim(pg_result($res,$i,nome));
	$cnpj       = trim(pg_result($res,$i,cnpj));
	$cidade     = trim(pg_result($res,$i,cidade));
	$estado     = trim(pg_result($res,$i,estado));
	$endereco     = trim(pg_result($res,$i,endereco));
	$bairro     = trim(pg_result($res,$i,bairro));
	$fone     = trim(pg_result($res,$i,fone));
	$numero     = trim(pg_result($res,$i,numero));
	$cep     = trim(pg_result($res,$i,cep));
	$nome = str_replace ('"','',$nome);
	$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
	$cep=substr($cep,0,5). "-".substr($cep,5,3);
	$bairro = strtoupper($bairro);
	$cidade = str_replace ('"','',$cidade);
	$cidade = strtoupper($cidade);
	$estado = str_replace ('"','',$estado);

	if(strlen($fone) ==0) $fone=" ";
	if(strlen($bairro) ==0) $bairro=" ";
	
	echo "<tr>\n";
	
	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj	</font>\n";
	echo "</td>\n";
	
	echo "<td nowrap>\n";

	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome	</font>\n";
	echo "</a>\n";
	echo "</td>\n";
	
		echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$endereco nº $numero</font>\n";
	echo "</td>\n";


	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$bairro	</font>\n";
	echo "</td>\n";

	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade	</font>\n";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$estado	</font>\n";
	echo "</td>\n";

	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#990000'><b>$cep	</b></font>\n";
	echo "</td>\n";


	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#990000'><b>$fone	</b></font>\n";
	echo "</td>\n";

	echo "</tr>\n";
}
echo "</table>\n";

?>

</body>
</html>
