<?php

require "../../../dbconfig.php";
require "../../../includes/dbconnect-inc.php";
require "../../../autentica_admin.php";

if ($_GET["gera_pedido_manual"]) {
        try {
                $os = $_GET["os"];
                $troca = $_GET["troca"];
                if($troca=="true"){
			system("php ../../../rotinas/mondial/gera-pedido-troca.php {$os}", $ret);
	            }else{
	                system("php ../../../rotinas/mondial/gera-pedido.php {$os}", $ret);
	            }

        } catch(Exception $e) {
                $retorno = array("erro" => utf8_encode($e->getMessage()));
        }

        if (isset($retorno)) {
                echo json_encode($retorno);
        }
}
