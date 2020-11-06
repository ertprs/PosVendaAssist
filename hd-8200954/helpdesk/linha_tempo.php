<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


#cria o XML

# examples/jfk/jfk.xml

	$sql= " SELECT tbl_hd_chamado_atendente.hd_chamado_atendente,
					tbl_hd_chamado_atendente.hd_chamado,
					TO_CHAR(tbl_hd_chamado_atendente.data_inicio,'Mon DD YYYY HH24:MI:SS') as data_inicio,
					TO_CHAR(tbl_hd_chamado_atendente.data_termino,'Mon DD YYYY HH24:MI:SS') as data_termino,
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.status,
					tbl_hd_chamado_item.comentario,
					tbl_admin.login,
					tbl_fabrica.nome
			FROM tbl_hd_chamado_atendente
			JOIN tbl_hd_chamado       ON tbl_hd_chamado.hd_chamado      = tbl_hd_chamado_atendente.hd_chamado
			JOIN tbl_hd_chamado_item  ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
			JOIN tbl_admin            ON tbl_admin.admin                = tbl_hd_chamado_item.admin
			JOIN tbl_fabrica          ON tbl_fabrica.fabrica            = tbl_admin.fabrica
			WHERE tbl_hd_chamado_item.admin = 567";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {

		$historico = "<data>";

		for ($i =0; $i<pg_numrows($res); $i++ ){
			$hd_chamado_atendente = pg_result($res,$i,hd_chamado_atendente);
			$hd_chamado       = pg_result($res,$i,hd_chamado);
			$data_inicio      = pg_result($res,$i,data_inicio);
			$data_termino     = pg_result($res,$i,data_termino);
			$titulo           = pg_result($res,$i,titulo);
			$status           = pg_result($res,$i,status);
			$comentario       = pg_result($res,$i,comentario);
			$admin            = pg_result($res,$i,login);
			$fabrica          = pg_result($res,$i,nome);

			$historico .= "
						<event 
							start=\"$data_inicio GMT\"
							end=\"$data_termino GMT\"
							isDuration=\"true\"
							title=\"$titulo\"
							image=\"http://simile.mit.edu/images/csail-logo.gif\"
							>";
			$historico .= "$comentario";
			$historico .= "</event>";
		}

		$arquivo  = fopen ("linha_tempo.xml", "w+");
		fwrite($arquivo, "$historico");
		fclose ($arquivo);
	}
?>

