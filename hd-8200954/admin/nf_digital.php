<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "financeiro";
include "autentica_admin.php";

$os==$_GET['os'];
if(strlen($os) > 0) {
?>
<link href="js/jquery-ui-1.8rc3.custom.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8rc3.custom.js"></script>
<script type="text/javascript" src="js/pixastic/pixastic.core.js"></script>
<script type="text/javascript" src="js/pixastic/actions/brightness.js"></script>
<script type="text/javascript" src="js/pixastic/actions/rotate.js"></script>

<script>
function voltar() {
	Pixastic.revert(document.getElementById("mainImage"));
}

function alterar() {
	Pixastic.process(document.getElementById("mainImage"), "brightness", {
		brightness : $("#value-brightness").val(),
		contrast : $("#value-contrast").val()
	});
}

function rotate(){
	actualURL = "js/jpie/includes/rotate/rotate.php?x=90&src=<?=$os?>.jpg"
	setTimeout("callEffect()", 0);
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
<body onload="loadJPIE('../../nf_digitalizada/<?=$os?>.jpg')">

<div id="">
	<div style='float:left'>
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
	</div>
	</div>
	<span><a href="javascript:rotate();"><img src="../imagens/rotate.png" border='0' width='30' alt="Girar a imagem"></a></span>
	<span><a href="javascript:resize();"><img src="js/jpie/icons/resize.gif" alt="Alterar tamanho da imagem"></a></span>

</div>
<br/><br/>
<div id="canvas" >
	<img id="mainImage">
</div>
<script type="text/javascript" src="js/jpie/includes/scripts/functions.js"></script>


</body>

<?}?>
