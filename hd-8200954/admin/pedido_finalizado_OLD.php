<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';


#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];
if (strlen ($pedido) > 0) {
	$sql = "SELECT * FROM tbl_pedido WHERE pedido = $pedido AND fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$condicao = pg_result ($res,0,condicao);
	}
}


$titulo = "Confirmação de Pedidos";
$cabecalho = "Confirmação de Pedidos";
$layout_menu = "callcenter";

include "cabecalho.php";
?>

<!------------------AQUI COMEÇA O SUB MENU ---------------------!-->

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#CCCCCC">
<tr>
	<td valign="top" align="center">
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Condição Pagamento</b></font>
				<br>
				<?
				$sql = "SELECT tbl_condicao.* FROM tbl_condicao WHERE condicao = $condicao";
				$resX = pg_exec ($con,$sql);
				echo pg_result ($resX,$i,descricao);
				?>
			</td>
		</tr>
		</table>
				
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Atenção:</b> Pedidos a prazo dependerão de análise do departamento de crédito.</font>
			</td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap align='center'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>PEDIDO:</b> <?echo pg_result ($res,0,pedido)?></font>
			</td>
		</tr>
		</table>
		
		<table width="400" border="0" cellspacing="5" cellpadding="0" align='center'>
		<tr height="20" bgcolor="#bbbbbb">
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Componente</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b>Quantidade</b></font></td>
		</tr>
		
		<?
		$sql = "SELECT tbl_pedido_item.* , tbl_peca.referencia, tbl_peca.descricao FROM tbl_pedido_item JOIN tbl_peca USING (peca) WHERE tbl_pedido_item.pedido = $pedido ORDER BY tbl_pedido_item.pedido_item";
		$res = pg_exec ($con,$sql);

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		?>
		<tr>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,descricao) ?></font></td>
			<td align='right'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,qtde) ?></font></td>
		</tr>
		<?
		}
		?>

		</table>


	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3"  bgcolor="#FFFFFF">
		<a href="pedido_cadastro.php">
		<b><font face="Arial, Helvetica, sans-serif" color="#808080">
		Lançar novo Pedido de Peças
		</font></b>
		</a>
	</td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3"  bgcolor="#FFFFFF">
		<a href="pedido_impressao.php?pedido=$pedido">
		<b><font face="Arial, Helvetica, sans-serif" color="#808080">
		Imprimir Pedido
		</font></b>
		</a>
	</td>
</tr>



</form>


</table>

<p>

<? include "rodape.php"; ?>