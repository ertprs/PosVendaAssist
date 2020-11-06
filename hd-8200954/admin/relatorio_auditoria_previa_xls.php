<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

include 'funcoes.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Relatório de OSs que sofreram auditoria prévia";

include "cabecalho.php";
?>
<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #596D9B
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<p>

<?
$btn_acao = strtolower($_GET['btn_acao']);

$posto_codigo = trim($_GET["posto_codigo"]);
$posto_nome   = trim($_GET["posto_nome"]);


if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	$posto_codigo     = trim($_GET["posto_codigo"]);
	$posto_nome       = trim($_GET["posto_nome"]);
	$data_final       = trim($_GET["data_final"]);
	$data_inicial     = trim($_GET["data_inicial"]);
	
	if ($data_inicial != "dd/mm/aaaa" && $data_final != "dd/mm/aaaa") {

		if (strlen($data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($data_inicial);
			$x_data_inicial = str_replace("'", "", $x_data_inicial);
			$dia_inicial    = substr($x_data_inicial, 8, 2);
			$mes_inicial    = substr($x_data_inicial, 5, 2);
			$ano_inicial    = substr($x_data_inicial, 0, 4);
			$x_data_inicial = $ano_inicial ."-". $mes_inicial ."-". $dia_inicial. " 00:00:00";
		}else{
			$msg .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($data_final);
			$x_data_final = str_replace("'", "", $x_data_final);
			$dia_final    = substr($x_data_final, 8, 2);
			$mes_final    = substr($x_data_final, 5, 2);
			$ano_final    = substr($x_data_final, 0, 4);
			$x_data_final   = $ano_final ."-". $mes_final ."-". $dia_final . " 23:59:59";
		}else{
			$msg .= " Preencha o campo Data Final para realizar a pesquisa. ";
		}
	}else{
		$msg .= " Informe as datas corretas para realizar a pesquisa. ";
	}

	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) == 1){
			$posto = pg_result($res,0,0);
		}
	}
$cond_1 = "1=1";
if(strlen($posto)>0) $cond_1 = " tbl_posto.posto = $posto ";

$sql = "SELECT distinct
				tbl_os.os                                       ,
				tbl_os.sua_os                                   ,
				tbl_posto_fabrica.codigo_posto                  ,
				tbl_posto.nome                                  ,
				tbl_os_auditar.descricao as tipo_auditoria      ,
				tbl_os_auditar.cancelada AS os_cancelada        ,
				tbl_admin.login                                 ,
				tbl_os_extra.mao_de_obra_desconto               ,
				tbl_os.mao_de_obra                              ,
				tbl_os_extra.status_os                          ,
				tbl_status_os.descricao as situacao             ,
				tbl_produto.referencia as produto_referencia    ,
				tbl_produto.descricao  as produto_descricao    ,
				tbl_os_extra.extrato                            ,
				to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') as data_geracao,
				to_char(tbl_os_auditar.liberado_data,'DD/MM/YYYY') as data_liberacao
		FROM  tbl_os_auditar
		JOIN  tbl_os using(os)
		JOIN  tbl_os_extra using(os)
		LEFT JOIN  tbl_extrato  on tbl_extrato.extrato = tbl_os_extra.extrato
		JOIN  tbl_posto on tbl_posto.posto = tbl_os.posto
		JOIN  tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT  JOIN tbl_admin ON tbl_os_auditar.admin = tbl_admin.admin
		LEFT  JOIN  tbl_status_os on tbl_os_extra.status_os = tbl_status_os.status_os
		JOIN  tbl_produto on tbl_os.produto = tbl_produto.produto
		WHERE tbl_os.fabrica = $login_fabrica
		/* AND   tbl_os.auditar is true HD 153238 todos que estao neste processo deverao aparecer */
		AND   tbl_os_auditar.data between '$x_data_inicial' and '$x_data_final'
		AND   $cond_1
		ORDER by tbl_os.sua_os, tbl_posto_fabrica.codigo_posto desc";
