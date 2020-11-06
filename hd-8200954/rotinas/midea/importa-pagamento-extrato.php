<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_169/Extrato.php';

try {
    // ini_set("display_errors", 1);
    // error_reporting(E_ALL);

    /*
    * Definições
    */
    $fabrica        = 169;
    $dia_mes        = date('d');
    $dia_extrato    = date('Y-m-d H:i:s');
    $dataFinal      = date('Ymd', strtotime(date('Ymd').' + 5 days'));
    $dataInicio     = date('Ymd', strtotime($dataFinal.' - 30 days'));
    //$dataFinal = '20190703';
    //$dataInicio = '20190501';
    #$dia_mes     = "27";
    #$dia_extrato = "2014-08-27 23:59:00";

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato Midea/Carrier")); // Titulo
    
    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("maicon.luiz@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail('helpdesk@telecontrol.com.br');
    }

    /*
    * Extrato Class
    */
    $classExtrato = new Extrato($fabrica);
    $classExtratoFabrica = new ExtratoMideaCarrier($fabrica);

    /*
    * Resgata o período dos 15 dias
    */
    // $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);
    $data_15 = date("Y-m-d");

    /*
    * Resgata a quantidade de OS por Posto
    */
    $extratos_pagamento = $classExtratoFabrica->getExtratoPagto();

    if(empty($extratos_pagamento)){
        exit;
    }

    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?wsdl";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }

    $client = new SoapClient($urlWSDL, array('trace' => 1));

    /**
    * Libera extrato automaticamente assim que é gerado
    */
    $libera_extrato_automaticamente = true;

    /*
    * Mensagem de Erro
    */
    $msg_erro = "";
    $msg_erro_arq = "";
    $pdo = $classExtrato->_model->getPDO();

    for ($i = 0; $i < count($extratos_pagamento); $i++) {
        $posto          = $extratos_pagamento[$i]["posto"];
        $codigo_posto   = strtoupper($extratos_pagamento[$i]["codigo_posto"]);
        $centro_custo   = $extratos_pagamento[$i]["centro_custo"];
        $conta_contabil = $extratos_pagamento[$i]["conta_contabil"];

        if ($classExtratoFabrica->verificaMatriz($codigo_posto, $conta_contabil) === true) {
            continue;
        }

        $xmlRequest = "
			<ns1:oXml>
                <Z_CB_TC_SEL_PAG xmlns='http://ws.carrieronline.com.br/PSA_WebService'>
    				<P_ABER>X</P_ABER>
      				<P_BLOQ>X</P_BLOQ>
      				<P_EFET>X</P_EFET>
      				<P_BUKRS>B001</P_BUKRS>
      				<P_DTINI>{$dataInicio}</P_DTINI>
      				<P_DTFIM>{$dataFinal}</P_DTFIM>
      				<P_ORD>0</P_ORD>
                    <P_LIFNR>{$conta_contabil}</P_LIFNR>
                </Z_CB_TC_SEL_PAG>
            </ns1:oXml>
        ";
        $params        = new SoapVar($xmlRequest, XSD_ANYXML);
        $array_params  = array('oXml' => $params);

        $result        = $client->Z_CB_TC_SEL_PAG($array_params);
        $dados_xml     = $result->Z_CB_TC_SEL_PAGResult->any;
        $xml           = simplexml_load_string($dados_xml);
        $xml           = json_decode(json_encode((array)$xml), TRUE);
        $arrayExtratos = array();

        if (empty($xml['NewDataSet']['ZCBFI_WEB_PAGTable']) && empty($xml['NewDataSet'][0]['ZCBFI_WEB_PAGTable'])) {
           continue;
        } else {

            if (!empty($xml['NewDataSet']['ZCBFI_WEB_PAGTable']['LIFNR'])) {
                $arrayExtratos[] = $xml['NewDataSet']['ZCBFI_WEB_PAGTable'];
            } else {
                $arrayExtratos = $xml['NewDataSet']['ZCBFI_WEB_PAGTable'];
            }

            foreach($arrayExtratos as $dados) {

                $pedidoCompraSap = $dados['EBELN'];
                $valorPagto = $dados['DMBTR'];
                $dataPagto = $dados['AUGDT'];
                $statusSAP = $dados['TPDOC'];
                $deposito = (int) $dados['LIFNR'];

                $sqlExt = "SELECT extrato FROM tbl_extrato_pagamento JOIN tbl_extrato USING(extrato) WHERE fabrica = {$fabrica} AND autorizacao_pagto = '{$pedidoCompraSap}';";
                $query = $pdo->query($sqlExt);

                if (!$query) {
                    throw new \Exception("Erro ao verificar existência do extrato");
                }

                $resExt = $query->fetch(\PDO::FETCH_ASSOC);

                if (empty($resExt['extrato'])) {
                    continue;
                }

                $extrato = $resExt['extrato'];

                $sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE conta_contabil = '{$deposito}' AND posto = {$posto};";
                $query  = $pdo->query($sql);

                if(!$query){
                    throw new \Exception("Erro ao buscar informações do posto: {$codigo_posto}");
                }

                $res = $query->fetchAll(\PDO::FETCH_ASSOC);
                
                if (count($res) == 0) {
                    throw new \Exception("Posto {$codigo_posto} não encontrado");
                }

                try {
                    /*
                    * Begin
                    */
                    $pdo->beginTransaction();

                    /*
                    * Atualiza data de pagamento do extrato
                    */
                    $classExtratoFabrica->atualizarPagto($extrato, $dataPagto, $statusSAP); 

                    /*
                    * Atualiza Status OS
                    */
                    $classExtratoFabrica->atualizarStatusOS($extrato);

                    $pdo->commit();
                } catch (Exception $e){
                    $pdo->rollBack();

                    $msg_erro .= $e->getMessage()."<br />";
                    $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();
                }
            }
        }
    }

    /*
    * Erro
    */
    if(!empty($msg_erro)){
        $logClass->adicionaLog($msg_erro);

        if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          echo $logClass->enviaEmails();
        }

        $fp = fopen("/tmp/{$fabrica_nome}/extrato/gera-extrato-".date("dmYH").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, $msg_erro_arq . "\n \n");
        fclose($fp);

        throw new Exception($msg_erro);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

/**
 * Cron Término
 */
$phpCron->termino();

