<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$jsonPOST = excelPostToJson($_POST);

function trocaPostToJson($post) {
    $json = array();

    $json["gerar_troca_excel"] = true;

    foreach ($post as $key => $value) {
        if (!is_array($value)) {
            $json[$key] = utf8_encode($value);
        } else {
            $json[$key] = $value;
        }
    }

    return json_encode($json);
}

function reincidentePostToJson($post) {
    $json = array();

    $json["gerar_reincidente_excel"] = true;

    foreach ($post as $key => $value) {
        if (!is_array($value)) {
            $json[$key] = utf8_encode($value);
        } else {
            $json[$key] = $value;
        }
    }

    return json_encode($json);
}

function reagendamentoPostToJson($post) {
    $json = array();

    $json["gerar_reagendamento_excel"] = true;

    foreach ($post as $key => $value) {
        if (!is_array($value)) {
            $json[$key] = utf8_encode($value);
        } else {
            $json[$key] = $value;
        }
    }

    return json_encode($json);
}

function spvPostToJson($post) {
    $json = array();

    $json["gerar_spv_excel"] = true;

    foreach ($post as $key => $value) {
        if (!is_array($value)) {
            $json[$key] = utf8_encode($value);
        } else {
            $json[$key] = $value;
        }
    }

    return json_encode($json);
}

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $posto_codigo       = $_POST['codigo_posto'];
    $descricao_posto    = $_POST['descricao_posto'];
    $linha              = $_POST['linha'];
    $familia            = $_POST['familia'];

    $relatorio_km_mo_va             = $_POST['relatorio_km_mo_va'];
    $relatorio_troca_ressarcimento  = $_POST['relatorio_troca_ressarcimento'];
    $relatorio_reincidencia         = $_POST['relatorio_reincidencia'];
    $relatorio_reagendamento        = $_POST['relatorio_reagendamento'];
    $relatorio_spv                  = $_POST['relatorio_spv'];
    $relatorio_agvi                 = $_POST['relatorio_agvi'];
    $pedido_peca                    = $_POST['pedido_peca'];

   
    if (empty($relatorio_km_mo_va) AND empty($relatorio_troca_ressarcimento) AND empty($relatorio_reincidencia) AND empty($relatorio_reagendamento) AND empty($relatorio_spv) AND empty($relatorio_agvi)){
        $msg_erro["msg"][]    = "Selecione um tipo de relatório";
        $msg_erro["campos"][] = "";
    }

    if (strlen($posto_codigo) > 0 or strlen($descricao_posto) > 0){
        $sql = "SELECT tbl_posto_fabrica.posto
                FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND (
                    (UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$posto_codigo}'))
                    OR
                    (TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
                )";
        $res = pg_query($con ,$sql);

        if (!pg_num_rows($res)) {
            $msg_erro["msg"][]    = "Posto não encontrado";
            $msg_erro["campos"][] = "posto";
        } else {
            $posto = pg_fetch_result($res, 0, "posto");
        }
    }

    if (!empty($tipo_posto)){
        $cond_tipo = " AND tbl_posto_fabrica.tipo_posto = $tipo_posto ";
    }



    if(in_array($login_fabrica, [169,170])) { 
        
        if (count($pedido_peca) > 0) {

            if (count($pedido_peca) <= 1) {

                   if(in_array('sem_pedido', $pedido_peca)) {
                    $cond_tipo .= " AND (SELECT tbl_os_item.pedido 
                                         FROM tbl_os AS o 
                                         JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                         JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                         WHERE tbl_os_item.pedido IS NOT NULL
                                         AND o.os = tbl_os.os
                                         LIMIT 1) IS NULL  "; //traz todas as OS que nao tem pedido
                                         
                   }
                
                   if(in_array('com_pedido', $pedido_peca)) {
                    $cond_tipo .= "  AND (SELECT tbl_os_item.pedido 
                                         FROM tbl_os AS o 
                                         JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                         JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                         WHERE tbl_os_item.pedido IS NOT NULL
                                         AND o.os = tbl_os.os
                                         LIMIT 1) IS NOT NULL  "; //traz todas as OS que tem pedido
                   }
             } 
        }
    }       

    
    if (!strlen($data_inicial) or !strlen($data_final)) {
        $msg_erro["msg"][]    = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "data";
    } else {
        list($di, $mi, $yi) = explode("/", $data_inicial);
        list($df, $mf, $yf) = explode("/", $data_final);

        if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_inicial = "{$yi}-{$mi}-{$di}";
            $aux_data_final   = "{$yf}-{$mf}-{$df}";

            if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
                $msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
                $msg_erro["campos"][] = "data";
            }

            $sql = "SELECT '$aux_data_final'::date - INTERVAL '6 MONTHS' > '$aux_data_inicial'::date ";
            $res = pg_query ($con,$sql);
            if (pg_fetch_result($res,0,0) == 't') {
                $msg_erro["msg"][]    = "A data de consulta deve ser no máximo de 6 meses.";
                $msg_erro["campos"][] = "data";
            }
        }
    }

    if (!count($msg_erro["msg"])) {
        
        if (!empty($posto)) {
            $cond_posto = " AND tbl_os.posto = {$posto} ";
        }else{
            $cond_posto = " AND tbl_os.posto <> 6359 ";
        }

        /*SQL KM MO VA*/
        if ($relatorio_km_mo_va == "true"){
            
            $sql_dados = "
                SELECT 
                    SUM(COALESCE(tbl_os.mao_de_obra, 0)) AS mao_de_obra,
                    SUM(COALESCE(tbl_os.qtde_km_calculada, 0)) AS qtde_km,
                    SUM(COALESCE(tbl_os.valores_adicionais, 0)) AS valores_adicionais,
                    SUM(COALESCE(tbl_os.mao_de_obra, 0)) + SUM(COALESCE(tbl_os.qtde_km_calculada, 0)) + SUM(COALESCE(tbl_os.valores_adicionais, 0)) AS total_geral,
                    tbl_posto.posto,
                    tbl_posto.nome,
                    tbl_posto_fabrica.codigo_posto
                FROM tbl_posto_fabrica
                JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                JOIN tbl_os ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = $login_fabrica
                {$join_linha}
                WHERE tbl_os.finalizada IS NOT NULL
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                {$cond_posto}
                {$cond_tipo}
                GROUP by tbl_posto.posto, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
                ORDER BY total_geral DESC";
                 
            $res_dados = pg_query($con, $sql_dados);
            
        }


        /*SQL RESSARCIMENTO TROCA*/
        if ($relatorio_troca_ressarcimento == "true"){
             
            $sql_ressarcimento_troca = "
                SELECT 
                    codigo_posto,
                    nome,
                    posto,
                    COALESCE(MAX(qtde_os_ressarcimento),0) AS qtde_os_ressarcimento,
                    COALESCE(MIN(qtde_os_troca),0) AS qtde_os_troca,
                    MAX(dados_os_ressarcimento) AS dados_os_ressarcimento,
                    MAX(dados_os_troca) AS dados_os_troca
                FROM (
                            SELECT
                                tbl_posto.nome,
                                tbl_posto.posto,
                                tbl_posto_fabrica.codigo_posto,
                                null AS qtde_os_troca,
                                COUNT(tbl_os_troca.os_troca) AS qtde_os_ressarcimento,
                                ARRAY_TO_STRING(array_agg(DISTINCT(tbl_os.sua_os || '-' || CASE WHEN TRIM(tbl_os.consumidor_nome) = '' THEN tbl_os.revenda_nome ELSE tbl_os.consumidor_nome END )), ', ', null) AS dados_os_ressarcimento,
                                null AS dados_os_troca
                            FROM tbl_posto_fabrica
                            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            JOIN tbl_os ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = $login_fabrica
                            JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.fabric = $login_fabrica
                            WHERE tbl_os.excluida IS NOT TRUE
                            AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                            {$cond_posto}
                            {$cond_tipo}
                            AND tbl_os_troca.ressarcimento IS TRUE
                            GROUP BY tbl_posto.nome, tbl_posto.posto, tbl_posto_fabrica.codigo_posto
                        UNION
                            SELECT
                                tbl_posto.nome,
                                tbl_posto.posto,
                                tbl_posto_fabrica.codigo_posto,
                                COUNT(tbl_os_troca.os_troca) AS qtde_os_troca,
                                null AS qtde_os_ressarcimento,
                                null AS dados_os_ressarcimento,
                                ARRAY_TO_STRING(array_agg(DISTINCT(tbl_os.sua_os || '-' || CASE WHEN TRIM(tbl_os.consumidor_nome) = '' THEN tbl_os.revenda_nome ELSE tbl_os.consumidor_nome END )), ', ', null) AS dados_os_troca
                            FROM tbl_posto_fabrica
                            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                            JOIN tbl_os ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = $login_fabrica
                            JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.fabric = $login_fabrica
                            WHERE tbl_os.excluida IS NOT TRUE
                            AND tbl_os_troca.ressarcimento IS FALSE
                            AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                            {$cond_posto}
                            {$cond_tipo}
                            GROUP BY tbl_posto.nome, tbl_posto.posto, tbl_posto_fabrica.codigo_posto
                    ) x

                GROUP BY codigo_posto, nome, posto
                ORDER BY codigo_posto;
            ";
            $res_ressarcimento_troca = pg_query($con, $sql_ressarcimento_troca);
            
            $dados_ressarcimento_troca = array();
            
            for ($i=0; $i < pg_num_rows($res_ressarcimento_troca); $i++) { 
                $posto_nome                     = pg_fetch_result($res_ressarcimento_troca, $i, 'nome');
                $qtde_os_ressarcimento          = pg_fetch_result($res_ressarcimento_troca, $i, 'qtde_os_ressarcimento');
                $os_trocas                      = pg_fetch_result($res_ressarcimento_troca, $i, 'trocas');
                $codigo_posto                   = pg_fetch_result($res_ressarcimento_troca, $i, 'codigo_posto');
                $qtde_os_troca                  = pg_fetch_result($res_ressarcimento_troca, $i, 'qtde_os_troca');
                $dados_os_ressarcimento         = pg_fetch_result($res_ressarcimento_troca, $i, 'dados_os_ressarcimento');
                $dados_os_troca                 = pg_fetch_result($res_ressarcimento_troca, $i, 'dados_os_troca');
                $posto                          = pg_fetch_result($res_ressarcimento_troca, $i, 'posto');

                $total_troca_ressarcimento = $qtde_os_troca+$qtde_os_ressarcimento;

                $dados_ressarcimento_troca[$posto]['posto'] = $posto;
                $dados_ressarcimento_troca[$posto]['codigo_posto'] = $codigo_posto;
                $dados_ressarcimento_troca[$posto]['posto_nome'] = $posto_nome;

                $dados_ressarcimento_troca[$posto]['qtde_os_ressarcimento'] = $qtde_os_ressarcimento;
                $dados_ressarcimento_troca[$posto]['ressarcimento'] = $os_ressarcimento;
                $dados_ressarcimento_troca[$posto]['dados_os_ressarcimento'] = $dados_os_ressarcimento;
                
                $dados_ressarcimento_troca[$posto]['qtde_os_troca'] = $qtde_os_troca;
                $dados_ressarcimento_troca[$posto]['os_trocas'] = $os_trocas;
                $dados_ressarcimento_troca[$posto]['dados_os_troca'] = $dados_os_troca;
                
                $dados_ressarcimento_troca[$posto]['total_troca_ressarcimento'] = $total_troca_ressarcimento;
            }
            
            $total_dados_order_ressarcimento_troca = array_map(function($v) {
                return (integer) $v["total_troca_ressarcimento"];
            }, $dados_ressarcimento_troca);
            array_multisort($total_dados_order_ressarcimento_troca, SORT_DESC, SORT_NUMERIC, $dados_ressarcimento_troca);
        }

        /*SQL REINCIDENTE*/
        if ($relatorio_reincidencia == "true"){
             
            $sql_reincidente = "
                SELECT
                    tbl_posto.nome AS posto_nome,
                    tbl_posto.posto,
                    tbl_posto_fabrica.codigo_posto,
                    COUNT(tbl_os_extra.os_reincidente) AS qtde_os_reincidente,
                    ARRAY_TO_STRING(array_agg(DISTINCT(tbl_os.sua_os || '-' || CASE WHEN TRIM(tbl_os.consumidor_nome) = '' THEN tbl_os.revenda_nome ELSE tbl_os.consumidor_nome END )), ', ', null) AS dados_os_reincidente
                FROM tbl_posto_fabrica
                JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                JOIN tbl_os ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = $login_fabrica
                JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                WHERE tbl_os.excluida IS NOT TRUE
                AND tbl_os_extra.os_reincidente IS NOT NULL
                AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                {$cond_posto}
                {$cond_tipo}
                GROUP BY tbl_posto.nome, tbl_posto.posto, tbl_posto_fabrica.codigo_posto";
            
            $res_reincidente = pg_query($con, $sql_reincidente);
            $dados_reincidente = pg_fetch_all($res_reincidente);

            $total_dados_order_reincidente = array_map(function($v) {
                return (integer) $v["qtde_os_reincidente"];
            }, $dados_reincidente);
            array_multisort($total_dados_order_reincidente, SORT_DESC, SORT_NUMERIC, $dados_reincidente);
        }
        
        /*SQL REAGENDAMENTO*/
        if ($relatorio_reagendamento == "true"){
            
            $sql_reagendamento = "
                SELECT
                    tbl_posto.nome AS posto_nome,
                    tbl_posto.posto,
                    tbl_posto_fabrica.codigo_posto,
                    COUNT(tbl_tecnico_agenda.os) AS qtde_reagendamento,
                    ARRAY_TO_STRING(array_agg(DISTINCT(tbl_os.sua_os || '-' || CASE WHEN TRIM(tbl_os.consumidor_nome) = '' THEN tbl_os.revenda_nome ELSE tbl_os.consumidor_nome END )), ', ', null) AS dados_os_reagendamento
                FROM tbl_posto_fabrica
                JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                JOIN tbl_os ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = $login_fabrica
                JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = $login_fabrica
                WHERE tbl_os.excluida IS NOT TRUE
                AND tbl_tecnico_agenda.ordem > 1
                AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                {$cond_posto}
                {$cond_tipo}
                GROUP BY tbl_posto.nome, tbl_posto.posto, tbl_posto_fabrica.codigo_posto";
            $res_reagendamento = pg_query($con, $sql_reagendamento);
            
            $dados_reagendamento = pg_fetch_all($res_reagendamento);

            $total_dados_order_reagendamento = array_map(function($v) {
                return (integer) $v["qtde_reagendamento"];
            }, $dados_reagendamento);
            array_multisort($total_dados_order_reagendamento, SORT_DESC, SORT_NUMERIC, $dados_reagendamento);
        }

        /*SQL SPV(Solução primeira visita)*/
        if ($relatorio_spv == "true"){
           
            $sql_spv = "
                SELECT 
                    codigo_posto,
                    nome AS posto_nome,
                    posto,
                    MAX(os_sem_deslocamento_finalizada) AS os_sem_deslocamento_finalizada,
                    COALESCE(MAX(qtde_os_sem_deslocamento_finalizada),0) AS qtde_os_sem_deslocamento_finalizada,
                    MIN(os_deslocamento_finalizada) AS os_deslocamento_finalizada,
                    COALESCE(MIN(qtde_os_deslocamento_finalizada),0) AS qtde_os_deslocamento_finalizada,
                    MAX(dados_os_sem_deslocamento_finalizada) AS dados_os_sem_deslocamento_finalizada,
                    MAX(dados_os_deslocamento_finalizada) AS dados_os_deslocamento_finalizada
                FROM (
                        SELECT
                            tbl_posto.nome,
                            tbl_posto.posto,
                            tbl_posto_fabrica.codigo_posto,
                            null AS os_deslocamento_finalizada,
                            null AS qtde_os_deslocamento_finalizada,
                            ARRAY_AGG(tbl_os.os) AS os_sem_deslocamento_finalizada,
                            COUNT(tbl_os.os) AS qtde_os_sem_deslocamento_finalizada,
                            ARRAY_TO_STRING(array_agg(DISTINCT(tbl_os.sua_os || '-' || CASE WHEN TRIM(tbl_os.consumidor_nome) = '' THEN tbl_os.revenda_nome ELSE tbl_os.consumidor_nome END )), ', ', null) AS dados_os_sem_deslocamento_finalizada,
                            null AS dados_os_deslocamento_finalizada
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        JOIN tbl_os ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = $login_fabrica
                        WHERE tbl_os.excluida IS NOT TRUE
                        AND tbl_os.finalizada IS NOT NULL
                        AND tbl_os.fabrica = $login_fabrica
                        AND tbl_os.qtde_km IS NULL
                        AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                        {$cond_posto}
                        {$cond_tipo}
                        AND( 
                                tbl_os.data_abertura = tbl_os.finalizada::date 
                            OR 
                                tbl_os.data_abertura + interval '1 day' = tbl_os.finalizada::date 
                        )
                        GROUP BY tbl_posto.nome, tbl_posto.posto, tbl_posto_fabrica.codigo_posto
                    UNION
                        SELECT
                            tbl_posto.nome,
                            tbl_posto.posto,
                            tbl_posto_fabrica.codigo_posto,
                            ARRAY_AGG(tbl_os.os) as os_deslocamento_finalizada,
                            COUNT(tbl_os.os) AS qtde_os_deslocamento_finalizada,
                            null AS os_sem_deslocamento_finalizada,
                            null AS qtde_os_sem_deslocamento_finalizada,
                            null AS dados_os_sem_deslocamento_finalizada,
                            ARRAY_TO_STRING(array_agg(DISTINCT(tbl_os.sua_os || '-' || CASE WHEN TRIM(tbl_os.consumidor_nome) = '' THEN tbl_os.revenda_nome ELSE tbl_os.consumidor_nome END )), ', ', null) AS dados_os_deslocamento_finalizada
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        JOIN tbl_os ON tbl_os.posto = tbl_posto.posto AND tbl_os.fabrica = $login_fabrica
                        WHERE tbl_os.excluida IS NOT TRUE
                        AND tbl_os.finalizada IS NOT NULL
                        AND tbl_os.fabrica = $login_fabrica
                        AND tbl_os.data_abertura BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                        {$cond_posto}
                        {$cond_tipo}
                        AND(
                                (
                                    SELECT tbl_tecnico_agenda.data_agendamento::date
                                    FROM tbl_tecnico_agenda
                                    WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
                                    AND tbl_tecnico_agenda.os = tbl_os.os
                                    AND tbl_tecnico_agenda.confirmado IS NOT NULL
                                    AND tbl_os.fabrica = $login_fabrica
                                    ORDER BY tbl_tecnico_agenda.data_input DESC
                                    LIMIT 1
                                ) = tbl_os.finalizada::date 
                            OR (
                                    SELECT tbl_tecnico_agenda.data_agendamento::date + interval '1 day'
                                    FROM tbl_tecnico_agenda
                                    WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
                                    AND tbl_tecnico_agenda.os = tbl_os.os
                                    AND tbl_tecnico_agenda.confirmado IS NOT NULL
                                    AND tbl_os.fabrica = $login_fabrica
                                    ORDER BY tbl_tecnico_agenda.data_input DESC
                                    LIMIT 1
                                ) = tbl_os.finalizada::date
                        )
                        GROUP BY tbl_posto.nome, tbl_posto.posto, tbl_posto_fabrica.codigo_posto
                ) x
                GROUP BY codigo_posto, nome, posto
                ORDER BY codigo_posto ";
            $res_spv = pg_query($con, $sql_spv);

            $dados_spv = array();
            if (pg_num_rows($res_spv) > 0){
                for ($i=0; $i < pg_num_rows($res_spv); $i++) { 
                    $codigo_posto                           = pg_fetch_result($res_spv, $i, 'codigo_posto');
                    $posto_nome                             = pg_fetch_result($res_spv, $i, 'posto_nome');
                    $posto                                  = pg_fetch_result($res_spv, $i, 'posto');
                    $os_sem_deslocamento_finalizada         = pg_fetch_result($res_spv, $i, 'os_sem_deslocamento_finalizada');
                    $qtde_os_sem_deslocamento_finalizada    = pg_fetch_result($res_spv, $i, 'qtde_os_sem_deslocamento_finalizada');
                    $os_deslocamento_finalizada             = pg_fetch_result($res_spv, $i, 'os_deslocamento_finalizada');
                    $qtde_os_deslocamento_finalizada        = pg_fetch_result($res_spv, $i, 'qtde_os_deslocamento_finalizada');
                    $dados_os_sem_deslocamento_finalizada   = pg_fetch_result($res_spv, $i, 'dados_os_sem_deslocamento_finalizada');
                    $dados_os_deslocamento_finalizada       = pg_fetch_result($res_spv, $i, 'dados_os_deslocamento_finalizada');
                
                    $total_spv = $qtde_os_deslocamento_finalizada+$qtde_os_sem_deslocamento_finalizada;

                    $dados_spv[$posto]['posto']                                 = $posto;
                    $dados_spv[$posto]['codigo_posto']                          = $codigo_posto;
                    $dados_spv[$posto]['posto_nome']                            = $posto_nome;

                    $dados_spv[$posto]['qtde_os_sem_deslocamento_finalizada']   = $qtde_os_sem_deslocamento_finalizada;
                    $dados_spv[$posto]['os_sem_deslocamento_finalizada']        = $os_sem_deslocamento_finalizada;
                    $dados_spv[$posto]['dados_os_sem_deslocamento_finalizada']  = $dados_os_sem_deslocamento_finalizada;
                    
                    $dados_spv[$posto]['qtde_os_deslocamento_finalizada']       = $qtde_os_deslocamento_finalizada;
                    $dados_spv[$posto]['os_deslocamento_finalizada']            = $os_deslocamento_finalizada;
                    $dados_spv[$posto]['dados_os_deslocamento_finalizada']      = $dados_os_deslocamento_finalizada;
                    
                    $dados_spv[$posto]['total_spv']                             = $total_spv;

                }
            }
            
            $total_dados_order_spv = array_map(function($v) {
                return (integer) $v["total_spv"];
            }, $dados_spv);
            array_multisort($total_dados_order_spv, SORT_DESC, SORT_NUMERIC, $dados_spv);
        }

        /*SQL AGVI*/
        if ($relatorio_agvi == "true"){
           
            $sqlFeriados = "
                SELECT data
                FROM tbl_feriado
                WHERE fabrica = {$login_fabrica}
                AND ativo IS TRUE
                AND (data BETWEEN '".date("Y-m-d", strtotime($aux_data_inicial))."' AND '".date("Y-m-d", strtotime($aux_data_final))."')
            ";
            $resFeriados = pg_query($con, $sqlFeriados);
            $resFeriados = array_map(function($f) {
                return $f["data"];
            }, pg_fetch_all($resFeriados));

            $sqlQtdeOs = "
                SELECT
                    tbl_os.os,
                    tbl_os.sua_os,
                    tbl_os.status_checkpoint,
                    tbl_status_checkpoint.descricao AS status_checkpoint_descricao,
                    tbl_posto.nome AS posto,
                    tbl_admin.nome_completo AS admin_nome,
                    tbl_admin.login AS admin_login,
                    TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
                    tbl_tipo_atendimento.descricao AS tipo_atendimento,
                    CASE WHEN tbl_status_checkpoint.status_checkpoint = 0 THEN
                        (
                            SELECT tbl_tecnico_agenda.data_agendamento::date
                            FROM tbl_tecnico_agenda
                            WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
                            AND tbl_tecnico_agenda.os = tbl_os.os
                            ORDER BY tbl_tecnico_agenda.data_input ASC
                            LIMIT 1
                        )
                    ELSE
                        (
                            SELECT tbl_tecnico_agenda.data_agendamento::date
                            FROM tbl_tecnico_agenda
                            WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
                            AND tbl_tecnico_agenda.os = tbl_os.os
                            /*AND tbl_tecnico_agenda.confirmado IS NOT NULL*/
                            ORDER BY tbl_tecnico_agenda.data_input ASC
                            LIMIT 1
                        ) 
                    END AS primeira_data_agendamento,
                    (
                        SELECT tbl_tecnico_agenda.data_agendamento::date
                        FROM tbl_tecnico_agenda
                        WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
                        AND tbl_tecnico_agenda.os = tbl_os.os
                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
                        ORDER BY tbl_tecnico_agenda.data_input DESC
                        LIMIT 1
                    ) AS ultima_data_agendamento,
                    (
                        SELECT tbl_tecnico_agenda.confirmado::date
                        FROM tbl_tecnico_agenda
                        WHERE tbl_tecnico_agenda.fabrica = $login_fabrica
                        AND tbl_tecnico_agenda.os = tbl_os.os
                        AND tbl_tecnico_agenda.confirmado IS NOT NULL
                        ORDER BY tbl_tecnico_agenda.data_input ASC
                        LIMIT 1
                    ) AS data_confirmacao,
                    tbl_os.data_abertura,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome AS nome_posto,
                    tbl_tipo_atendimento.km_google AS tipo_atendimento_deslocamento
                FROM tbl_os
                INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
                INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap AND tbl_admin.fabrica = $login_fabrica
                INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                WHERE tbl_os.fabrica = $login_fabrica
                AND tbl_os.excluida IS NOT TRUE
                $cond_posto
                AND tbl_os.data_digitacao BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}'
                {$cond_tipo}
                AND tbl_os.finalizada IS NULL
                AND (
                        tbl_tipo_atendimento.fora_garantia IS NOT TRUE 
                    OR (
                        tbl_tipo_atendimento.fora_garantia IS TRUE
                        AND tbl_tipo_atendimento.grupo_atendimento IS NOT NULL
                    )
                )
                ORDER BY tbl_os.status_checkpoint";
             $resQtdeOs = pg_query($con, $sqlQtdeOs);

            if (pg_num_rows($resQtdeOs) > 0){
                
                $os_lista       = array();
                pg_result_seek($resQtdeOs, 0);

                $os_tipo_atendimento_status_inspetor = array();
                $os_em_atraso                        = array();
                $os_tipo_atendimento                 = array();
                $os_status                           = array();

                $arquivo_os_csv = "xls/dashboard_relatorio_os_".date("c").".csv";
                $file = fopen($arquivo_os_csv, "w");

                $headers = array(
                    "'ordem de serviço'",
                    "'data de abertura'",
                    "'primeira data de agendamento'",
                    "'data de confirmação do posto autorizado'",
                    "'última data de agendamento'",
                    "'código do posto autorizado'",
                    "'nome do posto autorizado'",
                    "'status da ordem de serviço'"
                );
                fwrite($file, implode(";", $headers)."\n");

                while ($row = pg_fetch_object($resQtdeOs)) {
                    unset($primeira_data_agendamento);
                    unset($data_confirmacao);
                    unset($ultima_data);

                    if (empty($row->posto)){
                        $key_posto = "Sem Posto";
                    }else{
                        $key_posto = $row->posto;
                    }

                    if (!empty($row->primeira_data_agendamento) && $row->primeira_data_agendamento == $row->ultima_data_agendamento){
                        $ultima_data = "";
                    } else {
                        $ultima_data = $row->ultima_data_agendamento;
                    }

                    if (empty($row->primeira_data_agendamento)){
                        $primeira_data_agendamento = "Sem agendamento";
                    }else{
                        $primeira_data_agendamento = mostra_data($row->primeira_data_agendamento);
                    }

                    if (empty($row->data_confirmacao)){
                        $data_confirmacao = "Não confirmado";
                    }else{
                        $data_confirmacao = mostra_data($row->data_confirmacao);
                    }

                    if (empty($ultima_data)){
                        $ultima_data = "Sem reagendamento";
                    }else{
                        $ultima_data = mostra_data($ultima_data);
                    }

                    $os = array(
                        "os"                        => $row->os,
                        "sua_os"                    => $row->sua_os,
                        "data_digitacao"            => $row->data_digitacao,
                        "status"                    => utf8_encode($row->status_checkpoint_descricao),
                        "posto"                     => utf8_encode($key_posto),
                        "tipo_atendimento"          => utf8_encode($row->tipo_atendimento),
                        "primeira_data_agendamento" => $primeira_data_agendamento,
                        "data_confirmacao"          => utf8_encode($data_confirmacao),
                        "data_reagendamento"        => $ultima_data
                    );
                    
                    if ($key_posto != "Sem Posto") {
                        $os_tipo_atendimento[] = $row->tipo_atendimento;
                        $os_status[]           = $row->status_checkpoint_descricao;

                        if (!isset($os_tipo_atendimento_status_inspetor[$key_posto])) {
                            $os_tipo_atendimento_status_inspetor[$key_posto] = array();
                        }

                        if (!isset($os_tipo_atendimento_status_inspetor[$key_posto][$row->status_checkpoint_descricao])) {
                            $os_tipo_atendimento_status_inspetor[$key_posto][$row->status_checkpoint_descricao] = array();
                        }

                        if (!isset($os_tipo_atendimento_status_inspetor[$key_posto][$row->status_checkpoint_descricao][$row->tipo_atendimento])) {
                            $os_tipo_atendimento_status_inspetor[$key_posto][$row->status_checkpoint_descricao][$row->tipo_atendimento] = array();
                        }

                        $os_tipo_atendimento_status_inspetor[$key_posto][$row->status_checkpoint_descricao][$row->tipo_atendimento][] = $os;
                        
                        $os["atrasada"] = false;

                        if ($row->tipo_atendimento_deslocamento == "t") {
                            if ($row->status_checkpoint_descricao == "Aberta Call-Center") {
                                $previsao_atendimento = $row->data_abertura;

                                switch (date("w", strtotime($previsao_atendimento))) {
                                    case 4:
                                    case 5:
                                        $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +4 days"));
                                        break;
                                    
                                    default:
                                        $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +2 days"));
                                        break;
                                }

                                while (in_array($previsao_atendimento, $resFeriados)) {
                                    switch (date("w", strtotime($previsao_atendimento))) {
                                        case 4:
                                        case 5:
                                            $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +4 days"));
                                            break;
                                        
                                        default:
                                            $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +2 days"));
                                            break;
                                    }
                                }

                                if (strtotime($previsao_atendimento) < strtotime(date("Y-m-d", strtotime("today")))) {
                                    $os_em_atraso[$key_posto][$row->status_checkpoint_descricao][$row->tipo_atendimento][] = $os;
                                    $os["atrasada"] = true;
                                }
                            } else if ($row->status_checkpoint_descricao == "Aguardando Analise") {
                                $previsao_atendimento = $row->ultima_data_agendamento;

                                if (date("w", strtotime($previsao_atendimento)) == 5) {
                                    $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +3 days"));
                                } else {
                                    $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +1 day"));
                                }

                                while (in_array($previsao_atendimento, $resFeriados)) {
                                    if (date("w", strtotime($previsao_atendimento)) == 5) {
                                        $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +3 days"));
                                    } else {
                                        $previsao_atendimento = date("Y-m-d", strtotime($previsao_atendimento." +1 day"));
                                    }
                                }
                                
                                if (strtotime($previsao_atendimento) < strtotime(date("Y-m-d", strtotime("today")))) {
                                    $os_em_atraso[$key_posto][$row->status_checkpoint_descricao][$row->tipo_atendimento][] = $os;
                                    $os["atrasada"] = true;
                                }
                            }
                        }
                    }
                    $os_lista[] = $os;

                    $values = array(
                        "'{$row->sua_os}'",
                        "'".date("d/m/Y", strtotime($row->data_abertura))."'",
                        "'{$primeira_data_agendamento}'",
                        "'{$data_confirmacao}'",
                        "'{$ultima_data}'",
                        "'{$row->codigo_posto}'",
                        "'{$row->nome_posto}'",
                        "'{$row->status_checkpoint_descricao}'"
                    );
                    fwrite($file, implode(";", $values)."\n");
                }
                fclose($file);
                $os_tipo_atendimento = array_unique($os_tipo_atendimento);
                $os_status           = array_unique($os_status);
            }
        }
    }
}

