<?php

namespace Posvenda;

use Posvenda\Model\Os as FabricaModel;

class Fabrica
{
    /**
     * @var string
     */
    private $_os;

    /**
     * @var integer
     */
    private $_fabrica;

    /**
     * @var \Posvenda\Model\Os
     */
    private $_fabrica_model;

    public function __construct($fabrica, $os = null)
    {
        $this->_os = $os;
        $this->_fabrica = $fabrica;
        $this->_fabrica_model = new FabricaModel($this->_fabrica, $this->_os);

    } 
    public static function fabricaNome($fabrica)
    {
        if (empty($fabrica)) {
            throw new \Exception("Fábrica não informada");
        }

        $fabricaClass = new Fabrica($fabrica);

        $fabricaClass->_fabrica_model->select('tbl_fabrica')
             ->setCampos(array('nome'))
             ->addWhere(array('fabrica' => $fabricaClass->_fabrica));

        if (!$fabricaClass->_fabrica_model->prepare()->execute()) {
            throw new \Exception("Erro ao solicitar o nome da Fabrica");
        }

        $res = $fabricaClass->_fabrica_model->getPDOStatement()->fetch();

        if (!empty($res["nome"])) {
            $nome = $res["nome"];
        }

        return $nome;

        // if (empty($fabrica)) {
        //     throw new \Exception("Fabrica não informada");
        // }

        // $sql = "SELECT LOWER(nome) from tbl_fabrica where fabrica = $fabrica";
        // $res = pg_query($con , $sql);
        // $nome = pg_fetch_result($res, 0, nome);

        // if (!empty($nome)) {
        //     $nome = $res["nome"];
        // }

        // return $nome;

    }

    /**
     * Resgata o nome da Fabrica
     *
     * @return string
     */
    public function getNome()
    {
        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar o nome");
        }

        $this->_fabrica_model->select('tbl_fabrica')
             ->setCampos(array('nome'))
             ->addWhere(array('fabrica' => $this->_fabrica));

        if (!$this->_fabrica_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o nome da Fabrica");
        }

        $res = $this->_fabrica_model->getPDOStatement()->fetch();

        if (!empty($res["nome"])) {
            $nome = strtolower($res["nome"]);
        }

        return $nome;
    }

    /**
     * Resgata o parametros adicionais da Fabrica
     *
     * @return string
     */
    public function getParametroAdicional()
    {
        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada");
        }

        $this->_fabrica_model->select('tbl_fabrica')
             ->setCampos(array('parametros_adicionais'))
             ->addWhere(array('fabrica' => $this->_fabrica));

        if (!$this->_fabrica_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o nome da Fabrica");
        }

        $res = $this->_fabrica_model->getPDOStatement()->fetch();
        
        if (!empty($res["parametros_adicionais"])) {
            $res = json_decode($res["parametros_adicionais"]);
        }
        
        return $res;
    }
    
}
