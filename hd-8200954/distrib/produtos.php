<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$sql = "SELECT '1 - Peça' as Categoria, 'Positron' as Marca, '84496066000295' as Fornecedor, referencia as Codigo, descricao as Nome,ncm,'00' as Genero,'PC' as EstoqueUnidade,ncm as GrupoTributario, '6949' as CFOPPadrao,'PC' as UnidadeTributavel,0 as EstoqueSaldo,'1' as PrecoCusto,'1' as LucroDinheiro FROM tbl_peca JOIN tbl_fabrica USING(fabrica) WHERE fabrica IN (153)  ORDER BY 3";
$res = pg_query($con,$sql);

$descricao = pg_result($res,0,1);

$data = pg_fetch_all($res);
$headers = array("Authorization-Token:5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9","User:valeria@acaciaeletro.com.br","App:AcaciaEletro","Content-Type: application/json; charset=utf-8");


$uri = "http://api.sigecloud.com.br/request/produtos/pesquisar?codigo=&numeroSerie=&nome=&genero=&categoria=&marca=Positron&pageSize=400&skip=0";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $uri);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$response = curl_exec($ch);
$file = fopen("/home/paulo/bs.txt", "w");
fputs($file,$response);
fclose();
curl_close($ch);
//echo "<pre>";

//print_r($data);

//echo json_encode($data);

?>
