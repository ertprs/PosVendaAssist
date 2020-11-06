<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

##### CARREGA CONFIGURAÇÃO DA FÁBRICA #####
$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	$os_item_subconjunto = pg_result($res,0,0);
	if (strlen($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}

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
<table width='700' border='0'  cellspacing='1' cellpadding='0'>
	<tr  height='20' bgcolor='#666666'>
		<td align='center'><font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Produto: <?echo $referencia ." - ". $descricao_produto;?></b></font></td>
	</tr>
</table>

<br>

<?
if($ip=="201.13.180.14") echo $sql;
if ($os_item_subconjunto == 't' AND strlen($produto) > 0) {
	$sql = "SELECT  tbl_produto.produto   ,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM    tbl_subproduto
			JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
			WHERE   tbl_subproduto.produto_pai = $produto
			ORDER BY tbl_produto.referencia;";
	$res1 = pg_exec($con,$sql);
	if (pg_numrows($res1) > 0) {
		if($ip=="201.13.180.14") echo $sql;
		for($a = 0 ; $a < pg_numrows($res1) ; $a++) {
			$sub_produto    = trim(pg_result($res1,$a,produto));
			$sub_referencia = trim(pg_result($res1,$a,referencia));
			$sub_descricao  = trim(pg_result($res1,$a,descricao));

			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
			echo "<tr height='20' bgcolor='#666666'>\n";
			echo "<td align='center' colspan='2'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><i>Subconjunto: $sub_referencia - $sub_descricao</i></b></font></td>\n";
			echo "</tr>\n";
			echo "</table>\n\n";
			echo "<br>\n\n";

			$sql =	"SELECT DISTINCT tbl_peca.referencia            ,
							tbl_peca.descricao             
					FROM    tbl_lista_basica
					JOIN    tbl_peca USING (peca)
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $sub_produto ";
					if ($login_fabrica == 14) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
			$sql .= " ORDER BY tbl_peca.referencia";
			$res3 = pg_exec($con,$sql);
if($ip=="201.13.180.14") echo $sql;
			if (pg_numrows($res3) > 0) {
				echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
				echo "<tr height='20' bgcolor='#666666'>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça</b></font></td>\n";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
				echo "</tr>\n";

				for ($c = 0 ; $c < pg_numrows($res3) ; $c++) {

					$peca      = pg_result ($res3,$c,referencia);
					$descricao = pg_result ($res3,$c,descricao);

					echo "<tr>\n";
					echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$peca</font></td>\n";
					echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n\n";
				echo "<br>\n\n";
			}

			$sql = "SELECT  tbl_produto.produto   ,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM    tbl_subproduto
					JOIN    tbl_produto ON tbl_subproduto.produto_filho = tbl_produto.produto
					WHERE   tbl_subproduto.produto_pai = $sub_produto
					ORDER BY tbl_produto.referencia;";
			$res2 = pg_exec($con,$sql);
			if($ip=="201.13.180.14") echo $sql;
			if (pg_numrows($res2) > 0) {
				for($b = 0 ; $b < pg_numrows($res2) ; $b++) {
					$sub_produto    = trim(pg_result($res2,$b,produto));
					$sub_referencia = trim(pg_result($res2,$b,referencia));
					$sub_descricao  = trim(pg_result($res2,$b,descricao));

					echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='0'>\n";
					echo "<tr height='20' bgcolor='#666666'>\n";
					echo "<td align='center' colspan='2'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Subconjunto: $sub_referencia - $sub_descricao</b></font></td>\n";
					echo "</tr>\n";

					$sql =	"SELECT DISTINCT tbl_peca.referencia            ,
									tbl_peca.descricao             
							FROM    tbl_lista_basica
							JOIN    tbl_peca USING (peca)
							WHERE   tbl_lista_basica.fabrica = $login_fabrica
							AND     tbl_lista_basica.produto = $sub_produto";
							if ($login_fabrica == 14) $sql .= " AND tbl_lista_basica.ativo IS NOT FALSE";
					$sql .= " 
							ORDER BY tbl_peca.referencia";
					$res3 = pg_exec($con,$sql);
if($ip=="201.13.180.14") echo $sql;
					if (pg_numrows($res3) > 0) {
						
						echo "<tr height='20' bgcolor='#666666'>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça</b></font></td>\n";
						echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
						echo "</tr>\n";

						for ($c = 0 ; $c < pg_numrows($res3) ; $c++) {

							$peca      = pg_result ($res3,$c,referencia);
							$descricao = pg_result ($res3,$c,descricao);

							echo "<tr>\n";
							echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$peca</font></td>\n";
							echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>\n";
							echo "</tr>\n";
						}
					}

					echo "</table>\n\n";
					echo "<br>\n\n";

				} # Fecha 2º FOR
			}

		} # Fecha 1º FOR
	}
}

include "rodape.php";
?>