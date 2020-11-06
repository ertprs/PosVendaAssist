<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}



$msg_erro = "";
$msg      = "";

$btn_acao = trim($_GET['btn_acao']);
if (strlen($btn_acao)==0){
	$btn_acao = trim($_POST['btn_acao']);
}

$layout_menu = "auditoria";
$title = strtoupper("RelatÓrio de Vistoria de PeÇas");

include "cabecalho.php";
?>

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
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
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
	border-collapse: collapse;
	border:1px solid #596d9b;
}
#msg{ width:700px; margin:auto; }

</style>


<? include "../js/js_css.php";  // adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<!--
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
-->
<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<script language="JavaScript">

/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	else if (tipo == "nome" ) {
		var xcampo = campo2;
	}
	else alert('Campo não existe');

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para realizar a pesquisa!');
}



function verificarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value == 'NaN') {
		campo.value = '';
	}
	if (campo.value <0) {
		campo.value = 0;
	}
}

</script>

<div id="msg"></div>
<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="btn_acao">
<TABLE width="700" align="center" border="0" cellspacing='1' cellpadding='0' class='formulario'>

<tbody>
<tr><td class="titulo_tabela" colspan="3">Parâmetros de Pesquisa</td></tr>
<TR>
	<td width="25%">&nbsp;</td>
	<td width="150px">
		Data Inicial
		<br>
		<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='<? echo $data_inicial ?>' class='frm'>
	</td>

	<td nowrap>
		Data Final
		<br>
		<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='<? echo $data_final ?>' class='frm'>
	</td>
</tr>

<TR>
	<td>&nbsp;</td>
	<td nowrap>
		Código do Posto
		<br>
		<input class="frm" type="text" name="posto_codigo"  id="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome, 'codigo')"></A>
	</td>

	<td nowrap>
		Nome do Posto
		<br>
		<input class="frm" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.posto_codigo, document.frm_relatorio.posto_nome, 'nome')" style="cursor:pointer;"></A>
	</td>
</tr>
<tr>
	<td colspan="3" align='center' style="padding:10px 0 10px;">
		<input type="button" onclick="javascript: document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " value="Pesquisar" />
	</td>
</tr>
</tbody>
</table>
</form>



<? if (strlen($msg_erro) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg_erro ?></td>
	</tr>
</table>
<? } ?>

<? if (strlen($msg) > 0) { ?>
<br>
<table width="650" border="0" align="center" class="menu_top">
	<tr>
		<td><?echo $msg ?></td>
	</tr>
</table>
<? } ?>

<?


$btn_acao = $_POST['btn_acao'];

if ($btn_acao=="PESQUISAR"){
	$data_inicial = trim($_POST['data_inicial']);
	if (strlen(trim($_GET['data_inicial'])) > 0) {
		$data_inicial = trim($_GET['data_inicial']);
	}
	$data_final   = trim($_POST['data_final']);
	if (strlen(trim($_GET['data_final'])) > 0) {
		$data_final = trim($_GET['data_final']);
	}

	$posto_codigo = trim($_POST['posto_codigo']);
	if (strlen(trim($_GET['posto_codigo'])) > 0) {
		$posto_codigo = trim($_GET['posto_codigo']);
	}
	$posto_nome = trim($_POST['posto_nome']);
	if (strlen(trim($_GET['posto_nome'])) > 0) {
		$posto_nome = trim($_GET['posto_nome']);
	}

	$data_inicial = str_replace (" " , "" , $data_inicial);
	$data_inicial = str_replace ("-" , "" , $data_inicial);
	$data_inicial = str_replace ("/" , "" , $data_inicial);
	$data_inicial = str_replace ("." , "" , $data_inicial);


	$data_final = str_replace (" " , "" , $data_final);
	$data_final = str_replace ("-" , "" , $data_final);
	$data_final = str_replace ("/" , "" , $data_final);
	$data_final = str_replace ("." , "" , $data_final);


	if (strlen ($data_inicial) == 6) {
		$data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
	}
	if (strlen ($data_final)   == 6) {
		$data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);
	}

	if (strlen ($data_inicial) > 0) {
		$data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);
	}
	if (strlen ($data_final)   > 0) {
		$data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);
		$x_data_final   = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);
	}
