<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	function formatCEP($cep){
	    $cep = preg_replace("/[^0-9]/", "", $cep);

	    $cepI = substr($cep, 0, 5);
	    $cepF = substr($cep, 5);

	    $cep = $cepI."-".$cepF;

	    return $cep;
	}

	function acentos ($string) {
		$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
		$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
		$string = str_replace($array1, $array2, $string);

		return $string;
	}
	$fabrica     = strtolower($argv[1]);
	if(!empty($fabrica)) {
		$cond = " AND tbl_posto_fabrica.fabrica = $fabrica ";
	}
	$sql = "SELECT 
				tbl_posto.posto, 
				tbl_posto.endereco, 
				tbl_posto.numero, 
				tbl_posto.bairro, 
				tbl_posto.cidade, 
				tbl_posto.estado, 
				'BRASIL' as  pais, 
				tbl_posto.cep, 
				tbl_posto.latitude AS longitude, 
				tbl_posto.longitude AS latitude
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_posto.pais = 'BR'
			AND tbl_posto.latitude IS NULL
			AND tbl_posto.longitude IS NULL
			$cond
			ORDER BY random()
			LIMIT 1500";
	$res = pg_query($con, $sql);

	while ($result = pg_fetch_object($res)) {
		unset($address);

		if (strlen($result->endereco) > 0 ) {
			foreach ($result as $key => $value) {
				if (in_array($key, array("posto", "latitude", "longitude", "cep","bairro"))) {
					continue;
				}

				if ($key == "bairro" and strtolower(trim($value)) == "centro") {
					continue;
				}

				if (strlen($value) > 0) {
					$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($value))));
				}
			}

			$address = implode(",+", $address);
		} else if (strlen($result->cep) > 0) {
			$address = formatCEP($result->cep);
		} else {
			continue;
		}

		$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";

		$geocode = file_get_contents($url);
		$output = json_decode($geocode);

		$endereco = (array) $output->results[0]->address_components; 
		                                                                  
		foreach($endereco as $key => $value) {

	        $value = (array)$value;

	        if($value['types'][0] == 'administrative_area_level_1'){
	        	$estado_google = $value['short_name'];
	        }

	        if($value['types'][0] == 'locality'){

	        	$cidade_posto = strtoupper(trim($result->cidade));
	        	$estado_posto = $result->estado;

	        	$cidade_google = $value['long_name'];
	        	$cidade_google = acentos($cidade_google);
	        	$cidade_google = strtoupper(trim($cidade_google));

	        	if(strstr($cidade_google, ",")){
	        		list($dado1, $dado2) = explode(",", $cidade_google);
	        		$cidade_google = trim($dado2); 
	        	}

	        	if($cidade_posto != $cidade_google && $estado_posto != $estado_google){

	        		/* echo "<strong>Posto:</strong> ".$result->nome."<br />";
	            	echo "<strong>Cidade Posto:</strong> ".$cidade_posto." - ".$result->estado."<br />";
	            	echo "<strong>Cidade Googl:</strong> ".$cidade_google." - ".$estado_google."<br />";
	            	echo "<strong>URL:</strong> <a href='$url' target='_blank'>".$url."</a><hr />"; */

	            	$address = $cidade_posto.", ".$estado_posto;

	            	$address = trim(str_replace(" ", "+", $address));

            		$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";

					$geocode = file_get_contents($url);
					$output = json_decode($geocode);

	        	}
	        }
	    }

		$lat = $output->results[0]->geometry->location->lat;
		$lng = $output->results[0]->geometry->location->lng;

		if (strlen($lat) > 0 and strlen($lng) > 0) {
			$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 59 WHERE posto = {$result->posto}";
			$updateRes = pg_query($con, $update);
		}
	}
	$sql = "SELECT
				tbl_posto.posto, 
				tbl_posto.nome,
				tbl_posto.endereco, 
				tbl_posto.numero, 
				tbl_posto.bairro, 
				tbl_posto.cidade, 
				tbl_posto.estado, 
				'BRASIL' as  pais, 
				tbl_posto.cep, 
				tbl_posto.latitude AS longitude, 
				tbl_posto.longitude AS latitude
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_posto.pais = 'BR'  
			AND tbl_posto.latitude IS NOT NULL
			AND tbl_posto.longitude IS NOT NULL
			and tbl_posto.empresa isnull 
			$cond
			ORDER BY random()
			LIMIT 1000";
	$res = pg_query($con, $sql);

	while ($result = pg_fetch_object($res)) {
		unset($address);

		if (strlen($result->endereco) > 0 ) {
			foreach ($result as $key => $value) {
				if (in_array($key, array("posto", "nome", "latitude", "longitude", "cep","bairro"))) {
					continue;
				}

				if ($key == "bairro" and strtolower(trim($value)) == "centro") {
					continue;
				}

				if (strlen($value) > 0) {
					$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($value))));
				}
			}

			$address = implode(",+", $address);
		} else if (strlen($result->cep) > 0) {
			$address = formatCEP($result->cep);
		} else {
			continue;
		}

		$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";

		$geocode = file_get_contents($url);
		$output = json_decode($geocode);

		$endereco = (array) $output->results[0]->address_components; 

		foreach($endereco as $key => $value) {

	        $value = (array)$value;

	        if($value['types'][0] == 'administrative_area_level_1'){
	        	$estado_google = $value['short_name'];
	        }

	        if($value['types'][0] == 'locality'){

	        	$cidade_posto = strtoupper(trim($result->cidade));
	        	$estado_posto = $result->estado;

	        	$cidade_google = $value['long_name'];
	        	$cidade_google = acentos($cidade_google);
	        	$cidade_google = strtoupper(trim($cidade_google));

	        	if(strstr($cidade_google, ",")){
	        		list($dado1, $dado2) = explode(",", $cidade_google);
	        		$cidade_google = trim($dado2); 
	        	}

	        	if($cidade_posto != $cidade_google && $estado_posto != $estado_google){

	        		/* echo "<strong>Posto:</strong> ".$result->nome."<br />";
	            	echo "<strong>Cidade Posto:</strong> ".$cidade_posto." - ".$result->estado."<br />";
	            	echo "<strong>Cidade Googl:</strong> ".$cidade_google." - ".$estado_google."<br />";
	            	echo "<strong>URL:</strong> <a href='$url' target='_blank'>".$url."</a><hr />"; */

	            	$address = $cidade_posto.", ".$estado_posto;

	            	$address = trim(str_replace(" ", "+", $address));

            		$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";

					$geocode = file_get_contents($url);
					$output = json_decode($geocode);

	        	}
	        }
	    }

		$lat = $output->results[0]->geometry->location->lat;
		$lng = $output->results[0]->geometry->location->lng;

		if (strlen($lat) > 0 and strlen($lng) > 0) {
			if ($lat <> $result->latitude or $lng <> $result->longitude) {
				$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 59 WHERE posto = {$result->posto}";
				$updateRes = pg_query($con, $update);
			}
		}
	}


exit;
