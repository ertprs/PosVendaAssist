<?
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';
	include 'autentica_admin.php';
	include 'funcoes.php';

	if(strlen($_POST['condicoes'])>0) $condicoes = $_POST['condicoes'];
	else                              $condicoes = $_GET['condicoes'];

	$condicoes = explode(";",$condicoes);
	$cond_1        = $condicoes[0];
	$cond_2        = $condicoes[1];
	$cond_3        = $condicoes[2];
	$cond_4        = $condicoes[3];
	$xdata_inicial = $condicoes[4];
	$xdata_final   = $condicoes[5];
	$title         = $condicoes[6];


	if(strlen($cond_1)>0) $cond_1 = " tbl_hd_chamado_extra.produto = $cond_1 ";
	else                  $cond_1 = " 1=1 ";
	
	if(strlen($cond_2)>0) $cond_2 = " tbl_hd_chamado.categoria in ('$cond_2')";
	else                  $cond_2 = " 1=1 ";

	if(strlen($cond_3)>0) $cond_3 = " tbl_hd_chamado.status = '$cond_3'  ";
	else                  $cond_3 = " 1=1 ";

	if(strlen($cond_4)>0) $cond_4 = " tbl_hd_chamado_extra.posto=$cond_5 ";
	else                  $cond_4 = " 1=1 ";

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.div_titulo {
	text-align: center;
	font-family: Arial;
	font-size: 18px;
	font-weight: bold;
	color: #000;
	background-color: #F1F4FA;
}

td {
	font-family: Arial;
	font-size: 10px;
}


</style>
<?

if($title=="RELATORIO DE ATENDIMENTO"){
	if(strlen($xdata_inicial)>0 AND strlen($xdata_final)>0){
		$sql = "SELECT tbl_hd_chamado.status,
						count(tbl_hd_chamado.hd_chamado) as qtde
				from tbl_hd_chamado
				join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				and $cond_1
				and $cond_2
				and $cond_3
				and $cond_4
				GROUP BY tbl_hd_chamado.status
				order by qtde desc";
		#echo nl2br($sql);

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td class='div_titulo'>";
				echo "RELATÓRIO DE ATENDIMENTO";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table><tr><td>&nbsp</td></tr></table>";

			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<TR >\n";
			if(strlen($nome_posto)>0){
				echo "<td class='menu_top' background='imagens_admin/azul.gif'>Posto</TD>\n";
				echo "</TR >\n";
				echo "<TR >\n";
				echo "<td >$nome_posto</TD>\n";
				echo "</TR >\n";
				echo "</table>";
				echo "<BR><BR>";
			}
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Status</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$status_desc = pg_result($res,$y,status);
				$qtde   = pg_result($res,$y,qtde);
				$grafico_status[] = $status_desc;
				$grafico_qtde[] = $qtde;
				$total = $total + $qtde;
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}

				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>$status_desc</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			if($login_fabrica==2){//HD 36906 9/10/2008
			echo "<TR >\n";
				echo "<TD align='center' nowrap><B>Total</B></TD>\n";
				echo "<TD align='center' nowrap>$total</TD>\n";
			echo "</TR >\n";
			}
			echo "</table>";
		
		echo "<BR><BR>";
		include ("../jpgraph/jpgraph.php");
		include ("../jpgraph/jpgraph_pie.php");
		include ("../jpgraph/jpgraph_pie3d.php");
		$img = time();
		$image_graph = "png/1_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
		$graph = new PieGraph(500,350,"auto");
		$graph->SetShadow();

		$data_inicial = mostra_data($xdata_inicial);
		$data_final   = mostra_data($xdata_final);

		$graph->title->Set("Relatório de Atendimento $data_inicial - $data_final");

		$p1 = new PiePlot3D($grafico_qtde);
		$p1->SetAngle(35);
		$p1->SetSize(0.4);
		$p1->SetCenter(0.5,0.7); // x.y

		$p1->SetLegends($grafico_status);
		$p1->SetSliceColors(array('blue','red'));
		$graph->Add($p1);
		$graph->Stroke($image_graph);

		echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2'>";
			echo "<TR >\n";
				echo "<TD>";
							echo "\n\n<img src='$image_graph'>\n\n";
				echo "</TD>";
			echo "</TR>";
		echo "</TABLE>";

		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
}


