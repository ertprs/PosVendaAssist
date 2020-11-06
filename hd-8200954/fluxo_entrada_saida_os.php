<?php
$areaAdmin = (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) ? true : false;

include __DIR__."/dbconfig.php";
include __DIR__."/includes/dbconnect-inc.php";

if ($areaAdmin) {
    $admin_privilegios = "gerencia";
    include __DIR__."/admin/autentica_admin.php";
} else {
    include __DIR__."/autentica_usuario.php";
}

include __DIR__."/funcoes.php";

$btn_acao = $_REQUEST['btn_acao'];
$interval = 6;

$mesesx = array(
    "1" => "Jan.", "2" => "Fev.", "3" => "Mar.", "4" => "Abr.", "5" => "Mai.", "6" => "Jun.", 
    "7" => "Jul.", "8" => "Ago.", "9" => "Set.", "10" => "Out.", "11" => "Nov.", "12" => "Dez."
);

if ($btn_acao == 'submit') {

    $data_inicial = $_REQUEST["data_inicial"];
    $data_final = $_REQUEST["data_final"];

    if (!empty($data_inicial) && !empty($data_final)) {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) || !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }
        }

        $sqlX = "SELECT '$aux_data_inicial'::date + interval '{$interval} months' >= '$aux_data_final'";
        $resSubmitX = pg_query($con,$sqlX);
        $periodo_6meses = pg_fetch_result($resSubmitX,0,0);

        if($periodo_6meses == 'f'){
            $msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo {$interval} meses";
        }
    } else {
        $aux_data_final = date('Y-m-d H:i:s');
        $aux_data_inicial = date( "Y-m-d H:i:s", strtotime("-6 month"));
    }

    if (!empty($_REQUEST["posto_id"])) {
        $posto_id = $_REQUEST["posto_id"];
        $whereOsPosto = "AND o.posto = {$posto_id}";
    }

    if (!empty($_REQUEST["tipo_atendimento"])) {
        $tipo_atendimento = $_REQUEST["tipo_atendimento"];
        $whereOsTipoAtendimento = "AND o.tipo_atendimento IN (".implode(", ", $tipo_atendimento).")";
    }

    if (!empty($_REQUEST["tipo_posto"])) {
        $tipo_posto = $_REQUEST["tipo_posto"];
        $whereTipoPosto = "AND tp.tipo_posto IN (".implode(", ", $tipo_posto).")";
    }

    if (!empty($_REQUEST["linha"])) {
        $linha = $_REQUEST['linha'];
        $whereOsLinha = "AND p.linha IN (".implode(", ", $linha).")";
    }

    if (!empty($_REQUEST["familia"])) {
        $familia = $_REQUEST['familia'];
        $whereOsFamilia = "AND p.familia IN (".implode(", ", $familia).")";
    }

    if (!empty($_REQUEST["inspetor"])) {
        $inspetor = $_REQUEST["inspetor"];
        $whereInspetor = "AND pf.admin_sap IN (".implode(", ", $inspetor).")";
    }

    if (!empty($_REQUEST["estado"])) {
        $estado = $_REQUEST["estado"];
        $estado = array_map(function($e) {
            return "'{$e}'";
        }, $estado);
        $whereEstado = "AND pf.contato_estado IN (".implode(", ", $estado).")";
    }

    if (!empty($_REQUEST["status_os"])) {
        $status_os = $_REQUEST["status_os"];
        $whereStatusOs = "AND o.status_checkpoint IN (".implode(", ", $status_os).")";
    }

    if (count($msg_erro['msg']) == 0) {
        if (!empty($data_inicial) AND !empty($data_final)){
            $interval_label = "período {$data_inicial} - {$data_final}";
        }else{
            $interval_label = "período ".date( "d/m/Y", strtotime("-6 month"))." - ".date('d/m/Y');
        }
        
        $sqlSemanas = "
            SELECT 
                EXTRACT('week' from datas) as semana,
                EXTRACT('month' from datas) as mes,
                EXTRACT('year' from datas) as ano 
            FROM generate_series(
                  '$aux_data_inicial'::TIMESTAMP,
                  '$aux_data_final'::TIMESTAMP,
                  '1 day'::interval
                ) datas
                GROUP BY ano, mes, semana
                ORDER BY ano, mes, semana";
        $resSemanas = pg_query($con, $sqlSemanas);
       
        $array_semanas = array();
        $range_grafico = array();

        if (pg_num_rows($resSemanas) > 0){
            for ($i=0; $i < pg_num_rows($resSemanas); $i++) {
                $year = pg_fetch_result($resSemanas, $i, 'ano');
                $week = pg_fetch_result($resSemanas, $i, 'semana');
                $month = pg_fetch_result($resSemanas, $i, 'mes');
                $array_semanas[] = "$week-$month";
                $range_grafico[$year][$month][$week] = $week;
                $array_total_te[$month][$week] = array();
                $array_total_abertas[$month][$week] = array();
            }
            
            $categoria_geral = array();
            $oa_semanas = array();
            foreach ($range_grafico as $ano => $meses) {
                foreach ($meses as $mes => $semanas) {
                    $oa_semanas = array_keys($semanas);
                    sort($oa_semanas);
                    $categoria_geral[] = array(
                        'name' => $mesesx[$mes].' - '.$ano,
                        "categories" => $oa_semanas
                    );
                }
            }

            $categoria_geral = array_values($categoria_geral);
            $categoria_geral = json_encode($categoria_geral);
        }
        
        // Abertas X Fechadas
        $sqlAbxFi = "
            SELECT
                qtde_finalizadas,
                qtde_abertas,
                (
                    SELECT DISTINCT
                        COUNT(oh.os) AS qtde_andamento
                    FROM tbl_os_historico_checkpoint oh
                    LEFT JOIN tbl_os o ON o.os = oh.os AND o.fabrica = $login_fabrica
                    LEFT JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = $login_fabrica
                    LEFT JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = $login_fabrica
                    LEFT JOIN tbl_os_produto op ON op.os = o.os
                    LEFT JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = $login_fabrica
                    WHERE oh.fabrica = {$login_fabrica}
                    AND DATE_PART('week', oh.data_input) = semana
                    AND DATE_PART('month', oh.data_input) = mes
                    AND DATE_PART('year', oh.data_input) = ano
                    {$whereOsPosto}
                    {$whereOsTipoAtendimento}
                    {$whereTipoPosto}
                    {$whereOsLinha}
                    {$whereOsFamilia}
                    {$whereInspetor}
                    {$whereEstado}
                    {$whereStatusOs}
                ) AS qtde_andamento,
                semana,
                mes,
                ano
            FROM (
                SELECT
                    COUNT(of.os) AS qtde_finalizadas,
                    x.qtde_abertas,
                    x.semana,
                    x.mes,
                    x.ano
                FROM (
                    SELECT
                        COUNT(o.os) AS qtde_abertas,
                        DATE_PART('week', o.data_abertura) AS semana,
                        DATE_PART('month', o.data_abertura) AS mes,
                        DATE_PART('year', o.data_abertura) AS ano
                    FROM tbl_os o
                    JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
                    JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
                    JOIN tbl_os_produto op ON op.os = o.os
                    JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                    WHERE o.fabrica = {$login_fabrica}
                    AND o.excluida IS NOT TRUE
                    AND o.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                    {$whereOsPosto}
                    {$whereOsTipoAtendimento}
                    {$whereTipoPosto}
                    {$whereOsLinha}
                    {$whereOsFamilia}
                    {$whereInspetor}
                    {$whereEstado}
                    {$whereStatusOs}
                    GROUP BY semana, mes, ano
                ) x
                JOIN tbl_os of ON DATE_PART('year', of.data_conserto) = x.ano AND DATE_PART('month', of.data_conserto) = x.mes AND DATE_PART('week', of.data_conserto) = x.semana AND of.fabrica = {$login_fabrica}
                GROUP BY qtde_abertas, semana, mes, ano
            ) xx
            ORDER BY semana, mes, ano;
        ";

        $resAbxFi = pg_query($con, $sqlAbxFi);
        $countResAbxFi = pg_num_rows($resAbxFi);
        
        $abxfi_qtde_finalizadas = array();
        $abxfi_qtde_abertas     = array();

        $table_abxfi_qtde_finalizadas = array();
        $table_abxfi_qtde_abertas     = array();
        $table_abxfi_qtde_andamento   = array();
        $table_abxfi_percentual       = array();
        $table_abxfi_fechadas_soma    = array();

        if ($countResAbxFi > 0) {
            $range_periodo_abxfi = array();
            $range_abxfi         = array();
            
            for ($i = 0; $i < $countResAbxFi; $i++) {
                $qtde_finalizadas_abxfi = pg_fetch_result($resAbxFi, $i, "qtde_finalizadas");
                $qtde_abertas_abxfi = pg_fetch_result($resAbxFi, $i, "qtde_abertas");
                $qtde_andamento_abxfi = pg_fetch_result($resAbxFi, $i, "qtde_andamento");
                $semana_abxfi = pg_fetch_result($resAbxFi, $i, "semana");
                $mes_abxfi = pg_fetch_result($resAbxFi, $i, "mes");
                $ano_abxfi = pg_fetch_result($resAbxFi, $i, "ano");

                $range_abxfi[$ano_abxfi][$mes_abxfi][$semana_abxfi] = $semana_abxfi;
                
                $mes_atual = "$mes_abxfi$ano_abxfi";

                if ($mes_anterior != $mes_atual){
                    $soma_fechadas = 0;
                }
                $soma_fechadas += $qtde_finalizadas_abxfi;

                $range_periodo_abxfi[$ano_abxfi][$mes_abxfi][$semana_abxfi] = array(
                    "qtde_finalizadas" => $qtde_finalizadas_abxfi,
                    "qtde_abertas" => $qtde_abertas_abxfi,
                    "qtde_andamento" => $qtde_andamento_abxfi,
                    "soma_fechadas" => $soma_fechadas,
                    "ano" => $ano_abxfi,
                    "mes" => $mes_abxfi,
                    "semana" => $semana_abxfi
                );
                $mes_anterior = "$mes_abxfi$ano_abxfi";
            }
            
            $abxfi_semana = $array_semanas;

            foreach ($abxfi_semana as $key => $value) {
                if (!array_key_exists($value, $abxfi_qtde_finalizadas)){
                    $abxfi_qtde_finalizadas[$key] = array();
                    $abxfi_qtde_abertas[$key] = array();
                    $table_abxfi_qtde_finalizadas[$key] = array();
                    $table_abxfi_qtde_abertas[$key] = array();
                    $table_abxfi_qtde_andamento[$key] = array();
                    $table_abxfi_percentual[$key] = array();
                    $table_abxfi_fechadas_soma[$key] = array();
                }
            }

            $abxfi_qtde_finalizadas = array_map(function($x){
                $x = 0;
                return $x;
                #return array_fill(0, 0, 0);
            }, $abxfi_qtde_finalizadas);
            
            $abxfi_qtde_abertas = $abxfi_qtde_finalizadas;
            $table_abxfi_qtde_finalizadas = $abxfi_qtde_finalizadas;
            $table_abxfi_qtde_abertas = $abxfi_qtde_finalizadas;
            $table_abxfi_qtde_andamento = $abxfi_qtde_finalizadas;
            $table_abxfi_percentual = $abxfi_qtde_finalizadas;
            $table_abxfi_fechadas_soma = $abxfi_qtde_finalizadas;
            
            foreach ($range_periodo_abxfi as $ano => $values) {
                foreach ($values as $mes => $dados) {
                    foreach ($dados as $semana => $value) {
                        
                        unset($percentual);

                        if ($value["qtde_abertas"] == 0){
                            $percentual = 0;
                        }else{
                            $percentual = ($value["qtde_finalizadas"] * 100) / $value["qtde_abertas"];
                        }
                        
                        $mes_semana = "$semana-$mes";
                        $chaveSemana = array_search($mes_semana, $abxfi_semana);

                        $abxfi_qtde_finalizadas[$chaveSemana] = (int) $value["qtde_finalizadas"];
                        $abxfi_qtde_abertas[$chaveSemana] = (int) $value["qtde_abertas"];
                        $table_abxfi_qtde_finalizadas[$chaveSemana] = (int) $value["qtde_finalizadas"];
                        $table_abxfi_qtde_abertas[$chaveSemana] = (int) $value["qtde_abertas"];
                        $table_abxfi_qtde_andamento[$chaveSemana] = (int) $value["qtde_andamento"];
                        $table_abxfi_percentual[$chaveSemana] = (int) $percentual;
                        $table_abxfi_fechadas_soma[$chaveSemana] = (int) $value["soma_fechadas"];
                    }
                }
            }
            
            $abxfi_categoria          = array_values($abxfi_categoria);
            $abxfi_categoria          = json_encode($abxfi_categoria);
            $abxfi_qtde_finalizadas   = json_encode($abxfi_qtde_finalizadas);
            $abxfi_qtde_abertas       = json_encode($abxfi_qtde_abertas);
        }
        // FIM Abertas X Fechadas
        
        // AGVI
        $sqlAgvi = "
            SELECT 
                fora_prazo_agvi,
                tma,
                os,
                semana,
                mes,
                ano,
                qtde_andamento
            FROM (
                SELECT
                    CASE WHEN EXTRACT(DAYS FROM (data_preenchimento - ultimo_agendamento)) > 1 THEN 't' ELSE 'f' END AS fora_prazo_agvi,
                    CASE
                        WHEN EXTRACT(DAYS FROM (data_conserto - data_abertura)) <= 10 THEN 10
                        WHEN EXTRACT(DAYS FROM (data_conserto - data_abertura)) <= 30 THEN 30
                        WHEN EXTRACT(DAYS FROM (data_conserto - data_abertura)) > 30 OR data_conserto IS NULL THEN 31
                    END AS tma,
                    COUNT(os) as os,
                    semana,
                    mes,
                    ano,
                    (
                        SELECT DISTINCT
                            COUNT(oh.os) AS qtde_andamento
                        FROM tbl_os_historico_checkpoint oh
                        LEFT JOIN tbl_os o ON o.os = oh.os AND o.fabrica = 169
                        LEFT JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169
                        LEFT JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = 169
                        LEFT JOIN tbl_os_produto op ON op.os = o.os
                        LEFT JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = 169
                        WHERE oh.fabrica = $login_fabrica
                        AND DATE_PART('week', oh.data_input) = semana
                        AND DATE_PART('month', oh.data_input) = mes
                        AND DATE_PART('year', oh.data_input) = ano
                        {$whereOsPosto}
                        {$whereOsTipoAtendimento}
                        {$whereTipoPosto}
                        {$whereOsLinha}
                        {$whereOsFamilia}
                        {$whereInspetor}
                        {$whereEstado}
                        {$whereStatusOs}
                    ) AS qtde_andamento
                FROM (
                    SELECT DISTINCT
                        CASE WHEN ta.km_google = 'f' THEN o.data_digitacao::TIMESTAMP
                        ELSE (
                            SELECT tbl_tecnico_agenda.data_agendamento::TIMESTAMP
                            FROM tbl_tecnico_agenda
                            WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
                            AND tbl_tecnico_agenda.os = o.os
                            ORDER BY tbl_tecnico_agenda.data_input DESC
                            LIMIT 1
                        ) END AS ultimo_agendamento,
                        (
                            SELECT oh.data_input::TIMESTAMP
                            FROM tbl_os_historico_checkpoint oh
                            LEFT JOIN tbl_os o ON o.os = oh.os AND o.fabrica = 169
                            LEFT JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169
                            LEFT JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = 169
                            LEFT JOIN tbl_os_produto op ON op.os = o.os
                            LEFT JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = 169
                            WHERE oh.os = o.os
                            AND oh.fabrica = $login_fabrica
                            AND oh.status_checkpoint NOT IN (0,1)
                            {$whereOsPosto}
                            {$whereOsTipoAtendimento}
                            {$whereTipoPosto}
                            {$whereOsLinha}
                            {$whereOsFamilia}
                            {$whereInspetor}
                            {$whereEstado}
                            {$whereStatusOs}
                            ORDER BY oh.data_input ASC
                            LIMIT 1
                        ) AS data_preenchimento,
                        o.os,
                        o.data_abertura,
                        o.data_conserto,
                        DATE_PART('week', o.data_abertura) AS semana,
                        DATE_PART('month', o.data_abertura) AS mes,
                        DATE_PART('year', o.data_abertura) AS ano
                    FROM tbl_os o
                    JOIN tbl_tipo_atendimento ta USING(tipo_atendimento,fabrica)
                    JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = $login_fabrica
                    JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = $login_fabrica
                    JOIN tbl_os_produto op ON op.os = o.os
                    JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = $login_fabrica
                    AND o.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                    WHERE o.fabrica = $login_fabrica
                    {$whereOsPosto}
                    {$whereOsTipoAtendimento}
                    {$whereTipoPosto}
                    {$whereOsLinha}
                    {$whereOsFamilia}
                    {$whereInspetor}
                    {$whereEstado}
                    {$whereStatusOs}
                    AND o.excluida IS NOT TRUE
                ) x
                GROUP BY semana, mes, ano, tma, fora_prazo_agvi
            )xc
            ORDER BY semana, mes, ano;
        ";

        $resAgvi = pg_query($con, $sqlAgvi);
        $countResAgvi = pg_num_rows($resAgvi);

        $tempo_x = array();
        $tempo_f = array();
        $tempo_b = array();
        
        $range_periodo_fora_prazo_agvi = array();
        $range_periodo_dentro_prazo_agvi = array();
        if ($countResAgvi > 0){
            for ($i=0; $i < $countResAgvi; $i++) { 
                #$percentual_agvi        = pg_fetch_result($resAgvi, $i, 'percentual');
                $fora_prazo_agvi        = pg_fetch_result($resAgvi, $i, 'fora_prazo_agvi');
                $tma_agvi               = pg_fetch_result($resAgvi, $i, 'tma');
                $os_agvi                = pg_fetch_result($resAgvi, $i, 'os');
                $semana_agvi            = pg_fetch_result($resAgvi, $i, 'semana');
                $mes_agvi               = pg_fetch_result($resAgvi, $i, 'mes');
                $ano_agvi               = pg_fetch_result($resAgvi, $i, 'ano');
                $qtde_andamento_agvi    = pg_fetch_result($resAgvi, $i, 'qtde_andamento');

                $range_agvi[$ano_agvi][$mes_agvi][$semana_agvi] = $semana_agvi;

                if ($fora_prazo_agvi == "t"){
                    $mes_atual = "$mes_agvi$ano_agvi$semana_agvi";

                    if ($mes_anterior != $mes_atual){
                        $soma_os = 0;
                    }
                    $soma_os += $os_agvi;
                    $range_periodo_fora_prazo_agvi[$ano_agvi][$mes_agvi][$semana_agvi][$tma_agvi] = array(
                        "qtde" => $qtde_andamento_agvi,
                        "semana" => $semana_agvi,
                        "mes" => $mes_agvi,
                        "ano" => $ano_agvi,
                        "os"  => $soma_os,
                        "tma_agvi" => $tma_agvi,
                        "fora_prazo_agvi" => $fora_prazo_agvi
                    );
                    $mes_anterior = "$mes_agvi$ano_agvi$semana_agvi";
                }else {

                    $mes_atual = "$mes_agvi$ano_agvi$semana_agvi";

                    if ($mes_anterior != $mes_atual){
                        $soma_os = 0;
                    }
                    $soma_os += $os_agvi;

                    $range_periodo_dentro_prazo_agvi[$ano_agvi][$mes_agvi][$semana_agvi][$fora_prazo_agvi] = array(
                        "qtde" => $qtde_andamento_agvi,
                        "semana" => $semana_agvi,
                        "mes" => $mes_agvi,
                        "ano" => $ano_agvi,
                        "os" => $soma_os,
                        "tma_agvi" => $tma_agvi,
                        "fora_prazo_agvi" => $fora_prazo_agvi
                    );
                    $mes_anterior = "$mes_agvi$ano_agvi$semana_agvi";
                }

                if (!array_key_exists($tma_agvi, $tempo_x)){
                    $tempo_x[$tma_agvi] = array();
                }

                if (!array_key_exists($fora_prazo_agvi, $tempo_f)){
                    $tempo_f[$fora_prazo_agvi] = array();
                }

                if (!array_key_exists($fora_prazo_agvi, $tempo_b)){
                    $tempo_b[$tma_agvi] = array();
                }
            }
            
            $agvi_semanas = $array_semanas;
            $tempo_x = array_map(function($x) use($agvi_semanas){
                return array_fill(0, count($agvi_semanas), 0);
            }, $tempo_x);

            $tempo_f = array_map(function($x) use($agvi_semanas){
                return array_fill(0, count($agvi_semanas), 0);
            }, $tempo_f);

            $tempo_b = array_map(function($x) use($agvi_semanas){
                return array_fill(0, count($agvi_semanas), 0);
            }, $tempo_b);
            
            foreach ($range_periodo_fora_prazo_agvi as $ano => $dados_ano) {
                foreach ($dados_ano as $mes => $dados_mes) {
                    foreach ($dados_mes as $semana => $dados_semana) {
                        foreach ($dados_semana as $tempo => $dados_tempo) {
                            unset($mes_semana);
                            $mes_semana = "$semana-$mes";
                            $chave_semana_agvi_f = array_search($mes_semana, $agvi_semanas);
                            unset($percentual);

                            if ($dados_tempo["qtde"] == 0){
                                $percentual = 0;
                            }else{
                                $percentual = ($dados_tempo["os"] * 100) / $dados_tempo["qtde"];
                            }
                            $tempo_x[$tempo][$chave_semana_agvi_f] = (int) $percentual;
                            $tempo_b[$tempo][$chave_semana_agvi_f] = (int) $dados_tempo['os'];
                        }  
                    }
                }
            }
            
            foreach ($range_periodo_dentro_prazo_agvi as $ano => $dados_ano) {
                foreach ($dados_ano as $mes => $dados_mes) {
                    foreach ($dados_mes as $semana => $dados_semana) {
                        foreach ($dados_semana as $tempo => $dados_tempo) {
                                
                            unset($percentual);
                            unset($mes_semana);
                            
                            if ($dados_tempo["qtde"] == 0){
                                $percentual = 0;
                            }else{
                                $percentual = ($dados_tempo["os"] * 100) / $dados_tempo["qtde"];
                            }
                            $mes_semana = "$semana-$mes";
                            $chave_semana_agvi = array_search($mes_semana, $agvi_semanas);
                            $tempo_f[$tempo][$chave_semana_agvi] = (int) $percentual;
                        }  
                    }
                }
            }
            
            foreach ($tempo_x as $key => $value) {
                switch ($key) {
                    case '10':
                        $table_agvi_dez = $value;
                        $serie_t[] = array(
                            "name" => '0-10',
                            "color" => "#ff9d00",
                            "data" => $value
                        );
                        break;
                    case '30':
                        $table_agvi_trinta = $value;
                        $serie_t[] = array(
                            "name" => '11-20',
                            "color" => "#a3a3a3",
                            "data" => $value
                        );
                        break;
                }
            }
            
            foreach ($tempo_b as $key => $value) {
                switch ($key) {
                    case '31':
                        $table_agvi_backlog = $value;
                        $serie_b[] = array(
                            "name" => 'BackLog(+30)',
                            "color" => "blue",
                            "data" => $value
                        );
                        break;
                }
            }
            
            foreach ($tempo_f as $key => $value) {
                switch ($key) {
                    case 'f':
                        $table_agvi = $value;
                        $serie_f[] = array(
                            "name" => 'AGVI',
                            "color" => "#598ff9",
                            "data" => $value
                        );
                        break;
                }
            }

            $serie_agvi     = array_merge($serie_f, $serie_t);
            $serie_b        = json_encode($serie_b);
            $categoria_agvi = json_encode($categoria_agvi);
            $serie_agvi     = json_encode($serie_agvi);
        }
        // FIM AGVI

        // TEMPO ENCERRAMENTO
        $sqlTE = "
            SELECT
                COALESCE(COUNT(o.os)::int,0) AS qtde,
                DATE_PART('week', o.data_abertura) AS semana,
                DATE_PART('month', o.data_abertura) AS mes,
                DATE_PART('year', o.data_abertura) AS ano,
                CASE WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 10 THEN 0
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 20 THEN 11
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 30 THEN 21
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 60 THEN 31
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) > 60 THEN 60
                END AS tempo
            FROM tbl_os o
            JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
            JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
            JOIN tbl_os_produto op ON op.os = o.os
            JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
            WHERE o.fabrica = {$login_fabrica}
            AND o.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
            AND o.finalizada IS NOT NULL
            AND o.excluida IS NOT TRUE
            $whereOsPosto
            $whereOsTipoAtendimento
            $whereTipoPosto
            $whereOsLinha
            $whereOsFamilia
            $whereInspetor
            $whereEstado
            $whereStatusOs
            GROUP BY ano, mes, semana, tempo
            ORDER BY tempo;
        ";

        $resTE = pg_query($con, $sqlTE);
        $countTE = pg_num_rows($resTE);
        
        if ($countTE > 0){
            $range_periodo_te = array();
            $range_te = array();
            $tempo_te = array();
            
            for ($i=0; $i < $countTE; $i++) { 
                $qtde_te      = pg_fetch_result($resTE, $i, 'qtde');
                $semana_te    = pg_fetch_result($resTE, $i, 'semana');
                $mes_te       = pg_fetch_result($resTE, $i, 'mes');
                $ano_te       = pg_fetch_result($resTE, $i, 'ano');
                $tempo        = pg_fetch_result($resTE, $i, 'tempo');
                    
                if (!array_key_exists($tempo, $tempo_te)){
                    $tempo_te[$tempo] = array();
                }

                $range_te[$ano_te][$mes_te][$semana_te] = $semana_te;
                $range_periodo_te[$ano_te][$mes_te][$semana_te][$tempo] = array(
                    "qtde" => $qtde_te,
                    "semana" => $semana_te,
                    "mes" => $mes_te,
                    "ano" => $ano_te,
                    "tempo" => $tempo
                );
            }

            $semanas_te = $array_semanas;

            $tempo_te = array_map(function($x) use($semanas_te){
                return array_fill(0, count($semanas_te), 0);
            }, $tempo_te);

            $array_total_te = array_map(function($x){
                foreach ($x as $key => $value) {
                    $x[$key][] = 0;
                }
                return $x;
            }, $array_total_te);
           
            foreach ($range_periodo_te as $ano => $dados_ano) {
                foreach ($dados_ano as $mes => $dados_mes) {
                    foreach ($dados_mes as $semana => $dados_semana) {
                        foreach ($dados_semana as $tempo => $dados_tempo) {
                            unset($mes_semana);
                            $mes_semana = "$semana-$mes";
                            $chaveSemana = array_search($mes_semana, $semanas_te);
                            $tempo_te[$tempo][$chaveSemana] = (int) $dados_tempo["qtde"];
                            $array_total_te[$mes][$semana][] = $dados_tempo["qtde"];
                        }  
                    }
                }
            }
           
            $dados_total_te = array();
            foreach ($array_total_te as $semana => $dados_semana) {
                foreach ($dados_semana as $key => $value) {
                    $dados_total_te[] = array_sum($value);
                }
            }
            
            foreach ($tempo_te as $key => $value) {
                switch ($key) {
                    case '0':
                        $table_serie_zero = $value;
                        $serie_te[] = array(
                            "name" => '0-10',
                            "color" => "#6d912a",
                            "data" => $value
                        );
                        break;
                    case '11':
                        $table_serie_onze = $value;
                        $serie_te[] = array(
                            "name" => '11-20',
                            "color" => "#a3a3a3",
                            "data" => $value
                        );
                        break;
                    case '21':
                        $table_serie_vinte_um = $value;
                        $serie_te[] = array(
                            "name" => '21-30',
                            "color" => "#598ff9",
                            "data" => $value
                        );
                        break;
                    case '31':
                        $table_serie_trinta_um = $value;
                        $serie_te[] = array(
                            "name" => '30-60',
                            "color" => "#ff9d00",
                            "data" => $value
                        );
                        break;
                    case '60':
                        $table_serie_sessenta = $value;
                        $serie_te[] = array(
                            "name" => '61+',
                            "color" => "#ff4d07",
                            "data" => $value
                        );
                        break;
                }
            }
            $categoria_te = json_encode($categoria_te);
            $serie_te    = json_encode($serie_te);
        }
        // FIM TEMPO ENCERRAMENTO

        // OS EM ABERTO
        $sqlOA = "
            SELECT
                COUNT(o.os) AS qtde_andamento,
                CASE WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 10 THEN 0
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 20 THEN 11
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 30 THEN 21
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) <= 60 THEN 31
                    WHEN EXTRACT(DAYS FROM (o.data_conserto - o.data_abertura)) > 60 OR o.data_conserto IS NULL THEN 60
                END AS tempo,
            semana_check,
            mes_check,
            ano_check
            FROM (
                SELECT DISTINCT
                    oh.os,
                    DATE_PART('week', oh.data_input) AS semana_check,
                    DATE_PART('month', oh.data_input) AS mes_check,
                    DATE_PART('year', oh.data_input) AS ano_check
                FROM tbl_os_historico_checkpoint oh
                LEFT JOIN tbl_os o ON o.os = oh.os AND o.fabrica = 169
                LEFT JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169
                LEFT JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = 169
                LEFT JOIN tbl_os_produto op ON op.os = o.os
                LEFT JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = 169
                WHERE oh.fabrica = {$login_fabrica}
                AND oh.status_checkpoint NOT IN (9)
                AND oh.data_input BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                $whereOsPosto
                $whereOsTipoAtendimento
                $whereTipoPosto
                $whereOsLinha
                $whereOsFamilia
                $whereInspetor
                $whereEstado
                $whereStatusOs
            ) x
            JOIN tbl_os o USING(os)
            JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
            JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
            JOIN tbl_os_produto op ON op.os = o.os
            JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
            WHERE o.fabrica = $login_fabrica
            $whereOsPosto
            $whereOsTipoAtendimento
            $whereTipoPosto
            $whereOsLinha
            $whereOsFamilia
            $whereInspetor
            $whereEstado
            $whereStatusOs
            GROUP BY semana_check, mes_check, ano_check, tempo
            ORDER BY semana_check;
        ";
            
        $resOA = pg_query($con, $sqlOA);
        $countOA = pg_num_rows($resOA);
        
        if ($countOA > 0){
            $range_periodo_oa = array();
            $range_oa = array();
            $tempo_oa = array();
            
            for ($i=0; $i < $countOA; $i++) { 
                $qtde_oa    = pg_fetch_result($resOA, $i, 'qtde_andamento');
                $semana_oa  = pg_fetch_result($resOA, $i, 'semana_check');
                $mes_oa     = pg_fetch_result($resOA, $i, 'mes_check');
                $ano_oa     = pg_fetch_result($resOA, $i, 'ano_check');
                $tempo      = pg_fetch_result($resOA, $i, 'tempo');
                
                if (!array_key_exists($tempo, $tempo_oa)){
                    $tempo_oa[$tempo] = array();
                }

                $range_oa[$ano_oa][$mes_oa][$semana_oa] = $semana_oa;
                $range_periodo_oa[$ano_oa][$mes_oa][$semana_oa][$tempo] = array(
                    "qtde" => $qtde_oa,
                    "semana" => $semana_oa,
                    "mes" => $mes_oa,
                    "ano" => $ano_oa,
                    "tempo" => $tempo
                );
            }
            
            $semanas_oa = $array_semanas;

            $tempo_oa = array_map(function($x) use($semanas_oa){
                return array_fill(0, count($semanas_oa), 0);
            }, $tempo_oa);
            
            $array_total_abertas = array_map(function($x){
                foreach ($x as $key => $value) {
                    $x[$key][] = 0;
                }
                return $x;
            }, $array_total_abertas);

            foreach ($range_periodo_oa as $ano => $dados_ano) {
                foreach ($dados_ano as $mes => $dados_mes) {
                    foreach ($dados_mes as $semana => $dados_semana) {
                        foreach ($dados_semana as $tempo => $dados_tempo) {
                            unset($mes_semana);
                            $mes_semana = "$semana-$mes";
                           
                            $chave_semana_oa = array_search($mes_semana, $semanas_oa);
                            $tempo_oa[$tempo][$chave_semana_oa] = (int) $dados_tempo["qtde"];
                            $array_total_abertas[$mes][$semana][] = $dados_tempo["qtde"];
                        }  
                    }
                }
            }
            
            $dados_total_abertas = array();
            foreach ($array_total_abertas as $semana => $dados_semana) {
                foreach ($dados_semana as $key => $value) {
                    $dados_total_abertas[] = array_sum($value);
                }
            }
            foreach ($tempo_oa as $key => $value) {
                switch ($key) {
                    case '0':
                        $table_serie_oa_zero = $value;
                        $serie_oa[] = array(
                            "name" => '0-10',
                            "color" => "#6d912a",
                            "data" => $value
                        );
                        break;
                    case '11':
                        $table_serie_oa_onze = $value;
                        $serie_oa[] = array(
                            "name" => '11-20',
                            "color" => "#a3a3a3",
                            "data" => $value
                        );
                        break;
                    case '21':
                        $table_serie_oa_vinte_um = $value;
                        $serie_oa[] = array(
                            "name" => '21-30',
                            "color" => "#598ff9",
                            "data" => $value
                        );
                        break;
                    case '31':
                        $table_serie_oa_trinta_um = $value;
                        $serie_oa[] = array(
                            "name" => '30-60',
                            "color" => "#ff9d00",
                            "data" => $value
                        );
                        break;
                    case '60':
                        $table_serie_oa_sessenta = $value;
                        $serie_oa[] = array(
                            "name" => '61+',
                            "color" => "#ff4d07",
                            "data" => $value
                        );
                        break;
                }

            }
            $categoria_oa = json_encode($categoria_oa);
            $serie_oa    = json_encode($serie_oa);
        }
        // FIM OS EM ABERTO
    }
}

