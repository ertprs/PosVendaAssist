<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

pg_query($con, "BEGIN");
foreach (explode("\n", file_get_contents(dirname(__FILE__)."/midea_usuarios.csv")) as $usuario) {
    if (empty($usuario)) {
        continue;
    }

    list(
        $nome_completo,
        $login,
        $senha,
        $email,
        $fone,
        $privilegios,
        $help_desk_supervisor,
        $pais,
        $callcenter_supervisor,
        $participa_agenda,
        $admin_sap,
        $atendente_callcenter,
        $responsavel_postos,
        $responsavel_ti,
        $live_help
    ) = explode(",", $usuario);

    $fone                  = (trim(!strlen($fone))) ? "null" : trim($fone);
    $help_desk_supervisor  = (trim(!strlen($help_desk_supervisor))) ? "f" : trim($help_desk_supervisor);
    $callcenter_supervisor = (trim(!strlen($callcenter_supervisor))) ? "null" : trim($callcenter_supervisor);
    $participa_agenda      = (trim(!strlen($participa_agenda))) ? "null" : trim($participa_agenda);
    $admin_sap             = (trim(!strlen($admin_sap))) ? "null" : trim($admin_sap);
    $atendente_callcenter  = (trim(!strlen($atendente_callcenter))) ? "null" : trim($atendente_callcenter);
    $responsavel_postos    = (trim(!strlen($responsavel_postos))) ? "null" : trim($responsavel_postos);
    $responsavel_ti        = (trim(!strlen($responsavel_ti))) ? "null" : trim($responsavel_ti);
    $live_help             = (trim(!strlen($live_help))) ? "null" : trim($live_help);

    $sql = "SELECT * FROM tbl_admin WHERE fabrica = 169 AND login = '$login'";
    $res = pg_query($con, $sql);
    echo pg_last_error();

    if (pg_num_rows($res) > 0) {
        continue;
    }

    echo $insert = "
        INSERT INTO tbl_admin
        (fabrica, nome_completo, login, senha, email, fone, privilegios, help_desk_supervisor, pais, callcenter_supervisor, participa_agenda, admin_sap, atendente_callcenter, responsavel_postos, responsavel_ti, live_help)
        VALUES
        (169, '$nome_completo', '$login', '$senha', '$email', '$fone', '$privilegios', '$help_desk_supervisor', '$pais', '$callcenter_supervisor', '$participa_agenda', '$admin_sap', '$atendente_callcenter', '$responsavel_postos', '$responsavel_ti', '$live_help')
    ";
    $res = pg_query($con, $insert);
    echo pg_last_error();
}

if (strlen(pg_last_error()) > 0) {
    pg_query($con, "ROLLBACK");
} else {
    pg_query($con, "COMMIT");
}
