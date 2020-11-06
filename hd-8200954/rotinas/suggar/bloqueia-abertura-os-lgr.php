<?php

error_reporting(E_ALL);
#voltar no dia 01/04/2016
try{

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    /* Dados iniciais */
	$fabrica      = 24;
	$fabrica_nome = "Suggar";
	$log_posto    = array();
	$msg_erro     = array();
	$env 		  = "producao"; // test | producao
	// $env 		  = "test"; // test | producao
	

   	$posto = $argv[1];
   	if(strlen($posto) > 0){
		$cond = " AND tbl_extrato.posto = $posto";
	}
	/*
    * Log
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Bloqueio de postos para abertura de OS - {$fabrica_nome}")); // Titulo
    if ($env == "producao" ) {
	    
	    $logClass->adicionaEmail("marisa.silvana@telecontrol.com.br");
	    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");


    } else {
        $logClass->adicionaEmail("thiago.tobias@telecontrol.com.br");
        //$limit = " LIMIT 1";
    }


    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
	Seleciona os postos
    */
	$sql = "SELECT DISTINCT tbl_extrato.posto
				FROM tbl_extrato 
					JOIN tbl_extrato_lgr USING(extrato) 
					LEFT JOIN tbl_faturamento_item fi ON fi.peca = tbl_extrato_lgr.peca AND extrato = extrato_devolucao 
					LEFT JOIN tbl_faturamento f ON f.distribuidor = tbl_extrato.posto AND f.faturamento = fi.faturamento 
				WHERE tbl_extrato.fabrica = {$fabrica}
					AND qtde_nf isnull 
					AND current_date - interval '60 days' > data_geracao 
					AND data_geracao > '2013-01-01 00:00' 					
					{$cond}				
				ORDER BY posto;";
echo $sql;exit;
	$res = pg_query($con, $sql);

	if(strlen(pg_last_error($con)) > 0){

		$msg_erro[] = "Erro ao selecionar os postos com extrato mais de 60 dias sem fechamento. Erro (".pg_last_error($con).").";

	}else{

		if(pg_num_rows($res) > 0){

	        for($i = 0; $i < pg_num_rows($res); $i++){
				$posto   = pg_fetch_result($res, $i, 'posto');

				$sqlA = "SELECT posto 
							FROM tbl_os_campo_extra 
							WHERE fabrica = {$fabrica} 
								AND posto = {$posto} 
								AND observacao = 'Extrato com mais de 60 dias sem fechamento'
								AND desbloqueio is not true";
				$resA = pg_query($con,$sqlA);

				if (pg_num_rows($resA) == 0) {
					$sqlB = "INSERT INTO tbl_posto_bloqueio(fabrica, posto, observacao,desbloqueio) VALUES ($fabrica, $posto,'Extrato com mais de 60 dias sem fechamento',false)";
					$resB = pg_query($con,$sqlB);
					
					if(strlen(pg_last_error($con)) > 0){

						$msg_erro[] = "Erro ao inserir o posto {$posto} para ser bloqueado. Erro (".pg_last_error($con).").";
						/* Posto bloquado: $posto */

					}
				}
			}
		}
	}

    if(count($msg_erro) > 0){
        print_r($msg_erro);
    	$logClass->adicionaLog(implode("<br />", $msg_erro));
        $logClass->enviaEmails();
    }

    /*
    * Cron Término
    */
    $phpCron->termino();

} catch (Excpection $e) {
	echo $e->getMessage();
}
