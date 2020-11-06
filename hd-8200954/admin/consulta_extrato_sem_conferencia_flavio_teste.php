<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include "funcoes.php";


$extrato = trim($_GET['extrato']);
$posto   = trim($_GET['posto']);
$codigo_agrupado   = trim($_GET['codigo_agrupado']);

include "cabecalho_extrato_print_britania.php";
?>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js/dimensions.js'></script>



<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$("input[@rel=data]").maskedinput("99/99/9999");
	});
</script>

<style type="text/css">
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_obs2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFBB;
}

error{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #FF0000;
}
</style>

<script language="JavaScript">



function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseFloat(num).toFixed(2);
	if (campo.value=='NaN') {
		campo.value='0';
	}
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO
function calcula_total(){
	var x = parseInt(document.getElementById('qtde_linha').value);
	var y = parseInt(document.getElementById('qtde_avulso').value);

	var somav = 0;
	var somat = 0;
	var mao_de_obra  = 0;
	var qtde_conferir_os = 0;
	var valor_avulso = 0;

	for (f=0; f<x;f++){
		mao_de_obra  = document.getElementById('unitario_'+f).value.replace(',','.');
		qtde_conferir_os = document.getElementById('qtde_conferir_os_'+f).value.replace(',','.');
		somav = parseInt(qtde_conferir_os) * parseFloat(mao_de_obra);
		somat = somat + parseFloat(somav); 
	}

	for (a=0; a<y; a++){
		valor_avulso = document.getElementById('valor_avulso_'+a).value;
		somat += parseFloat(valor_avulso);
	}

	document.getElementById('valor_conferencia_a_pagar').value= somat;
}


</script>
<p>
<center>
<?
if(strlen($msg_erro)>0){
	echo "<DIV class='error'>".$msg_erro."</DIV>";
}

?>
<font size='+1' face='arial'>Data do Extrato</font><TABLE border=1><TR>
	<TD>codigo_posto</TD>
	<TD>extrato</TD>
	<TD>conferencia</TD>
</TR>
<?

$sql = "select tbl_posto_fabrica.codigo_posto, tbl_extrato.extrato, tbl_extrato_conferencia.extrato as conferencia from tbl_extrato left outer join tbl_extrato_conferencia using(extrato) join tbl_posto_fabrica on tbl_extrato.posto =tbl_posto_fabrica.posto where tbl_extrato.fabrica =3 and tbl_posto_fabrica.fabrica = 3 order by extrato desc limit 2000;";

$res = pg_exec ($con,$sql);

for($i=0; $i < pg_numrows($res); $i++){

$codigo_posto = pg_result($res,$i,codigo_posto);
$extrato = pg_result($res,$i,extrato);
$conferencia = pg_result($res,$i,conferencia);
echo "
<TR>
	<TD>$codigo_posto</TD>
	<TD>$extrato</TD>
	<TD>$conferencia</TD>
</TR>";

}?>
</TABLE>		
<? include "rodape_print.php"; ?>
