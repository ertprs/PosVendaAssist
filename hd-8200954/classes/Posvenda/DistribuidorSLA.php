<?

namespace Posvenda;

use Posvenda\Model\DistribuidorSLA as DistribSLAModel;

class DistribuidorSLA {

    private $distribuidor_sla;
    private $fabrica;
    private $centro;
    private $descricao;
    private $regiao;
    private $franquia;
    private $cidade;
    private $unidade_negocio;
    private $data_input;

    public function getDistribuidorSLA() {
        return $this->distribuidor_sla;
    }

    public function setDistribuidorSLA($distribuidor_sla = null) {
        $this->distribuidor_sla = $distribuidor_sla;
    }

    public function getFabrica() {
        return $this->fabrica;
    }

    public function setFabrica($fabrica = null) {
        $this->fabrica = $fabrica;
    }

    public function getCentro() {
        return $this->centro;
    }

    public function setCentro($centro = null) {
        $this->centro = $centro;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao = null) {
        $this->descricao = $descricao;
    }

    public function getRegiao() {
        return $this->regiao;
    }

    public function setRegiao($regiao = null) {
        $this->regiao = $regiao;
    }

    public function getFranquia() {
        return $this->franquia;
    }

    public function setFranquia($franquia = null) {
        $this->franquia = $franquia;
    }

    public function getCidade() {
        return $this->cidade;
    }

    public function setCidade($cidade = null) {
        $this->cidade = $cidade;
    }

    public function getDataInput() {
        return $this->data_input;
    }

    public function setUnidadeNegocio($unidade_negocio = null) {
        $this->unidade_negocio = $unidade_negocio;
    }

    public function getUnidadeNegocio() {
        return $this->unidade_negocio;
    }

    public function getAll($validate_value = false) {

        $arr = array(
            'distribuidor_sla' => $this->getDistribuidorSLA(),
            'fabrica'          => $this->getFabrica(),
            'centro'           => $this->getCentro(),
            'descricao'        => $this->getDescricao(),
            'regiao'           => $this->getRegiao(),
            'franquia'         => $this->getFranquia(),
            'cidade'           => $this->getCidade(),
            'unidade_negocio'  => $this->getUnidadeNegocio(),
            'data_input'       => $this->getDataInput()
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
        $oDistribSLAModel = new DistribSLAModel();
        $pdo = $oDistribSLAModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'distribuidor_sla') {
                $columnsIns[] = "$key";
                $valuesIns[] = (is_string($value)) ? "'$value'" : "$value";
            }
        }

        $columnsInsert = implode(", ", $columnsIns);
        $valuesInsert = implode(", ", $valuesIns);

        $sql = "
            INSERT INTO tbl_distribuidor_sla
                ({$columnsInsert})
            VALUES
                ({$valuesInsert});
        ";
        $query = $pdo->query($sql);

