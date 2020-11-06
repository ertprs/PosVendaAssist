<?php

require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/./funcoes.php';

include_once dirname(__FILE__) . "/../../class/aws/s3_config.php";
include_once S3CLASS;
include "TDocsTinyUploader.php";

date_default_timezone_set("America/Sao_Paulo");

global $login_fabrica;
$login_fabrica = 158;

$debug = true;

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;

$routines = new \Posvenda\Routine();
$routine = $routines->SelectRoutine('Dado Mestre - Numero de Serie');

$routineSchedule = new RoutineSchedule();
$routineSchedule->setRoutine($routine[0]['routine']);
$routineSchedule->setWeekDay(date("w"));

$routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

$log = new \Posvenda\Log();
$log->setRoutineSchedule($routine_schedule_id);
$log->setDateStart(date("Y-m-d H:i:s"));
$finalMessage = "Finalizada com sucesso";

$finalStatus = 1;

$login_fabrica = 158;

#$ret = file_put_contents("/tmp/integracoes-imbera.log", "\n\n".date("d-m-Y H:i:s")." --------------------------- INICIANDO INTEGRAÇÃO DE NÚMERO DE SÉRIE ---------------------------\n\n", FILE_APPEND);

try {

    //SELECT DE PRODUTOS
    $sqlProdutos = "SELECT DISTINCT produto, referencia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} limit 1;";
    $resProdutos = pg_query($con, $sqlProdutos);
    $produtos = pg_fetch_all($resProdutos);

 #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." ".count($produtos)." produtos para processar \n", FILE_APPEND);

    $link = urlSap(true);

    foreach ($produtos as $produto) {

        if ($_serverEnvironment == 'development') {

            $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_NumSerie_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

            $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

        } else {

            $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_NumSerie_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

            $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

        }

        $xml_post_string = '
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
                <soapenv:Header/>
                <soapenv:Body>
                    <tel:MT_NumSerie_Req>
                        <I_MATNR>'.$produto['referencia'].'</I_MATNR>
                    </tel:MT_NumSerie_Req>
                </soapenv:Body>
            </soapenv:Envelope>
        ';

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: ".strlen($xml_post_string),
            $authorization
        );

        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " \n " . $xml_post_string . "  \n", FILE_APPEND);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2000);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $series = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $xmlFileName = "xmlSeriesImbera-".date("Y-m-d").".xml";
        file_put_contents("/tmp/".$xmlFileName, $series, FILE_APPEND);

        try {
            $tdocsUploader = new TDocsTinyUploader();
            $docs = $tdocsUploader->sendFile("/tmp/".$xmlFileName);
            $doc = array_pop($docs[0]);

            $log->setTDocs($doc['unique_id']);
			system("rm /tmp/$xmlFileName");
        } catch (\Exception $ef) {
            #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." ERRO UPLOAD ".$ef->getMessage()."\n", FILE_APPEND);
			system("rm /tmp/$xmlFileName");
        }

        $log->Insert();
        $log->setRoutineScheduleLog($log->SelectId());

        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." Retorno Imbera\n", FILE_APPEND);
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")."\n".$series."\n", FILE_APPEND);

        $series = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3", $series);
        $retornoXML = new \SimpleXMLElement(utf8_encode($series));
        $retornoXML = $retornoXML->xpath('//E_NUM_SERIE');
        $retornoXML = json_decode(json_encode((array) $retornoXML));
        $retornoXML = $retornoXML[0];

        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")."\n".print_r($retornoXML, 1)."\n", FILE_APPEND);

        if (count($retornoXML->NUM_SERIE) == 0) {
         #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." Nenhum Número de Série retornado para o produto ".$produto['referencia'].".\n", FILE_APPEND);
            continue;
        }

        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." Iniciando Integração com Posvenda \n", FILE_APPEND);
        
        foreach ($retornoXML->NUM_SERIE as $numSerie) {

            $numero_serie = (int) $numSerie->SERNR;
            $cnpj = $numSerie->STCD1;

            $produtoId = $produto['produto'];
            $referencia_produto = $produto['referencia'];
            
            $sql = "SELECT numero_serie FROM tbl_numero_serie WHERE (serie = '{$numero_serie}' OR serie = '{$numSerie->SERNR}') AND fabrica = {$login_fabrica};";
	                
	 #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." Verificando existência da série \n", FILE_APPEND);
          #  file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." {$sql}\n", FILE_APPEND);
	    
	    $res = pg_query($con,$sql);
            
            if (pg_num_rows($res) == 0) {

                $data_venda = Datetime::createFromFormat('Ymd', $numSerie->DT_VENDA);
                $data_carga = new Datetime('now');
                $data_fabricacao = Datetime::createFromFormat('Ymd', $numSerie->DT_FABRICACAO);

                $insert = "
                    INSERT INTO tbl_numero_serie (fabrica,serie,referencia_produto,data_venda,data_carga,data_fabricacao,produto,cnpj)
                    VALUES ({$login_fabrica},'{$numero_serie}','{$referencia_produto}','{$data_venda->format('Y-m-d')}','{$data_carga->format('Y-m-d H:i:s')}','{$data_fabricacao->format('Y-m-d')}',{$produtoId},'{$cnpj}');
                ";
                
	#	file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." Inserindo novo número de série\n", FILE_APPEND);
	#	file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." {$insert}\n", FILE_APPEND);
                $resQuery = pg_query($con,$insert);

	    } else {
        #	file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." Número de Série já cadastrado\n", FILE_APPEND);
	    }

            if (pg_last_error($con)) {
                $finalStatus = 2;
                $logError = new \Posvenda\LogError();
                $logError->setRoutineScheduleLog($log->SelectId());
                $logError->setErrorMessage("ERRO AO EXECUTAR A QUERY");
                $logError->setContents("ERRO AO EXECUTAR A QUERY $insert ------------ " . pg_last_error($con));
                $logError->Insert();
         #       file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " !!!!!!!!!!!!!!!!!!!!! ERRO AO EXECUTAR A QUERY " . pg_last_error($con) . " !!!!!!!!!!!!!!!!!!\n", FILE_APPEND);

            }
        }

        if ($finalStatus == 2) {
            $finalMessage = "Finalizada com algumas excessões";
        }

    }

    $log->setStatus($finalStatus);
    $log->setStatusMessage($finalMessage);
    $log->Update();

} catch (\Exception $e) {
    $log->setStatus(0);
    $log->setStatusMessage($e->getMessage());
    $log->Update();
}

#file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "--------------------------- INTEGRAÇÃO DE SERIES FINALIZADA ---------------------------\n\n", FILE_APPEND);
