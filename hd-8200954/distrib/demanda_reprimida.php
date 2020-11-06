<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'cabecalho.php';

$btn_acao = $_POST['btn_acao'];
if (strlen($btn_acao)==0){
	$btn_acao = $_GET['btn_acao'];
}



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Demanda Reprimida</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>



<center><h1>Demanda Reprimida</h1></center>

<p>
<form name='frm_relatorio' method='post' action='<?=$PHP_SELF?>'
<input type='hidden' name='btn_acao'>
<input type='button' name='btn_gravar' value='Gerar Demanda Reprimida' onClick="if (confirm('Deseja gerar o relatório?')){this.form.btn_acao.value='gerar';this.form.submit()}">
</form>

<?

if ($btn_acao == 'gerar'){
$sql = "SELECT  tbl_peca.referencia                    ,
				tbl_peca.descricao                     ,
				tbl_peca.peca                          ,
				tbl_posto_estoque.qtde AS qtde_estoque ,
				ped.qtde_pedido                        ,
				(SELECT (SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) / 1 )::INT4
					FROM tbl_pedido JOIN tbl_pedido_item USING (pedido) 
					WHERE tbl_pedido.fabrica IN (".implode(",", $fabricas).")
					AND   tbl_pedido.distribuidor  = $login_posto
					AND   tbl_pedido.posto        <> $login_posto
					AND   tbl_pedido.tipo_pedido IN (2,3)
					AND   tbl_pedido.data >= current_date - interval '45 days'
					AND   tbl_pedido_item.peca = tbl_peca.peca
				) AS qtde_giro                         ,
				(SELECT SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) 
					FROM tbl_pedido JOIN tbl_pedido_item USING (pedido) 
					WHERE tbl_pedido.fabrica IN (".implode(",", $fabricas).")
					AND  (tbl_pedido.posto = $login_posto OR (tbl_pedido.tipo_pedido = 3 AND tbl_pedido.distribuidor = $login_posto)) 
					AND  (tbl_pedido.status_pedido NOT IN (3,4,6,13) OR tbl_pedido.status_pedido is null)
					AND   tbl_pedido.tipo_pedido IN (2,3)
					AND   tbl_pedido_item.peca = tbl_peca.peca
				) AS qtde_pendente                     ,
				(SELECT SUM (tbl_faturamento_item.qtde)
					FROM tbl_faturamento_item
					JOIN tbl_faturamento USING (faturamento)
					WHERE tbl_faturamento.fabrica IN (".implode(",", $fabricas).")
					AND   tbl_faturamento.posto   = $login_posto
					AND   tbl_faturamento.conferencia IS NULL
					AND   tbl_faturamento.cancelada   IS NULL
					AND   tbl_faturamento_item.peca = tbl_peca.peca
				) AS qtde_transp                       ,
				(SELECT MAX (tbl_tabela_item.preco)
					FROM tbl_tabela_item
					JOIN tbl_posto_linha ON tbl_tabela_item.tabela = tbl_posto_linha.tabela AND tbl_posto_linha.posto = $login_posto
					WHERE tbl_tabela_item.peca = tbl_peca.peca
				) AS preco                             ,
				(SELECT tbl_produto.linha FROM tbl_produto JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_lista_basica.peca = tbl_peca.peca LIMIT 1) AS linha
		FROM    tbl_peca
		JOIN    (SELECT tbl_pedido_item.peca, SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pedido
				 FROM tbl_pedido_item
				 JOIN tbl_pedido USING (pedido)
				 WHERE tbl_pedido.fabrica IN (".implode(",", $fabricas).")
				 AND   tbl_pedido.posto   <> tbl_pedido.distribuidor
				 AND   tbl_pedido.distribuidor = $login_posto
				 AND   (tbl_pedido.status_pedido_posto NOT IN (3,4,6,13) OR tbl_pedido.status_pedido_posto IS NULL)
				 AND   tbl_pedido.tipo_pedido IN (2,3)
				 GROUP BY tbl_pedido_item.peca
				) ped ON tbl_peca.peca = ped.peca
		LEFT JOIN tbl_posto_estoque ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
		WHERE tbl_peca.produto_acabado IS NOT TRUE
		ORDER BY linha, preco, tbl_peca.referencia";
	

