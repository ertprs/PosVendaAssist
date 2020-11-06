<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$layout_menu = "os";
$title = "Relação de OS com peça para previsão de entrega";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Arial;
	font-size: 10px;
	font-weight: normal;
}
</style>

<br>

<?
$sql =	"SELECT LPAD(tbl_os.sua_os,20,'0') AS ordem              ,
				tbl_os.sua_os                                    ,
				tbl_os.consumidor_nome                           ,
				tbl_produto.referencia     AS produto_referencia ,
				tbl_produto.descricao      AS produto_descricao  ,
				tbl_peca.referencia        AS peca_referencia    ,
				tbl_peca.descricao         AS peca_descricao
		FROM   tbl_os
		JOIN   tbl_os_produto    ON  tbl_os_produto.os       = tbl_os.os
		JOIN   tbl_os_item       ON  tbl_os_item.os_produto  = tbl_os_produto.os_produto
		JOIN   tbl_peca          ON  tbl_peca.peca           = tbl_os_item.peca
		JOIN   tbl_produto       ON  tbl_produto.produto     = tbl_os.produto
		WHERE  tbl_os.fabrica = $login_fabrica
		AND    tbl_os.posto           = $login_posto
		AND    tbl_peca.previsao_entrega > date(current_date + INTERVAL '20 days')
		AND    tbl_os.finalizada ISNULL
		ORDER BY LPAD(tbl_os.sua_os,20,'0');";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	echo "<h1>Favor entrar em contato com a fábrica p/ a troca do produto.</h1>";
	echo "<br>";
	echo "<table width='600' border='1' cellspadding='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo'>";
	echo "<td>OS</td>";
	echo "<td>CONSUMIDOR</td>";
	echo "<td>PRODUTO</td>";
	echo "<td>PEÇA</td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$sua_os             = trim(pg_result($res,$i,sua_os));
		$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
		$produto_referencia = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
		$peca_referencia    = trim(pg_result($res,$i,peca_referencia));
		$peca_descricao     = trim(pg_result($res,$i,peca_descricao));
		$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";
		echo "<tr class='Conteudo' bgcolor='$cor'>";
		echo "<td>".$sua_os."</td>";
		echo "<td>".substr($consumidor_nome,0,20)."</td>";
		echo "<td><acronym title='Referência: $produto_referencia | Descrição: $produto_descricao'>".substr($produto_descricao,0,20)."</acronym></td>";
		echo "<td><acronym title='Referência: $peca_referencia | Descrição: $peca_descricao'>".substr($peca_descricao,0,20)."</acronym></td>";
		echo "</tr>";
	}
	echo "</table>";
}
?>

<BR>

<? include "rodape.php" ?>
