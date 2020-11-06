<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

global $fabrica ;
$fabrica = 158;

$authorizationKey = '12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9';

$curl = curl_init();
curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento",
	    CURLOPT_RETURNTRANSFER => true,  
	      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
			      "authorizationv2: ".$authorizationKey
			        ),
			));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

$baseconhecimento = json_decode($response,true);

foreach ($baseconhecimento['data'] as $key => $value) {
	  $baseconhecimento['by_codigo'][$value['codigo']] = $value;
}

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

$rows = pg_fetch_all($res);

foreach ($rows as $key => $value) {
	  if(array_key_exists($value['codigo'], $baseconhecimento['by_codigo'])){
		      $rows[$key]['codigo_id'] = $baseconhecimento['by_codigo'][$value['codigo']]['id'];
		        }
	    if(array_key_exists($value['codigo_solucao'], $baseconhecimento['by_codigo'])){
		        $rows[$key]['codigo_solucao_id'] = $baseconhecimento['by_codigo'][$value['codigo_solucao']]['id'];
			  }
	    if($rows[$key]['codigo_id'] != "" && $rows[$key]['codigo_solucao_id'] != ""){
		        $bases_telecontrol[$rows[$key]['codigo_id']."_".$rows[$key]['codigo_solucao_id']] = $rows[$key];
			  }
}

$curl = curl_init();

curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/bases",
	    CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	        CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		      CURLOPT_CUSTOMREQUEST => "GET",
		        CURLOPT_HTTPHEADER => array(
				    "authorizationv2: ".$authorizationKey
				      ),
			      ));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

$bases_persys = json_decode($response,true);

foreach ($bases_persys['data'] as $key => $value) {
	  if(!array_key_exists($value['codigo'], $bases_telecontrol)){
		      $bases_persys['data'][$key]['action'] = "DISABLE";    
		        }else{    
				    $bases_telecontrol[$value['codigo']]['action'] = "VERIFIED";
				        $bases_persys['data'][$key]['action'] = "FIND";   
				      }
}

$bases_persys = array_filter($bases_persys['data'], function($r) {
	if ($r['action'] != 'DISABLE') {
		return false;
	}

	return true;
});

print_r($bases_persys);
