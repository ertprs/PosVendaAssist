<?php
// require_once __DIR__ . '/bootstrap-exemplos.php';

// $params = include __DIR__ . '/helper-criar-pre-lista.php';

// $phpSigep = new PhpSigep\Services\SoapClient\Real();
// $result = $phpSigep->fechaPlpVariosServicos($params);

// echo '<pre>';
// print_r((array)$result);
// echo '</pre>';

include '../../funcao_correio.php';

$etiquetas       = $_POST['etiquetas'];
$cartao_postagem = $_POST['cartao_postagem'];

$embarque = imprimePLP($etiquetas, $cartao_postagem);

require_once __DIR__ . '/bootstrap-exemplos.php';

function acentos ($string) {
    $array1 = array("б" => "a", "а" => "a", "в" => "a", "г" => "a", "д" => "a", 
        "й" => "e", "и" => "e", "к" => "e", "л" => "e", 
        "н" => "i", "м" => "i", "о" => "i", "п" => "i", 
        "у" => "o", "т" => "o", "ф" => "o", "х" => "o", "ц" => "o", 
        "ъ" => "u", "щ" => "u", "ы" => "u", "ь" => "u", 
        "з" => "c", 
        "Б" => "A", "А" => "A", "В" => "A", "Г" => "A", "Д" => "A", 
        "Й" => "E", "И" => "E", "К" => "E", "Л" => "E", 
        "Н" => "I", "М" => "I", "О" => "I", "П" => "I", 
        "У" => "O", "Т" => "O", "Ф" => "O", "Х" => "O", "Ц" => "O", 
        "Ъ" => "U", "Щ" => "U", "Ы" => "U", "Ь" => "U", "З" => "C");
    $string = strtr(utf8_decode($string), $array1);

    return $string;
}

$contrato = $embarque['contrato'];

$accessData = new \PhpSigep\Model\AccessData();
$accessData->setCodAdministrativo($contrato['codigo_administrativo']);
$accessData->setUsuario($contrato['usuario']); //$contrato['usuario']
$accessData->setSenha($contrato['senha']);
$accessData->setCartaoPostagem($contrato['cartao']);
$accessData->setCnpjEmpresa($contrato['cnpj']);
$accessData->setNumeroContrato($contrato['contrato']);
$accessData->setAnoContrato($contrato['ano']);

$diretoria = new \PhpSigep\Model\Diretoria(74);
$diretoria->setNumero(74);
$diretoria->setNome("DR - Sao Paulo Interior");
$diretoria->setSigla("SPI");
$diretoria->setSigla("SPI");

$accessData->setDiretoria($diretoria);

$remetente = new \PhpSigep\Model\Remetente();
$dados_remetente = $embarque['remetente'];
$consultaCEP = new \PhpSigep\Services\SoapClient\Real();
$endereco_remetente = $consultaCEP->consultaCep($dados_remetente['cep']);

$remetente->setNumeroContrato(NULL);
$remetente->setDiretoria('74');
$remetente->setCodigoAdministrativo(NULL);
$remetente->setNome(acentos($dados_remetente['nome']));
$remetente->setLogradouro(strtoupper(acentos($endereco_remetente->getResult()->getEndereco())));
$remetente->setNumero($dados_remetente['numero']);
$remetente->setComplemento("");
$remetente->setBairro(acentos($endereco_remetente->getResult()->getBairro()));
$remetente->setCep($dados_remetente['cep']);
$remetente->setCidade(strtoupper(acentos($endereco_remetente->getResult()->getCidade())));
$remetente->setUf($dados_remetente['estado']);
$remetente->setTelefone("");
$remetente->setFax("");
$remetente->setEmail("");

$embarque = $embarque['embarque'];

