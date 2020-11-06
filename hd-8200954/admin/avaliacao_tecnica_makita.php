<?php

if ($_REQUEST["aplicativo"] == "t") {
	exit(json_encode(["sucesso" => true]));
}

$pesquisa      = $_REQUEST["pesquisa"];
$posto         = $_REQUEST["posto"];
$resposta      = $_REQUEST["resposta"];
$tecnico       = $_REQUEST["tecnico"];
$cnpjPosto     = $_REQUEST["cnpj"];
$acao          = $_REQUEST["acao"];          

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$autentica_admin = true;
if (!empty($_REQUEST["hashApp"])) {

	$strValida = base64_decode($_REQUEST["hashApp"]);

	if ($strValida == ($tecnico.$cnpjPosto.$pesquisa)) {

		$login_fabrica = $_REQUEST["fabrica"];
		
		$autentica_admin = false;

		$sqlPosto = "SELECT tbl_posto.posto
					 FROM tbl_posto
					 JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					 AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					 WHERE tbl_posto.cnpj = '{$cnpjPosto}'";
		$resPosto = pg_query($con, $sqlPosto);

		if (pg_num_rows($resPosto) > 0) {

			$posto = pg_fetch_result($resPosto, 0, 'posto');

		} else {

			exit("Parâmetros inválidos #1");

		}

	} else {

		exit("Parametros inválidos #2");

	}

}

if ($autentica_admin) {
	include 'autentica_admin.php';
}

if (isset($_POST['excluirResposta']) && $_POST['excluirResposta']) {
	
	try {

		$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, 'questionario_avaliacao', $login_admin);
		
		$retorno = $easyBuilderMirror->deleteResposta($_POST);

	} catch(\Exception $e){

	    $msg_erro = utf8_encode($e->getMessage());

	}

	if (empty($msg_erro)) {
		
		$retorno = [
			"success" => true
		];

	} else {

		$retorno = [
			"success" => false, 
			"msg" => $msg_erro
		];

	}

	exit(json_encode($retorno));

}

include 'funcoes.php';

try {

	$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, 'questionario_avaliacao', $login_admin);

	if (isset($_POST["btn_acao"])) {

		if (isset($_POST["resposta_hidden"])) {
			$retorno = $easyBuilderMirror->alteraResposta($_POST, [
				"dataProximaAvaliacao" => formata_data($_POST['data_proxima_avaliacao']),
				"pontuacaoTotal" 	   => (int) $_POST['pontuacao']
			]);

		} else {
			$retorno = $easyBuilderMirror->gravaResposta($_POST, [
				"dataProximaAvaliacao" => formata_data($_POST['data_proxima_avaliacao']),
				"pontuacaoTotal" 	   => (int) $_POST['pontuacao']
			]);
		}

		$sucesso = true;

	} else {

		$dadosPesquisa = $easyBuilderMirror->get($pesquisa);

		$arrFormulario = json_decode($dadosPesquisa["campos"][0]["formulario"], true);
		$arrFormulario = array_map_recursive("utf8_decode", $arrFormulario);

		if (!empty($resposta)) {

			$dadosResposta = $easyBuilderMirror->getRespostas([], [
				"respostaId" => $resposta
			]);

			$dadosResposta = json_decode($dadosResposta["campos"][0]["txt_resposta"], true);

		}

	}

} catch(\Exception $e){

    $msg_erro["msg"][] = utf8_decode($e->getMessage());

}
?>
<html>
	<head>
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<?php
		$plugins = array(
		   "bootstrap3",
		   "dataTableAjax",
		   "datepicker",
		   "mask"
		);

		include "plugin_loader.php";

		?>
		<style>
			body {
				font-family: sans-serif;
				background-color: #D9E2EF;
			}
			.panel-heading {
				font-weight: bolder;
				text-align: center;
			}
			.input-text[type=number] {
				width: 90px;
			}
			.control-label {
				text-align: left !important;
			}
			.obrigatorio {
				color: red;
			}
			.info-obrigatorios {
				font-size: 12px;
				margin-bottom: -30px;
			}
			.panel {
				margin-top: 50px;
				margin-left: 0px !important;
				margin-right: 0px !important;
			}
			.panel:last-of-type {
				margin-bottom: 30%;
			}
			.titulo {
				text-align: center;
				margin: 0 !important;
				font-family: sans-serif;
				font-size: 17px;
				letter-spacing: 1.5px;
				color: white;
				font-weight: bolder;
			}
			.control-label {
				cursor: pointer;
			}
			.area-titulo {
				height: 15%;
				background-color: #2e6da4;
				position: relative;
				top: 0;
				left: 0;
				width: 100%;
				border-bottom: darkgray 2px solid;
				padding: 20px;
				margin-bottom: 30px;
			}
			.lbl-multiplos {
				margin-right: 10px;
				cursor: pointer;
				font-weight: normal !important;
			}
			.finalizar-fixo {
				height: 20%;
				width: 100%;
				top: 80%;
				background-color: #2e6da4;
				position: fixed;
				text-align: center;
				padding-top: 5px;
			}
			hr {
				border-top: 1px solid lightgray !important;
			}
			.alert-success,
			.alert-warning {
				width: 80%;
				left: 10%;
				top: 20%;
				position: absolute;
			}
		</style>
	</head>
