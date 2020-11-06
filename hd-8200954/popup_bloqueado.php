<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" type="text/css" href="css/lupas/lupas.css">
		<link rel="stylesheet" type="text/css" href="css/posicionamento.css">
		<script src="js/jquery-1.6.2.js" ></script>
		<script>
			$(function() {
				var nav = window.navigator.userAgent;

				if (nav.match(/Firefox/gi)) {
					$("#nav-name").text("Firefox");
					$("#nav-img").html("<img src='imagens/antipopup_firefox.jpg' style='border: 0px; width: 100%;' />");
				} else if (nav.match(/Chrome/gi) && nav.match(/Safari/gi)) {
					$("#nav-name").text("Google Chrome");
					$("#nav-img").html("<img src='imagens/antipopup_chrome.jpg' style='border: 0px; width: 100%;' />");
				} else if (nav.match(/Opera/gi)) {
					$("#nav-name").text("Opera");
					$("#nav-img").html("<img src='imagens/antipopup_opera.jpg' style='border: 0px; width: 100%;' />");
				} else if (nav.match(/Safari/gi) && !nav.match(/Chrome/gi)) {
					$("#nav-name").text("Safari");
					$("#nav-img").html("<img src='imagens/antipopup_safari.jpg' style='border: 0px; width: 100%;' />");
				} else if (nav.match(/MSIE/gi)) {
					$("#nav-name").text("Internet Explorer");
					$("#nav-img").html("<img src='imagens/antipopup_internet_explorer.jpg' style='border: 0px; width: 100%;' />");
				}
			});
		</script>
	</head>

	<body style="background-color: #FFF;">
		<div class='lp_nova_pesquisa' style="position: fixed; top: 0px; height: 180px;">
			<br />
			<a href='javascript:window.parent.Shadowbox.close();' style='border: 0;'>
				<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar' />
			</a>

			<h3 style="color: #f00; text-align: center;">POPUP BLOQUEADO</h3>

			<p>
				Para uma melhor usabilidade do nosso sistema e evitar problemas para visualizar
				vistas explodidas, comunicados, esquemas elétricos e manuais, <b>desative o anti-popup
				do seu navegador e clique novamente no link</b>.
			</p>

			<p>Como desativar o <b>anti-popup</b> do <span id="nav-name"></span></p>
		</div>
		<div style="background-color: #FFF; width: 100%; text-align: center; margin-top: 190px;">
			<span id="nav-img"></span>
		</div>
	</body>
</html>