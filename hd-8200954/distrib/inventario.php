<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	color: #333333;

}
</style>
<?
include 'menu.php';

if(strlen($_POST["ano"])>0)$ano = $_POST["ano"];
else             $ano = '2005';

echo "<center><h1>Invetário - $ano</h1></center>";
echo "<form method='post' action='$PHP_SELF'>";
echo "<select name='ano' size='1' >";
echo "<option value=''></option>";
for($i = date("Y"); $i > 2004; $i--){
	echo "<option value='$i'";
	if ($ano == $i) echo " selected";
	echo ">$i</option>";
}
echo "</select>";
echo "<input type='submit' name='btn_acao' value='Visualizar'>";
echo "</form>";

	//AND   tipo_pedido      = 2
//--=== SAIDAS ======================================================================
$sql = "SELECT  tbl_faturamento_item.peca         ,
		tbl_peca.referencia               ,
		tbl_peca.descricao                ,
		sum(preco*qtde)     AS total      ,
		sum(qtde)           AS total_pecas
	FROM tbl_faturamento
	JOIN tbl_faturamento_item USING (faturamento)
	JOIN tbl_posto            USING (posto)
	JOIN tbl_peca             USING (peca)
	WHERE tbl_faturamento.distribuidor = 4311
	AND   tbl_faturamento.fabrica      = 3 

	AND   tbl_faturamento.emissao BETWEEN '$ano-01-01 00:00:00' AND '$ano-12-31 23:59:59'
	AND   (tbl_faturamento.emir IS NOT FALSE OR tbl_faturamento.emissao > '2005-12-01')

	GROUP BY tbl_faturamento_item.peca,
		 tbl_peca.referencia      ,
		 tbl_peca.descricao
	order by  total DESC";

$res = pg_exec ($con,$sql);
$total_pecas_saida = pg_numrows ($res);
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$total            = trim(pg_result ($res,$i,total));
	$total_a_pagar = $total_a_pagar + $total;
}



//--==== ENTRADAS ===================================================================
$sql2 = "	SELECT  tbl_faturamento_item.peca   ,
		tbl_peca.referencia                 ,
		tbl_peca.descricao                  ,
		sum(preco*qtde)     AS total        ,
		sum(qtde)           AS total_pecas
	FROM tbl_faturamento
	JOIN tbl_faturamento_item USING (faturamento)
	JOIN tbl_posto            USING (posto)
	JOIN tbl_peca             USING (peca)
	WHERE tbl_faturamento.posto   = 4311
	AND   tbl_faturamento.fabrica = 3
	AND   tbl_faturamento.distribuidor IS NULL
	AND   tbl_faturamento.emissao BETWEEN '$ano-01-01 00:00:00' AND '$ano-12-31 23:59:59'
	AND   (tbl_faturamento.emir IS NOT FALSE OR tbl_faturamento.emissao > '2006-12-01')
	GROUP BY tbl_faturamento_item.peca,
		 tbl_peca.referencia      ,
		 tbl_peca.descricao
	order by  total DESC";

$res2 = pg_exec ($con,$sql2);
$total_pecas_entrada = pg_numrows ($res2);
for ($i = 0 ; $i < $total_pecas_entrada; $i++) {
	$total_entrada         = trim(pg_result ($res2,$i,total));
	$total_a_pagar_entrada = $total_a_pagar_entrada + $total_entrada;
}
$inventario            = $total_a_pagar - $total_a_pagar_entrada;
$total_a_pagar         = number_format ($total_a_pagar,2,",",".");
$total_a_pagar_entrada = number_format ($total_a_pagar_entrada,2,",",".");
$inventario            = number_format ($inventario,2,",",".");



echo "<table width='600'border='1' cellpadding='2' cellspacing='0' align='center' style='border-collapse: collapse' bordercolor='#000000'>";
echo "<tr bgcolor='fefefe'>";
echo "<td><b>$ano</b></td>";
echo "<td align='center'><b>Peças</b></td>";
echo "<td align='center'><b>Total</b></td>";
echo "</tr>";
echo "<tr bgcolor='fefefe'>";
echo "<td>Saída</td>";
echo "<td align='right'> $total_pecas_saida</td>";
echo "<td align='right'width='200'><font color='009900'><b>R$ $total_a_pagar</b></font></td>";
echo "</tr>";

