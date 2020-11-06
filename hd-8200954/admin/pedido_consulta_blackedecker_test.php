<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia,call_center";
include "autentica_admin.php";

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

setcookie("CookieLink","http://www.telecontrol.com.br".$_SERVER["REQUEST_URI"]);

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
if($_POST['chk_opt11'])   $chk11       = $_POST['chk_opt11'];
if($_POST['chk_opt12'])   $chk12       = $_POST['chk_opt12'];
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
if($_GET['chk_opt11'])   $chk11       = $_GET['chk_opt11'];
if($_GET['chk_opt12'])   $chk12       = $_GET['chk_opt12'];
if($_GET['tipo_pedido']) $tipo_pedido = $_GET['tipo_pedido'];
if($_GET['tipo'])        $tipo        = $_GET['tipo'];

if($_POST["data_inicial_01"])	$data_inicial_01 = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])		$data_final_01   = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])		$codigo_posto    = trim($_POST['codigo_posto']);
if($_POST["peca_referencia"])	$peca_referencia = trim($_POST["peca_referencia"]);
if($_POST["peca_descricao"])	$peca_descricao  = trim($_POST["peca_descricao"]);
if($_POST["numero_os"])			$numero_os       = trim($_POST["numero_os"]);
if($_POST["numero_nf"])			$numero_nf       = trim($_POST["numero_nf"]);
if($_POST["nome_revenda"])		$nome_revenda    = trim($_POST["nome_revenda"]);
if($_POST["cnpj_revenda"])		$cnpj_revenda    = trim($_POST["cnpj_revenda"]);

if($_GET["data_inicial_01"])	$data_inicial_01 = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])		$data_final_01   = trim($_GET["data_final_01"]);
if($_GET['codigo_posto'])		$codigo_posto    = trim($_GET['codigo_posto']);
if($_GET["peca_referencia"])	$peca_referencia = trim($_GET["peca_referencia"]);
if($_GET["peca_descricao"])		$peca_descricao  = trim($_GET["peca_descricao"]);
if($_GET["numero_os"])			$numero_os       = trim($_GET["numero_os"]);
if($_GET["numero_nf"])			$numero_nf       = trim($_GET["numero_nf"]);
if($_GET["nome_revenda"])		$nome_revenda    = trim($_GET["nome_revenda"]);
if($_GET["cnpj_revenda"])		$cnpj_revenda    = trim($_GET["cnpj_revenda"]);
//echo $tipo;


# Desabilitado Alterado por Sono 18/08/2006
# Reabilitado por Tulio 18/08/2006

$data_padrao = "data";
if (strlen($chk10) > 0) $data_padrao = "data";
else                    $data_padrao = "exportado";

