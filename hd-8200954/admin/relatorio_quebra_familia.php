<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$title = traduz("RELATÓRIO DE QUEBRA: FAMÍLIA DE PRODUTO NOS ÚLTIMOS 12 MESES");
$layout_menu = "gerencia";

include "cabecalho_new.php";

$plugins = array(
	"dataTable"
);

include("plugin_loader.php");

?>
<script src="js/novo_highcharts.js"></script>
<script type="text/javascript">
$(function () {
	$.dataTableLoad({
            	table: "#relatorio_quebra_famila",
            	type: "basic"
	});
});
</script>

</div>

<?

//--==== Otimização para rodar o relatório Anual =============================================
echo "<table id='relatorio_meses' style='margin: 0 auto;' class='table table-striped table-bordered table-hover table-large'>";
echo "<thead><tr class='titulo_tabela'>";
echo "<th colspan='12'>". traduz("Processando os Seguintes Meses") ."</th>";
echo "</tr>";
echo "<tr class='titulo_coluna'>";
for($x = 0; $x < 12; $x++) {
	$ultimo_dia = date("t", mktime(0, 0, 0, date("m") - 12 + $x, '01', date("Y")));
	$data_serv = mktime(date("H"), date("i"), date("s"), date("m") - 12 + $x, $ultimo_dia, date("Y"));
	$xMES = strftime("%m/%Y", $data_serv);
	echo "<th id='mes_$x'>$x". traduz("MES")."</th>";
}
echo "</tr></thead>";
echo "</table>";
flush();

$cond_garantia = '';
if ($login_fabrica == 117) {
	$cond_garantia = ' AND tbl_os_extra.garantia IS NOT false ';
}

for($x = 0; $x < 12; $x++) {
	$ultimo_dia = date("t", mktime(0, 0, 0, date("m") - 12 + $x, '01', date("Y")));
	$data_serv = mktime(date("H"), date("i"), date("s"), date("m") - 12 + $x, $ultimo_dia, date("Y"));
	$data_inicial = strftime ("%Y-%m-01", $data_serv);
	$xdata_inicial = $data_inicial .' 00:00:00';

	$sql = "SELECT ('$data_inicial'::DATE + INTERVAL'1 MONTH'- INTERVAL'1 day')::DATE || ' 23:59:59';";
	$res = pg_query($con,$sql);
	$xdata_final = pg_fetch_result($res,0,0);

	$cond_1 = "AND tbl_extrato.liberado IS NOT NULL";

	$aux = $login_admin;
	if($x == 0){
		$sql = "SELECT tbl_os_extra.os , tbl_extrato.data_geracao
			INTO TEMP tmp_rqf_$aux
			FROM tbl_extrato
			JOIN tbl_os_extra USING (extrato)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
			$cond_garantia
			$cond_1;

			CREATE INDEX tmp_rqf_OS_$aux ON tmp_rqf_$aux(os);";
		if($login_fabrica == 178){
			
			$sql = "SELECT 	os, 
					data_abertura AS data_geracao 
				INTO TEMP tmp_rqf_$aux
				FROM tbl_os 
				WHERE fabrica = {$login_fabrica} 
				AND data_abertura BETWEEN '$xdata_inicial' AND '$xdata_final'; 
				CREATE INDEX tmp_rqf_OS_$aux ON tmp_rqf_$aux(os);";
		}
	}else{
		$sql = "INSERT INTO tmp_rqf_$aux (os,data_geracao)
			SELECT tbl_os_extra.os , tbl_extrato.data_geracao
			FROM tbl_extrato
			JOIN tbl_os_extra USING (extrato)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
			$cond_garantia
			$cond_1;";

		if($login_fabrica == 178){
			$sql = "INSERT INTO tmp_rqf_$aux (os,data_geracao)
				SELECT  os, 
					data_abertura AS data_geracao 
				FROM tbl_os 
				WHERE fabrica = {$login_fabrica} 
				AND data_abertura BETWEEN '$xdata_inicial' AND '$xdata_final';";
		}
	}
	$res = pg_query($con,$sql);
	flush();
}

