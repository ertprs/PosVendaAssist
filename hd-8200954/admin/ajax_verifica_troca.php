<?php
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
if (isset($_POST["admin"])) {
	$areaAdmin = true;
}

if ($areaAdmin === true) {
	include __DIR__.'/autentica_admin.php';
} else {
	 include __DIR__.'/../autentica_usuario.php';
}

if (isset($_POST["ajax_verifica_troca"])) {
	$referencia = $_POST["produto"];

	$sql = "SELECT produto 
			FROM tbl_produto
			WHERE referencia = '{$referencia}'
			AND fabrica_i = {$login_fabrica}";
	$res = pg_query($con, $sql);
	$produto_id = pg_fetch_result($res, 0, 'produto');

	$sql = "SELECT tbl_produto_troca_opcao.produto, produto_opcao 
			FROM tbl_produto_troca_opcao
			JOIN tbl_produto ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
			AND tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.ativo IS TRUE 
			WHERE tbl_produto_troca_opcao.produto = {$produto_id}";

	$res = pg_query($con, $sql);

	//pegar somente o primeiro registro para validar no if
	$produto       = pg_fetch_result($res, 0, 'produto');
	$produto_opcao = pg_fetch_result($res, 0, 'produto_opcao');

	$qtde_produtos_troca = pg_num_rows($res);

	if ($qtde_produtos_troca > 1 || ($qtde_produtos_troca == 1 && $produto != $produto_opcao)) {
		$exibir_shadowbox = true;
	} else {
		$exibir_shadowbox = false;
	}

	exit(json_encode(["mostra_shadowbox" => $exibir_shadowbox, "produto" => $produto_id]));
}
?>
