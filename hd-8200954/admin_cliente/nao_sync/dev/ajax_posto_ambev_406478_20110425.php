
<?php
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";

if ($pl <> 'sim') {
	include 'autentica_admin.php';
}

function acentos1( $texto ){
	 $array1 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
	 $array2 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" );
	return str_replace( $array1, $array2, $texto );
}
function acentos2( $texto ){
	 $array1 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
	$array2 = array("Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" ,"Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
	return str_replace( $array1, $array2, $texto );
}
function acentos3( $texto ){
 $array1 = array("б", "а", "в", "г", "д", "й", "и", "к", "л", "н", "м", "о", "п", "у", "т", "ф", "х", "ц", "ъ", "щ", "ы", "ь", "з" , "Б", "А", "В", "Г", "Д", "Й", "И", "К", "Л", "Н", "М", "О", "П", "У", "Т", "Ф", "Х", "Ц", "Ъ", "Щ", "Ы", "Ь", "З" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

function geoGetCoords($address,$depth = 0) {

	$lookup_server = array(	'GOOGLE'=> 'maps.google.com',
							'YAHOO'	=> 'api.local.yahoo.com');

	$lookup_service = 'GOOGLE';
	$key_api = (strpos($_SERVER['HTTP_HOST'], '.net.br'))?	'ABQIAAAA58Y5NwUpOJR6Pos3XqtrxBSXzuw64REmAHFbLybXzpS0ysbbShRqfU4U8Ml9-PIIJrRfhec89KxBWA' :
															'ABQIAAAA4k5ZzVjDVAWrCyj3hmFzTxR_fGCUxdSNOqIGjCnpXy7SRGDdcRTb85b5W8d9rUg4N-hhOItnZScQwQ';

	switch($lookup_service) {
					
		case 'GOOGLE':
			
			$_url = sprintf('http://%s/maps/geo?&q=%s&output=csv&key=%s',$lookup_server['GOOGLE'],rawurlencode($address),$key_api);

			$_result = false;
			$_result = file_get_contents($_url);

			if($_result) {
				$_result_parts = explode(',',$_result);
				if($_result_parts[0] != 200)
					return false;
				$_coords['lat'] = $_result_parts[2];
				$_coords['lon'] = $_result_parts[3];
			}
			
			break;
		
		case 'YAHOO':
		default:
					
			$_url = 'http://%s/MapsService/V1/geocode';
			$_url .= sprintf('?appid=%s&location=%s',$lookup_server['YAHOO'],$key_api,rawurlencode($address));

			$_result = false;

			if($_result = file_get_contents($_url)) {
				preg_match('!<Latitude>(.*)</Latitude><Longitude>(.*)</Longitude>!U', $_result, $_match);

				$_coords['lon'] = $_match[2];
				$_coords['lat'] = $_match[1];

			}
			
			break;
	}
	return $_coords;
}

/**
 * get distance between to geocoords using great circle distance formula
 * 
 * @param float $lat1
 * @param float $lat2
 * @param float $lon1
 * @param float $lon2
 * @param float $unit   M=miles, K=kilometers, N=nautical miles, I=inches, F=feet
 */
function geoGetDistance($lat1,$lon1,$lat2,$lon2,$unit='M') {
	
  // calculate miles
  $M =  69.09 * rad2deg(acos(sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon1 - $lon2)))); 

  switch(strtoupper($unit))
  {
	case 'K':
	  // kilometers
	  return $M * 1.609344;
	  break;
	case 'N':
	  // nautical miles
	  return $M * 0.868976242;
	  break;
	case 'F':
	  // feet
	  return $M * 5280;
	  break;            
	case 'I':
	  // inches
	  return $M * 63360;
	  break;            
	case 'M':
	default:
	  // miles
	  return $M;
	  break;
  }
  
}    

//Mesma coisa que a funзгo acima (geoGetDistance), sу que calcula de outra forma
function distanciaPontosGPS($p1LA, $p1LO, $p2LA, $p2LO) {

	$r = 6371.0;

	$p1LA = $p1LA * pi() / 180.0;
	$p1LO = $p1LO * pi() / 180.0;
	$p2LA = $p2LA * pi() / 180.0;
	$p2LO = $p2LO * pi() / 180.0;
	
	$dLat = $p2LA - $p1LA;
	$dLong = $p2LO - $p1LO;

	$a = sin($dLat / 2) * sin($dLat / 2) + cos($p1LA) * cos($p2LA) * sin($dLong / 2) * sin($dLong / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

	return round($r * $c * 1000); // resultado em metros.
}

if(strlen($linha)>0){
	$sql = "SELECT  *
			FROM    tbl_linha
			WHERE   tbl_linha.fabrica = $login_fabrica
			AND     tbl_linha.linha   = $linha
			ORDER BY tbl_linha.nome;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$aux_linha = trim(pg_fetch_result($res,$x,linha));
		$aux_nome  = trim(pg_fetch_result($res,$x,nome));
		$info = "<br><b>Linha: </b>$aux_nome\n";
	}
	$sql_add = " AND tbl_posto.posto IN (
		SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto)
		WHERE linha = $linha AND  fabrica = $login_fabrica) ";
}

