<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


//-=============================FUNÇÃO VALIDA EMAIL==============================-//

function validatemail($email=""){ 
    if (preg_match("/^[a-z]+([\._\-]?[a-z0-9\._-]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) { 
//validacao anterior [a-z0-9\._-]
		$valida = "1"; 
    } 
    else { 
        $valida = "0"; 
    } 
    return $valida; 
}


if (strlen($_GET['acao']))  $acao      = $_GET['acao'];
if (strlen($_POST['acao'])) $acao      = $_POST['acao'];

$btn_gravar = $_POST['btn_gravar'];

if (strlen($btn_gravar) > 0) {

	$senha_velha = trim($_POST['senha_velha']);
	$senha_nova  = trim($_POST['senha_nova']);
	$senha_nova2 = trim($_POST['senha_nova2']);
//	$email       = trim($_POST['email']);


	if(strlen($senha_nova) ==0) $msg_erro = "O campo da nova senha não pode estar vazio";
	if(strlen($senha_nova2)==0) $msg_erro = "O campo para repetir a nova senha não pode estar vazio";
//	if(strlen($email)==0) $msg_erro = "O campo de E-mail está vazio, entre em contato com a TELECONTROL.";

	if(strlen($msg_erro)==0){
	
		if($senha_nova == $senha_nova2 ){
			$senha = $senha_nova;
			$senha = str_replace(' ','',$senha);

			if (strlen(trim($senha)) >= 6) {
				//- verifica qtd de letras e numeros da senha digitada -//
				$senha = strtolower($senha);
				$count_letras  = 0;
				$count_numeros = 0;
				$letras  = 'abcdefghijklmnopqrstuvwxyz';
				$numeros = '0123456789';
	
				for ($i = 0; $i <= strlen($senha); $i++) {
					if ( strpos($letras, substr($senha, $i, 1)) !== false)
						$count_letras++;
					
					if ( strpos ($numeros, substr($senha, $i, 1)) !== false)
						$count_numeros++;
				}
	
				if ($count_letras < 2)  $msg_erro = "Senha inválida, a senha deve ter pelo menos 2 letras.";
				if ($count_numeros < 2) $msg_erro = "Senha inválida, a senha deve ter pelo menos 2 números.";
			}else{
				$msg_erro = "A senha deve conter um mínimo de 6 caracteres.";
			}

			$sql =  "SELECT * FROM tbl_posto_fabrica where posto='$login_posto' AND upper(senha) = upper('$senha') and fabrica <> $login_fabrica";
			$res = @pg_exec ($con,$sql);

			if(@pg_numrows($res) > 0) $msg_erro = "Esta senha já está cadastrada para seu posto em outro fabricante";

			if(strlen($msg_erro) == 0){

				$sql = "BEGIN";
				$res = pg_exec ($con,$sql);

				$sql = "UPDATE tbl_posto_fabrica SET
						senha = '$senha',
						data_expira_senha = current_date + interval '90day'
						WHERE posto = $login_posto and fabrica=$login_fabrica";

				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
			}

/*
			//VALIDA EMAIL
			$email = $_POST['email'];
			if($msg_erro==0){
				if (validatemail($email)) {
	
					$chave1 = md5($login_posto);

//Modificado conforme chamado: 1323
//O posto não entra mais com o e-mail. Para que seja mudado, deve entrar em contato com a Telecontrol.

//					$sql = "UPDATE tbl_posto SET email = '$email',email_enviado=CURRENT_TIMESTAMP WHERE posto = $login_posto";
//					$res = pg_exec ($con,$sql);
	
					$sql=  "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
					$res = pg_exec ($con,$sql);
					$nome = pg_result($res,0,nome);
			
					//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
			
					$email_origem  = "verificacao@telecontrol.com.br";
					$email_destino = "$email";
					$assunto       = "Verificação do email";
					$corpo         = "Posto: $codigo_posto - $nome \n";
					$corpo.="<br>Email: $email\n\n";
					$corpo.="<br>Você recebeu esse email para confirmar e liberar seu acesso ao sistema TELECONTROL ASSIST, clique no link abaixo para confirmar o email.\n\n";
					$corpo.="<br><br><a href='http://www.telecontrol.com.br/assist/email_confirmacao.php?key1=$chave1&key2=$login_posto'>CLIQUE AQUI PARA VALIDAR O ACESSO AO TELECONTROL</a> \n\n";
					$corpo.="<br><br><br>Telecontrol\n";
					$corpo.="<br>www.telecontrol.com.br\n";
					$corpo.="<br>_______________________________________________\n";
					$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";
			
			
					$body_top = "MIME-Version: 1.0\r\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\r\n";
					$body_top .= "From: $email_origem\r\n";
			
					if ( @mail($email_destino, stripslashes($assunto), $corpo, $body_top ) ){
						$msg = "$email";
					}else{
						$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
	
					}
			
				}else  $msg_erro = "Endereço de Email não é válido: $email";
			}
*/
			if(strlen($msg_erro)>0){
				$sql = "ROLLBACK";
				$res = @pg_exec ($con,$sql);
			}else{
				$sql = "commit";
				$res = pg_exec ($con,$sql);
				$msg = "Cadastro efetuado com sucesso!";
				header("Location:$PHP_SELF?ok=$msg");
			}
		}else  $msg_erro = "Senhas não conferem!";
	}
}


$title = "Alterar Senha";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

//include "cabecalho.php";
?>


<style type="text/css">

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}


.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B;
}

.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>


<?

echo "<center><img src='logos/telecontrol_new.gif'><BR></center>";
$info = $_GET["ok"];
	
