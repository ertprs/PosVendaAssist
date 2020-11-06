<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao = $_POST['btn_acao'];
if ($btn_acao == 'juntar') {
	$posto    = $_POST['posto'];
	$embarque = $_POST['embarque'];
	$qtde_item = $_POST['qtde_item'];

	$sql = "BEGIN TRANSACTION";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < $qtde_item ; $i++) {

		$os_item     = $_POST['os_item_'     . $i];
		$pedido_item = $_POST['pedido_item_' . $i];
		$peca        = $_POST['peca_'        . $i];
		$qtde        = $_POST['qtde_'        . $i];

		if (strlen ($peca) > 0 AND $qtde > 0) {

			if (strlen ($os_item) == 0) $os_item = "null";

			$sql = "SELECT fn_embarca_item ($login_posto, $posto, $peca, $qtde::float, $pedido_item, $os_item)";
			//echo nl2br($sql);
			$resX = pg_exec ($con,$sql);
#			echo $sql;

			/*
			$sql = "SELECT referencia, descricao FROM tbl_peca WHERE peca = $peca";
			$res = pg_exec ($con,$sql);
			$referencia = pg_result ($res,0,referencia);
			$descricao  = pg_result ($res,0,descricao);

			$produto = "";
			$consumidor_nome = "";
			$posto_nome      = "";
			$sua_os = "";

			if (strlen ($os_item) > 0) {
				$sql = "SELECT tbl_os.sua_os, tbl_os.consumidor_nome, tbl_posto.nome AS posto_nome, tbl_produto.descricao, tbl_produto.referencia 
						FROM tbl_os
						JOIN tbl_produto    USING (produto)
						JOIN tbl_posto      USING (posto)
						JOIN tbl_os_produto USING (os)
						JOIN tbl_os_item    USING (os_produto)
						WHERE tbl_os_item.os_item = $os_item";
				$res = pg_exec ($con,$sql);
				$produto = pg_result ($res,0,descricao);
				$consumidor_nome = pg_result ($res,0,consumidor_nome);
				$posto_nome      = pg_result ($res,0,posto_nome);
				$sua_os = pg_result ($res,0,sua_os);
			}

			$sql = "SELECT localizacao FROM tbl_posto_estoque_localizacao WHERE peca = $peca AND posto = $login_posto";
			$res = pg_exec ($con,$sql);
			$localizacao = pg_result ($res,0,localizacao);

			$sql = "INSERT INTO exp_etiqueta_distrib_$login_posto (
				embarque,
				referencia,
				descricao,
				produto_descricao,
				sua_os,
				consumidor_nome,
				codigo_barras,
				qtde,
				localizacao,
				posto_nome
			) VALUES (
				$embarque,
				'$referencia',
				'$descricao',
				'$produto',
				'$sua_os',
				'$consumidor_nome',
				'$os_item',
				$qtde,
				'$localizacao',
				'$posto_nome'
			)";
			$resX = pg_exec ($con,$sql);

			if (strlen ($os_item) == 0) $os_item = "null";

			$sql = "INSERT INTO tbl_embarque_item (embarque, peca, qtde, pedido_item, os_item) VALUES ($embarque, $peca, $qtde, $pedido_item, $os_item)";
			$resX = pg_exec ($con,$sql);

		*/
		}
	}

	$sql = "COMMIT TRANSACTION";
	$res = pg_exec ($con,$sql);

#	header ("Location: embarque.php");
	echo "<script language='javascript'> window.close() </script>";
	exit;
}


$posto    = $_GET['posto'];
$embarque = $_GET['embarque'];


?>

<html>
<head>
<title>Juntar Embarque deste Posto</title>
</head>

<body>

<? include 'menu.php' ?>



<center><h1>Juntar Embarque do Posto</h1></center>

<p>


<?
$posto = $_GET['posto'];

$sql = "SELECT tbl_posto.nome FROM tbl_posto WHERE posto = $posto";
$res = pg_exec ($con,$sql);
$nome = pg_result ($res,0,nome);

echo "<center><h2>$nome</h2></center>";

echo "<center><h2>Embarque atual - $embarque</h2></center>";

echo "<table border='1' align='center'>";
echo "<tr bgcolor='#6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
echo "<td>O.S.</td>";
echo "<td>Pedido</td>";
echo "<td>Referência</td>";
echo "<td>Descrição</td>";
echo "<td>Qtde</td>";
echo "<td>Localização</td>";
echo "</tr>";

