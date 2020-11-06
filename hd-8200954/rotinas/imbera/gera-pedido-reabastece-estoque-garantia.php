<?php

/*
* Esse gera reabastecimento para posto que atende somente garantia
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
    $oLog->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Bonificação Garantia Estoque Mínimo Imbera")); // Titulo
    if ($env == 'producao') {
	    $oLog->adicionaEmail("ingrid.dealmeida@imberacooling.com");
	    $oLog->adicionaEmail("viviane.franco@imberacooling.com");
	    $oLog->adicionaEmail("bruna.ferreira@imberacooling.com");
	    $oLog->adicionaEmail("alexandre.assis@imberacooling.com");
        $oLog->adicionaEmail("alan.desouza@imberacooling.com");
        $oLog->adicionaEmail("amanda.dasilva@imberacooling.com");
        $oLog->adicionaEmail("wagner.rodrigues@imberacooling.com");
        $oLog->adicionaEmail("kaique.magalhaes@telecontrol.com.br");
        $oLog->adicionaEmail("carlos.albertosantos@imberacooling.com");
	$oLog->adicionaEmail("douglas.rosa@imberacooling.com");
	$oLog->adicionaEmail("alex.silva@imberacooling.com");
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

    /*
        Kaique

        Alterada a rotina para ser gerada manualmente pela tela admin/pedido_gera_manual.php
        onde é passado o array de postos selecionados para a variável $postos_bonificacao 
    */

    $pecas_bonificacao = $oPedidoBonificacao->verificaEstoqueBonificacaoMinimo($fabrica,$postos_bonificacao);


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
                "status_pedido" => 1
            );

            /*i
            * Grava o Pedido
	     */
            $oPedido->grava($dados, null);
            $pedido = $oPedido->getPedido();

            $value['status_pedido'] = $dados['status_pedido'];
	    $value['data_pedido'] = date("Ymd");

            $dadosItens      = array();
            $pecas_sem_preco = "";

            /*
            * Grava Pedido Item
            */
            foreach ($value['pecas'] as $peca => $dados_peca) {

                unset($dadosItens);

                /*
                * Insere o Pedido Item
                */
                $preco = $oPedido->getPrecoPecaGarantiaAntecipada($peca,$posto,$tabela,true);

                if (empty($preco)) {
                    $dadosPeca = $oPedido->retornaDadosPeca($peca);

                    $pecas_sem_preco .= "A peça ".$dadosPeca['referencia']." - ".$dadosPeca['descricao']." está sem preço <br />";
                } else {

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
            			$this->pedido->atualizaOsItemPedidoItem($os_item, (int)$pedido, (int)$pedido_item, $fabrica);
            		}

                }

	    }

        if (!empty($pecas_sem_preco)) {
            throw new \Exception($pecas_sem_preco);
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
	    $msg_erro = array_unique($msg_erro);
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
