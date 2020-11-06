<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "gerencia";
$title = "Relatório de Ofensores";
include 'cabecalho_new.php';

if (isset($_POST['pesquisar'])) {

	$data_inicial  = $_POST['data_inicial'];
	$data_final    = $_POST['data_final'];
	$tipo_pesquisa = $_POST['tipo_pesquisa'];
	$qtde_postos   = $_POST['qtde_postos'];
	$estados       = $_POST['estados'];

	if (!strlen($data_inicial) or !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (count($estados) > 0) {
		$estados_pesquisa = implode(",", $estados);
		$cond_estado      = "AND tbl_posto.estado IN ({$estados})";
	}

	if (!empty($qtde_postos)) {

		$limitOfensores = "LIMIT {$qtde_postos}";

	} else {

		$msg_erro['msg'][] 	  = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "qtde_postos";

	}

	if ($tipo_pesquisa == 'ordem_servico') {

		$cond_periodo    = "AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' and '{$aux_data_final}'";
		$sub_finalizadas_os = "
		(
			SELECT COUNT(tbl_os.os)
		 	FROM tbl_os
		 	JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		 	WHERE tbl_os.fabrica = {$login_fabrica}
		 	AND tbl_os.finalizada IS NOT NULL
			AND tbl_os.excluida is not true
		 	AND to_char(tbl_os.data_abertura, 'mm') = dados.mes
		 	{$cond_periodo}
		 	{$cond_estado}
		)
		";
		$sub_finalizadas_posto = "
		(
			SELECT COUNT(tbl_os.os)
		 	FROM tbl_os
		 	JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		 	WHERE tbl_os.fabrica = {$login_fabrica}
		 	AND tbl_os.finalizada IS NOT NULL
		 	AND tbl_os.posto = dados.posto
			AND tbl_os.excluida is not true
		 	{$cond_periodo}
		 	{$cond_estado}
		)
		";




		if (count($msg_erro) == 0) {

			$sqlOsGarantia = "SELECT dados.*,
									 ({$sub_finalizadas_os} * 100) / dados.qtde_total_os as porcentagem_finalizadas,
									 {$sub_finalizadas_os} as qtde_finalizadas,
									 (
										 SELECT ROUND(
										 	AVG(tbl_os.finalizada::date - tbl_os.data_abertura),
										 2)
										 FROM tbl_os
										 JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
										 WHERE tbl_os.finalizada IS NOT NULL
										 AND to_char(tbl_os.data_abertura, 'mm') = dados.mes
										 AND tbl_os.fabrica = {$login_fabrica}
										 AND (tbl_os.finalizada::date - tbl_os.data_abertura) > 0
										 AND tbl_os.excluida IS NOT TRUE
										 {$cond_periodo}
										 {$cond_estado}
									 ) as media_dias_em_aberto
								FROM (
									SELECT 
										to_char(tbl_os.data_abertura,'mm') as mes,
		       							extract(year from tbl_os.data_abertura) as ano,
										COUNT(tbl_os.os) AS qtde_total_os
								  	FROM tbl_os
								  	JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
								  	WHERE tbl_os.fabrica = {$login_fabrica}
								  	AND tbl_os.excluida IS NOT TRUE
								  	{$cond_periodo}
								  	{$cond_estado}
								  	GROUP BY mes, ano
							  ) as dados
							  ORDER BY dados.ano DESC, dados.mes DESC";
			$resOsGarantia = pg_query($con, $sqlOsGarantia);


			$sqlPostosOfensores = "SELECT dados.*,
									 ({$sub_finalizadas_posto} * 100) / dados.qtde_total_os as porcentagem_finalizadas,
									 {$sub_finalizadas_posto} as qtde_finalizadas,
									 (
										 SELECT ROUND(
										 	AVG(tbl_os.finalizada::date - tbl_os.data_abertura),
										 2)
										 FROM tbl_os
										 JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
										 WHERE tbl_os.finalizada IS NOT NULL
										 AND tbl_os.posto = dados.posto
										 AND tbl_os.fabrica = {$login_fabrica}
										 AND (tbl_os.finalizada::date - tbl_os.data_abertura) > 0
										 AND tbl_os.excluida IS NOT TRUE
										 {$cond_periodo}
										 {$cond_estado}
									 ) as media_dias_em_aberto
									FROM (
										SELECT
											tbl_posto.nome as nome_posto,
											tbl_posto.posto,
											COUNT(tbl_os.os) AS qtde_total_os
									  	FROM tbl_os
									  	JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
									  	WHERE tbl_os.fabrica = {$login_fabrica}
									  	AND tbl_os.excluida IS NOT TRUE
									  	{$cond_periodo}
									  	{$cond_estado}
									  	GROUP BY tbl_posto.posto, nome_posto
									  	ORDER BY qtde_total_os DESC
									  	{$limitOfensores}
								  ) as dados
								  ORDER BY dados.qtde_total_os DESC";
			$resPostosOfensores = pg_query($con, $sqlPostosOfensores);

		}

	} else {
		$cond_periodo    = "AND tbl_pedido.data::date BETWEEN '{$aux_data_inicial}' and '{$aux_data_final}'";
		$sub_finalizadas_pedido = "
		(
			SELECT COUNT(tbl_pedido.pedido)
			FROM tbl_pedido
			JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
			JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
			AND tbl_tipo_pedido.pedido_faturado IS TRUE
			JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido AND UPPER(tbl_status_pedido.descricao) = 'ENTREGUE'
			WHERE to_char(tbl_pedido.data, 'mm') = dados.mes
			AND tbl_pedido.fabrica = {$login_fabrica}
		 	{$cond_periodo}
		 	{$cond_estado}
		)
		";

		$sqlPedido = "SELECT dados.*,
							 ({$sub_finalizadas_pedido} * 100) / dados.qtde_total_pedido as porcentagem_finalizadas,
							 {$sub_finalizadas_pedido} as qtde_finalizadas,
							 (
							 	SELECT ROUND(AVG(
							 			(
							 				SELECT tbl_pedido_status.data::date
							 				FROM tbl_pedido_status
							 				JOIN tbl_status_pedido ON tbl_pedido_status.status = tbl_status_pedido.status_pedido
							 				AND UPPER(tbl_status_pedido.descricao) = 'ENTREGUE'
							 				WHERE tbl_pedido_status.pedido = tbl_pedido.pedido
							 				ORDER BY tbl_pedido_status.data DESC
							 				LIMIT 1
							 			) 	 - tbl_pedido.data::date
							 		),2)
								FROM tbl_pedido
								JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
								JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
								AND tbl_tipo_pedido.pedido_faturado IS TRUE
								JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido 
								AND UPPER(tbl_status_pedido.descricao) = 'ENTREGUE'
								WHERE to_char(tbl_pedido.data, 'mm') = dados.mes
								AND tbl_pedido.fabrica = {$login_fabrica}
							 	{$cond_periodo}
							 	{$cond_estado}
							 ) as media_dias_em_aberto
						FROM (
							SELECT 
								to_char(tbl_pedido.data,'mm') as mes,
	   							extract(year from tbl_pedido.data) as ano,
								COUNT(tbl_pedido.pedido) AS qtde_total_pedido
						  	FROM tbl_pedido
						  	JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
						  	JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
						  	AND tbl_tipo_pedido.pedido_faturado IS TRUE
						  	WHERE tbl_pedido.fabrica = {$login_fabrica}
						  	AND tbl_pedido.finalizado IS NOT NULL
						  	{$cond_periodo}
						  	{$cond_estado}
						  	GROUP BY mes, ano
					  ) as dados
					  ORDER BY dados.ano DESC, dados.mes DESC";
		$resPedido = pg_query($con, $sqlPedido);

		$sub_finalizadas_pedido_posto = "
		(
			SELECT COUNT(tbl_pedido.pedido)
			FROM tbl_pedido
			JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
			JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
			AND tbl_tipo_pedido.pedido_faturado IS TRUE
			JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido AND UPPER(tbl_status_pedido.descricao) = 'ENTREGUE'
			WHERE tbl_posto.posto = dados.posto
			AND tbl_pedido.fabrica = {$login_fabrica}
		 	{$cond_periodo}
		 	{$cond_estado}
		)
		";

		$sqlPedidoPosto = "SELECT dados.*,
							 ({$sub_finalizadas_pedido_posto} * 100) / dados.qtde_total_pedido as porcentagem_finalizadas,
							 {$sub_finalizadas_pedido_posto} as qtde_finalizadas,
							 (
							 	SELECT ROUND(AVG(
							 			(
							 				SELECT tbl_pedido_status.data::date
							 				FROM tbl_pedido_status
							 				JOIN tbl_status_pedido ON tbl_pedido_status.status = tbl_status_pedido.status_pedido
							 				AND UPPER(tbl_status_pedido.descricao) = 'ENTREGUE'
							 				WHERE tbl_pedido_status.pedido = tbl_pedido.pedido
							 				ORDER BY tbl_pedido_status.data DESC
							 				LIMIT 1
							 			) 	 - tbl_pedido.data::date
							 		),2)
								FROM tbl_pedido
								JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
								JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
								AND tbl_tipo_pedido.pedido_faturado IS TRUE
								JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido 
								AND UPPER(tbl_status_pedido.descricao) = 'ENTREGUE'
								WHERE tbl_pedido.posto = dados.posto
								AND tbl_pedido.fabrica = {$login_fabrica}
							 	{$cond_periodo}
							 	{$cond_estado}
							 ) as media_dias_em_aberto
						FROM (
							SELECT
								tbl_posto.posto,
								tbl_posto.nome AS nome_posto,
								COUNT(tbl_pedido.pedido) AS qtde_total_pedido
						  	FROM tbl_pedido
						  	JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
						  	JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
						  	AND tbl_tipo_pedido.pedido_faturado IS TRUE
						  	WHERE tbl_pedido.fabrica = {$login_fabrica}
						  	AND tbl_pedido.finalizado IS NOT NULL
						  	{$cond_periodo}
						  	{$cond_estado}
						  	GROUP BY tbl_posto.posto, nome_posto
						  	{$limitOfensores}
					  ) as dados
					  ORDER BY dados.qtde_total_pedido DESC";
		$resPedidoPosto = pg_query($con, $sqlPedidoPosto);

	}
}

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"multiselect"
);

