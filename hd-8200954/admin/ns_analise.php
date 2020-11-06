<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';
include "autentica_admin.php";

if ($_POST["btn_acao"] == "ativar") {
	$produto_serie = $_POST["produto_serie"];

	$sql = "SELECT produto_serie FROM tbl_produto_serie WHERE fabrica = {$login_fabrica} AND produto_serie = {$produto_serie}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_produto_serie SET serie_ativa = TRUE WHERE fabrica = {$login_fabrica} AND produto_serie = {$produto_serie}";
		$res = pg_query($con, $sql);

		if (pg_last_error()) {
			die('error');
		}
		die('success');
	}
	exit;
}

if ($_POST["btn_acao"] == "inativar") {
	$produto_serie = $_POST["produto_serie"];

	$sql = "SELECT produto_serie FROM tbl_produto_serie WHERE fabrica = {$login_fabrica} AND produto_serie = {$produto_serie}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_produto_serie SET serie_ativa = FALSE WHERE fabrica = {$login_fabrica} AND produto_serie = {$produto_serie}";
		$res = pg_query($con, $sql);

		if (pg_last_error()) {
			die('error');
		}
		die('success');
	}

	exit;
}

if ($_POST["btn_acao"] == "excluir") {
	$produto_serie = $_POST["produto_serie"];

	$sql = "SELECT produto_serie FROM tbl_produto_serie WHERE fabrica = {$login_fabrica} AND produto_serie = {$produto_serie}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		$sql = "DELETE FROM tbl_produto_serie WHERE fabrica = {$login_fabrica} AND produto_serie = {$produto_serie}";
		$res = pg_query($con, $sql);

		if (pg_last_error()) {
			die('error');
		}
		die('success');
	}

	exit;
}

if($_POST["series_cadastradas"] == "series_cadastradas"){
	$referencia = $_POST["referencia"];
	$sqlVerifica = " SELECT  tbl_produto.produto,
							 tbl_produto.referencia,
							 tbl_produto.descricao,
							 tbl_produto_serie.serie_inicial,
							 tbl_produto_serie.serie_final,
							 tbl_produto_serie.produto_serie,
							 tbl_produto_serie.observacao
						FROM tbl_produto
						INNER JOIN tbl_produto_serie ON tbl_produto_serie.produto = tbl_produto.produto AND
														tbl_produto_serie.fabrica = {$login_fabrica}
						WHERE tbl_produto.referencia = '{$referencia}' AND
							  tbl_produto_serie.fabrica = {$login_fabrica}";

	$res = pg_query($con, $sqlVerifica);
	$numRows = pg_num_rows($res);
	$results = array();
	if($numRows > 0){
		for ($i=0; $i < $numRows; $i++) {
			$object = pg_fetch_object($res,$i);
			$results[] = $object;
		}

	}
	$respJson = json_encode($results);

	echo $respJson;
	exit;
}

