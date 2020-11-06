<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

$admin_privilegios="gerencia";
$layout_menu = 'gerencia';

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$arquivo = trim($_POST['arquivo']);
if (strlen($arquivo) == 0) $arquivo = trim($_GET["arquivo"]);

$arquivo_descricao = trim($_POST['arquivo_descricao']);
if (strlen($arquivo_descricao) == 0) $arquivo_descricao = trim($_GET["arquivo_descricao"]);

$interacao = trim($_POST['interacao']);
if (strlen($interacao) == 0) $interacao = trim($_GET["interacao"]);

$data_inicial = trim($_POST['data_inicial']);
if (strlen($data_inicial) == 0) $data_inicial = trim($_GET["data_inicial"]);

$data_final = trim($_POST['data_final']);
if (strlen($data_final) == 0) $data_final = trim($_GET["data_final"]);

$admin = trim($_POST['admin']);
if (strlen($admin) == 0) $admin = trim($_GET["admin"]);

$hd_chamado = trim($_POST['hd_chamado']);
if (strlen($hd_chamado) == 0) $hd_chamado = trim($_GET["hd_chamado"]);

$somente_resolvido = trim($_POST['somente_resolvido']);
if (strlen($somente_resolvido) == 0) $somente_resolvido = trim($_GET["somente_resolvido"]);

$melhoria_status = trim($_POST['melhoria_status']);
if (strlen($melhoria_status) == 0) $melhoria_status = trim($_GET["melhoria_status"]);

if (strlen($arquivo_descricao) == 0 && strlen($arquivo) > 0) {
	$arquivo = "";
}

if (strlen($arquivo) > 0) {
	$arquivo = intval($arquivo);
	$sql = "
	SELECT
	arquivo

	FROM
	tbl_arquivo

	WHERE
	arquivo=$arquivo
	AND descricao='$arquivo_descricao'
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		$msg_erro = "Arquivo $arquivo_descricao não encontrado";
		$arquivo = "";
		$arquivo_descricao = "";
	}
}

if (strlen($admin) > 0) {
	$admin = intval($admin);
	$sql = "
	SELECT
	admin

	FROM
	tbl_admin

	WHERE
	admin=$admin
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) == 0) {
		$msg_erro = "Admin $admin não encontrado";
	}
}

if (strlen($data_inicial)) {
	$xdata_inicial = implode("-", array_reverse(explode("/", $data_inicial)));
	$sql = "SELECT '$xdata_inicial'::date";
	@$res = pg_query($con, $sql);
	if (pg_errormessage($con)) {
		$msg_erro = "Data INICIAL em inválida: $data_inicial";
	}
}

if (strlen($data_final)) {
	$xdata_final = implode("-", array_reverse(explode("/", $data_final)));
	$sql = "SELECT '$xdata_final'::date";
	@$res = pg_query($con, $sql);
	if (pg_errormessage($con)) {
		$msg_erro = "Data FINAL em inválida: $data_final";
	}
}

if (strlen($msg_erro) == 0 && strlen($data_inicial) && strlen($data_final)) {
	$sql = "SELECT '$xdata_final'::date - '$xdata_inicial'::date AS periodo";
	$res = pg_query($con, $sql);
	$periodo = intval(pg_result($res, 0, periodo));

	if ($periodo > 180) {
		$msg_erro = "Período de busca entre data inicial e data final maior que 180 dias. Pesquisa não permitida";
	}
	elseif ($periodo < 0) {
		$msg_erro = "Data INICIAL MAIOR que a data FINAL";
	}
}

if (strlen($arquivo_descricao) > 0 && strlen($arquivo) == 0) {
	$msg_erro = "Para selecionar um arquivo, digite parte do nome no campo e selecione um item da listagem que irá aparecer";
	$arquivo_descricao = "";
}

if (strlen($hd_chamado)) {
	$sql = "
	SELECT
	hd_chamado

	FROM
	tbl_hd_chamado

	WHERE
	hd_chamado = $hd_chamado
	AND fabrica_responsavel = 10
	";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Chamado não localizado.<br>São permitidos apenas chamados de suporte.<br>Chamados de CallCenter não são aceitos";
	}
}

