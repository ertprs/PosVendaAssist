<?php  

/**
*
* libera-pedido.auditoria.php
*
* Rotina verifica os pedidos que entraram em auditoria por possuirem apenas OS de Revenda.
* Se pedido ainda estiver em auditoria a mais de 7 dias, a rotia irá aprovar o pedido automaticametente e o pedido ficará liberado para exportação.
* Se o pedido estiver em auditoria por um período menor ou igual a 7 dias, a rotina verifica se para o posto do pedido houve o lançanmento de um novo pedido que contém OS de consumidor.
* Caso encontre um pedido com OS de consumidor que esteja aguardando exportação, a rotina irá liberar o pedido de auditora de OS de Revenda automaticamente e o pedido ficará liberado para exportação.
*
* @author Ronald Santos
* @version 2019.02.05
*
*/

define('ENV', 'testes');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';

	$msg_erro       = array();
	$log            = array();

	$vet['fabrica'] = 'wanke';
	$vet['tipo']    = 'pedido';

	if (ENV == 'testes') {
		$vet['dest'] = 'ronald.santos@telecontrol.com.br';
	} else {
		$vet['dest'] = 'helpdesk@telecontrol.com.br';
	}

	$vet['log']     = 2;

	$vet2        = $vet;
	$vet2['log'] = 1;

	$fabrica    = "91" ;

	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	
	$sql = "SELECT 	tbl_pedido.pedido,
					tbl_pedido.posto,
					(CURRENT_DATE - tbl_pedido_status.data::date) AS tempo_auditoria
			FROM tbl_pedido
			JOIN tbl_pedido_status USING(pedido)
			WHERE tbl_pedido.fabrica = {$fabrica} 
			AND tbl_pedido.status_pedido = 18
			AND tbl_pedido_status.status = 18
			AND tbl_pedido_status.observacao = 'Pedido de OS REVENDA'";
	$res = pg_query($con,$sql);
	
	if (strlen(pg_last_error($con)) > 0) {
		$erros[] = 'Erro na $sql: '.pg_last_error($con);
	}

	print_r($erros);

	if (pg_num_rows($res) > 0){

		for ($i=0; $i < pg_num_rows($res); $i++) {
			unset($msg_erro);		
			list($pedido, $posto, $tempo_auditoria) = pg_fetch_row($res, $i);

			if($tempo_auditoria > 7){

				$sql = "INSERT INTO tbl_pedido_status(
														pedido,
														status,
														observacao
													) VALUES(
														{$pedido},
														1,
														'Pedido liberado da auditoria de OS REVENDA automaticamente após 7 dias'
													);";
				$res2 = pg_query($con,$sql);

				if (strlen(pg_last_error($con)) > 0) {

					$msg_erro[] = 'Erro na $sql: '.pg_last_error($con);

				}else{

					$sql = "UPDATE tbl_pedido SET status_pedido = 1 WHERE pedido = {$pedido}";
					$res3 = pg_query($con,$sql);

					if (strlen(pg_last_error($con)) > 0) {

						$msg_erro[] = 'Erro na $sql: '.pg_last_error($con);

					}

				}

			}else{

				$sql = "SELECT tbl_pedido.pedido
						FROM tbl_os
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_pedido USING(pedido)
						WHERE tbl_os.fabrica = {$fabrica}
						AND tbl_os.consumidor_revenda = 'C'
						AND tbl_pedido.fabrica = {$fabrica}
						AND tbl_pedido.posto = {$posto}
						AND tbl_pedido.data::date = CURRENT_DATE;";
				$res4 = pg_query($con,$sql);

				if (strlen(pg_last_error($con)) > 0) {

					$msg_erro[] = 'Erro na $sql: '.pg_last_error($con);

				}else{

					if(pg_num_rows($res4) > 0){

						$pedidoConsumidor = pg_fetch_result($res4, 0, 'pedido');

						$sql = "INSERT INTO tbl_pedido_status(
														pedido,
														status,
														observacao
													) VALUES(
														{$pedido},
														1,
														'Pedido liberado da auditoria de OS REVENDA automaticamente pois foi gerado o pedido de consumidor Nº $pedidoConsumidor'
													);";
						$res5 = pg_query($con,$sql);

						if (strlen(pg_last_error($con)) > 0) {

							$msg_erro[] = 'Erro na $sql: '.pg_last_error($con);

						}else{

							$sql = "UPDATE tbl_pedido SET status_pedido = 1 WHERE pedido = {$pedido}";
							$res6 = pg_query($con,$sql);

							if (strlen(pg_last_error($con)) > 0) {

								$msg_erro[] = 'Erro na $sql: '.pg_last_error($con);

							}

						}

					}

				}

			}			

			if (count($msg_erro)>0){

				$erros = $msg_erro;

				$res14 = pg_query($con,"ROLLBACK TRANSACTION");
				

			}else {

				$res16 = pg_query($con,"COMMIT TRANSACTION");

			}

		}

	} 


	if (count($erros) > 0) {

		$msg_erro = implode("<br>", $erros);
		Log::log2($vet, $msg_erro);

	}

	if ($log) {

		$log = implode("<br>", $log);
		Log::log2($vet2, $log);

	}

	if ($msg_erro) {
		
		Log::envia_email($vet, "Log de ERROS - Liberação Auditoria de Pedido de OS WANKE", $msg_erro);

	}

	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}
?>
