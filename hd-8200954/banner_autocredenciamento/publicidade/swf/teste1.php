    <html>
    <title></title>
     
    <style type="text/css">
	body { 
		background: white;
	}
    #popup{
    position: absolute;
    top: 0%;
    left: 35%;
    width: 495px;
    height: 130px;
    padding: 0px 0px 0px 0px;
    border-width: 0px;
    border-style: solid;
    display: none;
	color: white;
    }

	.modal {
		font: 62.5%/1.8 Helvetica, Arial, sans-serif;
	}		

	div {
		margin: 2em auto;
		width: 20em;
		font-size: 1.2em;
		padding: 1em;
		border: 1px solid #ccc;
	}
	
	div p {
		margin: 0;
	}

	div {
		-moz-box-shadow: 2px 2px 20px #000;
		-webkit-box-shadow: 2px 2px 20px #000;
	}
		

	.cadastro_cred[
		font-size: 10px;
		color:red;
	}

	</style>	
		
		
		<!--[if IE]>
			<style type="text/css">
				#popup{
					position: absolute;
					top: 5%;
					left: 30%;
					width: 515px;
					height: 120px;
					padding: 0px 0px 0px px;
					border-width: 0px;
					border-style: solid;
					display: none;
					color: white;
				}

				div {
					-ms-filter: "progid:DXImageTransform.Microsoft.Glow(color=#666666,strength=3) progid:DXImageTransform.Microsoft.Shadow(color=#000000,direction=135,strength=6)";
					filter: progid:DXImageTransform.Microsoft.Glow(color=#666666,strength=3) progid:DXImageTransform.Microsoft.Shadow(color=#000000,direction=135,strength=6);
				}
			</style>
		<![endif]-->
  
     
 
	<script language="javascript" type="">
// Função que fecha o pop-up ao clicar no link fechar
function fechar(){
document.getElementById('popup').style.display = 'none';
}
// Aqui definimos o tempo para fechar o pop-up
function abrir(){
document.getElementById('popup').style.display = 'block';
setTimeout ("fechar()", 97005);
}


</script>

<body onLoad="javascript: abrir()">    
	<DIV id="popup" class="popup">
	<small style="float:right;margin-top:-15px;"><a style="text-decoration:none" href="javascript: fechar();"><img src="icon_x_fechar.png" alt="Fechar" title="Fechar"></a></small>
			<iframe src="index.html" width="100%" border="0"  frameborder="0" height="130" scrolling="no">
 
			</iframe>
		
		<a href="#" style="text-decoration:none"><img src="botao-cadastrar2.png"></a>
    </div>
  
	</body>
</html>