<?php

namespace Posvenda;

use Posvenda\Model\RoutineSchedule as RoutineScheduleModel;

if (!class_exists("TcComm")) {
    include_once __DIR__.'/../../../class/communicator.class.php';
}

class RoutineSchedule {

    private $routine_schedule;
    private $routine;
    private $month_day;
    private $week_day;
    private $hour;
    private $minute;
    private $active;
    private $create_at;

    public function getRoutineSchedule() {
        return $this->routine_schedule;
    }

    public function setRoutineSchedule($routine_schedule = null) {
        $this->routine_schedule = $routine_schedule;
    }

    public function getRoutine() {
        return $this->routine;
    }

    public function setRoutine($routine = null) {
        $this->routine = $routine;
    }

    public function getMonthDay() {
        return $this->month_day;
    }

    public function setMonthDay($month_day = null) {
        $this->month_day = $month_day;
    }

    public function getWeekDay() {
        return $this->week_day;
    }

    public function setWeekDay($week_day = null) {
        $this->week_day = $week_day;
    }

    public function getHour() {
        return $this->hour;
    }

    public function setHour($hour = null) {
        $this->hour = $hour;
    }

    public function getMinute() {
        return $this->minute;
    }

    public function setMinute($minute = null) {
        $this->minute = $minute;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active = null) {
        $this->active = $active;
    }

    public function getCreateAt() {
        return $this->create_at;
    }

    public function getAll($validate_value = false) {

        $arr = array('routine_schedule' => $this->getRoutineSchedule(),
                            'routine' => $this->getRoutine(),
                            'month_day' => $this->getMonthDay(),
                            'week_day' => $this->getWeekDay(),
                            'hour' => $this->getHour(),
                            'minute' => $this->getMinute(),
                            'active' => $this->getActive(),
                            'create_at' => $this->getCreateAt());

        if ($validate_value === false) {
            return $arr;
        } else {
            return array_map(function($c) {
                if (strlen($c) > 0) {
                    return true;
                }
            }, $arr);
        }
    }

    public function Insert() {
        $oRoutineScheduleModel = new RoutineScheduleModel();
        $pdo = $oRoutineScheduleModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'routine_schedule') {
                $columnsIns[] = "$key";
                $valuesIns[] = (is_string($value)) ? pg_escape_literal($value) : "$value";
            }
        }

        $columnsInsert = implode(", ", $columnsIns);
        $valuesInsert = implode(", ", $valuesIns);

        $sql = "INSERT INTO tbl_routine_schedule ({$columnsInsert}) VALUES ({$valuesInsert});";
        $query = $pdo->query($sql);

        if ($query != false) {
            return true;
        } else {
            return false;
        }

    }

    public function Update() {
        $oRoutineScheduleModel = new RoutineScheduleModel();
        $pdo = $oRoutineScheduleModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'routine_schedule') {
                $camposUp[] = "$key = ".((is_string($value)) ? pg_escape_literal($value) : "$value");
            }
        }

        $setUpdate = implode(", ", $camposUp);

        $sql = "UPDATE tbl_routine_schedule SET {$setUpdate} WHERE routine_schedule = {$this->getRoutineSchedule()};";
        $query = $pdo->query($sql);

        if ($query != false) {
            return true;
        } else {
            return false;
        }
    }

    public function SelectId($next = false) {
        $oRoutineScheduleModel = new RoutineScheduleModel();
        $pdo = $oRoutineScheduleModel->getPDO();

        $sql = "SELECT last_value AS id FROM seq_routine_schedule;";
        $query = $pdo->query($sql);

        if ($query == false) {
            return false;
        }

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            if ($next == true) {
                return $res[0]['id'] + 1;
            } else {
                return $res[0]['id'];
            }
        } else {
            return false;
        }
    }

    public function SelectRoutineSchedule() {
        $oRoutineScheduleModel = new RoutineScheduleModel();
        $pdo = $oRoutineScheduleModel->getPDO();

		if(empty($this->getRoutine())) {
			return false;
		}
        $sql = "
            SELECT
                routine_schedule
            FROM tbl_routine_schedule
            WHERE week_day = {$this->getWeekDay()}
            AND routine = {$this->getRoutine()}
            AND active IS TRUE;
        ";

        $query = $pdo->query($sql);

        if ($query == false) {
            return false;
        }
        
        if ($query->rowCount() > 0) {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
            return $res[0]['routine_schedule'];
        } else {
            return false;
        }
    }

    public function SelectRoutineScheduleWithContext($fabrica = null) {
        $oRoutineScheduleModel = new RoutineScheduleModel();
        $pdo = $oRoutineScheduleModel->getPDO();

        if ($this->getRoutine() != '') {
            $whereRoutine = "AND r.routine = {$this->getRoutine()}";
        }

        if ($fabrica != null) {
            $whereFactory = "AND r.factory = {$fabrica}";
        }

        $sql = "
            SELECT
                r.routine,
                r.context,
                rs.routine_schedule,
                rs.month_day,
                rs.week_day,
                rs.hour,
                rs.minute
            FROM tbl_routine r
            LEFT JOIN tbl_routine_schedule rs USING(routine)
            WHERE (rs.active IS TRUE AND r.active IS TRUE)
            {$whereFactory}
            {$whereRoutine}
            ORDER BY rs.week_day, rs.hour;
        ";

        $query = $pdo->query($sql);

        if ($query == false) {
            return false;
        }

        if ($query->rowCount() > 0) {
            return $query->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }

    }
    
    public function enviaEmail($fabrica = null, $arq_email = null, $baixa=false) {
        $tem_erro = false;
        $oRoutineScheduleModel = new RoutineScheduleModel();
        $pdo = $oRoutineScheduleModel->getPDO();

        if (count($arq_email) > 0) {
            $hoje = date("d/m/Y");
                
            if ($fabrica == 158) {
                if($baixa==false){
                    $emails = "jesse.silva@imberacooling.com;ana.mariadecaires@imberacooling.com;sabrina.donascimento@imberacooling.com;helpdesk@telecontrol.com.br;vitor.martin@imberacooling.com;";
                }else{
                    $emails = "telecontrol.monitor@imberacooling.com;";
                }
            }

            $body .= "<br> <b>Rotina:</b> ".$arq_email[1]['rotina'].".<br><br>";

            $body .= " <table>
                        <thead>
                            <tr>
                                <th>OS KOF</th>
                                <th>".utf8_decode("Localização")."</th>
                                <th>".utf8_decode("Data da Solicitação")."</th>
                                <th>".utf8_decode("Data da Integração Telecontrol")."</th>
                                <th>Erro</th>
                            </tr>
                        </thead>
                        <tbody>";

            foreach ($arq_email as $key => $value) {
                if (count($value['erros']) > 0) {
                    $tem_erro = true;
                    $body .= "<tr>";
                    $body .= "<td>".$value['os_kof']."</td>";
                    $body .= "<td>".$value['localizacao']."</td>";
                    $body .= "<td>".$value['data_abertura']."</td>";
                    $body .= "<td>".$hoje."</td>";
                    $erros = implode(' , ', $value['erros']);
                    $body .= "<td>".$erros."</td>";   
                    $body .= "</tr>";
                }
                
            }

            $body .= "  </tbody>
                      </table>";

            if ($tem_erro) {
                $mailer = new \TcComm("smtp@posvenda");
                $assunto = "Log de Interação Telecontrol";
                $res = $mailer->sendMail(
                    $emails,
                    $assunto,
                    $body,
                    "noreply@telecontrol.com.br"
                );
            }
        }
    }
}
