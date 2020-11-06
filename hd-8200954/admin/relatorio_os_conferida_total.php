<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="callcenter";
$layout_menu = 'callcenter';
include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$title = "Totalização de OS Conferidas por Linha";

include "cabecalho.php";

$estilo_dados = "";
$estilo_aprovar = "background: #44FF44;";
$estilo_reprovar = "background: #FFAA99;";

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Erro {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #FF0000;
}
.Mensagem {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #007700;
}
.Total {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #DDEEEE;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
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
";

?>

$(function()
{
	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");
});

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

function acao_autorizacao_postagem(hd_chamado) {
	status_pos = $(("#status_"+hd_chamado)).val();
	motivo = $("#motivo_"+hd_chamado).val();
	obs = $("#obs_"+hd_chamado).val();

	mensagem = status_pos + " a postagem para o chamado " + hd_chamado + "?";
	
	if (confirm(mensagem)) {
		$("#status_"+hd_chamado).attr("disabled", true);
		$("#motivo_"+hd_chamado).attr("disabled", true);
		$("#obs_"+hd_chamado).attr("disabled", true);
		$("#gravar_"+hd_chamado).attr("disabled", true);

		url = "hd_chamado_postagem_ajax.php?acao=gravar&hd_chamado="+hd_chamado+"&status="+status_pos+"&motivo="+motivo+"&obs="+obs;
		requisicaoHTTP("GET", url, true , "acao_autorizacao_postagem_retorno", hd_chamado);
	}
}

function acao_autorizacao_postagem_retorno(retorno, hd_chamado) {
	retorno = retorno.split('|');
	acao = retorno[0];
	status_pos = retorno[1];
	mensagem = retorno[2];

	switch(status_pos) {
		case "sucesso":
			status_pos = $("#status_"+hd_chamado).val();

			if (status_pos == "Aprovar") {
				$("#status_td_"+hd_chamado).html("Aprovado");
				$("#status_td_"+hd_chamado).attr("style", estilo_aprovar);
			}
			else if (status_pos == "Reprovar") {
				$("#status_td_"+hd_chamado).html("Reprovado");
				$("#status_td_"+hd_chamado).attr("style", estilo_reprovar);
			}
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
		$("#periodo_quinzena").attr("disabled", true);
	}
	else {
		$("#periodo").attr("disabled", false);
		$("#periodo_quinzena").attr("disabled", false);
	}
}

</script>

<? if (strlen($msg_erro) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Mensagem">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table>
<? } ?>

	<br>

	<form name="frm_busca" method="post" action="<?=$PHP_SELF?>">
	<input type="hidden" name="acao">
	<table width="400" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Titulo">
			<td colspan="4" background='imagens_admin/azul.gif' height='25'>PESQUISA</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
				
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">Este relatório irá buscar todos os meses que possuem OS não conferidas. O relatório faz a busca de extratos gerados a partir de março/2010. Meses que já tiveram todas as OS coferidas não entrarão na pesquisa</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
		</tr>
	</table>
	</form>

<?

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	//Estabelecendo limites dos meses do relatório
	$sql = "
	SELECT
	MIN(tbl_extrato.extrato)

	FROM
	tbl_extrato
	LEFT JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato

	WHERE
	tbl_extrato_conferencia.caixa IS NULL
	";
	$res = pg_query($con, $sql);
	$numero_registros = pg_num_rows($res);
	$colunas = 8;

	$cabecalho = "
		<tr>
			<th width='100'>Data</th>
			<th width='60'>Chamado</th>
			<th width='60'>Status</th>
			<th width='200'>Admin</th>
			<th width='180'>Motivo</th>
			<th>Observação</th>
			<th width='150'>Código Postagem</th>
			<th width='100'>Ação</th>
		</tr>";
	
	echo "
	<table class='tablesorter' id='tabela_principal'>
	$cabecalho
	<tbody>";

	$total = 0;
	for ($i = 0; $i < pg_num_rows($res); $i++) {
		//Recupera os valores do resultado da consulta
		$valores_linha = pg_fetch_array($res, $i);

		//Transforma os resultados recuperados de array para variáveis
		extract($valores_linha);

		if ($i % 10 == 0 && $i != 0) {
			echo $cabecalho;
		}

		switch($aprovado) {
			case "t":
				$estilo = $estilo_aprovar;
				$status .= " em $data_aprovacao";
			break;

			case "f":
				$estilo = $estilo_reprovar;
				$status .= " em $data_aprovacao";
			break;
			
			default:
				$status = "<select name='status_$hd_chamado' id='status_$hd_chamado' class='frm'>";
				$status .= "<option value='Aprovar'>Aprovar</option>";
				$status .= "<option value='Reprovar'>Reprovar</option>";
				$status .= "</select>";

				$motivos = array("Outro", "Trocar Produto", "Ressarcir Cliente", "Pedido Invalido", "Chamado Cancelado");
				$motivo = "<select name='motivo_$hd_chamado' id='motivo_$hd_chamado' class='frm'>";
				foreach($motivos as $indice => $valor) {
					$motivo .= "<option value='$valor'>$valor</option>";
				}
				$motivo .= "</select>";

				$obs = "<input type='text' name='obs_$hd_chamado' id='obs_$hd_chamado' style='width:100%;' class='frm'  onkeyup='somenteMaiusculaSemAcento(this);'>";

				$acoes = "<input type='button' name='gravar_$hd_chamado' id='gravar_$hd_chamado' value='Gravar' onclick='acao_autorizacao_postagem($hd_chamado)' class='frm'>";

				$estilo = "";
		}

		echo "
		<tr>
			<td style='$estilo_dados'>$data</td>
			<td style='$estilo_dados'><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank' title='Clique para visualizar o chamado em outra janela'>$hd_chamado</a></td>
			<td style='$estilo' id='status_td_$hd_chamado' nowrap>$status</td>
			<td style='$estilo_dados'>$admin_nome_completo</td>
			<td style='$estilo_dados'>$motivo</td>
			<td style='$estilo_dados'>$obs</td>
			<td style='$estilo_dados'>$codigo_postagem</td>
			<td style='$estilo_dados'>$acoes</td>
		</tr>";

		$sql = "
		SELECT
		tbl_hd_chamado_extra.nome AS consumidor_nome,
		tbl_hd_chamado_extra.reclamado,
		tbl_hd_chamado_extra.revenda_cnpj AS hd_chamado_revenda_cnpj,
		tbl_hd_chamado_extra.revenda_nome,
		tbl_cidade.nome AS cidade_nome,
		tbl_cidade.estado AS cidade_estado,
		tbl_produto.referencia AS produto_referencia,
		tbl_produto.descricao AS produto_descricao,
		CASE
			WHEN tbl_hd_chamado_extra.defeito_reclamado IS NULL THEN
				tbl_hd_chamado_extra.defeito_reclamado_descricao
			ELSE
				(SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado=tbl_hd_chamado_extra.defeito_reclamado)
		END AS defeito_reclamado_descricao,
		tbl_admin.nome_completo

		FROM
		tbl_hd_chamado
		JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
		JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
		JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto
		JOIN tbl_admin ON tbl_hd_chamado.atendente=tbl_admin.admin

		WHERE
		tbl_hd_chamado_extra.hd_chamado=$hd_chamado
		";
		$res_itens = pg_query($con, $sql);

		if (pg_num_rows($res_itens)) {
			//Recupera os valores do resultado da consulta
			$valores_linha_itens = pg_fetch_array($res_itens, $j);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha_itens);

			echo "
		<tr>
			<td colspan=$colunas>
				<b>Consumidor:</b> $consumidor_nome - $cidade_nome-$cidade_estado - <b>Produto:</b> $produto_referencia - $produto_descricao<br>
				<b>Revenda:</b> $hd_chamado_revenda_cnpj - $revenda_nome<br>
				<b>Defeito:</b> $defeito_reclamado_descricao - $reclamado
			</td>
		</tr>";
		}
	}

	if ($numero_registros) {
		echo "
		<tr>
			<td colspan=$colunas align='center'>
				<b>Quantidade de registros listados: $numero_registros</b>
			</td>
		</tr>
	</tbody>
	</table>";
	}
	else {
		echo "<br><FONT size='2' COLOR=\"#FF3333\"><B>Não encontrado!</B></FONT><br><br>";
	}
}
echo "<br>";


include "rodape.php";
?>
