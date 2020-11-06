<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,auditoria";
include 'autentica_admin.php';

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_line.php");

$ydata = array(11,3,8,12,5,1,9,13,5,7,0);

// Create the graph. These two calls are always required
$graph = new Graph(600,400,"auto");    
$graph->SetScale("textlin");

// Create the linear plot
$lineplot=new LinePlot($ydata);


// Add the plot to the graph
$graph->Add($lineplot);


$ydata2 = array(1 ,19,15, 7,22,14 ,5,9, 21,13,2);
$lineplot2 =new LinePlot($ydata2);
$lineplot2 ->SetColor("green");
$lineplot2 ->SetWeight(2);

$graph->Add( $lineplot2);

$ydata3 = array(10 ,3,5, 6,19,12 ,9,12, 16,7,6);
$lineplot3 =new LinePlot($ydata3);
$lineplot3 ->SetColor("black");
$lineplot3 ->SetWeight(2);

$graph->Add($lineplot3);

$lineplot  ->SetLegend("Plot 1");
$lineplot2 ->SetLegend("Plot 2");
$lineplot3 ->SetLegend("Plot 3");


$graph->legend->SetLayout(LEGEND_HOR);
$graph->legend->Pos(0.4,0.95,"center","bottom");

$graph->img->SetMargin(40,20,20,70);
$graph->title->Set("Example 3");
$graph->xaxis->title->Set("X-title");
$graph->yaxis->title->Set("Y-title");

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$lineplot->SetColor("blue");
$lineplot->SetWeight(2);
$graph->yaxis->SetColor("red");
$graph->yaxis->SetWeight(2);
$graph->SetShadow();

// Display the graph
$graph->Stroke();
?>