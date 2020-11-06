<?

namespace Posvenda;

use Posvenda\Model\DefeitoReclamado as DefeitoReclamadoModel;

class DefeitoReclamado {

    private $defeito_reclamado;
    private $descricao;
    private $familia;
    private $ativo;
    private $linha;
    private $duvida_reclamacao;
    private $codigo;
    private $fabrica;
    private $entrega_tecnica;
    private $external_id;

    public function getDefeitoReclamado() {
        return $this->defeito_reclamado;
    }

    public function setDefeitoReclamado($defeito_reclamado = null) {
        $this->defeito_reclamado = $defeito_reclamado;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao = null) {
        $this->descricao = $descricao;
    }

    public function getFamilia() {
        return $this->familia;
    }

    public function setFamilia($familia = null) {
        $this->familia = $familia;
    }

    public function getAtivo() {
        return $this->ativo;
    }

    public function setAtivo($ativo = null) {
        $this->ativo = $ativo;
    }

    public function getLinha() {
        return $this->linha;
    }

    public function setLinha($linha = null) {
        $this->linha = $linha;
    }

    public function getDuvidaReclamacao() {
        return $this->duvida_reclamacao;
    }

    public function setDuvidaReclamacao($duvida_reclamacao = null) {
        $this->duvida_reclamacao = $duvida_reclamacao;
    }

    public function getCodigo() {
        return $this->codigo;
    }

    public function setCodigo($codigo = null) {
        $this->codigo = $codigo;
    }

    public function getFabrica() {
        return $this->fabrica;
    }

    public function setFabrica($fabrica = null) {
        $this->fabrica = $fabrica;
    }

    public function getEntregaTecnica() {
        return $this->entrega_tecnica;
    }

    public function setEntregaTecnica($entrega_tecnica = null) {
        $this->entrega_tecnica = $entrega_tecnica;
    }

    public function getExternalId() {
        return $this->external_id;
    }

    public function setExternalId($external_id = null) {
        $this->external_id = $external_id;
    }

    public function getAll($validate_value = false) {

        $arr = array('defeito_reclamado' => $this->getDefeitoReclamado(),
                            'descricao' => $this->getDescricao(),
                            'familia' => $this->getFamilia(),
                            'ativo' => $this->getAtivo(),
                            'linha' => $this->getLinha(),
                            'duvida_reclamacao' => $this->getDuvidaReclamacao(),
                            'codigo' => $this->getCodigo(),
                            'fabrica' => $this->getFabrica(),
                            'entrega_tecnica' => $this->getEntregaTecnica(),
                            'external_id' => $this->getExternalId());

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
        $oDefeitoReclamadoModel = new DefeitoReclamadoModel();
        $pdo = $oDefeitoReclamadoModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'defeito_reclamado') {
                $columnsIns[] = "$key";
                $valuesIns[] = (is_string($value)) ? "'$value'" : "$value";
            }
        }

        $columnsInsert = implode(", ", $columnsIns);
        $valuesInsert = implode(", ", $valuesIns);

        $sql = "INSERT INTO tbl_defeito_reclamado
                                        ({$columnsInsert})
                    VALUES
                                        ({$valuesInsert});";
        $query = $pdo->query($sql);

        if ($query != false)
            return true;
        else
            return false;
    }

    public function Update() {
        $oDefeitoReclamadoModel = new DefeitoReclamadoModel();
        $pdo = $oDefeitoReclamadoModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'defeito_reclamado') {
                $camposUp[] = "$key = ".((is_string($value)) ? "'$value'" : "$value");
            }
        }

        $setUpdate = implode(", ", $camposUp);

        $sql = "UPDATE tbl_defeito_reclamado SET
                                {$setUpdate}
                    WHERE defeito_reclamado = {$this->getDefeitoReclamado()};";
        $query = $pdo->query($sql);

        if ($query != false) {
            return true;
        } else {
            return false;
        }
    }

    public function SelectId($next = false) {
        $oDefeitoReclamadoModel = new DefeitoReclamadoModel();
        $pdo = $oDefeitoReclamadoModel->getPDO();

        $sql = "SELECT last_value AS id FROM seq_defeito_reclamado;";

        $query = $pdo->query($sql);
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

    public function SelectActives() {
        $oDefeitoReclamadoModel = new DefeitoReclamadoModel();
        $pdo = $oDefeitoReclamadoModel->getPDO();

        if ($this->getFabrica() != "") {
            if ($this->getCodigo() != "") {
                $sql = "SELECT * FROM tbl_defeito_reclamado WHERE ativo IS TRUE AND fabrica = {$this->getFabrica()} AND codigo = '{$this->getCodigo()}';";
            } else {
                $sql = "SELECT * FROM tbl_defeito_reclamado WHERE ativo IS TRUE AND fabrica = {$this->getFabrica()};";
            }

            $query = $pdo->query($sql);
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);

            if (count($res) > 0) {
                return $res;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
