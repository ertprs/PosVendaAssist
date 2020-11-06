<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

if ($areaAdmin === true) {
	include "autentica_admin.php";
} else {
	include "autentica_usuario.php";
}

include "funcoes.php";

#ini_set("display_errors", 1);
#error_reporting(E_ALL);

if (isset($_POST["ajax_confirma_leitura"])) {
	$id = $_POST["id"];
	$pendInt = $_POST["pendInt"];

	if (empty($id)) {
		$retorno = array("erro" => "Erro ao confirmar leitura");
	} else {
		$data       = date("Y-m-d H:i");
		$data_title = date("d/m/Y H:i");

        $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os_interacao.fabrica IN (11,172) " : " tbl_os_interacao.fabrica = $login_fabrica ";

		$sql = "UPDATE tbl_os_interacao SET confirmacao_leitura = '{$data}' WHERE os_interacao = {$id} AND {$cond_pesquisa_fabrica}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => "Erro ao confirmar leitura");
		} else {
			
			$retorno = array("title" => utf8_encode("Leitura confirmada em {$data_title}"));

			if ($pendInt == 'nao') {
				 $sqlInteracoesPendentes = "
				 							SELECT DISTINCT ON (tbl_os.os) tbl_os.os
								            FROM tbl_os_interacao
								            JOIN tbl_os ON tbl_os.os = tbl_os_interacao.os AND tbl_os.fabrica = {$login_fabrica} AND tbl_os.posto = {$login_posto}
										    WHERE tbl_os_interacao.interno IS NOT TRUE
										    AND tbl_os.finalizada IS NULL
										    AND tbl_os_interacao.posto = $login_posto
										    AND tbl_os_interacao.fabrica = $login_fabrica
								            AND tbl_os_interacao.confirmacao_leitura IS NULL
								            AND tbl_os_interacao.admin IS NOT NULL
								            AND tbl_os_interacao.data > CURRENT_TIMESTAMP - INTERVAL '1 YEAR'
								            AND tbl_os.excluida is not true
								            AND tbl_os.cancelada is not true
								            ORDER BY tbl_os.os, tbl_os_interacao.data DESC ";
				$resInteracoesPendentes = pg_query($con, $sqlInteracoesPendentes);
				if (pg_num_rows($resInteracoesPendentes) == 0) {
					$retorno["recarrega"] = true;
				}
			}
		}
	}

	exit(json_encode($retorno));
}

$interacao_interna = NULL;

if (isset($_POST["interacao_interna"])) {
    $interacao_interna_post = $_POST["interacao_interna"];
    $interacao_interna = false;

    if ($interacao_interna == "true") {
        $interacao_interna = true;
    }
}

if ($login_fabrica == 203) {
    if (base64_decode($_GET['v']) == true) {
        $login_fabrica       = 167;
    }
}

include "os_cadastro_unico/interacao/regras.php";

if (file_exists("os_cadastro_unico/interacao/{$login_fabrica}/regras.php")) {
	include "os_cadastro_unico/interacao/{$login_fabrica}/regras.php";
}

unset($interacao_interna);

if ($login_fabrica == 3 && $mostra_tela_interacao === false) {
	exit;
}

include "os_cadastro_unico/interacao/interacao.php";

