<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';
include "funcoes.php";

if($login_fabrica != 1) {
	header("Location: menu_callcenter.php");
	exit;
}

##### F I N A L I Z A R   P E D I D O #####
if ($_GET["finalizar"] == 1 AND $_GET["unificar"] == "t") {

	$pedido    = $_GET["pedido"];

	$sql =	"UPDATE tbl_pedido SET
				unificar_pedido = 't'
			WHERE tbl_pedido.pedido = $pedido
			AND   tbl_pedido.unificar_pedido ISNULL;";
	$res = pg_exec ($con,$sql);

	if (strlen(pg_errormessage($con)) > 0) {
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "INSERT INTO tbl_pedido_alteracao (
					pedido
				)VALUES(
					$pedido
				);";
		$res = pg_exec($con,$sql);

		if (strlen(pg_errormessage($con)) > 0) {
			$msg_erro = pg_errormessage($con) ;
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_suframa($pedido,$login_fabrica);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	if (strlen($msg_erro) == 0) {
		header ("Location: ".$_COOKIE["CookieLink"]);
		exit;
	}
}

setcookie("CookieLink","http://posvenda.telecontrol.com.br".$_SERVER["REQUEST_URI"]);

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

if($_POST["data_inicial_01"])	$data_inicial_01 = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])		$data_final_01   = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])		$codigo_posto    = trim($_POST['codigo_posto']);
if($_POST["peca_referencia"])	$peca_referencia = trim($_POST["peca_referencia"]);
if($_POST["peca_descricao"])	$peca_descricao  = trim($_POST["peca_descricao"]);
if($_POST["numero_pedido"])		$numero_pedido   = trim($_POST["numero_pedido"]);
if($_POST["numero_nf"])			$numero_nf       = trim($_POST["numero_nf"]);
if($_POST["nome_revenda"])		$nome_revenda    = trim($_POST["nome_revenda"]);
if($_POST["cnpj_revenda"])		$cnpj_revenda    = trim($_POST["cnpj_revenda"]);

if($_GET["data_inicial_01"])	$data_inicial_01 = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])		$data_final_01   = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])		$codigo_posto    = trim($_GET['codigo_posto']);
if($_GET["peca_referencia"])	$peca_referencia = trim($_GET["peca_referencia"]);
if($_GET["peca_descricao"])		$peca_descricao  = trim($_GET["peca_descricao"]);
if($_GET["numero_pedido"])			$numero_pedido       = trim($_GET["numero_pedido"]);
if($_GET["numero_nf"])			$numero_nf       = trim($_GET["numero_nf"]);
if($_GET["nome_revenda"])		$nome_revenda    = trim($_GET["nome_revenda"]);
if($_GET["cnpj_revenda"])		$cnpj_revenda    = trim($_GET["cnpj_revenda"]);

//if (strlen($numero_pedido) > 0) $numero_pedido = $numero_pedido - 1000;
//echo $tipo;
$layout_menu = "callcenter";
$title = "RELAÇÃO DE PEDIDOS DE ACESSÓRIOS";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}



.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>

