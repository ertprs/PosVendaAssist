<?php

	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	$admin_privilegios="call_center";
	include 'autentica_admin.php';
	include 'funcoes.php';

	if(isset($_GET["os"])){

		$sql_pecas = "SELECT 
						tbl_peca.peca AS peca,
						tbl_peca.referencia AS referencia_peca,
						tbl_peca.descricao AS nome_peca,
						tbl_os_item.qtde AS qtde_peca,
						tbl_tabela_item.preco AS preco_peca,
						tbl_os_item.custo_peca AS preco_custo_peca,
						tbl_servico_realizado.descricao AS servico_realizado,
						tbl_defeito.descricao AS defeito 
					FROM tbl_os 
					JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os 
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto 
					JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca 
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_os_item.peca 
					JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela AND tbl_tabela.sigla_tabela = 'GARAN5' AND tbl_tabela.fabrica = {$login_fabrica} 
					LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
					LEFT JOIN tbl_defeito ON tbl_defeito.defeito = tbl_os_item.defeito 
					WHERE tbl_os.os = {$os} 
					AND tbl_os_item.fabrica_i = {$login_fabrica} 
					AND tbl_os.fabrica = {$login_fabrica}";
		// echo nl2br($sql_pecas); exit;
		$res_pecas = pg_query($con, $sql_pecas);

		?>

			<!DOCTYPE html>
			<html>
				<head>
					<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
					<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

					<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
					<script src="bootstrap/js/bootstrap.js"></script>
					<script src="plugins/dataTable.js"></script>
					<script src="plugins/resize.js"></script>
				</head>
				<body>

					<div class="container" style="overflow: auto; height: 295px; width: 99%;">

						<input type="hidden" id="loading_action" value="f" />

						<h4 class="tac">Relatório Total Garantia - Peças </h4>

						<?php

							if(pg_num_rows($res_pecas) > 0){

								?>
								<table class="table table-striped table-bordered table-hover" style="margin: 0 auto; width: 99%;">
					                <thead>
					                    <tr class="titulo_coluna">
					                    	<th calss="tac">Peça</th>
					                    	<th calss="tac">Descrição</th>
					                    	<th calss="tac">Qtde</th>
					                    	<th calss="tac">Defeito</th>
					                    	<th calss="tac">Tipo Atendimento</th>
					                    	<th calss="tac">Preço</th>
					                    	<th calss="tac">Preço Total</th>
					                    </tr>
					                </thead>
					                <tbody>
										<?php

											$total = 0;

											for($i = 0; $i < pg_num_rows($res_pecas); $i++){

												$peca 				= pg_fetch_result($res_pecas, $i, "peca");
												$referencia_peca 	= pg_fetch_result($res_pecas, $i, "referencia_peca");
												$nome_peca 			= pg_fetch_result($res_pecas, $i, "nome_peca");
												$qtde_peca 			= pg_fetch_result($res_pecas, $i, "qtde_peca");
												$preco_peca 		= pg_fetch_result($res_pecas, $i, "preco_peca");
												$preco_custo_peca 	= pg_fetch_result($res_pecas, $i, "preco_custo_peca");
												$defeito 			= pg_fetch_result($res_pecas, $i, "defeito");
												$servico_realizado 	= pg_fetch_result($res_pecas, $i, "servico_realizado");

												echo "<tr>";
													echo "<td>".$referencia_peca."</td>";
													echo "<td>".$nome_peca."</td>";
													echo "<td calss='tac'>".$qtde_peca."</td>";
													echo "<td>".$defeito."</td>";
													echo "<td >".$servico_realizado."</td>";
													echo "<td class='tac'>R$ ".number_format($preco_peca, 2, ",", ".")."</td>";
													echo "<td class='tac'>R$ ".number_format($preco_custo_peca, 2, ",", ".")."</td>";
												echo "</tr>";

												$total += $preco_custo_peca;

											}
										?>
									</tbody>
									<tfoot>
										<tr>
											<td style="text-align: right;" colspan="6"><h4>Total</h4></td>
											<td><h4 class="tac">R$ <?php echo number_format($total, 2, ",", "."); ?></h4></td>
										</tr>
									</tfoot>
								</table>

								<?php

							}else{
								?>
								<br />
								<div class="alert alert-warning text-center" style="margin-left: 10px; margin-right: 10px;">
					                <h4>Nenhum resultado encontrado</h4>
					            </div>
								<?php
							}

						?>

					</div>

				</body>

			</html>

		<?php

	}else{
		?>
		<script>
			window.onload = window.parent.close();
		</script>
		<?php
	}

?>