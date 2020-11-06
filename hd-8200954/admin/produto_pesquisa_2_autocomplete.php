<?php

/*Funчуo ajax autocomplete, chamada da tela admin_cliente/pre_os_cadastro_sac.php
quando o usuсrio digita nos campos "descriчуo" ou "modelo"
*/

include "dbconfig.php";
include "includes/dbconnect-inc.php";
//include 'autentica_admin.php';

//ATEЧТO: Script trabalhando somente para o cliente Filizola, $login_fabrica = 7;
$login_fabrica = 7 ;
$term = $_GET['term'];

if($term !=""){

	$sql = "select referencia,descricao, 
		case when referencia = '4006430' then '398/2012'
		else '399/2012' 
		end as contrato
	 	from tbl_produto where fabrica_i = ".$login_fabrica." and
			(descricao ilike('%$term%') or referencia ilike('%$term%')) and referencia in('4006430','4006477')";
	

	$resource = pg_query($sql);	
	if($resource!= false){		
		$res = pg_fetch_all($resource);
		for($i=0;$i<count($res);$i++){
			$arrayres[$i] = array(
								"referencia"=>$res[$i]['referencia'],
								"descricao"=>$res[$i]['descricao'],
								"contrato"=>$res[$i]['contrato']);
		}
		
		$arrayresult[] = json_encode($arrayres);
		echo json_encode($arrayresult);
	}else{
		$arrayresult[] = json_encode(array("erro",0));
	}
}else{
	$arrayresult[] = json_encode(array("erro","Digite uma descriчуo ou um modelo para realizar a pesquisa"));
}

exit;
?>