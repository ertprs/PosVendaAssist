<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

pg_query($con, "BEGIN");
foreach (explode("\n", file_get_contents(dirname(__FILE__)."/midea_classificacao_providencia.csv")) as $providencia) {
    if (empty(str_replace("\n", "", $providencia))) {
        continue;
    }

    list(
        $classificacao_descricao,
        $classificacao_envia_email,
        $classificacao_script_falha,
        $providencia_descricao,
        $providencia_tipo_registro,
        $providencia_texto_email,
        $providencia_texto_sms,
        $providencia_prazo_dias,
        $providencia_abre_os,
        $providencia_os_obrigatoria,
        $aba_nome
    ) = explode(",", $providencia);

    $classificacao_envia_email  = (trim(!strlen($classificacao_envia_email))) ? "f" : trim($classificacao_envia_email);
    $classificacao_script_falha = (trim(!strlen($classificacao_script_falha))) ? "f" : trim($classificacao_script_falha);
    $providencia_tipo_registro  = (trim(!strlen($providencia_tipo_registro))) ? "null" : trim($providencia_tipo_registro);
    $providencia_prazo_dias     = (trim(!strlen($providencia_prazo_dias))) ? "null" : trim($providencia_prazo_dias);
    $providencia_abre_os        = (trim(!strlen($providencia_abre_os))) ? "f" : trim($providencia_abre_os);
    $providencia_os_obrigatoria = (trim(!strlen($providencia_os_obrigatoria))) ? "f" : trim($providencia_os_obrigatoria);

    $res = pg_query($con, "
        SELECT hd_classificacao FROM tbl_hd_classificacao WHERE fabrica = 169 AND lower(descricao) = lower('{$classificacao_descricao}')
    ");
    echo pg_last_error();

    if (pg_num_rows($res) == 0) {
        $res = pg_query($con, "
            INSERT INTO tbl_hd_classificacao (fabrica, descricao, envia_email, script_falha)
            VALUES (169, '$classificacao_descricao', '$classificacao_envia_email', '$classificacao_script_falha')
            RETURNING hd_classificacao
        ");
        echo pg_last_error();
    }

    $res = pg_fetch_assoc($res);

    $classificacao_id = $res["hd_classificacao"];

    if (!strlen($classificacao_id)) {
	exit("erro classificacao");
        continue;
    }

    $res = pg_query($con, "
        SELECT natureza FROM tbl_natureza WHERE fabrica = 169 AND lower(fn_retira_especiais(nome)) = lower(fn_retira_especiais('{$aba_nome}'))
    ");
    echo pg_last_error();

    $res = pg_fetch_assoc($res);

    $aba_id = $res["natureza"];

    if (!strlen($aba_id)) {
	exit("erro natureza $aba_nome");
        continue;
    }

    $res = pg_query($con, "
        SELECT * FROM tbl_hd_motivo_ligacao WHERE fabrica = 169 AND descricao = '{$providencia_descricao}' AND hd_classificacao = {$classificacao_id} AND natureza = {$aba_id}
    ");
    echo pg_last_error();

    if (pg_num_rows($res) > 0) {
        continue;
    }

    pg_query($con, "
        INSERT INTO tbl_hd_motivo_ligacao (fabrica, hd_classificacao, natureza, descricao, tipo_registro, texto_email, texto_sms, prazo_dias, abre_os, os_obrigatoria)
        VALUES (169, $classificacao_id, $aba_id, '$providencia_descricao', '$providencia_tipo_registro', '$providencia_texto_email', '$providencia_texto_sms', $providencia_prazo_dias, '$providencia_abre_os', '$providencia_os_obrigatoria')
    ");
    echo pg_last_error();
}

if (strlen(pg_last_error()) > 0) {
    pg_query($con, "ROLLBACK");
} else {
    pg_query($con, "COMMIT");
}
