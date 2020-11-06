<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";

$layout_menu = "gerencia";
$title = "PRODUTOS MAIS DEMANDADOS";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}
</style>

<?
$meses        = $_POST['meses'];
$qtde_produto = $_POST['qtde_produto'];
$familia      = $_POST['familia'];
?>
<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
Exibir os <input type='text' name='qtde_produto' value='<? echo $qtde_produto ?>' size='3'> produtos que mais quebraram
<br>
Nos últimos <select name='meses' size='1'>
<option value='3' <? if ($meses == "3" or strlen ($meses) == 0) echo " selected " ?> >3 meses</option>
<option value='6' <? if ($meses == "6" ) echo " selected " ?> >6 meses</option>
<option value='12' <? if ($meses == "12" ) echo " selected " ?> >12 meses</option>
</select> meses
<br>

Da família <select name='familia' size='1'>
<option value="">Todas</option>
<?
$linha = $_POST['linha'];

$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY descricao";
$res = pg_exec ($con,$sql);
for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
	echo "<option value='" . pg_result ($res,$i,familia) . "' ";
	if ($familia == pg_result ($res,$i,familia) ) echo " selected " ;
	echo ">";
	echo pg_result ($res,$i,descricao) ;
	echo "</option>";
}
?></select>
<br><br>
<input type="submit" name="acao" value="Pesquisar">
</form>

<br>

<?
if (strlen($acao) > 0 ) {
	
	$array_meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

	//$data_final = date ('Y-m-') . "01";
	$data_final = date ('Y-m-d');

	$cond_1 = " tbl_os.os = tbl_os.os ";
	if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";

	$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao, os.mes, os.qtde
			FROM tbl_produto
			JOIN (SELECT produto, to_char (tbl_os.data_digitacao,'MM') AS mes, COUNT(*) AS qtde FROM tbl_os
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.excluida IS NOT TRUE
					AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS' AND '$data_final'::date
					AND   tbl_os.produto IN ( SELECT produto FROM (
						SELECT produto , COUNT(*) FROM tbl_os 
							JOIN  tbl_produto USING (produto)
							WHERE tbl_os.fabrica = $login_fabrica
							AND   tbl_os.excluida IS NOT TRUE
							AND   $cond_1
							AND   tbl_os.data_digitacao BETWEEN '$data_final'::date - INTERVAL '$meses MONTHS' AND '$data_final'::date
							GROUP BY tbl_os.produto
							ORDER BY COUNT(*) DESC ";
							$sql .= is_numeric($qtde_produto) ? " LIMIT $qtde_produto " : '';
							$sql .= "
						) os1
					) 
					GROUP BY tbl_os.produto, to_char (tbl_os.data_digitacao,'MM')
			) os ON tbl_produto.produto = os.produto
			ORDER BY tbl_produto.referencia, os.mes";
	$res = pg_exec ($con,$sql);
//echo $sql;
	echo "<table border='1' cellpadding='2' cellspacing='0' width='200'> ";
	echo "<tr class='Titulo'>";
	echo "<td>Referência</td>";
	echo "<td colspan='2'>Produto</td>";
	$mes_final = intval (date('m',mktime (0,0,0,date('m')-1)));
	$mes_inicial = intval (date('m',mktime (0,0,0,date('m')-$meses)));

	$indice = 0;
	for ($i = $mes_inicial ; $i <= $mes_final ; $i++) {
		echo "<td>" . $array_meses [ $i ] . "</td>";
		$coluna[$indice] = "<td>&nbsp;</td>";
		$mes_coluna[$indice] = $i;
		$indice++;
	}
	echo "</tr>";

	$produto_antigo = "" ;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($produto_antigo <> pg_result ($res,$i,produto)){
			if (strlen ($produto_antigo) > 0) {
				for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
					echo $coluna [$indice] ;
				}
				echo "</tr>";
			}
			echo "<tr align='left' style='font-size:12px'>";
			echo "<td nowrap>";
			echo pg_result ($res,$i,referencia);
			echo "</td>";

			echo "<td nowrap>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			$total_colunas = count ($coluna);
			for ($indice = 0 ; $indice < $total_colunas ; $indice++) {
				$coluna [$indice] = "<td>&nbsp;</td>";
			}

			$produto_antigo = pg_result ($res,$i,produto);
		}

		$indice = @array_search (pg_result ($res,$i,mes) , $mes_coluna);
		$coluna [$indice] = "<td nowrap align='right'>" . pg_result ($res,$i,qtde) . "</td>";
	}
	for ($indice = 0 ; $indice < count ($coluna) ; $indice++) {
		echo $coluna [$indice] ;
	}
	echo "</tr>";
	echo "</table>";

}

echo "<br>";

include "rodape.php";
?>