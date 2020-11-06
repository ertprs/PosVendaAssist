<?php
/**
 *
 * @author  Kaique
 * @version 2019.12.11
 *
*/
namespace Mirrors;

use Posvenda\Model\GenericModel as Model;

class EasyBuilderMirror
{

	//private $_url 		= '192.168.0.171:8080/telecontrol-easy-form-builder/';
	//private $_url 		= 'backend2.telecontrol.com.br/homologation-telecontrol-easy-form-builder/';
	private $_url 		= 'https://api2.telecontrol.com.br/telecontrol-easy-form-builder/';
	private $_pdo;
	private $_fabrica;
	private $_categoria;
	private $_admin;
	private $_headers;
	private $_accessEnv = "PRODUCTION";
	private $_appKey 	= "7e5bcb2c9c6284315c4bf3d09eb95bd06c52ba20";

	public $_tiposPesquisaFabrica = array(
		1  => [
			"atualizacao_cadastral" => [
				"descricao"   => "Atualização Cadastral posto (login)",
				"informativo" => "A pesquisa de atualização cadastral fica disponível apenas para postos do tipo “Autorizada” e “Locadora Autorizada” no momento do login, com a opção de responder depois."
			]
		],
		10 => [
			"questionario_avaliacao" => [
				"descricao"   => "Situação cadastral",
				"informativo" => "Situação cadastral posto."
			]
		],
		42 => [
			"questionario_avaliacao" => [
				"descricao"   => "Questionário Avaliação (app)",
				"informativo" => "O questionário de avaliação é realizado pelo técnico da makita via aplicativo ou na tela de cadastro do posto via telecontrol."
			]
		],
		166 => [
			"tela_inicial_posto" => [
				"descricao" => "Pesquisa Tela Inicial Posto",
				"informativo" => "Essa pesquisa será exibida na tela inicial do posto autorizado"
			]
		]
	);

	public function __construct($fabrica, $categoria, $admin) {
		global $_serverEnvironment;


		if ($_serverEnvironment == "development") {

			$this->_accessEnv = "HOMOLOGATION";
			$this->_appKey 	  = "39e971629867a4fb14c1ae76c2e3d84437a7c905";

		}

		$model = new Model();

		$this->_pdo = $model->getPDO();

		$this->_fabrica   = $fabrica;
		$this->_categoria = $categoria;
		$this->_admin     = $admin;

		$this->_headers   = [
            "Access-Application-Key: {$this->_appKey}",
            "Access-Env: {$this->_accessEnv}",
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ];

	}

	public function post($request) {
		
		$arrConteudoQuestionario = array_map_recursive("utf8_encode", $request['easybuilder']);

		$curl = curl_init();

        curl_setopt_array($curl, array(
		        CURLOPT_URL => $this->_url."easy-builder",
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_ENCODING => "",
		        CURLOPT_MAXREDIRS => 10,
		        CURLOPT_TIMEOUT => 90,
		        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		        CURLOPT_CUSTOMREQUEST => "POST",
		        CURLOPT_POSTFIELDS => json_encode([
		            'easyForm' => $arrConteudoQuestionario,
		            'fabricaTelecontrol' => $this->_fabrica,
		            'categoriaPesquisa' => $this->_categoria,
		            'admin' => $this->_admin,
		            'ativo' => ($request["ativo"] == "t") ? true : false
		        ]),
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

        if (!empty($response["id_pesquisa"])) {
        	return $response;
        } 

        throw new \Exception("Erro ao gravar pesquisa", 400);
        
	}

	public function put($request) {
		
		$arrConteudoQuestionario = array_map_recursive("utf8_encode", $request['easybuilder']);

		$curl = curl_init();

        curl_setopt_array($curl, array(
		        CURLOPT_URL => $this->_url."easy-builder",
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_ENCODING => "",
		        CURLOPT_MAXREDIRS => 10,
		        CURLOPT_TIMEOUT => 90,
		        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		        CURLOPT_CUSTOMREQUEST => "PUT",
		        CURLOPT_POSTFIELDS => json_encode([
		            'easyForm' => $arrConteudoQuestionario,
		            'fabricaTelecontrol' => $this->_fabrica,
		            'idPesquisa' => $request["pesquisa"],
		            'ativo' => ($request["ativo"] == "t") ? true : false,
		            'categoriaPesquisa' => $this->_categoria
		        ]),
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

        if (!empty($response["id_pesquisa"])) {
        	return $response;
        } 

        throw new \Exception("Erro ao alterar pesquisa", 400);
        
	}

	public function get($pesquisaId) {

		$curl = curl_init();

		$url = $this->_url."easy-builder/pesquisaId/{$pesquisaId}".
		"/fabricaTelecontrol/{$this->_fabrica}";
		
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $this->_headers
        ]);

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

	public function delete($pesquisaId) {

		$curl = curl_init();

		$url = $this->_url."easy-builder/idPesquisa/{$pesquisaId}".
		"/fabricaTelecontrol/{$this->_fabrica}";
		
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => $this->_headers
        ]);

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

	public function deleteResposta($request) {

		$curl = curl_init();

		$url = $this->_url."easy-builder-resposta/idResposta/".$request["resposta"].
		"/fabricaTelecontrol/{$this->_fabrica}".
		"/idPesquisa/".$request["pesquisa"].
		"/idPosto/".$request["posto"].
		"/idAdmin/".$this->_admin;
		
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => $this->_headers
        ]);

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

	public function getAll($objRequest) {

		$curl = curl_init();

		$url = $this->_url."easy-builder".
		"/paginate/1".
		((empty($objRequest->offset)) ? null : "/offset/{$objRequest->offset}").
		((empty($objRequest->limit)) ? null : "/limit/{$objRequest->limit}").
		((empty($objRequest->orderBy)) ? null : "/orderBy/{$objRequest->orderBy}").
		((empty($objRequest->order)) ? null : "/order/{$objRequest->order}").
		((empty($objRequest->strPesquisa)) ? null : "/pesquisa/{$objRequest->strPesquisa}").
		"/fabricaTelecontrol/{$this->_fabrica}";
		
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $this->_headers
        ]);

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

