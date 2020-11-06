<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_fabrica == 1){
	header("Location: pedido_blackedecker_finalizado.php?pedido=".$_GET['pedido']);
	exit;
}

$liberar_preco = true ;
if ($login_fabrica == 3 AND $login_e_distribuidor <> true AND ($login_distribuidor == 1007 OR $login_distribuidor == 560)) $liberar_preco = false;


#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];
if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                ,
					tbl_pedido.condicao                              ,
					tbl_pedido.tabela                                ,
					tbl_pedido.distribuidor                          ,
					tbl_pedido.pedido_cliente                        ,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS pedido_data ,
					tbl_condicao.descricao      AS condicao_descricao,
					tbl_tipo_pedido.descricao   AS tipo_descricao    ,
					tbl_tabela.tabela                                ,
					tbl_tabela.descricao        AS tabela_descricao  ,
					tbl_posto_fabrica.codigo_posto                   ,
					tbl_posto.nome              AS posto_nome        ,
					distrib.fantasia            AS distrib_fantasia  ,
					distrib.nome                AS distrib_nome
			FROM    tbl_pedido
			JOIN    tbl_posto           ON tbl_pedido.posto            = tbl_posto.posto
			JOIN    tbl_posto_fabrica   ON tbl_posto.posto             = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
			LEFT JOIN tbl_condicao      ON tbl_condicao.condicao       = tbl_pedido.condicao
			LEFT JOIN tbl_tipo_pedido   ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = $login_fabrica
			LEFT JOIN tbl_tabela        ON tbl_tabela.tabela           = tbl_pedido.tabela
			LEFT JOIN tbl_posto distrib ON tbl_pedido.distribuidor     = distrib.posto
			WHERE   tbl_pedido.pedido  = $pedido
			AND     tbl_pedido.fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido           = trim(pg_result ($res,0,pedido));
		$condicao         = trim(pg_result ($res,0,condicao_descricao));
		$distribuidor     = trim(pg_result ($res,0,distribuidor));
		$tipo_pedido      = trim(pg_result ($res,0,tipo_descricao));
		$tabela           = trim(pg_result ($res,0,tabela));
		$tabela_descricao = trim(pg_result ($res,0,tabela_descricao));
		$pedido_cliente   = trim(pg_result ($res,0,pedido_cliente));
		$pedido_data      = trim(pg_result ($res,0,pedido_data));
		$distrib_fantasia = trim(pg_result ($res,0,distrib_fantasia));
		$codigo_posto     = trim(pg_result ($res,0,codigo_posto));
		$posto_nome       = trim(pg_result ($res,0,posto_nome));
		
		if (strlen ($distrib_fantasia) == 0) $distrib_fantasia = substr (trim(pg_result ($res,0,distrib_nome)),0,15);
		if (strlen ($distrib_fantasia) == 0) $distrib_fantasia = '<b>Fabrica</b>';

		if ($condicao == "Garantia" OR $tipo_pedido == "Garantia") $detalhar = "ok";
		$detalhar = "ok";
	}
}


$title = "CONFIRMAÇÃO DE PEDIDO DE PEÇAS";
$layout_menu = 'pedido';

