<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Pré Fechamento de Extrato do Posto";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	border: 1px solid;
	color:#000000;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	color:#000000;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
}

</style>

<p>
<?
// INICIO DA SQL POSTO
$sql = "SELECT nome AS posto_nome
		FROM   tbl_posto
		WHERE  posto = $posto";
$res = pg_exec ($con,$sql);

$posto_nome	= trim(pg_result ($res,0,posto_nome));
echo "<br>";
echo "<TABLE width=\"650\" height=\"18\" align='center'>";
echo "	<TR>";
echo "		<TD class='menu_top' align='center'><br><b>$title<br>Posto: $posto_nome</b><br>Fechamento: $data_limite<br><br></TD>";
echo "	</TR>";
echo "</TABLE>";

// SQL
$data_limite   = $_GET ['data_limite'];
$extrato = $_GET['extrato'];
if (strlen ($data_limite) < 10) $data_limite = date ("d/m/Y");
$x_data_limite = substr ($data_limite,6,4) . "-" . substr ($data_limite,3,2) . "-" . substr ($data_limite,0,2);

$sql = "SELECT  tbl_os.posto                                                     ,
				tbl_os.os                                                        ,
				tbl_os.sua_os                                                    ,
				tbl_os.mao_de_obra                                               ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura    ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento  ,
				tbl_os.consumidor_nome                       AS consumidor       ,
				tbl_os.pecas
		FROM	tbl_os
		JOIN    tbl_posto USING (posto)
		JOIN    tbl_os_extra USING (os)
		LEFT JOIN   tbl_os_status ON tbl_os_status.os = tbl_os.os
		WHERE   tbl_os.posto   = $posto
		AND     tbl_os.fabrica = $login_fabrica
		AND     tbl_os.data_fechamento <= '$x_data_limite'::date
		/* AND     tbl_os_extra.extrato is null */
		AND     tbl_os_extra.extrato = $extrato
		AND     tbl_os.finalizada is not null
		AND     (tbl_os.excluida is false OR tbl_os.excluida is null)
		AND         (tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)
		ORDER BY tbl_os.sua_os";
//echo nl2br($sql); die;
$res = pg_exec ($con,$sql);
$totalRegistros = pg_numrows($res);

if ($totalRegistros > 0) {
	echo "<br><br>";
	echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "	<TR>\n";
	echo "		<TD class='menu_top' align='center' width='10%' ><B>OS</B></TD>\n";
	echo "		<TD class='menu_top' align='center' width='40%'><B>CLIENTE</B></TD>\n";
	echo "		<TD class='menu_top' align='center' width='25%'><B>MO</B></TD>\n";
	echo "		<TD class='menu_top' align='center' width='25%'><B>PEÇAS</B></TD>\n";
	echo "	</TR>\n";

	$valorTotal = 0;
	$valorMaoDeObra	= 0;
	$valorPeca	= 0;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$posto			= trim(pg_result ($res,$i,posto));
		$os				= trim(pg_result ($res,$i,os));
		$sua_os			= trim(pg_result ($res,$i,sua_os));
		$mao_de_obra	= trim(pg_result ($res,$i,mao_de_obra));
		$data_abertura	= trim(pg_result ($res,$i,data_abertura));
		$data_fechamento= trim(pg_result ($res,$i,data_fechamento));
		$pecas			= trim(pg_result ($res,$i,pecas));
		$consumidor		= strtoupper(trim(pg_result ($res,$i,consumidor)));
		$consumidor_str	= substr($consumidor,0,23);
		
		# soma valores
		$valorMaoDeObra	= $valorMaoDeObra + $mao_de_obra;
		$valorPeca		= $valorPeca + $peca;
		$valor			= $mao_de_obra + $pecas; 
		$valorTotal		= $valorTotal + $valor;
		
		# formata valores
		$mao_de_obraForm= number_format($mao_de_obra,2,",",".");
		$pecasForm		= number_format($pecas,2,",",".");
		
		$cor = "#ffffff";
		
		if ($i % 2 == 0) $cor = '#f8f8f8';
		
		echo "	<TR class='table_line' style='background-color: $cor;'>\n";
		echo "		<TD align='center' width='10%'>$sua_os</TD>\n";
		echo "		<TD align='left' width='40%' norap>$consumidor</TD>\n";
		echo "		<TD align='right' width='25%' style='padding-right:5px'>$mao_de_obraForm</TD>\n";
		echo "		<TD align='right' width='25%' style='padding-right:5px'>$pecasForm</TD>\n";
		echo "	</TR>\n";
	}
	
	# formata valores
	$valorMaoDeObra	= number_format($valorMaoDeObra,2,",",".");
	$valorPeca		= number_format($valorPeca,2,",",".");
	$valorTotal		= number_format($valorTotal,2,",",".");
	
	echo "	<TR class='table_line'>\n";
	echo "		<TD align='right' style='padding-right:5px' colspan='2'><B>SUB-TOTAIS</B></TD>\n";
	echo "		<TD align='right' style='padding-right:5px'><b>$valorMaoDeObra</b></TD>\n";
	echo "		<TD align='right' style='padding-right:5px'><b>$valorPeca</b></TD>\n";
	echo "	</TR>\n";
	echo "	<TR class='table_line'>\n";
	echo "		<TD align='right' style='padding-right:5px' colspan='2'><b>TOTAL (MO + Peças)</b></TD>\n";
	echo "		<TD bgcolor='$cor' align='center' colspan='3'><b>R$ $valorTotal</b></TD>\n";
	echo "	</TR>\n";
}

echo "</TABLE>\n";

?>

<script>
	window.print();
</script>