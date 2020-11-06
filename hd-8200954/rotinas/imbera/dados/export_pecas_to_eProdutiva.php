<?php

include dirname(__FILE__) . '/../../../dbconfig.php';
include dirname(__FILE__) . '/../../../includes/dbconnect-inc.php';



global $fabrica ;
$fabrica = 158;

//Utilizado para a API telecontrol.eprodutiva.com.br/api

//chave teste persys
#$authorizationKey = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447'; 



#chave producao persys

$authorizationKey = '12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9';
$sql = "select tbl_peca.referencia as codigo,
               tbl_peca.descricao as material 
        FROM  tbl_peca
        WHERE fabrica = 158";

$res = pg_query($con, $sql);
$i=0;
while($row = pg_fetch_assoc($res)){
    $i++;
    $row['material'] = utf8_encode($row['material']);
    $row['medida']['id'] = 376;
    $json = json_encode($row);
   //die;
   postData($json,$authorizationKey);
   echo 'OK' . $row['codigo'] . "\n";
}

function postData($json, $authKey){
    $url = 'http://telecontrol.eprodutiva.com.br/api/recurso/material';

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
