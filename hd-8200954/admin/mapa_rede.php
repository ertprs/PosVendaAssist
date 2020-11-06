<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

include '../helpdesk/mlg_funciones.php';
//HD 7277 Paulo - tirar acento do arquivo upload
function acentos1($texto) {
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" );
	return str_replace( $array1, $array2, $texto );
}

function acentos2($texto) {
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	return str_replace( $array1, $array2, $texto );
}

function acentos3($texto) {
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
	$array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
	return str_replace( $array1, $array2, $texto );
}

function geoGetCoords($address,$depth = 0) {

	$lookup_server = array(	'GOOGLE'=> 'maps.google.com',
							'YAHOO'	=> 'api.local.yahoo.com');

	$lookup_service = 'GOOGLE';
	//HD 406478 - MLG - API-Key para o domínio telecontrol.NET.br
	//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
	include '../gMapsKeys.inc';
// if ($max_distance) $map_zoom = $map_zoom - ($max_distance * 2);

	switch($lookup_service) {
					
		case 'GOOGLE':
			
			$_url = sprintf('http://%s/maps/geo?&q=%s&output=csv&key=%s',$lookup_server[$lookup_service],rawurlencode($address),$gAPI_key);
			$_result = false;
			$_result = file_get_contents($_url);

			if ($_result) {
				$_result_parts = explode(',',$_result);
				if ($_result_parts[0] != 200) return false;
				$_coords['pre'] = $_result_parts[1];
				$_coords['lat'] = $_result_parts[2];
				$_coords['lon'] = $_result_parts[3];
			}
			
			break;
		
		case 'YAHOO':
		default:
					
			$_url = sprintf('http://%s/MapsService/V1/geocode?appid=%s&location=%s',$lookup_server[$lookup_service],$gAPI_key,rawurlencode($address));
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

$qtde_de_postos = 5;    // Quantidade de postos próximos a retornar
// Já mostra no mapa o percurso até o posto mais próximo
$mostrar_mais_proximo = in_array($login_fabrica, array(52,81,114));

/**
 * get distance between to geocoords using great circle distance formula
 * 
 * @param float $lat1
 * @param float $lat2
 * @param float $lon1
 * @param float $lon2
 * @param string $unit  M=miles, K=kilometers, N=nautical miles, I=inches, F=feet
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
}?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1"/>
<title>Telecontrol - Mapa da Rede Autorizada</title>
</head>

<body onload='load()' onunload='GUnload()'><?php

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
	function anti_injection($string) {
		$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
		return strtr(strip_tags(trim($string)), $a_limpa);
	}
}

$tem_mapa = 0 ;

$cidade     = anti_injection($_REQUEST['cidade']);
$estado     = anti_injection($_REQUEST['estado']);
$pais       = anti_injection($_REQUEST['pais']);
$cep_orig   = anti_injection($_REQUEST['cep']);
$linha      = anti_injection($_REQUEST['linha']);
$consumidor = anti_injection($_REQUEST['consumidor']);
$estado     = ((!$estado or $estado == '00') and $consumidor) ? substr($consumidor, -2) : $estado;
//echo $estado;

$cond_cadence = ($login_fabrica == 35 ) ? " AND tipo_posto <> 163 " : "";

if (strlen ($estado) > 0) {
	$estado = strtoupper($estado);
	$cond_estado = '';
	switch ($estado) {
		case '00':			$cond_estado = ''; break;
		case 'BR-CO':		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MT','MS','GO','DF','TO')"; break;
		case 'BR-N':		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR')"; break;
		case 'BR-NEES': //NEES e NE é Nordeste...
		case 'BR-NE':		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('MA','CE','PI','RN','PB','PN','AL','SE','BA','PE')"; break;
		case 'BR-SUL':
		case 'SUL':			$cond_estado = "tbl_posto_fabrica.contato_estado IN ('PR','SC','RS')"; break;
		case 'SP-CAPITAL':  $cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND TRIM(tbl_posto_fabrica.contato_cidade) ~* 's.o paulo' "; break;
		case 'SP-INTERIOR': $cond_estado = "tbl_posto_fabrica.contato_estado ='SP' AND TRIM(tbl_posto_fabrica.contato_cidade) !~* 's.o paulo'"; break;
		case 'BR-NCO':		$cond_estado = "tbl_posto_fabrica.contato_estado IN ('AM','AC','AP','PA','RO','RR','MT','MS','GO','DF','TO')"; break;
		default:			$cond_estado = (preg_match('/^[A-Z]{2}$/i', $estado)) ? "tbl_posto_fabrica.contato_estado = '$estado'" : '';
	}
}

$cond_estado = ($cond_estado == '') ? $cond_estado : "AND  CASE WHEN tbl_posto.pais = 'BR' THEN $cond_estado ELSE FALSE END ";
if ($pais) $cond_pais = " tbl_posto_fabrica.contato_pais = '$pais' ";

$sql_campo_linha = 'tbl_posto_fabrica.divulgar_consumidor,';
$sql_join_linha  = "";

if (strlen($linha) > 0) {
	$sql = "SELECT *
			FROM   tbl_linha
			WHERE   tbl_linha.fabrica = $login_fabrica
			AND     tbl_linha.linha   = $linha
			ORDER BY tbl_linha.nome;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$aux_linha = trim(pg_result($res,$x,'linha'));
		$aux_nome  = trim(pg_result($res,$x,'nome'));
		$info = "<br /><b>Linha: </b>$aux_nome\n";
	}

	$sql_add = " AND tbl_posto.posto IN (
		SELECT DISTINCT posto FROM tbl_posto_fabrica JOIN tbl_posto_linha USING(posto)
		WHERE linha = $linha AND  fabrica = $login_fabrica) ";
		
	if ( $login_fabrica == 24 ){
		$sql_campo_linha = 'tbl_posto_linha.divulgar_consumidor,';
		$sql_join_linha  = "JOIN   tbl_posto_linha ON tbl_posto_linha.posto=tbl_posto_fabrica.posto and tbl_posto_linha.linha = $linha";
		$sql_add .= " AND tbl_posto_linha.divulgar_consumidor IS TRUE ";
	}

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
	//HD 382941: Ébano: ascrescentei busca com TO_ASCII neste caso, para ignorar os acentos na base de dados
	$cond_cidade = "TO_ASCII(tbl_posto_fabrica.contato_cidade, 'LATIN1') ~* TO_ASCII('^$xcidade3$', 'LATIN1') ";
}

if (!empty($cidade)) $vet_local[] = $cidade;
if (!empty($estado)) $vet_local[] = $estado;

//HD 382491 - AGORA VERIFICA COM POR CEP e ENDEREÇO DO CONSUMIDOR
$consumidor_pre = 0;
$tot_pesquisa   = !isset($callcenter) ? 1 : 2;

if (strlen($consumidor) and preg_match('/^\d{5}-?\d{3}$/', trim($cep_orig))) {
	list ($c_logr, $c_num, $c_cidade, $c_estado) = explode(',', $consumidor);
	$c_end  = trim("$c_logr $c_num");
	$c_end_pesquisa = implode (',', array(0 => $c_end, $c_cidade, $cep_orig));
}
if ($_GET['debug']=='t') echo "$c_end_pesquisa <br />";
if (!isset($c_end_pesquisa)) $c_end_pesquisa = $cep_orig;

if(is_array($vet_local) and ($tot_pesquisa == 1)) {
	$pesquisar      = implode(',',$vet_local);
}else{
	$pesquisar = $c_end_pesquisa;
}

for ($hx = 0; $hx < $tot_pesquisa; $hx++) {

	$endereco_consumidor = geoGetCoords($pesquisar);//vetor com as coordenadas

	if ($endereco_consumidor['pre'] > $consumidor_pre) {

		$consumidor_pre = $endereco_consumidor['pre']; 
		$consumidor_lng = $endereco_consumidor['lon']; 
		$consumidor_lat = $endereco_consumidor['lat'];   /*  Para coincidir com o banco! */

	}

	$pesquisar = $consumidor;

}//HD 382491

