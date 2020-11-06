<?php

header("Content-Type: application/json");

if (empty($_GET['familia'])) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(array('erro' => 'Família inválida')) , "\n";
    exit;
}

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


$familia = $_GET['familia'];

if($familia <> "irc_global"){
    $familia = (int) $familia;
    $condFamilia = " AND tbl_produto.familia = $familia";
}

$sql = "SELECT DISTINCT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao
        FROM tbl_produto
        WHERE tbl_produto.fabrica_i = $login_fabrica
        $condFamilia
        ORDER BY tbl_produto.descricao";
        // echo $sql;exit;
$query = pg_query($con, $sql);

if (pg_num_rows($query) == 0) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(array('erro' => 'Nenhum resultado encontrado')) , "\n";
    exit;
}

$resultados = array();

if (isset($_GET['unico'])) {
    $sql = "SELECT DISTINCT tbl_produto.referencia_fabrica, count(tbl_produto.produto) AS qtde_familia
            FROM tbl_produto
            WHERE tbl_produto.fabrica_i = $login_fabrica
            $condFamilia
            GROUP BY tbl_produto.referencia_fabrica";
    $query = pg_query($con, $sql);

    if (pg_num_rows($query) == 0) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(array('erro' => 'Nenhum resultado encontrado')) , "\n";
        exit;
    }

    $resultados = array();

    while ($fetch = pg_fetch_array($query)) {
        $resultados[] = array('data' => utf8_encode($fetch['referencia_fabrica']), 'value' => utf8_encode($fetch['referencia_fabrica']) . ' - ' . $fetch['qtde_familia'] . ' produtos adicionados');
    }

} else {
    while ($fetch = pg_fetch_array($query)) {
        $resultados[] = array('data' => $fetch['produto'], 'value' => $fetch['referencia'] . ' - ' . utf8_encode($fetch['descricao']));
    }
}



echo json_encode($resultados);
