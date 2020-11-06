<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

if($login_fabrica == 1) {
	include("pedido_consulta_blackedecker.php");
	exit;
}


if ($login_fabrica == 42) {
	if ($_GET["reintegrar"]) {
		$pedido = $_GET['pedido'];
		$sql = "SELECT fn_reintegrar($pedido)";
		$res = pg_exec($con,$sql);
		if(strlen(pg_errormessage($con))>0){
			$msg_erro = "Falha na reintegração do pedido!";
		} else {
			header('Location:pedido_parametros.php');
		}
	}
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

if($_GET["status_pedido"])			$status_pedido      = trim($_GET["status_pedido"]); //HD 49364

//echo $tipo;
$layout_menu = "callcenter";
$title = "Relação de Pedidos Lançados";
#$body_onload = "javascript: document.frm_os.condicao.focus()";

include "cabecalho.php";
include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 
?>

<p>

<script language="JavaScript">

function selecionarTudo(){
	$('input[@rel=imprimir]').each( function (){
		this.checked = !this.checked;
	});
}

function imprimirSelecionados(){
	var qtde_selecionados = 0;
	var linhas_seleciondas = "";
	$('input[@rel=imprimir]:checked').each( function (){
		if (this.checked){
			linhas_seleciondas = this.value+", "+linhas_seleciondas;
			qtde_selecionados++;
		}
	});

	if (qtde_selecionados>0){
		janela = window.open('pedido_print_selecao.php?lista_pedido='+linhas_seleciondas,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=850,height=600,top=18,left=0");
	}
}

</script>

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

	if ($login_fabrica==10 OR $login_fabrica == 7){
		$sql_left = " LEFT ";
	}
	// HD 27232
	$sql = "SELECT DISTINCT tbl_pedido.pedido                                         ,
					tbl_pedido.pedido_cliente                                         ,
					tbl_posto.nome                    AS posto_nome                   ,
					tbl_posto_fabrica.codigo_posto                                    ,
					tbl_pedido.fabrica                                                ,
					tbl_pedido.pedido_cliente                                         ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data                     ,
					to_char(tbl_pedido.recebido_posto,'DD/MM/YYYY') AS recebido_posto ,
					tbl_tipo_pedido.descricao AS descricao_tipo_pedido                ,
					tbl_status_pedido.descricao AS descricao_status_pedido            ,
					tbl_pedido.exportado                                              ,
					tbl_pedido.status_fabricante                                      ,
					tbl_pedido.origem_cliente                                         ,
					tbl_pedido.pedido_os                                              ,
					tbl_pedido.total                                                  ,
					tbl_admin.login
			FROM	tbl_posto
			JOIN      tbl_pedido        ON  tbl_pedido.posto                = tbl_posto.posto
			JOIN      tbl_tipo_pedido   ON  tbl_tipo_pedido.tipo_pedido     = tbl_pedido.tipo_pedido
			JOIN      tbl_pedido_item   ON  tbl_pedido_item.pedido          = tbl_pedido.pedido
			JOIN      tbl_peca          ON  tbl_peca.peca                   = tbl_pedido_item.peca
			$sql_left JOIN tbl_posto_fabrica ON  tbl_posto_fabrica.posto         = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica       = tbl_pedido.fabrica
			LEFT JOIN tbl_produto		 on tbl_produto.produto       = tbl_pedido.produto
			LEFT JOIN tbl_status_pedido ON  tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			LEFT JOIN tbl_admin         ON  tbl_admin.admin = tbl_pedido.admin
			WHERE	tbl_pedido.fabrica = $login_fabrica ";
if (strlen($status_pedido) > 0 AND (in_array($login_fabrica,array(51,45,24,85)))) {
	$sql .= "AND tbl_pedido.status_pedido = $status_pedido ";
}

$sql .= " AND (1=2 ";

if (strlen($chk1) > 0) {
	// data do dia
	$sqlX = "SELECT to_char (current_date , 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$sqlX = "SELECT to_char (current_timestamp + INTERVAL '1 day' - INTERVAL '1 seconds', 'YYYY-MM-DD HH:MI:SS')";
	$resX = pg_exec ($con,$sqlX);
	#  $dia_hoje_final = pg_result ($resX,0,0);

	$monta_sql .=" OR (tbl_pedido.data BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
//	if ($ip == '201.42.44.145') echo $monta_sql;
	$dt = 1;

	$msg .= " &middot; Pedidos lançados hoje";

}

if (strlen($chk2) > 0) {
	// dia anterior
	$sqlX = "SELECT to_char (current_date - INTERVAL '1 day', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.data BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= " e Pedidos lançados ontem";

}

if (strlen($chk3) > 0) {
	// nesta semana
	$sqlX = "SELECT to_char (current_date , 'D')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_hoje = pg_result ($resX,0,0) - 1 ;

	$sqlX = "SELECT to_char (current_date - INTERVAL '$dia_semana_hoje days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$sqlX = "SELECT to_char ('$dia_semana_inicial'::date + INTERVAL '6 days', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.data BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
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
	if ($login_fabrica == 1) {
		$monta_sql .=" OR (tbl_pedido.exportado::date BETWEEN fnc_formata_data('$data_inicial') AND fnc_formata_data('$data_final')) ";
	}else{
		$monta_sql .=" OR (tbl_pedido.data::date BETWEEN fnc_formata_data('$data_inicial') AND fnc_formata_data('$data_final')) ";
	}
	$dt = 1;

	$msg .= "Pedidos lançados entre os dias $data_inicial e $data_final ";

}

if (strlen($chk7) > 0)
{
	// posto
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_posto_fabrica.codigo_posto ='$codigo_posto' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo posto $nome_posto";

}

# Peça
if (strlen($chk8) > 0 and ($login_fabrica == 3 or $login_fabrica ==1 or $login_fabrica ==80)) {
	$peca_referencia = str_replace("-","",$peca_referencia);

	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_peca.referencia_pesquisa = '".$peca_referencia."' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pela peça $peca_descricao";
}else if (strlen($chk8) > 0){
	// aparelho
	if ($dt == 1) $xsql = " AND ";
	else          $xsql = " OR ";
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

if (strlen($tipo_pedido) > 0 and $login_fabrica <> 3)
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
							WHERE  ";
							if($login_fabrica ==45 or $login_fabrica ==24){
								$monta_sql .="tbl_status_pedido.descricao = 'Aguardando Confirmacao' ";
							}else{
								$monta_sql .="tbl_status_pedido.descricao = 'Aguardando Faturamento' ";
							}
			$monta_sql .= ")";
			$msg .= " e Pedidos não atendidos";
			break;
	}

}

if (strlen($tipo_pedido) > 0 and $login_fabrica == 3){
	// Tipo do Pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_pedido.tipo_pedido=" . $tipo_pedido;
	$dt = 1;

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";
}

if (strlen($tipo) > 0)
{
	// tipo de pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	
	$monta_sql .= "$xsql tbl_pedido.tipo_pedido = $tipo";
}
if($login_fabrica == 24){ // HD 18161
	$sql_status_pedido=" AND tbl_pedido.status_pedido <> 14 ";
}

// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") $sql_status_pedido ORDER BY tbl_pedido.pedido DESC";
#if ($ip == '201.76.85.4') echo $sql;
#if ($ip == "201.0.9.216") echo $sql;

#echo nl2br($sql);
$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

//echo "<br>".$sql."<br>".$sqlCount."<br>";

// ##### PAGINACAO ##### //
//if($ip=='201.76.78.194') echo nl2br($sql);
require "_class_paginacao.php";

// definicoes de variaveis
$max_links = 10;				// máximo de links à serem exibidos
$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

//if ($ip == "201.42.109.216") echo $sql;

$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

// ##### PAGINACAO ##### //

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD colspan='5'>$msg        </TD>\n";
echo "</TR>\n";
echo "</table>\n";

if (@pg_numrows($res) == 0) {
	echo "<center><h2>Não existem pedidos com estes parâmetros</h2></center>";
}

if (@pg_numrows($res) > 0) {
	
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='pedido_consulta_xls.php?chk_opt1=$chk1&chk_opt2=$chk2&chk_opt3=$chk3&chk_opt4=$chk4&chk_opt5=$chk5&chk_opt6=$chk6&chk_opt7=$chk7&chk_opt8=$chk8&chk_opt9=$chk9&chk_opt10=$chk10&tipo_pedido=$tipo_pedido&tipo=$tipo&data_inicial_01=$data_inicial_01&data_final_01=$data_final_01&codigo_posto=$codigo_posto&nome_posto=$nome_posto&estado_posto=$estado_posto&produto_referencia=$produto_referencia&produto_nome=$produto_nome&numero_pedido=$numero_pedido' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";




	echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

	echo "<FORM NAME='frm_tipo_pedido' METHOD='get' ACTION='".$PHP_SELF."'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt1'           value='$chk1'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt2'           value='$chk2'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt3'           value='$chk3'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt4'           value='$chk4'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt5'           value='$chk5'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt10'          value='$chk10'>\n";
	echo "<INPUT TYPE='hidden' name='tipo_pedido'        value='$tipo_pedido'>\n";
	echo "<INPUT TYPE='hidden' name='tipo'               value='$tipo'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt6'           value='$chk6'>\n";
	echo "<INPUT TYPE='hidden' name='data_inicial_01'    value='$data_inicial_01'>\n";
	echo "<INPUT TYPE='hidden' name='data_final_01'      value='$data_final_01'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt7'           value='$chk7'>\n";
	echo "<INPUT TYPE='hidden' name='codigo_posto'       value='$codigo_posto'>\n";
	echo "<INPUT TYPE='hidden' name='nome_posto'         value='$nome_posto'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt8'           value='$chk8'>\n";
	echo "<INPUT TYPE='hidden' name='produto_referencia' value='$produto_referencia'>\n";
	echo "<INPUT TYPE='hidden' name='produto_nome'       value='$produto_nome'>\n";
	echo "<INPUT TYPE='hidden' name='chk_opt9'           value='$chk9'>\n";
	echo "<INPUT TYPE='hidden' name='numero_pedido'      value='$numero_pedido'>\n";
	echo "<TR class='menu_top'>\n";
	echo "	<TD colspan='";
		if ($login_fabrica == 7) {echo "10";}else{echo "9";}
echo "'>Por fechamento de pedido: \n";
	echo "		<select name='tipo_pedido' onChange='javascript:submit();'>\n";
	echo "			<option value='1'";
	if ($tipo_pedido == 1) echo " selected";
	echo ">Todos os pedidos</option>\n";
	echo "			<option value='2'";
	if ($tipo_pedido == 2) echo " selected";
	echo ">Atendido parcial</option>\n";
	echo "			<option value='3'";
	if ($tipo_pedido == 3) echo " selected";
	echo ">Atendido integral</option>\n";
	echo "			<option value='4'";
	if ($tipo_pedido == 4) echo " selected";
	echo ">Não atendido</option>\n";
	echo "		</select>\n";
	echo "	</TD>\n";
	$sql = "SELECT  tbl_tipo_pedido.tipo_pedido,
					tbl_tipo_pedido.descricao
			FROM    tbl_tipo_pedido
			WHERE   tbl_tipo_pedido.fabrica = $login_fabrica
			ORDER BY tbl_tipo_pedido.descricao;";
	$res1 = @pg_exec ($con,$sql);

	if (pg_numrows($res1) > 0) {
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan='";
		if ($login_fabrica == 7) {echo "10";}else{echo "9";}
echo "'>Por tipo de pedido: \n";
		echo "<select name='tipo' onChange='javascript:submit();'>\n";
		echo "<option value=''></option>";
		
		for ($i = 0 ; $i < pg_numrows ($res1) ; $i++){
			$aux_tipo      = trim(pg_result($res1,$i,tipo_pedido));
			$aux_descricao = trim(pg_result($res1,$i,descricao));
			
			echo "<option value='$aux_tipo'";
			if ($aux_tipo == $tipo) echo " selected";
			echo ">$aux_descricao</option>\n";
		}
		echo "</select>";
		echo "</td>";
	}
	
	echo "</TR>\n";
	echo "</FORM>";

	echo "<TR class='menu_top'>\n";
	echo "	<TD >PEDIDO</TD>\n";
	echo "	<TD >PEDIDO CLIENTE</TD>\n";
	echo "	<TD >TIPO</TD>\n";
	if($login_fabrica == 7){
		echo "	<TD>ORIGEM (OS/COMPRA)</TD>\n";
		echo "	<TD>SOLICITANTE (PTA/CLIENTE)</TD>\n";
		echo "	<TD>ADMIN</TD>\n";
	}
	echo "	<TD >STATUS</TD>\n";
	if($login_fabrica == 45) { echo "<TD >STATUS FABRICANTE </TD>\n"; }
	echo "	<TD >DATA</TD>\n";
	if ($login_fabrica == 24) {echo "	<TD >RECEBIDO</TD>\n";}
	echo "	<TD>POSTO</TD>\n";
	if ($login_fabrica == 14) {echo "	<TD >VALOR</TD>\n";}

	echo "	<TD colspan='2'>AÇÕES</TD>\n";
	if($login_fabrica == 50) {
	echo "	<TD colspan='2'><a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'> </a></TD>\n";
	}



	echo "</TR>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido             = trim(pg_result ($res,$i,pedido));
		$pedido_cliente     = trim(pg_result ($res,$i,pedido_cliente));
		$descricao_tipo     = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$exportado          = trim(pg_result ($res,$i,exportado));
		if ($login_fabrica == 2 and strlen($exportado) > 0)
			$status             = "OK";
		else
			$status             = trim(pg_result ($res,$i,descricao_status_pedido));
		$data               = trim(pg_result ($res,$i,data));
		$codigo_posto       = trim(pg_result ($res,$i,codigo_posto));
		$posto_nome         = trim(pg_result ($res,$i,posto_nome));
		$data_recebido      = trim(pg_result ($res,$i,recebido_posto));
		$status_fabricante  = trim(pg_result ($res,$i,status_fabricante));

		$origem_cliente     = trim(pg_result ($res,$i,origem_cliente));
		$pedido_os          = trim(pg_result ($res,$i,pedido_os));
		$total              = trim(pg_result ($res,$i,total));
		$login              = trim(pg_result ($res,$i,login));

		
		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
			$btn = 'azul';
		}
	if(strlen($pedido) > 0 AND $login_fabrica == 24){ // HD 18327
		$sql2="SELECT  sum(qtde) AS qtde,
					  sum(qtde_cancelada) AS qtde_cancelada
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING(pedido)
				WHERE tbl_pedido.pedido=$pedido
				AND   tbl_pedido.status_pedido <> 14";
		$res2=pg_exec($con,$sql2);
		if(pg_numrows($res2) > 0){
			$qtde           = pg_result($res2,0,qtde);
			$qtde_cancelada = pg_result($res2,0,qtde_cancelada);
			if($qtde == $qtde_cancelada){
				$sql3="UPDATE tbl_pedido SET status_pedido=14
						WHERE pedido = $pedido";
				$res3=pg_exec($con,$sql3);
				echo "<script>";
				echo "window.location.reload()";
				echo "</script>";
			}
		}
	}
	echo "<TR class='table_line' style='background-color: $cor;'>\n";
	echo "	<TD style='padding-left:5px'><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido</font></a></TD>\n";
	echo "	<TD style='padding-left:5px'>$pedido_cliente</TD>\n";
	echo "	<TD style='padding-left:5px'>$descricao_tipo</TD>\n";
	if($login_fabrica == 7) {
			if($pedido_os =='t'){
				$pedido_os_descricao = " Ordem Serviço";
			}else{
				$pedido_os_descricao = " Compra Manual";
			}
			echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap>". $pedido_os_descricao ."</td>";
			if($origem_cliente == 't'){
				$origem_descricao = "Cliente";
			}else{
				$origem_descricao = "PTA";
			}
			echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap>".$origem_descricao ."</td>";
			echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap>".$login."</td>";
	}
	echo "	<TD style='padding-left:5px' nowrap>$status</TD>\n";
	if($login_fabrica == 45) {
		echo "	<TD style='padding-left:5px' nowrap>$status_fabricante</TD>\n";
	}
	echo "	<TD align='center'>$data</TD>\n";
	if ($login_fabrica == 24) {echo "	<TD align='center'>$data_recebido</TD>\n";}
	echo "	<TD nowrap >$codigo_posto - <ACRONYM TITLE=\"$posto_nome\">".substr($posto_nome,0,14)."</ACRONYM></TD>\n";
	if ($login_fabrica == 14) {
		echo "<TD align='right'><b>". number_format($total,2,",",".") ."</b></TD>\n";
	}
	if ($login_fabrica == 14) {
		if ($login_admin == 265) {
			echo "	<TD nowrap width='85'>&nbsp;</TD>\n";
		}else{
			echo "	<TD nowrap width='85'><a href='pedido_cadastro.php?pedido=$pedido'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a></TD>\n";
		}
	}
	elseif ($login_fabrica == 80) {
		$sql = "
		SELECT
		tbl_pedido.pedido

		FROM
		tbl_pedido
		LEFT JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido

		WHERE
		tbl_pedido.fabrica = $login_fabrica
		AND tbl_pedido.exportado IS NOT NULL
		AND tbl_status_pedido.descricao <> 'Faturado Integral'
		AND tbl_status_pedido.status_pedido <> 14
		AND tbl_pedido.pedido=$pedido
		";
		$res_faturado = pg_query($con, $sql);
		if (pg_num_rows($res_faturado)) {
			echo "<TD nowrap width='85'><a href='pedido_cadastro.php?pedido=$pedido'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a>&nbsp;<a href='pedido_nao_faturado_cadastro.php?pedido=$pedido'><img src='imagens/btn_faturar_".$btn.".gif'></a></TD>\n";
		}
		else {
			echo "	<TD nowrap width='85'><a href='pedido_cadastro.php?pedido=$pedido'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a></TD>\n";
		}
	}
	else{
		echo "	<TD nowrap width='85'><a href='pedido_cadastro.php?pedido=$pedido'><img src='imagens_admin/btn_alterar_".$btn.".gif'></a></TD>\n";
		if ($login_fabrica == 42) {
			echo "	<TD nowrap width='85'><a href='#' onclick='javascript: if (confirm(\"Tem certeza que deseja reintegrar o pedido, o mesmo ficará com status de aguardando exportação?\")==true) { window.location = \"pedido_consulta.php?reintegrar=sim&pedido=$pedido\"}'>Re-integrar</a></TD>\n";
		}

	}
	if($login_fabrica == 50) {
	echo "	<TD nowrap width='85'><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'><img src='imagens_admin/btn_imprimir_".$btn.".gif'></a></TD>\n";
	echo "	<TD nowrap width='85'><input name='imprimir_$i' type='checkbox' id='imprimir' rel='imprimir' value='".$pedido."' /></TD>\n";
	echo "</TR>\n";
	}



	}
}
if($login_fabrica == 50) {
echo "<tr>";
		echo "<td colspan='7'>";
		echo "";
		echo "</td>";
		echo "<td colspan='2'>";
		echo "<a href='javascript:imprimirSelecionados()' style='font-size:10px'>Imprime Selecionados</a>";
		echo "</td>";
echo "</tr>";
}
echo "</TABLE>\n";

	echo "<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='0'>";
	echo "<TR class='table_line'>";
	echo "<td align='center' background='#D9E2EF'>";
	echo "<a href='pedido_parametros.php'><img src='imagens_admin/btn_nova_busca.gif'></a>";
	echo "</td>";
	echo "</TR>";
	echo "</TABLE>";


// ##### PAGINACAO ##### //

// links da paginacao
echo "<br>";

echo "<div>";

if($pagina < $max_links) { 
	$paginacao = pagina + 1;
}else{
	$paginacao = pagina;
}

// paginacao com restricao de links da paginacao

// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
$todos_links		= $mult_pag->Construir_Links("strings", "sim");

// função que limita a quantidade de links no rodape
$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

for ($n = 0; $n < count($links_limitados); $n++) {
	echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
}

echo "</div>";

$resultado_inicial = ($pagina * $max_res) + 1;
$resultado_final   = $max_res + ( $pagina * $max_res);
$registros         = $mult_pag->Retorna_Resultado();

$valor_pagina   = $pagina + 1;
$numero_paginas = intval(($registros / $max_res) + 1);

if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

if ($registros > 0){
	echo "<br>";
	echo "<div>";
	echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
	echo "<font color='#cccccc' size='1'>";
	echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
	echo "</font>";
	echo "</div>";
}

// ##### PAGINACAO ##### //

?>

<p>

<? include "rodape.php"; ?>
