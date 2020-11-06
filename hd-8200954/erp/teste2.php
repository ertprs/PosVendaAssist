<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';
echo '-'.$login_privilegios;
/*if (strpos ($login_privilegios,'cadastros') === false and strpos ($login_privilegios,'*') === false) {
		header ("Location: teste2.php");
	exit;
}*/
//---------------------------------------------------

echo "<H1>cadastros</H1>";
include "rodape.php"; ?>
