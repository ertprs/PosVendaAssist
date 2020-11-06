<?php

error_reporting(E_ALL ^ E_NOTICE);

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/log/log.class.php';

$log = new Log();
$log->adicionaLog(array("titulo" => "Log de Erro - arquivo finaliza_os_50_dias.php"));
$log->adicionaTituloEmail("Log de Erro - arquivo finaliza_os_50_dias.php");
$log->adicionaEmail("helpdesk@telecontrol.com.br");

try {

	/*
    * Definições
    */
    $fabrica        = 163;
    $fabrica_nome   = "rowa";
    $dia_mes        = date('d');
    $dia_extrato    = date('Y-m-d H:i:s');

    $sql = "SELECT os
			FROM tbl_os
			WHERE fabrica = {$fabrica}
			AND finalizada IS NULL
			AND excluida IS NOT TRUE
			AND (data_abertura + INTERVAL '50 days') < CURRENT_DATE";
	$res = pg_query($con, $sql);
	$msg_erro = pg_num_rows($con);

	if(!empty($msg_erro)){
		$log->adicionaLog("Erro ao selecionar OS's abertas a mais de 50 dias - ".$msg_erro);
	}


	if (pg_num_rows($res) > 0) {
		while ($result = pg_fetch_object($res)) {
			pg_query($con, "BEGIN");

			$sql_df = "UPDATE tbl_os 
					   SET 
					   	data_fechamento = CURRENT_TIMESTAMP, 
					   	finalizada = CURRENT_TIMESTAMP,
					  	qtde_km_calculada = 0,
					  	qtde_km = 0,
					  	pecas = 0,
					  	mao_de_obra = 0,
					   	status_checkpoint = 9
					   WHERE os = {$result->os} 
					   AND fabrica = {$fabrica}";
			$res_df = pg_query($con, $sql_df);

			if (pg_last_error()) {
				$msg_erro[] = pg_last_error();
				pg_query($con, "ROLLBACK");
				continue;
			}

			pg_query($con, "COMMIT");
		}
	}

	if(!empty($msg_erro)){
		$log->enviaEmails();
	}

}catch (Exception $e) {

    $msg_erro = $e->getMessage();
    $log->adicionaLog("Erro ao selecionar OS's - ".$msg_erro);
    $log->enviaEmails();

}

?>