<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="auditoria";
$layout_menu = 'auditoria';
include "funcoes.php";

$os = intval($_GET["os"]);
$os_auditar = intval($_GET["os_auditar"]);
$acao = $_GET["acao"];
$justificativa = $_GET["justificativa"];

if (strlen($os)) {
	$sql = "SELECT os FROM tbl_os WHERE os=$os AND fabrica=$login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		$sql = "
		SELECT
		os
		
		FROM
		tbl_os_auditar
		
		WHERE
		os_auditar=$os_auditar
		AND os=$os
		AND fabrica=$login_fabrica
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
		}
		else {
			$msg_erro = "OS informada nгo estб em auditoria";
		}
	}
	else {
		$msg_erro = "OS nгo encontrada";
	}
}
else {
	$msg_erro = "Nъmero da OS nгo informado";
}

if ($acao == "reprovar" && strlen($justificativa) == 0) {
	$msg_erro = "Para reprovar uma OS na auditoria, informe a justificativa";
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
	die;
}

$sql = "BEGIN TRANSACTION";
$res = pg_query($con, $sql);

switch($acao) {
	case "aprovar":
		$sql = "
		SELECT
		os_auditar

		FROM
		tbl_os_auditar

		WHERE
		fabrica=$login_fabrica
		AND os=$os
		AND liberado IS FALSE
		AND cancelada IS FALSE
		AND admin IS NULL
		AND liberado_data IS NULL
		AND os_auditar=$os_auditar
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$sql = "
			UPDATE
			tbl_os_auditar

			SET
			liberado=true,
			cancelada=false,
			cancelada_data=null,
			admin=$login_admin,
			liberado_data=NOW(),
			justificativa='$justificativa'

			WHERE
			fabrica=$login_fabrica
			AND os=$os
			AND liberado IS FALSE
			AND cancelada IS FALSE
			AND admin IS NULL
			AND liberado_data IS NULL
			AND os_auditar=$os_auditar
			";
			@$res = pg_query($con, $sql);

			if (pg_errormessage($con)) {
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
				//ebano teste
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk" . pg_errormessage($con);
			}
			else {
				$msg = "OS $os aprovada na auditoria prйvia";
			}
		}
		else {
			$msg_erro = "O status da auditoria da OS $os nгo permite a aзгo Aprovar";
		}
	break;

	case "reprovar":
		$sql = "
		SELECT
		os_auditar

		FROM
		tbl_os_auditar

		WHERE
		fabrica=$login_fabrica
		AND os=$os
		AND liberado IS FALSE
		AND cancelada IS FALSE
		AND admin IS NULL
		AND liberado_data IS NULL
		AND os_auditar=$os_auditar
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$sql = "
			UPDATE
			tbl_os_auditar

			SET
			liberado=false,
			cancelada=true,
			cancelada_data=NOW(),
			admin=$login_admin,
			liberado_data=null,
			justificativa='$justificativa'

			WHERE
			fabrica=$login_fabrica
			AND os=$os
			AND liberado IS FALSE
			AND cancelada IS FALSE
			AND admin IS NULL
			AND liberado_data IS NULL
			AND os_auditar=$os_auditar
			";
			@$res = pg_query($con, $sql);

			if (pg_errormessage($con)) {
				pg_errormessage($con);
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
				//ebano teste
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk" . pg_errormessage($con);
			}
			else {
				$msg = "OS $os reprovada na auditoria prйvia";
			}
		}
		else {
			$msg_erro = "O status da auditoria da OS $os nгo permite a aзгo Reprovar";
		}
	break;

	case "excluir_os":
		$sql = "SELECT os FROM tbl_os WHERE os=$os AND fabrica=$login_fabrica";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$sql = "SELECT fn_exclui_os($os, $login_fabrica);";
			$res = pg_query($con, $sql);

			if (pg_errormessage($con)) {
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
				//ebano teste
				$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk" . pg_errormessage($con);
			}
			else {
				$msg = "OS $os excluнda do sistema";
			}
		}
		else {
			$msg_erro = "Ordem de Serviзo nгo encontrada";
		}
	break;
	
	default:
		$msg_erro = "Opзгo de aзгo invбlida";
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
	$sql = "ROLLBACK TRANSACTION";
}
elseif (strlen($msg)) {
	echo "$acao|sucesso|$msg";
	$sql = "COMMIT";
}

$res = pg_query($con, $sql);

?>