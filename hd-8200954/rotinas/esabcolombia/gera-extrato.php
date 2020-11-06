<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
	include dirname(__FILE__) . '/classes/extrato.php';

	if ($_serverEnvironment == "production") {
        define("ENV", "prod");
    } else {
        define("ENV", "dev");
    }


	/*
	* Definições
	*/
    $fabrica     = 181;
    $dia_mes     = date('d');
    $dia_extrato = date('Y-m-d H:i:s');
    $usa_lgr     = true;
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
    $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato ESAB Colombia")); // Titulo

    if (ENV == "prod") {
        $logClass->adicionaEmail("filipe.souza@esab.com.br");
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
	   $logClass->adicionaEmail("rafael.macedo@telecontrol.com.br");
    }

    /*
    * Extrato Class
    */
    $classExtrato = new Extrato($fabrica);

    /*
    * Classe Extrato que verifica se existe posto sem gerar extrato
    */
    $classVerificaExtrato = new ExtratoTela($fabrica);

    /*
    * Resgata o período dos 15 dias
    */
    $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);
    $data_15 = date('Y-m-d');

    /* Retorna OS's abertas a mais de 90 dias sem extrato gerado */
    $os_intervalo = $classVerificaExtrato->retornaOsIntervalo($fabrica);
    if (empty($os_intervalo)) {
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

    /*
    * Mensagem de Erro
    */

    $msg_erro = "";
    $msg_erro_arq = "";
    
    foreach ($posto_os as $key => $postos) {
        $posto = $key;

        try {
            // begin
            $classExtrato->_model->getPDO()->beginTransaction();

            // insert e get extrato do posto
            $classExtrato->insereExtratoPosto($fabrica, $posto, $dia_extrato);
            $extrato = $classExtrato->getExtrato();

            $sql_extrato_status = "INSERT INTO tbl_extrato_status (fabrica, extrato, data, obs, advertencia) 
                        VALUES ($fabrica, $extrato, now(), 'Pendente de Aprovação', false)";
            $res_extrato_status = $classExtrato->_model->getPDO()->query($sql_extrato_status);

            foreach ($postos as $item) {
                $nome = $item["posto_nome"];
                $fabrica = $item["fabrica"];
                $codigo_posto = $item["posto_codigo"];
                $os = $item["os"];

                if (ENV == "prod" && $posto == 6359) {
                    continue;
                }

                // relaciona extrato com os's
                $classExtrato->relacionaOsExtrato90($fabrica, $posto, $extrato, $dia_extrato, $os);
            }

            // insere lançamentos avulsos para o posto
            $classExtrato->atualizaAvulsosPosto($fabrica, $posto, $extrato);

            // insere valor dos avulsos para o extrato
            $classExtrato->atualizaValoresAvulsos($fabrica, $extrato);

            // verifica LGR
            if($usa_lgr == true){
                $classExtrato->verificaLGR($extrato, $posto, $data_15);
            }

            // calcula o extrato
            $total_avulso  = $classExtrato->verificaTotalAvulsos($posto, $extrato);
            $total_extrato = $classExtrato->calcula($extrato);

            // verifica valor avulso para nao gerar extrato negativo
            if(($total_extrato - $total_avulso) <= 0){
                throw new Exception("Extrato com valor negativo ou zerado acumulando");
            }

            // verifica valor mínimo
            $classExtrato->verificaValorMinimoExtrato(250, $total_extrato);

            // notifica postos
            $classVerificaExtrato->gerarComunicadoPosto($fabrica, $posto, "O sistema gerou extrato automaticamente para Ordens de Serviço abertas há mais de 90 dias.");

            // commita alterações
            $classExtrato->_model->getPDO()->commit();
        } catch (Exception $e) {
            $msg_erro .= $e->getMessage()."<br />";
            $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

            // rollback
            $classExtrato->_model->getPDO()->rollBack();
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

        $fp = fopen("tmp/{$fabrica_nome}/extrato/log-erro".date('d-m-Y').".txt", "a");
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
