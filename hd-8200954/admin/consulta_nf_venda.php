<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

if ($areaAdmin === true) {
    include 'autentica_admin.php';
} else {
    include 'autentica_usuario.php';
}

include 'funcoes.php';

if ($_POST["btn_acao"] == "submit") {
    $data_inicial                   = $_POST["data_inicial"];
    $data_final                     = $_POST["data_final"];
    $numero_serie                   = trim($_POST["numero_serie"]);
    $codigo_posto    				= trim($_POST["codigo_posto"]);
    $descricao_posto 				= trim($_POST["descricao_posto"]);
    $nota_fiscal 					= trim($_POST["nota_fiscal"]);

	if (!empty($data_inicial) && !empty($data_final)) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = traduz("Data Inválida");
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = traduz("Data final não pode ser menor que a Data inicial");
				$msg_erro["campos"][] = "data";
			}

			if (strtotime($aux_data_inicial.'+1 month') < strtotime($aux_data_final) ) {
				$msg_erro["msg"][]    = traduz("O intervalo entre as datas não pode ser maior que 1 meses");
				$msg_erro["campos"][] = "data";
			}
		}
	}else{
		if (empty($nota_fiscal) AND empty($codigo_posto)){
			$msg_erro["msg"][]    = traduz("Favor Preencher os campos obrigatórios");
			$msg_erro["campos"][] = "data";
		}
	}

	if (!empty($nota_fiscal)){
		$cond_nota_fiscal = " AND tbl_venda.nota_fiscal = '{$nota_fiscal}' ";
	}

	if (strlen($codigo_posto) > 0 || strlen($descricao_posto) > 0) {
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(UPPER(fn_retira_especiais(tbl_posto.nome)) = UPPER(fn_retira_especiais('{$descricao_posto}')))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = traduz("Posto não encontrado");
			$msg_erro["campos"][] = "posto";
		} else {
            $posto = pg_fetch_result($res, 0, "posto");
            $wherePosto = "AND tbl_venda.posto = {$posto}";
        }
	}
	
	if (!count($msg_erro["msg"])) {
	
        if (!empty($aux_data_inicial) and !empty($aux_data_final)) {
            $whereData = "AND tbl_venda.data_nf BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59'";
        }

        $sql = "
            SELECT  DISTINCT 
                    tbl_posto_fabrica.codigo_posto AS posto_codigo,
                    tbl_posto.nome AS posto_nome,
                    tbl_posto.cnpj AS posto_cnpj,
                    tbl_venda.nota_fiscal,
                    TO_CHAR(tbl_venda.data_nf, 'DD/MM/YYYY') AS data_nf
            FROM    tbl_venda
       		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_venda.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
       		JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
            WHERE tbl_venda.fabrica = {$login_fabrica}
            $whereData
            {$wherePosto}
            {$cond_nota_fiscal}";
       	$resSubmit = pg_query($con, $sql);

		if (pg_last_error($con)) {
			$msg_erro["msg"][] =  traduz("Erro ao realizar pesquisa");
		}
	
		if ($_POST["gerar_excel"]) {
			if (pg_num_rows($resSubmit) > 0) {
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_nf_venda-{$data}.xls";

				$file = fopen("/tmp/{$fileName}", "w");
				$thead = "
					<table border='1'>
						<thead>
							<tr>
								<th colspan='4' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
									RELATÓRIO DE NF VENDAS
								</th>
							</tr>
							<tr>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Nota Fiscal</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Data Nota Fiscal</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente CNPJ</th>
								<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Cliente Nome</th>
							</tr>
						</thead>
						<tbody>
				";
				fwrite($file, $thead);

				while ($result = pg_fetch_object($resSubmit)) {
					$body .="
						<tr>
							<td nowrap align='center' valign='top'>{$result->nota_fiscal}</td>
							<td nowrap align='center' valign='top'>{$result->data_nf}</td>
							<td nowrap align='center' valign='top'>{$result->posto_cnpj}</td>
							<td nowrap align='center' valign='top'>{$result->posto_codigo} - {$result->posto_nome}</td>
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
}

if ($areaAdmin === true) {
    $layout_menu = "callcenter";
}

$title = traduz("Consulta Notas Fiscais de Venda");

include 'cabecalho_new.php';

$plugins = array(
    "mask",
    "maskedinput",
    "shadowbox",
    "dataTable",
    "datepicker",
    "autocomplete"
);

include "plugin_loader.php";
?>

<script>

$(function() {

	Shadowbox.init();
	$.datepickerLoad(["data_final", "data_inicial"]);

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("input[name=cnpjCpf]").change(function(){
        $("#consumidor_cpf").unmask();

        var tipo = $(this).val();

        if(tipo == 'cnpj'){
            $("#consumidor_cpf").mask("99.999.999/9999-99");
        }else{
            $("#consumidor_cpf").mask("999.999.999-99");
        }
    });

});

function retorna_posto(retorno) {
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}

function retorna_produto(retorno) {
	$("#produto_referencia").val(retorno.referencia);
	$("#produto_descricao").val(retorno.descricao);
}
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]) ?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios")?></b>
</div>

<form name='frm_consulta' method="POST" class="form-search form-inline tc_formulario" >

	<div class="titulo_tabela" ><?=traduz("Parâmetros de Pesquisa")?></div>

	<br/>

	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="data_inicial" ><?=traduz("Data emissão inicial")?></label>
				<div class="controls controls-row" >
					<div class="span12" >
						<h5 class="asteristico" >*</h5>
						<input type="text" name="data_inicial" id="data_inicial" class="span6" value="<?=$_POST['data_inicial']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span4">
			<div class="control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="data_final" ><?=traduz("Data emissão final")?></label>
				<div class="controls controls-row" >
					<div class="span12" >
						<h5 class="asteristico" >*</h5>
						<input type="text" name="data_final" id="data_final" class="span6" value="<?=$_POST['data_final']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<?php if ($areaAdmin === true) { ?>
		<div class="row-fluid">
			<div class="span2"></div>
			<div class="span4">
				<div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>" >
					<label class="control-label" for="codigo_posto"><?=traduz("Código do Cliente")?></label>
					<div class="controls controls-row" >
						<div class="span12 input-append" >
							<input type="text" name="codigo_posto" id="codigo_posto" class="span8" maxlength="20" value="<?=$_POST['codigo_posto']?>" />
							<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class="control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>" >
					<label class="control-label" for="descricao_posto"><?=traduz("Nome do Cliente")?></label>
					<div class="controls controls-row" >
						<div class="span12 input-append" >
							<input type="text" name="descricao_posto" id="descricao_posto" class="span12" maxlength="150" value="<?=$_POST['descricao_posto']?>" />
							<span class="add-on" rel="lupa" ><i class="icon-search" ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>
	<?php } ?>
    	<div class="row-fluid">
		<div class="span2"></div>
		<div class="span4">
			<div class="control-group <?=(in_array("nota_fiscal", $msg_erro["campos"])) ? "error" : ""?>" >
				<label class="control-label" for="nota_fiscal" ><?=traduz("Nota Fiscal")?></label>
				<div class="controls controls-row">
					<div class="span12">
						<input type="text" name="nota_fiscal" id="nota_fiscal" class="span8" maxlength="20" value="<?=$_POST['nota_fiscal']?>" />
					</div>
				</div>
			</div>
		</div>
		<div class="span2"></div>
	</div>
	<br />
	<p>
		<button type="submit" class="btn" name="btn_acao" onclick="submitForm($(this).parents('form'));" value="submit" ><?=traduz("Pesquisar")?></button>
	</p>
	<br />