<body>
<?php

$res_campo = (!empty($resposta)) ? " AND resposta = $resposta" : "";

$sqlResposta = "SELECT campos_adicionais
                FROM tbl_resposta
                WHERE pesquisa = {$pesquisa}
                AND posto = {$posto}
                $res_campo
                ORDER BY resposta DESC
                LIMIT 1";
$resResposta = pg_query($con, $sqlResposta);

$bloqueiaAvaliacao = false;
if (pg_num_rows($resResposta) > 0) {

    $arrCamposAdicionais  = json_decode(pg_fetch_result($resResposta, 0, "campos_adicionais"), true);
    $dataProximaAvaliacao = $arrCamposAdicionais["dataProximaAvaliacao"];
    $pontuacaoTotal       = (empty($arrCamposAdicionais["pontuacaoTotal"])) ? 0 : $arrCamposAdicionais["pontuacaoTotal"];

    if ($dataProximaAvaliacao > date("Y-m-d")) {
        $bloqueiaAvaliacao = true;
    }

    if (!empty($dataProximaAvaliacao)) {
    	$dataProximaAvaliacao = mostra_data($dataProximaAvaliacao);
    }

}

if ($_POST['resposta_hidden']) {
?>
	<script>
		parent.location.reload();
	</script>
<?php
}

if ($sucesso) { ?>
	<div class='alert alert-success'>
		<h4><strong>Avaliação realizada com sucesso! <br /><br /> a próxima avaliação foi marcada para a data <?= $_POST['data_proxima_avaliacao'] ?></strong></h4>
	  </div>
	  <script>
	  $(".btn-avaliacao-posto", window.parent.document).hide();
	  </script>
<?php
	exit;
}
?>
<div class="area-titulo">
	<h2 class="titulo"><?= $arrFormulario["titulo"] ?></h2>
