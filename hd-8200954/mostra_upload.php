<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';
include 'funcoes.php';

$programa_insert = $_SERVER['PHP_SELF'];


include_once 'class/aws/s3_config.php';

include_once S3CLASS;

$s3 = new AmazonTC('os', (int) $login_fabrica);

header("Content-Type: text/html; charset=iso-8859-1");

if ($_POST["excluir"] == "true") {
	$os     = $_POST["os"];
	$motivo = trim($_POST["motivo"]);
	$files  = str_replace("\\","",$_POST['files']);
	$files  = json_decode($files, true);

	$sql = "SELECT os, DATE_PART('YEAR', data_digitacao) AS year, DATE_PART('MONTH', data_digitacao) AS month 
			FROM tbl_os 
			WHERE fabrica = {$login_fabrica} AND os = {$os}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		if (strlen($motivo) > 0) {
			pg_query($con, "BEGIN");

			$year  = pg_fetch_result($res, 0, "year");
			$month = pg_fetch_result($res, 0, "month");

			foreach ($files as $os_item => $fotos) {
				$sql = "SELECT parametros_adicionais 
						FROM tbl_os_item 
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
						WHERE tbl_os.os = {$os}
						AND tbl_os_item.os_item = {$os_item}";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");
					$parametros_adicionais = json_decode($parametros_adicionais, true);

					$parametros_adicionais["motivo"] = utf8_encode($motivo);

					foreach ($fotos as $array) {
						list($key) = $array;

						$parametros_adicionais["foto_upload"][$key]["upload"] = "f";
						$parametros_adicionais["foto_upload"][$key]["ext"]    = "";
					}

					$parametros_adicionais = json_encode($parametros_adicionais);

					$sql = "UPDATE tbl_os_item
							SET parametros_adicionais = '{$parametros_adicionais}'
							WHERE os_item = {$os_item}";
					$res = pg_query($con, $sql);

					if (pg_last_error()) {
						$return["erro"] = "Erro ao deletar imagens";
						break;
					}
				}
			}

			if (!isset($return["erro"])) {
				$sql = "UPDATE tbl_os_item
						SET
							admin                      = {$login_admin},
							liberacao_pedido           = FALSE,
							liberacao_pedido_analisado = FALSE,
							data_liberacao_pedido      = NULL
						FROM tbl_os_produto
						WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
						AND tbl_os_produto.os = {$os}";
				$res = pg_query($con, $sql);

				if (pg_last_error()) {
					$return["erro"] = "Erro ao deletar imagens";
				}

				if (!isset($return["erro"])) {
					$sql = "SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
					$res = pg_query($con, $sql);

					$posto = pg_fetch_result($res, 0, "posto");
					$sua_os = pg_fetch_result($res, 0, "sua_os");

					$sql = "SELECT tbl_comunicado.comunicado
							FROM tbl_comunicado
							LEFT JOIN tbl_comunicado_posto_blackedecker 
							ON tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
							WHERE tbl_comunicado.fabrica = {$login_fabrica}
							AND TO_ASCII(tbl_comunicado.descricao, 'LATIN-9') = TO_ASCII('Anexos de peças excluidos da OS {$os}', 'LATIN-9')
							AND tbl_comunicado_posto_blackedecker.comunicado_posto IS NULL";
					$res = pg_query($con, $sql);

					if (!pg_num_rows($res)) {
						$sql = "INSERT INTO tbl_comunicado (
									fabrica,
									posto,
									obrigatorio_site,
									tipo,
									ativo,
									descricao,
									mensagem
								) VALUES (
									{$login_fabrica},
									{$posto},
									true,
									'Com. Unico Posto',
									true,
									'Anexos de peças excluidos da OS {$sua_os}',
									'{$motivo}'
								)";
						$res = pg_query($con, $sql);

						$sql = "INSERT INTO tbl_os_interacao ( programa, fabrica, os, comentario, admin, interno) VALUES ('$programa_insert',$login_fabrica, $os, 'Anexo de peça excluido - $motivo', $login_admin, false)";
						$res = pg_query($con, $sql);

						if (pg_last_error()) {
							$return["erro"] = "Erro ao deletar imagens";
						}
					}

					if (!isset($return["erro"])) {
						$sql = "SELECT status_os FROM tbl_os_status WHERE os = {$os} ORDER BY data DESC";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) == 0 || pg_fetch_result($res, 0, "status_os") <> 174) {
							$sql = "INSERT INTO tbl_os_status
									(os, status_os, data, observacao)
									VALUES
									({$os}, 174, current_timestamp, 'Os com pendência de fotos regularize o anexo das fotos das peças')";
							$res = pg_query($con, $sql);

							if (pg_last_error()) {
								$return["erro"] = "Erro ao deletar imagens";
							}
						}
					}

					if (!isset($return["erro"])) {
						foreach ($files as $os_item => $fotos) {
							foreach ($fotos as $array) {
								list($key, $ext) = $array;
								$s3->deleteObject("{$os}-{$os_item}-{$key}.{$ext}", false, $year, $month);
							}
						}
					}
				}
			}
		} else {
			$return["erro"] = "Digite o motivo da exclusão das fotos";
		}
	} else {
		$return["erro"] = "OS não encontrada";
	}

	if (isset($return["erro"])) {
		pg_query($con, "ROLLBACK");
	} else {
		pg_query($con, "COMMIT");
		$return["sucesso"] = true;
	}

	echo json_encode($return);

	exit;
}