$layout_menu = "callcenter";
$title = "Relação de Pedidos de Peças/Produtos";
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
<script language="JavaScript">
var checkflag = "false";
function AbrirJanelaObs (pedido) {
	var largura  = 650;
	var tamanho  = 450;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "obs_pedido_consulta_blackedecker.php?pedido=" + pedido;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>
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

	$sql =	"SELECT DISTINCT tbl_pedido.pedido                                          ,
					tbl_pedido.pedido_blackedecker                                      ,
					tbl_pedido.seu_pedido                                               ,
					tbl_pedido.unificar_pedido                                          ,
					tbl_pedido.fabrica                                                  ,
					to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI:SS')  AS exportado ,
					to_char(tbl_pedido.data,'DD/MM/YYYY HH24:MI:SS')       AS data      ,
					to_char(tbl_pedido.finalizado,'DD/MM/YYYY HH24:MI:SS') AS finalizado,
					tbl_pedido.finalizado                                               ,
					tbl_pedido.pedido_suframa                                           ,
					tbl_pedido.status_pedido                                            ,
					tbl_status_pedido.descricao                AS status_descricao      ,
					tbl_posto_fabrica.codigo_posto                                      ,
					substr(tbl_posto.nome,0,25)                AS nome_posto            ,
					tbl_posto.estado                           AS estado_posto          ,
					tbl_tipo_posto.descricao                   AS tipo_posto            ,
					pedido_tipo_posto.descricao                AS pedido_tipo_posto     ,
					tbl_tipo_pedido.descricao                  AS descricao_tipo_pedido ,
					tbl_tabela.sigla_tabela                                             ,
					tbl_condicao.descricao                     AS condicao_descricao    ,
					tbl_admin.login                                                     ,
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
			FROM      tbl_posto
			JOIN      tbl_pedido        ON  tbl_pedido.posto                = tbl_posto.posto
			JOIN      tbl_pedido_item   ON  tbl_pedido_item.pedido          = tbl_pedido.pedido
			JOIN      tbl_peca          ON  tbl_peca.peca                   = tbl_pedido_item.peca
			JOIN      tbl_tipo_pedido   ON  tbl_tipo_pedido.tipo_pedido     = tbl_pedido.tipo_pedido
			JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto         = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica       = tbl_pedido.fabrica
			JOIN      tbl_tipo_posto    ON  tbl_tipo_posto.tipo_posto       = tbl_posto_fabrica.tipo_posto
			JOIN      tbl_tabela        ON  tbl_tabela.tabela               = tbl_pedido.tabela
			JOIN      tbl_condicao      ON  tbl_condicao.condicao           = tbl_pedido.condicao
			LEFT JOIN tbl_admin         ON  tbl_pedido.admin                = tbl_admin.admin
			LEFT JOIN tbl_produto       ON  tbl_produto.produto             = tbl_pedido.produto
			LEFT JOIN tbl_status_pedido ON  tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			LEFT JOIN tbl_tipo_posto AS pedido_tipo_posto ON pedido_tipo_posto.tipo_posto = tbl_pedido.tipo_posto
			WHERE tbl_pedido.fabrica = $login_fabrica
			AND   tbl_pedido.pedido_acessorio IS FALSE
			AND (1=2 ";

# Data do dia
if (strlen($chk1) > 0) {
	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date,'YYYY-MM-DD')");
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";
	
	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;
	
	$msg .= " &middot; Pedidos lançados hoje";
}
//	if($ip == '201.42.44.145') echo $monta_sql;

# Dia anterior
if (strlen($chk2) > 0) {
	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '1 day','YYYY-MM-DD')");
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados ontem";
}

# Nesta Semana
if (strlen($chk3) > 0) {
	$resX = pg_exec($con,"SELECT TO_CHAR(current_date,'D')");
	$dia_semana_hoje = pg_result($resX,0,0) - 1 ;

	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '$dia_semana_hoje days','YYYY-MM-DD')");
	$dia_semana_inicial = pg_result($resX,0,0) . " 00:00:00";

	$resX = pg_exec($con,"SELECT TO_CHAR('$dia_semana_inicial'::date + INTERVAL '6 days','YYYY-MM-DD')");
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados nesta semana";
}

# Semana anterior
if (strlen($chk4) > 0) {
	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date,'D')");
	$dia_semana_hoje = pg_result($resX,0,0) - 1 + 7 ;

	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '$dia_semana_hoje days','YYYY-MM-DD')");
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$resX = pg_exec ($con,"SELECT TO_CHAR('$dia_semana_inicial'::date + INTERVAL '6 days','YYYY-MM-DD')");
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados na semana anterior";
}

# Neste mês
if (strlen($chk5) > 0) {
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
	$monta_sql .= " OR (tbl_pedido.$data_padrao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= " e Pedidos lançados neste mês";
}

# Entre datas
if (strlen($chk6) > 0) {
	if (trim($data_inicial_01) <> "dd/mm/aaaa") {
		$resD = pg_exec($con,"SELECT fnc_formata_data('$data_inicial_01')");
		$data_inicial     = pg_result($resD,0,0);
	}
	
	if ($data_final_01 <> "dd/mm/aaaa") {
		$resD = pg_exec($con,"SELECT fnc_formata_data('$data_final_01')");
		$data_final     = pg_result($resD,0,0);
	}
	
	if (strlen($data_inicial) > 0 and strlen($data_final) > 0) {
		$monta_sql .=" OR (tbl_pedido.$data_padrao::date BETWEEN '$data_inicial' AND '$data_final') ";
		$dt = 1;
	}
	
	$msg .= "Pedidos lançados entre os dias $data_inicial e $data_final ";
}

# Posto
if (strlen($chk7) > 0) {
	if ($dt == 1) $xsql = " AND ";
	else          $xsql = " OR ";
	$monta_sql .= " $xsql tbl_posto_fabrica.codigo_posto ='$codigo_posto' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo posto $nome_posto";
}

