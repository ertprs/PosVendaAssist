<?php
// 408341
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
header('Content-Type: text/html; charset=iso-8859-1');

$item = (int) $_GET['item'];
$tipo_resposta = (int) $_GET['tipo_resposta'];
if(empty($item) ) {
    echo '<tr><td colspan="5">Erro na passagem de par&atilde;metros</td></tr>';
    exit;
}

if ( !empty($item) ) {

	$sql = "DELETE FROM tbl_tipo_resposta_item WHERE tipo_resposta_item = $item AND tipo_resposta = $tipo_resposta";
	$res = @pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	echo ( empty( $msg_erro ) ) ? 't' : $msg_erro;
	exit;

}
