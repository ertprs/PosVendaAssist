<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "monitora_cabecalho.php";
$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$linha        = $_GET['linha'];
$estado       = $_GET['estado'];
$familia      = $_GET['familia'];
$posto        = $_GET['posto'];
$tipo         = $_GET['tipo'];
$tipo_os = $_GET['tipo_os'];
$cond_5 = " 1=1 ";
if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
if($tipo=="produto"){
$sql = "SELECT tbl_produto.descricao 
		FROM tbl_produto
		JOIN tbl_familia using(familia)
		WHERE produto = $produto
		and fabrica = $login_fabrica";
//if ($ip == "201.0.9.216") { echo nl2br($sql);}
$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);
}else{
$descricao_produto = "$produto";
}
$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

$title = "RELATÓRIO DE QUEBRA DE PEÇAS";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style type="text/css">

.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titPreto12 {
	color: #000000;
	/*text-align: left;*/
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo10 {
	color: #000000;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}
.conteudo101{
	color: #000000;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}
.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

<script>
function AbreDefeito(peca,data_inicial,data_final,linha,estado,produto,tipo){

	janela = window.open("relatorio_field_call_rate_defeitos_grupo.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>&tipo=" + tipo  + "&tipo_os=<?echo $tipo_os;?>","defeito",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerie(peca,data_inicial,data_final,linha,estado,produto,tipo){

	janela = window.open("relatorio_field_call_rate_serie_grupo.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>&tipo="+tipo  + "&tipo_os=<?echo $tipo_os;?>","serie",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerieGrafico(peca,data_inicial,data_final,linha,estado,produto,tipo){
	janela = window.open("relatorio_field_call_rate_nserie_grafico.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>"  + "&tipo_os=<?echo $tipo_os;?>","grafico",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSemPeca(produto,data_inicial,data_final,linha,estado,defeito_constatado,solucao,tipo){

	janela = window.open("relatorio_field_call_rate_sem_peca_grupo.php?data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&defeito_constatado=" + defeito_constatado + "&solucao=" + solucao + "&produto=<? echo $produto ?>&tipo="+ tipo  + "&tipo_os=<?echo $tipo_os;?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=20');
	janela.focus();
}

</script>
</HEAD>

<BODY>
<?



echo "<table align='center'><tr><td align='center'>";
include 'relatorio_field_call_rate_pecas_grafico_grupo.php';
echo "</td></tr></table>";
?>
<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14' align = 'center'><B><? echo $title; ?></B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'><? echo $aux_data_inicial." até ".$aux_data_final ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='center'>PRODUTO: <b><? echo $descricao_produto; ?></b></TD>
	</TR>
</TABLE>

<BR>

<?
if($tipo=="produto"){
$sql = "SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM (SELECT tbl_os.os , 
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
					CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				AND $cond_5
		) fcr
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		GROUP BY fcr.com_sem " ;
}else{
$sql = "SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM (SELECT tbl_os.os , 
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
					CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) JOIN tbl_produto on tbl_os.produto = tbl_produto.produto WHERE tbl_produto.referencia_fabrica='$produto' AND tbl_produto.ativo='t' AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' AND   tbl_os.excluida IS NOT TRUE
				AND $cond_5
				AND tbl_produto.referencia_fabrica='$produto'
		) fcr
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		GROUP BY fcr.com_sem " ;
}
//if($ip=="201.68.13.116")echo nl2br($sql); 

//echo "qw: $sql<BR>";
$res = pg_exec ($con,$sql);
if(pg_numrows($res)==0){
echo "<center>Nenhuma ocorrência</center>";
exit;
}
$qtde_com = 0 ;
$qtde_sem = 0 ;
for($i = 0 ; $i < pg_numrows($res) ; $i++){
	if (pg_result ($res,$i,com_sem) == "COM") $qtde_com = pg_result ($res,$i,qtde);
	if (pg_result ($res,$i,com_sem) == "SEM") $qtde_sem = pg_result ($res,$i,qtde);
}


$total = $qtde_com + $qtde_sem;

$porc_com = ($qtde_com/$total) * 100;
$porc_com = round($porc_com,0);
$porc_sem = 100 - $porc_com;

?>

<TABLE WIDTH='250' align='center'>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>OS sem peças :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $qtde_sem; ?></b></TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $porc_sem; ?> %</b></TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>OS com peças :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $qtde_com; ?></b></TD>
        		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $porc_com; ?> %</b></TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>Total :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $total; ?></b></TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b>100 %</b></TD>
	</TR>

</TABLE>

<br>

<?

include "relatorio_field_call_rate_def_constatado_grafico.php";
echo "<BR><BR>";


if($login_fabrica==24){
	if($tipo=="produto"){
		$xsql="SELECT 
				defeito_constatado,
				defeito_constatado_descricao,
				solucao_os ,
				solucao_descricao,
				count(os) AS qtde
				FROM(
				SELECT tbl_os.defeito_constatado,
				tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
				tbl_os.solucao_os ,
				tbl_solucao.descricao AS solucao_descricao,
				tbl_os.os,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status_2
				FROM tbl_os
				join tbl_posto on tbl_os.posto=tbl_posto.posto 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_extrato using (extrato) 
				LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado 
				LEFT JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
				WHERE tbl_os.produto = $produto 
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND $cond_5
				AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) 
				AND tbl_os.os NOT IN( 
					SELECT DISTINCT(tbl_os.os) 
					FROM tbl_os 
					JOIN tbl_os_extra using(os) 
					join tbl_extrato using (extrato)
					JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
					JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
					WHERE tbl_os.fabrica = $login_fabrica 
					AND tbl_os.produto = $produto
					AND tbl_os.excluida IS NOT TRUE 
					AND (tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' )
				))as fcr
				where fcr.status_2 not in (13,15) or status_2 is null
				GROUP BY 
				defeito_constatado,
				defeito_constatado_descricao,
				solucao_os ,
				solucao_descricao
				ORDER BY qtde desc";
	}else{
		$xsql="SELECT 
				defeito_constatado,
				defeito_constatado_descricao,
				solucao_os ,
				solucao_descricao,
				count(os) AS qtde
				FROM(
				SELECT tbl_os.defeito_constatado,
				tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
				tbl_os.solucao_os ,
				tbl_solucao.descricao AS solucao_descricao,
				tbl_os.os,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status_2
				FROM tbl_os
				join tbl_posto on tbl_os.posto=tbl_posto.posto 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_extrato using (extrato) 
				JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
				LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado 
				LEFT JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
				WHERE tbl_produto.referencia_fabrica='$produto' AND tbl_produto.ativo='t' 
				AND tbl_os.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND $cond_5
				AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) 
				AND tbl_os.os NOT IN( 
					SELECT DISTINCT(tbl_os.os) 
					FROM tbl_os 
					JOIN tbl_os_extra using(os) 
					join tbl_extrato using (extrato)
					JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
					JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
					WHERE tbl_os.fabrica = $login_fabrica 
					AND tbl_produto.referencia_fabrica='$produto' AND ativo='t'
					AND tbl_os.excluida IS NOT TRUE 
					AND (tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' )
				))as fcr
				where fcr.status_2 not in (13,15) or status_2 is null
				GROUP BY 
				defeito_constatado,
				defeito_constatado_descricao,
				solucao_os ,
				solucao_descricao
				ORDER BY qtde desc";
	}
}


	$xres = pg_exec($con, $xsql);
//echo nl2br($xsql);
//exit;
	//echo "aqui : $xsql";
	$qtde_por_defeito= pg_numrows($xres);
	if(pg_numrows($xres) > 0){
		echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
		echo "<TR>";
		echo "<TD colspan='5' class='titChamada10' align = 'center' >OS sem peças</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titChamada10'>Produto</TD>";
		echo "<TD class='titChamada10'>Defeito Constatado</TD>";
		echo "<TD class='titChamada10'>Solução</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "<TD class='titChamada10'>Quantidade</TD>";
		echo "</TR>";
		for($a=0; $a<pg_numrows($xres); $a++){
			$defeito_constatado				= pg_result($xres,$a,defeito_constatado);
			$solucao	= pg_result($xres,$a,solucao_os);
			$defeito_constatado_descricao	= pg_result($xres,$a,defeito_constatado_descricao);
			$solucao_descricao				= pg_result($xres,$a,solucao_descricao);
			$qtde							= pg_result($xres,$a,qtde);
			$xporcentagem = ($qtde * 100)/$qtde_sem;
		//	$xporcentagem = round($xporcentagem,0);
			if($defeito_constatado_descricao==''){
				$defeito_constatado_descricao="Os sem defeito selecionado";
				$defeito_constatado	="00";
			}
			if($solucao_descricao==''){ $solucao	="00"; }
			if($solucao_descricao==''){
				$solucao_descricao="Os sem solução selecionada";

			}
			$xcor = ($a % 2 == 0) ? "#FEFEFE": '#F9FCFF';
			echo "<TR bgcolor='$xcor'>";
			echo "<TD class='conteudo101'>$descricao_produto</TD>";
 			echo "<TD class='conteudo101'><a href='javascript:AbreSemPeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$defeito_constatado\",\"$solucao\",\"$tipo\")'>$defeito_constatado_descricao</A></TD>";
			echo "<TD class='conteudo101'>$solucao_descricao</TD>";
			echo "<TD class='conteudo101' align='center'>". number_format($xporcentagem,2,",",".") ."</TD>";
			echo "<TD class='conteudo101' align='center'>$qtde</TD>";
			echo "</TR>";
		}
		echo "</TABLE><BR><BR>";
	}
//fim takashi	
		
/////////////////
		
if($login_fabrica<>14){
if($tipo=="produto"){
$sql = "SELECT tbl_os.os , tbl_os.sua_os,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
				CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra 
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto" ;
}else{
$sql = "SELECT tbl_os.os , tbl_os.sua_os,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
				CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra 
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto in(select produto from tbl_produto where referencia_fabrica='$produto' AND ativo='t') AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_produto.referencia_fabrica='$produto' AND tbl_produto.ativo='t' " ;



}

//echo "$sql";
//echo nl2br($sql); 
////////////////
$res = pg_exec($con, $sql);

if(pg_numrows($res) > 0){

	$imprime		= null;
	for($i=0; $i<pg_numrows($res); $i++){
		$os				= pg_result($res,$i,os);
		$sua_os			= pg_result($res,$i,sua_os);
		$com_sem		= pg_result($res,$i,com_sem);
	
	if($login_fabrica<>6 and 1==2){
		if(($com_sem == 'SEM') and ($imprime == null)) {
			$imprime = 1;
?>

			<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>
			<TR>
			<TD class='titChamada10' align = 'center' >OS sem peças</TD>
			</TR>
			
<?
		}

		if ($com_sem == 'SEM') {
			$cor = '2';
			if ($i % 2 == 0) $cor = '1';

			echo "<TR class='bgTRConteudo$cor'>";
			echo "<TD class='conteudo10' align='center'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
			echo "</TR>";
			
		}
	}
}

}

}

if(pg_numrows($yres) > 0){
?>
	<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>
	<TR>
		<TD class='titChamada10'>REFERÊNCIA</TD>
		<TD class='titChamada10'>PEÇA</TD>
		<TD class='titChamada10'>OCORRÊNCIAS</TD>
		<TD class='titChamada10'>%</TD>
		<TD class='titChamada10'># Série</TD>
	</TR>
<?

	for ($x = 0; $x < pg_numrows($yres); $x++) {
		$total_ocorrencia = $total_ocorrencia + pg_result($yres,$x,ocorrencia);
	}
	
	for($i=0; $i<pg_numrows($yres); $i++){
		$peca       = pg_result($yres,$i,peca);
		$referencia = pg_result($yres,$i,referencia);
		$descricao  = pg_result($yres,$i,descricao);
		$ocorrencia = pg_result($yres,$i,ocorrencia);

		if ($total_ocorrencia > 0) {
			$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
		}
	
		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";

		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"\",\"$tipo\")'>$referencia</a></TD>";
		echo "	<TD class='conteudo10' align='left'>$descricao</TD>";
		echo "	<TD class='conteudo10' align='center'>$ocorrencia</TD>";
		echo "	<TD class='conteudo10' align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"\",\"$tipo\")'>#série</a></TD>";
// <a href='javascript:AbreSerieGrafico(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$produto\")'>#série</a> 
		echo "</TR>";
	}
		echo "<TR >";
		echo "	<TD class='titChamada10' align='center' colspan='2'><B>TOTAL</b></TD>";
		echo "	<TD class='titChamada10' align='center'>$total_ocorrencia</TD>";
		echo "	<TD class='titChamada10' align='center'>&nbsp</TD>";
		echo "	<TD class='titChamada10' align='center'>&nbsp</TD>";
		echo "</TR>";
echo "</table>";

}

?>

</TABLE>

</BODY>
</HTML>
<? include "monitora_rodape.php";?>