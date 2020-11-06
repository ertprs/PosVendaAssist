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
$res = @pg_query($con,$sql);

$referencia        = @pg_fetch_result($res,0,referencia);
$descricao_produto = @pg_fetch_result($res,0,descricao);

$title = "Relação das Peças dos Produtos";
$layout_menu = 'os';
include "cabecalho.php";
?>
<p>
<table width='700' border='0' align='center' cellspacing='1' cellpadding='0'>
	<tr  height='20' bgcolor='#666666'>
		<td align='center'><font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Produto: <?echo $referencia ." - ". $descricao_produto;?></b></font></td>
	</tr>
</table>

<?
if (strlen ($produto) > 0) {

	$sql =	"SELECT tbl_lista_basica.lista_basica  ,
					tbl_lista_basica.posicao       ,
					tbl_peca.referencia            ,
					tbl_peca.descricao             ,
					tbl_lista_basica.serie_inicial ,
					tbl_lista_basica.serie_final   ,
					tbl_lista_basica.type          ,
					tbl_lista_basica.qtde          
			FROM    tbl_lista_basica
			JOIN    tbl_peca USING (peca)
			WHERE   tbl_lista_basica.fabrica = $login_fabrica
			AND     tbl_lista_basica.produto = $produto";
	if ($login_fabrica == 1 ) {
		$sql .= "
				/*ADD CONDIÇÃO WHERE - HD 20505 - IGOR 20/05/2008 - ESTAVA CADASTRANDO PRODUTOS NOS ITENS DAS OS*/
				AND tbl_peca.referencia not in(
					SELECT referencia
					FROM tbl_produto 
					JOIN tbl_linha using(linha) 
					WHERE fabrica = $login_fabrica
					AND (troca_faturada is true or troca_garantia is true)
				)
				";

	}

	if ($login_fabrica == 6 or $login_fabrica ==15) {
		$sql .= " ORDER BY rpad(trim (tbl_lista_basica.posicao),20,' '), tbl_peca.descricao, tbl_lista_basica.type";
	}else{
		$sql .= " ORDER BY lpad(trim (tbl_lista_basica.posicao),20,'0'), tbl_peca.descricao, tbl_lista_basica.type";
	}
	$res = pg_query ($con,$sql);

	for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
		if ($i % 20 == 0) {
			if ($i > 0) echo "</table>";
			
			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
			echo "<tr  height='20' bgcolor='#666666'>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Posição</b></font></td>";
			if ($login_fabrica == 6 or $login_fabrica ==15) {
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Inicial</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Final</b></font></td>";
			}
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Peça</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Descrição</b></font></td>";
			if ($login_fabrica == 1) {
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Type</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Qtde</b></font></td>";
			}
			echo "</tr>";
		}
			

		if ($i < pg_num_rows ($res) AND strlen ($msg_erro) == 0) {
			$cor       = "#FFFFFF";
			
			$lbm           = pg_fetch_result ($res,$i,lista_basica);
			$posicao       = pg_fetch_result ($res,$i,posicao);
			$peca          = pg_fetch_result ($res,$i,referencia);
			$descricao     = pg_fetch_result ($res,$i,descricao);
			$serie_inicial = pg_fetch_result ($res,$i,serie_inicial);
			$serie_final   = pg_fetch_result ($res,$i,serie_final);
			$type          = pg_fetch_result ($res,$i,type);
			$qtde          = pg_fetch_result ($res,$i,qtde);

			
		}
		
		echo "<tr>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>";
		if ($login_fabrica == 6 or $login_fabrica ==15) {
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$serie_inicial</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$serie_final</font></td>";
		}
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$peca</font></td>";
		echo "<td align='left'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>";
		if ($login_fabrica == 1) {
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$type</font></td>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$qtde</font></td>";
		}
	echo "</tr>";
	}

?>
</table>
<p>
<?	}



include "rodape.php";?>

</body>
</html>