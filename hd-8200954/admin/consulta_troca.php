<?
//alterado por takashi 20-09-06 nao batia valor de peças com produtos.. arquivo anterior... relatorio_field_call_rate_pecas_defeitos-ant_20-09-06.php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia,auditoria";
include "autentica_admin.php";



$layout_menu = "gerencia";
$title = "Aprovação Ordem de Serviço de Troca";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
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

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<style type="text/css">
<!--
.titPreto14 {
	color: #000000;
	text-align: center;
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
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

-->
</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>



<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ 
		<FORM name="frm_pesquisa" METHOD="POST" ACTION="<?// echo $PHP_SELF ?>">
		-->

		<?
if (strlen($erro) == 0) {
	$sql="SELECT 
				tbl_os.os,
				tbl_os.sua_os, 
				tbl_os.consumidor_nome, 
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
				tbl_os.fabrica, 
				tbl_os.tipo_atendimento, 
 				tbl_tipo_atendimento.descricao as atendimento_descricao,
				tbl_os.produto,
				tbl_produto.descricao as produto_descricao,
				tbl_posto.nome as posto_nome,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_os 
		join tbl_tipo_atendimento on tbl_os.tipo_atendimento=tbl_tipo_atendimento.tipo_atendimento 
		join tbl_produto on tbl_os.produto=tbl_produto.produto
		join tbl_posto on tbl_posto.posto=tbl_os.posto
		join tbl_posto_fabrica on tbl_posto_fabrica.posto=tbl_posto.posto and tbl_posto_fabrica.fabrica=$login_fabrica
		WHERE tbl_os.fabrica=$login_fabrica 
		AND (tbl_os.tipo_atendimento=17 or tbl_os.tipo_atendimento=18) order by tbl_os.os, tbl_os.tipo_atendimento";
	//echo "$sql";
	$res = @pg_exec($con,$sql);
	if(pg_numrows($res)>0){
	echo "<BR><BR><table width='650' border='0' align='center' cellpadding='4' cellspacing='1' style='font-family: verdana; font-size: 12px' bgcolor='#485989'>";
	echo "<tr>";
	echo "<td><font color='#FFFFFF'><B>OS</B></font></td>";
	echo "<td><font color='#FFFFFF'><B>Posto</B></font></td>";
	echo "<td><font color='#FFFFFF'><B>Consumidor</B></font></td>";
	echo "<td><font color='#FFFFFF'><B>Produto</B></font></td>";
	echo "<td><font color='#FFFFFF'><B>Tipo Atendimento</B></font></td>";
	echo "</tr>";
		for ($x; $x<pg_numrows($res);$x++){
				$os= pg_result($res, $x, os);
				$sua_os= pg_result($res, $x, sua_os);
				$codigo_posto= pg_result($res, $x, codigo_posto);
				$consumidor_nome= pg_result($res, $x, consumidor_nome);
  				$data_digitacao= pg_result($res, $x, digitacao);
				$data_abertura= pg_result($res, $x, abertura);
				$atendimento_descricao= pg_result($res, $x, atendimento_descricao);
				$produto_descricao= pg_result($res, $x, produto_descricao);
				$posto_nome= pg_result($res, $x, posto_nome);
			//echo "$codigo_posto$sua_os - $data_abertura - $atendimento_descricao - $produto_descricao - $posto_nome <BR>";
 			$cor = ($x % 2 == 0) ? "#FEFEFE": '#F9FCFF';
			echo "<tr>";
			echo "<td bgcolor='$cor'><a href='os_cadastro_troca.php?os=$os' target='blank'>$codigo_posto$sua_os</a></td>";
			echo "<td bgcolor='$cor' align='left'>$posto_nome</td>";
			echo "<td bgcolor='$cor' align='left'>$consumidor_nome</td>";
			echo "<td bgcolor='$cor' align='left'>$produto_descricao</td>";
			echo "<td bgcolor='$cor' align='left'>$atendimento_descricao</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{ 
		echo "<center>Não foi encontrada OS de Troca.</center>";
	}
	
}



include "rodape.php" ?>

		