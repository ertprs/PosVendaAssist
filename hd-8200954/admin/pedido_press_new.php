<?php 

function geraDataNormal($data) {
	list($ano, $mes, $dia) = explode("-", $data);
	return $dia."/".$mes."/".$ano;
}

$sqlPrincipal = "SELECT tbl_pedido.*, 
						TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY HH24:MI:SS') AS data_pedido,
						tbl_posto.nome AS nome_posto, 
						tbl_posto_fabrica.codigo_posto,
						tbl_status_pedido.descricao AS status_pedido_descricao
				   FROM tbl_pedido 
				   JOIN tbl_status_pedido ON tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
				   JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} 
				   JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto 
				  WHERE tbl_pedido.fabrica = {$login_fabrica} 
					AND tbl_pedido.pedido = {$pedido}";
$resPrincipal = pg_query($con, $sqlPrincipal);
if (pg_num_rows($resPrincipal) == 0) {
	$msg_erro["msg"][] = "Pedido Nº {$pedido} não encontrado";
}

$admin_privilegios 	= "call_center";
$layout_menu 		= "callcenter";
$title 				= "CONFIRMAÇÃO DE PEDIDO DE PEÇAS";

include "cabecalho_new.php"; 
?>
<style>
	.formulario{
    	background-color: #D9E2EF;
	}
	.formulario h2{
    	font-size: 15px;
    	padding: 10px;
	}
	.box{
		padding: 20px;
	}
	.red{
		color: red;
	}
	.titulo_total{
		background-color: #596d9b !important;
	    font: bold 11px "Arial";
	    color: #FFFFFF !important;
	}
</style>
<div class="container">
	<?php if (count($msg_erro["msg"]) > 0) {?>
		<div class="alert alert-warning"><h4><?php echo implode("<br>", $msg_erro["msg"]);?></h4></div>
	<?php 
		include "rodape.php";
		exit;
	?>
	<?php }?>
	<?php
		$rows = pg_fetch_assoc($resPrincipal);
	?>
	<div class="formulario">
		<h2 class="titulo_coluna">Dados do Pedido</h2>
		<div class="box">
			<div class="row-fluid">
				<div class="span2">
					<b>Pedido</b><br />
					<?php echo $rows["pedido"];?>
				</div>
				<div class="span2">
					<b>Pedido Cliente </b><br />
					<?php echo $rows["pedido_cliente"];?>
				</div>
				<div class="span2">
					<b>Pedido SAP</b><br />
					<?php echo $rows["seu_pedido"];?>
				</div>
				<div class="span3">
					<b>Data</b><br />
					<?php echo $rows["data_pedido"];?>
				</div>
				<div class="span3">
					<b>Status Pedido</b><br />
					<?php echo $rows["status_pedido_descricao"];?>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span2">
					<b>Posto</b><br />
					<?php echo $rows["codigo_posto"];?>
				</div>
				<div class="span10">
					<b>Razão Social </b><br />
					<?php echo $rows["nome_posto"];?>
				</div>
			</div>
		</div>
		<table class="table table-bordered table-striped">
			<thead>
				<tr class="titulo_coluna">
					<th>Componente</th>
					<th>Qtde</th>
					<th>Qtde Cancelada</th>
					<th>Qtde Faturada</th>
					<th>Pendência do Pedido</th>
					<th>IPI</th>
					<th>Preço Unitário</th>
					<th>Total c/ IPI</th>
				</tr>
			</thead>
			<tbody>
				<?php 

					$sqlPrincipalItens = "SELECT 
											tbl_pedido_item.qtde, 
											COALESCE(tbl_pedido_item.qtde_cancelada, 0) AS qtde_cancelada, 
											COALESCE(tbl_pedido_item.qtde_faturada, 0) AS qtde_faturada, 
											COALESCE(tbl_pedido_item.ipi, 0) AS ipi, 
											COALESCE(tbl_pedido_item.preco, 0) AS preco, 
											COALESCE(COALESCE(tbl_pedido_item.qtde,0)-(COALESCE(tbl_pedido_item.qtde_cancelada,0)+COALESCE(tbl_pedido_item.qtde_faturada,0)), 0) AS pecas_pendentes,
											((COALESCE(tbl_pedido_item.qtde,0)-COALESCE(tbl_pedido_item.qtde_cancelada, 0))*COALESCE(tbl_pedido_item.preco,0))*(1+(COALESCE(tbl_pedido_item.ipi, 0)/100)) AS total_com_ipi,
											tbl_peca.descricao AS nome_peca, 
											tbl_peca.referencia AS referencia_peca
									   FROM tbl_pedido_item 
									   JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido 
									   JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca  AND tbl_peca.fabrica = {$login_fabrica}
									  WHERE tbl_pedido.fabrica = {$login_fabrica} 
										AND tbl_pedido.pedido = {$pedido}";
					$resPrincipalItens = pg_query($con, $sqlPrincipalItens);


					if (pg_num_rows($resPrincipal) > 0) {
						foreach (pg_fetch_all($resPrincipalItens) as $key => $linha) {
							$total_com_ipi += $linha["total_com_ipi"];
				?>
				<tr>
					<td><?php echo $linha["referencia_peca"];?> - <?php echo $linha["nome_peca"];?></td>
					<td class="tac"><?php echo $linha["qtde"];?></td>
					<td class="tac red"><?php echo $linha["qtde_cancelada"];?></td>
					<td class="tac"><?php echo $linha["qtde_faturada"];?></td>
					<td class="tac red"><?php echo $linha["pecas_pendentes"];?></td>
					<td class="tac"><?php echo $linha["ipi"];?>%</td>
					<td class="tac" nowrap>R$ <?php echo number_format($linha["preco"],2,',','');?></td>
					<td class="tac" nowrap>R$ <?php echo number_format($linha["total_com_ipi"],2,',','');?></td>
				</tr>
				<?php }?>
				<tr>
					<td class="titulo_total tar" colspan="7">TOTAL</td>
					<td class="titulo_total tac">R$ <?php echo number_format($total_com_ipi,2,',','');?></td>
				</tr>
				<?php }?>
			</tbody>
		</table>
	</div>
