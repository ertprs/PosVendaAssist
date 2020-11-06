<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "tecnica";
$title = "Relatório para Vista Explodida";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

</style>


<?
$sql = "SELECT tbl_linha.nome AS linha_nome, tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica ORDER BY tbl_linha.nome, tbl_produto.referencia";

$res = pg_exec ($con,$sql);

echo "<table width='400' align='center' border='0' style='font-size: 10px'>";

$linha_ant = "";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	if ($linha_ant <> pg_result ($res,$i,linha_nome)) {
		echo "<tr>";
		echo "<td colspan='2' bgcolor='#6633CC' align='center'><font size='+1' color='#ffffff'>" . pg_result ($res,$i,linha_nome) . "</font></td>";
		echo "</tr>";
		$linha_ant = pg_result ($res,$i,linha_nome);
	}
	echo "<tr>";

	echo "<td>";
	echo "<a href='vista_explodida.php?produto=" . pg_result ($res,$i,produto) . " ' target='_blank'>";
	echo pg_result ($res,$i,referencia);
	echo "</a>";
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,descricao);
	echo "</td>";

	echo "</tr>";
}

echo "</table>";


?>



<?

echo "<BR><BR>";

include "rodape.php"; 

?>