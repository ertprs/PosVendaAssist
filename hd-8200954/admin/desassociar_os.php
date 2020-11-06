<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$os         = $_GET["os"];
$hd_chamado = $_GET["hd_chamado"];
$posto      = $_GET["posto"];

if (empty($os)) {
	echo json_encode(array("erro" => utf8_encode("OS não encontrada")));
	exit;
} else {
	$sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
	$res = pg_query($con, $sql);

	$sua_os = pg_fetch_result($res, 0, "sua_os");
}

if (empty($hd_chamado)) {
	echo json_encode(array("erro" => utf8_encode("Atendimento não encontrado")));
	exit;
}

if (empty($posto)) {
	echo json_encode(array("erro" => utf8_encode("Posto não encontrado")));
	exit;
}
?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<script>
	$(function () {
		$("button[name=btn_pergunta_1]").click(function () {
			var acao = $(this).val();

			$("#acao").val(acao);
			$("#pergunta_1").hide();
			$("#motivo").show();
		});

		$("button[name=btn_motivo]").click(function () {
			var motivo = $.trim($("textarea[name=motivo]").val());

			if (motivo.length > 0) {
				var acao       = $("#acao").val();
				var os         = $("#os").val();
				var hd_chamado = $("#hd_chamado").val();
				var posto      = $("#posto").val();

				switch (acao) {
					case "exclui":
						$.ajax({
							url: "desassocia_os_ajax.php",
							type: "POST",
							data: { exclui: true, os: os, hd_chamado: hd_chamado, motivo: motivo, posto: posto },
							beforeSend: function () {
								$("#motivo").hide();
								$("#loading2").show();
							},
							complete: function (data) {
								data = $.parseJSON(data.responseText);

								if (data.nao_exclui) {
									$("#pergunta_2 > h4[name=erro_exclui]").text(data.nao_exclui);
									$("#pergunta_2").show();
								} else {
									$("#ok > h4").text("OS excluida");
									$("#ok").show();
								}

								$("#loading2").hide();
							}
						});
						break;

					case "desassocia":
						$.ajax({
							url: "desassocia_os_ajax.php",
							type: "POST",
							data: { desassocia: true, os: os, hd_chamado: hd_chamado, motivo: motivo, posto: posto },
							beforeSend: function () {
								$("#motivo").hide();
								$("#loading2").show();
							},
							complete: function (data) {
								data = $.parseJSON(data.responseText);

								if (data.erro) {
									alert(data.erro);
									$("#motivo").show();
								} else {
									$("#ok > h4").text("A OS foi desassociada do atendimento");
									$("#ok").show();
								}

								$("#loading2").hide();
							}
						});
						break;
				}
			} else {
				alert("Informe o motivo");
			}
		});

		$("button[name=btn_pergunta_2]").click(function () {
			var motivo     = $.trim($("textarea[name=motivo]").val());
			var acao       = $(this).val();
			var os         = $("#os").val();
			var hd_chamado = $("#hd_chamado").val();
			var posto      = $("#posto").val();

			switch (acao) {
				case "nao_desassocia":
					window.parent.resetaPosto(hd_chamado);
					break;

				case "desassocia":
					$.ajax({
						url: "desassocia_os_ajax.php",
						type: "POST",
						data: { desassocia: true, os: os, hd_chamado: hd_chamado, motivo: motivo, posto: posto },
						beforeSend: function () {
							$("#pergunta_2").hide();
							$("#loading2").show();
						},
						complete: function (data) {
							data = $.parseJSON(data.responseText);

							if (data.erro) {
								alert(data.erro);
								$("#pergunta_2").show();
							} else {
								$("#ok > h4").text("A OS foi desassociada do atendimento");
								$("#ok").show();
							}

							$("#loading2").hide();
						}
					});
					break;
			}
		});

		$(document).on("click", "button[name=fecha_shadowbox_desassociar]", function () {
			window.parent.fechaShadowboxDesassocia();
		});

		$("button[name=cancelar_acao]").click(function () {
			var hd_chamado = $("#hd_chamado").val();
			window.parent.resetaPosto(hd_chamado);
		});
	});
</script>

<body style="background-color: #FFFFFF;">
	<input type="hidden" id="hd_chamado" name="hd_chamado" value="<?=$hd_chamado?>" />
	<input type="hidden" id="os" name="os" value="<?=$os?>" />
	<input type="hidden" id="posto" name="posto" value="<?=$posto?>" />
	<input type="hidden" id="acao" name="acao" value="" />

	<div id="loading2" style="width: 96%; margin: 0 auto; text-align: center; display: none;">
		<img src="imagens/loading_img.gif" />
	</div>

	<div id="pergunta_1" style="width: 96%; margin: 0 auto;" >
    	<h4>Já existe uma OS aberta para o atendimento.</h4>
    	<h4>OS: <span style="color: #B94A48" ><?=$sua_os?></span></h4>

		<div style="text-align: center;">
			<button type="button" class="btn btn-danger" name="btn_pergunta_1" value="exclui" title="Exclui a OS, pode ser visualizada no relatório de OS excluida" >Excluir OS</button>
			<button type="button" class="btn btn-warning" name="btn_pergunta_1" value="desassocia" title="Desassocia o atendimento da OS, possibilitando a abertura de uma nova OS para o atendimento" >Desassociar OS</button>
			<button type="button" class="btn" name="cancelar_acao" title="Cancelar ação" >Cancelar</button>
		</div>
	</div>

	<div id="pergunta_2" style="width: 96%; margin: 0 auto; display: none;" >
    	<h4 style="color: #B94A48" name="erro_exclui"></h4>
    	<h4>Deseja desassociar a OS do atendimento ?</h4>

		<div style="text-align: center;">
			<button type="button" class="btn btn-danger" name="btn_pergunta_2" value="nao_desassocia" title="Não desassociar a OS do atendimento, o posto do atendimento será resetado para o posto anterior" >Não</button>
			<button type="button" class="btn btn-warning" name="btn_pergunta_2" value="desassocia" title="Desassocia o atendimento da OS, possibilitando a abertura de uma nova OS para o atendimento" >Sim</button>
		</div>
	</div>

	<div id="motivo" style="width: 96%; margin: 0 auto; display: none;" >
		<h4>Informe o motivo:</h4>
		<textarea name="motivo" style="width: 100%;" ></textarea>

		<div style="text-align: center;">
			<button type="button" class="btn" name="btn_motivo" >Prosseguir</button>
		</div>
	</div>

	<div id="ok" style="text-align: center; width: 96%; margin: 0 auto; display: none;" >
		<h4 style="color: #468847"></h4>
		<button type="button" class="btn" name="fecha_shadowbox_desassociar" >Fechar</button>
	</div>
</body>
