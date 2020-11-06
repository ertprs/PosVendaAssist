<?

//include_once('banco.inc.php');

class chat{

//	var $host="localhost";
//	var $user="root";
//	var $db="fazenda";
//	var $pass="";

	
						
	function main(){
		echo '<html><head><script language="JavaScript">
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
			
			<div id="view_ajax" style="overflow=auto;scrolling=auto; width: 340px; height: 400px; border: 1px;" align="left">
			
			</div><br>
			<form action="JavaScript: send()" method="get" name="ajax">
			<input type="text" name="chat">&nbsp;<input type="button" value="OK" onClick="send()"><br><br>
			<input type="button" value="Fechar" onClick="javascript:window.location=\'logout.php\'">
			</form>
			</center>
			</body></html>';
		}
}