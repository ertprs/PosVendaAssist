<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$nome  = $_GET['nome'];
$email = $_GET['email'];
$ok    = $_GET['ok'];
$posto = $_GET['p'];

$btn_acao      = trim($_POST['btn_acao']);

if (strlen($btn_acao)>0){
	$cadastrar = trim($_GET['cadastrar']);
	
	if ($btn_acao=="Finalizar Cadastro" AND $cadastrar=='1'){
		$nome        = trim($_POST['nome']);
		$email       = trim($_POST['email']);
		$cnpj        = trim($_POST['cnpj']);
		$endereco    = trim($_POST['endereco']);
		$numero      = trim($_POST['numero']);
		$bairro      = trim($_POST['bairro']);
		$cidade      = trim($_POST['cidade']);
		$estado      = trim($_POST['estado']);
		$complemento = trim($_POST['complemento']);
		$cep         = trim($_POST['cep']);
		$fone        = trim($_POST['fone']);
		$senha       = trim($_POST['senha']);
		$senha2      = trim($_POST['senha2']);

		$Xcnpj = str_replace("-","",$cnpj);
		$Xcnpj = str_replace(".","",$Xcnpj);
		$Xcnpj = str_replace("/","",$Xcnpj);
		$Xcnpj = str_replace(" ","",$Xcnpj);
		$Xcnpj = str_replace("'","",$Xcnpj);

		$Xcep = str_replace("-","",$cep);
		$Xcep = str_replace(".","",$Xcep);
		$Xcep = str_replace("/","",$Xcep);
		$Xcep = str_replace(" ","",$Xcep);
		$Xcep = str_replace("'","",$Xcep);

		if (strlen($nome)==0){
			$msg_erro = "O nome é obrigatório.";
		}else{
			$Xnome = "'".$nome."'";
		}

		if (strlen($msg_erro)==0 AND strlen($email)==0){
			$msg_erro = "O email é obrigatório.";
		}else{
			$Xemail = "'".$email."'";
		}

		if (strlen($msg_erro)==0 AND strlen($Xcnpj)==0){
			$msg_erro = "O CNPJ/CPF é obrigatório.";
		}else{
			$Xcnpj = "'".$Xcnpj."'";
		}
		
		if (strlen($msg_erro)==0 AND strlen($endereco)==0){
			$msg_erro = "O endereço é obrigatório.";
		}else{
			$Xendereco = "'".$endereco."'";
		}

		if (strlen($msg_erro)==0 AND strlen($numero)==0){
			$msg_erro = "O endereço é obrigatório.";
		}else{
			$Xnumero = "'".$numero."'";
		}

		if (strlen($msg_erro)==0 AND strlen($bairro)==0){
			$msg_erro = "O bairro é obrigatório.";
		}else{
			$Xbairro = "'".$bairro."'";
		}

		if (strlen($msg_erro)==0 AND strlen($cidade)==0){
			$msg_erro = "A cidade é obrigatória.";
		}else{
			$Xcidade = "'".$cidade."'";
		}

		if (strlen($msg_erro)==0 AND strlen($estado)==0){
			$msg_erro = "O estado é obrigatório.";
		}else{
			$Xestado = "'".$estado."'";
		}
		
		if (strlen($msg_erro)==0 AND strlen($complemento)==0){
			$Xcomplemento = " NULL ";
		}else{
			$Xcomplemento = "'".$complemento."'";
		}
		
		if (strlen($msg_erro)==0 AND strlen($Xcep)==0){
			$msg_erro = "O cep é obrigatório.";
		}elseif (strlen($msg_erro)==0 AND strlen($Xcep)<>8){
				$msg_erro = "O cep é inválido.";
		}else{
			$Xcep = "'".$Xcep."'";
		}

		if (strlen($msg_erro)==0 AND strlen($fone)==0){
			$msg_erro = "O telefone é obrigatório.";
		}else{
			$Xfone = "'".$fone."'";
		}

		if (strlen($msg_erro)==0 AND strlen($senha)==0){
			$msg_erro = "A senha é obrigatória.";
		}elseif (strlen($msg_erro)==0 AND $senha != $senha2){
			$msg_erro = "As senhas devem ser iguais.";
		}else{
			$Xsenha = "'".$senha."'";
		}

		if (strlen($msg_erro)==0){
			$sql = "SELECT posto
					FROM tbl_posto
					WHERE cnpj = $Xcnpj";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if (pg_numrows ($res) > 0) {
				$msg_erro = "Já existe cadastro com este CNPJ. Se você for um usuário do Assist, faça seu cadastro no <a href='http://www.telecontrol.com.br/login_unico.php'>Login Único</a></u> ";
			}

			if (strlen($msg_erro)==0){
				$sql = "SELECT posto
						FROM tbl_posto
						WHERE email= $Xemail";
				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (pg_numrows ($res) > 0) {
					$msg_erro = "Já existe cadastro com este email. Se você for um usuário do Assist, faça seu cadastro no <a href='http://www.telecontrol.com.br/login_unico.php'>Login Único</a></u> ";
				}
			}

			if (strlen($msg_erro)==0){

				$res = pg_exec ($con,"BEGIN TRANSACTION");

				$sql = "INSERT INTO tbl_posto (
						nome,
						cnpj,
						endereco,
						numero,
						complemento,
						cep,
						cidade,
						estado,
						email,
						fone,
						bairro
					)VALUES(
						$Xnome,
						$Xcnpj,
						$Xendereco,
						$Xnumero,
						$Xcomplemento,
						$Xcep,
						$Xcidade,
						$Xestado,
						$Xemail,
						$Xfone,
						$Xbairro
					)";

				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if (strlen($msg_erro)==0){
					$res = @pg_exec ($con,"SELECT CURRVAL ('seq_posto')");
					$posto  = pg_result ($res,0,0);
					$msg_erro .= pg_errormessage($con);
				}

				if (strlen($msg_erro)==0){
					$sql = "INSERT INTO tbl_posto_fabrica (
							posto,
							fabrica,
							senha,
							codigo_posto,
							distribuidor,
							tipo_posto,
							contato_endereco,
							contato_numero,
							contato_complemento,
							contato_cep,
							contato_cidade,
							contato_estado,
							contato_bairro,
							contato_email
						)VALUES(
							$posto,
							10,
							$Xsenha,
							NULL,
							4311,
							52,
							$Xendereco,
							$Xnumero,
							$Xcomplemento,
							$Xcep,
							$Xcidade,
							$Xestado,
							$Xbairro,
							$Xemail
						)";
					$res = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if (strlen($msg_erro)==0){
					$email_origem  = "helpdesk@telecontrol.com.br";
					$email_destino = $email;
					$chave1 = md5($posto);
					$assunto       = "Loja Virtual - Login";
					$corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR 
							NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
				
							<P align=justify>Parabéns, <br><br>Seu cadastro na Loja Virtual da Telecontrol foi concluído com sucesso!<br><br>

							<P align=justify>Para <FONT color=#006600><STRONG>validar</STRONG></FONT> seu email, utilize o link abaixo: <br>
							<a href='http://posvenda.telecontrol.com.br/assist/lv_simples_login.php?btn_acao=Autenticar&id=$posto&key1=$chave1'><u><b>Clique aqui para validar seu email</b></u></a>.</P><br>
							Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>http://posvenda.telecontrol.com.br/assist/lv_simples_login.php?btn_acao=Autenticar&id=$posto&key1=$chave1<br>
							<br>
							Seu LOGIN para acesso é: $email<br>
							Sua Senha é: $senha<br><br>
							<br>
							<br>
							<P align=justify>Suporte Telecontrol Networking.<BR>helpdesk@telecontrol.com.br 
							</P>";
				
					$body_top = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";
				
					if ( @mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " ) ){
						$msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
					}
				}
			}
			if(strlen($msg_erro)==0){
				#$res = pg_exec($con,"ROLLBACK TRANSACTION");
				$res = pg_exec($con,"COMMIT TRANSACTION");
				header("Location: $PHP_SELF?ok=1&p=$posto");
				exit;
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual";

include "login_unico_cabecalho.php";



$cook_fabrica = 10;
$login_fabrica = 10;

echo "<div style='position: absolute;opacity:.90;z-index:1; overflow: auto;position:absolute;top:0px;right: 5px;'><table id='mensagem' style='border: 1px solid rgb(211, 190, 150); background-color: rgb(252, 240, 216);' ><tbody><tr><td><b>Carregando dados...</b></td></tr></tbody></table></div>";

?>

<script language='javascript'>
	function checarNumero(campo){
		var num = campo.value.replace(",",".");
		campo.value = parseInt(num);
		if (campo.value=='NaN') {
			campo.value='';
		}
	}
</script>


<style type="text/css">
	ul#intro,ul#intro li{list-style-type:none;margin:0;padding:0}
	ul#intro{width:100%;overflow:hidden;margin-bottom:10px}
	ul#intro li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
	li#produto{background: #CEDFF0}
	ul#intro li#more{margin-right:0;background: #7D63A9}
	ul#intro p,ul#intro h3{margin:0;padding: 0 10px}
	
	ul#intro2,ul#intro2 li{list-style-type:none;margin:0;padding:0}
	ul#intro2{width:100%;overflow:hidden;margin-bottom:10px}
	ul#intro2 li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
	li#infor{text-align:left;background: #0082d7;color:#FFFFFF;}
	ul#intro2 li#more{margin-right:0;background: #7D63A9}
	ul#intro p,ul#intro2 h3{margin:0;padding: 0 10px}

	ul#intro3,ul#intro3 li{list-style-type:none;margin:0;padding:0}
	ul#intro3{width:100%;overflow:hidden;margin-bottom:10px}
	ul#intro3 li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
	li#maisprod{background: #FFBA75}
	ul#intro3 li#more{margin-right:0;background: #7D63A9}
	ul#intro p,ul#intro3 h3{margin:0;padding: 0 10px}
</style>
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript" src="js/niftyLayout.js"></script>


<?

include 'lv_menu.php';
# AVISO
echo "<BR>"; 
# BUSCA POR PEÇA
echo "<table width='98%' border='0' align='center' cellpadding='2' cellspacing='2'>\n";
echo "<tr>\n";
echo "<td width='170' valign='top'>\n";
/*MENU*/
include "lv_menu_lateral.php";

echo "</td>\n";
echo "<td valign='top' align='right'>\n";
//echo "	<center><img src='imagens/liquidacao2.png' border='0'></center>";
echo "<table width='95%' border='0' align='center' cellpadding='0' cellspacing='0'>\n";

echo "<tr>\n";
echo "<td align='center'>\n";
echo "<ul id=\"intro2\">\n";
echo "<li id=\"infor\">\n";
echo "<font size='3'><B>Loja Virtual - Cadastro</b></font>";
echo "</li>\n</ul>\n";	
echo "</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td align='center'>\n";
echo "<div align='left'>";

if (strlen($msg_erro)>0){
	echo "<p><b style='color:#FF8484'>".$msg_erro."</b></p><br>";
}

if ($ok=="1" AND strlen($posto)>0){
	echo "<p class='titulo2'>Parabéns! Seu cadastro foi concluído com sucesso!</p>";
	echo "<br>";
	echo "<p class='titulo2'>Foi enviado um email para você confirmando o cadastro. É preciso que você acesse seu email e confirme o cadastro no link enviado.</p>";
	echo "<br>";
	echo "<p class='titulo2'>Após confirmar seu cadastro, <a href='lv_simples_login.php'>clique aqui para efetuar o Login</a></p>";
}else{
?>
<form name='frm_login' method='POST' action='<?=$PHP_SELF?>?cadastrar=1'>
<table align='left' cellspacing='5'>
	<tr>
		<td colspan='4'>
			<p class='titulo2'>Preencha os dados para efetuar seu cadastro.</p><br>
			<p class='aviso'>(*) Campos obrigatórios</p><br>
			</td>
	</tr>
	<tr>
		<td align='right'>Nome: * </td>
		<td><input type='text' size='30' maxlength='50' name='nome' value='<?=$nome?>'></td>
	</tr>
	<tr>
		<td align='right'>CPF / CNPJ: * </td>
		<td><input type='text' size='20' maxlength='20' name='cnpj' value='<?=$cnpj?>'></td>
	</tr>
	<tr>
		<td align='right'>E-mail: * </td>
		<td><input type='text' size='30' maxlength='100' name='email' value='<?=$email?>'></td>
	</tr>
	<tr>
		<td align='right'>Endereço: * </td>
		<td><input type='text' size='40' maxlength='40' name='endereco' value='<?=$endereco?>'></td>
		<td align='right'>Número: * </td>
		<td><input type='text' size='6' maxlength='6' name='numero' value='<?=$numero?>'></td>
	</tr>
	<tr>
		<td align='right'>Bairro: * </td>
		<td colspan='3'><input type='text' size='40' maxlength='40' name='bairro' value='<?=$bairro?>'></td>
	</tr>
	<tr>
		<td align='right'>Cidade: * </td>
		<td><input type='text' size='40' name='cidade' maxlength='40' value='<?=$cidade?>'></td>
		<td align='right'>Estado: * </td>
		<td><input type='text' size='2' name='estado' maxlength='2' value='<?=$estado?>'></td>
	</tr>
	<tr>
		<td align='right'>Complemento: </td>
		<td colspan='3'><input type='text' size='50'  maxlength='50' name='complemento' value='<?=$complemento?>'></td>
	</tr>
	<tr>
		<td align='right'>CEP: * </td>
		<td><input type='text' size='10' maxlength='10' name='cep' value='<?=$cep?>'></td>
	</tr>
	<tr>
		<td align='right'>Telefone: * </td>
		<td><input type='text' size='15' maxlength='20' name='fone' value='<?=$fone?>'></td>
	</tr>
	<tr>
		<td align='right'>Senha de Acesso: * </td>
		<td><input type='password' size='10' maxlength='10' name='senha' value=''></td>
	</tr>
	<tr>
		<td align='right'>Repita a Senha: * </td>
		<td><input type='password' size='10' maxlength='10' name='senha2' value=''></td>
	</tr>
	<tr>
		<td></td>
		<td><input type='submit' name='btn_acao' value='Finalizar Cadastro'></td>
	</tr>
</table>
</form>
<?}?>

<?
echo "</div>\n";
echo "</td>\n";
echo "</tr>\n";

echo "<script>document.getElementById('mensagem').style.visibility = 'hidden';</script>";
echo "</table>\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";

include "login_unico_rodape.php";
?>