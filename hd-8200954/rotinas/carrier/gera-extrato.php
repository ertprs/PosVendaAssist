<?php
    
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_170/Extrato.php';

try {

    // ini_set("display_errors", 1);
    // error_reporting(E_ALL);

    /*
    * Definições
    */
    $fabrica        = 170;
    $dia_mes        = date('d');
    $dia_extrato    = date('Y-m-d H:i:s');

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
    $os_posto = $classExtratoFabrica->getOsPosto($fabrica);

    if(empty($os_posto)){
        exit;
    }

    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/Portal_PSA_WebService/PSA.asmx?WSDL";
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

    for ($i = 0; $i < count($os_posto); $i++) {

        $posto          = $os_posto[$i]["posto"];
        $codigo_posto   = $os_posto[$i]["codigo_posto"];
        $centro_custo   = $os_posto[$i]["centro_custo"];
        $conta_contabil = $os_posto[$i]["conta_contabil"];
        $conta_contabil = str_pad($conta_contabil, 10, "0", STR_PAD_LEFT);

        $params = new SoapVar("
            <ns1:xmldoc>
                <criterios>
                    <PV_LIFNR>{$conta_contabil}</PV_LIFNR>
                    <PV_EKORG>B010</PV_EKORG>
                    <PV_BSART>ZGAR</PV_BSART>
                    <PV_WERKS>{$centro_custo}</PV_WERKS>
                    <PV_DIAS>30</PV_DIAS>
                </criterios>
            </ns1:xmldoc>
        ", XSD_ANYXML);

        $array_params = array('xmldoc' => $params);
        $result = $client->BuscaNotaFiscalServicos($array_params);
        $dados_xml = $result->BuscaNotaFiscalServicosResult;
        $xml = simplexml_load_string($dados_xml);
        $xml = json_decode(json_encode((array)$xml), TRUE);

        if (!empty($xml['ZCBSM_DADOS_PEDIDOTABLE']['EBELN'])) {

            $pedidoCompraSap = $xml['ZCBSM_DADOS_PEDIDOTABLE']['EBELN'];
            $valorTotalSap = $xml['ZCBSM_DADOS_PEDIDOTABLE']['NETWR'];
            $dia_extrato = $xml['ZCBSM_DADOS_PEDIDOTABLE']['BEDAT'];
            $deposito = (int) $xml['ZCBSM_DADOS_PEDIDOTABLE']['LIFNR'];

            $sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE conta_contabil = '{$deposito}' AND posto = {$posto};";
            $query  = $pdo->query($sql);

            if(!$query){
                throw new \Exception("Erro ao buscar informações do posto: {$posto}");
            }

            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
            
            if (count($res) == 0) {
                throw new \Exception("Posto {$posto} não encontrado");
            }

            try {
                /*
                * Begin
                */
                $pdo->beginTransaction();

                /*
                * Insere o Extrato para o Posto
                */
                $classExtrato->insereExtratoPosto($fabrica, $posto, $dia_extrato, $mao_de_obra = 0, $pecas = 0, $total = 0, $avulso = 0);

                /*
                * Resgata o numero do Extrato
                */
                $extrato = $classExtrato->getExtrato();

                /**
                 * Insere o número do pedido SAP
                 */
                $classExtratoFabrica->insereExtratoPagto($fabrica, $extrato, $pedidoCompraSap);

                foreach ($xml['ZCBSM_DADOS_ITEM_PEDIDOTABLE'] as $itens) {

                    $os = (int) $itens['QMNUM'];
                    $valor_os_sap = number_format($itens['NETWR'], 2, '.', '');

                    try {

                        $sqlOS = "SELECT os FROM tbl_os WHERE fabrica = {$fabrica} AND os = {$os};";
                        $queryOS = $pdo->query($sqlOS);

                        if(!$queryOS){
                            throw new \Exception("Erro ao buscar OS: {$os}");
                        }

                        $res = $queryOS->fetchAll(\PDO::FETCH_ASSOC);

                        if (count($res) == 0) {

                            /*
                             * Grava valor avulso com ordens do PSA
                             */

                            $insertLanc = "
                                INSERT INTO tbl_extrato_lancamento (posto,fabrica,extrato,descricao,lancamento,debito_credito,valor)
                                VALUES ({$posto},{$fabrica},{$extrato},'OS PSA {$os}',(SELECT lancamento FROM tbl_lancamento WHERE fabrica = {$fabrica} AND descricao = 'OSs ANTIGAS'),'C',{$valor_os_sap});
                            ";

                            $query = $pdo->query($insertLanc);

                            if(!$query){
                                throw new \Exception("Erro ao inserir OS: {$os}");
                            }

                        } else {

                            /*
                            * Relaciona as OSs com o Extrato
                            */
                            $classExtratoFabrica->relacionaExtratoOS($fabrica, $posto, $extrato, $os, $valor_os_sap);

                        }

                    } catch (Exception $e){

                        $msg_erro .= $e->getMessage()."<br />";
                        $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

                    }

                }

                /*
                * Atualizar o valor do Extrato
                */
                $total_extrato = $classExtratoFabrica->setValorTotal($extrato, $valorTotalSap);

                /**
                * Libera extrato automaticamente
                */
                if($libera_extrato_automaticamente == true){
                    $classExtrato->liberaExtrato($extrato);
                }

            } catch (Exception $e){

                $msg_erro .= $e->getMessage()."<br />";
                $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

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

    } else {

        /**
         * Commit
         */
        $pdo->commit();

    }

} catch (Exception $e) {
    
    echo $e->getMessage();

    /**
     * Rollback
     */
    $pdo->rollBack();
}

/**
 * Cron Término
 */
$phpCron->termino();