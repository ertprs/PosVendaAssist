<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$backlog = 1;
if($login_fabrica == 1 or $login_fabrica == 20 or $login_fabrica == 3){
	$backlog = 3;
}

//VERIFICA SE O USUÁRIO É SUPERVISOR
$sql="  SELECT * FROM tbl_admin
		WHERE admin=$login_admin
		AND help_desk_supervisor='t'";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$supervisor='t';
	$nome_completo=pg_result($res,0,nome_completo);
}
//PEGA O NOME DA FABRICA
$sql = "SELECT   *
		FROM     tbl_fabrica
		WHERE    fabrica=$login_fabrica
		ORDER BY nome";
$res = pg_exec ($con,$sql);
$nome      = trim(pg_result($res,0,nome));

$menu_cor_fundo="EEEEEE";
$menu_cor_linha="BBBBBB";


if($_GET['conteudo'])  $conteudo  = $_GET['conteudo'];
//echo $conteudo."<br>".$ajuda;


//FIM DO SELECT DA TABELA ESTATISTICAS DE CHAMADAS---------------------------------
$hd_chamado      = $_GET['hd_chamado'];
$aprova          = $_GET['aprova'];
$aprova_execucao = $_GET['aprova_execucao'];

$data       = date("d/m/Y  h:i");

if($aprova=='sim'){
	$sql = "SELECT count(*)
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.nome = '$nome'
		AND (tbl_hd_chamado.status <> 'Resolvido' and tbl_hd_chamado.status <> 'Cancelado' and tbl_hd_chamado.status <> 'Aprovação' and tbl_hd_chamado.status <> 'Novo')
		ORDER BY tbl_hd_chamado.data DESC";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows($res) > ($backlog -1) ) {
		$msg_erro = "Somente após resolvido o chamado que está em desenvolvimento que você poderá aprovar o próximo chamado.";
	}

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$sql= " SELECT TO_CHAR(data,'DD/MM HH24:MI') AS data
			FROM tbl_hd_chamado
			WHERE fabrica = $login_fabrica
			AND hd_chamado = $hd_chamado
			AND status='Aprovação'";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (pg_numrows($res) > 0) {
		$data_abertura = pg_result($res,0,data);
	}

	$sql = "UPDATE tbl_hd_chamado
			SET exigir_resposta = 'f', status = 'Novo', data = CURRENT_TIMESTAMP
			WHERE hd_chamado = $hd_chamado
			AND   status='Aprovação'";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if(strlen($msg_erro) ==0 and strlen($data_abertura) >0 ){
		$sql = "INSERT into tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					admin
					) VALUES (
					$hd_chamado,
					'MENSAGEM AUTOMÁTICA - ESTE CHAMADO FOI ABERTO EM $data_abertura E FOI APROVADO EM $data PELO USUÁRIO $nome_completo',
					$login_admin
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if(strlen($msg_erro) > 0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg_erro .= 'Houve um erro na aprovação do Chamado.';
	}else{
		$res = @pg_exec($con,"COMMIT");
	}
}
if($cancela=='sim'){
	$sql = "UPDATE tbl_hd_chamado SET status = 'Cancelado' WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	if($login_fabrica==6){
	$sql = "INSERT into tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin
				) VALUES (
				$hd_chamado,
				'MENSAGEM AUTOMÁTICA-ESTE CHAMADO FOI CANCELADO EM $data PELO USUÁRIO $nome_completo',
				$login_admin
				)";
	$res = pg_exec ($con,$sql);
	}
}
// HD 17195
if($aprova_execucao== 'sim'){
	$sql="SELECT to_char(current_date,'MM')   AS mes,
				 to_char(current_date,'YYYY') AS ano;";
	$res=pg_exec($con,$sql);
	$mes=pg_result($res,0,mes);
	$ano=pg_result($res,0,ano);



	$sql="SELECT hora_desenvolvimento,
				data_aprovacao
			FROM tbl_hd_chamado
			WHERE hd_chamado=$hd_chamado";
	$res = pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		$hora_desenvolvimento=pg_result($res,0,hora_desenvolvimento);
		$data_aprovacao=pg_result($res,0,data_aprovacao);
		if($hora_desenvolvimento == 0 or strlen($hora_desenvolvimento)==0){
			$msg_erro="Prezado Supervisor, este chamado está sem a hora de desenvolvimento cadastrado, por favor, entrar em contato com o Suporte Telecontrol para cadastrá-lo.";
		}
		if(strlen($data_aprovacao) > 0){
			$msg_erro="Este Chamado já foi aprovado, não pode aprovar mais de uma vez";
		}

	}
	if(strlen($msg_erro) ==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");

		$sql = "UPDATE tbl_hd_chamado
				SET exigir_resposta = 'f', status = 'Análise', data_aprovacao = CURRENT_TIMESTAMP
				WHERE hd_chamado = $hd_chamado";
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
					'MENSAGEM AUTOMÁTICA - HORA DE DESENVOLVIMENTO APROVADO EM  $data PELO USUÁRIO $nome_completo',
					$login_admin
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$email_origem  = "suporte@telecontrol.com.br";
		$email_destino = "suporte@telecontrol.com.br";

		$assunto       = "Chamado $hd_chamado aprovado para execução";
		$corpo = "";
		$corpo.= "<br>O chamado $hd_chamado, que estava aguardando aprovação,foi aprovado.\n\n";
		$corpo.= "<br>Chamado n°: $hd_chamado\n\n";
		$corpo.= "<br><br>Telecontrol\n";
		$corpo.= "<br>www.telecontrol.com.br\n";
		$corpo.= "<br>_______________________________________________\n";
		$corpo.= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

		$body_top  = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		if ( mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
			$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
		}

		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Houve um erro na aprovação do Chamado.';
		}else{
			$res = @pg_exec($con,"COMMIT");
		}
	}
}

