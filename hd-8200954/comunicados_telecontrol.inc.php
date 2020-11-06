<?php
// Mensagem da Telecontrol, para todos.
$mlg_hoje = strtotime('now');
//if ($mlg_hoje > strtotime('12/06/2011 09:10:00') and $mlg_hoje < strtotime('12/06/2011 11:40:00'))
if ($mlg_hoje > strtotime('12/12/2011 11:00:00') and $mlg_hoje < strtotime('12/12/2011 11:15:00'))
	include 'admin/dropdown_mensagem_admin.html';

if ($mlg_hoje >= strtotime('2017-10-10 00:00:01') and $mlg_hoje < strtotime('2017-10-16 18:00:01')
	and strpos($_SERVER['PHP_SELF'], 'menu_') > 0
	and ($_COOKIE['ComunicadoGeralPosto'] != $login_posto)
) {
?>
	<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css">
	<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
	<script type="text/javascript">
		Shadowbox.init({
			skipSetup: true,
			modal: true
		});
	</script>
	<style>
		#comunicado_importante{
			padding: 10px;
			z-index: 99999;
		}

		#comunicado_importante h4{
			text-align: center;
		}
		#sb-body .table {
			display: table;
			position: relative;
			width: 94%;
			height: 95%;
			padding: 1em 2em;
		}
		#sb-body .table .row p {
			font-size: 14px;
		}
		#sb-body .table .row {
			table-layout: fixed;
			display: block;
		}
		#sb-body .table > div {
			display: table-cell;
			vertical-align: middle;
		}
		#sb-body {
			background-image: url()!important
		}
	</style>
	<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	<script type="text/javascript">
	function desfazerNuvemComunicadoGeralPosto(){
		// $('#nuvem').css({"display" : "none"});
		$.ajax({
			url: document.location.href,
			type: "POST",
			data: "ComunicadoGeralPosto=ja_li",
			complete: function(data){
				data = data.responseText;
				// console.log("ok -> ja leu");
			}
		});
	}

	window.onload = function() {
		Shadowbox.open({
			content: 'comunicado_layout_posto.html',
			player:  "iframe",
			title:   "Comunicado Importante",
			onClose: desfazerNuvemComunicadoGeralPosto()
		});
	};
	</script>

<?php
}

