<?
include "../../dbconfig.php";
include "../../includes/dbconnect-inc.php";
include "../../autentica_admin.php";

include '../../../anexaNF_inc.php';
$os==$_GET['os'];
if(strlen($os) > 0) {
	$link_imgNF = temNF($os, 'url');
	$link_imgNF = $link_imgNF[0];
?>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script src="https://code.jquery.com/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="../jquery-ui-1.8rc3.custom.js"></script>
<link href="../jquery-ui-1.8rc3.custom.css" rel="stylesheet" type="text/css">

<script type="text/javascript" src="../pixastic/pixastic.core.js"></script>
<script type="text/javascript" src="../pixastic/actions/brightness.js"></script>
<style type="text/css">
body {
	margin:auto;
	text-align:center;
	font:normal normal 12px Verdana, Arial, Helvetica, sans-serif;
}
img {
	position:relative;
	margin-left: 1em;
	cursor:pointer;
	transition: all 0.5s ease-out;
	-o-transition: all 0.5s ease-out;
	-moz-transition: all 0.5s ease-out;
	-webkit-transition: all 0.5s ease-out;
}
label {
	text-align:right;
	display:inline-block;
	width:75px;
	_zoom:1;
	*zoom:1;
}
img.rotateLeft {
          transform: rotate(-90deg);  
       -o-transform: rotate(-90deg);  /* Opera 10.5 */
     -moz-transform: rotate(-90deg);  /* FF3.5+ */
  -webkit-transform: rotate(-90deg);  /* Saf3.1+, Chrome */
             filter: progid:DXImageTransform.Microsoft.Matrix(/* IE6.IE8 */ 
                     M11=6.123031769111886e-17, M12=1, M21=-1, M22=.2246063538223773e-16, sizingMethod='auto expand');
      -ms-transform: rotate(-90deg);  /* IE9 */
               zoom: 1; /*IE*/
}
img.rotateRight {
          transform: rotate(90deg);  
       -o-transform: rotate(90deg);  /* Opera 10.5 */
     -moz-transform: rotate(90deg);  /* FF3.5+ */
  -webkit-transform: rotate(90deg);  /* Saf3.1+, Chrome */
             filter: progid:DXImageTransform.Microsoft.Matrix(/* IE6.IE8 */ 
                     M11=6.123031769111886e-17, M12=-1, M21=1, M22=6.123031769111886e-17, sizingMethod='auto expand');
      -ms-transform: rotate(90deg);  /* IE9 */
               zoom: 1; /*IE*/
}
img.rotate180 {
          transform: rotate(180deg);  
       -o-transform: rotate(180deg);  /* Opera 10.5 */
     -moz-transform: rotate(180deg);  /* FF3.5+ */
  -webkit-transform: rotate(180deg);  /* Saf3.1+, Chrome */
             filter: progid:DXImageTransform.Microsoft.Matrix(/* IE6.IE8 */ 
                     M11=-1, M12=-1.2246063538223773e-16, M21=1.2246063538223773e-16, M22=-1, sizingMethod='auto expand');
      -ms-transform: rotate(180deg);  /* IE9 */
               zoom: 1; /*IE*/
}
#buttons {
	text-align:center;
	position:fixed;
	top: -1px;
	left: 20%;
	border: 1px solid darkgrey;
	margin:auto;
	background-color: white;
	background-color: rgba(255, 255, 255, 0.7);
	width: 60%;
	min-width: 400px;
	height: 26px;
	overflow-y: hidden;
	z-index: 500;
	border-radius: 0 0 5px 5px;
	-moz-border-radius: 0 0 5px 5px;
	box-shadow: 0 2px 5px grey;
	-moz-box-shadow: 0 2px 5px grey;
	-webkit-box-shadow: 0 2px 5px grey;
	transition: all 0.5s ease-out;
	-o-transition: all 0.5s ease-out;
	-moz-transition: all 0.5s ease-out;
	-webkit-transition: all 0.5s ease-out;
}
#buttons:hover {
	background-color: white;
	/*height: 105px;*/
}
#canvas {
	position: relative;
	top: 7em;
	margin: auto;
}

#canvas img {z-index: 1;}
</style>
<script>
function voltar() {
	Pixastic.revert(document.getElementById("mainImage"));
}

function alterar() {
	Pixastic.process(document.getElementById("mainImage"), "brightness", {
		brightness : $("#value-brightness").val(),
		contrast : $("#value-contrast").val(),
		legacy : $("#value-legacy").attr("checked")
	});
}

