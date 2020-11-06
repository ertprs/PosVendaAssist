<?php
namespace Lojavirtual;
include dirname(__FILE__) . '/../../loja/integracoes/correios-sigep/src/PhpSigep/Bootstrap.php';

use Lojavirtual\Controller;

class Frete extends Controller {

	private $envio_nome;
	private $envio_ambiente;
	private $envio_status;
	private $envio_codAdministrativo;
	private $envio_usuario;
	private $envio_senha;
	private $envio_cartaoPostagem;
	private $envio_cnpjEmpresa;
	private $envio_numeroContrato;
	private $envio_anoContrato;

    public function __construct() {
        parent::__construct();
       	if (isset($this->_loja_config["forma_envio"]["meio"]["correios"])) {
        	$this->initCorreiosSigep();
       	}
    }


	public function initCorreiosSigep() {

		$this->envio_nome				= $this->_loja_config["forma_envio"]["meio"]["correios"]["nome"];
		$this->envio_ambiente			= $this->_loja_config["forma_envio"]["meio"]["correios"]["ambiente"];
		$this->envio_status				= $this->_loja_config["forma_envio"]["meio"]["correios"]["status"];
		$this->envio_codAdministrativo	= $this->_loja_config["forma_envio"]["meio"]["correios"]["codAdministrativo"];
		$this->envio_usuario			= $this->_loja_config["forma_envio"]["meio"]["correios"]["usuario"];
		$this->envio_senha				= $this->_loja_config["forma_envio"]["meio"]["correios"]["senha"];
		$this->envio_cartaoPostagem		= $this->_loja_config["forma_envio"]["meio"]["correios"]["cartaoPostagem"];
		$this->envio_cnpjEmpresa		= $this->_loja_config["forma_envio"]["meio"]["correios"]["cnpjEmpresa"];
		$this->envio_numeroContrato		= $this->_loja_config["forma_envio"]["meio"]["correios"]["numeroContrato"];
		$this->envio_anoContrato		= $this->_loja_config["forma_envio"]["meio"]["correios"]["anoContrato"];

		if ($this->envio_ambiente == "sandbox") {

			$accessData = new \PhpSigep\Model\AccessDataHomologacao();

		} else {

			$accessData = new \PhpSigep\Model\AccessData();
			$accessData->setCodAdministrativo($this->envio_codAdministrativo);
			$accessData->setUsuario($this->envio_usuario);
			$accessData->setSenha($this->envio_senha);
			$accessData->setCartaoPostagem($this->envio_cartaoPostagem);
			$accessData->setCnpjEmpresa($this->envio_cnpjEmpresa);
			$accessData->setNumeroContrato($this->envio_numeroContrato);
			$accessData->setAnoContrato($this->envio_anoContrato);
		}

		$config = new \PhpSigep\Config();

		$config->setAccessData($accessData);
		
		$config->setEnv(\PhpSigep\Config::ENV_PRODUCTION);

		\PhpSigep\Bootstrap::start($config);


	}

    public function calculaPrazoValorCorreiosSigep($cepPosto, $alturaProduto, $comprimentoProduto, $larguraProduto, $pesoProduto, $cepOrigem) {

		$dimensao = new \PhpSigep\Model\Dimensao();
		$dimensao->setTipo(\PhpSigep\Model\Dimensao::TIPO_PACOTE_CAIXA);
		$dimensao->setAltura($alturaProduto); // em centímetros
		$dimensao->setComprimento($comprimentoProduto); // em centímetros
		$dimensao->setLargura($larguraProduto); // em centímetros

		$params = new \PhpSigep\Model\CalcPrecoPrazo();
		$params->setAccessData(new \PhpSigep\Model\AccessDataHomologacao());
		$params->setCepOrigem($cepOrigem);
		$params->setCepDestino($cepPosto);
		$params->setServicosPostagem(\PhpSigep\Model\ServicoDePostagem::getAll());
		$params->setAjustarDimensaoMinima(true);
		$params->setDimensao($dimensao);
		$params->setPeso($pesoProduto);// 150 gramas

		$phpSigep = new \PhpSigep\Services\SoapClient\Real();
		$result = $phpSigep->calcPrecoPrazo($params);
		$retorno = (object) $result;
		return $this->trataRetornoCorreios($retorno);
    }

    public function trataRetornoCorreios($retorno) {
    	if (empty($retorno)) {
    		return ["erro" => true, "msg" => "Erro ao conectar com Webservice dos Correios"];
    	}
    	
    	if (strlen($retorno->getErrorMsg()) > 0 &&  $retorno->getErrorCode() != 11) {
    		return ["erro" => true, "msg" => $retorno->getErrorMsg()];
    	}

    	$formas = [];
    	foreach ($retorno->getResult() as $key => $row) {

    		if (strlen($row->getErroMsg()) > 0 &&  $retorno->getErrorCode() != 11) {
    		}
    		
    		if (!in_array($row->getServico()->getCodigo(), $this->_loja_config["forma_envio"]["meio"]["correios"]["servicos_usados"])) {
    			continue;
    		}
    		if ($row->getValor() <= 0) {
    			continue;
    		}
    		$formas[$key]["codigo"]			= $row->getServico()->getCodigo();
    		$formas[$key]["nome"]			= $row->getServico()->getNome();
    		$formas[$key]["valor"]			= $row->getValor();
    		$formas[$key]["prazoEntrega"]	= $row->getPrazoEntrega();
    	}

    	return $formas;

    }
	public function getCepOrigem($con, $id_fornecedor = "", $login_fabrica) {
		if ($login_fabrica == 42) {
			$sql = "SELECT cep FROM tbl_loja_b2b_fornecedor WHERE loja_b2b_fornecedor={$id_fornecedor}";
			$res = pg_query($con, $sql);
			return pg_fetch_result($res, 0, 'cep');
		} else {
			$sql = "SELECT cep FROM tbl_fabrica WHERE fabrica={$login_fabrica}";
			$res = pg_query($con, $sql);
			return pg_fetch_result($res, 0, 'cep');
		}
	}
}
