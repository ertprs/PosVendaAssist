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
 $sql = "SELECT  tbl_produto.produto ,
    tbl_produto.referencia produto_referencia,
    tbl_peca.referencia peca_referencia,
    tbl_lista_basica.qtde as maxQuantity,
    1 as minQuantity
    FROM tbl_produto
    INNER JOIN tbl_lista_basica on tbl_produto.produto = tbl_lista_basica.produto and
    tbl_lista_basica.fabrica = tbl_produto.fabrica_i
    INNER JOIN tbl_peca on tbl_peca.peca = tbl_lista_basica.peca and
    tbl_peca.fabrica = tbl_lista_basica.fabrica
    WHERE tbl_produto.fabrica_i = 158 and tbl_lista_basica.data_input > '2016-10-17 00:00:00' ";	

$res = pg_query($con, $sql);


$i=0;
while($row = pg_fetch_assoc($res)){
    $i++;
    $productData = getProductEprodutivaId($authorizationKey, $row['produto_referencia']);
    $pecaData = getPecaEprodutivaId($authorizationKey, $row['peca_referencia']);

    $listaBasicaItem = vinculaProdutoPeca($authorizationKey, $productData, $pecaData, $row);
}
function getProductEprodutivaId($authKey, $codigo){

    $header = array(
        "Authorizationv2: ".$authKey
    );
    $url = 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/' . $codigo ;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $result = curl_exec($ch);
    if(!$result){
        echo ">>>>>>>>>> Não foi possível obter os dados (" .$url . ")" ;
    }
    curl_close($ch);

    return  validateResponseReturningArray($result, 'equipamento/codigo/'.$codigo);

}

function getPecaEprodutivaId($authKey, $codigo){

    $header = array(
        "Authorizationv2: ".$authKey
    );
    $url = 'http://telecontrol.eprodutiva.com.br/api/recurso/material/codigo/' . $codigo ;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $result = curl_exec($ch);
    if(!$result){
        echo "Não foi possível obter os dados (" . $url . ")";
    }
    curl_close($ch);

    return  validateResponseReturningArray($result, 'material/codigo/'.$codigo);

}

function vinculaProdutoPeca($authKey, $productData, $pecaData, $listaBasicaData){
    $url = 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/'. $productData['id'] .'/material';
  

    if(!empty($pecaData['id'])) {
	  $data = array(
        "material" => array(
            "id" => $pecaData['id']
        ),
        "maxQuantity" => $listaBasicaData['maxquantity'],
        "minQuantity" => $listaBasicaData['minquantity']
    );
    $json = json_encode($data);
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
        echo '>>>>>>>>>> CURL ERROR: (' .$url . " -> ". $result . ' -> ' . $json. ')' . ' *produto: '.$listaBasicaData['produto_referencia'] . ' *peca: ' . $listaBasicaData['peca_referencia']. "\n";
    }
    curl_close($ch);
    echo json_encode(validateResponseReturningArray($result, $json)) . " \n";
  }
}

function validateResponseReturningArray($curlResult, $requestParams = null){
    $arrResult = json_decode($curlResult, true);
    if(array_key_exists('error', $arrResult)){
        echo '>>>>>>>>>> Response: (' . $curlResult . ' -> ' . $requestParams . ')' . "\n";

    }
    return $arrResult;
}
echo 'Finish:#' . $i .'Records';
