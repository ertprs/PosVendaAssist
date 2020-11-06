<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "Relatório de Prazos Médios de Atendimento";

include "cabecalho.php";

$sql = "SELECT  x.codigo_posto                                                                                                    ,
				x.nome                                                                                                            ,
				count(*)                                                                      AS qtde                             ,
				sum(x.media_abertura_pedido)                                                  AS soma_media_abertura_pedido       ,
				sum(x.media_pedido_faturamento)                                               AS soma_media_pedido_faturamento    ,
				sum(x.media_faturamento_fechamento)                                           AS soma_media_faturamento_fechamento,
				to_char((sum(x.media_abertura_pedido)        / count(*)),999999990.99)::float AS media_abertura_pedido            ,
				to_char((sum(x.media_pedido_faturamento)     / count(*)),999999990.99)::float AS media_pedido_faturamento         ,
				to_char((sum(x.media_faturamento_fechamento) / count(*)),999999990.99)::float AS media_faturamento_fechamento
		FROM (
			SELECT  tbl_posto_fabrica.codigo_posto                                                                                                                     ,
					tbl_posto.nome                                                                                                                                     ,
					tbl_os.data_digitacao                                                                                                                              ,
					tbl_os.finalizada                                                                                                  AS data_fechamento              ,
					tbl_pedido.data                                                                                                    AS data_pedido                  ,
					tbl_faturamento.emissao                                                                                            AS data_faturamento             ,
					((to_char(tbl_pedido.data,'YYYY-MM-DD')::date - to_char(tbl_os.data_digitacao,'YYYY-MM-DD')::date) / count(*))     AS media_abertura_pedido        ,
					((to_char(tbl_faturamento.emissao,'YYYY-MM-DD')::date - to_char(tbl_pedido.data,'YYYY-MM-DD')::date) / count(*))   AS media_pedido_faturamento     ,
					((to_char(tbl_os.finalizada,'YYYY-MM-DD')::date - to_char(tbl_faturamento.emissao,'YYYY-MM-DD')::date) / count(*)) AS media_faturamento_fechamento
			FROM    tbl_posto_fabrica
			JOIN    tbl_posto       ON tbl_posto.posto        = tbl_posto_fabrica.posto
			JOIN    tbl_os          ON tbl_os.posto           = tbl_posto_fabrica.posto
			JOIN    tbl_os_produto  ON tbl_os_produto.os      = tbl_os.os
			JOIN    tbl_os_item     ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN    tbl_pedido      ON tbl_pedido.pedido      = tbl_os_item.pedido
			JOIN    tbl_faturamento ON tbl_faturamento.pedido = tbl_pedido.pedido
			WHERE   tbl_os.finalizada notnull
			AND     tbl_posto_fabrica.fabrica = $login_fabrica
			GROUP BY    tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_os.data_digitacao         ,
						tbl_os.finalizada             ,
						tbl_pedido.data               ,
						tbl_faturamento.emissao
		) AS x
		WHERE x.media_faturamento_fechamento > 0
		GROUP BY    x.codigo_posto,
					x.nome;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0) {
	$linhas = pg_numrows($res);
	
	for ($i = 0; $i < pg_numrows($res); $i++) {
		$media_abertura_pedido        = trim(pg_result($res,$i,media_abertura_pedido));
		$media_pedido_faturamento     = trim(pg_result($res,$i,media_pedido_faturamento));
		$media_faturamento_fechamento = trim(pg_result($res,$i,media_faturamento_fechamento));
		
		$soma_media_abertura_pedido        = $soma_media_abertura_pedido        + $media_abertura_pedido;
		$soma_media_pedido_faturamento     = $soma_media_pedido_faturamento     + $media_pedido_faturamento;
		$soma_media_faturamento_fechamento = $soma_media_faturamento_fechamento + $media_faturamento_fechamento;
		
		$x_ap[$i] = $media_abertura_pedido;
		$x_pf[$i] = $media_pedido_faturamento;
		$x_ff[$i] = $media_faturamento_fechamento;
	}
	
	// DESVIO PADRAO
	function desvio_padrao($n,$media,$x){
		global $linhas;
		
		for($j=0; $j<$n; $j++){
			$media1   = ($x[$j] - $media);
			$quadrado = $media1 * $media1;
			$soma     = $soma + $quadrado;
		}
		
		$resultado  = $soma / $linhas;
		$retorno    = sqrt($resultado);
		return $retorno;
	}
	
	$media_ap = $soma_media_abertura_pedido        / $linhas;
	$media_pf = $soma_media_pedido_faturamento     / $linhas;
	$media_ff = $soma_media_faturamento_fechamento / $linhas;
	
	$desvio_padrao_ap = desvio_padrao($i,$media_ap,$x_ap);
	$desvio_padrao_pf = desvio_padrao($i,$media_pf,$x_pf);
	$desvio_padrao_ff = desvio_padrao($i,$media_ff,$x_ff);
	
	$media_grafico_1_acima  = 0;
	$media_grafico_1_desvio = 0;
	$media_grafico_1_abaixo = 0;
	$media_grafico_2_acima  = 0;
	$media_grafico_2_desvio = 0;
	$media_grafico_2_abaixo = 0;
	$media_grafico_3_acima  = 0;
	$media_grafico_3_desvio = 0;
	$media_grafico_3_abaixo = 0;
	
	for ($i = 0; $i < pg_numrows($res); $i++) {
		$media_abertura_pedido        = trim(pg_result($res,$i,media_abertura_pedido));
		$media_pedido_faturamento     = trim(pg_result($res,$i,media_pedido_faturamento));
		$media_faturamento_fechamento = trim(pg_result($res,$i,media_faturamento_fechamento));
		
		// CALCULA E PASSA OS VALORES
		if($media_abertura_pedido <= (($soma_media_abertura_pedido / $linhas) + $desvio_padrao_ap) AND $media_abertura_pedido >= (($soma_media_abertura_pedido / $linhas) - $desvio_padrao_ap))
			$media_grafico_1_desvio++;
		elseif($media_abertura_pedido > ($soma_media_abertura_pedido / $linhas))
			$media_grafico_1_acima++;
		else
			$media_grafico_1_abaixo++;
		
		if($media_pedido_faturamento <= (($soma_media_pedido_faturamento / $linhas) + $desvio_padrao_pf) AND $media_pedido_faturamento >= (($soma_media_pedido_faturamento / $linhas) - $desvio_padrao_pf))
			$media_grafico_2_desvio++;
		elseif($media_pedido_faturamento > ($soma_media_pedido_faturamento / $linhas))
			$media_grafico_2_acima++;
		else
			$media_grafico_2_abaixo++;
		
		if($media_faturamento_fechamento <= (($soma_media_faturamento_fechamento / $linhas) + $desvio_padrao_ff) AND $media_faturamento_fechamento >= (($soma_media_faturamento_fechamento / $linhas) - $desvio_padrao_ff))
			$media_grafico_3_desvio++;
		elseif($media_faturamento_fechamento > ($soma_media_faturamento_fechamento / $linhas))
			$media_grafico_3_acima++;
		else
			$media_grafico_3_abaixo++;
	}
	
	//////////////////////////////////////////
	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_pie.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/$img.png";
	
	// seleciona os dados das médias
	$data1 = array($media_grafico_1_acima, $media_grafico_1_abaixo, $media_grafico_1_desvio);
	$data2 = array($media_grafico_2_acima, $media_grafico_2_abaixo, $media_grafico_2_desvio);
	$data3 = array($media_grafico_3_acima, $media_grafico_3_abaixo, $media_grafico_3_desvio);
	
	// Create the Pie Graph.
	$graph = new PieGraph(700,300,"auto");
	$graph->SetShadow();
	
	// Set A title for the plot
	$graph->title->Set("Ordens de Serviços - Prazo médio em conserto");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	$graph->legend->Pos(0.02,0.02);
	
	// Create plots
	$size=0.15;
	$p1 = new PiePlot($data1);
	$p1->SetLegends(array("Acima da média","Abaixo da média", "Na média / Des. padrão"));
	$p1->SetSize($size);
	$p1->SetCenter(0.12,0.60);
	$p1->value->SetFont(FF_FONT0);
	$p1->title->Set("Abertura - Pedido\nMédia ". number_format($soma_media_abertura_pedido / $linhas,2,".","") ." dias\nDesvio padrão ".number_format($desvio_padrao_ap,2,'.','')."\n\n");
	$p1->SetTheme("sand");
	
	$p2 = new PiePlot($data2);
	$p2->SetSize($size);
	$p2->SetCenter(0.38,0.60);
	$p2->value->SetFont(FF_FONT0);
	$p2->title->Set("Pedido - Faturamento\nMédia ". number_format($soma_media_pedido_faturamento / $linhas,2,".","") ." dias\nDesvio padrão ".number_format($desvio_padrao_pf,2,'.','')."\n\n");
	$p2->SetTheme("sand");
	
	$p3 = new PiePlot($data3);
	$p3->SetSize($size);
	$p3->SetCenter(0.62,0.60);
	$p3->value->SetFont(FF_FONT0);
	$p3->title->Set("Faturamento - Fechamento\nMédia ". number_format($soma_media_faturamento_fechamento / $linhas,2,".","") ." dias\nDesvio padrão ".number_format($desvio_padrao_ff,2,'.','')." \n\n");
	$p3->SetTheme("sand");
	
	$graph->Add($p1);
	$graph->Add($p2);
	$graph->Add($p3);
	
	$graph->Stroke($image_graph);
	//////////////////////////////////////////
	
?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B;
}