if($title=="RELATORIO PERIODO DE ATENDIMENTO"){
		if(strlen($xdata_inicial)>0 AND strlen($xdata_final)>0){
		$sql = "
				SELECT COUNT(tbl_hd_chamado.hd_chamado) AS qtde,
						dias_aberto as periodo
				FROM tbl_hd_chamado_extra 
				JOIN tbl_hd_chamado using(hd_chamado) 
				WHERE fabrica_responsavel =  $login_fabrica 
				AND tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
				AND $cond_1
				AND $cond_2
				AND $cond_3
				AND $cond_4
				group by dias_aberto order by qtde desc";

		//	echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td class='div_titulo'>";
				echo "RELATÓRIO PERÍODO DE ATENDIMENTO";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table><tr><td>&nbsp</td></tr></table>";

			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Intervalo de Atendimento</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$periodo = pg_result($res,$y,periodo);
				$qtde   = pg_result($res,$y,qtde);
				$xperiodo =$periodo;

				if($periodo==0){$periodo = "Mesmo dia";}
				if($periodo==1){$periodo = "1 dia";}
				if($periodo>1){$periodo .= " dias";}
				
				$grafico_periodo[] = $periodo;
				$grafico_qtde[] = $qtde;

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>$periodo</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
		
			echo "<BR><BR>";
			include ("../jpgraph/jpgraph.php");
			include ("../jpgraph/jpgraph_pie.php");
			include ("../jpgraph/jpgraph_pie3d.php");
			$img = time();
			$image_graph = "png/2_call$img.png";
			
			// seleciona os dados das médias
			setlocale (LC_ALL, 'et_EE.ISO-8859-1');
		
			//$data = array(40,60,21,33);
			
			$graph = new PieGraph(500,400,"auto");
			$graph->SetShadow();

			$data_inicial = mostra_data($xdata_inicial);
			$data_final   = mostra_data($xdata_final);

			$graph->title->Set("Relatório Período de Atendimento $data_inicial - $data_final");
			$graph->title->SetFont(FF_FONT1,FS_BOLD);

			$p1 = new PiePlot3D($grafico_qtde);
			$p1->SetSize(0.4);

			$p1->SetCenter(0.45);
			//$p1->SetLegends($gDateLocale->GetShortMonth());
			$p1->SetLegends($grafico_periodo);
		
			$graph->Add($p1);
			$graph->Stroke($image_graph);

			echo "<table width='500' align='center' cellpadding='1' cellspacing='2'>";
				echo "<TR >\n";
					echo "<TD>";
								echo "\n\n<img src='$image_graph'>\n\n";
					echo "</TD>";
				echo "</TR>";
			echo "</TABLE>";

		}
	}
}


