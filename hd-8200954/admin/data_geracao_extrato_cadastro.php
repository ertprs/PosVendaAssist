<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
require __DIR__.'/../classes/api/Client.php';

use api\Client;






// CONFIGURAÇÃO AUDITOR
$primaryKey = "geracao_extrato";
if(strstr($_SERVER['SERVER_NAME'], 'devel.telecontrol') || strstr($_SERVER['SERVER_NAME'], 'homologacao.telecontrol')){
	$primaryKey = "geracao_extrato_teste";
}
//---------------------

if(count($_POST)){

	$campos_obrigatorios = array();
	if($_POST['data_geracao'] == ""){
		$campos_obrigatorios[] = 'data_geracao';
	}

	if(!array_key_exists('toda_rede', $_POST) && $_POST['posto']['codigo'] == ""){
		$campos_obrigatorios[] = 'posto_codigo';
	}



	if(count($campos_obrigatorios) == 0){
		if($_POST['toda_rede'] == "true"){
			$sql = "SELECT posto, parametros_adicionais from tbl_posto_fabrica where fabrica = {$login_fabrica}";
			$tipo_geracao = 'extrato_rede';

		}else{
			$codigo_posto = $_POST['posto']['codigo'];
			$sql = "SELECT posto, parametros_adicionais from tbl_posto_fabrica where fabrica = {$login_fabrica} AND codigo_posto = '{$codigo_posto}'";
			$tipo_geracao = 'extrato_posto';
		}

		$data_geracao = $_POST['data_geracao'];
		$data_geracao = str_replace("/", "-", $data_geracao);
		$data_geracao = date("Y-m-d",strtotime($data_geracao));

		$res = pg_query($con,$sql);
		if(pg_last_error() == null){
			$res = pg_fetch_all($res);

			$alteracao['data_geracao'] = $data_geracao;
			$alteracao['data_hora_servidor'] = date("d-m-Y H:i:s");

			foreach ($res as $idx => $posto) {
				$parametros_adicionais = json_decode($posto['parametros_adicionais'], true);
				if($parametros_adicionais == null){
					$parametros_adicionais = array();
				}
				$parametros_adicionais['geracao_extrato'] = $tipo_geracao;
				$parametros_adicionais = json_encode($parametros_adicionais);

				$alteracao['postos'][] = $posto['posto'];

				$sql = "UPDATE tbl_posto_fabrica
						SET
							extrato_programado = '$data_geracao',
							parametros_adicionais = '{$parametros_adicionais}'
						WHERE posto = {$posto['posto']} AND fabrica = {$login_fabrica}";
				$res = pg_query($con,$sql);
			}

			$alteracao['postos'] = json_encode($alteracao['postos']);

			

			auditorLog($primaryKey,array(),$alteracao, "tbl_posto_fabrica", 'admin/data_geracao_extrato_cadastro.php', strtoupper($tipo_geracao));
			$msgSuccess = "Data de geração alterada com sucesso";
		}
	}
}


$client = Client::makeTelecontrolClient("auditor","auditor");
$client->urlParams = array(
	"aplication" => "02b970c30fa7b8748d426f9b9ec5fe70",
	"table" => 'tbl_posto_fabrica',
	"primaryKey" => $login_fabrica."*".$primaryKey,
	"limit" => "50"
);
try{
	$logAlteracoes = $client->get();
}catch(\Exception $e){
	$logAlteracoes = array();
}


$title       = "Data Geração Extrato";
$layout_menu = 'cadastro';

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
$(function(){
	//$.datepickerLoad(Array("data_geracao"));
	$("#data_geracao").datepicker().mask("99/99/9999");

	Shadowbox.init();

	$("span[rel=lupa]").click(function() {
		$.lupa($(this));
	});

	$("#toda_rede").change(function(){
		if($(this).is(':checked')){
			$("#posto_id").val("");
			$("#posto_codigo").val("");
			$("#posto_nome").val("");
		}
	});

	$("#btn-save").click(function(){
		var exception = false;

		if($("#data_geracao").val() == ""){
			$("#data_geracao").parents(".control-group").addClass("error");
			exception = true;
		}
		if($("#toda_rede").is(':checked') == false && $("#posto_codigo").val() == ""){
			$("#posto_codigo").parents(".control-group").addClass("error");
			$("#posto_nome").parents(".control-group").addClass("error");
			exception = true;
		}

		if(exception == true){
			return false;
		}

		if($("#toda_rede").is(':checked')){
			alert("Atenção o extrato será gerado para a rede de postos no dia "+$("#data_geracao").val())
		}else{
			alert("Atenção o posto "+$          ("#posto_nome").val()+" irá gerar extrato na data "+$("#data_geracao").val())
		}


		$("#frm_geracao_extrato").submit();
	});
});

