<?php
// HD 2824422
$sql = "SELECT comunicado
          FROM tbl_comunicado
         WHERE tipo = 'Contrato'
           AND fabrica = $login_fabrica";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
    $sqlContrato = "
        SELECT tbl_comunicado_posto_blackedecker.posto
          FROM tbl_comunicado_posto_blackedecker
          JOIN tbl_comunicado    ON tbl_comunicado.comunicado = tbl_comunicado_posto_blackedecker.comunicado
                                AND tbl_comunicado.fabrica    = {$login_fabrica}
          JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_comunicado_posto_blackedecker.posto
                                AND tbl_posto_fabrica.fabrica = {$login_fabrica}
         WHERE tbl_comunicado_posto_blackedecker.posto   = {$login_posto}
           AND tbl_comunicado_posto_blackedecker.fabrica = {$login_fabrica}
           AND tbl_posto_fabrica.credenciamento         IN('CREDENCIADO','EM DESCREDENCIAMENTO')
           AND tbl_comunicado.tipo                       = 'Contrato'
";
    $resContrato = pg_query($con, $sqlContrato);

    if (pg_num_rows($resContrato) == 0) {
        header('Location: http://www.telecontrol.com.br/');
        exit;
    }
}
