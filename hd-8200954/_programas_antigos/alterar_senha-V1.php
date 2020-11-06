<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['acao'])) $acao       = $_GET['acao'];
if (strlen($_POST['acao'])) $acao      = $_POST['acao'];

$btn_gravar = $_POST['btn_gravar'];

if (strlen($btn_gravar) > 0) {

	$senha_velha = trim($_POST['senha_velha']);
	$senha_nova  = trim($_POST['senha_nova']);
	$senha_nova2 = trim($_POST['senha_nova2']);

	if(strlen($senha_velha)==0) $msg_erro = "Senha atual não pode estar vazia";
	if(strlen($senha_nova) ==0) $msg_erro = "O campo da nova senha não pode estar vazio";
	if(strlen($senha_nova2)==0) $msg_erro = "O campo para repetir a nova senha não pode estar vazio";

	if(strlen($msg_erro)==0){
		
		$sql =  "SELECT * FROM tbl_posto_fabrica where posto='$login_posto' AND upper(senha) = upper('$senha_velha') and fabrica=$login_fabrica";

		$res = @pg_exec ($con,$sql);

		$sql2 =  "SELECT * FROM tbl_posto_fabrica where posto='$login_posto' AND upper(senha) = upper('$senha_nova') and fabrica <> $login_fabrica";
		$res2 = @pg_exec ($con,$sql2);
		if(@pg_numrows($res2) > 0) $msg_erro = "Esta senha já está cadastrada para seu posto em outro fabricante";

		if (@pg_numrows($res) > 0) {
	
			if($senha_nova == $senha_nova2 ){
				$senha = $senha_nova;
				if (strlen(trim($senha)) >= 6) {
					//- verifica qtd de letras e numeros da senha digitada -//
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
					$sql = "UPDATE tbl_posto_fabrica SET
							senha = '$senha',
							data_expira_senha = current_date + interval '90day'
							WHERE posto = $login_posto and fabrica=$login_fabrica";
					//if($ip =='200.208.222.134') echo $sql;
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}

				if(strlen($msg_erro)==0) 
					if($acao=='inserir') $info = "A senha foi cadastrada com sucesso!";
					else{
						$msg_erro = "A senha foi alterada com sucesso!";
						
						header("Location:menu_os.php?ok=$info");
					}
			}else{
				$msg_erro = "Senhas não conferem!";
			}
		}else $msg_erro="Senha atual está incorreta";
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
	border: 1px solid;	
	background-color: #596D9B;
}
.caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
}


</style>


<?



	
if(strlen($info)>0){
	echo "<h1>$info</h1>";
}else{
	echo "<center><img src='logos/telecontrol2.jpg'><BR><h2>Sua senha de acesso expirou, por favor cadastre uma nova senha.</h2>Sua senha deverá conter no mínimo 6 digitos, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9)</center>";
	echo "<BR><BR><FORM name='frm_gravar' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	if(strlen($msg_erro)>0){
		echo "<h1 class='Erro'><center>$msg_erro</center></h1>";
	}
	echo "<table class='Tabela' width='300' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>";
	echo "<tr >";
	echo "<td class='Titulo'>Senha do Usuário</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor='#F3F8FE'>";
	echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='2'  bgcolor='#F3F8FE'>";
	echo "<tr class='Conteudo' >";
	echo "<TD colspan='4' style='text-align: center;'>";
	echo "<br>";
	echo "</TD>";
	echo "</tr>";
	echo "<TR width='100%'  >";
	echo "<td colspan='2'  align='right' height='40'";
	if($msg_erro == "Senha atual está incorreta") echo "bgcolor='FF0000'";
	echo ">Senha Atual:&nbsp;</td>";
	echo "<td colspan='2'";
	if($msg_erro == "Senha atual está incorreta") echo "bgcolor='FF0000'";
	echo " ><INPUT TYPE='password' NAME='senha_velha' class='caixa'></td>";
	echo "</tr>";
	echo "<TR  >";
	echo "<td colspan='4'  ><hr class='TituloConsulta'></td>";
	echo "</tr>";
	echo "<TR width='100%' ";
	if($msg_erro == "Senhas não conferem!") echo "bgcolor='FF0000'";
	echo " >";

	echo "<td colspan='2'  align='right' height='20'>Senha Nova:&nbsp;</td>";
	echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' class='caixa'></td>";
	echo "</tr>";
	echo "<TR width='100%' ";
	if($msg_erro == "Senhas não conferem!") echo "bgcolor='FF0000'";
	echo " >";
	echo "<td colspan='2'  align='right' height='20' >Repetir Senha:&nbsp;</td>";
	echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' class='caixa'></td>";
	echo "</tr>";
	echo "<tr class='Conteudo' >";
	echo "<TD colspan='4' style='text-align: center;'>";
	echo "<br><input type='submit' name='btn_gravar' value='gravar'><input type='hidden' name='acao' value=$acao>";
	echo "</TD>";
	echo "</tr>";
	echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
	//alterar senha
}