include "cabecalho.php";
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td valign="top" align="center">
		<table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Atenção:</b>
				Pedidos a prazo dependerão de análise do departamento de crédito.
				</font>
			</td>
		</tr>
		</table>
		
		
		<table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
		<tr>
			<td class='menu_top'>Emitente do Pedido</td>
		</tr>

		<tr>
			<td align='center' class='table_line1'><?echo $codigo_posto . " - " . $posto_nome ?></td>
		</tr>
		</table>
		
		
		<table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
		<tr>
			<td class='menu_top'>Pedido</td>
			<? if (strlen($pedido_cliente) > 0) { ?>
			<td class='menu_top'>Pedido Cliente</td>
			<? } ?>
			<td class='menu_top'>Data</td>
			<td class='menu_top'>Condição Pagamento</td>
			<td class='menu_top'>Tipo Pedido</td>
			<td class='menu_top'>Tabela de Preços</td>
			<td class='menu_top'>Atendido Por</td>
		</tr>
		<tr>
			<td align='center' class='table_line1'><?echo $pedido?></td>
			<? if (strlen($pedido_cliente) > 0) { ?>
			<td align='center' class='table_line1'><?echo $pedido_cliente?></td>
			<? } ?>
			<td align='center' class='table_line1'><?echo $pedido_data?></td>
			<td align='center' class='table_line1'><?echo $condicao?></td>
			<td align='center' class='table_line1'><?echo $tipo_pedido?></td>
			<td align='center' class='table_line1'><?echo $tabela_descricao?></td>
			<td align='center' class='table_line1'><?echo $distrib_fantasia?></td>
		</tr>
		</table>
		<br>
		<table width="100%" border="0" cellspacing="1" cellpadding="3" align='center'>
		<tr height="20">
			<td class='menu_top'>Componente</td>
			<td class='menu_top'>Qtde Pedida</td>
			<td class='menu_top'>Qtde Cancelada</td>
			<td class='menu_top'>Qtde Faturada</td>
			<td class='menu_top'>Pendência do Pedido</td>
			<td class='menu_top'>Pendência Total</td>
			<? if ($liberar_preco) { ?>
				<td class='menu_top'>Preço (R$)</td>
				<td class='menu_top'>IPI (%)</td>
				<td class='menu_top'>Total c/ IPI (R$)</td>
			<? } ?>
		</tr>
		
		<?
		$sql = "SELECT  case when $login_fabrica = 14 then 
				rpad (sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))),7,0)::float 
				else 
				      sum(tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100))) 
				end as total_pedido
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $pedido
				GROUP BY tbl_pedido.pedido";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$total_pedido = pg_result($res,0,total_pedido);
		}else{
			$total_pedido = 0;
		}
		
		$sql = "SELECT  tbl_pedido_item.peca           ,
						tbl_peca.referencia            ,
						tbl_peca.descricao             ,
						tbl_peca.ipi                   ,
						to_char(tbl_peca.previsao_entrega,'DD/MM/YYYY') AS previsao_entrega    ,
						tbl_pedido_item.qtde           ,
						tbl_pedido_item.qtde_faturada  ,
						tbl_pedido_item.qtde_faturada_distribuidor  ,
						tbl_pedido_item.qtde_cancelada ,
						tbl_pedido_item.preco          ,
						tbl_pedido.desconto            ,
						case when $login_fabrica = 14 then rpad (tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)),7,0)::float else tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (tbl_peca.ipi / 100)) end as total
				FROM  tbl_pedido
				JOIN  tbl_pedido_item USING (pedido)
				JOIN  tbl_peca        USING (peca)
				WHERE tbl_pedido_item.pedido = $pedido
				ORDER BY tbl_pedido_item.pedido_item;";
		$res = pg_exec ($con,$sql);
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$peca           = pg_result ($res,$i,peca);
			$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
			$qtde           = pg_result ($res,$i,qtde);
			$qtde_faturada  = pg_result ($res,$i,qtde_faturada);
			$qtde_faturada_distribuidor  = pg_result ($res,$i,qtde_faturada_distribuidor);
			$qtde_cancelada = pg_result ($res,$i,qtde_cancelada);
			$preco          = pg_result ($res,$i,preco);
			$desconto       = pg_result ($res,$i,desconto);
			$ipi            = pg_result ($res,$i,ipi);
			$total          = pg_result ($res,$i,total);
			$previsao_entrega = pg_result ($res,$i,previsao_entrega);
			
			#$total = $qtde * ($preco * (1 + ($ipi/100)));
			#$total_pedido += $total ;
			
			### LUIS 15/09
			if ($login_fabrica <> 14) {
				$preco = number_format ($preco,2,",",".");
				$total = number_format ($total,2,",",".");
			}else{
				$preco = str_replace (".",",",$preco);
				$total = str_replace (".",",",$total);
			}
			
			if ($login_fabrica <> 3 and $login_fabrica <> 11) {

				// VERIFICA PENDÊNCIAS DESTA PEÇA NO FATURAMENTO PARA O PEDIDO ESPECÍFICO
				$sql = "SELECT  tbl_faturamento_item.pendente
						FROM    tbl_faturamento_item
						JOIN    tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
						WHERE   tbl_faturamento_item.peca = $peca
						AND     tbl_faturamento.pedido    = $pedido;";
				$resx = pg_exec ($con,$sql);
				
				if (pg_numrows($resx) > 0) $pendente = trim(pg_result($resx,0,pendente));
				else                       $pendente = $qtde;
				
				// VERIFICA PENDÊNCIA TOTAL DO ITEM NO FATURAMENTO PARA O POSTO
				###############################################################################
				# ESTAVA ASSIM, MAS TEM QUE AGRUPAR COM OS NÃO FATURADOS
				$sql = "SELECT  sum(tbl_faturamento_item.pendente) AS pendencia_total
						FROM    tbl_faturamento_item
						JOIN    tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
						WHERE   tbl_faturamento_item.peca = $peca
						AND     tbl_faturamento.posto     = $login_posto
						AND     tbl_faturamento.fabrica   = $login_fabrica;";
				###############################################################################
				
				###############################################################################
				# BUSCA OS PEDIDOS COM FATURAMENTO E OS PEDIDOS JÁ EXPORTADOS E SEM FATURAMENTO
				$sql = "SELECT  sum(x.pendencia_total) AS pendencia_total
						FROM (
							(
								SELECT tbl_faturamento_item.pendente AS pendencia_total
								FROM   tbl_faturamento_item
								JOIN   tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
								WHERE  tbl_faturamento_item.peca = $peca
								AND    tbl_faturamento.posto     = $login_posto
								AND    tbl_faturamento.fabrica   = $login_fabrica
								GROUP BY tbl_faturamento_item.pendente
							) UNION (
								SELECT sum(tbl_pedido_item.qtde) AS pendencia_total
								FROM   tbl_pedido_item
								JOIN   tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
								WHERE  tbl_pedido_item.peca = $peca
								AND    tbl_pedido.posto     = $login_posto
								AND    tbl_pedido.fabrica   = $login_fabrica
								AND    tbl_pedido.exportado NOTNULL
								AND    tbl_pedido.pedido NOT IN (
									SELECT tbl_faturamento.pedido
									FROM   tbl_faturamento
									WHERE  tbl_faturamento.fabrica = $login_fabrica
								)
							)
						) AS x;";
				$resx = pg_exec ($con,$sql);
				
				if (pg_numrows($resx) > 0)        $pendente_total = trim(pg_result($resx,0,pendencia_total));
				if (strlen($pendente_total) == 0) $pendente_total = $qtde;
				
				// CASO A PENDÊNCIA TOTAL = 0 E A PENDÊNCIA DO PEDIDO NÃO TENHA SIDO FATURADA
				// A PENDÊNCIA SERÁ A QUANTIDADE PEDIDA
				if ($pendente_total == 0 AND $pendente > 0) $pendente_total = $qtde;
			}

		?>
		<tr bgcolor="<? echo $cor ?>" >
			<td class='table_line1' nowrap><? echo $peca_descricao ?></td>
			<td class='table_line1' align='right'><? echo $qtde ?></td>
			<td class='table_line1' align='right'><font color='#FF0000'><b>
				<?
				if ($qtde_cancelada == 0 OR strlen($qtde_cancelada) == 0) echo "&nbsp;";
				else echo $qtde_cancelada;
				?>
			</b></font></td>

			<? if ($login_fabrica == 3 and $distribuidor == 4311) $qtde_faturada = $qtde_faturada_distribuidor; ?>

			<td class='table_line1' align='right'><? echo $qtde_faturada ?></td>
			<? 
			if ($login_fabrica == 3 or $login_fabrica == 11) {
				$qtde_pendente = $qtde - $qtde_faturada - $qtde_cancelada;
				echo "<td class='table_line1' align='right'>";
				if ($qtde_pendente == 0 OR strlen($qtde_pendente) == 0) echo "&nbsp;";
				else echo $qtde_pendente;
				echo "</td>";
				echo "<td></td>";
			}else{
				echo "<td class='table_line1' align='right'> $pendente </td>";
				echo "<td class='table_line1' align='right'> $pendente_total </td>";
			}
			if ($liberar_preco) {
				echo "<td class='table_line1' align='right'> $preco </td>";
				echo "<td class='table_line1' align='right'> $ipi </td>";
				echo "<td class='table_line1' align='right'> $total </td>";
			} 

			if (strlen($previsao_entrega) > 0) {
				echo "<tr bgcolor='$cor'>";
				echo "<td colspan='9'>";
				echo "<font face='Verdana' size='1' color='#CC0066'>";
				echo "Esta peça estará disponível em $previsao_entrega";
				echo "<br>";
				echo "Para as peças com prazo de fornecimento superior a 25 dias, a fábrica tomará as medidas necessárias para atendimento do consumidor.";
				echo "</font>";
				
				echo "</td>";
				echo "</tr>";
			}

			?>
		</tr>
		<?
		}
		?>

		<? if ($liberar_preco) { ?>
			<tr>
			<td colspan='8' align='center' class='menu_top'>TOTAL</td>
			<td align='right' class='table_line1'><b>
				<?
				### LUIS 15/09
				if ($login_fabrica <> 14) {
					echo number_format ($total_pedido,2,",",".");
				}else{
					echo str_replace (".",",",$total_pedido);
				}
				?></b></td>
			</tr>
		<? } ?>
		</table>
		
	<?
	if ($detalhar == "ok") {
		echo "<br>";
		
		$sql = "SELECT  distinct
						lpad(tbl_os.sua_os,10,0) ,
						tbl_peca.peca      ,
						tbl_peca.referencia,
						tbl_peca.descricao ,
						tbl_os.os          ,
						tbl_os.sua_os      ,
						tbl_os.revenda_nome,
						tbl_os_item_nf.nota_fiscal
				FROM    tbl_pedido
				JOIN    tbl_pedido_item		ON  tbl_pedido_item.pedido     = tbl_pedido.pedido
				JOIN    tbl_peca			ON  tbl_peca.peca              = tbl_pedido_item.peca
				JOIN    tbl_os_item			ON  tbl_os_item.peca           = tbl_pedido_item.peca
											AND tbl_os_item.pedido         = tbl_pedido.pedido
				LEFT JOIN tbl_os_produto	ON  tbl_os_produto.os_produto  = tbl_os_item.os_produto
				LEFT JOIN tbl_os			ON  tbl_os.os                  = tbl_os_produto.os
				LEFT JOIN tbl_os_item_nf	ON  tbl_os_item.os_item        = tbl_os_item_nf.os_item
				WHERE   tbl_pedido_item.pedido = $pedido
				ORDER BY tbl_peca.descricao";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table width='100%' border='0' cellspacing='1' cellpadding='3' align='center'>";
			echo "<tr>";
			echo "<td align='center' colspan='3' class='menu_top'>Ordens de Serviço que geraram o pedido acima</td>";
			echo "</tr>";
			echo "<tr>";
			//if ($condicao == "Garantia") {
			if (strpos($condicao,"Garantia") !== false OR strpos ($tipo_pedido,"Garantia") !== false OR strpos ($tipo_pedido,"antecipada") !== false ) {
				echo "<td class='menu_top'>Sua OS</td>";
			}
			
			if ( ( ($login_fabrica <> 3) or ($login_fabrica==3 and $login_e_distribuidor == 't') ) and $login_fabrica <> 11) {
				echo "<td class='menu_top'>Nota Fiscal</td>";
				
			}

			if ($login_fabrica == 11) {
				echo "<td class='menu_top'>Revenda</td>";
			}

			echo "<td class='menu_top'>Peça</td>";
			echo "</tr>";
			
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$cor = "#FFFFFF";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				
				$peca           = pg_result ($res,$i,peca);
				$os             = pg_result ($res,$i,os);
				$sua_os         = pg_result ($res,$i,sua_os);
				$revenda_nome   = trim(pg_result ($res,$i,revenda_nome));
				$peca_descricao = pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao);
				$nota_fiscal    = trim(pg_result ($res,$i,nota_fiscal));
				
				$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento.pedido    = $pedido
						AND     tbl_faturamento_item.peca = $peca
						ORDER BY lpad(tbl_faturamento.nota_fiscal,20,0) ASC;";
				$resx = pg_exec ($con,$sql);
				
				if (strlen ($nota_fiscal) == 0) {
					if (pg_numrows ($resx) > 0) {
						$xnf = "";
						for ($x=0; $x < pg_numrows($resx); $x++) {
							$nf   = trim(pg_result($resx,$x,nota_fiscal));
							$linx[$x] = "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca'>$nf</a><br>";
						}
						$link = 1;
						$qtde_link = $x;
					}else{
						if ($login_fabrica == 2)
							$nf = "OK";
						else
							$nf = "Pendente";
						$link = 0;
						$qtde_link = 0;
					}
				}else{
					$nf = $nota_fiscal;
					$link = 1;
					$qtde_link = 0;
				}
				
				if (strlen($sua_os) == 0) $sua_os = $os;
				
				echo "<tr bgcolor='$cor'>";
				//if ($condicao == "Garantia") {
				if (strpos($condicao,"Garantia") !== false OR strpos ($tipo_pedido,"Garantia") !== false OR strpos ($tipo_pedido,"antecipada") !== false ) {
					echo "<td class='table_line1' align='center'><a href='os_press.php?os=$os'>$sua_os</a></td>";
				}

				if ( ( ($login_fabrica <> 3) or ($login_fabrica==3 and $login_e_distribuidor == 't') ) and $login_fabrica <> 11) {
					echo "<td align='center' class='table_line1'>";
					if (strtolower($nf) <> 'pendente'){
						if ($link == 1) {
							if ($qtde_link > 0) {
								for ($x=0; $x < $qtde_link; $x++) {
									$link = $linx[$x];
									echo $link;
								}
							}else{
								if ($login_fabrica==3 and $login_e_distribuidor == 't') {
									echo "$nf";
								} else {
									echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca'>$nf</a>";
								}
							}
						}
						else
							echo "$nf";
					}else{
						echo "$nf &nbsp;";
					}
					echo "</td>";
				}

				if ($login_fabrica == 11) {
					echo "<td align='left' class='table_line1'>".$revenda_nome."</td>";
				}
				echo "<td align='left' class='table_line1'>$peca_descricao</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
	?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3">
		<br>
		<a href="pedido_cadastro.php"><img src='imagens/btn_lancarnovopedido.gif'></a>
		&nbsp;&nbsp;
