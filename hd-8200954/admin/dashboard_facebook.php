<?php

/**
 *
 * @author Gabriel Tinetti
 *
**/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

if ($_POST['ajax']) {
	switch ($_POST['ajax']) {
		case 'loadHDCalls':
			$qCalls = 	"SELECT DISTINCT x.* FROM (
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
							AND thco.descricao = 'Facebook'
							AND thc.status NOT IN ('Resolvido')
							AND thco.fabrica = {$login_fabrica}
							)x
							JOIN tbl_hd_chamado_item_externo thcie ON thcie.hd_chamado = x.hd_chamado
							WHERE thcie.id_integracao IS NOT NULL";
			$rCalls = 	pg_query($con, $qCalls);

			if (strlen(pg_last_error()) > 0) {
				$response = ['exception' => pg_last_error()];
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
						AND thco.descricao = 'Facebook'
						AND thc.status NOT IN ('Resolvido')
						AND thco.fabrica = {$login_fabrica}
						AND thc.hd_chamado = {$hd_chamado}
						)x
						JOIN tbl_hd_chamado_item_externo thcie ON thcie.hd_chamado = x.hd_chamado
						WHERE thcie.id_integracao IS NOT NULL;";
			$rCall 	= 	pg_query($con, $qCall);
			
			if (pg_num_rows($rCall) == 0) {
				$response = ['error' => 'HD not found'];
			} else {
				$rCall = pg_fetch_all($rCall);
				$response = $rCall[0];
			}

			echo json_encode($response);
			break;
	}

	exit;
}

$layout_menu = "callcenter";
$title = "DASHBOARD FACEBOOK";

include "cabecalho_new.php";
$plugins = array("font_awesome");
include ("plugin_loader.php");

?>
<script src="plugins/ion-sound/ion.sound.min.js"></script>
<script src="plugins/socket.io-client/dist/socket.io.js"></script>
<style type="text/css">
	* {
		box-sizing: border-box;
	}

	.head-title {
		background-color: #596D9B;
		width: 100%;
		color: #FFF;
		font-size: 18px;
		text-align: center;
		text-transform: uppercase;
		padding: 5px 0;
	}

	p {margin:0;}

	.dashbody {
		padding: 0 20px;
		background-color: #F1F1F1;
	}

	.dashbody-content {
		display: none;
	}

	.fa-check-circle {
		font-size: 13px;
	}

	.row-fluid {
		margin: 0;
		padding: 0;
	}

	.row {
		margin: 0;
		padding-top: 20px;
		padding-bottom: 20px;
	}
</style>

<div class="head-title">
	<h5 class="title">ATENDIMENTOS</h5>
	<div style="float:right;margin-top:-30px;margin-right:10px">
		<small style="font-weight:bold" class="status"><i class="fa fa-spinner fa-spin"></i> Carregando...</small>
	</div>
</div>
<div class="dashbody">
	<div class="row dashbody-content">
		
	</div>
