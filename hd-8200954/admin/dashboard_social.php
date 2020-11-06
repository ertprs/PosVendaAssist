<?php

/**
 *
 * @author Gabriel Tinetti
 *
 **/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center";
include 'autentica_admin.php';

if ($_POST['ajax']) {
	switch ($_POST['ajax']) {
		case 'loadHDCalls':
			$service = ucfirst(trim($_POST['service']));

			$qCalls = "SELECT DISTINCT x.* FROM (
				SELECT
					thc.hd_chamado,
					thc.admin,
					thce.hd_chamado_origem,
					thce.nome AS nome_cliente,
					thco.descricao,
					(SELECT
						to_char(thci.data, 'DD/MM/YYYY HH24:MI')
					FROM tbl_hd_chamado_item thci
					WHERE thc.hd_chamado = thci.hd_chamado
					ORDER BY thci.hd_chamado_item DESC
					LIMIT 1
					) AS data_ultima_interacao,
					(SELECT
						thci.comentario
					FROM tbl_hd_chamado_item thci
					WHERE thc.hd_chamado = thci.hd_chamado
					ORDER BY thci.hd_chamado_item DESC
					LIMIT 1
					) AS ultima_interacao,
					(SELECT
						thci.admin
					FROM tbl_hd_chamado_item thci
					JOIN tbl_hd_chamado_item_externo thcie ON thcie.hd_chamado_item = thci.hd_chamado_item
					WHERE thc.hd_chamado = thci.hd_chamado
					ORDER BY thci.hd_chamado_item DESC
					LIMIT 1
					) AS admin_ultima_interacao
				FROM  tbl_hd_chamado thc
				JOIN  tbl_hd_chamado_extra thce ON thce.hd_chamado = thc.hd_chamado
				JOIN  tbl_hd_chamado_origem thco ON thce.hd_chamado_origem = thco.hd_chamado_origem
				WHERE thc.fabrica = {$login_fabrica}
				AND thc.admin = {$login_admin}
				AND thco.descricao = '{$service}'
				AND thc.status NOT IN ('Resolvido')
				AND thco.fabrica = {$login_fabrica}
				)x
				JOIN tbl_hd_chamado_item_externo thcie ON thcie.hd_chamado = x.hd_chamado
				WHERE thcie.id_integracao IS NOT NULL";
			$rCalls = pg_query($con, $qCalls);

			if (strlen(pg_last_error()) > 0) {
				$response = ['exception' => utf8_encode(pg_last_error())];
				echo json_encode($response);
				break;
			}

			$rCalls = pg_fetch_all($rCalls);
			$response = array_map(function ($r) {
				$r['descricao'] = utf8_encode($r['descricao']);
				$r['ultima_interacao'] = utf8_encode($r['ultima_interacao']);
				return $r;
			}, $rCalls);

			echo json_encode($response);
			break;
		case 'loadHD':
			$hd_chamado = $_POST['callcenter'];
			$qCall = "SELECT DISTINCT x.* FROM (
						SELECT thc.hd_chamado,
							 thc.admin,
							 thce.hd_chamado_origem,
							 thce.nome AS nome_cliente,
							 thco.descricao,
							 (
							 	SELECT
							  		to_char(thci.data, 'DD/MM/YYYY HH24:MI')
							  	FROM tbl_hd_chamado_item thci
							  	WHERE thc.hd_chamado = thci.hd_chamado
							  	ORDER BY thci.hd_chamado_item DESC
							  	LIMIT 1
							 ) AS data_ultima_interacao,
							 (
							  	SELECT
							  		thci.comentario
							  	FROM tbl_hd_chamado_item thci
							  	WHERE thc.hd_chamado = thci.hd_chamado
							  	ORDER BY thci.hd_chamado_item DESC
							  	LIMIT 1
							 ) AS ultima_interacao,
							 (
							 	SELECT
							  		thci.admin
							  	FROM tbl_hd_chamado_item thci
							  	JOIN tbl_hd_chamado_item_externo thcie ON thcie.hd_chamado_item = thci.hd_chamado_item
							  	WHERE thc.hd_chamado = thci.hd_chamado
							  	ORDER BY thci.hd_chamado_item DESC
							  	LIMIT 1
							 ) AS admin_ultima_interacao
						FROM  tbl_hd_chamado thc
						JOIN  tbl_hd_chamado_extra thce ON thce.hd_chamado = thc.hd_chamado
						JOIN  tbl_hd_chamado_origem thco ON thce.hd_chamado_origem = thco.hd_chamado_origem
						WHERE thc.fabrica = {$login_fabrica}
						AND thc.admin = {$login_admin}
						AND thc.status NOT IN ('Resolvido')
						AND thco.fabrica = {$login_fabrica}
						AND thc.hd_chamado = {$hd_chamado}
						)x
						JOIN tbl_hd_chamado_item_externo thcie ON thcie.hd_chamado = x.hd_chamado
						WHERE thcie.id_integracao IS NOT NULL;";
			$rCall 	= 	pg_query($con, $qCall);

			if (pg_num_rows($rCall) == 0) {
				$response = ['error' => utf8_encode('HD not found')];
			} else {
				$rCall = pg_fetch_all($rCall);
				$rCall = array_map(function ($r) {
					$r['nome_cliente'] = utf8_encode($r['nome_cliente']);
					$r['descricao'] = utf8_encode($r['nome_cliente']);
					$r['ultima_interacao'] = utf8_encode($r['ultima_interacao']);
					return $r;
				}, $rCall);

				$response = $rCall[0];
			}

			echo json_encode($response);
			break;
	}

	exit;
}

