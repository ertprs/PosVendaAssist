<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_pie.php");
include ("../jpgraph/jpgraph_pie3d.php");

include ("funcoes.php");


//pegar valores das paginas
$total_geral = $_GET['total'];
$resp_dada = $_GET['resp_dada'];
$pendentes = $_GET['pendentes'];

$total = $total_geral - ($resp_dada + $pendentes);

if($resp_dada > 0 AND $pendentes > 0){
	$data = array("$total","$resp_dada","$pendentes");
}elseif($resp_dada > 0 AND $pendentes == 0){
	$data = array("$total","$resp_dada");
}elseif($resp_dada == 0 AND $pendentes > 0){
	$data = array("$total","$pendentes");
}elseif($resp_dada == 0 AND $pendentes == 0){
	$data = array("$total");
}


$graph = new PieGraph(500,400,"auto");
$graph->SetShadow();

$graph->title->Set("ATENDIMENTOS SAC");
$graph->subtitle->Set("OCORRENCIAS ABERTAS NO SISTEMA DO SAC");

$graph->title->SetFont(FF_FONT1,FS_BOLD);

$p1 = new PiePlot3D($data);
$p1->SetSize(0.40);
$p1->SetCenter(0.45);
$p1->SetStartAngle(290);

if($resp_dada > 0 AND $pendentes > 0){
	$array = array("Resolvidos","Sem Resp. 3 dias","Pendentes");
}elseif($resp_dada > 0 AND $pendentes == 0){
	$array = array("Resolvidos", "Sem Resp. 3 dias");
}elseif($resp_dada == 0 AND $pendentes > 0){
	$array = array("Resolvidos","Pendentes");
}elseif($resp_dada == 0 AND $pendentes == 0){
	$array = array("Resolvidos");
}

$p1->SetLabelType(PIE_VALUE_ABS);

$p1->SetLegends($array);

$p1->value->SetFormat('%d');

if($resp_dada > 0 AND $pendentes > 0){
	$p1->SetSliceColors(array('blue','red','green'));
}elseif($resp_dada > 0 AND $pendentes == 0){
	$p1->SetSliceColors(array('blue','red'));
}elseif($pendentes > 0 AND $resp_dada == 0){
	$p1->SetSliceColors(array('blue','green'));
}elseif($pendentes > 0 AND $resp_dada == 0){
	$p1->SetSliceColors(array('green'));
}

$p1->ExplodeAll();

$graph->Add($p1);
$graph->Stroke();


?>