?>

<html>
<head>
<title>Telecontrol - Help Desk</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<style>
.tabela{
	font:bold;
}
.supervisor{
	font-size: 12px;
}

.supervisor ul{
	list-style-type:none;
	margin:0px;
}

</style>
<body>
<?
include "menu.php";
?>
<table width="700" align="center" bgcolor="#FFFFFF" border='0'>
	<tr>
		<td >
			<table width='100%' border='0'>
				<tr>
					<td valign='middle'>

<table width="700" align="center"><tr><td style='font-family: arial ; color: #666666; font-size:10px' align="justify">
<?
echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando aprobación&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Aprovação'>Aguarda Aprovação</a>&nbsp;";
	echo "</td>";
	echo "<td width='50%' nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_cinza.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Meus Chamados&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?admin=admin'>Meus Chamados</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Pendiente&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Análise&exigir_resposta=f'>Pendentes Telecontrol</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando su respuesta&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Análise&exigir_resposta=t'>Aguarda sua resposta</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Resolvido&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Resolvido&filtro=1'>Meus Resolvidos</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_azul_bb.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Todos Chamados&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?todos=todos&filtro=1'>Todos Chamados</a>&nbsp;";
	echo "</td>";
		echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_rosa.gif' valign='absmiddle'> ";
		echo "&nbsp;<a href='relatorio_horas_cobradas.php'>Relatório Mensal</a>&nbsp;";
	echo "</td>";
	echo "</tr>";
echo "</table>";

