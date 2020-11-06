<?php
// HD 3741276 - QRCode
if (!function_exists('isFabrica')) {
    include 'funcoes.php';
}
$fabricas_image_uploader = !isFabrica(3,11,85,87,125,126,137,172) && !$novaTelaOs;

if ($login_posto == 6359 and $fabricas_image_uploader) {
    include_once 'class/simple_rest.class.php';

    $qrCodeSize = getValorFabrica([
            0 => '4cm',
            1 => '3cm',
        ], $login_fabrica
    );


    $optionsData = array(
        0 => ['typeId' => 'notafiscal', 'typeDescription' => traduz('nota.fiscal')],
        1 => ['typeId' => 'osproduto',  'typeDescription' => traduz('Foto do Produto')]
    );

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

    $body = array(
        "data" => array(
            "objectId" => $os,
            "title"    => traduz('anexos') . " $sua_os",
            "uploader" => array(
                "clientCode" => $login_fabrica,
                "posto"      => $login_posto
            ),
            "cameraOnly" => false,
            "options"    => $optionsData
        ),
        'expires' => 21600, // 15 dias
        "callback" => "https://api2.telecontrol.com.br/callcenter/imageUploaderCallback"
    );

    $QRC = new simpleREST($URL, 'POST');

    $QRC->addHeader($apiSettings)
        ->setBody($body)
        ->send();

    if ($QRC->response['status'] == 201) {
        $qrCode = $QRC->getBody('object');

        if (strlen($qrCode->qrcode) > 100) {
            echo <<<QRCODE
        <hr width="100%" style="margin:0.7cm 2cm 0 0" />
        <table style="text-align: center;width: 650px;margin:1ex 0 0 0">
           <tr>
               <td><h4>Leia o QRCode com o <em>ImageUploader</em> para anexar suas fotos à OS</h4></td>
               <td><img src="{$qrCode->qrcode}" style="width:$qrCodeSize"></td>
           </tr>
        </table>
QRCODE;
        }
    } else {
        if ($_serverEnvironment === 'development') {
            echo json_encode(array("exception" => $QRC->response['body']));
        }
    }
}
/* End of file os_print_qrcode.php */
