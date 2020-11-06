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
	$k     = $_POST["k"];
	$year  = $_POST["year"];
	$month = $_POST["month"];
	$peca  = $_POST["peca"];
	$type  = strtolower(preg_replace("/.+\//", "", $file["type"]));

	if ($type == "jpeg") {
		$type = "jpg";
	}

	if (strlen($file["tmp_name"]) > 0 && $file["size"] > 0) {
		if (!in_array($type, $types)) {
			echo json_encode(array("erro" => utf8_encode("Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp")));
			exit;
		} else {
			$s3->tempUpload("{$os}-{$peca}-{$i}-{$k}", $file, $year, $month);

			$file = $s3->getLink("thumb_{$os}-{$peca}-{$i}-{$k}.{$type}", true, $year, $month);
		}
	} else {
		echo json_encode(array("erro" => "Erro ao fazer o upload do arquivo"));
		exit;
	}
}

echo json_encode(array("file" => $file, "i" => $i, "k" => $k, "ext" => $type));

exit;