if ( $_POST['gerar_excel'] == 'true' ) {
    if (pg_num_rows($res_dados) > 0){
        
        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_dashboard_km_mo_va-${$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");
        
        $headers = array(
            "'código do posto'",
            "'nome do posto'",
            "'valor km'",
            "'valor m.o'",
            "'valor adicional'",
            "'valor total'"
        );
        fwrite($file, implode(";", $headers)."\n");
            
        for ($i=0; $i < pg_num_rows($res_dados); $i++) { 
            $mao_de_obra = number_format(pg_fetch_result($res_dados, $i, 'mao_de_obra'),2,",",".");
            $qtde_km = number_format(pg_fetch_result($res_dados, $i, 'qtde_km'),2,",",".");
            $valores_adicionais = number_format(pg_fetch_result($res_dados, $i, 'valores_adicionais'),2,",",".");
            $total_geral = number_format(pg_fetch_result($res_dados, $i, 'total_geral'),2,",",".");
            $nome = pg_fetch_result($res_dados, $i, 'nome');
            $codigo_posto = pg_fetch_result($res_dados, $i, 'codigo_posto');
            $nome = utf8_encode($nome);
            $values = array(
                "'{$codigo_posto}'",
                "'{$nome}'",
                "'{$qtde_km}'",
                "'{$mao_de_obra}'",
                "'{$valores_adicionais}'",
                "'{$total_geral}'"
            );
            fwrite($file, implode(";", $values)."\n");
        }
        
        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
            echo "xls/{$fileName}";
        }
    }
    exit;
}

