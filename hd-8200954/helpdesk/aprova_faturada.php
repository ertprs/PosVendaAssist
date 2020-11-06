<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$hd_chamado=$_GET['hd_chamado'];
$aprova_execucao = $_GET['aprova_execucao'];
$insere_saldo = $_GET['insere_saldo'];

$data       = date("d/m/Y  h:i");

$sql="SELECT TO_CHAR(CURRENT_DATE,'MM')   AS mes,
			 TO_CHAR(CURRENT_DATE,'YYYY') AS ano;";
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
			$msg_erro=traduz("Este Chamado já foi aprovado, não pode aprovar mais de uma vez");
		}
	}

	if(strlen($msg_erro) == 0){
		$query = "SELECT ts.status_chamado,
						 ts.data_input,
						 tc.status,
						 tc.ordem
				  FROM tbl_status_chamado ts
				  JOIN tbl_controle_status tc ON tc.controle_status = ts.controle_status
				  WHERE ts.hd_chamado = {$hd_chamado}
				  AND tc.status ILIKE '%Orcamento%'
				  AND tc.ordem = 2
				  ORDER BY ts.data_input DESC;";
		$result = pg_query($con, $query);
		$status_chamado = pg_fetch_result($result, 0, 'status_chamado');

		$data = date('Y-m-d H:i:s');

		$sql = "UPDATE tbl_status_chamado 
				SET data_entrega = '{$data}'
				WHERE status_chamado = {$status_chamado}";
		pg_query($con, $sql);
		
		$sql = "SELECT * FROM tbl_controle_status WHERE status ILIKE '%Analise%' AND ordem = 1";
		$result = pg_query($con, $sql);
		
		$controle_status = pg_fetch_result($result, 0, 'controle_status');
		$dias = pg_fetch_result($result, 0, 'dias');

		$query = "SELECT  
				  tbl_admin.admin,
				  tbl_admin.nome_completo
				  FROM tbl_hd_chamado
					JOIN tbl_fabrica using(fabrica)
				  JOIN tbl_admin on tbl_admin.parametros_adicionais::jsonb->'equipe' ? (tbl_fabrica.parametros_adicionais::jsonb->>'equipe')::text and tbl_admin.ativo
				  WHERE tbl_hd_chamado.fabrica_responsavel = 10
				  AND tbl_admin.grupo_admin IN (1)
				AND tbl_hd_chamado.hd_chamado = $hd_chamado
				  AND tbl_admin.ativo IS TRUE";
        $result = pg_query($con, $query);
        $admin = pg_fetch_result($result, 0, 'admin');
        $adminName = pg_fetch_result($result, 0, 'nome_completo');

		$params = [$hd_chamado, $admin, $controle_status, 'Analise', $data];
		$sql_analise = "INSERT INTO tbl_status_chamado (
							hd_chamado,
							admin,
							controle_status,
							status,
							data_inicio,
							data_prazo
						) VALUES ($1, $2, $3, $4, $5, fn_calcula_previsao_retorno('{$data}', {$dias}, {$login_fabrica}));";
		pg_query_params($con, $sql_analise, $params);

		$qSup = "SELECT admin
				 FROM tbl_admin
				 WHERE nome_completo ILIKE 'Suporte'
				 AND fabrica = 10";
		$rSup = pg_query($con, $qSup);
		$rSup = pg_fetch_result($rSup, 0, 'admin');

		$comment = "MENSAGEM AUTOMÁTICA - Chamado transferido para <b>{$adminName}</b> para a realização da análise.";
		$params = [$hd_chamado, $comment, $rSup, true];

		$qInteracao = "INSERT INTO tbl_hd_chamado_item (
					   		hd_chamado,
					   		comentario,
					   		admin,
					   		interno
					   ) VALUES ($1, $2, $3, $4);";
		$rInteracao = pg_query_params($con, $qInteracao, $params);

		$qTransfere = "UPDATE tbl_hd_chamado
					   SET atendente = {$admin}
					   WHERE hd_chamado = {$hd_chamado}
					   AND fabrica = {$login_fabrica}";
		$rTransfere = pg_query($con, $qTransfere);

		$res = @pg_exec($con,"BEGIN TRANSACTION");

		$sql="SELECT hora_desenvolvimento,
				data_aprovacao
			FROM tbl_hd_chamado
			WHERE hd_chamado=$hd_chamado";

		$res = pg_query($con,$sql);

		if(pg_numrows($res) > 0){
			$hora_desenvolvimento = pg_fetch_result($res,0,'hora_desenvolvimento');	
		}

		$sql = "UPDATE tbl_hd_chamado
				SET exigir_resposta = 'f', status = 'Análise', data_aprovacao = CURRENT_TIMESTAMP,
					atendente            =coalesce((select admin from tbl_hd_chamado_item join tbl_admin using(admin) where hd_chamado = $hd_chamado and grupo_admin = 1 order by hd_chamado_item desc limit 1) ,atendente) 
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
			$hora_faturada=pg_result($res,0,hora_faturada);

			$hora_franqueada 	= pg_fetch_result($res,0,hora_franqueada);
			$hora_utilizada 	= pg_fetch_result($res,0,hora_utilizada);

			$saldo_horas = $hora_franqueada - $hora_utilizada;

			if($saldo_horas > $hora_desenvolvimento){
				$hora_utilizada_franquia = $hora_desenvolvimento;
			}else{
				$hora_utilizada_franquia = $saldo_horas;
			}
			

			$sqlh = "UPDATE tbl_hd_franquia
					SET hora_faturada    = tbl_hd_franquia.hora_faturada + (hora_desenvolvimento - hora_franqueada - saldo_hora +hora_utilizada),
					valor_faturado   = valor_faturado + ((hora_desenvolvimento - hora_franqueada - saldo_hora +hora_utilizada) * tbl_hd_franquia.valor_hora_franqueada),
					hora_utilizada = hora_utilizada +(hora_desenvolvimento - (hora_desenvolvimento - hora_franqueada - saldo_hora +hora_utilizada))
					FROM tbl_hd_chamado
					WHERE tbl_hd_franquia.fabrica     = tbl_hd_chamado.fabrica
					AND   tbl_hd_chamado.hd_chamado   = $hd_chamado
					AND   tbl_hd_franquia.hd_franquia = $hd_franquia
					AND   tbl_hd_chamado.fabrica      = $login_fabrica";

			$resh = pg_exec ($con,$sqlh);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT hora_faturada FROM tbl_hd_franquia WHERE hd_franquia = $hd_franquia "; 
			$res = pg_query($con,$sql);

			$hora_faturada_hd=pg_result($res,0,hora_faturada);

			$hora_fat = $hora_faturada_hd - $hora_faturada;
			$sqlh = "UPDATE tbl_hd_chamado
					SET hora_faturada = $hora_fat
					WHERE hd_chamado = $hd_chamado";
			$resh = pg_exec ($con,$sqlh);
			$msg_erro .= pg_errormessage($con);
		}

		if($sistema_lingua == 'ES'){
			$comentario = "MENSAJE AUTOMÁTICA - HORA DE DESARROLLO APROBADO Y FACTURADO EN $data POR EL CLIENTE $nome_completo";
		}else{
			$comentario = "MENSAGEM AUTOMÁTICA - HORA DE DESENVOLVIMENTO APROVADO E FATURADO EM $data PELO USUÁRIO $nome_completo";
		}

		$sql = "INSERT into tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					admin
					) VALUES (
					$hd_chamado,
					'$comentario',
					$login_admin
					)";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if($hora_utilizada_franquia > 0){

			$sql = "INSERT INTO tbl_hd_chamado_item (
						hd_chamado,
						comentario,
						admin
						) VALUES (
						$hd_chamado,
						'MENSAGEM AUTOMÁTICA - FORAM UTILIZADAS $hora_utilizada_franquia HORAS DA FRANQUIA DA FABRICA.' ,
						$login_admin
						)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			
		}

		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Houve um erro na aprovação do Chamado.';
		}else{
			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = "suporte.fabricantes@telecontrol.com.br , ricardo.tamiao@telecontrol.com.br";

			$assunto       = "Chamado faturado";
			$corpo = "";
			$corpo.= "<br>O chamado $hd_chamado foi aprovado e faturado.\n\n";
			$corpo.= "<br>Chamado n°: $hd_chamado\n\n";
			$corpo.= "<br><br>Telecontrol\n";
			$corpo.= "<br>www.telecontrol.com.br\n";
			$corpo.= "<br>_______________________________________________\n";
			$corpo.= "<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

			$body_top  = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			if ($mailer->sendMail($email_destino, stripslashes($assunto),$corpo, $email_origem)) {
				$msg .= "<br>Foi enviado um email para: ".$email_destino."<br>";
			}
			$res = @pg_exec($con,"COMMIT TRANSACTION");
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
			$msg_erro=traduz("Este Chamado já foi aprovado, não pode aprovar mais de uma vez");
		}
	}
	if(strlen($msg_erro) ==0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");


		$sql = "UPDATE tbl_hd_chamado
				SET exigir_resposta = 'f', status = 'Análise', data_aprovacao = CURRENT_TIMESTAMP,
					atendente            =coalesce((select admin from tbl_hd_chamado_item join tbl_admin using(admin) where hd_chamado = $hd_chamado and grupo_admin = 1 order by hd_chamado_item desc limit 1) ,atendente) 
				WHERE hd_chamado = $hd_chamado
				AND   data_aprovacao IS NULL";
		$res=pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);


		$sql="SELECT *
				FROM tbl_hd_franquia
				WHERE fabrica = $login_fabrica
				AND   periodo_fim IS NULL
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

		if($sistema_lingua == 'ES'){
			$comentario = "MENSAJE AUTOMÁTICO - ESTA LLAMADA FUE APROBADA EN $data PARA UTILIZAR HORAS DE FRANQUICIA PRÓXIMO MES POR USUARIO $nome_completo PARA LA APLICACIÓN";
		}else{
			$comentario = "MENSAGEM AUTOMÁTICA - ESTE CHAMADO FOI APROVADO EM  $data PARA UTILIZAR HORAS FRANQUEADAS DO MÊS SEGUINTE PELO USUÁRIO $nome_completo PARA EXECUÇÃO";
		}	

		$sql = "INSERT into tbl_hd_chamado_item (
					hd_chamado,
					comentario,
					admin
					) VALUES (
					$hd_chamado,
					'$comentario',
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
	if(in_array($login_fabrica,array(76,80,59,40,52))) {
		$cond_hora = " (hora_desenvolvimento - (hora_franqueada *2) +hora_utilizada)";
	}else{
		$cond_hora = " (hora_desenvolvimento - hora_franqueada - saldo_hora +hora_utilizada)";
	}
	$sql="SELECT (valor_hora_franqueada * $cond_hora) as valor_excedida,
				hora_desenvolvimento,
				hora_franqueada
			FROM tbl_hd_franquia
			JOIN tbl_hd_chamado ON tbl_hd_franquia.fabrica=tbl_hd_chamado.fabrica
			WHERE hd_chamado=$hd_chamado
			AND   tbl_hd_franquia.fabrica = $login_fabrica
			AND   periodo_fim IS NULL ORDER BY hd_franquia desc limit 1";
	$res = @pg_exec ($con,$sql);
	if(pg_numrows($res) > 0){
		$valor_excedida       = pg_result($res,0,valor_excedida);
		$hora_desenvolvimento = pg_result($res,0,hora_desenvolvimento);
		$hora_franqueada = pg_result($res,0,hora_franqueada);
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
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> ".traduz('Aguardando aprovação')."";
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
	if($insere_saldo !='sim' AND $aprova_execucao !='sim' and $hora_franqueada > 0 ){
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
		AND (tbl_hd_chamado.status ='Orçamento' or tbl_hd_chamado.status = 'Suspenso')
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
		echo "	<td>".traduz('Título')."</td>";
		echo "	<td>".traduz('Status')."</td>";
		echo "	<td>".traduz('Data')."</td>";
		echo "	<td>".traduz('Solicitante')."</td>";
		echo "	<td align='center'>".traduz('Ação')."</td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td><img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> $hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>".traduz($status)." </B></font></td>";
		}else{
			echo "<td nowrap>".traduz($status)." </td>";
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
		if ($login_fabrica <> 77){
			if($sistema_lingua == 'ES'){
				echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova_execucao=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APRUEBA LA FACTURACIÓN DE $hora_desenvolvimento HORAS - R$ $valor_excedida</a><br>
			<br><a href=\"javascript: if (confirm('Su pedido permanecerá pendiente de aprobación. Esta acción cerrará la pantalla.') == true) { window.close(); }\"><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>DEJAR EN APROBACIÓN</a>";
			}else{
			echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova_execucao=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA O FATURAMENTO DE $hora_desenvolvimento HORAS - R$ $valor_excedida</a><br>
			<br><a href=\"javascript: if (confirm('Seu pedido continuara pendente para ser aprovado. Esta ação irá fechar a tela') == true) { window.close(); }\"><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>DEIXAR EM APROVAÇÃO</a>";
			}
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
			if($sistema_lingua == 'ES'){
				echo "Las horas de franquicia se utilizarán el próximo mes. Esta pantalla se cerrará.";
			}else{
				echo "As horas franqueadas serão utilizadas do próximo mês. Esta tela irá fechar.";
			}
		}elseif($aprova_execucao =='sim'){
			if($sistema_lingua == 'ES'){
				echo "El pedido se facturará según su elección. Esta pantalla se cerrará.";
			}else{
			echo "O pedido será faturado conforme a sua escolha. Esta tela irá fechar.";
			}
		}else{
			if($sistema_lingua == 'ES'){
				echo "¡Esta llamada ya ha sido aprobado para desarrollo! Esta pantalla se cerrará.";
			}else{
			echo "Este chamado já foi aprovado para desenvolvimento! Esta tela irá fechar.";
			}
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
