<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$produto = $_GET['produto'];

$os= $_GET['os'];
if(strlen($os)>0){
	$sql = "SELECT serie from tbl_os where os = $os and fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	$serie        = @pg_result($res,0,serie);
}

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
<script language="JavaScript">
function mostraPeca(peca){
	if (document.getElementById('dados_'+peca)){
		var style2 = document.getElementById('dados_'+peca); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
</script>
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

		$sql =	"SELECT tbl_lista_basica.posicao       ,
						tbl_peca.peca                  ,
						tbl_peca.referencia            ,
						tbl_peca.descricao             ,
						tbl_lista_basica.serie_inicial ,
						tbl_lista_basica.serie_final   ,
						tbl_lista_basica.type          ,
						tbl_lista_peca.peca_pai        ,
						tbl_lista_peca.peca_filha
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca) 
				left JOIN tbl_lista_peca ON tbl_lista_basica.peca = tbl_lista_peca.peca_pai 
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto";
		if (strlen($serie)>0) {
		$sql .= " and tbl_lista_basica.serie_inicial < '$serie'
				  and tbl_lista_basica.serie_final > '$serie'";
		}
		$sql .= "ORDER BY tbl_lista_peca.peca_pai, tbl_peca.referencia, rpad(trim (tbl_lista_basica.posicao),20,' '), tbl_peca.descricao, tbl_lista_basica.type";
	$res = pg_exec ($con,$sql);
//echo $sql;
$xpeca_filha =  array();
		$xpeca_peca =  array();
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$peca_filha = pg_result ($res,$i,peca_filha);
		if(strlen($peca_filha)>0) $xpeca_filha[] = $peca_filha;
	}
/*
$aux_peca_filha="";
foreach ($xpeca_filha as $key => $value) {
  	if(strlen($value)>0){
  		$aux_peca_filha .=  $value. " , ";
	}
}
$aux_peca_filha = substr($aux_peca_filha,0,strlen($aux_peca_filha)-2);
//echo $aux_peca_filha; 
*/



//print_r($xpeca_filha); 
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		if ($i % 40 == 0) {
			if ($i > 0) echo "</table>";
			
			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
			echo "<tr  height='20' bgcolor='#666666'>";
			echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Posição";
			echo "</b></font></td>";
			echo "<td align='center'  width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Inicial</b></font></td>";
			echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Final</b></font></td>";
			
			echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Código Peça";
			echo "</b></font></td>";
			echo "<td align='center' width='350'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Descrição";
			echo "</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Ação";
			echo "</b></font></td>";
			echo "</tr>";
		}
			
		
		if ($i < pg_numrows ($res) AND strlen ($msg_erro) == 0) {
			$cor       = "#FFFFFF";
			
			

		//	array_push($xpeca_filha,$peca_filha);
			$tem = array_search($referencia,$xpeca_peca);
//if(strlen($tem)>0) continue;
			$peca = pg_result ($res,$i,peca);
			$posicao = pg_result ($res,$i,posicao);
			$referencia = pg_result ($res,$i,referencia);
			$descricao = pg_result ($res,$i,descricao);
			$serie_inicial = pg_result ($res,$i,serie_inicial);
			$serie_final = pg_result ($res,$i,serie_final);
			$type = pg_result ($res,$i,type);
			$peca_pai = pg_result ($res,$i,peca_pai);
			$peca_filha = pg_result ($res,$i,peca_filha);

			$xpeca_peca[]="$referencia";
//			next;

			if(($peca_ant == $peca and strlen($peca_pai)>0) or (in_array($peca,$xpeca_filha))) continue;

			if(strlen($peca_pai)>0){
				$b = "<B>"; $bb="</B>";
			}else{
				$b = ""; $bb="";
			}
			//--=== Tradução para outras linguas ============================= Raphael HD:1212
			if ((strlen($id) > 0) AND (strlen($sistema_lingua) > 0)) {
				$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $id AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
			}
			//--=== Tradução para outras linguas ===================================================================
			
		}



		echo "<tr ";
		if(strlen($b)>0){
			echo " bgcolor='#b8b7af'";
		}

		echo ">";
		echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>";
		echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$serie_inicial</font></td>";
		echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$serie_final</font></td>";
		echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$b $referencia $bb</font></td>";
		echo "<td align='left' width='350'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>";
		echo "<td align='center'>";
		if(strlen($b)>0){echo "<a href=\"javascript:mostraPeca($i);\"><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>abrir</font></a>";}
		echo "</td>";
		echo "</tr>";
		
		if(strlen($b)>0 and 1==1){
			echo "<tr>";
			echo "<td colspan='6'>";
			echo "<div id='dados_$i' style='position:relative; display:none; border: 1px solid #949494;background-color: #D5D4CD;width:100%;'>";
			echo "<table border='0' cellspacing='1' cellpadding='0' bgcolor='#FFFFFF'>";

			$xsql = "SELECT tbl_lista_basica.posicao , 
							tbl_lista_basica.serie_inicial , 
							tbl_lista_basica.serie_final , 
							tbl_peca.peca , 
							tbl_peca.referencia , 
							tbl_peca.descricao 
					FROM (
							SELECT tbl_lista_peca.peca_filha
							FROM tbl_lista_peca
							WHERE tbl_lista_peca.peca_pai = $peca
					) as X
					JOIN  tbl_lista_basica on tbl_lista_basica.peca = X.peca_filha
					JOIN  tbl_peca on tbl_peca.peca = tbl_lista_basica.peca
					WHERE tbl_lista_basica.produto = $produto
					AND   tbl_lista_basica.fabrica = $login_fabrica";
					if (strlen($serie)>0) {
						$xsql .= " AND tbl_lista_basica.serie_inicial < '$serie'
								  AND tbl_lista_basica.serie_final > '$serie'";
					}
			$xsql .="ORDER BY tbl_peca.referencia, tbl_peca.descricao";
			$xres = pg_exec($con,$xsql);

			if(pg_numrows($xres)>0){
				for($y=0;$y<pg_numrows($xres);$y++){
					$xposicao       = pg_result($xres,$y,posicao);
					$xserie_inicial = pg_result($xres,$y,serie_inicial);
					$xserie_final   = pg_result($xres,$y,serie_final);
					$xpeca          = pg_result($xres,$y,peca);
					$xreferencia    = pg_result($xres,$y,referencia);
					$xdescricao     = pg_result($xres,$y,descricao);
					
					echo "<tr bgcolor='#D5D4CD'>";
					echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xposicao</font></td>";
					echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xserie_inicial</font></td>";
					echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xserie_final</font></td>";
					echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$b $xreferencia $bb</font></td>";
					echo "<td align='left' width='350'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xdescricao</font></td>";
					echo "</tr>";
					
				}
			}
			echo "</table>";
			echo "</div>";
		echo "</td>";
		echo "</tr>";
		}
	$peca_ant = $peca;
	}

?>
</table>
<p>
<?	}
//print_r($xpeca_peca);


include "rodape.php";?>

</body>
</html>