$layout_menu = "callcenter";
$title = "Dashboard Mídias Sociais";

include "cabecalho_new.php";
$plugins = array("font_awesome");
include("plugin_loader.php");

?>
<script src="plugins/ion-sound/ion.sound.min.js"></script>
<script src="plugins/socket.io-client/dist/socket.io.js"></script>
<style type="text/css">
	* {box-sizing: border-box;}
	p {margin: 0;}

	.dashbody {
		padding: 0 10px;
		border: 1px solid #ddd;
		border-top-color: transparent;
		overflow-y: auto;
		max-height: 600px;
		margin-bottom: 20px
	}

	.dashbody-content {display: none;}
	.fa-check-circle {font-size: 13px;}
	.row-fluid {margin: 0;padding: 0;}
	.row {margin: 0;padding-top: 20px;padding-bottom: 20px;}
	.nav.nav-tabs {margin: 0;}

	.status {color: #596D9B;}

	.hd-item {
		background-color: #596D9B;
		height: 100px;
		max-height: 100px;
		min-height: 100px;
		margin: 3%;
		border-radius: 5px;
		box-shadow: 0 0 10px #CCC;
		transition: box-shadow 1s;
		-webkit-transition: box-shadow 1s;
		display": none;
	}

	.hd-item:hover {
		cursor: pointer;
		box-shadow: 0 0 10px #666;
	}

	.notify-area {
		float: right;
		margin-top: -10px;
		margin-right: -10px;
	}

	.notify-area i {
		font-size: 3em;
	}

	.info-body {
		background-color: #FFF;
		border-top-left-radius: 5px;
		border-top-right-radius: 5px;
		padding": 0px 0px;
	}

	.info-body .callcenter {
		font-size: 15px;
		color: #3D3D3D;
		font-family: Arial;
		line-height: 0.8em;
		text-transform: uppercase;
	}

	.info-body .cliente {
		font-size: 12px;
		color: #3D3D3D;
		font-family: Arial;
		line-height: 0.8em;
		text-transform: uppercase;
	}

	.snippet {
		font-size: 11px;
		color: #3D3D3D;
		font-family: "Arial;
	}

	.last-interaction {
		font-size: 12px;
		color: #FFF;
		font-family: Arial;
		line-height: 1.2em;
		font-weight: 600;
	}
</style>

<div class="tabbable">
	<ul class="nav nav-tabs">
		<li class="<?= $atendimentoFacebook ? "active" : "" ?>">
			<a <?= $atendimentoFacebook ? "href='#facebook-dash' data-toggle='tab'" : "href='#'" ?>">
				<b>Dashboard Facebook</b>
			</a>
		</li>
		<li class="<?= !$atendimentoFacebook && $atendimentoIG ? "active" : "" ?>">
			<a <?= $atendimentoIG ? "href='#instagram-dash' data-toggle='tab'" : "href='#'" ?>">
				<b>Dashboard Instagram</b>
			</a>
		</li>
	</ul>
	<div style="float:right;margin-top:-30px;">
		<small style="font-weight:bold" class="status"><i class="fa fa-spinner fa-spin"></i> Carregando...</small>
	</div>
	<div class="tab-content">
		<div class="tab-pane <?= $atendimentoFacebook ? "active" : "" ?>" id="facebook-dash">
			<div class="dashbody">
				<div class="row dashbody-content"></div>
			</div>
		</div>
		<div class="tab-pane <?= !$atendimentoFacebook && $atendimentoIG ? "active" : "" ?>" id="instagram-dash">
			<div class="dashbody">
				<div class="row dashbody-content"></div>
			</div>
		</div>
	</div>
</div>

<script type="text/html" id="template-atendimentos">
	<div class="span3 hd-item" id="">
		<div class="notify-area">
			<i class="fas"></i>
		</div>
		<div class="row-fluid info-body">
			<div style="padding-left:10px;">
				<div>
					<h6 class="callcenter">oops</h6>
				</div>
				<div>
					<h6 class="cliente">something went wrong</h6>
				</div>
				<div>
					<p class="snippet">contact the sys admin</p>
				</div>
			</div>
		</div>
		<div class="row-fluid" style="padding-top:3px;">
			<div style="padding:5px 10px;">
				<p class="last-interaction">xx</p>
			</div>
		</div>
	</div>
</script>

<script type="text/javascript">
	var admin = "<?= $login_admin ?>";
	var atendimentoFacebook = '<?= $atendimentoFacebook == true ? "true" : "false" ?>';
	var atendimentoIG = '<?= $atendimentoIG == true ? "true" : "false" ?>';

	var socketNotify = io.connect("https://api2.telecontrol.com.br:3003");

	$(function() {
		if (Notification.permission !== "denied") {
			Notification.requestPermission();
		}

		ion.sound({
			sounds: [{name: "facebook_notify",volume: 1.0}],
			volume: 1.0,
			path: "plugins/ion-sound/sounds/",
			preload: true
		});

		socketNotify.on("conected", function() {
			if (atendimentoFacebook == "true") {
				socketNotify.emit("joinRoom", {"room": "callcenter_admin_" + admin}, function () {});
			}

			if (atendimentoIG == "true") {
				socketNotify.emit("joinRoom", {"room": "ig_callcenter_admin_" + admin}, function () {});
			}
		});

		socketNotify.on("newMessage", function(data) {
			if (data.body) {
				let service = data.body.service;
				let dashbodyContent;

				if (typeof service !== "undefined" && service === "facebook") {
					dashbodyContent = $("#facebook-dash").find(".dashbody-content");
				} else if (typeof service !== "undefined" && service === "instagram") {
					dashbodyContent = $("#instagram-dash").find(".dashbody-content");
				}

				if ($(dashbodyContent).find("#callcenter_" + data.body.callcenter).length > 0) {
					$(dashbodyContent).find("#callcenter_" + data.body.callcenter).remove();
				}

				$.ajax(window.location.pathname.split("/").reverse()[0], {
					method: 'POST',
					async: true,
					data: {
						ajax: 'loadHD',
						callcenter: data.body.callcenter
					}
				}).done(function(response) {
					response = JSON.parse(response);

					notifyAdmin(service, data.body.callcenter);
					ion.sound.play('facebook_notify');

					createElement(service, response);
				});
			}
		});

		if (atendimentoFacebook == "true")
			loadHdChamados("facebook")

		if (atendimentoIG == "true")
			loadHdChamados("instagram")

		function loadHdChamados(service) {
			let dashbodyContent = $("#" + service + "-dash").find(".dashbody-content");

			$.ajax(window.location.pathname.split("/").reverse()[0], {
				method: 'POST',
				async: true,
				data: {
					ajax: 'loadHDCalls',
					service: service
				}
			}).done(function(response) {
				response = JSON.parse(response);

				if (response) {
					$(response).each(function (index, element) {
						createElement(service, element);
					});
					$(dashbodyContent).slideDown("slow");
				} else {
					$(dashbodyContent).html("<div class='alert_na' style='text-align:center'>Nenhum atendimento encontrado</div>");
					$(dashbodyContent).slideDown("slow");
				}

				$(".status").html("<i class='fas fa-check-circle'></i> Conectado!");
			});
		}

		function notifyAdmin(service, callcenter) {
			if (Notification.permission === "granted") {
				if (service === "facebook") {
					new Notification("Facebook", {
						body: "Você recebeu uma nova mensagem no Facebook.",
						icon: "imagens_admin/tc_logo.png"
					}).onclick = function (e) {
						e.preventDefault();
						window.open('callcenter_interativo_new.php?callcenter=' + callcenter, '_blank');
					}
				} else if (service === "instagram") {
					new Notification("Instagram", {
						body: "Há um novo comentário aguardando sua resposta no Instagram.",
						icon: "imagens_admin/tc_logo.png"
					}).onclick = function (e) {
						e.preventDefault();
						window.open('callcenter_interativo_new.php?callcenter=' + callcenter, '_blank');
					}
				}
			}
		}

		function createElement(service, element) {
			let dashbodyContent = $("#" + service + "-dash").find(".dashbody-content");

			let template = $.parseHTML($("#template-atendimentos").html().trim());
			template = $(template);

			$(template).attr("id", 'callcenter_' + element.hd_chamado);
			$(template).on("click", function () {
				window.open("callcenter_interativo_new.php?callcenter=" + element.hd_chamado, "_blank");
			});

			let icon = $(template).find(".notify-area i");
			if (element.admin_ultima_interacao == null) {
				$(icon).addClass("fa-comments");
				$(icon).css("color", "#ED6A6A");
			} else {
				$(icon).addClass("fa-comment");
				$(icon).css("color", "#588BCE");
			}

			$(template).find(".info-body .callcenter").text('HD-' + element.hd_chamado);
			$(template).find(".info-body .cliente").text(element.nome_cliente.substring(0, 20) + "...");

			let snippet = element.ultima_interacao.replace(/<[a-zA-Z]\s[a-zA-Z]+\W+>|<[a-z]>|<\/[a-z]+>|<[a-z]+\s\/>/, "");
			$(template).find(".info-body .snippet").text(snippet.substring(0, 20) + "...");

			$(template).find(".last-interaction").text(element.data_ultima_interacao);

			if ($("#" + service + "-dash").find(".alert_na").length === 1) {
				$("#" + service + "-dash").find(".alert_na").remove();
			}

			$(dashbodyContent).prepend(template);
			$(template).fadeIn(800);
		}
	});
</script>

<? include "rodape.php"; ?>