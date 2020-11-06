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

#HD 17140
if (1==2){
	echo "<TABLE  border='0' cellspacing='0' cellpadding='2' align='center' name='relatorio' id='relatorio'  style=' border:#485989 1px solid; background-color: #e6eef7 '>";
	echo "<tr>";
	echo "<td colspan='12' class='Titulo'>CONSULTA MESES</td>";
	echo "</tr>";
	echo "<tr>";
	for($x=0;$x<12;$x++){
		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12 +$x  , date("d"), date("Y"));
		$xMES = strftime ("%m/%Y", $data_serv);
		echo "<td width='40' height='40' class='Conteudo' bgcolor='#FFFFDD' id='mes_$x'>$xMES</td>";
	}
	echo "</tr></table>";
}
flush();
for($x=0;$x<12;$x++){
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12 +$x  , date("d"), date("Y"));
	$data_inicial = strftime ("%Y-%m-01", $data_serv);
	$xdata_inicial = $data_inicial .' 00:00:00';

	$sql = "SELECT ('$data_inicial'::DATE + INTERVAL'1 MONTH'- INTERVAL'1 day')::DATE || ' 23:59:59';";
	$res = pg_exec($con,$sql);
	$xdata_final = pg_result($res,0,0);

	//if($login_fabrica<>3)
	$cond_1 = "AND   tbl_extrato.aprovado IS NOT NULL";

	$aux = $login_admin;
	if($x==0){
		$sql = "SELECT tbl_os_extra.os , tbl_extrato.data_geracao
				INTO TEMP tmp_rqf_$aux
				FROM tbl_extrato
				JOIN tbl_os_extra USING (extrato)
				JOIN tbl_posto    USING(posto)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_posto.pais      = '$login_pais'
				AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
				$cond_1;
		
				CREATE INDEX tmp_rqf_OS_$aux ON tmp_rqf_$aux(os);";
	}else{
		$sql = "INSERT INTO tmp_rqf_$aux (os,data_geracao) 
				SELECT tbl_os_extra.os , tbl_extrato.data_geracao
				FROM tbl_extrato
				JOIN tbl_os_extra USING (extrato)
				JOIN tbl_posto    USING(posto)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_posto.pais      = '$login_pais'
				AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
				$cond_1;";
	}
	#echo nl2br($sql);
	#echo "<hr>";
	$res = pg_exec($con,$sql);
	#HD 17140
	#echo "<script language='javascript'>document.getElementById('mes_$x').style.background = '#D7FFE1'</script>";
//echo $sql.'<br>';
	flush();
}
//--========================================================================================


$sql = "

	SELECT tbl_familia_idioma.descricao,tbl_familia.familia,to_char(fcr.data_geracao,'YYYY-MM')As data_geracao, COUNT(*) AS qtde
	FROM tbl_os
	JOIN tmp_rqf_$aux         fcr ON tbl_os.os = fcr.os
	JOIN tbl_posto                ON tbl_os.posto = tbl_posto.posto
	JOIN tbl_produto              ON tbl_produto.produto = tbl_os.produto
	JOIN tbl_familia              ON tbl_familia.familia = tbl_produto.familia
	JOIN tbl_familia_idioma       ON tbl_familia_idioma.familia = tbl_familia.familia
	WHERE tbl_os.excluida IS NOT TRUE
	AND   tbl_posto.pais      = '$login_pais'
	GROUP BY tbl_familia.familia,tbl_familia_idioma.descricao, to_char(data_geracao,'YYYY-MM')
	ORDER BY tbl_familia.familia,tbl_familia_idioma.descricao,data_geracao";
//echo nl2br($sql) . "<br><Br>";exit;

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

//echo pg_numrows($res)." linhas <BR><BR>";

	for ($i=0; $i<pg_numrows($res); $i++){
		$descricao = trim(pg_result($res,$i,descricao));
		$familia   = trim(pg_result($res,$i,familia));
		
//if ($ip=="201.68.13.116") echo "<BR>familia = ".$familia." - descricao = ".$descricao;

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

	for ($i=0; $i<pg_numrows($res); $i++){

		$familia         = trim(pg_result($res,$i,familia));
		$descricao    = trim(pg_result($res,$i,descricao));
		$data_geracao = trim(pg_result($res,$i,data_geracao));
		$qtde         = trim(pg_result($res,$i,qtde));

		$xdata_geracao = explode('-',$data_geracao);
		$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

		if($familia_anterior<>$familia){

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
			#Retirei <a href='relatorio_quebra_produto.php?familia=$familia' class='mes'> //FAbio - HD 17140
			echo "<td bgcolor='$cor' width='150' height='40'>$descricao</td>";

			$total_ano = 0;
			$x=0; //ZERA OS MESES
			$familia_anterior=$familia; 
		}

		while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
			$x=$x+1;
			if($x>12) $x=1;
//			echo $data_geracao.$mes[$x]; echo "<br>";
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

	include "relatorio_quebra_familia_grafico.php";
}else{
	echo "<p>Ningún resultado encuentrado</p>";
}
echo "<br>";
include 'rodape.php';
//
?>