?>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css" />
		<link rel="stylesheet" type="text/css" href="fancybox/jquery.fancybox-1.3.4.css" />
		<style>
			.os_item {
				height: 100px;
				margin-bottom: 10px;
			}

			.os_item h5 {
				display: block;
				position: relative;
				float: left;
				margin-top: 40px;
				margin-right: 40px;
			}

			.img {
				display: block;
				position: relative;
				width: 100px;
				height: 90px;
				float: left;
				margin-right: 10px;
				cursor: pointer;
				text-align: center;
			}

			#deletar {
				margin-top: 20px;
				height: 40px;
				font-size: 14px;
				cursor: pointer;
			}

			p {
				font-size: 12px;
				color: #f00;
			}

			#loading {
				display: none;
			}
		</style>
		<script src="../js/jquery-1.7.2.js" ></script>
		<script type='text/javascript' src='../js/FancyZoom.js'></script>
		<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>
		<script>
			$(function () {
				setupZoom();

				var ajax_process = false;

				$("#deletar").click(function () {
					if ($("input[name=file_check]:checked").length > 0) {
						if (ajax_process == false) {
							if (confirm("Deseja realmente deletar as imagens selecionadas ?")) {
								var p = prompt("Motivo da exclusão das imagens");
								p = $.trim(p);

								if (p.length > 0) {
									var filesDelete = {};
									var os = $("input[name=os]").val();

									$("div.os_item").each(function () {
										if ($(this).find("input[name=file_check]:checked").length > 0) {
											var os_item = $(this).find("input[name=os_item]").val();

											if (os_item.length > 0) {
												filesDelete[os_item] = new Array();

												$(this).find("input[name=file_check]:checked").each(function () {
													var s = $(this).parent("span");
													var key = $(s).find("input[name=key]").val();
													var type = $(s).find("input[name=type]").val();

													filesDelete[os_item].push(new Array(key, type));
												});
											}
										}
									});

									$.ajax({
										url: "mostra_upload.php",
										type: "POST",
										dataType: "JSON",
										data: { excluir: true, os: os, motivo: p, files: JSON.stringify(filesDelete) },
										beforeSend: function () {
											ajax_process = true;
											$("#loading").show();
											$("#deletar").hide();
											$("input[type=checkbox]:not(:checked)").attr({ "disabled": "disabled" });
										},
										success: function (data) {
											if (data.erro) {
												alert(data.erro);
											} else {
												$("input[name=file_check]:checked").parent("span").hide(500);

												setTimeout(function () {
														$("input[name=file_check]:checked").parent("span").remove();

														$("div.os_item").each(function () {
															var count = $(this).find("span").length;

															if (count == 0) {
																if ($(this).find("h5[rel=sem_imagem]").length == 0) {
																	$(this).append("<h5 style='color: #F00;' rel='sem_imagem'>Nenhuma imagem anexada</h5>");
																}	
															}
														});
												}, 600);
											}

											ajax_process = false;
											$("#loading").hide();
											$("#deletar").show();
											$("input[type=checkbox]:not(:checked)").removeAttr("disabled");
										}
									});
								} else {
									alert("Informe o motivo da exclusão das imagens");
								}
							}
						} else {
							alert("Espere o processo atual finalizar");
						}
					} else {
						alert("Nenhuma imagem selecionada");
					}
				});
			});
		</script>
	</head>
	<body style="background-color: #FFF;">
		<div class="lp_header" >
		</div>
			<?php
			$os = $_GET["os"];
			if (isset($_GET["os_item"])) {
				$os_item = $_GET["os_item"];
				$where = "AND tbl_os_item.os_item = {$os_item}";
			}

			$sql = "SELECT 
						tbl_os_item.os_item,
						tbl_peca.referencia,
						tbl_os_item.parametros_adicionais,
						DATE_PART('YEAR', data_digitacao) AS year, 
						DATE_PART('MONTH', data_digitacao) AS month
				    FROM tbl_os_item
				    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				    JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
				    JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
				    WHERE tbl_os.os = {$os}
				    AND tbl_os.fabrica = {$login_fabrica}
				    /*AND tbl_os_item.servico_realizado = 20*/
				    AND tbl_os_item.parametros_adicionais ILIKE '%\"item_foto_upload\":\"t\"%'
				    {$where}
				    ORDER BY tbl_os_item.os_item ASC";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
			?>
				<div class="lp_nova_pesquisa">
				<input type="hidden" name="os" value="<?=$os?>" />

				<br />

					<?php
					for ($i = 0; $i < pg_num_rows($res); $i++) {
						$os_item               = pg_fetch_result($res, $i, "os_item");
						$referencia            = pg_fetch_result($res, $i, "referencia");
						$year                  = pg_fetch_result($res, $i, "year");
						$month                 = pg_fetch_result($res, $i, "month");
						$parametros_adicionais = pg_fetch_result($res, $i, "parametros_adicionais");
						$parametros_adicionais = json_decode($parametros_adicionais, true);
						?>

						<div id="<?=$os_item?>"	class="os_item" >
							<h5><?=$referencia?> - Anexos</h5>
							<input type="hidden" name="os_item" value="<?=$os_item?>" />

							<?php
							if ($parametros_adicionais["item_foto_upload"] == "t") {
								$count = 0;
								foreach ($parametros_adicionais["foto_upload"] as $key => $foto) {
									$upload = $foto["upload"];

									if ($upload == "t") {
										$ext = $foto["ext"];

										$thumb = $s3->getLink("thumb_{$os}-{$os_item}-{$key}.{$ext}", false, $year, $month);
										$full  = $s3->getLink("{$os}-{$os_item}-{$key}.{$ext}", false, $year, $month);
										?>
										<span name="<?=$key?>" class="img">
											<input type="hidden" name="key" value="<?=$key?>" />
											<input type="hidden" name="type" value="<?=$ext?>" />
											<a href="<?=$full?>" ><img src="<?=$thumb?>" title="Clique para ver a imagem em uma escala maior" style="width: 100px; height: 90px;" /></a>
											<? if (!isset($_GET["os_item"])) { ?>
												<br />
												<input type="checkbox" name="file_check" />
											<? } ?>
										</span>
									<?php
										$count++;
									}
								}

								if ($count == 0) {
								?>
									<h5 style="color: #F00;" rel="sem_imagem">Nenhuma imagem anexada</h5>
								<?php
								}
							}
							?>
						</div>
						<hr />
					<?php
					}

					if (!isset($_GET["os_item"])) {
					?>
						<h4 style="color: #FF0000; font-weight: bold;">Favor não fechar a janela antes do término da exclusão das imagens</h4>
						<button type="button" id="deletar">Deletar Imagens Selecionadas</button>
						<div id="loading" style="text-align: center;">
							<img src="../imagens/loading_indicator_big.gif" />
						</div>
						<p>
						Ao deletar alguma imagem a os irá sair da intervenção retornando ao posto para fazer novamente o upload da imagem
						</p>
					<?php
					}
					?>
				</div>
				<?php
			} else {
			?>
				<h5 style="color: #F00; text-align: center;">Nenhuma imagem anexada</h5>
			<?php
			}
			?>
		<div id="imgView" style="display: none;" >
			<div id="img" style="margin: 0 auto; margin-top: 5px; margin-bottom: 5px; text-align: center;" >
				<img  src='' />
			</div>
		</div>
	</body>
</html>
