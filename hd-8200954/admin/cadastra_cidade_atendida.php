<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';;
include 'autentica_admin.php';
include 'funcoes.php';

if ($_POST["adicionar_cidade"]) {
	$cidade = utf8_decode($_POST["cidade"]);
	$estado = $_POST["estado"];
	$posto  = $_POST["posto"];
	$tipo   = $_POST["tipo"];
	$km     = $_POST["km"];

	if (in_array($login_fabrica,array(74,91,117))) {
		$tipo = $login_fabrica == 74 ? 6 : 7;
	}

	if (!strlen($km)) {
		$km = 0;
	}

	if (strlen($cidade) > 0 && strlen($estado) > 0) {
		$sql = "SELECT  cidade,
                        nome,
			estado,
			cod_ibge
                FROM    tbl_cidade
                WHERE   UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))
                AND     UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$id = pg_fetch_result($res, 0, "cidade");
			$cod_ibge = pg_fetch_result($res,0,"cod_ibge");
		} else {
			$sql = "SELECT cidade, estado, cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade   = pg_fetch_result($res, 0, "cidade");
				$estado   = pg_fetch_result($res, 0, "estado");
				$cod_ibge = pg_fetch_result($res, 0, "cod_ibge");



				$sql = "INSERT INTO tbl_cidade (
							nome, estado, cod_ibge
						) VALUES (
							'{$cidade}', '{$estado}', {$cod_ibge}
						) RETURNING cidade";
				$res = pg_query($con, $sql);

				$id = pg_fetch_result($res, 0, "cidade");
			} else {
				$retorno = array("erro" => utf8_encode("Cidade não encontrada"));
			}
		}

		$cod_ibge = (empty($cod_ibge) ?  'null' : $cod_ibge);
		if (!isset($retorno["erro"])) {
			$sql = "SELECT posto_fabrica_ibge FROM tbl_posto_fabrica_ibge WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND cidade = {$id}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$retorno = array("erro" => utf8_encode("Cidade já cadastrada"));
			} else {
				 $sql = "INSERT INTO tbl_posto_fabrica_ibge (posto, fabrica, cidade, cod_ibge, posto_fabrica_ibge_tipo, km) VALUES ({$posto}, {$login_fabrica}, {$id}, {$cod_ibge}, {$tipo}, {$km}) RETURNING posto_fabrica_ibge";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$retorno = array("erro" => "Erro ao gravar cidade");
				} else {
					$id = pg_fetch_result($res, 0, "posto_fabrica_ibge");

					$sql  = "SELECT nome FROM tbl_posto_fabrica_ibge_tipo WHERE fabrica = {$login_fabrica} AND posto_fabrica_ibge_tipo = {$tipo}";
					$res  = pg_query($con, $sql);
					$tipo = utf8_encode(pg_fetch_result($res, 0, "nome"));

					$retorno = array("id" => $id, "cidade" => utf8_encode($cidade), "estado" => $estado, "km" => $km, "tipo" => $tipo);
				}
			}
		}
	} else {
		$retorno = array("erro" => "Selecione uma cidade e um estado");
	}

	exit(json_encode($retorno));
}

if ($_POST["adicionar_bairro"]) {
	$posto  = $_POST["posto"];
	$id     = $_POST["id"];
	$bairro = strtoupper(retira_acentos(utf8_decode($_POST["bairro"])));

	if (strlen($bairro) > 0) {
		$sql = "SELECT bairro FROM tbl_posto_fabrica_ibge WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$bairros = json_decode(pg_fetch_result($res, 0, "bairro"), true);

			if (count($bairros) > 0) {
				foreach ($bairros as $key => $value) {
					$bairros[$key] = strtoupper(retira_acentos(utf8_decode($value)));
				}
			}

			if (in_array(strtoupper($bairro), $bairros)) {
				$retorno = array("erro" => utf8_encode("Bairro já cadastrado"));	
			} else {
				$bairros[] = $bairro;

				$bairros = json_encode($bairros);

				$sql = "UPDATE tbl_posto_fabrica_ibge SET bairro = '{$bairros}' WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
				$res = pg_query($con, $sql);

				if (!strlen(pg_last_error())) {
					$retorno = array("ok" => $bairro);
				} else {
					$retorno = array("erro" => "Erro ao gravar bairro");
				}
			}
		} else {
			$retorno = array("erro" => utf8_encode("Cidade não encontrada"));
		}
	} else {
		$retorno = array("erro" => "Digite um bairro");
	}

	exit(json_encode($retorno));
}

if ($_POST["excluir_cidade"]) {
	$id    = $_POST["id"];
	$posto = $_POST["posto"];

	if (strlen($id) > 0) {
		$sql = "SELECT posto_fabrica_ibge FROM tbl_posto_fabrica_ibge WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$sql = "DELETE FROM tbl_posto_fabrica_ibge WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
			$res = pg_query($con, $sql);

			if (!strlen(pg_last_error())) {
				$retorno = array("ok" => true);
			} else {
				$retorno = array("erro" => "Erro ao excluir cidade");
			}
		} else {
			$retorno = array("erro" => utf8_encode("Cidade não encontrada"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Cidade não encontrada"));
	}

	exit(json_encode($retorno));
}

if ($_POST["excluir_bairro"]) {
	$id     = $_POST["id"];
	$posto  = $_POST["posto"];
	$bairro = strtoupper(retira_acentos(utf8_decode($_POST["bairro"])));

	if (strlen($bairro) > 0) {
		$sql = "SELECT bairro FROM tbl_posto_fabrica_ibge WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$bairros = json_decode(pg_fetch_result($res, 0, "bairro"), true);

			if (count($bairros) > 0) {
				foreach ($bairros as $key => $value) {
					$bairros[$key] = strtoupper(retira_acentos(utf8_decode($value)));
				}
			}

			$bairro_key = array_search($bairro, $bairros);

			unset($bairros[$bairro_key]);

			$bairros = json_encode($bairros);

			$sql = "UPDATE tbl_posto_fabrica_ibge SET bairro = '{$bairros}' WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
			$res = pg_query($con, $sql);

			if (!strlen(pg_last_error())) {
				$retorno = array("ok" => true);
			} else {
				$retorno = array("erro" => "Erro ao excluir bairro");
			}
		} else {
			$retorno = array("erro" => utf8_encode("Cidade não encontrada"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Bairro não encontrado"));
	}

	exit(json_encode($retorno));
}

if ($_POST["salvar_cidade"]) {
	$posto = $_POST["posto"];
	$id    = $_POST["id"];
	$km    = $_POST["km"];

	if (!strlen($km)) {
		$km = 0;
	}

	if (strlen($id) > 0) {
		$sql = "SELECT posto_fabrica_ibge FROM tbl_posto_fabrica_ibge WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$sql = "UPDATE tbl_posto_fabrica_ibge SET km = {$km} WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND posto_fabrica_ibge = {$id}";
			$res = pg_query($con, $sql);

			if (!strlen(pg_last_error())) {
				$retorno = array("ok" => true);
			} else {
				$retorno = array("erro" => "Erro ao salvar alteração de KM");
			}
		} else {
			$retorno = array("erro" => utf8_encode("Cidade não encontrada"));
		}
	} else {
		$retorno = array("erro" => utf8_encode("Cidade não encontrada"));
	}

	exit(json_encode($retorno));
}

?>
