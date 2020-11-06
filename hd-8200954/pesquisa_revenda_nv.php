<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if (preg_match('/pesquisa_revenda_nv(.*).php/', $PHP_SELF, $a_suffix)) {
	$suffix = $a_suffix[1];
	if (!file_exists("pesquisa_revenda_frame_nv$suffix.php")) unset($suffix);
}

if ($sistema_lingua=='ES') 
	echo "<title>Pesquisa distribuidores.. </title>";
else
	echo "<title>Búsqueda de Revendedores... </title>";

$params = ($_SERVER['REQUEST_METHOD'] == 'GET') ? $_GET : $_POST;

include ("pesquisa_revenda_frame_nv$suffix.php");

// Fechando explicitamente a conexão com o BD
if (is_resource($con)) {
    pg_close($con);
}
