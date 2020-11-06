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

$origem = "/tmp/";

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;

$routines = new \Posvenda\Routine();
$routine = $routines->SelectRoutine('Dado Mestre - Lista Basica');

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

try{

	$link = urlSap();

    if ($_serverEnvironment == 'development') {

        $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_ListaBase_Receiver&interfaceNamespace=http://imbera.com/telecontrol";


        $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");
 
    } else {

        $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_ListaBase_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

        $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");
    }

    $xml_post_string = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
        <soapenv:Header/>
        <soapenv:Body>
        <tel:MT_ListaBase_Receiver_Req>
        </tel:MT_ListaBase_Receiver_Req>
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
        $listaBasica = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);


        $xmlFileName = "xmlListaBasicaImbera-" . date("Y-m-d") . ".xml";
        file_put_contents("$origem" . $xmlFileName, $listaBasica, FILE_APPEND);
      
       // usando arquivo de texto;
       // $listaBasica = file_get_contents('./entrada/xmlListaBasicaImbera-2017-11-23.xml');

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

        $listaBasica = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$listaBasica);
        $retornoXML = new \SimpleXMLElement(utf8_encode($listaBasica));
        $retornoXML = $retornoXML->xpath('//LISTA');
        $retornoXML = json_decode(json_encode((array) $retornoXML));
        //$retornoXML = $retornoXML[0]; //retirado pois precido de todas as linhas;

        if (count($retornoXML) == 0) {
            break;
        }

        foreach ($retornoXML as $dados) {
            $produto_referencia = (int)$dados->MATNR;
            $peca_referencia = (int)$dados->IDNRK;
            $qtde = (int)$dados->MNGLG;

            $sql_produto = "SELECT produto FROM tbl_produto
                            WHERE tbl_produto.referencia = TRIM('$produto_referencia')
                            AND tbl_produto.fabrica_i = $login_fabrica LIMIT 1";
            $query_produto = pg_query($con, $sql_produto);

            if (pg_num_rows($query_produto) == 1) {
                $produto_id = pg_fetch_result($query_produto, 0, 'produto');
            } else {
                $msg_erro .= pg_last_error($con). "SQL > $sql_produto \n\n";
                $finalStatus = 2;
                $logError = new \Posvenda\LogError();
                $logError->setRoutineScheduleLog($log->SelectId());
                $logError->setErrorMessage("produto $produto_referencia não encontrado");
                $logError->setContents(" PRODUTO - REF: $produto_referencia ");
                $logError->Insert();
                continue;
            }

            $sql_peca = "SELECT peca FROM tbl_peca
                                WHERE tbl_peca.referencia = TRIM('$peca_referencia')
                                AND tbl_peca.fabrica = $login_fabrica LIMIT 1";
            $query_peca = pg_query($con, $sql_peca);

            if (pg_num_rows($query_peca) == 1) {
                $peca_id = pg_fetch_result($query_peca, 0, 'peca');
            } else {
                $finalStatus = 2;
                $logError = new \Posvenda\LogError();
                $logError->setRoutineScheduleLog($log->SelectId());
                $logError->setErrorMessage("peça $peca_referencia não encontrada");
                $logError->setContents(" PECA - REF: $peca_referencia ");
                $logError->Insert();
                continue;
            }

            $sql_lista_basica = "SELECT tbl_lista_basica.produto,tbl_lista_basica.peca FROM tbl_lista_basica
                            WHERE tbl_lista_basica.produto = $produto_id AND tbl_lista_basica.peca = $peca_id
                                AND tbl_lista_basica.fabrica = $login_fabrica";
            $query_lista_basica = pg_query($con, $sql_lista_basica);

            if (pg_num_rows($query_lista_basica) == 0) {
                $sql = "INSERT INTO tbl_lista_basica (
                                        fabrica,
                                        produto,
                                        peca,
                                        qtde
                                    )VALUES(
                                        $login_fabrica,
                                        $produto_id,
                                        $peca_id,
                                        $qtde
                                    )";
            } else {
                $sql = "UPDATE tbl_lista_basica SET
                                qtde = $qtde
                            WHERE tbl_lista_basica.produto = $produto_id
                            AND   tbl_lista_basica.peca    = $peca_id
                            AND tbl_lista_basica.fabrica   = $login_fabrica ";

            }
            $query = pg_query($con, $sql);

            if (strlen(pg_last_error($con))) {
                $finalStatus = 2;
                $logError = new \Posvenda\LogError();
                $logError->setRoutineScheduleLog($log->SelectId());
                $logError->setErrorMessage("ERRO AO EXECUTAR A QUERY");
                $logError->setContents("ERRO AO EXECUTAR A QUERY $sql ------------ " . pg_last_error($con));
                $logError->Insert();
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

?>
