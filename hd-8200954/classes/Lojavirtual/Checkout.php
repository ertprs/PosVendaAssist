<?php
namespace Lojavirtual;
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
require_once(dirname(__FILE__) . '/../../loja/integracoes/pagseguro/vendor/autoload.php');
require_once(dirname(__FILE__) . '/../../loja/integracoes/cielo/vendor/autoload.php');

use Lojavirtual\Controller;
use Lojavirtual\Loja;
use Lojavirtual\Posto;
use Cielo\API30\Merchant;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\CreditCard;
use Cielo\API30\Ecommerce\Request\CieloRequestException;

class Checkout extends Controller {
    public $ccvTrava = array(
                    "mastercard" => "3",
                    "elo" => "3",
                    "aura" => "0",
                    "visa" => "3",
                    "diners" => "3",
                    "jcb" => "3",
                    "discover" => "3",
                    "amex" => "4");

    public $bandeiras_cielo = array(
                        "VISA" => "Visa",
                        "MASTERCARD" => "Master",
                        "AMEX" => "Amex",
                        "ELO" => "Elo",
                        "AURA" => "Aura",
                        "JCB" => "JCB",
                        "DINERS" => "Diners",
                        "DISCOVER" => "Discover",
                        "HIPERCARD" => "Hipercard",
                        );


    public $erros_pagseguro = array(
              53019 => "DD Telefone � inv�lido",
              53021 => "Telefone inv�lido",
              53037 => "Token do cartao de credito � inv�lido",
              53052 => "Telefone  do cart�o de cr�dito inv�lido",
              53041 => "Valor de parcelamento inv�lido",
              53040 => "O valor da parcela � obrigat�rio",
              53047 => "O Anivers�rio do titular do cart�o de cr�dito � obrigat�rio.",
              53117 => "CNPJ � inv�lido",
              53046 => "CPF do titular do cart�o de cr�dito � inv�lido.",
              53048 => "Anivers�rio do titular do cart�o de cr�dito � inv�lida.",
              53122 => "E-mail inv�lido.",
              53045 => "CPF do titular do cart�o de cr�dito � obrigat�rio.",
              53045 => "Nome do titular do cart�o de cr�dito inv�lido.",
              53026 => "O numero do endere�o � obrigat�rio.",
            );

    public $erros_cielo = array(
        0   => "Dado enviado excede o tamanho do campo",
        100 => "Campo enviado est� vazio ou invalido",
        101 => "Campo enviado est� vazio ou invalido",
        102 => "Campo enviado est� vazio ou invalido",
        103 => "Caracteres especiais n�o permitidos",
        104 => "Campo enviado est� vazio ou invalido",
        105 => "Campo enviado est� vazio ou invalido",
        106 => "Campo enviado est� vazio ou invalido",
        107 => "Campo enviado excede o tamanho ou contem caracteres especiais",
        108 => "Valor da transa��o deve ser maior que '0'",
        109 => "Campo enviado est� vazio ou invalido",
        110 => "Campo enviado est� vazio ou invalido",
        111 => "Campo enviado est� vazio ou invalido",
        112 => "Campo enviado est� vazio ou invalido",
        113 => "Campo enviado est� vazio ou invalido",
        114 => "O MerchantId enviado n�o � um GUID",
        115 => "O MerchantID n�o existe ou pertence a outro ambiente (EX: Sandbox)",
        116 => "Loja bloqueada, entre em contato com o suporte Cielo",
        117 => "Campo enviado est� vazio ou invalido",
        118 => "Campo enviado est� vazio ou invalido",
        119 => "N� 'Payment' n�o enviado",
        120 => "IP bloqueado por quest�es de seguran�a",
        121 => "N� 'Customer' n�o enviado",
        122 => "Campo enviado est� vazio ou invalido",
        123 => "Numero de parcelas deve ser superior a 1",
        124 => "Campo enviado est� vazio ou invalido",
        125 => "Campo enviado est� vazio ou invalido",
        126 => "Campo enviado est� vazio ou invalido",
        127 => "Numero do cart�o de cr�dito � obrigat�rio",
        128 => "Numero do cart�o superiro a 16 digitos",
        129 => "Meio de pagamento n�o vinculado a loja ou Provider invalido",
        130 => "Could not get Credit Card",
        131 => "Campo enviado est� vazio ou invalido",
        132 => "O Merchantkey enviado n�o � um v�lido",
        133 => "Provider enviado n�o existe",
        134 => "Dado enviado excede o tamanho do campo",
        135 => "Dado enviado excede o tamanho do campo",
        136 => "Dado enviado excede o tamanho do campo",
        137 => "Dado enviado excede o tamanho do campo",
        138 => "Dado enviado excede o tamanho do campo",
        139 => "Dado enviado excede o tamanho do campo",
        140 => "Dado enviado excede o tamanho do campo",
        141 => "Dado enviado excede o tamanho do campo",
        142 => "Dado enviado excede o tamanho do campo",
        143 => "Dado enviado excede o tamanho do campo",
        144 => "Dado enviado excede o tamanho do campo",
        145 => "Dado enviado excede o tamanho do campo",
        146 => "Dado enviado excede o tamanho do campo",
        147 => "Dado enviado excede o tamanho do campo",
        148 => "Dado enviado excede o tamanho do campo",
        149 => "Dado enviado excede o tamanho do campo",
        150 => "Dado enviado excede o tamanho do campo",
        151 => "Dado enviado excede o tamanho do campo",
        152 => "Dado enviado excede o tamanho do campo",
        153 => "Dado enviado excede o tamanho do campo",
        154 => "Dado enviado excede o tamanho do campo",
        155 => "Dado enviado excede o tamanho do campo",
        156 => "Dado enviado excede o tamanho do campo",
        157 => "Dado enviado excede o tamanho do campo",
        158 => "Dado enviado excede o tamanho do campo",
        159 => "Dado enviado excede o tamanho do campo",
        160 => "Dado enviado excede o tamanho do campo",
        161 => "Dado enviado excede o tamanho do campo",
        162 => "Dado enviado excede o tamanho do campo",
        163 => "URL de retorno n�o � valida - N�o � aceito pagina��o ou exten��es (EX .PHP) na URL de retorno",
        166 => "AuthorizeNow is required",
        167 => "Antifraude n�o vinculado ao cadastro do lojista",
        168 => "Recorrencia n�o encontrada",
        169 => "Recorrencia n�o est� ativa. Execu��o paralizada",
        170 => "Cart�o protegido n�o vinculado ao cadastro do lojista",
        171 => "Falha no processamento do pedido - Entre em contato com o suporte Cielo",
        172 => "Falha na valida��o das credenciadas enviadas",
        173 => "Meio de pagamento n�o vinculado ao cadastro do lojista",
        174 => "Campo enviado est� vazio ou invalido",
        175 => "Campo enviado est� vazio ou invalido",
        176 => "Campo enviado est� vazio ou invalido",
        177 => "Campo enviado est� vazio ou invalido",
        178 => "Campo enviado est� vazio ou invalido",
        179 => "Campo enviado est� vazio ou invalido",
        180 => "Token do Cart�o protegido n�o encontrado",
        181 => "Token do Cart�o protegido bloqueado",
        182 => "Bandeira do cart�o n�o enviado",
        183 => "Data de nascimento invalida ou futura",
        184 => "Falha no formado ta requisi��o. Verifique o c�digo enviado",
        185 => "Bandeira n�o suportada pela API Cielo",
        186 => "Meio de pagamento n�o suporta o comando enviado",
        187  => "ExtraData Collection contains one or more duplicated names",
        188  => "Avs with CPF invalid",
        189  => "Dado enviado excede o tamanho do campo",
        190  => "Dado enviado excede o tamanho do campo",
        190  => "Dado enviado excede o tamanho do campo",
        191  => "Dado enviado excede o tamanho do campo",
        192  => "CEP enviado � invalido",
        193  => "Valor para realiza��o do SPLIT deve ser superior a 0",
        194  => "SPLIT n�o habilitado para o cadastro da loja",
        195  => "Validados de plataformas n�o enviado",
        196  => "Campo obrigat�rio n�o enviado",
        197 => "Campo obrigat�rio n�o enviado",
        198 => "Campo obrigat�rio n�o enviado",
        199 => "Campo obrigat�rio n�o enviado",
        200 => "Campo obrigat�rio n�o enviado",
        201 => "Campo obrigat�rio n�o enviado",
        202 => "Campo obrigat�rio n�o enviado",
        203 => "Campo obrigat�rio n�o enviado",
        204 => "Campo obrigat�rio n�o enviado",
        205 => "Campo obrigat�rio n�o enviado",
        206 => "Dado enviado excede o tamanho do campo",
        207 => "Dado enviado excede o tamanho do campo",
        208 => "Dado enviado excede o tamanho do campo",
        209 => "Dado enviado excede o tamanho do campo",
        210 => "Campo obrigat�rio n�o enviado",
        211 => "Dados da Visa Checkout invalidos",
        212 => "Dado de Wallet enviado n�o � valido",
        213 => "Cart�o de cr�dito enviado � invalido",
        214 => "Portador do cart�o n�o deve conter caracteres especiais",
        215 => "Campo obrigat�rio n�o enviado",
        216 => "IP bloqueado por quest�es de seguran�a",
        300 => "MerchantId was not found",
        301 => "Request IP is not allowed",
        302 => "Sent MerchantOrderId is duplicated",
        303 => "Sent OrderId does not exist",
        304 => "Customer Identity is required",
        306 => "Merchant is blocked",
        307 => "Transa��o n�o encontrada ou n�o existente no ambiente.",
        308 => "Transa��o n�o pode ser capturada - Entre em contato com o suporte Cielo",
        309 => "Transa��o n�o pode ser Cancelada - Entre em contato com o suporte Cielo",
        310 => "Comando enviado n�o suportado pelo meio de pagamento",
        311 => "Cancelamento ap�s 24 horas n�o liberado para o lojista",
        312 => "Transa��o n�o permite cancelamento ap�s 24 horas",
        313 => "Transa��o recorrente n�o encontrada ou n�o disponivel no ambiente",
        314 => "Invalid Integration",
        315 => "Cannot change NextRecurrency with pending payment",
        316 => "N�o � permitido alterada dada da recorrencia para uma data passada",
        317 => "Invalid Recurrency Day",
        318 => "No transaction found",
        319 => "Recorrencia n�o vinculada ao cadastro do lojista",
        320 => "Can not Update Affiliation Because this Recurrency not Affiliation saved",
        321 => "Can not set EndDate to before next recurrency.",
        322 => "Zero Dollar n�o vinculado ao cadastro do lojista",
        323 => "Consulta de Bins n�o vinculada ao cadastro do lojista",
    );

