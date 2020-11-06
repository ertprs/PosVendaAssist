<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

pg_query($con, "BEGIN");

foreach (explode("\n", file_get_contents(dirname(__FILE__)."/midea_supervisor_atendente.csv")) as $value) {
    if (empty(str_replace("\n", "", $value))) {
        continue;
    }

    list(
        $supervisor_login,
        $atendente_login
    ) = explode(",", $value);

    $supervisor_login = trim($supervisor_login);
    $atendente_login = trim($atendente_login);

    $res = pg_query($con, "
        SELECT admin FROM tbl_admin WHERE fabrica = 169 AND UPPER(login) = UPPER('{$supervisor_login}')
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $supervisor_admin_id = $res["admin"];

    if (!strlen($supervisor_admin_id)) {
        exit("erro");
        continue;
    }

    $res = pg_query($con, "
        SELECT admin FROM tbl_admin WHERE fabrica = 169 AND UPPER(login) = UPPER('{$atendente_login}')
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $atendente_admin_id = $res["admin"];

    if (!strlen($atendente_admin_id)) {
        exit("erro");
        continue;
    }

    $res = pg_query($con, "
        SELECT * FROM tbl_supervisor_atendente WHERE fabrica = 169 AND supervisor = {$supervisor_admin_id} AND atendente = {$atendente_admin_id}
    ");
    echo pg_last_error();

    if (pg_num_rows($res) > 0) {
        continue;
    }

    pg_query($con, "
        INSERT INTO tbl_supervisor_atendente (fabrica, supervisor, atendente)
        VALUES (169, $supervisor_admin_id, $atendente_admin_id)
    ");
    echo pg_last_error();
}

if (strlen(pg_last_error()) > 0) {
    pg_query($con, "ROLLBACK");
} else {
    pg_query($con, "COMMIT");
}
