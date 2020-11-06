<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$title = "Atendimento Call-Center"; 
$layout_menu = 'callcenter';

$admin_privilegios="call_center";
include 'autentica_admin.php';
if($login_fabrica<>6){
	header ("Location: callcenter_pendente_interativo.php");
	exit;
}

/*MARCAR O ADMIN SUPERVISOR DO CALLCENTER*/
$sql = "SELECT callcenter_supervisor from tbl_admin where fabrica = $login_fabrica and admin = $login_admin";
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0) $callcenter_supervisor = pg_result($res,0,0);

if ($callcenter_supervisor=="t") { 
	$supervisor="true";
}
/*MARCAR O ADMIN SUPERVISOR DO CALLCENTER*/


include 'cabecalho.php';
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.linha{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	color:#393940 ;
}
a.linha:link, a.linha:visited, a.linha:active{
	text-decoration: none;
	font-weight: normal;
	color: #393940;
}

a.linha:hover {
	text-decoration: underline overline; 
	color: #393940;
  }

</style>

<?
echo "<BR>";
echo "<table width='700'>";
echo "<TR >\n";
echo "<TD width='50%'>";
	echo "<table width='300' border='0' align='left' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";
	echo "<TR >\n";
	echo "<TD bgcolor='#F8615C' width='10'>&nbsp;</TD >";
	echo "<TD align='left'>Último contato a mais de 3 dias</TD >";
	echo "</TR >\n";  
	echo "<TR >\n";
	echo "<TD bgcolor='#FABD6B' width='10'>&nbsp;</TD >";
	echo "<TD align='left'>Último contato a 2 dias</TD >";
	echo "</TR >\n";
	echo "</TABLE >\n";
/*imagens_admin/cadastra_callcenter.gif
imagens_admin/consulta_callcenter.gif*/
echo "</TD >\n";
echo "<TD width='50%'>";
echo "<table width='300' border='0' align='left' cellpadding='2' cellspacing='2' style='font-size:10px'>";
	echo "<TR >\n";
	echo "<TD width='10'><a href='cadastra_callcenter.php'><img src='imagens_admin/cadastra_callcenter.gif' border='0' width='15'></a></TD >";
	echo "<TD align='left'><a href='cadastra_callcenter.php'>Cadastrar Atendimento</a></TD >";
	echo "</TR >\n";
	echo "<TR >\n";
	echo "<TD width='10'><a href='callcenter_parametros_new.php'><img src='imagens_admin/consulta_callcenter.gif' width='15' border='0'></a></TD >";
	echo "<TD align='left'><a href='callcenter_parametros_new.php'>Consultar Atendimento</a></TD >";
	echo "</TR >\n";
	echo "</TABLE >\n";

echo "</TD >\n";
echo "</TR >\n";
echo "</TABLE >\n";
echo "<BR>";

