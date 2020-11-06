<?php
/**
 *
 * importa-peca.php
 *
 * Importa��o de pe�as Vonder/DWT
 *
 * @author  Francisco Ambrozio
 * @version 2012.02.22
 *
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao'); /* producao | teste */
define('DEV_EMAIL', 'guilherme.silva@telecontrol.com.br');

try {

//	include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	// newup
	$fabrica = 201;
	$fabrica_nome = 'newup';

	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("'", "\'", $var);
		}

		return $var;
	}

	$diretorio_origem = '/home/newup/newup-telecontrol';
	$arquivo_origem = 'telecontrol-peca.txt';

	$ftp = '/tmp/newup';

	date_default_timezone_set('America/Sao_Paulo');
	$now = date('Ymd_His');

	$log_dir = '/tmp/' . $fabrica_nome .'/logs';
	$arq_log = $log_dir . '/importa-peca-' . $now . '.log';
	$err_log = $log_dir . '/importa-peca-err-' . $now . '.log';

	if (!is_dir($log_dir)) {
		if (!mkdir($log_dir, 0777, true)) {
			throw new Exception("ERRO: N�o foi poss�vel criar logs. Falha ao criar diret�rio: $log_dir");
		}
	}

	$arquivo = $diretorio_origem . '/' . $arquivo_origem;

	if (ENV == 'teste') {
		$arquivo = "entrada/telecontrol-peca.txt";
	}

	if (file_exists($arquivo) and (filesize($arquivo) > 0)) {
		$conteudo = file_get_contents($arquivo);
		$conteudo = explode("\n", $conteudo);

		$nlog = fopen($arq_log, "w");
		$elog = fopen($err_log, "w");

		foreach ($conteudo as $linha) {
			if (!empty($linha)) {
				list ($referencia, $descricao, $unidade, $origem_arq, $ipi, $ncm, $peso) = explode ("\t",$linha);
				$original = array($referencia, $descricao, $unidade, $origem_arq, $ipi, $ncm, $peso);

				if ($origem_arq == 0) {
					$origem_arq = '1';
				}
				$not_null = array($referencia, $descricao, $origem_arq);
				foreach ($not_null as $value) {
					if (!$value) {
						array_push($original, 'erro');
						$log = implode(";", $original);
						fwrite($nlog, $log . "\n");
						continue 2;
					}
				}

				/*
				Refer�ncia	Referencia da pe�a - tamanho m�ximo de 20 caracteres - sem caracteres especiais
				Descri��o	Descri��o da pe�a - tamanho m�ximo de 50 caracteres - sem caracteres especiais
				Unidade	Unidade de medida da pe�a. EX: PC ou UM
				Origem	NAC ou IMP
				IPI	Percentual 0 a 100
				NCM	Num�rico
				*/

				$referencia = strtim($referencia);
				$descricao  = strtim($descricao);
				$unidade    = strtim($unidade);
				$origem_arq = strtim($origem_arq);
				$ipi        = strtim($ipi);
				$ncm        = strtim($ncm);
                $peso = trim($peso);

				$descricao = substr($descricao, 0, 50);

				$unidade = str_replace("-", "", $unidade);
				$unidade = str_replace(".", "", $unidade);
				$unidade = str_replace("/", "", $unidade);
				
				$peso = str_replace(',','.',$peso);
				$ipi = str_replace(',','.',$ipi);
			
				$origem_arq = str_replace("/", "", $origem_arq);
				switch ($origem_arq) {
					case '0':
						$origem_arq = 'NAC';
						break;
					case '1':
						$origem_arq = 'NAC';
						break;
					case '2':
						$origem_arq = 'IMP';
						break;
				}
				$origem_arq = substr($origem_arq, 0, 10);

				$ipi  = preg_replace('/\D/', '', $ipi);
				$ipi = str_replace("/", "", $ipi);

				$ipi = ($ipi == "") ? 0 : $ipi;

				if(!is_numeric($ipi)) continue;

				$sql_peca = "SELECT tbl_peca.peca FROM   tbl_peca
								WHERE  tbl_peca.referencia = '$referencia'
								AND    tbl_peca.fabrica    = $fabrica";
				$query_peca = pg_query($con, $sql_peca);

				if ($unidade) {
					$unidade = "'$unidade'";
				} else {
					$unidade = 'null';
				}

				if (pg_num_rows($query_peca) == 0) {

					$sql = "INSERT INTO tbl_peca (
									fabrica,
									referencia,
									descricao,
									unidade,
									origem,
									ipi,
                                    ncm,
                                    peso
								) VALUES (
									$fabrica,
									'$referencia',
									(E'$descricao'),
									$unidade,
									'$origem_arq',
									$ipi,
                                    '$ncm',
                                    $peso
								)";
				} else {

					$peca = pg_fetch_result($query_peca, 0, 'peca');
					$sql = "UPDATE tbl_peca SET
									descricao = (E'$descricao'),
									unidade   = $unidade,
									origem    = '$origem_arq',
									ipi       = $ipi,
                                    ncm 	  = '$ncm',
                                    peso = $peso
								WHERE tbl_peca.peca = $peca";
				}

				$query = pg_query($con, $sql);

				if (pg_last_error()) {
					array_push($original, 'erro');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");

					$erro = "==============================\n\n";
					$erro.= $sql . "\n\n";
					$erro.= pg_last_error();
					$erro.= "\n\n";
					fwrite($elog, $erro);
				} else {
					array_push($original, 'ok');
					$log = implode(";", $original);
					fwrite($nlog, $log . "\n");
				}

			}
		}

		fclose($nlog);
		fclose($elog);

		if(ENV == "teste"){
			if(strlen(pg_last_error()) > 0){
				printf(pg_last_error());
			}
		}

		if (filesize($arq_log) > 0) {
			$data_arq_enviar = date('dmy');
			$cmds = "cd $log_dir && cp importa-peca-$now.log peca$data_arq_enviar.txt && zip -r peca$data_arq_enviar.zip peca$data_arq_enviar.txt 1>/dev/null";
			system("$cmds", $retorno);

			$joga_ftp = "cd $log_dir && cp peca$data_arq_enviar.txt $ftp/$fabrica_nome-pecas$data_arq_enviar.ret";
			system("$joga_ftp");

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Importa��o de pe�as ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo arquivo de pe�as importado na rotina...<br/><br/>";
				$mail->AddAttachment($log_dir . '/peca' . $data_arq_enviar . '.zip', 'peca' . $data_arq_enviar . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/peca' . $data_arq_enviar . '.txt');
					unlink($log_dir . '/peca' . $data_arq_enviar . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log: ' , $retorno;
			}
		}

		if (filesize($err_log) > 0) {
			system("cd $log_dir && zip -r importa-peca-err-$now.zip importa-peca-err-$now.log 1>/dev/null", $retorno);

			if ($retorno == 0) {

				require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

				$assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na importa��o de pe�as ') . date('d/m/Y');

				$mail = new PHPMailer();
				$mail->IsHTML(true);
				$mail->From = 'helpdesk@telecontrol.com.br';
				$mail->FromName = 'Telecontrol';

				if (ENV == 'producao') {
					$mail->AddAddress('marisa.silvana@telecontrol.com.br');
				} else {
					$mail->AddAddress(DEV_EMAIL);
				}

				$mail->Subject = $assunto;
				$mail->Body = "Segue anexo log de erro na importa��o de pe�as...<br/><br/>";
				$mail->AddAttachment($log_dir . '/importa-peca-err-' . $now . '.zip', 'importa-peca-err-' . $now . '.zip');

				if (!$mail->Send()) {
					echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
				} else {
					unlink($log_dir . '/importa-peca-err-' . $now . '.zip');
				}

			} else {
				echo 'Erro ao compactar arquivo de log de erros: ' , $retorno;
			}
		}

		$data_arq_process = date('Ymd');
		system("mv $arquivo /tmp/$fabrica_nome/telecontrol-peca-$data_arq_process.txt");

	}

} catch (Exception $e) {
	echo $e->getMessage();
}

