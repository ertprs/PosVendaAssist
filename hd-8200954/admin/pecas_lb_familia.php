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

$sql = "SELECT DISTINCT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
        FROM tbl_peca
        JOIN tbl_lista_basica ON tbl_lista_basica.peca = tbl_peca.peca
        JOIN tbl_produto ON tbl_lista_basica.produto = tbl_produto.produto
        WHERE tbl_peca.fabrica = $login_fabrica
        $condFamilia
        ORDER BY tbl_peca.descricao";
$query = pg_query($con, $sql);

if (pg_num_rows($query) == 0) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(array('erro' => 'Nenhum resultado encontrado')) , "\n";
    exit;
}

$resultados = array();

while ($fetch = pg_fetch_array($query)) {
    $resultados[] = array('data' => $fetch['peca'], 'value' => $fetch['referencia'] . ' - ' . utf8_encode($fetch['descricao']));
}

echo json_encode($resultados);
