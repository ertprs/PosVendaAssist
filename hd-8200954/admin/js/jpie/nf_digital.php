<?
include "../../dbconfig.php";
include "../../includes/dbconnect-inc.php";
include "../../autentica_admin.php";

include '../../../anexaNF_inc.php';
$os==$_GET['os'];
if(strlen($os) > 0) {
	$link_imgNF = end(temNF($os, 'url'));
	$link_imgNF = '../../../../' . substr($link_imgNF, strpos($link_imgNF, 'nf'));
?>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script type="text/javascript" src="/js/jquery.min.js"></script>
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
	margin-left: 1em;
	cursor:pointer;
}
label {
	text-align:right;
	display:inline-block;
	width:75px;
	_zoom:1;
	*zoom:1;
}

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
		max:    150,
		step:     5,
		value:  100,
		range:   25
	});	
	$('#RtLeft').click(function() {
		rotate();
	});
	$('#RtRight').click(function() {
		rotate_e();
	});
	$('#ResetSize').click(function() {
/*		var imgSource = $('#mainImage').attr('src');
		$('#mainImage').remove();
		$('#canvas').html('<img src="'+imgSource+'"');*/
		$('#mainImage').css('width', 'auto');
		$('#value-size').text('auto');
		//$('#mainImage').removeAttr('style');
	});
});
</script>
<body onload="loadJPIE('<?=$link_imgNF?>')">
<div id="buttons">
	<img src="../../../imagens/rotate.png"  width='15' border='0' alt="Girar a imagem" title="Girar a imagem" id='RtLeft'>
	<label title='Percentagem referente à largura da janela'>Tamanho: </label>
	<span id="value-size" style="width:50px;text-align:right;font-weight:bold;display:inline-block;_zoom:1">auto</span>
	<span id='slider-size' style="_zoom:1;width:150px;margin-top:8px;display:inline-block;*zoom:1"></span>
	<img src="../../../imagens/BACK.png" width='15' border='0' alt="Voltar tamanho original" title="Voltar tamanho original" id='ResetSize'>
<img src="../../../imagens/rotatee.png" width='15' border='0' alt="Girar a imagem" title="Girar a imagem" id='RtRight'>
	<hr width='360' />
	<div>	
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
	<img id="mainImage">
</div>
<p>&nbsp;</p>

<script src="includes/scripts/functions.js" language="javascript" type="text/javascript"></script>
<?	include('includes/configure.php');
	$img_size = getImageSize(TMP_IMAGE_PATH . $link_imgNF);
?>

</body>
<?}?>
