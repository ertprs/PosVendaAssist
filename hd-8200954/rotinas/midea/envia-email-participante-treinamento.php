<?php

/*
* Includes
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

include dirname(__FILE__) . '/../../class/ComunicatorMirror.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;


try {

    /*ini_set("display_errors", 1);
    error_reporting(E_ALL);*/

    /*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 169;
    $data = date('d-m-Y');

    /**
     * Log da Rotina
     */
    $routine = new Routine();
    $routine->setFactory($fabrica);
    $comunicatorMirror = new ComunicatorMirror();

    $arr = $routine->SelectRoutine("Gera Pedido");
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
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina} | grep -v grep"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

    $count_routine = 0;
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/midea\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }

    $em_execucao = ($count_routine > 4) ? true : false;

    if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $em_execucao == false) {
        $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
        $routine_schedule_log_stopped = $routineScheduleLog->GetRoutineWithoutFinish();

        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_stopped['routine_schedule_log']);
        $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage(utf8_encode('Rotina finalizada'));
        $routineScheduleLog->Update();
    }

    /* Limpando variáveis */
    $routineScheduleLog->setRoutineSchedule(null);
    $routineScheduleLog->setRoutineScheduleLog(null);
    $routineScheduleLog->setDateFinish(null);
    $routineScheduleLog->setStatus(null);
    $routineScheduleLog->setStatusMessage(null);

    if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $em_execucao == true) {
        throw new Exception('Rotina em execução');
    } else {
        $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
        $routineScheduleLog->setDateStart(date("Y-m-d H:i"));
        if (!$routineScheduleLog->Insert()) {
           throw new Exception("Erro ao gravar log da rotina");
        }
        $routine_schedule_log_id = $routineScheduleLog->SelectId();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);
    }

    /*
    * Log
    */
    $logClass = new Log2();

    $logClass->adicionaLog(array("titulo" => "Log erro - Quantidade Mínima de Participantes Midea Carrier")); // Titulo

    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("lucas.silva@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail('helpdesk@telecontrol.com.br');
    }

    /*
    * Cron
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Class Fábrica
    */
    $fabricaClass = new \Posvenda\Fabrica($fabrica);

    /*
    * Resgata o nome da Fabrica
    */
    $fabrica_nome = $fabricaClass->getNome();


    /*
    * começa de fato a Rotina
    */
    $sql_treinamentos   = "SELECT data_inicio,
                            data_fim,
                            vagas_min,
                            local,
                            treinamento,
                            qtde_participante,
                            qtde_inscritos,
                            fabrica,       
                            treinamento_tipo,
                            titulo
                        FROM (
                                SELECT 
                                    tbl_treinamento.data_inicio,
                                    tbl_treinamento.data_fim,
                                    tbl_treinamento.local,
                                    tbl_treinamento.treinamento,
                                    tbl_treinamento.vagas_min,
                                    tbl_treinamento.qtde_participante,
                                    tbl_treinamento.fabrica,
                                    tbl_treinamento.treinamento_tipo,
                                    tbl_treinamento.titulo,
                                    (
                                        SELECT COUNT(*)
                                        FROM tbl_treinamento_posto
                                        WHERE tbl_treinamento_posto.treinamento = tbl_treinamento.treinamento
                                        AND   tbl_treinamento_posto.ativo IS TRUE
                                    ) AS qtde_inscritos
                                FROM tbl_treinamento
                                WHERE tbl_treinamento.ativo IS TRUE
                            ) x
                        WHERE qtde_inscritos >= vagas_min
                            AND data_inicio::date = current_date + interval '2 days'
                            AND fabrica = {$fabrica};";
    $query_treinamentos = pg_query($con,$sql_treinamentos);
    if (pg_num_rows($query_treinamentos) > 0)
    {
        $treinamento_dados = pg_fetch_array($query_treinamentos);

        do
        {
            $id_treinamento   = $treinamento_dados['treinamento'];
            $data_inicial     = $treinamento_dados['data_inicio'];
            $data_final       = $treinamento_dados['data_fim'];
            $local            = $treinamento_dados['local'];
            $tituloc          = str_replace("'","",$treinamento_dados['titulo']);

            $data_inicial_old = explode(' ', $data_inicial);
            $hora_inicial     = $data_inicial_old[1];
            $hora_inicial     = explode(":", $hora_inicial);
            $data_inicial     = explode('-', $data_inicial_old[0]);

            $data_final_old   = explode(' ', $data_final);
            $hora_final       = $data_final_old[1];
            $hora_final       = explode(":", $hora_final);
            $data_final       = explode('-', $data_final_old[0]);
            $local            = str_replace("'", "", $local);

            switch ($treinamento_dados['treinamento_tipo']) {
                case '8': case 8: $tipo_treinamento = "PALESTRA";     break;
                case '9': case 9: $tipo_treinamento = "TREINAMENTO";  break;
            }

            switch($data_inicial[1]){ 
                case '01': $mes = 'Janeiro'; break; case '02': $mes = 'Fevererio'; break; case '03': $mes = 'Março';    break;
                case '04': $mes = 'Abril';   break; case '05': $mes = 'Maio';      break; case '06': $mes = 'Junho';    break;
                case '07': $mes = 'Julho';   break; case '08': $mes = 'Agosto';    break; case '09': $mes = 'Setembro'; break;
                case '10': $mes = 'Outubro'; break; case '11': $mes = 'Novembro';  break; case '12': $mes = 'Dezembro'; break;
            }

            if ($tipo_treinamento == 'TREINAMENTO'){
                $treinamento_tipo_nome_aux = 'o Treinamento';
            }else if ($tipo_treinamento == 'PALESTRA'){
                $treinamento_tipo_nome_aux = 'a Palestra';
            }
            
            /* query para obter informações dos técnicos */
            $sql_tecnicos   = "SELECT 
                                tecnico_nome,
                                tecnico_email
                            FROM tbl_treinamento_posto
                                WHERE treinamento = {$id_treinamento}
                                    AND ativo IS TRUE
                                    AND tecnico_email IS NOT NULL";
            $query_tecnicos = pg_query($con,$sql_tecnicos);
            if (pg_num_rows($query_tecnicos) > 0)
            {
                $tecnico_dados = pg_fetch_array($query_tecnicos);

                do
                {
                    $tecnico_nome  = $tecnico_dados['tecnico_nome'];
                    $tecnico_email = $tecnico_dados['tecnico_email'];
                    $titulo_email  = "Confirmação d$treinamento_tipo_nome_aux";

                    /* Faz o envio do e-mail de confirmação */
                    $msg_email = "<h2>Olá, ".$tecnico_nome."</h2>
                        <p>Informamos que ".$treinamento_tipo_nome_aux." <b>".$tituloc."</b> esta confirmado para o dia <b>".$data_inicial[2]." a ".$data_final[2]." de ".$mes."</b>, na <b>".$local."</b>, das <b>".$hora_inicial[0].":".$hora_inicial[1]." às ".$hora_final[0].":".$hora_final[1]."h</b>.</p>
                        <p><center><b>Contamos com sua presença</b></center></p>

                        <div align=right>
                            <span>Contato: <a href=mailto:treinamentostecnicos©mideacarrier.com target=_top>treinamentostecnicos@mideacarrier.com</a><span> <br />
                            <span>Fone: (51) 3477-9014</span>
                        </div>
                    ";
                    
                    if (!empty($tecnico_email)){
                        try {
                            $comunicatorMirror->post($tecnico_email, utf8_encode("$titulo_email"), utf8_encode("$msg_email"), "smtp@posvenda");
                        } catch (\Exception $e) {
                        }
                    }else{
                        $msg_erro["msg"][] = "Email com informações de $titulo_email não enviado para o técnico $tecnico_nome. Técnico sem email cadastrado";
                    }

                }while($tecnico_dados = pg_fetch_array($query_tecnicos));
            }

        }while($treinamento_dados = pg_fetch_array($query_treinamentos));
    }

    $routineScheduleLog->setTotalRecord($total_dados);
    $routineScheduleLog->setTotalRecordProcessed($total_dados_sucesso);
    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");

    if(!empty($msg_erro)){
        $logClass->adicionaLog(implode("<br />", $msg_erro));

        if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          $logClass->enviaEmails();
        }
    }

    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
