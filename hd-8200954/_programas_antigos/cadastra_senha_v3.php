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
	$posto			= trim($_POST ['posto']);
	$fabrica		= trim($_POST ['fabrica']);
	$senha			= trim($_POST ['senha']);
	$confirmar_senha	= trim($_POST ['confirmar_senha']);
	$email			= trim($_POST ['email']);
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

			if ($count_letras < 2)  $msg_erro = "Senha inv�lida, a senha deve ter pelo menos 2 letras.";
			if ($count_numeros < 2) $msg_erro = "Senha inv�lida, a senha deve ter pelo menos 2 n�meros.";
		}else{
			$msg_erro = "A senha deve conter um m�nimo de 6 caracteres.";
		}

		$xsenha = "'".$senha."'";
	}else{
		$msg_erro = "Digite uma senha";
	}
	//
	
	
		
	if (strlen($fabrica) > 0) {
		$xfabrica = "'".$fabrica."'";
	}else{
		$msg_erro = "Selecione a f�brica.";
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
	
	// verifica se o posto j� tem codigo e senha cadastrados para alguma outra f�brica
	$sql = "SELECT tbl_posto_fabrica.fabrica
			FROM   tbl_posto_fabrica
			WHERE  tbl_posto_fabrica.posto   = $posto
			AND    tbl_posto_fabrica.senha   = $xsenha
			AND    tbl_posto_fabrica.fabrica <> $xfabrica";
	$res = @pg_exec($con,$sql);
	
	if (@pg_numrows ($res) > 0) {
		$msg_erro = "Senha inv�lida. Por favor, digite uma nova senha para esta f�brica.";
	}
	// verifica se o posto j� tem codigo e senha cadastrados para alguma outra f�brica
	

	if (strlen($msg_erro) == 0) {
		if($senha == $confirmar_senha){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			$sql = "SELECT 	tbl_posto.posto    ,
							tbl_posto.email    ,
							tbl_posto_fabrica.*,
							tbl_posto.pais     ,
							tbl_posto_fabrica.oid AS oid_posto_fabrica
					FROM   	tbl_posto
					JOIN    tbl_posto_fabrica USING (posto)
					WHERE  	tbl_posto_fabrica.posto   = $posto
					AND	    tbl_posto_fabrica.fabrica = $xfabrica";
			$res = @pg_exec($con,$sql);

			$codigo_posto      = pg_result($res,0,codigo_posto);
			$pais              = pg_result($res,0,pais);
			$oid_posto_fabrica = pg_result($res,0,oid_posto_fabrica);

			if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);

			// grava posto_fabrica
			if (strlen($msg_erro) == 0){
				if (pg_numrows ($res) > 0) {
					$sql = "UPDATE tbl_posto_fabrica SET
								senha             = $xsenha,
								data_expira_senha = current_date + interval '90day',
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
					$msg_erro = "N�o foi poss�vel cadastrar sua nova senha.";
				}
			}
		}else {
			$msg_erro = "Os campos senha e confirmar senha est�o diferentes.";
		}
	}//IF MSG_ERRO

	if (strlen($msg_erro) == 0){
		// envia email
		$assunto = "Seus dados de acesso ao Sistema - Telecontrol";

		$mens_corpo  = "\n";
		$mens_corpo .= " Endere�o para acesso: http://www.telecontrol.com.br/assist/ \n\n";
		$mens_corpo .= " Seguem os dados de acesso ao sistema: \n\n";
		$mens_corpo .= " Login: $codigo_posto \n";
		$mens_corpo .= " Senha: $senha \n\n";
		$mens_corpo .= " Para liberar seu acesso ao site, clique no link abaixo:\n";
		$mens_corpo .= " http://www.telecontrol.com.br/assist/libera_senha.php?codigo=$codigo_posto&fabrica=$fabrica&oid_posto_fabrica=$oid_posto_fabrica \n\n\n";
		$mens_corpo .= " ---------------------------------------- \n";
		$mens_corpo .= " TELECONTROL NETWORKING";

		if($pais<>'BR'){
			$assunto = "Sus datos de acceso al sistema - Telecontrol";

			$mens_corpo  = "\n";
			$mens_corpo .= " Direcci�n para acceso: http://www.telecontrol.com.br/assist/ \n\n";
			$mens_corpo .= " Seguem os dados de acesso ao sistema: \n\n";
			$mens_corpo .= " Login: $codigo_posto \n";
			$mens_corpo .= " Clave: $senha \n\n";
			$mens_corpo .= " Para liberar su acceso al sitio, haga um clic en el link abajo:\n";
			$mens_corpo .= " http://www.telecontrol.com.br/assist/libera_senha.php?codigo=$codigo_posto&fabrica=$fabrica&oid_posto_fabrica=$oid_posto_fabrica \n\n\n";
			$mens_corpo .= " ---------------------------------------- \n";
			$mens_corpo .= " TELECONTROL NETWORKING";

		}
		$email_from = "From: TELECONTROL <suporte@telecontrol.com.br>";
		if(!mail($email, $assunto, $mens_corpo, $email_from)){
			$msg_erro = "Erro no envio de email de confirma��o. Por favor, digite novamente.";
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
		$msg_erro = "CNPJ n�o cadastrado.";
		header("Location: index.php?msg_erro=$msg_erro");
		exit;
	}else{
		$cnpj             = pg_result ($res,0,cnpj);
		$posto            = pg_result ($res,0,posto);
		$nome             = pg_result ($res,0,nome);
		$capital_interior = pg_result($res,0,capital_interior);
		$pais             = pg_result($res,0,pais);
	}
}