$cidade     = $_REQUEST['cidade'];
$estado     = $_REQUEST['estado'];
$consumidor = $_REQUEST['consumidor'];
$cep        = $_REQUEST['cep'];

if (strlen ($estado) > 0) {
		$cond_estado = "";
	if		 ($estado == "00")		{
		$cond_estado = "";
	} elseif ($estado == "BR-CO")		{
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MT','MS','GO','DF','TO')";
	} elseif ($estado == "BR-N")		{
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR')";
	} elseif ($estado == "BR-NE")		{
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','BA','PE')";
	} elseif ($estado == "SUL")			{
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('PR','SC','RS')";
	} elseif ($estado == "SP-capital")	{
		$cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND tbl_posto_fabrica.contato_cidade ILIKE 's_o paulo' ";
	} elseif ($estado == "SP-interior")	{
		$cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND TRIM(UPPER(tbl_posto_fabrica.contato_cidade)) NOT LIKE 'S_O PAULO'";
	} elseif ($estado == "BR-NEES")		{
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','ES','PE')";
	} elseif ($estado == "BR-NCO")		{
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR','MT','MS','GO','DF','TO')";
	}else{
		$cond_estado = "tbl_posto_fabrica.contato_estado = '$estado'";
	}
}

$cond_estado = ($cond_estado == '') ? $cond_estado : "AND  CASE WHEN tbl_posto.pais = 'BR' THEN $cond_estado ELSE FALSE END ";

if(strlen($cidade)>0){
	$xcidade1 = acentos1($cidade);
	$xcidade2 = acentos2($cidade);
	$xcidade3 = acentos3($cidade);
	$info .= "&nbsp;&nbsp;<b>Cidade:</b> $cidade";
	$cond_cidade = "tbl_posto_fabrica.contato_cidade ~* '$xcidade3' ";
}

if ($consumidor != '') {
	$endereco_consumidor = geoGetCoords($consumidor);
	$consumidor_lng = $endereco_consumidor['lon'];   
	$consumidor_lat = $endereco_consumidor['lat'];   /*  Para coincidir com o banco! */
	$coordenadas['latitude']    = $consumidor_lat;
	$coordenadas['longitude']   = $consumidor_lng;
	$max_distance = 0.5;    // Rбdio de busca em Graus
}
if ($consumidor and ($consumidor_lng=='' and $consumidor_lat =='')) $msg_erro ="Endereзo do consumidor nгo localizado no mapa!";
if(empty($consumidor)) {
	$msg_erro = "Por favor, informe os dados de consumidor";
}

