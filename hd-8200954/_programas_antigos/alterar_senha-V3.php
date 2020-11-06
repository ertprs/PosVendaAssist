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
	$email       = trim($_POST['email']);


	if(strlen($senha_nova) ==0) $msg_erro = "O campo da nova senha não pode estar vazio";
	if(strlen($senha_nova2)==0) $msg_erro = "O campo para repetir a nova senha não pode estar vazio";

	if(strlen($msg_erro)==0){
	
		if($senha_nova == $senha_nova2 ){
			$senha = $senha_nova;
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


			//VALIDA EMAIL
			$email = $_POST['email'];
			if($msg_erro==0){
				if (validatemail($email)) {
	
					$chave1 = md5($login_posto);
			
					$sql = "UPDATE tbl_posto SET email = '$email',email_enviado=CURRENT_TIMESTAMP WHERE posto = $login_posto";
					$res = pg_exec ($con,$sql);
	
					$sql=  "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
					$res = pg_exec ($con,$sql);
					$nome = pg_result($res,0,nome);
			
					//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO
			
					$email_origem  = "verificacao@telecontrol.com.br";
					$email_destino = "$email";
					$assunto       = "Verificação do email";
					$corpo         = "<br>Posto: $codigo_posto - $nome \n";
					$corpo.="<br>Email: $email\n\n";
					$corpo.="<br>Você recebeu esse email para confirmar e liberar seu acesso ao sistema TELECONTROL ASSIST, clique no link abaixo para confirmar o email.\n\n";
					$corpo.="<br><a href='http://www.telecontrol.com.br/assist/email_confirmacao.php?key1=$chave1&key2=$login_posto'>CLIQUE AQUI PARA LIBERAR</a> \n\n";
					$corpo.="<br><br>Telecontrol\n";
					$corpo.="<br>www.telecontrol.com.br\n";
					$corpo.="<br>_______________________________________________\n";
					$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";
			
			
					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
			
			//$corpo = $body_top.$corpo;
			
					if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
						$msg = "$email";
					}else{
						$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
	
					}
			
				}else  $msg_erro = "Endereço de Email não é válido: $email";
			}

			if(strlen($msg_erro)>0){
				$sql = "ROLLBACK";
				$res = @pg_exec ($con,$sql);
			}else{
				$sql = "commit";
				$res = pg_exec ($con,$sql);
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

echo "<center><img src='logos/telecontrol2.jpg'><BR></center>";
$info = $_GET["ok"];
	
if(strlen($info)>0){
	echo "<br><table style=' border: #D3BE96 1px solid; background-color: #FCF0D8; font-size: 14px;' align='center' width='90%'><tr><td class='Exibe'>";
	echo "<b>Sua senha nova senha foi cadastrada!</b><br>Foi encaminhado para o seu email: $info com o assunto: Validação de senha do ASSIST<br>Favor entrar em sua caixa-postal de email, e validar a nova senha!<br><br><center><a href='login.php'>Clique Aqui para voltar ao menu inicial</a></center>";
	echo "</td></tr></table>";
}else{
	if(strlen($msg_erro)>0) echo "<h1 class='Erro'><center>$msg_erro</center></h1>";
	echo "<center><h3>Sua senha de acesso expirou, por favor cadastre uma nova senha.</h3></center>";
	echo "<FORM name='frm_gravar' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table width='350' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>";

	echo "<tr >";
	echo "<td class='Titulo' background='admin/imagens_admin/azul.gif'>Senha do Usuário</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";

		echo "<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>";
	
		echo "<tr>";
		echo "<td colspan='4'>Sua senha deverá conter no mínimo 6 digitos e no máximo 10 digitos, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9)<br><br></td>";
		echo "</tr>";
	
		echo "<tr width='100%' ";
		if($msg_erro == "Senhas não conferem!") echo "bgcolor='#FFCC00'";
		echo " >";
		echo "<td colspan='2'  align='right' height='20'>Senha Nova:&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' CLASS='Caixa' maxlength></td>";
		echo "</tr>";
	
		echo "<tr width='100%' ";
		if($msg_erro == "Senhas não conferem!") echo "bgcolor='#FFCC00'";
		echo " >";
		echo "<td colspan='2' align='right' height='20' >Repetir Senha:&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' CLASS='Caixa'>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td height='25' background='admin/imagens_admin/azul.gif' class='Titulo'>Segurança do sistema ASSIST!";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>Favor informar o seu email atual abaixo!<br>Somente após você confirmar em sua caixa-postal o email, você terá a validação da sua senha.<br><br>E-mail:&nbsp;&nbsp;
		<input type='text' size='30' maxlength='255' name='email' value='$email' CLASS='Caixa'>
		&nbsp;&nbsp;";

	echo "<br><br><center>";
	echo "</td>";
	echo "</tr>";


	echo "</table>";
echo "<center><br><input type='submit' name='btn_gravar' value='gravar'><input type='hidden' name='acao' value=$acao></center>";
	echo "</form>";


}
