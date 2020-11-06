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

    /*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 182;
    $data = date('d-m-Y');

    $env = "producao";

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */

    $param = "os"; /* posto | os */

    /* 
    * Log 
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos ESAB Peru")); // Titulo
    if ($env == 'producao' ) {
	    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
	    $logClass->adicionaEmail("filipe.souza@esab.com.br");
    } else {
        $logClass->adicionaEmail("rafael.macedo@telecontrol.com.br");
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
    $os_garantia = $osClass->getOsGarantia($param);
    // print_r($os_garantia);var_dump($os_garantia);exit;
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
    $condicao = $pedidoClass->getCondicaoGarantia();

    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia();

    $previsao_entrega = date('Y-m-d',strtotime('+7 days',strtotime(date('Y-m-d'))));
    $array_posto = 0;

    for ($i = 0; $i < count($os_garantia); $i++) {
        $comunicado = "<ul>";
        try {
    
            $posto = $os_garantia[$i]["posto"];

            /*
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();

            $dados = array(
                "posto"            => $posto,
                "tipo_pedido"      => $tipo_pedido,
                "condicao"         => $condicao,
                "fabrica"          => $fabrica,
                "previsao_entrega" => "'".$previsao_entrega."'",
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            /*
            * Grava o Pedido
            */
            $pedidoClass->grava($dados);

            $pedido = $pedidoClass->getPedido();

            $comunicado .= "<li>Pedido: ".$pedido." - Previsão de faturamento: ".date("d/m/Y",strtotime($previsao_entrega))."</li>";
            
            if(empty($param) or $param == "os"){

                $os = $os_garantia[$i]["os"];
                $dadosItens = array();
                
                /**
                 * Pega as peças da OS
                 */
                $osClass = new \Posvenda\Os($fabrica, $os);

                $pecas = $osClass->getPecasPedidoGarantia();

                foreach ($pecas as $key => $peca) {

                    unset($dadosItens);

                    /*
                    * Insere o Pedido Item
                    */
                    $preco = $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os);
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

                    $array_posto[$posto][$pedido][] = $peca["peca"];
                }
            }
			
			$pedidoClass->registrarPedidoExportado($pedido);
            /*
            * Commit
            */
            $pedidoClass->_model->getPDO()->commit();
        } catch(Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();

            $msg_erro[] = $e->getMessage();

            continue;
        }

        if($comunicado !== "<ul>"){
            $comunicado .= "</ul>";

            pg_query($con,"BEGIN");

            $sql = "INSERT INTO tbl_comunicado (fabrica,posto,
                        obrigatorio_site, tipo, ativo, descricao, mensagem 
                    ) VALUES (
                    {$fabrica},{$posto}, true, 'Com. Unico Posto', true,
                'Foram gerados os seguintes pedidos com a previsão de faturamento',
                '{$comunicado}')";
            pg_query($con, $sql);

            if(strlen(pg_last_error()) > 0){
                pg_query($con,"ROLLBACK");
                $msg_erro = "ERRO: Não foi possível gravar o comnunicado do posto, em relação aos pedidos gerados.";
            }else{
                pg_query($con,"COMMIT");
            }
        }
    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();

        $fp = fopen("tmp/{$fabrica_nome}/pedidos/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
