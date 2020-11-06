<?
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_admin.php';

if (strlen($_GET['acao'])) $acao       = $_GET['acao'];
if (strlen($_POST['acao'])) $acao       = $_POST['acao'];

$btn_gravar = $_POST['btn_gravar'];

if (strlen($btn_gravar) > 0) {

	$senha_nova  = trim($_POST['senha_nova']);
	$senha_nova2 = trim($_POST['senha_nova2']);


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


			if(strlen($msg_erro) == 0){

				$sql = "SELECT senha FROM tbl_admin WHERE admin = $login_admin AND senha = '$senha';";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res) > 0){
					$msg_erro = "A Senha tem que ser diferente da anterior. Escolha outra Senha.";
				}else{
					$sql = "UPDATE tbl_admin SET
							senha = '$senha_nova',
							data_expira_senha = current_date + interval '90day'
							WHERE admin = $login_admin";
					$res = pg_exec ($con,$sql);
					
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);

					if(strlen($msg_erro)==0) 
						if($acao=='inserir'){ $info = "A senha foi cadastrada com sucesso!";
						}else{ 
							$msg_validade_cadastro = "A senha foi alterada com sucesso!";
							
							header("Location: menu_cadastro.php");
						}
				}
			}
		}
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

echo "<center><img src='logos/logo_tc_2009_md.gif'><BR></center>";
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
	echo "<td class='Titulo' background='imagens_admin/azul.gif'>Senha do Usuário</td>";
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


	echo "</table>";
	echo "<center><br><input type='submit' name='btn_gravar' value='gravar'><input type='hidden' name='acao' value=$acao></center>";
	echo "</form>";


}
