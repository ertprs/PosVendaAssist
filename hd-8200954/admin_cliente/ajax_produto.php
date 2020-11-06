
<?php
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';


$referencia     = $_REQUEST['referencia'];
$data_nf     = $_REQUEST['data_nf'];
if(!empty($referencia) and !empty($data_nf)) {
	$xdata_nf       = fnc_formata_data_pg(trim($data_nf));

	$sql = "SELECT  garantia,
					produto
			FROM tbl_produto
			JOIN tbl_linha   USING(linha)
			WHERE referencia = '$referencia'
			AND   fabrica    = $login_fabrica";
	$res = @pg_query($con,$sql);
	if(pg_num_rows($res)>0){

		$produto  = pg_fetch_result($res,0,produto);
		$garantia = pg_fetch_result($res,0,garantia);

		$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date < current_date,to_char(($xdata_nf::date + (($garantia || ' months')::interval))::date,'DD/MM/YYYY')";
		$res = @pg_query($con,$sql);
		if(pg_num_rows($res) > 0 ) {
			if(pg_fetch_result($res,0,0) == 'f'){
				echo "ok|sim";
			}else{
				echo "no|Produto fora da garantia vencida em ".pg_fetch_result($res,0,1);
			}
		}
		exit;
	}
}
exit;

?>