    public $status_cielo_cartao_credito = array(
            '4'   => "Opera��o realizada com sucesso",
            '6'   => "Opera��o realizada com sucesso",
            '5'  => "N�o Autorizada",
            '05'  => "N�o Autorizada",
            '57'  => "Cart�o Expirado",
            '78'  => "Cart�o Bloqueado",
            '99'  => "Time Out",
            '77'  => "Cart�o Cancelado",
            '70'  => "roblemas com o Cart�o de Cr�dito",
            '99'  => "Operation Successful / Time Out",
    );

    public $status_maxipago = array(
                                "1" => "Em andamento",
                                "3" => "Capturada",
                                "6" => "Autorizada",
                                "7" => "Negada",
                                "9" => "Cancelada (Voided)",
                                "4" => "Pendente de captura",
                                "5" => "Pendente de autoriza��o",
                                "8" => "Revertida",
                                "10" => "Paga",
                                "22" => "Boleto Emitido",
                                "34" => "Boleto Visualizado",
                                "35" => "Boleto Pago A Menor",
                                "36" => "Boleto Page A Maior",
                                "11" => "Pendente de Confirma��o",
                                "12" => "Pendente de Revis�o (verificar com Suporte)",
                                "13" => "Pendente de Revers�o",
                                "14" => "Pendente de Captura (retentativa)",
                                "16" => "Pendente de Estorno",
                                "18" => "Pendente de Void",
                                "19" => "Pendente de Void (retentativa)",
                                "29" => "Pendente de Autentica��o",
                                "30" => "Autenticada",
                                "31" => "Pendente de Estorno (retentativa)",
                                "32" => "Autentica��o em andamento",
                                "33" => "Autentica��o enviada",
                                "38" => "Pendente de envio de arquivo de Estorno",
                                "44" => "Aprovada na Fraude",
                                "45" => "Negada por Fraude",
                                "46" => "Revis�o de Fraude",
        );

    public $classPosto;
    public $classLoja;
    public $classCarrinhoCompra;

    public $MERCHANT_ID  = "2d121366-24d7-4fc7-a950-64a12f2b6e82";
    public $MERCHANT_KEY = "ZZULLKXEVVONBPGIULQHUEQSYFJIUROSMAUXOMKV";
    public $credAcountEmail;
    public $credAcountToken;

    public function __construct() {
        parent::__construct();

        $this->classPosto = new Posto();
        $this->classLoja  = new Loja();
        $this->classCarrinhoCompra = new CarrinhoCompra();


        if (isset($this->classLoja->configuracao_pagamento["meio"]["cielo"])) {
            if ($this->classLoja->configuracao_pagamento["meio"]["cielo"]["ambiente"] == 'sandbox') {
                $this->MERCHANT_ID  = $this->classLoja->configuracao_pagamento["meio"]["cielo"]["merchant_id_sandbox"];
                $this->MERCHANT_KEY = $this->classLoja->configuracao_pagamento["meio"]["cielo"]["merchant_key_sandbox"];
            } else {
                $this->MERCHANT_ID  = $this->classLoja->configuracao_pagamento["meio"]["cielo"]["merchant_id_producao"];
                $this->MERCHANT_KEY = $this->classLoja->configuracao_pagamento["meio"]["cielo"]["merchant_key_producao"];
            }
        }

        if (isset($this->classLoja->configuracao_pagamento["meio"]["pagseguro"])) {
            if ($this->classLoja->configuracao_pagamento["meio"]["pagseguro"]["ambiente"] == "sandbox") {
                $this->credAcountEmail = $this->classLoja->configuracao_pagamento["meio"]["pagseguro"]["email_sandbox"];
                $this->credAcountToken = $this->classLoja->configuracao_pagamento["meio"]["pagseguro"]["token_sandbox"];
                $_ambiente = "sandbox";
            } else {
                $this->credAcountEmail = $this->classLoja->configuracao_pagamento["meio"]["pagseguro"]["email_producao"];
                $this->credAcountToken = $this->classLoja->configuracao_pagamento["meio"]["pagseguro"]["token_producao"];
                $_ambiente = "production";
            }
            \PagSeguro\Configuration\Configure::setEnvironment($_ambiente);
            \PagSeguro\Configuration\Configure::setAccountCredentials($this->credAcountEmail, $this->credAcountToken);
        }

    }

