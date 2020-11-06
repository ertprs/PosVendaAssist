<?





if (1 == 1) {


	// nome da imagem
	$img = time();
	$image_graph = "png/29_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');

	$legenda_atendimento_rapido = array("Sim","Não");

	$graph = new PieGraph(300,150,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("O atendimento foi rápido?");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	$p1 = new PiePlot3D($atendimento_rapido);
	$p1->SetSize(0.5);
	$p1->SetCenter(0.45);
	$p1->SetLegends($legenda_atendimento_rapido);
	
	$graph->Add($p1);
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
//echo "<BR>";
if (1 == 1) {


	// nome da imagem
	$img = time();
	$image_graph = "png/30_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');

	$legenda_confianca = array("Sim","Não");

	$graph = new PieGraph(300,150,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("O aspecto da loja, gerou confiança?");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	$p1 = new PiePlot3D($confianca);
	$p1->SetSize(0.5);
	$p1->SetCenter(0.45);
	$p1->SetLegends($legenda_confianca);
	
	$graph->Add($p1);
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
echo "<BR>";
if (1 == 1) {


	// nome da imagem
	$img = time();
	$image_graph = "png/31_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');

	$legenda_problema_resolvido = array("Sim","Não");

	$graph = new PieGraph(300,150,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("O problema foi resolvido?");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	$p1 = new PiePlot3D($problema_resolvido);
	$p1->SetSize(0.5);
	$p1->SetCenter(0.45);
	$p1->SetLegends($legenda_problema_resolvido);
	
	$graph->Add($p1);
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
?>