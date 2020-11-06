<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";;
include "autentica_admin.php";
include "funcoes.php";

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("laudo_tecnico", $login_fabrica);


if (isset($_POST["procedencia"])) {
	try {
		
		pg_query($con,"BEGIN");

		if(is_array($_POST["procedencia"])) {
			foreach ($_POST["procedencia"] as $key => $value) {
				$faturamento_item = $key;
				$procedencia = $value;
			}
		}
		if(is_array($_POST["peca"])) {
			foreach ($_POST["peca"] as $key => $value) {
				$peca = $value;
			}
		}
		if(is_array($_POST["status_analise_peca"])) {
			foreach ($_POST["status_analise_peca"] as $key => $value) {
				$status_analise_peca = $value;
			}
		}
		if(is_array($_POST["observacao"])) {
			foreach ($_POST["observacao"] as $key => $value) {
				$observacao = $value;
			}
		}

		if (empty($faturamento_item)) {
			throw new \Exception("Peça não informada");
		}

		if (empty($procedencia)) {
			throw new \Exception("Informe a procedência");
		}

		if (!strlen($status_analise_peca)) {
			throw new \Exception("Informe a análise");
		}

		$sqlFaturamentoItem = "SELECT peca, extrato_devolucao, faturamento FROM tbl_faturamento_item WHERE faturamento_item = {$faturamento_item}";
		$resFaturamentoItem = pg_query($con, $sqlFaturamentoItem);

		if (!pg_num_rows($resFaturamentoItem)) {
			throw new \Exception("Erro ao verificar informações");
		} else {
			$peca              = pg_fetch_result($resFaturamentoItem, 0, "peca");
			$extrato           = pg_fetch_result($resFaturamentoItem, 0, "extrato_devolucao");
			$faturamento       = pg_fetch_result($resFaturamentoItem, 0, "faturamento");
		}

		$sql = "INSERT INTO tbl_faturamento_lgr
				(fabrica, faturamento_item, peca, status_analise_peca, extrato, admin, procedencia, observacao)
				VALUES
				({$login_fabrica}, {$faturamento_item}, {$peca}, {$status_analise_peca}, {$extrato}, {$login_admin}, '{$procedencia}', '{$observacao}')";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new \Exception("Erro ao gravar análise de unidade");
		}

		if (empty($faturamento)) {
			throw new \Exception("Faturamento não informado");
		}

		$sql = "SELECT
					DISTINCT tbl_faturamento.faturamento,
					tbl_os_item.os_item,
					SUM(tbl_faturamento_item.qtde_inspecionada) AS qtde_inspecionada,
					(
						SELECT COUNT(tbl_faturamento_lgr.peca)
						FROM tbl_faturamento_lgr
						INNER JOIN tbl_extrato on tbl_faturamento_lgr.extrato = tbl_extrato.extrato
						INNER JOIN tbl_faturamento_item ON tbl_faturamento_lgr.faturamento_item = tbl_faturamento_item.faturamento_item
						INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.faturamento = {$faturamento}
						WHERE tbl_faturamento_lgr.extrato = tbl_extrato.extrato
					) AS qtde_analisada
				FROM tbl_faturamento_lgr
				INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_lgr.peca AND tbl_peca.fabrica = {$login_fabrica}
				INNER JOIN tbl_status_analise_peca ON tbl_status_analise_peca.status_analise_peca = tbl_faturamento_lgr.status_analise_peca AND tbl_status_analise_peca.fabrica = {$login_fabrica}
				INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento_item = tbl_faturamento_lgr.faturamento_item
				INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
				LEFT JOIN tbl_faturamento AS tbl_faturamento_origem ON tbl_faturamento_origem.nota_fiscal = tbl_faturamento_item.nota_fiscal_origem AND tbl_faturamento_origem.fabrica = {$login_fabrica} AND tbl_faturamento_origem.posto = tbl_faturamento.distribuidor
				LEFT JOIN tbl_faturamento_item AS tbl_faturamento_item_origem ON tbl_faturamento_item_origem.faturamento = tbl_faturamento_origem.faturamento AND tbl_faturamento_item_origem.peca = tbl_faturamento_item.peca
				LEFT JOIN tbl_os_item ON tbl_os_item.os_item = tbl_faturamento_item_origem.os_item
				WHERE tbl_faturamento.fabrica = {$login_fabrica}
				AND tbl_os_item.peca = {$peca}
				AND tbl_faturamento.faturamento = {$faturamento}
				GROUP BY tbl_faturamento.faturamento, tbl_os_item.os_item";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$qtde_inspecionada = pg_fetch_result($res, 0, "qtde_inspecionada");
			$qtde_analisada    = pg_fetch_result($res, 0, "qtde_analisada");
			$os_item           = pg_fetch_result($res, 0, "os_item");
			if ($qtde_inspecionada == $qtde_analisada) {
				$sql = "UPDATE tbl_faturamento
						SET devolucao_concluida = TRUE
						WHERE fabrica = {$login_fabrica}
						AND faturamento = {$faturamento}";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new \Exception("Erro ao concluir devolução");
				} 
			}
		}

	    $posicao = $os_item;

	    $arquivo = $_FILES["anexo_upload_{$faturamento_item}"];
	    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

	    if ($ext == "jpeg") {
	        $ext = "jpg";
	    }

	    if (strlen($arquivo["tmp_name"]) > 0) {
	        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
	            throw new \Exception("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx");
	        } else {
	        	$contador = $s3->getObjectList("analise_os_{$posicao}_");
	        	$contador = count($contador);

	            $arquivo_nome = "analise_os_{$posicao}_{$contador}";

	            $s3->upload("{$arquivo_nome}", $arquivo);

	            if($ext == "pdf"){
	            	$link = "imagens/pdf_icone.png";
	            } else if(in_array($ext, array("doc", "docx"))) {
	            	$link = "imagens/docx_icone.png";
	            } else {
		            $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}");
		      }

		        $href = $s3->getLink("{$arquivo_nome}.{$ext}");

	            if (!strlen($link)) {
	                throw new \Exception("Erro ao anexar arquivo link");
	            } 
	        }
	    } 
	    	pg_query($con,"COMMIT");

		$sql = "SELECT descricao FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND status_analise_peca = {$status_analise_peca}";
		$res = pg_query($con, $sql);

		$status_analise_peca_descricao = pg_fetch_result($res, 0, "descricao");

		$retorno = array(
			"procedencia"  => ($procedencia == "t") ? "Sim" : utf8_encode("Não"),
			"status_analise_peca" => utf8_encode($status_analise_peca_descricao),
			"observacao"   => utf8_encode($observacao),
			"faturamento"  => $faturamento,
		);
	} catch(Exception $e) {
		pg_query($con,"ROLLBACK");
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}


