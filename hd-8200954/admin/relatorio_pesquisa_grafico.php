<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
//include "funcoes.php";
include '../fn_logoResize.php';

function verificaDataValida($data)
{
    if (!empty($data)) {
        list($di, $mi, $yi) = explode("/", $data);

        return checkdate($mi, $di, $yi) ? true : false;
    }

    return false;
}

function carregaDados($con, $login_fabrica, $data_inicio, $data_fim, $pais, $grafico, $cidade = null, $posto = null, $marca = null)
{

    foreach ($pais as $p) {
        $wherePais[] = "(pais\":\"{$p})";
    }

    $sql_l = "
               SELECT  tbl_laudo_tecnico_os.os,
                       tbl_laudo_tecnico_os.observacao
               FROM    tbl_os
               JOIN    tbl_laudo_tecnico_os ON tbl_laudo_tecnico_os.os = tbl_os.os
               JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
               WHERE   tbl_laudo_tecnico_os.data BETWEEN '$data_inicio 00:00:00' AND '$data_fim 23:59:59'
               AND     tbl_laudo_tecnico_os.observacao ~ E'" . implode("|", $wherePais) . "'
               AND     titulo ILIKE 'Pesquisa de%'
         ORDER BY      tbl_laudo_tecnico_os.data;
       ";





    $res_l = pg_query($con, $sql_l);

    $result = pg_fetch_all($res_l);
    switch ($grafico) {
        case 1:
            return geraGrafico01($result, $login_fabrica, $con, $marca, $cidade, $posto);
            break;
        case 2:
            return geraGrafico02($result, $login_fabrica, $con, $marca, $cidade, $posto);
            break;
        case 3:
            return geraGrafico03($result, $login_fabrica, $con, $cidade, $posto, $marca);
            break;
        case 4:
            return geraGrafico04($result, $login_fabrica, $con, $cidade, $posto, $marca);
            break;
        case 5:
            return geraGrafico05($result, $login_fabrica, $con, $posto, $cidade, $marca);
            break;
        case 6:
            return geraGrafico06($result, $cidade, $posto);
            break;
    }
}

/**
 * 1º Gráfico - PIZZA
 * -- Estes gráficos mostram, separados por MARCA, as ocorrências
 * da pergunta 05 da pesquisa, que se refere à recomendação
 * dos produtos para outras pessoas
 *
 * PROMOTOR (Promove a marca):           Respostas entre 9 e 10
 * PASSIVO (Estão indiferentes à marca): Respostas entre 7 e 8
 * DETRACTOR (Denigrem a marca):         Respostas entre 0 e 6
 */
function geraGrafico01($json, $login_fabrica, $con, $marca = null, $cidade = null, $posto = null)
{

    foreach ($json as $respostas) {
        $respostaJson = $respostas['observacao'];
        // $resposta = json_decode(stripslashes($respostaJson), true);
        $resposta = json_decode($respostaJson,TRUE);

        if (!empty($posto)) {
            if (urlencode($posto) != $resposta['posto']) {
                continue;
            }
        } else {
            if (!empty($cidade)) {
                if (urlencode($cidade) != strtoupper($resposta['cidade'])) {
                    continue;
                }
            }
        }

        switch ($resposta['recomendacao']) {
            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                if ($resposta['marca'] != "") {
                    $grafico01Marca[$resposta['marca']]["detractor"] += 1;
                    $grafico01Total["detractor"] += 1;
                }

                break;
            case 7:
            case 8:
                if ($resposta['marca'] != "") {
                    $grafico01Marca[$resposta['marca']]["passivo"] += 1;
                    $grafico01Total["passivo"] += 1;
                }

                break;
            case 9:
            case 10:
                if ($resposta['marca'] != "") {
                    $grafico01Marca[$resposta['marca']]["promotor"] += 1;
                    $grafico01Total["promotor"] += 1;
                }

                break;
        }
    }

    $chavesEquip = array_keys($grafico01Marca);

    foreach ($chavesEquip as $keyx => $valuex) { //hd_chamado=3110652
        if(strlen($valuex) == 0){
            unset($chavesEquip[$keyx]);
        }
    }

    $sql = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica
        AND     marca IN (" . implode(',', $chavesEquip) . ")
    ";

    $res = pg_query($con, $sql);
    $arrayTudo = pg_fetch_all($res);
    $arrayMarcas = pg_fetch_all_columns($res, 0);
    $arrayNomes = pg_fetch_all_columns($res, 1);

    $arrayPontMarcas = array_combine($arrayMarcas, $arrayNomes);

    if (!empty($marca) && $marca != 0) {

        foreach ($arrayMarcas as $chave => $valor) {
            if ($valor == $marca) {
                $marcaEscolhida = $arrayNomes[$chave];
            } else {
                continue;
            }
        }
    }

    if (in_array($marca, $arrayMarcas)) {

        foreach ($grafico01Marca[$marca] as $pont => $voto) {
            $arrayGrafico[] = array(ucfirst($pont), $voto);
        }

        $somaNps = array_sum($grafico01Marca[$marca]);
        $baseNps = $grafico01Marca[$marca]['promotor'] - $grafico01Marca[$marca]['detractor'];
        $calculaNps = (float)number_format((($baseNps * 100) / $somaNps), 2);

        $seriesPie[] = array(
            "type" => "pie",
            "name" => utf8_encode("Pontuações " . $arrayPontMarcas[$marca]),
            "size" => 200,
            "dataLabels" => array("enabled" => true),
            "data" => $arrayGrafico,
            "tooltip" => array("pointFormat" => "<b>{point.percentage:.2f}%</b>")
        );
    } else {
        foreach ($grafico01Total as $pont => $voto) {
            $arrayGrafico[] = array(ucfirst($pont), $voto);
        }

        $somaNps = array_sum($grafico01Total);
        $baseNps = $grafico01Total['promotor'] - $grafico01Total['detractor'];
        $calculaNps = (float)number_format((($baseNps * 100) / $somaNps), 2);

        $seriesPie[] = array(
            "name" => utf8_encode("Pontuações Gerais"),
            "type" => "pie",
            "size" => 200,
            "dataLabels" => array("enabled" => true),
            "tooltip" => array("pointFormat" => "<b>{point.percentage:.2f}%</b>"),
            "data" => $arrayGrafico
        );
    }

    if (is_array($arrayGrafico)) {
        $resultadoPie = $seriesPie;

        return json_encode(
            array(
                "series" => $resultadoPie,
                "arrayMarcas" => $arrayTudo,
                "marcaEscolhida" => $marcaEscolhida,
                "nps" => $calculaNps
            )
        );
    } else {
        return "erro";
    }
}

/**
 * 2º Gráfico - COLUNAS COM LINE
 * -- Este gráfico mostra, separado por EQUIPAMENTO, as ocorrências
 * da pergunta 05 da pesquisa, que se refere à recomendação
 * dos produtos para outras pessoas.
 *
 * - Calcula-se, também, o nível de satisfação (%) de cada equipamento
 * FORMULA: TOTAL RESULTADO, INCIDE A PORCENTAGEM DE (PROMOTOR - DETRACTOR)
 *
 */
function geraGrafico02($json, $login_fabrica, $con, $marca = null, $cidade = null, $posto = null)
{

    foreach ($json as $respostas) {

        $respostaJson = $respostas['observacao'];
        #$resposta = json_decode(stripslashes($respostaJson),TRUE);
        $resposta = json_decode($respostaJson,TRUE);
        $language = $resposta['language'];



        if (!empty($posto)) {
            if (urlencode($posto) != $resposta['posto']) {
                continue;
            }
        } else {
            if (!empty($cidade)) {
                if (urlencode($cidade) != strtoupper($resposta['cidade'])) {
                    continue;
                }
            }
        }

        switch ($resposta['recomendacao']) {
            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                if($resposta['equipamento'] != ""){
                    $grafico02Equip[$resposta['equipamento']]["detractor"] += 1;
                    $grafico02EquipMarca[$resposta['marca']][$resposta['equipamento']]["detractor"] += 1;
                }

                break;
            case 7:
            case 8:
                if($resposta['equipamento'] != ""){
                    $grafico02Equip[$resposta['equipamento']]["passivo"] += 1;
                    $grafico02EquipMarca[$resposta['marca']][$resposta['equipamento']]["passivo"] += 1;
                }


                break;
            case 9:
            case 10:
                if($resposta['equipamento'] != ""){
                    $grafico02Equip[$resposta['equipamento']]["promotor"] += 1;
                    $grafico02EquipMarca[$resposta['marca']][$resposta['equipamento']]["promotor"] += 1;
                }


                break;
        }
    }



    $arrayEquipamento["es"] = array(
        "martillos",
        "inalambrico",
        "metalmecanica",
        "madera",
        "estacionaria",
        "jardin",
        "gasolina_explosion",
        "neumatica",
        "other"
    );

    $arrayEquipamento["pt"] = array(
        "martelo",
        "furadeira",
        "mecanica",
        "madeira",
        "serras",
        "jardinagem",
        "gasolina",
        "pneumatica",
        "outro"
    );
    // return print_r($arrayEquipamento);
    #$chavesEquip = array_keys($grafico02Equip);
    $chavesEquip        = array_keys(array_filter($grafico02Equip,'trim'));
    $intersect = array_diff($arrayEquipamento[$language], $chavesEquip);

    $novoArrayEquip = array_merge($chavesEquip, $intersect);


    sort($novoArrayEquip);
    $qtdeEquip = count($novoArrayEquip);

    $chavesEquipMarca = array_keys($grafico02EquipMarca);

    foreach ($chavesEquipMarca as $keyx => $valuex) { //hd_chamado=3110652
        if(strlen($valuex) == 0){
            unset($chavesEquipMarca[$keyx]);
        }
    }

    $sql = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica
        AND     marca IN (" . implode(',', $chavesEquipMarca) . ")
    ";

    $res = pg_query($con, $sql);

    $arrayMarcas = pg_fetch_all($res);
    if (!empty($marca) && $marca != 0) {
        $valorMarcaEscolhida = pg_fetch_all_columns($res, 0);
        $nomeMarcaEscolhida = pg_fetch_all_columns($res, 1);

        foreach ($valorMarcaEscolhida as $chave => $valor) {
            if ($valor == $marca) {
                $marcaEscolhida = $nomeMarcaEscolhida[$chave];
            } else {
                continue;
            }
        }
    }

    if (in_array($marca, $chavesEquipMarca)) {
        for ($c = 0; $c < $qtdeEquip; $c++) {
            if (array_key_exists("promotor", $grafico02EquipMarca[$marca][$novoArrayEquip[$c]])) {
                $qtdePromotor[] = (int)$grafico02EquipMarca[$marca][$novoArrayEquip[$c]]['promotor'];
            } else {
                $qtdePromotor[] = 0;
            }

            if (array_key_exists("passivo", $grafico02EquipMarca[$marca][$novoArrayEquip[$c]])) {
                $qtdePassivo[] = (int)$grafico02EquipMarca[$marca][$novoArrayEquip[$c]]['passivo'];
            } else {
                $qtdePassivo[] = 0;
            }

            if (array_key_exists("detractor", $grafico02EquipMarca[$marca][$novoArrayEquip[$c]])) {
                $qtdeDetractor[] = (int)$grafico02EquipMarca[$marca][$novoArrayEquip[$c]]['detractor'];
            } else {
                $qtdeDetractor[] = 0;
            }
        }
    } else {
        for ($c = 0; $c < $qtdeEquip; $c++) {
            if (array_key_exists("promotor", $grafico02Equip[$novoArrayEquip[$c]])) {
                $qtdePromotor[] = (int)$grafico02Equip[$novoArrayEquip[$c]]['promotor'];
            } else {
                $qtdePromotor[] = 0;
            }

            if (array_key_exists("passivo", $grafico02Equip[$novoArrayEquip[$c]])) {
                $qtdePassivo[] = (int)$grafico02Equip[$novoArrayEquip[$c]]['passivo'];
            } else {
                $qtdePassivo[] = 0;
            }

            if (array_key_exists("detractor", $grafico02Equip[$novoArrayEquip[$c]])) {
                $qtdeDetractor[] = (int)$grafico02Equip[$novoArrayEquip[$c]]['detractor'];
            } else {
                $qtdeDetractor[] = 0;
            }
        }
    }
    foreach ($qtdePromotor as $k => $promotor) {
        $base = $promotor - $qtdeDetractor[$k];
        $nps[] = (float)number_format(($base * 100) / ($promotor + $qtdePassivo[$k] + $qtdeDetractor[$k]), 2);

        $porcentagem = ($promotor + $qtdePassivo[$k] + $qtdeDetractor[$k]);
        $pcPromotor[] = (float)number_format(($promotor * 100) / $porcentagem, 2);
        $pcPassivo[] = (float)number_format(($qtdePassivo[$k] * 100) / $porcentagem, 2);
        $pcDetractor[] = (float)number_format(($qtdeDetractor[$k] * 100) / $porcentagem, 2);
    }

    $arrayEquipSeries = array(
        array(
            "name" => "Promotor",
            "type" => "column",
            "data" => $pcPromotor
        ),
        array(
            "name" => "Passivo",
            "type" => "column",
            "data" => $pcPassivo
        ),
        array(
            "name" => "Detractor",
            "type" => "column",
            "data" => $pcDetractor
        ),
        array(
            "name" => "NPS",
            "data" => $nps,
            "yAxis" => 1,
            "tooltip" => array(
                "valueSuffix" => "%"
            )
        )
    );

    $arrayTabelaSeries = array(
        array(
            "Promotor" => $qtdePromotor
        ),
        array(
            "Passivo" => $qtdePassivo
        ),
        array(
            "Detractor" => $qtdeDetractor
        )
    );

    if (is_array($novoArrayEquip)) {
        $arrayEquipamentoTrad = array(
            "martelo",
            "Furadeira / Furadeira sem fio",
            "Metal - Mecânica",
            "madeira",
            "Serras",
            "Jardinagem",
            "Gasolina",
            "Pneumática",
            "outro"
        );
        $graficoEquipamentos = json_encode($novoArrayEquip);
        $graficoEquipamentos = str_replace($arrayEquipamento[$language], $arrayEquipamentoTrad, $graficoEquipamentos);
        $graficoEquipamentos = utf8_encode($graficoEquipamentos);
        $graficoEquipamentos = json_decode($graficoEquipamentos);
        $graficoEquipSeries = $arrayEquipSeries;

        return json_encode(
            array(
                "categories" => $graficoEquipamentos,
                "series" => $graficoEquipSeries,
                "seriesTabela" => $arrayTabelaSeries,
                "arrayMarcas" => $arrayMarcas,
                "marcaEscolhida" => $marcaEscolhida
            )
        );
    } else {
        return "erro";
    }
}

