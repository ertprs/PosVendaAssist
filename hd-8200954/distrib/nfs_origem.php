<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
#include 'autentica_usuario.php';

$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_faturamento_item.peca, tbl_faturamento_item.qtde, tbl_faturamento_item.preco FROM tbl_faturamento_item JOIN tbl_peca USING (peca) WHERE faturamento = 693528 ORDER BY peca ";
$res = pg_exec ($con,$sql);


echo "<h1>Origem da devolução de nossa NF 009214</h1>";

echo "<table border='1'>";
echo "<tr>";
echo "<td><b>Peça</b></td>";
echo "<td><b>Descrição</b></td>";
echo "<td><b>Qtde</b></td>";
echo "<td><b>Preço</b></td>";
echo "<td><b>De-Para</b></td>";
echo "<td><b>Origem</b></td>";
echo "</tr>";
for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	echo "<tr>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,referencia);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,descricao);
	echo "</td>";

	echo "<td nowrap align='right'>";
	echo pg_result ($res,$i,qtde);
	echo "</td>";

	echo "<td nowrap align='right'>";
	echo number_format (pg_result ($res,$i,preco),2,',','.');
	echo "</td>";

	$peca = pg_result ($res,$i,peca);
	$qtde = pg_result ($res,$i,qtde);

	$pecas = "$peca";
	while (true) {
		$sql = "SELECT peca_de FROM tbl_depara WHERE peca_para IN ($pecas) AND peca_de NOT IN ($pecas)";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) == 0) break;
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
			$pecas = $pecas . "," . pg_result ($resX,$x,peca_de);
		}
	}

	echo "<td nowrap>";
	$depara = $pecas ;
	$procura = $peca . ",";
	$depara = str_replace ($procura,"",$depara);
	$procura = $peca;
	$depara = str_replace ($procura,"",$depara);
	if (strlen ($depara) > 0) {
		$sql = "SELECT referencia FROM tbl_peca WHERE peca IN ($depara)";
		$resX = pg_exec ($con,$sql);
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
			echo pg_result ($resX,$x,referencia);
			echo ",";
		}
	}
	echo "&nbsp;";
	echo "</td>";
	
	echo "<td nowrap>";
	$qtde_origem = 0 ;
	$x = 0 ;

	$faltou = false;
	while ($qtde_origem < $qtde) {
		$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal, tbl_faturamento_item.preco, tbl_faturamento_item.qtde, to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING (faturamento)
				WHERE tbl_faturamento.posto = 4311
				AND   tbl_faturamento.fabrica = 3
				AND   tbl_faturamento_item.peca IN ($pecas)
				AND   tbl_faturamento.emissao < '2006-12-01'
				AND   tbl_faturamento.cfop LIKE '61%'
				ORDER BY tbl_faturamento_item.preco DESC
				LIMIT 1
				OFFSET $x";
		$resX = pg_exec ($con,$sql);
		if (pg_numrows ($resX) == 0) {
			if ($qtde > $qtde_origem) $faltou = true ;
			break;
		}
		$qtde_origem += pg_result ($resX,0,qtde);

		echo pg_result ($resX,0,nota_fiscal);
		echo " , ";
		echo pg_result ($resX,0,qtde);
		echo " ; ";
		$x++;
	}

	$x = 0 ;
	if ($faltou) {
		$faltou = false;
		while ($qtde_origem < $qtde) {
			$sql = "SELECT tbl_faturamento.faturamento, tbl_faturamento.nota_fiscal, tbl_faturamento_item.preco, tbl_faturamento_item.qtde, to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
					FROM tbl_faturamento
					JOIN tbl_faturamento_item USING (faturamento)
					WHERE tbl_faturamento.posto = 4311
					AND   tbl_faturamento.fabrica = 3
					AND   tbl_faturamento_item.peca IN ($pecas)
					AND   tbl_faturamento.emissao < '2006-12-01'
					AND   tbl_faturamento.cfop LIKE '69%'
					ORDER BY tbl_faturamento_item.preco DESC
					LIMIT 1
					OFFSET $x";
			$resX = pg_exec ($con,$sql);
			if (pg_numrows ($resX) == 0) {
				if ($qtde > $qtde_origem) $faltou = true ;
				break;
			}
			$qtde_origem += pg_result ($resX,0,qtde);

			echo "#";
			echo pg_result ($resX,0,nota_fiscal);
			echo " , ";
			echo pg_result ($resX,0,qtde);
			echo " ; ";
			$x++;
		}
	}


	#---------------- Finalizaçao ---------------
	if ($faltou) echo "***";

	echo "</td>";
	echo "</tr>";
}

