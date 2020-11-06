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
			COUNT(*)
		FROM tbl_callcenter 
		LEFT JOIN tbl_produto using(produto)
	WHERE fabrica = 6 ";

if($anual> 0)
	$sql_2 .= " AND data BETWEEN '$anual-01-01 00:00:00'  AND '$anual-12-31 23:59:59' ";
else
	$sql_2 .= " AND data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";

$sql_2 .= " AND ((tbl_callcenter.produto not in('8059','8042','11159','1027','1042','1039','1056','1043','8040','1064','7494')) OR (tbl_callcenter.produto is null))
			AND excluida IS NOT TRUE
			GROUP BY nome_comercial;";

//echo "$sql_2";

$res_2 = pg_exec($con,$sql_2);
//echo "$sql_2<br><br>";

$qtdeProduto = array ();
$nomeComercial = array();

for($i=0;$i<pg_numrows($res_2);$i++){
	$nome_comercial2 = pg_result($res_2,$i,0);
	$qtde2           = pg_result($res_2,$i,1);
	if(trim($nome_comercial2) == "OUTROS") {
		$qtde3 = $qtde2+$qtde3;
	}else{
		array_push($nomeComercial,$nome_comercial2);
		array_push($qtdeProduto,$qtde2);
	}
}
array_push($nomeComercial,"OUTROS");
array_push($qtdeProduto,$qtde3);

$grafico = new graph(650,350,"png");

// margem das partes principais do gráfico (dados), o que está
// fora da margem fica separado para as labels, títulos, etc
$grafico->img->SetMargin(40,20,20,140);

$grafico->SetScale("textlin");
$grafico->SetShadow();

$grafico->title->Set('Quantidade de chamados abertos');
// definir subtitulo
$grafico->subtitle->Set("$sub_titulo");

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

$grafico->Add($gBarras);
$grafico->StrokeCSIM("imagem_relatorio_callcenter.php");


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

$sql_8 = "SELECT  tbl_callcenter.natureza                                      ,
				tbl_produto.nome_comercial                                   ,
				tbl_produto.produto                                          ,
				tbl_defeito_reclamado.descricao         AS defeito_descricao ,
				tbl_defeito_reclamado.defeito_reclamado AS defeito_reclamado ,
				count(*)                                AS qtde              
		INTO TEMP TABLE temp_callcenter_6
		FROM    tbl_callcenter
		LEFT JOIN tbl_produto           ON tbl_callcenter.produto           = tbl_produto.produto
		LEFT JOIN tbl_defeito_reclamado ON tbl_callcenter.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
		WHERE     tbl_callcenter.fabrica = 6
		AND tbl_callcenter.excluida IS NOT TRUE ";

//		AND       tbl_produto.produto NOT IN ('8059','8042','11159','1027','1042','1039','1056')

if($anual > "0"){
	$sql_8 .= " AND       tbl_callcenter.data BETWEEN '$anual-01-01 00:00:00'  AND '$anual-12-31 23:59:59' ";
}else{
	$sql_8 .= " AND       tbl_callcenter.data BETWEEN '$data_inicial 00:00:00'  AND '$data_final 23:59:59' ";
}
$sql_8 .= " GROUP BY  tbl_callcenter.natureza, tbl_defeito_reclamado.descricao, tbl_produto.nome_comercial, tbl_produto.produto, tbl_defeito_reclamado.defeito_reclamado
				ORDER BY  tbl_callcenter.natureza, tbl_defeito_reclamado.descricao, tbl_produto.nome_comercial; ";
$res = pg_exec ($con,$sql_8);

//echo "<br>".$sql_8."<br>";



/*
$sql = "UPDATE temp_callcenter_6 SET defeito_descricao = temp_callcenter_6.natureza WHERE defeito_descricao IS NULL; ";
$resX = pg_exec ($con,$sql);

$sql = "UPDATE temp_callcenter_6 SET defeito_descricao = temp_callcenter_6.natureza WHERE LENGTH (TRIM (defeito_descricao)) = 0;";
$resX = pg_exec ($con,$sql);
*/
$sql = "UPDATE temp_callcenter_6 SET nome_comercial = 'FORA DE LINHA' WHERE produto in('8059','8042','11159','1027','1042','1039','1056','8040','1043','1064','7494');";
$resX = pg_exec($con,$sql);

