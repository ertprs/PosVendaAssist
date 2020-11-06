<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="auditoria";
$layout_menu = 'auditoria';
include "funcoes.php";

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

$posto = trim($_POST["posto"]);
if (strlen($posto) == 0) $posto = trim($_GET["posto"]);

$codigo_posto = trim($_POST["codigo_posto"]);
if (strlen($codigo_posto) == 0) $codigo_posto = trim($_GET["codigo_posto"]);

$status = trim($_POST["status"]);
if (strlen($status) == 0) $status = $_GET["status"];

$periodo = trim($_POST["periodo"]);
if (strlen($periodo) == 0) $periodo = $_GET["periodo"];


if($acao == "PESQUISAR"){
	if ($status != "aprovacao") {
		$periodo = explode("/", $periodo);
		if (count($periodo) == 2) {
			$periodo_mes = intval($periodo[0]);
			$periodo_ano = intval($periodo[1]);

			if ($periodo_mes < 1 || $periodo_mes > 12) {
				$msg_erro = "Período informado é inválido";
			}
			if($periodo_mes < 10){
				$periodo_mes = '0'.$periodo_mes;
			}
		}
		else {
			$msg_erro = "Para status diferente de \"Em aprovação\" é obrigatório informar o mês para a pesquisa";
		}
	}
	else {
		$periodo = "";
	}

	if(strlen($posto)) {
		$sql="
		SELECT
		tbl_posto.posto,
		codigo_posto,
		tbl_posto.nome
		
		FROM
		tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
		
		WHERE
		tbl_posto.posto=$posto
		AND tbl_posto_fabrica.fabrica=$login_fabrica
		";
		$res = pg_exec($con,$sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro = "Informe o posto para a pesquisa";
			$posto = "";
			$codigo_posto = "";
			$posto_nome = "";
		}
		else {
			$acao = "PESQUISAR";
			$codigo_posto = pg_result($res, 0, codigo_posto);
			$posto_nome = pg_result($res, 0, nome);
		}
	}
	else {
		if (strlen($codigo_posto)) {
			$sql = "
			SELECT
			tbl_posto.posto,
			tbl_posto.nome
			
			FROM
			tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto

			WHERE
			fabrica=$login_fabrica
			AND codigo_posto='$codigo_posto'
			";
			$res = pg_query($sql);
			
			if (pg_num_rows($res)) {
				$acao = "PESQUISAR";
				$posto = pg_result($res, 0, posto);
				$posto_nome = pg_result($res, 0, nome);
			}
			else {
				$msg_erro = "Informe o posto para a pesquisa";
			}
		}
		else {
	//		$msg_erro = "Informe o posto para a pesquisa";
		}
	}
}
$title = "AUDITORIA PRÉVIA NAS ORDENS DE SERVIÇO";

if ($_POST["formato"] == "xls") {
	ob_start();
}
else {
	include "cabecalho.php";
}

$estilo_dados_os = "background: #AABBFF;";
$estilo_aprovar = "background: #44FF44;";
$estilo_aprovar_sistema = "background: #00FFFF;";
$estilo_reprovar = "background: #FF9944;";
$estilo_aprovar_mao_de_obra = "background: #44FFAA;";
$estilo_aprovar_pecas = "background: #AAFF44;";
$estilo_excluir_os = "background: #FF6666;";

if ($_POST["formato"] == "xls") {
}
else {
?>

<style type="text/css">
	.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial" !important;
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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
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

acronym {
	cursor:help;
}

#tabela_principal tr td div{
	overflow:hidden;
	white-space:nowrap;
	width:120px;
	
}

#tabela_principal tr td span{
	width:100%;
	display:block;
	
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

<?php

echo "
estilo_aprovar = \"$estilo_aprovar\";
estilo_reprovar = \"$estilo_reprovar\";
estilo_aprovar_mao_de_obra = \"$estilo_aprovar_mao_de_obra\";
estilo_aprovar_pecas = \"$estilo_aprovar_pecas\";
estilo_excluir_os = \"$estilo_excluir_os\";
";