$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, SUM (tbl_embarque_item.qtde) AS qtde , tbl_posto_estoque_localizacao.localizacao , tbl_os.sua_os, tbl_pedido_item.pedido
		FROM tbl_embarque_item
		JOIN tbl_peca USING (peca)
		JOIN tbl_pedido_item USING (pedido_item)
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_embarque_item.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		LEFT JOIN tbl_os_item ON tbl_embarque_item.os_item = tbl_os_item.os_item
		LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
		WHERE tbl_embarque_item.embarque = $embarque
		GROUP BY tbl_os.sua_os, tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque_localizacao.localizacao, tbl_pedido_item.pedido
		ORDER BY tbl_peca.referencia";
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<tr style='font-size:12px'>";
	echo "<td>" . pg_result ($res,$i,sua_os) . "</td>";
	echo "<td>" . pg_result ($res,$i,pedido) . "</td>";
	echo "<td>" . pg_result ($res,$i,referencia)  . "</td>";
	echo "<td align='left'>" . pg_result ($res,$i,descricao)   . "</td>";
	echo "<td align='left'>" . pg_result ($res,$i,qtde)        . "</td>";
	echo "<td align='left'>" . pg_result ($res,$i,localizacao) . "</td>";
	echo "</tr>";
}

echo "</table>";


echo "<p><center><h2>Peças a serem juntadas</h2></center>";


echo "<table border='1' align='center'>";
echo "<tr bgcolor='#6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
echo "<td>O.S.</td>";
echo "<td>Pedido</td>";
echo "<td>Referência</td>";
echo "<td>Descrição</td>";
echo "<td>Qtde</td>";
echo "<td>Estoque</td>";
echo "<td>Localização</td>";
echo "</tr>";

$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, SUM (tbl_embarque_item.qtde) AS qtde , tbl_posto_estoque_localizacao.localizacao, tbl_os.sua_os, tbl_pedido_item.pedido
		FROM tbl_embarque_item
		JOIN tbl_peca USING (peca)
		JOIN tbl_pedido_item USING (pedido_item)
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_embarque_item.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
		LEFT JOIN tbl_os_item ON tbl_embarque_item.os_item = tbl_os_item.os_item
		LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
		WHERE tbl_embarque_item.embarque = $embarque
		GROUP BY tbl_os.sua_os, tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque_localizacao.localizacao
		ORDER BY tbl_peca.referencia";

$sql = "SELECT  tbl_peca.peca                           ,
				tbl_peca.referencia                     ,
				tbl_peca.descricao                      ,
				ped.sua_os                              ,
				ped.os_item                             ,
				ped.pedido                              ,
				ped.pedido_item                         ,
				ped.qtde                                ,
				ped.qtde_os_item                        ,
				tbl_posto_estoque.qtde AS estoque       ,
				tbl_posto_estoque_localizacao.localizacao
		FROM   (SELECT	tbl_pedido_item.peca , 
						tbl_pedido_item.pedido ,
						tbl_os.sua_os ,
						tbl_pedido_item.pedido_item ,
						tbl_os_item.os_item ,
						tbl_os_item.qtde AS qtde_os_item ,
					SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde
				FROM tbl_pedido_item 
				JOIN tbl_pedido USING (pedido) 
				LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
				LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
				WHERE tbl_pedido.posto = $posto AND tbl_pedido.distribuidor = $login_posto 
				AND (tbl_pedido.status_pedido_posto IS NULL OR tbl_pedido.status_pedido_posto NOT IN (13,4,3) )
				AND  tbl_pedido.garantia_antecipada_distribuidor IS NOT TRUE
				GROUP BY tbl_os.sua_os, tbl_os_item.os_item, tbl_os_item.qtde, tbl_pedido_item.pedido_item, tbl_pedido.posto , tbl_pedido_item.peca, tbl_pedido_item.pedido
				) ped
		JOIN    tbl_peca USING (peca)
		LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.posto = $login_posto AND tbl_posto_estoque.peca = ped.peca
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = $login_posto AND tbl_posto_estoque_localizacao.peca = ped.peca
		GROUP BY tbl_peca.peca                           ,
				tbl_peca.referencia                      ,
				tbl_peca.descricao                       ,
				ped.sua_os                               ,
				ped.os_item                              ,
				ped.pedido_item                          ,
				ped.pedido                               ,
				ped.qtde                                 ,
				ped.qtde_os_item                         ,
				tbl_posto_estoque.qtde                   ,
				tbl_posto_estoque_localizacao.localizacao
		HAVING ped.qtde > 0
		ORDER BY ped.sua_os, tbl_peca.referencia";



