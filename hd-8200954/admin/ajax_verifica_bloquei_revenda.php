<?php 
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';
include 'funcoes.php';

    if(isset($_POST["VerificaBloqueioRevenda"]) == true){
        $cnpj = $_POST['cnpj'];
        $fabrica = $_POST['fabrica'];
        $resultado = VerificaBloqueioRevenda($cnpj, $fabrica);
        if(strlen($resultado)>0){
            echo json_encode(array('retorno' => utf8_encode($resultado)));
        }else{
            echo json_encode(array('retorno' => ''));
        }
        exit;
    }

?>