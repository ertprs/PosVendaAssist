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
    echo "\n".date('H:m')." - $msg";
}

//ob_start();
try{

    msg_log('Inicia rotina de importação de produtos');

    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Importa produto");
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

    // Limpando variáveis 
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
        $logError = new \Posvenda\LogError();

    }

    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }

    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));
    $data_consulta = date('Y-m-d', strtotime(date('Y-m-d'). '-1 week'));

    try {
        
        msg_log('Iniciando requisição à API');

        $request   = array('PI_MATNR' => '', 'PI_DT_ENVIO' => '');
        $result    = $client->Z_CB_TC_PRODUTOS_ACABADOS($request);
        $dados_xml = $result->Z_CB_TC_PRODUTOS_ACABADOSResult->any;

        $xml = simplexml_load_string($dados_xml);
        $xml = json_decode(json_encode((array)$xml), TRUE);

        if (count($xml["NewDataSet"]["PE_PRODUTOS_ACABADOSTABLE"]) == 0) {
            throw new Exception("Não foi possível consultar o produto. Erro: ".$xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']);
        } else {

            $sqlLinhaInt = "SELECT linha FROM tbl_linha WHERE fabrica = {$login_fabrica} AND ativo IS FALSE;";
            $resLinhaInt = pg_query($con, $sqlLinhaInt);
            $linhaInt = pg_fetch_result($resLinhaInt, 0, 'linha');

    	    $registrosInseridos = 0;
    	    $registrosAtualizados = 0;

            $xmlProdutos = array();
            if (!empty($xml['NewDataSet']['PE_PRODUTOS_ACABADOSTABLE']['MATNR'])) {
                $xmlProdutos[] = $xml['NewDataSet']['PE_PRODUTOS_ACABADOSTABLE'];
            } else {
                $xmlProdutos = $xml['NewDataSet']['PE_PRODUTOS_ACABADOSTABLE'];
            }

            $count = count($xmlProdutos);

            foreach ($xmlProdutos as $ponteiro => $produtos) {
                try{

                    $descricaoProduto  = addslashes(utf8_decode(trim($produtos['DESCRICAO'])));
                    $referenciaProduto = trim($produtos['MATNR']);
                    $referenciaProdutoPesq = str_replace("-", "YY", $referenciaProduto);
                    if (strpos($referenciaProdutoPesq, "-") !== false) {
                        $auxReferenciaProduto = preg_replace('/(-*?)$/', 'YY', $referenciaProduto);
                    } else {
                        $auxReferenciaProduto = $referenciaProduto;
                    }
                    $maoObra = trim($produtos['MAO_OBRA']);
                    $garantia = (empty(trim($produtos['GARANTIA']))) ? 0 : trim($produtos['GARANTIA']);
                    $classificacaoFiscal = trim($produtos['STEUC']);
                    $voltagem = trim($produtos['VOLTAGEM']);
                    $marca = trim($produtos['MARCA']);
                    $numeroSerieObrigatorio = (!empty($produtos['NR_SERIE_OBRIGATORIO'])) ? $produtos['NR_SERIE_OBRIGATORIO'] : '';
                    $numeroSerieObrigatorio = ($numeroSerieObrigatorio == 'S') ? 't' : 'f';
		    $nomeComercial = trim($produtos['NOME_COMERCIAL']);
		    $ipi = trim($produtos['IPI']);
		    $ipi = (empty($ipi)) ? 0 : $ipi;

                    $voltagensTC = array('12 V','110 V','127 V','127/220 V','220 V','220/380 V','230 V','380 V','400 V','440 V','690 V','BIVOLT','BIVOLT AUT','BATERIA','PILHA');

                    $auxVoltagem = "";
                    foreach ($voltagensTC as $voltagemTC) {
                        if (strpos($voltagemTC, $voltagem) !== false) {
                            $auxVoltagem = $voltagemTC;
                            continue;
                        }
                    }

                    msg_log("Verificando existência da marca {$marca}");
                    $sqlMarca = "SELECT marca FROM tbl_marca WHERE fabrica = {$login_fabrica} AND TRIM(LOWER(nome)) = LOWER('{$marca}');";
                    $resMarca = pg_query($con, $sqlMarca);

                    if (pg_num_rows($resMarca) > 0) {
                        $marcaId = pg_fetch_result($resMarca, 0, 'marca');
                        msg_log("Marca {$marca} existe");
                    } else {
                        $marcaId = 'NULL';
                        msg_log("Marca {$marca} inexistente");
                    }

                    msg_log("{$ponteiro} - Produto: {$referenciaProduto} - {$descricaoProduto}");

                    $sqlProduto = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND (UPPER(REPLACE(tbl_produto.referencia_pesquisa, '-', 'YY')) = UPPER('{$referenciaProdutoPesq}') OR UPPER(REPLACE(tbl_produto.referencia_fabrica, '-', 'YY')) = UPPER('{$referenciaProdutoPesq}') OR UPPER(REPLACE(tbl_produto.referencia, '-', 'YY')) = UPPER('{$referenciaProdutoPesq}'));";
                    $resProduto = pg_query($con, $sqlProduto);

                    if (pg_num_rows($resProduto) == 0) {
                        $registrosInseridos++;

                        $sql = "
                            INSERT INTO tbl_produto(
                                fabrica_i,
                                referencia,
                                descricao,
                                numero_serie_obrigatorio,
                                mao_de_obra,
                                mao_de_obra_admin,
                                garantia,
                                linha,
				ativo,
				uso_interno_ativo,
				lista_troca,
                                classificacao_fiscal,
                                voltagem,
                                marca,
				nome_comercial,
				ipi
                            )VALUES(
                                {$login_fabrica},
                                '{$auxReferenciaProduto}',
                                fn_retira_especiais('{$descricaoProduto}'),
                                '{$numeroSerieObrigatorio}',
                                {$maoObra},
                                0.00,
                                {$garantia},
                                {$linhaInt},
				't',
				't',
				't',
                                '{$classificacaoFiscal}',
                                '{$auxVoltagem}',
                                {$marcaId},
				'{$nomeComercial}',
				{$ipi}
                            );
                        ";
                        
                        msg_log("Produto $referenciaProduto - $descricaoProduto inserido com sucesso");

                        $txtRetorno = "cadastrar";

                    } else {
                        $registrosAtualizados++;

                        $produto = pg_fetch_result($resProduto, 0, 'produto');
                        
                        $sql = "
                            UPDATE tbl_produto SET
                                referencia = '{$auxReferenciaProduto}',
                                descricao = fn_retira_especiais('{$descricaoProduto}'),
                                numero_serie_obrigatorio = '{$numeroSerieObrigatorio}',
                                mao_de_obra = {$maoObra},
				ativo = 't',
				uso_interno_ativo = 't',
				lista_troca = 't',
                                classificacao_fiscal = '{$classificacaoFiscal}',
                                voltagem = '{$auxVoltagem}',
                                marca = {$marcaId},
				nome_comercial = '{$nomeComercial}',
				ipi = {$ipi},
				data_atualizacao = now()
                            WHERE fabrica_i = {$login_fabrica} 
                            AND produto = {$produto};
                        ";
                        
                        msg_log("Produto {$referenciaProduto} - {$descricaoProduto} atualizado com sucesso");

                        $txtRetorno = "atualizar";
                        
                    }

                    pg_query($con, $sql);

                    if (pg_last_error()) {
                        throw new Exception("Erro ao tentar {$txtRetorno} o produto {$referenciaProduto} - {$descricaoProduto}");
                    }

                } catch (Exception $e) {
                    msg_log("Erro: ".$e->getMessage());
                    $logError->setRoutineScheduleLog($routine_schedule_log_id);
                    $logError->setErrorMessage($e->getMessage());
                    $logError->Insert();
                }
            }
            msg_log("Registros Inseridos -> {$registrosInseridos} e Registros Atualizados -> {$registrosAtualizados}");
        }
    } catch (Exception $e) {
        msg_log("Erro: ".$e->getMessage());
        $logError->setRoutineScheduleLog($routine_schedule_log_id);
        $logError->setErrorMessage($e->getMessage());
        $logError->Insert();
    }

    msg_log("Arquivo enviado para o Tdocs.");
    if (!isset($status_final)) {
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage('Rotina finalizada');
        $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
        $routineScheduleLog->Update();
    }
} catch (Exception $e) {
    msg_log("Erro: ".$e->getMessage());
    $logError->setRoutineScheduleLog($routine_schedule_log_id);
    $logError->setErrorMessage($e->getMessage());
    $logError->Insert();

    $status_final = 2;

    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    $routineScheduleLog->Update();
}

$phpCron->termino();
?>
