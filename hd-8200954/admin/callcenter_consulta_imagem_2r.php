<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_bar.php");
include ("funcoes.php");

$mesmo_dia         = $_GET['mesmo_dia'];
$dia1              = $_GET['dia1'];
$dias2             = $_GET['dias2'];
$dias3             = $_GET['dias3'];
$mais_dias         = $_GET['mais_dias'];
$total_ocorrencias = $_GET['total_ocorrencias'];

//echo "$mesmo_dia - $dia1 - $dias2 - $dias3 - $mais_dias";

$data  = array("$mesmo_dia","$dia1","$dias2","$dias3","$mais_dias");
$array = array("Mesmo dia ($mesmo_dia)","1 dia ($dia1) ","2 dias ($dias2)","3 dias ($dias3)","Mais de 3 dias ($mais_dias)");

$grafico = new graph(500,350,"png");

//$sub_titulo = $total_ocorrencias;

// margem das partes principais do gráfico (dados), o que está
// fora da margem fica separado para as labels, títulos, etc
$grafico->img->SetMargin(40,20,20,140);

$grafico->SetScale("textlin");
$grafico->SetShadow();

$grafico->title->Set("Total de Ocorrencias registradas: $total_ocorrencias");
// definir subtitulo
$grafico->subtitle->Set("$sub_titulo");

// pedir para mostrar os grides no fundo do gráfico,
// o ygrid é marcado coom true por padrão
$grafico->ygrid->Show(true);
$grafico->xgrid->Show(true);

$gBarras = new BarPlot($data);
$gBarras->SetFillColor("orange");
$gBarras->SetShadow("darkblue");

// t&#65533;tulo dos vértices
$grafico->yaxis->title->Set("Qtde chamados");
//$grafico->xaxis->title->Set("Produtos");
// título das barras
$grafico->xaxis->SetTickLabels($array);

$grafico->xaxis->SetLabelAngle(90);

$grafico->Add($gBarras);
$grafico->Stroke();
?>