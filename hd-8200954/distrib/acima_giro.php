<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Peças Acima do Giro</title>
</head>

<body>

<? include 'menu.php' ?>

<center><h1>Peças acima do Giro</h1></center>

<p>

<?

#$res = pg_exec ($con,"DROP TABLE xxx_acima");

$sql = "SELECT * INTO xxx_acima FROM (
			SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, giro.qtde_giro, tbl_posto_estoque.qtde AS qtde_estoque, CASE WHEN ped.qtde_pedido IS NOT NULL THEN ped.qtde_pedido ELSE 0 END AS qtde_pedido ,
				(SELECT preco FROM tbl_tabela_item JOIN tbl_posto_linha USING (tabela) WHERE tbl_posto_linha.posto = $login_posto AND tbl_tabela_item.peca = tbl_peca.peca LIMIT 1) AS preco ,
				(SELECT MAX (data)                        FROM tbl_pedido      JOIN tbl_pedido_item      USING (pedido)      WHERE tbl_pedido.posto             = $login_posto AND tipo_pedido = 2 AND tbl_pedido_item.peca = tbl_peca.peca ) AS ultimo_pedido ,
				(SELECT MAX (emissao)                     FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento) WHERE tbl_faturamento.posto        = $login_posto AND tbl_faturamento.cfop ILIKE '61%' AND tbl_faturamento_item.peca = tbl_peca.peca ) AS ultima_compra ,
				(SELECT MAX (emissao)                     FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento) WHERE tbl_faturamento.distribuidor = $login_posto AND tipo_pedido IN (2,3) AND tbl_faturamento_item.peca = tbl_peca.peca) AS ultima_venda ,
				(SELECT MAX (tbl_faturamento.faturamento) FROM tbl_faturamento JOIN tbl_faturamento_item USING (faturamento) WHERE tbl_faturamento.posto        = $login_posto AND tbl_faturamento.cfop ILIKE '61%' AND tbl_faturamento_item.peca = tbl_peca.peca ) AS ultimo_faturamento
			FROM tbl_peca
			LEFT JOIN tbl_posto_estoque ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
			LEFT JOIN (SELECT tbl_pedido_item.peca, (SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) )::int4 AS qtde_giro
					FROM tbl_pedido_item
					JOIN tbl_pedido USING (pedido)
					WHERE tbl_pedido.fabrica      IN (".implode(",", $fabricas).")
					AND   tbl_pedido.posto        <> $login_posto
					AND   tbl_pedido.distribuidor =  $login_posto
					AND   tbl_pedido.tipo_pedido IN (2,3)
					AND   tbl_pedido.data >= current_date - interval '60 days'
					GROUP BY tbl_pedido_item.peca
			) giro ON tbl_peca.peca = giro.peca
			LEFT JOIN (SELECT tbl_pedido_item.peca, (SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) )::int4 AS qtde_pedido
					FROM tbl_pedido_item
					JOIN tbl_pedido USING (pedido)
					WHERE tbl_pedido.fabrica       IN (".implode(",", $fabricas).")
					AND   tbl_pedido.posto        <> $login_posto
					AND   tbl_pedido.distribuidor =  $login_posto
					AND   tbl_pedido.tipo_pedido IN (2,3)
					AND   tbl_pedido.status_pedido_posto NOT IN (3,4,6,13)
					GROUP BY tbl_pedido_item.peca
			) ped ON tbl_peca.peca = ped.peca
			WHERE tbl_posto_estoque.qtde > 0
			AND   (giro.qtde_giro < tbl_posto_estoque.qtde OR giro.qtde_giro IS NULL)
		) sobras 
		WHERE ( (qtde_giro + qtde_pedido) IS NULL OR qtde_estoque - qtde_pedido > qtde_giro * 2 OR ultima_venda IS NULL OR preco IS NULL )
		AND   (ultimo_pedido < CURRENT_DATE - INTERVAL '35 days' OR ultimo_pedido IS NULL)
		ORDER BY ((qtde_estoque - qtde_pedido - CASE WHEN qtde_giro > 0 THEN qtde_giro ELSE 0 END) * preco) DESC , referencia";