if ( $_POST['gerar_troca_excel'] == 'true' ) {
    if (pg_num_rows($res_ressarcimento_troca) > 0){
        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_dashboard_ressarcimento_troca-${$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");
        
        $headers = array(
            "'código do posto'",
            "'nome do posto'",
            "'qtde os ressarcimento'",
            "'qtde os troca'",
            "'qtde total'",
            "'dados os ressarcimento (os-consumidor)'",
            "'dados os troca (os-consumidor)'"
        );
        
        fwrite($file, implode(";", $headers)."\n");

        foreach ($dados_ressarcimento_troca as $key => $value) {
            $codigo_posto               = $value['codigo_posto'];
            $posto_nome                 = utf8_encode($value['posto_nome']);
            $qtde_os_ressarcimento        = $value['qtde_os_ressarcimento'];
            $qtde_os_troca              = $value['qtde_os_troca'];
            $total_troca_ressarcimento    = $value['total_troca_ressarcimento'];

            $dados_os_ressarcimento   = $value["dados_os_ressarcimento"];
            $dados_os_ressarcimento   = explode(",", $dados_os_ressarcimento);
            
            $dados_os_troca = $value["dados_os_troca"];
            $dados_os_troca = explode(',', $dados_os_troca);
            
            $count_dados_os_ressarcimento = count($dados_os_ressarcimento);
            $count_dados_os_troca = count($dados_os_troca);
           
            if ($count_dados_os_ressarcimento <= $count_dados_os_troca){
                $count = $count_dados_os_troca;
            }else if ($count_dados_os_troca <= $count_dados_os_ressarcimento){
                $count = $count_dados_os_ressarcimento;
            }
                
            for ($i=0; $i < $count; $i++) { 
                $values = array(
                    "'{$codigo_posto}'",
                    "'{$posto_nome}'",
                    "'{$qtde_os_ressarcimento}'",
                    "'{$qtde_os_troca}'",
                    "'{$total_troca_ressarcimento}'",
                    "'".utf8_encode($dados_os_ressarcimento[$i])."'",
                    "'".utf8_encode($dados_os_troca[$i])."'"
                );
                fwrite($file, implode(";", $values)."\n");
            }
        }
            
        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
            echo "xls/{$fileName}";
        }
    }
    exit;
}

