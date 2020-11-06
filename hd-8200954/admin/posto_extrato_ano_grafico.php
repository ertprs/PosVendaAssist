<?
//////////////////////////////////////////

	include("jpgraph/jpgraph.php");
	include("jpgraph/jpgraph_line.php");
	
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
	$graph->img->SetMargin(40,100,30,50);
	$graph->img->SetAntiAliasing("white");
	$graph->SetScale("textlin");
	$graph->SetShadow();
	$graph->title->Set(traduz("Relatório Anual de Extrato - ").$qtde_mes[0][12]);

//PARA COLOCAR LEGENDA
	$graph->yaxis->HideZeroLabel();
	$graph->legend->Pos(0.02,0.9,"right","bottom");
//PARA COLOCAR COR INTERCALANDO
	$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
	$graph->xgrid->Show();
	$graph->xaxis->SetTickLabels($gDateLocale->GetShortMonth());

	$graph->xaxis->SetTickLabels($xValorLabel);
	$graph->yaxis->title->Set("R$");
	$graph->xaxis->title->Set(traduz("Meses"));
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
	$p1->mark->SetType(MARK_FILLEDCIRCLE);
	$p1->mark->SetFillColor("blue");
	$p1->mark->SetWidth(2);
	//$p1->value->show();
	$p1->value->SetFont(FF_FONT1,FS_BOLD);
	$p1->SetColor("blue");
	$p1->SetCenter();
	$p1->SetLegend($titulo);
	$p1->value->SetFormat('%0.0f');
	$graph->xaxis->SetTickLabels($mes);
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
	$graph->xaxis->SetTickLabels($mes);
	$graph->Add($p2);
	




	// Segunda linha
/*	$p2 = new LinePlot($data2y);
red
orange
gray
*/
	// Output line

	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////

?>