<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

header("Content-type: application/json; charset=UTF-8");

if (empty($_GET['ref'])) {
    header("HTTP/1.1 400 Bad Request");
    die('{"erro": "Filtro requerido: ref"}');
}

$referencia = addslashes($_GET['ref']);

$sql = "SELECT peca_de, peca_para 
    FROM tbl_depara
    WHERE de = '$referencia'
    AND fabrica = $login_fabrica
    ORDER BY digitacao DESC LIMIT 1";
$qry = pg_query($con, $sql);

if (pg_num_rows($qry) == 0) {
    header("HTTP/1.1 404 Not Found");
    die('{"erro": "Referência ' . $referencia . ' não encontrada"}');
}

$de = pg_fetch_result($qry, 0, 'peca_de');
$para = pg_fetch_result($qry, 0, 'peca_para');

$sql_de = "SELECT peca, referencia, descricao FROM tbl_peca WHERE peca = $de";
$qry_de = pg_query($con, $sql_de);

$sql_para = "SELECT peca, referencia, descricao FROM tbl_peca WHERE peca = $para";
$qry_para = pg_query($con, $sql_para);

$ret = array(
    'peca_de' => pg_fetch_result($qry_de, 0, 'peca'),
    'referencia_de' => pg_fetch_result($qry_de, 0, 'referencia'),
    'descricao_de' => pg_fetch_result($qry_de, 0, 'descricao'),
    'peca_para' => pg_fetch_result($qry_para, 0, 'peca'),
    'referencia_para' => pg_fetch_result($qry_para, 0, 'referencia'),
    'descricao_para' => pg_fetch_result($qry_para, 0, 'descricao'),
);

echo json_encode($ret);
