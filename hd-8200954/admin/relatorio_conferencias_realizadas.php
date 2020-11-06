<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios = "call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';

	if($_POST["btn_acao"] == "submit"){

		$data_inicial       = $_POST['data_inicial'];
	    $data_final         = $_POST['data_final'];
	    $codigo_posto 		= $_POST['codigo_posto'];
	    $descricao_posto  	= $_POST['descricao_posto'];
	    // $tipo_pedido_opt   	= $_POST['tipo_pedido'];
	    $faturamento_opt   	= $_POST['faturamento'];

	    if(strlen($data_inicial) == 0 || strlen($data_final) == 0){
	    	$msg_erro["msg"][]    ="Por favor insira as Datas";
	        $msg_erro["campos"][] = "data";
	    }else{

	    	if(strlen($data_inicial) > 0 && $data_inicial <> "dd/mm/aaaa"){
		        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		        $xdata_inicial = str_replace("'","",$xdata_inicial);
		    }else{
		        $msg_erro["msg"][]    ="Data Inicial Inválida";
		        $msg_erro["campos"][] = "data";
		    }

		    if(strlen($data_final) > 0 && $data_final <> "dd/mm/aaaa"){
		        $xdata_final =  fnc_formata_data_pg(trim($data_final));
		        $xdata_final = str_replace("'","",$xdata_final);
		    }else{
		        $msg_erro["msg"][]    ="Data Final Inválida";
		        $msg_erro["campos"][] = "data";
		    }

		    if($xdata_inicial > $xdata_final){
		    	$msg_erro["msg"][]    ="Data Inicial maior que final";
	        	$msg_erro["campos"][] = "data";
		    }

		    $data1 = new DateTime($xdata_inicial);
			$data2 = new DateTime($xdata_final);
			$qtde_dias = $data1->diff($data2)->format('%a');

			if($qtde_dias > 365){
		    	$msg_erro["msg"][]    ="O intervalo entre as datas não pode ser maior do que 1 ano";
	        	$msg_erro["campos"][] = "data";
		    }

		    if(count($msg_erro["msg"]) == 0){

		    	if(empty($_POST["gerar_excel"])){
		    		$limit = "LIMIT 500";
		    	}

		    	if($faturamento_opt == "ambos"){
		    		$cond_faturamento = "HAVING SUM(tbl_faturamento_item.qtde_quebrada) >= 0";
		    	}else if($faturamento_opt == "divergentes"){
		    		$cond_faturamento = "HAVING SUM(tbl_faturamento_item.qtde_quebrada) > 0";
		    	}else{
		    		$cond_faturamento = "HAVING SUM(tbl_faturamento_item.qtde_quebrada) = 0";
		    	}

		    	// $cond_tipo_pedido = "AND tbl_tipo_pedido.tipo_pedido = {$tipo_pedido_opt}";

		    	if(strlen($descricao_posto) > 0){
			        $sql = "SELECT posto FROM tbl_posto_fabrica JOIN tbl_posto using(posto) WHERE fabrica = $login_fabrica AND TRIM(nome) = '$descricao_posto' AND codigo_posto = '{$codigo_posto}'";
			        $res = pg_query($con, $sql);
			        $posto = pg_fetch_result($res, 0, "posto");
			        if(strlen($posto) > 0){
			            $cond_posto = " AND tbl_posto.posto = {$posto} ";
			        }
			    }

				// pg_query($con,"DROP TABLE relatorio_conferencia");

		    	$sql = "SELECT
				    tbl_faturamento.faturamento,
				    tbl_faturamento.nota_fiscal,
				    tbl_faturamento.serie,
				    TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS data_emissao,
				    tbl_faturamento.total_nota AS valor_total,
				    tbl_tipo_pedido.descricao AS tipo_pedido,
				    tbl_posto.nome AS posto_autorizado,
				    SUM(tbl_faturamento_item.qtde) AS qtde_faturada,
				    SUM(tbl_faturamento_item.qtde_quebrada) AS qtde_faltante
			    INTO TEMP relatorio_conferencia
				FROM tbl_faturamento
				INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_faturamento_item.pedido AND tbl_pedido.fabrica = $login_fabrica
				INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido 
					AND tbl_tipo_pedido.fabrica = $login_fabrica AND tbl_tipo_pedido.garantia_antecipada IS TRUE
				INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE tbl_faturamento.fabrica = {$login_fabrica}
				AND tbl_faturamento.emissao BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' 
				{$cond_posto} 
				GROUP BY
					tbl_faturamento.faturamento,
				    tbl_faturamento.nota_fiscal,
				    tbl_faturamento.serie,
				    tbl_faturamento.emissao,
				    tbl_faturamento.total_nota,
				    tbl_tipo_pedido.descricao,
				    tbl_posto.nome 
			    {$cond_faturamento}
				ORDER BY tbl_faturamento.emissao DESC, tbl_posto.nome ASC {$limit}";
				pg_query($con, $sql);

				if($faturamento_opt == "ambos"){
					$sql = "SELECT count(nota_fiscal) AS total_divergencia FROM relatorio_conferencia 
						WHERE qtde_faltante > 0";
					$res = pg_query($con,$sql);

					$total_divergencia = pg_fetch_result($res, 0, "total_divergencia");

					$sql = "SELECT count(nota_fiscal) AS total_nao_divergencia FROM relatorio_conferencia 
						WHERE qtde_faltante = 0";
					$res = pg_query($con,$sql);

					$total_nao_divergencia = pg_fetch_result($res, 0, "total_nao_divergencia");

					if($total_divergencia == ""){
						$total_divergencia = 0;
					}
					if($total_nao_divergencia == ""){
						$total_nao_divergencia = 0;
					}

		    		$sql = "SELECT sum(qtde_faturada) AS total_faturada, 
							sum(qtde_faltante) AS total_faltante 
	    				FROM relatorio_conferencia";
					$res = pg_query($con,$sql);

					$total_faturada = pg_fetch_result($res, 0, "total_faturada");
					$total_faltante = pg_fetch_result($res, 0, "total_faltante");

					if($total_faturada == ""){
						$total_faturada = 0;
					}
					if($total_faltante == ""){
						$total_faltante = 0;
					}
					$total_faturada = $total_faturada - $total_faltante;
		    	}
		    }
	    }
	}

	$layout_menu = "gerencia";
	$title = "RELATÓRIO DE CONFERÊNCIAS REALIZADAS";

	include "cabecalho_new.php";

	$plugins = array(
	    "autocomplete",
	    "datepicker",
	    "shadowbox",
	    "mask",
	    "dataTable"
	);

	include("plugin_loader.php");

