<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

function mostraDados(){
	global $con, $login_fabrica;
	
	$sql = "SELECT valores_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$valor = pg_fetch_result($res, 0, valores_adicionais);
	
	if (!empty($valor)) {
		$valores_adicionais = json_decode($valor, true);
		$retorno = "
			<br />
			<table class='table table-striped table-bordered table-fixed' width='700' align='center' class='tabela'>
			<tr class='titulo_coluna'>
			<th>".traduz("Serviço")."</th>
			<th>".traduz("Valor")."</th>
			<th>".traduz("Posto Edita")."</th>
		";

		if (in_array($login_fabrica, array(169,170))) {
			$retorno .= "<th>".traduz("Tipo Atendimento")."</th>";
		}

		$retorno .= "</tr>";

		foreach ($valores_adicionais as $key => $value) {
			$servico = utf8_decode($key);
			$valor   = $value['valor'];
			$edita   = ($value['editar'] == "t") ? "Sim" : "Não";

			if (!empty($value['tipo_atendimento'])) {
				$tipoAtendimentoId = $value['tipo_atendimento'];

				$sqlTpAtn = "SELECT * FROM tbl_tipo_atendimento WHERE tipo_atendimento = {$tipoAtendimentoId} AND fabrica = {$login_fabrica};";
				$resTpAtn = pg_query($con, $sqlTpAtn);

				if (pg_num_rows($resTpAtn) > 0) {
					$tipo_atendimento = pg_fetch_result($resTpAtn, 0, descricao);
				} else {
					$tipo_atendimento = "";
				}
			} else {
				$tipo_atendimento = "";
				$tipoAtendimentoId = "";
			}

			$retorno .= "
				<tr>
				<td>
				<a href='javascript:void(0);' onclick='carregaDados(\"$servico\",\"$valor\",\"$edita\", \"$tipoAtendimentoId\")'>$servico</a>
				</td>
				<td class='tac'><input type='hidden' value='$valor' class='valor_adicional' /></td>
				<td class='tac'>$edita</td>
			";

			if (in_array($login_fabrica, array(169,170))) {
				$retorno .= "<td class='tac'>$tipo_atendimento</td>";
			}

			$retorno .= "</tr>";
		}
		$retorno .= "</table>";

		return $retorno;
	}
}

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {

	$servico_id 		= $_POST['servico_id'];
	$servico 			= $_POST['servico'];
	$valor 				= $_POST['valor'];
	$editar 			= $_POST['editar'];
	$tipo_atendimento 	= $_POST['tipo_atendimento'];

	$sql = "SELECT fn_retira_especiais('{$servico}')";
    $res = pg_query($con, $sql);

    $servico = strtoupper(pg_fetch_result($res, 0, 0));

	if (empty($servico)) {
		$msg_erro = "Informe um serviço";
	}

	if (empty($valor) && $login_fabrica < 152) {
		$msg_erro = traduz("Informe um valor");
	}
	
	if (empty($valor) && $login_fabrica >= 152 && $editar == "f") {
		$msg_erro = traduz("Informe um valor");
	}

	if (empty($msg_erro)) {
		
		$valores = array($servico => array("valor" => $valor, "editar" => $editar));

		if (in_array($login_fabrica, array(169,170)) && !empty($tipo_atendimento)) {
			$valores[$servico]['tipo_atendimento'] = $tipo_atendimento;
		}

		$sql = "SELECT valores_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica};";
		$res = pg_query($con, $sql);
		
		$valor_adicional = pg_fetch_result($res, 0, valores_adicionais);

		if (!empty($valor_adicional)) {
			$valores_adicionais = json_decode($valor_adicional,true);
			if (!empty($servico_id)) {
				$sql = "SELECT fn_retira_especiais('{$servico_id}');";
	            $res = pg_query($con, $sql);
	            $servico_id_min = pg_fetch_result($res, 0, 0);

                $servico_id = strtoupper($servico_id_min);
				$valores_adicionais[$servico_id]['valor'] = $valor;
				$valores_adicionais[$servico_id]['editar'] = $editar;

				if (in_array($login_fabrica, array(169,170)) && !empty($tipo_atendimento)) {
					$valores_adicionais[$servico_id]['tipo_atendimento'] = $tipo_atendimento;
				}
			} else {
				$valores_adicionais = array_merge($valores_adicionais, $valores);
			}
		} else {
			$valores_adicionais = $valores;
		}
		
		//$json = json_encode($valores_adicionais);
		$json = "{";
		$i = 1;
		foreach ($valores_adicionais as $key => $value) {
			$json .= "\"$key\":{";
			$j = 1;
			foreach ($value as $chave => $valor) {
					$json .= "\"$chave\":\"$valor\"";
					if($j < count($value)){
						$json .= ",";
					}
					$j++;
				}
			$json .= "}";
			if($i < count($valores_adicionais)){
				$json .= ",";
			}
			$i++;
		}
		$json .= "}";
		
		$sql = "UPDATE tbl_fabrica SET valores_adicionais = '{$json}' WHERE fabrica = {$login_fabrica};";
		$res = pg_query($con,$sql);
		$msg_erro =  pg_last_error($con);
	}

	if(empty($msg_erro)){
		header("Location: {$PHP_SERVER['PHP_SELF']}?msg=gravou");
	}
}

