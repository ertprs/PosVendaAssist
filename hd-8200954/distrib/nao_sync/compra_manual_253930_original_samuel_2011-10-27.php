<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


echo "<html>";
echo "<head>";
echo "<title>Compra Manual Para Gama</title>";
echo '<link type="text/css" rel="stylesheet" href="css/css.css">';
echo "</head>";
echo "<body>";

include 'menu.php' ;


//$res = @pg_exec ($con,"DROP TABLE x_pedido");
//$res = @pg_exec ($con,"DROP TABLE x_pedido_fabrica");
//$res = @pg_exec ($con,"DROP TABLE x_pedido_transp");

$sql = "
SELECT tbl_pedido_item.peca, SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde
INTO TABLE x_pedido
FROM (
SELECT pedido FROM tbl_pedido
WHERE distribuidor = 4311
AND   posto       <> 4311
AND   fabrica      = 51
AND   (status_pedido_posto IN (SELECT status_pedido FROM tbl_status_pedido WHERE status_pedido NOT IN (3,4,6,13)) OR status_pedido_posto IS NULL)
) x
JOIN tbl_pedido_item ON tbl_pedido_item.pedido = x.pedido
WHERE tbl_pedido_item.qtde > tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor
GROUP BY tbl_pedido_item.peca;

DELETE FROM x_pedido WHERE qtde <= 0 ;
DELETE FROM x_pedido WHERE qtde IS NULL ;

ALTER TABLE x_pedido ADD COLUMN estoque INT4 ;
ALTER TABLE x_pedido ADD COLUMN fabrica INT4 ;
ALTER TABLE x_pedido ADD COLUMN transp  INT4 ;

ALTER TABLE x_pedido ADD COLUMN dias30  INT4 ;
ALTER TABLE x_pedido ADD COLUMN dias60  INT4 ;
ALTER TABLE x_pedido ADD COLUMN dias90  INT4 ;
ALTER TABLE x_pedido ADD COLUMN maior   INT4 ;
ALTER TABLE x_pedido ADD COLUMN media   INT4 ;
ALTER TABLE x_pedido ADD COLUMN desvio  INT4 ;
ALTER TABLE x_pedido ADD COLUMN qtdex   INT4 ;
ALTER TABLE x_pedido ADD COLUMN media_ok INT4 ;


/*            Desconsidera pedidos de postos em atraso                  */

ALTER TABLE x_pedido ADD COLUMN qtde_atraso INT4 ;

UPDATE x_pedido SET qtde = (
SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada_distribuidor)
FROM tbl_pedido
JOIN tbl_pedido_item USING (pedido)
WHERE tbl_pedido.distribuidor = 4311
AND   tbl_pedido.posto       <> 4311
AND   tbl_pedido.fabrica      = 51
AND   (tbl_pedido.status_pedido_posto IN (SELECT status_pedido FROM tbl_status_pedido WHERE status_pedido NOT IN (3,4,6,13)) OR tbl_pedido.status_pedido_posto IS NULL)
AND   tbl_pedido.posto IN (SELECT posto FROM tbl_contas_receber WHERE tbl_contas_receber.posto = tbl_pedido.posto AND tbl_contas_receber.recebimento IS NULL AND tbl_contas_receber.vencimento <  CURRENT_DATE - INTERVAL '30 days' )
AND   tbl_pedido_item.peca = x_pedido.peca
)
;

UPDATE tmp_x_pedido SET qtde_atraso = 0 WHERE qtde_atraso IS NULL ;

UPDATE tmp_x_pedido SET qtde = qtde - qtde_atraso ;



/*                Estoque                   */

UPDATE x_pedido SET estoque = (SELECT qtde FROM tbl_posto_estoque WHERE peca = x_pedido.peca AND posto = 4311);
UPDATE x_pedido SET estoque = 0 WHERE estoque IS NULL ;




/*            Pendencia na Fabrica               */

SELECT tbl_pedido_item.peca,
	SUM (qtde - qtde_cancelada - qtde_faturada) AS qtde_fabrica
INTO TEMP TABLE x_pedido_fabrica
FROM tbl_pedido
JOIN tbl_pedido_item USING (pedido)
WHERE tbl_pedido.fabrica = 51
AND  (tbl_pedido.status_pedido IN
(SELECT status_pedido FROM tbl_status_pedido WHERE status_pedido NOT IN (3,4,6,13)) OR tbl_pedido.status_pedido IS NULL)
AND   tbl_pedido_item.qtde > (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)
AND   tbl_pedido.posto = 4311
GROUP BY tbl_pedido_item.peca;

