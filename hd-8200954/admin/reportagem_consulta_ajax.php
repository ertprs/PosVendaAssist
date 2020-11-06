<?php

require 'dbconfig.php';
require 'includes/dbconnect-inc.php';
require 'autentica_admin.php';

switch($_GET["tipo"]) {
	
	case "excluir":
	$reportagem = intval($_GET["reportagem"]);
	$sql="DELETE FROM tbl_reportagem_foto WHERE reportagem={$reportagem}";
	$res = pg_query($con, $sql);
	$excluir= pg_affected_rows($res);
	$sql=" DELETE FROM tbl_reportagem WHERE reportagem={$reportagem}";
	$res = pg_query($con, $sql);	
	$excluir = pg_affected_rows($res);
	echo "{$excluir}";
			
	break;
}
?>


