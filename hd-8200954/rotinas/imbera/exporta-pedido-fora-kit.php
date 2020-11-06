<?php

try {

    /*
    * Includes
    */

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_158/ExportaPedido.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_158/PedidoBonificacao.php';

    /*
    * Definição
    */
    $fabrica = 158;
    $data = date('d-m-Y');

    $env = ($_serverEnvironment == 'development') ? "dev" : "producao";
    
    $param = "os"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Imbera")); // Titulo
    if ($env == 'producao') {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("maicon.luiz@telecontrol.com.br");
    }

    /*
    * Cron
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Class Fábrica
    */
    $fabricaClass = new \Posvenda\Fabrica($fabrica);

    /*
    * Resgata o nome da Fabrica
    */
    $fabrica_nome = $fabricaClass->getNome();

    /*
    * Instância as Classes que irão ser utilizadas
    */
    $osClass = new \Posvenda\Os($fabrica);
    $pedidoClass = new \Posvenda\Pedido($fabrica, null, $param);
    $exportaPedidoClass = new \Posvenda\Fabricas\_158\ExportaPedido($pedidoClass, $osClass, $fabrica);
    $oPedidoBonificacao = new \Posvenda\Fabricas\_158\PedidoBonificacao($pedidoClass);

    /*
    * Resgata os Pedidos aguardando exportação
    */
    $pedidos = $exportaPedidoClass->getPedidoNTP($fabrica, null ,1);
    $pedidos = $oPedidoBonificacao->organizaEstoque($pedidos);
    
    if (empty($pedidos)) {
        exit;
    }

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();

    foreach($pedidos as $os => $value) {
        try {
            
	    $idPedido = key($value);

	    /*
            * Se Posto tiver Depósito (ForaKit/Piso) senão (CriaOrdemVenda)
            */
	    if (strtotime("today") > strtotime("2017-11-30 00:00:00")) {
	            $exportaPedidoClass->pedidoIntegracaoSemDeposito($pedidos[$os]);
	    } else {
            	if (!empty($value[$idPedido]['centro_custo'])) {
                	$exportaPedidoClass->pedidoIntegracao($pedidos[$os]);
	        } else {
        	        $exportaPedidoClass->pedidoIntegracaoSemDeposito($pedidos[$os]);
	        }
	    }

        } catch (Exception $e) {
            $msg_erro[] = $idPedido.': '.$e->getMessage();
            continue;
        }
    }

    if (!empty($msg_erro)) {
        $logClass->adicionaLog(implode("<br />", $msg_erro));

        $logClass->enviaEmails();

        $nome_fabrica = strtolower($fabrica_nome);
        $logError = "./{$nome_fabrica}-";

        if (!is_dir("/tmp/{$nome_fabrica}/pedidos")) {
            if (mkdir("/tmp/{$nome_fabrica}/pedidos")) {
                $logError = "/tmp/{$nome_fabrica}/pedidos/";
            }
        } else {
            $logError = "/tmp/{$nome_fabrica}/pedidos/";
        }

        $fp = fopen("{$logError}log-erro".date("d-m-Y_H-i-s").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);
    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