if ( $_POST['gerar_reincidente_excel'] == 'true' ) {
    
    if (pg_num_rows($res_reincidente) > 0){
        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_dashboard_reincidente-${$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");

        $headers = array(
            "'código do posto'",
            "'nome do posto'",
            "'qtde os reincidente'",
            "'dados os reincidente (os-consumidor)'"
        );
        
        fwrite($file, implode(";", $headers)."\n");

        foreach ($dados_reincidente as $key => $value) {
            $codigo_posto               = $value['codigo_posto'];
            $posto_nome                 = $value['posto_nome'];
            $qtde_os_reincidente        = $value['qtde_os_reincidente'];

            $dados_os_reincidente   = $value["dados_os_reincidente"];
            $dados_os_reincidente   = explode(",", $dados_os_reincidente);
            foreach ($dados_os_reincidente as $key_os => $value_os) {
                $values = array(
                    "'{$codigo_posto}'",
                    "'{$posto_nome}'",
                    "'{$qtde_os_reincidente}'",
                    "'".$value_os."'"
                );
                fwrite($file, implode(";", $values)."\n");
            }
        }
        
        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
            echo "xls/{$fileName}";
        }
    }
    exit;
}

if ( $_POST['gerar_reagendamento_excel'] == 'true' ) {
    
    if (pg_num_rows($res_reagendamento) > 0){
        
        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_dashboard_reagendamento-${$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");

        $headers = array(
            "'código do posto'",
            "'nome do posto'",
            "'qtde os reagendamento'",
            "'dados os reagendamento (os-consumidor)'"
        );
        fwrite($file, implode(";", $headers)."\n");
        foreach ($dados_reagendamento as $key => $value) {
            $codigo_posto             = $value['codigo_posto'];
            $posto_nome               = $value['posto_nome'];
            $qtde_reagendamento       = $value['qtde_reagendamento'];
            
            $dados_os_reagendamento   = $value["dados_os_reagendamento"];
            $dados_os_reagendamento   = explode(",", $dados_os_reagendamento);
            
            foreach ($dados_os_reagendamento as $key_os => $value_os) {
                $values = array(
                    "'{$codigo_posto}'",
                    "'{$posto_nome}'",
                    "'{$qtde_reagendamento}'",
                    "'".$value_os."'"
                );
                fwrite($file, implode(";", $values)."\n");
            }
        }
       
        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
            echo "xls/{$fileName}";
        }
    }
    exit;
}

