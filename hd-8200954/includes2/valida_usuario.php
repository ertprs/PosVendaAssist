<?php
$diretorio	= getenv("QUERY_STRING");

$sql = "SET DateStyle TO 'SQL,EUROPEAN'";
$res = pg_exec ($con,$sql);

############## Valida usuarios #################
if (strlen($cook_id)>0){
	$sql = "SELECT * FROM ACESSO WHERE usuario_id = $cook_usuario_id AND acesso ='$pagina'";
	$res = pg_exec($con,$sql);
}
if (dbnumrows($res) == 0 AND strlen($diretorio) == 0){
	setcookie ("cook_erro","Você não tem permissão para acessar este link");
	$cook_erro = "Você não tem permissão para acessar este link";
	echo "<meta http-equiv='refresh' content='0;url=index.php?permissao=$cook_permissao_ok'>";
	exit;
}
#######################################################
?>