/**
 * 3º Gráfico - BARRAS HORIZONTAIS
 * -- Mostra a porcentagem de motivos do consumidor
 * ter dado tal pontuação ao equipamento
 */
function geraGrafico03($json, $login_fabrica, $con, $cidade = null, $posto = null, $marca = null)
{
    foreach ($json as $respostas) {
        $respostaJson = $respostas['observacao'];
        #$resposta = json_decode(stripslashes($respostaJson),TRUE);
        $resposta = json_decode($respostaJson,TRUE);

        if (!empty($posto)) {
            if (urlencode($posto) != $resposta['posto']) {
                continue;
            }
        } else {
            if (!empty($cidade)) {
                if (urlencode($cidade) != strtoupper($resposta['cidade'])) {
                    continue;
                }
            }
        }

        if($resposta['marca'] != ""){
            $grafico03[] = $resposta['razao_pontuacao'];
            $grafico03Marca[$resposta['marca']][] = $resposta['razao_pontuacao'];
        }

    }

    $chavesEquipMarca = array_keys($grafico03Marca);

    foreach ($chavesEquipMarca as $keyx => $valuex) { //hd_chamado=3110652
        if(strlen($valuex) == 0){
            unset($chavesEquipMarca[$keyx]);
        }
    }

    $sql = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica
        AND     marca IN (" . implode(',', $chavesEquipMarca) . ")
    ";

    $res = pg_query($con, $sql);

    $arrayMarcas = pg_fetch_all($res);

    if (!empty($marca) && $marca != 0) {
        $valorMarcaEscolhida = pg_fetch_all_columns($res, 0);
        $nomeMarcaEscolhida = pg_fetch_all_columns($res, 1);

        foreach ($valorMarcaEscolhida as $chave => $valor) {
            if ($valor == $marca) {
                $marcaEscolhida = $nomeMarcaEscolhida[$chave];
            } else {
                continue;
            }
        }

    }


    $valuesAcertar = array(
        "atencao_suporte_telefonico",
        "atencao_suporte_recepcao",
        "falha_precoce_ferramenta",
        "qualidade_produto",
        "tempo_de_resposta",
        "custo_orcamento_reparo",
        "rastreamento_reparacao",
        "tempo_repado",
        "qualidade_reparacao",
        "servico_prestado_centro"
    );

    if (in_array($marca, $chavesEquipMarca)) {

        $graficoRazao = array_count_values($grafico03Marca[$marca]);
        $populados = array_keys($graficoRazao);
        $aux = array_diff($valuesAcertar, $populados);

        if(count($aux) > 0){ //HD-3017902
            foreach ($aux as $valor) {
                $arrayZerado[$valor] = (int)0;
            }
            if(is_array($arrayZerado)){
                $graficoRazao = array_merge($graficoRazao, $arrayZerado);
            }
        }

        $total = array_sum($graficoRazao);
        ksort($graficoRazao);

        foreach ($graficoRazao as $parcial) {
            $aux = ($parcial * 100) / $total;
            $porcentagemRazao[] = (float)number_format($aux, 2);
            $valorRazao[] = (int)$parcial;
        }
    } else {

        $graficoRazao = array_count_values($grafico03);

        $populados = array_keys($graficoRazao);

        $aux = array_diff($valuesAcertar, $populados);


        foreach ($aux as $valor) {
            $arrayZerado[$valor] = (int)0;
        }

        if(is_array($arrayZerado)){
            $graficoRazao = array_merge($graficoRazao, $arrayZerado);
        }


        $total = array_sum($graficoRazao);
        ksort($graficoRazao);

        foreach ($graficoRazao as $parcial) {
            $aux = ($parcial * 100) / $total;
            $porcentagemRazao[] = (float)number_format($aux, 2);
            $valorRazao[] = (int)$parcial;
        }
    }

    $tradValues = array(
        "Atenção e suporte (Telefônico)",
        "Atenção e suporte (Recepção da assistência técnica)",
        "Falha precoce da ferramenta",
        "A qualidade do produto",
        "Tempo de resposta",
        "Custo e/ou orçamento do reparo",
        "Rastreamento de reparação",
        "Tempo de reparo",
        "Qualidade da reparação",
        "Serviço prestado pelo centro de serviço"
    );



    // $graficoCategoriaRazao = json_encode(array_keys($graficoRazao));
    // $graficoCategoriaRazao = str_replace($valuesAcertar, $tradValues, $graficoCategoriaRazao);
    // $graficoCategoriaRazao = utf8_encode($graficoCategoriaRazao);
    // $graficoCategoriaRazao = json_decode($graficoCategoriaRazao, true);
    foreach($tradValues AS $key => $value){
        $tradValues[$key] = utf8_encode($value);
    }

    $graficoCategoriaRazao = array_keys($graficoRazao);
    $graficoCategoriaRazao = str_replace($valuesAcertar,$tradValues,$graficoCategoriaRazao);

    $graficoValoresRazao = $porcentagemRazao;

    if (is_array($graficoCategoriaRazao)) {
        return json_encode(
            array(
                "arrayMarcas" => $arrayMarcas,
                "marcaEscolhida" => $marcaEscolhida,
                "categories" => $graficoCategoriaRazao,
                "series" => array(
                    "porcentagem" => $graficoValoresRazao,
                    "valor" => $valorRazao
                )
            )
        );
    } else {
        return "erro";
    }
}

/**
 * 4º Gráfico - COLUNAS
 * -- Mostra a pontuação, de acordo com
 * os dias que a OS ficou aberta (informado pelo usuário em pesquisa)
 */
