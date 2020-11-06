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

$b = "";

function msg_log($msg){
    global $b;
    $b .= "\n".date('H:i')." - ".$msg;
}

try{
    msg_log('Inicia rotina de importação de lista básica');
    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Importa lista basica 2");
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

	$routine_schedule_log_id = $routineScheduleLog->Insert();

        if ($routine_schedule_log_id === false) {
            throw new Exception("Erro ao gravar log da rotina");
        }

        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);

    }

    if ($serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
    }

    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));
    $data_consulta = date('Y-m-d', strtotime(date('Y-m-d'). '-1 week')); /* CONSULTA ATÉ 1 SEMANA */

    pg_prepare($con, 'consulta_peca', "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = $1");
    pg_prepare($con, 'consulta_lista_basica', "SELECT lista_basica FROM tbl_lista_basica WHERE fabrica = {$login_fabrica} AND peca = $1 AND produto = $2");

    msg_log('Inicia consulta SQL na tabela tbl_produto');
    $sql = "
	SELECT
		tbl_produto.produto,
		tbl_produto.referencia 
	FROM tbl_produto
	WHERE fabrica_i = {$login_fabrica}
	AND ativo IS TRUE;
    ";

    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);
    msg_log("Iniciando consulta em $rows linhas");
    for ($i = 0; $i < $rows; $i++) {
        try {
            $referencia   = pg_fetch_result($res, $i, 'referencia');
            $produto     = pg_fetch_result($res, $i, 'produto');
            msg_log('Iniciando requisição à API');
            $params       = new SoapVar(
                "<ns1:xmlDoc><criterios><PV_MATNR>{$referencia}</PV_MATNR><PV_SERNR>*</PV_SERNR ></criterios></ns1:xmlDoc>", XSD_ANYXML
            );
            $request   = array('xmlDoc' => $params);
            $result    = $client->PesquisaSubstituicao($request);
            $dados_xml = $result->PesquisaSubstituicaoResult->any;
            $xml       = simplexml_load_string($dados_xml);
            $xml       = json_decode(json_encode((array)$xml), TRUE);

            if (isset($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE'])) { /* RETORNOU ALGUM ERRO. EX: NÃO ENCONTROU A PEÇA */
                throw new Exception("Não foi possível consultar a lista básica produto. Erro: ".$xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']);
            }else{
                msg_log("Consultando produto Ref: $referencia, Série: * com ".count($xml['NewDataSet']['ZCBSM_MATERIAIS_EQUIPAMENTOTABLE'])." peças retornadas");
                foreach ($xml['NewDataSet']['ZCBSM_MATERIAIS_EQUIPAMENTOTABLE'] as $ponteiro => $pecas) {
                    $pecaDescricao = utf8_encode(trim($pecas['MAKTX']));
                    $pecaUnidade   = trim($pecas['MEINS']);
                    $qtde          = (int)trim($pecas['MNGKO']);
                    $codigo        = trim($pecas['MATNR']);

                    msg_log("$ponteiro - Peça: $codigo - $pecaDescricao");

                    $res_peca = pg_execute($con, 'consulta_peca', array($codigo));
                    if (pg_num_rows($res_peca) == 0) {
                        $produto_acabado = (strtolower($codigo) == strtolower($referencia)) ? 'true' : 'false';

                        $sql = "INSERT INTO tbl_peca(
                                    fabrica,
                                    referencia,
                                    descricao,
                                    origem,
                                    unidade,
                                    produto_acabado
                                )VALUES(
                                    {$login_fabrica},
                                    '{$codigo}',
                                    '{$pecaDescricao}',
                                    'NAC',
                                    '{$pecaUnidade}',
                                    {$produto_acabado}
                                )RETURNING peca";
                        $res_peca = pg_query($con, $sql);
                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao tentar cadastrar a peça $codigo - $pecaDescricao");
                        }
                        msg_log("Peça $codigo - $pecaDescricao inserida com sucesso");
                    }
                    $peca = pg_fetch_result($res_peca, 0, 'peca');
                    if (isset($produto_acabado) && $produto_acabado) {
                        msg_log("Marcação de produto acabado marcado para a peça: $peca");
                    }

                    $lista_basica  = 0;
                    $res_lista_basica = pg_execute($con, 'consulta_lista_basica', array($peca, $produto));
                    if (pg_num_rows($res_lista_basica) == 0) {
                        $sql = "INSERT INTO tbl_lista_basica(
                                    fabrica,
                                    peca,
                                    produto,
                                    qtde
                                )VALUES(
                                    {$login_fabrica},
                                    '{$peca}',
                                    {$produto},
                                    {$qtde}
                                );";
                    }else{
                        $lista_basica = pg_fetch_result($res_lista_basica, 0, 'lista_basica');
                        $sql = "UPDATE tbl_lista_basica SET
                                    qtde = {$qtde}
                                WHERE lista_basica = {$lista_basica}";
                    }
                    pg_query($con, $sql);
                    if (pg_last_error() > 0) { /* ERRO AO INSERIR/ALTERAR */
                        $msg = ($lista_basica == 0) ? 'inserir' : 'alterar';
                        throw new Exception("Erro ao tentar $msg o registro. Erro: ".pg_last_error());
                    }
                    if ($lista_basica == 0) {
                        msg_log("Foi inserido um registro na tabela tbl_lista_basica. Peca: $peca, produto: $produto");
                    }else{
                        msg_log("Foi alterado um registro na tabela tbl_lista_basica. Registro: $lista_basica, Quantidade: $qtde");
                    }
                }
            }
        } catch (Exception $e) {
            msg_log("Erro: ".$e->getMessage());
            $logError = new \Posvenda\LogError();
            $logError->setRoutineScheduleLog($routine_schedule_log_id);
            $logError->setErrorMessage($e->getMessage());
            $logError->Insert();
        }
    }

    $nome_arquivo = 'rotina_importa_lista_basica2_'.date('Ymd').'.txt';
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

    file_put_contents($arquivo_log, $b, FILE_APPEND);

    if(!$tdocs->uploadFileS3($arquivo, $routine_id)){
        throw new Exception("Não foi possível enviar o arquivo de log para o Tdocs. Erro: ".$tdocs->error);
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
    $logError->setRoutineScheduleLog($routine_schedule_log_id);
    $logError->setErrorMessage($e->getMessage());
    $logError->Insert();

    $status_final = 2;

    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    $routineScheduleLog->Update();
}

$phpCron->termino();
?>
