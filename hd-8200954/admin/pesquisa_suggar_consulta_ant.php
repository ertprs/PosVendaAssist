<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";
include ("../jpgraph/jpgraph.php");
include ("../jpgraph/jpgraph_pie.php");
include ("../jpgraph/jpgraph_pie3d.php");
include ("../jpgraph/jpgraph_bar.php");

//////////////////////////////////////////
/*if($ip<>"201.27.30.119" ){
echo "programa em manunteção";
exit;
}*/
$msg = "";

// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");


$layout_menu = "callcenter";
$title       = "Pesquisa Satisfação";

include "cabecalho.php";
?>


<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<br>

<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="2">Selecione o produto para verificar o resultado da pesquisa</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="2">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
	<td align='right'>Produto</td>
	<td>
<?
$sql = "select tbl_produto.referencia,
				tbl_produto.descricao, tbl_produto.produto
		FROM tbl_produto
		JOIN tbl_os on tbl_os.produto = tbl_produto.produto
		JOIN tbl_suggar_questionario on tbl_suggar_questionario.os= tbl_os.os
		GROUP BY
			tbl_produto.referencia,
			tbl_produto.descricao, tbl_produto.produto
		ORDER BY
			tbl_produto.referencia";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
//	echo $sql;
	echo "<Select name='produto' value='1' style='width:350px'>";
	for($x=0;$x<pg_numrows($res);$x++){
		$xproduto = pg_result($res,$x,produto);
		$produto_referencia = pg_result($res,$x,referencia);
		$produto_descricao = pg_result($res,$x,descricao);
		echo "<option value='$xproduto' "; if($produto==$xproduto){echo "SELECTED";} 
		echo "> $produto_referencia - $produto_descricao</option>";
	}
	echo "</select>";
}else {
echo "Nenhum resultado";
}

?>
	</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
	<td align='right'>Notas</td>
	<td align='center'><a href='<?echo "$PHP_SELF?media_posto=true";?>'>Média por Postos Independente do Produto</a></td>
	<td>
	</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="6" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>
<Br><BR>
<?
$media_posto = $_GET['media_posto'];
if(strlen($media_posto)>0){

$sql = "SELECT sum(tbl_suggar_questionario.nota) as nota,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto,
				count(tbl_os.posto) as qtde
		FROM tbl_suggar_questionario
		JOIN tbl_os using(os)
		JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
		AND tbl_posto_fabrica.fabrica = $login_fabrica
		GROUP BY 
			tbl_posto.nome,
			tbl_posto_fabrica.codigo_posto
		order by nota";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='500' align='center' style='font-family: verdana; font-size: 11px'>";
		echo "<tr>";
		echo "<td bgcolor='#d2d7e1' align='center' width='60%'><B>Posto</B></td>";
		echo "<td bgcolor='#d2d7e1' align='center' width='20'><B>Pesquisa</B></td>";
		echo "<td bgcolor='#d2d7e1' align='center' width='20'><B>Média Nota</B></td>";
		echo "</tr>";
	for($i=0;$i<pg_numrows($res);$i++){
		$nota         = pg_result($res,$i,nota);
		$nome         = pg_result($res,$i,nome);
		$codigo_posto = pg_result($res,$i,codigo_posto);
		$qtde         = pg_result($res,$i,qtde);
		$nota = $nota / $qtde;

		$medias[$i]= $nota;
		$posto[$i]=$codigo_posto;
		echo "<tr>";
		echo "<td bgcolor='#ffffff' align='left' width='60%' nowrap>$codigo_posto - $nome</td>";
		echo "<td bgcolor='#ffffff' align='center' width='20'>$qtde</td>";
		echo "<td bgcolor='#ffffff' align='center' width='20'>$nota</td>";
		echo "</tr>";
	}
	echo "</table><BR><BR>";
/*
http://doc.async.com.br/jpgraph/html/exframes/frame_horizbarex1.html
*/
$img = time();
$image_graph = "png/21_$img.png";

// seleciona os dados das médias
setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	

// Size of graph
$width=450;
$height=400;

// Set the basic parameters of the graph
$graph = new Graph($width,$height,'auto');
$graph->SetScale("textlin");

// Rotate graph 90 degrees and set margin
$graph->Set90AndMargin(50,20,50,30);

// Nice shadow
$graph->SetShadow();

// Setup title
$graph->title->Set("Notas médias por postos");
//$graph->title->SetFont(FF_VERDANA,FS_BOLD,14);
//$graph->subtitle->Set("(No Y-axis)");

// Setup X-axis
$graph->xaxis->SetTickLabels($posto);
//$graph->xaxis->SetFont(FF_VERDANA,FS_NORMAL,12);

// Some extra margin looks nicer
$graph->xaxis->SetLabelMargin(10);

// Label align for X-axis
$graph->xaxis->SetLabelAlign('right','center');

// Add some grace to y-axis so the bars doesn't go
// all the way to the end of the plot area
$graph->yaxis->scale->SetGrace(20);

// We don't want to display Y-axis
$graph->yaxis->Hide();

// Now create a bar pot
$bplot = new BarPlot($medias);
$bplot->SetFillColor("orange");
$bplot->SetShadow();

//You can change the width of the bars if you like
//$bplot->SetWidth(0.5);

// We want to display the value of each bar at the top
$bplot->value->Show();
//$bplot->value->SetFont(FF_ARIAL,FS_BOLD,12);
$bplot->value->SetAlign('left','center');
$bplot->value->SetColor("black","darkred");
$bplot->value->SetFormat('%.1f média');

// Add the bar to the graph
$graph->Add($bplot);

// .. and stroke the graph
//$graph->Stroke();
$graph->Stroke($image_graph);
echo "\n\n<img src='$image_graph'>\n\n";


}

	
}


