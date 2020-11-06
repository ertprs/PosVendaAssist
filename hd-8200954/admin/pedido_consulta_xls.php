<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

if($login_fabrica == 1) {
	include("pedido_consulta_blackedecker.php");
	exit;
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$msg = "";

if($_POST['chk_opt1'])    $chk1        = $_POST['chk_opt1'];
if($_POST['chk_opt2'])    $chk2        = $_POST['chk_opt2'];
if($_POST['chk_opt3'])    $chk3        = $_POST['chk_opt3'];
if($_POST['chk_opt4'])    $chk4        = $_POST['chk_opt4'];
if($_POST['chk_opt5'])    $chk5        = $_POST['chk_opt5'];
if($_POST['chk_opt6'])    $chk6        = $_POST['chk_opt6'];
if($_POST['chk_opt7'])    $chk7        = $_POST['chk_opt7'];
if($_POST['chk_opt8'])    $chk8        = $_POST['chk_opt8'];
if($_POST['chk_opt9'])    $chk9        = $_POST['chk_opt9'];
if($_POST['chk_opt10'])   $chk10       = $_POST['chk_opt10'];
if($_POST['tipo_pedido']) $tipo_pedido = $_POST['tipo_pedido'];
if($_POST['tipo'])        $tipo        = $_POST['tipo'];

if($_GET['chk_opt1'])    $chk1        = $_GET['chk_opt1'];
if($_GET['chk_opt2'])    $chk2        = $_GET['chk_opt2'];
if($_GET['chk_opt3'])    $chk3        = $_GET['chk_opt3'];
if($_GET['chk_opt4'])    $chk4        = $_GET['chk_opt4'];
if($_GET['chk_opt5'])    $chk5        = $_GET['chk_opt5'];
if($_GET['chk_opt6'])    $chk6        = $_GET['chk_opt6'];
if($_GET['chk_opt7'])    $chk7        = $_GET['chk_opt7'];
if($_GET['chk_opt8'])    $chk8        = $_GET['chk_opt8'];
if($_GET['chk_opt9'])    $chk9        = $_GET['chk_opt9'];
if($_GET['chk_opt10'])   $chk10       = $_GET['chk_opt10'];
if($_GET['tipo_pedido']) $tipo_pedido = $_GET['tipo_pedido'];
if($_GET['tipo'])        $tipo        = $_GET['tipo'];

if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])			$data_final_01      = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])			$codigo_posto       = trim($_POST['codigo_posto']);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])			$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["numero_os"])				$numero_os          = trim($_POST["numero_os"]);
if($_POST["numero_nf"])				$numero_nf          = trim($_POST["numero_nf"]);
if($_POST["nome_revenda"])			$nome_revenda       = trim($_POST["nome_revenda"]);
if($_POST["cnpj_revenda"])			$cnpj_revenda       = trim($_POST["cnpj_revenda"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])			$data_final_01      = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])			$codigo_posto       = trim($_GET['codigo_posto']);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["produto_nome"])			$produto_nome       = trim($_GET["produto_nome"]);
if($_GET["numero_os"])				$numero_os          = trim($_GET["numero_os"]);
if($_GET["numero_nf"])				$numero_nf          = trim($_GET["numero_nf"]);
if($_GET["nome_revenda"])			$nome_revenda       = trim($_GET["nome_revenda"]);
if($_GET["cnpj_revenda"])			$cnpj_revenda       = trim($_GET["cnpj_revenda"]);