if ($btn_acao == 'excluir') {
	$servico 	= $_POST['servico_id'];

	$sql = "SELECT valores_adicionais FROM tbl_fabrica WHERE fabrica = {$login_fabrica};";
	$res = pg_query($con,$sql);
	$valor = pg_fetch_result($res, 0, valores_adicionais);
	if(!empty($valor)){
		$valores_adicionais = json_decode($valor,true);
		unset($valores_adicionais[$servico]);
	}

	if (count($valores_adicionais) > 0) {
		$json = "'{";
		$i = 1;
		foreach ($valores_adicionais as $key => $value) {
			$json .= "\"$key\":{";
			$j = 1;
			foreach ($value as $chave => $valor) {
					$json .= "\"$chave\":\"$valor\"";
					if($j < count($value)){
						$json .= ",";
					}
					$j++;
				}
			$json .= "}";
			if($i < count($valores_adicionais)){
				$json .= ",";
			}
			$i++;
		}
		$json .= "}'";
	} else {
		$json = 'null';
	}

	$sql = "UPDATE tbl_fabrica SET valores_adicionais = {$json} WHERE fabrica = {$login_fabrica};";
	$res = pg_query($con,$sql);

	if(pg_last_error($con)){
		$msg_erro =  pg_last_error($con);
	}else{
		header("Location: {$PHP_SERVER['PHP_SELF']}?msg=excluiu");
	}

}

$layout_menu = "cadastro";
$title = traduz("CADASTRO DE VALORES ADICIONAIS DA OS");

include "cabecalho_new.php";

$plugins = array(
   "price_format"
);

include 'plugin_loader.php';

if ($_GET['msg'] == 'gravou') {
	$msg = traduz("Gravado com sucesso.");
} else if ($_GET['msg'] == 'excluiu') {
	$msg = traduz("Excluído com sucesso.");
} else {
	$msg = "";
}

if ($msg) { ?>
	<div class='alert alert-success'><?= $msg; ?></div>
<? }
if ($msg_erro) { ?>
	<div class='alert alert-danger'><?= $msg_erro; ?></div>
<? } ?>

