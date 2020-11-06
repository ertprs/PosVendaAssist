<?php

require 'dbconfig.php';
require 'includes/dbconnect-inc.php';
require 'autentica_admin.php';

switch($_GET["tipo"]) {
	
	case "excluir":
	$informativo_modulo = intval($_GET["informativo_modulo"]);
	$sql="DELETE FROM tbl_informativo_modulo_texto WHERE informativo_modulo={$informativo_modulo}";
	$res = pg_query($con, $sql);
	$excluir= pg_affected_rows($res);
	$sql=" DELETE FROM tbl_informativo_modulo WHERE informativo_modulo={$informativo_modulo}";
	$res = pg_query($con, $sql);	
	$excluir = pg_affected_rows($res);
	echo "{$excluir}";
			
	break;
}
?>