if($_POST['btn_acao']=="gravar"){
	if(strlen($_POST["produto"]) > 0){
		$produto = $_POST["produto"];
	}

	if(strlen($_POST["ps"]) > 0){
		$ps = $_POST["ps"];
	}

	//valida produto
	if(strlen($_POST["produto_referencia"]) > 0){
		$produto_referencia = $_POST["produto_referencia"];
	}else{
		$msg_erro["campos"][] = "produto";
		$msg_erro["msg"]["obg"] = "Preencha os campos Obrigatórios";
	}

	if(strlen($_POST["produto_descricao"]) > 0){
		$produto_descricao = $_POST["produto_descricao"];
	}else{
		$msg_erro["campos"][] = "descricao";
		$msg_erro["msg"]["obg"] = "Preencha os campos Obrigatórios";
	}

	// valida nro serie
	if(strlen($_POST["ns_inicial"]) > 0){
		$ns_inicial = $_POST["ns_inicial"];
	}else{
		$msg_erro["campos"][] = "ns_inicial";
		$msg_erro["msg"]["obg"]= "Preencha os campos Obrigatórios";
	}

	if (!isFabrica(15)) {
		if(strlen($_POST["ns_final"]) > 0){
			$ns_final = $_POST["ns_final"];
		}else{
			$msg_erro["campos"][] = "ns_final";
			$msg_erro["msg"]["obg"] = "Preencha os campos Obrigatórios";
		}
	}

	if($login_fabrica == 91){ 

		if (strlen($data_fabricacao_inicial) == 0) {

		}

		if (strlen($data_fabricacao_inicial) == 0 OR strlen($data_fabricacao_final) == 0) {
			$msg_erro["campos"][] = "data";
			$msg_erro["msg"]["obg"] = "Preencha os campos Obrigatórios";
		}

		if (strlen($data_fabricacao_inicial) > 0  AND strlen($data_fabricacao_final) > 0) {

			list($di, $mi, $yi) = explode("/", $data_fabricacao_inicial);
			if(!checkdate($mi,$di,$yi)){
				$msg_erro["msg"]["obg"] = "Data de Fabricação Inicial Inválida";
			}else{
				$xdata_fabricacao_inicial = "$yi-$mi-$di";
			}

			list($df, $mf, $yf) = explode("/", $data_fabricacao_final);
			if(!checkdate($mf,$df,$yf)){
				$msg_erro["msg"]["obg"] = "Data de Fabricação Final Inválida";
			}else{
				$xdata_fabricacao_final = "$yf-$mf-$df";
			}

		}
	}

	if (isFabrica(30, 74)) {

		$observacao = $_POST['observacao'];
		$ns_inicial = preg_replace('/\D/','', $ns_inicial);
		$ns_final = preg_replace('/\D/','', $ns_final);
	}

	if (isFabrica(15, 30, 74)) {
		$ativo = ($_POST["ativo"] == "t") ? "t" : "f";
	}

	if($login_fabrica == 6){
		if (strlen($_POST['garantia_mes']) > 0) {
			$garantia_mes = $_POST['garantia_mes'];
		} else {
			$msg_erro["campos"][] = "garantia_mes";
			$msg_erro["msg"]["obg"]= "Preencha os campos Obrigatórios";
		}
	}

	if(count($msg_erro)==0){
		//veriica se existe sequencia de nro serie ja cadastrada para o produto
		$sqlProduto = " SELECT tbl_produto.produto
						FROM tbl_produto
						INNER JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
						WHERE referencia = '{$produto_referencia}' AND
							  fabrica = {$login_fabrica} ";

			$res = pg_query($con, $sqlProduto);
		$numRows = pg_num_rows($res);
		$produto = pg_fetch_result($res, 0, "produto");

		$sqlVerifica = " SELECT tbl_produto.produto
						FROM tbl_produto
						INNER JOIN tbl_produto_serie ON tbl_produto_serie.produto = tbl_produto.produto AND
														tbl_produto_serie.fabrica = {$login_fabrica}
						WHERE tbl_produto.produto = {$produto} AND
							  tbl_produto_serie.serie_inicial = '{$ns_inicial}' AND
							  tbl_produto_serie.serie_final = '{$ns_final}' ";

		$res = pg_query($con, $sqlVerifica);
		$numRows = pg_num_rows($res);

		if (strlen($ps) > 0) {
			$sqlWhere = " AND produto_serie NOT IN ({$ps}) ";
		}


		if (isFabrica(15, 30, 74)) {
			$sql = "SELECT produto_serie
					FROM tbl_produto_serie
					WHERE fabrica = {$login_fabrica}
					AND produto = {$produto}
					AND (
							(REGEXP_REPLACE('{$ns_inicial}', '[a-z]', '', 'gi') BETWEEN REGEXP_REPLACE(serie_inicial, '[a-z]', '', 'gi') AND REGEXP_REPLACE(serie_final, '[a-z]', '', 'gi'))
							OR
							('{$ns_inicial}' = serie_inicial OR '{$ns_inicial}' = serie_final)
						)";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$msg_erro["msg"][] = "Já existe uma sequência de número de série cadastrado para o produto dentro do intervalo {$ns_inicial} - {$ns_final}";
			} else if (strlen($ns_final) > 0) {
				$sql = "SELECT produto_serie
						FROM tbl_produto_serie
						WHERE fabrica = {$login_fabrica}
						AND produto = {$produto}
						AND (
								(REGEXP_REPLACE('{$ns_final}', '[a-z]', '', 'gi') BETWEEN REGEXP_REPLACE(serie_inicial, '[a-z]', '', 'gi') AND REGEXP_REPLACE(serie_final, '[a-z]', '', 'gi'))
								OR
								('{$ns_final}' = serie_inicial OR '{$ns_final}' = serie_final)
							)";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$msg_erro["msg"][] = "Já existe uma sequência de número de série cadastrado para o produto dentro do intervalo {$ns_inicial} - {$ns_final}";
				}
			}
		}

		if(count($msg_erro)==0){
			if($numRows > 0 && strlen($produto) > 0 && strlen($ps) > 0){

				$sqlUpdate = "	  UPDATE tbl_produto_serie
								  SET produto 		=	{$produto}	,
									  serie_inicial =	'{$ns_inicial}',
									  serie_final 	=	'{$ns_final}',
									  observacao    =     '{$observacao}'
									  ".((isFabrica(15, 30, 74)) ? ", serie_ativa = '$ativo' " : "")."
									  ".(($login_fabrica == 6) ? ", garantia_mes = $garantia_mes " : "")."
									  ".(($login_fabrica == 91) ? ",fabricacao_inicial = '$xdata_fabricacao_inicial' , fabricacao_final = '$xdata_fabricacao_final'" : "")."
								  WHERE produto_serie = {$ps} 
								  AND fabrica = {$login_fabrica} ";


				$res = pg_query($con,$sqlUpdate);

				if(strlen(pg_last_error($con)) > 0 ){
					$msg_erro["msg"][] = "Erro ao Gravar Dados";
				}else{
					$msg_success = "Atualizado com Sucesso";
					$produto = "";
					$produto_referencia	= "";
					$produto_descricao	= "";
					$ns_inicial	= "";
					$ns_final	= "";
					$observacao	= "";
					$familia    = "";
					$garantia_mes = "";
					$data_fabricacao_inical = "";
                    $data_fabricacao_final = "";
				}
			}else if($numRows == 0){

				$sqlInsert = "INSERT INTO tbl_produto_serie (
									 fabrica,
									 produto,
									 serie_inicial,
									 serie_final,
									 observacao
									 ".((isFabrica(15, 30, 74)) ? ",serie_ativa " : "")."
									 ".(($login_fabrica == 6) ? ", garantia_mes " : "")."
									 ".(($login_fabrica == 91) ? ", fabricacao_inicial, fabricacao_final " : "")."
								) VALUES (
									{$login_fabrica},
									{$produto},
									'{$ns_inicial}',
									'{$ns_final}',
									'{$observacao}'
									".((isFabrica(15, 30, 74)) ? ", '$ativo' " : "")."
									".(($login_fabrica == 6) ? ", '{$garantia_mes}' " : "")."
									".(($login_fabrica == 91) ? ", '{$xdata_fabricacao_inicial}','{$xdata_fabricacao_final}' " : "")."
								  )";

				$res = pg_query($con, $sqlInsert);
				if(strlen(pg_last_error($con)) > 0 ){
					$msg_erro["msg"][] = "Erro ao Gravar Dados";
				}else{
					$msg_success = "Gravado com Sucesso";
					$produto = "";
					$produto_referencia	= "";
					$produto_descricao	= "";
					$ns_inicial	= "";
					$ns_final	= "";
					$garantia_mes = "";
					$observacao = "";
					$data_fabricacao_inical = "";
					$data_fabricacao_final = "";

				}

			}else{
				$msg_erro["msg"][] = "Este produto já possui esta sequência";
			}

		}

	}
}