echo "</table>";

$peca = pg_result ($res,0,peca);


exit ;





#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Embarque de Pedidos</title>
<style>
a:visited{
	color:#663366;
}
</style>
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Embarque de pedidos</h1></center>

<p>


<?
#$sql = "SELECT fn_embarque_deleta_vazio ($login_posto)";
#$res = pg_exec ($con,$sql);


$sql = "SELECT tbl_posto.posto, tbl_posto.nome, TO_CHAR (emb.ultimo_embarque,'DD/MM/YYYY') AS ultimo_embarque , TO_CHAR (antigo.pedido_antigo,'DD/MM/YYYY') AS pedido_antigo, ped.qtde_pedido, estq.qtde_atender, tbl_posto.cidade, tbl_posto.estado, tbl_posto.fone, tbl_posto.email, andamento.embarque
		FROM tbl_posto
		JOIN    (SELECT tbl_pedido.posto, SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pedido
				 FROM tbl_pedido_item
				 JOIN tbl_pedido USING (pedido)
				 WHERE tbl_pedido.posto   <> tbl_pedido.distribuidor
				 AND   tbl_pedido.distribuidor = $login_posto
				 AND   (tbl_pedido.status_pedido_posto NOT IN (13,4,3) OR tbl_pedido.status_pedido_posto IS NULL)
				 AND   tbl_pedido.tipo_pedido IN (2,3)
				 AND   tbl_pedido.garantia_antecipada_distribuidor IS NOT TRUE
				 GROUP BY tbl_pedido.posto
				) ped ON tbl_posto.posto = ped.posto
		LEFT JOIN (SELECT estq.posto, SUM (CASE WHEN estq.estoque > estq.qtde THEN estq.qtde ELSE estq.estoque END) AS qtde_atender
						FROM (SELECT tbl_pedido.posto, tbl_pedido_item.peca, 
								SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde ,
								CASE WHEN tbl_posto_estoque.qtde > 0 THEN tbl_posto_estoque.qtde ELSE 0 END AS estoque
								FROM tbl_pedido 
								JOIN tbl_pedido_item USING (pedido)
								LEFT JOIN tbl_posto_estoque ON tbl_pedido_item.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
								WHERE tbl_pedido.posto   <> tbl_pedido.distribuidor
								AND   tbl_pedido.distribuidor = $login_posto
								AND   (tbl_pedido.status_pedido_posto NOT IN (13,4,3) OR tbl_pedido.status_pedido_posto IS NULL)
								AND   tbl_pedido.tipo_pedido IN (2,3)
								AND   tbl_pedido.garantia_antecipada_distribuidor IS NOT TRUE
								GROUP BY tbl_pedido.posto, tbl_pedido_item.peca, tbl_posto_estoque.qtde
								) estq
					GROUP BY estq.posto
				) estq ON estq.posto = tbl_posto.posto 
		LEFT JOIN (SELECT tbl_embarque.posto , MAX (tbl_embarque.data) AS ultimo_embarque
					FROM tbl_embarque
					GROUP BY tbl_embarque.posto
				) emb ON tbl_posto.posto = emb.posto
		LEFT JOIN (SELECT tbl_pedido.posto , MIN (tbl_pedido.data) AS pedido_antigo
					FROM tbl_pedido
					WHERE (tbl_pedido.status_pedido_posto NOT IN (13,4,3) OR tbl_pedido.status_pedido_posto IS NULL)
					AND   tbl_pedido.distribuidor = $login_posto
					GROUP BY tbl_pedido.posto
				) antigo ON tbl_posto.posto = antigo.posto
		LEFT JOIN (SELECT posto, embarque FROM tbl_embarque WHERE faturar IS NULL) andamento ON tbl_posto.posto = andamento.posto
		ORDER BY current_date - ultimo_embarque DESC , antigo.pedido_antigo 
		";



