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

if ($_serverEnvironment == "development") {
    $cond6359 = "";
} else {
    $cond6359 = " AND tbl_os.posto != 6359 ";
}
/**
 * Busca os Status das OSs para agrupar o resultado
 */
$sqlStatus = "SELECT status_checkpoint, descricao FROM tbl_status_checkpoint";
$resStatus = pg_query($con, $sqlStatus);

$status_os_json = array();

while ($row = pg_fetch_object($resStatus)) {
    $status_os_json[utf8_encode($row->descricao)] = $row->status_checkpoint;
}

$status_os_json = json_encode($status_os_json);
/**
 * Filtros
 */
$pesquisa = true;

if (!$areaAdmin) {
    $whereOsPosto      = "AND tbl_os.posto = {$login_posto}";
    $wherePedidoPosto  = "AND tbl_pedido.posto = {$login_posto}";
    $whereExtratoPosto = "AND tbl_extrato.posto = {$login_posto} AND tbl_extrato.liberado IS NOT NULL";

    if (in_array($login_fabrica, array(169,170))){
        if (!empty($_POST["tipo_atendimento"])) {
            $tipo_atendimento = $_POST["tipo_atendimento"];
            $whereOsTipoAtendimento = "AND tbl_tipo_atendimento.tipo_atendimento IN (".implode(", ", $tipo_atendimento).")";
        }

        if (!empty($_POST["tipo_os"])){
            $tipo_os = $_POST["tipo_os"];
            $tipo_os = array_map(function($n){
                $n = "'".$n."'";
                return $n;
            }, $tipo_os);
            $whereOsTipoOs = "AND tbl_os.consumidor_revenda IN (".implode(", ", $tipo_os).")";
        }
    }
} else {
    if (!empty($_POST["posto_id"])) {
        $posto_id = $_POST["posto_id"];
        $whereOsPosto      = "AND tbl_os.posto = {$posto_id}";
        $wherePedidoPosto  = "AND tbl_pedido.posto = {$posto_id}";
        $whereExtratoPosto = "AND tbl_extrato.posto = {$posto_id}";
    }

    if (in_array($login_fabrica, array(158))) {
        if (!empty($_POST["tipo"])) {
            $tipo = $_POST["tipo"];

            switch ($tipo) {
                case "garantia":
                    $whereOsTipoAtendimento = "AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE";
                    $wherePedidoTipo        = "AND tbl_tipo_pedido.pedido_em_garantia IS TRUE";
                    $whereExtratoTipo       = "AND tbl_extrato.protocolo = 'Garantia'";
                    break;

                case "fora_garantia":
                    $whereOsTipoAtendimento = "AND tbl_tipo_atendimento.fora_garantia IS TRUE";
                    $wherePedidoTipo        = "AND tbl_tipo_pedido.pedido_em_garantia IS NOT TRUE";
                    $whereExtratoTipo       = "AND tbl_extrato.protocolo = 'Fora de Garantia'";
                    break;
            }
        }
    } else {
        if (!empty($_POST["tipo_atendimento"])) {
            $tipo_atendimento = $_POST["tipo_atendimento"];
            $whereOsTipoAtendimento = "AND tbl_tipo_atendimento.tipo_atendimento IN (".implode(", ", $tipo_atendimento).")";
        }

        if (!empty($_POST["tipo_posto"])) {
            $tipo_posto = $_POST["tipo_posto"];
            $whereTipoPosto = "AND tbl_tipo_posto.tipo_posto IN (".implode(", ", $tipo_posto).")";
        }

        if (!empty($_POST["linha"])) {
            $linha = $_POST["linha"];
	    if (is_array($linha)) {
		$linha = implode(",", $linha);
	    }
            $whereOsLinha = "AND tbl_produto.linha IN ({$linha})";
        }
    }

    if (!empty($_POST["familia"])) {
        $familia = $_POST["familia"];
	if (is_array($familia)) {
	    $familia = implode(",", $familia);
	}
        $whereOsFamilia = "AND tbl_produto.familia IN ({$familia})";
    }

    if (in_array($login_fabrica, array(169,170,183))) {
        if (!empty($_POST["inspetor"])) {
            $inspetor = $_POST["inspetor"];
            $whereInspetor = "AND tbl_posto_fabrica.admin_sap IN (".implode(", ", $inspetor).")";
        }

        if (!empty($_POST["estado"])) {
            $estado = $_POST["estado"];
            $estado = array_map(function($e) {
                return "'{$e}'";
            }, $estado);
            $whereEstado = "AND tbl_posto_fabrica.contato_estado IN (".implode(", ", $estado).")";
        }

        if (!empty($_POST["status_os"])) {
            $status_os = $_POST["status_os"];
            $whereStatusOs = "AND tbl_os.status_checkpoint IN (".implode(", ", $status_os).")";
        }
    }
}

if (in_array($login_fabrica, array(169, 170)) && !$_POST && $areaAdmin) {
	$pesquisa = false;
}

$data_inicial = $_POST["data_inicial"];
$data_final   = $_POST["data_final"];

if (in_array($login_fabrica, array(169,170))) {
    $interval = 6;
} else {
    $interval = 3;
}

if (!empty($data_inicial) && !empty($data_final)) {
    list($dia, $mes, $ano) = explode("/", $data_inicial);
    $data_inicial          = "{$ano}-{$mes}-{$dia} 00:00:00";

    list($dia, $mes, $ano) = explode("/", $data_final);
    $data_final            = "{$ano}-{$mes}-{$dia} 23:59:59";

    if (!strtotime($data_inicial) || !strtotime($data_final)) {
        $msg_erro["msg"][] = "Data inválida";
        $pesquisa = false;
    } else if (strtotime($data_inicial) > strtotime($data_final)) {
        $msg_erro["msg"][] = "Data inicial não pode ser superior a data final";
        $pesquisa = false;
    } else if (strtotime($data_final) > strtotime("{$data_inicial} +{$interval} months")) {
        $msg_erro["msg"][] = "O período máximo para pesquisa é de {$interval} meses";
        $pesquisa = false;
    } else {
        $pesquisa_periodo      = true;
        $data_inicial_pesquisa = date("d/m/Y", strtotime($data_inicial));
        $data_final_pesquisa   = date("d/m/Y", strtotime($data_final));
        $interval_label        = "período {$_POST['data_inicial']} - {$_POST['data_final']}";
    }
} else {
    $pesquisa_periodo = false;
    $interval_label   = "últimos {$interval} meses";
}