function geraGrafico04($json, $login_fabrica, $con, $cidade = null, $posto = null, $marca = null)
{


    foreach ($json as $respostas) {
        $respostaJson = $respostas['observacao'];
        // $resposta = json_decode(stripslashes($respostaJson), true);
        $resposta = json_decode($respostaJson,TRUE);

        if (!empty($posto)) {
            if (urlencode($posto) != $resposta['posto']) {
                continue;
            }
        } else {
            if (!empty($cidade)) {
                if (urlencode($cidade) != strtoupper($resposta['cidade'])) {
                    continue;
                }
            }
        }

        if ($resposta['numero_dias'] == 'mais') {
            $resposta['numero_dias'] = 9;
        }

        switch ($resposta['recomendacao']) {
            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                if($resposta['marca'] != ""){
                    $grafico04Dias[$resposta['numero_dias']]["detractor"] += 1;
                    $grafico04DiasMarca[$resposta['marca']][$resposta['numero_dias']]["detractor"] += 1;
                    $totalParcial[$resposta['numero_dias']] += 1;
                }

                break;
            case 7:
            case 8:
                if($resposta['marca'] != ""){
                    $grafico04Dias[$resposta['numero_dias']]["passivo"] += 1;
                    $grafico04DiasMarca[$resposta['marca']][$resposta['numero_dias']]["passivo"] += 1;
                    $totalParcial[$resposta['numero_dias']] += 1;
                }

                break;
            case 9:
            case 10:
                if($resposta['marca'] != ""){
                    $grafico04Dias[$resposta['numero_dias']]["promotor"] += 1;
                    $grafico04DiasMarca[$resposta['marca']][$resposta['numero_dias']]["promotor"] += 1;
                    $totalParcial[$resposta['numero_dias']] += 1;
                }
                break;
        }
    }

    ksort($grafico04Dias);
    $chavesDias = array_keys($grafico04Dias);

    $semId = 1;
    while ($semId <= 9) {
        if (!in_array($semId, $chavesDias)) {
            $chavesDias[] = $semId;
        }
        $semId++;
    }
    sort($chavesDias);
    $qtdeDias = count($chavesDias);

    $chavesDiasMarca = array_keys($grafico04DiasMarca);

    foreach ($chavesDiasMarca as $keyx => $valuex) { //hd_chamado=3110652
        if(strlen($valuex) == 0){
            unset($chavesDiasMarca[$keyx]);
        }
    }

    $sql = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica
        AND     marca IN (" . implode(',', $chavesDiasMarca) . ")
    ";

    $res = pg_query($con, $sql);

    $arrayMarcas = pg_fetch_all($res);
    if (!empty($marca) && $marca != 0) {
        $valorMarcaEscolhida = pg_fetch_all_columns($res, 0);
        $nomeMarcaEscolhida = pg_fetch_all_columns($res, 1);

        foreach ($valorMarcaEscolhida as $chave => $valor) {
            if ($valor == $marca) {
                $marcaEscolhida = $nomeMarcaEscolhida[$chave];
            } else {
                continue;
            }
        }
    }

    if (in_array($marca, $chavesDiasMarca)) {
        $arrayAux = $grafico04DiasMarca[$marca];

        ksort($arrayAux);
        $chaves = array_keys($arrayAux);

        $semId = 1;
        while ($semId <= 9) {
            if (!in_array($semId, $chaves)) {
                $chaves[] = $semId;
            }
            $semId++;
        }
        sort($chaves);

        $qtdeDiasMarca = count($chaves);

        for ($j = 0; $j < $qtdeDiasMarca; $j++) {
            if (array_key_exists("promotor", $arrayAux[$chaves[$j]])) {
                $diasPromotor[] = (int)$arrayAux[$chaves[$j]]['promotor'];
            } else {
                $diasPromotor[] = 0;
            }

            if (array_key_exists("passivo", $arrayAux[$chaves[$j]])) {
                $diasPassivo[] = (int)$arrayAux[$chaves[$j]]['passivo'];
            } else {
                $diasPassivo[] = 0;
            }

            if (array_key_exists("detractor", $arrayAux[$chaves[$j]])) {
                $diasDetractor[] = (int)$arrayAux[$chaves[$j]]['detractor'];
            } else {
                $diasDetractor[] = 0;
            }

            if ($chaves[$j] == 9) {
                $chaves[$j] = '> 8';
            }

            if(strlen($chavesDias[$j]) == 0) {
                $chavesDias[$j] = 0 ;
            }
        }
    } else {

        for ($j = 0; $j < $qtdeDias; $j++) {
            if (array_key_exists("promotor", $grafico04Dias[$chavesDias[$j]])) {
                $diasPromotor[] = (int)$grafico04Dias[$chavesDias[$j]]['promotor'];
            } else {
                $diasPromotor[] = 0;
            }

            if (array_key_exists("passivo", $grafico04Dias[$chavesDias[$j]])) {
                $diasPassivo[] = (int)$grafico04Dias[$chavesDias[$j]]['passivo'];
            } else {
                $diasPassivo[] = 0;
            }

            if (array_key_exists("detractor", $grafico04Dias[$chavesDias[$j]])) {
                $diasDetractor[] = (int)$grafico04Dias[$chavesDias[$j]]['detractor'];
            } else {
                $diasDetractor[] = 0;
            }

            if ($chavesDias[$j] == 9) {
                $chavesDias[$j] = '> 8';
            }

            if(strlen($chavesDias[$j]) == 0) {
                $chavesDias[$j] = 0 ;
            }
        }
    }

    $arrayDiasSeries = array(
        array(
            "name" => "Promotor",
            "data" => $diasPromotor
        ),
        array(
            "name" => "Passivo",
            "data" => $diasPassivo
        ),
        array(
            "name" => "Detractor",
            "data" => $diasDetractor
        ),
    );

    $graficoDias = (empty($marca))
        ? $chavesDias
        : $chaves;
    $graficoDiasSeries = $arrayDiasSeries;

    if (is_array($graficoDias)) {

        return json_encode(
            array(
                "arrayMarcas" => $arrayMarcas,
                "marcaEscolhida" => $marcaEscolhida,
                "categories" => $graficoDias,
                "series" => $graficoDiasSeries
            )
        );
    } else {
        return "erro";
    }
}

/**
 * 5º Gráfico - COLUNAS
 * -- Mostra a relação das respostas dadas nas perguntas
 * 09 à 15, sobre a satisfação do serviço prestado
 */
function geraGrafico05($json, $login_fabrica, $con, $posto = null, $cidade = null, $marca = null)
{


    foreach ($json as $respostas) {

        $respostaJson = $respostas['observacao'];
        #$resposta = json_decode(stripslashes($respostaJson), true);
        $resposta = json_decode($respostaJson,TRUE);
        if (!empty($posto)) {
            if (urlencode($posto) != $resposta['posto']) {
                continue;
            }
        } else {
            if (!empty($cidade)) {
                if (urlencode($cidade) != strtoupper($resposta['cidade'])) {
                    continue;
                }
            }
        }

        if (!empty($marca) && $marca != 0) {
            if ($resposta['marca'] != $marca && $resposta['marca'] != NULL) {
                $grafico05Marcas[] = $resposta['marca'];
                continue;
            }
        }

        $grafico05Resp['nota_tempo_reparo'][] = $resposta['nota_tempo_reparo'];
        $grafico05Resp['nota_preco_reparo'][] = $resposta['nota_preco_reparo'];
        $grafico05Resp['nota_qualidade_reparo'][] = $resposta['nota_qualidade_reparo'];
        $grafico05Resp['nota_atencao'][] = $resposta['nota_atencao'];
        $grafico05Resp['nota_explicacao'][] = $resposta['nota_explicacao'];
        $grafico05Resp['nota_aspecto'][] = $resposta['nota_aspecto'];
        $grafico05Resp['nota_geral'][] = $resposta['nota_geral'];

        if(trim($resposta['marca']) != "" && $resposta['marca'] != NULL){
            $grafico05Marcas[] = $resposta['marca'];
        }
    }

    $nomesMarcas = array_unique($grafico05Marcas);

    foreach ($nomesMarcas as $keyx => $valuex) { //hd_chamado=3110652
        if(strlen($valuex) == 0){
            unset($nomesMarcas[$keyx]);
        }
    }

    asort($nomesMarcas);


    $sql = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica
        AND     marca IN (" . implode(',', $nomesMarcas) . ")
    ";


    $res = pg_query($con, $sql);

    $arrayMarcas = pg_fetch_all($res);


    if (!empty($marca) && $marca != 0) {
        $valorMarcaEscolhida = pg_fetch_all_columns($res, 0);
        $nomeMarcaEscolhida = pg_fetch_all_columns($res, 1);

        foreach ($valorMarcaEscolhida as $chave => $valor) {

            if ($valor == $marca) {
                $marcaEscolhida = $nomeMarcaEscolhida[$chave];
            }
        }
    }



    $contaTempoReparo = array_count_values($grafico05Resp['nota_tempo_reparo']);
    $contaPrecoReparo = array_count_values($grafico05Resp['nota_preco_reparo']);
    $contaQualidadeReparo = array_count_values($grafico05Resp['nota_qualidade_reparo']);
    $contaAtencao = array_count_values($grafico05Resp['nota_atencao']);
    $contaExplicacao = array_count_values($grafico05Resp['nota_explicacao']);
    $contaAspecto = array_count_values($grafico05Resp['nota_aspecto']);
    $contaGeral = array_count_values($grafico05Resp['nota_geral']);

    $satisfacaoAcertar = array(
        "nota_tempo_reparo",
        "nota_preco_reparo",
        "nota_qualidade_reparo",
        "nota_atencao",
        "nota_explicacao",
        "nota_aspecto",
        "nota_geral"
    );

    $tradSatisfacao = array(
        "Tempo de reparo",
        "Preço do reparo",
        "Qualidade do reparo",
        "Atenção do atendente",
        "Explicação do reparo",
        "Aspecto visual da assistência",
        "Satisfação geral"
    );


    $comparaKey = array(
        "plenamente_satisfeito" => "Totalmente Satisfeito",
        "muito_satisfeito" => "Bastante Satisfeito",
        "satisfeito" => "Neutro",
        "pouco_satisfeito" => "Pouco Satisfeito",
        "insatisfeito" => "Nada Satisfeito"
    );
    $graficoSatisfacao = json_encode(array_keys($grafico05Resp));
    $graficoCategoriesSatisfacao = str_replace($satisfacaoAcertar, $tradSatisfacao, $graficoSatisfacao);
    $graficoCategoriesSatisfacao = utf8_encode($graficoCategoriesSatisfacao);
    $graficoCategoriesSatisfacao = json_decode($graficoCategoriesSatisfacao);

    foreach ($contaTempoReparo as $chave => $valor) {
        switch ($chave) {
            case "plenamente_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;

                break;
            case "muito_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;

                break;
            case "satisfeito":
                $graficoSeries[$chave][] = (int)$valor;

                break;
            case "pouco_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;

                break;
            case "insatisfeito":
                $graficoSeries[$chave][] = (int)$valor;

                break;
        }
    }
    $arrCompara1 = array_diff_key($comparaKey, $contaTempoReparo);
    if (count($arrCompara1) > 0) {
        foreach ($arrCompara1 as $k => $v) {
            $graficoSeries[$k][] = (int)0;
        }
    }

    foreach ($contaPrecoReparo as $chave => $valor) {
        switch ($chave) {
            case "plenamente_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "muito_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "pouco_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "insatisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
        }
    }

    $arrCompara2 = array_diff_key($comparaKey, $contaPrecoReparo);
    if (count($arrCompara2) > 0) {
        foreach ($arrCompara2 as $k => $v) {
            $graficoSeries[$k][] = (int)0;
        }
    }

    foreach ($contaQualidadeReparo as $chave => $valor) {
        switch ($chave) {
            case "plenamente_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "muito_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "pouco_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "insatisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
        }
    }
    $arrCompara3 = array_diff_key($comparaKey, $contaQualidadeReparo);
    if (count($arrCompara3) > 0) {
        foreach ($arrCompara3 as $k => $v) {
            $graficoSeries[$k][] = (int)0;
        }
    }

    foreach ($contaAtencao as $chave => $valor) {
        switch ($chave) {
            case "plenamente_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "muito_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "pouco_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "insatisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
        }
    }
    $arrCompara4 = array_diff_key($comparaKey, $contaAtencao);
    if (count($arrCompara4) > 0) {
        foreach ($arrCompara4 as $k => $v) {
            $graficoSeries[$k][] = (int)0;
        }
    }

    foreach ($contaExplicacao as $chave => $valor) {
        switch ($chave) {
            case "plenamente_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "muito_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "pouco_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "insatisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
        }
    }
    $arrCompara5 = array_diff_key($comparaKey, $contaExplicacao);
    if (count($arrCompara5) > 0) {
        foreach ($arrCompara5 as $k => $v) {
            $graficoSeries[$k][] = (int)0;
        }
    }

    foreach ($contaAspecto as $chave => $valor) {
        switch ($chave) {
            case "plenamente_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "muito_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "pouco_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "insatisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
        }
    }
    $arrCompara6 = array_diff_key($comparaKey, $contaAspecto);
    if (count($arrCompara6) > 0) {
        foreach ($arrCompara6 as $k => $v) {
            $graficoSeries[$k][] = (int)0;
        }
    }

    foreach ($contaGeral as $chave => $valor) {
        switch ($chave) {
            case "plenamente_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "muito_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "pouco_satisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
            case "insatisfeito":
                $graficoSeries[$chave][] = (int)$valor;
                break;
        }
    }
    $arrCompara7 = array_diff_key($comparaKey, $contaGeral);
    if (count($arrCompara7) > 0) {
        foreach ($arrCompara7 as $k => $v) {
            $graficoSeries[$k][] = (int)0;
        }
    }

    foreach ($graficoSeries['plenamente_satisfeito'] as $kk => $vv) {
        $auxUp = $graficoSeries['plenamente_satisfeito'][$kk] + $graficoSeries['muito_satisfeito'][$kk];
        $auxDown = $graficoSeries['pouco_satisfeito'][$kk] + $graficoSeries['insatisfeito'][$kk];

        $somaNss = $auxUp - $auxDown;
        $somaTotal = $auxUp + $auxDown + $graficoSeries['satisfeito'][$kk];
        $nss[] = (float)number_format((($somaNss * 100) / $somaTotal), 2);
    }

    foreach ($graficoSeries as $tipo => $valores) {
        switch ($tipo) {
            case "plenamente_satisfeito":
                $graficoSeriesNotas[0] = array(
                    "name" => $comparaKey[$tipo],
                    "type" => "column",
                    "data" => $valores
                );
                break;
            case "muito_satisfeito":
                $graficoSeriesNotas[1] = array(
                    "name" => $comparaKey[$tipo],
                    "type" => "column",
                    "data" => $valores
                );
                break;
            case "satisfeito":
                $graficoSeriesNotas[2] = array(
                    "name" => $comparaKey[$tipo],
                    "type" => "column",
                    "data" => $valores
                );
                break;
            case "pouco_satisfeito":
                $graficoSeriesNotas[3] = array(
                    "name" => $comparaKey[$tipo],
                    "type" => "column",
                    "data" => $valores
                );
                break;
            case "insatisfeito":
                $graficoSeriesNotas[4] = array(
                    "name" => $comparaKey[$tipo],
                    "type" => "column",
                    "data" => $valores
                );
                break;
        }
    }

    ksort($graficoSeriesNotas);
    $graficoSeriesNotas[] = array(
        "name" => "NSS",
        "data" => $nss,
        "yAxis" => 1,
        "tooltip" => array(
            "valueSuffix" => "%"
        )
    );

    $graficoSeriesNotas = $graficoSeriesNotas;

    if (is_array($graficoCategoriesSatisfacao)) {

        return json_encode(
            array(
                "categories" => $graficoCategoriesSatisfacao,
                "series" => $graficoSeriesNotas,
                "arrayMarcas" => $arrayMarcas,
                "marcaEscolhida" => $marcaEscolhida,
                "max_total"         => $somaTotal
            )
        );
    } else {
        return "erro";
    }
}