if(strlen($info)>0){
	echo "<br><table style=' border: #D3BE96 1px solid; background-color: #FCF0D8; font-size: 14px;' align='center' width='90%'><tr><td class='Exibe' style='font-size: 12px;'>";
	if ($sistema_lingua == "ES") echo "<b>Su nueva clave fue catastrada!!</b><br>Fue encaminado para su correo: $info com el asunto: Validación de clave del ASSIST<br>Favor entrar en su caja postal de email, y validar su nueva clave!<br><br><center><a href='login.php'>Haga um click aquí para volver al menu inicial</a></center>";
#	echo "<b>Sua senha nova senha foi cadastrada!</b><br>Foi encaminhado para o seu email: $info com o assunto: Validação de senha do ASSIST<br>Favor entrar em sua caixa-postal de email, e validar a nova senha!<br><br><center><a href='login.php'>Clique Aqui para voltar ao menu inicial</a></center>";
	echo "<b>Sua senha nova senha foi cadastrada!</b><center><a href='login.php'>Clique Aqui para voltar ao menu inicial</a></center>";
	echo "</td></tr></table>";
}else{
	if(strlen($msg_erro)>0) echo "<h1 class='Erro'><center>$msg_erro</center></h1>";
	if ($sistema_lingua == "ES") echo "<center><h3>Su clave de acceso venció el plazo.... , por favor catastr su nueva clave.</h3></center>";
	else echo "<center><h3>Sua senha de acesso expirou, por favor cadastre uma nova senha.</h3></center>";
	echo "<center><h3>Por questão de segurança, a senha deve ser alterada a cada 90 dias.</h3></center>";
	echo "<center><h3>Qualquer problema na alteração da senha de acesso, envie um e-mail para helpdesk@telecontrol.com.br.</h3></center>";
	echo "<FORM name='frm_gravar' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table width='350' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>";

	echo "<tr >";
	echo "<td class='Titulo' background='admin/imagens_admin/azul.gif'>";
	if($sistema_lingua == "ES") echo "Clave del usuario";
	else                                       echo "Senha do Usuário";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";

		echo "<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>";
	
		echo "<tr>";
		echo "<td colspan='4'>";
		if($sistema_lingua == "ES") echo "Su clave deberá contener el mínimo 6 dígitos y máximo 10 digitos,  minímo 2 letras (de A a Z) y 2 números (de 0 a 9)";
		else                        echo "Sua senha deverá conter no mínimo 6 digitos e no máximo 10 digitos, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9)";
		echo "<br><br></td>";
		echo "</tr>";
	
		echo "<tr width='100%' ";
		if($msg_erro == "Senhas não conferem!") echo "bgcolor='#FFCC00'";
		echo " >";
		echo "<td colspan='2'  align='right' height='20'>";
		if($sistema_lingua == "ES") echo "Nueva Clave:";
		else                        echo "Senha Nova:";
		echo "&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' CLASS='Caixa' maxlength></td>";
		echo "</tr>";
	
		echo "<tr width='100%' ";
		if($msg_erro == "Senhas não conferem!") echo "bgcolor='#FFCC00'";
		echo " >";
		echo "<td colspan='2' align='right' height='20' >";
		if($sistema_lingua == "ES") echo "Repetir Clave:";
		else                        echo "Repetir Senha:";
		echo "&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' CLASS='Caixa'>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";
	echo "</td>";
	echo "</tr>";

/*	echo "<tr>";
	echo "<td height='25' background='admin/imagens_admin/azul.gif' class='Titulo'>";
	if($sistema_lingua == "ES") echo "Seguraidad del sistema ASSIST!";
	else                        echo "Segurança do sistema ASSIST!";
	echo "</td>";
	echo "</tr>";
//hd 9244 takashi 07/12

	$sql = "SELECT contato_email FROM tbl_posto_fabrica WHERE posto = $login_posto and fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);
	
	if(pg_numrows($res) > 0) $email = pg_result($res,0,contato_email);

	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";
	if($sistema_lingua=="ES") echo "El link de confirmación será enviado en el E-mail abajo.<br>Solamente despues usted de confirmar en su caja-postal el email, usted tendrá la validación de su clave.<br><br>E-mail:";
	else                      echo "O link de confirmação será enviado no E-mail abaixo.<br>Somente após você confirmar em sua caixa-postal o email, você terá a validação da sua senha.<br><br>E-mail:";
	echo "&nbsp;&nbsp;";
		if (strlen($email)==0) {
			echo "<input type='text' size='50' maxlength='255' name='sem_email' readonly value='Não existe email no seu cadastro!' CLASS='Caixa'>";
		} else {
			echo "<input type='text' size='50' maxlength='255' name='email' readonly value='$email' CLASS='Caixa'>";
		}
	if($sistema_lingua=="ES") echo "&nbsp;&nbsp;<br><br><b>*Caso no tenga acceso a este E-mail, o el campo arriba estea en blanco, entre en contacto con la TELECONTROL pediendo la alteración.";
	else                      echo "&nbsp;&nbsp;<br><br><b>*Caso não tenha acesso a este E-mail, ou o campo acima esteja em branco, entre em contato com o fabricante pedindo a alteração.</b>";
	echo "<br><br><center>";
	echo "</td>";
	echo "</tr>";
*/

	echo "</table>";

	$texto_botao = "Gravar";
	if($sistema_lingua=="ES") $texto_botao = "Guardar";

echo "<center><br><input type='submit' name='btn_gravar' value='$texto_botao'><input type='hidden' name='acao' value=$acao></center>";
	echo "</form>";

}
