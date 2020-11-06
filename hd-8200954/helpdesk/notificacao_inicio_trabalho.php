<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$hora = date("G");
$minuto = (int) date("i");
$hora_trabalho = false;

if (($hora == 8 && $minuto >= 30) || ($hora > 8 && $hora < 11 ) || ($hora == 11 && $minuto <= 30) || ($hora == 13 && $minuto >= 30) || ($hora > 13 && $hora < 16) || ($hora == 16 && $minuto > 15) || ($hora == 17)) {
	$hora_trabalho = true;
}

if ($_POST["ajax_verifica_inicio_trabalho"]) {
	$sql = "SELECT grupo_admin FROM tbl_admin WHERE fabrica = {$login_fabrica} AND admin = {$login_admin}";
        $res = pg_query($con, $sql);

        $grupo_admin = pg_fetch_result($res, 0, "grupo_admin");

        if (in_array($grupo_admin, array(1)) || in_array($login_admin, array(586))) {
		$sql = "SELECT nome_completo
			FROM tbl_admin
			WHERE fabrica = 10
			AND grupo_admin IN (2, 4)
			AND (ativo IS TRUE AND nao_disponivel IS NULL)
			AND admin NOT IN (2466)
			AND admin NOT IN (SELECT admin FROM tbl_hd_chamado_atendente WHERE data_inicio::DATE=CURRENT_DATE AND data_termino ISNULL)";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && $hora_trabalho === true) {
			$desenvolvedores = array();

			while ($desenvolvedor = pg_fetch_object($res)) {
				$desenvolvedores[] = utf8_encode($desenvolvedor->nome_completo);
			}

			exit(json_encode(
				array(
					"trabalho" => false,
					"nome" => utf8_encode("Desenvolvedores que não deram início de trabalho"),
					"mensagem" => implode(", ", $desenvolvedores)
				)
			));
		} else {
			exit(json_encode(array("trabalho" => true)));
		}
	} else {
		$sql = "SELECT nome_completo, admin
			FROM tbl_admin
			WHERE fabrica = 10
			AND   grupo_admin IN (2,4)
			AND   admin = $login_admin
			AND   ativo IS TRUE
			AND   admin NOT IN (SELECT admin FROM tbl_hd_chamado_atendente WHERE data_inicio::DATE=CURRENT_DATE AND data_termino ISNULL)";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && $hora_trabalho === true) {
			exit(json_encode(
				array(
					"trabalho" => false, 
					"nome" => utf8_encode(pg_fetch_result($res, 0, "nome_completo")),
					"mensagem" => utf8_encode("Você ainda não deu início de trabalho")
				)
			));
		} else {
			exit(json_encode(array("trabalho" => true)));
		}
	}
}