/*RETIRADA A CONSULTA ABAIXO
AND ((tbl_pedido.posto = 4311 AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = 4311 AND tbl_pedido.tipo_pedido = 3 ) )
*/


UPDATE x_pedido SET fabrica = x_pedido_fabrica.qtde_fabrica FROM x_pedido_fabrica WHERE x_pedido.peca = x_pedido_fabrica.peca ;
UPDATE x_pedido SET fabrica = 0 WHERE fabrica IS NULL ;



/*            Na Transportadora           */

SELECT tbl_faturamento_item.peca, SUM (tbl_faturamento_item.qtde) AS qtde_transp
INTO TEMP TABLE x_pedido_transp
FROM tbl_faturamento
JOIN tbl_faturamento_item USING (faturamento)
WHERE tbl_faturamento.posto = 4311
AND   tbl_faturamento.distribuidor IS NULL
AND   tbl_faturamento.fabrica = 51
AND   tbl_faturamento.conferencia IS NULL
AND   tbl_faturamento.cancelada   IS NULL
GROUP BY tbl_faturamento_item.peca
;

UPDATE x_pedido SET transp = x_pedido_transp.qtde_transp FROM x_pedido_transp WHERE x_pedido.peca = x_pedido_transp.peca ;
UPDATE x_pedido SET transp = 0 WHERE transp IS NULL ;


/*            Saldo para Comprar           */

ALTER TABLE x_pedido ADD COLUMN qtde_comprar INT4 ;
UPDATE x_pedido SET qtde_comprar = qtde - estoque - fabrica - transp ;



DELETE FROM x_pedido WHERE peca IN (SELECT peca FROM tbl_peca WHERE fabrica = 51 AND produto_acabado) ;

/*DELETE FROM x_pedido WHERE peca IN (SELECT peca FROM tbl_peca WHERE fabrica = 51 AND referencia LIKE '7%') ;*/


/*       30 dias           */
SELECT tbl_pedido_item.peca, SUM (tbl_pedido_item.qtde) AS qtde
INTO TEMP TABLE x_pedido_30
FROM tbl_pedido
JOIN tbl_pedido_item USING (pedido)
WHERE tbl_pedido.fabrica = 51
AND   tbl_pedido.distribuidor = 4311
AND   tbl_pedido.posto <> 4311
AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '30 DAYS' AND CURRENT_DATE
GROUP BY tbl_pedido_item.peca;

UPDATE x_pedido SET dias30 = x_pedido_30.qtde
FROM x_pedido_30
WHERE x_pedido.peca = x_pedido_30.peca;


UPDATE x_pedido SET dias30 = 0 WHERE dias30 IS NULL;

/*       60 dias           */
SELECT tbl_pedido_item.peca, SUM (tbl_pedido_item.qtde) AS qtde
INTO TEMP TABLE x_pedido_60
FROM tbl_pedido
JOIN tbl_pedido_item USING (pedido)
WHERE tbl_pedido.fabrica = 51
AND   tbl_pedido.distribuidor = 4311
AND   tbl_pedido.posto <> 4311
AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '60 DAYS' AND CURRENT_DATE - INTERVAL '31 DAYS'
GROUP BY tbl_pedido_item.peca;

UPDATE x_pedido SET dias60 = x_pedido_60.qtde
FROM x_pedido_60
WHERE x_pedido.peca = x_pedido_60.peca;

UPDATE x_pedido SET dias60 = 0 WHERE dias60 IS NULL;

/*       90 dias           */
SELECT tbl_pedido_item.peca, SUM (tbl_pedido_item.qtde) AS qtde
INTO TEMP TABLE x_pedido_90
FROM tbl_pedido
JOIN tbl_pedido_item USING (pedido)
WHERE tbl_pedido.fabrica = 51
AND   tbl_pedido.distribuidor = 4311
AND   tbl_pedido.posto <> 4311
AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '90 DAYS' AND CURRENT_DATE - INTERVAL '61 DAYS'
GROUP BY tbl_pedido_item.peca;

UPDATE x_pedido SET dias90 = x_pedido_90.qtde
FROM x_pedido_90
WHERE x_pedido.peca = x_pedido_90.peca;

UPDATE x_pedido SET dias90 = 0 WHERE dias90 IS NULL;


/*       maior comprador           */
UPDATE x_pedido SET maior = (
SELECT MAX (qtde)
FROM (
	SELECT posto, SUM (qtde) AS qtde
	FROM tbl_pedido
	JOIN tbl_pedido_item USING (pedido)
	WHERE tbl_pedido.fabrica = 51
	AND   tbl_pedido.distribuidor = 4311
	AND   tbl_pedido.posto <> 4311
	AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '30 DAYS' AND CURRENT_DATE
	AND   tbl_pedido_item.peca = x_pedido.peca
	GROUP BY tbl_pedido.posto
	) AS maior
) ;