$visual_black = "manutencao-admin";

$title     = "Cadastro de Senha";
$cabecalho = "Senha";

$layout_menu = "cadastro";

//include 'cabecalho_novologin.php';

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

<table width='400' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
<tr >
<td class='Titulo' background='admin/imagens_admin/azul.gif' colspan='2'><?if($pais<>'BR') echo "Primero Acceso - Catastro de Clave";else echo "Primeiro Acesso - Cadastro Senha";?></td>
</tr>
	<tr>
		<td bgcolor='#DBE5F5'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>
				<tr>
					<td colspan="2" align='center' bgcolor='#DBE5F5'>
						<? echo $nome ?>
					</td>
				</tr>
				<tr>
					<td align='right' height='20'> <?if($pais<>'BR') echo "Planta";else echo "F�brica:";?></td>
					<td>
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
							echo "<SELECT NAME = 'fabrica' class='Caixa'>";
							echo "	<option selected></option>";
							for($i; $i<pg_numrows($res); $i++){
								echo "<option value='".pg_result($res,$i,fabrica)."'";
								if ($fabrica == pg_result($res,$i,fabrica)) echo " SELECTED "; 
								echo ">".pg_result($res,$i,nome)."</option>\n";
							}
							echo "</select>";
						}else{
							echo "N�o h� permiss�o de acesso �s f�bricas. <br><br>Entre em contato com seu fabricante e solicite seu cadastramento e libera��o de acesso.<br><br>";
							exit;
						}
						?>
					</td>
				</tr>
				<tr>
					<td align='right' height='20' ><?if($pais<>'BR') echo "Nueva Clave";else echo "Senha Nova:";?></td>
					<td><input type="password" name="senha" size="20" maxlength="10" value="<? echo $senha ?>"  class='Caixa'>
					</td>
				</tr>
				<tr>
					<td align='right' height='20'><?if($pais<>'BR') echo "Repetir clave";else echo "Repetir Senha:";?></td>
					<td><input type="password" name="confirmar_senha" size="20" maxlength="10" value="<? echo $confirmar_senha ?>" class='Caixa'>
					</td>
				<tr>	
					<td align='right' height='20'><?if($pais<>'BR') echo "Capital/Provincia";else echo "Capital/Interior:";?></td>
					<td>
						<select name='capital_interior' size='1' class='Caixa'>
							<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> >Capital</option>
							<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> >Interior</option>
						</select>
					</td>
				</tr>
				<tr>
					<td align='right' height='20'><?if($pais<>'BR') echo "Email";else echo "Email:";?></td>
					<td><input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>" class='Caixa'></td>
				</tr>
				<tr><td colspan='2'><?if($pais<>'BR') echo "Su clave debe ser compuesta por m�nimo 6 digitos y maximo 10 digitos, minimo 2 letras ( de A a Z) y 2 n�meros (de 0 a 9)";else echo "Sua senha dever� conter no m�nimo 6 digitos e no m�ximo 10 digitos, sendo no min�mo 2 letras (de A a Z) e 2 n�meros (de 0 a 9)";?></td></tr>
			</table>
		</td>
	</tr>
</table>
<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_cadastro_senha.btn_acao.value == '' ) { document.frm_cadastro_senha.btn_acao.value='gravar' ; document.frm_cadastro_senha.submit() } else { alert ('Aguarde submiss�o') }" ALT="Gravar formul�rio" border='0'>

</center>
<br>
</form>

<? 
//include "rodape.php";
?>