if(strlen($_GET["produto"]) > 0){
	$produto = $_GET["produto"];
	$ps = $_GET["ps"];
	$serie_inicial = $_GET["serie_inicial"];
	$serie_final = $_GET["serie_final"];

	$sql = " SELECT  tbl_produto.produto,
					 tbl_produto.referencia,
					 tbl_produto.descricao,
					 tbl_produto_serie.serie_inicial,
					 tbl_produto_serie.serie_final,
					 tbl_produto_serie.observacao,
					 tbl_produto_serie.serie_ativa,
					 tbl_produto_serie.garantia_mes,
					 to_char(tbl_produto_serie.fabricacao_inicial,'DD/MM/YYYY') AS fabricacao_inicial,
					 to_char(tbl_produto_serie.fabricacao_final,'DD/MM/YYYY') AS fabricacao_final
			FROM tbl_produto
			INNER JOIN tbl_produto_serie ON tbl_produto_serie.produto = tbl_produto.produto AND
											tbl_produto_serie.fabrica = {$login_fabrica}
			WHERE tbl_produto.produto = {$produto} AND tbl_produto_serie.serie_inicial = '$serie_inicial' AND tbl_produto_serie.serie_final = '$serie_final'";
	$res = pg_query($con, $sql);
	$produto_referencia = pg_fetch_result($res, 0, "referencia");
	$produto_descricao = pg_fetch_result($res, 0, "descricao");
	$ns_inicial = pg_fetch_result($res, 0, "serie_inicial");
	$ns_final = pg_fetch_result($res, 0, "serie_final");
	$observacao = pg_fetch_result($res, 0, "observacao");
	$ativo = pg_fetch_result($res, 0, "serie_ativa");
	$garantia_mes = pg_fetch_result($res, 0, "garantia_mes");
	$data_fabricacao_inicial = pg_fetch_result($res, 0, "fabricacao_inicial");
	$data_fabricacao_final   = pg_fetch_result($res, 0, "fabricacao_final");
}