/**
 * 6º Graico - MAPA DE CALOR
 * -- Mostra a relação das respostas de satisfação
 * de atendimento com a promoção da marca, mostrando a
 * LEALDADE do consumidor perante o equipamento
 */
function geraGrafico06($json, $cidade = null, $posto = null)
{

    foreach ($json as $respostas) {
        $respostaJson = $respostas['observacao'];
        #$resposta = json_decode(stripslashes($respostaJson),TRUE);
        $resposta = json_decode($respostaJson,TRUE);

        if (!empty($posto)) {
            if (urlencode($posto) != $resposta['posto']) {
                continue;
            }
        } else {
            if (!empty($cidade)) {
                if (urlencode($cidade) != strtoupper($resposta['cidade'])) {
                    continue;
                }
            }
        }

        switch ($resposta['recomendacao']) {
            case 0:
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                $grafico06Lealdade['nota_tempo_reparo']["detractor"][] = $resposta['nota_tempo_reparo'];
                $grafico06Lealdade['nota_preco_reparo']["detractor"][] = $resposta['nota_preco_reparo'];
                $grafico06Lealdade['nota_qualidade_reparo']["detractor"][] = $resposta['nota_qualidade_reparo'];
                $grafico06Lealdade['nota_atencao']["detractor"][] = $resposta['nota_atencao'];
                $grafico06Lealdade['nota_explicacao']["detractor"][] = $resposta['nota_explicacao'];
                $grafico06Lealdade['nota_aspecto']["detractor"][] = $resposta['nota_aspecto'];
                $grafico06Lealdade['nota_geral']["detractor"][] = $resposta['nota_geral'];

                break;
            case 7:
            case 8:
                $grafico06Lealdade['nota_tempo_reparo']["passivo"][] = $resposta['nota_tempo_reparo'];
                $grafico06Lealdade['nota_preco_reparo']["passivo"][] = $resposta['nota_preco_reparo'];
                $grafico06Lealdade['nota_qualidade_reparo']["passivo"][] = $resposta['nota_qualidade_reparo'];
                $grafico06Lealdade['nota_atencao']["passivo"][] = $resposta['nota_atencao'];
                $grafico06Lealdade['nota_explicacao']["passivo"][] = $resposta['nota_explicacao'];
                $grafico06Lealdade['nota_aspecto']["passivo"][] = $resposta['nota_aspecto'];
                $grafico06Lealdade['nota_geral']["passivo"][] = $resposta['nota_geral'];

                break;
            case 9:
            case 10:
                $grafico06Lealdade['nota_tempo_reparo']["promotor"][] = $resposta['nota_tempo_reparo'];
                $grafico06Lealdade['nota_preco_reparo']["promotor"][] = $resposta['nota_preco_reparo'];
                $grafico06Lealdade['nota_qualidade_reparo']["promotor"][] = $resposta['nota_qualidade_reparo'];
                $grafico06Lealdade['nota_atencao']["promotor"][] = $resposta['nota_atencao'];
                $grafico06Lealdade['nota_explicacao']["promotor"][] = $resposta['nota_explicacao'];
                $grafico06Lealdade['nota_aspecto']["promotor"][] = $resposta['nota_aspecto'];
                $grafico06Lealdade['nota_geral']["promotor"][] = $resposta['nota_geral'];

                break;
        }
    }

    foreach ($grafico06Lealdade as $notas => $pontuacoes) {
        foreach ($pontuacoes as $pont => $valor) {
            $resp[$notas][$pont] = array_count_values($pontuacoes[$pont]);
        }
    }

    foreach ($resp as $valores) {
        foreach ($valores as $ch => $vl) {
            switch ($ch) {
                case "promotor":
                    $result[$ch]["plenamente_satisfeito"] += $vl["plenamente_satisfeito"];
                    $result[$ch]["muito_satisfeito"] += $vl["muito_satisfeito"];
                    $result[$ch]["satisfeito"] += $vl["satisfeito"];
                    $result[$ch]["pouco_satisfeito"] += $vl["pouco_satisfeito"];
                    $result[$ch]["insatisfeito"] += $vl["insatisfeito"];

                    break;
                case "detractor":
                    $result[$ch]["plenamente_satisfeito"] += $vl["plenamente_satisfeito"];
                    $result[$ch]["muito_satisfeito"] += $vl["muito_satisfeito"];
                    $result[$ch]["satisfeito"] += $vl["satisfeito"];
                    $result[$ch]["pouco_satisfeito"] += $vl["pouco_satisfeito"];
                    $result[$ch]["insatisfeito"] += $vl["insatisfeito"];

                    break;
                case "passivo":
                    $result[$ch]["plenamente_satisfeito"] += $vl["plenamente_satisfeito"];
                    $result[$ch]["muito_satisfeito"] += $vl["muito_satisfeito"];
                    $result[$ch]["satisfeito"] += $vl["satisfeito"];
                    $result[$ch]["pouco_satisfeito"] += $vl["pouco_satisfeito"];
                    $result[$ch]["insatisfeito"] += $vl["insatisfeito"];

                    break;
            }
        }
    }

    $verificaZerado = array_keys($result);
    $temPromotor = 1;
    $temPassivo = 1;
    $temDetractor = 1;

    foreach ($verificaZerado as $ver) {
        switch ($ver) {
            case "promotor":
                $temPromotor = 0;
                break;
            case "passivo":
                $temPassivo = 0;
                break;
            case "detractor":
                $temDetractor = 0;
                break;
        }
    }

    if ($temPromotor == 1) {
        $result["promotor"]["plenamente_satisfeito"] = (int)0;
        $result["promotor"]["muito_satisfeito"] = (int)0;
        $result["promotor"]["satisfeito"] = (int)0;
        $result["promotor"]["pouco_satisfeito"] = (int)0;
        $result["promotor"]["insatisfeito"] = (int)0;
    }

    if ($temPassivo == 1) {
        $result["passivo"]["plenamente_satisfeito"] = (int)0;
        $result["passivo"]["muito_satisfeito"] = (int)0;
        $result["passivo"]["satisfeito"] = (int)0;
        $result["passivo"]["pouco_satisfeito"] = (int)0;
        $result["passivo"]["insatisfeito"] = (int)0;

    }

    if ($temDetractor == 1) {
        $result["detractor"]["plenamente_satisfeito"] = (int)0;
        $result["detractor"]["muito_satisfeito"] = (int)0;
        $result["detractor"]["satisfeito"] = (int)0;
        $result["detractor"]["pouco_satisfeito"] = (int)0;
        $result["detractor"]["insatisfeito"] = (int)0;

    }

    foreach ($result as $posicao => $satisfacao) {
        switch ($posicao) {
            case "promotor":
                $posX = (int)0;
                break;
            case "passivo":
                $posX = (int)1;
                break;
            case "detractor":
                $posX = (int)2;
                break;
        }
        foreach ($satisfacao as $satis => $val) {
            switch ($satis) {
                case "plenamente_satisfeito":
                    $posY = (int)0;
                    break;
                case "muito_satisfeito":
                    $posY = (int)1;
                    break;
                case "satisfeito":
                    $posY = (int)2;
                    break;
                case "pouco_satisfeito":
                    $posY = (int)3;
                    break;
                case "insatisfeito":
                    $posY = (int)4;
                    break;
            }
            $graficoHeat[] = array($posX, $posY, (int)$val);
        }
    }

    $recebeGraficoHeat = "[" . implode(",", $graficoHeat) . "]";
    $graficoSeriesHeat[] = array(
        "name" => utf8_encode("Pontuação por Satisfação"),
        "borderWidth" => (int)1,
        "data" => $graficoHeat,
        "dataLabels" => array(
            "enabled" => (bool)true,
            "color" => "#000000"
        )
    );

    $comparaKey = array(
        "plenamente_satisfeito" => "Totalmente Satisfeito",
        "muito_satisfeito" => "Bastante Satisfeito",
        "satisfeito" => "Neutro",
        "pouco_satisfeito" => "Pouco Satisfeito",
        "insatisfeito" => "Nada Satisfeito"
    );

    $graficoCategoriaHeat = array_values($comparaKey);

    if (is_array($graficoCategoriaHeat)) {
        return json_encode(
            array(
                "categories" => $graficoCategoriaHeat,
                "series" => $graficoSeriesHeat
            )
        );
    } else {
        return "erro";
    }
}

