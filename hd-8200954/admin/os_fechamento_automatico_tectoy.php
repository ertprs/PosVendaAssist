<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if($_POST["btn_acao"] == "submit") {
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];
	$codigo_posto       = $_POST['codigo_posto'];
	$descricao_posto    = $_POST['descricao_posto'];

	switch ($tipo_data) {
		case 'abertura':
			$campo_data = "tbl_os.data_abertura";
			break;
		case 'fechamento':
			$campo_data = "tbl_os.data_fechamento";
			break;

		default:
			$campo_data = "tbl_os.data_abertura";
			break;
	}

	if(strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
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
		}
	}

	if(!strlen($data_inicial) or !strlen($data_final)) {
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

			// $dat_inicial = explode("/", $data_inicial); // tira a barra
			// $y_inicial = $dat_inicial[2];


			if(strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -3 month')) {
				$msg_erro["msg"][]    = "O intervalo entre as datas não pode ser maior que 3 meses.";
				$msg_erro["campos"][] = "data";
			}

			$sql = " SELECT date_part('YEAR', timestamp '".date ('Y-m-d h:i:s',strtotime($aux_data_inicial))."' )  ";
			$res_ano_inicio = pg_query($con, $sql);
			$ano_incio	= pg_fetch_result($res_ano_inicio, 0, 'date_part');
			
			$sql = " SELECT date_part('YEAR', timestamp '".date ('Y-m-d h:i:s',strtotime($aux_data_final))."' )  ";
			$res_ano_final = pg_query($con, $sql);
			$ano_final	= pg_fetch_result($res_ano_final, 0, 'date_part');
			

			if($ano_incio < '2013' OR $ano_final > '2014'){
				$msg_erro["msg"][]    = "A pesquisa deve ser feita entre o perido de 2013 - 2014";
				$msg_erro["campos"][] = "data";
			}

		}
	}

	if(!count($msg_erro["msg"])){

		if(!empty($posto)) {
			$cond_posto = " AND tbl_posto_fabrica.codigo_posto = '{$codigo_posto}' ";
		}

		$sql = "SELECT
						tbl_os.os,
						tbl_os.sua_os,
						tbl_os_status.status_os,
						tbl_os.consumidor_nome,
						tbl_posto.nome,
						TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
						TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento
					FROM tbl_os
					JOIN tbl_os_status on tbl_os.os = tbl_os_status.os AND tbl_os.fabrica = tbl_os_status.fabrica_status
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_os_status.status_os = 89
					AND {$campo_data} BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
					{$cond_posto} 
					ORDER BY data_abertura DESC";
		$resSubmit = pg_query($con, $sql);
	

		if($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_os_finalizada-{$data}.xls";
				$file = fopen("/tmp/{$fileName}", "w");

				$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='9' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE OS FINALIZADA X 90 DIAS
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>OS</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Consumidor</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Abertura</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Fechamento</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
						</tr>
					</thead>
					<tbody>
					";
					fwrite($file, $thead);
							
					for ($i=0; $i < pg_num_rows($resSubmit); $i++) { 
						$os 							= pg_fetch_result($resSubmit, $i, 'os');
						$sua_os 					= pg_fetch_result($resSubmit, $i, 'sua_os');
						$data_abertura		= pg_fetch_result($resSubmit, $i, 'data_abertura');
						$data_fechamento	= pg_fetch_result($resSubmit, $i, 'data_fechamento');
						$consumidor_nome 	= pg_fetch_result($resSubmit, $i, 'consumidor_nome');
						$nome_posto 			= pg_fetch_result($resSubmit, $i, 'nome');
						
						$body.="  
							<tr>
								<td nowrap align='center' valign='top'>{$sua_os}</td>
								<td nowrap align='center' valign='top'>{$consumidor_nome}</td>
								<td nowrap align='center' valign='top'>{$data_abertura}</td>
								<td nowrap align='center' valign='top'>{$data_fechamento}</td>
								<td nowrap align='left' valign='top'>{$nome_posto}</td>
							</tr>
						";
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

				if(file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}

			}
			exit;
		}

	}
}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE OS Finalizada x 90 Dias";
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
		$.datepickerLoad(Array("data_final", "data_inicial"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
		var table = new Object();
    table['table'] = '#resultado_os_fechamento';
    table['type'] = 'full';
    $.dataTableLoad(table);
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
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
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
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>
</div>
<?php

if(count($msg_erro["msg"]) == 0) {
	if(isset($resSubmit) > 0){
		if(pg_num_rows($resSubmit) > 0){

			$count = pg_num_rows($resSubmit);
			echo '<br />';
	?>
			<table id="resultado_os_fechamento" class='table table-striped table-bordered table-hover table-large'>
				<thead>
					<tr class='titulo_coluna' >
						<th>OS</th>
						<th>Consumidor</th>
						<th>Abertura</th>
						<th>Fechamento</th>
						<th>Posto</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					for ($i=0; $i < $count; $i++) {
						$os 							= pg_fetch_result($resSubmit, $i, 'os');
						$sua_os 					= pg_fetch_result($resSubmit, $i, 'sua_os');
						$data_abertura		= pg_fetch_result($resSubmit, $i, 'data_abertura');
						$data_fechamento	= pg_fetch_result($resSubmit, $i, 'data_fechamento');
						$consumidor_nome 	= pg_fetch_result($resSubmit, $i, 'consumidor_nome');
						$nome_posto 			= pg_fetch_result($resSubmit, $i, 'nome');
						
						$body = "<tr>
										<td class='tac'><a href='os_press.php?os={$os}' target='_blank' >{$sua_os}</a></td>
										<td>{$consumidor_nome}</td>
										<td class='tac'>{$data_abertura}</td>
										<td class='tac'>{$data_fechamento}</td>
										<td>{$nome_posto}</td>
										</tr>";

					echo $body;

					}
					?>	
				</tbody>
			</table>
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
		}else {
			echo '
				<div class="container">
				<div class="alert">
					    <h4>Nenhum resultado encontrado</h4>
				</div>
				</div>';
		}
	}
}
include 'rodape.php';
?>
