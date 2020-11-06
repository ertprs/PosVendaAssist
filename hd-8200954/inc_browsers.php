<?php
$rel_path = (strpos($_SERVER['PHP_SELF'], 'admin')) ? BI_BACK . '../' : ''; //BI_BACK só existe quando está no ambiente do admin...

$t1 = ($rel_path) ? "A Telecontrol recomenda o uso de um dos seguintes navegadores:" :
					traduz('a.telecontrol.recomenda.o.uso.de.um.dos.seguintes.navegadores', $con);
$t2 = ($rel_path) ? "Leia mais sobre as vantagens de ter um navegador atualizado" :
					traduz('leia.mais.sobre.porque.atualizar.seu.browser', $con);
?>
	<div id='browsers' class="no-print">
		<span id='browsers_span'><?=$t1?></span><br />
		<span style="display:inline-block;width:95%;_zoom:1">
			<a href="http://www.mozilla.org/firefox/" target="_blank"><img src="<?=$rel_path?>imagens/wb-firefox.gif" alt="FireFox" /><br />Firefox v6.0+</a>
			<a href="http://chrome.google.com/"		  target="_blank"><img src="<?=$rel_path?>imagens/wb-chrome.gif"  alt="Chrome"  /><br />Chrome v14.0+</a>
			<a href="http://www.apple.com/safari/"	  target="_blank"><img src="<?=$rel_path?>imagens/wb-safari.gif"  alt="Safari"  /><br />Safari 4.0+</a>
			<a href="http://www.opera.com/"			  target="_blank"><img src="<?=$rel_path?>imagens/wb-opera.gif"	  alt="Opera"   /><br />Opera 11.6+</a>
			<a href="http://ie.microsoft.com/"		  target="_blank"><img src="<?=$rel_path?>imagens/wb-ie8.gif"     alt="IE"	    /><br />Internet Explorer 8</a>
		</span>
		<span id='brwlink'>
			<br />&nbsp;
			<a href="http://updateyourbrowser.net/" target='_blank' style="float:initial;display:inline!important;width:initial">
				<?=$t2?></a> 
		</span>
	</div>
	<style>
		/* CSS Banner navegadores */
		#browsers {
			background-color: #363b61;
			border-radius: 0 0 6px 6px;
			box-shadow: 0 2px 5px black;
			color: white;
			font-size: 11px;
			margin: 1px auto 6px auto;
			padding: 4px 0 0 15px;
			position: relative;
			text-align: center;
			top: 0;
			width: 460px;
			height: 16px;
			transition: width 0.3s, height 0.3s;
			-o-transition: width 0.3s, height 0.3s;
			-ms-transition: width 0.3s, height 0.3s;
			-moz-transition: width 0.3s, height 0.3s;
			-webkit-transition: width 0.3s, height 0.3s;
			overflow: hidden;
		}
		#browsers, #browsers a {
			transition-delay: 0.5s;
			-o-transition-delay: 0.5s;
			-ms-transition-delay: 0.5s;
			-moz-transition-delay: 0.5s;
			-webkit-transition-delay: 0.5s;
		}
		#browsers a {
			color: white;
			display: inline-block;
			float: left;
			font-size: 11px;
			font-weight: bold;
			margin:auto;
			_zoom:1;
			width: 19%;
			white-space: nowrap;
			transition: font-size 0.3s;
			-o-transition: font-size 0.3s;
			-ms-transition: font-size 0.3s;
			-moz-transition: font-size 0.3s;
			-khtml-transition: font-size 0.3s;
			-webkit-transition: font-size 0.3s;
			transition-delay: 0.5s;
		}
		#browsers:hover {
			height: 96px;
			width: 680px;
		}
		#browsers a:hover {
			font-size: 13px;
			clear: none;
		}
		#browsers a img {
			border: 0 solid transparent;
			height: 24px;
			opacity: 0.8;
			transition: height 0.3s, opacity 0.3s;
			-o-transition: height 0.3s, opacity 0.3s;
			-ms-transition: height 0.3s, opacity 0.3s;
			-moz-transition: height 0.3s, opacity 0.3s;
			-khtml-transition: height 0.3s, opacity 0.3s;
			-webkit-transition: height 0.3s, opacity 0.3s;
		}
		#browsers a img:hover {
			height: 40px;
			font-style: normal;
			opacity: 1;
		}
		#browsers span {
			font-weight:bold;
			margin: 0 auto 2px auto;
			display:inline-block;
			_zoom: 1;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		#browsers span#brwlink {
			display:inline-block;
			width:96%;
			font-weight:normal;
			position:absolute;
			top:64px;
			left:2%;
			text-align: center;
			margin: auto;
			_zoom:1;
		}
		span#brwlink a {
			float: none;
			display: inline!important;
		}
		body {
			margin-top: 0!important;
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
	</script>
