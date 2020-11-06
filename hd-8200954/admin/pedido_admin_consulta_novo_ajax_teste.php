<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="auditoria";
$layout_menu = 'auditoria';
include "funcoes.php";

$pedido = intval($_GET["pedido"]);
$pedido_item = intval($_GET["pedido_item"]);
$peca	= intval($_GET['peca']);
$motivo = $_GET['motivo'];
$qtde   = intval($_GET['qtde']);
$posto  = intval($_GET['posto']);
$os_cancela = intval($_GET['os']);

if (strlen($pedido) > 0) {
	$sql = "
	SELECT
	tbl_pedido_item.pedido_item

	FROM
	tbl_pedido_item
	JOIN tbl_pedido USING(pedido)

	WHERE
	tbl_pedido.pedido=$pedido
	AND tbl_pedido.fabrica=$login_fabrica
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
	}
	else {
		$msg_erro = "Pedido não encontrado";
	}
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
	die;
}

switch($_GET['acao']) {
	case "pesquisaros":
		$sql = "
		SELECT
		tbl_os.os,
		tbl_os.sua_os,
		tbl_os_item.qtde,
		tbl_pedido_item.qtde_cancelada,
		tbl_pedido_item.qtde_faturada,
		tbl_pedido_item.qtde_faturada_distribuidor

		FROM
		tbl_os_item
		JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
		JOIN tbl_os ON tbl_os_produto.os=tbl_os.os
		LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item

		WHERE
		tbl_os_item.pedido=$pedido
		AND tbl_os_item.peca = $peca
		";
		$res = pg_query($con, $sql);

		$num_rows = pg_num_rows($res);
		$msg = "<table><tr><td>OS</td><td>Qtde</td>";

		$msg .= '<td>Canceladas</td><td>Faturadas</td>';

		$msg .= '</tr>';
		
		for ($i = 0; $i < $num_rows; $i++) {
			extract(pg_fetch_array($res));

			$msg .= "<tr><td><a href=os_press.php?os=$os target=_blank>$sua_os</a></td><td>$qtde</td>";
			
			
			$msg .= "<td>$qtde_cancelada</td>";
			if( $qtde_faturada_distribuidor > 0 ){
				$msg .= "<td>$qtde_faturada_distribuidor</td>";
				$soma = $qtde_cancelada + $qtde_faturada_distribuidor;
			}
			else{
				$msg .= "<td>$qtde_faturada</td>";
				$soma = $qtde_cancelada + $qtde_faturada;
			}
			
			if ($soma < $qtde) {
				$sqli = "SELECT tbl_pedido_item.pedido_item,
								tbl_pedido.posto
						FROM tbl_pedido_item
						LEFT JOIN tbl_pedido on tbl_pedido_item.pedido =tbl_pedido.pedido
						WHERE tbl_pedido_item.pedido=$pedido
						AND tbl_pedido_item.peca = $peca ";
				$resi = pg_query($con,$sqli);
				if(pg_num_rows($resi) > 0){
					$pedido_item = pg_fetch_result($resi,0,pedido_item);
					$posto       = pg_fetch_result($resi,0,posto);
				}
				
				$msg .= '<td align"=left" nowrap>';
				$msg .= '<form name="acao_'.$i.'">';
				$msg .= 'Qtde Cancelar <input type="text" size="5" name="qtde_a_cancelar" class="frm">';
				$msg .= 'Motivo: <input type="text" name="motivo" class="frm">';
				$msg .= ' <input type="button" value="Cancelar" onclick=" cancela_item('.$pedido_item.','.$pedido.',document.acao_'.$i.'.qtde_a_cancelar.value,document.acao_'.$i.'.motivo.value,'.$peca.','.$posto.','.$os.');"><font size="1">';
				$msg .= '</form>';
				$msg .= '</td>';
				
			}

			$msg .= '</tr>';
		}

		$msg .= "</table>";

		if ($num_rows == 0) {
			$msg = "Nenhuma OS encontrada";
		}
	break;

	case "pesquisarosfaturada" :
		$sql = '
			SELECT
			tbl_faturamento.nota_fiscal,
			SUM(tbl_faturamento_item.qtde) as qtde 

			FROM
			tbl_faturamento
			JOIN tbl_faturamento_item ON tbl_faturamento.faturamento=tbl_faturamento_item.faturamento

			WHERE
			tbl_faturamento_item.pedido = '.$pedido.'
			AND tbl_faturamento_item.peca='.$peca.'

			GROUP BY
			tbl_faturamento.nota_fiscal
		';
		$res = pg_query($con, $sql);

		$num_rows = pg_num_rows($res);
		$msg = "<table><tr><td>Nota Fiscal</td><td>Quantidade Atendida</td></tr>";
		
		for ($i = 0; $i < $num_rows; $i++) {
			extract(pg_fetch_array($res));

			$msg .= "<tr><td>$nota_fiscal</td><td>$qtde</td></tr>";
		}

		$msg .= "</table>";

		if ($num_rows == 0) 
			$msg = "Nenhuma Nota Encontrada";
		break;

	case "pesquisarpecacancelada" :
		$sql = "
			SELECT tbl_pedido_cancelado.os, 
				   to_char (tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data, 
				   tbl_pedido_cancelado.motivo, 
				   SUM(tbl_pedido_cancelado.qtde) AS qtde
			from tbl_pedido_cancelado 
			WHERE tbl_pedido_cancelado.pedido = ".$pedido."
			AND tbl_pedido_cancelado.peca = ".$peca."
			AND tbl_pedido_cancelado.os IS NOT NULL
			GROUP BY tbl_pedido_cancelado.os, tbl_pedido_cancelado.data,tbl_pedido_cancelado.motivo
		";
		
		$res = pg_query($con, $sql);

		$num_rows = pg_num_rows($res);
		$msg = "<table><tr><td>OS</td><td>Data</td><td>Motivo</td><td>Qtde</td></tr>";
		
		for ($i = 0; $i < $num_rows; $i++) {
			extract(pg_fetch_array($res));
			
			$msg .= "<tr><td><a href=os_press.php?os=$os target=_blank>$os</a></td>";

			$msg .= "<td>$data</td><td>$motivo</td><td>$qtde</td></tr>";
		}

		$msg .= "</table>";

		if ($num_rows == 0) 
			$msg = "Nenhuma Nota Encontrada";
		break;

	case "cancela_item":
		$sql_qtde = "SELECT tbl_pedido_item.qtde,
	                        tbl_pedido_item.qtde_faturada,
							tbl_pedido_item.qtde_cancelada
						FROM tbl_pedido_item
						WHERE tbl_pedido_item.pedido_item = ".$pedido_item."";
		$res_qtde = pg_query ($con,$sql_qtde);
		if(pg_num_rows($res_qtde) > 0){
			$atual_qtde = pg_fetch_result($res_qtde,0,qtde);
			$faturada       = pg_fetch_result($res_qtde,0,qtde_faturada);
			$cancelada       = pg_fetch_result($res_qtde,0,qtde_cancelada);
		}
		$total = $faturada + $cancelada + $qtde;
		if($atual_qtde < $total){
			$msg = '<script type="text/javascript">alert("Quantidade superior a quantidade atual");</script>';
		}

		
		/*
		else{
			$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = ".$qtde." WHERE pedido_item = $pedido_item;";
								
			$res = pg_query ($con,$sql);

			$sql = "INSERT INTO tbl_pedido_cancelado (
					pedido ,
					posto  ,
					fabrica,
					os     ,
					peca   ,
					qtde   ,
					motivo ,
					data
				)VALUES(
					$pedido,
					$posto,
					$login_fabrica,
					$os_cancela,
					$peca,
					$qtde,
					$motivo,
					current_date
				);";
			$res = @pg_query ($con,$sql);
		}	*/			
		$sql = "
		SELECT
		tbl_os.os,
		tbl_os.sua_os,
		tbl_os_item.qtde,
		tbl_pedido_item.qtde_cancelada,
		tbl_pedido_item.qtde_faturada,
		tbl_pedido_item.qtde_faturada_distribuidor

		FROM
		tbl_os_item
		JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
		JOIN tbl_os ON tbl_os_produto.os=tbl_os.os
		LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item

		WHERE
		tbl_os_item.pedido=$pedido
		AND tbl_os_item.peca = $peca
		";
		$res = pg_query($con, $sql);

		$num_rows = pg_num_rows($res);
		$msg .= "<table><tr><td>OS</td><td>Qtde</td>";

		$msg .= '<td>Canceladas</td><td>Faturadas</td>';

		$msg .= '</tr>';
		
		for ($i = 0; $i < $num_rows; $i++) {
			extract(pg_fetch_array($res));

			$msg .= "<tr><td><a href=os_press.php?os=$os target=_blank>$sua_os</a></td><td>$qtde</td>";
			
			
			$msg .= "<td>$qtde_cancelada</td>";
			if( $qtde_faturada_distribuidor > 0 ){
				$msg .= "<td>$qtde_faturada_distribuidor</td>";
				$soma = $qtde_cancelada + $qtde_faturada_distribuidor;
			}
			else{
				$msg .= "<td>$qtde_faturada</td>";
				$soma = $qtde_cancelada + $qtde_faturada;
			}
			
			if ($soma < $qtde) {
				$sqli = "SELECT tbl_pedido_item.pedido_item,
								tbl_pedido.posto
						FROM tbl_pedido_item
						LEFT JOIN tbl_pedido on tbl_pedido_item.pedido =tbl_pedido.pedido
						WHERE tbl_pedido_item.pedido=$pedido
						AND tbl_pedido_item.peca = $peca ";
				$resi = pg_query($con,$sqli);
				if(pg_num_rows($resi) > 0){
					$pedido_item = pg_fetch_result($resi,0,pedido_item);
					$posto       = pg_fetch_result($resi,0,posto);
				}
				
				$msg .= '<td align"=left" nowrap>';
				$msg .= '<form name="acao_'.$i.'">';
				$msg .= 'Qtde Cancelar <input type="text" size="5" name="qtde_a_cancelar" class="frm">';
				$msg .= 'Motivo: <input type="text" name="motivo" class="frm">';
				$msg .= ' <input type="button" value="Cancelar" onclick=" cancela_item('.$pedido_item.','.$pedido.',document.acao_'.$i.'.qtde_a_cancelar.value,document.acao_'.$i.'.motivo.value,'.$peca.','.$posto.','.$os.');"><font size="1">';
				$msg .= '</form>';
				$msg .= '</td>';
				
			}

			$msg .= '</tr>';
		}

		$msg .= "</table>";

		if ($num_rows == 0) {
			$msg = "Nenhuma OS encontrada";
		}
	break;

	default:
		$msg_erro = "Opção de ação inválida";
}

if (strlen($msg_erro)) {
	echo "$acao|erro|$msg_erro";
}
elseif (strlen($msg)) {
	echo "$acao|sucesso|$msg";
}

?>