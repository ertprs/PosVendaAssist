<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
}

// HD-7699020 
$urlN = $_SERVER['REQUEST_URI'];
$urlN = str_replace("esqueci_senha.php", "esqueci_senha_new.php", $urlN);

echo '<meta http-equiv="Refresh" content="0 ; url=' . $urlN . '" />';
exit;


$arr_host = explode('.', $_SERVER['HTTP_HOST']);
if ($arr_host[0] != "ww2" and $arr_host[0] != '192') {
	/**
	 * @since HD 878899 - redireciona pro ww2 como solução [temporária] para os problemas de envio de email
	 */
	$uri = preg_replace("/~\w+\//", '','http://ww2.telecontrol.com.br' . $_SERVER['REQUEST_URI']);
	$uri = str_replace('/posvenda/', '/assist/', $uri);
	if (!empty($_SERVER["QUERY_STRING"])) {
        $uri = str_replace("?{$_SERVER['QUERY_STRING']}", "", $uri);
    }
	echo '<meta http-equiv="Refresh" content="0 ; url=' . $uri . '" />';
	//echo '<meta http-equiv="Refresh" content="0 ; url=http://ww2.telecontrol.com.br/assist/externos/esqueci_senha.php" />';
	exit;
}

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

header("Content-Type: text/html;charset=UTF-8");

/*  26/11/2009  MLG - Convertendo a tradução ao novo padrão, corrigindo e acrescentando o que
					  está faltando.
					  Também passada para função a criação do e-mail.
					  Isto permite fazer igual o e-mail do posto, do admin e o de amostra,
					  sem repetir desnecessáriamente o texto em todos os idiomas
*/

//  Carrega a função de tradução

if (!function_exists('ttext')) {
	include 'trad_site/fn_ttext.php';
}

include_once '../class/email/mailer/class.phpmailer.php';
$mailer = new PHPMailer();

