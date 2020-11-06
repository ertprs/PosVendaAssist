<?php
// $params = include __DIR__ . '/helper-criar-pre-lista.php';

// $pdf  = new \PhpSigep\Pdf\ListaDePostagem($params, time());
// $pdf->render('I');

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../autentica_usuario.php';
include '../../funcao_correio.php';

$plp             = $_GET['plp'];
$etiquetas       = $_GET['etiquetas'];
$cartao_postagem = $_GET['cartao_postagem'];

$embarque = imprimePLP($etiquetas, $cartao_postagem);

require_once __DIR__ . '/bootstrap-exemplos.php';

function acentos ($string) {
    $array1 = array("á" => "a", "à" => "a", "â" => "a", "ã" => "a", "ä" => "a", 
        "é" => "e", "è" => "e", "ê" => "e", "ë" => "e", 
        "í" => "i", "ì" => "i", "î" => "i", "ï" => "i", 
        "ó" => "o", "ò" => "o", "ô" => "o", "õ" => "o", "ö" => "o", 
        "ú" => "u", "ù" => "u", "û" => "u", "ü" => "u", 
        "ç" => "c", 
        "Á" => "A", "À" => "A", "Â" => "A", "Ã" => "A", "Ä" => "A", 
        "É" => "E", "È" => "E", "Ê" => "E", "Ë" => "E", 
        "Í" => "I", "Ì" => "I", "Î" => "I", "Ï" => "I", 
        "Ó" => "O", "Ò" => "O", "Ô" => "O", "Õ" => "O", "Ö" => "O", 
        "Ú" => "U", "Ù" => "U", "Û" => "U", "Ü" => "U", "Ç" => "C");
    $string = strtr(utf8_decode($string), $array1);

    return $string;
}

$contrato   = $embarque['contrato'];
$accessData = new \PhpSigep\Model\AccessData();
$accessData->setCodAdministrativo($contrato['codigo_administrativo']);
$accessData->setUsuario($contrato['usuario']); //$contrato['usuario']
$accessData->setSenha($contrato['senha']);
$accessData->setCartaoPostagem($contrato['cartao']);
$accessData->setCnpjEmpresa($contrato['cnpj']);
$accessData->setNumeroContrato($contrato['contrato']);
$accessData->setAnoContrato($contrato['ano']);

$diretoria = new \PhpSigep\Model\Diretoria(10);
$diretoria->setNumero(10);
$diretoria->setNome("DR - Brasília");
$diretoria->setSigla("BSB");
$diretoria->setSigla("BSB");

$accessData->setDiretoria($diretoria);

$remetente          = new \PhpSigep\Model\Remetente();
$dados_remetente    = $embarque['remetente'];
$consultaCEP        = new \PhpSigep\Services\SoapClient\Real();
$endereco_remetente = $consultaCEP->consultaCep($dados_remetente['cep']);

$remetente->setNumeroContrato(NULL);
$remetente->setDiretoria(NULL);
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
    $etiqueta = new \PhpSigep\Model\Etiqueta();
    $etiqueta->setEtiquetaComDv($embarque[$i]['etiqueta']);

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
    $destino->setValorNotaFiscal(NULL);
    $destino->setNaturezaNotaFiscal(NULL);
    $destino->setDescricaoObjeto(NULL);
    $destino->setValorACobrar(NULL);

    $servicosAdicionais[0] = new \PhpSigep\Model\ServicoAdicional();
    $servicosAdicionais[0]->setCodigoServicoAdicional(19);
    $servicosAdicionais[0]->setValorDeclarado($embarque[$i]['total_nota']);

    $dimensao = new \PhpSigep\Model\Dimensao();
    $dimensao->setTipo(2);
    $dimensao->setAltura($embarque[$i]['altura']);
    $dimensao->setLargura($embarque[$i]['largura']);
    $dimensao->setComprimento($embarque[$i]['comprimento']);
    $dimensao->setDiametro(NULL);

    $encomendas[$i] = new \PhpSigep\Model\ObjetoPostal();
    $encomendas[$i]->setEtiqueta($etiqueta);
    $encomendas[$i]->setServicoDePostagem($servicoDePostagem);
    $encomendas[$i]->setCubagem($embarque[$i]['cubagem']);
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

$pdf  = new \PhpSigep\Pdf\ListaDePostagem($plp, $_GET['idplp']);
$pdf->render($plp);