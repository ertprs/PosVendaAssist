<?php
require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_bar.php");
include ("funcoes.php");

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$anual        = $_GET["anual"];

if($anual> 0){
	$sub_titulo = "Busca por ano: $anual";
}else{
	$sub_titulo = "Busca por datas: ". mostra_data($data_inicial) ." até ". mostra_data($data_final) ." ";
}

$sql_2 = "SELECT CASE WHEN nome_comercial IS NULL OR TRIM(nome_comercial) = '' OR LENGTH(trim(nome_comercial)) = 0 
				THEN 'OUTROS' 
				ELSE nome_comercial
			END AS produto,
			CASE 
			WHEN natureza = 'Ocorrência' 
				THEN 'Reclamação' 
				ELSE natureza 
			END as natureza2, 
			COUNT(*)AS total 
		FROM tbl_callcenter 
		JOIN tbl_produto using(produto)
	WHERE fabrica = 6 ";

if($anual> 0)
	$sql_2 .= " AND data BETWEEN '$anual-01-01 00:00:00'  AND '$anual-12-31 23:59:59' ";
else
	$sql_2 .= " AND data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";

$sql_2 .= " AND ((tbl_callcenter.produto not in('8059','8042','11159','1027','1042','1039','1056','1043','8040','1064','7494')) OR (tbl_callcenter.produto is null))
			AND excluida IS NOT TRUE
			AND (natureza = 'Informação' OR natureza = 'Reclamação' OR natureza = 'Ocorrência')
			GROUP BY nome_comercial, natureza2 order by nome_comercial;";


//echo "$sql_2";

$res_2 = pg_exec($con,$sql_2);
//echo "$sql_2<br><br>";

$qtdeNatureza1 = array ();
$qtdeNatureza2 = array ();
$nomeComercial = array();
$contador = 0;

for($i=0;$i<pg_numrows($res_2);$i++){
	$nome_comercial2 = pg_result($res_2,$i,produto);
	$qtde2           = pg_result($res_2,$i,total);
	$natureza        = pg_result($res_2,$i,natureza2);

	if($nc2 <> $nome_comercial2) {
		if ($contador==0){
			if($natureza == 'Informação') {
				array_push($qtdeNatureza1,$qtde2);
			}else{
				array_push($qtdeNatureza2,$qtde2);
			}
			$contador =1;
			$nc2 = $nome_comercial2;
			array_push($nomeComercial,$nc2);
			$natureza_ant = $natureza;
		}else{
			if($contador ==1){
				if($natureza_ant == 'Informação') {
					array_push($qtdeNatureza2,0);
				}else{
					array_push($qtdeNatureza1,0);
				}
				$contador =0;
				$i--;
			}
		}
	}else{
		if($natureza == 'Informação') {
			array_push($qtdeNatureza1,$qtde2);
		}else{
			array_push($qtdeNatureza2,$qtde2);
		}
		$contador =0;
	}
}

$data1y=array(12,8,19,3,10,5);
$data2y=array(8,2,11,7,14,4);

// Create the graph. These two calls are always required
$graph = new Graph(650,350,"png");
$graph->SetScale("textlin");

$graph->SetShadow();
$graph->img->SetMargin(40,30,20,140);

// Create the bar plots
$b1plot = new BarPlot($qtdeNatureza1);
$b1plot->SetFillColor("orange");
$b1plot->SetLegend("Informação");
$b2plot = new BarPlot($qtdeNatureza2);
$b2plot->SetFillColor("blue");
$b2plot->SetLegend("Reclamação");

// Create the grouped bar plot
$gbplot = new GroupBarPlot(array($b1plot,$b2plot));

// ...and add it to the graPH
$graph->Add($gbplot);

$graph->title->Set("Informação X Reclamação");
// título das barras
$graph->xaxis->SetTickLabels($nomeComercial);
//$graph->xaxis->title->Set("X-title");
//$graph->yaxis->title->Set("Y-title");

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->SetLabelAngle(90);
// Display the graph

$graph->StrokeCSIM("imagem_relatorio_callcenter2.php");


function RemoveAcentos($Msg) 
{
  $a = array( 
            '/[ÂÀÁÄÃ]/'=>'A', 
            '/[âãàáä]/'=>'a', 
            '/[ÊÈÉË]/'=>'E', 
            '/[êèéë]/'=>'e', 
            '/[ÎÍÌÏ]/'=>'I', 
            '/[îíìï]/'=>'i', 
            '/[ÔÕÒÓÖ]/'=>'O', 
            '/[ôõòóö]/'=>'o', 
            '/[ÛÙÚÜ]/'=>'U', 
            '/[ûúùü]/'=>'u', 
            '/ç/'=>'c', 
            '/Ç/'=>'C'); 
    // Tira o acento pela chave do array                         
    return preg_replace(array_keys($a), array_values($a), $Msg); 
}


