<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}
	$peca = $_GET['peca'];

	$sql = "SELECT peca,tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao 
				from tbl_lista_basica 
				join tbl_produto on tbl_produto.produto = tbl_lista_basica.produto 
				and tbl_produto.fabrica_i = $login_fabrica  
				where peca = $peca and fabrica = $login_fabrica";
	$res = pg_query($con, $sql);
?>
	<body>
		<table  class="table table-striped table-bordered table-lupa" >
			<thead>
				<tr class='titulo_coluna'>
					<th>Referência</th>
					<th>Descrição</th>
				</tr>
			</thead>
			<tbody>
				<?php for($i=0; $i<pg_num_rows($res); $i++){ 
					$referencia = pg_fetch_result($res, $i, 'referencia');
					$descricao 	= pg_fetch_result($res, $i, 'descricao');
				?>
				<tr>	
					<td><?=$referencia?></td>
					<td><?=$descricao?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<br><br>
		<div style='width: 100%; text-align: center;' >
			<button type='button' class="btn btn-primary voltar">Voltar</button>
		</div>
	</body>