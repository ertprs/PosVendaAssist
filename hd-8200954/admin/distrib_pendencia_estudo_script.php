<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "gerencia";
$title = "Estudo de Peças Pendentes no Posto x Distribuidor x Fábrica";

include 'cabecalho.php';

$sql_script = "SELECT cancel, data FROM tmp_cancelamento_pedido WHERE data IS NULL";
$res_script = pg_exec($con,$sql_script);

for ($i=0;$i<pg_numrows($res_script);$i++){ 
	$sql    = "BEGIN;";
	$res    = pg_exec($con,$sql);
	$cancel = pg_result($res_script,$i,cancel);
	
	$sql = "$cancel ;";
	$res = pg_exec($con,$sql);
	$cancel_aux = substr($cancel,0,54);
	if(strlen(pg_last_error($con))==0){
		echo $cancel."<BR>";
		$sql = "UPDATE tmp_cancelamento_pedido SET data = current_timestamp WHERE cancel like '$cancel_aux%';";
		$res = pg_exec($con,$sql);
		$sql = "COMMIT;";
		$res = pg_exec($con,$sql);
	}else{
		$sql = "ROLLBACK;";
		$res = pg_exec($con,$sql);
	}
}