$title = "Melhorias em Programas através de Intervenção em Chamados";

include "menu.php";
?>

<style type="text/css">
.admin_nome_completo_css {
	font-weight: bold;
}

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
.frm {
        BORDER-RIGHT: #888888 1px solid; BORDER-TOP: #888888 1px solid; FONT-WEIGHT: bold; FONT-SIZE: 8pt; BORDER-LEFT: #888888 1px solid; BORDER-BOTTOM: #888888 1px solid; FONT-FAMILY: Verdana; BACKGROUND-COLOR: #f0f0f0
}
</style>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="../js/jquery.js"></script>
<script language="javascript" src="js_admin/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js_admin/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js_admin/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js_admin/dimensions.js'></script>
<script type='text/javascript' src='js_admin/bibliotecaAJAX.js'></script>
<script type="text/javascript" src="js_admin/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js_admin/jquery.tablesorter.pager.js"></script>

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
		widgets: ['zebra','repeatHeaders']
	});

});


$(function()
{
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");
});

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca do arquivo (programa) */
	$("#arquivo_descricao").autocomplete("hd_chamado_melhoria_ajax.php?acao=arquivo", {
		minChars: 3,
		delay: 450,
		width: 450,
		matchContains: true,
		formatItem: function(row) {return row[1];},
		formatResult: function(row) {return row[1];}
	});

	$("#arquivo_descricao").result(function(event, data, formatted) {
		$("#arquivo").val(data[0]) ;
	});

	/* Novo arquivo (programa) */
	$("#arquivo_descricao_novo").autocomplete("hd_chamado_melhoria_ajax.php?acao=arquivo", {
		minChars: 3,
		delay: 450,
		width: 450,
		matchContains: true,
		formatItem: function(row) {return row[1];},
		formatResult: function(row) {return row[1];}
	});

	$("#arquivo_descricao_novo").result(function(event, data, formatted) {
		$("#arquivo_novo").val(data[0]) ;
	});

});

function atualizarJustificativa(resposta) {
	partes = resposta.split("|");
	if ($("#justificativa_" + partes[0]).html() == "") {
		$("#justificativa_" + partes[0]).html($("#justificativa_" + partes[0]).html() + partes[1]);
	}
	else {
		$("#justificativa_" + partes[0]).html($("#justificativa_" + partes[0]).html() + "<br>" + partes[1]);
	}

	$("#justificativa_nova_" + partes[0]).val("");
}

function atualizarValidacao(resposta) {
	partes = resposta.split("|");
	$("#validacao_" + partes[0]).html(partes[2]);
}

function gravarJustificativa(hd_chamado_melhoria, justificativa) {
	if (justificativa == "") {
		alert("Preencha a justificativa");
		return false;
	}
	else {
		$("#indicadorajax").html("<img src='js/indicator.gif'>");
		url = "hd_chamado_melhoria_ajax.php?hd_chamado_melhoria=" + hd_chamado_melhoria + "&justificativa=" + justificativa + "&acao=Gravar Justificativa";
		requisicaoHTTP("GET", url, true, "gravarJustificativaResposta");
	}
}

function gravarJustificativaResposta(resposta) {
	$("#indicadorajax").html("");
	if (resposta == "" || resposta == "falha") {
		alert("Falha ao gravar a justificativa");
	}
	else {
		atualizarJustificativa(resposta);
		alert("Justificativa gravada com sucesso!");
	}
}

function cancelarMelhoria(hd_chamado_melhoria) {
	if (hd_chamado_melhoria == "" || typeof hd_chamado_melhoria == "undefined") {
		alert("Melhoria não informada");
		return false;
	}
	else {
		$("#indicadorajaxcancelar").html("<img src='js/indicator.gif'>");
		url = "hd_chamado_melhoria_ajax.php?hd_chamado_melhoria=" + hd_chamado_melhoria + "&acao=Cancelar";
		requisicaoHTTP("GET", url, true, "cancelarMelhoriaResposta");
	}
}