//  Array com a tradução
$a_rec_senha = array (
	"esqueci_senha" => array (
		"pt-br" => "Esqueci minha Senha",
		"es"    => "Olvidé mi Contraseña",
		"en"    => "Forgot my password",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"enviado_email" => array (
		"pt-br" => "Foi enviado um e-mail para",
		"es"    => "Se ha enviado un mensaje a",
		"en"    => "An e-mail has been sent to",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"nome" => array (
		"pt-br" => "Nome",
		"es"    => "Nombre",
		"en"    => "Name",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"email_incorreto" => array (
		"pt-br" => "O e-mail digitado está incorreto!",
		"es"    => "¡La dirección de correo es incorrecta!",
		"en"    => "The e-mail address is not correct!",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"titulo" => array (
		"pt-br" => "Recuperar senha de acesso",
		"es"    => "Recuperar datos de acceso",
		"en"    => "Retrieve access data",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"email_inexixtente_ou_nao_master" => array(
		"pt-br" => "Cadastro não encontrado ou este e-mail não é do login-unico MASTER, preencha corretamente seu e-mail.",
		"es"	=> "Registro no encontrado, o este correo electrónico no es del Login Único MASTER, escriba la dirección correcta.",
		"en"	=> "Record not found, or this is not the Unique Login MASTER's e-mail, please type in the correct e-mail address."
	),
	"digite_o_email" => array(
		"pt-br" => "Preencha o e-mail",
		"es"    => "Escriba la dirección de correo electrónico",
		"en"    => "Type in the e-mail address",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"selecione_o_fabricante" => array(
		"pt-br" => "Selecione o Fabricante",
		"es"    => "Seleccione el Fabricante",
		"en"    => "Select the Manufacturer",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"digite_o_email" => array(
		"pt-br" => "E-mail",
		"es"    => "Cibercorreo",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"esqueceu_senha" => array (
		"pt-br" => "Esqueceu sua senha?",
		"es"    => "¿Ha olvidado su clave?",
		"en"    => "Forgot your password?",
		"de"    => "Kennwort vergessen?",
		"zh-cn" => "忘记密码?",
		"zh-tw" => "忘記密碼?"
	),
	"preencha_email" => array (
		"pt-br" => "Preencha o e-mail que está cadastrado no sistema e selecione a fábrica que você esqueceu login e senha, para que seja enviado para seu e-mail.",
		"es"    => "Escriba su dirección de correo-e que consta en el registro y el fabricante cuyo usuario/clave ha perdido y le enviaremos un mensaje con los datos de acceso.",
		"en"    => "Fill out the e-maill that is registered in the system and select the factory you forgot the login and password and it will be sent to your e-mail.",
		"de"    => "Teilen Sie uns Ihr mail mit mit dem Sie bei uns angemeldet sind und die Firma für die Sie das den Usernamen und das Kennwort vergessen haben. Das Kennwort wird an Ihre Mailanschrift versandt.",
		"zh-cn" => "填写注册的电子信箱后选择所忘记的厂商用户名和密码，使系统可以发送E-MAIL到您的信箱。",
		"zh-tw" => "填寫註冊的電子信箱後選擇所忘記的廠商用戶名和密碼，使系統可以發送E-MAIL到您的信箱。"
	),
	"email" => array (
		"pt-br" => "E-mail",
		"es"    => "Cibercorreo",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"login_unico" => array (
		"pt-br" => "Login Único",
		"es"    => "Login Único",
		"de"    => "General-Login",
		"en"    => "Unique Login",
		"zh-cn" => "單一登录",
		"zh-tw" => "單一登录"
	),
	"fabrica" => array (
		"pt-br" => "Fábrica",
		"es"    => "",
		"en"    => "Brand",
		"de"    => "Firma",
		"zh-cn" => "公司",
		"zh-tw" => "公司"
	),
	"Selecionar" => array (
		"pt-br" => "",
		"es"    => "Seleccionar",
		"en"    => "Select",
		"de"    => "",
		"zh-cn" => "选择",
		"zh-tw" => "選擇"
	),
	"email_cadastro" => array (
		"pt-br" => "Por favor, digitar o email cadastrado.",
		"es"    => "Por favor, use la dirección electrónica de su usuario.",
		"en"    => "Please, type in the registered e-mail.",
		"de"    => "",
		"zh-cn" => "请填写注册的电子信箱",
		"zh-tw" => "請填寫註冊的電子信箱"
	),
	"nome_cadastro" => array (
		"pt-br" => "Por favor, digitar o nome cadastrado.",
		"es"    => "Por favor, use el nombre de su usuario.",
		"en"    => "Please, type in the registered name.",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"apos_enviar" => array (
		"pt-br" => "Após clicar <b>em Enviar</b>, você receberá um e-mail como o abaixo:",
		"es"    => "Tras pulsar en 'Enviar', recibirá un e-mail como éste:",
		"en"    => "Next click Send, you will receive an e-mail like the one below:",
		"de"    => "Nach einem Klick auf “Senden” erhalten Sie folgende mail:",
		"zh-cn" => "在按下寄出之后，您会收到内容如下的E-MAIL:",
		"zh-tw" => "在按下寄出之後，您會收到內容如下的E-MAIL:"
	),
	"Usuario" => array (
		"pt-br" => "Usuário",
		"es"    => "Usuario",
		"en"    => "User",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"Nome_Da_Fabrica" => array (
		"pt-br" => "NOME DA FÁBRICA",
		"es"    => "NOMBRE DE LA FÁBRICA",
		"en"    => "so and so Factory",
		"de"    => "Firma Beispiel",
		"zh-cn" => "",
		"zh-tw" => ""
	)
);

//  função que cria o e-mail para enviar, com texto diferenciado dependendo do idioma
function email_senha($tipo,$nome, $f_nome, $login, $senha, $idioma = 'pt-br') {

	if ($demo = strpos($tipo, 'demo')) {
		$tipo = str_replace('_demo', '', $tipo);
	}

	//if ($demo) echo("Mostrar DEMO <u>$tipo</u> en $idioma");

	if ($tipo == 'normal'){

		switch ($idioma) {
	        case "es":
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Nota: Este mensaje es automático. ".
						"**** POR FAVOR NO RESPONDA ESTE MENSAJE ****".
						"</p>\n".
						"<p>Apreciado/a $nome,</p>\n".
						"<p>Se nos ha solicitado el envío de los datos de acceso (usuario y clave) para acceder a la fábrica $f_nome:\n".
						"<br>\n".
						"Usuario: <b>$login</b><br>\n".
						"Clave: <b>$senha</b>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>Para accceder al sistema, puede usar este enlace:<br>\n";
	    	break;
	        case "en":
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Note: This e-mail is sent automatically. ".
						"**** PLEASE DO NOT ANSWER THIS MESSAGE ****".
						"</p>\n".
						"<p>Dear $nome,</p>\n".
						"<p>The following login and password has been requested to access the system for {$f_nome}:\n".
						"<br>\n".
						"User: <b>$login</b><br>\n".
						"Password: <b>$senha</b>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>To access the system, use the link below:<br>\n";
	    	break;
	        case "de":
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Anm.: Diese mail wurde automatisch erstellt. ".
						"**** BITTE NICHT ANTWORTEN ****".
						"</p>\n".
						"<p>Sehr geehrte $nome,</p>\n".
						"<p>Wir bestätgen den Eingang der Beantragung von Login und Kennwort zwecks Zugang zum System der $f_nome GmbH:\n".
						"<br>\n".
						"Login: <b>$login</b><br>\n".
						"Kennwort: <b>$senha</b>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>Zum Zugang bitte untenstehenden Link anklicken:<br>\n";
	    	break;
			default:
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Nota: Este e-mail é gerado automaticamente. ".
						"**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****".
						"</p>\n".
						"<p>Caro {$nome},</p>\n".
						"<p>Foi solicitado o login e a senha para acessar o sistema na fábrica ${f_nome}:\n".
						"<br>\n".
						"Login: <b>$login</b><br>\n".
						"Senha: <b>$senha</b>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>Para acessar o sistema use o link abaixo:<br>\n";
		}

		$body.= "<a href='http://www.telecontrol.com.br'>http://www.telecontrol.com.br</a>\n".
			"<br><br>\n".
			"Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
			"</p>";


	}elseif($tipo == 'login_unico'){
		
		switch ($idioma) {
	        case "es":
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Nota: Este mensaje es automático. ".
						"**** POR FAVOR NO RESPONDA ESTE MENSAJE ****".
						"</p>\n".
						"<p>Apreciado/a $nome,</p>\n".
						"<p>Se ha solicitado la recuperación de la contraseña de su Login Único:\n".
						"<br>\n".
						"Nombre: <b>$login</b><br>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>Para recuperar su contraseña, abra este enlace:<br>\n";
	    	break;
	        case "en":
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Note: This e-mail is sent automatically. ".
						"**** PLEASE DO NOT ANSWER THIS MESSAGE ****".
						"</p>\n".
						"<p>Dear $nome,</p>\n".
						"<p>You asked for password recovery for your Unique Login:\n".
						"<br>\n".
						"User: <b>$nome</b><br>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>To recover your password use the link below:<br>\n";
	    	break;
	        case "de":
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Anm.: Diese mail wurde automatisch erstellt. ".
						"**** BITTE NICHT ANTWORTEN ****".
						"</p>\n".
						"<p>Sehr geehrte $nome,</p>\n".
						"<p>Wir bestätgen den Eingang der Beantragung von Login und Kennwort zwecks Zugang zum System der $f_nome GmbH:\n".
						"<br>\n".
						"Name: <b>$login</b><br>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>Zum Zugang bitte untenstehenden Link anklicken:<br>\n";
	    	break;
			default:
				$body = "<p style='text-align:left;font-weight:bold'>".
						"Nota: Este e-mail é gerado automaticamente. ".
						"**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****".
						"</p>\n".
						"<p>Caro {$nome},</p>\n".
						"<p>Foi solicitada a recuperação de senha para o seu Login Único:\n".
						"<br>\n".
						"Nome: <b>$nome</b><br>\n".
						"Email: <b>$login</b><br>\n".
						"</p>\n".
						"<p>&nbsp;</p>\n".
						"<p>Para recuperar sua senha use o link abaixo:<br>\n";
		}

		 $body.= "<a href='http://posvenda.telecontrol.com.br/assist/externos/recupera_senha_lu.php?lu=$f_nome'>http://posvenda.telecontrol.com.br/assist/externos/recupera_senha_lu.php?lu=$f_nome</a>\n".
		 		"<br><br>\n".
		 		"Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
		 		"</p>";


	}

	//Tira os links reais quando solicitado e-mail de demonstração
	if ($demo) {
		$body = preg_replace('/href=[\'"]["\'].+\s/', "href='javascript:void(0);' ", $body);
	}

	return $body;

}

$html_titulo = ttext($a_rec_senha, "titulo");
$body_options = "onload='document.frm_es.email.focus() ;' ";
include "topo_wordpress.php"; 


if( $_POST['btn_acao']=='Enviar') {
	
	$email       = strtolower(trim($_POST['email']));
	$fabrica     = trim($_POST['fabrica']);
	$login_unico = trim($_POST['login_unico']);

	if(strlen($email)==0)	$msg_erro = ttext($a_rec_senha, "digite_o_email");
	if (empty($login_unico)){
		if(strlen($fabrica)==0)	$msg_erro .= ttext($a_rec_senha, "selecione_o_fabircante");
	}
	if (!empty($login_unico) and $login_unico == 't'){
		
		if (empty($msg_erro)){

			$sql = "SELECT login_unico,nome,email FROM tbl_login_unico WHERE email = '$email' AND ativo AND master";
			$res = pg_query($con,$sql);
			
			if (pg_num_rows($res) == 0){
				$msg_erro .= ttext($a_rec_senha, "email_inexixtente_ou_nao_master");
			}else{
				$lu       = pg_fetch_result($res, 0, 0);
				$lu_nome  = pg_fetch_result($res, 0, 1);
				$lu_email = pg_fetch_result($res, 0, 2);

				$lu_md5 = md5($lu);
			}

		}

	}

	if(strlen($msg_erro) ==0) {
		
		$tipo_email = ($lu) ? 'login_unico' : 'normal';

		/*  08/10/2009  MLG - Ao fazer a condição com '=' no WHERE, não pegava e-mails com espaços antes ou depois,
		 *              Agora compara com TRIM, para não ter esse problema, e o valor recuperado em 'posto_email'
		 *              também é TRIM(campo).
		*/
		$body_top = "--Message-Boundary\n";
		$body_top.= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top.= "Content-transfer-encoding: 7BIT\n";
		$body_top.= "Content-description: Mail message body\n\n";

		if ($tipo_email == 'normal'){

			$sql = "SELECT  tbl_posto.nome                  AS posto_nome  ,
					        tbl_posto_fabrica.codigo_posto  AS posto_codigo,
					        CASE WHEN contato_email IS NULL OR LENGTH(contato_email) = 0
								 	  AND tbl_posto.email IS NOT NULL
								 THEN tbl_posto.email
								 ELSE contato_email
					        END                             AS posto_email ,
					        tbl_posto_fabrica.senha         AS posto_senha ,
					        tbl_fabrica.nome                AS fabrica_nome
					    FROM  tbl_posto
					    JOIN  tbl_posto_fabrica USING (posto)
					    JOIN  tbl_fabrica       USING (fabrica)
					    WHERE (LOWER(TRIM(contato_email))       = '$email'
					       OR  LOWER(TRIM(tbl_posto.email))     = '$email')
						  AND  tbl_posto_fabrica.credenciamento  IN ('CREDENCIADO','EM DESCREDENCIAMENTO')
					      AND  tbl_posto_fabrica.fabrica = $fabrica LIMIT 1";
			$res = pg_query($con, $sql);

			if (!is_resource($res)) $msg_erro = pg_last_error($con);

			//die('Qtde.: "' . pg_last_error($con) .  '" / ' . pg_num_rows($res));

			if(pg_num_rows($res) > 0 and strlen($msg_erro) == 0) {

				extract(pg_fetch_assoc($res, 0));

				$email_origem  = "suporte@telecontrol.com.br";
				$email_destino = $posto_email;
				$assunto       = "Telecontrol - ".ttext($a_rec_senha, "esqueci_senha");
				$corpo         = email_senha($tipo_email,$posto_nome, $fabrica_nome, $posto_codigo, $posto_senha, $cook_login);

				$mailer->IsSMTP();
				$mailer->IsHTML();
				$mailer->AddAddress($email);
				$mailer->Subject = $assunto;
				$mailer->Body = $corpo;

				if ($mailer->Send()){
					$msg = ttext($a_rec_senha, "enviado_email").": $email";
					$sucesso = "1";
				}else{
					$msg_erro.= ttext($a_rec_senha, "email_incorreto");
				}

			}else {

				$fabrica = trim($_POST["fabrica"]);
				$email   = trim($_POST["email"]);

				$sql = "SELECT  login,
								senha,
								TRIM(email) AS email,
								nome
						FROM tbl_admin
						JOIN tbl_fabrica USING(fabrica)
						WHERE TRIM(email) = '$email'
						AND fabrica = $fabrica";

				$res = pg_query($con,$sql);
				$msg_erro.= pg_last_error($con);

				if(pg_num_rows($res) > 0 and strlen($msg_erro) == 0) {

					$login         = pg_fetch_result($res, 0, 'login');
					$email         = pg_fetch_result($res, 0, 'email');
					$senha         = pg_fetch_result($res, 0, 'senha');
					$fabrica_nome  = pg_fetch_result($res, 0, 'nome');
				
					$email_origem  = "suporte@telecontrol.com.br";
					$email_destino = $email;
					$assunto       = "Telecontrol - ".ttext($a_rec_senha, "esqueci_senha");
					$corpo         = email_senha($tipo_email,ttext($a_rec_senha,"usuario"), $fabrica_nome, $login, $senha, $cook_login);

					$mailer->IsSMTP();
					$mailer->IsHTML();
					$mailer->AddAddress($email);
					$mailer->Subject = $assunto;
					$mailer->Body = $corpo;

					if ($mailer->Send()){
						$msg = ttext($a_rec_senha, "enviado_email").": $email";
						$sucesso = "1";
					}else{
						$msg_erro.= ttext($a_rec_senha, "email_incorreto");
					}

				}else{

					$msg_erro.= ttext($a_rec_senha, "email_incorreto");

				}

			}

		}elseif($tipo_email == 'login_unico'){ //ENVIO DE EMAIL PARA QUANDO O POSTO DESEJA RECUPERAR O LOGIN UNICO

			$mailer->IsSMTP();
			$mailer->IsHTML();
			$mailer->AddAddress($lu_email);
			$mailer->AddBCC("suporte@telecontrol.com.br");
			$subject = "Telecontrol - ".ttext($a_rec_senha, "esqueci_senha");
			$mailer->Subject = $subject;

			$body_text = email_senha($tipo_email,$lu_nome, $lu_md5, $lu_email, " ", $cook_login);
			$mailer->Body = $body_text;

			if ($mailer->Send()){
				$msg = ttext($a_rec_senha, "enviado_email").": $lu_email";
				$sucesso = "1";
			}else{
				$msg_erro.= ttext($a_rec_senha, "email_incorreto");
			}

		}

	}
	
}

?>
<link rel="stylesheet" href="css/login_unico_envio_email.css" type="text/css" media="screen">
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script type="text/javascript">
	
	$(function() {

		if ($("#login_unico").is(":checked")){
			$("#fabrica_p").hide();
			$("#exlu").show();
			$("#ex").hide();
		}else{
			$("#fabrica_p").show();
			$("#ex").show();
			$("#exlu").hide();
		}

		$("#login_unico").click(function(){
			
			if ($(this).is(":checked")){
				$("#fabrica_p").slideUp("fast");
				$("#exlu").show();
				$("#ex").hide();

			}else{
				
			$("#fabrica_p").slideDown("fast");
			$("#ex").show();
			$("#exlu").hide();
			}

		});

	});

</script>

<div class="titulo_tela">

	<br><h1><a href="javascript:void(0)" style="cursor:point;"><?=ttext($a_rec_senha,"esqueceu_senha")?></a></h1>

</div>

<div class="div_top_principal">

	<table width="950" style="text-align: right;">
		<tr>
			<td>
				*Campos obrigat&oacute;rios.
			</td>
		<tr>	
	</table>

</div>

<?php 

if ($msg != '') { 
	$class_mensagem = "email_sucesso";
	$mensagem = $msg;
	echo "<script>limpa_campo_esqueci_senha('');</script>";
}

if ($msg_erro != '') { 
	$class_mensagem = "erro_campos_obrigatorios";
	$mensagem = $msg_erro;
}

?>
<table width="948" class="barra_topo">
	<tr>
		<td>
			<div id="mensagem_envio" class='<?php echo $class_mensagem;?>'><?php echo $mensagem;?></div>
		</td>
	<tr>	
</table>
<table style="border:solid 1px #CCCCCC;width: 948px;height:300px;" class="caixa_conteudo">
	<tr>
		<td style="padding: 1ex 2em">
<div id='conteiner' style="min-height:480px">
  <div id='conteudo'>

	<form name='frm_es' id='frm_es' method='post' action='<?=$PHP_SELF?>'>
	  <input type='hidden' name='btn_acao' value='Enviar' >

		<p><?=ttext($a_rec_senha,"preencha_email");?></p>
		<p>&nbsp;</p>

		<p>
			<label class='email' for='email'><?=ttext($a_rec_senha,"email")?>: *</label>
			<input name ="email" id='email' type="text" size="30" maxlength='50' value="<?=$email ?>" />
			<span><?=ttext($a_rec_senha,"email_cadastro")?></span>
		</p>

		<p id="nome_p" style="display:none" >
			<label class='nome' for='nome'><?=ttext($a_rec_senha,"nome")?>: *</label>
			<input name ="nome" id='nome' type="text" size="30" maxlength='50' value="<?=$nome ?>" />
			<span><?=ttext($a_rec_senha,"nome_cadastro")?></span>
		</p>

		<p id="fabrica_p">
			<label class='fabrica' for='fabrica'><?=ttext($a_rec_senha,"fabrica")?>: *</label>
			<select name='fabrica' id='fabrica'>
				<option value=''><?=ttext($a_rec_senha,"Selecionar")?></option>
			<?
				$sql = "SELECT fabrica,nome
					      FROM tbl_fabrica
						 WHERE ativo_fabrica IS TRUE 
						   AND fabrica       NOT IN(10,63,92,93,109)
						   AND nome !~* 'pedidoweb'
						 ORDER BY nome";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					for($x = 0; $x < pg_num_rows($res);$x++) {
						$aux_fabrica = pg_fetch_result($res,$x,fabrica);
						$aux_nome    = pg_fetch_result($res,$x,nome);
						echo "<option value='$aux_fabrica' ";
						if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			?>
			</select>
		</p>

		<p>
			<?php 
			$checked = ($_POST['login_unico'] and empty($sucesso)) ? "CHECKED" : "" ;
			?>
			<label for="login_unico" class='login_unico'> <?=ttext($a_rec_senha,"login_unico") ?>? </label>
			<input type="checkbox" name="login_unico" id="login_unico" value="t" <?=$checked?> > 
		</p>

		<br />
			<p>
				<label class="login_fabricante">&nbsp;</label>
				<!--<input type='submit' name='btn_acao' value='Enviar' />-->
				<button type="button" name="btn_acao" value="Enviar" class='input_gravar' onclick="verifica_esqueceu_senha('');">Enviar</button>
			</p>
			<br />
			<p><?=ttext($a_rec_senha,"apos_enviar")?>
				<div class="border_tc_8" id="ex">
				<?
				$nome_mostra	= ttext($a_rec_senha,"Usuario");
				$fabrica_mostra	= ttext($a_rec_senha,"Nome_Da_Fabrica");
				echo email_senha('normal_demo',$nome_mostra, $fabrica_mostra, "1234", "xx123xx", $cook_idioma);
				?>
				</div>

				<div class="border_tc_8" style="display:none" id="exlu">
				<?
				$nome_mostra	= ttext($a_rec_senha,"Usuario");
				$fabrica_mostra	= ttext($a_rec_senha,"Nome_Da_Fabrica");
				echo email_senha('login_unico_demo','USUARIO', '12341231', " ", " ", $cook_idioma);
				?>
				</div>
			</p>
		</div>
	  </form>
	</div>
  </div>
		</td>
	</tr>
</table>
<div class="blank_footer">&nbsp;</div>

