<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "REPORTE DE FALLAS: FAMILIA DE PRODUCTOS EN LOS ÚLTIMOS 12 MESES";

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
.Mes{
	font-size: 8px;
}
</style>

<?


include "cabecalho.php";

$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
$data_inicial = strftime ("%Y-%m-%d", $data_serv);

$xdata_inicial = $data_inicial .' 00:00:00';
$xdata_final = date("Y-m-d 23:59:59");


$sql = "SELECT	tbl_familia_idioma.descricao,
				tbl_familia.familia,
				TO_CHAR(fcr.data_geracao,'YYYY-MM')As data_geracao, 
				COUNT(*) AS qtde
		FROM tbl_os
		JOIN (
			SELECT tbl_os_extra.os , tbl_extrato.data_geracao,(
				SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1
				) AS status
			FROM tbl_os_extra
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_posto   USING (posto)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
			AND   tbl_extrato.liberado IS NOT NULL
			AND   tbl_extrato.aprovado IS NOT NULL
			AND   tbl_posto.pais = '$login_pais'
		) fcr ON tbl_os.os = fcr.os
		JOIN tbl_posto   ON tbl_os.posto        = tbl_posto.posto
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
		JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
		JOIN tbl_familia_idioma ON tbl_familia_idioma.familia = tbl_familia.familia
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		AND tbl_os.excluida IS NOT TRUE
		AND tbl_posto.pais = '$login_pais' 
		GROUP BY tbl_familia.familia,tbl_familia_idioma.descricao, to_char(data_geracao,'YYYY-MM')
		ORDER BY tbl_familia.familia,tbl_familia_idioma.descricao,data_geracao";
if ($ip == "201.71.54.144") { echo nl2br($sql);}
//exit;
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	

	echo "<table border='2' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700' align='center'>";
	echo "<tr class='Titulo'>";
	echo "<td colspan='14' align='left'>Reporte de falla de los ultimos 12 meses por Familia</td>";
	echo "</tr>";
	echo "<tr class='Titulo'>";
	echo "<td width='100' rowspan='2'>Familia</td>";
	echo "<td colspan='12'>Meses</td>";
	echo "<td rowspan='2' class='Mes'>Total Ano</td>";
	echo "</tr><tr class='Titulo'>";
	for($x=0;$x<12;$x++){

		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")- 12 + $x , date("d"), date("Y"));
		$mes[$x] = strftime ("%m/%Y", $data_serv);
		
		echo "<td class='Mes'>$mes[$x]</td>";

	}
	echo "</tr>";

	$familia=0;
	$x=0;
	$y=0;
	//zerando todos arrays
	$familia_total=0;
	$qtde_mes =  array();

	for ($i=0; $i<pg_numrows($res); $i++){
		$descricao         = trim(pg_result($res,$i,descricao));
		$familia           = trim(pg_result($res,$i,familia));
		if($familia_anterior<>$familia){
			$qtde_mes[$familia_total][0]  = 0;
			$qtde_mes[$familia_total][1]  = 0;
			$qtde_mes[$familia_total][2]  = 0;
			$qtde_mes[$familia_total][3]  = 0;
			$qtde_mes[$familia_total][4]  = 0;
			$qtde_mes[$familia_total][5]  = 0;
			$qtde_mes[$familia_total][6]  = 0;
			$qtde_mes[$familia_total][7]  = 0;
			$qtde_mes[$familia_total][8]  = 0;
			$qtde_mes[$familia_total][9]  = 0;
			$qtde_mes[$familia_total][10] = 0;
			$qtde_mes[$familia_total][11] = 0;
			$qtde_mes[$familia_total][12] = $descricao;
			$x=0;
			$familia_anterior=$familia;
			$familia_total = $familia_total+1;
		}
	}
	echo 'Total de familia: '.$familia_total;
	for ($i=0; $i<pg_numrows($res); $i++){

		$familia         = trim(pg_result($res,$i,familia));
		$descricao    = trim(pg_result($res,$i,descricao));
		$data_geracao = trim(pg_result($res,$i,data_geracao));
		$qtde         = trim(pg_result($res,$i,qtde));

		$xdata_geracao = explode('-',$data_geracao);
		$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

		if($familia_anterior<>$familia OR pg_numrows($res)==1){

//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO familia
			if($i<>0 AND $familia_anterior<>$familia ){
				
				for($a=0;$a<12;$a++){			//imprime os doze meses
					echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
					if ($qtde_mes[$y][$a]>0)
						echo "<font color='#000000'><b>".$qtde_mes[$y][$a];
					else echo "<font color='#999999'> ";

					echo "</td>";
					$total_ano = $total_ano + $qtde_mes[$y][$a];
					if($a==11) {
						echo "<td bgcolor='$cor' >$total_ano</td>";
						echo "</tr>";
					}	// se for o ultimo mes quebra a linha
				}

				$y=$y+1;						// usado para indicação de familia
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' width='150' height='40'><a href='relatorio_quebra_produto.php?familia=$familia' class='mes'>$descricao</a></td>";

			$total_ano = 0;
			$x=0; //ZERA OS MESES
			$familia_anterior=$familia; 
		}

		while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
			$x=$x+1;
		};

		if($data_geracao==$mes[$x]){
			$qtde_mes[$y][$x] = $qtde;
		}

		$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
		if($i==(pg_numrows($res)-1)){
			for($a=0;$a<12;$a++){			//imprime os doze meses
				echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
				if ($qtde_mes[$y][$a]>0)
					echo "<font color='#000000'><b>".$qtde_mes[$y][$a];
				else echo "<font color='#999999'> ";

				echo "</td>";
				$total_ano = $total_ano + $qtde_mes[$y][$a];
				if($a==11) {
					echo "<td bgcolor='$cor' >$total_ano</td>";
					echo "</tr>";
				}	// se for o ultimo mes quebra a linha
			}
		}
	}
	echo "</table>";
/*	for($i=0; $i<$familia_total ; $i++){
		for($j=0; $j<13 ; $j++)echo $qtde_mes[$i][$j]." - ";
	echo "<br><br>";
	}*/
	include 'relatorio_quebra_familia_grafico.php';
}else{
	echo "<p>Ningún resultado encuentrado</p>";
}
echo "<br>";
include 'rodape.php';
//
?>