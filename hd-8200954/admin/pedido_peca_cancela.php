<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\Pedido;

$pedido = $_GET["pedido"];

if ($_POST["cancela_parcial"]) {
	$pedido_itens = $_POST["pedido_item"];

	pg_query($con, "BEGIN");

	$total_qtde = 0;
	$total_qtde_cancelar = 0;
	$peca = array();

	foreach ($pedido_itens as $i => $pedido_item) {
		$total_qtde          += $qtde = $_POST["qtde"][$i];
		$total_qtde_cancelar += $qtde_cancelar = $_POST["qtde_cancelar"][$i];

		if ($qtde_cancelar > $qtde) {
			$msg_erro = "A qtde a cancelar não pode ser maior que a qtde de peças";
			break;
		}

		$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = $qtde_cancelar WHERE pedido = $pedido AND pedido_item = $pedido_item";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro = "Erro ao cancelar peças";
			break;
		} else {
			$sql = "SELECT tbl_pedido.posto, tbl_pedido_item.peca 
					FROM tbl_pedido_item 
					INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido 
					WHERE tbl_pedido_item.pedido = $pedido 
					AND tbl_pedido_item.pedido_item = $pedido_item";
			$res = pg_query($con, $sql);

			$posto = pg_fetch_result($res, 0, "posto");
			$peca[$i]  = pg_fetch_result($res, 0, "peca");

			$sql = "INSERT INTO tbl_pedido_cancelado
					(pedido, posto, fabrica, peca, qtde, motivo, data)
					VALUES
					($pedido, $posto, $login_fabrica, {$peca[$i]}, $qtde_cancelar, 'Pedido cancelado pela Fábrica', current_date)";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro = "Erro ao cancelar peças";
				break;
			}
		}
	}

	if (!isset($msg_erro)) {
		$sql = "SELECT posto FROM tbl_pedido WHERE pedido = $pedido";
		$res = pg_query($con, $sql);

		$posto = pg_fetch_result($res, 0, "posto");

		if ($total_qtde == $total_qtde_cancelar) {
			$sql = "UPDATE tbl_pedido SET status_pedido = 14 WHERE pedido = $pedido";
			$res = pg_query($con, $sql);

			$sql = "INSERT INTO tbl_comunicado (
						fabrica,
						posto,
						obrigatorio_site,
						tipo,
						ativo,
						descricao,
						mensagem
					) VALUES (
						{$login_fabrica},
						{$posto},
						true,
						'Com. Unico Posto',
						true,
						'Pedido $pedido cancelado',
						'O pedido <a href=\'pedido_finalizado.php?pedido=$pedido\' target=\'_blank\' >$pedido</a> foi cancelado pela Fábrica'
					)";
			$res = pg_query($con, $sql);

			$cancelado_total = true;
		} else {
			$comunicado_mensagem = "";

			foreach ($pedido_itens as $i => $pedido_item) {
				$qtde_cancelar = $_POST["qtde_cancelar"][$i];
				
				$sql = "SELECT referencia FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$peca[$i]}";
				$res = pg_query($con, $sql);

				$item = pg_fetch_result($res, 0, "referencia");

				$comunicado_mensagem .= "{$item} quantidade cancelada {$qtde_cancelar}<br />";
			}

			$sql = "INSERT INTO tbl_comunicado (
						fabrica,
						posto,
						obrigatorio_site,
						tipo,
						ativo,
						descricao,
						mensagem
					) VALUES (
						{$login_fabrica},
						{$posto},
						true,
						'Com. Unico Posto',
						true,
						'Pedido $pedido peças canceladas',
						'O pedido <a href=\'pedido_finalizado.php?pedido=$pedido\' target=\'_blank\' >$pedido</a> foi cancelado parcialmente pela Fábrica:<br />$comunicado_mensagem'
					)";
			$res = pg_query($con, $sql);

			$cancelado_parcial = true;
		}

		pg_query($con, "COMMIT");

		$pedidoClass = new Pedido($login_fabrica, $pedido);
		$pedidoClass->totalizaPedido();
	} else {
		pg_query($con, "ROLLBACK");
	}
}

if ($_POST["cancela_total"]) {
	$sql = "UPDATE tbl_pedido SET status_pedido = 14 WHERE pedido = $pedido;

			UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido = $pedido;
			
			INSERT INTO tbl_pedido_cancelado 
			(pedido, posto, fabrica, peca, qtde, motivo, data)
			SELECT 
				pedido, posto, fabrica, peca, qtde, 'Pedido cancelado pela Fábrica', current_date
			FROM tbl_pedido 
			INNER JOIN tbl_pedido_item USING(pedido) 
			WHERE pedido = $pedido;";
	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		$msg_erro = "Erro ao cancelar pedido";
	} else {
		$sql = "SELECT posto FROM tbl_pedido WHERE pedido = $pedido";
		$res = pg_query($con, $sql);

		$posto = pg_fetch_result($res, 0, "posto");

		$sql = "INSERT INTO tbl_comunicado (
					fabrica,
					posto,
					obrigatorio_site,
					tipo,
					ativo,
					descricao,
					mensagem
				) VALUES (
					{$login_fabrica},
					{$posto},
					true,
					'Com. Unico Posto',
					true,
					'Pedido $pedido cancelado',
					'O pedido <a href=\'pedido_finalizado.php?pedido=$pedido\' target=\'_blank\' >$pedido</a> foi cancelado pela Fábrica'
				)";
		$res = pg_query($con, $sql);

		$pedidoClass = new Pedido($login_fabrica, $pedido);
		$pedidoClass->totalizaPedido();

		$cancelado_total = true;
	}
}

