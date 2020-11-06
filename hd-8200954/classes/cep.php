<?php

class CEP {

	private static $instance = null;
	private $cache = array();
	private $consultaMetodos = array(
			'soap' => 'ConsultaCEPSoap',
			'curl' => 'ConsultaCEPCurl',
			'db' => 'ConsultaCEPDB',
		);

	private static function getIntance(){
		if(CEP::$instance == null)
			CEP::$instance = new CEP();
		return CEP::$instance;
	}

	private function CEP(){

	}

	private function clear($cep){
		return preg_replace("/[ -.\W]/",'',$cep);
	}

	public static function consulta($cep,$method=null,$cache=true){
		$instance = CEP::getIntance();
		$cep = $instance->clear($cep);

        if(strlen($cep) < 8){
            throw new InvalidCEPException($cep);
        }
		if($cache && isset($instance->cache[$cep]) && $instance->cache[$cep])
			return $instance->cache[$cep];
		if($method)
			return $instance->consultWithMethod($cep,$method);
		return $instance->consult($cep);
	}

	private function consult($cep){
		$exceptions = array();
		foreach($this->consultaMetodos as $key => $class){
			try{
				return $this->consultWithMethod($cep,$key);
			}
			catch(Exception $ex){
				$exceptions[] = $ex;
				continue;
			}
		}
		//throw new Exception(implode('<br />',$exceptions));
		$exception = null;
		foreach($exceptions as $ex){
			if($ex instanceof InvalidCEPException)
				throw $ex;
			else if($ex instanceof NotFoundCepException)
				$exception = $ex;
			else
				throw $ex;
		}
		throw $exception;
	}

	private function consultWithMethod($cep,$method){
		if(isset($this->consultaMetodos[$method]))
			$class = $this->consultaMetodos[$method];
		else
			$class = $method;
		if(!class_exists($class))
			throw new InvalidMethodException($method,$class);
		$object = new $class();
		$resp = $object->consulta($cep);
		$this->cache[$cep] = $resp;
		return $resp;
	}

}

class CEPException extends Exception {

	protected function CEPException($message){
		parent::__construct($message);
	}

}

class InvalidMethodException extends CEPException{
	public function InvalidMethodException($method,$class){
		parent::__construct("Método {$method}({$class}) não suportado.");
	}
}

class InvalidCEPException extends CEPException{

	public function InvalidCEPException($cep){
		parent::__construct("O CEP: {$cep} é Inválido");
	}
}

class NotFoundCEPException extends CEPException{

	public function NotFoundCEPException($cep){
		parent::__construct("Não foi possivel validar o CEP : {$cep}");
	}
}

class ConsultaCEPSoap{

	private $correiosWebService = 'https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl';

	public function consulta($preparedCep){
		$soapClient = new SoapClient($this->correiosWebService);
		if(!is_callable(array($soapClient,'consultaCEP')))
        	throw new Exception("Não foi possivel consultar o CEP");
        $result = $soapClient->consultaCEP(array('cep'=>$preparedCep));
        if(!property_exists($result,'return'))
        	throw new InvalidCEPException($preparedCep);
        return array_map(function($e){return utf8_decode($e);},(array)$result->return);
	}

}

class ConsultaCEPCurl{

	private $url =  'http://www.buscacep.correios.com.br/servicos/dnec/consultaEnderecoAction.do';
	private $params = array(
		'Metodo' => 'listaLogradouro',
		'TipoConsulta'=>'relaxation',
	);

	private function prepareParameters($params){
		array_walk($params,function(&$val,$key){
			$val = $key.'='.$val;
		});
		return implode ('&',$params);
	}


	private function request($preparedCep){
		$post = array_merge($this->params,array('relaxation'=>$preparedCep));
		$post = $this->prepareParameters($post);
		$curl = curl_init();
		curl_setopt($curl,CURLOPT_URL,$this->url);
		curl_setopt($curl,CURLOPT_HEADER,true);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_POST,true);
		curl_setopt($curl,CURLOPT_POSTFIELDS,$post);
		$response = curl_exec($curl);
		curl_close($curl);
		if(!$response)
			throw new Exception();
		return $response;
	}

	private function mine($html,$cep){
		$dom = new DOMDocument();
		if(!@$dom->loadHTML($html))
			throw new Exception();
		$body = $dom->getElementsByTagName('body');
		$title = $dom->getElementsByTagName('title');
		foreach($title as $t)
			if(strtoupper($t->nodeValue) =='ERRO')
				throw new InvalidCEPException($cep);
		foreach ($body as $b) {
			$tables = $b->getElementsByTagName('table');
			$table = $tables->item(2);
			if(!is_object($table)){
				throw new InvalidCEPException($cep);
			}
			$trs = $table->getElementsByTagName('tr');
			return $this->extract($trs->item(0));
		}
	}

	private function extract($tr){
		$values = array();
		$tds = $tr->getElementsByTagName('td');
		foreach($tds as $td){
			$values[] = utf8_decode($td->nodeValue);
		}
		$address =  array_combine(array('end','bairro','cidade','uf','cep'),$values);
		$address['cep'] = preg_replace("/[ -.\W]/",'',$address['cep']);
		return $address;
	}

	public function consulta($preparedCep){
		$html = $this->request($preparedCep);
		return $this->mine($html,$preparedCep);
	}

}

class ConsultaCEPDB{

	public function consulta($cep){

		global $con;
		$sql = "SELECT * FROM tbl_cep WHERE cep = $1;";
 		$result = pg_query_params($con,$sql,array($cep));
 		if(!$result)
 			throw new Exception(pg_last_error($con));
 		if(pg_num_rows($result) == 0)
			throw new NotFoundCEPException($cep);
 		$address = array();
 		$address['end'] = pg_fetch_result($result, 0, "logradouro");
 		$address['bairro'] = pg_fetch_result($result, 0, "bairro");
 		$address['cidade'] = pg_fetch_result($result, 0, "cidade");
 		$address['uf'] = pg_fetch_result($result, 0, "estado");
 		$tipo = pg_fetch_result($result, 0, "tipo");

		switch ($tipo) {
			case "A":
				$address['end'] = "Av. ".$address['end'];
			break;
			case "R":
				$address['end'] = "R. " .$address['end'];
			break;
		}

		return $address;

	}

}
