<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
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

$sql2 = "SELECT descricao FROM tbl_produto_idioma WHERE idioma = 'ES' and produto = $produto";
$res2 = @pg_exec ($con,$sql2);
if (@pg_numrows($res2) > 0) $descricao_produto = @pg_result ($res2,0,descricao);

$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

$title = "REPORTES DE FALLAS DE PIEZAS";

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
function AbreDefeito(peca,data_inicial,data_final,linha,estado,produto){
	janela = window.open("relatorio_field_call_rate_defeitos.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>","peca",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerie(peca,data_inicial,data_final,linha,estado,produto){
	janela = window.open("relatorio_field_call_rate_serie.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&produto=<? echo $produto ?>","peca",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSemPeca(produto,data_inicial,data_final,linha,estado,defeito_constatado,solucao){
	janela = window.open("relatorio_field_call_rate_sem_peca.php?data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&defeito_constatado=" + defeito_constatado + "&solucao=" + solucao + "&produto=<? echo $produto ?>","peca",'scrollbars=yes,width=750,height=450,top=315,left=20');
	janela.focus();
}

</script>
</HEAD>

<BODY>
<?
echo "<table><tr><td>";
include 'relatorio_field_call_rate_pecas_grafico.php';
echo"</td></tr></table>";
?>
<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14' align = 'center'><B><? echo $title; ?></B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'><? echo $aux_data_inicial." hasta ".$aux_data_final ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='center'>HERRAMIENTA: <b><? echo $descricao_produto; ?></b></TD>
	</TR>
</TABLE>

<BR>

<?
	//	SELECT defeito_constatado, solucao_os, count(os) as qtde from tbl_os where produto=1082 and tbl_os.data_abertura BETWEEN '2006-09-01' AND '2006-09-15' group by defeito_constatado, solucao_os;

	//echo "SELECT os, defeito_constatado, solucao_os from tbl_os where produto=$produto and  tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";

unset ($cond_1, $cond_2, $cond_3, $cond_4);
//if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
if (strlen ($estado)  > 0) $cond_2 = "AND tbl_posto.estado    = '$estado' ";
if (strlen ($posto)   > 0) $cond_3 = "AND tbl_posto.posto     = $posto ";
//if (strlen ($linha)   > 0) $cond_4 = " tbl_produto.linha   = $linha ";

$sql = "SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM (SELECT tbl_os.os , 
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
					CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_extrato_extra USING (extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' 
				AND   tbl_posto.pais =  '$login_pais'";
if ($login_fabrica == 14) $sql .= "
				AND   tbl_extrato.liberado IS NOT NULL\n";
$sql .= "		AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				$cond_1
				$cond_2
				$cond_3
				$cond_4
		) fcr
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		GROUP BY fcr.com_sem " ;
# if ($ip == "201.71.54.144") { echo nl2br($sql); }

//echo "qw: $sql<BR>";
$res = pg_exec ($con,$sql);

if (is_resource($res) and pg_num_rows($res)) {
	$qtde_com = 0 ;
	$qtde_sem = 0 ;
	for($i = 0 ; $i < pg_numrows($res) ; $i++){
		if (pg_result ($res,$i,com_sem) == "COM") $qtde_com = pg_result ($res,$i,qtde);
		if (pg_result ($res,$i,com_sem) == "SEM") $qtde_sem = pg_result ($res,$i,qtde);
	}

/*	echo "<pre>$sql<br />Resultados:<br />";
	var_dump(pg_fetch_all($res));
	echo "</pre><br />\n";
*/
	$total = $qtde_com + $qtde_sem;

	$porc_com = ($qtde_com/$total) * 100;
	$porc_com = round($porc_com,0);
	$porc_sem = 100 - $porc_com;

?>
<TABLE WIDTH='250' align='center'>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>OS sin piezas :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $qtde_sem; ?></b></TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $porc_sem; ?> %</b></TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>OS con piezas :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $qtde_com; ?></b></TD>
        		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $porc_com; ?> %</b></TD>
	</TR>
	<TR>
		<TD HEIGHT='25' class='titPreto12' align='right'>Totales :</TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b><? echo $total; ?></b></TD>
		<TD HEIGHT='25' class='titPreto12' align='center'><b>100 %</b></TD>
	</TR>

</TABLE>
<br />
<?
}
//lembrando que: pode nao bater numeros, pois pode existir OS sem defeitos reclamados, constatado e solucao.

		//inicio takashi
	$xsql="SELECT tbl_os.defeito_constatado,
				tbl_defeito_constatado.descricao AS defeito_constatado_descricao, 
				tbl_os.solucao_os , 
				tbl_servico_realizado.descricao AS solucao_descricao, 
				count(os) AS qtde 
			FROM tbl_os 
			join tbl_posto         USING (posto)
			JOIN tbl_os_extra      USING (os) 
			JOIN tbl_extrato       USING (extrato)
			JOIN tbl_extrato_extra USING (extrato)
			LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado 
			LEFT JOIN tbl_servico_realizado  on tbl_os.solucao_os = tbl_servico_realizado.servico_realizado 
			WHERE tbl_os.produto = $produto 
			AND   tbl_os.fabrica = $login_fabrica 
			AND   tbl_posto.pais = '$login_pais'";
		if(strlen($estado)>0){$xsql.=" and  tbl_posto.estado = '$estado' ";}
			$xsql.=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' 
			AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) 
			AND tbl_os.os NOT IN( 
				SELECT DISTINCT(tbl_os.os) 
				FROM tbl_os 
				JOIN tbl_os_extra      USING (os)
				join tbl_posto         USING (posto)
				join tbl_extrato       USING (extrato)
				JOIN tbl_extrato_extra USING (extrato)
				JOIN tbl_produto       ON tbl_produto.produto= tbl_os.produto 
				JOIN tbl_os_produto    ON tbl_os.os= tbl_os_produto.os 
				WHERE tbl_os.fabrica = $login_fabrica 
				AND   tbl_os.produto = $produto
				AND   tbl_posto.pais = '$login_pais'
				AND   tbl_os.excluida IS NOT TRUE 
				AND (tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' )
			)GROUP BY tbl_os.defeito_constatado, tbl_defeito_constatado.descricao, tbl_os.solucao_os, tbl_servico_realizado.descricao
			ORDER BY qtde desc";
	$xres = pg_exec($con, $xsql);
//if ($ip == "201.43.247.42") { echo nl2br($xsql);}
	//echo "aqui : $xsql";
	$qtde_por_defeito= pg_numrows($xres);
	if(pg_numrows($xres) > 0){
		echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
		echo "<TR>";
		echo "<TD colspan='5' class='titChamada10' align = 'center' >OS sin piezas</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titChamada10'>Herramienta</TD>";
		echo "<TD class='titChamada10'>Defecto Comprobado</TD>";
		echo "<TD class='titChamada10'>Solución</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "<TD class='titChamada10'>Cuantidad</TD>";
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
				$defeito_constatado_descricao="OS sin defectos selecionados";
				$defeito_constatado	="00";
			}
			if($solucao_descricao==''){ $solucao	="00"; }
			if($solucao_descricao==''){$solucao_descricao="OS sin solución selecionadas";}
			$xcor = ($a % 2 == 0) ? "#FEFEFE": '#F9FCFF';
			echo "<TR bgcolor='$xcor'>";
			echo "<TD class='conteudo101'>$descricao_produto</TD>";
			echo "<TD class='conteudo101'>$defeito_constatado_descricao</TD>";
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
$sql = "SELECT tbl_os.os , tbl_os.sua_os,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
				CASE WHEN (
							SELECT tbl_os_item.os_item 
							FROM tbl_os_item 
							JOIN tbl_os_produto USING (os_produto) 
							WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1
						) IS NULL THEN 'SEM' 
				ELSE 'COM' END AS com_sem
				FROM tbl_os_extra 
				JOIN (
						SELECT tbl_os.os 
						FROM tbl_os 
						JOIN tbl_os_extra USING (os) 
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING(extrato)
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'
						AND   tbl_posto.pais = '$login_pais'
					) oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_extrato_extra USING(extrato)
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'
				AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				$cond_1
				$cond_2
				$cond_3
				$cond_4
		" ;
//echo "$sql";
//if ($ip == "201.43.203.132") { echo nl2br($sql); exit;}
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
			<TD class='titChamada10' align = 'center' >OS sin piezas</TD>
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
//mexer é com X
//se mecher nesse gráfico favor fazer a mesma alteração no relatorio_field_call_rate_pecas_grafico.php
$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia, tbl_peca_idioma.descricao AS descricao_idioma
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
						AND   tbl_posto.pais = '$login_pais'
						$cond_1
						$cond_2
						$cond_3
						$cond_4
				) fcr ON tbl_os_produto.os = fcr.os
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		LEFT JOIN tbl_peca_idioma ON tbl_peca.peca = tbl_peca_idioma.peca AND tbl_peca_idioma.idioma = '$login_idioma'
		ORDER BY pecas.qtde DESC " ;


if ($ip == "201.13.179.45") { echo nl2br($sql);}

$res = pg_exec($con, $sql);

if(pg_numrows($res) > 0){
?>
	<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>
	<TR>
		<TD class='titChamada10'>REFERENCIA</TD>
		<TD class='titChamada10'>PIEZA</TD>
		<TD class='titChamada10'>FRECUENCIA</TD>
		<TD class='titChamada10'>%</TD>
	</TR>
<?

	for ($x = 0; $x < pg_numrows($res); $x++) {
		$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
	}
	
	for($i=0; $i<pg_numrows($res); $i++){
		$peca       = pg_result($res,$i,peca);
		$referencia = pg_result($res,$i,referencia);
		$descricao_origem  = pg_result($res,$i,descricao);
		$descricao_idioma  = pg_result($res,$i,descricao_idioma);
		$ocorrencia = pg_result($res,$i,ocorrencia);

		$descricao = ($descricao_idioma) ? $descricao_idioma : $descricao_origem;
		/*if (strlen ($descricao_idioma) > 0) {
			$descricao = $descricao_idioma;
		}else{
			$descricao = $descricao_origem;
		}*/

		if ($total_ocorrencia > 0) {
			$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
		}
		
		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";
		echo "	<TD class='conteudo10' align='center'>$referencia</TD>";
		echo "	<TD class='conteudo10' align='left'>$descricao</TD>";
		echo "	<TD class='conteudo10' align='center'>$ocorrencia</TD>";
		echo "	<TD class='conteudo10' align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "</TR>";
	}

}

?>

</TABLE>

</BODY>
</HTML>