$array_estados = $array_estados();

include("plugin_loader.php");
?>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>

<script>
	$(function(){

		$.datepickerLoad(Array("data_final", "data_inicial"));

		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#estados").multiselect({
            selectedText: "selecionados # de #"
        });

        $(".ui-multiselect-all").hide();

        $("#estados").change(function(){
        	var countChecked = $(this).find("option:selected").length;

        	if (countChecked >= 10) {
        		$("input[name=multiselect_estados]:not(:checked)").prop("disabled", true);

        	} else {
        		$("input[name=multiselect_estados]").prop("disabled", false);
        	}
        	
        });

        $(".shadowboxOpen, .shadowboxOpenPosto").filter(function(){
        	return $(this).text() != 0;
        }).click(function(){

        	var posto = "";
        	var mes   = "";

        	if ($(this).hasClass("shadowboxOpenPosto")) {
        		posto 			  = $(this).closest("tr").find(".posto").val();
        	} else {
        		mes 			  = $(this).closest("tr").find(".mes").val();
        	}

        	let tipo  			  = $(this).data("tipo");
        	let status 			  = $(this).data("status");
        	let data_inicial 	  = $("#aux_data_inicial").val();
        	let data_final 	      = $("#aux_data_final").val();
        	let tipo_pesquisa     = $("#aux_tipo_pesquisa").val();

        	Shadowbox.open({
                content: "relatorio_ofensores_detalhado.php?tipo=" + tipo + "&tipo_pesquisa=" + tipo_pesquisa + "&mes=" + mes + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&status=" + status + "&posto=" + posto,
                player: "iframe",
                width: 900,
                height: 450
            });

        });

        $("#visao_geral").click(function(){
        	$(this).prop("disabled", true);
        	$("#postos_ofensores").prop("disabled", false);
        	$("#tabela_geral, #graficoOs, #graficoPedido, #graficoMedia, .gerar_excel_os").show("slow");
        	$("#tabela_ofensores, .gerar_excel_posto").hide("slow");
        });

        $("#postos_ofensores").click(function(){
        	$(this).prop("disabled", true);
        	$("#visao_geral").prop("disabled", false);
        	$("#tabela_geral, #graficoOs, #graficoPedido, #graficoMedia, .gerar_excel_os").hide("slow");
        	$("#tabela_ofensores, .gerar_excel_posto").show("slow");
        });

	});

	function graficoOsTotal(titulo, meses, total, totalFinalizadas, elementoId, titulo1, titulo2) {

		Highcharts.chart(elementoId, {
		    chart: {
		        type: 'column'
		    },
		    title: {
		        text: titulo
		    },
		    xAxis: {
		        categories: meses
		    },
		    credits: {
		        enabled: false
		    },
		    series: [{
		        name: titulo1,
		        data: total
		    }, {
		        name: titulo2,
		        data: totalFinalizadas
		    }]
		});

	}

	function graficoMedia(titulo, meses, total, totalFinalizadas, elementoId, titulo1, titulo2) {

		Highcharts.chart(elementoId, {
		    chart: {
		        type: 'bar'
		    },
		    title: {
		        text: titulo
		    },
		    xAxis: {
		        categories: meses
		    },
		    credits: {
		        enabled: false
		    },
		    series: [{
		        name: titulo1,
		        data: total
		    }]
		});

	}
	
