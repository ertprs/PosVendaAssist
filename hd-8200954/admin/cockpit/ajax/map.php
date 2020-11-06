<?php
require "../../dbconfig.php";
require "../../includes/dbconnect-inc.php";
require "../../autentica_admin.php";

use Posvenda\Cockpit;
use Posvenda\TcMaps;

$tcMaps = new TcMaps($login_fabrica, $con);

function distance($client_location, $technical_location) {
    global $tcMaps;

    try {
        $route = $tcMaps->route($client_location, $technical_location);
        $ida = $route["total_km"];

        $route = $tcMaps->route($technical_location, $client_location);
        $volta = $route["total_km"];

        return array(
            "ida"   => $ida,
            "volta" => $volta,
        );
    } catch(\Exception $e) {
        return array(
            "ida"   => 0,
            "volta" => 0
        );
    }
}

if ($_GET["ajax_technical_distance"]) {
    $destiny_lat   = $_GET["destiny_lat"];
    $destiny_lng   = $_GET["destiny_lng"];
    $technical_lat = $_GET["technical_lat"];
    $technical_lng = $_GET["technical_lng"];

    $distance = distance("{$destiny_lat},{$destiny_lng}", "{$technical_lat},{$technical_lng}");

    if (empty($distance["ida"]) || empty($distance["volta"])) {
        exit(json_encode(array("error" => true)));
    } else {
        exit(json_encode(array(
            "success"            => true, 
            "distance"           => number_format($distance["ida"], 2, ".", ""),
            "returning_distance" => number_format($distance["volta"], 2, ".", "")
        )));
    }
}

