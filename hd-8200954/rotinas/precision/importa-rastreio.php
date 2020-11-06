
<?php

	/*
	 	* exporta-rastreio.php
	 	* @author  Guilherme Henrique da Silva
	 	* @version 24/07/2013
	*/

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

		$diretorio_origem = "/home/{$fabrica_nome}/{$fabrica_nome}-telecontrol";
		//$diretorio_origem = '/home/lucas/public_html/PosVendaAssist/rotinas/precision/entrada/';
		$arquivo_origem = 'rastreio_notafiscal.csv';

		$arquivo = $diretorio_origem . '/' . $arquivo_origem;

		$erro = "";

		// Se o arquivo existe e tem conteudo
		if (file_exists($arquivo) and (filesize($arquivo) > 0)) {

			// Le o conteudo do arquivo
			$conteudo = file_get_contents($arquivo);
			// Quebra as informacoes por linhas
			$conteudo = explode("\n", $conteudo);

			// Le linha a linha do arquivo
			foreach ($conteudo as $linha) {

				// Se linha for diferente de vazio
				if (!empty($linha)) {

					// Separa o conteudo da linha por espaco de TAB
					list (
						$rastreamento,
						$nota_fiscal
					) = explode (";",$linha);

					//$cnpj 			= strtim($cnpj);
					$nota_fiscal 	= strtim($nota_fiscal);
					$rastreamento 	= strtim($rastreamento);

					// Busca o faturamento com os dados informados
					//retirada no chamado hd-6161122 analise 1 //Comentado no chamado hd-6161122 analise 1 
					//JOIN tbl_posto ON tbl_faturamento.posto = tbl_posto.posto AND tbl_posto.cnpj = '$cnpj'

					$sql = "SELECT tbl_faturamento.faturamento 
							FROM tbl_faturamento 							
							WHERE tbl_faturamento.nota_fiscal::numeric = $nota_fiscal
							AND tbl_faturamento.fabrica = $fabrica";
					$res = pg_query($con, $sql);

					if(pg_last_error($con)){
						$erro .= "Prezado Cliente, Não foi encontrado nunhum faturamento com a Nota Fiscal($nota_fiscal) e CNPJ($cnpj) informados.\n";
					}

					// Se achar o faturamento
					if(pg_num_rows($res) > 0){
						$faturamento = pg_fetch_result($res, 0, 'faturamento');
						// Atualiza/Insere o rastreamento no campo conhecimento
						$sql = "UPDATE tbl_faturamento SET conhecimento = '$rastreamento' WHERE faturamento = $faturamento AND fabrica = $fabrica and length(trim(conhecimento)) = 0 ";
						$res = pg_query($con, $sql);
					}
				}
			}

			if(strlen($erro) > 0){

				$assunto = '<b>Precision</b>: Erros na importação de Rastreio ' . date('d/m/Y');
				$assunto .= "<br /> <br />".$erro;

			}else{

				$assunto = 'Importação de Rastreio realizado com Sucesso : <b>Precision</b>';

			}

			$mail = new PHPMailer();
			$mail->IsHTML();
			$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
			$mail->FromName = 'Telecontrol';

			$mail->AddAddress('ronald.santos@telecontrol.com.br');
			$mail->Subject = $assunto;
			$mail->Body = $assunto;

			$mail->Send();
			system("mv $arquivo /tmp/$fabrica_nome/rastreio_notafiscal".date('Y-m-d-H-i').".txt");
		}

	} catch (Exception $e) {
		echo $e->getMessage();
	}

?>
