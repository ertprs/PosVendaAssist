<?
//include 'dbconfig.php';
//include 'includes/dbconnect-inc.php';

//$admin_privilegios = "auditoria";
//include 'autentica_admin.php';

//////////////////////////////////////////
if (1 == 1) {
	include("jpgraph/jpgraph.php");
	include("jpgraph/jpgraph_line.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/1_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	$month = array(1=>"Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez");

	// dados dos 6 meses anteriores
	for($i=5; $i>=0; $i--){
		$sqlX = "SELECT to_char (fn_dias_mes('$ano-$mes-01',0):: date - INTERVAL '$i months', 'YYYY-MM-DD'), 
						to_char (fn_dias_mes('$ano-$mes-01',1):: date - INTERVAL '$i months', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_mes_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_mes_final   = pg_result ($resX,0,1) . " 23:59:59";

		$sql = "SELECT	CASE WHEN COUNT  (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)        END AS qtde ,
						CASE WHEN SUM    (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM (tbl_os.custo_peca)  END AS pecas,
						CASE WHEN STDDEV (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM (tbl_os.mao_de_obra) END AS mo
				FROM	tbl_os
				JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto ";
		if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sql .="JOIN	tbl_os_extra ON tbl_os_extra.os     = tbl_os.os
				JOIN	tbl_extrato  ON tbl_extrato.extrato = tbl_os_extra.extrato
				WHERE	tbl_extrato.aprovado BETWEEN '$dia_mes_inicial' AND '$dia_mes_final'
				AND		tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha";
#echo $sql;
		$res = pg_exec($con,$sql);

		$qtde  = pg_result($res,0,qtde);
		$pecas = pg_result($res,0,pecas);
		$mo    = pg_result($res,0,mo);
		
		if ($qtde > 0) $valory[$i] = ($pecas + $mo) / $qtde;

		// dados para label
		$sqlY = "SELECT to_char ('$ano-$mes-01':: date - INTERVAL '$i months', 'MM'), to_char ('$ano-$mes-01':: date - INTERVAL '$i months', 'YY')";
		$resY = pg_exec ($con,$sqlY);
		$mesY = intval(pg_result($resY,0,0));
		$anoY = pg_result($resY,0,1);
		$valorLabel[$i] = $month[$mesY]."/".$anoY;
	}

	// inverte posicao do array do label
	$x = 0;
	reset($valorLabel);
	while (list($key, $val) = each($valorLabel)) {
		$xValorLabel[$x] = $valorLabel[$key];
		$x++;
	}

	$datay  = array(
				$valory[5],
				$valory[4],
				$valory[3],
				$valory[2],
				$valory[1],
				$valory[0]
			);
/*
	$data2y = array(
				$valor2y[0],
				$valor2y[1],
				$valor2y[2],
				$valor2y[3],
				$valor2y[4],
				$valor2y[5]
			);
*/
	// A nice graph with anti-aliasing
	$graph = new Graph(350,200,"auto");
	$graph->img->SetMargin(40,20,20,40);

	$graph->img->SetAntiAliasing("white");
	$graph->SetScale("textlin");
	$graph->SetShadow();
	$graph->title->Set("Média de custo por OS / mes");
	$graph->xaxis->SetTickLabels($xValorLabel);
	$graph->yaxis->title->Set("Custos");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	// Primeira linha
	$p1 = new LinePlot($datay);
	$p1->mark->SetType(MARK_FILLEDCIRCLE);
	$p1->mark->SetFillColor("red");
	$p1->mark->SetWidth(4);
	$p1->value->show();
	$p1->value->SetFont(FF_FONT1,FS_BOLD);
	$p1->SetColor("blue");
	$p1->SetCenter();
	$p1->value->SetFormat('$%0.2f');
	$graph->Add($p1);

/*
	// Segunda linha
	$p2 = new LinePlot($data2y);
	$p2->mark->SetType(MARK_STAR);
	$p2->mark->SetFillColor("red");
	$p2->mark->SetWidth(4);
	$p2->SetColor("red");
	$p2->SetCenter();
	$p2->value->SetFormat('$%0.1f');
	$graph->Add($p2);
*/
	// Output line

	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
?>