<?php

/*
* importa-rastreio.php
* @author  Guilherme Fabiano Monteiro
* @version 02/10/2014
*/

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'william.lopes@telecontrol.com.br');

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	$fabrica = 147;
	$fabrica_nome = 'hitachi';
	$data    = date('Y-m-d-H');

	function strtim($var)
	{
		if (!empty($var)) {
			$var = trim($var);
			$var = str_replace("-", "", $var);
			$var = str_replace("'", "", $var);
			$var = str_replace("/", "", $var);
		}

		return $var;
	}

	function adicionalTrim($str, $len = 0)
	{
		$str = str_replace(".", "", $str);
		$str = str_replace("-", "", $str);

		return $str;
	}

	if(ENV == 'producao'){
		
		$diretorio_origem = '/home/hitachi/pos-vendas/hitachi-telecontrol/rastreio/';
		$arquivo_origem = 'telecontrol-rastreio.txt';
		
	}else{
	
		$diretorio_origem = '/home/william/hitachi/entrada';
		$arquivo_origem = 'telecontrol-rastreio.txt';
		
	}

	$erro = "";

	foreach (glob("{$diretorio_origem}rastreio2*") as $arquivo) {
		if (file_exists($arquivo) and (filesize($arquivo) > 0)) {

			$conteudo = file_get_contents($arquivo);
			$conteudo = explode("\n", $conteudo);
			foreach ($conteudo as $linha) {
				if (!empty($linha)) {

					list (
						$cnpj,
						$nota_fiscal,
						$rastreamento
						) = explode ("\t",$linha);

					$cnpj 			= strtim($cnpj);
					$cnpj 			= adicionalTrim($cnpj);
					$cnpj           = preg_replace('/\D/','',$cnpj);
					$nota_fiscal 	= strtim($nota_fiscal);
					$rastreamento 	= strtim($rastreamento);

					if(!strlen($cnpj)){  $erro         .= "Campo nulo : cnpj ";continue;}
					if(!strlen($nota_fiscal)){  $erro  .= "Campo nulo : nota fiscal";continue;}
					if(!strlen($rastreamento)){  $erro .= "Campo nulo : rastreamento";continue;}

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
				$assunto = '<b>Hitachi</b>: Erros na importação de Rastreio ' . date('d/m/Y');
				$assunto .= "<br /> <br />".$erro;
			}else{
				$assunto = 'Importação de Rastreio realizado com Sucesso : <b>Hitachi</b>';
			}

			$mail = new PHPMailer();
			$mail->IsHTML();
			$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
			if(ENV == 'producao'){
					$mail->AddAddress("amaral@hitachi-koki.com.br");
	   				$mail->AddAddress("helpdesk@telecontrol.com.br");
			}else{
					$mail->AddAddress("william.lopes@telecontrol.com.br");
			}
			$mail->FromName = 'Telecontrol';

			$mail->Subject = $assunto;
			$mail->Body = $assunto;

			$mail->Send();
			system("mv $arquivo /tmp/$fabrica_nome/telecontrol-importa_rastreio-$data.txt");
		}
	}
} catch (Exception $e) {
	echo $e->getMessage();
}

?>
