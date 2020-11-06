<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$produto = $_GET['produto'];
$tipo    = $_GET['tipo'];

$os= $_GET['os'];
if(strlen($os)>0){
	$sql = "SELECT serie from tbl_os where os = $os and fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	$serie        = @pg_fetch_result($res,0,serie);
}

if($login_fabrica==6){
	header ("Location: peca_consulta_por_produto_tectoy.php?produto=$produto&os=$os");
	exit;
}
$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao
		FROM   tbl_produto
		JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
		WHERE  tbl_produto.produto = $produto
		AND tbl_linha.fabrica = $login_fabrica;";
$res = @pg_query($con,$sql);

$referencia        = @pg_fetch_result($res,0,referencia);
$descricao_produto = @pg_fetch_result($res,0,descricao);

if ($sistema_lingua=='ES') {
	$sql = "SELECT tbl_produto.referencia, tbl_produto_idioma.descricao
					FROM   tbl_produto
					JOIN   tbl_produto_idioma using(produto)
					JOIN   tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE  tbl_produto.produto = $produto
					AND tbl_linha.fabrica = $login_fabrica;";
	$res = @pg_query($con,$sql);

	$referencia        = @pg_fetch_result($res,0,referencia);
	$descricao_produto = @pg_fetch_result($res,0,descricao);
}

$sql = "SELECT    tbl_posto_fabrica.tabela
	FROM     tbl_posto
	JOIN     tbl_posto_fabrica USING(posto)
	WHERE    tbl_posto.posto           = $login_posto
	AND      tbl_posto_fabrica.fabrica = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_num_rows ($res) > 0) {
	$tabela         = pg_fetch_result($res,0,tabela);
}

if ($sistema_lingua=='ES') $title = "Relación de repuesto de los productos";
else $title = "Relação das Peças dos Produtos";
$layout_menu = 'os';
include "cabecalho.php";
?>
<p>
<table width='700' border='0' align='center' cellspacing='1' cellpadding='0'>
	<tr  height='20' bgcolor='#666666'>
		<td align='center'><font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><? if($sistema_lingua == 'ES') echo "Herramienta: "; else echo "Produto: ";echo $referencia ." - ". $descricao_produto;?>
		<?if ($login_fabrica==6) echo "<BR>Série: $serie ";?>
		</b></font></td>
	</tr>
</table>

