<?php

namespace Posvenda;

use Posvenda\Model\Log as LogModel;

class Log {

    private $routine_schedule_log;
    private $routine_schedule;
    private $date_start;
    private $date_finish;
    private $file_name;
    private $total_line_file;
    private $total_record;
    private $total_record_processed;
    /**
     * como não existe tabela de status, segue a definição de status
     * 0 = erro
     * 1 = processado (ok)
     * 2 = processado parcial
     */
    private $status;
    private $status_message;
    private $tdocs;
    private $create_at;

    public function __construct() {
	date_default_timezone_set("America/Sao_Paulo");
    }

    public function getRoutineScheduleLog() {
        return $this->routine_schedule_log;
    }

    public function setRoutineScheduleLog($routine_schedule_log = null) {
        $this->routine_schedule_log = $routine_schedule_log;
    }

    public function getRoutineSchedule() {
        return $this->routine_schedule;
    }

    public function setRoutineSchedule($routine_schedule = null) {
        $this->routine_schedule = $routine_schedule;
    }

    public function getDateStart() {
        return $this->date_start;
    }

    public function setDateStart($date_start = null) {
        $this->date_start = $date_start;
    }

    public function getDateFinish() {
        return $this->date_finish;
    }

    public function setDateFinish($date_finish = null) {
        $this->date_finish = $date_finish;
    }

    public function getFileName() {
        return $this->file_name;
    }

    public function setFileName($file_name = null) {
        $this->file_name = $file_name;
    }

    public function getTotalLineFile() {
        return $this->total_line_file;
    }

    public function setTotalLineFile($total_line_file = null) {
        $this->total_line_file = $total_line_file;
    }

    public function getTotalRecord() {
        return $this->total_record;
    }

    public function setTotalRecord($total_record = null) {
        $this->total_record = $total_record;
    }

    public function getTotalRecordProcessed() {
        return $this->total_record_processed;
    }

    public function setTotalRecordProcessed($total_record_processed = null) {
        $this->total_record_processed = $total_record_processed;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status = null) {
        $this->status = $status;
    }

    public function getStatusMessage() {
        return $this->status_message;
    }

    public function setStatusMessage($status_message = null) {
        $this->status_message = $status_message;
    }

    public function getTDocs() {
        return $this->tdocs;
    }

    public function setTDocs($tdocs = null) {
        $this->tdocs = $tdocs;
    }

    public function getCreateAt() {
        return $this->create_at;
    }

    public function setCreateAt() {
	$this->create_at = date("Y-m-d H:i:s");
    }

