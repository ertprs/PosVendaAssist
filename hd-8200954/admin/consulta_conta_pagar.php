<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];


//$layout_menu = "financeiro";
//$title = "Consulta e Manutenção de Extratos";

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
;
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

function AbrirJanelaObs (extrato) {
	var largura  = 400;
	var tamanho  = 250;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function gerarExportacao(but){
	 if (but.value == 'Exportar Extratos' ) {
		if (confirm('Deseja realmente prosseguir com a exportação?\n\nSerá exportado somente os extratos aprovados e liberados.')){
			but.value='Exportando...';
			exportar();
		}
	} else {
		 alert ('Aguarde submissão');
	}

}


</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<?
if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}


$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

$fornecedor_nome   = $_POST['fornecedor_nome'];
if (strlen($_GET['fornecedor_nome']) > 0) $fornecedor_nome = $_GET['fornecedor_nome'];
if (strlen($_GET['razao']) > 0) $fornecedor_nome = $_GET['razao'];

$fornecedor_codigo = $_POST['fornecedor_codigo'];
if (strlen($_GET['fornecedor_codigo']) > 0) $fornecedor_codigo = $_GET['fornecedor_codigo'];
if (strlen($_GET['cnpj']) > 0) $fornecedor_codigo = $_GET['cnpj'];

echo "<TABLE width='600' align='center' border='0' cellspacing='3' cellpadding='2'>\n";
echo "<FORM METHOD='GET' NAME='conta_pagar' ACTION=\"$PHP_SELF\">";
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
echo "CNPJ";
echo "		<input type='text' name='fornecedor_codigo' size='18' value='$fornecedor_codigo' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.fornecedor_nome,document.frm_extrato.fornecedor_codigo,'cnpj')\">";

echo "&nbsp;&nbsp;Razão Social ";
echo "		<input type='text' name='fornecedor_nome' size='45' value='$fornecedor_nome' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.fornecedor_nome,document.frm_extrato.fornecedor_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "<TR>\n";

echo "</TABLE>\n";

echo "<br><img src=\"imagens_admin/btn_filtrar.gif\" onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";

echo "</form>";


// INICIO DA SQL
$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];
$data_final   = $_POST['data_final'];
if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];
$fornecedor_codigo = $_POST['fornecedor_codigo'];
if (strlen($_GET['cnpj']) > 0) $fornecedor_codigo = $_GET['cnpj'];

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


