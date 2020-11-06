<?php

namespace Posvenda\Model;

class Linha extends AbstractModel {

    private $pdo;

    public function __construct($fabrica) {
        parent::__construct('tbl_linha');

        $this->pdo = $this->getPDO();
    }

    public function getData($linha, $fabrica) {
        if (!strlen($linha)) {
            throw new \Exception("Linha não informada");
        }

        if (empty($fabrica)) {
            throw new \Exception("Fábrica não informada");
        }

        $sql = "
            SELECT *
            FROM tbl_linha
            WHERE fabrica = {$fabrica}
            AND linha = {$linha}
        ";
        $qry = $this->pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar linha");
        }

        if ($qry->rowCount() > 0) {
            return $qry->fetch();
        } else {
            return false;
        }
    }

}
