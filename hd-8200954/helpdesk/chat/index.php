<?php

session_start();	
include_once('banco.inc.php');
include 'autentica_admin.php';
if($login_fabrica<>10) exit();

//require_once('autentica_usuario.php');

//if ($_SESSION['sess_nivel']=='admin'){	
// 	header("Location:gerenciador.php");
// 	exit();
//}


if (!isset($_SESSION['sess_nick'])){	
	header("Location: nick.php");
	exit();
}


//require_once("chat.php");
//$chat = new chat();
//$chat->main();
echo '<html><head>
			<style>
			.body{
				font-size:12px;
				font-family:"Verdana,arial";
				
			}
			</style>
			<script language="JavaScript">
				function createRequestObject(){
			var request_;
			var browser = navigator.appName;
			if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
			}else{
			 request_ = new XMLHttpRequest();
			}
			return request_;
			}
			
			var http = new Array();
			var http2 = new Array();
				
			
			function getInfo(){
			
			var curDateTime = new Date();
			http[curDateTime] = createRequestObject();
			
			http[curDateTime].open(\'get\', \'refresh.php\');
			
			http[curDateTime].onreadystatechange = function(){
				if (http[curDateTime].readyState == 4) 
		    	{
		        	if (http[curDateTime].status == 200 || http[curDateTime].status == 304) 
		        	{
		           	 	var response = http[curDateTime].responseText;
		 				var area = document.getElementById(\'view_ajax\');
						area.innerHTML = response;
						area.scrollTop=10000;

		        	}
		    	}
			}
			
			http[curDateTime].send(null);
			}
			
			
			function getInfo2(){
			var curDateTime = new Date();
			http2[curDateTime] = createRequestObject();
			http2[curDateTime].open(\'get\', \'submit.php?chat=\'+ document.ajax.chat.value);
			http2[curDateTime].send(null);
			}
			
			function send(){
			getInfo2();
			document.ajax.chat.value=" ";
			}
			
			
			function go(){
			getInfo();
			window.setTimeout("go()", 2000);
			}
			
			</script>
			</head><body onLoad="go()"><center>
			<div id="view_ajax" style="overflow:auto;scrolling=auto; width: 330px; height: 360px; border: 1px;background-color:D9E2EF;font-size:12px;" align="left">
			</div><br>
			<form action="JavaScript: send()" method="get" name="ajax">
			<input type="text" name="chat">&nbsp;<input type="button" value="OK" onClick="send()"><br><br>
			<input type="button" value="Fechar" onClick="javascript:window.location=\'logout.php\'">
			</form>	
			</center>
			</body></html>';
?>