<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
use Posvenda\Fabricas\_42;
use Posvenda\Os;

if ($_serverEnvironment == "production") {
    define("ENV", "prod");
} else {
    define("ENV", "dev");
}

/*
 * Definições
 */
$fabrica     = 42;
$dia_mes     = date('d');
$dia_extrato = date('Y-m-d H:i:s');

$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();


$logClass = new Log2();
$logClass->adicionaLog(array("titulo" => "Log de erro rotina cancela O.S 180 dias - Makita"));

$logClass->adicionaEmail("helpdesk@telecontrol.com.br");

$oOs = new Posvenda\Fabricas\_42\Os($fabrica);

$dados_os = $oOs->buscarOs(180);

if(count($dados_os)>0){        

   foreach ($dados_os as $os) {
        $retorno1 = $oOs->Cancelar($os);

        if($retorno1 == false){
			echo $os;exit;
            $erro[]= $os;
        }                    
    }        
}

if(count($erro)){

    $os_erros = implode(", ",$erro);

    $logClass->adicionaLog($os_erros);

    $msg_erro_arq = "As O.Ss apresentaram erro ao executar a rotina de cancela O.S com 180 dias. \n $os_erros";
    if($logClass->enviaEmails() == "200"){
      echo "Log de erro enviado com Sucesso!";
    }else{
      echo $logClass->enviaEmails();
    }
    $fp = fopen("/tmp/makita/cancela-os-180-dias-log-erro-".date('d-m-Y').".txt", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $msg_erro_arq . "\n \n");
    fclose($fp);

}

/*
* Cron Término
*/
$phpCron->termino();

?>
