<?

$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
//////////////////////////////////////////
if (1 == 1) {

	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_pie.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/3_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	$sql = "SELECT count(*) AS ocorrencia,tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		FROM tbl_os
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
		JOIN   (SELECT DISTINCT tbl_os_produto.os
				FROM tbl_os_produto
				JOIN (SELECT tbl_os.os , 
							(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
					FROM tbl_os_extra
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'
					AND   tbl_extrato.liberado IS NOT NULL
					AND   tbl_os.excluida IS NOT TRUE
					AND   tbl_os.produto = $produto
					AND   tbl_posto.pais = '$login_pais'
					AND   $cond_1
					AND   $cond_3
					AND   $cond_4
				) fcr ON tbl_os_produto.os = fcr.os
				JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				AND   $cond_2
		) fcr1 ON tbl_os.os = fcr1.os
		GROUP BY tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		ORDER BY ocorrencia DESC
		  " ;
					//echo $sql;
	$res = pg_exec ($con,$sql);

	
	$res = pg_exec($con,$sql);
	for ($x = 0; $x < pg_numrows($res); $x++) {
			$total = $total + pg_result($res,$x,ocorrencia);
		}
	$n_ocorrencia_anterior =0;
	for ($x = 0; $x < pg_numrows($res); $x++) {

		$y = pg_result($res,$x,ocorrencia);
		$p_ocorrencia = ( $y/ $total ) * 100;

		if ($x==0) {
			$ocorrencia = pg_result($res,$x,ocorrencia);
			
			$descricao  = substr(pg_result($res,$x,nome), 0, 35);
			$porc_ocorrencia = $p_ocorrencia;
		}
		elseif ($x>=20){
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

			$n_descricao  = substr(pg_result($res,$x,nome), 0, 35);

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
		echo "\n\n<center><img src='$image_graph'></center>\n\n";

	}

//////////////////////////////////////////
}
?>