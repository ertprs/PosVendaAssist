<?php

error_reporting(E_ALL ^ E_NOTICE);


include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';


$sql = "DELETE FROM tbl_login_cookie WHERE data_input::date <= current_date - 1;";
pg_query($con, $sql);

$sql = "UPDATE tbl_admin SET cliente_admin = null WHERE fabrica = 158";
pg_query($con, $sql);