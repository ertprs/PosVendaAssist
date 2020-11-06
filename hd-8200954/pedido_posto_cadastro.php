<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$sql = "SELECT	tbl_tipo_posto.distribuidor
		FROM	tbl_tipo_posto
		JOIN	tbl_posto_fabrica USING(tipo_posto)
		WHERE	tbl_posto_fabrica.posto   = $login_posto
		AND		tbl_posto_fabrica.fabrica = $login_fabrica
		AND		tbl_tipo_posto.distribuidor IS true";
$res = pg_exec ($con,$sql);

if (pg_result($res,0,distribuidor) <> 't' OR strlen($pedido) == 0){
	header("Location: pedido_relacao.php");
	exit;
}

if ($btn_acao == "gravar") {

	$pedido              = $_POST['pedido'];
	$pedido_distribuidor = $_POST['pedido_distribuidor'];
	if (strlen($pedido_distribuidor) == 0) {
		$aux_pedido_distribuidor = "null";
	}else{
		$aux_pedido_distribuidor = "'". $pedido_distribuidor ."'";
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen ($pedido) > 0) {
		#-------------- insere pedido ------------
		$sql = "UPDATE tbl_pedido SET 
					pedido_distribuidor = $aux_pedido_distribuidor
				WHERE pedido = $pedido";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		
	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: pedido_posto_relacao.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

$title       = "Dados do Pedido do Posto";
$layout_menu = 'pedido';
include "cabecalho.php";

#------------ Le Pedido da Base de dados ------------#

$pedido = $_GET['pedido'];
if (strlen($_POST['pedido']) > 0) $pedido = $_POST['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT  tbl_pedido.pedido                                ,
					tbl_pedido.condicao                              ,
					tbl_pedido.tabela                                ,
					tbl_pedido.pedido_cliente                        ,
					tbl_pedido.pedido_distribuidor                   ,
					tbl_condicao.descricao      AS condicao_descricao,
					tbl_tabela.tabela                                ,
					tbl_tabela.descricao        AS tabela_descricao
			FROM    tbl_pedido
			LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			LEFT JOIN tbl_tabela   ON tbl_tabela.tabela     = tbl_pedido.tabela
			WHERE   tbl_pedido.pedido       = $pedido
			AND     tbl_pedido.distribuidor = $login_posto
			AND     tbl_pedido.fabrica      = $login_fabrica;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$pedido           = trim(pg_result ($res,0,pedido));
		$condicao         = trim(pg_result ($res,0,condicao_descricao));
		$tabela           = trim(pg_result ($res,0,tabela));
		$tabela_descricao = trim(pg_result ($res,0,tabela_descricao));
		$pedido_cliente   = trim(pg_result ($res,0,pedido_cliente));
		$pedido_distribuidor = trim(pg_result ($res,0,pedido_distribuidor));
		$detalhar = "ok";
	}
}
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
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}
</style>

<p>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<form name='frm_pedido' method='post' action='<? echo $PHP_SELF; ?>'>
<INPUT TYPE="hidden" name='pedido' value='<? echo $pedido; ?>'>
<tr>
	<td valign="top" align="center">
		<table width="100%" border="0" cellspacing="2" cellpadding="2">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido</b>
				<br>
				<?echo $pedido?>
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
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Pedido Distribuidor</b>
				<br>
				<input type='text' name='pedido_distribuidor' value='<? echo $pedido_distribuidor; ?>'>
				</font>
			</td>
			
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
			<td class='menu_top'>Componente</td>
			<td class='menu_top' align='center'>Quantidade</td>
			<td class='menu_top' align='center'>Preço</td>
			<td class='menu_top' align='center'>Total</td>
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
		<td class='menu_top' colspan='3' align='center'>TOTAL</td>
		<td class='menu_top'align='right' nowrap><? echo number_format ($total_pedido,2,",","."); ?></td>
		</tr>
		</table>
		
	<?
	if ($detalhar == "ok") {
		echo "<br>";
		
		$sql = "SELECT  distinct
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
				ORDER BY tbl_os.sua_os;";
		$res = pg_exec ($con,$sql);
		
		if (pg_numrows($res) > 0) {
			echo "<table width='100%' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr bgcolor='#C0C0C0'>";
			echo "<td class='menu_top' colspan='3'>Ordens de Serviço que geraram o pedido acima</td>";
			echo "</tr>";
			echo "<tr bgcolor='#C0C0C0'>";
			//if ($condicao == "Garantia") {
			if (strpos($condicao,"Garantia") !== false) {
				echo "<td class='menu_top'>Sua OS</td>";
			}
			echo "<td class='menu_top'>Nota Fiscal</td>";
			echo "<td class='menu_top'>Peça</td>";
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
	<td height="27" valign="middle" align="center">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
	</td>
</tr>

</form>

</table>

<p>

<? include "rodape.php"; ?>
