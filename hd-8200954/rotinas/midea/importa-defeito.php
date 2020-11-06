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
    echo "\n".date('H:i')." - $msg";
}

//ob_start();
try{
    msg_log('Inicia rotina de importação de Defeito e Defeito Constatado');
    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Importa defeito e defeito constatado");
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

    /* Limpando variáveis */
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
   
    if ($serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
    }

    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));

    $data_consulta = date('Y-m-d', strtotime(date('Y-m-d'))); /* HOJE*///date('Y-m-d', strtotime(date('Y-m-d'). '-1 week')); /* CONSULTA ATÉ 1 SEMANA */

    pg_prepare($con, 'consulta_diagnostico_defeito_familia', "SELECT diagnostico FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND defeito_constatado = $1 AND familia = $2");
    pg_prepare($con, 'consulta_diagnostico_defeito', "SELECT diagnostico FROM tbl_diagnostico WHERE fabrica = {$login_fabrica} AND defeito_constatado = $1 AND defeito = $2");
    pg_prepare($con, 'consulta_defeito', "SELECT defeito FROM tbl_defeito WHERE fabrica = {$login_fabrica} AND codigo_defeito = $1");
    pg_prepare($con, 'consulta_defeito_constatado', "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND codigo = $1");

    $res = pg_query($con, "
	SELECT DISTINCT ON (tbl_produto.referencia)
            tbl_produto.familia,
            REPLACE(tbl_produto.referencia, 'YY', '-') AS referencia,
            COALESCE(tbl_numero_serie.serie, '*') AS serie
        FROM tbl_produto
        LEFT JOIN tbl_numero_serie ON tbl_numero_serie.produto = tbl_produto.produto AND tbl_numero_serie.fabrica = {$login_fabrica}
        WHERE fabrica_i = {$login_fabrica}
        AND tbl_produto.ativo IS TRUE
	AND tbl_produto.familia IS NOT NULL
	AND tbl_produto.familia = 7663;
    ");
    $rows = pg_num_rows($res);

    msg_log("Iniciando consulta em $rows linhas");
    for ($i = 0; $i < $rows; $i++) {
        try {
            $familia      = pg_fetch_result($res, $i, 'familia');
            $referencia   = pg_fetch_result($res, $i, 'referencia');
	    $serie        = pg_fetch_result($res, $i, 'serie');

            msg_log("Requisicao: $i -> Iniciando requisição à API: serie $serie -> produto $referencia - familia - $familia");
            $params = new SoapVar("<ns1:xmlDoc><criterios><PV_MATNR>{$referencia}</PV_MATNR><PV_SERNR>{$serie}</PV_SERNR><PV_KATALOGART>C</PV_KATALOGART></criterios></ns1:xmlDoc>", XSD_ANYXML
            );
            $request   = array('xmlDoc' => $params);
            $result    = $client->PesquisaDefeitos($request);
            $dados_xml = $result->PesquisaDefeitosResult->any;
            $xml       = simplexml_load_string($dados_xml);
            $xml       = json_decode(json_encode((array)$xml), TRUE);

            if (isset($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE'])) { /* RETORNOU ALGUM ERRO. EX: NÃO ENCONTROU O DEFEITO */
                throw new Exception("Não foi possível consultar o defeito. Erro: ".$xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']);
            } else {
                msg_log("Consultando produto Ref: {$referencia} com ".count($xml['NewDataSet']['ZCBSM_DEFEITOSTable'])." defeitos retornados");
                
                foreach ($xml['NewDataSet']['ZCBSM_DEFEITOSTable'] as $ponteiro => $defeitos) {
                    $defeito_constatado_codigo    = trim($defeitos['CODEGRUPPE']);
                    $defeito_constatado_descricao = utf8_decode(trim($defeitos['GRUPPETEXT']));
                    $defeito_codigo               = trim($defeitos['CODE']);
                    $defeito_descricao            = utf8_decode(trim($defeitos['CODETEXT']));
                    $defeito                      = "";
                    $defeito_constatado           = "";

                    msg_log("$ponteiro - Defeito: $defeito_codigo - $defeito_descricao");

                    $res_defeito = pg_execute($con, 'consulta_defeito', array($defeito_codigo));
                    if (pg_num_rows($res_defeito) == 0) {

                        $sql = "INSERT INTO tbl_defeito (
                                    fabrica,
                                    codigo_defeito,
                                    descricao
                                )VALUES(
                                    {$login_fabrica},
                                    '{$defeito_codigo}',
                                    '{$defeito_descricao}'
                                )RETURNING defeito";
                        $res_defeito = pg_query($con, $sql);
                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao tentar cadastrar defeito: $defeito_codigo - $defeito_descricao");
                        }
                        msg_log("Defeito $defeito_codigo - $defeito_descricao inserido com sucesso");
                        $defeito = pg_fetch_result($res_defeito, 0, 'defeito');
                    } else {
                        $defeito = pg_fetch_result($res_defeito, 0, 'defeito');
                    	msg_log("$ponteiro - Defeito da peça ja cadastrato: $defeito_codigo - $defeito_descricao");
		     }
                    msg_log("$ponteiro - Defeito Constatado: $defeito_constatado_codigo - $defeito_constatado_descricao");

                    $res_defeito_constatado = pg_execute($con, 'consulta_defeito_constatado', array($defeito_constatado_codigo));
                    if (pg_num_rows($res_defeito_constatado) == 0) {
                        $sql = "INSERT INTO tbl_defeito_constatado (
                                    fabrica,
                                    codigo,
                                    descricao
                                )VALUES(
                                    {$login_fabrica},
                                    '{$defeito_constatado_codigo}',
                                    '{$defeito_constatado_descricao}'
                                )RETURNING defeito_constatado;";
                        $res_defeito_constatado = pg_query($con, $sql);
                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao tentar cadastrar defeito constatado: $defeito_constatado_codigo - $defeito_constatado_descricao");
                        }
                        msg_log("Defeito Constatado $defeito_constatado_codigo - $defeito_constatado_descricao inserido com sucesso");
                        $defeito_constatado = pg_fetch_result($res_defeito_constatado, 0, 'defeito_constatado');
                    } else {
                        $defeito_constatado = pg_fetch_result($res_defeito_constatado, 0, 'defeito_constatado');
			msg_log("$ponteiro - Defeito Constatado ja cadastrado: $defeito_constatado_codigo - $defeito_constatado_descricao");

		    }

                    msg_log("$ponteiro - Amarrando Defeito Constatado com Familia na tabela diagnostico");

                    if (!empty($defeito_constatado) && !empty($familia)) {
                        $res_diagnostico_defeito_familia = pg_execute($con, 'consulta_diagnostico_defeito_familia', array($defeito_constatado, $familia));
                        if (pg_num_rows($res_diagnostico_defeito_familia) == 0) {
                            $sql = "INSERT INTO tbl_diagnostico (
                                        fabrica,
                                        defeito_constatado,
                                        familia
                                    )VALUES(
                                        {$login_fabrica},
                                        {$defeito_constatado},
                                        {$familia}
                                    );";
                            $res_dia = pg_query($con, $sql);
                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao tentar amarrar Defeito Constatado com Familia");
                            }
                            msg_log("Amarração de Defeito Constatado com Familia efetuada com sucesso");
                        } else {
			    $sql = "UPDATE tbl_diagnostico SET ativo = TRUE, data_atualizacao = now() WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$defeito_constatado} AND familia = {$familia} AND ativo IS NOT TRUE;";
			    $res_dia = pg_query($con, $sql);
                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao atualizar Defeito Constatado com Familia");
                            }
                    	    msg_log("$ponteiro - Amarracao Defeito Constatado atualizada: $defeito_constatado_codigo - $defeito_constatado_descricao");

             		}
                    }

                    msg_log("$ponteiro - Amarrando Defeito Constatado com Familia na tabela diagnostico");

                    if (!empty($defeito_constatado) && !empty($defeito)) {

                        $res_diagnostico_defeito = pg_execute($con, 'consulta_diagnostico_defeito', array($defeito_constatado, $defeito));
                        if (pg_num_rows($res_diagnostico_defeito) == 0) {
                            $sql = "INSERT INTO tbl_diagnostico (
                                        fabrica,
                                        defeito_constatado,
                                        defeito
                                    )VALUES(
                                        {$login_fabrica},
                                        {$defeito_constatado},
                                        {$defeito}
                                    );";
                            $resDia = pg_query($con, $sql);
                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao tentar amarrar Defeito Constatado com Defeito");
                            }
                            msg_log("Amarração de  Defeito Constatado com Defeito efetuada com sucesso");
                        } else {
			    $sql = "UPDATE tbl_diagnostico SET ativo = TRUE, data_atualizacao = now() WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$defeito_constatado} AND defeito = {$defeito} AND ativo IS NOT TRUE;";
                            $resDia = pg_query($con, $sql);
                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao atualizar Defeito Constatado com Defeito");
                            }
                            msg_log("Amarração de Defeito Constatado com Defeito da peca atualizado");
			
			}
                    }
                }//fecha foreach webservice
            }
        } catch (Exception $e) {
            msg_log("Erro: ".$e->getMessage());
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
            $logError->setErrorMessage($e->getMessage());
            $logError->Insert();
        }
    }//fechar
/*
    $nome_arquivo = 'rotina_importa_lista_basica_'.date('Ymd').'.txt';
    $arquivo_log  = "/tmp/$nome_arquivo";

    msg_log("Enviando arquivo para o Tdocs: $nome_arquivo");

    $arquivo = array(
        'tmp_name' => $arquivo_log,
        'name'     => $nome_arquivo,
        'size'     => filesize($arquivo_log),
        'type'     => mime_content_type($arquivo_log),
        'error'    => null
    );

    if (!file_exists($arquivo_log)) {
        system("touch {$arquivo_log}");
    }

    $b = ob_get_contents();

    file_put_contents($arquivo_log, $b, FILE_APPEND);
    ob_end_flush();
    ob_clean();

    if(!$tdocs->uploadFileS3($arquivo, $routine_id)){
        throw new Exception("Não foi possível enviar o arquivo de log para o Tdocs. Erro: ".$tdocs->error);
    }
*/
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
