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
	$fabrica = 153;
	$data    = date('d-m-Y');
	$env     = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

	/*
	* A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
	*/
	$param = "posto"; /* posto | os */

	/*
	* Log
	*/
	$logClass = new Log2();
	$logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Inbrasil")); // Titulo
	if ($env == 'producao' ) {
		$logClass->adicionaEmail("helpdesk@telecontrol.com.br");
	$logClass->adicionaEmail("");
	} else {
		$logClass->adicionaEmail("lucas.carlos@telecontrol.com.br");
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
	$osTroca = $osClass->getTrocaPosto($param);

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
	$condicao = $pedidoClass->getCondicaoPagamentoGarantia();

	/*
	* Resgata o tipo pedido da Fabrica
	*/
	$tipo_pedido = $pedidoClass->getTipoPedidoGarantia();

	for ($i = 0; $i < count($osTroca); $i++) {
		try {
			$posto = $osTroca[$i]["posto"];

			$os_pedido_posto = $osClass->getOsTrocaPosto($posto);

			if (empty($os_pedido_posto) || $posto == 6359) {
				continue;
			}

			$dados = array(
				'posto'         => $posto,
				'tipo_pedido'   => $tipo_pedido,
				'condicao'      => $condicao,
				'fabrica'       => $fabrica,
                'status_pedido' => 2,
                'troca'         => "'t'"
			);

			/*
			* Grava o Pedido. Begin.
			*/
			$pedidoClass->_model->getPDO()->beginTransaction();
			$pedidoClass->grava($dados);

			$pedido = $pedidoClass->getPedido();

			for ($x = 0; $x < count($os_pedido_posto); $x++) {
				$os = $os_pedido_posto[$x]["os"];
				$dadosItens = array();

				/**
				 * Pega as peças da OS
				 */
				$osClass = new \Posvenda\Os($fabrica, $os);

				// Consultando se tem distribuidor...
				$distrib = $osClass->getDistribuidorOsTroca($os);

				if ($distrib) {
					$pedidoClass->grava(array(
						'distribuidor' => $distrib,
						'pedido_via_distribuidor' => 't' // MLG: queria usar TRUE, mas... :D
					), $pedido);
				}

				$pecas = $osClass->getPecasPedidoGarantiaTroca();

				foreach ($pecas as $key => $peca) {
					// print_r($pecas);
					unset($dadosItens);

					/*
					* Insere o Pedido Item
					*/
					$dadosItens[] = array(
						"pedido"            => (int)$pedido,
						"peca"              => $peca["peca"],
						"qtde"              => $peca["qtde"],
						"qtde_faturada"     => 0,
						"qtde_cancelada"    => 0,
						"preco"             => $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os)
					);
					// $dadosItens["total_item"] = ($dadosItens["preco"] * $dadosItens["qtde"]);

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
				//seta distribuidor 4311 - distrib
				$osClass->setPedidoOsTroca($pedido, $os);
			}

			$pedidoClass->finaliza($pedido);

            /*
            * Commit
            */
            $pedidoClass->_model->getPDO()->commit();
        } catch(Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();

            $msg_erro[] = $e->getMessage();

            continue;
        }

    }

    if(!empty($msg_erro)){

        $logClass->adicionaLog(implode("<br />", $msg_erro));

        // if($logClass->enviaEmails() == "200"){
        //   echo "Log de erro enviado com Sucesso!";
        // }else{
        //   $logClass->enviaEmails();
        // }

        $fp = fopen("tmp/{$fabrica_nome}/pedidos/troca-log-erro-".date("dmY").".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, implode("\n", $msg_erro));
        fclose($fp);

    }

    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