######## Produto Composto - Fujitsu [138] HD 2541097 (01/10/2015)#########
if (in_array($login_fabrica, array(138))) {
	$sql = "SELECT tbl_familia.descricao,tbl_familia.familia,to_char(fcr.data_geracao,'YYYY-MM')As data_geracao, COUNT(*) AS qtde
		FROM tbl_os_produto
		JOIN tbl_os USING (os)
		JOIN tmp_rqf_$aux         fcr ON tbl_os.os = fcr.os
		JOIN tbl_posto                ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_produto              ON tbl_produto.produto = tbl_os_produto.produto
		JOIN tbl_familia              ON tbl_familia.familia = tbl_produto.familia
		WHERE tbl_os.excluida IS NOT TRUE
		GROUP BY tbl_familia.familia,tbl_familia.descricao, to_char(data_geracao,'YYYY-MM')
		ORDER BY tbl_familia.familia,tbl_familia.descricao,data_geracao";
} else {
	$sql = "SELECT tbl_familia.descricao,tbl_familia.familia,to_char(fcr.data_geracao,'YYYY-MM')As data_geracao, COUNT(*) AS qtde
		FROM tbl_os
		JOIN tmp_rqf_$aux         fcr ON tbl_os.os = fcr.os
		JOIN tbl_posto                ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
		JOIN tbl_familia              ON tbl_familia.familia = tbl_produto.familia
		WHERE tbl_os.excluida IS NOT TRUE
		GROUP BY tbl_familia.familia,tbl_familia.descricao, to_char(data_geracao,'YYYY-MM')
		ORDER BY tbl_familia.familia,tbl_familia.descricao,data_geracao";
}

//echo nl2br($sql) . "<br><Br>";exit;
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {

	echo "<table id='relatorio_quebra_famila' class='table table-striped table-bordered table-large'>";
	echo "<thead>";
	echo "<tr class='titulo_tabela'>";
	echo "<th colspan='14'>". traduz("Relatório de Quebra nos últimos 12 meses por Família") ."</th>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<th rowspan='2'>". traduz("Família") ."</th>";
	echo "<th colspan='12'>". traduz("Meses") ."</th>";
	echo "<th rowspan='2'>". traduz("Total Ano") ."</th>";
	echo "</tr><tr class='titulo_coluna'>";
	for($x = 0; $x < 12; $x++){

		$ultimo_dia = date("t", mktime(0, 0, 0, date("m") - 12 + $x, '01', date("Y")));
		$data_serv = mktime(date("H"), date("i"), date("s"), date("m") - 12 + $x, $ultimo_dia, date("Y"));
		$mes[$x] = strftime ("%m/%Y", $data_serv);
		$iMes[$x] = strftime ("%m", $data_serv);
		echo "<th>$mes[$x]</th>";

	}
	echo "</tr></thead>";

	$familia = 0;
	$x = 0;
	$y = 0;
	//zerando todos arrays
	$familia_total = 0;

	$arrayQuebraFamilia = array();
	$arrayChart = array();
	$numArray = 0;

	for ($i = 0; $i < pg_num_rows($res); $i++){
		$descricao 		= trim(pg_fetch_result($res,$i,descricao));
		$familia 		= trim(pg_fetch_result($res,$i,familia));
		$data_geracao 		= trim(pg_fetch_result($res,$i,data_geracao));
		$qtde 			= trim(pg_fetch_result($res,$i,qtde));

		if($familia_anterior != $familia){
			$arrayQuebraFamilia[$i]['descricao'] = $descricao;
			$arrayQuebraFamilia[$i]['familia'] = $familia;
			$arrayQuebraFamilia[$i]['meses'][] = array("data" => $data_geracao, "qtde" => $qtde);
			$arrayQuebraFamilia[$i]['qtdeTotal'] += $qtde;

			$familia_anterior = $familia;
			$familia_total += 1;
			$numArray = $i;
		} else {
			$arrayQuebraFamilia[$numArray]['meses'][] = array("data" => $data_geracao, "qtde" => $qtde);
			$arrayQuebraFamilia[$numArray]['qtdeTotal'] += $qtde;
		}
	}

	/*var_dump($arrayQuebraFamilia);
	exit(0);*/

	echo "<tbody>";

	foreach($arrayQuebraFamilia as $i => $arrayFamilia) {
		echo "<tr>";
		echo "<td><a href='relatorio_quebra_produto.php?familia=".$arrayFamilia['familia']."'>".$arrayFamilia['descricao']."</a></td>";
		$arrayChart[$i]['name'] = utf8_encode($arrayFamilia['descricao']);
		foreach ($iMes as $numMes) {
			$temQtde = false;
			foreach ($arrayFamilia['meses'] as $mesQtde) {
				$dataExp = explode('-', $mesQtde['data']);
				if ($numMes == $dataExp[1]) {
					echo "<td>".$mesQtde['qtde']."</td>";
					$qtdeMesChart = (int)$mesQtde['qtde'];
					$temQtde = true;
					break;
				}
			}
			if (!$temQtde) {
				echo "<td>0</td>";
				$qtdeMesChart = 0;
			}
			$arrayChart[$i]['data'][] = $qtdeMesChart;
		}
		echo "<td>".$arrayFamilia['qtdeTotal']."</td>";
		echo "</tr>";
	}

	echo "</tbody></table>";

	include "relatorio_quebra_familia_grafico_new.php";
}

echo "<br />";

include 'rodape.php';

?>