<html>
<head>
	<title>SIMILE | Timeline</title>
	<link rel='stylesheet' href='styles.css' type='text/css' />
	<style type="text/css">
		@import url("http://simile.mit.edu/styles/default.css");
	</style>
	
	<script src="http://simile.mit.edu/timeline/api/scripts/timeline-api.js" type="text/javascript"></script>
	<script>
		var tl;
		function onLoad() {
			var eventSource = new Timeline.DefaultEventSource();
			
			var zones = [
				{   start:    "Fri Nov 22 1963 00:00:00 GMT-0600",
					end:      "Mon Nov 25 1963 00:00:00 GMT-0600",
					magnify:  10,
					unit:     Timeline.DateTime.DAY
				},
				{   start:    "Fri Nov 22 1963 09:00:00 GMT-0600",
					end:      "Sun Nov 24 1963 00:00:00 GMT-0600",
					magnify:  5,
					unit:     Timeline.DateTime.HOUR
				},
				{   start:    "Fri Nov 22 1963 11:00:00 GMT-0600",
					end:      "Sat Nov 23 1963 00:00:00 GMT-0600",
					magnify:  5,
					unit:     Timeline.DateTime.MINUTE,
					multiple: 10
				},
				{   start:    "Fri Nov 22 1963 12:00:00 GMT-0600",
					end:      "Fri Nov 22 1963 14:00:00 GMT-0600",
					magnify:  3,
					unit:     Timeline.DateTime.MINUTE,
					multiple: 5
				}
			];
			var zones2 = [
				{   start:    "Fri Nov 22 1963 00:00:00 GMT-0600",
					end:      "Mon Nov 25 1963 00:00:00 GMT-0600",
					magnify:  10,
					unit:     Timeline.DateTime.WEEK
				},
				{   start:    "Fri Nov 22 1963 09:00:00 GMT-0600",
					end:      "Sun Nov 24 1963 00:00:00 GMT-0600",
					magnify:  5,
					unit:     Timeline.DateTime.DAY
				},
				{   start:    "Fri Nov 22 1963 11:00:00 GMT-0600",
					end:      "Sat Nov 23 1963 00:00:00 GMT-0600",
					magnify:  5,
					unit:     Timeline.DateTime.MINUTE,
					multiple: 60
				},
				{   start:    "Fri Nov 22 1963 12:00:00 GMT-0600",
					end:      "Fri Nov 22 1963 14:00:00 GMT-0600",
					magnify:  3,
					unit:     Timeline.DateTime.MINUTE,
					multiple: 15
				}
			];
			
			var theme = Timeline.ClassicTheme.create();
			theme.event.label.width = 250; // px
			theme.event.bubble.width = 250;
			theme.event.bubble.height = 200;
			
			var date = "Fri Nov 22 1963 13:00:00 GMT-0600"
			var bandInfos = [
				Timeline.createHotZoneBandInfo({
					width:          "75%", 
					intervalUnit:   Timeline.DateTime.WEEK, 
					intervalPixels: 200,
					zones:          zones,
					eventSource:    eventSource,
					date:           date,
					timeZone:       -6,
					theme:          theme
				}),
				Timeline.createHotZoneBandInfo({
					width:          "25%", 
					intervalUnit:   Timeline.DateTime.MONTH, 
					intervalPixels: 200,
					zones:          zones2, 
					eventSource:    eventSource,
					date:           date, 
					timeZone:       -6,
					showEventText:  false, 
					trackHeight:    0.5,
					trackGap:       0.2,
					theme:          theme
				})
			];
			bandInfos[1].syncWith = 0;
			bandInfos[1].highlight = true;
			bandInfos[1].eventPainter.setLayout(bandInfos[0].eventPainter.getLayout());
			
			for (var i = 0; i < bandInfos.length; i++) {
				bandInfos[i].decorators = [
					new Timeline.SpanHighlightDecorator({
						startDate:  "Fri Nov 22 1963 12:30:00 GMT-0600",
						endDate:    "Fri Nov 22 1963 13:00:00 GMT-0600",
						color:      "#FFC080",
						opacity:    50,
						startLabel: "shot",
						endLabel:   "t.o.d.",
						theme:      theme
					}),
					new Timeline.PointHighlightDecorator({
						date:       "Fri Nov 22 1963 14:38:00 GMT-0600",
						color:      "#FFC080",
						opacity:    50,
						theme:      theme
					}),
					new Timeline.PointHighlightDecorator({
						date:       "Sun Nov 24 1963 13:00:00 GMT-0600",
						color:      "#FFC080",
						opacity:    50,
						theme:      theme
					})
				];
			}
			
			tl = Timeline.create(document.getElementById("tl"), bandInfos, Timeline.HORIZONTAL);
			tl.loadXML("linha_tempo.xml", function(xml, url) { eventSource.loadXML(xml, url); });
		}
		
		var resizeTimerID = null;
		function onResize() {
			if (resizeTimerID == null) {
				resizeTimerID = window.setTimeout(function() {
					resizeTimerID = null;
					tl.layout();
				}, 500);
			}
		}
	</script>
</head>
<body onload="onLoad();" onresize="onResize();">

<ul id="path">
  <li><a href="www.telecontrol.com.br" title="Home">HELP DESK - TIMELINE</a></li>
  <li><span>Linha do Tempo - Trabalho</span></li>
</ul>

<div id="body">
	<h1>Linha do tempo</h1>
	<p>Abaixo tem a linha do tempo</p>

	<div id="tl" class="timeline-default" style="height: 400px; margin: 2em;">
	</div>
		
	<h2>Trabalho</h2>
	<ul>
		<li>Testes de trabalho
		</li>
		
		<li>ok!!!
		</li>
	</ul>

	
	<h2>HelpDesk</h2>
	<p>Em teste
		<ul>
			<li>OK.
			</li>
		</ul>
	</p>
</body>
</html>
