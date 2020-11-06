<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
require_once '../helpdesk/mlg_funciones.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$tDocs = new TDocs($con,$login_fabrica,'avulso');

$meses_campos = [1 =>'Janeiro',
      'Fevereiro',
      'Março',
      'Abril',
      'Maio',
      'Junho',
      'Julho',
      'Agosto',
      'Setembro',
      'Outubro',
      'Novembro',
      'Dezembro'];

function preparaSequenciaMeses($mes_inicio, $data_final_data){
	global $meses_campos; 

	for($m=$mes_inicio; $m<$data_final_data; $m++){
		$arr_mes[] = $meses_campos[$m];
	}
	return $arr_mes; 
}

function preparaData($ano_pesquisa, $mes_inicio, $mes_final){
	$data_incio = mktime(0, 0, 0, $mes_inicio, 1 , $ano_pesquisa);
	$data_fim = mktime(0, 0, 0, $mes_final, 0, date("Y"));
	$retorno['data_inicial'] = date('Y-m-d',$data_incio);
	$retorno['data_final'] = date('Y-m-d',$data_fim);
	return $retorno; 
}

if(isset($_POST['btnacao'])) {

	$ano_pesquisa      = $_POST["ano_pesquisa"];
	$mes_inicio      = $_POST["mes_inicio"];
    $mes_final        = $_POST["mes_final"];

    $mes_final_data = $mes_final + 1; 
    $pagos        = $_POST["pagos"];

    $mo        = $_POST["mo"];
    $km        = $_POST["km"];
    $pecas        = $_POST["pecas"];
    $avulsos        = $_POST["avulsos"];
 
    $arr = preparaData($ano_pesquisa, $mes_inicio, $mes_final_data);
    $arr_meses = preparaSequenciaMeses($mes_inicio, $mes_final_data);

    $meses_graficos = implode("','", $arr_meses);

	$data_inicial_pg = is_date($arr['data_inicial']);
    $data_final_pg   = is_date($arr['data_final']);


    if (empty($data_inicial_pg) || empty($data_final_pg)) {
    	$msg_erro["msg"][]    = "Preencha a data corretamente";
		$msg_erro["campos"][] = "data";
    }
    

	if($mo != 't' and $km != 't' and $pecas != 't' and $avulsos != 't'){
		$msg_erro['msg'][] = 'Informe um tipo de valor';
	}

	$campos[] = "data_geracao";
	$campos[] = " (select count(tbl_os_extra.os) from tbl_os_extra where extrato = tbl_extrato.extrato) as qtde_os ";

	//if($mo == 't'){
		$campos[] = 'sum(tbl_extrato.mao_de_obra) as mo';
		$campos2[] = 'sum(mo) as mo';
	// }
	// if($km == 't'){
		$campos[] = 'sum(tbl_extrato.deslocamento) as km';
		$campos2[] = 'sum(km) as km';
	// }	
	// if($pecas == 't'){
		$campos[] = 'sum(pecas) as pecas';
		$campos2[] = 'sum(pecas) as pecas';
	// }
	// if($avulsos == 't'){
		$campos[] = 'sum(avulso) as avulso';
		$campos2[] = 'sum(avulso) as avulso';
	//}	

	if(count(array_filter($msg_erro))==0){

		$sqlTemp = "SELECT EXTRACT(YEAR FROM data_geracao) as ano, EXTRACT(MONTH FROM data_geracao) as mes, tbl_extrato.extrato, ". implode(",", $campos) ." into temp grafico_extrato FROM tbl_extrato
					where fabrica = $login_fabrica
					and data_geracao between '$data_inicial_pg 00:00:00' and '$data_final_pg 23:59:59'
					group by ano, mes, tbl_extrato.extrato ";
		$resTemp = pg_query($con, $sqlTemp);

	    $sql = " SELECT ano, mes, count(qtde_os) as qtde_os, ".implode(",", $campos2)." from grafico_extrato group by ano, mes order by ano, mes";
	    $res = pg_query($con, $sql); 
	    $total_registro = pg_num_rows($res);

	$meses = $arr_meses;

	$thead = "";

	$thead[] = "";

	    for($i=0; $i<$total_registro; $i++){    	
	    	
	    	$totalGeral = ""; 
	    	$data_geracao 	= pg_fetch_result($res, $i, 'data_geracao');


	    	$thead[] = $meses[$i];

	    	//if($mo == 't'){
	    		$valor_mo 			= pg_fetch_result($res, $i, 'mo');
	    		$qtde_os 			= pg_fetch_result($res, $i, 'qtde_os');

	    		$arr_mo[] 			= number_format($valor_mo, 2, '.', '');
	    		$media 				= $valor_mo / $qtde_os; 

	    		$arr_media_mo[] 	= number_format($media, 2, '.', ''); 
	    	//}
	    	//if($km == 't'){
	    		$valor_km 			= pg_fetch_result($res, $i, 'km');
	    		$arr_km[] 		= number_format($valor_km, 2, '.', '');
	    	//}
	    	//if($pecas == 't'){
	    		$valor_pecas 			= pg_fetch_result($res, $i, 'pecas');
	    		$arr_pecas[] 	= number_format($valor_pecas, 2, '.', '');
	    	//}
	    	//if($avulsos == 't'){
	    		$avulso			= pg_fetch_result($res, $i, 'avulso');
	    		$arr_avulso[] 	= number_format($avulso, 2, '.', '');
	    	//}

	    	$totalGeral = $valor_mo+$valor_km+$valor_pecas+$avulso;

	    	$total_geral[] = number_format($totalGeral, 2, '.', '');
	    }

	    if ($_POST['gerar_excel']) {

			$data = date("d-m-Y-H:i");
			$fileName = "relatorio_extrato-{$login_fabrica}-{$data}.csv";
			$file = fopen("/tmp/{$fileName}", "w");
			$thead[] = ";\r\n";

			fwrite($file, implode(";", $thead));
			
			$tbody = ""; 

			if($mo == 't'){
				$tbody .= "Mão de Obra;"; 
				$tbody .= implode(";", $arr_mo).";\r\n";
			}

			if($km == 't'){
				$tbody .= "Km;"; 
				$tbody .= implode(";", $arr_km).";\r\n";
			}

			if($pecas == 't'){
				$tbody .= "Peças;"; 
				$tbody .= implode(";", $arr_pecas).";\r\n";
			}

			if($avulsos == 't'){
				$tbody .= "Avulsos;"; 
				$tbody .= implode(";", $arr_avulso).";\r\n";
			}			

			fwrite($file, $tbody);
			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				echo "xls/{$fileName}";
			}
			exit;
		}

		if ($_POST['gerar_excel_media']) {

			$data = date("d-m-Y-H:i");
			$fileName = "relatorio_extrato-media-{$login_fabrica}-{$data}.csv";
			$file = fopen("/tmp/{$fileName}", "w");
			$thead[] .= ";\r\n";

			fwrite($file, implode(";", $thead));
			
			$tbody = "Média de Mão de Obra;"; 

			$tbody .= implode(";", $arr_media_mo).";\r\n";			

			fwrite($file, $tbody);
			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				echo "xls/{$fileName}";
			}
			exit;
		}


		if ($_POST['gerar_excel_total']) {

			$data = date("d-m-Y-H:i");
			$fileName = "relatorio_extrato-total-{$login_fabrica}-{$data}.csv";
			$file = fopen("/tmp/{$fileName}", "w");
			$thead[] .= ";\r\n";

			fwrite($file, implode(";", $thead));
			
			$tbody = "Total Geral;";

			$tbody .= implode(";", $total_geral).";\r\n";

			fwrite($file, $tbody);
			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");
				echo "xls/{$fileName}";
			}
			exit;
		}
	}
}

