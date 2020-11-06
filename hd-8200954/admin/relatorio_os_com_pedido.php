<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$admin_privilegios	= "gerencia";
$layout_menu 		= "gerencia";
$title 				= "OS COM PEDIDO";

## MESSAGE OF ERROR
$msg_erro = array();
$msgErrorPattern01 = "Preencha os campos obrigatórios.";
$msgErrorPattern02 = "Não foram encontrados registros no período indicado.";

if($btn_acao == "submit"){

	$tipo_relatorio  = $_POST['tipo_relatorio'];
	$data_inicial    = $_POST['data_inicial'];
	$data_final 	 = $_POST['data_final'];
	$codigo_posto    = $_POST['codigo_posto'];
	$descricao_posto = $_POST['descricao_posto'];

	$sql = "SELECT tbl_os.os, 
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome,
				tbl_os.sua_os,
				tbl_pedido.pedido, 
				to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao, 
				to_char (tbl_pedido.data,'DD/MM/YYYY')       AS data
			FROM tbl_os 
			JOIN tbl_os_produto    ON tbl_os_produto.os 	  = tbl_os.os
			JOIN tbl_os_item 	   ON tbl_os_item.os_produto  = tbl_os_produto.os_produto
			JOIN tbl_pedido 	   ON tbl_pedido.pedido 	  = tbl_os_item.pedido
			JOIN tbl_posto         ON tbl_posto.posto 	  	  = tbl_os.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_os.fabrica = ".$login_fabrica." AND tbl_os.excluida IS NOT TRUE
			AND tbl_pedido.fabrica = ".$login_fabrica." AND tbl_pedido.status_pedido <> 14
			AND tbl_posto_fabrica.fabrica = ".$login_fabrica."";

	if($codigo_posto != ""){
		$sql .= " AND tbl_posto_fabrica.codigo_posto = '".$codigo_posto."' ";
	}
	if($descricao_posto != ""){
		$sql .= " AND tbl_posto_fabrica.codigo_posto = '".$codigo_posto."' ";
	}

	$data = explode("/",$data_inicial);
	$dtInicial = $data[2]."-".$data[1]."-".$data[0];

	$data = explode("/",$data_final);
	$dtFinal = $data[2]."-".$data[1]."-".$data[0];

	if($tipo_relatorio == "OS"){
		$sql .= " AND tbl_os.data_digitacao BETWEEN '".$dtInicial." 00:00:00' and '".$dtFinal." 23:59:59' GROUP BY tbl_os.os, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_pedido.pedido, tbl_os.data_digitacao, tbl_pedido.data ORDER BY tbl_os.data_digitacao";
		// $sql .= " AND tbl_os.data_digitacao BETWEEN '".$dtInicial." 00:00:00' and '".$dtFinal." 23:59:59' ORDER BY tbl_os.data_digitacao";
	}else{
		$sql .= " AND tbl_pedido.data BETWEEN '".$dtInicial." 00:00:00' and '".$dtFinal." 23:59:59' GROUP BY tbl_os.os, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_pedido.pedido, tbl_os.data_digitacao, tbl_pedido.data ORDER BY tbl_pedido.data"; 
		// $sql .= " AND tbl_pedido.data BETWEEN '".$dtInicial." 00:00:00' and '".$dtFinal." 23:59:59' ORDER BY tbl_pedido.data";
	}

	$resSubmit = pg_query($con,$sql);

	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_os_com_pedido-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='11' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE OS COM PEDIDO
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Código Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data de OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Pedido</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Pedido</th>
						</tr>
					</thead>
					<tbody>";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {

				$codigo_posto       = pg_fetch_result($resSubmit, $i, 'codigo_posto');
				$nome_posto         = pg_fetch_result($resSubmit, $i, 'nome');
				$sua_os                 = pg_fetch_result($resSubmit, $i, 'sua_os');
				$data_digitacao     = pg_fetch_result($resSubmit, $i, 'data_digitacao');
				$pedido      		= pg_fetch_result($resSubmit, $i, 'pedido');
				$data    			= pg_fetch_result($resSubmit, $i, 'data');

				$body .="
					<tr>
						<td nowrap align='center' valign='top'>{$codigo_posto}</td>
						<td nowrap align='center' valign='top'>{$nome_posto}</td>
						<td nowrap align='center' valign='top'>{$sua_os}</td>
						<td nowrap align='center' valign='top'>{$data_digitacao}</td>
						<td nowrap align='center' valign='top'>{$pedido}</td>
						<td nowrap align='center' valign='top'>{$data}</td>
					</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='9' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
						</tr>
					</tbody>
				</table>
			");

			fclose($file);

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		}

		exit;
	}
}


