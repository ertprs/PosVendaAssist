<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$hd_chamado=$_GET['hd_chamado'];
$aprova_execucao = $_GET['aprova_execucao'];
$insere_saldo = $_GET['insere_saldo'];

$data       = date("d/m/Y  h:i");

$sql="SELECT to_char(current_date,'MM')   AS mes,
			 to_char(current_date,'YYYY') AS ano;";
$res=pg_exec($con,$sql);
$mes=pg_result($res,0,mes);
$ano=pg_result($res,0,ano);

//VERIFICA SE O USUÁRIO É SUPERVISOR
$sql="  SELECT * FROM tbl_admin
		WHERE admin=$login_admin
		AND help_desk_supervisor='t'";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$supervisor='t';
	$nome_completo=pg_result($res,0,nome_completo);
}
if($aprova_execucao== 'sim'){
	$sql="SELECT data_aprovacao
			FROM tbl_hd_chamado
			WHERE hd_chamado=$hd_chamado";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		$data_aprovacao=pg_result($res,0,data_aprovacao);
		if(strlen($data_aprovacao) > 0){
			$msg_erro="Este Chamado já foi aprovado, não pode aprovar mais de uma vez";
		}
	}

	if(strlen($msg_erro) ==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_hd_chamado
				SET exigir_resposta = 'f', status = 'Análise', data_aprovacao = CURRENT_TIMESTAMP,hora_faturada=hora_desenvolvimento
				WHERE hd_chamado = $hd_chamado
				AND   data_aprovacao IS NULL";

		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql="SELECT *
				FROM tbl_hd_franquia
				WHERE fabrica = $login_fabrica
				ORDER BY hd_franquia desc limit 1";
		$res=pg_exec($con,$sql);

		if(pg_numrows($res) >0){
			$hd_franquia=pg_result($res,0,hd_franquia);

			$sqlh = "UPDATE tbl_hd_franquia
					SET hora_faturada    = tbl_hd_franquia.hora_faturada + tbl_hd_chamado.hora_desenvolvimento,
					valor_faturado   = valor_faturado + (tbl_hd_chamado.hora_desenvolvimento * tbl_hd_franquia.valor_hora_franqueada)
					FROM tbl_hd_chamado
					WHERE tbl_hd_franquia.fabrica     = tbl_hd_chamado.fabrica
					AND   tbl_hd_chamado.hd_chamado   = $hd_chamado
					AND   tbl_hd_franquia.hd_franquia = $hd_franquia
					AND   tbl_hd_chamado.fabrica      = $login_fabrica";

			$resh = pg_exec ($con,$sqlh);
			$msg_erro .= pg_errormessage($con);
		}

		$sql = "INSERT into tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					admin
					) VALUES (
					$hd_chamado,
					'MENSAGEM AUTOMÁTICA - HORA DE DESENVOLVIMENTO APROVADO E FATURADO EM $data PELO USUÁRIO $nome_completo',
					$login_admin
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Houve um erro na aprovação do Chamado.';
		}else{
			$res = @pg_exec($con,"COMMIT");
		}
	}
}

if($insere_saldo == 'sim' ){

	if($mes == '12'){
		$mes_seguinte = '01';
		$ano_seguinte = $ano+1;
	}else{
		$mes_seguinte = $mes +1;
		$ano_seguinte = $ano;
	}

	$sql="SELECT data_aprovacao
			FROM tbl_hd_chamado
			WHERE hd_chamado=$hd_chamado";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		$data_aprovacao=pg_result($res,0,data_aprovacao);
		if(strlen($data_aprovacao) > 0){
			$msg_erro="Este Chamado já foi aprovado, não pode aprovar mais de uma vez";
		}
	}
	if(strlen($msg_erro) ==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");


		$sql = "UPDATE tbl_hd_chamado
				SET exigir_resposta = 'f', status = 'Análise', data_aprovacao = CURRENT_TIMESTAMP
				WHERE hd_chamado = $hd_chamado
				AND   data_aprovacao IS NULL";
		$res=pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql="SELECT *
				FROM tbl_hd_franquia
				WHERE fabrica = $login_fabrica
				ORDER BY hd_franquia desc limit 1";
		$res=pg_exec($con,$sql);

		if(pg_numrows($res) >0){
			$hd_franquia=pg_result($res,0,hd_franquia);

			$sqlh = "UPDATE tbl_hd_franquia
					SET hora_utilizada=hora_utilizada + hora_desenvolvimento
					FROM tbl_hd_chamado
					WHERE tbl_hd_franquia.fabrica     = tbl_hd_chamado.fabrica
					AND   tbl_hd_chamado.hd_chamado   = $hd_chamado
					AND   tbl_hd_franquia.hd_franquia = $hd_franquia
					AND   tbl_hd_chamado.fabrica      = $login_fabrica";
			$resh = pg_exec ($con,$sqlh);
			$msg_erro .= pg_errormessage($con);
		}

		$sql = "INSERT into tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					admin
					) VALUES (
					$hd_chamado,
					'MENSAGEM AUTOMÁTICA - ESTE CHAMADO FOI APROVADO EM  $data PARA UTILIZAR HORAS FRANQUEADAS DO MÊS SEGUINTE PELO USUÁRIO $nome_completo PARA EXECUÇÃO',
					$login_admin
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Houve um erro na aprovação do Chamado.';
		}else{
			$res = @pg_exec($con,"COMMIT");
		}
	}
}


