<?
$diretorio = getenv("QUERY_STRING");
$res	   = pg_exec($con,"SET DateStyle TO 'SQL,EUROPEAN'");

if (strlen ($cook_posto) == 0) {
	header ("Location: index.php");
}

?>