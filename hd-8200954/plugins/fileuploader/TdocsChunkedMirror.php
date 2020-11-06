<?php

class TdocsChunkedMirror
{

    private $url = "http://api2.telecontrol.com.br/tdocs";
    private $appKey = "084f77e7ff357414d5fe4a25314886fa312b2cff";
    private $appEnv = "PRODUCTION";

    public function post($file, $uploadId, $part, $lastPart, $metadata)
    {
        $files = curl_file_create(realpath($file));
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api2.telecontrol.com.br/api-tdocs-v2/chunked",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => array(
                'file' => $files,
                'uploadId' => $uploadId,
                'part' => $part,
                'lastPart' => $lastPart,
		        'metadata' => $metadata
            ),
            CURLOPT_HTTPHEADER => array(
                "access-application-key: ".$this->appKey,
                "access-env: ".$this->appEnv,
                "appKey: key12a"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if (array_key_exists("error", json_decode($response))) {
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
			CURLOPT_PUT => true,
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

    public function put($nome)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api2.telecontrol.com.br/api-tdocs-v2/chunked",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode(array(
		        'name' => $nome
            )),
            CURLOPT_HTTPHEADER => array(
                "access-application-key: ".$this->appKey,
                "access-env: ".$this->appEnv,
                "appKey: key12a",
				"Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        if (array_key_exists("error", json_decode($response))) {
            throw new \Exception("Ocorreu um erro ao efetuar o upload: " . $response['error']);
        }

        return $response;
    }
}
