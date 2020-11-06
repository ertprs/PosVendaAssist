<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	$sql = "SELECT  posto,
					a.cnpj,
					array(select fabrica from tbl_posto_fabrica p where p.posto = a.posto) fabricas
    		 FROM tbl_posto A 
    		 JOIN tbl_posto_fabrica using(posto) 
			 join tbl_tipo_posto using(tipo_posto, fabrica)
	 		 JOIN tbl_fabrica using(fabrica) 
	 		 WHERE pais='BR' 
		 		 AND credenciamento ='CREDENCIADO' 
		 		 AND ativo_fabrica 
				 and fabrica <> 10
				 and posto_interno is not true and tipo_revenda is not true and descricao !~*'revenda'
		 		 AND length(a.cnpj) =14 
		 		 AND tipo_posto <> 263 group by posto, a.cnpj order by random();";

    $res = pg_query($con,$sql);
    
	$total_registros = pg_num_rows($res);
    if($total_registros > 0){

		$curl = curl_init();
    	$postos = pg_fetch_all($res);
		
		foreach($postos as $posto) {
			try {
				curl_setopt_array($curl, array(
					CURLOPT_URL => ("https://www.receitaws.com.br/v1/cnpj/".$posto['cnpj']),
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => "",
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 60,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => "GET",
					CURLOPT_HTTPHEADER => array("Cache-Control: no-cache")
				));
				
				$response = curl_exec($curl);
				$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

				echo $posto['cnpj']." - HTTP code: $httpcode";
				if($httpcode == 200){
					$data = json_decode($response);
					if( "OK" == $data->status){
						processar($posto, $data, $response);
					}
					echo "- Status:".$data->status;
				}
				// else -> error de CNPJ invalido ou CNPJ ignorado pela receita
				echo "\n";
			} catch (Exception $e) {
				// ERROR 504 - erro de timeout -> será consultado na proxima execucao do job
				echo 'TIMEOUT\\n';
			}
			sleep(20);	
		}
	}

function processar($posto, $data, $response){
	global $con;

	$data->cep = str_replace(".", "", str_replace("-", "", $data->cep));
	
	$sql_insert = "INSERT INTO tbl_posto_receita(
				posto, cnpj, nome, fantasia, endereco,
				numero, complemento, bairro, cidade, 
				estado, cep, fabricantes, dados
			) VALUES (
				{$posto['posto']}, '{$posto['cnpj']}', '$data->nome', '$data->fantasia', '$data->logradouro',
				 '$data->numero', '$data->complemento', '$data->bairro','$data->municipio', 
				 '$data->uf','$data->cep','{$posto['fabricas']}','{$response}'
			)";
    
	$sql_update = "UPDATE SET nome = '$data->nome', fantasia = '$data->fantasia',
				endereco	= '$data->logradouro',
				numero 		= '$data->numero',
				complemento = '$data->complemento',
				bairro		= '$data->bairro',
				cidade		= '$data->municipio',
				estado		= '$data->uf',
				cep			= '$data->cep',
				fabricantes	= '{$posto['fabricas']}',
				dados		= '{$response}',
				data_input	= now()";

	$sql = "$sql_insert ON CONFLICT (posto) DO $sql_update";
    pg_query($con, $sql);
}
