<?php
/**
 * Created by PhpStorm.
 * User: desnot01
 * Date: 02/01/18
 * Time: 17:12
 */

class ComunicatorMirror
{

    private $url = "http://api2.telecontrol.com.br/communicator";
    private $appKey = "3c8f3fbd89576e1116c185dc31302be433c577c0";
    private $appEnv = "PRODUCTION";

    public function post($to, $subject, $body, $account='noreply@tc', $from='noreply@telecontrol.com.br', $cc = null)
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url."/email",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(array(
                "reference" => ["type" => 'comunicado', "value" => 'posvenda'],
                "from" => $from,
                "to" => $to,
                "cc" => $cc,
                "subject" => $subject,
                "body" => $body
            )),
            CURLOPT_HTTPHEADER => array(
                "access-application-key: ".$this->appKey,
                "access-env: ".$this->appEnv,
                "Content-Type: application/json",
                "smtp-account: ".$account
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, 1);
        if (array_key_exists("exception", $response)) {
            throw new \Exception("Ocorreu um erro ao efetuar o envio do email: " . $response['message']);
        }
        return $response;
    }
}
