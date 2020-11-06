<?php

/*
* Esse gera reabastecimento para posto que atende garantia e fora de gerantia
*/

//include dirname(__FILE__).'/../../dbconfig.php';
//include dirname(__FILE__).'/../../includes/dbconnect-inc.php';
require dirname(__FILE__).'/../funcoes.php';

use Posvenda\Fabrica;
use Posvenda\Pedido;
use Posvenda\Os;
use Posvenda\Fabricas\_158\ExportaPedido;
use Posvenda\Fabricas\_158\PedidoBonificacao;

try {

    /*
    * Definição
    */
    $fabrica = 158;
    $data = date('d-m-Y');

    $env = ($_serverEnvironment == 'development') ? "dev" : "producao";
    
    $param = "posto"; /* posto | os */

    /*
    * Log
    */
    $oLog = new Log2();
    $oLog->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Bonificação Garantia Imbera")); // Titulo
    if ($env == 'producao') {
	$oLog->adicionaEmail("vitorio.ferreira@imberacooling.com");
	$oLog->adicionaEmail("alysson.silva@imberacooling.com");
	$oLog->adicionaEmail("wagner.rodrigues@imberacooling.com");
    } else {
        $oLog->adicionaEmail("kaique.magalhaes@telecontrol.com.br");
    }

    /*
    * Cron
    */
    //$oPHPCron = new PHPCron($fabrica, __FILE__);
    //$oPHPCron->inicio();

    /*
    * Class Fábrica
    */
    $oFabrica = new Fabrica($fabrica);

    /*
    * Resgata o nome da Fabrica
    */
    $fabrica_nome = $oFabrica->getNome();

    /*
    * Resgata as peças as serem Bonificadas
    */
    $oOS = new Os($fabrica);
    $oPedido = new Pedido($fabrica);
    $oPedidoBonificacao = new PedidoBonificacao($oPedido);
    $oExportaPedido = new ExportaPedido($oPedido, $oOS, $fabrica);

    $pecas_bonificacao = $oPedidoBonificacao->verificaEstoqueBonificacaoGarantia($fabrica,$postos_bonificacao);
    
    /*
    * Array para exportação dos dados
    */
    $dadosExporta = array();

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();

    /*
    * Resgata a condição da Fabrica
    */
    $condicao = $oPedido->getCondicaoGarantia();

    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $oPedidoBonificacao->getTipoPedidoBonificadoGarantia($fabrica);

    foreach ($pecas_bonificacao as $posto => $value) {

        try {

            /*
            * Begin
            */
            $oPedido->_model->getPDO()->beginTransaction();

            /*
            * Busca tabela de preço para Bonificação em Garantia
            */
            $tabela = $oPedido->_model->getTabelaPreco($posto, $tipo_pedido);

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "fabrica"       => $fabrica,
                "status_pedido" => 1,
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            /*
            * Grava o Pedido
            */
            $oPedido->grava($dados, null);
            $pedido = $oPedido->getPedido();

            $value['status_pedido'] = $dados['status_pedido'];
            $value['data_pedido'] = date("Ymd");

            $dadosItens = array();

            /*
            * Grava Pedido Item
            */
            foreach ($value['pecas'] as $peca => $dados_peca) {

                unset($dadosItens);

                /*
                * Insere o Pedido Item
                */
                $preco = $oPedido->getPrecoPecaGarantiaAntecipada($peca,$posto,$tabela);
                $os_item = $dados_peca['os_item'];

                $dadosItens[] = array(
                    "pedido"            => (int)$pedido,
                    "peca"              => $peca,
                    "qtde"              => $dados_peca["qtde_pedido"],
                    "qtde_faturada"     => 0,
                    "qtde_cancelada"    => 0,
                    "preco"             => $preco,
                    "total_item"        => $preco * $dados_peca["qtde_pedido"]
                );

                $oPedido->gravaItem($dadosItens, $pedido);

                /*
                * Resgata o Pedido Item
                */
		$pedido_item = $oPedido->getPedidoItem();


		if (!empty($os_item)) {
			$oPedido->atualizaOsItemPedidoItem($os_item, (int)$pedido, (int)$pedido_item, $fabrica);
		}

            }

            $oPedido->finaliza($pedido);

            /*
             * Commit
	     */
	    $oPedido->_model->getPDO()->commit();
            #$oPedido->_model->getPDO()->rollBack();

            $dadosExporta = $oExportaPedido->getPedido(null, $pedido);
            $dadosExporta = $oPedidoBonificacao->organizaEstoque($dadosExporta);

        } catch (Exception $e) {
            $oPedido->_model->getPDO()->rollBack();
            $msg_erro[] = $e->getMessage();
            continue;
        }

    }

    /*
    * Exporta Pedido Bonificado Garantia
    */
    /*if (!empty($dadosExporta)) {
        $pedidos = $oExportaPedido->pedidoIntegracaoSemDeposito($dadosExporta);
    }*/

    if (!empty($msg_erro)) {
        $oLog->adicionaLog(implode("<br />", $msg_erro));

        $oLog->enviaEmails();

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

    //$oPHPCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
