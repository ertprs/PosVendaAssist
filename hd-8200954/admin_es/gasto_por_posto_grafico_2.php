<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
include 'autentica_admin.php';

//////////////////////////////////////////
if (1 == 1) {

//	include ("../jpgraph/jpgraph.php");
//	include ("../jpgraph/jpgraph_line.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/2_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	$month = array(1=>"Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dec");

	// dados dos 6 meses anteriores
	for($i=5; $i>=0; $i--){
		$sqlX = "SELECT to_char (fn_dias_mes('$ano-$mes-01',0):: date - INTERVAL '$i months', 'YYYY-MM-DD'), 
						to_char (fn_dias_mes('$ano-$mes-01',1):: date - INTERVAL '$i months', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_mes_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_mes_final   = pg_result ($resX,0,1) . " 23:59:59";

		$sql = "SELECT	COUNT(tbl_os.*)                                                         AS total        ,
						COUNT(CASE WHEN tbl_os.defeito_constatado NOTNULL THEN 1 ELSE NULL END) AS total_defeito
				FROM	tbl_os
				JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto 
				JOIN    tbl_posto      ON tbl_posto.posto     = tbl_os.posto ";
		if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sql .= "WHERE	tbl_os.fabrica = $login_fabrica
				AND     tbl_posto.pais = '$login_pais' 
				AND		tbl_os.data_digitacao BETWEEN '$dia_mes_inicial' AND '$dia_mes_final' ";
		if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha";

		$sql = "SELECT	COUNT(tbl_os.*)                                                         AS total,
						COUNT(CASE WHEN tbl_os.defeito_constatado NOTNULL THEN 1 ELSE NULL END) AS total_defeito
				FROM	tbl_os
				JOIN (
					SELECT os 
					FROM tbl_os_extra 
					JOIN tbl_extrato USING(extrato) 
					JOIN tbl_posto   USING(posto)
					".$sql_linha."
					WHERE tbl_extrato.aprovado BETWEEN '$dia_mes_inicial' AND '$dia_mes_final'
					AND tbl_extrato.fabrica = $login_fabrica
					AND tbl_posto.pais      = '$login_pais'
					".$sql_linha_cond."
				) oss ON oss.os = tbl_os.os";

		$res = pg_exec($con,$sql);
		$total         = pg_result($res,0,total);
		$total_defeito = pg_result($res,0,total_defeito);

		if ($total > 0)
			$valory[$i] = round(($total_defeito / $total ) * 100);
		else
			$valory[$i] = 0;

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
	$graph->img->SetMargin(40,20,30,40);

	$graph->img->SetAntiAliasing("white");
	$graph->SetScale("textlin");
	$graph->SetShadow();
	$graph->title->Set("% de OS con cambio");
	$graph->xaxis->SetTickLabels($xValorLabel);
	$graph->yaxis->title->Set("Porcentaje");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	// Primeira linha
	$p1 = new LinePlot($datay);
	$p1->mark->SetType(MARK_FILLEDCIRCLE);
	$p1->mark->SetFillColor("red");
	$p1->mark->SetWidth(4);
	$p1->value->show();
	$p1->value->SetFont(FF_FONT1,FS_BOLD);
	$p1->SetColor("blue");
	$p1->value->SetFormat('%0.1f%%');
	$p1->SetCenter();
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
