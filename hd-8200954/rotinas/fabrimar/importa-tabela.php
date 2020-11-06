<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

$fabrica        = 145;
$nome_fabrica   = "fabrimar";
$data           = date('YmdHis');
$arquivo_log    = "/tmp/{$nome_fabrica}/logs/importa_tabela_log-{$data}.txt";
$arquivo_origem = "/home/{$nome_fabrica}/{$nome_fabrica}-telecontrol/telecontrol-tabela.txt";
$arquivo        = "/tmp/{$nome_fabrica}/tabela-{$data}.txt";

try {
	if (file_exists($arquivo_origem)) {
		system("mv {$arquivo_origem} {$arquivo}");

		$log = fopen($arquivo_log, "w");

		$conteudo = file_get_contents($arquivo);
		$conteudo = explode("\n", $conteudo);

		if (count($conteudo) > 0) {
			foreach ($conteudo as $key => $linha) {
				if (empty($linha)) {
					continue;
				}

				pg_query($con, "BEGIN");

				$log_linha = array();

				list($tabela, $peca, $preco) = explode(";", $linha);

				$tabela = trim($tabela);
				$peca   = trim($peca);
				$preco  = trim($preco);

				if (empty($tabela)) {
					$log_linha[] = "Tabela não informada";
				} else {
					$sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = {$fabrica} AND sigla_tabela = '{$tabela}'";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$tabela_id = pg_fetch_result($res, 0, "tabela");
					} else {
						$insert = "INSERT INTO tbl_tabela (fabrica, sigla_tabela, ativa) VALUES ({$fabrica}, '{$tabela}', TRUE) RETURNING tabela";
						$resInsert = pg_query($con, $insert);

						$tabela_id = pg_fetch_result($resInsert, 0, "tabela");
					}

					if (!isset($tabela_id) || empty($tabela_id)) {
						$log_linha[] = "Tabela não encontrada: {$tabela}";
					}
				}
				
				if (empty($peca)) {
					$log_linha[] = "Peça não informada";
				} else {
					$sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$fabrica} AND UPPER(referencia) = UPPER('{$peca}')";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$peca_id = pg_fetch_result($res, 0, "peca");
					} else {
						$log_linha[] = "Peça não encontrada: {$peca}";
					}
				}

				if (empty($preco)) {
					$log_linha[] = "Preço não informado";
				} else {
					$preco = str_replace(".", "", $preco);
					$preco = str_replace(",", ".", $preco);
				}

				if (!count($log_linha)) {
					$sql = "SELECT tabela_item FROM tbl_tabela_item WHERE tabela = {$tabela_id} AND peca = {$peca_id}";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$tabela_item = pg_fetch_result($res, 0, "tabela_item");

						$update = "UPDATE tbl_tabela_item SET preco = {$preco} WHERE tabela_item = {$tabela_item}";
						$resUpdate = pg_query($con, $update);

						if (strlen(pg_last_error()) > 0) {
							$log_linha[] = "Erro ao atualizar registro: tabela = {$tabela}, peça = {$peca}, preço = {$preco}";
						}
					} else {
						$insert = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ({$tabela_id}, {$peca_id}, {$preco})";
						$resInsert = pg_query($con, $insert);

						if (strlen(pg_last_error()) > 0) {
							$log_linha[] = "Erro ao inserir registro: tabela = {$tabela}, peça = {$peca}, preço = {$preco}";
						}
					}
				} 

				if (count($log_linha) > 0) {
					fwrite($log, "Erro na linha {$key}:<br />".implode("<br />", $log_linha)."<br /><br />");
					pg_query($con, "ROLLBACK");
				} else {
					pg_query($con, "COMMIT");
				}
			}
		}

		fclose($log);

		$log = file_get_contents($arquivo_log);

		if (strlen($log) > 0) {
			$mail = new PHPMailer();
			$mail->IsHTML(true);
			$mail->From = 'helpdesk@telecontrol.com.br';
			$mail->FromName = 'Telecontrol';
			$mail->AddAddress($email);
			$mail->AddAddress("helpdesk@telecontrol.com.br");
			$mail->AddAddress("fernando.saibro@fabrimar.com.br");
			$mail->AddAddress("kevin.robinson@fabrimar.com.br");
			$mail->AddAddress("anderson.dutra@fabrimar.com.br");
			$mail->Subject = "Telecontrol - Importação de preços";
			$mail->Body = $log;
			$mail->Send();
		}
	}
} catch (Exception $e) {
	$log = fwrite($log, "Erro ao processar arquivo");
	fclose($log);

	$log = file_get_contents($arquivo_log);

	$mail = new PHPMailer();
	$mail->IsHTML(true);
	$mail->From = 'helpdesk@telecontrol.com.br';
	$mail->FromName = 'Telecontrol';
	$mail->AddAddress($email);
	$mail->Subject = "Telecontrol - Importação de preços";
	$mail->Body = $log;
	$mail->Send();
}

?>
