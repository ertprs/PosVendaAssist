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
$routine = $routines->SelectRoutine('Dado Mestre - Peca');

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

#file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "---------------------------INICIANDO INTEGRAÇÃO DE PEÇAS---------------------------\n\n", FILE_APPEND);

#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Preparando requisição para imbera \n", FILE_APPEND);

try {

	$link = urlSap(true);

    if ($_serverEnvironment == 'development') {

	$url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_Pecas_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

	$authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

    } else {

	$url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_Pecas_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

	$authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

    }

    $xml_post_string = '
	<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
	    <soapenv:Header/>
	    <soapenv:Body>
		<tel:MT_Pecas_Req>
		</tel:MT_Pecas_Req>
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

    while (true) {
    	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2000);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$pecas = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Retorno Imbera \n", FILE_APPEND);
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . "\n" . $pecas . "  \n", FILE_APPEND);

        $xmlFileName = "xmlPecasImbera-" . date("Y-m-d") . ".xml";
        file_put_contents("/tmp/" . $xmlFileName, $pecas, FILE_APPEND);

        try {
            $tdocsUploader = new TDocsTinyUploader();
            $docs = $tdocsUploader->sendFile("/tmp/" . $xmlFileName);
            $doc = array_pop($docs[0]);

            $log->setTDocs($doc['unique_id']);
        } catch (\Exception $e) {
         #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " ERRO UPLOAD " . $e->getMessage() . "\n", FILE_APPEND);
        }


        $log->Insert();
        $log->setRoutineScheduleLog($log->SelectId());

        $pecas = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$pecas);
        $retornoXML = new \SimpleXMLElement(utf8_encode($pecas));
        $retornoXML = $retornoXML->xpath('//E_PECAS');
	$retornoXML = json_decode(json_encode((array) $retornoXML));
	$retornoXML = $retornoXML[0];

	#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . "\n" . print_r($retornoXML, 1) . "  \n", FILE_APPEND);

        if (count($retornoXML->PECAS) == 0) {
         #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Nenhuma peça retornada, finalizando buscas.  \n", FILE_APPEND);
            break;
	}

	#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Iniciando Integração com Posvenda \n", FILE_APPEND);

        foreach ($retornoXML->PECAS as $peca) {

            $referencia = (int)$peca->MATNR;
            $descricao = (string)$peca->DESCRICAO;
            $origem = (string)$peca->ORIGEM;
            $ipi = (string)$peca->IPI;
            $ativo = (string)$peca->ATIVO;
            $garantia_diff = (string)$peca->GARANTIA_DIF;
            $multiplo = (string)$peca->MULTIPLO;
            $unidade = (string)$peca->MEINS;
            $clas_fiscal = (string)$peca->STEUC;

          #  file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . " ITEM - REF: $referencia, DESC: $descricao, ORIG: $origem, IPI: $ipi, ATIVO: $ativo, GARANT_DIFF: $garantia_diff, MULT: $multiplo \n", FILE_APPEND);

            if ($ativo == 'T') {
                $ativo = 'true';
            } else {
                $ativo = 'false';
	    }

	    $sql = "SELECT * FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$referencia}';";
        $res = pg_query($con, $sql);
		$acao = "";
        if (pg_num_rows($res) == 0) {
            $acao = "insert";
        } else {
            $acao = "update";
        }
    

	    if (strcmp($acao, "insert") == 0) {
            # file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Inserindo Peça $referencia\n", FILE_APPEND);

            $sql = "INSERT INTO tbl_peca(fabrica,referencia,descricao,unidade,ativo,classificacao_fiscal,origem,ipi,garantia_diferenciada,multiplo)
                    VALUES($login_fabrica,'$referencia','$descricao','$unidade',$ativo,'$clas_fiscal','$origem',$ipi,$garantia_diff,$multiplo)";

            #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $insert . "\n", FILE_APPEND);
	    } else {
            $sql = "
                UPDATE tbl_peca
                SET fabrica               = $login_fabrica,
                    referencia            = '$referencia',
                    descricao             = '$descricao',
                    unidade               = '$unidade',
                    ativo                 = $ativo,
                    classificacao_fiscal  = '$clas_fiscal',
                    origem                = '$origem',
                    ipi                   = $ipi,
                    garantia_diferenciada = $garantia_diff,
                    multiplo              = $multiplo
                WHERE fabrica = $login_fabrica AND referencia = '$referencia'
            ";
        }

            $res = pg_query($con, $sql);
            if (pg_last_error($con)) {
                $finalStatus = 2;
                $logError = new \Posvenda\LogError();
                $logError->setRoutineScheduleLog($log->SelectId());
                $logError->setErrorMessage("ERRO AO EXECUTAR A QUERY");
                $logError->setContents("ERRO AO EXECUTAR A QUERY $insert ------------ " . pg_last_error($con));
                $logError->Insert();
             #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " !!!!!!!!!!!!!!!!!!!!! ERRO AO EXECUTAR A QUERY " . pg_last_error($con) . " !!!!!!!!!!!!!!!!!!\n", FILE_APPEND);

            }
        }
    }

    if ($finalStatus == 2) {
        $finalMessage = "Finalizada com algumas excessões";
    }

    $log->setStatus($finalStatus);
    $log->setStatusMessage($finalMessage);
    $log->Update();

} catch (\Exception $e) {
    $log->setStatus(0);
    $log->setStatusMessage($e->getMessage());
    $log->Update();
}

#file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "---------------------------INTEGRAÃ‡ÃƒO DE PECAS FINALIZADA---------------------------\n\n", FILE_APPEND);
