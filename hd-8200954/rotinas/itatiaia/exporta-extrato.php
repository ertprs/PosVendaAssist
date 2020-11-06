<?php
$fabrica_nome = "itatiaia";
define('APP', 'Exporta Pedido - '.$fabrica_nome);

$vet['fabrica'] = 'itatiaia';
$vet['tipo']    = 'exporta-extrato';
$vet['dest']    = array('ronald.santos@telecontrol.com.br');
$vet['log']     = 1;

function exportar_dados($dados) {
    global $_serverEnvironment;

    if ($_serverEnvironment == "development") {
        $curlUrl = "https://piqas.cozinhasitatiaia.com.br/RESTAdapter/ReceberPedidoCompra";
        $curlPass = "aXRhYWJhcDpBYmFwMjAxOA==";
    } else {
        $curlUrl = "https://pi.cozinhasitatiaia.com.br/RESTAdapter/ReceberPedidoCompra";
        $curlPass = "UElTVVBFUjppdGExMjM0NQ==";
    }
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $curlUrl, 
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic {$curlPass}",
            "Content-Type: application/json"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    $response = json_decode($response,1);
    if (!empty($response)){
        $error = $response["MT_Telecontrol_BuscarSerial_response"]["Produto"]["DescRetorno"];

        if (!empty($error)){
            echo json_encode(['message' => 'error']);
        }else{
            echo json_encode(['message' => 'success']);
        }
    }
    
    curl_close($curl);
    return $response;
}

