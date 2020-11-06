<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <title>Impressão Certificado Garantia</title>
    <meta http-equiv=pragma content=no-cache>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: segoe ui,arial,helvetica,verdana,sans-serif;
            font-size: 12px;
            margin:0px;
        }
        table {
            font-size: 12px;
        }
        a {
            text-decoration: none;
            color: #000000;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body><?php

$certificado = (int) $_GET['certificado'];

$sql = "SELECT certificado_impresso FROM tbl_certificado WHERE certificado = $certificado AND fabrica = $login_fabrica";

$res = @pg_query($con,$sql);
$tot = @pg_num_rows($res);

if ($tot > 0) {

	$msg = pg_fetch_result($res, 0, 'certificado_impresso');
	$msg = str_replace('border="0"','border="0" width="240"', $msg);
	$msg = str_replace('http://www.telecontrol.com.br/', 'http://posvenda.telecontrol.com.br/', $msg);

	echo $msg;

	echo '<script type="text/javascript">';
		echo 'window.print();';
	echo '</script>';

} else {

    echo '<h2 align="center">Nenhum certificado encontrado!</h2>';

}?>

</body>
</html>
