<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

header("Content-type: application/json; charset=UTF-8");

if (empty($_GET['posto'])) {
    die('{"erro": "Filtro requerido: posto"}');
}

$posto_codigo = addslashes($_GET['posto']);

$sql = "SELECT credenciamento FROM tbl_posto_fabrica 
    WHERE codigo_posto = '{$posto_codigo}' AND fabrica = $login_fabrica";
$qry = pg_query($con, $sql);

if (pg_num_rows($qry) == 0) {
    die('{"erro": "Posto não encontrado"}');
}

$credenciamento = pg_fetch_result($qry, 0, 'credenciamento');
$ret = array(
    'posto' => $posto_codigo,
    'credenciamento' => $credenciamento
);

die(json_encode($ret));