try {
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/tdocs.class.php';


    $log_dir = '/tmp/' . $fabrica_nome . '/logs';
    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. falha ao criar diretório: $log_dir");
        }
    }

    $login_fabrica = 183;
    
    /* CONFIG LOG ERROR */
        unset($log_erro);
        $manda_email = false;
        $now = date('Ymd_His');
        $err_log = $log_dir . '/exporta-extrato-error-'. $now . '.log';
	$json_log = $log_dir . '/exporta-extrato-json-log-'. $now . '.log';
        $log_erro[] = " ==== LOG ERRO INÍCIO: ".date("H:i")." ==== ";
    /* FIM CONFIG LOG ERROR */
    
    $tdocsClass = new TDocs($con, 183, 'extrato');


    $sql = "
        SELECT 
            tbl_extrato.extrato,
            tbl_extrato.mao_de_obra,
            tbl_extrato.avulso,
            tbl_extrato.valor_adicional,
            tbl_extrato.deslocamento,
            tbl_posto_fabrica.conta_contabil AS codigo_fornecedor
        FROM tbl_extrato
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        WHERE tbl_extrato.fabrica = {$login_fabrica}
        AND tbl_extrato.exportado IS NULL";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0){

        for ($i=0; $i < pg_num_rows($res); $i++) {

            $error = false;

            $array_dados = array();
            $array_info = array();
            $array_img = array();

            $extrato            = pg_fetch_result($res, $i, "extrato");
            $codigo_fornecedor  = pg_fetch_result($res, $i, "codigo_fornecedor");
            $mao_de_obra        = pg_fetch_result($res, $i, "mao_de_obra");
            $valor_adicional    = pg_fetch_result($res, $i, "valor_adicional");
            $deslocamento       = pg_fetch_result($res, $i, "deslocamento");
            $avulso             = pg_fetch_result($res, $i, "avulso");

            $sql_tdocs = "SELECT tdocs_id FROM tbl_tdocs WHERE fabrica = {$login_fabrica} AND contexto = 'extrato' AND situacao = 'ativo' AND referencia_id = $extrato ";
            $res_tdocs = pg_query($con, $sql_tdocs);

            if (pg_num_rows($res_tdocs) > 0){
                for ($x=0; $x < pg_num_rows($res_tdocs); $x++) { 
                    $tdocs_id = pg_fetch_result($res_tdocs, $x, "tdocs_id");
                    $tdocs_url = $tdocsClass->getUrl($tdocs_id);
                    $img = file_get_contents($tdocs_url); 
                    $data = base64_encode($img); 
                    $array_img[] = $data;
                }
            }
            
            if (strlen(trim($avulso)) > 0){            
                $avulso = explode("-", $avulso);

                if (!empty($deslocamento)){
                    if (strlen(trim($avulso[1])) > 0){
                        $deslocamento = ($deslocamento-$avulso[1]);
                    }else{
                        $deslocamento = ($deslocamento+$avulso[0]);
                    }

                    if ($deslocamento < 0){
                        $deslocamento = explode("-", $deslocamento);
                        $mao_de_obra = ($mao_de_obra - $deslocamento[1]);
                        $deslocamento = 0;
                    }
                }else{
                    if (strlen(trim($avulso[1])) > 0){
                        $mao_de_obra = ($mao_de_obra-$avulso[1]);
                    }else{
                        $mao_de_obra = ($mao_de_obra+$avulso[0]);
                    }
                }
            }

            $sqlNfeVisualizada = "
                SELECT 
                    ultima_obs.obs,
                    TO_CHAR(ultima_obs.data, 'DD/MM/YYYY HH24:MI') as data_conferencia
                FROM (
                    SELECT obs, data
                    FROM tbl_extrato_status 
                    WHERE fabrica = {$login_fabrica}
                    AND extrato = {$extrato}
                    ORDER BY data DESC
                    LIMIT 1
                ) ultima_obs
                WHERE ultima_obs.obs = 'Nota Fiscal Aprovada'";
            $resNfeVisualizada = pg_query($con, $sqlNfeVisualizada);

            if (pg_num_rows($resNfeVisualizada) == 0){
                continue;
            }
            
	    if (empty($codigo_fornecedor)){
                $log_erro[] = "LOG PEDIDO {$extrato} - EXTRATO SEM O CÓDIGO DE FORNECEDOR";
                $error = true;
            }

            if (!empty($mao_de_obra)){
                $mao_de_obra = round($mao_de_obra);
            
                $array_info[] = array(
                    //MAO DE OBRA
                    "CategClasContabil" => "K",
                    "CategItem" => "D",
                    "Material" => "",
                    "DescItem" => "Mão de Obra REF Lote: $extrato",
                    "GrupoMercad" => "1016",
                    "Centro" => "1040",
                    "IVA" => "SC",
                    "ItemServico" => array(
                            array(
                                "CodiServico" => "3000083",
                                "QtdeServico" => "1",
                                "Preco" => $mao_de_obra,
                                "CentroCusto" => "310102"
                            )
                        )
                );
            }
            // Conta razao alterada de 43005919 para 43005971 dia 24/07/2020 solicitado pelo rafael Itatiaia
            if (!empty($deslocamento)){
                $deslocamento = number_format($deslocamento, 2, '.', '');
            
                $array_info[] = array(
                    "CategClasContabil" => "K",
                    "CategItem" => "",
                    "Material" => "000000004500000020",
                    "DescItem" => "",
                    "GrupoMercad" => "1021",
                    "Centro" => "1040",
                    "IVA" => "C0",
                    "ItemMaterial" => array(
                        "ContaRazao" => "43005971",
                        "PrecoMaterial" => "$deslocamento",
                        "CentroCusto" => "310102"
                    )
                );
            }
            
            if (!empty($valor_adicional)){
                
                $array_info[] = array(
                    "CategClasContabil" => "K",
                    "CategItem" => "",
                    "Material" => "000000004500000020",
                    "DescItem" => "",
                    "GrupoMercad" => "1021",
                    "Centro" => "1040",
                    "IVA" => "C0",
                    "ItemMaterial" => array(
                        "ContaRazao" => "43005971",
                        "PrecoMaterial" => "$valor_adicional",
                        "CentroCusto" => "310102"
                    )
                );
            }        
            
            $array_dados["PedidoCompra"][] = array(
                "TipDocto" => "ZNB",
                "CodiFornecedor" => $codigo_fornecedor,
                "CondiPagamento" => "M113",
                "Incoterms" => "CIF",
                "TextIncoterms" => "CUSTO, SEGURO e FRETE",
                "OrgCompras" => "2000",
                "GrupoCompradores" => "100",
                "Empresa" => "1000",
                "ItemPedido" => $array_info,
                "arquivo" => $array_img
            );
	
	    $log_json[] = json_encode($array_dados);		

            $retorno = exportar_dados($array_dados);

            $retorno_msg = $retorno["MT_RecPedidoCompra_Resp"]["Registro"][0]["StatPedido"];

            if ($retorno_msg == "Pedido criado"){
                $sql_up = "UPDATE tbl_extrato SET exportado = now() WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
                $res_up = pg_query($con, $sql_up);
            }else{
                $requisicao = json_encode($array_dados);
                $msg_erro = $retorno["MT_RecPedidoCompra_Resp"]["Registro"][0]["StatPedido"];
                $log_erro[] = "LOG PEDIDO {$pedido} - ERRO NA INTEGRAÇÃO ENTRE SAP/TELECONTROL MSG: {$msg_erro} - REQUISIÇÃO - $requisicao";
                $error = true;
            }
        }

        if ($error === true){
            $elog = fopen($err_log, "w");
            $dados_log_erro = implode("\n", $log_erro);
            fwrite($elog, $dados_log_erro);
            fclose($elog);
            $manda_email = true;
        }

        if ($manda_email === true){

            require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
            $assunto = ucfirst($fabrica_nome) . utf8_decode(': Exportação de extrato ') . date('d/m/Y');
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            $mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
            $mail->Subject = $assunto;
            $mail->Body = "Segue anexo arquivo de log erro na rotina...<br/><br/>";
            
            $mail->AddAttachment("$log_dir/exporta-extrato-error-$now.log");
	    $mail->send();
        }
	
	if (count($log_json) > 0) {
            $elog_json = fopen($json_log, "w");
            $dados_log_json = implode("\n", $log_json);
            fwrite($elog_json, $dados_log_json);
            fclose($elog_json);

            require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';
            $assunto = ucfirst($fabrica_nome) . utf8_decode(': Exportação de extrato Log Json') . date('d/m/Y');
            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            $mail->AddAddress('guilherme.monteiro@telecontrol.com.br');
            $mail->Subject = $assunto;
            $mail->Body = "Segue anexo arquivo de log json na rotina...<br/><br/>";
            
            $mail->AddAttachment("$log_dir/exporta-extrato-json-log-$now.log");
            $mail->send();
        }

    }
    

} catch (Exception $e) {
    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    echo $msg;
    Log::envia_email($vet, APP, $msg);
}