if ($pesquisa) {

    $current_date_inicial = date("Y-m-d 00:00:00");
    $current_date_final   = date("Y-m-d 23:59:59");

    if (!$pesquisa_periodo) {
        $sqlDataInicialPesquisa = "SELECT TO_CHAR(('{$current_date_inicial}'::timestamp - INTERVAL '{$interval} months'), 'DD/MM/YYYY') AS data_inicial_pesquisa";
        $resDataInicialPesquisa = pg_query($con, $sqlDataInicialPesquisa);
        $resDataInicialPesquisa = pg_fetch_assoc($resDataInicialPesquisa);
        $data_inicial_pesquisa = $resDataInicialPesquisa["data_inicial_pesquisa"];

        if ($login_fabrica == 175){
            $data_final_pesquisa   = date("d/m/Y");
        }
        if (in_array($login_fabrica,[169,170])) {
            $sqlDataFinalPesquisa = "SELECT TO_CHAR(('{$current_date_final}'::timestamp), 'DD/MM/YYYY') AS data_final_pesquisa";
            $resDataFinalPesquisa = pg_query($con, $sqlDataFinalPesquisa);
            $resDataFinalPesquisa = pg_fetch_assoc($resDataFinalPesquisa);
            $data_final_pesquisa = $resDataFinalPesquisa["data_final_pesquisa"];
        }
    }

    if (in_array($login_fabrica, array(169,170))) {
        $tempo_pesquisa = array(
            3 => array(
                "o" => "><",
                "d" => 0,
                "label" => "0-3 Dias"
            ),
            7 => array(
                "o" => "><",
                "d" => 4,
                "label" => "4-7 Dias"
            ),
            10 => array(
                "o" => "><",
                "d" => 8,
                "label" => "8-10 Dias"
            ),
            15 => array(
                "o" => "><",
                "d" => 11,
                "label" => "11-15 Dias"
            ),
            25 => array(
                "o" => "><",
                "d" => 16,
                "label" => "16-25 Dias"
            ),
            30 => array(
                "o" => "><",
                "d" => 26,
                "label" => "26-30 Dias"
            ),
            60 => array(
                "o" => "><",
                "d" => 31,
                "label" => "31-60 Dias"
            ),
            61 => array(
                "o" => "<",
                "label" => "> 60 Dias"
            )
        );
    } else {
        $tempo_pesquisa = array(
            3 => array(
                "o" => "><",
                "d" => 0,
                "label" => "0-3 Dias"
            ),
            7 => array(
                "o" => "><",
                "d" => 4,
                "label" => "4-7 Dias"
            ),
            15 => array(
                "o" => "><",
                "d" => 8,
                "label" => "8-15 Dias"
            ),
            25 => array(
                "o" => "><",
                "d" => 16,
                "label" => "16-25 Dias"
            ),
            26 => array(
                "o" => "<",
                "label" => "> 25 Dias"
            )
        );
    }

    $tempo_key = array();
    $categorias_json = array();
    $tempo_pesquisa_json = array();
    $selectDias = "CASE ";
    $i = -1;

    foreach ($tempo_pesquisa as $dia1 => $v) {
        $dia2 = $v["d"];

        if ($v["o"] == "><") {
            $selectDias .= "
                WHEN (tbl_os.data_digitacao BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '{$dia1} days' AND '{$current_date_final}'::timestamp - INTERVAL '{$dia2} days') THEN {$dia1}
            ";
        } else if ($v["o"] == "<") {
            $selectDias .= "
                WHEN tbl_os.data_digitacao < ('{$current_date_final}'::timestamp - INTERVAL '{$dia1} days') THEN {$dia1}
            ";
        }

        $tempo_key[$dia1] = ++$i;
        $categorias_json[] = $v["label"];
        $tempo_pesquisa_json[$v["label"]] = array("d1" => $dia1, "d2" => $dia2);
    }

    $selectDias .= "END AS tempo ";
    $categorias_json = json_encode($categorias_json);

    if (in_array($login_fabrica, array(169, 170)) and empty($status_os)) {
        $whereOsFinalizada = "AND tbl_os.finalizada IS NULL";
    }

    if (in_array($login_fabrica, array(169, 170))) {

        $pedido_peca = $_POST['pedido_peca'];

        if (count($pedido_peca) > 0) {

            if (count($pedido_peca) <= 1) {

                   if(in_array('sem_pedido', $pedido_peca)) {
                       
                    $cond_status .= " AND (SELECT tbl_os_item.pedido 
                                         FROM tbl_os AS o 
                                         JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                         JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                         WHERE tbl_os_item.pedido IS NOT NULL
                                         AND o.os = tbl_os.os
                                         LIMIT 1) IS NULL  "; //traz todas as OS que nao tem pedido
                                         
                   }
                
                   if(in_array('com_pedido', $pedido_peca)) {
                        
                    $cond_status .= "  AND (SELECT tbl_os_item.pedido 
                                         FROM tbl_os AS o 
                                         JOIN tbl_os_produto ON tbl_os_produto.os = o.os
                                         JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                         WHERE tbl_os_item.pedido IS NOT NULL
                                         AND o.os = tbl_os.os
                                         LIMIT 1) IS NOT NULL  "; //traz todas as OS que tem pedido
                   }
             } 
        }

        $camposFabrica = "
            , tbl_posto.nome AS posto,
            tbl_admin.nome_completo AS admin_nome,
            tbl_admin.login AS admin_login,
            CASE WHEN (tbl_os.data_digitacao BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '3 days' AND '{$current_date_final}'::timestamp - INTERVAL '0 days') THEN 3
            WHEN (tbl_os.data_digitacao BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '10 days' AND '{$current_date_final}'::timestamp - INTERVAL '4 days') THEN 10
            WHEN (tbl_os.data_digitacao BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '20 days' AND '{$current_date_final}'::timestamp - INTERVAL '11 days') THEN 20
            WHEN (tbl_os.data_digitacao BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '30 days' AND '{$current_date_final}'::timestamp - INTERVAL '21 days') THEN 30
            WHEN tbl_os.data_digitacao < ('{$current_date_final}'::timestamp - INTERVAL '31 days') THEN 31
            END AS tempo_inspetor,
            TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY') AS data_digitacao,
            tbl_tipo_atendimento.descricao AS tipo_atendimento,
            tbl_linha.nome AS linha,
            tbl_familia.descricao AS familia,
            tbl_produto.referencia AS produto_referencia,
            tbl_produto.descricao AS produto_descricao,
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
            tbl_os_produto.serie,
            tbl_os.data_abertura,
            tbl_os.consumidor_revenda,
            tbl_posto_fabrica.codigo_posto,
            tbl_posto.nome AS nome_posto,
            tbl_posto_fabrica.contato_cidade,
            tbl_posto_fabrica.contato_estado,
            tbl_os.consumidor_nome AS consumidor_revenda_nome,
            tbl_os.consumidor_bairro AS consumidor_revenda_bairro,
            tbl_os.consumidor_cidade AS consumidor_revenda_cidade,
            tbl_os.nota_fiscal,
            tbl_tipo_atendimento.km_google AS tipo_atendimento_deslocamento
        ";
        $joinFabrica = "
            LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
            LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
            LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda
            LEFT JOIN tbl_cidade revenda_cidade ON revenda_cidade.cidade = tbl_revenda.cidade
        ";
        $whereForaGarantia = "
            AND (
                tbl_tipo_atendimento.fora_garantia IS NOT TRUE 
                OR (
                    tbl_tipo_atendimento.fora_garantia IS TRUE
                    AND tbl_tipo_atendimento.grupo_atendimento IS NOT NULL
                )
            )
        ";
        $tempo_inspetor_posto = array(3, 10, 20, 30, 31, "total");
    }
    
    /**
     * Busca Feriados
     */
    if (in_array($login_fabrica, array(169,170))) {
        $sqlFeriados = "
            SELECT data
            FROM tbl_feriado
            WHERE fabrica = {$login_fabrica}
            AND ativo IS TRUE
            AND (data BETWEEN '".date("Y-m-d", strtotime($data_inicial))."' AND '".date("Y-m-d", strtotime($data_final))."')
        ";
        $resFeriados = pg_query($con, $sqlFeriados);
        $resFeriados = array_map(function($f) {
            return $f["data"];
        }, pg_fetch_all($resFeriados));
    }

    /**
     * Busca as OSs
     */
    if ($pesquisa_periodo) {
        $whereData = "AND (tbl_os.data_digitacao BETWEEN '{$data_inicial}' AND '{$data_final}')";
    } else {
        $whereData = "AND (tbl_os.data_digitacao BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '{$interval} months' AND '{$current_date_final}')";
    }

    $sqlQtdeOs = "
        SELECT
            tbl_os.os,
            tbl_os.sua_os,
            tbl_os.status_checkpoint,
            tbl_status_checkpoint.descricao AS status_checkpoint_descricao,
            {$selectDias}
            {$camposFabrica}
        FROM tbl_os
        INNER JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
        INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
        LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
        LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap AND tbl_admin.fabrica = {$login_fabrica}
        INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
        {$joinFabrica}
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os.excluida IS NOT TRUE
        {$cond6359}
        {$whereData}
        {$whereOsPosto}
        {$whereOsTipoAtendimento}
        {$whereOsFamilia}
        {$whereOsLinha}
        {$whereInspetor}
        {$whereOsFinalizada}
        {$whereTipoPosto}
        {$whereEstado}
        {$whereStatusOs}
        {$whereForaGarantia}
        {$whereOsTipoOs}
        {$cond_status}
        ORDER BY tempo ASC, tbl_os.status_checkpoint
    ";
    $resQtdeOs = pg_query($con, $sqlQtdeOs);

    $grafico_tempo_os_series = array();

    while ($row = pg_fetch_object($resQtdeOs)) {
        $grafico_tempo_os_series[$row->status_checkpoint]["name"] = utf8_encode($row->status_checkpoint_descricao);

        if (!isset($grafico_tempo_os_series[$row->status_checkpoint]["data"])) {
            $grafico_tempo_os_series[$row->status_checkpoint]["data"] = array_fill(0, count($tempo_key), 0);
        }

        $grafico_tempo_os_series[$row->status_checkpoint]["data"][$tempo_key[$row->tempo]] += 1;
    }

    $grafico_tempo_os_series_json = array();

    foreach ($grafico_tempo_os_series as $key => $value) {
        $grafico_tempo_os_series_json[] = $value;
    }

    $grafico_tempo_os_series = json_encode($grafico_tempo_os_series_json);

    if (in_array($login_fabrica, array(169,170,183))) {
        $tempo_inspetor = array();
        $tempo_posto    = array();
        $os_lista       = array();
        pg_result_seek($resQtdeOs, 0);

        $arquivo_os_csv = "xls/dashboard_relatorio_os_{$login_fabrica}{$login_admin}".date("c").".csv";
        $file = fopen($arquivo_os_csv, "w");

        $headers = array(
            "'ordem de serviço'",
            "'série'",
            "'data de abertura'",
            "'primeira data de agendamento'",
            "'data de confirmação do posto autorizado'",
            "'última data de agendamento'",
            "'inspetor'",
            "'tipo de ordem de serviço'",
            "'código do posto autorizado'",
            "'nome do posto autorizado'",
            "'cidade do posto autorizado'",
            "'estado do posto autorizado'",
            "'cliente'",
            "'cliente bairro'",
            "'cliente cidade'",
            "'nota fiscal'",
            "'produto'",
            "'status da ordem de serviço'"
        );
        fwrite($file, implode(";", $headers)."\n");

        $os_tipo_atendimento_status_inspetor = array();
        $os_em_atraso                        = array();
        $os_tipo_atendimento                 = array();
        $os_status                           = array();

        while ($row = pg_fetch_object($resQtdeOs)) {
            unset($primeira_data_agendamento);
            unset($data_confirmacao);
            unset($ultima_data);

            if (empty($row->admin_login)) {
                $key_inspetor = "Sem Inspetor";
            } else {
                $key_inspetor = (empty($row->admin_nome)) ? $row->admin_login : $row->admin_nome;
            }

            if (!isset($tempo_inspetor[$key_inspetor])) {
                $tempo_inspetor[$key_inspetor] = array(3 => 0, 10 => 0, 20 => 0, 30 => 0, 31 => 0);
            }

            if (!isset($tempo_posto[$row->posto])) {
                $tempo_posto[$row->posto] = array(3 => 0, 10 => 0, 20 => 0, 30 => 0, 31 => 0);
            }

            if (!empty($row->primeira_data_agendamento) && $row->primeira_data_agendamento == $row->ultima_data_agendamento){
                $ultima_data = "";
            } else {
                $ultima_data = $row->ultima_data_agendamento;
            }

            $tempo_inspetor[$key_inspetor][$row->tempo_inspetor] += 1;
            $tempo_posto[$row->posto][$row->tempo_inspetor] += 1;

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
                "tempo"                     => $row->tempo_inspetor,
                "inspetor"                  => utf8_encode($key_inspetor),
                "posto"                     => utf8_encode($row->posto),
                "tipo_atendimento"          => utf8_encode($row->tipo_atendimento),
                "linha"                     => utf8_encode($row->linha),
                "familia"                   => utf8_encode($row->familia),
                "produto_referencia"        => $row->produto_referencia,
                "produto_descricao"         => utf8_encode($row->produto_descricao),
                "primeira_data_agendamento" => $primeira_data_agendamento,
                "data_confirmacao"          => utf8_encode($data_confirmacao),
                "data_reagendamento"        => $ultima_data
            );

            if ($key_inspetor != "Sem Inspetor") {
                $os_tipo_atendimento[] = $row->tipo_atendimento;
                $os_status[]           = $row->status_checkpoint_descricao;

                if (!isset($os_tipo_atendimento_status_inspetor[$key_inspetor])) {
                    $os_tipo_atendimento_status_inspetor[$key_inspetor] = array();
                }

                if (!isset($os_tipo_atendimento_status_inspetor[$key_inspetor][$row->status_checkpoint_descricao])) {
                    $os_tipo_atendimento_status_inspetor[$key_inspetor][$row->status_checkpoint_descricao] = array();
                }

                if (!isset($os_tipo_atendimento_status_inspetor[$key_inspetor][$row->status_checkpoint_descricao][$row->tipo_atendimento])) {
                    $os_tipo_atendimento_status_inspetor[$key_inspetor][$row->status_checkpoint_descricao][$row->tipo_atendimento] = array();
                }

                $os_tipo_atendimento_status_inspetor[$key_inspetor][$row->status_checkpoint_descricao][$row->tipo_atendimento][] = $os;

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
                            $os_em_atraso[$key_inspetor][$row->status_checkpoint_descricao][$row->tipo_atendimento][] = $os;
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
                            $os_em_atraso[$key_inspetor][$row->status_checkpoint_descricao][$row->tipo_atendimento][] = $os;
                            $os["atrasada"] = true;
                        }
                    }
                }
            }

            $os_lista[] = $os;

            $values = array(
                "'{$row->sua_os}'",
                "'{$row->serie}'",
                "'".date("d/m/Y", strtotime($row->data_abertura))."'",
                "'{$primeira_data_agendamento}'",
                "'{$data_confirmacao}'",
                "'{$ultima_data}'",
                "'{$key_inspetor}'",
                "'{$row->consumidor_revenda}'",
                "'{$row->codigo_posto}'",
                "'{$row->nome_posto}'",
                "'{$row->contato_cidade}'",
                "'{$row->contato_estado}'",
                "'{$row->consumidor_revenda_nome}'",
                "'{$row->consumidor_revenda_bairro}'",
                "'{$row->consumidor_revenda_cidade}'",
                "'{$row->nota_fiscal}'",
                "'{$row->produto_referencia} - {$row->produto_descricao}'",
                "'{$row->status_checkpoint_descricao}'"
            );
            fwrite($file, implode(";", $values)."\n");
        }

        $os_tipo_atendimento = array_unique($os_tipo_atendimento);
        $os_status           = array_unique($os_status);

        fclose($file);

        $tempo_posto = array_map(function($v) {
            $v["total"] = array_sum($v);
            return $v;
        }, $tempo_posto);

        $tempo_posto_order = array_map(function($v) {
            return (integer) $v["total"];
        }, $tempo_posto);

        array_multisort($tempo_posto_order, SORT_DESC, SORT_NUMERIC, $tempo_posto);

        $tempo_inspetor = array_map(function($v) {
            $v["total"] = array_sum($v);
            return $v;
        }, $tempo_inspetor);

        if ($pesquisa_periodo) {
            $whereData = "AND (tbl_os.data_digitacao BETWEEN '{$data_inicial}' AND '{$data_final}')";
        } else {
            $whereData = "AND (tbl_os.data_digitacao BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '{$interval} months' AND '{$current_date_final}')";
        }

        if (empty($whereStatusOs)) {
            $sqlOsFinalizada = "
                SELECT COUNT(*) AS qtde, (CASE WHEN EXTRACT(DAYS FROM (tbl_os.data_conserto - tbl_os.data_digitacao)) <= 10 THEN TRUE ELSE FALSE END) AS finalizada_10_dias
                FROM tbl_os
                INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.excluida IS NOT TRUE
                {$cond6359}
                {$whereData}
				AND tbl_os.finalizada notnull
				AND tbl_os.excluida is not true
                AND tbl_os.data_conserto IS NOT NULL
                {$whereOsPosto}
                {$whereOsTipoAtendimento}
                {$whereOsFamilia}
                {$whereOsLinha}
                {$whereInspetor}
                {$whereTipoPosto}
                {$whereEstado}
                {$whereOsTipoOs}
                GROUP BY finalizada_10_dias
            ";
            #echo nl2br($sqlOsFinalizada);
            $resOsFinalizada = pg_query($con, $sqlOsFinalizada);

            $os_finalizada_10_dias = array("t" => 0, "f" => 0);

            while ($r = pg_fetch_object($resOsFinalizada)) {
                $os_finalizada_10_dias[$r->finalizada_10_dias] += $r->qtde;
            }

            $grafico_os_finalizada_10_dias = array();

            foreach ($os_finalizada_10_dias as $tipo => $qtde) {
                $tipo = ($tipo == "t") ? "Finalizada em até 10 dias" : "Finalizada em mais de 10 dias";
                $grafico_os_finalizada_10_dias[] = array(
                    (string) utf8_encode($tipo),
                    (int) $qtde
                );
            }

            $grafico_os_finalizada_10_dias = json_encode($grafico_os_finalizada_10_dias);
        }
    }

    /**
     * Busca os Pedidos
     */
    if ($areaAdmin) {
        $camposPedidoStatus = "
            , tbl_status_pedido.descricao AS status
        ";
    } else {
        $camposPedidoStatus = "
            , CASE WHEN tbl_status_pedido.status_pedido IN(1, 2) THEN
                'Pendente'
            ELSE
                tbl_status_pedido.descricao
            END AS status
        ";
    }

    if ($pesquisa_periodo) {
        $whereData = "AND (tbl_pedido.data BETWEEN '{$data_inicial}' AND '{$data_final}')";
    } else {
        $whereData = "AND (tbl_pedido.data BETWEEN '{$current_date_inicial}'::timestamp - INTERVAL '{$interval} months' AND '{$current_date_final}')";
    }

    $sqlQtdePedido = "
        SELECT
            COUNT(tbl_pedido.pedido) AS qtde_pedido
            {$camposPedidoStatus}
        FROM tbl_pedido
        INNER JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
        INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
        WHERE tbl_pedido.fabrica = {$login_fabrica}
        {$whereData}
        {$wherePedidoPosto}
        {$wherePedidoTipo}
        {$whereInspetor}
        {$whereTipoPosto}
        {$whereEstado}
        GROUP BY status
        ORDER BY status, qtde_pedido
    ";
    $resQtdePedido = pg_query($con, $sqlQtdePedido);

    $grafico_pedido_series = array();

    while ($row = pg_fetch_object($resQtdePedido)) {
        $grafico_pedido_series[] = array(
            (string) utf8_encode($row->status),
            (int) $row->qtde_pedido
        );
    }

    $grafico_pedido_series = json_encode($grafico_pedido_series);

    if (in_array($login_fabrica, array(158))) {
        $extratoTipo = ", tbl_extrato.protocolo";
    }
    /**
     * Busca os Extratos
     */
    if ($pesquisa_periodo) {
        $whereData = "AND (tbl_extrato.data_geracao BETWEEN '{$data_inicial}' AND '{$data_final}')";
    } else {
        $whereData = "AND (tbl_extrato.data_geracao BETWEEN CURRENT_DATE - INTERVAL '{$interval} months' AND CURRENT_DATE)";
    }

    $sqlExtrato = "
        SELECT
            COUNT(tbl_extrato.extrato) AS qtde_extrato,
            SUM(tbl_extrato.total) AS total_extrato,
            EXTRACT(MONTH FROM tbl_extrato.data_geracao) AS mes
            {$extratoTipo}
        FROM tbl_extrato
        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_extrato.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
        WHERE tbl_extrato.fabrica = {$login_fabrica}
        {$whereData}
        {$whereExtratoTipo}
        {$whereExtratoPosto}
        {$whereInspetor}
        {$whereTipoPosto}
        {$whereEstado}
        GROUP BY mes {$extratoTipo}
        ORDER BY mes {$extratoTipo}
    ";
    $resExtrato = pg_query($con, $sqlExtrato);

    $grafico_valores_extrato = array();
    $mes_key = array();
    $grafico_mes_categorias = array();

    for ($i = 0; $i <= $interval; $i++) {
        $m = $interval - $i;
        $mes_key[(int) date("m", strtotime("-{$m} months"))] = $i;
        $grafico_mes_categorias[] = utf8_encode($meses_idioma["pt-br"][(int) date("m", strtotime("-{$m} months"))]."/".date("Y", strtotime("-{$m} months")));
    }

    $grafico_mes_categorias = json_encode($grafico_mes_categorias);

    if ($login_fabrica == 158) {
        $total_qtde_extrato = array();
    }

    while ($row = pg_fetch_object($resExtrato)) {
        if (!$row->protocolo) {
            $row->protocolo = "extrato";
        }

        if ($login_fabrica == 158) {
            $total_qtde_extrato[$row->protocolo] += (int) $row->qtde_extrato;
        }

        $grafico_valores_extrato[$row->protocolo]["name"] = $row->protocolo;
        $grafico_valores_extrato[$row->protocolo]["type"] = "column";

        if (!isset($grafico_valores_extrato[$row->protocolo]["data"])) {
            $grafico_valores_extrato[$row->protocolo]["data"] = array_fill(0, ($interval + 1), 0);
        }

        $grafico_valores_extrato[$row->protocolo]["data"][$mes_key[$row->mes]] = (double) $row->total_extrato;
    }

    if ($login_fabrica == 158) {
        $grafico_total_qtde_extrato = array();

        foreach ($total_qtde_extrato as $tipo => $qtde) {
            $grafico_total_qtde_extrato[] = array(
                (string) $tipo,
                (int) $qtde
            );
        }

        $grafico_total_qtde_extrato = json_encode($grafico_total_qtde_extrato);
    }

    $grafico_valores_extrato_series = array();

    foreach ($grafico_valores_extrato as $key => $value) {
        $grafico_valores_extrato_series[] = $value;
    }
    $grafico_valores_extrato    = json_encode($grafico_valores_extrato_series);
}

