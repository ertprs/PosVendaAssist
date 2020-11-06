<?php

include dirname(__FILE__) . '/../../../dbconfig.php';
include dirname(__FILE__) . '/../../../includes/dbconnect-inc.php';

global $fabrica ;
$fabrica = 158;

$logEmail = "Log Rotina: export_defeito_constatado_solucao_to_eProdutiva.php\n\n";

#prod
#$authorizationKey = '12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9';

#test
#$authorizationKey = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447';

if ($_serverEnvironment == "production") {
	$authorizationKey = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
}else{
	$authorizationKey = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
}


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

$sql = "SELECT DISTINCT s.descricao as descricao_solucao, s.codigo || '_S_' || substring(f.descricao from 1 for 3) AS codigo_solucao, dc.descricao as descricao_defeito, dc.codigo || '_C_' || substring(f.descricao from 1 for 3) AS codigo, d.ativo
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
	}else{
		$rows[$key]['codigo_action'] = "INSERT";		
	}

	if(array_key_exists($value['codigo_solucao'], $baseconhecimento['by_codigo'])){
		$rows[$key]['codigo_solucao_id'] = $baseconhecimento['by_codigo'][$value['codigo_solucao']]['id'];
	}else{		
		$rows[$key]['codigo_solucao_action'] = "INSERT";		
	}

	if($rows[$key]['codigo_id'] != "" && $rows[$key]['codigo_solucao_id'] != ""){
		$bases_telecontrol[$rows[$key]['codigo_id']."_".$rows[$key]['codigo_solucao_id']] = $rows[$key];
	}
}

