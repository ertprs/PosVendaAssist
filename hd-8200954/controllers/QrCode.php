<?php
/**
 +-+-+-+-+-+-+-+-+-+-+
 |D|E|P|R|E|C|A|T|E|D| 
 +-+-+-+-+-+-+-+-+-+-+

  Não utilizar mais essa controller em novas implementações do QR Code!

  Utilizar a QrCodeImageUploader.php
            ------------------------
+-+-+-+-+-+-+-+-+-+-+
 |D|E|P|R|E|C|A|T|E|D|
 +-+-+-+-+-+-+-+-+-+-+
**/

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

if (empty($_REQUEST["fabrica"])) {
include '../autentica_usuario.php';
} else {
$login_fabrica = $_REQUEST["fabrica"];
}

include_once '../class/simple_rest.class.php';


/**
 * Documentação da API:
 * https://tcqrcode.docs.apiary.io/#reference/qrcode
 */

 /**
 * Ao adicionar um novo tipo, alterar os seguintes arquivos:
 * backend2:/var/www/callcenter/src/Tc/Callcenter/Controllers/ImageUploaderCallback.php
 * posvenda:/assist/controllers/QrCode.php
 * posvenda:/assist/plugins/fileuploader/fileuploader-iframe.php
 */
$optionsConfig = array(
	"notafiscal"               => "Nota Fiscal",
	"peca"                     => "Peça",
	"produto"                  => "Produto",
	"assinatura"               => "Assinatura",
	"certificado_aprovacao"    => "Certificado de Aprovação",
	"certificado_participacao" => "Certificado de Participaçao",
	"foto_treinamento"         => "Foto do Treinamento",
	"foto_palestra"            => "Foto da Palestra",
	"foto_lista_presenca"      => "Foto da Lista de Presença",
	"certificado"              => "Certificado",
	"documento"                => "Documento"
);

$ajax = $_REQUEST['ajax'];

$URL = 'https://api2.telecontrol.com.br/qrcode/qrCode';

$apiSettings = array(
    "Content-Type" => "application/json",
    "access-application-key" => "701c59e0eb73d5ffe533183b253384bd52cd6973",
    "access-env" => "PRODUCTION"
);

if ($_serverEnvironment === 'development') {
    $apiSettings = array(
        "Content-Type" => "application/json",
        "access-application-key" => "fcbe7e2ae586efb6d928a2f254c5c49d07679d22",
        "access-env" => "HOMOLOGATION"
    );
    $URL = 'https://api2.telecontrol.com.br/homologation-qrcode/qrCode';
}


switch ($ajax) {
    case 'requireQrCode':

        $options = $_REQUEST['options'];

        foreach ($options as $value) {
            $optionsData[] = array("typeId" => $value, "typeDescription" => $optionsConfig[$value]);
        }

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
            "options" => $optionsData
        ),
        "callback" => "https://api2.telecontrol.com.br/callcenter/imageUploaderCallback"
        );	    

        if (array_key_exists("hash_temp", $_REQUEST)) {
            $body["data"]["hash_temp"] = true;
        }

        $client = new simpleREST($URL);
        $client->addHeader($apiSettings)
            ->setBody($body)
            ->send("POST");


        if($client->response['status'] == 201){
            echo $client->response['body'];
        }else{
            echo json_encode(array("exception" => $client->response['body']));
        }
        exit;

    break;

    default:
        # code...
    break;
}

