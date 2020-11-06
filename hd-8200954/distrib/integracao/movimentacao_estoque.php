<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

$sql = "SELECT referencia as ProdutoCodigo, '1' as DepositoNome,qtde as Quantidade, 'true' as EhEntrada FROM tbl_posto_estoque JOIN tbl_peca USING(peca) JOIN tbl_fabrica USING(fabrica) WHERE fabrica IN (123) AND qtde > 0 ";
$res = pg_query($sql);


$data = pg_fetch_all($res);
$headers = array("Authorization-Token:5bc00e47b1523ccfd4a05c81006d41244a77c67e078c7e3a3dc739185039e7cdf2c856cb955cff8d890a094a70f849b548d4e1bb4403fb9c4812b1c0e2646f076517c22759306d00997ad40a841544a166f3bac548a9b3987987246c274d98030f896535d6a1f89899e965fa429f0624ac95000e99af04823c1438986184feb9","User:valeria@acaciaeletro.com.br","App:AcaciaEletro","Content-Type: application/json; charset=utf-8");


$uri = "https://www.sigecloud.com.br/api/request/produtosestoque/salvar";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $uri);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

foreach($data as $valor) {
//	echo $valor."-".$linha; echo "<br>";
	$valor['descricao'] = utf8_encode($valor['descricao']);	
//cho json_encode($valor); echo "<br>";

	curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($valor));
	$response = curl_exec($ch);
	var_dump($response);
	flush();

}

curl_close($ch);
//echo "<pre>";

//print_r($data);

//echo json_encode($data);

?>
