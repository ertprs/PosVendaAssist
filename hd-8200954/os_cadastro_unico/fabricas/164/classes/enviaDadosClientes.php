<?php
	/* ENVIA DADOS CLIENTE CALLCENTER GAMA ITALY */
    $servidor = $_SERVER['SERVER_NAME'];
    $pos = strpos($servidor, 'devel');

    if($pos === false){
        $url = "http://189.56.10.34:8082/03/GAMA_CADCLI.apw?WSDL";
    }else{
        $url = "http://189.56.10.34:8081/GAMA_CADCLI.apw?WSDL";
    }

    class enviaDadosClientes {

		private $_fabrica;

		public function __construct($fabrica){

			if(empty($fabrica)){
				return "ERRO: Não foi possível criar a classe de Consulta de Saldo de Peça.";
			}
			$this->_fabrica = $fabrica;
		}

		public function enviaClientes($cliente){
			global $url;
			$atendimento = $cliente['ATENDIMENTO'];
			unset($cliente['ATENDIMENTO']);
			try{
				$soap = new SoapClient($url, array("trace" => 1, "exception" => 1));
				$metodo = "CADCLI";
				$options = array('location' => 'http://189.56.10.34:8082/03/GAMA_CADCLI.apw');
				$dados['CADCLI']['STRUCTREC'] = $cliente;
				$soapResult = $soap->__soapCall($metodo, $dados, $options);
			}catch(SoapFault $e){
				$return_erro = (array) $e;
				var_dump($return_erro);
				$data_sistema	= Date('Y-m-d H:i:s');
				$error = "==================================== ATENDIMENTO: $atendimento ==================================== \n";
				$error .= $return_erro['faultstring'].' - '.$data_sistema;
				$error .= "\n =================================================================================================== \n";
				$arquivos = "/tmp/gamaitaly/";

				$arquivo_log = "{$arquivos}envia-dados-clientes.log";

				if(!is_dir($arquivos)){
					system("mkdir -p -m 777 $arquivos");
				}

				if(file_exists($arquivo_log)){
					$file_log_erro = fopen($arquivo_log,"a");
				}else{
					$file_log_erro = fopen($arquivo_log,"w+");
				}
			    fputs($file_log_erro,$error."\n");
			    fclose ($file_log_erro);
			}
		}
	}
?>