?>

		</td>
	</tr>
	<tr>
		<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
	</tr>
	<tr>
		<td colspan="2" class="Conteudo" align="center">
		<?
		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='3' align = 'center' width='100%' style='font-family: arial ; color:#666666' nowrap
				><CENTER>ATENÇÃO - Somente estes administradores abaixo podem aprovar chamados</CENTER></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "	<td align='CENTER'>Login</td>";
				echo "	<td align='CENTER'>Fone</td>";
				echo "	<td align='CENTER'>Email</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			$sql = "SELECT nome_completo,fone, email FROM tbl_admin where fabrica = $login_fabrica AND help_desk_supervisor is true order by nome_completo asc";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				for($i=0;$i<pg_numrows($res);$i++){
					$nome_completo = pg_result($res,$i,nome_completo);
					$fone          = pg_result($res,$i,fone);
					$email         = pg_result($res,$i,email);
					echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
					echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
					echo "	<td nowrap align='CENTER's>$nome_completo</td>";
					echo "	<td nowrap align='CENTER'>$fone</td>";
					echo "	<td nowrap align='CENTER'>$email</td>";
					echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				echo "</tr>";
				}
			} 
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='3' align = 'center' width='100%'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
		echo "</table>";

		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='1' align = 'center' width='100%' style='font-family: arial ; color:#666666' nowrap
				><CENTER>Regras básicas de funcionamento do Help-Desk</CENTER></td>";
				echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'>
				Prezado Cliente,
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				A Telecontrol, visando atender dentro de padrões internacionais de qualidade, está melhorando o seu atendimento via Help-Desk com relação ao controle do SLA de seus chamados.
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				A Fábrica deverá definir suas prioridades e indicar qual chamado deverá ser desenvolvido pela Telecontrol. Após a indicação do chamado o Supervisor verificará facilmente se está sendo atendido dentro dos prazos estipulados.
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";				
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'>
				Funcionamento do HELPDESK:				
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'><center><br>
				<img src='/assist/imagens/Processo.png' width='650'>
				<br></center>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				1) O chamado de erro continua tendo prioridade e não precisa de aprovação, basta escolher a opção “Erro de Programa”. 				
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'>
				2) O demais chamados, Backlog, serão gerenciados pelo(s) Supervisor(es) de Help-Desk de cada fabricante, que determinarão as prioridades dos chamados. A Telecontrol não fará mais este controle. 
				<br>				
				<br>				
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='left'> <FONT color=''>
				3) A triagem para desenvolvimento será da seguinte forma: 
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color=''>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				a) Após verificar o chamado, o Supervisor deverá aprovar (1ª Etapa) para seguir a análise da Telecontrol, que terá 48h para iniciar o atendimento.
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color=''>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				b) Em seguida, com o auxílio do autor do chamado, a Telecontrol fará a análise final e encaminhará a solicitação de aprovação da quantidade de horas de franquia que serão utilizadas para desenvolvimento. 
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color=''>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				c) Após aprovada a quantidade de horas (2ª Etapa), pelo Supervisor, a Telecontrol terá 48h para informar a previsão de término para resolver o chamado.
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";					
				echo "	<td align='justify'> <FONT color='RED'>
				4) O fabricante terá $backlog chamado(s) aprovado(s) e em desenvolvimento na Telecontrol, o restante ficará em sua posse com o status “EM ESPERA”.				
				<br>				
				<br>				
				</font>
				</td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='1' align = 'center' width='100%'></td>";
				echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
		echo "</table>";


		?>
	</tr>


</table>

<?
/*
$sql = "SELECT
			hd_chamado ,
			tbl_hd_chamado.admin ,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo ,
			status ,
			atendente ,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.nome = '$nome'
		AND (tbl_hd_chamado.status ='Aprovação')
		AND tbl_hd_chamado.data_envio_aprovacao IS NULL
		ORDER BY tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);


if (@pg_numrows($res) > 0) {
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>Chamados para serem aprovados</CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td>N°</td>";
	echo "	<td>Título</td>";
	echo "	<td>Status</td>";
	echo "	<td>Data</td>";
	echo "	<td>Solicitante</td>";
	echo "	<td>Ação</td>";
	echo "</tr>";


//inicio imprime chamados
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
//		$posto                = pg_result($res,$i,posto);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));


		$sql2 = "SELECT nome_completo, admin
			FROM	tbl_admin
			WHERE	admin='$atendente'";

		$res2 = pg_exec ($con,$sql2);
		$xatendente            = pg_result($res2,0,nome_completo);
		$xxatendente = explode(" ", $xatendente);

		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

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
		echo "<td>";
		if ($supervisor=='t' AND $status=='Aprovação'){
			echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA</a><br><a href='$PHP_SELF?hd_chamado=$hd_chamado&cancela=sim'><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>CANCELA";
		}
		echo"</td>";

		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>";

	}

//fim imprime chamados

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
}
*/
echo "</td>";
echo "</tr>";

echo "</table>";

$sql="SELECT to_char(current_date,'MM') as mes,
			 to_char(current_date,'YYYY') as ano;";
$res=pg_exec($con,$sql);
$mes=pg_result($res,0,mes);
$ano=pg_result($res,0,ano);
if (strlen($mes) > 0) {
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
}

$sql="SELECT saldo_hora            ,
			 mes                   ,
			 ano                   ,
			 hora_franqueada       ,
			 hora_faturada         ,
			 hora_utilizada        ,
			 valor_hora_franqueada ,
			 to_char(periodo_inicio,'DD/MM/YYYY') as periodo_inicio,
			 to_char(periodo_fim,'DD/MM/YYYY') as periodo_fim
		from tbl_hd_franquia
		where fabrica=$login_fabrica
		order by hd_franquia desc limit 2";

$res=pg_exec($con,$sql);

