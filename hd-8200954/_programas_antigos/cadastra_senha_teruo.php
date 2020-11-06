
<?
 include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (strlen($_POST['btn_acao']) > 0) 
	$btn_acao = $_POST['btn_acao'];

if (strlen($_POST['cnpj']) > 0) 
	$cnpj = $_POST['cnpj'];

if (strlen($_GET['cnpj']) > 0) 
	$cnpj = $_GET['cnpj'];

if ($btn_acao == "gravar"){
	$posto				= trim($_POST ['posto']);
	$fabrica			= trim($_POST ['fabrica']);
	$senha				= trim($_POST ['senha']);
	$confirmar_senha	= trim($_POST ['confirmar_senha']);
	$email				= trim($_POST ['email']);

	if (strlen($confirmar_senha) > 0) {
		$xconfirmar_senha = "'".$confirmar_senha."'";
	}else{
		$msg_erro = "Confirme sua senha.";
	}

	if (strlen($senha) > 0) {
		$xsenha = "'".$senha."'";
	}else{
		$msg_erro = "Digite sua senha.";
	}
	
	if (strlen($fabrica) > 0) {
		$xfabrica = "'".$fabrica."'";
	}else{
		$msg_erro = "Selecione a fábrica.";
	}

	if (strlen($email) > 0) {
		$xemail = "'".$email."'";
	}else{
		$msg_erro = "Preencha seu email.";
	}


	if (strlen($msg_erro) == 0) {
		if($senha == $confirmar_senha){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			$sql = "SELECT 	tbl_posto.posto    ,
							tbl_posto.email    ,
							tbl_posto_fabrica.*,
							tbl_posto_fabrica.oid AS oid_posto_fabrica
					FROM   	tbl_posto
					JOIN	tbl_posto_fabrica USING (posto)
					WHERE  	tbl_posto_fabrica.posto   = $posto
					AND	    tbl_posto_fabrica.fabrica = $xfabrica";
			$res = pg_exec($con,$sql);
			$codigo_posto      = pg_result($res,0,codigo_posto);
			$oid_posto_fabrica = pg_result($res,0,oid_posto_fabrica);

			if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);

			// grava posto_fabrica
			if (strlen($msg_erro) == 0){
				if (pg_numrows ($res) > 0) {
					$sql = "UPDATE tbl_posto_fabrica SET
								senha            = $xsenha,
								login_provisorio = 't'    
							WHERE tbl_posto_fabrica.posto   = $posto
							AND   tbl_posto_fabrica.fabrica = $xfabrica ";
					$res = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

					$sql = "UPDATE tbl_posto SET email = $xemail WHERE tbl_posto.posto = $posto";
					$res = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

				}else{
					$msg_erro = "Não foi possível cadastrar sua nova senha.";
				}
			}
		}else {
			$msg_erro = "Os campos senha e confirmar senha estão diferentes.";
		}
	}//IF MSG_ERRO

	if (strlen($msg_erro) == 0){
		// envia email
		$assunto = "Seus dados de acesso ao Sistema - Telecontrol";

		$mens_corpo  = "\n";
		$mens_corpo .= " Endereço para acesso: http://www.telecontrol.com.br/assist/ \n\n";
		$mens_corpo .= " Seguem os dados de acesso ao sistema: \n\n";
		$mens_corpo .= " Login: $codigo_posto \n";
		$mens_corpo .= " Senha: $senha \n\n";
		$mens_corpo .= " Para liberar seu acesso ao site, clique no link abaixo:\n";
		$mens_corpo .= " http://www.telecontrol.com.br/assist/libera_senha.php?codigo=$codigo_posto&fabrica=$fabrica&oid_posto_fabrica=$oid_posto_fabrica \n\n\n";
		$mens_corpo .= " ---------------------------------------- \n";
		$mens_corpo .= " TELECONTROL NETWORKING";

		$email_from = "From: TELECONTROL<telecontrol@telecontrol.com.br>";

		if(!mail($email, $assunto, $mens_corpo, $email_from)){
			$msg_erro = "Erro no envio de email de confirmação. Por favor, digite novamente.";
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: index.php?s=1");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

if (strlen ($cnpj) > 0){
	$sql = "SELECT * FROM tbl_posto WHERE	cnpj = '$cnpj'";
	$res = pg_exec($con,$sql);

	if (pg_numrows ($res) == 0) {
		$msg_erro = "CNPJ não cadastrado.";
		header("Location: index.php?msg_erro=$msg_erro");
		exit;
	}else{
		$cnpj  = pg_result ($res,0,cnpj);
		$posto = pg_result ($res,0,posto);
		$nome  = pg_result ($res,0,nome);
	}
}

$visual_black = "manutencao-admin";

$title     = "Cadastro de Senha";
$cabecalho = "Senha";


include 'cabecalho_novologin.php';

?>



<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border-bottom: #ff0000 1px solid;

}

.border {
	border: 0px solid #000000;
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
	font-size: 12px;
	border: #000000 1px solid;
}

select {
	font-size: 14px;
	border: #f0f0f0 1px solid;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#000000;
	background-color: #000000;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#000000;
	background-color: #ffffff
}

img {border: 0px;}
</style>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' bgcolor='#ff0000'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "	<font face='arial, verdana' color='#ffffff'>";
	echo $msg_erro;
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
echo $msg_debug;
?>

<form name="frm_cadastro_senha" method="post" action='<? echo $PHP_SELF ?>'>
<input type="hidden" name="posto" value="<? echo $posto ?>">

<table class="border" width='330px' align='center' border='0' cellpadding="1" cellspacing="3">
<!-- 	<tr>
		<td colspan="2" align='center'>
			<img src="imagens/cab_informacoescadastrais2.gif">
		</td>
	</tr> -->
	<tr>
		<td colspan="2" align='center' class='table_line'>
			<h1><? echo $nome ?><hr></h1>
		</td>
	</tr>
	<tr class="menu_top">
		<td colspan = '2'> FABRICA </td>
	</tr>
	<tr class="table_line">
		<td colspan='2'>
			<?
			$sql = "SELECT 	tbl_posto_fabrica.*,
							tbl_fabrica.fabrica,
							tbl_fabrica.nome   
					FROM   	tbl_posto_fabrica
					JOIN	tbl_fabrica USING (fabrica)
					WHERE  	tbl_posto_fabrica.posto = $posto
					ORDER BY tbl_fabrica.nome";
			$res = @pg_exec($con,$sql);

			if (pg_numrows($res) > 0){
				echo "<SELECT NAME = 'fabrica'>";
				echo "	<option selected></option>";
				for($i; $i<pg_numrows($res); $i++){
					echo "<option value='".pg_result($res,$i,fabrica)."'";
					if ($fabrica == pg_result($res,$i,fabrica)) echo " SELECTED "; 
					echo ">".pg_result($res,$i,nome)."</option>\n";
				}
				echo "</select>";

			}else{
				echo "Não há permissão de acesso às fábricas. <br><br>Entre em contato com seu fabricante e solicite seu cadastramento e liberação de acesso.<br><br>";
				exit;
			}
			?>
		<hr></td>
	</tr> 
	<tr class="menu_top">
		<td width nowrap>NOVA SENHA</td>
		<td width nowrap>CONFIRMAR NOVA SENHA</td>
	</tr>
	<tr class="table_line">
		<td><input type="password" name="senha" size="27" maxlength="10" value="<? echo $senha ?>" ></td>
		<td><input type="password" name="confirmar_senha" size="27" maxlength="10" value="<? echo $confirmar_senha ?>" ></td>
	</tr>
	<tr class="menu_top">
		<td colspan='2'>email</td>
	</tr>
	<tr class="table_line">
		<td colspan='2'><input type="text" name="email" size="61" maxlength="50" value="<? echo $email ?>" ></td>
	</tr>
</table>
<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_gravar_vermelho.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_cadastro_senha.btn_acao.value == '' ) { document.frm_cadastro_senha.btn_acao.value='gravar' ; document.frm_cadastro_senha.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>

</center>
<br>
</form>

<table>
<tr>
	<td>
		<a href='http://www.telecontrol.com.br'><img src='imagens/rodape_novo.gif'></a>
	</td>
</tr>
</table>