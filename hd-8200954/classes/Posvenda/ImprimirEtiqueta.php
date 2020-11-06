<?php
namespace Posvenda;
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
include __DIR__.'/../../distrib/PhpSigep/vendor/autoload.php';

use Posvenda\Model\ImprimirEtiqueta as ImprimirEtiquetaModel;
use Posvenda\Model\Sigep as SigepModel;
use Posvenda\GeraColeta as GeraColeta;


class ImprimirEtiqueta {

	private $_fabrica;
	private $_conn;
	private $_model;
	private $_modelSigep;

	public function __construct($fabrica, $conn = null){

		$this->_fabrica = $fabrica;
		$this->_conn    = $conn;
		
		$this->_model      = new ImprimirEtiquetaModel($this->_fabrica, $conn);
		$this->_modelSigep = new SigepModel($this->_fabrica, $conn);

		$dados_contrato = $this->_modelSigep->dadosContrato('producao');


		$accessData = new \PhpSigep\Model\AccessData();
	
		$codAdministrativo = $dados_contrato["codigo_administrativo"];
		$usuario = $dados_contrato["usuario"];
		$senha = $dados_contrato["senha"];
		
		$cartaoPostagem = $dados_contrato["cartao"];
		$cnpjEmpresa = ($this->_fabrica == 186) ? "22104417000137" : $dados_contrato["cnpj"];
		$numeroContrato = $dados_contrato["contrato"];
		$anoContrato = null;

		$accessData->setCodAdministrativo($codAdministrativo);
		$accessData->setSenha($senha);
		$accessData->setUsuario($usuario);
		$accessData->setCartaoPostagem($cartaoPostagem);
		$accessData->setCnpjEmpresa($cnpjEmpresa);
		$accessData->setNumeroContrato($numeroContrato);
		$accessData->setAnoContrato($anoContrato);
//		$accessData->setDiretoria($diretoria);


		$config = new \PhpSigep\Config();
		$config->setAccessData($accessData);
		$config->setEnv(\PhpSigep\Config::ENV_PRODUCTION);
		$config->setCacheOptions(
		    array(
		        'storageOptions' => array(
		            'enabled' => false,
		            'ttl' => 10,
		            'cacheDir' => sys_get_temp_dir(),
		        ),
		    )
		);
		\PhpSigep\Bootstrap::start($config);
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

	public function trataEtiqueta($etiquetas){
		$etiquetas = explode(",",$etiquetas);
		foreach ($etiquetas as $key => $value) {
			$lista_etiquetas[] = "'{$value}'";
		}
		return implode(",", $lista_etiquetas);
	}

	public function consultaDadosContrato($tipo = "producao", $etiquetas){

		return $this->_modelSigep->dadosContrato($tipo, $etiquetas);

	}

	public function imprimirEtiquetaPdf($etiquetas){
		$etiquetas = $etiquetas["lista_etiqueta"];
		
		$dados_contrato	 	      = $this->consultaDadosContrato("producao");
		$dados_contrato['cartao'] = $cartao;
		$dados['contrato'] 	   	  = $dados_contrato;
		$dados['remetente']    	  = $this->consultaDadosContrato("remetente");
		$dados['dados_coleta'] 	  = $this->consultaDadosContrato("destinatario", $this->trataEtiqueta($etiquetas));
		
		$logoFile = __DIR__ . '/logo_mq.jpg';
		$pdf = new \PhpSigep\Pdf\CartaoDePostagem($dados, time(), $logoFile);
		$pdf->render();

	}

	public function getDadosEtiqueta($etiquetas){
		$dados['remetente']          = $this->consultaDadosContrato("remetente");
		$dados['dados_destinatario'] = $this->consultaDadosContrato("destinatarioDeclaracao", $this->trataEtiqueta($etiquetas));
		return $dados;
	}
}
