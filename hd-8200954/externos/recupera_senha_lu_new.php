<?php

$arr_host = explode('.', $_SERVER['HTTP_HOST']);
// voltar deposi if ($arr_host[0] != "ww2") {
if ($arr_host[0] != "ww2") {
	/**
	 * @since HD 878899 - redireciona pro ww2 como solução [temporária] para os problemas de envio de email
	 */
	$uri = preg_replace("/~\w+\//", '','http://ww2.telecontrol.com.br' . $_SERVER['REQUEST_URI']);
	$uri = str_replace('/posvenda/', '/assist/', $uri);
	echo '<meta http-equiv="Refresh" content="0 ; url=' . $uri . '" />';
	//echo '<meta http-equiv="Refresh" content="0 ; url=http://ww2.telecontrol.com.br/assist/externos/recupera_senha_lu.php" />';
	exit;
}

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

header("Content-Type: text/html;charset=UTF-8");

$lu = (isset($_GET['lu'])) ? $_GET['lu'] : '' ;

if (!function_exists('ttext')) {
	include 'trad_site/fn_ttext.php';
}



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
	"nova_senha" => array (
		"pt-br" => "Nova Senha",
		"es"    => "Nueva Contraseña",
		"en"    => "New Password:",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"conf_nova_senha" => array (
		"pt-br" => "Confirme a Senha",
		"es"    => "Confirmar Contraseña",
		"en"    => "Confirm new Password",
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
	"esqueceu_senha" => array (
		"pt-br" => "Esqueceu sua senha?",
		"es"    => "¿Ha olvidado su clave?",
		"en"    => "Forgot your password?",
		"de"    => "Kennwort vergessen?",
		"zh-cn" => "忘记密码?",
		"zh-tw" => "忘記密碼?"
	),
	"recupera_senha" => array (
		"pt-br" => "Recupere sua senha",
		"es"    => "Recuperar su contraseña",
		"en"    => "Recover your password",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"preencha_email" => array (
		"pt-br" => "Preencha o e-mail que está cadastrado no sistema e selecione a fábrica que você esqueceu login e senha, para que seja enviado para seu e-mail.",
		"es"    => "Escriba su dirección de correo-e que consta en el registro y el fabricante cuyo usuario/clave ha perdido y le enviaremos un mensaje con los datos de acceso.",
		"en"    => "Fill out the e-maill that is registered in the system and select the factory you forgot the login and password and it will be sent to your e-mail.",
		"de"    => "Teilen Sie uns Ihr mail mit mit dem Sie bei uns angemeldet sind und die Firma für die Sie das den Usernamen und das Kennwort vergessen haben. Das Kennwort wird an Ihre Mailanschrift versandt.",
		"zh-cn" => "填写注册的电子信箱后选择所忘记的厂商用户名和密码，使系统可以发送E-MAIL到您的信箱。",
		"zh-tw" => "填寫註冊的電子信箱後選擇所忘記的廠商用戶名和密碼，使系統可以發送E-MAIL到您的信箱。"
	),
	"preencha_nova_senha" => array (
		"pt-br" => "Preencha a nova senha que deseja para o seu login.",
		"es"    => "Escriba la nueva contraseña para su usuario.",
		"en"    => "Fill the new password you want for your login.",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"alterado_com_sucesso" => array (
		"pt-br" => "Senha alterada com sucesso.",
		"es"    => "Contraseña actualizada correctamente.",
		"en"    => "Password changed successfully.",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"faca_seu_login_com_a_nova_senha" => array (
		"pt-br" => "Faça seu login com a nova senha para acessar o Sistema de Pós-Venda",
		"es"    => "Ya puede entrar con su nueva contraseña para acceder al Sistema de Pós-Venda",
		"en"    => "Login with new password in Telecontrol Pós-Venda system.",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"erro_senhas_nao_batem" => array (
		"pt-br" => "Campos de 'Nova Senha' e 'Confirmação de nova senha' estão diferentes.",
		"es"    => "Los campos 'Contraseña' y 'Confirmar Contraseña' no coinciden.",
		"en"    => "The fields 'Password' and 'Confirm Password' do not match.",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"email" => array (
		"pt-br" => "E-mail",
		"es"    => "Correo",
		"en"    => "",
		"de"    => "",
		"zh-cn" => "",
		"zh-tw" => ""
	),
	"login_unico" => array (
		"pt-br" => "Login Único",
		"es"    => "Login Único",
		"en"    => "Unique Login",
		"de"    => "General-Login",
		"zh-cn" => "單一登录",
		"zh-tw" => "單一登录"
	),
	"fabrica" => array (
		"pt-br" => "Fábrica",
		"es"    => "Fábrica",
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
		"es"    => "Por favor, use el correo-e de su usuario.",
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
	),
	"err_conf_pass" => array (
 		"pt-br" => "Confirme sua senha.",
 		"es"    => "Confirme su clave.",
 		"en"    => "Confirm your Password",
 		"de"    => "",
 		"zh-cn" => "确认密码",
 		"zh-tw" => "確認密碼"
 	),
	"err_senha_invalidos" => array (
		"pt-br" => "Senha inválida, a senha contém caracteres inválidos.",
		"es"    => "Clave inválida, la clave debe tener al menos 2 letras.",
		"en"    => "Password invalid, it must have at least 2 letters.",
		"de"    => "",
  		"zh-cn" => "密码设置错误",
  		"zh-tw" => "密碼設置錯誤"
	),
	"err_senha_2_letras" => array (
  		"pt-br" => "Senha inválida, a senha deve ter pelo menos 2 letras.",
  		"es"    => "Clave inválida, la clave debe tener al menos 2 letras.",
  		"en"    => "Password invalid, it must have at least 2 letters.",
  		"de"    => "",
  		"zh-cn" => "密码设置错误，最少要有两个英文字母",
  		"zh-tw" => "密碼設置錯誤，最少要有兩個英文字母"
  	),
	"err_senha_2_nums" => array (
		"pt-br" => "Senha inválida, a senha deve ter pelo menos 2 números.",
		"es"    => "Clave inválida, la clave debe tener al menos 2 números.",
		"en"    => "Password invalid, it must have at least 2 numbers.",
		"de"    => "",
  		"zh-cn" => "密码设置错误，最少要有两个数字",
  		"zh-tw" => "密碼設置錯誤，最少要有兩個數字"
	),
	"err_senha_6_min" => array (
  		"pt-br" => "A senha deve conter um mínimo de 6 caracteres.",
  		"es"    => "La clave debe ser como mínimo de 6 caracteres.",
  		"en"    => "Password invalid, it must be at least 6 characters long.",
  		"de"    => "",
  		"zh-cn" => "密码设置错误，最少要有六个字节",
  		"zh-tw" => "密碼設置錯誤，最少要有六個字節"
  	),
	"err_senha_vazia" => array (
		"pt-br" => "Digite uma senha",
		"es"    => "La clave es obligatoria",
		"en"    => "Password is required",
		"de"    => "",
		"zh-cn" => "输入一个密码",
		"zh-tw" => "輸入一個密碼"
	),
);

if ($_POST['btn_acao'] == 'Enviar'){

	$nova_senha      = $_POST['nova_senha'];
	$conf_nova_senha = $_POST['conf_nova_senha'];
	//
	//Wellington 31/08/2006 - MINIMO 6 CARACTERES SENDO UM MINIMO DE 2 LETRAS E MINIMO DE 2 NUMEROS
	if (strlen($nova_senha) > 0) {
		if (strlen($nova_senha) > 5 and strlen($nova_senha) < 11) {
			//- verifica qtd de letras e numeros da senha digitada -//
			$count_letras	= preg_match_all('/[a-z]/i', $nova_senha, $a_letras);
			$count_nums		= preg_match_all('/\d/', $nova_senha, $a_nums);
			$count_invalido	= preg_match_all('/\W/', $nova_senha, $a_invalidos);

			if ($count_invalido > 0) {
				$msg_erro = ttext($a_rec_senha, "err_senha_invalidos");
			}
			if ($count_letras < 2) {
				$msg_erro = ttext($a_rec_senha, "err_senha_2_letras");
			}
			if ($count_nums < 2){
				$msg_erro = ttext($a_rec_senha, "err_senha_2_nums");
			}
		}else{
			$msg_erro = ttext($a_rec_senha, "err_senha_6_min");
		}

	}else{
		$msg_erro = ttext($a_rec_senha, "err_senha_vazia");
	}

	if ($nova_senha != $conf_nova_senha){
		$msg_erro = ttext($a_rec_senha, "erro_senhas_nao_batem");
	}

	if (empty($msg_erro)){

		$nova_senha = strtolower($nova_senha);

		$lu = $_POST['lu'];

		$res = pg_query($con,'BEGIN TRANSACTION');

		$sql = "UPDATE tbl_login_unico SET senha = '$nova_senha' WHERE md5(login_unico::text) = '$lu' AND ativo";

		$res = pg_query($con,$sql);

		$msg_erro = pg_last_error($con);


		if (empty($msg_erro)){

			$res = pg_query($con,'COMMIT TRANSACTION');
			$sucesso = "1";

		}else{

			$res = pg_query($con,'ROLLBACK TRANSACTION');

		}

	}

}


$html_titulo = ttext($a_rec_senha, "titulo");
$body_options = "onload='document.frm_es.email.focus() ;' ";
include "site_estatico/header.php";

?>

<?php

if ($sucesso) {
	$class_mensagem = "email_sucesso";
	$mensagem = ttext($a_rec_senha,"alterado_com_sucesso");
	echo "<script>limpa_campo_esqueci_senha('');</script>";
	$display_success = "style='display:block'";
}

if ($msg_erro != '') {
	$class_mensagem = "erro_campos_obrigatorios";
	$mensagem = $msg_erro;
	$display_error = "style='display:block'";
}

?>

<table width="948" class="barra_topo">
	<tr>
		<td>
			<div id="mensagem_envio" class='<?php echo $class_mensagem;?>'><?php echo $mensagem;?></div>
		</td>
	<tr>
</table>

<script>$('body').addClass('pg log-page')</script>
<section class="table h-img">
	<?php include('site_estatico/menu-pgi.php'); ?>
	<div class="cell">
		<div class="title"><h2>Sistema Telecontrol</h2></div>
		<h3>Recuperar sua senha.</h3>
	</div>
</section>

<section class="pad-1 login">
	<div class="main">

		<div class="alerts">
			<div class="alert success" <?=$display_success?> id="mensagem_envio_success"><i class="fa fa-check-circle"></i><?php echo $mensagem;?></div>

			<div class="alert error" <?=$display_error?> id="mensagem_envio"><i class="fa fa-exclamation-circle"></i><?php echo $mensagem;?></div>
		</div>


		<form name='frm_es' id='frm_es' method='post' action='<?=$PHP_SELF?>'>
	  		<input type='hidden' name='btn_acao' value='Enviar' >
	  		<input type="hidden" name="lu" id="lu" value="<?=$lu?>" >
	  		<?php if (!$sucesso): ?>

  			<input name ="nova_senha" id='nova_senha' type="password" size="20" placeholder="Nova senha" maxlength='50' value="<?=$nova_senha ?>" />
			<input name ="conf_nova_senha" id='conf_nova_senha' type="password" placeholder="Confirmar nova senha" size="20" maxlength='50' value="<?=$conf_nova_senha ?>" />
			<!-- <p id="nome_p">
				<label class='conf_nova_senha' for='conf_nova_senha'><?=ttext($a_rec_senha,"conf_nova_senha")?>: *</label>
				<input name ="conf_nova_senha" id='conf_nova_senha' type="password" size="20" maxlength='50' value="<?=$conf_nova_senha ?>" />
			</p> -->

			<!-- <p>
				<label class="login_fabricante">&nbsp;</label>
				<button type="button" name="btn_acao" value="Enviar" class='input_gravar' onclick="verificaSenhasRecupera('');">Enviar</button>
			</p> -->
			<button type="button" name='btn_acao' value='Enviar' onclick="verificaSenhasRecupera('');"><i class="fa fa-lock"></i>Enviar</button>

	  		<?php else: ?>

  			<p><?=ttext($a_rec_senha,"faca_seu_login_com_a_nova_senha")?></p>

	  		<?php endif ?>

	  	</form>
	</div>
</section>
<?php include("site_estatico/footer.php"); ?>
</body>
</html>
