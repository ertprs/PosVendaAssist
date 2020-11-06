<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$sql = "SELECT	tbl_tipo_posto.distribuidor
		FROM	tbl_tipo_posto
		JOIN	tbl_posto_fabrica USING(tipo_posto)
		WHERE	tbl_posto_fabrica.posto   = $login_posto
		AND		tbl_posto_fabrica.fabrica = $login_fabrica
		AND		tbl_tipo_posto.distribuidor IS true";
$res = pg_exec ($con,$sql);

if (pg_result($res,0,distribuidor) <> 't'){
	header("Location: pedido_relacao.php?entrou=s");
	exit;
}

$title = "Relação de Pedido dos Postos";
$layout_menu = 'pedido';
include "cabecalho.php";

?>

<!-- AQUI COMEÇA O SUB MENU - ÁREA DE CABECALHO DOS RELATÓRIOS E DOS FORMULÁRIOS -->

<p>

<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">

<tr>

	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="center">

		<p>

		<table width="100%" border="0" cellspacing="2" cellpadding="0" align='center'>
		<tr height="20" bgcolor="#999999">
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif" align='center'><b>Pedido</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif" align='center'><b>Posto</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif" align='center'><b>Data</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif" align='center'><b>Status</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif" align='center'><b>Tipo Pedido</b></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif" align='center'><b>Valor Total</b></font></td>
			<td>&nbsp;</td>
		</tr>

		<?
		$sql = "SELECT  tbl_pedido.*, 
						tbl_tipo_pedido.descricao AS tipo_pedido_descricao, 
						(
						SELECT tbl_status.descricao AS status
						FROM   tbl_pedido_status
						JOIN   tbl_status USING (status)
						WHERE  tbl_pedido_status.pedido = tbl_pedido.pedido
						ORDER BY tbl_pedido_status.data DESC
						LIMIT 1
						) AS pedido_status,
						tbl_posto.nome
				FROM    tbl_pedido
				JOIN    tbl_tipo_pedido USING (tipo_pedido)
				JOIN    tbl_posto       USING (posto)
				WHERE   tbl_pedido.distribuidor = $login_posto
				AND     tbl_pedido.fabrica      = $login_fabrica
				ORDER BY tbl_pedido.data DESC, tbl_posto.nome ASC ";
		$res = pg_exec ($con,$sql);

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';

		?>
		<tr bgcolor="<? echo $cor ?>" >
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><a href="pedido_finalizado.php?pedido=<? echo pg_result ($res,$i,pedido) ?>"><? echo pg_result ($res,$i,pedido) ?></a></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,nome) ?></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo mostra_data (pg_result ($res,$i,data)) ?></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,pedido_status) ?></font></td>
			<td><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo pg_result ($res,$i,tipo_pedido_descricao) ?></font></td>
			<td align='right'><font size="2" face="Geneva, Arial, Helvetica, san-serif"><? echo number_format (pg_result ($res,$i,total),2,",","."); ?></font></td>
			<td><a href='pedido_posto_detalhe.php?pedido=<? echo pg_result($res,$i,pedido); ?>'><img src='imagens/btn_altera.gif'></a></td>
		</tr>
		<?
			}
		?>

		</table>

	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
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
