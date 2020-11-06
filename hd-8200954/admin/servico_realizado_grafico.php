<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "auditoria";
include 'autentica_admin.php';

//////////////////////////////////////////
if (1 == 1) {

//	include ("../jpgraph/jpgraph.php");
//	include ("../jpgraph/jpgraph_bar.php");
//	include ("../jpgraph/jpgraph_line.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/sr_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	$month = array(1=>"Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez");

	// seleciona as 6 datas, validando com a funcao
	$sql = "SELECT  (
						(fn_dias_mes('$ano-$mes-01',0) - interval '0 months'):: date
					) AS data_inicio_0,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '0 months'):: date
					) AS data_fim_0,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '1 months'):: date
					) AS data_inicio_1,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '1 months'):: date
					) AS data_fim_1,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '2 months'):: date
					) AS data_inicio_2,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '2 months'):: date
					) AS data_fim_2,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '3 months'):: date
					) AS data_inicio_3,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '3 months'):: date
					) AS data_fim_3,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '4 months'):: date
					) AS data_inicio_4,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '4 months'):: date
					) AS data_fim_4,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '5 months'):: date
					) AS data_inicio_5,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '5 months'):: date
					) AS data_fim_5";
	$resDia = pg_exec($con,$sql);
	if (pg_numrows($resDia) > 0){
		$data_inicio[0] = pg_result($resDia,0,data_inicio_0) . ' 00:00:00';
		$data_fim[0]    = pg_result($resDia,0,data_fim_0)    . ' 23:59:59';
		$data_inicio[1] = pg_result($resDia,0,data_inicio_1) . ' 00:00:00';
		$data_fim[1]    = pg_result($resDia,0,data_fim_1)    . ' 23:59:59';
		$data_inicio[2] = pg_result($resDia,0,data_inicio_2) . ' 00:00:00';
		$data_fim[2]    = pg_result($resDia,0,data_fim_2)    . ' 23:59:59';
		$data_inicio[3] = pg_result($resDia,0,data_inicio_3) . ' 00:00:00';
		$data_fim[3]    = pg_result($resDia,0,data_fim_3)    . ' 23:59:59';
		$data_inicio[4] = pg_result($resDia,0,data_inicio_4) . ' 00:00:00';
		$data_fim[4]    = pg_result($resDia,0,data_fim_4)    . ' 23:59:59';
		$data_inicio[5] = pg_result($resDia,0,data_inicio_5) . ' 00:00:00';
		$data_fim[5]    = pg_result($resDia,0,data_fim_5)    . ' 23:59:59';
	}