</div>
<form id="form_pesquisa" class="form-horizontal" action="<?= $_SERVER["PHP_SELF"] ?>" method="POST">

	<input type="hidden" name="btn_acao" value="gravar" />
	<input type="hidden" name="pesquisa" value="<?= $pesquisa ?>" />
	<input type="hidden" name="posto" value="<?= $posto ?>" />
	<input type="hidden" name="tecnico" value="<?= $tecnico ?>" />
	<input type="hidden" name="cnpj" value="<?= $cnpjPosto ?>" />
	<input type="hidden" name="hashApp" value="<?= $_REQUEST["hashApp"] ?>" />
	<input type="hidden" name="fabrica" value="<?= $_REQUEST["fabrica"] ?>" />

	<div class="container-fluid">
		<div class="alert alert-danger" hidden>
			<h5 style="text-align: center;">Preencha os campos obrigatórios!</h5>
		</div>
		<div class="info-obrigatorios">
			<span class='obrigatorio'>*</span> Campos Obrigatórios
		</div>
			
		<?php
			foreach ($arrFormulario["formulario"] as $indexGrupo => $arrGrupo) { ?>
				<div class="panel panel-primary">
					<div class="panel-heading">
						<?= $arrGrupo["tituloGrupo"] ?>
					</div>
					<div class="panel-body" style="padding: 15px;">
						<?php
						foreach ($arrGrupo["perguntas"] as $indexPergunta => $arrPergunta) { 

							$tipoCampo = array_keys($arrPergunta)[0];

							$divObrigatorio = ($arrPergunta["obrigatorio"] == "t") ? "<span class='obrigatorio'>*</span> " : "";

							if (count($dadosResposta) > 0) {
								$textoResposta = $dadosResposta[$indexGrupo]["perguntas"][$indexPergunta];
							}

							switch ($tipoCampo) {
								case 'text':
								case 'number':

								if ($tipoCampo == "number") {
									$attrAdicional = 'min="0"';
								}

								?>
									<div class="form-group">
									    <label class="col-sm-4 control-label">
									    	<?= $divObrigatorio.$arrPergunta[$tipoCampo] ?>:
									    </label>
									    <div class="col-sm-8">
									      <input <?= $attrAdicional ?> type="<?= $tipoCampo ?>" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>]" class="form-control input-text" obrigatorio="<?= $arrPergunta["obrigatorio"] ?>" value="<?= $textoResposta ?>" />
									    </div>
									</div>
								<?php
									break;

								case 'textarea': 
								?>
									<div class="form-group">
									    <label class="col-sm-4 control-label">
									    	<?= $divObrigatorio.$arrPergunta[$tipoCampo] ?>:
									    </label>
									    <div class="col-sm-8">
									      <textarea class="form-control input-text" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>]" obrigatorio="<?= $arrPergunta["obrigatorio"] ?>"><?= $textoResposta ?></textarea>
									    </div>
									</div>
								<?php
									break;

								case 'radio':
								case 'checkbox': 
								?>
									<div class="form-group multiplos" obrigatorio="<?= $arrPergunta["obrigatorio"] ?>">
									    <label class="col-sm-4 control-label">
									    	<?= $divObrigatorio.$arrPergunta[$tipoCampo]["descricao"] ?>:
									    </label>
									    <div class="col-sm-8" style="padding-left: 50px;">
									    <?php
									    $valor_input = 0;
									    foreach ($arrPergunta[$tipoCampo]["options"] as $indexOpcao => $descricao) { 

									    	?>
									      	<label class="lbl-multiplos">
										      	<?php
										      	if ($tipoCampo == "radio") { 

										      		if (count($dadosResposta) > 0) {
											    		$valorResposta = $dadosResposta[$indexGrupo]["perguntas"][$indexPergunta][$tipoCampo]["options"];

											    		if ($valorResposta == $indexOpcao) {
											    			$checkedOption = "checked";
											    			$valor_input = $arrPergunta["parametrosAdicionais"]["pontos"][$indexOpcao];
											    		} else {
											    			$checkedOption = "";
											    		}
											    	}

											    	$pontosPergunta = $arrPergunta["parametrosAdicionais"]["pontos"][$indexOpcao];

										      		?>
										      		<input <?= $checkedOption ?> class="input-radio" type="radio" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>][radio][options]" value="<?= $indexOpcao ?>" data-pontos="<?= $pontosPergunta ?>" />
										      	<?php
										      	} else { 

										      		if (count($dadosResposta) > 0) {
											    		$valorResposta = $dadosResposta[$indexGrupo]["perguntas"][$indexPergunta][$tipoCampo]["options"][$indexOpcao];

											    		$checkedOption = ($valorResposta == $indexOpcao) ? "checked" : "";
											    	}

										      		?>
										      		<input <?= $checkedOption ?> class="input-checkbox" type="checkbox" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>][checkbox][options][<?= $indexOpcao ?>]" value="<?= $indexOpcao ?>" />
										      	<?php
										      	}
										      	echo $descricao;
										      	?>
										    </label><br />
									    <?php
									    } ?>
									    </div>
									    <input class="pontos-pergunta" value="<?=$valor_input?>" type="hidden" />
									</div>
								<?php
									break;
							}
							echo "<hr />";
						} ?>
					</div>
				</div>
			<?php
			}
		?>
	</div>
	<?php
	if (empty($resposta)) {
	?>
		<div class="finalizar-fixo" style="margin-bottom: 5px;">
			<div class="row row-fluid">
				<div class="form" style="width: 40%;margin-left: 5%;text-align: right;margin-bottom: 5px;float: left;">
					<strong style="color: white;">Data da próxima visita <span color="red">*</span></strong>
					<br />
					<input type="text" id="proxima_avaliacao" name="data_proxima_avaliacao" style="width: 200px;text-align: center;" />
				</div>
				<div class="form" style="width: 40%;margin-right: 5%;text-align: left;margin-bottom: 5px;float: right;">
					<strong style="color: white;">Pontuação  <span color="red">*</span></strong>
					<br />
					<input readonly type="text" id="pontuacao" name="pontuacao" value="0" style="width: 150px;text-align: center;background-color: lightgray;border-radius: 4px;" />
				</div>
			</div>
			<div class="row row-fluid">
				<button class="btn btn-default" id="btn_finalizar">
					Finalizar Pesquisa
				</button>
			</div>
		</div>
	<?php
	} else if ($acao == 'alterar') {
	?>
		<div class="finalizar-fixo" style="margin-bottom: 5px;">
			<div class="row row-fluid">
				<div class="form" style="width: 40%;margin-left: 5%;text-align: right;margin-bottom: 5px;float: left;">
					<strong style="color: white;">Data da próxima visita <span color="red">*</span></strong>
					<br />
					<input type="text" id="proxima_avaliacao" name="data_proxima_avaliacao" value="<?=$dataProximaAvaliacao?>" style="width: 200px;text-align: center;" />
				</div>
				<div class="form" style="width: 40%;margin-right: 5%;text-align: left;margin-bottom: 5px;float: right;">
					<strong style="color: white;">Pontuação  <span color="red">*</span></strong>
					<br />
					<input readonly type="text" id="pontuacao" name="pontuacao" value="<?=$pontuacaoTotal?>" style="width: 150px;text-align: center;background-color: lightgray;border-radius: 4px;" />
				</div>
			</div>
			<div class="row row-fluid">
				<button class="btn btn-default" id="btn_finalizar">
					Alterar Pesquisa
				</button>
				<input type="hidden" name="resposta_hidden" value="<?= $resposta ?>" />
			</div>
		</div>
	<?php
	} else { ?>
		<script>
			$(function(){
				$("input, textarea").prop("disabled", true);
			});
		</script>
	<?php
	}
	?>
