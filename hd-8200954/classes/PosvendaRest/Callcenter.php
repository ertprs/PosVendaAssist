<?php 
namespace PosvendaRest;

use PosvendaRest\ApplicationKey;
use PosvendaRest\ClientRest;

class Callcenter extends ClientRest
{
    public $_header;
    public $_appKey;

    function __construct($fabrica) 
    {

        $this->_appKey  = new ApplicationKey();
        $this->_header  = $this->_appKey->getApplicationKeyByFabrica($fabrica);

        if (isset($this->_header["exception"])) {

        } else {
            $this->setHeader($this->_header);
        }

    }

    public function buscaInteracaoAtendimento($hd_chamado)
    {
        
        $this->setUrl('posvenda-callcenter', 'callcenter/interacaoCallcenter');
        return $this->get(["hd_chamado", $hd_chamado]);

    }

    public function buscaAtendimentoById($hd_chamado)
    {
        
        $this->setUrl('posvenda-callcenter', 'callcenter/');
        return $this->get(["hd_chamado", $hd_chamado]);

    }

    public function buscaOrigem()
    {
        
        $this->setUrl('posvenda-callcenter', 'origem/');
        return $this->get();

    }

    public function buscaProvidencia()
    {
        
        $this->setUrl('posvenda-callcenter', 'providencia/');
        return $this->get();

    }

    public function buscaClassicacao()
    {
        
        $this->setUrl('posvenda-callcenter', 'classificacao/');
        return $this->get();

    }


}