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
$tipo         = $_GET['tipo'];
$com_sem      = $_GET['com_sem'];
$tipo_os      = $_GET['tipo_os'];
$cond_5 = " 1=1 ";
if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";

if($tipo=="produto"){

$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
//if ($ip == "201.0.9.216") { echo nl2br($sql);}
$res = pg_exec($con,$sql);
$descricao_produto = pg_result($res,0,descricao);
}else{
$descricao_produto = "$produto";
}
$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);
if($com_sem =='com'){
	$title = "RELATÓRIO DE OS COM PEÇA";
}else{
	$title = "RELATÓRIO DE OS SEM PEÇA";
}
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
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

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

$sql = "SELECT 	tbl_os.os                                                            ,
				tbl_os.sua_os                                                        ,
				fcr.defeito_constatado                                               ,
				fcr.defeito_constatado_descricao                                     ,
				fcr.solucao                                                          ,
				fcr.solucao_descricao                                                ,
				tbl_produto.descricao as produto_nome                                ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		 ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	 ,
				tbl_posto.nome as posto_nome                                         ,
				fcr.defeito_reclamado_descricao
		FROM (
			SELECT tbl_os.os ,
					tbl_defeito_constatado.defeito_constatado as defeito_constatado,
					tbl_defeito_constatado.descricao as defeito_constatado_descricao,
					tbl_solucao.solucao ,
					tbl_solucao.descricao as solucao_descricao,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					CASE WHEN(
							SELECT tbl_servico_realizado.troca_de_peca
							FROM tbl_os_produto
							JOIN tbl_os_item using(os_produto)
							JOIN tbl_servico_realizado using(servico_realizado)
							WHERE tbl_os_produto.os = tbl_os.os limit 1 ) IS TRUE
					THEN 'com' ELSE 'sem' END AS com_sem ,
					( SELECT status_os
						FROM tbl_os_status
						WHERE tbl_os_status.os = tbl_os_extra.os
						ORDER BY data DESC LIMIT 1
					) AS status
			FROM tbl_os
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' 
			AND tbl_os.produto = $produto 
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_defeito_constatado.defeito_constatado = $defeito_constatado
			and tbl_solucao.solucao = $solucao
			AND $cond_5
		) as fcr
		JOIN tbl_os on tbl_os.os = fcr.os
		JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
		JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL) and fcr.com_sem='$com_sem'";
//echo $sql;
}else{
$sql = "SELECT 	tbl_os.os                                                            ,
				tbl_os.sua_os                                                        ,
				fcr.defeito_constatado                                               ,
				fcr.defeito_constatado_descricao                                     ,
				fcr.solucao                                                          ,
				fcr.solucao_descricao                                                ,
				tbl_produto.descricao as produto_nome                                ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		 ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	 ,
				tbl_posto.nome as posto_nome                                         ,
				fcr.defeito_reclamado_descricao
		FROM (
			SELECT tbl_os.os ,
					tbl_defeito_constatado.defeito_constatado as defeito_constatado,
					tbl_defeito_constatado.descricao as defeito_constatado_descricao,
					tbl_solucao.solucao ,
					tbl_solucao.descricao as solucao_descricao,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					CASE WHEN(
							SELECT tbl_servico_realizado.troca_de_peca
							FROM tbl_os_produto
							JOIN tbl_os_item using(os_produto)
							JOIN tbl_servico_realizado using(servico_realizado)
							WHERE tbl_os_produto.os = tbl_os.os limit 1 ) IS TRUE
					THEN 'com' ELSE 'sem' END AS com_sem ,
					( SELECT status_os
						FROM tbl_os_status
						WHERE tbl_os_status.os = tbl_os_extra.os
						ORDER BY data DESC LIMIT 1
					) AS status
			FROM tbl_os
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' 
			AND tbl_produto.referencia_fabrica = '$produto' 
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_defeito_constatado.defeito_constatado = $defeito_constatado
			and tbl_solucao.solucao = $solucao
			AND $cond_5
		) as fcr
		JOIN tbl_os on tbl_os.os = fcr.os
		JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
		JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL) and fcr.com_sem='$com_sem'";
}


