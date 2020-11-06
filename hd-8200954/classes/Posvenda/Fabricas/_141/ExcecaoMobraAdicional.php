<?php
	
namespace Posvenda\Fabricas\_141;

use Posvenda\ExcecaoMobra;
use Posvenda\Model\ExcecaoMobra as ExcecaoMobraModel;

class ExcecaoMobraAdicional extends ExcecaoMobra
{
    private $_model;
    private $_dias;

    public function __construct($os, $fabrica) {
        parent::__construct($os, $fabrica, true);

        $this->calculaExcecaoMobra();

        $this->_model = new ExcecaoMobraModel($os, $fabrica);
        $this->_dias = $this->_model->totalDias();
        $this->_model->calculaExcecaoMobraDiasConserto($this->_dias);
    }
}