if ($login_fabrica == 167) {
    if (base64_decode($_GET['v']) == true) {
        $login_fabrica       = 203;
    }
}
?>
<!DOCTYPE html />
<html>
<head>
    <meta http-equiv=pragma content=no-cache />
    <link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/glyphicon.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
    <link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

    <script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js" ></script>
    <script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="bootstrap/js/bootstrap.js" ></script>
    <script src="plugins/shadowbox_lupa/lupa.js"></script>
    <script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js"></script>
    <script src="plugins/jquery.mask.js"></script>

    <script>

    $(function() {
        <?php
        if (in_array("interacao_data_contato", $inputs_interacao)) {
        ?>
            $.datepickerLoad(["interacao_data_contato"], { minDate: 0, dateFormat: "dd/mm/yy" });
        <?php
        }
        ?>

        $("#inter_submit").on("click", function() {

        	$("#inter_submit").prop("disabled", true);

            var os 	   = $(this).data('os');
            var iframe = $(this).data('iframe');
            var dados  = $("#form_interacao").serialize();
            var btn = $(this);

            $(btn).button("loading");

            $.ajax({
                url: "interacao_os.php?os="+os+iframe,
                type: "POST",
                data: dados,
                dataType: "json",
                success : function(resultado) {



                	<?php if (in_array($login_fabrica, [104])) { ?>
                	let div = $("#" + os + " .os_auditoria_titulo",  window.parent.document);

                	if (resultado.interacao_admin) {
                		div.css('background-color', 'rgb(117,220,117);');
                	} else {
                		div.css("background-color", "rgb(255,64,64);");
                	} 	
                	<?php } ?>
	            
	                if (resultado.status) {
	                    $(".mensagem-sucesso").show();
	                    $("#mensagem-sucesso").html(resultado.mensagem);
	                    $("#interacao_mensagem").val("");
	                    $("#parecer_final").prop("checked", false);
	                    $("#interacao_interna").prop("checked", false);
	                    $("#interacao_email").prop("checked", false);
	                    $("#interacao_transferir").prop("checked", false);
	                    setTimeout(function(){ location.reload(); }, 500);
	                } else {
	                    $(".mensagem-erro").show();
	                    $("#mensagem-erro").html(resultado.mensagem);
	                    setTimeout(function(){ location.reload(); }, 500);
	                }
            	}, error: function(result){
            		$(".mensagem-erro").show();
                    $("#mensagem-erro").html(result.mensagem);
                    setTimeout(function(){ location.reload(); }, 500);
            	}
            });
        });

        $("button.btn-leitura").on("click", function() {
            var btn = $(this);
            var id  = $(this).data("id");
            let pendInt = "";

            <?php if (in_array($login_fabrica, [152,180,181,182])) { ?>
            	pendInt = $("#pendInt").val();
            <?php } ?>

            $.ajax({
                url: "interacao_os.php",
                type: "post",
                data: { ajax_confirma_leitura: true, id: id, pendInt: pendInt }
            })
            .done(function(data) {
                data = JSON.parse(data);

                if (data.erro) {
                    alert(data.erro);
                } else {
                	if (data.recarrega) {
                		location.reload();
                	}

                    $(btn).removeClass("btn-warning")
                          .addClass("btn-success")
                          .prop({ disabled: true })
                          .attr({ title: data.title })
                          .find("i")
                          .removeClass("icon-eye-close")
                          .addClass("icon-eye-open");
                }
            });
        });
    });

    $(window).on("load", function() {
        changeHeight();
    });

    function changeHeight() {
    	$("#inter_submit").button("reset");
        if (typeof window.parent.changeIframeHeight != "undefined") {
            var height = $(document).height();
            window.parent.changeIframeHeight("iframe_interacao_os", height);

            height = $("#container_lupa").height();
            $("#container_lupa").css({ height: height+"px" });
        }
    }

    </script>
