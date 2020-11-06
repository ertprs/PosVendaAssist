<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

/*
* Definições
*/
$fabrica        = 163;
$fabrica_nome   = "Rowa";
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
$logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato Rowa")); // Titulo

if ($_serverEnvironment == 'production') {
    $logClass->adicionaEmail("dfelix@bombasRowa.com.br");
} else {
    $logClass->adicionaEmail("thiago.tobias@telecontrol.com.br");
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
* Pega o tipo do Posto para gerar Extrato.
*/  
$tipo_posto = array();  
for ($i=1; $i < $argc; $i++) {

    switch ($argv[$i]) {
        case 'assistencia_tecnica_n1':
            $tipo_posto[] = " ( tbl_tipo_posto.codigo = 'N1' AND tbl_tipo_posto.distribuidor = 'f' AND tbl_tipo_posto.posto_interno = 'f' AND tbl_tipo_posto.tipo_revenda = 'f' AND tbl_tipo_posto.locadora = 'f' AND tbl_tipo_posto.montadora = 'f' ) ";
            break;

        case 'assistencia_tecnica_n2':
            $tipo_posto[] = " ( tbl_tipo_posto.codigo = 'N2' AND tbl_tipo_posto.distribuidor = 'f' AND tbl_tipo_posto.posto_interno = 'f' AND tbl_tipo_posto.tipo_revenda = 'f' AND tbl_tipo_posto.locadora = 'f' AND tbl_tipo_posto.montadora = 'f' ) ";
            break;

        case 'assistencia_tecnica_n3':
            $tipo_posto[] = " ( tbl_tipo_posto.codigo = 'N3' AND tbl_tipo_posto.distribuidor = 'f' AND tbl_tipo_posto.posto_interno = 'f' AND tbl_tipo_posto.tipo_revenda = 'f' AND tbl_tipo_posto.locadora = 'f' AND tbl_tipo_posto.montadora = 'f' ) ";
            break;
        case 'loja_asteca_revenda':
            $tipo_posto[] = " ( tbl_tipo_posto.codigo = 'REV' AND tbl_tipo_posto.distribuidor = 'f' AND tbl_tipo_posto.posto_interno = 'f' AND tbl_tipo_posto.tipo_revenda = 't' AND tbl_tipo_posto.locadora = 'f' AND tbl_tipo_posto.montadora = 'f' ) ";
            break;            
        case 'posto_interno':
            $tipo_posto[] = " ( tbl_tipo_posto.codigo = 'INTERNO' AND tbl_tipo_posto.distribuidor = 'f' AND tbl_tipo_posto.posto_interno = 't' AND tbl_tipo_posto.tipo_revenda = 'f' AND tbl_tipo_posto.locadora = 'f' AND tbl_tipo_posto.montadora = 'f' ) ";
            break;
        default:
            exit;
            break;
    }
}

if (count($tipo_posto) > 0) {
    $query_cond = " AND (";
    $query_cond .= implode(" OR ", $tipo_posto); 
    $query_cond .= " ) ";

} else {
    exit;
}

/*
* Resgata a quantidade de OS por Posto
*/
$os_posto = $classExtrato->getOsPosto($dia_extrato, $fabrica,false, $query_cond);

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
        $classExtrato->insereExtratoPosto($fabrica, $posto, $dia_extrato, $mao_de_obra = 0, $pecas = 0, $total = 0, $avulso = 0);

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

    $fp = fopen("tmp/{$fabrica_nome}/extrato/gera-extrato-".date("dmYH").".txt", "w");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
    fwrite($fp, $msg_erro_arq . "\n \n");
    fclose($fp);

}

/*
* Cron Término
*/
$phpCron->termino();
