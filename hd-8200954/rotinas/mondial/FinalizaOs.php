<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include '../../os_cadastro_unico/fabricas/151/classes/FinalizaOsTroca.php';
	include '../../classes/Posvenda/Os.php';

	$fabrica = 151;
	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Log Class
	*/
	$logClass = new Log2();
	$logClass->adicionaLog(array("titulo" => "Log erro finaliza os troca - Mondial")); // Titulo
	$logClass->adicionaEmail("guilherme.curcio@telecontrol.com.br");
	$msg_erro = "";

	$finalizaOsTroca = new FinalizaOsTroca();



	/*
	* Verifica os
	*/
	try {

		/*traz todas as OS que estao faturadas e que são de troca produto para finalizar*/
		$array_os = $finalizaOsTroca->verificaOsTroca();

		if($array_os != false){

			foreach ($array_os as $key => $value) {
				print_r($value);
				$classOS = new Posvenda\Os($fabrica,$value['os']);
				$classOS->finaliza($con, true);

			}

		}
	} catch(\Exception $e) {

		$msg_erro .= $e->getMessage()."<br />";
	    // $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

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

	    $fp = fopen("tmp/{$fabrica_nome}/os-troca/log-erro.txt", "a");
	    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
	    fwrite($fp, $msg_erro_arq . "\n \n");
	    fclose($fp);

	}

	/*
	* Cron Término
	*/
	$phpCron->termino();

?>
