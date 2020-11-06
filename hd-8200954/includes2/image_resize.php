<?
$foto = $_GET['foto'];

$largura = $_GET['largura'];
if (strlen ($largura) == 0) $largura = 50;

$tamanho = GetImageSize($foto);
$largura_original = $tamanho[0];
$altura_original  = $tamanho[1];

$altura = $altura_original * $largura / $largura_original ;

$thumb = ImageCreateTrueColor($largura,$altura);
$origem = ImageCreateFromJpeg("$foto");
ImageCopyResized($thumb,$origem,0,0,0,0,$largura,$altura,ImageSX($origem),ImageSY($origem));
ImageJpeg($thumb);
?>
