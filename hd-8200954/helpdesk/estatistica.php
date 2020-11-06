<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$conta_chamado    = $_GET['conta_chamado'];
$verifica_chamado = $_GET['verifica_chamado'];
$chamados         = $_GET['chamados'];

ob_start(); 
ob_implicit_flush(0); 
ob_end_clean(); 
if(isset($_GET['trabalho'])){
	$sql="select x.dia ,login
			from(
				select sum(to_char(data_termino,'HH24:MI:SS')::time - to_char(data_inicio,'HH24:MI:SS')::time) as dia,admin 
				from tbl_hd_chamado_atendente where data_inicio between (current_date || ' 00:00:00')::timestamp 
				and (current_date || ' 23:59:59')::timestamp
				group by admin
			) x
			join tbl_admin using(admin)";
	$res = pg_query($con,$sql);
	for($i =0;$i<pg_num_rows($res);$i++) {
		echo pg_fetch_result($res,$i,login);
		echo "&nbsp;";
		echo pg_fetch_result($res,$i,dia);
		echo "\n";
	}
	exit;
}

if(isset($_GET['chamados'])){
	$sql = "SELECT 
			hd_chamado,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			tbl_hd_chamado.previsao_termino AS previsao,
			to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
			tbl_hd_chamado.titulo,
			tbl_hd_chamado.status,
			tbl_hd_chamado.prazo_horas                           ,
			tbl_hd_chamado.exigir_resposta,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_hd_chamado.prioridade                    ,
			tbl_fabrica.nome AS fabrica_nome,
			tbl_tipo_chamado.descricao as tipo_chamado_descricao
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE atendente = $login_admin
			AND tbl_hd_chamado.fabrica_responsavel = 10
			AND status NOT IN ('Resolvido','Cancelado','Aprovação')
			AND tbl_hd_chamado.hd_chamado not in ($chamados)
		ORDER BY tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC ";
	$res = pg_query ($con,$sql);

	if (@pg_num_rows($res) > 0) {
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$hd_chamado               = pg_fetch_result($res,$i,hd_chamado);
			$login                    = pg_fetch_result($res,$i,login);
			$data                     = pg_fetch_result($res,$i,data);
			$titulo                   = pg_fetch_result($res,$i,titulo);
			$status                   = pg_fetch_result($res,$i,status);
			$exigir_resposta          = pg_fetch_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_fetch_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_fetch_result($res,$i,fabrica_nome));
			$previsao                 = trim(pg_fetch_result($res,$i,previsao));
			$previsao_termino         = "<b>".trim(pg_fetch_result($res,$i,previsao_termino))."</b>";
			$hora_desenvolvimento     = trim(pg_fetch_result($res,$i,hora_desenvolvimento));
			if (strlen($hora_desenvolvimento) > 0) $hora_desenvolvimento = $hora_desenvolvimento."h";

			$tipo_chamado_descricao= trim(pg_fetch_result($res,$i,tipo_chamado_descricao));
			$prazo_horas          = pg_fetch_result($res,$i,prazo_horas);
			$total_prazo_horas = $total_prazo_horas +$prazo_horas;
			if (strlen($prazo_horas) > 0) $prazo_horas = $prazo_horas."h";

			$prioridade           = pg_fetch_result($res,$i,prioridade);
			
			$wsql =" select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) from tbl_hd_chamado_atendente where hd_chamado = $hd_chamado;";
			$wres = pg_query($con, $wsql);
			if(pg_num_rows($wres)>0)
			$horas= pg_fetch_result ($wres,0,0);	
			if(strlen($horas)>0){
				$xhoras = explode(":",$horas);
				$hh= $hh + $xhoras[0];
				$mm= $mm + $xhoras[1];

				$horas = $xhoras[0].":".$xhoras[1];
			}

			$cor = ($i % 2 == 0) ? '#FFFFFF' : '#F2F7FF';

			if ($prioridade == 't'){
				$cor='#FFD5CC';
			}

			if ($atrasou_interno == '1'){
				$cor='#F8FBB3';
			}

			if (strlen($previsao) > 0) {
				$sqlp = "SELECT ('$previsao' - current_timestamp) > interval'1 day';";
				$resp = pg_query($con, $sqlp);
				if (pg_fetch_result($resp, 0, 0) == 'f') {
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
			$resultado .= "<tr height='25' bgcolor='$cor' onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" nowrap id='$hd_chamado' rel='chamado'>";

			$resultado .= "<td nowrap width='50'>";
			if($status =="Análise" AND $exigir_resposta <> "t"){
				$resultado .= "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
				$resultado .= "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
			}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
					$resultado .= "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
				}elseif ($status == "Aprovação") {
					$resultado .= "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
				}else{
					$resultado .= "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
				}
			$resultado .= " $hd_chamado</td>";

			$resultado .= "<td nowrap  width='180'>";

			$resultado .= "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

			$resultado .= "<acronym title='$titulo'>$interno ";
			$resultado .= substr($titulo,0,20)."...</acronym></a>";
			$resultado .= ($login_admin == 822) ? "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado&KeepThis=true&TB_iframe=true&height=590&width=940' class='thickbox' target='_blank'><img src='imagem/chamad.png' width='11' border='0'></a>" : "";
			$resultado .= "</td>";
			
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				$cor_status="#000000";
				if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
				if($status=="Execução")$cor_status="#0000FF";
				if($status=="Aguard.Execução")$cor_status="#339900";
				if($status=="Aguard.Execução") $status="A.Execução";
				$resultado .= "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
			}else{
				$resultado .= "<td nowrap>$status</td>";
			}
			$imagem_erro="";

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

			$resz = pg_query($con,$sqlz);
			if(pg_num_rows($resz)>0){
				$data_agenda    = pg_fetch_result($resz,0,data_agenda);
				$agenda_horario = pg_fetch_result($resz,0,horario);
				$horas_agenda   = pg_fetch_result($resz,0,horas_agenda);
				
				$resultado .= "<td nowrap ><font size='1'><acronym title='Inicio: $agenda_horario - $horas_agenda h agendadas'>$data_agenda</acronym></font></td>";
			}else{
				$resultado .= "<td nowrap ><font size='1'>$data_agenda</font></td>";
			}
			$data_agenda = '';

			if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
			$resultado .= "<td nowrap width='120' >$imagem_erro <font size='1'><strong>$tipo_chamado_descricao</strong></font></td>";

			$resultado .= "<td nowrap ><font size='1'>$data</font></td>";
			$resultado .= "<td nowrap><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				$nome_completo2 = explode (' ',$nome_completo);
				$nome_completo2 = $nome_completo2[0];
				$resultado .= $nome_completo2;
			}else{
				$resultado .= $login;
			}
			$resultado .= "</font></td>";
			$resultado .= "<td nowrap width='80'><font size='1'>$fabrica_nome</font></td>";
			$resultado .= "<td nowrap align='right'><font size='1'>$horas</font></td>";
			$resultado .= "<td nowrap align='right'><font size='1'>$prazo_horas</font></td>";
			$resultado .= "<td nowrap><font size='1'>$previsao_termino</font></td>";
			$resultado .= "</tr>"; 
			$chamados .= $hd_chamado;
		}
		echo $resultado."|".$chamados;
	}else{
		echo "";
	}

	exit;
}


