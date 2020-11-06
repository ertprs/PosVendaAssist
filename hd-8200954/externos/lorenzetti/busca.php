<?php 

require_once('../../admin/dbconfig.php');
require_once('../../admin/includes/dbconnect-inc.php');
use Posvenda\TcMaps;
$oTcMaps = new TcMaps(19, $con);

	function maskCep($cep) {
	    $num_cep = preg_replace('/\D/', '', $cep);
	    return (strlen($cep == 8)) ? preg_replace('/(\d\d)(\d{3})(\d{3})/', '$1.$2-$3', $num_cep) : $cep;
	}

	function retira_acentos($texto) {
	    $array1 = array( 'á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç'
	    , 'Á', 'À', 'Â', 'Ã', 'Ä', 'É', 'È', 'Ê', 'Ë', 'Í', 'Ì', 'Î', 'Ï', 'Ó', 'Ò', 'Ô', 'Õ', 'Ö', 'Ú', 'Ù', 'Û', 'Ü', 'Ç' );
	    $array2 = array( 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c'
	    , 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'C' );
	    return str_replace( $array1, $array2, $texto);
	}


	function getLatLonConsumidor($logradouro = null, $bairro = null, $cidade, $estado, $pais = "BR", $cep){
	    global $con, $fabrica, $oTcMaps;
	    try{
	        $retorno = $oTcMaps->geocode($logradouro, null, $bairro, $cidade, $estado, $pais, $cep);
	        return $retorno['latitude']."@".$retorno['longitude'];
	    }catch(Exception $e){
	        return false;
	    }
	}

	function formatCEP($cepString){
	    $cepString = str_replace("-", "", $cepString);
	    $cepString = str_replace(".", "", $cepString);
	    $cepString = str_replace(",", "", $cepString);
	    $antes = substr($cepString, 0, 5);
	    $depois = substr($cepString, 5);
	    $cepString = $antes."-".$depois;
	    return $cepString;
	}

	function getEnderecoCEP($cep) {
		$_GET["busca_ura"] = true;
		$_GET["method"] = "database";
		$_GET["cep"]    = $cep;
		return include_once("../ajax_cep.php");

	}

	$fabrica       = 19;

    $codigo_linha = $_GET["especialidade"];
    $cep          = $_GET["cep"];
    $cond_uf      = "";
    $cond_cidade  = "";
    $order_by     = "tbl_posto_fabrica.contato_cidade,tbl_posto_fabrica.nome_fantasia";

    list ($ok, $endereco, $bairro, $cidade, $estado) = explode(";", getEnderecoCEP($cep));
    $latLonConsumidor = getLatLonConsumidor($endereco, $bairro, $cidade, $estado,'BR',$cep);
    $parte = explode('@', $latLonConsumidor);
    $from_lat = substr(trim($parte[0]), 0, 7);
    $from_lon = substr(trim($parte[1]), 0, 7);

    $coluna_distancia = ", (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distancia";
    $cond_distancia = "AND (111.045 * DEGREES(ACOS(COS(RADIANS({$from_lat})) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS({$from_lon})) + SIN(RADIANS({$from_lat})) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) < 100";
    $order_by = "distancia ASC";

    switch($codigo_linha){
	case 1: $linhas = 265; break;
	case 2: $linhas = "263,260,327,603"; break;
	case 3: $linhas = 261; break;
    }

    $sql   = " SELECT
                    distinct tbl_posto.posto ,
                    tbl_posto_fabrica.parametros_adicionais::JSON->>'id_posto_lorenzetti' as codigo
					$coluna_distancia
              FROM tbl_posto
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
              JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha IN({$linhas})
             WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                   {$cond_distancia}
               AND tbl_posto_fabrica.posto <> 6359
               AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
               AND tbl_posto_fabrica.senha <> '*'
			order by $order_by
          LIMIT 1";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        echo pg_fetch_result($res, 0, 'codigo');
    } else {
    	echo "Posto não encontrado";
    }
