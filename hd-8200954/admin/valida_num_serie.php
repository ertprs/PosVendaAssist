<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

	if(isset($_POST["btnacao"])){

		$serie 		= $_POST["serie"];
		$produto 	= $_POST["produto"];
		$callcenter = $_POST["callcenter"];

		if(!empty($callcenter)) {
			$cond = " and tbl_hd_chamado.hd_chamado <> $callcenter ";
		}

		$sql = "select tbl_hd_chamado.data, tbl_admin.nome_completo, 
				tbl_hd_chamado.hd_chamado, 				
				tbl_hd_chamado_item.serie,
				(select status_item from tbl_hd_chamado_item where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado order by data desc limit 1) as status,
				(select data from tbl_hd_chamado_item where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado order by data desc limit 1) as data_resolvido 
				
				from tbl_hd_chamado
				inner join tbl_hd_chamado_item on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado 
				inner join tbl_admin on tbl_admin.admin = tbl_hd_chamado.atendente and tbl_admin.fabrica = $login_fabrica
				inner join tbl_produto on tbl_produto.produto = tbl_hd_chamado_item.produto
				where tbl_hd_chamado.fabrica = $login_fabrica and tbl_hd_chamado_item.serie = '$serie' and tbl_hd_chamado.data between current_timestamp -interval '2 years' and current_timestamp and  tbl_produto.referencia = '$produto'
				$cond
				order by tbl_hd_chamado.data desc limit 1";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res)>0){
			$status 			= pg_fetch_result($res, 0, status);
			$hd_chamado 		= pg_fetch_result($res, 0, hd_chamado);
			$serie 				= pg_fetch_result($res, 0, serie);
			$nome_completo		= pg_fetch_result($res, 0, nome_completo);
			$data_resolvido 	= substr(pg_fetch_result($res, 0, data_resolvido), 0, 10);
			$data 				= substr(pg_fetch_result($res, 0, data), 0, 10);

			list($ynf, $mnf, $dnf) = explode("-", $data);
			$data = $dnf."-".$mnf."-".$ynf;


			if(strlen(trim($data_resolvido))>0){
				$data_inicial = DateTime::createFromFormat('Y-m-d', $data_resolvido);
				$data_final = DateTime::createFromFormat('Y-m-d', date("Y-m-d"));
				$diferenca = $data_final->diff($data_inicial);
				$intervalo = $diferenca->format("%a");
			}else{
				$intervalo = 0;
			}

			$dados = array("hd_chamado" =>$hd_chamado, 'serie'=>$serie, 'qtd_dias' => $intervalo, 'status' => $status, 'atendente' => $nome_completo, 'data_abertura'=> $data);

			echo json_encode($dados);

		}
	}

?>
