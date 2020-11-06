<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";
include "autentica_admin_cliente.php";
$programa_insert = $_SERVER['PHP_SELF'];


header('content-type: application/json; charset=utf-8');
if(count($_POST)>0){
	if($_POST['os'] != "" && $_POST['interacao'] != ""){
		 $sql = "INSERT INTO tbl_os_interacao(
                                programa,
                                os             ,
                                comentario,
                                admin          
                            )VALUES(
                                '$programa_insert',
                                ".$_POST['os'].",
                                '".$_POST['interacao']."',
                                $login_admin   
                            )";


        $res = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);
        if($msg_erro != "" && $msg_erro != null){
    		echo json_encode(array("exception" => utf8_encode($msg_erro)));    	
        }else{
        	echo json_encode(array("msg" => "ok"));    	
        }
	}else{
		echo json_encode(array("exception" => utf8_encode("Digite o texto da interação!")));
	}
}else{
    if($_GET['os'] != ""){
        $interacoes = array();

        $os = $_GET['os'];

        $sql = "SELECT o.os, o.data, o.comentario, a.nome_completo, p.nome_fantasia
        from tbl_os_interacao o 
        LEFT JOIN tbl_admin a  ON o.admin = a.admin 
        INNER JOIN tbl_posto p ON o.posto = p.posto 
        WHERE o.fabrica = 30 AND o.os = $os ORDER BY o.os_interacao DESC;";

        $res = pg_query($con,$sql);
        for ($i = 0; $i < pg_num_rows($res); $i++) {

            $dataAux = strtotime(pg_fetch_result($res,$i,'data'));


            $interacoes[]  = array(
                "os"      => pg_fetch_result($res,$i,'os'),
                "data"     => date("d/m/Y h:i:s",$dataAux),
                "comentario" => pg_fetch_result($res,$i,'comentario'),
                "nome_completo"    => pg_fetch_result($res,$i,'nome_completo'),
                "nome_fantasia"    => pg_fetch_result($res,$i,'nome_fantasia')
            );
        }

        echo json_encode($interacoes);
    }else{
        echo json_encode(array("exception" => utf8_encode("GET não permitido")));    
    }
}