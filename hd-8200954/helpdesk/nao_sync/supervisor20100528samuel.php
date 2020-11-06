<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

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

		$assunto       = "Chamado aprovado para execução";
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
		<td colspan="2" class="Conteudo" align="center"><b>Somente:
		<?
			$sql = "SELECT nome_completo,fone, email FROM tbl_admin where fabrica = $login_fabrica AND help_desk_supervisor is true order by nome_completo asc";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<div class='supervisor'>";
				echo "<ul>";
				for($i=0;$i<pg_numrows($res);$i++){
					$nome_completo = pg_result($res,$i,nome_completo);
					$fone          = pg_result($res,$i,fone);
					$email         = pg_result($res,$i,email);
					echo "<li>$nome_completo - $fone - $email</li>";
				}
				echo "</ul>";
				echo "</div>";
			}
		
		?>
		pode/podem aprovar os chamados</font></td>
	</tr>


</table>

<!-- ====================INICIO DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->
<?
$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'novo'";
//echo $sql1;


$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'análise'";
$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}

$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'aprovação'";
$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}

$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'resolvido'";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}


$sql5 = "SELECT	 COUNT (*) AS total_cancelado
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'cancelado'";

$res5 = @pg_exec ($con,$sql5);

if (@pg_numrows($res5) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_cancelado           = pg_result($res5,0,total_cancelado);
	}


$sql6 = "SELECT	 COUNT (*) AS total_Execução
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'Execução'";;

$res6 = @pg_exec ($con,$sql6);

if (@pg_numrows($res6) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_Execução           = pg_result($res6,0,total_Execução);
	}


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
		AND (tbl_hd_chamado.status ='Aprovação')
		AND tbl_hd_chamado.data_envio_aprovacao IS NOT NULL
		ORDER BY tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);


if (@pg_numrows($res) > 0) {
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='7' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>Chamados a serem aprovados para desenvolvimento</CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td>N°</td>";
	echo "<td>Título</td>";
	echo "<td>Status</td>";
	echo "<td>Data</td>";
	echo "<td>Solicitante</td>";
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
		echo "<td align='center'>$hora_desenvolvimento </td>";
		echo "<td>";
		if ($supervisor=='t' AND $status=='Aprovação'){
			$sql3 = "SELECT hora_utilizada
						FROM  tbl_hd_franquia
						JOIN  tbl_hd_chamado ON tbl_hd_chamado.fabrica = tbl_hd_franquia.fabrica
						WHERE tbl_hd_franquia.fabrica   = $login_fabrica
						AND   tbl_hd_chamado.hd_chamado = $hd_chamado
						AND   periodo_fim is null
						GROUP BY hora_utilizada,hora_franqueada, saldo_hora,hd_franquia,hora_desenvolvimento
						HAVING  (hora_franqueada+saldo_hora) < (hora_utilizada + hora_desenvolvimento)
						ORDER BY hd_franquia desc limit 1";
			$res3 = pg_exec ($con,$sql3);
			if(pg_numrows($res3) >0){
				echo "<a href='aprova_faturada.php?hd_chamado=$hd_chamado' target='_blank'>";
			}else{
				echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova_execucao=sim'>";
			}
			echo "<img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA</a><br><a href='$PHP_SELF?hd_chamado=$hd_chamado&cancela=sim'><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>CANCELA";
		}
		echo"</td>";

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

echo "</td>";
echo "</tr>";

echo "</table>";


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
		AND tbl_hd_chamado.exigir_resposta = 'f' and (tbl_hd_chamado.status = 'Análise' OR tbl_hd_chamado.status = 'Execução' OR tbl_hd_chamado.status = 'Novo' )
		ORDER BY tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);

	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";


	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //

if (@pg_numrows($res) > 0) {
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='5' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td colspan='5'><b><CENTER>Chamados para Acompanhamento</CENTER></b></td>";
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


		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td>";
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


		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>";

	}

//fim imprime chamados

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='5' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>";
### PÉ PAGINACAO###

	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='9' align='center'>";
		// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}



	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //

	}

	echo "</td>";
	echo "</tr>";

	echo "</table>";

?>

<table width = '700' align = 'center' cellpadding='0' cellspacing='0'>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Estatística de Chamadas</b></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td nowrap colspan="9"></td>
	<td nowrap></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER>Novo: <B><? echo $total_novo ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER>Análise: <B><? echo $total_analise ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Aprovação: <B><? echo $total_aprovacao ?></B></CENTER></td>
	<td nowrap><CENTER>Resolvido: <B><? echo $total_resolvido ?></B></CENTER></td>
	<td nowrap><CENTER>Execução: <B><? echo $total_Execução ?></B></CENTER></td>
	<td nowrap><CENTER>Cancelado: <B><? echo $total_cancelado ?></B></CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap colspan="4"><a href='chamado_detalhe.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>INSERIR CHAMADO</B></a></td>
	<td nowrap align="right" colspan="4"><a href='chamado_lista.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>LISTAR CHAMADO</B></a></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</TR>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
</table>

<!-- ====================FIM DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->

<? include "rodape.php" ?>
</body>
</html>