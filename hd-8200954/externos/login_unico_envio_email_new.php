<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

define ('APP_URL',  '//' . $_SERVER["HTTP_HOST"] .
    preg_replace(
        '#/(admin|admin_es|admin_callcenter|helpdesk|externos)#', '',
        dirname($_SERVER['SCRIPT_NAME'])
    ) . DIRECTORY_SEPARATOR
);

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
}

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include ('../mlg/mlg_funciones_utf8.php');
include_once ('trad_site/fn_ttext.php');

$acao_unico  = trim($_POST['acao_unico']);
$cod_menssagem = "0";
if ($_POST['acao_unico']=='ok') {
	$email = trim($_POST['email']);
	if(strlen($email)==0) $msg = "Preencha o email";

	if(strlen($msg) ==0) {

		include_once '../class/communicator.class.php';

		$sql="SELECT  login_unico
			FROM  tbl_login_unico
			WHERE email ='$email'";
		$res = pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(pg_numrows($res) > 0 and strlen($msg_erro) == 0) {
			$login_unico   = pg_result ($res,0,0);

			$chave1         = md5($login_unico);
            $link_validacao = 'https:' . APP_URL . '/externos/login_unico_new.php' . "?id=$lu&key1=$chave1";
			$email_origem   = "suporte@telecontrol.com.br";
			$email_destino  = $email;
			$assunto        = utf8_decode("Assist - Login Único");
			$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
					NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

					<P align=justify>Parabéns pela sua nova conta de login único no Assist:$email, para <span style='color:#060;font-weight:bold'>validar</span> seu email, utilize o link abaixo:
					<br><a href='$link_validacao'><u><b>Clique aqui para validar seu email</b></u></a>.</P>
					<br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>$link_validacao<br>
					<P align=justify>Suporte Telecontrol Networking.<BR>suporte@telecontrol.com.br
					</P>";
					//<br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>http://posvenda.telecontrol.com.br/assist/externos/login_unico.php?id=$login_unico&key1=$chave1<br>

			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			$mailer = new TcComm("noreply@tc");
			$res = $mailer->sendMail(
					$email_destino,
					stripslashes($assunto),
					$corpo,
					'noreply@telecontrol.com.br'
			         );

			if($res === true){
				$msg = "Foi enviado um email para: ".$email_destino."";
				$cod_menssagem = "1";
			}
		}else {
			$msg="O E-MAIL DIGITADO ESTÁ INCORRETO!";
			$cod_menssagem = "2";
		}
	}
	//echo $msg;
}
header("Content-Type:text/html;charset=iso-8859-1");
include('site_estatico/header.php');
?>
<script>$('body').addClass('pg log-page')</script>
<!--Mensagens de erro e status-->
<?php
	if($cod_menssagem == '1'){
		$msg_sucess = $msg;
		echo "<script>login_unico_envia_email('');</script>";
?>
		<script type="text/javascript">
		$(function(){
			$('.alert.success').show();

			setTimeout(function(){
				$("#msg_success").hide();

			}, 6000);

		});
		</script>
<?php
	}
?>

<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Reenviar E-mail de Validação</h2></div>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">

	<div class="alerts">
		<div class="alert success" id="msg_success"><i class="fa fa-check-circle"></i><?php echo $msg_sucess;?></div>
		<div class="alert error" id="mensagem_envio"><i class="fa fa-exclamation-circle"></i><?php echo $msg;?></div>
	</div>

	<div class="desc">
		<h3>
		Caso você não tenha recebido o e-mail de confirmação do login único, digite seu email abaixo e clique em enviar.
		</h3>
	</div>
	<div class="sep"></div>
	<form name="frm_os" id="frm_os" method="POST" action="login_unico_envio_email_new.php">
		<input type="hidden" name="acao_unico" value="ok">
		<input name="email" id="email" type="text" value="" placeholder="Email">
		<button type="button" name="acao" value="Acessar" class="input_gravar" onclick="email_validacao('');"><i class="fa fa-lock"></i>Enviar</button>
		<br><br>
		<h4>Após clicar em enviar, você receberá um email como o abaixo, e para liberar o acesso você deverá clicar no link, ou se tiver problemas copiar (CRTL+C) o endereço e colar (CRTL+V) no seu navegador:</h4>
		<div class="mail-preview">
			<p>
			<strong>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</strong>
			<br><br>
			Parabéns pela sua nova conta de login único no Assist:suporte@telecontrol.com.br, para <strong>validar</strong> seu email, utilize o link abaixo:
			<br>
			<strong>Clique aqui para validar seu email.</strong>
			<br><br>
			Suporte Telecontrol Networking.
			<br>
			suporte@telecontrol.com.br
			</p>
		</div>
	</form>
	</div>
</section>
<?php include('site_estatico/footer.php') ?>