/* Validação de datas */
	if( !empty($data_inicial) && !empty($data_final) ) {
		if( (strtotime($x_data_inicial) > strtotime($x_data_final)) ) {
			$msg_erro = 'Data Inválida';
		}

		if(strlen($msg_erro)==0){
			list($d, $m, $y) = explode("/", $data_inicial);
			if(!checkdate($m,$d,$y))
				$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			list($d, $m, $y) = explode("/", $data_final);
			if(!checkdate($m,$d,$y))
				$msg_erro = "Data Inválida";
		}
	}
	if(empty($msg_erro)) {
		if($login_fabrica <> 43) {
			$cfop = " '694921','694922','594919','594920','594921','594922','594923' ";
		}else{
			$cfop = " '5949','599','6949','694921' ";
		}

		$sql = "SELECT
					tbl_posto_fabrica.codigo_posto,
					to_char(tbl_vistoria.data,'DD/MM/YYYY') AS data_inspecao,
					tbl_admin.login,
					tbl_vistoria.valor_total,
					tbl_vistoria.desconto,
					tbl_vistoria.multa,
					tbl_vistoria.parcelas,
					tbl_vistoria.extrato,
					tbl_vistoria.observacao,
					SUM(tbl_faturamento_item.qtde) AS qtde,
					SUM(tbl_faturamento_item.qtde_inspecionada) AS qtde_inspecionada_real,
					to_char(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao
				FROM tbl_vistoria
				JOIN tbl_faturamento      ON tbl_faturamento.extrato_devolucao = tbl_vistoria.extrato
				JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento  = tbl_faturamento.faturamento AND tbl_faturamento.posto NOT IN (4311,13996)
				JOIN tbl_peca             USING (peca)
				JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto           = tbl_faturamento.posto       AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_admin            ON tbl_admin.admin                   = tbl_vistoria.admin
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_vistoria.extrato
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_faturamento.cfop ~'5949|6949'
				AND tbl_peca.devolucao_obrigatoria IS NOT TRUE
				AND tbl_peca.produto_acabado       IS NOT TRUE
				";
		if (strlen($posto_codigo)>0){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		}
		if (strlen($x_data_inicial)>0 AND strlen($x_data_final)>0){
			$sql .= " AND tbl_vistoria.data BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
		}
		$sql .= "
				GROUP BY tbl_posto_fabrica.codigo_posto,
						tbl_vistoria.data,
						tbl_admin.login,
						tbl_vistoria.valor_total,
						tbl_vistoria.desconto,
						tbl_vistoria.multa,
						tbl_vistoria.parcelas,
						tbl_vistoria.extrato,
						tbl_vistoria.observacao,
						tbl_extrato.data_geracao
				ORDER BY tbl_vistoria.data;
				";
				//exit;
		$res = pg_exec ($con,$sql);
		$qtde_vistorias = pg_numrows($res);

		if ($qtde_vistorias>0){
			echo  "<br /><table border='0' cellspacing='1' cellpadding='0' width='700' class='tabela' align='center'>\n";
			echo   "<thead>\n";
			echo   "<tr><td colspan='12' class='titulo_tabela'>Vistoria de Peças</td></tr>\n";
			echo   "<tr class='titulo_coluna' nowrap>\n";
			echo   "<td  nowrap><b>Posto</b></td>\n";
			echo   "<td  nowrap><b>Extrato</b></td>\n";
			echo   "<td  nowrap><b>Data Vistoria</b></td>\n";
			echo   "<td  nowrap><b>Qtde Pç</b></td>\n";
			echo   "<td  nowrap><b>Qtde Pç Vistoriada</b></td>\n";
			echo   "<td  nowrap><b>Responsável</b></td>\n";
			echo   "<td  nowrap><b>Valor de Peças</b></td>\n";
			echo   "<td  nowrap><b>Desconto</b></td>\n";
			echo   "<td  nowrap><b>Multa</b></td>\n";
			echo   "<td  nowrap><b>Nº Parcelas</b></td>\n";
			echo   "<td  nowrap><b>Valor Total</b></td>\n";
			echo   "<td  nowrap><b>Observação</b></td>\n";
			echo   "</tr>\n";
			echo   "</thead>\n";

			echo "<tbody>\n";
			for ($i=0; $i<$qtde_vistorias; $i++){
				$codigo_posto       = pg_result($res,$i,codigo_posto);
				$data_inspecao      = pg_result($res,$i,data_inspecao);
				$login              = pg_result($res,$i,login);
				$valor_total        = pg_result($res,$i,valor_total);
				$parcelas           = pg_result($res,$i,parcelas);
				$desconto           = pg_result($res,$i,desconto);
				$multa              = pg_result($res,$i,multa);
				$extrato            = pg_result($res,$i,extrato);
				$qtde               = pg_result($res,$i,qtde);
				$qtde_inspecionada_real= pg_result($res,$i,qtde_inspecionada_real);
				$observacao         = pg_result($res,$i,observacao);
				// HD 50611
				$data_geracao       = pg_result($res,$i,data_geracao);

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				echo "<tr bgcolor='$cor'>\n";
				echo "<td align='left'>".$codigo_posto."</td>\n";
				echo "<td align='center'>".$data_geracao."</td>\n";
				echo "<td align='center'>".$data_inspecao."</td>\n";
				echo "<td align='center'>".$qtde."</td>\n";
				echo "<td align='center'>".$qtde_inspecionada_real."</td>\n";
				echo "<td align='left'>".$login."</td>\n";
				echo "<td align='right'>".number_format($valor_total,2,",",".")."</td>\n";
				echo "<td align='center'>".$desconto."</td>\n";
				echo "<td align='center'>".$multa."</td>\n";
				echo "<td align='center'>".$parcelas."</td>\n";
				echo "<td align='right'>".number_format($valor_total,2,",",".")."</td>\n";
				echo "<td align='left'>".$observacao."</td>\n";
				echo "</tr>\n";
			}
			echo "</tbody>\n";
			echo "</table>\n";
		}else{
			echo "<p>Nenhum resultado encontrado.</p>";
		}

		$topo ="";
	}
	else echo '<p style="display:none;padding:0;margin:0;" id="msg_erro" class="msg_erro">' . $msg_erro . '</p>';
}

?>

<script type="text/javascript">
	$("#msg_erro").appendTo("#msg").fadeIn("slow");
	$("#msg").click(function(){
		$("#msg").slideToggle();
	});
</script>

<? include "rodape.php"; ?>
