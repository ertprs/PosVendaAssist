<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";


$layout_menu = "gerencia";
$title = "RELATÓRIO DE PENDÊNCIA DE PEÇAS";

include "cabecalho.php";
?>

<p>

<center>

<?
$posto = $_GET['posto'];

$sql = "SELECT tbl_posto.estado, tbl_posto.cidade, tbl_posto.estado, tbl_posto_fabrica.codigo_posto, tbl_posto.nome FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto.posto = $posto";
$res = pg_exec ($con,$sql);

echo "<b>" . pg_result ($res,0,codigo_posto) . " - " . pg_result ($res,0,nome) . "</b>";
echo "<br>";
echo pg_result ($res,0,cidade) . " - " . pg_result ($res,0,cidade) . "</b>";

?>


</center>

<table width='600' align='center' border='0' cellspacing='2'>
<tr bgcolor='#330099' align='center' style='color:#ffffff ; font-weight:bold ; font-size:12px'>
<td>Pedido</td>
<td>Data</td>
<td>Referência</td>
<td>Descrição</td>
<td>Pedida</td>
<td>Cancelada</td>
<td>Atendida</td>
<td>Pendência</td>
</tr>

<?
if ($login_fabrica == 2 ) $pedido_faturado = 1;
if ($login_fabrica == 2 ) $pedido_garantia = 70;
if ($login_fabrica == 3 ) $pedido_faturado = 2;
if ($login_fabrica == 3 ) $pedido_garantia = 3;
/*TAKASHI HD 1895 - nao sei pq definiram para fabrica 2 e 3 e liberaram para todas as fabricas*/
if ($login_fabrica == 24 ) $pedido_garantia = 104;
if ($login_fabrica == 24 ) $pedido_faturado = 103;
if ($login_fabrica == 11 ) $pedido_garantia = 84;
if ($login_fabrica == 11 ) $pedido_faturado = 85;
/*TAKASHI HD 1895 - nao sei pq definiram para fabrica 2 e 3 e liberaram para todas as fabricas*/

$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.posto, tbl_posto.nome, tbl_posto.estado, pend.qtde_menos_15, pend.qtde_mais_15
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN (SELECT pend_0.posto, SUM (pend_0.qtde_menos_15) AS qtde_menos_15, SUM (pend_0.qtde_mais_15) AS qtde_mais_15
				FROM (SELECT (CASE WHEN tbl_pedido.distribuidor IS NULL THEN tbl_pedido.posto ELSE tbl_pedido.distribuidor END) AS posto,
						SUM  (CASE WHEN tbl_pedido.data >  (CURRENT_DATE - INTERVAL '15 days') THEN (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) ELSE 0 END ) AS qtde_menos_15 ,
						SUM  (CASE WHEN tbl_pedido.data <= (CURRENT_DATE - INTERVAL '15 days') THEN (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) ELSE 0 END ) AS qtde_mais_15
						FROM tbl_pedido
						JOIN tbl_pedido_item USING (pedido)
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.status_pedido IN (2,5,8)
						AND   tbl_pedido.data > '2004-01-01'
						AND   (tbl_pedido.tipo_pedido = $pedido_garantia OR (tbl_pedido.distribuidor IS NULL AND tbl_pedido.tipo_pedido = $pedido_faturado))
						GROUP BY tbl_pedido.posto, tbl_pedido.distribuidor
					) pend_0
				GROUP BY pend_0.posto
			) pend ON tbl_posto.posto = pend.posto
		ORDER BY (pend.qtde_mais_15 + pend.qtde_menos_15) DESC";

$sql = "SELECT  tbl_pedido.pedido,
				to_char (tbl_pedido.data,'DD/MM/YYYY') AS data,
				tbl_peca.referencia,
				tbl_peca.descricao, ped.qtde,
				ped.qtde_cancelada,
				ped.qtde_faturada
		FROM tbl_pedido
		JOIN (
			SELECT  tbl_pedido_item.pedido,
					tbl_pedido_item.peca,
					SUM (tbl_pedido_item.qtde) AS qtde,
					SUM (tbl_pedido_item.qtde_cancelada) AS qtde_cancelada,
					SUM (tbl_pedido_item.qtde_faturada) AS qtde_faturada
				FROM tbl_pedido
				JOIN tbl_pedido_item USING (pedido)
				WHERE tbl_pedido.posto = $posto
				AND   tbl_pedido.fabrica = $login_fabrica ";
				if ($login_fabrica == 2) $sql .= "AND tbl_pedido.exportado ISNULL ";
				$sql .= "AND   tbl_pedido.status_pedido IN (2,5,8)
				AND   tbl_pedido.data > '2004-01-01'
				AND   (tbl_pedido.tipo_pedido = $pedido_garantia OR (tbl_pedido.distribuidor IS NULL AND tbl_pedido.tipo_pedido = $pedido_faturado))
				GROUP BY tbl_pedido_item.pedido, tbl_pedido_item.peca
			) ped ON tbl_pedido.pedido = ped.pedido
		JOIN tbl_peca ON ped.peca = tbl_peca.peca
		GROUP BY    tbl_pedido.pedido,
					tbl_pedido.data  ,
					tbl_peca.referencia,
					tbl_peca.descricao,
					ped.qtde,
					ped.qtde_cancelada,
					ped.qtde_faturada
		HAVING qtde - qtde_cancelada - qtde_faturada > 0
		ORDER BY tbl_pedido.data";

if($ip=="200.206.159.250")echo $sql;
$res = pg_exec ($con,$sql);

$total_qtde = 0;
$total_qtde_cancelada = 0;
$total_qtde_faturada = 0;

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$cor = '#ffffff';
	if ($i % 2 == 0) $cor = '#FFFFCC';

	echo "<tr style='font-size:10px ' align='left' bgcolor='$cor'>";

	echo "<td>";
	echo pg_result ($res,$i,pedido);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,data);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,referencia);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,descricao);
	echo "</td>";

	echo "<td align='right'>";
	echo pg_result ($res,$i,qtde);
	echo "</td>";

	echo "<td align='right'>";
	echo pg_result ($res,$i,qtde_cancelada);
	echo "</td>";

	echo "<td align='right'>";
	echo pg_result ($res,$i,qtde_faturada);
	echo "</td>";

	echo "<td align='right'>";
	echo pg_result ($res,$i,qtde) - pg_result ($res,$i,qtde_cancelada) - pg_result ($res,$i,qtde_faturada);
	echo "</td>";

	echo "</tr>";

	$total_qtde += pg_result ($res,$i,qtde);
	$total_qtde_cancelada += pg_result ($res,$i,qtde_cancelada);
	$total_qtde_faturada += pg_result ($res,$i,qtde_faturada);
}

?>

<tr bgcolor='#330099' align='center' style='color:#ffffff ; font-weight:bold'>
<td colspan='4'>Total da Pendência</td>
<td><? echo $total_qtde ?></td>
<td><? echo $total_qtde_cancelada ?></td>
<td><? echo $total_qtde_faturada ?></td>
<td><? echo ($total_qtde - $total_qtde_cancelada - $total_qtde_faturada) ?></td>
</tr>

</table>



<? #include "rodape.php"; ?>

</body>
</html>