//echo nl2br($sql); echo "<BR>=================<BR>";
if($tipo=="produto"){

$sql = "SELECT 	tbl_os.os                                                            ,
				tbl_os.sua_os                                                        ,
				fcr.defeito_constatado                                               ,
				fcr.defeito_constatado_descricao                                     ,
				fcr.solucao                                                          ,
				fcr.solucao_descricao                                                ,
				tbl_produto.descricao as produto_nome                                ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		 ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	 ,
				tbl_posto.nome as posto_nome                                         ,
				fcr.defeito_reclamado_descricao
		FROM (
			SELECT tbl_os.os ,
					tbl_defeito_constatado.defeito_constatado as defeito_constatado,
					tbl_defeito_constatado.descricao as defeito_constatado_descricao,
					tbl_solucao.solucao ,
					tbl_solucao.descricao as solucao_descricao,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					CASE WHEN(
							SELECT tbl_servico_realizado.troca_de_peca
							FROM tbl_os_produto
							JOIN tbl_os_item using(os_produto)
							JOIN tbl_servico_realizado using(servico_realizado)
							WHERE tbl_os_produto.os = tbl_os.os limit 1 ) IS TRUE
					THEN 'com' ELSE 'sem' END AS com_sem 
			FROM tbl_os
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
			AND tbl_os.produto = $produto 
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_defeito_constatado.defeito_constatado = $defeito_constatado
			and tbl_solucao.solucao = $solucao
			AND tbl_extrato.liberado is not null
			AND tbl_extrato.total > 0
			AND $cond_5
		) as fcr
		JOIN tbl_os on tbl_os.os = fcr.os
		JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
		JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		WHERE fcr.com_sem='$com_sem'";
//echo $sql;
}else{
$sql = "SELECT 	tbl_os.os                                                            ,
				tbl_os.sua_os                                                        ,
				fcr.defeito_constatado                                               ,
				fcr.defeito_constatado_descricao                                     ,
				fcr.solucao                                                          ,
				fcr.solucao_descricao                                                ,
				tbl_produto.descricao as produto_nome                                ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura		 ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY')       AS fechamento	 ,
				tbl_posto.nome as posto_nome                                         ,
				fcr.defeito_reclamado_descricao
		FROM (
			SELECT tbl_os.os ,
					tbl_defeito_constatado.defeito_constatado as defeito_constatado,
					tbl_defeito_constatado.descricao as defeito_constatado_descricao,
					tbl_solucao.solucao ,
					tbl_solucao.descricao as solucao_descricao,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					CASE WHEN(
							SELECT tbl_servico_realizado.troca_de_peca
							FROM tbl_os_produto
							JOIN tbl_os_item using(os_produto)
							JOIN tbl_servico_realizado using(servico_realizado)
							WHERE tbl_os_produto.os = tbl_os.os limit 1 ) IS TRUE
					THEN 'com' ELSE 'sem' END AS com_sem 
			FROM tbl_os
			JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
			JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
			JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
			JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
			WHERE tbl_os.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND tbl_produto.referencia_fabrica = '$produto' 
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_defeito_constatado.defeito_constatado = $defeito_constatado
			and tbl_solucao.solucao = $solucao
			AND tbl_extrato.liberado is not null
			AND tbl_extrato.total > 0
			AND $cond_5
		) as fcr
		JOIN tbl_os on tbl_os.os = fcr.os
		JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
		JOIN tbl_posto on tbl_os.posto = tbl_posto.posto
		WHERE fcr.com_sem='$com_sem'";
}


