<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$title = "Relação das Mensalidades";
$layout_menu = 'gerencia';
include "cabecalho.php";

?>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: right;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
}

.table_line3 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
}

</style>

<?

$sql = "SELECT tbl_fabrica.nome as nome_fabrica
		FROM   tbl_fabrica
		WHERE  tbl_fabrica.fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);

$nome_fabrica  = strtoupper (pg_result($res,0,nome_fabrica));

?>
<br>

<?

echo"<TABLE width='700' border='1' cellspacing='3' cellpadding='3' align='center'>";
echo"	<TR>";
echo"		<TD colspan='8' height='20' class='menu_top' align='center'>$nome_fabrica</td>";
echo"	</TR>";

echo"	<TR>";
echo"		<TD class='table_line' nowrap>MÊS/ANO</TD>";
echo"		<TD class='table_line' nowrap>ÚLTIMO PEDIDO</TD>";
echo"		<TD class='table_line' nowrap>QTDE DE PEDIDOS</TD>";
echo"		<TD class='table_line' nowrap>ÚLTIMA OS</TD>";
echo"		<TD class='table_line' nowrap>QTDE DE OS</TD>";
echo"		<TD class='table_line' nowrap>QTDE CALLCENTER</TD>";
echo"		<TD class='table_line' nowrap>ÚLTIMO CALLCENTER</TD>";
echo"		<TD class='table_line' nowrap>VALOR</TD>";
echo"	</TR>";

$sql = "SELECT  mes              ,
				ano              ,
				ultimo_pedido    ,
				ultima_os        ,
				qtde_pedido      ,
				qtde_os          ,
				valor            ,
				qtde_callcenter  ,
				ultimo_callcenter
		FROM  tbl_mensalidade
		WHERE tbl_mensalidade.fabrica = $login_fabrica";

//Alterado por Wellington temporariamente, pois mensalidade da Lenoxx de Outubro está aparecendo errado
if ($login_fabrica <> 11) $sql.= " ORDER BY ano, mes";
else $sql.= " ORDER BY ano desc limit 1";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
	$mes                = pg_result($res,$i,mes);
	$ano                = pg_result($res,$i,ano);
	$ultimo_pedido      = pg_result($res,$i,ultimo_pedido);
	$ultima_os          = pg_result($res,$i,ultima_os);
	$qtde_pedido        = pg_result($res,$i,qtde_pedido);
	$qtde_os            = pg_result($res,$i,qtde_os);
	$ultimo_callcenter  = pg_result($res,$i,ultimo_callcenter);
	$qtde_callcenter    = pg_result($res,$i,qtde_callcenter);
	$valor              = pg_result($res,$i,valor);

	switch($mes){
		case "01"  :$mesD = "Janeiro"  ;break;
		case "02"  :$mesD = "Fevereiro";break;
		case "03"  :$mesD = "Março"    ;break;
		case "04"  :$mesD = "Abril"    ;break;
		case "05"  :$mesD = "Maio"     ;break;
		case "06"  :$mesD = "Junho"    ;break;
		case "07"  :$mesD = "Julho"    ;break;
		case "08"  :$mesD = "Agosto"   ;break;
		case "09"  :$mesD = "Setembro" ;break;
		case "10"  :$mesD = "Outubro"  ;break;
		case "11"  :$mesD = "Novembro" ;break;
		case "12"  :$mesD = "Dezembro" ;break;
	}//FIM SWITCH
	

	$ultimo_pedido     = number_format($ultimo_pedido,0,'','.');
	$ultima_os         = number_format($ultima_os,0,'','.');
	$qtde_pedido       = number_format($qtde_pedido,0,'','.');
	$qtde_os           = number_format($qtde_os,0,'','.');
	$ultimo_callcenter = number_format($ultimo_callcenter,0,'','.');
	$qtde_callcenter   = number_format($qtde_callcenter,0,'','.');
	$valor             = number_format($valor,2,",",".");

	$sql = "SELECT fn_dias_mes('$ano-$mes-01',0)";
	$resZ = pg_exec($con,$sql);
	$a_data_inicio = explode(" ",pg_result($resZ,0,0));
	$a_data_inicio = substr($a_data_inicio[0],8,2) ."/". substr($a_data_inicio[0],5,2) ."/". substr($a_data_inicio[0],0,4);

	$sql = "SELECT fn_dias_mes('$ano-$mes-01',1)";
	$resZ = pg_exec($con,$sql);
	$a_data_final = explode(" ",pg_result($resZ,0,0));
	$a_data_final = substr($a_data_final[0],8,2) ."/". substr($a_data_final[0],5,2) ."/". substr($a_data_final[0],0,4);

	echo"	<TR>";
	echo"		<TD class='table_line3' nowrap> $mesD/$ano</TD>";
	echo"		<TD class='table_line2' nowrap> $ultimo_pedido</TD>";
	echo"		<TD class='table_line2' nowrap> $qtde_pedido</TD>";
	echo"		<TD class='table_line2' nowrap> $ultima_os</TD>";
	echo"		<TD class='table_line2' nowrap> $qtde_os</TD>";
	echo"		<TD class='table_line2' nowrap> $qtde_callcenter</TD>";
	echo"		<TD class='table_line2' nowrap> $ultimo_callcenter</TD>";
	echo"		<TD class='table_line2' nowrap> &nbsp;&nbsp; $valor</TD>";
	echo"		<TD class='table_line2' nowrap><a href='os_mensalidade_consulta.php?btn_acao=pesquisa&data_inicial_01=$a_data_inicio&data_final_01=$a_data_final'> Relação das OSs </a></TD>";
	echo"	</TR>";
}//FIM FOR

echo"</TABLE>";

echo "<br>";

include "rodape.php"; 

?>