?>

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
	$("#tabela_principal").tablesorter({
		widgets: []
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

function acao_auditoria_os(os, os_auditar, acao, acao_label) {
	if (acao == "excluir_os") {
		mensagem = "Excluir a OS " + os + "?";
	}
	else {
		mensagem = acao_label + " a auditoria para a OS " + os + "?";
	}
	
	if (acao == "reprovar") {
		if ($("#justificativadiv_"+os).css("display") == "none") {
			$("#justificativadiv_"+os).css("display", "inline");
			$("#justificativa_"+os).focus();
			return false;
		}
		else if ($("#justificativa_"+os).val() == "") {
			alert("Digite uma justificativa para a reprova");
			return false;
		}
	}


	if (confirm(mensagem)) {
		$("#btn_aprovar").attr("disabled", true);
		$("#btn_reprovar").attr("disabled", true);
		$("#btn_aprovar_mao_de_obra").attr("disabled", true);
		$("#btn_aprovar_pecas").attr("disabled", true);
		$("#btn_excluir_os").attr("disabled", true);
		justificativa = $("#justificativa_"+os).val();

		url = "os_auditar_posto_ajax.php?acao="+acao+"&os="+os+"&os_auditar="+os_auditar+"&justificativa="+justificativa;
		requisicaoHTTP("GET", url, true , "auditoria_os_retorno", os);
	}
}

function auditoria_os_retorno(retorno, os) {
	retorno = retorno.split('|');
	var acao = retorno[0];
	var status = retorno[1];
	var mensagem = retorno[2];

	$("#btn_aprovar").attr("disabled", false);
	$("#btn_reprovar").attr("disabled", false);
	$("#btn_aprovar_mao_de_obra").attr("disabled", false);
	$("#btn_aprovar_pecas").attr("disabled", false);
	$("#btn_excluir_os").attr("disabled", false);

	switch(status) {
		case "sucesso":
			$("#acoes_" + os).attr("style", $("#btn_"+acao).attr("style"));
			$("#acoes_" + os).html(mensagem);
		break;

		case "erro":
			alert(mensagem);
		break;

		default:
			alert("Ocorreu um erro no sistema, contate o HelpDesk");
	}
}

function status_onchange() {
	if ($("#status").val() == "aprovacao") {
		$("#periodo").attr("disabled", true);
	}
	else {
		$("#periodo").attr("disabled", false);
	}
}

</script>

<? if (strlen($msg_erro) > 0) { ?>
	
	<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" >
		<tr class="msg_erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	
	<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" >
		<tr class="sucesso">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table>
<? } ?>

	<form name="frm_busca" method="post" action="<?=$PHP_SELF?>">
	<input type="hidden" name="acao">
	<table width="700" border="0" cellspacing="0" cellpadding="2" align="center" class='formulario'>
		<tr class="titulo_tabela">
			<td colspan="4" height='25'>Parâmetros de Pesquisa</td>
		</tr>
		<tr >
			<td colspan="4">&nbsp;</td>
		</tr>

		<tr  align='left'>
			<td width="130">&nbsp;</td>
			<td>Posto</td>
			<td>Nome do Posto</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="codigo_posto" id="codigo_posto" size="15"  value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'codigo')">
			</td>
			<td>
				<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_busca.codigo_posto, document.frm_busca.posto_nome, 'nome')">
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>Status</td>
			<td>Mês</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<select id="status" name="status" class='frm' onchange="status_onchange();" onkeyup="status_onchange();">
				<?
				$opcoes_status = array();
				$opcoes_status["todas"] = "Todas";
				$opcoes_status["aprovacao"] = "Em aprovação";
				$opcoes_status["aprovadas"] = "Aprovadas";
				$opcoes_status["reprovadas"] = "Reprovadas";

				foreach($opcoes_status as $valor => $label) {
					if ($status == $valor) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}
					echo "
				<option $selected value='$valor'>$label</option>";
				}
				?>
				</select>
			</td>
			<td>
				<?
				if ($status == "aprovacao") {
					$disabled = "disabled";
				}
				else {
					$disabled = "";
				}

				echo "
				<select id='periodo' name='periodo' class='frm' $disabled>";

					$ano = intval(date("Y"));
					$mes = intval(date("n"));
					
					//Mêses a partir de junho/2010
					for($a = $ano; $a >= 2010; $a--) {
						if ($a == 2010) {
							$mes_inicial = 8;
						}
						else {
							$mes_inicial = 1;
						}

						if ($a == $ano) {
							$mes_final = $mes;
						}
						else {
							$mes_final = 12;
						}

						for($m = $mes_final; $m >= $mes_inicial; $m--) {
							$valor = "$m/$a";
							$label = substr("0".$m, -2) . "/$a";

							if ($valor == "$periodo_mes/$periodo_ano") {
								$selected = "selected";
							}
							else {
								$selected = "";
							}

							echo "
							<option value='$valor' $selected>$label</option>";
						}
					}
				?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>Formato</td>
			<td></td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr  align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<select name="formato" id="formato" class="frm">
					<option value="tela">Tela</option>
					<option value="xls">Excel</option>
				</select>
			</td>
			<td>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr >
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr >
			<td colspan="4" align="center" style='padding:10px 0 10px 0;'><input type='button' value='Pesquisar' onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
		</tr>
	</table>
	</form>

