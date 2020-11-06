<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../admin/funcoes.php';


if($login_fabrica<>10) header ("Location: index.php");

$TITULO = "Lista de Chamados";

include "menu.php";
?>
<meta http-equiv="refresh" content="300">
<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#data_inicio").maskedinput("99/99/9999");
		$("#data_fim").maskedinput("99/99/9999");
	});
</script>
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

	echo "<br><table width='600' align='center' cellpadding='0' cellspacing='0' border='0'>
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
	</table><br>";

}else{
	echo "<br><table width='600' align='center' cellpadding='0' cellspacing='0' border='0'>
	<tr bgcolor='#FFFFFF' valign='middle' align='center'>
		<td > <font face='verdana' size='2' color = 'red'> <B>Atenção. Você não está trabalhando em nenhum chamado.</B></font></td>
	</tr>
	</table><br>";


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
		and tbl_hd_chamado.fabrica_responsavel = 10
		AND status NOT IN ('Resolvido','Cancelado')";

$sql .= " ORDER BY tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC ";



//echo nl2br($sql);

$res = pg_exec ($con,$sql);

if (@pg_numrows($res) >= 0) {

	

	##### LEGENDAS #####
	echo "<CENTER><font face='verdana' size='1'><b style='border:1px solid #666666;background-color:#FFD5CC;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Prioridade</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF3333;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Previsão Vencida</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<b style='border:1px solid #666666;background-color:#FF9966;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Risco de Previsão</b>";
	
	echo "<br><br><b><font color='#FF0000'>* Analistas acompanhem as previsões de término dos seus chamados, este prazo é visualizado pelo fabricante.</font></b></CENTER><br>";
	


	echo "<table width = '100%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
echo "<thead>";
	echo "<tr bgcolor='#D9E8FF'>";
	echo "	<th ><strong>Nº </strong></th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Agenda</strong></th>";
	if($login_admin ==568 ){
		echo "	<th nowrap ><strong>Ag.</strong></th>";
	}
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
	echo "	<th nowrap ><strong>Hr Des.</strong></th>";
	echo "	<th nowrap ><strong>Previsão</strong></th>";
	echo "</tr>";
	
	echo "</thead>";
if (@pg_numrows($res) > 0) {

//	echo $sql;
		
//inicio imprime chamados
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
//		echo $sql2;
		$res2 = pg_exec ($con,$sql2);	
		$xatendente            = pg_result($res2,0,nome_completo);
		$xxatendente = explode(" ", $xatendente);
		
		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

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
		echo "<tbody>";
		echo "<tr   height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
		 echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

		echo "<td nowrap width='65'>";
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

		echo "<td nowrap  width='200'>";

		echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

		echo "<acronym title='$titulo'>$interno ";
		echo substr($titulo,0,20)."...</acronym></a></td>";
		
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			$cor_status="#000000";
			if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
			if($status=="Execução")$cor_status="#0000FF";
			if($status=="Aguard.Execução")$cor_status="#339900";
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

		if($login_admin ==568){
			$check_agendado= " ";
			if($esta_agendado =='t'){
				$check_agendado= " checked='checked' ";
			}
			echo "<td align='center' nowrap><input type='checkbox' name='esta_agendado' value='t' $check_agendado> </td>";
		}


		if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
		echo "<td nowrap width='130' >$imagem_erro <font size='1'><strong>$tipo_chamado_descricao</strong></font></td>";

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
		echo "<td nowrap align='right'><font size='1'>$hora_desenvolvimento</font></td>";
		echo "<td nowrap><font size='1'>$previsao_termino</font></td>";

		echo "</tr>"; 
		$interno='';
	}

	$tot_trab = number_format(($hh + ($mm/60)), 2);

	echo "<tr >";
	echo "<td colspan='8' align='right'>";
	echo "&nbsp;<b>Total de prazo:</b>";
	echo "</td>";
	echo "<td align='right'>";
	echo " $tot_trab h";
	echo "</td>";

	echo "<td align='right'>";
	echo " $total_prazo_horas h";
	echo "</td>";

	echo "<td colspan='1' align='right'>";
	echo " $total_prazo_horas h";
	echo "</td>";

	echo "<td colspan='1' align='right'>";
	echo " &nbsp;";
	echo "</td>";

	echo "</tr>";
	


//fim imprime chamados
	
		echo "</tbody>";

	echo "</table>"; 
### PÉ PAGINACAO###

	if ($chamados_atrasados == 1){
		echo "<center><h3>Chamados atrasados! Concluir com URGÊNCIA.</h3><center>";
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
	
}
	
	
	
	
	
	
	
	
	
	
	
	




?>

<? include "rodape.php" ?>

<?
if ($msg) {
	if ($login_admin == '399') {
		echo "<script language='JavaScript'>alert('$msg');</script>";
	}
}
?>
