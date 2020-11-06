<?


//////////////////////////////////////////
if (1 == 1) {

	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_pie.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/3_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	

	for ($x = 0; $x < pg_numrows($res); $x++) {
			$total = $total + pg_result($res,$x,ocorrencia);
		}
	$n_ocorrencia_anterior =0;
	for ($x = 0; $x < pg_numrows($res); $x++) {

		$y = pg_result($res,$x,ocorrencia);
		$p_ocorrencia = ( $y/ $total ) * 100;

		if ($x==0) {
			$ocorrencia = pg_result($res,$x,ocorrencia);
			
			$descricao  = substr(pg_result($res,$x,descricao), 0, 35);
			$porc_ocorrencia = $p_ocorrencia;
		}
		elseif ($x>=8){
			$fim          = pg_numrows($res) -1;
			$n_ocorrencia = pg_result($res,$x,ocorrencia);

			$n_ocorrencia = $n_ocorrencia + $n_ocorrencia_anterior;
//echo "Atual: $n_ocorrencia - Anterior: $n_ocorrencia_anterior <br>";
			$n_ocorrencia_anterior = $n_ocorrencia;
			
			
			if($x ==$fim){
				$p_ocorrencia = ( $n_ocorrencia/ $total ) * 100;
				$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;
				$descricao       = $descricao.', Outros';
//echo "<br><br>Total".$n_ocorrencia;
			}
		}
		else {

			$n_descricao  = substr(pg_result($res,$x,descricao), 0, 35);

			$ocorrencia   = $ocorrencia.','.$n_ocorrencia;

			$descricao  = $descricao.','.$n_descricao;

			$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;

		}
	}
//if ($ip="200.158.65.19") {echo $ocorrencia;} 
	if ($total > 0){

		$data = explode(",",$porc_ocorrencia);

		// Create the graph. These two calls are always required
		$graph = new PieGraph(700,500,"auto");
		$graph->SetShadow();

		// Set a title for the plot
		$graph->title->Set("Relatório de Field Call Rate ");
		$graph->title->SetFont(FF_FONT1,FS_BOLD);

		// Create
		$p1 = new PiePlot($data);
		$p1->SetCenter(0.35,0.50);
		$p1->SetLegends(explode(",",$descricao));

		$graph->Add($p1);

		$graph->Stroke($image_graph);
		echo "\n\n<img src='$image_graph'>\n\n";

	}

//////////////////////////////////////////
}
?>