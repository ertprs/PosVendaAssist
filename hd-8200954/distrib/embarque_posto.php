<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$cancelar = $_GET['cancelar'];
if ($cancelar == 'S') {
	$posto    = $_GET['posto'];
	$embarque = $_GET['embarque'];

	$sql = "SELECT fn_cancela_embarque ($login_posto, $posto, $embarque)";
	$res = pg_exec ($con,$sql);

	header ("Location: embarque.php");
	exit;
}


$posto = $_GET['posto'];
if (strlen ($posto) > 0) {
	$sql = "SELECT fn_cria_embarque ($login_posto,$posto)";
	$res = pg_exec ($con,$sql);
	$embarque = pg_result ($res,0,0);
}

?>

<html>
<head>
<title>Embarque por Posto</title>
</head>

<body>

<? include 'menu.php' ?>



<center><h1>Embarque por Posto</h1></center>

<p>


<?
$posto = $_GET['posto'];

$res = pg_exec ($con,"BEGIN TRANSACTION");

$sql = "SELECT tbl_posto.nome FROM tbl_posto WHERE posto = $posto";
$res = pg_exec ($con,$sql);
$nome = pg_result ($res,0,nome);

echo "<center><h2>$nome</h2></center>";

$sql = "SELECT tbl_peca.peca                            ,
				tbl_peca.referencia                     ,
				tbl_peca.descricao                      ,
				ped.qtde                                ,
				ped.qtde_cancelada                      ,
				ped.qtde_faturada_distribuidor          ,
				embarque.qtde          AS qtde_embarque ,
				tbl_posto_estoque.qtde AS estoque
		FROM   (SELECT	tbl_pedido_item.peca , 
					SUM (tbl_pedido_item.qtde) AS qtde ,
					SUM (tbl_pedido_item.qtde_cancelada) AS qtde_cancelada ,
					SUM (tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_faturada_distribuidor
				FROM tbl_pedido_item 
				JOIN tbl_pedido USING (pedido) 
				WHERE tbl_pedido.posto = $posto AND tbl_pedido.distribuidor = $login_posto 
				AND (tbl_pedido.status_pedido_posto IS NULL OR tbl_pedido.status_pedido_posto <> 13)
				AND   tbl_pedido.garantia_antecipada_distribuidor IS NOT TRUE
				GROUP BY tbl_pedido.posto , tbl_pedido_item.peca
				) ped
		JOIN    tbl_peca USING (peca)
		LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.posto = $login_posto AND tbl_posto_estoque.peca = ped.peca
		LEFT JOIN (SELECT peca, SUM (qtde) AS qtde FROM tbl_embarque_item WHERE tbl_embarque_item.embarque = $embarque GROUP BY peca ) embarque ON embarque.peca = ped.peca
		ORDER BY tbl_peca.referencia";



$res = pg_exec ($con,$sql);

#echo $sql;

flush();

echo "<table width='600' align='center'>";
echo "<tr bgcolor='##6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
echo "<td>Peça</td>";
echo "<td>Descrição</td>";
echo "<td>Qtde Pedida</td>";
echo "<td>Qtde Cancelada</td>";
echo "<td>Qtde Atendida</td>";
echo "<td>Faturar</td>";
echo "<td>Estoque</td>";
echo "<td>Qtde Pendente</td>";
echo "</tr>";


for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$peca                       = trim(pg_result($res,$i,peca)) ;
	$referencia                 = trim(pg_result($res,$i,referencia)) ;
	$descricao                  = trim(pg_result($res,$i,descricao)) ;
	$qtde                       = trim(pg_result($res,$i,qtde)) ;
	$qtde_cancelada             = trim(pg_result($res,$i,qtde_cancelada)) ;
	$qtde_faturada_distribuidor = trim(pg_result($res,$i,qtde_faturada_distribuidor)) ;
	$estoque                    = trim(pg_result($res,$i,estoque)) ;
	$qtde_embarque              = trim(pg_result($res,$i,qtde_embarque)) ;

	if (strlen ($estoque) == 0) $estoque = 0;
	if (strlen ($qtde_faturada_distribuidor) == 0) $qtde_faturada_distribuidor = 0;
	if (strlen ($qtde_cancelada) == 0) $qtde_cancelada = 0;


/*
	$qtde_embarque = 0 ;
	$qtde_faturar  = $qtde - $qtde_cancelada - $qtde_faturada_distribuidor ;

	if ($qtde_faturar > 0) {
		$saldo = $estoque - $qtde_faturar ;
		if ($saldo > 0) {
			$qtde_embarque = $qtde_faturar;
		}else{
			$qtde_embarque = $saldo ;
		}
	}

*/

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#DDDDEE";

	echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
	echo "<td align='left' nowrap>$referencia</td>\n";
	echo "<td align='left' nowrap>$descricao</td>\n";
	echo "<td align='right' nowrap>$qtde</td>\n";
	echo "<td align='right' nowrap>$qtde_cancelada</td>\n";
	$qtde_faturada_anterior = $qtde_faturada_distribuidor - $qtde_embarque ;
	echo "<td align='right' nowrap>$qtde_faturada_anterior</td>\n";
	$qtde_pendente = $qtde - $qtde_cancelada - $qtde_faturada_distribuidor;

	$cor_fat='';
	if ($qtde_pendente > 0) {
		$cor_fat = '#FFCCCC';
		if ($estoque > 0) $cor_fat = '#FFFF99';
		if ($estoque > $qtde_pendente) $cor_fat = '#66FF66';
	}

	echo "<td align='right' nowrap bgcolor='$cor_fat'><b>$qtde_embarque</b></td>\n";
	echo "<td align='right' nowrap bgcolor='$cor_fat'>$estoque</td>\n";
	echo "<td align='right' nowrap bgcolor='$cor_fat'>$qtde_pendente</td>\n";

	echo "</tr>\n";

/*
	$sql = "SELECT tbl_pedido.tipo_pedido, tbl_pedido_item.peca, tbl_pedido_item.qtde, tbl_pedido_item.pedido_item, null AS os_item
				FROM   tbl_pedido_item
				JOIN   tbl_pedido USING (pedido)
				WHERE  tbl_pedido.posto = $posto
				AND    tbl_pedido.tipo_pedido = 2
				AND    tbl_pedido_item.peca = $peca
				AND   (tbl_pedido.status_pedido_posto IS NULL OR tbl_pedido.status_pedido_posto <> 13)
			UNION
			SELECT tbl_pedido.tipo_pedido, tbl_os_item.peca, tbl_os_item.qtde, null AS pedido_item, tbl_os_item.os_item
				FROM   tbl_os_item
				JOIN   tbl_pedido USING (pedido)
				WHERE  tbl_pedido.posto = $posto
				AND    tbl_pedido.tipo_pedido = 3
				AND    tbl_os_item.peca = $peca
				AND   (tbl_pedido.status_pedido_posto IS NULL OR tbl_pedido.status_pedido_posto <> 13)";
	$resX = pg_exec ($con,$sql);
	$qtdeX = 0 ;
	for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
		$tipo_pedido = pg_result ($resX,$x,tipo_pedido);
		$qtde        = pg_result ($resX,$x,qtde);
		$pedido_item = pg_result ($resX,$x,pedido_item);
		$os_item     = pg_result ($resX,$x,os_item);

		if ($qtdeX + $qtde > $qtde_embarque) {
			if ($qtdeX < $qtde_embarque AND $tipo_pedido == 2) {
				$qtde = $qtde_embarque - $qtde_x;
			}else{
				$qtde = 0;
			}
		}


		if ($qtde > 0) {
			if (strlen ($os_item) == 0 ) $os_item = "null";
			$sql = "SELECT fn_embarca_item ($login_posto, $posto, $peca, $qtde, $pedido_item, $os_item)";
#			$resZ = pg_exec ($con,$sql);
		}
		$qtdeX += $qtde;
	}

*/

	flush();
}

echo "</table>\n";

echo "<p align='center'>";
echo "<a href='$PHP_SELF?posto=$posto&embarque=$embarque&cancelar=S'>Clique aqui CANCELAR este embarque</a>";
echo "<p>";


$res = pg_exec ($con,"COMMIT TRANSACTION");

?>



<p>

<? #include "rodape.php"; ?>

</body>
</html>
