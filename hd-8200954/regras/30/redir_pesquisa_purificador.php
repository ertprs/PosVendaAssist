<?php
// 01/10/2010 HD 308656
if ($login_fabrica == 30) {
    $sql_frm     = "SELECT posto FROM tbl_pesquisa_purificador_esmaltec WHERE posto = $login_posto";
    $sql_frm_iso = "SELECT posto FROM tbl_pesquisa_purificador_iso      WHERE posto = $login_posto";

    // HD-2306475
    $res_frm = pg_query($con, $sql_frm);
    // pre_echo("$sql\nTotal: ".pg_num_rows($res_frm). ", posto " . pg_fetch_result($res_frm, 0, 0));
    if (pg_num_rows($res_frm) == 0) {
        include (APP_DIR . 'esmaltec_purif_form.php');
    }

    $res_frm_iso = pg_query($con, $sql_frm_iso);
    if (pg_num_rows($res_frm_iso) == 0) {
        include (APP_DIR . 'esmaltec_purif_iso_form.php');
    }
}


