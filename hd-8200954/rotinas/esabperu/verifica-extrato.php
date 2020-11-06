<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/classes/extrato.php';

	if ($_serverEnvironment == "production") {
    define("ENV", "prod");
  } else {
    define("ENV", "dev");
  }

	/*
	* Definições
	*/
	$fabrica 		= 182;
	$dia_mes     	= date('d');
	$dia_extrato 	= date('Y-m-d H:i:s');

	#$dia_mes     = "27";
	#$dia_extrato = "2014-08-27 23:59:00";

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Log Class
	*/
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro Verifica Posto sem gerar extrato a mais de 75 dias ESAB Peru")); // Titulo

    if (ENV == "prod") {
        $logClass->adicionaEmail("filipe.souza@esab.com.br");
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
	   $logClass->adicionaEmail("rafael.macedo@telecontrol.com.br");
    }

    /*
    * Extrato Class
    */
    $classExtrato = new ExtratoTela($fabrica);

    /*
    * Verifica se o posto não gera extrato a mais de 75 dias
    */

    // rotina alterada no chamado hd-4367465 - verifica os's abertas a mais de 75 dias sem fechamento
    // $posto_extrato = $classExtrato->verificaExtrato($fabrica, "75");
    $os_intervalo = $classExtrato->verificaOsAbertas($fabrica);
    if(empty($os_intervalo)){
      exit;
    }

    $posto_os = [];
    for ($i = 0; $i < count($os_intervalo); $i++) {
        $posto_os[$os_intervalo[$i]['posto']][] = [
            'os' => $os_intervalo[$i]['os'], 
            'fabrica' => $os_intervalo[$i]['fabrica'],
            'posto_nome' => $os_intervalo[$i]['nome'], 
            'posto_codigo' => $os_intervalo[$i]['codigo_posto']
        ];
    }

//     echo key($posto_extrato[0]);
//     print_r($posto_extrato);

    /*
    * Mensagem de Erro
    */
    $msg_erro = "";
    $msg_erro_arq = "";

    foreach ($posto_os as $key => $postos) {
        $posto = $key;

        $mensagem = "<center>Você tem OSs abertas há mais de 75 dias.</center>
                    <center><b>Segue abaixo a relação:</b></center>
                    <ul>";

        foreach ($postos as $item) {
            $os = $item["os"];
            $mensagem .= "<li><a target='_blank' href='https://posvenda.telecontrol.com.br/assist/os_press.php?os=" . $os . "'>" . $os . "</a></li>";
        }

        $mensagem .= "</ul>";

        $classExtrato->_model->getPDO()->beginTransaction();

        try {
            $classExtrato->gerarComunicadoPosto($fabrica, $posto, $mensagem);
            $classExtrato->_model->getPDO()->commit();
        } catch (\Exception $e) {
            $msg_erro .= $e->getMessage()."<br />";
            $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();
            $classExtrato->_model->getPDO()->rollback();
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

        $fp = fopen("tmp/{$fabrica_nome}/verifica-extrato/log-erro".date('d-m-Y').".txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, $msg_erro_arq . "\n \n");
        fclose($fp);

    }

    /*
     * Cron Término
     */
    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