if(pg_numrows($res) > 0){
	$saldo_hora            = pg_result($res,0,saldo_hora);
	$hora_franqueada       = pg_result($res,0,hora_franqueada);
	$hora_faturada         = pg_result($res,0,hora_faturada);
	$hora_utilizada        = pg_result($res,0,hora_utilizada);
	$valor_hora_franqueada        = pg_result($res,0,valor_hora_franqueada);
	$valor_hora_franqueada        = number_format($valor_hora_franqueada,2,',','.');
	$periodo_inicio        = pg_result($res,0,periodo_inicio);
	$periodo_fim           = pg_result($res,0,periodo_fim);
	$mes                   = pg_result($res,0,mes);
	$ano                   = pg_result($res,0,ano);
	$valor_faturado = $hora_faturada * $valor_hora_franqueada;

	echo "<table width = '700' align = 'center' class='tabela' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='2' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>FRANQUIA DE HORAS</CENTER></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td align='center' colspan='100%'>$mes/$ano Inicio: $periodo_inicio -";
	echo "</td>";
	echo "</tr>";
	echo "<tr style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'>";
	echo "Total de franquia de horas deste mês: ";
	echo "</td>";
	echo "<td align='center'> $hora_franqueada</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'>";
	echo "Saldo de Hora: ";
	echo "</td>";
	echo "<td align='center'> $saldo_hora</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'>";
	echo "Total de horas utilizadas: ";
	echo "</td>";
	echo "<td align='center'> $hora_utilizada</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'><font color='red'>";
	echo "A fabrica pode liberar este mês, sem cobrar, o total de : ";
	echo "</td>";
	$horas_que_ainda_podem_aprovar = $hora_franqueada + $saldo_hora - $hora_utilizada;
	echo "<td align='center'> $horas_que_ainda_podem_aprovar hora(s)</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";


	echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'>";
	echo "Hora faturada: ";
	echo "</td>";
	echo "<td align='center'> $hora_faturada</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'>";
	echo "Valor faturado:";
	echo "</td>";
	echo "<td align='center'> $valor_faturado</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='2' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";

	if(pg_numrows($res) > 1){
		$saldo_hora1            = pg_result($res,1,saldo_hora);
		$hora_franqueada1       = pg_result($res,1,hora_franqueada);
		$hora_faturada1         = pg_result($res,1,hora_faturada);
		$hora_utilizada1        = pg_result($res,1,hora_utilizada);
		$valor_hora_franqueada1 = pg_result($res,1,valor_hora_franqueada);
		$valor_hora_franqueada1        = number_format($valor_hora_franqueada1,2,',','.');
		$periodo_inicio1        = pg_result($res,1,periodo_inicio);
		$periodo_fim1           = pg_result($res,1,periodo_fim);
		$mes1                   = pg_result($res,1,mes);
		$ano1                   = pg_result($res,1,ano);
		$valor_faturado1        = $hora_faturada1 * $valor_hora_franqueada1;
		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='2' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>MÊS ANTERIOR</CENTER></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
		echo "<td align='center' colspan='100%'>$mes1/$ano1 Período: $periodo_inicio1 - $periodo_fim1";
		echo "</td>";
		echo "</tr>";
		echo "<tr style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Total de franquia de horas deste mês: ";
		echo "</td>";
		echo "<td align='center'> $hora_franqueada1</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Saldo de Hora: ";
		echo "</td>";
		echo "<td align='center'> $saldo_hora1</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Total de horas utilizadas: ";
		echo "</td>";
		echo "<td align='center'> $hora_utilizada1</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Hora faturada: ";
		echo "</td>";
		echo "<td align='center'> $hora_faturada1</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Valor faturado:";
		echo "</td>";
		echo "<td align='center'> $valor_faturado1</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='2' align = 'center' width='100%'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
	}
}
////////////////////////////Chamados no Telecontrol////////////////////////////////////////
$sql = "SELECT
			hd_chamado ,
			tbl_hd_chamado.admin ,
			to_char (tbl_hd_chamado.previsao_termino,'DD/MM/YY') AS previsao_termino,
			tbl_tipo_chamado.descricao,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM/YY HH24:MI') AS data,
			titulo ,
			status ,
			atendente ,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.nome = '$nome'
		AND (tbl_hd_chamado.status <> 'Resolvido' and tbl_hd_chamado.status <> 'Cancelado' and tbl_hd_chamado.status <> 'Aprovação' and tbl_hd_chamado.status <> 'Novo')
		ORDER BY tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$numero_hd_no_telecontrol = @pg_numrows($res);
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";


	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='7' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>Chamados em análise/desenvolvimento no TELECONTROL</CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; font-size: 9px; color: #666666'>"; 
	echo "<td>N°</td>";
	echo "<td nowrap>Título</td>";
	echo "<td nowrap>Tipo</td>";
	echo "<td nowrap>Status&nbsp;</td>";
	echo "<td nowrap>Data</td>";
	echo "<td nowrap>Solicitante&nbsp;</td>";
	echo "<td nowrap>Previsão Término</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
		$tipo_chamado         = pg_result($res,$i,descricao);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));
		$previsao_termino     = pg_result($res,$i,previsao_termino);

		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td nowrap>";
		if($status =="Análise" AND $exigir_resposta <> "t"){
			echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		}elseif($exigir_resposta == "t" AND $status<>'Cancelado'OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
			echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle'> ";
		}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
				echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}
		echo "$hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		echo "<td nowrap>$tipo_chamado&nbsp;</td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td class='Conteudo'>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;

		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td nowrap>&nbsp;$previsao_termino &nbsp;</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>";

	}
