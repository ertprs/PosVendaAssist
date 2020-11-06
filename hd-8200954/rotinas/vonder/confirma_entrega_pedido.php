<?php
#Rotina de Confirmação de Entrega de Pedidos 

error_reporting(E_ALL ^ E_NOTICE);
define('APP', 'Confirmar Pedido - Vonder');

try { 
	
   	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
	
	$fabrica = 104;

	$phpCron = new PHPCron($fabrica, __FILE__); 
	$phpCron->inicio();

	#$local_arquivo  = "/home/williamcastro/public_html/ovd-retorno-pedido.csv";
	$local_arquivo = "/home/vonder/vonder-telecontrol/ovd-retorno-pedido.csv";

	$file = fopen($local_arquivo , "r");

	if ($file === FALSE) {

		#$local_arquivo  = "/home/williamcastro/public_html/ovd-retorno-pedido .csv";
		$local_arquivo = "/home/vonder/vonder-telecontrol/ovd-retorno-pedido .csv";
		$file = fopen($local_arquivo , "r");

		if ($file === FALSE) {
			throw new Exception("Arquivo não encontrado");
		}
	}

	$csv = [];

	echo "Carregando Arquivo:[";

	while (($linha = fgetcsv($file, 0, ",")) !== FALSE) {
    	$csv[] = $linha;
		echo "#";
	}
	echo ']' . PHP_EOL;

	fclose($file);

	$infoPedidos = [];

	foreach ($csv as $chave => $valor) {

		$linha = $valor[0];
		
		$pedido = "";
		
		for ($i = 0; $i < 9; $i++) {
			
			if ($i == 0 && $linha[$i] != 0) {
				$pedido .= $linha[$i];
			}

			if ($i != 0) {
				$pedido .= $linha[$i];
			}      		
		}

		$pedido_item = "";

		for ($i = 9; $i < 18; $i++) {

			if ($i == 9 && $linha[$i] != 0) {
				$pedido_item .= $linha[$i];
			}

			if ($i != 9) {
				$pedido_item .= $linha[$i];
			}

		}

		$quantidade = "";

		for ($i = 18; $i < 28; $i++) {
			$quantidade .= $linha[$i];
		}

		$infoPedidos[intval($pedido)][] = [
			'pedido_item' => $pedido_item,
			'quantidade'  => intval($quantidade)
      	];
	}

	echo "Confirmando pedidos:[";

	$log_email = [];

	foreach ($infoPedidos as $pedido => $value) {

		$liberarPedido = false;
		
		foreach ($value as $val) {

			$pedidoItem = $val['pedido_item'];
			$quantidade = $val['quantidade']; 

			$getPedido = "SELECT tbl_pedido.pedido, tbl_pedido_item.pedido_item, tbl_pedido_item.qtde, tbl_pedido.confirmado_fabrica
						  FROM tbl_pedido 
						  JOIN tbl_pedido_item ON (tbl_pedido_item.pedido = tbl_pedido.pedido)
						  WHERE tbl_pedido_item.pedido_item = $pedidoItem";

			$resPedido = pg_query($con, $getPedido);
			
			$objetoPedido = pg_fetch_object($resPedido);

			if ($objetoPedido->qtde == $quantidade && $objetoPedido->pedido_item == $pedidoItem && $objetoPedido->confirmado_fabrica == 'f') {

				$liberarPedido = true;
	
			} else {

				$liberarPedido = false;

				break;
			}
		}

		if ($liberarPedido == true) {

			$data = date('Y-m-d');

			pg_query($con,'BEGIN');

			$update = "UPDATE tbl_pedido 
					   SET  recebido_fabrica = '$data', 
					   		confirmado_fabrica = 't',
					   		status_pedido = 2 
					   WHERE pedido = $pedido";

					   #SELECT fn_atualiza_status_pedido($fabrica, $pedido)

			$resUpdate = pg_query($con, $update);

			if (pg_last_error()) {
				
				pg_query($con,'ROLLBACK');

				$log_email[] = "Pedido : " . $pedido . " Não confirmado";

				#throw new Exception(pg_last_error());
			}

			pg_query($con,'COMMIT');

			$log_email[] = "Pedido : " . $pedido . " Confirmado";	
		} else {

			$log_email[] = "Pedido : " . $pedido . " Não confirmado";
		}

		echo "#";
	}
	
	echo "]" . PHP_EOL;
	
	$arquivo_log  = "/tmp/vonder/confirma_entrega_log.txt";
	#$arquivo_log = "/home/williamcastro/public_html/confirma_entrega_log.txt";
	
	$fp = fopen($arquivo_log, 'a+');
	
	foreach ($log_email as $log_pedido) {
		
		$log = $log_pedido . " " . date('d/m/Y h:i:s') .  "\r\n";

		fwrite($fp, $log);
	}

	fclose($fp);

	require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$mail = new PHPMailer();
	$mail->IsHTML(true);
	$mail->From = 'helpdesk@telecontrol.com.br';
	$mail->FromName = 'Telecontrol';
	$mail->AddAddress('maicon.luiz@telecontrol.com.br');
	$mail->Subject = utf8_decode("Entrega de Pedidos");
	$mail->Body    = "Segue anexo log de erro na importação de produtos...<br/><br/>";
	$mail->AddAttachment($arquivo_log, $arquivo_log);

	if (!$mail->Send()) {
		echo 'Erro ao enviar email: ' . $mail->ErrorInfo;
	} else {
		echo "Enviou";
	}

	unlink($arquivo_log);

    $phpCron->termino();

} catch (Exception $e) {

 	echo 'Erro capturado: ',  $e->getMessage(), "\n";
}