    /* REFERENTE A INTEGRA��O COM  PAGSEGURO */
    public function processaPagSeguro($dadosPedido, $tipo_pagamento, $posto, $dadosPost) {
        \PagSeguro\Library::initialize();
        \PagSeguro\Library::cmsVersion()->setName("Nome")->setRelease("1.0.0");
        \PagSeguro\Library::moduleVersion()->setName("Nome")->setRelease("1.0.0");

        if ($tipo_pagamento == "CREDIT_CARD") {
            return $this->pagamentoCartaoCreditoPagSeguro($dadosPedido, $posto, $dadosPost);
        }
        if ($tipo_pagamento == "BOLETO") {
            return $this->pagamentoBoletoPagSeguro($dadosPedido, $posto, $dadosPost);
        }
    }

    public function pagamentoBoletoPagSeguro($dadosPedido, $posto, $dadosPost) {
        if (empty($dadosPedido)) {
            return array("erro" => true, "msg" => utf8_encode("Dados n�o enviado"));
        }

        $objPosto = $this->classPosto->get($posto);
        if (isset($objPosto->erro)) {
            return array("erro" => true, "msg" => utf8_encode("Dados n�o enviado"));
        }

        $boleto          = new \PagSeguro\Domains\Requests\DirectPayment\Boleto();
        $vetorFone       = explode(')',  $objPosto->contato_fone_comercial);
        $senderAreaCode  = trim(str_replace(array("("," ", ")"), "", $vetorFone[0])); 
        $senderPhone     = trim(str_replace(array("-"," ", "."), "", $vetorFone[1]));

        $objPosto->contato_email = "teste@sandbox.pagseguro.com.br";

        $boleto->setMode('DEFAULT');       
        $boleto->setCurrency("BRL");

        $totalPedido = array();
        foreach ($dadosPost['produtos'] as $key => $rows) {
            $totalPedido[] = ($rows["qtde"]*$rows["valor"]);
            $boleto->addItems()->withParameters(
                $rows["item"],
                $rows["nome"],
                $rows["qtde"],
                $rows["valor"]
            );
        }   
        $totalPedido = array_sum($totalPedido);
        $boleto->setReference($dadosPedido["pedido"]);

        //$boleto->setExtraAmount(-71.00);//desconto no boleto

        $boleto->setSender()->setName($objPosto->nome);
        $boleto->setSender()->setEmail($objPosto->contato_email);

        $boleto->setSender()->setPhone()->withParameters(
            $senderAreaCode,
            $senderPhone
        );

        $boleto->setSender()->setDocument()->withParameters(
            'CNPJ',
            str_replace(array('.','/','-'), "", "14.533.481/0001-05")
        );
        /*$boleto->setSender()->setDocument()->withParameters(
            'CNPJ',
            str_replace(array('.','/','-'), "", $objPosto->cnpj)
        );*/

        $boleto->setSender()->setHash($dadosPost["sendHarsh"]);
        $boleto->setSender()->setIp('127.0.0.0');

        $boleto->setShipping()->setAddress()->withParameters(
            $objPosto->contato_endereco,
            $objPosto->contato_numero,
            $objPosto->contato_bairro,
            str_replace(array('-'), "", $objPosto->contato_cep),
            $objPosto->contato_cidade,
            $objPosto->contato_estado,
            'BRA',
            $objPosto->contato_complemento
        );
        $insereDadosPagamento = $this->trataInsereDadosBoletoPagamentoPagSeguro($boleto);
        $id_pagamento = $this->classCarrinhoCompra->inserePagamentoB2B($insereDadosPagamento, "BOLETO", $totalPedido, $dadosPedido["pedido"]);
        
        try {

            $result = $boleto->register(\PagSeguro\Configuration\Configure::getAccountCredentials());
            if (strlen($result->getPaymentLink()) > 0) {
                $UPDadosPagamento = $this->trataUpdateDadosBoletoPagamentoPagSeguro($result);
                $this->classCarrinhoCompra->atualizaPagamentoB2B($id_pagamento, $UPDadosPagamento, "BOLETO", $dadosPedido["pedido"], $result->getStatus());
                return array("erro" => false,"tipo_pagamento_escolhido" => "BOLETO", "msg" => utf8_encode("Pedido criado com sucesso"), "pedido" => $dadosPedido["pedido"], "link_boleto" => $result->getPaymentLink(), "status_boleto" => $result->getStatus());
            } else {
                return array("erro" => true, "msg" => utf8_encode("N�o foi poss�vel gerar o boleto"));
            }
        } catch (\Exception $e) {

            $erros = simplexml_load_string($e->getMessage());
            $json  = json_encode($erros) ;   
            $jsond = json_decode($json, 1) ;

            $mensagem = array();
            if (isset($jsond["error"]['code'])) {

                $mensagem[] = utf8_encode($this->erros_pagseguro[$jsond["error"]['code']]); 

            } else {
                foreach ($jsond["error"] as $key => $value) {
                    $mensagem[] = utf8_encode($this->erros_pagseguro[$value['code']]); 
                }
            }

            return array("erro" => true, "msg" => implode("<br />", $mensagem));
        }
    }