if (filter_input(INPUT_POST, 'bt_periodo')) {

    if (!empty($_POST["paises"])) {
        $pais = $_POST["paises"];

        if (array_search("CCA", $pais) !== false) {
            unset($pais[array_search("CCA", $pais)]);
            $pais = array_merge($pais, array('PA', 'GY', 'BS', 'SR', 'JY', 'HT', 'KY', 'AW', 'VE', 'CR', 'HN', 'NI', 'GT', 'DO', 'SV', 'PR', 'TT', 'EC'));
            $pais = array_unique($pais);
        }

        $pais = json_encode($pais);
    } else {
        $pais = 0;
        $msg_erro['campos'][] = "pais";
    }

    //Validação Datas
    if (filter_input(INPUT_POST, "data_inicio")) {
        $data_inicio = filter_input(INPUT_POST, "data_inicio");
        $data_inicial = $data_inicio;
        if (empty($data_inicial) OR !verificaDataValida($data_inicial)) {
            $msg_erro["campos"][] = "data_inicio";
        }
        $aux_data_inicial = implode("-", array_reverse(explode("/", $data_inicial)));
    } else {
        $msg_erro["campos"][] = "data_inicio";
    }


    if (filter_input(INPUT_POST, "data_fim")) {
        $data_fim = filter_input(INPUT_POST, "data_fim");
        $data_final = $data_fim;
        if (empty($data_final) OR !verificaDataValida($data_final)) {
            $msg_erro["campos"][] = "data_fim";
        }
        $aux_data_final = implode("-", array_reverse(explode("/", $data_final)));
    } else {
        $msg_erro["campos"][] = "data_fim";
    }

    if (count($msg_erro["campos"]) > 0) {
        $msg_erro["msg"][] = "Preencher campos obrigatórios";
    }

    if (count($msg_erro["msg"]) == 0) {

        if ($aux_data_inicial > $aux_data_final) {

            $msg_erro["msg"][] = "Intervalo de Datas Incorreto.";

        } else {

            $sqlX = "SELECT '$aux_data_inicial'::date + interval '6 months' > '$aux_data_final'";
            $resX = pg_query($con, $sqlX);
            $periodo_meses = pg_fetch_result($resX, 0, 0);

            if ($periodo_meses == 'f') {

                $msg_erro["msg"][] = "AS DATAS DEVEM SER NO MÁXIMO 6 MESES";
                $msg_erro["campos"][] = "data_inicio";
                $msg_erro["campos"][] = "data_fim";

            }
        }
    }
}

if (filter_input(INPUT_POST, "grafico", FILTER_VALIDATE_INT)) {
    $dataInicioAjax = filter_input(INPUT_POST, "data_inicio");
    $dataFimAjax = filter_input(INPUT_POST, "data_fim");
    $paisAjax = $_POST["pais"];
    $cidadeAjax = filter_input(INPUT_POST, "cidade");
    $graficoAjax = filter_input(INPUT_POST, "grafico", FILTER_VALIDATE_INT);
    $marcaAjax = filter_input(INPUT_POST, "marca");
    $postoAjax = filter_input(INPUT_POST, "posto");

    echo carregaDados($con, $login_fabrica, $dataInicioAjax, $dataFimAjax, $paisAjax, $graficoAjax, $cidadeAjax, $postoAjax, $marcaAjax);
    exit;
}

if (filter_input(INPUT_POST, 'ajax')) {
    $ajaxType = filter_input(INPUT_POST, 'ajaxType');
    switch ($ajaxType) {
        case "buscaCidade":
            $pais = filter_input(INPUT_POST, 'pais');
            if($pais == 'BR'){
                $sql = "
                    SELECT  DISTINCT
                            upper(trim(tbl_posto_fabrica.contato_cidade)) AS cidade
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica = 1
                                                AND tbl_posto.pais = '$pais'
              ORDER BY      upper(trim(tbl_posto_fabrica.contato_cidade))                 ";
            }else{
                $sql = "
                    SELECT  DISTINCT
                            upper(trim(tbl_posto.cidade)) as cidade
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                AND tbl_posto_fabrica.fabrica = 1
                                                AND tbl_posto.pais = '$pais'
              ORDER BY      upper(trim(tbl_posto.cidade))                 ";

            }
            $res = pg_query($con, $sql);
            $conta = pg_num_rows($res);
            for ($c = 0; $c < $conta; $c++) {
                $cidade = pg_fetch_result($res, $c, cidade);
                $cidade = htmlentities($cidade);
                $retorno[] = array("cidade" => $cidade);
            }
            break;
        case "buscaPosto":
            $cidade = utf8_decode(filter_input(INPUT_POST, 'cidade'));
            $sql = "
                SELECT  DISTINCT
                        tbl_posto.nome
                FROM    tbl_posto_fabrica
                JOIN    tbl_posto   ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
                                    AND (
                                            tbl_posto.cidade                    = '$cidade'
                                        OR  tbl_posto_fabrica.contato_cidade    = '$cidade'
                                        )
          ORDER BY      tbl_posto.nome
            ";
            $res = pg_query($con, $sql);
            $conta = pg_num_rows($res);
            for ($c = 0; $c < $conta; $c++) {
                $posto = pg_fetch_result($res, $c, nome);
                $retorno[] = array("nome" => $posto);
            }
            break;
    }

    echo json_encode($retorno);
    exit;
}

$title = "GRÁFICOS DA PESQUISA DE SATISFAÇÃO ";
$layout_menu = 'callcenter';

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "maskedinput",
    "multiselect",
    "select2"
);

