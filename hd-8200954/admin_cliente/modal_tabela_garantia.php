<link href="../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="../admin/css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>

<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

?>
<script>
	$(document).ready(function(){
		$(".btnFechar").on("click", function(){
			window.parent.Shadowbox.close();
		});
	});
</script>

<body class="container" style="background-color: #FFFFFF; overflow: hidden; padding: 10px 20px; width: 94%;" >
	<form method="post" >
		<div class="control-group-inline" >
		    <div class="controls" >
				<button type="button" class="btn btn-danger btn-small btnFechar" style="float: right;" >Fechar</button>
	  	  </div>
	 	</div>
		<div class="row-fluid" >
			<div class='span12' >
				<div class='control-group' >
					<h4 style="text-align: center;">Tabela de Garantia</h4>
					<div class='controls controls-row' >
						<div class='span12' >
						<?php 
						$defeito_reclamado = $_GET["defeito_reclamado"];

						$sql = "SELECT 
								tbl_tabela_garantia.defeito_reclamado,
								tbl_tabela_garantia.ano_fabricacao,
								tbl_tabela_garantia.mao_de_obra,
								tbl_tabela_garantia.pecas,
								tbl_defeito_reclamado.descricao
							FROM tbl_tabela_garantia 
								INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_tabela_garantia.defeito_reclamado
									AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
									AND tbl_defeito_reclamado.defeito_reclamado = {$defeito_reclamado}
								INNER JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_tabela_garantia.cliente_admin
								INNER JOIN tbl_admin ON tbl_admin.cliente_admin = tbl_cliente_admin.cliente_admin
									AND tbl_admin.admin = {$login_admin}
								WHERE tbl_tabela_garantia.fabrica = {$login_fabrica}

								ORDER BY defeito_reclamado, ano_fabricacao";
						$res = pg_query($con, $sql);

						$row = pg_num_rows($res);

						if ($row > 0) {
						?>
						<table id="resultado_tabela_garantia" class='table table-striped table-bordered table-fixed'>
							<tr class='titulo_coluna'>
								<th>Defeito</th>
								<th class="th_result">Ano de Fabricação</th>
								<th class="th_result">Mão de obra</th>
								<th class="th_result">Peças</th>
							</tr>
								<?php 

								for ($i = 0; $i < $row; $i++) { 
									$defeito_reclamado  = pg_fetch_result($res, $i, 'defeito_reclamado');
									$ano_fabricacao     = pg_fetch_result($res, $i, 'ano_fabricacao');
									$mao_de_obra        = pg_fetch_result($res, $i, 'mao_de_obra');
									$pecas              = pg_fetch_result($res, $i, 'pecas');
									$descricao          = pg_fetch_result($res, $i, 'descricao');
									?>
									<tr>
										<td>
											<?=$descricao;?>
										</td>
										<td data-ano_fabricacao="<?=$ano_fabricacao?>" class="tac"><?=$ano_fabricacao;?></td>
										<td data-mao_de_obra="<?=$mao_de_obra?>" class="tac"><?=$mao_de_obra;?> (meses)</td>
										<td data-pecas="<?=$pecas?>" class="tac"><?=$pecas;?> (meses)</td>
									</tr>
								<?php 
								} ?>
						</table>
						<br>
						<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
 	</form>
</body>