?>

<!DOCTYPE html>
<html>
<head>
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

	<style>

	label.control-label {
		font-weight: bold;
	}

	table.table-no-margin {
		margin-bottom: 0px;
	}

	ul.nav.nav-tabs {
		margin-right: 0px;
	}

	ul.nav.nav-tabs > li.active  {
		background-color: #596D9B;
	}

	ul.nav.nav-tabs > li.active > a  {
		background-color: #596D9B;
		color: #FFFFFF;
        border:none;
	}

	ul.nav.nav-tabs > li.active > a:first-child  {
        cursor: pointer;	
	}

	ul.nav.nav-tabs > li.active > a:first-child:hover  {
        background: #eeeeee ;	
        color: #000000;
	}
	div.tab-content {
		display: none;
		border: 1px solid #596D9B;
		border-radius: 0px 4px 4px 4px;
		padding: 5px;
	}

	#nota_fiscal_input {
		display: none;
	}

	</style>

	<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script src="bootstrap/js/bootstrap.js"></script>

	<?php 
	$plugins = array(
		"ajaxform",
		"fancyzoom",
	);

	include __DIR__."/plugin_loader.php";
	?>


	<script>
	var badge;
	var div;


	$(function() {

		$("form[name=form_anexo]").ajaxForm({
	        complete: function(data) {
				data = JSON.parse(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {

					var qtde_analisada = parseInt($(badge).data("qtde_analisada")) + 1;
					var qtde_inspecionada  = parseInt($(badge).data("qtde_inspecionada"));

					$(badge).data("qtde_analisada", qtde_analisada).text(qtde_analisada + "/" + qtde_inspecionada);

					if (qtde_analisada == qtde_inspecionada) {
						$(badge).removeClass("badge-important badge-warning").addClass("badge-success");
						$(div).find("div.row-fluid").first().after("<div class='alert alert-success'><h6>Todas as unidades já foram analisadas</h6></div>");
						$(div).find("div.row-fluid").remove();

					} else if (qtde_analisada < qtde_inspecionada) {
						$(badge).removeClass("badge-important").addClass("badge-warning");
						$(div).find("input[type=text]").val("");
						$(div).find("input[type=radio]:checked")[0].checked = false;
						$(div).find("select").val("");
					}

					var trLength = $(div).find("table > tbody > tr").length + 1;

					$(div).find("table > tbody").append("\
						<tr>\
							<td><span class='badge' >"+trLength+"</span></td>\
							<td>"+data.procedencia+"</td>\
							<td>"+data.status_analise_peca+"</td>\
							<td>"+data.observacao+"</td>\
						</tr>\
					");

				}
	    		}
	    	});

		$("ul.nav.nav-tabs > li > .open-pane").click(function() {
			if ($("div.tab-content").not(":visible")) {
				$("div.tab-content").show();
                window.scrollTo(0, $("ul.nav.nav-tabs").offset().top);
			}
		});

		$("button.grava_analise").click(function() {
			$(this).button("loading");

			try {
				var faturamento_item = $(this).data("item");

				if (typeof faturamento_item != "undefined") {
					badge = $("#badge_"+faturamento_item);
					div   = $("#"+faturamento_item);

					var procedencia         = $("input[name='procedencia["+faturamento_item+"]']:checked").val();
					var status_analise_peca        = $("select[name='status_analise_peca["+faturamento_item+"]']").val();
					var observacao          = $.trim($("input[name='observacao["+faturamento_item+"]']").val());

					if (typeof procedencia == "undefined" || procedencia.length == 0) {
						throw new Error("Informe a Procedência");
					}

					if (typeof status_analise_peca == "undefined" || status_analise_peca.length == 0) {
						throw new Error("Informe a Análise");
					}

					$(this).parents('form').submit();

				}
			} catch (e) {
				alert(e.message);
			}

			$(this).button("reset");
		});	
	});

	</script>
</head>
<body>
	<div class="container" >
		<?php
		$faturamento = $_REQUEST["faturamento"];

		if (empty($faturamento)) {
		?>
			<div class="alert alert-danger"><h4>Faturamento não informado</h4></div>
		<?php
		} else {
		?>
			<div class="page-header"><h4>Parecer Técnico da Nota Fiscal de Devolução</h4></div>

			<?php
			 $sql = "SELECT
						DISTINCT tbl_faturamento.faturamento,
						tbl_faturamento.nota_fiscal,
						tbl_extrato.extrato
					FROM tbl_faturamento
					INNER JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.posto
					INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
					INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao OR tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
					WHERE tbl_faturamento.fabrica = {$login_fabrica}
					AND tbl_extrato.fabrica = {$login_fabrica}
					AND tbl_faturamento.faturamento = {$faturamento}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$nota_fiscal        = pg_fetch_result($res, 0, "nota_fiscal");
				$extrato            = pg_fetch_result($res, 0, "extrato");
				?>

				<h5>Extrato: <?=$extrato?></h5>
				<h5>Nota Fiscal:  <label style="display: inline"><?=$nota_fiscal?></label></h5>

				<br />

				<h5>Análise das Peças <span style="color: #B94A48;" >(clique no código da peça para fazer a análise)</span></h5>

				<div class="tabbable tabs-left" >
					<ul  class="nav nav-tabs" >
						<?php
                        $campos = array();
                        if($login_fabrica == 145){
                            $campos[] = ',tbl_os_extra.os'; 
                            $joinOsExtra = "INNER JOIN tbl_extrato on tbl_extrato.extrato = tbl_extrato_lgr.extrato
                                            INNER JOIN tbl_os_extra on tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_os_extra.os = tbl_faturamento_item.os";
                        }
						$sqlItem = "SELECT 
								DISTINCT tbl_faturamento_item.faturamento_item,
								tbl_peca.peca,
								tbl_peca.referencia AS codigo,
								tbl_peca.descricao,
								tbl_faturamento_item.qtde_inspecionada AS qtde_inspecionada,
								(
									SELECT COUNT(tbl_faturamento_lgr.peca)
									FROM tbl_faturamento_lgr
									WHERE tbl_faturamento_lgr.fabrica = {$login_fabrica}
									AND tbl_faturamento_lgr.faturamento_item = tbl_faturamento_item.faturamento_item
									AND tbl_faturamento_lgr.peca = tbl_faturamento_item.peca
									AND tbl_faturamento_lgr.extrato = tbl_extrato_lgr.extrato
                                ) AS qtde_analisada ".
                                implode(',',$campos). " 
							FROM tbl_faturamento_item
							INNER JOIN tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca AND tbl_peca.fabrica = {$login_fabrica}
							INNER JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato = tbl_faturamento_item.extrato_devolucao AND tbl_extrato_lgr.peca = tbl_faturamento_item.peca
                            ". $joinOsExtra ."
							WHERE tbl_faturamento_item.faturamento = {$faturamento}
							AND tbl_faturamento_item.qtde_inspecionada > 0";
						$resItem = pg_query($con, $sqlItem);
						while ($item = pg_fetch_object($resItem)) {
							if ($item->qtde_inspecionada == $item->qtde_analisada) {
								$badgeClass = "badge-success";
							} else if ($item->qtde_analisada > 0 && $item->qtde_analisada < $item->qtde_inspecionada) {
								$badgeClass = "badge-warning";
							} else if ($item->qtde_analisada == 0) {
								$badgeClass = "badge-important";
							}
                            //style='display:inline;width:74px;'
							echo "<li style='width:160px;padding:8px'><a style='display:inline;padding:0px;' href='os_press.php?os={$item->os}' target='_blank'>{$item->os} /</a>  <a style='display:inline;padding:0px;' href='#{$item->faturamento_item}' class='open-pane' data-toggle='tab' ><span title='{$item->codigo} - {$item->descricao}' > {$item->codigo}</span>&nbsp;&nbsp;&nbsp;<span style='display:inline' id='badge_{$item->faturamento_item}' class='badge {$badgeClass} pull-right' title='analisadas: {$item->qtde_analisada} de {$item->qtde_inspecionada} peças' data-qtde_analisada='{$item->qtde_analisada}' data-qtde_inspecionada='{$item->qtde_inspecionada}' >{$item->qtde_analisada}/{$item->qtde_inspecionada}</span> <br /></a></li>";
                        }
						?>
					</ul>
					<div  class="tab-content" >
						<?php
						pg_result_seek($resItem, 0);

						while ($item = pg_fetch_object($resItem)) {
						?>
							<div class="tab-pane" id="<?=$item->faturamento_item?>" >
								<h6>Análise de unidades</h6>
								<h6>Peça: <?=$item->codigo?> - <?=$item->descricao?></h6>

								<?php
								if ($item->qtde_analisada < $item->qtde_inspecionada) {
								?>
									<form enctype="multipart/form-data" name="form_anexo" method="post" >

										<div class="row-fluid" >

											<input type="hidden" class="span12" name="peca[<?=$item->peca?>]" />
											<div class="span2">
												<div class="control-group" >
													<label class="control-label" >Procede</label>
													<div class="controls controls-row" >
														<label class="radio span6"><input type="radio" class="radio" name="procedencia[<?=$item->faturamento_item?>]" value="t" />Sim</label>
														<label class="radio span6"><input type="radio" class="radio" name="procedencia[<?=$item->faturamento_item?>]" value="f" />Não</label>
													</div>
												</div>
											</div>
											
											<div class="span3">
												<div class="control-group" >
													<label class="control-label" >Análise</label>
													<div class="controls controls-row" >
														<select class="span12" name="status_analise_peca[<?=$item->faturamento_item?>]" >
															<option></option>
															<?php
															unset($resAnalisePeca);

															$sqlAnalisePeca = "SELECT status_analise_peca, descricao FROM tbl_status_analise_peca WHERE fabrica = {$login_fabrica} AND ativo IS TRUE";
															$resAnalisePeca = pg_query($con, $sqlAnalisePeca);

															if (pg_num_rows($resAnalisePeca) > 0) {
																while ($status_analise_peca = pg_fetch_object($resAnalisePeca)) {
																	echo "<option value='{$status_analise_peca->status_analise_peca}' >{$status_analise_peca->descricao}</option>";
																}
															}
															?>
														</select>
													</div>
												</div>
											</div>
											
											<div class="span5">
												<div class="control-group" >
													<label class="control-label" >Observação</label>
													<div class="controls controls-row" >
														<input type="text" class="span12" name="observacao[<?=$item->faturamento_item?>]" />
													</div>
												</div>
											</div>

											<div class="span1">
												<div class="control-group" >
													<label>&nbsp;</label>
													<div class="controls controls-row" >
														<button type="button" class="btn btn-success btn-small grava_analise" data-item="<?=$item->faturamento_item?>" data-loading-text="Gravando..." >Gravar</button>
													</div>
												</div>
											</div>
										</div>
										<div class="row-fluid" >

											<div class="span11">
												<div class="control-group" >
													<label class="control-label" >Anexo</label>
													<div class="controls controls-row" >
														<input type="file" name="anexo_upload_<?=$item->faturamento_item?>" value="" />
													</div>
												</div>
											</div>

										</div>
									</form>
								<?php
								} else {
								?>
									<div class="alert alert-success"><h6>Todas as unidades já foram analisadas</h6></div>
								<?php
								}
								?>

								<table class="table table-bordered" >
									<thead>
										<tr class="titulo_coluna" >
											<th colspan="5" >Unidades Analisadas</th>
										</tr>
										<tr class="titulo_coluna" >
											<th>#</th>
											<th>Procede</th>
											<th>Análise</th>
											<th>Observação</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$sql = "SELECT
													tbl_faturamento_lgr.procedencia,
													tbl_status_analise_peca.descricao AS status_analise_peca,
													tbl_faturamento_lgr.observacao
												FROM tbl_faturamento_lgr
												INNER JOIN tbl_status_analise_peca ON tbl_status_analise_peca.status_analise_peca = tbl_faturamento_lgr.status_analise_peca AND tbl_status_analise_peca.fabrica = {$login_fabrica}
												WHERE tbl_faturamento_lgr.fabrica = {$login_fabrica}
												AND tbl_faturamento_lgr.faturamento_item = {$item->faturamento_item}
												ORDER BY tbl_faturamento_lgr.data_input ASC";
										$res = pg_query($con, $sql);

										$rows = pg_num_rows($res);


										// $anexos = $s3->getObjectList("analise_os_{$item->faturamento_item}");

										// if (count($anexos) > 0) {
										// 	$ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

										// 	if ($ext == "pdf") {
										// 		$anexo_imagem = "imagens/pdf_icone.png";
										// 	} else if (in_array($ext, array("doc", "docx"))) {
										// 		$anexo_imagem = "imagens/docx_icone.png";
										// 	} else {
										// 		$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]));
										// 	}

										// 	$anexo_link    = $s3->getLink(basename($anexos[0]));
										// }


										for ($i = 0; $i < $rows; $i++) {
											$x                   = $i + 1;
											$procedencia         = pg_fetch_result($res, $i, "procedencia");
											$procedencia         = ($procedencia == "t") ? "Sim" : "Não";
											$status_analise_peca        = pg_fetch_result($res, $i, "status_analise_peca");
											$observacao          = pg_fetch_result($res, $i, "observacao");

											echo "
												<tr>
													<td><span class='badge'>{$x}</span></td>
													<td>{$procedencia}</td>
													<td>{$status_analise_peca}</td>
													<td>{$observacao}</td>
												</tr>
											";
										}
										?>
									</tbody>
								</table>
							</div>
						<?php
						}
                        
	
						?>
					</div>
				</div>
			<?php
			} else {
			?>
				<div class="alert alert-danger"><h4>Nota Fiscal de Devolução não encontrada</h4></div>
			<?php
			}
		}
		?>

		<br /><br /><br />
	</div>
</body>
</html>
