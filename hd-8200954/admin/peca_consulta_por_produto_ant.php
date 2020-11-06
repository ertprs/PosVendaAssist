<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$produto = $_GET['produto'];

$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao
		FROM   tbl_produto
		JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
		WHERE  tbl_produto.produto = $produto
		AND tbl_linha.fabrica = $login_fabrica;";
$res = @pg_exec($con,$sql);

$referencia        = @pg_result($res,0,referencia);
$descricao_produto = @pg_result($res,0,descricao);

$title = "Relação das Peças dos Produtos";
$layout_menu = 'os';
include "cabecalho.php";
?>
<p>
<table width='600' border='0'  cellspacing='1' cellpadding='0'>
	<tr  height='20' bgcolor='#666666'>
		<td align='center'><font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Produto: <?echo $referencia ." - ". $descricao_produto;?></b></font></td>
	</tr>
</table>

<?
if (strlen ($produto) > 0) {

	$sql = "SELECT      tbl_lista_basica.lista_basica,
						tbl_lista_basica.posicao     ,
						tbl_peca.referencia          ,
						tbl_peca.descricao
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca)
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				ORDER BY lpad(trim (tbl_lista_basica.posicao),20,'0'), tbl_peca.descricao, tbl_lista_basica.type";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		if ($i % 20 == 0) {
			if ($i > 0) echo "</table>";
			
			echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='0'>";
			echo "<tr  height='20' bgcolor='#666666'>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
			echo "</tr>";
		}
			

		if ($i < pg_numrows ($res) AND strlen ($msg_erro) == 0) {
			$cor       = "#FFFFFF";
			
			$lbm       = pg_result ($res,$i,lista_basica);
			$posicao   = pg_result ($res,$i,posicao);
			$peca      = pg_result ($res,$i,referencia);
			$descricao = pg_result ($res,$i,descricao);
						
			
		}
		
		echo "<tr>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$peca</font></td>";
		echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>";
	echo "</tr>";
	}

?>
</table>
<p>
<?	}



include "rodape.php";?>

</body>
</html>