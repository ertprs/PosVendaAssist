<?php

include dirname(__FILE__) . '/../../../dbconfig.php';
include dirname(__FILE__) . '/../../../includes/dbconnect-inc.php';

global $fabrica ;
$fabrica = 158;

$logEmail = "Log Rotina: export_defeito_constatado_familia_to_eProdutiva.php\n\n";



#prod
#$authorizationKey = '12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9';

#test
#$authorizationKey = '4716427000141-dc3442c4774e4edc44dfcc7bf4d90447';



if ($_serverEnvironment == "production") {
	$authorizationKey = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
}else{
	$authorizationKey = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
}



//PEGANDO FAMILIAS
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/categoria",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "authorizationv2: ".$authorizationKey,
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
	$familias_persys = json_decode($response,true);
}

foreach ($familias_persys['data'] as $key => $value) {
	$familias_persys_aux[$value['codigo']] = $value;
}

$familias_persys = $familias_persys_aux;


$sql = "SELECT familia,descricao,ativo,codigo_familia from tbl_familia where fabrica = 158;";
$res = pg_query($con, $sql);
$familias_telecontrol = pg_fetch_all($res);

foreach ($familias_telecontrol as $key => $value) {
	if(array_key_exists(trim($value['codigo_familia']), $familias_persys)){
		$familias_persys[trim($value['codigo_familia'])]['familias_telecontrol'] = $value;	
	}	
}





$sql = "SELECT DISTINCT trim(f.codigo_familia) as codigo_familia, dc.codigo || '_C_' || substring(f.descricao from 1 for 3) AS codigo,  dc.descricao as descricao_defeito, d.ativo
FROM tbl_diagnostico AS d
INNER JOIN tbl_solucao AS s ON s.solucao = d.solucao
INNER JOIN tbl_familia AS f ON f.familia = d.familia
INNER JOIN tbl_defeito_constatado AS dc ON dc.defeito_constatado = d.defeito_constatado
WHERE d.fabrica = 158
AND d.solucao IS NOT NULL
AND d.defeito_constatado IS NOT NULL
AND d.defeito_reclamado IS NULL
AND d.familia IS NOT NULL and dc.codigo is not null";

$res = pg_query($con, $sql);
$res_defeitos_familias = pg_fetch_all($res);


foreach ($res_defeitos_familias as $key => $value) {
	$defeitos_familias[$value['codigo']] = $value;
	$defeitos_familias[$value['codigo']]['familias_persys'] = $familias_persys[$value['codigo_familia']];
}


foreach ($defeitos_familias as $key => $value) {


	$baseconhecimento = getBaseConhecimento($value['codigo']);

	if(array_key_exists("error", $baseconhecimento)){
		$dados = array(
			"codigo" => $value['codigo'],
			"titulo" =>  utf8_encode($value['descricao_defeito']),
  			"tipo" =>  "1"
		);
		$dados = json_encode($dados);

		$baseconhecimento = postNovaBase($dados);		
	}

	
	
	$baseconhecimento_gerada =  $baseconhecimento['id']."_".$familias_persys[$value['codigo_familia']]['id'];

	if(array_key_exists(0, $baseconhecimento['categorias'])){
		$finded = false;
		foreach ($baseconhecimento['categorias'] as $key1 => $value1) {
			$explode = explode("_", $value1['codigo']);			

			if($explode[1] == $value['familias_persys']['id']){
				$finded = true;
				if($value['ativo'] == 't'){					
					ativarBaseConhecimentoCategoria($baseconhecimento['categorias'][$key1]['id']);
				}else{
					inativarBaseConhecimentoCategoria($baseconhecimento['categorias'][$key1]['id']);
				}
			}else{
				inativarBaseConhecimentoCategoria($baseconhecimento['categorias'][$key1]['id']);
			}				
		}
		if($finded == false){
			vincularDefeitoFamilia($baseconhecimento['codigo'],$value['familias_persys']['id']);				
		}
	}else{								
		$explode = explode("_",$baseconhecimento['categorias']['codigo']);
		if($explode[1] == $value['familias_persys']['id']){
			if($value['ativo'] == 't'){
				ativarBaseConhecimentoCategoria($baseconhecimento['categorias']['id']);
			}else{
				inativarBaseConhecimentoCategoria($baseconhecimento['categorias']['id']);
			}
		}else{
			inativarBaseConhecimentoCategoria($baseconhecimento['categorias']['id']);
			vincularDefeitoFamilia($baseconhecimento['codigo'],$value['familias_persys']['id']);	
		}
	}
}

// foreach ($inativarCategoria as $key => $value) {
// 	inativarBaseConhecimentoCategoria($value);
// }


$logEmail = str_replace("\n", "<br>", $logEmail);

$jsonEmail = json_encode(array(
  "reference" => array(
    "type" => "email_log",
    "value" => "imbera"
  ),
  "from" => "noreply@telecontrol.com.br",
  "to" => array("wagner.rodrigues@imberacooling.com","guilherme.curcio@telecontrol.com.br"),
  "subject" => "Log de Sincronismo Telecontrol x Persys - Defeito Constatado x Familia",
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
  echo $response."\n\n";
}




/**
$dados = array(
			"codigo" => $value['codigo_solucao'],
			"titulo" =>  utf8_encode($value['descricao_solucao']),
  			"tipo" =>  "2"
		);

$dados = json_encode($dados);
*/

function postNovaBase($json){
	echo "postNovaBase($json)\n\n";

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
		$logEmail .= $json."\n\n";

	  	return json_decode($response,true);
	}
}

function vincularDefeitoFamilia($baseconhecimento_codigo,$familia_persys_id){
	echo "------------------------- vincularDefeitoFamilia($baseconhecimento_codigo,$familia_persys_id) ------------------------- \n\n";
	
	global $authorizationKey;
	global $logEmail;


	$json = json_encode(array(
		"categoria" => array(
			"id" => $familia_persys_id
			)
		));

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/codigo/".$baseconhecimento_codigo."/categorias",
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "POST",
	  CURLOPT_POSTFIELDS => $json,
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
		$logEmail .= "---- Vincular Defeito Constatado x Familia ----\n";
		$logEmail .= $json."\n";
		$logEmail .= $response."\n\n";

	  echo $response."\n\n";
	}
}



function ativarBaseConhecimentoCategoria($categoria){
	echo "ativarBaseConhecimentoCategoria(".$categoria.")\n\n";

	global $authorizationKey;
	global $logEmail;

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/categorias/".$categoria,
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
		echo $response."\n\n";
		$logEmail .= "---- Ativar Base de Conhecimento ----\n";
		$logEmail .= $categoria."\n";
		$logEmail .= $response."\n\n";

	  return true;
	}
}

function inativarBaseConhecimentoCategoria($categoria){
	echo "inativarBaseConhecimentoCategoria(".$categoria.")\n\n";

	global $authorizationKey;
	global $logEmail;

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/categorias/".$categoria,
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
		$logEmail .= "---- Inativar Base de Conhecimento ----\n";
		$logEmail .= $categoria."\n";
		$logEmail .= $response."\n\n";

	  echo $response."\n\n";
	  return true;
	}
}


function getBaseConhecimento($codigo){
	

	global $authorizationKey;
	global $logEmail;

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/codigo/".$codigo,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => "",
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 30,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => "GET",
	  CURLOPT_HTTPHEADER => array(
	    "authorizationv2: ".$authorizationKey,
	  ),
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
	  echo "cURL Error #:" . $err;
	} else {
	  return json_decode($response,true);
	}
}
