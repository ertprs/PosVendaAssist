<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";

include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST["btn_pesquisa"])) {
	$data_inicial = $_POST["data_inicial"];
	$data_final   = $_POST["data_final"];
	$codigo_posto = $_POST["codigo_posto"];

	if (!empty($data_inicial) && !empty($data_final)) {
		$xdata_inicial = formata_data($data_inicial);
		$xdata_final   = formata_data($data_final);
	} else {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data";
	}

	if (!empty($codigo_posto)) {
		$sql = "SELECT posto 
				FROM tbl_posto_fabrica 
				WHERE codigo_posto = '$codigo_posto'
				AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$posto = pg_fetch_result($res, 0, 'posto');

			$cond_posto = " AND tbl_os.posto = $posto";
		} else {
			$msg_erro["msg"][]    = "Posto informado não encontrado";
			$msg_erro["campos"][] = "posto";
		}
	}

	if (count($msg_erro) == 0) {
		$sql = "SELECT DISTINCT ON (tbl_os.posto) 
					tbl_sms_resposta.resposta,
					tbl_os.posto,
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto.nome as descricao_posto, 
					(
						SELECT COUNT(o.os) 
						FROM tbl_os o
						JOIN tbl_sms s          ON s.os = o.os AND s.fabrica = $login_fabrica
						JOIN tbl_sms_resposta r ON r.sms = s.sms
						WHERE o.posto = tbl_os.posto
						AND (o.data_abertura BETWEEN '{$xdata_inicial}' AND '{$xdata_final}')
						AND o.fabrica = $login_fabrica
						AND (trim(r.resposta) ~ '^[0-9.-]+$')
					) AS qtde_os,
					(	
						SELECT SUM(r.resposta::int) 
						FROM tbl_sms_resposta r	
						JOIN tbl_sms s ON r.sms = s.sms AND s.fabrica = $login_fabrica
						JOIN tbl_os o ON s.os = o.os
						WHERE o.posto = tbl_os.posto
						AND (o.data_abertura BETWEEN '{$xdata_inicial}' AND '{$xdata_final}')
						AND o.fabrica = $login_fabrica
						AND (trim(r.resposta) ~ '^[0-9.-]+$')
					) AS total_resposta
				FROM tbl_os
				JOIN tbl_sms           ON tbl_sms.os = tbl_os.os AND tbl_sms.fabrica = $login_fabrica
				JOIN tbl_sms_resposta  ON tbl_sms_resposta.sms = tbl_sms.sms
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto         ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE (tbl_os.data_abertura BETWEEN '{$xdata_inicial}' AND '{$xdata_final}')
				$cond_posto
				AND tbl_os.fabrica = $login_fabrica
				AND trim(tbl_sms_resposta.resposta)  ~ '^[0-9.-]+$'
				GROUP BY tbl_sms_resposta.resposta,tbl_os.posto,tbl_posto_fabrica.codigo_posto,tbl_posto.nome";

		
		$resConsulta = pg_query($con, $sql);
		$pesquisar   = true;
	}

}

$layout_menu = "callcenter";
$title = "Desempenho do posto autorizado";

include 'cabecalho_new.php';

$plugins = array(
    "shadowbox",
    "datepicker",
    "mask",
    "dataTable",
    "shadowbox"
);

include("plugin_loader.php"); ?>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>

<script>

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
		$("#posto_id").val(retorno.posto);
    }
</script>
<div class="container">
    <? if (count($msg_erro["msg"]) > 0) { ?>
        <div class="alert alert-error">
            <h4><?= implode("<br />", $msg_erro["msg"]); ?></h4>
        </div>
    <? } ?>
    <div class="container">
        <strong class="obrigatorio pull-right"> * Campos obrigatórios</strong>
    </div>
    <form name='frm_relatorio' method='POST' action='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
    	<input type="hidden" id="posto_id" name="posto_id" value="<?= $_POST['posto_id'] ?>" />
        <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
        <br />
        <div class="row-fluid">
            <div class="span2"></div>
            <div class="span4">
                <div class="control-group <?= (in_array("data", $msg_erro["campos"])) ? "error" : ""; ?>">
                    <label class="control-label" for="data_inicial">Data Inicial</label>
                    <div class="controls controls-row">
                        <div class="span5">
                            <h5 class="asteristico">*</h5>
                            <input type="text" id="data_inicial" name="data_inicial" class="span12" maxlength="10" value="<?= $data_inicial; ?>" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4">
                <div class="control-group <?= (in_array("data", $msg_erro["campos"])) ? "error" : ""; ?>">
                    <label class="control-label" for="data_final">Data Final</label>
                    <div class="controls controls-row">
                        <div class="span5">
                            <h5 class="asteristico">*</h5>
                            <input type="text" id="data_final" value="<?= $data_final; ?>" name="data_final" class="span12" maxlength="10" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
            <div class="span2"></div>
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
        <p>
            <br/>
            <button class='btn' id="btn_acao" name="btn_pesquisa">Pesquisar</button>
        </p>
        <br/>  
    </form>