if(isset($_GET['verifica_chamado'])){
	$sql = " SELECT status,atendente from tbl_hd_chamado where hd_chamado = $verifica_chamado";
	$res = pg_query($con,$sql);
	echo ((in_array(pg_fetch_result($res,0,0),array('Aprovação','Resolvido','Cancelado'))) or pg_fetch_result($res,0,1) <> $login_admin) ? "ok" : "";
	exit;
}
if(isset($_GET['conta_chamado'])){
	$data_hoje = date('Y-m');
	$sqlr = " SELECT sum(prazo_horas) from tbl_hd_chamado join tbl_admin on tbl_admin.admin = tbl_hd_chamado.atendente where atendente= $login_admin and data_resolvido > '$data_hoje-01 00:00:00' and tipo_chamado  <> 5 group by login";
	$resr = pg_query($con,$sqlr);
	
	if(pg_num_rows($resr) > 0){
		$total_resolvido = pg_fetch_result($resr,0,0);
	}

	$sql = " SELECT count(*),
					(SELECT count(*)
					FROM tbl_hd_chamado
					WHERE  tbl_hd_chamado.fabrica_responsavel =10
					AND data_resolvido is not null
					AND data_resolvido::date=current_date
					AND    tbl_hd_chamado.titulo <>'Atendimento interativo'
					) as resolvido,
					(SELECT count(*)
					FROM tbl_hd_chamado
					WHERE  tbl_hd_chamado.fabrica_responsavel =10
					AND data is not null
					AND data::date=current_date
					AND    tbl_hd_chamado.titulo <>'Atendimento interativo'
					) as aberto,
					(SELECT count(*)
						FROM tbl_hd_chamado
						WHERE  tbl_hd_chamado.fabrica_responsavel =10
						AND data_resolvido is null
						AND tbl_hd_chamado.status not in ('Aprovação','Cancelado','Resolvido')
						AND tbl_hd_chamado.posto IS NULL
						AND atendente = $login_admin
						AND    tbl_hd_chamado.titulo <>'Atendimento interativo'
					) as meu
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE  tbl_hd_chamado.fabrica_responsavel =10
		AND    tbl_hd_chamado.titulo <>'Atendimento interativo'
		AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
	$res = pg_query($con,$sql);
	echo pg_fetch_result($res,0,0)."|".pg_fetch_result($res,0,1)."|".pg_fetch_result($res,0,2)."|".pg_fetch_result($res,0,3)."|".$total_resolvido;
	exit;
}

