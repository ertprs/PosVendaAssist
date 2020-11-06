<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../admin/funcoes.php';


if($login_fabrica<>10) header ("Location: index.php");

$TITULO = "Lista de Chamados";

include "menu.php";
?>
<?if($login_admin <> 822) { ?>
<meta http-equiv="refresh" content="300">
<? } ?>
<script type="text/javascript" charset="utf-8">
	function mostra_agendados(){
		window.location="adm_atendimento_lista.php?mostra_agendados=nao";
	}

</script>
<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<? if($login_admin == 822) { ?>
<script type="text/javascript" src="js/interface.js"></script>
<script type="text/javascript" src="js/jquery.ui.interaction.min.js"></script>
<link rel="stylesheet" href="css/interface.css" type="text/css" media="screen" />
<?}?>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();
});


 $(document).ready(function(){
   $(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});

   });
 


</script>
<style>
	table.relatorio {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
		font-family: Verdana;
		font-size: 11px;
	}

	table.relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 1px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
		font-family: Verdana;
		font-size: 11px;
	}

	table.relatorio td {
		padding: 1px 5px;
		border-bottom: 1px solid #95bce2;
		font-family: Verdana;
		font-size: 11px;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/


	</style>
<?

###busca###


$sql="SELECT *
		FROM	tbl_change_log
		LEFT jOIN tbl_fabrica ON tbl_fabrica.fabrica=tbl_change_log.fabrica
		LEFT join tbl_change_log_admin On tbl_change_log.change_log=tbl_change_log_admin.change_log AND tbl_change_log_admin.admin = $login_admin
		WHERE tbl_change_log_admin.data IS NULL 
		AND   tbl_change_log.admin <> $login_admin";

$res = pg_exec ($con,$sql);
if(pg_numrows($res) >0) {
	echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>
	<tr valign='middle' align='center'><td class='change_log'><a href='change_log_mostra.php' target='_blank'>Existem CHANGE LOG para ser lido. Clique aqui para visualizar</a></td></tr>
	</table>";
}

echo "<div style='text-align:right;float:left' id='bloco'></div>";

$sql="SELECT 
		TO_CHAR(data_inicio,' hh24:mi')            AS hora_inicio ,
		tbl_hd_chamado.hd_chamado                                 ,
		tbl_hd_chamado.titulo                                     ,
		tbl_fabrica.nome
	FROM tbl_hd_chamado_atendente
	JOIN tbl_admin using(admin)
	JOIN tbl_hd_chamado using(hd_chamado)
	JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
	WHERE data_inicio BETWEEN  (current_date||' 00:00:00')::timestamp and (current_date||' 23:59:59')::timestamp
	and tbl_hd_chamado_atendente.admin = $login_admin
	and tbl_hd_chamado_atendente.data_termino is null
	ORDER BY hora_inicio desc limit 1";

$res = pg_exec ($con,$sql);
if(pg_numrows($res) >0 ) {
	$hora_inicio = pg_result($res,0,hora_inicio);
	$hd_chamado  = pg_result($res,0,hd_chamado);
	$titulo      = pg_result($res,0,titulo);
	$nome_fabrica = pg_result($res,0,nome);
	
	echo "<br><div id='trabalho'><table width='600' align='center' cellpadding='0' cellspacing='0' border='0' >
	<tr bgcolor='#FFFFFF' valign='middle' align='center'>
		<td colspan= '3'> <font face='verdana' size='1px'> <B>CHAMADO QUE VOCÊ ESTÁ TRABALHANDO AGORA</B></font></td>
	</tr>
	<tr bgcolor='#D9E8FF' valign='middle' align='center'>
		<font face='verdana' size='2'> 
		<td > HD</td>
		<td > TITULO HD</td>
		<td > FABRICA </td>
		</font>
	</tr>
	<tr bgcolor='#EAEDFF' valign='middle' align='center'>
		<font face='verdana' size='1'> 
		<td > <B>			 <a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a></B></td>
		<td nowrap> $titulo</td>
		<td > $nome_fabrica</td>
		</font> 
	</tr>
	</table></div><br>";

}else{
	echo "<br><div id='trabalho'><table width='600' align='center' cellpadding='0' cellspacing='0' border='0' >
	<tr bgcolor='#FFFFFF' valign='middle' align='center'>
		<td > <font face='verdana' size='2' color = 'red'> <B>Atenção. Você não está trabalhando em nenhum chamado.</B></font></td>
	</tr>
	</table></div><br>";
}

$sql_mostra_agendados = " 1=1 ";
$mostra_agendados     =$_GET["mostra_agendados"] ;
if($mostra_agendados == "nao"){
	$sql_mostra_agendados = " tbl_hd_chamado.esta_agendado is not true ";
}


$sql = "SELECT 
			hd_chamado,
			tbl_hd_chamado.admin    ,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
			to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
			tbl_hd_chamado.titulo                        ,
			tbl_hd_chamado.status                        ,
			tbl_hd_chamado.atendente                     ,
			tbl_hd_chamado.prazo_horas                   ,
			tbl_hd_chamado.exigir_resposta               ,
			tbl_hd_chamado.cobrar                        ,
			tbl_hd_chamado.prioridade                    ,
			tbl_hd_chamado.hora_desenvolvimento          ,
			tbl_fabrica.nome AS fabrica_nome             ,
			tbl_tipo_chamado.descricao as tipo_chamado_descricao,
			CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
				THEN 1
				ELSE 0
			END AS atrasou,
			CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
				THEN 1
				ELSE 0
			END AS atrasou_interno
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.atendente = $login_admin 
		and 
		ORDER BY tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC";

$sql = "SELECT 
			hd_chamado,
			tbl_hd_chamado.admin    ,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			tbl_hd_chamado.previsao_termino AS previsao,
			to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
			to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
			tbl_hd_chamado.titulo,
			tbl_hd_chamado.status,
			tbl_hd_chamado.atendente,
			tbl_hd_chamado.prazo_horas                           ,
			tbl_hd_chamado.exigir_resposta,
			tbl_hd_chamado.cobrar,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_hd_chamado.prioridade                    ,
			tbl_fabrica.nome AS fabrica_nome,
			tbl_tipo_chamado.descricao as tipo_chamado_descricao,
			CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
				THEN 1
				ELSE 0
			END AS atrasou,
			CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
				THEN 1
				ELSE 0
			END AS atrasou_interno,
			tbl_hd_chamado.esta_agendado
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE atendente = $login_admin
			AND tbl_hd_chamado.fabrica_responsavel = 10
			AND status NOT IN ('Resolvido','Cancelado','Aprovação')
			AND $sql_mostra_agendados ";


$sql .= " ORDER BY tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC ";
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) >= 0) {
	##### LEGENDAS #####
	echo "<CENTER><font face='verdana' size='1'><b style='border:1px solid #666666;background-color:#FFD5CC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Prioridade</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF3333;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF9966;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Risco de Previsão</b>";
	echo "<br><b><font color='#FF0000'>* Analistas acompanhem as previsões de término dos seus chamados, este prazo é visualizado pelo fabricante.</font></b></CENTER><br>";

	echo "<table width = '100%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
	echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'>";
	echo "	<th ><strong>Nº </strong></th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Agenda</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	#echo "	<th nowrap ><strong>Hr Des.</strong></th>";/*nao servia para nada, removi - Fabio*/
	echo "	<th nowrap ><strong>Previsão</strong></th>";
	echo "</tr>";
	
	echo "</thead>";
if (@pg_numrows($res) > 0) {

//inicio imprime chamados
	echo "<tbody>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado               = pg_result($res,$i,hd_chamado);
		$admin                    = pg_result($res,$i,admin);
		$login                    = pg_result($res,$i,login);
//		$posto                    = pg_result($res,$i,posto);
		$data                     = pg_result($res,$i,data);
		$titulo                   = pg_result($res,$i,titulo);
		$status                   = pg_result($res,$i,status);
		$atendente                = pg_result($res,$i,atendente);
		$exigir_resposta          = pg_result($res,$i,exigir_resposta);
		$nome_completo            = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome             = trim(pg_result($res,$i,fabrica_nome));
		$previsao                 = trim(pg_result($res,$i,previsao));
		$previsao_termino         = "<b>".trim(pg_result($res,$i,previsao_termino))."</b>";
		$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
		$atrasou                  = trim(pg_result($res,$i,atrasou));
		$atrasou_interno          = trim(pg_result($res,$i,atrasou_interno));
		$cobrar                   = trim(pg_result($res,$i,cobrar));
		$hora_desenvolvimento     = trim(pg_result($res,$i,hora_desenvolvimento));
		$esta_agendado            = trim(pg_result($res,$i,esta_agendado));
		if (strlen($hora_desenvolvimento) > 0) $hora_desenvolvimento = $hora_desenvolvimento."h";

		$tipo_chamado_descricao= trim(pg_result($res,$i,tipo_chamado_descricao));
		$prazo_horas          = pg_result($res,$i,prazo_horas);
		$total_prazo_horas = $total_prazo_horas +$prazo_horas;
		if (strlen($prazo_horas) > 0) $prazo_horas = $prazo_horas."h";

		$prioridade           = pg_result($res,$i,prioridade);
		
		$wsql =" select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) from tbl_hd_chamado_atendente where hd_chamado = $hd_chamado;";
		$wres = pg_exec($con, $wsql);
		if(pg_numrows($wres)>0)
		$horas= pg_result ($wres,0,0);	
		if(strlen($horas)>0){
			$xhoras = explode(":",$horas);
			$hh= $hh + $xhoras[0];
			$mm= $mm + $xhoras[1];

			$horas = $xhoras[0].":".$xhoras[1];
		}

		if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
			$atrasou = 0;
		}

		if ($atrasou == 0 AND $chamados_atrasados==1){
			//break;
		}
	
		$sql2 = "SELECT nome_completo, admin
			FROM	tbl_admin
			WHERE	admin='$atendente'";
		$res2 = pg_exec ($con,$sql2);	
		$xatendente            = pg_result($res2,0,nome_completo);
		$xxatendente = explode(" ", $xatendente);
		
		$cor = ($i % 2 == 0) ? '#FFFFFF' : '#F2F7FF';

		if ($prioridade == 't'){
			$cor='#FFD5CC';
		}

		if ($atrasou_interno == '1'){
			$cor='#F8FBB3';
		}

		if (strlen($previsao) > 0) {
			$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
			$resp = pg_exec($con, $sqlp);
			if (pg_result($resp, 0, 0) == 'f') {
				$cor='#FF9966';
			}
		}

		if ($atrasou == '1'){
			$chamados_atrasados = 1;
			$cor='#FF3333';
		}

		if (strlen($hora_desenvolvimento)>0){
			$hora_desenvolvimento = $hora_desenvolvimento;
		}

		for($r = 0 ; $r < count($chamado_interno); $r++){
			if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
		}
		echo "<tr height='25' bgcolor='$cor' onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" nowrap id='$hd_chamado' rel='chamado'>";

		echo "<td nowrap width='50'>";
		if($status =="Análise" AND $exigir_resposta <> "t"){
			echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
		}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
			echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
		}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
				echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}
		echo " $hd_chamado</td>";

		echo "<td nowrap  width='180'>";

		echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

		echo "<acronym title='$titulo'>$interno ";
		echo substr($titulo,0,20)."...</acronym></a>";
		echo ($login_admin == 822) ? "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado&KeepThis=true&TB_iframe=true&height=590&width=940' class='thickbox'><img src='imagem/chamad.png' width='11' border='0'></a>" : "";
		echo "</td>";
		
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			$cor_status="#000000";
			if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
			if($status=="Execução")$cor_status="#0000FF";
			if($status=="Aguard.Execução")$cor_status="#339900";
			if($status=="Aguard.Execução") $status="A.Execução";
			echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status</td>";
		}
		$imagem_erro="";

		$sqlz = "select to_char(data,'DD/MM/YYYY') as data_agenda, horario from tbl_hd_chamado_agenda join tbl_agenda using(agenda) where hd_chamado = $hd_chamado limit 1";
		$sqlz = "
				select to_char(data,'DD/MM/YYYY') as data_agenda, 
					horario ,
					case when hd.qtde > 0
						then hd.qtde /2
						else 0
					end as horas_agenda
				from tbl_hd_chamado_agenda 
				join tbl_agenda using(agenda) 
				join (
					select hd_chamado, count(agenda) as qtde
					from tbl_hd_chamado_agenda 
					where hd_chamado = $hd_chamado
					group by hd_chamado
				)as hd on hd.hd_chamado = tbl_hd_chamado_agenda.hd_chamado
				where tbl_hd_chamado_agenda.hd_chamado = $hd_chamado
				limit 1";

		$resz = pg_exec($con,$sqlz);
		if(pg_numrows($resz)>0){
			$data_agenda    = pg_result($resz,0,data_agenda);
			$agenda_horario = pg_result($resz,0,horario);
			$horas_agenda   = pg_result($resz,0,horas_agenda);
			
			echo "<td nowrap ><font size='1'><acronym title='Inicio: $agenda_horario - $horas_agenda h agendadas'>$data_agenda</acronym></font></td>";
		}else{
			echo "<td nowrap ><font size='1'>$data_agenda</font></td>";
		}
		$data_agenda = '';

		if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
		echo "<td nowrap width='120' >$imagem_erro <font size='1'><strong>$tipo_chamado_descricao</strong></font></td>";

		echo "<td nowrap ><font size='1'>$data</font></td>";
		echo "<td nowrap><font size='1'>";
		if (strlen ($nome_completo) > 0) {
			$nome_completo2 = explode (' ',$nome_completo);
			$nome_completo2 = $nome_completo2[0];
			echo $nome_completo2;
		}else{
			echo $login;
		}
		echo "</font></td>";
		echo "<td nowrap width='80'><font size='1'>$fabrica_nome</font></td>";
