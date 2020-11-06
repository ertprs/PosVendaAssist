<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

pg_query($con, "BEGIN");

foreach (explode("\n", file_get_contents(dirname(__FILE__)."/midea_atendente_origem.csv")) as $value) {
    if (empty(str_replace("\n", "", $value))) {
        continue;
    }

    list(
        $admin_login,
        $origem_descricao
    ) = explode(",", $value);

    $admin_login = trim($admin_login);
    $origem_descricao = trim($origem_descricao);

    $res = pg_query($con, "
        SELECT admin FROM tbl_admin WHERE fabrica = 169 AND upper(login) = upper('{$admin_login}')
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $admin_id = $res["admin"];

    if (!strlen($admin_id)) {
        exit("erro");
        continue;
    }

    $res = pg_query($con, "
        SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = 169 AND upper(descricao) = upper('{$origem_descricao}')
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $origem_id = $res["hd_chamado_origem"];

    if (!strlen($origem_id)) {
        exit("erro");
        continue;
    }

    $res = pg_query($con, "
        SELECT * FROM tbl_hd_origem_admin WHERE fabrica = 169 AND admin = {$admin_id} AND hd_chamado_origem = {$origem_id}
    ");
    echo pg_last_error();

    if (pg_num_rows($res) > 0) {
        continue;
    }

    pg_query($con, "
        INSERT INTO tbl_hd_origem_admin (fabrica, admin, hd_chamado_origem)
        VALUES (169, $admin_id, $origem_id)
    ");
    echo pg_last_error();
}

if (strlen(pg_last_error()) > 0) {
    pg_query($con, "ROLLBACK");
} else {
    pg_query($con, "COMMIT");
}