$layout_menu = ($areaAdmin) ? "gerencia" : "os";
$title = "DASHBOARD";

if(!$areaAdmin) {
	$sql = "SELECT  senha_financeiro
			FROM    tbl_posto_fabrica
			WHERE   tbl_posto_fabrica.posto     = $login_posto
			AND     tbl_posto_fabrica.fabrica   = $login_fabrica
			AND     senha_financeiro            IS NOT NULL
			AND     LENGTH(senha_financeiro)    > 0
	";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$senha_financeiro = pg_fetch_result($res,0,senha_financeiro);
		$esconder = "SIM";
	}else{
		$esconder = "NAO";
	}
}
if ($areaAdmin) {
    include __DIR__."/admin/cabecalho_new.php";
} else {
    include __DIR__."/cabecalho_new.php";
}
?>

<style>

#modal-os-filter {
    width: 80%;
    margin-left:-40%;
}

</style>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-danger">
        <?=implode("<br />", $msg_erro["msg"])?>
    </div>
<?php
}
?>

<div class="tc_formulario" >
    <?php
    if ($areaAdmin) {
    ?>
        <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
        <br />
        <form method="POST" class="form-search form-inline" >
            <?php
            if (in_array($login_fabrica, array(169,170))) {
            ?>
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
            <?php
            }
            ?>

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

                <?php
                if (in_array($login_fabrica, array(169,170,183))) {
                ?>
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
                                            $selected = (in_array($row->admin, $_POST["inspetor"])) ? "selected" : "";
                                            echo "<option value='{$row->admin}' {$selected} >{$descricao}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }

                if (in_array($login_fabrica, array(158))) {
                ?>
                    <div class="span6" >
                        <div class="control-group" >
                            <label class="control-label" >Tipo</label>
                            <div class="controls controls-row" >
                                <div class="span12 radio" >
                                    <label class="radio" >
                                        <input type="radio" name="tipo" value="" checked /> Todas
                                    </label>

                                    <label class="radio" >
                                        <input type="radio" name="tipo" value="garantia" <?=(getValue("tipo") == "garantia") ? "checked" : ""?> /> Garantia
                                    </label>

                                    <label class="radio" >
                                        <input type="radio" name="tipo" value="fora_garantia" <?=(getValue("tipo") == "fora_garantia") ? "checked" : ""?> /> Fora de Garantia
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>

            <div class="row-fluid" >
                <div class="span1" ></div>

                <?php
                if (!in_array($login_fabrica, array(158))) {
                ?>
                    <div class="span3" >
                        <div class="control-group" >
                            <label class="control-label" >Linha <label class="text-error">(ordens de serviço)</label></label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                				    <?php
                				    $multipleLinha = "";
                				    $nameLinha = "linha";
                				    if (in_array($login_fabrica, array(169,170))) {
                					$multipleLinha = "multiple";
                					$nameLinha = "linha[]";
                				    } ?>
                                    <select id="linha" name="<?= $nameLinha; ?>" class="span12" <?= $multipleLinha; ?> >
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
                    					    if (!empty($multipleLinha)) {
                    						  $selected = (in_array($row->linha, $_POST["linha"])) ? "selected" : "";
                    					    } else {
                                        	   $selected = ($row->linha == $_POST["linha"]) ? "selected" : "";
				                            }
                                            echo "<option value='{$row->linha}' {$selected} >{$row->nome}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>

                <div class="span3" >
                    <div class="control-group" >
                        <label class="control-label" for="familia" >Família <label class="text-error">(ordens de serviço)</label></label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                				<?php
                				$multipleFamilia = "";
                				$nameFamilia = "familia";
                				if (in_array($login_fabrica, array(169,170))) {
                				    $multipleFamilia = "multiple";
                				    $nameFamilia = "familia[]";
                				} ?>
                                <select class="span12" name="<?= $nameFamilia; ?>" id="familia" <?= $multipleFamilia; ?> >
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
                    					if (!empty($multipleFamilia)) {
                    					    $selected = (in_array($row->familia, $_POST['familia'])) ? "selected" : "";
                    					} else {
                                            $selected = ($row->familia == $_POST['familia']) ? "selected" : "";
				                        }
                                        echo "<option value='{$row->familia}' {$selected} >{$row->descricao}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                if (!in_array($login_fabrica, array(158))) {
                ?>
                    <div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" >Tipo de Atendimento <label class="text-error">(ordens de serviço)</label></label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <select class="span12" id="tipo_atendimento" name="tipo_atendimento[]" multiple >
                                        <?php
                                        if (!in_array($login_fabrica, array(169,170))) {
                                        ?>
                                            <option value="" >Selecione</option>
                                        <?php
                                        } else {
                                            $whereTipoAtendimentoForaGarantia = "AND (fora_garantia IS NOT TRUE OR (fora_garantia IS TRUE AND grupo_atendimento IS NOT NULL))";
                                        }

                                        $sqlTipoAtendimento = "
                                            SELECT tipo_atendimento, descricao
                                            FROM tbl_tipo_atendimento
                                            WHERE fabrica = {$login_fabrica}
                                            AND ativo IS TRUE
                                            {$whereTipoAtendimentoForaGarantia}
                                            ORDER BY descricao
                                        ";
                                        $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

                                        while ($row = pg_fetch_object($resTipoAtendimento)) {
                                            $selected = (in_array($row->tipo_atendimento, $_POST["tipo_atendimento"])) ? "selected" : "";
                                            echo "<option value='{$row->tipo_atendimento}' {$selected} >{$row->descricao}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>

            <?php if (in_array($login_fabrica, array(169,170,183))) { ?>
                <div class="row-fluid" >
                    <div class="span1" ></div>
            	    <?php if (in_array($login_fabrica, [169,170])) { ?>
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
                                            $selected = (in_array($row->tipo_posto, $_POST["tipo_posto"])) ? "selected" : "";
                                            echo "<option value='{$row->tipo_posto}' {$selected} >{$row->descricao}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
		    <?php } ?>
                    <div class="span4" >
                        <div class="control-group" >
                            <label class="control-label" >Estado do Posto Autorizado</label>
                            <div class="controls controls-row" >
                                <div class="span12" >
                                    <select class="span12" id="estado" name="estado[]" multiple >
                                        <?php
                                        foreach ($array_estados() as $sigla => $estado) {
                                            $selected = (in_array($sigla, $_POST["estado"])) ? "selected" : "";
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

					if ($login_fabrica == 183) {
						$sql = "
							SELECT
								status_checkpoint,
								descricao,
								CASE WHEN status_checkpoint = 0 THEN 0
                                                		WHEN status_checkpoint = 1 THEN 1
                                                		WHEN status_checkpoint = 2 THEN 2
                                               			WHEN status_checkpoint = 8 THEN 3
                                                		WHEN status_checkpoint = 3 THEN 4
                                                		WHEN status_checkpoint = 30 THEN 5
                                                		WHEN status_checkpoint = 9 THEN 6
                                                		WHEN status_checkpoint = 28 THEN 7 END AS ordem
                                            		FROM tbl_status_checkpoint
                                            		WHERE status_checkpoint IN (0,1,2,3,8,9,28,30)
                                            		ORDER BY ordem ASC;
						";
					} else if (in_array($login_fabrica, [169,170])) {
                                        $sql = "
                                            SELECT 
                                                status_checkpoint,
                                                descricao,
                                                CASE WHEN status_checkpoint = 0 THEN 0
						WHEN status_checkpoint = 1 THEN 1
						WHEN status_checkpoint = 2 THEN 2
						WHEN status_checkpoint = 8 THEN 3
						WHEN status_checkpoint = 45 THEN 4
						WHEN status_checkpoint = 46 THEN 5
						WHEN status_checkpoint = 47 THEN 6
						WHEN status_checkpoint = 3 THEN 7
						WHEN status_checkpoint = 4 THEN 8
						WHEN status_checkpoint = 14 THEN 9
						WHEN status_checkpoint = 30 THEN 10
						WHEN status_checkpoint = 9 THEN 11
						WHEN status_checkpoint = 48 THEN 12
						WHEN status_checkpoint = 49 THEN 13
						WHEN status_checkpoint = 50 THEN 14
						WHEN status_checkpoint = 28 THEN 15 END AS ordem
                                            FROM tbl_status_checkpoint
                                            WHERE status_checkpoint IN(0,1,2,8,45,46,47,3,4,14,30,9,48,49,50,28)
                                            ORDER BY ordem ASC
                                        ";
					}
                                        $res = pg_query($con, $sql);
                                        $res = pg_fetch_all($res);

                                        foreach ($res as $s) {
                                            $selected = (in_array($s["status_checkpoint"], $_POST["status_os"])) ? "selected" : "";
                                            echo "<option value='{$s['status_checkpoint']}' {$selected} >{$s['descricao']}</option>";
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
               
            <div class="span4" >
                <div class="control-group" id="marca_bd">
                    <label class='control-label'>Pedido de Peça <label class="text-error">&nbsp;</label></label>
                    <div class="controls controls-row">
                        <div class="span12">
                            <select name='pedido_peca[]' id='pedido_peca' class='span12 tipo_posto_bd bd_sel' multiple="multiple">
                                <option <?php echo (in_array('sem_pedido',$pedido_peca)) ? "selected" : ""; ?> value="sem_pedido">Os sem pedido de peça</option>
                                <option <?php echo (in_array('com_pedido',$pedido_peca)) ? "selected" : ""; ?> value="com_pedido">Os com pedido de peça</option>
                            </select>
                        </div>
                   </div>
               </div>
             </div>
          <?
            }
          ?>  

            </div>
            <?php
            }
            ?>
            <br />

            <p class="tac" >
                <button type="submit" class="btn" >Pesquisar</button>
            </p>

            <br />
        </form>
    <?php
    }else if (!$areaAdmin AND in_array($login_fabrica, array(169,170))){
    ?>
        <div class='titulo_tabela'>Parâmetros de Pesquisa</div>
        <br />
        <form method="POST" class="form-search form-inline">
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4" >
                    <div class="control-group" >
                        <label class="control-label" >Tipo de Atendimento <label class="text-error">(ordens de serviço)</label></label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <select class="span12" id="tipo_atendimento" name="tipo_atendimento[]" multiple >
                                    <?php
                                    $whereTipoAtendimentoForaGarantia = "AND (fora_garantia IS NOT TRUE OR (fora_garantia IS TRUE AND grupo_atendimento IS NOT NULL))";
                                    
                                    $sqlTipoAtendimento = "
                                        SELECT tipo_atendimento, descricao
                                        FROM tbl_tipo_atendimento
                                        WHERE fabrica = {$login_fabrica}
                                        AND ativo IS TRUE
                                        {$whereTipoAtendimentoForaGarantia}
                                        ORDER BY descricao
                                    ";
                                    $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

                                    while ($row = pg_fetch_object($resTipoAtendimento)) {
                                        $selected = (in_array($row->tipo_atendimento, $_POST["tipo_atendimento"])) ? "selected" : "";
                                        echo "<option value='{$row->tipo_atendimento}' {$selected} >{$row->descricao}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span4" >
                    <div class="control-group" >
                        <label class="control-label" >Tipo de OS <label class="text-error">(ordens de serviço)</label></label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <select class="span12" id="tipo_os" name="tipo_os[]" multiple >
                                    <?php 
                                    $array_tipo_os = array( "C" => "Consumidor", "R" => "Revenda" );
                                    foreach ($array_tipo_os as $key => $value) {
                                        $selected = (in_array($key, $_POST["tipo_os"])) ? "selected" : "";
                                    ?>
                                        <option <?=$selected?> value='<?=$key?>'><?=$value?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
            <p class="tac" >
                <button type="submit" class="btn" >Pesquisar</button>
            </p>
            <br />
        </form>
    <?php    
    }

    if (!$pesquisa && in_array($login_fabrica, array(169,170))) {
    ?>
        <div class="alert alert-info">
            <strong>Se a pesquisa for realizada sem informar um período será mostrado o resultado dos últimos <?=$interval?> meses clique em Pesquisar.</strong>
        </div>
    <?php
    }

    if ($pesquisa) {
        if (pg_num_rows($resQtdeOs) > 0) {
        ?>
            <div id="grafico_tempo_os" ></div>
            <?php
            if (in_array($login_fabrica, array(169,170,183)) && $areaAdmin) {
            ?>
                <br />

                <button type="button" class="btn btn-block btn-success download-arquivo-csv" data-arquivo="<?=$arquivo_os_csv?>" >Download arquivo CSV das Ordens de Serviço</button>

                <br />

    </div>
</div>
                <table class="table table-bordered" style="width: 100%; margin-bottom: 0px !important;" >
                    <thead>
                        <tr class="titulo_coluna" >
                            <th colspan="<?=(count($os_tipo_atendimento) * 3) + 4?>" >Ordens de Serviço - Inspetor x Tipo de Atendimento x Status</th>
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
                    <tbody>
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
                    </tfoot>
                </table>

<div class="container tc_container" >
    <div class="tc_formulario" >
                <br />

                <table class="table table-bordered table-striped" style="width: 100%;" >
                    <thead>
                        <tr class="titulo_coluna" >
                            <th colspan="7" >Ordens de Serviço - Inspetor</th>
                        </tr>
                        <tr class="titulo_coluna" >
                            <th>Inspetor</th>
                            <th>0-3 Dias</th>
                            <th>4-10 Dias</th>
                            <th>11-20 Dias</th>
                            <th>21-30 Dias</th>
                            <th> 30 Dias</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($tempo_inspetor as $inspetor => $arr_os) {
                        ?>
                            <tr>
                                <td><?=$inspetor?></td>
                                <?php
                                foreach ($tempo_inspetor_posto as $dias) {
                                ?>
                                    <td class="tac" style="vertical-align: middle;" ><?=(empty($arr_os[$dias])) ? 0 : "<button class='btn btn-link explodir-os' data-acao='inspetor' data-filtro='$inspetor' data-tempo='$dias' >".$arr_os[$dias]."</button>"?></td>
                                <?php
                                }
                                ?>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                <br />
                <table class="table table-bordered table-striped" style="width: 100%;" >
                    <thead>
                        <tr class="titulo_coluna" >
                            <th colspan="8" >Ordens de Serviço - Posto Autorizado</th>
                        </tr>
                        <tr class="titulo_coluna" >
                            <th>Posto Autorizado</th>
                            <th>0-3 Dias</th>
                            <th>4-10 Dias</th>
                            <th>11-20 Dias</th>
                            <th>21-30 Dias</th>
                            <th>> 30 Dias</th>
                            <th>Total</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        $total = array_sum($tempo_posto_order);

                        foreach ($tempo_posto as $posto => $arr_os) {
                            $display = ($i <= 120) ? "table-row" : "none";
                            ?>
                            <tr style="display: <?=$display?>;" >
                                <td><?=$posto?></td>
                                <?php
                                foreach ($tempo_inspetor_posto as $dias) {
                                    ?>
                                    <td class="tac" style="vertical-align: middle;" ><?=(empty($arr_os[$dias])) ? 0 : "<button class='btn btn-link explodir-os' data-acao='posto' data-filtro='$posto' data-tempo='$dias' >".$arr_os[$dias]."</button>"?></td>
                                <?php
                                }
                                ?>
                                <td style="vertical-align: middle;" ><?=number_format((($arr_os["total"] / $total) * 100), 2, ",", "")?>%</td>
                            </tr>
                            <?php
                            $i++;
                        }
                        ?>
                    </tbody>
                    <?php
                    if (count($tempo_posto) > 120) {
                    ?>
                        <tfoot>
                            <tr>
                                <th colspan="8" >
                                    <button type="button" class="btn btn-primary btn-block carrega-os-pa">Carregar mais <?=(count($tempo_posto) - 120)?> Postos Autorizados</button>
                                </th>
                            </tr>
                        </tfoot>
                    <?php
                    }
                    ?>
                </table>
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
                                            <th>Linha</th>
                                            <th>Família</th>
                                            <th>Produto</th>
                                            <th>Primeira data de agendamento</th>
                                            <th>Data de confirmação do posto autorizado</th>
                                            <th>Última data de agendamento</th>
                                            <th>Inspetor</th>
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
            }
        } else {
        ?>
            <div class="alert alert-danger" ><strong>Não foram encontradas OSs abertas, <?=$interval_label?></strong></div>
        <?php
        }
        ?>

        <hr />

        <?php
        if (pg_num_rows($resQtdePedido) > 0) {
        ?>
            <div id="grafico_pedido" ></div>
        <?php
        } else {
        ?>
            <div class="alert alert-danger" ><strong>Não foram encontradas Pedidos gerados, <?=$interval_label?></strong></div>
        <?php
        }
        ?>

        <hr />

        <?php
        if (pg_num_rows($resExtrato) > 0) {
			if($esconder == "SIM"){
		?>
			<div class="accordion">
					<div id="senha_extrato">
						<div id="senha">
							<span>Validação de Senha</span>
							<br />
							<p style="text-align:center;">
								Para acessar o gráfico, favor Digitar a senha de acesso do financeiro
							</p>
							<br />
							<cite id="msg" style="display:none;">Favor, digitar a senha correta</cite>
							<input type="password" name="senha_financeiro" id="senha_financeiro" >
							<br />
							<button type="button" onclick="javascript:senhaFinanceiro();">Acessar</button>
						</div>
					</div>
			<?
			}
			?>
            <div id="grafico_extrato" style="height: 350px; margin: 0 auto;<? if($esconder == "SIM"){?>opacity:0;<?}?>"></div>
        <?php
        } else {
        ?>
            <div class="alert alert-danger" ><strong>Não foram encontrados Extratos gerados, <?=$interval_label?></strong></div>
        <?php
        }
    }
    ?>
</div>

<?php
$plugins = array(
   "select2",
   "highcharts",
   "shadowbox",
   "dataTable",
   "mask",
   "datepicker"
);

include __DIR__."/admin/plugin_loader.php";
?>

<script>

$(function() {
Shadowbox.init();
});

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

<?
if(strlen($senha_financeiro) > 0){
?>
function senhaFinanceiro(){
    var senha = document.getElementById("senha_financeiro").value;
    if(senha == '<?=$senha_financeiro?>'){
        document.getElementById("senha_extrato").style.display="none";
        document.getElementById("grafico_extrato").style.opacity=1;
    }else{
        document.getElementById("msg").style.display="block";
        document.getElementById("msg").style.color="#F00";
        document.getElementById("senha_financeiro").value="";
    }
}
<?
}
?>

$("select").select2();

<?php
if (in_array($login_fabrica, array(169, 170))) {
?>
    $.datepickerLoad(["data_inicial", "data_final"]);
<?php
}

if ($pesquisa) {
?>
    if ($("#grafico_tempo_os").length > 0) {
        var status_obj = <?=$status_os_json?>;
        var tempo_pesquisa = <?=json_encode($tempo_pesquisa_json)?>;

        var grafico_tempo_os_series = <?=$grafico_tempo_os_series?>;


        <?php
        if (in_array($login_fabrica, array(169,170)) && empty($whereStatusOs)) {
        ?>
            grafico_tempo_os_series.push({
                type: "pie",
                name: "OSs Finalizadas",
                data: <?=$grafico_os_finalizada_10_dias?>,
                center: [450, 0],
                size: 50,
                showInLegend: false,
                dataLabels: {
                    enabled: true,
                    formatter: function() {
                        return this.point.name + " - " + Highcharts.numberFormat(parseFloat(this.percentage), 2, ",", ".") + "%";
                    }
                }
            });
        <?php
        }
        ?>

        $("#grafico_tempo_os").highcharts({
            chart: {
                type: "column"
            },
            title: {
                text: "OSs abertas, <?=$interval_label?>"
            },
            xAxis: {
                categories: <?=$categorias_json?>
            },
            yAxis: {
                minorTickInterval: "auto",
                minorTickLength: 0,
                min: 0,
                title: {
                    text: "Quantidade de OSs"
                }
            },
            tooltip: {
                headerFormat: "<table>",
                pointFormat: "\
                    <tr>\
                        <td style='color: {series.color}; padding: 0;' >{series.name}: </td>\
                        <td style='padding: 0;' nowrap ><b>{point.y} OS</b></td>\
                    </tr>\
                ",
                footerFormat: "</table>",
                useHTML: true
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0,
                    dataLabels: {
                        enabled: true,
                        format: "{y}"
                    }
                },
                series: {
                    cursor: "pointer",
                    point: {
                        events: {
                            click: function() {
                                var filtros = ["btn_acao=1", "data_tipo=digitacao"];

                                if (this.shapeType == "arc") {
                                    var data = this;

                                    if (data.total == 0) {
                                        return false;
                                    }

                                    var data_final   = "<?=$data_final_pesquisa?>";
                                    var data_inicial  = "<?=$data_inicial_pesquisa?>";

                                    //datas
                                    filtros.push("finalizada_index="+data.index);

                                    <?php
                                    if ($areaAdmin) {
                                    ?>
                                        //posto
                                        if ($("#posto_id").val() != null && $("#posto_id").val() != '') {
                                            [codigo_posto, posto_nome] = $("#posto_codigo_nome").val().split("-");

                                            filtros.push("codigo_posto="+codigo_posto.trim());
                                            filtros.push("posto_nome="+posto_nome.trim());
                                        }

                                        //linha
                                        if ($("#linha").val() != null && $("#linha").val() != '') {
                                            var linha = $("#linha").val();

                                            filtros.push("linha="+linha);
                                        }

                                        //tipo_atendimento
                                        if ($("#tipo_atendimento").val() != null && $("#tipo_atendimento").val() != '') {
                                            var tipo_atendimento = $("#tipo_atendimento").val();
                                            filtros.push("tipo_atendimento="+tipo_atendimento);
                                        }

                                        //inspetor
                                        if ($("#inspetor").val() != null && $("#inspetor").val() != '') {
                                            var inspetor = $("#inspetor").val();

                                            filtros.push("admin_sap="+inspetor);
                                        }

                                        //familia
                                        if ($("#familia").val() != null && $("#familia").val() != '') {
                                            var familia = $("#familia").val();

                                            filtros.push("familia="+familia);
                                        }

                                        //tipo_posto
                                        if ($("#tipo_posto").val() != null && $("#tipo_posto").val() != '') {
                                            var tipo_posto = $("#tipo_posto").val();

                                            filtros.push("tipo_posto="+tipo_posto);
                                        }

                                        //estado
                                        if ($("#estado").val() != null && $("#estado").val() != '') {
                                            var estado = $("#estado").val();

                                            filtros.push("posto_estado="+estado);
                                        }
                                    <?php
                                    }
                                    ?>

                                    //status
                                    filtros.push("status_checkpoint=9");
                                }else{
                                    if (this.y == 0) {
                                        return false;
                                    }

                                    var data   = this.category;
                                    var status = status_obj[this.series.name];

                                    var data_inicial = new Date();
                                    var data_final   = new Date();
                                    var dia, mes, ano;

                                    if (tempo_pesquisa[data].d2 == null) {
                                        data_inicial = "<?=$data_inicial_pesquisa?>";
                                        data_final = data_final.removeDays(tempo_pesquisa[data].d1);
										filtros.push("intervalo1="+tempo_pesquisa[data].d1);
                                    } else {
                                        data_final = data_final.removeDays(tempo_pesquisa[data].d2);
                                        data_inicial = data_inicial.removeDays(tempo_pesquisa[data].d1);

                                        dia          = String(data_inicial.getDate()).lpad("0", 2);
                                        mes          = String((data_inicial.getMonth() + 1)).lpad("0", 2);
                                        ano          = data_inicial.getFullYear();
                                        data_inicial =  dia + "/" + mes + "/" + ano;
										filtros.push("intervalo1="+tempo_pesquisa[data].d1);
										filtros.push("intervalo2="+tempo_pesquisa[data].d2);
                                    }

                                    dia        = String(data_final.getDate()).lpad("0", 2);
                                    mes        = String((data_final.getMonth() + 1)).lpad("0", 2);
                                    ano        = data_final.getFullYear();
                                    data_final =  dia + "/" + mes + "/" + ano;

                                    filtros.push("status_checkpoint="+status);
                                }

                                //datas
                                <?php
                                    if (in_array($login_fabrica,[169,170])) {
                                ?>
                                    data_final = "<?=$data_final_pesquisa?>";
                                <?php 
                                    } 
                                ?>
                                filtros.push("data_inicial="+data_inicial);
                                filtros.push("data_final="+data_final);

                                <?php
                                if ($areaAdmin) {
                                ?>
                                    //posto
                                    if ($("#posto_id").val() != null && $("#posto_id").val() != '') {
                                        [codigo_posto, posto_nome] = $("#posto_codigo_nome").val().split("-");

                                        filtros.push("codigo_posto="+codigo_posto.trim());
                                        filtros.push("posto_nome="+posto_nome.trim());
                                    }

                                    <?php
                                    if (in_array($login_fabrica, array(158))) {
                                    ?>
                                        //tipo garantia ou fora de garantia
                                        if ($("input[name=tipo]:checked").val() != null && $("input[name=tipo]:checked").val() != '') {
                                            var tipo = $("input[name=tipo]:checked").val();

                                            filtros.push("tipo_garantia="+tipo);
                                        }
                                    <?php
                                    } else {
                                    ?>
                                        //linha
                                        if ($("#linha").val() != null && $("#linha").val() != '') {
                                            var linha = $("#linha").val();

                                            filtros.push("linha="+linha);
                                        }

                                        //tipo_atendimento
                                        if ($("#tipo_atendimento").val() != null && $("#tipo_atendimento").val() != '') {
                                            var tipo_atendimento = $("#tipo_atendimento").val();
                                            filtros.push("tipo_atendimento="+tipo_atendimento);
                                        }
                                    <?php
                                    }

                                    if ($login_fabrica == 183) {
                                    ?>
                                        if ($("#inspetor").val() != null && $("#inspetor").val() != '') {
                                            var inspetor = $("#inspetor").val();

                                            filtros.push("admin_sap="+inspetor);
                                        }

                                        //estado
                                        if ($("#estado").val() != null && $("#estado").val() != '') {
                                            var estado = $("#estado").val();

                                            filtros.push("posto_estado="+estado);
                                        }
                                        
                                    <?php
                                    }

                                    if (in_array($login_fabrica, array(169,170))) {
                                    ?>
                                        //inspetor
                                        if ($("#inspetor").val() != null && $("#inspetor").val() != '') {
                                            var inspetor = $("#inspetor").val();

                                            filtros.push("admin_sap="+inspetor);
                                        }

                                        //tipo_posto
                                        if ($("#tipo_posto").val() != null && $("#tipo_posto").val() != '') {
                                            var tipo_posto = $("#tipo_posto").val();

                                            filtros.push("tipo_posto="+tipo_posto);
                                        }

                                        //estado
                                        if ($("#estado").val() != null && $("#estado").val() != '') {
                                            var estado = $("#estado").val();

                                            filtros.push("posto_estado="+estado);
                                        }

                                        //status
                                        if ($("#status_os").val() != null && $("#status_os").val() != '') {
                                            var status_os = $("#status_os").val();

                                        }

                                         if ($("#pedido_peca").val() != null && $("#pedido_peca").val() != '') {
                                            var pedido_peca = $("#pedido_peca").val();

                                            filtros.push("pedido_peca="+pedido_peca);
                                        }    
                                    <?php   
                                    }
                                    ?>

                                    //familia
                                    if ($("#familia").val() != null && $("#familia").val() != '') {
                                        var familia = $("#familia").val();

                                        filtros.push("familia="+familia);
                                    }
                                <?php
                                }else if (!$areaAdmin AND in_array($login_fabrica, array(169,170))){
                                ?>
                                    //tipo_atendimento
                                    if ($("#tipo_atendimento").val() != null && $("#tipo_atendimento").val() != '') {
                                        var tipo_atendimento = $("#tipo_atendimento").val();

                                        filtros.push("tipo_atendimento="+tipo_atendimento);
                                    }
                                    if ($("#tipo_os").val() != null && $("#tipo_os").val() != '') {
                                        var tipo_os = $("#tipo_os").val();

                                        filtros.push("tipo_os="+tipo_os);
                                    }
                                <?php    
                                }
                                if ($usaNovaTelaConsultaOs) {
                                ?>
                                    filtros.push("action=formulario_pesquisa");                                
                                    window.open("consulta_lite_new.php?"+filtros.join("&"));
                                <?php
                                }else{
                                ?>
                                    window.open("os_consulta_lite.php?"+filtros.join("&"));
                                <?php
                                }
                                ?>
                            }
                        }
                    }
                }
            },
            series: grafico_tempo_os_series
        });
    }

    if ($("#grafico_pedido").length > 0) {
        $("#grafico_pedido").highcharts({
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: "pie"
            },
            title: {
                text: "Pedidos gerados, <?=$interval_label?>"
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: "pointer",
                    dataLabels: {
                        enabled: false
                    },
                    showInLegend: true
                },
                series: {
                    point: {
                        events: {
                            click: function() {
                                var status = this.name;

                                var data_inicial = "<?=$data_inicial_pesquisa?>";
                                var data_final   = "<?=$data_final_pesquisa?>";

                                var filtros = ["btn_acao=continuar"];
                                switch (status) {

                                    case "Pendente":
                                        filtros.push("pedido_status=0");
                                        break;

                                    case "Faturado Integral":
                                        filtros.push("pedido_status=4");
                                        break;

                                    case "Faturado Parcial":
                                        filtros.push("pedido_status=5");
                                        break;

                                    case "Cancelado Total":
                                        filtros.push("pedido_status=14");
                                        break;

                                    case "Aguardando aprovação":
                                        filtros.push("pedido_status=18");
                                        break;

                                    case "Aguardando Faturamento":
                                        filtros.push("pedido_status=2");
                                        break;

                                    default:
                                        filtros.push("pedido_status=1");
                                        break;
                                }

                                <?php
                                if ($areaAdmin) {
                                ?>
                                    //posto
                                    if ($("#posto_id").val() != null && $("#posto_id").val() != '') {
                                        [codigo_posto, posto_nome] = $("#posto_codigo_nome").val().split("-");

                                        filtros.push("chk_opt7=true");
                                        filtros.push("codigo_posto="+codigo_posto.trim());
                                        filtros.push("nome_posto="+posto_nome.trim());
                                    }

                                    <?php
                                    if (in_array($login_fabrica, array(158))) {
                                    ?>
                                        //tipo garantia ou fora de garantia
                                        if ($("input[name=tipo]:checked").val() != null && $("input[name=tipo]:checked").val() != '') {
                                            var tipo = $("input[name=tipo]:checked").val();

                                            filtros.push("tipo="+tipo);
                                        }
                                    <?php
                                    }

                                    if (in_array($login_fabrica, array(169,170))) {
                                    ?>
                                        //inspetor
                                        if ($("#inspetor").val() != null && $("#inspetor").val() != '') {
                                            var inspetor = $("#inspetor").val();

                                            filtros.push("admin_sap="+inspetor);
                                        }

                                        //tipo_posto
                                        if ($("#tipo_posto").val() != null && $("#tipo_posto").val() != '') {
                                            var tipo_posto = $("#tipo_posto").val();

                                            filtros.push("tipo_posto="+tipo_posto);
                                        }

                                        //estado 
                                        if ($("#estado").val() != null  && $("#estado").val() != '') {
                                            var estado = $("#estado").val();

                                            filtros.push("estado_posto_autorizado="+estado);
                                        }
                                    <?php
                                    }
                                }

                                if ($areaAdmin) {
                                ?>
                                    filtros.push("data_inicial_01="+data_inicial);
                                    filtros.push("data_final_01="+data_final);
                                    window.open("pedido_parametros.php?"+filtros.join("&"));
                                <?php
                                } else {
                                ?>
                                    filtros.push("data_inicial="+data_inicial);
                                    filtros.push("data_final="+data_final);

                                    window.open("pedido_relacao.php?"+filtros.join("&"));
                                <?php
                                }
                                ?>
                            }
                        }
                    }
                }
            },
            series: [{
                name: "Status",
                colorByPoint: true,
                tooltip: {
                    pointFormat: "{name}<br />{point.y} Pedidos",
                    useHTML: true
                },
                data: <?=$grafico_pedido_series?>,
                dataLabels: {
                    enabled: true,
                    formatter: function() {
                        return this.point.name + " - " + Highcharts.numberFormat(parseFloat(this.percentage), 2, ",", ".") + "%";
                    }
                }
            }]
        });
    }

    if ($("#grafico_extrato").length > 0) {
        var grafico_valores_extrato = <?=$grafico_valores_extrato?>;

        <?php
        if ($login_fabrica == 158) {
        ?>
            grafico_valores_extrato.push({
                type: "pie",
                name: "Quantidade de Extratos",
                data: <?=$grafico_total_qtde_extrato?>,
                center: [150, 0],
                size: 50,
                showInLegend: false,
                dataLabels: {
                    enabled: true,
                    formatter: function() {
                        return this.point.name + " - " + Highcharts.numberFormat(parseFloat(this.percentage), 2, ",", ".") + "%";
                    }
                }
            });
        <?php
        }
        ?>

        $("#grafico_extrato").highcharts({
            title: {
                text: "Extratos gerados, <?=$interval_label?>"
            },
            xAxis: {
                categories: <?=$grafico_mes_categorias?>
            },
            yAxis: {
                minorTickInterval: "auto",
                minorTickLength: 0,
                min: 0,
                title: {
                    text: "Valor do Extrato"
                }
            },
            legend: {
                enabled: <?=($login_fabrica == 158) ? "true" : "false"?>
            },
            plotOptions: {
                column: {
                    dataLabels: {
                        enabled: true,
                        formatter: function() {
                            return "R$ " + Highcharts.numberFormat(parseFloat(this.y), 2, ",", ".");
                        }
                    }
                },
                series:{
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function() {
                                if (this.shapeType == "arc" || this.y == 0) {
                                    return false;
                                }

                                <?php
                                if ($areaAdmin) {
                                ?>
                                    var meses = {
                                        "Janeiro": 1,
                                        "Fevereiro": 2,
                                        "Março": 3,
                                        "Abril": 4,
                                        "Maio": 5,
                                        "Junho": 6,
                                        "Julho": 7,
                                        "Agosto": 8,
                                        "Setembro": 9,
                                        "Outubro": 10,
                                        "Novembro": 11,
                                        "Dezembro": 12
                                    };

                                    var filtros = ["btnacao=filtrar"];

                                    [mes, ano] = this.category.split("/");

                                    mes = meses[mes];

                                    filtros.push("data_mes="+mes);
                                    filtros.push("data_ano="+ano);

                                    <?php
                                    if ($pesquisa_periodo) {
                                    ?>
                                        filtros.push("data_inicial=<?=$data_inicial_pesquisa?>");
                                        filtros.push("data_final=<?=$data_final_pesquisa?>");
                                    <?php
                                    }

                                    if (in_array($login_fabrica, array(158))) {
                                    ?>
                                        var tipo = this.series.name;

                                        if (tipo != null && tipo.length > 0) {
                                            filtros.push("tipo_extrato="+tipo);
                                        }
                                    <?php
                                    }

                                    if (in_array($login_fabrica, array(169,170))) {
                                    ?>
                                        //inspetor
                                        if ($("#inspetor").val() != null && $("#inspetor").val() != '') {
                                            var inspetor = $("#inspetor").val();

                                            filtros.push("admin_sap="+inspetor);
                                        }

                                        //tipo_posto
                                        if ($("#tipo_posto").val() != null && $("#tipo_posto").val() != '') {
                                            var tipo_posto = $("#tipo_posto").val();

                                            filtros.push("tipo_posto="+tipo_posto);
                                        }

                                        //estado
                                        if ($("#estado").val() != null && $("#estado").val() != '') {
                                            var estado = $("#estado").val();

                                            filtros.push("estado="+estado);
                                        }
                                    <?php
                                    }
                                    ?>

                                    //posto
                                    if ($("#posto_id").val() != null && $("#posto_id").val() != '') {
                                        [codigo_posto, posto_nome] = $("#posto_codigo_nome").val().split("-");

                                        filtros.push("posto_codigo="+codigo_posto.trim());
                                    }

                                    window.open("extrato_consulta.php?"+filtros.join("&"));
                                <?php
                                } else {
                                ?>
                                    window.open("os_extrato_novo_lgr.php");
                                <?php
                                }
                                ?>
                            }
                        }
                    }
                }
            },
            series: grafico_valores_extrato
        });
    }

    <?php
    if (in_array($login_fabrica, array(169, 170))) {
    ?>
        var filtrarOs = new function() {
            var modal = $("#modal-os-filter");
            var tbody = $(modal).find("tbody");
            var os_lista = <?=json_encode($os_lista)?>;

            this.show = function(acao, filtro, tempo, status, tipo_atendimento, atrasado) {
                clearTbody();

                var label = [];

                if (typeof tempo != "undefined") {
                    switch(tempo) {
                        case 3:
                            label.push("Tempo: 0-3 Dias");
                            break;

                        case 10:
                            label.push("Tempo: 4-10 Dias");
                            break;

                        case 20:
                            label.push("Tempo: 11-20 Dias");
                            break;

                        case 30:
                            label.push("Tempo: 21-30 Dias");
                            break;

                        case 31:
                            label.push("Tempo: > 30 Dias");
                            break;

                        default:
                            label.push("Tempo: Total");
                            break;
                    }
                }

                if (acao == "inspetor") {
                    label.push("Inspetor: "+filtro);
                }

                if (acao == "posto") {
                    label.push("Posto Autorizado: "+filtro);
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

                $(modal).find(".modal-title").text(label.join(" - "));

                os_lista.forEach(function(os, i) {
                    if (acao == "inspetor" && os.inspetor != filtro) {
                        return;
                    } else if (acao == "posto" && os.posto != filtro) {
                        return;
                    } else if (typeof tempo != "undefined" && tempo != "total" && os.tempo != tempo) {
                        return;
                    } else if (typeof status != "undefined" && status != os.status) {
                        return;
                    } else if (typeof tipo_atendimento != "undefined" && tipo_atendimento != os.tipo_atendimento) {
                        return;
                    } else if (acao == "todos" && os.inspetor == "Sem Inspetor") {
                        return;
                    } else if (typeof atrasado != "undefined" && atrasado == true && os.atrasada != true) {
                        return;
                    }

                    $(tbody).append("\
                        <tr>\
                            <td><a href='os_press.php?os="+os.os+"' target='_blank' >"+os.sua_os+"</a></td>\
                            <td>"+os.data_digitacao+"</td>\
                            <td>"+os.status+"</td>\
                            <td>"+os.posto+"</td>\
                            <td>"+os.tipo_atendimento+"</td>\
                            <td>"+os.linha+"</td>\
                            <td>"+os.familia+"</td>\
                            <td>"+os.produto_referencia+" - "+os.produto_descricao+"</td>\
                            <td>"+os.primeira_data_agendamento+"</td>\
                            <td>"+os.data_confirmacao+"</td>\
                            <td>"+os.data_reagendamento+"</td>\
                            <td>"+os.inspetor+"</td>\
                        </tr>\
                    ");
                });

                $.dataTableLoad({
                    table: ".table-modal-os",
                    type: "custom",
                    config: ["info", "paginacao", "pesquisa"]
                });

                $(modal).modal("show");
            };

            var clearTbody = function() {
                if (dataTableGlobal) {
                    dataTableGlobal.fnDestroy();
                }
                $(tbody).find("tr").remove();
            };
        };

        $(".explodir-os").on("click", function() {
            var acao             = $(this).data("acao");
            var filtro           = $(this).data("filtro");
            var tempo            = $(this).data("tempo");
            var status           = $(this).data("status");
            var tipo_atendimento = $(this).data("tipo-atendimento");
            var atrasado         = $(this).data("atrasado");

            filtrarOs.show(acao, filtro, tempo, status, tipo_atendimento, atrasado);
        });

        $(".carrega-os-pa").on("click", function() {
            var t = $(this).parents("table");
            $(t).find("tbody > tr").css({ display: "table-row" });
            $(t).find("tfoot").css({ display: "none" });
        });

        $(".download-arquivo-csv").on("click", function() {
            var a = $(this).data("arquivo");
            window.open(a);
        });
    <?php
    }
}
?>

</script>

<?php
include "rodape.php";
?>
