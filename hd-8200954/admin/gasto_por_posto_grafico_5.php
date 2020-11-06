<?
//include 'dbconfig.php';
//include 'includes/dbconnect-inc.php';

//$admin_privilegios = "auditoria";
//include 'autentica_admin.php';

//////////////////////////////////////////
if (1 == 1) {

//	include ("../jpgraph/jpgraph.php");
//	include ("../jpgraph/jpgraph_bar.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/5_$img.png";
	
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

		$sql = "SELECT  COUNT(CASE WHEN length (trim (consumidor_fone)) = 0 THEN 1 ELSE NULL      END) AS total_sem_fone,
						COUNT(CASE WHEN tbl_os.os IS NULL                   THEN 0 ELSE tbl_os.os END) AS total
				FROM    tbl_posto
				JOIN    tbl_os            ON tbl_os.posto        = tbl_posto.posto
				JOIN    tbl_produto       ON tbl_produto.produto = tbl_os.produto ";
		if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sql .= "JOIN   tbl_os_extra      ON tbl_os_extra.os     = tbl_os.os
				JOIN    tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN    tbl_posto_fabrica ON tbl_posto.posto     = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
				WHERE   tbl_extrato.aprovado BETWEEN '$dia_mes_inicial' AND '$dia_mes_final'
				AND     tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha";
		$res = pg_exec($con,$sql);

		$total          = pg_result($res,0,total);
		$total_sem_fone = pg_result($res,0,total_sem_fone);

		if ($total > 0)
			$valory[$i] = round(($total_sem_fone / $total ) * 100);
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

	// Create the graph. These two calls are always required
	$graph = new Graph(700,200,"auto");
	$graph->SetScale("textlin");
	$graph->yaxis->scale->SetGrace(20);
	$graph->SetShadow();
	$graph->img->SetMargin(40,30,20,40);

	// Create a bar pot
	$bplot = new BarPlot($datay);

	// Adjust fill color
	$bplot->SetFillColor('orange@0.4');
	$bplot->SetShadow();
	$bplot->value->Show();
	$bplot->value->SetFont(FF_ARIAL,FS_BOLD,10);
	$bplot->value->SetFormat('%0.1f%%');
	$graph->Add($bplot);

	// Setup the titles
	$graph->title->Set("Média geral de OS sem telefone do cliente");
	$graph->xaxis->SetTickLabels($xValorLabel);
	$graph->yaxis->title->Set("Porcentagem");

	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
//	$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

	$graph->Stroke($image_graph);

	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
?>