//		echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
		echo "<td nowrap align='right'><font size='1'>$horas</font></td>";
		echo "<td nowrap align='right'><font size='1'>$prazo_horas</font></td>";
		#echo "<td nowrap align='right'><font size='1'>$hora_desenvolvimento</font></td>"; Removi - Fabio
		echo "<td nowrap><font size='1'>$previsao_termino</font></td>";

		echo "</tr>"; 
		$interno='';
		$chamados .= ($i == 0) ? $hd_chamado : ",".$hd_chamado;
	}

	$tot_trab = number_format(($hh + ($mm/60)), 2);
	echo "</tbody>";
	echo "<tfoot>";
	echo "<tr rel='$chamados' id='chamados'>";
	echo "<td colspan='8' align='right'>";
	echo "&nbsp;<b>Total de prazo:</b>";
	echo "</td>";
	echo "<td align='right'>";
	echo " $tot_trab h";
	echo "</td>";

	echo "<td align='right'>";
	echo " $total_prazo_horas h";
	echo "</td>";

	#echo "<td colspan='1' align='right'>";
	#echo " $total_prazo_horas h";
	#echo "</td>";

	echo "<td colspan='1' align='right'>";
	echo " &nbsp;";
	echo "</td>";
	echo "</tr>";
	echo "</tfoot>";
	echo "</table>"; 
