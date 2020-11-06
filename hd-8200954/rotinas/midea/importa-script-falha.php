<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

pg_query($con, "BEGIN");

foreach (explode("\n", file_get_contents("midea_script_falha.csv")) as $value) {
    if (empty(str_replace("\n", "", $value))) {
        continue;
    }

    list(
        $defeito_reclamado_codigo,
        $familia_descricao,
        $json_script,
        $json_execucao
    ) = explode(",", $value);

    $res = pg_query($con, "
        SELECT familia FROM tbl_familia WHERE fabrica = 169 AND descricao = '$familia_descricao'
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $familia_id = $res["familia"];

    if (!strlen($familia_id)) {
        exit("erro");
        continue;
    }

    $res = pg_query($con, "
        SELECT dr.defeito_reclamado FROM tbl_diagnostico d INNER JOIN tbl_defeito_reclamado dr ON dr.defeito_reclamado = d.defeito_reclamado INNER JOIN tbl_familia f ON f.familia = d.familia WHERE d.fabrica = 169 AND f.familia = $familia_id AND dr.codigo = '$defeito_reclamado_codigo'
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $defeito_reclamado_id = $res["defeito_reclamado"];

    if (!strlen($defeito_reclamado_id)) {
        exit("erro");
        continue;
    }

    $res = pg_query($con, "
        SELECT * FROM tbl_script_falha WHERE fabrica = 169 AND familia = {$familia_id} AND defeito_reclamado = {$defeito_reclamado_id}
    ");
    echo pg_last_error();

    if (pg_num_rows($res) > 0) {
        continue;
    }

    pg_query($con, "
        INSERT INTO tbl_script_falha (fabrica, defeito_reclamado, familia, json_script, json_execucao_script)
        VALUES (169, $defeito_reclamado_id, $familia_id, '$json_script', '$json_execucao')
    ");
    echo pg_last_error();
}

if (strlen(pg_last_error()) > 0) {
    pg_query($con, "ROLLBACK");
} else {
    pg_query($con, "COMMIT");
}
