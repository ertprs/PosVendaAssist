<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include "funcoes.php";

$programa_insert = $_SERVER['PHP_SELF'];

if ($login_fabrica == 7) {
	header ("Location: os_press_filizola.php?os=$os");
	exit;
}

/*	HD 135436(+Mondial))
	Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
	na admin/os_press e nas os_fechamento, sempre nesta função
*/
function usaDataConserto($posto, $fabrica) {
	if ($posto == '4311' or (($fabrica <> 11 and $fabrica<>1) and $posto==6359) or
		in_array($fabrica, array(2,3,5,7,11,14,15,20,43,45)) or $fabrica >50) {
		return true;
	}
	return false;
}

##Testes
#if ($login_admin == 449 OR $login_admin == 398 OR $login_admin == 805) {
#	header ("Location: os_press_20080515.php?os=$os");
#	exit;
#}


$sql = "SELECT  tbl_fabrica.os_item_subconjunto
		FROM    tbl_fabrica
		WHERE   tbl_fabrica.fabrica = $login_fabrica";
$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
	$os_item_subconjunto = pg_fetch_result ($res,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0) $os_item_subconjunto = 't';
}


$navegador = $_SERVER['HTTP_USER_AGENT'];
$mozilla = "Firefox";
$pos = strpos($navegador, $mozilla);

$btn_acao = $_POST['btn_acao'];

$os                  = trim($_GET['os']);
$mostra_valor_faturada = trim($_GET['mostra_valor_faturada']);

if($mostra_valor_faturada =='sim' and !empty($os)) { // HD 181964
	echo "<script>window.open('admin/produto_valor_faturada.php?os=$os','','height=300, width=650, top=20, left=20, scrollbars=yes')</script>";
}


if($btn_acao == 'gravar_orientacao'){ # HD 68629 para Colormaq
	$orientacao_sac = trim($_POST['orientacao_sac']);
	$orientacao_sac = htmlentities ($orientacao_sac,ENT_QUOTES);
	$orientacao_sac = nl2br($orientacao_sac);
	if (strlen ($orientacao_sac) == 0) {
		$orientacao_sac  = "null";
	}
	$sql = "UPDATE  tbl_os_extra SET orientacao_sac = trim('$orientacao_sac')
	WHERE tbl_os_extra.os = $os;";
	$res = pg_query ($con,$sql);
	$msg_erro = pg_last_error($con);
	if(strlen($msg_erro) == 0){
		echo "<script language='javascript'>\n";
		echo "	alert('Orientação gravada com sucesso.');\n";
		echo "</script>\n";
	} else {
		echo "<script language='javascript' >\n";
		echo "	alert('Erro. Não foi possível gravar a Orientação.');\n";
		echo "</script>\n";
	}
}

$apagarJustificativa = trim($_GET['apagarJustificativa']);
$justificativa       = trim($_GET['justificativa']);
#Adicionado por Fábio - 19/10/2007 - HD 6107
if (strlen($os)>0 AND strlen($apagarJustificativa)>0){

		$sql = "SELECT observacao
				FROM tbl_os_status
				WHERE os=$os
				AND os_status = $apagarJustificativa";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$observacao = pg_fetch_result ($res,0,observacao);

			$tmp = substr($observacao,0,strpos($observacao, 'Justificativa:'));

			$justificativa = "'".$tmp." Justificativa: ".$justificativa."'";

			$sql = "UPDATE tbl_os_status
					SET observacao = $justificativa
					WHERE os_status = $apagarJustificativa";
			$res = pg_query ($con,$sql);

			header("Location: $PHP_SELF?os=$os");
			exit;
		}
}

##Se a pendência estiver sem admin será setado para o primeiro que clicar na OS.
##Retirado para a Britania - Não inserie o comentario caso o ADMIN visualiza a OS pendente - HD 22775
$visualiza = trim($_GET['visualiza']);
if( ($login_fabrica == 11 OR $login_fabrica == 10) AND $visualiza == 'true' ) {
	$sql = "SELECT os_interacao, admin
				FROM tbl_os_interacao
				WHERE exigir_resposta IS TRUE
				AND os = $os
				ORDER BY os_interacao DESC LIMIT 1; ";
	$res = pg_query($con,$sql);
	$msg_erro = pg_last_error($con);
	if(pg_num_rows($res) > 0){
		if(strlen(pg_fetch_result($res,0,admin)) == 0){
			$sql2 = "SELECT login FROM tbl_admin WHERE admin = $login_admin;";
			$res2 = pg_query($con,$sql2);
			$admin_nome = pg_fetch_result($res2,0,0);
			$msg = "Esta pendência foi visualizada por <b>$admin_nome.</b>";
			if(strlen($msg_erro) == 0){
				$sql = "INSERT INTO tbl_os_interacao(
										programa	   ,
										os             ,
										comentario     ,
										exigir_resposta,
										admin          ,
										interno
									)VALUES(
										'$programa_insert',
										$os            ,
										'$msg'         ,
										't'            ,
										$login_admin   ,
										't'
									)";
				$res = pg_query($con,$sql);
				$msg_erro = pg_last_error($con);
				if(strlen($msg_erro) == 0){
					header ("Location: $PHP_SELF?os=$os");
				}
			}
		}
	}
}

# HD 44202 - Ultimo Status para as Aprovações de OS aberta a mais de 90 dias
$sql = "SELECT status_os, observacao
		FROM tbl_os_status
		WHERE os = $os
		AND status_os IN (120,122,123,126)
		ORDER BY data DESC
		LIMIT 1";
$res_status = @pg_query($con,$sql);

if (@pg_num_rows($res_status) >0) {
	$status_os_aberta = trim(pg_fetch_result($res_status,0,status_os));
	$status_os_aberta_obs = trim(pg_fetch_result($res_status,0,observacao));
}




##GRAVA - Interações na OS
if($btn_acao == 'gravar_interacao'){

	$resolvido       = $_POST['resolvido'];
	$msg             = $_POST['interacao_msg'];
	$admin           = $_POST['admin_transfere'];
	$interno         = $_POST['interno'];

	/**
     * Tratando envio de email para posto caso transferencia de OS seja efetuada para ele
     * HD 101619
     *
     * @author Augusto Pascutti
     */
    if ( strtolower($_POST['admin_transfere']) == 'posto' && empty($msg_erro) ) {

        $sql   = "SELECT email
                  FROM tbl_admin
                  WHERE tbl_admin.admin = {$login_admin}";

        $res   = pg_query($con,$sql);
        $rows  = (int) pg_num_rows($res);
        if ( $rows <= 0 ) {
            $msg_erro = "Admin não encontrado!";
        }
        $email_admin = pg_fetch_result($res,0,'email');

        $sql   = "SELECT contato_email,
						 tbl_os.sua_os
					FROM tbl_os
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = tbl_os.fabrica
					WHERE tbl_os.os = {$os}";
		$res   = pg_query($con,$sql);
		$rows  = (int) pg_num_rows($res);
		if ( $rows <= 0 ) {
			$msg_posto_erro = "Posto da OS não encontrado !";
		}
		$email_posto = pg_fetch_result($res,0,'contato_email');
		$sua_os      = pg_fetch_result($res,0,'sua_os');

		if ( empty($email_posto) && empty($msg_erro) ) {
			$msg_posto_erro = "E-mail do Posto da OS não definido !";
		}
		if ( empty($msg_erro) ) {
            // se nao existem erros, enviar o email para o POSTO
            $email_origem = $email_admin ;//'helpdesk@telecontrol.com.br';
            $assunto      = "A OS '{$sua_os}' aguarda sua interação ";
            $email_para   = $email_posto;
			//'augusto.pascutti@telecontrol.com.br';//$email_posto;
            $header       = "Content-type: text/html; charset=iso-8859-1\r\n";
            $header      .= "From: {$email_origem}\r\n";
            $mensagem     = <<<MENSAGEM
<p>
{$email_admin} colocou seguinte interação na OS {$sua_os}:
</p>
<p> &nbsp; {$msg}</p>
<p>
Atenciosamente, NKS
</p>
MENSAGEM;
            if ( @mail($email_para, stripslashes(utf8_encode($assunto)), utf8_encode($mensagem), $header) ){
                $msg_posto_sucesso = "<p>&nbsp</p> <p>O e-mail foi enviado para o posto ! ({$email_para})</p>";
            } else {
                $msg_posto_erro    = "Ocorreu um erro durante o envio de e-mail para o posto.";
            }
        }
        // Removendo informacoes do usuario destino da trsnferencia como POSTO, deixando em branco
        // para n transferir para ninguém.
        $admin = $_POST['admin_transfere'] = '';
    }
    if ( isset($msg_posto_erro) || isset($msg_posto_sucesso) ) {
        echo "<div class=\"banner\">",$msg_posto_erro,$msg_posto_sucesso,"</div>";
        unset($msg_posto_erro,$msg_posto_sucesso,$email_origem,$email_para,$heade,$assuntor,$mensagem,$sql,$res,$rows);
    }
    // --- fim do HD 101619

	if(strlen($interacao_msg) == 0){
		$msg_erro = "Por favor, insira algum comentário.";
	}

	if($resolvido == 't'){
		$exigir_resposta = 'f';
	}else{
		$exigir_resposta = 't';
	}

	if($interno <> 't'){
		$interno = 'f';
	}

	if($exigir_resposta == 'f'){##Se estiver resolvido, envia e-mail para o PA.
		$sql = "SELECT contato_email, tbl_os.sua_os, tbl_fabrica.nome
					FROM tbl_posto_fabrica
					JOIN tbl_os using(posto)
					JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND os = $os;";
		$res = pg_query($con,$sql);
		$email_para   = pg_fetch_result($res,0,contato_email);
		$posto_sua_os = pg_fetch_result($res,0,sua_os);
		$nome_fabrica = pg_fetch_result($res,0,nome);

		$email_origem  = "helpdesk@telecontrol.com.br";
		$assunto       = "Resposta da OS $posto_sua_os";
//		$email_para = 'fernando@telecontrol.com.br';
		$corpo.="Prezado,<br><br>Foi feita uma interação na sua OS $posto_sua_os por parte do Fabricante $nome_fabrica.
			<br><br>
			<P>Telecontrol Networking<br>www.telecontrol.com.br</P><br>Obs.: Não responder este email! Mensagem automatica!";
		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		if ( @mail($email_para, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
			$msg_ = "<br>Foi enviado um email para: ".$email_para."<br>";
		}
	}

	##Se for transferência entre neste IF
	if(strlen($admin) > 0 AND ($admin <> $login_admin) AND strlen($msg_erro) == 0){

		$sql = "INSERT INTO tbl_os_interacao(
								programa       ,
								os             ,
								comentario     ,
								exigir_resposta,
								interno        ,
								admin
							)VALUES(
								'$programa_insert',
								$os         ,
								'$msg'      ,
								'$exigir_resposta',
								't'         ,
								$login_admin
							)";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);

		$sql = "SELECT login, admin, email FROM tbl_admin WHERE admin = $admin;";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		if(pg_num_rows($res) > 0){
			$login_para = pg_fetch_result($res,0,login);
			$admin_para = pg_fetch_result($res,0,admin);
			$email_para = pg_fetch_result($res,0,email);
		}

		$sql = "SELECT login, admin, email FROM tbl_admin WHERE admin = $login_admin;";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
		if(pg_num_rows($res) > 0){
			$login_de = pg_fetch_result($res,0,login);
			$admin_de = pg_fetch_result($res,0,admin);
			$email_de = pg_fetch_result($res,0,email);
		}
		$msg = "O chamado foi transferido do Admin<b> $login_de </b>para<b> $login_para. </b>";

		if(strlen($msg_erro) == 0){
			$sql = "INSERT INTO tbl_os_interacao(
									programa       ,
									os             ,
									comentario     ,
									exigir_resposta,
									admin          ,
									interno
								)VALUES(
									'$programa_insert',
									$os            ,
									'$msg'         ,
									'$exigir_resposta'   ,
									$admin_para    ,
									't'
								)";
			$res = pg_query($con,$sql);

		}
		##Envia e-mail para o Admin DESTINO
		if(strlen($msg_erro) == 0){
			$sql = "SELECT sua_os FROM tbl_os WHERE os = $os;";
			$res = pg_query($con,$sql);
			$sua_os = pg_fetch_result($res,0,0);
			$email_origem  = "helpdesk@telecontrol.com.br";
			$assunto       = "FOI TRANSFERIDA UMA PENDÊNCIA DE OS PARA VOCÊ";
//			$email_para = 'fernando@telecontrol.com.br';
			$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
				Há uma pendência na OS <b>$sua_os</b> esperando a sua interação.<br><br>
				Acesse a aba <b>'Callcenter'</b> no link <b>'OS´s Pendentes' para visualiza-la.<br>
				<P>Telecontrol Networking<br>www.telecontrol.com.br</P>";
			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			if ( @mail($email_para, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
				$msg = "<br>Foi enviado um email para: ".$email_para."<br>";
			}else{
				$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
			}
		}
	}else{
		if(strlen($msg_erro) == 0){
			$sql = "INSERT INTO tbl_os_interacao(
									programa       ,
									os             ,
									comentario     ,
									exigir_resposta,
									interno        ,
									admin
								)VALUES(
									'$programa_insert',
									$os         ,
									'$msg'      ,
									'$exigir_resposta',
									'$interno'  ,
									$login_admin
								)";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);
		}
	}
	##Se resolvido, seta todas as interações como exigir_resposta = false para sumir da tela do admin.
	if($exigir_resposta == 'f' AND strlen($msg_erro) == 0){
		$sql = "UPDATE tbl_os_interacao SET exigir_resposta = false WHERE os = $os;";
		$res = pg_query($con,$sql);
		$msg_erro = pg_last_error($con);
	}
	if(strlen($msg_erro) == 0){
		header ("Location: $PHP_SELF?os=$os");
	}
}


echo "<div class='banner'";if($pos == false) echo "id='janela'";echo " >";


#------------ Detecta OS para Auditoria -----------#
$auditoria = $_GET['auditoria'];
$auditoria_motivo = '';
if ($auditoria == 't') {

	$btn_acao                 = $_POST['btn_acao'];
	$os                       = $_POST['os'];
	$sua_os                   = $_POST['sua_os'];
	$posto                    = $_POST['posto'];
	$justificativa_reprova    = $_POST['justificativa_reprova'];

	if(strlen($posto)==0)$posto    = $_GET['posto'];



	//--=== As ações de cada botão ===========================================================
	if ($btn_acao == 'Reprovar') {
		$sql = "UPDATE tbl_os_extra SET status_os = 13 WHERE os = $os";
		$res = pg_query ($con,$sql);

		$sql = "UPDATE tbl_os_item SET
					admin_liberacao = $login_admin,
					liberacao_pedido           = 'f',
					liberacao_pedido_analisado = TRUE
				WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
		$sql = "SELECT fn_auditoria_previa_admin($os,$login_admin,'f','0')";
		$res = pg_query ($con,$sql);

		/* EXCLUI A OS */
		$justificativa_exclusao = " OS CANCELADA. Após ser auditada, a OS foi cancelada. <br>Justificativa da Fábrica: $justificativa_reprova";
		$sql =	"INSERT INTO tbl_os_status (
					os         ,
					observacao ,
					status_os
				) VALUES (
					$os    ,
					'$justificativa_exclusao',
					15
				)";
		$res = pg_query($con,$sql);

		$sql = "UPDATE tbl_os SET
					excluida = true,
					data_fechamento = CURRENT_DATE,
					finalizada      = CURRENT_TIMESTAMP
				WHERE  tbl_os.os           = $os
				AND    tbl_os.fabrica      = $login_fabrica;";
		$res = pg_query($con,$sql);


		/* INSERE COMUNICADO PARA O POSTO */
		$sql = "INSERT INTO tbl_comunicado (
			descricao              ,
			mensagem               ,
			tipo                   ,
			fabrica                ,
			obrigatorio_os_produto ,
			obrigatorio_site       ,
			posto                  ,
			ativo
		) VALUES (
			'OS $sua_os foi CANCELADA',
			'Após ser auditada, a OS $sua_os foi cancela. <br><br>Justificativa da Fábrica: $justificativa_reprova',
			'Pedido de Peças' ,
			$login_fabrica    ,
			'f'               ,
			't'               ,
			$posto            ,
			't'
		);";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_last_error($con);

	}elseif ($btn_acao == 'Analisar') {
		//Analisar: os não retorna mais no dia atual para auditoria apenas no dia seguinte
		$sql = "UPDATE tbl_os_extra SET
					status_os = 20,
					data_status = current_date
				WHERE os = $os";
		$res = pg_query ($con,$sql);

		$sql = "UPDATE tbl_os_item SET
					admin_liberacao = $login_admin,
					liberacao_pedido = 'f',
,					liberacao_pedido_analisado = TRUE
				WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
		$sql = "SELECT fn_auditoria_previa_admin ($os,$login_admin,'f','0')";
		$res = pg_query ($con,$sql);

		//seta cancelada = false pq na função cancela toda OS nãi liberada.
		$sql = "UPDATE tbl_os_auditar set cancelada = false where os = $os";
		$res = pg_query ($con,$sql);

	}elseif ($btn_acao == 'Aprovar') {
		$sql = "UPDATE tbl_os_extra SET status_os = 19 WHERE os = $os";
		$res = pg_query ($con,$sql);

		$sql = "UPDATE tbl_os_item SET
					admin_liberacao = $login_admin,
					liberacao_pedido = 't' ,
,					liberacao_pedido_analisado = TRUE,
					data_liberacao_pedido = CURRENT_TIMESTAMP
				WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
		$sql = "SELECT fn_auditoria_previa_admin ($os,$login_admin,'t','0')";
		$res = pg_query ($con,$sql);
	}elseif ($btn_acao == 'Aprovar sem Mão de Obra'){
		$sql = "UPDATE tbl_os_extra SET status_os = 19 WHERE os = $os";
		$res = pg_query ($con,$sql);


		$sql = "SELECT fn_auditoria_previa_admin ($os,$login_admin,'t',mao_de_obra) FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);


	}
	$os = "";
	//--======================================================================================


	//hd 7118 - dependendo do dia da semana não deve contar sábado e domingo
	$sql_dia_semana = "SELECT  extract(dow from now()) + 1 as dia_da_semana";
	$res_dia_semana = pg_query($con,$sql_dia_semana);
	if (pg_fetch_result($res_dia_semana,0,0) == 2 or pg_fetch_result($res_dia_semana,0,0) == 3 or pg_fetch_result($res_dia_semana,0,0) == 4)
		$intervalo = "5 DAY";
	elseif (pg_fetch_result($res_dia_semana,0,0) == 5 or pg_fetch_result($res_dia_semana,0,0) == 6 or pg_fetch_result($res_dia_semana,0,0) == 7)
		$intervalo = "3 DAY";
	elseif (pg_fetch_result($res_dia_semana,0,0) == 1)
		$intervalo = "4 DAY";


	$sql = "
		SELECT (
			SELECT  count(tbl_os.os) AS total_reincidente
			FROM tbl_os
			JOIN tbl_os_auditar    USING(os)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os.posto           = $posto
			AND   tbl_os.fabrica         = $login_fabrica
			AND   tbl_os_auditar.auditar = 1
			AND   tbl_os.auditar          IS TRUE
			AND   tbl_os_auditar.liberado IS NOT TRUE
			AND   tbl_os_auditar.cancelada IS NOT TRUE
			AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
			AND   (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL)
		) AS total_reincidente,
		(
			SELECT  count(tbl_os.os) AS total_reincidente
			FROM tbl_os
			JOIN tbl_os_auditar    USING(os)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os.posto           = $posto
			AND   tbl_os.fabrica         = $login_fabrica
			AND   tbl_os_auditar.auditar = 2
			AND   tbl_os.auditar          IS TRUE
			AND   tbl_os_auditar.liberado IS NOT TRUE
			AND   tbl_os_auditar.cancelada IS NOT TRUE
			AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
			AND   (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL)
		) AS total_tres_pecas,
		(
			SELECT  count(tbl_os.os) AS total_reincidente
			FROM tbl_os
			JOIN tbl_os_auditar    USING(os)
			JOIN tbl_posto         USING(posto)
			JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os.posto           = $posto
			AND   tbl_os.fabrica         = $login_fabrica
			AND   tbl_os_auditar.auditar = 3
			AND   tbl_os.auditar          IS TRUE
			AND   tbl_os_auditar.liberado IS NOT TRUE
			AND   tbl_os_auditar.cancelada IS NOT TRUE
			AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'
			AND   (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL)
		) AS total_datas_diferentes";
	$res = pg_query ($con,$sql);
	if(pg_num_rows($res) == 1){
		$total_reincidente      = pg_fetch_result ($res,0,total_reincidente)     ;
		$total_tres_pecas       = pg_fetch_result ($res,0,total_tres_pecas)      ;
		$total_datas_diferentes = pg_fetch_result ($res,0,total_datas_diferentes);
	}

	$sql = "SELECT  tbl_os.os                     ,
					tbl_os_auditar.auditar        ,
					tbl_os_auditar.descricao      ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome
			FROM tbl_os
			JOIN tbl_os_auditar    ON tbl_os.os = tbl_os_auditar.os
			JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto =  tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os.posto   = $posto
			AND   tbl_os.fabrica = $login_fabrica
			AND   tbl_os.auditar          IS TRUE
			AND   tbl_os_auditar.liberado IS NOT TRUE
			AND   tbl_os_auditar.cancelada IS NOT TRUE
			AND   tbl_os_auditar.data::date >= current_date-INTERVAL'$intervalo'

			AND   (tbl_os_extra.data_status < current_date OR tbl_os_extra.data_status ISNULL)
			LIMIT 1";

	$res = pg_query ($con,$sql);
	if(pg_num_rows($res) == 1){
		$os           = pg_fetch_result ($res,0,os)          ;
		$auditar      = pg_fetch_result ($res,0,auditar)     ;
		$descricao    = pg_fetch_result ($res,0,descricao)   ;
		$nome         = pg_fetch_result ($res,0,nome)        ;
		$codigo_posto = pg_fetch_result ($res,0,codigo_posto);

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#003399'  align='center' width='700'>";
		echo "<tr>";
		echo "<td colspan='2'><b><font color='#000099'>AUDITORIA DE OS</font></b></td>";
		echo "<td rowspan='3'><font size='1'>OS Reincidentes: <b>$total_reincidente</b><br>OS com 3 ou mais peças: <b>$total_tres_pecas</b><br>OS com peças lançadas em datas diferentes: <b>$total_datas_diferentes</b></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Posto</td>";
		echo "<td class='Conteudo'>$codigo_posto - $nome</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='subtitulo'>Motivo</td>";
		echo "<td class='Conteudo'><font color='#990000'>$descricao</font></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br>";
		echo "</div>";
	}else{
		echo "<center><h1>Todas OS desse posto foram auditadas</h1>";
		echo "<a href=\"javascript:window.close();\">Fechar esta janela</a></center>";
		exit;
	}




}


if ($auditar === false) {
	echo "<p><h1>Todas as OS auditadas </h1><p>";
	exit;
}

if($login_fabrica==19){//hd 19833 3/6/2008
	$sql_revendas = "tbl_revenda.cnpj AS revenda_cnpj                                  ,
					 tbl_revenda.nome AS revenda_nome                                  ,";

	$join_revenda = "LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_os.revenda";
}else{//lpad 25/8/2008 HD 34515
	$sql_revendas = "tbl_os.revenda_nome                                               ,
					 lpad(tbl_os.revenda_cnpj, 14, '0') AS revenda_cnpj                ,";
}