    public function trataUpdateDadosBoletoPagamentoPagSeguro($result) {

        $UPDadosPagamento["date"]                               = $result->getDate();
        $UPDadosPagamento["code"]                               = $result->getCode();
        $UPDadosPagamento["reference"]                          = $result->getReference();
        $UPDadosPagamento["type"]                               = $result->getType();
        $UPDadosPagamento["status"]                             = $result->getStatus();
        $UPDadosPagamento["installmentCount"]                   = $result->getInstallmentCount();
        $UPDadosPagamento["cancelationSource"]                  = $result->getCancelationSource();
        $UPDadosPagamento["discountAmount"]                     = $result->getDiscountAmount();
        $UPDadosPagamento["extraAmount"]                        = $result->getExtraAmount();
        $UPDadosPagamento["feeAmount"]                          = $result->getFeeAmount();
        $UPDadosPagamento["grossAmount"]                        = $result->getGrossAmount();
        $UPDadosPagamento["netAmount"]                          = $result->getNetAmount();
        $UPDadosPagamento["itemCount"]                          = $result->getItemCount();
        foreach ($result->getItems() as $key => $value) {
            $UPDadosPagamento["items"][$key]["id"]              = $value->getId();
            $UPDadosPagamento["items"][$key]["description"]     = utf8_encode($value->getDescription());
            $UPDadosPagamento["items"][$key]["quantity"]        = $value->getQuantity();
            $UPDadosPagamento["items"][$key]["amount"]          = $value->getAmount();
            $UPDadosPagamento["items"][$key]["weight"]          = $value->getWeight();
            $UPDadosPagamento["items"][$key]["shippingCost"]    = $value->getShippingCost();
        }
        $UPDadosPagamento["paymentMethod"]["code"]              = $result->getPaymentMethod()->getCode();
        $UPDadosPagamento["paymentMethod"]["type"]              = $result->getPaymentMethod()->getType();
        $UPDadosPagamento["sender"]["name"]                     = utf8_encode($result->getSender()->getName());
        $UPDadosPagamento["sender"]["email"]                    = $result->getSender()->getEmail();
        $UPDadosPagamento["sender"]["phone"]["areaCode"]        = $result->getSender()->getPhone()->getAreaCode();
        $UPDadosPagamento["sender"]["phone"]["number"]          = $result->getSender()->getPhone()->getNumber();
        $UPDadosPagamento["shipping"]["address"]["street"]      = utf8_encode($result->getShipping()->getAddress()->getStreet());
        $UPDadosPagamento["shipping"]["address"]["number"]      = utf8_encode($result->getShipping()->getAddress()->getNumber());
        $UPDadosPagamento["shipping"]["address"]["complement"]  = utf8_encode($result->getShipping()->getAddress()->getComplement());
        $UPDadosPagamento["shipping"]["address"]["district"]    = utf8_encode($result->getShipping()->getAddress()->getDistrict());
        $UPDadosPagamento["shipping"]["address"]["postalCode"]  = utf8_encode($result->getShipping()->getAddress()->getPostalCode());
        $UPDadosPagamento["shipping"]["address"]["city"]        = utf8_encode($result->getShipping()->getAddress()->getCity());
        $UPDadosPagamento["shipping"]["address"]["state"]       = utf8_encode($result->getShipping()->getAddress()->getState());
        $UPDadosPagamento["shipping"]["address"]["country"]     = utf8_encode($result->getShipping()->getAddress()->getCountry());
        $UPDadosPagamento["shipping"]["type"]["type"]           = utf8_encode($result->getShipping()->getType()->getType());
        $UPDadosPagamento["shipping"]["cost"]["cost"]           = $result->getShipping()->getCost()->getCost();
        $UPDadosPagamento["paymentLink"]                        = $result->getPaymentLink();
        
        return $UPDadosPagamento;
    }

    public function trataInsereDadosBoletoPagamentoPagSeguro($boleto) {
       
        $insereDadosPagamento["currency"]                           = $boleto->getCurrency();
        $insereDadosPagamento["extraAmount"]                        = $boleto->getExtraAmount();
        foreach ($boleto->getItems() as $key => $value) {
            $insereDadosPagamento["items"][$key]["id"]              = $value->getId();
            $insereDadosPagamento["items"][$key]["description"]     = utf8_encode($value->getDescription());
            $insereDadosPagamento["items"][$key]["quantity"]        = $value->getQuantity();
            $insereDadosPagamento["items"][$key]["amount"]          = $value->getAmount();
            $insereDadosPagamento["items"][$key]["weight"]          = $value->getWeight();
            $insereDadosPagamento["items"][$key]["shippingCost"]    = $value->getShippingCost();
        }
        $insereDadosPagamento["mode"]                                       = $boleto->getMode();
        $insereDadosPagamento["sender"]["ip"]                               = $boleto->getSender()->getIp();
        $insereDadosPagamento["sender"]["hash"]                             = $boleto->getSender()->getHash();
        $insereDadosPagamento["sender"]["name"]                             = utf8_encode($boleto->getSender()->getName());
        $insereDadosPagamento["sender"]["email"]                            = $boleto->getSender()->getEmail();
        $insereDadosPagamento["sender"]["phone"]["areaCode"]                = $boleto->getSender()->getPhone()->getAreaCode();
        $insereDadosPagamento["sender"]["phone"]["number"]                  = $boleto->getSender()->getPhone()->getNumber();
        foreach ($boleto->getSender()->getDocuments() as $key => $value) {
            $insereDadosPagamento["sender"]["documents"][$key]["type"]      = $value->getType();
            $insereDadosPagamento["sender"]["documents"][$key]["identifier"]= $value->getIdentifier();
        }
        $insereDadosPagamento["shipping"]["address"]["street"]              = utf8_encode($boleto->getShipping()->getAddress()->getStreet());
        $insereDadosPagamento["shipping"]["address"]["number"]              = utf8_encode($boleto->getShipping()->getAddress()->getNumber());
        $insereDadosPagamento["shipping"]["address"]["complement"]          = utf8_encode($boleto->getShipping()->getAddress()->getComplement());
        $insereDadosPagamento["shipping"]["address"]["district"]            = utf8_encode($boleto->getShipping()->getAddress()->getDistrict());
        $insereDadosPagamento["shipping"]["address"]["postalCode"]          = utf8_encode($boleto->getShipping()->getAddress()->getPostalCode());
        $insereDadosPagamento["shipping"]["address"]["city"]                = utf8_encode($boleto->getShipping()->getAddress()->getCity());
        $insereDadosPagamento["shipping"]["address"]["state"]               = utf8_encode($boleto->getShipping()->getAddress()->getState());
        $insereDadosPagamento["shipping"]["address"]["country"]             = utf8_encode($boleto->getShipping()->getAddress()->getCountry());
        $insereDadosPagamento["shipping"]["type"]                           = $boleto->getShipping()->getType();
        $insereDadosPagamento["shipping"]["cost"]                           = $boleto->getShipping()->getCost();
        $insereDadosPagamento["reference"]                                  = $boleto->getReference();

        return $insereDadosPagamento;

    }