if ($consumidor and ($consumidor_lng=='' and $consumidor_lat =='')) {
	echo "<p>Endereço do consumidor não localizado no mapa!</p>\n";
} else {
	$max_distance = 0.5;    // Rádio de busca em Graus (aprox.)

	while ($qtde_postos_proximos < 5 and $max_distance < 2.6) { //  Procura até achar 5 postos... até 5° ao redor
		unset($sql_dist);
		if (strlen(trim($consumidor_lat)) > 0 and isset($callcenter)) {
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
		$cond_lugar.= ($cond_lugar != '') ? " AND $cond_pais":$cond_pais;
		//$cond_lugar.= ($cond_lugar != '') ? "\n\t\t\tAND ":'';
		$sql = "SELECT tbl_posto.posto,
					   TRIM (tbl_posto.nome)                       AS nome,
					   TRIM (tbl_posto_fabrica.nome_fantasia)      AS nome_fantasia,
					   TRIM (tbl_posto_fabrica.contato_endereco)   AS endereco,
					   tbl_posto_fabrica.contato_numero            AS numero,
					   tbl_posto_fabrica.contato_complemento       AS complemento,
					   tbl_posto_fabrica.contato_fone_comercial,
					   tbl_posto_fabrica.contato_fone_residencial,
					   tbl_posto_fabrica.contato_cidade            AS cidade,
					   tbl_posto_fabrica.contato_bairro            AS bairro,
					   tbl_posto_fabrica.contato_cep               AS cep,
					   tbl_posto_fabrica.contato_estado            AS estado,
					   tbl_posto.latitude,
					   tbl_posto.longitude,
					   tbl_posto_fabrica.codigo_posto,
					   tbl_posto_fabrica.contato_email             AS email,
					   $sql_campo_linha
					   tbl_posto.fone
					   $sql_dist
				FROM   tbl_posto
				JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				$sql_join_linha
				WHERE  $cond_lugar\n";
		if($login_fabrica <> 43){
			$sql .= ($cond_lugar)?' AND ':'';
			$sql.= "tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";
		}
		$sql .= " $sql_add
				$cond_estado
				$cond_cadence
				AND tbl_posto_fabrica.divulgar_consumidor IS NOT FALSE
				AND tbl_posto.posto <> 6359
				ORDER BY ";
		$sql.= ($sql_dist)?"distancia, ":'';
		$sql.= "tbl_posto_fabrica.contato_pais, tbl_posto_fabrica.contato_estado,
				tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_cep";
		$sql.= ($sql_dist) ? "\n		LIMIT $qtde_de_postos":'';
	//echo (nl2br($sql));
		$resPosto = pg_query($con,$sql);
		$qtde_postos_proximos = @pg_num_rows($resPosto);
		$max_distance += 0.5;
	}
}
//echo (nl2br($sql));
if ($qtde_postos_proximos) {
	$tem_mapa = 1;
	$a_lats[] = $latitude;
	$a_lngs[] = $longitude;

	if ($mostrar_mais_proximo) {
//		Calcula a distância em Km dos 5 caminhos mais proximos
		for ($i = 0 ; $i < $qtde_postos_proximos; $i++){
			$posto		= pg_result($resPosto, $i, posto);
			$latitude	= pg_result($resPosto, $i, latitude);	//Esta gravando invertido!!
			$longitude	= pg_result($resPosto, $i, longitude);	//Esta gravando invertido!!

			if ($latitude && $longitude) {
				$metros = geoGetDistance($endereco_consumidor['lat'],  $endereco_consumidor['lon'], $longitude, $latitude, 'K');
				$distancias_postos[$posto] = array('distancia' => $metros);
			}
		}
		asort($distancias_postos); //   Ordena por distância, o mais próximo agora é o primeiro índice
 //     Determinando as coordenadas máx. e mín. para mostrar todos os pontos do mapa...

		if ($postos_mais_proximos){
			$info .= "<br />&nbsp;&nbsp;<b>$qtde_de_postos postos mais próximos:</b>";
		}

		$postos_mais_proximos = array_keys($distancias_postos);

		if ($qtde_postos_proximos) {
		    $pl = ($qtde_postos_proximos == 1) ? '':'s';
			$info .= "<br />&nbsp;&nbsp;<b>O$pl $qtde_postos_proximos posto$pl mais próximo$pl:</b>";
		}
	}?>
<center>
	<p style='font-weight:bold'>Clique sobre as marcas para ver informações detalhadas do posto</p>
	<p>
		+) Podem haver postos que não apareçam no mapa, por estarem com o endereço incorreto<br />
		+) A localização dos postos não é exata, podendo haver margem de erro<br />
		+) Caso ele encontre o endereço, mas não consiga traçar a rota, tente remover o número da residência, o google não mantem atualizado
	</p><?php
}