#------------ Le OS da Base de dados ------------#
if (strlen ($os) == 0) $os = $_GET['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT  tbl_os.posto                                                      ,
					tbl_os.sua_os                                                     ,
					tbl_os.sua_os_offline                                             ,
					tbl_admin.login                              AS admin             ,
					troca_admin.login                            AS troca_admin       ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura     ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento   ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
					tbl_os.tipo_atendimento                                           ,
					tbl_os.tecnico_nome                                               ,
					tbl_tipo_atendimento.descricao                 AS nome_atendimento,
					tbl_os.consumidor_nome                                            ,
					tbl_os.consumidor_fone                                            ,
					tbl_os.consumidor_celular                                         ,
					tbl_os.consumidor_fone_comercial                                  ,
					tbl_os.consumidor_endereco                                        ,
					tbl_os.consumidor_numero                                          ,
					tbl_os.consumidor_complemento                                     ,
					tbl_os.consumidor_bairro                                          ,
					tbl_os.consumidor_cep                                             ,
					tbl_os.consumidor_cidade                                          ,
					tbl_os.consumidor_estado                                          ,
					tbl_os.consumidor_cpf                                             ,
					tbl_os.consumidor_email                                           ,
					tbl_os.consumidor_fone_recado                                     ,
					$sql_revendas
					tbl_os.nota_fiscal                                                ,
					tbl_os.cliente                                                    ,
					tbl_os.revenda                                                    ,
					tbl_os.os_reincidente                        AS reincidencia      ,
					tbl_os.motivo_atraso                                              ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_defeito_reclamado.descricao              AS defeito_reclamado ,
					tbl_os.defeito_reclamado_descricao                                ,
					tbl_defeito_constatado.descricao             AS defeito_constatado,
					tbl_defeito_constatado.codigo                AS defeito_constatado_codigo,
					tbl_causa_defeito.descricao                  AS causa_defeito     ,
					tbl_causa_defeito.codigo                     AS causa_defeito_codigo ,
					tbl_os.aparencia_produto                                          ,
					tbl_os.acessorios                                                 ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.obs                                                        ,
					tbl_os.rg_produto                                                 ,
					tbl_os.excluida                                                   ,
					tbl_os.promotor_treinamento                                       ,
					tbl_os.autorizacao_cortesia                                       ,
					tbl_os.certificado_garantia                                       ,
					tbl_produto.referencia                                            ,
					tbl_produto.descricao                                             ,
					tbl_produto.voltagem                                              ,
					tbl_produto.valor_troca                                           ,
					tbl_os.qtde_produtos                                              ,
					tbl_os.serie                                                      ,
					tbl_os.serie_reoperado                                            ,
					tbl_os.posto                                                      ,
					tbl_os.codigo_fabricacao                                          ,
					tbl_os.troca_garantia                                             ,
					tbl_os.troca_via_distribuidor                                     ,
					tbl_os.troca_garantia_admin                                       ,
					to_char(tbl_os.troca_garantia_data,'DD/MM/YYYY') AS troca_garantia_data ,
					tbl_posto_fabrica.codigo_posto               AS posto_codigo      ,
					tbl_posto.nome                               AS posto_nome        ,
					tbl_posto.posto                              AS codigo_posto      ,
					tbl_posto.endereco                           AS posto_endereco    ,
					tbl_posto.numero                             AS posto_num         ,
					tbl_posto.complemento                        AS posto_complemento ,
					tbl_posto.cep                                AS posto_cep         ,
					tbl_posto.cidade                             AS posto_cidade      ,
					tbl_posto.estado                             AS posto_estado      ,
					tbl_posto_fabrica.contato_fone_comercial                  AS posto_fone        ,
					tbl_os_extra.os_reincidente                                       ,
					tbl_os_extra.orientacao_sac                                       ,
					tbl_os.ressarcimento                                              ,
					tbl_os.obs_reincidencia                                           ,
					tbl_os.solucao_os                                                 ,
					tbl_os.fisica_juridica                                            ,
					tbl_marca.marca ,
					tbl_marca.nome as marca_nome,
					tbl_os.tipo_os                                                    ,
					(	select observacao
						from tbl_os_status
						where os        = tbl_os.os
						and   status_os = 15
						order by data desc limit 1
					)                                                AS motivo_exclusao,
					tbl_os.nota_fiscal_saida                                           ,
					to_char(tbl_os.data_nf_saida,'DD/MM/YYYY') as data_nf_saida        ,
					to_char(tbl_os.data_conserto,'DD/MM/YYYY HH24:MI') as data_conserto,
					tbl_os.troca_faturada                                              ,
					tbl_os_extra.tipo_troca                                            ,
					tbl_os.os_posto                                                    ,
					to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI') as data_ressarcimento,
					tbl_extrato.extrato                                                  ,
					to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_previsao,
					to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_pagamento,
					tbl_os.fabricacao_produto                                           ,
					tbl_os.qtde_km                                                      ,
					tbl_os.quem_abriu_chamado                                           ,
					tbl_os.os_numero
			FROM    tbl_os
			JOIN    tbl_posto                   ON tbl_posto.posto         = tbl_os.posto
			JOIN    tbl_posto_fabrica           ON  tbl_posto_fabrica.posto   = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN    tbl_os_extra           ON tbl_os.os               = tbl_os_extra.os
			LEFT JOIN  tbl_extrato            ON tbl_extrato.extrato                   = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
			LEFT JOIN  tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato        = tbl_extrato.extrato
			LEFT JOIN    tbl_admin              ON tbl_os.admin  = tbl_admin.admin
			LEFT JOIN    tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
			LEFT JOIN    tbl_defeito_reclamado  ON tbl_os.defeito_reclamado  = tbl_defeito_reclamado.defeito_reclamado
			LEFT JOIN    tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			LEFT JOIN    tbl_causa_defeito      ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
			LEFT JOIN    tbl_produto            ON tbl_os.produto            = tbl_produto.produto
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca
			$join_revenda
			WHERE   tbl_os.os = $os
			AND     tbl_os.fabrica = $login_fabrica";

	$res = pg_query ($con,$sql);

#	echo $sql . "<br>- ". pg_num_rows ($res);

	if (pg_num_rows ($res) > 0) {
		$posto                       = pg_fetch_result ($res,0,posto);
		$sua_os                      = pg_fetch_result ($res,0,sua_os);
		$admin                       = pg_fetch_result ($res,0,admin);
		$data_digitacao              = pg_fetch_result ($res,0,data_digitacao);
		$data_abertura               = pg_fetch_result ($res,0,data_abertura);
		$data_fechamento             = pg_fetch_result ($res,0,data_fechamento);
		$data_finalizada             = pg_fetch_result ($res,0,finalizada);
		$consumidor_nome             = pg_fetch_result ($res,0,consumidor_nome);
		$consumidor_endereco         = pg_fetch_result ($res,0,consumidor_endereco);
		$consumidor_numero           = pg_fetch_result ($res,0,consumidor_numero);
		$consumidor_complemento      = pg_fetch_result ($res,0,consumidor_complemento);
		$consumidor_bairro           = pg_fetch_result ($res,0,consumidor_bairro);
		$consumidor_cidade           = pg_fetch_result ($res,0,consumidor_cidade);
		$consumidor_estado           = pg_fetch_result ($res,0,consumidor_estado);
		$consumidor_cep              = pg_fetch_result ($res,0,consumidor_cep);
		$consumidor_fone             = pg_fetch_result ($res,0,consumidor_fone);
		$consumidor_celular          = pg_fetch_result ($res,0,consumidor_celular);
		$consumidor_fone_comercial   = pg_fetch_result ($res,0,consumidor_fone_comercial);
		$consumidor_cpf              = pg_fetch_result ($res,0,consumidor_cpf);
		$consumidor_email            = pg_fetch_result ($res,0,consumidor_email);
		$revenda_cnpj                = pg_fetch_result ($res,0,revenda_cnpj);
		$revenda_nome                = pg_fetch_result ($res,0,revenda_nome);
		$motivo_atraso               = pg_fetch_result ($res,0,motivo_atraso);
		$nota_fiscal                 = pg_fetch_result ($res,0,nota_fiscal);
		$data_nf                     = pg_fetch_result ($res,0,data_nf);
		$cliente                     = pg_fetch_result ($res,0,cliente);
		$revenda                     = pg_fetch_result ($res,0,revenda);
		$rg_produto                   = pg_fetch_result ($res,0,rg_produto);
		$defeito_reclamado           = pg_fetch_result ($res,0,defeito_reclamado);
		$aparencia_produto           = pg_fetch_result ($res,0,aparencia_produto);
		$acessorios                  = pg_fetch_result ($res,0,acessorios);
		$defeito_reclamado_descricao = pg_fetch_result ($res,0,defeito_reclamado_descricao);
		$produto_referencia          = pg_fetch_result ($res,0,referencia);
		$produto_descricao           = pg_fetch_result ($res,0,descricao);
		$produto_voltagem            = pg_fetch_result ($res,0,voltagem);
		$serie                       = pg_fetch_result ($res,0,serie);
		$serie_reoperado             = pg_fetch_result ($res,0,serie_reoperado);
		if($login_fabrica==14) $numero_controle = $serie_reoperado; //HD 56740
		$codigo_fabricacao           = pg_fetch_result ($res,0,codigo_fabricacao);
		$consumidor_revenda          = pg_fetch_result ($res,0,consumidor_revenda);
		$defeito_constatado          = pg_fetch_result ($res,0,defeito_constatado);
		$defeito_constatado_codigo   = pg_fetch_result ($res,0,defeito_constatado_codigo);
		$causa_defeito_codigo        = pg_fetch_result ($res,0,causa_defeito_codigo);
		$causa_defeito               = pg_fetch_result ($res,0,causa_defeito);
		$posto_codigo                = pg_fetch_result ($res,0,posto_codigo);
		$posto_nome                  = pg_fetch_result ($res,0,posto_nome);
		$posto_endereco              = pg_fetch_result ($res,0,posto_endereco);
		$posto_num                   = pg_fetch_result ($res,0,posto_num);
		$posto_complemento           = pg_fetch_result ($res,0,posto_complemento);
		$posto_cep                   = pg_fetch_result ($res,0,posto_cep);
		$posto_cidade                = pg_fetch_result ($res,0,posto_cidade);
		$posto_estado                = pg_fetch_result ($res,0,posto_estado);
		$posto_fone                  = pg_fetch_result ($res,0,posto_fone);
		$obs_os                      = pg_fetch_result ($res,0,obs);
		$qtde_produtos               = pg_fetch_result ($res,0,qtde_produtos);
		$excluida                    = pg_fetch_result ($res,0,excluida);
		$os_reincidente              = trim(pg_fetch_result ($res,0,os_reincidente));
		$reincidencia                = trim(pg_fetch_result ($res,0,reincidencia));
		$orientacao_sac              = pg_fetch_result ($res,0,orientacao_sac);
		$solucao_os              = trim(pg_fetch_result ($res,0,solucao_os));
		$troca_garantia        = trim(pg_fetch_result($res,0,troca_garantia));
		$troca_garantia_data   = trim(pg_fetch_result($res,0,troca_garantia_data));
		$troca_garantia_admin  = trim(pg_fetch_result($res,0,troca_garantia_admin));
		$motivo_exclusao       = trim(pg_fetch_result($res,0,motivo_exclusao));
		$certificado_garantia       = trim(pg_fetch_result($res,0,certificado_garantia));
		$autorizacao_cortesia       = trim(pg_fetch_result($res,0,autorizacao_cortesia));
		$promotor_treinamento       = trim(pg_fetch_result($res,0,promotor_treinamento));
		$fisica_juridica            = trim(pg_fetch_result($res,0,fisica_juridica));
		$data_conserto              = pg_fetch_result ($res,0,data_conserto);
		$troca_faturada             = pg_fetch_result ($res,0,troca_faturada);
		$tipo_troca                 = pg_fetch_result ($res,0,tipo_troca); //HD 51792
		$consumidor_fone_recado     = pg_fetch_result ($res,0,consumidor_fone_recado);
		$os_posto                   = pg_fetch_result ($res,0,os_posto);
		$data_ressarcimento         = pg_fetch_result ($res,0,data_ressarcimento);
		$valor_troca                = pg_fetch_result ($res,0,valor_troca);

		if($fisica_juridica=="F"){
			$fisica_juridica = "Pessoa Física";
		}
		if($fisica_juridica=="J"){
			$fisica_juridica = "Pessoa Jurídica";
		}

		$tipo_atendimento   = trim(pg_fetch_result($res,0,tipo_atendimento));
		$tecnico_nome       = trim(pg_fetch_result($res,0,tecnico_nome));
		$nome_atendimento   = trim(pg_fetch_result($res,0,nome_atendimento));
		$sua_os_offline     = trim(pg_fetch_result($res,0,sua_os_offline));
		$marca_nome         = trim(pg_fetch_result($res,0,marca_nome));
		$marca              = trim(pg_fetch_result($res,0,marca));

		$ressarcimento = trim(pg_fetch_result($res,0,ressarcimento));
		$troca_admin   = trim(pg_fetch_result($res,0,troca_admin));
		$codigo_posto   = trim(pg_fetch_result($res,0,posto));
		$obs_reincidencia   = trim(pg_fetch_result($res,0,obs_reincidencia));
		$tipo_os             = trim(pg_fetch_result($res,0,tipo_os));
		$nota_fiscal_saida   = trim(pg_fetch_result($res,0,nota_fiscal_saida));
		$data_nf_saida       = trim(pg_fetch_result($res,0,data_nf_saida));

		//--==== Dados Extrato HD 61132 ====================================
		$extrato        = trim(pg_fetch_result($res,0,extrato));
		$data_previsao  = trim(pg_fetch_result($res,0,data_previsao));
		$data_pagamento = trim(pg_fetch_result($res,0,data_pagamento));

		// HD 64152
		$fabricacao_produto = trim(pg_fetch_result($res,0,fabricacao_produto));
		$qtde_km            = trim(pg_fetch_result($res,0,qtde_km));
		$os_numero          = trim(pg_fetch_result($res,0,os_numero));
		$quem_abriu_chamado = trim(pg_fetch_result($res,0,quem_abriu_chamado));
		if(strlen($qtde_km) == 0) $qtde_km = 0;

		if(strlen($promotor_treinamento)>0){
			$sql = "SELECT nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $promotor_treinamento";
			$res_pt = pg_query($con,$sql);
			if (@pg_num_rows($res_pt) >0) {
			$promotor_treinamento  = trim(@pg_fetch_result($res_pt,0,nome));
			}
		}

		# HD 13940 - Ultimo Status para as Aprovações de OS
		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (92,93,94)
				ORDER BY data DESC
				LIMIT 1";
		$res_status = @pg_query($con,$sql);
		if (@pg_num_rows($res_status) >0) {
			$status_recusa_status_os  = trim(pg_fetch_result($res_status,0,status_os));
			$status_recusa_observacao = trim(pg_fetch_result($res_status,0,observacao));
			if($status_recusa_status_os == 94){
				$os_recusada = 't';
			}
		}

		if (strlen($revenda) > 0) {
			$sql = "SELECT  tbl_revenda.endereco   ,
							tbl_revenda.numero     ,
							tbl_revenda.complemento,
							tbl_revenda.bairro     ,
							tbl_revenda.cep        ,
							tbl_revenda.fone       ,
							tbl_revenda.email
					FROM    tbl_revenda
					WHERE   tbl_revenda.revenda = $revenda;";
			$res1 = pg_query ($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$revenda_endereco    = strtoupper(trim(pg_fetch_result ($res1,0,endereco)));
				$revenda_numero      = trim(pg_fetch_result ($res1,0,numero));
				$revenda_complemento = strtoupper(trim(pg_fetch_result ($res1,0,complemento)));
				$revenda_bairro      = strtoupper(trim(pg_fetch_result ($res1,0,bairro)));
				$revenda_email       = trim(pg_fetch_result ($res1,0,email));
				$revenda_fone        = strtoupper(trim(pg_fetch_result ($res1,0,fone)));
				$revenda_cep         = trim(pg_fetch_result ($res1,0,cep));
				$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
			}
		}
		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}

			if($aparencia_produto=='NEW')$aparencia_produto= $aparencia_produto.' - Bom Estado';
			if($aparencia_produto=='USL')$aparencia_produto= $aparencia_produto.' - Uso intenso';
			if($aparencia_produto=='USN')$aparencia_produto= $aparencia_produto.' - Uso Normal';
			if($aparencia_produto=='USH')$aparencia_produto= $aparencia_produto.' - Uso Pesado';
			if($aparencia_produto=='ABU')$aparencia_produto= $aparencia_produto.' - Uso Abusivo';
			if($aparencia_produto=='ORI')$aparencia_produto= $aparencia_produto.' - Original, sem uso';
			if($aparencia_produto=='PCK')$aparencia_produto= $aparencia_produto.' - Embalagem';

	}
}

if (strlen($sua_os) == 0) $sua_os = $os;

$title = "Confirmação de Ordem de Serviço";

$layout_menu = 'os';
include "cabecalho.php";

?>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>

<style type="text/css">

body {
	margin: 0px;
}

body {
	margin: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}



.titulo3 {
	font-family: Arial;
	font-size: 7pt;
	text-align: left;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	color: #FFFFFF;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}

.Tabela{
	border:1px solid #d2e4fc;
	background-color:#485989;
	}
.subtitulo {
	font-family: Verdana;
	FONT-SIZE: 9px;
	text-align: left;
	background: #F4F7FB;
	padding-left:5px
}
.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}
.inpu{
	border:1px solid #666;
}
.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}

table.bordasimples {border-collapse: collapse;}

table.bordasimples tr td {border:1px solid #000000;}

</style>

<script>
	function excluirComentario(os,os_status){

		if (confirm('Deseja alterar este comentário?')){
			var justificativa = prompt('Informe a nova justificativo. É Opcional.', '');
			if (justificativa==null){
				return;
			}else{
				window.location = "<?=$PHP_SELF?>?os="+os+"&apagarJustificativa="+os_status+"&justificativa="+justificativa;
			}
		}
	}
</script>

<?
if ($auditoria == 't'){
?>
<style type="text/css">
div.banner {
  margin:       0;
  font-size:   10px;
  position:    absolute;
  top:         0em;
  left:        auto;
  width:       100%;
  right:       0em;
  background:  #F7F5F0;
  border-bottom: 1px solid #FF9900;
}




body>div.banner {position: fixed}

</style>

<script>
function janela(a , b , c , d) {
	var arquivo = a;
	var janela = b;
	var largura = c;
	var altura = d;
	posx = (screen.width/2)-(largura/2);
	posy = (screen.height/2)-(altura/2);
	features="width=" + largura + " height=" + altura + " status=yes scrollbars=yes";
	newin = window.open(arquivo,janela,features);
	newin.focus();
}


window.onscroll = function(){
	var p = document.getElementById("janela") || document.all["janela"];
	var y1 = y2 = y3 = 0, x1 = x2 = x3 = 0;

	if (document.documentElement) y1 = document.documentElement.scrollTop || 0;
	if (document.body) y2 = document.body.scrollTop || 0;
	y3 = window.scrollY || 0;
	var y = Math.max(y1, Math.max(y2, y3));

	if (document.documentElement) x1 = document.documentElement.scrollLeft || 0;
	if (document.body) x2 = document.body.scrollLeft || 0;
	x3 = window.scrollX || 0;
	var x = Math.max(x1, Math.max(x2, x3));

	p.style.top = (parseInt(p.initTop) + y) + "px";
	p.style.left = (parseInt(p.initLeft) + x) + "px";
	p.style.marginLeft = (0) + "px";
	p.style.marginTop = (0) + "px";
}

window.onload = function(){
	var p = document.getElementById("janela") || document.all["janela"];
	p.initTop = p.offsetTop; p.initLeft = p.offsetLeft;
	window.onscroll();
}


</script>
<?
}
?>
<p>

<?

if ($login_fabrica == 50 or $login_fabrica == 14) {


$sql = "SELECT os_interacao
				FROM tbl_os_interacao
				WHERE os = $os;";
	$res = pg_query($con,$sql);
	$cont = 0;
	if(pg_num_rows($res) > 0){

?>
	<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
	<TR>
		<TD><font size='2' color='#FFFFFF'><center><b><? if ($login_fabrica ==3) echo "SUPORTE TÉCNICO"; else echo "INTERAGIR NA OS"; ?></b></center></font></TD>
	</TR>
	<TR><TD class='conteudo'>
	<FORM NAME='frm_interacao' METHOD=POST ACTION="<? echo "$PHP_SELF?os=$os"; ?>">
	<?
			$sql = "SELECT excluida from tbl_os where os = $os";
			$res = pg_query($con,$sql);

			$excluida = pg_fetch_result($res,0,0);

		if ($excluida <> 't') {
	?>
	<TABLE width='500' align='center' cellpadding='0' cellspacing='0'>
	<TR>
		<TD>
		<TABLE align='center' border='0' cellspacing='0' cellpadding='5'>
		<TR align='center'>
			<TD colspan='3'><INPUT TYPE="text" NAME="interacao_msg" size='60'></TD>
		</TR>

		<TR align='center'>
			<TD colspan='3'><input type="hidden" name="btn_acao" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_interacao.btn_acao.value == '' ) { document.frm_interacao.btn_acao.value='gravar_interacao' ; document.frm_interacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Comentário" border='0' style="cursor:pointer;">
			</TD>
		</TR>
		</TABLE>
		</TD>
	</TR>
	</TABLE>
	</FORM>

	<?
		}
	$sql = "SELECT os_interacao               ,
					to_char(data,'DD/MM/YYYY HH24:MI') as data,
					comentario                ,
					interno                   ,
					tbl_admin.nome_completo
				FROM tbl_os_interacao
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
				WHERE os = $os
				ORDER BY os_interacao DESC;";
	$res = pg_query($con,$sql);
	$cont = 0;
	if(pg_num_rows($res) > 0){

		echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
		echo "<tr>";
		echo "<td class='titulo'><CENTER><b>Nº</b></CENTER></td>";
		echo "<td class='titulo'><CENTER><b>Data</b></CENTER></td>";
		echo "<td class='titulo'><CENTER><b>Mensagem</b></CENTER></td>";
		echo "<td class='titulo'><CENTER><b>Admin</b></CENTER></td>";
		echo "</tr>";
		echo "<tbody>";
		for($i=0;$i<pg_num_rows($res);$i++){
			$os_interacao     = pg_fetch_result($res,$i,os_interacao);
			$interacao_msg    = pg_fetch_result($res,$i,comentario);
			$interacao_interno= pg_fetch_result($res,$i,interno);
			$interacao_data   = pg_fetch_result($res,$i,data);
			$interacao_nome   = pg_fetch_result($res,$i,nome_completo);

			if($interacao_interno == 't'){
				$cor = "style='font-family: Arial; FONT-SIZE: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
			}else{
				$cor = "class='conteudo'";
			}

			$cont++;

			echo "<tr>";
			echo "<td width='25' $cor>"; echo $cont; echo "</td>";
			echo "<td width='90' $cor nowrap>$interacao_data</td>";
			echo "<td $cor>$interacao_msg</td>";
			echo "<td $cor nowrap>$interacao_nome</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</TABLE>";
		echo "<br>&nbsp;";
	}
	echo "</TD></TR></TABLE> <br><br>";
	}
}

if (strlen($status_os_aberta)>0 AND $login_fabrica==3) {
	$status_os_aberta_inter= "";
	if($status_os_aberta == 122) {
		$status_os_aberta_inter = "<br><b style='font-size:11px'>OS com intervenção da fábrica. Aguardando liberacão </b>";
	}
	echo "<br>
		<center>
		<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
			<b style='font-size:14px;color:red;width:100%'>Status OS </b>
			 $status_os_aberta_inter <br>
			<b style='font-size:11px'>$status_os_aberta_obs </b>
		</div>
		</center><br>";
}


if (strlen($os_reincidente) > 0 OR $reincidencia =='t') {

	//verifica se OS faz parte de extrato. HD7622
	$sql = "SELECT tbl_extrato.extrato FROM tbl_os_extra JOIN tbl_extrato using(extrato) WHERE os = $os AND tbl_extrato.aprovado IS NOT NULL ; ";
	$res2 = pg_query ($con,$sql);
	$reic_extrato = @pg_fetch_result($res2,0,0);

//  16/11/2009 HD 171349 - Waldir
// 	if(strlen($reic_extrato) == 0){
//		echo "passou para verificar a reincidencia.";
// 		$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
// 		$res1 = pg_query ($con,$sql);
// 	}
	$sql = "SELECT  tbl_os_status.status_os,tbl_os_status.observacao
		FROM tbl_os_extra JOIN tbl_os_status USING(os)
		WHERE tbl_os_extra.os = $os
		AND tbl_os_status.status_os IN (67,68,70,95,132)";
	//HD: 53642
	if($login_fabrica ==3 and $os > 8082706) $sql .= " ORDER BY tbl_os_status.status_os ";

	$res1 = pg_query ($con,$sql);

	if (pg_num_rows ($res1) > 0) {
		$status_os  = trim(pg_fetch_result($res1,0,status_os));
		$observacao  = trim(pg_fetch_result($res1,0,observacao));
	}

	$sql ="SELECT os_reincidente FROM tbl_os_extra WHERE os=$os";
	$resr=pg_query($con,$sql);
	if(pg_num_rows($resr) >0){
		$xos_reincidente     = trim(pg_fetch_result($resr,0,os_reincidente));
	}
	echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
	echo "<tr>";
	echo "<td align='center'><b><font size='1'>ATENÇÃO</font></b></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center'><font size='1'>";

	if(strlen($xos_reincidente)>0 ){
		$sql = "SELECT  tbl_os.sua_os,
				tbl_os.serie
				FROM    tbl_os
				WHERE   tbl_os.os = $xos_reincidente;";
		$res1 = pg_query ($con,$sql);
		if (pg_num_rows ($res1) > 0) {
			$sos     = trim(pg_fetch_result($res1,0,sua_os));
			$serie_r = trim(pg_fetch_result($res1,0,serie));
		}
		if($login_fabrica==1)$sos=$posto_codigo.$sos;
	}else{
		//CASO NÃO TENHA A REINCIDENCIA NÃO TENHA SIDO APONTADA, PROCURA PELA REINCIDENCIA NA SERIE
		$sql = "SELECT os,sua_os
			FROM tbl_os
			JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE   serie   = '$serie'
			AND     os     <> $os
			AND     fabrica = $login_fabrica
			AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";
			//echo $sql;

		$res2 = pg_query ($con,$sql);

		echo "ORDEM DE SERVIÇO COM NÚMERO DE SÉRIE: <u>$serie_r</u> REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR:<br>";

		if (pg_num_rows ($res2) > 0 ) {
			for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
				$sos_reinc  = trim(pg_fetch_result($res2,$i,sua_os));
				$os_reinc   = trim(pg_fetch_result($res2,$i,os));
				echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
				$mostrou=1;
			}
		}

	}
	if($status_os==67 and $mostrou<>1){

		echo "ORDEM DE SERVIÇO COM NÚMERO DE SÉRIE: <u>$serie</u> REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR:<br>";

		if ($login_fabrica == 11) {
			$sql = "SELECT os_reincidente
					FROM tbl_os_extra
					WHERE os= $os";
			$res2 = pg_query($con,$sql);

			$osrein = pg_fetch_result($res2,0,os_reincidente);

			if (strlen($osrein) > 0) {
				$sql = "SELECT os,sua_os
						FROM tbl_os
						WHERE   serie   = '$serie_r'
						AND     os      = $osrein
						AND     fabrica = $login_fabrica";
				$res2 = pg_query($con,$sql);
				if (pg_num_rows($res2) > 0) {
					$sua_osrein = pg_fetch_result($res2,0,sua_os);
					echo "<a href='os_press.php?os=$osrein' target='_blank'>» $sua_osrein</a>";
				}
			}


		} else {
			$sql = "SELECT os,sua_os
				FROM tbl_os
				JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
				WHERE   serie   = '$serie'
				AND     os     <> $os
				AND     fabrica = $login_fabrica
				AND     tbl_produto.numero_serie_obrigatorio IS TRUE LIMIT 5";

			$res2 = pg_query ($con,$sql);

			if (pg_num_rows ($res2) > 0) {
				for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
					$sos_reinc  = trim(pg_fetch_result($res2,$i,sua_os));
					$os_reinc   = trim(pg_fetch_result($res2,$i,os));
					echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";

				}
			}
			if($login_fabrica ==3 and $os > 8082706) echo "<br>$observacao";
		}
	}elseif($status_os==68){
		echo "ORDEM DE SERVIÇO COM MESMA REVENDA E NOTA FISCAL REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
	}elseif($status_os==70){
		echo "ORDEM DE SERVIÇO COM MESMA REVENDA, NOTA FISCAL E PRODUTO REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
		if($login_fabrica ==3 and $os > 8082706) echo "<br>$observacao";
	}elseif($status_os==95){
		echo "ORDEM DE SERVIÇO COM MESMA NOTA FISCAL E PRODUTO REINCIDENTE. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
	}elseif($status_os==132){
		echo "ORDEM DE SERVIÇO COM MESMA NOTA FISCAL E MESMA DATA DA NF. ORDEM DE SERVIÇO ANTERIOR: <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";
	}else{
		if($mostrou<>1)echo "OS Reincidente:<a href='os_press.php?os=$os_reincidente' target = '_blank'>$sos</a>";

	}
	echo "";
	echo "</font></td>";
	echo "</tr>";
	echo "</table>";
}


