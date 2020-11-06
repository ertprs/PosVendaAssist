<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (empty($_REQUEST['acao'])) {
	exit;
}

$consulta = $_REQUEST['acao'];

switch ($consulta) {

	case 'posto':
		if (empty($_REQUEST['fabrica']) or empty($_REQUEST['posto'])) {
			exit;
		}

		$fabrica = $_REQUEST['fabrica'];
		$posto = $_REQUEST['posto'];
		$sql = "SELECT estado, cidade, codigo_posto, nome, fone, email
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				AND tbl_posto.posto = $posto";
		break;

	case 'sql':
		$sql = stripslashes($_REQUEST["sql"]);
		break;

	default:
		exit;
		break;
}

$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
	$dados = pg_fetch_assoc($res);
	$dados = implode("|", $dados);

	echo $dados;
}
exit;

