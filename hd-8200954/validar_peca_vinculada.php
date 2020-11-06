<?php
if($_REQUEST['ajax'] == 'validar_peca_vinculada'){
	$referencia = $_REQUEST['referencia'];
	$ListaPeca = '';
	$sqlFilhaContainer = "SELECT peca_mae, peca_filha
						FROM tbl_peca_container 
							JOIN tbl_peca ON peca_filha = peca 
						WHERE tbl_peca_container.fabrica = $login_fabrica 
							AND referencia = '$referencia' ";
	$resFilhaContainer = pg_query($con, $sqlFilhaContainer);
	while ($lista = pg_fetch_object($resFilhaContainer)) {
		$sqlRefMae = "SELECT referencia FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = {$lista->peca_mae} ;";
		$resRefMae = pg_query($con, $sqlRefMae);
		$sqlRefFilha = "SELECT referencia FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = {$lista->peca_filha} ;";
		$resRefFilha = pg_query($con, $sqlRefFilha);
		$ListaPeca['peca_mae'][] = (string) pg_fetch_result($resRefMae, 0, 'referencia');
		$ListaPeca[pg_fetch_result($resRefMae, 0, 'referencia')][] = pg_fetch_result($resRefFilha, 0, 'referencia');
	}
	$sqlFilhaContainer = "SELECT peca_mae, peca_filha
						FROM tbl_peca_container 
							JOIN tbl_peca ON peca_mae = peca 
						WHERE tbl_peca_container.fabrica = $login_fabrica 
							AND referencia = '$referencia' ";
	$resFilhaContainer = pg_query($con, $sqlFilhaContainer);
	while ($lista = pg_fetch_object($resFilhaContainer)) {
		$sqlRefMae = "SELECT referencia FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = {$lista->peca_mae} ;";
		$resRefMae = pg_query($con, $sqlRefMae);
		$sqlRefFilha = "SELECT referencia FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = {$lista->peca_filha} ;";
		$resRefFilha = pg_query($con, $sqlRefFilha);
		$ListaPeca['peca_mae'][] = (string) pg_fetch_result($resRefMae, 0, 'referencia');
		$ListaPeca[pg_fetch_result($resRefMae, 0, 'referencia')][] = pg_fetch_result($resRefFilha, 0, 'referencia');
	}
	
	$maes = implode("','", $ListaPeca['peca_mae']);

	$sqlFilhaContainer = "SELECT peca_mae, peca_filha
						FROM tbl_peca_container 
							JOIN tbl_peca ON peca_mae = peca 
						WHERE tbl_peca_container.fabrica = $login_fabrica 
							AND referencia IN ('{$maes}')";
	$resFilhaContainer = pg_query($con, $sqlFilhaContainer);
	while ($lista = pg_fetch_object($resFilhaContainer)) {
		$sqlRefMae = "SELECT referencia FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = {$lista->peca_mae} ;";
		$resRefMae = pg_query($con, $sqlRefMae);
		$sqlRefFilha = "SELECT referencia FROM tbl_peca WHERE fabrica = $login_fabrica AND peca = {$lista->peca_filha} ;";
		$resRefFilha = pg_query($con, $sqlRefFilha);
		$ListaPeca['peca_mae'][] = pg_fetch_result($resRefMae, 0, 'referencia');
		$ListaPeca[pg_fetch_result($resRefMae, 0, 'referencia')][] = pg_fetch_result($resRefFilha, 0, 'referencia');
	}
	echo json_encode($ListaPeca);	
	exit;
}