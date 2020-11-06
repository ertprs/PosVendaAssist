<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';
if (isset($_GET['pedido_item'])) {
	$where = " pedido_item = {$_GET['pedido_item']}";
} else if (isset($_GET['pedido'])) {
	$where = " pedido = {$_GET['pedido']}";
}
$sqlItens = "	
	SELECT 	
		pedido_item,
		referencia,
		qtde,
		preco,
		qtde_faturada,
		qtde_cancelada,
		data_item::date,
		descricao
	FROM tbl_pedido_item
		JOIN tbl_peca USING (peca)
	WHERE $where";
$resItens = pg_query($con, $sqlItens);
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
	<link href="starter-template.css" rel="stylesheet">
	<script src="../../assets/js/ie-emulation-modes-warning.js"></script>
	<style type="text/css">
		.center {
			text-align: center;;
		}
		.left {
			text-align: left;
		}
	</style>
</head>

<body>
	<div class="container">
		<div id="accordion">
			<?php foreach (pg_fetch_all($resItens) as $item) {?>
				<div class="card">
					<div class="card-header" id="headingOne">
				     	<table class="table">
							<thead>
							<tr>
								<th scope="col" class="left">Peça Solicitada</th>
								<th scope="col" class="center">Qtde Faturada</th>
								<th scope="col" class="center">Qtde Cancelada</th>
								<th scope="col" class="center">Qtde Pedida</th>
							</tr>
							</thead>
							<tbody>
							<tr>
								<th scope="row"  class="left"><?php echo $item['referencia'] . ' - ' . $item['descricao'] ; ?></th>
								<td class="center"><?=$item['qtde_faturada']?></td>
								<td class="center"><?=$item['qtde_cancelada']?></td>
								<td class="center"><?=$item['qtde']?></td>
							</tr>
							</tbody>
						</table>
				    </div>
					<div id="<?=$item['peca']?>" class="show" aria-labelledby="headingOne" data-parent="#accordion" >
						<div class="card-body" style="padding-left: 5%;width: 95%;">
							<?php
								$sqlFaturados = "
									SELECT 
										referencia,
										descricao,
										qtde,
										preco,
										nota_fiscal,
										emissao,
										total_nota,
										conhecimento
									FROM tbl_faturamento_item
										JOIN tbl_peca USING (peca)
										JOIN tbl_faturamento USING (faturamento)
										LEFT JOIN tbl_transportadora USING (transportadora)
									WHERE tbl_faturamento_item.pedido_item = {$item['pedido_item']}
								";
								$resFaturados = pg_query($con, $sqlFaturados);
							?>
							<table class="table">
								<thead>
								<tr>
									<th scope="col" class="left">Peça(s) Enviadas</th>
									<th scope="col" class="center">Qtde</th>
									<th scope="col" class="center">preço</th>
									<th scope="col" class="center">Nota</th>
									<th scope="col" class="center">Emissao</th>
									<th scope="col" class="center">Conhecimento</th>
								</tr>
								</thead>
								<tbody>
								<?php foreach (pg_fetch_all($resFaturados) as $faturado) { ?>
								<tr>
									<th scope="row" class="left"><?php echo $faturado['referencia'] . ' - ' . $faturado['descricao'] ; ?></th>
									<td class="center"><?=$faturado['qtde']?></td>
									<td class="center">R$<?php echo number_format($faturado['preco'], 2, ',', '.');?></td>
									<td class="center"><?=$faturado['nota_fiscal']?></td>
									<td class="center"><?php echo date_format(date_create($faturado['emissao']), "d/m/Y" )?></td>
									<td class="center"><?php echo ($faturado['conhecimento']) ? "<a target='_blank' href='{$faturado['conhecimento']}'>Rastreio</a> " :  'Não tem'; ?></td>
								</tr>
								<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>			
	</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<script src="http://getbootstrap.com/assets/js/ie10-viewport-bug-workaround.js"></script>
</body>
</html>
