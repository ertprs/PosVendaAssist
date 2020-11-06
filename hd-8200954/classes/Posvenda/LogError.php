<?

namespace Posvenda;

use Posvenda\Model\LogError as LogErrorModel;

class LogError {

    private $routine_schedule_log_error;
    private $routine_schedule_log;
    private $line_number;
    private $contents;
    private $error_message;
    private $create_at;

    public function __construct() {
        date_default_timezone_set("America/Sao_Paulo");
    }


    public function getRoutineScheduleLogError() {
        return $this->routine_schedule_log_error;
    }

    public function setRoutineScheduleLogError($routine_schedule_log_error = null) {
        $this->routine_schedule_log_error = $routine_schedule_log_error;
    }

    public function getRoutineScheduleLog() {
        return $this->routine_schedule_log;
    }

    public function setRoutineScheduleLog($routine_schedule_log = null) {
        $this->routine_schedule_log = $routine_schedule_log;
    }

    public function getLineNumber() {
        return $this->line_number;
    }

    public function setLineNumber($line_number = null) {
        $this->line_number = $line_number;
    }

    public function getContents() {
        return $this->contents;
    }

    public function setContents($contents = null) {
        $this->contents = $contents;
    }

    public function getErrorMessage() {
        return $this->error_message;
    }

    public function setErrorMessage($error_message = null) {
        $this->error_message = $error_message;
    }

    public function getCreateAt() {
        return $this->create_at;
    }

    public function setCreateAt() {
        $this->create_at = date("Y-m-d H:i:s");
    }


    public function getAll($validate_value = false) {

        $arr = array('routine_schedule_log_error' => $this->getRoutineScheduleLogError(),
                            'routine_schedule_log' => $this->getRoutineScheduleLog(),
                            'line_number' => $this->getLineNumber(),
                            'contents' => $this->getContents(),
                            'error_message' => $this->getErrorMessage(),
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

    public function SelectId($next = false) {
        $oLogModel = new LogErrorModel();
        $pdo = $oLogModel->getPDO();

        $sql = "SELECT last_value AS id FROM seq_routine_schedule_log_error;";

        $query = $pdo->query($sql);
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

    public function Insert() {
        $oLogErrorModel = new LogErrorModel();
        $pdo = $oLogErrorModel->getPDO();

	$this->setCreateAt();

	$line_number = (strlen($this->getLineNumber())) ? $this->getLineNumber() : "null";

        $sql = "
            INSERT INTO tbl_routine_schedule_log_error
                (routine_schedule_log,
                line_number,
                contents,
                error_message,create_at)
            VALUES
                ({$this->getRoutineScheduleLog()},
                {$line_number},
                '{$this->getContents()}',
                '{$this->getErrorMessage()}', '{$this->getCreateAt()}');
        ";

        $query = $pdo->query($sql);

        if ($query != false)
            return $this->SelectId();
        else
            return false;
    }

    public function Update() {
        $oLogErrorModel = new LogErrorModel();
        $pdo = $oLogErrorModel->getPDO();

        $sql = "
            UPDATE tbl_routine_schedule_log_error SET
                contents = '{$this->getContents()}',
                error_message = '{$this->getErrorMessage()}'
            WHERE routine_schedule_log_error = {$this->getRoutineScheduleLogError()};
        ";
        $query = $pdo->query($sql);

        if ($query != false)
            return true;
        else
            return false;
    }

    function SelectLogErrors() {
        $oLogErrorModel = new LogErrorModel();
        $pdo = $oLogErrorModel->getPDO();

        if ($this->getRoutineScheduleLog() != "") {
            $whereRoutineScheduleLog = "WHERE rsl.routine_schedule_log = {$this->getRoutineScheduleLog()}";
        }

        $sql = "
            SELECT
                rsl.file_name,
                rsle.*
            FROM tbl_routine_schedule_log rsl
            RIGHT JOIN tbl_routine_schedule_log_error rsle USING(routine_schedule_log)
            {$whereRoutineScheduleLog}
        ";

        $query = $pdo->query($sql);

        if ($query->rowCount() > 0) {
            return $query->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return false;
        }

    }

    function SelectLogErrorsByRoutineId($routine = null) {
        $oLogErrorModel = new LogErrorModel();
        $pdo = $oLogErrorModel->getPDO();

        if ($routine != null) {

            if ($this->getLineNumber() != "") {
                $whereLineNumber = "AND rsle.line_number = {$this->getLineNumber()}";
            }

            $sql = "
                SELECT
                    rsl.file_name,
                    rsle.*
                FROM tbl_routine_schedule_log rsl
                JOIN tbl_routine_schedule rs ON rs.routine_schedule = rsl.routine_schedule
                JOIN tbl_routine_schedule_log_error rsle USING(routine_schedule_log)
                WHERE rs.routine = {$routine}
                {$whereLineNumber};
            ";

            $query = $pdo->query($sql);

            if ($query->rowCount() > 0) {
                return $query->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                return false;
            }

        }

    }

}