$TITULO = "Estatísticas - Telecontrol Hekp-Desk";

include "menu.php";

#NOVO#
$sqlnovo = "SELECT count(*) AS total_novo
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
	WHERE      tbl_admin.fabrica=$login_fabrica
	AND  (status ILIKE 'novo' OR status ILIKE '$status'  OR status IS NULL) ";

$resnovo = @pg_query ($con,$sqlnovo);

if (@pg_num_rows($resnovo) > 0) {
	$xtotal_novo           = pg_fetch_result($resnovo,0,total_novo);
}

#ANALISE#
$sqlanalise = "SELECT count(*) AS total_analise
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
	WHERE      tbl_admin.fabrica=$login_fabrica
	AND  status ILIKE 'análise' ";

$resanalise = @pg_query ($con,$sqlanalise);

if (@pg_num_rows($resanalise) > 0) {
	$xtotal_analise           = pg_fetch_result($resanalise,0,total_analise);
}

#APROVACAO#
$sqlaprovacao = "SELECT count(*) AS total_aprovacao
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
	WHERE      tbl_admin.fabrica=$login_fabrica
	AND status ILIKE 'aprovação' ";

$resaprovacao = @pg_query ($con,$sqlaprovacao);

if (@pg_num_rows($resnovo) > 0) {
	$xtotal_aprovacao          = pg_fetch_result($resaprovacao,0,total_aprovacao);
	}

#RESOLVIDO#
$sqlresolvido = "SELECT count(*) AS total_resolvido
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
	WHERE      tbl_admin.fabrica=$login_fabrica
	AND  status ILIKE 'resolvido' ";

$resresolvido = @pg_query ($con,$sqlresolvido);

if (@pg_num_rows($resresolvido) > 0) {
	$xtotal_resolvido         = pg_fetch_result($resresolvido,0,total_resolvido);
	}

#EXECUCAO#
$sqlexecucao = "SELECT count(*) AS total_execucao
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
	WHERE      tbl_admin.fabrica=$login_fabrica
	AND status ILIKE 'execução' ";

$resexecucao = @pg_query ($con,$sqlexecucao);

