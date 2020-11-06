<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim(strtolower($_POST["btnacao"]));
}

if (strlen($_POST["posto"]) > 0) {
	$posto = $_POST["posto"];
}

if (strlen($_GET["posto"]) > 0) {
	$posto = $_GET["posto"];
}


$layout_menu = "gerencia";
$title = "Consulta e Manutenção de Extratos";

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
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<script type="text/javascript" src="alphaAPI.js"></script>

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

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<?

$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final'];
$posto_nome   = $_POST['posto_nome'];
$posto_codigo = $_POST['posto_codigo'];

echo "<TABLE width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
echo "<FORM METHOD='POST' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Consultar postos com extratos fechados entre";
echo "	</TD>";
echo "<TR>\n";

echo "<TR>\n";
echo "	<TD ALIGN='center'>";
echo "	Data Inicial ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' value='$data_inicial'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaInicial_Extrato')\" style='cursor:pointer' alt='Clique aqui para abrir o calendário'>\n";
echo "	</TD>\n";

echo "	<TD ALIGN='center'>";
echo "	Data Final ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' value='$data_final'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaFinal_Extrato')\" style='cursor:pointer' alt='Clique aqui para abrir o calendário'>\n";
echo "</TD>\n";
echo "</TR>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Somente extratos do posto";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "CNPJ";
echo "		<input type='text' name='posto_codigo' size='18' value='$posto_codigo'>&nbsp;&nbsp;</A>";
echo "<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'cnpj')\">";

echo "Razão Social ";
echo "		<input type='text' name='posto_nome' size='45' value='$posto_nome' >&nbsp;<img src='imagens/btn_buscar5.gif' style='cursor:pointer border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor:pointer;'></A>";
echo "	</TD>";
echo "<TR>\n";

echo "</TABLE>\n";

echo "<br><img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";


// INICIO DA SQL
$data_inicial = $_POST['data_inicial'];
$data_final   = $_POST['data_final'];
$posto_codigo = $_POST['posto_codigo'];

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
	$sql = "SELECT  tbl_posto.nome                ,
					tbl_posto.cnpj                ,
					tbl_posto_fabrica.codigo_posto,
					tbl_extrato.extrato           ,
					to_char (tbl_extrato.data_geracao,'dd/mm/yyyy') as data_geracao,
					to_char (tbl_extrato.total,'999,999.99') as total ,
					(SELECT count (tbl_os.os) FROM tbl_os JOIN tbl_os_extra USING (os) WHERE tbl_os_extra.extrato = tbl_extrato.extrato) AS qtde_os,
					to_char (tbl_extrato_extra.baixado,'dd/mm/yyyy') as baixado
			FROM    tbl_extrato 
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_extrato_extra USING (extrato)
			WHERE   tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.extrato = tbl_extrato_extra.extrato";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	
	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0) 
	$sql .= " AND      tbl_extrato.data_geracao 
			  BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

//	if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";

	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);
	if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xposto_codigo' ";

	if (strlen ($posto_nome) > 0 ) $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";

	$sql .= " ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	$res = pg_exec ($con,$sql);


	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}

	if (pg_numrows ($res) > 0) {
		echo "<table width='700' align='center' border='0' cellspacing='2'>";
		echo "<tr class = 'menu_top'>";
		echo "<td align='center'>Código</td>";
		echo "<td align='center'>Nome do Posto</td>";
		echo "<td align='center'>Extrato</td>";
		echo "<td align='center'>Data</td>";
		echo "<td align='center'>Qtde. OS</td>";
		echo "<td align='center'>Total</td>";
		echo "<td align='center'>Baixado em</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));
			$nome_sub       = substr($nome,0,20);
			$extrato        = trim(pg_result($res,$i,extrato));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			$qtde_os        = trim(pg_result($res,$i,qtde_os));
			$total          = trim(pg_result($res,$i,total));
			$baixado        = trim(pg_result($res,$i,baixado));

			$total	= number_format ($total, 2, ',', ' ');

			echo "<tr>";

			echo "<td class='table_line' align='left'>$codigo_posto</td>";
			echo "<td class='table_line' align='left'><ACRONYM TITLE=\"$nome\">$nome_sub</ACRONYM></td>";
			echo "<td class='table_line' align='center'><a href = 'extrato_consulta_os.php?extrato=$extrato'>$extrato</a></td>";
			echo "<td class='table_line' align='center'>$data_geracao</td>";
			echo "<td class='table_line' align='center'>$qtde_os</td>";
			echo "<td class='table_line' align='rigth'>R$ $total</td>";
			echo "<td class='table_line' align='rigth'>$baixado</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>
<p>
<p>
<? include "rodape.php"; ?>