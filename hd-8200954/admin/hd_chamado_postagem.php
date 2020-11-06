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

$ArrayEstados = array('', 'AC', 'AL', 'AM', 'AP', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MG', 'MS', 'MT', 'PA', 'PB', 'PE', 'PI', 'PR', 'RJ', 'RN', 'RO', 'RR', 'RS', 'SC', 'SE', 'SP', 'TO');

$atendente = trim($_POST["atendente"]);
if (strlen($atendente) == 0) $atendente = trim($_GET["atendente"]);

$status = trim($_POST["status"]);
if (strlen($status) == 0) $status = $_GET["status"];

$periodo = trim($_POST["periodo"]);
if (strlen($periodo) == 0) $periodo = $_GET["periodo"];

$periodo_quinzena = trim($_POST["periodo_quinzena"]);
if (strlen($periodo_quinzena) == 0) $periodo_quinzena = $_GET["periodo_quinzena"];

$cidade = trim($_POST["cidade"]);
if (strlen($cidade) == 0) $cidade = $_GET["cidade"];

$estado = trim($_POST["estado"]);
if (strlen($estado) == 0) $estado = $_GET["estado"];

if ($status != "aprovacao") {
	$periodo = explode("/", $periodo);
	if (count($periodo) == 2) {
		$periodo_mes = intval($periodo[0]);
		$periodo_ano = intval($periodo[1]);

		if ($periodo_mes < 1 || $periodo_mes > 12) {
			$msg_erro = "Mês informado é inválido";
		}

		if (strlen($periodo_quinzena) && $periodo_quinzena<>1 && $periodo_quinzena <>2) {
			$msg_erro = "Período informado é inválido";
		}
	}
	else {
		$msg_erro = "Para status diferente de \"Em aprovação\" é obrigatório informar o mês para a pesquisa";
	}
}
else {
	$periodo = "";
}

if(strlen($postatendente)) {
	$sql="
	SELECT
	admin
	
	FROM
	tbl_admin
	
	WHERE
	tbl_admin.admin=$atendente
	AND tbl_admin.fabrica=$login_fabrica
	";
	$res = pg_exec($con,$sql);

	if (pg_num_rows($res) == 0) {
		$msg_erro = "Atendente não encontrado";
		$atendente = "";
	}
	else {
		$acao = "PESQUISAR";
	}
}

if (strlen($estado)) {
	if (in_array($estato, $ArrayEstados)) {
	}
	else {
		$msg_erro = "O estado informado ($estado) é inválido";
	}
}

if (strlen($cidade) && strlen($estado) == 0) {
	$msg_erro = "Para buscar pela cidade, informe o estado";
}

if (strlen($revenda_cnpj)) {
	$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$revenda_cnpj));
	if(empty($valida_cpf_cnpj)){
		$sql = "SELECT fn_valida_cnpj_cpf('$revenda_cnpj')";
		@$res = pg_query($con, $sql);
		if ($res) {
		}
		else {
			$msg_erro = "CNPJ da revenda informado ($revenda_cnpj) não é válido";
		}
	}else{
		$msg_erro = $valida_cpf_cnpj;
	}
}