$sql = "UPDATE temp_callcenter_6 SET nome_comercial = 'OUTROS' WHERE (nome_comercial = '' OR nome_comercial is null);";
$resX = pg_exec($con,$sql);
/*
$sql = "UPDATE temp_callcenter_6 SET nome_comercial = 'XXX' WHERE nome_comercial is null; ";
$resX = pg_exec($con,$sql);
*/


echo "<table><tr><td>";

echo "<table border= '1' width='300' style='font-size: 10px;'>";
echo "<tr style='font-size: 14px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td colspan='3' align='center'><b>GERAL POR PRODUTOS</b></td>";
echo "</tr>";
echo "<tr style='font-size: 12px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td><b>Produto</b></td>";
	echo "<td><b>Total</b></td>";
	echo "<td><b>%</b></td>";
echo "</tr>";


$sql2 = "SELECT SUM(qtde) AS total_callcenter FROM temp_callcenter_6; ";
$res2 = pg_exec($con,$sql2);
$total_callcenter = pg_result($res2,0,0);

$total_geral          = 0;
$total_geral_porcento = 0;

$sql2 = "SELECT distinct nome_comercial, sum(qtde) AS total_produto FROM temp_callcenter_6 group by nome_comercial order by nome_comercial; ";
$res2 = pg_exec($con,$sql2);
if(pg_numrows($res2) > 0) {
	for($i=0;$i<pg_numrows($res2);$i++){
		$total_produto    = pg_result($res2,$i,total_produto);
		$nome_comercial   = pg_result($res2,$i,nome_comercial);
		$porcento         = (($total_produto * 100) / $total_callcenter);
		echo "<tr style='font-size: 12px'>";
			if($nome_comercial == 'FORA DE LINHA' OR $nome_comercial == 'OUTROS') echo "<td bgcolor='#D5DAE1'><b>$nome_comercial</b></td>";
			else echo "<td bgcolor='#D5DAE1'>$nome_comercial</td>";
			echo "<td align='right'>$total_produto</td>";
			echo "<td align='right'>". round($porcento,2) ."%</td>";
		echo "</tr>";
		$total_geral          = $total_geral + $total_produto;
		$total_geral_porcento = $total_geral_porcento + $porcento;
	}
}

echo "<tr style='font-size: 14px' bgcolor='#6699FF' style='color:#ffffff ; font-weight:bold ;'>";
	echo "<td>TOTAL</td>";
	echo "<td>$total_geral</td>";
	echo "<td>$total_geral_porcento%</td>";
echo "</tr>";

echo "</table>";
echo "</td><td valign='top'>";

echo "<table border= '1' width='300' style='font-size: 10px;'>";
echo "<tr style='font-size: 14px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td colspan='3' align='center'><b>OUTROS</b></td>";
echo "</tr>";
echo "<tr style='font-size: 12px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td><b>Descrição</b></td>";
	echo "<td><b>Total</b></td>";
	echo "<td><b>%</b></td>";
echo "</tr>";

$sql2 = "SELECT sum(qtde) AS total_callcenter FROM temp_callcenter_6 WHERE nome_comercial = 'OUTROS'; ";
$res2 = pg_exec($con,$sql2);
$total_callcenter = pg_result($res2,0,0);

$total_geral          = 0;
$total_geral_porcento = 0;