$media_meses = array_sum($arr_media_mo) / count($arr_media_mo); 

$layout_menu = "financeiro";
$title = traduz("DASHBOARD FINANCEIRO");

include "cabecalho_new.php";

$plugins = array( 
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include "plugin_loader.php";

?>

<script language='javascript'>

<?php if(isset($_POST['btnacao']) and count(array_filter($msg_erro))==0 and $total_registro > 0){ ?>
	$(function(){
	Highcharts.chart('grafico', {
  chart: {
    type: 'column'
  },
  title: {
    text: 'Totais de Extratos'
  },
  subtitle: {
    text: 'Fonte: telecontrol.com.br'
  },
  xAxis: {
    categories: ['<?=$meses_graficos?>'],
    crosshair: true
  },
  yAxis: {
    
    title: {
      text: 'Valores'
    }
  },
  tooltip: {
    headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
      '<td style="padding:0"><b>{point.y:.1f} </b></td></tr>',
    footerFormat: '</table>',
    shared: true,
    useHTML: true
  },
  plotOptions: {
    column: {
      pointPadding: 0.2,
      borderWidth: 0
    }
  },
  series: [
<?php if($mo == 't'){ ?>
  {
    name: 'Mão de Obra',
    data: [<?= implode(',', $arr_mo) ?>]
  },
<?php } if($km == 't'){ ?>
   {
    name: 'KM',
    data: [<?= implode(",", $arr_km)?>]
  },
<? } if($pecas == 't'){?>
   {
    name: 'Peças',
    data: [<?= implode(",", $arr_pecas)?>]
  },
<?php } if($avulsos == 't'){?> 
  {
    name: 'Avulsos',
    data: [<?= implode(",", $arr_avulso)?>]
  },
<?php } ?>
  ]
});


Highcharts.chart('media', {
  title: {
    text: 'Média de Mão de Obra'
  },
  xAxis: {
    categories: ['<?=$meses_graficos?>']
  },
  labels: {
    items: [{
      //html: 'Média de Mão de Obra',
      style: {
        left: '50px',
        top: '18px',
        color: ( // theme
          Highcharts.defaultOptions.title.style &&
          Highcharts.defaultOptions.title.style.color
        ) || 'black'
      }
    }]
  },
  series: [{
    type: 'column',
    name: 'Media Mensal',
    data: [<?= implode(",", $arr_media_mo); ?>]
  }
  
  ]
});


Highcharts.chart('totalGeral', {
  title: {
    text: 'Total Geral'
  },
  xAxis: {
    categories: ['<?=$meses_graficos?>']
  },
  labels: {
    items: [{
      //html: 'Média de Mão de Obra',
      style: {
        left: '50px',
        top: '18px',
        color: ( // theme
          Highcharts.defaultOptions.title.style &&
          Highcharts.defaultOptions.title.style.color
        ) || 'black'
      }
    }]
  },
  series: [{
    type: 'column',
    name: 'Soma de: Mão de Obra, Km, Peças e Avulso',
    data: [<?= implode(",", $total_geral); ?>]
  }
  
  ]
});


$("#gerar_excel_total").click(function () {
	if (ajaxAction()) {
		if ($(this).hasClass("gerar_excel_total")) {
			var json = $.parseJSON($(this).find(".jsonPOST_total").val());
		} else {
			var json = $.parseJSON($("#jsonPOST_total").val());
		}
		
		json["gerar_excel_total"] = true;

		$.ajax({
			url: "<?=$_SERVER['PHP_SELF']?>",
			type: "POST",
			data: json,
			beforeSend: function () {
				loading("show");
			},
			complete: function (data) {
				window.open(data.responseText, "_blank");

				loading("hide");
			}
		});
	}
});

$("#gerar_excel_media").click(function () {
	if (ajaxAction()) {
		if ($(this).hasClass("gerar_excel_media")) {
			var json = $.parseJSON($(this).find(".jsonPOST_media").val());
		} else {
			var json = $.parseJSON($("#jsonPOST_media").val());
		}
		
		json["gerar_excel_media"] = true;

		$.ajax({
			url: "<?=$_SERVER['PHP_SELF']?>",
			type: "POST",
			data: json,
			beforeSend: function () {
				loading("show");
			},
			complete: function (data) {
				window.open(data.responseText, "_blank");

				loading("hide");
			}
		});
	}
});

	});

<?php } ?>

</script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		 $("#data_inicial").datepicker().mask("99/99/9999");
		 $("#data_final").datepicker().mask("99/99/9999");

		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
	});
