<?php

namespace Posvenda;

class TcMaps
{

    private $_fabrica;
    private $_con;
    #private $_token = "pk.eyJ1IjoiYW5kZXJzb250ZWxlY29udHJvbCIsImEiOiJjajA4NXc5NTkwMDd4MndueWhtbWp3c3JlIn0.SI2BGGzVpQfwoq9Y78YqAQ";
    private $_token = "pk.eyJ1IjoiaW5mcmEiLCJhIjoiY2o1bnZzdjJuMzFyaDM2bzFvdHdvM2VicSJ9.zLKWAKTiqJO61C2UkgUIxw";

    #private $fabricasTcGeocode = [10,30 ,52, 161, 125, 169, 170];
    private $fabricasTcGeocode = [];

    public function __construct($fabrica, $con = null)
    {
        $this->_fabrica = $fabrica;
        if ($con != null) {
            $this->_con = $con;
        }

        if ($this->_fabrica == 158) {
            $this->_token = "pk.eyJ1IjoiaW1iZXJhdGNsIiwiYSI6ImNqNXV6dWZ2OTBiNGQzMnF3OGQydGc1aW4ifQ.u2Y5PB8gT7klfSZuxtySSw";
        }
    }

    public function geocode($endereco, $numero, $bairro, $cidade, $estado, $pais, $cep = null, $debug = false )
    {

        try {

            // Completo
            // Sem bairro
            // Sem numero
            // Sem endereco

            if(strpos($cidade,'DAGUA') !== false) {
                $cidade = str_replace("DAGUA","D'AGUA", $cidade);
            }
            if(strpos($cidade,'DOESTE') !== false) {
                $cidade = str_replace("DOESTE","D'OESTE", $cidade);
            }
            if (strtolower($cidade) == 'parati') {
                $cidade = 'paraty';
            }

            $distrito_retorno = $cidade;
            $cidade_retorno   = $cidade;

            $endereco = $this->retiraAcentos($endereco);
            $bairro = $this->retiraAcentos($bairro);
            $cidade = $this->retiraAcentos($cidade);
            $distrito = strtolower($cidade);
            $uf = $estado;
            $estado = $this->retiraAcentos((strlen(trim($estado)) == 2) ? $this->parseEstado(trim($estado)) : $estado);
            $pais = $this->retiraAcentos($pais);
	        $numero = preg_replace("/\D/", "", $numero);
            
            $estado_retorno = $estado;

            if ($cep){
                $cep = str_replace(".", "", $cep);
                $cep = str_replace("-", "", $cep);
            }
			$xcidade = str_replace("'","",$cidade); 
            $sql = "
                SELECT  municipio_pesquisa,
                    replace(latitude,',','.')   AS latitude,
                    replace(longitude,',','.')  AS longitude
				FROM tbl_ibge_completa
				WHERE municipio_pesquisa = '{$xcidade}'
                AND upper(uf) = '".strtoupper($uf)."'
                AND distrito_pesquisa = municipio_pesquisa
            ";
            $res = pg_query($this->_con, $sql);
            $num_rows = pg_num_rows($res);

            if ($num_rows > 0) {
                for ($i = 0; $i < $num_rows; $i++) {
                    if (strtolower($cidade) == strtolower(pg_fetch_result($res, $i, municipio_pesquisa))) {
                        unset($distrito, $distrito_retorno);
                        $latitude  = pg_fetch_result($res, 0, 'latitude');
                        $longitude = pg_fetch_result($res, 0, 'longitude');
                        break;
                    }
                }

                if (!empty($distrito)) {
                    $latitude  = pg_fetch_result($res, 0, 'latitude');
                    $longitude = pg_fetch_result($res, 0, 'longitude');
                }
            } else {

                unset($distrito, $distrito_retorno);

                $sql = "
                    SELECT  municipio_pesquisa,
                        replace(latitude,',','.')   AS latitude,
                        replace(longitude,',','.')  AS longitude
                    FROM tbl_ibge_completa
					WHERE distrito_pesquisa = '{$xcidade}'
					AND upper(uf) = '".strtoupper($uf)."'
					AND distrito_pesquisa <> municipio_pesquisa
                           ";
                $res = pg_query($this->_con, $sql);
                $num_rows = pg_num_rows($res);

                if ($num_rows > 0) {
                    $latitude  = pg_fetch_result($res, 0, 'latitude');
                    $longitude = pg_fetch_result($res, 0, 'longitude');
                }
            }
            // Tratativa para buscar no Tc Geocode, fabricas configuradas no atributo fabricasTcGeocode
            if(!in_array($this->_fabrica, $this->fabricasTcGeocode)){
                $response = $this->tcGeocode($endereco, $numero, $cidade, $uf, $cep, $debug);

                $estado_response = strtolower($response["request_information"]["state"]);
                $estado          = strtolower($estado);
                $cidade          = strtolower($cidade);
                $uf_response     = $response["request_information"]["region"];
				$cidade_response = $response["request_information"]["city"];
            	$cidade_response = mb_detect_encoding($cidade_response, 'UTF-8', true) ? utf8_decode($cidade_response) : $cidade_response;
            	$cidade_response = mb_detect_encoding($cidade_response, 'UTF-8', true) ? utf8_decode($cidade_response) : $cidade_response;
                $cidade_response = str_replace("'", "", strtolower($this->retiraAcentos($cidade_response)));
				$latlon = $response['latlon'];
				$cep_response = str_replace("-", "", $response["request_information"]["postcode"]);

                if($response != false && is_array($response)){
                    if (($cidade == $cidade_response || $distrito == $cidade_response || str_replace("'","",$cidade) == $cidade_response) || str_replace("-"," ", $cidade_response) == $cidade) {

						if (!empty($cep) and empty($latlon)) {

							if (!empty($cep_response) and substr($cep,0,5) != substr($cep_response,0,5)) {
								$response = $this->tcGeocodePostCode($cep);

								if (!$response) {
									throw new \Exception("Não foi possível buscar a geolocalização");
								} else {
									return $response;
								}
							}
						}elseif(!empty($cep) and !empty($cep_response) and substr($cep,0,5) != substr($cep_response,0,5)) {
        
								$response = $this->tcGeocodePostCode($cep);

								if (!$response) {
									throw new \Exception("Não foi possível buscar a geolocalização");
								} else {
									return $response;
								}
						}elseif(empty($cidade_response)){

							$xendereco = preg_replace('/\d+$/','',$endereco);
							$xendereco = preg_replace('/\s+$/','',$xendereco);
							$response = $this->tcGeocode($xendereco, $numero,  $cidade, $uf, $cep, $debug);

							if (!$response) {
								throw new \Exception("Não foi possível buscar a geolocalização");
							} else {
								return $response;
							}					
						}

                        return $response;
                    } else {

                        if (empty($latitude) || empty($longitude)) {
                            throw new \Exception("Não foi possível buscar a geolocalização");            
                        } else {
                            $response["latitude"]                      = $latitude;
                            $response["longitude"]                     = $longitude;
                            $response["latlon"]                        = "{$latitude},{$longitude}";
                            $response["request_information"]["region"] = $uf;
                            $response["request_information"]["state"]  = $estado_retorno;
                            $response["request_information"]["lat"]    = $latitude;
                            $response["request_information"]["lon"]    = $longitude;

                            if (!empty($distrito)) {
                                $response["request_information"]["city"] = $distrito_retorno;
                            } else {
                                $response["request_information"]["city"] = $cidade_retorno;
                            }

                            unset(
                                $response["request_information"]["street"],
                                $response["request_information"]["postcode"],
                                $response["request_information"]["neighborhood"]
                            );

                            return $response;
                        }
                    }
                }

		        throw new \Exception("Não foi possível buscar a geolocalização");
            }
            //------------------------------------

            if (strtolower($bairro) == "centro") {
                unset($bairro);
            }

            $sql = "SELECT  municipio_pesquisa,
                            replace(latitude,',','.')   AS latitude,
                            replace(longitude,',','.')  AS longitude
                    FROM    tbl_ibge_completa
                    WHERE   distrito_pesquisa = '{$cidade}'
                    AND     upper(uf) = '" . strtoupper($uf) . "'
                    AND     distrito_pesquisa <> municipio_pesquisa
            ";

            $res = pg_query($this->_con, $sql);
            $num_rows = pg_num_rows($res);
            if ($num_rows > 0) {
                for ($i = 0; $i < $num_rows; $i++) {
                    if (strtolower($cidade) == strtolower(pg_fetch_result($res, $i, municipio_pesquisa))) {
                        unset($distrito);
                        break;
                    }
                }
                if (!empty($distrito)) {
                    $cidade = pg_fetch_result($res, 0, municipio_pesquisa);
                    $LatD = pg_fetch_result($res, 0, latitude);
                    $LngD = pg_fetch_result($res, 0, longitude);
                }
            } else {
                unset($distrito);
            }

            $request = array(
                "request1" => "$endereco, $numero, $distrito, $estado, $pais",
                "request2" => "$endereco, $numero,  $cidade, $estado, $pais",
                "request5" => "$endereco, $distrito, $estado, $pais",
                "request6" => "$endereco, $cidade, $estado, $pais",
                "request9" => "$distrito, $estado, $pais",
                "request7" => "$LatD, $LngD",
                "request10" => "$cidade, $estado, $pais",
                "request8" => "$cidade, $estado, $pais",
            );

            $request = array_filter($request, function ($v) {
                $arr = explode(", ", $v);

                $args = array_filter($arr, function ($a) {
                    if (empty($a)) {
                        return false;
                    } else {
                        return true;
                    }
                });

                if (count($arr) == count($args)) {
                    return true;
                } else {
                    return false;
                }
            });

            foreach ($request as $key => $address) {
                //echo $address."<br />";

                //$relevancia_nivel = 0.8;

                if ($key == "request7") { //Retornar localizaÃ§Ã£o do distrito
                    $Latlng = explode(",", $address);
                    $retorno = array(
                        "latitude" => trim($Latlng[0]),
                        "longitude" => trim($Latlng[1]),
                        "latlon" => $address
                    );

                    return $retorno;
                }

                if (in_array($key, array("request1", "request2", "request5", "request6", "request8"))) {
                    $type = "address";
                } elseif (in_array($key, array("request9", "request10"))) {
                    $type = "place";
                }


                $geocode = file_get_contents("https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode($address) . ".json?access_token={$this->_token}&limit=1&types=$type");
                $response = json_decode($geocode, true);

                if (!count($response["features"]) && $i == count($request)) {
                    throw new \Exception("Não foi possível buscar a geolocalização");
                } else {
                    if (!count($response["features"])) {
                        continue;
                    } else {
                        //$relevance   = $response["features"][0]["relevance"];
                        $matching_place = strtolower($this->retiraAcentos($response["features"][0]["place_name"]));
                        $coordinates = $response["features"][0]["geometry"]["coordinates"];

                        $cidade = strtolower($cidade);
                        $estado = strtolower($estado);

                        $regexp = "/{$estado}\s[0-9]{1,},\s(brazil|brasil)/";
                        $matching_place_pesquisa = strtolower($this->retiraAcentos($matching_place));

                        //echo $matching_place."<br />";
                        if (preg_match($regexp, $matching_place_pesquisa)) {
                            $estado_replace = $estado . ", brazil";
                            $matching_place = preg_replace($regexp, $estado_replace, $matching_place_pesquisa);
                        }
                        //echo $matching_place."<br />";


                        if (((preg_match("/$cidade\, $estado\, brazil/", $matching_place) || (!empty($distrito) and preg_match("/$distrito\, $estado/", $matching_place))))) {

                            //echo '<pre>';
                            //print_r($response);

                            list($lng, $lat) = $coordinates;
                            $retorno = array(
                                "latitude" => trim($lat),
                                "longitude" => trim($lng),
                                "latlon" => trim($lat) . "," . trim($lng)
                            );
                        } else {
                            if ($key == 'request8') {
                                $sql = "
                                SELECT  municipio_pesquisa,
                                        replace(latitude,',','.')   AS latitude,
                                        replace(longitude,',','.')  AS longitude
                                FROM    tbl_ibge_completa
                                WHERE   distrito_pesquisa = upper('{$cidade}')
                                AND     upper(uf) = '" . strtoupper($uf) . "'";

                                $res = pg_query($this->_con, $sql);
                                if (pg_num_rows($res) > 0) {
                                    $cidade = pg_fetch_result($res, 0, municipio_pesquisa);
                                    $LatD = pg_fetch_result($res, 0, latitude);
                                    $LngD = pg_fetch_result($res, 0, longitude);
                                    $retorno = array(
                                        "latitude" => trim($LatD),
                                        "longitude" => trim($LngD),
                                        "latlon" => "$LatD, $LngD"
                                    );

                                    return $retorno;
                                }
                            }
                            continue;
                        }

                        return $retorno;
                    }

                }


            }
        } catch (\Exception $e) {
            return array("error" => $e->getMessage());
        }
    }

    public function tcGeocodePostCode($cep) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "http://api2.telecontrol.com.br/geocoder/geocoder/postcode/".$cep,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array("cache-control: no-cache")
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	if ($err) {
		return false;
	} else {
		$response = json_decode($response, true);

		if ($response["nearst_result"]) {
			$lat = $response["nearst_result"]["lat"];
			$lon = $response["nearst_result"]["lon"];

			return array(
				"latitude" => trim($lat),
				"longitude" => trim($lon),
				"latlon" => trim($lat).",".trim($lon),
				"request_information" => $response["nearst_result"]
			);
		} else {
			return false;
		}
	}
    }

