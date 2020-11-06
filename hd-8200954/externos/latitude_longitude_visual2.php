<?php

	try{

		include dirname(__FILE__) . '/../dbconfig.php';
		include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
		$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
		$resInsertLog = pg_query($con, $sqlInsertLog);

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
			$string	= preg_replace("/[^a-zA-Z0-9\s]/", "", $string);

			return $string;
		}

		$fabrica = $argv[1];

		if(!file_exists("localizacao/$fabrica.txt") && $fabrica != 125){

			$fp = fopen("localizacao/postos_$fabrica.txt", "w+");

			$sqlUpdateNUll = "UPDATE tbl_posto SET empresa = 59 FROM tbl_posto_fabrica WHERE tbl_posto.empresa <> 89 AND tbl_posto.posto =tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica";
			$resUpdateNull = pg_query($con, $sqlUpdateNUll);

			$sql = "SELECT
						tbl_posto.posto, 
						tbl_posto.nome,
						tbl_posto.endereco, 
						tbl_posto.numero, 
						tbl_posto.bairro, 
						tbl_posto.cidade, 
						tbl_posto.estado, 
						tbl_posto.pais, 
						tbl_posto.cep, 
						tbl_posto.latitude AS longitude, 
						tbl_posto.longitude AS latitude
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
					AND tbl_posto.pais = 'BR'  
					AND tbl_posto_fabrica.fabrica = $fabrica 
					AND empresa <> 89 
					LIMIT 3000";
			$res = pg_query($con, $sql);

			while ($result = pg_fetch_object($res)){

				if (strlen($result->endereco) > 0 or strlen($result->bairro) > 0) {

					$address = array();

					#if (strtolower(trim($result->bairro)) == "centro") {
					#	continue;
					#}

					if(strlen($result->endereco) > 0){
						$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->endereco))));
					}

					if(strlen($result->numero) > 0){
						$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->numero))));
					}

					if((strlen($result->bairro) > 0) AND (strtolower(trim($result->bairro)) <> "centro")){
						$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->bairro))));
					}

					if(strlen($result->cidade) > 0){
						$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->cidade))));
					}

					if(strlen($result->estado) > 0){
						$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->estado))));
					}

					if(strlen($result->pais) > 0){
						$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->pais))));
					}else{
						$address[] = "Brasil";
					}

					$address = implode(",", $address);

				} else if (strlen($result->cep) > 0){

					$address = formatCEP($result->cep);

				} else {

					continue;

				}

				fwrite($fp, $result->posto." \t ".$result->nome." \t ".$result->latitude." \t ".$result->longitude." \t ".$address." \n ");

			}

			fclose($fp);

			$arquivo = file("localizacao/postos_$fabrica.txt"); 

			$fp2 = fopen("localizacao/$fabrica.txt", "w+");

			for($i = 0; $i < count($arquivo) - 1 ; $i++){

				$linha = $arquivo[$i];

				list($posto, $nome, $latitude, $longitude, $endereco) = explode("\t", $linha);

				$endereco = trim($endereco);

				/* Solicita as coordenadadas de localização do endereço do posto ao Google */
				$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$endereco."&sensor=false";

				$geocode = file_get_contents($url);
				$output = json_decode($geocode);

				/* Latitude e Longitude do Google */
				$lat = $output->results[0]->geometry->location->lat;
				$lng = $output->results[0]->geometry->location->lng;

				if (strlen($lat) > 0 and strlen($lng) > 0) {

					if ($lat <> $latitude or $lng <> $longitude) {
						/* Postos Atualizados - 89 */
						$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 89 WHERE posto = {$posto}";
					}else if($lat == $latitude && $lng == $longitude){
						/* Postos Não Atualizados */
						$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 89 WHERE posto = {$posto}";
					}

					$updateRes = pg_query($con, $update);

				}else{
					/* Postos que NÃO foram Localizados */
					fwrite($fp2, "Posto: {$posto} \t Nome: {$nome} \t Endereco: {$endereco} \n ");
					$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 59 WHERE posto = {$posto}";
				}

				sleep(10);

			}

			echo "Fabrica atualizada com Sucesso! \n";

		}else{
			echo "Processo de atualizacao ja realizado para esta Fabrica! \n";
		}

	}catch(Exception $e){
		echo $e->getMessage()."\n";
	}

?>