<?
if (strlen ($produto) > 0) {
	if($login_fabrica == 15 ) {
		# HD 143026
		$sql = " SELECT tbl_kit_peca.referencia as referencia_kit,
						tbl_kit_peca.descricao as descricao_kit,
						tbl_kit_peca_peca.peca,
						tbl_peca.referencia,
						tbl_peca.descricao
				FROM    tbl_kit_peca
				JOIN    tbl_kit_peca_peca USING(kit_peca)
				JOIN    tbl_peca          USING(peca)
				WHERE    tbl_kit_peca.produto = $produto
				AND      tbl_kit_peca.fabrica = $login_fabrica
				ORDER BY tbl_kit_peca.referencia";
		
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			echo "<br/>";
			$resultados = pg_fetch_all($res);
			$referencia_kit_ant = "";
			foreach($resultados as $resultado) { 
				$referencia_kit = $resultado['referencia_kit'];

				if($referencia_kit <> $referencia_kit_ant) {
					echo (!empty($referencia_kit_ant)) ? "</table>" : "";

					echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
					echo "<caption style='border: 1px; background-color:#666666; color:#FFFFFF'>Kit ".$resultado['descricao_kit'];
					echo "<tr height='20' bgcolor='#666666' style='font-weight:bold'>";
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'>";
					echo "Posição";
					echo "</font></td>";
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'>";
					echo "Código Peça";
					echo "</font></td>";
					echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'>";
					echo "Descrição";
					echo "</font></td>";
					echo "</tr>";
				}
				
				echo "<tr style='color: #000000;'>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'></font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>".$resultado['referencia']."</font></td>";
				echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>".$resultado['descricao']."</font></td>";
				echo "</tr>";

				$referencia_kit_ant = $referencia_kit;
			}
		}
	}
	if ($sistema_lingua=='ES') {
		$sql =	"SELECT tbl_lista_basica.lista_basica  ,
					tbl_lista_basica.posicao       ,
					tbl_peca.peca                  ,
					tbl_peca.referencia            ,
					tbl_peca_idioma.descricao      ,
					tbl_lista_basica.serie_inicial ,
					tbl_lista_basica.serie_final   ,
					tbl_lista_basica.type          
			FROM    tbl_lista_basica
			JOIN    tbl_peca USING (peca) 
			LEFT JOIN    tbl_peca_idioma ON tbl_peca.peca = tbl_peca_idioma.peca ";
	} else {	
		$sql =	"SELECT tbl_lista_basica.lista_basica  ,
						tbl_lista_basica.posicao       ,
						tbl_peca.peca                  ,
						tbl_peca.referencia            ,
						tbl_peca.descricao             ,
						tbl_lista_basica.serie_inicial ,
						tbl_lista_basica.serie_final   ,
						tbl_lista_basica.type          ,
						tbl_lista_basica.qtde          
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca) ";
	}
	
			if($login_fabrica == 20 AND $login_pais <>'BR') $sql .= "JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tabela = $tabela ";
			$sql .= " WHERE   tbl_lista_basica.fabrica = $login_fabrica
			AND     tbl_lista_basica.produto = $produto";

			if ($login_fabrica == 50)  $sql .= " AND tbl_lista_basica.ativo IS TRUE ";
			 $sql .= " AND tbl_peca.ativo IS TRUE ";

			if (strlen($tipo) > 0) {
				$sql .= " AND tbl_lista_basica.type = '$tipo' ";
			}
			if ($login_fabrica == 20 and $produto == 20567) {
				$sql .= " AND tbl_peca.acessorio";
			}
	if ($login_fabrica == 6 and strlen($serie)>0) {
		$sql .= " and tbl_lista_basica.serie_inicial < '$serie'
				  and tbl_lista_basica.serie_final > '$serie'
				  ORDER BY rpad(trim (tbl_lista_basica.posicao),20,' '), tbl_peca.descricao, tbl_lista_basica.type";
	}else{
		if($login_fabrica == 2){
			$sql .= " ORDER BY tbl_peca.descricao, tbl_lista_basica.type, lpad(trim (tbl_lista_basica.posicao),20,'0')";
		}else{
			$sql .= " ORDER BY lpad(trim (tbl_lista_basica.posicao),20,'0'), tbl_peca.descricao, tbl_lista_basica.type";
		}
	}
	#echo nl2br($sql);
	$res = pg_query ($con,$sql);

	for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
		if ($i % 20 == 0) {
			if ($i > 0) echo "</table>";
			
			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
			echo "<tr  height='20' bgcolor='#666666'>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo ($sistema_lingua=='ES') ? "Posicion" : "Posição";
			echo "</b></font></td>";
			if ($login_fabrica == 6) {
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Inicial</b></font></td>";
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Final</b></font></td>";
			}
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo ($sistema_lingua=='ES') ? "Pieca" : "Código Peça";
			echo "</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo ($sistema_lingua=='ES') ? "Descrion" : "Descrição";
			echo "</b></font></td>";
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
			$id            = pg_fetch_result ($res,$i,peca);
			$peca          = pg_fetch_result ($res,$i,referencia);
			$descricao     = pg_fetch_result ($res,$i,descricao);
			$serie_inicial = pg_fetch_result ($res,$i,serie_inicial);
			$serie_final   = pg_fetch_result ($res,$i,serie_final);
			$type          = pg_fetch_result ($res,$i,type);
			if($sistema_lingua!='ES') $qtde = pg_fetch_result ($res,$i,qtde);

			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $id AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_query($con,$sql_idioma);
			if (@pg_num_rows($res_idioma) >0) {
				$descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
			}
			//--=== Tradução para outras linguas ===================================================================
			
		}

		echo "<tr>";
		echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>";
		if ($login_fabrica == 6) {
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