if($_GET["estado"])					$estado             = trim($_GET["estado"]); //HD 280384
//echo $tipo;
$layout_menu = "callcenter";
$title = "Relação de Pedidos Lançados";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

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
	$dt = 0;
	
	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

	if($login_fabrica == 72){
		$sql = "SELECT  tbl_pedido.pedido						,
				tbl_posto.nome AS posto_nome				,
				tbl_posto_fabrica.codigo_posto				,
				tbl_pedido.fabrica							,
				tbl_peca.referencia						,
				tbl_peca.descricao						,
				tbl_pedido_item.qtde						,
				tbl_pedido.pedido_cliente                          ,
				to_char(tbl_pedido.data,'DD/MM/YYYY') AS data	,
				tbl_tipo_pedido.descricao AS descricao_tipo_pedido	,
				tbl_status_pedido.descricao AS descricao_status_pedido
			FROM tbl_posto
				JOIN tbl_pedido USING (posto)
				JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
				JOIN tbl_peca using(peca)
				JOIN tbl_tipo_pedido USING (tipo_pedido)
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
				LEFT JOIN tbl_produto on tbl_produto.produto = tbl_pedido.produto
				LEFT JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			WHERE tbl_pedido.fabrica = $login_fabrica AND (1=2 ";
	}else{
		$sql = "SELECT  tbl_pedido.pedido                                  ,
						tbl_posto.nome                    AS posto_nome    ,
						tbl_posto_fabrica.codigo_posto                     ,
						tbl_pedido.fabrica                                 ,
						tbl_pedido.pedido_cliente                          ,
						to_char(tbl_pedido.data,'DD/MM/YYYY') AS data      ,
						tbl_tipo_pedido.descricao AS descricao_tipo_pedido ,
						tbl_status_pedido.descricao AS descricao_status_pedido
				FROM	tbl_posto
				JOIN	tbl_pedido USING (posto)
				JOIN	tbl_tipo_pedido USING (tipo_pedido)
				JOIN	tbl_posto_fabrica    on tbl_posto_fabrica.posto   = tbl_posto.posto
											and tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
				LEFT JOIN tbl_produto		 on tbl_produto.produto       = tbl_pedido.produto
				LEFT JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
				WHERE	tbl_pedido.fabrica = $login_fabrica AND (1=2 ";
	}


if (strlen($chk1) > 0) {
	// data do dia
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_query ($con,$sqlX);
	$dia_hoje_inicio = pg_fetch_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_fetch_result ($resX,0,0) . " 23:59:59";

	$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_query ($con,$sqlX);
	#  $dia_hoje_final = pg_fetch_result ($resX,0,0);

	$monta_sql .=" OR (tbl_pedido.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " &middot; Pedidos lançados hoje";

}

if (strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_query ($con,$sqlX);
	$dia_ontem_inicial = pg_fetch_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_fetch_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados ontem";

}

if (strlen($chk3) > 0) {
	// nesta semana
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_query ($con,$sqlX);
	$dia_semana_hoje = pg_fetch_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_query ($con,$sqlX);
	$dia_semana_inicial = pg_fetch_result ($resX,0,0) . " 00:00:00";

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_query ($con,$sqlX);
	$dia_semana_final = pg_fetch_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados nesta semana";

}

if (strlen($chk4) > 0) {
	// semana anterior
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_query ($con,$sqlX);
	$dia_semana_hoje = pg_fetch_result ($resX,0,0) - 1 + 7 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_query ($con,$sqlX);
	$dia_semana_inicial = pg_fetch_result ($resX,0,0) . " 00:00:00";

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_query ($con,$sqlX);
	$dia_semana_final = pg_fetch_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados na semana anterior";

}

if (strlen($chk5) > 0)
{
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
	$monta_sql .= " OR (tbl_pedido.data BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e Pedidos lançados neste mês";

}

if (strlen($chk6) > 0) {
	// do mês selecionado
	$data_inicial     = $data_inicial_01;
	$data_final       = $data_final_01;
	$monta_sql .=" OR (tbl_pedido.data::date BETWEEN fnc_formata_data('$data_inicial') AND fnc_formata_data('$data_final')) ";
	$dt = 1;

	$msg .= "Pedidos lançados entre os dias $data_inicial e $data_final ";

}

if (strlen($chk7) > 0)
{
	// posto
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto ='$codigo_posto'";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo posto $nome_posto";

}

if (strlen($chk8) > 0)
{
	// aparelho
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_produto.referencia ='".$produto_referencia."' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo produto $peca_descricao";

}

if (strlen($chk9) > 0)
{
	// cliente
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_pedido.pedido_cliente='".$numero_pedido."' OR tbl_pedido.pedido = $numero_pedido ";

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";

}

if (strlen($tipo_pedido) > 0)
{
	// tipo de pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	
	switch ($tipo_pedido) {
		case 1:
			break;
		case 2:
			$monta_sql .= "$xsql tbl_pedido.status_pedido = (
							SELECT tbl_status_pedido.status_pedido
							FROM   tbl_status_pedido
							WHERE  tbl_status_pedido.descricao = 'Faturado Parcial'
						)";
			$msg .= " e Pedidos com fechamento Parcial";
			break;
		case 3:
			$monta_sql .= "$xsql tbl_pedido.status_pedido = (
							SELECT tbl_status_pedido.status_pedido
							FROM   tbl_status_pedido
							WHERE  tbl_status_pedido.descricao = 'Faturado Integral'
						)";
			$msg .= " e Pedidos com fechamento Integral";
			break;
		case 4:
			$monta_sql .= "$xsql tbl_pedido.status_pedido = (
							SELECT tbl_status_pedido.status_pedido
							FROM   tbl_status_pedido
							WHERE  tbl_status_pedido.descricao = 'Aguardando Faturamento'
						)";
			$msg .= " e Pedidos não atendidos";
			break;
	}

}

if (strlen($tipo) > 0)
{
	// tipo de pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	
	$monta_sql .= "$xsql tbl_pedido.tipo_pedido = $tipo ";
}

if (strlen($estado) > 0 AND $login_fabrica==72) { #HD 280384
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= " $xsql tbl_posto.estado = '$estado' ";
}


// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") ORDER BY tbl_pedido.pedido DESC";

$res = pg_query($con,$sql);
echo "Total de Pedidos no relatório: ".pg_num_rows($res) ;
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

if (@pg_num_rows($res) == 0) {
	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";
}

