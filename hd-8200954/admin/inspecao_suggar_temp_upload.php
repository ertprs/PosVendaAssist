<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'autentica_usuario.php';

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$s3 = new AmazonTC("inspecao", $login_fabrica);

if ($_FILES) {
	$types 			  = array("png", "jpg", "jpeg", "bmp");
	$file  			  = $_FILES[key($_FILES)];
	$i     			  = $_POST["i"];
	$nome_arquivo     = $_POST["nome_arquivo"];


	$type  = strtolower(preg_replace("/.+\//", "", $file["type"]));

	if ($type == "jpeg") {
		$type = "jpg";
	}

	if (strlen($file["tmp_name"]) > 0 && $file["size"] > 0) {
		if (!in_array($type, $types)) {
			echo json_encode(array("erro" => utf8_encode("Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp")));
			exit;
		} else {
			if($s3->ifObjectExists($nome_arquivo.".".$type,true)){
				$s3->deleteObject($nome_arquivo.".".$type,true);
			}

			$s3->tempUpload("{$nome_arquivo}", $file);

			$file = $s3->getLink("thumb_".$nome_arquivo.".".$type."", true);			
			$file_original = $s3->getLink($nome_arquivo.".".$type."", true);			

			$caminho = pathinfo($file_original);
			$caminho = $caminho['filename'].".".$type;
			

		}
	} else {
		echo json_encode(array("erro" => "Erro ao fazer o upload do arquivo"));
		exit;
	}
}

echo json_encode(array("file" => $file,"nome" => $caminho, "i" => $i, "ext" => $type));

exit;