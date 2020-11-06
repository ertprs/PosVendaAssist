<?php

include "../dbconfig.php";
include "../dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

use Posvenda\TcMaps;
$oTCMaps = new TcMaps($login_fabrica,$con);

//HD 7277 Paulo - tirar acento do arquivo upload
function acentos1( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" );
	return str_replace( $array1, $array2, $texto );
}

function acentos2( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	return str_replace( $array1, $array2, $texto );
}

function acentos3( $texto ){
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
	return str_replace( $array1, $array2, $texto );
}

function pre_echo($s, $h='') {
	if ($h) echo "<h3>$h</h3>\n";
	echo "<pre>";
	print_r($s);
	echo "</pre>";
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
					
			$_url = sprintf('http://%s/MapsService/V1/geocode?appid=%s&location=%s',$lookup_server[$lookup_service],$key_api,rawurlencode($address));

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

//Mesma coisa que a função acima (geoGetDistance), só que calcula de outra forma
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

if($ajax=='grava'){
	$sql = "SELECT posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica = $login_fabrica
			AND   posto   = $posto";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		str_replace("'","",$xdata_abertura);
		$ponto = str_replace("(","",$ponto);
		$ponto = str_replace(")","",$ponto);
		$pontos = explode(",", $ponto);
		$latitude  = $pontos[0];
		$longitude = $pontos[1];
		$sql = "UPDATE tbl_posto SET
					latitude  = '$longitude',
					longitude = '$latitude'
				WHERE posto = $posto";
		$res = pg_exec($con,$sql);
		echo "ok|lat $latitude long $longitude";
	}else echo "NO|";
	exit;
}
?>

<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
<title>Telecontrol - Mapa da Rede Autorizada</title>
</head>

<body>
<!-- <body onload='load()' onunload='GUnload()'> -->
<?php

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

$tem_mapa = 0 ;
$qtde_de_postos = 5;    // Quantidade de postos próximos a retornar
if ($login_fabrica == 52) $mostrar_mais_proximo = true;

// $cidade = $_POST['cidade'];
// $estado = $_POST['estado'];
// $pais   = $_POST['pais'];
// $cep    = $_POST['cep'];
// $linha  = $_POST['linha'];
// 
// if(strlen($_GET['cidade'])>0)       $cidade     = $_GET['cidade'];
// if(strlen($_GET['estado'])>0)       $estado     = $_GET['estado'];
// if(strlen($_GET['pais'])>0)         $pais       = $_GET['pais'];
// if(strlen($_GET['consumidor'])>0)   $consumidor = $_GET['consumidor'];
// if(strlen($_GET['linha'])>0)        $linha      = $_GET['linha'];
//echo "($pais) [$estado] $cidade";
//$cidade = utf8_decode($cidade);
$cidade     = anti_injection($_REQUEST['cidade']);
$estado     = anti_injection($_REQUEST['estado']);
$pais       = anti_injection($_REQUEST['pais']);
$cep        = anti_injection($_REQUEST['cep']);
$linha      = anti_injection($_REQUEST['linha']);
$consumidor = anti_injection($_REQUEST['consumidor']);
$callcenter = anti_injection($_REQUEST['callcenter']);
$estado     = ((!$estado or $estado == '00') and $consumidor) ? substr($consumidor, -2) : $estado;

$cond_cadence = ($login_fabrica == 35 ) ? " AND tipo_posto <> 163 " : "";

if (strlen ($estado) > 0) {

	$cond_estado = "";

	if ($estado == "00") {
		$cond_estado = "";
	} else if ($estado == "BR-CO") {
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MT','MS','GO','DF','TO')";
	} else if ($estado == "BR-N") {
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR')";
	} else if ($estado == "BR-NE") {
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','BA','PE')";
	} else if ($estado == "SUL") {
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('PR','SC','RS')";
	} else if ($estado == "SP-capital") {
		$cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND tbl_posto_fabrica.contato_cidade ILIKE 's_o paulo' ";
	} else if ($estado == "SP-interior") {
		$cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND TRIM(UPPER(tbl_posto_fabrica.contato_cidade)) NOT LIKE 'S_O PAULO'";
	} else if ($estado == "BR-NEES") {
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','ES','PE')";
	} else if ($estado == "BR-NCO") {
		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR','MT','MS','GO','DF','TO')";
	} else {
		$cond_estado = "tbl_posto_fabrica.contato_estado = '$estado'";
	}

}

$cond_estado = ($cond_estado == '') ? $cond_estado : "AND  CASE WHEN tbl_posto.pais = 'BR' THEN $cond_estado ELSE FALSE END ";
if ($pais) $cond_pais = " tbl_posto_fabrica.contato_pais = '$pais' ";

if (strlen($linha) > 0) {
	$sql = "SELECT *
			FROM   tbl_linha
			WHERE   tbl_linha.fabrica = $login_fabrica
			AND     tbl_linha.linha   = $linha
			ORDER BY tbl_linha.nome;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$aux_linha = trim(pg_result($res,$x,linha));
		$aux_nome  = trim(pg_result($res,$x,nome));
		$info = "<br><b>Linha: </b>$aux_nome\n";
	}

	$sql_add = " AND tbl_posto.posto IN (
		SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto)
		WHERE linha = $linha AND  fabrica = $login_fabrica) ";

}

if (strlen($cidade) > 0) {
	$xcidade1 = acentos1($cidade);
	$xcidade2 = acentos2($cidade);
	$xcidade3 = acentos3($cidade);
	#Paulo tirou, não sei para que esse sql_add, ja que tem $cond_cidade que faz mesma coisa
	#$sql_add .= " AND (
	#					UPPER(tbl_posto_fabrica.contato_cidade) LIKE upper('%$xcidade1%')
	#					OR UPPER(tbl_posto_fabrica.contato_cidade) LIKE upper('%$xcidade2%')
	#					OR UPPER(tbl_posto_fabrica.contato_cidade) LIKE upper('%$xcidade3%')
	#					)";
	$info .= "&nbsp;&nbsp;<b>Cidade:</b> $cidade";
	$cond_cidade = "tbl_posto_fabrica.contato_cidade ~* '$xcidade3' ";
}

