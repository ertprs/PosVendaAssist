<?php

include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

if (!empty($_POST['del']) and $_POST['del'] === 'true') {
    if (empty($_POST['ref'])) {
        die('Referência não encontrada');
    }

    if (empty($_POST['token'])) {
        die('Token inválida');
    }

    $referencia = $_POST['ref'];
    $token      = $_POST['token'];

    $t = pg_query($con, "BEGIN");

    $sql = "DELETE FROM tbl_token_pedido WHERE token = '$token' AND referencia = '$referencia' AND fabrica = $login_fabrica";
    $qry = pg_query($con, $sql);

    $tuples = pg_affected_rows($qry);

    if ($tuples <> 1) {
        $t = pg_query($con, "ROLLBACK");
        exit;
    }

    $t = pg_query($con, "COMMIT");
    exit;
}
