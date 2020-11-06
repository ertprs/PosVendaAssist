<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "Pesquisar") {
    $data_inicial       = filter_input(INPUT_POST,'data_inicial');
    $data_final         = filter_input(INPUT_POST,'data_final');
    $codigo_posto       = filter_input(INPUT_POST,'codigo_posto');
    $descricao_posto    = filter_input(INPUT_POST,'descricao_posto');    

    if (strlen($codigo_posto) > 0 || strlen($descricao_posto) > 0){
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
			$condPosto = " AND   tbl_os.posto   = $posto ";
		}
	}

	if (!strlen($data_inicial) || !strlen($data_final)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	} else {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
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

		$sqlX = "SELECT '$aux_data_inicial'::date + interval '3 months' >= '$aux_data_final'";
		$resSubmitX = pg_query($con,$sqlX);
		$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
		if($periodo_6meses == 'f'){
			$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo três(3) meses";
			$msg_erro["campos"][] = "data";
		}
	}

	if (!count($msg_erro["msg"])) {
		
		$sql = " SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						tbl_os.os,
						tbl_os.serie,
						tbl_os.serie_reoperado,
						to_char (tbl_os.data_abertura,'DD/MM/YYYY')         AS data_abertura,
						to_char (tbl_os.finalizada,'DD/MM/YYYY')         AS fechamento
					FROM tbl_os
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto 
							AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
						JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto 
							AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_os.fabrica = {$login_fabrica}
						AND tbl_os.serie_reoperado IS NOT NULL
						AND tbl_os.finalizada BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
						{$condPosto}
		";
		$resSubmit = pg_query($con, $sql);
	}

	if(isset($_POST['gerar_excel'])){

		$data = date("d-m-Y-H:i");
		$filename = "relatorio-os-serie-{$data}.csv";
		$file = fopen("/tmp/{$filename}", "w");
		
		$head = "Código Posto;Descrição Posto;OS;Série;Série Anterior;Data Abertura;Data Finalização\r\n";

		fwrite($file, $head);

		for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
			$arq_cod_posto 			= pg_fetch_result($resSubmit, $i, codigo_posto);
			$arq_nome_posto 		= pg_fetch_result($resSubmit, $i, nome);
			$arq_os 				= pg_fetch_result($resSubmit, $i, os);
			$arq_serie 				= pg_fetch_result($resSubmit, $i, serie);
			$arq_serie_reoperado 	= pg_fetch_result($resSubmit, $i, serie_reoperado);
			$arq_data_abertura 		= pg_fetch_result($resSubmit, $i, data_abertura);
			$arq_fechamento 		= pg_fetch_result($resSubmit, $i, fechamento);

			unset($body);
			$body = "$arq_cod_posto;$arq_nome_posto;$arq_os;$arq_serie;$arq_serie_reoperado;$arq_data_abertura;$arq_fechamento\r\n";

			fwrite($file, $body);
		}
		
		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
			system("mv /tmp/{$filename} xls/{$filename}");

			echo "xls/{$filename}";
		}
		exit;
	}
}

/* ---------------------- */

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS COM NÚMERO DE SÉRIES ALTERADOS";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto"));
		Shadowbox.init();

		$("span[rel=lupa_posto]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

</script>

<?php
if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
} ?>

<div class="alert alert">
	<?php
		$meses = ($qtde_mes == 1) ? "mês" : "meses";
	?>
		<b>O período máximo para busca é de três(3) meses.</b>
</div>


<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' method='POST' id='series_alteradas' action="<?=$PHP_SELF?>" align='center' class="form-search form-inline tc_formulario">
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<br />
	<div class="container tc_container">
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
					<label class='control-label' for='descricao_posto'>Razão Social</label>
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
		<center>
			<input type='submit' name='btn_acao' value='Pesquisar' class='btn' />
		</center>
		<br />
		</div>
	</form>
</div>

<?php
if(isset($resSubmit)){
	if (pg_num_rows($resSubmit) > 0) {
	?>
		
		<table id='resultado' class="table table-striped table-bordered table-hover table-large">
			<thead>
				<tr class='titulo_coluna'>
					<th>Código Posto</th>
					<th>Descrição Posto</th>
					<th>OS</th>
					<th>Série</th>
					<th>Série Anterior</th>
					<th>Data Abertura</th>
					<th>Data Finalização</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < $count; $i++) {
					$tb_cod_posto 		= pg_fetch_result($resSubmit, $i, codigo_posto);
					$tb_nome_posto 		= pg_fetch_result($resSubmit, $i, nome);
					$tb_os 				= pg_fetch_result($resSubmit, $i, os);
					$tb_serie 			= pg_fetch_result($resSubmit, $i, serie);
					$tb_serie_reoperado = pg_fetch_result($resSubmit, $i, serie_reoperado);
					$tb_data_abertura 	= pg_fetch_result($resSubmit, $i, data_abertura);
					$tb_fechamento 		= pg_fetch_result($resSubmit, $i, fechamento);
					?>
					<tr>
						<td><?=$tb_cod_posto;?></td>
						<td><?=$tb_nome_posto;?></td>
						<td><?=$tb_os;?></td>
						<td><?=$tb_serie;?></td>
						<td><?=$tb_serie_reoperado;?></td>
						<td><?=$tb_data_abertura;?></td>
						<td><?=$tb_fechamento;?></td>
					</tr>
				<?php
				} ?>
			</tbody>
		</table>
		<br />
		<?php
		$jsonPOST = excelPostToJson($_POST); ?>
		<br />
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>
	<?php
	}else{ ?>
		<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}

include 'rodape.php';?>