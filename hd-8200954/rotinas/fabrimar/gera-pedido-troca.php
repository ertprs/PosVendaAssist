<?php

// Inicialização
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	/* Classes */
	include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';

	/*
	* Config. inicial
	*/
	date_default_timezone_set('America/Sao_Paulo');
	define('ENV',
		($_serverEnvironment == 'development')
			? 'teste'
			: 'producao'
	);  // producao Alterar para produção ou algo assim

	$env       = ($_serverEnvironment == 'development') ? 'teste' : 'producao';
	$data      = date('d-m-Y');
	$fabrica   = 145;
	if (isset($argv[2]) and is_numeric($argv[2]))
		$os_argv   = $argv[1];
	$agrupamento = "posto"; /* posto | os */

	/*
	* Log
	*/
	$logClass = new Log2();
	$logClass->adicionaLog(array("titulo" => "Log de ERROS - Geração de Pedido de Troca de OS Fabrimar")); // Titulo

	if (ENV === 'producao') {
		$logClass->adicionaEmail('helpdesk@telecontrol.com.br');
		$logClass->adicionaEmail('fernando.saibro@fabrimar.com.br');
		$logClass->adicionaEmail('kevin.robinson@fabrimar.com.br');
		$logClass->adicionaEmail('anderson.dutra@fabrimar.com.br');
	} else {
		$logClass->adicionaEmail('manuel.lopez@telecontrol.com.br');

		include '../../helpdesk/mlg_funciones.php';
		echo <<<INITSTATUS
            Ambiente: $env
            Agrupar por $agrupamento

INITSTATUS;
    }

	/*
	* Cron
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

} catch(Exception $e) {
	die("Erro durante a inicialização da geração de pedido de troca da Fabrimar\n");
}

/**
 * Inicializa OS, Pedido e variáveis
 */
try {
	$osClass = new \Posvenda\Os($fabrica);
	$osTroca = $osClass->getTrocaPosto($agrupamento, null, $os_argv);

	if (empty($osTroca)) {
		exit;
	}

	$Fabrica      = new \Posvenda\Fabrica($fabrica);
	$pedidoClass  = new \Posvenda\Pedido($fabrica);

	$fabrica_nome = $Fabrica->getNome();
	$condicao     = $pedidoClass->getCondicaoGarantia();
	$tipo_pedido  = $pedidoClass->getTipoPedidoGarantia();

} catch (Exception $e) {
	if ($e->getMessage())
		$logClass->adicionaLog($e->getMessage());
	else
		$logClass->adicionaLog('Erro desconhecido!');
	$logClass->enviaEmails();
	$phpCron->termino;
	die;
}

/**
 * Processa OSs
 */
foreach($osTroca as $troca) {
	$posto  = $troca['posto'];
	$tabela = $pedidoClass->_model->getTabelaPreco($posto, $tipo_pedido);
	$dados  = compact(
		'posto',    'tipo_pedido',
		'condicao', 'fabrica',
		'tabela'
	);
	$dados['status_pedido'] = 1;

	$os_pedido_posto = $osClass->getOsTrocaPosto($posto, null, $os_argv);

    if (ENV=='teste')
        pre_echo($os_pedido_posto, 'OS com troca');

	foreach ($os_pedido_posto as $os_posto) {
		try {
			$os = $os_posto['os'];
			if (!$os)
				continue;

            if (ENV=='teste')
                pecho("Procesando OS $os...");

            $osPedido = new \Posvenda\Os($fabrica, $os);

			$pedidoClass->_model->getPDO()->beginTransaction();

			/**
			 * Verifica se tem peças para inserir
			 */
			$pecas = $osPedido->getPecasPedidoGarantiaTroca($os);

			if (!count($pecas)) {
				continue;
			}

			$pedidoClass->grava($dados);
			$id_pedido = $pedidoClass->getPedido();

			if (ENV=='teste')
				pre_echo($pecas, "PEÇAS PARA O PEDIDO $id_pedido");

            foreach ($pecas as $peca) {
				$preco_peca = $pedidoClass->getPrecoPecaGarantia($peca['peca'], $os);

				$item[0] = array(
					'pedido'         => $id_pedido,
					'peca'           => $peca['peca'],
					'qtde'           => $peca['qtde'],
					'qtde_faturada'  => 0,
					'qtde_cancelada' => 0,
					'preco'          => $preco_peca
				);

				if (ENV == 'teste') {
					pre_echo($item, 'ITEM A INSERIR');
				}

				$pedidoClass->gravaItem($item);
				$pedido_item = $pedidoClass->getPedidoItem();

				$status = $pedidoClass->atualizaOsItemPedidoItem(
					$peca['os_item'],
					$id_pedido, $pedido_item, $fabrica
				);

				if ($status) {
					$msg_erro[] = "Erro ao atualizar a peça da OS $os do Pedido $id_pedido";
				}
				$pedidoClass->setOsTrocaPedido($os, $id_pedido, $pedido_item);
			}
			$pedidoClass->finaliza($id_pedido);
			$pedidoClass->_model->getPDO()->commit();

		} catch (Exception $e) {
			$pedidoClass->_model->getPDO()->rollBack();
			$msg_erro[] = $e->getMessage();
			continue;
		}
	}
}

if(!empty($msg_erro)){
	if (ENV == 'teste') {
		pre_echo($msg_erro, 'ERROS DURANTE O PROCESSAMENTO', true);
	}

	if (isset($argv[1])) {
		echo json_encode(array("erro" => utf8_encode($msg_erro[0])));
	} else {
		$logClass->adicionaLog(implode("<br />", $msg_erro));

		$logClass->enviaEmails();
		$arquivo_err   = "/tmp/fabrimar/gera-pedido-troca-{$data_sistema}.err";
		$arquivo_log   = "/tmp/fabrimar/gera-pedido-troca-{$data_sistema}.log";
		$arquivo = "tmp/{$fabrica_nome}/pedidos/log-erro-gera-pedido-troca".date("d-m-Y_H-i-s").'.txt';

		file_put_contents(
			$arquivo_err,
			"Data Log: " . date('d/m/Y') .PHP_EOL .
			implode(PHP_EOL, $msg_erro) . PHP_EOL
		);
	}
} else if (isset($argv[1])) {
	echo json_encode(array("sucesso" => "true"));
}

$phpCron->termino();
