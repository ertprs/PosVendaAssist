<?php

require __DIR__.'/../../classes/autoload.php';

use Posvenda\Cockpit;
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

date_default_timezone_set("America/Sao_Paulo");

$routine = new Routine();
$routine->setFactory(158);

$arr = $routine->SelectRoutine("Atualizar posição inicial dos técnicos");
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

    $tecnicos = $cockpit->getTecnicos();

    foreach ($tecnicos as $tecnico) {
        $pdo = $cockpit->model->getPDO();

        $sql = "
            SELECT latitude, longitude
            FROM tbl_posto_fabrica
            WHERE posto = {$tecnico['posto']}
            AND fabrica = 158
        ";
        $query = $pdo->query($sql);

        if (!$query || $query->rowCount() == 0) {
            throw new Exception("Não foi possível buscar o Posto do técnico");
        }

        $res = $query->fetch();

        if ($tecnico["latitude"] != $res["latitude"] || $tecnico["longitude"] != $res["longitude"]) {
            $sql = "
                UPDATE tbl_tecnico SET
                    latitude = {$res['latitude']},
                    longitude = {$res['longitude']}
                WHERE fabrica = 158
                AND tecnico = {$tecnico['tecnico']}
            ";
            $query = $pdo->query($sql);

            if (!$query) {
                throw new Exception("Não foi possível atualizar a localização dos técnicos");
            }
        }
    }

    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
} catch(Exception $e) {
    print_r($e->getMessage());

    $routineScheduleLog->setStatus(0);
    $routineScheduleLog->setStatusMessage("Erro ao executar a rotina, erro: {$e->getMessage()}");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
}