function retorna_posto(retorno) {
	$("#posto_id").val(retorno.posto);
	$("#posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);

	$("#toda_rede").attr('checked', false);
}

</script>


<?php
unset($data_geracao);
if($msgSuccess != ""){
	?>
	<div class="container">
		<div class="alert alert-success">
	        <h4><?=$msgSuccess?></h4>
	    </div>
    </div>
	<?php
}else{
	$posto_nome = getValue('posto[nome]');
	$posto_codigo = getValue('posto[codigo]');
	$data_geracao = getValue('data_geracao');

	if(getValue('toda_rede') != ""){
		$checked = "checked";
	}
}
?>
<div class="alert alert-block">
	<!--<button type="button" class="close" data-dismiss="alert">&times;</button>-->
	<h4>ATENÇÃO!</h4>
	As datas de geração de extrato terão que ser cadastradas todos os meses, caso não tenha cadastro para o mês corrente,
	<br/> não irá ocorrer a geração dos extratos.
</div>
<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_geracao_extrato' id='frm_geracao_extrato' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>

	<br/>



	<div class="row-fluid">
		<div class="span2">
		</div>
		<div class="span4">
		    <label class="checkbox" for="" style="margin-top: 20px">
		        <input id="toda_rede" type='checkbox' name='toda_rede' <?php echo $checked; ?> value='true'> Toda Rede
		    </label>
		</div>
	</div>
	<div class="row-fluid">
		<div class="span2"></div>

		<div class="span4">
			<div class='control-group <?php if(in_array("posto_codigo", $campos_obrigatorios)){ echo 'error'; } ?>' >
				<label class="control-label" for="posto_codigo">Código do Posto</label>
				<div class="controls controls-row">
					<div class="span10 input-append">
						<h5 class="asteristico">*</h5>
						<input id="posto_codigo" name="posto[codigo]" class="span12" type="text" value="<?=$posto_codigo?>" <?=$posto_readonly?> />
						<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
							<i class="icon-search"></i>
						</span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>

		<div class="span4">
			<div class='control-group <?php if(in_array("posto_codigo", $campos_obrigatorios)){ echo 'error'; } ?>' >
				<label class="control-label" for="posto_nome">Nome do Posto</label>
				<div class="controls controls-row">
					<div class="span10 input-append">
						<h5 class="asteristico">*</h5>
						<input id="posto_nome" name="posto[nome]" class="span12" type="text" value="<?=$posto_nome?>" <?=$posto_readonly?> />
						<span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
							<i class="icon-search"></i>
						</span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>

		<div class="span2"></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
	    <div class='span2'>
	        <div class='control-group <?php if(in_array("data_geracao", $campos_obrigatorios)){ echo 'error'; } ?>'>
	            <label class="control-label" for="codigo_posto">Data Geração</label>
	            <div class="controls controls-row">
	                <div class="span12">
	                	<h5 class="asteristico">*</h5>
	                    <input class="span12" type="text" id='data_geracao' name="data_geracao" value="<?=$data_geracao?>">
	                </div>
	            </div>
	        </div>
	    </div>

		<div class='span2'></div>
	</div>

	<input type='hidden' id='btn_click' name='btn_acao' value=''><br/>

	<div class="row-fluid">
	    <!-- margem -->
	    <div class="span4"></div>

	    <div class="span4">
	        <div class="control-group">
	            <div class="controls controls-row tac">
	                <button type="button" id="btn-save"  class="btn" value="gravar" alt="Gravar formulário"> Gravar</button>
	            </div>
	        </div>
	    </div>
	    <!-- margem -->
	    <div class="span4"></div>
	</div>
</form>

<span class="label" style='background-color: #dff0d8;' >&nbsp&nbsp&nbsp&nbsp</span>&nbspÚltima alteração de TODA REDE
<table class="table table-striped table-bordered table-hover table-fixed">
	<thead>
		<tr class="titulo_coluna">
			<th>Data alteração</th>
			<th>Admin</th>
			<th>Tipo de Alteração</th>
			<th>Data de Geração</th>
			<th>Postos</th>
		</tr>
	</thead>
	<tbody>
		<?php
		$todaRedeMarcada = false;
		foreach ($logAlteracoes as $value) {
			
			$data = $value['data'];

			$postos = $data['content']['depois']['postos'];
			if(count($postos) > 1){
				$postos = "TODA REDE";
			}else{
				$postos = $data['content']['depois']['postos'];
				$sql = "SELECT codigo_posto, nome from tbl_posto join tbl_posto_fabrica  using(posto) where posto = ".$postos[0] ."AND fabrica = $login_fabrica";

				$res = pg_query($con,$sql);
				if(!pg_last_error($con)){
					$res = pg_fetch_all($res);

					$postos = $res[0]['codigo_posto']." - ".$res[0]['nome'];
				}
			}

			$admin = $data['user'];
			$sql = "select nome_completo from tbl_admin where admin = $admin and fabrica = $login_fabrica;";
			$res = pg_query($con,$sql);
			if(!pg_last_error($con)){
				$res = pg_fetch_all($res);
				$admin = $res[0]['nome_completo'];
			}

			$class = "";
			if($postos == "TODA REDE" && $todaRedeMarcada == false){
				$class = "success";
				$todaRedeMarcada = true;
			}
			?>
			<tr class="<?=$class?>">
				<td class="tac"><?=$data['content']['depois']['data_hora_servidor']?></td>
				<td><?=$admin?></td>
				<td class="tac"><?=$data['action']?></td>
				<td class="tac">
					<?php 
						if (strlen($data['content']['depois']['data_geracao']) > 0) {
							echo date("d-m-Y",strtotime($data['content']['depois']['data_geracao']));
						}
					?>
				</td>
				<td><?=$postos?></td>
			</tr>
			<?php
		}
		?>

	</tbody>
</table>



 <?php
 include 'rodape.php';
 ?>


