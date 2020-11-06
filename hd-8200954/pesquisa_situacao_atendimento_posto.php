<?php

if ($_REQUEST["aplicativo"] == "t") {
	exit(json_encode(["sucesso" => true]));
}

$pesquisa      = $_REQUEST["pesquisa"];
$posto         = $_REQUEST["posto"];
$acao          = $_REQUEST["acao"];          
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
#include 'autentica_usuario.php';
include 'funcoes.php';

try {

	$easyBuilderMirror = new \Mirrors\EasyBuilderMirror(10, 'questionario_avaliacao', $login_admin);

	if (isset($_POST["btn_acao"])) {

		#$retorno = $easyBuilderMirror->gravaResposta($_POST);
		$resposta = json_encode($_POST['formulario']);
		$sql = "INSERT INTO tbl_resposta(pesquisa,posto,txt_resposta) VALUES({$pesquisa},{$posto},'{$resposta}')";
		$res = pg_query($con,$sql);

		if(strlen(pg_last_error()) == 0){
			$sucessoPesquisa = true;
		}

	} else {

		#$dadosPesquisa = $easyBuilderMirror->get($pesquisa);
		$sql = "SELECT formulario FROM tbl_pesquisa_formulario WHERE pesquisa = {$pesquisa}";
		$res = pg_query($con,$sql);
		
		$arrFormulario = json_decode(pg_fetch_result($res,0,'formulario'),true);
		$arrFormulario = array_map_recursive("utf8_decode", $arrFormulario);
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
				height: 5%;
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
				height: 10%;
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
if ($sucessoPesquisa == true) { 
?>
	
	  <script>
		window.parent.location.reload();  
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
									    <label class="col-sm-5 control-label">
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
		<div class="finalizar-fixo" style="margin-bottom: 5px;">
			<div class="row row-fluid">
				<button class="btn btn-default" id="btn_finalizar">
					Responder
				</button>
			</div>
		</div>
	</form>
<script>

	$("#btn_finalizar").click(function(e){

		e.preventDefault();

		var erro = false;
		
		$(".multiplos[obrigatorio=t]").each(function(){

			if ($(this).find("input:checked").length == 0) {

				$(this).addClass("has-error");
				$(".alert-danger").show();
				erro = true;

			} else {

				$(this).removeClass("has-error");

			}
			
		});


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