if(empty($msg_erro)) {
	while ($qtde_postos_proximos < 5 and $max_distance < 2.6) {
		unset($sql_dist);
		if (strlen(trim($consumidor_lat))>0) {
			$sql_dist   = ", point($consumidor_lat, $consumidor_lng) <-> point(longitude,latitude) AS distancia\n";
			$sql_coords = "point(longitude,latitude) <@ circle'(($consumidor_lat, $consumidor_lng),$max_distance)' ";
			$ordem_por_distancia = "point($consumidor_lat, $consumidor_lng) <-> point(longitude,latitude),";/*  Para coincidir com o banco! */
		}

		if ($cond_cidade != '' || $sql_coords != '') {
			if ($sql_coords != '' and $cond_cidade != '') {
				$cond_lugar = "($sql_coords OR $cond_cidade)";
			} else {
				$cond_lugar = $sql_coords.$cond_cidade;
			}
		}

		$sql = "SELECT TRIM (tbl_posto.nome) AS nome,
					   tbl_posto.latitude,
					   tbl_posto.longitude,
					   tbl_posto.posto,
					   tbl_posto_fabrica.codigo_posto,
					   contato_endereco AS endereco,
					 contato_numero   AS numero  ,
					 contato_bairro   AS bairro  ,
					 contato_cidade   AS cidade  ,
					 contato_estado   AS estado  ,
					 contato_cep      AS cep
					   $sql_dist
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE  tbl_posto_fabrica.contato_pais='BR'
				AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO' 
				 $sql_add
				 $cond_estado
				AND  $cond_lugar
				ORDER BY ";
		$sql.= ($sql_dist)?"distancia, ":'';
		$sql.= "tbl_posto_fabrica.contato_pais, tbl_posto_fabrica.contato_estado,
				tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_cep";
		$sql.= ($sql_dist) ? " LIMIT 5":'';
		$resPosto = pg_query($con,$sql);
		$qtde_postos_proximos = @pg_num_rows($resPosto);
		$max_distance += 0.5;
	}
	if (pg_num_rows($resPosto)) {
		$tem_mapa = 1;
		$a_lats[] = $latitude;
		$a_lngs[] = $longitude;

		for ($i = 0 ; $i < pg_num_rows($resPosto); $i++){
			$posto			= pg_fetch_result ($resPosto, $i, posto);
			$codigo_posto	= pg_fetch_result ($resPosto, $i, codigo_posto);
			$nome			= pg_fetch_result ($resPosto, $i, nome);
			$latitude		= pg_fetch_result ($resPosto, $i, latitude);
			$longitude		= pg_fetch_result ($resPosto, $i, longitude);
			
			if ($latitude && $longitude) {
				$metros = geoGetDistance($endereco_consumidor['lat'],  $endereco_consumidor['lon'], $longitude, $latitude, 'K');
				$distancias_postos[$posto] = array('distancia' => $metros);
			}
		}
		asort($distancias_postos);

		$postos_mais_proximos = array_keys($distancias_postos);

		for ($i = 0 ; $i < pg_num_rows($resPosto); $i++){
			$posto			= pg_fetch_result ($resPosto, $i, posto);
			$codigo_posto	= pg_fetch_result ($resPosto, $i, codigo_posto);
			$nome			= pg_fetch_result ($resPosto, $i, nome);
			$latitude		= pg_fetch_result ($resPosto, $i, latitude);
			$longitude		= pg_fetch_result ($resPosto, $i, longitude);
			$endereco_posto = pg_fetch_result($resPosto,$i,endereco).', '.pg_fetch_result($resPosto,$i,numero).' '.pg_fetch_result($resPosto,$i,bairro).' '.pg_fetch_result($resPosto,$i,cidade).' '.pg_fetch_result($resPosto,$i,estado);
			$cep_posto = pg_fetch_result($resPosto,$i,cep);
			if ($latitude && $longitude) {
				$metros = geoGetDistance($endereco_consumidor['lat'],  $endereco_consumidor['lon'], $longitude, $latitude, 'K');
			}
			if($posto == $postos_mais_proximos[0]) {
				echo "OK;$codigo_posto;$nome;$cep_posto;$endereco_posto";
				exit;
			}
		}
	}
	
}else{
	$msg_erro = "Posto nгo encontrado, por favor, pesquisa pelo botгo Mapa";
}

echo  "Erro;",$msg_erro;
exit;

?>