if ( $_POST['gerar_spv_excel'] == 'true' ) {
    if (pg_num_rows($res_spv) > 0){
        
        $data = date("d-m-Y-H:i");
        $fileName = "relatorio_dashboard_spv-${$data}.csv";
        $file = fopen("/tmp/{$fileName}", "w");

        $headers = array(
            "'código do posto'",
            "'nome do posto'",
            "'qtde os agendamento finalizada'",
            "'qtde os balcao finalizada'",
            "'qtde total'",
            "'dados os agendamento finalizada (os-consumidor)'",
            "'dados os balcão finalizada (os-consumidor)'"
        );
        fwrite($file, implode(";", $headers)."\n");
        foreach ($dados_spv as $key => $value) {
            $codigo_posto               = $value['codigo_posto'];
            $posto_nome                 = $value['posto_nome'];

            $qtde_os_sem_deslocamento_finalizada    = $value['qtde_os_sem_deslocamento_finalizada'];
            $qtde_os_deslocamento_finalizada        = $value['qtde_os_deslocamento_finalizada'];
            $total_spv                              = $value['total_spv'];
            
            $dados_os_sem_deslocamento_finalizada   = $value["dados_os_sem_deslocamento_finalizada"];
            $dados_os_sem_deslocamento_finalizada   = explode(",", $dados_os_sem_deslocamento_finalizada);
            
            $dados_os_deslocamento_finalizada = $value["dados_os_deslocamento_finalizada"];
            $dados_os_deslocamento_finalizada = explode(',', $dados_os_deslocamento_finalizada);
            
            $count_dados_os_sem_deslocamento_finalizada = count($dados_os_sem_deslocamento_finalizada);
            $count_dados_os_deslocamento_finalizada = count($dados_os_deslocamento_finalizada);
            
            if ($count_dados_os_deslocamento_finalizada <= $count_dados_os_sem_deslocamento_finalizada){
                $count = $count_dados_os_sem_deslocamento_finalizada;
            }else if ($count_dados_os_sem_deslocamento_finalizada <= $count_dados_os_deslocamento_finalizada){
                $count = $count_dados_os_deslocamento_finalizada;
            }
            
            for ($i=0; $i < $count; $i++) { 
                $values = array(
                    "'{$codigo_posto}'",
                    "'{$posto_nome}'",
                    "'{$qtde_os_deslocamento_finalizada}'",
                    "'{$qtde_os_sem_deslocamento_finalizada}'",
                    "'{$total_spv}'",
                    "'".utf8_encode($dados_os_deslocamento_finalizada[$i])."'",
                    "'".utf8_encode($dados_os_sem_deslocamento_finalizada[$i])."'"
                );
                fwrite($file, implode(";", $values)."\n");
            }
        }
        
        fclose($file);
        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");
            echo "xls/{$fileName}";
        }
    }
    exit;
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE OS x Atendimentos";
include 'cabecalho_new.php';


