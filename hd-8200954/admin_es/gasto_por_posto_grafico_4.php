<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
include 'autentica_admin.php';

//////////////////////////////////////////
if (1 == 1) {

//	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_bar.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/4_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	//$month = array(1=>"Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez");

	// dados dos 6 meses anteriores
	for($i=5; $i>=0; $i--){
		$sqlX = "SELECT to_char (fn_dias_mes('$ano-$mes-01',0):: date - INTERVAL '$i months', 'YYYY-MM-DD'), 
						to_char (fn_dias_mes('$ano-$mes-01',1):: date - INTERVAL '$i months', 'YYYY-MM-DD')";
		$resX = pg_exec ($con,$sqlX);
		$dia_mes_inicial = pg_result ($resX,0,0) . " 00:00:00";
		$dia_mes_final   = pg_result ($resX,0,1) . " 23:59:59";

		$sql = "SELECT	COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL THEN 1 ELSE NULL END)   AS qtde_os_consumidor,
						COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'R'                                      THEN 1 ELSE NULL END)   AS qtde_os_revenda   
				FROM	tbl_os
				JOIN    tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN    tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto ";
		if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sql .= "JOIN	tbl_os_extra ON tbl_os_extra.os     = tbl_os.os
				JOIN	tbl_extrato  ON tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN    tbl_posto    ON tbl_posto.posto     = tbl_os.posto
				WHERE	tbl_extrato.aprovado BETWEEN '$dia_mes_inicial' AND '$dia_mes_final'
				AND		tbl_os.fabrica = $login_fabrica
				AND     tbl_posto.pais = '$login_pais' ";
		if (strlen($linha) > 0) $sql .= " AND tbl_linha.linha = $linha";
		$res = pg_exec($con,$sql);

		$xqtde_os_consumidor[$i] = pg_result($res,0,qtde_os_consumidor);
		$xqtde_os_revenda[$i]    = pg_result($res,0,qtde_os_revenda);

		// dados para label
		$sqlY = "SELECT to_char ('$ano-$mes-01':: date - INTERVAL '$i months', 'MM'), to_char ('$ano-$mes-01':: date - INTERVAL '$i months', 'YY')";
		$resY = pg_exec ($con,$sqlY);
		$mesY = pg_result($resY,0,0);
		$anoY = pg_result($resY,0,1);
		//$valorLabel[$i] = $month[$mesY]."/".$anoY;
		$valorLabel[$i] = $mesY."/".$anoY;
	}

	// inverte posicao do array do label
	$x = 0;
	reset($valorLabel);
	while (list($key, $val) = each($valorLabel)) {
		$xValorLabel[$x] = $valorLabel[$key];
		$x++;
	}

	$data1x  = array(
				$xqtde_os_consumidor[5],
				$xqtde_os_consumidor[4],
				$xqtde_os_consumidor[3],
				$xqtde_os_consumidor[2],
				$xqtde_os_consumidor[1],
				$xqtde_os_consumidor[0]
			);

	$data2x  = array(
				$xqtde_os_revenda[5],
				$xqtde_os_revenda[4],
				$xqtde_os_revenda[3],
				$xqtde_os_revenda[2],
				$xqtde_os_revenda[1],
				$xqtde_os_revenda[0]
			);

	// A nice graph with anti-aliasing
	$graph = new Graph(350,200,"auto");
	$graph->img->SetMargin(40,80,20,40);

	$graph->img->SetAntiAliasing("white");
	$graph->SetScale("textlin");
	$graph->SetShadow();
	$graph->title->Set("Cantidad de OS para usuários y diostribuidores");
	$graph->xaxis->SetTickLabels($xValorLabel);
	$graph->yaxis->title->Set("");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);

	// Primeira linha
	$bplot1 = new BarPlot($data1x);
	$bplot2 = new BarPlot($data2x);

	$bplot1->SetLegend('Consumidor');
	$bplot2->SetLegend('Distribuidor');

	$bplot1->SetFillColor("#FFD3A4");
	$bplot2->SetFillColor("orange");

	$graph->legend->Pos(0.03,0.11);
	$graph->legend->SetFont(FF_FONT1,FS_NORMAL,6);

	$gbarplot = new GroupBarPlot(array($bplot1,$bplot2));
	$gbarplot->SetWidth(0.6);
	$graph->Add($gbarplot);

	// Output line
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}

?>
