<?php
$video = $_GET['video'];
if(strlen($video)==0): ?>
<HTML>
<HEAD>
	<META http-equiv='content-type' content='text/html; charset=windows-1252'>
	<TITLE>Vídeo</TITLE>
</HEAD>
<BODY>
<script type="text/javascript">
	alert("Não há vídeo anexado");
	window.close();
</script>
<BODY>
<HTML><?
endif;

$video = str_replace("/watch?v=", "/embed/", urldecode($video)); // Converte a URL direta para assistir o vídeo para a URL do objeto

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
	<HEAD>
		<META content='HTML Tidy for Windows (vers 15 August 2007), see www.w3.org'
				 name='generator'>
		<META http-equiv='content-type' content='text/html; charset=windows-1252'>
		<TITLE>Vídeo</TITLE>
        <style type="text/css">
			body {
				background-color: #6b7290;
				font-family: Arial,sans-serif;
				font-size: 8pt;
				background-image: -webkit-gradient(linear, left top, left bottom, from(#6b8ab6), to(#6b8ab6), color-stop(45%, #2b405b));
				filter: progid:DXImageTransform.Microsoft.Gradient(enabled="true",startColorStr=#2b405b,endColorStr=#6b8ab6);
				margin: 2px 0;
				padding: 0;
				height: 100%;
                vertical-align: middle;
			}
			#fechar {
				position:absolute;
				top:3px;	right:  1em;
				width: 6em; height: 2em;
                text-align: right;
                padding-top: 4px;
				background-color:#CCC;
				border: 1px solid #999;
				cursor: pointer;
				vertical-align: baseline;
			}
			#btn {
				border: 2px outset #666;
				background-color: #999;
				padding: 0 3px;
                border-radius: 6px;
                	-moz-border-radius: 6px;
                	-webkit-border-radius: 6px;
                	-o-border-radius: 6px;
			}
        </style>
	</HEAD>
<BODY>
<script type="text/javascript">
	window.resizeTo(650,550);
	window.moveTo(eval((screen.width/2)-275),eval((screen.height/2)-225));
</script>
	<!--<OBJECT width='445' height='364'>
		<PARAM name='movie'
			  value='<?=$video?>&hl=pt-br&fs=1&rel=0&color1=0x2b405b&color2=0x6b8ab6&border=1'></PARAM>
		<PARAM name='allowFullScreen'   value='true'></PARAM>
		<PARAM name='allowscriptaccess' value='always'></PARAM>
		<EMBED  src='<?=$video?>&hl=pt-br&fs=1&rel=0&color1=0x2b405b&color2=0x6b8ab6&border=1'
			   type='application/x-shockwave-flash' allowscriptaccess='always'
			   width='445' height='364'			 	  allowfullscreen='true'>
		</EMBED>
	</OBJECT>-->
	<iframe width="630" height="540" src="<?=$video?>?showinfo=0" frameborder="0" allowfullscreen></iframe>
	<DIV id='fechar' onClick='javascript:window.close();'>Fechar <SPAN id='btn'>X</SPAN>&nbsp;
	</DIV>
  </BODY>
</HTML>
