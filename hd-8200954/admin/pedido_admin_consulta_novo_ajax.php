<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="auditoria";
$layout_menu = 'auditoria';
include "funcoes.php";

$pedido_item = intval($_GET["pedido_item"]);

if (strlen($pedido_item) > 0) {
	$sql = "
	SELECT
	tbl_pedido_item.pedido_item

	FROM
	tbl_pedido_item
	JOIN tbl_pedido USING(pedido)

	WHERE
	tbl_pedido_item.pedido_item=$pedido_item
	AND tbl_pedido.fabrica=$login_fabrica
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
	}
	else {
		$msg_erro = "Item do pedido não encontrado";
	}
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
	die;
}

switch($acao) {
	case "pesquisaros":
		$sql = "
		SELECT
		tbl_os.os,
		tbl_os.sua_os,
		tbl_os_item.qtde

		FROM
		tbl_os_item
		JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
		JOIN tbl_os ON tbl_os_produto.os=tbl_os.os

		WHERE
		tbl_os_item.pedido_item=$pedido_item
		";
		$res = pg_query($con, $sql);

		$num_rows = pg_num_rows($res);
		$msg = "<table><tr><td>OS</td><td>Qtde</td></tr>";
		
		for ($i = 0; $i < $num_rows; $i++) {
			extract(pg_fetch_array($res));

			$msg .= "<tr><td><a href=os_press.php?os=$os target=_blank>$sua_os</a></td><td>$qtde</td></tr>";
		}

		$msg .= "</table>";

		if ($num_rows == 0) {
			$msg = "Nenhuma OS encontrada";
		}
	break;

	default:
		$msg_erro = "Opção de ação inválida";
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
}
elseif (strlen($msg)) {
	echo "$acao|sucesso|$msg";
}

?>