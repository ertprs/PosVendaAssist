<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


#------------ Le Pedido da Base de dados ------------#
$pedido = $HTTP_GET_VARS['pedido'];
if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                ,
					tbl_pedido.condicao                              ,
					tbl_pedido.tabela                                ,
					tbl_pedido.pedido_cliente                        ,
					to_char(tbl_pedido.data, 'DD/MM/YYYY') as data   ,
					tbl_posto.nome                                   ,
					tbl_condicao.descricao      AS condicao_descricao,
					tbl_tabela.tabela                                ,
					tbl_tabela.descricao        AS tabela_descricao
			FROM    tbl_pedido
			LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_tabela   ON tbl_tabela.tabela     = tbl_pedido.tabela
			JOIN	tbl_posto ON tbl_posto.posto = tbl_pedido.posto
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido           = trim(pg_result($res,0,pedido));
		$condicao         = trim(pg_result($res,0,condicao_descricao));
		$data             = trim(pg_result($res,0,data));
		$tabela           = trim(pg_result($res,0,tabela));
		$tabela_descricao = trim(pg_result($res,0,tabela_descricao));
		$pedido_cliente   = trim(pg_result($res,0,pedido_cliente));
		$posto_nome       = trim(pg_result($res,0,nome));
		
		if ($condicao == "Garantia") $detalhar = "ok";
		$detalhar = "ok";
	}
}


$title = "CONFIRMAÇÃO DE PEDIDO DE PEÇAS";
$layout_menu = 'pedido';

include "cabecalho.php";
?>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="center">
		<table width="100%" border="0" cellspacing="2" cellpadding="2">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Posto</b>
				<br>
				<?echo $posto_nome ?>
				</font>
			</td>

			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido</b>
				<br>
				<?echo $pedido?>
				</font>
			</td>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Data</b>
				<br>
				<? echo $data ?>
				</font>
			</td>
			
			<? if (strlen($pedido_cliente) > 0) { ?>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido Cliente</b>
				<br>
				<?echo $pedido_cliente?>
				</font>
			</td>
			<? } ?>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Condição Pagamento</b>
				<br>
				<?echo $condicao?>
				</font>
			</td>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Tabela de Preços</b>
				<br>
				<?echo $tabela_descricao?>
				</font>
			</td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="2" cellpadding="2" align='center'>
		<tr height="20" bgcolor="#C0C0C0">
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Componente</b></font></td>
			<td align='center'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Quantidade</b></font></td>
			<td align='center'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Preço</b></font></td>
			<td align='center'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Total</b></font></td>
		</tr>
		
		<?
		$sql = "SELECT  tbl_pedido_item.peca,
						tbl_peca.referencia ,
						tbl_peca.descricao  ,
						tbl_pedido_item.qtde
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $pedido
				ORDER BY tbl_pedido_item.pedido_item;";
		$res = pg_exec ($con,$sql);
		$total_pedido = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$peca           = pg_result ($res,$i,peca);
			$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
			$qtde           = pg_result ($res,$i,qtde);
			
			$sql  = "SELECT tbl_tabela_item.preco
					FROM    tbl_tabela_item
					WHERE   tbl_tabela_item.tabela = $tabela
					AND     tbl_tabela_item.peca   = $peca;";
			$resT = pg_exec ($con,$sql);
			
			if (pg_numrows ($resT) > 0) {
				$preco = pg_result ($resT,0,0);
				$total = $preco * pg_result ($res,$i,qtde);
				$total_pedido += $total ;
				$preco = number_format ($preco,2,",",".");
				$total = number_format ($total,2,",",".");
			}else{
				$preco = "***";
				$total = "***";
			}
		?>
		<tr bgcolor="<? echo $cor ?>" >
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo $peca_descricao ?></font></td>
			<td align='right'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo $qtde ?></font></td>
			<td align='right'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo $preco ?></font></td>
			<td align='right'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo $total ?></font></td>
		</tr>
		<?
		}
		?>

		<tr>
		<td colspan='3' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
		<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido,2,",","."); ?></b></td>
		</tr>
		</table>
		
	<?
	if ($detalhar == "ok") {
		echo "<br>";
		
		$sql = "SELECT  distinct
						lpad(tbl_os.sua_os,10,0),
						tbl_peca.peca      ,
						tbl_peca.referencia,
						tbl_peca.descricao ,
						tbl_os.os          ,
						tbl_os.sua_os
				FROM    tbl_pedido
				JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido    = tbl_pedido.pedido
				JOIN    tbl_peca        ON  tbl_peca.peca             = tbl_pedido_item.peca
				LEFT JOIN tbl_os_item   ON  tbl_os_item.peca          = tbl_pedido_item.peca
										AND tbl_os_item.pedido        = tbl_pedido.pedido
				LEFT JOIN tbl_os_produto  ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os          ON  tbl_os.os                 = tbl_os_produto.os
				WHERE   tbl_pedido_item.pedido = $pedido
				ORDER BY lpad(tbl_os.sua_os,10,0);";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table width='100%' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr bgcolor='#C0C0C0'>";
			echo "<td align='center' colspan='3'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Ordens de Serviço que geraram o pedido acima</b></font></td>";
			echo "</tr>";
			echo "<tr bgcolor='#C0C0C0'>";
			//if ($condicao == "Garantia") {
			if (strpos($condicao,"Garantia") !== false) {
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Sua OS</b></font></td>";
			}
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Nota Fiscal</b></font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Peça</b></font></td>";
			echo "</tr>";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				$peca           = pg_result ($res,$i,peca);
				$os             = pg_result ($res,$i,os);
				$sua_os         = pg_result ($res,$i,sua_os);
				$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
				
				$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento.pedido    = $pedido
						AND     tbl_faturamento_item.peca = $peca;";
				$resx = pg_exec ($con,$sql);
				
				if (pg_numrows ($resx) > 0) {
					$nf = trim(pg_result($resx,0,nota_fiscal));
				}else{
					$nf = "Pendente";
				}
				
				if (strlen($sua_os) == 0) $sua_os = $os;
				
				echo "<tr bgcolor='$cor'>";
				//if ($condicao == "Garantia") {
				if (strpos($condicao,"Garantia") !== false) {
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
				}
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
				if (strtolower($nf) <> 'pendente'){
					echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
				}else{
					echo "$nf &nbsp;";
				}
				echo "</font></td>";
				echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$peca_descricao</font></td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
	?>
	</td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<a href="pedido_cadastro.php"><img src='imagens/btn_lancarnovopedido.gif'></a>
	</td>
</tr>



</form>


</table>

<p>

<? include "rodape.php"; ?>