$sql2 = "SELECT distinct natureza, sum(qtde) AS total_produto FROM temp_callcenter_6 WHERE nome_comercial = 'OUTROS' GROUP BY natureza ORDER BY natureza; ";
$res2 = pg_exec($con,$sql2);
if(pg_numrows($res2) > 0) {
	for($i=0;$i<pg_numrows($res2);$i++){
		$total_produto    = pg_result($res2,$i,total_produto);
		$nome_comercial   = pg_result($res2,$i,natureza);
		$porcento         = (($total_produto * 100) / $total_callcenter);
		echo "<tr style='font-size: 12px'>";
			echo "<td bgcolor='#D5DAE1'>$nome_comercial</td>";
			echo "<td align='right'>$total_produto</td>";
			echo "<td align='right'>". round($porcento,2) ."%</td>";
		echo "</tr>";
		$total_geral          = $total_geral + $total_produto;
		$total_geral_porcento = $total_geral_porcento + $porcento;
	}
}
echo "<tr style='font-size: 14px' bgcolor='#6699FF' style='color:#ffffff ; font-weight:bold ;'>";
	echo "<td>TOTAL</td>";
	echo "<td>$total_geral</td>";
	echo "<td>$total_geral_porcento%</td>";
echo "</tr>";

echo "</table>";
echo "</td></tr></table>";