    public function tcGeocode($endereco, $numero = null, $cidade = null, $uf = null, $cep = null, $debug = false){
        $params = array();

        if($endereco){
            $params['address'] = $endereco;
        }

        if($numero){
            $params['number'] = preg_replace("/(S\\N|S\/N|SN|S\|N)/", "", strtoupper($numero));
        }

        if ($cep){
            $params['postcode'] = $cep;
        }

    	if(empty($params['number'])){
    		unset($params['number']);
    	}

        if($cidade){
            $params['city'] = $cidade;
        }

        // if($distrito){
        //     $params['distrito'] = $distrito;
        // }

        if($uf){
            $params['state'] = $uf;
        }

        foreach ($params as $key => $value) {
            $url .= "/".$key."/".urlencode(preg_replace("/(\\|\/)/", "", $value));
        }

        $curl = curl_init();

	if ($debug === true) {
		echo "http://api2.telecontrol.com.br/geocoder/geocoder".$url;
	}

        curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/geocoder/geocoder".$url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {

        } else {
            $response = json_decode($response, true);

            if(array_key_exists("nearst_result", $response)){
                if($estado != null && strtoupper($response['nearst_result']['region']) != strtoupper($estado)){
                    return false;
                }

                $lat = $response['nearst_result']['lat'];
                $lon = $response['nearst_result']['lon'];


                $retorno = array(
                    "latitude" => trim($lat),
                    "longitude" => trim($lon),
                    "latlon" => trim($lat) . "," . trim($lon),
                    "request_information" => $response['nearst_result']
                );

                return $retorno;

            }
        }