#echo $sql;
#exit;

$res = pg_exec ($con,$sql);

echo "<table>";
echo "<tr bgcolor='##6666CC' style='color:#ffffff ; font-weight:bold' align='center'>";
echo "<td>Posto</td>";
echo "<td>Nome</td>";
echo "<td>Último<br>Embarque</td>";
echo "<td nowrap>Pedido<br>mais antigo</td>";
echo "<td nowrap>Pendente</td>";
echo "<td nowrap>Atender</td>";
echo "<td>Juncao</td>";
echo "<td>Cidade</td>";
echo "<td>Estado</td>";
echo "<td>Fone</td>";
echo "<td>eMail</td>";
echo "</tr>";


for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$embarque    = trim(pg_result($res,$i,embarque)) ;
	$posto       = trim(pg_result($res,$i,posto)) ;
	$nome        = trim(pg_result($res,$i,nome)) ;
	$cidade      = trim(pg_result($res,$i,cidade));
	$estado      = trim(pg_result($res,$i,estado));
	$fone        = trim(pg_result($res,$i,fone));
	$email       = trim(pg_result($res,$i,email));
	$ultimo_embarque = trim(pg_result($res,$i,ultimo_embarque));
	$pedido_antigo   = trim(pg_result($res,$i,pedido_antigo));
	$qtde_atender    = trim(pg_result($res,$i,qtde_atender));
	$qtde_pedido     = trim(pg_result($res,$i,qtde_pedido));

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#DDDDEE";

	echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
	if (strlen ($embarque) == 0) {
		echo "<td align='left' nowrap>&nbsp;<a href='embarque_posto.php?posto=$posto'><font color='#6633CC'>$posto</font></a></td>\n";
	}else{
		echo "<td align='left' nowrap>&nbsp;$posto</td>\n";
	}
	echo "<td align='left' nowrap>$nome</td>\n";
	echo "<td align='left' nowrap>$ultimo_embarque</td>\n";
	echo "<td align='left' nowrap>$pedido_antigo</td>\n";

	if ($qtde_pedido == $qtde_atender AND $qtde_pedido > 0) $cor = "#ddffdd";

	echo "<td align='left' bgcolor='$cor' nowrap>$qtde_pedido</td>\n";

	if (strlen ($embarque) > 0 AND $qtde_atender > 0) {
		$cor = "#0099FF";
		echo "<td align='left' bgcolor='$cor' alt='Clique aqui para juntar estas peças ao embarque' nowrap onmouseover=\"this.style.backgroundColor='#FF9900';this.style.cursor='pointer'\" onmouseout=\"this.style.backgroundColor='#0099FF';this.style.cursor='normal'\" onclick=\"javascript: document.location='embarque_juntar.php?embarque=$embarque&posto=$posto' \">$qtde_atender</td>\n";
	}else{
		echo "<td align='left' nowrap>$qtde_atender</td>\n";
	}
	echo "<td align='left' nowrap>";
	if ($embarque > 0) echo "<a href='embarque_juntar.php?embarque=$embarque&posto=$posto' target='_new'>juntar</a>";
	echo "</td>\n";
	echo "<td align='left' nowrap>$cidade</td>\n";
	echo "<td align='left' nowrap>$estado</td>\n";
	echo "<td align='left' nowrap>$fone</td>\n";
	echo "<td align='left' nowrap>$email</td>\n";
	echo "</tr>\n";
}

echo "</table>\n";

?>

<p>

<? #include "rodape.php"; ?>

</body>
</html>