function cancelarMelhoriaResposta(resposta) {
	$("#indicadorajaxcancelar").html("");
	if (resposta == "" || resposta == "falha") {
		alert("Falha ao cancelar a melhoria\nSó pode cancelar a justificativa o usuário que a cadastrou e se esta ainda não está associada a nenhum chamado");
	}
	else {
		atualizarJustificativa(resposta);
		atualizarValidacao(resposta);
		alert("Melhoria cancelada com sucesso!");
	}
}

function validarMelhoria(hd_chamado_melhoria) {
	if (hd_chamado_melhoria == "" || typeof hd_chamado_melhoria == "undefined") {
		alert("Melhoria não informada");
		return false;
	}
	else {
		$("#indicadorajaxvalidar").html("<img src='js/indicator.gif'>");
		url = "hd_chamado_melhoria_ajax.php?hd_chamado_melhoria=" + hd_chamado_melhoria + "&acao=Validar";
		requisicaoHTTP("GET", url, true, "validarMelhoriaResposta");
	}
}

function validarMelhoriaResposta(resposta) {
	$("#indicadorajaxvalidar").html("");
	if (resposta == "" || resposta == "falha") {
		alert("Falha ao validar a melhoria\nSó pode validar a justificativa o usuário que a cadastrou e se esta estiver associada a um chamado Resolvido");
	}
	else {
		atualizarJustificativa(resposta);
		atualizarValidacao(resposta);
		alert("Melhoria validada com sucesso!");
	}
}

</script>

<?

if(strlen($produto_referencia)) {
	$sql="SELECT produto FROM tbl_produto JOIN tbl_linha using (linha) WHERE referencia = '$produto_referencia' and fabrica=$login_fabrica";
	$res = pg_exec($con,$sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Produto $produto_referencia não encontrado";
	}
	else {
		$produto = pg_result($res,0,produto);
	}
}

if (strlen($revenda_cnpj)) {
	$revenda_cnpj = preg_replace( '/[^0-9]+/', '', $revenda_cnpj);
	if(strlen($revenda_cnpj) == 8) {
		$cond_revenda_cnpj = " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%'";
	}
	else {
		$msg_erro = "CNPJ da Revenda digitado inválido";
	}
}


if ($acao == "GRAVAR" && strlen($msg_erro) == 0) {
	if ($arquivo == "" || $arquivo_descricao == "" || $interacao == "") {
		$msg_erro = "Todos os campos são obrigatórios para GRAVAR.<br>O Programa (arquivo) deve ser selecionado da lista que aparece ao digitar parte do nome do arquivo";
	}
	else {
		$sql = "BEGIN";
		$res = pg_query($sql);
	
		$sql = "
		INSERT INTO
		tbl_hd_chamado_melhoria (
			arquivo,
			admin,
			interacao
		)
		VALUES (
			$arquivo,
			$login_admin,
			'$interacao'
		)
		";
		$res = pg_query($con, $sql);

		if (pg_errormessage($con)) {
			$sql = "ROLLBACK";
			$res = pg_query($con, $sql);
		}
		else {
			echo "
			<script language=javascript>
			document.location = '" . $PHP_SELF . "?revenda_cnpj=$revenda_cnpj&produto=$produto&msg_sucesso=Melhoria GRAVADA com sucesso!<br>Esta melhoria ficará aguardando o próximo chamado envolvendo este programa para que seja analisada e executada.';
			</script>";
			$sql = "COMMIT";
			$res = pg_query($con, $sql);
			die;
		}
	}

	$acao = "PESQUISAR";
}
elseif ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	if ($arquivo == "" && $data_inicial == "" && $data_final == "" && $admin == "" && $hd_chamado == "") {
		$msg_erro = "Para PESQUISAR selecione pelo menos uma opção de busca";
	}
	elseif (strlen($data_inicial) != 10 && strlen($data_inicial) > 0) {
		$msg_erro = "Data inicial em formato inválido: $data_inicial";
	}
	elseif (strlen($data_final) != 10 && strlen($data_final) > 0) {
		$msg_erro = "Data final em formato inválido: $data_inicial";
	}
	elseif ((strlen($data_inicial) == 10 && strlen($data_final) == 0) ||
			(strlen($data_inicial) == 0 && strlen($data_final) == 10)) {
		$msg_erro = "Para busca por data, preencha as datas INICIAL E FINAL";
	}
}

