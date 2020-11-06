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


$curl = curl_init();

curl_setopt_array($curl, array(
	  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento",
	    CURLOPT_RETURNTRANSFER => true,
	      CURLOPT_ENCODING => "",
	        CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		      CURLOPT_CUSTOMREQUEST => "GET",
		        CURLOPT_HTTPHEADER => array(
				    "authorizationv2: $authorizationKey",
				        "cache-control: no-cache",
					    "content-type: application/json",
						  ),
					  ));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err; 

	die;
} else {
	
$array_response = json_decode($response,true);

$dados = $array_response['data'];

//print_r($dados);

foreach($dados as $value) {
	
	if ($value['tipo'] == 0) {

		 $value['codigo'] . '-' . $value['titulo'] . "\n\n" ;

		$codigo_array = explode('_',$value['codigo']);

		 $sql = "SELECT defeito_reclamado
			from tbl_defeito_reclamado r
			join tbl_diagnostico d using(defeito_reclamado) 
			join tbl_familia f on f.familia = d.familia 
			where r.fabrica = 158 
					and r.codigo = '" .$codigo_array[0]. "'
					and substring(f.descricao from 1 for 3) = '".$codigo_array[1]. "'
					and d.ativo
					";
		$res = pg_query($sql);

		if (pg_num_rows($res)==0) {
			echo "ñao tem na telecontrol ativo: " . $value['codigo'] ;

			$curl = curl_init();
			$array_put = array("statusModel"=>0,"tipo"=>0,"titulo"=>$value['titulo']);

			echo	$json_put = json_encode($array_put);

			curl_setopt_array($curl, array(
				  CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/baseconhecimento/codigo/".$value['codigo'],
				    CURLOPT_RETURNTRANSFER => true,
				      CURLOPT_ENCODING => "",
				        CURLOPT_MAXREDIRS => 10,
					  CURLOPT_TIMEOUT => 30,
					    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					      CURLOPT_CUSTOMREQUEST => "PUT",
					        CURLOPT_POSTFIELDS => $json_put,
						  CURLOPT_HTTPHEADER => array(
							      "authorizationv2: 12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9",
							          "cache-control: no-cache",
								      "content-type: application/json",
								          "postman-token: c8f0623e-f7dc-ad67-8599-9a2b1ab0baba"
									    ),
								    ));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				  echo "cURL Error #:" . $err;
			} else {
				echo $response;
				echo "Inativou" ;
			}


		}
		echo "\n\n";				


	}


}






}




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

//echo $sql;

$res = pg_query($sql);
//die;


$i=0;

while($row = pg_fetch_assoc($res)){
    $i++;

    $dados = array('titulo'=>trim(utf8_encode($row['titulo'])),'codigo'=>trim($row['codigo']),'tipo'=>'0');

      $json = json_encode($dados) ;
//	die;
    postData($json,$authorizationKey,$row['codigo']);
    //echo 'OK' . $row['codigo_solucao'] . "\n";
}

function postData($json, $authKey,$codigo){
  $url = 'http://telecontrol.eprodutiva.com.br/api/baseconhecimento';
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
