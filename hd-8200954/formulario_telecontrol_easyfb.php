<?php

if (empty($pesquisa)) {

	$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include "funcoes.php";

	if ($areaAdmin === true) {
	    include __DIR__.'/admin/autentica_admin.php';
	} else {
	    include __DIR__.'/autentica_usuario.php';
	}    

	$pesquisa      = $_REQUEST["pesquisa"];
	$posto         = $_REQUEST["posto"];

} else {

	$posto 		  = $login_posto;

}

$resposta = $_REQUEST["resposta"];

$categoria = getCategoriaPesquisa($pesquisa);

if (isset($_POST['excluirResposta']) && $_POST['excluirResposta']) {
	
	try {

		$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, $categoria, $login_admin);
		
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

try {

	$easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, $categoria, $login_admin);

	$dadosResposta = [];
	if (isset($_POST["btn_acao"])) {

		$camposAdicionais = [];

		if (in_array($login_fabrica, [1])) {

			$className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\PostoFabrica';
			$classPosto = new $className($login_fabrica, $con);

			$camposAdicionais["dadosAnteriores"] = $classPosto->getDadosPosto($login_posto);

		}

		$retorno = $easyBuilderMirror->gravaResposta($_POST, $camposAdicionais);

		$sucesso = true;

	} else {

		$dadosPesquisa = $easyBuilderMirror->get($pesquisa);

		$arrFormulario = json_decode($dadosPesquisa["campos"][0]["formulario"], true);
		$arrFormulario = array_map_recursive("utf8_decode", $arrFormulario);

		if (!empty($resposta)) {

			$dadosRespostaRetorno = $easyBuilderMirror->getRespostas([], [
				"respostaId" => $resposta
			]);

			$dadosRespostaAdicionais = json_decode($dadosRespostaRetorno["campos"][0]["campos_adicionais"], true);
			$dadosRespostaAdicionais = array_map_recursive("utf8_decode", $dadosRespostaAdicionais);

			$dadosResposta = json_decode($dadosRespostaRetorno["campos"][0]["txt_resposta"], true);
			$dadosResposta = array_map_recursive("utf8_decode", $dadosResposta);

		}

	}

} catch(\Exception $e){

    $msg_erro["msg"][] = utf8_decode($e->getMessage());

}

function buscaLinhaResposta($nomeLinha, $dadosResposta, $arrFormulario) {

	foreach ($dadosResposta[1]["perguntas"] as $indexPergunta => $dadosResposta) {

		$indexResposta = $dadosResposta["radio"]["options"];

		$perguntaFormulario = $arrFormulario["formulario"][1]["perguntas"][$indexPergunta]["radio"];

		if (trim(strtolower($nomeLinha)) == trim(strtolower($perguntaFormulario["descricao"]))) {

			return $perguntaFormulario["options"][$indexResposta];

		}
		
	}

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
		   "maskedinput",
   		   "alphanumeric"
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
				height: 7.5%;
				background-color: #2e6da4;
				position: relative;
				top: 0;
				left: 0;
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
				top: 90%;
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
			#dados_anteriores th {
				color: white;
				background-color: #2e6da4;
				text-align: center;
			}
			#dados_anteriores td {
				background-color: white;
			}
		</style>
	</head>
