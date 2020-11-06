<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';



if (strlen($_GET['acao'])) $acao       = $_GET['acao'];
if (strlen($_POST['acao'])) $acao       = $_POST['acao'];

$btn_gravar = $_POST['btn_gravar'];

if (strlen($btn_gravar) > 0) {

	$senha_velha = trim($_POST['senha_velha']);
	$senha_nova  = trim($_POST['senha_nova']);
	$senha_nova2 = trim($_POST['senha_nova2']);

	
	$sql =  "SELECT * FROM tbl_admin where admin='$login_admin' AND senha='$senha_velha'";
	$res = @pg_exec ($con,$sql);
	if (@pg_numrows($res) > 0) {

		if (strlen(trim($senha_nova)) >= 6) {
			//- verifica qtd de letras e numeros da senha digitada -//
			$senha_nova = strtolower($senha_nova);
			$senha_nova2 = strtolower($senha_nova2);
			$count_letras  = 0;
			$count_numeros = 0;
			$letras  = 'abcdefghijklmnopqrstuvwxyz';
			$numeros = '0123456789';
		//echo strlen($senha); echo $senha;exit;
			for ($j = 0; $j <= strlen($senha_nova); $j++) {
				if ( strpos($letras, substr($senha_nova, $j, 1)) !== false)
					$count_letras++;
				
				if ( strpos ($numeros, substr($senha_nova, $j, 1)) !== false)
					$count_numeros++;
			}

			if ($count_letras < 2)  $msg_erro .= "Senha inválida, a senha deve ter pelo menos 2 letras para o LOGIN $login <br>";
			if ($count_numeros < 2) $msg_erro .= "Senha inválida, a senha deve ter pelo menos 2 números para o LOGIN $login <br>";
		}else{
			$msg_erro .= "A senha deve conter um mínimo de 6 caracteres para o LOGIN $login<br>";
		}
		if(strlen($msg_erro)==0){
			if($senha_nova == $senha_nova2 ) {
				$sql = "UPDATE tbl_admin SET
						senha = '$senha_nova'
						WHERE admin = $login_admin";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);
				if(strlen($msg_erro)==0) 
					if($acao=='inserir') $info = "A senha foi cadastrada com sucesso!";
					else                 $info = "A senha foi alterada com sucesso!";
			}else{
				$msg_erro = "Senhas não conferem!";
			}
		}
	}else{$msg_erro="Senha atual está incorreta";}
}


$TITULO = "Alteração de Senha do Cadastro";

include "menu.php";
?>


<style type="text/css">

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.TituloConsulta {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #D9E2EF;
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

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>


<?



	
if(strlen($info)>0){
	echo "<h1>$info</h1>";
}else{
	echo "<center>Aqui você poderá alterar sua senha do sistema Assit, para fazer isso é necessário digitar a senha atual e a nova senha.</center>";
	echo "<FORM name='frm_gravar' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table class='Tabela' width='300' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>";
	echo "<tr >";
	echo "<td class='Titulo'>Senha do Usuário</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td bgcolor='#F3F8FE'>";
	echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='2' CLASS='table_line' bgcolor='#F3F8FE'>";
	echo "<tr class='Conteudo' >";
	echo "<TD colspan='4' style='text-align: center;'>";
	echo "<br>";
	echo "</TD>";
	echo "</tr>";
	echo "<TR width='100%'  >";
	echo "<td colspan='2'  align='right' height='40'>Senha Atual:&nbsp;</td>";
	echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_velha' ></td>";
	echo "</tr>";
	echo "<TR  >";
	echo "<td colspan='4'  ><hr class='TituloConsulta'></td>";
	echo "</tr>";
	echo "<TR width='100%'  >";
	echo "<td colspan='2'  align='right' height='20'>Senha Nova:&nbsp;</td>";
	echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' ></td>";
	echo "</tr>";
	echo "<TR width='100%'  >";
	echo "<td colspan='2'  align='right' height='20' >Repetir Senha:&nbsp;</td>";
	echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' ></td>";
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
