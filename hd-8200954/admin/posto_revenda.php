<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$fabrica = $_GET['fabrica'];
$title   = "POSTOS QUE ATENDEM REVENDA";

?>
<style>
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>

<?

$sql=" SELECT	posto,
				nome,
				cnpj,
				cidade,
				estado,
				tbl_posto.nome_fantasia
		FROM tbl_posto 
		JOIN tbl_posto_fabrica using(posto)
		WHERE fabrica=$fabrica
		AND tbl_posto_fabrica.item_aparencia='t'
		AND credenciamento <> 'DESCREDENCIADO';";
$res=pg_exec($con,$sql);

if (@pg_numrows ($res) == 0) {
	echo "<h1>Nenhum posto encontrado</h1>";
	echo "<script language='javascript'>";
	echo "setTimeout('window.close()',2500);";
	echo "</script>";
	exit;
} else {

echo "<table width='100%'  border='0' align='center' cellpadding='1' cellspacing='1' class='tabela'>\n";
echo "<caption class='titulo_tabela'>POSTOS QUE ATENDEM REVENDA</caption>";

echo "<thead>";
echo "<tr class='titulo_coluna'>";
echo "<td>CNPJ</td>";
echo "<td>Nome do posto</td>";
echo "<td>Cidade</td>";
echo "<td>Estado</td>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";
for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
	$posto      = trim(pg_result($res,$i,posto));
	$nome       = trim(pg_result($res,$i,nome));
	$cnpj       = trim(pg_result($res,$i,cnpj));
	$cidade     = trim(pg_result($res,$i,cidade));
	$estado     = trim(pg_result($res,$i,estado));
	$fantasia   = trim(pg_result($res,$i,nome_fantasia));
	
	$nome = str_replace ('"','',$nome);
	$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
	$cidade = str_replace ('"','',$cidade);
	$estado = str_replace ('"','',$estado);
	
	$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

	echo "<tr bgcolor=$cor>\n";
	
	echo "<td nowrap>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cnpj</font>\n";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome</font>\n";
	echo "</a>\n";
	if (strlen (trim ($fantasia)) > 0) echo "<br><font color='#808080' size='-1'>$fantasia</font>";
	echo "</td>\n";
	
	echo "<td>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade</font>\n";
	echo "</td>\n";
	
	echo "<td align='center'>\n";
	echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$estado</font>\n";
	echo "</td>\n";

	
	echo "</tr>\n";
	
}
echo "</tbody>";
echo "</table>\n";
echo "<center><a href='javascript: window.close()' rel='ajuda' title='Clique aqui para fechar a janela'>Fechar</a></center>";
}
?>