//  Procura os postos mais próximos
if ($consumidor != '') {
	$vetConsumidor = explode(",", $consumidor);

	$endereco_consumidor = $oTCMaps->geocode($vetConsumidor[0], $vetConsumidor[1], $vetConsumidor[2], $vetConsumidor[3], $vetConsumidor[4], "Brasil");

	$consumidor_lng = $endereco_consumidor['longitude'];   
	$consumidor_lat = $endereco_consumidor['latitude'];
	$consumidor_latlng = $endereco_consumidor['latlon'];
	$coordenadas['latitude']    = $consumidor_lat;
	$coordenadas['longitude']   = $consumidor_lng;
	$max_distance = 0.5;    // Rádio de busca em Graus
}
	while ($qtde_postos_proximos < 5 and $max_distance < 2.6) { //  Procura até achar 5 postos... até 5° ao redor
	    unset($sql_dist);
		/*if (strlen(trim($consumidor_lat))>0) {
		    $sql_dist   = ", point($consumidor_lat, $consumidor_lng) <-> point(longitude,latitude) AS distancia\n";
			$sql_coords = "point(longitude,latitude) <@ circle'(($consumidor_lat, $consumidor_lng),$max_distance)' ";
			$ordem_por_distancia = "point($consumidor_lat, $consumidor_lng) <-> point(longitude,latitude),";/*  Para coincidir com o banco! */
		//}
		/*
		if ($cond_cidade != '' || $sql_coords != '') {
			if ($sql_coords != '' and $cond_cidade != '') {
				$cond_lugar = "($sql_coords OR $cond_cidade)";
			} else {
			 $cond_lugar = $sql_coords.$cond_cidade;
			}
		}*/
		//$cond_lugar.= ($cond_lugar != '') ? " AND $cond_pais":$cond_pais;
 		//$cond_lugar.= ($cond_lugar != '') ? "\n\t\t\tAND ":'';

 		if (!empty($consumidor_lat) and !empty($consumidor_lng)) {
            $campo = ",  (111.045 * DEGREES(ACOS(COS(RADIANS($consumidor_lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($consumidor_lng)) + SIN(RADIANS($consumidor_lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance ";
            $order = " distance limit 5 ";
        } else {
            $cond = " AND tbl_posto_fabrica.contato_estado = '$estado'
                AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade') ";
            $order = " nome ";
        }

		$sql = "SELECT tbl_posto.posto,
					   TRIM (tbl_posto.nome) AS nome,
					   TRIM (tbl_posto_fabrica.nome_fantasia) AS nome_fantasia,
					   TRIM (tbl_posto_fabrica.contato_endereco) AS endereco,
					   tbl_posto_fabrica.contato_numero AS numero,
					   tbl_posto_fabrica.contato_fone_comercial,
					   tbl_posto_fabrica.contato_cidade AS cidade,
					   tbl_posto_fabrica.contato_bairro AS bairro,
					   tbl_posto_fabrica.contato_cep AS cep,
					   tbl_posto_fabrica.contato_estado AS estado,
					   tbl_posto_fabrica.latitude,
					   tbl_posto_fabrica.longitude,
					   tbl_posto_fabrica.codigo_posto,
					   tbl_posto_fabrica.contato_email as email,
					   tbl_posto.fone
					   $campo
		FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		        WHERE  $cond_lugar\n";
		if($login_fabrica <> 43){
			//$sql .= ($cond_lugar)?' AND ':'';
			$sql.= "tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
		}
		$sql .= " $sql_add
		        $cond_estado
				$cond_cadence
				$cond
		/*		AND tbl_posto.posto <> 6359*/
				ORDER BY $order";
		//$sql.= ($sql_dist)?"distancia, ":'';
		//$sql.= "tbl_posto_fabrica.contato_pais, tbl_posto_fabrica.contato_estado,
				//tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_cep";
		//$sql.= ($sql_dist) ? "\n		LIMIT $qtde_de_postos":'';
 		//if ($ip=="201.76.76.130") {
 			//var_dump(utf8_decode($cidade));
 			//echo nl2br($sql);
 		//}

		$resPosto = pg_query($con,$sql);
		$qtde_postos_proximos = @pg_num_rows($resPosto);
		$max_distance += 0.5;
	}

if ($consumidor and ($consumidor_lng=='' and $consumidor_lat =='')) echo "<p>Endereço do consumidor não localizado no mapa!</p>\n";

$a_lats = array();
$a_lngs = array();

if ($qtde_postos_proximos) {
	$tem_mapa = 1;
	$a_lats[] = $latitude;
	$a_lngs[] = $longitude;

	if ($mostrar_mais_proximo) {
		//Calcula a distância em Km dos 5 caminhos mais proximos
		for ($i = 0 ; $i < $qtde_postos_proximos; $i++){
			$posto		= pg_result ($resPosto, $i, posto);
			$latitude	= pg_result ($resPosto, $i, latitude);	//Esta gravando invertido!!
			$longitude	= pg_result ($resPosto, $i, longitude);	//Esta gravando invertido!!

			if ($latitude && $longitude) {
				//$metros1= distanciaPontosGPS($coordenadas['latitude'],$coordenadas['longitude'],$longitude,$latitude);
				$metros = geoGetDistance($endereco_consumidor['latitude'],  $endereco_consumidor['longitude'], $longitude, $latitude, 'K');
				$distancias_postos[$posto] = array('distancia' => $metros);
			}
		}
		asort($distancias_postos); //   Ordena por distância, o mais próximo agora é o primeiro índice
      //Determinando as coordenadas máx. e mín. para mostrar todos os pontos do mapa...

		if ($postos_mais_proximos){
			$info .= "&nbsp;&nbsp;<b>$qtde_de_postos postos mais próximos:</b>";
		}

		$postos_mais_proximos = array_keys($distancias_postos);

		if ($qtde_postos_proximos) {
		    $pl = ($qtde_postos_proximos == 1) ? '':'s';
			$info .= "&nbsp;&nbsp;<b>O$pl $qtde_postos_proximos posto$pl mais próximo$pl:</b>";
		}
	}?>
<center>
	<p style='font-weight:bold'>Clique sobre as marcas para ver informações detalhadas do posto</p>
	<p>
		+) Podem haver postos que não apareçam no mapa, por estarem com o endereço incorreto<br>
		+) A localização dos postos não é exata, podendo haver margem de erro
	</p><?php
}

