<?


if(strlen($codigo_posto2)>0){

	# 51985 - Francisco Ambrozio
	#   Alterei para pesquisar a partir do dia 1º do mês e incluir o mês atual
	#$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
	$data_inicial = strftime ("%Y-%m-%d", $data_serv);
	
	$xdata_inicial = $data_inicial .' 00:00:00';
	$xdata_final = date("Y-m-d 23:59:59");

	$sql = "SELECT   SUM(coalesce(pecas,0)+coalesce(mao_de_obra,0)+coalesce(avulso,0))        AS total        ,
			 to_char(data_geracao,'YYYY-MM')      AS data_geracao ,
			 tbl_posto.posto                                      ,
			 tbl_posto.nome                       AS posto_nome   ,
			 tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato 
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto 
					AND tbl_posto_fabrica.fabrica = $login_fabrica 
		JOIN tbl_posto          ON tbl_extrato.posto          = tbl_posto.posto 
		WHERE tbl_extrato.fabrica          = $login_fabrica 
		AND tbl_posto_fabrica.codigo_posto = '$codigo_posto2'
		AND tbl_extrato.aprovado IS NOT NULL
			AND to_char(data_geracao,'YYYY-MM') IN (
			SELECT DISTINCT to_char(data_geracao,'YYYY-MM') 
			FROM tbl_extrato 
			JOIN tbl_posto_fabrica using(posto) 
			WHERE codigo_posto     ='$codigo_posto2'
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		)
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		GROUP BY to_char(data_geracao,'YYYY-MM'),
			tbl_posto.posto                 ,
			tbl_posto.nome                  ,
			tbl_posto_fabrica.codigo_posto  
		ORDER BY to_char(data_geracao,'YYYY-MM');";

	#if ($ip == "200.228.76.11") echo $sql;

	$resgrafico = pg_exec($con,$sql);
	
	if (pg_numrows($resgrafico) > 0) {
		$posto           = trim(pg_result($resgrafico,0,posto))       ;
		$posto_nome      = trim(pg_result($resgrafico,0,posto_nome))  ;
		$codigo_posto2    = trim(pg_result($resgrafico,0,codigo_posto));

				
		# HD 68843
		$mes_atual = date("m");
		$ano_atual = date("Y");
		$ano_atual--;

		for($x=0;$x<12;$x++){
			
			if ($mes_atual < 12){
				$mes_atual++;
			}else{
				$mes_atual = 01;
				$ano_atual++;
			}
			
			$mes_atual = sprintf("%02d",$mes_atual);
			
			$mes[$x] = "$mes_atual/$ano_atual";
	
		}

	

		$x=0;
		$y=0;
		//zerando todos arrays
		$posto_total=0;
		$qtde_mes =  array();
	
		$total_mes = 0;
		$total_ano = 0;
	

		$qtde_mes[$posto_total][0]  = 0;
		$qtde_mes[$posto_total][1]  = 0;
		$qtde_mes[$posto_total][2]  = 0;
		$qtde_mes[$posto_total][3]  = 0;
		$qtde_mes[$posto_total][4]  = 0;
		$qtde_mes[$posto_total][5]  = 0;
		$qtde_mes[$posto_total][6]  = 0;
		$qtde_mes[$posto_total][7]  = 0;
		$qtde_mes[$posto_total][8]  = 0;
		$qtde_mes[$posto_total][9]  = 0;
		$qtde_mes[$posto_total][10] = 0;
		$qtde_mes[$posto_total][11] = 0;
		$qtde_mes[$posto_total][12] = $posto_nome;
		$x=0;


		for ($i=0; $i<pg_numrows($resgrafico); $i++){
	
			$posto           = trim(pg_result($resgrafico,$i,posto));
			$data_geracao    = trim(pg_result($resgrafico,$i,data_geracao));
			$total           = trim(pg_result($resgrafico,$i,total));
			
			$xdata_geracao = explode('-',$data_geracao);
			$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

			if($posto_anterior<>$posto){

	//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO PRODUTO
				if($i<>0 ){
	
					for($a=0;$a<12;$a++){			//imprime os doze meses

						if ($qtde_mes[$y][$a]>0)
							echo "<font color='#000000'><b>R$ ".number_format($qtde_mes[$y][$a],2,',','.');
						else echo "<font color='#999999'> ";
	

						$total_ano = $total_ano + $qtde_mes[$y][$a];
						if($a==11) {
							$total_ano = number_format($total_ano,2,',','.');

						}	// se for o ultimo mes quebra a linha
					}
	
					$y=$y+1;						// usado para indicação de produto
				}
	
				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
	
	
	
				$total_ano = 0;
				$x=0; //ZERA OS MESES
				
			}
			
			while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
//				echo "$data_geracao<>".$mes[$x];
				$x=$x+1;
			};
	
			
			if($data_geracao == $mes[$x]){
				$qtde_mes[$y][$x] = $total;
			}
	
			$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
			
			if($i==(pg_numrows($resgrafico)-1)){
				for($a=0;$a<12;$a++){			//imprime os doze meses

					if ($qtde_mes[$y][$a]>0)
						echo "<font color='#000000'>";
					else echo "<font color='#999999'> ";
	
	
					$total_ano = $total_ano + $qtde_mes[$y][$a];
					if($a==11) {
						$total_ano = number_format($total_ano,2,',','.');
					}	// se for o ultimo mes quebra a linha
				}
			
			}
			$posto_anterior=$posto;

	
		}



	/*	for($i=0; $i<$posto_total ; $i++){
			for($j=0; $j<13 ; $j++)echo $qtde_mes[$i][$j]." - ";
		echo "<br><br>";
		}
	*/
	

		flush();

		# 51985
		#$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
		$data_inicial = strftime ("%Y-%m-%d", $data_serv);
		
		$xdata_inicial = $data_inicial .' 00:00:00';
		$xdata_final = date("Y-m-d 23:59:59");
	
		$sql = "SELECT   SUM(coalesce(pecas,0)+coalesce(mao_de_obra,0)+coalesce(avulso,0))                           AS total        ,
			to_char(data_geracao,'YYYY-MM')      AS data_geracao 
		FROM tbl_extrato 
		WHERE tbl_extrato.fabrica          = $login_fabrica 
		AND tbl_extrato.aprovado IS NOT NULL
		AND to_char(data_geracao,'YYYY-MM') IN (
			SELECT DISTINCT to_char(data_geracao,'YYYY-MM') 
			FROM tbl_extrato
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		)
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		GROUP BY to_char(data_geracao,'YYYY-MM')
		ORDER BY to_char(data_geracao,'YYYY-MM');";

#if ($ip == "200.228.76.93") echo "<br><br>".$sql;

		$resgrafico = pg_exec($con,$sql);
		if (pg_numrows($resgrafico) > 0) {
	
			$x=0;
			$y=0;
			//zerando todos arrays
	
			$posto_total2 = 0;
			$qtde_mes2   =  array();
			$qtde_posto2 =  array();
	
			$total_mes2 = 0;
			$total_ano2 = 0;
	
			$qtde_mes2[$posto_total2][0]  = 0;
			$qtde_mes2[$posto_total2][1]  = 0;
			$qtde_mes2[$posto_total2][2]  = 0;
			$qtde_mes2[$posto_total2][3]  = 0;
			$qtde_mes2[$posto_total2][4]  = 0;
			$qtde_mes2[$posto_total2][5]  = 0;
			$qtde_mes2[$posto_total2][6]  = 0;
			$qtde_mes2[$posto_total2][7]  = 0;
			$qtde_mes2[$posto_total2][8]  = 0;
			$qtde_mes2[$posto_total2][9]  = 0;
			$qtde_mes2[$posto_total2][10] = 0;
			$qtde_mes2[$posto_total2][11] = 0;
			$qtde_mes2[$posto_total2][12] = "Média";
	
			$qtde_posto2[$posto_total2][0]  = 0;
			$qtde_posto2[$posto_total2][1]  = 0;
			$qtde_posto2[$posto_total2][2]  = 0;
			$qtde_posto2[$posto_total2][3]  = 0;
			$qtde_posto2[$posto_total2][4]  = 0;
			$qtde_posto2[$posto_total2][5]  = 0;
			$qtde_posto2[$posto_total2][6]  = 0;
			$qtde_posto2[$posto_total2][7]  = 0;
			$qtde_posto2[$posto_total2][8]  = 0;
			$qtde_posto2[$posto_total2][9]  = 0;
			$qtde_posto2[$posto_total2][10] = 0;
			$qtde_posto2[$posto_total2][11] = 0;
			$qtde_posto2[$posto_total2][12] = "Média";
			
			$x = 0;

			for ($i=0; $i<pg_numrows($resgrafico); $i++){
		
				$data_geracao    = trim(pg_result($resgrafico,$i,data_geracao));
				$total           = trim(pg_result($resgrafico,$i,total));

				#HD 93080 adicionei o databarx que é incrementado a data para cada mês
				$databarx[$i] = $data_geracao;
	
				$sql2 = "SELECT  count(*) ,
						posto 
					FROM tbl_extrato 
					WHERE fabrica = $login_fabrica 
					AND  to_char(data_geracao,'YYYY-MM') ='$data_geracao'
					GROUP BY posto;";
	
				$xdata_geracao = explode('-',$data_geracao);
				$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];
	
				$resgrafico2 = pg_exec($con,$sql2);
				if (pg_numrows($resgrafico2) > 0) {
					$postos_digitaram[$i] = pg_numrows($resgrafico2);
					$media_mes[$i] = $total / $postos_digitaram[$i];
					//echo "data: $data_geracao".$postos_digitaram[$i].' = '.$media_mes[$i].'<br>';
				}
	
		
				$cor = '#F7F5F0';
	
				if($i==0){
				}
		
				$total_ano2 = 0;
				$x = 0; //ZERA OS MESES

				while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
					//echo "$data_geracao<>".$mes[$x];
					$x=$x+1;
				};
		
				if($data_geracao == $mes[$x]){
					$qtde_mes2[$y][$x]   = $media_mes[$i];
					$qtde_posto2[$y][$x] = $postos_digitaram[$i];
	
				}
		
				$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
				
				if($i==(pg_numrows($resgrafico)-1)){
					for($a=0;$a<12;$a++){			//imprime os doze meses
						
						if ($qtde_mes2[$y][$a]>0)
							echo "<font color='#000000'>";
						else echo "<font color='#999999'> ";
						$total_ano2 = $total_ano2 + $qtde_mes2[$y][$a];
						if($a==11) {
							$total_ano2 = number_format($total_ano2,2,',','.');
		
							//TOTAL DE POSTOS
		
							for($a=0;$a<12;$a++){
								if($a==0) 
								if ($qtde_mes2[$y][$a]>0)
									echo "";
								else    echo " ";

							}

						}	// se for o ultimo mes quebra a linha
					}
				
				}
			}
		}
		echo "</table><br>";

	include("jpgraph2/jpgraph.php");
	include("jpgraph2/jpgraph_line.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/1_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	// Joga os meses no eixo X



	$data2y = array(
				$valor2y[0]  = 0,
				$valor2y[1]  = 0,
				$valor2y[2]  = 0,
				$valor2y[3]  = 0,
				$valor2y[4]  = 0,
				$valor2y[5]  = 0,
				$valor2y[6]  = 0,
				$valor2y[7]  = 0,
				$valor2y[8]  = 0,
				$valor2y[9]  = 0,
				$valor2y[10] = 0,
				$valor2y[11] = 0
			);

	// A nice graph with anti-aliasing
	$graph = new Graph(700,500,"auto");
	$graph->img->SetMargin(40,100,30,80);
	$graph->img->SetAntiAliasing("white");
	$graph->SetScale("textlin");
	$graph->SetShadow();
	$graph->title->Set("Relatório Anual de Extrato - ".$qtde_mes[0][12]);

//PARA COLOCAR LEGENDA
	$graph->yaxis->HideZeroLabel();
	$graph->legend->Pos(0.02,0.9,"right","bottom");
//PARA COLOCAR COR INTERCALANDO
	$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
	$graph->xgrid->Show();
	//$graph->xaxis->SetTickLabels($gDateLocale->GetShortMonth());

	$graph->xaxis->SetLabelAngle(90);
	//$graph->xaxis->SetTickLabels($xValorLabel);

	#$ HD 93080 foi tirado $xValorLabel e substituido por $databarx para exibir a data de exibição do extrato
	$graph->xaxis->SetTickLabels($databarx);
	$graph->yaxis->title->Set("");
	$graph->xaxis->title->Set("");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	// Primeira linha
	$i=0;
	for($j=0; $j<13; $j++){
		if ($j==12){
			$titulo = "Posto";
		}
		else $valory[$j] = $qtde_mes[$i][$j];
	}

	$data1y  = array(
				$valory[0],
				$valory[1],
				$valory[2],
				$valory[3],
				$valory[4],
				$valory[5],
				$valory[6],
				$valory[7],
				$valory[8],
				$valory[9],
				$valory[10],
				$valory[11]

			);
	// Primeira linha

	$p1 = new LinePlot($data1y);
	$p1->mark->SetType(MARK_UTRIANGLE);
	// se precisar exibir o valor descomentar abaixo
	//$p1->value->show();

	$p1->mark->SetFillColor("blue");
	$p1->mark->SetWidth(2);



	$p1->value->SetFont(FF_FONT1,FS_BOLD);
	
	$p1->SetColor("blue");
	$p1->SetCenter();
	$p1->SetLegend($titulo);
	$p1->value->SetFormat('%0.0f');

     //$p1 -> xgrid-> Show (true, true); 
	$graph->Add($p1);




	// Primeira linha
	$i=0;
	for($j=0; $j<13; $j++){
		if ($j==12){
			$titulo = $qtde_mes2[$i][$j];
		}
		else $valory[$j] = $qtde_mes2[$i][$j];
	}

	$data1y  = array(
				$valory[0],
				$valory[1],
				$valory[2],
				$valory[3],
				$valory[4],
				$valory[5],
				$valory[6],
				$valory[7],
				$valory[8],
				$valory[9],
				$valory[10],
				$valory[11]
			);
	// Primeira linha



	$p2 = new LinePlot($data1y);
	$p2->mark->SetType(MARK_FILLEDCIRCLE);
	$p2->mark->SetFillColor("orange");
	$p2->mark->SetWidth(2);
	//$p2->value->show();
	$p2->value->SetFont(FF_FONT1,FS_BOLD);
	$p2->SetColor("orange");
	$p2->SetCenter();
	$p2->SetLegend($titulo);
	$p2->value->SetFormat('%0.0f');
	$graph->Add($p2);
	




	 //Segunda linha
/*	$p2 = new LinePlot($data2y);
red
orange
gray
*/
	// Output line

	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph' height='300' width='600'>\n\n";

//////////////////////////////////////////




	}else{
		echo "Nenhum extrato durante este período";
	}
}



?>
