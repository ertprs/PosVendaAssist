<?php
/**
 * Created by PhpStorm.
 * User: desnot01
 * Date: 02/01/18
 * Time: 17:12
 */

class QrCodeMirror
{

    private $url = "https://api2.telecontrol.com.br/qrcode";
    public $appKey = "32e1ea7c54c0d7c144bc3d3045d8309a5b137af9";
    public $appEnv = "PRODUCTION";

    public function __construct($env="production"){
        if($env == "development"){
            $this->appKey =  "fcbe7e2ae586efb6d928a2f254c5c49d07679d22";
            $this->appEnv  =  "HOMOLOGATION";
        }
    }

    public function post($data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url."/qrCode",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Access-Application-Key: ".$this->appKey,
                "Access-Env: ".$this->appEnv,
                "Content-Type: application/json",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, 1);
        if (array_key_exists("exception", $response)) {
            throw new \Exception("Ocorreu um erro ao solicitar o QRCODE: " . $response['message']);
        }

        return $response;
    }
}