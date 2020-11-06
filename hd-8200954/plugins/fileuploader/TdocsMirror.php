<?php
/**
 * Created by PhpStorm.
 * User: desnot01
 * Date: 02/01/18
 * Time: 17:12
 */

class TdocsMirror
{

    private $url = "http://api2.telecontrol.com.br/tdocs";
    private $appKey = "084f77e7ff357414d5fe4a25314886fa312b2cff";
    private $appEnv = "PRODUCTION";

    public function post($file)
    {
        $files = curl_file_create(realpath($file));

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api2.telecontrol.com.br/tdocs/document",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => array(
                'file' => $files,
            ),
            CURLOPT_HTTPHEADER => array(
                "access-application-key: ".$this->appKey,
                "access-env: ".$this->appEnv,
                "appKey: key12a"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

		if(!is_array($response)) {
			$response = json_decode($response, true);
		}

        if (array_key_exists("error", $response)) {
            throw new \Exception("Ocorreu um erro ao efetuar o upload: " . $response['error']);
        }

        return $response;
    }

    public function get($uniqueId){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "http://api2.telecontrol.com.br/tdocs/link/id/".$uniqueId."/permaLink/1",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
        "access-application-key: ".$this->appKey,
        "access-env: ".$this->appEnv,
        ),
    ));
        
        $response = curl_exec($curl);
        
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, 1);

        if (array_key_exists("exception", $response)) {
            throw new \Exception("Ocorreu um erro ao efetuar o upload: " . $response['message']);
        }

        return $response;
    }

    public function duplicate($tdocsId)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api2.telecontrol.com.br/tdocs/duplicate/tdocsId/$tdocsId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array(
                "access-application-key: ".$this->appKey,
                "access-env: ".$this->appEnv,
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, 1);
        if (array_key_exists("exception", $response)) {
            throw new \Exception("Ocorreu um erro ao efetuar o upload: " . $response['message']);
        }

        return $response;
    }
}