$sql= "
		SELECT tbl_pedido.pedido,
			tbl_pedido.posto
		INTO TEMP TABLE tmp_pedido
		FROM tbl_pedido
		WHERE   tbl_pedido.posto = $posto
			AND tbl_pedido.distribuidor = $login_posto 
			AND (tbl_pedido.status_pedido_posto IS NULL OR tbl_pedido.status_pedido_posto NOT IN (13,4,3) )
			AND  tbl_pedido.garantia_antecipada_distribuidor IS NOT TRUE;

		CREATE INDEX tmp_pedido_pedido_index on tmp_pedido(pedido);
		CREATE INDEX tmp_pedido_posto_index on tmp_pedido(posto);


		SELECT	tbl_pedido_item.peca , 
			tbl_pedido_item.pedido ,
			tbl_os.sua_os ,
			tbl_pedido_item.pedido_item ,
			tbl_os_item.os_item ,
			tbl_os_item.qtde AS qtde_os_item ,
			SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde
		INTO TEMP TABLE tmp_tab1
		FROM tbl_pedido_item 
		JOIN tmp_pedido USING (pedido) 
		LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
		LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
		GROUP BY tbl_os.sua_os, tbl_os_item.os_item, tbl_os_item.qtde, tbl_pedido_item.pedido_item, tmp_pedido.posto , tbl_pedido_item.peca, tbl_pedido_item.pedido;

		CREATE INDEX tmp_tab1_peca_index on tmp_tab1(peca);

		SELECT  tbl_peca.peca                           ,
				tbl_peca.referencia                     ,
				tbl_peca.descricao                      ,
				ped.sua_os                              ,
				ped.os_item                             ,
				ped.pedido                              ,
				ped.pedido_item                         ,
				ped.qtde                                ,
				ped.qtde_os_item                        ,
				tbl_posto_estoque.qtde AS estoque       ,
				tbl_posto_estoque_localizacao.localizacao
		FROM   tmp_tab1 as ped
		JOIN    tbl_peca USING (peca)
		LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.posto = $login_posto  AND tbl_posto_estoque.peca = ped.peca
		LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.posto = $login_posto  AND tbl_posto_estoque_localizacao.peca = ped.peca
		GROUP BY tbl_peca.peca                           ,
				tbl_peca.referencia                      ,
				tbl_peca.descricao                       ,
				ped.sua_os                               ,
				ped.os_item                              ,
				ped.pedido_item                          ,
				ped.pedido                               ,
				ped.qtde                                 ,
				ped.qtde_os_item                         ,
				tbl_posto_estoque.qtde                   ,
				tbl_posto_estoque_localizacao.localizacao
		HAVING ped.qtde > 0
		ORDER BY ped.sua_os, tbl_peca.referencia;
";

$res = pg_exec ($con,$sql);

echo "<form name='frm_embarque' action='$PHP_SELF' method='post'>";
echo "<input type='hidden' name='embarque' value='$embarque'>";
echo "<input type='hidden' name='posto'    value='$posto'>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$qtde         = pg_result ($res,$i,qtde) ;
	$qtde_os_item = pg_result ($res,$i,qtde_os_item) ;
	$estoque      = pg_result ($res,$i,estoque) ;
	$os_item      = pg_result ($res,$i,os_item) ;
	$pedido_item  = pg_result ($res,$i,pedido_item) ;

	if (strlen ($os_item) > 0) $qtde = $qtde_os_item ;
	if ($estoque < $qtde) $qtde = $estoque ;

	$cor = "";

	if ($qtde > 0) {
		echo "<input type='hidden' name='peca_$i'        value='" . pg_result ($res,$i,peca) . "'>";
		echo "<input type='hidden' name='qtde_$i'        value='" . $qtde . "'>";
		echo "<input type='hidden' name='os_item_$i'     value='" . $os_item . "'>";
		echo "<input type='hidden' name='pedido_item_$i' value='" . $pedido_item . "'>";
	}else{
		$cor = "#FF99CC";
	}

	echo "<tr style='font-size:12px' bgcolor='$cor'>";
	echo "<td>" . pg_result ($res,$i,sua_os)  . "</td>";
	echo "<td>" . pg_result ($res,$i,pedido)  . "</td>";
	echo "<td>" . pg_result ($res,$i,referencia)  . "</td>";
	echo "<td align='left'>" . pg_result ($res,$i,descricao)   . "</td>";
	echo "<td align='left'>" . $qtde . "</td>";
	echo "<td align='left'>" . pg_result ($res,$i,estoque)     . "</td>";
	echo "<td align='left'>" . pg_result ($res,$i,localizacao) . "</td>";
	echo "</tr>";
}

echo "</table>";

echo "<input type='hidden' name='btn_acao'    value=''>";
echo "<input type='hidden' name='qtde_item'   value='$i'>";

echo "<p><center><h4><a href=\"javascript: document.frm_embarque.btn_acao.value='juntar' ; document.frm_embarque.submit() ; \">Confirmar junção</a></h4></center>";

echo "</form>";




#echo "<script language='javascript'>
#		document.frm_embarque.btn_acao.value='juntar' ; 
#		document.frm_embarque.submit() ; 
#		</script>";


?>



<p>

<? #include "rodape.php"; ?>

</body>
</html>