//echo $sql;
#exit;

$res = pg_exec ($con,$sql);

echo "<table width='500' align='center'>";
echo "<tr bgcolor='##6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
echo "<td>Linha</td>";
echo "<td>Peça</td>";
echo "<td>Referência</td>";
echo "<td>Descrição</td>";
echo "<td>Qtde</td>";
echo "<td>Estoque</td>";
echo "<td>Pendente</td>";
echo "<td nowrap>Giro</td>";
echo "<td>Preço</td>";
echo "<td>Pedir</td>";
echo "</tr>";

$qtde_pedido_total     = 0;
$qtde_estoque_total    = 0;
$qtde_pendente_total   = 0;
$qtde_giro_total       = 0;
$qtde_pedir_total      = 0;

$imprimindo = 0 ;

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$linha         = trim(pg_result($res,$i,linha)) ;
	$peca          = trim(pg_result($res,$i,peca)) ;
	$referencia    = trim(pg_result($res,$i,referencia)) ;
	$descricao     = trim(pg_result($res,$i,descricao)) ;
	$preco         = trim(pg_result($res,$i,preco));
	$qtde_pedido   = trim(pg_result($res,$i,qtde_pedido));
	$qtde_giro     = trim(pg_result($res,$i,qtde_giro));
	$qtde_estoque  = trim(pg_result($res,$i,qtde_estoque));
	$qtde_pendente = trim(pg_result($res,$i,qtde_pendente));
	$qtde_transp   = trim(pg_result($res,$i,qtde_transp));

	$cor = "#ffffff";
	if ($imprimindo % 2 == 0) $cor = "#DDDDEE";

	if (strlen ($qtde_pendente) == 0) $qtde_pendente = "0";
	if ($qtde_pendente < 0) $qtde_pendente = "0";

	if (strlen ($qtde_estoque)  == 0) $qtde_estoque  = 0;
	if (strlen ($qtde_pendente) == 0) $qtde_pendente = 0;
	if (strlen ($qtde_transp)   == 0) $qtde_transp   = 0;
	if (strlen ($qtde_giro)     == 0) $qtde_giro     = 0;

	$qtde_pendente += $qtde_transp;
	$pedir = "";
	$estoque_minimo  = number_format ($qtde_giro / 2,0);
	$ponto_de_pedido = number_format ($estoque_minimo / 2,0);

	$tenho   = $qtde_estoque + $qtde_pendente ;
	$demanda = $qtde_pedido + $qtde_giro ;
	$pedir   = $demanda - $tenho;

	if ($tenho > $qtde_pedido) {
		$sobra = $tenho - $qtde_pedido;
		if ($sobra > $estoque_minimo) {
			$pedir = 0;
		}else{
			$pedir = $estoque_minimo - $sobra;
		}
	}

	if ($pedir < $ponto_de_pedido) $pedir = 0;

	if ($pedir <= 0) $pedir = "" ;

	if ($qtde_pedido == $qtde_pendente AND $qtde_estoque < $qtde_pedido ) $cor = '#FFCC99';

	if (strlen ($pedir) > 0 OR 1==2) {
		echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
		echo "<td align='left' nowrap>$linha</td>\n";
		echo "<td align='left' nowrap>$peca</td>\n";
		echo "<td align='left' nowrap>$referencia</td>\n";
		echo "<td align='left' nowrap>$descricao</td>\n";
		echo "<td align='right' nowrap>$qtde_pedido</td>\n";
		echo "<td align='right' nowrap>$qtde_estoque</td>\n";
		echo "<td align='right' nowrap>$qtde_pendente</td>\n";
		echo "<td align='right' nowrap>$qtde_giro</td>\n";
		echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>\n";
		echo "<td align='right' nowrap bgcolor='$cor'>$pedir</td>\n";
		echo "</tr>\n";
		$imprimindo++;
	}else{
		$pedir = 0 ;
	}

	$qtde_pedido_total     += $qtde_pedido;
	$qtde_estoque_total    += $qtde_estoque;
	$qtde_pendente_total   += $qtde_pendente;
	$qtde_giro_total       += $qtde_giro;
	$qtde_pedir_total      += $pedir;

}