if ($consumidor_revenda == 'R')
	$consumidor_revenda = 'REVENDA';
else
	if ($consumidor_revenda == 'C')
		$consumidor_revenda = 'CONSUMIDOR';
?>
<?
 ##############################################
# se é um distribuidor da Britania consultando #
# exibe o posto                                #
 ##############################################
if ((strlen($tipo_atendimento)>0) and $login_fabrica==1) {
?>
<center>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela'>
<?
		if($tipo_os==13){
			echo "<TR>";
			echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;";
			echo "Tipo Atendimento:";
			echo "</TD>";
			echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$nome_atendimento</TD>";
			echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;";
			echo "Solicitante:";
			echo "</TD>";
			echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$quem_abriu_chamado</TD>";
			echo "</TR>";

		}else{
			echo "<TR>";
			echo "<TD class='inicio' height='20' width='150'>&nbsp;&nbsp;";
			echo "Troca de Produto:";
			echo "</TD>";
			echo "<TD class='conteudo' height='20'>&nbsp;&nbsp;$nome_atendimento</TD>";
			echo "</TR>";
		}
?>
</TABLE>
</center>
<?
}
if($login_fabrica ==1 AND strlen($os) > 0){ // HD 17284
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os_troca IS TRUE";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		echo "<TABLE width='700' border='0' cellspacing='0' cellpadding='0' class='Tabela' align='center'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>HISTÓRICO</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>Motivo: $observacao_status</TD>";
			echo "</TR>";
		}
		echo "</TABLE></center>";
	}
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 17284
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os IN (98,99,100, 101)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>Histórico</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$observacao_status</TD>";
			echo "</TR>";
		}
		echo "</TABLE></center>";
	}
}

 if ($excluida == "t") {
	 if (strlen($motivo_exclusao) > 0) $motivo_exclusao = "Motivo: ".$motivo_exclusao;
?>
<CENTER>
<TABLE width="700" border="0" cellspacing="1" cellpadding="0" class='Tabela' >
<TR>
	<TD  bgcolor="#FFE1E1" height='20'>
	<?
	if ($login_fabrica==20 AND $os_recusada =='t'){
		#HD 13940
		echo "OS RECUSADA - ".$status_recusa_observacao;
	}else{
		echo "<h1>ORDEM DE SERVIÇO EXCLUÍDA</h1>";
		echo $motivo_exclusao;
	}
	?>
	</TD>
</TR>
</TABLE>
</CENTER>
<?
}
?>


<?
if (strlen ($auditoria_motivo) > 0) {
	echo "<center><h2><font size='+2'> $auditoria_motivo </font></h2></center>";
}
?>

<?

//HD 211825: Novo status de OS de Troca criado: Autorização para Troca pela Revenda, somente Salton
if ($login_fabrica == 81) {
	$sql = "
	SELECT
	troca_revenda

	FROM
	tbl_os_troca

	WHERE
	os=$os
	";
	$res_troca_revenda = pg_query($con, $sql);

	if (pg_num_rows($res_troca_revenda)) {
		$troca_revenda = pg_result($res_troca_revenda, 0, troca_revenda);
	}
}

if ($troca_revenda == "t") {
	echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
	echo "<TR height='30'>";
	echo "<TD align='left' colspan='3'>";
	echo "<font family='arial' size='2' color='#ffffff'><b>";
	echo "Autorização de Troca pela Revenda";
	echo "</b></font>";
	echo "</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='titulo3'  height='15' >Responsável</TD>";
	echo "<TD class='titulo3'  height='15' >Data</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $troca_admin;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</TD>";
	echo "<TD class='conteudo' height='15' nowrap>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $data_fechamento ;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>";
}
elseif ($ressarcimento == "t") {
	echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
	echo "<TR height='30'>";
	echo "<TD align='left' colspan='3'>";
	echo "<font family='arial' size='2' color='#ffffff'><b>";
	echo "Ressarcimento Financeiro";
	echo "</b></font>";
	echo "</TD>";
	echo "</TR>";

	//4/1/2008 HD 11068
	if($login_fabrica == 45 or $login_fabrica == 11){
		$sql = "SELECT
					observacao,
					descricao
				FROM tbl_os_troca
				LEFT JOIN tbl_causa_troca USING (causa_troca)
				WHERE tbl_os_troca.os = $os";
		$resY = pg_query ($con,$sql);

		if (pg_num_rows ($resY) > 0) {
			$troca_observacao = pg_fetch_result ($resY,0,observacao);
			$troca_causa = pg_fetch_result ($resY,0,descricao);
		}
	}
	echo "<tr>";
	echo "<TD class='titulo3'  height='15' >Responsável</TD>";
	echo "<TD class='titulo3'  height='15' >Data</TD>";
	//4/1/2008 HD 11068
	if($login_fabrica == 45){
		echo "<TD class='titulo3'  height='15' >Observação</TD>";
	}elseif($login_fabrica == 11){
		echo "<TD class='titulo3'  height='15' >Causa</TD>";
	}else{
		echo "<TD class='titulo3'  height='15' >&nbsp;</TD>";
	}
	echo "</tr>";

	// HD 23030
	if($login_fabrica==3){
		if(strlen($data_fechamento) ==0){
			$data_fechamento = $data_conserto;
		}
	}

	echo "<tr>";
	echo "<TD class='conteudo' height='15'>";
	echo "&nbsp;&nbsp;&nbsp;";
	echo $troca_admin;
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";
	echo "<TD class='conteudo' height='15' nowrap>";
	echo "&nbsp;&nbsp;&nbsp;";
	if($login_fabrica ==11) { // HD 56237
		echo $data_ressarcimento;
	}else{
		echo $data_fechamento ;
	}
	echo "&nbsp;&nbsp;&nbsp;";
	echo "</td>";

	//4/1/2008 HD 11068
	if($login_fabrica == 45){
		echo "<TD class='conteudo' height='15' width='80%'>$troca_observacao</td>";
	}elseif($login_fabrica == 11){
		echo "<TD class='conteudo'  height='15' >$troca_causa</TD>";
	}else{
		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
	}
	echo "</tr>";

	if($login_fabrica==11) { // hd 56237
		echo "<tr>";
		echo "<TD class='conteudo' height='15' colspan='100%'>OBS: $troca_observacao</td>";
		echo "</tr>";
	}

	echo "</table>";
}
else {
	if ($troca_garantia == "t") {
		echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela'>";
		echo "<TR height='30'>";
		echo "<TD align='left' colspan='3'>";
		echo "<font family='arial' size='2' color='#ffffff'><b>";
		echo "Produto Trocado";
		echo "</b></font>";
		echo "</TD>";
		echo "</TR>";

		echo "<tr>";
		echo "<TD align='left' class='titulo3'  height='15' >Responsável</TD>";
		echo "<TD align='left' class='titulo3'  height='15' >Data</TD>";
		echo "<TD align='left' class='titulo3'  height='15' >Trocado Por</TD>";
#		echo "<TD class='titulo'  height='15' >&nbsp;</TD>";
		echo "</tr>";
		$sql = "SELECT TO_CHAR(data,'dd/mm/yyyy hh:mi') AS data            ,
						setor                                              ,
						situacao_atendimento                               ,
						tbl_os_troca.observacao                            ,
						tbl_peca.referencia             AS peca_referencia ,
						tbl_peca.descricao              AS peca_descricao  ,
						tbl_causa_troca.descricao       AS causa           ,
						tbl_os_troca.modalidade_transporte                 ,
						tbl_os_troca.envio_consumidor
				FROM tbl_os_troca
				JOIN tbl_peca        USING(peca)
				JOIN tbl_causa_troca USING(causa_troca)
				JOIN tbl_os          ON tbl_os_troca.os = tbl_os.os
				WHERE tbl_os_troca.os = $os
				AND  tbl_os.fabrica = $login_fabrica; ";
		$resX = pg_query ($con,$sql);
		if (pg_num_rows ($resX) > 0) {
			$troca_data           = pg_fetch_result ($resX,0,data);
			$troca_setor          = pg_fetch_result ($resX,0,setor);
			$troca_situacao       = pg_fetch_result ($resX,0,situacao_atendimento);
			$troca_observacao     = pg_fetch_result ($resX,0,observacao);
			$troca_peca_ref       = pg_fetch_result ($resX,0,peca_referencia);
			$troca_peca_des       = pg_fetch_result ($resX,0,peca_descricao);
			$troca_causa          = pg_fetch_result ($resX,0,causa);
			$troca_transporte     = pg_fetch_result ($resX,0,modalidade_transporte);
			$envio_consumidor     = pg_fetch_result ($resX,0,envio_consumidor);

			if($troca_situacao == 0) $troca_situacao = "Garantia";
			else                     $troca_situacao .= "% Faturado";
			if($envio_consumidor=='t') $envio_consumidor = "Envio para o Consumidor";
			else                       $envio_consumidor = "Envio para o Posto Autorizado";

			echo "<tr>";
			echo "<TD class='conteudo' align='left' height='15' nowrap>";
			echo "&nbsp;&nbsp;&nbsp;";
			echo $troca_admin;
			echo "&nbsp;&nbsp;&nbsp;";
			echo "</td>";
			echo "<TD class='conteudo' align='left' height='15' nowrap>";
			echo "&nbsp;&nbsp;&nbsp;";
			echo $troca_data;
			echo "&nbsp;&nbsp;&nbsp;";
			echo "</td>";
			echo "<TD class='conteudo' align='left' height='15' nowrap >";
			echo $troca_peca_ref . " - " . $troca_peca_des;
			echo "</td>";

			echo "<tr>";
			echo "<TD align='left' class='titulo3'  height='15' >Setor</TD>";
			echo "<TD align='left' class='titulo3'  height='15' >Situação do Atendimento</TD>";
			if($login_fabrica==11) {
				echo "<TD align='left' class='titulo3'  height='15' >Causa</TD>";
			}else{
				echo "<TD align='left' class='titulo3'  height='15' >Causa da Troca</TD>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<TD class='conteudo' align='left' height='15' nowrap>";
			echo "&nbsp;&nbsp;&nbsp;";
			echo $troca_setor;
			echo "&nbsp;&nbsp;&nbsp;";
			echo "</td>";
			echo "<TD class='conteudo' align='left' height='15' nowrap>";
			echo "&nbsp;&nbsp;&nbsp;";
			echo $troca_situacao;
			echo "&nbsp;&nbsp;&nbsp;";
			echo "<TD class='conteudo' align='left' height='15' nowrap >";
			echo $troca_causa;
			echo "</td>";
			echo "</tr>";
			if($login_fabrica==3){
				echo "<tr>";
				echo "<TD align='left' class='titulo3'  height='15' >Transporte</TD>";
				echo "<TD align='left' class='titulo3'  height='15' colspan='2'>Situação do Atendimento</TD>";
				echo "</tr>";
				echo "<tr>";
				echo "<TD class='conteudo' align='left' height='15' nowrap>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $troca_transporte;
				echo "</td>";
				echo "<TD class='conteudo' align='left' height='15' nowrap colspan='2'>";
				echo "&nbsp;&nbsp;&nbsp;";
				echo $envio_consumidor;
				echo "</td>";
				echo "</tr>";
			}

			echo "<tr>";
			echo "<TD class='conteudo' align='left' height='15'  colspan='3'><b>OBS:</b>";
			echo $troca_observacao;
			echo "</td>";
			echo "</tr>";


	#		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
			echo "</tr>";

		}else{
			$sql = "SELECT tbl_peca.referencia , tbl_peca.descricao
					FROM tbl_peca
					JOIN tbl_os_item USING (peca)
					JOIN tbl_os_produto USING (os_produto)
					JOIN tbl_os_extra USING (os)
					WHERE tbl_os_produto.os = $os
					AND   tbl_peca.produto_acabado IS TRUE ";
			$resX = pg_query ($con,$sql);
			if (pg_num_rows ($resX) > 0) {
				$troca_por_referencia = pg_fetch_result ($resX,0,referencia);
				$troca_por_descricao  = pg_fetch_result ($resX,0,descricao);
			}


			echo "<tr>";
			echo "<TD class='conteudo' align='left' height='15' nowrap>";
			echo "&nbsp;&nbsp;&nbsp;";
			echo $troca_admin;
			echo "&nbsp;&nbsp;&nbsp;";
			echo "</td>";
			echo "<TD class='conteudo' align='left' height='15' nowrap>";
			echo "&nbsp;&nbsp;&nbsp;";
			echo $data_fechamento;
			echo "&nbsp;&nbsp;&nbsp;";
			echo "</td>";
			echo "<TD class='conteudo' align='left' height='15' nowrap >";
			echo $troca_por_referencia . " - " . $troca_por_descricao;
			echo "</td>";

	#		echo "<TD class='conteudo' height='15' width='80%'>&nbsp;</td>";
			echo "</tr>";
		}
	}
}
?>

<?

// Verifica se o pedido de peça foi cancelado ou autorizado caso a peça esteja bloqueada para garantia

#HD 14830  Fabrica 25
#HD 13618  Fabrica 45


if ($login_fabrica==3 OR $login_fabrica==11 OR $login_fabrica==25 OR $login_fabrica==45 OR $login_fabrica==50 OR $login_fabrica==51){
	$sql_status = "SELECT
				status_os,
				observacao,
				tbl_admin.login,
				to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data,
				tbl_os_status.data as date
				FROM tbl_os_status
				LEFT JOIN tbl_admin USING(admin)
				WHERE os=$os
				AND status_os IN (72,73,62,64,65,87,88,98,99,100,101,102,103,104,116,117)
				ORDER BY date DESC LIMIT 1";

	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado==1){
		$data_status        = trim(pg_fetch_result($res_status,0,data));
		$status_os          = trim(pg_fetch_result($res_status,0,status_os));
		$status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
		$intervencao_admin  = trim(pg_fetch_result($res_status,0,login));

		if (strlen($intervencao_admin)>0 AND $login_fabrica<>50){
			$intervencao_admin = "<br><b>OS em intervenção colocada pela Fábrica ($intervencao_admin)</b>";
		}

		if ($status_os==72 or $status_os==116) {
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>A peça solicitada necessita de autorização. <br>O PA aguarda a fábrica analisar o pedido</b>
					$intervencao_admin
				</div>
				</center><br>
			";
		}
		if ($status_os==73 or $status_os==117) {
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==62){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>OS Sob Intervenção da Assistência Técnica da Fábrica</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==64){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>OS Liberada da Assistência Técnica da Fábrica</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==88){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>$status_observacao</b>
				</div>
				</center><br>
			";
		}
		if ($status_os==87){
			echo "<br>
				<center>
				<div style='font-family:verdana;border:1px solid #D3BE96;width:700px;align:center;background-color:#FCF0D8' align='center'>
					<b style='font-size:14px;color:red;width:100%'>Pedido de peça necessita de autorização</b><br>
					<b style='font-size:11px'>A peça solicitada necessita de autorização. ";
				if ($login_fabrica==1){
					echo "<br>Entrar em contato com o Suporte de sua região.</b>";
				}else{
					echo "<br>Aguarde a fábrica analisar seu pedido.</b>";
				}
			echo "</div>
				</center><br>
			";
			if ($login_fabrica==1){
				echo "<script language='JavaScript'>alert('OS em intervenção. Gentileza, entre em contato com o Suporte de sua região');</script>";
			}
		}
		if($login_fabrica==50){
			# HD 42933 - Alterei para Colormaq, não estava mostrando
			#    a última interação da OSs
			#if ($status_os==98 or $status_os==99 or $status_os==100 or $status_os==101 or $status_os==102 or $status_os==103 or $status_os==104){
				$sql_status = /*"select descricao from tbl_status_os where status_os = $status_os";*/
					"SELECT
						tbl_os_status.status_os,
						tbl_os_status.observacao,
						tbl_admin.login,
						tbl_status_os.descricao,
						to_char(tbl_os_status.data, 'dd/mm/yyyy') AS data
					FROM tbl_os_status
					JOIN tbl_status_os USING (status_os)
					LEFT JOIN tbl_admin USING (admin)
					WHERE os = $os
					ORDER BY tbl_os_status.data DESC LIMIT 1";

				$res_status = pg_query($con, $sql_status );
				if(pg_num_rows($res_status)>0){
					$data_status = pg_fetch_result($res_status, 0, data);;
					$descricao_status = pg_fetch_result($res_status, 0, descricao);
					$intervencao_admin = pg_fetch_result($res_status, 0, login);
					$descricao_status = pg_fetch_result($res_status, 0, descricao);
					$status_observacao = pg_fetch_result($res_status, 0, observacao);


				echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
						echo "<TR>";
							echo "<TD class='inicio' background='imagens_admin/azul.gif' height='19px' colspan='4'>&nbsp;STATUS OS &nbsp;</TD>";
						echo "</TR>";
						echo "<TR>";
							echo "<TD class='inicio' nowrap>&nbsp;DATA &nbsp;</TD>";
							echo "<TD class='inicio' nowrap>&nbsp;ADMIN &nbsp;</TD>";
							echo "<TD class='inicio' nowrap>&nbsp;STATUS &nbsp;</TD>";
							echo "<TD class='inicio' nowrap>&nbsp;MOTIVO &nbsp;</TD>";
						echo "</TR>";
						echo "<TR>";
							echo "<TD class='conteudo' width='10%'>&nbsp; $data_status </TD>";
							echo "<TD class='conteudo'>&nbsp;$intervencao_admin </TD>";
							echo "<TD class='conteudo'>&nbsp;$descricao_status </TD>";
							echo "<TD class='conteudo'>&nbsp;$status_observacao </TD>";
						echo "</TR>";
				echo "</TABLE>";
				}
		#}
		}
	}
}

if(strlen($extrato)>0 AND $login_fabrica==50){ //HD 61132
	echo "<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>";
		echo "<TR>";
			echo "<TD class='inicio'>&nbsp;EXTRATO</TD>";
			echo "<TD class='inicio'>&nbsp;PREVISÃO</TD>";
			echo "<TD class='inicio'>&nbsp;PAGAMENTO</TD>";
		echo "</TR>";
		echo "<TR>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$extrato </TD>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$data_pagamento </TD>";
			echo "<TD class='conteudo' width='33%'>&nbsp;$data_previsao </TD>";
		echo "</TR>";
	echo "</TABLE>";
}


if($login_fabrica ==50 AND strlen($os) > 0){ // HD 37276
	# HD 42933 - Retirei o resultado da tela, deixando apenas um pop-up
	#   mostrando todo o histórico da OS
	/*$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){*/
		echo "<TABLE width='700' border='0' align='center' cellspacing='0' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='1' align='center'>"; #HISTÓRICO</TD>";
		?>

		<!--<td class="inicio" colspan="1" align="left">--><a style='cursor:pointer;' onclick="javascript:window.open('historico_os.php?os=<? echo $os ?>','mywindow','menubar=1,resizable=yes,scrollbars=yes,width=500,height=350')">&nbsp;VER HISTÓRICO DA OS<!--Ver todo o Histórico--></a></td>

		<?
		echo "</TR>";
		/*for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$observacao_status</TD>";
			echo "</TR>";
		}*/
		echo "</TABLE></center>";
	//}
}

