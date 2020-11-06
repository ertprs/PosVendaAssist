<?php
include "dbconfig.php";
include "dbconnect-inc.php";

$admin_privilegios = "call_center";
$layout_menu       = "callcenter";

include "autentica_admin.php";

include "../admin/relatorio_ambev.php";
?>