<?php

//Desenvolvedor: Ébano
//Criei o arquivo para respostas de AJAX da tela os_cadastro_tudo.php
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
		$res = pg_query($con, $sql);

		for ($i = 0; $i < pg_num_rows($res); $i++) {
			$dados = pg_fetch_row($res);
			$dados = implode("|", $dados);
			echo $dados . "\n";
		}
	break;
}

?>