?>

<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/jquery.alphanumeric.js"></script>

		<script>

		$(function() {
			$("input.numeric").numeric();
		});

		</script>
	</head>
	<body>
		<div class="container" style="width: 100%;" >
			<h1>Pedido <?=$pedido?></h1>

			<?php if (isset($msg_erro)) { ?>
				<div class="alert alert-error" ><h4><?=$msg_erro?></h4></div>
			<?php } else if ($cancelado_total == true) { ?>
				<div class="alert alert-success" ><h4>Pedido Cancelado</h4></div>
				<script>
					setTimeout(function() {
						$(window.parent.document).find("#pedido_<?=$pedido?>").find("td").last().html("<div class='alert alert-danger' style='margin-bottom: 0px;'>Pedido Cancelado</div>");
						window.parent.Shadowbox.close();
					}, 2000);
				</script>
			<?php } else if ($cancelado_parcial == true) { ?>
				<div class="alert alert-success" ><h4>Peças Canceladas</h4></div>
			<?php } ?>

			<?php if (!isset($cancelado_total)) { ?>
				<div class="row-fluid" >
					<form method="post" >
						<table class="table table-bordered" style="table-layout: fixed;">
							<thead>
								<tr class="titulo_coluna" >
									<th>Peça</th>
									<th>Quantidade</th>
									<th>Quantidade a Cancelar</th>
									<th>Preço Unitário</th>
									<th>Total</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$sql = "SELECT 
											tbl_pedido_item.pedido_item,
											(tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS peca,
											(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) AS qtde,
											tbl_pedido_item.preco_base AS preco
										FROM tbl_pedido_item
										INNER JOIN tbl_peca ON tbl_peca.fabrica = {$login_fabrica} AND tbl_peca.peca = tbl_pedido_item.peca
										INNER JOIN tbl_pedido ON tbl_pedido.fabrica = {$login_fabrica} AND tbl_pedido.pedido = tbl_pedido_item.pedido
										WHERE tbl_pedido.pedido = {$pedido}";
								$res = pg_query($con, $sql);

								$rows = pg_num_rows($res);

								$total_pedido = 0;

								if ($rows > 0) {
									for ($i = 0; $i < $rows; $i++) { 
										$pedido_item = pg_fetch_result($res, $i, "pedido_item");
										$peca        = pg_fetch_result($res, $i, "peca");
										$qtde        = pg_fetch_result($res, $i, "qtde");
										$preco       = pg_fetch_result($res, $i, "preco");

										$preco       = number_format($preco, 2, ",", ".");

										$total = moneyDB($preco) * $qtde;
										$total_pedido += $total;

										echo "
										<tr class='peca'>
											<td>
												{$peca}
												<input type='hidden' name='pedido_item[{$i}]' value='{$pedido_item}' />
												<input type='hidden' name='qtde[{$i}]' value='$qtde' />
											</td>
											<td class='tac qtde' >{$qtde}</td>
                                            <td class='tac qtde' >";
                                        if ($qtde > 0) {
                                            echo "
                                                <input type='text' class='span4 numeric' maxlength='3' name='qtde_cancelar[{$i}]' value='{$_POST['qtde_cancelar'][$i]}' />";
                                        } else {
                                            echo "&nbsp;";
                                        }
                                        echo "
											</td>
											<td class='tac preco_base' >{$preco}</td>
											<td class='tac total' >".number_format($total, 2, ",", ".")."</td>
										</tr>
										";
									}
								} else {
									echo "<tr class='error'><th colspan='5'>Não foi encontrado peças no pedido</th></tr>";
								}
								?>
							</tbody>
							<tfoot>
								<tr>
									<td colspan="5" class="tac">
										<span style="color: #B94A48; font-weight: bold;" >
											O cancelamento parcial irá cancelar a quantidade digitada no campo "qtde a cancelar" de cada peça<br />
											Caso no cancelamento parcial seja cancelado todas as peças o pedido é cancelado<br />
											O cancelamento total cancela o pedido de peças
										</span><br />
										<input type="submit" class="btn btn-warning" name="cancela_parcial" value="Cancelar Parcial" />
										<input type="submit" class="btn btn-danger" name="cancela_total" value="Cancelar Total" />
									</td>
								</tr>
							</tfoot>
						</table>
					</form>
				</div>
			<?php } ?>
		</div>
	</body>
</html>
