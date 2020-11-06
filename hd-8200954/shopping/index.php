<?
include_once "../dbconfig.php";
include_once "../includes/dbconnect-inc.php";
include_once "cabecalho.php";

?>
		<tr>
			<td>
				<form name='frm' method=post action="pedido.php">
				<input type="hidden" name='peca' value=''>
				<input type="hidden" name='qtde' value=''>

<script language="javascript">
<!--
function compra(peca, qtde) {
	//alert(peca);
	//alert(qtde.value);
	document.forms[0].peca.value = peca ;
	document.forms[0].qtde.value = qtde.value ;
	document.forms[0].submit();
}
//-->
</script>

				<table border="0" cellspacing="2" cellpadding="3" width='80%' align='center'>
<?
$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_tabela_item.preco from tbl_peca join tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca where tbl_peca.fabrica = 10 and tbl_peca.ativo IS TRUE";
$res = pg_exec($con,$sql);
$total = pg_numrows($res);

for ($i=0; $i<$total; $i++){
	$peca       = pg_result($res,$i,peca);
	$referencia = pg_result($res,$i,referencia);
	$descricao  = pg_result($res,$i,descricao);
	$preco      = pg_result($res,$i,preco);
	$preco      = number_format($preco,2,',','.');

	echo "<tr>\n";
	echo "<td width='150'>";
	if (is_file("foto/$peca.jpg")) echo "<img src='foto/$peca.jpg' width='150' height='100' border='0'>";
	echo "</td>\n";
	echo "<td>&middot; $referencia <br> &middot; $descricao <br>&middot; Valor <B>R$ $preco</B> <br></td>\n";
	echo "<td width='50'><input type='text' name='qtde_$i' value='1' size='1' maxlength='2'></td>\n";
	echo "<td width='100'><input type='button' name='enviar' value='Incluir no pedido' onClick=\"javascript: compra($peca, document.forms[0].qtde_$i)\"></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='4'><hr color='#dddddd' noshade align='center' size='1' width='100%'></td>\n";
	echo "</tr>\n";
}
?>
				</table>
				</form>
			</td>
		</tr>

<?
include"rodape.php";
?>