$(document).ready(function(){
	$("#slider-brightness").slider({
			slide: function(e, brUI) {
				$("#value-brightness").val(brUI.value);
			},
			value:   0,
			min:  -128,
			max:   128
	}).slider("moveTo", 0);
	$("#value-brightness").change(function() {
		$("#slider-brightness").slider('value', $(this).val());
	});
	$("#value-brightness").val($("#slider-brightness").slider('value'));

	$("#slider-contrast").slider({
			slide: function(e, crUI) {
				$("#value-contrast").val((crUI.value / 10).toFixed(1));
			},
			value:  0,
			min:  -10,
			max:   10
	}).slider("moveTo", 0);
	$("#value-contrast").change(function() {
		$("#slider-contrast").slider('value', $(this).val()*10);
	});
	$('#slider-size').slider({
		slide: function(e, ui) {
			if (ui.value < 25) ui.value = 25;
			$('#mainImage').css('width', ui.value+'%');
			$('#value-size').text(ui.value+'%');
		},
		min:      0,
		max:	200,
		step:     5,
		value:  100,
		range:   25
	});	
	$('#RtLeft').click(function() {
		$('#mainImage').removeClass('rotateLeft rotateRight rotate180 rotate360').addClass('rotateLeft');
	});
	$('#pontaCabeca').click(function() {
		var classe = $("#mainImage").attr("class");
		if(classe == "rotate180"){
			$('#mainImage').removeClass('rotateLeft rotateRight rotate180 rotate360').addClass('rotate360');
		}else{
			$('#mainImage').removeClass('rotateLeft rotateRight rotate180 rotate360').addClass('rotate180');
		}
	});
	
	$('#RtRight').click(function() {
		$('#mainImage').removeClass('rotateLeft rotateRight rotate180 rotate360').addClass('rotateRight');
	});
	$('#ResetSize').click(function() {
/*		var imgSource = $('#mainImage').attr('src');
		$('#mainImage').remove();
		$('#canvas').html('<img src="'+imgSource+'"');*/
		$('#mainImage').css('width', '100%').removeClass('rotateLeft rotateRight rotate180');
		$('#value-size').text('auto');
		$("#slider-size").slider('value', 100);
		//$('#mainImage').removeAttr('style');
	});
});
</script>
<body >
<div id="buttons">
	<label title='Percentagem referente à largura da janela'>Tamanho: </label>
	<span id="value-size" style="width:50px;text-align:right;font-weight:bold;display:inline-block;_zoom:1">auto</span>
	<span id='slider-size' style="_zoom:1;width:150px;margin-top:8px;display:inline-block;*zoom:1"></span>
	<img src="../../../imagens/BACK.png" width='15' border='0' alt="Voltar tamanho original" title="Voltar tamanho original" id='ResetSize'>
	<img src="../../../imagens/rotate.png"  width='15' border='0' alt="Girar a imagem" title="Girar a imagem à esq. 90º" id='RtLeft'>
	<img src="../../../imagens/rotatee.png" width='15' border='0' alt="Girar a imagem" title="Girar a imagem à dir. 90º" id='RtRight'>
	<img src="../../../imagens/rotate180.png" width='20' border='0' alt="Girar a imagem" title="Girar a imagem 180º" id='pontaCabeca'>
	<hr width='360' />
	<div style='display:none'>	
		<label for=''>Brilho: </label>
		<input type="text" id="value-brightness" value="0" class="demo-input" style="width:30px;" />
		<span id='slider-brightness' style="width:256px;margin-top:8px;display:inline-block;*zoom:1"></span>
		<br />
		<label for=''>Contraste: </label>
		<input type="text" id="value-contrast" value="0" class="demo-input" style="width:30px;" />
		<span id='slider-contrast' style="_zoom:1;width:100px;margin-top:8px;display:inline-block;*zoom:1"></span>
		<span style="width:154px;display:inline-block;_zoom:1">
			<input type="button" onclick="alterar();" value="Alterar" />
			<input type="button" onclick="voltar();"  value="Voltar" />
		</span>
		<br />
	</div>
	<hr width='360' />
</div>
<br />
<div id="canvas">
<img id="mainImage" src='<?=$link_imgNF?>'>
</div>
<p>&nbsp;</p>

<script src="includes/scripts/functions.js" language="javascript" type="text/javascript"></script>
<?	include('includes/configure.php');
	$img_size = getImageSize(TMP_IMAGE_PATH . $link_imgNF);
?>

</body>
<?}?>
