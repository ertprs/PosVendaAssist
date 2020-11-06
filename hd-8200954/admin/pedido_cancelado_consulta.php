<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";


$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

$layout_menu = "callcenter";
$title = "Consulta de Pedidos cancelados pelo fabricante";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF
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
.quadro{
	border: 1px solid #596D9B;
	width:450px;
	height:50px;
	padding:10px;
	
}

.botao {
		border-top: 1px solid #333;
	        border-left: 1px solid #333;
	        border-bottom: 1px solid #333;
	        border-right: 1px solid #333;
	        font-size: 13px;
	        margin-bottom: 10px;
	        color: #0E0659;
		font-weight: bolder;
}
</style>
<script language='javascript' src='../ajax.js'></script>

<script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/admin/js/jquery.mask.js"></script>

<script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link type="text/css" href="https://posvenda.telecontrol.com.br/assist/plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />

<script language="JavaScript">

/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}
</script>

<!-- <script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script> -->

<script>
$(document).ready(function(){

	$('input[name="data_inicial"]').mask("99/99/9999").datepick({startDate:'01/01/2000'});
	$('input[name="data_final"]').mask("99/99/9999").datepick({startDate:'01/01/2000'});

});
</script>

<?
if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";

	echo "</tr>";
	echo "</table>\n";
}

	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' >\n";
	echo "<tr>";
	echo "<td align='center'> <font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='../download/relatorio-pedido-cancelado-consulta-lenoxx.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download</a></font><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'> do arquivo xls com os cancelamentos referentes aos últimos 15 dias.</font></td>";
	echo "</tr>";
	echo "</table>\n";
$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

echo "<TABLE width='600' align='center' border='0' cellspacing='3' cellpadding='2'>\n";
echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Consultar peças canceladas em pedidos entre";
echo "	</TD>";
echo "<TR>\n";

echo "<TR>\n";
echo "	<TD ALIGN='center'>";
echo "	Data Inicial ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' value='$data_inicial' class='frm'> \n";
echo "	</TD>\n";

echo "	<TD ALIGN='center'>";
echo "	Data Final ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' value='$data_final' class='frm'> \n";
echo "</TD>\n";
echo "</TR>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Somente pedidos do posto";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD COLSPAN='2' ALIGN='center' nowrap>";
echo "CNPJ";
echo "		<input type='text' name='posto_codigo' size='18' value='$posto_codigo' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'cnpj')\">";

echo "&nbsp;&nbsp;Razão Social ";
echo "		<input type='text' name='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";

echo "</TABLE>\n";

echo "<br><img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar pedidos cancelados\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";


// INICIO DA SQL
$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

$data_inicial = str_replace (" " , "" , $data_inicial);
$data_inicial = str_replace ("-" , "" , $data_inicial);
$data_inicial = str_replace ("/" , "" , $data_inicial);
$data_inicial = str_replace ("." , "" , $data_inicial);


$data_final = str_replace (" " , "" , $data_final);
$data_final = str_replace ("-" , "" , $data_final);
$data_final = str_replace ("/" , "" , $data_final);
$data_final = str_replace ("." , "" , $data_final);


if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);


if (strlen ($posto_codigo) > 0 OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) ) {
	$sql = "SELECT  tbl_posto_fabrica.codigo_posto||' - '||tbl_posto.nome as posto,
					tbl_pedido_cancelado.pedido                                   ,
					tbl_pedido.pedido_blackedecker                                ,
					tbl_pedido_cancelado.qtde                                     ,
					tbl_pedido_cancelado.os                                       ,
					tbl_os.sua_os                                                 ,
					TO_CHAR(tbl_pedido_cancelado.data,'DD/MM/YYYY') as datax      ,
					tbl_pedido_cancelado.data                                     ,
					tbl_peca.referencia||' - '||tbl_peca.descricao as peca
			FROM tbl_pedido_cancelado
			JOIN tbl_posto on tbl_pedido_cancelado.posto             = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_pedido_cancelado.posto     = tbl_posto_fabrica.posto
									AND tbl_pedido_cancelado.fabrica = tbl_posto_fabrica.fabrica
			JOIN tbl_peca on tbl_pedido_cancelado.peca               = tbl_peca.peca
			LEFT JOIN tbl_os ON tbl_pedido_cancelado.os              = tbl_os.os
			JOIN tbl_pedido ON tbl_pedido_cancelado.pedido           = tbl_pedido.pedido
			WHERE tbl_pedido_cancelado.fabrica                       = $login_fabrica";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND tbl_pedido_cancelado.data BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);

	if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";
	if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";

	$sql.= " ORDER BY tbl_pedido_cancelado.oid DESC";
	$res = pg_exec ($con,$sql);


	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";
	
	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página
	
	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
	
	// ##### PAGINACAO ##### //
	if (@pg_numrows($res) > 0) {
		echo "<BR><BR>";
		echo "<table width='800' border='0' cellspacing='2' cellpadding='0' align='center'>";
		echo "<tr height='20' bgcolor='#596D9B'>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Posto</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>OS</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Pedido</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Ped. Fab.</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Peça</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Qtde</b></font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color=#FFFFFF><b>Dt. Cancelada</b></font></td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#D9E2EF";
			if ($i % 2 == 0) $cor = '#FFFFFF';

			$posto             = substr(pg_result($res,$i,posto),0,30);
			$pedido            = trim(pg_result($res,$i,pedido));
			$pedido_fabricante = trim(pg_result($res,$i,pedido_blackedecker));
			$qtde              = trim(pg_result($res,$i,qtde));
			$os                = trim(pg_result($res,$i,os));
			$sua_os            = trim(pg_result($res,$i,sua_os));
			$data              = trim(pg_result($res,$i,datax));
			$peca              = substr(trim(pg_result($res,$i,peca)),0,40);
			
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$posto</font></td>";

			if (strlen($sua_os) > 0) {
				echo "<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></font></td>";
			} else {
				echo "<td align='right'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>&nbsp;</font></td>";
			}
			
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>$pedido</a></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$pedido_fabricante</font></td>";
			echo "<td align='left' nowrap><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$peca</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$qtde</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
			echo "</tr>";
		}
		echo "</table>";
		
		echo "</td>";
		echo "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";

		
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
	}else{
		echo "<p>";
		
		echo "<table width='800' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td valign='top' align='center'>";
		echo "<h4>Não foi(am) encontrado(s) pedido(s).</h4>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
	}
}
?>

<br>

<? include "rodape.php"; ?>