        return false;
    }


    public function route($lat_lng_origem=null, $lat_lng_destino=null)
    {
        try {
            $lat_lng_origem = explode(',', $lat_lng_origem);
            $lat_lng_origem = $lat_lng_origem[1] . ',' . $lat_lng_origem[0];
            $lat_lng_destino = explode(',', $lat_lng_destino);
            $lat_lng_destino = $lat_lng_destino[1] . ',' . $lat_lng_destino[0];

            //$router   = file_get_contents("https://api.mapbox.com/directions/v5/mapbox/driving/" . $lat_lng_origem . ";" . $lat_lng_destino . "?overview=full&steps=true&geometries=polyline&access_token={$this->_token}");
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://maps.telecontrol.com.br:5000/route/v1/driving/" . $lat_lng_origem . ";" . $lat_lng_destino . "?overview=full&steps=true&geometries=polyline");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            $response = curl_exec($ch);
            $response = json_decode($response, true);
            curl_close($ch);

            if (isset($response["exception"]) || count($response["routes"]) == 0) {
                throw new \Exception("NÃ£o foi possÃ­vel gerar a rota.");

            } else {
                $totalKM = number_format(($response['routes'][0]['distance']) / 1000, 2, ".", "");

                $retorno = array(
                    "total_km" => $totalKM,
                    "rota" => $response
                );

                return $retorno;
            }

        } catch (\Exception $e) {
            return array("error" => $e->getMessage());
        }

    }

    public function near($latlon, $range = 100, $limit = 5)
    {

        try {
            //anderson adicionar os seguintes parametros | fabrica | linha | cidade | estado
            $near = file_get_contents("https://api2.telecontrol.com.br/maps/closestService/coordinates/{$latlon['latitude']},{$latlon['longitude']}/range/{$range}");
            $response = json_decode($near, true);

            if (isset($response["exception"]) || count($response[0]["results"]) == 0) {

                throw new \Exception($response["exception"]);

            } else {

                $result = $response[0]["results"];
                $dadospostos = array();

                for ($i = 0; $i < $limit; $i++) {
                    $dadospostos = $this->getPosto($result[$i]["posto"]);
                    $result[$i]["posto"] = $dadospostos;
                }

                return $result;
            }

        } catch (\Exception $e) {
            return array("error" => $e->getMessage());
        }

    }

    private function getPosto($posto)
    {

        if (empty($posto)) {
            return array();
        }

        $sql = "SELECT
                    tbl_posto.nome,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.contato_endereco,
                    tbl_posto_fabrica.contato_numero,
                    tbl_posto_fabrica.contato_complemento,
                    tbl_posto_fabrica.contato_cep,
                    tbl_posto_fabrica.contato_bairro,
                    tbl_posto_fabrica.contato_cidade,
                    tbl_posto_fabrica.contato_estado,
                    tbl_posto_fabrica.contato_email,
                    tbl_posto_fabrica.contato_fone_comercial,
                    tbl_posto_fabrica.latitude,
                    tbl_posto_fabrica.longitude
                  FROM tbl_posto
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto=tbl_posto.posto
                 WHERE tbl_posto_fabrica.posto={$posto}
                   AND tbl_posto_fabrica.fabrica={$this->_fabrica}";
        $res = pg_query($this->_conn, $sql);

        return pg_fetch_assoc($res);
    }


    private function parseEstado($uf)
    {

        $array_estados = array(
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AM' => 'Amazonas',
            'AP' => 'AmapÃ¡',
            'BA' => 'Bahia',
            'CE' => 'CearÃ¡',
            'DF' => 'Distrito Federal',
            'ES' => 'EspÃ­rito Santo',
            'GO' => 'GoiÃ¡s',
            'MA' => 'MaranhÃ£o',
            'MG' => 'Minas Gerais',
            'MS' => 'Mato Grosso do Sul',
            'MT' => 'Mato Grosso',
            'PA' => 'ParÃ¡',
            'PB' => 'ParaÃ­ba',
            'PE' => 'Pernambuco',
            'PI' => 'PiauÃ­',
            'PR' => 'ParanÃ¡',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RO' => 'RondÃ´nia',
            'RR' => 'Roraima',
            'RS' => 'Rio Grande do Sul',
            'SC' => 'Santa Catarina',
            'SE' => 'Sergipe',
            'SP' => 'SÃ£o Paulo',
            'TO' => 'Tocantins'
        );

        return $array_estados[$uf];
    }


    private function retiraAcentos($texto)
    {
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@", "'" );
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C", "_", "_", "_", "_", "_", "_");

        return str_replace($array1, $array2, $texto);
    }

}
