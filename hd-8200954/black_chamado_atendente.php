<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

ini_set("display_errors", "on");
error_reporting(E_ALL);

$sql = "SELECT hd.hd_chamado, hd.posto, hd.categoria
		FROM tbl_hd_chamado hd 
		JOIN tbl_hd_chamado_extra hde USING(hd_chamado)
		WHERE hd.data > '2013-12-20 00:00:00'
		AND hd.fabrica = 1
		AND hd.hd_chamado IN (
			SELECT hdi.hd_chamado
			FROM tbl_hd_chamado_item hdi
			WHERE hdi.hd_chamado = hd.hd_chamado
			AND hdi.hd_chamado NOT IN (
				SELECT hdi2.hd_chamado
				FROM tbl_hd_chamado_item hdi2
				WHERE hdi2.admin IS NOT NULL
				AND hdi2.hd_chamado = hdi.hd_chamado
				LIMIT 1
			)
			GROUP BY hdi.hd_chamado
			HAVING COUNT(hdi.hd_chamado) = 1
		)";
$res = pg_query($con, $sql);

$rows = pg_num_rows($res);

if ($rows > 0) {
	for ($i = 0; $i < $rows; $i++) { 
		$hd_chamado = pg_fetch_result($res, $i, "hd_chamado");
		$posto      = pg_fetch_result($res, $i, "posto");
		$categoria  = pg_fetch_result($res, $i, "categoria");

		$atendente = "";

		//admin preferencial
		$sql2 = "SELECT tbl_posto_fabrica.admin_sap
				 FROM tbl_posto_fabrica
				 JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
				 WHERE tbl_posto_fabrica.fabrica = 1
				 AND tbl_admin.ativo IS TRUE
				 AND tbl_posto_fabrica.posto = {$posto}";
		$res2 = pg_query($con, $sql2);

		if (pg_num_rows($res2) > 0 ) {
			$atendente = pg_fetch_result($res2, 0, "admin_sap");
		}

		if (empty($atendente)) {
			$sql2 = "SELECT cod_ibge AS cidade, UPPER(contato_estado) AS estado
					 FROM tbl_posto_fabrica
					 WHERE fabrica = 1
					 AND posto = {$posto}";
			$res2 = pg_query($con, $sql2);

			$cod_ibge = pg_fetch_result($res2, 0, "cidade");
			$estado   = pg_fetch_result($res2, 0, "estado");

			//tipo de solicitação + cidade + estado
			$sql2 = "SELECT tbl_admin_atendente_estado.admin
					 FROM tbl_admin_atendente_estado
					 JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					 WHERE tbl_admin_atendente_estado.fabrica = 1
					 AND tbl_admin.ativo IS TRUE
					 AND tbl_admin.nao_disponivel is null
					 AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge}
					 AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					 AND tbl_admin_atendente_estado.categoria = '{$categoria}'";
			$res2 = pg_query($con, $sql2);

			if (pg_num_rows($res2) > 0) {
				$atendente = pg_fetch_result($res2, 0, "admin");
			}
		}

		if (empty($atendente)) {
			//tipo de solicitação + estado
			$sql2 = "SELECT tbl_admin_atendente_estado.admin
					 FROM tbl_admin_atendente_estado
					 JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					 WHERE tbl_admin_atendente_estado.fabrica = 1
					 AND tbl_admin.ativo IS TRUE
					 AND tbl_admin.nao_disponivel is null
					 AND tbl_admin_atendente_estado.cod_ibge IS NULL
					 AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					 AND tbl_admin_atendente_estado.categoria = '{$categoria}'";
			$res2 = pg_query($con, $sql2);

			if (pg_num_rows($res2) > 0) {
				$atendente = pg_fetch_result($res2, 0, "admin");
			}
		}

		if (empty($atendente)) {
			//tipo de solicitação
			$sql2 = "SELECT tbl_admin_atendente_estado.admin
					 FROM tbl_admin_atendente_estado
					 JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					 WHERE tbl_admin_atendente_estado.fabrica = 1
					 AND tbl_admin.ativo IS TRUE
					 AND tbl_admin.nao_disponivel is null
					 AND tbl_admin_atendente_estado.cod_ibge IS NULL
					 AND (tbl_admin_atendente_estado.estado IS NULL OR LENGTH(tbl_admin_atendente_estado.estado) = 0)
					 AND tbl_admin_atendente_estado.categoria = '{$categoria}'";
			$res2 = pg_query($con, $sql2);

			if (pg_num_rows($res2) > 0) {
				$atendente = pg_fetch_result($res2, 0, "admin");
			}
		}

		if (empty($atendente)) {
			//cidade + estado
			$sql2 = "SELECT tbl_admin_atendente_estado.admin
					 FROM tbl_admin_atendente_estado
					 JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					 WHERE tbl_admin_atendente_estado.fabrica = 1
					 AND tbl_admin.ativo IS TRUE
					 AND tbl_admin.nao_disponivel is null
					 AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge}
					 AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					 AND tbl_admin_atendente_estado.categoria IS NULL";
			$res2 = pg_query($con, $sql2);

			if (pg_num_rows($res2) > 0) {
				$atendente = pg_fetch_result($res2, 0, "admin");
			}
		}

		if (empty($atendente)) {
			//estado
			$sql2 = "SELECT tbl_admin_atendente_estado.admin
					 FROM tbl_admin_atendente_estado
					 JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin
					 WHERE tbl_admin_atendente_estado.fabrica = 1
					 AND tbl_admin.ativo IS TRUE
					 AND tbl_admin.nao_disponivel is null
					 AND tbl_admin_atendente_estado.cod_ibge IS NULL
					 AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado}'
					 AND tbl_admin_atendente_estado.categoria IS NULL";
			$res2 = pg_query($con, $sql2);

			if (pg_num_rows($res2) > 0) {
				$atendente = pg_fetch_result($res2, 0, "admin");
			}
		}

		if (empty($atendente)) {
			$atendente = 155;
		}

		if (!empty($atendente)) {
			$sql2 = "UPDATE tbl_hd_chamado
					 SET atendente = {$atendente}
					 WHERE hd_chamado = {$hd_chamado}
					 AND fabrica = 1";
			$res2 = pg_query($con, $sql2);
		}
	}
}