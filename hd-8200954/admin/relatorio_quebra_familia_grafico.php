<?
//////////////////////////////////////////
if ($familia_total<10) {
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
				$valor2y[4]  = 10,
				$valor2y[5]  = 0,
				$valor2y[6]  = 15,
				$valor2y[7]  = 0,
				$valor2y[8]  = 0,
				$valor2y[9]  = 0,
				$valor2y[10] = 0,
				$valor2y[11] = 0
			);

	// A nice graph with anti-aliasing
	$graph = new Graph(700,700,"auto");
	$graph->img->SetMargin(40,200,20,50);
	$graph->img->SetAntiAliasing("white");
	$graph->SetScale("textlin");
	$graph->SetShadow();
	$graph->title->Set("Relatório de Quantidade de Quebra");

//PARA COLOCAR LEGENDA
	$graph->yaxis->HideZeroLabel();
	$graph->legend->Pos(0.05,0.9,"right","bottom");
//PARA COLOCAR COR INTERCALANDO
	$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
	$graph->xgrid->Show();
	$graph->xaxis->SetTickLabels($gDateLocale->GetShortMonth());

	$graph->xaxis->SetTickLabels($xValorLabel);
	$graph->yaxis->title->Set("Qtde");
	$graph->xaxis->title->Set("Meses");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	// Primeira linha

	
	for($i=0; $i<$familia_total ; $i++){
		for($j=0; $j<13; $j++)
			if ($j==12){
				$titulo = $qtde_mes[$i][$j];
			}
			else $valory[$j] = $qtde_mes[$i][$j];
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
if($i==0){
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
		$graph->Add($p1);
}
if($i==1){
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
}
if($i==2){
		$p3 = new LinePlot($data1y);
		$p3->mark->SetType(MARK_FILLEDCIRCLE);
		$p3->mark->SetFillColor("pink");
		$p3->mark->SetWidth(2);
		//$p3->value->show();
		$p3->value->SetFont(FF_FONT1,FS_BOLD);
		$p3->SetColor("pink");
		$p3->SetCenter();
		$p3->SetLegend($titulo);
		$p3->value->SetFormat('%0.0f');
		$graph->Add($p3);
}
if($i==3){
		$p4 = new LinePlot($data1y);
		$p4->mark->SetType(MARK_FILLEDCIRCLE);
		$p4->mark->SetFillColor("purple");
		$p4->mark->SetWidth(2);
		//$p4->value->show();
		$p4->value->SetFont(FF_FONT1,FS_BOLD);
		$p4->SetColor("purple");
		$p4->SetCenter();
		$p4->SetLegend($titulo);
		$p4->value->SetFormat('%0.0f');
		$graph->Add($p4);
}
if($i==4){
		$p5 = new LinePlot($data1y);
		$p5->mark->SetType(MARK_FILLEDCIRCLE);
		$p5->mark->SetFillColor("brown");
		$p5->mark->SetWidth(2);
		//$p5->value->show();
		$p5->value->SetFont(FF_FONT1,FS_BOLD);
		$p5->SetColor("brown");
		$p5->SetCenter();
		$p5->SetLegend($titulo);
		$p5->value->SetFormat('%0.0f');
		$graph->Add($p5);
}
if($i==5){
		$p6 = new LinePlot($data1y);
		$p6->mark->SetType(MARK_FILLEDCIRCLE);
		$p6->mark->SetFillColor("green");
		$p6->mark->SetWidth(2);
		//$p6->value->show();
		$p6->value->SetFont(FF_FONT1,FS_BOLD);
		$p6->SetColor("green");
		$p6->SetCenter();
		$p6->SetLegend($titulo);
		$p6->value->SetFormat('%0.0f');
		$graph->Add($p6);
}
if($i==6){
		$p7 = new LinePlot($data1y);
		$p7->mark->SetType(MARK_FILLEDCIRCLE);
		$p7->mark->SetFillColor("black");
		$p7->mark->SetWidth(2);
		//$p6->value->show();
		$p7->value->SetFont(FF_FONT1,FS_BOLD);
		$p7->SetColor("black");
		$p7->SetCenter();
		$p7->SetLegend($titulo);
		$p7->value->SetFormat('%0.0f');
		$graph->Add($p7);
}
if($i==7){
		$p8 = new LinePlot($data1y);
		$p8->mark->SetType(MARK_FILLEDCIRCLE);
		$p8->mark->SetFillColor("blue");
		$p8->mark->SetWidth(2);
		//$p1->value->show();
		$p8->value->SetFont(FF_FONT1,FS_BOLD);
		$p8->SetColor("blue");
		$p8->SetCenter();
		$p8->SetLegend($titulo);
		$p8->value->SetFormat('%0.0f');
		$graph->Add($p8);
}
if($i==8){
		$p9 = new LinePlot($data1y);
		$p9->mark->SetType(MARK_FILLEDCIRCLE);
		$p9->mark->SetFillColor("orange");
		$p9->mark->SetWidth(2);
		//$p2->value->show();
		$p9->value->SetFont(FF_FONT1,FS_BOLD);
		$p9->SetColor("orange");
		$p9->SetCenter();
		$p9->SetLegend($titulo);
		$p9->value->SetFormat('%0.0f');
		$graph->Add($p9);
}

	}

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
}
?>