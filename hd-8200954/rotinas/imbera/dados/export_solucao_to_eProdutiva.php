<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

global $fabrica ;
$fabrica = 158;

//Utilizado para a API telecontrol.eprodutiva.com.br/api
$authorizationKey = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447'; 
$sql = "select referencia as codigo, 
               tbl_produto.descricao as equipamento,
               tbl_familia.codigo_familia as categoria
        from tbl_produto
        inner join tbl_familia on tbl_familia.familia = tbl_produto.familia and 
                                  tbl_familia.fabrica  = tbl_produto.fabrica_i
        WHERE tbl_produto.fabrica_i =" . $fabrica . " AND 
              tbl_produto.referencia <> '41008983' AND 
              tbl_produto.referencia <> '41008780' AND 
              tbl_produto.referencia <> '1011734' AND
              tbl_produto.referencia <> '1011750'
        ORDER BY referencia ASC";
$sql = "SELECT codigo,tbl_solucao.descricao as titulo, 2 as tipo 
		FROM tbl_diagnostico
	JOIN tbl_solucao using(solucao)
     WHERE tbl_diagnostico.fabrica = 158 ;

";


$sql = "SELECT codigo,tbl_familia.codigo_familia as categoria 
		FROM tbl_diagnostico
	JOIN tbl_solucao using(solucao)
	JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia
     WHERE tbl_diagnostico.fabrica = 158 ;

";


//$sql = "select referencia as codigo, 
//               tbl_produto.descricao as equipamento,
//               tbl_familia.codigo_familia as categoria
//               from tbl_produto
//               inner join tbl_familia on tbl_familia.familia = tbl_produto.familia and 
//               fabrica = " . $fabrica ."
//               ORDER BY referencia ASC";
$res = pg_query($con, $sql);
$i=0;
while($row = pg_fetch_assoc($res)){
    $i++;
    $row['titulo'] = utf8_encode($row['titulo']);

    $dados = array('categoria'=>array('codigo'=>trim($row['categoria'])));

    $json = json_encode($dados);

    postData($json,$authorizationKey,$row['codigo']);
    echo 'OK' . $row['codigo'] . "\n";
}

function postData($json, $authKey,$codigo){
    $url = 'http://telecontrol.eprodutiva.com.br/api/baseconhecimento';
    $url = "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/codigo/$codigo/categorias";
    
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
