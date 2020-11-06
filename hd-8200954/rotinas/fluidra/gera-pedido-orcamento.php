<?php

try {
    error_reporting(0);
    /*
    * Includes
    */

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_191/Os.php';



    /*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 191;
    $data = date('d-m-Y');

    function getEstadoPosto($pedido){
        global $con,$fabrica;
        $sql = "SELECT tbl_posto_fabrica.contato_estado
                FROM tbl_pedido
                INNER JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
                WHERE tbl_pedido.pedido = {$pedido}";
        $res = pg_query($con,$sql);
        if (pg_num_rows($res) > 0) {

            $dados = pg_fetch_assoc($res);
            return $dados['contato_estado'];
        }
        return '';

    }

    function montaEmail($pedido){
        global $con,$fabrica;

        $sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_pedido_item.qtde
                FROM tbl_pedido_item
                INNER JOIN tbl_peca USING(peca)
                WHERE pedido = {$pedido}";
        $res = pg_query($con,$sql);

        $dados = pg_fetch_all($res);

        $retorno = "<table cellspacing='0' cellpadding='0'>
                        <tr>
                            <td style='border:solid 1px;font-weight:bold;'>Referência</td>
                            <td style='border:solid 1px;font-weight:bold;'>Descrição</td>
                            <td style='border:solid 1px;font-weight:bold;'>Qtde</td>
                        </tr>";

        foreach ($dados as $key => $value) {
            $retorno .= "<tr>
                            <td style='border:solid 1px;'>{$value['referencia']}</td>
                            <td style='border:solid 1px;'>{$value['descricao']}</td>
                            <td align='center' style='border:solid 1px;'>{$value['qtde']}</td>
                          </tr>";
        }
        $retorno .= "</table>";

        return $retorno;
    }

    $env = "teste";

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "os"; /* posto | os */

    /*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Orçamento Fluidra")); // Titulo
    if ($env == 'producao' ) {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("felipe.marttos@telecontrol.com.br");
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
    $osOrcamentoFL = new \Posvenda\Fabricas\_191\Os($fabrica);
    $os_garantia = $osOrcamentoFL->getOsOrcamento();
    if(empty($os_garantia)){
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
    $condicao = $osOrcamentoFL->getCondicaoBoleto();

    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $osOrcamentoFL->getTipoPedidoOrcamento();
    $tabela = $osOrcamentoFL->getTabelaVenda();

    for ($i = 0; $i < count($os_garantia); $i++) {

        try {

            $posto = $os_garantia[$i]["posto"];

            /*
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();
            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => "'".$condicao."'",
                "fabrica"       => $fabrica,
                "status_pedido" => 1,
		      "obs"		=> "'Total de OSs: ".count($os_garantia)."'",
                "finalizado"    => "'".date("Y-m-d H:i:s")."'"
            );
            /*
            * Grava o Pedido
            */
            $pedidoClass->grava($dados);

            $pedido = $pedidoClass->getPedido();

            /* Pedido por Posto */

            $os = $os_garantia[$i]["os"];

            $dadosItens = array();

            /**
             * Pega as peças da OS
             */
            $osClass = new \Posvenda\Os($fabrica, $os);

            $pecas = $osOrcamentoFL->getPecasPedidoOrcamento($os);
            
            foreach ($pecas as $key => $peca) {

                unset($dadosItens);

                /*
                * Insere o Pedido Item
                */
	             $preco = $pedidoClass->verificaPreco($peca["peca"], $tabela);

                $dadosItens[] = array(
                    "pedido"            => (int)$pedido,
                    "peca"              => $peca["peca"],
                    "qtde"              => $peca["qtde"],
                    "qtde_faturada"     => 0,
                    "qtde_cancelada"    => 0,
                    "preco"             => $preco,
	                 "total_item"        => $preco * $peca["qtde"]
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

            /*
            * Commit
            */
            $pedidoClass->_model->getPDO()->commit();


            $estado_posto = getEstadoPosto($pedido);

            if (strlen($estado_posto) > 0) {

                $logClassX = new Log2();
                $logClassX->adicionaLog(array("titulo" => " Pedido Nº {$pedido}, para separação de peças"));

/*
                if ($estado_posto == 'SC') {
                    $logClassX->adicionaEmail("cmelo@fluidra.com.br");
                    $logClassX->adicionaEmail("mcruz@fluidra.com.br");
                    $logClassX->adicionaEmail("cc_assistencia@fluidra.com.br");
                }
                if ($estado_posto == 'SP') {
                    $logClassX->adicionaEmail("eruiz@fluidra.com.br");
                }
*/
		$logClassX->adicionaEmail("flavio.zequin@telecontrol.com.br");
		$logClass->adicionaTituloEmail(array("Solicitação de transferência de peças do M1 para o 31"));
                $email = montaEmail($pedido);
                $logClassX->adicionaLog($email);
                $logClassX->enviaEmails();
            }

        } catch(Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();

            $msg_erro[] = $e->getMessage();

            continue;
        }
    }

    if(!empty($msg_erro)){
        $logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();

        $fp = fopen("/tmp/{$fabrica_nome}/pedidos/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