        if ($query != false)
            return true;
        else
            return false;
    }

    public function Update() {
        $oDistribSLAModel = new DistribSLAModel();
        $pdo = $oDistribSLAModel->getPDO();

        $campos = $this->getAll();

        foreach ($campos as $key => $value) {
            if (!is_null($value) && $key != 'distribuidor_sla') {
                $camposUp[] = "$key = ".((is_string($value)) ? "'$value'" : "$value");
            }
        }

        $setUpdate = implode(", ", $camposUp);

        $sql = "
            UPDATE tbl_distribuidor_sla
            SET {$setUpdate}
            WHERE distribuidor_sla = {$this->getDistribuidorSLA()};
        ";
        $query = $pdo->query($sql);

        if ($query != false) {
            return true;
        } else {
            return false;
        }
    }

    public function SelectId($next = false) {
        $oDistribSLAModel = new DistribSLAModel();
        $pdo = $oDistribSLAModel->getPDO();

        $sql = "SELECT last_value AS id FROM seq_distribuidor_sla;";

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

    public function Select() {
        $oDistribSLAModel = new DistribSLAModel();
        $pdo = $oDistribSLAModel->getPDO();

        if ($this->getFabrica() == "") {
            return false;
        }

        if ($this->getCentro() != "") {
            $whereCentro = "AND centro = '{$this->getCentro()}'";
        } 

        if ($this->getUnidadeNegocio() != "") {
            $whereUnidadeNegocio = "AND unidade_negocio = '{$this->getUnidadeNegocio()}'";
        }

        $sql = "
            SELECT *
            FROM tbl_distribuidor_sla
            WHERE fabrica = {$this->getFabrica()}
            {$whereCentro}
            {$whereUnidadeNegocio};
        ";

        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            return $res;
        } else {
            return false;
        }
    }

    public function SelectUnidadeNegocio() {
        $oDistribSLAModel = new DistribSLAModel();
        $pdo = $oDistribSLAModel->getPDO();

        if ($this->getCentro() != "" && $this->getFabrica() != "") {
            $where = "WHERE tbl_distribuidor_sla.centro LIKE '{$this->getCentro()}' AND tbl_distribuidor_sla.fabrica = {$this->getFabrica()}";
        } else if ($this->getFabrica() != "") {
            $where = "WHERE tbl_distribuidor_sla.fabrica = {$this->getFabrica()}";
        } else if ($this->getCentro() != "") {
            $where = "WHERE tbl_distribuidor_sla.centro LIKE '{$this->getCentro()}'";
        } 

        $sql = "
            SELECT
                tbl_distribuidor_sla.unidade_negocio,
                MAX(tbl_distribuidor_sla.distribuidor_sla) AS distribuidor_sla,
                tbl_distribuidor_sla.unidade_negocio||' - '||tbl_unidade_negocio.nome AS cidade
            FROM tbl_distribuidor_sla            
            JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio
            {$where}
            GROUP BY tbl_distribuidor_sla.unidade_negocio, tbl_unidade_negocio.nome
            ORDER BY tbl_distribuidor_sla.unidade_negocio ASC;
        ";

        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            return $res;
        } else {
            return false;
        }
    }

    public function SelectUnidadeNegocioNotIn($unidades_negocio = null, $distribuidor_sla = null, $unidade_negocio = null) {
        $oDistribSLAModel = new DistribSLAModel();
        $pdo = $oDistribSLAModel->getPDO();

        if ($unidades_negocio != null) {
            $where = "
                WHERE tbl_distribuidor_sla.unidade_negocio NOT IN (SELECT DISTINCT unidade_negocio FROM tbl_distribuidor_sla WHERE distribuidor_sla IN ({$unidades_negocio}))
            ";
        }
        if ($distribuidor_sla != null && $unidades_negocio == null) {
            $whereDistribuidor = " WHERE tbl_distribuidor_sla.distribuidor_sla = {$distribuidor_sla}";
        }
        if ($unidade_negocio != null && $distribuidor_sla == null && $unidades_negocio == null) {
            $whereUnidade = " WHERE tbl_distribuidor_sla.unidade_negocio = '{$unidade_negocio}'";
        }

        $sql = "
            SELECT DISTINCT ON (tbl_distribuidor_sla.unidade_negocio)
                tbl_distribuidor_sla.unidade_negocio,
                MAX(tbl_distribuidor_sla.distribuidor_sla) AS distribuidor_sla,
			    tbl_distribuidor_sla.unidade_negocio||' - '||tbl_unidade_negocio.nome AS cidade
            FROM tbl_distribuidor_sla
            JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio
            {$where}
            {$whereDistribuidor}
            {$whereUnidade}
            GROUP BY tbl_distribuidor_sla.unidade_negocio, tbl_unidade_negocio.nome
            ORDER BY tbl_distribuidor_sla.unidade_negocio ASC;
        ";
        
        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            return $res;
        } else {
            return false;
        }
    }
}
