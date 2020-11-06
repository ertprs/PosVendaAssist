<?php 


include_once dirname(__FILE__) . '/../../dbconfig.php';
include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . '/../funcoes.php';
require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

$fabrica_nome = "midea";
$login_fabrica = 169;

define('APP', 'Cancela Pedido - '.$fabrica_nome);

	$oPedido = new \Posvenda\Pedido($login_fabrica);

    function formataValor($valor){
        $novoValor = str_replace(".", "", $valor);
        $novoValor = str_replace(",", ".", $novoValor);
        return $novoValor;
    }

    $vet['fabrica'] = 'midea';
    $vet['tipo']    = 'cancela-pedido-venda';
    $vet['dest']    = array('lucas.carlos@telecontrol.com.br');
    $vet['log']     = 1;
    
    $log_erro = array();
    $dir = '/tmp/' . $fabrica_nome;

    if ($_serverEnvironment == 'development') {
    	$urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/BlueService.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }
    

	$client = new SoapClient($urlWSDL, array('trace' => 1)); 


    $array_request = array('PI_PEDIDO'=> '0032424794');

	$result = $client->Z_CB_TC_RETORNA_DADOS_NFE_BS($array_request);
	$dados_xml = $result->Z_CB_TC_RETORNA_DADOS_NFE_BSResult->any;

	$xml = simplexml_load_string($dados_xml);
    $xml = json_decode(json_encode((array)$xml), true);


    print_r($xml);