///////////////////////////////////////////// OS RETORNO  - FABIO 10/01/2007  - INICIO /////////////////////////////////////////////////////////////
// informações de postagem para envio do produto para BRITANIA
// ADICIONADO POR FABIO 03/01/2007
if ($login_fabrica==3  OR $login_fabrica==11){
	$sql = "SELECT  nota_fiscal_envio,
				TO_CHAR(data_nf_envio,'DD/MM/YYYY')  AS data_nf_envio,
				numero_rastreamento_envio,
				TO_CHAR(envio_chegada,'DD/MM/YYYY')  AS envio_chegada,
				nota_fiscal_retorno,
				TO_CHAR(data_nf_retorno,'DD/MM/YYYY')  AS data_nf_retorno,
				numero_rastreamento_retorno,
				TO_CHAR(retorno_chegada,'DD/MM/YYYY')  AS retorno_chegada
			FROM tbl_os_retorno
			WHERE   os = $os;";
	$res = pg_query ($con,$sql);
	if (@pg_num_rows($res)==1){
		$retorno=1;
		$nota_fiscal_envio			= trim(pg_fetch_result($res,0,nota_fiscal_envio));
		$data_nf_envio				= trim(pg_fetch_result($res,0,data_nf_envio));
		$numero_rastreamento_envio	= trim(pg_fetch_result($res,0,numero_rastreamento_envio));
		$envio_chegada				= trim(pg_fetch_result($res,0,envio_chegada));
		$nota_fiscal_retorno		= trim(pg_fetch_result($res,0,nota_fiscal_retorno));
		$data_nf_retorno			= trim(pg_fetch_result($res,0,data_nf_retorno));
		$numero_rastreamento_retorno= trim(pg_fetch_result($res,0,numero_rastreamento_retorno));
		$retorno_chegada			= trim(pg_fetch_result($res,0,retorno_chegada));
	} else $retorno=0;

	if ($retorno==1 AND strlen($nota_fiscal_envio)==0){
		$sql_status = "SELECT status_os
					FROM tbl_os_status
					WHERE os=$os
					ORDER BY data DESC LIMIT 1";
		$res_status = pg_query($con,$sql_status);
		$resultado = pg_num_rows($res_status);
		if ($resultado==1){
			$status_os  = trim(pg_fetch_result($res_status,0,status_os));
			if ($status_os==65){
				echo "<br>
					<center>
					<b style='font-size:15px;color:#990033;padding:2px 5px'>O reparo deste produto deve ser efetuado pela assistência técnica da fábrica</b></center>";
			}
			else{
				echo "<br>
					<center>
					<b style='font-size:15px;background-color:#596D9B;color:white;padding:2px 5px'>O reparo deste produto foi feito pela Fábrica</b></center>";
			}
		}
	}

	if ( $retorno==1 AND $nota_fiscal_envio AND $data_nf_envio AND $numero_rastreamento_envio) {
		if (strlen($envio_chegada)==0){
			echo "<BR><b style='font-size:14px;color:#990033'>O Produto foi enviado a fábrica mas a fábrica ainda não confirmou seu recebimento.<br></b><BR>";
		}else {
			if (strlen($data_nf_retorno)==0){
				echo "<BR><b style='font-size:14px;color:#990033'>O Produto foi recebido pela fábrica em $envio_chegada<br> Aguarde a fábrica efetuar o reparo e enviar ao seu posto.</b><BR>";
			}
			else{
				if (strlen($retorno_chegada)==0){
					echo "<BR><b style='font-size:14px;color:#990033'>O reparo do produto foi feito pela fábrica e foi enviado ao seu posto em $data_nf_retorno</b><BR>";
				}
				else {
					echo "<BR><b style='font-size:14px;color:#990033'>O REPARO DO PRODUTO FOI FEITO PELA FÁBRICA.</b><BR>";
				}
			}
		}
	}
	if ( $retorno==1){
	?>
	<br>
	<TABLE width='430px' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
			<TR>
				<TD class="inicio" background='imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;ENVIO DO PRODUTO À FÁBRICA</TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'>INFORMAÇÕES DO ENVIO DO PRODUTO À FÁBRICA</TD>
			</TR>
			<TR>
				<TD class="titulo3" width='260px' >NÚMERO DA NOTA FISCAL DE ENVIO &nbsp;</TD>
				<TD class="conteudo" width='170px'>&nbsp;<? echo $nota_fiscal_envio ?></TD>
			</TR>
			<TR>
				<TD class="titulo3">DATA DA NOTA FISCAL DO ENVIO &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $data_nf_envio ?></TD>
			</TR>
			<TR>
				<TD class="titulo3">NÚMERO O OBJETO / PAC &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_envio"."BR' target='_blank'>$numero_rastreamento_envio</a>" ?></TD>
			</TR>
			<TR>
				<TD class="titulo3">DATA DA CHEGADA À FÁBRICA &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $envio_chegada; ?></TD>
			</TR>
			<TR>
				<TD class="inicio" background='imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;RETORNO DO PRODUTO DA FÁBRICA AO POSTO</TD>
			</TR>
			<TR>
				<TD class="subtitulo" height='19px' colspan='2'>INFORMAÇÕES DO RETORNO DO PRODUTO AO POSTO</TD>
			</TR>
			<TR>
				<TD class="titulo3">NÚMERO DA NOTA FISCAL DO RETORNO &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $nota_fiscal_retorno ?></TD>
			</TR>
			<TR>
				<TD class="titulo3">DATA DO RETORNO &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $data_nf_retorno ?></TD>
			</TR>
			<TR>
				<TD class="titulo3">NÚMERO O OBJETO / PAC DE RETORNO &nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo ($numero_rastreamento_retorno)?"<a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_retorno"."BR' target='_blank'>$numero_rastreamento_retorno</a>":""; ?></TD>
			</TR>
			<TR>
				<TD class="titulo3" >DATA DA CHEGADA AO POSTO&nbsp;</TD>
				<TD class="conteudo" >&nbsp;<? echo $retorno_chegada ?></TD>
			</TR>
		</TABLE>
	<br><br>
	<?
	}
}

// Mostra número do Extrato que esta OS's está - A pedido da Edina
// Fabio
// 27/12/2006
if ($login_fabrica==2){
	if (strlen(trim($data_finalizada))>0){
		$query = "SELECT extrato,
					to_char(data_pagamento,'DD/MM/YYYY')  AS data_pagamento,
						data_vencimento
				FROM tbl_os
				JOIN tbl_os_extra using(os)
				JOIN tbl_extrato using(extrato)
				LEFT JOIN tbl_extrato_pagamento using(extrato)
				WHERE tbl_os.os = $os
				AND tbl_os.fabrica = 2;";
		$result = pg_query ($con,$query);
		if (pg_num_rows ($result) > 0) {
			$extrato = pg_fetch_result ($result,0,extrato);
			$data_pg = pg_fetch_result ($result,0,data_pagamento);
			$data_vcto = pg_fetch_result ($result,0,data_vencimento);
			?><!--
			<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
					<TR  style='font-size:12px;background-color:#ced7e7'>
						<TD>
							<b  style='padding:0px 10px;font-weight:normal'>	ESTA OS ESTÁ NO EXTRATO:</b>
							<a href='http://www.telecontrol.com.br/assist/admin/extrato_consulta_os.php?extrato=<? echo $extrato; ?>' style='font-weight:bold;color:black;'><? echo $extrato; ?></a>
							<b  style='padding:0px 15px;font-weight:normal'> DATA DO PAGAMENTO:</b>
							<b><? echo $data_pg; ?></b>
						</TD>
					</TR>

			</TABLE><br>

			<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
					<TR  >
						<TD class='inicio' style='text-align:center;'>
							ESTA OS ESTÁ PAGA
						</td>
						<TD class='titulo' style='padding:0px 15px;'>
							EXTRATO
						</td>
						<td	class='conteudo' style='padding:0px 15px;'>
							<a href='http://www.telecontrol.com.br/assist/admin/extrato_consulta_os.php?extrato=<? echo $extrato; ?>' style='font-weight:bold;color:black;'><? echo $extrato; ?></a>
						</td>
						<td class='titulo' style='padding:0px 15px;'>
							DATA DO PAGAMENTO
						</td>
						<td class='conteudo' style='padding:0px 15px;'>
							<b><? echo $data_pg; ?></b>
						</TD>
					</TR>

			</TABLE><br>-->
			<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
					<TR >
						<TD class='inicio' style='text-align:center;'  colspan='4'>
							EXTRATO
						</td>
					</tr>
					<tr>
						<TD class='titulo' style='padding:0px 5px;' width='120' >
							Nº EXTRATO
						</td>
						<td	class='conteudo' style='padding:0px 5px;' width='226' >
							<a href='admin/extrato_consulta_os.php?extrato=<? echo $extrato; ?>' ><? echo $extrato; ?></a>
						</td>
						<td class='titulo' style='padding:0px 5px;' width='120' >
							DATA DO PAGAMENTO
						</td>
						<td class='conteudo' style='padding:0px 5px;' width='226' >
							&nbsp;<b><? echo $data_pg; ?></b>
						</TD>
					</TR>

			</TABLE><br>

			<?

		}


	}

}// fim mostra número do Extrato
if($login_fabrica ==14 AND strlen($os) > 0){ // HD 65661
	$sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
				  tbl_status_os.descricao,
				  tbl_os_status.observacao,
				  tbl_os_status.status_os
			FROM tbl_os_status
			JOIN tbl_os using(os)
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND   tbl_os.os_reincidente IS TRUE
			AND   tbl_os_status.extrato IS NULL
			AND status_os IN (13,19)
			ORDER BY os_status desc
			limit 1";
	//if($ip=='201.76.86.85') echo $sql2;
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>Histórico</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$status_os       = pg_fetch_result($res2,$i,status_os);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status";
			if($status_os == 13) {
				echo "- Motivo: $observacao_status";
			}
			if($status_os == 19) {
				echo " da reincidência";
			}
			echo "</TD>";
			echo "</tr>";
		}
		echo "</TABLE></center>";
	}
}

if($login_fabrica ==30 AND strlen($os) > 0){ // HD 65661
	$sql2="SELECT to_char(tbl_os_status.data,'DD/MM/YYYY') as data,
				  tbl_admin.login
			FROM tbl_os_status
			JOIN tbl_os using(os)
			JOIN tbl_admin ON tbl_os_status.admin = tbl_admin.admin
			WHERE os=$os
			AND   tbl_os.os_reincidente IS TRUE
			AND   tbl_os_status.extrato IS NULL
			AND status_os IN (132,19)
			AND status_os_ultimo = 19
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		$data        = pg_fetch_result($res2,0,'data');
		$login       = pg_fetch_result($res2,0,'login');

		echo "<TABLE width='700' border='0' align='center' cellspacing='1' cellpadding='0' class='Tabela'>";
		echo "<TR>";
		echo "<TD class='inicio'>Admin(APROVOU REINCIDÊNCIA)</TD>";
		echo "<TD class='inicio'>Data</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='conteudo'>$login</TD>";
		echo "<TD class='conteudo'>$data</TD>";
		echo "</tr>";
		echo "</TABLE></center>";
	}
}


?>

<TABLE width="700" border="0" cellspacing="1" align='center' cellpadding="0" class='Tabela' >
		<TR>
			<TD class="inicio">&nbsp;&nbsp;POSTO</TD>
			<?
				if (strlen(trim($admin)) > 0 )
				echo "<TD class=\"inicio\" width=\"50%\">OS ADMIN: $admin</TD>";
			?>
		</TR>
		<TR>
			<TD class="conteudo" <?php
				if ((strlen(trim($admin)) > 0) or ($login_fabrica == 6)){
					echo "colspan = 2";
			}
			?>>
			<? echo "&nbsp; $posto_codigo - $posto_nome"; ?></TD>
		</TR>
		<? if ($login_fabrica == 6) {?>
		<TR>
			<TD class="conteudo">
			<? echo "&nbsp; $posto_endereco, $posto_num $posto_complemento - $posto_cidade/$posto_estado - CEP $posto_cep";?></TD>
			<TD class="conteudo"><?php echo "&nbsp;$posto_fone"; ?></td>
		</TR>
		<? }?>
</TABLE>
<?
// }
?>

<? if($login_fabrica ==35 AND strlen($os) > 0){ // HD 56418
	$sql2="SELECT to_char(data,'DD/MM/YYYY') as data,
				  descricao,
				  observacao
			FROM tbl_os_status
			JOIN tbl_status_os using(status_os)
			WHERE os=$os
			AND status_os IN (13,19,127)
			ORDER BY os_status desc
			limit 1";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		echo "<TABLE width='700' border='0' cellspacing='1' align='center' cellpadding='0' class='Tabela' >";
		echo "<TR>";
		echo "<TD class='inicio' colspan='2' align='center'>HISTÓRICO</TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {
			$data             = pg_fetch_result($res2,$i,data);
			$descricao_status = pg_fetch_result($res2,$i,descricao);
			$observacao_status = pg_fetch_result($res2,$i,observacao);
			echo "<TR>";
			echo "<TD class='conteudo2' colspan='2' align='center'>$data - $descricao_status</TD>";
			echo "</tr>";
		}
		echo "</TABLE>";
	}
}

if($login_fabrica ==50 AND strlen($os) > 0){ // HD 79844
	$sql2="SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
			FROM tbl_os
			JOIN tbl_numero_serie USING (serie)
			WHERE os=$os ";
	$res2=pg_query($con,$sql2);
	if(pg_num_rows($res2) > 0){
		$data_fabricacao = pg_fetch_result($res2,0,data_fabricacao);
	}
}
?>

<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr >
		<td rowspan='4' class='conteudo' width='300' ><center>OS FABRICANTE<br><br>&nbsp;<b>
			<?
			echo "<FONT SIZE='6' COLOR='#C67700'>";
			if ($login_fabrica == 1) echo $posto_codigo;
			if (strlen($consumidor_revenda) > 0) echo $sua_os ."</FONT> - ". $consumidor_revenda;
			else echo $sua_os;
			?>
			<?
			if($login_fabrica==3){ echo "<BR><font color='#D81005' SIZE='4' ><strong>$marca_nome</strong></font>";}

			if(strlen($sua_os_offline)>0){
			echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
			echo "<tr >";
			echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>OS Off Line - $sua_os_offline</center></td>";
			echo "</tr>";
			echo "</table>";
			}
			?>
			</b></center>
		</td>
		<td class='inicio' height='15' colspan='4'>&nbsp;DATAS DA OS</td>
	</TR>
	<TR>
		<td class='titulo'width='100' height='15'>ABERTURA&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<?echo $data_abertura?></td>
		<td class='titulo' width='100' height='15'>DIGITAÇÃO&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_digitacao ?></td>
	</tr>
	<tr>
		<td class='titulo' width='100' height='15'>FECHAMENTO&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_fechamento ?></td>
		<td class='titulo' width='100' height='15'>FINALIZADA&nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;<? echo $data_finalizada ?></td>

	</tr>
	<tr>
		<TD class="titulo"  height='15'>DATA DA NF&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf ?></TD>
		<td class='titulo' width='100' height='15'>FECHADO EM &nbsp;</td>
		<td class='conteudo' width='100' height='15'>&nbsp;
		<?
		if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){

			$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
			$resD = pg_query ($con,$sql_data);
			if (pg_num_rows ($resD) > 0) {
				$total_de_dias_do_conserto = pg_fetch_result ($resD,0,'final');
			}

			if($total_de_dias_do_conserto==0) echo 'no mesmo dia' ;
			else echo $total_de_dias_do_conserto;
			if($total_de_dias_do_conserto==1) echo ' dia' ;
			if($total_de_dias_do_conserto>1)  echo ' dias' ;
			if($login_fabrica == 1){
				$sql_extrato = "SELECT to_char(tbl_extrato_financeiro.data_envio,'DD/MM/YYYY') AS data_envio
								FROM tbl_os_extra
								LEFT JOIN tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
								WHERE tbl_os_extra.os = $os LIMIT 1";
				$res_extrato = pg_query($con,$sql_extrato);
				if(pg_num_rows($res_extrato)>0){
					$data_envio = pg_fetch_result ($res_extrato,0,data_envio);
					echo " ";
					echo "<acronym title='Data de envio para o Financeiro'>$data_envio</acronym>" ;
				}
			}
		}else{
			echo "NÃO FINALIZADO";
		}
		?>
		</td>
	</tr>
	<?if (usaDataConserto($login_posto, $login_fabrica)) { ?>
		<tr>
		<td class='titulo' width='100' height='15'>
		<td class='titulo' width='100' height='15'>CONSERTADO &nbsp;</td>
		<td class='conteudo' width='100' height='15' colspan ='1' >&nbsp;
		<?
				$sql_data_conserto = "SELECT to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' )	as data_conserto FROM tbl_os WHERE os=$os";
				$resdc = pg_query ($con,$sql_data_conserto);
				if (pg_num_rows ($resdc) > 0) {
					$data_conserto= pg_fetch_result ($resdc,0,data_conserto);
				}
				if(strlen($data_conserto)>0){
					echo $data_conserto;
				}else{
					echo "&nbsp;";
				}
			echo "</td>";
			echo "<td class='titulo' width='100'height='15'>&nbsp;</td>";
			echo "<td class='conteudo' width='100' height='15'> </tr>";
		?>
		<? } ?>
	<?
	if(strlen($motivo_atraso)>0){
	?>
		<tr><td colspan='5' bgcolor='#FF0000' size='2'><b><font color='#FFFF00'>Motivo do atraso: <?=$motivo_atraso?></font></b></td></tr>
	<?
	}
	if(strlen($obs_reincidencia)>0){
	?>
		<tr><td colspan='5' bgcolor='#FF0000' size='2'><b><font color='#FFFF00'>Justifica: <?=$obs_reincidencia?></font></b></td></tr>
	<?}?>
</table>
<?
// CAMPOS ADICIONAIS SOMENTE PARA LORENZETTI
if($login_fabrica==19){
	if(strlen($tipo_os)>0){
		$sqll = "SELECT descricao from tbl_tipo_os where tipo_os=$tipo_os";
		$ress = pg_query($con,$sqll);
		$tipo_os_descricao = pg_fetch_result($ress,0,0);
	}
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
	<TD class="titulo"  height='15' width='90'>ATENDIMENTO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tipo_atendimento.' - '.$nome_atendimento ?></TD>
	 <TD class="titulo"  height='15' width='90'>MOTIVO&nbsp;</TD>
    <TD class="conteudo" height='15'>&nbsp;<? echo $tipo_os_descricao; ?></TD>
	<?if( $tecnico_nome){?>
	<TD class="titulo" height='15'width='90'>NOME DO TÉCNICO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
	<?}?>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA LORENZETTI

// CAMPOS ADICIONAIS SOMENTE PARA BOSCH
if($login_fabrica==20){
	if($tipo_atendimento==13 AND $tipo_troca==1){
		$tipo_atendimento = 00;
		$nome_atendimento = "Troca em Cortesia Comercial";
	}
?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
<TR>
	<TD class="titulo"  height='15' width='90'>ATENDIMENTO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tipo_atendimento.' - '.$nome_atendimento ?></TD>
	<?if( $tecnico_nome){?>
	<TD class="titulo" height='15'width='90'>NOME DO TÉCNICO&nbsp;</TD>
	<TD class="conteudo" height='15'>&nbsp;<? echo $tecnico_nome ?></TD>
	<?}?>
	<?if($tipo_atendimento=='15' or $tipo_atendimento=='16'){?>
			<TD class="titulo"  height='15' width='90'>AUTORIZAÇÃO&nbsp;</TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $autorizacao_cortesia ?></TD>
			<TD class="titulo"  height='15' width='90'>PROMOTOR&nbsp;</TD>
			<TD class="conteudo" height='15'>&nbsp;<? echo $promotor_treinamento ?></TD>
	<?}?>
</TR>
</TABLE>
<?
}//FIM DA PARTE EXCLUSIVA DA BOSCH
?>
<?
if(strlen($troca_garantia_admin)>0){
	$sql = "SELECT login,nome_completo
			FROM tbl_admin
			WHERE admin = $troca_garantia_admin";
	$res2 = pg_query ($con,$sql);

	if (pg_num_rows($res2) > 0) {
		$login                = pg_fetch_result ($res2,0,login);
		$nome_completo        = pg_fetch_result ($res2,0,nome_completo);

?>
		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<TR>
				<TD class="titulo"  height='15' width='90'>Usuários&nbsp;</TD>
				<TD class="conteudo" height='15'>&nbsp;<? if($nome_completo )echo $nome_completo; else echo $login;  ?></TD>
				<TD class="titulo" height='15'width='90'>Data</TD>
				<TD class="conteudo" height='15'>&nbsp;
				<? echo $troca_garantia_data ?></TD>
			</TR>
			<TR>
				<TD class="conteudo"  height='15'colspan='4'>
				<?
				if($troca_garantia=='t')
					echo '<b><center>Troca Direta</center></b>';
				else
					echo '<b><center>Troca Via Distribuidor</center></b>';
				?>
				</TD>
			</TR>
		</TABLE>
<?
	}
}
if ($login_fabrica == 15){
if($serie[0]=="9"){

	$sqlx = "select os from tbl_os where serie_reoperado = '$serie' AND posto = $posto AND fabrica = $login_fabrica";
	$xres = pg_query ($con,$sqlx);

	if(pg_num_rows($xres)>0){
		$xos = trim(pg_fetch_result($xres,0,$xos));
	}
	$serie = "<A HREF='os_press.php?os=$xos' target='_blank'>$serie</A>";
}
}

	if($login_fabrica ==59 AND strlen($os) > 0){ // HD 79844
		$sql2="SELECT versao
				FROM tbl_os
				WHERE os=$os ";
		$res2=pg_query($con,$sql2);
		if(pg_num_rows($res2) > 0){
			$versao = pg_fetch_result($res2,0,versao);
		}
	}

	if($login_fabrica == 1 and $tipo_atendimento ==18) {
		$sql = " SELECT total_troca
				FROM tbl_os_troca
				WHERE os = $os";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$total_troca = pg_fetch_result($res,0,total_troca);
		}
	}
?>
<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr>
		<td class='inicio' height='15' colspan='4'>&nbsp;INFORMAÇÕES DO PRODUTO&nbsp;</td>
	</tr>
	<tr >
		<TD class="titulo" height='15' width='90'>REFERÊNCIA&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?></TD>
		<TD class="titulo" height='15' width='90'>DESCRIÇÃO&nbsp;</TD>
		<TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
		<TD class="titulo" height='15' width='90'>
			<?
			if($login_fabrica==35){
				echo "PO#";
			}else{
				echo "NÚMERO DE SÉRIE";
			}?>
			&nbsp;
		</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $serie ?></TD>

	<? if($login_fabrica==14 AND strlen($numero_controle)>0){?>
		<TD class="titulo" height='15' width='100'>NÚMERO DE CONTROLE</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $numero_controle;  ?></TD>
	<? } ?>
	<? if($login_fabrica==50 AND strlen($data_fabricacao)>0){?>
		<TD class="titulo" height='15' width='100'>DATA FABRICAÇÃO</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $data_fabricacao;  ?></TD>
	<? }
		if ($login_fabrica == 11) {?>
        <TD class="titulo" height='15' width='90'>RG DO PRODUTO</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $rg_produto ?>&nbsp;</TD>
		<? }?>
	<?if($login_fabrica==59){?>
        <TD class="titulo" height='15' width='90'>VERSÃO</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $versao ?>&nbsp;</TD>
	<?}?>
	</tr>
	<? if ($login_fabrica == 15 and strlen($serie_reoperado)>0) { ?>
	<TR>
		<TD class="conteudo" height='15' colspan="4">&nbsp;</TD>
		<TD class="titulo" height='15' width='95'>SÉRIE REOPERADO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $serie_reoperado ?></TD>
	</tr>
	<?
	}
	if ($login_fabrica == 1) { ?>
	<tr>
		<TD class="titulo" height='15' width='90'>VOLTAGEM&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $produto_voltagem ?></TD>
		<TD class="titulo" height='15' width='110'>CÓDIGO FABRICAÇÃO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $codigo_fabricacao ?></TD>
		<?if($tipo_atendimento == 18 and strlen($total_troca) > 0) { ?>
		<TD class="titulo" height='15' width='110' style='font-weight:bold;' nowrap>VALOR DA TROCA FATURADA&nbsp;</TD>
		<TD class="conteudo" height='15' style='font-weight:bold; color:red'>R$&nbsp;<? echo number_format($total_troca,2,",","."); ?></TD>
		<? } ?>
	</tr>
	<? } ?>
</table>
<? if (strlen($aparencia_produto) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
	<td class='titulo' height='15' width='300'>APARENCIA GERAL DO APARELHO/PRODUTO</td>
	<td class="conteudo">&nbsp;<? echo $aparencia_produto ?>
	</td>
</TR>
</TABLE>
<? } ?>
<? if (strlen($acessorios) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
<TR>
	<TD class='titulo' height='15' width='300'>ACESSÓRIOS DEIXADOS JUNTO COM O APARELHO</TD>
	<TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
</TR>
</TABLE>
<? } ?>
<? if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
	<TR>
		<TD class='titulo' height='15'width='300'>&nbsp;INFORMAÇÕES SOBRE O DEFEITO</TD>
		<TD class="conteudo" >&nbsp;
			<?
			if (strlen($defeito_reclamado) > 0) {
				$sql = "SELECT tbl_defeito_reclamado.descricao
						FROM   tbl_defeito_reclamado
						WHERE  tbl_defeito_reclamado.descricao = '$defeito_reclamado'";
						//WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";

				$res = pg_query ($con,$sql);

				if (pg_num_rows($res) > 0) {
					$descricao_defeito = trim(pg_fetch_result($res,0,descricao));

					//HD 172561 - Cliente solicitou para mostrar o defeito_reclamado_descricao em um campo e o
					//tbl_defeito_reclamado.descricao em outro
					if ($login_fabrica == 3) {
						echo $defeito_reclamado_descricao;
					}
					else {
						echo $descricao_defeito ." - ".$defeito_reclamado_descricao;
					}
				}
			}
			?>
		</TD>
	</TR>
