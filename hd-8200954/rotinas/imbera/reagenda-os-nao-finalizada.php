<?php

require __DIR__.'/../../classes/autoload.php';

use Posvenda\Cockpit;
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

$debug = true;

$routine = new Routine();
$routine->setFactory(158);

$arr = $routine->SelectRoutine("Reagenda OS não finalizada");
$routine_id = $arr[0]["routine"];

if (!strlen($routine_id)) {
    throw new Exception("Rotina não encontrada");
}

$routineSchedule = new RoutineSchedule();
$routineSchedule->setRoutine($routine_id);
$routineSchedule->setWeekDay(date("w"));

$routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

if (!strlen($routine_schedule_id)) {
    throw new Exception("Agendamento da rotina não encontrado");
}

$routineScheduleLog = new Log();
$routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
$routineScheduleLog->setDateStart(date("Y-m-d H:i"));

if (!$routineScheduleLog->Insert()) {
    throw new Exception("Erro ao gravar log da rotina");
}

$routine_schedule_log_id = $routineScheduleLog->SelectId();
$routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);

try {
    $cockpit = new Cockpit(158);

    $reagendar_os = true;
    $array_os = $cockpit->osAgendadaNaoFinalizada($reagendar_os);

    if (count($array_os) > 0) {
        foreach ($array_os as $os) {
            $pdo = $cockpit->model->getPDO();
            
            //Atualiza agenda no telecontrol
            $data = date("Y-m-d 12:00:00", strtotime("+1 day"));
            
            //Reagenda na persys
            $reagenda = $cockpit->reagendaOs($os["os"], date("d-m-Y", strtotime("+1 day")));

            if (empty($reagenda)) {
                throw new Exception("Erro ao reagendar a OS {$os['os']}");
            } else if ($reagenda["error"]) {
                throw new Exception("Erro ao reagendar a OS {$os['os']}: {$persys['error']['message']}");
            }

            $sql = "
                UPDATE tbl_tecnico_agenda SET
                    data_agendamento = '{$data}'
                WHERE fabrica = 158
                AND os = {$os['os']}
            ";
            $qry = $pdo->query($sql);

            if (!$qry) {
                throw new Exception("Erro ao reagendar a OS {$os['os']}");
            }
        }
    }

    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
} catch(Exception $e) {
    $routineScheduleLog->setStatus(0);
    $routineScheduleLog->setStatusMessage("Erro ao executar a rotina, erro: {$e->getMessage()}");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
}