$layout_menu = ($areaAdmin) ? "gerencia" : "os";
$title = "ORDENS ABERTAS X FINALIZADAS";

if ($areaAdmin) {
    include __DIR__."/admin/cabecalho_new.php";
} else {
    include __DIR__."/cabecalho_new.php";
}

$plugins = array(
   "select2",
   "highcharts",
   "highcharts_grouped_categories",
   "shadowbox",
   "dataTable",
   "mask",
   "datepicker"
);

include __DIR__."/admin/plugin_loader.php";

if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-danger">
        <?= implode("<br />", $msg_erro["msg"]); ?>
    </div>
<?php } ?>

<div class="tc_formulario" >
    <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
    <br />
    <form method="POST" class="form-search form-inline" >
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span3" >
                <div class="control-group" >
                    <label class="control-label" for="data_inicial" >Data Inicial</label>
                    <div class="controls controls-row" >
                        <div class="span10 input-append" >
                            <input type="text" name="data_inicial" id="data_inicial" class="span12" value="<?=getValue('data_inicial')?>" />
                            <span class="add-on" ><i class="icon-calendar" ></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3" >
                <div class="control-group" >
                    <label class="control-label" for="data_final" >Data Final</label>
                    <div class="controls controls-row" >
                        <div class="span10 input-append" >
                            <input type="text" name="data_final" id="data_final" class="span12" value="<?=getValue('data_final')?>" />
                            <span class="add-on" ><i class="icon-calendar" ></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span5" >
                <div class="control-group" >
                    <label class="control-label" for="posto_codigo_nome" >Posto Autorizado</label>
                    <div class="controls controls-row" >
                        <div class="span10 input-append" >
                            <?php
                            if (strlen(getValue("posto_id")) > 0) {
                                $input_readonly = "readonly";
                                $lupa_icon      = "icon-remove";
                                $lupa_acao      = "limpar";
                            } else {
                                $lupa_icon = "icon-search";
                                $lupa_acao = "pesquisar";
                            }
                            ?>
                            <input type="text" name="posto_codigo_nome" id="posto_codigo_nome" class="span12" value="<?=getValue('posto_codigo_nome')?>" placeholder="Código ou Nome" <?=$input_readonly?> />
                            <span class="add-on posto_lupa" data-acao="<?=$lupa_acao?>" ><i class="<?=$lupa_icon?>" ></i></span>
                            <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo_nome" />
                            <input type="hidden" name="posto_id" id="posto_id" value="<?=getValue('posto_id')?>" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="span5" >
                <div class="control-group" >
                    <label class="control-label" >Inspetor</label></label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select id="inspetor" name="inspetor[]" multiple >
                                <option value="" >Selecione</option>
                                <?php
                                $sqlInspetor = "
                                    SELECT admin, nome_completo, login
                                    FROM tbl_admin
                                    WHERE fabrica = {$login_fabrica}
                                    AND ativo IS TRUE
                                    AND admin_sap IS TRUE
                                    ORDER BY login
                                ";
                                $resInspetor = pg_query($con, $sqlInspetor);

                                while ($row = pg_fetch_object($resInspetor)) {
                                    $descricao = (!empty($row->nome_completo)) ? $row->nome_completo : $row->login;
                                    $selected = (in_array($row->admin, $_REQUEST["inspetor"])) ? "selected" : "";
                                    echo "<option value='{$row->admin}' {$selected} >{$descricao}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span3" >
                <div class="control-group" >
                    <label class="control-label" >Linha <label class="text-error">(ordens de serviço)</label></label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select id="linha" name="linha[]" class="span12" multiple>
                                <option value="" >Selecione</option>
                                <?php
                                $sqlLinha = "
                                    SELECT linha, nome
                                    FROM tbl_linha
                                    WHERE fabrica = {$login_fabrica}
                                    AND ativo IS TRUE
                                    ORDER BY nome
                                ";
                                $resLinha = pg_query($con, $sqlLinha);

                                while ($row = pg_fetch_object($resLinha)) {
                                    $selected = (in_array($row->linha, $_REQUEST["linha"])) ? "selected" : "";
                                    echo "<option value='{$row->linha}' {$selected} >{$row->nome}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span3" >
                <div class="control-group" >
                    <label class="control-label" for="familia" >Família <label class="text-error">(ordens de serviço)</label></label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select class="span12" name="familia[]" id="familia" multiple>
                                <option value="" >Selecione</option>
                                <?php
                                $sqlFamilia = "
                                    SELECT familia, descricao
                                    FROM tbl_familia
                                    WHERE fabrica = {$login_fabrica}
                                    ORDER BY descricao ASC
                                ";
                                $resFamilia = pg_query($con, $sqlFamilia);

                                while ($row = pg_fetch_object($resFamilia)) {
                                    $selected = (in_array($row->familia, $_REQUEST['familia'])) ? "selected" : "";
                                    echo "<option value='{$row->familia}' {$selected} >{$row->descricao}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" >Tipo de Atendimento <label class="text-error">(ordens de serviço)</label></label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select class="span12" id="tipo_atendimento" name="tipo_atendimento[]" multiple >
                                <?php
                                $sqlTipoAtendimento = "
                                    SELECT tipo_atendimento, descricao
                                    FROM tbl_tipo_atendimento
                                    WHERE fabrica = {$login_fabrica}
                                    AND ativo IS TRUE
                                    AND (fora_garantia IS NOT TRUE
                                    OR (fora_garantia IS TRUE
                                    AND grupo_atendimento IS NOT NULL))
                                    ORDER BY descricao
                                ";
                                $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

                                while ($row = pg_fetch_object($resTipoAtendimento)) {
                                    $selected = (in_array($row->tipo_atendimento, $_REQUEST["tipo_atendimento"])) ? "selected" : "";
                                    echo "<option value='{$row->tipo_atendimento}' {$selected} >{$row->descricao}</option>";
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" >Tipo de Posto Autorizado</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select class="span12" id="tipo_posto" name="tipo_posto[]" multiple >
                                <option value="" >Selecione</option>
                                <?php
                                $sqlTipoPosto = "
                                    SELECT tipo_posto, descricao
                                    FROM tbl_tipo_posto
                                    WHERE fabrica = {$login_fabrica}
                                    AND ativo IS TRUE
                                    ORDER BY descricao
                                ";
                                $resTipoPosto = pg_query($con, $sqlTipoPosto);

                                while ($row = pg_fetch_object($resTipoPosto)) {
                                    $selected = (in_array($row->tipo_posto, $_REQUEST["tipo_posto"])) ? "selected" : "";
                                    echo "<option value='{$row->tipo_posto}' {$selected} >{$row->descricao}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" >Estado do Posto Autorizado</label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select class="span12" id="estado" name="estado[]" multiple >
                                <?php
                                foreach ($array_estados() as $sigla => $estado) {
                                    $selected = (in_array($sigla, $_REQUEST["estado"])) ? "selected" : "";
                                    echo "<option value='{$sigla}' {$selected} >{$estado}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid" >
            <div class="span1" ></div>       
            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" >Status <label class="text-error">(ordens de serviço)</label></label>
                    <div class="controls controls-row" >
                        <div class="span12" >
                            <select class="span12" id="status_os" name="status_os[]" multiple >
                                <?php
                                $sql = "
                                    SELECT 
                                        status_checkpoint,
                                        descricao,
                                        CASE WHEN status_checkpoint = 0 THEN 0
                                        WHEN status_checkpoint = 1 THEN 1
                                        WHEN status_checkpoint = 2 THEN 2
                                        WHEN status_checkpoint = 8 THEN 3
                                        WHEN status_checkpoint = 3 THEN 4
                                        WHEN status_checkpoint = 4 THEN 5
                                        WHEN status_checkpoint = 14 THEN 6
                                        WHEN status_checkpoint = 30 THEN 7
                                        WHEN status_checkpoint = 28 THEN 8 END AS ordem
                                    FROM tbl_status_checkpoint
                                    WHERE status_checkpoint IN (0,1,2,8,3,4,14,30,28)
                                    ORDER BY ordem ASC
                                ";
                                $res = pg_query($con, $sql);
                                $res = pg_fetch_all($res);

                                foreach ($res as $s) {
                                    $selected = (in_array($s["status_checkpoint"], $_REQUEST["status_os"])) ? "selected" : "";
                                    echo "<option value='{$s['status_checkpoint']}' {$selected} >{$s['descricao']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row-fluid">
            <p class="tac">
                <button class='btn' id="btn_acao" type="button" onclick="submitForm($(this).parents('form'));">Pesquisar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p>
        </div>
    </form>
    <div class="alert alert-info">
        <strong>Se a pesquisa for realizada sem informar um período será mostrado o resultado dos últimos <?= $interval; ?> meses clique em Pesquisar.</strong>
    </div>
</div>
<div class="container tc_container" >
    <div class="tc_formulario" >
        <?php if ($countResAbxFi > 0) { ?>
            <div id="grafico_aberta_encerrada"></div>
            <hr />
        <?php } ?>
        
        <?php if ($countResAgvi > 0){ ?>
            <div id="grafico_agvi"></div>
            <br/>
            <div id="grafico_backlog"></div>
            <hr />
        <?php } ?>

        <?php if ($countTE > 0) { ?>
            <div id="grafico_te"></div>
            <hr />
        <?php } ?>

        <?php if ($countOA > 0) { ?>
            <div id="grafico_oa"></div>
            <hr />
        <?php } ?>
        
        <?php if (isset($_POST["btn_acao"]) AND empty($countResAgvi) AND empty($countResAgvi) AND empty($countTE) AND empty($countOA)) { ?>
            <div class="alert alert-danger" ><strong>Não foram encontrados Registros, <?=$interval_label?></strong></div>
        <?php } ?>
    </div>
</div>

<?php if ($countResAbxFi > 0 OR $countResAgvi > 0 OR $countTE > 0 OR $countOA > 0) { 
    foreach ($range_grafico as $ano => $meses) {
        foreach ($meses as $mes => $semanas) {
            foreach ($semanas as $key => $value) {
                $th_tabela_abxfi[$mes][] = $value;
            }
        }
    }
?>
    </div>
    <div class="container-fluid">
    <table id="table_abxfi" class='table table-bordered table-fixed' >
        <thead>
            <tr>
                <th rowspan="3"></th>
                <th colspan="<?=count($array_semanas)?>" class='titulo_tabela'>Meses / Semanas</th>
            </tr>
            <tr>
            <?php
                foreach ($th_tabela_abxfi as $key => $value) {
                    echo "<th colspan='".count($value)."' >$mesesx[$key]</th>";
                }
            ?>
            </tr>
            <tr>
                <?php 
                    foreach ($th_tabela_abxfi as $key => $value) {
                        foreach ($value as $keyx => $valuex) {
                           echo "<th>$valuex w</th>";
                        }
                    }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php if (count($countResAbxFi) > 0){ ?>
            <tr>
                <td class="titulo_coluna"></td>
                <td colspan="<?=count($array_semanas)?>" class='tac titulo_tabela'>Abertas X Encerradas</td>
            </tr>
            <?php } ?>
            <?php if (count($table_abxfi_qtde_abertas) > 0){ ?>
            <tr>
                <td class="titulo_coluna">Entrantes</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_abxfi_qtde_abertas)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_abxfi_qtde_finalizadas) > 0){ ?>
            <tr>
                <td class="titulo_coluna">Finalizada</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_abxfi_qtde_finalizadas)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_abxfi_percentual) > 0){ ?>
            <tr>
                <td class="titulo_coluna">Entrantes x Finalizada</td>
                <td class="tac"><?=implode('</td><td class="tac">',$table_abxfi_percentual)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_abxfi_qtde_andamento) > 0){ ?>
            <tr>
                <td class="titulo_coluna">Em Andamento</td>
                <td class="tac"><?=implode('</td><td class="tac">',$table_abxfi_qtde_andamento)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_abxfi_fechadas_soma) > 0){ ?>
            <tr>
                <td class="titulo_coluna">Fechadas</td>
                <td class="tac"><?=implode('</td><td class="tac">',$table_abxfi_fechadas_soma)?></td>
            </tr>
            <?php } ?>

            <?php if (count($countResAgvi) > 0){ ?>
            <tr>
                <td class="titulo_coluna"></td>
                <td colspan="<?=count($array_semanas)?>" class='tac titulo_tabela'>Indicadores</td>
            </tr>
            <?php } ?>

            <?php if (count($table_agvi) > 0){ ?>
            <tr>
                <td class="titulo_coluna">AGVI</td> 
                <td class="tac"><?=implode(' %</td><td class="tac">',$table_agvi)?> %</td>
            </tr>
            <?php } ?>

            <?php if (count($table_agvi_dez) > 0){ ?>
            <tr>
                <td class="titulo_coluna">TMA 10</td> 
                <td class="tac"><?=implode(' %</td><td class="tac">',$table_agvi_dez)?> %</td>
            </tr>
            <?php } ?>

            <?php if (count($table_agvi_trinta) > 0){ ?>
            <tr>
                <td class="titulo_coluna">TMA 30</td> 
                <td class="tac"><?=implode(' %</td><td class="tac">',$table_agvi_trinta)?> %</td>
            </tr>
            <?php } ?>

            <?php if (count($table_agvi_backlog) > 0){ ?>
            <tr>
                <td class="titulo_coluna">BackLog(+30)</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_agvi_backlog)?></td>
            </tr>
            <?php } ?>

            <?php if (count($countTE) > 0){ ?>
            <tr>
                <td class="titulo_coluna"></td>
                <td colspan="<?=count($array_semanas)?>" class='tac titulo_tabela'>Os Encerradas</td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_zero) > 0){ ?>
            <tr>
                <td class="titulo_coluna">0-10</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_zero)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_onze) > 0){ ?>
            <tr>
                <td class="titulo_coluna">11-20</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_onze)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_vinte_um) > 0){ ?>
            <tr>
                <td class="titulo_coluna">21-30</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_vinte_um)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_trinta_um) > 0){ ?>
            <tr>
                <td class="titulo_coluna">30-60</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_trinta_um)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_sessenta) > 0){ ?>
            <tr>
                <td class="titulo_coluna">61+</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_sessenta)?></td>
            </tr>
            <?php } ?>

            <?php if (count($dados_total_te) > 0){ ?>
            <tr>
                <td class="titulo_coluna">TOTAL</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$dados_total_te)?></td>
            </tr>
            <?php } ?>

            <?php if (count($countOA) > 0){ ?>
            <tr>
                <td class="titulo_coluna"></td>
                <td colspan="<?=count($array_semanas)?>" class='tac titulo_tabela'>Os em Andamento</td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_oa_zero) > 0){ ?>
            <tr>
                <td class="titulo_coluna">0-10</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_oa_zero)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_oa_onze) > 0){ ?>
            <tr>
                <td class="titulo_coluna">11-20</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_oa_onze)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_oa_vinte_um) > 0){ ?>
            <tr>
                <td class="titulo_coluna">21-30</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_oa_vinte_um)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_oa_trinta_um) > 0){ ?>
            <tr>
                <td class="titulo_coluna">30-60</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_oa_trinta_um)?></td>
            </tr>
            <?php } ?>

            <?php if (count($table_serie_oa_sessenta) > 0){ ?>
            <tr>
                <td class="titulo_coluna">61+</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$table_serie_oa_sessenta)?></td>
            </tr>
            <?php } ?>

            <?php if (count($dados_total_abertas) > 0){ ?>
            <tr>
                <td class="titulo_coluna">TOTAL</td> 
                <td class="tac"><?=implode('</td><td class="tac">',$dados_total_abertas)?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
<?php }?>
<script>
    Shadowbox.init();

    $("span.posto_lupa").on("click", function() {
        var acao = $(this).data("acao");

        if (acao == "pesquisar") {
            $.lupa($(this));
        } else {
            $("#posto_codigo_nome").val("").prop({ readonly: false });
            $("#posto_id").val("");
            $("span.posto_lupa").data({ "acao": "pesquisar" }).find("i").removeClass("icon-remove").addClass("icon-search");
        }
    });

    function retorna_posto(response) {
        $("#posto_codigo_nome").val(response.codigo + " - " + response.nome).prop({ readonly: true });
        $("#posto_id").val(response.posto);
        $("span.posto_lupa").data({ "acao": "limpar" }).find("i").removeClass("icon-search").addClass("icon-remove");
    }

    $("select").select2();

    $.datepickerLoad(["data_inicial", "data_final"]);

    <?php if ($countResAbxFi > 0) { ?>
        Highcharts.chart('grafico_aberta_encerrada', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Aberta X Encerrada'
            },
            subtitle: {
                text: '<?=$interval_label?>'
            },
            xAxis: {
                categories: <?=$categoria_geral?>
            },
            yAxis: {
                title: {
                    text: ''
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: [{
                name: 'Abertas',
                color: '#ff9d00',
                data: <?=$abxfi_qtde_abertas?>
            }, {
                name: 'Finalizadas',
                color: '#598ff9',
                data: <?=$abxfi_qtde_finalizadas?>
            }]
        });
    <?php } ?>

    <?php if ($countTE > 0){ ?>
        Highcharts.chart('grafico_te', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Tempo de Encerramento'
            },
            subtitle: {
                text: '<?=$interval_label?>'
            },
            xAxis: {
                categories: <?=$categoria_geral?>
            },
            yAxis: {
                title: {
                    text: ''
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: <?=$serie_te?>
        });
    <?php } ?>

    <?php if ($countOA > 0){ ?>
        Highcharts.chart('grafico_oa', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Aging da OS em aberto'
            },
            subtitle: {
                text: '<?=$interval_label?>'
            },
            xAxis: {
                categories: <?=$categoria_geral?>
            },
            yAxis: {
                title: {
                    text: ''
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: <?=$serie_oa?>
        });
    <?php } ?>

    <?php if ($countResAgvi > 0){ ?>
        Highcharts.chart('grafico_agvi', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Indicadores'
            },
            subtitle: {
                text: '<?=$interval_label?>'
            },
            xAxis: {
                categories: <?=$categoria_geral?>
            },
            yAxis: {
                title: {
                    text: ''
                },
                labels: {
                    format: '{value} %'
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: <?=$serie_agvi?>
        });
        
        Highcharts.chart('grafico_backlog', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'BackLog(+30)'
            },
            subtitle: {
                text: '<?=$interval_label?>'
            },
            xAxis: {
                categories: <?=$categoria_geral?>
            },
            yAxis: {
                title: {
                    text: ''
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: <?=$serie_b?>
        });
    <?php } ?>
</script>

<?php
include "rodape.php";
?>
