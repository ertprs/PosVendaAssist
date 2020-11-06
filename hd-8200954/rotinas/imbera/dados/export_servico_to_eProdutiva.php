<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

global $fabrica ;
$fabrica = 158;

//Utilizado para a API telecontrol.eprodutiva.com.br/api
$authorizationKey = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447'; 
echo $sql = "select servico_realizado as codigo, 
               descricao as nome
        from tbl_servico_realizado
        WHERE tbl_servico_realizado.fabrica =" . $fabrica . " 
        ORDER BY servico_realizado ASC";
$res = pg_query($con, $sql);
$i=0;
while($row = pg_fetch_assoc($res)){
    $i++;
    $row['nome'] = utf8_encode($row['nome']);
    $json = json_encode($row);

    postData($json,$authorizationKey,$row['codigo']);
    echo 'OK' . $row['codigo'] . "\n";
}

function postData($json, $authKey,$codigo){
    $url = 'http://telecontrol.eprodutiva.com.br/api/servico';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorizationv2: ".$authKey,
    ));

    $result = curl_exec($ch);
    if(!$result){
        echo '>>>>>>>>>> CURL ERROR: (' . $result . ' -> ' . $json. ')' . "\n";
    }
    curl_close($ch);
    validateResponseReturningArray($result, $json);
}

function validateResponseReturningArray($curlResult, $requestParams){
    $arrResult = json_decode($curlResult, true);
    if(array_key_exists('error', $arrResult)){
        echo '>>>>>>>>>> Response: (' . $curlResult . ' -> ' . $requestParams . ')' . "\n";

    }
    return $arrResult;
}
echo 'Finish:#' . $i .'Records';