/*  Zoom do mapa: 0: mapamundi, 20: aprox. máx. */
$map_zoom = 12;
if (!$consumidor) $map_zoom =  5;
if ($estado)	  $map_zoom =  8;
if ($cidade)	  $map_zoom = 10;


if (!empty($consumidor)) {
	$vet =  explode(",", $consumidor);
	$enderecoConsumidor = $vet[0];
	$numeroConsumidor  	= $vet[1];
	$bairroConsumidor  	= $vet[2];
	$cidadeConsumidor  	= $vet[3];
	$estadoConsumidor  	= $vet[4];
	$paisConsumidor  	= $vet[5];
}
//include '../gMapsKeys.inc';

// if ($max_distance) $map_zoom = $map_zoom - ($max_distance * 2);
?>
<!-- <script src="http://maps.google.com/maps?file=api&v=2&key=<?//=$gAPI_key?>" type="text/javascript"></script>
<script type='text/javascript' src='js/jquery-1.3.2.js'></script> -->
<script src="../js/jquery-1.6.2.js"></script>

<link href="plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
<script src="plugins/leaflet/leaflet.js" ></script>		
<script src="plugins/leaflet/map.js" ></script>
<script src="plugins/mapbox/geocoder.js"></script>
<script src="plugins/mapbox/polyline.js"></script>