</TABLE>
<? } ?>
<? if ($login_fabrica == 19 and (strlen($fabricacao_produto) > 0 or strlen($qtde_km) > 0)) {  // HD 64152?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
	<TR>
		<TD class='titulo' height='15'width='300'>Mês e Ano de Fabricação do Produto&nbsp;</TD>
		<TD class="conteudo" >&nbsp;<?echo $fabricacao_produto;?>
		</TD>
		<TD class='titulo' height='15'width='100'>Quilometragem &nbsp;</TD>
		<TD class="conteudo" >&nbsp;<?echo $qtde_km;?>
		</TD>
	</TR>
</TABLE>
<? } ?>
<? if($login_fabrica==6 or $login_fabrica == 30) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
	<td class='titulo' height='15' width='300'><?
			if ($login_fabrica == 6) echo "OS Posto";
			else                     echo "OS Revendedor";?></td>
	<td class="conteudo">&nbsp;<? echo $os_posto ?>
	</td>
	<?if($login_fabrica == 30){?>
		<TD class="titulo" height='15' width='100' align='right'>Técnico</TD>
		<TD class="conteudo" >&nbsp;<? echo $tecnico_nome ?>&nbsp;</TD>
	<?}?>
</TR>
</TABLE>
<?}?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<TR>
		<TD  height='15' class='inicio' colspan='4'>&nbsp;DEFEITOS</TD>
	</TR>
	<TR>
		<TD class="titulo" height='15' width='90'>RECLAMADO</TD>
		<TD class="conteudo" height='15' width='150' <?if ($login_fabrica == 30 or $login_fabrica == 43) echo "colspan=4"?>> &nbsp;<?
			// HD 22820
			if($login_fabrica==1){
				if($troca_garantia=='t' or $troca_faturada=='t')	echo $descricao_defeito ;
				else echo $descricao_defeito ; if($defeito_reclamado_descricao)echo " - ".$defeito_reclamado_descricao;
			}elseif($login_fabrica == 19){ // hd 64152
				$sql = "SELECT DISTINCT tbl_defeito_reclamado.codigo,tbl_defeito_reclamado.descricao
					FROM tbl_os_defeito_reclamado_constatado
					JOIN tbl_defeito_reclamado USING(defeito_reclamado)
					WHERE os=$os";
				$res = pg_query ($con,$sql);

				$array_integridade_reclamado = array();

				if(@pg_num_rows($res)>0){
					for ($i=0;$i<pg_num_rows($res);$i++){
						$aux_defeito_reclamado = pg_fetch_result($res,$i,1);
						array_push($array_integridade_reclamado,$aux_defeito_reclamado);
					}
				}
				$lista_defeitos_reclamados = implode($array_integridade_reclamado,", ");
				echo "$lista_defeitos_reclamados";

			}else{
				echo $descricao_defeito;

				if($defeito_reclamado_descricao) {
					//HD 172561 - Cliente solicitou para mostrar o defeito_reclamado_descricao em um campo e o
					//tbl_defeito_reclamado.descricao em outro
					if ($login_fabrica != 3) {
						echo " - ".$defeito_reclamado_descricao;
					}
				}
			}

			?></TD>

			<?if ($login_fabrica != 30 and $login_fabrica != 43) { ?>
		<TD class="titulo" height='15' width='90'><? if($login_fabrica==20)echo "REPARO";else echo "CONSTATADO";?> &nbsp;</td>
		<td class="conteudo" height='15'>&nbsp;
			<?
			//HD 17683 - VÁRIOS DEFEITOS CONSTATADOS
			if($login_fabrica==30 or $login_fabrica == 19 or $login_fabrica == 43){

				$sql = "SELECT DISTINCT tbl_defeito_constatado.codigo,tbl_defeito_constatado.descricao
					FROM tbl_os_defeito_reclamado_constatado
					JOIN tbl_defeito_constatado USING(defeito_constatado)
					WHERE os=$os";
				$res = pg_query ($con,$sql);

				$array_integridade = array();

				if(@pg_num_rows($res)>0){
					for ($i=0;$i<pg_num_rows($res);$i++){
						$aux_defeito_constatado = pg_fetch_result($res,$i,0).'-'.pg_fetch_result($res,$i,1);
						array_push($array_integridade,$aux_defeito_constatado);
					}
				}
				$lista_defeitos = implode($array_integridade,", ");
				echo "$lista_defeitos";
			}else{
				// HD 22820
				if( $login_fabrica==1){
					if($troca_garantia=='t' or $troca_faturada=='t'){
						echo $defeito_reclamado_descricao;
					}else{
						echo $defeito_constatado;
					}
				}else{
					if($login_fabrica==20)echo $defeito_constatado_codigo.' - ';
					echo $defeito_constatado;
				}
			}
			?>
		</TD>
	</TR>

	<TR>
		<TD class="titulo" height='15' width='90'>
		<?
		if($login_fabrica==6 or $login_fabrica==24 or $login_fabrica==43 or $login_fabrica==15 or $login_fabrica==3 OR $login_fabrica==50 or $login_fabrica == 40)      echo "SOLUÇÃO";
		elseif($login_fabrica==20) echo "DEFEITO";
		else                       echo "CAUSA"  ;
		?>
		&nbsp;</td>
		<td class="conteudo" colspan='3' height='15'>&nbsp;
		<?
		if(($login_fabrica==24 or $login_fabrica == 43 or $login_fabrica == 40)and strlen($solucao_os)>0) {//takashi 30-11
			$sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
			$xres = pg_query($con, $sql);
			if(pg_num_rows($xres)>0){
				$xsolucao = trim(pg_fetch_result($xres,0,descricao));
				echo "$xsolucao";
			}else{
				$sql = "select descricao from tbl_servico_realizado where servico_realizado = $solucao_os and fabrica = $login_fabrica limit 1;";
				$xres = pg_query($con, $sql);
				$xsolucao = trim(pg_fetch_result($xres,0,descricao));
				echo "$xsolucao";
			}
		}

	//HD 53480 Adicionado fabrica = 3
	if($login_fabrica==6 OR $login_fabrica==11 OR $login_fabrica==15 OR $login_fabrica==3 OR $login_fabrica==50){
		if (strlen($solucao_os)>0){
			//chamado 1451 - não estava validando a data...
			$sql_data = "SELECT SUM(validada - '2006-11-05')as total_dias FROM tbl_os WHERE os=$os";
			$resD = pg_query ($con,$sql_data);
			if (pg_num_rows ($resD) > 0) {
				$total_dias = pg_fetch_result ($resD,0,total_dias);
			}
			if ( ($total_dias > 0 AND $login_fabrica==6) OR ($login_fabrica==11)  OR $login_fabrica==15 OR $login_fabrica==3 OR $login_fabrica==50){
				$sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
				$xres = pg_query($con, $sql);
				if (pg_num_rows($xres)>0){
					$xsolucao = trim(pg_fetch_result($xres,0,descricao));
					echo "$xsolucao";
				}

			}else{
				$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
				$xres = pg_query($con, $xsql);
				if (pg_num_rows($xres)>0){
					$xsolucao = trim(pg_fetch_result($xres,0,descricao));
					echo "$xsolucao";
				}else{
					$sql="select descricao from tbl_solucao where solucao=$solucao_os and 	fabrica=$login_fabrica limit 1";
					$xres = pg_query($con, $sql);
					$xsolucao = trim(pg_fetch_result($xres,0,descricao));
					echo "$xsolucao";
				}
			}
		}
		}else{
			if($login_fabrica==20)echo $causa_defeito_codigo.' - ' ;
			echo $causa_defeito;
			}
 		?>
		</TD>
		<?}?>
	</TR>
	<?if ($login_fabrica == 43 or $login_fabrica == 30) {

			$sql_cons = "SELECT
						tbl_defeito_constatado.defeito_constatado,
						tbl_defeito_constatado.descricao         ,
						tbl_defeito_constatado.codigo,
						tbl_solucao.solucao,
						tbl_solucao.descricao as solucao_descricao
				FROM tbl_os_defeito_reclamado_constatado
				JOIN tbl_defeito_constatado USING(defeito_constatado)
				LEFT JOIN tbl_solucao USING(solucao)
				WHERE os = $os";

			$res_dc = pg_query($con, $sql_cons);
			if(pg_num_rows($res_dc) > 0){
				for($x=0;$x<pg_num_rows($res_dc);$x++){
					$dc_defeito_constatado = pg_fetch_result($res_dc,$x,defeito_constatado);
					$dc_solucao = pg_fetch_result($res_dc,$x,solucao);

					$dc_descricao = pg_fetch_result($res_dc,$x,descricao);
					$dc_codigo    = pg_fetch_result($res_dc,$x,codigo);
					$dc_solucao_descricao = pg_fetch_result($res_dc,$x,solucao_descricao);

					echo "<tr>";
						echo "<td class='titulo' height='15'>Defeito Constatado</td>";
						if ($login_fabrica == 30 ){
							echo "<td class='conteudo' colspan=4>$dc_codigo - $dc_descricao</td>";
						}else {
							echo "<td class='conteudo'>&nbsp; $dc_descricao</td>";
						}
						if ($login_fabrica <> 30 ){
							echo "<td class='titulo' height='15'>Solucão</td>";
							echo "<td class='conteudo'>&nbsp; $dc_solucao_descricao</td>";
						}
					echo "</tr>";
				}
			}
		}
	?>
	<?
	if($login_fabrica==20){
		$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
		$xres = @pg_query($con, $xsql);
		$xsolucao = trim(@pg_fetch_result($xres,0,descricao));
		echo "<tr>";
		echo "<td class='titulo' height='15' width='90'>IDENTIFICAÇÃO&nbsp;</td>";
		echo "<td class='conteudo'colspan='3' height='15'>&nbsp;$xsolucao</TD>";
		echo "</tr>";
	}
	?>
</TABLE>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DO CONSUMIDOR&nbsp;</td>
	</tr>
	<TR>
		<TD class="titulo" height='15'>NOME&nbsp;</TD>
		<TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_nome ?></TD>
		<TD class="titulo">TELEFONE RESIDENCIAL&nbsp;</TD>
		<TD class="conteudo"height='15'>&nbsp;<? echo $consumidor_fone ?></TD>
	</TR>
	<?// if($login_fabrica==3 or $login_fabrica == 45 or $login_fabrica == 59){ HD 199446 liberar para todas as fabricas?>
    <TR>
        <TD class="titulo" height='15'>TELEFONE CELULAR&nbsp;</TD>
        <TD class="conteudo" height='15' width='300'>&nbsp;<? echo $consumidor_celular ?></TD>
        <TD class="titulo">TELEFONE COMERCIAL&nbsp;</TD>
        <TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_fone_comercial ?></TD>
    </TR>
	<?//}?>
	<TR>
		<TD class="titulo" height='15'>CPF&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cpf ?></TD>
		<TD class="titulo" height='15'>CEP&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_cep ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>
            <a href='mapa_rede.php?<?="callcenter=true&pais=BR&estado=$consumidor_estado&cidade=$consumidor_cidade&cep=$consumidor_cep&consumidor=$consumidor_endereco $consumidor_numero"."$consumidor_complemento $consumidor_bairro $consumidor_cidade $consumidor_estado"?>'
              title='Calcular o trajeto entre o posto e o endereço do cliente' target='_blank'>
                <img  src='/assist/imagens/icone_mapa.gif' alt='mapa'
                    style='vertical-align: bottom;height: 15px;overlow: hidden;'>
            </a>
        ENDEREÇO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_endereco ?></TD>
		<TD class="titulo" height='15'>NÚMERO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_numero ?></TD>
	</TR>
	<TR>
		<TD class="titulo" height='15'>COMPLEMENTO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_complemento ?></TD>
		<TD class="titulo" height='15'>BAIRRO&nbsp;</TD>
		<TD class="conteudo" height='15'>&nbsp;<? echo $consumidor_bairro ?></TD>
	</TR>
	<TR>
		<TD class="titulo">CIDADE&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_cidade ?></TD>
		<TD class="titulo">ESTADO&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_estado ?></TD>
	</TR>
	<TR>
		<TD class="titulo">EMAIL&nbsp;</TD>
		<TD class="conteudo">&nbsp;<? echo $consumidor_email ?></TD>
		<?if($login_fabrica==1){?>
			<TD class="titulo">TIPO CONSUMIDOR</TD>
			<TD class="conteudo">&nbsp;<? echo $fisica_juridica; ?></TD>
		<?}elseif($login_fabrica==11){?>
			<TD class="titulo">FONE REC</TD>
			<TD class="conteudo"><? echo $consumidor_fone_recado; ?></TD>
		<?}else{?>
			<TD class="titulo">&nbsp;</TD>
			<TD class="conteudo">&nbsp;</TD>
		<?}?>
	</TR>
</TABLE>




<?
/*COLORMAQ TEM 2 REVENDAS*/
if($login_fabrica==50){

	$sql = "SELECT
				cnpj,
				to_char(data_venda, 'dd/mm/yyyy') as data_venda
			FROM tbl_numero_serie
			WHERE serie = trim('$serie')";

	$res_serie = pg_query ($con,$sql);

	if (pg_num_rows ($res_serie) > 0) {


		$txt_cnpj       = trim(pg_fetch_result($res_serie,0,cnpj));
		$data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

		$sql = "SELECT      tbl_revenda.nome              ,
							tbl_revenda.revenda           ,
							tbl_revenda.cnpj              ,
							tbl_revenda.cidade            ,
							tbl_revenda.fone              ,
							tbl_revenda.endereco          ,
							tbl_revenda.numero            ,
							tbl_revenda.complemento       ,
							tbl_revenda.bairro            ,
							tbl_revenda.cep               ,
							tbl_revenda.email             ,
							tbl_cidade.nome AS nome_cidade,
							tbl_cidade.estado
				FROM        tbl_revenda
				LEFT JOIN   tbl_cidade USING (cidade)
				LEFT JOIN   tbl_estado using(estado)
				WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

		$res_revenda = pg_query ($con,$sql);

		# HD 31184 - Francisco Ambrozio (02/08/08) - detectei que pode haver
		#   casos em que o SELECT acima não retorna resultado nenhum.
		#   Acrescentei o if para que não dê erros na página.
		$msg_revenda_info = "";
		if (pg_num_rows ($res_revenda) > 0) {
			$revenda_nome_1       = trim(pg_fetch_result($res_revenda,0,nome));
			$revenda_cnpj_1       = trim(pg_fetch_result($res_revenda,0,cnpj));

			$revenda_bairro_1     = trim(pg_fetch_result($res_revenda,0,bairro));
			$revenda_cidade_1     = trim(pg_fetch_result($res_revenda,0,cidade));
			$revenda_fone_1       = trim(pg_fetch_result($res_revenda,0,fone));
		} else {
			$msg_revenda_info = "Não foi possível obter INFORMAÇÕES DA REVENDA (CLIENTE COLORMAQ): Nome, CNPJ e Telefone.";
		}
?>
		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;<?echo "INFORMAÇÕES DA REVENDA (CLIENTE COLORMAQ)";?></td>
			</tr>
			<? if (strlen($msg_revenda_info) > 0){
					echo "<tr>";
					echo "<td class='conteudo' colspan= '4' height='15'><center>$msg_revenda_info</center></td>";
					echo "</tr>";
				} ?>
			<TR>
				<TD class="titulo"  height='15' ><?echo "NOME";?>&nbsp;</TD>
				<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome_1 ?></TD>
				<TD class="titulo"  height='15' width='80'><?echo "CNPJ";?>&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj_1 ?></TD>
			</TR>
			<TR>
			<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
				<TD class="titulo"  height='15'><?echo "FONE";?>&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_fone_1  ?></TD>
				<TD class="titulo"  height='15'><?echo "DATA DA NF";?>&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<?echo $data_venda; ?></TD>
			</TR>
		</TABLE>
<?

	}
}
/*COLORMAQ TEM 2 REVENDAS - FIM*/
?>




<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>&nbsp;INFORMAÇÕES DA REVENDA <? if($login_fabrica==50){ echo " (CONSUMIDOR)";}?> </td>
	</tr>
	<TR>
		<TD class="titulo"  height='15' width='90'>NOME&nbsp;</TD>
		<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
		<TD class="titulo"  height='15' width='80'>CNPJ&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
	</TR>
	<TR>
		<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
		<TD class="titulo"  height='15'>NF NÚMERO&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<FONT COLOR="#FF0000"><? if($login_fabrica==6 and $posto==4260 and strlen($nota_fiscal_saida)>0) echo $nota_fiscal_saida ; else echo $nota_fiscal; ?></FONT></TD>
		<TD class="titulo"  height='15'>DATA DA NF&nbsp;</TD>
		<TD class="conteudo"  height='15'>&nbsp;<? if($login_fabrica==6 and $posto==4260 and strlen($data_nf_saida)>0) echo $data_nf_saida ; else echo $data_nf; ?></TD>
	</TR>
	<?if($login_fabrica==11) { ?>
		<TR>
			<TD class="titulo"  height='15'>FONE&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<?echo $revenda_fone;?></TD>
			<TD class="titulo"  height='15'>EMAIL&nbsp;</TD>
			<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_email; ?></TD>
		</TR>
	<?}

	if($login_fabrica==11){
		$sql = "SELECT nota_fiscal_saida,
			to_char(data_nf_saida, 'DD/MM/YYYY') as data_nf_saida
			FROM   tbl_os
			WHERE os     = $os
			";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res)==1){

			$nota_fiscal_saida    = pg_fetch_result($res,0,nota_fiscal_saida);
			$data_nf_saida        = pg_fetch_result($res,0,data_nf_saida);

			?>
			 <TR>
				<TD class="titulo"  height='15' >NF DE SAIDA</TD>
				<TD class="conteudo"  height='15' width='300'><? echo $nota_fiscal_saida;?></TD>
				<TD class="titulo"  height='15'>DATA&nbsp;NF&nbsp;DE&nbsp;SAIDA</TD>
				<TD class="conteudo"  height='15'><? echo $data_nf_saida;?></TD>
			</TR>
		<?}
	}?>
</TABLE>

<?
	/* HD 26244 */
	if ($login_fabrica==30 AND strlen($certificado_garantia)>0){

		$sql_status = "	SELECT	status_os,
								observacao,
								to_char(data, 'DD/MM/YYYY')   as data_status
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (105,106,107)
						ORDER BY tbl_os_status.data DESC
						LIMIT 1 ";
		$res_status = pg_query($con,$sql_status);
		$resultado = pg_num_rows($res_status);
		if ($resultado>0){
				$estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
				$estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
				$estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

				if ($estendida_status_os == 105){
					$estendida_observacao = "OS em auditoria";
				}
				if ($estendida_status_os == 106){
					$estendida_observacao = "OS Aprovada na Auditoria";
				}
				if ($estendida_status_os == 107){
					$estendida_observacao = "OS Recusada na Auditoria";
				}
			?>

		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;GARANTIA ESTENDIDA </td>
			</tr>
			<TR>
				<TD class="titulo"  height='15' width='90'>LGI&nbsp;</TD>
				<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $certificado_garantia ?></TD>
				<TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
			</TR>
		</TABLE>
<?
		}
	}
?>

<?
	/* HD 26244 */
	if ($login_fabrica==30){

		$sql_status = "	SELECT	status_os,
								observacao,
								to_char(data, 'DD/MM/YYYY')   as data_status
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (132,19)
						ORDER BY tbl_os_status.data DESC
						LIMIT 1 ";
		$res_status = pg_query($con,$sql_status);
		$resultado = pg_num_rows($res_status);
		if ($resultado>0){
				$estendida_status_os   = trim(pg_fetch_result($res_status,0,status_os));
				$estendida_observacao  = trim(pg_fetch_result($res_status,0,observacao));
				$estendida_data_status = trim(pg_fetch_result($res_status,0,data_status));

				if ($estendida_status_os == 132){
					$estendida_observacao = "OS em auditoria de reincidência";
				}
			?>

		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
			<tr>
				<td class='inicio' colspan='4' height='15'>&nbsp;Auditoria </td>
			</tr>
			<TR>
				<TD class="titulo"  height='15' width='80'>STATUS ATUAL&nbsp;</TD>
				<TD class="conteudo"  height='15'>&nbsp;<? echo $estendida_observacao ?></TD>
			</TR>
		</TABLE>
<?
		}
	}
?>


<?
$sql = "SELECT os
		FROM tbl_os_troca_motivo
		WHERE os = $os ";
$res = pg_query($con,$sql);
if($login_fabrica==20 AND pg_num_rows($res)>0) {
	$motivo1 = "Não são fornecidas peças de reposição para este produto";
	$motivo2 = "Há peça de reposição, mas está em falta";
	$motivo3 = "Vicio do produto";
	$motivo4 = "Divergência de voltagem entre embalagem e produto";
	$motivo5 = "Informações adicionais";
	$motivo6 = "Informações complementares";
	$troca = true;
?>

<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
	<tr>
		<td class='inicio' colspan='4' height='15'>
<?
if($sistema_lingua=='ES')echo "Informações sobre o MOTIVO DA TROCA";
else {
	echo "Informações sobre o MOTIVO DA TROCA";
}
?>
<div id="container">
	<div id="page">
<?

		$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
						tbl_causa_defeito.codigo        AS causa_codigo     ,
						tbl_causa_defeito.descricao     AS causa_defeito
				FROM   tbl_os_troca_motivo
				JOIN   tbl_servico_realizado USING(servico_realizado)
				JOIN   tbl_causa_defeito     USING(causa_defeito)
				WHERE os     = $os
				AND   motivo = '$motivo1'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)==1){
			echo "OK";
			$identificacao1 = pg_fetch_result($res,0,servico_realizado);
			$causa_defeito1 = pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
		?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Data de entrada do produto na assistência técnica
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $data_abertura; ?>
				</div>
			</div>

			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo1; ?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Identificação do defeito
				</div>
				<div id="contentleft2" style="width: 250px; ">
					Defeito
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $identificacao1; ?>
				</div>
				<div id="contentleft" style="width: 250px;font:75%">
					<? echo $causa_defeito1; ?>
				</div>
			</div>
			<?
			}
			$sql = "SELECT
							TO_CHAR(data_pedido,'DD/MM/YYYY') AS data_pedido    ,
							pedido                                              ,
							PE.referencia                     AS peca_referencia,
							PE.descricao                      AS peca_descricao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_peca            PE USING(peca)
					WHERE os     = $os
					AND   motivo = '$motivo2'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)==1){
				$peca_referencia = pg_fetch_result($res,0,peca_referencia);
				$peca_descricao  = pg_fetch_result($res,0,peca_descricao);
				$data_pedido     = pg_fetch_result($res,0,data_pedido);
				$pedido          = pg_fetch_result($res,0,pedido);

			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo2?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Código da Peça
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Data do Pedido
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Número do Pedido
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $peca_referencia."-".$peca_descricao; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $data_pedido; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $pedido; ?>
				</div>
			</div>
			<?
			}

			$sql = "SELECT  tbl_servico_realizado.descricao AS servico_realizado,
							tbl_causa_defeito.codigo        AS causa_codigo     ,
							tbl_causa_defeito.descricao     AS causa_defeito    ,
							observacao
					FROM   tbl_os_troca_motivo
					JOIN   tbl_servico_realizado USING(servico_realizado)
					JOIN   tbl_causa_defeito     USING(causa_defeito)
					WHERE os     = $os
					AND   motivo = '$motivo3'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)==1){
				$identificacao2 = pg_fetch_result($res,0,servico_realizado);
				$causa_defeito2 =  pg_fetch_result($res,0,causa_codigo)." - ".pg_fetch_result($res,0,causa_defeito);
				$observacao1    = pg_fetch_result($res,0,observacao);

			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo3?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					Identificação do Defeito
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Defeito
				</div>
				<div id="contentleft2" style="width: 200px; ">
					Quais as OS’s deste produto:
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $identificacao2; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $causa_defeito2; ?>
				</div>
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $observacao1; ?>
				</div>
			</div>
			<?
			}

			$sql = "SELECT observacao
					FROM   tbl_os_troca_motivo
					WHERE os     = $os
					AND   motivo = '$motivo4'";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)==1){
				$observacao2    = pg_fetch_result($res,0,observacao);
			?>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 200px; " nowrap>
					<br><? echo $motivo4; ?>
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft2" style="width: 650px; " nowrap>
					Qual a divergência:
				</div>
			</div>
			<div id="contentcenter" style="width: 650px;">
				<div id="contentleft" style="width: 200px;font:75%">
					<? echo $observacao2; ?>
				</div>
			</div>
			<?
			}
			?>
		</h2>
	</div>
