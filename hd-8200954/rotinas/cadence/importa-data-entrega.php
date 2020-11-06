<?php
//ini_set("display_errors", "1");
//error_reporting(E_ALL);
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../funcoes.php';
$msg_log_erro    = array();
$msg_log_sucesso = array();
$fabrica_nome    = "Cadence";
$fabrica         = 35;
$origem          = '/var/www/cgi-bin/cadence/entrada/';
$pathCompleto    = $origem.'faturamento_entrega.txt';
$i               = 0;

if (!file_exists($pathCompleto)) {
    echo "Arquivo não existe.";exit;
}

$conteudo = file_get_contents($pathCompleto);
$conteudo_array = explode("\n", $conteudo);

foreach ($conteudo_array as $linha) {

    $linha = trim($linha);
    if (empty($linha)) {
        continue;
    }

    list($cnpj, $razao, $pedido, $nota_fiscal, $serie, $referencia, $data_entrega) = explode("\t", $linha);

    $cnpj = limpa_cnpj($cnpj);
    $data_entrega = trata_data($data_entrega);

    if (!verificaCpfCnpj($cnpj)) {
        $msg_log_erro[$i]["status"] =  "CNPJ inválido";
        $msg_log_erro[$i]["cnpj"]   =  "CNPJ: {$cnpj}";
    }

    $sql = "SELECT nome, posto FROM tbl_posto WHERE cnpj = '$cnpj';";
    $res = pg_query($con,$sql);


    if (pg_last_error($con)) {
        $msg_log_erro[$i]["status"] =  "Erro ao executar a select de posto";
        $msg_log_erro[$i]["cnpj"]   =  "CNPJ: {$cnpj}";
    }
    
    if (pg_num_rows($res) == 0) {
        $msg_log_erro[$i]["status"] =  "Posto não encontrato";
        $msg_log_erro[$i]["cnpj"]   =  "CNPJ: {$cnpj}";
    } else {
        $posto     = pg_fetch_result($res, 0, posto);
        $nomeposto = pg_fetch_result($res, 0, nome);

        $sqlFat = "SELECT faturamento FROM tbl_faturamento 
                   WHERE fabrica = $fabrica 
                     AND posto = $posto 
                     AND nota_fiscal = '$nota_fiscal' 
                     AND serie = '$serie'";

        $resFat = pg_query($con, $sqlFat);

        if (pg_last_error($con) || pg_num_rows($resFat) == 0) {

            $msg_log_erro[$i]["status"] = "Faturamento não encontrado";
            $msg_log_erro[$i]["cnpj"]   = "Posto: {$cnpj} - {$nomeposto} - Nota Fiscal:{$nota_fiscal} - Serie: {$serie} - Data Entrega: {$data_entrega}";

        } else {
            $faturamento = pg_fetch_result($resFat, 0, faturamento);

            $resi  = pg_query($con,"BEGIN TRANSACTION");

            $sqlUp = "UPDATE tbl_faturamento 
                         SET previsao_chegada = '$data_entrega' 
                       WHERE fabrica = $fabrica 
                         AND posto = $posto 
                         AND faturamento = $faturamento";

            $resUp = pg_query($con, $sqlUp);

			$sqlUp = "UPDATE tbl_os
                         SET status_checkpoint = fn_os_status_checkpoint_os(os)
                       WHERE fabrica = $fabrica 
                         AND os in (select p.os from tbl_faturamento_item join tbl_os_item using(pedido,pedido_item) join tbl_os_produto p using(os_produto) where faturamento =$faturamento) ";

            $resUp = pg_query($con, $sqlUp);
            if (pg_last_error($con)) {

                $msg_log_erro[$i]["status"] = "Erro ao fazer update na tbl_faturamento -> Erro: ".pg_last_error($con);
                $msg_log_erro[$i]["cnpj"]   = "CNPJ: {$cnpj} -  Posto: {$posto} - {$nomeposto}";

            } else {

                $msg_log_sucesso[$i]["status"] =  "Atualizado";
                $msg_log_sucesso[$i]["cnpj"]   =  "$cnpj - $posto - $nomeposto";

            }

            if (count($msg_log_erro) == 0) {
                $resi = pg_query($con,"COMMIT TRANSACTION");
            } else {
                $resi = pg_query($con,"ROLLBACK TRANSACTION");
            }
        }
    }
    $i++;
}

fclose($file);

function limpa_cnpj($cnpj) {
    $cnpj_limpo = str_replace(array('/','-','.', ' '), "", $cnpj);
    return $cnpj_limpo;
}

function trata_data($data) {

    $dataBR = explode("/", $data);
    if (count($dataBR) == 3) {
        return $dataBR[2].'-'.$dataBR[1].'-'.$dataBR[0];
    }
    $data3 = explode("-", $data);
    if (count($data3) == 3) {
        if (strlen($data3[0]) == 2) {
            return $data3[2].'-'.$data3[1].'-'.$data3[0];
        } else {
            return $data;
        }
    }
    return $data;
}

$logClass = new Log2();
$logClass->adicionaEmail("ronald.santos@telecontrol.com.br");

$logClass2 = new Log2();
$logClass2->adicionaEmail("ronald.santos@telecontrol.com.br");


if (count($msg_log_erro) > 0) {
    $conteudo_erro = "<table border='1' style='border-color:#D90000' cellpadding='0' cellspacing='0' width='100%'>
                        <thead>
                            <tr bgcolor='#D90000'>
                                <th align='center' style='padding:3px;color:#ffffff'>
                                    CNPJ
                                </th>
                                <th align='center' style='padding:3px;color:#ffffff'>
                                    STATUS
                                </th>
                            </tr>
                        </thead>";
                        foreach ($msg_log_erro as $k => $rows) {
   $conteudo_erro .= "<tr>
                            <td style='padding:3px;'>".$rows["cnpj"]."</td>
                            <td style='padding:3px;'>".$rows["status"]."</td>
                        </tr>";
                        }
   $conteudo_erro .= "</table>";

    $logClass->adicionaLog(array("titulo" => "Log de Erro [$fabrica_nome] - Importação de Data de Entrega")); // Titulo
    $logClass->adicionaLog($conteudo_erro);

    if ($logClass->enviaEmails() == "200") {
        echo "Log de Erro enviado com Sucesso!";
    } else {
        $logClass->enviaEmails();
    }
} 

if (count($msg_log_sucesso) > 0) {
    $conteudo_sucesso = "<table border='1' style='border-color:green' cellpadding='0' cellspacing='0' width='100%'>
                        <thead>
                            <tr bgcolor='green'>
                                <th align='center' style='padding:3px;color:#ffffff'>
                                    CNPJ
                                </th>
                                <th align='center' style='padding:3px;color:#ffffff'>
                                    STATUS
                                </th>
                            </tr>
                        </thead>";
                        foreach ($msg_log_sucesso as $k => $rows) {
   $conteudo_sucesso .= "<tr>
                            <td style='padding:3px;'>".$rows["cnpj"]."</td>
                            <td style='padding:3px;'>".$rows["status"]."</td>
                        </tr>";
                        }
   $conteudo_sucesso .= "</table>";

    $logClass2->adicionaLog(array("titulo" => "[$fabrica_nome] - Importação de Data de Entrega")); // Titulo
    $logClass2->adicionaLog($conteudo_sucesso);

    if ($logClass2->enviaEmails() == "200") {
        echo "Log enviado com Sucesso!";
    } else {
        $logClass2->enviaEmails();
    }

}
