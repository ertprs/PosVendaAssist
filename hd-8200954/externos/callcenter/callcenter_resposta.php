<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';

include_once '../../class/aws/s3_config.php';
include_once S3CLASS;

$login_fabrica = 35;
$consumidor = 6822;
$s3 = new AmazonTC('callcenter', (int) $login_fabrica);

$ate_cpf = $_GET['x'];
if ($_POST["deleta_imagens"] == "true") {
	$files = $_POST["files"];
	$callcenter = $_POST['atendimento'];
	$sql = "SELECT  tbl_hd_chamado.status
	        FROM    tbl_hd_chamado
	        WHERE   tbl_hd_chamado.hd_chamado = $callcenter
	";
	$res = pg_query($con,$sql);
	$status_item = pg_fetch_result($res,0,0);
	// $consumidor = 6822;
	// teste 
	//$consumidor = 6602 ;
	if (count($files) > 0) {
		foreach ($files as $key => $file) {
			$s3->deleteObject($file);

			if ($s3->result == false) {
				echo json_encode(array("erro" => "Erro ao deletar arquivo"));
				break;
			}
			$sql = "INSERT INTO tbl_hd_chamado_item (
				hd_chamado  ,
				comentario  ,
				admin       ,
				interno     ,
				status_item
				) VALUES (
				$callcenter                                                             ,
				'Imagem deletada: $file pelo(a) usuário(a) <b> Consumidor </b>' 		,
				$consumidor                                                            		,
				't'                                                                     ,
				'Aberto'
				) ";
			
			$res = pg_query($con,$sql);
			$msg_db = pg_last_error($con);
			if(strlen($msg_db) > 0){
				echo json_encode(array("erro" => "Erro ao deletar arquivo"));
				break;
			}

		}

		$msg_db = pg_last_error($con);
		if(strlen($msg_db) > 0){
			echo json_encode(array("erro" => "Erro ao fazer o upload do arquivo"));    
		}else{
			echo json_encode(array("success" => "true"));    
		}        
	} else {
		echo json_encode(array("erro" => "Nenhum arquivo selecionado para deletar"));
	}

exit;
}

if ($_POST["anexar_imagem"] == "true") {
	$types      = array("png", "jpg", "jpeg", "bmp", "pdf","doc","txt");
	$file       = $_FILES[key($_FILES)];
	$hd_chamado = $_POST["file_hd_chamado"];
	$i          = $_POST["file_i"];
	$type  = trim(strtolower(preg_replace("/.+\./", "", $file["name"])));
	$sql = "SELECT  tbl_hd_chamado.status
	        FROM    tbl_hd_chamado
	        WHERE   tbl_hd_chamado.hd_chamado = $hd_chamado
	";
	$res = pg_query($con,$sql);
	$status_item = pg_fetch_result($res,0,0);
	
	if (count($_FILES) > 0) {
		if ($file["size"] <= 2097152) {
			if ($type == "jpeg") {
				$type = "jpg";
			}

			if (strlen($file["tmp_name"]) > 0 && $file["size"] > 0) {
				if (!in_array($type, $types)) {
					echo json_encode(array("erro" => utf8_encode("Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf")));
					exit;
				} else {
					$s3->upload("{$hd_chamado}-{$i}", $file, null, null);

					if ($type != "pdf") {
						$file_mini = $s3->getLink("thumb_{$hd_chamado}-{$i}.{$type}", false, null, null);
					}

					$file      = $s3->getLink("{$hd_chamado}-{$i}.{$type}", false, null, null);
				}
			} else {
				echo json_encode(array("erro" => "Erro ao fazer o upload do arquivo"));
				exit;
			}

			if ($s3->result == false) {
				echo json_encode(array("erro" => "Erro ao fazer o upload do arquivo"));
			} else {
				$sql = "INSERT INTO tbl_hd_chamado_item (
					hd_chamado  ,
					comentario  ,
					admin       ,
					interno     ,
					status_item
					) VALUES (
					$callcenter                                                                         ,
					'Arquivo anexado: {$hd_chamado}-{$i} pelo(a) usuário(a) <b> Consumidor </b>'  		,
					$consumidor                                                                        		,
					't'                                                                                 ,
					'Aberto'
					) ";
				$res = pg_query($con,$sql);
	

				$msg_db = pg_last_error($con);
				if(strlen($msg_db) > 0){
					echo json_encode(array("erro" => "Erro ao fazer o upload do arquivo"));
				}else{
					echo json_encode(array("file_mini" => $file_mini, "file" => $file, "i" => $i, "type" => $type, "file_name" => "{$hd_chamado}-{$i}.{$type}"));
				}
			}
		} else {
			echo json_encode(array("erro" => utf8_encode("O arquivo deve ter no máximo 2mb")));
		}
	} else {
		echo json_encode(array("erro" => "Nenhum arquivo selecionado"));
	}

