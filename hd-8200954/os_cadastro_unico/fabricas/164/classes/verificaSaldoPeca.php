<?php
   
    class verificaSaldoPeca {

		private $_fabrica;
        public $url;

		public function __construct($fabrica){

			if(empty($fabrica)){
				return "ERRO: Não foi possível criar a classe de Consulta de Saldo de Peça.";
			}

			$this->_fabrica = $fabrica;

            /* CONSULTA DE SALDO DE PEÇA GAMA ITALY */
            $servidor = $_SERVER['SERVER_NAME'];
            // $servidor = "devel";
            $pos = strpos($servidor, 'devel');
           
            if($pos === false){
                $this->url = "http://189.56.10.34:8082/GAMA_CSLD.apw?WSDL";
            }else{
                $this->url = "http://189.56.10.34:8081/GAMA_CSLD.apw?WSDL";
            }
		   
		}

		public function retornaSaldo($codPeca){

			$soap = new SoapClient($this->url, array("trace" => 1, "exception" => 1));
			$metodo = "CSLD";
			$options = array('location' => 'http://189.56.10.34:8082/GAMA_CSLD.apw');
			$peca['STRUCTMAT']['STRCODMAT'] = array('STRSNDMAT'=> array('CODMATERIAL'=>"{$codPeca}"));

			$peca = array($peca);

			$soapResult = $soap->__soapCall($metodo, $peca,$options);
			$retorno = (array) $soapResult->CSLDRESULT;
			$retorno = (array) $retorno['STRCSLD'];

			if(strlen($retorno['QTDT']) == 0){
				$retorno['QTDT'] = 0;
			}
			
			return $retorno['QTDT'];

		}

	}

?>
