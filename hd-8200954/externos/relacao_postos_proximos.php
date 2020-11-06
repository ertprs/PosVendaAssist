<?php

	include '../admin/dbconfig.php';
	include '../admin/includes/dbconnect-inc.php';
	include '../admin/funcoes.php';

	ini_set('max_execution_time', 300);

	function compute_distance($from_lat, $from_lon, $to_lat, $to_lon, $units = 'K'){
	    $units = strtoupper(substr(trim($units),0,1));
	    // ENSURE THAT ALL ARE FLOATING POINT VALUES
	    $from_lat = floatval($from_lat);
	    $from_lon = floatval($from_lon);
	    $to_lat   = floatval($to_lat);
	    $to_lon   = floatval($to_lon);

	    // IF THE SAME POINT
	    if ( ($from_lat == $to_lat) && ($from_lon == $to_lon) ){
	        return "0.0";
	    }

	    // COMPUTE THE DISTANCE WITH THE HAVERSINE FORMULA
	    $distance = acos( sin(deg2rad($from_lat)) * sin(deg2rad($to_lat)) + cos(deg2rad($from_lat)) * cos(deg2rad($to_lat)) * cos(deg2rad($from_lon - $to_lon)));

	    $distance = rad2deg($distance);

	    if($distance == "0") return "0.0";
	    // DISTANCE IN MILES AND KM - ADD OTHERS IF NEEDED
	    $miles = (float) $distance * 69.0;
	    $km    = (float) $miles * 1.61;

	    // RETURN MILES
	    if ($units == 'M') return round($miles,1);

	    // RETURN KILOMETERS = MILES * 1.61
	    if ($units == 'K') return round($km,2);
	}

	function getLatLonConsumidor($address){

		$geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.'&sensor=false');

		$output = json_decode($geocode);

		$lat = $output->results[0]->geometry->location->lat;
		$lon = $output->results[0]->geometry->location->lng;

		$latLon = $lat."@".$lon;

		return $latLon;

	}

	function formatEndereco($end){
		$end = str_replace("R. ", "", $end);
		$end = str_replace(",,", "+", $end);
		$end = str_replace(", ,", "+", $end);
		$end = str_replace(" ", "+", $end);
		return $end;
	}

	$dados = array(
		/* "Avenida Morvan Dias de Figueiredo,Guarulhos",
		"Rua Frei Damiao, 355 Sao Bernardo do Campo",
		"Rua do Alho, 1095, Penha, Rio de Janeiro", 
		"Rodovia fernao dias, Contagem - Minas Gerais",
		"Av. Ayrton Senna, 2300 - Barra da Tijuca",
		"Rua Carlos Lisdegno Carlucci, Sao Paulo",
		"Rua Vitor Valpirio, 850 - Anchieta",
		"Rua Salgado Filho, 750, Pinhais, Parana",
		"Rod. Dom Pedro I - Campinas",
		"Av. Aricanduva, 5555 - Vila Matilde",
		"Rua Patativa, 280 - Sao Jose dos Campos",
		"Av. Pres.Castelo Branco,1865  - Ribeirao Preto",
		"74445-190 Goiania", */
		"Floriano Andre Cabrera,955,sao marcos, Sao Jose do Rio Preto , sao paulo",
		/* "17028-900 Bauru",
		"18110-005 Sorocaba",
		"R. Cap. Juvenal Figueiredo,570 - Sao Goncaalo rio de janeiro",
		"38406-267 Uberlandia",
		"86185-700 Londrina",
		"29160-001 vitoria",
		"11726-900 Praia Grande",
		"41305-280 Piraja, Salvador",
		"71200-140 brasilia",
		"50860-000 recife",
		"60880-000 fortaleza",
		"04455-330 interlagos",
		"41820-021 iguatemi",
		"59086-005 Natal",
		"78048-800 Cuiaba",
		"79034-001 Campo Grande",
		"65060-641 Sao Luis",
		"57080-000 Maceio",
		"88070-120 Florianopolis",
		"67010-000 Belem",
		"69077-000 Manaus",
		"05036-001 Lapa Sao Paulo",
		"58080-000 Joao Pessoa",
		"26031-480 Nova Iguacu",
		"07024-020 Guarulhos",
		"95001-970 Caxias do Sul",
		"64014-220 Teresina",
		"49025-620 Aracaju",
		"21061-020 Bonsucesso",
		"36081-374 Juiz de Fora",
		"81010-000 Curitiba",
		"31950-640 Belo Horizonte",
		"89216-500 Joinville",
		"08022-000 Merechal Tito",
		"09210-580 Santo Andre",
		"23092-000 Mendanha",
		"13050-080 Campinas II",
		"06020-010 Osasco",
		"69020-140 Manaus Moderna",
		"74912-651 Aparecida de Goiania",
		"55014-170 Caruaru",
		"28030-035 C. dos Goytacazes",
		"85860-290 Foz do Iguacu",
		"13420-835 Piracicaba",
		"56308-210 Petrolina",
		"93115-000 Sao Leopoldo",
		"39404-166 Montes Claros",
		"58104-590 Campina Grande",
		"78912-190 Porto Velho",
		"16071-340 Aracatuba",
		"19067-550 Presid. Prudente",
		"77021-230 Palmas",
		"29110-286 Vila Velha",
		"69905-801 Rio Branco",
		"12043-490 Taubate",
		"14403-077 Franca",
		"27259-010 Volta Redonda",
		"04763-280 Guarapiranga",
		"Rua São Judas Tadeu, 207 - Flores, Manaus",
		"17519-680 Marilia",
		"38055-020 Uberaba",
		"08773-600 Mogi das Cruzes",
		"11085-202 Santos",
		"45651-001 Costa do Cacau",
		"13563-470 Sao Carlos",
		"AV. Brasil, 43609 - Campo Grande ",
		"AV. Aricanduva, Sao Paulo",
		"Jardim, Nova America, Campinas",
		"Av. Laurita Ortega Marli, 144 - Vila das Oliveiras, Taboao da serra",
		"Rodovia 101, Cariacica",
		"88316-003 Itaipava ",
		"Butanta, Sao paulo", */
	);

	echo "<table border='1'>"; 

	foreach ($dados as $key => $end) {

		echo "<tr>";

			unset($bubble_sort);
			unset($posto_id);

			$end2 = formatEndereco($end);

			$latLon = getLatLonConsumidor($end2);
			list($from_lat, $from_lon) = explode("@", $latLon);
			
			$sql = "SELECT tbl_posto.posto AS posto,
				   UPPER(TRIM (tbl_posto.nome)) AS nome,
				   UPPER( TRIM (tbl_posto_fabrica.contato_endereco)) AS endereco,
				   tbl_posto_fabrica.fabrica AS fabrica, 
				   /* tbl_posto_fabrica.contato_numero AS numero,
				   LOWER( TRIM(tbl_posto_fabrica.contato_email)) AS email,
				   tbl_posto_fabrica.contato_fone_comercial AS telefone,
				   UPPER(TRIM(tbl_posto_fabrica.contato_bairro)) AS bairro, */
				   UPPER(TRIM(tbl_posto_fabrica.contato_cidade)) AS cidade,
				   tbl_posto_fabrica.contato_cep AS cep,
				   tbl_posto_fabrica.contato_estado AS estado,
				   tbl_posto.latitude AS lng,
				   tbl_posto.longitude AS lat
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING (posto)
				WHERE credenciamento = 'CREDENCIADO'
				   AND posto NOT IN(6359,20462)
				   AND tipo_posto <> 163
				   AND divulgar_consumidor IS TRUE
				   /* AND fabrica in (81,122,123,128) */
				   AND fabrica in (42, 104)
				ORDER BY fabrica";

			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				unset($bubble_sort);
				unset($posto_id);

				while ($d = pg_fetch_object($res)) {
					
					if(strlen($d->lat) > 0 && strlen($d->lng) > 0){

						$distacia_cliente = compute_distance($from_lat, $from_lon, $d->lat, $d->lng);

						$bubble_sort[] = $distacia_cliente;

						$color = ($d->posto == 355743) ? "style='color: #ff0000;'" : ""; // não localizou

						$posto_id[] = $d->posto." </td><td $color> ".$d->nome." </td><td> ".$d->cidade;

					}

				}

				asort($bubble_sort);

				foreach ($bubble_sort as $key => $km) {
					$dados_nome = $posto_id[$key];
					$km_posto = $km;
					break;
				}

				unset($bubble_sort);
				unset($posto_id);

			}

			echo "<td>$end</td><td> $dados_nome </td><td> $km_posto </td>";

		echo "</tr>";


	}

	echo "</table>";

?>