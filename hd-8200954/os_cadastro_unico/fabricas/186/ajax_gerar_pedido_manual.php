<?php

require "../../../dbconfig.php";
require "../../../includes/dbconnect-inc.php";
require "../../../autentica_admin.php";

if ($_GET["gera_pedido_manual"]) {
    try {

        $os = $_GET["os"];
        system("php ../../../rotinas/mqprofessionalonline/gera-pedido-posto-interno-manual.php {$os}", $ret);

    } catch(Exception $e) {
        $retorno = array("erro" => utf8_encode($e->getMessage()));
    }

    if (isset($retorno)) {
        echo json_encode($retorno);
    }
}
