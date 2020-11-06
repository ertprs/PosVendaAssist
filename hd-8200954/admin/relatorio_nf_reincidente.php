<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

$admin_privilegios = "auditoria";
include "autentica_admin.php";

if (strlen($_GET["nf"]) > 0) $nf = $_GET["nf"];

$layout_menu = "auditoria";
$title = "RELATÓRIO DE NOTA FISCAL RETROATIVA A 60 DIAS";

include "cabecalho.php";
?>

<style type="text/css">

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<br>

<?
if (strlen($nf) > 0) {
	$sql =	"SELECT tbl_os.sua_os                                                ,
					LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem     ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura     ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento   ,
					TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada   ,
					tbl_os.nota_fiscal                                           ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf      ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo ,
					tbl_posto.nome                               AS posto_nome   ,
					tbl_extrato.protocolo
			FROM      tbl_os
			JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os
			LEFT JOIN tbl_extrato       ON  tbl_extrato.extrato       = tbl_os_extra.extrato
			JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
			JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_os.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica
			AND TRIM(tbl_os.nota_fiscal) = '$nf'
			AND tbl_os.consumidor_revenda = 'C'
			AND tbl_os.finalizada BETWEEN (current_date - INTERVAL '60 days')::date AND current_date
			ORDER BY os_ordem;";
	$res = pg_exec($con,$sql);
	
#	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";
	
	if (pg_numrows($res) > 0) {
		echo "<table border='0' cellpadding='2' cellspacing='1' class='tabela' align='center' width='700'>";
		echo "<tr class='titulo_coluna' height='15' bgcolor='$cor'>";
		echo "<td nowrap>OS</td>";
		echo "<td nowrap>AB</td>";
		echo "<td nowrap>FC</td>";
		echo "<td nowrap>FN</td>";
		echo "<td nowrap>NF Nº</td>";
		echo "<td nowrap>Data NF</td>";
		echo "<td nowrap>Posto</td>";
		echo "<td nowrap>Extrato</td>";
		echo "</tr>";
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$sua_os       = trim(pg_result($res,$x,sua_os));
			$abertura     = trim(pg_result($res,$x,abertura));
			$fechamento   = trim(pg_result($res,$x,fechamento));
			$finalizada   = trim(pg_result($res,$x,finalizada));
			$nota_fiscal  = trim(pg_result($res,$x,nota_fiscal));
			$data_nf      = trim(pg_result($res,$x,data_nf));
			$posto_codigo = trim(pg_result($res,$x,posto_codigo));
			$posto_nome   = trim(pg_result($res,$x,posto_nome));
			$protocolo    = trim(pg_result($res,$x,protocolo));

			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr height='15' bgcolor='$cor'>";
			echo "<td nowrap>" . $posto_codigo . $sua_os . "</td>";
			echo "<td nowrap><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Data Fechamento: $fechamento' style='cursor: help;'>" . substr($fechamento,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Data Finalizada: $finalizada' style='cursor: help;'>" . substr($finalizada,0,5) . "</acronym></td>";
			echo "<td nowrap>" . $nota_fiscal . "</td>";
			echo "<td nowrap><acronym title='Data Nota Fiscal: $data_nf' style='cursor: help;'>" . substr($data_nf,0,5) . "</acronym></td>";
			echo "<td nowrap>" . $posto_codigo . " - " . $posto_nome . "</td>";
			echo "<td nowrap>" . $protocolo . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}
}

if (strlen($nf) == 0) {
	$sql =	"SELECT COUNT(*)          AS qtde        ,
					TRIM(nota_fiscal) AS nota_fiscal
			FROM tbl_os
			WHERE tbl_os.finalizada BETWEEN (current_date - INTERVAL '60 days')::date AND current_date
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.consumidor_revenda = 'C'
			AND tbl_os.nota_fiscal NOTNULL
			AND length(trim(tbl_os.nota_fiscal)) > 0
			AND tbl_os.data_fechamento notnull
			GROUP BY TRIM(tbl_os.nota_fiscal)
			HAVING COUNT(*) > 1
			ORDER BY COUNT(*) DESC;";
	$res = pg_exec($con,$sql);

	//if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res) . "<br>";

	if (pg_numrows($res) > 0) {
		echo "<table border='0' cellpadding='2' cellspacing='1' class='tabela' align='center' width='700'>";
		echo "<tr class='titulo_coluna' height='15' bgcolor='$cor'>";
		echo "<td nowrap>Nota Fiscal</td>";
		echo "<td nowrap>Qtde</td>";
		echo "</tr>";
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$nota_fiscal = trim(pg_result($res,$x,nota_fiscal));
			$qtde        = trim(pg_result($res,$x,qtde));

			$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr  bgcolor='$cor'>";
			echo "<td align='left' nowrap><a href='$PHP_SELF?nf=$nota_fiscal'>" . $nota_fiscal . "</a></td>";
			echo "<td align='left' nowrap>" . $qtde . "</td>";
			echo "</tr>";
		}
		echo "</table>\n";
		echo "<br>";
	}
}
echo "<input type='button' onclick='javascript: history.back();' value='Voltar'>";
//echo "<a href='javascript: history.back();'>VOLTAR</a>";

include "rodape.php";
?>