UPDATE x_pedido SET maior = 0 WHERE maior IS NULL;


/*       media de compra         */
UPDATE x_pedido SET media = (
SELECT AVG (qtde)
FROM (
	SELECT posto, SUM (qtde) AS qtde
	FROM tbl_pedido
	JOIN tbl_pedido_item USING (pedido)
	WHERE tbl_pedido.fabrica = 51
	AND   tbl_pedido.distribuidor = 4311
	AND   tbl_pedido.posto <> 4311
	AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '30 DAYS' AND CURRENT_DATE
	AND   tbl_pedido_item.peca = x_pedido.peca
	GROUP BY tbl_pedido.posto
	) AS maior
) ;

UPDATE x_pedido SET media = 0 WHERE media IS NULL;



/*       desvio padrao       */
UPDATE x_pedido SET desvio = (
SELECT stddev (qtde)
FROM (
	SELECT posto, SUM (qtde) AS qtde
	FROM tbl_pedido
	JOIN tbl_pedido_item USING (pedido)
	WHERE tbl_pedido.fabrica = 51
	AND   tbl_pedido.distribuidor = 4311
	AND   tbl_pedido.posto <> 4311
	AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '30 DAYS' AND CURRENT_DATE
	AND   tbl_pedido_item.peca = x_pedido.peca
	GROUP BY tbl_pedido.posto
	) AS maior
) ;

UPDATE x_pedido SET desvio = 0 WHERE desvio IS NULL;


/*       quantidade de postos que pedem a peca         */
UPDATE x_pedido SET qtdex = (
SELECT COUNT (*)
FROM (
	SELECT posto, SUM (qtde) AS qtde
	FROM tbl_pedido
	JOIN tbl_pedido_item USING (pedido)
	WHERE tbl_pedido.fabrica = 51
	AND   tbl_pedido.distribuidor = 4311
	AND   tbl_pedido.posto <> 4311
	AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '30 DAYS' AND CURRENT_DATE
	AND   tbl_pedido_item.peca = x_pedido.peca
	GROUP BY tbl_pedido.posto
	) AS maior
) ;
UPDATE x_pedido SET qtdex = 0 WHERE qtdex IS NULL;



/*    media sem o desvio padrao       */
UPDATE x_pedido SET media_ok = (
SELECT AVG (qtde)
FROM (
	SELECT posto, SUM (qtde) AS qtde
	FROM tbl_pedido
	JOIN tbl_pedido_item USING (pedido)
	WHERE tbl_pedido.fabrica = 51
	AND   tbl_pedido.distribuidor = 4311
	AND   tbl_pedido.posto <> 4311
	AND   tbl_pedido.data BETWEEN CURRENT_DATE - INTERVAL '30 DAYS' AND CURRENT_DATE
	AND   tbl_pedido_item.peca = x_pedido.peca
	GROUP BY tbl_pedido.posto
	) AS maior
	WHERE maior.qtde < x_pedido.media + x_pedido.desvio
) ;
UPDATE x_pedido SET media_ok = 0 WHERE media_ok IS NULL;




SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido.* FROM x_pedido JOIN tbl_peca USING (peca) ORDER BY x_pedido.dias30 DESC ;

";
//echo nl2br($sql); exit;
//$res = pg_exec ($con,$sql);
echo "<h1>Dados gerados em 16/07/2009 - Fazer apenas 1 compra </h1>";
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido.* FROM x_pedido JOIN tbl_peca USING (peca) ORDER BY x_pedido.dias30 DESC";

$res = pg_exec ($con,$sql);