<form class='form-search form-inline tc_formulario' name='frm_cadastro' method='post'>
	<div class='titulo_tabela'><?=traduz('Cadastro Valores Adicionais')?></div>
	<br />
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''><?=traduz('Serviço')?></label>
				<div class='controls controls-row'>
					<h5 class="asteristico">*</h5>
					<input type='text' name='servico' class='frm' size='25' value='<?=$servico?>' onkeyup="javascript:somenteMaiusculaSemAcento(this);">
				</div>
			</div>	
		</div>
		<div class="span2">
			<div class="control-group">
				<label class="control-label" for=''><?=traduz('Valor')?></label>
				<div class='controls controls-row'>
					<input class="span8" type='text' name='valor' class='frm' value='<?=$valor?>'>
				</div>
			</div>
		</div>
			<?php
				if($editar == 't'){
					$checked_sim = "checked";
					$checked_nao = "";
				}else{
					$checked_sim = "";
					$checked_nao = "checked";
				}
			?>
		<div class="span4">
			<div class="control-group">
				<label class="control-label" for=''><?=traduz('Posto Edita')?>?</label>
				<div class='controls row-fluid'>
					<div class="span4">
						<label class="radio">	
							<input type='radio' name='editar' id='radio_sim' value='t' <?=$checked_sim?> /><?=traduz('Sim')?>
						</label>
					</div>
					<div class="span3">
						<label class="radio">
							<input type='radio' name='editar' id='radio_nao' value='f' <?=$checked_nao?> /><?=traduz('Não')?>
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class="span1"></div>	
	</div>
	<? if (in_array($login_fabrica, array(169,170))) { ?>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span4">
				<div class="control-group">
					<label class="control-label" for=''><?=traduz('Tipo de Atendimento')?></label>
					<div class='controls controls-row'>
						<select id="tipo_atendimento" name="tipo_atendimento" class="frm">
							<?
							$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
							$res = pg_query($con, $sql);

							for ($i = 0; $i < pg_num_rows($res); $i++) {
								$xtipo_atendimento = pg_fetch_result($res, $i, tipo_atendimento);
								$xtipo_atendimento_descricao = pg_fetch_result($res, $i, descricao);
								$selected = ($tipo_atendimento == $xtipo_atendimento) ? "selected" : ""; ?>
								<option value="<?= $xtipo_atendimento; ?>" <?= $selected; ?>><?= $xtipo_atendimento_descricao; ?></option>
							<? } ?>
						</select>
					</div>
				</div>	
			</div>
			<div class="span1"></div>
		</div>
	<? } ?>
	<div class="row-fluid">
		<div class="span1"></div>
		<div class="span10 tac">
			<input type='hidden' name='btn_acao' value=''>
			<input type='hidden' name='servico_id' value='<?=$servico_id?>'>
			<input class='btn' type='button' value='Gravar' onclick="javascript: if(document.frm_cadastro.btn_acao.value == ''){document.frm_cadastro.btn_acao.value='gravar';document.frm_cadastro.submit();}else{alert('Aguarde submissão');}">
			<input class='btn btn-danger' type='button' value='Excluir' onclick="javascript: if(document.frm_cadastro.btn_acao.value == ''){document.frm_cadastro.btn_acao.value='excluir';document.frm_cadastro.submit();}else{alert('Aguarde submissão');}">
		</div>
		<div class="span1"></div>
	</div>
</form>

<div id='resultado'><?= mostraDados();?></div>

<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>

<script type="text/javascript">
$(document).ready(function(){
	$("input[name=valor]").maskMoney({showSymbol:"", symbol:"", decimal:",", precision:2, thousands:"",maxlength:10});

	$(".valor_adicional").priceFormat({
        prefix: '',
        thousandsSeparator: '',
        centsSeparator: '.',
        centsLimit: 2
    });

	$(".valor_adicional").each(function(){
		var valor = $(this).val();

		$(this).closest("td").html(valor);
	});

});

function carregaDados(servico,valor,edita,tipo_atendimento) {

	$("input[name=servico]").val(servico);
	$("input[name=servico]").attr("readonly","readonly");

	$("input[name=servico_id]").val(servico);
	$("input[name=valor]").val(valor);

	if(edita == "Sim"){
		$("#radio_sim").attr("checked","checked");
	}else{
		$("#radio_nao").attr("checked","checked");
	}

	if ($("#tipo_atendimento").length) {
		$("#tipo_atendimento").val(tipo_atendimento);
	}

}
</script>

<? include "rodape.php"; ?>
