<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

pg_query($con, "BEGIN");

foreach (explode("\n", file_get_contents(dirname(__FILE__)."/midea_atendente_classificacao.csv")) as $value) {
    if (empty(str_replace("\n", "", $value))) {
        continue;
    }

    list(
        $admin_login,
        $classificacao_nome
    ) = explode(",", $value);

    $admin_login = trim($admin_login);
    $classificacao_nome = trim($classificacao_nome);

    $res = pg_query($con, "
        SELECT admin FROM tbl_admin WHERE fabrica = 169 AND UPPER(login) = UPPER('{$admin_login}')
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $admin_id = $res["admin"];

    if (!strlen($admin_id)) {
        exit("erro");
        continue;
    }

    $res = pg_query($con, "
        SELECT hd_classificacao FROM tbl_hd_classificacao WHERE fabrica = 169 AND UPPER(descricao) = UPPER('{$classificacao_nome}')
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $classificacao_id = $res["hd_classificacao"];

    if (!strlen($classificacao_id)) {
        exit("erro");
        continue;
    }

    /*if (!empty($cidade_nome)) {
        $res = pg_query($con, "
            SELECT cod_ibge FROM tbl_ibge WHERE cidade = '{$cidade_nome}' AND estado = {$admin_classificacao_estado}
        ");
        echo pg_last_error();

        if (pg_num_rows($res) > 0) {
            $res = pg_fetch_assoc($res);
            $cidade_id = $res["cod_ibge"];
        } else {
            exit("erro");
            continue;
        }
    } else {
        $cidade_id = "null";
    }

    $whereCidade = "";

    if ($admin_classificacao_estado != "null") {
        $whereEstado = "AND estado = {$admin_classificacao_estado}";
    } else {
        $whereEstado = "AND estado IS NULL";
    }

    if ($cidade_id != "null") {
        $whereCidade = "AND cod_ibge = {$cidade_id}";
    } else {
        $whereCidade = "AND cod_ibge IS NULL";
    }*/

    $res = pg_query($con, "
        SELECT * FROM tbl_admin_atendente_estado WHERE fabrica = 169 AND admin = {$admin_id} AND hd_classificacao = {$classificacao_id} {$whereEstado} {$whereCidade} 
    ");
    echo pg_last_error();

    var_dump(pg_num_rows($res));

    if (pg_num_rows($res) > 0) {
        continue;
    }

    pg_query($con, "
        INSERT INTO tbl_admin_atendente_estado (fabrica, admin, hd_classificacao)
        VALUES (169, $admin_id, $classificacao_id)
    ");
    echo pg_last_error();
}

if (strlen(pg_last_error()) > 0) {
    pg_query($con, "ROLLBACK");
} else {
    pg_query($con, "COMMIT");
}
