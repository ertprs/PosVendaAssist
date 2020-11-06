<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
if (isset($_POST['ajax'])) {
	if (isset($_POST['estado']) && !isset($_POST['posto'])) {
		$sql = "SELECT 	tbl_posto_fabrica.posto,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome,
						sum(tbl_pedido_item.qtde) AS qtde_pecas
				FROM tbl_pedido
					JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
					JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				WHERE tbl_pedido.fabrica = {$login_fabrica}
					AND tbl_pedido.finalizado IS NOT NULL
					AND tbl_pedido.status_pedido <> 14
					AND tbl_posto_fabrica.contato_estado = '{$_POST['estado']}'
					AND tbl_pedido.data BETWEEN '{$_POST['inicio']}' and '{$_POST['fim']}'
				GROUP BY tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto_fabrica.posto
				ORDER BY qtde_pecas DESC
				LIMIT 10";
		$res = pg_query($con,$sql);
		$trs = '';
		if (pg_num_rows($res) > 0) {
			$trs .= "<tr data_estado='{$_POST['estado']}'>";
			$trs .= "<td style='background-color: #2A78AA;'><b>Código Posto</b></td>";
			$trs .= "<td style='background-color: #2A78AA;'><b>Nome Posto</b></td>";
			$trs .= "<td style='background-color: #2A78AA;'><b>Quantidade</b></td>";
			$trs .= "</tr>";
			foreach (pg_fetch_all($res) as $posto) {
				$trs .= "<tr data_estado='{$_POST['estado']}' style='cursor: pointer;' data-posto='{$posto['posto']}'>";
				$trs .= "<td style='background-color: #2A78AA;'>{$posto['codigo_posto']}</td>";
				$trs .= "<td style='background-color: #2A78AA;'>{$posto['nome']}</td>";
				$trs .= "<td style='background-color: #2A78AA;'>{$posto['qtde_pecas']}</td>";
				$trs .= "</tr>";
			}
		} else {
			$trs .= "<tr data_estado='{$_POST['estado']}'>>";
			$trs .= "<td colspan='3'><b>Nenhum Posto Encontrado</b></td>";
			$trs .= "</tr>";
		}
		exit($trs);
	} 
	if (isset($_POST['posto']) && isset($_POST['estado'])) {
		$sql = "SELECT 	tbl_peca.referencia,
						tbl_peca.descricao, 
						SUM(tbl_pedido_item.qtde) AS qtde_peca
				FROM tbl_pedido
					JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
					JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
				WHERE tbl_pedido.fabrica = {$login_fabrica}
					AND tbl_pedido.posto = {$_POST['posto']}
					AND tbl_pedido.finalizado IS NOT NULL
					AND tbl_pedido.data BETWEEN '{$_POST['inicio']}' and '{$_POST['fim']}'
				GROUP BY tbl_peca.referencia, tbl_peca.descricao
				ORDER BY qtde_peca DESC
				LIMIT 10";
		$res = pg_query($con,$sql);
		$trs = '';
		if (pg_num_rows($res)) {
			$trs .= "<tr data_estado='{$_POST['estado']}' data_posto='{$_POST['posto']}'>";
			$trs .= "<td><b>Código Produto</b></td>";
			$trs .= "<td><b>Nome Produto</b></td>";
			$trs .= "<td><b>Quantidade</b></td>";
			$trs .= "</tr>";
			foreach (pg_fetch_all($res) as $peca) {
				$trs .= "<tr data_estado='{$_POST['estado']}' data_posto='{$_POST['posto']}'>";
				$trs .= "<td>{$peca['referencia']}</td>";
				$trs .= "<td>{$peca['descricao']}</td>";
				$trs .= "<td>{$peca['qtde_peca']}</td>";
				$trs .= "</tr>";
			}
		} else {
			$trs .= "<tr>";
			$trs .= "<td><b>Nenhum Peça Encontrado</b></td>";
			$trs .= "</tr>";
		}
		exit($trs);
	} 
} 
if (isset($_GET['relatorio'])) {
	$sqlEstado = "
		SELECT	contato_estado,
				nome
		FROM tbl_posto_fabrica 
		JOIN tbl_estado ON tbl_estado.estado = tbl_posto_fabrica.contato_estado
		WHERE fabrica = $login_fabrica
				AND credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO') 
		GROUP BY contato_estado, nome";
	$resEstado = pg_query($con,$sqlEstado);

	foreach (pg_fetch_all($resEstado) as $estado) {
		$table .= "{$estado['nome']};\n";
		$sqlPosto = "SELECT tbl_posto_fabrica.posto,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							sum(tbl_pedido_item.qtde) AS qtde_pecas
					FROM tbl_pedido
						JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
						JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
					WHERE tbl_pedido.fabrica = {$login_fabrica}
						AND tbl_pedido.finalizado IS NOT NULL
						AND tbl_pedido.status_pedido <> 14
						AND tbl_posto_fabrica.contato_estado = '{$estado['contato_estado']}'
						AND tbl_pedido.data BETWEEN '{$inicio}' and '{$fim}'
					GROUP BY tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto_fabrica.posto
					ORDER BY qtde_pecas DESC
					LIMIT 10";
		$resPosto = pg_query($con,$sqlPosto);
		if (pg_num_rows($resPosto)) {			
			foreach (pg_fetch_all($resPosto) as $posto) {
				$table .= "Código Posto;Nome Posto;Quantidade;\n";
				$table .= "{$posto['codigo_posto']};{$posto['nome']};{$posto['qtde_pecas']};\n";
				$sqlProd = "SELECT 	tbl_peca.referencia,
								tbl_peca.descricao, 
								SUM(tbl_pedido_item.qtde) AS qtde_peca
						FROM tbl_pedido
							JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
							JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
						WHERE tbl_pedido.fabrica = {$login_fabrica}
							AND tbl_pedido.posto = {$posto['posto']}
							AND tbl_pedido.finalizado IS NOT NULL
							AND tbl_pedido.data BETWEEN '{$inicio}' and '{$fim}'
						GROUP BY tbl_peca.referencia, tbl_peca.descricao
						ORDER BY qtde_peca DESC
						LIMIT 10";
				$resProd = pg_query($con,$sqlProd);
				if (pg_num_rows($resProd)) {
					$table .= "Código Produto;Nome Produto;Quantidade;\n";
					foreach (pg_fetch_all($resProd) as $produto) {
						$table .= "{$produto['referencia']};{$produto['descricao']};{$produto['qtde_peca']};\n";
					}
				} else {
					$table .= "Nenhum Peça Encontrado;\n";
				}
			}
		} else {
			$table .= "Nenhum Posto Encontrado;\n";
		}
	}

	header("Content-type: application/vnd.ms-excel");
	header("Content-type: application/force-download"); 
	header("Content-Disposition: attachment; filename=planilha.csv"); 
	header("Pragma: no-cache");

	echo utf8_encode($table);
	exit;
}