/*
echo "0: $data_inicio[0] = $data_fim[0] <br> 1: $data_inicio[1] = $data_fim[1] <br> 2: $data_inicio[2] = $data_fim[2] <br> ";
echo "3: $data_inicio[3] = $data_fim[3] <br> 4: $data_inicio[4] = $data_fim[4] <br> 5: $data_inicio[5] = $data_fim[5] ";
exit;
*/
	// seleciona todos os servicos realizados da empresa
	$sql = "SELECT servico_realizado, descricao FROM tbl_servico_realizado WHERE fabrica = $login_fabrica ORDER BY descricao";
	$res = pg_exec($con,$sql);

	// monta cabeçalho
	$mes6 = $month[intval(substr($data_inicio[5],5,2))]; // variáveis
	$mes5 = $month[intval(substr($data_inicio[4],5,2))];
	$mes4 = $month[intval(substr($data_inicio[3],5,2))];
	$mes3 = $month[intval(substr($data_inicio[2],5,2))];
	$mes2 = $month[intval(substr($data_inicio[1],5,2))];
	$mes1 = $month[intval(substr($data_inicio[0],5,2))];

	$corpo = "<tr class='menu_top'>\n";
	$corpo .= "<td>Serviço Realizado</td>\n";
	$corpo .= "<td>$mes6</td>\n";
	$corpo .= "<td>$mes5</td>\n";
	$corpo .= "<td>$mes4</td>\n";
	$corpo .= "<td>$mes3</td>\n";
	$corpo .= "<td>$mes2</td>\n";
	$corpo .= "<td>$mes1</td>\n";
	$corpo .= "<td>Soma</td>\n";
	$corpo .= "</tr>\n";

	// monta dados dos 6 meses anteriores
	for($i=0; $i<pg_numrows($res); $i++){
		$soma[$i] = 0;
		$servico_realizado           = pg_result($res,$i,servico_realizado);
		$servico_realizado_descricao = pg_result($res,$i,descricao);

		$sqlX = "SELECT	a.qtde6,
						b.qtde5,
						c.qtde4,
						d.qtde3,
						e.qtde2,
						f.qtde1
				FROM (
						(
							SELECT	COUNT(*) AS qtde6 
							FROM	tbl_os_item 
							JOIN	tbl_servico_realizado USING (servico_realizado) 
							JOIN	tbl_os_produto using (os_produto) 
							JOIN	tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto ";
		if (strlen($linha) > 0) "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sqlX .= "			JOIN	tbl_os         ON tbl_os.os           = tbl_os_produto.os
							WHERE	tbl_os.data_digitacao between '$data_inicio[5]' AND '$data_fim[5]'
							AND		tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sqlX .= "AND tbl_linha.linha = $linha ";
		$sqlX .= "			AND		tbl_servico_realizado.servico_realizado = $servico_realizado
						)
					) AS a,
					(
						(
							SELECT	COUNT(*) AS qtde5 
							FROM	tbl_os_item 
							JOIN	tbl_servico_realizado USING (servico_realizado) 
							JOIN	tbl_os_produto USING (os_produto) 
							JOIN	tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto ";
		if (strlen($linha) > 0) "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sqlX .= "			JOIN	tbl_os         ON tbl_os.os           = tbl_os_produto.os
							WHERE	tbl_os.data_digitacao between '$data_inicio[4]' AND '$data_fim[4]' 
							AND		tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sqlX .= "AND tbl_linha.linha = $linha ";
		$sqlX .= "			AND		tbl_servico_realizado.servico_realizado = $servico_realizado 
						)
					) AS b,
					(
						(
							SELECT	COUNT(*) AS qtde4 
							FROM	tbl_os_item 
							JOIN	tbl_servico_realizado USING (servico_realizado) 
							JOIN	tbl_os_produto USING (os_produto) 
							JOIN	tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto ";
		if (strlen($linha) > 0) $sqlX .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sqlX .= "			JOIN	tbl_os         ON tbl_os.os           = tbl_os_produto.os
							WHERE	tbl_os.data_digitacao between '$data_inicio[3]' AND '$data_fim[3]' 
							AND		tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sqlX .= "AND tbl_linha.linha = $linha ";
		$sqlX .= "			AND		tbl_servico_realizado.servico_realizado = $servico_realizado 
						)
					) AS c,
					(
						(
							SELECT	COUNT(*) AS qtde3 
							FROM	tbl_os_item 
							JOIN	tbl_servico_realizado USING (servico_realizado) 
							JOIN	tbl_os_produto USING (os_produto) 
							JOIN	tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto ";
		if (strlen($linha) > 0) $sqlX .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sqlX .= "			JOIN	tbl_os         ON tbl_os.os           = tbl_os_produto.os
							WHERE	tbl_os.data_digitacao between '$data_inicio[2]' AND '$data_fim[2]' 
							AND		tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sqlX .= "AND tbl_linha.linha = $linha ";
		$sqlX .= "			AND		tbl_servico_realizado.servico_realizado = $servico_realizado 
						)
					) AS d,
					(
						(
							SELECT	COUNT(*) AS qtde2 
							FROM	tbl_os_item 
							JOIN	tbl_servico_realizado USING (servico_realizado) 
							JOIN	tbl_os_produto USING (os_produto) 
							JOIN	tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto ";
		if (strlen($linha) > 0) $sqlX .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sqlX .= "			JOIN	tbl_os         ON tbl_os.os           = tbl_os_produto.os
							WHERE	tbl_os.data_digitacao between '$data_inicio[1]' AND '$data_fim[1]' 
							AND		tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sqlX .= "AND tbl_linha.linha = $linha ";
		$sqlX .= "			AND		tbl_servico_realizado.servico_realizado = $servico_realizado 
						)
					) AS e,
					(
						(
							SELECT	COUNT(*) AS qtde1 
							FROM	tbl_os_item 
							JOIN	tbl_servico_realizado USING (servico_realizado) 
							JOIN	tbl_os_produto USING (os_produto) 
							JOIN	tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto ";
		if (strlen($linha) > 0) $sqlX .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
		$sqlX .= "			JOIN	tbl_os         ON tbl_os.os           = tbl_os_produto.os
							WHERE	tbl_os.data_digitacao between '$data_inicio[0]' AND '$data_fim[0]' 
							AND		tbl_os.fabrica = $login_fabrica ";
		if (strlen($linha) > 0) $sqlX .= "AND tbl_linha.linha = $linha ";
		$sqlX .= "			AND		tbl_servico_realizado.servico_realizado = $servico_realizado 
						)
					) AS f;";
//		echo $sql; exit;
		$resX = pg_exec($con,$sqlX);
		
		if (pg_numrows($resX) > 0){
			$qtde6 = @pg_result($resX,0,qtde6); // variáveis
			$qtde5 = @pg_result($resX,0,qtde5);
			$qtde4 = @pg_result($resX,0,qtde4);
			$qtde3 = @pg_result($resX,0,qtde3);
			$qtde2 = @pg_result($resX,0,qtde2);
			$qtde1 = @pg_result($resX,0,qtde1);
		}else{
			$qtde6 = 0; // variáveis
			$qtde5 = 0;
			$qtde4 = 0;
			$qtde3 = 0;
			$qtde2 = 0;
			$qtde1 = 0;
		}

		$soma[$i] += $qtde6 + $qtde5 + $qtde4 + $qtde3 + $qtde2 + $qtde1;	// soma dos valores dos servicos realizados

		$total_soma = $total_soma + $soma[$i]; // para calcular porcentagem

		$cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';

		$corpo .= "<tr class='table_line'  style='background-color: $cor;'>\n";
		$corpo .= "<td align='left'>";
		$corpo .= $i + 1;
		$corpo .= ") $servico_realizado_descricao</td>\n";
		$corpo .= "<td align='right'>$qtde6</td>\n";
		$corpo .= "<td align='right'>$qtde5</td>\n";
		$corpo .= "<td align='right'>$qtde4</td>\n";
		$corpo .= "<td align='right'>$qtde3</td>\n";
		$corpo .= "<td align='right'>$qtde2</td>\n";
		$corpo .= "<td align='right'>$qtde1</td>\n";
		$corpo .= "<td align='right'><b>$soma[$i]</b></td>\n";
		$corpo .= "</tr>\n";

		// dados para label
		//$valorLabel[$i] = substr($servico_realizado_descricao,0,10);
	}

	for ($i=0; $i < pg_numrows($res); $i++){
		if ($total_soma > 0)
			$porc[$i] = ($soma[$i]/$total_soma) * 100; // para calcular porcentagem
		else
			$porc[$i] = 0;
	}

	// o gráfico será montado somente com os valores da soma
	$datay  = $porc;

	// Create the graph. These two calls are always required
	$graph = new Graph(700,220,"auto");
	$graph->SetScale("textlin");
	//$graph->yaxis->scale->SetGrace(20);
	$graph->SetShadow();
	$graph->img->SetMargin(40,30,20,40);

	// Create a bar pot
	$bplot = new BarPlot($datay);
	$bplot->SetFillColor('orange@0.4');
	//$bplot->SetShadow();
	$bplot->value->Show();
	$bplot->value->SetFont(FF_ARIAL,FS_BOLD,10);
	$bplot->value->SetFormat('%0.1f%%');
	$bplot->SetWidth(0.2);
	$graph->Add($bplot);

	// line plot
	$lplot = new LinePlot($datay);
	$lplot->SetFillColor('skyblue@0.9');
	$lplot->SetBarCenter();
	$lplot->mark->SetType(MARK_SQUARE);
	$lplot->mark->SetColor('blue@0.7');
	$lplot->mark->SetFillColor('lightblue');
	$lplot->mark->SetSize(3);
	$graph->Add($lplot);

	// Setup the titles
	$graph->title->Set("Gráfico referente a soma dos Serviços Realizados no período de 6 meses");
//	$graph->xaxis->SetTickLabels($xValorLabel);
	$graph->yaxis->title->Set("Porcentagem");

	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
//	$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

	$graph->Stroke($image_graph);

//////////////////////////////////////////
}
?>


<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<TABLE cellpadding='2' cellspacing='1' border='0' width=700>
<? echo $corpo; ?>
</TABLE>
<p>
<TABLE cellpadding='0' cellspacing='1' border='0'>
<TR>
	<TD><img src='<? echo $image_graph; ?>'></TD>
</TR>
</TABLE>
