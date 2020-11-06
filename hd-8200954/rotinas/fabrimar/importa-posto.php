<?php

error_reporting(E_ALL ^ E_NOTICE);

try{

	/*
	* Includes de arquivos necessários
	*/
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

	/*
	* Inicia com o código da Fabrimar
	*/
	$fabrica = 145;
	$ambiente = "producao"; 

	/*
	* Log Class
	*/
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log de erro - Importa Posto - Fabrimar")); // Titulo
    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    $logClass->adicionaEmail("fernando.saibro@fabrimar.com.br");
    $logClass->adicionaEmail("kevin.robinson@fabrimar.com.br");
	$logClass->adicionaEmail("anderson.dutra@fabrimar.com.br");

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Resgata o Arquivo
	*/
	$file = ($ambiente == "teste") ? file_get_contents("entrada/exemplo-importa-posto.txt") : file_get_contents("/home/fabrimar/fabrimar-telecontrol/telecontrol-posto.txt");
	$file = explode("\n", $file);

	/* Erro */
	$msg_erro = "";

	function verificaCNPJ($cnpj){
		if(strlen($cnpj) >= 15){
			$cnpj = substr($cnpj, 1); 
		}
		return $cnpj;
	}

	function retira($str){
		return str_replace("'", "", $str);
	}

	foreach($file as $linha){

		$campos = explode(";", $linha);

		$codigo_posto 	= $campos[0];
		$razao 			= retira($campos[1]);
		$nome_fantasia 	= retira($campos[2]);
		$cnpj 			= verificaCNPJ($campos[3]);
		$ie 			= $campos[4];
		$endereco 		= retira($campos[5]);
		$numero 		= retira($campos[6]);
		$complemento 	= retira($campos[7]);
		$bairro 		= retira($campos[8]);
		$cep 			= $campos[9];
		$cidade 		= retira($campos[10]);
		$uf 			= $campos[11];
		$email 			= $campos[12];
		$telefone 		= $campos[13];
		$fax 			= $campos[14];
		$contato 		= retira($campos[15]);
		$capital_interior = $campos[16]; /* Se o posto está na Capital ou no Interior */
		$tipo_posto 	= $campos[17];

		echo $sql_posto = "SELECT tbl_posto.posto FROM tbl_posto WHERE tbl_posto.cnpj = '$cnpj'";
		$query_posto = pg_query($con, $sql_posto);

		if (pg_num_rows($query_posto) == 0) {

			$sql = "INSERT INTO tbl_posto (
									nome,
									nome_fantasia,
									cnpj,
									ie,
									endereco,
									numero,
									complemento,
									bairro,
									cep,
									cidade,
									estado,
									email,
									fone,
									fax,
									contato,
									capital_interior
								) VALUES (
									(E'$razao'),
									(E'$nome_fantasia'),
									'$cnpj',
									'$ie',
									'$endereco',
									'$numero',
									'$complemento',
									'$bairro',
									'$cep',
									'$cidade',
									'$estado',
									'$email',
									'$telefone',
									'$fax',
									'$contato',
									'$capital_interior'
								) RETURNING posto";
			$query = pg_query($con, $sql);

			if(strlen(pg_last_error()) > 0){
				$msg_erro .= "Erro ao cadastrar o Posto {$nome_fantasia} - CNPJ : {$cnpj} <br />";
			}else{
				$posto = pg_fetch_result($query, 0, 'posto');
			}

		} else {
			$posto = pg_fetch_result($query_posto, 0, 'posto');
		}

		$sql = "SELECT 
				    tbl_posto_fabrica.posto
				FROM   tbl_posto_fabrica
				WHERE  tbl_posto_fabrica.posto   = $posto
				AND    tbl_posto_fabrica.fabrica = $fabrica";
		$query = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro .= "Erro ao selecionar o Posto Fábrica {$nome_fantasia} - CNPJ : {$cnpj} <br />";
		}

		if (pg_num_rows($query) == 0 && empty($erro)) {

			$tipo_opr = "inserir";

			$sql = "INSERT INTO tbl_posto_fabrica (
									posto,
									fabrica,
									senha,
									tipo_posto,
									login_provisorio,
									codigo_posto,
									credenciamento,
									contato_fone_comercial,
									contato_fax,
									contato_endereco ,
									contato_numero,
									contato_complemento,
									contato_bairro,
									contato_cep,
									contato_cidade,
									contato_estado,
									contato_email,
									nome_fantasia,
									contato_nome
								) VALUES (
									$posto,
									$fabrica,
									'',
									456,
									null,
									'$codigo_posto',
									'DESCREDENCIADO',
									'$telefone',
									'$fax',
									'$endereco',
									'$numero',
									'$complemento',
									(E'$bairro'),
									'$cep',
									(E'$cidade'),
									'$estado',
									'$email',
									(E'$nome_fantasia'),
									(E'$contato')
								)";
		} elseif(empty($erro)) {

			$tipo_opr = "alterar";

			$sql = "UPDATE tbl_posto_fabrica SET
							codigo_posto = '$codigo_posto',
							contato_endereco = '$endereco',
							contato_bairro = (E'$bairro'),
							contato_cep = '$cep',
							contato_cidade = (E'$cidade'),
							contato_estado = '$estado',
							contato_fone_comercial = '$telefone',
							contato_fax = '$fax',
							nome_fantasia = (E'$nome_fantasia'),
							contato_email = '$email'
					WHERE tbl_posto_fabrica.posto = $posto
					AND tbl_posto_fabrica.fabrica = $fabrica";
		}

		$query = pg_query($con, $sql);

		if(strlen(pg_last_error()) > 0){
			$msg_erro .= "Erro ao {$tipo_opr} o Posto Fábrica {$nome_fantasia} - CNPJ : {$cnpj} <br />";
		}

		if (!empty($msg_erro)) {
			$arquivo = ($ambiente == "teste") ? fopen('entrada/posto-nao-importado.txt', 'a+') : fopen("/home/fabrimar/telecontrol-fabrimar/telecontrol-posto-nao-importado.txt", "+a"); 
			fwrite($arquivo, $codigo_posto." - ".$msg_erro." \n");
			fclose($arquivo);
			$erro = "";
		}else{
			$arquivo = ($ambiente == "teste") ? fopen('entrada/posto-importado.txt', 'a+') : fopen("/home/fabrimar/telecontrol-fabrimar/telecontrol-posto-importado.txt", "+a"); 
			fwrite($arquivo, $codigo_posto."\n");
			fclose($arquivo);
		}

	}

	/*
	* Erro
	*/
	if(!empty($msg_erro)){

        $logClass->adicionaLog($msg_erro);

        if($logClass->enviaEmails() == "200"){
          	echo "Log de erro enviado com Sucesso!";
        }else{
          	echo $logClass->enviaEmails();
        }

    }

	/*
	* Cron Término
	*/
	$phpCron->termino();
	
}catch(Exception $e) {
	echo $e->getMessage();
}

?>
