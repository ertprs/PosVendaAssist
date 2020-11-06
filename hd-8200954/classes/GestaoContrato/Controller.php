<?php
namespace GestaoContrato;

class Controller {

    protected $_fabrica;
    protected $_con;
    public    $_login_admin;
    public    $_posto;

    public function __construct($login_fabrica, $con, $posto = null) {

        $this->_con           = $con;
        $this->_fabrica       = $login_fabrica;
        $this->_posto         = $posto;
    }

}