for($i=0; isset($embarque[$i]); $i++){
	$total_nota = (float) str_replace(',', '.', trim($embarque[$i]['total_nota']));

    $etiqueta = new \PhpSigep\Model\Etiqueta();
	$etiqueta->setEtiquetaComDv($embarque[$i]['etiqueta']); 
	$etiqueta2 = $embarque[$i]['etiqueta']; 
	$etiqueta2 = substr($etiqueta2,0,-3)."BR";
	$etiqueta->setEtiquetaSemDv($etiqueta2);

    $servicoDePostagem = new \PhpSigep\Model\ServicoDePostagem($embarque[$i]['codigo']);
    $servicoDePostagem->setCodigo($embarque[$i]['codigo']);
    $servicoDePostagem->setIdServico($embarque[$i]['chave_servico']);
    $servicoDePostagem->setNome($embarque[$i]['descricao']);

    $destinatario = new \PhpSigep\Model\Destinatario();
    $destinatario->setNome(acentos($embarque[$i]['nome']));
    $destinatario->setEmbarque($embarque[$i]['embarque']);
    $destinatario->setTelefone(NULL);
    $destinatario->setCelular(NULL);
    $destinatario->setEmail(NULL);
    $destinatario->setLogradouro(acentos($embarque[$i]['endereco']));
    $destinatario->setComplemento("");
    $destinatario->setNumero($embarque[$i]['numero']);

    $destino = new \PhpSigep\Model\DestinoNacional();
    $destino->setBairro(acentos($embarque[$i]['bairro']));
    $destino->setCep($embarque[$i]['cep']);
    $destino->setCidade(acentos($embarque[$i]['cidade']));
    $destino->setUf($embarque[$i]['estado']);
    $destino->setNumeroNotaFiscal($embarque[$i]['nota_fiscal']);
    $destino->setSerieNotaFiscal(NULL);
    $destino->setValorNotaFiscal($embarque[$i]['total_nota']);
    $destino->setNaturezaNotaFiscal(NULL);
    $destino->setDescricaoObjeto(NULL);
    $destino->setValorACobrar(NULL);

	$servicosAdicionais[0] = new \PhpSigep\Model\ServicoAdicional();
	$pacs                  = ['PAC', 'PAC CONTRATO AGENCIA'];

	if(in_array(trim($embarque[$i]['descricao']), $pacs)) {
		$servicosAdicionais[0]->setCodigoServicoAdicional(64);
	}else{
		$servicosAdicionais[0]->setCodigoServicoAdicional(19);
	}
    if($total_nota > 20) { 
		$valorDeclarado = str_replace('.', ',', trim($embarque[$i]['total_nota']));
	    $servicosAdicionais[0]->setValorDeclarado($valorDeclarado);
    } else {
	    $servicosAdicionais[0]->setValorDeclarado(100);
	}
    $dimensao = new \PhpSigep\Model\Dimensao();
    $dimensao->setTipo('002');
    $dimensao->setAltura($embarque[$i]['altura']);
    $dimensao->setLargura($embarque[$i]['largura']);
    $dimensao->setComprimento($embarque[$i]['comprimento']);
    $dimensao->setDiametro(NULL);

    $encomendas[$i] = new \PhpSigep\Model\ObjetoPostal();
    $encomendas[$i]->setEtiqueta($etiqueta);
    $encomendas[$i]->setServicoDePostagem($servicoDePostagem);
    $encomendas[$i]->setCubagem(NULL);
    $encomendas[$i]->setPeso($embarque[$i]['peso']);
    $encomendas[$i]->setDestinatario($destinatario);
    $encomendas[$i]->setDestino($destino);
    $encomendas[$i]->setServicosAdicionais($servicosAdicionais);
    $encomendas[$i]->setDimensao($dimensao);
}

$plp = new \PhpSigep\Model\PreListaDePostagem();
$plp->setAccessData($accessData);
$plp->setEncomendas(array($encomendas));
$plp->setRemetente($remetente);

$phpSigep = new PhpSigep\Services\SoapClient\Real();
$soapArgs = $phpSigep->fechaPlpVariosServicos($plp);

if($soapArgs->getErrorMsg() != ""){
    $response[] = array("resultado" => "false", "mensagem" => $soapArgs->getErrorMsg());
    echo json_encode($response);
    exit;
} else {
    $response[] = array("resultado" => true, "idplp" => $soapArgs->getResult()->getIdPlp());
}

// $soapArgs['listaEtiquetas'] = array_unique($soapArgs['listaEtiquetas']);
// Link de Desenvolvimento
// $url = "https://apphom.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

// Link de Produзгo
// $url = "https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl";

// try {
//     $client = new SoapClient($url, array("trace" => 1, "exception" => 1));
// } catch (Exception $e) {
//     $response[] = array("resultado" => "false", "mensagem" => $e->faultstring);
//     echo json_encode($response);
//     exit;
// }

// try {
//     $result = $client->__soapCall("fechaPlpVariosServicos", array($soapArgs));
// } catch (Exception $e) {
//     $response[] = array("resultado" => "false", "mensagem" => $e->faultstring);
//     echo json_encode($response);
//     exit;
// }

// $response[] = array("resultado" => true, "idplp" => $result->return);
echo json_encode($response);