</script>
<style>
	.shadowboxOpen:hover, .shadowboxOpenPosto:hover  {
		cursor: pointer;
		background-color: darkblue !important;
		color: white;
		font-weight: bolder;
	}

	tfoot td:first-of-type {
		background-color: #596D9B !important;
		color: white;
	}
	tfoot td {
		font-weight: bolder;
		font-size: 15px;
		color: darkgreen;
	}
</style>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
		<div class='span2'></div>
	</div>
	<br />
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
		    <label class="radio">
		        <input type="radio" name="tipo_pesquisa" value="ordem_servico" <?= ($_POST['tipo_pesquisa'] == "ordem_servico" || !isset($_POST['btn_acao'])) ? "checked" : "" ?> >
		        Ordem de Serviço
		    </label>
		</div>
		<div class='span4'>
			 <label class="radio">
		        <input type="radio" name="tipo_pesquisa" value="pedido_faturado" <?= ($_POST['tipo_pesquisa'] == "pedido_faturado") ? "checked" : "" ?> >
		        Pedido Faturado
		    </label>
		</div>
		<div class='span2'></div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="<?=(in_array("qtde_postos", $msg_erro["campos"])) ? "error" : ""?>">
				<label class='control-label' for='data_final'>Postos Ofensores (Padrão: 15)</label>
				<div class='controls controls-row'>
					<h5 class='asteristico'>*</h5>
					<input type="number" class="span4" name="qtde_postos" value="<?= empty($qtde_postos) ? 15 : $qtde_postos ?>" />
				</div>
			</div>
		</div>
		<div class="span4">
			<label class='control-label' for='data_final'>Estados</label>
			<div class='controls controls-row'>
				<select id="estados" name="estados[]" class="span12" multiple="multiple">
                    <?php
                    #O $array_estados está no arquivo funcoes.php
                    foreach ($array_estados as $sigla => $nome_estado) {
                        $selected = ($sigla == $consumidor_estado) ? "selected" : "";

                        echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
                    }
                    ?>
                </select>
			</div>
		</div>
	</div>
	<p><br/>
		<button class='btn' id="btn_acao" name="pesquisar">Pesquisar</button>
	</p><br/>
