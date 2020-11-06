<?php
include ("../jpgraph.php");
include ("../jpgraph_pie.php");

// Some data
$data1 = array(60,40);
$data2 = array(10,90);
$data3 = array(50,50);

// Create the Pie Graph.
$graph = new PieGraph(750,300,"auto");
$graph->SetShadow();

// Set A title for the plot
$graph->title->Set("Postos em relação a média da OS");
$graph->title->SetFont(FF_FONT1,FS_BOLD);

// Create plots
$size=0.18;
$p1 = new PiePlot($data1);
$p1->SetLegends(array(" Acima da média"," De acordo / Abaixo"));
$p1->SetSize($size);
$p1->SetCenter(0.10,0.50);
$p1->value->SetFont(FF_FONT0);
$p1->title->Set("Abertura - Pedido\nMédia 15,1");

$p2 = new PiePlot($data2);
$p2->SetSize($size);
$p2->SetCenter(0.35,0.50);
$p2->value->SetFont(FF_FONT0);
$p2->title->Set("Pedido - Faturamento\nMédia 10,3");

$p3 = new PiePlot($data3);
$p3->SetSize($size);
$p3->SetCenter(0.60,0.50);
$p3->value->SetFont(FF_FONT0);
$p3->title->Set("Faturamento - Fechamento\nMédia 18,0");

$graph->Add($p1);
$graph->Add($p2);
$graph->Add($p3);

$graph->Stroke();

?>
