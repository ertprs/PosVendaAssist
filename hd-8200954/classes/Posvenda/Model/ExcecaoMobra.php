<?php

namespace Posvenda\Model;

class ExcecaoMobra extends \Posvenda\Model\AbstractModel {

    private $_os;
    private $_fabrica;
    private $_pdo;

    public function __construct($os = null, $fabrica = null){
        $this->_os      = $os;
        $this->_fabrica = $fabrica;
        $this->_pdo     = $this->getPDO();
    }

    public function totalDias(){
        $sql   = "SELECT data_conserto::date - data_digitacao::date AS total_dias FROM tbl_os WHERE os = $this->_os AND fabrica = $this->_fabrica";
        $query = $this->_pdo->query($sql);

        $res   = $query->fetch();

        return $res["total_dias"];
    }

    /*public function calculaExcecaoMobraTriagem(){
        $sql = "UPDATE tbl_os SET
                    mao_de_obra = ROUND(x.mao_de_obra::numeric,2)
                FROM (
                    SELECT tbl_os.os, tbl_excecao_mobra.mao_de_obra
                    FROM tbl_os
                    JOIN tbl_os_extra USING(os)
                    JOIN tbl_excecao_mobra ON tbl_os.fabrica = tbl_excecao_mobra.fabrica 
                    AND tbl_os.tipo_atendimento = tbl_excecao_mobra.tipo_atendimento
                    JOIN tbl_tipo_atendimento ON tbl_excecao_mobra.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento 
                    AND tbl_tipo_atendimento.fabrica = $this->_fabrica
                    WHERE tbl_os.fabrica          = $this->_fabrica
                    AND   tbl_excecao_mobra.mao_de_obra IS NOT NULL
                    AND   tbl_excecao_mobra.tipo_atendimento IS NOT NULL
                    AND   tbl_excecao_mobra.produto     IS NULL
                    AND   tbl_excecao_mobra.posto       IS NULL
                    AND   tbl_excecao_mobra.linha       IS NULL
                    AND   tbl_excecao_mobra.familia     IS NULL
                    AND   tbl_excecao_mobra.qtde_dias   IS NULL
                    AND   tbl_os.os = $this->_os
                ) x
                WHERE tbl_os.os = x.os";
        $query = $this->_pdo->query($sql);
    }*/

    public function calculaExcecaoMobraDiasConserto($dias){
        if($dias > 30){
            $sql   = "UPDATE tbl_os SET mao_de_obra = 0 WHERE os = $this->_os AND fabrica = $this->_fabrica";
            $query = $this->_pdo->query($sql);
        }
    }
}

