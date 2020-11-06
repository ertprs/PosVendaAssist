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
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

	if(ENV == 'producao'){
		$origem = "/home/marisa/";
		$email 	= 'marisa.silvana@telecontrol.com.br';
		$arquivos = '/tmp/fujitsu/';
	}else{
		$origem = "/home/marisa/";
		$email	= 'marisa.silvana@telecontrol.com.br';
		$arquivos = '/tmp/fujitsu';
	}

	$fabrica = "138";

	/* Inicio Processo */
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/* ---- */
	#$sql = "DROP TABLE if exists fujitsu_numero_serie;";
	#$res = pg_query($con, $sql);
	#$msg_erro .= pg_errormessage($con);

	$sql = "
			CREATE TABLE fujitsu_numero_serie (
				txt_serie              text,
				txt_cnpj               text,
				txt_referencia_produto text,
				txt_data_venda         text,
				txt_pro                text
	#		); ";
	#$res = pg_query($con, $sql);
	#$msg_erro .= pg_errormessage($con);

	#$sql = "GRANT ALL on fujitsu_numero_serie to telecontrol;";
	#$res = pg_query($con, $sql);
	#$msg_erro .= pg_errormessage($con);

	/* Carrega arquivo */
	$arquivo = file_get_contents($origem."numero_serie.txt");

	$linha = explode("\n", $arquivo);

	foreach($linha as $key => $l){

		$serie = "";
		$data_venda = "";
		
		list($serie, $cnpj, $produto, $data, $pro) = explode("\t", $l);

		$sql = "
			INSERT INTO fujitsu_numero_serie 
				(txt_serie, txt_cnpj, txt_referencia_produto, txt_data_venda) 
						VALUES 
						('$serie', '$cnpj', '$produto', '$data')";
					$res = pg_query($con, $sql);

			echo $cont++;
	}

} catch (Exception $e) {
        echo $e->getMessage();
}
