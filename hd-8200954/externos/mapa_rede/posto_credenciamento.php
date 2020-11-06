<?php

header('Content-type: application/json; charset=iso-8859-1');

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include 'mlg_funciones.php';

if (empty($_POST['token']) or empty($_POST['posto'])) {
    die('Acesso negado');
}

$token = $_POST['token'];
$posto = $_POST['posto'];

$sql = "SELECT posto, fabrica FROM tbl_posto_fabrica 
        WHERE posto = $posto
        AND md5('#-*-@' || fabrica || '@-*-!') = '$token'";
$qry = pg_query($con, $sql);

if (pg_num_rows($qry) == 0) {
    die('Acesso negado');
}

$posto = pg_fetch_result($qry, 0, 'posto');
$fabrica = pg_fetch_result($qry, 0, 'fabrica');

$sql = "SELECT to_char(data,'DD/MM/YYYY') AS data, texto, dias, status
        FROM tbl_credenciamento
        WHERE posto = $posto AND fabrica = $fabrica
        ORDER BY tbl_credenciamento.data DESC";
$res = pg_query($con, $sql);

$result = array();

if (pg_num_rows($res) > 0) {

    $result['status'] = 'true'; 

    while ($fetch = pg_fetch_assoc($res)) {
        $data = $fetch['data'];
        $texto = ($fetch['texto']) ? utf8_encode($fetch['texto']) : '';
        $dias = ($fetch['dias']) ? $fetch['dias'] : '';
        $status = $fetch['status'];

        $result['result'][] = array('data' => $data, 'texto' => $texto, 'dias' => $dias, 'status' => $status);
    }

} else {
    $result['status'] = 'false';
}

echo json_encode($result);
