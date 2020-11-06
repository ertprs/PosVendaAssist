<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "Relatório de Prazos de Atendimento";

//////////////////////////////////////////
if (1 == 1) {
	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_bar.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	$sql = "SELECT  nv_5  ,
					nv_10 ,
					nv_15 ,
					nv_20 ,
					nv_25 ,
					nv_30 ,
					sp_5  ,
					sp_10 ,
					sp_15 ,
					sp_20 ,
					sp_25 ,
					sp_30 ,
					pne_5 ,
					pne_10,
					pne_15,
					pne_20,
					pne_25,
					pne_30,
					nf_5  ,
					nf_10 ,
					nf_15 ,
					nf_20 ,
					nf_25 ,
					nf_30
			FROM    tbl_relatorio_prazo_atendimento
			WHERE   tbl_relatorio_prazo_atendimento.fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);
//echo $sql; exit;
	if (pg_numrows($res) > 0) {
		$nv_5  = trim(pg_result($res,0,nv_5));
		$nv_10 = trim(pg_result($res,0,nv_10));
		$nv_15 = trim(pg_result($res,0,nv_15));
		$nv_20 = trim(pg_result($res,0,nv_20));
		$nv_25 = trim(pg_result($res,0,nv_25));
		$nv_30 = trim(pg_result($res,0,nv_30));
		
		$sp_5  = trim(pg_result($res,0,sp_5));
		$sp_10 = trim(pg_result($res,0,sp_10));
		$sp_15 = trim(pg_result($res,0,sp_15));
		$sp_20 = trim(pg_result($res,0,sp_20));
		$sp_25 = trim(pg_result($res,0,sp_25));
		$sp_30 = trim(pg_result($res,0,sp_30));
		
		$pne_5  = trim(pg_result($res,0,pne_5));
		$pne_10 = trim(pg_result($res,0,pne_10));
		$pne_15 = trim(pg_result($res,0,pne_15));
		$pne_20 = trim(pg_result($res,0,pne_20));
		$pne_25 = trim(pg_result($res,0,pne_25));
		$pne_30 = trim(pg_result($res,0,pne_30));
		
		$nf_5  = trim(pg_result($res,0,nf_5));
		$nf_10 = trim(pg_result($res,0,nf_10));
		$nf_15 = trim(pg_result($res,0,nf_15));
		$nf_20 = trim(pg_result($res,0,nf_20));
		$nf_25 = trim(pg_result($res,0,nf_25));
		$nf_30 = trim(pg_result($res,0,nf_30));
	}else{
		$nv_5  = 0;
		$nv_10 = 0;
		$nv_15 = 0;
		$nv_20 = 0;
		$nv_25 = 0;
		$nv_30 = 0;
		
		$sp_5  = 0;
		$sp_10 = 0;
		$sp_15 = 0;
		$sp_20 = 0;
		$sp_25 = 0;
		$sp_30 = 0;
		
		$pne_5  = 0;
		$pne_10 = 0;
		$pne_15 = 0;
		$pne_20 = 0;
		$pne_25 = 0;
		$pne_30 = 0;
		
		$nf_5  = 0;
		$nf_10 = 0;
		$nf_15 = 0;
		$nf_20 = 0;
		$nf_25 = 0;
		$nf_30 = 0;
	}
	
	$data1y=array($nv_5,$nv_10,$nv_15,$nv_20,$nv_25,$nv_30);
	$data2y=array($sp_5,$sp_10,$sp_15,$sp_20,$sp_25,$sp_30);
	$data3y=array($pne_5,$pne_10,$pne_15,$pne_20,$pne_25,$pne_30);
	$data4y=array($nf_5,$nf_10,$nf_15,$nf_20,$nf_25,$nf_30);
	
	// Create the graph. These two calls are always required
	$graph = new Graph(700,400,"auto");
	$graph->SetScale("textlin");
	
	$graph->legend->Pos(0.02,0.02);
	
	// Create targets for the image maps. One for each column
	$graph->SetShadow();
	$graph->img->SetMargin(70,185,40,40);
	
	// Create the bar plots
	$b1plot = new BarPlot($data1y);
	$b1plot->SetFillColor("#FF0000");
	$b1plot->SetLegend('OS´s não vistas');
	$targ = array(
				"relatorio_prazo_atendimento_periodo.php?status=0&dia=5",
				"relatorio_prazo_atendimento_periodo.php?status=0&dia=10",
				"relatorio_prazo_atendimento_periodo.php?status=0&dia=15",
				"relatorio_prazo_atendimento_periodo.php?status=0&dia=20",
				"relatorio_prazo_atendimento_periodo.php?status=0&dia=25",
				"relatorio_prazo_atendimento_periodo.php?status=0&dia=30"
			);
	$alts = array(
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d"
			);
	$b1plot->SetCSIMTargets($targ,$alts);

	$b2plot = new BarPlot($data2y);
	$b2plot->SetFillColor("#66CCFF");
	$b2plot->SetLegend('OS´s sem peças');
	$targ = array(
				"relatorio_prazo_atendimento_periodo.php?status=1&dia=5",
				"relatorio_prazo_atendimento_periodo.php?status=1&dia=10",
				"relatorio_prazo_atendimento_periodo.php?status=1&dia=15",
				"relatorio_prazo_atendimento_periodo.php?status=1&dia=20",
				"relatorio_prazo_atendimento_periodo.php?status=1&dia=25",
				"relatorio_prazo_atendimento_periodo.php?status=1&dia=30"
			);
	$alts = array(
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d"
			);
	$b2plot->SetCSIMTargets($targ,$alts);
	
	$b3plot = new BarPlot($data3y);
	$b3plot->SetFillColor("#339900");
	$b3plot->SetLegend('OS´s sem peças enviadas');
	$targ = array(
				"relatorio_prazo_atendimento_periodo.php?status=2&dia=5",
				"relatorio_prazo_atendimento_periodo.php?status=2&dia=10",
				"relatorio_prazo_atendimento_periodo.php?status=2&dia=15",
				"relatorio_prazo_atendimento_periodo.php?status=2&dia=20",
				"relatorio_prazo_atendimento_periodo.php?status=2&dia=25",
				"relatorio_prazo_atendimento_periodo.php?status=2&dia=30"
			);
	$alts = array(
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d"
			);
	$b3plot->SetCSIMTargets($targ,$alts);
	
	$b4plot = new BarPlot($data4y);
	$b4plot->SetFillColor("#FFFF33");
	$b4plot->SetLegend('OS´s não finalizadas');
	$targ = array(
				"relatorio_prazo_atendimento_periodo.php?status=3&dia=5",
				"relatorio_prazo_atendimento_periodo.php?status=3&dia=10",
				"relatorio_prazo_atendimento_periodo.php?status=3&dia=15",
				"relatorio_prazo_atendimento_periodo.php?status=3&dia=20",
				"relatorio_prazo_atendimento_periodo.php?status=3&dia=25",
				"relatorio_prazo_atendimento_periodo.php?status=3&dia=30"
			);
	$alts = array(
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d",
				"qtde OS´s %d"
			);
	$b4plot->SetCSIMTargets($targ,$alts);
	
	// Create the grouped bar plot
	$gbplot = new AccBarPlot(array($b1plot,$b2plot,$b3plot,$b4plot));
	
	$gbplot->SetShadow();
	$gbplot->value->Show();
	
	// ...and add it to the graPH
	$graph->Add($gbplot);
	
	$graph->title->Set("Relatório de Prazos de Atendimento");
	$graph->xaxis->SetTickLabels(array(5,10,15,20,25,30));
	$graph->xaxis->title->Set("Períodos/dias");
	$graph->yaxis->SetTitleMargin(50);
	$graph->yaxis->title->Set("Ordens de Serviços");
	
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
	
	$graph->StrokeCSIM('imagem_relatorio.php');
}
//////////////////////////////////////////
?>