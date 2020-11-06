<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['pesquisa'])) {
	$data_inicial       	= formata_data($_POST['data_inicial']);
	$data_final         	= formata_data($_POST['data_final']);
	$data_inicial_nf    	= formata_data($_POST['data_inicial_nf']);
	$data_final_nf      	= formata_data($_POST['data_final_nf']);
	$codigo_posto       	= $_POST['codigo_posto'];
	$descricao_posto    	= $_POST['descricao_posto'];
	$mes_extrato        	= $_POST['mes_extrato'];
	$nf_anexada         	= $_POST['nf_anexada'];
	$conferencia_finalizada = $_POST['conferencia_finalizada'];
	$status                 = $_POST['status'];

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
			$condPosto = "AND tbl_extrato.posto = {$posto}";
		}
	}

	if (empty($data_inicial) && empty($data_final) && empty($data_inicial_nf) && empty($data_final_nf)) {

		$msg_erro["msg"][]    = "Preencha uma das datas (nf ou extrato)";
		$msg_erro["campos"][] = "data";
		$msg_erro["campos"][] = "data_nf";

	} else {

		if (!empty($data_inicial) && !empty($data_final)) {
			$condData = "AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
		}

		if (!empty($data_inicial_nf) && !empty($data_final_nf )) {
			$condDataNf = "AND tbl_tdocs.data_input BETWEEN '$data_inicial_nf' AND '$data_final_nf'";
		}

	}

	if (!empty($mes_extrato)) {
		$condMes = "AND to_char(tbl_extrato.data_geracao,'yyyy-mm') = '{$mes_extrato}'";
	}

	if (!empty($nf_anexada)) {

		if ($nf_anexada == "s") {
			$condNfAnexada = "AND tbl_tdocs.data_input IS NOT NULL";
		} else {
			$condNfAnexada = "AND tbl_tdocs.data_input IS NULL";
		}

	}

	if (!empty($conferencia_finalizada)) {

		if ($conferencia_finalizada == "s") {
			$condConferencia = "AND tbl_extrato_conferencia.data_conferencia IS NOT NULL";
		} else {
			$condConferencia = "AND tbl_extrato_conferencia.data_conferencia IS NULL";
		}

	}

	if (!empty($status)) {
		if ($status == "Finalizado") {
			$condStatus = "AND tbl_extrato_agrupado.aprovado IS NOT NULL
						   AND tbl_extrato_conferencia.previsao_pagamento IS NOT NULL";
		} else {
			$condStatus = "AND (tbl_extrato_agrupado.aprovado IS NULL 
						   OR tbl_extrato_conferencia.previsao_pagamento IS NULL)";
		}
	}

	if (count($msg_erro["msg"]) == 0) {

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sql = "SELECT DISTINCT ON (tbl_posto.posto,tbl_extrato.data_geracao::date,tbl_extrato_agrupado.codigo)
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_extrato.extrato,
						tbl_extrato.nf_recebida,
						to_char(tbl_extrato.data_geracao,'DD/MM') AS geracao,
						tbl_faturamento.nota_fiscal,
						to_char(tbl_tdocs.data_input,'DD/MM/YYYY') AS data_envio,
						tbl_extrato_agrupado.codigo AS codigo_agrupador,
						tbl_extrato_conferencia.nota_fiscal AS nf_mo,
						to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY') AS data_conferencia,
						to_char(tbl_extrato_conferencia.previsao_pagamento, 'DD/MM/YYYY') AS previsao_pagamento,
						CASE
							WHEN tbl_extrato_agrupado.aprovado IS NOT NULL AND tbl_extrato_conferencia.previsao_pagamento IS NOT NULL 
							THEN
								'Finalizado'
							ELSE
								'Aberto'
						END AS status
				FROM tbl_extrato
				JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_extrato.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_extrato.fabrica
				LEFT JOIN tbl_faturamento ON tbl_extrato.extrato = tbl_faturamento.extrato_devolucao AND tbl_faturamento.distribuidor = tbl_extrato.posto
				LEFT JOIN tbl_tdocs ON tbl_faturamento.faturamento = tbl_tdocs.referencia_id AND tbl_tdocs.contexto = 'lgr' and tbl_tdocs.fabrica = tbl_extrato.fabrica
				LEFT JOIN tbl_extrato_agrupado ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato
				LEFT JOIN tbl_extrato_conferencia ON tbl_extrato.extrato = tbl_extrato_conferencia.extrato
				AND tbl_extrato_conferencia.cancelada IS NOT TRUE
				WHERE tbl_extrato.fabrica = {$login_fabrica}
				{$condData}
				{$condDataNf}
				{$condMes}
				{$condNfAnexada}
				{$condConferencia}
				{$condStatus}
				{$condPosto}
				{$limit}";

		$resSubmit = pg_query($con, $sql);
	}

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {

			$data = date("d-m-Y-H:i");

			$fileName = "relatorio-acompanhamento-conferencia-lgr-{$data}.csv";

			$file = fopen("/tmp/{$fileName}", "w");

			$thead = "Código Posto;Nome Posto;Extrato Mês;NF LGR anexada;Data Envio NF LGR;Data Conferência;Código Agrupador;NF M.O.;Previsão Pagamento;Status\n";

			fwrite($file, $thead);

			$body = "";
			while ($dados = pg_fetch_array($resSubmit)) {

				$codigo_posto  	    = $dados['codigo_posto'];
				$nome_posto    	    = $dados['nome'];
				$geracao            = $dados['geracao'];
				$nf_recebida        = $dados['nf_recebida'];
				$nf_anexada         = (empty($dados['data_envio'])) ? "Não" : "Sim";
				$nf_anexada         = ($nf_recebida == 't') ? "Sim" : $nf_anexada;
				$data_envio_nf      = $dados['data_envio'];
				$data_conferencia   = $dados['data_conferencia'];
				$codigo_agrupador   = $dados['codigo_agrupador'];
				$nf_mo              = $dados['nf_mo'];
				$previsao_pagamento = $dados['previsao_pagamento'];
				$status             = $dados['status'];

				$body .= "{$codigo_posto};{$nome_posto};{$geracao};{$nf_anexada};{$data_envio_nf};{$data_conferencia};{$codigo_agrupador};{$nf_mo};{$previsao_pagamento};{$status};\n";

			}

			fwrite($file, $body);

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}
}


