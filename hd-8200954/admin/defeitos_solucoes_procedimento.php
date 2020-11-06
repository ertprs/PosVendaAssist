<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	if($_GET['area_posto'] == 'sim') {
		include_once '../autentica_usuario.php';
		include_once S3CLASS;
		$s3 = new AmazonTC("procedimento", $login_fabrica);
	    $ano = '2016';
	    $mes = '04';

	}else{
		$admin_privilegios="call_center";
		include 'autentica_admin.php';
		include_once S3CLASS;
		$s3 = new AmazonTC("procedimento", $login_fabrica);
	    $ano = '2016';
	    $mes = '04';
	}
	include_once 'funcoes.php';
	include_once '../helpdesk/mlg_funciones.php';

    /**
	* Cria a chave do anexo
	*/

	if (!strlen(getValue("anexo_chave"))) {
	    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
	} else {
	    $anexo_chave = getValue("anexo_chave");
	}

	/**
	* Inclui o arquivo no s3
	*/
	if (isset($_POST["ajax_anexo_upload"])) {
	    
	    $chave   = $_POST["anexo_chave"];
	    $arquivo = $_FILES["anexo_upload"];

	    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

	    if ($ext == "jpeg") {
	        $ext = "jpg";
	    }

	    if (strlen($arquivo["tmp_name"]) > 0) {
	        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
	            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
	        } else {
	            $arquivo_nome = "{$chave}";

	            $s3->tempUpload("{$arquivo_nome}", $arquivo);

	            if($ext == "pdf"){
	            	$link = "imagens/pdf_icone.png";
	            } else if(in_array($ext, array("doc", "docx"))) {
	            	$link = "imagens/docx_icone.png";
	            } else {
		            $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
		        }

	            if (!strlen($link)) {
	                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
	            } else {
	            	$href = $s3->getLink("{$arquivo_nome}.{$ext}", true);
	                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}", "href" => $href, "ext" => $ext);
	            }
	        }
	    } else {
	        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
	    }

	    //$retorno["posicao"] = $posicao;

	    exit(json_encode($retorno));
	}

	/**
	* Excluir anexo
	*/
	if (isset($_POST["ajax_anexo_exclui"])) {

		$anexo_nome_excluir = $_POST['anexo_nome_excluir'];		

		if (count($anexo_nome_excluir) > 0) {
			$s3->deleteObject($anexo_nome_excluir, false, $ano, $mes);
			$retorno = array("ok" => utf8_encode("Excluído com sucesso!"));
		}else{
			$retorno = array("error" => utf8_encode("Erro ao excluir arquivo"));
		}

		exit(json_encode($retorno));

	}//Fim excluir anexo

	if(isset($_POST["inserir_procedimento"])){

		$defeito = trim($_POST["defeito"]);
		$solucao = trim($_POST["solucao"]);
		$produto = trim($_POST["produto"]);

		$procedimento = trim(utf8_decode($_POST["procedimento"]));
		$anexo_procedimento = $_POST["anexo_procedimento"];

		$sql_alt = "UPDATE tbl_defeito_constatado_solucao 
					SET solucao_procedimento = '{$procedimento}' 
					WHERE 
						defeito_constatado = {$defeito} 
						AND solucao = {$solucao} 
						AND produto = {$produto} 
						AND fabrica = {$login_fabrica}";
		$res_alt = pg_query($con, $sql_alt);

		//Anexo
		if ( strlen( pg_last_error() ) == 0 ) {
			if (count($anexo_procedimento)>0) {
				$sql_inp = "SELECT defeito_constatado_solucao
								FROM tbl_defeito_constatado_solucao 
								WHERE defeito_constatado = {$defeito}
									AND solucao = {$solucao} 
									AND produto = {$produto} 
									AND fabrica = {$login_fabrica};";
				$res_inp = pg_query($con,$sql_inp);

				if (pg_num_rows($res_inp)> 0) {
					$def_cs = pg_fetch_result($res_inp, 0, defeito_constatado_solucao);

					$arquivos = array();

					if (strlen($anexo_procedimento) > 0) {
						$ext = preg_replace("/.+\./", "", $anexo_procedimento);
						$arquivos[] = array(
							"file_temp" => $anexo_procedimento,
							"file_new"  => "{$login_fabrica}_{$def_cs}.{$ext}"
						);
					}
				}		
				if (count($arquivos) > 0) {
					$s3->moveTempToBucket($arquivos, $ano, $mes, false);
				}
			}
		}
		//Fim Anexo

		$status = (pg_affected_rows($res_alt) > 0) ? true : false;

		echo json_encode(array("status" => $status, "defeito" => $defeito, "solucao" => $solucao, "produto" => $produto));

		exit;

	}

	if((isset($_GET["defeito"]) && isset($_GET["solucao"]) && isset($_GET["produto"])) || isset($_GET["defeito_solucao_id"]) ){

		$defeito = trim($_GET["defeito"]);
		$solucao = trim($_GET["solucao"]);
		$produto = trim($_GET["produto"]);
		$box 	 = trim($_GET["box"]);

		if(strlen($_GET["defeito_solucao_id"]) > 0){

			$defeito_constatado_solucao = $_GET["defeito_solucao_id"];

			$cond_where = "tbl_defeito_constatado_solucao.defeito_constatado_solucao = {$defeito_constatado_solucao} 
							AND tbl_defeito_constatado_solucao.fabrica = {$login_fabrica}";

		}else{

			$cond_where = "tbl_defeito_constatado_solucao.fabrica = {$login_fabrica} 
						AND tbl_defeito_constatado_solucao.defeito_constatado = {$defeito} 
						AND tbl_defeito_constatado_solucao.solucao = {$solucao} 
						AND tbl_defeito_constatado_solucao.produto = {$produto}";

		}

		$sql_info = "SELECT 
						tbl_defeito_constatado_solucao.defeito_constatado_solucao,
						tbl_defeito_constatado.descricao AS desc_defeito,
						tbl_solucao.descricao AS desc_solucao,
						tbl_produto.referencia AS ref_produto,
						tbl_produto.descricao AS desc_produto,
						tbl_defeito_constatado_solucao.solucao_procedimento AS procedimento,
						tbl_defeito_constatado_solucao.defeito_constatado_solucao As dc_solucao
					FROM tbl_defeito_constatado_solucao
					JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_defeito_constatado_solucao.defeito_constatado 
					JOIN tbl_solucao ON tbl_solucao.solucao = tbl_defeito_constatado_solucao.solucao 
					LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_defeito_constatado_solucao.produto 
					WHERE $cond_where";
		$res_info = pg_query($con, $sql_info);

		//thiago
		//echo nl2br($sql_info);

		if(pg_num_rows($res_info) > 0){

			$desc_defeito 	= pg_fetch_result($res_info, 0, "desc_defeito");
			$desc_solucao 	= pg_fetch_result($res_info, 0, "desc_solucao");
			$ref_produto 	= pg_fetch_result($res_info, 0, "ref_produto");
			$desc_produto 	= pg_fetch_result($res_info, 0, "desc_produto");
			$procedimento 	= pg_fetch_result($res_info, 0, "procedimento");
			$dc_solucao      = pg_fetch_result($res_info, 0, "dc_solucao");
			$defeito_constatado_solucao = pg_fetch_result($res_info, 0, "defeito_constatado_solucao");
			?>

			<!DOCTYPE html>
			<html>
				<head>
					<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

					<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
					<script src="bootstrap/js/bootstrap.js"></script>
					<script src="plugins/dataTable.js"></script>
					<script src="plugins/resize.js"></script>
					
					<?php $plugins = array("ajaxform");
					include 'plugin_loader.php';?>

					<script>
						$(function() {

							/**
						    * Eventos para anexar/excluir imagem
						    */
						    $("button.btn_acao_anexo").click(function(){
								var name = $(this).attr("name");
								
								if (name == "anexar") {
									$(this).trigger("anexar_s3", [$(this)]);
								}else{
									$(this).trigger("excluir_s3", [$(this)]);
								}
							});

						    //ativa o anexar
						    $("button.btn_acao_anexo").bind("anexar_s3",function(){
						    	var button = $(this);
								$("input[name=anexo_upload]").click();
						    });
							
							//ativa o excluir
						    $("button.btn_acao_anexo").bind("excluir_s3",function(){

								var button = $(this);
								var nome_an_p = $("input[name='anexo']").val();
								// alert(nome_an_p);
								// return;
								$.ajax({			
									url: "defeitos_solucoes_procedimento.php",
									type: "POST",
									data: { ajax_anexo_exclui: true, 
											anexo_nome_excluir: nome_an_p
									},
									beforeSend: function() {
										$("#div_anexo").find("button").hide();
										$("#div_anexo").find("img.anexo_thumb").hide();
										$("#div_anexo").find("img.anexo_loading").show();
									},
									complete: function(data) {
										data = $.parseJSON(data.responseText);

										if (data.error) {
											alert(data.error);
										} else {
											$("#div_anexo").find("a[target='_blank']").remove();
											$("#baixar").remove();
											$(button).text("Anexar").attr({
												id:"anexar",
												class:"btn btn-mini btn-primary btn-block",
												name: "anexar"
											});
											$("input[name='anexo']").val("f");				
											$("#div_anexo").prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

											$("#div_anexo").find("img.anexo_loading").hide();
											$("#div_anexo").find("button").show();
											$("#div_anexo").find("img.anexo_thumb").show();
									  		alert(data.ok);
										}
									}
								});
						    });

							/**
						    * Eventos para anexar imagem
						    */
						    $("form[name=form_anexo]").ajaxForm({
						        complete: function(data) {
									data = $.parseJSON(data.responseText);
									console.log(data);
									if (data.error) {
										alert(data.error);
									} else {
										var imagem = $("#div_anexo").find("img.anexo_thumb").clone();
										$(imagem).attr({ src: data.link });

										$("#div_anexo").find("img.anexo_thumb").remove();

										var link = $("<a></a>", {
											href: data.href,
											target: "_blank"
										});

										$(link).html(imagem);

										$("#div_anexo").prepend(link);										

								        $("#div_anexo").find("input[rel=anexo]").val(data.arquivo_nome);
									}

									$("#div_anexo").find("img.anexo_loading").hide();
									$("#div_anexo").find("button").show();
									$("#div_anexo").find("img.anexo_thumb").show();
						    	}
						    });

							$("input[name^=anexo_upload]").change(function() {
								//var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

								$("#div_anexo").find("button").hide();
								$("#div_anexo").find("img.anexo_thumb").hide();
								$("#div_anexo").find("img.anexo_loading").show();

								$(this).parent("form").submit();
						    });
						});


						function grava_procedimento(){

							$(".response").html("");

							var defeito = $('#defeito').val();
							var solucao = $('#solucao').val();
							var produto = $('#produto').val();
							var procedimento = $('#procedimento').val();
							
							var array_procedimento = new Array();
							var anexo_procedimento = $("input.classe_anexo").val();

							var alert = "";
							var situacao = <?php echo (strlen($procedimento) == 0) ? "'inserida'" : "'alterada'"; ?>;

							if(procedimento == ""){
								alert = "<div class='alert alert-danger tac'><h4>Por favor, insira a Descrição para a Solução</h4></div>";
								$(".response").html(alert);
								$('#procedimento').focus();
								return;
							}

							$.ajax({
								url	: "<?php echo $PHP_SELF; ?>",
								type : "POST",
								data : {
									defeito : defeito,
									solucao : solucao,
									produto : produto,
									procedimento : procedimento,
									anexo_procedimento : anexo_procedimento,
									inserir_procedimento : true
								},
								beforeSend: function(){
									$(".response").html("<em>inserindo... por favor aguarde!</em>");
								},
								complete: function(data){

									data = $.parseJSON(data.responseText);

									if(data.status == true){
										//$('#procedimento').val("");
										alert = "<div class='alert alert-success tac'><h4>Descrição "+situacao+" com sucesso</h4></div>";
										window.parent.insere_procedimento(data.defeito, data.solucao, data.produto);
										// console.log(data);
									}else{
										alert = "<div class='alert alert-danger tac'><h4>Erro ao "+situacao+" a Descrição</h4></div>";
									}

									$(".response").html(alert);

									setTimeout(function(){
										$(".response").html("");
									}, 10000);
								}
							});

						}


					</script>

				</head>
				<body>

					<?php $height = (strlen($box) > 0 && $box == 1) ? "530px" : "530px"; ?>

					<div class="container" style="margin: 0 auto; overflow: auto; height: <?php echo $height; ?>; width: 95%;">
						<div style="width: 99%; margin-top: 20px;" class="response"></div>

						<h3 class="tac"><?php echo (strlen($box) > 0 && $box == 1) ? "" : "Cadastro de"; ?> Descrição da Solução</h3>
						<strong>Defeito:</strong> <?php echo $desc_defeito; ?> <br />
						<strong>Solução:</strong> <?php echo $desc_solucao; ?> <br />
						<strong>Produto:</strong> <?php echo $ref_produto." - ".$desc_produto; ?>

						<br />

						<hr />

						<input type="hidden" name="defeito" id="defeito" value="<?php echo $defeito; ?>" />
						<input type="hidden" name="solucao" id="solucao" value="<?php echo $solucao; ?>" />
						<input type="hidden" name="produto" id="produto" value="<?php echo $produto; ?>" />

						<div class="row">
							<div class="span8"><strong>Descrição <?php echo (strlen($box) > 0 && $box == 1) ? "" : "para a Solução"; ?></strong></div>
						</div>

						<?php
						if(strlen($box) > 0 && $box == 1){
							?>
							<div class='row-fluid'>
								<div class="span10">
									<div class="control-group">
										<label class='control-label'>
											<?php 
											if (strlen($procedimento) == 0) {
												echo "Nenhum procedimento cadastrado para esse defeito/solução.";
											}else{?>
												<!-- <textarea name="procedimento" id="procedimento" rows="4" class="span10" readonly><?php  ?></textarea> -->
												<p><pre style="border: 0; background-color: transparent;font-family: Arial;"><?php echo $procedimento; ?></pre></p>
											<?php
											}?>
										</label>
   									</div>
   								</div>
   								<?php
   								if (!empty($dc_solucao)) {
   									$anexos = $s3->getObjectList("{$login_fabrica}_{$dc_solucao}", false, '2016', '04');

									if (count($anexos) > 0) {
										$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
										if ($ext == "pdf") {
											$anexo_imagem = "imagens/pdf_icone.png";
										} else if (in_array($ext, array("doc", "docx"))) {
											$anexo_imagem = "imagens/docx_icone.png";
										} else {
											$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, '2016', '04');
										}						
						
					 					$anexo_link = $s3->getLink(basename($anexos[0]), false, '2016', '04');
					 					$anexo = basename($anexos[0]);
										?>
										<div class="span2">
											<div class="control-group">
												<label class='control-label'>
													<a href="<?=$anexo_link?>" target="_blank" >
														<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
													</a>
												</label>
											</div>
										</div>
									<?php						
									}
								}?>
							</div>
							<br />
							<?php
						}else{
							?>
							<div class="row">
								<div class="span8">
									<textarea name="procedimento" id="procedimento" rows="4" class="span10"><?php echo nl2br($procedimento); ?></textarea>
								</div>
							</div>

							<!-- ANexo -->
							<div id="div_anexos" class="row">
								<div class="span8" >
									<input type='hidden' name='anexo_chave' value='<?=$anexo_chave?>' />
								<?php
									unset($anexo_link);

									$anexo_imagem = "imagens/imagem_upload.png";
									$anexo_s3     = false;
									$anexo        = "";

									if(strlen($defeito_constatado_solucao) > 0) {
										$anexos = $s3->getObjectList("{$login_fabrica}_{$defeito_constatado_solucao}", false, $ano, $mes);
										   
										if (count($anexos) > 0) {
											$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
											if ($ext == "pdf") {
												$anexo_imagem = "imagens/pdf_icone.png";
											} else if (in_array($ext, array("doc", "docx"))) {
												$anexo_imagem = "imagens/docx_icone.png";
											} else {
												$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
											}
										
									 		$anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

									 		$anexo        = basename($anexos[0]);
									 		$anexo_s3     = true;
									    }
									}
									?>
									<div id="div_anexo" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
										<?php 
										if (isset($anexo_link)) { ?>
											<a href="<?=$anexo_link?>" target="_blank" >
										<?php 
										} ?>

										<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

										<?php
										if (isset($anexo_link)) { ?>
											</a>												
										<?php } ?>

										<?php
										if ($anexo_s3 === false) {
										?>
										    <button id="anexar" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" >Anexar</button>
										<?php
										}
										?>

										<img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

										<input type="hidden" rel="anexo" class="classe_anexo" name="anexo" value="<?=$anexo?>" />
										<input type="hidden" name="anexo_s3" value="<?=($anexo_s3) ? 't' : 'f'?>" />
										<?php
										if ($anexo_s3 === true) {?>
											<button id="excluir" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" >Excluir</button>

											<button id="baixar" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>
							           	<?php	
							           	}
							           	?>						
									</div>
								</div>
							</div>
							<!-- Fim anexo-->

							<p class="tac">
								<button type="button" class="btn btn-primary" onclick="grava_procedimento();"><?php echo (strlen($procedimento) == 0) ? "Cadastrar" : "Alterar"; ?></button>
							</p>							
							<?php
						}
						?>

						<?php

						if(isset($_GET["defeito_solucao_id"])){?>
							<div class="breadcrumb tac">
								<strong>Essa Solução foi útil?</strong> &nbsp; &nbsp;
								<button class="btn btn-primary" onclick="window.parent.enviar_form('sim')">Sim</button> 
								<button class="btn btn-danger" onclick="window.parent.enviar_form('nao')">Não</button> 
							</div>
						<?php
						}
						?>
					</div>
				</body>				
				<!-- Inicio anexo -->
				<form name="form_anexo" method="post" action="defeitos_solucoes_procedimento.php" enctype="multipart/form-data" style="display: none;" >
					<input type="file" name="anexo_upload" value="" />

					<input type="hidden" name="ajax_anexo_upload" value="t" />
					<!-- <input type="hidden" name="anexo_posicao" value="<?=$i?>" /> -->
					<input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
				</form>
			</html>
		<?php
		}
	}
?>
