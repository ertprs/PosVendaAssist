<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="auditoria";
$layout_menu = 'auditoria';
include "funcoes.php";

$arrayEstados = array('' => 'Todos', 'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão', 'MG' => 'Minas Gerais', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PE' => 'Pernambuco', 'PI' => 'Piauí', 'PR' => 'Paraná', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'RS' => 'Rio Grande do Sul', 'SC' => 'Santa Catarina', 'SE' => 'Sergipe', 'SP' => 'São Paulo', 'TO' => 'Tocantins', 'AM, RR, AP, PA, TO, RO, AC' => 'Região Norte', 'MA, PI, CE, RN, PE, PB, SE, AL, BA' => 'Região Nordeste', 'MT, MS, GO' => 'Região Centro-Oeste', 'SP, RJ, ES, MG' => 'Região Sudeste', 'PR, SC, RS' => 'Região Sul');

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) ilike UPPER('%$q%') ";
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
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_produto.referencia like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) ilike UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$codigo_posto = trim($_POST["codigo_posto"]);
if (strlen($codigo_posto) == 0) $codigo_posto = $_GET["codigo_posto"];

$estado = trim($_POST["estado"]);
if (strlen($estado) == 0) $estado = $_GET["estado"];

if ($estado) {
	if (array_key_exists($estado, $arrayEstados)) {
	}
	else {
		$estado = "";
	}
}

if ($codigo_posto) {
	$sql = "
	SELECT
	posto

	FROM
	tbl_posto_fabrica

	WHERE
	fabrica=$login_fabrica
	AND codigo_posto='$codigo_posto'
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$posto = pg_result($res, 0, posto);
	}
	else {
		$codigo_posto = "";
		$posto = "";
	}
}

if(strlen($posto)) {
	$sql="SELECT posto FROM tbl_posto_fabrica WHERE posto='$posto' AND fabrica=$login_fabrica";
	$res = pg_exec($con,$sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Posto $codigo_posto não encontrado";
	}
	elseif ($acao == "PESQUISAR") {
		header("location:os_auditar_posto.php?posto=$posto&status=aprovacao&acao=PESQUISAR");
		die;
	}
}

if ($estado) {
	if (array_key_exists($estado, $arrayEstados)) {
	}
	else {
		$msg_erro = "Estado $estado inválido";
	}
}

if (strlen($estado) == 0 && strlen($posto) == "" && $acao == "PESQUISAR") {
	header("location:os_auditar_posto.php?status=aprovacao&acao=PESQUISAR");
}

$title = "Auditoria Prévia nas Ordens de Serviço";

include "cabecalho.php";
?>

<style type="text/css">
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
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

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>

<script language="JavaScript">
$(function() {
	// add new widget called repeatHeaders
	$.tablesorter.addWidget({
		// give the widget a id
		id: "repeatHeaders",
		// format is called when the on init and when a sorting has finished
		format: function(table) {
			// cache and collect all TH headers
			if(!this.headers) {
				var h = this.headers = [];
				$("thead th",table).each(function() {
					h.push(
						"<th>" + $(this).text() + "</th>"
					);

				});
			}

			// remove appended headers by classname.
			$("tr.repated-header",table).remove();

			// loop all tr elements and insert a copy of the "headers"
			for(var i=0; i < table.tBodies[0].rows.length; i++) {
				// insert a copy of the table head every 10th row
				if((i%20) == 0) {
					if(i!=0){
					$("tbody tr:eq(" + i + ")",table).before(
						$("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

					);
				}}
			}

		}
	});
	$("table").tablesorter({
		widgets: ['zebra', 'repeatHeaders']
	});

});


$(function()
{
/*	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");*/
});

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});
});

</script>

<? if (strlen($msg_erro) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center">
		<tr class="msg_erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" >
		<tr class="sucesso">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table>
<? } ?>

	
	<form name="frm_busca" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="acao">
	<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" class='formulario'>
		<tr class="titulo_tabela">
			<td colspan="4" height='25'>Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>

		<tr align='left'>
			<td width="10">&nbsp;</td>
			<td>Posto</td>
			<td>Nome do Posto</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr align='left'>
			<td width="150">&nbsp;</td>
			<td width='140'>
				<input type="text" name="codigo_posto" id="codigo_posto" size="15"  value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'codigo')">
			</td>
			<td>
				<input type="text" name="posto_nome" id="posto_nome" size="40"  value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'nome')">
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="2">Estado ou Região</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr align='left'>
			<td width="10">&nbsp;</td>
			<td nowrap colspan="2">
				<select class="frm" name='estado' id='estado'>
				<?php

				foreach($arrayEstados as $valor => $label) {
					if ($estado == $valor) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					if ($valor != '') {
						$label = "$label ($valor)";
					}

					echo "<option value='$valor' $selected>$label</option>";
				}

				?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr>
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="4" align='center' style='padding:10px 0 10px 0;'><input type='button' value='Pesquisar' onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
		</tr>
	</table>
	</form>

<?

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	if ($estado) {
		$estadoWhere = explode(", ", $estado);
		$estadoWhere = implode("', '", $estadoWhere);
		$estadoWhere = "AND tbl_posto.estado IN ('$estadoWhere')";
	}

	$sql = "
	SELECT
	DISTINCT
	tbl_posto.posto,
	tbl_posto_fabrica.codigo_posto,
	tbl_posto.nome

	FROM
	tbl_os_auditar
	JOIN tbl_os ON tbl_os_auditar.os=tbl_os.os
	JOIN tbl_posto ON tbl_os.posto=tbl_posto.posto
	JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
		 AND tbl_posto_fabrica.fabrica=$login_fabrica
	
	WHERE
	tbl_os_auditar.fabrica=$login_fabrica
	AND tbl_os.fabrica=$login_fabrica
	$estadoWhere
	";
	$res = pg_query($con, $sql);
	$numero_registros = pg_num_rows($res);
	
	echo "
	<table class='tablesorter tabela'>
	<thead>
		<tr>
			<th width=25%>Código Posto</th>
			<th width=75%>Posto</th>
		</tr>
	</thead>
	<tbody>";
	
	for ($i = 0; $i < pg_num_rows($res); $i++) {
		$posto = pg_result($res, $i, posto);
		$codigo_posto = pg_result($res, $i, codigo_posto);
		$nome = pg_result($res, $i, nome);
		$link = "<a href='os_auditar_posto.php?posto=$posto&status=aprovacao&acao=PESQUISAR'>";
		$link_fecha = "</a>";
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		echo  "
		<tr bgcolor='$cor'>
			<td>$link$codigo_posto$link_fecha</td>
			<td>$link$nome$link_fecha</td>
		</tr>";
	}

	if ($numero_registros) {
	}
	else {
		echo "<br><FONT size='2' COLOR=\"#FF3333\"><B>Não encontrado!</B></FONT><br><br>";
	}
}
echo "<br>";


include "rodape.php";
?>
