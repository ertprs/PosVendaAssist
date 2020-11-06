<?php

$admin_privilegios="gerencia";

require_once dirname(__FILE__) . "/../../admin/dbconfig.php";
require_once dirname(__FILE__) . "/../../admin/includes/dbconnect-inc.php";
require_once dirname(__FILE__) . "/../../admin/autentica_admin.php";


//url = /rotinas/esmaltec/calculo_idc.php?mes=1&ano=2012

	$fabrica = isset($login_fabrica) ? $login_fabrica : 30;
	$mes_referente = isset($_GET["mes"]) ? intval($_GET["mes"]) : $mes_referente;
	$ano_referente = isset($_GET["ano"]) ? intval($_GET["ano"]) : $ano_referente;
	
	$sql = "SELECT produto, qtde_producao , mes_producao, ano_producao FROM tbl_producao_defeito WHERE fabrica={$fabrica} AND mes_producao={$mes_referente} AND ano_producao={$ano_referente} AND indice IS NULL";
	$res = pg_query($con, $sql);
	
	while($linha = pg_fetch_array($res)){
		extract($linha);
		$ano = $ano_producao;
		$mes_seguinte = $mes_producao+1;
		
		if($mes_seguinte > 12){
			$mes_seguinte = 01;
			$ano++;
		}
		
		$sql = "
			SELECT
			COUNT(*) as qtde_defeito 
			
			FROM tbl_os 
			
			WHERE fabrica={$fabrica} 
			AND produto={$produto} 
			AND data_digitacao 
			
			BETWEEN '{$ano_producao}-{$mes_producao}-01 00:00:00' 
			AND '{$ano}-{$mes_seguinte}-01 00:00:00'::timestamp - INTERVAL '1 SECOND'";
		extract(pg_fetch_assoc(pg_query($con, $sql)));
		//echo "$qtde_defeito, $qtde_producao<br/>";
		$IDC = $qtde_defeito;  // /$qtde_producao * 1000;
		$sql = "UPDATE tbl_producao_defeito SET indice={$IDC} WHERE fabrica={$fabrica} AND produto={$produto} AND mes_producao={$mes_producao} AND ano_producao={$ano_producao}";
		$result = pg_query($con, $sql);
		
	}
	

?>