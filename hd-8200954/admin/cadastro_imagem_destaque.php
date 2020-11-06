<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
//include 'includes/image_resize.php';

	$layout_menu = "cadastro";
	$titulo = "Cadastramento de Defeitos Constatados";
	include 'cabecalho.php';


if ($btnacao == "gravar"){

	$cfgImagens['tamanho'] = "10000"; // em bytes
	$cfgImagens['largura'] = "194";
	$cfgImagens['altura']  = "250";
	$cfgImagens['dir']     = "../img_destaque/";
	$fImg = $_FILES["arquivo"]["tmp_name"];
	$fImg_type= $_FILES["arquivo"]["type"];
	$fImg_size= $_FILES["arquivo"]["size"];
	$fImg_name = strtolower($_FILES["arquivo"]["name"]);
	
		// salva a foto se existir
		if ($fImg == 'none'){
			$saveImg = "";

		}else{
	
			// Para verificar as dimensões da imagem
			$tamanhos = getimagesize($fImg);

			// Verifica largura

			$largura_original = $tamanhos[0];
			$altura_original  = $tamanhos[1];
			if($largura_original > $cfgImagens['largura']){
				$altura = ($altura_original * $cfgImagens['largura']) / $largura_original ;

				// pega extensao da imagem
				$extensao_arquivo = explode(".", $fImg_name);
				$totalExt_arquivo = count($extensao_arquivo);
				for($x=0; $x < $totalExt_arquivo; $x++)
				{
					$_extensao_arquivo = $extensao_arquivo[$x];
				}

			 if ($_extensao_arquivo=="jpeg" || $_extensao_arquivo=="jpg")
				{
					$thumb = ImageCreate($cfgImagens['largura'],$altura);
					$origem = ImageCreateFromJpeg("$fImg_name");
	            	ImageCopyResized($thumb,$origem,0,0,0,0,$cfgImagens['largura'],$altura,ImageSX($origem),ImageSY($origem));
					ImageJpeg($thumb);
			}
			elseif($_extensao_arquivo=="gif") {
					$thumb = ImageCreate($cfgImagens['largura'],$altura);
					$origem = ImageCreateFromgif("$fImg_name");
	            	ImageCopyResized($thumb,$origem,0,0,0,0,$cfgImagens['largura'],$altura,ImageSX($origem),ImageSY($origem));
					Imagegif($thumb);
			}

			elseif($_extensao_arquivo=="png") {
					$thumb = ImageCreate($cfgImagens['largura'],$altura);
					$origem = ImageCreateFrompng("$fImg_name");
	            	ImageCopyResized($thumb,$origem,0,0,0,0,$cfgImagens['largura'],$altura,ImageSX($origem),ImageSY($origem));
					Imagepng($thumb);
			}

			elseif($_extensao_arquivo=="bmp") {
					$thumb = ImageCreate($cfgImagens['largura'],$altura);
					$origem = ImageCreateFromWbmp("$fImg_name");
	            	ImageCopyResized($thumb,$origem,0,0,0,0,$cfgImagens['largura'],$altura,ImageSX($origem),ImageSY($origem));
					Imagewbmp($thumb);
			}

			}
			####################################################
			
			$erro = "";

			if(!preg_match("/^image\/(pjpeg|jpeg|png|gif|bmp)$/", $fImg_type)){
				$erro .= "Arquivo em formato inválido! A imagem deve ser jpg, jpeg, bmp, gif ou png. Envie outro arquivo<br>";
			}else{
				// Verifica tamanho do arquivo

				if($fImg_size > $cfgImagens['tamanho'])
//				if(ImageJpeg($thumb) > $cfgImagens['tamanho'])
					$erro .= "Arquivo em tamanho acima do permitido ! A imagem deve ter no máximo " . $cfgImagens['tamanho'] . " bytes. Envie outro arquivo <br>";
			}
		########################################################################################

			if (strlen($erro)==0)
			{
				// pega extensao da imagem
				$extensao = explode(".", $fImg_name);
				$totalExt = count($extensao);
				for($x=0; $x < $totalExt; $x++)
				{
					$_extensao = $extensao[$x];
				}
				$saveImg = $login_fabrica . "_destaque.".$_extensao; // login
				$caminho_arquivo=$saveImg;

				if(file_exists($cfgImagens['dir'].$saveImg))
					unlink($cfgImagens['dir'].$saveImg);

				if(copy($fImg, $cfgImagens['dir'].$saveImg)) 
					unlink($fImg);
				else
					$erro = "Erro ao copiar arquivo...";
			}
		}
		########################################################################################


}
?>

<html>
<head>
<title>Telecontrol - Cadastro de Imagem em Destaque</title>

</head>

<body>
<p>
<div id='wrapper'>
<form name="frm_envio" method="post" action="<? $PHP_SELF ?>" enctype=multipart/form-data>

<? if (strlen($erro) > 0) { ?>

<div id="wrapper">
	<b><? echo $erro; ?></b>
</div>

<? } ?>

<div id="wrapper">
	<div id="middleCol" style="width: 200px; ">
		<b>Selecione a Imagem Desejada</b>
	</div>
</div>

<div id='middleCol' style='width: 20px; '>
	<input type="file" name="arquivo" size="30">
</div>

</div>

<p>
<p>

<div id='wrapper'>
	<input type='hidden' name='btnacao' value=''>
	<a href='#'><IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_envio.btnacao.value == '' ) { document.frm_envio.btnacao.value='gravar' ; document.frm_envio.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'></a>

</div>

<p>

<div id="subBanner">
</div>

	<b>Imagem Atual</b><br>
	<b><? echo "<img src='../img_destaque/$caminho_arquivo'>"; ?></b>





</form>
</div>
<?
	include "rodape.php";
?>
</body>
</html>