	public function getRespostas($objRequest, $params = []) {

		$curl = curl_init();

		$url = $this->_url."easy-builder-resposta/fabricaTelecontrol/{$this->_fabrica}";

		if (count($objRequest) > 0) {

			$url .= "/paginate/1".
			((empty($objRequest->offset)) ? null : "/offset/{$objRequest->offset}").
			((empty($objRequest->limit)) ? null : "/limit/{$objRequest->limit}").
			((empty($objRequest->orderBy)) ? null : "/orderBy/{$objRequest->orderBy}").
			((empty($objRequest->order)) ? null : "/order/{$objRequest->order}").
			((empty($objRequest->strPesquisa)) ? null : "/pesquisa/{$objRequest->strPesquisa}");

		}

		foreach ($params as $chave => $valor) {
			if (!empty($valor)) {
				$url .= "/{$chave}/{$valor}";
			}
		}
		
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $this->_headers
        ]); 

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

	public function gravaResposta($request, $parametros_adicionais = []) {

		$arrConteudoResposta   = array_map_recursive("utf8_encode", $request['formulario']);
		$parametros_adicionais = array_map_recursive("utf8_encode", $parametros_adicionais);

		$curl = curl_init();
		
        curl_setopt_array($curl, array(
		        CURLOPT_URL => $this->_url."easy-builder-resposta",
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_ENCODING => "",
		        CURLOPT_MAXREDIRS => 10,
		        CURLOPT_TIMEOUT => 90,
		        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		        CURLOPT_CUSTOMREQUEST => "POST",
		        CURLOPT_POSTFIELDS => json_encode([
		            'formulario' => $arrConteudoResposta,
		            'fabricaTelecontrol' => $this->_fabrica,
		            'idPesquisa' => $request["pesquisa"],
		            'idPosto' => $request["posto"],
		            'idTecnico' => $request["tecnico"],
		            'parametrosAdicionais' => $parametros_adicionais,
		            "idAdmin" => $this->_admin
		        ]),
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

        if (!empty($response["id_resposta"])) {
        	return $response;
        } 

        throw new \Exception("Erro ao gravar pesquisa", 400);

	}

	public function alteraResposta($request, $parametros_adicionais = []) {

		$arrConteudoResposta = array_map_recursive("utf8_encode", $request['formulario']);

		$curl = curl_init();

        curl_setopt_array($curl, array(
		        CURLOPT_URL => $this->_url."easy-builder-resposta",
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_ENCODING => "",
		        CURLOPT_MAXREDIRS => 10,
		        CURLOPT_TIMEOUT => 90,
		        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		        CURLOPT_CUSTOMREQUEST => "PUT",
		        CURLOPT_POSTFIELDS => json_encode([
		            'formulario' => $arrConteudoResposta,
		            'fabricaTelecontrol' => $this->_fabrica,
		            'idPesquisa' => $request["pesquisa"],
		            'idPosto' => $request["posto"],
		            'idTecnico' => $request["tecnico"],
		            'parametrosAdicionais' => $parametros_adicionais,
		            'idResposta' => $request["resposta_hidden"],
		            "idAdmin" => $this->_admin
		        ]),
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

        if (!empty($response["id_resposta"])) {
        	return $response;
        } 

        throw new \Exception("Erro ao alterar resposta", 400);

	}

	public function getPesquisaPendentePosto($postoId) {

		$sqlPesquisaPendente = "SELECT pesquisa
                                 FROM tbl_pesquisa
                                 WHERE fabrica = {$this->_fabrica}
                                 AND categoria = '{$this->_categoria}'
                                 AND ativo
                                 AND (
                                    SELECT resposta FROM tbl_resposta
                                    WHERE posto = {$postoId}
                                    AND tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
                                    LIMIT 1
                                 ) IS NULL";

		$query = $this->_pdo->query($sqlPesquisaPendente);

		$dados = $query->fetchAll();

		return $dados[0]["pesquisa"];

	}

}
