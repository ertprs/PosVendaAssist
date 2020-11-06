<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];

if (strlen($_GET["liberar"]) > 0) $liberar = $_GET["liberar"];

if (strlen($liberar) > 0){
	$sql = "UPDATE tbl_extrato SET liberado = current_date WHERE extrato = $liberar";
	$res = @pg_exec($con,$sql);
	$msg_erro = @pg_errormessage($con);
}

if (strlen($_GET["aprovar"]) > 0) $aprovar = $_GET["aprovar"];

if (strlen($aprovar) > 0){
	$sql = "UPDATE tbl_extrato SET liberado = current_date WHERE extrato = $aprovar";
	$res = @pg_exec($con,$sql);
	$msg_erro = @pg_errormessage($con);
}

if ($btnacao == 'liberar_tudo'){

	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];

	for ($i=0; $i < $total_postos; $i++) {
		$extrato = $_POST["liberar_".$i];
		if (strlen($extrato) > 0) {
			$sql = "UPDATE tbl_extrato SET liberado = current_date WHERE extrato = $extrato";
			$res = @pg_exec($con,$sql);
			$msg_erro = @pg_errormessage($con);
		}
	}
}

if ($btnacao == 'aprovar_tudo'){

	if (strlen($_POST["total_postos"]) > 0) $total_postos = $_POST["total_postos"];

	for ($i=0; $i < $total_postos; $i++) {
		$extrato = $_POST["aprovar_".$i];
		if (strlen($extrato) > 0) {
			$sql = "UPDATE tbl_extrato SET aprovado = current_date WHERE extrato = $extrato";
			$res = @pg_exec($con,$sql);
			$msg_erro = @pg_errormessage($con);
		}
	}
}

$layout_menu = "financeiro";
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

var checkflag = "false";
function check(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}
</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<?

$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];
$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

echo "<TABLE width='600' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
echo "<FORM METHOD='POST' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";
/*
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
*/
echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Somente extratos do posto";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "Código";
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
/*
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

*/

if (strlen ($posto_codigo) > 0 OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) ) {
	$sql = "SELECT  tbl_posto.posto               ,
					tbl_posto.nome                ,
					tbl_posto.cnpj                ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.distribuidor,
					tbl_extrato.extrato           ,
					to_char (tbl_extrato.data_geracao,'dd/mm/yyyy') as data_geracao,
					tbl_extrato.total,
					(
						SELECT	count (tbl_os.os) 
						FROM	tbl_os JOIN tbl_os_extra USING (os) 
						WHERE tbl_os_extra.extrato = tbl_extrato.extrato
					) AS qtde_os,
					to_char (tbl_extrato_pagamento.data_pagamento,'dd/mm/yyyy') as baixado
			FROM    tbl_extrato
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			left JOIN    tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
			WHERE   tbl_extrato.fabrica = $login_fabrica
			AND     tbl_extrato.aprovado NOTNULL
			AND     tbl_posto_fabrica.distribuidor IS NULL ";
	
	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	
	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	
	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0) 
	$sql .= " AND      tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
	
	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);
	
	if (strlen ($posto_codigo) > 0 ) $sql .= " AND (tbl_posto.cnpj = '$xposto_codigo' OR tbl_posto_fabrica.codigo_posto = '$xposto_codigo') ";
	if (strlen ($posto_nome) > 0 ) $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";
	
	$sql .= " GROUP BY tbl_posto.posto ,
					tbl_posto.nome ,
					tbl_posto.cnpj ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto_fabrica.distribuidor,
					tbl_extrato.extrato ,
					tbl_extrato.liberado ,
					tbl_extrato.total,
					tbl_extrato.data_geracao,
					tbl_extrato_pagamento.data_pagamento
				ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}
	
	if (pg_numrows ($res) > 0) {
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto   = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));
			$extrato        = trim(pg_result($res,$i,extrato));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			$qtde_os        = trim(pg_result($res,$i,qtde_os));
			$total          = trim(pg_result($res,$i,total));
			$baixado        = trim(pg_result($res,$i,baixado));
			$extrato        = trim(pg_result($res,$i,extrato));
			$distribuidor   = trim(pg_result($res,$i,distribuidor));
			$total	        = number_format ($total,2,',','.');
			$liberado       = trim(pg_result($res,$i,liberado));

			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='2'>\n";
				echo "<tr class = 'menu_top'>\n";
				echo "<td align='center'>Código</td>\n";
				echo "<td align='center' nowrap>Nome do Posto</td>\n";
				echo "<td align='center'>Extrato</td>\n";
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>Qtde. OS</td>\n";
				echo "<td align='center'>Total</td>\n";
				//echo "<td align='center'>Baixado em</td>\n";
				if ($login_fabrica == 10) {
					echo "<td align='center'>Financeiro<input type='checkbox' class='frm' name='aprovar_todos' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.aprovar);'></td>\n";
				}
				echo "</tr>\n";
			}
			
			echo "<tr>\n";
			
			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>$nome</td>\n";
			echo "<td align='center'><a href = 'extrato_consulta_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'>$extrato</a></td>\n";
			echo "<td align='left'>$data_geracao</td>\n";
			echo "<td align='center'>$qtde_os</td>\n";
			echo "<td align='right' nowrap>R$ $total</td>\n";
			if ($login_fabrica == 10) {
				echo "<td align='center' nowrap>";
				if (strlen($aprovado) == 0) {
					echo "<a href='$PHP_SELF?aprovar=$extrato'>Aprovar</a>";
					echo " <input type='checkbox' class='frm' name='aprovar_$i' id='aprovar' value='$extrato'>";
				}
				echo "</td>\n";
			}
			echo "</tr>\n";
		}
		echo "<tr>\n";
		echo "<td colspan='6'>&nbsp;</td>\n";
		if ($login_fabrica == 6) {
			echo "<td align='center' nowrap>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"liberar_tudo\" ; document.Selecionar.submit() '>Liberar Selecionados</a>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "</td>\n";
		}
		echo "<td >&nbsp;</td>\n";
		if ($login_fabrica == 10) {
			echo "<td align='center' nowrap>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"aprovar_tudo\" ; document.Selecionar.submit() '>Aprovar Selecionados</a>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "</td>\n";
		}
		echo "<td colspan='2'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
	}
}
?>
<p>
<p>
<? include "rodape.php"; ?>