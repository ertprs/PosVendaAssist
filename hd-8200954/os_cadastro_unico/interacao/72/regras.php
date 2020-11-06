<?php
$sql = " SELECT AD.admin, nome_completo, PF.admin_sap, AD.email, PF.posto
           FROM tbl_admin         AS AD
      LEFT JOIN tbl_posto_fabrica AS PF
             ON AD.admin = PF.admin_sap
            AND PF.fabrica = AD.fabrica
          WHERE AD.fabrica = 72
            AND AD.ativo
            AND AD.admin_sap";
if (!$areaAdmin) {
    $sql .= "\n            AND posto = $login_posto";
}
$sql .= "\n       ORDER BY 2";
$res = pg_query($con, $sql);

// Caso o posto não tenha definido um atendente, mostrar todos.
if (pg_num_rows($res) == 0) {
    pg_free_result($res);
    $sql = " SELECT admin, nome_completo, email
               FROM tbl_admin AS AD
              WHERE AD.fabrica = 72
                AND AD.ativo
                AND AD.admin_sap
           ORDER BY 2";
    $res = pg_query($con, $sql);
}

$fabrica_setor_email = [];
while ($atendente = pg_fetch_assoc($res)) {
    $fabrica_setor_email[$atendente['admin']] = [
        'nome'  => $atendente['nome_completo'],
        'email' => $atendente['email']
    ];
}

if ($areaAdmin === false) {
    $inputs_interacao = array('interacao_email_setor');
}

