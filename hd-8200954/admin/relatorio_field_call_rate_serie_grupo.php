<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$data_inicial = $_GET['data_inicial'];
$data_final   = $_GET['data_final'];
$produto      = $_GET['produto'];
$peca         = $_GET['peca'];
$linha        = $_GET['linha'];
$estado       = $_GET['estado'];
$familia      = $_GET['familia'];
$tipo         = $_GET['tipo'];
$tipo_os = $_GET['tipo_os'];

$cond_conversor = "";;

if (!empty($_GET["dcg"]) and $_GET["dcg"] == "true") {
    $cond_conversor = " AND tbl_os.defeito_constatado <> 23118 AND tbl_os.solucao_os <> 4504 ";
}

if($tipo=="produto"){
$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);
}else{
$descricao_produto = "$produto";
}
$sql = "SELECT descricao,referencia FROM tbl_peca WHERE peca = $peca";
$res = pg_exec($con,$sql);
$descricao_peca = pg_result($res,0,descricao);
$referencia_peca = substr(pg_result($res,0,referencia),0,10);

$aux_data_inicial = substr($data_inicial,8,2)."/".substr($data_inicial,5,2)."/".substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)."/".substr($data_final,5,2)."/".substr($data_final,0,4);

$title = "Números de Série que apresentaram defeito";


//include "relatorio_field_call_rate_nserie_grafico.php";

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
	text-align: left;
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
	text-align: left;
	font:10px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>

</HEAD>

<BODY>

<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14'><B><? echo $title; ?></B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'><? echo $aux_data_inicial." até ".$aux_data_final ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
	<table align = 'center'>
		<TR>
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PRODUTO: <b><? echo $descricao_produto; ?></b></TD>
		</TR>
		<TR>
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PEÇA: <b><? echo $descricao_peca; ?></b></TD>
		</TR>
	</table>
</TABLE>
<BR>

<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>
<TR>
	<TD class='titChamada10'>OS</TD>
	<TD class='titChamada10'>ABERTURA</TD>
	<TD class='titChamada10'>POSTO</TD>
	<TD class='titChamada10'># SÉRIE</TD>
</TR>

<?