</form>

<input type="hidden" id="aux_data_inicial" value="<?= $aux_data_inicial ?>" />
<input type="hidden" id="aux_data_final"   value="<?= $aux_data_final ?>" />
<input type="hidden" id="aux_tipo_pesquisa"    value="<?= $tipo_pesquisa ?>" />

	<button class="btn btn-large btn-info" id="visao_geral" disabled="" style="float: left;margin-left: 100px;">Visão geral</button>
	<button class="btn btn-large btn-info" id="postos_ofensores" style="float: right;margin-right: 100px;">Postos Ofensores</button>
	<br /><br /><br />

</div>

<?php
if (pg_num_rows($resOsGarantia) > 0) {
	
    $arquivoOs 	    = "xls/relatorio-ofensores-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";
    $arquivoOsPosto = "xls/relatorio-ofensores-postos-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";
    
	?>
	<div class='gerar_excel_os btn_excel' onclick="window.open('<?= $arquivoOs ?>')">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Geral</span>
	</div>

	<div class='gerar_excel_posto btn_excel' onclick="window.open('<?= $arquivoOsPosto ?>')" style="width: 210px;display: none;">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Ofensores</span>
	</div>
<br />
	<?php
	$condicao_status      = "0,1,2,3,4,9";

	if ($telecontrol_distrib) {
		$condicao_status .= ",35,36,37,39";
	}

	if (in_array($login_fabrica, [81])) {
		$condicao_status .= ",8";
	}

	if (in_array($login_fabrica, [174])) {
	    $condicao_status .= ",40,41,42,43";
	}

	pg_prepare($con, "query_status_os", "SELECT COUNT(os) as total_os_status
								  	  FROM tbl_os
									  WHERE to_char(data_abertura, 'mm') = $1
									  AND status_checkpoint = $2
									  AND tbl_os.excluida is not true
									  AND fabrica = {$login_fabrica}
									  {$cond_periodo}");

	$sqlStatusCheckpoint = "SELECT status_checkpoint, descricao
										FROM tbl_status_checkpoint
										WHERE status_checkpoint IN ({$condicao_status})";
	$resStatusCheckpoint = pg_query($con, $sqlStatusCheckpoint);

	$colspan = 5 + pg_num_rows($resStatusCheckpoint);

	ob_start();

	?>

	<table class="table table-bordered table-striped" id="tabela_geral">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="<?= $colspan ?>">OSs no período de <?= $data_inicial ?> - <?= $data_final ?></th>
			</tr>
			<tr class="titulo_coluna">
				<th>
					Mês/Ano
				</th>
				<th>
					Qtde de OS's
				</th>
				<th>
					Finalizadas Qtde
				</th>
				<th>
					Finalizadas %
				</th>
				<th>
					Média da Abertura Ao Fechamento (Dias)
				</th>
				<?php

				while ($dadosStatus = pg_fetch_object($resStatusCheckpoint)) { ?>
					<th>
						<?= $dadosStatus->descricao ?>
					</th>
				<?php
				} ?>
			</tr>
		</thead>
		<tbody>
			<?php

			$qtdeTotalOs 			   = 0;
			$qtdeTotalFinalizadas	   = 0;
			$mediaTotal                = [];
			$arrQtdeStatus             = [];
			$mesesGrafico              = [];
			$totalOsGrafico            = [];
			$totalOsFinalizadasGrafico = [];
			$mediaFechamento           = [];

			while ($dadosOs = pg_fetch_object($resOsGarantia)) {

				$qtdeTotalOs 			+= $dadosOs->qtde_total_os;
				$qtdeTotalFinalizadas	+= $dadosOs->qtde_finalizadas;

				$mesesGrafico[]				 =  $dadosOs->mes."/".$dadosOs->ano;
				$totalOsGrafico[]      		 =  (int) $dadosOs->qtde_total_os;
				$totalOsFinalizadasGrafico[] =  (int) $dadosOs->qtde_finalizadas;
				$mediaFechamentoGrafico[]    =  (int) $dadosOs->media_dias_em_aberto;

				if (!empty($dadosOs->media_dias_em_aberto)) {
					$mediaTotal[] = $dadosOs->media_dias_em_aberto;
				}

				?>
				<tr>
					<input type="hidden" class="mes" value="<?= $dadosOs->mes ?>" />
					<td class="tac"><?= $dadosOs->mes ?>/<?= $dadosOs->ano ?></td>
					<td class="tac shadowboxOpen" data-tipo="qtde_total"><?= $dadosOs->qtde_total_os ?></td>
					<td class="tac shadowboxOpen" data-tipo="qtde_finalizadas"><?= $dadosOs->qtde_finalizadas ?></td>
					<td class="tac"><?= $dadosOs->porcentagem_finalizadas ?>%</td>
					<td class="tac"><?= $dadosOs->media_dias_em_aberto ?></td>
				<?php

					$resStatusCheckpoint = pg_query($con, $sqlStatusCheckpoint);

					while ($dadosStatus = pg_fetch_object($resStatusCheckpoint)) {

						$resStatus = pg_execute($con, "query_status_os",
							[
								$dadosOs->mes,
								$dadosStatus->status_checkpoint
							]
						);

						$total_os_status   = pg_fetch_result($resStatus, 0, 'total_os_status');

						$arrQtdeStatus[$dadosStatus->status_checkpoint] += $total_os_status;
						
						?>
						<td class="tac shadowboxOpen" data-tipo="status" data-status="<?= $dadosStatus->status_checkpoint ?>">
							<?= $total_os_status ?>
						</td>
					<?php
					} ?>
				</tr>
			<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td class="tac">
					Total
				</td>
				<td class="tac">
					<?= $qtdeTotalOs ?>
				</td>
				<td class="tac">
					<?= $qtdeTotalFinalizadas ?>
				</td>
				<td class="tac">
					<?= number_format(($qtdeTotalFinalizadas * 100) / $qtdeTotalOs, 2) ?>%
				</td>
				<td class="tac">
					<?= number_format(array_sum($mediaTotal) / count($mediaTotal), 2) ?>
				</td>
				<?php
				foreach ($arrQtdeStatus as $statusCheck => $qtde) { ?>
					<td class="tac"><?= $qtde ?></td>
				<?php
				}
				?>
			</tr>
		</tfoot>
	</table>
	<?php
		$excel = ob_get_contents();
		$fp = fopen($arquivoOs,"w");
        fwrite($fp, $excel);
        fclose($fp);
	?>
	<br /><br />
	<div id="graficoOs" style="min-width: 50%; height: 400px; margin: 0 auto"></div>
	<div id="graficoMedia" style="min-width: 50%; height: 400px; margin: 0 auto"></div>

	<script>

		graficoOsTotal("Visão geral OSs", <?= json_encode($mesesGrafico) ?>, <?= json_encode($totalOsGrafico) ?>, <?= json_encode($totalOsFinalizadasGrafico) ?>, 'graficoOs', 'Total de OSs', 'Total de OSs Finalizadas');

		graficoMedia("TEMPO MÉDIO DE FECHAMENTO DE OSs", <?= json_encode($mesesGrafico) ?>, <?= json_encode($mediaFechamentoGrafico) ?>, <?= json_encode($mediaTotal) ?>, 'graficoMedia', 'Média de dias Fechamento');

	</script>
	<?php
	ob_start();
	?>
	<table class="table table-bordered table-striped" id="tabela_ofensores" style="display: none;">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="<?= $colspan ?>">Lista dos <?= $qtde_postos ?> postos com mais Ordens de Serviço dentro do período selecionado</th>
			</tr>
			<tr class="titulo_coluna">
				<th>
					Posto
				</th>
				<th>
					Qtde de OS's
				</th>
				<th>
					Finalizadas Qtde
				</th>
				<th>
					Finalizadas %
				</th>
				<th>
					Média da Abertura Ao Fechamento (Dias)
				</th>
				<?php

				pg_prepare($con, "query_status_posto", "SELECT COUNT(os) as total_os_status
									  FROM tbl_os
									  WHERE posto = $1
									  AND status_checkpoint = $2
									  AND tbl_os.excluida is not true
									  AND fabrica = {$login_fabrica}
									  {$cond_periodo}");

				$resStatusCheckpoint = pg_query($con, $sqlStatusCheckpoint);

				while ($dadosStatus = pg_fetch_object($resStatusCheckpoint)) { ?>
					<th>
						<?= $dadosStatus->descricao ?>
					</th>
				<?php
				} ?>
			</tr>
		</thead>
		<tbody>
			
				<?php

				$qtdeTotalOs 			= 0;
				$qtdeTotalFinalizadas	= 0;
				$mediaTotal             = [];
				$arrQtdeStatus          = [];

				while ($dadosPostos = pg_fetch_object($resPostosOfensores)) {

					$qtdeTotalOs 			+= $dadosPostos->qtde_total_os;
					$qtdeTotalFinalizadas	+= $dadosPostos->qtde_finalizadas;

					if (!empty($dadosPostos->media_dias_em_aberto)) {
						$mediaTotal[] = $dadosPostos->media_dias_em_aberto;
					}

					?>
					<tr>
						<input type="hidden" class="posto" value="<?= $dadosPostos->posto ?>" />
						<td class="tac"><?= $dadosPostos->nome_posto ?></td>
						<td class="tac shadowboxOpenPosto" data-tipo="qtde_total"><?= $dadosPostos->qtde_total_os ?></td>
						<td class="tac shadowboxOpenPosto" data-tipo="qtde_finalizadas"><?= $dadosPostos->qtde_finalizadas ?></td>
						<td class="tac"><?= $dadosPostos->porcentagem_finalizadas ?>%</td>
						<td class="tac"><?= $dadosPostos->media_dias_em_aberto ?></td>
					<?php

						$resStatusCheckpoint = pg_query($con, $sqlStatusCheckpoint);

						while ($dadosStatus = pg_fetch_object($resStatusCheckpoint)) {

							$resStatus = pg_execute($con, "query_status_posto",
								[
									$dadosPostos->posto,
									$dadosStatus->status_checkpoint
								]
							);

							$total_os_status   = pg_fetch_result($resStatus, 0, 'total_os_status');

							$arrQtdeStatus[$dadosStatus->status_checkpoint] += $total_os_status;
							
							?>
							<td class="tac shadowboxOpenPosto" data-tipo="status" data-status="<?= $dadosStatus->status_checkpoint ?>">
								<?= $total_os_status ?>
							</td>
						<?php
						} ?>
					</tr>
				<?php
				}
				?>
		</tbody>
		<tfoot>
			<tr>
				<td class="tac">
					Total
				</td>
				<td class="tac">
					<?= $qtdeTotalOs ?>
				</td>
				<td class="tac">
					<?= $qtdeTotalFinalizadas ?>
				</td>
				<td class="tac">
					<?= number_format(($qtdeTotalFinalizadas * 100) / $qtdeTotalOs, 2) ?>%
				</td>
				<td class="tac">
					<?= number_format(array_sum($mediaTotal) / count($mediaTotal), 2) ?>
				</td>
				<?php
				foreach ($arrQtdeStatus as $statusCheck => $qtde) { ?>
					<td class="tac"><?= $qtde ?></td>
				<?php
				}
				?>
			</tr>
		</tfoot>
	</table>
	<br />
<?php

	$excel = ob_get_contents();
	$fp = fopen($arquivoOsPosto,"w");
    fwrite($fp, $excel);
    fclose($fp);

} else if (pg_num_rows($resPedido) > 0) {

	$sqlStatusPedido = "SELECT DISTINCT tbl_status_pedido.status_pedido, tbl_status_pedido.descricao
						FROM tbl_pedido
						JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
						AND tbl_tipo_pedido.pedido_faturado IS TRUE
						WHERE tbl_pedido.fabrica = {$login_fabrica}";
	$resStatusPedido = pg_query($con, $sqlStatusPedido);

	pg_prepare($con, "query_status_pedido", "SELECT COUNT(tbl_pedido.pedido) as total_pedido_status
											  FROM tbl_pedido
											  JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
							  				  AND tbl_tipo_pedido.pedido_faturado IS TRUE
											  WHERE to_char(tbl_pedido.data, 'mm') = $1
											  AND tbl_pedido.status_pedido = $2
											  AND tbl_pedido.fabrica = {$login_fabrica}
											  {$cond_periodo}");

	$colspan = 5 + pg_num_rows($resStatusPedido);

	$arquivoPedido 		= "xls/relatorio-ofensores-pedidos-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";
    $arquivoPedidoPosto = "xls/relatorio-ofensores-pedidos-postos-{$login_fabrica}-".date('Y-m-d h:i:s').".xls";
    
	?>
	<div class='gerar_excel_os btn_excel' onclick="window.open('<?= $arquivoPedido ?>')">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Geral</span>
	</div>

	<div class='gerar_excel_posto btn_excel' onclick="window.open('<?= $arquivoPedidoPosto ?>')" style="width: 210px;display: none;">
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Arquivo Excel Ofensores</span>
	</div>
	<br />
	<?php
	ob_start();
	?>
	<table class="table table-bordered table-striped" id="tabela_geral">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="<?= $colspan ?>">Pedidos no período de <?= $data_inicial ?> - <?= $data_final ?></th>
			</tr>
			<tr class="titulo_coluna">
				<th>
					Mês/Ano
				</th>
				<th>
					Qtde de Pedidos
				</th>
				<th>
					Total de Pedidos Entregues
				</th>
				<th>
					Pedidos Entregues %
				</th>
				<th>
					Média da Abertura Ao Fechamento (Dias)
				</th>
				<?php

				while ($dadosStatus = pg_fetch_object($resStatusPedido)) { ?>
					<th>
						<?= $dadosStatus->descricao ?>
					</th>
				<?php
				} ?>
			</tr>
		</thead>
		<tbody>
			<?php

			$qtdeTotalPedidos 		= 0;
			$qtdeTotalFinalizadas	= 0;
			$mediaTotal             = [];
			$arrQtdeStatus          = [];
			$mesesGrafico           = [];
			$totalPedidoGrafico                = [];
			$totalPedidoFinalizadoGrafico      = [];

			while ($dadosPedido = pg_fetch_object($resPedido)) {

				$qtdeTotalPedidos 		+= $dadosPedido->qtde_total_pedido;
				$qtdeTotalFinalizadas	+= $dadosPedido->qtde_finalizadas;

				$mesesGrafico[]				 =  $dadosPedido->mes."/".$dadosPedido->ano;
				$totalPedidoGrafico[]      		=  (int) $dadosPedido->qtde_total_pedido;
				$totalPedidoFinalizadoGrafico[] =  (int) $dadosPedido->qtde_finalizadas;


				if (!empty($dadosPedido->media_dias_em_aberto)) {
					$mediaTotal[] = $dadosPedido->media_dias_em_aberto;
				}

				?>
				<tr>
					<input type="hidden" class="mes" value="<?= $dadosPedido->mes ?>" />
					<td class="tac"><?= $dadosPedido->mes ?>/<?= $dadosPedido->ano ?></td>
					<td class="tac shadowboxOpen" data-tipo="qtde_total"><?= $dadosPedido->qtde_total_pedido ?></td>
					<td class="tac shadowboxOpen" data-tipo="qtde_finalizadas"><?= $dadosPedido->qtde_finalizadas ?></td>
					<td class="tac"><?= $dadosPedido->porcentagem_finalizadas ?>%</td>
					<td class="tac"><?= $dadosPedido->media_dias_em_aberto ?></td>
				<?php

					$resStatusPedido = pg_query($con, $sqlStatusPedido);

					while ($dadosStatus = pg_fetch_object($resStatusPedido)) {

						$resStatus = pg_execute($con, "query_status_pedido",
							[
								$dadosPedido->mes,
								$dadosStatus->status_pedido
							]
						);

						$total_pedido_status   = pg_fetch_result($resStatus, 0, 'total_pedido_status');

						$arrQtdeStatus[$dadosStatus->status_pedido] += $total_pedido_status;
						
						?>
						<td class="tac shadowboxOpen" data-tipo="status" data-status="<?= $dadosStatus->status_pedido ?>">
							<?= $total_pedido_status ?>
						</td>
					<?php
					} ?>
				</tr>
			<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td class="tac">
					Total
				</td>
				<td class="tac">
					<?= $qtdeTotalPedidos ?>
				</td>
				<td class="tac">
					<?= $qtdeTotalFinalizadas ?>
				</td>
				<td class="tac">
					<?= number_format(($qtdeTotalFinalizadas * 100) / $qtdeTotalPedidos, 2) ?>%
				</td>
				<td class="tac">
					<?= number_format(array_sum($mediaTotal) / count($mediaTotal), 2) ?>
				</td>
				<?php
				foreach ($arrQtdeStatus as $statusCheck => $qtde) { ?>
					<td class="tac"><?= $qtde ?></td>
				<?php
				}
				?>
			</tr>
		</tfoot>
	</table>
	<?php
	$excel = ob_get_contents();
	$fp = fopen($arquivoPedido,"w");
    fwrite($fp, $excel);
    fclose($fp);
	?>
	<br />
	<div id="graficoPedido" style="min-width: 50%; height: 400px; margin: 0 auto"></div>
	<script>
		graficoOsTotal("Visão geral Pedidos Faturados", <?= json_encode($mesesGrafico) ?>, <?= json_encode($totalPedidoGrafico) ?>, <?= json_encode($totalPedidoFinalizadoGrafico) ?>, 'graficoPedido', 'Total de Pedidos', 'Total de Pedidos Entregues');
	</script>
	<?php
	pg_prepare($con, "query_status_pedido_posto", "SELECT COUNT(tbl_pedido.pedido) as total_pedido_status
											  FROM tbl_pedido
											  JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
							  				  AND tbl_tipo_pedido.pedido_faturado IS TRUE
											  WHERE tbl_pedido.posto = $1
											  AND tbl_pedido.status_pedido = $2
											  AND tbl_pedido.fabrica = {$login_fabrica}
											  {$cond_periodo}");

	$colspan = 5 + pg_num_rows($resStatusPedido);

	ob_start();
	?>
	<table class="table table-bordered table-striped" id="tabela_ofensores" style="display: none;">
		<thead>
			<tr class="titulo_tabela">
				<th colspan="<?= $colspan ?>">Pedidos no período de <?= $data_inicial ?> - <?= $data_final ?></th>
			</tr>
			<tr class="titulo_coluna">
				<th>
					Posto
				</th>
				<th>
					Qtde de Pedidos
				</th>
				<th>
					Total de Pedidos Entregues
				</th>
				<th>
					Pedidos Entregues %
				</th>
				<th>
					Média da Abertura Ao Fechamento (Dias)
				</th>
				<?php

				$resStatusPedido = pg_query($con, $sqlStatusPedido);

				while ($dadosStatus = pg_fetch_object($resStatusPedido)) { ?>
					<th>
						<?= $dadosStatus->descricao ?>
					</th>
				<?php
				} ?>
			</tr>
		</thead>
		<tbody>
			<?php

			$qtdeTotalPedidos 		= 0;
			$qtdeTotalFinalizadas	= 0;
			$mediaTotal             = [];
			$arrQtdeStatus          = [];

			while ($dadosPedidoPosto = pg_fetch_object($resPedidoPosto)) {

				$qtdeTotalPedidos 		+= $dadosPedidoPosto->qtde_total_pedido;
				$qtdeTotalFinalizadas	+= $dadosPedidoPosto->qtde_finalizadas;

				if (!empty($dadosPedidoPosto->media_dias_em_aberto)) {
					$mediaTotal[] = $dadosPedidoPosto->media_dias_em_aberto;
				}

				?>
				<tr>
					<input type="hidden" class="posto" value="<?= $dadosPedidoPosto->posto ?>" />
					<td class="tac"><?= $dadosPedidoPosto->nome_posto ?></td>
					<td class="tac shadowboxOpenPosto" data-tipo="qtde_total"><?= $dadosPedidoPosto->qtde_total_pedido ?></td>
					<td class="tac shadowboxOpenPosto" data-tipo="qtde_finalizadas"><?= $dadosPedidoPosto->qtde_finalizadas ?></td>
					<td class="tac"><?= $dadosPedidoPosto->porcentagem_finalizadas ?>%</td>
					<td class="tac"><?= $dadosPedidoPosto->media_dias_em_aberto ?></td>
				<?php

					$resStatusPedido = pg_query($con, $sqlStatusPedido);

					while ($dadosStatus = pg_fetch_object($resStatusPedido)) {

						$resStatus = pg_execute($con, "query_status_pedido_posto",
							[
								$dadosPedidoPosto->posto,
								$dadosStatus->status_pedido
							]
						);

						$total_pedido_status   = pg_fetch_result($resStatus, 0, 'total_pedido_status');

						$arrQtdeStatus[$dadosStatus->status_pedido] += $total_pedido_status;
						
						?>
						<td class="tac shadowboxOpenPosto" data-tipo="status" data-status="<?= $dadosStatus->status_pedido ?>">
							<?= $total_pedido_status ?>
						</td>
					<?php
					} ?>
				</tr>
			<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td class="tac">
					Total
				</td>
				<td class="tac">
					<?= $qtdeTotalPedidos ?>
				</td>
				<td class="tac">
					<?= $qtdeTotalFinalizadas ?>
				</td>
				<td class="tac">
					<?= number_format(($qtdeTotalFinalizadas * 100) / $qtdeTotalPedidos, 2) ?>%
				</td>
				<td class="tac">
					<?= number_format(array_sum($mediaTotal) / count($mediaTotal), 2) ?>
				</td>
				<?php
				foreach ($arrQtdeStatus as $statusCheck => $qtde) { ?>
					<td class="tac"><?= $qtde ?></td>
				<?php
				}
				?>
			</tr>
		</tfoot>
	</table>
	<br />
<?php

	$excel = ob_get_contents();
	$fp = fopen($arquivoPedidoPosto,"w");
    fwrite($fp, $excel);
    fclose($fp);

} else if (isset($_POST['pesquisar']) && count($msg_erro) == 0) { ?>
	<div class="alert alert-warning">
		<h4>Não foram encontrados resultados para a consulta</h4>
	</div>
<?php
}

include 'rodape.php';
?>