    public function pagamentoCartaoCreditoPagSeguro($dadosPedido, $posto, $dadosPost) {

        if (empty($dadosPedido)) {
            return array("erro" => true, "msg" => utf8_encode("Dados n�o enviado"));
        }

        $objPosto = $this->classPosto->get($posto);
        if (isset($objPosto->erro)) {
            return array("erro" => true, "msg" => utf8_encode("Dados n�o enviado"));
        }

        $creditCard = new \PagSeguro\Domains\Requests\DirectPayment\CreditCard();

        $objPosto->contato_email = "teste@sandbox.pagseguro.com.br";

        $qtde_parcelas                  = explode("|", $dadosPost["qtde_parcelas"]);
        $qtdeparcelas                   = $qtde_parcelas[0];
        $valorparcelas                  = $qtde_parcelas[1];
        $valor_parcelas                 = $valorparcelas ;

        $creditCard->setCurrency("BRL");

        foreach ($dadosPost["produtos"] as $key => $rows) {
            $totalPedido[] = ($rows["qtde"]*$rows["valor"]);
            $creditCard->addItems()->withParameters(
                $rows["item"],
                $rows["nome"],
                $rows["qtde"],
                $rows["valor"]
            );
        }
        $totalPedido = array_sum($totalPedido);


        $senderCNPJ                     = str_replace(array('.','/','-'), "", $objPosto->cnpj);
        $senderCPFCartao                = str_replace(array('.','/','-'), "", $dadosPost["cpf_cartao"]);

        if (strlen($senderCPFCartao) >= 14) {
            $CPFCartao                  = "CNPJ";
            $senderCPFCartao            = $senderCPFCartao;
        } else {
            $CPFCartao                  = "CPF";
            $senderCPFCartao            = $senderCPFCartao;
        }

        $vetorFone                      = explode(')',  $objPosto->contato_fone_comercial);
        $senderAreaCode                 = trim(str_replace(array("("," ", ")"), "", $vetorFone[0])); 
        $senderPhone                    = trim(str_replace(array("-"," ", "."), "", $vetorFone[1]));

        $creditCard->setReference($dadosPedido["pedido"]);
        $creditCard->setSender()->setName($objPosto->nome);
        $creditCard->setSender()->setEmail($objPosto->contato_email);
        $creditCard->setSender()->setPhone()->withParameters(
            $senderAreaCode,
            $senderPhone
        );

        $creditCard->setSender()->setDocument()->withParameters(
            'CNPJ',
             $senderCNPJ
        );

        $creditCard->setSender()->setHash($dadosPost["sendHarsh"]);

        $creditCard->setSender()->setIp('127.0.0.0');

        $creditCard->setShipping()->setAddress()->withParameters(
            $objPosto->contato_endereco,
            $objPosto->contato_numero,
            $objPosto->contato_bairro,
            str_replace(array('-'), "", $objPosto->contato_cep),
            $objPosto->contato_cidade,
            $objPosto->contato_estado,
            'BRA',
            $objPosto->contato_complemento
        );

        $creditCard->setBilling()->setAddress()->withParameters(
            $objPosto->contato_endereco,
            $objPosto->contato_numero,
            $objPosto->contato_bairro,
            str_replace(array('-'), "", $objPosto->contato_cep),
            $objPosto->contato_cidade,
            $objPosto->contato_estado,
            'BRA',
            $objPosto->contato_complemento
        );

        $creditCard->setToken($dadosPost["cardHashs"]);
        $creditCard->setInstallment()->withParameters(intval($qtdeparcelas), $valor_parcelas, intval(6));
        $creditCard->setHolder()->setBirthdate($dadosPost["aniversario_titular_cartao"]);
        $creditCard->setHolder()->setName($dadosPost["nome_titular_cartao"]); // Equals in Credit Card

        $creditCard->setHolder()->setPhone()->withParameters(
            $senderAreaCode,
            $senderPhone
        );

        $creditCard->setHolder()->setDocument()->withParameters(
            $CPFCartao,
            $senderCPFCartao
        );
        $creditCard->setMode('DEFAULT');

        $parcelamento["quantity"] = intval($qtdeparcelas);
        $parcelamento["value"] = $valor_parcelas;
        $parcelamento["noInterestInstallmentQuantity"] = intval(6);
        $insereDadosPagamento = $this->trataInsereDadosCartaoPagamentoPagSeguro($creditCard, $parcelamento);

        $id_pagamento = $this->classCarrinhoCompra->inserePagamentoB2B($insereDadosPagamento, "CREDIT_CARD", $totalPedido, $dadosPedido["pedido"]);

        try {
            $result = $creditCard->register(
                \PagSeguro\Configuration\Configure::getAccountCredentials()
            );

            if (strlen($result->getCode()) > 0) {
                $UPDadosPagamento = $this->trataUpdateDadosCartaoPagamentoPagSeguro($result);
                $this->classCarrinhoCompra->atualizaPagamentoB2B($id_pagamento, $UPDadosPagamento, "CREDIT_CARD", $dadosPedido["pedido"], $result->getStatus());

                return array("erro" => false, "pedido" => $dadosPedido["pedido"], "status_ps" => $result->getStatus(), "code_auto" => $result->getCode());
            }

            return array("erro" => true, "msg" => utf8_encode("Erro ao conectar com o gateway de pagamento"));

        } catch (\Exception $e) {

            $erros = simplexml_load_string($e->getMessage());
            $json  = json_encode($erros) ;   
            $jsond = json_decode($json, 1) ;
            $mensagem = array();
            if (isset($jsond["error"]['code'])) {

                $mensagem[] = utf8_encode($this->erros_pagseguro[$jsond["error"]['code']]); 

            } else {
                foreach ($jsond["error"] as $key => $value) {
                    $mensagem[] = utf8_encode($this->erros_pagseguro[$value['code']]); 
                }
            }


            return array("erro" => true, "msg" => implode("<br />", $mensagem));
        }
    }