//insere bases não encontradas
foreach ($rows as $key => $value) {	
	if($value['codigo_solucao_action'] == "INSERT"){
		$dados = array(
			"codigo" => $value['codigo_solucao'],
			"titulo" =>  utf8_encode($value['descricao_solucao']),
  			"tipo" =>  "2"
		);

		$dados = json_encode($dados);

		echo "NOVA BASEE SOLUCAO >>>>\n";		
		$response = postNovaBase($dados);

		$rows[$key]['codigo_solucao_id'] = $response['id'];				
		unset($rows[$key]['codigo_solucao_action']);
	}

	if($value['codigo_action'] == "INSERT"){		
		$dados = array(
			"codigo" => $value['codigo'],
			"titulo" =>  utf8_encode($value['descricao_defeito']),
  			"tipo" =>  "1"
		);

		$dados = json_encode($dados);

		echo "NOVA BASEE DEFEITO >>>>\n";		
		$response = postNovaBase($dados);

		$rows[$key]['codigo_id'] = $response['id'];			
		unset($rows[$key]['codigo_action']);
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
$codigos_persys = array();

foreach ($bases_persys['data'] as $key => $value) {

	if(!array_key_exists($value['codigo'], $bases_telecontrol)){
		$bases_persys['data'][$key]['action'] = "DISABLE";
		echo "DISABLE >>>>>>>>>>>>>>>>>>>>>>>>>>> ".$value['id']." \n";
		disableRelacionamento($value['id']);
	}else{    
		$bases_telecontrol[$value['codigo']]['action'] = "VERIFIED";
	    $bases_persys['data'][$key]['action'] = "FIND";   
	    

	    if($bases_telecontrol[$value['codigo']]['ativo'] == 't'){
	    	echo "ENABLE >>>>>>>>>>>>>>>>>>>>>>>>>>> ".$value['id']." \n";
	    	enableRelacionamento($value['id']);
	    }else{
	    	echo "DISABLE >>>>>>>>>>>>>>>>>>>>>>>>>>> ".$value['id']." \n";
	    	disableRelacionamento($value['id']);
	    }
	}

	$codigos_persys[] = $value['codigo'];
}

foreach ($bases_telecontrol as $key => $value) {
	if(!in_array($key, $codigos_persys)){
		$bases_telecontrol[$key]['action'] = "INSERT";
		echo "BASE ".$value['codigo_id']."______".$value['codigo_solucao_id']."\n";
		postNovoRelacionamento($value['codigo_id'],$value['codigo_solucao_id']);		
		
	}else{
		$bases_telecontrol[$key]['action'] = "VERIFIED";
	}
}


$logEmail = str_replace("\n", "<br>", $logEmail);

$jsonEmail = json_encode(array(
  "reference" => array(
    "type" => "email_log",
    "value" => "imbera"
  ),
  "from" => "noreply@telecontrol.com.br",
  "to" => array("wagner.rodrigues@imberacooling.com","guilherme.curcio@telecontrol.com.br"),
  "subject" => "Log de Sincronismo Telecontrol x Persys",
  "body" => $logEmail
));

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://api2.telecontrol.com.br/communicator/email",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $jsonEmail,
  CURLOPT_HTTPHEADER => array(
    "access-application-key: 3c8f3fbd89576e1116c185dc31302be433c577c0",
    "access-env: PRODUCTION",    
    "content-type: application/json",    
    "smtp-account: noreply@tc"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}


echo "FIM >>>>>>>>>>>>>>";

function postNovaBase($json){
		
	global $authorizationKey;	
	global $logEmail;

	 $curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento",
	  CURLOPT_RETURNTRANSFER => true,	  	  	  	  
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $json,
	  CURLOPT_HTTPHEADER => array(
	  	"Content-Type: application/json",
	    "authorizationv2: ".$authorizationKey,
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	 curl_close($curl);



	if ($err) {
		echo "----------------------------------------------\n";
		print_r($json);
	  	echo "cURL Error #:" . $err;
	  	echo "----------------------------------------------\n";
	} else {
		echo "NOVA BASE >>>>>>>>>>>>>>>\n";
		echo $response."\n\n";

		$logEmail .= "Nova Base de Conhecimento\n";
		$logEmail .= $json."\n";
		$logEmail .= $response."\n\n";

	  	return json_decode($response,true);
	 }
}

function postNovoRelacionamento($idBase,$idRelacionar){
	global $authorizationKey;	
	global $logEmail;

	$data = array(
		"bondDemandVerification"=>array(
			"id"=> $idRelacionar
		)
	);

	$json = json_encode($data);


	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/".$idBase."/bases",
	  CURLOPT_RETURNTRANSFER => true,	  
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $json,
	  CURLOPT_HTTPHEADER => array(
	  	"Content-Type: application/json",
	    "authorizationv2: ".$authorizationKey,	    
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  	echo "----------------------------------------------\n";
		print_r($json);
	  	echo "cURL Error #:" . $err;
	  	echo "----------------------------------------------\n";
	} else {
	  	echo "NOVO REL >>>>>>>>>>>>>>>\n";
	  	echo $response."\n\n";

	  	$logEmail .= "Novo Relacionamento\n";
		$logEmail .= $json."\n";
		$logEmail .= $response."\n\n";

	  	return json_decode($response,true);
	}
}

function enableRelacionamento($idBase){	
	global $authorizationKey;	
	global $logEmail;

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/bases/".$idBase,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "PUT",
	  CURLOPT_POSTFIELDS => "{\n\t\"statusModel\": 1\n}",
	  CURLOPT_HTTPHEADER => array(
	    "authorizationv2: ".$authorizationKey,	    
	    "content-type: application/json",	    
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
		$logEmail .= "Habilitar Relacionamento\n";
		$logEmail .= $idBase."\n";
		$logEmail .= $response."\n\n";

	  echo $response;
	}
}

function disableRelacionamento($idBase){
	global $authorizationKey;	
	global $logEmail;

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/bases/".$idBase,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "PUT",
	  CURLOPT_POSTFIELDS => "{\n\t\"statusModel\": 0\n}",
	  CURLOPT_HTTPHEADER => array(
	    "authorizationv2: ".$authorizationKey,	    
	    "content-type: application/json",	    
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
		$logEmail .= "Desabilitar Relacionamento\n";
		$logEmail .= $idBase."\n";
		$logEmail .= $response."\n\n";

	  echo $response;
	}
}

// $bases_persys = array_filter($bases_persys['data'], function($r) {
// 	if ($r['action'] != 'DISABLE') {
// 		return false;
// 	}

// 	return true;
// });

// print_r($bases_persys);