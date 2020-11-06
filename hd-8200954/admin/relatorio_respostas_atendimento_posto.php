<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center, gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST['btn_acao']){
	$data_inicial = formata_data($_POST["data_inicial"]);
    	$data_final   = formata_data($_POST["data_final"]);
	$cod_posto = $_POST['codigo_posto'];
	$linha = $_POST['Linha'];
	$estado = $_POST['estado'];

	if(empty($data_inicial) OR empty($data_final)){
		$data_inicial = date("Y-m-d");
		$data_final = date("Y-m-d");
	}

	if(!empty($cod_posto)){
		$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '{$cod_posto}'";
		$res = pg_query($con,$sql);

		$posto = pg_fetch_result($res,0,0);

		if(!empty($posto)){
			$condPosto = " AND tbl_resposta.posto = {$posto} ";
		}
	}

	if(strlen($estado) > 0){
		$condUf = " AND tbl_posto_fabrica.contato_estado = '{$estado}' ";
	}

	if(!empty($linha)){
		$joinPostoLinha = " JOIN tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = {$linha} ";
	}
}else{
 	$data_inicial = date("Y-m-d");
	$data_final = date("Y-m-d");
}

	$sql = "SELECT tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome,
			tbl_resposta.txt_resposta,
			tbl_resposta.data_input
		FROM tbl_resposta
		JOIN tbl_posto ON tbl_posto.posto = tbl_resposta.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
		$joinPostoLinha
		WHERE tbl_resposta.pesquisa = 677
		AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		AND tbl_resposta.data_input BETWEEN '$data_inicial 00:00' and '$data_final 23:59:59'
		$condPosto
		$condUf
		ORDER BY tbl_resposta.data_input DESC";
	$resPrincipal = pg_query($con,$sql);

	$sql = "SELECT 	COUNT(1) FILTER (WHERE txt_resposta::jsonb->0->'perguntas'->0->'radio'->>'options' = '0') AS aberto,
					COUNT(1) FILTER (WHERE txt_resposta::jsonb->0->'perguntas'->0->'radio'->>'options' = '1') AS fechado,
					to_char(tbl_resposta.data_input,'DD/MM/YYYY') AS dia
		FROM tbl_resposta
		JOIN tbl_posto ON tbl_posto.posto = tbl_resposta.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
		$joinPostoLinha
		WHERE tbl_resposta.pesquisa = 677
		AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		AND tbl_resposta.data_input BETWEEN '$data_inicial 00:00' and '$data_final 23:59:59'
		$condPosto
		$condUf
		GROUP BY dia
		ORDER BY dia";
	$resResumo = pg_query($con,$sql);

$layout_menu = "callcenter";

$title = "Questionário de Avaliação do Posto Autorizado";
include 'cabecalho_new.php';

$plugins = array(
   "bootstrap3",
   "shadowbox",
   "telecontrol-easy-form-builder",
   "dataTable",
   "datepicker",
   "mask",
   "autocomplete"
);

include "plugin_loader.php";