<body>
<?php
if ($sucesso) { ?>
	<div class='alert alert-success'>
		<h4><strong>Pesquisa realizada com sucesso! <?= (!empty($login_posto)) ? " você será direcionado em alguns segundos..." : "" ?></strong></h4>
	  </div>
	  <script>
	  $(".btn-avaliacao-posto", window.parent.document).hide();

	  <?php
	  if (!empty($login_posto)) { ?>

	  	setTimeout(function(){
	  		window.location = "menu_inicial.php";
	  	}, 3000);
	  	
	  <?php
	  } ?>

	  </script>
<?php
	exit;
}
?>
<div class="row">
	<div class="area-titulo col-xs-12 col-sm-10 col-sm-offset-1 col-lg-8 col-lg-offset-2">
		<h2 class="titulo"><?= $arrFormulario["titulo"] ?></h2>
	</div>
	<?php
    if (count($dadosResposta) == 0) { ?>
    	<div class="col-xs-12 col-sm-10 col-sm-offset-1 col-lg-8 col-lg-offset-2">
			<strong><?= nl2br($arrFormulario["descricao"]) ?></strong>
		</div>
    <?php
    } else if (in_array($login_fabrica, [1])) { ?>
	    <div class="row">
	    	<div class="col-sm-10 col-sm-offset-1">
	    		<center>
	    			<button class="btn btn-info" onclick=" $('#dados_anteriores').toggle('slow') ">
	    				Visualizar Dados Anteriores
	    			</button>
	    		</center>
	    		<br />
	    		<table id="dados_anteriores" class="table table-bordered" hidden>
	    			<thead>
		    			<tr>
		    				<th>Descrição</th>
		    				<th>Dados Anteriores</th>
		    				<th>Dados Atuais</th>
		    			</tr>
	    			</thead>
	    			<tbody>
				    	<?php
						$dadosPosto = $dadosRespostaAdicionais["dadosAnteriores"]["dadosPosto"];
						?>
						<tr>
							<td><strong>Nome Fantasia</strong></td>
							<td><?= $dadosPosto["nome fantasia"] ?></td>
							<td><?= $dadosResposta[0]["perguntas"][0] ?></td>
						</tr>
						<tr>
							<td><strong>Telefone</strong></td>
							<td><?= $dadosPosto["telefone"] ?></td>
							<td><?= $dadosResposta[0]["perguntas"][1] ?></td>
						</tr>
						<tr>
							<td><strong>Fax</strong></td>
							<td><?= $dadosPosto["fax"] ?></td>
							<td><?= $dadosResposta[0]["perguntas"][2] ?></td>
						</tr>
						<tr>
							<td><strong>Contato (1)</strong></td>
							<td><?= $dadosPosto["contato (1)"] ?></td>
							<td><?= $dadosResposta[0]["perguntas"][3] ?></td>
						</tr>
						<tr>
							<td><strong>Email (1)</strong></td>
							<td><?= $dadosPosto["eMail (1)"] ?></td>
							<td><?= $dadosResposta[0]["perguntas"][7] ?></td>
						</tr>
						<tr>
							<td><strong>Contato (2)</strong></td>
							<td><?= $dadosPosto["contato (2)"] ?></td>
							<td><?= $dadosResposta[0]["perguntas"][8] ?></td>
						</tr>
						<tr>
							<td><strong>Email (2)</strong></td>
							<td><?= $dadosPosto["eMail (2)"] ?></td>
							<td><?= $dadosResposta[0]["perguntas"][9] ?></td>
						</tr>
						</tr>
						<?php
						foreach ($dadosRespostaAdicionais["dadosAnteriores"]["dadosLinhas"] as $idLinha => $valorCampo) { 

							$sqlLinha = "SELECT nome FROM tbl_linha WHERE linha = {$idLinha}";
							$resLinha = pg_query($con, $sqlLinha);

							$nomeLinha = pg_fetch_result($resLinha, 0, "nome");

							$linhaAtual = buscaLinhaResposta($nomeLinha, $dadosResposta, $arrFormulario);

							if (empty($linhaAtual)) {
								continue;
							}

						?>
							<tr>
								<td>
									<strong><?= $nomeLinha ?>:</strong>
								</td>
								<td>
									&nbsp; &nbsp; <?= utf8_decode($valorCampo) ?>
								</td>
								<td>
									<?= $linhaAtual ?>
								</td>
							</tr>
						<?php
						} ?>
					</tbody>
				</table>
			</div>
	    </div>
    <?php
    } ?>