    public function trataInsereDadosCartaoPagamentoPagSeguro($cartao, $parcelamento) {

        $insereDadosPagamento["currency"]                           = $cartao->getCurrency();
        $insereDadosPagamento["extraAmount"]                        = $cartao->getExtraAmount();
        $insereDadosPagamento["installment"]["installment"]["quantity"]                     = $parcelamento["quantity"];
        $insereDadosPagamento["installment"]["installment"]["value"]                        = $parcelamento["value"];
        $insereDadosPagamento["installment"]["installment"]["noInterestInstallmentQuantity"]= $parcelamento["noInterestInstallmentQuantity"];

        foreach ($cartao->getItems() as $key => $value) {
            $insereDadosPagamento["items"][$key]["id"]              = $value->getId();
            $insereDadosPagamento["items"][$key]["description"]     = utf8_encode($value->getDescription());
            $insereDadosPagamento["items"][$key]["quantity"]        = $value->getQuantity();
            $insereDadosPagamento["items"][$key]["amount"]          = $value->getAmount();
            $insereDadosPagamento["items"][$key]["weight"]          = $value->getWeight();
            $insereDadosPagamento["items"][$key]["shippingCost"]    = $value->getShippingCost();
        }


        $insereDadosPagamento["holder"]["name"]                             = utf8_encode($cartao->getHolder()->getName());
        $insereDadosPagamento["holder"]["birthDate"]                        = $cartao->getHolder()->getBirthDate();
        $insereDadosPagamento["holder"]["phone"]["areaCode"]                = $cartao->getHolder()->getPhone()->getAreaCode();
        $insereDadosPagamento["holder"]["phone"]["number"]                  = $cartao->getHolder()->getPhone()->getNumber();
        $insereDadosPagamento["holder"]["documents"]["type"]                = $cartao->getHolder()->getDocuments()->getType();
        $insereDadosPagamento["holder"]["documents"]["identifier"]          = $cartao->getHolder()->getDocuments()->getIdentifier();
        $insereDadosPagamento["sender"]["ip"]                               = $cartao->getSender()->getIp();
        $insereDadosPagamento["sender"]["hash"]                             = $cartao->getSender()->getHash();
        $insereDadosPagamento["sender"]["name"]                             = utf8_encode($cartao->getSender()->getName());
        $insereDadosPagamento["sender"]["email"]                            = $cartao->getSender()->getEmail();
        $insereDadosPagamento["sender"]["phone"]["areaCode"]                = $cartao->getSender()->getPhone()->getAreaCode();
        $insereDadosPagamento["sender"]["phone"]["number"]                  = $cartao->getSender()->getPhone()->getNumber();
        foreach ($cartao->getSender()->getDocuments() as $key => $value) {
            $insereDadosPagamento["sender"]["documents"][$key]["type"]      = $value->getType();
            $insereDadosPagamento["sender"]["documents"][$key]["identifier"]= $value->getIdentifier();
        }

        $insereDadosPagamento["mode"]                                      = $cartao->getMode();
        $insereDadosPagamento["billing"]["address"]["street"]              = utf8_encode($cartao->getBilling()->getAddress()->getStreet());
        $insereDadosPagamento["billing"]["address"]["number"]              = utf8_encode($cartao->getBilling()->getAddress()->getNumber());
        $insereDadosPagamento["billing"]["address"]["complement"]          = utf8_encode($cartao->getBilling()->getAddress()->getComplement());
        $insereDadosPagamento["billing"]["address"]["district"]            = utf8_encode($cartao->getBilling()->getAddress()->getDistrict());
        $insereDadosPagamento["billing"]["address"]["postalCode"]          = utf8_encode($cartao->getBilling()->getAddress()->getPostalCode());
        $insereDadosPagamento["billing"]["address"]["city"]                = utf8_encode($cartao->getBilling()->getAddress()->getCity());
        $insereDadosPagamento["billing"]["address"]["state"]               = utf8_encode($cartao->getBilling()->getAddress()->getState());
        $insereDadosPagamento["billing"]["address"]["country"]             = utf8_encode($cartao->getBilling()->getAddress()->getCountry());
        

        $insereDadosPagamento["shipping"]["address"]["street"]              = utf8_encode($cartao->getShipping()->getAddress()->getStreet());
        $insereDadosPagamento["shipping"]["address"]["number"]              = utf8_encode($cartao->getShipping()->getAddress()->getNumber());
        $insereDadosPagamento["shipping"]["address"]["complement"]          = utf8_encode($cartao->getShipping()->getAddress()->getComplement());
        $insereDadosPagamento["shipping"]["address"]["district"]            = utf8_encode($cartao->getShipping()->getAddress()->getDistrict());
        $insereDadosPagamento["shipping"]["address"]["postalCode"]          = utf8_encode($cartao->getShipping()->getAddress()->getPostalCode());
        $insereDadosPagamento["shipping"]["address"]["city"]                = utf8_encode($cartao->getShipping()->getAddress()->getCity());
        $insereDadosPagamento["shipping"]["address"]["state"]               = utf8_encode($cartao->getShipping()->getAddress()->getState());
        $insereDadosPagamento["shipping"]["address"]["country"]             = utf8_encode($cartao->getShipping()->getAddress()->getCountry());
        $insereDadosPagamento["shipping"]["type"]                           = $cartao->getShipping()->getType();
        $insereDadosPagamento["shipping"]["cost"]                           = $cartao->getShipping()->getCost();


        $insereDadosPagamento["reference"]                                  = $cartao->getReference();

        return $insereDadosPagamento;

    }

    public function trataUpdateDadosCartaoPagamentoPagSeguro($result) {

        $UPDadosPagamento["date"]                               = $result->getDate();
        $UPDadosPagamento["code"]                               = $result->getCode();
        $UPDadosPagamento["reference"]                          = $result->getReference();
        $UPDadosPagamento["type"]                               = $result->getType();
        $UPDadosPagamento["status"]                             = $result->getStatus();
        $UPDadosPagamento["installmentCount"]                   = $result->getInstallmentCount();
        $UPDadosPagamento["cancelationSource"]                  = $result->getCancelationSource();
        $UPDadosPagamento["discountAmount"]                     = $result->getDiscountAmount();
        $UPDadosPagamento["extraAmount"]                        = $result->getExtraAmount();
        $UPDadosPagamento["feeAmount"]                          = $result->getFeeAmount();
        $UPDadosPagamento["grossAmount"]                        = $result->getGrossAmount();
        $UPDadosPagamento["netAmount"]                          = $result->getNetAmount();
        $UPDadosPagamento["itemCount"]                          = $result->getItemCount();
        $UPDadosPagamento["paymentMethod"]["code"]              = $result->getPaymentMethod()->getCode();
        $UPDadosPagamento["paymentMethod"]["type"]              = $result->getPaymentMethod()->getType();
      
        return $UPDadosPagamento;
    }

    /* REFERENTE A INTEGRA��O COM MAXIPAGO */
    public function processaMaxiPago($dadosPedido, $tipo_pagamento, $posto, $dadosPost, $dadosFrete) {
        if ($tipo_pagamento == "CREDIT_CARD") {
            return $this->pagamentoCartaoCreditoMaxiPago($dadosPedido, $posto, $dadosPost, $dadosFrete);
        }
        if ($tipo_pagamento == "BOLETO") {
            return $this->pagamentoBoletoMaxiPago($dadosPedido, $posto, $dadosPost, $dadosFrete);
        }
    }