</script>

<style type="text/css">
.highcharts-figure, .highcharts-data-table table {
  min-width: 310px; 
  max-width: 800px;
  margin: 1em auto;
}

#container {
  height: 400px;
}

.highcharts-data-table table {
	font-family: Verdana, sans-serif;
	border-collapse: collapse;
	border: 1px solid #EBEBEB;
	margin: 10px auto;
	text-align: center;
	width: 100%;
	max-width: 500px;
}
.highcharts-data-table caption {
  padding: 1em 0;
  font-size: 1.2em;
  color: #555;
}
.highcharts-data-table th {
	font-weight: 600;
  padding: 0.5em;
}
.highcharts-data-table td, .highcharts-data-table th, .highcharts-data-table caption {
  padding: 0.5em;
}
.highcharts-data-table thead tr, .highcharts-data-table tr:nth-child(even) {
  background: #f8f8f8;
}
.highcharts-data-table tr:hover {
  background: #f1f7ff;
}

</style>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div id="dados" class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>			
<form name="frm_relatorio" METHOD="POST" ACTION="<?= $PHP_SELF ?>" class='form-search form-inline'>
	<div class="tc_formulario">
		<div class='titulo_tabela'><?=traduz('Parâmetros de Pesquisa')?></div>
		<br />	
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span3'>
					<div class='control-group <?=(in_array("ano", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='ano_pesquisa'><?=traduz('Ano')?></label>
							<div class='controls controls-row'>
								<div class='span12'>
									<h5 class='asteristico'>*</h5>
									<select name="ano_pesquisa" class='span6'>
										<option value="">Selecione</option>
										<option value="2019" <?php if($ano_pesquisa == '2019') echo " selected ";?> >2019</option>
										<option value="2020" <?php if($ano_pesquisa == '2020') echo " selected ";?> >2020</option>
									</select>
								</div>
							</div>	
					</div>	
				</div>
				<div class='span3'>
					<div class='control-group <?=(in_array("mes_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'><?=traduz('Mês Inicial')?></label>
							<div class='controls controls-row'>
								<div class='span12'>
									<h5 class='asteristico'>*</h5>
									<select name="mes_inicio" class='span6'>
										<option value="">Selecione</option>
										<?php foreach($meses_campos as $chave => $mes){ 

											$selected = ($mes_inicio == $chave) ? " selected " : ""; 											

											echo "<option value='$chave' $selected >$mes</option>";
										} ?>
									</select>
								</div>
							</div>	
					</div>	
				</div>
				<div class='span3'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_final'><?=traduz('Mês Final')?></label>
						<div class='controls controls-row'>
							<div class='span12'>
								<h5 class='asteristico'>*</h5>
								<select name="mes_final" class='span6'>
									<option value="">Selecione</option>
									<?php foreach($meses_campos as $chave => $mes){ 
										$selected = ($mes_final == $chave) ? " selected " : ""; 											
										echo "<option value='$chave' $selected >$mes</option>";
									} ?>
								</select>
							</div>	
						</div>
					</div>	
				</div>		
			<div class='span2'></div>			
		</div>	
		<div class='row-fluid'>	
			<div class='span2'></div>
				<div class='span5'>
					<div class='control-group'>	
						<div class='controls controls-row'>
							<label class='control-label' for='codigo_posto'><?=traduz('Tipos.de.Valores')?></label>
							<div class='controls controls-row'>
								<div class="span12">
									<input type="checkbox" name="mo" value="t" <?php echo ($mo =='t')?" checked ": ""; ?> > Mão de Obra
									&nbsp
									<input type="checkbox" name="km" value="t" <?php echo ($km =='t')?" checked ": ""; ?>> KM
									&nbsp
									<input type="checkbox" name="pecas" value="t" <?php echo ($pecas =='t')?" checked ": ""; ?>> Peças
									&nbsp
									<input type="checkbox" name="avulsos" value="t" <?php echo ($avulsos =='t')?" checked ": ""; ?>> Avulsos
								</div>
							</div>		
						</div>	
					</div>
				</div>				
			<div class='span1'></div>			
		</div>

		<div class='row-fluid'>	
			<div class='span2'></div>			
			<div class='span8'>
				<center><input type="submit" name="btnacao" value="Consultar" class="btn"></center>
			</div>
			<div class='span2'></div>			
		</div>		
	</div>	
