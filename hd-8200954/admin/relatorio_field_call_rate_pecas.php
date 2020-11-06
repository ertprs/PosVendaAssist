<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$linha        = $_GET['linha'];
$estado       = $_GET['estado'];
$familia      = $_GET['familia'];
$posto        = $_GET['posto'];

$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
//if ($ip == "201.0.9.216") { echo nl2br($sql);}
$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);

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

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

<script>
function AbreDefeito(peca,data_inicial,data_final,linha,estado,produto){
	janela = window.open("relatorio_field_call_rate_defeitos.php?peca=" + peca +
"&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" +
linha + "&estado=" + estado + "&produto=<? echo $produto
?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=50,left=50');
	janela.focus();
}
function AbreSerie(peca,data_inicial,data_final,linha,estado,produto){
	janela = window.open("relatorio_field_call_rate_serie.php?peca=" + peca +
"&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" +
linha + "&estado=" + estado + "&produto=<? echo $produto
?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=50,left=50');
	janela.focus();
}
</script>
</HEAD>

<BODY>

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

$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
#if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
if (strlen ($estado)  > 0) $cond_2 = " tbl_posto.estado    = '$estado' ";
if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
#if (strlen ($linha)   > 0) $cond_4 = " tbl_produto.linha   = $linha ";

$sql = "SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM (SELECT tbl_os.os , 
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
					CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				AND   $cond_1
				AND   $cond_2
				AND   $cond_3
				AND   $cond_4
		) fcr
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		GROUP BY fcr.com_sem " ;
//if ($ip == "201.43.11.216") { echo nl2br($sql); exit;}

$res = pg_exec ($con,$sql);

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
		<TD HEIGHT='25' class='titPreto12' align='right'>Totais :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $total; ?></b></TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b>100 %</b></TD>
	</TR>

</TABLE>

<br>

<?
/////////////////
if($login_fabrica<>14){
$sql = "SELECT tbl_os.os , tbl_os.sua_os ,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
				CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra 
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				AND   $cond_1
				AND   $cond_2
				AND   $cond_3
				AND   $cond_4
		" ;

//if ($ip == "201.43.11.216") { echo nl2br($sql); exit;}
////////////////
$res = pg_exec($con, $sql);

if(pg_numrows($res) > 0){

	$imprime		= null;
	for($i=0; $i<pg_numrows($res); $i++){
		$os				= pg_result($res,$i,os);
		$sua_os			= pg_result($res,$i,sua_os);
		$com_sem		= pg_result($res,$i,com_sem);
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
/* TAKASHI PARA PEGAR DEFEITOS
$sql="SELECT 	tbl_os.os																, 
					tbl_os.sua_os														,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao		, 
					tbl_defeito_constatado.descricao as defeito_constatado_descricao	,
					tbl_servico_realizado.descricao as solucao							,
					tbl_os.fabrica														,
 					tbl_os.produto
			FROM tbl_os 
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado=tbl_os.defeito_reclamado
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado=tbl_os.defeito_constatado
			JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado=tbl_os.solucao_os
			WHERE (tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final')   
			AND tbl_os.fabrica=$login_fabrica 
			AND tbl_os.produto=$produto
			AND tbl_os.data_fechamento notnull 
			AND tbl_os.os NOT IN(
								SELECT DISTINCT(tbl_os.os) 
									FROM tbl_os 
									JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
									JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
									WHERE tbl_os.fabrica=$login_fabrica 
									and tbl_os.produto=$produto 
									AND (tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final')
									) order by tbl_defeito_reclamado.descricao, tbl_defeito_constatado.descricao, tbl_servico_realizado.descricao";*/
}
//mexer é com X
//se mecher nesse gráfico favor fazer a mesma alteração no relatorio_field_call_rate_pecas_grafico.php
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca
		JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os , 
						      (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
				) fcr ON tbl_os_produto.os = fcr.os
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;

// if ($ip == "201.13.179.45") { echo nl2br($sql);}

$res = pg_exec($con, $sql);

if(pg_numrows($res) > 0){
	$total = 0;
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

	for ($x = 0; $x < pg_numrows($res); $x++) {
		$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
	}
	
	for($i=0; $i<pg_numrows($res); $i++){
		$peca       = pg_result($res,$i,peca);
		$referencia = pg_result($res,$i,referencia);
		$descricao  = pg_result($res,$i,descricao);
		$ocorrencia = pg_result($res,$i,ocorrencia);

		if ($total_ocorrencia > 0) {
			$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
		}
		
		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";
		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\")'>$referencia</a></TD>";
		echo "	<TD class='conteudo10' align='left'>$descricao</TD>";
		echo "	<TD class='conteudo10' align='center'>$ocorrencia</TD>";
		echo "	<TD class='conteudo10' align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\")'>#série</a></TD>";
		echo "</TR>";

		$total = $ocorrencia + $total;
	}
	//echo "<tr><td colspan='4'>TOTAL</td><td>$total</td></tr>";

}

?>

</TABLE>
<?
echo "<table><tr><td>";
include 'relatorio_field_call_rate_pecas_grafico.php';
echo"</td></tr></table>";
?>
</BODY>
</HTML>
