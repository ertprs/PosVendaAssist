<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';
include 'menu.php';

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
				$res = pg_exec ($con,"BEGIN TRANSACTION");

				$sql = "UPDATE tbl_pessoa SET
						nome = '$nome_completo',
						email = '$email',
						fone_residencial = '$fone_residencial',
						fone_comercial = '$fone_comercial'
						WHERE pessoa = $login_pessoa and empresa = $login_empresa";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				$sql = "UPDATE tbl_empregado SET
						senha = '$senha_nova',
						data_expira_senha = current_date + interval '90day'
						WHERE pessoa = $login_pessoa and empresa = $login_empresa";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				if (strlen ($msg_erro) == 0) {
					$res = pg_exec ($con,"COMMIT TRANSACTION");
					$msg_erro = "Dados do usuario gravado com sucesso!";
				}else{
					$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				}
			}
		}else{
			$msg_erro = "Senhas não conferem.";
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
		if(strlen($login_empregado)>0){
			$sql = "SELECT 
						tbl_pessoa.pessoa ,
						tbl_pessoa.nome  ,
						tbl_pessoa.email ,
						tbl_pessoa.fone_residencial,
						tbl_pessoa.fone_comercial,
						tbl_empregado.senha ,
						tbl_empregado.ativo ,
						tbl_empregado.privilegios 
					FROM tbl_pessoa
					JOIN tbl_empregado USING(pessoa)
					WHERE tbl_empregado.empregado = $login_empregado and tbl_pessoa.empresa = $login_empresa";
			$res = pg_exec ($con,$sql);
			for ($i = 0; $i < pg_numrows($res); $i ++){
				$pessoa				=	trim(pg_result ($res,$i,pessoa));
				$senha				=	trim(pg_result ($res,$i,senha));
				$nome_completo		=	trim(pg_result ($res,$i,nome));
				$email				=	trim(pg_result ($res,$i,email));
				$fone_residencial	=	trim(pg_result ($res,$i,fone_residencial));
				$fone_comercial		=	trim(pg_result ($res,$i,fone_comercial));


$info = $_GET["ok"];

	if(strlen($msg_erro)>0) echo "<div class='Erro' align='center'><font color='#ffffff'>$msg_erro</font></div>";
	echo "<FORM name='frm_gravar' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table width='500' class='tabela' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>";

	echo "<tr >";
	echo "<td class='Titulo_Tabela' background='imagens_admin/azul.gif' align='center'>Dados do Usuário</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";

		echo "<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>";
		echo "<tr class='Label'>\n";
		echo "<input type='hidden' name='pessoa' value='$pessoa'>\n";
		echo "<td height='20'>Nome:&nbsp;</td>";
		echo "<td nowrap bgcolor='$cor'><input type='text' name='nome_completo' size='30' maxlength='' value='$nome_completo'";
		echo "></td>\n";
		echo "<td height='20'>Fone Residencial:&nbsp;</td>";
		echo "<td bgcolor='$cor' ><input type='text' name='fone_residencial' value='$fone_residencial' size='15' maxlength='' value='$fone'";
		echo "></td>\n";
		echo "</tr>";
		echo "<tr>";
		echo "<td height='20'>Email:&nbsp;</td>";
		echo "<td nowrap bgcolor='$cor'><input type='text' name='email' size='30' maxlength='' value='$email'";
		echo "></td>\n";
		echo "<td height='20'>Fone Comercial:&nbsp;</td>";
		echo "<td bgcolor='$cor' ><input type='text' name='fone_comercial' value='$fone_comercial' size='15' maxlength='' value='$fone'";
		echo "></td>\n";
		echo "<tr>";
					echo "<td height='20' >Senha Nova:&nbsp;</td>";
					echo "<td ><INPUT TYPE='password' NAME='senha_nova' value='$senha' CLASS='Caixa' size='15' maxlength rel='ajuda' title='Sua senha deverá conter no mínimo 6 digitos e no máximo 10 digitos, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9)'></td>";
					echo "<td height='20' >Repetir Senha:&nbsp;</td>";
					echo "<td><INPUT TYPE='password' NAME='senha_nova2' value='$senha' CLASS='Caixa' size='15'>";
		echo "</tr>";
		echo "</table>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
		echo "<center><br><input type='submit' name='btn_gravar' value='gravar'></center>";
	echo "</form>";
		}
	}

