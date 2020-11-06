<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

if($_POST['ajax']){
    $input          = $_POST['input'];
    $textarea       = $_POST['textarea'];
    $select         = $_POST['select'];
    $campo_vazio = 0;

    $string = array(";",":","/","+","\n","\r\n","\r");

    $pergunta           = explode("&",$input);
    $perguntaTextarea   = explode("&",$textarea);
    $perguntaSelect     = explode("&",$select);
    
    foreach($pergunta as $key=>$value){
        $dados = explode("=",$value);
        $valores[$dados[0]] = htmlentities(str_replace($string," ",rawurldecode($dados[1])),ENT_QUOTES,'UTF-8');
        if(strlen($dados[1]) == 0){
            if($dados[0] != "os_email" && $dados[0] != "outro_qual"){
                $msg_erro = "vazio";
                break;
            }
        }else{
            if($dados[0] != "os_email" && $dados[0] != "outro_qual" && $dados[0] != "language" && $dados[0] != "os" && $dados[0] != "produto"){
                $campo_vazio++;
            }
        }
        if($dados[0] == "os_email"){
            $os_email = $dados[1];
        }
        if($dados[0] == "language"){
            $language = $dados[1];
        }
    }

    foreach($perguntaTextarea as $key=>$value){
        $dadosT = explode("=",$value);
        $valoresT[$dadosT[0]] = htmlentities(str_replace($string," ",rawurldecode($dadosT[1])),ENT_QUOTES,'UTF-8');
    }

    foreach($perguntaSelect as $key=>$value){
        $dadosS = explode("=",$value);
        if(strlen($dadosS[1]) == 0){
            $msg_erro = "vazio";
            break;
        }
        $valoresS[$dadosS[0]] = $dadosS[1];
    }

    if($campo_vazio < 10){
        $msg_erro = "vazio";
    }

    $resultado = array_merge($valores,$valoresT,$valoresS);
    $resultado = json_encode($resultado);

    $res = pg_query($con,"BEGIN TRANSACTION");


    if(strlen($os_email) == 0){
        $sqlOsTemp = "
            INSERT INTO tbl_os (
                fabrica,
                posto,
                obs,
                data_abertura,
                data_digitacao
            ) VALUES (
                1,
                6359,
                'OS aberta para cadastro de pesquisa de satisfação para américa latina',
                CURRENT_DATE,
                CURRENT_DATE
            ) RETURNING os
        ";
        $resOsTemp = pg_query($con,$sqlOsTemp);
        $os_email = pg_fetch_result($resOsTemp,0,os);
    }

    $sqlGravar = "
        INSERT INTO tbl_laudo_tecnico_os (
            titulo      ,
            os          ,
            observacao  ,
            fabrica
        ) VALUES (
            'Pesquisa de satisfação - $language',
            $os_email                           ,
            '$resultado'                        ,
            1
        )
    ";
    $resGravar = pg_query($con,$sqlGravar);

    if(!pg_last_error($con) && strlen($msg_erro) == 0){
        $res = pg_query($con,"COMMIT TRANSACTION");
        $resposta = array(
            "status" => "ok",
            "language" => $language
        );
    }else{
        if(strlen($msg_erro) == 0){
            $msg_erro = pg_last_error($con);
        }
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        $resposta = array(
            "status" => "erro",
            "erro" => $msg_erro,
            "language" => $language
        );
    }
    echo json_encode($resposta);
    exit;
}

?>