<?
}
if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	$sql = "SELECT pedir_defeito_reclamado_descricao FROM tbl_fabrica WHERE fabrica=$login_fabrica";
	$res = pg_query($con, $sql);
	$pedir_defeito_reclamado_descricao = pg_result($res, 0, pedir_defeito_reclamado_descricao);

	if ($pedir_defeito_reclamado_descricao == 't') {
		$campo_defeito_reclamado = "tbl_os.defeito_reclamado_descricao";
	}
	else {
		$campo_defeito_reclamado = "tbl_os.defeito_reclamado";
	}

	switch($status) {
		case "aprovacao":
			$statusWhere = "AND tbl_os_auditar.liberado_data IS NULL AND tbl_os_auditar.cancelada_data IS NULL AND liberado IS FALSE AND tbl_os_auditar.cancelada IS FALSE";
		break;

		case "aprovadas":
			$statusWhere = "AND tbl_os_auditar.liberado_data IS NOT NULL AND liberado IS TRUE";
			$periodoWhere = "AND tbl_os_auditar.data BETWEEN '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp AND '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '1 MONTH' AND tbl_os.data_digitacao >= '2010-11-01 00:00:00'::timestamp";
		break;

		case "reprovadas":
			$statusWhere = "AND tbl_os_auditar.cancelada_data IS NOT NULL AND tbl_os_auditar.cancelada IS TRUE";
			$periodoWhere = "AND tbl_os_auditar.data BETWEEN '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp AND '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '1 MONTH' AND tbl_os.data_digitacao >= '2010-11-01 00:00:00'::timestamp";
		break;

		case "todas":
			$periodoWhere = "AND tbl_os_auditar.data BETWEEN '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp AND '$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '1 MONTH' AND tbl_os.data_digitacao >= '2010-11-01 00:00:00'::timestamp";
		break;
	}

	if (strlen($posto)) {
		$postoWhere = "AND tbl_os.posto=$posto";
	}

	$sql = "
	SELECT DISTINCT
	tbl_os.os,
	tbl_os.sua_os,
	tbl_os.posto as posto_os,
	tbl_posto.nome as posto_nome,
	tbl_os.consumidor_nome,
	tbl_produto.descricao AS produto_nome,
	tbl_os.serie,
	tbl_servico_realizado.descricao AS servico_realizado,
	$campo_defeito_reclamado AS defeito_reclamado,
	tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
	ROUND(tbl_os.mao_de_obra::numeric, 2) AS mao_de_obra,
	ROUND(tbl_os.pecas::numeric, 2) AS pecas,
	tbl_os.obs

	FROM
	tbl_os_auditar
	JOIN tbl_os ON tbl_os_auditar.os=tbl_os.os
	JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
	JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto
	JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
	LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado  = tbl_os.solucao_os
	LEFT JOIN tbl_admin ON tbl_os_auditar.admin=tbl_admin.admin
	
	WHERE
	tbl_os.fabrica=$login_fabrica
	/*
		Não acrescentar o campo tbl_os_auditar.fabrica nessa condição, pois co campo não está preenchido para a fábrica 3
	*/
	
	$postoWhere
	$statusWhere
	$periodoWhere

	ORDER BY
	tbl_os.os
	";
	$res = pg_query($con, $sql);
	$numero_registros = pg_num_rows($res);
	$colunas = 12;
	if($numero_registros > 0){
		$cabecalho = "
				<tr class='titulo_coluna'>
				<th>Posto</th>
				<th>Nome Posto</th>
				<th>OS</th>
				<th>Cliente</th>
				<th>Produto</th>
				<th>Série</th>
				<th>Defeito</th>
				<th>M.O.</th>
				<th>Subproduto</th>
				<th>Posição</th>
				<th>Componente</th>
				<th>Serviço</th>
			</tr>";
		
		if ($login_fabrica == 14){ #HD 303819
		
		$cabecalho2 = "
				<tr class='titulo_coluna'>
				<th>Posto</th>
				<th>Nome Posto</th>
				<th>OS</th>
				<th >Cliente</th>
				<th>Produto</th>
				<th>Série</th>
				<th>Defeito</th>
				<th>M.O.</th>
				<th>Subproduto</th>
				<th>Posição</th>
				<th>Componente</th>
				<th>Solução</th>
			</tr>";
		}
		echo "<br>
		<table class='tabela' id='tabela_principal' cellspacing='1' width='100%' align='center'>"; #HD 303819

		$total = 0;
		for ($i = 0; $i < pg_num_rows($res); $i++) {
			//Recupera os valores do resultado da consulta
			$valores_linha = pg_fetch_array($res, $i);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha);

			if ($pedir_defeito_reclamado_descricao == 't') {
			}
			else {
				$defeito_reclamado = pg_result($res,$i,defeito_reclamado);
				if (strlen($defeito_reclamado)>0) {
					$sqlDefeito = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado=$defeito_reclamado";
					$res_defeito = pg_query($con, $sqlDefeito);
					$defeito_reclamado = pg_result($res_defeito, 0, descricao);
				}
			}

			
			
			if($i % 2 == 0)
				$cor = "#F7F5F0";
			else
				$cor = "#F1F4FA";
			

			#HD 303819 
			$columns = "
			
			<tr bgcolor='$cor'>
				<td style='$estilo_dados_os;'>
					$posto_os
				</td>
				
				<td style='$estilo_dados_os;'>
					<div>
						<acronym title='Nome Posto: $posto_nome'>
							$posto_nome
						</acronym>
					</div>
				</td>
				
				<td style='$estilo_dados_os;'>
					<a href='os_press.php?os=$os' target='_blank'>
						$sua_os
					</a>
				</td>
				
				<td style='$estilo_dados_os;'>
					<div>
						<acronym title='Cliente: $consumidor_nome'>
							$consumidor_nome
						<acronym>
					</div>
				</td>
				
				<td style='$estilo_dados_os;'>
					<div>
						<acronym title='Produto: $produto_nome'>
							$produto_nome
						<acronym>
					</div>
				</td>
				
				<td style='$estilo_dados_os;'>$serie</td>
				
				<td style='$estilo_dados_os;'>
					<div>
						<acronym title='Defeito: $defeito_constatado_descricao'>
							$defeito_constatado_descricao
						</acronym>
					</div>
				</td>
				
				<td style='$estilo_dados_os;'>$mao_de_obra</td>";
			
			$sql = "
			SELECT
			tbl_produto.descricao AS subconjunto_descricao,
			tbl_os_item.posicao,
			tbl_peca.referencia AS peca_referencia,
			tbl_peca.descricao AS peca_descricao,
			tbl_defeito.descricao AS defeito_descricao,
			tbl_servico_realizado.descricao AS servico_descricao

			FROM
			tbl_os_produto
			JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
			JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
			JOIN tbl_defeito ON tbl_os_item.defeito=tbl_defeito.defeito
			JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
			JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado

			WHERE
			tbl_os_produto.os=$os
			";
			$res_itens = pg_query($con, $sql);
			
			if (pg_num_rows($res_itens)) {
				
				if ($i % 2 == 0) {
					echo $cabecalho;
				}
				
				echo $columns;
			
				for($j = 0; $j < pg_num_rows($res_itens); $j++) {
					if ($j != 0) {
						echo "<tr>";
						
						if ($login_fabrica == 14){
						echo "<td colspan='8'></td>";
						}else{
						echo "<td colspan='6'></td>";
						}
					}

					//Recupera os valores do resultado da consulta
					$valores_linha_itens = pg_fetch_array($res_itens, $j);

					//Transforma os resultados recuperados de array para variáveis
					extract($valores_linha_itens);

					echo "
				<td>
					<div>
						<acronym title='Sub-Produto: $subconjunto_descricao'>
							$subconjunto_descricao
						</acronym>
					</div>
				</td>
				<td>
					<div style='width:60px'>
						<acronym title='Posição: $posicao'>
							$posicao
						</acronym>
					</div>
				</td>
				
				<td align='center'>
					<div>
						<acronym title='REFERÊNCIA: $peca_referencia'>
							$peca_descricao
						</acronym>
					</div>
				</td>
				
				<td>
					<div>
						<acronym title='Serviço: $servico_descricao'>
							$servico_descricao
						</acronym>
					</div>
				</td>
					";
				}

				echo "
			</tr>";
			}
			else {

				if ($i % 2 == 0 && $_POST["formato"] != "xls") {
					
					if ($login_fabrica == 14){
					echo $cabecalho2; #HD 303819
					}else{
						echo $cabecalho;
					}
				}
				echo $columns;
				?>
				<td colspan='3' align='left'>Não existem ITENS lançados para esta Ordem de Serviço</td>							
				<td><div><acronym title='Solução: $servico_realizado'><? echo "$servico_realizado" ?><acronym></div></td>
				</tr>
				<?
			}

			//86400 segundos = 1 dia
			switch($login_fabrica) {
				case 14:
					$dias_auditoria = "INTERVAL '3 DAY' +
				86400*CASE EXTRACT(dow FROM data)
							WHEN 0 THEN '1' /* Dom */
							WHEN 1 THEN '0' /* Seg */
							WHEN 2 THEN '0' /* Ter */
							WHEN 3 THEN '2' /* Qua */
							WHEN 4 THEN '2' /* Qui */
							WHEN 5 THEN '2' /* Sex */
							WHEN 6 THEN '2' /* Sab */
					  END::interval";
				break;

				case 43:
					$dias_auditoria = "INTERVAL '1 DAY' +
				86400*CASE EXTRACT(dow FROM data)
							WHEN 0 THEN '1' /* Dom */
							WHEN 1 THEN '0' /* Seg */
							WHEN 2 THEN '0' /* Ter */
							WHEN 3 THEN '0' /* Qua */
							WHEN 4 THEN '0' /* Qui */
							WHEN 5 THEN '2' /* Sex */
							WHEN 6 THEN '2' /* Sab */
					  END::interval";
				break;

				default:
					$dias_auditoria = "INTERVAL '3 DAY' +
				86400*CASE EXTRACT(dow FROM data)
							WHEN 0 THEN '1' /* Dom */
							WHEN 1 THEN '0' /* Seg */
							WHEN 2 THEN '0' /* Ter */
							WHEN 3 THEN '2' /* Qua */
							WHEN 4 THEN '2' /* Qui */
							WHEN 5 THEN '2' /* Sex */
							WHEN 6 THEN '2' /* Sab */
					  END::interval";
			}

			$sql = "
			SELECT
			os_auditar,
			TO_CHAR(tbl_os_auditar.data, 'DD/mm/YYYY') AS data_entrada_auditoria,
			TO_CHAR(tbl_os_auditar.data + $dias_auditoria, 'DD/mm/YYYY') AS data_saida_auditoria,
			tbl_os_auditar.descricao,
			tbl_os_auditar.liberado,
			TO_CHAR(tbl_os_auditar.liberado_data, 'DD/mm/YYYY') AS liberado_data,
			tbl_os_auditar.cancelada,
			TO_CHAR(tbl_os_auditar.cancelada_data, 'DD/mm/YYYY') AS cancelada_data,
			tbl_os_auditar.justificativa,
			tbl_os_auditar.admin,
			tbl_admin.nome_completo AS admin_nome_completo,
			tbl_admin.login AS admin_login

			FROM
			tbl_os_auditar
			LEFT JOIN tbl_admin ON tbl_os_auditar.admin=tbl_admin.admin
			
			WHERE tbl_os_auditar.os=$os
			/*
				Não acrescentar o campo tbl_os_auditar.fabrica nessa condição, pois co campo não está preenchido para a fábrica 3
			*/
			
			

			ORDER BY
			tbl_os_auditar.os_auditar DESC
			";
			$res_auditorias = pg_query($con, $sql);

			for ($j = 0; $j < pg_num_rows($res_auditorias); $j++) {
				//Recupera os valores do resultado da consulta
				$valores_linha_itens = pg_fetch_array($res_auditorias, $j);

				//Transforma os resultados recuperados de array para variáveis
				extract($valores_linha_itens);

				if ($j == pg_num_rows($res_auditorias) - 1) {
					$estilo_ultima_auditoria = "border-bottom: 1px solid #444444;";
				}
				else {
					$estilo_ultima_auditoria = "";
				}

				if ($liberado == 't') {
					if (strlen($admin) == 0) {
						$admin_nome_completo = "SISTEMA";
						$admin_login = "sistema";
						$estilo = $estilo_aprovar_sistema;
					}
					else {
						$estilo = $estilo_aprovar;
					}

					if ($_POST["formato"] == "xls") {
						$estilo_td = "$estilo_ultima_auditoria $estilo_aprovar";
					}
					else {
						$estilo_td = "$estilo_ultima_auditoria";
					}

					echo "
				<tr>

					<td colspan=$colunas align='left' style='$estilo_td'>
					<span style='$estilo'>
					<b>Período:</b> $data_entrada_auditoria à $data_saida_auditoria - <b>Situação ($liberado_data):</b> APROVADA - <b>Responsável:</b> $admin_nome_completo ($admin_login)
					</span>
					</td>
				</tr>
					";
				}
				elseif ($cancelada == 't') {
					if ($_POST["formato"] == "xls") {
						$estilo_td = "$estilo_ultima_auditoria $estilo_reprovar";
					}
					else {
						$estilo_td = "$estilo_ultima_auditoria";
					}

					echo "
				<tr>
					<td colspan=$colunas align='left' style='$estilo_td'>
					<span id='acoes_$os' style='$estilo_reprovar'>
					<b>Período:</b> $data_entrada_auditoria à $data_saida_auditoria - <b>Situação ($cancelada_data):</b> REPROVADA - <b>Responsável:</b> $admin_nome_completo ($admin_login) - <b>Justificativa:</b> $justificativa
					</span>
					</td>
				</tr>
					";
				}
				else {
					echo "
				<tr>
					<td colspan=$colunas align='left' style='$estilo_ultima_auditoria'>
					<span id='acoes_$os'>
						<b>Período:</b> $data_entrada_auditoria à $data_saida_auditoria";
					
					if ($_POST["formato"] == "xls") {
					}
					else {
						echo "
						<input id='btn_aprovar' name='btn_aprovar' class='frm ac_input' style='$estilo_aprovar' type=button value='Aprovar' onclick=\"acao_auditoria_os('$os', '$os_auditar', 'aprovar', 'APROVAR');\">
						<div style='display:none' id='justificativadiv_$os'>Justificativa: <input id='justificativa_$os' name='justificativa_$os' type='text' class='frm ac_input' style='$estilo_reprovar;' size='30' maxlength='200'></div>
						<input id='btn_reprovar' name='btn_reprovar' class='frm ac_input' style='$estilo_reprovar' type=button value='Reprovar' onclick=\"acao_auditoria_os('$os', '$os_auditar', 'reprovar', 'REPROVAR');\">";
				//Esta rotina foi criada por solicitação da Intelbras, no entanto a mesma não faz recompra de peças, portanto não precisa aprovar separadamente mão de obra ou peças, apenas aprova, reprova ou exclui a OS. Deixei os botões caso precise futuramente
		/*		echo "
						<input id='btn_aprovar_mao_de_obra' name='btn_aprovar_mao_de_obra' class='frm ac_input' style='$estilo_aprovar_mao_de_obra' type=button value='Aprovar Mão de Obra'>
						<input id='btn_aprovar_pecas' name='btn_aprovar_pecas' class='frm ac_input' style='$estilo_aprovar_pecas' type=button value='Aprovar Peças'>";
				echo "
						<input id='btn_excluir_os' name='btn_excluir_os' class='frm ac_input' style='$estilo_excluir_os' type=button value='Excluir OS' onclick=\"acao_auditoria_os('$os', '$os_auditar', 'excluir_os', 'EXCLUIR');\">";*/
					}

					echo "
					</span>
					</td>
				</tr>";

				}
			}
		}

		if ($numero_registros) {
			echo "
			</table>";
		}
	}
	else {
		echo "<br><center>Não foram encontrados resultados para esta pesquisa</center><br><br>";
	}
}
echo "<br>";

if ($_POST["formato"] == "xls" && strlen($msg_erro) == 0) {

        //Redireciona a saida da tela, que estava em buffer, para a variÃÂ¡vel
        $hora = time();
        $xls = "xls/os_auditar_posto_".$login_admin."_data_".$hora.".xls";

        $saida = ob_get_clean();

        $arquivo = fopen($xls, "w");
        fwrite($arquivo, $saida);
        fclose($arquivo);

        header("location:$xls");
		die;
}
else {
	include "rodape.php";
}
?>
