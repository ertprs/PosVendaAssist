<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$linha =$_GET['linha'];
$os    =$_GET['os'];


$sql = "UPDATE tbl_os_item
		SET gera_pedido_imediato = true
		JOIN tbl_os_produto 	on tbl_os.os 					= tbl_os_produto.os
		JOIN tbl_os_item 		on tbl_os_produto.os_produto 	= tbl_os_item.os_produto
		WHERE tbl_os.os = $os
		";
echo $sql;
//$res = pg_query($con,$sql);
$erro .= pg_errormessage($con);


}?>