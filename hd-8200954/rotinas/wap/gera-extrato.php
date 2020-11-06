<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_157/Extrato.php';

/*
* Definições
*/
$fabrica        = 157;
$fabrica_nome   = "wap";
$dia_mes        = date('d');
$dia_extrato    = date('Y-m-d H:i:s');

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
$logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato WAP")); // Titulo

if ($_serverEnvironment == 'production') {
    $logClass->adicionaEmail("sistemasti@wap.ind.br");
} else {
    $logClass->adicionaEmail("kaique.magalhaes@telecontrol.com.br");
}

/*
* Extrato Class
*/
$classExtrato = new Extrato($fabrica);

/*
* Resgata o período dos 15 dias
*/
$data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);

/*
* Resgata a quantidade de OS por Posto
*/
$os_posto = $classExtrato->getOsPosto($dia_extrato, $fabrica);

if(empty($os_posto)){
    exit;
}

/**
* Utiliza LGR
*/
$usa_lgr = true;

/**
* Verifica valor mínimo
*/
$verifica_valor_minino = false;

/*
* Mensagem de Erro
*/
$msg_erro = "";
$msg_erro_arq = "";

for ($i = 0; $i < count($os_posto); $i++) {

    $posto          = $os_posto[$i]["posto"];
    $nome           = $os_posto[$i]["nome"];
    $codigo_posto   = $os_posto[$i]["codigo_posto"];
    $qtde           = $os_posto[$i]["qtde"];

    try {
        /*
        * Begin
        */
        $classExtrato->_model->getPDO()->beginTransaction();

        /*
        * Insere o Extrato para o Posto
	 */
	$mao_de_obra = 0;
	$pecas = 0;
	$total = 0;
	$avulso = 0;
        $classExtrato->insereExtratoPosto($fabrica, $posto, $dia_extrato, $mao_de_obra, $pecas, $total, $avulso);

        /*
        * Resgata o numero do Extrato
        */
        $extrato = $classExtrato->getExtrato();

        /*
        * Insere lançamentos avulsos para o Posto
        */
        $classExtrato->atualizaAvulsosPosto($fabrica, $posto, $extrato);

        /*
        * Relaciona as OSs com o Extrato
        */
        $classExtrato->relacionaExtratoOS($fabrica, $posto, $extrato, $dia_extrato);

        /*
        * Atualiza os valores avulso dos postos
        */
        $classExtrato->atualizaValoresAvulsos($fabrica);

        /*
        * Calcula o Extrato
        */
        $total_extrato = $classExtrato->calcula($extrato);

        /**
        * Verifica LGR
        */
        if($usa_lgr == true){
            $classExtrato->verificaLGR($extrato, $posto, $data_15);
        }

        /*
        * Commit
        */
        $classExtrato->_model->getPDO()->commit();

    } catch (Exception $e){

        $msg_erro .= $e->getMessage()."<br />";
        $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

        /*
        * Rollback
        */
        $classExtrato->_model->getPDO()->rollBack();

    }

}

/*
* Erro
*/
if(!empty($msg_erro)){

    $logClass->adicionaLog($msg_erro);
    echo $logClass->enviaEmails();

    system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
    system("mkdir /tmp/{$fabrica_nome}/extrato/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/extrato/" );

    $fp = fopen("/tmp/{$fabrica_nome}/extrato/gera-extrato-".date("dmYH").".txt", "w");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $msg_erro_arq . "\n \n");
    fclose($fp);

}

/*
* Cron Término
*/
$phpCron->termino();
