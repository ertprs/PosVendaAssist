<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
?>

<html>
<head>
<title>Peças fora de lista básica</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Peças fora de lista básica</h1></center>

<p>

<center><b>Peças em estoque não cadastradas em lista básica</b></center>


<table width='500' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>Referência</td>
	<td align='center'>Descricao</td>
	<td align='center'>Qtde</td>
</tr>

<?
$sql = "SELECT tbl_peca.peca                              ,
				tbl_peca.referencia                       ,
				tbl_peca.descricao                        ,
				sum(tbl_posto_estoque.qtde) as qtde_estoque
			FROM tbl_posto_estoque 
			LEFT JOIN tbl_lista_basica using(peca)
			JOIN      tbl_peca using(peca)
			WHERE tbl_peca.fabrica = 51
			AND tbl_posto_estoque .posto = 4311
			AND   tbl_lista_basica.peca is null
			GROUP BY tbl_peca.peca, 
					tbl_peca.referencia, 
					tbl_peca.descricao; ";

$res = pg_exec ($con,$sql);
$count=0;
$count_peca =0;
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$peca             = trim(pg_result($res,$i,peca)) ;
	$referencia       = trim(pg_result($res,$i,referencia)) ;
	$descricao        = trim(pg_result($res,$i,descricao)) ;
	$qtde_estoque     = trim(pg_result($res,$i,qtde_estoque));

	
	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";
	
	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='left' nowrap>$referencia</td>\n";
	echo "<td align='left' nowrap>$descricao</td>\n";
	echo "<td align='left' nowrap>$qtde_estoque</td>\n";
	echo "</tr>\n";
	$count++;
	$count_peca = $count_peca + $qtde_estoque;
}

echo "<tr style='font-size: 12px' bgcolor='#FF9933'>\n";
	echo "<td align='center' colspan='2'><b>TOTAL: $count</b></td>\n";
	echo "<td align='left' nowrap><b>$count_peca</b></td>\n";
echo "</tr>";

echo "</table>\n";
?>


<br><br><br>


<center><b>Peças que estão em OS e sem preço(GAMA ITALY)</b></center>


<table width='500' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>Referência</td>
	<td align='center'>Descricao</td>
	<td align='center'>Preço</td>
</tr>

<?
$sql = "SELECT tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_os_produto.os
			FROM tbl_os_produto
			JOIN tbl_os_item using(os_produto)
			JOIN tbl_peca using(peca)
			LEFT JOIN tbl_tabela_item using(peca)
			WHERE fabrica = 51
			AND tbl_tabela_item.preco is null";

$res = pg_exec ($con,$sql);
$count=0;
$count_peca =0;
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$os               = trim(pg_result($res,$i,os)) ;
	$referencia       = trim(pg_result($res,$i,referencia)) ;
	$descricao        = trim(pg_result($res,$i,descricao)) ;

	
	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";
	
	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='left' nowrap>$referencia</td>\n";
	echo "<td align='left' nowrap>$descricao</td>\n";
	echo "<td align='left' nowrap><a href='/assist/os_press.php?os=$os' target='_BLANK'>$os</a></td>\n";
	echo "</tr>\n";
	$count++;
	#$count_peca = $count_peca + $qtde_estoque;
}

echo "<tr style='font-size: 12px' bgcolor='#FF9933'>\n";
	echo "<td align='center' colspan='3'><b>TOTAL: $count</b></td>\n";
#	echo "<td align='left' nowrap><b>$count_peca</b></td>\n";
echo "</tr>";

echo "</table>\n";
?>




</body>
<?
include'rodape.php';
?>