$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "select2"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        $.autocompleteLoad(Array("produto", "peca", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $("select").select2();

        $.dataTableLoad({ 
            table: "#result_dados", 
            aoColumns:[null,null,{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"}], 
            aaSorting: [[5, "desc" ]] 
        });

        $.dataTableLoad({ 
            table: "#dados_ressarcimento_troca",
            aoColumns:[null,null,{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"}], 
            aaSorting: [[4, "desc" ]] 
        });
      
        $.dataTableLoad({ 
            table: "#dados_reincidente",
            aoColumns:[null,null,{"sType":"numeric"}], 
            aaSorting: [[2, "desc" ]] 
        });

        $.dataTableLoad({ 
            table: "#dados_reagendamento",
            aoColumns:[null,null,{"sType":"numeric"}], 
            aaSorting: [[2, "desc" ]] 
        });

        $.dataTableLoad({ 
            table: "#dados_spv",
            aoColumns:[null,null,{"sType":"numeric"},{"sType":"numeric"},{"sType":"numeric"}], 
            aaSorting: [[4, "desc" ]] 
        });

        $("#btn-close-modal-reprova-os").click(function() {
            var modal_reprova_os = $("#modal-reprova-os");
            var btn_fechar = $("#btn-close-modal-reprova-os");
            $(modal_reprova_os).find("#dadosx").html('');
            $(modal_reprova_os).modal("hide");
        });

        $("#gerar_troca_excel").click(function () {
            if (ajaxAction()) {
                var json = $.parseJSON($("#jsonPOSTtroca").val());
                json["gerar_troca_excel"] = true;

                $.ajax({
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    data: json,
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        window.open(data.responseText, "_blank");

                        loading("hide");
                    }
                });
            }
        });

        $("#gerar_reincidente_excel").click(function () {
            if (ajaxAction()) {
                var json = $.parseJSON($("#jsonPOSTreincidente").val());
                json["gerar_reincidente_excel"] = true;

                $.ajax({
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    data: json,
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        window.open(data.responseText, "_blank");

                        loading("hide");
                    }
                });
            }
        });

        $("#gerar_reagendamento_excel").click(function () {
            if (ajaxAction()) {
                var json = $.parseJSON($("#jsonPOSTreagendamento").val());
                json["gerar_reagendamento_excel"] = true;

                $.ajax({
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    data: json,
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        window.open(data.responseText, "_blank");

                        loading("hide");
                    }
                });
            }
        });

        $("#gerar_spv_excel").click(function () {
            if (ajaxAction()) {
                var json = $.parseJSON($("#jsonPOSTspv").val());
                json["gerar_spv_excel"] = true;

                $.ajax({
                    url: "<?=$_SERVER['PHP_SELF']?>",
                    type: "POST",
                    data: json,
                    beforeSend: function () {
                        loading("show");
                    },
                    complete: function (data) {
                        window.open(data.responseText, "_blank");

                        loading("hide");
                    }
                });
            }
        });

        $("#gerar_agvi_excel").click(function () {
            var a = $(this).data("arquivo");
            window.open(a);
        });
    });

    var modal_reprova_os;
    var dados_os_ressarcimento_troca;
    var dados_os_troca;
    var dados_os_reincidente;
    var dados_split_ressarcimento_troca;
    var dados_split_troca;
    var dados_split_reincidente;
    var dados_os_reagendamento;
    var dados_split_reagendamento;
    var dados_os_sem_deslocamento_finalizada;
    var dados_os_deslocamento_finalizada;
    var dados_split_os_sem_deslocamento_finalizada;
    var dados_split_os_deslocamento_finalizada;

    $(document).on("click", ".ver_os", function() {
        modal_reprova_os = $("#modal-reprova-os");
        dados_os_ressarcimento_troca = $(this).data("os_ressarcimento_troca");
        dados_os_troca = $(this).data("os_troca");
        dados_os_reincidente = $(this).data("os_reincidente");
        dados_os_reagendamento = $(this).data("os_reagendamento");
        dados_os_sem_deslocamento_finalizada = $(this).data("dados_os_sem_deslocamento_finalizada");
        dados_os_deslocamento_finalizada = $(this).data("dados_os_deslocamento_finalizada");

        if (dados_os_ressarcimento_troca != undefined){
            if(dados_os_ressarcimento_troca.length > 0){
                dados_os_ressarcimento_troca = dados_os_ressarcimento_troca.split(',');
                $(dados_os_ressarcimento_troca).each(function(a,b){
                    dados_split_ressarcimento_troca = b.split("-");
                    
                    $(modal_reprova_os).find("#dadosx").append("\
                        <tr>\
                            <td class='tac'><a target='_blank' href='os_press.php?os="+$.trim(dados_split_ressarcimento_troca[0])+"'>"+dados_split_ressarcimento_troca[0]+"</a></td>\
                            <td>"+dados_split_ressarcimento_troca[1]+"</td>\
                        </tr>\
                    ");
                });
                $(modal_reprova_os).modal("show");
            }
        }

        if (dados_os_troca != undefined){
            if (dados_os_troca.length > 0){
                dados_os_troca = dados_os_troca.split(',');
                
                $(dados_os_troca).each(function(a,b){
                    dados_split_troca = b.split("-");
                    
                    $(modal_reprova_os).find("#dadosx").append("\
                        <tr>\
                            <td class='tac'><a target='_blank' href='os_press.php?os="+$.trim(dados_split_troca[0])+"'>"+dados_split_troca[0]+"</td>\
                            <td>"+dados_split_troca[1]+"</td>\
                        </tr>\
                    ");
                });
                $(modal_reprova_os).modal("show");
            }
        }

        if (dados_os_reincidente != undefined){
            if (dados_os_reincidente.length > 0){
                dados_os_reincidente = dados_os_reincidente.split(',');
                
                $(dados_os_reincidente).each(function(a,b){
                    dados_split_reincidente = b.split("-");
                    
                    $(modal_reprova_os).find("#dadosx").append("\
                        <tr>\
                            <td class='tac'><a target='_blank' href='os_press.php?os="+$.trim(dados_split_reincidente[0])+"'>"+dados_split_reincidente[0]+"</td>\
                            <td>"+dados_split_reincidente[1]+"</td>\
                        </tr>\
                    ");
                });
                $(modal_reprova_os).modal("show");
            }
        }

        if (dados_os_reagendamento != undefined){
            if (dados_os_reagendamento.length > 0){
                dados_os_reagendamento = dados_os_reagendamento.split(',');
                
                $(dados_os_reagendamento).each(function(a,b){
                    dados_split_reagendamento = b.split("-");
                    
                    $(modal_reprova_os).find("#dadosx").append("\
                        <tr>\
                            <td class='tac'><a target='_blank' href='os_press.php?os="+$.trim(dados_split_reagendamento[0])+"'>"+dados_split_reagendamento[0]+"</td>\
                            <td>"+dados_split_reagendamento[1]+"</td>\
                        </tr>\
                    ");
                });
                $(modal_reprova_os).modal("show");
            }
        }

        if (dados_os_sem_deslocamento_finalizada != undefined){
            if (dados_os_sem_deslocamento_finalizada.length > 0){
                dados_os_sem_deslocamento_finalizada = dados_os_sem_deslocamento_finalizada.split(',');
                
                $(dados_os_sem_deslocamento_finalizada).each(function(a,b){
                    dados_split_os_sem_deslocamento_finalizada = b.split("-");
                    
                    $(modal_reprova_os).find("#dadosx").append("\
                        <tr>\
                            <td class='tac'><a target='_blank' href='os_press.php?os="+$.trim(dados_split_os_sem_deslocamento_finalizada[0])+"'>"+dados_split_os_sem_deslocamento_finalizada[0]+"</td>\
                            <td>"+dados_split_os_sem_deslocamento_finalizada[1]+"</td>\
                        </tr>\
                    ");
                });
                $(modal_reprova_os).modal("show");
            }
        }

        if (dados_os_deslocamento_finalizada != undefined){
            if (dados_os_deslocamento_finalizada.length > 0){
                dados_os_deslocamento_finalizada = dados_os_deslocamento_finalizada.split(',');
                
                $(dados_os_deslocamento_finalizada).each(function(a,b){
                    dados_split_os_deslocamento_finalizada = b.split("-");
                    
                    $(modal_reprova_os).find("#dadosx").append("\
                        <tr>\
                            <td class='tac'><a target='_blank' href='os_press.php?os="+$.trim(dados_split_os_deslocamento_finalizada[0])+"'>"+dados_split_os_deslocamento_finalizada[0]+"</td>\
                            <td>"+dados_split_os_deslocamento_finalizada[1]+"</td>\
                        </tr>\
                    ");
                });
                $(modal_reprova_os).modal("show");
            }
        }
    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
    }

    function visualizar_dados(id_botao, id_tbody){
        if( $("#"+id_tbody).is(':visible') ) {
            $("#"+id_tbody).hide();
            $("#"+id_botao).text('Visualizar');
        }else{
            $("#"+id_tbody).show();
            $("#"+id_botao).text('Esconder');
        }
    }
</script>

<style type="text/css">
    #modal-reprova-os {
       width: 80%;
       margin-left: -40%;
    }
    .ver_os{
        cursor: pointer; color: #0d0dff;
    }
    #modal-os-filter {
        width: 80%;
        margin-left:-40%;
    }
</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                            <h5 class='asteristico'>*</h5>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <h5 class='asteristico'>*</h5>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $posto_codigo ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" >Tipo de Posto</label>
                <div class="controls controls-row" >
                    <div class="span10" >
                        <select class="span10" id="tipo_posto" name="tipo_posto" >
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
                                $selected = ($row->tipo_posto == $_POST["tipo_posto"]) ? "selected" : "";
                                echo "<option value='{$row->tipo_posto}' {$selected} >{$row->descricao}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <?  
            if(in_array($login_fabrica, [169,170])) {
        ?>
                <div class="span4">
                <div class="control-group" id="marca_bd">
                    <label class='control-label'>Pedido de Peça</label>
                    <select name='pedido_peca[]' id='pedido_peca' class='span12 tipo_posto_bd bd_sel' multiple="multiple">
                        <option <?php echo (in_array('sem_pedido',$pedido_peca)) ? "selected" : ""; ?> value="sem_pedido">Os sem pedido de peça</option>
                        <option <?php echo (in_array('com_pedido',$pedido_peca)) ? "selected" : ""; ?> value="com_pedido">Os com pedido de peça</option>
                    </select>
                </div>
            </div>
        <?
            }
        ?>
    </div>
 
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">&nbsp;</label>
                <div class="controls controls-row">
                    <label class="checkbox" >
                        <input type='checkbox' name='relatorio_km_mo_va' id='relatorio_km_mo_va' value='true' <?if($relatorio_km_mo_va == 'true') echo "CHECKED";?> /> Relatório Valores de Km, M.O, V.A
                    </label>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">&nbsp;</label>
                <div class="controls controls-row">
                    <label class="checkbox" >
                        <input type='checkbox' name='relatorio_troca_ressarcimento' id='relatorio_troca_ressarcimento' value='true' <?if($relatorio_troca_ressarcimento == 'true') echo "CHECKED";?> /> Relatório Troca/Ressarcimento
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">&nbsp;</label>
                <div class="controls controls-row">
                    <label class="checkbox" >
                        <input type='checkbox' name='relatorio_reincidencia' id='relatorio_reincidencia' value='true' <?if($relatorio_reincidencia == 'true') echo "CHECKED";?> /> Relatório Reincidência
                    </label>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">&nbsp;</label>
                <div class="controls controls-row">
                    <label class="checkbox" >
                        <input type='checkbox' name='relatorio_reagendamento' id='relatorio_reagendamento' value='true' <?if($relatorio_reagendamento == 'true') echo "CHECKED";?> /> Relatório Reagendamento
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">&nbsp;</label>
                <div class="controls controls-row">
                    <label class="checkbox" >
                        <input type='checkbox' name='relatorio_spv' id='relatorio_spv' value='true' <?if($relatorio_spv == 'true') echo "CHECKED";?> /> Relatório SPV(Solução Primeira Visita)
                    </label>
                </div>
            </div>
        </div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="">&nbsp;</label>
                <div class="controls controls-row">
                    <label class="checkbox" >
                        <input type='checkbox' name='relatorio_agvi' id='relatorio_agvi' value='true' <?if($relatorio_agvi == 'true') echo "CHECKED";?> /> Relatório AGVI
                    </label>
                </div>
            </div>
        </div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
    </p><br/>
</form>
</div>

<div id="modal-reprova-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
    <div class="modal-header">
        <h3>Informações</h3>
    </div>
    <div class="modal-body">
        <table class="table table-striped table-bordered table-fixed" id="table_x">
            <thead>
                <tr class='titulo_coluna'>
                    <th>OS</th>
                    <th>Consumidor</th> 
                </tr>
            </thead>
            <tbody id="dadosx">
                
            </tbody>
        </table>
    </div>
    <div class="modal-footer">
        <button type="button" id="btn-close-modal-reprova-os" class="btn">Fechar</button>
    </div>
</div>

<div class="modal fade" id="modal-os-filter">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-striped table-modal-os">
                    <thead>
                        <tr class="titulo_coluna">
                            <th>OS</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Posto autorizado</th>
                            <th>Tipo de atendimento</th>
                            <th>Primeira data de agendamento</th>
                            <th>Data de confirmação do posto autorizado</th>
                            <th>Última data de agendamento</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
