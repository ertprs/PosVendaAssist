<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("os", $login_fabrica, true);

if ($_FILES && $_POST["os"]) {
	$types = array("png", "jpg", "jpeg", "bmp");
	$file  = $_FILES[key($_FILES)];
	$os    = $_POST["os"];
	$i     = $_POST["i"];
	$peca_critica    = $_POST["referencia_peca_critica_{$i}"];
	$type  = strtolower(preg_replace("/.+\//", "", $file["type"]));

	if (strlen($file["tmp_name"]) > 0 && $file["size"] > 0) {
		if (!in_array($type, $types)) {
			echo json_encode(array("erro" => utf8_encode("Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp")));
			exit;
		} else {
			
			$s3->tempUpload("peca_critica-{$os}-{$peca_critica}-{$i}", $file, "", "");
			$ext = strtolower(preg_replace("/.+\./", "", basename($file["name"])));

			$file = $s3->getLink("thumb_peca_critica-{$os}-{$peca_critica}-{$i}.{$ext}", true, "", "");
		}
	} else {
		echo json_encode(array("erro" => "Erro ao fazer o upload do arquivo"));
		exit;
	}
}

echo json_encode(array("file" => $file, "i" => $i, "ext" => $ext));

exit;