include "plugin_loader.php";
?>
    <script src="js/highcharts_4.2.5.js"></script>
    <script src="js/modules/heatmap_4.2.5.src.js"></script>
    <script src="js/modules/data.js"></script>
    <script src="js/modules/exporting.js"></script>
    <style type="text/css">
        .topo {
            background-color: #CCC;
        }

        .tabelaGrafico {
            width: 400px;
            text-align: center;
            margin: 0 auto;
        }

        .tabelaGrafico tr:first-child {
            border-top: 1px solid #333;
        }

        .tabelaGrafico th {
            border-left: 1px solid #333;
            border-right: 1px solid #333;
        }

        .tabelaGrafico td {
            border: 1px solid #333;
        }

        .last {
            background-color: #CCC;
            font-weight: bold;
        }

        #casoNecessite,
        #mostraGrafico,
        #mostraTabela {
            margin: 0 auto;
            display: none;
        }

        #mostraGrafico {
            width: 800px;
        }

        #casoNecessite {
            width: 200px;
            padding-top: 10px;
        }
    </style>
    <script type="text/javascript">
        <?php
        if (filter_input(INPUT_POST, 'bt_periodo')) {
        ?>
        function grafico01(marca) {
            var optionMarcas = "<select id='marcasGrafico01' name='marcasGrafico01'>";
            optionMarcas += "<option value=''>Selecione a marca específica</option>";
            optionMarcas += "<option value='0'>Todas</option>";

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: "POST",
                dataType: "JSON",
                data: {
                    data_inicio: "<?=$data_inicio;?>",
                    data_fim: "<?=$data_fim;?>",
                    pais:<?=$pais;?>,
                    cidade: "<?=$cidade;?>",
                    posto: "<?=$posto;?>",
                    marca: marca,
                    grafico: 1
                },
                beforeSend: function () {
                    if ($("#mostraGrafico").highcharts() !== undefined) {
                        $("#mostraGrafico").highcharts().destroy();
                    }

                    $("#mostraGrafico").text("Aguardando carregamento...")
                        .css({
                            "width": "700px",
                            "height": "400px"
                        });
                    $("#casoNecessite").css("display", "none");
                    $("#mostraTabela").css("display", "none");
                }
            })
                .done(function (result) {
                    var subtitle;
                    $.each(result.arrayMarcas, function (k, v) {
                        optionMarcas += "<option value='" + v.marca + "'>" + v.nome + "</option>";
                    });
                    optionMarcas += "</select>";

                    if (result.marcaEscolhida != null) {
                        subtitle = "NPS " + result.marcaEscolhida + ": " + result.nps + "%";
                    } else {
                        subtitle = "NPS Total: " + result.nps + "%";
                    }
                    $('#casoNecessite').css("display", "block").html(optionMarcas);
                    $("#mostraGrafico").highcharts({
                        title: {
                            text: 'Resultado por MARCAS'
                        },
                        subtitle: {
                            text: subtitle
                        },
                        yAxis: {
                            title: {
                                text: 'Pontuações'
                            }
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: false
                                },
                                showInLegend: true
                            }
                        },
                        series: result.series
                    }, function (chart) {
                        var table = "<table>";
                        var ordem = [];
                        var zerada = "";
                        $.each(chart.series, function (i, val) {
                            table += "<tr><th colspan='3'>" + this.name + "</th></tr>";
                            $.each(this.data, function (k, val2) {
                                switch (this.name) {
                                    case "Promotor":
                                        ordem[0] = {};
                                        ordem[0]["name"] = this.name;
                                        ordem[0]["y"] = this.y;
                                        ordem[0]["percentage"] = this.percentage.toFixed(2);
                                        this.update({color: '#68A042'});
                                        break;
                                    case "Passivo":
                                        ordem[1] = {};
                                        ordem[1]["name"] = this.name;
                                        ordem[1]["y"] = this.y;
                                        ordem[1]["percentage"] = this.percentage.toFixed(2);
                                        this.update({color: '#FFC000'});
                                        break;
                                    case "Detractor":
                                        ordem[2] = {};
                                        ordem[2]["name"] = this.name;
                                        ordem[2]["y"] = this.y;
                                        ordem[2]["percentage"] = this.percentage.toFixed(2);
                                        this.update({color: '#FF0000'});
                                        break;
                                }
                            });
                            var ultima = 0;
                            $.each(ordem, function (linha, valor) {
                                ultima = linha;
                                if (typeof this.y == "undefined") {
                                    if (linha == 0) {
                                        var name = "Promotor";
                                    } else if (linha == 1) {
                                        var name = "Passivo";
                                    }
                                    table += "<tr><td>" + name + "</td>";
                                    table += "<td>0</td>";
                                    table += "<td>0%</td></tr>";
                                    return true;
                                }
                                table += "<tr><td>" + this.name + "</td>";
                                table += "<td>" + this.y + "</td>";
                                table += "<td>" + this.percentage + "%</td></tr>";
                            });

                            if (ultima == 1) {
                                table += "<tr><td>Detractor</td>";
                                table += "<td>0</td>";
                                table += "<td>0%</td></tr>";
                            }

                            table += "<tr style='font-weight:bold;'><td>Total</td>";
                            table += "<td>" + this.total + "</td>";
                            table += "<td>100%</td></tr>";
                        });
                        table += "</table>";
                        $("#mostraTabela").html(table).css({
                            "display": "block"
                        });
                        $("#mostraTabela table").addClass("tabelaGrafico");
                        $("#mostraTabela table tr th").addClass("topo");
                    });
                })
                .fail(function () {
                    $("#mostraGrafico").text("Não foi possível carregar o gráfico.")
                        .css({
                            "background-color": "#F00",
                            "font": "bold 16px Arial",
                            "color": "#FFF",
                            "width": "700px",
                            "height": "20px",
                            "margin": "auto",
                            "text-align": "center"
                        });
                });
        }

        function grafico02(marca) {

            var optionMarcas = "<select id='marcasGrafico02' name='marcasGrafico02'>";
            optionMarcas += "<option value=''>Selecione a marca específica</option>";
            optionMarcas += "<option value='0'>Todas</option>";

            Highcharts.setOptions({
                colors: ['#68A042', '#FFC000', '#FF0000', '#FF8C00']
            });

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: "POST",
                dataType: "JSON",
                data: {
                    data_inicio: "<?=$data_inicio;?>",
                    data_fim: "<?=$data_fim;?>",
                    pais:<?=$pais;?>,
                    cidade: "<?=$cidade;?>",
                    posto: "<?=$posto;?>",
                    marca: marca,
                    grafico: 2
                },
                beforeSend: function () {
                    if ($("#mostraGrafico").highcharts() !== undefined) {
                        $("#mostraGrafico").highcharts().destroy();
                    }

                    $("#mostraGrafico").text("Aguardando carregamento...")
                        .css({
                            "height": "400px"
                        });
                    $("#casoNecessite").css("display", "none");
                    $("#mostraTabela").css("display", "none");
                }
            })
                .done(function (result) {
                    var subtitle;
                    $.each(result.arrayMarcas, function (k, v) {
                        optionMarcas += "<option value='" + v.marca + "'>" + v.nome + "</option>";
                    });
                    optionMarcas += "</select>";

                    if (result.marcaEscolhida != null) {
                        subtitle = "Nível de confiabilidade da marca " + result.marcaEscolhida;
                    } else {
                        subtitle = "Nível de confiabilidade de cada equipamento";
                    }
                    $('#casoNecessite').css("display", "block").html(optionMarcas);
                    $('#mostraGrafico').highcharts({
                        chart: {
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'Resultado por EQUIPAMENTO'
                        },
                        subtitle: {
                            text: subtitle
                        },
                        xAxis: [{
                            labels: {
                                autoRotationLimit: 20,
                                align: "center",
                                padding: 10
                            },
                            categories: result.categories,
                            crosshair: true
                        }],
                        yAxis: [{
                            min: 0,
                            max: 100,
                            title: {
                                text: 'Pontuação'
                            },
                            labels: {
                                format: '{value} %'
                            },
                            stackLabels: {
                                enabled: false,
                                style: {
                                    fontWeight: 'bold',
                                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                                }
                            }
                        }, {
                            max: 100,
                            title: {
                                text: 'Porcentagem NPS',
                                style: {
                                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                                }
                            },
                            labels: {
                                format: '{value} %',
                                style: {
                                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                                }
                            },
                            opposite: true
                        }],
                        legend: {
                            align: 'center',
                            x: 30,
                            verticalAlign: 'bottom',
                            y: 15,
                            floating: false,
                            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
                            borderColor: '#CCC',
                            borderWidth: 1,
                            shadow: false
                        },
                        tooltip: {
                            formatter: function () {
                                var header = "<b>" + this.x + "</b><br/>";
                                var s = "";
                                var nps = "";
                                var soma = 0;

                                $.each(this.points, function () {

                                    if (this.series.name != "NPS") {
                                        s += '<br/><b style="color:' + this.color + '">' + this.series.name + '</b>: ' +
                                            this.percentage.toFixed(2) + "%";
                                        soma += parseFloat(this.percentage.toFixed(2));

                                    } else {
                                        nps = '<br/><b style="color:' + this.color + '">' + this.series.name + '</b>: ' +
                                            this.y + '%';
                                    }

                                });
                                s += nps;
                                s += "<br/><b>Total: " + soma;
                                return header + s;
                            },
                            shared: true
                        },
                        plotOptions: {
                            column: {
                                stacking: 'normal',
                                dataLabels: {
                                    enabled: false,
                                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                                    style: {
                                        textShadow: '0 0 3px black'
                                    }
                                }
                            }
                        },
                        series: result.series
                    }, function (chart) {
                        var coletaData = [];
                        var arrayTabela = {};
                        var equip;
                        var pont;
                        var valor;
                        var table = "<table><tr>";
                        table += "<th>Equipamento</th>";
                        table += "<th>Promotor</th>";
                        table += "<th>Passivo</th>";
                        table += "<th>Detractor</th>";
                        table += "<th>NPS</th>";
                        table += "</tr>";
                        $.each(chart.series, function () {
                            $.each(this.data, function () {
                                coletaData.push(this.category + "|" + this.series.name + "|" + this.y);
                            });
                        });

                        var tamanho = 0;
                        $.each(coletaData, function () {
                            var linha = this.split("|");
                            if (typeof arrayTabela[linha[0]] == "undefined") {
                                arrayTabela[linha[0]] = {};
                            }

                            arrayTabela[linha[0]][linha[1]] = linha[2];
                            tamanho++;
                        });

                        var agora = 0;
                        $.each(arrayTabela, function (i, val) {
                            table += "<tr>";
                            table += "<td>" + i + "</td>";
                            table += "<td>" + result.seriesTabela[0]["Promotor"][agora] + "</td>";
                            table += "<td>" + result.seriesTabela[1]["Passivo"][agora] + "</td>";
                            table += "<td>" + result.seriesTabela[2]["Detractor"][agora] + "</td>";
                            table += "<td>" + this.NPS + "</td>";
                            table += "</tr>";

                            if (agora < tamanho) {
                                agora++;
                            }
                        });
                        table += "</table>";
                        $("#mostraTabela").html(table).css({
                            "display": "block"
                        });
                        $("#mostraTabela table").addClass("tabelaGrafico");
                        $("#mostraTabela table tr th").addClass("topo");

                    });
                })
                .fail(function () {
                    $("#mostraGrafico").text("Não foi possível carregar o gráfico.")
                        .css({
                            "background-color": "#F00",
                            "font": "bold 16px Arial",
                            "color": "#FFF",
                            "width": "700px",
                            "height": "20px",
                            "margin": "auto",
                            "text-align": "center"
                        });
                });
        }

        function grafico03(marca) {

            var optionMarcas = "<select id='marcasGrafico03' name='marcasGrafico03'>";
            optionMarcas += "<option value=''>Selecione a marca específica</option>";
            optionMarcas += "<option value='0'>Todas</option>";

            Highcharts.setOptions({
                colors: ['#1E90FF']
            });

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: "POST",
                dataType: "JSON",
                data: {
                    data_inicio: "<?=$data_inicio;?>",
                    data_fim: "<?=$data_fim;?>",
                    pais:<?=$pais;?>,
                    cidade: "<?=$cidade;?>",
                    posto: "<?=$posto;?>",
                    marca: marca,
                    grafico: 3
                },
                beforeSend: function () {
                    if ($("#mostraGrafico").highcharts() !== undefined) {
                        $("#mostraGrafico").highcharts().destroy();
                    }

                    $("#mostraGrafico").text("Aguardando carregamento...")
                        .css({
                            "height": "400px"
                        });
                    $("#casoNecessite").css("display", "none");
                    $("#mostraTabela").css("display", "none");
                }
            })
                .done(function (result) {
                    var subtitle;
                    $.each(result.arrayMarcas, function (k, v) {
                        optionMarcas += "<option value='" + v.marca + "'>" + v.nome + "</option>";
                    });
                    optionMarcas += "</select>";

                    if (result.marcaEscolhida != null) {
                        subtitle = "Baseado no motivo do cliente em pontuar o atendimento da marca " + result.marcaEscolhida;
                    } else {
                        subtitle = "Baseado no motivo do cliente em pontuar o atendimento";
                    }
                    $('#casoNecessite').css("display", "block").html(optionMarcas);
                    $('#mostraGrafico').highcharts({
                        chart: {
                            type: 'bar'
                        },
                        title: {
                            text: 'Razão da pontuação'
                        },
                        subtitle: {
                            text: subtitle
                        },
                        xAxis: {
                            categories: result.categories,
                            title: {
                                text: null
                            }
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: '%',
                                align: 'high'
                            },
                            labels: {
                                overflow: 'justify'
                            }
                        },
                        tooltip: {
                            valueSuffix: ' %'
                        },
                        plotOptions: {
                            bar: {
                                dataLabels: {
                                    enabled: true,
                                    format: '{y}%'
                                }
                            }
                        },
                        series: [{
                            name: "Porcentagem",
                            data: result.series.porcentagem
                        }]
                    }, function (chart) {
                        var acumulaValor = 0;
                        var table = "<table><tr>";
                        table += "<th>Motivo</th>";
                        table += "<th>qtde</th>";
                        table += "<th>%</th>";
                        table += "</tr>";
                        $.each(chart.series, function () {
                            $.each(this.data, function (i, val) {
                                acumulaValor += result.series.valor[i];
                                table += "<tr>";
                                table += "<td>" + this.category + "</td>";
                                table += "<td>" + result.series.valor[i] + "</td>";
                                table += "<td>" + this.y + "</td>";
                                table += "</tr>";
                            });
                        });
                        table += "<tr>";
                        table += "<td>Total</td>";
                        table += "<td>" + acumulaValor + "</td>";
                        table += "<td>100</td>";
                        table += "</tr>";
                        table += "</table>";
                        $("#mostraTabela").html(table).css({
                            "display": "block"
                        });
                        $("#mostraTabela table").addClass("tabelaGrafico");
                        $("#mostraTabela table tr th").addClass("topo");
                        $("#mostraTabela table tr:last-child").addClass("last");
                    });
                })
                .fail(function () {
                    $("#mostraGrafico").text("Não foi possível carregar o gráfico.")
                        .css({
                            "background-color": "#F00",
                            "font": "bold 16px Arial",
                            "color": "#FFF",
                            "width": "700px",
                            "height": "20px",
                            "margin": "auto",
                            "text-align": "center"
                        });
                });
        }

        function grafico04(marca) {

            var optionMarcas = "<select id='marcasGrafico04' name='marcasGrafico04'>";
            optionMarcas += "<option value=''>Selecione a marca específica</option>";
            optionMarcas += "<option value='0'>Todas</option>";

            Highcharts.setOptions({
                colors: ['#68A042', '#FFC000', '#FF0000']
            });

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: "POST",
                dataType: "JSON",
                data: {
                    data_inicio: "<?=$data_inicio;?>",
                    data_fim: "<?=$data_fim;?>",
                    pais:<?=$pais;?>,
                    cidade: "<?=$cidade;?>",
                    posto: "<?=$posto;?>",
                    marca: marca,
                    grafico: 4
                },
                beforeSend: function () {
                    if ($("#mostraGrafico").highcharts() !== undefined) {
                        $("#mostraGrafico").highcharts().destroy();
                    }

                    $("#mostraGrafico").text("Aguardando carregamento...")
                        .css({
                            "height": "400px"
                        });
                    $("#casoNecessite").css("display", "none");
                    $("#mostraTabela").css("display", "none");
                }
            })
                .done(function (result) {
                    var subtitle;
                    $.each(result.arrayMarcas, function (k, v) {
                        optionMarcas += "<option value='" + v.marca + "'>" + v.nome + "</option>";
                    });
                    optionMarcas += "</select>";

                    if (result.marcaEscolhida != null) {
                        subtitle = "Nível de confiabilidade com o passar dos dias para resolução da OS da marca " + result.marcaEscolhida;
                    } else {
                        subtitle = "Nível de confiabilidade com o passar dos dias para resolução da OS";
                    }
                    $('#casoNecessite').css("display", "block").html(optionMarcas);
                    $('#mostraGrafico').highcharts({
                        chart: {
                            type: 'column'
                        },
                        title: {
                            text: 'Resultado por DIAS DE ATENDIMENTO '
                        },
                        subtitle: {
                            text: subtitle
                        },
                        xAxis: {
                            title: {
                                text: 'Dias em Aberto'
                            },
                            categories: result.categories
                        },
                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Pontuação'
                            },
                            stackLabels: {
                                enabled: false,
                                style: {
                                    fontWeight: 'bold',
                                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                                }
                            }
                        },
                        legend: {
                            align: 'center',
                            x: 30,
                            verticalAlign: 'bottom',
                            y: 15,
                            floating: false,
                            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
                            borderColor: '#CCC',
                            borderWidth: 1,
                            shadow: false
                        },
                        tooltip: {
                            formatter: function () {
                                var header = "Dias no P.A.:<b> " + this.x + "</b><br/>";
                                var s = "";
                                var soma = 0;
                                $.each(this.points, function () {
                                    s += '<br/><b style="color:' + this.color + '">' + this.series.name + '</b>: ' +
                                        this.y;
                                    soma += parseInt(this.y);
                                });
                                s += "<br/><b>Total: " + soma;
                                return header + s;
                            },
                            shared: true
                        },
                        plotOptions: {
                            column: {
                                stacking: 'normal',
                                dataLabels: {
                                    enabled: false,
                                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                                    style: {
                                        textShadow: '0 0 3px black'
                                    }
                                }
                            }
                        },
                        series: result.series
                    }, function (chart) {
                        var coletaData = [];
                        var arrayTabela = {};
                        var equip;
                        var pont;
                        var valor;
                        var somaPromotor = 0;
                        var somaDetractor = 0;
                        var somaPassivo = 0;
                        var somaTodos = 0;
                        var somaTodosTodos = 0;
                        var table = "<table><tr>";
                        table += "<th>Dias de reparo</th>";
                        table += "<th>Promotor</th>";
                        table += "<th>Passivo</th>";
                        table += "<th>Detractor</th>";
                        table += "<th>Total</th>";
                        table += "</tr>";
                        $.each(chart.series, function () {
                            $.each(this.data, function () {
                                coletaData.push(this.category + "|" + this.series.name + "|" + this.y);
                            });
                        });
                        $.each(coletaData, function () {
                            var linha = this.split("|");
                            if (typeof arrayTabela[linha[0]] == "undefined") {
                                arrayTabela[linha[0]] = {};
                            }

                            arrayTabela[linha[0]][linha[1]] = linha[2];
                        });
                        $.each(arrayTabela, function (i, val) {
                            somaPromotor += parseInt(this.Promotor);
                            somaPassivo += parseInt(this.Passivo);
                            somaDetractor += parseInt(this.Detractor);
                            somaTodos = parseInt(this.Promotor) + parseInt(this.Passivo) + parseInt(this.Detractor);
                            somaTodosTodos += somaTodos;

                            table += "<tr>";
                            table += "<td>" + i + "</td>";
                            table += "<td>" + this.Promotor + "</td>";
                            table += "<td>" + this.Passivo + "</td>";
                            table += "<td>" + this.Detractor + "</td>";
                            table += "<td>" + somaTodos + "</td>";
                            table += "</tr>";
                        });
                        table += "<tr>";
                        table += "<td>Total</td>";
                        table += "<td>" + somaPromotor + "</td>";
                        table += "<td>" + somaPassivo + "</td>";
                        table += "<td>" + somaDetractor + "</td>";
                        table += "<td>" + somaTodosTodos + "</td>";
                        table += "</tr>";
                        table += "</table>";
                        $("#mostraTabela").html(table).css({
                            "display": "block"
                        });
                        $("#mostraTabela table").addClass("tabelaGrafico");
                        $("#mostraTabela table tr th").addClass("topo");
                        $("#mostraTabela table tr:last-child").addClass("last");
                    });
                })
                .fail(function () {
                    $("#mostraGrafico").text("Não foi possível carregar o gráfico.")
                        .css({
                            "background-color": "#F00",
                            "font": "bold 16px Arial",
                            "color": "#FFF",
                            "width": "700px",
                            "height": "20px",
                            "margin": "auto",
                            "text-align": "center"
                        });
                });
        }

        function grafico05(marca) {

            var optionMarcas = "<select id='marcasGrafico05' name='marcasGrafico05'>";
            optionMarcas += "<option value=''>Selecione a marca específica</option>";
            optionMarcas += "<option value='0'>Todas</option>";

            Highcharts.setOptions({
                colors: ['#68A042', '#A9D08E', '#D9D9D9', '#FFC000', '#FF0000', '#FF8C00']
            });

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: "POST",
                dataType: "JSON",
                data: {
                    data_inicio: "<?=$data_inicio;?>",
                    data_fim: "<?=$data_fim;?>",
                    pais:<?=$pais;?>,
                    cidade: "<?=$cidade;?>",
                    posto: "<?=$posto;?>",
                    marca: marca,
                    grafico: 5
                },
                beforeSend: function () {
                    if ($("#mostraGrafico").highcharts() !== undefined) {
                        $("#mostraGrafico").highcharts().destroy();
                    }

                    $("#mostraGrafico").text("Aguardando carregamento...")

                    $("#casoNecessite").css("display", "none");
                    $("#mostraTabela").css("display", "none");
                }
            })
                .done(function (result) {
                    var subtitle;
                    $.each(result.arrayMarcas, function (k, v) {
                        optionMarcas += "<option value='" + v.marca + "'>" + v.nome + "</option>";
                    });
                    optionMarcas += "</select>";

                    if (result.marcaEscolhida != null) {
                        subtitle = "Nível de satisfação do atendimento da marca " + result.marcaEscolhida;
                    } else {
                        subtitle = "Nível de satisfação do atendimento";
                    }
                    $('#casoNecessite').css("display", "block").html(optionMarcas);
                    $('#mostraGrafico').highcharts({
                        chart: {
                            zoomType: 'xy'
                        },
                        title: {
                            text: 'Resultado por SATISFAÇÃO DE ATENDIMENTO '
                        },
                        subtitle: {
                            text: subtitle
                        },
                        xAxis: [{
                            title: {
                                text: 'Em relação ao atendimento'
                            },
                            labels: {
                                align: "center",
                                autoRotationLimit: 30
                            },
                            categories: result.categories,
                            crosshair: true
                        }],
                        yAxis: [{
                            min: 0,
                            title: {
                                text: 'Satisfação'
                            },
                            stackLabels: {
                                enabled: false,
                                style: {
                                    fontWeight: 'bold',
                                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                                }
                            }
                        }, {
                            min: 0,
                            title: {
                                text: 'Porcentagem NSS',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            labels: {
                                format: '{value} %',
                                style: {
                                    color: Highcharts.getOptions().colors[0]
                                }
                            },
                            opposite: true
                        }],
                        legend: {
                            align: 'center',
                            verticalAlign: 'bottom',
                            floating: false,
                            backgroundColor: (Highcharts.theme && Highcharts.theme.background2) || 'white',
                            borderColor: '#CCC',
                            borderWidth: 1,
                            shadow: false
                        },
                        tooltip: {
                            formatter: function () {
                                var header = "<b>" + this.x + "</b><br/>";
                                var s = "";
                                var nss = "";
                                var soma = 0;
                                $.each(this.points, function () {
                                    if (this.series.name != "NSS") {
                                        s += '<br/><b style="color:' + this.color + '">' + this.series.name + '</b>: ' +
                                            this.y;
                                        soma += parseInt(this.y);
                                    } else {
                                        nss = '<br/><b style="color:' + this.color + '">' + this.series.name + '</b>: ' +
                                            this.y + '%';
                                    }
                                });
                                s += nss;
                                s += "<br/><b>Total: " + soma;
                                return header + s;
                            },
                            shared: true
                        },
                        plotOptions: {
                            column: {
                                stacking: 'normal',
                                dataLabels: {
                                    enabled: false,
                                    color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                                    style: {
                                        textShadow: '0 0 3px black'
                                    }
                                }
                            }
                        },
                        series: result.series
                    });
                })
                .fail(function () {
                    $("#mostraGrafico").text("Não foi possível carregar o gráfico.")
                        .css({
                            "background-color": "#F00",
                            "font": "bold 16px Arial",
                            "color": "#FFF",
                            "width": "700px",
                            "height": "20px",
                            "margin": "auto",
                            "text-align": "center"
                        });
                });
        }

        function grafico06() {

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: "POST",
                dataType: "JSON",
                data: {
                    data_inicio: "<?=$data_inicio;?>",
                    data_fim: "<?=$data_fim;?>",
                    pais:<?=$pais;?>,
                    cidade: "<?=$cidade;?>",
                    posto: "<?=$posto;?>",
                    grafico: 6
                },
                beforeSend: function () {
                    if ($("#mostraGrafico").highcharts() !== undefined) {
                        $("#mostraGrafico").highcharts().destroy();
                    }

                    $("#mostraGrafico").text("Aguardando carregamento...")
                        .css({
                            "height": "400px"
                        });
                    $("#casoNecessite").css("display", "none");
                    $("#mostraTabela").css("display", "none");
                }
            })
                .done(function (result) {
                    $('#mostraGrafico').highcharts({
                        chart: {
                            type: 'heatmap',
                            marginTop: 50,
                            marginBottom: 80,
                            plotBorderWidth: 1
                        },
                        title: {
                            text: 'Mapa de Satisfação / Lealdade'
                        },
                        xAxis: {
                            categories: ['Promotor', 'Passivo', 'Detractor']
                        },
                        yAxis: {
                            categories: result.categories,
                            title: null
                        },
                        colorAxis: {
                            min: 0,
                            minColor: '#FF6666',
                            maxColor: '#68A042'
                        },

                        legend: {
                            align: 'right',
                            layout: 'vertical',
                            margin: 0,
                            verticalAlign: 'top',
                            y: 25,
                            symbolHeight: 280
                        },

                        tooltip: {
                            formatter: function () {
                                return '<b>' + this.series.yAxis.categories[this.point.y] + '</b> foi escolhida por <br><b>' +
                                    this.point.value + '</b> clientes que se enquadram em <br><b>' + this.series.xAxis.categories[this.point.x] + '</b>';
                            }
                        },

                        series: result.series
                    });
                })
                .fail(function () {
                    $("#mostraGrafico").text("Não foi possível carregar o gráfico.")
                        .css({
                            "background-color": "#F00",
                            "font": "bold 16px Arial",
                            "color": "#FFF",
                            "width": "700px",
                            "height": "20px",
                            "margin": "auto",
                            "text-align": "center"
                        });
                });
        }
        <?php
        }
        ?>
        $(function () {

            $("#data_inicio").datepicker({maxDate: 0, dateFormat: "dd/mm/yy"}).mask("99/99/9999");
            $("#data_fim").datepicker({maxDate: 0, dateFormat: "dd/mm/yy"}).mask("99/99/9999");
            //$("#paises, #cidade").select2();
            $("#paises").multiselect({
                selectedText: "Selecionado # de #",
            });

            $("#paises").change(function () {
                var pais = $(this).val();

                $("#cidade").html("<option value='' ></option>").val("").trigger("change");

                if (pais.length > 0) {
                    pais.forEach(function (p, i) {
                        if (p == "CCA") {
                            return;
                        }

                        var pais_label = $("#paises > option[value=" + p + "]").text();
                        var retorno = [];

                        $.ajax({
                            type: "POST",
                            url: "<?=$PHP_SELF?>",
                            dataType: "json",
                            data: {
                                ajax: true,
                                ajaxType: "buscaCidade",
                                pais: p
                            }
                        })
                            .done(function (data) {
                                retorno.push("<option value=''></option>");

                                retorno.push("<optgroup label='" + pais_label + "' >");

                                $(data).each(function (key, val) {
                                    retorno.push("<option value='" + val.cidade + "'>" + val.cidade + "</option>");
                                });

                                retorno.push("</optgroup>");

                                $("#cidade").append(retorno);
                            });
                    });
                }
            });

            $("#cidade").change(function () {
                var cidade = $(this).val();
                var retorno = [];
                var nomeCorrigido;

                $.ajax({
                    type: "POST",
                    url: "<?=$PHP_SELF?>",
                    dataType: "json",
                    data: {
                        ajax: true,
                        ajaxType: "buscaPosto",
                        cidade: cidade
                    }
                })
                    .done(function (data) {
                        retorno.push("<option value=''></option>");
                        $(data).each(function (key, val) {
                            retorno.push("<option value='" + val.nome + "'>" + val.nome + "</option>");
                        });
                        $("#posto").html(retorno);
                    });
            });

            $("button[id^=grafico]").click(function () {
                window[this.id]();
                $("#mostraGrafico").css({
                    "display": "block",
                    "width": "700px"
                });
            });

            $(document).on('change', 'select[name=marcasGrafico01]', function () {
                grafico01($("#marcasGrafico01").val());
            });
            $(document).on('change', 'select[name=marcasGrafico02]', function () {
                grafico02($("#marcasGrafico02").val());
            });
            $(document).on('change', 'select[name=marcasGrafico03]', function () {
                grafico03($("#marcasGrafico03").val());
            });
            $(document).on('change', 'select[name=marcasGrafico04]', function () {
                grafico04($("#marcasGrafico04").val());
            });
            $(document).on('change', 'select[name=marcasGrafico05]', function () {
                grafico05($("#marcasGrafico05").val());
            });
        });
    </script>

