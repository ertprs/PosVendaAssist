<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';


//-=============================FUN��O VALIDA EMAIL==============================-//

function validatemail($email=""){ 
    if (preg_match("/^[a-z]+([\._\-]?[a-z0-9]+)+@+[a-z0-9\._-]+\.+[a-z]{2,3}$/", $email)) { 
//validacao anterior [a-z0-9\._-]
		$valida = "1"; 
    } 
    else { 
        $valida = "0"; 
    } 
    return $valida; 
} 


$email = $_POST['email'];
if(strlen($email)>0){

	if (validatemail($email)) { 
		$chave1 = md5($login_posto);

		//BEGIN no banco, porque caso de erro na hora de fazer o update ele da um rollback se nao um commit
		$sql = "BEGIN";
		$res = pg_exec ($con,$sql);

		$sql = "UPDATE tbl_posto SET email = '$email',email_enviado=CURRENT_TIMESTAMP WHERE posto = $login_posto";
		$res = pg_exec ($con,$sql);

		$sql=  "SELECT nome FROM tbl_posto WHERE posto = $login_posto";
		$res = pg_exec ($con,$sql);
		$nome = pg_result($res,0,nome);

		//ENVIA EMAIL PARA POSTO PRA CONFIRMA��O

		$email_origem  = "verificacao@telecontrol.com.br";
		$email_destino = "$email";
		$assunto       = "Verifica��o do email";
		$corpo         = "<br>Posto: $codigo_posto - $nome \n";
		$corpo.="<br>Email: $email\n\n";
		$corpo.="<br>Voc� recebeu esse email para confirmar e liberar seu acesso ao sistema TELECONTROL ASSIST, clique no link abaixo para confirmar o email.\n\n";
		$corpo.="<br><a href='http://posvenda.telecontrol.com.br/assist/email_confirmacao.php?key1=$chave1&key2=$login_posto'>CLIQUE AQUI PARA LIBERAR</a> \n\n";
		$corpo.="<br><br>Telecontrol\n";
		$corpo.="<br>www.telecontrol.com.br\n";
		$corpo.="<br>_______________________________________________\n";
		$corpo.="<br>OBS: POR FAVOR N�O RESPONDA ESTE EMAIL.";


		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

//$corpo = $body_top.$corpo;

		if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
			$msg = "<br>Foi enviado um email para: ".$email.", e nele h� um link para confirmar a validade do email.<br>Logo ap�s a confirma��o o sistema estar� liberado!<br>";
		}else{
			$msg_erro = "N�o foi poss�vel enviar o email. Por favor entre em contato com a TELECONTROL.";
			$sql = "rollback";
			$res = @pg_exec ($con,$sql);
		}
		$sql = "commit";
		$res = pg_exec ($con,$sql);
//echo $msg;exit;
	echo "<script language='javascript'>this.close()</script>";
		exit;

	}else{
		echo "<table width='650'>";
		echo "<tr>";
		echo "<td bgcolor='#3399FF'><h3>Este endere�o de Email n�o � v�lido: $email</h3>";
		echo "</tr>";
		echo "</table>";
		exit;
	}
}
?>
<html>
<head>
<title>Verifica��o de Email - Telecontrol</title>
</head>

<?
//AP�S A TELA DE LOGIN � PASSADO UMA CHAVE E CAI NESSE CASO
if (strlen($key1)>0){
	$chave1 = md5($login_posto);
	if ( $chave1 == $key1 ){
		$sql="  SELECT nome,email
				FROM tbl_posto
				WHERE posto = $login_posto";
		$res = @pg_exec ($con,$sql);
		
		$email		= pg_result ($res,0,email);
		$nome		= pg_result ($res,0,nome);
			
		if (@pg_numrows($res) > 0) {
		?>
			<form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>">
				<input type="hidden" name="btn_acao">
				<fieldset class="borda"  >
					<legend align="center"  class="titulo">Verifica��o de Email</legend>
					<br>
					<center>
						<font color="#000000" size="2">Por favor confirme se o endere�o de email abaixo � o email atual do  <?=$nome?><br><br>
						Email</font>
						<input class="frm" type="text" name="email" size="30" maxlength="50" value="<? echo $email; ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o email v�lido.');">
						<input type='hidden' name='key1' value='<?=$key1;?>'>
						<img border="0" src="imagens/btn_continuar.gif" align="absmiddle" onclick="javascript: if (document.frm_locacao.btn_acao.value == '') { document.frm_locacao.btn_acao.value='locacao'; document.frm_locacao.submit(); } else { alert('N�o clique no bot�o voltar do navegador, utilize somente os bot�es da tela'); }" style="cursor: hand" alt="Clique aqui p/ atualizar e continuar a usar o sistema">
						<br>
						<br>
					</center>
				</fieldset>
				</form>
				</td>
				</tr>
				</table>
			<?
		}
	}
	else{
		echo 'C�digo de verifica��o inv�lido';
	}
}else{
	echo'Voc� n�o tem acesso a essa p�gina!';
}
?>
