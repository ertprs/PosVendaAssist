<?
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';

if ($areaAdmin === true) {
	include __DIR__.'/admin/autentica_admin.php';
} else {
	include __DIR__.'/autentica_usuario.php';
}

include __DIR__.'/funcoes.php';

include_once __DIR__."/regras.php";

if (file_exists(__DIR__."/{$login_fabrica}/regras.php")) {
	include_once __DIR__."/{$login_fabrica}/regras.php";
}
include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("helpdesk_pa", $login_fabrica);
$tDocs   = new TDocs($con, $login_fabrica);


if ($fabricaFileUploadOS) {
    if (!empty($hd_chamado_item)) {
        $tempUniqueId = $hd_chamado_item;
        $anexoNoHash = null;
    } else if (strlen(getValue("anexo_chave")) > 0) {
        $tempUniqueId = getValue("anexo_chave");
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

if (isset($_POST["ajax_aprovar_fora_garantia"])) {

	$os = $_POST["os"];

	$sqlUpdateCheckpoint = "UPDATE tbl_os 
							SET status_checkpoint = 1
							WHERE os = {$os}";
	pg_query($con, $sqlUpdateCheckpoint);

	$sqlInsereStatus = "INSERT INTO tbl_os_status (os, status_os, observacao, fabrica_status, admin) 
                		VALUES ({$os}, 257, 'OS Aprovada pelo helpdesk', {$login_fabrica}, {$login_admin})";
	pg_query($con, $sqlInsereStatus);

	$retorno = [
		"success" => false
	];
	if (!pg_last_error()) {
		$retorno = [
			"success" => true
		];
	}

	exit(json_encode($retorno));
}

if (isset($_POST['ajax_anexo_upload'])) {
    $chave   = $_POST['anexo_chave'];

    $arquivo = $_FILES["anexo_upload"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($arquivo['tmp_name']) > 0) {
        if (!in_array($ext, array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx'))) {
            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx'));
        } else {

			// Se enviou um outro arquivo, este substitui o anterior
			if ($_FILES['anexo_upload']['tmp_name']) {

				$anexoID = $tDocs->sendFile($_FILES['anexo_upload']);
				$arquivo_nome      = json_encode($tDocs->sentData);

				if (!$anexoID) {
					$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
				} else {
					// Se ocorrer algum erro, o anexo está salvo:
					if (isset($idExcluir)) {
						$tDocs->deleteFileById($idExcluir);
					}
				}
			}

			if (empty($anexoID)) {
				$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
			}

			if ($ext == 'pdf') {
				$link = 'imagens/pdf_icone.png';
			} else if(in_array($ext, array('doc', 'docx'))) {
				$link = 'imagens/docx_icone.png';
			} else {
				$link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;

			}

			$href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;

			if (!strlen($link)) {
				$retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
			} else {
				$retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao');
			}
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
    }

    exit(json_encode($retorno));
}

include "helpdesk_posto_autorizado/helpdesk.php";

if ($areaAdmin === true) {
	$layout_menu = "callcenter";
} else {
	$layout_menu = "os";
}

$title = (in_array($login_fabrica, [198])) ? "Help-desk Interno" : "Help-desk do Posto Autorizado";

if ($areaAdmin === true) {
	include __DIR__.'/admin/cabecalho_new.php';
} else {
	include __DIR__.'/cabecalho_new.php';
}

if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
}

$plugins = array(
    "datepicker",
    "maskedinput",
	"ckeditor",
	"ajaxform",
	"fancyzoom",
	"shadowbox"
);

include __DIR__.'/admin/plugin_loader.php';
?>

<style>

#cke_nova_interacao {
	margin: 0 auto;
}

label.control-label {
	font-weight: bold;
	font-size: 13px;
}

div.row-not-visible, div.col-not-visible {
	display: none !important;
}

td.warning {
	background-color: #FCF8E3 !important;
}

td.info {
	background-color: #D9EDF7 !important;
}

td.success {
	background-color: #DFF0D8 !important;
}

td.error {
	background-color: #F2DEDE !important;
}

</style>
<script type='text/javascript' src='admin/js/FancyZoom.js'></script>
<script type='text/javascript' src='admin/js/FancyZoomHTML.js'></script>

<script>

$(function() {

	$("#aprovar_fora_garantia").click(function(){

		let os = $(this).attr("os");console.log(os);

		$.ajax({
            url: window.location,
            type: 'POST',
            data: {
                ajax_aprovar_fora_garantia: true,
                os: os
            },
            dataType: "json"
        }).fail(function (data) {

            alert("<?php echo traduz("Falha ao gravar informações."); ?>");

        }).done(function (data) {

        	if (data.success) {

        		$("#aprovar_fora_garantia").remove();
        		alert("OS aprovada");

        	}
            
        });

	});

	<?php if ($fabricaFileUploadOS) { ?>

		$(".visualizar_anexo").click(function () {

			var hd_chamado_item = $(this).data("hd-chamado-item");
			let url = "exibe_anexos_chamado_item_boxuploader.php?item=";
			
			url = url + hd_chamado_item;
		
			Shadowbox.init();

			Shadowbox.open({
		        content: url,
		            player: "iframe",
		            height: 1050,
		            width: 2050,
		    });
		});

	<?php } ?>

	$("#data_providencia").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");

	CKEDITOR.replace("nova_interacao", { enterMode: CKEDITOR.ENTER_BR });

	setupZoom();

	$("input[type=submit]").on("click", function() {
		$(this).button("loading");
	});

	$("input[name=tipo_interacao]").click(function() {
		if ($(this).val() == "Transferir") {
			$("#transferir_hd_input").show();
		} else {
			$("#transferir_hd_input").hide();
		}
	});

	//Anexo
	$("#anexar_i").click(function() {
		$("input[name=anexo_upload]").click();
	});

	$("input[name=anexo_upload]").change(function() {
		$("#anexar_i").button("loading");

		$(this).parent("form").submit();
    });

    $("form[name=form_anexo]").ajaxForm({
        complete: function(data) {
        	data = JSON.parse(data.responseText);

			if (data.error) {
				alert(data.error);
			} else {
				var imagem = $("#div_anexo_i").find("img.anexo_thumb").clone();

				$(imagem).attr({ src: data.link });

				$("#div_anexo_i").find("img.anexo_thumb").remove();

				var link = $("<a></a>", {
					href: data.href,
					target: "_blank"
				});

				$(link).html(imagem);

				$("#div_anexo_i").prepend(link);

				if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
					setupZoom();
				}

		        $("#div_anexo_i").find("input[name=anexo_i]").val(data.arquivo_nome);
			}

			$("#anexar_i").button("reset");
    	}
    });
    //fim anexo
});

</script>

<?
if ($atendimento_nao_encontrado == true) {
?>
	<br />
	<div class="alert alert-error"><h4>Atendimento não encontrado</h4></div>
<?
	exit;
}

if (count($msg_erro["msg"]) > 0) {
?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg_erro["msg"])?></h4></div>
<? } ?>