echo "<tr bgcolor='fefefe' >";
echo "<td>Entrada </td>";
echo "<td align='right'> $total_pecas_entrada</td>";
echo "<td align='right'width='200'><font color='990000'><b>R$ $total_a_pagar_entrada</b></font></td>";
echo "</tr>";

echo "<tr bgcolor='eeeeee' >";
echo "<td colspan='2'></td>";
echo "<td align='right'><font color='003300' size='2'><b>R$ $inventario</font></td>";
echo "</tr>";

echo "</table>";

echo "<a href=\"javascript:var com = document.getElementById('justificativa_$i');com.display = 'block';\">Ver entradas e saídas apuradas</a>";
echo "<div style='display : none;' id='com'>";

$p    = array();
$xx   = $total_pecas_saida;
$cont = 0;

//--==== SAÍDA ===================================================================
if ($total_pecas_saida > 0) {

	$total_a_pagar = 0;
	echo "<br><table border='1' cellpadding='2' cellspacing='2' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='600'>";
	echo "<tr class='Titulo' background='../admin/imagens_admin/azul.gif' height='25'>";
	echo "<td>REFERENCIA</td>";
	echo "<td>DESCRICAO</td>";
	echo "<td>VALOR TOTAL</td>";
	echo "<td>QTDE PEÇAS</td>";
	echo "<td>MÉDIA</td>";
	echo "</tr>";

	for ($i = 0 ; $i < $total_pecas_saida ; $i++) {
		$referencia       = trim(pg_result ($res,$i,referencia));
		$descricao        = trim(pg_result ($res,$i,descricao));
		$total            = trim(pg_result ($res,$i,total));
		$total_pecas      = trim(pg_result ($res,$i,total_pecas));

		$media = $total / $total_pecas;
		$media = number_format (round($media,2),2,",",".");

		$total_a_pagar = $total_a_pagar + $total;
		$total = number_format ($total,2,",",".");

		$p[$i][0] = $referencia;
		$p[$i][1] = $descricao;
		$p[$i][2] = $total;
		$p[$i][3] = $total_pecas;
		$p[$i][4] = $media;
		$p[$i][5] = "";
		$p[$i][6] = "";
		$p[$i][7] = "";

		if($cor=="#F1F4FA")$cor = '#FAFAFA';
		else               $cor = '#F1F4FA';

		echo "<tr class='Conteudo' bgcolor='$cor'height='20' align='center'>";
		echo "<td>$referencia</td>";
 		echo "<td align='left'>$descricao</td>";
		echo "<td align = 'right' width='100'> $total</td>";
		echo "<td align = 'right' width='100'> $total_pecas</td>";
		echo "<td align = 'right' width='100'> $media</td>";
		echo "</tr>";

	}
	$total_a_pagar = number_format ($total_a_pagar,2,",",".") ;
	echo "<tr >";
	echo "<td colspan ='4' class='Titulo'>TOTAL SAÍDA</td>";
	echo "<td  align = 'right'> <b>$total_a_pagar</b></td>";
	echo "</tr>";
	echo "</table>";
}