if($_GET["listagem"] == "true")	{

    $produto_referencia = filter_input(INPUT_GET,"produto_ref");
    $produto_descricao  = filter_input(INPUT_GET,"produto_desc");
    $familia            = filter_input(INPUT_GET,'familia');
    $ns_inicial      = filter_input(INPUT_GET,'ns_ini');
    $ns_final        = filter_input(INPUT_GET,'ns_fin');

    if (!empty($familia)) {
        $sql_cond = " AND tbl_produto.familia = $familia ";
    }  

    if (!empty($produto_referencia)){
        $sql_cond .= " AND tbl_produto.referencia = '{$produto_referencia}' ";
    }

    if (!empty($produto_descricao)) {
        $sql_cond .= " AND tbl_produto.descricao = '{$produto_descricao}' ";
    }

    if (!empty($ns_inicial) && !empty($ns_final)) {
    	if ($ns_inicial > $ns_final) {
    		$msg_erro["msg"][] = "Série Inicial Maior que a Final";
    	} else {
    		$sql_cond .= " AND tbl_produto_serie.serie_inicial BETWEEN '$ns_inicial' AND '$ns_final' ";
    	}
    }

    if (count($msg_erro) == 0) {
	    $sqlListagem = "
	        SELECT  tbl_produto.produto,
	                tbl_produto.referencia,
	                tbl_produto.familia,
	                tbl_produto.descricao,
	                tbl_produto_serie.serie_inicial,
	                tbl_produto_serie.serie_final,
	                tbl_produto_serie.produto_serie,
	                tbl_produto_serie.serie_ativa,
	                tbl_produto_serie.garantia_mes,
			to_char(tbl_produto_serie.fabricacao_inicial,'DD/MM/YYYY') AS fabricacao_inicial,
	                to_char(tbl_produto_serie.fabricacao_final,'DD/MM/YYYY') AS fabricacao_final
	        FROM    tbl_produto
	        JOIN    tbl_produto_serie ON tbl_produto_serie.produto = tbl_produto.produto AND tbl_produto_serie.fabrica = tbl_produto.fabrica_i
	        WHERE   tbl_produto.fabrica_i = {$login_fabrica}
	        $sql_cond
	        ";
	    $resListagem = pg_query($con,$sqlListagem);
    	$listagem = "listagem";
    }
}

$layout_menu = "cadastro";
$title = (in_array($login_fabrica,[6,91])) ? 'CADASTRO DE GARANTIA POR INTERVALO DE NS' : "NS PARA ANÁLISE";
include "cabecalho_new.php";

