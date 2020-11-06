<?  
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$title = "CONFIRMAÇÃO DE PEDIDO DE PEÇAS / PRODUTOS";

$layout_menu = 'pedido';
$pedido = (!empty($_GET['pedido'])) ? $_GET['pedido'] : null;

if(strlen($pedido) > 0) {
	$sql = " SELECT tbl_pedido.pedido,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome   as nome_posto,
					to_char(tbl_pedido.data,'DD/MM/YYYY HH24:MI:SS')       AS data_pedido         ,
					to_char(tbl_pedido.finalizado,'DD/MM/YYYY HH24:MI:SS') AS data_finalizado     
			FROM    tbl_pedido
			JOIN    tbl_posto USING(posto)
			JOIN    tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_pedido.fabrica = tbl_posto_fabrica.fabrica
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica ;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$pedido              = trim(pg_result ($res,0,pedido));
		$data_pedido         = trim(pg_result ($res,0,data_pedido));
		$data_finalizado     = trim(pg_result ($res,0,data_finalizado));
		$codigo_posto        = trim(pg_result ($res,0,codigo_posto));
		$nome_posto          = trim(pg_result ($res,0,nome_posto));
	}

}
include "cabecalho.php";
?>
<style>
.Tabela{
	font-family: Verdana,Sans;
	font-size: 10px;
}
.Tabela thead{
	font-size: 12px;
	font-weight:bold;
}
.menu_top2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	color: #000000;
}

</style>

