<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";


header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
//header("Pragma: no-cache, public");

$fabrica = $_GET ['fabrica'];


if (strlen ($fabrica) > 0) {
	$sql = "SELECT * FROM tbl_admin WHERE fabrica = $fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {

		$admin           = trim (pg_result ($res,0,admin));
		$login         = trim (pg_result ($res,0,login));
		$nome_completo = trim (pg_result ($res,0,nome_completo));


		echo $admin . ";" . $login . ";" . $nome_completo . ";" ;
	}
}

?>