if (pg_num_rows($res_dados) > 0){
?>
    <div class="container-fluid">
        <table id="result_dados" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="5">Dados Financeiro</th>
                    <th>
                        <button type='button' class='btn btn-small' id="button_dados" onclick="visualizar_dados('button_dados','tbdoy_result_dados');" >Visualizar</button>
                    </th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Codigo Posto</th>
                    <th>Nome Posto</th>
                    <th>Valor KM</th>
                    <th>Valor M.O</th>
                    <th>Valor Adicional</th>
                    <th>Valor Total</th>
                </tr>
            </thead>
            <tbody id="tbdoy_result_dados" style="display:none;">
            <?php 
                for ($i=0; $i < pg_num_rows($res_dados); $i++) { 
                    $codigo_posto       = pg_fetch_result($res_dados, $i, 'codigo_posto');
                    $nome               = pg_fetch_result($res_dados, $i, 'nome');
                    $qtde_km            = pg_fetch_result($res_dados, $i, 'qtde_km');
                    $mao_de_obra        = pg_fetch_result($res_dados, $i, 'mao_de_obra');
                    $valores_adicionais = pg_fetch_result($res_dados, $i, 'valores_adicionais');
                    $total_geral        = pg_fetch_result($res_dados, $i, 'total_geral');
            ?>
                <tr>
                    <td class='tal'><?=$codigo_posto?></td>
                    <td class='tal'><?=$nome?></td>
                    <td class="tal">R$ <?=number_format($qtde_km,2,",",".");?></td>
                    <td class="tal">R$ <?=number_format($mao_de_obra,2,",",".");?></td>
                    <td class="tal">R$ <?=number_format($valores_adicionais,2,",",".");?></td>
                    <td class="tal">R$ <?=number_format($total_geral,2,",",".");?></td>
                </tr>    
            <?php
                }
            ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="tac">
                        <input type="hidden" id="jsonPOST" value='<?php echo $jsonPOST ?>' />
                        <div id='gerar_excel' class="btn_excel">
                            <span><img src='imagens/excel.png' /></span>
                            <span class="txt">Gerar Arquivo CSV</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
}

if (pg_num_rows($res_ressarcimento_troca) > 0){
?>
    <br/>
    <div class="container-fluid">
        <table id="dados_ressarcimento_troca" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="4">Dados Ressarcimento/Troca</th>
                    <th>
                        <button type='button' class='btn btn-small' id="button_dados_ressarcimento_troca" onclick="visualizar_dados('button_dados_ressarcimento_troca','tbdoy_dados_ressarcimento_troca');" >Visualizar</button>
                    </th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Codigo Posto</th>
                    <th>Nome Posto</th>
                    <th>Qtde OS Ressarcimento</th>
                    <th>Qtde OS Troca</th>
                    <th>Qtde Total</th>
                </tr>
            </thead>
            <tbody id="tbdoy_dados_ressarcimento_troca" style="display:none;">
        <?php 
            foreach ($dados_ressarcimento_troca as $key => $value) {
        ?>
                <tr>
                    <td class='tal'><?=$value["codigo_posto"]?></td>
                    <td class='tal'><?=$value["posto_nome"]?></td>
                    <td class="tac ver_os" data-os_ressarcimento_troca="<?=$value["dados_os_ressarcimento"]?>"><?=$value["qtde_os_ressarcimento"]?></td>
                    <td class="tac ver_os" data-os_troca="<?=$value["dados_os_troca"]?>"><?=$value["qtde_os_troca"]?></td>
                    <td class="tac"><?=$value["total_troca_ressarcimento"]?></td>
                </tr>    
        <?php
            }
        ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php
                        $jsonPOSTtroca = trocaPostToJson($_POST);
                    ?>
                    <td colspan="5" class="tac">
                        <div id='gerar_troca_excel' class="btn_excel">
                            <input type="hidden" id="jsonPOSTtroca" value='<?php echo $jsonPOSTtroca ?>' />
                            <span><img src='imagens/excel.png' /></span>
                            <span class="txt">Gerar Arquivo CSV</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
}

if (pg_num_rows($res_reincidente) > 0){
?>  
    <br/>
    <div class="container-fluid">
        <table id="dados_reincidente" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="2">Dados Reincidente</th>
                    <th>
                        <button type='button' class='btn btn-small' id="button_dados_reincidente" onclick="visualizar_dados('button_dados_reincidente','tbdoy_dados_reincidente');" >Visualizar</button>
                    </th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Codigo Posto</th>
                    <th>Nome Posto</th>
                    <th>Qtde OS Reincidente</th>
                </tr>
            </thead>
            <tbody id="tbdoy_dados_reincidente" style="display:none;">
        <?php 
            foreach ($dados_reincidente as $key => $value) {
        ?>
                <tr>
                    <td class='tal'><?=$value["codigo_posto"]?></td>
                    <td class='tal'><?=$value["posto_nome"]?></td>
                    <td class="tac ver_os" data-os_reincidente="<?=$value["dados_os_reincidente"]?>"><?=$value["qtde_os_reincidente"]?></td>
                </tr>    
        <?php
            }
        ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php
                        $jsonPOSTreincidente = reincidentePostToJson($_POST);
                    ?>
                    <td colspan="5" class="tac">
                        <div id='gerar_reincidente_excel' class="btn_excel">
                            <input type="hidden" id="jsonPOSTreincidente" value='<?php echo $jsonPOSTreincidente ?>' />
                            <span><img src='imagens/excel.png' /></span>
                            <span class="txt">Gerar Arquivo CSV</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
}

if (pg_num_rows($res_reagendamento) > 0){
?>  
    <br/>
    <div class="container-fluid">
        <table id="dados_reagendamento" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="2">Dados Reagendamento</th>
                    <th>
                        <button type='button' class='btn btn-small' id="button_dados_reagendamento" onclick="visualizar_dados('button_dados_reagendamento','tbdoy_dados_reagendamento');" >Visualizar</button>
                    </th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Codigo Posto</th>
                    <th>Nome Posto</th>
                    <th>Qtde Reagendamento</th>
                </tr>
            </thead>
            <tbody id="tbdoy_dados_reagendamento" style="display:none;">
        <?php 
            foreach ($dados_reagendamento as $key => $value) {
        ?>
                <tr>
                    <td class='tal'><?=$value["codigo_posto"]?></td>
                    <td class='tal'><?=$value["posto_nome"]?></td>
                    <td class="tac ver_os" data-os_reagendamento="<?=$value["dados_os_reagendamento"]?>"><?=$value["qtde_reagendamento"]?></td>
                </tr>    
        <?php
            }
        ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php
                        $jsonPOSTreagendamento = reagendamentoPostToJson($_POST);
                    ?>
                    <td colspan="5" class="tac">
                        <div id='gerar_reagendamento_excel' class="btn_excel">
                            <input type="hidden" id="jsonPOSTreagendamento" value='<?php echo $jsonPOSTreagendamento ?>' />
                            <span><img src='imagens/excel.png' /></span>
                            <span class="txt">Gerar Arquivo CSV</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
}

if (pg_num_rows($res_spv) > 0){
?>
    <br/>
    <div class="container-fluid">
        <table id="dados_spv" class='table table-striped table-bordered table-hover table-fixed' >
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="4">Dados SPV(Solução Primeira Visita)</th>
                    <th>
                        <button type='button' class='btn btn-small' id="button_dados_spv" onclick="visualizar_dados('button_dados_spv','tbdoy_dados_spv');" >Visualizar</button>
                    </th>
                </tr>
                <tr class='titulo_coluna' >
                    <th>Codigo Posto</th>
                    <th>Nome Posto</th>
                    <th>Qtde OS Agendamento Finalizada</th>
                    <th>Qtde OS Balção Finalizada</th>
                    <th>Qtde Total</th>
                </tr>
            </thead>
            <tbody id="tbdoy_dados_spv" style="display:none;">
        <?php 
            foreach ($dados_spv as $key => $value) {
        ?>
                <tr>
                    <td class='tal'><?=$value["codigo_posto"]?></td>
                    <td class='tal'><?=$value["posto_nome"]?></td>
                    <td class="tac ver_os" data-dados_os_deslocamento_finalizada="<?=$value["dados_os_deslocamento_finalizada"]?>"><?=$value["qtde_os_deslocamento_finalizada"]?></td>
                    <td class="tac ver_os" data-dados_os_sem_deslocamento_finalizada="<?=$value["dados_os_sem_deslocamento_finalizada"]?>"><?=$value["qtde_os_sem_deslocamento_finalizada"]?></td>
                    <td class="tac"><?=$value["total_spv"]?></td>
                </tr>    
        <?php
            }
        ?>
            </tbody>
            <tfoot>
                <tr>
                    <?php
                        $jsonPOSTspv = spvPostToJson($_POST);
                    ?>
                    <td colspan="5" class="tac">
                        <div id='gerar_spv_excel' class="btn_excel">
                            <input type="hidden" id="jsonPOSTspv" value='<?php echo $jsonPOSTspv ?>' />
                            <span><img src='imagens/excel.png' /></span>
                            <span class="txt">Gerar Arquivo CSV</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
}