//echo nl2br($sql); 
	$res = @pg_exec($con, $sql);
	$qtde = pg_numrows($res);
	if(pg_numrows($res)>0){
	echo "<BR><BR><center><font size='1'>Foram encontradas $qtde OS $com_sem peça.</font></center><BR>";
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

			
			
			
			$cor = ($i % 2 == 0) ? "#FFFFFF": '#f4f7fb';
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

// HD 23612
if($login_fabrica == 24) {
	if($com_sem =='com' AND $tipo=='grupo'){
		$sql="SELECT distinct tbl_peca.peca,
					 tbl_peca.referencia,
					 tbl_peca.descricao,
					 count(tbl_peca.peca) AS qtde_peca
			FROM (
				SELECT	tbl_os.os,
						CASE WHEN(
								SELECT tbl_servico_realizado.troca_de_peca
								FROM tbl_os_produto
								JOIN tbl_os_item using(os_produto)
								JOIN tbl_servico_realizado using(servico_realizado)
								WHERE tbl_os_produto.os = tbl_os.os limit 1 ) IS TRUE
						THEN 'com' ELSE 'sem' END AS com_sem 
				FROM tbl_os
				JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os
				JOIN tbl_extrato on tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_defeito_constatado on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
				JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado
				JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND tbl_produto.referencia_fabrica = '$produto' 
				AND tbl_extrato.fabrica = $login_fabrica
				AND tbl_defeito_constatado.defeito_constatado = $defeito_constatado
				and tbl_solucao.solucao = $solucao
				AND $cond_5
			) as fcr
			JOIN tbl_os_produto on tbl_os_produto.os = fcr.os
			JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_peca On tbl_peca.peca=tbl_os_item.peca
			WHERE fcr.com_sem='$com_sem'
			GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
			ORDER BY qtde_peca desc ";

		$res=pg_exec($con,$sql);
		if(pg_numrows($res) >0){
			echo "<BR><BR>";
			echo "<center><div style='width:450px;'><TABLE width='400' border='1' cellspacing='1' cellpadding='2' align='center' name='relatorio' id='relatorio' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
			echo "<caption style='font-size:15px;'>PEÇAS TROCADAS</caption>";
			echo "<thead>";
			echo "<TR>";
			echo "<TD align='center' >PEÇA</TD>";
			echo "<TD align='center' >QTDE</TD>";
			echo "<TD ALIGN='CENTER'>%</TD>";
			echo "</TR>";
			echo "</thead>";

			for ($x = 0; $x < pg_numrows($res); $x++) {
				$total_qtde = $total_qtde + pg_result($res,$x,qtde_peca);
			}

			echo "<tbody>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$referencia = trim(pg_result($res,$i,referencia));
				$descricao = trim(pg_result($res,$i,descricao));
				$qtde_peca = trim(pg_result($res,$i,qtde_peca));

				if ($total_qtde > 0) $porcentagem = (($qtde_peca * 100) / $total_qtde);
				
				$cor = ($i % 2 == 0) ? "#FFFFFF": '#f4f7fb';
				echo "<TR bgcolor='$cor'>";
				echo "<TD align='left' nowrap><acronym title='$referencia - $descricao'>$referencia - $descricao</acronym></TD>";
				echo "<TD align='center' nowrap>$qtde_peca</TD>";
				echo "<TD align='right' nowrap title='%'>". number_format($porcentagem,2,".",".") ."</TD>";
				echo "</tr>";
				$total_porcentagem+=$porcentagem;
				
			}
			echo "</tbody>";
			echo "<tr>";
			echo "<td ><font size='2'><b><CENTER>TOTAL DE PEÇAS</b></td>";
			echo "<td  ALIGN='CENTER'><font size='2' color='009900'><b>$total_qtde</b></td>";
			echo "<td align='right'>".number_format($total_porcentagem,2,".",".")."</td>";
			echo "</tr>";
			echo " </TABLE></div>";
		}
	}
}
?>
</BODY>
</HTML>