<?
if(strlen($pedido) > 0) { ?>
	<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
		<tr>
			<td valign="top" align="center">
				<table width="700" border="0" cellspacing="5" cellpadding="0">
					<tr class='menu_top2'>
						<td nowrap align='center'>
							<strong>Pedido</strong>
							<br /><?=$pedido;?>
						</td>
						<td nowrap align='center'>
							<strong>Posto</strong>
							<br />
							<?=$codigo_posto?>
						</td>
						<td nowrap align='center'>
							<strong>Razão Social</strong>
							<br/>
							<?=$nome_posto?>
						</td>
						<td nowrap align='center'>
							<strong>Data</strong>
							<br/>
							<?=$data_pedido?>
							&nbsp;
						</td>
						<td nowrap align='center'>
							<strong>Finalizado</strong>
							<br/>
							<?=$data_finalizado?>
							&nbsp;
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<?
	if($login_fabrica == 15) {
	?>
	<style>
	.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		border: 0px solid;
		color:#ffffff;
		background-color: #596D9B
	}
	.Tabela{
		font-family: Verdana,Sans;
		font-size: 10px;
	}
	.Tabela thead{
		font-size: 12px;
		font-weight:bold;
	}
	.table_line1 {
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}
	.table_line1_pendencia {
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		color: #FF0000;
	}

	.menu_top2 {
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		font-weight: normal;
		color: #000000;
	}
	.miolo{
		color:#000000;
		font-size:9px;
		font-weight:normal;
		text-align:left;
		background-color: #f7f5f0;
		}
	</style>
	<?
	$cabecalho_consumidor_topo = "<table width='700' border='0' cellspacing='2' cellpadding='2' align='center'><tr class='menu_top'><td>Peça</td><td>Qtde</td></tr>";
	$corpo_consumidor = "";
	$cabecalho_rodape = "</table>";

	$sql2 = "SELECT  tbl_pedido_item.pedido_item,
							tbl_pedido_item.peca,
							tbl_pedido_item.preco,
							case when $login_fabrica = 14 then rpad ((tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)))::text,7,'0')::float else tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)) end as total,
							tbl_peca.referencia            ,
							tbl_peca.descricao             ,
							tbl_peca.ipi                   ,
							tbl_pedido_item.qtde           ,
							tbl_pedido_item.qtde_faturada  ,
							tbl_pedido_item.qtde_cancelada ,
							tbl_pedido_item.qtde_faturada_distribuidor,
							tbl_pedido_item.obs 
							
						FROM  tbl_pedido
						JOIN  tbl_pedido_item USING (pedido)
						JOIN  tbl_peca        USING (peca)
						WHERE tbl_pedido_item.pedido = $pedido
						AND   tbl_pedido.fabrica     = $login_fabrica
						ORDER BY tbl_peca.descricao;";

	$res2 = pg_exec ($con,$sql2);
	$lista_os = array();
	if (pg_numrows($res2) > 0) {
		for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
			
			$pedido_item		= pg_result ($res2,$i,pedido_item);
			$qtde				= pg_result ($res2,$i,qtde);
			$peca_descricao		= pg_result ($res2,$i,referencia) . " - " . pg_result ($res2,$i,descricao);
			$peca				= pg_result ($res2,$i,peca);

			if ($peca_antiga == $peca) {
				$lista_os .= (!empty($lista_os)) ? ",".$os : $os;
				$condicao1 =  " AND tbl_os.os not in ($lista_os) ";

			}else{
				$condicao1 =  " AND 1 = 1 ";
				$lista_os = "";
			}

			$sql = "SELECT  distinct
								tbl_os.os,
								tbl_os.sua_os, 
								tbl_produto.descricao as descricao_produto, 
								tbl_os.revenda_nome, 
								tbl_os.consumidor_revenda, 
								tbl_produto.produto
						FROM    tbl_pedido
						JOIN    tbl_pedido_item   ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
						LEFT JOIN tbl_os_item     ON  tbl_os_item.peca          = tbl_pedido_item.peca AND tbl_os_item.pedido         = tbl_pedido.pedido
						LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto  = tbl_os_item.os_produto
						LEFT JOIN tbl_os          ON tbl_os.os                  = tbl_os_produto.os
						LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item        = tbl_os_item_nf.os_item 
						LEFT JOIN tbl_produto     ON tbl_produto.produto        = tbl_os.produto 
						WHERE   tbl_pedido_item.pedido = $pedido
						AND     tbl_pedido_item.pedido_item  = $pedido_item
						$condicao1 
						ORDER BY tbl_os.sua_os;";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				
				$cor = ($i % 2 == 0) ? "#FFFFFF": "#F1F4FA";
				$consumidor_revenda = pg_result ($res,0,consumidor_revenda);
				$os = pg_result ($res,0,os);

				if ($consumidor_revenda=='C'){
				
					if( $peca_ant == $peca){
						$peca_qtde += $qtde; 
					}
					else{ 
						$peca_qtde += $qtde; 
						if( $peca_descricao_antiga > 0){
							$corpo_consumidor .= "<tr class='miolo'><td>$peca_descricao_antiga</td><td>$peca_qtde</td>";
						}
						$peca_qtde = 0;
					}
				$corpo_consumidor .= "</tr>";
				$peca_descricao_antiga = $peca_descricao;
				$peca_ant = $peca;
				}
			$peca_antiga = $peca;
			}
		}
	}
	$peca_qtde += $qtde; 
	echo $cabecalho_consumidor_topo.$corpo_consumidor."<tr class='miolo'><td>$peca_descricao_antiga</td><td>$peca_qtde</td></tr>".$cabecalho_rodape;
	
	$sql = "SELECT tbl_os.os, tbl_os.consumidor_revenda as R,
				   tbl_os.revenda_nome,
				   tbl_produto.produto, tbl_produto.descricao as produto_descr,
				   tbl_peca.peca, tbl_peca.descricao as peca_descr, tbl_peca.referencia as peca_ref,
				   tbl_os_item.qtde
			FROM tbl_pedido
			INNER JOIN tbl_os_item USING (pedido)
			INNER JOIN tbl_os_produto USING (os_produto)
			INNER JOIN tbl_os USING (os)
			INNER JOIN tbl_produto ON (tbl_produto.produto = tbl_os_produto.produto)
			INNER JOIN tbl_peca ON (tbl_peca.peca= tbl_os_item.peca)
			WHERE tbl_pedido.pedido = $pedido
			AND tbl_pedido.fabrica = 15
			AND tbl_os.consumidor_revenda = 'R'";

		$res    	= @pg_query($con, $sql);
		$aDados 	= array();
		$aProdutos 	= array();
		if ( is_resource($res) && pg_num_rows($res) > 0 ) {
			while ( $linha = pg_fetch_assoc($res) ) {
				$idx_revenda = $linha['revenda_nome'];
				$idx_produto = $linha['produto'];
				$idx_peca	 = $linha['peca'];
				if ( ! isset($aProduto[$idx_produto]) ) {
					$aProdutos[$idx_produto] 			= array();
					$aProdutos[$idx_produto]['id'] 		= $linha['produto'];
					$aProdutos[$idx_produto]['descr'] 	= $linha['produto_descr'];
				}
				if ( ! isset($aDados[$idx_revenda]) ) {
					$aDados[$idx_revenda] = array();
				}
				if ( ! isset($aDados[$idx_revenda][$idx_produto]) ) {
					$aDados[$idx_revenda][$idx_produto] = array();
				}
				if ( isset($aDados[$idx_revenda][$idx_produto][$idx_peca]) ) {
					$linha['qtde'] = $linha['qtde'] + $aDados[$idx_revenda][$idx_produto][$idx_peca]['qtde'];
				}
				$aDados[$idx_revenda][$idx_produto][$idx_peca] = $linha;
			}
		}
		?>
		<br><br>
		<table width="700" border="0" cellspacing="2" cellpadding="2" align="center">
			<tr class='menu_top'>
				<td> Revenda </td>
				<td> Produto </td>
				<td> Peça </td>
				<td> Quantidade </td>
			</tr>
			<?php foreach ($aDados as $revenda=>$eachProdutos): ?>
				<?php $rowspan_rev = count($eachProdutos); ?>
				<?php foreach ($eachProdutos as $produto=>$eachPecasCont): ?>
						<?php $rowspan_linhaCont += count($eachPecasCont); ?>
				<?php endforeach; ?>
				<?php foreach ($eachProdutos as $produto=>$eachPecas): ?>
					<?php $rowspan_prod = count($eachPecas); ?>
					<?php foreach ($eachPecas as $linha): ?>
						<?php $rowspan_linha = count($eachPecas); ?>
						<tr class='miolo'>
							<?php if ( ! is_null($rowspan_rev) ): ?>
								<td rowspan="<?php echo $rowspan_linhaCont; ?>" align='left'> <?php echo $revenda; ?> </td>
								<? $rowspan_linhaCont = 0;?>
							<?php endif; ?>
							<?php if ( ! is_null($rowspan_prod) ): ?>
								<td rowspan="<?php echo $rowspan_linha; ?>" align='left'> <?php echo $aProdutos[$produto]['descr']; ?><br>Quantidade: 
								<?
							
								$sql = "SELECT count(distinct tbl_os.os)as numero
										FROM tbl_pedido
										JOIN tbl_os_item USING (pedido)
										JOIN tbl_os_produto USING (os_produto)
										JOIN tbl_os USING (os)
										JOIN tbl_produto ON (tbl_produto.produto = tbl_os_produto.produto)
										JOIN tbl_peca ON (tbl_peca.peca= tbl_os_item.peca)
										WHERE tbl_pedido.pedido = $pedido
										AND tbl_produto.produto = $produto
										AND tbl_os.revenda_nome = '$revenda'
										AND tbl_pedido.fabrica = 15
										AND tbl_os.consumidor_revenda = 'R'";
			
										$res = pg_exec ($con,$sql);
										if (pg_numrows($res) > 0) {
											$numero = pg_result ($res,0,numero);
											echo $numero;
										}

								?> </td>
							<?php endif; ?>
							<td align='left'> <?php echo $linha['peca_ref']." - ".$linha['peca_descr']; ?> </td>
							<td> <?php echo $linha['qtde']; ?> </td>
						</tr>
						<?php 
							$rowspan_prod = null;
							$rowspan_rev = null;
						?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</table>
		<?php
	}
	else{
		$sql = "SELECT DISTINCT
					tbl_peca.referencia,
					tbl_peca.descricao,
					sum(qtde) as qtde
				FROM tbl_pedido
				JOIN tbl_pedido_item USING(pedido)
				JOIN tbl_peca        USING(peca)
				WHERE tbl_pedido.pedido = $pedido
				GROUP BY tbl_peca.referencia,	tbl_peca.descricao	;";

		$res2 = pg_exec ($con,$sql);
		if (pg_numrows($res2) > 0) {
			echo "<table width='500' cellpadding='2' cellspacing='1'   align='center' class='Tabela' >";
			echo "<thead>";
			echo "<tr bgcolor='#C0C0C0' style ='font:bold; text-align:center;'>";
			echo "<td>PEÇA</td>";
			echo "<td>QTDE</td>";
			echo "</tr>";
			echo "</thead>";
			for ($i = 0 ; $i < pg_numrows ($res2) ; $i++) {
				$referencia   = pg_result ($res2,$i,referencia);
				$descricao    = pg_result ($res2,$i,descricao);
				$qtde         = pg_result ($res2,$i,qtde);
				$cor = ($i % 2 == 0) ? "#FFFFFF": "#F1F4FA";

				echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left; font-weight:normal' >";
				echo "<td align='left'>$referencia - $descricao</td>";
				echo "<td align='center'>$qtde</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br />";
		}
	}
}

 include "rodape.php"; ?>
