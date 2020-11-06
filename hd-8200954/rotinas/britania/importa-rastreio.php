<?php

	/*
	 	* importa-rastreio.php
	 	* @author  Guilherme Fabiano Monteiro
	 	* @version 02/10/2014
	*/

	error_reporting(E_ALL ^ E_NOTICE);

	define('ENV', 'production');
	define('DEV_EMAIL', 'guilherme.monteiro@telecontrol.com.br');

	try {

		include dirname(__FILE__) . '/../../dbconfig.php';
		include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
		require dirname(__FILE__) . '/../funcoes.php';
		include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

		$fabrica = 3;
		$fabrica_nome = 'britania';
		$data    = date('Y-m-d-H');

		function strtim($var)
		{
			if (!empty($var)) {
				$var = trim($var);
				$var = str_replace("'", "\'", $var);
				$var = str_replace("/", "", $var);
			}

			return $var;
		}

		$diretorio_origem = '/www/cgi-bin/britania/entrada';
		$arquivo_origem = 'importa_rastreio.txt';

		$arquivo = $diretorio_origem . '/' . $arquivo_origem;

		$erro = "";

		// Se o arquivo existe e tem conteudo
		if (file_exists($arquivo) and (filesize($arquivo) > 0)) {

			$conteudo = file_get_contents($arquivo);
			$conteudo = explode("\n", $conteudo);
			foreach ($conteudo as $linha) {
				if (!empty($linha)) {
					list (
						$cnpj,
						$nota_fiscal,
						$rastreamento
					) = explode (";",$linha);

					$cnpj 			= strtim($cnpj);
					$cnpj           = preg_replace('/\D/','',$cnpj);
					$nota_fiscal 	= strtim($nota_fiscal);
					$rastreamento 	= strtim($rastreamento);

					$sql = "SELECT tbl_faturamento.faturamento
							FROM tbl_faturamento
							JOIN tbl_posto ON tbl_faturamento.posto = tbl_posto.posto AND tbl_posto.cnpj = '$cnpj'
							WHERE tbl_faturamento.nota_fiscal = '$nota_fiscal'
							AND tbl_faturamento.fabrica = $fabrica";
					$res = pg_query($con, $sql);
					if(pg_last_error($con)){
						$erro .= "Prezado Cliente, Não foi encontrado nunhum faturamento com a Nota Fiscal($nota_fiscal) e CNPJ($cnpj) informados.\n";
					}

					if(pg_num_rows($res) > 0){
						$faturamento = pg_fetch_result($res, 0, 'faturamento');
						$sql = "UPDATE tbl_faturamento SET conhecimento = '$rastreamento' WHERE faturamento = $faturamento AND fabrica = $fabrica";
						$res = pg_query($con, $sql);

					}

				}

			}

			if(strlen($erro) > 0){
				$assunto = '<b>Britania</b>: Erros na importação de Rastreio ' . date('d/m/Y');
				$assunto .= "<br /> <br />".$erro;
			}else{
				$assunto = 'Importação de Rastreio realizado com Sucesso : <b>Britânia</b>';
			}

			$mail = new PHPMailer();
			$mail->IsHTML();
			$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
			$mail->FromName = 'Telecontrol';

			$mail->Subject = $assunto;
			$mail->Body = $assunto;

			$mail->Send();
			system("mv $arquivo /tmp/$fabrica_nome/importa_rastreio-$data.txt");
		}

	} catch (Exception $e) {
		echo $e->getMessage();
	}

?>
