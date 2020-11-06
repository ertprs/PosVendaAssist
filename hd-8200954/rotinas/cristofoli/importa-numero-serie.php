<?php
/**
 *
 * importa-numero-serie.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author Guilherme Silva
 * @version 2013.08.30
 * @version 2013.10.01 // Alteração
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao / teste

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
	#include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	if(ENV == 'producao'){
		$origem = "/home/ronald/";
		$email 	= 'ronald.santos@telecontrol.com.br';
		$arquivos = '/tmp/cristofoli/';
	}else{
		$origem = "entrada/";
		$email	= 'ronald.santos@telecontrol.com.br';
		$arquivos = '/tmp/cristofoli/';
	}


	$fabrica = "161";

	/* Inicio Processo */
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/* Carrega arquivo */
	$arquivo = file_get_contents($origem."telecontrol-serie.txt");

	$linha = explode("\n", $arquivo);

	foreach($linha as $key => $l){

		$serie = "";
		$data_venda = "";
		$data_fabricacao = "";
		
		list($serie, $referencia, $cnpj, $venda, $fabricacao) = explode("\t", $l);

		$serie = trim($serie);
		$cnpj = trim($cnpj);
		$cnpj = str_replace(".","",$cnpj);
		$cnpj = str_replace("/","",$cnpj);
		$cnpj = str_replace("-","",$cnpj);

		/* Data Venda */
		$venda = trim($venda);
		$venda = explode("/",$venda);
		$data_venda = $venda[2]."-".$venda[1]."-".$venda[0];
		/* Fim Data Venda*/

		/* Data Fabricação */
		$fabricacao = trim($fabricacao);
		$fabricacao = explode("/",$fabricacao);
		$data_fabricacao = $fabricacao[2]."-".$fabricacao[1]."-".$fabricacao[0];
		/* Fim Data Fabricação*/

		$referencia = trim($referencia);

		if(strlen($referencia) > 0){
			
			$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = $fabrica AND referencia = '$referencia'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$produto = pg_fetch_result($res,0,0);
			}else{
				continue;
			}
		}else{
			continue;
		}

		$sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = $fabrica AND serie = '$serie' AND referencia_produto = '$referencia'";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
			$sql = "INSERT INTO tbl_numero_serie(
				fabrica,
				serie,
				cnpj,
				referencia_produto,
				data_venda,
				data_fabricacao,
				produto
			)VALUES(
				$fabrica,
				'$serie',
				'$cnpj',
				'$referencia',
				'$data_venda',
				'$data_fabricacao',
				$produto
			)";
			$res = pg_query($con,$sql);
		}
	}

	if(strlen($msg_erro) > 0){

		$mensagem = $msg_erro;
		$dest = "ronald.santos@telecontrol.com.br";

		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->IsHTML();
		$mail->AddReplyTo("helpdesk@telecontrol.com.br", "Suporte Telecontrol");
		$mail->Subject = Date('d/m/Y')." - Erro na importação de Número de Série";
	    $mail->Body = $mensagem;
	    $mail->AddAddress($dest);
	    $mail->Send();

	}


	$data_sistema = date("Y_m_d_h_i_s");

	system ("mv ".$origem."numero_serie.txt ".$arquivos."numero_serie_".$data_sistema.".txt && rm -f ".$origem."numero_serie.txt");

	system ("zip -oqm ".$arquivos."numero_serie_$data_sistema.zip ".$arquivos."numero_serie_".$data_sistema.".txt");

	/* Fim Processo */
	$phpCron->termino();

}catch(Exception $e){

	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - cristofoli - Importa Serie (importa-numero-serie.php)", $msg);

}
