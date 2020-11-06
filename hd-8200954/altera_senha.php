<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

if (strlen($_POST['btn_acao']) > 0) 
	$btn_acao = $_POST['btn_acao'];

if ($btn_acao == "gravar") {

	// verifica se posto está cadastrado
	$sql = "SELECT senha 
			FROM   tbl_posto_fabrica 
			WHERE  posto = '$login_posto'
			AND    fabrica = '$login_fabrica'";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0){
		$senha_bd = pg_result($res,0,senha);

		$senha_antiga			= trim($_POST ['senha_antiga']);
		$nova_senha				= trim($_POST ['nova_senha']);
		$confirmar_nova_senha	= trim($_POST ['confirmar_nova_senha']);
		$email					= trim($_POST ['email']);
		
		if (strlen($email) > 0)
			$xemail = "'".$email."'";
		else
			$msg_erro = "Digite seu email.";

		if (strlen($confirmar_nova_senha) > 0)
			$xconfirmar_nova_senha = "'".$confirmar_nova_senha."'";
		else
			$msg_erro = "Confirme sua nova senha.";

		if (strlen($nova_senha) > 0)
			$xnova_senha = "'".$nova_senha."'";
		else
			$msg_erro = "Digite sua nova senha.";
		
		if (strlen($senha_antiga) > 0)
			$xsenha_antiga = "'".$senha_antiga."'";
		else
			$msg_erro = "Digite sua senha atual.";

		if (strlen($msg_erro) == 0) {

			if($senha_bd == $senha_antiga){
				if($nova_senha == $confirmar_nova_senha){

					$res = pg_exec ($con,"BEGIN TRANSACTION");
					#----------------------------- Alteração de Dados ---------------------
					if (strlen ($posto) > 0) {
						$sql = "UPDATE tbl_posto SET
									senha = '$senha'
								WHERE tbl_posto.posto           = $login_posto
								AND	  tbl_posto.posto           = tbl_posto_fabrica.posto
								AND	  tbl_posto_fabrica.fabrica = $login_fabrica ";
						$res = @pg_exec($con,$sql);
						if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);
					}
				}else {
					$msg_erro = "Os campos nova senha e confirmar nova senha estão diferentes.";
				}
			}else {
				$msg_erro = "Senha antiga incorreta.";
			}
			
			// grava posto_fabrica
			if (strlen($msg_erro) == 0){
				$senha         = trim ($_POST['nova_senha']);
				$email         = trim ($_POST['email']);

				if (strlen($email) > 0)
					$xemail = "'".$email."'";
				else
					$xemail = 'null';

				$sql = "SELECT	* 
						FROM	tbl_posto_fabrica
						WHERE	posto   = $login_posto
						AND		fabrica = $login_fabrica ";
				$res = pg_exec($con,$sql);
				$total_rows = pg_numrows($res);

				if (pg_numrows ($res) > 0) {
					$sql = "UPDATE tbl_posto_fabrica SET
								senha          = $xnova_senha           
							WHERE tbl_posto_fabrica.posto   = $login_posto
							AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
				}else{
					$msg_erro = "Não foi possível atualizar a nova senha.";
				}
	
				$res = pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}//fim if msg_erro
}

if ($msg_erro == 0){
	$sql = "SELECT  tbl_posto.cnpj        ,
					tbl_posto.nome        
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.posto   = $login_posto ";
	$res = pg_exec ($con,$sql);
	if (@pg_numrows ($res) > 0) {
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
	}
}

$visual_black = "manutencao-admin";

$title     = "Senha";
$cabecalho = "Senha";

$layout_menu = "cadastro";
include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "	<font face='arial, verdana' color='#330000' size='-1'>";
	echo $msg_erro;
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<form name="frm_senha" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="senha" value="<? echo $senha ?>">

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5" align='center'>
			<!-- <img src="imagens/cab_informacoescadastrais.gif"> -->
			Mudança de Senha
		</td>
	</tr>
	<tr class="menu_top">
		<td> CNPJ </td>
		<td COLSPAN='3'>NOME POSTO</td>
	</tr>
	<tr class="table_line">
		<td><? echo $cnpj ?></td>
		<td COLSPAN='3'><? echo $nome ?></td>
	</tr>
	<tr class="menu_top">
		<td width = '33%'>SENHA ANTIGA</td>
		<td width = '33%'>NOVA SENHA</td>
		<td width = '33%'>CONFIRMAR NOVA SENHA</td>
	</tr>
	<tr class="table_line">
		<td><input type="password" name="senha_antiga" size="10" maxlength="10" value="<? echo $senha_antiga ?>"></td>
		<td><input type="password" name="nova_senha" size="10" maxlength="10" value="<? echo $nova_senha ?>" ></td>
		<td><input type="password" name="confirmar_nova_senha" size="10" maxlength="10" value="<? echo $confirmar_nova_senha ?>" ></td>
	</tr>
	<tr class="menu_top">
		<td COLSPAN='3'>EMAIL</td>
	</tr>
	<tr class="table_line">
		<td COLSPAN='3'><input type="text" name="email" size="40" maxlength="50" value="<? echo $email ?>"></td>
	</tr>
	
</table>
<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_senha.btn_acao.value == '' ) { document.frm_senha.btn_acao.value='gravar' ; document.frm_senha.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>

</center>
<br>
</form>

<? include "rodape.php";?>