?>

	<table width="100%" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Titulo">
			<td colspan="5" background='imagens_admin/azul.gif' height='25'>Melhorias em Programas Através de Intervenção em Chamados</td>
		</tr>
	</table>

	<br>

<? if (strlen($msg_erro) > 0) { ?>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
	<br>
<? } ?>

<? if (strlen($_GET["msg_sucesso"]) > 0) { ?>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Mensagem">
			<td colspan="4" height='25'><? echo $_GET["msg_sucesso"]; ?></td>
		</tr>
	</table>
	<br>
<? } ?>

	<form name="frm_busca" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="acao">
	<table width="400" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Titulo">
			<td colspan="5" background='imagens_admin/azul.gif' height='25'>PESQUISA</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="5">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan='3'>Programa (arquivo)</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td align='left' colspan='3' nowrap>
				<input class="frm" style="width:380px;" type="text" name="arquivo_descricao" id="arquivo_descricao" value="<? echo $arquivo_descricao; ?>" size="50" maxlength="1000">
				<input type="hidden" name="arquivo" id="arquivo" value="<? echo $arquivo ?>">
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align=left>
			<td width="10">&nbsp;</td>
			<td>Data Inicial</td>
			<td>Data Final</td>
			<td>Admin</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align=left nowrap>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="data_inicial" id="data_inicial" style="width:85px;" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); ?>" class="frm">
			</td>
			<td>
				<input type="text" name="data_final" id="data_final" style="width:85px;" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); ?>" class="frm">
			</td>
			<td>
				<select class="frm" name="admin" id="admin" style="width:140px;">
					<option value=""></option>";
				<?
				$sql = "
				SELECT
				DISTINCT
				tbl_admin.admin,
				tbl_admin.nome_completo

				FROM
				tbl_hd_chamado_melhoria
				JOIN tbl_admin ON tbl_hd_chamado_melhoria.admin=tbl_admin.admin
				";
				$res = pg_query($con, $sql);

				for($i = 0; $i < pg_num_rows($res); $i++) {
					$admin_lista = pg_result($res, $i, admin);
					$nome_completo = pg_result($res, $i, nome_completo);

					if ($admin_lista == $admin) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					echo "
					<option value=$admin_lista $selected>$nome_completo</option>";
				}
				?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Chamado</td>
			<td colspan='2'></td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td align='left' colspan='1' nowrap>
				<input class="frm" style="width:85px;" type="text" name="hd_chamado" id="hd_chamado" value="<? echo $hd_chamado; ?>" maxlength="8">
			</td>
			<td colspan="2">
				<?

				if ($somente_resolvido) {
					$checked = "CHECKED";
				}
				else {
					$checked = "";
				}

				?>
				<input type="checkbox" name="somente_resolvido" id="somente_resolvido" value="1" <? echo $checked; ?>> Somente chamados resolvidos
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="3">Status da Melhoria</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="3">
			<?php

			switch($melhoria_status) {
				case "aguardando":
					$aguardando_selected = "selected";
				break;

				case "nao_validados":
					$nao_validados_selected = "selected";
				break;

				case "validados":
					$validados_selected = "selected";
				break;

				case "cancelados":
					$cancelados_selected = "selected";
				break;
			}

			?>
			<select class="frm" name="melhoria_status" id="melhoria_status">
			<option value="">Todos</option>
			<option <? echo $aguardando_selected; ?> value="aguardando">Aguardando Execução</option>
			<option <? echo $nao_validados_selected; ?> value="nao_validados">Não Validados</option>
			<option <? echo $validados_selected; ?> value="validados">Validados</option>
			<option <? echo $cancelados_selected; ?> value="cancelados">Cancelados</option>
			</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="5">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="5"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
		</tr>
	</table>
	</form>

	<br>

	<form name="frm_lancamento" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="acao">
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Titulo">
			<td colspan="4" background='imagens_admin/azul.gif' height='25'>NOVA SOLICITAÇÃO DE MELHORIA</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Programa (arquivo)</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td align='center' nowrap>
				<input class="frm" style="width:380px;" type="text" name="arquivo_descricao" id="arquivo_descricao_novo" value="<? echo $arquivo_descricao; ?>" size="50" maxlength="1000">
				<input type="hidden" name="arquivo" id="arquivo_novo" value="<? echo $arquivo ?>">
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		</tr>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Descrição</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>
			<textarea class="frm" style="width:380px;" rows=20; id="interacao" name="interacao"><? echo $interacao; ?></textarea>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="3">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="3" align="center"><img src="imagens_admin/btn_gravar.gif" onclick="javascript: document.frm_lancamento.acao.value='GRAVAR'; document.frm_lancamento.submit();" style="cursor:pointer " alt="Clique aqui para GRAVAR a solicitação"></td>
		</tr>
	</table>
	</form>