# Peça
if (strlen($chk8) > 0) {
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
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
#Utilizando o campo seu_pedido para a black HD32341
#	$monta_sql .= "$xsql (tbl_pedido.pedido_cliente='".$numero_pedido."' OR tbl_pedido.pedido_blackedecker = $numero_pedido OR tbl_pedido.pedido_blackedecker = ($numero_pedido+100000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+200000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+200000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+300000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+400000)) ";
	$monta_sql .= "$xsql (tbl_pedido.pedido_cliente='".$numero_pedido."' OR substr(tbl_pedido.seu_pedido,4) = '$numero_pedido' OR tbl_pedido.seu_pedido = '$numero_pedido')";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";
}

// Finalizado?
if (strlen($chk10) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql ( tbl_pedido.finalizado IS NULL AND tbl_pedido.exportado IS NULL )";
	$dt = 1;

	$msg .= " e Pedidos não finalizados";
}

// Promocional
if (strlen($chk11) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_condicao.promocao IS TRUE ";
	$dt = 1;

	$msg .= " e Pedidos promocionais";
}
//Sedex
if (strlen($chk12) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_pedido.pedido_sedex IS TRUE ";
	$dt = 1;

	$msg .= " e Pedidos sedex";
}

if (strlen($tipo_pedido) > 0) {
	// Tipo do Pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	/* HD 21725 */
	$tipo_pedido_aux = explode('|',trim($tipo_pedido));
	if (count($tipo_pedido_aux)==2){
		if ($tipo_pedido_aux[1]=="produto"){
			$monta_sql .= " AND tbl_pedido.troca IS TRUE ";
		}
		if ($tipo_pedido_aux[1]=="peca"){
			$monta_sql .= " AND tbl_pedido.troca IS NOT TRUE ";
		}
		$tipo_pedido = $tipo_pedido_aux[0];
	}
	$monta_sql .= "$xsql tbl_pedido.tipo_pedido=" . $tipo_pedido;
	$dt = 1;

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";
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

//echo nl2br($sql);
$res = pg_exec($con,$sql);

//echo nl2br($sql);
$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD colspan='5'>$msg        </TD>\n";
echo "</TR>\n";
echo "</table>\n";

if (@pg_numrows($res) == 0)
	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";
else
	echo "<center><h3>Existem ".pg_numrows($res)." pedidos no sistema</h3></center>";


if (@pg_numrows($res) > 0) {
	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido              = trim(pg_result ($res,$i,pedido));
		$pedido_suframa      = trim(pg_result ($res,$i,pedido_suframa));
		$pedido_blackedecker = trim(pg_result ($res,$i,pedido_blackedecker));
		$seu_pedido          = trim(pg_result ($res,$i,seu_pedido));
		$unificar_pedido     = trim(pg_result ($res,$i,unificar_pedido));
		$descricao_tipo      = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$data                = trim(pg_result ($res,$i,data));
		$finalizado          = trim(pg_result ($res,$i,finalizado));
		$exportado           = trim(pg_result ($res,$i,exportado));
		$login               = trim(pg_result ($res,$i,login));
		$codigo_posto        = trim(pg_result ($res,$i,codigo_posto));
		$nome_posto          = trim(pg_result ($res,$i,nome_posto));
		$estado_posto        = trim(pg_result ($res,$i,estado_posto));
		$tipo_posto          = trim(pg_result ($res,$i,tipo_posto));
		$pedido_tipo_posto   = trim(pg_result ($res,$i,pedido_tipo_posto));
		$condicao_descricao  = trim(pg_result ($res,$i,condicao_descricao));
		$sigla_tabela        = trim(pg_result ($res,$i,sigla_tabela));
		$total               = trim(pg_result ($res,$i,total));
		$total_com_ipi       = trim(pg_result ($res,$i,total_com_ipi));
		$total_geral         = $total_geral + $total;
		$total_geral_com_ipi = $total_geral_com_ipi + $total_com_ipi;
		$total               = number_format($total,2,",",".");
		$total_com_ipi       = number_format($total_com_ipi,2,",",".");
		$status_pedido       = trim(pg_result ($res,$i,status_pedido));
		$status_descricao    = trim(pg_result ($res,$i,status_descricao));


		$pedido_blackedecker = "00000" . $pedido_blackedecker;
		$pedido_blackedecker = substr($pedido_blackedecker, strlen($pedido_blackedecker)-5, strlen($pedido_blackedecker));

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}

		if ($unificar_pedido == 't') $unificar_pedido = "S";
		else                         $unificar_pedido = "N";

		echo "<TR><TD colspan='14'>&nbsp;</TD></TR>\n";

		echo "<TR class='menu_top'>\n";
		echo "<TD>UP</TD>\n";
		echo "<TD>TIPO</TD>\n";
		echo "<TD>ADMIN</TD>\n";
		echo "<TD>PEDIDO</TD>\n";
		echo "<TD>ABERTURA</TD>\n";
		echo "<TD>FINALIZADO</TD>\n";
		echo "<TD>POSTO</TD>\n";
		echo "<TD>REGIÃO</TD>\n";
		echo "<TD>TIPO ATUAL</TD>\n";
		echo "<TD>TIPO ANTERIOR</TD>\n";
		echo "<TD>CONDIÇÃO</TD>\n";
		echo "<TD>TABELA</TD>\n";
		echo "<TD>TOTAL</TD>\n";
		echo "<TD>TOTAL+IPI</TD>\n";
		echo "<TD colspan='2'>AÇÕES</TD>\n";
		echo "<TD>OBS</TD>\n";
		echo "</TR>\n";

		echo "<TR class='table_line' bgcolor='#F1F4FA'>\n";
		echo "<TD align='center'>$unificar_pedido</TD>\n";
		echo "<TD align='center'>";
		if (strlen($pedido_suframa) > 0) {
			echo "IMP";
		}else{
			echo "&nbsp;";
		}
		echo "</TD>\n";
		echo "<TD>$login</TD>\n";
		echo "<TD><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido_blackedecker</font></a></TD>\n";
		echo "<TD align='center'>$data</TD>\n";
		echo "<TD align='center'>$finalizado</TD>\n";
		echo "<TD nowrap><ACRONYM TITLE='$codigo_posto - $nome_posto'>$codigo_posto - "
		.substr($nome_posto,0,20)."</ACRONYM></TD>\n";
		echo "<TD align='center'>$estado_posto</TD>\n";
		echo "<TD align='center'>$tipo_posto</TD>\n";
		echo "<TD align='center'>$pedido_tipo_posto</TD>\n";
		echo "<TD align='center'>$condicao_descricao</TD>\n";
		echo "<TD align='center'>$sigla_tabela</TD>\n";
		echo "<TD align='center'>$total</TD>\n";
		echo "<TD align='center'>$total_com_ipi</TD>\n";
		echo "<TD align='center' nowrap colspan='2'>&nbsp;";
		if ($login_admin == 232 OR $login_admin == 245 OR $login_admin == 112) {  // duas usuarias da Black&Decker
			if (strlen($exportado) == 0) {
			echo "<a href='pedido_cadastro_blackedecker.php?pedido=$pedido' target='_blank'><img src='imagens/btn_alterarcinza.gif'></a>";
			}
			if (strlen($exportado) == 0) {
				echo "&nbsp;<a href='$PHP_SELF?pedido=$pedido&finalizar=1&unificar=t'><img src='imagens/btn_finalizar.gif' border='0' style='cursor: hand;'></a>";
			}
		}else{
			if (strlen($exportado) == 0) {
			echo "<img src='imagens/btn_alterarcinza.gif' alt='Opção de uso somente da Rúbia e Lilian'>";
			}
			if (strlen($exportado) == 0) {
				echo "&nbsp;<img alt='Opção de uso somente da Rúbia e Lilian' src='imagens/btn_finalizar.gif' border='0' >";
			}
		}
		echo "&nbsp;</TD>\n";
		echo "<td><a href=\"javascript:AbrirJanelaObs('$pedido')\">Inserir Obs</a></td>";
		echo "</TR>\n";

		if (strlen($exportado) > 0) {
			echo "<TR class='table_line'>\n";
			echo "<TD align='left' colspan='100%'>Enviado para fábrica em $exportado";  
			if($status_pedido == 14) { echo "<br><b>Pedido: $status_descricao</b>";}
			echo "<br></TD>\n";
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
	echo "<TR class='table_line'>\n";
	echo "<TD align='center'><b>TOTAL GERAL COM IPI</b></TD>\n";
	echo "<TD align='right'><b>". number_format($total_geral_com_ipi,2,",",".") ."</b></TD>\n";
	echo "</TR>\n";
	echo "</TABLE>\n";
	echo "<br>\n";
}

echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
echo "<TR class='table_line'>";
echo "<td align='center' background='#D9E2EF'>";
echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
echo "</td>";
echo "</TR>";
echo "</TABLE>";
?>

<br>

<? include "rodape.php"; ?>