    public function getAll($validate_value = false) {

        $arr = array(
            'routine_schedule_log' => $this->getRoutineScheduleLog(),
            'routine_schedule' => $this->getRoutineSchedule(),
            'date_start' => $this->getDateStart(),
            'date_finish' => $this->getDateFinish(),
            'file_name' => $this->getFileName(),
            'total_line_file' => $this->getTotalLineFile(),
            'total_record' => $this->getTotalRecord(),
            'total_record_processed' => $this->getTotalRecordProcessed(),
            'status' => $this->getStatus(),
            'status_message' => $this->getStatusMessage(),
            'tdocs' => $this->getTDocs(),
            'create_at' => $this->getCreateAt()
        );

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
        $oLogModel = new LogModel();
        $pdo = $oLogModel->getPDO();

        $this->setCreateAt();

        $campos = $this->getAll();

        if (!strlen(trim($campos['routine_schedule']))) {
            return false;
        }

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'routine_schedule_log') {
                $columnsIns[] = "$key";
                $valuesIns[] = (is_string($value)) ? pg_escape_literal($value) : "$value";
            }
        }

        $columnsInsert = implode(", ", $columnsIns);
        $valuesInsert = implode(", ", $valuesIns);

        $sql = "INSERT INTO tbl_routine_schedule_log ({$columnsInsert}) VALUES ({$valuesInsert}) RETURNING routine_schedule_log;";
        $query = $pdo->query($sql);

        if ($query != false) {
            $res = $query->fetch(\PDO::FETCH_ASSOC);
            return $res['routine_schedule_log'];
        } else {
            return false;
        }
    }

    public function Update() {
        $oLogModel = new LogModel();
        $pdo = $oLogModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'routine_schedule_log') {
                $camposUp[] = "$key = ".((is_string($value)) ? pg_escape_literal($value) : "$value");
            }
        }

        $setUpdate = implode(", ", $camposUp);

        if (!empty($this->getRoutineScheduleLog())) {
            $sql = "
                UPDATE tbl_routine_schedule_log SET
                    {$setUpdate}
                WHERE routine_schedule_log = {$this->getRoutineScheduleLog()};";
            $query = $pdo->query($sql);
        }

        if ($query != false) {
            return true;
        } else {
            return false;
        }
    }

    public function SelectId($next = false) {
        $oLogModel = new LogModel();
        $pdo = $oLogModel->getPDO();

        $sql = "SELECT last_value AS id FROM seq_routine_schedule_log;";

        $query = $pdo->query($sql);

        if ($query == false) {
            return false;
        }

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            if ($next == true) {
                return $res[0]["id"] + 1;
            } else {
                return $res[0]["id"];
            }
        } else {
            return false;
        }
    }

    function SelectRoutinesLog($routines_schedule = null, $data_inicial = null, $data_final = null, $tipo_ordem = null, $familia = null, $factory = null, $id = null) {
        $oLogModel = new LogModel();
        $pdo = $oLogModel->getPDO();

        if (($data_inicial != null && strlen($data_inicial) > 0) && ($data_final != null && strlen($data_final) > 0)) {
            $whereData = "AND rsl.date_start BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'";
        }

        if ($this->getStatus() != "") {
            $whereStatus = "AND rsl.status = {$this->getStatus()}";
        }

        if ($tipo_ordem != null) {
            $whereTipoOrdem = "AND rsle.contents LIKE '%{$tipo_ordem}%'";
        }

        if ($familia != null) {
            if ($familia == "REFRIGERADOR") {
                $familia = "geladeira";
            }

            $familia = strtolower($familia);

            $whereFamilia = "AND LOWER(rsle.contents) ~ '{$familia}'";
        }
        
        if (!is_null($id)) {
            $whereId = "AND rsl.routine_schedule_log = {$id}";
        }

        if ($routines_schedule != null) {

            $sql = "
                SELECT DISTINCT
                    r.context,
                    rsl.routine_schedule_log,
                    TO_CHAR(rsl.date_start, 'DD/MM/YYYY HH24:MI:SS') AS date_start,
                    TO_CHAR(rsl.date_finish, 'DD/MM/YYYY HH24:MI:SS') AS date_finish,
                    rsl.file_name,
                    rsl.total_line_file,
                    rsl.total_record,
                    rsl.total_record_processed,
                    rsl.status,
                    rsl.status_message
                FROM tbl_routine_schedule_log rsl
                LEFT JOIN tbl_routine_schedule_log_error rsle ON rsle.routine_schedule_log = rsl.routine_schedule_log
                JOIN tbl_routine_schedule USING(routine_schedule)
                JOIN tbl_routine r USING(routine)
                WHERE rsl.routine_schedule in ({$routines_schedule})
                {$whereId}
                {$whereData}
                {$whereStatus}
                {$whereTipoOrdem}
                {$whereFamilia}
                ORDER BY rsl.routine_schedule_log DESC;
            ";
            $query = $pdo->query($sql);

            if ($query == false) {
                return false;
            }

            if ($query->rowCount() > 0) {
                $retorno = $query->fetchAll(\PDO::FETCH_ASSOC);

                /*if ($familia != null && count($retorno) > 0 && 1 == 2) {
                    $columnsName = array(
                        'centroDistribuidor', 'branco', 'idCliente',
                        'nomeCliente', 'enderecoCliente', 'bairroCliente',
                        'cepCliente', 'cidadeCliente', 'estadoCliente',
                        'paisCliente', 'telefoneCliente', 'telefoneCliente2',
                        'numeroAtivo', 'modeloKof',  'patrimonioKof',  'osKof',
                        'protocoloKof',   'grupoCatalogoKof', 'categoriaDefeito',
                        'codDefeito', 'defeito', 'dataAbertura',   'horaAbertura',
                        'apelidoContato',  'nomeContato', 'comentario',  'nomeFantasia',
                        'tipoOrdem',  'descricaoTipo',   'classeAtividade', 'categoriaEquipamento',
                        'garantia',    'numeroSerie',
                    );
                    $logsFamilia = array();

                    foreach ($retorno as $key => $value) {
                        echo '<pre>';
                        var_dump($value);
                        echo '</pre>';
                        if (!empty($value['contents']) && $value['contents'] != 'null') {
                            $explodedLine = explode("|", $value['contents']);
                            $columns = array_combine($columnsName, $explodedLine);
                            if ($columns != false) {
                                $sql = "
                                    SELECT *
                                    FROM tbl_produto
                                    WHERE referencia = '{$columns['modeloKof']}'
                                    AND fabrica_i = {$factory}
                                    AND familia = {$familia};
                                ";
                                echo $sql;
                                $qry = $pdo->query($sql);
                                if ($qry->rowCount() > 0) {
                                    $logsFamilia[$key] = $retorno[$key];
                                }
                            }
                        }
                    }
                    if (count($logsFamilia) > 0) {
                        return $logsFamilia;
                    } else {
                        return false;
                    }
                }*/

                return $retorno;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    public function CheckFileProcessed() {
        $oLogModel = new LogModel();
        $pdo = $oLogModel->getPDO();

        $sql = "
            SELECT *
            FROM tbl_routine_schedule_log
            WHERE file_name LIKE '{$this->getFileName()}';
        ";

        $query = $pdo->query($sql);

        if ($query == false) {
            return false;
        }

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function FinishRoutinesWithoutFinish($factory, $routine) {
            $oLogModel = new LogModel();
            $pdo = $oLogModel->getPDO();
            
            if (!strlen($factory)) {
                throw new \Exception("Fábrica não informada");
            }

            if (!strlen($routine)) {
                throw new \Exception("Rotina não informada");
            }

            $sql = "
                UPDATE tbl_routine_schedule_log SET
                        date_finish = '".date("Y-m-d H:i:s")."',
                        status = 1,
                        status_message = 'Rotina finalizada'
                FROM tbl_routine_schedule, tbl_routine
                WHERE tbl_routine_schedule_log.routine_schedule = tbl_routine_schedule.routine_schedule
                AND tbl_routine_schedule.routine = tbl_routine.routine
                AND tbl_routine.factory = {$factory}
                AND tbl_routine.routine = {$routine}
                AND tbl_routine_schedule_log.date_finish IS NULL        
            ";
            $res = $pdo->query($sql);

            if (!$res) {
                 throw new \Exception("Erro ao finalizar rotinas");
            }
    }

    public function SelectRoutineWithoutFinish($factory, $routine) {
        $oLogModel = new LogModel();
        $pdo       = $oLogModel->getPDO();

        if (!strlen($factory)) {
            throw new \Exception("Fábrica não informada");
        }

        if (!strlen($routine)) {
            throw new \Exception("Rotina não informada");
        }

        $sql = "
            SELECT *
            FROM tbl_routine_schedule_log AS rsl
            INNER JOIN tbl_routine_schedule AS rs ON rs.routine_schedule = rsl.routine_schedule
            INNER JOIN tbl_routine AS r ON r.routine = rs.routine
            WHERE r.factory = {$factory}
            AND r.routine = {$routine}
            AND rsl.date_start IS NOT NULL
            AND rsl.date_finish IS NULL
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar rotinas não finalizadas");
        }

        if ($qry->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function GetRoutineWithoutFinish() {
        $oLogModel = new LogModel();
        $pdo       = $oLogModel->getPDO();

        if (!strlen($this->getRoutineSchedule())) {
            throw new \Exception("Agendamento da Rotina não informada");
        }

        $sql = "
            SELECT
                routine_schedule_log
            FROM tbl_routine_schedule_log rsl
            WHERE routine_schedule = {$this->getRoutineSchedule()}
            AND date_start IS NOT NULL
            AND date_finish IS NULL;
        ";
        
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar rotinas não finalizadas");
        }

        if ($qry->rowCount() > 0) {
            return $qry->fetch(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }
    }
}
