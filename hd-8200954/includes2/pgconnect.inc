<?php
/* 	Esta include conecta um banco de dados conforme parametros
	enviados
	Banco de Dados:	$dbnome
	Porta:		$port
	Usuario:	$usuario
	Senha:		$senha
*/
	if (strlen ($dbusuario) == 0) {
		$dbusuario 	= "postgres";
	}
	if (strlen ($dbsenha) == 0) {
		$dbsenha 	= "akacia2002";
	}

	$dbport 	= 5432;
	$parametros = "host=localhost dbname=$dbnome port=$dbport user=$dbusuario password=$dbsenha";
	
	if(!($con=pg_connect($parametros))) {
		echo "<p align=\"center\"><big><strong>Nao foi possivel
			estabelecer uma conexao com o banco de dados $dbnome.
			Favor contactar o Administrador.</strong></big></p>";
		exit;
	}
?>
