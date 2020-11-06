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

//envia defeito_reclamado tipo 0
$sql = "
SELECT DISTINCT dr.codigo || '_' || substring(f.descricao from 1 for 3) AS codigo, dr.descricao AS titulo
FROM tbl_diagnostico AS d
INNER JOIN tbl_familia AS f ON f.familia = d.familia
INNER JOIN tbl_defeito_reclamado AS dr ON dr.defeito_reclamado = d.defeito_reclamado
WHERE d.fabrica = 158
AND d.solucao IS NULL
AND d.defeito_constatado IS NULL
AND d.defeito_reclamado IS NOT NULL
AND d.familia IS NOT NULL and codigo is not null
";

//envia solucao tipo 2


//envia defeito_constatado tipo 1
$sql = "
SELECT DISTINCT dc.codigo || '_C_' || substring(f.descricao from 1 for 3) AS codigo, dc.descricao AS titulo
FROM tbl_diagnostico AS d
INNER JOIN tbl_familia AS f ON f.familia = d.familia
INNER JOIN tbl_defeito_constatado AS dc ON dc.defeito_constatado = d.defeito_constatado
WHERE d.fabrica = 158";

//envia defeito_constatado x familia
$sql = "SELECT DISTINCT trim(f.codigo_familia) as codigo_familia, dc.codigo || '_C_' || substring(f.descricao from 1 for 3) AS codigo
FROM tbl_diagnostico AS d
INNER JOIN tbl_solucao AS s ON s.solucao = d.solucao
INNER JOIN tbl_familia AS f ON f.familia = d.familia
INNER JOIN tbl_defeito_constatado AS dc ON dc.defeito_constatado = d.defeito_constatado
WHERE d.fabrica = 158
AND d.solucao IS NOT NULL
AND d.defeito_constatado IS NOT NULL
AND d.defeito_reclamado IS NULL
AND d.familia IS NOT NULL and dc.codigo is not null";

//envia solucao tipo 2
$sql = "
SELECT DISTINCT s.codigo || '_S_' || substring(f.descricao from 1 for 3) AS codigo, s.descricao AS titulo
FROM tbl_diagnostico AS d
INNER JOIN tbl_solucao AS s ON s.solucao = d.solucao
INNER JOIN tbl_familia AS f ON f.familia = d.familia
INNER JOIN tbl_defeito_constatado AS dc ON dc.defeito_constatado = d.defeito_constatado
WHERE d.fabrica = 158
AND d.solucao IS NOT NULL
AND d.defeito_constatado IS NOT NULL
AND d.defeito_reclamado IS NULL
AND d.familia IS NOT NULL and d.garantia is false
";
//envia solucao x familia
$sql = "

SELECT DISTINCT trim(f.codigo_familia) as codigo_familia, s.codigo || '_S_' || substring(f.descricao from 1 for 3) AS codigo
FROM tbl_diagnostico AS d
INNER JOIN tbl_solucao AS s ON s.solucao = d.solucao
INNER JOIN tbl_familia AS f ON f.familia = d.familia
INNER JOIN tbl_defeito_constatado AS dc ON dc.defeito_constatado = d.defeito_constatado
WHERE d.fabrica = 158
AND d.solucao IS NOT NULL
AND d.defeito_constatado IS NOT NULL
AND d.defeito_reclamado IS NULL
AND d.familia IS NOT NULL and d.garantia is false ";



//defeito constatado x solucao
$sql = "SELECT DISTINCT s.codigo || '_S_' || substring(f.descricao from 1 for 3) AS codigo_solucao, dc.codigo || '_C_' || substring(f.descricao from 1 for 3) AS codigo
FROM tbl_diagnostico AS d
INNER JOIN tbl_solucao AS s ON s.solucao = d.solucao
INNER JOIN tbl_familia AS f ON f.familia = d.familia
INNER JOIN tbl_defeito_constatado AS dc ON dc.defeito_constatado = d.defeito_constatado
WHERE d.fabrica = 158
AND d.solucao IS NOT NULL
AND d.defeito_constatado IS NOT NULL
AND d.defeito_reclamado IS NULL
AND d.familia IS NOT NULL and dc.codigo is not null and d.garantia is false";




$res = pg_query($con, $sql);
//echo $sql;

//die;


$i=0;

while($row = pg_fetch_assoc($res)){
    $i++;

    //$row['titulo'] = utf8_encode($row['titulo']);

  // $dados = array('categoria'=>array('codigo'=>$row['codigo_familia']));

   //$dados = array('titulo'=>trim(utf8_encode($row['titulo'])),'codigo'=>trim($row['codigo']),'tipo'=>'2');

    $dados = array('bondDemandVerification'=>array('codigo'=>$row['codigo_solucao']));

      $json = json_encode($dados) ;
//	die;
    postData($json,$authorizationKey,$row['codigo']);
    //echo 'OK' . $row['codigo_solucao'] . "\n";
}

function postData($json, $authKey,$codigo){
 # $url = 'http://telecontrol.eprodutiva.com.br/api/baseconhecimento';
   $url = "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/codigo/$codigo/categorias";
 $url = "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/codigo/$codigo/bases";
  #// die;
	echo $url;
	echo $json . "\n";

//	die;
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