if (@pg_num_rows($resexecucao) > 0) {
	$xtotal_execucao         = pg_fetch_result($resexecucao,0,total_execucao);
}

#CANCELADO#

$sqlcancelado = "SELECT count(*) AS total_cancelado
	FROM       tbl_hd_chamado
	JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
	WHERE      tbl_admin.fabrica=$login_fabrica
	AND status ILIKE 'cancelado' ";

$rescancelado = @pg_query ($con,$sqlcancelado);

if (@pg_num_rows($rescancelado) > 0) {
	$xtotal_cancelado         = pg_fetch_result($rescancelado,0,total_cancelado);
}

#TODOS#
$sqltodos = "SELECT count(*) AS total_todos
	FROM tbl_hd_chamado 
	WHERE 1=1 ";

$restodos = @pg_query ($con,$sqltodos);

if (@pg_num_rows($restodos) > 0) {
	$xtotal_todos         = pg_fetch_result($restodos,0,total_todos);
	$xtotal_aberto        = $xtotal_todos - $xtotal_cancelado - $xtotal_resolvido  - $xtotal_aprovacao;
	}

$sql_fabrica =  "SELECT nome FROM tbl_fabrica where fabrica=$login_fabrica";

$res_fabrica = @pg_query ($con,$sql_fabrica);
if (@pg_num_rows($res_fabrica) > 0) {
	$nome         = pg_fetch_result($res_fabrica,0,nome);
}
$vTotal =$xtotal_novo+$xtotal_analise+$xtotal_aprovacao+$xtotal_cancelado+$xtotal_execucao+$xtotal_resolvido ;
$v1 = $xtotal_novo;
$v2 = $xtotal_analise;
$v3 = $xtotal_aprovacao;
$v4 = $xtotal_cancelado;
$v5 = $xtotal_execucao;
$v6 = $xtotal_resolvido;

$Per_opt1 = number_format($v1 / $vTotal * 100,1);
$Per_opt2 = number_format($v2 / $vTotal * 100,1);
$Per_opt3 = number_format($v3 / $vTotal * 100,1);
$Per_opt4 = number_format($v4 / $vTotal * 100,1);
$Per_opt5 = number_format($v5 / $vTotal * 100,1);
$Per_opt6 = number_format($v6 / $vTotal * 100,1);

?>
<br><table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Estatística de Chamadas da <?=$nome?></b></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td nowrap colspan="9"></td>
	<td nowrap></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER>Novo: <B><? echo $xtotal_novo ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER>Análise: <B><? echo $xtotal_analise ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Aprovação: <B><? echo $xtotal_aprovacao ?></B></CENTER></td>
	<td nowrap><CENTER>Resolvido: <B><? echo $xtotal_resolvido ?></B></CENTER></td>
	<td nowrap><CENTER>Execução: <B><? echo $xtotal_execucao ?></B></CENTER></td>
	<td nowrap><CENTER>Cancelado: <B><? echo $xtotal_cancelado ?></B></CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER><B><? echo $Per_opt1 ?>%</B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER><B><? echo $Per_opt2 ?>%</B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER><B><? echo $Per_opt3 ?>%</B></CENTER></td>
	<td nowrap><CENTER><B><? echo $Per_opt6 ?>%</B></CENTER></td>
	<td nowrap><CENTER><B><? echo $Per_opt5 ?>%</B></CENTER></td>
	<td nowrap><CENTER><B><? echo $Per_opt4 ?>%</B></CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap colspan='8'><CENTER>
		<FORM METHOD=POST >
		<INPUT TYPE="submit" value='Clique Aqui para gerar o gráfico' onClick="javascript:popUp('<? echo "pizza.php?titulo=Chamados da $nome&dp=Novos;Análise;Aprovação;Cancelados;Execução;Resolvido&parcela=$xtotal_novo;$xtotal_analise;$xtotal_aprovacao;$xtotal_cancelado;$xtotal_execucao;$xtotal_resolvido"?>')">

</FORM>
	</CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
</table>

