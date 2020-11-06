<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$faturamento_item = intval($_GET["faturamento_item"]);
$qtde = intval($_GET["qtde"]);
$nota = $_GET["nota"];
$serie = $_GET["serie"];
$acao = $_GET["acao"];

$sql = "BEGIN TRANSACTION";
$res = pg_query($con, $sql);

if ($faturamento_item) {
	$sql = "SELECT faturamento_item, qtde_quebrada FROM tbl_faturamento_item WHERE faturamento_item=$faturamento_item";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$qtde_quebrada = floatval(pg_result($res, 0, qtde_quebrada));

		$sql = "
		SELECT
		SUM(qtde_acerto) AS qtde_baixado
		
		FROM
		tbl_faturamento_baixa_divergencia
		
		WHERE faturamento_item=$faturamento_item
		";
		$res = pg_query($con, $sql);
		$qtde_baixado = floatval(pg_result($res, 0, qtde_baixado));
		
		if ($qtde > $qtde_quebrada - $qtde_baixada) {
			$msg_erro = "Não existem itens para serem baixados para o item desta nota fiscal";
		}
	}
	else {
		$msg_erro = "Item de NF não encontrado";
	}
}
else {
	$msg_erro = "Item de NF não informado";
}

if (strlen($msg_erro)) {
	$sql = "ROLLBACK TRANSACTION";
	$res = pg_query($con, $sql);

	echo "$acao|erro|$msg_erro";
	die;
}

switch($acao) {
	case "baixar":
		$sql = "
		INSERT INTO tbl_faturamento_baixa_divergencia (
		faturamento_item,
		qtde_acerto,
		nota_fiscal,
		serie,
		data
		)

		VALUES (
		$faturamento_item,
		$qtde,
		'$nota',
		'$serie',
		NOW()
		)
		";
		@$res = pg_query($con, $sql);

		if (pg_errormessage($con)) {
			$msg_erro = pg_errormessage($con);
		}
		else {
			//Total já baixado
			$sql = "SELECT SUM(qtde_acerto) AS qtde_acerto FROM tbl_faturamento_baixa_divergencia WHERE faturamento_item=$faturamento_item";
			$res = pg_query($con, $sql);
			$qtde_total = pg_result($res, 0, qtde_acerto);

			$msg = $qtde_baixado + $qtde;
			if ($qtde_quebrada == $qtde_total) {
				$total = "total";
			}
			else {
				$total = "parcial";
			}
		}
	break;

	default:
		$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro|erro";
	$sql = "ROLLBACK TRANSACTION";
}
elseif (strlen($msg)) {
	echo "$acao|sucesso|$msg|$total";
	$sql = "COMMIT";
}

$res = pg_query($con, $sql);

?>