//echo nl2br($sql);exit;// echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		$data = date ("dmY");

		echo `rm /tmp/assist/relatorio_auditoria_previa-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/relatorio_auditoria_previa-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>RELATÓRIO DE VALORES DE EXTRATOS - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<table width='700' align='center'  border='0' cellspacing='1' cellpadding='2' bgcolor='#596D9B'  style='font-family: verdana; font-size: 10px'>");
		fputs ($fp,"<tr class='menu_top2'>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>OS</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>EXTRATO</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>CÓDIGO POSTO</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>NOME POSTO</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>REFERÊNCIA</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>DESCRIÇÃO</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>MÃO-DE-OBRA</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>TIPO AUDITORIA</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>DATA AUDITORIA</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>ADMIN</FONT></td>");
		fputs ($fp,"<td nowrap><font color='#FFFFFF'>SITUAÇÃO</FONT></td>");
		fputs ($fp,"</tr>");

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$os                   = pg_result($res,$i,os);
			$sua_os               = pg_result($res,$i,sua_os);
			$codigo_posto         = pg_result($res,$i,codigo_posto);
			$nome_posto           = pg_result($res,$i,nome);
			$tipo_auditoria       = pg_result($res,$i,tipo_auditoria);
			$login                = pg_result($res,$i,login);
			$mao_de_obra_desconto = pg_result($res,$i,mao_de_obra_desconto);
			$mao_de_obra          = pg_result($res,$i,mao_de_obra);
			$data_liberacao       = pg_result($res,$i,data_liberacao);
			$situacao             = pg_result($res,$i,situacao);
			$status_os           = pg_result($res,$i,status_os);
			$data_geracao         = pg_result($res,$i,data_geracao);
			$os_cancelada         = pg_result($res,$i,os_cancelada);
			$produto_referencia   = pg_result($res,$i,produto_referencia);/*hd 4790 011007 takashi*/
			$produto_descricao    = pg_result($res,$i,produto_descricao);/*hd 4790 011007 takashi*/
		
			$temp = ($mao_de_obra - $mao_de_obra_desconto);

			//IGOR HD 3612- estava com 	if($status_os==19 and $temp==0) 
			// Quando o desconto era de 20 e a mao de obra era 10, aparecia errado pois estava negativo
			// Quando o desconto era 10 e a mao de obra é 0, entao temp fica como 10 - ERRADO pois não entra na condição
			if(strlen($data_geracao)>0)  $situacao = "OS Aprovada";
			if($status_os==19 and $mao_de_obra <=0){
				$situacao = "OS Aprovada sem Mão-de-obra";
			}else{
				if($status_os==19 and $temp<=0){ 
					$situacao = "OS Aprovada sem Mão-de-obra";
				}
			}
			if($status_os == '' and strlen($data_liberacao)>0 )     $situacao = "OS Aprovada";
			
			if($os_cancelada == 't')     $situacao = "OS Cancelada";
			
			if ($i % 2 == 0) $cor = '#efeeea';
			else             $cor = '#d2d7e1';

			fputs ($fp,"<tr class='table_line' bgcolor='$cor'>");
			fputs ($fp,"<td nowrap align='center'><a href='os_press.php?os=$os'>$sua_os</a></td>");
			fputs ($fp,"<td nowrap align='left'>$data_geracao</td>");
			fputs ($fp,"<td nowrap align='left'>$codigo_posto</td>");
			fputs ($fp,"<td nowrap align='left'>$nome_posto</td>");
			fputs ($fp,"<td nowrap align='left'>$produto_referencia</td>");
			fputs ($fp,"<td nowrap align='left'>$produto_descricao</td>");
			fputs ($fp,"<td nowrap align='left'>R$".number_format($mao_de_obra,2,',','.')."</td>");
			fputs ($fp,"<td nowrap align='center'>$tipo_auditoria</td>");
			fputs ($fp,"<td nowrap align='center'>$data_liberacao</td>");
			fputs ($fp,"<td nowrap align='left'>$login</td>");
			fputs ($fp,"<td nowrap align='left'>$situacao</td>");
			fputs ($fp,"</tr>");

		}

		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_auditoria_previa-$login_fabrica.$data.xls /tmp/assist/relatorio_auditoria_previa-$login_fabrica.html`;
		flush();
		echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio_auditoria_previa-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
	}
}

echo "<br>";

include "rodape.php";
?>
