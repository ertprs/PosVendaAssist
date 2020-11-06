// JavaScript Document
$(function() {
	var nav = BrowserDetect.browser;
	var ver = parseFloat(BrowserDetect.version);

	if (BrowserDetect.validVersion) {
		var texto = "<div id='brw' class='msg'>Seu navegador está atualizado!<br /><b>" + nav + "</b>, versão <b>" + ver.toFixed(2) + "</b></div>";
		$('#headextras').prepend(texto);
	} else {
		var texto = "<div id='brw' class='erro' alt='" + ver.toFixed(2) + "'>Seu navegador está desatualizado!<br />" + 
					"Clique <a href='http://updateyourbrowser.net/' target='_blank' title='Atualizar seu Navegador'>aqui para atualizar seu navegador</a>" + 
					"<br /><br />Recomendamos o <i>Google <b>Chrome</b></i></div>";
		$('#headextras').prepend(texto);
		$('#brw').show()
				 .css('height', 80)
				 .css('margin', 'auto')
			     .animate({height: 86, width: 270,duration:'fast'})
				 .delay(4000)
				 .animate({height:10,width: 200,duration: 1000})
				 .removeAttr('style');
	}
});

/****
 * JS Browser detection
 * Borrowed from http://www.quirksmode.org/js/detect.html
 *
 * Usage:
 *   BrowserDetect.name    returns Browser's common name
 *   BrowserDetect.version returns browser's version (except for Safari... read de docs)
 *	 BrowserDetect.OS      returns claimed client's Operating System 
 ***/
var global_CORS = false; // TRUE se o navegador permite Cross-Origin AJAX

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
	{string: navigator.userAgent, subString: "Chrome",   identity: "Chrome",        minVersion: 14},
	{string: navigator.userAgent, subString: "OmniWeb",  versionSearch: "OmniWeb/", identity: "OmniWeb",      minVersion: 5},
	{string: navigator.vendor,    subString: "Apple",    identity: "Safari",        versionSearch: "Version", minVersion: undefined},
	{prop: window.opera,          identity:  "Opera",    versionSearch: "Version",  minVersion: 11.6},
	{string: navigator.vendor,    subString: "iCab",     identity: "iCab",          minVersion: 5},
	{string: navigator.vendor,    subString: "KDE",      identity: "Konqueror",     minVersion: 4.4},
	{string: navigator.userAgent, subString: "Firefox",  identity: "Firefox",       minVersion: 5},
	{string: navigator.vendor,    subString: "Camino",   identity: "Camino",        minVersion: 2},
	{string: navigator.userAgent, subString: "Netscape", identity: "Netscape",      minVersion: 8},           // for newer Netscapes (6+)
	{string: navigator.userAgent, subString: "MSIE",     identity: "Explorer",      versionSearch: "MSIE",    minVersion: 7},
	{string: navigator.userAgent, subString: "Gecko",    identity: "Mozilla",       versionSearch: "rv",      minVersion: 5},
		// for older Netscapes (4-)
	{string: navigator.userAgent, subString: "Mozilla",  identity: "Netscape",      versionSearch: "Mozilla", minVersion: 5} // Este estar sempre ultrapassado!
	],
	dataOS : [
		{string: navigator.platform,  subString: "Win",    identity: "Windows"},
		{string: navigator.platform,  subString: "Mac",    identity: "Mac"},
		{string: navigator.userAgent, subString: "WebOS",  identity: "WebOS"},
		{string: navigator.userAgent, subString: "iPhone", identity: "iPhone/iPod"},
		{string: navigator.platform,  subString: "Linux",  identity: "Linux"}
	]
};
BrowserDetect.init();