    public function pagamentoBoletoMaxiPago($dadosPedido, $posto, $dadosPost, $dadosFrete) {
        require_once(dirname(__FILE__) . "/../../loja/integracoes/maxipago/lib/maxipago/Autoload.php"); // Remove if using a globa autoloader
        require_once(dirname(__FILE__) . "/../../loja/integracoes/maxipago/lib/maxiPago.php");
        $maxiPago = new \maxiPago;
        

        $maxiPago->setCredentials("6735", "pcrk6rnwo42zuwypvdmnzafg");
        $maxiPago->setEnvironment("TEST");

        $dadosPosto = $this->classPosto->get($posto);
        if (empty($dadosPosto)) {
            return array("erro" => true, "msg" => utf8_encode("Posto n�o encontrado"));
        }

        $dadosFabrica = $this->classLoja->getFabrica($this->_fabrica);
        if (empty($dadosFabrica)) {
            return array("erro" => true, "msg" => utf8_encode("Fabrica n�o encontrado"));
        }

        $enderecoFabrica = $dadosFabrica["endereco"]." - CEP: ".$dadosFabrica["cep"]." - ".  $dadosFabrica["cidade"];
        $nomeFabrica     = (!empty($dadosFabrica["razao_social"])) ? $dadosFabrica["razao_social"] : $dadosFabrica["nome"];

        $data = array(
            "processorID"           => "12", // REQUIRED - Use 12 for testing. For production values contact our team //
            "referenceNum"          => $dadosPedido["pedido"], // REQUIRED - Merchant's internal order number //
            // "ipAddress"          => "123.123.123.123", // Optional //
            "customerIdExt"         => $dadosPosto->cnpj, //CPF,
            "billingName"           => $dadosPosto->nome, // REQUIRED - Customer name //
            "billingAddress"        => $dadosPosto->contato_endereco . ", N� " . $dadosPosto->contato_numero, // Optional - Customer address //
            "billingAddress2"       => $dadosPosto->contato_complemento . " - " . $dadosPosto->contato_bairro, // Optional - Customer address //
            "billingCity"           => $dadosPosto->contato_cidade, // Optional - Customer city //
            "billingState"          => $dadosPosto->contato_estado, // Optional - Customer state with 2 characters //
            "billingPostalCode"     => $dadosPosto->contato_cep,  // Optional - Customer zip code //
            "billingCountry"        => "BR", // Optional - Customer country under ISO 3166-2 //
            //"billingPhone"        => "2140099400", // Optional - Customer phone number //
            //"billingEmail"        => "fulanodetal@email.com", // Optional - Customer email address //
            "billingCompanyName"    => $nomeFabrica,
            "expirationDate"        => date('Y-m-d', strtotime('+'.$this->classLoja->configuracao_pagamento["meio"]["maxipago"]["dias_vencimento"].' days')), // REQUIRED - Boleto expiration date, YYYY-MM-DD format //
            "number"                => $dadosPedido["pedido"], // REQUIRED AND UNIQUE - Boleto ID number, max of 8 numbers //
            "chargeTotal"           => $dadosPost["carrinhosubtotal"], // REQUIRED - US format: 10.00 or 1234.56 //
            "instructions"          => $this->classLoja->configuracao_pagamento["meio"]["maxipago"]["instrucao_boleto"], // Optional - Instructions to be printed with the boleto. Use ";" to break lines //          
        
        );
        foreach ($dadosPost["produtos"] as $key => $rows) {
            $totalPedido[] = ($rows["qtde"]*$rows["valor"]);
        }
        $totalPedido = array_sum($totalPedido);

        $insereDadosPagamento = $this->trataInsereDadosBoletoPagamentoMaxiPago($data);
        $id_pagamento = $this->classCarrinhoCompra->inserePagamentoB2B($insereDadosPagamento, "BOLETO", $totalPedido, $dadosPedido["pedido"]);

        try {
            $maxiPago->boletoSale($data);

            if ($maxiPago->isErrorResponse()) {

                return array("erro" => true, "msg" => $maxiPago->getMessage());

            } elseif ($maxiPago->isTransactionResponse()) {

                if ($maxiPago->getResponseCode() == "0") { 
                    $dataRetorno = array(
                            "transactionID" => $maxiPago->getTransactionID(),
                        );
                    $maxiPago->pullReport($dataRetorno);
                    $resultado  = current($maxiPago->getReportResult());

                    $atualizaPagamento = $this->trataUpdateDadosBoletoPagamentoMaxiPago($resultado);
                    $this->classCarrinhoCompra->atualizaPagamentoB2B($id_pagamento, $atualizaPagamento, "BOLETO", $dadosPedido["pedido"], $resultado["transactionState"]);

                    return array(
                            "erro"                     => false,
                            "tipo_pagamento_escolhido" => "BOLETO", 
                            "msg"                      => utf8_encode("Pedido criado com sucesso"), 
                            "pedido"                   => $dadosPedido["pedido"], 
                            "link_boleto"              => $resultado["boletoUrl"], 
                            "vencimento_boleto"        => $resultado["expirationDate"], 
                            "linha_digita_boleto"      => $resultado["returnCode"], 
                            "status_boleto"            => $resultado["transactionState"],
                            "codigoEnvio"            	=> $dadosFrete["codigoEnvio"],
                            "servicoEnvio"            	=> $dadosFrete["servicoEnvio"],
                            "diasEnvio"            		=> $dadosFrete["diasEnvio"],
                            "valorEnvio"            	=> $dadosFrete["valorEnvio"],
                        );




                } else { 
                    return array("erro" => true, "msg" => $maxiPago->getMessage());
                }

            }
        } catch (Exception $e) { 
            return array("erro" => true, "msg" => $e->getMessage()." em ".$e->getFile()." na linha ".$e->getLine());
        }

    }


    public function trataInsereDadosBoletoPagamentoMaxiPago($dadosPedido) {
       
        $insereDadosPagamento["expirationDate"]      = $dadosPedido["expirationDate"];
        $insereDadosPagamento["number"]              = $dadosPedido["number"];
        $insereDadosPagamento["chargeTotal"]         = utf8_encode($dadosPedido["chargeTotal"]);
        $insereDadosPagamento["customerIdExt"]       = utf8_encode($dadosPedido["customerIdExt"]);
        $insereDadosPagamento["instructions"]        = utf8_encode($dadosPedido["instructions"]);
        $insereDadosPagamento["billingName"]         = utf8_encode($dadosPedido["billingName"]);
        $insereDadosPagamento["billingAddress"]      = utf8_encode($dadosPedido["billingAddress"]);
        $insereDadosPagamento["billingAddress2"]     = utf8_encode($dadosPedido["billingAddress2"]);
        $insereDadosPagamento["billingCity"]         = utf8_encode($dadosPedido["billingCity"]);
        $insereDadosPagamento["billingState"]        = utf8_encode($dadosPedido["billingState"]);
        $insereDadosPagamento["billingPostalCode"]   = utf8_encode($dadosPedido["billingPostalCode"]);
        $insereDadosPagamento["billingCountry"]      = utf8_encode($dadosPedido["billingCountry"]);
        $insereDadosPagamento["billingCompanyName"]  = utf8_encode($dadosPedido["billingCompanyName"]);
        $insereDadosPagamento["reference"]           = $dadosPedido["referenceNum"];
        $insereDadosPagamento["processorID"]         = $dadosPedido["processorID"];
        $insereDadosPagamento["referenceNum"]        = $dadosPedido["referenceNum"];

        return $insereDadosPagamento;

    }

    public function trataUpdateDadosBoletoPagamentoMaxiPago($dadosRetorno) {

        $dadosRetorno["date"] = $dadosRetorno["expirationDate"];
        $dadosRetorno["reference"] = $dadosRetorno["referenceNumber"];
        $dadosRetorno["status"] = $dadosRetorno["transactionState"];
        
        return $dadosRetorno;
    }

    /* REFERENTE A INTEGRA��O COM CIELO ECOMMERCE */
    public function processaCielo($dadosPedido, $tipo_pagamento, $posto, $dadosPost, $dadosFrete) {
        if ($tipo_pagamento == "CREDIT_CARD") {
            return $this->pagamentoCartaoCreditoCielo($dadosPedido, $posto, $dadosPost, $dadosFrete);
        }
        if ($tipo_pagamento == "BOLETO") {
            return $this->pagamentoBoletoCielo($dadosPedido, $posto, $dadosPost, $dadosFrete);
        }
    }