</div>
<?
	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo5'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)==1){
		$observacao3    = pg_fetch_result($res,0,observacao);
		?>
		<div id="container">
			<div id="page">
				<h2><?=$motivo5?>
				<div id="contentcenter" style="width: 650px;">
					<div id="contentleft" style="width: 650px;font:75%"><? echo $observacao3;?></div>
				</div>
				</h2>
			</div>
		</div>
		<?
	}
	/* HD 43302 - 26/9/2008 */
	$sql = "SELECT observacao
			FROM   tbl_os_troca_motivo
			WHERE os     = $os
			AND   motivo = '$motivo6'";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)==1){
		$observacao4    = pg_fetch_result($res,0,observacao);
		?>
		<div id="container">
			<div id="page">
				<h2><?=$motivo6?>
				<div id="contentcenter" style="width: 650px;">
					<div id="contentleft" style="width: 650px;font:75%"><? echo $observacao4;?></div>
				</div>
				</h2>
			</div>
		</div>
		<?
	}
}
?>
		</td>
	</tr>
</table>
<?

/*takashi compressores*/
if($login_fabrica==1){
	$sql = "SELECT tecnico
		FROM tbl_os_extra
		WHERE os= $os";
	$res = pg_query($con,$sql);
	$relatorio_tecnico             = pg_fetch_result($res,0,tecnico);
	if($tipo_os == 13){
		$where_visita= " os_revenda=$os_numero";
	}else{
		$where_visita= "os=$os";
	}
	$sql = "SELECT 	os                                  ,
					to_char(data, 'DD/MM/YYYY') as  data,
					to_char(hora_chegada_cliente, 'HH24:MI') as inicio      ,
					to_char(hora_saida_cliente, 'HH24:MI')   as fim         ,
					km_chegada_cliente   as km          ,
					valor_adicional                     ,
					justificativa_valor_adicional       ,
					qtde_produto_atendido
			FROM tbl_os_visita
			WHERE $where_visita";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){

		echo "<table border='0' cellpadding='0' cellspacing='1' width='700px' align='center' class='Tabela'>";
		echo "<tr class='inicio'>";
		if($tipo_os == 13){
			echo "<td width='100%' colspan='6'>&nbsp;DESPESAS DA OS GEO METAL: $os_numero</td>";
		}else{
			echo "<td width='100%' colspan='6'>&nbsp;DESPESAS DE COMPRESSORES</td>";
		}
		echo "</tr>";

		echo "<tr>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data da visita</font></td>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora início</font></td>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Hora fim</font></td>";
		echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>KM</font></td>";
		if($tipo_os ==13){
			echo "<td nowrap class='titulo2' rowspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Qtde Produto Atendido</font></td>";
		}
		echo "<td nowrap class='titulo2' colspan='2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Despesas Adicionais</font></td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td nowrap class='titulo2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor</font></td>";
		echo "<td nowrap class='titulo2'>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Justificativa</font></td>";
		echo "</tr>";

		for($i=0;$i<pg_num_rows($res);$i++){

			$data                          = pg_fetch_result($res,$i,data);
			$inicio                        = pg_fetch_result($res,$i,inicio);
			$fim                           = pg_fetch_result($res,$i,fim);
			$km                            = pg_fetch_result($res,$i,km);
			$valor_adicional               = pg_fetch_result($res,$i,valor_adicional);
			$justificativa_valor_adicional = pg_fetch_result($res,$i,justificativa_valor_adicional);
			$qtde_produto_atendido         = pg_fetch_result($res,$i,qtde_produto_atendido);


			echo "<tr class='conteudo'>";
			echo "<td align='center'>&nbsp;$data                         </td>";
			echo "<td align='center'>&nbsp;$inicio                       </td>";
			echo "<td align='center'>&nbsp;$fim                          </td>";
			echo "<td align='center'>&nbsp;$km                           </td>";
			if($tipo_os ==13){
				echo "<td align='center'>&nbsp;$qtde_produto_atendido    </td>";
			}
			echo "<td align='center'>&nbsp;".number_format($valor_adicional,2,",",".")."         </td>";
			echo "<td align='center'>&nbsp;$justificativa_valor_adicional</td>";
			echo "</tr>";
		}
		if($tipo_os==13){
			echo "<tr class='titulo2'>";
			echo "<td align='center' colspan='7'>Relatório Técnico</td>";
			echo "</tr>";
			echo "<tr class='Conteudo'>";
			echo "<td align='left' colspan='7'>$relatorio_tecnico</td>";
			echo "</tr>";
		}
		echo "</table>";

	}
}
 ?>

<? //if (strlen($defeito_reclamado) > 0) { ?>
<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<TR>
	<TD colspan="<? if ($login_fabrica == 1) { echo "8"; }else{ echo "7"; } ?>"class='inicio'>&nbsp;DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS</TD>
</TR>
<TR>
<!-- 	<TD class="titulo">EQUIPAMENTO</TD> -->
	<?
	if($os_item_subconjunto == 't') {
		echo"<TD class=\"titulo2\">SUBCONJUNTO</TD>";
		echo"<TD class=\"titulo2\">POSIÇÃO</TD>";
	}
	?>
	<? // HD 23036
	if($login_fabrica == 11){?>
		<TD class="titulo2">ADMIN</TD>
		<? } ?>
	<TD class="titulo2">COMPONENTE</TD>
	<TD class="titulo2">QTDE</TD>
	<? if ($login_fabrica == 1 and 1==2) echo "<TD class='titulo'>PREÇO</TD>"; ?>
	<TD class="titulo2">DIGIT.</TD>
	<TD class="titulo2">DEFEITO</TD>
	<TD class="titulo2">SERVIÇO</TD>
	<TD class="titulo2">PEDIDO</TD>
	<?if ($login_fabrica == 3) { /* ALTERADO TODA A ROTINA DE NF - HD 8973 */ ?>
		<TD class="titulo2" colspan='2' nowrap>N.F. FABRICANTE</TD>
	<?}?>
	<TD class="titulo2">NOTA FISCAL</TD>
	<TD class="titulo2">EMISSÃO</TD>
	<?
	//Gustavo 12/12/2007 HD 9095
	if ($login_fabrica == 35 or $login_fabrica ==45)  echo "<TD class='titulo2'>CONHECIMENTO</TD>"; ?>


<?
	$sql = "SELECT  tbl_produto.referencia                                        ,
					tbl_produto.descricao                                         ,
					tbl_os_produto.serie                                          ,
					tbl_os_produto.versao                                         ,
					tbl_os_item.serigrafia                                        ,
					tbl_os_item.pedido    AS pedido_item                          ,
					tbl_os_item.peca                                              ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
					tbl_defeito.descricao AS defeito                              ,
					tbl_peca.referencia   AS referencia_peca                      ,
					tbl_os_item_nf.nota_fiscal                                    ,
					tbl_os_item.obs                                                ,
					TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf      ,
					tbl_peca.descricao    AS descricao_peca                       ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao,
					tbl_status_pedido.descricao     AS status_pedido              ,
					tbl_produto.referencia          AS subproduto_referencia      ,
					tbl_produto.descricao           AS subproduto_descricao       ,
					tbl_lista_basica.posicao                                      ,
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			JOIN	tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
									       AND tbl_lista_basica.peca    = tbl_peca.peca
			LEFT JOIN    tbl_defeito USING (defeito)
			LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			ORDER BY tbl_peca.descricao";

	# HD 153693
	$ordem = ($login_fabrica == 11) ? " ORDER BY tbl_os_item.digitacao_item ,tbl_peca.referencia  " : " ORDER BY tbl_peca.descricao ";

	$sql = "SELECT  tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_os_produto.serie                                           ,
					tbl_os_produto.versao                                          ,
					tbl_os_item.os_item                                            ,
					tbl_os_item.serigrafia                                         ,
					tbl_os_item.pedido                                             ,
					tbl_os_item.pedido_item                                        ,
					tbl_os_item.peca                                               ,
					tbl_os_item.obs                                                ,
					tbl_os_item.custo_peca                                         ,
					tbl_os_item.posicao                                            ,
					tbl_os_item.admin                                              ,
                    tbl_os_item.servico_realizado AS servico_realizado_peca        ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
					case
						when tbl_pedido.pedido_blackedecker > 499999 then
							lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 399999 then
							lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 299999 then
							lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 199999 then
							lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
						when tbl_pedido.pedido_blackedecker > 99999 then
							lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
					else
						lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
					end                                      AS pedido_blackedecker,
					tbl_pedido.seu_pedido                                          ,
					tbl_pedido.distribuidor                                        ,
					tbl_defeito.descricao           AS defeito                     ,
					tbl_peca.referencia             AS referencia_peca             ,
                    tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
                    tbl_peca.retorna_conserto     AS retorna_conserto              ,
					tbl_peca.devolucao_obrigatoria  AS devolucao_obrigatoria       ,
					tbl_os_item_nf.nota_fiscal                                     ,
					TO_CHAR (tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf      ,
					tbl_peca.descricao              AS descricao_peca              ,
					tbl_servico_realizado.descricao AS servico_realizado_descricao ,
					tbl_status_pedido.descricao     AS status_pedido               ,
					tbl_produto.referencia          AS subproduto_referencia       ,
					tbl_produto.descricao           AS subproduto_descricao        ,
					tbl_os_item.qtde                                               ,
					tbl_os_item.faturamento_item    AS faturamento_item			   ,
					tbl_admin.login					AS nome_admin                  ,
					TO_CHAR (tbl_os_item.data_liberacao_pedido,'DD/MM/YYYY HH24:MI') AS data_liberacao_pedido
			FROM	tbl_os_produto
			JOIN	tbl_os_item USING (os_produto)
			JOIN	tbl_produto USING (produto)
			JOIN	tbl_peca    USING (peca)
			LEFT JOIN tbl_defeito USING (defeito)
			LEFT JOIN tbl_servico_realizado USING (servico_realizado)
			LEFT JOIN tbl_admin          ON tbl_os_item.admin        = tbl_admin.admin
			LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
			LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
			LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
			WHERE   tbl_os_produto.os = $os
			$ordem ";

	$res = pg_query($con,$sql);
	$total = pg_num_rows($res);

	$tem_pedido = 'f';
	$exibe_legenda = 0;

	for ($i = 0 ; $i < $total ; $i++) {
		$pedido                  = trim(pg_fetch_result($res,$i,pedido));
		$pedido_item             = trim(pg_fetch_result($res,$i,pedido_item));
		$pedido_blackedecker     = trim(pg_fetch_result($res,$i,pedido_blackedecker));
		$seu_pedido              = trim(pg_fetch_result($res,$i,seu_pedido));
		$os_item                 = trim(pg_fetch_result($res,$i,os_item));
		$peca                    = trim(pg_fetch_result($res,$i,peca));
		$faturamento_item        = trim(pg_fetch_result($res,$i,faturamento_item));
		//chamado 141 - britania - pega nota fiscal do distribuidor
		if ($login_fabrica == 3) {
			$nota_fiscal_distrib = trim(pg_fetch_result($res,$i,nota_fiscal));
			$data_nf_distrib     = trim(pg_fetch_result($res,$i,data_nf));
			$nota_fiscal         = "";
			$data_nf             = "";
			$link_distrib        = 0;
		} else {
			$nota_fiscal         = trim(pg_fetch_result($res,$i,nota_fiscal));
			$data_nf             = trim(pg_fetch_result($res,$i,data_nf));
		}
		$status_pedido           = trim(pg_fetch_result($res,$i,status_pedido));
		$obs                     = trim(pg_fetch_result($res,$i,obs));
		$distribuidor            = trim(pg_fetch_result($res,$i,distribuidor));
		$digitacao               = trim(pg_fetch_result($res,$i,digitacao_item));
		$admin_digitou           = trim(pg_fetch_result($res,$i,admin));
		$data_liberacao_pedido   = trim(pg_fetch_result($res,$i,data_liberacao_pedido));

		if (strlen($pedido) > 0) $tem_pedido = 't';

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}

		if (($login_fabrica==3 OR $login_fabrica==5) and strlen($admin_digitou) > 0) {
			$sqla = "SELECT login FROM tbl_admin WHERE admin = $admin_digitou";
			$resa = pg_query($con, $sqla);
			$admin_digitou = " <b style='font-weight:normal;color:#000000;font-size:10px'>(Digitado por ".trim(pg_fetch_result($resa,0,0)).")</b> ";
		}



		/*====--------- INICIO DAS NOTAS FISCAIS ----------===== */
		 /* ALTERADO TODA A ROTINA DE NF - HD 8973 */
		/*############ BLACKEDECKER ############*/
		if ($login_fabrica == 1){
			if (strlen ($nota_fiscal) == 0) {
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(nota_fiscal) As nota_fiscal ,
							TO_CHAR(data, 'DD/MM/YYYY') AS emissao
							FROM    tbl_pendencia_bd_novo_nf
							WHERE   pedido_banco = $pedido
							AND     peca         = $peca";
					$resx = pg_query ($con,$sql);
						// HD 22338
					if (pg_num_rows ($resx) > 0 AND 1==2) {
						$nf   = trim(pg_fetch_result($resx,0,nota_fiscal));
						$link = 0;
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
					}else{
						// HD 30781
						$sql  = "SELECT trim(nota_fiscal_saida) As nota_fiscal_saida ,
							TO_CHAR(data_nf_saida, 'DD/MM/YYYY') AS data_nf_saida
							FROM    tbl_os
							JOIN    tbl_os_produto USING (os)
							JOIN    tbl_os_item USING (os_produto)
							JOIN    tbl_peca USING(peca)
							WHERE   tbl_os_item.pedido= $pedido
							AND     tbl_os_item.peca         = $peca
							AND     tbl_peca.produto_acabado IS TRUE ";
						$resnf = pg_query ($con,$sql);
						if(pg_num_rows($resnf) >0){
							$nf   = trim(pg_fetch_result($resnf,0,nota_fiscal_saida));
							$link = 0;
							$data_nf = trim(pg_fetch_result($resnf,0,data_nf_saida));
						}else{
							$nf      = "Pendente";
							$data_nf = "";
							$link    = 1;
						}
					}
				}else{
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}

		/*############ BRITANIA ############*/
		}elseif ($login_fabrica == 3){

			//Nota do fabricante para distribuidor
			//NF para BRITANIA (DISTRIBUIDORES E FABRICANTES chamado 141) =============================

			if (strlen($pedido) > 0) {
				if(strlen($distribuidor) > 0){

					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento_item.pedido  = $pedido
							AND     tbl_faturamento_item.peca    = $peca
							AND     tbl_faturamento.posto = $distribuidor";
					echo "E2 - " . nl2br($sql);
					$resx = pg_query ($con,$sql);
					if (pg_num_rows ($resx) > 0) {
						$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
						$link    = 0;
					} else {
						$nf      = 'Pendente'; #HD 16354
						$data_nf = '';
						$link    = 0;
					}

					if ($distribuidor == 4311) {
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido  = $pedido
								/*AND     tbl_faturamento_item.peca  = $peca*/
								AND     tbl_faturamento_item.os_item = $os_item ";

								//retirado por Samuel 4/12/2007 - Um nf do distrib atendendo 2 os não tem como gravar 2 os_item.
								// Coloquei AND     tbl_faturamento_item.os_item = $os_item - Fabio - HD 7591

						$sql .= "AND     tbl_faturamento.posto        = $posto
								AND     tbl_faturamento.distribuidor = 4311";

						$resx = pg_query ($con,$sql);
						if (pg_num_rows ($resx) > 0) {
							$nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
							$link_distrib        = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link_distrib        = 0;
						}
					}

					if($distribuidor != 4311) {
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca   = $peca
								AND     tbl_faturamento.posto       <> $distribuidor;";
						#echo "E2 - " . nl2br($sql);
						$resx = pg_query ($con,$sql);

						if (pg_num_rows ($resx) > 0) {
							$nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
							$link_distrib        = 1;
						} else {
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link_distrib        = 0;
						}
					}
				}else{
					//(tbl_faturamento_item.os = $os) --> HD3709
				/*	HD 72977 */
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
								TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
						FROM    tbl_faturamento
						JOIN    tbl_faturamento_item USING (faturamento)
						WHERE   tbl_faturamento_item.pedido = $pedido
						AND     tbl_faturamento_item.peca   = $peca
						AND     tbl_faturamento.posto       = $posto
						AND     (length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os = $os)
						";
					$resx = pg_query ($con,$sql);

					if (pg_num_rows ($resx) > 0){
						$nf                  = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf             = trim(pg_fetch_result($resx,0,emissao));
						//se fabrica atende direto posto seta a mesma nota
						$nota_fiscal_distrib = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf_distrib     = trim(pg_fetch_result($resx,0,emissao));
						$link = 1;
					}else{
						//Foi alterado para buscar primeiro a NF com a peça caso não encontre, busca pela OS. HD 72977 HD 77790 HD 125880
						$sqly = "SELECT tbl_faturamento.nota_fiscal                                  ,
										to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao
						FROM tbl_faturamento_item
						JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
											 AND  tbl_faturamento.fabrica   = $login_fabrica
						JOIN   tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
										AND tbl_peca.fabrica         = $login_fabrica
						JOIN tbl_os_troca ON tbl_os_troca.os = tbl_faturamento_item.os
						WHERE  tbl_faturamento_item.pedido = $pedido
						AND    (
								(length(tbl_faturamento_item.os::text) = 0 OR tbl_faturamento_item.os IS NULL)
								OR tbl_faturamento_item.os = $os
								)
						AND     tbl_faturamento.posto =  $posto
						AND     tbl_os_troca.pedido   =  $pedido
						ORDER   BY tbl_peca.descricao";
						#echo "E4 - " . nl2br($sqly);
						$resy = pg_query ($con,$sqly);

						if (pg_num_rows ($resy) > 0){
							$nf                  = trim(pg_fetch_result($resy,0,nota_fiscal));
							$data_nf             = trim(pg_fetch_result($resy,0,emissao));
							//se fabrica atende direto posto seta a mesma nota
							$nota_fiscal_distrib = trim(pg_fetch_result($resy,0,nota_fiscal));
							$data_nf_distrib     = trim(pg_fetch_result($resy,0,emissao));
							$link = 1;
						}else{
							$nf                  = "Pendente";
							$data_nf             = "";
							$nota_fiscal_distrib = "";
							$data_nf_distrib     = "";
							$link                = 0;
						}
					}
				}
			}else{
				$nf                  = "";
				$data_nf             = "";
				$nota_fiscal_distrib = "";
				$data_nf_distrib     = "";
				$link = 0;
			}

		/*############ LENOXX ############*/
		}elseif ($login_fabrica==11){
				 # Agora o pedido da peça ta amarrado no faturamento item: Fabio 09/08/2007
				if (strlen($faturamento_item)>0){
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento.fabrica = $login_fabrica
								AND     tbl_faturamento_item.faturamento_item = $faturamento_item";
						$resx = pg_query ($con,$sql);
						#echo nl2br($sql);
						if (pg_num_rows ($resx) > 0) {
							$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf = trim(pg_fetch_result($resx,0,emissao));
							$link = 1;
						}else{
							$nf ="Pendente";
							$data_nf="";
							$link = 0;
						}
				}else{
					if (strlen($pedido) > 0) {
							$nf ="Pendente";
							$data_nf="";
							$link = 0;
					}else{
							$nf ="";
							$data_nf="";
							$link = 0;
					}
				}

		/*############ CADENCE ############*/
		}elseif ($login_fabrica==35 or $login_fabrica == 45) {
			if (strlen ($nota_fiscal) == 0){
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca;";
					$resx = pg_query ($con,$sql);

					if (pg_num_rows ($resx) > 0) {
						$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
						$link = 1;
					}else{
						//cadence relaciona pedido_item na os_item
						$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
										TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
										tbl_faturamento.posto,
										tbl_faturamento.conhecimento
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido      = $pedido
								AND     tbl_faturamento_item.peca        = $peca";
						if($login_fabrica == 35) {
							$sql .= " AND     tbl_faturamento_item.pedido_item = $pedido_item ";
						}

						$resx = pg_query ($con,$sql);

						if (pg_num_rows ($resx) > 0) {
							$nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf      = trim(pg_fetch_result($resx,0,emissao));
							$conhecimento = trim(pg_fetch_result($resx,0,conhecimento));
							$link         = 1;
						}else{
							$nf           = "Pendente";
							$data_nf      = "";
							$conhecimento = "";
							$link         = 1;
						}
					}
				}else{
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}
		/*############ DEMAIS FABRICANTES ############*/
		}else{
			if (strlen ($nota_fiscal) == 0){
				if (strlen($pedido) > 0) {
					$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM    tbl_faturamento
							JOIN    tbl_faturamento_item USING (faturamento)
							WHERE   tbl_faturamento.pedido    = $pedido
							AND     tbl_faturamento_item.peca = $peca ";
					if($login_fabrica == 51) $sql.="AND     tbl_faturamento_item.os_item = $os_item ";

					if ($login_fabrica == 2) {
						$sql = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal     ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
							FROM (SELECT * FROM tbl_pedido_item WHERE pedido = $pedido) tbl_pedido_item
							JOIN tbl_pedido_item_faturamento_item on tbl_pedido_item.pedido_item = tbl_pedido_item_faturamento_item.pedido_item
							JOIN tbl_faturamento_item ON tbl_pedido_item_faturamento_item.faturamento_item= tbl_faturamento_item.faturamento_item
							JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.fabrica = $login_fabrica
							WHERE    tbl_faturamento_item.peca = $peca";
					}
					$resx = pg_query ($con,$sql);

					if (pg_num_rows ($resx) > 0) {
						$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
						$data_nf = trim(pg_fetch_result($resx,0,emissao));
						$link = 1;
					}else{
						$condicao_01 = "";
						if (strlen ($distribuidor) > 0) {
							$condicao_01 = " AND tbl_faturamento.distribuidor = $distribuidor ";
						}
						$sql  = "SELECT
									trim(tbl_faturamento.nota_fiscal)                AS nota_fiscal ,
									TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
									tbl_faturamento.posto                            AS posto
								FROM    tbl_faturamento
								JOIN    tbl_faturamento_item USING (faturamento)
								WHERE   tbl_faturamento_item.pedido = $pedido
								AND     tbl_faturamento_item.peca   = $peca
								$condicao_01 ";
								if($login_fabrica == 51) $sql.="AND     tbl_faturamento_item.os_item = $os_item ";
						$resx = pg_query ($con,$sql);

						if (pg_num_rows ($resx) > 0) {
							$nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
							$data_nf      = trim(pg_fetch_result($resx,0,emissao));
							$link         = 1;
						}else{
							$nf      = "Pendente";
							$data_nf = "";
							$link    = 1;
							if($login_fabrica==6 and strlen($data_finalizada)>0){ //hd 3437
								$nf = "Atendido";
							}
						}
					}
				}else{
					$nf = "";
					$data_nf = "";
					$link = 0;
				}
			}else{
				$nf = $nota_fiscal;
			}
		}
		//HD 18479
		if($fabrica==3){
		if((strlen($pedido)>0 AND strlen($peca)>0) AND $nf=="Pendente"){
			$sql = "SELECT motivo
					FROM   tbl_pedido_cancelado
					WHERE  pedido = $pedido
					AND    peca   = $peca
					AND    posto  = $posto;";
			$resx = pg_query ($con,$sql);
			if (pg_num_rows ($resx) > 0) {
				$motivo = pg_fetch_result($resx,0,motivo);
				$nf           = "<a href=\"#\" title=\"$motivo\">Cancelada</a>";
				$data_nf      = "-";
				$link         = 1;
			}
		}
		if(strlen($nota_fiscal_distrib)==0 AND $nf<>'Pendente'){
			$sql = "SELECT motivo
					FROM   tbl_pedido_cancelado
					WHERE  pedido = $pedido
					AND    peca   = $peca
					AND    posto  = $posto;";
			$resx = pg_query ($con,$sql);
			if (pg_num_rows ($resx) > 0) {
				$motivo = pg_fetch_result($resx,0,motivo);
				$nota_fiscal_distrib = "<a href='#' title='$motivo'>Cancelada</a>";
			}
		}
		}
		/*====--------- FIM DAS NOTAS FISCAIS ----------===== HD 8973 */


		$devolucao_obrigatoria  = pg_fetch_result($res,$i,devolucao_obrigatoria);
?>
<TR class="conteudo"
		<?php
			if ($devolucao_obrigatoria == "t" and $login_fabrica == 51){
				$exibe_legenda++;
				echo " style='background-color:#FFC0D0'";
			}?>
>
<!-- 	<TD class="conteudo" style="text-align:left;"><? echo pg_fetch_result ($res,$i,referencia) . " - " . pg_fetch_result ($res,$i,descricao); ?></TD> -->
	<?
	if($os_item_subconjunto == 't') {
		echo"<TD style=\"text-align:left;\">".pg_fetch_result($res,$i,subproduto_referencia) . " - " . pg_fetch_result($res,$i,subproduto_descricao)."</TD>";
		echo "<TD style=\"text-align:center;\">".pg_fetch_result($res,$i,posicao)."</TD>";
	}
	// $status_os -> variavel pegada lá em cima
	$msg_peca_intervencao="";

	$bloqueada_pc           = pg_fetch_result($res,$i,bloqueada_pc);
	$servico_realizado_peca = pg_fetch_result($res,$i,servico_realizado_peca);
	$retorna_conserto		= pg_fetch_result($res,$i,retorna_conserto);

	if (($login_fabrica==3 OR $login_fabrica==11) AND ( $bloqueada_pc=='t' OR $retorna_conserto='t')){

		if ($login_fabrica==11) {
			$id_servico_realizado			= 61;
			$id_servico_realizado_ajuste	= 498;
		}
		if ($login_fabrica==3) {
			$id_servico_realizado			= 20;
			$id_servico_realizado_ajuste	= 96;
		}

		if (($status_os=='62' OR $status_os=='87' OR $status_os=='72' OR $status_os=='116') AND $servico_realizado_peca==$id_servico_realizado){
			$msg_peca_intervencao=" <b style='font-weight:normal;color:$cor_intervencao;font-size:10px'>(aguardando autorização da fábrica)</b>";
		}

		if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado){
			$msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(autorizado pela fábrica)</b>";
			$cancelou_peca = "sim";
		}

		if (($status_os=='64' OR $status_os=='73' OR $status_os=='88' OR $status_os=='117') AND $servico_realizado_peca==$id_servico_realizado_ajuste){
			$msg_peca_intervencao=" <b style='font-weight:normal;color:#CC0000;font-size:10px'>(pedido cancelado pela fábrica)</b>";
			$cancelou_peca = "sim";
		}

		if (($status_os=='62' OR $status_os=='73' OR $status_os=='87' OR $status_os=='116') AND strlen($pedido) > 0 AND $servico_realizado_peca==$id_servico_realizado) {
			$msg_peca_intervencao=" <b style='font-weight:normal;color:#333333;font-size:10px'>(autorizado pela fábrica)</b>";
			$cancelou_peca = "sim";
		}
	}

	if($excluida=='t' and strtolower($nf) == 'pendente') $nf= "Cancelada";

	?>
	<? // HD 23036
	if($login_fabrica == 11){?>
		<TD style="text-align:center;"><? echo pg_fetch_result($res,$i,nome_admin); ?></TD>
		<? } ?>

	<TD style="text-align:left;"><? echo pg_fetch_result($res,$i,referencia_peca) . " - " . pg_fetch_result($res,$i,descricao_peca);  echo $admin_digitou.$msg_peca_intervencao; ?></TD>
	<TD style="text-align:center;"><? echo pg_fetch_result($res,$i,qtde) ?></TD>
	<?
	if ($login_fabrica == 1 and 1==2) {
		echo "<TD style='text-align:center;'>";
		echo number_format (pg_fetch_result($res,$i,custo_peca),2,",",".");
		echo "</TD>";
	}
	?>
	<TD style="text-align:center;" title="<?echo 'Data da liberação:'.$data_liberacao_pedido ?>"><? echo pg_fetch_result($res,$i,digitacao_item) ?></TD>
	<TD style="text-align:left;"><? echo pg_fetch_result($res,$i,defeito) ?></TD>
	<TD style="text-align:left;"><? echo pg_fetch_result($res,$i,servico_realizado_descricao) ?></TD>
	<TD style="text-align:CENTER;">
	<? if ($login_fabrica==43){?>
			<a href='pedido_admin_consulta.php?pedido=<? echo $pedido ?>' target='_blank'><? if ($login_fabrica == 1) echo $pedido_blackedecker; else echo $pedido;
		}else{ ?>
			<a href='pedido_finalizado.php?pedido=<? echo $pedido ?>' target='_blank'><? if ($login_fabrica == 1) echo $pedido_blackedecker; else echo $pedido;
		} ?></a>&nbsp;</TD>
	<TD style="text-align:CENTER;" nowrap>
	<?
	if (strtolower($nf) <> 'pendente' and strtolower($nf) <> 'atendido'){
		if ($link == 1) {
			if($login_fabrica == 50) {
			echo "<a href='nota_fiscal_detalhe.php?pedido=$pedido&peca=$peca' target='_blank'>$nf</a>";
			}else {
				echo "$nf";
			}
		}else{
			echo "$nf ";
			//echo "<a href='nota_fiscal_detalhe.php?nota_fiscal=$nf&peca=$peca' target='_blank'>$nf</a>";
		}
	}else{
		if($login_fabrica==35){
			$sql  = "SELECT * FROM tbl_pedido_cancelado
			WHERE peca=$peca and pedido=$pedido";
			$resY = pg_query ($con,$sql);
			if (pg_num_rows ($resY) > 0) {
				echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
			}else{
				echo "$nf &nbsp;";
			}
		}else{
			$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca";
			$resY = pg_query ($con,$sql);
			if (pg_num_rows ($resY) > 0) {
				echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
			}else{
				if($login_fabrica == 51 or $login_fabrica == 81){
					$sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item";
				}else{
					$sql  = "SELECT tbl_embarque.embarque, TO_CHAR (tbl_embarque.faturar,'DD/MM/YYYY') AS faturar FROM tbl_embarque JOIN tbl_embarque_item USING (embarque) WHERE tbl_embarque_item.os_item = $os_item AND tbl_embarque.faturar IS NOT NULL";
				}
				$resX = pg_query ($con,$sql);
				if (pg_num_rows ($resX) > 0) {
					echo "Embarque " . pg_fetch_result ($resX,0,embarque) . " - " . pg_fetch_result ($resX,0,faturar) ;
				}else{
					echo "$nf &nbsp;";
				}
			}
		}
	}
	?>

	</TD>
	<TD style="text-align:CENTER;"><?= $data_nf ?>&nbsp;</TD>
	<? //Gustavo 12/12/2007 HD 9095
		if ($login_fabrica == 35 or $login_fabrica == 45){
			echo "<TD class='conteudo' style='text-align:CENTER;'>";
				echo "<A HREF='http://www.websro.com.br/correios.php?P_COD_UNI=$conhecimento' target = '_blank'>";
				 echo $conhecimento;
				echo "</A>";
				echo "</TD>";
	}
	?>

	<? //nf do distribuidor - chamado 141
	if ($login_fabrica==3) {
		echo "<TD style='text-align:CENTER;' nowrap colspan='2'>";

		if (strlen($nota_fiscal_distrib) > 0) {
			echo "<acronym title='Nota Fiscal do Distribuidor.' style='cursor:help;'> $nota_fiscal_distrib"." - ".$data_nf_distrib;
		} else {
			//se não tiver nota do distrib verifica se está em embarque e exibe numero do embarque
			$sql  = "SELECT tbl_embarque.embarque,
							to_char(liberado ,'DD/MM/YYYY') as liberado,
							to_char(embarcado ,'DD/MM/YYYY') as embarcado,
							faturar
					FROM tbl_embarque
					JOIN tbl_embarque_item USING (embarque)
					WHERE tbl_embarque_item.os_item = $os_item ";

			// HD 7319 Paulo alterou para mostrar dia que liberou o embarque
			$resX = pg_query ($con,$sql);
			if (pg_num_rows ($resX) > 0) {
				$liberado  = pg_fetch_result($resX,0,liberado);
				$embarcado = pg_fetch_result($resX,0,embarcado);
				$faturar   = pg_fetch_result($resX,0,faturar);

				echo "<acronym title='Embarque do Distribuidor.' style='cursor:help;'>";
				if(strlen($embarcado) > 0 and strlen($faturar) == 0){
					echo "Embarque " . pg_fetch_result ($resX,0,embarque);
				} else {
					echo "Embarcada ". pg_fetch_result($resX,0,liberado);
				}
				echo "</acronym>";
			}else{
				echo "<acronym title='Embarque do Distribuidor.' style='cursor:help;'>";
				echo "$nota_fiscal_distrib";
				echo "</acronym>";
				// HD 7319 Fim
			}
		}
		echo "</TD>";
	}
	?>

