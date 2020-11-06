<?php

    include 'dbconfig.php';
    include 'includes/dbconnect-inc.php';
    include 'autentica_usuario.php';

    if(in_array($login_fabrica, array(1)) && isset($_GET["prestacao_servico"])){

        require_once "gera_contrato_posto.php";

    }

?>