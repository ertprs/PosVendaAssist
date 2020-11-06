<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

pg_query($con, "BEGIN");

foreach (explode("\n", file_get_contents(dirname(__FILE__)."/midea-blacklist.csv")) as $value) {
    if (empty(str_replace("\n", "", $value))) {
        continue;
    }

    list(
        $cep,
        $posto_um,
        $posto_dois
    ) = explode(";", $value);

    $posto_blacklist = 624161;

    $cep        = preg_replace("/\D/", "", trim($cep));
    $posto_um   = trim($posto_um);
    $posto_dois = trim($posto_dois);

    $res = pg_query($con, "
        SELECT posto FROM tbl_posto_fabrica WHERE fabrica = 169 AND UPPER(codigo_posto) = UPPER('{$posto_um}')
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $posto_um = $res["posto"];

    if (!strlen($posto_um)) {
        exit("erro");
    }

    $sql_blacklist = "
        SELECT * FROM tbl_posto_cep_atendimento WHERE fabrica = 169 AND posto = {$posto_blacklist} AND cep_inicial = '{$cep}' AND blacklist IS TRUE
    ";
    $res_blacklist = pg_query($con, $sql_blacklist);
    echo pg_last_error();

    if (!pg_num_rows($res_blacklist)) {
        pg_query($con, "
            INSERT INTO tbl_posto_cep_atendimento
            (fabrica, posto, cep_inicial, cep_final, blacklist)
            VALUES
            (169, {$posto_blacklist}, '{$cep}', '{$cep}', TRUE)
        ");
        echo pg_last_error();

        if (strlen(pg_last_error()) > 0) {
            exit("erro");
        }
    }

    $sql_cep_atendimento = "
        SELECT posto_cep_atendimento FROM tbl_posto_cep_atendimento WHERE fabrica = 169 AND posto = {$posto_um} AND cep_inicial = '{$cep}' AND blacklist IS NOT TRUE
    ";
    $res_cep_atendimento = pg_query($con, $sql_cep_atendimento);
    echo pg_last_error();

    if (!pg_num_rows($res_cep_atendimento)) {
        pg_query($con, "
            INSERT INTO tbl_posto_cep_atendimento
            (fabrica, posto, cep_inicial, cep_final, blacklist)
            VALUES
            (169, {$posto_um}, '{$cep}', '{$cep}', FALSE)
        ");
        echo pg_last_error();

        if (strlen(pg_last_error()) > 0) {
            exit("erro");
        }
    }

    if (!empty($posto_dois)) {
        $res = pg_query($con, "
            SELECT posto FROM tbl_posto_fabrica WHERE fabrica = 169 AND UPPER(codigo_posto) = UPPER('{$posto_dois}')
        ");
        echo pg_last_error();

        $res = pg_fetch_assoc($res);

        $posto_dois = $res["posto"];

        if (!strlen($posto_dois)) {
            exit("erro");
        }

        $sql_cep_atendimento = "
            SELECT posto_cep_atendimento FROM tbl_posto_cep_atendimento WHERE fabrica = 169 AND posto = {$posto_dois} AND cep_inicial = '{$cep}' AND blacklist IS NOT TRUE
        ";
        $res_cep_atendimento = pg_query($con, $sql_cep_atendimento);
        echo pg_last_error();

        if (!pg_num_rows($res_cep_atendimento)) {
            pg_query($con, "
                INSERT INTO tbl_posto_cep_atendimento
                (fabrica, posto, cep_inicial, cep_final, blacklist)
                VALUES
                (169, {$posto_dois}, '{$cep}', '{$cep}', FALSE)
            ");
            echo pg_last_error();

            if (strlen(pg_last_error()) > 0) {
                exit("erro");
            }
        }
    }
}

if (strlen(pg_last_error()) > 0) {
    pg_query($con, "ROLLBACK");
} else {
    pg_query($con, "COMMIT");
}

