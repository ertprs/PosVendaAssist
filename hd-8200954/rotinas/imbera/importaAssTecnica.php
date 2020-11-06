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

function strtim($var)
{
    if (!empty($var)) {
        $var = trim($var);
        $var = str_replace("'", "\'", $var);
        $var = str_replace("/", "", $var);
    }

    return $var;
}
function cortaStr($str, $len)
{
    return substr($str, 0, $len);
}
function adicionalTrim($str, $len = 0)
{
    $str = str_replace(".", "", $str);
    $str = str_replace("-", "", $str);

    if ($len != 0 and strlen($str)>0) {
        $str = cortaStr($str, $len);
        
    }
    return $str;    
}
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

try {
	
    $link = urlSap();
    if ($_serverEnvironment == 'development') {

        $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_AssTecnica_Receiver&interfaceNamespace=http://imbera.com/telecontrol";


        $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

    } else {

        $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_AssTecnica_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

        $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

    }

    $xml_post_string = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
        <soapenv:Header/>
        <soapenv:Body>
        <tel:MT_AssTecnica_Receiver_Req>
        </tel:MT_AssTecnica_Receiver_Req>
        </soapenv:Body>
        </soapenv:Envelope>';

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
        $assisTecnica = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $xmlFileName = "xmlAssisTecnicaImbera-" . date("Y-m-d") . ".xml";
        file_put_contents("$origem" . $xmlFileName, $assisTecnica, FILE_APPEND);

        //Pega dados no arquivo, ws apenas retorna uma vez por dia
        //$assisTecnica = file_get_contents("./entrada/xmlAssisTecnicaImbera-2017-11-22.xml");

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

        $assisTecnica = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$assisTecnica);
        $retornoXML = new \SimpleXMLElement(utf8_encode($assisTecnica));
        $retornoXML = $retornoXML->xpath('//ASS_TECNICA');
        $retornoXML = json_decode(json_encode((array) $retornoXML));
        //$retornoXML = $retornoXML[0];

    if (count($retornoXML) == 0){
        break;
    }

        foreach ($retornoXML as $dados) {

                $codigo_posto       = (int)$dados->KUNNR;
                $razao              = $dados->NAME1;
                $nome_fantasia      = $dados->NAME2;
                $cnpj               = $dados->STCD1;
                $ie                 = $dados->STCD2;
                $endereco           = $dados->STREET;
                $numero             = $dados->HOUSE_NUM;
                $complemento        = $dados->HOUSE_NUM2;
                $bairro             = $dados->CITY2;
                $cep                = $dados->POST_CODE1;
                $cidade             = $dados->CITY1;
                $estado             = $dados->REGION;

                $telefone           = $dados->TELF1;
                $fax                = $dados->TELFX;

                $capital_interior    = strtim($capital_interior);
                $contato = strtim($contato);
                $codigo_posto = adicionalTrim($codigo_posto, 6);
                $razao = cortaStr($razao, 60);
                $nome_fantasia = cortaStr($nome_fantasia, 60);
                $cnpj = adicionalTrim($cnpj, 14);
                $ie = adicionalTrim($ie);
                $endereco = cortaStr($endereco, 50);
                $numero =adicionalTrim($numero);
                if(strlen(trim($complemento))>0){
                    $complemento = adicionalTrim($complemento);
                }else{
                    $complemento = "";
                }
                $bairro = cortaStr($bairro, 20);
                $cep = cortaStr($cep, 8);
                $cidade = cortaStr($cidade, 30);
                $estado = cortaStr($estado, 2);
                $email = strtolower(cortaStr($email, 50));
                $telefone = cortaStr($telefone, 30);
                $fax = cortaStr($fax, 30);
                $contato = cortaStr($contato, 30);
                

                $valida_cpnj = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj')");
                if (strlen(trim(pg_last_error($con)))>0 ) {
                    $finalStatus = 2;
                    $logError = new \Posvenda\LogError();
                    $logError->setRoutineScheduleLog($log->SelectId());
                    $logError->setErrorMessage("CNPJ $cnpj inválido.");
                    $logError->setContents(" CNPJ: $cnpj ");
                    $logError->Insert();
                    continue;
                }

                $sql_posto = "SELECT tbl_posto.posto FROM tbl_posto WHERE tbl_posto.cnpj = '$cnpj'";
                $query_posto = pg_query($con, $sql_posto);

                if (pg_num_rows($query_posto) == 0) {
                    $sql = "INSERT INTO tbl_posto (
                                            nome,
                                            nome_fantasia,
                                            cnpj,
                                            ie,
                                            endereco,
                                            numero,
                                            bairro,
                                            cep,
                                            cidade,
                                            estado,
                                            email,
                                            fone,
                                            fax,
                                            contato,
                                            capital_interior
                                        ) VALUES (
                                            (E'$razao'),
                                            (E'$nome_fantasia'),
                                            '$cnpj',
                                            '$ie',
                                            '$endereco',
                                            '$numero',
                                            '$bairro',
                                            '$cep',
                                            '$cidade',
                                            '$estado',
                                            '$email',
                                            '$telefone',
                                            '$fax',
                                            '$contato',
                                            '$capital_interior'
                                        )";
                    $query = pg_query($con, $sql);

                    if (pg_last_error()) {
                        $finalStatus = 2;
                        $logError = new \Posvenda\LogError();
                        $logError->setRoutineScheduleLog($log->SelectId());
                        $logError->setErrorMessage("ERRO AO EXECUTAR A QUERY");
                        $logError->setContents("ERRO AO EXECUTAR A QUERY $sql ------------ " . pg_last_error($con));
                        $logError->Insert();
                        continue;
                    }
                    $query_posto_id = pg_query($con, "SELECT currval ('seq_posto') AS seq_posto");
                    $posto = pg_fetch_result($query_posto_id, 0, 'seq_posto');

                } else {
                    $posto = pg_fetch_result($query_posto, 0, 'posto');
                }

                $sql = "SELECT 
                            tbl_posto_fabrica.posto
                        FROM   tbl_posto_fabrica
                        WHERE  tbl_posto_fabrica.posto   = $posto
                        AND    tbl_posto_fabrica.fabrica = $login_fabrica";
                $query = pg_query($con, $sql);

                if (pg_last_error()) {
                    $finalStatus = 2;
                    $logError = new \Posvenda\LogError();
                    $logError->setRoutineScheduleLog($log->SelectId());
                    $logError->setErrorMessage("ERRO AO EXECUTAR A QUERY");
                    $logError->setContents("ERRO AO EXECUTAR A QUERY $sql ------------ " . pg_last_error($con));
                    $logError->Insert();
                    continue;
                }

                if (pg_num_rows($query) == 0) {
                    $sql = "INSERT INTO tbl_posto_fabrica (
                                                posto,
                                                fabrica,
                                                senha,
                                                tipo_posto,
                                                login_provisorio,
                                                codigo_posto,
                                                credenciamento,
                                                contato_fone_comercial,
                                                contato_fax,
                                                contato_endereco ,
                                                contato_numero,
                                                contato_complemento,
                                                contato_bairro,
                                                contato_cep,
                                                contato_cidade,
                                                contato_estado,
                                                contato_email,
                                                nome_fantasia,
                                                contato_nome
                                            ) VALUES (
                                                $posto,
                                                $login_fabrica,
                                                '',
                                                400,
                                                null,
                                                '$codigo_posto',
                                                'DESCREDENCIADO',
                                                '$telefone',
                                                '$fax',
                                                '$endereco',
                                                '$numero',
                                                '$complemento',
                                                (E'$bairro'),
                                                '$cep',
                                                (E'$cidade'),
                                                '$estado',
                                                '$email',
                                                (E'$nome_fantasia'),
                                                (E'$contato')
                                            )";

                } else {
                    $sql = "UPDATE tbl_posto_fabrica SET
                                        codigo_posto = '$codigo_posto',
                                        contato_endereco = '$endereco',
                                        contato_bairro = (E'$bairro'),
                                        contato_cep = '$cep',
                                        contato_cidade = (E'$cidade'),
                                        contato_estado = '$estado',
                                        contato_numero = '$numero',
                                        contato_fone_comercial = '$telefone',
                                        contato_fax = '$fax',
                                        nome_fantasia = (E'$nome_fantasia'),
                                        contato_email = '$email'
                                WHERE tbl_posto_fabrica.posto = $posto
                                AND tbl_posto_fabrica.fabrica = $login_fabrica";
                }
                $query = pg_query($con, $sql);

                if (pg_last_error()) {
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

file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "---------------------------INTEGRACAO DE PECAS FINALIZADA---------------------------\n\n", FILE_APPEND);