$btn_acao = $_POST['acao'];
$produto = $_POST['produto'];
if(strlen($btn_acao)>0){
$sql = "select tbl_produto.referencia,
				tbl_produto.descricao, tbl_produto.produto
		FROM tbl_produto
					where produto = $produto";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$produto_referencia = pg_result($res,0,referencia);
	$produto_descricao = pg_result($res,0,descricao);
}
//echo $produto;


$sql = "select count(questionario) as total 
		from tbl_suggar_questionario
		join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto";
$res = pg_exec($con,$sql);
$total = pg_result($res,0,0);


/*
select tbl_os.os, tbl_os.produto, tbl_os.consumidor_nome from tbl_suggar_questionario join tbl_os using(os) where tbl_os.produto = 20690;


*/



echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
echo "<td  colspan='5'><font color='#FFFFFF' ><B>Total de consumidores consultados $total</B></FONT></td>";
echo "</tr>";
echo "<tr>";
echo "<td bgcolor='#d2d7e1' align='center' width='60%'><B>Pergunta</B></td>";
echo "<td bgcolor='#d2d7e1' align='center' width='40'><B>Sim</B></td>";
echo "<td bgcolor='#d2d7e1' align='center' width='40'><B>Não</B></td>";
echo "<td bgcolor='#d2d7e1' align='center' width='55' nowrap><B>Sim %</B></td>";
echo "<td bgcolor='#d2d7e1' align='center' width='55' nowrap><B>Não %</B></td>";
echo "</tr>";
echo "</table>";

echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
echo "<td bgcolor='#e7e1d2' align='left' colspan='5'><B>O que levou a escolher $produto_descricao ?</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						preco is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by preco;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$escolha[0]=$sim;
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);


echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>a. Foi o preço</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						qualidade is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by qualidade;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
}
	$escolha[1]=$sim;
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>b. Foi a qualidade</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";


	$sql = "select 	case when 
						design is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by design;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
}
	$escolha[2]=$sim;
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>c. Foi o design</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						tradicao is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by tradicao;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
}
	$escolha[3]=$sim;
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>d. Foi a tradição da marca</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						indicacao is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by indicacao;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
}
	$escolha[4]=$sim;
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>e. Foi por indicação</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						tbl_suggar_questionario.capacidade is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by tbl_suggar_questionario.capacidade;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$escolha[5]=$sim;
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>f. Foi pela capacidade</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						inovacao is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by inovacao;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$escolha[6]=$sim;
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>g. Foi por inovação</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
echo "</table>";
echo "<BR>";
if (1 == 1) {
	// nome da imagem
	$img = time();
	$image_graph = "png/25_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	$legenda_escolha = array("Preço","Qualidade","Design","Tradição Marca","Indicação","Inovação");
	
	$graph = new PieGraph(500,300,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("Motivo compra produto Suggar");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	$p1 = new PiePlot3D($escolha);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.35);
	$p1->SetLegends($legenda_escolha);
	
	$graph->Add($p1);
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
echo "<BR>";


echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
echo "<td bgcolor='#e7e1d2' align='left' colspan='5'><B>Com relação ao produto $produto_descricao</B></td>";
echo "</tr>";
	$sql = "select 	case when 
						satisfeito is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by satisfeito;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

	$satisfeito_sim_nao[] = $sim ;
	$satisfeito_sim_nao[] = $nao ;

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left' width='60%'>a. Satisfeito?</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
echo "</table>";
echo "<BR>";
if (1 == 1) {


	// nome da imagem
	$img = time();
	$image_graph = "png/26_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');

	$legenda_satisfeito_sim_nao = array("Sim","Não");

	$graph = new PieGraph(500,300,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("Satisfeito?");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	$p1 = new PiePlot3D($satisfeito_sim_nao);
	$p1->SetSize(0.5);
	$p1->SetCenter(0.45);
	$p1->SetLegends($legenda_satisfeito_sim_nao);
	
	$graph->Add($p1);
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
echo "<BR>";
echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
echo "<td bgcolor='#e7e1d2' align='left' colspan='5'><B>b. Se satisfeito: Sua satisfação é com relação</B></td>";
echo "</tr>";
	$sql = "select 	case when satisfeito_modo_usar is true then 'sim' 
						when satisfeito_modo_usar is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='t'
			group by satisfeito_modo_usar;";
	//echo $sql;
	$res = pg_exec($con,$sql);

	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	$satisfeito[0] = $sim;
	echo "</table>";

echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>i. Modo de usar o produto</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
	$sql = "select 	case when satisfeito_manual is true then 'sim' 
							when satisfeito_manual is false then 'nao'
						end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='t'
			group by satisfeito_manual;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){

	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	$satisfeito[1] = $sim;
	
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>ii. Manual de orientação</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
	$sql = "select 	case when 
						satisfeito_energia is true then 'sim' 
						 when 
						satisfeito_energia is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario  
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='t'
			group by satisfeito_energia;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	$satisfeito[2] = $sim;

echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>iii. Consumo de energia</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						satisfeito_barulho is true then 'sim' 
						 when 
						satisfeito_barulho is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='t'
			group by satisfeito_barulho;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	$satisfeito[3] = $sim;
	

	echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>iv. Nível de ruído</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						satisfeito_cor is true then 'sim' 
						 when 
						satisfeito_cor is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='t'
			group by satisfeito_cor;";
	//echo $sql;
	$res = pg_exec($con,$sql);

	$sim=0;
	$nao=0;
if(pg_numrows($res)>0){

	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	$satisfeito[4] = $sim;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>v. Cor do produto</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
echo "</table>";
echo "<br>";
if (1 == 1) {


	// nome da imagem
	$img = time();
	$image_graph = "png/27_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');

	$legenda_satisfeito = array("Modo de usar o produto","Manual de orientação","Consumo de energia","Nível de ruído","Cor do produto");

	$graph = new PieGraph(500,300,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("Satisfação em relação");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	$p1 = new PiePlot3D($satisfeito);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.35);
	$p1->SetLegends($legenda_satisfeito);
	
	$graph->Add($p1);
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
echo "<BR>";
echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
echo "<td bgcolor='#e7e1d2' align='left' colspan='5'><B>b. Se insatisfeito: Sua insatisfação é com relação</B></td>";
echo "</tr>";
	$sql = "select 	case when 
						insatisfeito_modo_usar is true then 'sim' 
						 when 
						insatisfeito_modo_usar is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='f'
			group by insatisfeito_modo_usar;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){

		for($x=0;pg_numrows($res)>$x;$x++){
			if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
			if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
		}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	}

	$insatisfeito[0] = $sim;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>i. Modo de usar o produto</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
	
	$sql = "select 	case when 
						insatisfeito_manual is true then 'sim' 
						 when 
						insatisfeito_manual is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='f'
			group by insatisfeito_manual;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	}

	$insatisfeito[1] = $sim;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>ii. Manual de orientação</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						insatisfeito_energia is true then 'sim' 
						when 
						insatisfeito_energia is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='f'
			group by insatisfeito_energia;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	}

	$insatisfeito[2] = $sim;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>iii. Consumo de energia</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						insatisfeito_barulho is true then 'sim' 
						when 
						insatisfeito_barulho is true then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='f'
			group by insatisfeito_barulho;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	}

	$insatisfeito[3] = $sim;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>iv. Nível de ruído</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						insatisfeito_cor is true then 'sim' 
						when 
						insatisfeito_cor is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='f'
			group by insatisfeito_cor;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);

	$insatisfeito[4] = $sim;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>v. Cor do produto</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
	}
	$sql = "select 	case when 
						insatisfeito_quebra_uso is true then 'sim' 
						when 
						insatisfeito_quebra_uso is false then 'nao' 
					end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			where satisfeito='f'
			group by insatisfeito_quebra_uso;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);


	$insatisfeito[5] = $sim;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>vi. Quebrou com pouco uso</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
