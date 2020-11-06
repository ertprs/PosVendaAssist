<?php
	
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
include "autentica_admin.php";
if($_POST['btn_acao']=="pesquisar"){

	
	//valida posto	
	
	if((strlen($_POST["codigo_posto"]) > 0) || (strlen($_POST["descricao_posto"]) > 0)){
		$codigo_posto = $_POST["codigo_posto"];
		$descricao_posto = $_POST["descricao_posto"];

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


	
	// valida nro serie
	if(strlen($_POST["produto_serie"]) > 0){
		$produto_serie = $_POST["produto_serie"];
	}

	if(strlen($_POST["os"]) > 0){
		$os = $_POST["os"];
	}

	$data_inicial = $_POST["data_inicial"];
	$data_final = $_POST["data_final"];

	if ((!strlen($data_inicial) || !strlen($data_final)) && (strlen($os) == 0) && (strlen($produto_serie) == 0) ) {
		$msg_erro["msg"][]    = "Preencha mais parâmetros para pesquisa.";
		$msg_erro["campos"][] = "data";
	} else {
		if(strlen($data_inicial) > 0 || strlen($data_final) > 0){
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

	if(count($msg_erro)==0){

		if(strlen($produto_serie) > 0){
			$cond_serie = " AND tbl_os.serie = '{$produto_serie}' ";
		}else{
			$cond_serie = "";
		}

		if(strlen($os) > 0){
			$cond_os = " AND tbl_os.sua_os = '{$os}' ";
		}else{
			$cond_os = "";
		}

		if(strlen($posto) > 0 ){
			$cond_posto = " AND tbl_posto_fabrica.posto = {$posto} ";	
		}else{
			$cond_posto = "";
		}

		if(strlen($aux_data_inicial) > 0 && strlen($aux_data_final) > 0 ){
			$cond_data = " AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial} 00:00:00' AND '{$aux_data_final} 23:59:59' ";
		}else{
			$cond_data = "";
		}

		$sql = " SELECT 	tbl_produto.fabrica_i,
						tbl_os.os, 
				        tbl_os.sua_os, 
				        tbl_os.serie, 
				        tbl_produto.produto, 
				        tbl_produto.referencia,
				        tbl_produto.descricao,
				        tbl_os.data_abertura,
				        tbl_posto.posto,
				        tbl_posto.nome,
				        tbl_posto.cnpj,
				        tbl_os_campo_extra.campos_adicionais
				 FROM tbl_os
				 
				 JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto AND
				        tbl_posto_fabrica.fabrica =  tbl_os.fabrica
				 JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
				 JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND fabrica_i = tbl_os.fabrica
				 JOIN tbl_os_campo_extra on tbl_os_campo_extra.os = tbl_os.os AND
				                                tbl_os_campo_extra.fabrica = {$login_fabrica} AND 
				                                tbl_os_campo_extra.campos_adicionais IS NOT NULL
				 WHERE tbl_os.fabrica = {$login_fabrica}
					  {$cond_data}
					  {$cond_os}
					  {$cond_posto}
					  {$cond_serie} ";
		$res_consulta = pg_query($con,$sql);
		$numRowsConsulta = pg_num_rows($res_consulta);
		$arrCampos = array();
		for ($i=0; $i < $numRowsConsulta; $i++){
			
			$campos_adicionais	= pg_fetch_result($res_consulta,$i,"campos_adicionais");
			$campos_adicionais = json_decode($campos_adicionais);
			
			
			if($campos_adicionais->ns_sequencia == "t"){
				//os
				$arr["os"] = pg_fetch_result($res_consulta,$i,"os");;
				$arr["sua_os"] = pg_fetch_result($res_consulta,$i,"sua_os");

				$data_abertura		= pg_fetch_result($res_consulta,$i,"data_abertura");
				$time = strtotime($data_abertura);
				$data_abertura = date("d/m/Y", $time);
				$arr["data_abertura"] = $data_abertura;

				//produto
				$arr["serie"] = pg_fetch_result($res_consulta,$i,"serie");
				$arr["referencia"] = pg_fetch_result($res_consulta,$i,"referencia");
				$arr["descricao"] = pg_fetch_result($res_consulta,$i,"descricao");
				//posto
				$arr["nome"] = pg_fetch_result($res_consulta,$i,"nome");
				$arr["cnpj"] = pg_fetch_result($res_consulta,$i,"cnpj");

				$arrCampos[] = $arr;
		
				
			}
		}

		if(isset($_POST["gerar_excel"]) && $_POST["gerar_excel"] == "true" ){

			if(count($numRowsConsulta) > 0 ){
				$data = date("d-m-Y-H:i");

				$fileName = "relatorio_ns_analise-{$data}.csv";

				$file = fopen("/tmp/{$fileName}", "w");

				$head = "OS;Posto;Produto;Série;Data;\n";
				fwrite($file, $head);

				for ($i=0; $i < count($arrCampos); $i++){
					$body = "{$arrCampos[$i]['sua_os']};{$arrCampos[$i]['cnpj']} - {$arrCampos[$i]['nome']};{$arrCampos[$i]['referencia']} - {$arrCampos[$i]['descricao']};{$arrCampos[$i]['serie']};{$arrCampos[$i]['data_abertura']};\n";
					fwrite($file, $body);															
				}

				fclose($file);
				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
				exit;
			}
		}
	}
}


$layout_menu = "cadastro";
$title = "RELATÓRIO DE NS PARA ANÁLISE";
include "cabecalho_new.php";

$plugins = array(
	"shadowbox",
	"datepicker",
	"mask",
	"dataTable"
);

include("plugin_loader.php");
?>
	
<script type="text/javascript">
	
	$(function(){
		$.dataTableLoad();
		Shadowbox.init();
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});
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
	<input type="hidden" name="produto" value="<?=$produto?>" />
	<input type="hidden" name="ps" value="<?=$ps?>" />
	
		<div class='titulo_tabela '>Parâmetros para Pesquisa</div>
		
		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("ns", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>OS</label>
					<div class='controls controls-row'>
					<input type="text" id="os" name="os" class='span8' value="<? echo $os ?>" >
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto_serie", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Nro. Série</label>
					<div class='controls controls-row'>
					<input type="text" id="produto_serie" name="produto_serie" class='span8' value="<? echo $produto_serie ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<h5 class='asteristico'>*</h5>
									<input type="text" name="data_inicial" id="data_inicial" size="14" maxlength="10" class='span12' value= "<?=$data_inicial?>">
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
								<input type="text" name="data_final" id="data_final" size="14" maxlength="10" class='span12' value="<?=$data_final?>" >
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
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'), 'pesquisar');">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>
<? 	
	if($_POST["btn_acao"] == "pesquisar" && count($msg_erro)==0){
		if((count($arrCampos) > 0 ) ){ ?>
			<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid" >
			<table id="produto_series_cadastradas" class="table table-striped table-bordered table-hover table-fixed">
				<thead>
					<tr class='titulo_coluna'>
						<th>OS</th>
						<th>Posto</th>
						<th>Produto</th>			
						<th>Série</th>
						<th>Data Abertura</th>
					</tr>
				</thead>
				<tbody>
					<? for ($i=0; $i < count($arrCampos); $i++) { ?>
						<tr>
							<td nowrap align="center"><a href="os_press.php?os=<?=$arrCampos[$i]["os"]?>" target="_blank"><?=$arrCampos[$i]["sua_os"]?></a></td>
							<td nowrap><?=$arrCampos[$i]["cnpj"]?> - <?=$arrCampos[$i]["nome"]?></td>
							<td nowrap><?=$arrCampos[$i]["referencia"]?> - <?=$arrCampos[$i]["descricao"]?></td>
							<td nowrap style="text-align: center"><?=$arrCampos[$i]["serie"]?></td>				
							<td nowrap style="text-align: center"><?=$arrCampos[$i]["data_abertura"]?></td>
						</tr>
				<?	}?>
				</tbody>
			</table>
			</div>
			<?php
				if ($count > 50) {
				?>
					<script>
						$.dataTableLoad({ table: "#produto_series_cadastradas" });
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
	<br/>
	<?}else{ ?>
		<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>

	<?}?>
<? } 
 include 'rodape.php'; ?>

