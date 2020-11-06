<?php

namespace Posvenda\Cockpit;

class Api
{
    /**
     * @var string
     */
    private $env = 'production';

    /**
     * @var string
     */
    private $apiResource;

    public $response;

    /**
     * @param string $env
     * @return Cockpit
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @param string $resource
     * @return Cockpit
     */
    public function setApiResource($resource)
    {
        $this->apiResource = $resource;

        return $this;
    }

    /**
     * @return array
     */
    public function getApi()
    {
    	include "/etc/telecontrol.cfg";

        if ($_serverEnvironment == "production") {
    		$this->env = "production";
    	} else {
    		$this->env = "devel";
    	}

        $url = 'https://api2.telecontrol.com.br/' . $this->apiResource;

        $appKey = array(
            'devel' => '519e67fe737c5de1c5656f1c08f9eac902c5eb25',
            'homologation' => '0ca6be6c7a41b7bfc084f8f140105ba7bb469824',
            'production' => '3bee2ca5a245f8b278ed3da48361250d6c45fb81'
        );

        return array(
            'url' => $url,
            'app-key' => $appKey[$this->env]
        );
    }

    public function curlDelete($url, array $headers) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        $this->response = $result;

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode != 204) {
            return json_decode($result, true);
        }

        return true;
    }

    public function curlPut($url, array $headers, $dados) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);

        $result = curl_exec($ch);

        curl_close($ch);

        $this->response = $result;

        return json_decode($result, true);
    }

    /**
     * @param string $url
     * @param array $headers
     * @param mixed $dados
     * @return array
     */
    public function curlPost($url, array $headers, $dados)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);

        $result = curl_exec($ch);

        $this->response = $result;

        curl_close($ch);

        return json_decode($result, true);
    }
    
    public function curlGet($url, array $headers)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        $this->response = $result;

        curl_close($ch);

        return json_decode($result, true);
    }
}
