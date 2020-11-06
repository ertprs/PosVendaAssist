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
$routine = $routines->SelectRoutine('Dado Mestre - Produto');

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

#file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "---------------------------INICIANDO INTEGRAÇÃO DE PRODUTOS---------------------------\n\n", FILE_APPEND);

#file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Preparando requisição para imbera \n", FILE_APPEND);

try {

	$link = urlSap();

    if ($_serverEnvironment == 'development') {

	//$url = "https://empwdq00.empaque.fne/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_Produtos_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

    $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_Produtos_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

	$authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

    } else {

	$url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_Produtos_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

	$authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

    }

    $xml_post_string = '
	<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
	    <soapenv:Header/>
	    <soapenv:Body>
		<tel:MT_Produtos_Req>
		</tel:MT_Produtos_Req>
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
    $produtos = curl_exec($ch);
    curl_close($ch);

    #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Retorno Imbera \n", FILE_APPEND);
    #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . "\n" . $produtos . "  \n", FILE_APPEND);

    $xmlFileName = "xmlProdutosImbera-" . date("Y-m-d") . ".xml";
    file_put_contents("/tmp/" . $xmlFileName, $produtos, FILE_APPEND);

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

    #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " LOG NUMERO -- " . $log->SelectId() . "\n", FILE_APPEND);

    $produtos = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$produtos);
    $retornoXML = new \SimpleXMLElement(utf8_encode($produtos));
    $retornoXML = $retornoXML->xpath('//E_PRODUTOS');
    $retornoXML = json_decode(json_encode((array) $retornoXML));
    $retornoXML = $retornoXML[0];

    #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . "\n" . print_r($retornoXML, 1) . "  \n", FILE_APPEND);
    #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " Iniciando Integração com Posvenda \n", FILE_APPEND);

    foreach ($retornoXML->PRODUTOS as $produto) {

        $referencia = (int)$produto->MATNR;
        $descricao = (string)$produto->DESCRICAO;
        $familia = (string)$produto->FAMILIA;
        $linha = (string)$produto->LINHA;
        $origem = (string)$produto->ORIGEM;
        $voltagem = (string)$produto->VOLTAGEM;
        $garantia = (string)$produto->GARANTIA;
        $mao_de_obra = (string)$produto->MAO_OBRA;
        $numero_serie = (string)$produto->NR_SERIE_OBRIGATORIO;

        #file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . " ITEM - REF: $referencia, DESC: $descricao, FAMIL: $familia, LINHA: $linha ORIG: $origem, VOLTAG: $voltagem, GARANT: $garantia, MAO_OBRA: $mao_de_obra, SERIE: $numero_serie \n", FILE_APPEND);

        if ($numero_serie == 'T') {
            $numero_serie = 'true';
        } else {
            $numero_serie = 'false';
        }

        //PROCURANDO FAMILIA
        $sqlFamilia = "SELECT familia FROM tbl_familia where descricao = '$familia' AND fabrica = $login_fabrica";
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $sqlFamilia . "\n", FILE_APPEND);
        $res = pg_query($con, $sqlFamilia);
	
	if (pg_num_rows($res) == 0) {
            $finalStatus = 2;
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($log->SelectId());
            $logError->setErrorMessage("Família $familia não encontrada");
            $logError->setContents(" ITEM - REF: $referencia, DESC: $descricao, FAMIL: $familia, LINHA: $linha ORIG: $origem, VOLTAG: $voltagem, GARANT: $garantia, MAO_OBRA: $mao_de_obra, SERIE: $numero_serie");
            $logError->Insert();
         #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . "Familia $familia não encontrada \n", FILE_APPEND);
            continue;
        }
	
	$familia = pg_fetch_result($res, 0, 'familia');

        //PROCURANDO LINHA
        $sqlLinha = "SELECT linha FROM tbl_linha where nome = '$linha' AND fabrica = $login_fabrica";
        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $sqlLinha . "\n", FILE_APPEND);
        $res = pg_query($con, $sqlLinha);
	
	if (pg_num_rows($res) == 0) {
            $finalStatus = 2;
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($log->SelectId());
            $logError->setErrorMessage("Linha $linha não encontrada");
            $logError->setContents(" ITEM - REF: $referencia, DESC: $descricao, FAMIL: $familia, LINHA: $linha ORIG: $origem, VOLTAG: $voltagem, GARANT: $garantia, MAO_OBRA: $mao_de_obra, SERIE: $numero_serie");
            $logError->Insert();

         #   file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . "Linha $linha não encontrada \n", FILE_APPEND);
            continue;
        }
	
	$linha = pg_fetch_result($res, 0, 'linha');

	$sqlProduto = "SELECT * FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND referencia = '{$referencia}';";
	$resProduto = pg_query($con, $sqlProduto);

	if (pg_num_rows($resProduto) > 0) {
	  #  file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s")." - Produto ({$referencia}) já cadastrado\n", FILE_APPEND);
	    continue;
	}

	$insert = "
	    INSERT INTO tbl_produto
		(fabrica_i,referencia,descricao,familia,origem,voltagem,garantia,mao_de_obra,numero_serie_obrigatorio,linha,mao_de_obra_admin)
            VALUES
	    	($login_fabrica,'$referencia','$descricao',$familia,'$origem','$voltagem',$garantia,$mao_de_obra,$numero_serie,$linha,0.00);
	";

        #file_put_contents("/tmp/integracoes-imbera.log", date("d-m-Y H:i:s") . " -  " . $insert . "\n", FILE_APPEND);

	$res = pg_query($con, $insert);

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

#file_put_contents("/tmp/integracoes-imbera.log", "\n\n" . date("d-m-Y H:i:s") . "---------------------------INTEGRAÇÃO DE PRODUTOS FINALIZADA---------------------------\n\n", FILE_APPEND);