#echo $sql;

#$res = pg_exec ($con,$sql);

$res = pg_exec ($con,"SELECT * FROM xxx_acima");



echo "<table align='center' border='1' cellspacing='3' cellpaddin='3'>";
echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td>Peça</td>";
echo "<td>Referência</td>";
echo "<td>Descrição</td>";
echo "<td>Última Compra</td>";
echo "<td>Última Venda</td>";
echo "<td>NF Fábrica</td>";
echo "<td nowrap>Giro Mensal</td>"; 
echo "<td>Estoque</td>";
echo "<td>Preço</td>";
echo "<td>Sobra</td>";
echo "<td>Capital</td>";
echo "</tr>";

$total_giro = 0 ;
$total_capital = 0 ;

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$cor = "";
	if ($i % 2 == 0) $cor = '#dddddd';
	
	echo "<tr bgcolor='$cor' style='font-size:11px'>";

	echo "<td>";
	$peca = pg_result ($res,$i,peca);
	echo $peca;
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,referencia);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,descricao);
	echo "</td>";

	echo "<td>";
	$data = pg_result ($res,$i,ultimo_pedido);
	if (strlen ($data) > 0) $data = substr ($data,8,2) . "/" . substr ($data,5,2) . "/" . substr ($data,0,4);
	echo $data;
	echo "</td>";

	echo "<td>";
	$data = pg_result ($res,$i,ultima_venda);
	if (strlen ($data) > 0) $data = substr ($data,8,2) . "/" . substr ($data,5,2) . "/" . substr ($data,0,4);
	echo $data;
	echo "</td>";



	$faturamento = pg_result ($res,$i,ultimo_faturamento);
	$nota_fiscal = "";
	if (strlen ($faturamento) > 0) {
		$sql = "SELECT nota_fiscal FROM tbl_faturamento WHERE faturamento = $faturamento";
		$resX = pg_exec ($con,$sql);
		$nota_fiscal = pg_result ($resX,0,nota_fiscal);
	}
	echo "<td>";
	echo $nota_fiscal;
	echo "</td>";

	echo "<td align='right'>";
	echo pg_result ($res,$i,qtde_giro);
	echo "</td>";

	echo "<td align='right'>";
	echo pg_result ($res,$i,qtde_estoque);
	echo "</td>";


	
	$preco = 0;
	if (strlen ($faturamento) > 0) {
		$sql = "SELECT preco FROM tbl_faturamento_item WHERE faturamento = $faturamento AND peca = $peca";
		$resX = pg_exec ($con,$sql);
		$preco = pg_result ($resX,0,preco) ;
		if (strlen ($preco) == 0) $preco = 0 ;
		$preco = $preco * (1 + (pg_result ($res,$i,ipi) / 100)) ;
	}
	echo "<td align='right'>";
	echo number_format ($preco,2,",",".");
	echo "</td>";

	$qtde_giro   = pg_result ($res,$i,qtde_giro) * 2;
	if (strlen ($qtde_giro) == 0) $qtde_giro = 0 ;

	$sobra = pg_result ($res,$i,qtde_estoque) - $qtde_giro ;
	$capital = $sobra * $preco ;

	echo "<td align='right'>";
	echo $sobra;
	echo "</td>";

	echo "<td align='right'>";
	echo number_format ($capital,2,",",".");
	echo "</td>";


	echo "</tr>";

	$total_giro    += $qtde_giro ;
	$total_capital += $capital ;

}

echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";

echo "<td colspan='6'>";
echo "TOTAL";
echo "</td>";


echo "<td align='right'>";
echo $total_giro;
echo "</td>";

echo "<td align='right'>";
echo "</td>";

echo "<td align='right'>";
echo "</td>";

echo "<td align='right'>";
echo "</td>";

echo "<td align='right' nowrap>";
echo number_format ($total_capital,2,",",".");
echo "</td>";


echo "</tr>";


echo "</table>";

?>


<? #include "rodape.php"; ?>

</body>
</html>
