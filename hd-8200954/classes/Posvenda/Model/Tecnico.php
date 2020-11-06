<?php

namespace Posvenda\Model;

class Tecnico extends AbstractModel
{

    private $_tecnico; 
    private $_fabrica;

    private $_nome;
    private $_email;
    private $_cpf;
    private $_ativo;

    public function __construct($fabrica, $tecnico = null)
    {
        parent::__construct('tbl_tecnico');

        if (!empty($tecnico)) {
            $this->_tecnico= $tecnico;
        }

        $this->_fabrica = $fabrica;
    }
    
    public function getTecnico(){
        return $this->_tecnico;
    }

    public function getNome(){
        return $this->_nome;
    }

    public function getEmail(){
        return $this->_email;
    }

    public function getCpf(){
        return $this->_cpf;
    }

    public function getAtivo(){
        return $this->_ativo;
    }

    public function setTecnico($tecnico){
        $this->_tecnico = $tecnico;
    }

    public function setNome($nome){
        $this->_nome = $nome;
    }

    public function setEmail($email){
        $this->_email = $email;
    }
    public function setCpf($cpf){
        $this->_cpf = $cpf;
    }

    public function setAtivo($ativo){
        $this->_ativo = $ativo;
    }
    
    public function save(){

        $queryBuilder = empty($this->_tecnico) ? $this->insert() : $this->update()->addWhere(array("tecnico" => $this->_tecnico));
        $this->setCampos(array("fabrica" => $this->_fabrica, "nome" => $this->_nome, "email" => $this->_email, "cpf" => $this->_cpf, "ativo" => $this->_ativo));
        
        $queryBuilder->prepare();
        return $queryBuilder->getQuery();
        
    }
}