<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
include 'autentica_admin.php';

//////////////////////////////////////////
if (1 == 1) {

//	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_pie.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/3_$img.png";
	
	// seleciona os dados das m�dias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	// dados dos 6 meses anteriores
	$sqlX = "SELECT to_char (fn_dias_mes('$ano-$mes-01',1):: date, 'YYYY-MM-DD')                      ,
					to_char (fn_dias_mes('$ano-$mes-01',0):: date - INTERVAL '6 months', 'YYYY-MM-DD')";
	$resX = pg_exec ($con,$sqlX);
	$dia_mes_inicial = pg_result ($resX,0,1) . " 00:00:00";
	$dia_mes_final   = pg_result ($resX,0,0) . " 23:59:59";

	$sql = "SELECT  COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL THEN 1 ELSE NULL END)   AS qtde_os_consumidor,
					COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'R' THEN 1 ELSE NULL END)   AS qtde_os_revenda
			FROM    tbl_os
			JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto ";
	if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
	$sql .= "JOIN    tbl_os_extra ON tbl_os_extra.os     = tbl_os.os
			JOIN     tbl_extrato  ON tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN     tbl_posto    ON tbl_posto.posto     = tbl_os.posto 
			WHERE   tbl_extrato.aprovado BETWEEN '$dia_mes_inicial' AND '$dia_mes_final'
			AND     tbl_os.fabrica = $login_fabrica 
			AND     tbl_posto.pais = '$login_pais'";
	if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha";
//echo $sql;

	$sql = "SELECT  COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL THEN 1 ELSE NULL END)   AS qtde_os_consumidor,
					COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'R' THEN 1 ELSE NULL END)   AS qtde_os_revenda
			FROM    tbl_os
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

	$qtde_os_consumidor = pg_result($res,0,qtde_os_consumidor);
	$qtde_os_revenda    = pg_result($res,0,qtde_os_revenda);

	$total = $qtde_os_revenda + $qtde_os_consumidor;

	if ($total > 0){
		$porc_os_consumidor = ($qtde_os_consumidor / $total ) * 100;
		$porc_os_revenda    = ($qtde_os_revenda    / $total ) * 100;

		$data = array(
					$porc_os_consumidor,
					$porc_os_revenda
				);

		// Create the graph. These two calls are always required
		$graph = new PieGraph(350,200,"auto");
		$graph->SetShadow();

		// Set a title for the plot
		$graph->title->Set("Promedio de los �ltimos 6 meses");
		$graph->title->SetFont(FF_FONT1,FS_BOLD);

		// Create
		$p1 = new PiePlot($data);
		$p1->SetCenter(0.35,0.50);
		$p1->SetLegends(array(" Consumidor"," Distribuidor"));

		$graph->Add($p1);

		$graph->Stroke($image_graph);
		echo "\n\n<img src='$image_graph'>\n\n";

	}

//////////////////////////////////////////
}
?>