</form>
</div>

<?php
if (isset($resSubmit)) {
	if (pg_num_rows($resSubmit) > 0) {
?>
		<table id="pesquisa_resultado" class="table table-striped table-bordered table-hover table-fixed">
			<thead>
				<tr class="titulo_coluna" >
					<th nowrap><?=traduz("Nota Fiscal")?></th>
					<th nowrap><?=traduz("Data Nota Fiscal")?></th>
					<th nowrap><?=traduz("Cliente CNPJ")?></th>
					<th nowrap><?=traduz("Cliente Nome")?></th>
				</tr>
			</thead>
			<tbody>
<?php
				while ($result = pg_fetch_object($resSubmit)) {
?>
					<tr class="venda-<?=$result->venda?>">
						<td nowrap><?=$result->nota_fiscal?></td>
						<td nowrap class='tac'><?=$result->data_nf?></td>
						<td nowrap class='tac'><?=$result->posto_cnpj?></td>
						<td nowrap><?=$result->posto_codigo?> - <?=$result->posto_nome?></td>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
		<script>
			$.dataTableLoad({ table: "#pesquisa_resultado" });
		</script>

		<?php
			$jsonPOST = excelPostToJson($_POST);
		?>
		<br/>
		<div id='gerar_excel' class="btn_excel">
			<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
			<span><img src='imagens/excel.png' /></span>
			<span class="txt">Gerar Arquivo Excel</span>
		</div>
<?php
	} else {
?>
		<div class="container" >
			<div class="alert alert-danger" >
		    	<h4>Nenhum resultado encontrado</h4>
			</div>
		</div>
	<?php
	}
}

include 'rodape.php';
?>
