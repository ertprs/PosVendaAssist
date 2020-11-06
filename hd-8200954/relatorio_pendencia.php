<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

$layout_menu = "pedido";
$title = "Pendência do Posto";
include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<script language="JavaScript">
function FuncMouseOver (linha, cor) {
	linha.style.cursor = "hand";
	linha.style.backgroundColor = cor;
}
function FuncMouseOut (linha, cor) {
	linha.style.cursor = "default";
	linha.style.backgroundColor = cor;
}
</script>

<br>

<?
$sql =	"SELECT DISTINCT pedido_blackedecker
		FROM tbl_pendencia_bd
		WHERE posto = $login_posto
		ORDER BY pedido_blackedecker;";
$res = pg_exec($con,$sql);

# if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);

if (pg_numrows($res) > 0) {
	$resultado = pg_numrows($res);
	
	echo "<h3><font size='1'><center><b>Clique no pedido para visualizar as peças.</b></center></font></h3>";
	
	echo "<table width='200' border='1' cellpadding='2' align='center' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<tr class='Titulo' height='15'>";
	echo "<td>PEDIDO</td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$pedido_blackedecker = trim(pg_result($res,$i,pedido_blackedecker));
		
		$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
		
		echo "<tr class='Conteudo' height='15' bgcolor='$cor' onclick=\"javascript: window.location = '$PHP_SELF?pedido=$pedido_blackedecker';\" onmouseover=\"javascript: FuncMouseOver(this, '#FFCC99');\" onmouseout=\"javascript: FuncMouseOut(this, '$cor');\">";
		echo "<td nowrap align='center'>" . $pedido_blackedecker . "</a></td>";
		echo "</tr>";
	}
	echo "</table>";
}
	echo "<h3><center><b>Resultado: $resultado registro(s).</b></center></h3>";

	//mostra as peças dos pedidos
	
	if (strlen($_GET["pedido"]) > 0) $pedido = $_GET["pedido"];

		
	if (strlen($pedido) > 0) {
	
	
	$sql = "SELECT pedido_blackedecker, referencia_peca, qtde_pendente
			FROM tbl_pendencia_bd
			WHERE posto               = $login_posto
			AND   pedido_blackedecker = '$pedido'
			ORDER BY qtde_pendente;";
	$res = pg_exec($con,$sql);

	
#	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);

	if (pg_numrows($res) > 0) {
		$resultado = pg_numrows($res);
	//monta tabela de acordo com cada pedido	
		echo "<table width='300' border='1' cellpadding='2' align='center' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td colspan='2'>PEDIDO: $pedido</td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td>PEÇA</td>";
		echo "<td>QTDE</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$peca_referencia     = trim(pg_result($res,$i,referencia_peca));
			$qtde_pendente       = trim(pg_result($res,$i,qtde_pendente));
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if (strlen($peca_referencia) > 0) {
				$sqlX =	"SELECT referencia ,
								descricao
						FROM tbl_peca
						WHERE fabrica    = $login_fabrica
						AND   referencia = '$peca_referencia'
						AND   descricao IS NOT NULL;";
				$resX = pg_exec($con,$sqlX);
				$peca_referencia = trim(pg_result($resX,0,referencia));
				$peca_descricao  = trim(pg_result($resX,0,descricao));
				echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
				echo "<td nowrap>$peca_referencia - $peca_descricao </td>";
				echo "<td nowrap align='center'>$qtde_pendente</td>";
				echo "</tr>";
			}else{
			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap>$peca_referencia</td>";
			echo "<td nowrap align='center'>$qtde_pendente</td>";
			echo "</tr>";

			}
		}
		echo "</table>";
	}
	echo "<h3><center><b>Resultado: $resultado registro(s).</b></center></h3>";
}

include "rodape.php";
?>