?>
<script>

	$(function(){

		$.datepickerLoad(Array("data_final", "data_inicial"));

		$.autocompleteLoad(Array("produto", "peca", "posto"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});


	function retorna_posto(retorno){
		$("#posto").val(retorno.posto);
        	$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    	}

</script>
<?php
if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
} else if (count($msg_success["msg"]) > 0) { ?>
	<div class="alert alert-success">
		<h4><?=implode("<br />", $msg_success["msg"])?></h4>
    </div>
<?php
} ?>

<div class="row alert">
	<b>
		Se não for informado um período, o relatório irá conseiderar apenas o dia atual.<br>
		O Período máximo para consulta é de 30 dias.
	</b>
</div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela'>Parâmetros de Pesquisa</div>
	<br />
	<div id="questionario_pesquisa"></div>
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_inicial'>Data Inicial</label>
				<div class='controls controls-row'>
					<div class='col-md-4' style="padding-left: 0px !important;">
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value="<?= mostra_data($_POST['data_inicial']) ?>">
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'>Data Final</label>
				<div class='controls controls-row'>
					<div class='col-md-4' style="padding-left: 0px !important;">
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?= mostra_data($_POST['data_final']) ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Linha</label>
				<div class='controls controls-row'>
					<div class='col-md-7 input-append' style="padding-left: 0px !important;">
						<select name="Linha" class="form-control">
							<option value="">Selecione uma Linha</option>
							<?php
								$sql = "SELECT linha, nome 
									FROM tbl_linha 
									WHERE fabrica = {$login_fabrica} 
									AND ativo
									ORDER BY nome";
								$res = pg_query($con,$sql);
				
								while($dados = pg_fetch_object($res)){
	
									$selected = ($dados->linha == $_POST['Linha']) ? "SELECTED" : "";

									echo "<option value='{$dados->linha}' {$selected}>{$dados->nome}</option>";

								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Estado</label>
				<div class='controls controls-row'>
					<div class='col-md-7 input-append' style="padding-left: 0px !important;">
						<select name="estado" class="form-control">
							<option value="">Selecione um Estado</option>
							<?php
							$sqlPesquisa = "SELECT estado,nome 
									FROM tbl_estado
									WHERE pais = '{$login_pais}'
									AND visivel IS TRUE
									ORDER BY nome";
							$resPesquisa = pg_query($con, $sqlPesquisa);

							while ($dados = pg_fetch_object($resPesquisa)) { 

								$selected = ($_POST["estado"] == $dados->estado) ? "selected" : "";

								?>
								<option value="<?= $dados->estado ?>" <?= $selected ?>><?= $dados->nome ?></option>
							<?php
							} ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<br />
	<input type="hidden" name="posto" id="posto" size="12" maxlength="10" class='span12' value="<?= $_POST['posto'] ?>" >
	<div class='row-fluid'>
		<div class='col-md-2'></div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='col-md-7 input-append' style="padding-left: 0px !important;">
						<input type="text" name="codigo_posto" id="codigo_posto" class='col-md-12' value="<? echo $codigo_posto ?>" >
						<span class='add-on' rel="lupa" style="height: 30px;"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-4'>
			<div class='control-group'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='col-md-12 input-append' style="padding-left: 0px !important;">
						<input type="text" name="descricao_posto" id="descricao_posto" class='col-md-12' value="<? echo $descricao_posto ?>" >&nbsp;
						<span class='add-on' rel="lupa" style="height: 30px;"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
    <br /><br />
	<input type="submit" class='btn btn-default' name="btn_acao" value="Pesquisar" />
	<br/><br />
</form>

<table id='listaPesquisas' class='table table-striped table-bordered table-hover table-fixed'>
  <thead>
    <tr class = 'titulo_coluna'>
      <th>Código Posto</th>
      <th>Nome Posto</th>
      <th>Data</th>
      <th>Aberto/Fechado</th>
    </tr>
  </thead>
  <tbody>
	<?php
	
	while($dados = pg_fetch_object($resPrincipal)){
		
		$resposta = json_decode($dados->txt_resposta,true);
		$resp = $resposta[0]['perguntas'][0]['radio']['options'];
		$status = ($resp == '0') ? 'Aberto' : 'Fechado';
		
		echo "	<tr>
				<td>".$dados->codigo_posto."</td>
				<td>".$dados->nome."</td>
				<td>".mostra_data_hora($dados->data_input)."</td>
				<td>".$status."</td>
			</tr>";
		

	}
	?>
  </tbody>
</table>
<?php
if (pg_num_rows($resPrincipal) > 20) {
?>
<script>
	$.dataTableLoad({ table: "#listaPesquisas" });
</script>
<?php
}
?>
<br>
<table id='resumo' class='table table-striped table-bordered table-hover table-fixed'>
	<caption class="titulo_tabela">RESUMO</caption>
  <thead>
    <tr class="titulo_coluna">
      <th data-column="dia">Dia</th>
      <th data-column="aberto">Total Aberto</th>
      <th data-column="fechado">Total Fechado</th>
      <th data-column="total">Total Logados</th>
    </tr>
  </thead>
  <tbody>
	<?php
	while($dados = pg_fetch_object($resResumo)){
		
		$total = $dados->aberto + $dados->fechado;
		echo "	<tr>
				<td>".$dados->dia."</td>
				<td class='tac'>".$dados->aberto."</td>
				<td class='tac'>".$dados->fechado."</td>
				<td class='tac'>".$total."</td>
			</tr>";
	}
	?>
  </tbody>
</table>
<br><br>
<?php

include "rodape.php";
?>
