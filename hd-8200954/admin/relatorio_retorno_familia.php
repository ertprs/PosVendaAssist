<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : FAMÍLIA DE PRODUTO";

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<?


include "cabecalho.php";

$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
$data_inicial = strftime ("%Y-%m-%d", $data_serv);

$xdata_inicial = $data_inicial .' 00:00:00';
$xdata_final = date("Y-m-d 23:59:59");



$sql = "SELECT tbl_familia.descricao,tbl_familia.familia,to_char(fcr.data_geracao,'YYYY-MM')As data_geracao, COUNT(*) AS qtde
		FROM tbl_os
		JOIN (
			SELECT tbl_os_extra.os , tbl_extrato.data_geracao,(
				SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1
				) AS status
			FROM tbl_os_extra
			JOIN tbl_extrato USING (extrato)
			WHERE tbl_extrato.fabrica = 14
			AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
			AND   tbl_extrato.liberado IS NOT NULL
		) fcr ON tbl_os.os = fcr.os
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
		JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		AND tbl_os.excluida IS NOT TRUE
		GROUP BY tbl_familia.familia,tbl_familia.descricao, to_char(data_geracao,'YYYY-MM')
		ORDER BY tbl_familia.familia,tbl_familia.descricao,data_geracao";
//echo nl2br($sql);
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	
	echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700' align='center'>";
	echo "<tr class='Titulo'>";
	echo "<td width='100' rowspan='2'>Família</td>";
	echo "<td colspan='12'>Meses</td>";
	echo "</tr><tr class='Titulo'>";
	for($x=0;$x<12;$x++){

		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")- 12 + $x , date("d"), date("Y"));
		$mes[$x] = strftime ("%m/%Y", $data_serv);
		
		echo "<td>$mes[$x]</td>";

	}
	echo "</tr>";

	$familia=0;
	$x=0;

	for ($i=0; $i<pg_numrows($res); $i++){

		$familia         = trim(pg_result($res,$i,familia));
		$descricao       = trim(pg_result($res,$i,descricao));
		$data_geracao = trim(pg_result($res,$i,data_geracao));
		$qtde            = trim(pg_result($res,$i,qtde));

		$xdata_geracao = explode('-',$data_geracao);
		$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

		if($familia_anterior<>$familia){

			if ($i % 2 == 0 AND $familia_anterior<>$familia) {
				$cor   = "#F1F4FA";
			}else{
				$cor   = "#F7F5F0";
			}

			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor'><a href='?familia=$familia'><font size='1'> $descricao</font></a></td>";
			
			$familia_anterior=$familia;

		}

		if($data_geracao <> $mes[$x]){
			echo "<td bgcolor='$cor'>0</td>";
			$x=$x+1;
		}
		echo "<td bgcolor='$cor'>$qtde</td>";

		$x=$x+1;
		if($x==12)$x=0;

		if(($i%11)==0 AND $i<>0 AND $familia_anterior<>$familia){
			echo "</tr>";
		}

	}
	echo "</table>";
}

?>