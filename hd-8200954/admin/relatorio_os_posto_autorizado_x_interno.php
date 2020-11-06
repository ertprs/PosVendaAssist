<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if (isset($_POST['pesquisar'])) {
	$data_inicial   = $_POST["data_inicial"];
    $data_final     = $_POST["data_final"];

    $xdata_inicial = formata_data($_POST['data_inicial']);
    $xdata_final   = formata_data($_POST['data_final']);

	$sql = "SELECT DISTINCT 
				tbl_hd_chamado.hd_chamado, 
				tbl_hd_chamado.status, 	
				tbl_posto.nome,				
				tbl_tipo_posto.descricao as tipo_descricao
			FROM tbl_hd_chamado
            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
			JOIN tbl_posto_fabrica ON tbl_hd_chamado_extra.posto = tbl_posto_fabrica.posto
			AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			AND tbl_tipo_posto.fabrica = {$login_fabrica}
			WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
        	AND tbl_hd_chamado.data BETWEEN '{$xdata_inicial} 00:00:00' AND '{$xdata_final} 23:59:59'";

    $sql_grafico = "SELECT tipo_posto.descricao as tipo_descricao, 
    			COUNT(hd_chamado.hd_chamado) as total
			FROM tbl_hd_chamado as hd_chamado
            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = hd_chamado.hd_chamado
				JOIN tbl_posto_fabrica as posto_fabrica
					ON tbl_hd_chamado_extra.posto = posto_fabrica.posto
						AND posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_tipo_posto as tipo_posto
					ON tipo_posto.tipo_posto = posto_fabrica.tipo_posto
						AND tipo_posto.fabrica = {$login_fabrica}
			WHERE 
				hd_chamado.fabrica = $login_fabrica
        		AND hd_chamado.data BETWEEN '{$xdata_inicial}' AND '{$xdata_final}' 
        	GROUP BY 
        		tipo_posto.descricao";

    $res = pg_query($con, $sql);
    $count_res = pg_num_rows($res);
    $array_dados = pg_fetch_all($res);

    $res_grafico = pg_query($con, $sql_grafico);
    $array_dados_grafico = pg_fetch_all($res_grafico);
    $total_atendimentos = 0;
    $array_formatado_para_grafico = [];

    foreach ($array_dados_grafico as $dados) {
    	$total_atendimentos += $dados['total'];    	
    }
    foreach ($array_dados_grafico as $dados) {
    	$array_formatado_para_grafico[] = [
    		'name' => utf8_encode($dados['tipo_descricao']),
    		'y' => (float) number_format(($dados['total']*100)/$total_atendimentos, 2)
    	];
    }
    $json_dados_grafico = json_encode($array_formatado_para_grafico);
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE INDICAÇÃO POSTOS AUTORIZADOS X INTERNO";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "highcharts_v7",
    "mask",
    "dataTable"
);

include("plugin_loader.php");
?>
<script type="text/javascript">
	$(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
    });
</script>
<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
<form class="form-search form-inline tc_formulario" name="frm_pesquisa" method="POST">
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
								required="required"
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
								required="required"
								value="<?=$_POST['data_final']?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	</div>
	<br />
	<center>
		<input type="submit" class='btn' value="Pesquisar" name="pesquisar" alt="Preencha as opções e clique aqui para pesquisar">
	</center>
	<br />
</form>
<?php if ($count_res > 0){ ?>
	<table id="table_total" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class="titulo_coluna">
                <th>Tipo do Posto</th>
                <th>Atendimentos</th>
                <th>Porcentagem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($array_dados_grafico as $dados) { ?>
                    <tr>
                        <td><?=$dados['tipo_descricao']?></td>
                        <td><?=$dados['total']?></td>
                        <td><?= number_format(($dados['total']*100)/$total_atendimentos, 2) ?> %</td>
                    </tr>
            <?php } ?>
             <tr>
                <td colspan="3" style="text-align: right;">
                	Total: <?=$total_atendimentos?>
                </td>
            </tr>
        </tbody>
    </table>
    <center>
    	<input type="button" class='btn btn-success' value="Ver Grafico" id="ver_grafico" >
    </center>
    <table id="table_resultado" class='table table-striped table-bordered table-hover table-fixed' >
        <thead>
            <tr class="titulo_coluna">
                <th>Atendimento</th>
                <th>Posto</th>
                <th>Tipo do posto</th>
                <th>Status Atendimento</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($array_dados as $dados) { ?>
                    <tr>
                        <td>
                        	<a href='callcenter_interativo_new.php?callcenter=<?=$dados['hd_chamado']?>'
                        		target='_blank'>
                        			<?=$dados['hd_chamado']?>                        		
                        	</a>
                        </td>
                        <td><?=$dados['nome']?></td>
                        <td><?=$dados['tipo_descricao']?></td>
                        <td><?=$dados['status']?></td>
                    </tr>
            <?php } ?>
        </tbody>
    </table>
<?php }elseif(isset($_POST['pesquisar'])){ ?>
    <div class="container">
        <div class="alert">
            <h4>Nenhum resultado encontrado</h4>
        </div>
    </div>
<?php } ?>
<div id="modal_grafico" class="modal hide fade" data-backdrop="static" style="width: 50%;" data-keyboard="false" >
    <div class="modal-body">
		<div id="container" style="height: 400px; width: 600px;"></div>
	</div>
	<div class="modal-footer">
        <button type="button" id="close_modal_grafico" class="btn">Fechar</button>
    </div>
</div>
<script type="text/javascript">
    $.dataTableLoad({ table: "#table_resultado" });
    // --- Inicio script modal ---//
    
    $("#ver_grafico").on("click", function() {
        console.log(<?=$json_dados_grafico?>);
        Highcharts.chart('container', {
	        chart: {
	            type: 'pie'
	        },
	        title: {
	            text: 'Indicações por Tipo de posto'
	        },
	        subtitle: {
	            text: 'Indicações de Atendimento por tipo de posto'
	        },
	        series: [
	            {
	                name: "Atendimentos",
	                colorByPoint: true,
	                data: <?=$json_dados_grafico?>
	            }
	        ]
	    });
	    let modal_grafico  = $("#modal_grafico");
        $(modal_grafico).modal("show");
    });
    $("#close_modal_grafico").on("click", function(){
        let modal_grafico  = $("#modal_grafico");
        $(modal_grafico).modal("hide");
    });
    //---Fim Script modal ---//
</script>