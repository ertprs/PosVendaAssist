<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Extrato Distribuidor Posto";

include "cabecalho.php";

?>


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
echo "<br>";
// INICIO DA SQL
/*$sql = "SELECT   tbl_extrato.extrato                                            ,
				 to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao,
				 tbl_extrato.mao_de_obra                                        ,
				 tbl_extrato.pecas                                              ,
				 tbl_extrato.total                                              
		FROM     tbl_extrato
		WHERE    tbl_extrato.fabrica = $login_fabrica
		AND      tbl_extrato.posto   = $login_posto
		AND      tbl_extrato.aprovado IS NOT NULL
		ORDER BY tbl_extrato.data_geracao DESC";
$res = pg_exec ($con,$sql);*/

	echo "<TABLE width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<FORM METHOD=POST NAME=frm_extrato ACTION=\"$PHP_SELF\">";

//if (pg_numrows($res) > 0) {
	echo "<TR class='menu_top'><TD colspan='5'> 42379 - Refrigeração Ambiente</TD></tr>";
//	echo "<TR class='menu_top'><TD colspan='7'> $codigo - $descricao</TD></tr>";
	echo "	<TR class='menu_top'>\n";
	echo "		<TD align=\"center\">OS</TD>\n";
	echo "		<TD align=\"center\">CLIENTE</TD>\n";
	echo "		<TD align=\"center\">APARELHO</TD>\n";
	echo "		<TD align=\"center\">MO PA</TD>\n";
	echo "		<TD align=\"center\">MO DIST</TD>\n";
	echo "	</TR>\n";
	
/*	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$extrato		= trim(pg_result($res,$i,extrato));
		$data_geracao	= trim(pg_result($res,$i,data_geracao));
		$mao_de_obra	= trim(pg_result($res,$i,mao_de_obra));
		$pecas			= trim(pg_result($res,$i,pecas));
		$total			= trim(pg_result($res,$i,total));

		$mao_de_obra	= number_format($mao_de_obra, 2, ',', ' ');
		$pecas			= number_format($pecas, 2, ',', ' ');
		$total			= number_format($total, 2, ',', ' ');
		
*/
		echo "	<TR class='table_line' style='background-color: $cor;'>\n";
		echo "		<TD align='left' style='padding-left:7px;'>$os 253685</TD>\n";
		echo "		<TD align='center' >$cliente Maria Irene</TD>\n";
		echo "		<TD align='center' >$aparelho AB 1000</TD>\n";
		echo "		<TD align='right'  style='padding-right:3px;' nowrap>$mo_pa 4,00</TD>\n";
		echo "		<TD align='right'  style='padding-right:3px;' nowrap>$mo_dist 4,00</TD>\n";
		echo "</TR>\n";

		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "		<TD COLSPAN = '3' align='right' style='padding-left:7px;'><b>TOTAL&nbsp;</b></TD>\n";
		echo "		<TD align='right' >$total_pa 4,00</TD>\n";
		echo "		<TD align='right' >$total_dist 4,00</TD>\n";
		echo "</TR>\n";
		
//}else{

/*	echo "	<TR class='table_line'>\n";
	echo "		<TD align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</TD>\n";
	echo "	</TR>\n";
	echo "	<TR>\n";
	echo "		<TD align=\"center\">\n";
	echo "			<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
	echo "		</TD>\n";
	echo "	</TR>\n";*/

//}

echo "</form>";
echo "</TABLE>\n";
?>
<p>

<p>
<? include "rodape.php"; ?>