</tr>
<?
	//HD 8412
	if(($login_fabrica==35 or $login_fabrica==3 or $login_fabrica==14)and strlen($obs) > 0) {
		echo "<tr>";
		echo "<td class='conteudo' colspan='100%'>";
		echo "Obs: $obs";
		echo "</td></tr>";
	}

	}//Chamado 2365


	//Chamado 2365
	if($login_fabrica == 1 AND (in_array($tipo_atendimento,array(17,18,35,64,65,69)))){

		#HD 15198
		$sql  = "SELECT tbl_os_troca.ri                            AS pedido,
						tbl_os.nota_fiscal_saida                   AS nota_fiscal,
						TO_CHAR(tbl_os.data_nf_saida,'DD/MM/YYYY') AS data_nf
				FROM tbl_os_troca
				JOIN tbl_os USING(os)
				WHERE tbl_os.os      = $os
				AND   tbl_os.fabrica = $login_fabrica; ";
		$resX = pg_query ($con,$sql);
		if (pg_num_rows ($resX) > 0) {
			$Xpedido      = pg_fetch_result($resX,0,pedido);
			$Xnota_fiscal = pg_fetch_result($resX,0,nota_fiscal);
			$Xdata_nf     = pg_fetch_result($resX,0,data_nf);

			#HD 15198
			echo "<tr align='center'>";

				//hd 21461
				$sql = "SELECT descricao
						FROM tbl_produto
						JOIN tbl_os_troca USING(produto)
						WHERE os = $os";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					echo "<td class='conteudo' align='center'><center>".pg_fetch_result($res,0,0)."</center></td>";
				} else {
					echo "<td class='conteudo' align='center'><center>$produto_descricao</center></td>";
				}
				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo'></td>";
				echo "<td class='conteudo' align='center'><center>$Xpedido</center></td>";
				echo "<td class='conteudo' align='center'><center>$Xnota_fiscal</center></td>";
				echo "<td class='conteudo' align='center'><center>$Xdata_nf</center></td>";
			echo "<tr>";
		}
	}
?>
</TABLE>
<?
if ($login_fabrica == 51 and $login_admin == 586){
	$teste = system("cat /tmp/telecontrol/embarque_novo.txt | grep \"$sua_os\" ");
		if(strlen($teste)>0){
		echo $teste;
	}
}
if ($login_fabrica == 51 and $exibe_legenda > 0){
	echo "<BR>\n";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'>\n";
	echo "<TR style='line-height: 12px'>\n";
	echo "<TD width='5' bgcolor='#FFC0D0'>&nbsp;</TD>\n";
	echo "<TD style='padding-left: 10px; font-size: 14px;'><strong>Peças de retorno obrigatório</strong></TD>\n";
	echo "</TR></TABLE>\n";
}

if ($login_fabrica == 50) {
echo "<BR>";
echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
echo "<TR>";
	echo "<TD class='conteudo'><b>OBS:</b>&nbsp;$obs_os</TD>";
echo "</TR>";
echo "</TABLE>";
}


// 7/1/2008 HD 11083 - estava mostrando campo null
if (strlen($orientacao_sac) > 0 and $orientacao_sac <> "null"){
	echo "<BR>";
	echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
	echo "<TR>";
	echo "<TD colspan=7 class='inicio'>&nbsp;Orientações do SAC ao Posto Autorizado</TD>";
	echo "</TR>";
	echo "<TR>";
	echo "<TD class='conteudo_sac'>Obs: ".nl2br(trim(str_replace("|","<br/>",str_replace("<p>","<br/>",str_replace("</p>","<br/>",str_replace("</p><p>","<br/>",str_replace("null","<br />",$orientacao_sac)))))))."</TD>";
	echo "</TR>";
	echo "</TABLE>";
}

if ($login_fabrica == 50){ #HD 68629 para Colormaq ?>
	<p>
	<center>
	<form name='frm_orientacao' method=post action="<? echo "$PHP_SELF?os=$os"; ?>">
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">
		Orientações do SAC ao Posto Autorizado
		</font>
		<br>
		<textarea name='orientacao_sac' rows='4' cols='50'><? if($orientacao_sac!="null") echo trim($orientacao_sac); ?></textarea>
		<br><br>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_orientacao.btn_acao.value == '' ) { document.frm_orientacao.btn_acao.value='gravar_orientacao' ; document.frm_orientacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Orientação" border='0' style="cursor:pointer;">
		</center>
	</form>
	<?

}

?>

<?

if (strlen($obs_os) > 0 and $login_fabrica <> 35 and $login_fabrica <> 50) {
echo "<BR>";
echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center'>";
echo "<TR>";
	echo "<TD class='conteudo'><b>OBS:</b>&nbsp;$obs_os</TD>";
echo "</TR>";
echo "</TABLE>";
}

//HD 163220 - Mostrar os chamados aos quais a OS tem relacionamento no Call Center (tbl_hd_chamado_extra.os)
if ($login_fabrica == 11) {
	$sql = "
	SELECT
	tbl_hd_chamado.hd_chamado,
	TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY') AS data

	FROM
	tbl_hd_chamado
	JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado

	WHERE
	tbl_hd_chamado_extra.os=$os

	ORDER BY
	tbl_hd_chamado.data ASC
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {
		echo "
		<TABLE width='300px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
		<br>
		<TR>
			<TD colspan='2' class='inicio'>ATIVIDADE DE CHAMADOS NO CALLCENTER</TD>

		</TR>
		<TR>
			<TD class='titulo2'>CHAMADO</TD>
			<TD class='titulo2'>DATA</TD>
		</TR>";

		for($h = 0; $h < pg_num_rows($res); $h++) {
			$hd_chamado = pg_fetch_result($res, $h, hd_chamado);
			$data = pg_fetch_result($res, $h, data);

			echo "
			<TR>
				<TD class=conteudo style='text-align:center'>
					<a href='callcenter_interativo_new.php?callcenter=$hd_chamado'>$hd_chamado</a>
				</TD>
				<TD class=conteudo style='text-align:center'>
					<a href='callcenter_interativo_new.php?callcenter=$hd_chamado'>$data</a>
				</TD>
			</TR>";
		}

		echo "
		</TABLE>";
	}
}

if($login_fabrica==3){
?>

<TABLE width="300px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
<br>
<TR>
	<TD colspan='2' class='inicio'>LOG DE ALTERAÇÃO NA OS PELO ADMIN</TD>

</TR>

<TR>
	<TD class='titulo2'>NOME COMPLETO</TD>
	<TD class='titulo2'>DATA</TD>
</TR>

<?


	$sql = "select to_char(tbl_os_log_admin.data, 'dd/mm/yyyy hh24:mi') as data,tbl_admin.nome_completo
	from tbl_os_log_admin
	join tbl_admin on tbl_os_log_admin.admin = tbl_admin.admin
	where tbl_os_log_admin.os=$os";
	$res = pg_query($con,$sql);

	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
	$data  = trim(pg_fetch_result($res,$i,data));
	$nome_completo  = trim(pg_fetch_result($res,$i,nome_completo));
?>

<TR>
	<TD class='titulo2'><? echo $nome_completo;?></TD>
	<TD class='titulo2'><? echo $data;?></TD>

</TR>

<?
	}
?>
</TABLE>
<?
}
?>


<?
# adicionado por Fabio - 26/03/2007 - hd chamado 1392
# adicionado para HBTech - #HD 14830 - Fabrica 25
# adicionado para HBTech - #HD 13618 - Fabrica 45
if ($login_fabrica==1  OR $login_fabrica==3  OR $login_fabrica==6 OR $login_fabrica==11 OR $login_fabrica==25 OR $login_fabrica==45) {
	$sql_status = "SELECT
					os_status,
					status_os,
					observacao,
					tbl_admin.login AS login,
					to_char(data, 'DD/MM/YYYY')   as data_status,
					tbl_os_status.admin
					FROM tbl_os_status
					LEFT JOIN tbl_admin USING(admin)
					WHERE os=$os
					AND status_os IN (72,73,62,64,65,87,88,116,117)
					ORDER BY data ASC";
	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		echo "<TD colspan='7' class='inicio'>&nbsp;Justificativa do Pedido de Peça</TD>\n";
		echo "</TR>\n";

		for ($j=0;$j<$resultado;$j++){
			$os_status          = trim(pg_fetch_result($res_status,$j,os_status));
			$status_os          = trim(pg_fetch_result($res_status,$j,status_os));
			$status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
			$status_admin       = trim(pg_fetch_result($res_status,$j,login));
			$status_data        = trim(pg_fetch_result($res_status,$j,data_status));
			$status_admin2      = trim(pg_fetch_result($res_status,$j,admin));

			if (($status_os==72 OR  $status_os==64) AND strlen($status_observacao)>0){
				$status_observacao = strstr($status_observacao,"Justificativa:");
				$status_observacao = str_replace("Justificativa:","",$status_observacao);
			}

			$status_observacao = trim($status_observacao);

			if (strlen($status_observacao)==0 AND $status_os==73) $status_observacao="Autorizado";
			if (strlen($status_observacao)==0 AND $status_os==72) $status_observacao="-";

			if (strlen($status_admin)>0){
				$status_admin = " ($status_admin)";
				if ($login_fabrica==11){
					$status_observacao = trim(pg_fetch_result($res_status,$j,observacao));
				}
			}

			echo "<TR>\n";

			echo "<TD  class='justificativa' width='100px'  align='center'><b>$status_data</b></TD>\n";
			if ($status_os==72)
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>Justificativa do Posto</b>&nbsp;</TD>\n";
			if ($status_os==73)
				echo "<TD  class='justificativa' width='140px' align='left' nowrap>&nbsp;<b>Resposta da Fábrica</b>&nbsp;</TD>\n";
			if ($status_os==62)
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>OS em Intervenção</b>&nbsp;</TD>\n";
			if ($status_os==65)
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>OS em reparo na Fábrica</b>&nbsp;</TD>\n";
			if ($status_os==64)
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>Resposta da Fábrica</b>&nbsp;</TD>\n";
			if ($status_os==87 OR $status_os==116){
				echo "<TD  class='justificativa' width='140px'  align='left' nowrap>&nbsp;<b>Fábrica</b>&nbsp;</TD>\n";
			}
			if ($status_os==88 OR $status_os==117){
				echo "<TD  class='justificativa' width='140px' align='left' nowrap>&nbsp;<b>Fábrica</b>&nbsp;</TD>\n";
			}
			echo "<TD  class='justificativa' width='450px' align='left' colspan='5'> &nbsp; $status_observacao";
			if ($login_fabrica == 11 AND strlen($status_admin2)>0 AND $status_admin2 == $login_admin){
				echo " <a href=\"javascript:excluirComentario('$os','$os_status');\" title='Apagar este comentário'><img src='imagens/delete_2.gif' align='absmiddle'></a>";
			}
			echo "</TD>\n";

			echo "</TR>\n";
		}
		echo "</TABLE>\n";
	}
}
?>