$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
$cond_5 = "1=1";
if (strlen ($tipo_os) > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";

if (strlen ($produto) > 0) $cond_1 = " tbl_os.produto      = $produto ";
if (strlen ($peca)    > 0) $cond_2 = " tbl_os_item.peca    = $peca ";

$sql ="SELECT  tbl_os.os                                                     , 
				tbl_os.sua_os                                                 ,
				TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura  , tbl_posto_fabrica.codigo_posto                                ,
				tbl_posto.nome                                                ,
				tbl_os.serie
		FROM (SELECT tbl_os.os,
				( SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1 ) AS status 
			FROM tbl_os 
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
			join tbl_os_produto on tbl_os.os = tbl_os_produto.os
			join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND $cond_5
			AND tbl_os.excluida IS NOT TRUE 
			AND tbl_os.produto = $produto
			AND tbl_extrato.fabrica = $login_fabrica 
			and tbl_servico_realizado.troca_de_peca is true 
			and $cond_2
		) AS fcr
		JOIN tbl_os using(os)
		JOIN tbl_posto using(posto)
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		ORDER BY tbl_os.serie, tbl_os.data_abertura";
if($tipo=="grupo"){
$sql ="SELECT  tbl_os.os                                                     , 
				tbl_os.sua_os                                                 ,
				TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura  , tbl_posto_fabrica.codigo_posto                                ,
				tbl_posto.nome                                                ,
				tbl_os.serie
		FROM (SELECT tbl_os.os,
				( SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1 ) AS status 
			FROM tbl_os 
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto 
			join tbl_os_produto on tbl_os.os = tbl_os_produto.os
			join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND $cond_5
			AND tbl_os.excluida IS NOT TRUE 
			AND tbl_produto.referencia_fabrica = '$produto' 
			AND tbl_extrato.fabrica = $login_fabrica 
			and tbl_servico_realizado.troca_de_peca is true 
			and $cond_2
		) AS fcr
		JOIN tbl_os using(os)
		JOIN tbl_posto using(posto)
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
		ORDER BY tbl_os.serie, tbl_os.data_abertura";
}



$sql ="SELECT  tbl_os.os                                                     , 
				tbl_os.sua_os                                                 ,
				TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura  , tbl_posto_fabrica.codigo_posto                                ,
				tbl_posto.nome                                                ,
				tbl_os.serie
		FROM (SELECT tbl_os.os
			FROM tbl_os 
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
			join tbl_os_produto on tbl_os.os = tbl_os_produto.os
			join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND $cond_5
			AND tbl_os.excluida IS NOT TRUE 
			AND tbl_os.produto = $produto
			AND tbl_extrato.fabrica = $login_fabrica 
			and tbl_servico_realizado.troca_de_peca is true 
			and $cond_2
			$cond_conversor
		) AS fcr
		JOIN tbl_os using(os)
		JOIN tbl_posto using(posto)
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		ORDER BY tbl_os.serie, tbl_os.data_abertura";
if($tipo=="grupo"){
$sql ="SELECT  tbl_os.os                                                     , 
				tbl_os.sua_os                                                 ,
				TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura  , tbl_posto_fabrica.codigo_posto                                ,
				tbl_posto.nome                                                ,
				tbl_os.serie
		FROM (SELECT tbl_os.os
			FROM tbl_os 
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os 
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato 
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto 
			join tbl_os_produto on tbl_os.os = tbl_os_produto.os
			join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIn tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND $cond_5
			AND tbl_os.excluida IS NOT TRUE 
			AND tbl_produto.referencia_fabrica = '$produto' 
			AND tbl_extrato.fabrica = $login_fabrica 
			and tbl_servico_realizado.troca_de_peca is true 
			and $cond_2
			$cond_conversor
		) AS fcr
		JOIN tbl_os using(os)
		JOIN tbl_posto using(posto)
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
		ORDER BY tbl_os.serie, tbl_os.data_abertura";
}
//echo '<br><br>'.nl2br($sql);



$res = pg_exec($con, $sql);
if(pg_numrows($res) > 0){

	for($i=0; $i<pg_numrows($res); $i++){
		$os				= pg_result($res,$i,os);
		$sua_os			= pg_result($res,$i,sua_os);
		$data_abertura	= pg_result($res,$i,data_abertura);
		$codigo_posto	= pg_result($res,$i,codigo_posto);
		$nome			= pg_result($res,$i,nome);
		
		$serie			= pg_result($res,$i,serie);

		$cor = '2';
		if ($i % 2 == 0) $cor = '1';

		echo "<TR class='bgTRConteudo$cor'>";
		echo "	<TD class='conteudo10' align='left'><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
		echo "	<TD class='conteudo10' align='left'>$data_abertura</TD>";
		echo "	<TD class='conteudo10' align='left'>$codigo_posto - $nome</TD>";
		echo "	<TD class='conteudo10' align='left'><B>". substr($serie,0,4) . "</b>". substr($serie,4,12) . "</TD>";
		echo "</TR>";

	}
echo "</table>";
}
?>

</TABLE>

<p>
<center>
Ocorrências: <? echo $i ?>
</center>
<?include ("relatorio_field_call_rate_serie_grafico_grupo.php"); ?>
<BR><BR>
<? 
if(1==1){
include "relatorio_field_call_rate_nserie_grafico_continuo.php"; 
}else{
include "relatorio_field_call_rate_nserie_grafico.php"; 
}
?>
<BR><BR>

<?
$sql = "SELECT 	to_char(tbl_acao_corretiva.data, 'DD/MM/YYYY') as data,
				tbl_acao_corretiva.descricao           ,
				tbl_acao_corretiva.radical_serie
		FROM tbl_acao_corretiva
		WHERE tbl_acao_corretiva.fabrica = $login_fabrica
		and tbl_acao_corretiva.produto = $produto order by radical_serie";
if($tipo=="grupo"){
$sql = "SELECT 	to_char(tbl_acao_corretiva.data, 'DD/MM/YYYY') as data,
				tbl_acao_corretiva.descricao           ,
				tbl_acao_corretiva.radical_serie
		FROM  tbl_acao_corretiva
		WHERE tbl_acao_corretiva.fabrica = $login_fabrica
		AND   tbl_acao_corretiva.referencia_fabrica ='$produto' order by radical_serie";
}
//echo $sql;
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
echo "<center><font size='1' face='verdana'>Ação Corretiva</font></center>";
	echo "<table width='400' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>";
	echo "<tr>";
	echo "<td  class='titChamada10' width='50'>Data</td>";
	echo "<td class='titChamada10' width='80'>Radical</td>";
	echo "<td class='titChamada10'>Descrição</td>";
	echo "</tr>";
	for($x=0;pg_numrows($res)>$x;$x++){
		$data      = pg_result($res,$x,data);
		$descricao = pg_result($res,$x,descricao);
		$radical   = pg_result($res,$x,radical_serie);
	echo "<tr>";
	echo "<td class='conteudo10' >$data</td>";
	echo "<td class='conteudo10' >$radical</td>";
	echo "<td class='conteudo10' >$descricao</td>";
	echo "</tr>";
	}
echo "</table>";
}

?>
</BODY>
</HTML>
