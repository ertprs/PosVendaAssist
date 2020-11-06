<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$etiqueta           = $_POST['etiqueta'];
	$pedido             = $_POST['pedido'];
	if (strlen($etiqueta) == 0 && strlen($pedido) == 0) {
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
	}

	if (!count($msg_erro["msg"])) {
	
		if (strlen($pedido) > 0) {
			$cond = " AND tbl_pedido.pedido={$pedido}";
		} elseif (strlen($etiqueta) > 0) {
			$cond = " AND tbl_etiqueta_servico.etiqueta='{$etiqueta}'";
		}else {
			$cond = " AND tbl_pedido.data::DATE BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'";
		}

		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sql = "SELECT tbl_etiqueta_servico.*,
					   tbl_servico_correio.descricao,
					   tbl_pedido.pedido
				  FROM tbl_pedido
				  JOIN tbl_etiqueta_servico USING(etiqueta_servico,  fabrica)
				  JOIN tbl_servico_correio USING(servico_correio)
				 WHERE tbl_pedido.fabrica = {$login_fabrica}  
				{$cond}
				{$limit}";
		$resSubmit = pg_query($con, $sql);
	}
	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_custo_postagem-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$th_produto = "";
			$xcolspan = 6;
			
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='{$xcolspan}' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE CUSTOS DE POSTAGENS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Pedido</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Serviço</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Código Rastreio</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Preço</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Peso</th>
	                        <th bgcolor='#596D9B' color='#fff' style='color: #fff !important;' nowrap>Prazo de entrega</th>
						</tr>
					</thead>
			";
			fwrite($file, $thead);


			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {

				$preco 			= pg_fetch_result($resSubmit, $i, 'preco');
				$descricao		= pg_fetch_result($resSubmit, $i, 'descricao');
				$etiqueta		= pg_fetch_result($resSubmit, $i, 'etiqueta');
				$pedido			= pg_fetch_result($resSubmit, $i, 'pedido');
				$peso			= pg_fetch_result($resSubmit, $i, 'peso');
				$prazo_entrega	= pg_fetch_result($resSubmit, $i, 'prazo_entrega');

				$body .= "<tr>
							<td nowrap class='tac' style='vertical-align: middle;'><b>".$pedido."</b></td>
							<td nowrap class='tac' style='vertical-align: middle;'><b>".$descricao."</b></td>
							<td nowrap class='tac' style='vertical-align: middle;'><b>".$etiqueta."</b></td>
							<td nowrap class='tac' style='vertical-align: middle;'><b>R$ ".number_format($preco, 2, '.', '')."</b></td>
							<td nowrap class='tac' style='vertical-align: middle;'><b>".number_format($peso, 2, '.', '')."</b></td>
							<td nowrap class='tac' style='vertical-align: middle;'><b>".$prazo_entrega." dia(s)</b></td>
						</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='{$xcolspan}' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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

function geraDataNormal($data) {

	list($ano, $mes, $dia) = explode("-", $data);

	return $dia.'/'.$mes.'/'.$ano;
}
function mesPorExtenso($mes, $ano){

    switch ($mes){

        case 1: $mes = "Janeiro"; break;
        case 2: $mes = "Fevereiro"; break;
        case 3: $mes = "Março"; break;
        case 4: $mes = "Abril"; break;
        case 5: $mes = "Maio"; break;
        case 6: $mes = "Junho"; break;
        case 7: $mes = "Julho"; break;
        case 8: $mes = "Agosto"; break;
        case 9: $mes = "Setembro"; break;
        case 10: $mes = "Outubro"; break;
        case 11: $mes = "Novembro"; break;
        case 12: $mes = "Dezembro"; break;

    }

    $data_extenso = $mes . ' de ' . $ano;

    return $data_extenso;
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE CUSTOS DE POSTAGENS";
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
<style>
	.titulo_coluna2 th{
		background: #ccc !important;
	}
	.tal{
		text-align: left;
	}
</style>
<script type="text/javascript">
	var hora = new Date();
	var engana = hora.getTime();

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$(document).on("click", ".btn-ver-produtos", function() {
			var posicao  = $(this).data("posicao");
			if( $(".mostra_pd_"+posicao).is(":visible")){
			  $(".mostra_pd_"+posicao).hide();
			}else{
			  $(".mostra_pd_"+posicao).show();
			}
		});
	});

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
		<br/>
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

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("pedido", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='pedido'>Número do Pedido</label>
					<div class='controls controls-row'>
						<div class='span8'>
							<input type="text" name="pedido" id="pedido" class='span12' value="<? echo $pedido;?>" >
						</div>
					</div>
				</div>
			</div>

			<div class='span4'>
				<div class='control-group <?=(in_array("etiqueta", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='etiqueta'>Código de rastreio</label>
					<div class='controls controls-row'>
						<div class='span8'>
							<input type="text" name="etiqueta" id="etiqueta" class='span12' value="<? echo $etiqueta;?>" >
						</div>
					</div>
				</div>
			</div>

			<div class='span2'></div>
		</div>
		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php
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
			<table id="resultado_os_atendimento" class='table table-striped table-bordered table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<th nowrap>Pedido</th>
                        <th nowrap>Serviço</th>
                        <th nowrap>Código Rastreio</th>
                        <th nowrap>Preço</th>
                        <th nowrap>Peso</th>
                        <th nowrap>Prazo de entrega</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($i = 0; $i < $count; $i++) {
           				
						$preco 			= pg_fetch_result($resSubmit, $i, 'preco');
						$descricao		= pg_fetch_result($resSubmit, $i, 'descricao');
						$etiqueta		= pg_fetch_result($resSubmit, $i, 'etiqueta');
						$pedido			= pg_fetch_result($resSubmit, $i, 'pedido');
						$peso			= pg_fetch_result($resSubmit, $i, 'peso');
						$prazo_entrega	= pg_fetch_result($resSubmit, $i, 'prazo_entrega');
				

						$body = "<tr>
									<td nowrap class='tac' style='vertical-align: middle;'><a href='pedido_admin_consulta.php?pedido=".$pedido."' target='_blank' >".$pedido."</a></td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$descricao."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$etiqueta."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>R$ ".number_format($preco, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".number_format($peso, 2, '.', '')."</td>
									<td nowrap class='tac' style='vertical-align: middle;'>".$prazo_entrega." dia(s)</td>
								</tr>";
						echo $body;

					}
					?>
				</tbody>
			</table>

			<?php
			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado_os_atendimento" });
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

include 'rodape.php';?>