if (strlen ($fornecedor_codigo) > 0 OR (strlen ($data_inicial) > 0 and strlen ($data_final) > 0) ) {
	$sql = "SELECT  tbl_pagar.fornecedor                                                        ,
			FROM      tbl_pagar
			LEFT JOIN tbl_extrato_pagamento ON tbl_extrato.extrato        = tbl_extrato_pagamento.extrato
			WHERE     tbl_pagar.posto = $login_posto";

	if (strlen ($data_inicial) < 8) $data_inicial = date ("d/m/Y");
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $data_final = date ("d/m/Y");
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0)
		$sql .= " AND      tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

	$xfornecedor_codigo = str_replace (" " , "" , $fornecedor_codigo);
	$xfornecedor_codigo = str_replace ("-" , "" , $xfornecedor_codigo);
	$xfornecedor_codigo = str_replace ("/" , "" , $xfornecedor_codigo);
	$xfornecedor_codigo = str_replace ("." , "" , $xfornecedor_codigo);

	if (strlen ($fornecedor_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj = '$xfornecedor_codigo' ";
	if (strlen ($fornecedor_nome) > 0 )   $sql .= " AND tbl_posto.nome ILIKE '%$fornecedor_nome%' ";

	$sql .= " GROUP BY tbl_posto.posto ,
					tbl_posto.nome ,
					tbl_posto.cnpj ,
					tbl_extrato_pagamento.valor_liquido";
	if ($login_fabrica <> 1) $sql .= " ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	else                     $sql .= " ORDER BY tbl_posto_fabrica.codigo_posto, tbl_extrato.data_geracao";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}
// echo "$sql";
	if (pg_numrows ($res) > 0) {

		echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
		echo "</tr>";
		echo "</table>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto          = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$nome           = trim(pg_result($res,$i,nome));

			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='2'>\n";
				echo "<tr class = 'menu_top'>\n";
				echo "<td align='center'>Código</td>\n";
				echo "<td align='center' nowrap>Nome do Posto</td>\n";
				if ($login_fabrica == 1) echo "<td align='center' nowrap>Tipo</td>\n";
				if ($login_fabrica == 1 OR $login_fabrica == 19) {
				echo "<td align='center'>Protocolo</td>\n";
				} else {
				echo "<td align='center'>Extrato</td>\n";
				}
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>Qtde. OS</td>\n";
				if ($login_fabrica == 1 ) {
					echo "<td align='center'>Total Peça</td>\n";
					echo "<td align='center'>Total MO</td>\n";
					echo "<td align='cent			if ($login_fabrica==2){
				
					
			}er'>Total Avulso</td>\n";
					echo "<td align='center'>Total Geral</td>\n";
					echo "<td align='center'>Obs.</td>\n";
				}else{
					echo "<td align='center'>Total</td>\n";
					
					// SONO - 04/09/206 exibir valor_liquido para intelbras //
					if ($login_fabrica == 14) {
						echo "<td align='center' nowrap>Total Líquido</td>\n";
					}
				}
				echo "<td align='center'>Baixado em</td>\n";
				if ($login_fabrica == 6 OR $login_fabrica == 14 OR $login_fabrica == 15 OR $login_fabrica == 11 OR $login_fabrica == 24) {
					echo "<td align='center'>Liberar <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.liberar);'></td>\n";
					if ($login_fabrica == 11) echo "<td align='center' nowrap>Posto sem<br>email</td>\n";
				}
				if ($login_fabrica == 1) {
					echo "<td align='center'>Acumular <input type='checkbox' class='frm' name='marcar' value='tudo' title='Selecione ou desmarque todos' onClick='check(this.form.acumular);'></td>\n";
				}
				echo "<td align='center' colspan='3' nowrap>Valores Adicionais ao Extrato</td>\n";
				echo "</tr>\n";
			}

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

			echo "<tr bgcolor='$cor'>\n";

			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
			if ($login_fabrica == 1) echo "<td align='center' nowrap>$tipo_posto</td>\n";
			if($login_fabrica == 20)echo "<td align='center'><a href='extrato_os_aprova";
			else echo "<td align='center'><a href='extrato_consulta_os";
			if ($login_fabrica == 14) echo "_intelbras";
			echo ".php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xfornecedor_codigo&razao=$fornecedor_nome' target='_blank'>";
			if ($login_fabrica == 1 OR $login_fabrica == 19 ) echo $protocolo;
			else                     echo $extrato;
			echo "</a></td>\n";
			echo "<td align='left'>$data_geracao</td>\n";
			echo "<td align='center'>$qtde_os</td>\n";
			if ($login_fabrica == 1) {
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
					echo "<td align='right' nowrap> " . number_format(pg_result($resT,0,total_pecas),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap> " . number_format(pg_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap> " . number_format(pg_result($resT,0,total_avulso),2,',','.') . "</td>\n";
				}else{
					echo "<td>&nbsp;</td>\n";
					echo "<td>&nbsp;</td>\n";
					echo "<td>&nbsp;</td>\n";
				}
			}
			echo "<td align='right' nowrap> $total</td>\n";

			// SONO - 04/09/206 exibir valor_liquido para intelbras //
			if ($login_fabrica == 14) {
				echo "<td align='right' nowrap> $valor_liquido</td>\n";
			}	

			if ($login_fabrica == 1 ) echo "<td><a href=\"javascript: AbrirJanelaObs('$extrato');\">OBS.</a></td>\n";
			echo "<td align='left'>$baixado</td>\n";
			if ($login_fabrica == 6 OR $login_fabrica == 14 OR $login_fabrica == 15 OR $login_fabrica == 11 OR $login_fabrica == 24) {
				echo "<td align='center' nowrap>";
				if (strlen($liberado) == 0) {
					echo "<a href='$PHP_SELF?liberar=$extrato'>Liberar</a>";
					echo " <input type='checkbox' class='frm' name='liberar_$i' id='liberar' value='$extrato'>";
				}
				echo "</td>\n";
			}

			if ($login_fabrica == 11) {
				echo "<td align='center' nowrap>";
				if (strlen($email) == 0) {
					?>
					<center>
					<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;'>
					<?
				} else {
					echo "&nbsp;";
				}
				echo "</td>\n";
			}
			if ($login_fabrica == 24) {
				echo "<td align='center' nowrap>";
				if (strlen($email) == 0) {
					?>
					<center>
					<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('extrato_consulta_os_print.php?extrato=<? echo $extrato; ?>','printextrato','toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" ALT='Imprimir' border='0' style='cursor:pointer;'>
					<?
				} else {
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			if ($login_fabrica == 1 OR $login_fabrica == 2 OR $login_fabrica == 8 OR $login_fabrica==20) {
				if ($msg_os_deletadas==""){
					echo "<td align='center' nowrap>";
					if (strlen($aprovado) == 0){
						echo "<a href='$PHP_SELF?aprovar=$extrato&posto=$posto&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xfornecedor_codigo&razao=$fornecedor_nome'><img src='imagens_admin/btn_aprovar_azul.gif' ALT='Aprovar o extrato'></a>";
						if($login_fabrica<>20)
						echo "<input type='checkbox' name='acumular_$i' id='acumular' value='$extrato' class='frm'>\n";
					}
					echo "</td>\n";
				}
			}

			// se o msg_os_deletadas for nulo o extrato não foi cancelado. Se não for nulo, o Extrato foi cancelado
			if ($msg_os_deletadas==""){
				echo "<td>";
				if (strlen($aprovado) == 0 OR $login_fabrica == 8)
					echo "<a href='extrato_avulso.php'><img src='imagens/btn_novo_azul.gif' ALT='Cadastrar um Novo Extrato'></a>";
				echo "</td>\n";
	
				echo "<td>";
				if (strlen($aprovado) == 0 OR $login_fabrica == 8)
					echo "<a href='extrato_avulso.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_adicionar_azul.gif' ALT = 'Lançar itens no extrato'></a>";
				echo "</td>\n";
			}
			else{ //só entra aqui se o extrato foi excluido e a fabrica eh 2-  DYNACON
				echo "<td colspan='3' align='center'>";
				echo "<b style='font-size:10px;color:red'>Extrato cancelado!!</b>";
				echo "</td>";	
				echo "</tr>";
				echo "<tr>";
				echo		 "<td></td>";
				echo 		"<td colspan=9 align='left'> <b style='font-size:12px;font-weight:normal'>$msg_os_deletadas</b> </td>";
				echo 	"</td>";
			}

			echo "</tr>\n";
		}
		echo "<tr>\n";
		echo "<td colspan='7'>&nbsp;</td>\n";
		if ($login_fabrica == 6 OR $login_fabrica == 14 OR $login_fabrica == 15 OR $login_fabrica == 11 OR $login_fabrica==20 OR $login_fabrica == 24) {
			echo "<td align='center'>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"liberar_tudo\" ; document.Selecionar.submit() '><font size='2'>Liberar Selecionados</font></a>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "</td>\n";
		}

		if ($login_fabrica == 1 ) {
			echo "<td colspan='5'>&nbsp;</td>\n";
			echo "<td align='center'>";
			echo "<a href='javascript: document.Selecionar.btnacao.value=\"acumular_tudo\" ; document.Selecionar.submit() '>Acumular selecionados</a>";
			echo "<input type='hidden' name='total_postos' value='$i'>";
			echo "</td>\n";
		}
		echo "<td colspan='2'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "</form>\n";
		if ($login_fabrica==20){
			echo "<br><center><div class='quadro'><input type='button' name='btn_exportar'' class='botao' value='Exportar Extratos' onclick=\"javascript:gerarExportacao(this)\"><br>Só serão exportados os Extratos que foram <B>Aprovados e Liberados</b></div></center>";
		}

	}

	if (strlen($msg_os_deletadas)>0 and$login_fabrica==2){
		echo "<br><div name='os_excluidas' style='border:1px solid #00ffff'><h4>OS excluidas</h4>$msg_os_deletadas;</div>";
	}


}
?>

<br>

<? include "rodape.php"; ?>