<?
		if ($_GET['loc'] == 1) {
			$link = "pedido_cadastro.php?pedido=$pedido";		// se veio de pedido_cadastro.php, para retorno
		}else{
			$link = "javascript:history.back()";
		}
?>
		<a href="<? echo $link; ?>"><img src='imagens/btn_voltar.gif'></a>
	</td>
</tr>



</form>


</table>


<!------------ Atendimento Direto de Pedidos ------------------- -->
<?
$sql = "SELECT posto, distribuidor FROM tbl_pedido WHERE pedido = $pedido";
$res = pg_exec ($con,$sql);

if (pg_result ($res,0,posto) <> pg_result ($res,0,distribuidor) AND strlen (pg_result ($res,0,distribuidor)) > 0) {
	echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Pedido atendido via distribuidor</h2>";

	#------------- Atendimento TELECONTROL -------------------
	if (pg_result ($res,0,distribuidor) == 4311) {
		echo "<table width='550' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
		echo "<td nowrap>Nota Fiscal</td>";
		echo "<td>Data</td>";
		echo "<td>Peça</td>";
		echo "<td>Qtde</td>";
		echo "</tr>";

		$sql = "SELECT  tbl_faturamento.faturamento ,
						tbl_faturamento.nota_fiscal ,
						to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao , 
						tbl_peca.referencia         ,
						tbl_faturamento_item.qtde   ,
						tbl_peca.descricao
				FROM    tbl_faturamento_item
				JOIN    tbl_faturamento USING (faturamento)
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_faturamento.distribuidor = $distribuidor
				AND     tbl_faturamento.fabrica = $login_fabrica
				AND     tbl_faturamento_item.pedido = $pedido
				ORDER BY  tbl_faturamento.nota_fiscal , tbl_peca.referencia";

		$res = pg_exec ($con,$sql);
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$nota_fiscal = trim (pg_result ($res,$i,nota_fiscal));

			echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
			echo "<td><a href='nf_detalhe_britania.php?faturamento=" . pg_result ($res,$i,faturamento) . "'><b>". $nota_fiscal . "</b></td>";
			echo "<td>" . pg_result ($res,$i,emissao) . "</td>";
			echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
			echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	#------------- Atendimento GARANTIA DISTRIBUIDOR -------------------
	if (@pg_result ($res,0,distribuidor) <> 4311 AND $tipo_pedido == "Garantia" ) {
		echo "<table width='550' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
		echo "<td nowrap>O.S.</td>";
#		echo "<td nowrap>Consumidor</td>";
		echo "<td nowrap>Nota Fiscal</td>";
		echo "<td>Data</td>";
		echo "<td>Peça</td>";
		echo "<td>Qtde</td>";
		echo "</tr>";

		//wellington - Para exibir pedidos atendidos via distriuidor também na tela do distribuidor
		if ($login_e_distribuidor == true) {
			$sqlp = "SELECT posto FROM tbl_pedido WHERE pedido = $pedido";
			$resp = pg_exec ($con,$sqlp);
			$dposto = pg_result($resp,0,0);
		} else {
			$dposto = $login_posto;
		}

		$sql = "SELECT  tbl_os.sua_os ,
						tbl_os.consumidor_nome,
						tbl_os_item_nf.nota_fiscal ,
						TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf ,
						tbl_peca.referencia         ,
						tbl_os_item_nf.qtde_nf   ,
						tbl_peca.descricao
				FROM    tbl_os
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item    USING (os_produto)
				JOIN    tbl_os_item_nf USING (os_item)
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_os.posto   = $dposto
				AND     tbl_os.fabrica = $login_fabrica
				AND     tbl_os_item.pedido = $pedido
				ORDER BY  tbl_os_item_nf.nota_fiscal , tbl_peca.referencia";

		$res = pg_exec ($con,$sql);
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$nota_fiscal = trim (pg_result ($res,$i,nota_fiscal));

			echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
#			echo "<td><a href='nf_detalhe_britania.php?faturamento=" . pg_result ($res,$i,faturamento) . "'><b>". $nota_fiscal . "</b></td>";
			echo "<td nowrap><b>". pg_result ($res,$i,sua_os) . "</b></td>";
#			echo "<td>". pg_result ($res,$i,consumidor_nome) . "</td>";
			echo "<td><b>". $nota_fiscal . "</b></td>";
			echo "<td>" . pg_result ($res,$i,data_nf) . "</td>";
			echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
			echo "<td align='right'>" . pg_result ($res,$i,qtde_nf) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	#------------- Atendimento FATURADO DISTRIBUIDOR -------------------
	if (@pg_result ($res,0,distribuidor) <> 4311 AND $tipo_pedido == "Venda" ) {
		echo "<table width='550' align='center' border='0' cellspacing='3'>";
		echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
		echo "<td nowrap>Nota Fiscal</td>";
		echo "<td>Data</td>";
		echo "<td>Peça</td>";
		echo "<td>Qtde</td>";
		echo "</tr>";

		//wellington - Para exibir pedidos atendidos via distriuidor também na tela do distribuidor
		if ($login_e_distribuidor == true) {
			$sqlp = "SELECT posto FROM tbl_pedido WHERE pedido = $pedido";
			$resp = pg_exec ($con,$sqlp);
			$dposto = pg_result($resp,0,0);
		} else {
			$dposto = $login_posto;
		}

		$sql = "SELECT  tbl_pedido_item_nf.nota_fiscal ,
						TO_CHAR (tbl_pedido_item_nf.data_nf,'DD/MM/YYYY') AS data_nf ,
						tbl_peca.referencia         ,
						tbl_pedido_item_nf.qtde_nf   ,
						tbl_peca.descricao
				FROM    tbl_pedido_item
				JOIN    tbl_pedido         USING (pedido)
				JOIN    tbl_pedido_item_nf USING (pedido_item)
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_pedido.posto   = $dposto
				AND     tbl_pedido.fabrica = $login_fabrica
				AND     tbl_pedido_item.pedido = $pedido
				ORDER BY  tbl_pedido_item_nf.nota_fiscal , tbl_peca.referencia";

		$res = pg_exec ($con,$sql);
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$nota_fiscal = trim (pg_result ($res,$i,nota_fiscal));

			echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
			echo "<td><b>". $nota_fiscal . "</b></td>";
			echo "<td>" . pg_result ($res,$i,data_nf) . "</td>";
			echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
			echo "<td align='right'>" . pg_result ($res,$i,qtde_nf) . "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

}else{
	echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Notas Fiscais que atenderam a este pedido</h2>";
	echo "<table width='450' align='center' border='0' cellspacing='3'>";
	echo "<tr bgcolor='#663399' style='color: #FFFFFF ; font-weight:bold ; text-align:center ' >";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Data</td>";
	echo "<td>Peça</td>";
	echo "<td>Qtde</td>";
	echo "</tr>";
	
	$sql = "SELECT	distinct tbl_faturamento.faturamento , 
					tbl_faturamento.nota_fiscal , 
					to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao , 
					tbl_faturamento_item.faturamento_item,
					tbl_faturamento_item.peca , 
					tbl_faturamento_item.qtde , 
					tbl_peca.peca ,
					tbl_peca.referencia ,
					tbl_peca.descricao
			FROM    (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
			JOIN    tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido AND tbl_pedido_item.peca = tbl_faturamento_item.peca
			JOIN    tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
										AND tbl_faturamento.fabrica = $login_fabrica
			JOIN    tbl_peca             ON tbl_pedido_item.peca = tbl_peca.peca
			ORDER   BY tbl_peca.descricao";
	$res = pg_exec ($con,$sql);
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		echo "<tr style='font-size:9px ; color: #000000 ; text-align:left' >";
		echo "<td>" . pg_result ($res,$i,nota_fiscal) . "</td>";
		echo "<td>" . pg_result ($res,$i,emissao) . "</td>";
		echo "<td nowrap>" . pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) . "</td>";
		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>";
		echo "</tr>";
	}
	echo "</table>";

}
?>

<!-- ########## PEDIDO CANCELADO ########## -->
<?
echo "<h2 style='font-size:15px ; color:#000000 ; text-align:center ' >Pedidos cancelados que pertencem a este pedido</h2>";

$sql =	"SELECT tbl_peca.referencia         ,
				tbl_peca.descricao          ,
				tbl_pedido_cancelado.qtde   ,
				tbl_pedido_cancelado.motivo ,
				to_char (tbl_pedido_cancelado.data,'DD/MM/YYYY') AS data ,
				tbl_os.sua_os               
		FROM tbl_pedido_cancelado
		JOIN tbl_peca USING (peca)
		LEFT JOIN tbl_os ON tbl_pedido_cancelado.os = tbl_os.os
		WHERE tbl_pedido_cancelado.pedido  = $pedido
		AND   tbl_pedido_cancelado.fabrica = $login_fabrica";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 2 == 0) $cor = '#F1F4FA';
			else             $cor = "#FFFFFF";
			if ($i == 0) {
				echo "<table width='600' align='center' border='0' cellspacing='3'>";
				echo "<tr bgcolor='#663399' style='color:#FFFFFF; font-weight:bold; text-align:center'>";
				echo "<td>OS</td>";
				echo "<td>Data</td>";
				echo "<td>Peça</td>";
				echo "<td>Qtde</td>";
				echo "</tr>";
				echo "<tr bgcolor='#663399' style='color:#FFFFFF; font-weight:bold; text-align:center'>";
				echo "<td colspan='4'>Motivo</td>";
				echo "</tr>";
			}
			echo "<tr bgcolor='$cor' style='font-size:9px; color:#000000; text-align:left'>";
			echo "<td nowrap align='center' rowspan='2'>".pg_result($res,$i,sua_os)."</td>";
			echo "<td nowrap align='center'>".pg_result($res,$i,data)."</td>";
			echo "<td nowrap>".pg_result($res,$i,referencia)." - ".pg_result($res,$i,descricao)."</td>";
			echo "<td nowrap align='right'>".pg_result($res,$i,qtde)."</td>";
			echo "</tr>";
			echo "<tr bgcolor='$cor' style='font-size:9px; color:#000000; text-align:left'>";
			echo "<td colspan='3' nowrap>".pg_result($res,$i,motivo)."</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<p align='center'>Não há nenhum pedido cancelado.</p>";
	}
?>

<p>

<? include "rodape.php"; ?>