if (@pg_num_rows($res) > 0) {
	$colspan = $login_fabrica == 72 ? 9 : 6;
	flush();

	echo "<br><br>";
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center' id='processa'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, processando arquivo PostScript</font></td>";
	echo "</tr>";
	echo "</table>";
	
	flush();

	$data = date ("d/m/Y H:i:s");
	$total = pg_num_rows ($res);
	echo `rm /tmp/assist/relatorio-consulta-pedido-$login_fabrica.xls`;

	$fp = fopen ("/tmp/assist/relatorio-consulta-pedido-$login_fabrica.html","w");

	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>RELATÓRIO DE PEDIDOS - $data - $msg");
	fputs ($fp,"</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");
	fputs ($fp,"<TABLE width='700' align='center' border='1' cellspacing='0' cellpadding='1'>\n");

	fputs ($fp, "<tr  align='center'>\n");
	fputs ($fp, "<td colspan='$colspan' bgcolor='#0000FF'><FONT  COLOR='#FFFFFF'><b>RELATÓRIO DE PEDIDOS - $data - $msg</b></FONT></td>\n");
	fputs ($fp, "</tr>\n");

	fputs ($fp,"<TR class='menu_top'>\n");
	fputs ($fp,"	<TD  bgcolor='#FFCC00'>PEDIDO</TD>\n");
	fputs ($fp,"	<TD  bgcolor='#FFCC00'>PEDIDO CLIENTE</TD>\n");
	if($login_fabrica == 72){
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>QTDE</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>REFERÊNCIA</TD>\n");
		fputs ($fp,"	<TD  bgcolor='#FFCC00'>DESCRIÇÃO</TD>\n");
	}
	fputs ($fp,"	<TD  bgcolor='#FFCC00'>TIPO</TD>\n");
	fputs ($fp,"	<TD  bgcolor='#FFCC00'>STATUS</TD>\n");
	fputs ($fp,"	<TD  bgcolor='#FFCC00'>DATA</TD>\n");
	fputs ($fp,"	<TD  bgcolor='#FFCC00'>POSTO</TD>\n");
	fputs ($fp,"</TR>\n");

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++){
		$pedido             = trim(pg_fetch_result ($res,$i,pedido));
		$pedido_cliente     = trim(pg_fetch_result ($res,$i,pedido_cliente));
		$descricao_tipo               = trim(pg_fetch_result ($res,$i,descricao_tipo_pedido));
		if($login_fabrica == 72){
			$referencia               = trim(pg_fetch_result ($res,$i,referencia));
			$descricao               = trim(pg_fetch_result ($res,$i,descricao));
			$qtde               = trim(pg_fetch_result ($res,$i,qtde));
		}
		if ($login_fabrica == 2)
			$status             = "OK";
		else
			$status         = trim(pg_fetch_result ($res,$i,descricao_status_pedido));
		$data               = trim(pg_fetch_result ($res,$i,data));
		$codigo_posto       = trim(pg_fetch_result ($res,$i,codigo_posto));
		$posto_nome         = trim(pg_fetch_result ($res,$i,posto_nome));
		
		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}
		
		fputs ($fp,"<TR class='table_line'>\n");
		fputs ($fp,"	<TD style='padding-left:5px; background-color: $cor;'><a href='http://posvenda.telecontrol.com.br/assist/admin/pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido</font></a></TD>\n");
		fputs ($fp,"	<TD style='padding-left:5px; background-color: $cor;'>$pedido_cliente</TD>\n");
		if($login_fabrica == 72){
			fputs ($fp,"	<TD style='padding-left:5px; background-color: $cor;'>$qtde</TD>\n");
			fputs ($fp,"	<TD style='padding-left:5px; background-color: $cor;'>$referencia</TD>\n");
			fputs ($fp,"	<TD style='padding-left:5px; background-color: $cor;'>$descricao</TD>\n");
		}
		fputs ($fp,"	<TD style='padding-left:5px; background-color: $cor;'>$descricao_tipo</TD>\n");
		fputs ($fp,"	<TD style='padding-left:5px; background-color: $cor;' nowrap>$status</TD>\n");
		fputs ($fp,"	<TD align='center' style ='background-color: $cor;'>$data</TD>\n");
		fputs ($fp,"	<TD nowrap style='background-color: $cor;'>$codigo_posto - <ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,14)."</ACRONYM></TD>\n");
		fputs ($fp,"</TR>\n");		
	
	}
	fputs ($fp, "<tr  align='center'>\n");
	fputs ($fp, "<td colspan='$colspan' bgcolor='#0000FF'><FONT  COLOR='#FFFFFF'><b>TOTAL DE PEDIDOS: $total </b></FONT></td>\n");
	fputs ($fp, "</tr>\n");
	fputs ($fp,"</TABLE>\n");

	fputs ($fp,"</body>");
	fputs ($fp,"</html>");
	fclose ($fp);


	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-consulta-pedido-$login_fabrica.$data.xls /tmp/assist/relatorio-consulta-pedido-$login_fabrica.html`;
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio-consulta-pedido-$login_fabrica.$data.xls' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";

}

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";
	
?>
	<script>
		window.onload = function (){
			document.getElementById('processa').style.display='none';
		}

	</script>
<p>

<? include "rodape.php"; ?>
