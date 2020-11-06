<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

header('Content-Type: text/html; charset=ISO-8859-1');

if (isset($_GET["tipo_registro"])){
	$tipo_registro = trim ($_GET["tipo_registro"]);

	if (strlen($tipo_registro)>0){
		echo "<option value=''></option>";

		$sql = "SELECT	hd_situacao,
						descricao
				FROM tbl_hd_situacao
				WHERE fabrica       = $login_fabrica
				AND   tipo_registro ='$tipo_registro'
				ORDER BY descricao";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			for($i=0;pg_numrows($res)>$i;$i++){
				$xhd_situacao = pg_result($res,$i,hd_situacao);
				$descricao   = pg_result($res,$i,descricao);
				$selected=" ";
				if($xhd_situacao ==$hd_situacao ){
					$selected=" selected ";
				}
				echo "<option value='$xhd_situacao' $selected>$descricao</option>";
			}
		}
	}
	exit;
}


?>