//--==== ENTRADAS ===================================================================
if ($total_pecas_entrada > 0) {

	$total_a_pagar_entrada = 0;
	echo "<br><table border='1' cellpadding='2' cellspacing='2' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='600'>";
	echo "<tr class='Titulo' background='../admin/imagens_admin/azul.gif' height='25'>";
	echo "<td>REFERENCIA</td>";
	echo "<td>DESCRICAO</td>";
	echo "<td>VALOR TOTAL</td>";
	echo "<td>QTDE PEÇAS</td>";
	echo "<td>MÉDIA</td>";
	echo "</tr>";

	for ($i = 0 ; $i < $total_pecas_entrada ; $i++) {
		$referencia_entrada       = trim(pg_result ($res2,$i,referencia));
		$descricao_entrada        = trim(pg_result ($res2,$i,descricao));
		$total_entrada            = trim(pg_result ($res2,$i,total));
		$total_pecas              = trim(pg_result ($res2,$i,total_pecas));

		$media = $total_entrada/$total_pecas;

		$media = number_format (round($media,2),2,",",".");

		$total_a_pagar_entrada = $total_a_pagar_entrada + $total_entrada;
		$total_entrada = number_format ($total_entrada,2,",",".");

		$ok = 0;
		for($x = 0 ; $x < $total_pecas_saida ; $x++){
			if($p[$x][0] == $referencia_entrada){

				$p[$x][5] = $total_entrada;
				$p[$x][6] = $total_pecas;
				$p[$x][7] = $media;
				$ok = 1;
			}
		}
		if($ok==0){


			$p[$xx][0] = $referencia_entrada;
			$p[$xx][1] = $descricao_entrada;
			$p[$xx][2] = "";
			$p[$xx][3] = "";
			$p[$xx][4] = "";
			$p[$xx][5] = $total_entrada;
			$p[$xx][6] = $total_pecas;
			$p[$xx][7] = $media;
			$xx = $xx + 1;
		}

		if($cor=="#F1F4FA")$cor = '#FAFAFA';
		else               $cor = '#F1F4FA';

		echo "<tr class='Conteudo' bgcolor='$cor'height='20' align='center'>";
		echo "<td>$referencia_entrada</td>";
 		echo "<td align='left'>$descricao_entrada</td>";
		echo "<td align = 'right' width='100'> $total_entrada</td>";
		echo "<td align = 'right' width='100'> $total_pecas</td>";
		echo "<td align = 'right' width='100'> $media</td>";
		echo "</tr>";

	}
	$total_a_pagar_entrada = number_format ($total_a_pagar_entrada,2,",",".") ;
	echo "<tr >";
	echo "<td colspan ='2' class='Titulo'>TOTAL SAÍDA</td>";
	echo "<td  align = 'right'> <b>$total_a_pagar_entrada</b></td>";
	echo "</tr>";
	echo "</table>";
}
echo "</div>";
//echo $xx;
if($xx > 0){
	echo "<br><table border='1' cellpadding='2' cellspacing='2' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' >";

	echo "<tr class='Titulo' background='../admin/imagens_admin/azul.gif' height='25'>";
	echo "<td colspan='2'>PEÇA</td>";
	echo "<td>SAÍDA</td>";
	echo "<td colspan='3'>ENTRADA</td>";
	echo "<td>ESTOQUE</td>";
	echo "</tr>";

	echo "<tr class='Titulo' height='25'>";
	echo "<td>REFERENCIA</td>";
	echo "<td>DESCRICAO</td>";
	echo "<td>QTDE PEÇAS</td>";
//	echo "<td>VALOR TOTAL</td>";
	echo "<td>QTDE PEÇAS</td>";
	echo "<td>MÉDIA PREÇO</td>";
	echo "<td>ESTOQUE</td>";
	echo "</tr>";

	for ($i = 0 ; $i < $xx ; $i++) {

		$total_pecas_em_estoque = $p[$i][6] - $p[$i][3];

		if($cor=="#F1F4FA")$cor = '#FAFAFA';
		else               $cor = '#F1F4FA';

		echo "<tr class='Conteudo' bgcolor='$cor'height='20' align='center'>";

		echo "<td>".$p[$i][0]."</td>";
 		echo "<td align='left'>".$p[$i][1]."</td>";

//		echo "<td align = 'right' width='100'>".$p[$i][2]."</td>";
		echo "<td align = 'right' width='100'>".$p[$i][3]."</td>";
//		echo "<td width='100'>".$p[$i][4]."</td>";

//		echo "<td align = 'right' width='100'>".$p[$i][5]."</td>";
		echo "<td align = 'right' width='100'>".$p[$i][6]."</td>";
		echo "<td width='100'>".$p[$i][7]."</td>";
		echo "<td>$total_pecas_em_estoque</td>";

		echo "</tr>";

	}
}

?>







