<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if (preg_match('/pesquisa_revenda(.*).php/', $PHP_SELF, $a_suffix)) {
	$suffix = $a_suffix[1];
	if (!file_exists("pesquisa_revenda_frame$suffix.php")) unset($suffix);
}
?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv='pragma' content='no-cache'>
<? if ($sistema_lingua<>'ES') { ?>
	<title>Pesquisa distribuidores.. </title>
<? } else { ?>
	<title>Búsqueda de Revendedores... </title>
<? } ?>
</head>
<?
$params = ($_SERVER['REQUEST_METHOD'] == 'GET') ? $_GET : $_POST;
?>
<body>
<?include ("pesquisa_revenda_frame$suffix.php"); //?<?=serialize($params)"?>
</body>
</html>

<?php
// Fechando explicitamente a conexão com o BD
if (is_resource($con)) {
    pg_close($con);
}
?>
