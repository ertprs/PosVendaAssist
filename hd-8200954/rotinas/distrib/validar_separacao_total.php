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

$sqlAguardandoEstoque = "SELECT pedido FROM tbl_pedido WHERE status_pedido = 22 and fabrica in ($telecontrol_distrib) ";

$resAguardandoEstoque = pg_query($con, $sqlAguardandoEstoque);

while($aguardandoEstoque = pg_fetch_object($resAguardandoEstoque)){

	$temTodas = true;
	$sqlPecas = "SELECT peca, qtde FROM tbl_pedido_item WHERE pedido = {$aguardandoEstoque->pedido}";
	$resPecas = pg_query($con, $sqlPecas);

	while ($pecas = pg_fetch_object($resPecas)) {
		$sqlPecaEstoque = "SELECT qtde FROM tbl_posto_estoque WHERE tbl_posto_estoque.peca = {$pecas->peca} AND tbl_posto_estoque.posto = 4311 ";
		$resPecasEstoque = pg_query($con, $sqlPecaEstoque);

		if (pg_fetch_result($resPecasEstoque, 0, 'qtde') < $pecas->qtde) {
			$sqlA = "select qtde from tbl_posto_estoque join tbl_peca_alternativa on peca_para = peca where peca_de = {$pecas->peca} AND tbl_posto_estoque.posto = 4311";
			$resA = pg_query($con, $sqlA);

			if (pg_fetch_result($resA, 0, 'qtde') < $pecas->qtde) {
				$temTodas = false;
			}
		} 
	}

	if ($temTodas) {
		$updateTblPedido = " UPDATE tbl_pedido SET status_pedido = 29 , status_fabricante = 'APROVADO' WHERE pedido = {$aguardandoEstoque->pedido} ";
		pg_query($con, $updateTblPedido);

		$udpateTblPedidoStatus = " INSERT INTO tbl_pedido_status(pedido, data, status,  observacao) VALUES ({$aguardandoEstoque->pedido}, current_timestamp, 29, 'Ag. Separacao')";
		pg_query($con, $udpateTblPedidoStatus);
	}		
}

/*
* Cron Término
*/
$phpCron->termino();