$plugins = array(
	"shadowbox",
	"mask" ,
	"dataTable",
	"datepicker"
);

include("plugin_loader.php");
?>

<script type="text/javascript">

	$(function () {
		<?php if($login_fabrica == 91){ ?>
			$("#data_fabricacao_inicial").datepicker().mask("99/99/9999");
                	$("#data_fabricacao_final").datepicker().mask("99/99/9999");
		<?php } ?>

		$(document).on("click", "button[name=ativar]", function () {
			if (ajaxAction()) {
				var produto_serie = $(this).parent().find("input[name=produto_serie]").val();
				var that     = $(this);

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "ativar", produto_serie: produto_serie },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(that).removeClass("btn-success").addClass("btn-danger");
							$(that).attr({ "name": "inativar", "title": "Inativar abertura de os para está sequência de número de série" });
							$(that).text("Inativar");
						}

						loading("hide");
					}
				});
			}
		});

		$(document).on("keypress", "#observacao", function () {
			var caracteresDigitados = parseInt($(this).val().length);
            var textoMostrar = caracteresDigitados;
            if(textoMostrar >= 200){
            	var texto = $(this).val();
            	texto.substr(-1)
            	$(this).val(texto);
            	return false
            }
        });

		$(document).on("click", "button[name=inativar]", function () {
			if (ajaxAction()) {
				var produto_serie = $(this).parent().find("input[name=produto_serie]").val();
				var that     = $(this);

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "inativar", produto_serie: produto_serie },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(that).removeClass("btn-danger").addClass("btn-success");
							$(that).attr({ "name": "ativar", "title": "Ativar abertura de os para está sequência de número de série" });
							$(that).text("Ativar");
						}

						loading("hide");
					}
				});
			}
		});

		$(document).on("click", "button[name=excluir]", function () {
			if (ajaxAction()) {
				var produto_serie = $(this).parent().find("input[name=produto_serie]").val();
				var that     = $(this);

				$.ajax({
					async: false,
					url: "<?=$_SERVER['PHP_SELF']?>",
					type: "POST",
					dataType: "JSON",
					data: { btn_acao: "excluir", produto_serie: produto_serie },
					beforeSend: function () {
						loading("show");
					},
					complete: function (data) {
						data = data.responseText;

						if (data == "success") {
							$(that).parents("tr").remove();
						}

						loading("hide");
					}
				});
			}
		});
	});

	function retornaSeriesCadastradas(referencia){
		$.ajax({
			url: "<?=$PHP_SELF;?>",
			type: "POST",
			data:{
				series_cadastradas: "series_cadastradas",
				referencia: referencia
			},
			complete: function(data){

				respJson = $.parseJSON(data.responseText);
				if(respJson.length > 0){

					for (var i = 0; i < respJson.length; i++) {
						var tr = $("<tr>");

						var a = $("<a>").attr("href","<?=$PHP_SELF.'?produto=';?>"+respJson[i].produto+"&ps="+respJson[i].produto_serie);
						a.html(respJson[i].referencia);

						var tdReferencia 	= $("<td>").append(a);

						a = $("<a>").attr("href","<?=$PHP_SELF.'?produto=';?>"+respJson[i].produto+"&ps="+respJson[i].produto_serie);
						a.html(respJson[i].descricao);

						var tdDescricao 	= $("<td>").html(a);
						var tdSerieInicial 	= $("<td>").html(respJson[i].serie_inicial);
						var tdSerieFinal 	= $("<td>").html(respJson[i].serie_final);

						tr.append(tdReferencia);
						tr.append(tdDescricao);
						tr.append(tdSerieInicial);
						tr.append(tdSerieFinal);
						$("#produto_series_cadastradas").append(tr);
					};

					$("#produto_series_cadastradas").show();
				}

			}
		});
	}

	$(function(){
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

	});

	function retorna_produto (retorno) {

		$("#produto_referencia").val(retorno.referencia);
		$("#produto_descricao").val(retorno.descricao);
		retornaSeriesCadastradas(retorno.referencia);

		$("div.box-produto-ref").removeClass("error");
		$("div.box-produto-desc").removeClass("error");
		$(".box-produto-msg").html("");

	}

	<? if ($login_fabrica == 6) { ?>
		$(document).ready(function () {
			$('#garantia_mes').keypress(function (e) {
					var verified = (e.which == 8 || e.which == undefined || e.which == 0) ? null : String.fromCharCode(e.which).match(/[^0-9]/);
							if (verified) {e.preventDefault();}
			});
		});

	<? } ?>

	function listagem_produtos(){

		$("div.box-produto-ref").removeClass("error");
		$("div.box-produto-desc").removeClass("error");
		$("div.box-ns-ini").removeClass("error");
		$("div.box-ns-fin").removeClass("error");
		$(".box-produto-msg").html("");

		$("div[class^='control-group']").removeClass("error");

		$("div[class^='alert']").remove();

        let produto_ref     = $("#produto_referencia").val();
        let produto_desc    = $("#produto_descricao").val();
        let familia         = $("#familia").val();
        let ns_ini          = $("#ns_inicial").val();
		let ns_fin          = $("#ns_final").val();


		var sem_produto = true;

		if ((produto_ref != "" && produto_desc != "") || (familia != "") || (ns_ini != "" && ns_fin != "")) {
			sem_produto = false;
		} else {
			$("div.box-produto-ref").addClass("error");
			$("div.box-produto-desc").addClass("error");
			$("div.box-familia").addClass("error");
			$("div.box-ns-ini").addClass("error");
			$("div.box-ns-fin").addClass("error");
		}

		if (sem_produto == true) {
			$(".box-produto-msg").html("<div class='alert alert-error'> <h4> Preencha os campos Obrigatórios </h4> </div>");
			return;
		}

		window.location = "<?=$PHP_SELF;?>?listagem=true&produto_ref="+produto_ref+"&produto_desc="+produto_desc+"&familia="+familia+"&ns_ini="+ns_ini+"&ns_fin="+ns_fin;
	}

