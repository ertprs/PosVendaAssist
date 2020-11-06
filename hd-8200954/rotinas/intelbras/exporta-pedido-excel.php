<?php
/**
 *
 * exporta-pedido-excel.php
 *
 * @author  Francisco Ambrozio
 * @version 2011.12.12
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	date_default_timezone_set('America/Sao_Paulo');
	$current_date = date('Y-m-d');

	$fabrica = 14;

	$diretorio_saida = '/tmp/intelbras/pedidos';
	$arquivo_saida   = $diretorio_saida . '/pedidos-exportados-excel.xls';

	if (!is_dir($diretorio_saida)) {
		if (!mkdir($diretorio_saida, 0777, true)) {
			throw new Exception('Erro ao processar exporta-pedido-excel.php para a Intelbrás: não foi possível criar diretório: ' . $diretorio_saida);
		}
	}

	$sql = "SELECT tbl_posto_fabrica.codigo_posto,
					tbl_pedido.pedido,
					tbl_os.sua_os,
					tbl_peca.referencia,
					SUM(tbl_os_item.qtde) AS qtde,
					TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data,
					TO_CHAR(tbl_pedido.exportado, 'DD/MM/YYYY') AS exportado
				FROM tbl_pedido
				JOIN tbl_os_item USING (pedido)
				JOIN tbl_os_produto USING (os_produto)
				JOIN tbl_os USING (os)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
				JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
				WHERE tbl_pedido.fabrica = $fabrica
				AND tbl_posto_fabrica.fabrica  = $fabrica
				AND tbl_pedido.exportado::date = '$current_date'
				GROUP BY tbl_posto_fabrica.codigo_posto,
					tbl_pedido.pedido,
					tbl_os.sua_os,
					tbl_os_item.peca,
					tbl_peca.referencia,
					tbl_pedido.data,
					tbl_pedido.exportado
				ORDER BY tbl_pedido.pedido, tbl_os_item.peca";
	$query = pg_query($con, $sql);

	if (pg_num_rows($query) > 0) {
		$f = fopen($arquivo_saida, 'w');

		$cabecalho = "<html>\n";
		$cabecalho .= "<head>\n";
		$cabecalho .= "<title>Pedidos Exportados no dia em EXCEL</title>\n";
		$cabecalho .= "</head>\n";
		$cabecalho .= "<body>\n";

		$cabecalho .= "<table border='1'>\n";
		$cabecalho .= "<td>Código Posto</td>\n";
		$cabecalho .= "<td>Pedido</td>\n";
		$cabecalho .= "<td>O.S.</td>\n";
		$cabecalho .= "<td>Referência</td>\n";
		$cabecalho .= "<td>Qtde</td>\n";
		$cabecalho .= "<td>Data Pedido</td>\n";
		$cabecalho .= "<td>Data Exportado</td>\n";
		$cabecalho .= "</tr>\n";

		fwrite($f, $cabecalho);

		while ($res = pg_fetch_array($query)) {
			$linha = "<tr>\n";
			$linha .= "<td>" . $res['codigo_posto'] . "</td>\n";
			$linha .= "<td>" . $res['pedido'] . "</td>\n";
			$linha .= "<td>" . $res['sua_os'] . "</td>\n";
			$linha .= "<td>" . $res['referencia'] . "</td>\n";
			$linha .= "<td>" . $res['qtde'] . "</td>\n";
			$linha .= "<td>" . $res['data'] . "</td>\n";
			$linha .= "<td>" . $res['exportado'] . "</td>\n";
			$linha .= "</tr>\n";

			fwrite($f, $linha);
		}

		$rodape = "</table>\n";

		$rodape .= "</body>\n";
		$rodape .= "</html>";

		fwrite($f, $rodape);

		fclose($f);

		system("cd $diretorio_saida && zip -r pedidos-exportados-excel.zip pedidos-exportados-excel.xls 1>/dev/null", $retorno);

		if ($retorno == 0) {

			require dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

			$assunto = 'Pedidos em Excel exportados no dia ' . date('d/m/Y');

		 	$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';
			$mail->AddAddress('helpdesk@telecontrol.com.br');
			$mail->AddAddress('maria.martins@intelbras.com.br');
			$mail->Subject = $assunto;
			$mail->Body = "Segue anexo arquivo de pedidos em formato EXCEL referente aos pedidos AST postados na área de FTP...<br/><br/>";
			$mail->AddAttachment($diretorio_saida . '/pedidos-exportados-excel.zip', 'pedidos-exportados-excel.zip');

			if (!$mail->Send()) {
				echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
			} else {
				unlink($diretorio_saida . '/pedidos-exportados-excel.zip');
			}

		} else {
			echo 'Erro ao compactar arquivo: ' , $retorno;
		}

		rename($diretorio_saida . '/pedidos-exportados-excel.xls', $diretorio_saida . '/pedidos-exportados-excel-' . str_replace('-', '', $current_date) . '.xls');

	}

} catch (Exception $e) {

	echo $e->getMessage();

}

