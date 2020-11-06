<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];

$layout_menu = "gerencia";
$title = "Totais de Atendimento no Ano";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>


<?

// INICIO DA SQL

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<TR class='menu_top'>\n";
	echo "<TD  align=\"center\">TOTAIS DE ATENDIMENTO NO ANO</TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
	
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "	<TR class='menu_top'>\n";
	echo "		<TD>LINHA</TD>\n";
	echo "		<TD>&nbsp;</TD>\n";
	echo "		<TD>JAN</TD>\n";
	echo "		<TD>FEV</TD>\n";
	echo "		<TD>MAR</TD>\n";
	echo "		<TD>ABR</TD>\n";
	echo "		<TD>MAI</TD>\n";
	echo "		<TD>JUN</TD>\n";
	echo "		<TD>JUL</TD>\n";
	echo "		<TD>AGO</TD>\n";
	echo "		<TD>SET</TD>\n";
	echo "		<TD>OUT</TD>\n";
	echo "		<TD>NOV</TD>\n";
	echo "		<TD>DEZ</TD>\n";
	echo "	</TR>\n";
/*for*/
	echo "	<TR>\n";
	echo "		<TD rowspan='2'  class='menu_top'>AUDIO</TD>\n";
	echo "		<TD class='menu_top'>OS</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "	</TR>\n";
	echo "	<TR>\n";
	echo "		<TD class='menu_top'>MO</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "		<TD class='table_line'>&nbsp;</TD>\n";
	echo "	</TR>\n";

	
	/*fim for*/


/*	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}*/

/*		echo "<input type='hidden' name='aux_posto[$i]' value='$posto'>";
		echo "<TR  style='background-color: $cor;'>\n";
		echo "	<TD>$cnpj</TD>\n";
		if ($codigo_posto == $cnpj or strlen($codigo_posto) == 0) {
			echo "	<TD><ACRONYM TITLE=\"$nome\">". substr($nome,0,33)."</ACRONYM></TD>\n";
		}else{
			echo "	<TD><ACRONYM TITLE=\"$nome\">". $codigo_posto . "-" . substr($nome,0,33)."</ACRONYM></TD>\n";
		}
		echo "	<TD align=right style='padding-right:7px;'>&nbsp;</TD>\n";
		echo "	<TD align=center>&nbsp;</TD>\n";
		echo "	<TD>&nbsp;</TD>\n";
		echo "	<TD>&nbsp;</TD>\n";
		echo "</TR>\n";
*/	
	echo "<input type='hidden' name='total' value='$i'>";

echo "</form>";
echo "</TABLE>\n";

?>
<p>

<p>
<? include "rodape.php"; ?>