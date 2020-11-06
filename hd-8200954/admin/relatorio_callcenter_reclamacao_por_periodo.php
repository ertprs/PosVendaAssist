<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios.";

$layout_menu = "callcenter";
$title = "CALL-CENTER - RELATÓRIO DE RECLAMAÇÃO POR PERÍODO";

include 'cabecalho_new.php';

$sqlEstado = "SELECT estado, nome, regiao FROM tbl_estado WHERE visivel = 't' AND pais = 'BR'";
$resEstado = pg_query($con, $sqlEstado);
$arrayEstado = pg_fetch_all($resEstado);
$arrayRegiao = [];
$arrayReclamacao = [];
$arrayReclamacaoRegiao = [];

foreach ($arrayEstado as $key => $value) {
	$arrayRegiao[$value['regiao']] = $value['regiao'];
}

if (isset($_POST['pesquisar'])) {
	$data_inicial   = $_POST["data_inicial"];
    $data_final     = $_POST["data_final"];
    $estado 		= implode("','" , $_POST["estado"]);

    if(strlen($data_inicial)>0 && $data_inicial != "dd/mm/aaaa"){
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);
    }

    if(strlen($data_inicial)== 0 || strlen($data_final) == 0){
        $msg_erro["msg"][]    = "Informe um período.";
        $msg_erro["campos"][] = "data";
    }

    if(strlen($data_final)>0 && $data_final != "dd/mm/aaaa"){
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    }

    if (!count($msg_erro["msg"])) {
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }
    if (!count($msg_erro["msg"])) {
        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y)){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }

    if($xdata_inicial > $xdata_final) {
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data";
    }

    if (!empty($estado)) {
    	$whereEstado = "AND estado IN ('{$estado}')";
    }

	$sqlReclamacaoGroupEstado = "
		SELECT
			estado,
			regiao,
			COUNT(hd_chamado) AS TOTAL
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra USING(hd_chamado)
		JOIN tbl_cidade USING(cidade)
		JOIN tbl_estado USING(estado)
		WHERE data BETWEEN '{$xdata_inicial} 00:00:00' AND '{$xdata_final} 23:59:59'
		AND fabrica_responsavel = {$login_fabrica}
	   	AND categoria ~* 'reclamacao'
	   	AND tbl_estado.pais = 'BR'
	   	{$whereEstado}
		GROUP BY estado, regiao
		ORDER BY regiao;
	";
	$resReclamacaoGroupEstado = pg_query($con, $sqlReclamacaoGroupEstado);
	$arrayReclamacaoGroupEstado = pg_fetch_all($resReclamacaoGroupEstado);
    $count_res = pg_num_rows($resReclamacaoGroupEstado);
}

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "highcharts",
    "mask",
    "dataTable",
    "select2"
);

include("plugin_loader.php"); ?>

<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form name='frm_relatorio' method="POST" class="form-search form-inline tc_formulario">
<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Inicial</label>
					<div class='controls controls-row'>
						<div class='span10'>
							<h5 class='asteristico'>*</h5>
							<input 
								type="text" 
								name="data_inicial" 
								id="data_inicial" 
								size="12" 
								maxlength="10" 
								class="span12"
								value= "<?=$_POST['data_inicial']?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span10'>
							<h5 class='asteristico'>*</h5>
							<input 
								type="text" 
								name="data_final" 
								id="data_final" 
								size="12" 
								maxlength="10" 
								class="span12"
								value="<?=$_POST['data_final']?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("regiao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Região</label>
					<div class='controls controls-row'>
						<div class='span10'>
							<select 
								class="select2"
								name="regiao[]" 
								id="regiao" 
								class="span12"
								multiple="multiple">
								<?php
								foreach ($arrayRegiao as $key => $regiao) {
									$select = false;
									if (in_array($regiao, $_POST['regiao'])) {
										$select = "selected='selected'";
									}
									echo "<option value='{$key}' {$select}>{$regiao}</option>";
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("estado", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'>Estado</label>
					<div class='controls controls-row'>
						<div class='span10'>
							<select 
								class="select2"
								name="estado[]" 
								id="estado" 
								class="span12"
								multiple="multiple">
								<?php
								foreach ($arrayEstado as $estado) {
									$select = false;
									if (in_array($estado['estado'], $_POST['estado'])) {
										$select = "selected='selected'";
									}
									echo "<option value='{$estado['estado']}' data-regiao='{$estado['regiao']}' {$select}>{$estado['nome']}</option>";
								}
								?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	</div>
	<center>
		<input type="submit" class='btn' value="Pesquisar" name="pesquisar" alt="Preencha as opções e clique aqui para pesquisar">
	</center>
	<br />
</form>
<br />

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }
if ($count_res > 0 && count($msg_erro['msg']) == 0) { ?>
	<div id="grafico_regioes"></div>
	<br /><br />
    <table id="table_resultado_estado" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class="titulo_coluna">
                <th>Região</th>
                <th>Estado</th>
                <th>Quantidade</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $dadosGrafico = array();
            foreach ($arrayReclamacaoGroupEstado as $dados) {
            	$arrayReclamacaoRegiao[$dados['regiao']] += $dados['total'];
            	$dadosGrafico[] = array(
            		'name' => $dados['regiao'],
            		'y' => (int) $arrayReclamacaoRegiao[$dados['regiao']]
            	); ?>
                <tr>
                	<td><?=$dados['regiao']?></td>
                    <td><?=$dados['estado']?></td>
                    <td><?=$dados['total']?></td>
                </tr>
            <?php }

            $regioesJson = json_encode(array_keys($arrayReclamacaoRegiao));
            $xDadosJson = json_encode(
            	array(
            		'name' => 'Atendimentos',
		        	'colorByPoint' => 'true',
		        	'data' => $dadosGrafico
		        )
            ); ?>
        </tbody>
    </table>
    <table id="table_resultado_regiao" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class="titulo_coluna">
                <th>Região</th>
                <th>Quantidade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($arrayReclamacaoRegiao as $regiao => $total) { ?>
                <tr>
                	<td><?=$regiao?></td>
                    <td><?=$total?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } else if (isset($_POST['pesquisar']) && count($msg_erro['msg']) == 0) { ?>
    <div class="container">
        <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
        </div>
    </div>
<?php } ?>

<script type="text/javascript">
	$(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $('.select2').select2();
        $('#regiao').change(function(){
        	var arr_list = []
        	$('#regiao option:selected').each(function(){
        		arr_list.push($(this).text())
        	})
        	select_estado(arr_list)
        })
    });
    function select_estado(regiao){    	
    	$('[data-regiao]').each(function() {
    		if ($.inArray($(this).data('regiao'), regiao) !== -1){
    			$(this).prop('selected', 'selected')
    		}else{
    			$(this).prop('selected', false)
    		}
    	})
    	$('#estado').trigger('change')
    }
    <?php if ($count_res > 0 && count($msg_erro['msg']) == 0) { ?>
    	var xdata_inicial = '<?= $xdata_inicial; ?>';
    	var xdata_final = '<?= $xdata_final; ?>';

	    $("#grafico_regioes").highcharts({
	        chart: {
		        plotBackgroundColor: null,
		        plotBorderWidth: null,
		        plotShadow: false,
		        type: 'pie'
		    },
		    title: {
		        text: 'Atendimentos por Região'
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
		                format: '<b>{point.name}</b>: {point.percentage:.1f} %'
		            }
		        }
		    },
		    series: [<?= $xDadosJson; ?>]
	    });
	<?php } ?>

</script>

<?php include "rodape.php"; ?>