echo "<tr bgcolor='##6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
echo "<td>&nbsp;</td>";
echo "<td>&nbsp;</td>";
echo "<td>&nbsp;</td>";
echo "<td>Total</td>";
echo "<td>$qtde_pedido_total</td>";
echo "<td>$qtde_estoque_total</td>";
echo "<td>$qtde_pendente_total</td>";
echo "<td>$qtde_giro_total</td>";
echo "<td>&nbsp;</td>";
echo "<td>$qtde_pedir_total</td>";
echo "</tr>";


echo "</table>\n";

?>


<?
if (1 == 2) {
?>

<hr>

<center><h2>Itens para GARANTIA</h2></center>

<?

$sql = "SELECT  tbl_peca.referencia,
				tbl_peca.descricao ,
				tbl_peca.peca ,
				SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde ,
				estoque.qtde AS estoque 
		FROM    tbl_pedido
		JOIN    tbl_pedido_item USING (pedido)
		JOIN    tbl_peca        USING (peca)
		LEFT JOIN (SELECT peca, qtde FROM tbl_posto_estoque WHERE posto = $login_posto) estoque ON estoque.peca = tbl_peca.peca
		WHERE   tbl_pedido.distribuidor = $login_posto
		AND     tbl_pedido.posto <> $login_posto
		AND     tbl_pedido.fabrica IN (".implode(",", $fabricas).")
		AND     tbl_pedido.tipo_pedido = 3
		GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, estoque.qtde
		HAVING   SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) > 0
		AND     (SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) > estoque.qtde OR estoque.qtde IS NULL)
		ORDER BY qtde DESC";

$res = pg_exec ($con,$sql);

echo "<table width='500' align='center'>";
echo "<tr bgcolor='##6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
echo "<td>Peça</td>";
echo "<td>Referência</td>";
echo "<td>Descrição</td>";
echo "<td>Qtde</td>";
echo "<td>Estoque</td>";
echo "<td>Pendente</td>";
echo "<td>Pedir</td>";
echo "</tr>";


for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$peca        = trim(pg_result($res,$i,peca)) ;
	$referencia  = trim(pg_result($res,$i,referencia)) ;
	$descricao   = trim(pg_result($res,$i,descricao)) ;
	$qtde        = trim(pg_result($res,$i,qtde));
	$estoque     = trim(pg_result($res,$i,estoque));

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#DDDDEE";

	$sql = "SELECT SUM (qtde - qtde_cancelada - qtde_faturada_distribuidor) 
			FROM tbl_pedido_item 
			JOIN tbl_pedido USING (pedido) 
			WHERE tbl_pedido.fabrica IN (".implode(",", $fabricas).")
			AND tbl_pedido_item.peca = $peca
			AND ((tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3) OR (tbl_pedido.posto = $login_posto and tbl_pedido.tipo_pedido = 2) ) ";
	$resX = pg_exec ($con,$sql);
	$pendente = pg_result ($resX,0,0);
	if (strlen ($pendente) == 0) $pendente = "0";
	if ($pendente < 0) $pendente = 0;

	echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
	echo "<td align='left' nowrap>$peca</td>\n";
	echo "<td align='left' nowrap>$referencia</td>\n";
	echo "<td align='left' nowrap>$descricao</td>\n";
	echo "<td align='right' nowrap>$qtde</td>\n";
	echo "<td align='right' nowrap>$estoque</td>\n";
	echo "<td align='right' nowrap>$pendente</td>\n";

	$pedir = "";
	$cor   = "";
	if ($qtde > ($estoque + $pendente)) $pedir = $qtde - ($estoque + $pendente);
	if ($qtde == $pendente AND (strlen ($estoque) == 0 OR $estoque < $qtde) ) $cor = '#FFCC99';

	echo "<td align='right' nowrap bgcolor='$cor'>$pedir</td>\n";
	echo "</tr>\n";
}

echo "</table>\n";

?>


<? } ?>

<?
} //if se gerar
?>

<p>

<? #include "rodape.php"; ?>

</body>
</html>
<?
include'rodape.php';
?>