<script type="text/javascript">
$().ready(function(){
	$(".btn_consulta_cidades_atendidas").each(function(){
		$(this).click(function(){
			var codigo = $(this).attr("id_posto");
			if(codigo != ""){
				var URL = "cidades_atendidas_cliente.php?codigo="+ codigo +"&nome="+ $(this).attr("id_nome");
				window.open(URL,  "janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=200,top=18,left=0" );
			}else{
				alert("Código do posto inválido");
			}
		});
						
	})

});
	/* - - - - - - - - - - -  MAPBOX - - - - - - - - - - - - - - - */
	var geocoder, c_latlon, c_lat, c_lon, p_latlon, p_lat, p_lon;
	var dados_posto = "";
	var inicio = true;
	var callcenter         = '<?=$callcenter?>';
	var linha              = '<?=$linha;?>';
	var enderecoConsumidor = "<?=$enderecoConsumidor;?>";
	var numeroConsumidor   = "<?=$numeroConsumidor;?>";
	var bairroConsumidor   = '<?=$bairroConsumidor;?>';
	var cidadeConsumidor   = "<?=$cidadeConsumidor;?>";
	var estadoConsumidor   = '<?=$estadoConsumidor;?>';
	var paisConsumidor     = '<?=$paisConsumidor;?>';
	var cep_consumidor     = "<?=$_GET['cep']?>";

	var Map, Markers, Route, Geocoder;



	function geocodeLatLon(inicio,dados_posto) {

	    if (typeof Map !== "object") {
			Map      = new Map("Gmapa");
			Markers  = new Markers(Map);
			Router   = new Router(Map);
			Geocoder = new Geocoder();
		}

		try {
			Geocoder.setEndereco({
				endereco: enderecoConsumidor,
				numero: numeroConsumidor,
				bairro: bairroConsumidor,
				cidade: cidadeConsumidor,
				estado: estadoConsumidor,
				pais: "Brasil"
			});

			request = Geocoder.getLatLon();

			request.then(
				function(resposta) {
					c_lat  = resposta.latitude;
					c_lon  = resposta.longitude;
					c_latlon = c_lat+", "+c_lon;

					Map.load();

					Markers.clear();
					Markers.remove();

					if (inicio == false) {
						parte  = p_latlon.split(",");
						p_lat  = parte[0];
						p_lon  = parte[1];
						
						$.ajax({
							url: 'controllers/TcMaps.php',
							type: 'post',
							data: {ajax : "route", origem: c_latlon, destino: p_latlon, ida_volta: "não"},
							success: function(data){
								rotas = JSON.parse(data);
								var dadosPosto = dados_posto.split("|");
								var nome_posto = dadosPosto[0]+"<br /> Endereço: "+dadosPosto[1]+", "+dadosPosto[2]+ " <br /> CEP: "+ dadosPosto[6]+ " - Cidade: "+dadosPosto[4]+"/"+dadosPosto[5]+" <br > Fone: "+dadosPosto[3];   ;
								
								if (data.length > 0 ) {

									Markers.add(p_lat, p_lon, "red", nome_posto);
									Markers.add(c_lat, c_lon, "blue", "Cliente");
									Markers.render();

									Router.remove();
									Router.clear();
									Router.add(Polyline.decode(rotas["rota"]["routes"][0]["geometry"]));
									Router.render();
								} else {
									alert("Nenhum Posto localizado com o Endereço/CEP Informado");
								}
							}
						});
					} else {
						$("tbody").find("tr.posto").each(function() {
							var lat        = $(this).data("lat");
							var lng        = $(this).data("lon");
							var posto       = $(this).data("id");

							var dadosPosto = $(this).data("nome").split("|");

							var nome_posto = dadosPosto[0]+"<br /> Endereço: "+dadosPosto[1]+", "+dadosPosto[2]+ " <br /> CEP: "+ dadosPosto[6]+ " - Cidade: "+dadosPosto[4]+"/"+dadosPosto[5]+" <br > Fone: "+dadosPosto[3];   ;
							if (lat.length != "" && lng.length != "" ) {
								Map.setView(lat, lng, 5);
								Markers.add(lat, lng, "red", nome_posto);
							}
						});
						Markers.render();
						Markers.focus();
					}

				},
				function(erro) {
					alert(erro);
					$('.tbody').html("<td colspan='10'><br /><h1 align='center'>Erro ao buscar Posto Autorizado</h1><br /></td>");
				}
			);
		} catch(e) {
			alert(e.message);
			$('.tbody').html("<td colspan='10'><br /><h1 align='center'>Erro ao buscar Posto Autorizado</h1><br /></td>");
		}
	}


	var bounds, map, markers_map;

	var markers = [];
	var rotas   = [];


	function loadMaps () {
		<?php
			//  Config. para a tabela
			$colspan    = 8;
			if ($login_fabrica == 52) {
				$fantasiaTH = '<th>Nome Fantasia</th>';
				$fantasiaTD = 'nome_fantasia';
				$colspan = 11;
			}
			$razaoText	= ($login_fabrica == 59) ? 'Nome Fantasia' : 'Nome do Posto';
			$razao		= ($login_fabrica == 59) ? 'nome_fantasia' : 'nome';

			if ($tem_mapa == "1") {
				for ($i = 0; $i < $qtde_postos_proximos; $i++) {
					$posto			= pg_result($resPosto, $i, 'posto');
					$codigo_posto	= pg_result($resPosto, $i, 'codigo_posto');
					$nome			= pg_result($resPosto, $i, 'nome');
					$nome_fantasia	= pg_result($resPosto, $i, 'nome_fantasia');
					$email			= strtolower(pg_result($resPosto, $i, 'email'));
					$endereco		= pg_result($resPosto, $i, 'endereco');
					$numero			= pg_result($resPosto, $i, 'numero');
					$fone			= pg_result($resPosto, $i, 'contato_fone_comercial');
					$fone			= ($fone) ? $fone : pg_result($resPosto, $i, 'fone'); // Fone da tbl_posto se não tiver na tbl_posto_fabrica
					$cidade			= pg_result($resPosto, $i, 'cidade');
					$bairro			= pg_result($resPosto, $i, 'bairro');
					$estado			= pg_result($resPosto, $i, 'estado');
					$cep			= pg_result($resPosto, $i, 'cep');
					$latitude		= pg_result($resPosto, $i, 'latitude');
					$longitude		= pg_result($resPosto, $i, 'longitude');

					$a_lats[] = $latitude;
					$a_lngs[] = $longitude;

					//Se o posto for o primeiro do array, então este é o posto mais proximo
					if ($i == 0) {
						$mais_proximo['latitude']  = $latitude;
						$mais_proximo['longitude'] = $longitude;
					}

					$clausula = "posto = $posto AND fabrica = $login_fabrica";
					$sql = "SELECT * FROM tbl_empresa_cliente    WHERE $clausula";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					$sql = "SELECT * FROM tbl_empresa_fornecedor WHERE $clausula";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					$sql = "SELECT * FROM  tbl_erp_login         WHERE $clausula";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					// echo "/* posto: $posto - Lat: $latitude, Long: $longitude (Distância: $distancia) */\n";
					$nome     = str_replace ("\"","",$nome);
					$nome     = str_replace ("'","",$nome);
					$endereco = str_replace ("\"","",$endereco);
					$endereco = str_replace ("'","",$endereco);
					$cidade   = str_replace ("\"","",$cidade);
					$cidade   = str_replace ("'","",$cidade);
					$bairro   = str_replace ("\"","",$bairro);
					$bairro   = str_replace ("'","",$bairro);
					$cep      = preg_replace('/\D/',"",$cep);
					$cep      = str_replace ("'","",$cep);
					$fone     = str_replace ("(","",$fone);
					$fone     = str_replace (")","",$fone);
					$email    = str_replace ("(","",$email);
					$email    = str_replace (")","",$email);
					$latlng   = "$latitude,$longitude";

					$cep = preg_replace('/(\d{2})(\d{3})(\d{3})/','$1.$2-$3',$cep); //substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

					//  Formata o nº de telefone
					$num_fone = preg_replace('/\D/','',$fone);
					$len_fone = strlen($num_fone);
					if ($len_fone == 10 or ($len_fone == 11 and $num_fone[0]=='0')) {
						$telMask= '/0?(\d{2})(\d{4})(\d{4})/';
						$telFmt = '(0$1) $2-$3';
					}
					if ($len_fone == 8) {
						$telMask= '/(\d{4})(\d{4})/';
						$telFmt = '(xx) $1-$2';
					}
					if ($len_fone > 11) {   // Fone internacional ¿?
						$telMask= '/(\d{2})0?(\d{1,2})(\d{4})(\d{4})/';
						$telFmt = '+$1 ($2) $3-$4';
					}
					$fone = (($len_fone <10 and $len_fone <> 8) or $len_fone > 15) ? $fone : preg_replace($telMask, $telFmt, $num_fone);

					$totalKMIda   = $oTCMaps->route($latitude.",".$longitude, $consumidor_latlng);
					$totalKMVolta = $oTCMaps->route($consumidor_latlng, $latitude.",".$longitude);
					//  Linha da tabela
					$cor= ($i % 2 == 0) ? '#ffffff' : '#eeeeff';
					if ($login_fabrica == 3)
						$link_posto  =  "posto_tab.value= '$posto';".
										"codigo_posto_tab.value='$codigo_posto';".
										"posto_nome_tab.value='$nome';".
										"posto_nome_fantasia.value='$nome_fantasia';".
										"posto_endereco.value='$endereco,$numero';".
										"fone_posto.value='$fone';".
										"posto_cidade.value='$cidade';".
										"posto_estado.value='$estado';".
										"posto_cep.value='$cep';".
										"this.close();\"";
					if ($login_fabrica == 52)
						$link_posto  =  "posto_km_tab.value = '".($totalKMIda['total_km']+$totalKMVolta['total_km'])."';".
										"posto_email_tab.value='$email';".
										"posto_fone_tab.value='$fone';";
					if ($login_fabrica != 3 and isset($callcenter))
						$link_posto  =  "posto_tab.value= '$posto';".
										"codigo_posto_tab.value='$codigo_posto';".
										"posto_nome_tab.value='$nome';".
										"posto_km_tab.value = '".($totalKMIda['total_km']+$totalKMVolta['total_km'])."';".
										"window.close();\"";
					$link_posto = ($login_fabrica==3 or isset($callcenter)) ? '<a href="javascript:'.$link_posto.">".$$razao.'</a>' : $$razao;
					$dadosPostoCidade = trim("$cidade");
					$dadosPosto = trim("$nome|$endereco|$numero|$fone|$dadosPostoCidade|$estado|$cep");
					$enderecoPosto = trim("$endereco|$numero|$bairro|$dadosPostoCidade|$estado");

					$tbl_postos.= "
					<tr data-id='$posto' data-lat='$latitude' data-lon='$longitude' data-nome='$dadosPosto' class='posto' bgcolor='$cor' style='border:1px #77aadd solid;height:22px; font-size: 10px'>
					    <td>$link_posto</td>\n";
					$tbl_postos.= ($login_fabrica == 52) ? "\t\t\t\t\t\t<td> $nome_fantasia</td>\n" : '';
					$tbl_postos.= "\t\t\t\t\t\t<td>$endereco, $numero</td>
						<td nowrap>$bairro</td>
						<td nowrap>$cidade</td>
						<td nowrap align='center'>$estado</td>
						<td nowrap align='right'>$cep</td>
						<td nowrap align='right'>$email</td>
						<td nowrap align='right'>$fone</td>
						<td nowrap align='center' class='total_km_ida_volta_{$i}'>".($totalKMIda['total_km']+$totalKMVolta['total_km'])." km</td>";
						if(($login_cliente_admin == 3890) || ($login_cliente_admin == 3791) || ($login_cliente_admin == 3790)){
							$tbl_postos.= "<td nowrap align='right'><a id_posto='{$codigo_posto}' id_nome='{$nome}' class='btn_consulta_cidades_atendidas' id='btn_consulta_cidades_atendidas' href='javascript:void(0);'> Cidades atendidas</a></td>";
						}
						
						
					if (strlen ($latitude) > 0 and strlen ($longitude) > 0) {
					    $acao = 'mapa';
					    $link_acao = "showMapa(\"$dadosPosto\",\"$latlng\")";
					} else {
					    $acao = 'localizar';
					    $link_acao = "showLocalizar(\"$enderecoPosto\",\"$posto\",this);";
					}
					$tbl_postos .= "<td>
							<input id='address_$i' type='hidden' value = '$endereco,$numero,$cidade,br'>
							<a href='#mapa_inicio' onclick='javascript: $link_acao'>$acao</a>
						</td>
					</tr>\n";

					/*//  Pontos no mapa
					if ($latitude and $longitude) {
						if ($centro_mapa == 0) {
							//echo "map.setCenter(new GLatLng($latitude,$longitude),mapZoom);\n\n";
							$centro_mapa = 1;
						}

						//echo "var point_$posto = new GLatLng($latitude,$longitude); \n";
						//echo "var posto_$posto = new GMarker(point_$posto); \n";
						//echo "map.addOverlay(posto_$posto); \n";
						//echo "GEvent.addListener (posto_$posto, \"click\", function(){	\n";
						//echo "posto_$posto.openInfoWindowHtml('<FONT SIZE=\"-1\"><b>$nome</b> <br> $endereco, $numero <br> fone: $fone  <br> $cidade - $estado - $cep </FONT>'); \n";
						//echo "}); \n";
						//echo "\n\n";
					}*/
				}
                /*
				$a_lats = array_filter($a_lats);
				$a_lngs = array_filter($a_lngs);

		        //echo "map.setCenter (new GLatLng(".(min($a_lats) + max($a_lats))/2 .','.(min($a_lngs) + max($a_lngs))/2 ."));";

				if (is_array($mais_proximo) && count($mais_proximo)>0){
					//echo "setDirections('$consumidor','{$mais_proximo['latitude']},{$mais_proximo['longitude']}','pt-br');";
				}else{
					if (strlen ($latitude) > 0 AND strlen ($longitude) > 0 and strlen($consumidor) > 0) {
						//echo "setDirections('$consumidor','{$mais_proximo['latitude']},{$mais_proximo['longitude']}','pt-br');";
					}
				}*/
			} else {
			    echo "showMapa(-15.815279,-48.070252,3);";
			}
		?>
		geocodeLatLon();

	}



	function showMapa(dados_posto, latlon_posto) {
		p_latlon = latlon_posto;
		geocodeLatLon(false,dados_posto);
	}

	/* Localizar */
	function showLocalizar(endereco_posto, dados_posto, id) {

		var vet = endereco_posto.split("|");

		if (endereco_posto.length > 0) {

			$.ajax({
				url: 'controllers/TcMaps.php',
				type: 'post',
				data: {
						ajax : "geocode", 
						endereco: vet[0], 
						numero: vet[1], 
						bairro: vet[2], 
						cidade: vet[3], 
						estado: vet[4], 
						pais: "Brasil"
					},
				success: function(data){
					var geo = JSON.parse(data);
					var dadosPosto = dados_posto.split("|");
					var nome_posto = dadosPosto[0]+"<br /> Endereço: "+dadosPosto[1]+", "+dadosPosto[2]+ " <br /> CEP: "+ dadosPosto[6]+ " - Cidade: "+dadosPosto[4]+"/"+dadosPosto[5]+" <br > Fone: "+dadosPosto[3];   ;
					
					if (data.length > 0 ) {
						Map.setView(geo.latitude, geo.longitude, 5);
					} else {
						alert("Nenhum Posto localizado com o Endereço/CEP Informado");
					}
				}
			});
		} else {
			alert("Nenhum Posto localizado com o Endereço/CEP Informado");
		}
	}

	$(function() {
		loadMaps();
	});



	//var map;
	//var end_consumidor_lat	= <?/*echo floatval($consumidor_lat)?>;
	//var end_consumidor_lng	= <?echo floatval($consumidor_lng)?>;
	//var MostrarCaminho		= <? echo ($mostrar_mais_proximo) ? 'true' : 'false'; ?>;
	//var mapZoom = <?=$map_zoom?>;

	function load() {
		if (GBrowserIsCompatible()) {
			map = new GMap2(document.getElementById("Gmapa"));
			map.addControl(new GMapTypeControl());
			map.addControl(new GLargeMapControl());

			//  Ícone para o local do consumidor
			if (end_consumidor_lat != 0 && end_consumidor_lng != 0) {
			    var iconeConsumidor		= new GIcon(G_DEFAULT_ICON);
			    iconeConsumidor.image	= 'http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=home|FF0000|000000';
			    iconeConsumidor.shadow	= 'http://chart.apis.google.com/chart?chst=d_map_pin_shadow';

				var coordsConsumidor = new GLatLng(end_consumidor_lat, end_consumidor_lng);
				map.addOverlay(new GMarker(coordsConsumidor, {icon:iconeConsumidor}));
			}

			gdir = new GDirections(map, document.getElementById("directions"));
			var pt1 = '17504380';
			var pt2 =  '17505324';
			gdir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});
			GEvent.addListener(gdir,"load", function() {
				for (var i=0; i<gdir.getNumRoutes(); i++) {
						var route = gdir.getRoute(i);
						var dist = route.getDistance();
						var x = dist.meters*2/1000;
						var y = x.toString().replace(".",",");
						var valor_calculado = parseFloat(x);
				 }

				 document.getElementById('km').value = ((Math.round(x*100))/100);
			});
			GEvent.addListener(gdir, "addoverlay", onGDirectionsAddOverlay);

			map.setCenter(new GLatLng(end_consumidor_lat,end_consumidor_lng),mapZoom);	// inital setCenter()  added by Esa.
			<?php
			$centro_mapa = ($consumidor_lat !=0 and $consumidor_lng != 0);

			//  Config. para a tabela
			$colspan    = 8;
			if ($login_fabrica == 52) {
				$fantasiaTH = '<th>Nome Fantasia</th>';
				$fantasiaTD = 'nome_fantasia';
				$colspan = 9;
			}
			$razaoText	= ($login_fabrica == 59) ? 'Nome Fantasia' : 'Nome do Posto';
			$razao		= ($login_fabrica == 59) ? 'nome_fantasia' : 'nome';

			if ($tem_mapa == "1") {
				for ($i = 0; $i < $qtde_postos_proximos; $i++) {
					$posto			= pg_result($resPosto, $i, 'posto');
					$codigo_posto	= pg_result($resPosto, $i, 'codigo_posto');
					$nome			= pg_result($resPosto, $i, 'nome');
					$nome_fantasia	= pg_result($resPosto, $i, 'nome_fantasia');
					$email			= strtolower(pg_result($resPosto, $i, 'email'));
					$endereco		= pg_result($resPosto, $i, 'endereco');
					$numero			= pg_result($resPosto, $i, 'numero');
					$fone			= pg_result($resPosto, $i, 'contato_fone_comercial');
					$fone			= ($fone) ? $fone : pg_result($resPosto, $i, 'fone'); // Fone da tbl_posto se não tiver na tbl_posto_fabrica
					$cidade			= pg_result($resPosto, $i, 'cidade');
					$bairro			= pg_result($resPosto, $i, 'bairro');
					$estado			= pg_result($resPosto, $i, 'estado');
					$cep			= pg_result($resPosto, $i, 'cep');
					$latitude		= pg_result($resPosto, $i, 'longitude');
					$longitude		= pg_result($resPosto, $i, 'latitude');

					$a_lats[] = $latitude;
					$a_lngs[] = $longitude;

					//Se o posto for o primeiro do array, então este é o posto mais proximo
					if ($i == 0) {
						$mais_proximo['latitude']  = $latitude;
						$mais_proximo['longitude'] = $longitude;
					}

					$clausula = "posto = $posto AND fabrica = $login_fabrica";
					$sql = "SELECT * FROM tbl_empresa_cliente    WHERE $clausula";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					$sql = "SELECT * FROM tbl_empresa_fornecedor WHERE $clausula";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					$sql = "SELECT * FROM  tbl_erp_login         WHERE $clausula";
					$res2 = pg_exec ($con,$sql);
					if (pg_numrows($res2) > 0) continue;

					// echo "/* posto: $posto - Lat: $latitude, Long: $longitude (Distância: $distancia) /\n";
					$nome     = str_replace ("\"","",$nome);
					$nome     = str_replace ("'","",$nome);
					$endereco = str_replace ("\"","",$endereco);
					$endereco = str_replace ("'","",$endereco);
					$cidade   = str_replace ("\"","",$cidade);
					$cidade   = str_replace ("'","",$cidade);
					$bairro   = str_replace ("\"","",$bairro);
					$bairro   = str_replace ("'","",$bairro);
					$cep      = preg_replace('/\D/',"",$cep);
					$cep      = str_replace ("'","",$cep);
					$fone     = str_replace ("(","",$fone);
					$fone     = str_replace (")","",$fone);
					$email    = str_replace ("(","",$email);
					$email    = str_replace (")","",$email);
					$latlng   = "$latitude,$longitude";

					$cep = preg_replace('/(\d{2})(\d{3})(\d{3})/','$1.$2-$3',$cep); //substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

					//  Formata o nº de telefone
					$num_fone = preg_replace('/\D/','',$fone);
					$len_fone = strlen($num_fone);
					if ($len_fone == 10 or ($len_fone == 11 and $num_fone[0]=='0')) {
						$telMask= '/0?(\d{2})(\d{4})(\d{4})/';
						$telFmt = '(0$1) $2-$3';
					}
					if ($len_fone == 8) {
						$telMask= '/(\d{4})(\d{4})/';
						$telFmt = '(xx) $1-$2';
					}
					if ($len_fone > 11) {   // Fone internacional ¿?
						$telMask= '/(\d{2})0?(\d{1,2})(\d{4})(\d{4})/';
						$telFmt = '+$1 ($2) $3-$4';
					}
					$fone = (($len_fone <10 and $len_fone <> 8) or $len_fone > 15) ? $fone : preg_replace($telMask, $telFmt, $num_fone);

					//  Linha da tabela
					$cor= ($i % 2 == 0) ? '#ffffff' : '#eeeeff';
					if ($login_fabrica == 3)
						$link_posto  =  "posto_tab.value= '$posto';".
										"codigo_posto_tab.value='$codigo_posto';".
										"posto_nome_tab.value='$nome';".
										"posto_nome_fantasia.value='$nome_fantasia';".
										"posto_endereco.value='$endereco,$numero';".
										"fone_posto.value='$fone';".
										"posto_cidade.value='$cidade';".
										"posto_estado.value='$estado';".
										"posto_cep.value='$cep';".
										"this.close();\"";
					if ($login_fabrica == 52)
						$link_posto  =  "posto_km_tab.value = document.getElementById('km').value;".
										"posto_email_tab.value='$email';".
										"posto_fone_tab.value='$fone';";
					if ($login_fabrica != 3 and isset($callcenter))
						$link_posto  =  "posto_tab.value= '$posto';".
										"codigo_posto_tab.value='$codigo_posto';".
										"posto_nome_tab.value='$nome';".
										"window.close();\"";
					$link_posto = ($login_fabrica==3 or isset($callcenter)) ? '<a href="javascript:'.$link_posto.">".$$razao.'</a>' : $$razao;
					$tbl_postos.= "
					<tr bgcolor='$cor' style='border:1px #77aadd solid;height:22px; font-size: 10px'>
					    <td>$link_posto</td>\n";
					$tbl_postos.= ($login_fabrica == 52) ? "\t\t\t\t\t\t<td> $nome_fantasia</td>\n" : '';
					$tbl_postos.= "\t\t\t\t\t\t<td>$endereco, $numero</td>
						<td nowrap>$bairro</td>
						<td nowrap>$cidade</td>
						<td nowrap align='center'>$estado</td>
						<td nowrap align='right'>$cep</td>
						<td nowrap align='right'>$email</td>
						<td nowrap align='right'>$fone</td>";
						if(($login_cliente_admin == 3890) || ($login_cliente_admin == 3791) || ($login_cliente_admin == 3790)){
							$tbl_postos.= "<td nowrap align='right'><a id_posto='{$codigo_posto}' id_nome='{$nome}' class='btn_consulta_cidades_atendidas' id='btn_consulta_cidades_atendidas' href='javascript:void(0);'> Cidades atendidas</a></td>";
						}
						
						
					if (strlen ($latitude) > 0 and strlen ($longitude) > 0) {
					    $acao = 'mapa';
					    $link_acao = "showMapa(\"$consumidor\",\"$latlng\",\"pt-br\")";
					} else {
					    $acao = 'localizar';
					    $link_acao = "showLocalizar(\"$endereco $cidade $cep\",\"$posto\",this);";
					}
					$tbl_postos .= "<td>
							<input id='address_$i' type='hidden' value = '$endereco,$numero,$cidade,br'>
							<a href='#mapa_inicio' onclick='javascript: $link_acao'>$acao</a>
						</td>
				</tr>\n";

					//  Pontos no mapa
					if ($latitude and $longitude) {
						if ($centro_mapa == 0) {
							echo "map.setCenter(new GLatLng($latitude,$longitude),mapZoom);\n\n";
							$centro_mapa = 1;
						}

						echo "var point_$posto = new GLatLng($latitude,$longitude); \n";
						echo "var posto_$posto = new GMarker(point_$posto); \n";
						echo "map.addOverlay(posto_$posto); \n";
						echo "GEvent.addListener (posto_$posto, \"click\", function(){	\n";
						echo "posto_$posto.openInfoWindowHtml('<FONT SIZE=\"-1\"><b>$nome</b> <br> $endereco, $numero <br> fone: $fone  <br> $cidade - $estado - $cep </FONT>'); \n";
						echo "}); \n";
						echo "\n\n";
					}
				}

				$a_lats = array_filter($a_lats);
				$a_lngs = array_filter($a_lngs);

		        echo "				map.setCenter (new GLatLng(".(min($a_lats) + max($a_lats))/2 .','.(min($a_lngs) + max($a_lngs))/2 ."));";

				if (is_array($mais_proximo) && count($mais_proximo)>0){
					echo "setDirections('$consumidor','{$mais_proximo['latitude']},{$mais_proximo['longitude']}','pt-br');";
				}else{
					if (strlen ($latitude) > 0 AND strlen ($longitude) > 0 and strlen($consumidor) > 0) {
						echo "setDirections('$consumidor','{$mais_proximo['latitude']},{$mais_proximo['longitude']}','pt-br');";
					}
				}
			} else {
			    echo "map.setCenter (new GLatLng(-15.815279,-48.070252),3);";
			}?>
			GEvent.addListener(gdir,"error", function() {
				setDirections("<?=$cep;?>","<?= $latitude?>,<?=$longitude?>","pt-br");
			});
		}
	}

	function setDirections(fromAddress, toAddress, locale) {
	  gdir.load("from: " + fromAddress + " to: " + toAddress,
	  { "locale": locale , "getSteps":true});
	}

	function copyClick(newMarker,oldMarker) {
		GEvent.addListener(newMarker, 'click', function(){
			GEvent.trigger(oldMarker,'click');
		});
	}

	function onGDirectionsAddOverlay(){
		// Remove the draggable markers from previous function call.
		for (var i=0; i<newMarkers.length; i++){
			map.removeOverlay(newMarkers[i]);
		}

		// Loop through the markers and create draggable copies
		for (var i=0; i<=gdir.getNumRoutes(); i++){
			var originalMarker = gdir.getMarker(i);
			latLngs[i] = originalMarker.getLatLng();
			icons[i] = originalMarker.getIcon();
			newMarkers[i] = new GMarker(latLngs[i],{icon:icons[i], draggable:true, title:'móvel'});
			map.addOverlay(newMarkers[i]);

			// Get the new waypoints from the newMarkers array and call loadFromWaypoints by dragend
			GEvent.addListener(newMarkers[i], "dragend", function(){
				var points = [];
				for (var i=0; i<newMarkers.length; i++){
					points[i]= newMarkers[i].getLatLng();
				}
				gdir.loadFromWaypoints(points);
			});

			//Bind 'click' event to original markers 'click' event
			copyClick(newMarkers[i],originalMarker);

			// Now we can remove the original marker safely
			map.removeOverlay(originalMarker);
		}
	}


	var geocoder = new GClientGeocoder();
	function showAddress(address,posto,item) {
		geocoder.getLatLng(
			address,
			function(point) {
				if (!point) {
					alert(address + " Não Encontrado!");
				} else {
					map.setCenter(point, 16);
					//alert(point+"Posto:"+posto);
					grava_ll(posto,point);
					var marker = new GMarker(point);
					map.addOverlay(marker);
					marker.openInfoWindowHtml(address);
					item.innerText = 'mapa';
				}
			}
		);
	}