if($title=="RELATORIO DE RECLAMACAO"){
	if(strlen($xdata_inicial)>0 AND strlen($xdata_final)>0){
		$sql = "SELECT tbl_hd_chamado_extra.defeito_reclamado,
						tbl_defeito_reclamado.descricao,
						count(tbl_hd_chamado.hd_chamado) as qtde
				from tbl_hd_chamado
				join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				and $cond_1
				and $cond_2
				and $cond_3
				and $cond_4
				GROUP by tbl_hd_chamado_extra.defeito_reclamado,
						tbl_defeito_reclamado.descricao
				order by qtde desc
				limit 10";
		//echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td class='div_titulo'>";
				echo "RELATÓRIO DE RECLAMAÇÃO";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table><tr><td>&nbsp</td></tr></table>";

			echo "<table width='500' border='1'  bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Status</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$defeito_reclamado = pg_result($res,$y,defeito_reclamado);
				$descricao         = pg_result($res,$y,descricao);
				$qtde              = pg_result($res,$y,qtde);
				if(strlen($descricao)==0){$descricao = "Sem defeito reclamado";}
				$grafico_status[] = $descricao;
				$grafico_qtde[] = $qtde;

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>$descricao</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
		
		echo "<BR><BR>";
		include ("../jpgraph/jpgraph.php");
		include ("../jpgraph/jpgraph_pie.php");
		include ("../jpgraph/jpgraph_pie3d.php");
		$img = time();
		$image_graph = "png/4_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	
		$graph = new PieGraph(550,350,"auto");
		$graph->SetShadow();

		$data_inicial = mostra_data($xdata_inicial);
		$data_final   = mostra_data($xdata_final);

		$graph->title->Set("Relatório de Reclamação $data_inicial - $data_final");
//		$graph->title->Set("");
		$p1 = new PiePlot3D($grafico_qtde);
		$p1->SetAngle(35);
		$p1->SetSize(0.4);
		$p1->SetCenter(0.4,0.7); // x.y
		//$p1->SetLegends($gDateLocale->GetShortMonth());
		$p1->SetLegends($grafico_status);
		//$p1->SetSliceColors(array('blue','red'));
		$graph->Add($p1);
		$graph->Stroke($image_graph);

		echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2'>";
			echo "<TR >\n";
				echo "<TD>";
							echo "\n\n<img src='$image_graph'>\n\n";
				echo "</TD>";
			echo "</TR>";
		echo "</TABLE>";

		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
}

if($title == "RELATORIO MAIOR TEMPO ENTRE INTERACOES"){
	if(strlen($xdata_inicial)>0 AND strlen($xdata_final)>0){

	$sql = "
		SELECT	(dias/item)::integer   AS media, 
				count(*) as qtde
		FROM(
			SELECT	hd_chamado, 
					CASE WHEN (dias_aberto - feriado - fds) = 0 THEN 1
					ELSE (dias_aberto - feriado - fds)
					END AS dias, 
					item 
			FROM (
				SELECT	X.hd_chamado, 
						(	SELECT COUNT(*) 
							FROM fn_calendario(X.data_abertura::date,X.ultima_data::date) 
							where nome_dia in('Domingo','Sábado')
						) AS fds, 
						(	SELECT COUNT(*) 
							FROM tbl_feriado 
							WHERE tbl_feriado.fabrica = 6 AND tbl_feriado.ativo IS TRUE 
							AND tbl_feriado.data BETWEEN X.data_abertura::date AND X.ultima_data::date 
						) AS feriado,
						X.item , 
						EXTRACT('days' FROM X.ultima_data::timestamp - X.data_abertura ::timestamp) AS dias_aberto,
						X.data_abertura, X.ultima_data 
				FROM(	SELECT	tbl_hd_chamado.hd_chamado, 
								TO_CHAR(tbl_hd_chamado.data,'YYYY-MM-DD') AS data_abertura, 
								COUNT(tbl_hd_chamado_item.hd_chamado) AS item,
								(	SELECT to_char(tbl_hd_chamado_item.data,'YYYY-MM-DD') 
									FROM tbl_hd_chamado_item 
									WHERE tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado 
									ORDER BY tbl_hd_chamado_item.hd_chamado_item DESC LIMIT 1 
								) AS ultima_data 
						FROM tbl_hd_chamado 
						JOIN tbl_hd_chamado_item using(hd_chamado) 
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado_item.interno is not true
						and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
						and $cond_1
						and $cond_2
						and $cond_3
						and $cond_4
						GROUP BY tbl_hd_chamado.hd_chamado, tbl_hd_chamado.data 
				) AS X
			) as Y
		) as w
		group by media
		order by media 
	";

	//select date_part('day',interval '02:04:25.296765');
	$sql = "SELECT count(X.hd_chamado) as qtde	,
				X.intervalo
		FROM (
		SELECT tbl_hd_chamado.hd_chamado,
				CASE WHEN
					(	SELECT MAX( DATE_PART('day',(tbl_hd_chamado_item.tempo_interacao)::interval))
						FROM  tbl_hd_chamado_item 
						WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
						AND   tbl_hd_chamado_item.interno IS NOT TRUE
						LIMIT 1) IS NULL THEN '0'
				ELSE 
					(	SELECT MAX( DATE_PART('day',(tbl_hd_chamado_item.tempo_interacao)::interval))
						FROM  tbl_hd_chamado_item 
						WHERE tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado 
						AND   tbl_hd_chamado_item.interno IS NOT TRUE
						LIMIT 1)  
				END AS intervalo
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra     on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		WHERE tbl_hd_chamado.fabrica  = $login_fabrica
		AND   tbl_hd_chamado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
		AND   $cond_1
		AND   $cond_2
		AND   $cond_3
		AND   $cond_4
		) AS X
		group by intervalo order by qtde desc";

		//echo nl2br($sql);

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td class='div_titulo'>";
				echo "RELATÓRIO MAIOR TEMPO ENTRE INTERAÇÕES";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table><tr><td>&nbsp</td></tr></table>";

			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Qtde de dias</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde chamados</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$intervalo = pg_result($res,$y,intervalo);
				$qtde   = pg_result($res,$y,qtde);
				if($intervalo=="0"){$xintervalo = "Mesmo dia";}else{
					$xintervalo = "$intervalo dia(s)";
				}
				$grafico_media[] = $xintervalo;
				$grafico_qtde[] = $qtde;

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>$xintervalo</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
		
		echo "<BR><BR>";
		include ("../jpgraph/jpgraph.php");
		include ("../jpgraph/jpgraph_pie.php");
		include ("../jpgraph/jpgraph_pie3d.php");
		$img = time();
		$image_graph = "png/1_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	
		$graph = new PieGraph(500,500,"auto");
		$graph->SetShadow();

		$data_inicial = mostra_data($xdata_inicial);
		$data_final   = mostra_data($xdata_final);

		$graph->title->Set("Relatório maior tempo entre interações\n $data_inicial - $data_final");

		$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$p1 = new PiePlot3D($grafico_qtde);
		$p1->SetAngle(60);
		$p1->SetSize(0.35);
		$p1->SetCenter(0.4,0.6); // x.y
		$p1->SetLegends($grafico_media);
		$graph->Add($p1);
		$graph->Stroke($image_graph);

		echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2'>";
			echo "<TR >\n";
				echo "<TD>";
							echo "\n\n<img src='$image_graph'>\n\n";
				echo "</TD>";
			echo "</TR>";
		echo "</TABLE>";

		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
}


if($title == "RELATORIO DE NATUREZA DE CHAMADO"){
	if(strlen($xdata_inicial)>0 AND strlen($xdata_final)>0){
		$sql = "SELECT tbl_hd_chamado.categoria,
						count(tbl_hd_chamado.hd_chamado) as qtde
				from tbl_hd_chamado
				join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				and $cond_1
				and $cond_2
				and $cond_3
				and $cond_4
				GROUP by tbl_hd_chamado.categoria
				HAVING LENGTH(tbl_hd_chamado.categoria)>0
				order by qtde desc";

		//echo $sql;
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td class='div_titulo'>";
				echo "RELATÓRIO DE NATUREZA DE CHAMADO";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table><tr><td>&nbsp</td></tr></table>";

			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Status</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$categoria = pg_result($res,$y,categoria);
				$qtde      = pg_result($res,$y,qtde);
		
				$grafico_status[] = $categoria;
				$grafico_qtde[] = $qtde;
				$total = $total + $qtde;
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>";
				if($categoria == 'troca_produto') echo "Troca do Produto"; elseif($categoria == 'reclamacao_produto') echo "Reclamação de Produto";
				elseif($categoria == 'duvida_produto') echo "Dúvida sobre produto";
				elseif($categoria == 'reclamacao_empresa') echo "Reclamação de empresa";
				elseif($categoria == 'reclamacao_at') echo "Reclamação de atendimento";
				else echo "$categoria";
				
				
				echo "</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			if($login_fabrica==2){//HD 36906 9/10/2008
			echo "<TR >\n";
				echo "<TD align='center' nowrap><B>Total</B></TD>\n";
				echo "<TD align='center' nowrap>$total</TD>\n";
			echo "</TR >\n";
			}

			echo "</table>";
		
		echo "<BR><BR>";
		include ("../jpgraph/jpgraph.php");
		include ("../jpgraph/jpgraph_pie.php");
		include ("../jpgraph/jpgraph_pie3d.php");
		$img = time();
		$image_graph = "png/4_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	
		$graph = new PieGraph(550,350,"auto");
		$graph->SetShadow();

		$data_inicial = mostra_data($xdata_inicial);
		$data_final   = mostra_data($xdata_final);

		$graph->title->Set("Relatório de Reclamação $data_inicial - $data_final");
		$p1 = new PiePlot3D($grafico_qtde);
		$p1->SetAngle(35);
		$p1->SetSize(0.4);
		$p1->SetCenter(0.4,0.7); // x.y
		$p1->SetLegends($grafico_status);
		$graph->Add($p1);
		$graph->Stroke($image_graph);

		echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2'>";
			echo "<TR >\n";
				echo "<TD>";
							echo "\n\n<img src='$image_graph'>\n\n";
				echo "</TD>";
			echo "</TR>";
		echo "</TABLE>";

		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
}


if($title == "RELATORIO POR ATENDENTES"){
	if(strlen($xdata_inicial)>0 AND strlen($xdata_final)>0){
		if ($login_fabrica != 2) {
			$sql = "SELECT tbl_admin.admin                          ,
							tbl_admin.login                         ,
							count(tbl_hd_chamado.hd_chamado) as qtde
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
						JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
						and $cond_1
						and $cond_2
						and $cond_3
						and $cond_4
						GROUP by tbl_admin.admin, tbl_admin.login
						ORDER BY qtde DESC;
				";
		} else {
			$sql = "SELECT tbl_admin.admin                               ,
							tbl_admin.login                              ,
							count(tbl_hd_chamado_item.hd_chamado) as qtde
					FROM tbl_hd_chamado_item
					JOIN tbl_hd_chamado_extra on tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado
					JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
					JOIN tbl_hd_chamado ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					AND tbl_hd_chamado_item.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
					and $cond_1
					and $cond_2
					and $cond_3
					and $cond_4
					GROUP by tbl_admin.admin, tbl_admin.login
					ORDER BY qtde DESC;
					";
		}
		
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td class='div_titulo'>";
				echo "RELATÓRIO POR ATENDENTES";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table><tr><td>&nbsp</td></tr></table>";

			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Atendente</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$adm              = pg_result($res,$y,admin);
				$login_admin      = pg_result($res,$y,login);
				$qtde             = pg_result($res,$y,qtde);
#				if(strlen($descricao)==0){$descricao = "Sem defeito reclamado";}
				$grafico_atendente[] = $login_admin;
				$grafico_qtde[] = $qtde;

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>$login_admin</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
		

			echo "<BR><BR>";
			include ("../jpgraph/jpgraph.php");
			include ("../jpgraph/jpgraph_pie.php");
			include ("../jpgraph/jpgraph_pie3d.php");
			$img = time();
			$image_graph = "png/4_call$img.png";
			
			// seleciona os dados das médias
			setlocale (LC_ALL, 'et_EE.ISO-8859-1');
		
		
			$graph = new PieGraph(550,350,"auto");
			$graph->SetShadow();
			
			$data_inicial = mostra_data($xdata_inicial);
			$data_final   = mostra_data($xdata_final);

			$graph->title->Set("Relatório por atendente $data_inicial - $data_final");
			$p1 = new PiePlot3D($grafico_qtde);
			$p1->SetAngle(35);
			$p1->SetSize(0.4);
			$p1->SetCenter(0.4,0.7); // x.y
			$p1->SetLegends($grafico_atendente);
			$graph->Add($p1);
			$graph->Stroke($image_graph);

		echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2'>";
			echo "<TR >\n";
				echo "<TD>";
							echo "\n\n<img src='$image_graph'>\n\n";
				echo "</TD>";
			echo "</TR>";
		echo "</TABLE>";

		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}

	}
}


if($title == "RELATORIO ATENDIMENTO POR PRODUTO"){
	if(strlen($xdata_inicial)>0 AND strlen($xdata_final)>0){
		$sql = "SELECT	tbl_produto.produto        ,
						tbl_produto.referencia     , 
						tbl_produto.descricao      , 	
						tbl_produto.ativo          , 
						count(*) as qtde
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra using(hd_chamado)
				LEFT JOIN tbl_produto on tbl_produto.produto= tbl_hd_chamado_extra.produto
				WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				and  tbl_hd_chamado.status<>'Cancelado'
				and $cond_1
				and $cond_2
				and $cond_3
				GROUP BY	tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  , 
							tbl_produto.ativo
				ORDER BY qtde desc
				;
		";
		
	//	echo $sql;

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<tr>";
				echo "<td class='div_titulo'>";
				echo "RELATÓRIO ATENDIMENTO POR PRODUTO";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "<table><tr><td>&nbsp</td></tr></table>";

			echo "<table width='500' border='1' bordercolor='#000000' align='center' cellpadding='0' cellspacing='0'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Produto</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$produto    = pg_result($res,$y,produto);
				$referencia = pg_result($res,$y,referencia);
				$descricao  = pg_result($res,$y,descricao);
				$ativo      = pg_result($res,$y,ativo);
				$qtde       = pg_result($res,$y,qtde);

				if(strlen($produto)==0){
					$descricao  = "Chamado sem produto";
				}
				
				$grafico_descricao[] = substr($descricao,0,24);
				$grafico_qtde[] = $qtde;

				if($ativo<>"t"){$ativo="*";}else{$ativo="";}
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>$ativo $referencia - $descricao</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
			echo "<center><font size='1'>* produto(s) inativo(s)</font></center>";
			echo "<BR><BR>";
			include ("../jpgraph/jpgraph.php");
			include ("../jpgraph/jpgraph_pie.php");
			include ("../jpgraph/jpgraph_pie3d.php");
			$img = time();
			$image_graph = "png/3_call$img.png";
			
			// seleciona os dados das médias
			setlocale (LC_ALL, 'et_EE.ISO-8859-1');
		
		
			$graph = new PieGraph(600,400,"auto");
			$graph->SetShadow();

			$data_inicial = mostra_data($xdata_inicial);
			$data_final   = mostra_data($xdata_final);

			$graph->title->Set("Relatório de Atendimento por Produto $data_inicial - $data_final");
			$graph->title->SetFont(FF_FONT1,FS_BOLD);

			$p1 = new PiePlot3D($grafico_qtde);
			$p1->SetAngle(60);
			$p1->SetSize(0.4);
			$p1->SetCenter(0.35,0.5); // x.y
			$p1->SetLegends($grafico_descricao);

			$graph->Add($p1);
			$graph->Stroke($image_graph);

			echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2'>";
				echo "<TR >\n";
					echo "<TD>";
								echo "\n\n<img src='$image_graph'>\n\n";
					echo "</TD>";
				echo "</TR>";
			echo "</TABLE>";

		}
	}
}
?>

<SCRIPT LANGUAGE="JavaScript">
<!--
	window.print();
//-->
</SCRIPT>
