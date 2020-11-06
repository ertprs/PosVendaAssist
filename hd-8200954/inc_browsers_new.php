<?php
$rel_path = (strpos($_SERVER['PHP_SELF'], 'admin')) ? BI_BACK . '../' : ''; //BI_BACK só existe quando está no ambiente do admin...

$t1 = ($rel_path) ? "A Telecontrol recomenda o uso de um dos seguintes navegadores, clique aqui:" :
					traduz('a.telecontrol.recomenda.o.uso.de.um.dos.seguintes.navegadores.clique.aqui', $con);
$t2 = ($rel_path) ? "Leia mais sobre as vantagens de ter um navegador atualizado" :
					traduz('leia.mais.sobre.porque.atualizar.seu.browser', $con);
?>
	<div id='browsers' class="no-print">
		<span id='browsers_span'><?=$t1?></span><br /><br />
			
		<table>
				<tr>
					<th> 
						<a href="http://www.mozilla.org/firefox/" target="_blank">
							<img src="<?=$rel_path?>imagens/wb-firefox.png" alt="FireFox" />
							<br />Firefox v6.0+
						</a>
					</th>
					<th>
						<a href="http://chrome.google.com/"	target="_blank">
							<img src="<?=$rel_path?>imagens/wb-chrome.png"  alt="Chrome"  />
							<br />Chrome v14.0+
						</a>
					</th>
					<th>
						<a href="http://www.apple.com/safari/" target="_blank">
							<img src="<?=$rel_path?>imagens/wb-safari.png"  alt="Safari"  />
							<br />Safari 4.0+
						</a>
					</th>
					<th>
						<a href="http://www.opera.com/"	target="_blank">
							<img src="<?=$rel_path?>imagens/wb-opera.png" alt="Opera"/>
							<br />Opera 11.6+
						</a>
					</th>
					<th>
						<a href="http://ie.microsoft.com/" target="_blank">
							<img src="<?=$rel_path?>imagens/wb-ie10.png" alt="IE"/>
							<br />Internet Explorer 8
						</a>
					</th>
				</tr>
		</table>

		<br />
		

		<span id='brwlink'>
			<a href="http://updateyourbrowser.net/" target='_blank'>
				<?=$t2?></a> 
		</span>

	</div>
	<style>

		/* CSS Banner navegadores */
		#browsers {

			/*background-color: #363b61;*/
			/*background-color: #596D9B;*/
			cursor: pointer;
			background: #006327; /* Old browsers */
			background: -moz-linear-gradient(top,  #006327 2%, #00b232 100%); /* FF3.6+ */
			background: -webkit-gradient(linear, left top, left bottom, color-stop(2%,#006327), color-stop(100%,#00b232)); /* Chrome,Safari4+ */
			background: -webkit-linear-gradient(top,  #006327 2%,#00b232 100%); /* Chrome10+,Safari5.1+ */
			background: -o-linear-gradient(top,  #006327 2%,#00b232 100%); /* Opera 11.10+ */
			background: -ms-linear-gradient(top,  #006327 2%,#00b232 100%); /* IE10+ */
			background: linear-gradient(to bottom,  #006327 2%,#00b232 100%); /* W3C */
			border-radius: 0 0 6px 6px;
			box-shadow: 0 2px 6px black;
			color: white;
			font-size: 13px;
			margin: 3px auto;
			position: relative;
			top: 0;
			width: 714px;
			height: 20px;
			transition: width 1.0s, height 1.0s;
			-o-transition: width 1.0s, height 1.0s;
			-ms-transition: width 1.0s, height 1.0s;
			-moz-transition: width 1.0s, height 1.0s;
			-webkit-transition: width 1.0s, height 1.0s;
			overflow: hidden;
		}
		
		/*#browsers:hover{
			width: 850px;
			height: 150px;
			transition: width 1.0s, height 1.0s;
			-o-transition: width 1.0s, height 1.0s;
			-ms-transition: width 1.0s, height 1.0s;
			-moz-transition: width 1.0s, height 1.0s;
			-webkit-transition: width 1.0s, height 1.0s;
		}*/

		table tbody th a:hover img {
			-moz-transform: scale(1.1);
			-webkit-transform: scale(1.1);
			-o-transform: scale(1.1);
			-ms-transform: scale(1.1);
			transform: scale(1.1);
		}

		#browsers_span{
			font-size: 13px;
			font-weight: bold;
			display: inline-block;
			width: 100%;
			text-align: center;

		}

		#browsers table{
			margin: 0 auto;
			table-layout: fixed;
		}

		#browsers table tr th{
			text-align: center;
			padding: 0px 20px 0 20px;
			
		}

		#browsers table tr th a, #brwlink a{
			color: white;
			text-decoration: none;
			cursor: pointer;
		}

		#brwlink{
			font-size: 12px;
			display: inline-block;
			width: 100%;
			text-align: center;
			text-decoration: underline;

		}

	</style>
	<script type="text/javascript">
	/****
	 * JS Browser detection
	 * Borrowed from http://www.quirksmode.org/js/detect.html
	 *
	 * Usage:
	 *   BrowserDetect.name    returns Browser's common name
	 *   BrowserDetect.version returns browser's version (except for Safari... read de docs)
	 *	 BrowserDetect.OS      returns claimed client's Operating System 
	 ***/
	var BrowserDetect = {
		init: function () {
				  this.browser = this.searchString(this.dataBrowser) || "Browser desconhecido";
				  this.version = this.searchVersion(navigator.userAgent)
					  || this.searchVersion(navigator.appVersion)
					  || "versão desconhecida";
				  this.OS = this.searchString(this.dataOS) || "SO desconhecido";
			  },
		searchString: function (data) {
						  for (var i=0;i<data.length;i++)	{
							  var dataString           = data[i].string;
							  var dataProp             = data[i].prop;
							  this.versionSearchString = data[i].versionSearch || data[i].identity;
							  if (data[i].minVersion != undefined) {
								  this.minVersion      = data[i].minVersion;
							  }

							  if (dataString) {
								  if (dataString.indexOf(data[i].subString) != -1)
									  return data[i].identity;
							  }
							  else if (dataProp)
								  return data[i].identity;
						  }
					  },
		searchVersion: function (dataString) {
						   var index = dataString.indexOf(this.versionSearchString);
						   if (index == -1) return;
						   var ver = parseFloat(dataString.substring(index+this.versionSearchString.length+1));
						   this.validVersion = (ver >= this.minVersion || this.minVersion == undefined);
						   return ver;
					   },
		dataBrowser: [
			{ string: navigator.userAgent, subString: "Chrome",   identity: "Chrome"       , minVersion: 14}, 
			{ string: navigator.userAgent, subString: "OmniWeb",  versionSearch: "OmniWeb/", identity: "OmniWeb",      minVersion: 5}, 
			{ string: navigator.vendor   , subString: "Apple",    identity: "Safari",        versionSearch: "Version", minVersion: undefined}, 
			{ prop: window.opera,          identity: "Opera",     versionSearch: "Version",  minVersion: 11.6}, 
			{ string: navigator.vendor,    subString: "iCab",     identity: "iCab",          minVersion: 5}, 
			{ string: navigator.vendor,    subString: "KDE",      identity: "Konqueror",     minVersion: 4.4}, 
			{ string: navigator.userAgent, subString: "Firefox",  identity: "Firefox",       minVersion: 6}, 
			{ string: navigator.vendor,    subString: "Camino",   identity: "Camino",        minVersion: 2}, 
			{ string: navigator.userAgent, subString: "Netscape", identity: "Netscape",      minVersion: 8}, // for newer Netscapes (6+)
			{ string: navigator.userAgent, subString: "MSIE",     identity: "Explorer",      versionSearch: "MSIE",    minVersion: 7}, 
			{ string: navigator.userAgent, subString: "Gecko",    identity: "Mozilla",       versionSearch: "rv",      minVersion: 5}, 
			{ string: navigator.userAgent, subString: "Mozilla",  identity: "Netscape",      versionSearch: "Mozilla", minVersion: 5} // for older Netscapes (4-). Este estará sempre ultrapassado!
		],
		dataOS : [
			{ string: navigator.platform,  subString: "Win",    identity: "Windows"},
			{ string: navigator.platform,  subString: "Mac",    identity: "Mac"},
			{ string: navigator.userAgent, subString: "WebOS",  identity: "WebOS"},
			{ string: navigator.userAgent, subString: "iPhone", identity: "iPhone/iPod"},
			{ string: navigator.platform,  subString: "Linux",  identity: "Linux"}
		]
	};
	BrowserDetect.init();

	if (!BrowserDetect.validVersion) {
		document.getElementById('browsers').style.backgroundColor = 'darkRed';
		document.getElementById('browsers_span').innerHTML = 'Seu navegador não é um dos recomendados. A Telecontrol recomenda os seguintes navegadores:';
	}


	
	$("#browsers").click(function() {
		if ($(this).attr("show") == undefined || $(this).attr("show") == "false") {
			$(this).finish().animate({height: "160px"},100);
			$(this).attr({ show: true });
		} else {
			$(this).finish().animate({height: "20px"},100);
			$(this).attr({ show: false });
		}
	});



	</script>
