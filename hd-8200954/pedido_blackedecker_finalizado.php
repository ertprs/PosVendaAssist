<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$sql = "SELECT	tbl_posto.posto,
				tbl_posto.suframa,
				tbl_posto.estado
		FROM	tbl_posto
		JOIN	tbl_posto_fabrica USING(posto)
		WHERE	tbl_posto_fabrica.posto   = $login_posto
		AND		tbl_posto_fabrica.fabrica = $login_fabrica";

$res_posto = @pg_exec ($con,$sql);
if (@pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}

$cod_posto = trim(pg_result ($res_posto,0,posto));
$suframa   = trim(pg_result ($res_posto,0,suframa));
$estado    = trim(pg_result ($res_posto,0,estado));

$lista_pedido_suframa = "sim";

if (strlen($_GET['pedido']) > 0) {
	$cook_pedido = $_GET['pedido'];
	$lista_pedido_suframa = "nao";
}

if ($suframa == 't' or $estado == 'SC') {
	$sql = "SELECT case when tbl_pedido.pedido_blackedecker > 99999 then
				lpad((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0') 
			else
				lpad(tbl_pedido.pedido_blackedecker::text,5,'0') 
			end AS pedido_blackedecker
			FROM   tbl_pedido
			WHERE  tbl_pedido.pedido_suframa = $cook_pedido";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$pedido_suframa = trim(pg_result($res,0,pedido_blackedecker));
	}
}

if (strlen($cook_pedido) > 0) {
	$sql = "SELECT case when tbl_pedido.pedido_blackedecker > 99999 then
				lpad((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0') 
			else
				lpad(tbl_pedido.pedido_blackedecker::text,5,'0') 
			end AS pedido_blackedecker
			FROM   tbl_pedido
			WHERE  tbl_pedido.pedido = $cook_pedido";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
	}
}

$title     = "Pedido Finalizado";
$cabecalho = "Pedido Finalizado";

$layout_menu = "pedido";

include "cabecalho.php";


if (strlen($_GET['pedido']) == 0) {
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align='center'>
<tr>
	<td align="center">
		<center>
		<br>
		<p>
<?
			if (strlen($_GET['msg']) == 0) {
				echo "<b>Seu pedido número <font color='#990000'>$pedido_blackedecker</font> foi finalizado com sucesso !</b>";
			}else{
				$msg = $_GET['msg'];
				$mens[1] = "<b>Seu pedido <font color='#990000'> $pedido_blackedecker </font> foi concluído com sucesso. Somando as pendências do pedido anterior. As pendências assumirão a condição de pagamento e o preço deste pedido atual. Acompanhe suas pendências através dos relatórios gerenciais.</b>";
				$mens[2] = "<b>Seu pedido <font color='#990000'> $pedido_blackedecker </font> foi concluído com sucesso e suas pendências canceladas.</b>";
				echo $mens[$msg];
			}
			
			if (strlen($pedido_suframa) > 0) {
				echo "<b>";
				echo "No pedido acima foram mantidas as peças Nacionais. <br><br>";
				echo "Foi gerado um novo pedido de número <font color='#990000'>$pedido_suframa</font> com as peças Importadas.<br>";
				echo "</b>";
				echo "<p>";
			}
?>
			
			<font face='Verdana, Arial' size='2' color='#C64533'><b>
			Os pedidos são exportados diariamente às 13h30. <br>
			Caso precise INCLUIR ou CANCELAR algum item o pedido ficará em aberto até este horário. <br>
			Na tela de digitação de pedidos, faça a manutenção necessária GRAVE e FINALIZE o pedido. <br> <br>
			</b></font>
			
			<b>Acompanhe neste site o andamento da sua compra</b>.
		</center>
	</td>
</tr>
</table>
<br><br><hr width='700'>

<?
}

if (strlen ($cook_pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                              ,
					case when tbl_pedido.pedido_blackedecker > 99999 then
						lpad((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0') 
					else
						lpad(tbl_pedido.pedido_blackedecker::text,5,'0') 
					end                                      AS pedido_blackedecker,
					tbl_pedido.condicao                                            ,
					tbl_pedido.tabela                                              ,
					tbl_pedido.pedido_cliente                                      ,
					tbl_pedido.pedido_acessorio                                    ,
					to_char(tbl_pedido.data,'DD/MM/YYYY')    AS pedido_data        ,
					tbl_condicao.descricao                   AS condicao_descricao ,
					tbl_tabela.tabela                                              ,
					tbl_tabela.descricao                     AS tabela_descricao   ,
					tbl_faturamento.nota_fiscal
			FROM    tbl_pedido
			LEFT JOIN tbl_condicao    ON  tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_tabela      ON  tbl_tabela.tabela     = tbl_pedido.tabela
			LEFT JOIN tbl_faturamento ON  tbl_pedido.posto      = tbl_faturamento.posto
									  AND tbl_pedido.fabrica    = tbl_faturamento.fabrica
			WHERE   tbl_pedido.pedido  = $cook_pedido
			AND     tbl_pedido.posto   = $login_posto
			AND     tbl_pedido.fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido              = trim(pg_result ($res,0,pedido));
		$pedido_blackedecker = trim(pg_result ($res,0,pedido_blackedecker));
		$pedido_acessorio    = trim(pg_result ($res,0,pedido_acessorio));
		$condicao            = trim(pg_result ($res,0,condicao_descricao));
		$tabela              = trim(pg_result ($res,0,tabela));
		$tabela_descricao    = trim(pg_result ($res,0,tabela_descricao));
		$pedido_cliente      = trim(pg_result ($res,0,pedido_cliente));
		$pedido_data         = trim(pg_result ($res,0,pedido_data));
		$nota_fiscal         = trim(pg_result ($res,0,nota_fiscal));
		if ($condicao == "Garantia") $detalhar = "ok";
	}
}
?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="center">
		<table width="650" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido</b>
				<br>
				<?
				if ($pedido_acessorio == "t") $pedido_blackedecker = intval($pedido_blackedecker + 1000);
				echo $pedido_blackedecker;
				?>
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
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Data</b>
				<br>
				<?echo $pedido_data?>
				</font>
			</td>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Condição Pagamento</b>
				<br>
				<?echo $condicao?>
				</font>
			</td>
			
			<? if (strlen($nota_fiscal) > 0 AND $login_fabrica <> 1) { ?>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Nota Fiscal</b>
				<br>
				<?echo $nota_fiscal?>
				</font>
			</td>
			<? } ?>
			
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Tabela de Preços</b>
				<br>
				<?echo $tabela_descricao?>
				</font>
			</td>
		</tr>
		</table>
		
		<table width="650" border="0" cellspacing="3" cellpadding="0" align='center'>
		<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>
			<td align='left'>Componente</td>
			<td align='center'>Qtde</td>
			<td align='center'>Preço</td>
			<?if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {?>
			<td align='center'>IPI</td>
			<? } ?>
			<td align='center'>Total</td>
		</tr>
		
		<?
		$sql = "SELECT  tbl_pedido_item.peca  ,
						tbl_peca.referencia   ,
						tbl_peca.descricao    ,
						tbl_pedido_item.qtde  ,
						tbl_pedido_item.preco ,
						tbl_peca.ipi          ,
						(1 + (tbl_peca.ipi / 100)) AS ipi_agregado
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $cook_pedido
				ORDER BY tbl_pedido_item.pedido_item;";
		if(!empty($cook_pedido)){
			$res = pg_exec ($con,$sql);
		}
		$total_pedido = 0 ;
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$peca           = pg_result ($res,$i,peca);
			$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
			$qtde           = pg_result ($res,$i,qtde);
			$preco          = pg_result ($res,$i,preco);
			$ipi            = pg_result ($res,$i,ipi);
			$ipi_agregado   = pg_result ($res,$i,ipi_agregado);
			
			if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {
				$total = $preco * $qtde * $ipi_agregado;
			}else{
				$total = $preco * $qtde;
			}
			$total_pedido += $total ;
			$preco = number_format ($preco,2,",",".");
			$total = number_format ($total,2,",",".");
		?>
		<tr bgcolor='<?echo $cor?>' style='color: #000000 ; font-size:12px; text-align:center '>
			<td align='left'><? echo $peca_descricao ?></td>
			<td align='right'><? echo $qtde ?></td>
			<td align='right'><? echo $preco ?></td>
			<?if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {?>
			<td align='right'><? echo $ipi ?>%</td>
			<? } ?>
			<td align='right'><? echo $total ?></td>
		</tr>
		<?
		}
		?>
		
		<tr>
		<?if ($login_tipo_posto <> 39 and $login_tipo_posto <> 79 and $login_tipo_posto <> 81 and $login_tipo_posto <> 80 and $login_tipo_posto <> 38 and $login_tipo_posto <> 85 and $login_tipo_posto <> 87 and $login_tipo_posto <> 86) {?>
		<td colspan='4' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
		<?}else{?>
		<td colspan='3' bgcolor='#cccccc' align='center'><b>TOTAL</b></td>
		<? } ?>
		<td bgcolor='#cccccc' align='right' nowrap><b><? echo number_format ($total_pedido,2,",","."); ?></b></td>
		</tr>
		</table>
		
	<?
	if ($detalhar == "ok") {
		echo "<br>";
		
		$sql = "SELECT  distinct
						lpad(tbl_os.sua_os::text,10,'0'),
						tbl_peca.peca      ,
						tbl_peca.referencia,
						tbl_peca.descricao ,
						tbl_os.os          ,
						tbl_os.sua_os      ,
						tbl_os_item_nf.nota_fiscal
				FROM    tbl_pedido
				JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
				JOIN    tbl_peca        ON  tbl_peca.peca              = tbl_pedido_item.peca
				LEFT JOIN tbl_os_item   ON  tbl_os_item.peca           = tbl_pedido_item.peca
										AND tbl_os_item.pedido         = tbl_pedido.pedido
				LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
				LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item       = tbl_os_item_nf.os_item
				WHERE   tbl_pedido_item.pedido = $pedido
				ORDER BY lpad(tbl_os.sua_os::text,10,'0');";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca           = pg_result ($res,$i,peca);
				$os             = pg_result ($res,$i,os);
				$sua_os         = pg_result ($res,$i,sua_os);
				$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
				$nota_fiscal    = pg_result ($res,$i,nota_fiscal);
				
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				if ($i == 0) {
					echo "<table width='650' border='0' cellspacing='5' cellpadding='0' align='center'>";
					if (strlen($os) > 0) {
						echo "<tr bgcolor='#C0C0C0'>";
						echo "<td align='center' colspan='3'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Ordens de Serviço que geraram o pedido acima</b></font></td>";
						echo "</tr>";
					}
					echo "<tr bgcolor='#C0C0C0'>";
					//if ($condicao == "Garantia") {
					if (strpos($condicao,"Garantia") !== false) {
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Sua OS</b></font></td>";
					}
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Nota Fiscal</b></font></td>";
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Peça</b></font></td>";
					echo "</tr>";
				}
				
				$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento.pedido    = $pedido
						AND     tbl_faturamento_item.peca = $peca;";
				$resx = pg_exec ($con,$sql);
				
				if (strlen ($nota_fiscal) == 0) {
					if (pg_numrows ($resx) > 0) {
						$nf = trim(pg_result($resx,0,nota_fiscal));
						$link = 0;
					}else{
						$nf = "Pendente";
						$link = 0;
					}
				}else{
					$nf = $nota_fiscal;
					$link = 0;
				}
				
				if (strlen($sua_os) == 0) $sua_os = $os;
				
				echo "<tr bgcolor='$cor'>";
				//if ($condicao == "Garantia") {
				if (strpos($condicao,"Garantia") !== false) {
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
				}
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
				if (strtolower($nf) <> 'pendente'){
					if ($link == 1) 
						echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
					else
						echo "$nf";
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
</table>

<p>

<?
if (strlen ($cook_pedido) > 0 and $lista_pedido_suframa == "sim") {
	$sql = "SELECT  tbl_pedido.pedido                                ,
					tbl_pedido.condicao                              ,
					tbl_pedido.tabela                                ,
					tbl_pedido.pedido_cliente                        ,
					tbl_condicao.descricao      AS condicao_descricao,
					tbl_tabela.tabela                                ,
					tbl_tabela.descricao        AS tabela_descricao
			FROM    tbl_pedido
			LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_tabela   ON tbl_tabela.tabela     = tbl_pedido.tabela
			WHERE   tbl_pedido.pedido_suframa = $cook_pedido
			AND     tbl_pedido.posto          = $login_posto
			AND     tbl_pedido.fabrica        = $login_fabrica;";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) > 0) {
		$pedido           = trim(pg_result ($res,0,pedido));
		$condicao         = trim(pg_result ($res,0,condicao_descricao));
		$tabela           = trim(pg_result ($res,0,tabela));
		$tabela_descricao = trim(pg_result ($res,0,tabela_descricao));
		$pedido_cliente   = trim(pg_result ($res,0,pedido_cliente));
		
		if ($condicao == "Garantia") $detalhar = "ok";
?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="center">
		<table width="650" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido</b>
				<br>
				<? if ($login_fabrica == 1 ) echo $pedido_suframa; else echo $pedido; ?>
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
		
		<table width="650" border="0" cellspacing="3" cellpadding="0" align='center'>
		<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>
			<td align='left'>Componente</td>
			<td align='center'>Qtde</td>
			<td align='center'>Preço</td>
			<td align='center'>Total</td>
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
		<tr bgcolor='<?echo $cor?>' style='color: #000000 ; font-size:12px; text-align:center '>
			<td align='left'><? echo $peca_descricao ?></td>
			<td align='right'><? echo $qtde ?></td>
			<td align='right'><? echo $preco ?></td>
			<td align='right'><? echo $total ?></td>
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
						lpad(tbl_os.sua_os::text,10,'0'),
						tbl_peca.peca      ,
						tbl_peca.referencia,
						tbl_peca.descricao ,
						tbl_os.os          ,
						tbl_os.sua_os      ,
						tbl_os_item_nf.nota_fiscal
				FROM    tbl_pedido
				JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
				JOIN    tbl_peca        ON  tbl_peca.peca              = tbl_pedido_item.peca
				LEFT JOIN tbl_os_item   ON  tbl_os_item.peca           = tbl_pedido_item.peca
										AND tbl_os_item.pedido         = tbl_pedido.pedido
				LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
				LEFT JOIN tbl_os_item_nf  ON tbl_os_item.os_item       = tbl_os_item_nf.os_item
				WHERE   tbl_pedido_item.pedido = $pedido
				ORDER BY lpad(tbl_os.sua_os::text,10,'0');";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$peca           = pg_result ($res,$i,peca);
				$os             = pg_result ($res,$i,os);
				$sua_os         = pg_result ($res,$i,sua_os);
				$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
				$nota_fiscal    = pg_result ($res,$i,nota_fiscal);
				
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				if ($i == 0) {
					echo "<table width='650' border='0' cellspacing='5' cellpadding='0' align='center'>";
					if (strlen($os) > 0) {
						echo "<tr bgcolor='#C0C0C0'>";
						echo "<td align='center' colspan='3'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Ordens de Serviço que geraram o pedido acima</b></font></td>";
						echo "</tr>";
					}
					echo "<tr bgcolor='#C0C0C0'>";
					//if ($condicao == "Garantia") {
					if (strpos($condicao,"Garantia") !== false) {
						echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Sua OS</b></font></td>";
					}
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Nota Fiscal</b></font></td>";
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Peça</b></font></td>";
					echo "</tr>";
				}
				
				$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento.pedido    = $pedido
						AND     tbl_faturamento_item.peca = $peca;";
				$resx = pg_exec ($con,$sql);
				
				if (strlen ($nota_fiscal) == 0) {
					if (pg_numrows ($resx) > 0) {
						$nf = trim(pg_result($resx,0,nota_fiscal));
						$link = 0;
					}else{
						$nf = "Pendente";
						$link = 0;
					}
				}else{
					$nf = $nota_fiscal;
					$link = 0;
				}
				
				if (strlen($sua_os) == 0) $sua_os = $os;
				
				echo "<tr bgcolor='$cor'>";
				//if ($condicao == "Garantia") {
				if (strpos($condicao,"Garantia") !== false) {
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><a href='os_press.php?os=$os' target='_new'>$sua_os</a></font></td>";
				}
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
				if (strtolower($nf) <> 'pendente'){
					if ($link == 1) 
						echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
					else
						echo "$nf";
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
</table>

<?
	}
}
?>

<!------------ Pendências do Pedidos ------------------- -->
<?
$sql = "SELECT	tbl_pedido_item.qtde         ,
				tbl_pedido_item.qtde_faturada,
				tbl_peca.peca                ,
				tbl_peca.referencia          ,
				tbl_peca.descricao
		FROM    tbl_pedido
		JOIN    tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_pedido.pedido
		JOIN    tbl_peca             ON tbl_peca.peca          = tbl_pedido_item.peca
		WHERE   tbl_pedido.pedido = $pedido
		AND     tbl_pedido_item.qtde_faturada < tbl_pedido_item.qtde
		ORDER   BY tbl_pedido_item.pedido_item";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	if ($i == 0) {
		echo "<table width='650' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
		echo "<td colspan='6'>Pendências deste pedido</td>";
		echo "</tr>";
		echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
		echo "<td align='left'>Componente</td>";
		echo "<td>Qtde<br>Pedida</td>";
		echo "<td>Qtde<br>Faturada</td>";
		echo "</tr>";
	}
	
	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = '#F1F4FA';
	
	echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
	echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,qtde_faturada) . "</td>";
	echo "</tr>";
}
echo "</table>";

echo "<br>";
?>

<!------------ Atendimento Direto de Pedidos ------------------- -->
<?
$sql = "SELECT	tbl_faturamento.faturamento , 
				tbl_faturamento.nota_fiscal , 
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao , 
				tbl_faturamento_item.peca , 
				tbl_faturamento_item.qtde as qtde_fatura,
				tbl_pedido_item.qtde,
				tbl_peca.peca ,
				tbl_peca.referencia ,
				tbl_peca.descricao
		FROM    (
			SELECT *
			FROM   tbl_pedido_item
			WHERE  tbl_pedido_item.pedido = $pedido
		) tbl_pedido_item
		JOIN tbl_faturamento_item    ON tbl_pedido_item.pedido      = tbl_faturamento_item.pedido
									AND tbl_pedido_item.peca        = tbl_faturamento_item.peca
		JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
		JOIN    tbl_peca             ON tbl_pedido_item.peca        = tbl_peca.peca
		ORDER   BY tbl_pedido_item.pedido_item";


$sql = "SELECT  DISTINCT
				tbl_pedido_item.pedido_item ,
				tbl_faturamento.nota_fiscal ,
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao , 
				tbl_faturamento_item.peca , 
				tbl_faturamento_item.qtde as qtde_fatura,
				tbl_pedido_item.qtde,
				tbl_peca.peca ,
				tbl_peca.referencia ,
				tbl_peca.descricao
		FROM    tbl_pedido_item
		JOIN    tbl_faturamento_item     ON tbl_pedido_item.pedido      = tbl_faturamento_item.pedido
										AND tbl_pedido_item.peca        = tbl_faturamento_item.peca
		JOIN    tbl_faturamento          ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
		JOIN    tbl_peca                 ON tbl_pedido_item.peca        = tbl_peca.peca
		WHERE   tbl_pedido_item.pedido = $pedido
		ORDER   BY tbl_pedido_item.pedido_item";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	if ($i == 0) {
		echo "<table width='650' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-weight:bold ; text-align:center ' >";
		echo "<td colspan='6'>Notas Fiscais que atenderam a este pedido</td>";
		echo "</tr>";
		echo "<tr bgcolor='#cccccc' style='color: #000000 ; font-size:12px; font-weight:bold ; text-align:center '>";
		echo "<td nowrap>Nota<br>Fiscal</td>";
		echo "<td>Data</td>";
		echo "<td align='left'>Componente</td>";
		echo "<td>Qtde<br>Pedida</td>";
		echo "<td>Qtde<br>Faturada</td>";
		//echo "<td>Qtde<br>Pendente</td>";
		echo "</tr>";
	}
	
	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = '#F1F4FA';
	$pendente = pg_result ($res,$i,qtde) - pg_result ($res,$i,qtde_fatura);
	
	//if ($pendente > 0) $cor = "#ff6666";
	
	echo "<tr bgcolor='$cor' style='font-size:9px ; color: #000000 ; text-align:left' >";
	echo "<td>" . pg_result ($res,$i,nota_fiscal) . "</td>";
	echo "<td>" . pg_result ($res,$i,emissao) . "</td>";
	echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
	echo "<td align='right'>" . pg_result ($res,$i,qtde_fatura) . "</td>";
	//echo "<td align='right'>$pendente</td>";
	echo "</tr>";
}
echo "</table>";

echo "<br>";
?>

<? include "rodape.php"; ?>
