<?php

namespace Posvenda\Cockpit;

class Agenda
{
    /**
     * @var mixed
     */
    private $fabrica;

    /**
     * @var object
     */
    private $model;

    public function __construct($fabrica, \Posvenda\Model\GenericModel $model)
    {
        $this->fabrica = $fabrica;
        $this->model = $model;
    }

    /**
     * @param integer $tecnico
     * @param string $data
     * @param integer $os
     * @return array
     */
    public function verificaAgenda($tecnico, $data = null, $os = null)
    {
        if (!strlen($tecnico)) {
            throw new \Exception("Técnico não informado");
        }

        $this->model->select("tbl_tecnico_agenda")
            ->setCampos(array("*"))
            ->addWhere(array("tecnico" => $tecnico));

        if ($data != null) {
            $this->model->addWhere(
                $this->model->between('data_agendamento', array("$data 00:00:00", "$data 23:59:59"))
            );
        }

        if ($os != null) {
            $this->model->addWhere(array("os" => $os));
        }

        $this->model->orderBy("data_agendamento", true);

        if (!$this->model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar agenda");
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return array();
        } else {
            return $this->model->getPDOStatement()->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    /**
     * @param integer $os
     * @return array
     */
    public function verificaAgendaByOS($os)
    {
        if (!strlen($os)) {
            throw new \Exception("OS não informada");
        }

        $this->model->select("tbl_tecnico_agenda")
            ->setCampos(array("*"))
            ->addWhere(array("os" => $os));

        if (!$this->model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar agenda");
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return array();
        } else {
            return $this->model->getPDOStatement()->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    /**
     * @param integer $tecnico
     * @param integer $os
     * @param string $data
     * @return boolean
     */
    public function insereAgenda($tecnico, $os, $data, $ordem,$con)
    {
        if (!strlen($tecnico)) {
            throw new \Exception("Técnico não informado");
        }

        if (!strlen($os)) {
            throw new \Exception("OS não informada");
        }

        if (!strlen($data)) {
            throw new \Exception("Data não informada");
        }

        if (!strlen($ordem)) {
            throw new \Exception("Ordem não informada");
        }

        if (preg_match("/\//", $data)) {
            list($dia, $mes, $ano) = explode("/", $data);
            $auxData = $data;
            $data = "{$ano}-{$mes}-{$dia}";
        } else {
            if (preg_match("/^[0-9]{2}-/", $data)) {
                list($dia, $mes, $ano) = explode("-", $data);
                $data = "$ano-$mes-$dia";
            } else {
                list($ano, $mes, $dia) = explode("-", $data);
            }

            $auxData = "$dia/$mes/$ano";
        }

        $pdo = $this->model->getPDO();

        $sql = "INSERT INTO tbl_tecnico_agenda (
                    fabrica,
                    tecnico,
                    os,
                    data_agendamento,
                    ordem
                ) VALUES (
                    {$this->fabrica},
                    {$tecnico},
                    {$os},
                    '{$data} 12:00:00',
                    {$ordem})";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        /*
        ** Interação OS
        ** Campos: OS, Data, comentario, fabrica
        */ 
        $sql = "INSERT INTO tbl_os_interacao (
                    os, data, comentario, fabrica
                ) VALUES (
                    {$os}, '{$data}','OS agendada para o dia {$auxData}', {$this->fabrica}
                );";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        return true;
    }

    /**
     * @param integer $id
     * @param integer $tecnico
     * @param string $data
     * @param integer $os
     * @return boolean
     */
    public function atualizarAgenda($id, $tecnico, $data, $ordem, $os)
    {
        if (!strlen($id)) {
            throw new \Exception("Agenda não informada");
        }

        if (!strlen($tecnico)) {
            throw new \Exception("Técnico não informado");
        }

        if (!strlen($data)) {
            throw new \Exception("Data não informada");
        }

        if (!strlen($ordem)) {
            throw new \Exception("Ordem não informada");
        }

        if (!strlen($os)) {
            throw new \Exception("OS não informada");
        }

        if (preg_match("/\//", $data)) {
            list($dia, $mes, $ano) = explode("/", $data);
            $auxData = $data;
            $data = "{$ano}-{$mes}-{$dia}";
        } else {
            if (preg_match("/^[0-9]{2}-/", $data)) {
                list($dia, $mes, $ano) = explode("-", $data);
                $data = "$ano-$mes-$dia";
            } else {
                list($ano, $mes, $dia) = explode("-", $data);
            }

            $auxData = "$dia/$mes/$ano";
        }

        $pdo = $this->model->getPDO();

        $sql = "UPDATE tbl_tecnico_agenda SET
                    data_agendamento = '{$data} 12:00:00',
                    tecnico = {$tecnico},
                    ordem = {$ordem}
                WHERE fabrica = {$this->fabrica}
                AND tecnico_agenda = {$id}";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        /*
        ** Interação OS
        ** Campos: OS, Data, Comentário, Fábrica
        */
        $sql = "INSERT INTO tbl_os_interacao (
                    os, data, comentario, fabrica
                ) VALUES (
                    {$os}, '{$data}', 'OS agendada alterada para o dia {$auxData}', {$this->fabrica}
                );";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        return true;
    }

    /**
     * @param integer $id
     * @return boolean
     */
    public function deletarAgenda($id)
    {
        if (!strlen($id)) {
            throw new \Exception("Agenda não informada");
        }

        $pdo = $this->model->getPDO();

        $sql = "DELETE FROM tbl_tecnico_agenda WHERE fabrica = {$this->fabrica} AND tecnico_agenda = {$id}";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $cliente
     * @param string $produto
     */
    public function verificaPrioridade($cliente, $produto)
    {
        $keyAccount = $this->verificaKeyAccount($cliente);
        $familia = $this->getFamiliaProduto($produto);

        if (empty($familia)) {
            throw new \Exception("Família não encontrada. - {$produto}");
        }

        $prios = array(
            "REFRIGERADOR" => "Normal",
            "POST MIX" => "Alta",
            "MAQUINA DE CAFE" => "Alta",
            "CHOPEIRA" => "Alta",
        );

        if (array_key_exists($familia["descricao"], $prios)) {
            $prioridade = $prios[$familia["descricao"]];
        } else {
            $prioridade = 'Normal';
        }

        if (true === $keyAccount) {
            $prioridade .= ' KA';
        }

        $sqlPeso = $this->model->select("tbl_hd_chamado_cockpit_prioridade")
                        ->setCampos(array("hd_chamado_cockpit_prioridade", "peso"))
                        ->addWhere(array("fabrica" => $this->fabrica))
                        ->addWhere(array("descricao" => $prioridade))
                        ->addWhere(array("ativo" => 't'));

        $return = array(
            "hd_chamado_cockpit" => 0,
            "peso" => 0
        );

        if (!$this->model->prepare()->execute()) {
            return $return;
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return $return;
        }

        return $this->model->getPDOStatement()->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $razao
     * @return boolean
     */
    private function verificaKeyAccount($razao)
    {
        $this->model->select("tbl_cliente")
            ->setCampos(array("*"))
            ->addJoin(array("tbl_fabrica_cliente" => "USING(cliente)"))
            ->addWhere(array("fabrica" => $this->fabrica))
            ->addWhere(array("nome" => $razao))
            ->addWhere(array("contrato" => "t"));

        if (!$this->model->prepare()->execute()) {
            return false;
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return false;
        }

        return true;
    }

    /**
     * @param integer $produto
     * @return integer
     */
    private function getFamiliaProduto($produto)
    {
        $this->model->select("tbl_produto")
            ->setCampos(array("tbl_familia.descricao"))
            ->addJoin(array("tbl_familia" => "USING(familia)"))
            ->addWhere(array("referencia" => $produto))
            ->addWhere(array("fabrica_i" => $this->fabrica));

        if (!$this->model->prepare()->execute()) {
            return array();
        }

        if ($this->model->getPDOStatement()->rowCount() == 0) {
            return array();
        }

        return $this->model->getPDOStatement()->fetch(\PDO::FETCH_ASSOC);
    }

    public function osAgendadaNaoFinalizada($reagendar_os) {
        $pdo = $this->model->getPDO();

        $data = date("Y-m-d 22:30:00");

        if ($reagendar_os == true) {
            $whereReagendarOs = "
                AND (
                    (tbl_linha.auto_agendamento IS TRUE
                    AND rsl.create_at + INTERVAL '72 hours' > current_timestamp)
                    OR (tbl_tipo_atendimento.grupo_atendimento = 'S')
                )
            ";
        } else {
            $whereReagendarOs = "
                AND (
                    (tbl_linha.auto_agendamento IS NOT TRUE AND tbl_tipo_atendimento.grupo_atendimento != 'S')
                    OR
                    (
                        tbl_linha.auto_agendamento IS TRUE
                        AND rsl.create_at + INTERVAL '72 hours' < current_timestamp
                    )
                )
                AND (
                    SELECT COUNT(os_item_peca.os_item)
                    FROM tbl_os os_peca
                    INNER JOIN tbl_os_produto os_produto_peca ON os_produto_peca.os = os_peca.os
                    INNER JOIN tbl_os_item os_item_peca ON os_item_peca.os_produto = os_produto_peca.os_produto
                    WHERE os_peca.fabrica = {$this->fabrica}
                    AND os_peca.os = os.os
                ) = 0
            ";
        }

        $sql = "
            SELECT hdc.hd_chamado_cockpit, ta.os, hdcp.descricao AS prioridade, os.os_numero AS id_externo, ta.tecnico_agenda
            FROM tbl_tecnico_agenda AS ta
            INNER JOIN tbl_tecnico AS t ON t.tecnico = ta.tecnico AND t.fabrica = {$this->fabrica}
            INNER JOIN tbl_posto_fabrica AS pf ON pf.posto = t.posto AND pf.fabrica = {$this->fabrica}
            INNER JOIN tbl_tipo_posto AS tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$this->fabrica}
            INNER JOIN tbl_os AS os ON os.os = ta.os AND os.fabrica = {$this->fabrica}
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = os.os
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$this->fabrica}
            INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$this->fabrica}
            INNER JOIN tbl_hd_chamado AS hd ON hd.hd_chamado = os.hd_chamado AND hd.fabrica = {$this->fabrica}
            INNER JOIN tbl_hd_chamado_cockpit AS hdc ON hdc.hd_chamado = hd.hd_chamado AND hdc.fabrica = {$this->fabrica}
            INNER JOIN tbl_hd_chamado_cockpit_prioridade AS hdcp ON hdcp.hd_chamado_cockpit_prioridade = hdc.hd_chamado_cockpit_prioridade AND hdcp.fabrica = {$this->fabrica}
            INNER JOIN tbl_routine_schedule_log rsl ON rsl.routine_schedule_log = hdc.routine_schedule_log
            INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$this->fabrica}
            WHERE ta.fabrica = {$this->fabrica}
            AND os.finalizada IS NULL
            AND os.os_numero IS NOT NULL
            {$whereReagendarOs}
            AND ta.hora_inicio_trabalho IS NULL
            AND ta.data_agendamento <= '{$data}'
            AND tp.tecnico_proprio IS TRUE
        ";
        $qry = $pdo->query($sql);

        if (!$qry) {
            throw new \Exception("Erro ao buscar OSs agendadas não finalizadas");
        }

        if ($qry->rowCount() > 0) {
            return $qry->fetchAll();
        } else {
            return array();
        }
    }
}
