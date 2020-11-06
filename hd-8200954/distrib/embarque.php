<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$NOVO_SQL = "select tbl_pedido_item.peca, sum (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) from tbl_pedido_item join tbl_pedido using (pedido) where (tbl_pedido.status_pedido_posto not in (3,4,6,13) or tbl_pedido.status_pedido_posto is null) and tbl_pedido.distribuidor = 4311 group by tbl_pedido_item.peca ;
";

/*

Roteiro para Embarque Automático
================================

Pegar todos os pedidos que ainda estejam em aberto;

Separar apenas os que têm estoque para atender, Total ou Parcial;

Pegar todas as peças em Garantia e embarcar imediatamente;

Atender por ordem de data os pedidos, posto a posto;

Gerente informa a quantidade de peças que quer manipular por dia;

Liberamos o embarque apenas destas peças - Tela de Conferência de Embarque;
nesta tela ele pode retirar peças que não quer enviar;
gera etiquetas


*/

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
				 WHERE tbl_pedido.distribuidor = $login_posto
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
								WHERE tbl_pedido.distribuidor = $login_posto
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
// O sql foi alterado para melhor desempenho, retirado os SQL's 


$sql_2007_dezembro_tulio = "
	SELECT tbl_pedido.pedido, tbl_pedido.posto, tbl_pedido.garantia_antecipada_distribuidor
	INTO TEMP TABLE tmp_pedido
	FROM tbl_pedido 
	WHERE tbl_pedido.distribuidor = 4311
		AND   tbl_pedido.tipo_pedido IN (2,3)
		AND   tbl_pedido.status_pedido_posto IN (SELECT status_pedido FROM tbl_status_pedido WHERE status_pedido NOT IN (13,4,3) ) ;

	DELETE FROM tmp_pedido WHERE garantia_antecipada_distribuidor IS TRUE ;

	SELECT tmp_pedido.pedido,
	    tbl_pedido_item.pedido_item,
		tbl_pedido_item.peca,
		(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pedido
	into temp table tmp_pedido_item
	FROM tmp_pedido
	JOIN tbl_pedido_item USING (pedido);
			
	
SELECT tmp_pedido.pedido,
tbl_pedido_item.pedido_item,
tbl_pedido_item.peca,
(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pedido
into temp table tmp_pedido_item
FROM tmp_pedido
JOIN tbl_pedido_item USING (pedido);


";

$sql="
	SELECT tbl_pedido.pedido, tbl_pedido.posto
	INTO TEMP TABLE tmp_pedido
	FROM tbl_pedido 
	WHERE	tbl_pedido.distribuidor = 4311
		AND   tbl_pedido.tipo_pedido IN (2,3)
		AND   tbl_pedido.garantia_antecipada_distribuidor IS NOT TRUE
		AND   (tbl_pedido.status_pedido_posto NOT IN (13,4,3) OR tbl_pedido.status_pedido_posto IS NULL);

	CREATE INDEX tmp_pedido_pedido_index on tmp_pedido(pedido);
	CREATE INDEX tmp_pedido_posto_index on tmp_pedido(posto);

	SELECT tmp_pedido.posto, 
		SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde_pedido
	into temp table tmp_tab1
	FROM tbl_pedido_item
	JOIN tmp_pedido ON  tmp_pedido.pedido = tbl_pedido_item.pedido 
	GROUP BY tmp_pedido.posto;
			
	SELECT tbl_pedido.posto, tbl_pedido_item.peca, 
		SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde ,
		CASE WHEN tbl_posto_estoque.qtde > 0 THEN tbl_posto_estoque.qtde ELSE 0 END AS estoque
	into temp table tmp_interno1
	FROM tbl_pedido 
	JOIN tbl_pedido_item USING (pedido)
	LEFT JOIN tbl_posto_estoque ON tbl_pedido_item.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
	WHERE tbl_pedido.distribuidor = $login_posto
		AND   (tbl_pedido.status_pedido_posto NOT IN (13,4,3) OR tbl_pedido.status_pedido_posto IS NULL)
		AND   tbl_pedido.tipo_pedido IN (2,3)
		AND   tbl_pedido.garantia_antecipada_distribuidor IS NOT TRUE
	GROUP BY tbl_pedido.posto, tbl_pedido_item.peca, tbl_posto_estoque.qtde;
			

	SELECT estq.posto, 
		SUM (CASE WHEN estq.estoque > estq.qtde THEN estq.qtde ELSE estq.estoque END) AS qtde_atender
	into temp table tmp_tab2
	FROM tmp_interno1 as  estq
	GROUP BY estq.posto;

	SELECT tbl_embarque.posto , MAX (tbl_embarque.data) AS ultimo_embarque
	into temp table tmp_tab3
	FROM tbl_embarque
	GROUP BY tbl_embarque.posto;
			
			
	SELECT tbl_pedido.posto , MIN (tbl_pedido.data) AS pedido_antigo
	into temp table tmp_tab4
	FROM tbl_pedido
	WHERE (tbl_pedido.status_pedido_posto NOT IN (13,4,3) OR tbl_pedido.status_pedido_posto IS NULL)
	AND   tbl_pedido.distribuidor = $login_posto
	GROUP BY tbl_pedido.posto;


	SELECT posto, embarque 
	into temp table tmp_tab5
	FROM tbl_embarque 
	WHERE faturar IS NULL;


	SELECT tbl_posto.posto, tbl_posto.nome, TO_CHAR (emb.ultimo_embarque,'DD/MM/YYYY') AS ultimo_embarque , TO_CHAR (antigo.pedido_antigo,'DD/MM/YYYY') AS pedido_antigo, ped.qtde_pedido, estq.qtde_atender, tbl_posto.cidade, tbl_posto.estado, tbl_posto.fone, tbl_posto.email, andamento.embarque
	FROM tbl_posto
	JOIN    tmp_tab1 as ped ON tbl_posto.posto = ped.posto
	LEFT JOIN tmp_tab2 as estq ON estq.posto = tbl_posto.posto 
	LEFT JOIN tmp_tab3 as emb ON tbl_posto.posto = emb.posto
	LEFT JOIN tmp_tab4 as antigo ON tbl_posto.posto = antigo.posto
	LEFT JOIN tmp_tab5 as  andamento ON tbl_posto.posto = andamento.posto
	ORDER BY current_date - ultimo_embarque DESC , antigo.pedido_antigo ";


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
