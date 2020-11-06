<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if (strlen($acao) > 0) {

	$tipo_os = trim($_POST["tipo_os"]);
	if(strlen($tipo_os)==0){
		$tipo_os = "t";
	}

	##### Pesquisa entre datas #####
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {

		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$data_inicial = date("01/m/Y H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
		}else{
			$msg .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$data_final   = date("t/m/Y H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));
		}else{
			$msg .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg .= " Informe as datas corretas para realizar a pesquisa. ";
	}
}

$layout_menu = "callcenter";
$title = "PEDIDO RELATÓRIO: AT-SHOP";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}
</style>

<?
include "javascript_pesquisas.php";
include "javascript_calendario.php"; 
?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<script language="JavaScript">
function GerarRelatorio (produto, data_inicial, data_final) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto=' + produto + '&data_inicial=' + data_inicial + '&data_final=' + data_final;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" id='Formulario'>
	<caption>PESQUISA PEDIDOS DA AT-SHOP</caption>
	<tbody>
	<tr><td colspan='4'>&nbsp;</td></tr>
	<tr>
		<th>Data Inicial</th>
		<td>
			<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
		</td>
		<th>Data Final</th>
		<td>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
		</td>
	</tr>
	<tr><td colspan='4'>&nbsp;</td></tr>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
	</tfoot>
</table>
</form>


<?
if (strlen($acao) > 0 && strlen($erro) == 0) {
	$x_data_inicial = date("Y-m-01 H:i:s", mktime(0, 0, 0, $mes_inicial, $dia_inicial, $ano_inicial));
	$x_data_final   = date("Y-m-t H:i:s", mktime(23, 59, 59, $mes_final, $dia_final, $ano_final));

	$sql = "SELECT  DISTINCT
					PE.pedido                                                                                 ,
					PO.posto                                                                                 ,
					total                                                                                    ,
					TO_CHAR(PE.data,'DD/MM/YYYY')                                            AS data         ,
					TO_CHAR(PE.finalizado,'DD/MM/YYYY')                                      AS finalizado   ,
					SP.descricao                                                             AS status_pedido,
					CO.descricao                                                             AS condicao     ,
					(SELECT login FROM tbl_admin WHERE tbl_admin.admin = PE.admin)           AS admin_pedido ,
					PF.codigo_posto                                                          AS posto_codigo ,
					PO.nome                                                                  AS posto_nome
			FROM      tbl_pedido        PE
			JOIN      tbl_pedido_item   PI ON PI.pedido        = PE.pedido
			JOIN      tbl_peca          PC ON PC.peca          = PI.peca
			JOIN      tbl_linha         LI ON LI.linha         = PE.linha
			JOIN      tbl_condicao      CO ON CO.condicao      = PE.condicao
			JOIN      tbl_status_pedido SP ON SP.status_pedido = PE.status_pedido
			JOIN      tbl_posto         PO ON PO.posto         = PE.posto
			JOIN      tbl_posto_fabrica PF ON PF.posto         = PO.posto AND PF.fabrica = PE.fabrica
			WHERE PE.fabrica = $login_fabrica
			AND   PE.data BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND   PE.pedido_loja_virtual IS TRUE 
			AND   PC.produto_acabado     IS TRUE
			AND   PE.posto <> 6359
			ORDER BY PE.pedido
			;
			";
#echo nl2br($sql);
#exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$total = 0;
		echo "<br>";
		echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final </b>";
		echo "<br>";

		echo "<center><div style='width:750px;'><TABLE width='700' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
		echo "<thead>";
		echo "<TR>";
		echo "<TD align='left' nowrap width='50'>Pedido</TD>";
		echo "<TD align='left' nowrap>Status</TD>";
		echo "<TD align='left' nowrap width='20'>Condição</TD>";
		echo "<TD align='left' nowrap>Data</TD>";
		echo "<TD align='left' nowrap>Fechado</TD>";
		echo "<TD align='left' nowrap>Posto</TD>";
		echo "<TD align='left' nowrap width='80'>Total</TD>";
		echo "<TD align='left' nowrap width='80' title='Usuário responsável pelo pedido'>Resp.</TD>";
		echo "</TR>";
		echo "</thead>";
		echo "<tbody>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$pedido         = trim(pg_result($res,$i,pedido));
			$posto          = trim(pg_result($res,$i,posto));
			$total          = trim(pg_result($res,$i,total));
			$data           = trim(pg_result($res,$i,data));
			$finalizado     = trim(pg_result($res,$i,finalizado));
			$status_pedido  = trim(pg_result($res,$i,status_pedido));
			$condicao       = trim(pg_result($res,$i,condicao));
			$admin_pedido   = trim(pg_result($res,$i,admin_pedido));
			$posto_codigo   = trim(pg_result($res,$i,posto_codigo));
			$posto_nome     = trim(pg_result($res,$i,posto_nome));

			$total_geral += $total;
			$total = number_format($total,2,'.','');

			echo "<TR>";
			echo "<TD align='left' nowrap><a href='pedido_admin_consulta.php?pedido=$pedido'>$pedido</a></TD>";
			echo "<TD align='left' nowrap>$status_pedido</TD>";
			echo "<TD align='left' nowrap style='font-size:10px;'>$condicao</TD>";
			echo "<TD align='left' nowrap style='font-size:10px;'>$data</TD>";
			echo "<TD align='left' nowrap style='font-size:10px;'>$finalizado</TD>";
			echo "<TD align='left' nowrap style='font-size:10px;'>$posto_codigo - <ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,20)."</ACRONYM></TD>";
			echo "<TD align='right' nowrap><b>$total</b></TD>";
			echo "<TD align='left' nowrap style='font-size:10px;'>$admin_pedido</TD>";
			echo "</TR>";
		}
		echo "</tbody>";
		$total_geral = number_format($total_geral,2,'.','');
		echo "<tfoot>";
		echo "<tr class='table_line'><td colspan='6'><font size='2'><b><CENTER>TOTAL DE VENDAS NO PERÍODO</b></td><td align='center'><font size='2' color='009900'><b>$total_geral</b></td></tr>";
		echo "</tfoot>";
		echo " </TABLE></div>";

		flush();
		$data = date ("d/m/Y H:i:s");

		$arquivo_nome     = "relatorio-pedido-relatorio-$login_fabrica.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>Relatório de Pedido Relatório da Loja Virtual - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		fputs ($fp,"<TABLE width='700' border='0' cellspacing='2' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>");
		fputs ($fp,"<thead>");
		fputs ($fp,"<TR>");
		fputs ($fp,"<TD align='left' nowrap width='50'>Pedido</TD>");
		fputs ($fp,"<TD align='left' nowrap>Status</TD>");
		fputs ($fp,"<TD align='left' nowrap width='20'>Condição</TD>");
		fputs ($fp,"<TD align='left' nowrap>Data</TD>");
		fputs ($fp,"<TD align='left' nowrap>Fechado</TD>");
		fputs ($fp,"<TD align='left' nowrap>Posto</TD>");
		fputs ($fp,"<TD align='left' nowrap width='80'>Total</TD>");
		fputs ($fp,"<TD align='left' nowrap width='80' title='Usuário responsável pelo pedido'>Resp.</TD>");
		fputs ($fp,"</TR>");
		fputs ($fp,"</thead>");
		fputs ($fp,"<tbody>");
		$total_geral = "";
		for ($i=0; $i<pg_numrows($res); $i++){
			$pedido         = trim(pg_result($res,$i,pedido));
			$posto          = trim(pg_result($res,$i,posto));
			$total          = trim(pg_result($res,$i,total));
			$data           = trim(pg_result($res,$i,data));
			$finalizado     = trim(pg_result($res,$i,finalizado));
			$status_pedido  = trim(pg_result($res,$i,status_pedido));
			$condicao       = trim(pg_result($res,$i,condicao));
			$admin_pedido   = trim(pg_result($res,$i,admin_pedido));
			$posto_codigo   = trim(pg_result($res,$i,posto_codigo));
			$posto_nome     = trim(pg_result($res,$i,posto_nome));

			$total_geral += $total;
			$total = number_format($total,2,'.','');

			fputs ($fp,"<TR>");
			fputs ($fp,"<TD align='left' nowrap><a href='pedido_admin_consulta.php?pedido=$pedido'>$pedido</a></TD>");
			fputs ($fp,"<TD align='left' nowrap>$status_pedido</TD>");
			fputs ($fp,"<TD align='left' nowrap style='font-size:10px;'>$condicao</TD>");
			fputs ($fp,"<TD align='left' nowrap style='font-size:10px;'>$data</TD>");
			fputs ($fp,"<TD align='left' nowrap style='font-size:10px;'>$finalizado</TD>");
			fputs ($fp,"<TD align='left' nowrap style='font-size:10px;'>$posto_codigo - <ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,20)."</ACRONYM></TD>");
			fputs ($fp,"<TD align='right' nowrap><b>$total</b></TD>");
			fputs ($fp,"<TD align='left' nowrap style='font-size:10px;'>$admin_pedido</TD>");
			fputs ($fp,"</TR>");
		}
		fputs ($fp,"</tbody>");
		$total_geral = number_format($total_geral,2,'.','');
		fputs ($fp,"<tfoot>");
		fputs ($fp,"<tr class='table_line'><td colspan='6'><font size='2'><b><CENTER>TOTAL DE VENDAS NO PERÍODO</b></td><td align='center'><font size='2' color='009900'><b>$total_geral</b></td></tr>");
		fputs ($fp,"</tfoot>");
		fputs ($fp," </TABLE>");
		fputs ($fp," </body>");
		fputs ($fp," </html>");



		echo ` cp $arquivo_completo_tmp $path `;
		$data = date("Y-m-d").".".date("H-i-s");

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
		if ($login_fabrica == 3) { // HD 59787
			echo  "<br>";
			echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
			echo  "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			echo  "</tr>";
			echo  "</table>";
		}
	}else{
		echo "<br>";
		
		echo "<b>Nenhum resultado encontrado entre $data_inicial e $data_final $mostraMsgLinha $mostraMsgEstado $mostraMsgPais</b>";
	}
	

}


include "rodape.php";
?>
