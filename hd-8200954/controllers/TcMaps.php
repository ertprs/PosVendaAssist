<?php
include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';

use Posvenda\TcMaps;



$ajax = $_REQUEST['ajax'];
$fabrica = $_REQUEST['fabrica'];

if(empty($fabrica)){
    if(array_key_exists("sess", $_COOKIE)){
        $cookie = get_cookie_login($_COOKIE['sess']);

        if(array_key_exists("cook_fabrica", $cookie)){
            $fabrica = $cookie['cook_fabrica'];
        }else{
            $fabrica = "";
        }
    }
}


$oTcMaps = new TcMaps($fabrica, $con);

switch ($ajax) {
    case "geocode" :

        $endereco       = empty($_REQUEST['endereco'])  ? "" : trim($_REQUEST['endereco']);
        $numero         = empty($_REQUEST['numero'])    ? "" : trim($_REQUEST['numero']);
        $bairro         = empty($_REQUEST['bairro'])    ? "" : trim($_REQUEST['bairro']);
        $cidade         = empty($_REQUEST['cidade'])    ? "" : trim($_REQUEST['cidade']);
        $cep            = empty($_REQUEST['cep'])       ? "" : trim($_REQUEST['cep']);

		$endereco = str_replace("/","",$endereco);

        $estado         = empty($_REQUEST['estado'])    ? "" : trim($_REQUEST['estado']);
        $pais           = empty($_REQUEST['pais'])      ? "" : trim($_REQUEST['pais']);

        $response = $oTcMaps->geocode($endereco, $numero, $bairro, $cidade, $estado, $pais, $cep);
        echo json_encode($response);

        break;

    case "route" :

        $lat_lng_origem     = $_REQUEST['origem'];
        $lat_lng_destino    = $_REQUEST['destino'];
        $ida_volta          = $_REQUEST['ida_volta'];

        $response = $oTcMaps->route($lat_lng_origem, $lat_lng_destino);

        if($ida_volta == 'sim' && empty($response["error"])) {
            $km_ida =  $response['total_km'];
            $response2 = $oTcMaps->route($lat_lng_destino,$lat_lng_origem);
            $km_volta =  $response2['total_km'];
            unset($response['total_km']);
            $response['km_ida'] = $km_ida;
            $response['km_volta'] = $km_volta;
            $response['total_km'] = $km_volta + $km_ida;
        }

        echo json_encode($response);

        break;

    case "near" :

        $latlon  = $_REQUEST['latlon'];
        //$latlon  = array("latitude" => "-22.2300565", "longitude" => "-49.9272667");
        $response = $oTcMaps->near($latlon);
        echo json_encode($response);

        break;


    default :
        echo json_encode(array("error" => "Função não definida"));
        break;
}