    public function pagamentoCartaoCreditoCielo($dadosPedido, $posto, $dadosPost, $dadosFrete) {
           
        $environment = $environment = Environment::sandbox();
        $merchant    = new Merchant($this->MERCHANT_ID, $this->MERCHANT_KEY);

        $dadosPosto = $this->classPosto->get($posto);
        if (empty($dadosPosto)) {
            return array("erro" => true, "msg" => utf8_encode("Posto n�o encontrado"));
        }

        $dadosFabrica = $this->classLoja->getFabrica($this->_fabrica);
        if (empty($dadosFabrica)) {
            return array("erro" => true, "msg" => utf8_encode("Fabrica n�o encontrado"));
        }

        $qtde_parcelas                  = explode("|", $dadosPost["qtde_parcelas"]);
        $qtdeparcelas                   = $qtde_parcelas[0];
        $valorparcelas                  = $qtde_parcelas[1];
        $valor_parcelas                 = $valorparcelas ;

        $enderecoFabrica = $dadosFabrica["endereco"]." - CEP: ".$dadosFabrica["cep"]." - ".  $dadosFabrica["cidade"];
        $nomeFabrica     = (!empty($dadosFabrica["razao_social"])) ? $dadosFabrica["razao_social"] : $dadosFabrica["nome"];
        $sale            = new Sale($dadosPedido["pedido"]);
        $customer        = $sale->customer($dadosPosto->nome);
        $totalPedido     = $dadosPost["carrinhosubtotal"];
        $payment         = $sale->payment(str_replace(array(".",","), "", $totalPedido), $qtdeparcelas);
        $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)
                ->creditCard($dadosPost["cvv"], $this->bandeiras_cielo[$dadosPost["bandeira"]])
                ->setExpirationDate($dadosPost["validadeMes"]."/".$dadosPost["validadeAno"])
                ->setCardNumber($dadosPost["cartao"])
                ->setHolder($dadosPost["nome_titular_cartao"]);
        try {

            $sale = (new CieloEcommerce($merchant, $environment))->createSale($sale);

            $insereDadosPagamento = $this->trataInsereDadosCartaoPagamentoCielo($sale);
            $id_pagamento = $this->classCarrinhoCompra->inserePagamentoB2B($insereDadosPagamento, "CREDIT_CARD", $totalPedido, $dadosPedido["pedido"]);

            $paymentId = $sale->getPayment()->getPaymentId();
            $codigo_autorizacao = $sale->getPayment()->getReturnCode();

            $this->classCarrinhoCompra->atualizaPagamentoB2B($id_pagamento, $sale, "CREDIT_CARD", $dadosPedido["pedido"], $codigo_autorizacao);

            return array(
                        "erro"                      => false,
                        "tipo_pagamento_escolhido"  => "CREDIT_CARD", 
                        "msg"                       => utf8_encode("Pedido criado com sucesso"), 
                        "pedido"                    => $dadosPedido["pedido"], 
                        "code_auto"                 => $paymentId, 
                        "bandeira"                  => $dadosPost["bandeira"],
                        "status_cartao"             => $codigo_autorizacao,
                        "codigoEnvio"               => $dadosFrete["codigoEnvio"],
                        "servicoEnvio"              => $dadosFrete["servicoEnvio"],
                        "diasEnvio"                 => $dadosFrete["diasEnvio"],
                        "valorEnvio"                => $dadosFrete["valorEnvio"],
                    );

        } catch (CieloRequestException $e) {
            $error = $e->getCieloError();
            return array(
                        "erro"                      => true,
                        "msg"                       => utf8_encode($this->erros_cielo[$error->getCode()]), 
                    );
        }
    }

    public function pagamentoBoletoCielo($dadosPedido, $posto, $dadosPost, $dadosFrete) {

        $environment = Environment::sandbox();
        $merchant = new Merchant($this->MERCHANT_ID, $this->MERCHANT_KEY);

        $dadosPosto = $this->classPosto->get($posto);
        if (empty($dadosPosto)) {
            return array("erro" => true, "msg" => utf8_encode("Posto n�o encontrado"));
        }

        $dadosFabrica = $this->classLoja->getFabrica($this->_fabrica);
        if (empty($dadosFabrica)) {
            return array("erro" => true, "msg" => utf8_encode("Fabrica n�o encontrado"));
        }

        $enderecoFabrica = $dadosFabrica["endereco"]." - CEP: ".$dadosFabrica["cep"]." - ".  $dadosFabrica["cidade"];
        $nomeFabrica     = (!empty($dadosFabrica["razao_social"])) ? $dadosFabrica["razao_social"] : $dadosFabrica["nome"];

        $sale = new Sale($dadosPedido["pedido"]);

        $customer = $sale->customer($dadosPosto->nome)
                          ->setIdentity(str_replace(array(".", ",","-","/"), "", $dadosPosto->cnpj))
                          ->setIdentityType('CNPJ')
                          ->address()->setZipCode(str_replace(array(".", ",","-","/"), "", $dadosPosto->contato_cep))
                                     ->setCountry('BRA')
                                     ->setState($dadosPosto->contato_estado )
                                     ->setCity($dadosPosto->contato_cidade)
                                     ->setDistrict($dadosPosto->contato_bairro)
                                     ->setStreet($dadosPosto->contato_endereco)
                                     ->setNumber($dadosPosto->contato_numero);

        $payment = $sale->payment(str_replace(array(".",","), "", $dadosPost["carrinhosubtotal"]))
                        ->setType(Payment::PAYMENTTYPE_BOLETO)
                        ->setAddress($enderecoFabrica)
                        ->setProvider('SIMULADO')
                        ->setBoletoNumber($dadosPedido["pedido"])
                        ->setAssignor($nomeFabrica)
                        ->setDemonstrative('Pedido N '.$dadosPedido["pedido"])
                        ->setExpirationDate(date('d/m/Y', strtotime('+'.$this->classLoja->configuracao_pagamento["meio"]["cielo"]["dias_vencimento"].' days')))
                        ->setIdentification($dadosPedido["pedido"])
                        ->setInstructions($this->classLoja->configuracao_pagamento["meio"]["cielo"]["instrucao_boleto"]);
        

        try {
            $sale = (new CieloEcommerce($merchant, $environment))->createSale($sale);
            $boletoURL = $sale->getPayment()->getUrl();

            if (strlen($boletoURL) > 0) {

                return array(
                            "erro"                     => false,
                            "tipo_pagamento_escolhido" => "BOLETO", 
                            "msg"                      => utf8_encode("Pedido criado com sucesso"), 
                            "pedido"                   => $dadosPedido["pedido"], 
                            "link_boleto"              => $sale->getPayment()->getUrl(), 
                            "vencimento_boleto"        => $sale->getPayment()->getExpirationDate(), 
                            "linha_digita_boleto"      => $sale->getPayment()->getDigitableLine(), 
                            "status_boleto"            => $sale->getPayment()->getStatus()
                        );
            }

        } catch (CieloRequestException $e) {

           $error = $e->getCieloError();
            return array(
                        "erro"                      => true,
                        "msg"                       => utf8_encode($this->erros_cielo[$error->getCode()]), 
                    );

        }
    }

    public function trataInsereDadosCartaoPagamentoCielo($dadosretornocartao) {
        $dadosretornocartao->reference = $dadosretornocartao->getMerchantOrderId();
        return $dadosretornocartao;
    }

}