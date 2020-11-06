<?php
include __DIR__.'/../../../dbconfig.php';
include __DIR__.'/../../../includes/dbconnect-inc.php';

header("access-control-allow-origin: *");

function envia_email($transactionss='',$log='')
{
    $headers = "MIME-Version: 1.1\r\n";
    $headers .= "Content-type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: felipe.marttos@telecontrol.com.br\r\n"; // remetente
    $headers .= "Return-Path: felipe.marttos@telecontrol.com.br\r\n"; // return-path

    $mensagem .= "<pre>".print_r($transactionss, 1)."</pre>";
    $mensagem .= "<hr>".$log;

    $envio = mail("felipemarttos@hotmail.com", "Notificação do PagSeguro", $mensagem, $headers);

    if($envio)
     return "Mensagem enviada com sucesso";
    else
     return "A mensagem não pode ser enviada";
}

if (isset($_GET["f"])) {
    list($nomeFab, $fabrica) = explode("_", base64_decode($_GET["f"]));
}

if (strlen($fabrica) == 0) {
    envia_email('', 'Fabrica nao encontrada: '.$nomeFab.' ->>');
    exit;
}

$sql = "SELECT tbl_loja_b2b_configuracao.pa_forma_pagamento
          FROM tbl_loja_b2b
          JOIN tbl_loja_b2b_configuracao USING(loja_b2b)
         WHERE tbl_loja_b2b.fabrica={$fabrica}";
$res = pg_query($con, $sql);
if (pg_last_error($con)) {
    envia_email('', 'Fabrica nao encontrada: '.$nomeFab.' ->>');
    exit;
}

$configLoja = pg_fetch_assoc($res);
$configLojaPagamento = json_decode($configLoja["pa_forma_pagamento"], 1);

if (isset($configLojaPagamento["meio"]["pagseguro"]) && $configLojaPagamento["meio"]["pagseguro"]["status"] == 1){

    $ambiente = $configLojaPagamento["meio"]["pagseguro"]["ambiente"];

    if ($ambiente == "sandbox") {
        $sandbox = true;
        $xmail          = $configLojaPagamento["meio"]["pagseguro"]["email_sandbox"];
        $xtoken         = $configLojaPagamento["meio"]["pagseguro"]["token_sandbox"];
    } else {
        $sandbox = false;
        $xmail          = $configLojaPagamento["meio"]["pagseguro"]["email_producao"];
        $xtoken         = $configLojaPagamento["meio"]["pagseguro"]["token_producao"];
    }

    $url = ($sandbox) ? 'https://ws.sandbox.pagseguro.uol.com.br/v2/transactions/notifications/' : 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/';

    $user = array(
        'email' => $xmail,
        'token' => $xtoken,
    );

    if (isset($_POST['notificationType']) && $_POST['notificationType'] == 'transaction') {

        $code = $_POST['notificationCode'];

        $data = array();

        foreach ($user as $k => $v) {
            $data[$k] = $v;
        }

        $data = http_build_query($data);

        $url = $url . $code . '?' . $data;

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $transaction= curl_exec($curl);

        if ($transaction === 'Unauthorized') {
            echo 'Erro! {{ SERVER }}';
            exit;
        }

        $transaction = simplexml_load_string($transaction);
        $transactions = json_encode($transaction);
        $transactionss = json_decode($transactions,1);

        $log = "";

        $sql = "SELECT pedido FROM tbl_loja_b2b_pagamento  WHERE pedido = ".$transactionss["reference"];
        $res = pg_query($con, $sql);

        if (pg_last_error($con)) {
            $log .= "Erro ao buscar pedido: ".pg_last_error($con)."\n\n\n\n";
        }

        if (pg_num_rows($res) > 0) {
            $log .= $sql."\n\n\n\n";
            $sqlAtualiza = "UPDATE tbl_loja_b2b_pagamento SET status_pagamento=".$transactionss["status"].", response='{$transactions}' WHERE pedido = ".$transactionss["reference"];
            $resAtualiza = pg_query($con, $sqlAtualiza);
            $log .= $sqlAtualiza."\n\n\n\n";
            if (pg_last_error($con)) {
                $log .= "Erro ao atualizar status: ".pg_last_error($con)."\n\n\n\n";
            }
        }
        envia_email($transactionss["reference"], $log);


    }

}

