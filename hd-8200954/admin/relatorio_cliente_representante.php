<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
	$xcodigo_posto       = $_POST['codigo_posto'];
	$xdescricao_posto    = $_POST['descricao_posto'];
	
	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "
			SELECT 
				tbl_posto_fabrica.posto,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.cnpj
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_tipo_posto.codigo = 'Rep'
			AND (
				(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$xcodigo_posto}'))
				OR
				(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$xdescricao_posto}'), 'LATIN-9'))
			)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$codigo_posto = pg_fetch_result($res, 0, "codigo_posto");
			$cnpj_posto = pg_fetch_result($res, 0, "cnpj");
			$cond_representante = "AND tbl_representante.codigo = '$codigo_posto' AND tbl_representante.cnpj = '$cnpj_posto'";
		}
	}

	if (!count($msg_erro["msg"])) {

		if (!empty($posto)) {
			$cond_posto = " AND tbl_os.posto = {$posto} ";
		}
		$limit = (!isset($_POST["gerar_excel"])) ? "LIMIT 501" : "";

		$sql = "
			SELECT
				tbl_representante.representante,
				tbl_representante.cnpj AS cnpj_representante,
				tbl_representante.codigo AS codigo_representante,
				tbl_representante.nome AS nome_representante,
				tbl_posto.cnpj AS cnpj_cliente,
				tbl_posto_fabrica.codigo_posto AS codigo_cliente,
				tbl_posto.nome AS nome_cliente
			FROM tbl_representante
			JOIN tbl_posto_fabrica_representante ON tbl_posto_fabrica_representante.representante = tbl_representante.representante
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_fabrica_representante.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE tbl_representante.fabrica = {$login_fabrica}
			{$cond_representante}";
		$resSubmit = pg_query($con, $sql);

		$array_dados = [];
		if (pg_num_rows($resSubmit) > 0){
			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$representante 		  = pg_fetch_result($resSubmit, $i, 'representante');
				$codigo_representante = pg_fetch_result($resSubmit, $i, 'codigo_representante');
				$cnpj_representante   = pg_fetch_result($resSubmit, $i, 'cnpj_representante');
				$nome_representante   = pg_fetch_result($resSubmit, $i, 'nome_representante');
				$cnpj_cliente         = pg_fetch_result($resSubmit, $i, 'cnpj_cliente');
				$codigo_cliente       = pg_fetch_result($resSubmit, $i, 'codigo_cliente');
				$nome_cliente         = pg_fetch_result($resSubmit, $i, 'nome_cliente');

				$array_dados[$representante]["info_revenda"] = array(
					"cnpj_representante" => $cnpj_representante,
					"codigo_representante" => $codigo_representante,
					"nome_representante" => $nome_representante,
				);

				$array_dados[$representante]["info_clientes"][] = array(
					"cnpj_cliente" => $cnpj_cliente,
					"codigo_cliente" => $codigo_cliente,
					"nome_cliente" => $nome_cliente,
				);
			}
		}
	}
	
	if ($_POST["gerar_excel"]) {
		if (pg_num_rows($resSubmit) > 0) {
			$data = date("d-m-Y-H:i");

			$fileName = "relatorio_cliente_representante-{$data}.xls";

			$file = fopen("/tmp/{$fileName}", "w");
			$thead = "
				<table border='1'>
					<thead>
						<tr>
							<th colspan='4' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
								RELATÓRIO DE REPRESENTANTES E CLIENTES
							</th>
						</tr>
						<tr>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ Representante</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Representante</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ Cliente</th>
							<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente</th>
						</tr>
					</thead>
					<tbody>
			";
			fwrite($file, $thead);

			for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {
				$representante 		  = pg_fetch_result($resSubmit, $i, 'representante');
				$codigo_representante = pg_fetch_result($resSubmit, $i, 'codigo_representante');
				$cnpj_representante   = pg_fetch_result($resSubmit, $i, 'cnpj_representante');
				$nome_representante   = pg_fetch_result($resSubmit, $i, 'nome_representante');
				$cnpj_cliente         = pg_fetch_result($resSubmit, $i, 'cnpj_cliente');
				$codigo_cliente       = pg_fetch_result($resSubmit, $i, 'codigo_cliente');
				$nome_cliente         = pg_fetch_result($resSubmit, $i, 'nome_cliente');

				$body .="
						<tr>
							<td nowrap align='center' valign='top'>{$cnpj_representante}</td>
							<td nowrap align='center' valign='top'>{$codigo_representante} - {$nome_representante}</td>
							<td nowrap align='center' valign='top'>{$cnpj_cliente}</td>
							<td nowrap align='center' valign='top'>{$codigo_cliente} - {$nome_cliente}</td>
						</tr>";
			}

			fwrite($file, $body);
			fwrite($file, "
						<tr>
							<th colspan='4' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
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

$layout_menu = "gerencia";
$title = "RELATÓRIO DE CLIENTES REPRESENTANTES";
include 'cabecalho_new.php';

$plugins = array(
	"shadowbox",
	"dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
	$(function() {
		Shadowbox.init();

		$(document).on("click", "span[rel=lupa_posto]", function() {
	        var parametros_lupa_produto = ["parametro", "page"];
	        $.lupa($(this), parametros_lupa_produto);
	    });
	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

    $(document).on("click", ".toogle-os", function() {
		var numero_os = $(this).attr('rel');
		if ($(".tr_"+numero_os).is(":visible")) {
			$(this).removeClass("icon-minus").addClass("icon-plus");
			$(".tr_"+numero_os).fadeOut("slow");
		} else {
			$(this).removeClass("icon-plus").addClass("icon-minus");
			$(".tr_"+numero_os).show(800);
		}
	});
</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php } ?>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
						<span class='add-on' rel="lupa_posto"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" page="relatorio_cliente_representante"/>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >
						<span class='add-on' rel="lupa_posto"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" page="relatorio_cliente_representante" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	
	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
		<button class='btn btn-success' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Listar todos</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

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
	<table id="resultado" class='table table-bordered table-fixed' >
		<thead>
			<tr class='titulo_coluna' >
				<th style="width: 15px;"></th>
				<th>CNPJ Representante</th>
				<th>Representante</th>
	    	</tr>
		</thead>
		<tbody>
		<?php foreach ($array_dados as $key => $value) { ?>
			<tr>
				<td class='tac'><i class='icon-plus toogle-os' rel='<?=$key?>' style='cursor:pointer; width: 15px;' title='Mostrar/Esconder Ordens Produto' ></i></td>
				<td class='tac'><?=$value["info_revenda"]["cnpj_representante"]?></td>
				<td><?=$value["info_revenda"]["codigo_representante"]?> - <?=$value["info_revenda"]["nome_representante"]?></td>
			</tr>
			<tr class='tr_<?=$key?>' style='display: none'>
				<td></td>
				<td class='titulo_coluna tac'>CNPJ Cliente</td>
				<td class='titulo_coluna'>Cliente</td>
			</tr>
			<?php foreach ($value['info_clientes'] as $cl => $clientes) { ?>
				<tr class='tr_<?=$key?>' style='display: none'>
					<td></td>
					<td class='tac'><?=$clientes['cnpj_cliente']?></td>
					<td><?=$clientes['codigo_cliente']?>-<?=$clientes['nome_cliente']?></td>
				</tr>
			<?php } ?>
		<?php } ?>
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
<?php include 'rodape.php';?>
