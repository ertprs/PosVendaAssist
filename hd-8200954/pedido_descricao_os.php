<?php

    $sql = "SELECT  tbl_os_item.os_item,
        tbl_os_item.pedido,
        tbl_os_item.qtde,
        tbl_os_item.causa_defeito,
        tbl_peca.referencia,
        tbl_peca.descricao,
        tbl_peca.devolucao_obrigatoria,
        tbl_defeito.defeito,
        tbl_defeito.descricao AS defeito_descricao,
        tbl_causa_defeito.descricao AS causa_defeito_descricao,
        tbl_produto.referencia AS subconjunto,
        tbl_os_produto.produto,
        tbl_os_produto.serie,
        tbl_servico_realizado.servico_realizado,
        tbl_servico_realizado.descricao AS servico_descricao,
        tbl_os_item.peca_serie_trocada
    FROM tbl_os
    JOIN (SELECT os FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os = oss.os
    JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
    JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
    JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
    JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
    JOIN tbl_pedido ON tbl_os_item.pedido       = tbl_pedido.pedido
    LEFT JOIN tbl_defeito USING (defeito)
    LEFT JOIN tbl_causa_defeito ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
    LEFT JOIN tbl_servico_realizado USING (servico_realizado)
    WHERE tbl_os.os = $os
    AND tbl_os.fabrica = $login_fabrica
    AND tbl_os_item.pedido NOTNULL
    ORDER BY tbl_os_item.os_item ASC";

    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){

    	?>

    	<style>
    	.titulo_tabela_min{
    		font-size: 13px !important;
    	}
    	</style>

    	<table align="center" id="resultado_os" class='table table-bordered table-large'>
    		<thead>
				<tr>
					<th class="titulo_tabela tac" colspan="4">Pedidos enviados ao Fabricante</hd>
				</tr>
				<tr>
					<th class="titulo_tabela tac titulo_tabela_min">Pedido</th>
					<th class="titulo_tabela tac titulo_tabela_min">Referência Peça</th>
					<th class="titulo_tabela tac titulo_tabela_min">Descrição Peça</th>
					<th class="titulo_tabela tac titulo_tabela_min">Qtde</th>
				</tr>
			</thead>
			<tbody>

			<?php

			for($i = 0; $i < pg_num_rows($res); $i++){

				$pedido     = pg_fetch_result($res, $i, "pedido");
				$referencia = pg_fetch_result($res, $i, "referencia");
				$descricao  = pg_fetch_result($res, $i, "descricao");
				$qtde       = pg_fetch_result($res, $i, "qtde");

				?>

				<tr>
					<td class="tac"><strong><?php echo $pedido; ?></strong></td>
					<td class="tac"><?php echo $referencia; ?></td>
					<td><?php echo $descricao; ?></td>
					<td class="tac"><?php echo $qtde; ?></td>			
				</tr>

				<?php

			}

			?>

			</tbody>
		</table>

    	<?php

    }

?>