/*  Zoom do mapa: 0: mapamundi, 20: aprox. máx. */
$map_zoom = 12;
if (!$consumidor) $map_zoom = 5;
if ($estado) $map_zoom = 8;
if ($cidade) $map_zoom = 10;

//HD 406478 - MLG - API-Key para o domínio telecontrol.NET.br
//HD 678667 - MLG - Adicionar mais uma Key. Alterado para um include que gerencia as chaves.
include '../gMapsKeys.inc';
// if ($max_distance) $map_zoom = $map_zoom - ($max_distance * 2);

//HD 382941: Ébano: acrescentei estas duas linhas caso não localize nada, assim não dá erro
if (strlen($consumidor_lat) == 0) $consumidor_lat = 0;
if (strlen($consumidor_lng) == 0) $consumidor_lng = 0;

?>

<script src="http://maps.google.com/maps?file=api&v=2&key=<?=$gAPI_key?>" type="text/javascript"></script>
<script type='text/javascript' src='js/jquery-1.3.2.js'></script>
<link type="text/css" rel="stylesheet" href="css/css.css">
<style type="text/css">

	.titulo_tabela td {
		background-color:#596d9b;
		font: bold 14px "Arial" !important;
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna {
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
</style>
<script type="text/javascript">

$().ready(function(){
	$(".btn_consulta_cidades_atendidas").each(function(){
		$(this).click(function(){
			var codigo = $(this).attr("id_posto");
			if(codigo != ""){
				var URL = "cidades_atendidas.php?codigo="+ codigo +"&nome="+ $(this).attr("id_nome");
				window.open(URL,  "janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=450,height=200,top=18,left=0" );
			}else{
				alert("Código do posto inválido");
			}
		});
						
	})
	
	$('.km_distancia').each(function(){
		$(this).click(function(){	   
			$('.posto_codigo', window.opener.document).val($(this).attr('cod_posto'));
			$('.posto_nome',   window.opener.document).val($(this).attr('nome_posto'));
			$('.km_distancia', window.opener.document).val($(this).attr('km'));
			$('.posto_fone',   window.opener.document).val($(this).attr('fone_posto'));
			$('.posto_email',  window.opener.document).val($(this).attr('email_posto'));
			window.close();
		}); 
	});

});

	var map;
	var end_consumidor_lat = <?=$consumidor_lat?>;
	var end_consumidor_lng = <?=$consumidor_lng?>;
	var MostrarCaminho     = <?=($mostrar_mais_proximo) ? 'true' : 'false'; ?>;
	var mapZoom            = <?=$map_zoom?>;
	var verificacao        = 0;
	var newMarkers         = new Array();//HD 288186
	var latLngs            = new Array();//HD 288186
	var icons              = new Array();//HD 288186

	function load() {

		if (GBrowserIsCompatible()) {

			map = new GMap2(document.getElementById("Gmapa"));
			gdir = new GDirections(map, document.getElementById("directions"));
			map.addControl(new GMapTypeControl());
			map.addControl(new GLargeMapControl());

			var pt1 = '';
			var pt2 = '';

			//  Ícone para o local do consumidor
			if(pt1 != "" && pt2 != ""){
				if (end_consumidor_lat != 0 && end_consumidor_lng != 0) {<?php
					if (isset($callcenter)) {
	?>					var iconeConsumidor    = new GIcon(G_DEFAULT_ICON);
						iconeConsumidor.image  = 'http://chart.apis.google.com/chart?chst=d_map_pin_icon&chld=home|FF0000|000000';
						iconeConsumidor.shadow = 'http://chart.apis.google.com/chart?chst=d_map_pin_shadow';

						var coordsConsumidor = new GLatLng(end_consumidor_lat, end_consumidor_lng);
						map.addOverlay(new GMarker(coordsConsumidor, {icon:iconeConsumidor}));<?php
					} else {?>
						pt1 = end_consumidor_lat;
						pt2 = end_consumidor_lng;<?php
					}?>
				}

				gdir.loadFromWaypoints([pt1,pt2], {locale:"pt-br", getSteps:true});

				GEvent.addListener(gdir,"load", function() {

					for (var i = 0; i < gdir.getNumRoutes(); i++) {
						var route = gdir.getRoute(i);
						var dist  = route.getDistance();
						var x     = dist.meters * 2 / 1000;
						var y     = x.toString().replace(".",",");
						var valor_calculado = parseFloat(x);
					}

					document.getElementById('km').value = ((Math.round(x*100))/100);

				});

				GEvent.addListener(gdir, "addoverlay", onGDirectionsAddOverlay);
			}
			map.setCenter(new GLatLng(end_consumidor_lat,end_consumidor_lng),mapZoom);<?php

			$centro_mapa = ($consumidor_lat != 0 and $consumidor_lng != 0);

			//  Config. para a tabela
			if($tem_mapa) {
				$colspan = 9;
			} else {
				$colspan = 8;
			}

			if ($login_fabrica == 52) {
				$fantasiaTH = '<td>Nome Fantasia</td>';
				$fantasiaTD = 'nome_fantasia';
				$colspan = 9;
			}

			$razaoText = ($login_fabrica == 59) ? 'Nome Fantasia' : 'Nome do Posto';
			$razao     = ($login_fabrica == 59) ? 'nome_fantasia' : 'nome';

			if ($tem_mapa == "1") {

				for ($i = 0; $i < $qtde_postos_proximos; $i++) {
					$posto			= pg_result($resPosto, $i, 'posto');
					$codigo_posto	= pg_result($resPosto, $i, 'codigo_posto');
					$nome			= pg_result($resPosto, $i, 'nome');
					$nome_fantasia	= pg_result($resPosto, $i, 'nome_fantasia');
					$email			= strtolower(pg_result($resPosto, $i, 'email'));
					$endereco		= pg_result($resPosto, $i, 'endereco');
					$numero			= pg_result($resPosto, $i, 'numero');
					$complemento            = pg_result($resPosto, $i, 'complemento');
					$fone			= pg_result($resPosto, $i, 'contato_fone_comercial');
					$fone2			= pg_result($resPosto, $i, 'contato_fone_residencial');
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

					// echo "/* posto: $posto - Lat: $latitude, Long: $longitude (Distância: $distancia) */\n";
					$nome     = str_replace("\"","",$nome);
					$nome     = str_replace("'","",$nome);
					$endereco = str_replace("\"","",$endereco);
					$endereco = str_replace("'","",$endereco);
					$complemento = str_replace("\"","",$complemento);
                                        $complemento = str_replace("'","",$complemento);
					$cidade   = str_replace("\"","",$cidade);
					$cidade   = str_replace("'","",$cidade);
					$bairro   = str_replace("\"","",$bairro);
					$bairro   = str_replace("'","",$bairro);
					$cep      = preg_replace('/\D/',"",$cep);
					$cep      = str_replace("'","",$cep);
					$latlng   = "$latitude,$longitude";

					$cep = preg_replace('/(\d{2})(\d{3})(\d{3})/','$1$2-$3',$cep); //substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3);

					//  Formata o nº de telefone
					$fone  = phone_format($fone);
					$fone2 = phone_format($fone2);

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
										"codigo_posto_tab.focus();".
										"this.close();\"";
					if ($login_fabrica == 52)
						$link_posto  =  "posto_km_tab.value = document.getElementById('km').value;".
										"posto_email_tab.value='$email';".
										"posto_fone_tab.value='$fone';";
					if ($login_fabrica != 3 and isset($callcenter))
						$link_posto  =  "posto_tab.value= '$posto';".
										"codigo_posto_tab.value='$codigo_posto';".
										"posto_nome_tab.value='$nome';".
										"posto_email_tab.value='$email';".
										"posto_fone_tab.value='$fone';".
										"codigo_posto_tab.focus();".
										"window.close();\"";
					$link_posto = ($login_fabrica==3 or isset($callcenter)) ? '<a href="javascript:'.$link_posto.">".$$razao.'</a>' : $$razao;
					
					$descricao = "<tr bgcolor='$cor' style='border:1px #77aadd solid;height:22px; font-size: 10px'>
							    <td>$link_posto</td>\n";

					$descricao.= ($login_fabrica == 52) ? "\t\t\t\t\t\t<td>$nome_fantasia</td>\n" : '';

					if(in_array($login_fabrica, array(81,114))) {	
					$descricao.= "\t\t\t\t\t\t<td>$endereco, $numero, $complemento</td>
						<td nowrap>$bairro</td>
						<td nowrap>$cidade</td>
						<td nowrap align='center'>$estado</td>
						<td nowrap align='right'>$cep</td>
						<td nowrap align='right'>$email</td>
						<td nowrap align='right'>$fone</td>
						<td nowrap align='right'>$fone2</td>
						";
					} else {
						$descricao.= "\t\t\t\t\t\t<td>$endereco, $numero, $complemento</td>
							<td nowrap>$bairro</td>
							<td nowrap>$cidade</td>
							<td nowrap align='center'>$estado</td>
							<td nowrap align='right'>$cep</td>
							<td nowrap align='right'>$email</td>
							<td nowrap align='right'>$fone</td>";
						if($login_fabrica == 30){
							$descricao.= "<td nowrap align='right'><a id_posto='{$codigo_posto}' id_nome='{$nome}' class='btn_consulta_cidades_atendidas' id='btn_consulta_cidades_atendidas' href='javascript:void(0);'> Cidades atendidas</a></td>";
						}
					}

					$tbl_postos_desc 	   .= $descricao;
					$tbl_postos_excel_desc .= $descricao;

					if (strlen ($latitude) > 0 and strlen ($longitude) > 0) {
					    $acao = 'mapa';
					    $link_acao  = "map.setCenter(new GLatLng($latlng),16);";
						//HD 951137 - Atualizar o percurso mostrado no mapa com o end. do posto selecionado
						if ($consumidor_lat and $consumidor_lng)
							$link_acao .= "setDirections(\"$consumidor_lat, $consumidor_lng\",\"$latlng\",\"pt-br\")";
					} else {
					    $acao = 'localizar';
					    $link_acao = "showAddress(\"$endereco $cidade $cep\",\"$posto\",this);";
					}

					$tbl_postos_desc .= "<td>
											<input id='address_$i' type='hidden' value = '$endereco,$numero,$cidade,br'>
											<a href='#mapa_inicio' onclick='$link_acao'>$acao</a>
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
						echo "posto_$posto.openInfoWindowHtml('<FONT SIZE=\"-1\"><b>$nome</b> <br /> $endereco, $numero, $complemento <br /> fone: $fone <br /> $cidade - $estado - $cep </FONT>'); \n";
						if(in_array($login_fabrica, array(81,114))){
						echo "posto_$posto.openInfoWindowHtml('<FONT SIZE=\"-1\"><b>$nome</b> <br /> $endereco, $numero, $complemento <br /> fone: $fone  <br /><br /> fone 2: $fone2  <br /> $cidade - $estado - $cep </FONT>'); \n";
						}
						echo "}); \n";
						echo "\n\n";

					}
				}

				$a_lats = array_filter($a_lats);
				$a_lngs = array_filter($a_lngs);

		        echo " map.setCenter (new GLatLng(".(min($a_lats) + max($a_lats))/2 .','.(min($a_lngs) + max($a_lngs))/2 ."));";

				if (is_array($mais_proximo) && count($mais_proximo) > 0) {

					echo "setDirections('$consumidor_lat, $consumidor_lng','{$mais_proximo['latitude']},{$mais_proximo['longitude']}','pt-br');";

				} else {

					if (strlen($latitude) > 0 && strlen($longitude) > 0 && strlen($consumidor) > 0) {
						echo "setDirections('$consumidor_lat, $consumidor_lng','{$mais_proximo['latitude']},{$mais_proximo['longitude']}','pt-br');";
					}

				}

			} else {

				echo "map.setCenter(new GLatLng(-15.815279,-48.070252),3);";

			}

			$tbl_postos 	  = $tbl_postos_desc;
			$tbl_postos_excel = $tbl_postos_excel_desc;

			?>

			GEvent.addListener(gdir,"error", function() {

				if (verificacao > 3) {

					if (gdir.getStatus().code == G_GEO_UNKNOWN_ADDRESS) {
						alert("O endereço informado não pôde ser localizado no GoogleMaps. \nIsto pode ter acontecido por o endereço ser muito recente ou estar incompleto ou incorreto.");
					} else if (gdir.getStatus().code == G_GEO_SERVER_ERROR) {
						alert("Não foi possível localizar um dos endereços.");
					} else if (gdir.getStatus().code == G_GEO_MISSING_QUERY) {
						alert("Não foi informado um dos endereços.");
					} else if (gdir.getStatus().code == G_GEO_BAD_KEY) {
						alert("Erro de configuração. Contate a Telecontrol. Obrigado.");
					} else if (gdir.getStatus().code == G_GEO_BAD_REQUEST) {
						alert("GoogleMaps não entendeu algum dos endereços fornecidos.");
					}

					return false;

				}<?php
				if (isset($callcenter)) {?>
					setDirections("<? echo "$consumidor_lat, $consumidor_lng"; ?>","<?= $mais_proximo['latitude']?>,<?=$mais_proximo['longitude']?>","pt-br");<?php
				}?>
				verificacao++;

			});
		}
	}

	function setDirections(fromAddress, toAddress, locale) {
		//alert("from: " + fromAddress + " to: " + toAddress);
		//fromAddress = 'R. Santa Rita - Vila Camargo, Bauru - SP, 17060-130';
		gdir.load("from: " + fromAddress + " to: " + toAddress,{ "locale": locale , "getSteps":true});
	}

	function copyClick(newMarker,oldMarker) {
		GEvent.addListener(newMarker, 'click', function(){
			GEvent.trigger(oldMarker,'click');
		});
	}

	function onGDirectionsAddOverlay() {

		// Remove the draggable markers from previous function call.
		for (var i = 0; i < newMarkers.length; i++) {
			map.removeOverlay(newMarkers[i]);
		}

		// Loop through the markers and create draggable copies
		for (var i = 0; i <= gdir.getNumRoutes(); i++) {

			var originalMarker = gdir.getMarker(i);

			latLngs[i] = originalMarker.getLatLng();
			icons[i]   = originalMarker.getIcon();

			newMarkers[i] = new GMarker(latLngs[i],{icon:icons[i], draggable:true, title:'móvel'});
			map.addOverlay(newMarkers[i]);

			// Get the new waypoints from the newMarkers array and call loadFromWaypoints by dragend
			GEvent.addListener(newMarkers[i], "dragend", function() {

				var points = [];

				for (var i = 0; i < newMarkers.length; i++) {
					points[i] = newMarkers[i].getLatLng();
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
		console.log(item);
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
					item.innerHTML = 'mapa';
				}
			}
		);
	}

	function createRequestObject() {

		var request_;
		var browser = navigator.appName;

		if (browser == "Microsoft Internet Explorer") {
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		} else {
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

<style type="text/css">
	body {
	font: normal normal 12px / 15px Segoue UI, Trebuchet, Arial, Helvetica, Sans-Serif
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
	

.cidades_atendidas th{
	height:22px;
	font-weight:bold;
	text-align:center;
	background-color: #eeeeff;
	border:1px #77aadd solid;
	font-size: 14px;

}
.th_titulo th{
margin-top: 50px;
}
.km_distancia{
	text-decoration:underline;
	color: 0101ee;
	cursor: pointer;
}
table.tabela tr td{
	height: 25px;
}
</style><?php

	$thead   = ($login_fabrica == 2) ? 'tfoot' : 'thead';

	// Fábricas que geram o excel (HD-936214)
	$fabricas_geram_excel = array(86, 81, 114);

	// Inicializa o arquivo XLS
	if(in_array($login_fabrica, $fabricas_geram_excel)) {

		$caminho = "xls/relatorio-mapa-rede-$login_fabrica.xls";
		$fp 	 = fopen ($caminho,"w");

		$table_excel .= "<table width='700' class='tabela' align='center'>
						 <$thead>";
	}

	$table = "<table width='700' class='tabela' align='center'>
			  <caption>$info</caption>
			  <$thead>
			    <tr>
					<th colspan='$colspan' align='center'>
						<center>
							<div id='Gmapa'>
								<div style='padding: 1em; color: gray'>Carregando Mapa...</div>
							</div>
						</center>
					</td>
				</tr>";

	if ($postos_mais_proximos){
		$desc 		  = "				<p>Listagem do$pl <b>$qtde_postos_proximos</b> posto$pl mais próximo$pl</p>\n";
		$table 		 .= $desc;
		$table_excel .= $desc;
	}

	// Mostra a opção para gerar excel caso a fabrica estiver no array
	if(in_array($login_fabrica, $fabricas_geram_excel)) {

		if($login_fabrica == 86) {
			$colspan_excel = 9;
		} else if($login_fabrica == 52 or $login_fabrica == 114 or $login_fabrica == 81) {
			$colspan_excel = 10;
		} else {
			$colspan_excel = 7;
		}
	} 

	if(in_array($login_fabrica, array(81,114))) {
		$table .= (in_array($login_fabrica, $fabricas_geram_excel) ? "
			<tr style=\"text-align: center\">
				<td colspan=\"$colspan_excel\" style='border: 0; font: bold 14px \"Arial\";'><a href=\"$caminho\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"20px\" width=\"20px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>
			</tr>
			<tr><td colspan=\"$colspan_excel\" style='border: 0;'>&nbsp;</td></tr>" : '');

		$desc = "
			<tr class='titulo_tabela'>
				<td>$razaoText</td>
				$fantasiaTH
				<td>Endereço</td>
				<td>Bairro</td>
				<td>Cidade</td>
				<td>Estado</td>
				<td>CEP</td>
				<td>Email</td>
				<td>Fone</td>
				<td>Fone 2</td>";

		$table 		 .= $desc . "<td>Mapa</td>";
		$table_excel .= $desc;

		$desc = '
				</tr>
			</$thead>
			';

		$table 		 .= $desc;
		$table_excel .= $desc;

	} else {

		$table .= (in_array($login_fabrica, $fabricas_geram_excel) ? "
			<tr style=\"text-align: center\">
				<td colspan=\"$colspan_excel\" style='border: 0; font: bold 14px \"Arial\";'><a href=\"$caminho\" target=\"_blank\" style=\"text-decoration: none; \"><img src=\"imagens/excel.png\" height=\"20px\" width=\"20px\" align=\"absmiddle\">&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>
			</tr>
			<tr><td colspan=\"$colspan_excel\" style='border: 0;'>&nbsp;</td></tr>" : '');

		$desc 	= "
			<tr class='titulo_tabela'>
				<td>$razaoText</td>
				$fantasiaTH
				<td>Endereço</td>
				<td>Bairro</td>
				<td>Cidade</td>
				<td>Estado</td>
				<td>CEP</td>
				<td>Email</td>
				<td>Fone</td>";

				if($login_fabrica == 30){
					$desc.= "<td>Cidades Atendidas</td>";
				}

				$table 		 .= $desc;
				$table_excel .= $desc;

				$table .= "<td>Mapa</td>";

				$desc = '
						</tr>
					</$thead>
					';

				$table 		 .= $desc;
				$table_excel .= $desc;

	}

	$table .= "
	<tbody>
		<input type='hidden' id='km' name='km'>
		$tbl_postos
	</tbody>\n";

	$table_excel .= "
	<tbody>
		<input type='hidden' id='km' name='km'>
		$tbl_postos_excel
	</tbody>\n";
	
	echo $table;

	if(in_array($login_fabrica, $fabricas_geram_excel)) {
		fwrite($fp, $table_excel);
	}

	//print nl2br($table_excel); exit;

if($login_fabrica == 52 OR $login_fabrica ==120) {	

	$consumidor_estado = $_GET['consumidor_estado'];
	$consumidor_cidade = $_GET['consumidor_cidade'];

	$sql = "SELECT
			tbl_posto_fabrica.codigo_posto,
			tbl_posto_fabrica.contato_endereco AS endereco,
			tbl_posto_fabrica.contato_bairro AS bairro,
			tbl_posto_fabrica.contato_cidade AS cidade,
			tbl_posto_fabrica.contato_estado AS estado,
			tbl_posto_fabrica.contato_cep AS cep,
			tbl_posto_fabrica.contato_email as email,
			tbl_posto_fabrica.nome_fantasia,
		   	tbl_posto_fabrica_ibge.km,
		   	tbl_posto.nome AS nome,
			tbl_posto.fone AS fone
			
			
			FROM
			tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica.fabrica=tbl_posto_fabrica_ibge.fabrica 
			
			AND 
			tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
			JOIN tbl_ibge ON tbl_posto_fabrica_ibge.cod_ibge = tbl_ibge.cod_ibge
			
			WHERE
			tbl_posto_fabrica.fabrica={$login_fabrica}
			AND tbl_ibge.estado=UPPER('{$consumidor_estado}')
			AND tbl_ibge.cidade_pesquisa=UPPER(fn_retira_especiais('{$consumidor_cidade}'))
			
			ORDER BY
			tbl_posto_fabrica_ibge.km";


	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0){

		$desc = "<tr><td style='border:0px;'>&nbsp;</td></tr>
				<tr class='titulo_tabela'>
					<td colspan='10' align='center' style='border:0px; background-color: transparent; color: black;'><b>Cidades Atendidas</b></td>
				<tr class='titulo_tabela' style='margin-top:20px;'>
					<td>Nome do Posto</td>
					<td>Nome Fantasia</td>
					<td>Endereço</td>
					<td>Bairro</td>
					<td>Cidade</td>
					<td>Estado</td>
					<td>CEP</td>
					<td>Email</td>
					<td>Fone</td>
					<td>KM</td>
				</tr>
				<tr>";

		$i = 0;
		while ($resultado = pg_fetch_array($res)) {
			$bgcolor = $i % 2 == 0 ? "#eeeeff" : "#ffffff" ;
			
			$cep_atendidas = $resultado['cep'];
			$cep_atendidas = preg_replace('/(\d{2})(\d{3})(\d{3})/','$1$2-$3',$cep_atendidas);
			$desc .= "
			<tr bgcolor='{$bgcolor}' style='height:22px; font-size: 10px' >
				<td>
					<a class='km_distancia' 
					km='{$resultado['km']}' 
					cod_posto='{$resultado['codigo_posto']}'  
					nome_posto='{$resultado['nome']}'
					email_posto='{$resultado['email']}'
					fone_posto= '{$resultado['fone']}'
					href'#'>{$resultado['nome']}</a>
				</td>
				
				<td>{$resultado['nome_fantasia']}</td>
				<td>{$resultado['endereco']}</td>
				<td>{$resultado['bairro']}</td>
				<td>{$resultado['cidade']}</td>
				<td align='center'>{$resultado['estado']}</td>
				<td>{$cep_atendidas}</td>
				<td align='right'>{$resultado['email']}</td>
				<td>{$resultado['fone']}</td>
				<td><a class='km_distancia'
					km='{$resultado['km']}' 
					cod_posto='{$resultado['codigo_posto']}'  
					nome_posto='{$resultado['nome']}'
					email_posto='{$resultado['email']}'
					fone_posto= '{$resultado['fone']}'
					href'#'>{$resultado['km']}</a></td>
			</tr>
			";
			$i++;
		}
	}

	$table_distancia .= $desc;
	$table_excel	 .= $desc;

	echo $table_distancia;
}

?>
	
	<tfoot>
	<tr>
		<th colspan='<?=$colspan?>'>
		<center>
			<div id="directions" style="width: 275px"></div>
		</center>
		</th>
	</tr>

	</tfoot>
</table>

</body>
</html>