//fim imprime chamados

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='7' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";


}
/////////////////////FIM DOS CHAMADOS NO TELECONTROL

//fim imprime chamados

$sql = "SELECT
			hd_chamado ,
			tbl_hd_chamado.admin ,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo ,
			status ,
			atendente ,
			tbl_fabrica.nome AS fabrica_nome,
			hora_desenvolvimento
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		AND tbl_fabrica.nome = '$nome'
		AND (tbl_hd_chamado.status ='Aprovação' or tbl_hd_chamado.status ='Novo')
		/* AND tbl_hd_chamado.data_envio_aprovacao IS NOT NULL */
		ORDER BY tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);


if (@pg_numrows($res) > 0) {
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>Chamados Aguardando Aprovação</CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; font-size: 9px; color: #666666'>";
	echo "<td>N°</td>";
	echo "<td nowrap>Título</td>";
	echo "<td>Status&nbsp;</td>";
	echo "<td>Data</td>";
	echo "<td>Solicitante</td>";
	echo "<td>Etapa</td>";
	echo "<td>Franquia/Hora</td>";
	echo "<td align='center'>Ação</td>";
	echo "</tr>";


//inicio imprime chamados
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));
		$hora_desenvolvimento = pg_result($res,$i,hora_desenvolvimento);
		if(strlen($hora_desenvolvimento)==0){
			$etapa = "1ª Etapa";
		}else{
			$etapa = "2ª Etapa";
		}
		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap><img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> $hd_chamado</td>";
		echo "<td nowrap><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>&nbsp;$titulo&nbsp;</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td nowrap>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;

		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td nowrap>&nbsp;$etapa &nbsp;</td>";
		echo "<td align='center'>&nbsp;$hora_desenvolvimento &nbsp;</td>";
		if ($supervisor=='t' AND ($status=='Aprovação' or $status=='Novo')){
			if(in_array($login_fabrica,array(76,80,59,40,52))) {
				$cond_hora = " hora_franqueada *2 ";
			}else{
				$cond_hora = " hora_franqueada+saldo_hora ";
			}
			$sql3 = "SELECT hora_utilizada
						FROM  tbl_hd_franquia
						JOIN  tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica
						WHERE tbl_hd_franquia.fabrica   = $login_fabrica
						AND   tbl_hd_chamado.hd_chamado = $hd_chamado
						AND   periodo_fim is null
						GROUP BY hora_utilizada,hora_franqueada, saldo_hora,hd_franquia,hora_desenvolvimento
						HAVING  ($cond_hora) < (hora_utilizada + hora_desenvolvimento)
						ORDER BY hd_franquia desc limit 1";
			$res3 = pg_exec ($con,$sql3);
			if(pg_numrows($res3) >0){
				$href = "<a href='aprova_faturada.php?hd_chamado=$hd_chamado' target='_blank'>";
			}else{
				$href = "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova_execucao=sim'>";
			}
			if($numero_hd_no_telecontrol > ($backlog - 1)){
				echo "<td  nowrap title='Somente após resolvido o chamado que está em desenvolvimento que poderá aprovar o próximo chamado.'>";
				echo "<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>EM ESPERA";
			}else{
				echo "<td nowrap>";
				echo "$href<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA</a><br><a href='$PHP_SELF?hd_chamado=$hd_chamado&cancela=sim'><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>CANCELA";
			}
		}else{
			echo "<td  nowrap title='Somente após resolvido o chamado que está em desenvolvimento que poderá aprovar o próximo chamado.'>";
			echo "<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>EM ESPERA";
		}
		echo"</td>";

		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>";

	}

//fim imprime chamados

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
}

echo "</td>";
echo "</tr>";

echo "</table>";

include "rodape.php" ?>
</body>
</html>