if(strlen($supervisor)>0){
	$cond1 = " 1 = 1 ";
}else{
	$cond1 = " tbl_hd_chamado.atendente = $login_admin ";
}
	$sql = "SELECT tbl_hd_chamado.hd_chamado        ,
					tbl_hd_chamado.titulo,
					tbl_hd_chamado.status,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					CASE WHEN tbl_hd_chamado_extra.nome IS NOT NULL THEN tbl_hd_chamado_extra.nome 
					ELSE tbl_cliente.nome end AS cliente_nome,
					tbl_admin.login as atendente,
					(select to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY') from tbl_hd_chamado_item  where tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado and tbl_hd_chamado_item.interno is not true order by data desc limit 1) as data_interacao ,
					tbl_hd_chamado_extra.dias_aberto,
					tbl_hd_chamado_extra.dias_ultima_interacao
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_cliente on tbl_hd_chamado_extra.cliente = tbl_cliente.cliente
			JOIN tbl_admin            on tbl_admin.admin           = tbl_hd_chamado.atendente
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND tbl_hd_chamado.status <> 'Resolvido' and tbl_hd_chamado.status <> 'Cancelado'
			AND tbl_hd_chamado.categoria <> 'Procon' AND tbl_hd_chamado.categoria <> 'Jec'
			AND $cond1
			ORDER BY data_interacao";
	$res = pg_exec($con,$sql);
#	echo nl2br($sql);
	if(pg_numrows($res)>0){
		echo "<table width='700' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
		echo "<TR >\n";
		echo "<td class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"NÚMERO ATENDIMENTO\">AT.</ACRONYM></TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'>TITULO</TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'>CLIENTE</TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'>ABERTURA</TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"ÚLTIMA INTERAÇÃO\">ÚLT.INTER</ACRONYM></TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS ABERTO\">DIAS AB.</ACRONYM></TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'><ACRONYM TITLE=\"QUANTIDADE DE DIAS ÚTEIS DA ÚLTIMA INTERAÇÃO\">DIAS INT.</ACRONYM></TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'>STATUS</TD>\n";
		echo "<TD class='menu_top' background='imagens_admin/azul.gif'>ATENDENTE</TD>\n";
		echo "</TR>\n";
		for($x=0;pg_numrows($res)>$x;$x++){
			$callcenter = pg_result($res,$x,hd_chamado);
			$titulo     = pg_result($res,$x,titulo);
			$status     = pg_result($res,$x,status);
			$data       = pg_result($res,$x,data);
			$data_interacao = pg_result($res,$x,data_interacao);
			$cliente_nome   = pg_result($res,$x,cliente_nome);
			$atendente      = pg_result($res,$x,atendente);
			$dias_aberto    = pg_result($res,$x,dias_aberto);
			$dias_ultima_interacao   = pg_result($res,$x,dias_ultima_interacao);
			if ($x % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
			if($dias_ultima_interacao == "2"){$cor = '#FABD6B';}
			if($dias_ultima_interacao >= "3"){$cor = '#F8615C';}
			echo "<TR bgcolor='$cor' onmouseover=\"this.bgColor='#F0EBC8'\" onmouseout=\"this.bgColor='$cor'\">\n";
			echo "<TD class='linha' align='center' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>$callcenter</a></TD>\n";
			echo "<TD class='linha' align='left' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>".substr($titulo,0,25)."</a></TD>\n";
			echo "<TD class='linha'  align='left' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>".substr($cliente_nome,0,17)."</a></TD>\n";
			echo "<TD class='linha' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>$data</a></TD>\n";
			echo "<TD class='linha' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>$data_interacao</a></TD>\n";
			
			echo "<TD class='linha' align=center nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>$dias_aberto</a></TD>";
			echo "<TD class='linha' align=center nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>$dias_ultima_interacao</a></TD>";

			echo "<TD class='linha' align=center nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>$status</a></TD>";
			echo "<TD class='linha' width=85 align=center><a href='cadastra_callcenter.php?callcenter=$callcenter' class='linha'>$atendente</a></TD>\n";
			echo "</TR>\n";
		
		}
		echo "</table>";
	}else{
		echo "<center>Nenhum chamado pendente!</center>";
	
	}

	$sql = "SELECT	tbl_hd_chamado_extra.dias_aberto, count(*) as qtde
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND tbl_hd_chamado.status <> 'Resolvido' and tbl_hd_chamado.status <> 'Cancelado'
			AND tbl_hd_chamado.categoria <> 'Procon' AND tbl_hd_chamado.categoria <> 'Jec'
			AND $cond1
			and dias_aberto is not null
			group by dias_aberto
			";

	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($i=0;pg_numrows($res)>$i;$i++){
			$graf_dias_aberto[] = pg_result($res,$i,dias_aberto)." dia(s) aberto(s)";
			$graf_qtde[] = pg_result($res,$i,qtde);
		}
	}
	$sql = "SELECT	tbl_hd_chamado_extra.dias_ultima_interacao, count(*) as qtde
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND tbl_hd_chamado.status <> 'Resolvido' and tbl_hd_chamado.status <> 'Cancelado'
			AND tbl_hd_chamado.categoria <> 'Procon' AND tbl_hd_chamado.categoria <> 'Jec'
			AND $cond1 
			and dias_ultima_interacao is not null
			group by dias_ultima_interacao";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($i=0;pg_numrows($res)>$i;$i++){
			$xgraf_dias_ultima_interacao[] = pg_result($res,$i,dias_ultima_interacao)." dia(s)";
			$xgraf_qtde[] = pg_result($res,$i,qtde);
		}
	
echo "<BR><BR>";
		include ("../jpgraph/jpgraph.php");
		include ("../jpgraph/jpgraph_pie.php");
		include ("../jpgraph/jpgraph_pie3d.php");
		$img = time();
		$image_graph = "png/9_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	
		$graph = new PieGraph(500,350,"auto");
		$graph->SetShadow();

		$graph->title->Set("Quantidade de dias de chamados em aberto");
//		$graph->title->Set("");
		$p1 = new PiePlot3D($graf_qtde);
		$p1->SetAngle(35);
		$p1->SetSize(0.4);
		$p1->SetCenter(0.4,0.7); // x.y
		//$p1->SetLegends($gDateLocale->GetShortMonth());
		$p1->SetLegends($graf_dias_aberto);
//		$p1->SetSliceColors(array('blue','red',));
		$graph->Add($p1);
		$graph->Stroke($image_graph);
		echo "\n\n<img src='$image_graph'>\n\n";
echo "<BR><BR>";

		$ximage_graph = "png/10_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	
		$graph = new PieGraph(500,350,"auto");
		$graph->SetShadow();

		$graph->title->Set("Quantidade de dias da última interação");
//		$graph->title->Set("");
		$p1 = new PiePlot3D($xgraf_qtde);
		$p1->SetAngle(35);
		$p1->SetSize(0.4);
		$p1->SetCenter(0.5,0.5); // x.y
		//$p1->SetLegends($gDateLocale->GetShortMonth());
		$p1->SetLegends($xgraf_dias_ultima_interacao);
//		$p1->SetSliceColors(array('blue','red',));
		$graph->Add($p1);
		$graph->Stroke($ximage_graph);
		echo "\n\n<img src='$ximage_graph'>\n\n";
}
include "rodape.php"; 

?>
