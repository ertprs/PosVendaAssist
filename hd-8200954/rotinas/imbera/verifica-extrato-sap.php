<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__)."/../../classes/Posvenda/Extrato.php";
require dirname(__FILE__)."/../../classes/Posvenda/Fabricas/_158/IntegracaoExtrato.php";
/*
* Definições
*/
$fabrica        = 158;
$dia_mes        = date('d');
$dia_extrato    = date('Y-m-d H:i:s');

try {

    /*
    * Cron Class
    */
    $oPhpCron = new PHPCron($fabrica, __FILE__);
    $oPhpCron->inicio();

    /*
    * Log Class
    */
    $oLog = new Log2();
    $oLog->adicionaLog(array("titulo" => "Log erro Verificação de Extratos Liberados/Pagos no SAP - Imbera")); // Titulo
    $oLog->adicionaEmail("helpdesk@telecontrol.com.br");
    // $oLog->adicionaEmail("maicon.luiz@telecontrol.com.br");

    $oExtrato = new Extrato($fabrica);
    $oIntegracaoExtrato = new IntegracaoExtrato($fabrica);

    /*
    * Resgata os Extratos que foram enviados para o SAP
    */
    $extratos = $oIntegracaoExtrato->getExtratoSapByStatus();

    if(empty($extratos)){
      exit;
    }

    /*
    * Mensagem de Erro
    */
    $msg_erro = "";
    $msg_erro_arq = "";

    $link = urlSap(true);
    foreach($extratos as $extrato) {

    	try {
            /*
            * Begin
            */
            $oExtrato->_model->getPDO()->beginTransaction();

            if (!empty($extrato['autorizacao_pagto'])) {

                if (!empty($extrato['liberado'])) {
                    $funcao = "ConsultaPedPagto";
                } else {
                    $funcao = "ConsultaPed";
                }
	
		if ($this->_serverEnvironment == 'development') {

		    $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_{$funcao}_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

		    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

		} else {

		    $url = $link."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_{$funcao}_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

		    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

		}

		$xml_post_string = '
                    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
			<soapenv:Header/>
			<soapenv:Body>
			    <tel:MT_'.$funcao.'_Req>
				<I_PEDIDO>'.$extrato['autorizacao_pagto'].'</I_PEDIDO>
			    </tel:MT_'.$funcao.'_Req>
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

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$retornoCurl = curl_exec($ch);
		fwrite($file, 'Error Curl: '.$erroCurl.'\n\r');
		fwrite($file, 'Http Code: '.$httpcode.'\n\r');
                curl_close($ch);

		$retornoCurl = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$retornoCurl);

		$retornoXML = new \SimpleXMLElement(utf8_encode($retornoCurl));
		$retornoXML = $retornoXML->xpath('//RETURN');
		$retornoSoap = json_decode(json_encode((array) $retornoXML), true);
		$retornoSoap = $retornoSoap[0];

		if ($this->_serverEnvironment == "development") {
		    $file = fopen('/tmp/imbera-ws.log','a');
                } else {
                    $file = fopen('/mnt/webuploads/imbera/logs/imbera-ws.log','a');
		}

		fwrite($file, 'Resquest \n\r');
		fwrite($file, $url);
                fwrite($file, $xml_post_string);

		fwrite($file, 'Response \n\r');
		fwrite($file, 'Error Curl: '.$erroCurl.'\n\r');
		fwrite($file, 'Http Code: '.$httpcode.'\n\r');
                fwrite($file, utf8_decode($retornoCurl));
                fclose($file);

                if (!empty($extrato['liberado'])) {

                    $retorno = (array) $retornoSoap["RETURN"];

                    if ($retorno['TYPE'] == 'S') {

                        $sql = "UPDATE tbl_extrato_pagamento SET data_pagamento = now() WHERE extrato = :extrato;";
                        $query = $oExtrato->_model->getPDO()->prepare($sql);
                        $query->bindParam(':extrato', $extrato['extrato'], \PDO::PARAM_INT);
                        if (!$query->execute()) {
                            throw new Exception("Erro ao atualizar informações de pagamento do extrato");
                        }

                    }

                } else {

                    if ($retornoSoap['STATUS'] == 'S') {

                        $sql = "UPDATE tbl_extrato SET liberado = now() WHERE extrato = :extrato;";
                        $query = $oExtrato->_model->getPDO()->prepare($sql);
                        $query->bindParam(':extrato', $extrato['extrato'], \PDO::PARAM_INT);
                        if (!$query->execute()) {
                            throw new Exception("Erro ao efetuar liberação do extrato");
                        }

                    }
                    
                }

            }
        
            $oExtrato->_model->getPDO()->commit();
    	} catch (Exception $e){
            $msg_erro .= $e->getMessage()."<br />";
            $msg_erro_arq .= $msg_erro . " - SQL: " . $oExtrato->getErro();

            /*
            * Rollback
            */
            $oExtrato->_model->getPDO()->rollBack();
    	}
    }

    /*
    * Erro
    */
    if(!empty($msg_erro)){

        $oLog->adicionaLog($msg_erro);

        if ($oLog->enviaEmails() == "200") {
            echo "Log de erro enviado com Sucesso!";
        } else {
            echo $oLog->enviaEmails();
        }

        $fp = fopen("tmp/{$fabrica_nome}/extratos/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, $msg_erro_arq . "\n \n");
        fclose($fp);

    }

    /*
    * Cron Término
    */
    $phpCron->termino();

} catch (Exception $e) {

    echo $e->getMessage();

}