<?php
if (count($msg_erro["msg"]) > 0) {
    ?>
    <br/>
    <div class="alert alert-error">
        <h4><?= implode("<br />", $msg_erro["msg"]) ?></h4>
    </div>
    <?php
}
?>

    <div class="row">
        <b class="obrigatorio pull-right"> * Campos obrigatórios </b>
    </div>
    <form id="frm_grafico" action="<?= $PHP_SELF ?>" method="POST" class="form-search form-inline tc_formulario">
        <input type="hidden" id="posto_codigo" name='posto_codigo' value='<?= $posto ?>'>
        <input type="hidden" id="login_fabrica_codigo" name='login_fabrica_codigo' value='<?= $login_fabrica ?>'>
        <div class='container tc_container' style="background-color:#D3D3D3;">
            <div class="titulo_tabela">Parâmetros de pesquisa</div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class="span4">
                    <div class='control-group <?= (in_array("data_inicio", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class="control-label" for="data_inicio">Data Inicio</label>
                        <div class="controls controls-row">
                            <div class="span5">
                                <h5 class='asteristico'>*</h5>
                                <input id="data_inicio" name="data_inicio" class="span12" type="text" value="<?= getValue('data_inicio') ?>"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class='control-group <?= (in_array("data_fim", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class="control-label" for="data_fim">Data Fim</label>
                        <div class="controls controls-row">
                            <div class="span5">
                                <h5 class='asteristico'>*</h5>
                                <input id="data_fim" name="data_fim" class="span12" type="text" value="<?= getValue('data_fim') ?>"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?= (in_array("pais", $msg_erro["campos"])) ? "error" : "" ?>'>
                        <label class="control-label" for="paises">Países</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <h5 class='asteristico'>*</h5>
                                <select id="paises" name="paises[]" class="span12" multiple="multiple">
                                    <?php
                                    $sql_pais = "   SELECT  DISTINCT
                                                    tbl_pais.nome,
                                                    tbl_pais.pais
                                            FROM    tbl_pais
                                            JOIN    tbl_posto           ON  tbl_posto.pais              = tbl_pais.pais
                                            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
                                      ORDER BY      tbl_pais.nome
                                      ";

                                    $res_pais = pg_query($con, $sql_pais);
                                    $paises = pg_fetch_all($res_pais);
                                    $pais_array = array();

                                    foreach ($paises as $pais) {
                                        $pais_nome = ucfirst(strtolower($pais['nome']));

                                        $pais_array[$pais["pais"]] = $pais_nome;
                                        ?>
                                        <option value="<?= $pais['pais'] ?>" <?= (in_array($pais['pais'], $_POST['paises'])) ? "selected" : "" ?> ><?= $pais_nome ?></option>
                                        <?php
                                    }
                                    ?>
                                    <option value="CCA" <?= (in_array('CCA', $_POST['paises'])) ? "selected" : "" ?> >CCA</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4">
                    <div class='control-group'>
                        <label class='control-label' for='cidade'>Cidade</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <select id="cidade" name="cidade" class="span12">
                                    <option value="">&nbsp;</option>
                                    <?php
                                    $pais = $_POST["paises"];

                                    foreach ($pais as $p) {
                                        if ($p == "CCA") {
                                            continue;
                                        }

                                        $pais_nome = $pais_array[$p];

                                        if ($p == 'BR') {
                                            $sqlCidade = "
                                        SELECT  DISTINCT
                                                tbl_posto_fabrica.contato_cidade AS cidade
                                        FROM    tbl_posto
                                        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                                    AND tbl_posto_fabrica.fabrica = $login_fabrica
                                                                    AND tbl_posto.pais = '$p'
                                    ORDER BY      tbl_posto_fabrica.contato_cidade
                                    ";
                                        } else {
                                            $sqlCidade = "
                                        SELECT  DISTINCT
                                                tbl_posto.cidade
                                        FROM    tbl_posto
                                        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto = tbl_posto.posto
                                                                    AND tbl_posto_fabrica.fabrica = $login_fabrica
                                                                    AND tbl_posto.pais = '$p'
                                    ORDER BY      tbl_posto.cidade
                                    ";

                                        }

                                        $resCidade = pg_query($con, $sqlCidade);
                                        $cidades = pg_fetch_all($resCidade);

                                        echo "<optgroup label='{$pais_nome}'>";

                                        foreach ($cidades as $city) {
                                            ?>
                                            <option value="<?= $city['cidade'] ?>" <?= ($_POST['cidade'] == $city['cidade']) ? "selected" : "" ?>><?= $city['cidade'] ?></option>
                                            <?php
                                        }

                                        echo "</optgroup>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div class='row-fluid'>
                <div class="span2"></div>
                <div class="span4">
                    <div class='control-group'>
                        <label class='control-label' for='posto'>Posto</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <select id="posto" name="posto" class="span12">
                                    <option value="">&nbsp;</option>
                                    <?php
                                    if (filter_input(INPUT_POST, 'cidade')) {
                                        $cidade = utf8_decode(filter_input(INPUT_POST, 'cidade'));
                                        $sqlPostos = "
                                    SELECT  DISTINCT
                                            tbl_posto.nome
                                    FROM    tbl_posto_fabrica
                                    JOIN    tbl_posto   ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                                        AND tbl_posto_fabrica.fabrica   = $login_fabrica
                                                        AND (
                                                                tbl_posto.cidade                    = '$cidade'
                                                            OR  tbl_posto_fabrica.contato_cidade    = '$cidade'
                                                            )
                                ORDER BY      tbl_posto.nome
                                ";
                                        $resPostos = pg_query($con, $sqlPostos);
                                        $postos = pg_fetch_all($resPostos);

                                        foreach ($postos as $pa) {
                                            ?>
                                            <option value="<?= $pa['nome'] ?>" <?= ($_POST['posto'] == $pa['nome']) ? "selected" : "" ?>><?= $pa['nome'] ?></option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <div class='row-fluid'>
                <div class="span12" align="center">
                    <p class="tac">
                        <input type="submit" id="bt_periodo" class="btn btn-primary" value="Pesquisar" name="bt_periodo">
                    </p>
                </div>
            </div>
        </div>
    </form>
<?php
if (filter_input(INPUT_POST, 'bt_periodo') && count($msg_erro["msg"]) == 0) {
    ?>

    <div id="botoes" style="text-align:center">
        <button class="btn btn-small" id="grafico01">Pontuação por Marcas</button>
        <button class="btn btn-small" id="grafico02">Pontuação por Equipamentos</button>
        <button class="btn btn-small" id="grafico03">Razão da Pontuação</button>
        <button class="btn btn-small" id="grafico04">Dias de atendimento</button>
        <button class="btn btn-small" id="grafico05">Satisfação de atendimento</button>
        <button class="btn btn-small" id="grafico06">Satisfação / Lealdade</button>
    </div>
    <div id="casoNecessite"></div>
    <br/>
    <div id="mostraGrafico"></div>
    <br/>
    <div id="mostraTabela"></div>
    <?php
}
include "rodape.php";
?>