</head>
<body>
	<?php
	if (isset($_GET["iframe"])) {
		$overflow_y = "none";
		$iframe = "&iframe=true";
	} else {
		$overflow_y = "auto";
	}

	if ($_GET['cancelada'] || (isFabrica(35) && $areaAdmin != true)) {
		$formulario_interacao = false;
	}
	?>
	<div id="container_lupa" style="overflow-y: <?=$overflow_y?>;" >
		<?php
		if (isset($os_not_found)) {
		?>
			<div class="alert alert-error" >Ordem de Serviço <?=$os?> não encontrada</div>
		<?php
		} else {

			if (in_array($login_fabrica, [152,180,181,182]) && $areaAdmin != true) {
				 $sqlInteracoesPendentes = "
				 							SELECT DISTINCT ON (tbl_os.os) tbl_os.os
								            FROM tbl_os_interacao
								            JOIN tbl_os ON tbl_os.os = tbl_os_interacao.os AND tbl_os.fabrica = {$login_fabrica} AND tbl_os.posto = {$login_posto}
										    WHERE tbl_os_interacao.interno IS NOT TRUE
										    AND tbl_os.finalizada IS NULL
										    AND tbl_os_interacao.posto = $login_posto
										    AND tbl_os_interacao.fabrica = $login_fabrica
								            AND tbl_os_interacao.confirmacao_leitura IS NULL
								            AND tbl_os_interacao.admin IS NOT NULL
								            AND tbl_os_interacao.data > CURRENT_TIMESTAMP - INTERVAL '1 YEAR'
								            AND tbl_os.excluida is not true
								            AND tbl_os.cancelada is not true
								            ORDER BY tbl_os.os, tbl_os_interacao.data DESC ";
				$resInteracoesPendentes = pg_query($con, $sqlInteracoesPendentes);
				if (pg_num_rows($resInteracoesPendentes) > 0) {
					$formulario_interacao = false;
				}
			}

			if (in_array($login_fabrica, [167,203])) {
			    if (base64_decode($_GET['v']) == true) {
			        $formulario_interacao = false;
			    }
			}

			if ($formulario_interacao === true) {
			?>
				<form name="form_interacao" role="form" class="tc_formulario" id="form_interacao" >
				<input type="hidden" name="interacao_submit" id="interacao_submit" value="1" />
					<div class='titulo_tabela '><?= traduz('Interagir na Ordem de Serviço') ?></div>

					<br />

					<div class="row-fluid" >
						<div class="span1"></div>
						<div class="span10" >
							<div class="control-group" >
								<label class="control-label" for="interacao_mensagem" ><?= traduz('Mensagem') ?></label>
								<div class="controls controls-row" >
									<textarea id="interacao_mensagem" name="interacao_mensagem" class="span12" ><?=$_POST['interacao_mensagem']?></textarea>
								</div>
							</div>
						</div>
						<div class="span1"></div>
					</div>
					<?php
					if (count($inputs_interacao) > 0) {
					?>
						<div class="row-fluid" >
							<div class="span1"></div>
							<div class="span10" >
								<div class="control-group" >
									<div class="controls controls-row" >
										<?php
										if (in_array("parecer_final", $inputs_interacao)) {
                                        ?>
                                               <label class="checkbox" style="width: 100px">
                                               <input type="checkbox" id="parecer_final" name="parecer_final" value="true" <?=($_POST["parecer_final"] == "true") ? "checked" : ""?> /> Parecer Final
                                           </label>
                                        <?php
                                        }

										if (in_array("interacao_interna", $inputs_interacao)) {
										?>
											<label class="checkbox" style="width: 100px">
										    	<input type="checkbox" id="interacao_interna" name="interacao_interna" value="true" <?=($_POST["interacao_interna"] == "true") ? "checked" : ""?> /> <?=traduz('Interação Interna')?>
										    </label>
										<?php
										}

										if (in_array("interacao_email", $inputs_interacao)) {
										?>
											<label class="checkbox" style="width: 222px">
										    	<input type="checkbox" id="interacao_email" name="interacao_email" value="true" <?=($_POST["interacao_email"] == "true") ? "checked" : ""?> /> <?=traduz('Enviar Email para o Posto Autorizado')?>
										    </label>
									    <?php
										}

										if (in_array("interacao_email_consumidor", $inputs_interacao)) {
										?>
											<label class="checkbox" style="width: 190px">
										    	<input type="checkbox" id="interacao_email_consumidor" name="interacao_email_consumidor" value="true" <?=($_POST["interacao_email_consumidor"] == "true") ? "checked" : ""?> /> Enviar Email para o Consumidor
										    </label>
									    <?php
										}

										if (in_array("interacao_sms_consumidor", $inputs_interacao)) {
										?>
											<label class="checkbox" style="width: 185px">
										    	<input type="checkbox" id="interacao_sms_consumidor" name="interacao_sms_consumidor" value="true" <?=($_POST["interacao_sms_consumidor"] == "true") ? "checked" : ""?> /> Enviar SMS para o Consumidor
										    </label>
									    <?php
										}

										if (in_array("interacao_transferir", $inputs_interacao)) {
										?>
										    <label class="checkbox" style="width: 350px">
										    	<input type="checkbox" id="interacao_transferir" name="interacao_transferir" style="margin-top: 12px;" value="true" <?=($_POST["interacao_transferir"] == "true") ? "checked" : ""?> /> Transferir para:
										    	<select id="interacao_transferir_admin" name="interacao_transferir_admin" class="span5" style="width: 250px">
										    		<?php
										    		//SELECT admin, login FROM tbl_admin WHERE fabrica = $login_fabrica and tbl_admin.ativo ORDER BY login
										    		$sqlAdmin = "SELECT admin, nome_completo FROM tbl_admin WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY nome_completo ASC";
										    		$resAdmin = pg_query($con, $sqlAdmin);
										    			echo "<option value=''>Selecione</option>"; //hd_chamado=2742793 & hd_chamado=2757360
										    		while ($admin = pg_fetch_object($resAdmin)) {
										    			$selected = ($_POST["interacao_transferir_admin"] == $admin->admin) ? "selected" : "";

										    			echo "<option value='{$admin->admin}' {$selected} >{$admin->nome_completo}</option>";
										    		}
										    		?>
										    	</select>
										    </label>
										<?php
										}

										if (in_array("interacao_data_contato", $inputs_interacao)) {
										?>
											<label>
												Data de contato
												<input type="text" name="interacao_data_contato" id="interacao_data_contato" class="span4 datepicker" value="<?=$_POST['interacao_data_contato']?>" />
											</label>
										<?php
										}

										if (in_array("interacao_atendido", $inputs_interacao)) {
										?>
											<label class="checkbox" style="width: 55px">
										    	<input type="checkbox" id="interacao_atendido" name="interacao_atendido" value="true" <?=($_POST["interacao_atendido"] == "true") ? "checked" : ""?> /> Atendido
										    </label>
										<?php
										}

										if (in_array("interacao_email_fabricante", $inputs_interacao)) {
										?>
											<label class="checkbox" style="width: 160px">
										    	<input type="checkbox" id="interacao_email_fabricante" name="interacao_email_fabricante" value="true" <?=($_POST["interacao_email_fabricante"] == "true") ? "checked" : ""?> /> Enviar Email para a fábrica
										    </label>
										<?php
										}

										if (in_array("interacao_email_setor", $inputs_interacao)) {
											$label_email = 'Enviar Email para';
										?>
											<label class="checkbox" >
                                                <?php 
                                                	if ($login_fabrica <> 11): 
                                                		if (in_array($login_fabrica, [11, 172])) { 
                                                			$label_email = 'Selecione o setor:';
                                                ?>
                                                			<input type="hidden" name="interacao_email_setor_enviar" value="true" />
                                        		<?php 
                                        				} else { ?>
                                        					<input type="checkbox" id="interacao_email_setor_enviar" name="interacao_email_setor_enviar" style="margin-top: 12px;" value="true" <?=($_POST["interacao_email_setor_enviar"] == "true") ? "checked" : ""?> />
                                        		<?php   
                                        				}
                                                	endif; 
                                                	echo $label_email;
                                                ?>

												<select id="interacao_email_setor" name="interacao_email_setor" class="span5" >
													<?php
														echo "<option value=''>Selecione</option>"; //hd_chamado=2742793 & hd_chamado=2757360
													foreach ($fabrica_setor_email as $setor_id => $setor_data) {
														$selected = ($setor_id == $interacao_email_setor) ? "selected" : "" ;

														echo "<option value='{$setor_id}' {$selected} >{$setor_data['nome']}</option>";
													}
													?>
												</select>
										    </label>
										<?php
										}
										if ($telecontrol_distrib == 't' && $areaAdmin === true && $login_privilegios == '*') {
										?>
											<label class="checkbox" style="width: 335px">
												<input type="checkbox" id="#interacao_obg_gestao_interna" name="interacao_obg_gestao_interna">
												Mensagem de leitura obrigatória pela Assistência Técnica
											</label>
										<?php
										}
										?>
									</div>
								</div>
							</div>
							<div class="span1"></div>
						</div>
					<?php
					}
					?>
					<div class="row-fluid">
						<div class="span12 tac" >
							<div class="control-group" >
								<label class="control-label" >&nbsp;</label>
								<div class="controls controls-row tac" >
									<button type="button" data-iframe="<?=$iframe?>" data-os="<?=$os?>" id="inter_submit" name="inter_submit" class="btn btn-success" data-loading-text="Interagindo..." ><i class="icon-comment icon-white" ></i> <?= traduz('Interagir') ?></button>
								</div>
							</div>
						</div>
					</div>
				</form>
			<?php
			}
			?>
			<div class="alert alert-success mensagem-sucesso" style="display:none">
				<button type="button" class="close" data-dismiss="alert" >&times;</button>
				<strong id="mensagem-sucesso"><?=$msg_sucesso?></strong>
			</div>

			<div class="alert alert-danger mensagem-erro" style="display:none">
				<button type="button" class="close" data-dismiss="alert" >&times;</button>
				<strong id="mensagem-erro"><?=implode("<br />", $msg_erro)?></strong>
			</div>

			<?php if (!in_array($login_fabrica, [203]) || in_array($login_fabrica, [203]) && (base64_decode($_GET['v']) != true || empty($_GET['v']))) { ?> 
			<table class="table table-striped table-bordered" >
				<thead>
					<?php
					if ($areaAdmin === true) {
						$array_interacao_interna = array("interacao_interna", "interacao_transferir", "interacao_sms_consumidor");

						$legenda_mostra_interacao_interna = array_map(function($i) use ($array_interacao_interna) {
							return (in_array($i, $array_interacao_interna)) ? true : false;
						}, $inputs_interacao);

						if (count(array_filter($legenda_mostra_interacao_interna)) > 0) {
						?>
							<tr>
								<td style="background-color: #F2DEDE;" >&nbsp;</td>
								<td><?=traduz('Interação Interna')?></td>
							</tr>
						<?php
						}

						if (in_array("interacao_transferir", $inputs_interacao)) {
						?>
							<tr>
								<td class="tac" ><i class="icon-retweet" ></i></td>
								<td>Transferido</td>
							</tr>
						<?php
						}

						if (in_array("interacao_email", $inputs_interacao)) {
						?>
							<tr>
								<td class="tac" ><i class="icon-envelope" ></i></td>
								<td><?=traduz('Enviou Email para o Posto Autorizado')?></td>
							</tr>
						<?php
						}

						if (in_array("interacao_sms_consumidor", $inputs_interacao)) {
						?>
							<tr>
								<td class="tac" ><i class="glyphicon icon-phone" ></i></td>
								<td>Enviou SMS para o Consumidor</td>
							</tr>
						<?php
						}

						if (in_array("interacao_email_consumidor", $inputs_interacao)) {
						?>
							<tr>
								<td class="tac" nowrap ><i class="icon-envelope" ></i><i class="icon-user" ></i></td>
								<td>Enviou Email para o Consumidor</td>
							</tr>
						<?php
						}

						if (in_array("interacao_atendido", $inputs_interacao)) {
						?>
							<tr>
								<td class="tac" nowrap ><i class="icon-ok" ></i></td>
								<td>Atendido</td>
							</tr>
						<?php
						}
					} else {
						if (in_array("recebeu_email", $posto_legendas)) {
						?>
							<tr>
								<td class="tac" ><i class="icon-envelope" ></i></td>
								<td><?= traduz('Recebeu por Email') ?></td>
							</tr>
						<?php
						}
					}
					?>
					<tr>
						<td class="tac" >
							<button type="button" class="btn btn-warning btn-mini" ><i class="icon-eye-close icon-white" ></i></button>
						</td>
						<td><?= traduz('Leitura Não Confirmada') ?></td>
					</tr>
					<tr>
						<td class="tac" >
							<button type="button" class="btn btn-success btn-mini" ><i class="icon-eye-open icon-white" ></i></button>
						</td>
						<td><?= traduz('Leitura Confirmada') ?></td>
					</tr>
				</thead>
			</table>
			<?php } ?>

			<div class='titulo_tabela '><?= traduz('Histórico de Interações') ?></div>

			<table class="table table-striped table-bordered" >
				<thead>
					<tr class="titulo_coluna" >
						<th>Nº</th>
						<th><?= traduz('Data') ?></th>
						<?php
						if (in_array("interacao_data_contato", $inputs_interacao)) {
						?>
							<th>Data de contato</th>
						<?php
						}
						?>
						<th><?= traduz('Mensagem') ?></th>
						<th><?php echo (in_array($login_fabrica, [11,172])) ? "Destinatário" : "Admin";?></th>
						<th><?= traduz('Leitura') ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ($login_fabrica == 203) {
					    if (base64_decode($_GET['v']) == true) {
					        $login_fabrica       = 167;
					    }
					}


					if ($areaAdmin === false) {
						$whereInteracaoInterna = "AND tbl_os_interacao.interno IS NOT TRUE";
						$whereSms = " and tbl_os_interacao.sms is not true ";
					}

                    $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os_interacao.fabrica IN (11,172) " : " tbl_os_interacao.fabrica = $login_fabrica ";
                    $cond_pesquisa_admin = (in_array($login_fabrica, array(11,172))) ? " tbl_admin.fabrica IN (11,172) " : " tbl_admin.fabrica = $login_fabrica ";

					$sqlInteracoes = "SELECT
										tbl_os_interacao.os_interacao AS id,
										(CASE WHEN tbl_os_interacao.admin IS NULL THEN
											'Posto Autorizado'
										ELSE
											case when length(tbl_admin.nome_completo) = 0 then login else nome_completo end
										END) AS admin,
										TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY HH24:MI') AS data,
										TO_CHAR(tbl_os_interacao.data_contato, 'DD/MM/YYYY') AS data_contato,
										tbl_os_interacao.comentario AS mensagem,
										tbl_os_interacao.interno,
										tbl_os_interacao.posto,
										tbl_os_interacao.sms,
										tbl_os_interacao.exigir_resposta,
										tbl_os_interacao.atendido,
										tbl_os_interacao.programa,
										TO_CHAR(tbl_os_interacao.confirmacao_leitura, 'DD/MM/YYYY HH24:MI') AS confirmacao_leitura
									  FROM tbl_os_interacao
									  LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin AND $cond_pesquisa_admin
									  WHERE {$cond_pesquisa_fabrica}
									  AND tbl_os_interacao.os = {$os}
										$whereSms
									  {$whereInteracaoInterna}
									  ORDER BY tbl_os_interacao.data DESC";
					$resInteracoes = pg_query($con, $sqlInteracoes);

					if (pg_num_rows($resInteracoes) > 0) {
						$i = pg_num_rows($resInteracoes);

						while ($interacao = pg_fetch_object($resInteracoes)) {
						?>

							<tr <?=($interacao->interno == "t" && !empty($interacao->admin)) ? "class='error'" : ""?> >
								<td>
									<?php
									echo $i;

									if ($areaAdmin === true) {
										if (in_array("interacao_email", $inputs_interacao) && $interacao->exigir_resposta == "t" && !in_array("interacao_email_consumidor", $inputs_interacao)) {
											echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
										}

										if (in_array("interacao_transferir", $inputs_interacao) && preg_match("/^transferido para o admin/", strtolower($interacao->mensagem))) {
											echo "&nbsp;<i class='icon-retweet pull-right' ></i>";
										}

										if (in_array("interacao_sms_consumidor", $inputs_interacao)) {
											if ($interacao->sms == "t") {
												echo "&nbsp;<i class='glyphicon icon-phone pull-right' ></i>";
											}
										}

										if (in_array("interacao_email_consumidor", $inputs_interacao)) {
											if ($interacao->interno == "t" && $interacao->exigir_resposta == "t" && preg_match("/^enviou email para o consumidor/", strtolower($interacao->mensagem))) {
												echo "&nbsp;<i class='icon-envelope pull-right' ></i><i class='icon-user' ></i>";
											} else if ($interacao->exigir_resposta == "t") {
												echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
											}
										}

										if (in_array("interacao_atendido", $inputs_interacao)) {
											if ($interacao->atendido == "t") {
												echo "&nbsp;<i class='icon-ok pull-right' ></i>";
											}
										}
									} else {
										if (in_array("recebeu_email", $posto_legendas) && $interacao->exigir_resposta == "t" && !in_array("fabrica_envia_email_consumidor", $posto_legendas)) {
											echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
										}

										if (in_array("fabrica_envia_email_consumidor", $posto_legendas)) {
											if (!($interacao->interno == "t" && $interacao->exigir_resposta == "t" && preg_match("/^enviou email para o consumidor/", strtolower($interacao->mensagem))) && $interacao->exigir_resposta == "t") {
												echo "&nbsp;<i class='icon-envelope pull-right' ></i>";
											}
										}
									}
									?>
								</td>
								<td class="tac" ><?=$interacao->data?></td>
								<?php
								if (in_array("interacao_data_contato", $inputs_interacao)) {
								?>
									<td class="tac" ><?=$interacao->data_contato?></td>
								<?php
								}
								$msg = wordwrap( $interacao->mensagem, 50, "\n", 1);
								$msg = mb_detect_encoding($msg, 'UTF-8', true) ? utf8_decode($msg) : $msg;
								?>
								<td><?=$msg?></td>
								<td><?=$interacao->admin?></td>
								<td class="tac" >
									<?php
									if (!empty($interacao->confirmacao_leitura)) {
									?>
										<button type="button" class="btn-leitura btn btn-success btn-mini" title="Leitura confirmada em <?=$interacao->confirmacao_leitura?>" disabled ><i class="icon-eye-open icon-white" ></i></button>
									<?php
									} else if ($areaAdmin === true && strtolower($interacao->admin) == "posto autorizado") {
									?>
										<button type="button" class="btn-leitura btn btn-warning btn-mini" data-id="<?=$interacao->id?>" title="Confirmar leitura" ><i class="icon-eye-close icon-white" ></i></button>
									<?php
									} else if ($areaAdmin === false && strtolower($interacao->admin) != "posto autorizado" && $interacao->programa != "/assist/interacao_os.php") {
									?>
										<button type="button" class="btn-leitura btn btn-warning btn-mini" data-id="<?=$interacao->id?>" title="Confirmar leitura" ><i class="icon-eye-close icon-white" ></i></button>
									<?php
									}else if($areaAdmin === true and $telecontrol_distrib){

										if ( !(in_array("interacao_transferir", $inputs_interacao) && preg_match("/^transferido para o admin/", strtolower($interacao->mensagem))) ){?>
											<button type="button" class="btn-leitura btn btn-warning btn-mini" data-id="<?=$interacao->id?>" title="Confirmar leitura" ><i class="icon-eye-close icon-white" ></i></button>
												
										<?php }	?>										
									<?php
									}
									?>
								</td>
							</tr>

							<?php
							$i--;
						}
						if (in_array($login_fabrica, [152,180,181,182])) { 
							$pendInt = ($formulario_interacao == false) ? "nao" : "sim";
						?>
							<input type="hidden" name="pendenteInt" id="pendInt" value="<?=$pendInt?>" />
						<?php
						}
					}

					if ($login_fabrica == 167) {
					    if (base64_decode($_GET['v']) == true) {
					        $login_fabrica       = 203;
					    }
					}
					?>
				</tbody>
			</table>
		<?php
		}
		?>
	</div>
</body>
</html>
