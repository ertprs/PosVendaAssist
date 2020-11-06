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
<title>Acerto de DE-PARA</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>
<STYLE> 
.novo_estilo TD {
FONT-FAMILY:Arial;
FONT-SIZE:16px;
}
</STYLE>

<? include 'menu.php' ?>


<center><h1>Acerto de DE-PARA</h1></center>

<p>

Desconsidera localização = 'FL'

<p>


<?

flush();


$sql = "SELECT DISTINCT	de.referencia AS de_referencia, de.descricao AS de_descricao, de.qtde AS de_qtde, de.localizacao AS de_localizacao,
				para.referencia AS para_referencia, para.descricao AS para_descricao, para.qtde AS para_qtde, para.localizacao AS para_localizacao
		FROM    tbl_depara
		JOIN      (SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, tbl_posto_estoque_localizacao.localizacao FROM tbl_peca JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de   LEFT JOIN tbl_posto_estoque ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.peca = tbl_peca.peca AND tbl_posto_estoque_localizacao.posto = $login_posto) de   ON tbl_depara.peca_de   = de.peca
		LEFT JOIN (SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, tbl_posto_estoque_localizacao.localizacao FROM tbl_peca JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_para LEFT JOIN tbl_posto_estoque ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto LEFT JOIN tbl_posto_estoque_localizacao ON tbl_posto_estoque_localizacao.peca = tbl_peca.peca AND tbl_posto_estoque_localizacao.posto = $login_posto) para ON tbl_depara.peca_para = para.peca
		WHERE tbl_depara.fabrica IN (".implode(",", $fabricas).")
		AND   de.qtde IS NOT NULL
		AND   (de.localizacao <> 'FL' OR de.qtde <> 0)
		ORDER BY de_localizacao";
$res = pg_exec ($con,$sql);


echo "<table align='center' border='0' cellspacing='1' cellpadding='1' class='novo_estilo'>";
echo "<tr bgcolor='#0099cc' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td>DE-Referência</td>";
echo "<td>DE-Descrição</td>";
echo "<td>DE-Estoque</td>";
echo "<td>DE-Localização</td>";

echo "<td>PARA-Referência</td>";
echo "<td>PARA-Descrição</td>";
echo "<td>PARA-Estoque</td>";
echo "<td>PARA-Localização</td>";
echo "</tr>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$cor = "eeeeee";
	if ($i % 2 == 0) $cor = '#cccccc';
		
	echo "<tr bgcolor='$cor' style='font-size:10px'>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,de_referencia);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,de_descricao);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,de_qtde);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,de_localizacao);
	echo "</td>";




	echo "<td nowrap>";
	echo pg_result ($res,$i,para_referencia);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,para_descricao);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,para_qtde);
	echo "</td>";

	echo "<td nowrap>";
	echo pg_result ($res,$i,para_localizacao);
	echo "</td>";

	echo "</tr>";
}

echo "</table>";


?>

<? #include "rodape.php"; ?>

</body>
</html>
<?
include'rodape.php';
?>