<?php

exit("nao usa para nada");

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_158/PedidoBonificacao.php';

$fabrica = 158;

$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();

$logClass = new Log2();
$logClass->adicionaLog(array("titulo" => "Log erro Abastecimento de Estoque - Imbera"));
$logClass->adicionaEmail("francisco.ambrozio@telecontrol.com.br");
$msg_erro = '';

$pedidoClass = new \Posvenda\Pedido($fabrica);

/* Seleciona os postos que controlam estoque */
/* passa como parametro true para trazer os postos que controlam estoque e false para os que não controlam */
$postos_controlam_estoque = $pedidoClass->getPostosControlamEstoque(true);

if ($postos_controlam_estoque != false) {

    foreach ($postos_controlam_estoque as $value) {
        $posto = $value["posto"];

        $pedidoBonificacao = new \Posvenda\Fabricas\_158\PedidoBonificacao($pedidoClass);
        if ($pedidoBonificacao->isPostoProprio($value['tipo_posto'])) {
            continue;
        }

        $acao = $pedidoBonificacao->abasteceEstoque($posto,$fabrica);

        if ($acao['status'] == 'error') {
            $msg_erro .= $acao['msg'];
        }
    }
}

if (!empty($msg_erro)) {
    $logClass->adicionaLog($msg_erro);

    if ($logClass->enviaEmails() == "200") {
        echo "Log de erro enviado com Sucesso!";
    } else {
        echo $logClass->enviaEmails();
    }

    $nome_fabrica = 'imbera';
    $logError = "./{$nome_fabrica}-";

    if (!is_dir("/tmp/{$nome_fabrica}/pedidos")) {
        if (mkdir("/tmp/{$nome_fabrica}/pedidos")) {
            $logError = "/tmp/{$nome_fabrica}/pedidos/";
        }
    } else {
        $logError = "/tmp/{$nome_fabrica}/pedidos/";
    }

    $fp = fopen("{$logError}log-erro.txt", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $msg_erro . "\n \n");
    fclose($fp);

}

$phpCron->termino();
