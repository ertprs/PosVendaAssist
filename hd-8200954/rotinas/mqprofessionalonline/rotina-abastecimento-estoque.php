<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

	$fabrica = 186;

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Log Class
	*/
	$logClass = new Log2();
	$logClass->adicionaLog(array("titulo" => "Log erro Abastecimento de Estoque - MQ Hair")); // Titulo
	$logClass->adicionaEmail("ronald.santos@telecontrol.com.br");
	$msg_erro = "";

	$pedidoClass = new \Posvenda\Pedido($fabrica);

	/* Seleciona os postos que controlam estoque */
	/* passa como parametro true para trazer os postos que controlam estoque e false para os que não controlam */
	$postos_controlam_estoque = $pedidoClass->getPostosControlamEstoque(true);

	if($postos_controlam_estoque != false){


		foreach ($postos_controlam_estoque as $value) {


			try{

				/* begin */
				$pedidoClass->_model->getPDO()->beginTransaction();

				$posto = $value["posto"];
				$pecas          = "";
				$pecasStatus    = "";
				$novoEstoque    = "";
				$entrou         = "";

				$estoque = $pedidoClass->verificaEstoquePosto($posto);

				if (count($estoque) == 0) {
				    $pedidoClass->_model->getPDO()->rollBack();
				    continue;
				}

				foreach ($estoque as $key => $value) {
				    $pecas[] = $value["peca"];
				}

				$status_pedido = $pedidoClass->pedidoBonificadoNaoFaturado($posto);

				if (count($status_pedido) > 0) {
				    foreach ($status_pedido as $key => $result) {
					$pecasStatus[] = $result["peca"];
				    }

				    $pecasPedido = array_diff($pecas,$pecasStatus);


				    if (count($pecasPedido) == 0) {
					$pedidoClass->_model->getPDO()->rollBack();
					continue;
				    }
				}

				foreach ($estoque as $key => $value) {
				    if (array_search($value["peca"],$pecasPedido) or count($pecasPedido) == 0 ) {

					$entrou = 1;

					$novoEstoque[$key]["peca"]          = $value["peca"];
					$novoEstoque[$key]["referencia"]    = $value["referencia"];
					$novoEstoque[$key]["qtde_pedido"]   = $value["qtde_pedido"];
				    }
				}

				if ($entrou == 1) {
				    $pedidoClass->pedidoBonificado($posto, $novoEstoque);
				}

				#$emails = array('ronald.santos@telecontrol.com.br');

				#$pedidoClass->EnviaEmailPedidoGerados($posto, $fabrica, $emails);

				/* commit */
				$pedidoClass->_model->getPDO()->commit();

			}catch(Exception $e){

				/* rollback */
				$pedidoClass->_model->getPDO()->rollBack();

	            		$msg_erro .= $e->getMessage();

	            		continue;

			}

		}

	}

	/*
	* Erro
	*/
	if(!empty($msg_erro)){

	    $logClass->adicionaLog($msg_erro);

	    if($logClass->enviaEmails() == "200"){
	      echo "Log de erro enviado com Sucesso!";
	    }else{
	      echo $logClass->enviaEmails();
	    }

	    $fp = fopen("tmp/mqhair/reabastecimento-estoque".date('Y-m-d').".err", "a");
	    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
	    fwrite($fp, $msg_erro_arq . "\n \n");
	    fclose($fp);

	}

	/*
	* Cron Término
	*/
	$phpCron->termino();

?>

