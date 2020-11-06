<?php

namespace Posvenda;

use Posvenda\Model\Routine as RoutineModel;

class Routine {

    private $routine;
    private $factory;
    private $context;
    private $hostname;
    private $user_name;
    private $pass;
    private $passive;
    private $key;
    private $port;
    private $protocol;
    private $remote_folder;
    private $local_folder;
    private $create_at;
    private $update_at;
    private $comment;
    private $file_mask;
    private $program;
    private $active;

    public function getRoutine() {
        return $this->routine;
    }

    public function setRoutine($routine = null) {
        $this->routine = $routine;
    }

    public function getFactory() {
        return $this->factory;
    }

    public function setFactory($factory = null) {
        $this->factory = $factory;
    }

    public function getContext() {
        return $this->context;
    }

    public function setContext($context = null) {
        $this->context = $context;
    }

    public function getHostname() {
        return $this->hostname;
    }

    public function setHostname($hostname = null) {
        $this->hostname = $hostname;
    }

    public function getUserName() {
        return $this->user_name;
    }

    public function setUserName($user_name = null) {
        $this->user_name = $user_name;
    }

    public function getPass() {
        return $this->pass;
    }

    public function setPass($pass = null) {
        $this->pass = $pass;
    }

    public function getPassive() {
        return $this->passive;
    }

    public function setPassive($passive = null) {
        $this->passive = $passive;
    }

    public function getKey() {
        return $this->key;
    }

    public function setKey($key = null) {
        $this->key = $key;
    }

    public function getPort() {
        return $this->port;
    }

    public function setPort($port = null) {
        $this->port = $port;
    }

    public function getProtocol() {
        return $this->protocol;
    }

    public function setProtocol($protocol = null) {
        $this->protocol = $protocol;
    }

    public function getRemoteFolder() {
        return $this->remote_folder;
    }

    public function setRemoteFolder($remote_folder = null) {
        $this->remote_folder = $remote_folder;
    }

    public function getLocalFolder() {
        return $this->local_folder;
    }

    public function setLocalFolder($local_folder = null) {
        $this->local_folder = $local_folder;
    }

    public function getCreateAt() {
        return $this->update_at;
    }

    public function setCreateAt($create_at = null) {
        $this->create_at = $create_at;
    }

    public function getUpdateAt() {
        return $this->update_at;
    }

    public function setUpdateAt($update_at = null) {
        $this->update_at = $update_at;
    }

    public function getComment() {
        return $this->comment;
    }

    public function setComment($comment = null) {
        $this->comment = $comment;
    }

    public function getFileMask() {
        return $this->file_mask;
    }

    public function setFileMask($file_mask = null) {
        $this->file_mask = $file_mask;
    }

    public function getProgram() {
        return $this->program;
    }

    public function setProgram($program = null) {
        $this->program = $program;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active = null) {
        $this->active = $active;
    }

    public function getAll($validate_value = false) {

        $arr = array(
            'routine' => $this->getRoutine(),
            'factory' => $this->getFactory(),
            'context' => $this->getContext(),
            'hostname' => $this->getHostname(),
            'user_name' => $this->getUserName(),
            'pass' => $this->getPass(),
            'passive' => $this->getPassive(),
            'key' => $this->getKey(),
            'port' => $this->getPort(),
            'protocol' => $this->getProtocol(),
            'remote_folder' => $this->getRemoteFolder(),
            'local_folder' => $this->getLocalFolder(),
            'create_at' => $this->getCreateAt(),
            'update_at' => $this->getUpdateAt(),
            'comment' => $this->getComment(),
            'file_mask' => $this->getFileMask(),
            'program' => $this->getProgram(),
            'active' => $this->getActive()
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
        $oRoutineModel = new RoutineModel();
        $pdo = $oRoutineModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'routine') {
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
        $oRoutineModel = new RoutineModel();
        $pdo = $oRoutineModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'routine') {
                $camposUp[] = "$key = ".((is_string($value)) ? pg_escape_literal($value) : "$value");
            }
        }

        $setUpdate = implode(", ", $camposUp);

        $sql = "UPDATE tbl_routine SET {$setUpdate} WHERE routine = {$this->getRoutine()};";
        $query = $pdo->query($sql);

        if ($query != false) {
            return true;
        } else {
            return false;
        }
    }

    public function SelectId($next = false) {
        $oRoutineModel = new RoutineModel();
        $pdo = $oRoutineModel->getPDO();

        $sql = "SELECT last_value AS id FROM seq_routine;";

        $query = $pdo->query($sql);

        if ($query == false) {
            return false;
        }

        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            if ($next == true) {
                return $res[0][id] + 1;
            } else {
                return $res[0][id];
            }
        } else {
            return false;
        }
    }

    function SelectRoutine($filter_by_context = null) {
        $oRoutineModel = new RoutineModel();
        $pdo = $oRoutineModel->getPDO();

        if ($this->getRoutine() != '') {
            $whereRoutine = "AND r.routine = {$this->getRoutine()}";
        }

        if ($this->getFactory() != '') {
            $whereFactory = "AND r.factory = {$this->getFactory()}";
        }

        if (!empty($filter_by_context)) {
            $filter_by_context = addslashes(strtolower(trim($filter_by_context)));

            $whereContext = "AND LOWER(fn_retira_especiais(r.context)) = fn_retira_especiais('{$filter_by_context}')";
        }

        $sql = "
            SELECT *
            FROM tbl_routine r
            WHERE r.active IS TRUE
            {$whereFactory}
            {$whereRoutine} 
            {$whereContext};
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
}