if (strlen($_GET["btn_acao"]) > 0) $btn_acao = $_GET["btn_acao"];
$anual = $_GET["anual"];


echo "<table width='600' border='0'>";
echo "<tr>";

echo "<td>";
echo "<br><table border= '1' style='font-size: 10px;'>";
echo "<tr style='font-size: 14px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td colspan='3' align='center'><b>GERAL POR PRODUTOS</b></td>";
echo "</tr>";
echo "<tr style='font-size: 12px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td><b>Produto</b></td>";
	echo "<td><b>Informação</b></td>";
	echo "<td><b>Reclamação</b></td>";
echo "</tr>";
$sql_2 = "SELECT CASE WHEN nome_comercial IS NULL OR TRIM(nome_comercial) = '' OR LENGTH(trim(nome_comercial)) = 0 
				THEN 'OUTROS' 
				ELSE nome_comercial
			END AS produto,
			CASE 
			WHEN natureza = 'Ocorrência' 
				THEN 'Reclamação' 
				ELSE natureza 
			END as natureza2, 
			COUNT(*)AS total 
		FROM tbl_callcenter 
		JOIN tbl_produto using(produto)
	WHERE fabrica = 6 ";

if($anual> 0)
	$sql_2 .= " AND data BETWEEN '$anual-01-01 00:00:00'  AND '$anual-12-31 23:59:59' ";
else
	$sql_2 .= " AND data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";

$sql_2 .= " AND ((tbl_callcenter.produto not in('8059','8042','11159','1027','1042','1039','1056','1043','8040','1064','7494')) OR (tbl_callcenter.produto is null))
			AND excluida IS NOT TRUE
			AND (natureza = 'Informação' OR natureza = 'Reclamação' OR natureza = 'Ocorrência')
			GROUP BY nome_comercial, natureza2 order by nome_comercial;";

$j='0';

$res_2 = pg_exec($con,$sql_2);

for($i=0;$i<pg_numrows($res_2);$i++){
	$nome_comercial[$j] = pg_result($res_2,$i,produto);
	$qtde2           = @pg_result($res_2,$i,total);
	$natureza        = @pg_result($res_2,$i,natureza2);


//Falta imprimir o total quando for null ou zero.

	if($nome_comercial[$j] == $nc){
		if($natureza == 'Informação') {
			$qtde_informacao2[$j] = $qtde2 ;
		}else{
			$qtde_reclamacao2[$j] = $qtde2;
		}
	}else{
		$nc = $nome_comercial[$j];
		if($natureza == 'Informação') {
			$qtde_informacao2[$j] = $qtde2 ;
		}else{
			$qtde_reclamacao2[$j] = $qtde2;
		}
	}
	$j++;
}
$nc = '';

$controle = 1;
for($i=0;$i<pg_numrows($res_2);$i++){

	if($nome_comercial[$i] <> $nc){
		echo "<tr style='font-size: 12px'>";
		echo "<td bgcolor='#D5DAE1'>$nome_comercial[$i]</td>";
		echo "<td>";
		$nc = $nome_comercial[$i];

		if($qtde_reclamacao2[$i] > 0){
			echo "$qtde_reclamacao2[$i]";
		}else{
			echo "$qtde_informacao2[$i]";
		}
		echo "</td>";
		$controle++;
	}else{
		echo "<td>";
		if($qtde_reclamacao2[$i] > 0){
			echo "$qtde_reclamacao2[$i]";
		}else{
			echo "$qtde_informacao2[$i]";
		}
		echo "</td>";
		echo "</tr>";
		if($i > 1 AND $controle == 1){
			echo "<td>-</td>";
		}
		$controle = 1;
	}
}
//echo "$sql_2";
//exit;

echo "<tr style='font-size: 12px' bgcolor='#D5DAE1' style='color:#ffffff ; font-weight:bold ;'>";
	echo "<td style='color: #330000'><b>TOTAL</b></td>";
	echo "<td><b>$produto_qtde_total</b></td>";
	echo "<td style='color: #330000'><b>$porcento_total%</b></td>";
echo "</tr>";
echo "</table>";

echo "</td>";
echo "</tr>";
echo "</table>";



?>