</form>
<?php 

if($total_registro>0 ){
?>
<div id="grafico"></div>
	<br><br>
	<?php
	$jsonPOST = excelPostToJson($_REQUEST);
	$jsonPOST = utf8_decode($jsonPOST);
	?>
	<div id='gerar_excel' class="btn_excel">
		<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>


<br><br>
<div id="media"></div>
<br><br>
	<?php
	$jsonPOST = excelPostToJson($_REQUEST);

	$jsonPOST = json_decode($jsonPOST, true);
	unset($jsonPOST['gerar_excel']);
	$jsonPOST['gerar_excel_media'] = true; 
	$jsonPOST = json_encode($jsonPOST);

	$jsonPOST = utf8_decode($jsonPOST);
	?>
	<div id='gerar_excel_media' class="btn_excel">
		<input type="hidden" id="jsonPOST_media" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>
	<br><br>



<div id="totalGeral"></div>
<br><br>
	<?php
	$jsonPOST = excelPostToJson($_REQUEST);

	$jsonPOST = json_decode($jsonPOST, true);
	unset($jsonPOST['gerar_excel']);
	$jsonPOST['gerar_excel_total'] = true; 

	$jsonPOST = json_encode($jsonPOST);
	$jsonPOST = utf8_decode($jsonPOST);
	?>
	<div id='gerar_excel_total' class="btn_excel">
		<input type="hidden" id="jsonPOST_total" value='<?=$jsonPOST?>' />
		<span><img src='imagens/excel.png' /></span>
		<span class="txt">Gerar Arquivo Excel</span>
	</div>
	<br><Br>

<?php

}elseif(isset($_POST['btnacao']) and count(array_filter($msg_erro))==0 and $total_registro == 0){
?>
	<div class="alert alert-warning">
		<h4><?=traduz('Nenhum resultado encontrado')?></h4>
	</div>	
<?php
}

?>


<?php include "rodape.php" ?>