if ($_GET["ajax_technical_nearest"]) {
    $lat                 = $_GET["lat"];
    $lng                 = $_GET["lng"];
    $zip_code            = $_GET["zip_code"];
    $call_type           = $_GET["call_type"];
    $call_type_warranty  = $_GET["call_type_warranty"];
    $cliente_id          = $_GET["cliente_id"];
    $product             = $_GET["product"];
    $technical_id        = $_GET["technical_id"];
    $distribution_center = $_GET["distribution_center"];

    if (!strlen($lat) || !strlen($lng)) {
        if (!isset($_GET["search"])) {
            exit(json_encode(array("error" => utf8_encode("Latitude ou Longitude não informada"))));
        } else {
            exit(json_encode(array(array("error" => utf8_encode("Latitude ou Longitude não informada")))));
        }
    }

    if ($_GET["search"] == "autocomplete" || empty($technical_id)) {
        if (!strlen($product)) {
            exit(json_encode(array("error" => utf8_encode("Produto não informado"))));
        }

        $inner_join_product_line = "
            INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
            INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$login_fabrica}
            INNER JOIN tbl_produto ON tbl_produto.linha = tbl_linha.linha AND tbl_produto.fabrica_i = {$login_fabrica}
        ";

        $where_product_line = "
            AND tbl_produto.produto = {$product}
        ";

        $cockpit = new Cockpit($login_fabrica);

        $product_line = $cockpit->getProdutoById($product, true);

        if ($product_line["linha_nome"] != "REFRIGERADOR") {
            $call_type_warranty = "";
        }

        $call_type = $cockpit->getTipoAtendimentoKOF($call_type, $call_type_warranty);

        if (!empty($call_type)) {
            $inner_join_call_type = "
                INNER JOIN tbl_posto_tipo_atendimento ON tbl_posto_tipo_atendimento.posto = tbl_posto_fabrica.posto AND tbl_posto_tipo_atendimento.fabrica = {$login_fabrica}
            ";
            $where_call_type = "
                AND tbl_posto_tipo_atendimento.tipo_atendimento = {$call_type['tipo_atendimento']}
            ";
        } else {
            exit(json_encode(array("error" => utf8_encode("Tipo de Atendimento não informado"))));
        }

        if (!strlen($distribution_center)) {
            exit(json_encode(array("error" => utf8_encode("Centro Distribuidor não informado"))));
        }

        $inner_join_distribution_center = "
            INNER JOIN tbl_distribuidor_sla_posto unidade_negocio_posto ON unidade_negocio_posto.posto = tbl_posto_fabrica.posto AND unidade_negocio_posto.fabrica = {$login_fabrica}
            INNER JOIN tbl_distribuidor_sla unidade_negocio ON unidade_negocio.distribuidor_sla =unidade_negocio_posto.distribuidor_sla AND unidade_negocio.fabrica = {$login_fabrica}
            INNER JOIN tbl_distribuidor_sla centro_distribuidor ON centro_distribuidor.unidade_negocio = unidade_negocio.unidade_negocio AND centro_distribuidor.fabrica = {$login_fabrica}
        ";
        $where_distribution_center = "
            AND centro_distribuidor.centro = '{$distribution_center}'
        ";
    } else if (!empty($technical_id)) {
        $where_id = "AND tbl_tecnico.tecnico = {$technical_id}";
    }

    if ($_GET["search"] == "autocomplete") {
        $term   = $_GET["term"];
        $ilike  = "AND (fn_retira_especiais(tbl_posto.nome) ILIKE '%{$term}%' OR tbl_posto.cnpj ILIKE '%{$term}%')";

        if (!empty($not_in)) {
            $not_in = "AND tbl_tecnico.tecnico NOT IN (".implode(", ", $_GET["not_in"]).")";
        }
    } else if (empty($technical_id)) {
        if (!empty($zip_code)) {
            $zip_code = preg_replace("/\D/", "", $zip_code);

            $inner_join_zip_code = "INNER JOIN tbl_posto_cep_atendimento ON tbl_posto_cep_atendimento.posto = tbl_posto_fabrica.posto AND tbl_posto_cep_atendimento.fabrica = {$login_fabrica}";
            $where_zip_code      = "AND tbl_posto_cep_atendimento.cep_inicial = '{$zip_code}'";
        } else {
            exit(json_encode(array("error" => utf8_encode("CEP do Cliente não informado"))));
        }

        $cockpit = new Cockpit($login_fabrica);

        $client = $cockpit->getClienteKOF($client_id);

        if ($client && strlen($client["grupo_cliente"]) > 0) {
            $inner_join_client_group = "
                INNER JOIN tbl_posto_grupo_cliente ON tbl_posto_grupo_cliente.posto = tbl_posto_fabrica.posto AND tbl_posto_grupo_cliente.fabrica = {$login_fabrica}
            ";
            $where_client_group = "
                AND tbl_posto_grupo_cliente.grupo_cliente = {$client['grupo_cliente']}
            ";
        }

        $order_by = "ORDER BY distance ASC";
        $limit    = "LIMIT 5";
    }

    $sql = "SELECT DISTINCT
                tbl_tecnico.tecnico AS id,
                tbl_posto.nome AS name,
                tbl_posto.cnpj,
                tbl_tipo_posto.tecnico_proprio AS internal_technical,
                tbl_tecnico.endereco AS address,
                tbl_tecnico.numero AS number,
                tbl_tecnico.bairro AS neighborhood,
                tbl_tecnico.cidade AS city,
                tbl_tecnico.estado AS state,
                tbl_tecnico.cep AS zip_code,
                tbl_tecnico.latitude,
                tbl_tecnico.longitude,
                (
                    111.045 * DEGREES(
                        ACOS(
                            COS(RADIANS({$lat}))
                            * COS(RADIANS(tbl_tecnico.latitude))
                            * COS(RADIANS(tbl_tecnico.longitude) - RADIANS({$lng}))
                            + SIN(RADIANS({$lat}))
                            * SIN(RADIANS(tbl_tecnico.latitude))
                        )
                    )
                ) AS distance,
                COALESCE(tbl_tecnico.qtde_atendimento, NULL, 0) AS maximum_amount,
                (
                    SELECT COUNT(*) 
                    FROM tbl_tecnico_agenda 
                    INNER JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica = {$login_fabrica}
                    WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica} 
                    AND tbl_tecnico_agenda.tecnico = tbl_tecnico.tecnico
                    AND tbl_os.finalizada IS NULL
                    AND tbl_os.excluida IS NOT TRUE
                ) AS calls
            FROM tbl_tecnico
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_tecnico.posto
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            {$inner_join_distribution_center}
            {$inner_join_product_line}
            {$inner_join_zip_code}
            {$inner_join_call_type}
            {$inner_join_client_group}
            WHERE tbl_tecnico.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            AND tbl_tipo_posto.posto_interno IS NOT TRUE
            AND (
                tbl_tecnico.latitude IS NOT NULL
                AND tbl_tecnico.longitude IS NOT NULL
                AND tbl_tecnico.nome IS NOT NULL
            )
            {$where_distribution_center}
            {$where_product_line}
            {$where_zip_code}
            {$where_call_type}
            {$where_client_group}
            {$where_id}
            {$ilike}
            {$not_in}
            {$order_by}
            {$limit}";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        $array_technical = array();

        if (!isset($_GET["search"])) {
            $technicals_locations = array();
        }

        $i = 0;

        while ($fetch = pg_fetch_object($qry)) {
            $array_technical[$i] = array(
                "id"                 => $fetch->id,
                "name"               => utf8_encode($fetch->name),
                "cnpj"               => utf8_encode($fetch->cnpj),
                "internal-technical" => ($fetch->internal_technical == "t") ? true : false,
                "address"            => utf8_encode($fetch->address),
                "number"             => $fetch->number,
                "neighborhood"       => utf8_encode($fetch->neighborhood),
                "city"               => utf8_encode($fetch->city),
                "state"              => $fetch->state,
                "zip-code"           => $fetch->zip_code,
                "latitude"           => (float) $fetch->latitude,
                "longitude"          => (float) $fetch->longitude,
                "maximum_amount"     => (int) $fetch->maximum_amount,
                "calls"              => (int) $fetch->calls,
            );

            if (!isset($_GET["search"]) && empty($technical_id)) {
                $distance = distance("{$lat},{$lng}", "{$fetch->latitude},{$fetch->longitude}");

                $array_technical[$i]["distance"]           = number_format($distance["ida"], 2, ".", "");
                $array_technical[$i]["returning_distance"] = number_format($distance["volta"], 2, ".", "");
            }

            $i++;
        }

        if (!isset($_GET["search"])) {
            exit(json_encode(array("success" => true, "result" => $array_technical)));
        } else {
            exit(json_encode($array_technical));
        }
    } else {
        if (!isset($_GET["search"])) {
            exit(json_encode(array(
                "error" => utf8_encode("Não foi encontrado nenhum Técnico"), 
                "technical_not_found" => true
            )));
        } else {
            exit(json_encode(array(array("error" => utf8_encode("Não foi encontrado nenhum Técnico")))));
        }
    }
}

