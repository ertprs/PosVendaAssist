<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
use Posvenda\Fabricas\_180;
use Posvenda\Os;

if ($_serverEnvironment == "production") {
    define("ENV", "prod");
} else {
    define("ENV", "dev");
}

/*
 * Definições
 */
$fabrica     = 180;
$dia_mes     = date('d');
$dia_extrato = date('Y-m-d H:i:s');

$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();


$logClass = new Log2();
$logClass->adicionaLog(array("titulo" => "Log de erro rotina finaliza os 90 dias - ESAB Argentina"));

$logClass->adicionaEmail("helpdesk@telecontrol.com.br");

$oOs = new Posvenda\Fabricas\_180\Os($fabrica);

$dados_os = $oOs->buscarOs(90);
if(count($dados_os)>0){        

   foreach ($dados_os as $os) {
        pg_query($con,"BEGIN TRANSACTION");
        $retorno1 = $oOs->finalizar($os);

        $retorno2 = $oOs->tempoReparar($os, 60);

        $retorno3 = $oOs->calculaOs($os);

        if($retorno1 == false OR $retorno2 == false OR $retorno3 == false){
            $erro[]= $os;
            pg_query($con,"ROLLBACK TRANSACTION");
        }else{
            pg_query($con,"COMMIT TRANSACTION");
        }                     
    }        
}

if(count($erro)){

    $os_erros = implode(", ",$erro);

    $logClass->adicionaLog($os_erros);

    $msg_erro_arq = "As O.Ss apresentaram erro ao executar a rotina de finaliza os com 90 dias. \n $os_erros";
    if($logClass->enviaEmails() == "200"){
      echo "Log de erro enviado com Sucesso!";
    }else{
      echo $logClass->enviaEmails();
    }
    $fp = fopen("/tmp/esabargentina/finaliza-os-90-dias-log-erro-".date('d-m-Y').".txt", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $msg_erro_arq . "\n \n");
    fclose($fp);

}

/*
* Cron Término
*/
$phpCron->termino();

?>