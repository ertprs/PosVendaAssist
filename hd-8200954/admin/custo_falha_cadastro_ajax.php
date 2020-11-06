<?php

function checkPost($post) {
	if (!isset($_POST["$post"]{0})) {
		echo '<span style="color: #FF0000">Erro ao gravar!</span>';
		exit(1);
	}
}

function setPostVar($post) {
	return $_POST["$post"];
}

checkPost("mes");
checkPost("ano");
checkPost("familia");
checkPost("cfe");
checkPost("qtde_produto_produzido");

$custo_falha = setPostVar("custo_falha");
$mes = setPostVar("mes");
$ano = setPostVar("ano");
$familia = setPostVar("familia");
$regiao = setPostVar("regiao");
$cfe = str_replace(',', '.', setPostVar("cfe"));
$qtde_produto_produzido = setPostVar("qtde_produto_produzido");
$produto = setPostVar("produto");
$linha = setPostVar("linha");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (empty($regiao)) {
    $regiao = 'NULL';
    $cond_regiao = '';
} else {
    $cond_regiao = " AND regiao = $regiao ";
}

$update_produto = '';
$insert_produto_campo = '';
$insert_produto_value = '';

if (!empty($produto)) {
    $update_produto = "AND produto = $produto";
    $insert_produto_campo = ", produto";
    $insert_produto_value = ", $produto";
}

if (!empty($custo_falha)) {
	$sql = "UPDATE tbl_custo_falha SET 	cfe = $cfe, qtde_produto_produzido = $qtde_produto_produzido
				WHERE custo_falha = $custo_falha
				AND familia = $familia
                $cond_regiao
                $update_produto
				AND mes = $mes
				AND ano = $ano
				AND fabrica = $login_fabrica";
} else {
	$sql = "INSERT INTO tbl_custo_falha (mes, ano, familia, regiao, cfe, qtde_produto_produzido, fabrica {$insert_produto_campo}) VALUES ($mes, $ano, $familia, $regiao, $cfe, $qtde_produto_produzido, $login_fabrica {$insert_produto_value}) RETURNING custo_falha";
}


$qry = pg_query($con, $sql);

if (empty($custo_falha)) {
    $custo_falha = pg_fetch_result($qry, 0, 'custo_falha');
}

if (!pg_last_error()) {
	echo '<span style="color: #008000"><input type="hidden" id="custo_falha_atualizado_' . $linha . '" value="' . $custo_falha . '">Gravado com sucesso!</span>';
} else {
    echo '<span style="color: #FF0000">Erro ao gravar!</span>';
}