if ($_GET["ajax_set_current_technical"]) {
    if (empty($ticket)) {
        exit(json_encode(array("error" => utf8_encode("Ticket não informado"))));
    }

    $lat = $_GET["lat"];
    $lng = $_GET["lng"];

    $sql = "
        SELECT 
            tbl_tecnico_agenda.tecnico, 
            COALESCE(tbl_tecnico.qtde_atendimento, NULL, 0) AS maximum_amount, 
            tbl_tipo_posto.posto_interno AS internal, 
            tbl_tecnico.longitude,
            (
                111.045 * DEGREES(
                    ACOS(
                        COS(RADIANS({$lat}))
                        * COS(RADIANS(tbl_tecnico.latitude))
                        * COS(RADIANS(tbl_tecnico.longitude) - RADIANS({$lng}))
                        + SIN(RADIANS({$lat}))
                        * SIN(RADIANS(tbl_tecnico.latitude))
                    )
                )
            ) AS distance
        FROM tbl_hd_chamado_cockpit
        INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_cockpit.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
        INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
        INNER JOIN tbl_os ON tbl_os.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_os.fabrica = {$login_fabrica}
        INNER JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = {$login_fabrica}
        INNER JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}
        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_tecnico.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
        WHERE tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
        AND tbl_hd_chamado_cockpit.hd_chamado_cockpit = {$ticket}
    ";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        $return = array(
            "success"        => true, 
            "technical"      => pg_fetch_result($qry, 0, "tecnico"), 
            "maximum_amount" => pg_fetch_result($qry, 0, "maximum_amount"), 
            "internal"       => pg_fetch_result($qry, 0, "internal"),
            "distance"       => pg_fetch_result($qry, 0, "distance")
        );
    } else {
        $return = array("success" => false);
    }

    exit(json_encode($return));
}
