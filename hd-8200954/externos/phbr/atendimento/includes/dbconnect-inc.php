<?php
/* 	Esta include conecta um banco de dados conforme parametros
	enviados
	Banco de Dados:	$dbbanco
	Nome do Banco:  $dbnome	
	Porta:		$dbport
	Usuario:	$dbusuario
	Senha:		$dbsenha
*/

	if ($dbport == 0 OR $dbport == NULL) {
		$dbport 	= 5432;
	}


	if (strlen ($dbbanco) == 0) {
		$dbbanco 	= "postgres";
		$dbport         = 5432;
	}


	#-------------------- PostgreSQL ----------------
	if ($dbbanco == "postgres") {
#		echo "$dbbanco \n Porta = $dbport \n $dbnome \n $dbusuario \n $dbsenha";
		$parametros = "host=$dbhost dbname=$dbnome port=$dbport user=$dbusuario password=$dbsenha";
		if(!($con=pg_connect($parametros))) {
			echo "<p align=\"center\"><big><strong>Não foi possível
				estabelecer uma conexao com o banco de dados $dbnome.
				Favor contactar o Administrador.</strong></big></p>";
			exit;
		}
	}
?>
