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
	$capital_interior	= trim($_POST ['capital_interior']);

	if (strlen($confirmar_senha) > 0) {
		$xconfirmar_senha = "'".$confirmar_senha."'";
	}else{
		$msg_erro = "Confirme sua senha.";
	}

	

	//Wellington 31/08/2006 - MINIMO 6 CARACTERES SENDO UM MINIMO DE 2 LETRAS E MINIMO DE 2 NUMEROS
	if (strlen($senha) > 0) {
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

		$xsenha = "'".$senha."'";
	}else{
		$msg_erro = "Digite uma senha";
	}
	//
	
	
		
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
	
	if (strlen($capital_interior) > 0)
		$xcapital_interior = "'".$capital_interior."'";
	else
		$xcapital_interior = 'null';
	
	// verifica se o posto já tem codigo e senha cadastrados para alguma outra fábrica
	$sql = "SELECT tbl_posto_fabrica.fabrica
			FROM   tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.posto   = $posto
			AND    tbl_posto_fabrica.senha   = $xsenha
			AND    tbl_posto_fabrica.fabrica <> $xfabrica";
	$res = @pg_exec($con,$sql);
	
	if (@pg_numrows ($res) > 0) {
		$msg_erro = "Senha inválida. Por favor, digite uma nova senha para esta fábrica.";
	}
	// verifica se o posto já tem codigo e senha cadastrados para alguma outra fábrica
	

	if (strlen($msg_erro) == 0) {
		if($senha == $confirmar_senha){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			$sql = "SELECT 	tbl_posto.posto    ,
							tbl_posto.email    ,
							tbl_posto_fabrica.*,
							tbl_posto_fabrica.oid AS oid_posto_fabrica
					FROM   	tbl_posto
					JOIN    tbl_posto_fabrica USING (posto)
					WHERE  	tbl_posto_fabrica.posto   = $posto
					AND	    tbl_posto_fabrica.fabrica = $xfabrica";
			$res = @pg_exec($con,$sql);

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

					$sql = "UPDATE tbl_posto SET 
											email = $xemail, 
											capital_interior = upper($xcapital_interior)
							WHERE tbl_posto.posto = $posto";
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
		//$email = 'renata@telecontrol.com.br';
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
	$res = @pg_exec($con,$sql);

	if (pg_numrows ($res) == 0) {
		$msg_erro = "CNPJ não cadastrado.";
		header("Location: index.php?msg_erro=$msg_erro");
		exit;
	}else{
		$cnpj             = pg_result ($res,0,cnpj);
		$posto            = pg_result ($res,0,posto);
		$nome             = pg_result ($res,0,nome);
		$capital_interior = pg_result($res,0,capital_interior);
	}
}

$visual_black = "manutencao-admin";

$title     = "Cadastro de Senha";
$cabecalho = "Senha";

$layout_menu = "cadastro";

//include 'cabecalho_novologin.php';

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
echo $msg_debug;
?>
<br><br><br><br><br><br><br>
<form name="frm_cadastro_senha" method="post" action='<? echo $PHP_SELF ?>'>
<input type="hidden" name="posto" value="<? echo $posto ?>">

<table class="border" width='330px' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="2" align='center'>
			<img src="imagens/cab_informacoescadastrais2.gif">
		</td>
	</tr>
	<tr>
		<td colspan="2" align='center' class='table_line'>
			<? echo $nome ?>
		</td>
	</tr>
	<tr class="menu_top">
		<td colspan = '2'> FABRICA </td>
	</tr>
	<tr class="table_line">
		<td colspan='2'>
			<?
			$sql = "SELECT  tbl_posto_fabrica.*,
							tbl_fabrica.fabrica,
							tbl_fabrica.nome   
					FROM    tbl_posto_fabrica
					JOIN    tbl_fabrica USING (fabrica)
					WHERE   tbl_posto_fabrica.posto = $posto
					ORDER BY tbl_fabrica.nome;";
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
		</td>
	</tr> 
	<tr class="menu_top">
		<td width nowrap>NOVA SENHA</td>
		<td width nowrap>CONFIRMAR NOVA SENHA</td>
	</tr>
	<tr class="table_line">
		<td><input type="password" name="senha" size="10" maxlength="10" value="<? echo $senha ?>" ></td>
		<td><input type="password" name="confirmar_senha" size="10" maxlength="10" value="<? echo $confirmar_senha ?>" ></td>
	</tr>
	<tr class="menu_top">
		<td colspan='2'>CAPITAL/INTERIOR</td>
	</tr>
	<tr class="table_line">
		<td colspan='2'>
			<select name='capital_interior' size='1'>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> >Capital</option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> >Interior</option>
			</select>		
		</td>
	</tr>	
	<tr class="menu_top">
		<td colspan='2'>email</td>
	</tr>
	<tr class="table_line">
		<td colspan='2'><input type="text" name="email" size="40" maxlength="50" value="<? echo $email ?>" >
		<font size = '1'><br>
* A senha deve ser composta por no mínimo 6 caracteres, 
  sendo no mínimo 2 LETRAS e no mínimo 2 NÚMEROS.<br>
  ex: ab123456
</font>
		</td>
	</tr>
</table>
<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_cadastro_senha.btn_acao.value == '' ) { document.frm_cadastro_senha.btn_acao.value='gravar' ; document.frm_cadastro_senha.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>

</center>
<br>
</form>

<? 
//include "rodape.php";
?>