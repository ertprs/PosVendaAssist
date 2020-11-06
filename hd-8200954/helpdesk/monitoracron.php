<head>
	<link href='../plugins/shadowbox_lupa/shadowbox.css' rel='stylesheet' type='text/css'/>

	<link type="text/css" rel="stylesheet" media="screen" href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">

</head>

<?php

/**
 *
 *  monitoracron.php
 *
 *  Programa para monitoramento de rotinas cron
 *
 * @author  Francisco Ambrozio
 * @version 2012.03
 *
 */
 
$TITULO = 'Monitoramento de Rotinas';
$css = '
	<style>
		table.listagem {
			font-family: Verdana;
			font-size: 8px;
			border-collapse: collapse;
			/*width: 1120px;*/
			/*font-size: 1.1em;*/
		}

		table.listagem tr td {
			font-family: Verdana !important;
			font-size: 8px !important;
			padding: 1px 5px 5px 5px;
			border-bottom: 1px solid #303030;
			line-height: 15px;
            border-spacing: 0;
		}

		.titulo_tabela {
            font: bold 11px "Arial" !important;
			color: #FFFFFF;
			background: #596d9b;	
            text-align: center;            
            padding: 5px 0 0 0;
		}
		
		.conteudotxt {
			display:none; 
			position:absolute; 
			border: 1px solid black; 
			background-color: #FFFFCC; 
			padding: 5px;			
		}
		
		.nome_fabricante {
			font-family: Verdana;
			font-size: 20px;
			font-weight: bold;
			color: #596d9b;
		}
		
		.mensagem_sombra {
			clear: both;
			text-align: center;
			font-family: Verdana;
			font-size: 14px;
			color: #273977;
		}

		.lbl {
			font: -webkit-control;
		}

		.al {
			line-height: 10%;
			font-size: 17px;
		}
		
	</style>
';

$js = <<<JS

	<script type="text/javascript" src="../plugins/shadowbox_lupa/shadowbox.js"></script>
	<script type='text/javascript' src='https://code.jquery.com/jquery-1.9.1.min.js'></script>

	<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
	<script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
	<script src="../admin/plugins/jquery.alphanumeric.js"></script>
	<script src='../admin/plugins/jquery.mask.js'></script>


	<script src='../plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js'></script>
	<script type="text/javascript">		
		function ajax()
		{
			var xmlHttp;
			try {
				xmlHttp = new XMLHttpRequest();
			} catch(e) {
				try {
					xmlHttp = new ActiveXObject("Msxml2.XMLHTTP");
				} catch(e) {
					try {
						xmlHttp = new ActiveXObject("Microsoft.XMLHTTP");
					}catch(e){}
				}
			}

			return xmlHttp;
		}

		function doPost(params)
		{
			var xmlHttp = ajax();

			var url = 'monitoracron_ajax.php?';

			xmlHttp.open("POST",url,true);
			xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			xmlHttp.setRequestHeader("Content-length", params.length);
			xmlHttp.setRequestHeader("Connection", "close");
			xmlHttp.send(params);

			xmlHttp.onreadystatechange =  function () {
				if (xmlHttp.readyState == 4) {
					if (xmlHttp.status == 200) {
						alert(xmlHttp.response);

						if (xmlHttp.response == 'Removido com sucesso') {
							window.location = 'monitoracron.php?m=agendados';
						}
					}
				}
			}
		}

		function agendaExecucao(programa)
		{
			var params = 'p=' + programa;
			doPost(params);
		}

		function removeAgenda(perl)
		{
			var params = 'plid=' + perl;
			doPost(params);
		}
		
		// Exibir layer com mensagem de erro	
		// Autor..: Breno Sabella
		// Inicio
	
		var cX = 0; var cY = 0; var rX = 0; var rY = 0;
		
		function UpdateCursorPosition(e){ cX = e.pageX; cY = e.pageY;}
		function UpdateCursorPositionDocAll(e){ cX = event.clientX; cY = event.clientY;}
		
		if(document.all) { document.onmousemove = UpdateCursorPositionDocAll; }
		else { document.onmousemove = UpdateCursorPosition; }
		
		function AssignPosition(d) {
			if(self.pageYOffset) {
				rX = self.pageXOffset;
				rY = self.pageYOffset;
			}
			else if(document.documentElement && document.documentElement.scrollTop) {
				rX = document.documentElement.scrollLeft;
				rY = document.documentElement.scrollTop;
			}
			else if(document.body) {
				rX = document.body.scrollLeft;
				rY = document.body.scrollTop;
			}
			if(document.all) {
				cX += rX; 
				cY += rY;
			}
			d.style.left = (cX+10) + "px";
			d.style.top = (cY+10) + "px";
		}
		
		function HideContent(d) {
			if(d.length < 1) { return; }
			document.getElementById(d).style.display = "none";
		}

		function ShowContent(d) {
			if(d.length < 1) { return; }
			var dd = document.getElementById(d);
			AssignPosition(dd);
			dd.style.display = "block";
		}
		
		function ReverseContentDisplay(d) {
			if(d.length < 1) { return; }
			var dd = document.getElementById(d);
			AssignPosition(dd);
			if(dd.style.display == "none") { dd.style.display = "block"; }
			else { dd.style.display = "none"; }
		}

		$(function() {
			$("#data_inicial").datepicker().mask("99/99/9999");
	  		$("#data_final").datepicker().mask("99/99/9999");
		});

	</script>
JS;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

include 'menu_bs.php';

include 'monitoracron.inc.php';

echo $css;
echo $js;


$monitoraCron = new MonitoraCron(); 

if ($_POST && ($_POST['btn_acao'] == 'Pesquisar' || $_POST['btn_acao'] == 'Listar Todas')) {
	$cond = $_POST;
} else {
	$cond = [];
}

$monitoraCron->run($cond);

include 'rodape.php';
