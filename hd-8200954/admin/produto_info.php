<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

header('Content-type: application/json');

function stripStr($str) {
    $strip = array('--', ';');
    $cl = strtoupper(str_replace($strip, '', $str));
    return $cl;
}

if (empty($_GET)) {
    die('{}');
}

if (!empty($_GET['type'])) {
    $type = $_GET['type'];
    switch ($type) {
        case 'valor_troca':
            if (empty($_GET['referencia'])) {
                die('{}');
            }

            $referencia = stripStr($_GET['referencia']);
            $sql = "SELECT valor_troca FROM tbl_produto WHERE UPPER(referencia) = '$referencia' AND fabrica_i = $login_fabrica";
            break;

    }

    $qry = pg_query($con, $sql);
    $return = '{}';

    if (pg_num_rows($qry) > 0) {
        $arr = array();
        while ($fetch = pg_fetch_assoc($qry)) {
            $arr[key($fetch)] = $fetch[key($fetch)];
        }
        $return = json_encode($arr);
    }

    echo $return;
}

