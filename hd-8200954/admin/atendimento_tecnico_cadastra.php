<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro,gerencia,call_center";
include "funcoes.php";
include "autentica_admin.php";

$layout_menu = "callcenter";
$title = "CADASTRO DE ATENDIMENTO TÉCNICO";

$btn_acao     = $_POST["btn_acao"];

if ($_POST["btn_acao"] == "submit"){

	$atendimento = "";

	$defeito_reclamado  = $_POST["defeito_reclamado"];
	$solucao            = $_POST["solucao_os"];
	$defeito_constatado = $_POST["defeito_constatado"];
	$produto            = $_POST["produto_produto"];
	$posto_posto        = $_POST["posto_posto"];
	$os                 = $_POST["os"];
	$tec_pa             = $_POST["tec_pa"];
	$descricao_posto    = $_POST["descricao_posto"];
	$codigo_posto       = $_POST["codigo_posto"];

	if(!strlen($os) > 0 /*&& !strlen($defeito_reclamado) > 0 && !strlen($tec_pa) > 0*/)
	{
		$msg_erro["msg"][]    = "Preencha todos os campos obrigatórios";
		$msg_erro["campos"][] = "os";
		$msg_erro["campos"][] = "defeito_reclamado";
		$msg_erro["campos"][] = "tec_pa";
	}else{
		if ($os==null || !strlen($os)>0){
				$msg_erro["msg"][]    = "Necessário especificar a OS";
				$msg_erro["campos"][] = "os";
		}else{
			$sql = "SELECT  tbl_os.sua_os
				FROM    tbl_os
				WHERE   tbl_os.os = $os
				AND tbl_os.posto = $posto_posto";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
				$sua_os = trim(pg_result($res,0,sua_os));
				//$posto = trim(pg_result($res,0,posto));
			}else{
				$msg_erro["msg"][]    = "Erro ao identificar a ordem de serviço.";
				$msg_erro["campos"][] = "os";
			}
		}

		if (!strlen($defeito_reclamado) > 0 || $defeito_reclamado == null){
			$msg_erro["msg"][]    = "Necessário especificar o defeito reclamado.";
			$msg_erro["campos"][] = "defeito_reclamado";
		}else{
			$sql = "SELECT descricao FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
			$defeito_reclamado_desc = trim(pg_result($res,0,descricao));
			}
		}
		
		if ($defeito_constatado==0 or $defeito_constatado == null){
			$defeito_constatado = 'null';
		}else{
			$sql = "SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado = $defeito_constatado AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
			$defeito_constatado_desc = trim(pg_result($res,0,descricao));
			}
		}
		
		if ($solucao==0 or $solucao == null){
			$solucao = 'null';
		}else{
			$sql = "SELECT descricao FROM tbl_solucao WHERE solucao = $solucao AND fabrica = $login_fabrica";
			$res = pg_exec ($con,$sql) ;
			if (@pg_numrows($res) > 0) {
			$solucao_desc = trim(pg_result($res,0,descricao));
			}
		}

		if(!strlen($tec_pa) > 0){
			$msg_erro["msg"][]    = "Necessário especificar o Técnico Posto de atendimento";
			$msg_erro["campos"][] = "tec_pa";
		}

		if(!count($msg_erro["msg"]) > 0){
			$sql = "INSERT INTO tbl_atendimento_tecnico  (admin,data_atendimento,posto,tecnico_posto,os,produto,defeito_reclamado,defeito_constatado,solucao) VALUES ($login_admin,current_timestamp,$posto_posto,'$tec_pa',$os,$produto,$defeito_reclamado,$defeito_constatado,$solucao) returning atendimento ";
			
			$res = pg_query($con, $sql);
			$atendimento = pg_fetch_result($res, 0, "atendimento");

			if(strlen($atendimento) > 0){
				$sucesso = "sim";
			}else{
				$sucesso = "nao";
			}

			$atendimento        = NULL;
			$defeito_reclamado  = NULL;
			$solucao            = NULL;
			$defeito_constatado = NULL;
			$produto            = NULL;
			$posto_posto        = NULL;
			$os                 = NULL;
			$tec_pa             = NULL;
			$descricao_posto    = NULL;
			$codigo_posto       = NULL;
		}
	}
}