?>
<style>
	.coluna {
		text-align: center;
	}
	.icon_excel {
		margin-left: 350px;
	}
	table.table, .accordion{
		margin-top: 10px;
	}
</style>
<script src="js/highcharts_4.1.5.js"></script>
<script type="text/javascript">
$(function() {
    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto", "posto"));

    Shadowbox.init();

    $("button[id^=btn_conferir_]").click(function(){
        var faturamento = this.id.replace(/\D/g, "");
        var nota_fiscal = $("#nf_"+faturamento).val();
        var serie = $("#serie_"+faturamento).val();

        $("input.faturamento").val(faturamento);
        
        Shadowbox.open({
            content: "visualizar_divergente.php?faturamento="+faturamento+"&nf="+nota_fiscal+"&serie="+serie,
            player: "iframe", 
            width: 900, 
            height: 500,

            options: {
                enableKeys: false
            }
        });
    });
    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    <?php if($_POST["btn_acao"] == "submit" AND $faturamento_opt == "ambos"){ ?>
    $('#conferencia_chart').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie',
            borderColor: '#CCC',
            borderWidth: 2
        },
        title: {
            text: ''
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y}</b>'
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
                }
            }
        },
        series:[{
            name: 'Total de Faturamento ',
            colorByPoint: true,
            data: [{
            	name: 'Divergentes', 
            	y: <?=$total_divergencia?>,
            	color: '#468847'
            }, {
            	name: 'Não Divergentes', 
            	y: <?=$total_nao_divergencia?>,
            	color: '#D05948'
            }]
        }]
    });
    $('#conferencia_peca_chart').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie',
            borderColor: '#CCC',
            borderWidth: 2
        },
        title: {
            text: ''
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.y}</b>'
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
                }
            }
        },
        series:[{
            name: 'Total de Peça ',
            colorByPoint: true,
            data: [{
            	name: 'Peças Recebidas', 
            	y: <?=$total_faturada?>,
            	color: '#468847'
            }, {
            	name: 'Peças Faltantes', 
            	y: <?=$total_faltante?>,
            	color: '#D05948'
            }]
        }]
    });
	<?php } ?>
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
	    <b class="obrigatorio pull-right">* Campos obrigatórios</b>
	</div>

	<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
	    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	    <br/>

		<div class='row-fluid'>
	        <div class='span2'></div>
	            <div class='span4'>
	                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
	                    <label class='control-label' for='data_inicial'>Data Inicial</label>
	                    <div class='controls controls-row'>
	                        <div class='span5'>
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
	                    <div class='span5'>
	                        <h5 class='asteristico'>*</h5>
	                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
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
	        <!-- <div class='span4'>
	            <div class='control-group '>
	                <label class='control-label' for='tipo_pedido'>Tipo de Pedido</label>
	                <div class='controls controls-row'>
	                    <div class='span4'>
	                        <select name="tipo_pedido" id="tipo_pedido">
	                        	<?php
	                        	// $sql_tipo_pedido = "SELECT tipo_pedido, descricao FROM tbl_tipo_pedido WHERE fabrica = {$login_fabrica}";
	                        	// $res_tipo_pedido = pg_query($con, $sql_tipo_pedido);

	                        	// if(pg_num_rows($res_tipo_pedido)){

	                        	// 	for ($i = 0; $i < pg_num_rows($res_tipo_pedido); $i++) { 
	                        	// 		$tipo_pedido = pg_fetch_result($res_tipo_pedido, $i, "tipo_pedido");
	                        	// 		$descricao = pg_fetch_result($res_tipo_pedido, $i, "descricao");

	                        	// 		$select_tipo_pedido = ($tipo_pedido == $tipo_pedido_opt) ? "SELECTED" : "";

	                        	// 		echo "<option value='".$tipo_pedido."' {$select_tipo_pedido} >".$descricao."</option>";
	                        	// 	}
	                        	// }
	                        	?>
	                        </select>
	                    </div>
	                    <div class='span2'></div>
	                </div>
	            </div>
	        </div> -->
	        <div class='span4'>
	            <div class='control-group '>
	                <label class='control-label' for='faturamento'>Faturamentos</label>
	                <div class='controls controls-row'>
	                    <div class='span4'>
	                        <select name="faturamento" id="faturamento">
	                            <option value="ambos" <?php echo ($faturamento_opt == "ambos") ? "SELECTED" : ""; ?> >Ambos</option>
	                            <option value="divergentes" <?php echo ($faturamento_opt == "divergentes") ? "SELECTED" : ""; ?> >Divergentes</option>
	                            <option value="nao_divergentes" <?php echo ($faturamento_opt == "nao_divergentes") ? "SELECTED" : ""; ?> >Não Divergentes</option>
	                        </select>
	                    </div>
	                    <div class='span2'></div>
	                </div>
	            </div>
	        </div>
	        <div class='span2'></div>
	    </div>

	    <p>
	    	<br />
        	<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        	<input type='hidden' id="btn_click" name='btn_acao' value='' />
    	</p>

    	<br />

	</form>

	<?php
	$sql = "SELECT * FROM relatorio_conferencia";
	$result = pg_query($con, $sql);
	if($_POST["btn_acao"] == "submit"){

		if(pg_num_rows($result) > 0){
			include_once "relatorio_conferencia_excel.php";
			if($faturamento_opt == "ambos"){
			?>
                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
                        <b>Notas Fiscais</b>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseOne" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="conferencia_chart" style="height: 350px; margin: 0 auto;"></div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="accordion">
                  <div class="accordion-group">
                    <div class="accordion-heading" style="background-color: #ebebeb">
                      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
                        <b>Peças</b>
                        <i class="icon-zoom-in pull-right"></i>
                      </a>

                    </div>
                    <div id="collapseOne" class="accordion-body collapse in">
                      <div class="accordion-inner">
                        <div id="conferencia_peca_chart" style="height: 350px; margin: 0 auto;"></div>
                      </div>
                    </div>
                  </div>
                </div>
			<?php
			}
			$cont = pg_num_rows($result);

			?>
			<table id="callcenter_relatorio_atendimento" class='table table-striped table-bordered table-hover table-large' style="min-width: 850px;">
    			<thead>
    				<tr class="titulo_coluna">
    					<th>Nota Fiscal</th>
    					<th>Série</th>
    					<th>Data Emissão</th>
    					<th>Valor Total</th>
    					<th>Tipo de Pedido</th>
    					<th>Posto</th>
    					<th>Quantidade Faturada</th>
    					<th>Quantidade Faltante</th>
    					<th>Peças Divergentes</th>
    				</tr>
    			</thead>
    			<tbody>
				<?php
					for ($i = 0; $i < $cont; $i++) {
						$faturamento   = pg_fetch_result($result, $i, "faturamento");
						$nota_fiscal   = pg_fetch_result($result, $i, "nota_fiscal");
						$serie         = pg_fetch_result($result, $i, "serie");
						$data_emissao  = pg_fetch_result($result, $i, "data_emissao");
						$valor_total   = pg_fetch_result($result, $i, "valor_total");
						$tipo_pedido   = pg_fetch_result($result, $i, "tipo_pedido");
						$posto         = pg_fetch_result($result, $i, "posto_autorizado");
						$qtde_faturada = pg_fetch_result($result, $i, "qtde_faturada");
						$qtde_faltante = pg_fetch_result($result, $i, "qtde_faltante");

						$valor_total = number_format($valor_total, 2);
					?>
					<tr>
						<td class="coluna"><?=$nota_fiscal?>
							<input type="hidden" id="nf_<?=$faturamento?>" value="<?=$nota_fiscal?>">
                        	<input type="hidden" id="serie_<?=$faturamento?>" value="<?=$serie?>">
                        </td>
						<td><?=$serie?></td>
						<td><?=$data_emissao?></td>
						<td><?=$valor_total?></td>
						<td><?=$tipo_pedido?></td>
						<td><?=$posto?></td>
						<td><?=$qtde_faturada?></td>
						<td><?=$qtde_faltante?></td>
						<td><?php if($qtde_faltante > 0){ ?>
	                        <button type="button" class="btn btn-small btn-link" data-loading-text="Aguarde..." id="btn_conferir_<?=$faturamento?>">Ver Peças</button>
	                    <?php }?></td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<?php
		}else{ ?>
			<div class='container'>
        		<div class='alert'>
                	<h4>Nenhum resultado encontrado</h4>
        		</div>
        	</div>
		<?php }
	}
	include "rodape.php";
	?>