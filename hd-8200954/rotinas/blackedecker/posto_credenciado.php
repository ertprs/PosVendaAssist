<?php

/*
* Rotinas Posto Credenciado.
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$login_fabrica = 1;

$sql = "SELECT tbl_posto_fabrica.codigo_posto
		FROM tbl_posto_fabrica 
		INNER JOIN tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto
		AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE fabrica = $login_fabrica and tbl_posto_fabrica.credenciamento = 'CREDENCIADO' and tbl_posto_fabrica.categoria not  ilike '%Cadastro' ";
$res = pg_query($con, $sql);

if(pg_num_rows($res)>0){
	$fileName = "postos_credenciados.txt";
	$file = fopen("/tmp/{$fileName}", "w");	

	fwrite($file, $head);
	$body = "";
	for($i=0; $i<pg_num_rows($res); $i++){
		$codigo_posto = pg_fetch_result($res, $i, 'codigo_posto');

		$body .= $codigo_posto;
		$body .= "\r\n";
	}

	fwrite($file, $body);
	fclose($file);
   	if (file_exists("/tmp/{$fileName}")) {
		system("mv /tmp/{$fileName}  /home/blackedecker/telecontrol-black/{$fileName}");
	}
}







?>