</div>
<br />
<div class="row">
	<form id="form_pesquisa" class="form-horizontal col-xs-12 col-sm-10 col-sm-offset-1 col-lg-8 col-lg-offset-2" action="<?= $_SERVER["PHP_SELF"] ?>" method="POST">

		<input type="hidden" name="btn_acao" value="gravar" />
		<input type="hidden" name="pesquisa" value="<?= $pesquisa ?>" />
		<input type="hidden" name="resposta" value="<?= $resposta ?>" />
		<input type="hidden" name="posto" value="<?= $posto ?>" />
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
							if (!empty(trim($arrGrupo["descricaoGrupo"]))) {

								echo "<p>{$arrGrupo["descricaoGrupo"]}</p><br />";

							}

							foreach ($arrGrupo["perguntas"] as $indexPergunta => $arrPergunta) { 

								$tipoCampo = array_keys($arrPergunta)[0];

								$divObrigatorio = ($arrPergunta["obrigatorio"] == "t") ? "<span class='obrigatorio'>*</span> " : "";

								if (count($dadosResposta) > 0) {
									$textoResposta = $dadosResposta[$indexGrupo]["perguntas"][$indexPergunta];
								}

								switch ($tipoCampo) {
									case 'text':
									case 'number':
									case 'telefone':
									case 'email':

									$inputType = ($tipoCampo == "telefone") ? "text" : $tipoCampo;

									$attrAdicional = "";
									$classAdicional = "";

									if ($tipoCampo == "number") {
										$attrAdicional .= 'min="0"';
									}

									if ($tipoCampo == "telefone") {
										$classAdicional .= "mascara-fone";
										$attrAdicional  .= "placeholder='(00) 00000-0000'";
									}

									if ($tipoCampo == "email") {
										$classAdicional .= "input-email";
										$attrAdicional  .= "placeholder='nome@exemplo.com'";
									}

									?>
										<div class="form-group">
										    <label class="col-sm-5 control-label">
										    	<?= $divObrigatorio.$arrPergunta[$tipoCampo] ?>:
										    </label>
										    <div class="col-sm-8">
										      <input <?= $attrAdicional ?> type="<?= $inputType ?>" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>]" class="form-control input-text <?= $classAdicional ?>" obrigatorio="<?= $arrPergunta["obrigatorio"] ?>" value="<?= $textoResposta ?>" />
										    </div>
										</div>
									<?php
										break;

									case 'textarea': 
									?>
										<div class="form-group">
										    <label class="col-sm-5 control-label">
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
										    <div class="col-sm-7" style="padding-left: 10px;">
										    <?php

										    $atendeLinha = null;
										    if (in_array($login_fabrica, [1]) && $indexGrupo == 1) {

								      			$sqlLinhaPosto = "
								      				SELECT tbl_linha.linha
								      				FROM tbl_linha
								      				JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
								      				AND tbl_posto_linha.ativo
								      				AND tbl_posto_linha.posto = {$login_posto}
								      				WHERE fn_retira_especiais(fn_retira_acentos(UPPER(tbl_linha.nome))) = fn_retira_especiais(fn_retira_acentos(UPPER('{$arrPergunta[$tipoCampo]["descricao"]}')))
								      				AND tbl_linha.fabrica = {$login_fabrica}
								      			";
								      			$resLinhaPosto = pg_query($con, $sqlLinhaPosto);

								      			$atendeLinha = "Não";
								      			if (pg_num_rows($resLinhaPosto) > 0) {

								      				$atendeLinha = "Sim";

								      			}

								      		}

										    $valor_input = 0;
										    foreach ($arrPergunta[$tipoCampo]["options"] as $indexOpcao => $descricao) { 

										    	?>
										      	<span class="lbl-multiplos">
											      	<?php
											      	if ($tipoCampo == "radio") { 

											      		$classOption = "";
											      		$attrOption  = "";

											      		if (!empty($atendeLinha)) {

											      			$checkedOption = "";
											      			$classOption    = "linhas-atende";
											      			$attrOption     = "data-alterou-linha='t'";

											      			if ($atendeLinha == $descricao) {

											      				$checkedOption = "checked";
											      				$classOption    = "linhas-atende";
											      				$attrOption     = "data-alterou-linha='f'";

											      			}

											      		}

											      		if (count($dadosResposta) > 0) {

												    		$valorResposta = $dadosResposta[$indexGrupo]["perguntas"][$indexPergunta][$tipoCampo]["options"];

												    		if ($valorResposta == $indexOpcao) {
												    			$checkedOption = "checked";
												    		} else {
												    			$checkedOption = "";
												    		}

												    	}
											      		?>
											      		<label>
											      			<input <?= $checkedOption ?> <?= $attrOption ?> class="input-radio <?= $classOption ?>" type="radio" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>][radio][options]" value="<?= $indexOpcao ?>" />
											      			<?= $descricao ?>
											      		</label>
											      	<?php
											      	} else { 

											      		if (count($dadosResposta) > 0) {
												    		$valorResposta = $dadosResposta[$indexGrupo]["perguntas"][$indexPergunta][$tipoCampo]["options"][$indexOpcao];

												    		$checkedOption = ($valorResposta == $indexOpcao) ? "checked" : "";
												    	}

											      		?>
											      		<label>
											      			<input <?= $checkedOption ?> class="input-checkbox" type="checkbox" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>][checkbox][options][<?= $indexOpcao ?>]" value="<?= $indexOpcao ?>" />
											      			<?= $descricao ?>
											      		</label>
											      	<?php
											      	}
											      	?>
											    </span>
										    <?php
										    } 

										    if ($arrPergunta["justificar"] == "t") { 

										    	if (count($dadosResposta) > 0) {
										    		$justificativaResposta = $dadosResposta[$indexGrupo]["perguntas"][$indexPergunta]["justificativa"];
										    	}

										    	?>
										    	<span class="lbl-multiplos">
										    		<?php
										    		if (count($dadosResposta) == 0) { ?>
										    			<input type="text" placeholder="Justificativa" name="formulario[<?= $indexGrupo ?>][perguntas][<?= $indexPergunta ?>][justificativa]" value="<?= $justificativaResposta ?>" class="form-control input-text input-justificativa" obrigatorio="f" style="max-width: 225px !important;margin: 0px !important;" />
										    		<?php
										    		} else { ?>
										    			<br />
										    			<strong>Justificativa:</strong> <?= $justificativaResposta ?>
										    		<?php
										    		}
										    		?>
										    		
										    	</span>
										    <?php
										    } ?>
										    </div>
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
		if (count($dadosResposta) == 0) { 
		?>
			<div class="finalizar-fixo">
				<br />
				<div class="row row-fluid">
					<button class="btn btn-default" id="btn_finalizar">
						Finalizar Pesquisa
					</button>
					<?php
					if (!$areaAdmin && in_array($login_fabrica, [1])) { 

						$sqlParametros = "SELECT parametros_adicionais FROM tbl_posto_fabrica
										  WHERE posto = {$login_posto}
										  AND fabrica = {$login_fabrica}
										  AND JSON_FIELD('responderAtualizacaoDepois', parametros_adicionais) IS NOT NULL";
						$resParametros = pg_query($con, $sqlParametros);

						if (pg_num_rows($resParametros) == 0) {
						?>
							<button type="button" class="btn btn-default btn-danger responder-depois">
								Responder Depois
							</button>
					<?php
						}
					} ?>
				</div>
			</div>
		<?php
		}
		?>
	</form>