include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
    "mask",
	"dataTable"
);
// $plugins = array( "dataTable" );

include("plugin_loader.php");

?>


<?php
if(count($_POST)>0){
	$dtInicial = trim($_POST['data_inicial']);
	$dtFinal = trim($_POST['data_final']);

	if (strlen($dtInicial)==0 || strlen($dtFinal)==0)
	{
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = "data";
	}
}
?>

<?php if (count($msg_erro["msg"]) > 0) {	?>
	<div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php 	}	?>


<script src="js/novo_highcharts.js"></script>
<script src="js/modules/exporting.js"></script>

<script>
	$(function(){
	
		Shadowbox.init();

		// $.datepickerLoad(Array("data_final", "data_inicial"));

		$('#data_inicial').mask("99/99/9999");
		$('#data_final').mask("99/99/9999");

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$(".list_os").click(function(){
			var data = $(this).attr("rel");
			var posto = $(this).attr("posto");

			Shadowbox.open({
				content: "listar_prdutividade_os_reparo.php?data="+data+"&posto="+posto,
				player: "iframe",
				width: 1800,
				height: 800
			});

		});

		// $('#container').highcharts({
	 //        chart: {
	 //            type: 'line'
	 //        },
	 //        title: {
	 //            text: 'RELATÓRIO DE OS COM PEDIDO '
	 //        },
	 //        subtitle: {
	 //            text: 'Período: <?=$options_mes[$mes]?> de <?=$ano?>'
	 //        },
	 //        xAxis: {
	 //            categories: [<?=$meta_grafico?>]
	 //        },
	 //        yAxis: {
	 //            title: {
	 //                text: 'OS com pedido'
	 //            }
	 //        },
	 //        plotOptions: {
	 //            line: {
	 //                dataLabels: {
	 //                    enabled: true
	 //                },
	 //                enableMouseTracking: false
	 //            }
	 //        },
	 //        series: [{
	 //            name: 'Prdodução',
	 //            data: [<?=$producao_grafico?>]
	 //        }, {
	 //            name: 'Meta',
	 //            data: [<?=$producao_meta?>]
	 //        }]
	 //    });

		$("#data_inicial").datepicker({
			changeMonth: true,
      		changeYear: true,

			dateFormat: 'dd/mm/yy',
		    dayNames: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'],
		    dayNamesMin: ['D','S','T','Q','Q','S','S','D'],
		    dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb','Dom'],
		    monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
		    monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
		    nextText: 'Próximo',
		    prevText: 'Anterior'
		});

		$("#data_final").datepicker({
			changeMonth: true,
			changeYear: true
	    });

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

    function retorna_produto (retorno) {
		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
	}
</script>

<div class="container">
	<div class="row"> <b class="obrigatorio pull-right">  * Campos obrigatórios </b> </div>
	<form name='frm_os_com_pedido' class="form-search form-inline tc_formulario" action='<? echo $PHP_SELF ?>' method='post'>
		<div class="titulo_tabela">Parâmetros de Pesquisa</div>
		<br />
		<div class="container tc_container">

			<div class='row-fluid'>
				<div class='span2'></div>

				<div class='span4' style="width: 250px;">
					<div class='control-group>'>
						<label class='control-label' for='Tipo'>Tipo de Relatório</label>
						<div class='controls controls-row'>
							<div class='span12' style="width: 300px;">

								<input type='radio' name='tipo_relatorio' style="margin: 10px;" value='OS'<?if($tipo_relatorio=="OS") echo "CHECKED"; else if($tipo_relatorio=="") echo "CHECKED"?>>Digitação OS
                    			<input type='radio' name='tipo_relatorio' style="margin: 10px;" value='P'<?if($tipo_relatorio=="P") echo "CHECKED";?>>Digitação Pedido

								<!-- <select name="cbTipoRelatorio" style="width: 150px;">
									<option value="1" selected >Digitação OS</option>
									<option value="2" >Digitação Pedido</option>
								</select> -->
							</div>
						</div>
					</div>
				</div>

				<div class='span4' style="width: 120px;">
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<input type="text" style="width: 120px;" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?php echo $data_inicial; ?>">
							</div>
						</div>
					</div>
				</div>
				<div class='span4' style="width: 120px;">
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_final'>Data Final</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
								<input type="text" style="width: 120px;" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?php echo $data_final; ?>" >
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="container tc_container">
				<div class='row-fluid'>

					<div class='span2'></div>

					<div class="span4" style="width: 250px;">
						<div class='control-group'>
							<label class='control-label' for='codigo_posto'>Código Posto</label>
							<div class='controls controls-row'>
								<div class='span7 input-append'>
									<input type="text" style="width: 200px;" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
									<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
								</div>
							</div>
						</div>
	                </div>
					<div class='span4'>
						<div class='control-group'>
							<label class='control-label' for='descricao_posto'>Nome do Posto</label>
	                        <div class='controls controls-row'>
	                            <div class='span12 input-append'>
									<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
									<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
									<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
								</div>
	                        </div>
						</div>
	                </div>
	                <div class='span2'></div>
	            </div>
	        </div>

			<br/>
	        <center>	
				<button  type="button" class='btn' id="btn_acao" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
				<input type='hidden' id="btn_click" name='btn_acao' value='' /></p>
			
				<!-- <input type='submit' name='btn_gravar' value='Pesquisar' clas<!-- s='btn' />
				<input type='hidden' name='acao' value="<? echo $acao; ?>" />  -->
			</center>
			<br />

		</div>
	</form>
</div>

<?php
	
	if($btn_acao == "submit"){

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
			<table align="center" id="resultado_os_com_pedido" class='table table-striped table-bordered table-hover table-large' >
				<thead>
					<tr class='titulo_coluna' >
						<th>Cód. Posto</th>
						<th>Posto</th>
						<th>OS</th>
						<th>Data da OS</th>
						<th>Pedido</th>
						<th>Data do Pedido</th>
					</tr>
                </thead>
				<tbody>
		<?php

    		for ($x = 0 ; $x < $count ; $x++){
    			
		        echo " <tr>
		        	<td class='tac'>".trim(pg_fetch_result($resSubmit,$x,codigo_posto))."</td>
		        	<td class='tac'>".trim(pg_fetch_result($resSubmit,$x,nome))."</td>
				<td class='tac'>".trim(pg_fetch_result($resSubmit,$x,sua_os))."</td>
			        <td class='tac'>".trim(pg_fetch_result($resSubmit,$x,data_digitacao))."</td>
			        <td class='tac'>".trim(pg_fetch_result($resSubmit,$x,pedido))."</td>
			        <td class='tac'>".trim(pg_fetch_result($resSubmit,$x,data))."</td>
		        </tr>";

		    }
		?>
				</tbody>
			</table>

			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os_com_pedido", type:"full" });
				</script>
			<?php
			}
			?>

			<br />

			<?php
				$jsonPOST = excelPostToJson($_POST);
			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
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

		?>
			<br />
			<!-- <div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div> -->
		<?php

	}

/* Rodapé */
	include 'rodape.php';
?>
