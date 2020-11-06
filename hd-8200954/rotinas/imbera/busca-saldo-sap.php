<?php

require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/./funcoes.php';

include_once dirname(__FILE__) . "/../../class/aws/s3_config.php";
include_once S3CLASS;

date_default_timezone_set("America/Sao_Paulo");

global $login_fabrica;
$login_fabrica = 158;

$debug = true;

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;

//Aguardando faturamento
//Faturamento Parcial
//Cancelado Parcial

$sqlPedidos = "
    SELECT
        tbl_pedido.pedido
    FROM tbl_pedido
    JOIN tbl_tipo_pedido USING(tipo_pedido,fabrica)
    JOIN tbl_posto_fabrica USING(posto,fabrica)
    JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
    WHERE status_pedido IN (2,5,8)
    AND tbl_pedido.fabrica = {$login_fabrica}
    AND tbl_tipo_pedido.pedido_em_garantia IS TRUE
    AND tbl_tipo_posto.tecnico_proprio IS NOT TRUE
    AND tbl_tipo_posto.posto_interno IS NOT TRUE
    AND tbl_pedido.data BETWEEN NOW() - INTERVAL '3 months' AND NOW()
    ORDER BY pedido DESC;
";

echo $sqlPedidos."\n";
$pedidos = pg_query($con, $sqlPedidos);
$pedidos = pg_fetch_all($pedidos);
var_dump($pedidos);