</form>
<script>

	$("#proxima_avaliacao").mask("99/99/9999");

	$(".input-radio").click(function(){

		let inputPontos    = $(this).closest(".multiplos").find(".pontos-pergunta");
		let pontosAnterior = $(inputPontos).val();
		let p = parseInt($(this).data("pontos"));

		if (isNaN(p)) {
			p = 0;
		}

		let pontuacaoTotal = (parseInt($("#pontuacao").val()) - parseInt(pontosAnterior)) + p;

		$("#pontuacao").val(pontuacaoTotal);
		$(inputPontos).val(parseInt($(this).data("pontos")));

	});

	$("#btn_finalizar").click(function(e){

		e.preventDefault();

		var erro = false;

		$(".input-text[obrigatorio=t]").each(function(){
			
			if ($(this).val() == "" && $(this).text() == "") {

				$(this).closest(".form-group").addClass("has-error");
				$(".alert-danger").show();
				erro = true;

			} else {
				$(this).closest(".form-group").removeClass("has-error");
			}
			
		});

		$(".multiplos[obrigatorio=t]").each(function(){

			if ($(this).find("input:checked").length == 0) {

				$(this).addClass("has-error");
				$(".alert-danger").show();
				erro = true;

			} else {

				$(this).removeClass("has-error");

			}
			
		});

		if ($("#proxima_avaliacao").val() == "") {

			$(".alert-danger").show();
			$("#proxima_avaliacao").css({
				"border-color": "red"
			});
			erro = true;

		} else {

			if (!validateDate($("#proxima_avaliacao"))) {
				erro = true;
				$(".alert-danger").show().text("Informe uma data válida");
				$("#proxima_avaliacao").css({
					"border-color": "red"
				});
			}

		}

		if (erro) {
			$("html, body").animate({ scrollTop: 0 }, 600);
		} else {
			$("#form_pesquisa").submit();
		}

	});

	function validateDate(id) {
      var RegExPattern = /^((((0?[1-9]|[12]\d|3[01])[\.\-\/](0?[13578]|1[02])      [\.\-\/]((1[6-9]|[2-9]\d)?\d{2}))|((0?[1-9]|[12]\d|30)[\.\-\/](0?[13456789]|1[012])[\.\-\/]((1[6-9]|[2-9]\d)?\d{2}))|((0?[1-9]|1\d|2[0-8])[\.\-\/]0?2[\.\-\/]((1[6-9]|[2-9]\d)?\d{2}))|(29[\.\-\/]0?2[\.\-\/]((1[6-9]|[2-9]\d)?(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)|00)))|(((0[1-9]|[12]\d|3[01])(0[13578]|1[02])((1[6-9]|[2-9]\d)?\d{2}))|((0[1-9]|[12]\d|30)(0[13456789]|1[012])((1[6-9]|[2-9]\d)?\d{2}))|((0[1-9]|1\d|2[0-8])02((1[6-9]|[2-9]\d)?\d{2}))|(2902((1[6-9]|[2-9]\d)?(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)|00))))$/;

     if (!((id.val().match(RegExPattern)) && (id.val!=''))) {
          return false;
 		  id.focus();
        }
       else
        return true;
    }

</script>
</body>
</html>
