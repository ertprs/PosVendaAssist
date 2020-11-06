<?php
/* Esta funcao executa um comando SQL no banco de dados
	$con 	Ponteiro da conexao
	$sql	Clausula SQL a executar
	$erro 	Especifica se a funcao exibe ou nao (0=nao, 1=sim)
	$res	Resposta
*/

#------------ Executa uma função SQL -----------------
function dbexec($con,$sql,$erro = 1) {

	if(empty($sql) OR !($con)) {
		echo "Não foi passado o comando SQL ou o número da conexão.";
		return 0; // Erro na conexao ou no comando SQL
	}
	
	#---------------- Executa no PostgreSQL -----------
	if ($GLOBALS ["dbbanco"] == "postgres") {
		if(!($res = @pg_exec($con,$sql))) {
			if($erro) {
				echo "<p align='center'>" . pg_errormessage($con) . 
				"<br>Ocorreu um 
				erro na execucao do comando SQL no banco de 
				dados <b> $dbbanco </b>. 
				<br>Favor contactar o administrador
				<p>";
				exit;
			}
		}
		return $res;
	}
	
}



#------------ Retorna o número de linhas da Consulta -----------------
function dbnumrows($res) {
	if(!($res)) {
#		echo "Não foi passado o RECORDSET.";
		return 0; // Erro na conexao ou no comando SQL
	}
	
	#---------------- Executa no PostgreSQL -----------
	if ($GLOBALS ["dbbanco"] == "postgres") {
		return @pg_numrows ($res);	
	}
}


#------------ Recupera uma COLUNA do RECORDSET -----------------
function dbresult ($res,$i,$col) {
#	echo $res;

	if(!($res)) {
		echo "Não foi passado o RECORDSET.";
		return NULL; // Erro na conexao ou no comando SQL
	}
	
#	if(!($i)) {
#		echo $i;
#		echo "Não foi passada a linha do RECORDSET.";
#		return NULL; // Erro na conexao ou no comando SQL
#	}
	
	if(strlen($col) == 0) {
		echo "Não foi passada a coluna a pesquisar.";
		return NULL; // Erro na conexao ou no comando SQL
	}
	
	#---------------- Executa no PostgreSQL -----------
	if ($GLOBALS ["dbbanco"] == "postgres") {
		return pg_result ($res,$i,$col);
		
	}
}



#------------ Retorna a mensagem do Banco de Dados -----------------
function dberror($con) {

	if(!($con)) {
		echo "Não foi passado o número da conexão.";
		return 0; // Erro na conexao ou no comando SQL
	}
	
	#---------------- Executa no PostgreSQL -----------
	if ($GLOBALS ["dbbanco"] == "postgres") {
		return @pg_errormessage ($con);

	}
	
}



#$res = dbexec($con,"SET DateStyle TO 'SQL,EUROPEAN'",0);
?>