if ($_POST["btn_acao"] == "os") {

	$posto = $_POST["posto_posto"];

	$sql = "
		SELECT
		   tbl_os.os,
		   tbl_os.sua_os
		FROM
		   tbl_os
		   JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
		   JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE
		   tbl_os.posto = {$posto}
		   AND tbl_os.fabrica = {$login_fabrica}
		   AND tbl_os.finalizada IS NULL
		   AND tbl_os.data_fechamento IS NULL
		   /*AND tbl_os.status_checkpoint = 1*/
		   AND tbl_os.cancelada IS NULL
		ORDER BY
		   tbl_os.data_abertura;
	";

	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$option = "<option value=''>Selecione</option>";

		for ($i = 0; $i < pg_num_rows($res); $i++){
			$aux_os     = trim(pg_fetch_result($res,$i,os));
			$aux_sua_os = trim(pg_fetch_result($res,$i,sua_os));
			$option .= "<option value='{$aux_os}'>{$aux_sua_os}</option>";
		}
		echo $option;
	}else{
		echo "Erro";
	}

	exit;
}

if ($_POST["btn_acao"] == "produto_produto") {

	$os = $_POST["os"];

	$sql = "
		SELECT
		   tbl_produto.produto,
		   tbl_produto.referencia,
		   tbl_produto.descricao
		FROM
		   tbl_produto
		   JOIN tbl_os ON tbl_os.produto = tbl_produto.produto AND tbl_os.fabrica = {$login_fabrica}
		WHERE
		   tbl_os.os = {$os}
	";

	$res = pg_query($con, $sql);
	$last_error = pg_last_error($res);

	if(pg_num_rows($res) > 0){
		$option = "";

		for ($i = 0; $i < pg_num_rows($res); $i++){
			$aux_produto = trim(pg_fetch_result($res,$i,produto));
			$aux_produto_referencia = trim(pg_fetch_result($res,$i,referencia));
			$aux_descricao         = trim(pg_fetch_result($res,$i,descricao));
			$option .= "<option value='{$aux_produto}'>{$aux_produto_referencia} - {$aux_descricao}</option>";
		}
		echo $option;
	}else if(pg_num_rows($res) == 0 && strlen($last_error) == 0) {
		$option = "<option value=''>Selecione</option>";
		echo $option;
	} else{
		echo "Erro";
	}

	exit;
}

if ($_POST["btn_acao"] == "defeito_reclamado") {

	$os = $_POST["os"];

	$sql = "
		SELECT
		   tbl_defeito_reclamado.defeito_reclamado,
		   tbl_defeito_reclamado.descricao
		FROM
		   tbl_defeito_reclamado
		   JOIN tbl_os ON tbl_os.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado AND tbl_os.fabrica = {$login_fabrica}
		WHERE
		   tbl_os.os = {$os}
	";

	$res = pg_query($con, $sql);
	$last_error = pg_last_error($res);

	if(pg_num_rows($res) > 0){
		$option = "";

		for ($i = 0; $i < pg_num_rows($res); $i++){
			$aux_defeito_reclamado = trim(pg_fetch_result($res,$i,defeito_reclamado));
			$aux_descricao         = trim(pg_fetch_result($res,$i,descricao));
			$option .= "<option value='{$aux_defeito_reclamado}'>{$aux_descricao}</option>";
		}
		echo $option;
	}else if(pg_num_rows($res) == 0 && strlen($last_error) == 0) {
		$option = "<option value=''>Selecione</option>";
		echo $option;
	} else{
		echo "Erro";
	}

	exit;
}

