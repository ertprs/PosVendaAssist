<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$os         = $_POST["os"];
$hd_chamado = $_POST["hd_chamado"];
$motivo     = $_POST["motivo"];
$posto      = $_POST["posto"];

$sql = "SELECT tbl_posto.nome
		FROM tbl_hd_chamado 
		JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
		JOIN tbl_posto ON tbl_posto.posto = tbl_hd_chamado_extra.posto
		WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
		AND tbl_hd_chamado.hd_chamado = {$hd_chamado}";
$res = pg_query($con, $sql);

$posto_antigo_nome = pg_fetch_result($res, 0, "nome");

$sql = "SELECT nome
		FROM tbl_posto
		WHERE posto = {$posto}";
$res = pg_query($con, $sql);

$posto_nome = pg_fetch_result($res, 0, "nome");

if ($_POST["resetaPosto"] == true) {
	$sql = "SELECT 
				tbl_posto_fabrica.posto, 
				tbl_posto_fabrica.codigo_posto, 
				tbl_posto.nome, 
				tbl_posto_fabrica.contato_email,
				tbl_posto.fone
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
			JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
			WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
			AND tbl_hd_chamado.hd_chamado = {$hd_chamado}";
	$res = pg_query($con, $sql);

	$array = array(
		"posto"  => pg_fetch_result($res, 0, "posto"),
		"codigo" => pg_fetch_result($res, 0, "codigo_posto"),
		"nome"   => utf8_encode(pg_fetch_result($res, 0, "nome")),
		"email"  => pg_fetch_result($res, 0, "contato_email"),
		"fone"   => pg_fetch_result($res, 0, "fone")
	);

	$retorno = json_encode($array);

	exit($retorno);
}

if (empty($hd_chamado)) {
	exit(json_encode(array("erro" => utf8_encode("Atendimento não encontrado"))));
}

if (empty($os)) {
	exit(json_encode(array("erro" => utf8_encode("OS não encontrada"))));
}

if (empty($posto)) {
	exit(json_encode(array("erro" => utf8_encode("Posto não encontrado"))));
}

if ($_POST["exclui"] == true) {
	$sql = "SELECT 
				tbl_os.defeito_constatado, 
				(
					SELECT COUNT(0) 
					FROM tbl_os_item 
					JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto 
					WHERE tbl_os_produto.os = tbl_os.os
				) AS itens
			FROM tbl_os
			WHERE tbl_os.fabrica = {$login_fabrica}
			AND tbl_os.os = {$os}";
	$res = pg_query($con, $sql);

	$defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");
	$itens              = pg_fetch_result($res, 0, "itens");

	if (strlen($defeito_constatado) > 0 || $itens > 0) {
		exit(json_encode(array("nao_exclui" => utf8_encode("OS não pode ser excluida pois já possui defeito constatado ou itens lançados"))));
	} else {
		$sql = "SELECT sua_os 
				FROM tbl_os 
				WHERE os = {$os} 
				AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$sua_os = pg_fetch_result($res, 0, "sua_os");

		$sql = "INSERT INTO tbl_os_status 
				(os, status_os, data, observacao, fabrica_status) 
				VALUES 
				($os, 15, current_timestamp, '$motivo', $login_fabrica)";
		$res = pg_query($con, $sql);

		$sql = "SELECT fn_os_excluida({$os}, {$login_fabrica}, {$login_admin})";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			exit(json_encode(array("nao_exclui" => utf8_encode(pg_last_error()))));
		} else {
			$sql = "SELECT posto FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
			$res = pg_query($con, $sql);

			$posto_antigo = pg_fetch_result($res, 0, "posto");

			$sql = "UPDATE tbl_hd_chamado_extra 
					SET 
						os    = NULL,
						posto = {$posto}
					WHERE hd_chamado = {$hd_chamado}";
			$res = pg_query($con, $sql);

			$sql = "INSERT INTO tbl_comunicado (
						fabrica,
						posto,
						obrigatorio_site,
						tipo,
						ativo,
						descricao,
						mensagem
					) VALUES (
						{$login_fabrica},
						{$posto_antigo},
						true,
						'Com. Unico Posto',
						true,
						'OS {$sua_os} foi excluída',
						'{$motivo}'
					)";
			$res = pg_query($con, $sql);

			$sql = "INSERT INTO tbl_hd_chamado_item (
					hd_chamado, comentario, admin, interno
				) VALUES (
					{$hd_chamado}, 'OS {$sua_os} foi excluída.<br />Motivo: {$motivo}', {$login_admin}, true
				)";
			$res = pg_query($con, $sql);

			$sql = "INSERT INTO tbl_hd_chamado_item (
						hd_chamado, comentario, admin, interno
					) VALUES (
						{$hd_chamado}, 'Posto <b>{$posto_antigo_nome}</b> alterado para <b>{$posto_nome}</b>', {$login_admin}, true
					)";
			$res = pg_query($con, $sql);

			exit(json_encode(array("exclui" => true)));
		}
	}
}

if ($_POST["desassocia"] == true) {
	$sql = "UPDATE tbl_hd_chamado_extra 
			SET 
				os    = NULL,
				posto = {$posto}
			WHERE hd_chamado = {$hd_chamado}";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		exit(json_encode(array("erro" => "Erro ao desassociar OS do atendimento")));
	} else {
		$sql = "SELECT sua_os 
				FROM tbl_os 
				WHERE os = {$os} 
				AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$sua_os = pg_fetch_result($res, 0, "sua_os");

		$sql = "INSERT INTO tbl_hd_chamado_item (
					hd_chamado, comentario, admin, interno
				) VALUES (
					{$hd_chamado}, 'OS {$sua_os} desassociada do Atendimento.<br />Motivo: {$motivo}', {$login_admin}, true
				)";
		$res = pg_query($con, $sql);

		$sql = "INSERT INTO tbl_hd_chamado_item (
					hd_chamado, comentario, admin, interno
				) VALUES (
					{$hd_chamado}, 'Posto <b>{$posto_antigo_nome}</b> alterado para <b>{$posto_nome}</b>', {$login_admin}, true
				)";
		$res = pg_query($con, $sql);

		exit(json_encode(array("ok" => true)));
	}
}

exit;
?>