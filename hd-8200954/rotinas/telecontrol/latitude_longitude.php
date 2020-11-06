<?php

    /*
    Rotina: Atualiza localização dos Postos - Latitude e Longitude
    */

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    use Posvenda\TcMaps;

    $fabrica = $argv[1];
    $posto   = $argv[2];

    function formatCEP($cep){
        $cep = preg_replace("/[^0-9]/", "", $cep);

        $cepI = substr($cep, 0, 5);
        $cepF = substr($cep, 5);

        $cep = $cepI."-".$cepF;

        return $cep;
    }

    function acentos($string) {
        $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" ,"-");
        $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","" );
        $string = str_replace($array1, $array2, $string);

        return $string;
    }

    function verificaCidadeEstado($cidade, $estado, $posto){

        $sql = "SELECT contato_cidade, cidade_estado FROM tbl_posto_fabrica WHERE tbl_posto_fabrica = $posto AND fabrica = $fabrica AND contato_cidade = '$cidade' AND contato_estado = '$estado'";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            return true;
        }else{
            return false;
        }

    }

    function infoLocalizacao($cidade_posto, $estado_posto, $dados_google){

        foreach ($dados_google as $key => $value) {

            if(in_array("administrative_area_level_2", $value->types)){
                $cidade_google = $value->long_name;
            }

            if(in_array("administrative_area_level_1", $value->types)){
                $estado_google = $value->short_name;
            }
        }

        $cidade_google = trim(strtolower(acentos($cidade_google)));

        $cidade_posto = trim(strtolower(str_replace("+", " ", $cidade_posto)));

        $cidade_google = str_replace("'","",$cidade_google);
        $cidade_posto = str_replace("'","",$cidade_posto);

        #echo "$cidade_posto - $cidade_google / $estado_posto - $estado_google \n";

        return ($cidade_posto == $cidade_google && $estado_posto == $estado_google) ? true : false;

    }

    function searchResultType($result, $type) {
        $found = array_filter(function($data) use($type) {
            if (in_array($type, $data->types)) {
                return true;
            }
        }, $result);

        if (count($found) > 0) {
            return true;
        } else {
            return false;
        }
    }
/*
    $sql = "SELECT CURRENT_DATE - '2014-11-17'";
    $res = pg_query($con,$sql);
    $posicao = pg_fetch_result($res,0,0);

    if (!empty($argv[1])) {
        $whereFabrica = "AND fabrica = {$argv[1]}";
    }
    $dia_semana = date('N');
    switch($dia_semana){
        case '1' :$sql = "SELECT fabrica FROM tbl_fabrica WHERE ativo_fabrica {$whereFabrica} and nome !~* 'telecont' ORDER BY random()"; break;
        case '3' :$sql = "select fabrica, count(1) from tbl_posto_fabrica join tbl_fabrica using(fabrica) where latitude isnull and data_input > current_date - interval '90 days' and ativo_fabrica  and senha <> '*'  and (length(trim(contato_endereco)) > 0 or contato_cep notnull) {$whereFabrica} group by fabrica order by count(1) desc ;"; break;
        case '5' :$sql = "select fabrica, count(1) from tbl_posto_fabrica join tbl_fabrica using(fabrica) where latitude isnull and ativo_fabrica and credenciamento != 'DESCREDENCIADO' and senha <> '*'  and (length(trim(contato_endereco)) > 0 or contato_cep notnull) {$whereFabrica} group by fabrica order by count(1) desc ;"; break;
        default :$sql = "SELECT fabrica FROM tbl_fabrica WHERE ativo_fabrica {$whereFabrica} and nome !~* 'telecont' ORDER BY random()"; break;
    }
    $res = pg_query($con,$sql);

    $alta_precisao = (in_array($argv[1], array(158))) ? true : false;
*/
    
    if(pg_num_rows($res) > 0 OR 1 ==1 ){
        
	$oTcMaps = new TcMaps($fabrica, $con);
            

            if(!empty($fabrica)) {
                $cond = " AND tbl_posto_fabrica.fabrica = $fabrica ";
            }

            $cond_posto = " AND (length(trim(contato_endereco)) > 0 or contato_cep notnull)
                            AND (tbl_posto_fabrica.latitude isnull or atualizacao > current_date - interval '4 days')";

            $cond_lat   = " AND latitude ISNULL";
            
            if (!empty($posto)) {
                $cond_posto = "AND tbl_posto_fabrica.posto = $posto";
                $cond_lat   = "";
            }

            $sql = "SELECT
                tbl_posto.posto, 
                tbl_posto.nome,
                fn_retira_especiais(tbl_posto_fabrica.contato_endereco) as contato_endereco, 
                tbl_posto_fabrica.contato_numero, 
                fn_retira_especiais(tbl_posto_fabrica.contato_bairro) as contato_bairro, 
                fn_retira_especiais(tbl_posto_fabrica.contato_cidade) as contato_cidade, 
                contato_cidade as cidade,
                tbl_posto_fabrica.contato_estado,
                tbl_posto_fabrica.contato_cep, 
                tbl_posto_fabrica.latitude, 
				tbl_posto_fabrica.longitude,
			   tbl_posto_fabrica.fabrica	
            FROM tbl_posto
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN tbl_fabrica using(fabrica)
            WHERE tbl_posto_fabrica.contato_pais = 'BR' 
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_posto.pais='BR'
			AND length(contato_cep) = 8
			and ativo_fabrica
            /*AND tbl_posto_fabrica.senha <> '*'*/
            $cond_posto
            $cond ";
			$sql .= " order by " . rand(1,9) ; 
            
            $resF = pg_query($con, $sql);
            
            $cont = 0;
            
            while ($result = pg_fetch_object($resF)){

                $posto = $result->posto;
				$fabrica = $result->fabrica;
                echo $posto->nome;
                echo "\n";

                if(strlen($result->contato_endereco) > 0){
                    $endereco = trim(acentos($result->contato_endereco));
                }

                if(strlen($result->contato_numero) > 0){
                    $numero =  trim(acentos($result->contato_numero));
                }

                if(strlen($result->contato_bairro) > 0){
                    $bairro = trim(acentos($result->contato_bairro));
                }

                if(strlen($result->contato_cidade) > 0){
                        $cidade = trim(acentos($result->contato_cidade));
                }


                if(strlen($result->contato_estado) > 0){
                    $estado = trim(acentos($result->contato_estado));
                }

                if(strlen($result->contato_cep) > 0){
                    $address['cep'] = formatCEP($result->contato_cep);
                }

                $pais = "Brasil";

				$response = $oTcMaps->geocode($endereco, $numero, $bairro, $cidade, $estado, $pais, $address['cep']);

                if (!empty($response['latitude']) && !empty($response['longitude'])) {
                    $sql_update = "UPDATE tbl_posto_fabrica SET latitude = {$response['latitude']}, longitude = {$response['longitude']} WHERE posto = {$posto} AND fabrica = $fabrica;
                                    UPDATE tbl_posto set latitude = {$response['latitude']} , longitude = {$response['longitude']} where posto = $posto $cond_lat;
					    ";
                    $res_update = pg_query($con, $sql_update);

                }else{
                    echo "Cont: ".$cont++." Nao localizou - Posto: ".$posto.PHP_EOL;
                }

                echo "\n\n";

                sleep(1);

            }
    }


exit;

