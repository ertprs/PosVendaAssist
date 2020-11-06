<?php

use \Posvenda\Extrato as ExtratoPosvenda;

class Extrato extends ExtratoPosvenda
{
    public function __construct($fabrica)
    {
        parent::__construct($fabrica);
    }

    /**
     * Retira OSs amarradas com algum atendimento callcenter
     *  (estas entram em extrato separado)
     *
     * @param integer extrato
     * @throws Exception se houver erro na execução de query
     * @return boolean
     */
    public function retiraOsCallcenter($extrato)
    {
        $pdo = $this->_model->getPDO();

        $sql = "UPDATE tbl_os_extra
            SET extrato = NULL
            WHERE os IN (
                SELECT os FROM tbl_os
                JOIN tbl_os_extra USING(os)
                WHERE extrato = {$extrato}
                AND hd_chamado IS NOT NULL
            )";
        $query = $pdo->query($sql);

        if (!$query) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao executar {$query}: {$this->_erro}");
        }

        $sql = "UPDATE tbl_os_extra
            SET extrato = NULL
            WHERE os IN (
                SELECT os FROM tbl_hd_chamado_extra
                JOIN tbl_os_extra USING(os)
                WHERE extrato = {$extrato}
            )";
        $query = $pdo->query($sql);

        if (!$query) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao executar {$query}: {$this->_erro}");
        }

        return true;
    }

    /**
     * Relaciona OSs amarradas com algum atendimento callcenter
     *
     * @param integer $fabrica
     * @param integer $posto
     * @param integer $extrato
     * @param integer $dia_extrato
     * @throws Exception quando erro
     * @return boolean
     */
    public function relacionaOsCallcenter($fabrica, $posto, $extrato, $dia_extrato)
    {
        if (empty($extrato)) {
            throw new \Exception(
                "Extrato não informado para relacionar as OSs com o extrato para o posto : {$posto}"
            );
        } elseif (empty($dia_extrato)) {
            throw new \Exception(
                "Dia de Geração de Extrato não informado para relacionar as OSs com o extrato para o posto : {$posto}"
            );
        }

        $pdo = $this->_model->getPDO();

        $sql = "UPDATE tbl_os_extra 
            SET extrato = $extrato
            FROM  tbl_os
            WHERE tbl_os.posto = $posto
            AND tbl_os.fabrica = $fabrica
            AND tbl_os.os = tbl_os_extra.os
            AND tbl_os_extra.extrato IS NULL
            AND tbl_os.excluida IS NOT TRUE
            ANd tbl_os.hd_chamado IS NOT NULL
            AND tbl_os.finalizada <= '$dia_extrato'";

        $query = $pdo->query($sql);

        if (!$query) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao relacionar OS com Extrato para o posto : {$posto}");
        }

        $sql = "UPDATE tbl_os_extra 
            SET extrato = $extrato
            FROM  tbl_os
            JOIN tbl_hd_chamado_extra USING(os)
            WHERE tbl_os.posto = $posto
            AND tbl_os.fabrica = $fabrica
            AND tbl_os.os = tbl_os_extra.os
            AND tbl_os_extra.extrato IS NULL
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.finalizada <= '$dia_extrato'";

        $query = $pdo->query($sql);

        if (!$query) {
            $this->_erro = $pdo->errorInfo();
            throw new \Exception("Erro ao relacionar OS com Extrato para o posto : {$posto}");
        }
        $calcula_os_sem_valor = \Posvenda\Regras::get("calcula_os_sem_valor", "extrato", $this->_fabrica);

        if ($calcula_os_sem_valor == true) {
            $nao_calcula_posto_interno = \Posvenda\Regras::get("nao_calcula_posto_interno", "extrato", $this->_fabrica);

            if ($nao_calcula_posto_interno == true) {
                $wherePostoInterno = "AND tbl_tipo_posto.posto_interno IS NOT TRUE";
            }

            $sql = "
                SELECT DISTINCT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$fabrica}
                INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.fabric = {$fabrica} AND tbl_os_troca.ressarcimento IS NOT TRUE
                WHERE tbl_os.fabrica = {$fabrica}
                AND tbl_os_extra.extrato = {$extrato}
                {$wherePostoInterno}
            ";
            $query = $pdo->query($sql);

            if (!$query) {
                throw new \Exception("Erro ao calcular OS");
            }

            $res = $query->fetchAll();

            if (count($res) > 0) {
                $classOs = new \Posvenda\Os($fabrica);

                foreach ($res as $os) {
                    $classOs->calculaOs($os["os"]);
                }
            }
        }

        return true;
    }

    /**
     * Remove o extrato se ele estiver com total = 0
     *
     * @param integer $extrato
     * @return integer
     */
    public function removeExtratoZerado($extrato)
    {
        $pdo = $this->_model->getPDO();

        $sql = "DELETE FROM tbl_extrato
            WHERE fabrica = {$this->_fabrica}
            AND extrato = $extrato
            AND total = 0";
        $deleted = $pdo->exec($sql);

        return $deleted;
    }

    /**
     * Verifica se extrato já tem LGR
     *
     * @param integer $extrato
     * @return boolean
     */
    public function verificaExtratoLgr($extrato)
    {
        $pdo = $this->_model->getPDO();

        $sql = "SELECT extrato_lgr FROM tbl_extrato_lgr WHERE extrato = $extrato";
        $query = $pdo->query($sql);

        if ($query->rowCount() == 0) {
            return false;
        }

        return true;
    }
}