if(pg_numrows ($res)==0){
	echo "<br><center><b>Não existem peças para compra manual</center></b>";
}else{

	echo "<script>
		  function mostra_esconde_coluna(coluna, mostrar) {

			var status1;
			if (mostrar) statusl = 'block'
			else         statusl = 'none';

			var tbl  = document.getElementById('tabela');
			var rows = tbl.getElementsByTagName('tr');

			for (var row=1; row < rows.length ; row++) {
			  var cels = rows[row].getElementsByTagName('td')
			  cels[coluna].style.display = statusl;
			}
		  }

		</script>";


	echo "<br><br>";


	echo "<div id='esconder' style='display: block'>";
	echo "<a id='esconder' href='#' onclick=\"javascript:
		mostra_esconde_coluna(3,false) ;
		mostra_esconde_coluna(4,false) ;
		mostra_esconde_coluna(5,false) ;
		mostra_esconde_coluna(6,false) ;
		mostra_esconde_coluna(7,false) ;
		mostra_esconde_coluna(8,false) ;
		mostra_esconde_coluna(9,false) ;
		mostra_esconde_coluna(10,false) ;
		document.getElementById('esconder').style.display = 'none';
		document.getElementById('mostrar').style.display  = 'block';
	\">";
	echo "esconder campos de cálculo";
	echo "</a>";
	echo "</div>";


	echo "<div id='mostrar' style='display: none'>";
	echo "<a href='#' onclick=\"javascript:
		mostra_esconde_coluna(3,true) ;
		mostra_esconde_coluna(4,true) ;
		mostra_esconde_coluna(5,true) ;
		mostra_esconde_coluna(6,true) ;
		mostra_esconde_coluna(7,true) ;
		mostra_esconde_coluna(8,true) ;
		mostra_esconde_coluna(9,true) ;
		mostra_esconde_coluna(10,true) ;
		document.getElementById('esconder').style.display = 'block';
		document.getElementById('mostrar').style.display  = 'none';
	\">";
	echo "mostrar campos de cálculo";
	echo "</a>";
	echo "</div>";



	echo "<br><table id='tabela' align='center' border='0' cellspacing='1' cellpaddin='1'>";

	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:26px' align='center'>";
	echo "<td colspan='16' align='center'><font size='+2'>Peças para Compra Manual</font></td>";
	echo "</tr>";

	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Referência</td>";
	echo "<td>Descrição</td>";
	echo "<td>Comprar</td>";
	echo "<td>90d</td>";
	echo "<td>60d</td>";
	echo "<td>30d</td>";
	echo "<td>maior</td>";
	echo "<td>qtdeX</td>";
	echo "<td>media</td>";
	echo "<td>desvio</td>";
	echo "<td>media OK</td>";
	echo "<td>Mínimo</td>";
	echo "<td>Pedido</td>";
	echo "<td>Estoque</td>";
	echo "<td>Fábrica</td>";
	echo "<td>Transp.</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$cor = "#cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';

		echo "<tr bgcolor='$cor'>";

		echo "<td title='Referência'>";
		echo pg_result ($res,$i,referencia);
		echo "</td>";

		echo "<td title='Descrição'>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		$estoque_minimo = round (pg_result ($res,$i,media_ok) * pg_result ($res,$i,qtdeX) / 2 , 0);
		$qtde_comprometida = $estoque_minimo + pg_result ($res,$i,qtde);
		$qtde_vindo = pg_result ($res,$i,estoque) + pg_result ($res,$i,fabrica) + pg_result ($res,$i,transp) ;
		$comprar = $qtde_comprometida - $qtde_vindo ;

		if ($comprar > 0) $cor = "#FF9999";

		echo "<td align='right' bgcolor='$cor' title='comprar'>";
		echo $comprar ;
		echo "</td>";




		echo "<td align='right' title='90d' bgcolor='#0099FF'>";
		echo pg_result ($res,$i,dias90);
		echo "</td>";

		echo "<td align='right' title='60d' bgcolor='#0099FF'>";
		echo pg_result ($res,$i,dias60);
		echo "</td>";

		echo "<td align='right' title='30d' bgcolor='#0099FF'>";
		echo pg_result ($res,$i,dias30);
		echo "</td>";

		echo "<td align='right' title='maior'>";
		echo pg_result ($res,$i,maior);
		echo "</td>";

		echo "<td align='right' title='qtdex'>";
		echo pg_result ($res,$i,qtdex);
		echo "</td>";

		echo "<td align='right' title='media'>";
		echo pg_result ($res,$i,media);
		echo "</td>";

		echo "<td align='right' title='desvio'>";
		echo pg_result ($res,$i,desvio);
		echo "</td>";

		echo "<td align='right' title='media OK'>";
		echo pg_result ($res,$i,media_ok);
		echo "</td>";

		echo "<td align='right' title='Estoque Mínimo'>";
		echo $estoque_minimo;
		echo "</td>";

		echo "<td align='right' title='Pedido' bgcolor='#33CC00'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right' title='Estoque'>";
		echo pg_result ($res,$i,estoque);
		echo "</td>";

		echo "<td align='right' title='Fábrica'>";
		echo pg_result ($res,$i,fabrica);
		echo "</td>";

		echo "<td align='right' title='Transp.'>";
		echo pg_result ($res,$i,transp);
		echo "</td>";


		echo "</tr>";
	}

	echo "</table>";
	flush();
}


?>



<? include "rodape.php"; ?>

</body>
</html>
