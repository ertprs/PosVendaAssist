<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}
	$peca = $_GET['peca'];

	
?>
	<body>
        <?php 
        	$sql = "SELECT tbl_pedido.pedido, tbl_pedido.seu_pedido, tbl_pedido.data, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_status_pedido.descricao as status_descricao, tbl_pedido_item.qtde  
				from tbl_pedido_item 
				join tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido and tbl_pedido.fabrica = $login_fabrica 
				join tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_pedido.posto and tbl_posto_fabrica.fabrica = $login_fabrica 
				join tbl_posto on tbl_posto.posto = tbl_posto_fabrica.posto 
				join tbl_status_pedido on tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
				where tbl_pedido_item.peca = $peca and tbl_pedido.data between current_date - interval '30 days' and current_date 
				and finalizado is not null ";
			$res = pg_query($con, $sql);

        ?>
		<table  class="table table-striped table-bordered table-lupa" >
			<thead>
				<tr class='titulo_coluna'>
					<th>Pedido</th>
					<th>Data</th>
					<th>Status</th>
					<th>Posto</th>
					<th>Qtde da Peça</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if(pg_num_rows($res)>0){
				for($i=0; $i<pg_num_rows($res); $i++){ 
					$pedido 		= pg_fetch_result($res, $i, 'pedido');
					$seu_pedido		= pg_fetch_result($res, $i, 'seu_pedido');
					$qtde 			= pg_fetch_result($res, $i, 'qtde');
					$nome_posto 	= pg_fetch_result($res, $i, 'nome');
					$codigo_posto 	= pg_fetch_result($res, $i, 'codigo_posto');
					$data 			= mostra_data(substr(pg_fetch_result($res, $i, 'data'), 0, 10));
					$status_descricao 	= pg_fetch_result($res, $i, 'status_descricao');	
				?>
				<tr>	
					<td class="tac"><?=$seu_pedido?></td>
					<td class="tac"><?=$data?></td>
					<td class="tac"><?=$status_descricao?></td>
					<td class="tac"><?=$codigo_posto . " - " . $nome_posto?></td>
					<td class="tac"><?=$qtde?></td>
				</tr>
				<?php } 
				}else{ ?>
					<div class="alert alert-warning">
						<h4>Nenhum registro encontrato</h4>
					</div>

				<?php 
				}
				?>
			
			</tbody>
		</table>
		<br><br>
		<div style='width: 100%; text-align: center;' >
			<button type='button' class="btn btn-primary voltar">Voltar</button>
		</div>
	</body>
