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
$pais         = $_GET['pais'];
if ($login_fabrica <> 20) $pais = 'BR';
$familia      = $_GET['familia'];
$posto        = $_GET['posto'];
$consumidor_revenda = $_GET['consumidor_revenda'];
// Alterado por Paulo - chamado : 3195
if($login_fabrica == 20 and $pais != 'BR'){
	$sql = "SELECT descricao FROM tbl_produto_idioma WHERE produto = $produto";
}else{
	$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
}
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
function AbreDefeito(peca,data_inicial,data_final,linha,estado,pais,produto){
	janela = window.open("relatorio_field_call_rate_defeitos_latinatec.php?peca=" + peca + "&data_inicial=" + data_inicial + "&consumidor_revenda=<? echo $consumidor_revenda?>&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado  + "&pais=" + pais + "&produto=<? echo $produto ?>","peca",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSerie(peca,data_inicial,data_final,linha,estado,pais,produto){
	janela = window.open("relatorio_field_call_rate_serie_latinatec.php?peca=" + peca + "&data_inicial=" + data_inicial + "&consumidor_revenda=<? echo $consumidor_revenda?>&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&pais=" + pais + "&produto=<? echo $produto ?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
function AbreSemPeca(produto,data_inicial,data_final,linha,estado,pais,defeito_constatado,solucao){
	janela = window.open("relatorio_field_call_rate_sem_peca_latinatec.php?data_inicial=" + data_inicial + "&consumidor_revenda=<? echo $consumidor_revenda?>&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&pais=" + pais + "&defeito_constatado=" + defeito_constatado + "&solucao=" + solucao + "&produto=<? echo $produto ?>","peca",'resizable=1,scrollbars=yes,width=750,height=450,top=315,left=20');
	janela.focus();
}

</script>
</HEAD>

<BODY>
<?
echo "<table><tr><td align='center'>";
include 'relatorio_field_call_rate_pecas_grafico_latinatec.php';
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
	//	SELECT defeito_constatado, solucao_os, count(os) as qtde from tbl_os where produto=1082 and tbl_os.data_abertura BETWEEN '2006-09-01' AND '2006-09-15' group by defeito_constatado, solucao_os;

	//echo "SELECT os, defeito_constatado, solucao_os from tbl_os where produto=$produto and  tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";
$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
$cond_5 = "1=1";
$cond_6 = "1=1";
	
#if (strlen ($familia) > 0) $cond_1 = " tbl_produto.familia = $familia ";
if (strlen ($estado)  > 0) $cond_2 = " tbl_posto.estado    = '$estado' ";
if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
if (strlen ($linha)   > 0 and 1==2) $cond_4 = " tbl_produto.linha   = $linha ";
if (strlen ($consumidor_revenda)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$consumidor_revenda' ";
if (strlen ($pais)   > 0) $cond_6 = " tbl_posto.pais     = '$pais' ";

if($login_fabrica == 20 and $pais != 'BR'){
	$tipo_data = " tbl_extrato.data_geracao ";
}else{
	if($login_fabrica == 20)
		$tipo_data = " tbl_extrato_extra.exportado ";
	else
		$tipo_data = " tbl_extrato.data_geracao ";
}
/*
$sql = "SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM (SELECT tbl_os.os , 
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
					CASE WHEN (
						SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   $tipo_data BETWEEN '$data_inicial' AND '$data_final' ";

if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";

$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				AND   $cond_1
				AND   $cond_2
				AND   $cond_3
				AND   $cond_4
				AND   $cond_5 ";

if  ($login_fabrica == 20 and $login_admin == 590 and $pais != 'BR') $sql .= "AND   $cond_6 ";

$sql .= ") fcr
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		GROUP BY fcr.com_sem  ";
*/


$sql = "SELECT COUNT(*) AS qtde , fcr.com_sem
		FROM (
			SELECT tbl_os.os , 
				(
					SELECT status_os FROM tbl_os_status 
					WHERE tbl_os_status.os = tbl_os_extra.os 
					ORDER BY data DESC LIMIT 1
				) AS status ,
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
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					WHERE tbl_os.produto = $produto 
					AND $tipo_data BETWEEN '$data_inicial' AND '$data_final'
			) oss            ON oss.os = tbl_os_extra.os
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_posto   ON tbl_os.posto    = tbl_posto.posto AND tbl_posto.pais = '$pais'
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   $tipo_data BETWEEN '$data_inicial' AND '$data_final' ";

if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";

$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				AND   $cond_1
				AND   $cond_2
				AND   $cond_3
				AND   $cond_4
				AND   $cond_5 ";

if  ($login_fabrica == 20 ) $sql .= "AND   $cond_6 ";

$sql .= ") fcr
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		GROUP BY fcr.com_sem  ";


if($login_fabrica == 24){
	$sql= " SELECT COUNT(*) AS qtde , fcr.com_sem
			FROM (
				SELECT tbl_os.os , 
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
						JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
						WHERE tbl_os.produto = $produto 
						AND $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
					) oss            ON oss.os = tbl_os_extra.os
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
					JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_posto   ON tbl_os.posto    = tbl_posto.posto AND tbl_posto.pais='BR'
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' AND   tbl_os.excluida IS NOT TRUE
					AND   tbl_os.produto = $produto
					AND   $cond_1
					AND   $cond_2
					AND   $cond_3
					AND   $cond_4
					AND   $cond_5
			) fcr
			GROUP BY fcr.com_sem  ";
}


$res = pg_exec ($con,$sql);
echo nl2br($sql);
$qtde_com = 0 ;
$qtde_sem = 0 ;
for($i = 0 ; $i < pg_numrows($res) ; $i++){
	if (pg_result ($res,$i,com_sem) == "COM") $qtde_com = pg_result ($res,$i,qtde);
	if (pg_result ($res,$i,com_sem) == "SEM") $qtde_sem = pg_result ($res,$i,qtde);
}


	$total = $qtde_com + $qtde_sem;
if ($qtde_com > 0){
	$porc_com = ($qtde_com/$total) * 100;
}
else
	$porc_com = 0;
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
//lembrando que: pode nao bater numeros, pois pode existir OS sem defeitos reclamados, constatado e solucao.
	$ajoin = " tbl_servico_realizado.descricao AS solucao_descricao, ";
	$join = "LEFT JOIN tbl_servico_realizado on tbl_os.solucao_os = tbl_servico_realizado.servico_realizado";
	$bjoin = " tbl_servico_realizado.descricao ";
if($login_fabrica==6 or $login_fabrica==15){
	$ajoin = " tbl_solucao.descricao AS solucao_descricao, ";
	$join = "LEFT JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao";
	$bjoin = " tbl_solucao.descricao ";
}
if ($login_fabrica == 20 and $pais !='BR'){
	$ajoin = " tbl_servico_realizado_idioma.descricao AS solucao_descricao_espanhol, ";
	$join = "  LEFT JOIN tbl_servico_realizado_idioma on tbl_os.solucao_os = tbl_servico_realizado_idioma.servico_realizado";
	$bjoin = " tbl_servico_realizado_idioma.descricao ";
}
		//inicio takashi
	$xsql="SELECT tbl_os.defeito_constatado,";
		if ($login_fabrica == 20 and $pais != 'BR'){
			$xsql .=" tbl_defeito_constatado_idioma.descricao AS defeito_constatado_descricao_espanhol,";
		}
		else {
			$xsql .=" tbl_defeito_constatado.descricao AS defeito_constatado_descricao,"; 
		}	
			$xsql .= " tbl_os.solucao_os , 
					 $ajoin
					count(os) AS qtde 
					FROM tbl_os 
					join tbl_posto on tbl_os.posto=tbl_posto.posto 
					JOIN tbl_os_extra using(os) 
					JOIN tbl_extrato using (extrato) 
					JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato";
		if ($login_fabrica == 20 and $pais != 'BR'){
			$xsql .= " LEFT JOIN tbl_defeito_constatado_idioma on tbl_os.defeito_constatado=tbl_defeito_constatado_idioma.defeito_constatado ";
		}
		else {
			$xsql .=" LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado "; 
		}	
					
			$xsql .="$join
					WHERE tbl_os.produto=$produto 
					AND tbl_os.fabrica=$login_fabrica ";
		if(strlen($estado)>0){$xsql.=" and  tbl_posto.estado = '$estado' ";}
		if($login_fabrica == 20 ){$xsql.=" and  tbl_posto.pais = '$pais' ";}
			$xsql.=" AND $tipo_data BETWEEN '$data_inicial' AND '$data_final' 
			AND (tbl_os_extra.status_os NOT IN (13,15) OR tbl_os_extra.status_os IS NULL) 
			AND tbl_os.os NOT IN( 
				SELECT DISTINCT(tbl_os.os) 
				FROM tbl_os 
				JOIN tbl_os_extra using(os) 
				join tbl_extrato using (extrato)
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
				JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
				WHERE tbl_os.fabrica=$login_fabrica 
				AND tbl_os.produto=$produto
				AND tbl_os.excluida IS NOT TRUE 
				AND ($tipo_data BETWEEN '$data_inicial' AND '$data_final' )
			)GROUP BY tbl_os.defeito_constatado, ";
		if($login_fabrica == 20 and $pais != 'BR')
			$xsql.=" tbl_defeito_constatado_idioma.descricao, tbl_os.solucao_os,";
		else
			$xsql.="tbl_defeito_constatado.descricao, tbl_os.solucao_os,";
			$xsql.="$bjoin
					ORDER BY qtde desc";
//if($ip=="201.92.126.18")echo $xsql;
/*######################## takashi 05-12-06
Liberar apenas qdo todas as OS estiverem 
com o relacionamento de integridade
Lembrando que antigamente a solucao_os era na tbl_servico_realizado
agora com a integridade pega na tbl_solucao
##############################################*/
	if($login_fabrica==24){
	$xsql="SELECT tbl_os.defeito_constatado,
				tbl_defeito_constatado.descricao AS defeito_constatado_descricao, 
				tbl_os.solucao_os , 
				tbl_solucao.descricao AS solucao_descricao, 
				count(os) AS qtde 
			FROM tbl_os 
			join tbl_posto on tbl_os.posto=tbl_posto.posto 
			JOIN tbl_os_extra using(os) 
			JOIN tbl_extrato using (extrato) 
			LEFT JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado 
			LEFT JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			WHERE tbl_os.produto=$produto 
			AND tbl_os.fabrica=$login_fabrica 
			AND   $cond_5 ";
		if(strlen($estado)>0){$xsql.=" and  tbl_posto.estado = '$estado' ";}
			$xsql.=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND tbl_os.os NOT IN( 
				SELECT DISTINCT(tbl_os.os) 
				FROM tbl_os 
				JOIN tbl_os_extra using(os) 
				join tbl_extrato using (extrato)
				JOIN tbl_produto ON tbl_produto.produto= tbl_os.produto 
				JOIN tbl_os_produto ON tbl_os.os= tbl_os_produto.os 
				WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_os.produto=$produto
				AND tbl_os.excluida IS NOT TRUE 
				AND (tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' )
			)GROUP BY tbl_os.defeito_constatado, tbl_defeito_constatado.descricao, tbl_os.solucao_os, tbl_solucao.descricao
			ORDER BY qtde desc";


	}
//echo "$sql"; exit;
//if ($ip == "201.42.44.239") { echo nl2br($xsql);}
	$xres = pg_exec($con, $xsql);
//if ($ip == "201.68.13.116") { echo nl2br($xsql);}
	//echo "aqui : $xsql";
	echo nl2br($xsql);
	$qtde_por_defeito= pg_numrows($xres);
	if(pg_numrows($xres) > 0){
		echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
		echo "<TR>";
		echo "<TD colspan='5' class='titChamada10' align = 'center' >OS sem peças</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titChamada10'>Produto</TD>";
		echo "<TD class='titChamada10'>Defeito Constatado</TD>";
		echo "<TD class='titChamada10'>Solucao</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "<TD class='titChamada10'>Quantidade</TD>";
		echo "</TR>";
		for($a=0; $a<pg_numrows($xres); $a++){
			$defeito_constatado				= pg_result($xres,$a,defeito_constatado);
			$solucao	= pg_result($xres,$a,solucao_os);
			if ($login_fabrica == 20 and $pais != 'BR'){
			$defeito_constatado_descricao	= pg_result($xres,$a,defeito_constatado_descricao_espanhol);
			$solucao_descricao				= pg_result($xres,$a,solucao_descricao_espanhol);
			}
			else {
			$defeito_constatado_descricao	= pg_result($xres,$a,defeito_constatado_descricao);
			$solucao_descricao				= pg_result($xres,$a,solucao_descricao);
			}
			$qtde							= pg_result($xres,$a,qtde);
			if ($qtde_sem > 0) {
				$xporcentagem = ($qtde * 100)/$qtde_sem;
			} else {
				$xporcentagem = 0;
			}
		//	$xporcentagem = round($xporcentagem,0);
			if($defeito_constatado_descricao==''){
				$defeito_constatado_descricao="Os sem defeito selecionados";
				$defeito_constatado	="00";
			}
			if($solucao_descricao=='' and $login_fabrica<>15){ $solucao	="00"; }
			if($solucao_descricao==''){
				if($login_fabrica==3 AND 1==2){
					$xxsql = "select solucao, descricao from tbl_solucao where fabrica=$login_fabrica";;
					$xxres = pg_exec($con, $xxsql);
					$solucao	= pg_result($xxres,0,solucao);
					$solucao_descricao				= pg_result($xxres,0,descricao);
				}else{
					$solucao_descricao="Os sem solução selecionadas";
				}
			}
			$xcor = ($a % 2 == 0) ? "#FEFEFE": '#F9FCFF';
			echo "<TR bgcolor='$xcor'>";
			echo "<TD class='conteudo101'>$descricao_produto</TD>";
			/*if($login_fabrica == 20 and $pais !='BR'){
			echo "<TD class='conteudo101'><a href='javascript:AbreSemPeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$defeito_constatado\",\"$solucao\")'>$defeito_constatado_descricao</A></TD>";
			}
			else{*/
			echo "<TD class='conteudo101'><a href='javascript:AbreSemPeca(\"$produto\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\",\"$defeito_constatado\",\"$solucao\")'>$defeito_constatado_descricao</A></TD>";
			//}
			/*if($login_fabrica == 20 and $pais !='BR') { echo "<TD class='conteudo101'>$solucao_descricaoes</TD>"; }
			else {*/ echo "<TD class='conteudo101'>$solucao_descricao</TD>"; //}
			echo "<TD class='conteudo101' align='center'>". number_format($xporcentagem,2,",",".") ."</TD>";
			echo "<TD class='conteudo101' align='center'>$qtde</TD>";
			echo "</TR>";
		}
		echo "</TABLE><BR><BR>";
	}
//fim takashi	
	//echo "$sql"; exit;	
/////////////////
		
if($login_fabrica<>14){
$sql = "SELECT tbl_os.os , tbl_os.sua_os,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status ,
				CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
				FROM tbl_os_extra 
				JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final') oss ON oss.os = tbl_os_extra.os
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
				JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   $tipo_data BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= " AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				AND   $cond_1
				AND   $cond_2
				AND   $cond_3
				AND   $cond_4
		" ;
if  ($login_fabrica == 20 and $pais != 'BR') {
		$sql .= "AND   $cond_6 ";
	}
	if($login_fabrica==24){
		$sql = "SELECT tbl_os.os , tbl_os.sua_os,
						CASE WHEN (SELECT tbl_os_item.os_item FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os_extra.os LIMIT 1) IS NULL THEN 'SEM' ELSE 'COM' END AS com_sem
						FROM tbl_os_extra 
						JOIN (SELECT tbl_os.os FROM tbl_os JOIN tbl_os_extra USING (os) JOIN tbl_extrato USING (extrato) WHERE tbl_os.produto = $produto AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59') oss ON oss.os = tbl_os_extra.os
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
						AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
				" ;
	}

//if ($ip == "201.42.44.239") { echo nl2br($sql);}
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

//se mexer nesse gráfico favor fazer a mesma alteração no relatorio_field_call_rate_pecas_grafico.php
$sql = "SELECT tbl_peca.referencia, ";
	if($login_fabrica == 20 and $pais !='BR'){
		$sql .=" tbl_peca_idioma.descricao AS descricao_espanhol, ";
		$join_pc_idioma="LEFT JOIN tbl_peca_idioma on tbl_peca_idioma.peca = tbl_peca.peca";
	}else{
		$sql .=" tbl_peca.descricao, ";
		$join_pc_idioma="";
	}



	$sql .=" tbl_peca.peca, pecas.qtde AS ocorrencia
			 FROM tbl_peca 
			 $join_pc_idioma
			 JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
			 FROM tbl_os_item
			 JOIN tbl_os_produto USING (os_produto)

			 JOIN   (SELECT tbl_os.os , 
					(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
					FROM tbl_os_extra
					JOIN tbl_extrato USING (extrato)
					JOIN tbl_extrato_extra USING (extrato)
					JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   ";
if($login_fabrica == 20 and $pais !='BR')  $sql .=	" tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
else
if($login_fabrica == 20)  $sql .=	" tbl_extrato_extra.exportado BETWEEN '$data_inicial' AND '$data_final' ";
else                      $sql .=	" tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";

$sql .= "AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5 ";
if  ($login_fabrica == 20 and $pais != 'BR') {
		$sql .= "AND   $cond_6 ";
}
$sql .= ") fcr ON tbl_os_produto.os = fcr.os
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;

if($login_fabrica==24){
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
			FROM tbl_peca
			JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
					FROM tbl_os_item
					JOIN tbl_os_produto USING (os_produto)
					JOIN   (SELECT tbl_os.os 
							FROM tbl_os_extra
							JOIN tbl_extrato USING (extrato)
							JOIN tbl_extrato_extra USING (extrato)
							JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
							JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND    tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
							AND   tbl_os.excluida IS NOT TRUE
							AND   tbl_os.produto = $produto
							AND   $cond_1
							AND   $cond_2
							AND   $cond_3
							AND   $cond_4
							AND   $cond_5 
					) fcr ON tbl_os_produto.os = fcr.os
					GROUP BY tbl_os_item.peca
			) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;
}
//	if ($ip == "189.18.99.251") { echo nl2br($sql);}
//echo "sql: $sql";
//exit;
$res = pg_exec($con, $sql);

if(pg_numrows($res) > 0){
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
		if($login_fabrica == 20 and $pais !='BR')
			$descricao  = pg_result($res,$i,descricao_espanhol);		
		else
			$descricao  = pg_result($res,$i,descricao);
		$ocorrencia = pg_result($res,$i,ocorrencia);

		if ($total_ocorrencia > 0) {
			$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
		}
	
		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";
		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\")'>$referencia</a></TD>";
		echo "	<TD class='conteudo10' align='left'>$descricao</TD>";
		echo "	<TD class='conteudo10' align='center'>$ocorrencia</TD>";
		echo "	<TD class='conteudo10' align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
		echo "	<TD class='conteudo10' align='center'><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$pais\")'>#série</a></TD>";
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
