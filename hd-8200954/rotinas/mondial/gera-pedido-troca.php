<?php

/*
* Includes
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

/*
* Definição
*/
date_default_timezone_set('America/Sao_Paulo');
$fabrica = 151;
$data = date('d-m-Y');

$env = "producao";
$os_argv = $argv[1];
/*
* A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
*/

$param = "posto"; /* posto | os */

/*
* Log
*/
$logClass = new Log2();
$logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Mondial Brasil")); // Titulo
if ($env == 'producao' ) {
    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
} else {
    $logClass->adicionaEmail("guilherme.curcio@telecontrol.com.br");
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
* Resgata as OSs em Garantia
*/
$osClass = new \Posvenda\Os($fabrica);

$osTroca = $osClass->getTrocaPosto($param, null, $os_argv);

if(empty($osTroca)){
    exit;
}

/*
* Mensagem de Erro
*/
$msg_erro = array();

$pedidoClass = new \Posvenda\Pedido($fabrica);

/*
* Resgata a condição da Fabrica
*/
$condicao = $pedidoClass->getCondicaoGarantia();


/*
* Resgata a condição da Fabrica
*/
$tipo_pedido = $pedidoClass->getTipoPedidoGarantia("GARANTIA - PRODUTO");

for ($i = 0; $i < count($osTroca); $i++) {
    $posto = $osTroca[$i]["posto"];

    $os_pedido_posto = $osClass->getOsTrocaPosto($posto, null, $os_argv);

    $dados = array(
        "posto"         => $posto,
        "tipo_pedido"   => $tipo_pedido,
        "condicao"      => $condicao,
        "fabrica"       => $fabrica,
        "status_pedido" => 1,
		"troca"			=> "'t'",
	"tabela" => $pedidoClass->_model->getTabelaId("08-01 H45D")
    );

    for ($x = 0; $x < count($os_pedido_posto); $x++) {

        try {
            $os = $os_pedido_posto[$x]["os"];

            if (empty($os)) {
                continue;
            }
		
	    $osClass = new \Posvenda\Os($fabrica, $os);

            $pedidoClass->_model->getPDO()->beginTransaction();
        /*
        * Grava o Pedido
	 */

	    $pedido_cliente = $osClass->verificaAtendimentoCallcenter($os);

	    if($pedido_cliente !== false){
		    $dados['pedido_cliente'] = $pedido_cliente;
	    }else{
		    unset($dados['pedido_cliente']);
	    }

            $pedidoClass->grava($dados);

            $pedido = $pedidoClass->getPedido();
            
            $dadosItens = array();
        
        /**
        * Pega as peças da OS
        */
            $verifica_tabela_alterada = $pedidoClass->verificaTabelaAlterada($os);

            $pecas = $osClass->getPecasPedidoGarantiaTroca($os);

            foreach ($pecas as $key => $peca) {

                unset($dadosItens);

            //Caso a linha tenha sido retirada do posto depois de ter aberto a os
            
            if (!empty($verifica_tabela_alterada)) { 
                $tabela_padrao_mondial = 847;
            }    
            /*
            * Insere o Pedido Item
            */

                $dadosItens[] = array(
                    "pedido"            => (int)$pedido,
                    "peca"              => $peca["peca"],
                    "qtde"              => $peca["qtde"],
                    "qtde_faturada"     => 0,
                    "qtde_cancelada"    => 0,
                    "preco"             => $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os, $tabela_padrao_mondial)
                );

                $pedidoClass->gravaItem($dadosItens, $pedido);

            /*
            * Resgata o Pedido Item
            */
                $pedido_item = $pedidoClass->getPedidoItem();

            /*
            * Atualiza os Pedidos Item na OS Item
            */
                $pedidoClass->atualizaOsItemPedidoItem($peca["os_item"], $pedido, $pedido_item, $fabrica);

            }

            $pedidoClass->finaliza($pedido);

            $pedidoClass->_model->getPDO()->commit();
        } catch(Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();

            $msg_erro[] = $e->getMessage();

            continue;
        }
    }
}

if(!empty($msg_erro)){
   if (isset($argv[1])) {
            echo json_encode(array("erro" => utf8_encode($msg_erro[0])));
    } else {
        $logClass->adicionaLog(implode("<br />", $msg_erro));

    	$logClass->enviaEmails();

        $fp = fopen("tmp/{$fabrica_nome}/pedidos/log-erro-gera-pedido-troca".date("d-m-Y_H-i-s").".txt", "a");
    	fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
    	fclose($fp);
   }
} else if (isset($argv[1])) {
    echo json_encode(array("sucesso" => "true"));
}

$phpCron->termino();