if (pg_num_rows($resQtdeOs) > 0){
?>
    <br/>
    <div class="container-fluid">
    <table class="table table-bordered" id="dados_agvi" >
        <thead>
            <tr class="titulo_coluna">
                <th colspan="<?=(count($os_tipo_atendimento) * 3) + 3?>" >Ordens de Serviço - Inspetor x Tipo de Atendimento x Status</th>
                <th>
                    <button type='button' class='btn btn-small' id="button_dados_agvi" onclick="visualizar_dados('button_dados_agvi','tbdoy_dados_agvi');" >Visualizar</button>
                </th>
            </tr>
            <tr class="titulo_coluna" >
                <th rowspan="2" >Inspetor</th>
                <?php
                foreach ($os_tipo_atendimento as $ota) {
                    echo "<th colspan='3' >{$ota}</th>";
                }
                ?>
                <th colspan="3" >Total</th>
            </tr>
            <tr class="titulo_coluna" >
                <?php
                foreach ($os_tipo_atendimento as $ota) {
                ?>
                    <th>Status</th>
                    <th>Qtde.</th>
                    <th>Em Atraso</th>
                <?php
                }
                ?>
                <!--Total-->
                <th>Status</th>
                <th>Qtde.</th>
                <th>Em Atraso</th>
            </tr>
        </thead>
        <tbody id="tbdoy_dados_agvi" style="display:none;">
            <?php
            if (!empty($os_tipo_atendimento_status_inspetor)) {
                $total_geral_tipo_atendimento = array();
                $total_geral_tipo_atendimento_atrasado = array();

                foreach ($os_tipo_atendimento_status_inspetor as $i => $status) {
            ?>
                    <tr class="info" >
                        <td rowspan="<?=(count($status) + 2)?>" ><?=$i?></td>
                    </tr>
                    <?php
                        $total_tipo_atendimento = array();
                        $total_tipo_atendimento_atrasado = array();

                        foreach ($status as $s => $tipo_atendimento) {
                            $total_status = 0;
                            $total_status_atrasado = 0;
                    ?>
                    <tr style='background-color: #FFF;'>
                    <?php
                            foreach ($os_tipo_atendimento as $ota) {
                                $qtde = count($tipo_atendimento[$ota]);
                                $qtdeAtrasado = count($os_em_atraso[$i][$s][$ota]);
                                echo "
                                    <td class='vam' >{$s}</td>
                                    <td class='tac vam' >".((empty($qtde)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' data-status='{$s}' data-tipo-atendimento='{$ota}' >{$qtde}</button>")."</td>
                                    <td class='tac vam' >".((empty($qtdeAtrasado)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' data-status='{$s}' data-tipo-atendimento='{$ota}' data-atrasado='true' >{$qtdeAtrasado}</button>")."</td>
                                ";
                                $total_status += $qtde;
                                $total_status_atrasado += $qtdeAtrasado;
                                $total_tipo_atendimento[$ota] += $qtde;
                                $total_tipo_atendimento_atrasado[$ota] += $qtdeAtrasado;
                            }
                    ?>
                            <!--Total Status-->
                        <td class="vam" ><?=$s?></td>
                        <td class="tac vam" ><?=((empty($total_status)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' data-status='{$s}' >{$total_status}</button>")?></td>
                        <td class="tac vam" ><?=((empty($total_status_atrasado)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' data-status='{$s}' data-atrasado='true' >{$total_status_atrasado}</button>")?></td>
                    </tr>
                    <?php
                }
            ?>
                    <tr class="info" >
                    <?php
                        $total_geral = 0;
                        $total_geral_atrasado = 0;

                        foreach ($os_tipo_atendimento as $ota) {
                            $qtde = $total_tipo_atendimento[$ota];
                            $qtdeAtrasado = $total_tipo_atendimento_atrasado[$ota];
                            echo "
                                <td class='vam' >Total</td>
                                <td class='tac vam' >".((empty($qtde)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' data-tipo-atendimento='{$ota}' >{$qtde}</button>")."</td>
                                <td class='tac vam' >".((empty($qtdeAtrasado)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' data-tipo-atendimento='{$ota}' data-atrasado='true' >{$qtdeAtrasado}</button>")."</td>
                            ";
                            $total_geral += $qtde;
                            $total_geral_atrasado += $qtdeAtrasado;
                            $total_geral_tipo_atendimento[$ota] += $qtde;
                            $total_geral_tipo_atendimento_atrasado[$ota] += $qtdeAtrasado;
                        }
                    ?>
                        <!--Total Tipo de Atendimento-->
                        <td class="vam" >Total</td>
                        <td class="tac vam" ><?=((empty($total_geral)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' >{$total_geral}</button>")?></td>
                        <td class="tac vam" ><?=((empty($total_geral_atrasado)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='{$i}' data-atrasado='true' >{$total_geral_atrasado}</button>")?></td>
                    </tr>
                    <?php
                }
            }
        ?>
        </tbody>
        <tfoot>
            <tr class="titulo_coluna" >
                <th>&nbsp;</th>
                <?php
                $total_geral = 0;
                $total_geral_atrasado = 0;

                foreach ($os_tipo_atendimento as $ota) {
                    $qtde = $total_geral_tipo_atendimento[$ota];
                    $qtdeAtrasado = $total_geral_tipo_atendimento_atrasado[$ota];
                    echo "
                        <td class='vam' >Total</td>
                        <td class='tac vam' >".((empty($qtde)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='todos' data-filtro='{$i}' data-tipo-atendimento='{$ota}' >{$qtde}</button>")."</td>
                        <td class='tac vam' >".((empty($qtdeAtrasado)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='todos' data-filtro='{$i}' data-tipo-atendimento='{$ota}' data-atrasado='true' >{$qtdeAtrasado}</button>")."</td>
                    ";
                    $total_geral += $qtde;
                    $total_geral_atrasado += $qtdeAtrasado;
                }
                ?>
                <!--Total Geral-->
                <td class="vam" >Total Geral</td>
                <td class="tac vam" ><?=((empty($total_geral)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='todos' >{$total_geral}</button>")?></td>
                <td class="tac vam" ><?=((empty($total_geral_atrasado)) ? 0 : "<button class='btn btn-link explodir-os' data-acao='todos' data-atrasado='true' >{$total_geral_atrasado}</button>")?></td>
            </tr>
            <tr>
                <td colspan="22" class="tac">
                    <div id='gerar_agvi_excel' class="btn_excel download-arquivo-csv" data-arquivo="<?=$arquivo_os_csv?>" >
                        <span><img src='imagens/excel.png' /></span>
                        <span class="txt">Gerar Arquivo CSV</span>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
    </div>
<?php                
}

if (
    pg_num_rows($res_dados) == 0 AND pg_num_rows($res_ressarcimento_troca) == 0 
    AND pg_num_rows($res_reincidente) == 0 AND pg_num_rows($res_reagendamento) == 0
    AND pg_num_rows($res_spv)  == 0 AND pg_num_rows($resQtdeOs) == 0
){
    echo '
        <div class="container">
            <div class="alert">
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>
    ';
}
?>

<script type="text/javascript">
    var filtrarOs = new function() {
        var modal_os = $("#modal-os-filter");
        var tbody = $(modal_os).find("tbody");
        var os_lista = <?=json_encode($os_lista)?>;

        this.show = function(acao, filtro, status, tipo_atendimento, atrasado) {
            clearTbody();

            var label = [];

            if (acao == "inspetor") {
                label.push("Posto: "+filtro);
            }
            if (typeof tipo_atendimento != "undefined") {
                label.push("Tipo de Atendimento: "+tipo_atendimento);
            }
            if (typeof status != "undefined") {
                label.push("Status: "+status);
            }
            if (typeof atrasado != "undefined") {
                label.push("Em Atraso");
            }

            $(modal_os).find(".modal-title").text(label.join(" - "));
            os_lista.forEach(function(os, i) {
                if (acao == "inspetor" && os.posto != filtro) {
                    return;
                } else if (acao == "posto" && os.posto != filtro) {
                    return;
                }else if (typeof status != "undefined" && status != os.status) {
                    return;
                } else if (typeof tipo_atendimento != "undefined" && tipo_atendimento != os.tipo_atendimento) {
                    return;
                }else if (typeof atrasado != "undefined" && atrasado == true && os.atrasada != true) {
                    return;
                }
                $(tbody).append("\
                    <tr>\
                        <td><a href='os_press.php?os="+os.os+"' target='_blank' >"+os.sua_os+"</a></td>\
                        <td>"+os.data_digitacao+"</td>\
                        <td>"+os.status+"</td>\
                        <td>"+os.posto+"</td>\
                        <td>"+os.tipo_atendimento+"</td>\
                        <td>"+os.primeira_data_agendamento+"</td>\
                        <td>"+os.data_confirmacao+"</td>\
                        <td>"+os.data_reagendamento+"</td>\
                    </tr>\
                ");
            });

            $.dataTableLoad({
                table: ".table-modal-os",
                type: "custom",
                config: ["info", "paginacao", "pesquisa"]
            });
            $(modal_os).modal("show");
        };

        var clearTbody = function() {
            if (dataTableGlobal) {
                dataTableGlobal.fnDestroy();
            }
            $(tbody).find("tr").remove();
        };
    };

    $(document).on("click", ".explodir-os", function() {
        var acao             = $(this).data("acao");
        var filtro           = $(this).data("filtro");
        var status           = $(this).data("status");
        var tipo_atendimento = $(this).data("tipo-atendimento");
        var atrasado         = $(this).data("atrasado");

        filtrarOs.show(acao, filtro, status, tipo_atendimento, atrasado);
    });
</script>
<?php
include 'rodape.php';
?>
