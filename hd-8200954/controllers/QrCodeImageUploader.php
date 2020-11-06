<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'QrCodeMirror.php';
include 'ImageuploaderTiposMirror.php';

if (empty($_REQUEST["fabrica"])) {
	include '../autentica_usuario.php';
} else {
	$login_fabrica = $_REQUEST["fabrica"];
}

$url = 'https://api2.telecontrol.com.br/qrcode/qrCode';

$ajax = $_REQUEST['ajax'];
$qrCodeMirror = new QrCodeMirror($_serverEnvironment);

if (in_array($login_fabrica, array(152,177))){
	$imageuploaderTiposMirror = new ImageuploaderTiposMirror($login_fabrica);
}else{
	$imageuploaderTiposMirror = new ImageuploaderTiposMirror();
}

try{
    $comboboxContext = $imageuploaderTiposMirror->get($_REQUEST['contexto']);
}catch(\Exception $e){    
    $comboboxContext = [];
}

$options = [];
foreach ($comboboxContext as $key => $value) {
	$option['typeId'] = $value['value'];
	$option['typeDescription'] = $value['label'];

	$options[] = $option;
}

switch ($ajax) {
	case 'requireQrCode':
		$body = array(
			"data" => array(
				"objectId" =>$_REQUEST['objectId'],
				"title" => $_REQUEST['title'],
				"uploader" => array(
					"clientCode" => $login_fabrica,
					"posto" => $login_posto,
					"contexto" => $_REQUEST["contexto"]
				),
				"cameraOnly" => false,  
				"options" => $options,
				"hashTemp" => $_REQUEST['hashTemp'],
				"print" => !empty($_REQUEST['print']) ? $_REQUEST['print'] : "false"
			),			
			"env" => $_serverEnvironment,
			"callback" => "https://api2.telecontrol.com.br/posvenda-integracoes/imageuploader?sistema=POSVENDA"
		);
		
		try{			
			$response = $qrCodeMirror->post($body);			
		}catch(\Exception $e){
			$response['exception'] = $e->getMessage();
		}
		header("Content-Type: application/json");
		echo json_encode($response);
		exit;
		break;
	
	default:
		# code...
		break;
}


http://api2.telecontrol.com.br/qrcode/qr-code/uid/16e3920ffda1974e1508b813385e075c9a8852f723318affb506d7680a6813a8#HOMOLOGATION














