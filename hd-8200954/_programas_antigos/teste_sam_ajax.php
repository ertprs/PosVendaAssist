<?include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
?>
<script language="javascript">
var Ajax = new Object();

Ajax.Request = function(url,callbackMethod){
	
	Page.getPageCenterX();
	Ajax.request = Ajax.createRequestObject();
 	Ajax.request.onreadystatechange = callbackMethod;
	Ajax.request.open("POST", url, true);
	Ajax.request.send(url);
}

Ajax.Response = function (){
	if(Ajax.CheckReadyState(Ajax.request))	{
		var	response2 = Ajax.request.responseText;
		var temp= document.getElementById('id');
		
		if (response2=="error"){
			document.getElementById('loading').innerHTML ="";
			document.getElementById('loading').innerHTML = "<table border=0 cellpadding=0 cellspacing=1 width=200 bgcolor=gray><tr><td align=center class=loaded height=45 bgcolor=#ffffff style='color:red;font-weigth:bold'>Imagem maior não encontrada!!</td></tr></table>";
			setTimeout('Page.loadOut()',3000);
			temp.innerHTML = "&nbsp;";
		}
		else{
			response = response2.split('|');
			temp.innerHTML = response[0];
			var temp2= document.getElementById('referencia');
			temp2.value = response[1];
		}
	}
}

Ajax.createRequestObject = function(){
	var obj;
	if(window.XMLHttpRequest)	{
		obj = new XMLHttpRequest();
	}
	else if(window.ActiveXObject)	{
		obj = new ActiveXObject("MSXML2.XMLHTTP");
	}
	return obj;
}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('loading').innerHTML ='';	
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;		
	//For old IE browsers 
	if(document.all) { 
		fWidth = document.body.clientWidth; 
		fHeight = document.body.clientHeight; 
	} 
	//For DOM1 browsers 
	else if(document.getElementById &&!document.all){ 
			fWidth = innerWidth; 
			fHeight = innerHeight; 
		} 
		else if(document.getElementById) { 
				fWidth = innerWidth; 
				fHeight = innerHeight; 		
			} 
			//For Opera 
			else if (is.op) { 
					fWidth = innerWidth; 
					fHeight = innerHeight; 		
				} 
				//For old Netscape 
				else if (document.layers) { 
						fWidth = window.innerWidth; 
						fHeight = window.innerHeight; 		
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}

</script>

<div id='loading'></div>
<div id="id">
</div><br> 

<?
$contador = 0;
//$referencia = "78909";

if ($handle = opendir('imagens_pecas/pequena/.')) {
			while (false !== ($file = readdir($handle))) {
				$contador++;
				if($contador == 10) break;
				$posicao = strpos($file, $referencia);
				if ($file != "." && $file != ".." ) {
					?>
					<a href="#" onclick="Ajax.Request('teste_sam_ajax.php?pegar=imagens_pecas/media/<? echo $file;?>, Ajax.Response')">
					<img src="imagens_pecas/pequena/<? echo $file;?>">
					</a>
					<?
				}				
			}
	closedir($handle);
}		

