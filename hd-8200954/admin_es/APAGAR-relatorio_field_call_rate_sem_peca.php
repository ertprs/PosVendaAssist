<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

//echo "<Br>"; echo 
$data_inicial = $_GET['data_inicial'];
//echo "<Br>";echo 
$data_final   = $_GET['data_final'];
//echo "<Br>produto";echo 
$produto      = $_GET['produto'];
//echo "<Br>linha";echo 
//$linha        = $_GET['linha'];
//echo "<Br>estado"; echo 
$estado       = $_GET['estado'];
//echo "<Br>defeito_constatado: "; echo 
$defeito_constatado       = $_GET['defeito_constatado'];
//echo "<BR>solucao: ";echo 
$solucao       = $_GET['solucao'];


$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
//if ($ip == "201.0.9.216") { echo nl2br($sql);}
$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);

$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

$title = "RELATÓRIO DE OS SEM PEÇA";

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
$sql = "SELECT * from (
			SELECT tbl_os.os, 
				tbl_os.sua_os,
				tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
				tbl_os.defeito_constatado,
				tbl_defeito_constatado.descricao AS defeito_constatado_descricao, 
				tbl_os.solucao_os , ";
				if($login_fabrica<>24) {$sql .= "tbl_servico_realizado.descricao AS solucao_descricao, ";}else{
			$sql .= " tbl_solucao.descricao as solucao_descricao, ";}
				$sql .= " tbl_produto.descricao as produto_nome,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	,
				tbl_posto.nome as posto_nome,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC 	LIMIT 1) AS status_2
			FROM tbl_os 
			join tbl_posto using (posto)
			JOIN tbl_os_extra using(os) 
			JOIN tbl_extrato using (extrato)
			join tbl_produto using (produto)
			join tbl_defeito_reclamado using(defeito_reclamado) 
			left JOIN tbl_defeito_constatado on tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado ";
			if($login_fabrica<>24) {$sql .= " left JOIN tbl_servico_realizado on tbl_os.solucao_os = tbl_servico_realizado.servico_realizado ";}else{
			$sql .= "left JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao and tbl_solucao.fabrica = $login_fabrica ";			}
			$sql .=" WHERE tbl_os.produto=$produto AND tbl_os.fabrica=$login_fabrica ";
			if($defeito_constatado=="00"){$sql.=" AND tbl_os.defeito_constatado is null";}else{$sql.=" AND tbl_os.defeito_constatado=$defeito_constatado ";}
			if($solucao=="00"){$sql .=" AND tbl_os.solucao_os is null";
				}else{$sql.=" AND tbl_os.solucao_os=$solucao ";}
			if(strlen($estado)>0){$sql.=" and  tbl_posto.estado = '$estado' ";}
			$sql.=" AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 

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
			)
			) as X where X.status_2 not in (13,15) or status_2 is null";
//if ($ip == "189.18.36.103") { echo nl2br($sql);}
	$res = @pg_exec($con, $sql);
	$qtde = pg_numrows($res);
	if(pg_numrows($res)>0){
	echo "<BR><BR><center><font size='1'>Foram encontradas $qtde OS sem peça.</font></center><BR>";
		echo "<table width='700' border='0' bgcolor='#485989' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 10px'>";
		echo "<tr height='25'>";
		echo "<td><font color='#FFFFFF'><B>OS</B></font></td>";
		//echo "<td><font color='#FFFFFF'><B>Posto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Produto</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Abertura</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Fechamento</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Defeito Reclamado</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Defeito Constatado</B></font></td>";
		echo "<td><font color='#FFFFFF'><B>Solução</B></font></td>";
		echo "</tr>";
		for ($i=0; $i<pg_numrows($res); $i++){
			$os								= trim(pg_result($res,$i,os));
			$sua_os							= trim(pg_result($res,$i,sua_os));
			$defeito_reclamado_descricao 	= trim(pg_result($res,$i,defeito_reclamado_descricao));
			$defeito_constatado_descricao 	= trim(pg_result($res,$i,defeito_constatado_descricao));
			$solucao_descricao 				= trim(pg_result($res,$i,solucao_descricao));
			$abertura 						= trim(pg_result($res,$i,abertura));
			$fechamento 					= trim(pg_result($res,$i,fechamento));
			$posto_nome 					= trim(pg_result($res,$i,posto_nome));
			$produto_descricao				= trim(pg_result($res,$i,produto_nome));

			
			
			
			$cor = ($y % 2 == 0) ? "#FFFFFF": '#f4f7fb';
			echo "<tr bgcolor='$cor'>";
			echo "<td align='left'><a href='os_press.php?os=$os' target='blank'>$sua_os</A></td>";
			//echo "<td align='left'>$posto_nome</td>";
			echo "<td align='left'>$produto_descricao</td>";
			echo "<td>$abertura</td>";
			echo "<td>$fechamento</td>";
			echo "<td align='left'>$defeito_reclamado_descricao</td>";
			echo "<td align='left'>$defeito_constatado_descricao</td>";
			echo "<td align='left'>$solucao_descricao</td>";
			echo "</tr>";
}
		echo "</table>";
}else{
	echo "<center>Nenhuma Ordem de Serviço encontrada.</center>";
}


?>
</BODY>
</HTML>