foreach ($pedidos as $value) {
    echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! INICIO FOREACH PEDIDO !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
    $pedido = $value['pedido'];

    try { // $e

        $status = 1;
        $message = "Finalizado Com Sucesso";
        $routine = new Routine();
	$routine->setFactory($login_fabrica);

        $arr = $routine->SelectRoutine("Busca Saldo SAP");
        $routine_id = $arr[0]["routine"];

        $routineSchedule = new RoutineSchedule();
        $routineSchedule->setRoutine($routine_id);
        $routineSchedule->setWeekDay(date("w"));

        $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

        $log = new Log();
        $log->setRoutineSchedule($routine_schedule_id);
        $log->setDateStart(date("Y-m-d H:i:s"));

        $finalMessage = "Finalizada com sucesso";

        try {
	    echo "Chamada ao webservice...\n";

	    $link = urlSap(true);

	    if ($_serverEnvironment == 'development') {

		$url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_BuscaDados_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

		$authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

	    } else {

		$url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_BuscaDados_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

		$authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

	    }
            
	    $xml_post_string = '
		<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
		    <soapenv:Header/>
		    <soapenv:Body>
			<tel:MT_BuscaDados_Req>
			    <I_PEDIDO>'.$pedido.'</I_PEDIDO>
			</tel:MT_BuscaDados_Req>
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

            echo $xml_post_string;
	    echo "SENDING $pedido\n";

	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 500);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $faturamentos = curl_exec($ch);
            curl_close($ch);

	    echo "Resquest \n\r";
	    echo "URL: ".$url."\n\r";

	    echo 'Response \n\r';
	    echo 'Error Curl: '.$erroCurl.'\n\r';
	    echo 'Http Code: '.$httpcode.'\n\r';
	    echo "---".$faturamentos."\n\n";

            $logId = $log->Insert();
            echo "LOG #".$logId."\n";

    //         $faturamentos = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xs="http://www.w3.org/2001/XMLSchema">
    //    <soapenv:Header/>
    //    <soapenv:Body>
    //       <DATA>2016-07-26</DATA>
    //       <HORA>12:46:44</HORA>
    //       <PEDIDO>0012155482</PEDIDO>
    //       <KUNNR>0000008316</KUNNR>
    //       <ITENS>
    //          <MATNR>000000000003048495</MATNR>
    //          <MENGE>1.000</MENGE>
    //          <NETWR>-2.20</NETWR>
    //          <MWSBP>2.20</MWSBP>
    //          <NFENUM>000019572</NFENUM>
    //          <SERIE>2</SERIE>
    //          <DOCNUM>0000135054</DOCNUM>
    //          <ITEMNF>000010</ITEMNF>
    //          <CFOP>5949AA</CFOP>
    //          <WERKS>6004</WERKS>
    //          <IMPOSTOS>
    //             <TAXGRP>ICMS</TAXGRP>
    //             <BASE>12.20</BASE>
    //             <RATE>18.00</RATE>
    //             <TAXVAL>2.20</TAXVAL>
    //          </IMPOSTOS>
    //       </ITENS>
    //       <ITENS>
    //          <MATNR>000000000003048495</MATNR>
    //          <MENGE>1.000</MENGE>
    //          <NETWR>-2.20</NETWR>
    //          <MWSBP>2.20</MWSBP>
    //          <NFENUM>000019572</NFENUM>
    //          <SERIE>2</SERIE>
    //          <DOCNUM>0000135054</DOCNUM>
    //          <ITEMNF>000010</ITEMNF>
    //          <CFOP>5949AA</CFOP>
    //          <WERKS>6004</WERKS>
    //          <IMPOSTOS>
    //             <TAXGRP>ICMS</TAXGRP>
    //             <BASE>12.20</BASE>
    //             <RATE>18.00</RATE>
    //             <TAXVAL>2.20</TAXVAL>
    //          </IMPOSTOS>
    //       </ITENS>
    //    </soapenv:Body>
    // </soapenv:Envelope>
    // ';

	    $faturamentos = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$faturamentos);
            $retornoXML = new \SimpleXMLElement(utf8_encode($faturamentos));
            $retornoXML = $retornoXML->xpath('//E_DADOS_NF');
	    $retornoXML = json_decode(json_encode((array) $retornoXML), true);
	    $retornoXML = $retornoXML[0];

            //POSTO
	    $posto = (int) $retornoXML['KUNNR'];

	    if (empty($posto)) {
		continue;
	    }

            $sql = "SELECT posto FROM tbl_posto_fabrica where codigo_posto = '".$posto."' AND fabrica = $login_fabrica";
            echo $sql."\n";
            $postoResponse = pg_query($con, $sql);
            $posto = pg_fetch_result($postoResponse,0,'posto');
            echo "---------------------------------------------------------------------------> POSTO ".$posto."\n";
            if($posto == "" || $posto == null){
                echo "!!!!!!!!!!!!! POSTO não encontrado !!!!!!!!!!!!!!!!";
                continue;
            }
            // ---------------------------

            $pedido = (int) $retornoXML['PEDIDO'];
            $emissao = $retornoXML['DATA'];
            
            $saida = "";

            #############################################################
            //$begin = pg_query($con,"BEGIN");
            #############################################################

	    $nota_fiscal_anterior = "";

	    $itens = array();

	    if (!empty($retornoXML['ITENS']['NFENUM'])) {	
		$itens[] = $retornoXML['ITENS'];
	    } else {
		$itens = $retornoXML['ITENS'];
	    }

            foreach ($itens as $item) {
		$nota_fiscal = $item['NFENUM'];

                echo "-------------->NF  ".$nota_fiscal."\n";

                echo "!!!!!!!!!!!!!!!!!!! INICIO FOREACH ITEM !!!!!!!!!!!!!!!!!!!!\n";
                if($nota_fiscal != $nota_fiscal_anterior){
                    $nota_fiscal_anterior = $nota_fiscal;

		    $sqlFaturamento = "
			SELECT faturamento
			FROM tbl_faturamento
			WHERE pedido = {$pedido}
			AND nota_fiscal = '{$nota_fiscal}'
			AND fabrica = {$login_fabrica};
		    ";

                    echo $sqlFaturamento."\n";
		    $faturamentoResponse = pg_query($con,$sqlFaturamento);

                    if(pg_num_rows($faturamentoResponse) > 0){          
                        $status = 2;
                        echo $message = "----------------- > Faturamento já cadastrado para nota $nota_fiscal \n";  

                        $routineScheduleLogError = new LogError();
                        $routineScheduleLogError->setRoutineScheduleLog($logId);
                        $routineScheduleLogError->setErrorMessage("Faturamento encontrado para o pedido #".$pedido);
                        $routineScheduleLogError->Insert();
                        $faturamento = pg_fetch_result($faturamentoResponse,0,'faturamento');
                    }else{
                        $insertFaturamento = "INSERT INTO tbl_faturamento(fabrica, pedido, posto, emissao, saida, nota_fiscal, total_nota) VALUES($login_fabrica, $pedido, $posto, '$emissao', now(), '$nota_fiscal',  0.00) RETURNING faturamento";                
                        echo $insertFaturamento."\n";
                        $faturamentoResponse = pg_query($con,$insertFaturamento);                        

                        $faturamento = pg_fetch_result($faturamentoResponse,0,'faturamento');
                        if($faturamento == ""){
                            echo "!!!!!!! FATURAMENTO NÃO ENCONTRADO !!!!!!!";
                            continue;
			}
                    }   
                }

                //PECA-----------------------------------------
                
                $referencia = (int) $item['MATNR'];
                echo "MATERIAL ".$referencia."\n";
                $sqlPeca = "SELECT peca FROM tbl_peca where referencia = '".$referencia."' AND fabrica = $login_fabrica";            
                echo $sqlPeca."\n";
                $pecaResponse = pg_query($con, $sqlPeca);

                $peca = pg_fetch_row($pecaResponse);
                $peca = $peca[0];

                if($peca == ""){
                    echo "Peça #".$referencia." não encontrada no faturamento do pedido #".$pedido."\n";
                    $status = 2;
                    $message = "Finalizado Com Alguns Erros";  

                    $routineScheduleLogError = new LogError();
                    $routineScheduleLogError->setRoutineScheduleLog($logId);
                    $routineScheduleLogError->setErrorMessage("Peça #".$referencia." não encontrada no faturamento do pedido #".$pedido);
                    $routineScheduleLogError->Insert();            
                    continue;
                }            
                //---------------------------------------------

                $menge = $item['MENGE']; //qtde
                $netwr = $item['NETWR']; //preÃ§o
                $mwsbp = $item['MWSBP']; //impostos
		$cfop = $item['CFOP'];

		foreach ($item['IMPOSTOS'] as $imposto) {
		    if($imposto['TAXGRP'] == "ICMS") {
                    	$aliq = $imposto['RATE'];
                    	$base = $imposto['BASE'];
                    	$valor = $imposto['TAXVAL'];

                    	$impostos = ", aliq_icms, base_icms, valor_icms";
		    	$valorImpostos = ", $aliq, $base, $valor";

                    	$updateImpostos = ", aliq_icms = $aliq, base_icms = $base, valor_icms = $valor";
		    }
		}

                $sqlPedidoItem = "SELECT pedido_item FROM tbl_pedido_item where pedido = $pedido AND peca = $peca";
                echo $sqlPedidoItem."\n";
                $pedidoItemResponse = pg_query($con, $sqlPedidoItem);
                $pedidoItemResponse = pg_fetch_row($pedidoItemResponse);            
                $pedido_item = $pedidoItemResponse[0];
                if($pedido_item == ""){
                    echo "Pedido Item não encontrado para a peça #".$peca." e pedido #".$pedido ."\n";

                    $status = 2;
                    $message = "Finalizado Com Alguns Erros";  

                    $routineScheduleLogError = new LogError();
                    $routineScheduleLogError->setRoutineScheduleLog($logId);
                    $routineScheduleLogError->setErrorMessage("Pedido Item não encontrado para a peça #".$peca." e pedido #".$pedido);
                    $routineScheduleLogError->Insert();            
                    continue;
                }            

                $sqlPecaVerifica = "SELECT peca FROM tbl_faturamento_item WHERE pedido = $pedido AND pedido_item  = $pedido_item AND peca = $peca AND faturamento = $faturamento;"; 
                echo $sqlPecaVerifica."\n";
                $resPecaVerifica = pg_query($con,$sqlPecaVerifica);
                if(pg_num_rows($resPecaVerifica) == 0){
                    $queryFaturamentoItem = "INSERT INTO tbl_faturamento_item(pedido, pedido_item, faturamento, peca, qtde, preco, valor_impostos, cfop $impostos) 
                        VALUES({$pedido}, {$pedido_item}, $faturamento, $peca, $menge, $netwr, $mwsbp, '$cfop' $valorImpostos)";                
                    echo $queryFaturamentoItem."\n\n";
                    $fatItemResponse = pg_query($con, $queryFaturamentoItem);


                    $sqlAtualizaPedidoItem = "SELECT fn_atualiza_pedido_item($peca, $pedido, $pedido_item, $menge)";
                    echo $sqlAtualizaPedidoItem."\n\n";
                    $atualizaPedidoItem = pg_query($con,$sqlAtualizaPedidoItem);


		    $sql = "
			SELECT tp.codigo
                        FROM tbl_pedido p
                        INNER JOIN tbl_tipo_pedido tp ON p.tipo_pedido = tp.tipo_pedido
			WHERE p.fabrica = $login_fabrica
			AND p.pedido = $pedido;
		    ";
                    echo $sql;
                    $qryTP = pg_query($con, $sql);

                    echo date("d-m-Y H:i:s") . "  tipo de pedido -> $sql" . " \n";
                    $tipoPedido = pg_fetch_result($qryTP, 0, 'codigo');

                    echo date("d-m-Y H:i:s") . "  tipo de pedido->$tipoPedido" . " \n";
                    if ($tipoPedido == "BON-GAR") {
			    $sqlE = "
				    SELECT
				    	posto
				    FROM tbl_estoque_posto_movimento
				    WHERE fabrica = {$login_fabrica}
				    AND posto = {$posto}
				    AND peca = {$peca}
				    AND nf = '{$nota_fiscal}';
			    ";
                        echo $sqlE."\n";
                        $resE = pg_query($con, $sqlE);
                        if (pg_num_rows($resE) == 0) {
                            $sql = "INSERT INTO tbl_estoque_posto_movimento(fabrica, posto, peca, data, qtde_entrada, pedido, nf, faturamento,tipo)
                                VALUES ($login_fabrica, $posto, $peca, current_date, $menge, $pedido, $nota_fiscal, $faturamento, 'GARANTIA');";
                            echo $sql . " \n";
                            $qry = pg_query($con, $sql);
                        }

                        $sql = "SELECT fabrica
                            FROM tbl_estoque_posto
                            WHERE fabrica = $login_fabrica
                            AND posto = $posto
                            AND peca = $peca";
                        echo $sql."\n";
                        $res = pg_query($con, $sql);
                        if (pg_num_rows($res) == 0) {
                            $sql = "INSERT INTO tbl_estoque_posto(qtde, posto, peca, fabrica)
                                VALUES ($menge, $posto, $peca, $login_fabrica)";

                            echo date("d-m-Y H:i:s") . "  $sql \n";
                            $res = pg_query($con, $sql);
                        }else{
                            $sql = "UPDATE tbl_estoque_posto
                                SET qtde =  CASE WHEN qtde IS NULL THEN 0+$menge ELSE qtde + $menge END
                                WHERE fabrica = $login_fabrica AND posto = $posto AND peca = $peca";
                            echo $sql . " \n";
                            $qry = pg_query($con, $sql);
                        }
                    }
                }
            }
            ######################################################################
            //pg_query($con,"ROLLBACK");
            ######################################################################

            echo "!!!!!!!!!!!!!! FIM FOREACH ITEM !!!!!!!!!!!!!!!!!!!\n";

            $sqlAtualizaPedido = "SELECT fn_atualiza_status_pedido($login_fabrica,  $pedido)";
            echo $sqlAtualizaPedido."\n";
            $atualizaPedido = pg_query($con,$sqlAtualizaPedido);

            $sqlUpdate = "UPDATE tbl_faturamento f SET total_nota = (SELECT sum(preco) FROM tbl_faturamento_item fi WHERE fi.faturamento = f.faturamento) 
                WHERE faturamento = $faturamento AND f.fabrica = $login_fabrica";
            echo $sqlUpdate."\n";
            pg_query($con,$sqlUpdate);
        } catch (\Exception $e) {
            $status = 2;
            $message = "Finalizado Com Alguns Erros";  

            $routineScheduleLogError = new LogError();
            $routineScheduleLogError->setRoutineScheduleLog($logId);
            $routineScheduleLogError->setErrorMessage($e->getMessage());
            $routineScheduleLogError->Insert();            
        }
    } catch (\Exception $ef) {
        $status = 0;
        $message = "Erro Ao Executar Rotina";  

        $routineScheduleLogError = new LogError();
        $routineScheduleLogError->setRoutineScheduleLog($logId);
        $routineScheduleLogError->setErrorMessage($ef->getMessage());
        $routineScheduleLogError->Insert();            
    }
    $log->setStatus($status);
    $log->setStatusMessage($message);
    $log->setRoutineScheduleLog($logId);
    $log->Update();
    echo "!!!!!!!!!!!!!!!!!!!!!!!! FIM FOREACH PEDIDO !!!!!!!!!!!!!!!!!!!!!!\n";
}
