<?php
/**
 *
 * @author  Kaique
 * @version 2020.04.03
 *
*/
namespace Mirrors\Ri;

use Posvenda\Model\GenericModel;

class RiMirror extends GenericModel
{

    use RiHelpers;

    //private $_url = '192.168.0.171:8080/api-ri';
	private $_url = 'https://api2.telecontrol.com.br/api-ri';
	private $_curl;
	private $_pdo;
	private $_fabrica;
	private $_admin;
	private $_headers;
	private $_accessEnv = "PRODUCTION";
	private $_appKey;
	private $_abasDefault;

	public function __construct($fabrica, $admin) {
		global $_serverEnvironment, $abas;

		if ($_serverEnvironment == "development") {

			$this->_accessEnv = "HOMOLOGATION";

		}

		$this->_appKey    = \Mirrors\AccessControl::getAppKey($fabrica, $this->_accessEnv);

		$this->_pdo         = $this->getPDO();
		$this->_curl        = curl_init();
		$this->_fabrica     = $fabrica;
		$this->_admin       = $admin;
		$this->_abasDefault = $abas;

		$this->_headers   = [
		    "Access-Application-Key: {$this->_appKey}",
		    "Access-Env: {$this->_accessEnv}",
		    "Cache-Control: no-cache",
		    "Content-Type: application/json"
		];

	}

	public function envia($request) {
		
		$curl = curl_init();

		$arrConteudoForm = array_map_recursive("utf8_encode", $request);

		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->_url."/gravar",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 160,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($arrConteudoForm),
				CURLOPT_HTTPHEADER => $this->_headers
			)
		);

		$response = json_decode(curl_exec($curl), true);
		$err = curl_error($curl);

		if (!empty($err)) {
			throw new \Exception("Erro curl ".$err, 400);
		}

		if (isset($response["exception"])) {
			throw new \Exception($response["exception"], 400);
		} 

		if ($response["success"]) {
			return $response;
		} 

		throw new \Exception("Erro ao gravar RI", 400);
        
	}

	public function transfere($request) {
		
		$arrConteudoForm = array_map_recursive("utf8_encode", $request);

		curl_setopt_array($this->_curl, array(
				CURLOPT_URL => $this->_url."/transferir",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 160,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "PUT",
				CURLOPT_POSTFIELDS => json_encode($arrConteudoForm),
				CURLOPT_HTTPHEADER => $this->_headers
			)
		);

		$response = json_decode(curl_exec($this->_curl), true);
		$err = curl_error($this->_curl);

		if (!empty($err)) {
			throw new \Exception("Erro curl ".$err, 400);
		}

		if (isset($response["exception"])) {
			throw new \Exception($response["exception"], 400);
		} 

		if ($response["success"]) {
			return $response;
		} 

		throw new \Exception("Erro ao gravar RI", 400);
        
	}

	public function relatorio($request, $params = []) {

		$curl = curl_init();

		$arrConteudoForm = [];

		if (count($request) > 0) {

			$arrConteudoForm["paginacao"] = $request;

		}

		$arrConteudoForm["parametros"] = $params;

		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->_url."/relatorio",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 160,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($arrConteudoForm),
				CURLOPT_HTTPHEADER => $this->_headers
			)
		);

		$response = json_decode(curl_exec($curl), true);
		
		$err = curl_error($curl); 
			
		if (!empty($err)) {
			throw new \Exception("Erro curl ".$err, 400);
		}

		if (isset($response["exception"])) {
			throw new \Exception($response["exception"], 400);
		} 

		return $response;

	}

	public function consulta($idRi) {

		$curl = curl_init();

		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->_url."/consulta",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 160,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode(["id" => $idRi]),
				CURLOPT_HTTPHEADER => $this->_headers
			)
		);

		$response = json_decode(curl_exec($curl), true);

		$err = curl_error($curl);

		if (!empty($err)) {
			throw new \Exception("Erro curl ".$err, 400);
		}

		if (isset($response["exception"])) {
			throw new \Exception($response["exception"], 400);
		} 

		if (count($response["ri"]) > 0) {
			return $response;
		} 

		throw new \Exception("Erro ao gravar RI", 400);
        
	}

	public function gravaAdminFollowup($request) {

		$curl = curl_init();

		$arrConteudoForm = array_map_recursive("utf8_encode", $request);

		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->_url."/followup",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 160,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode($arrConteudoForm),
				CURLOPT_HTTPHEADER => $this->_headers
			)
		);

		$response = json_decode(curl_exec($curl), true);
		$err = curl_error($curl);

		if (!empty($err)) {
			throw new \Exception("Erro curl ".$err, 400);
		}

		if (isset($response["exception"])) {
			throw new \Exception($response["exception"], 400);
		} 

		if ($response["success"]) {
			return $response;
		} 

		throw new \Exception("Erro ao gravar RI", 400);

	}

	public function consultaAdminFollowup($request) {

		$curl = curl_init();
		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->_url."/followup/all",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 160,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => $this->_headers
			)
		);
		$response = json_decode(curl_exec($curl), true);

		$err = curl_error($curl);

		if (!empty($err)) {
			throw new \Exception("Erro curl ".$err, 400);
		}

		if (isset($response["exception"])) {
			throw new \Exception($response["exception"], 400);
		} 

		if (count($response) > 0) {
			return $response;
		} 

		throw new \Exception("Erro ao gravar RI", 400);

	}

	public function deletaAdminFollowup($riGrupo) {

		$curl = curl_init();

		curl_setopt_array($curl, array(
				CURLOPT_URL => $this->_url."/followup/delete",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 160,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => json_encode(["id" => $riGrupo]),
				CURLOPT_HTTPHEADER => $this->_headers
			)
		);

		$response = json_decode(curl_exec($curl), true);

		$err = curl_error($curl);

		if (!empty($err)) {
			throw new \Exception("Erro curl ".$err, 400);
		}

		if (isset($response["exception"])) {
			throw new \Exception($response["exception"], 400);
		} 

		if (count($response) > 0) {
			return $response;
		} 

		throw new \Exception("Erro ao gravar RI", 400);

	}

}