<?
	$dt = 0;

	// BTN_NOVA BUSCA
	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros_blackedecker_acessorio.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";

	$sql =	"SELECT DISTINCT tbl_pedido.pedido                                                      ,
					tbl_pedido.pedido_blackedecker                                                  ,
					tbl_pedido.seu_pedido                                                           ,
					tbl_pedido.pedido_acessorio                                                     ,
					tbl_pedido.unificar_pedido                                                      ,
					tbl_pedido.fabrica                                                              ,
					to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI:SS')  AS exportado             ,
					to_char(tbl_pedido.data,'DD/MM/YYYY HH24:MI:SS')       AS data                  ,
					to_char(tbl_pedido.finalizado,'DD/MM/YYYY HH24:MI:SS') AS finalizado            ,
					tbl_pedido.finalizado                                                           ,
					tbl_posto_fabrica.codigo_posto                                                  ,
					tbl_posto.nome                                         AS nome_posto            ,
					tbl_posto.estado                                       AS estado_posto          ,
					tbl_tipo_posto.descricao                               AS tipo_posto            ,
					tbl_tipo_pedido.descricao                              AS descricao_tipo_pedido ,
					tbl_tabela.sigla_tabela                                                         ,
					tbl_condicao.descricao                                 AS condicao_descricao    ,
					tbl_admin.login                                                                 ,
					(
						SELECT SUM (tbl_pedido_item.qtde * tbl_pedido_item.preco) AS total
						FROM  tbl_pedido_item
						WHERE tbl_pedido_item.pedido = tbl_pedido.pedido
						GROUP BY tbl_pedido_item.pedido
					)                                          AS total,
										( 
						SELECT SUM ((tbl_pedido_item.qtde * tbl_pedido_item.preco)+((tbl_pedido_item.qtde * tbl_pedido_item.preco) * tbl_peca.ipi / 100)) AS total 
						FROM tbl_pedido_item 
						join tbl_peca using(peca)
						WHERE tbl_pedido_item.pedido = tbl_pedido.pedido 
						GROUP BY tbl_pedido_item.pedido 
					) AS total_com_ipi 
			FROM	tbl_posto
			JOIN	tbl_pedido USING (posto)
			JOIN	tbl_pedido_item USING (pedido)
			JOIN	tbl_peca USING (peca)
			JOIN	tbl_tipo_pedido USING (tipo_pedido)
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_posto.posto
									 AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
			JOIN	tbl_tipo_posto    ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			JOIN	tbl_tabela ON tbl_pedido.tabela = tbl_tabela.tabela
			JOIN	tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_admin ON tbl_pedido.admin = tbl_admin.admin
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_pedido.produto
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE	tbl_pedido.fabrica = $login_fabrica
			AND		tbl_pedido.pedido_acessorio IS TRUE
			AND (1=2 ";

if (strlen($chk1) > 0) {
	// data do dia
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_exec ($con,$sqlX);

	$monta_sql .=" OR (tbl_pedido.exportado BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= " &middot; Pedidos lançados hoje";
}

if (strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.exportado BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados ontem";
}

if (strlen($chk3) > 0) {
	// Nesta Semana
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.exportado BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados nesta semana";
}

if (strlen($chk4) > 0) {
	// semana anterior
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 + 7 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.exportado BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados na semana anterior";
}

if (strlen($chk5) > 0)
{
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
	$monta_sql .= " OR (tbl_pedido.exportado BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e Pedidos lançados neste mês";
}

if (strlen($chk6) > 0)
{
	// do mês selecionado
	$data_inicial     = $data_inicial_01;
	$data_final       = $data_final_01;
	
	//Início Validação de Datas
	if($data_inicial_01){		
		$dat = explode ("/", $data_inicial_01 );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if($data_final_01){
		$dat = explode ("/", $data_final_01 );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		
		$d_ini = explode ("/", $data_inicial_01);//tira a barra
		$data_inicial_01 = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $data_final_01);//tira a barra
		$data_final_01 = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($data_final_01 < $data_inicial_01){
			$msg_erro = "Data Inválida";
		}

		
		//Fim Validação de Datas
	}
	if(strlen($msg_erro)==0){
		$monta_sql .=" OR (tbl_pedido.exportado::date BETWEEN fnc_formata_data('$data_inicial') AND fnc_formata_data('$data_final')) ";
		$dt = 1;

		$msg .= "Pedidos lançados entre os dias $data_inicial e $data_final ";
	}
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
	// peça
	$peca_referencia = str_replace("-","",$peca_referencia);

	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_peca.referencia_pesquisa = '".$peca_referencia."' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pela peça $peca_descricao";

}

if (strlen($chk9) > 0)
{
	// cliente
	$numero_pedido1 = $numero_pedido + 200000;
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql (tbl_pedido.pedido_cliente='".$numero_pedido."' OR tbl_pedido.pedido_blackedecker in ($numero_pedido,$numero_pedido1)  OR substr(tbl_pedido.seu_pedido,4) = '$numero_pedido' OR tbl_pedido.seu_pedido = '$numero_pedido' ) ";

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";
}

if (strlen($chk10) > 0)
{
	// cliente
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql ( tbl_pedido.finalizado IS NULL AND tbl_pedido.exportado IS NULL )";

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

// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") ORDER BY tbl_pedido.pedido ASC";
#echo nl2br($sql); exit;

$res = @pg_exec($con,$sql);


$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";
/*
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD colspan='5'>$msg        </TD>\n";
echo "</TR>\n";
echo "</table>\n";
*/
if (@pg_numrows($res) == 0 AND strlen($msg_erro)==0) {
	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";
}
if(strlen($msg_erro)>0){
	echo "<table width='700' align='center'>";
	echo "<tr class='msg_erro'><td>$msg_erro</td></tr>";
	echo "</table>";
}

if (@pg_numrows($res) > 0){
	$total_pedidos = pg_numrows($res);
	//echo "<center><h3>Existem ".$total_pedidos." pedidos no sistema</h3></center>";
}


if (@pg_numrows($res) > 0) {
	echo "<TABLE width='990' align='center' border='0' cellspacing='1' cellpadding='2' class='tabela'>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido              = trim(pg_result ($res,$i,pedido));
		$pedido_blackedecker = trim(pg_result ($res,$i,pedido_blackedecker));
		$seu_pedido          = trim(pg_result ($res,$i,seu_pedido));
		$pedido_acessorio    = trim(pg_result ($res,$i,pedido_acessorio));
		$unificar_pedido     = trim(pg_result ($res,$i,unificar_pedido));
		$descricao_tipo      = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$data                = trim(pg_result ($res,$i,data));
		$finalizado          = trim(pg_result ($res,$i,finalizado));
		$exportado           = trim(pg_result ($res,$i,exportado));
		$login               = trim(pg_result ($res,$i,login));
		$codigo_posto        = trim(pg_result ($res,$i,codigo_posto));
		$nome_posto          = trim(pg_result ($res,$i,nome_posto));
		$posto_completo      = $codigo_posto . " - " . $nome_posto;
		$estado_posto        = trim(pg_result ($res,$i,estado_posto));
		$tipo_posto          = trim(pg_result ($res,$i,tipo_posto));
		$condicao_descricao  = trim(pg_result ($res,$i,condicao_descricao));
		$sigla_tabela        = trim(pg_result ($res,$i,sigla_tabela));
		$total               = trim(pg_result ($res,$i,total));
		$total_com_ipi       = trim(pg_result ($res,$i,total_com_ipi));
		$total_geral         = $total_geral + $total;
		$total               = number_format($total,2,",",".");
		$total_geral_com_ipi = $total_geral_com_ipi + $total_com_ipi;
		$total_com_ipi       = number_format($total_com_ipi,2,",",".");

		if($pedido_blackedecker > 99999){ $pedido_blackedecker = $pedido_blackedecker - 1000; }
		$pedido_blackedecker = "00000".$pedido_blackedecker;
		$pedido_blackedecker = substr($pedido_blackedecker, strlen($pedido_blackedecker)-5, strlen($pedido_blackedecker));

		if ($unificar_pedido == 't') $unificar_pedido = "S";
		else                         $unificar_pedido = "N";

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}

		echo "<TR><TD colspan='12' style='border:0px;'>&nbsp;</TD></TR>\n";

		echo "<TR class='titulo_coluna'>\n";
		echo "<TD>UP</TD>\n";
		echo "<TD>Admin</TD>\n";
		echo "<TD>Pedido</TD>\n";
		echo "<TD>Abertura</TD>\n";
		echo "<TD>Finalizado</TD>\n";
		echo "<TD>Posto</TD>\n";
		echo "<TD>Região</TD>\n";
		echo "<TD>Tipo</TD>\n";
		echo "<TD>Condição</TD>\n";
		echo "<TD>Tabela</TD>\n";
		echo "<TD>Total</TD>\n";
		echo "<TD>Total+IPI</TD>\n";
		echo "<TD>Ações</TD>\n";
		echo "</TR>\n";
		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		$cor2 = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		echo "<TR bgcolor='$cor'>\n";
		echo "<TD align='center'>$unificar_pedido</TD>\n";
		echo "<TD>$login</TD>\n";
		#$pedido_blackedecker = intval($pedido_blackedecker + 1000); // agora usa o SEU_PEDIDO
		echo "<TD><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido_blackedecker</font></a></TD>\n";
		echo "<TD align='center'>$data</TD>\n";
		echo "<TD align='center'>$finalizado</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE='$codigo_posto - $nome_posto'>" . substr($posto_completo,0,25) . "</ACRONYM></TD>\n";
		echo "<TD align='center'>$estado_posto</TD>\n";
		echo "<TD align='center'>$tipo_posto</TD>\n";
		echo "<TD align='center'>$condicao_descricao</TD>\n";
		echo "<TD align='center'>$sigla_tabela</TD>\n";
		echo "<TD align='center'>$total</TD>\n";
		echo "<TD align='center'>$total_com_ipi</TD>\n";
		echo "<TD align='center' nowrap>&nbsp;";
		echo "<a href='pedido_cadastro_blackedecker.php?pedido=$pedido' target='_blank'><img src='imagens/btn_alterarcinza.gif'></a>";
		if (strlen($exportado) == 0) {
			echo "&nbsp;<a href='$PHP_SELF?pedido=$pedido&finalizar=1&unificar=t'><img src='imagens/btn_finalizar.gif' border='0' style='cursor: hand;'></a>";
		}
		echo "&nbsp;</TD>\n";

		echo "</TR>\n";

		if (strlen($exportado) > 0) {
			echo "<TR bgcolor='$cor2'>\n";
			echo "<TD align='left' colspan='13'>Enviado para fábrica em $exportado</TD>\n";
			echo "</TR>\n";
		}
	}
	echo "</TABLE>\n";
	echo "<br>\n";

	echo "<TABLE width='990' align='center' border='0' cellspacing='1' cellpadding='2' class='tabela'>\n";
	echo "<TR bgcolor='#D9E2EF'>\n";
	echo "<TD align='right'><b>Número de Pedidos para o Período</b></TD>\n";
	echo "<TD align='right'><b>". $total_pedidos ."</b></TD>\n";
	echo "</TR>\n";
	echo "<TR bgcolor='#D9E2EF'>\n";
	echo "<TD align='right'><b>Total Geral</b></TD>\n";
	echo "<TD align='right'><b>". number_format($total_geral,2,",",".") ."</b></TD>\n";
	echo "</TR>\n";
	echo "<TR bgcolor='#D9E2EF'>\n";
	echo "<TD align='right'><b>Total Geral com IPI</b></TD>\n";
	echo "<TD align='right'><b>". number_format($total_geral_com_ipi,2,",",".") ."</b></TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
	echo "<br>\n";
}

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros_blackedecker_acessorio.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";
?>


