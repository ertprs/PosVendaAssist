<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	$sql = "SELECT  os, sua_os, qtde_km, nome
		FROM tbl_os
		join tbl_fabrica using(fabrica)
		WHERE tbl_os.excluida is not true
		and fabrica <> 0 
		and qtde_km > 200
		and data_digitacao > current_timestamp - interval '1 hour' ";

	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){

		$msg .="<table><tr><td>OS</td>
			<td>FABRICA</td>
			<td>KM</td>
			</tr>";

		for($i = 0; $i < pg_num_rows($res); $i++){
			$sua_os		= pg_result($res,$i,'sua_os');
			$nome		= pg_result($res,$i,'nome');
			$qtde_km 	= pg_result($res,$i,'qtde_km');

			$msg .="<tr><td>$sua_os</td>
				<td>$nome</td>
				<td>$qtde_km</td>
				</tr>";
		}
		$msg .= "</table>";
		$vetC['dest'][] ='suporte@telecontrol.com.br';
		Log::envia_email($vetC,'OS com km mais de 200 no dia '.date('d/m/Y'),$msg);
	}
} catch (Exception $e) {
    echo $e->getMessage();
}
