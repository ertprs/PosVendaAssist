<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";

include_once '../anexaNF_inc.php';

$extrato = $_GET["extrato"];

?>
<div style="width: 100%;height: 100%;background-color: white;">
<?php
	if (temNF("e_$extrato", 'bool')) {
		echo temNF("e_$extrato", 'linkEx');
		echo $include_imgZoom;
	}
?>
</div>