$layout_menu = "financeiro";
$title = "Acompanhamento conferência LGR";
include 'cabecalho_new.php';


$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial", "data_inicial_nf", "data_final_nf"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$(document).on('click', ".agendar-pdf", function() {

			let extrato = $(this).data("extrato");

			$.ajax({
				type: "GET",
				url: "extrato_posto_mao_obra_novo_britania_pdf.php",
				async:false,
				data:"agendar=true&extrato="+extrato,
				complete: function (resultado){
					alert('Solicitação realizada com sucesso!');
				}
			});

		});

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }
</script>

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
						<label class='control-label' for='data_inicial'>Data Inicial (extrato)</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_inicial']?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final (extrato)</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$_POST['data_final']?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_nf", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial_nf'>Data Inicial (NF LGR anexo)</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_inicial_nf" id="data_inicial_nf" size="12" maxlength="10" class='span12' value= "<?=$_POST['data_inicial_nf']?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data_nf", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final_nf'>Data Final (NF LGR anexo)</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
								<input type="text" name="data_final_nf" id="data_final_nf" size="12" maxlength="10" class='span12' value="<?=$_POST['data_final_nf']?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Código Posto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Nome Posto</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("mes_extrato", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Mês extrato</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="mes_extrato" id="mes_extrato" class="span12">
								<option value=""></option>
								<?php
									$month = time();
									for ($i = 1; $i <= 12; $i++) {
									  $month = strtotime('last month', $month); 

									  $selected = ($_POST['mes_extrato'] == date('Y-m', $month)) ? "selected" : "";

									  ?>
									  <option value="<?= date('Y-m', $month) ?>" <?= $selected ?>><?= date('m/Y', $month) ?></option>
								<?php
									}

								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("nf_anexada", $msg_erro["campos"])) ? "error" : ""?>'>
					<div class='span1'></div>
					<strong>NF LGR anexada</strong>
					<br />
					<div class='span4'>
						 <label class="radio">
					        <input type="radio" name="nf_anexada" value="s" <?= ($nf_anexada == "s") ? "checked" : "" ?> />
					        Sim
					    </label>
					</div>
					<div class='span4'>
					    <label class="radio">
					        <input type="radio" name="nf_anexada" value="n" <?= ($nf_anexada == "n") ? "checked" : "" ?> />
					        Não
					    </label>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("conferencia_finalizada", $msg_erro["campos"])) ? "error" : ""?>'>
					<div class='span1'></div>
					<strong>Conferência Finalizada</strong>
					<br />
					<div class='span3'>
						 <label class="radio">
					        <input type="radio" name="conferencia_finalizada" value="s" <?= ($conferencia_finalizada == "s") ? "checked" : "" ?> />
					        Sim
					    </label>
					</div>
					<div class='span3'>
					    <label class="radio">
					        <input type="radio" name="conferencia_finalizada" value="n" <?= ($conferencia_finalizada == "n") ? "checked" : "" ?> />
					        Não
					    </label>
					</div>
					<div class='span2'></div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("status", $msg_erro["campos"])) ? "error" : ""?>'>
					<div class='span1'></div>
					<strong>Status</strong>
					<br />
					<div class='span4'>
						 <label class="radio">
					        <input type="radio" name="status" value="Finalizado" <?= ($status == "Finalizado") ? "checked" : "" ?> />
					        Finalizado
					    </label>
					</div>
					<div class='span4'>
					    <label class="radio">
					        <input type="radio" name="status" value="Aberto" <?= ($status == "Aberto") ? "checked" : "" ?> />
					        Em aberto
					    </label>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span12 tac">
				<input type="submit" name="pesquisa" value="Pesquisar" class="btn btn-default" id="btn_pesquisa" />
			</div>
		</div>
