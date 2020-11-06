<?php 

header("Content-Type: text/html; charset=iso-8859-1");
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'classes/cep.php';
include_once('funcoes.php');
include_once 'class/AuditorLog.php';



if(isset($_POST['verifica_produto_serie']) == true){
    $numero_serie = $_POST["serie"];

    if(strlen(trim($numero_serie))>0){

        $sql = "SELECT produto_serie, observacao
                FROM tbl_produto_serie 
                WHERE '$numero_serie' between serie_inicial and serie_final 
                AND fabrica = $login_fabrica AND serie_ativa is true ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)>0){
            $observacao = utf8_encode(pg_fetch_result($res, 0, observacao));
            echo json_encode(array('retorno' => "erro", 'observacao' => "$observacao"));
        }else{
            echo json_encode(array('retorno' => "ok"));
        }
    }

    exit;
}


?>