</div>
<?php 
if (pg_num_rows($resConsulta) > 0) {
?>
	<table class="table table-bordered table-fixed" id="tabela_postos">
		<thead>
			<tr class="titulo_coluna">
				<th>Posto</th>
				<th>Qtde. OS</th>
				<th>Nota Média</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
				<?php 
				for ($x=0;$x < pg_num_rows($resConsulta); $x++) {
					$posto             = pg_fetch_result($resConsulta, $x, 'posto');
					$nota    		   = pg_fetch_result($resConsulta, $x, 'resposta');
					$qtde_os 		   = pg_fetch_result($resConsulta, $x, 'qtde_os');
					$descricao_posto   = pg_fetch_result($resConsulta, $x, 'descricao_posto');
					$total_nota        = pg_fetch_result($resConsulta, $x, 'total_resposta');

					$media_notas_posto = ($total_nota / $qtde_os);

				?>
					<td>
						<?= $descricao_posto ?>
					</td>
					<td class="tac">
						<?= $qtde_os ?>
					</td>
					<td class="tac">
						<?= number_format($media_notas_posto, 2); ?>
					</td>
					<td class="tac">
						<button class="btn btn-primary btn_detalhar" type="button" data-posto="<?= $posto ?>">
							Detalhar
						</button>
					</td>
				</tr>
				<?php	
				}	

				if (empty($codigo_posto)) {
					$sqlGraficoGeral = "SELECT DISTINCT ON (tbl_sms_resposta.resposta)
													tbl_sms_resposta.resposta::int AS nota,
													COUNT(*) as total_de_notas
												FROM tbl_sms
												JOIN tbl_sms_resposta USING(sms) 
												WHERE tbl_sms.fabrica = {$login_fabrica}
												AND trim(tbl_sms_resposta.resposta)  ~ '^[0-9.-]+$'
												GROUP BY tbl_sms_resposta.resposta
												";
					$resGraficoGeral = pg_query($con, $sqlGraficoGeral);

					$array_nota = [];
					for ($i=0;$i < pg_num_rows($resGraficoGeral);$i++) {

						$array_nota[$i]["name"] = "Nota ".pg_fetch_result($resGraficoGeral, $i, "nota");
						$array_nota[$i]["y"]    = (int) pg_fetch_result($resGraficoGeral, $i, "total_de_notas");
					}
					$array_grafico = json_encode($array_nota);
				} ?>
		</tbody>
	</table>
<?php 
if (empty($codigo_posto)) { ?>
	<div id="grafico" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
	<script>

		$(function() {
			Highcharts.chart('grafico', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'Média Geral dos Postos'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: '<b>{point.name}</b>: {point.percentage:.1f} %',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                }
			            },
			            showInLegend: true
			        }
			    },
			    series: [{
			        name: 'Notas',
			        colorByPoint: true,
			        data: <?= $array_grafico ?>
			    }]
			});
		});

	</script>
	<?php 
	}
	?>
	<?php 
	} else if(count($msg_erro) == 0 && $pesquisar === true) { ?>
		<div class="alert alert-warning">
			<h4>Nenhum resultado encontrado</h4>
		</div>
    <?php
	} ?>
<script>
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("produto", "peca", "posto"));

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$(".btn_detalhar").click(function(){
		var posto = $(this).data("posto");
		var data_inicial    = $("#data_inicial").val();
		var data_final      = $("#data_final").val();

		Shadowbox.open({
            content: "os_por_nota_consumidor.php?posto="+posto+"&data_inicial="+data_inicial+"&data_final="+data_final,
            player: "iframe",
            title:  "Detalhes",
            width:  1600,
            height: 800
        });
	});

	$.dataTableLoad({ table: "#tabela_postos" });
</script>
<?php
include("rodape.php"); ?>
