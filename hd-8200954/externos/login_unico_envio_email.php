<?php

header("Location: login_unico_envio_email_new.php");
exit;

$arr_host = explode('.', $_SERVER['HTTP_HOST']);
if ($arr_host[0] != "ww2") {
	/**
	 * @since HD 878899 - redireciona pro ww2 como solução [temporária] para os problemas de envio de email
	 */
	$uri = preg_replace("/~\w+\//", '','http://ww2.telecontrol.com.br' . $_SERVER['REQUEST_URI']);
	$uri = str_replace('/posvenda/', '/assist/', $uri);
	echo '<meta http-equiv="Refresh" content="0 ; url=' . $uri . '" />';
	//echo '<meta http-equiv="Refresh" content="0 ; url=http://ww2.telecontrol.com.br/assist/externos/login_unico_envio_email.php" />';
	exit;
}

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include ('../mlg/mlg_funciones_utf8.php');
include_once ('trad_site/fn_ttext.php');

$acao_unico  = trim($_POST['acao_unico']);
$cod_menssagem = "0";
if($_POST['acao_unico']=='ok') {
	$email = trim($_POST['email']);

	if(strlen($email)==0) $msg = "<label class='erro_campos_obrigatorios'>*&nbsp;Preencha o email</label>";

	if(strlen($msg) ==0) {

		$sql="SELECT  login_unico
			FROM  tbl_login_unico
			WHERE email ='$email'";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(pg_numrows($res) > 0 and strlen($msg_erro) == 0) {
			$login_unico  = pg_result ($res,0,0);

			$chave1=md5($login_unico);
			$email_origem  = "suporte@telecontrol.com.br";
			$email_destino = $email;
			$assunto       = utf8_decode("Assist - Login Único");
			$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
					NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

					<P align=justify>Parabéns pela sua nova conta de login único no Assist:$email, para <span style='color:#060;font-weight:bold'>validar</span> seu email, utilize o link abaixo:
					<br><a href='http://posvenda.telecontrol.com.br/assist/externos/login_unico.php?id=$login_unico&key1=$chave1'><u><b>Clique aqui para validar seu email</b></u></a>.</P>
					<br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>http://posvenda.telecontrol.com.br/assist/externos/login_unico.php?id=$login_unico&key1=$chave1<br>
					<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
					</P>";

			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			if (mail($email_destino, stripslashes($assunto), utf8_decode($corpo), "From: ".$email_origem." \n $body_top ")) {
				$msg = "<label class='email_sucesso'>Foi enviado um email para: ".$email_destino."</label>";
				$cod_menssagem = "1";
			}
		}else {
			$msg="<label class='erro_campos_obrigatorios'>*&nbsp;O E-MAIL DIGITADO ESTÁ INCORRETO!</label>";
			$cod_menssagem = "2";
		}
	}
	//echo $msg;
}
header("Content-Type:text/html;charset=utf-8");
include('topo_wordpress.php');
?>
<!--Mensagens de erro e status-->
<?php
	if($cod_menssagem == '1'){
		echo "<script>login_unico_envia_email('');</script>";
	}
?>
<link rel="stylesheet" href="css/login_unico_envio_email.css" type="text/css" media="screen" />
<div class="titulo_tela">
	<br>
	<h1><a href="javascript:void(0)" style="cursor:point;">Reenviar E-mail de Validação</a></h1>
</div>

<div class="div_top_principal">
	<table width="948" style="text-align: right;">
		<tr>
			<td>
				*Campos obrigat&oacute;rios.
			</td>
		<tr>
	</table>
</div>

<table width="948" class="barra_topo">
	<tr>
		<td>
			<div id="mensagem_envio" class='<?php echo $class_mensagem;?>'>&nbsp;<?php echo $msg;?></div>
		</td>
	<tr>
</table>
<table style="border:solid 1px #CCCCCC;width: 948px;height:300px;" class="caixa_conteudo">
	<tr>
		<td style="padding:1ex 2em">
			<div id="conteiner">
			<div id="conteudo">
				<form name="frm_os" id="frm_os" method="POST" action="login_unico_envio_email.php">
					<input type="hidden" name="acao_unico" value="ok">
					<fieldset>
						<p>Caso você não tenha recebido o e-mail de confirmação do login único, digite seu e-mail abaixo
							e clique em enviar.
						</p>
						<label class='email' title="Por favor, digitar o email cadastrado.">E-mail: *</label>
						<input name="email" id="email" type="text" size="40" value="">&nbsp;&nbsp;&nbsp;&nbsp;
						<button type="button" name="acao" value="Acessar" class="input_gravar" onclick="email_validacao('');">Enviar</button>
					</fieldset>
				</form>
				<p>Após clicar em enviar, você receberá um email como o abaixo, e para liberar o acesso você
				deverá clicar no link, ou se tiver problemas copiar(CRTL+C) o endereço e colar(CRTL+V) no seu navegador:</p>
				<p>&nbsp;</p>
				<div class="border_tc_8" id="ex">
					<b>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****</b>.
					<br><br>
					<p>Parabéns pela sua nova conta de login único no Assist:suporte@telecontrol.com.br, para
					<span style='color:#060;font-weight:bold;font-size:1em;padding-left:0'>validar</span> seu email, utilize o link abaixo:</p>
					<p style="font-weight:bold;text-decoration:underline">Clique aqui para validar seu email.</p>
					<br>
					Suporte Telecontrol Networking.<br>
					suporte@telecontrol.com.br<br>
				</div>
			</div>
			</div>
			</div>
		</td>
	</tr>
</table>
<div class="blank_footer">&nbsp;</div>

