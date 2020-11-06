<?
include "../../dbconfig.php";
include "../../includes/dbconnect-inc.php";
include "../../autentica_admin.php";

$os==$_GET['os'];
if(strlen($os) > 0) {

?>
<link href="stylesheet.css" rel="stylesheet" type="text/css">
</head>
<script type="text/javascript" src="../jquery-1.4.2.js"></script>
<script type="text/javascript" src="../jquery-ui-1.8rc3.custom.js"></script>
<link href="../jquery-ui-1.8rc3.custom.css" rel="stylesheet" type="text/css">

<script type="text/javascript" src="../pixastic/pixastic.core.js"></script>
<script type="text/javascript" src="../pixastic/actions/brightness.js"></script>
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
			slide: function() {
				$("#value-brightness").val(Math.round($("#slider-brightness").slider("value") / 100 * 300) - 150);
			}, value : 50
	}).slider("moveTo", 60);

	$("#slider-contrast").slider({
			slide: function() {
				$("#value-contrast").val( (($("#slider-contrast").slider("value") / 100 * 4)-1).toFixed(1));
			}
	}).slider("moveTo", 1/3*100);
});


</script>
<body onload="loadJPIE('../../../../nf_digitalizada/<?=$os?>.jpg')">
<div id="canvas" >
	<img id="mainImage">
</div>
<br/><br/>

<div id="">
	<span><a href="javascript:rotate();"><img src="../../../imagens/rotate.png" width='30' border='0' alt="Girar a imagem"></a></span>
	<span><a href="javascript:rotate_e();"><img src="../../../imagens/rotatee.png" width='30' border='0' alt="Girar a imagem"></a></span>
	<span><a href="javascript:aumentar();"><img src="../../../imagens/Plus.png" width='30' border='0' alt="Aumentar tamanho da imagem"></a></span>
	<span><a href="javascript:diminuir();"><img src="../../../imagens/reduce.png" width='30' border='0' alt="Diminuir tamanho da imagem"></a></span>
	<span><a href="javascript:voltarTamanho();"><img src="../../../imagens/BACK.png" width='30' border='0' alt="Voltar tamanho original"></a></span>
	<span>
	<div>Brilho: <input type="text" id="value-brightness" value="0" class="demo-input" style="width:30px;"/></div>
	<div id='slider-brightness' class='ui-slider' style="width:150px;margin-top:5px;margin-bottom:5px;">
			<div class="ui-slider-handle"></div><div class="ui-slider-range"></div>
	</div>
	<div>Contraste: <input type="text" id="value-contrast" value="0" class="demo-input" style="width:30px;"/></div>
		<div id='slider-contrast' class='ui-slider' style="width:150px;margin-top:5px;margin-bottom:5px;">
		<div class="ui-slider-handle"></div><div class="ui-slider-range"></div>
	</div>
	<div>
			<input type="button" onclick="alterar();" value="Alterar"/>
			<input type="button" onclick="voltar();" value="Voltar"/>
			<input type="hidden" name='altura' id='altura' value='10'>
			<input type="hidden" name='altura2' id='altura2' value='10'>
	</div>

	</span>
</div>
<script src="includes/scripts/functions.js" language="javascript" type="text/javascript"></script>
<?	include('includes/configure.php');
	$img_size = getImageSize(TMP_IMAGE_PATH . "../../../../nf_digitalizada/$os.jpg");
?>
<script>
function aumentar(){
	<?php echo "imgRatio = " . $img_size[0]/$img_size[1] . ";"; ?>
	var altura = $('#altura').val();

	var new_width =<? echo $img_size[0];?> + parseInt(altura); 
	var new_height= Math.floor(new_width / imgRatio);	

	actualURL = "includes/resize/resize.php?w="+new_width+"&h="+new_height+"&src=" + historyImages[historyPosition];	
	setTimeout("callEffect()", 0);

	var altura2 = parseInt(altura) + 10
	$('#altura').val(parseInt(altura2));
}

function diminuir(){
	<?php echo "imgRatio = " . $img_size[0]/$img_size[1] . ";"; ?>
	var altura_d = $('#altura2').val();

	var new_width_d =<? echo $img_size[0];?> - parseInt(altura_d); 
	var new_height_d= Math.floor(new_width_d / imgRatio);	

	actualURL = "includes/resize/resize.php?w="+new_width_d+"&h="+new_height_d+"&src=" + historyImages[historyPosition];	
	setTimeout("callEffect()", 0);

	var altura_d2 = parseInt(altura_d) + 10
	$('#altura2').val(parseInt(altura_d2));
}

function voltarTamanho(){
	<?php echo "imgRatio = " . $img_size[0]/$img_size[1] . ";"; ?>

	var new_width_d =<? echo $img_size[0];?>;
	var new_height_d=<? echo $img_size[1];?>;

	actualURL = "includes/resize/resize.php?w="+new_width_d+"&h="+new_height_d+"&src=" + historyImages[historyPosition];	
	setTimeout("callEffect()", 0);
}

</script>

</body>
<?}?>
