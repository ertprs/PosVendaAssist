<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos Enviados ao Financeiro";

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

function AbrirJanela (extrato) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_financeiro_envio.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function AbrirJanelaObs (extrato) {
	var largura  = 750;
	var tamanho  = 550;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status_aprovado.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
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
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

##### Pesquisa de produto #####
if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo  = trim($_POST["posto_codigo"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo  = trim($_GET["posto_codigo"]);
if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);
if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
	if (strlen($posto_codigo) > 0) $sql .= " AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
	if (strlen($posto_nome) > 0) $sql .= " AND   tbl_posto.nome ILIKE '%$posto_nome%';";
	
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$posto        = pg_result($res,0,posto);
		$posto_codigo = pg_result($res,0,codigo_posto);
		$posto_nome   = pg_result($res,0,nome);
	}else{
		$msg_erro .= " Posto não encontrado. ";
	}
}


echo "<TABLE width='680' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Consultar postos com extratos fechados entre";
echo "	</TD>";
echo "<TR>\n";

echo "<TR>\n";
echo "	<TD ALIGN='center'>";
echo "	Data Inicial ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' value='$data_inicial' class='frm'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaInicial_Extrato')\" style='cursor:pointer' alt='Clique aqui para abrir o calendário'>\n";
echo "	</TD>\n";

echo "	<TD ALIGN='center'>";
echo "	Data Final ";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' value='$data_final' class='frm'>&nbsp;<IMG src=\"imagens_admin/btn_lupa.gif\" align='absmiddle' onclick=\"javascript:showCal('dataPesquisaFinal_Extrato')\" style='cursor:pointer' alt='Clique aqui para abrir o calendário'>\n";
echo "</TD>\n";
echo "</TR>\n";

echo "<TR class='menu_top'>\n";
echo "	<TD COLSPAN='2' ALIGN='center'>";
echo "		Somente extratos do posto";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD COLSPAN='2' ALIGN='center' nowrap>";
echo "CNPJ/Código";
echo "		<input type='text' name='posto_codigo' size='18' value='$posto_codigo' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'cnpj')\">";
echo "&nbsp;&nbsp;";
echo "Razão Social ";
echo "		<input type='text' name='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";

echo "</TABLE>\n";

echo "<br><img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";


