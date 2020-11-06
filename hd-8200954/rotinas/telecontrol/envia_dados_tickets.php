<?php 

use Ticket\Ticket;

try{

	include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Ticket/Ticket.php';

    define('APP', 'Envia Tickets');
	define('ENV','devel');

	$logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Exporta Ticket")); // Titulo
    $logClass->adicionaEmail("lucas.carlos@telecontrol.com.br"); // email

	//$fabrica 	= 148;
	$log 		= "";

    if ($argv[1]) {
        $fabrica = $argv[1] ;
    }

	$ticket = new Ticket($fabrica);	

	$arr_OS = $ticket->buscaOS();

	if(count(array_filter($arr_OS))==0){
		$log .= "Nenhuma O.S para integraчуo \n\r";
	}

	foreach($arr_OS as $valor){
		$retornoRun = $ticket->run($valor['os']);
		$log .= "OS:". $valor['os']. " " .json_encode($retornoRun). "\n\r";
	}
	
  if(!empty($log)){

    $logClass->adicionaLog($log);

    if($logClass->enviaEmails() == "200"){
      echo "Log enviado com Sucesso!";
    }else{
      echo $logClass->enviaEmails();
    }

    $fp = fopen("/tmp/envia-ticket-".date("dmY").".txt", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y H:i:s") . "\n");
    fwrite($fp, $log . "\n \n");
    fclose($fp);
}



	
	
} catch (Exception $e) {
    echo $e->getMessage();
}



?>