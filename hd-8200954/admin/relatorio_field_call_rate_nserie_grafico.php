<?

include ("../jpgraph/jpgraph_line.php");

// Create the graph. These two calls are always required
$xgraph = new Graph(700,500,"auto");    
$xgraph->SetScale("textlin");
	$img = time();
	$ximage_graph = "png/4_$img.png";


$peca         = $_GET['peca'];
$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$tipo_os      = $_GET['tipo_os'];

if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
$sql = "SELECT 	count(*) as qtde                  , 
				substr(serie,1,2)::integer as mes , 
				substr(serie,3,4) as ano
		FROM (
			SELECT DISTINCT tbl_os_produto.os, substr(tbl_os.serie,1,4) as serie
			FROM tbl_os_produto
			JOIN tbl_os on tbl_os.os = tbl_os_produto.os
			JOIN (	SELECT tbl_os.os ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
					FROM tbl_os_extra
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
					AND tbl_extrato.liberado IS NOT NULL
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.produto = $produto
					AND $cond_5
			) fcr ON tbl_os_produto.os = fcr.os
		JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		AND tbl_os_item.peca = $peca
		) as X 
		GROUP by mes, ano
		ORDER BY ano, mes";

if($tipo=="grupo"){

$sql = "SELECT 	count(*) as qtde                  , 
				substr(serie,1,2)::integer as mes , 
				substr(serie,3,4) as ano
		FROM (
			SELECT DISTINCT tbl_os_produto.os, substr(tbl_os.serie,1,4) as serie
			FROM tbl_os_produto
			JOIN tbl_os on tbl_os.os = tbl_os_produto.os
			JOIN (	SELECT tbl_os.os ,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
					FROM tbl_os_extra
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
					AND tbl_extrato.liberado IS NOT NULL
					AND tbl_os.excluida IS NOT TRUE
					AND $cond_5
					AND tbl_produto.referencia_fabrica='$produto' AND tbl_produto.ativo='t'
			) fcr ON tbl_os_produto.os = fcr.os
		JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		AND tbl_os_item.peca = $peca
		) as X 
		GROUP by mes, ano
		ORDER BY ano, mes";

}
//echo nl2br($sql); 
$res = pg_exec($con,$sql);
//pega qtos os anos
for($i=0;pg_numrows($res)>$i; $i++){
	$anos[$i] = pg_result($res,$i,ano);
}

$anos = array_unique($anos);
sort($anos);
reset($anos);
$qtde_anos = count($anos);

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

$cor = array("blue","red","orange","green","yellow");

for($i=0;$qtde_anos>$i; $i++){ // faz a qtde de anos que resultou
//$ncor = array_rand($cor,1);
$lineplot[$i]  = new LinePlot($vet_ano[$anos[$i]]);
$lineplot[$i]  ->SetColor($cor[$i]);
$lineplot[$i]  ->SetWeight(2);

$lineplot[$i] ->mark->SetType(MARK_UTRIANGLE);
$lineplot[$i] ->value->show();


$xgraph->Add($lineplot[$i]) ;
$lineplot[$i]  ->SetLegend("Ano ".$anos[$i]);

// Create the linear plot



}


$xgraph->legend->SetLayout(LEGEND_HOR);
$xgraph->legend->Pos(0.4,0.95,"center","bottom");

$xgraph->img->SetMargin(40,20,20,70);
$xgraph->title->Set("Relatorio Defeitos X Periodo");
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
?>