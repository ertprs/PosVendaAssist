<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


$layout_menu = "cadastro";
$title = "Lista de Produtos e suas Garantias";
//include 'cabecalho.php';
?>


<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}

</style>

</head>

<BODY id="homePage">

<p>
<?
	// $sql2 = 
	$sql =	"SELECT tbl_pedido.pedido, to_char(tbl_pedido.data,'DD/MM/YYYY HH:MM:SS') as data, tbl_faturamento.nota_fiscal, tbl_faturamento.emissao
				from tbl_pedido
				LEFT JOIN tbl_faturamento on tbl_faturamento.pedido = tbl_pedido.pedido
				where tbl_pedido.pedido >= 70000 and tbl_pedido.pedido <= 70999 and tbl_pedido.fabrica = 3;";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($i % 20 == 0) {
			if ($i > 0) echo "</table>";
			flush();
			echo "<table width='700' align='center' border='1' class='conteudo' cellpadding='2' cellspacing='0'>";
			echo "<tr bgcolor='#D9E2EF'>";

			echo "<td align='center' width='20%'>";
			echo "<b>Pedido</b>";
			echo "</td>";

			echo "<td align='left' width='40%'>";
			echo "<b>Data</b>";
			echo "</td>";

			echo "<td align='center' width='10%'>";
			echo "<b>NF</b>";
			echo "</td>";

			echo "<td align='center' width='10%'>";
			echo "<b>Data NF</b>";
			echo "</td>";

			echo "</tr>";
		}

		echo "<tr>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,pedido);
		echo "&nbsp;</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,data);
		echo "&nbsp;</td>";

		echo "<td align='right' nowrap>";
		echo pg_result ($res,$i,nota_fiscal);
		echo "&nbsp;</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,emissao);
		echo "&nbsp;</td>";
		echo "</tr>";
	}

	echo "</table><br>";
?>

<p>
<?
	// $sql2 = 
	$sql =	"SELECT tbl_pedido.pedido, to_char(tbl_pedido.data,'DD/MM/YYYY HH:MM:SS') as data, tbl_faturamento.nota_fiscal, to_char(tbl_faturamento.emissao,'DD/MM/YYYY') as emissao
				from tbl_pedido
				LEFT JOIN tbl_faturamento on tbl_faturamento.pedido = tbl_pedido.pedido
				where tbl_pedido.pedido >= 72000 and tbl_pedido.pedido <= 72999 and tbl_pedido.fabrica = 3;";
	$res = pg_exec ($con,$sql);
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($i % 20 == 0) {
			if ($i > 0) echo "</table>";
			flush();
			echo "<table width='700' align='center' border='1' class='conteudo' cellpadding='2' cellspacing='0'>";
			echo "<tr bgcolor='#D9E2EF'>";

			echo "<td align='center' width='20%'>";
			echo "<b>Pedido</b>";
			echo "</td>";

			echo "<td align='left' width='40%'>";
			echo "<b>Data</b>";
			echo "</td>";

			echo "<td align='center' width='10%'>";
			echo "<b>NF</b>";
			echo "</td>";

			echo "<td align='center' width='10%'>";
			echo "<b>Data NF</b>";
			echo "</td>";

			echo "</tr>";
		}

		echo "<tr>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,pedido);
		echo "&nbsp;</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,data);
		echo "&nbsp;</td>";

		echo "<td align='right' nowrap>";
		echo pg_result ($res,$i,nota_fiscal);
		echo "&nbsp;</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,emissao);
		echo "&nbsp;</td>";
		echo "</tr>";
	}

	echo "</table><br>";
?>

<p>

<?
//	include "rodape.php";
?>