if ($_POST["btn_acao"] == "defeito_constatado") {

	$os = $_POST["os"];

	$sql = "
		SELECT
		   tbl_defeito_constatado.defeito_constatado,
		   tbl_defeito_constatado.descricao
		FROM
		   tbl_defeito_constatado
		   JOIN tbl_os ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado AND tbl_os.fabrica = {$login_fabrica}
		WHERE
		   tbl_os.os = {$os}
	";

	$res = pg_query($con, $sql);
	$last_error = pg_last_error($res);

	if(pg_num_rows($res) > 0){
		$option = "";

		for ($i = 0; $i < pg_num_rows($res); $i++){
			$aux_defeito_constatado = trim(pg_fetch_result($res,$i,defeito_constatado));
			$aux_descricao          = trim(pg_fetch_result($res,$i,descricao));
			$option .= "<option value='{$aux_defeito_constatado}'>{$aux_descricao}</option>";
		}
		echo $option;
	}else if(pg_num_rows($res) == 0 && strlen($last_error) == 0) {
		$option = "<option value=''>Selecione</option>";
		echo $option;
	} else{
		echo "Erro";
	}

	exit;
}

if ($_POST["btn_acao"] == "solucao_os") {

	$os = $_POST["os"];

	$sql = "
		SELECT
		   tbl_solucao.solucao,
		   tbl_solucao.descricao
		FROM
		   tbl_solucao
		   JOIN tbl_os ON tbl_os.solucao_os = tbl_solucao.solucao AND tbl_os.fabrica = {$login_fabrica}
		WHERE
		   tbl_os.os = {$os}
	";

	$res = pg_query($con, $sql);
	$last_error = pg_last_error($res);

	if(pg_num_rows($res) > 0){
		$option = "";

		for ($i = 0; $i < pg_num_rows($res); $i++){
			$aux_solucao = trim(pg_fetch_result($res,$i,solucao));
			$aux_descricao          = trim(pg_fetch_result($res,$i,descricao));
			$option .= "<option value='{$aux_solucao}'>{$aux_descricao}</option>";
		}
		echo $option;
	}else if(pg_num_rows($res) == 0 && strlen($last_error) == 0) {
		$option = "<option value=''>Selecione</option>";
		echo $option;
	} else{
		echo "Erro";
	}

	exit;
}

include "cabecalho_new.php";

$plugins = array(
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>

<script language="JavaScript">
$(function() {
	$.datepickerLoad(Array("data_final", "data_inicial"));
	$.autocompleteLoad(Array("produto", "peca", "posto"));
	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	$("#os").change(function(){
		if (ajaxAction()) {
			var os = $("#os").val();

			if(os != "")
			{
				popularProdutoProduto(os);
				popularDefeitoReclamado(os);
				popularDefeitoConstatado(os);
				popularSolucao(os);

			}else{
				alert("Erro ao identificar a OS informada.");
			}
		}
	});
});

function popularOS(){
	if (ajaxAction()) {
		var posto_posto = $("#posto_posto").val();

		if(posto_posto != "") {
			$.ajax({
				async: false,
				url: "<?=$_SERVER['PHP_SELF']?>",
				type: "POST",
				dataType: "JSON",
				data: { btn_acao: "os", posto_posto : posto_posto },
				complete: function (data) {
					data = data.responseText;

					if (data != "Erro") {
						$("#os").html(data);
					}else{
						Alert("Erro ao identificar as OS's do posto informado.");
					}
				}
			});
		}
		else{
				Alert("Erro ao identificar o posto informado.");
			}
	}
}

function popularProdutoProduto(os){
	$.ajax({
		async: false,
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		dataType: "JSON",
		data: { btn_acao: "produto_produto", os : os },
		complete: function (data) {
			data = data.responseText;

			if (data != "Erro") {
				$("#produto_produto").html(data);
			}else{
				alert("Erro ao identificar a referencia do produto da OS informada.");
			}
		}
	});
}

function popularDefeitoReclamado(os){
	$.ajax({
		async: false,
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		dataType: "JSON",
		data: { btn_acao: "defeito_reclamado", os : os },
		complete: function (data) {
			data = data.responseText;

			if (data != "Erro") {
				$("#defeito_reclamado").html(data);
			}else{
				alert("Erro ao identificar o defeito reclamado da OS informada.");
			}
		}
	});
}

function popularDefeitoConstatado(os){
	$.ajax({
		async: false,
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		dataType: "JSON",
		data: { btn_acao: "defeito_constatado", os : os },
		complete: function (data) {
			data = data.responseText;

			if (data != "Erro") {
				$("#defeito_constatado").html(data);
			}else{
				alert("Erro ao identificar o defeito constatado da OS informada.");
			}
		}
	});
}

function popularSolucao(os){
	$.ajax({
		async: false,
		url: "<?=$_SERVER['PHP_SELF']?>",
		type: "POST",
		dataType: "JSON",
		data: { btn_acao: "solucao_os", os : os },
		complete: function (data) {
			data = data.responseText;

			if (data != "Erro") {
				$("#solucao_os").html(data);
			}else{
				alert("Erro ao identificar a solução da OS informada.");
			}
		}
	});
}

function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
	$("#posto_posto").val(retorno.posto);
	popularOS();
}

