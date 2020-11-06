
<?php

	error_reporting(E_ALL ^ E_NOTICE);

	define('ENV', 'producao');
	define('DEV_EMAIL', 'ronald.santos@telecontrol.com.br');

	try {

		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';
		include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

		// Precision
		$fabrica = 80;
		$fabrica_nome = 'precision';

		function strtim($var)
		{
			if (!empty($var)) {
				$var = trim($var);
				$var = str_replace("'", "\'", $var);
				$var = str_replace("/", "", $var);
			}

			return $var;
		}

		$diretorio_origem = '/home/' . $fabrica_nome . '/'.$fabrica_nome.'-telecontrol';
		//$diretorio_origem = '/home/lucas/public_html/PosVendaAssist/rotinas/precision/entrada';
		$arquivo_origem = 'rastreio_os.txt';

		$arquivo = $diretorio_origem . '/' . $arquivo_origem;

		$erro = "";

		// Se o arquivo existe e tem conteudo
		if (file_exists($arquivo) and (filesize($arquivo) > 0)) {

			// Le o conteudo do arquivo
			$conteudo = file_get_contents($arquivo);
			// Quebra as informacoes por linhas
			$conteudo = explode("\n", $conteudo);
			$conteudo = array_filter($conteudo);

			// Le linha a linha do arquivo
			foreach ($conteudo as $linha) {

				// Se linha for diferente de vazio
				if (!empty($linha)) {

					// Separa o conteudo da linha por espaco de TAB
					list (
						$rastreamento,
						$os
					) = explode ("\t",$linha);

					$os 	= strtim($os);
					$rastreamento 	= strtim($rastreamento);

					$sql = "SELECT tbl_os.os 
							FROM tbl_os 							
							WHERE tbl_os.os = '$os' 
							AND tbl_os.fabrica = $fabrica";
					$res = pg_query($con, $sql);
					// Se achar o faturamento
					if(pg_num_rows($res) > 0){
						$os = pg_fetch_result($res, 0, 'os');
						// Atualiza/Insere o rastreamento no campo conhecimento
						$sql = "UPDATE tbl_os_extra SET pac = '$rastreamento' WHERE os = $os ";
						$res = pg_query($con, $sql);

						echo $sql ."\n";
					}
				}
			}

			if(strlen($erro) > 0){

				$assunto = '<b>Precision</b>: Erros na importação de Rastreio OS ' . date('d/m/Y');
				$assunto .= "<br /> <br />".$erro;

			}else{

				$assunto = 'Importação de Rastreio OS realizado com Sucesso : <b>Precision</b>';

			}

			$mail = new PHPMailer();
			$mail->IsHTML();
			$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
			$mail->FromName = 'Telecontrol';

			$mail->AddAddress('marisa.silvana@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = $assunto;

			$mail->Send();
			system("mv $arquivo /tmp/$fabrica_nome/rastreio_os".date('Y-m-d-H-i').".txt");
		}

	} catch (Exception $e) {
		echo $e->getMessage();
	}

?>
