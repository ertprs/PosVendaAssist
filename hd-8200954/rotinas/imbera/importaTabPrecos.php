<?php
/**
 * Created by PhpStorm.
 * User: desnot01
 * Date: 19/07/16
 * Time: 09:36
 */
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
$routine = $routines->SelectRoutine('Dado Mestre - Tabela de Preco');

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

#file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "---------------------------INICIANDO INTEGRAÇÃO DE TABELA DE PREÇO---------------------------\n\n", FILE_APPEND);

#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Preparando requisição para imbera \n", FILE_APPEND);
$link = urlSap(true);

if ($_serverEnvironment == 'development') {

    $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_TabPreco_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

} else {

    $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_TabPreco_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

}

$xml_post_string = '
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
	<soapenv:Header/>
	<soapenv:Body>
	    <tel:MT_TabPreco_Req>
	    </tel:MT_TabPreco_Req>
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
$tabPrecos = curl_exec($ch);
curl_close($ch);

#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Retorno Imbera \n", FILE_APPEND);

$xmlFileName = "xmlTabPrecoImbera-" . date("Y-m-d") . ".xml";
file_put_contents("/tmp/" . $xmlFileName, $tabPrecos, FILE_APPEND);

try {
    $tdocsUploader = new TDocsTinyUploader();
    $docs = $tdocsUploader->sendFile("/tmp/" . $xmlFileName);
    $doc = array_pop($docs[0]);

    $log->setTDocs($doc['unique_id']);
} catch (\Exception $e) {
    #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " ERRO UPLOAD " . $e->getMessage() . "\n", FILE_APPEND);
}

$log->Insert();
$log->setRoutineScheduleLog($log->SelectId());

$tabPrecos = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$tabPrecos);
$retornoXML = new \SimpleXMLElement(utf8_encode($tabPrecos));
$retornoXML = $retornoXML->xpath('//E_TABELA_PRECO');
$retornoXML = json_decode(json_encode((array) $retornoXML));
$retornoXML = $retornoXML[0];

#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . "\n" . print_r($retornoXML, 1) . "  \n", FILE_APPEND);
#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Iniciando Integração com Posvenda \n", FILE_APPEND);

try {
    foreach ($retornoXML->TAB_PRECO as $tabPreco) {

        $tabela = $tabPreco->TABELA;
        $pecaRef = (int)$tabPreco->MATNR;
        $preco = (string)$tabPreco->PRECO;

        #file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . " ITEM - TAB: $tabela, PECA: $pecaRef, PRECO: $preco \n", FILE_APPEND);

        //PROCURANDO TABELA
        $sqlTabela = "SELECT tabela FROM tbl_tabela where descricao = '$tabela' AND fabrica = $login_fabrica";
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $sqlTabela . "\n", FILE_APPEND);

        $res = pg_query($con, $sqlTabela);
        if (pg_num_rows($res) == 0) {
            $finalStatus = 2;
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($log->SelectId());
            $logError->setErrorMessage("Tabela $tabela não encontrada");
            $logError->setContents(" ITEM - TAB: $tabela, PECA: $pecaRef, PRECO: $preco");
            $logError->Insert();

            #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . "Tabela $tabela não encontrada \n", FILE_APPEND);
            continue;
        }
        $tabela = pg_fetch_result($res, 0, 'tabela');

        //PROCURANDO PECA
        $sqlPeca = "SELECT peca FROM tbl_peca WHERE referencia = '$pecaRef' AND fabrica = $login_fabrica";
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $sqlPeca . "\n", FILE_APPEND);

        $res = pg_query($con, $sqlPeca);
        if (pg_num_rows($res) == 0) {
            $finalStatus = 2;
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($log->SelectId());
            $logError->setErrorMessage("Peça $pecaRef não encontrada");
            $logError->setContents(" ITEM - TAB: $tabela, PECA: $pecaRef, PRECO: $preco");
            $logError->Insert();

            #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . "Peça $pecaRef não encontrada \n", FILE_APPEND);
            continue;
        }
        $peca = pg_fetch_result($res, 0, 'peca');

        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . "Verificando existencia da tabela e peça \n", FILE_APPEND);
        $sqlVerify = "SELECT tabela_item FROM tbl_tabela_item WHERE tabela = $tabela AND peca = $peca";
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $sqlVerify . "\n", FILE_APPEND);
        $res = pg_query($con, $sqlVerify);
        if (pg_num_rows($res) == 0) {
            #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . "Não existe  - INSERT\n", FILE_APPEND);
            $query = "INSERT INTO tbl_tabela_item(tabela,peca,preco)
                                    VALUES($tabela,$peca,$preco)";
        } else {
            #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . "Já existe - UPDATE\n", FILE_APPEND);
            $query = "UPDATE tbl_tabela_item set preco = $preco WHERE tabela = $tabela AND peca = $peca";
        }
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $query . "\n", FILE_APPEND);


        $res = pg_query($con, $query);
        if (pg_last_error($con)) {
            $finalStatus = 2;
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($log->SelectId());
            $logError->setErrorMessage("ERRO AO EXECUTAR A QUERY");
            $logError->setContents("ERRO AO EXECUTAR A QUERY $query ------------ " . pg_last_error($con));
            $logError->Insert();

         #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " !!!!!!!!!!!!!!!!!!!!! ERRO AO EXECUTAR A QUERY " . pg_last_error($con) . " !!!!!!!!!!!!!!!!!!\n", FILE_APPEND);
        }
    }

    if ($finalStatus == 2) {
        $finalMessage = "Finalizada com algumas exceções";
    }

    $log->setStatus($finalStatus);
    $log->setStatusMessage($finalMessage);
    $log->Update();

} catch (\Exception $e) {
    $log->setStatus(0);
    $log->setStatusMessage($e->getMessage());
    $log->Update();
}
#file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "---------------------------INTEGRAÇÃO DE TABELA DE PREÇOS FINALIZADA---------------------------\n\n", FILE_APPEND);
