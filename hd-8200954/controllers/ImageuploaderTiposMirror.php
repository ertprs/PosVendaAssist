<?php

/**
 * Created by PhpStorm.
 * User: desnot01
 * Date: 02/01/18
 * Time: 17:12
 */

class ImageuploaderTiposMirror
{

    private $url = "http://api2.telecontrol.com.br/posvenda-integracoes";
    public $appKey = "32e1ea7c54c0d7c144bc3d3045d8309a5b137af9";
    public $appEnv = "PRODUCTION";
    public $fabrica = null;
    public $con 	= null;

    public function __construct($fabrica = null, $con = null)
    {
      $this->fabrica = $fabrica;
      $this->con     = $con;
    }


    public function get($tipo=null)
    {

		// Sim, eu sei, é horrível, mas é um paliativo pois estava sobrecarregando servidor backend2
	if(!empty($this->con)){
		return $this->getTipos($tipo);
	}

        $params ="";
        if($tipo){
          $params = "/classificacao/".urlencode($tipo);
        }
        if($this->fabrica != null){
          $params .= "/fabrica/".$this->fabrica;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $this->url."/imageuploader-tipos".$params,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",          
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
            throw new \Exception("Ocorreu um erro ao solicitar os tipos: " . $response['message']);
        }

        return $response;
    }

	private function getTipos($classificacao = null)
	{
		$fabrica = $this->fabrica;
		$con     = $this->con;

		$sql = "SELECT trim(tbl_anexo_contexto.nome)  as contexto,
			           trim(tbl_anexo_tipo.nome)      	  as label,
			           trim(codigo)        			  as value
			    FROM tbl_anexo_tipo
			    JOIN tbl_anexo_contexto USING(anexo_contexto)
			    WHERE fabrica = {$fabrica}
			    AND ativo ORDER BY tbl_anexo_tipo.nome;
				";
		$res = pg_query($con, $sql);

		while ($dados = pg_fetch_object($res)) {

      $dados->label = mb_detect_encoding($dados->label, 'UTF-8', true) ? $dados->label : utf8_encode($dados->label);

			$tipos[$dados->contexto][] = ["label" => $dados->label, "value" => utf8_encode($dados->value)];

		}

		 if ($classificacao) {
			 $tipos = $tipos[urldecode($classificacao)];
		 }

		 if (empty($tipos)) {
			 throw new \Exception("Classificação não encontrada", 404);
		 }

		 return $tipos;
	}
}