// INICIO DA SQL
$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0)  $data_inicial = $_GET['data_inicial'];
if (strlen($_POST['data_inicial']) > 0) $data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_final']) > 0)  $data_final = $_GET['data_final'];
if (strlen($_POST['data_final']) > 0) $data_final = $_POST['data_final'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['posto_codigo'];

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
	$sql = "SELECT  tbl_posto.posto                                                         ,
					tbl_posto.nome                                                          ,
					tbl_posto.cnpj                                                          ,
					tbl_posto_fabrica.codigo_posto                                          ,
					tbl_tipo_posto.descricao                                AS tipo_posto   ,
					tbl_extrato.extrato                                                     ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra                               ,
					TO_CHAR(tbl_extrato.aprovado,'dd/mm/yy')              AS aprovado     ,
					LPAD(tbl_extrato.protocolo,5,'0')                       AS protocolo    ,
					TO_CHAR(tbl_extrato.data_geracao,'dd/mm/yy')          AS data_geracao ,
					tbl_extrato.total                                                       ,
					(
						SELECT	count (tbl_os.os)
						FROM	tbl_os JOIN tbl_os_extra USING (os)
						WHERE tbl_os_extra.extrato = tbl_extrato.extrato
					)                                                       AS qtde_os      ,
					TO_CHAR(tbl_extrato_financeiro.data_envio,'dd/mm/yy') AS data_envio
			FROM      tbl_extrato
			JOIN      tbl_posto USING (posto)
			JOIN      tbl_posto_fabrica     ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto        ON tbl_tipo_posto.tipo_posto  = tbl_posto_fabrica.tipo_posto
											AND tbl_tipo_posto.fabrica    = $login_fabrica
			JOIN      tbl_extrato_financeiro ON tbl_extrato.extrato       = tbl_extrato_financeiro.extrato
			JOIN      tbl_extrato_extra on tbl_extrato_extra.extrato = tbl_extrato.extrato
			WHERE     tbl_extrato.fabrica = $login_fabrica
			AND       tbl_extrato.aprovado NOTNULL
			AND       tbl_extrato_financeiro.data_envio NOTNULL";

	if (strlen ($data_inicial) < 8) $x_data_inicial = "";
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $x_data_final = "";
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) == 10 AND strlen ($x_data_final) == 10)
		$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);

	if (strlen ($posto_codigo) > 0 ) $sql .= " AND (tbl_posto.cnpj = '$xposto_codigo' or tbl_posto_fabrica.codigo_posto = '$xposto_codigo') ";
	if (strlen ($posto_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%' ";

	$sql .= " GROUP BY tbl_posto.posto                ,
					tbl_posto.nome                    ,
					tbl_posto.cnpj                    ,
					tbl_posto_fabrica.codigo_posto    ,
					tbl_tipo_posto.descricao          ,
					tbl_extrato.extrato               ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra                      ,
					tbl_extrato.total                 ,
					tbl_extrato.aprovado              ,
					LPAD(tbl_extrato.protocolo,5,'0') ,
					tbl_extrato.data_geracao          ,
					tbl_extrato_financeiro.data_envio
			ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	$res = pg_exec ($con,$sql);
	
//if ($ip=="201.43.201.204")echo nl2br($sql);

	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}

	if (pg_numrows ($res) > 0) {

		echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
		echo "</tr>";
		echo "</table>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto        = trim(pg_result($res,$i,posto));
			$codigo_posto = trim(pg_result($res,$i,codigo_posto));
			$nome         = trim(pg_result($res,$i,nome));
			$nome = substr($nome,0,15);
			$tipo_posto   = trim(pg_result($res,$i,tipo_posto));
			$extrato      = trim(pg_result($res,$i,extrato));
			$data_geracao = trim(pg_result($res,$i,data_geracao));
			$qtde_os      = trim(pg_result($res,$i,qtde_os));
			$total        = trim(pg_result($res,$i,total));
			$data_envio   = trim(pg_result($res,$i,data_envio));
			$extrato      = trim(pg_result($res,$i,extrato));
			$total	      = number_format($total,2,',','.');
			$aprovado     = trim(pg_result($res,$i,aprovado));
			$protocolo    = trim(pg_result($res,$i,protocolo));
			$nf_mo        = trim(pg_result($res,$i,nota_fiscal_mao_de_obra));
			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='2' style='font-size:11px;'>\n";
				echo "<tr class = 'menu_top'>\n";
				echo "<td align='center'>Código</td>\n";
				echo "<td align='center' nowrap>Nome do Posto</td>\n";
				echo "<td align='center'>Tipo</td>\n";
				echo "<td align='center'>Extrato</td>\n";
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>OS</td>\n";
				echo "<td align='center'>Total Peça</td>\n";
				echo "<td align='center'>Total MO</td>\n";
				echo "<td align='center'>Total Avulso</td>\n";
				echo "<td align='center'>Total Geral</td>\n";
				echo "<td align='center'>Aprovação</td>\n";
				echo "<td align='center'>NF Autor.</td>\n";
	if ($login_fabrica == 1) echo "<td align='center' nowrap>Pendência</td>\n";
				echo "<td align='center'>Financeiro</td>\n";
				echo "</tr>\n";
			}

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    fabrica = $login_fabrica";
				$res_avulso = pg_exec($con,$sql);

				if (@pg_numrows($res_avulso) > 0) {
					if (@pg_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}

			}
			##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

			echo "<tr bgcolor='$cor'>\n";

			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
			echo "<td align='center' nowrap>$tipo_posto</td>\n";
			echo "<td align='center'><a href='extrato_consulta_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'"; if ($login_fabrica == 1) echo " target='_blank'"; echo ">";
			echo $protocolo;
			echo "</a></td>\n";
			echo "<td align='left'>$data_geracao</td>\n";
			echo "<td align='center'>$qtde_os</td>\n";
			$sql =	"SELECT SUM(tbl_os.pecas)       AS total_pecas     ,
							SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
							tbl_extrato.avulso      AS total_avulso
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
					WHERE tbl_os_extra.extrato = $extrato
					GROUP BY tbl_extrato.avulso;";
			$resT = pg_exec($con,$sql);

			if (pg_numrows($resT) == 1) {
				echo "<td align='right' nowrap>R$ " . number_format(pg_result($resT,0,total_pecas),2,',','.') . "</td>\n";
				echo "<td align='right' nowrap>R$ " . number_format(pg_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
				echo "<td align='right' nowrap>R$ " . number_format(pg_result($resT,0,total_avulso),2,',','.') . "</td>\n";
			}else{
				echo "<td>&nbsp;</td>\n";
				echo "<td>&nbsp;</td>\n";
				echo "<td>&nbsp;</td>\n";
			}
			echo "<td align='right' nowrap>R$ $total</td>\n";
			echo "<td align='center' nowrap>" . $aprovado . "</td>\n";
			echo "<td align='center' nowrap>" . $nf_mo . "</td>\n";
						if ($login_fabrica == 1){ 
				echo "<td><a href=\"javascript: AbrirJanelaObs('$extrato');\"><font size='1'>Abrir</font></a>";
				echo "</td>\n";
			}
			echo "<td align='center' nowrap><a href=\"javascript: AbrirJanela('$extrato');\">" . $data_envio . "</a></td>\n";

			echo "</tr>\n";
		}
		echo "<tr>\n";
		echo "<td colspan='7'>&nbsp;</td>\n";
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
