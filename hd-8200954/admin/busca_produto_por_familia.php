<?php

header("Content-Type: application/json");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if ($_GET['familia']) {
    $familia = (int) $_GET['familia'];

    $sql = "SELECT produto, referencia, descricao FROM tbl_produto where familia = $familia and fabrica_i = $login_fabrica and ativo";
    $qry = pg_query($con, $sql);


    $arr_result = array();

    while ($fetch = pg_fetch_assoc($qry)) {
        $arr_result[] = array(
            'id' => $fetch['produto'],
            'produto' => utf8_encode($fetch['referencia'] . ' - ' . $fetch['descricao']),
        );
    }

    echo json_encode($arr_result);

}

