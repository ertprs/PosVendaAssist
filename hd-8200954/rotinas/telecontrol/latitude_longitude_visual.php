<!DOCTYPE html />

<html>

	<?php

	/*
	Rotina: Atualiza localização dos Postos - Latitude e Longitude
	*/

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
		$string	= preg_replace("/[^a-zA-Z0-9\s]/", "", $string);

		return $string;
	}

	?>

	<head>
		<title>Localização de Postos</title>

		<style>
			html{
				font: 15px arial;
				color: #333;
			}
			table{
				font: 12px arial;
				border: 1px solid #CCC;
				width: 100%;
			}
			table tr th{
				background-color: #cecece;
			}
		</style>

	</head>

	<body>

		<h1>Localização de Postos</h1>

		<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">

			<strong>Fábrica</strong> <br />
			<select name="fabrica">
				<option value=""></option>
				<?php
					$sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) > 0){
						while($fabrica = pg_fetch_object($res)){
							$selected = (isset($_POST['fabrica']) && $_POST['fabrica'] == $fabrica->fabrica) ? "selected" : "";
							echo "<option value='".$fabrica->fabrica."' ".$selected." > ".$fabrica->nome." - ".$fabrica->fabrica."</option>";
						}
					}
				?>
			</select>

			<br /> <br />

			<input type="submit" value="Localizar Postos" />

		</form>

		<?php

		if(isset($_POST['fabrica'])){

			// $fabrica     = strtolower($argv[1]);
			$fabrica     = $_POST['fabrica'];

			/* Não atualizar os postos da Saint-Gobain, pois foi atualizado manualmente */
			if($fabrica == 125){
				echo "<h1 style='color: red;'>Esta Fábrica foi atualizada manualmente...</h1>";
				exit;
			}

			if(!file_exists("localizacao/$fabrica.txt")){

				$fp = fopen("localizacao/$fabrica.txt", "w+");

				fwrite($fp, "* O endereco se encontra no seguinte formato -> rua, numero, bairro, cidade, estado, pais \n \n ");

				$sqlUpdateNUll = "UPDATE tbl_posto SET empresa = 59 FROM tbl_posto_fabrica WHERE tbl_posto.posto =tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica";
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

						if (strtolower(trim($result->bairro)) == "centro") {
							continue;
						}

						if(strlen($result->endereco) > 0){
							$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->endereco))));
						}

						if(strlen($result->numero) > 0){
							$address[] = str_replace(" ", "+", str_replace(",", "", trim(acentos($result->numero))));
						}

						if(strlen($result->bairro) > 0){
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

					/* Solicita as coordenadadas de localização do endereço do posto ao Google */
					$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address."&sensor=false";

					$geocode = file_get_contents($url);
					$output = json_decode($geocode);

					/* Latitude e Longitude do Google */
					$lat = $output->results[0]->geometry->location->lat;
					$lng = $output->results[0]->geometry->location->lng;

					if (strlen($lat) > 0 and strlen($lng) > 0) {

						if ($lat <> $result->latitude or $lng <> $result->longitude) {
							/* Postos Atualizados - 89 */
							$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 89 WHERE posto = {$result->posto}";
						}else if($lat == $result->latitude && $lng == $result->longitude){
							/* Postos Não Atualizados */
							$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 89 WHERE posto = {$result->posto}";
						}

						$updateRes = pg_query($con, $update);

					}else{
						/* Postos que NÃO foram Localizados */
						fwrite($fp, "Posto: {$result->posto} \t Nome: {$result->nome} \t Endereco: {$address} \n ");
						$update = "UPDATE tbl_posto SET latitude = $lng, longitude = $lat, empresa = 59 WHERE posto = {$result->posto}";
					}

					sleep(10);

				}

				fclose($fp);

				echo "<em style='color: red;'>Processo finalizado... por favor realize o download dos postos não localizados no link abaixo!</em> <br /> <br />";

				echo "<a href='localizacao/$fabrica.txt' target='_blank'>Arquivo Download</a>";

			}else{

				echo "<em style='color: red;'>Processo de atualização já realizado para está Fábrica!</em> <br /> <br />";

			}

		}

		?>

	</body>

</html>