*/?>

	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

	var http_forn = new Array();
	function grava_ll(posto,ponto) {
		url = "<?=$PHP_SELF?>?ajax=grava&posto="+posto+"&ponto="+ponto;
		var curDateTime = new Date();
		http_forn[curDateTime] = createRequestObject();
		http_forn[curDateTime].open('GET',url,true);
		http_forn[curDateTime].onreadystatechange = function(){
			if (http_forn[curDateTime].readyState == 4){
				if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) {
					var response = http_forn[curDateTime].responseText.split("|");
					if (response[0]=="ok"){
// 						alert('Informações Atualizadas com Sucesso: '+response[1])
					}else{
						alert('Não foi possível atualizar as informações');
					}
				}
			}
		}
		http_forn[curDateTime].send(null);
	}
</script>

<style>
	body {font: normal normal 12px / 15px Segoue UI, Trebuchet, Arial, Helvetica, Sans-Serif}
	tr+tr th {
		height:22px;
		font-weight: 700;
		color: #ffffff;
		text-align:center;
		background-color: #4A5280;
		border:1px #4A5280 solid;
		font-size: 13px;
		padding: 5px;
	}
	#Gmapa {
		width: 700px;
		height: 400px;
		border: 1px solid #979797;
	    border-radius: 6px;
	    -moz-border-radius: 6px;
	    -webkit-border-radius: 6px;
	    box-shadow: 3px 3px 5px #666;
	    -moz-box-shadow: 3px 3px 5px #666;
	    -webkit-box-shadow: 3px 3px 5px #666;
	    filter:progid:DXImageTransform.Microsoft.DropShadow(color='#666666', offX=3, offY=3,enabled=true,positive='false');
		background-color: #e5e3df;
		margin: 2em auto;
	}
