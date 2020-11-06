<?php

namespace Posvenda\Fabricas\_165;

use Posvenda\Model\Os as OsModel;
use Posvenda\ExcecaoMobra;
use Posvenda\Regras;
use Posvenda\MaoDeObra as MaoDeObraPosvenda;

class MaoDeObra extends MaoDeObraPosvenda
{
    public function __construct($os, $fabrica, $conn=null)
    {
        parent::__construct($os, $fabrica, $conn);

    }

    /**
     * Calcula a mão-de-obra da OS
     *
     * @return MaoDeObra
     */
    public function calculaMaoDeObra($valoMaoObra, $os)
    {
        
        if (empty($os)) {
            $os = $this->_os;
        }

        try {
            $this->getOsModel()->updateMaoDeObra($valoMaoObra,$os);
            $this->calculaExcecaoMaoDeObra();

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $this;
    }
}