<form name="frm_novo_atendimento" method="POST" class="form-search form-inline" enctype="multipart/form-data" >

	<div class="tc_formulario" >
		<div class="titulo_tabela">Informações do Atendimento</div>

		<br />

		<div class="row-fluid" >

			<div class="span1"></div>

			<div class="span4">
				<div class='control-group' >
					<div class="controls controls-row">
						<div class="span12">
							<label class='label label-warning' style="font-size: 16px; padding-left: 10px; padding-right: 10px;" >Nº Help-Desk <?=$hd_chamado?></label>
						</div>
					</div>
				</div>
			</div>

		</div>


		<? if ($areaAdmin === true && !in_array($login_fabrica, [169,170,198])) {

			if (in_array($login_fabrica, [151]) && getValue("status") != "Cancelado") {

				$sqlVerificaAprovacao = "SELECT tbl_hd_chamado_extra.os
										 FROM tbl_hd_chamado_extra
										 JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
										 JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os
										 WHERE tbl_hd_chamado_extra.hd_chamado = {$hd_chamado}
										 AND (
										 	SELECT status_os
										 	FROM tbl_os_status
										 	WHERE status_os = 256
										 	AND tbl_os_status.os = tbl_hd_chamado_extra.os
										 	LIMIT 1
										 ) IS NOT NULL
										 AND (
										 	SELECT status_os
										 	FROM tbl_os_status
										 	WHERE status_os = 257
										 	AND tbl_os_status.os = tbl_hd_chamado_extra.os
										 	LIMIT 1
										 ) IS NULL";
				$resVerificaAprovacao = pg_query($con, $sqlVerificaAprovacao);

				if (pg_num_rows($resVerificaAprovacao) > 0) { 

					$os_chamado = pg_fetch_result($resVerificaAprovacao, 0, "os");

					?>
					<div class="row-fluid">
						<div class="span4"></div>
						<div class="span4">
							<div class='control-group'> &nbsp; &nbsp; 
								<button type="button" class="btn btn-primary" id="aprovar_fora_garantia" os="<?= $os_chamado ?>">
									Aprovar OS Fora de Garantia
								</button>
							</div>
						</div>
					</div>

				<?php
				}
				
			}
			?>
			<div class="row-fluid" >

				<div class="span1"></div>

				<div class="span4">
					<div class='control-group' >
						<label class="control-label">Código do Posto Autorizado</label>
						<div class="controls controls-row">
							<div class="span12">
								<?=getValue("posto_codigo")?>
							</div>
						</div>
					</div>
				</div>

				<div class="span4">
					<div class='control-group' >
						<label class="control-label">Nome do Posto Autorizado</label>
						<div class="controls controls-row">
							<div class="span12">
								<?=getValue("posto_nome")?>
							</div>
						</div>
					</div>
				</div>

			</div>
		<?php } ?>

		<div class="row-fluid" >

			<div class="span1"></div>

			<?php if (!in_array($login_fabrica, [198])) { ?> 
			<div class="span4" >
				<div class="control-group" >
					<label class="control-label" >Responsável pela Solicitação</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<?=getValue('responsavel_solicitacao')?>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>

			<div class="span4">
				<div class='control-group' >
					<label class="control-label" >Tipo de Solicitação</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<?=getValue('tipo_solicitacao')?>
						</div>
					</div>
				</div>
			</div>
			<?php
			if (!in_array($login_fabrica, [169,170])) { ?>
				<div class="span3" >
					<div class="control-group" >
						<label class="control-label" >Produto em Garantia</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<?=(getValue('produto_garantia') == "t") ? "Sim" : "Não"?>
							</div>
						</div>
					</div>
				</div>
			<?php
			} else { ?>
				<div class="span3" >
					<div class="control-group" >
						<label class="control-label" >Providência</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<?= $arrProvidencia[getValue("providencia")] ?>
							</div>
						</div>
					</div>
				</div>
			<?php
			}
			?>
		</div>
			<?php
			if (in_array($login_fabrica, [169,170])) { ?>
				<div class="row-fluid" >
					<div class="span1"></div>
					<div class="span4" >
						<div class="control-group" >
							<label class="control-label" >Sub-item</label>
							<div class="controls controls-row" >
								<div class="span12" >
									<?= $arrSubItem[getValue("sub_item")] ?>
								</div>
							</div>
						</div>
					</div>
					<div class="span3" >
						<div class="control-group" >
							<label class="control-label" >Origem</label>
							<div class="controls controls-row" >
								<div class="span12" >
									<?= getValue("origem") ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			<?php
			}
			?>

		<?
		$arr = array_filter(array("ordem_de_servico", "pedido", "protocolo_atendimento", "cod_localizador", "ticket_atendimento", "pre_logistica"), function($campo) use($informacoes_adicionais){
			return (in_array($campo, $informacoes_adicionais));
		});

		$row_class = (!count($arr)) ? "row-not-visible" : "";
		?>
		<div class="row-fluid <?=$row_class?>" >
			<div class="span1" ></div>

			<div class="span3 <?=(!in_array('ordem_de_servico', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>" >
				<div class="control-group" >
					<label class="control-label" >Ordem de Serviço</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<?php echo (strlen(getValue("sua_os")) > 0) ? getValue("sua_os") : getValue("ordem_de_servico")?>
						</div>
					</div>
				</div>
			</div>

			<div class="span3 <?=(!in_array('pedido', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>" >
				<div class="control-group" >
					<label class="control-label" >Pedido</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<?=getValue("pedido")?>
						</div>
					</div>
				</div>
			</div>

			<div class="span3 <?=(!in_array('protocolo_atendimento', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>" >
				<div class="control-group" >
					<label class="control-label" >Protocolo de Atendimento</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<?=getValue("protocolo_atendimento")?>
						</div>
					</div>
				</div>
			</div>
			<? if($login_fabrica == 35) {?>
				<div class="span3 <?=(!in_array('ticket_atendimento', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>" >
					<div class="control-group" >
						<label class="control-label" >Ticket Atendimento</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<?php echo $informacoes_adicionais['ticket_atendimento']?>
							</div>
						</div>
					</div>
				</div>
				<div class="span3 <?= (!in_array('cod_localizador', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>" >
					<div class="control-group" >
						<label class="control-label" >Código Localizador</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<?php echo $informacoes_adicionais['cod_localizador']?>
							</div>
						</div>
					</div>
				</div>
				<div class="span3 <?=(!in_array('pre_logistica', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>" >
					<div class="control-group" >
						<label class="control-label" >Pre-Logistica</label>
						<div class="controls controls-row" >
							<div class="span12" >
								<?php echo $informacoes_adicionais['pre_logistica']?>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>

		<?
		$arr = array_filter(array("cliente", "produto"), function($campo) use($informacoes_adicionais){
			return (in_array($campo, $informacoes_adicionais));
		});

		$row_class = (!count($arr)) ? "row-not-visible" : "";
		?>
		<div class="row-fluid <?=$row_class?>" >
			<div name="margin" class="span1" ></div>

			<div class="span4 <?=(!in_array('produto', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>">
				<div class="control-group" >
					<label class="control-label" >Produto</label>
					<div class="controls controls-row" >
						<div class="span12">
							<?=getValue("produto")?>
						</div>
					</div>
				</div>
			</div>

			<div class="span4 <?=(!in_array('cliente', $informacoes_adicionais)) ? 'col-not-visible' : '' ?>" >
				<div class="control-group" >
					<label class="control-label">Cliente</label>
					<div class="controls controls-row" >
						<div class="span12" >
							<?=getValue("cliente")?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?
		$arr = array_filter(array("pecas"), function($campo) use($informacoes_adicionais){
			return (in_array($campo, $informacoes_adicionais));
		});

		$row_class = (!count($arr)) ? "row-not-visible" : "";
		?>

		<div class="row-fluid <?=$row_class?>" >
			<div name="margin" class="span1" ></div>

			<div class="span8" >
				<div class="control-group" >
				<label class="control-label"><? echo ($login_fabrica == 35) ? "Peças = Qtde" : "Peças" ;?> </label>
					<div class="controls controls-row" >
						<div class="span12">
							<ul>
								<?
								if($login_fabrica == 35){
									$pecas = explode(",", getValue("pecas"));
									foreach ($pecas as $peca) {
										$campos = explode("=", $peca);
										if (strlen($peca) > 0){
											$sqlPeca = "SELECT referencia || ' - ' || descricao AS peca
													FROM tbl_peca
													WHERE fabrica = {$login_fabrica}
													AND peca = {$campos[0]}";
											$resPeca = pg_query($con, $sqlPeca);

											$peca = pg_fetch_result($resPeca, 0, "peca");
										}

										echo "<li>{$peca}  = {$campos[1]}</li>";
									}	
								}else{
									$sep = (strpos(getValue("pecas"), "pecas")!== false) ? "pecas" : "," ;
									$pecas = explode($sep , getValue("pecas"));
									foreach ($pecas as $peca) {
										if (strlen($peca) > 0){
											$sqlPeca = "SELECT referencia || ' - ' || descricao AS peca
													FROM tbl_peca
													WHERE fabrica = {$login_fabrica}
													AND peca = {$peca}";
											$resPeca = pg_query($con, $sqlPeca);

											$peca = pg_fetch_result($resPeca, 0, "peca");
										}

										echo "<li>{$peca}</li>";
									}
								}									
								?>
							</ul>
						</div>
					</div>
				</div>
			</div>

		</div>

		<?
		$anexos = $s3->getObjectList("$hd_chamado.");
		if (count($anexos) > 0) {
			foreach ($attCfg['labels'] as $attLabel) {
				$etiquetas[] = clear_att_fname($attLabel);
			}
		?>
			<div class="row-fluid" >
				<div name="margin" class="span1" ></div>

			<?php foreach ($anexos as $i => $anexo):
				$j=0;
				while (strpos($anexo, $etiquetas[$j++])===false and $j<count($etiquetas));
				// pecho("\$J = $j | LABEL: " . $attCfg['labels'][$j-1]);
				$anexo_label = ($j<=count($etiquetas)) ? $attCfg['labels'][$j-1] : 'Anexo';
?>
				<div class="span2" >
					<div class="control-group" >
					<label class="control-label"><?=$anexo_label?></label>
						<div class="controls controls-row" >
							<div class="span12">
								<?
								$ext = strtolower(preg_replace("/.+\./", "", basename($anexo)));

								if ($ext == "pdf") {
									$anexo_imagem = "imagens/pdf_icone.png";
								} else if (in_array($ext, array("doc", "docx"))) {
									$anexo_imagem = "imagens/docx_icone.png";
								} else {
									$anexo_imagem = $s3->getLink("thumb_".basename($anexo));
								}

								$anexo_link = $s3->getLink(basename($anexo));
								?>
								<a href="<?=$anexo_link?>" target="_blank" >
									<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
								</a>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>

			</div>
		<? } ?>

		<br />

		<? if (getValue("status") == "Cancelado") { ?>
			<div class="alert alert-danger" ><strong>ATENDIMENTO CANCELADO</strong></div>
		<? } else if (getValue("status") == "Finalizado") { ?>
			<div class="alert alert-success" ><strong>ATENDIMENTO FINALIZADO</strong></div>
		<? } else { ?>
			<div class="titulo_tabela" >Interagir</div>

			<? if ($areaAdmin === true) { ?>
				<br />

				<div class="row-fluid" >
					<div class="span12 tac">
						<?php
						if (!in_array($login_fabrica, [169,170])) {
						?>
							<?php if (!in_array($login_fabrica, [198])) { ?>
								<label class="radio" >
									Ag. Posto
									<input type="radio" name="tipo_interacao" checked value="Ag. Posto" />
								</label>
							 &nbsp; 
							<?php } ?>
							<label class="radio" >
								Resposta Conclusiva
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Resposta Conclusiva") ? "checked" : ""?> value="Resposta Conclusiva" />
							</label>
							 &nbsp; 
							<label class="radio" >
								Interação Interna
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Interação Interna") ? "checked" : ""?> value="Interação Interna" />
							</label>
							 &nbsp; 
							<label class="radio" >
								Cancelar Atendimento
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Cancelado") ? "checked" : ""?> value="Cancelado" />
							</label>
							 &nbsp; 
							<label class="radio" >
								Transferir
								<input type="radio" name="tipo_interacao" <?= (getValue("tipo_interacao") == "Transferir") ? "checked" : ""; ?> value="Transferir" />
							</label>
							<?php
							if (getValue("status") == "Ag. Conclusão" and !in_array($login_fabrica, array(30, 160)) ) {
							?>
								<label class="radio" >
									Finalizar Atendimento
									<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Finalizado") ? "checked" : ""?> value="Finalizado" />
								</label>
							<? } ?>
							<label class="radio" >
								Finalizar Atendimento
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Finalizado") ? "checked" : ""?> value="Finalizado" />
							</label>
						<?php
						} else { ?>
							<label class="radio" >
								Transferir
								<input type="radio" name="tipo_interacao" <?= (getValue("tipo_interacao") == "Transferir") ? "checked" : ""; ?> value="Transferir" />
							</label>
							 &nbsp; 
							<label class="radio" >
								Call Center
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Call Center") ? "checked" : ""?> value="Call Center" />
							</label>
							 &nbsp; 
							<label class="radio" >
								Eng. Serviços
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Eng. Servicos") ? "checked" : ""?> value="Eng. Servicos" />
							</label>
							 &nbsp; 
							<label class="radio" >
								Cancelar Atendimento
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Cancelado") ? "checked" : ""?> value="Cancelado" />
							</label>
							 &nbsp; 
							<label class="radio" >
								Finalizar Atendimento
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Finalizado") ? "checked" : ""?> value="Finalizado" />
							</label>
						<?php
						}
						?>
					</div>
				</div>
                <?php if (in_array($login_fabrica, array(30,72))): ?>
				<div class="row-fluid" >
					<div class="span1"></div>
                    <div class="span2">
                        <div class='control-group <?=(in_array('data_providencia', $msg_erro['campos'])) ? "error" : "" ?>' >
                            <label class="control-label" for="data_providencia">Data do Retorno</label>
                            <div class="controls controls-row">
                                <div class="span12">
                                    <input id="data_providencia" name="data_providencia" class="span12" rel="data" type="text" value="<?=getValue('data_providencia')?>" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif ?>
				<div class="row-fluid" >
					<div class="span2"></div>
					<div id="transferir_hd_input" class="span8"<?= (getValue("tipo_interacao") == "Transferir") ? "" : " style='display:none;'"; ?>>
						<div class='control-group'>
							<label class="control-label" for="admin_disp">Para:</label>
							<div class="controls controls-row">
								<div class="span6">
									<h5 class='asteristico'>*</h5>
									<select id="admin_disp" name="admin_disp">
										<option value="">Escolha</option>
										<?
										$adminsDisponiveis = selectAdminsDisponiveis();
										foreach ($adminsDisponiveis as $admin) {
										?>
											<option value="<?= $admin['admin']; ?>"><?= $admin['nome_completo']; ?></option>
										<? } ?>
									</select>
								</div>
							</div>
						</div>
					</div>
					<div class="span2"></div>
				</div>
			<? } else { ?>
				<br />

				<? if (getValue("status") == "Ag. Conclusão") { ?>
					<div class="alert alert-success" >
						<strong>Se você concorda com a solução dada pela fábrica selecione "Concordo com a Solução". Se não concorda, faça uma nova interação para a fábrica respondendo o atendimento.</strong>
					</div>
				<? } ?>

				<div class="row-fluid" >
					<div class="span12 tac" >
						<label class="radio" >
							Ag. Fábrica
							<input type="radio" name="tipo_interacao" checked value="Ag. Fábrica" />
						</label>
						 &nbsp;
						<? if ($login_fabrica != 35 or ($login_fabrica == 35 and !getValue('admin_abre'))) { ?> 
							<label class="radio" >
								Cancelar Atendimento 
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Cancelado") ? "checked" : ""?> value="Cancelado" />
							</label>
						<? } ?>
						 &nbsp; 
						<?
						if (getValue("status") == "Ag. Conclusão" and $login_fabrica != 30 ) {
						?>
							<label class="radio" >
								Finalizar Atendimento
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Finalizado") ? "checked" : ""?> value="Finalizado" />
							</label>
						<? }

						if (getValue("status") == "Ag. Conclusão" ) {
						?>
							<label class="radio" >
								Concordo com a Solução
								<input type="radio" name="tipo_interacao" <?=(getValue("tipo_interacao") == "Finalizado") ? "checked" : ""?> value="Ag. Finalização" />
							</label>
						<? } ?>
					</div>
				</div>
			<? } ?>

			<div class="row-fluid" >
				<div class="span12 tac" >
					<textarea class="span10" id="nova_interacao" name="nova_interacao" ><?=getValue("nova_interacao")?></textarea>
				</div>
			</div>
			<br />
	        <?php 

	        if ($fabricaFileUploadOS) {

	            $boxUploader = array(
	                "div_id" => "div_anexos",
	                "prepend" => $anexo_prepend,
	                "context" => "help desk",
	                "unique_id" => $tempUniqueId,
	                "hash_temp" => $anexoNoHash,
	                "reference_id" => $tempUniqueId
	            );

            	include "box_uploader.php";

        	} else {
	            ?>
				<div class="titulo_tabela" >Anexo</div>

				<br />

				<div class="tac" >
					<?php
					$anexo_imagem_i = "imagens/imagem_upload.png";
					$anexo_i        = "";
					
					?>
					<div id="div_anexo_i" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top;" >
						<?php
						if (isset($anexo_link_i)) {
						?>
							<a href="<?=$anexo_link_i?>" target="_blank" >
								<img src="<?=$anexo_imagem_i?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
							</a>

	 					<script>setupZoom();</script> 
						<?php
						} else {
						?>
							<img src="<?=$anexo_imagem_i?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
						<?php
						}
						?>

						<button type="button" class="btn btn-mini btn-primary btn-block" id="anexar_i" data-loading-text="Anexando..." >Anexar</button>

						<input type="hidden" name="anexo_i" value="<?=$anexo_i?>" />
						<input type="hidden" name="anexo_chave" value="<?=$anexoNoHash?>">
					</div>
				</div>
			<?php } ?>
			<br>

			<p class="tac">
				<input type="submit" class="btn btn-default" name="gravar_interacao" value="Gravar Interação" data-loading-text="Gravando Interação..." />
			</p>

			<br />
		<? } ?>

		<div class="titulo_tabela">Histórico de Interações</div>

		<br />

		<table class="table table-bordered table-striped" style="table-layout: fixed; width:600px; margin: 0 auto; border-collapse: collapse;"  >
			<tbody>
				<tr>
					<? 
					if ($areaAdmin === true) { ?>
						<td class="tac warning" style="border: 1px solid #fff;" >Interação Interna</td>
					<? 
					} 

					if (!in_array($login_fabrica, [169,170])) { ?>
						<td class="tac info" style="border: 1px solid #fff;" >Resposta Conclusiva</td>
					<?php
					} ?>
					<td class="tac success" style="border: 1px solid #fff;" >Finalizado</td>
					<td class="tac error" style="border: 1px solid #fff;" >Cancelado</td>
				</tr>
			</tbody>
		</table>

		<br />

		<table class="table table-bordered table-striped" >
			<thead>
				<tr class="titulo_coluna" >
					<th>#</th>
					<th>Status</th>
					<th>Mensagem</th>
					<th>Admin</th>
					<th>Data</th>
					<th>Anexo</th>
				</tr>
			</thead>
			<tbody>
				<? $sqlInteracoes = "SELECT
						tbl_admin.nome_completo AS admin,
						tbl_hd_chamado_item.posto,
						tbl_hd_chamado_item.comentario,
						tbl_hd_chamado_item.interno,
						tbl_hd_chamado_item.hd_chamado_item,
						tbl_hd_chamado_item.status_item,
						tbl_hd_chamado_item.hd_chamado,
						TO_CHAR(tbl_hd_chamado_item.data, 'DD/MM/YYYY HH24:MI') AS data
					FROM tbl_hd_chamado_item
					LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin AND tbl_admin.fabrica = {$login_fabrica}
					WHERE tbl_hd_chamado_item.hd_chamado = {$hd_chamado}
					ORDER BY tbl_hd_chamado_item.data DESC";
				$resInteracoes = pg_query($con, $sqlInteracoes);

				$i = pg_num_rows($resInteracoes);

				while($interacao = pg_fetch_object($resInteracoes)) {
					if ($areaAdmin === false && $interacao->interno == "t") {
						$i--;
						continue;
					}

					$class_tr = "";

					switch ($interacao->status_item) {
						case 'Interação Interna':
							$class_tr = "warning";
							break;

						case 'Resposta Conclusiva':
							$class_tr = "info";
							break;

						case 'Finalizado':
							$class_tr = "success";
							break;

						case 'Cancelado':
							$class_tr = "error";
							break;
					}

					?>
					<tr class="<?=$class_tr?>" >
						<td><?=$i?></td>
						<td><?=$interacao->status_item?></td>
						<td><?=$interacao->comentario?></td>
						<td><?=(!empty($interacao->posto)) ? "Posto Autorizado" : $interacao->admin ?></td>
						<td><?=$interacao->data?></td>
						<td style="text-align: center;">
						<?
							if ($fabricaFileUploadOS) { 
								unset($temAnexo);
								unset($temAnexoHd);
								$sqlTemAnexo = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND contexto = 'help desk' AND referencia_id = ".$interacao->hd_chamado_item;
								$resTemAnexo = pg_query($con, $sqlTemAnexo);
								if (pg_num_rows($resTemAnexo) > 0) {
									$temAnexo = pg_fetch_all($resTemAnexo);
								}

								if (count($temAnexo) > 0) {
						?>
									<button  data-hd-chamado-item="<?=$interacao->hd_chamado_item;?>" class="btn btn-info visualizar_anexo" type="button">Visualizar Anexos</button>
						<?php
								} else {

									$sqlTemAnexoHd = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND contexto = 'help desk' AND referencia_id = ".$interacao->hd_chamado;
									$resTemAnexoHd = pg_query($con, $sqlTemAnexoHd);
									if (pg_num_rows($resTemAnexoHd) > 0) {
										$temAnexoHd = pg_fetch_all($resTemAnexoHd);
									}

									if (count($temAnexoHd) > 0) {
									?>
										<button  data-hd-chamado-item="<?=$interacao->hd_chamado;?>" class="btn btn-info visualizar_anexo" type="button">Visualizar Anexos</button>
									<?php
									}
								}
							
							} else {

								$idAnexo = '';
								$anexo_item = $s3->getObjectList("{$hd_chamado}_{$interacao->hd_chamado_item}.");
								if (count($anexo_item) > 0) {
								?>
									<!-- <div class="row-fluid" >
										<div name="margin" class="span1" ></div>

										<div class="span4" >
											<div class="control-group" >
												<label class="control-label"></label>
												<div class="controls controls-row" >
													<div class="span12"> -->
														<?
														$ext_item = strtolower(preg_replace("/.+\./", "", basename($anexo_item[0])));

														if ($ext_item == "pdf") {
															$anexo_item_imagem = "imagens/pdf_icone.png";
														} else if (in_array($ext_item, array("doc", "docx"))) {
															$anexo_item_imagem = "imagens/docx_icone.png";
														} else {
															$anexo_item_imagem = $s3->getLink("thumb_".basename($anexo_item[0]));
														}

														$anexo_item_link = $s3->getLink(basename($anexo_item[0]));
														?>
														<a href="<?=$anexo_item_link?>" target="_blank" >
															<img src="<?=$anexo_item_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
														</a>
													<!-- </div>
												</div>
											</div>
										</div>

									</div> -->
								<? } else {

										$idAnexo = $tDocs->getDocumentsByRef($interacao->hd_chamado_item,'hdpostoitem')->attachListInfo;
										if (empty($idAnexo)) {
											$idAnexo = $tDocs->getDocumentsByRef($hd_chamado,'hdposto')->attachListInfo;
										}
										foreach($idAnexo as $anexo) {
											$ext_item = pathinfo($anexo['filename'], PATHINFO_EXTENSION);

											if ($ext_item == "pdf") {
												$anexo_item_imagem = "imagens/pdf_icone.png";
											} else if (in_array($ext_item, array("doc", "docx"))) {
												$anexo_item_imagem = "imagens/docx_icone.png";
											} else {
												$anexo_item_imagem = $anexo['link'];
											}

											$anexo_item_link = $anexo['link'];

											if (!empty($anexo_item_link)) {
												echo '
													<a href="'.$anexo_item_link.'" target="_blank" >
													<img src="'.$anexo_item_imagem.'" class="anexo_thumb" style="width: 100px; height: 90px;" />
													</a>
													';
											}
										}
									} 
								} ?>
						</td>
					</tr>
				<?
					$i--;
				}
				?>
			</tbody>
		</table>

		<br />

	</div>

</form>

<form name="form_anexo" method="post" action="helpdesk_posto_autorizado_novo_atendimento.php" enctype="multipart/form-data" style="display: none;" >
	<input type="file" name="anexo_upload" value="" />
	<input type="hidden" name="ajax_anexo_upload" value="t" />
	<input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
</form>
<?

include "rodape.php";

?>
