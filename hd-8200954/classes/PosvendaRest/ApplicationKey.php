<?php 
namespace PosvendaRest;

class ApplicationKey
{
    public function getApplicationKeyByFabrica($fabrica)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/AccessControl/application-key/client_code/".$fabrica,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "postman-token: 7b8f8d50-ee77-cd29-274b-6e9b2cc6df98"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        if ($err) {
            return $this->trataRetorno($err);
        } else {
            return $this->trataRetorno($response);
        }

    }

    private function trataRetorno($dados) 
    {
        global $_serverEnvironment;
        $dados = json_decode($dados,1);

        if (isset($dados["exception"]) && strlen($dados["exception"]) > 0) {
            return $dados;
        }

        foreach ($dados as $key => $value) {
            $retorno[$value["application"]["system_code"]][$value["key_type"]["system_code"]] = $value["application_key"];
        }

        if ($_serverEnvironment == "development") {
            return [
                "Access-Application-Key" => $retorno["POSVENDA-MESTRE"]["HOMOLOGATION"], 
                "Access-Env" => "HOMOLOGATION"
            ];
        } else {
            return [
                "Access-Application-Key" => $retorno["POSVENDA-MESTRE"]["PRODUCTION"], 
                "Access-Env" => "PRODUCTION"
            ];
        }

    }

}