</form>
</div>

<?php
if (isset($resSubmit)) {
		if (pg_num_rows($resSubmit) > 0) {
			echo "<br />";

			if (pg_num_rows($resSubmit) > 500) {
				$count = 500;
				?>
				<div id='registro_max'>
					<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
				</div>
			<?php
			} else {
				$count = pg_num_rows($resSubmit);
			}
		?>
			<table id="tabela_conferencia" class='table table-bordered table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Código Posto</th>
						<th>Nome Posto</th>
						<th>Extrato (dia/mês)</th>
						<th>NF LGR</th>
						<th class="date_column">Data envio NF LGR</th>
						<th class="date_column">Data Conferência</th>
						<th>Código Agrupador</th>
						<th>NF M.O.</th>
						<th class="date_column">Previsão de Pagamento</th>
						<th>Status</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php
					while ($dados = pg_fetch_array($resSubmit)) {
			
						$codigo_posto  	    = $dados['codigo_posto'];
						$nome_posto    	    = $dados['nome'];
						$geracao            = $dados['geracao'];
						$nf_recebida        = $dados['nf_recebida'];
						$nf_anexada         = (empty($dados['data_envio'])) 	  ? "Não" : "Sim";
						$nf_anexada         = ($nf_recebida == 't') ? "Sim" : $nf_anexada;
						$data_envio_nf      = (empty($dados['data_envio'])) 	  ? "00/00/0000" : $dados['data_envio'];
						$data_conferencia   = (empty($dados['data_conferencia'])) ? "00/00/0000" : $dados['data_conferencia'];
						$codigo_agrupador   = $dados['codigo_agrupador'];
						$nf_mo              = $dados['nf_mo'];
						$previsao_pagamento = empty($dados['previsao_pagamento']) ? "00/00/0000" : $dados['previsao_pagamento'];
						$status             = $dados['status']; 
					?>
						<tr>
							<td><?= $codigo_posto  ?></td>
							<td><?= $nome_posto ?></td>
							<td class="tac"><?= $geracao ?></td>
							<td class="tac"><?= $nf_anexada ?></td>
							<td class="tac"><?= $data_envio_nf ?></td>
							<td class="tac"><?= $data_conferencia ?></td>
							<td class="tac"><?= $codigo_agrupador ?></td>
							<td ><?= $nf_mo ?></td>
							<td class="tac"><?= $previsao_pagamento ?></td>
							<td class="tac"><?= $status ?></td>
							<td class="tac">
								<input class="btn btn-primary agendar-pdf" data-extrato="<?= $dados['extrato'] ?>" type='button' value='Agenda geração PDF' />
							</td>
						</tr>
					<?php
						}
					?>
				</tbody>
			</table>
			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<input type="hidden" id="jsonPOST" value='<?php echo $jsonPOST ?>' />
            <div id='gerar_excel' class="btn_excel">
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Arquivo CSV</span>
            </div>
		<?php
		}else{
			echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
		}
	}


?>
<script>
    jQuery.extend(jQuery.fn.dataTableExt.oSort, {
        "currency-pre": function (a) {
            a = (a === "-") ? 0 : a.replace(/[^\d\-\.]/g, "");
            return parseFloat(a);
        },
        "currency-asc": function (a, b) {
            return a - b;
        },
        "currency-desc": function (a, b) {
            return b - a;
        }
    });

    var colunas = [];

    $("#tabela_conferencia th").each(function(){
        if ($(this).hasClass("date_column")) {
            colunas.push({"sType":"date"});
        } else if ($(this).hasClass("money_column")) {
            colunas.push({"sType":"numeric"});
        } else {
            colunas.push(null);
        }
    });

    $("td:contains('00/00/0000')").css({color: "white"});

	$.dataTableLoad({ table: "#tabela_conferencia", aoColumns:colunas });
</script>
<?php
include 'rodape.php';?>
