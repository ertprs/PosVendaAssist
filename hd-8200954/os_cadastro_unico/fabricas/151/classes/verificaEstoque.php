<?php

class verificaEstoque{

	protected $_url_servidor;
	protected $_chave_acesso;

        protected $_fabrica = 151;
        protected $_url;
        protected $servidor;

        public function __construct(){
        	global $_serverEnvironment;

                $this->servidor = $server;

        }

	public function alteraReferenciaPeca($destino, $pecas){

		foreach($pecas as $key => $value){

			if($destino == "mk_sul"){
			
				$pecas[$key]["referencia"] = "6".$value["referencia"];				
			}else{
				$pecas[$key]["referencia"] = substr($value["referencia"],1,strlen($value["referencia"]));
			}
		}
		return $pecas;
	}

	public function consultaEstoquePecas($pecas,$tipo_pedido,$server = "mk_nordeste",$fp = null,$logar = null){
		global $_serverEnvironment;
                include_once dirname(__FILE__) . '/MKDistribuicao.php';
                $Send = new DadosSend($this->_fabrica);
                $dadosServidor  = $Send->urlServidor(null,$server);
		$this->_url  = $dadosServidor["url"];
		$this->_chave_acesso = $Send->getKey($server);
		$ambiente = ($_serverEnvironment == 'development') ? 2 : 1;

		foreach($pecas as $key => $value){
			
			$dadosProduto = array();
			$dadosProduto["SdConsultaEstoqueProduto"]["UnidadeOperacional"] = $this->_chave_acesso["unidade_operacional"];
			$dadosProduto["SdConsultaEstoqueProduto"]["UsuarioChaveGUID"]   = $this->_chave_acesso["chave_seguranca_send"];
			$dadosProduto["SdConsultaEstoqueProduto"]["AmbienteTipo"] = $ambiente;
			$dadosProduto["SdConsultaEstoqueProduto"]["Produto"] = $value["referencia"];
			$dadosProduto["SdConsultaEstoqueProduto"]["TipoPedido"] = $tipo_pedido;
			$dadosProduto["SdConsultaEstoqueProduto"]["CodigoAlmoxarifado"] = "933";
			$dadosProduto = json_encode($dadosProduto); 

			$ch = curl_init($this->_url."wsconsultaestoqueproduto");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $dadosProduto);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
			$result = curl_exec($ch);
			curl_close($ch);

			if($logar == true){
				$dadosLog = "Consulta estoque item {$value["referencia"]} \n URL: {$this->_url}wsconsultaestoqueproduto \n Dados Enviados: {$dadosProduto} \n Dados de retorno: {$result}\n\n";
				fwrite($fp,$dadosLog);
			}

			$resposta = json_decode($result, true);
			$resp = $resposta["SdConsultaEstoqueProduto_Ret"];

			$tem_estoque = false;
			if($resp["ErroCod"] == 0 && $resp["Estoque"]){
			
				foreach($resp["Estoque"] AS $k => $v){
					if($v['QuantidadeEstoque'] >= $value['qtde']){
						$tem_estoque = true;
						break;
					}		
				}	
			}

		}
		return $tem_estoque;
	}	

}