exit;
}



if ($_POST['ajax']=='true'){
	$v_atendimento = $_POST['v_antendimento'];
	$v_cpf = $_POST['v_cpf'];
	$verificacao = md5($v_atendimento.$v_cpf);
	if ($ate_cpf === $verificacao){
		echo "t";
	}else{
		echo "f";
	}
	exit;
}

if (strlen($_POST["btn_acao"]) > 0 ){
	//	var_dump($_GET);exit;
	if (strlen($_POST['mensagem'])> 0){
		$mensagem = $_POST['mensagem'];
		$callcenter = $_POST['atendimento'];

			$sql = "INSERT INTO tbl_hd_chamado_item(
				hd_chamado   	,
				data         	,
				comentario   	,
				admin       	,
				status_item
				) values (
				$callcenter       ,
				current_timestamp ,
				E'$mensagem'	  ,
				$consumidor       		  ,
				'Aberto'  
				)";
			$res = pg_query($con,$sql);
			$sql = "UPDATE tbl_hd_chamado set status = 'Aberto' where hd_chamado = $callcenter";
			$res = pg_query($con,$sql);
			if (strlen(pg_last_error($con))>0){
				$msgerro = pg_last_error($con);
			}
	}
}


?>


<!DOCTYPE>
<html>
<head>

	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="language" content="pt-br" />

	<script type="text/javascript" src="../../js/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="../../admin/js/jquery.mask.js"></script>

	<style>

		body{
			font-family: Trebuchet MS, "Trebuchet MS", Arial, Helvetica, sans-serif;
			font-size: 12px;
			color: #FFA403;
		}

		input{
			padding: 7px 2px;
			border: 1px solid #ccc;
			background-color: #fff;
		}

		input[type="submit"]{
			color: #fff;
			border-radius: 4px;
			background-color: #FFA403;
			padding-right: 15px;
			padding-left: 15px;
			font-weight: bold;
		}

		input[type="submit"]:hover{
			cursor: pointer;
			background-color: #E59302;
		}

		select{
			padding: 7px 2px;
			border: 1px solid #ccc;
			background-color: #fff;
		}

		textarea{
			padding: 7px 2px;
			border: 1px solid #ccc;
			background-color: #fff;
			font: 12px arial;
		}

		.tab{
			width: 790px;
			padding: 25px;
			background-color: #fff; 
		}

		.left{
			width: 48%;
			float: left;
			margin-bottom: 10px;
		}

		.right{
			width: 48%;
			float: right;
			margin-bottom: 10px;
		}

		.min-box{
			float: left;
			margin-bottom: 10px;
		}

		.box_envio{
			width: 99%;
			padding-top: 12px;
			padding-bottom: 12px;
			text-align: center;
			background-color: #CEF6CE;
			font-weight: bold;
			color: #0B610B;
			font-size: 13px;
		}

		.box_erro_produto{
			width: 98%;
			padding-top: 12px;
			padding-bottom: 12px;
			text-align: center;
			background-color: #F8E0E6;
			font-weight: bold;
			color: #ff0000;
			margin-bottom: 20px;
		}
		.sucesso{
					padding: 5px;
					margin: 10px 0;
					border: 1px solid #339900;
					background: #99CC99;
					color: #FCFCFC;
		}


	</style>
	<script src="../../js/jquery.form.js"></script>
	<script type="text/javascript">

		$(function(){

			$("#btn_acao").hide();
			$("#min-box").hide();
			$("#erro").hide();
			$(".td_img_anexadas").hide();
			$("#file_form").hide();
			$("#tamanhomax").hide();
			$("#atendimento").attr("readonly", false);
			$("#cpf").attr("readonly", false);

			$("#cpf").mask("999.999.999-99");

			$("#verifica_atendimento").click(function(){
				var cpf = $("#cpf").val();
				var atendimento = $("#atendimento").val();
				$("#erro").hide();

				$.ajax({
					url 		: "<?php echo $_SERVER['PHP_SELF']; echo '?x='.$ate_cpf ?>",
					type 		: "POST",
					data 		: { ajax:true , v_cpf:cpf ,	v_antendimento:atendimento },
					complete 	: function(md5){
						results = md5.responseText;
						if (results == "t"){
							$("#verifica_atendimento").hide();
							$("#atendimento").attr("readonly", true);
							$("#cpf").attr("readonly", true);
							$("#min-box").show();
							$("#file_form").find("input[name=file_hd_chamado]").val(atendimento);
							$("#file_form").find("input[name=callcenter]").val(atendimento);
							$("#btn_acao").show();
							$(".td_img_anexadas").show();
							$("#file_form").show();
							$("#tamanhomax").show();

							var callcenter = $("#frm_callcenter").find("input[name=atendimento]").val();
							
							$("#file_form").ajaxForm({
								data:{callcenter:callcenter},
								complete: function(data) {
									data = $.parseJSON(data.responseText);

									if (data.erro != undefined) {
										alert(data.erro);
									} else {
										var file_div = $(".img_anexada_model").clone();

										$(file_div).find("a").attr({ "href": data.file, "target": "_blank" });
										if (data.type != "pdf") {
											$(file_div).find("img").attr({ "src": data.file_mini });
										} else {
											$(file_div).find("img").attr({ "src": "../../admin/imagens/icone_pdf.jpg" });
										}
										$(file_div).find("input[name=img_anexada_nome]").val(data.file_name);
										$(file_div).find("input[name=img_i]").val(data.i);
										$(file_div).addClass("img_anexada").removeClass("img_anexada_model").css({ "display": "inline-block" }).attr({ "rel": data.i });

										$(".td_img_anexadas").append(file_div);

										$("#file_form").find("input[name=file]").val("");

										if (!$("#deleta_img_checked").is(":visible")) {
											$("#deleta_img_checked").show();
										}
									}

									anexando = false;

									$("#anexando").hide();
									$("#anexarImagens").show();
								}
							});

						}else{
							$("#erro").show();
							$("#atendimento").attr("disabled", false);
							$("#cpf").attr("disabled", false);
						}
					}
				});

			});

			
			var login_fabrica = "<?=$login_fabrica?>";

			var anexando = false;

			var img_contador = {
				1: "div.img_anexada[rel=1]",
				2: "div.img_anexada[rel=2]",
				3: "div.img_anexada[rel=3]",
				4: "div.img_anexada[rel=4]",
				5: "div.img_anexada[rel=5]",
				6: "div.img_anexada[rel=6]",
				7: "div.img_anexada[rel=7]",
				8: "div.img_anexada[rel=8]",
				9: "div.img_anexada[rel=9]",
				10: "div.img_anexada[rel=10]"
			};

			$("#anexarImagens").click(function () {
				if ($(".img_anexada").length == 3) {
					alert("O máximo de anexos por atendimento é 3 arquivos");

					return false;
				}

				if (anexando === false) {
					$.each(img_contador, function (key, div) {
						if ($(div).length == 0) {
							$("#file_form").find("input[name=file_i]").val(key);
							return false;
						}
					});

					anexando = true;

					$("#anexando").show();
					$("#anexarImagens").hide();
					$("#file_form").submit();
				} else {
					alert("Espere o upload atual finalizar!");
				}
			});

			


		$("#deleta_img_checked").click(function () {
			if ($("input[name=img_anexada_nome]:checked").length > 0) {
				if (anexando === false) {
					var files = [];

					$("input[name=img_anexada_nome]:checked").each(function () {
						files.push($(this).val());
					});

					anexando = true;

					$("#deletando").show();
					$("#deleta_img_checked").hide();
					var callcenter = $("#atendimento").val();
					console.log(callcenter);
					$.ajax({
						url: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type: "POST",
						data: { files: files, deleta_imagens: true, callcenter:callcenter },
						complete: function (data) {

							data = $.parseJSON(data.responseText);

							if (data.erro != undefined) {
								alert(data.erro);
							} else {
								$.each(files, function (key, value) {
									$("input[name=img_anexada_nome][value='"+value+"']").parents("div.img_anexada").remove();
								});
							}

							anexando = false;

							$("#deletando").hide();

							if ($(".img_anexada").length > 0) {
								$("#deleta_img_checked").show();
							}
						}
					});
				} else {
					alert("Espere o processo atual finalizar!");
				}
			}
		});
	});
