<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_bar.php");


$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];


$sql_2 = "select nome_comercial, count(*) from tbl_callcenter join tbl_produto using(produto) where data between '$data_inicial 00:00:00' and '$data_final 23:59:59' group by nome_comercial;";

//$sql_2 = "SELECT distinct nome_comercial, sum(qtde) AS produto_qtde from temp_callcenter_6 where nome_comercial is not null  group by nome_comercial; ";


$res_2 = pg_exec($con,$sql_2);
//echo "$sql_2<br><br>";

$qtdeProduto = array ();
$nomeComercial = array();

for($i=0;$i<pg_numrows($res_2);$i++){
	$nome_comercial2 = pg_result($res_2,$i,0);
	$qtde2           = pg_result($res_2,$i,1);
	array_push($nomeComercial,$nome_comercial2);
	array_push($qtdeProduto,$qtde2);
}

//print_r($qtdeProduto);

$grafico = new graph(550,350,"png");

// margem das partes principais do gráfico (dados), o que está
// fora da margem fica separado para as labels, títulos, etc
$grafico->img->SetMargin(40,20,20,140);

$grafico->SetScale("textlin");
$grafico->SetShadow();

$grafico->title->Set('Quantidade de chamados abertos');
// definir subtitulo
$grafico->subtitle->Set('Total por produtos');

// pedir para mostrar os grides no fundo do gráfico,
// o ygrid é marcado coom true por padrão
$grafico->ygrid->Show(true);
$grafico->xgrid->Show(true);

$gBarras = new BarPlot($qtdeProduto);
$gBarras->SetFillColor("orange");
$gBarras->SetShadow("darkblue");

// t&#65533;tulo dos vértices
$grafico->yaxis->title->Set("Qtde chamados");
//$grafico->xaxis->title->Set("Produtos");
// título das barras
$grafico->xaxis->SetTickLabels($nomeComercial);

$grafico->xaxis->SetLabelAngle(90);

//$grafico->title->SetFont(FF_TIMES,FS_BOLD);
//$grafico->yaxis->title->SetFont(FF_TIMES,FS_BOLD);
//$grafico->xaxis->title->SetFont(FF_TIMES,FS_BOLD);



$grafico->Add($gBarras);
$grafico->Stroke();

//include "imagem_relatorio_callcenter.php";






?>