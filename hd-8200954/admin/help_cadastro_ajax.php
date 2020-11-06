<?php

//Desenvolvedor: Ébano
//Criei o arquivo para respostas de AJAX da tela help_cadastro.php
//Organizei com um switch na variável GET acao. Para adicionar uma nova acao, acrescente um case "": break;

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

switch ($_GET["acao"]) {
	//Este case recebe na variavel acao "sql"
	//Deve vir também uma variavel sql com uma query para ser executada
	//O bloco de codigo gerara uma linha por resultado, separando os campos por PIPE |
	case "sql":
		$sql = stripslashes($_GET["sql"]);
		if (stripos($sql, "SELECT") !== false && stripos($sql, array("DELETE", "INSERT", "CREATE", "DROP", "TRUNCATE", "UPDATE", "GRANT") === false)) {
			$res = pg_query($con, $sql);

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$dados = pg_fetch_row($res);
				$dados = implode("|", $dados);
				echo $dados . "\n";
			}
		}
	break;

	case "arquivo":
		$sql = "SELECT arquivo, descricao FROM tbl_arquivo WHERE status='ativo' AND descricao ILIKE '%" . $_GET["q"] . "%' ORDER BY descricao LIMIT 10";
		$res = pg_query($con, $sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$dados = pg_fetch_row($res);
			$dados = implode("|", $dados);
			echo $dados . "\n";
		}
	break;

	case "fabrica":
		$localizar_fabrica_id = preg_replace( '/[^0-9]+/', '', $_GET["q"]);
		if ($localizar_fabrica_id) $localizar_fabrica_id = "OR fabrica=$localizar_fabrica_id";
		$sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE nome ILIKE '%" . $_GET["q"] . "%' $localizar_fabrica_id LIMIT 10";
		$res = pg_query($con, $sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$dados = pg_fetch_row($res);
			$dados = implode("|", $dados);
			echo $dados . "\n";
		}
	break;
}

?>