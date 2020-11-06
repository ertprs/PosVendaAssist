<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$linha =$_GET['linha'];
$os    =$_GET['os'];

$sql = "UPDATE tbl_os_item
		SET gera_pedido_imediato = true 
		FROM tbl_os, tbl_os_produto
		WHERE tbl_os.os               = tbl_os_produto.os
		AND tbl_os_produto.os_produto = tbl_os_item.os_produto
		AND tbl_os.os = $os ";
$res = pg_query($con,$sql);
$erro .= pg_errormessage($con);

if (strlen($erro) == 0){
	echo'ok';
}else{
	echo'no';
}
?>