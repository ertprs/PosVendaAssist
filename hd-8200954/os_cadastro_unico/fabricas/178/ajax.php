<?php

if($_POST["ajax_estoque_roca"]){
	
	$referencia  = $_POST['referencia'];
	$posto       = $_POST['posto'];

	$sql = "
	    SELECT tbl_estoque_posto.qtde 
	    FROM tbl_estoque_posto 
	    JOIN tbl_peca USING(peca,fabrica)
	    WHERE tbl_estoque_posto.fabrica = {$login_fabrica}
	    AND tbl_peca.referencia = '{$referencia}'
	    AND tbl_estoque_posto.posto = {$posto};
	";
	$resE = pg_query($con,$sql);

	if (pg_num_rows($resE) > 0) {
		$qtde = pg_fetch_result($resE, 0, 'qtde');
	} else {
		$qtde = 0;
	}

	$retorno["qtde"] = $qtde;
	echo json_encode($retorno);
	exit;

}
