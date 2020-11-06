<?php
	
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
	if($_GET['ajax'] == 's' && !empty($_GET['pedido']) && ($_GET['rejeitar']== 's' || $_GET['aceitar'] == 's' ) ) {
		$pedido = (int) $_GET['pedido'];
		if(empty($pedido)) {
			echo 'Pedido Inválido';
			exit;
		}
		// @todo ações do botao
		$sql = "SELECT exportado, aprovado_cliente, posto, valores_adicionais
				FROM tbl_pedido WHERE pedido = " . $pedido . " AND fabrica = " . $login_fabrica;
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)) {
			$posto = pg_result($res,0,2);

			$exportado 	= pg_result($res,0,0);
			$aprovado 	= pg_result($res,0,1);
			if(!empty($exportado) && !empty($aprovado)) {
				echo 'Pedido já confirmado anteriormente.';
				exit;
			}
			if($_GET['aceitar'] == 's') { 
				$campo_cond = "";
				$vl = json_decode(pg_result($res,0,3), true);
				if (isset($vl['nova_condicao'])) {
					if ($vl['nova_condicao'] == 1) {
						$sql_cond = "SELECT condicao FROM tbl_condicao WHERE fabrica = $login_fabrica AND codigo_condicao = 'VIS' LIMIT 1";
						$res_cond = pg_query($con, $sql_cond);
						if (pg_num_rows($res_cond) > 0) {
							$campo_cond = ", condicao = ".pg_fetch_result($res_cond, 0, 'condicao');
						}
					} else {
						$sql_cond = "SELECT condicao FROM tbl_condicao WHERE fabrica = $login_fabrica AND codigo_condicao = 'PRZ' LIMIT 1";
						$res_cond = pg_query($con, $sql_cond);
						if (pg_num_rows($res_cond) > 0) {
							$campo_cond = ", condicao = ".pg_fetch_result($res_cond, 0, 'condicao');
						}
					}
				}

				$sql = "UPDATE tbl_pedido
						SET aprovado_cliente = CURRENT_TIMESTAMP,
						exportado = NOW(),
						status_pedido = 20,
						tipo_pedido = 196 
						$campo_cond
						WHERE fabrica = " . $login_fabrica . 
						" AND pedido = " . $pedido;

					if (!empty($campo_cond)) {
						$sql .= "; UPDATE tbl_pedido_item SET condicao = ".pg_fetch_result($res_cond, 0, 'condicao')." WHERE pedido = $pedido;";
					}

					$msg = "Confirmado";
			}
			else if($_GET['rejeitar'] == 's' ) { // @todo
				$sql = "SELECT peca, qtde -(qtde_cancelada + qtde_faturada) 
						FROM tbl_pedido_item 
						WHERE pedido = $pedido
						AND qtde > (qtde_cancelada + qtde_faturada)";
				$res = pg_query($con,$sql);
				for($i=0;$i<pg_num_rows($res);$i++) {
					$peca = pg_result($res,$i,0);
					$qtde = pg_result($res,$i,1);
					$sql_cancela = "INSERT INTO tbl_pedido_cancelado(pedido,peca,fabrica,data,posto,qtde) values($pedido,$peca,$login_fabrica,current_date, $posto,$qtde)";
					$res_cancela = pg_query($con,$sql_cancela);
				}
				$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde - (qtde_cancelada + qtde_faturada)
						WHERE pedido =" .$pedido." 
						AND qtde > (qtde_cancelada + qtde_faturada)";
				$res = pg_query($con,$sql);
				$sql = "INSERT INTO tbl_pedido_status(pedido,observacao,status)
						VALUES($pedido, 'Pedido Rejeitado pelo Posto', 14);
						UPDATE tbl_pedido
						SET status_pedido = 21,
						aprovado_cliente = NOW()
						WHERE pedido = $pedido AND fabrica = $login_fabrica;";
				$msg = "Pedido Rejeitado com Sucesso";
			}
			$res = pg_query($con,$sql);
			echo $msg;	

		}
		else
			echo 'Pedido não Encontrado';
	}
	else
		echo 'Falha na Requisição, contate o Suporte';

?>