</div>
<script>

	$(".finalizar-fixo").css({
		width: $(".area-titulo").width() + 20
	});

	<?php
	if (count($dadosResposta) > 0) { ?>
		$("input, textarea").prop("disabled", true);
	<?php
	}
	?>

	$(".mascara-fone").mask("(99)9999-9999?9");

	$("#btn_finalizar").click(function(e){

		e.preventDefault();

		var erro = false;

		$(".mascara-fone").filter(function(){
			return $(this).val() != "";
		}).each(function(){

			let foneValido = validatePhone($(this).val());

			if (!foneValido) {

				$(this).closest(".form-group").addClass("has-error");
				$(".alert-danger").show().find("h5").text("O telefone/celular digitado é inválido");
				erro = true;

			} else {

				$(this).closest(".form-group").removeClass("has-error");

			}

		});

		$(".input-email").filter(function(){
			return $(this).val() != "";
		}).each(function(){

			let emailValido = validateEmail($(this).val());

			if (!emailValido) {

				$(this).closest(".form-group").addClass("has-error");
				$(".alert-danger").show().find("h5").text("O E-mail digitado é inválido");
				erro = true;

			} else {

				$(this).closest(".form-group").removeClass("has-error");

			}

		});

		$(".input-text[obrigatorio=t]").each(function(){
			
			if ($(this).val() == "" && $(this).text() == "") {

				$(this).closest(".form-group").addClass("has-error");
				$(".alert-danger").show().find("h5").text("Preencha os campos obrigatórios");
				erro = true;

			} else {

				if (!$(this).hasClass(".input-email") && !$(this).hasClass(".mascara-fone")) {

					$(this).closest(".form-group").removeClass("has-error");

				}

			}
			
		});

		$(".multiplos[obrigatorio=t]").each(function(){

			if ($(this).find("input[type!=text]:checked").length == 0) {

				$(this).addClass("has-error");
				$(".alert-danger").show().find("h5").text("Preencha os campos obrigatórios");
				erro = true;

			} else {

				if ($(this).find(".input-justificativa").length == 0) {

					$(this).removeClass("has-error");

				}

			}
			
		});

		if (erro) {
			$("html, body").animate({ scrollTop: 0 }, 600);
		} else {
			$("#form_pesquisa").submit();
		}

	});

	$(".responder-depois").click(function(){
			
		let that = $(this);

		$.ajax({
			url: "ajax/responder_depois.php",
			type: "POST",
			dataType: "JSON",
			data: {
				ajaxResponderDepois: true
			},
			beforeSend: function () {
				$(that).prop("disabled", true).text("Aguarde...");
			},
			success: function (data) {

				if (data.success) {

					window.location = "menu_inicial.php";

				} else {

					alert("Erro ao redirecionar");

				}

			}
		});

	});

	$(".linhas-atende").change(function(){

		let alterou 		   = $(this).data("alterou-linha");
		let inputJustificativa = $(this).closest(".multiplos").find(".input-justificativa");

		if ( alterou == "t" ) {

			$( inputJustificativa ).attr("obrigatorio", "t");
			$(this).closest(".multiplos").addClass("has-error");

		} else {

			$( inputJustificativa ).attr("obrigatorio", "f");
			$(this).closest(".multiplos").removeClass("has-error");

		}

	});

	function validatePhone(telefone){
	    //retira todos os caracteres menos os numeros
	    telefone = telefone.replace(/\D/g,'');
	    
	    //verifica se tem a qtde de numero correto
	    if(!(telefone.length >= 10 && telefone.length <= 11)) return false;
	    
	    //Se tiver 11 caracteres, verificar se começa com 9 o celular
	    if (telefone.length == 11 && parseInt(telefone.substring(2, 3)) != 9) return false;
	      
	    //verifica se não é nenhum numero digitado errado (propositalmente)
	    for(var n = 0; n < 10; n++){
	    	//um for de 0 a 9.
	      //estou utilizando o metodo Array(q+1).join(n) onde "q" é a quantidade e n é o 	  
	      //caractere a ser repetido
	    	if(telefone == new Array(11).join(n) || telefone == new Array(12).join(n)) return false;
	      }
	      //DDDs validos
	      var codigosDDD = [11, 12, 13, 14, 15, 16, 17, 18, 19,
	    21, 22, 24, 27, 28, 31, 32, 33, 34,
	    35, 37, 38, 41, 42, 43, 44, 45, 46,
	    47, 48, 49, 51, 53, 54, 55, 61, 62,
	    64, 63, 65, 66, 67, 68, 69, 71, 73,
	    74, 75, 77, 79, 81, 82, 83, 84, 85,
	    86, 87, 88, 89, 91, 92, 93, 94, 95,
	    96, 97, 98, 99];
	      //verifica se o DDD é valido (sim, da pra verificar rsrsrs)
	      if(codigosDDD.indexOf(parseInt(telefone.substring(0, 2))) == -1) return false;
	      
				//  E por ultimo verificar se o numero é realmente válido. Até 2016 um celular pode 
	      //ter 8 caracteres, após isso somente numeros de telefone e radios (ex. Nextel)
	      //vão poder ter numeros de 8 digitos (fora o DDD), então esta função ficará inativa
	      //até o fim de 2016, e se a ANATEL realmente cumprir o combinado, os numeros serão
	      //validados corretamente após esse período.
	      //NÃO ADICIONEI A VALIDAÇÂO DE QUAIS ESTADOS TEM NONO DIGITO, PQ DEPOIS DE 2016 ISSO NÃO FARÁ DIFERENÇA
	      //Não se preocupe, o código irá ativar e desativar esta opção automaticamente.
	      //Caso queira, em 2017, é só tirar o if.
	      //if(new Date().getFullYear() < 2017) return true;
	      if (telefone.length == 10 && [2, 3, 4, 5, 7].indexOf(parseInt(telefone.substring(2, 3))) == -1) return false;

				//se passar por todas as validações acima, então está tudo certo
	      return true;
	  
	}

	function validateEmail(email) {
	    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	    return re.test(String(email).toLowerCase());
	}

</script>
</body>
</html>
