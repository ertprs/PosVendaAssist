<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';

use Posvenda\Pedido;

$pedido = $_GET["pedido"];

if ($_POST["gravar_valor_frete"]) {
	$pedido_itens = $_POST["pedido_item"];

	pg_query($con, "BEGIN");

	foreach ($pedido_itens as $i => $pedido_item) {
		$valor_frete = $_POST["valor_frete_unitario"][$i];

		if (!strlen($valor_frete)) {
			$msg_erro = "Informe o valor do frete";
			break;
		}

		$preco       = $_POST["preco"][$i];

		if (!strlen($valor_frete)) {
			$valor_frete = 0;
		}

		if (empty($pedido_item)) {
			$msg_erro = "Erro ao gravar valores de frete";
			break;
		} elseif(is_numeric($preco)) {
			$sql = "UPDATE tbl_pedido_item SET preco = {$preco}, acrescimo_financeiro = {$valor_frete}, total_item = total_item + {$valor_frete} WHERE pedido = $pedido AND pedido_item = {$pedido_item}";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				$msg_erro = "Erro ao gravar valores de frete";
				break;
			}
		}
	}

	if (!isset($msg_erro)) {
		$valor_frete_total = str_replace(",", ".", str_replace(".", "", $_POST["valor_frete_total"]));

		$sql = "UPDATE tbl_pedido SET valor_frete = {$valor_frete_total}, status_pedido = 23 WHERE pedido = {$pedido} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$sql = "SELECT posto FROM tbl_pedido WHERE fabrica = {$login_fabrica} AND pedido = {$pedido}";
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
					'Atualização nos valores do pedido: $pedido ',
					E'O pedido <a href=\'pedido_finalizado.php?pedido=$pedido\' target=\'_blank\' >$pedido</a> teve seus valores atualizados pela fábrica'
				)";
		$res = pg_query($con, $sql);

		pg_query($con, "COMMIT");

		$pedidoClass = new Pedido($login_fabrica, $pedido);
		$pedidoClass->totalizaPedido();
		
		$gravado = true;
	} else {
		pg_query($con, "ROLLBACK");
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
		<script src="plugins/price_format/jquery.price_format.1.7.min.js"></script>
		<script src="plugins/price_format/config.js"></script>

		<script>

		function number_format( number, decimals, dec_point, thousands_sep ) {
		    var n = number, prec = decimals;
		    n = !isFinite(+n) ? 0 : +n;
		    prec = !isFinite(+prec) ? 0 : Math.abs(prec);
		    var sep = (typeof thousands_sep == "undefined") ? ',' : thousands_sep;
		    var dec = (typeof dec_point == "undefined") ? '.' : dec_point;
		 
		    var s = (prec > 0) ? n.toFixed(prec) : Math.round(n).toFixed(prec); //fix for IE parseFloat(0.55).toFixed(0) = 0;
		 
		    var abs = Math.abs(n).toFixed(prec);
		    var _, i;
		 
		    if (abs >= 1000) {
		        _ = abs.split(/\D/);
		        i = _[0].length % 3 || 3;
		 
		        _[0] = s.slice(0,i + (n < 0)) +
		              _[0].slice(i).replace(/(\d{3})/g, sep+'$1');
		 
		        s = _.join(dec);
		    } else {
		        s = s.replace('.', dec);
		    }
		 
		    return s;
		}

		$(function() {
			$("input[name=valor_frete_total]").blur(function() {
				var valor_frete = parseFloat($(this).val().replace(/\./, "").replace(/,/, "."));

				var qtde = 0;

				$("td.qtde").each(function() {
					qtde += parseInt($.trim($(this).text()));
				});

				var valor_frete_unitario = valor_frete / qtde;

				var total_pedido = 0;

				$("tr.peca").each(function() {
					var preco_base  = parseFloat($(this).find("td.preco_base").text().replace(/\./, "").replace(/,/, "."));	
					var preco_frete = valor_frete_unitario + preco_base;
					var qtde        = parseInt($.trim($(this).find("td.qtde").text()));
					var total       = preco_frete * qtde;
					total_pedido    += total;

					$(this).find("input.preco_input").val(preco_frete.toFixed(2));
					$(this).find("input.valor_frete_input").val(valor_frete_unitario.toFixed(2));
					$(this).find("td.valor_frete").text(number_format(valor_frete_unitario, 2, ",", "."));
					$(this).find("td.preco_frete").text(number_format(preco_frete, 2, ",", "."));
					$(this).find("td.total").text(number_format(total, 2, ",", "."));
				});

				$("td.total_pedido").text(number_format(total_pedido, 2, ",", "."));

				if ($("span.desconto").length > 0) {
					var desconto = $.trim($("span.desconto").text());
					var total_pedido_desconto = total_pedido - ((total_pedido / 100) * parseFloat(desconto));

					$("td.total_pedido_desconto").text(number_format(total_pedido_desconto, 2, ",", "."));
				}
			});
		});

		</script>
	</head>
	<body>
		<div class="container" style="width: 100%;" >
			<h1>Pedido <?=$pedido?></h1>

			<?php if (isset($msg_erro)) { ?>
				<div class="alert alert-error" ><h4><?=$msg_erro?></h4></div>
			<?php } else if ($gravado == true) { ?>
				<div class="alert alert-success" ><h4>Os valores de fretes foram gravados com sucesso</h4></div>
				<script>
					setTimeout(function() {
						$(window.parent.document).find("#pedido_<?=$pedido?>").find("td").last().html("<div class='alert alert-success' style='margin-bottom: 0px;'>Valor do frete gravado com sucesso</div>");
						window.parent.Shadowbox.close();
					}, 2000);
				</script>
			<?php } ?>

			<?php if (!isset($gravado)) { ?>
				<div class="row-fluid" >
					<form method="post" >
						<table class="table table-bordered">
							<thead>
								<tr class="titulo_coluna" >
									<th>Peça</th>
									<th>Quantidade</th>
									<th>Preço Unitário</th>
									<th>Valor do Frete</th>
									<th>Preço + Frete</th>
									<th>Total</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$sql = "SELECT 
											tbl_pedido_item.pedido_item,
											(tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS peca,
											(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) AS qtde,
											tbl_pedido_item.preco_base AS preco,
											tbl_pedido_item.acrescimo_financeiro  AS valor_frete_unitario
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
										
										if (!isset($_POST["valor_frete_unitario"][$i])) {
											$valor_frete = pg_fetch_result($res, $i, "valor_frete_unitario");

											if (strlen($valor_frete) > 0) {
												$valor_frete = number_format($valor_frete, 2, ",", ".");
											}
										} else {
											$valor_frete = $_POST["valor_frete_unitario"][$i];
										}

										$total = (moneyDB($preco) + moneyDB($valor_frete)) * $qtde;
										$total_pedido += $total;

										echo "
										<tr class='peca'>
											<td>
												{$peca}
												<input type='hidden' name='pedido_item[{$i}]' value='{$pedido_item}' />
												<input type='hidden' class='preco_input' name='preco[{$i}]' value='{$_POST['preco'][$i]}' />
												<input type='hidden' class='valor_frete_input' name='valor_frete_unitario[{$i}]' value='{$_POST['valor_frete_unitario'][$i]}' />
											</td>
											<td class='tac qtde' >{$qtde}</td>
											<td class='tac preco_base' >{$preco}</td>
											<td class='tac valor_frete' >{$valor_frete}</td>
											<td class='tac preco_frete' >".number_format(($preco + $valor_frete), 2, ",", ".")."</td>
											<td class='tac total' >".number_format($total, 2, ",", ".")."</td>
										</tr>
										";
									}
								} else {
									echo "<tr class='error'><th colspan='6'>Não foi encontrado peças no pedido</th></tr>";
								}
								?>
							</tbody>
							<tfoot>
								<tr>
									<td colspan="5" class="tac">
										Valor do Frete
										<input type='text' name='valor_frete_total' class='span2 tar' price='true' style='margin-bottom: 0px;' value='<?=$_POST['valor_frete_total']?>' />
									</td>
									<td class="tac total_pedido" ><?=number_format($total_pedido, 2, ",", ".")?></td>
								</tr>
								<?php
								if (in_array($login_fabrica, array(138))) {
									$sql = "SELECT desconto FROM tbl_pedido WHERE pedido = $pedido";
									$res = pg_query($con, $sql);

									$desconto = pg_fetch_result($res, 0, "desconto");

									if ($desconto > 0) {
										$total_pedido_desconto = $total_pedido - (($total_pedido / 100) * $desconto);
									?>
										<tr>
											<td colspan="5" class="tac">
												Desconto (<span class='desconto'><?=$desconto?></span>%)
											</td>
											<td class="tac total_pedido_desconto" ><?=number_format($total_pedido_desconto, 2, ",", ".")?></td>
										</tr>
									<?php
									}
								}
								?>
								<tr>
									<td colspan="6" class="tac">
										<span style="color: #B94A48; font-weight: bold;" >
											Se o campo valor de frete estiver em branco o valor gravado será 0<br />
											Ao gravar os valores o pedido irá voltar para o posto autorizado para a aprovação de valores e anexo de comprovante de pagamento
										</span><br />
										<input type="submit" class="btn btn-primary" name="gravar_valor_frete" value="Gravar valores de frete" />
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
