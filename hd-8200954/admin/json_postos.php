<?php

header("Content-Type: application/json");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$sql = "SELECT posto, codigo_posto, nome FROM tbl_posto_fabrica
        JOIN tbl_posto USING(posto)
        WHERE fabrica = $login_fabrica
        AND credenciamento <> 'DESCREDENCIADO'
        ORDER BY nome";
$query = pg_query($con, $sql);

$arr_result = array();

while ($fetch = pg_fetch_assoc($query)) {
    $arr_result[] = array(
        'data' => $fetch['posto'],
        'value' => utf8_encode($fetch['codigo_posto'] . ' - ' . $fetch['nome']),
    );
}

echo json_encode($arr_result);

