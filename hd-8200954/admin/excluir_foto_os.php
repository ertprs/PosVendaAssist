<?
$Destino  = "/www/assist/www/nf_digitalizada/";
$DestinoP = "/www/assist/www/nf_digitalizada/";

$excluir_foto=$_GET['excluir_foto'];
$qual        =$_GET['qual'];
$os          =$_GET['os'];

	if($qual=='1'){
		$foto=$excluir_foto . ".jpg";
		$foto_thumb=$excluir_foto . "_thumb".".jpg";
		@unlink($Destino . $foto);
		@unlink($DestinoP . $foto_thumb);
	}
	if($qual=='2'){
		$foto=$excluir_foto . "-2.jpg";
		$foto_thumb=$excluir_foto . "_thumb"."-2.jpg";
		@unlink($Destino . $foto);
		@unlink($DestinoP . $foto_thumb);
	}
header("location:os_press.php?os=$os");
?>