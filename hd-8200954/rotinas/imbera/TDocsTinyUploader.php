<?php

/**
 * Created by PhpStorm.
 * User: desnot01
 * Date: 22/07/16
 * Time: 16:29
 */
class TDocsTinyUploader
{

    public function sendFile($path)
    {
#        file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Upload de arquivo pro TDOCS " . $path . "  \n", FILE_APPEND);

        $curl = curl_init();

        $file = realpath($path);

        $post = array("file" => '@' . $path);

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api2.telecontrol.com.br/tdocs/document",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => array(
                "access-application-key: 32e1ea7c54c0d7c144bc3d3045d8309a5b137af9",
                "access-env: PRODUCTION",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("Ocorreu um erro ao realizar o upload " . $err);
        } else {
 #           file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " \n " . $response . "  \n", FILE_APPEND);

            return json_decode($response,true);
        }
    }
}
