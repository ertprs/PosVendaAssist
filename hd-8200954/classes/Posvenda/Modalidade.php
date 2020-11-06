<?

namespace Posvenda;

use Posvenda\Model\Modalidade as ModalidadeModel;

class Modalidade {

    private $modalidade;
    private $fabrica;
    private $nome;
    private $ativo;
    public  $_model;

    public function __construct($fabrica) {
        $this->fabrica = $fabrica;

        if(!empty($this->fabrica)){
            $this->_model = new ModalidadeModel($this->fabrica);
        }
    }

    public function getModalidade() {
        return $this->modalidade;
    }

    public function setModalidade($modalidade = null) {
        $this->modalidade = $modalidade;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome = null) {
        $this->nome = $nome;
    }

    public function getAtivo() {
        return $this->ativo;
    }

    public function setAtivo($ativo = null) {
        $this->ativo = $ativo;
    }

    public function getFabrica() {
        return $this->fabrica;
    }

    public function setFabrica($fabrica = null) {
        $this->fabrica = $fabrica;
    }

    public function getAll() {
        $pdo = $this->_model->getPDO();

        $sql = "SELECT * FROM tbl_modalidade WHERE fabrica = $this->fabrica ORDER BY nome";

        $query = $pdo->query($sql);
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $res;
    }

    public function Insert($nome, $ativo, $fabrica) {
        $pdo = $this->_model->getPDO();

        $columnsInsert = implode(", ", $columnsIns);
        $valuesInsert = implode(", ", $valuesIns);

        $sql = "INSERT INTO tbl_modalidade(nome, ativo, fabrica)
                VALUES ('{$nome}', '{$ativo}', $fabrica);";
        $query = $pdo->query($sql);

        if ($query != false) {
            return true;
        } else {
            return false;
        }
    }

    public function Update($modalidade, $nome, $ativo) {
        $pdo = $this->_model->getPDO();

        $columnsInsert = implode(", ", $columnsIns);
        $valuesInsert = implode(", ", $valuesIns);

        $sql = "UPDATE tbl_modalidade SET nome = '{$nome}', ativo = '{$ativo}' WHERE modalidade = $modalidade";
        $query = $pdo->query($sql);

        if ($query != false) {
            return true;
        } else {
            return false;
        }
    }
}