if(strlen($hd_chamado) > 0){
	$sql="SELECT (valor_hora_franqueada * (hora_desenvolvimento - hora_franqueada - saldo_hora)) as valor_excedida,
			hora_desenvolvimento
			FROM tbl_hd_franquia
			JOIN tbl_hd_chamado ON tbl_hd_franquia.fabrica=tbl_hd_chamado.fabrica
			WHERE hd_chamado=$hd_chamado
			AND   tbl_hd_franquia.fabrica = $login_fabrica
			AND   periodo_fim IS NULL ORDER BY hd_franquia desc limit 1";
	$res = @pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		$valor_excedida       = pg_result($res,0,valor_excedida);
		$hora_desenvolvimento = pg_result($res,0,hora_desenvolvimento);
		$valor_excedida       = number_format($valor_excedida,2,',','.');
	}
}
$menu_cor_linha="BBBBBB";
?>
<html>
<head>
<title>Telecontrol - Help Desk</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<body>
<?
include "menu.php";
?>

<table width="700" align="center" bgcolor="#FFFFFF" border='0'>
	<tr>
		<td >
			<table width='100%' border='0'>
				<tr>
					<td valign='top' class='Conteudo'>
						<img src="imagem/help.png" width="36" height="36" border='0' align='absmiddle'> SUPERVISOR
					</td>
					<td valign='middle'>
<?
	echo "<table width='400' align='center' cellpadding='0' cellspacing='0' border='0' bgcolor='#FFFFFF'>";
	echo "<input type='hidden' value='$insere_saldo'>";
	echo "<input type='hidden' value='$aprova_execucao'>";
	echo "<tr class='Legenda' align='center'>";
	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Aguardando aprovação";
	echo "</tr>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='2' bgcolor='$menu_cor_linha' width='1' height='1'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='2'align='center'>";
	if($insere_saldo !='sim' AND $aprova_execucao !='sim'){
		echo "<b><font color='red'>Este chamado excede o total de horas de franquia do Mes!</font></b>";
	}
	echo "</td></tr>";
	echo "</table>";
if(strlen($hd_chamado) > 0){
	$sql="SELECT hd_chamado                                                    ,
				tbl_hd_chamado.admin                                           ,
				tbl_admin.nome_completo                                        ,
				tbl_admin.login                                                ,
				titulo                                                         ,
				status                                                         ,
				atendente                                                      ,
				tbl_fabrica.nome AS fabrica_nome                               ,
				to_char (tbl_hd_chamado.data_envio_aprovacao,'DD/MM HH24:MI') AS data
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica  = tbl_fabrica.fabrica
		WHERE data_aprovacao IS NULL
		AND   data_envio_aprovacao IS NOT NULL
		AND (tbl_hd_chamado.status ='Aprovação')
		AND   hd_chamado=$hd_chamado
		AND   tbl_hd_chamado.fabrica=$login_fabrica";
	$res = @pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){

		$hd_chamado           = pg_result($res,0,hd_chamado);
		$admin                = pg_result($res,0,admin);
		$login                = pg_result($res,0,login);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$status               = pg_result($res,0,status);
		$atendente            = pg_result($res,0,atendente);
		$nome_completo        = trim(pg_result($res,0,nome_completo));
		$fabrica_nome         = trim(pg_result($res,0,fabrica_nome));
		$cor='#F2F7FF';
		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
		echo "<tr>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>
</CENTER></td>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
		echo "	<td>N°</td>";
		echo "	<td>Título</td>";
		echo "	<td>Status</td>";
		echo "	<td>Data</td>";
		echo "	<td>Solicitante</td>";
		echo "	<td align='center'>Ação</td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td><img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> $hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;

		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td nowrap>";
		if ($supervisor=='t' and ($login_fabrica == 1 or $login_fabrica ==11)){
			echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova_execucao=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA O FATURAMENTO DE $hora_desenvolvimento HORAS - R$ $valor_excedida</a><br><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>UTILIZAR HORAS FRANQUEADAS DO MÊS SEGUINTE
			<br><a href=\"javascript: if (confirm('Seu pedido continuara pendente para ser aprovado. Esta ação irá fechar a tela') == true) { window.close(); }\"><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>DEIXAR EM APROVAÇÃO</a>";
		}else{
			if ($supervisor=='t' ){
				echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova_execucao=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA O FATURAMENTO DE $hora_desenvolvimento HORAS - R$ $valor_excedida</a><br><a href='$PHP_SELF?hd_chamado=$hd_chamado&insere_saldo=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>UTILIZAR HORAS FRANQUEADAS DO MÊS SEGUINTE</a>
				<br><a href=\"javascript: if (confirm('Seu pedido continuara pendente para ser aprovado. Esta ação irá fechar a tela') == true) { window.close(); }\"><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>DEIXAR EM APROVAÇÃO</a>";
			}
		}
		echo"</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
	}else{
		echo "<center><font size='5' color='red'>";
		if($insere_saldo=='sim'){
			echo "As horas franqueadas serão utilizadas do próximo mês. Esta tela irá fechar.";
		}elseif($aprova_execucao =='sim'){
			echo "O pedido será faturado conforme a sua escolha. Esta tela irá fechar.";
		}else{
			echo "Este chamado já foi aprovado para desenvolvimento! Esta tela irá fechar.";
		}
		echo "</font></center>";
		echo "<script language='javascript'>";
		echo "window.opener=null; ";
		echo "window.open(\"\",\"_self\"); ";
		echo "setTimeout('window.close()',5000); ";
		echo "</script>";
	}
}
?>
<? include "rodape.php" ?>
</body>
</html>