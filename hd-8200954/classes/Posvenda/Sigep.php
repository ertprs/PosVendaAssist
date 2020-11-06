<?php

namespace Posvenda;
include __DIR__.'/../../distrib/PhpSigep/vendor/autoload.php';

use Posvenda\Model\Sigep as SigepModel;
use Posvenda\GeraColeta as GeraColeta;


class Sigep{


	private $_fabrica;
	private $_conn;
	private $_model;

	public function __construct($fabrica, $conn = null){

		$this->_fabrica = $fabrica;
		$this->_conn = $conn;
		
		$this->_model = new SigepModel($this->_fabrica, $conn);

		$accessDataParaAmbienteDeHomologacao = new \PhpSigep\Model\AccessDataHomologacao();
		$config = new \PhpSigep\Config();
		$config->setAccessData($accessDataParaAmbienteDeHomologacao);
		$config->setEnv(\PhpSigep\Config::ENV_PRODUCTION);
		$config->setCacheOptions(
		    array(
		        'storageOptions' => array(
		            'enabled' => false,
		            'ttl' => 10,// "time to live" de 10 segundos
		            'cacheDir' => sys_get_temp_dir(), // Opcional. Quando não inforado é usado o valor retornado de "sys_get_temp_dir()"
		        ),
		    )
		);
		\PhpSigep\Bootstrap::start($config);

	}

	public function consultaDadosContrato($tipo = "producao", $etiquetas){

		return $this->_model->dadosContrato($tipo, $etiquetas);

	}

	public function consultaServicosContrato(){

		
		
	}

	public function calculaFrete($dados){
		$response = $this->_model->calcPrecoPrazo($dados);
		return $response;

	}

	public function buscaEtiquetaBanco($dados,$tipo = "embarque"){

		try{
			if($tipo == "embarque"){
				$response = $this->_model->buscaEstiquetasEmbarque($dados);
			}else if($tipo == "pedido"){
				$response = $this->_model->buscaEtiquetasPedidos($dados);
			}
			return $response;
		}catch(\Exception $e){
			$response[] = array("resultado" => false, "msg" => $e->getMessage());
			throw new \Exception($e->getMessage());
		}		

	}

	public function gerarPLP($etiquetas,$cartao){
		$dados_contrato	 	      = $this->consultaDadosContrato("producao");
		$dados_contrato["cnpj"] = ($this->_fabrica == 186) ? "22104417000137" : $dados_contrato["cnpj"];


		$dados['contrato'] 	   	  = $dados_contrato;
		$dados['remetente']    	  = $this->consultaDadosContrato("remetente");
		$dados['dados_coleta'] 	  = $this->consultaDadosContrato("destinatario", $this->trataEtiqueta($etiquetas));
		
		$plp = new GeraColeta();
		$plp->execute($dados);

		$phpSigep = new \PhpSigep\Services\SoapClient\Real();
		$soapArgs = $phpSigep->fechaPlpVariosServicosImplantacao($plp);
	
		 if($soapArgs->getErrorMsg() != ""){
		     $response[] = array("resultado" => "false", "msg" => $soapArgs->getErrorMsg());
		 } else {
		     $response[] = array("resultado" => true, "idplp" => $soapArgs->getResult()->getIdPlp());
		 }

		return $response;
	}

	public function imprimirPLP($idPlp){
		$dados_contrato	 	      = $this->consultaDadosContrato("producao");
		$dados_contrato["cnpj"] = ($this->_fabrica == 186) ? "22104417000137" : $dados_contrato["cnpj"];

		$dados['contrato'] 	   	  = $dados_contrato;
		$dados['remetente']    	  = $this->consultaDadosContrato("remetente");
		
		$dados_plp = $this->consultaDadosContrato("buscaIdPlp", $idPlp);
		$idPlp     = $dados_plp["idplp"];
		$etiqueta  = $dados_plp["etiqueta"];
		
		$dados['dados_coleta'] = $this->consultaDadosContrato("destinatario", $this->trataEtiqueta($etiqueta));
		
		$plp = new GeraColeta();
		$plp->execute($dados);

		#$phpSigep = new \PhpSigep\Services\SoapClient\Real();
		#$soapArgs = $phpSigep->fechaPlpVariosServicosImplantacao($plp);
	
		$pdf  = new \PhpSigep\Pdf\ListaDePostagem($plp, $idPlp);
		$pdf->render($plp);
	}

	public function gravaPLP($plp,$etiquetas){
		
		return $this->_model->gravaIdPLP($plp,$etiquetas);
	}

	public function trataEtiqueta($etiquetas){
		$etiquetas = explode(",",$etiquetas);
		foreach ($etiquetas as $key => $value) {
			$lista_etiquetas[] = "'{$value}'";
		}
		return implode(",", $lista_etiquetas);
	}
}
