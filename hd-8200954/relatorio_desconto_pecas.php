<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';


$layout_menu = "os";
$title = "Relatório de Desconto de Peças";

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
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>


<p><p><p>

<TABLE width='500' align='center' border='0' cellspacing='1' cellpadding='1'>

<tr class='menu_top'><td align='center' COLSPAN='3'>Relatório de Desconto de Peças</td></tr>

<TR class='menu_top'>
<TD align='center' width='25%'>Mês</TD>
<TD align='center' width='25%'>Desconto</TD>
<TD align='center' width='25%'>Baixado</TD>

</TR>

<?		if ($i % 2 == 0){
			$cor = '#F1F4FA';
			$btn = 'azul';
		}
?>		

<TR class='table_line' style='background-color: $cor;'>
<TD align='left'><a href='#'><?//echo $mes."-".$ano?>Agosto-04</a></TD>
<TD align='right'><?//echo $desconto;?>15.250,00</TD>
<TD align='right'><?//echo $baixado	;?>5.337,50</TD>
</TR>

<TR class='table_line' style='background-color: $cor;'>
<TD align='left'><a href='#'><?//echo $mes."-".$ano?>Setembro-04</a></TD>
<TD align='right'><?//echo $desconto;?>17.280,00</TD>
<TD align='right'><?//echo $baixado	;?>6.048,00</TD>
</TR>

</TABLE>


<p><p><p>
<TABLE width='500' align='center' border='0' cellspacing='1' cellpadding='1'>

<tr class='menu_top'><td align='center' COLSPAN='3'>Mês - <?//echo $mes $ano;?>Agosto 2004</td></tr>

<TR class='menu_top'>
<TD align='center' width='50%'></TD>
<TD align='center' width='25%'>Desconto</TD>
<TD align='center' width='25%'>Baixado</TD>
</TR>

<TR class='table_line'>
<TD align='left' >Total em Peças Baixadas(garantia)</TD>
<TD align='right'><?//$desconto?>15.250,00</TD>
<TD align='right'><?//$baixado?>5.337,50</TD>
</TR>

<TR class='table_line'>
<TD align='left'>Total em Peças Baixadas(venda)</TD>
<TD align='right'><?//$desconto?>5.453,00</TD>
<TD align='right'><?//$baixado?>1.908,55</TD>
</TR>

<TR class='table_line'>
<TD align='right' colspan='2'><b>Total do Desconto</b>&nbsp;&nbsp;</TD>
<TD align='right'><?//$total_desconto?>5.453,00</TD>
</TR>

</TABLE>


<?include "rodape.php"; ?>