</script>
<?php
if (strlen($msg_success) > 0) {
?>
	<div class="alert alert-success">
		<h4><?=$msg_success?></h4>
	</div>
<?php
}

if (count($msg_erro["msg"]) > 0) {
?>
	<div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
	</div>
<?php
}
?>

<div class="box-produto-msg"></div>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
	<input type="hidden" name="produto" value="<?=$produto?>" />
	<input type="hidden" name="ps" value="<?=$ps?>" />
	<input type="hidden" id="listagem" name="listagem" value="<?=$listagem?>" />
	<? if(strlen($produto) > 0 && strlen($ps) > 0){ ?>
			<div class='titulo_tabela '>Atualizar</div>
	<? }else {?>
			<div class='titulo_tabela '>Cadastro</div>
	<?}?>

		<br/>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group box-produto-ref <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'> Ref. Produto</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<h5 class="asteristico">*</h5>
							<input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" <?= ($login_fabrica == 6 && isset($_GET['produto'])) ? 'readonly="true"' : ''; ?>>
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group box-produto-desc <?=(in_array("descricao", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'>Descrição Produto</label>
					<div class='controls controls-row'>
						<div class='span11 input-append'>
							<h5 class="asteristico">*</h5>
							<input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" <?= ($login_fabrica == 6 && isset($_GET['produto'])) ? 'readonly="true"' : ''; ?>>
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<div class='control-group box-familia <?=(in_array("familia", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='familia'> Família</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
                            <select name="familia" id="familia">
                                <option value="">&nbsp;</option>
									<?php
									$sqlFamilia = "
									    SELECT  familia,
									            descricao
									    FROM    tbl_familia
									    WHERE   fabrica = $login_fabrica
									    AND     ativo IS TRUE
									ORDER BY    descricao
									";
									$resFamilia = pg_query($con,$sqlFamilia);

									while ($familias = pg_fetch_object($resFamilia)) {
									?>
                                		<option value="<?=$familias->familia?>" <?=($familias->familia == $familia) ? "selected" : ""?>><?=$familias->descricao?></option>
									<?php
									}
									?>
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
				<div class='control-group box-ns-ini <?=(in_array("ns_inicial", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Número Série Inicial</label>
					<div class='controls controls-row'>
						<h5 class="asteristico">*</h5>
						<input type="text" id="ns_inicial" name="ns_inicial" class='span12' value="<? echo $ns_inicial ?>" <?= ($login_fabrica == 6 && isset($_GET['produto'])) ? 'readonly="true"' : ''; ?>>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group box-ns-fin <?=(in_array("ns_final", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>Número Série Final</label>
					<div class='controls controls-row'>
						<h5 class="asteristico">*</h5>
						<input type="text" id="ns_final" name="ns_final" class='span12' value="<? echo $ns_final ?>" <?= ($login_fabrica == 6 && isset($_GET['produto'])) ? 'readonly="true"' : ''; ?>>
					</div>
				</div>
			</div>
		</div>
		<?php
                if($login_fabrica == 91){
                ?>

		<div class="row-fluid">
			<div class="span2"></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_fabricacao_inicial'>Data Fabricação Inicial</label>
					<div class='controls controls-row'>
						<h5 class="asteristico">*</h5>
						<input class="span6" type="text" name="data_fabricacao_inicial" id="data_fabricacao_inicial" size="12" maxlength="10" value="<? if (strlen($data_fabricacao_inicial) > 0) echo $data_fabricacao_inicial; ?>" >
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_fabricacao_final'>Data Fabrcação Final</label>
					<div class='controls controls-row'>
						<h5 class="asteristico">*</h5>
						<input class="span6" type="text" name="data_fabricacao_final" id="data_fabricacao_final" size="12" maxlength="10" value="<? if (strlen($data_fabricacao_final) > 0) echo $data_fabricacao_final;  ?>" >
					</div>
				</div>
			</div>
			<div class="span2"></div>
		</div>

		<?php } ?>

		<?php
		if($login_fabrica == 6){
		?>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group <?=(in_array("garantia_mes", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'>* Mês(es) Garantia</label>
								<div class='controls controls-row'>
										<input type="number" id="garantia_mes" name="garantia_mes" class='span4' value="<? echo $garantia_mes; ?>">
								</div>
							</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php } ?>

		<?php
		if (isFabrica(24,30, 74)) {
		?>

		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<label class='control-label' for='linha'>Observação</label>
				<div class='controls controls-row'>
					<textarea id="observacao" name="observacao" class='span12' rows="4" ><? echo $observacao; ?></textarea> <br>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php } ?>

		<?php if (isFabrica(15, 24, 30, 74)) { ?>
		<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span8'>
				<label class="checkbox">
					<input type="checkbox" name="ativo" value="<?=($ativo != "f") ? "t" : "f"?>" <?=($ativo != "f") ? "checked" : ""?> />
					Ativo
				</label>
			</div>
			<div class='span2'></div>
		</div>

		<?php } ?>

		<p>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<button class='btn' id="btn_acao" ype="button"  onclick="submitForm($(this).parents('form'), 'gravar');">Gravar</button>
			<?php
			if ($_GET["produto"]) {
			?>
				<button class='btn btn-warning' id="btn_limpar" type="button" onclick="window.location='<?=$PHP_SELF?>'">Limpar</button>
			<?php
			}
			?>
			<button class='btn btn-primary' id="btn_listar" type="button" onclick="listagem_produtos()">Pesquisar</button>
		</p>
		<br/>
</form>

<?
	if($_GET["listagem"]=="true"){ ?>

		<?php if(pg_num_rows($resListagem) > 0){ ?>

		<div id="DataTables_Table_0_wrapper" class="dataTables_wrapper form-inline" role="grid" >
			<table id="produto_series_cadastradas_listagem" class="table table-striped table-bordered table-hover table-fixed" >
				<thead>
					<tr class='titulo_coluna'>
						<th>Referência</th>
						<th>Descrição</th>
						<th>Série Inicial</th>
						<th>Série Final</th>
			<?php if (isFabrica(20)): ?>
						<th>Mês(es) Garantia</th>
			<?php endif; ?>
			  <?php if (isFabrica(91)): ?>
                        <th>Data Fabricação Inicial</th>
						<th>Data Fabricação Final</th>
                        <?php endif; ?>
            <?php if (isFabrica(6)) { ?>
            		<th>Garantia Mês</th>
            <?php } ?>
			<?php if (isFabrica(6,15,20,30,74)): ?>
						<th>Ações</th>
			<?php endif; ?>
					</tr>
				</thead>
				<tbody>
			<? for ($i = 0; $i < pg_num_rows($resListagem); $i++) {
				$produto       = pg_fetch_result($resListagem, $i, "produto");
				$produto_serie = pg_fetch_result($resListagem, $i, "produto_serie");
				$referencia    = pg_fetch_result($resListagem, $i, "referencia");
				$descricao     = pg_fetch_result($resListagem, $i, "descricao");
				$serie_inicial = pg_fetch_result($resListagem, $i, "serie_inicial");
				$serie_final   = pg_fetch_result($resListagem, $i, "serie_final");
				$ativo         = pg_fetch_result($resListagem, $i, "serie_ativa");
				$garantia_mes  = pg_fetch_result($resListagem, $i, "garantia_mes");
				$familia       = pg_fetch_result($resListagem, $i, "familia");
				$link_data     = compact('produto', 'serie_inicial', 'serie_final');
				$link_data['ps'] = $produto_serie;
				$link_data['familia'] = $familia;
				$fabricacao_inicial = pg_fetch_result($resListagem, $i, "fabricacao_inicial");
				$fabricacao_final = pg_fetch_result($resListagem, $i, "fabricacao_final");

				if (isFabrica(6)) {
					$link_data['garantia_mes'] = $garantia_mes;
				}
				$link_produto  = $_SERVER['PHP_SELF'] . '?' . http_build_query($link_data); ?>
						<tr>
							<td class="tac"><a href="<?=$link_produto?>"><?=$referencia?></a></td>
							<td class="tac"><a href="<?=$link_produto?>"><?=$descricao?></a></td>
							<td class="tac"><?=$serie_inicial?></td>
							<td class="tac"><?=$serie_final?></td>
						<?php if (isFabrica(91)) { ?>
							 <td class='tac'><?=$fabricacao_inicial?></td>
							 <td class='tac'><?=$fabricacao_final?></td>
						<?php } ?>
				<?php if (isFabrica(15,20,30,74)) { ?>
							<td class='tac'>
								<input type="hidden" name="produto_serie" value="<?=$produto_serie?>" />
					<?php if (isFabrica(15, 30, 74)) {
							$btnLabel = ($ativo == 'f') ? 'Ativar'      : 'Inativar';
							$btnColor = ($ativo == 'f') ? 'btn-success' : 'btn-danger';
							$btnName  = strtolower($btnLabel);
							$btnTitle = "$btnLabel a abertura de OS para esta sequência de números de série"; ?>
								<button class="btn btn-small <?=$btnColor?>" type="button" name="<?=$btnName?>" title="<?=$btnTitle?>"><?=$btnLabel?></button>
					<?php } ?>
								<button type='button' name='excluir' class='btn btn-small btn-warning' title='Excluir sequência de número de série' >Excluir</button>
							</td>
				<?php
				}
				if ($login_fabrica == 6) { ?>
							<td class="tac"><?=$garantia_mes?></td>
							<td class="tac">
								<input type="hidden" name="produto_serie" value="<?=$produto_serie?>" />
								<a href="<?=$link_produto?>" class='btn btn-small btn-success' title='Alterar sequência de número de série'>Alterar</a>
								<button type='button' name='excluir' class='btn btn-small btn-warning' title='Excluir sequência de número de série'>Excluir</button>
							</td>
				<?php } ?>
						</tr>
			<? } ?>
				</tbody>
			</table>
		</div>

		<?php if($i > 1){ ?>
			<script type="text/javascript">
				$.dataTableLoad();
			</script>

		<?php } ?>

		<?php } else if (count($msg_erro) == 0) { ?>

		<div class="alert alert-warning">
			<h4>Nunhum resultado encontrado</h4>
		</div>

		<?php } ?>

 <? } ?>
<?php include 'rodape.php'; ?>

