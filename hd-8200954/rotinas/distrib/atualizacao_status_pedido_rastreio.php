<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica_distrib = 63;

/*
* Cron Class
*/
$phpCron = new PHPCron($fabrica_distrib, __FILE__);
$phpCron->inicio();

/* Fabricas Distrib */
$sql = "SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais ilike '%telecontrol_distrib%' and ativo_fabrica and fabrica not in(10,119,168) ";
$res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){
	while($data = pg_fetch_object($res)){
		$telecontrol_distrib .= $data->fabrica.",";
	}
}

$telecontrol_distrib = substr($telecontrol_distrib, 0, strlen($telecontrol_distrib) - 1);

$sqlPedido = " SELECT tbl_pedido.pedido, tbl_faturamento_item.faturamento 
    FROM tbl_pedido
      JOIN tbl_pedido_item USING (pedido)
      JOIN tbl_faturamento_item USING (pedido)
      join tbl_faturamento_correio on tbl_faturamento_item.faturamento = tbl_faturamento_correio.faturamento 
    WHERE tbl_pedido.fabrica in ({$telecontrol_distrib})
      AND tbl_pedido.status_pedido in (4,5,30)
    GROUP BY tbl_pedido.pedido, tbl_faturamento_item.faturamento ";
$resPedido = pg_query($con, $sqlPedido);

while ($pedido = pg_fetch_row($resPedido)) {

	$temRegistro = false;
	$foiEntregue = false;

	$sqlSituacao = "SELECT situacao FROM tbl_faturamento_correio WHERE faturamento = {$pedido[1]} limit 1";
	$resSituacao = pg_query($con, $sqlSituacao);
	$rowSituacao = pg_num_rows($resSituacao);

	$temRegistro = ($rowSituacao > 0 ) ? true : false;

	$sqlSituacaoEntregue = "SELECT situacao FROM tbl_faturamento_correio WHERE faturamento = {$pedido[1]} AND situacao ~* 'entregue' limit 1";
	$resSituacaoEntregue = pg_query($con, $sqlSituacaoEntregue);
	$rowSituacaoEntregue = pg_num_rows($resSituacaoEntregue);

	$foiEntregue = ($rowSituacaoEntregue > 0 ) ? true : false;
	if ($temRegistro && $foiEntregue) {
		$status = 31;
	}else if ($temRegistro){
		$status = 30;
	}	
	$sqlUpdate = "UPDATE tbl_pedido SET status_pedido = $status WHERE pedido = {$pedido[0]} and status_pedido in (13,4)";
	$resUpdate = pg_query($con, $sqlUpdate);

	$sql = "SELECT * from tbl_pedido_status where pedido = {$pedido[0]} and status = $status and observacao ='Rastreio' "; 
	$res = pg_query($con, $sql);
	if(pg_num_rows($res) == 0) {
		$aux_sql = " INSERT INTO tbl_pedido_status(pedido, data, status,  observacao) VALUES ({$pedido[0]} , current_timestamp, $status, 'Rastreio')";
		$aux_res = pg_query($con, $aux_sql);
	}
}

/*
* Cron Término
*/
$phpCron->termino();