echo "</table>";
echo "<br>";
if (1 == 1) {


	// nome da imagem
	$img = time();
	$image_graph = "png/28_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');

	$legenda_insatisfeito = array("Modo de usar o produto","Manual de orientação","Consumo de energia","Nível de ruído","Cor do produto","Quebrou com pouco uso");

	$graph = new PieGraph(500,300,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("Insatisfação em relação");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	$p1 = new PiePlot3D($insatisfeito);
	$p1->SetSize(0.4);
	$p1->SetCenter(0.35);
	$p1->SetLegends($legenda_insatisfeito);
	
	$graph->Add($p1);
	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";

//////////////////////////////////////////
}
}
echo "<BR>";
echo "<table border='0' cellpadding='4' cellspacing='1'  bgcolor='#596D9B' width='600' align='center' style='font-family: verdana; font-size: 11px'>";
echo "<tr>";
echo "<td bgcolor='#e7e1d2' align='left' colspan='5'><B>Com relação ao atendimento da autorizada</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						atendimento_rapido is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by atendimento_rapido;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	$atendimento_rapido[] = $sim;
	$atendimento_rapido[] = $nao;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>a. O atendimento foi rápido?</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='55'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						confianca is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by confianca;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
	$confianca[] = $sim;
	$confianca[] = $nao;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>b. O aspecto da loja, gerou confiança?</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";

	$sql = "select 	case when 
						problema_resolvido is true then 'sim' 
						else 'nao' end as sim_nao , 
					count(questionario) as qtde 
			from tbl_suggar_questionario 
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto
			group by problema_resolvido;";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$sim=0;
	$nao=0;
	if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		if(pg_result($res,$x,sim_nao)=="sim")$sim = pg_result($res,$x,qtde);
		if(pg_result($res,$x,sim_nao)=="nao")$nao = pg_result($res,$x,qtde);
	}
	}
	$xtotal = $sim + $nao;
	$xsim = (($sim*100)/$xtotal);
	$xnao = (($nao*100)/$xtotal);
$problema_resolvido[] = $sim;
$problema_resolvido[] = $nao;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>c. O problema foi resolvido?</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$sim</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>$nao</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xsim,1,",",".")."%</B></td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40'><B>".number_format($xnao,1,",",".")."%</B></td>";
echo "</tr>";
	$sql = "select 	sum(nota) as nota
			from tbl_suggar_questionario
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto";
//	echo $sql;
	$res = pg_exec($con,$sql);
	$nota = pg_result($res,0,0);
	$nota = $nota/$total;
	$nota = $nota;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>d. De 0 a 10, qual nota daria ao posto autorizado?</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40' colspan='4'><B>Média: ".number_format($nota,2,",",".")."</B></td>";
echo "</tr>";
	$sql = "select 	sum(nota_produto) as nota_produto
			from tbl_suggar_questionario
			join tbl_os on tbl_os.os = tbl_suggar_questionario.os and tbl_os.produto = $produto";
	//echo $sql;
	$res = pg_exec($con,$sql);
	$nota_produto = pg_result($res,0,0);
	$nota_produto = $nota_produto/$total;
	$nota_produto = $nota_produto;
echo "<tr>";
echo "<td bgcolor='#FFFFFF' align='left'>e. De 0 a 10, qual nota daria a(o) $produto_descricao?</td>";
echo "<td bgcolor='#FFFFFF' align='center' width='40' colspan='4'><B>Média: ".number_format($nota_produto,2,",",".")."</B></td>";
echo "</tr>";
echo "</table>";

echo "<Br><BR>";
/*GRAFICO ESCOLHA*/

include "pesquisa_suggar_grafico_escolha.php";
/*GRAFICO ESCOLHA*/
}
?>

<br>

<? include "rodape.php" ?>
