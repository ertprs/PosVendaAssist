<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO DE QUEBRA: PRODUTO NOS ÚLTIMOS 12 MESES";


include "cabecalho_new.php";

$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
$data_inicial = strftime ("%Y-%m-%d", $data_serv);

$xdata_inicial = $data_inicial .' 00:00:00';
$xdata_final = date("Y-m-d 23:59:59");

if (strlen($_GET['familia']) > 0){
	$familia = trim($_GET['familia']);
}

//--==== Otimização para rodar o relatório Anual =============================================

for($x=0;$x<12;$x++){
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12 +$x  , date("d"), date("Y"));
	$xMES = strftime ("%m/%Y", $data_serv);
}

flush();
for($x=0;$x<12;$x++){
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12 +$x  , date("d"), date("Y"));
	$data_inicial = strftime ("%Y-%m-01", $data_serv);
	$xdata_inicial = $data_inicial .' 00:00:00';

	$sql = "SELECT ('$data_inicial'::DATE + INTERVAL'1 MONTH'- INTERVAL'1 day')::DATE || ' 23:59:59';";
	$res = pg_exec($con,$sql);
	$xdata_final = pg_result($res,0,0);

	if ($x == 0) { ?>
		<script> 
			$("#loading").show();
            $("#loading-block").show();
            $("#loading_action").val("t");
		</script>
	<?	
	}

	$aux = $login_admin;
	if($x==0){
	$sql = "
		SELECT tbl_os_extra.os , tbl_extrato.data_geracao
		INTO TEMP tmp_rqp_$aux
		FROM tbl_os_extra
		JOIN tbl_extrato USING (extrato)
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		AND   tbl_extrato.liberado IS NOT NULL;
	
		CREATE INDEX tmp_rqp_OS_$aux ON tmp_rqp_$aux(os);";
	}else{
		$sql = "INSERT INTO tmp_rqp_$aux (os,data_geracao) 
			SELECT tbl_os_extra.os , tbl_extrato.data_geracao
			FROM tbl_os_extra
			JOIN tbl_extrato USING (extrato)
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
			AND   tbl_extrato.liberado IS NOT NULL;";
	}
	//die(nl2br($sql));	
	$res = pg_exec($con,$sql);
	if ($x == 11) { ?>
		<script>
			$("#loading").hide();
            $("#loading-block").hide();
            $("#loading_action").val("f");	
		</script>
	<? } 

	flush();
}
//--========================================================================================

$sql = "SELECT tbl_produto.descricao,tbl_produto.produto,to_char(fcr.data_geracao,'YYYY-MM')As data_geracao, COUNT(*) AS qtde
	FROM  tbl_os
	JOIN  tmp_rqp_$aux         fcr ON tbl_os.os = fcr.os
	JOIN  tbl_produto              ON tbl_produto.produto = tbl_os.produto
	WHERE tbl_os.excluida IS NOT TRUE
	AND   tbl_produto.familia = $familia
	GROUP BY tbl_produto.produto,tbl_produto.descricao, to_char(data_geracao,'YYYY-MM')
	ORDER BY tbl_produto.descricao,tbl_produto.produto,data_geracao";
//die(nl2br($sql));
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$sql2= "SELECT  
					tbl_familia.descricao AS familia_descricao   
			FROM    tbl_familia 
			WHERE    tbl_familia.familia = $familia";

	//die(nl2br($sql2));			

	$res2 = pg_exec($con,$sql2);
	$familia_descricao         = trim(pg_result($res2,0,familia_descricao));

	echo "</div><table style='width: 80%;position: relative;left: 5%;' class='table table-bordered table-striped'>";
	echo "<thead><tr class='titulo_tabela'>";
	echo "<th colspan='16' class='tac'>$familia_descricao </th>";
	echo "</tr>";
	echo "<tr class='titulo_coluna'>";
	echo "<th>Produto</th>";
	echo "<th>Posto</th>";
	echo "<th>Peça</th>";
	for($x=0;$x<12;$x++){

		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")- 12 + $x , date("d"), date("Y"));
		$mes[$x] = strftime ("%m/%Y", $data_serv);
		
		echo "<th class='Mes'>$mes[$x]</th>";
	}
	echo "<th>Total Ano</th>";
	echo "</tr></thead>";

	$familia=0;
	$x=0;
	$y=0;
	//zerando todos arrays
	$produtos_total=0;
	$qtde_mes =  array();

	$total_mes = 0;
	$total_ano = 0;

	$contadorRES = pg_numrows($res);

	for ($i=0; $i<$contadorRES; $i++){
		$descricao         = trim(pg_result($res,$i,descricao));
		$produto           = trim(pg_result($res,$i,produto));		
		if($produto_anterior<>$produto){
			$qtde_mes[$produtos_total][0]  = 0;
			$qtde_mes[$produtos_total][1]  = 0;
			$qtde_mes[$produtos_total][2]  = 0;
			$qtde_mes[$produtos_total][3]  = 0;
			$qtde_mes[$produtos_total][4]  = 0;
			$qtde_mes[$produtos_total][5]  = 0;
			$qtde_mes[$produtos_total][6]  = 0;
			$qtde_mes[$produtos_total][7]  = 0;
			$qtde_mes[$produtos_total][8]  = 0;
			$qtde_mes[$produtos_total][9]  = 0;
			$qtde_mes[$produtos_total][10] = 0;
			$qtde_mes[$produtos_total][11] = 0;
			$qtde_mes[$produtos_total][12] = $descricao;
			$x=0;
			$produto_anterior=$produto;
			$produtos_total = $produtos_total+1;
		}
	}
	for ($i=0; $i<$contadorRES; $i++){

		$produto         = trim(pg_result($res,$i,produto));
		$descricao       = trim(pg_result($res,$i,descricao));
		$data_geracao = trim(pg_result($res,$i,data_geracao));
		$qtde            = trim(pg_result($res,$i,qtde));

		$xdata_geracao = explode('-',$data_geracao);
		$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		if($produto_anterior<>$produto){
			//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO PRODUTO

			if($i<>0 AND $produto_anterior<>$produto){

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

				$y=$y+1;						// usado para indicação de produto
			} 

			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' width='150'  height = '40' class='Mes'>", $descricao, "</td>";
			echo "<td bgcolor='$cor' width='150'  height = '40'><a href='relatorio_quebra_posto.php?produto=$produto' class='Mes'>Posto</a></td>";
			echo "<td bgcolor='$cor' width='150'  height = '40'><a href='relatorio_quebra_peca.php?produto=$produto' class='Mes'>Peça</a></td>";

			$total_ano = 0;
			$x=0; //ZERA OS MESES
			$produto_anterior=$produto; 
		}elseif($produto_anterior==$produto AND $i==0){
						echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' width='150'  height = '40' class='Mes'>", $descricao, "</td>";
			echo "<td bgcolor='$cor' width='150'  height = '40'><a href='relatorio_quebra_posto.php?produto=$produto' class='Mes'>Posto</a></td>";
			echo "<td bgcolor='$cor' width='150'  height = '40'><a href='relatorio_quebra_peca.php?produto=$produto' class='Mes'>Peça</a></td>";
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
/*	for($i=0; $i<$produtos_total ; $i++){
		for($j=0; $j<13 ; $j++)echo $qtde_mes[$i][$j]." - ";
	echo "<br><br>";
	}
*/
}
echo "<a href='javascript:history.back()'>Voltar</a>";
echo "<br>";
include 'rodape.php';

?>