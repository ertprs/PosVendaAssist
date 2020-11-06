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

    if (!empty($argv[1])) {
        $postoId = $argv[1];
    }

    /*
    * Extrato Class
    */
    $classExtrato = new Extrato($fabrica);
    $classExtratoFabrica = new ExtratoMideaCarrier($fabrica, $postoId);

    /*
    * Resgata o período dos 15 dias
    */
    // $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);
    $data_15 = date("Y-m-d");

    /*
    * Resgata a quantidade de OS por Posto
    */
    $os_posto = $classExtratoFabrica->getOsPosto();

    if(empty($os_posto)){
        exit;
    }

    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
    } else {
	    $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
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
        $codigo_posto   = strtoupper($os_posto[$i]["codigo_posto"]);
        $centro_custo   = $os_posto[$i]["centro_custo"];
        $conta_contabil = $os_posto[$i]["conta_contabil"];

        if ($classExtratoFabrica->verificaMatriz($codigo_posto, $conta_contabil) === true) {
            continue;
        }

        $conta_contabil = str_pad($conta_contabil, 10, "0", STR_PAD_LEFT);

	    $xmlRequest = "
            <ns1:xmldoc>
                <criterios>
                    <PV_LIFNR>{$conta_contabil}</PV_LIFNR>
                    <PV_EKORG>B010</PV_EKORG>
                    <PV_BSART>ZGAR</PV_BSART>
                    <PV_WERKS>{$centro_custo}</PV_WERKS>
                    <PV_DIAS>30</PV_DIAS>
                </criterios>
            </ns1:xmldoc>
        ";

        $params = new SoapVar($xmlRequest, XSD_ANYXML);

        $array_params = array('xmldoc' => $params);
        $result = $client->BuscaNotaFiscalServicos($array_params);
        $dados_xml = $result->BuscaNotaFiscalServicosResult;
        $xml = simplexml_load_string($dados_xml);
        $xml = json_decode(json_encode((array)$xml), TRUE);

	$arrayExtratos = array();

        if ($xml['ZCBSM_MENSAGEMTABLE']['MSGTY'] == "E") {
	    continue;
	} else if (!empty($xml['ZCBSM_DADOS_PEDIDOTABLE']['EBELN'])) {
	    $arrayExtratos = array(0 => $xml['ZCBSM_DADOS_PEDIDOTABLE']);
	} else {
	    $arrayExtratos = $xml['ZCBSM_DADOS_PEDIDOTABLE'];
	}

	foreach($arrayExtratos as $dados) {
            $pedidoCompraSap = $dados['EBELN'];
            $valorTotalSap = $dados['NETWR'];
            $dia_extrato = $dados['BEDAT'];
            $deposito = (int) $dados['LIFNR'];

	    $sqlExt = "SELECT extrato FROM tbl_extrato_pagamento JOIN tbl_extrato USING(extrato) WHERE fabrica = {$fabrica} AND autorizacao_pagto = '{$pedidoCompraSap}';";
	    $query = $pdo->query($sqlExt);

	    if (!$query) {
		throw new \Exception("Erro ao verificar existência do extrato");
	    }

	    $resExt = $query->fetch(\PDO::FETCH_ASSOC);

	    if ($resExt != null) {
		continue;
	    }

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

                if (isset($xml['ZCBSM_DADOS_ITEM_PEDIDOTABLE']['QMNUM'])) {
                    $array_itens = array( 0 => $xml['ZCBSM_DADOS_ITEM_PEDIDOTABLE'] );
                    $xml['ZCBSM_DADOS_ITEM_PEDIDOTABLE'] = $array_itens;
                }

                foreach ($xml['ZCBSM_DADOS_ITEM_PEDIDOTABLE'] as $itens) {
                    $os = (int) $itens['QMNUM'];
                    $valor_os_sap = number_format($itens['NETWR'], 2, '.', '');

		    if ($itens['EBELN'] != $pedidoCompraSap) {
			continue;
		    }

                    $sqlOS = "SELECT os, posto FROM tbl_os WHERE fabrica = {$fabrica} AND os = {$os};";
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
                        if ($res[0]["posto"] != $posto) {
                            /*
                            * Gravar valor avulso com ordens das FILIAIS
                            */
                            $insertLanc = "
                                INSERT INTO tbl_extrato_lancamento (posto,fabrica,extrato,descricao,lancamento,debito_credito,valor)
                                VALUES ({$posto},{$fabrica},{$extrato},'OS FILIAL {$os}',(SELECT lancamento FROM tbl_lancamento WHERE fabrica = {$fabrica} AND descricao = 'OSs FILIAIS'),'C',{$valor_os_sap});
                            ";
                            $query = $pdo->query($insertLanc);

                            if(!$query){
                                throw new \Exception("Erro ao inserir OS: {$os}");
                            }

			    /*
                             * Atualiza status da OS
                             */
                            $resStatus = pg_query($con, "SELECT fn_os_status_checkpoint_os({$os});");

                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Ocorreu um erro atualizando dados da OS #001");
                            } else {
                                $status_checkpoint = pg_fetch_result($resStatus, 0, 0);
                                pg_query($con, "UPDATE tbl_os SET status_checkpoint = {$status_checkpoint} WHERE os = {$os};");
                                if (strlen(pg_last_error()) > 0) {
                                    throw new Exception("Ocorreu um erro atualizando dados da OS #002");
                                }
                            }

                        } else {
                            /*
                            * Relaciona as OSs com o Extrato
                            */
                            $classExtratoFabrica->relacionaExtratoOS($fabrica, $posto, $extrato, $os, $valor_os_sap);

			    /*
                             * Atualiza status da OS
                             */
                            $resStatus = pg_query($con, "SELECT fn_os_status_checkpoint_os({$os});");

                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Ocorreu um erro atualizando dados da OS #001");
                            } else {
                                $status_checkpoint = pg_fetch_result($resStatus, 0, 0);
                                pg_query($con, "UPDATE tbl_os SET status_checkpoint = {$status_checkpoint} WHERE os = {$os};");
                                if (strlen(pg_last_error()) > 0) {
                                    throw new Exception("Ocorreu um erro atualizando dados da OS #002");
                                }
                            }

                        }
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

		        $pdo->commit();
            } catch (Exception $e){
		        $pdo->rollBack();

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
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

/**
 * Cron Término
 */
$phpCron->termino();