</style><?php

	$thead = ($login_fabrica == 2) ? 'tfoot' : 'thead';
	$th    = ($login_fabrica == 2) ? 'tf' : 'th';

	$table = "<table width='100%' align='center' style='border:0px #77aadd solid;height:22px;'>
	<caption>$info</caption>
	<$thead>
		<tr>
			<$th colspan='$colspan' align='center'>
				<center>
				<div id='Gmapa'>
					<div style='padding: 1em; color: gray'>Carregando Mapa...</div>
				</div>
				</center>\n";
	if ($postos_mais_proximos){
		$table.= "				<p>Listagem do$pl <b>$qtde_postos_proximos</b> posto$pl mais próximo$pl</p>\n";
	}
	$table.= "
			</$th>
		</tr>
		<tr>
			<$th>$razaoText</$th>
			$fantasiaTH
			<$th>Endereço</$th>
			<$th>Bairro</$th>
			<$th>Cidade</$th>
			<$th>Estado</$th>
			<$th>CEP</$th>
			<$th>Email</$th>
			<$th>Fone</$th>
			<$th>Total Ida/Volta KM</$th>
			";
			if(($login_cliente_admin == 3890) || ($login_cliente_admin == 3791) || ($login_cliente_admin == 3790)){
				$table.= "<$th>Cidades Atendidas</$th>";
			}
			$table.= "<$th>Mapa</$th>
		</tr>
	</$thead>
	<tbody>
		<input type='hidden' id='km' name='km'>
		$tbl_postos
	</tbody>\n";
	echo $table;?>
	<tfoot>
	<tr>
		<td colspan='<?=$colspan?>'>
		<center>
			<div id="directions" style="width: 275px"></div>
		</center>
		</td>
	</tr>
	</tfoot>
</table>
</body>
</html>