a:link.top   { color:#ffffff; }
a:visited.top{ color:#ffffff; }
a:hover.top  { color:#ffffff; }

.table_linex {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<?
	// EXIBE A IMAGEM
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='2'>";
	echo "<tr>\n";
	echo "<td bgcolor='#FFFFFF'align='center'><br><p><img src='$image_graph'></td>\n";
	echo "</tr>";
	echo "</table>";

	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='2'>";
	echo "<tr class='menu_top'>\n";
	
	echo "<td nowrap>POSTO</td>";
	echo "<td nowrap>MÉDIA<br>AB - PED</td>";
	echo "<td nowrap>MÉDIA<br>PED - FAT</td>";
	echo "<td nowrap>MÉDIA<br>FAT - FECH</td>";
	
	echo "</tr>";
	
	$soma_qtde                         = 0;
	$soma_media_abertura_pedido        = 0;
	$soma_media_pedido_faturamento     = 0;
	$soma_media_faturamento_fechamento = 0;
	
	for ($i = 0; $i < pg_numrows($res); $i++) {
		$codigo_posto                 = trim(pg_result($res,$i,codigo_posto));
		$nome                         = trim(pg_result($res,$i,nome));
		$qtde                         = trim(pg_result($res,$i,qtde));
		$abertura_pedido              = trim(pg_result($res,$i,soma_media_abertura_pedido));
		$pedido_faturamento           = trim(pg_result($res,$i,soma_media_pedido_faturamento));
		$faturamento_fechamento       = trim(pg_result($res,$i,soma_media_faturamento_fechamento));
		$media_abertura_pedido        = trim(pg_result($res,$i,media_abertura_pedido));
		$media_pedido_faturamento     = trim(pg_result($res,$i,media_pedido_faturamento));
		$media_faturamento_fechamento = trim(pg_result($res,$i,media_faturamento_fechamento));
		
		$soma_qtde                         = $soma_qtde + $qtde;
		$soma_media_abertura_pedido        = $soma_media_abertura_pedido        + $media_abertura_pedido;
		$soma_media_pedido_faturamento     = $soma_media_pedido_faturamento     + $media_pedido_faturamento;
		$soma_media_faturamento_fechamento = $soma_media_faturamento_fechamento + $media_faturamento_fechamento;
		
		echo "<tr>\n";
		echo "<td class='table_linex' align='left' nowrap>$codigo_posto - $nome</td>";
		echo "<td class='table_linex' align='right'>". number_format($media_abertura_pedido,2,",",".") ."</td>";
		echo "<td class='table_linex' align='right'>". number_format($media_pedido_faturamento,2,",",".") ."</td>";
		echo "<td class='table_linex' align='right'>". number_format($media_faturamento_fechamento,2,",",".") ."</td>";
		echo "</tr>";
	}
	echo "<tr>\n";
	
	echo "<td class='menu_top' align='center' nowrap><b>MÉDIA GERAL</b></td>";
	echo "<td class='menu_top' align='right'><b>". number_format(($soma_media_abertura_pedido / $i),2,",",".") ."</b></td>";
	echo "<td class='menu_top' align='right'><b>". number_format(($soma_media_pedido_faturamento / $i),2,",",".") ."</b></td>";
	echo "<td class='menu_top' align='right'><b>". number_format(($soma_media_faturamento_fechamento / $i),2,",",".") ."</b></td>";
	
	echo "</tr>";
	echo "</table>\n";

}else{

	echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
	echo "<tr class='table_line'>";
	echo "<td align='center'><font size='2'>Não foram encontrados registros</font></td>";
	echo "</tr>";
	echo "</table>";

}

include "rodape.php"; 

?>