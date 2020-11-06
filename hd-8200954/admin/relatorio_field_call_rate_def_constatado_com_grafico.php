<?


// Create the graph. These two calls are always required
$xgraph = new Graph(600,400,"auto");    
$xgraph->SetScale("textlin");
	$img = time();
	$ximage_graph = "png/74_$img.png";


$sql = "SELECT COUNT(tbl_os.os) AS qtde ,
				substr(tbl_os.serie,1,2) as mes , 
				substr(tbl_os.serie,3,2) as ano
		FROM tbl_os
		JOIN tmp_com_peca_$login_fabrica USING(OS)
		GROUP by mes, ano
		ORDER BY ano, mes";

//echo nl2br($sql); 
$res = @pg_exec($con,$sql);
//pega qtos os anos
if(@pg_numrows($res)>0){
for($i=0;pg_numrows($res)>$i; $i++){
	$anos[$i] = pg_result($res,$i,ano);
}
$anos = array_unique($anos);

sort($anos);
reset($anos);
$qtde_anos = count($anos);
$qtde_ocorrencia = 12*$qtde_anos;
//echo $qtde_ocorrencia;
for($i=0;$qtde_ocorrencia>$i; $i++){ // faz a qtde de anos que resultou
$ocorrencia[$i] = 0;
}

for($i=0;$qtde_anos>$i; $i++){ // faz a qtde de anos que resultou
	$vet_ano[$anos[$i]]  = array(0,0,0,0,0,0,0,0,0,0,0,0);	
}



//preenche os vetores
for($y=0;pg_numrows($res)>$y; $y++){
	$ano  = pg_result($res,$y,ano);
	$mes  = pg_result($res,$y,mes)-1;
	$qtde = pg_result($res,$y,qtde);
	$vet_ano[$ano][$mes] = $qtde;
}
$ocorrencia="";
$xmes="";
$ymes="";
$contador = 0;
for($x=0;$qtde_anos>$x;$x++){
	for($y=0;$y<12;$y++){

	$ocorrencia[$contador] = $vet_ano[$anos[$x]][$y];
	$ymes[$contador] = ($y+1);
	$h = $y+1;
	$xmes[$contador] = substr(date("M", mktime(0, 0, 0, $h+1,0,0,0)), 0,1)."-".$anos[$x];

	$contador++;
	}
}

$lineplot  = new LinePlot($ocorrencia);
$lineplot  ->SetColor("green");
$lineplot  ->SetWeight(2);

$lineplot ->mark->SetType(MARK_UTRIANGLE);
$lineplot ->value->show();

$xgraph->ygrid->Show(true,true);
$xgraph->xgrid->Show(true,false);

$xgraph->Add($lineplot) ;
$lineplot  ->SetLegend(" Último(s) ".$qtde_anos." ano(s)");

$xgraph->xaxis-> SetTickLabels($xmes);
$xgraph->xaxis-> SetLabelAngle(90);


$xgraph->legend->SetLayout(LEGEND_HOR);
$xgraph->legend->Pos(0.4,0.95,"center","bottom");

$xgraph->img->SetMargin(40,40,40,100);
$xgraph->title->Set("Defeito Produtos X Periodo (Com peças)");
$xgraph->xaxis->title->Set("Mes");
$xgraph->yaxis->title->Set("Ocorrencia");

$xgraph->title->SetFont(FF_FONT1,FS_BOLD);
$xgraph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$xgraph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

$xgraph->yaxis->SetColor("black");
$xgraph->yaxis->SetWeight(2);
$xgraph->SetShadow();

// Display the graph
$xgraph->Stroke($ximage_graph);
echo "\n\n<center><img src='$ximage_graph'></center>\n\n";
}
?>