</div>


		<table class="table table-bordered table-striped">
			<thead>
				<tr class="titulo_coluna">
					<th>Nota Fiscal</th>
					<th>Emissão</th>
					<th>Peça</th>
					<th>Qtde Faturada</th>
				</tr>
			</thead>
			<tbody>
				<?php 

					$sqlFat = "SELECT 
									tbl_faturamento.nota_fiscal, 
									tbl_faturamento.emissao, 
									COALESCE(tbl_pedido_item.qtde_cancelada, 0) AS qtde_cancelada, 
									COALESCE(tbl_faturamento_item.qtde, 0) AS qtde_faturada, 
									COALESCE(tbl_pedido_item.ipi, 0) AS ipi, 
									COALESCE(tbl_pedido_item.preco, 0) AS preco, 
									COALESCE(tbl_pedido_item.qtde-(tbl_pedido_item.qtde_cancelada+tbl_pedido_item.qtde_faturada), 0) AS pecas_pendentes,
									((tbl_pedido_item.qtde-COALESCE(tbl_pedido_item.qtde_cancelada, 0))*tbl_pedido_item.preco)*(1+(COALESCE(tbl_pedido_item.ipi, 0)/100)) AS total_com_ipi,
									tbl_peca.descricao AS nome_peca, 
									tbl_peca.referencia AS referencia_peca
							   FROM tbl_pedido
							   JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido 
							   JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca  AND tbl_peca.fabrica = {$login_fabrica}
							   LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item 
							   LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
							  WHERE tbl_pedido.fabrica = {$login_fabrica} 
								AND tbl_pedido.pedido = {$pedido} ORDER BY nota_fiscal";
					$resFat = pg_query($con, $sqlFat);

					if (pg_num_rows($resFat) > 0) {
						foreach (pg_fetch_all($resFat) as $key => $linha) {

							if (strlen($linha["nota_fiscal"]) > 0) {
								$nf = $linha["nota_fiscal"];
								$emissao = geraDataNormal($linha["emissao"]);
							} else {
								$nf = "Pendente";
								$emissao = "Pendente";
							}
				?>
				<tr>
					<td class="tac"><?php echo $nf;?></td>
					<td class="tac"><?php echo $emissao;?></td>
					<td><?php echo $linha["referencia_peca"];?> - <?php echo $linha["nome_peca"];?></td>
					<td class="tac"><?php echo $linha["qtde_faturada"];?></td>
				</tr>
				<?php }?>
				<?php }?>
			</tbody>
		</table>

<?php include "rodape.php"; ?>