</script>

</head>
<body>
	<form name='frm_callcenter' id='frm_callcenter' method='POST' action='<?$PHP_SELF?>'>
		<div class='tab'>
			<strong><font size=5 >Central de Atendimento</font></strong><br/>
				<font size=2>Preencha o formul&aacute;rio abaixo e aguarde o nosso retorno</font>
		</div>
		<div class="tab">
			<div class="left">
				<strong>PROTOCOLO</strong> <br />
				<input type="text" name="atendimento" id="atendimento" style='width: 98%;' />
			</div>

			<div class="right">
				<strong>CPF</strong> <br />
				<input type="text" name="cpf" id="cpf" style='width: 98%;' />
			</div>

			<div class="min-box" id="min-box" style="width: 100%">

				<strong>*MENSAGEM</strong> <br />
				<textarea name="mensagem" id="mensagem" rows="6" style="width: 99%"></textarea> <br /> <br />

			</div>


			<div class="min-box" style="width: 100%">


				<div class="box_envio" id="box_envio" style="display: none;">Mensagem Enviada com Sucesso!</div>

			</div>
				<input id="btn_acao" type="submit" name="btn_acao" value="ENVIAR"/><br /> <br />

			<div style="clear: both;"></div>

		</div>
	</form>
	<div>
		<table style="margin: 0 auto;" >
			<tr>
				<td>
					<div style="text-align: center; height: 32px;">
						<form id="file_form" name="file_form" action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data" >
							<input type="file" name="file" value="" />
							<input type="hidden" name="file_hd_chamado" value="<?php echo $callcenter; ?>" />
							<input type="hidden" name="callcenter" value="<?php echo $callcenter; ?>" />
							<input type="hidden" name="anexar_imagem" value="true" />
							<input type="hidden" name="file_i" value="" />
							<button type="button" id="anexarImagens" style="cursor: pointer;" >Anexar arquivo selecionado</button>
							<img class="loadImg" id="anexando" style="vertical-align: -14px; display: none;" src="../../imagens/loading_indicator_big.gif" />
						</form>
					</div>

				</td>
			</tr>
			<tr>
				<td style="text-align: center;" class="td_img_anexadas">
					<br />

					<div class="img_anexada_model" style="display: none;">
						<a href="" ><img src="" /></a>

						<br />

						<span class="img_check_delete" >
							<input type="checkbox" name="img_anexada_nome" value="" />
							<input type="hidden" name="img_i" value="" />
						</span>
					</div>

					<?php
					$s3->getObjectList("{$callcenter}-", false);

					if (count($s3->files) > 0) {
						$file_links = $s3->getLinkList($s3->files);

						foreach ($s3->files as $key => $file) {
							$img_i = preg_replace("/.*.\//", "", $file);
							$img_i = preg_replace("/\..*./", "", $img_i);
							$img_i = explode("-", $img_i);
							$img_i = $img_i[1];

							$file_name = preg_replace("/.*.\//", "", $file);

							$type  = trim(strtolower(preg_replace("/.+\./", "", $file_name)));

							if ($type != "pdf") {
								$file_thumb = $s3->getLink("thumb_".$file_name);

								if (!strlen($file_thumb)) {
									$file_thumb = $file_name;
								}
							} else {
								$file_thumb = "imagens/icone_pdf.jpg";
							}

							?>
							<div class="img_anexada" rel="<?=$img_i?>">
								<a href="<?=$file_links[$key]?>" target="_blank" ><img src="<?=$file_thumb?>" /></a>

								<br />

								<span class="img_check_delete" >
									<input type="checkbox" name="img_anexada_nome" value="<?=$file_name?>" />
									<input type="hidden" name="img_i" value="<?=$img_i?>" />
								</span>
							</div>
							<?php
						}
					}
					?>
				</td>
			</tr>
			<tr>
				<td style="text-align: center;">
					<br />
					<img id="deletando" class="loadImg" src="../../imagens/loading_indicator_big.gif" style="display: none;" />
					<button type="button" id="deleta_img_checked" style="display: <?=(count($s3->files) > 0) ? 'inline' : 'none'?>;" >Deletar os arquivos selecionados</button>
				</td>
			</tr>
		</table>
	</div>
</body>
<body>
	<div class="tab">
		<div class="box_erro_produto" id="erro">
			<span>Por favor digite atendimento e o cpf corretamente</span>
		</div>
		<?php if ( strlen($_POST["btn_acao"]) > 0  AND strlen($msgerro) == 0 ){ ?>
		<div class="msg_enviada" id"sucesso" >
			<p style='display: block; text-align: center;' class='clear sucesso'>Mensagem Enviada com Sucesso</p>
		</div>
		<?php }else{ ?>
		<div class="min-box" style="width: 100%">
			<input id="verifica_atendimento" type="submit" value="OK"/><br /> <br />
		</div>
		<?php } ?>
	</div>
</body>
</html>
