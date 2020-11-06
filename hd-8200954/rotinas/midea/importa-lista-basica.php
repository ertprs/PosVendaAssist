<?php

require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/tdocs.class.php';
require dirname(__FILE__) . '/../funcoes.php';

include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;

$login_fabrica = 169;
$tdocs = new TDocs($con, $login_fabrica, 'rotina');

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

function msg_log($msg){
    echo "\n\r".date('H:m')." - $msg";
}

//ob_start();
try{
    msg_log('Inicia rotina de importação de lista básica');
    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Importa lista basica");
    $routine_id = $arr[0]["routine"];

    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if (!strlen($routine_schedule_id)) {
        throw new Exception("Agendamento da rotina não encontrado");
    }

    $routineScheduleLog = new Log();

    $arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

    $count_routine = 0;
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/midea\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }
    $em_execucao = ($count_routine > 2) ? true : false;

    if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $em_execucao == false) {

        $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
        $routine_schedule_log_stopped = $routineScheduleLog->GetRoutineWithoutFinish();

        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_stopped['routine_schedule_log']);
        $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage(utf8_encode('Rotina finalizada'));
        $routineScheduleLog->Update();
        msg_log('Finalizou rotina anterior Schedule anterior. Rotina cod: '.$routine_id);
    }

    // Limpando variáveis /
    $routineScheduleLog->setRoutineSchedule(null);
    $routineScheduleLog->setRoutineScheduleLog(null);
    $routineScheduleLog->setDateFinish(null);
    $routineScheduleLog->setStatus(null);
    $routineScheduleLog->setStatusMessage(null);

    if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $em_execucao == true) {
        throw new Exception("Rotina em execução");
    } else {

        $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
        $routineScheduleLog->setDateStart(date("Y-m-d H:i"));

        if (!$routineScheduleLog->Insert()) {
            throw new Exception("Erro ao gravar log da rotina");
        }

        $routine_schedule_log_id = $routineScheduleLog->SelectId();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);

    }

    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }

    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));
    $data_consulta = date('Y-m-d', strtotime(date('Y-m-d'). '-1 week')); /* CONSULTA ATÉ 1 SEMANA */

    pg_prepare($con, 'consulta_peca',         "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = $1");
    pg_prepare($con, 'consulta_produto',      "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND (UPPER(REPLACE(tbl_produto.referencia_pesquisa, '-', 'YY')) = UPPER('$1') OR UPPER(REPLACE(tbl_produto.referencia_fabrica, '-', 'YY')) = UPPER('$1') OR UPPER(REPLACE(tbl_produto.referencia, '-', 'YY')) = UPPER('$1'));");
    pg_prepare($con, 'consulta_lista_basica', "SELECT lista_basica FROM tbl_lista_basica WHERE fabrica={$login_fabrica} AND produto = $1 AND peca = $2");

    try {

        msg_log('Iniciando requisição à API');

        $request   = array('PI_MATNR' => '', 'PI_DT_ENVIO' => '');
        $result    = $client->Z_CB_TC_LISTA_TECNICA($request);
        $dados_xml = $result->Z_CB_TC_LISTA_TECNICAResult->any;
        $xml       = simplexml_load_string($dados_xml);
        $xml       = json_decode(json_encode((array)$xml), TRUE);

        if (count($xml['NewDataSet']['ZES_LISTA_TECNICATABLE']) == 0) { /* RETORNOU ALGUM ERRO. EX: NÃO ENCONTROU A PEÇA */
            msg_log("Não foi possível consultar a lista básica produto.");
            throw new Exception("Não foi possível consultar a lista básica produto.");

        } else {

            foreach ($xml['NewDataSet']['ZES_LISTA_TECNICATABLE'] as $ponteiro => $pecas) {
                try {

                    $referenciaProduto = str_replace("-", "YY", trim($pecas['MATNR']));
                    $referenciaPeca    = trim($pecas['IDNRK']);
                    $qtde              = trim($pecas['MNGLG']);

                    msg_log("Consultando produto Ref: $referenciaProduto ".count($xml['NewDataSet']['ZES_LISTA_TECNICATABLE'])." peças retornadas");

                    $res_produto       = pg_execute($con, 'consulta_produto', array($referenciaProduto));

                    if (pg_num_rows($res_produto) == 0) {

                        throw new Exception("Produto não cadastrado Referência: $referenciaProduto.");

                    } else {
                        $produto = pg_fetch_result($res_produto, 0, 'produto');

                        msg_log("$ponteiro - Peça: $referenciaPeca");

                        $res_peca = pg_execute($con, 'consulta_peca', array($referenciaPeca));

                        if (pg_num_rows($res_peca) == 0) {

                            throw new Exception("Peça não cadastrada Referência: $referenciaPeca.");

                        } else {

                            $peca = pg_fetch_result($res_peca, 0, 'peca');

                            $res_lista_basica = pg_execute($con, 'consulta_lista_basica', array($produto, $peca));

                            if (pg_num_rows($res_lista_basica) > 0) {

                                $sqlUP = "UPDATE tbl_lista_basica SET qtde = {$qtde} WHERE lista_basica = {$lista_basica}";
                                $resUp = pg_query($con, $sqlUP);

                                if (pg_last_error() > 0) {
                                    throw new Exception("Erro ao tentar ALTERAR o registro. Erro: ".pg_last_error());
                                }

                                msg_log("Foi alterado um registro na tabela tbl_lista_basica. Lista basica: $lista_basica, Quantidade: $qtde");

                            } else {

                                $sqlIns = "INSERT INTO tbl_lista_basica(
                                    fabrica,
                                    peca,
                                    produto,
                                    qtde
                                )VALUES(
                                    {$login_fabrica},
                                    {$peca},
                                    {$produto},
                                    {$qtde}
                                );";
                                $resIns = pg_query($con, $sqlIns);
                                if (pg_last_error() > 0) {
                                    throw new Exception("Erro ao tentar INSERIR o registro. Erro: ".pg_last_error());
                                }
                                msg_log("Foi inserido um registro na tabela tbl_lista_basica. Peca: $peca, Ref: $referencia");
                            }
                        }
                    }

                } catch (Exception $e) {
                    msg_log("Erro: ".$e->getMessage());
                    $logError = new \Posvenda\LogError();
                    $logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
                    $logError->setErrorMessage($e->getMessage());
                    $logError->Insert();
                }
            }

        }
    } catch (Exception $e) {
       msg_log("Erro: ".$e->getMessage());
        $logError = new \Posvenda\LogError();
        $logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
        $logError->setErrorMessage($e->getMessage());
        $logError->Insert();
    }

    msg_log("Arquivo enviado para o Tdocs.");
    if (!isset($status_final)) {
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage('Rotina finalizada');
        $routineScheduleLog->Update();
    }
} catch (Exception $e) {
    msg_log("Erro: ".$e->getMessage());
    $logError = new \Posvenda\LogError();
    $logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
    $logError->setErrorMessage($e->getMessage());
    $logError->Insert();

    $status_final = 2;

    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    $routineScheduleLog->Update();
}

$phpCron->termino();
?>