<?
if (1 == 2) {
	$sql         = "";
	$monta_sql   = "";
	$msg         = "";
	$total_geral = 0;
	$msg = "Pedidos não finalizados <br>";
	// BTN_NOVA BUSCA NÃO EXPORTADOS
	$sql =	"SELECT DISTINCT tbl_pedido.pedido                                          ,
					tbl_pedido.pedido_blackedecker                                      ,
					tbl_pedido.unificar_pedido                                          ,
					tbl_pedido.fabrica                                                  ,
					to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI:SS') AS exportado  ,
					to_char(tbl_pedido.data,'DD/MM/YYYY HH24:MI:SS')      AS data       ,
					tbl_pedido.finalizado                                               ,
					tbl_posto_fabrica.codigo_posto                                      ,
					tbl_posto.nome                             AS nome_posto            ,
					tbl_posto.estado                           AS estado_posto          ,
					tbl_tipo_posto.descricao                   AS tipo_posto            ,
					tbl_tipo_pedido.descricao                  AS descricao_tipo_pedido ,
					tbl_tabela.sigla_tabela                                             ,
					tbl_condicao.descricao                     AS condicao_descricao    ,
					tbl_admin.login                                                     ,
					tbl_pedido.total
			FROM	tbl_posto
			JOIN	tbl_pedido USING (posto)
			JOIN	tbl_pedido_item USING (pedido)
			JOIN	tbl_peca USING (peca)
			JOIN	tbl_tipo_pedido USING (tipo_pedido)
			JOIN	tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									  AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
			JOIN	tbl_tipo_posto USING (tipo_posto)
			JOIN	tbl_tabela USING (tabela)
			JOIN	tbl_condicao USING (condicao)
			LEFT JOIN tbl_admin ON tbl_pedido.admin = tbl_admin.admin
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_pedido.produto
			LEFT JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE	tbl_pedido.fabrica = $login_fabrica
			AND		tbl_pedido.pedido_acessorio IS TRUE
			AND (1=2 ";

if (strlen($chk1) > 0) {
	// data do dia
	$monta_sql .=" OR (tbl_pedido.finalizado IS NULL AND tbl_pedido.total IS NULL) ";
	$dt = 1;

	$msg .= " &middot; Pedidos lançados hoje";
}

if (strlen($chk2) > 0) {
	$monta_sql .=" OR (tbl_pedido.finalizado IS NULL AND tbl_pedido.total IS NULL) ";
	$dt = 1;

	$msg .= " e Pedidos lançados ontem";
}

if (strlen($chk3) > 0) {
	// Nesta Semana
	$monta_sql .=" OR (tbl_pedido.finalizado IS NULL AND tbl_pedido.total IS NULL) ";
	$dt = 1;

	$msg .= " e Pedidos lançados nesta semana";
}

if (strlen($chk4) > 0) {
	// semana anterior
	$monta_sql .=" OR (tbl_pedido.finalizado IS NULL AND tbl_pedido.total IS NULL) ";
	$dt = 1;

	$msg .= " e Pedidos lançados na semana anterior";
}

if (strlen($chk5) > 0) {
	$monta_sql .= " OR (tbl_pedido.finalizado IS NULL AND tbl_pedido.total IS NULL) ";
	$dt = 1;

	$msg .= " e Pedidos lançados neste mês";
}

if (strlen($chk6) > 0) {
	// do mês selecionado
	$monta_sql .=" OR (tbl_pedido.finalizado IS NULL AND tbl_pedido.total IS NULL) ";
	$dt = 1;

	$msg .= "Pedidos lançados entre os dias $data_inicial e $data_final ";
}

if (strlen($chk7) > 0) {
	// posto
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto ='$codigo_posto' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo posto $nome_posto";
}

if (strlen($chk8) > 0) {
	// peça
	$peca_referencia = str_replace("-","",$peca_referencia);

	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_peca.referencia_pesquisa = '".$peca_referencia."' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pela peça $peca_descricao";
}

if (strlen($chk9) > 0) {
	// cliente
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_pedido.pedido_cliente='".$numero_pedido."' OR tbl_pedido.pedido_blackedecker = $numero_pedido ";

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";
}

if (strlen($chk10) > 0) {
	// cliente
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql (tbl_pedido.finalizado IS NULL AND tbl_pedido.total IS NULL)";

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

if (strlen($tipo) > 0) {
	// tipo de pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql tbl_pedido.tipo_pedido = $tipo ";
}

// ordena sql padrao

$sql .= $monta_sql;
$sql .= ") ORDER BY tbl_pedido.pedido ASC";

$res = pg_exec($con,$sql);

$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD colspan='5'>$msg</TD>\n";
echo "</TR>\n";
echo "</table>\n";

if (@pg_numrows($res) == 0) {
	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";
}

if (@pg_numrows($res) > 0) {
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido              = trim(pg_result ($res,$i,pedido));
		$pedido_blackedecker = trim(pg_result ($res,$i,pedido_blackedecker));
		$unificar_pedido     = trim(pg_result ($res,$i,unificar_pedido));
		$descricao_tipo      = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$data                = trim(pg_result ($res,$i,data));
		$exportado           = trim(pg_result ($res,$i,exportado));
		$login               = trim(pg_result ($res,$i,login));
		$codigo_posto        = trim(pg_result ($res,$i,codigo_posto));
		$nome_posto          = trim(pg_result ($res,$i,nome_posto));
		$posto_completo      = $codigo_posto . " - " . $nome_posto;
		$tipo_posto          = trim(pg_result ($res,$i,tipo_posto));
		$estado_posto        = trim(pg_result ($res,$i,estado_posto));
		$condicao_descricao  = trim(pg_result ($res,$i,condicao_descricao));
		$sigla_tabela        = trim(pg_result ($res,$i,sigla_tabela));
		$total               = trim(pg_result ($res,$i,total));
		$total_geral         = $total_geral + $total;

		$pedido_blackedecker = "00000".$pedido_blackedecker;
		$pedido_blackedecker = substr($pedido_blackedecker, strlen($pedido_blackedecker)-5, strlen($pedido_blackedecker));

		if ($unificar_pedido == 't') $unificar_pedido = "S";
		else                         $unificar_pedido = "N";

		echo "<TR><TD colspan='12'>&nbsp;</TD></TR>\n";

		echo "<TR class='menu_top'>\n";
		echo "<TD>UP</TD>\n";
		echo "<TD>ADMIN</TD>\n";
		echo "<TD>PEDIDO</TD>\n";
		echo "<TD>ABERTURA</TD>\n";
		echo "<TD>POSTO</TD>\n";
		echo "<TD>REGIÃO</TD>\n";
		echo "<TD>TIPO</TD>\n";
		echo "<TD>CONDIÇÃO</TD>\n";
		echo "<TD>TABELA</TD>\n";
		echo "<TD>TOTAL</TD>\n";
		echo "<TD>AÇÕES</TD>\n";
		echo "</TR>\n";

		echo "<TR class='table_line' bgcolor='#F1F4FA'>\n";
		echo "<TD align='center'>$unificar_pedido</TD>\n";
		echo "<TD>$login</TD>\n";
		$pedido_blackedecker = intval($pedido_blackedecker + 1000);
		echo "<TD><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido_blackedecker</font></a></TD>\n";
		echo "<TD align='center'>$data</TD>\n";
		echo "<TD nowrap><acronym title='CÓDIGO: $codigo_posto\nRAZÃO SOCIAL: $nome_posto'>" . substr($posto_completo,0,25) . "</ACRONYM></TD>\n";
		echo "<TD align='center'>$estado_posto</TD>\n";
		echo "<TD align='center'>$tipo_posto</TD>\n";
		echo "<TD align='center'>$condicao_descricao</TD>\n";
		echo "<TD align='center'>$sigla_tabela</TD>\n";
		echo "<TD align='center'>". number_format($total,2,",",".") ."</TD>\n";
		echo "<TD align='center' nowrap>&nbsp;";
		echo "<a href='pedido_cadastro_blackedecker.php?pedido=$pedido' target='_blank'><img src='imagens/btn_alterarcinza.gif'></a>";
		if (strlen($exportado) == 0) {
			echo "&nbsp;<a href='$PHP_SELF?pedido=$pedido&finalizar=1&unificar=t'><img src='imagens/btn_finalizar.gif' border='0' style='cursor: hand;'></a>";
		}
		echo "&nbsp;</TD>\n";

		echo "</TR>\n";

		if (strlen($exportado) > 0) {
			echo "<TR class='table_line'>\n";
			echo "<TD align='left' colspan='12'>Enviado para fábrica em $exportado</TD>\n";
			echo "</TR>\n";
		}
	}
	echo "</TABLE>\n";
	echo "<br>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";
	echo "<TR class='table_line'>\n";
	echo "<TD align='center'><b>TOTAL GERAL</b></TD>\n";
	echo "<TD align='right'><b>". number_format($total_geral,2,",",".") ."</b></TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
	echo "<br>\n";
}

echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='pedido_parametros_blackedecker_acessorio.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</TR>";
echo "</TABLE>";
} # 1 == 2
?>

<p>

<? include "rodape.php"; ?>