<?

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	$whereSql = array();

	if (strlen($arquivo)) {
		$whereSql[] = "tbl_hd_chamado_melhoria.arquivo=$arquivo";
	}

	if (strlen($data_inicial)) {
		$whereSql[] = "tbl_hd_chamado_melhoria.data BETWEEN '$data_inicial' AND '$data_final'";
	}

	if (strlen($admin)) {
		$whereSql[] = "tbl_hd_chamado_melhoria.admin=$admin";
	}

	if (strlen($hd_chamado)) {
		$whereSql[] = "tbl_hd_chamado_melhoria.hd_chamado=$hd_chamado";
	}

	if (strlen($somente_resolvido)) {
		$whereSql[] = "tbl_hd_chamado.status='Resolvido'";
		$fromResolvido = "JOIN tbl_hd_chamado ON tbl_hd_chamado_melhoria.hd_chamado=tbl_hd_chamado.hd_chamado";
	}

	switch($melhoria_status) {
		case "aguardando":
			$whereSql[] = "tbl_hd_chamado_melhoria.hd_chamado IS NULL AND tbl_hd_chamado_melhoria.validacao IS NULL";
		break;

		case "nao_validados":
			$whereSql[] = "tbl_hd_chamado_melhoria.hd_chamado IS NOT NULL AND tbl_hd_chamado_melhoria.validacao IS NULL";
		break;

		case "validados":
			$whereSql[] = "tbl_hd_chamado_melhoria.hd_chamado IS NOT NULL AND tbl_hd_chamado_melhoria.validacao IS NOT NULL";
		break;

		case "cancelados":
			$whereSql[] = "tbl_hd_chamado_melhoria.hd_chamado IS NULL AND tbl_hd_chamado_melhoria.validacao IS NOT NULL";
		break;
	}

	$whereSql = implode(" AND ", $whereSql);

	$sql = "
	SELECT
	tbl_hd_chamado_melhoria.hd_chamado_melhoria,
	tbl_arquivo.descricao AS arquivo_descricao,
	tbl_admin.nome_completo AS admin_nome_completo,
	tbl_hd_chamado_melhoria.admin,
	TO_CHAR(tbl_hd_chamado_melhoria.data, 'dd/mm/yyyy') AS data,
	tbl_hd_chamado_melhoria.interacao,
	tbl_hd_chamado_melhoria.justificativa,
	tbl_hd_chamado_melhoria.hd_chamado,
	TO_CHAR(validacao, 'DD/MM/YYYY HH24:MI:SS') AS validacao

	FROM
	tbl_hd_chamado_melhoria
	JOIN tbl_arquivo ON tbl_hd_chamado_melhoria.arquivo = tbl_arquivo.arquivo
	JOIN tbl_admin ON tbl_hd_chamado_melhoria.admin=tbl_admin.admin
	$fromResolvido

	WHERE
	$whereSql
	";
	
	$res = pg_query($con, $sql);
	
	if (pg_numrows($res) > 0) {
		echo "
		<table class=tablesorter>
		<thead>
			<tr>
				<th width=30>ID</th>
				<th>Arquivo</th>
				<th>Admin</th>
				<th>Data</th>
				<th width=20%>Interação</th>
				<th width=20%>Justificativa</th>
				<th>HD Chamado</th>
				<th>Validação</th>
			</tr>
		</thead>
		<tbody>";
		
		$total = 0;
		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$hd_chamado_melhoria = pg_result($res, $i, hd_chamado_melhoria);
			$arquivo_descricao = pg_result($res, $i, arquivo_descricao);
			$admin = pg_result($res, $i, admin);
			$admin_nome_completo = pg_result($res, $i, admin_nome_completo);
			$data = pg_result($res, $i, data);
			$interacao = pg_result($res, $i, interacao);
			$justificativa = pg_result($res, $i, justificativa);
			$hd_chamado = pg_result($res, $i, hd_chamado);
			$validacao = pg_result($res, $i, validacao);

			$interacao = str_replace("\n", "<br>", $interacao);
			
			if ($hd_chamado) {
				$sql = "
				SELECT
				status

				FROM
				tbl_hd_chamado

				WHERE
				hd_chamado=$hd_chamado
				";
				$res_hd_chamado = pg_query($con, $sql);
				$hd_chamado_status = pg_result($res_hd_chamado, 0, status);
			}
			else {
				$hd_chamado_status = "";
			}

			if (strpos($arquivo_descricao, "var/www")) {
				$partes = explode("/", $arquivo_descricao);
				unset($partes[0]);
				unset($partes[1]);
				unset($partes[2]);
				unset($partes[4]);
				$arquivo_link = "http://" . $_SERVER["HTTP_HOST"] . "/" . implode("/", $partes);
				$arquivo_link_abre = "<a href='$arquivo_link' target='_blank' title='Clique no link para abrir o programa em outra janela'>";
				$arquivo_link_fecha = "</a>";
			}

			if (strlen($justificativa)) {
				$justificativa = str_replace("\n", "<br>", $justificativa);
			}

			if ($validacao == "") {
				if ($hd_chamado == "") {
					if ($admin == $login_admin) {
						$validacao = "<input class=frm type='button' value='Cancelar' onclick='if (confirm(\"Cancelar esta solicitação de melhoria?\")) { cancelarMelhoria($hd_chamado_melhoria); }'><div id='indicadorajax'></div>";
					}
				}
				else {
					if ($hd_chamado_status == "Resolvido" && $admin == $login_admin) {
						$validacao = "<input class=frm type='button' value='Validar' onclick='if (confirm(\"Confirma validação da melhoria, concordando com o desenvolvimento/justificativa aplicado para a mesma?\")) { validarMelhoria($hd_chamado_melhoria); }'><div id='indicadorajaxvalidar'></div>";
					}
				}
			}

			echo  "
			<tr>
				<td>$hd_chamado_melhoria</td>
				<td>$arquivo_link_abre$arquivo_descricao$arquivo_link_fecha</td>
				<td>$admin_nome_completo</td>
				<td>$data</td>
				<td>$interacao</td>
				<td align=center><p align=left id='justificativa_$hd_chamado_melhoria'>$justificativa</p><textarea cols=30 name='justificativa' id='justificativa_nova_" . $hd_chamado_melhoria . "'></textarea><br><input class=frm type=button value='Gravar Justificativa' onclick='gravarJustificativa($hd_chamado_melhoria, $(\"#justificativa_nova_" . $hd_chamado_melhoria . "\").val())'><div id='indicadorajax'></div></td>
				<td>$hd_chamado<br>$hd_chamado_status</td>
				<td><div id='validacao_$hd_chamado_melhoria'>$validacao</div></td>
			</tr>";
		}

		echo  "
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