<? //hd 24288
if ($login_fabrica==3) {
		$sql_status = "SELECT  tbl_os.os                            ,
								(SELECT tbl_status_os.descricao FROM tbl_status_os where tbl_status_os.status_os = tbl_os_status.status_os) AS status_os ,
								tbl_os_status.observacao              ,
								to_char(tbl_os_status.data, 'dd/mm/yyy') AS data
								FROM tbl_os
						LEFT JOIN tbl_os_status USING(os)
						WHERE tbl_os.os    = $os
						AND   tbl_os_status.status_os IN(
								SELECT status_os
								FROM tbl_os_status
								WHERE tbl_os.os = tbl_os_status.os
								AND status_os IN (98,99,101) ORDER BY data DESC
						)";
	$res_km = pg_query($con,$sql_status);

	if(pg_num_rows($res_km)>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		echo "<TD colspan='7' class='inicio'>&nbsp;Historico Atendimento Domicilio</TD>\n";
		echo "</TR>\n";

		for($x=0; $x<pg_num_rows($res_km); $x++){
			$status_os    = pg_fetch_result($res_km, $x, status_os);
			$observacao   = pg_fetch_result($res_km, $x, observacao);
			$data         = pg_fetch_result($res_km, $x, data);

			echo "<tr>";
				echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$status_os</font></td>";
				echo "<td class='justificativa'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$observacao</font></td>";
				echo "<td class='justificativa' align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$data</font></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>


<?
# adicionado por Fabio
# HD 13940 - Bosch
if ($login_fabrica==20) {
	$sql_status = "SELECT
					tbl_os_status.status_os                                    ,
					tbl_os_status.observacao                                   ,
					to_char(tbl_os_status.data, 'DD/MM/YYYY')   as data_status ,
					tbl_os_status.admin                                        ,
					tbl_status_os.descricao                                    ,
					tbl_admin.nome_completo AS nome                            ,
					tbl_admin.email                                            ,
					tbl_promotor_treinamento.nome  AS nome_promotor            ,
					tbl_promotor_treinamento.email AS email_promotor
				FROM tbl_os
				JOIN tbl_os_status USING(os)
				LEFT JOIN tbl_status_os USING(status_os)
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
				LEFT JOIN tbl_promotor_treinamento ON tbl_os.promotor_treinamento = tbl_promotor_treinamento.promotor_treinamento
				WHERE os = $os
				AND status_os IN (92,93,94)
				ORDER BY data ASC";

	$res_status = pg_query($con,$sql_status);
	$resultado = pg_num_rows($res_status);
	if ($resultado>0){
		echo "<BR>\n";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>\n";
		echo "<TR>\n";
		echo "<TD colspan='4' class='inicio'>&nbsp;Histórico</TD>\n";
		echo "</TR>\n";
		echo "<TR>\n";
		echo "<TD  class='titulo2' width='100px' align='center'><b>Data</b></TD>\n";
		echo "<TD  class='titulo2' width='170px' align='left'><b>Status</b></TD>\n";
		echo "<TD  class='titulo2' width='260px' align='left'><b>Observação</b></TD>\n";
		echo "<TD  class='titulo2' width='170px' align='left'><b>Promotor</b></TD>\n";
		echo "</TR>\n";
		for ($j=0;$j<$resultado;$j++){
			$status_os          = trim(pg_fetch_result($res_status,$j,status_os));
			$status_observacao  = trim(pg_fetch_result($res_status,$j,observacao));
			$status_data        = trim(pg_fetch_result($res_status,$j,data_status));
			$status_admin       = trim(pg_fetch_result($res_status,$j,admin));
			$descricao          = trim(pg_fetch_result($res_status,$j,descricao));
			$nome               = trim(strtoupper(pg_fetch_result($res_status,$j,nome)));
			$email              = trim(pg_fetch_result($res_status,$j,email));
			$nome_promotor      = trim(strtoupper(pg_fetch_result($res_status,$j,nome_promotor)));
			$email_promotor     = trim(pg_fetch_result($res_status,$j,email_promotor));

			echo "<TR>\n";
			echo "<TD  class='justificativa' align='center'><b>".$status_data."</b></TD>\n";
			echo "<TD  class='justificativa' align='left' nowrap>".$descricao."</TD>\n";
			echo "<TD  class='justificativa' align='left'>".$status_observacao."</TD>\n";
			echo "<TD  class='justificativa' align='left' nowrap>";
			if($status_os == 92) { // HD 55196
				echo "<acronym title='Nome: ".$nome_promotor." - \nEmail:".$email_promotor."'>".$nome_promotor;
			}else{
				echo "<acronym title='Nome: ".$nome." - \nEmail:".$email."'>".$nome;
			}
			echo "</TD>\n";
			echo "</TR>\n";
		}
		echo "</TABLE>\n";
	}
}
?>


<!--            Valores da OS           -->
<?
if ($login_fabrica == "20" or $login_fabrica=="50") {

	$sql = "SELECT mao_de_obra
			FROM tbl_produto_defeito_constatado
			WHERE produto = (	SELECT produto
								FROM tbl_os
								WHERE os = $os
			)
			AND defeito_constatado = (	SELECT defeito_constatado
										FROM tbl_os
										WHERE os = $os
			)";

	/* HD 19054 */
	if ($login_fabrica==50){
		$sql = "SELECT mao_de_obra
				FROM tbl_os
				WHERE os = $os
				AND fabrica = $login_fabrica";
	}
	$res = pg_query ($con,$sql);
	$mao_de_obra = 0 ;
	if (pg_num_rows ($res) == 1) {
		$mao_de_obra = pg_fetch_result ($res,0,0);
	}

	$sql = "SELECT tabela , desconto
			FROM tbl_posto_fabrica
			WHERE posto = $posto
			AND fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	$tabela = 0 ;
	$desconto = 0;

	if (pg_num_rows ($res) == 1) {
		$tabela = pg_fetch_result ($res,0,tabela);
		$desconto = pg_fetch_result ($res,0,desconto);
	}

	if (strlen ($desconto) == 0) $desconto = "0";

	if (strlen ($tabela) > 0) {

		$sql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item    USING (os_produto)
				JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
				WHERE tbl_os.os = $os";
		$res = pg_query ($con,$sql);
		$pecas = 0 ;


		if (pg_num_rows ($res) == 1) {
			$pecas = pg_fetch_result ($res,0,0);
		}
		$pecas = number_format ($pecas,2,",",".");
	}else{
		$pecas = "0";
	}
	echo "<br>";
	echo "<table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";
	if ($login_fabrica==50){
		echo "<td align='center' bgcolor='#E1EAF1'><b>Valor Deslocamento</b></td>";
	}else{
		echo "<td align='center' bgcolor='#E1EAF1'><b>Valor das Peças</b></td>";
	}
	echo "<td align='center' bgcolor='#E1EAF1'><b>Mão-de-Obra</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Total</b></td>";
	echo "</tr>";

	if($login_fabrica==20 ){
		$sql = "select pais from tbl_os join tbl_posto on tbl_os.posto = tbl_posto.posto where os = $os;";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) >0) {
			$sigla_pais = pg_fetch_result ($res,0,pais);
		}
	}


	if(strlen($sigla_pais)>0 and $sigla_pais <> "BR") {

		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$valor_liquido = pg_fetch_result ($res,0,pecas);
			$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
		}
		$sql = "select imposto_al  from tbl_posto_fabrica where posto=$posto and fabrica=$login_fabrica";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 1) {
			$imposto_al   = pg_fetch_result ($res,0,imposto_al);
			$imposto_al   = $imposto_al / 100;
			$acrescimo     = ($valor_liquido + $mao_de_obra) * $imposto_al;
		}
		$total = $valor_liquido + $mao_de_obra + $acrescimo;

		$total          = number_format ($total,2,",",".")         ;
		$mao_de_obra    = number_format ($mao_de_obra ,2,",",".")  ;
		$acrescimo      = number_format ($acrescimo ,2,",",".")    ;
		$valor_desconto = number_format ($valor_desconto,2,",",".");
		$valor_liquido  = number_format ($valor_liquido ,2,",",".");

		echo "<tr style='font-size: 12px ; color:#000000 '>";
		echo "<td align='right'>" ;
		echo "<font color='#333377'><b>$valor_liquido</b>" ;
		echo "</td>";
		echo "<td align='center'>$mao_de_obra</td>";
		if($sistema_lingua=='ES') echo "<td align='center'>+ $acrescimo</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
		echo "</tr>";
	}else{
		echo "<tr style='font-size: 12px ; color:#000000 '>";
		echo "<td align='right'>" ;
		if ($login_fabrica<>50){
			echo $pecas ;
		}
		if ($desconto > 0 and $pecas > 0) {
			$pecas = str_replace (".","",$pecas);
			$pecas = str_replace (",",".",$pecas);
			$sql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) == 1) {
				$produto = pg_fetch_result ($res,0,0);
			}
			//echo 'peca'.$pecas;
			if( $produto == '20567' ){
				$desconto = '0.2238';
				$valor_desconto = round ( (round ($pecas,2) * $desconto ) ,2);
				//echo $valor_desconto;
			}else{
				$valor_desconto = round ( (round ($pecas,2) * $desconto / 100) ,2);
			}
			$valor_liquido  = $pecas - $valor_desconto ;


		}

		/* HD 19054 */
		$valor_km = 0;
		if($login_fabrica == 50){
			$sql = "SELECT	tbl_os.mao_de_obra,
							tbl_os.qtde_km_calculada,
							tbl_os_extra.extrato
					FROM tbl_os
					LEFT JOIN tbl_os_extra USING(os)
					WHERE tbl_os.os = $os
					AND   tbl_os.fabrica = $login_fabrica";
			$res = pg_query ($con,$sql);

			if (pg_num_rows ($res) == 1) {
				$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
				$valor_km      = pg_fetch_result ($res,0,qtde_km_calculada);
				$extrato       = pg_fetch_result ($res,0,extrato);
			}
		}

		$total = $valor_liquido + $mao_de_obra + $valor_km;

		$total          = number_format ($total,2,",",".");
		$mao_de_obra    = number_format ($mao_de_obra ,2,",",".");
		$valor_desconto = number_format ($valor_desconto,2,",",".");
		$valor_liquido  = number_format ($valor_liquido ,2,",",".");
		$valor_km       = number_format ($valor_km ,2,",",".");

		if ($login_fabrica==50){
			echo "<font color='#333377'><b>" . $valor_km . "</b>" ;
		}else{
			echo "<br><font color='#773333'>Desc. ($desconto%) " . $valor_desconto ;
			echo "<br><font color='#333377'><b>" . $valor_liquido . "</b>" ;
		}
		echo "</td>";
		echo "<td align='center'>$mao_de_obra</td>";
		echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
		echo "</tr>";

		/* HD 19054 */
		if ($login_fabrica==50 and strlen($extrato)==0){
			echo "<tr style='font-size: 12px ; color:#000000 '>";
			echo "<td colspan='3'>";
			echo "<font color='#757575'>Valores sujeito a alteração até fechamento do extrato" ;
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "</table>";
	/*HD 9469 - Alteração no cálculo da BOSCH do Brasil*/
	if($login_fabrica == 20) {
		$sql = "select pecas,mao_de_obra  from tbl_os where os=$os";
		$resx = pg_query ($con,$sql);

		if (pg_num_rows ($resx) == 1) {
			$valor_liquido = pg_fetch_result ($resx,0,pecas);
			$mao_de_obra   = pg_fetch_result ($resx,0,mao_de_obra);
			$xtotal = $valor_liquido +$mao_de_obra;
			echo "<table  align='center' >";
			echo "<tr style='font-size: 12px ; color:#000000 '>";
			echo "<td align='center'>Preço de peça com custo administrativo: " ;
			echo "<font color='#333377'><b>$valor_liquido</b>" ;
			echo "</td>";
			echo "<td align='center'>Mão de Obra: $mao_de_obra</td>";
			echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$xtotal</b></td>";
			echo "</table>";
		}
	}

}




if ($login_fabrica == "30" ){
	echo "<table cellpadding='10' cellspacing='0' border='1' align='center' style='border-collapse: collapse' bordercolor='#485989'>";
	echo "<tr style='font-size: 12px ; color:#53607F ' >";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Valor Deslocamento</b></td>";
	if ($login_fabrica<>50){
		echo "<td align='center' bgcolor='#E1EAF1'><b>Valor das Peças</b></td>";
	}
	echo "<td align='center' bgcolor='#E1EAF1'><b>Mão-de-Obra</b></td>";
	echo "<td align='center' bgcolor='#E1EAF1'><b>Total</b></td>";
	echo "</tr>";

	echo "<tr style='font-size: 12px ; color:#000000 '>";


	$sql = "SELECT	tbl_os.mao_de_obra,
					tbl_os.qtde_km_calculada,
					tbl_os_extra.extrato,
					tbl_os.pecas
			FROM tbl_os
			LEFT JOIN tbl_os_extra USING(os)
			WHERE tbl_os.os = $os
			AND   tbl_os.fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) == 1) {
		$mao_de_obra   = pg_fetch_result ($res,0,mao_de_obra);
		$valor_km      = pg_fetch_result ($res,0,qtde_km_calculada);
		$extrato       = pg_fetch_result ($res,0,extrato);
		$valor_liquido = pg_fetch_result ($res,0,pecas);
	}


	$total = $valor_liquido + $mao_de_obra + $valor_km;

	$total          = number_format ($total,2,",",".");
	$mao_de_obra    = number_format ($mao_de_obra ,2,",",".");
	$valor_desconto = number_format ($valor_desconto,2,",",".");
	$valor_liquido  = number_format ($valor_liquido ,2,",",".");
	$valor_km       = number_format ($valor_km ,2,",",".");

	echo "<td align='center'>$valor_km </td>";
	echo "</td>";

	if ($login_fabrica<>50){
		echo "<td align='right'>" ;
		echo "<br><font color='#333377'><b>" . $valor_liquido . "</b>" ;
		echo "</td>";
	}

	echo "<td align='center'>$mao_de_obra</td>";
	echo "<td align='center' bgcolor='#E1EAF1'><font size='3' color='FF0000'><b>$total</b></td>";
	echo "</tr>";

	/* HD 19054 */
	if (($login_fabrica==50 OR $login_fabrica==30) and strlen($extrato)==0){
		echo "<tr style='font-size: 12px ; color:#000000 '>";
		echo "<td colspan='3'>";
		echo "<font color='#757575'>Valores sujeito a alteração até fechamento do extrato" ;
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}

?>

<?
//incluido por takashi 19/10/2007 - hd4536
//qdo OS é fechada com peças ainda pedente o posto tem que informar o motivo, o motivo a gente mostra aqui
if ($login_fabrica == 3){
	$sql = "SELECT obs_fechamento from tbl_os_extra where os=$os";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		$motivo_fechamento = pg_fetch_result($res,0,0);
		if(strlen($motivo_fechamento)>0){
			echo "<BR>";
			echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
			echo "<TR>";
			echo "<TD colspan=7 class='inicio'>&nbsp;Justificativa fechamento de OS com peça ainda pendente</TD>";
			echo "</TR>";
			echo "<TR>";
			echo "<TD class='conteudo'>$motivo_fechamento</TD>";
			echo "</TR>";
			echo "</TABLE>";
		}
	}
}
?>

<?
//Colocado por Fabio - HD 14344
//mostra o status da OS: acumulada ou resucasa
# 53003 - mostrar todas as ocorrências e o admin
if ($login_fabrica == 45){
	$sql = "SELECT	TO_CHAR(data,'DD/MM/YYYY') AS data,
					tbl_os_status.status_os    AS status_os,
					tbl_os_status.observacao   AS observacao,
					tbl_os_status.extrato,
					tbl_admin.nome_completo
			FROM tbl_os_extra
			JOIN tbl_os_status USING(os)
			LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_status.admin
			WHERE os = $os
			AND tbl_os_status.status_os IN (13,14)
			AND tbl_os_extra.extrato IS NULL
			ORDER BY tbl_os_status.data DESC";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		echo "<BR>";
		echo "<TABLE width='700px' border='0' cellspacing='1' cellpadding='2' align='center'  class='Tabela'>";
		echo "<TR>";
		echo "<TD colspan=7 class='inicio'>EXTRATO - STATUS DA OS</TD>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD class='titulo2' align='center'>DATA</TD>";
		echo "<TD class='titulo2' align='center'>ADMIN</TD>";
		echo "<TD class='titulo2' align='center'>EXTRATO</TD>";
		echo "<TD class='titulo2' align='center'>STATUS</TD>";
		echo "<TD class='titulo2' align='center'>OBSERVAÇÃO</TD>";
		echo "</TR>";

		for ($i=0; $i<pg_num_rows($res); $i++){
			$status_data       = pg_fetch_result($res,$i,data);
			$status_status_os  = pg_fetch_result($res,$i,status_os);
			$status_observacao = pg_fetch_result($res,$i,observacao);
			$zextrato          = pg_fetch_result($res,$i,extrato);
			$admin_nome        = pg_fetch_result($res,$i,nome_completo);

			if ($status_status_os==13){
				$status_status_os = "Recusada";
			}elseif ($status_status_os==14){
				$status_status_os = "Acumulada";
			}else{
				$status_status_os = "-";
			}

			echo "<TR>";
			echo "<TD class='conteudo' style='text-align: center'>$status_data</TD>";
			echo "<TD class='conteudo' style='text-align: center'>$admin_nome</TD>";
			echo "<TD class='conteudo' style='text-align: center'>$zextrato</TD>";
			echo "<TD class='conteudo' style='padding-left: 5px'>$status_status_os</TD>";
			echo "<TD class='conteudo' style='padding-left: 5px'>$status_observacao</TD>";
			echo "</TR>";
		}
		echo "</TABLE>";
	}
}
?>


<?
$sql="SELECT * from tbl_admin
		WHERE admin=$login_admin
		AND ( privilegios like '%info%' OR privilegios ='*' )";
$res=pg_query($con,$sql);
if(pg_num_rows($res) > 0){
	$suporte_tecnico ='t';
}

if( (($login_fabrica == 11 OR $login_fabrica == 10) AND $posto == '6359') OR ($login_fabrica == 3 AND $suporte_tecnico=='t') OR in_array($login_fabrica,array(45,40,51)) OR $login_fabrica >80){?>
<br>
	<TABLE width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
	<TR>
		<TD><font size='2' color='#FFFFFF'><center><b><? if ($login_fabrica ==3) echo "SUPORTE TÉCNICO"; else echo "INTERAGIR NA OS"; ?></b></center></font></TD>
	</TR>
	<TR><TD class='conteudo'>
	<FORM NAME='frm_interacao' METHOD=POST ACTION="<? echo "$PHP_SELF?os=$os"; ?>">
	<TABLE width='500' align='center' cellpadding='0' cellspacing='0'>
	<TR>
		<TD>
		<TABLE align='center' border='0' cellspacing='0' cellpadding='5'>
		<TR align='center'>
			<TD colspan='3'><INPUT TYPE="text" NAME="interacao_msg" size='60'></TD>
		</TR>
		<TR>
			<TD style='font-size: 12px' nowrap>
	<?
			echo "<font size='1'>Transfere para:</font><select size='1' name='admin_transfere'>";
			echo "<option selected></option>";
            echo "<option value=\"posto\"><em>Posto</em></option>";
			$sql = "SELECT admin, login FROM tbl_admin WHERE fabrica = $login_fabrica ORDER BY login";
			$res = pg_query ($con,$sql) ;

			for ($x = 0 ; $x < pg_num_rows ($res) ; $x++ ) {
				echo "<option ";
				if ($admin[$i] == pg_fetch_result ($res,$x,admin)) echo " selected ";
				echo " value='" . pg_fetch_result ($res,$x,admin) . "'>" ;
				echo pg_fetch_result ($res,$x,login) ;
				echo "</option>";
			}
			echo "</select>";
			echo "</TD>";
	?>
			<TD style='font-size: 12px'><INPUT TYPE="checkbox" NAME="resolvido" value='t'>&nbsp;Resolvido</TD>
			<TD style='font-size: 12px'><INPUT TYPE="checkbox" NAME="interno" value='t'>&nbsp;Interno</TD>
		</TR>
		<TR align='center'>
			<TD colspan='3'><input type="hidden" name="btn_acao" value="">
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_interacao.btn_acao.value == '' ) { document.frm_interacao.btn_acao.value='gravar_interacao' ; document.frm_interacao.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar Comentário" border='0' style="cursor:pointer;">
			</TD>
		</TR>
		</TABLE>
		</TD>
	</TR>
	</TABLE>
	</FORM>

	<?
	$sql = "SELECT os_interacao               ,
					to_char(data,'DD/MM/YYYY HH24:MI') as data,
					comentario                ,
					interno                   ,
					tbl_admin.nome_completo
				FROM tbl_os_interacao
				LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin
				WHERE os = $os
				ORDER BY os_interacao DESC;";
	$res = pg_query($con,$sql);
	$cont = 0;
	if(pg_num_rows($res) > 0){

		echo "<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#F3F5CF'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp;<b>Interação Interna</b></b></font></td>";
		echo "</tr>";
		echo "</table>";

		echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>";
		echo "<tr>";
		echo "<td class='titulo'><CENTER><b>Nº</b></CENTER></td>";
		echo "<td class='titulo'><CENTER><b>Data</b></CENTER></td>";
		echo "<td class='titulo'><CENTER><b>Mensagem</b></CENTER></td>";
		echo "<td class='titulo'><CENTER><b>Admin</b></CENTER></td>";
		echo "</tr>";
		echo "<tbody>";
		for($i=0;$i<pg_num_rows($res);$i++){
			$os_interacao     = pg_fetch_result($res,$i,os_interacao);
			$interacao_msg    = pg_fetch_result($res,$i,comentario);
			$interacao_interno= pg_fetch_result($res,$i,interno);
			$interacao_data   = pg_fetch_result($res,$i,data);
			$interacao_nome   = pg_fetch_result($res,$i,nome_completo);

			if($interacao_interno == 't'){
				$cor = "style='font-family: Arial; FONT-SIZE: 8pt; font-weight: bold; text-align: left; background: #F3F5CF;'";
			}else{
				$cor = "class='conteudo'";
			}

			$cont++;

			echo "<tr>";
			echo "<td width='25' $cor>"; echo $cont; echo "</td>";
			echo "<td width='90' $cor nowrap>$interacao_data</td>";
			echo "<td $cor>$interacao_msg</td>";
			echo "<td $cor nowrap>$interacao_nome</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</TABLE>";
		echo "<br>&nbsp;";
	}
	echo "</TD></TR></TABLE>";
}

if(in_array($login_fabrica, array(14, 43, 45, 66,1))) { // HD 51709
	$dir_nota_os = "../nf_digitalizada";
	$arquivo = "$dir_nota_os/$os.jpg";

	if(in_array($login_fabrica, array(14, 43, 45, 66,1))) { // HD 51709
		if(file_exists($arquivo)) {
			$arquivo2 = "js/jpie/nf_digital.php?os=$os";
			list($width,$height) = getimagesize($arquivo);
			$thumb_img = "$dir_nota_os/$os"."_thumb.jpg";
			if (!file_exists($thumb_img)) $thumb_img = "$arquivo' height='150"; // Depois fecha a aspa dupla
			echo "<div>";
			echo "<br>";
			echo "<h1>Imagem anexa</h1><br>";
			echo "Clique na foto para visualizar melhor<br>";
			echo "<a href=\"javascript: if(confirm('Deseja realmente excluir essa imagem?') == true) { window.location='excluir_foto_os.php?os=$os&excluir_foto=$os&qual=1'}\"  style='color:red'>excluir</a><br>";
			echo "<img src='$thumb_img' onclick=\"javascript:window.open('$arquivo2','Nota','status=yes,scrollbars=no,width=$width,height=$height');\">";
			echo "</div>";
		}
			$arquivo2 = "$dir_nota_os/$os-2.jpg";
			if(file_exists($arquivo2)){
				list($width,$height) = getimagesize($arquivo2);
				echo "<div>";
				echo "<br>";
				echo "<h1>Imagem anexa - 2</h1>";
				echo "Clique na foto para visualizar melhor<br>";
				echo "<a href=\"javascript: if(confirm('Deseja realmente excluir essa imagem?') == true) { window.location='excluir_foto_os.php?os=$os&excluir_foto=$os&qual=2'}\"  style='color:red'>excluir</a><br>";
				echo "<img src='$dir_nota_os/$os"."_thumb-2.jpg' onclick=\"javascript:window.open('$arquivo2','Nota','status=yes,scrollbars=no,width=$width,height=$height');\">";
				echo "</div>";
			}
	}
}

# hd 21896 - Francisco Ambrozio - inclusão do laudo técnico
if ($login_fabrica == 1 or $login_fabrica == 19){
	$sql = "SELECT tbl_laudo_tecnico_os.*
			FROM tbl_laudo_tecnico_os
			WHERE os = $os
			ORDER BY ordem;";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
?>
		<BR>
s		<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
		<TR>
		<TD colspan="9" class='inicio'>&nbsp;LAUDO TÉCNICO</TD>
<?
		echo "<tr>";
		echo "<td class='titulo' style='width: 30%'><CENTER>";
		if ($login_fabrica==19) echo "QUESTÃO";
		else                    echo "TÍTULO";
		echo "</CENTER></td>";
		echo "<td class='titulo' style='width: 10%'><CENTER>AFIRMATIVA</CENTER></td>";
		echo "<td class='titulo' style='width: 60%'><CENTER>OBSERVAÇÃO</CENTER></td>";
		echo "</tr>";

		for($i=0;$i<pg_num_rows($res);$i++){
			$laudo		 = pg_fetch_result($res,$i,laudo_tecnico_os);
			$titulo      = pg_fetch_result($res,$i,titulo);
			$afirmativa  = pg_fetch_result($res,$i,afirmativa);
			$observacao  = pg_fetch_result($res,$i,observacao);

			echo "<tr>";
			echo "<td class='conteudo' align='left' style='width: 30%'>&nbsp;$titulo</td>";
			if(strlen($afirmativa) > 0){
				echo "<td class='conteudo' style='width: 10%'><CENTER>"; if($afirmativa == 't'){ echo "Sim</CENTER></td>";} else { echo "Não</CENTER></td>";}
			}else{
				echo "<td class='conteudo' style='width: 10%'>&nbsp;</td>";
			}
			if(strlen($observacao) > 0){
				echo "<td class='conteudo' style='width: 60%'><CENTER>$observacao</CENTER></td>";
			}else{
				echo "<td class='conteudo' style='width: 60%'>&nbsp;</td>";
			}
			echo "</tr>";
		}
?>
</TR>
</TABLE>
<?
	}
}
# Finaliza inclusão do laudo técnico
?>

<!-- =========== FINALIZA TELA NOVA============== -->

<br>
<?

if ($login_fabrica == 66 or $login_fabrica == 43 or $login_fabrica == 14) {

		echo "<center><table>
				<tr class='Conteudo'>
					<td align='center'><a href='#'>
						<img src='imagens/btn_solicitar_coleta.gif'></a>
					</td>
				</tr>
			</table></center>";

		}

		?>

<?
if ($auditoria == 't') {

	$sql = "SELECT admin,login
		FROM tbl_os
		JOIN tbl_admin USING(admin)
		WHERE os = $os";

	$res1 = pg_query ($con,$sql);
	if (pg_num_rows ($res1) > 0){
		$sadmin   = trim(pg_fetch_result($res1,0,login));
		echo "OS digitada por <b>$sadmin</b>";
	}else{
		echo "OS digitada por <b>$posto_nome</b>";
	}

	echo "<form method='post' name='frm_auditoria' action='$PHP_SELF?auditoria=t'>";
	echo "<input type='hidden' name='os' value='$os'>";
	echo "<input type='hidden' name='sua_os' value='$sua_os'>";
	echo "<input type='hidden' name='posto' value='$posto'>";
	echo "<p>";

	if ($tem_pedido == 't') {
		echo "<input type='submit' name='btn_acao' value='Reprovar' disabled>";
	} else {
		echo "<input type='submit' name='btn_acao' value='Reprovar'>";
	}

	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type='submit' name='btn_acao' value='Analisar'>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type='submit' name='btn_acao' value='Aprovar'>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<input type='submit' name='btn_acao' value='Aprovar sem Mão de Obra'>";
	echo "<br>Motivo da Reprova:<br><textarea name='justificativa_reprova'ROWS='4' COLS='40' ></textarea>";
	echo "</form>";
}else{
?>
<div id='container'>
	<div id="contentleft2" style="width: 150px;">
		&nbsp;
	</div>

	<div id="contentleft2" style="width: 150px;">
		<?if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18 OR $tipo_atendimento == 35)){?>
			<a href="os_cadastro_troca_black.php"><img src="imagens/btn_lancanovaos.gif"></a>
		<?}else{?>
			<a href="os_cadastro.php"><img src="imagens/btn_lancanovaos.gif"></a>
		<?}?>
	</div>
	<div id="contentleft2" style="width: 150px;">
		<a href="os_print.php?os=<? echo $os ?>" target="_blank"><img src="imagens/btn_imprimir.gif"></a>
	</div>
</div>

<div id='container'>
	&nbsp;
</div>

<?
}

include "rodape.php"; ?>