/*

for($i=0;$i<pg_numrows($res);$i++){
	$nome_comercial[$i] = pg_result($res,$i,nome_comercial);
	$produto_qtde[$i]   = pg_result($res,$i,produto_qtde);

	if($nome_comercial[$i] == "XXX") $produto_qtde[$i] = $produto_qtde[$i] + $total_xxx2;
	$porcento = ($produto_qtde[$i] * 100) / $total_callcenter;

	echo "<tr style='font-size: 12px'>";
		if($nome_comercial[$i] == "XXX") echo "<td bgcolor='#D5DAE1'>OUTROS</td>";
		else echo "<td bgcolor='#D5DAE1'>$nome_comercial[$i]</td>";

		echo "<td align='right'>$produto_qtde[$i]</td>";
		echo "<td align='right'>". round($porcento,2) ."%</td>";
	echo "</tr>";
	$porcento_total     = $porcento + $porcento_total;
	$produto_qtde_total = $produto_qtde[$i] + $produto_qtde_total;
}



$sql = "SELECT sum(qtde) AS total_callcenter from temp_callcenter_6 ";
$res = pg_exec($con,$sql);
$total_callcenter = pg_result($res,0,0);

$sql = "SELECT distinct nome_comercial, sum(qtde) AS produto_qtde from temp_callcenter_6 WHERE nome_comercial <> 'XXX2' group by nome_comercial ";
$res = pg_exec($con,$sql);
$total_grafico = pg_numrows($res);

$sql2 = "SELECT sum(qtde) AS total_callcenter FROM temp_callcenter_6 WHERE nome_comercial = 'XXX2'";
$res2 = pg_exec($con,$sql2);
if(pg_numrows($res2) > 0) {
	$total_xxx2 = pg_result($res2,0,0);
	$total_callcenter = $total_xxx2 + $total_callcenter;
}


for($i=0;$i<pg_numrows($res);$i++){
	$nome_comercial[$i] = pg_result($res,$i,nome_comercial);
	$produto_qtde[$i]   = pg_result($res,$i,produto_qtde);

	if($nome_comercial[$i] == "XXX") $produto_qtde[$i] = $produto_qtde[$i] + $total_xxx2;
	$porcento = ($produto_qtde[$i] * 100) / $total_callcenter;

	echo "<tr style='font-size: 12px'>";
		if($nome_comercial[$i] == "XXX") echo "<td bgcolor='#D5DAE1'>OUTROS</td>";
		else echo "<td bgcolor='#D5DAE1'>$nome_comercial[$i]</td>";

		echo "<td align='right'>$produto_qtde[$i]</td>";
		echo "<td align='right'>". round($porcento,2) ."%</td>";
	echo "</tr>";
	$porcento_total     = $porcento + $porcento_total;
	$produto_qtde_total = $produto_qtde[$i] + $produto_qtde_total;
}

$sql2 = "SELECT sum(qtde) AS total_callcenter FROM temp_callcenter_6 WHERE nome_comercial = 'XXX2'";
$res2 = pg_exec($con,$sql2);
if(pg_numrows($res2) > 0) {
	$total_xxx2 = pg_result($res2,0,0);
	$total_outros = $total_xxx2 + $total_outros;
}

if($total_xxx2 > 0){
	$porcento = ($total_xxx2 * 100) / $total_callcenter;
	echo "<tr style='font-size: 12px'>";
		echo "<td bgcolor='#D5DAE1'>PRODUTOS FORA DE LINHA</td>";
		echo "<td align='right'>$total_xxx2</td>";
		echo "<td align='right'>". round($porcento,2) ."%</td>";
	echo "</tr>";
	$porcento_total     = $porcento + $porcento_total;
	$defeito_qtde_total = $total_xxx2 + $defeito_qtde_total;
}


///
if($total_xxx2 > 0){
	$porcento = ($total_xxx2 * 100) / $total_callcenter;
	echo "<tr style='font-size: 12px'>";
		echo "<td bgcolor='#D5DAE1'>PRODUTOS FORA DE LINHA</td>";
		echo "<td align='right'>$total_xxx2</td>";
		echo "<td align='right'>". round($porcento,2) ."%</td>";
	echo "</tr>";
	$porcento_total     = $porcento + $porcento_total;
	$defeito_qtde_total = $total_xxx2 + $defeito_qtde_total;
}
///
echo "<tr style='font-size: 14px' bgcolor='#6699FF' style='color:#ffffff ; font-weight:bold ;'>";
	echo "<td>TOTAL</td>";
	echo "<td>$produto_qtde_total</td>";
	echo "<td>$porcento_total%</td>";
echo "</tr>";
echo "</table>";

echo "</td>";
echo "<td valign='top'>";
//outras reclamções sem produto.

echo "<br><table border= '1' style='font-size: 10px;'>";
echo "<tr style='font-size: 14px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td colspan='3' align='center'><b>OUTROS</b></td>";
echo "</tr>";
echo "<tr style='font-size: 12px' bgcolor='#ddeeFF' style='font-size:10px ; font-color:#ffffff'>";
	echo "<td><b>Descrição</b></td>";
	echo "<td><b>Total</b></td>";
	echo "<td><b>%</b></td>";
echo "</tr>";


$sql = "SELECT sum(qtde) AS total_callcenter from temp_callcenter_6 WHERE nome_comercial = 'XXX'  ";
$res = pg_exec($con,$sql);
$total_outros = pg_result($res,0,0);

$sql = "SELECT distinct defeito_descricao, sum(qtde) AS produto_qtde from temp_callcenter_6 WHERE nome_comercial = 'XXX' and defeito_descricao <> '' group by defeito_descricao ";
$res = pg_exec($con,$sql);
$total_callcenter = pg_numrows($res);


$porcento_total = 0;
$porcento = 0;
$defeito_qtde_total = 0;

for($i=0;$i<pg_numrows($res);$i++){
	$defeito_descricao[$i] = strtoupper(pg_result($res,$i,defeito_descricao));
	$defeito_qtde[$i]   = pg_result($res,$i,produto_qtde);
	$porcento = ($defeito_qtde[$i] * 100) / $total_outros;
	
	$defeito_descricao[$i] = strtoupper(RemoveAcentos($defeito_descricao[$i]));
	//if($defeito_descricao[$i] == 'ENGANO' OR $defeito_descricao[$i] == 'OUTRAS AREAS' OR $defeito_descricao[$i] == 'SUGESTÃO'){
		echo "<tr style='font-size: 12px'>";
		echo "<td>$defeito_descricao[$i]</td>";
		echo "<td align='right'>$defeito_qtde[$i]</td>";
		echo "<td align='right'>". round($porcento,2) ."%</td>";
		echo "</tr>";
		$porcento_total     = $porcento + $porcento_total;
		$defeito_qtde_total = $defeito_qtde[$i] + $defeito_qtde_total;
	//}
}

echo "<tr style='font-size: 14px' bgcolor='#6699FF' style='color:#ffffff ; font-weight:bold ;'>";
	echo "<td>TOTAL</td>";
	echo "<td>$defeito_qtde_total</td>";
	echo "<td>$porcento_total%</td>";
echo "</tr>";
echo "</table>";

echo "</td>";
echo "</tr>";
echo "</table>";


*/
?>