### PÉ PAGINACAO###

	if ($chamados_atrasados == 1 and $login_admin <> 822){
		echo "<center><h3>Chamados atrasados! Concluir com URGÊNCIA.</h3><center>";
	}

	echo "<BR>";
	if($login_admin == 852){
		###busca todos RESOLVIDOS###
		$sql = "
				SELECT  count(tbl_hd_chamado.hd_chamado)as qtde,
					tbl_fabrica.nome,
					sum(prazo_horas) as prazo_horas
				FROM tbl_hd_chamado
				JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
				WHERE data_resolvido BETWEEN  (current_date||' 00:00:00')::timestamp AND (current_date||' 23:59:00')::timestamp
					AND tbl_hd_chamado.status = 'Resolvido'
					AND atendente = $login_admin
				GROUP by tbl_fabrica.nome
				ORDER BY COUNT(tbl_hd_chamado.hd_chamado) DESC";

		$res = pg_exec ($con,$sql);


		/*--===============================TABELA DE CHAMADOS RESOLVIDOS========================--*/
		if (@pg_numrows($res) > 0) {
			echo "<table width = '300' align = 'center' cellpadding='2' cellspacing='1' border='0' class='relatorio'>";
			echo "<thead>";
			echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>CHAMADOS RESOLVIDOS HOJE</CENTER></th></tr>";
			echo "<tr bgcolor='#D9E8FF' >";
			echo "	<th ><strong>Fabrica</strong></th>";
			echo "	<th >QTDE</th>";
			echo "	<th >Prazo do Chamado</th>";
			echo "</tr>";

			echo "</thead>";
			$qtde_total = 0;
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$qtde = pg_result($res,$i,qtde);
				$nome                    = pg_result($res,$i,nome);
				$prazo_horas = pg_result($res,$i,prazo_horas );
				$qtde_total = $qtde_total+ $qtde ;
				echo "<tbody>";
				echo "<tr  style='cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
				echo "<td nowrap ><font size='1'>$nome </font></td>";
				echo "<td nowrap  >$qtde</td>";
				echo "<td nowrap  >$prazo_horas</td>";
				echo "</tr>";
				echo "</tbody>";

			}
			echo "<tr><td width='100%' align='right' colspan='2'>";
			echo "<font size='2'><b>Total $qtde_total chamados Resolvidos</b></font>";
			echo "</td></tr>";
			echo "</table>";
			$total_resolvido= $qtde_total;
		}

		$sql = "
				SELECT 
				count(tbl_hd_chamado.hd_chamado) AS count,
				sum(case when tbl_hd_chamado.prazo_horas is null then 3 else tbl_hd_chamado.prazo_horas end ) as horas
				FROM tbl_hd_chamado
				LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
				JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
				JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
				WHERE tbl_hd_chamado.fabrica_responsavel = 10
					AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";

		$res = pg_exec ($con,$sql);


		/*--===============================TABELA DE CHAMADOS RESOLVIDOS========================--*/
		if (@pg_numrows($res) > 0) {	
			echo "<table width = '300' align = 'center' cellpadding='2' cellspacing='1' border='0' class='relatorio'>";
			echo "<thead>";
			echo "<tr bgcolor='#D9E8FF'><th colspan=11><CENTER>TOTAL DE CHAMADOS</CENTER></th></tr>";
			echo "<tr bgcolor='#D9E8FF' >";
			echo "	<th ><strong>QTDE</strong></th>";
			echo "	<th >TOTAL DE HORAS</th>";
			echo "</tr>";
			$count= pg_result($res,0,count);
			$horas= pg_result($res,0,horas);

			echo "<tr >";
			echo "	<th ><strong>$count</strong></th>";
			echo "	<th >$horas h</th>";
			echo "</tr>";
			echo "</table>";	
			echo "<tr >";
			echo "	<th >Total de Analistas: 4</strong></th>";
			echo "	<th >Horas por Analista: ".($horas /4) ."h - Qtde Semana Trabalho: ".(($horas /4)/40) ."h</th>";
			echo "</tr>";
			echo "</table>";	

		}

	}
	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='10' align='center'>";
		// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	// ##### PAGINACAO ##### //
	}

	echo "</td>";
	echo "</tr>";

	echo "</table>";

	###busca todos RESOLVIDOS###
	if($login_admin == 822) {
		$sql = " SELECT count(*),
					(SELECT count(*)
					FROM tbl_hd_chamado
					WHERE  tbl_hd_chamado.fabrica_responsavel =10
					AND data_resolvido is not null
					AND data_resolvido::date=current_date
					) as resolvido,
					(SELECT count(*)
					FROM tbl_hd_chamado
					WHERE  tbl_hd_chamado.fabrica_responsavel =10
					AND data is not null
					AND data::date=current_date
					) as aberto
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE  tbl_hd_chamado.fabrica_responsavel =10
		AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
		$res = pg_query($con,$sql);

		echo "<div id='fisheye2' class='fisheye'>";
		echo "<div class='fisheyeContainter'>";
		echo "<a href='adm_chamado_lista.php?KeepThis=true&TB_iframe=true&height=590&width=940' class='fisheyeItem2 thickbox' ><span id='total_chamado'>".pg_fetch_result($res,0,0)."</span><img src='imagem/fisheye/windows.png' width='30' /></a>";
		echo "<a href='adm_chamado_lista_novo.php?KeepThis=true&TB_iframe=true&height=590&width=940' class='fisheyeItem2 thickbox'><span id='resolvido'>".pg_fetch_result($res,0,1)."</span><img src='imagem/fisheye/linux.png' width='30' /></a>";
		echo "<a href='adm_relatorio_diario.php?KeepThis=true&TB_iframe=true&height=590&width=940' class='fisheyeItem2 thickbox' ><span id='aberto'>".pg_fetch_result($res,0,2)."</span><img src='imagem/fisheye/mac.png' width='30' /></a>";
		echo "<a href='#' class='fisheyeItem2' ><span></span><img src='imagem/sticky_thumb.png' width='30' id='sticky'/></a>";
		echo "<a href='#' class='fisheyeItem2' id='statistic'><span></span><img src='imagem/statistic.png' width='30' /></a>";
		echo "</div>";
		echo "</div>";
	}
}

?>

<? include "rodape.php" ?>
