<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';


$msg_erro = "";
$os = $_GET['excluir'];
if (strlen ($os) > 0) {
	$sql = "DELETE FROM tbl_os WHERE os = $os AND posto = $login_posto AND fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);
	if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage ($con);
}


$title = "Relação de Ordem de Serviço";
$layout_menu = 'os';
include "cabecalho.php";


?>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='70%' align='center' bgcolor='#FF6600'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "<font face='arial' size='+1' color='#330066'>$msg_erro</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<table width='100%' border='0'>
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="center">

		<!-- ------------- Formulário ----------------- -->
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr height="20" bgcolor="#bbbbbb">
			<? if ($sistema_lingua=='ES') { ?>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">OS</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Abertura</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Cerramiento</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">ID Consumidor</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Status</font></b></td>
				<td></td>
			<? } else { ?>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">OS Fabr.</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Abertura</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Fechamento</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ</font></b></td>
				<td nowrap><b><font size="2" face="Geneva, Arial, Helvetica, san-serif">Status</font></b></td>
				<td></td>
			<? } ?>
			</tr>
		<?

		$sql = "SELECT * FROM tbl_os WHERE posto = $login_posto AND tbl_os.fabrica = $login_fabrica ORDER BY lpad (sua_os,8,'0') ";
		$res = pg_exec ($con, $sql);

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
		?>
		
		<tr bgcolor="<? echo $cor ?>" >
			<td nowrap><font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#808080"><? echo pg_result ($res,$i,sua_os) ?></td>
			<td nowrap><font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#808080"><? echo mostra_data (pg_result ($res,$i,data_abertura)) ?></td>
			<td nowrap><font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#808080"><? echo mostra_data (pg_result ($res,$i,data_fechamento)) ?></td>
			<td nowrap><font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#808080"><? echo pg_result ($res,$i,consumidor_nome) ?></td>
			<td nowrap><font size="2" face="Geneva, Arial, Helvetica, san-serif" color="#808080"><? echo pg_result ($res,$i,consumidor_cpf) ?></td>
			<td>&nbsp;</td>

			<td nowrap >
				<? if ($sistema_lingua=='ES') { ?>
					<A HREF="os_press.php?os=<? echo pg_result ($res,$i,os) ?>"><img src="imagens/btn_busca.gif"></A>
					<A HREF="os_print.php?os=<? echo pg_result ($res,$i,os) ?>" target="_blank"><img src="imagens/btn_imprime.gif"></A>
					<? 
					if (strlen (pg_result ($res,$i,data_fechamento)) == 0 ) { ?>
						<A HREF="os_item.php?os=<? echo pg_result ($res,$i,os) ?>"><img src="imagens/btn_lanzar.gif"></A>
					<? } ?>
					<? if (strlen (pg_result ($res,$i,data_fechamento)) == 0 ) { ?>
						<A HREF="javascript: if (confirm ('Deseja realmente excluir OS <? echo pg_result ($res,$i,sua_os) ?> ?') == true) { window.location='<? echo $PHP_SELF ?>?excluir=<? echo pg_result ($res,$i,os) ?>' }"><img src="imagens/btn_excluir.gif"></A>
					<? } ?>
				<? } else { ?>
					<A HREF="os_press.php?os=<? echo pg_result ($res,$i,os) ?>"><img src="imagens/btn_consulta.gif"></A>
					<A HREF="os_print.php?os=<? echo pg_result ($res,$i,os) ?>" target="_blank"><img src="imagens/btn_imprime.gif"></A>
					<? 
					if (strlen (pg_result ($res,$i,data_fechamento)) == 0 ) { 
					?>
					<A HREF="os_item.php?os=<? echo pg_result ($res,$i,os) ?>"><img src="imagens/btn_lanca.gif"></A>
					<? } ?>
					<? 
					if (strlen (pg_result ($res,$i,data_fechamento)) == 0 ) { 
					?>
					<A HREF="javascript: if (confirm ('Deseja realmente excluir OS <? echo pg_result ($res,$i,sua_os) ?> ?') == true) { window.location='<? echo $PHP_SELF ?>?excluir=<? echo pg_result ($res,$i,os) ?>' }"><img src="imagens/btn_excluir.gif"></A>
					<? } ?>
				<? } ?>

			</td>
		</tr>
		
		<? } ?>
		</table>


	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
<tr>
<td>

</td>
</tr>
</table>

<p>

<? include "rodape.php";?>