</div>
<script type="text/javascript">
	let admin = "<?= $login_admin ?>";
	let socketNotify = io.connect("https://api2.telecontrol.com.br:3003");
	let baseURL =  "https://" + document.domain + "/assist/admin/callcenter_interativo_new.php?callcenter=";
	ion.sound({
		sounds: [
			{
				name: "facebook_notify",
				volume: 1.0
			}
		],
		volume: 1.0,
		path: "plugins/ion-sound/sounds/",
		preload: true
	});

	$(function () {
		if (Notification.permission !== "denied") {
			Notification.requestPermission();
		}

		socketNotify.on("newMessage", function (data) {
			$(".dashbody-content").find(".alert_na").remove();

			if (data.body) {
				if ($(".dashbody-content").find("#callcenter_" + data.body.callcenter).length > 0) {
					let hdItem = $(".dashbody-content").find("#callcenter_" + data.body.callcenter);
					let infoBody = $(hdItem).find(".info-body");
					let notifyIcon = $(hdItem).find(".notify-area").find("i");
					let infoInteract = $(hdItem).find(".last-interaction")[0];
					let notifyColor = "#ED6A6A";
					let notifyType = "fa-comments";

					if (data.body.admin === true || data.body.admin === "true") {
						notifyColor = "#588BCE";
						notifyType = "fa-comment";
					}

					$(notifyIcon).fadeOut(500, function () {
						$(notifyIcon).removeClass("fa-comment fa-comments");
						$(notifyIcon).addClass(notifyType);
						$(notifyIcon).css({
							"color": notifyColor
						})
					});
					$(notifyIcon).fadeIn(500);

					$(infoBody).animate({
						"background-color": "#EAEAEA"
					}, 500);
					setTimeout(function () {
						$(infoBody).animate({
							"background-color": "#FFF"
						});
					}, 500);

					let rawDate = new Date(data.body.time);
					$(infoInteract).text("Última interação: " + formatDate(rawDate));

					let snippet = data.body.message;
					$(infoBody).find(".snippet").text(snippet.substring(0, 30) + "...");

					$(hdItem).prependTo($(".dashbody-content"));
				} else {
					$.ajax("dashboard_facebook.php", {
						method: 'POST',
						async: true,
						data: {
							ajax: 'loadHD',
							callcenter: data.body.callcenter
						}
					}).done(function (response) {
						response = JSON.parse(response);
						createElement(response);
					});
				}

				if (Notification.permission === "granted") {
					let notification = new Notification('HD-' + data.body.callcenter, {
						body: 'Última Mensagem: ' + data.body.message.substring(0, 100) + '...',
						icon: 'imagens_admin/tc_logo.png'
					});
					notification.onclick = function(event) {
						event.preventDefault();
						window.open('callcenter_interativo_new.php?callcenter=' + data.body.callcenter, '_blank');
					}
				}
				ion.sound.play('facebook_notify');
			}
		});

		socketNotify.on("conected", function () {
            $.ajax("dashboard_facebook.php", {
				method: 'POST',
				async: true,
				data: {
					ajax: 'loadHDCalls'
				}
			}).done(function (response) {
				loading = false;
				response = JSON.parse(response);
				if (response) {
					$(response).each(function (index, element) {
						createElement(element);
					});
					$(".dashbody-content").slideDown("slow");
				} else {
					$(".dashbody-content").html("<div class='alert_na' style='text-align:center'>Nenhum atendimento encontrado</div>");
					$(".dashbody-content").slideDown("slow");
				}
				$(".status").html("<i class='fas fa-check-circle'></i> Conectado!");
				socketNotify.emit("joinRoom", {
					"room": "callcenter_admin_" + admin
				}, function () { console.log('joined admin room'); });
			});
        });
	});

	function createElement(element) {
		let divHdItem = $("<div></div>", {
			class: "span3 hd-item",
			css: {
				"background-color": "#596D9B",
				"height": "100px",
				"max-height": "100px",
				"min-height": "100px",
				"margin": "3%",
				"border-radius": "5px",
				"box-shadow": "0 0 10px #CCC",
				"transition": "box-shadow 1s",
				"-webkit-transition": "box-shadow 1s",
				"display": "none"
			},
			attr: {
				"id": "callcenter_" + element.hd_chamado
			}
		});
		$(divHdItem).on("click", function () {
			infoBody = $(this).find(".info-body");
			notifyIcon = $(this).find(".notify-area").find("i");
			infoInteract = $(this).find(".last-interaction")[0];

			$(notifyIcon).removeClass("fa-comments");
			$(notifyIcon).addClass("fa-comment");
			$(notifyIcon).css({
				"color": "#588BCE"
			});
			window.open(baseURL + element.hd_chamado, "_blank");
		});

		$(divHdItem).mouseenter(function () {
			$(this).css({
				"cursor": "pointer",
				"box-shadow": "0 0 10px #666"
			})
		}).mouseleave(function () {
			$(this).css({
				"cursor": "initial",
				"box-shadow": "0 0 10px #CCC"
			})
		});

		let divIcon = $("<div></div>", {
			css: {
				"float": "right",
				"margin-top": "-10px",
				"margin-right": "-10px",
			},
			class: "notify-area"
		});

		let notifyColor = "#ED6A6A";
		let notifyIcon = "fas fa-comments";

		if (element.admin_ultima_interacao) {
			notifyIcon = "fas fa-comment";
			notifyColor = "#588BCE";
		}

		let iconI = $("<i></i>", {
			class: notifyIcon,
			css: {
				"font-size": "3em",
				"color": notifyColor,
			}
		});

		let divRowHd = $("<div></div>", {
			class: "row-fluid info-body",
			css: {
				"background-color": "#FFF",
				"border-top-left-radius": "5px",
				"border-top-right-radius": "5px",
				"padding": "0px 0"
			}
		});

		let divInner = $("<div></div>", {
			css: {
				"padding-left": "10px",
			}
		});

		let divHD = $("<div></div>");
		let hdCall = $("<h6></h6>", {
			css: {
				"font-size": "15px",
				"color": "#3D3D3D",
				"font-family": "Arial",
				"line-height": "0.8em",
				"text-transform": "uppercase"
			},
			text: "HD-" + element.hd_chamado
		});

		let divName = $("<div></div>");
		let clientName = $("<h6></h6>", {
			css: {
				"font-size": "12px",
				"color": "#3D3D3D",
				"font-family": "Arial",
				"line-height": "0.8em",
				"text-transform": "uppercase"
			},
			text: element.nome_cliente.substring(0, 15) + "..."
		});

		let divSnippet = $("<div></div>");

		let snippetText = element.ultima_interacao.replace(/<[a-zA-Z]\s[a-zA-Z]+\W+>|<[a-z]>|<\/[a-z]+>|<[a-z]+\s\/>/, "");
		let snippet = $("<p></p>", {
			class: "snippet",
			css: {
				"font-size": "11px",
				"color": "#3D3D3D",
				"font-family": "Arial"
			},
			text: snippetText.substring(0, 30) + "..."
		});

		let divRowInfo = $("<div></div>", {
			class: "row-fluid",
			css: {
				"padding-top": "3px"
			}
		});

		let divInnerInfo = $("<div></div>", {
			css: {
				"padding": "5px 10px 5px 10px",
			}
		});

		let pInfo = $("<p></p>", {
			css: {
				"font-size": "12px",
				"color": "#FFF",
				"font-family": "Arial",
				"line-height": "1.2em",
				"font-weight": "600"
			},
			class: "last-interaction",
			text: "Última interação: " + element.data_ultima_interacao
		});

		$(divInnerInfo).append(pInfo);
		$(divRowInfo).append(divInnerInfo);

		$(divName).append(clientName);
		$(divHD).append(hdCall);
		$(divSnippet).append(snippet);

		$(divInner).append(divHD);
		$(divInner).append(divName);
		$(divInner).append(divSnippet);

		$(divRowHd).append(divInner);

		$(divIcon).append(iconI);

		$(divHdItem).append(divIcon);
		$(divHdItem).append(divRowHd);
		$(divHdItem).append(divRowInfo);

		$(divHdItem).prependTo($(".dashbody-content"));
		$(divHdItem).fadeIn(800);
	}

	function formatDate(rawDate) {
		let day = rawDate.getNumberDay();
		let month = rawDate.getNumberMonth();
		let year = rawDate.getFullYear();
		let hours = rawDate.getHours();
		let minutes = rawDate.getMinutes();

		if (rawDate.getNumberDay().toString().length == 1) day = "0" + rawDate.getNumberDay();
		if (rawDate.getNumberMonth().toString().length == 1) month = "0" + rawDate.getNumberMonth();
		if (rawDate.getHours().toString().length == 1) hours = "0" + rawDate.getHours();
		if (rawDate.getMinutes().toString().length == 1) minutes = "0" + rawDate.getMinutes();

		return day + "/" + month + "/" + year + " " + hours + ":" + minutes;
	}
</script>

<? include "rodape.php"; ?>