$title = "Autorização de Postagens do CallCenter";

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

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="2">Atendente</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="2">
				<select name="atendente" class="frm">
				<option value=""></option>
				<?
				$sql = "
				SELECT
				admin,
				login,
				nome_completo

				FROM
				tbl_admin

				WHERE
				fabrica = $login_fabrica
				AND ativo is true
				AND (privilegios like '%call_center%' or privilegios like '*') 

				ORDER BY
				nome_completo,
				login
				";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)){
					for ($i = 0; $i < pg_num_rows($res); $i++) {
						$nome_completo = pg_result($res, $i, nome_completo);
						$login = pg_result($res, $i, login);
						$admin = pg_result($res, $i, admin);

						if (strlen($nome_completo) > 50) {
							$nome_completo = substr($nome_completo, 0, 50) . '...';
						}

						echo "<option value=\"$admin\">$nome_completo</option>";
					}
				}
				?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Status</td>
			<td></td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
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
			</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Mês</td>
			<td>Período</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
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
							$mes_inicial = 6;
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
			<td>
				<select id="periodo_quinzena" name="periodo_quinzena" class="frm" <? echo $disabled; ?>>
					<option value="">Mês Inteiro</option>
					<option value="1" <? echo ($periodo_quinzena == 1)? "selected" : ""?>>Primeira Quinzena</option>
					<option value="2" <? echo ($periodo_quinzena == 2)? "selected" : ""?>>Segunda Quinzena</option>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Cidade</td>
			<td>Estado</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="cidade" id="cidade" class="frm" value="<? echo $cidade; ?>" />
			</td>
			<td align='left'>
				<select name="estado" id='estado' class='frm'>
					<?
					for ($i=0; $i<=count($ArrayEstados); $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}
					?>
				</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
		
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>CNPJ Revenda</td>
			<td></td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="revenda_cnpj" id="revenda_cnpj" class="frm" value="<? echo $revenda_cnpj; ?>" onkeyup="re = /\D/g; this.value = this.value.replace(re, '');" maxlength="14" />
			</td>
			<td align='left'>
			</td>
			<td width="10">&nbsp;</td>
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
	switch($periodo_quinzena) {
		case "1":
			$data_inicial = "'$periodo_ano-$periodo_mes-01 00:00:00'::timestamp";
			$data_final = "'$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '15 DAY'";
		break;

		case "2":
			$data_inicial = "'$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '1 MONTH' - INTERVAL '15 DAY'";
			$data_final = "'$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '1 MONTH'";
		break;

		default:
			$data_inicial = "'$periodo_ano-$periodo_mes-01 00:00:00'::timestamp";
			$data_final = "'$periodo_ano-$periodo_mes-01 00:00:00'::timestamp + INTERVAL '1 MONTH'";
	}

	switch($status) {
		case "aprovacao":
			$statusWhere = " AND tbl_hd_chamado_postagem.aprovado IS NULL";
		break;

		case "aprovadas":
			$statusWhere = " AND tbl_hd_chamado_postagem.aprovado IS TRUE";
			$periodoWhere = "AND tbl_hd_chamado_postagem.data BETWEEN $data_inicial AND $data_final";
		break;

		case "reprovadas":
			$statusWhere = " AND tbl_hd_chamado_postagem.aprovado IS FALSE";
			$periodoWhere = "AND tbl_hd_chamado_postagem.data BETWEEN $data_inicial AND $data_final";
		break;

		case "todas":
			$periodoWhere = " AND tbl_hd_chamado_postagem.data BETWEEN $data_inicial AND $data_final";
		break;
	}

	if (strlen($estado)) {
		$cidade_estadoWhere = " AND tbl_cidade.estado='$estado'";

		if (strlen($cidade)) {
			$cidade_estadoWhere = " AND tbl_cidade.nome ILIKE '%$cidade%'";
		}
	}

	if (strlen($revenda_cnpj)) {
		$revenda_cnpjWhere = " AND tbl_hd_chamado_extra.revenda_cnpj='$revenda_cnpj'";
	}

	$sql = "
	SELECT
	TO_CHAR(tbl_hd_chamado_postagem.data, 'DD/MM/YYYY HH24:MI') AS data,
	TO_CHAR(tbl_hd_chamado_postagem.data_aprovacao, 'DD/MM/YYYY HH24:MI') AS data_aprovacao,
	tbl_hd_chamado_postagem.hd_chamado,
	CASE
		WHEN tbl_hd_chamado_postagem.aprovado IS false and tbl_hd_chamado_postagem.admin isnull THEN 'Em aprovação'
		WHEN tbl_hd_chamado_postagem.aprovado IS TRUE then 'Aprovado'
		WHEN tbl_hd_chamado_postagem.aprovado IS FALSE and tbl_hd_chamado_postagem.admin notnull then 'Reprovado'
	END AS status,
	tbl_hd_chamado_postagem.aprovado,
	(SELECT nome_completo || ' (' || login || ')' FROM tbl_admin WHERE admin=tbl_hd_chamado_postagem.admin) AS admin_nome_completo,
	tbl_hd_chamado_postagem.motivo,
	tbl_hd_chamado_postagem.obs,
	tbl_hd_chamado_postagem.codigo_postagem

	FROM
	tbl_hd_chamado
	JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_postagem.hd_chamado
	JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
	LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
	
	WHERE
	tbl_hd_chamado.fabrica=$login_fabrica
	$statusWhere
	$periodoWhere
	$cidade_estadoWhere
	$revenda_cnpjWhere

	ORDER BY
	tbl_hd_chamado_postagem.data ASC
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