</script>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<? }

if($sucesso == "sim") { ?>
	 <div class='container'>
        <div class="alert alert-success">                
            <h4>Atendimento gravado com sucesso</h4>
        </div>
    </div>
<? }

if($sucesso == "nao") { ?>
	 <div class='container'>
        <div class="alert alert-error">                
            <h4>Erro ao cadastrar os registros</h4>
        </div>
    </div>
<? } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>" class='form-search form-inline tc_formulario' >
	<div class="titulo_tabela">Cadastro de Atendimento Técnico</div>
	<br>

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
					<div class='span8 input-append'>
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
		<div class='span4'>
			<div class='control-group <?=(in_array("tec_pa", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='tec_pa'>Técnico Posto de atendimento</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<!-- <input type="text" name="tec_pa" class='span12' id="tec_pa" value="<?=$tec_pa;?>"> -->
						<textarea name="tec_pa" id="tec_pa" value="<?=$tec_pa;?>" style="margin: 0px; height: 102px; width: 484px;"></textarea>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='os'>Número da OS</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<!-- <input type="text" name="os" id="os" class='span12' value='<?=$sua_os;?>'> -->
						<select id="os" name="os" class='span12' name="os" >
						<option value="">Selecione</option>
						</select>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='produto_produto'>Produto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<select id="produto_produto" class='span12' name="produto_produto" >
							<option value="">Selecione</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("defeito_reclamado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='defeito_reclamado'>Defeito Reclamado</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<h5 class='asteristico'>*</h5>
						<select id="defeito_reclamado" class='span12' name="defeito_reclamado" >
							<option value="">Selecione</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("defeito_constatado", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='defeito_constatado'>Defeito Constatado</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<select id="defeito_constatado" class='span12' name="defeito_constatado" >
							<option value="">Selecione</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("solucao_os", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='solucao_os'>Solução</label>
				<div class='controls controls-row'>
					<div class='span8'>
						<select name="solucao_os" id="solucao_os" class='span12' >
							<option value="">Selecione</option>
						</select>
					</div>
					<div class='span2'></div>
				</div>
			</div>
		</div>
	</div>

	<input type="hidden" name="posto_posto" id="posto_posto" value="<?=$posto_posto;?>">

	<p><br/>
		<button class='btn btn-warning' id="btn_acao" type="button"  onclick="location.reload();">Limpar</button>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>

</form>
<br>
<br>
<? include "rodape.php" ?>
