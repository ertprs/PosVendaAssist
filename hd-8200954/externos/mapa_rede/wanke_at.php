<?
    include "../../dbconfig.php";
    include "/var/www/includes/dbconnect-inc.php";
    include "../../funcoes.php";
    include 'mlg_funciones.php';

    // if ($_GET["ajax_rota"]) {
    //     include "../../funcoes.php";

    //     $origem  = $_GET["origem"];
    //     $destino = $_GET["destino"];

    //     $rota = googleMapsGeraRota($origem, $destino);

    //     exit(json_encode($rota));
    // }

    $html_titulo = "Telecontrol - Mapa da Rede Autorizada";

    $array_estados = $array_estados();

    $fabrica = 91;
    $login_fabrica = 91;

    use Posvenda\TcMaps;
    $oTCMaps = new TcMaps($login_fabrica,$con);

    if ($_GET["ajax_rota"]) {

        $origem  = $_GET["origem"];
        $destino = $_GET["destino"];        

        $resposta = $oTCMaps->route($origem,$destino);

        echo json_encode($resposta);
        exit;
    }

    $buscaAjax = $_POST['buscaAjax'];

    $estados = "'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'";

    if($buscaAjax == "estados"){

        $sql = "
            SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_estado) AS estado
            FROM tbl_produto
            JOIN tbl_posto_linha   ON tbl_posto_linha.linha     = tbl_produto.linha
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_fabrica.fabrica  = $fabrica
            WHERE tbl_produto.fabrica_i = $fabrica
            AND   tbl_produto.ativo
            AND tbl_posto_linha.linha != 901
            AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
            AND tbl_posto_fabrica.contato_estado IS NOT NULL
            ORDER BY estado;";

        $res = pg_exec($con,$sql);
        if (pg_numrows ($res) > 0) {
            echo "<option value='' selected >Selecione</option>";
            for ($i=0; $i<pg_numrows ($res); $i++ ){
                $estado = pg_result($res,$i,'estado');

                echo "<option value='$estado'> $estado</option>";
            }
        }else{
            echo "<option value='0'> Nenhum estado encontrado para esta famÌlia.</option>";
        }
        exit;
    }

    if($buscaAjax == "cidades"){

        $estado = $_POST['estado'];

        if ($estado == "") exit("<OPTION SELECTED>Sem resultados</OPTION>");

        if(strlen($estado) > 0) {
            $tot_i = false;
            $sql_cidades =  "SELECT  LOWER(mlg_cidade)||'#('||count(mlg_cidade)||')' AS cidade
                                FROM (
                                    SELECT tbl_posto_fabrica.posto,
                                            tipo_posto,
                                            UPPER(TRIM(TRANSLATE(tbl_cidade.nome,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«-','aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC '))) AS mlg_cidade,
                                            tbl_cidade.estado  AS mlg_estado
                                    FROM tbl_posto_fabrica
                                    JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
                                    AND tbl_posto_fabrica_ibge.fabrica = {$fabrica}
                                    JOIN tbl_cidade ON tbl_posto_fabrica_ibge.cidade = tbl_cidade.cidade
                                    AND tbl_cidade.estado = '{$estado}'
                                    WHERE credenciamento ='CREDENCIADO'
                                    AND tbl_posto_fabrica.posto NOT IN(6359)
                                    AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                                    AND tbl_posto_fabrica.tipo_posto <> 268
                                    AND tbl_posto_fabrica.fabrica=$fabrica
                                ) mlg_posto
                                GROUP BY mlg_posto.mlg_cidade ORDER BY cidade ASC";
            $res_cidades = pg_query($con,$sql_cidades);
            if (is_resource($res_cidades)) {
                $tot_i       = pg_num_rows($res_cidades);
                if ($tot_i == 0) exit("<OPTION SELECTED>Sem resultados</OPTION>");

                $cidades     = pg_fetch_all($res_cidades);
                if ($tot_i) echo "<option></option>";

                foreach($cidades as $info_cidade) {
                    list($cidade_i,$cidade_c) = preg_split('/#/',htmlentities($info_cidade['cidade']));
                    $sel      = (strtoupper($cidade) == strtoupper($cidade_i))?" SELECTED":"";
                    echo "\t\t\t<OPTION value='$cidade_i'$sel>".ucwords($cidade_i." ".$cidade_c)."</OPTION>\n";
                }
            } else {
                if ($debug) pre_echo($sql_cidades, "Resultado: $tot_i registro(s)");
                exit('KO|Erro ao acessar o Sistema Telecontrol.');
            }
        }
        exit;
    }

    // function getLatLonConsumidor($address,$cep){     

    //     $geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$address.',brasil&sensor=false');

    //     $output = json_decode($geocode);
    //     $resultado = (array) $output;
    //     foreach ($resultado as $key => $value){
    //         $resultado[$key] = (array)$value;
    //         foreach ($value as $key2 => $value2){
    //             $value[$key2] = (array)$value2;
    //         }
    //         $resultado[$key] = (array)$value;
    //     }
    //     $cidade = $resultado[results][0][address_components][3]->short_name;
    //     if(strlen(trim($cep))>0 and $cep != "undefined"){
    //         if(strpos($address,$cidade) ===false) {
    //             $geocode = file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?address='.$cep.',brasil&sensor=false');
    //             $output = json_decode($geocode);
    //         }
    //     }

    //     $lat = $output->results[0]->geometry->location->lat;
    //     $lon = $output->results[0]->geometry->location->lng;

    //     $latLon = $lat."@".$lon;
    //     return $latLon;

    // }

    if($buscaAjax == "assistencia"){

        $estado     = $_POST['estado'];
        $endereco   = $_POST['endereco'];
        $cep        = $_POST['cep'];
        $cidade     = utf8_decode($_POST['cidade']);

        $pais = 'Brasil';

        if(strlen(trim($endereco)) > 0 or (strlen(trim($endereco)) == 0 and strlen($cidade) > 0) ){

            $endereco = explode(', ', $endereco);
            $contax = count($endereco);
            #$estado = trim($endereco[3]);
            #$cidade = trim($endereco[2]);
            $bairro = trim($endereco[1]);
            $endereco = trim($endereco[0]);
            if($contax == 2) {
                $cidade  = $endereco;
                $estado = $bairro;
            }
            $latLon = $oTCMaps->geocode($endereco, null, $bairro, $cidade, $estado, $pais, $cep);

            $lat = $latLon['latitude'];
            $lon = $latLon['longitude'];
        }

        //$latLon = getLatLonConsumidor($endereco, $cep);

        // $parte = explode('@', $latLon);
        // $lat = $parte[0];
        // $lon = $parte[1];
        $latLonStr = $lat.'@'.$lon;

        $order = " nome ";
        if(strlen($cep) > 2 or (!empty($lat) and !empty($lon))) {
            if(!empty($lat) and !empty($lon)) {
                //$latLon = getLatLonConsumidor($endereco, $cep);
                //$latLon = $oTCMaps->geocode(null, null, null, $cidade, $estado, $pais);
                //echo $endereco." == endereco ".$bairro." == bairro ".$cidade." == cidade ".$estado." == estado".$pais." == pais";exit;
                $latLonStr = $lat.'@'.$lon;
                $lat = substr(trim($lat),0,7);
                $lon = substr(trim($lon),0,7);
                $distancia = ($cidade == 'bage') ? '300.0' : '50.0';
                $campo = ",  (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) AS distance ";
                $order = " distance";
                $cond = "AND (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) <= $distancia";
            }

            $pesquisaPorLatitude = true;

/*  
            if (strlen($cep) > 0) {
                $limit = "LIMIT 8";
            }else{
                $cond = "AND tbl_posto_fabrica.contato_estado = '$estado' AND upper(tbl_posto_fabrica.contato_cidade) = upper('$cidade')";
        }
*/
        }else{
            $cond = " AND tbl_posto_fabrica.contato_estado = '$estado'
                      AND UPPER(TRIM(TRANSLATE(cidade_atende.nome,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«-',
                                                              'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC ')))
                                = UPPER('".tira_acentos($cidade)."')
                       ";
            $order = " nome ";
        }


        $sql = "
            SELECT DISTINCT UPPER(tbl_posto.nome) AS nome, tbl_posto_fabrica.nome_fantasia, tbl_posto.posto, tbl_posto_fabrica.contato_endereco, tbl_posto_fabrica.contato_numero,
                   tbl_posto_fabrica.contato_bairro, tbl_posto_fabrica.contato_fone_comercial, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.obs_conta, tbl_posto_fabrica.parametros_adicionais, 
                   tbl_posto_fabrica.contato_email,
                        tbl_posto_fabrica.latitude,
                        tbl_posto_fabrica.longitude
                    $campo

              FROM tbl_posto_fabrica
              JOIN tbl_posto        ON tbl_posto_fabrica.posto   = tbl_posto.posto
                                   AND tbl_posto_fabrica.fabrica = $fabrica

              JOIN tbl_cidade cidade_atende ON UPPER(TRIM(TRANSLATE(cidade_atende.nome,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«-',
                                                              'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC ')))
                                = UPPER('".tira_acentos($cidade)."')
              AND cidade_atende.estado = '{$estado}'

              JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
              AND tbl_posto_fabrica_ibge.fabrica = {$fabrica}
              AND tbl_posto_fabrica_ibge.cidade = cidade_atende.cidade

             WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
             AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
            AND tbl_posto_fabrica.posto      NOT IN(6359,4311)
            AND tbl_posto_fabrica.tipo_posto <> 268
        {$cond}
        ORDER BY {$order}
        limit 10";

        $res = pg_exec($con,$sql);

        if ($pesquisaPorLatitude && pg_num_rows($res) == 0) {
            $sql = "
                SELECT DISTINCT UPPER(tbl_posto.nome) AS nome, tbl_posto_fabrica.nome_fantasia, tbl_posto.posto, tbl_posto_fabrica.contato_endereco, tbl_posto_fabrica.contato_numero,
                       tbl_posto_fabrica.contato_bairro, tbl_posto_fabrica.contato_fone_comercial, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.obs_conta, tbl_posto_fabrica.parametros_adicionais,
                       tbl_posto_fabrica.contato_email, 
                            tbl_posto_fabrica.latitude,
                            tbl_posto_fabrica.longitude
                        $campo

                  FROM tbl_posto_fabrica
                  JOIN tbl_posto        ON tbl_posto_fabrica.posto   = tbl_posto.posto
                                       AND tbl_posto_fabrica.fabrica = $fabrica

                  JOIN tbl_cidade cidade_atende ON UPPER(TRIM(TRANSLATE(cidade_atende.nome,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«-',
                                                              'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC ')))
                                = UPPER('".tira_acentos($cidade)."')
                  AND cidade_atende.estado = '{$estado}'

                  JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
                  AND tbl_posto_fabrica_ibge.fabrica = {$fabrica}
                  AND tbl_posto_fabrica_ibge.cidade = cidade_atende.cidade

                 WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                 AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                AND tbl_posto_fabrica.posto      NOT IN(6359,4311)
                AND tbl_posto_fabrica.tipo_posto <> 268
               AND (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) <= 100.0
            ORDER BY {$order}
            limit 10";

            $res = pg_exec($con,$sql);

            if(pg_num_rows($res) == 0){
                $sql = "
                SELECT DISTINCT UPPER(tbl_posto.nome) AS nome, tbl_posto_fabrica.nome_fantasia, tbl_posto.posto, tbl_posto_fabrica.contato_endereco, tbl_posto_fabrica.contato_numero,
                       tbl_posto_fabrica.contato_bairro, tbl_posto_fabrica.contato_fone_comercial, tbl_posto_fabrica.contato_cidade, tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.obs_conta, tbl_posto_fabrica.parametros_adicionais,
                       tbl_posto_fabrica.contato_email, 
                            tbl_posto_fabrica.latitude,
                            tbl_posto_fabrica.longitude
                        $campo

                  FROM tbl_posto_fabrica
                  JOIN tbl_posto        ON tbl_posto_fabrica.posto   = tbl_posto.posto
                                       AND tbl_posto_fabrica.fabrica = $fabrica

                  JOIN tbl_cidade cidade_atende ON UPPER(cidade_atende.nome)
                                = UPPER('".($cidade)."')
                  AND cidade_atende.estado = '{$estado}'

                  JOIN tbl_posto_fabrica_ibge ON tbl_posto_fabrica.posto = tbl_posto_fabrica_ibge.posto
                  AND tbl_posto_fabrica_ibge.fabrica = {$fabrica}
                  AND tbl_posto_fabrica_ibge.cidade = cidade_atende.cidade

                 WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                 AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
                AND tbl_posto_fabrica.posto      NOT IN(6359,4311)
                AND tbl_posto_fabrica.tipo_posto <> 268
               AND (111.045 * DEGREES(ACOS(COS(RADIANS($lat)) * COS(RADIANS(tbl_posto_fabrica.latitude)) * COS(RADIANS(tbl_posto_fabrica.longitude) - RADIANS($lon)) + SIN(RADIANS($lat)) * SIN(RADIANS(tbl_posto_fabrica.latitude))))) <= 300.0
            ORDER BY {$order}
            limit 10";

            $res = pg_exec($con,$sql);
            }
        }

       //echo $sql;

        if (pg_numrows ($res) > 0) {
            for ($i=0; $i < pg_numrows ($res); $i++ ){
                $nome     = utf8_encode(pg_result($res,$i,'nome'));
                $nome_fantasia = utf8_encode(pg_result($res,$i,'nome_fantasia'));
                $endereco = utf8_encode(pg_result($res,$i,'contato_endereco'));
                $numero   = utf8_encode(pg_result($res,$i,'contato_numero'));
                $fone     = utf8_encode(pg_result($res,$i,'contato_fone_comercial'));
                $bairro   = utf8_encode(pg_result($res,$i,'contato_bairro'));
                $latitude   = pg_result($res,$i,'latitude');
                $longitude   = pg_result($res,$i,'longitude');
                $obs_conta   = pg_result($res,$i,'obs_conta');
                $cidade_posto = utf8_encode(pg_result($res,$i,'contato_cidade'));
                $estado       = pg_fetch_result($res, $i, 'contato_estado');
                $posto = pg_fetch_result($res, $i, 'posto');
                $distancia = pg_fetch_result($res, $i, 'distance');
                $contato_email = pg_fetch_result($res, $i, "contato_email");

                // $obs_conta = (strlen($obs_conta) > 0) ? "<br /> <strong>ObservaÁ„o:</strong> <br /> $obs_conta <br />" : "";

                $parametros_adicionais   = pg_result($res,$i,'parametros_adicionais');

                if(strlen($parametros_adicionais) > 0){
                    $parametros_adicionais = str_replace("\\n", "<br>", $parametros_adicionais);
                    $obs = json_decode($parametros_adicionais, true);
                    $obs_conta = (strlen($obs["obs_cadence"]) > 0) ? str_replace("\\", "", $obs["obs_cadence"]) : "";
                }else{
                    $obs_conta = "N/I";
                }

                 $sql_cidades_atende = "SELECT
                            tbl_posto_fabrica_ibge.posto_fabrica_ibge,
                            tbl_cidade.nome AS cidade,
                            tbl_cidade.estado,
                            tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo,
                            tbl_posto_fabrica_ibge.km,
                            tbl_posto_fabrica_ibge.bairro
                        FROM tbl_posto_fabrica_ibge
                        INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
                        WHERE tbl_posto_fabrica_ibge.fabrica = {$fabrica}
                        AND tbl_posto_fabrica_ibge.posto = {$posto}";
                $res_cidades_atende = pg_query($con, $sql_cidades_atende);
                $rows = pg_num_rows($res_cidades_atende);

                $cidades_atende = "";

                if ($rows > 0) {
                    for ($j = 0; $j < $rows; $j++) {

                        $posto_fabrica_ibge      = pg_fetch_result($res_cidades_atende, $j, "posto_fabrica_ibge");
                        $cidade                  = pg_fetch_result($res_cidades_atende, $j, "cidade");
                        $estado_atende           = pg_fetch_result($res_cidades_atende, $j, "estado");
                        $posto_fabrica_ibge_tipo = pg_fetch_result($res_cidades_atende, $j, "posto_fabrica_ibge_tipo");
                        $tipo_nome               = pg_fetch_result($res_cidades_atende, $j, "tipo_nome");
                        $km                      = pg_fetch_result($res_cidades_atende, $j, "km");
                        $bairros                 = json_decode(pg_fetch_result($res_cidades_atende, $j, "bairro"), true);

                        $cidades_atende .= " ".$cidade." / ".$estado_atende;
                        if (count($bairros) > 0) {
                            $cidades_atende .= " - Bairro(s) ";
                            $k = 0;
                            foreach ($bairros as $bairro) {
                                if (!strlen($bairro)) {
                                    continue;
                                }

                                $bairro = strtoupper(utf8_decode($bairro));
                                $cidades_atende .= $bairro;
                                $cidades_atende .= ($k++ < count($bairros) - 1) ? ", " : ""; 
                            }
                        }
                        $cidades_atende .= ",";

                    }
                }
           

                    $postoDados[] = array('nome_fantasia' => "$nome_fantasia", "nome" => $nome, 'latitude' => "$latitude", 'longitude' => "$longitude", "endereco" => "$endereco", 'numero' => $numero, "bairro" => "$bairro", "cidade_posto" => "$cidade_posto", "estado" => "$estado", "fone" => "$fone", "obs" => "$obs_conta", "cidades_atende" => $cidades_atende, "distancia" => number_format($distancia, 2), "email" => $contato_email);
            
            }

            echo json_encode(array("posto" => $postoDados, "consumidor" => $latLonStr));
            
        }
        exit;
    }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
    <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <style>
                html, body, #wrap { height:100%; }
                body { background:#fff; color:#a2acac; font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; font-size:13px; box-sizing: border-box;}
                p { line-height:18px; }
                p a { color:#fa8e07; text-decoration:none; }
                h2 { margin-bottom:5px; float:left; font-size:18px; color:#f98a05; font-family:'MyriadProSemibold', Verdana, Geneva, sans-serif; width:100%; }
                h1 { color:#9ba6a6; font-size:32px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif; padding:20px 20px 10px; }
                .box_content { padding:0 20px 20px; width:789px; }

                .clear { clear:both; }
                h1 { color:#9ba6a6; font-size:32px; font-family:'MyriadProBold',Verdana, Geneva, sans-serif;  padding: 10px; margin: 0;}
                .box_content { margin: 0 auto; padding:0 20px 20px; width:80%; }
        
                #formAssistencia { float:left; width:100%; margin:25px 0; }
                #cep {
                    width: 93%;
                }
                select, input {  
                    border: 1px solid #ACACAC; 
                    width: 334px; 
                    color: #a2acac; 
                    margin-top: 5px;
                    margin-bottom: 25px;
                    padding: 10px; 
                    font-size: 13px;
                    background-color: #ffffff;
                    border-radius: 5px;
                }
                #resultado { float:left; width:100%; margin-top:25px; }
                #resultado h2 { color:#828f8f }
                #consultar{
                 border:1px solid #bdc4c4; 
                    width:107px; 
                    height:29px; 
                    margin:5px 0 15px; 
                    color:#a2acac; 
                    font-family:'MyriadProRegular', Verdana, Geneva, sans-serif; 
                    padding:2px;    
                }
                #resultado span { margin:10px 0; 
                    display: block;
                    /*color: #FCFCFC;*/
                }

                div.alert {
                    background-color: #d9edf7;
                    color: #31708f;
                    font-weight: bold;
                    border: 1px solid #bce8f1;
                    padding: 15px;
                    border-radius: 4px;
                    font-size: 12px;
                    margin-bottom: 20px;
                }

                div.calculo-rota {
                    margin-top: 20px;
                    display: none;
                }

                .btn-instrucao-rota {
                    position: absolute;
                    margin-top: 10px;
                    margin-left: 10px;
                    cursor: pointer;
                    z-index: 1;
                }

                div.instrucao-rota {
                    position: absolute;
                    display: none;
                    z-index: 2;
                    background-color: #fff;
                    overflow-y: scroll;
                }

                .btn-fechar-instrucao {
                    float: right;
                    display: block;
                    margin-top: -30px;
                }

                span.localizar, span.localizar-todos{
                    cursor: pointer;
                    color: #a2acac;
                    text-decoration: underline;
                    display: inline;
                }
                
                span.rota{
                    cursor: pointer;
                    color: #a2acac;
                    text-decoration: underline;
                    display: inline;
                }

                .cor_hr{
                    border-color: #eeeeee;
                    
                }
                .asterisco{
                    color:#f12a30; 
                }
                .txt_titulo_principal{
                    color:#f12a30; 
                    font-size:28px; 
                    font-family:'MyriadProBold',Verdana, Geneva, sans-serif;
                    text-align: center;
                }
                .txt_subtitulo_principal{
                    color: #989898;
                    font-size: 14px;
                    text-align: center;
                }
                .txt_campos_obg{
                    color:#f12a30; 
                    font-size:16px; 
                    font-weight: bold;
                    display: block;
                    width: 100%;
                    margin-bottom: 20px
                }
                .txt_label{
                    color: #989898;
                    font-size: 16px;
                }
                
                .box_all{
                    width: 100%;
                    margin: 0 auto;
                }
                
                .btn_pesquisar{
                    border: solid 1px #E57622;
                    background: #f12a30;
                    color: #ffffff;
                    font-size: 13px;
                    font-weight: bold;
                    padding: 10px 35px;
                    text-align: center;
                    cursor: pointer;
                    border-radius: 5px;
                }
                .btn_pesquisar:hover{
                    border: solid 1px #f12a30;
                    background: #E57622;
                    color: #ffffff;
                    font-size: 13px;
                    font-weight: bold;
                    padding: 10px 35px;
                    text-align: center;
                    cursor: pointer;
                    border-radius: 5px;
                }
                .box_50_left{
                    margin: auto;
                    width: 30%;
                }
                /*
                *.box_50_right {
                *    width: 66%;
                *   float: left;
                *    margin-left: 20px;
                *}
                */

                .lista-postos {
                    overflow-x: hidden !important; 
                    overflow-y: visible !important; 
                    width: 80%;
                    min-width: 300px;
                    margin: auto; 
                    max-height: 500px !important;
                }

                .control-group {
                    width: 100%;
                }

                .form-input {
                    display: block;
                    width: 100%;
                }

                @media only all and (max-width: 838px) {
                    .box_50_left { width: 100% !important; margin: auto !important;}
                    .box_50_right { display:block; width:100%; float:none; overflow-y: visible !important; }
                    #box_mapa { display:none !important; }
                    .btn_pesquisar.localizar { display:none; }
                    #cep { width:96%; }
                }

                @media only all and (max-width: 500px) {
                    #cep { width:94%; }
                }
            </style>
        <body>

        <h1 class="txt_titulo_principal">ServiÁo TÈcnico Autorizado</h1>
        <p class="txt_subtitulo_principal">Para levar qualidade e praticidade aos lares de todo o Brasil,<br /> a Wanke conta com uma ampla rede de assistÍncia tÈcnica, <br /> que oferece suporte e atendimento exclusivo ao cliente.<br /> Selecione seu estado e cidade e saiba onde tem uma assistÍncia perto de vocÍ.<br/>
        <div class="box_content">
            <div class="box_all">
                <!-- HD-7084357
                <div class="box_50_left">
                    <div id="map-brazil">
                        <ul class="brazil">
                            <li id="AC" class="br1"><a href="#acre">Acre</a></li>
                            <li id="AL" class="br2"><a href="#alagoas">Alagoas</a></li>
                            <li id="AP" class="br3"><a href="#amapa">Amap·</a></li>
                            <li id="AM" class="br4"><a href="#amazonas">Amazonas</a></li>
                            <li id="BA" class="br5"><a href="#bahia">Bahia</a></li>
                            <li id="CE" class="br6"><a href="#ceara">Cear·</a></li>
                            <li id="DF" class="br7"><a href="#distrito-federal">Distrito Federal</a></li>
                            <li id="ES" class="br8"><a href="#espirito-santo">EspÌrito Santo</a></li>
                            <li id="GO" class="br9"><a href="#goias">Goi·s</a></li>
                            <li id="MA" class="br10"><a href="#maranhao">Maranh„o</a></li>
                            <li id="MT" class="br11"><a href="#mato-grosso">Mato Grosso</a></li>
                            <li id="MS" class="br12"><a href="#mato-grosso-do-sul">Mato Grosso do Sul</a></li>
                            <li id="MG" class="br13"><a href="#minas-gerais">Minas Gerais</a></li>
                            <li id="PA" class="br14"><a href="#para">Par·</a></li>
                            <li id="PB" class="br15"><a href="#paraiba">ParaÌba</a></li>
                            <li id="PR" class="br16"><a href="#parana">Paran·</a></li>
                            <li id="PE" class="br17"><a href="#pernambuco">Pernambuco</a></li>
                            <li id="PI" class="br18"><a href="#piaui">PiauÌ</a></li>
                            <li id="RJ" class="br19"><a href="#rio-de-janeiro">Rio de Janeiro</a></li>
                            <li id="RN" class="br20"><a href="#rio-grande-do-norte">Rio Grande do Norte</a></li>
                            <li id="RS" class="br21"><a href="#rio-grande-do-sul">Rio Grande do Sul</a></li>
                            <li id="RO" class="br22"><a href="#rondonia">RondÙnia</a></li>
                            <li id="RR" class="br23"><a href="#roraima">Roraima</a></li>
                            <li id="SC" class="br24"><a href="#santa-catarina">Santa Catarina</a></li>
                            <li id="SP" class="br25"><a href="#sao-paulo">S„o Paulo</a></li>
                            <li id="SE" class="br26"><a href="#sergipe">Sergipe</a></li>
                            <li id="TO" class="br27"><a href="#tocantins">Tocantins</a></li>
                        </ul>
                    </div>
                </div> 
                --> 
                <div class="carrouselWrapper">
                    <div class="carrousel">
                        <div class="item active" id="formulario">
                            <div class="box_50_left">
                                <form id="formAssistencia">
                                    <label class="txt_campos_obg">* Campos obrigatÛrios</label>
                                    <div class="control-group">
                                        <label class="txt_label" for="cep">CEP:</label>
                                        <input class="form-input" id="cep" name="cep" type="tel" value=""/>
                                    </div>
                                    <div class="control-group">
                                        <label class="txt_label" for="estado"><span class='asterisco'>*</span> Estado:</label>
                                        <select class="form-input" id="estado" name="estado" onchange="buscaCidade(this.value)">
                                            <option value="" >Selecione</option>
                                            <?php
                                            #O $array_estados est· no arquivo funcoes.php
                                            foreach ($array_estados as $sigla => $nome_estado) {
                                                $selected = ($sigla == $consumidor_estado) ? "selected" : "";

                                                echo "<option value='{$sigla}' {$selected} >" . $nome_estado . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="control-group">
                                        <label class="txt_label" for='cidade'><span class='asterisco'>*</span> Cidade:</label>
                                        <select class="form-input" name="cidade" id="cidade">
                                            <option></option>
                                        </select>
                                    </div>
                                    <div class="control-group">
                                        <label class="txt_label" for="endereco">EndereÁo:</label>
                                        <input class="form-input" id="endereco" name="endereco" type="tel" value=""/>
                                    </div>
                                    <div class="control-group">
                                        <button type="button"  onclick="buscaAssistencia($('#cidade').val())" class="btn_pesquisar"><i class="fa fa-search"></i> Pesquisar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="item" id="lista-posto">
                             <div class="box_50_right">
                                <div id="resultado" >
                                    <div style="width:100%; margin-bottom:30px;" id="nova-pesquisa">
                                        <button style='display: none;' type='hidden' class="btn btn_pesquisar" id='pesquisar-novamente'>Pesquisar novamente</button>
                                    </div>
                                    <div id="assistencia" class="lista-postos"></div>
                                </div>

                               
                               <div id="box_mapa">
                                    <div class="alert calculo-rota" >Calculando rota aguarde...</div>
                                    <div id="map_canvas" style="height: 375px; margin-left: 30px; margin-top: 20px; border: 1px solid #CCCCCC;"></div>
                                    <div style="text-align: right;margin-top: 20px" >
                                        <button type="button" class="btn_pesquisar localizar-todos" onclick="markers.focus(true);" ><i class="fa fa-map-marker"></i> Mostrar todos os Postos</button>
                                    </div>
                                </div> 
                                
                            </div>
                        </div>
                    </div>
                </div>

                
                </div>
               
            </div>
            <div id="resultado" >
                <div id="assistencia" style="width:100%; height:300px; overflow:auto;"></div>
            </div>
            </div>
            <div class="clear">&nbsp;</div>  

            
        </div>

        <script src="https://use.fontawesome.com/a1911bb13f.js"></script>

        <script language="JavaScript" src="../../js/jquery-1.3.2.js"></script>
        <script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
        <link rel="stylesheet/less" type="text/css" media="screen,projection" href="../cssmap_brazil_v4_4/cssmap-brazil/cssmap-brasil.less" />

        <script src="../cssmap_brazil_v4_4/cssmap-brazil/less-1.3.0.min.js"></script>
        <script type="text/javascript" src="../cssmap_brazil_v4_4/jquery.cssmap.js"></script>
        <script src='../../plugins/jquery.maskedinput_new.js'></script>

        <!-- plugin para o MapTC -->
        <link href="../../plugins/leaflet/leaflet.css" rel="stylesheet" type="text/css" />
        <script src="../../plugins/leaflet/leaflet.js" ></script>       
        <script src="../../plugins/leaflet/map.js" ></script>
        <script src="../../plugins/mapbox/geocoder.js"></script>
        <script src="../../plugins/mapbox/polyline.js"></script>


            <script language="JavaScript">
                $(window).load(function () {
                    less.modifyVars({'@map_500':'transparent url(\'../cssmap-brazil/br-500-cadence-laranja.png\') no-repeat -1010px 0'});
                });
                $(function(){
                    $("#cep").mask("99999-999")
                    $("#box_mapa").hide();
                    /*
                    * Evento para buscar o endereÁo do cep digitado
                    */
                    $("#cep").blur(function() {
                        busca_cep($("#cep").val());                        
                    });

                    $(document).on("click", "button.localizar", function() {
                        var lat = $(this).data("lat");
                        var lng = $(this).data("lng");

                        map.setView(lat, lng, 25);
                        map.scrollToMap();

                    });

                    $(document).on("click", "button.rota", function() {
                        var lat = $(this).data("lat");
                        var lng = $(this).data("lng");
                        var c   = $(this).data("consumidor");

                        if ($(".box_50_left").css("display") == "none") {
                            if ((navigator.platform.indexOf("iPhone") != -1) || (navigator.platform.indexOf("iPad") != -1) || (navigator.platform.indexOf("iPod") != -1))
                                window.open("maps://maps.google.com/maps?daddr=" + lat + "," + lng + "&ll=");
                            else
                                window.open("https://maps.google.com/maps?daddr=" + lat + "," + lng + "&ll=");
                        } else {

                            rota(lat, lng, c);
                            
                        }
                    });

                    $(document).on("click", ".btn-instrucao-rota", function() {
                        $("div.instrucao-rota").show();
                        $("div.instrucao-rota").width($("#map_canvas").width());
                        $("div.instrucao-rota").height($("#map_canvas").height());
                    });

                    $(document).on("click", ".btn-fechar-instrucao", function() {
                        $("div.instrucao-rota").hide();
                    });
                    
                    $("#cep").on("change", function() {
                        var v = $(this) .val();
                        
                        if (v.length == 0) {
                            endereco = {};
                        }
                    });
                    $('#map-brazil').cssMap({
                        'size' :  500,
                        onClick : function(e){
                            var uf = e[0].id;
                            $('#estado').val(uf);
                            $('#estado').change();
                        },
                    });
                });


                //TcMaps
                //var geocoder, latlon, c_lat, c_lon;
                var rotas      = [];
                var map, markers, router;
                var mapRend = false;
                function initialize(markersIni) {

                    
                    
                    $("#box_mapa").show();

                    if (mapRend == false) {
                        map      = new Map("map_canvas");
                        map.load();
                        markers  = new Markers(map);
                        router   = new Router(map);
                        mapRend = true;
                    }
                    

                    markersIni.forEach(function(v, k) {
                        markers.add(v.latitude,v.longitude,'red',v.title);
                    });

                    markers.render();
                    markers.focus();
                }

                function addMap() {
                    var markersMap = [];

                    $("span.posto-resultado").each(function() {
                        var lat = $(this).data("lat");
                        var lng = $(this).data("lng");
                        var fantasia = $(this).data("fantasia");

                        if (lat == null || lng == null) {
                            return true;
                        }

                        markersMap.push({latitude:lat,longitude:lng,title:fantasia});
                    });

                    initialize(markersMap);
                }

                function rota (lat, lng, c) {
                    var p = lat+","+lng;
                    c = c.replace("@", ",");

                    if (typeof rotas[p] == "undefined") {
                        $.ajax({
                            async: true,
                            timeout: 60000,
                            url: window.location,
                            type: "get",
                            data: {
                                ajax_rota: true,
                                origem: c,
                                destino: p
                            },
                            beforeSend: function() {
                                $("div.calculo-rota").show();
                            }
                        }).fail(function(r) {
                            alert("Erro ao gerar Rota");
                            $("div.calculo-rota").hide();
                        }).done(function(r) {
                            r = JSON.parse(r);

                            $("div.calculo-rota").hide();

                            if (r.exception) {
                                alert("Erro ao gerar Rota");
                            
                            } else {
                                //console.log(r["routes"][0]["geometry"]);

                                rotas[p] = Polyline.decode(r.rota.routes[0].geometry);
                                c = c.split(',');
                                markers.add(c[0],c[1],'blue','Cliente');
                                geraMapaRota(rotas[p]);
                            }
                        });
                    } else {
                        geraMapaRota(rotas[p]);
                    }
                }

                function geraMapaRota(rota) {
                    //console.log(rota);
                    markers.clear();
                    markers.render();

                    router.remove();
                    router.clear();
                    router.add(rota);
                    router.render();

                    map.scrollToMap();

                }
                
                //Fim TcMaps               


                /* Google Maps */
                // function initialize(markers) {
                //     $("#box_mapa").show();
                //     var width = parseInt($("#map_canvas").width() / 2);
                //     var height = parseInt($("#map_canvas").height() / 2);

                //     var url = "https://maps.googleapis.com/maps/api/staticmap?scale=2&size="+width+"x"+height+"&maptype=roadmap&"+markers.join('&')+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

                //     $("#map_canvas").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");
                // }

                // var rotas      = [];
                // var instrucoes = [];

                // function rota (lat, lng, c) {
                //     var p = lat+","+lng;
                //     c = c.replace("@", ",");

                //     if (typeof rotas[p] == "undefined") {
                //         $.ajax({
                //             async: true,
                //             timeout: 60000,
                //             url: window.location,
                //             type: "get",
                //             data: {
                //                 ajax_rota: true,
                //                 origem: c,
                //                 destino: p
                //             },
                //             beforeSend: function() {
                //                 $("div.calculo-rota").show();
                //             }
                //         }).fail(function(r) {
                //             alert("Erro ao gerar Rota");
                //             $("div.calculo-rota").hide();
                //         }).done(function(r) {
                //             r = JSON.parse(r);

                //             $("div.calculo-rota").hide();

                //             if (r.exception) {
                //                 alert("Erro ao gerar Rota");
                //             } else if (r.status != "OK") {
                //                 alert("Erro ao gerar Rota");
                //             } else {
                //                 instrucoes[p] = "<h2>InstruÁıes</h2><button type='button' class='btn-fechar-instrucao'>Fechar instruÁıes</button><br /><hr />";

                //                 r.routes[0].legs[0].steps.forEach(function(v, k) {
                //                     instrucoes[p] += v.html_instructions+"<br /><hr />";
                //                 });

                //                 rotas[p] = r.routes[0].overview_polyline.points;
                //                 geraMapaRota(rotas[p], instrucoes[p], c, p);
                //             }
                //         });
                //     } else {
                //         geraMapaRota(rotas[p], instrucoes[p], c, p);
                //     }
                // }

                // function geraMapaRota(rota, instrucao, c, p) {
                //     var path = "&path=weight:2%7Cenc:"+rota;
                //     var markers = [];

                //     markers.push("markers=color:red%7C"+p);
                //     markers.push("markers=color:blue%7C"+c);

                //     var width = parseInt($("#map_canvas").width() / 2);
                //     var height = parseInt($("#map_canvas").height() / 2);

                //     var url = "https://maps.googleapis.com/maps/api/staticmap?scale=2&size="+width+"x"+height+"&maptype=roadmap&"+markers.join('&')+path+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

                //     $("#map_canvas").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");
                //     $("#map_canvas").prepend("<button type='button' class='btn-instrucao-rota' >Ver instruÁıes da rota</button>");
                //     $("#map_canvas").prepend("<div class='instrucao-rota' >"+instrucao+"</div>");
                // }

                // function localizar (lat, lng){
                //     var width = parseInt($("#map_canvas").width() / 2);
                //     var height = parseInt($("#map_canvas").height() / 2);

                //     var url = "https://maps.googleapis.com/maps/api/staticmap?center="+lat+","+lng+"&zoom=15&scale=2&size="+width+"x"+height+"&maptype=roadmap&markers=color:red%7C"+lat+","+lng+"&key=AIzaSyC5AsH3NU-IwraXqLAa1qbXxyjklSVP-cQ";

                //     $("#map_canvas").html("<img src='"+url+"' style='width: 100%; height: 100%;' />");
                // }

                // function addMap() {
                //     var markers = [];

                //     $("span.posto-resultado").each(function() {
                //         var lat = $(this).data("lat");
                //         var lng = $(this).data("lng");

                //         if (lat == null || lng == null) {
                //             return true;
                //         }

                //         markers.push("markers=color:red%7C"+lat+","+lng);
                //     });

                //     initialize(markers);
                // }
                /* Google Maps FIM*/

                var endereco = {};

                /**
                * FunÁ„o que faz um ajax para buscar o cep nos correios
                */
                function busca_cep(cep, method) {                  
                    if (cep.length > 0) {
                        if (typeof method == "undefined") {
                            method = "webservice";
                        }

                      //  $("#consultar").prop({ disabled: true }).text("Consultando...");
                        //$("#estado, #cidade").prop({ disabled: true });
                        
                        endereco = {};
                        rotas = [];

                        $.ajax({
                            async: true,
                            timeout: 60000,
                            url: "../../admin/ajax_cep.php",
                            type: "get",
                            data: { method: method, cep: cep }
                        }).fail(function(r) {
                            if (method == "webservice") {
                                busca_cep(cep, "database");
                            } else {
                                alert("Erro ao consultar CEP, tempo limite esgotado");
                                $("#consultar").prop({ disabled: false }).text("Consultar");
                                $("#estado, #cidade").prop({ disabled: false });
                            }
                        }).done(function(r) {
                            data = r.split(";");

                            if (data[0] != "ok" && method == "webservice") {
                                busca_cep(cep, "database");
                            } else if (data[0] != "ok") {
                                if (data[0].length > 0) {
                                    alert(data[0]);
                                } else {
                                    alert("Erro ao buscar CEP");
                                }
                            } else {
                                var estado, cidade, end, bairro;

                                if (data[4] != undefined) estado = data[4];
                                if (data[3] != undefined) cidade = data[3];
                                if (data[1] != undefined && data[1].length > 0) end = data[1];
                                if (data[2] != undefined && data[2].length > 0) bairro = data[2];

                                endereco.estado   = estado;
                                endereco.cidade   = cidade;
                                endereco.endereco = end;
                                endereco.bairro   = bairro;

                                buscaEstado(function() {
                                    $("#estado").val(estado);

                                    buscaCidade(estado, function() {
                                        $("#cidade").val(retiraAcentos(cidade).toUpperCase());

                                        buscaAssistencia(retiraAcentos(cidade).toUpperCase());
                                    });
                                });
                            }

                            $("#consultar").prop({ disabled: false }).text("Consultar");
                        });
                    }
                }

                /**
                 * FunÁ„o para retirar a acentuaÁ„o
                 */
                function retiraAcentos(palavra){
                    var com_acento = '·‡„‚ÈËÍ?ÌÏÓiÛÚÙı˙˘˚uÁ¡¿¬√…» ?ÃÕŒI”“‘’⁄Ÿ€U«';
                    var sem_acento = 'aaaaeeeeiiiioooouuuucAAAAEEEEIIIIOOOOUUUUC';
                    var newPalavra = "";

                    for(i = 0; i < palavra.length; i++) {
                        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
                            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
                        } else {
                            newPalavra += palavra.substr(i, 1);
                        }
                    }

                    return newPalavra.toUpperCase();
                }

                function buscaEstado(callback) {
                    $("#estado").html("\
                        <option value='' >Carregando Estados...</option>\
                    ");
                    $("#estado").prop({ disabled: true });

                    $.ajax({
                        type: "POST",
                        url:  "wanke_at.php",
                        data: "buscaAjax=estados",
                        async: true,
                        timeout: 60000
                    }).fail(function(r) {
                        alert("Erro ao buscar estados, tempo limite esgotado");
                        $("#estado").html("<option value='' ></option>");
                        $("#estado").prop({ disabled: false });
                    }).done(function(r) {
                        $("#estado").html(r);
                        $("#estado").prop({ disabled: false });
                        $("#cidade").html("<option value='' >Selecione um Estado</option>");
                        $("#cidade").prop({ disabled: true });
                        $("#assistencia").html("");
                        $("#box_mapa").hide();

                        if (typeof callback != "undefined") {
                            callback();
                        }
                    });
                }

                function buscaCidade(estado, callback) {
                    if(estado.length > 0){
                        $("#cidade").html("\
                            <option value='' >Carregando Cidades...</option>\
                        ");

                        $("#cidade").prop({ disabled: true });
                        $.ajax({
                            type: "POST",
                            url:  "wanke_at.php",
                            data: "estado="+estado+"&buscaAjax=cidades",
                            async: true,
                            timeout: 60000
                        }).fail(function(r) {
                            alert("Erro ao buscar cidades, tempo limite esgotado");
                            $("#cidade").html("<option value='' ></option>");
                            $("#cidade").prop({ disabled: false });
                        }).done(function(r) {
                            $("#cidade").html(r);
                            $("#cidade").prop({ disabled: false });
                            $("#assistencia").html("");
                            $("#box_mapa").hide();

                            if (typeof callback != "undefined") {
                                callback();
                            }
                        });
                    } else {
                        $("#cidade").html("\
                            <option value='' >Selecione um Estado</option>\
                        ");
                        $("#cidade").prop({ disabled: true });
                    }
                }

                function buscaAssistencia(cidade) {
                    var estado   = $("#estado").val();
                    var cep      = $("#cep").val();

                    if (estado == "" && cidade == "") {
                        alert("Estado e Cidade s„o obrigatÛrios");
                        return;
                    }

                    $("#assistencia").show();
                    var lista = "";

                    var width_page = $(window).width();
                    if (width_page <= 838) {
                        $('.box_50_left').css({width: '30%', margin: 'auto'});    
                    } else {
                        $('.box_50_left').css({width: '30%', margin: 'auto'});
                    }
                    
                    $('#cep').css({width: '93%'});
                    $("#formulario").animate({
                        marginLeft: "0%"
                    }, "slow");

                    if(cidade.length > 0 && estado.length > 0){
                        if (cep.length == 0) {
                            endereco = {};
                        }

                        var end = [];

                        if (typeof endereco.endereco != "undefined" && endereco.endereco.length > 0) {
                            end.push(endereco.endereco);
                        }

                        if (typeof endereco.bairro != "undefined" && endereco.bairro.length > 0) {
                            end.push(endereco.bairro);
                        }

                        if (typeof endereco.cidade != "undefined" && endereco.cidade.length > 0) {
                            end.push(endereco.cidade);
                        }

                        if (typeof endereco.estado != "undefined" && endereco.estado.length > 0) {
                            end.push(endereco.estado);
                        }

                        end = end.join(", ");
                        
                        // console.log(end);

                        $("#assistencia").html("Pesquisando...");

                        $.ajax({
                            async: true,
                            timeout: 60000,
                            type: "POST",
                            url:  "wanke_at.php",
                            data: "estado="+estado+"&cidade="+cidade+"&cep="+cep+"&endereco="+end+"&buscaAjax=assistencia",
                            success: function(resposta){
                                if(resposta.length > 0){
                                    var dados = $.parseJSON(resposta);
                                    // console.log(dados);
                                    //lista += "<h2>Postos para assistÍncia:</h2>";
                                    $.each(dados.posto, function(key, value) {
                                        var rota = "";
                                        if (markers != null) {
                                            markers.remove();
                                            markers.clear();
                                        }
                                        if (router != null) {
                                            router.remove();
                                            router.clear();
                                        }  
                                        if (dados.consumidor.length > 0 && dados.consumidor != "@") {
                                            rota = "<button type='button' class='btn_pesquisar rota' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' data-consumidor='"+dados.consumidor+"' ><i class='fa fa-map-marker'></i> Realizar Rota</button>";
                                        }

                                        //* RETIRADO BOT√O 'LOCALIZAR' HD-7084357 *//
                                        rota += "&nbsp; <button type='button' class='btn_pesquisar localizar' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' ><i class='fa fa-search'></i> Localizar</button>";

                                        lista += "\
                                            <span class='posto-resultado' style='margin: 10px 5px' data-fantasia='"+value.nome_fantasia+"' data-lat='"+value.latitude+"' data-lng='"+value.longitude+"' >\
                                                <b style='color: black'>"+value.nome_fantasia+"</b><br />\
                                                <b>"+value.nome+"</b><br />\
                                                "+value.endereco+", "+value.numero+" - "+value.bairro+"<br />\
                                                "+value.cidade_posto+" - "+value.estado+" - Fone: "+value.fone+" <br />\
                                                <strong>Email: </strong>"+value.email+" <br />\
                                                <strong>Cidades que Atende:</strong> "+value.cidades_atende+" <br />\
                                                <strong>Dist‚ncia:</strong> "+value.distancia+" KM \
                                            </span>\
                                            "+rota+"\
                                            <br /><br /><hr class='cor_hr' /><br /><br />\
                                        ";
                                    });

                                    $("#assistencia").html(lista);
                                    
                                    addMap();

                                    endereco = {};
                                    $("#cep").val("");
                                }else{
                                    /*alert("Prezado Consumidor, n„o encontramos um posto autorizado na sua regi„o, por favor, entrar em contato com o nosso ServiÁo de Atendimento ao Consumidor atravÈs do contato sac@jcsbrasil.com.br ou telefone 0800 644 6442 de segunda a sexta-feira das 08:00 as 17:00 horas (exceto feriados nacionais).");*/
                                    
                                    /*alert("Importante: n„o encontramos um posto autorizado em sua regi„o.\nPor favor entrar em contato com o nosso ServiÁo de Atendimento ao Consumidor para maiores informaÁıes.\nContatos: sac@jcsbrasil.com.br ou atravÈs do telefone 4020 2905.");
                                    $("#assistencia").html("");
                                    $("#box_mapa").hide();
                                    endereco = {};
                                    $("#cep").val("");*/

                                    lista = "<span class='posto-resultado' style='margin: 10px 5px; margin-top: 30px;'>\
                                                <h2 style='font-size: 24px !important;'>\
                                                    <b style='color: #f12a30;'>\
                                                        Oops! No momento n„o localizamos uma assistÍncia autorizada em sua cidade.\
                                                    </b>\
                                                </h2>\
                                            </span>";
                                    $("#assistencia").html(lista);
                                }
                            }
                        }).fail(function(r) {
                            alert("Erro ao buscar Postos Autorizado, tempo limite esgotado");
                            $("#assistencia").html("");
                            $("#box_mapa").hide();
                            endereco = {};
                            $("#cep").val("");
                        });
                    }
                    $("#formAssistencia").fadeOut(); 
                    $("#pesquisar-novamente").attr('style', 'display:flex;margin:auto;');
                    let btnPesquisar = $("#pesquisar-novamente");
                    btnPesquisar.click(function(){
                        //$("#assistencia").attr('style','overflow-x: hidden !important; overflow-y: visible !important; height: 600px !important;');
                        $("#assistencia, #box_mapa").hide();
                        $("#formAssistencia").fadeIn();
                        $("#pesquisar-novamente").attr('style', 'display:none;');
                        btnPesquisar.off();
                        console.log($("#assistencia").attr('style'));
                    });
                }
            </script>
        </body>
    </html>
