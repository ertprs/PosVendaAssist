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
	$capital_interior	= trim($_POST ['capital_interior']);
	$pais				= trim($_POST ['pais']);

	if (strlen($confirmar_senha) > 0) {
		$xconfirmar_senha = strtolower($confirmar_senha);
	}else{
		if($pais<>'BR') $msg_erro = "Confirme su clave";
		else            $msg_erro = "Confirme sua senha.";
	}

	

	//Wellington 31/08/2006 - MINIMO 6 CARACTERES SENDO UM MINIMO DE 2 LETRAS E MINIMO DE 2 NUMEROS
	if (strlen($senha) > 0) {
		if (strlen(trim($senha)) >= 6) {
			$senha = strtolower($senha);
			
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

			if ($count_letras < 2)  {
				if($pais<>'BR') $msg_erro = "Clave inválida, la clave debe tener al minus 2 letras.";
				else            $msg_erro = "Senha inválida, a senha deve ter pelo menos 2 letras.";
			}
			if ($count_numeros < 2){
				if($pais<>'BR') $msg_erro = "Clave inválida, la clave debe tener al minus 2 numeros.";
				else            $msg_erro = "Senha inválida, a senha deve ter pelo menos 2 números.";
			}
		}else{
			if($pais<>'BR') $msg_erro = "La clave debe tener el mínimo de 6 caracteres.";
			else            $msg_erro = "A senha deve conter um mínimo de 6 caracteres.";
		}

		$xsenha = "'".$senha."'";
	}else{
		if($pais<>'BR') $msg_erro = "El campo de clave no puede estar vacío";
		else            $msg_erro = "Digite uma senha";
	}
	//
	
	
		
	if (strlen($fabrica) > 0) {
		$xfabrica = "'".$fabrica."'";
	}else{
		if($pais<>'BR') $msg_erro = "Elija la planta";
		else            $msg_erro = "Selecione a fábrica.";
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
		if($pais<>'BR') $msg_erro = "Clave inválida. Por favor, digite una nueva clave para está planta";
		else            $msg_erro = "Senha inválida. Por favor, digite uma nova senha para esta fábrica.";
	}
	// verifica se o posto já tem codigo e senha cadastrados para alguma outra fábrica
	

	if (strlen($msg_erro) == 0) {
		if($senha == $xconfirmar_senha){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			$sql = "SELECT 	tbl_posto.posto    ,
							tbl_posto.email    ,
							tbl_posto_fabrica.*,
							tbl_posto.pais     ,
							tbl_posto_fabrica.oid AS oid_posto_fabrica
					FROM   	tbl_posto
					JOIN    tbl_posto_fabrica USING (posto)
					WHERE  	tbl_posto_fabrica.posto   = $posto
					AND	tbl_posto_fabrica.fabrica = $xfabrica";
			$res = @pg_exec($con,$sql);

			$codigo_posto      = pg_result($res,0,codigo_posto);
			$pais             = trim(pg_result($res,0,pais));
			if(strlen($pais)==0)$pais = "BR";
			$oid_posto_fabrica = pg_result($res,0,oid_posto_fabrica);
			$email = pg_result($res,0,email);

			if (strlen($email) == 0) 
				if($pais<>'BR') $msg_erro = "No fue encuentrado un correo catastrado para su servicio, entre en contacto com el fabricante y solicite el catastramiento de su email";
				else            $msg_erro = "Não foi encontrado um email cadastrado para seu posto, entre em contato com o fabricante e solicite o cadastramento do seu email.";

			if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);

			// grava posto_fabrica
			if (strlen($msg_erro) == 0){
				if (pg_numrows ($res) > 0) {
					$sql = "UPDATE tbl_posto_fabrica SET
								senha             = $xsenha                        ,
								data_expira_senha = current_date + interval '90day',
								login_provisorio = 't'
							WHERE tbl_posto_fabrica.posto   = $posto
							AND   tbl_posto_fabrica.fabrica = $xfabrica ";
					$res = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

					$sql = "UPDATE tbl_posto SET 
							capital_interior = upper($xcapital_interior)
						WHERE tbl_posto.posto = $posto";
					$res = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

				}else{
					if($pais<>'BR') $msg_erro = "No fue posible catastrar su clave";
					else            $msg_erro = "Não foi possível cadastrar sua nova senha.";
				}
			}
		}else {
if ($ip == "201.92.127.116") echo $senha." = ".$xconfirmar_senha;

			if($pais<>'BR') $msg_erro = "Los campos clave y confirmar clave están diferentes";
			else            $msg_erro = "Os campos senha e confirmar senha estão diferentes.";

		}
	}

	if (strlen($msg_erro) == 0){

		$key = md5($codigo_posto);
		// envia email
		$assunto = "Seus dados de acesso ao Sistema - Telecontrol";

		$mens_corpo  = "\n";
		$mens_corpo .= " Seguem os dados de acesso ao sistema: <p>\n\n";
		$mens_corpo .= " Login: $codigo_posto<br> \n";
		$mens_corpo .= " Senha: $senha <p>\n\n";
		$mens_corpo .= " Para liberar seu acesso ao site, clique no link abaixo:<br>\n";
		$mens_corpo .= "<a href=' libera_senha.php?id=$key&id2=$codigo_posto&id3=$fabrica&id4=$oid_posto_fabrica'><font color='#0000FF'><b>CLIQUE AQUI</b></font></a> <p>Caso não consiga, copie e cole o endereço a seguir no seu navegador:<font color='FF0000'> http://posvenda.telecontrol.com.br/assist/libera_senha.php?id=$key&id2=$codigo_posto&id3=$fabrica&id4=$oid_posto_fabrica</font><p>\n\n\n";
		$mens_corpo .= " ---------------------------------------- <br>\n";
		$mens_corpo .= " TELECONTROL NETWORKING";

		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		if($pais<>'BR'){
			$assunto = "Sus datos de acceso al sistema - Telecontrol";

			$mens_corpo  = "\n";
			$mens_corpo .= " Dirección para acceso: http://posvenda.telecontrol.com.br/assist/ <p>\n\n";
			$mens_corpo .= " Seguem os dados de acesso ao sistema: <p>\n\n";
			$mens_corpo .= " Login: <b>$codigo_posto</b> <br>\n";
			$mens_corpo .= " Clave: <b>$senha</b><br>\n\n";
			$mens_corpo .= " Para liberar su acceso al sitio, haga um clic en el link abajo:\n";
			$mens_corpo .= "<a href='http://posvenda.telecontrol.com.br/assist/libera_senha.php?id=$key&id2=$codigo_posto&id3=$fabrica&id4=$oid_posto_fabrica'><font color='#0000FF'><b>CLIC AQUI</b></font></a><p> <p>Caso não consiga, copie e cole o endereço a seguir no seu navegador:<font color='FF0000'> http://posvenda.telecontrol.com.br/assist/libera_senha.php?id=$key&id2=$codigo_posto&id3=$fabrica&id4=$oid_posto_fabrica</font><p>\n\n\n";
			$mens_corpo .= " ---------------------------------------- <br>\n";
			$mens_corpo .= " TELECONTROL NETWORKING";

		}
		$email_from = "From: TELECONTROL <helpdesk@telecontrol.com.br>";
		if(!mail($email, utf8_encode($assunto), utf8_encode($mens_corpo), $email_from."\n $body_top")){
			$msg_erro = "Erro no envio de email de confirmação. Por favor, entre em contato com o suporte.";
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		$key = md5($posto);
		header ("Location: $PHP_SELF?posto=$posto&fabrica=$fabrica&key=$key");
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
		$pais             = trim(pg_result($res,0,pais));
		if(strlen($pais)==0)$pais = "BR";
	}
}





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
.Info{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	padding-left: 5px;
	padding-right:5px;
	margin-left:  10px;
	font-size:    12px;
}


#primeiro label{
	display:block;
	width:150px;
	float:left;
	border-bottom:1px dotted #cccccc;
	clear:both;
}
</style>

<?
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='1' bgcolor='#FF8080'>";
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
<br><br><br><br><center><img src='logos/telecontrol_new.gif'></center><br>
<?
$key = '';
$key     = $_GET["key"]    ;
if(strlen($key)>0){
	$fabrica = $_GET["fabrica"];
	$posto   = $_GET["posto"]  ;
	$posto_key = md5($posto);


	if ($key == $posto_key){
		$sql = "SELECT  tbl_posto.nome                   AS posto_nome  ,
				tbl_posto.cnpj                   AS posto_cnpj  ,
				tbl_posto.email                  AS posto_email ,
				tbl_posto.pais                                  ,
				tbl_posto_fabrica.codigo_posto                  ,
				tbl_posto_fabrica.senha                         ,
				tbl_fabrica.nome                 AS fabrica_nome
			FROM tbl_posto 
			JOIN tbl_posto_fabrica USING (posto)
			JOIN tbl_fabrica       USING (fabrica)
			WHERE tbl_posto.posto =$posto 
			AND tbl_posto_fabrica.fabrica = $fabrica";
	
		$res = @pg_exec ($con,$sql);
	
		if (pg_numrows ($res) == 1) {
			$posto_nome       = pg_result ($res,0,posto_nome)     ;
			$posto_cnpj       = pg_result ($res,0,posto_cnpj)     ;
			$posto_email      = pg_result ($res,0,posto_email)    ;
			$pais             = pg_result ($res,0,pais)    ;
			$codigo_posto     = pg_result ($res,0,codigo_posto)   ;
			$senha            = pg_result($res,0,senha)           ;
			$fabrica_nome     = pg_result($res,0,fabrica_nome)    ;



	if($pais<>'BR'){
		echo "<table style=' border: #DDDDDD 1px solid; background-color: #FDFDFD ' width='650' align='center'><tr><td class='Info' align='justify' > ";
		echo "<b><font size='2'><center>Congratulaciones usted ha hecho el primer acceso</center></font></b><br><br>";   
		echo "&nbsp;&nbsp;&nbsp;&nbsp;Ahora usted puede acceder el  <font color='3366ff'>Assist Telecontrol</font>, un moderno software de gerenciamiento de servicios tecnicos ON-LINE .<br>";  
		echo "&nbsp;&nbsp;&nbsp;&nbsp;  Fue enviado un correo para: <i>$posto_email</i> con sus datos usted puede logarse al sistema <br>&nbsp;&nbsp;&nbsp;&nbsp; Junto al e-mail sigue un link de confirmación, usted debe acceder la dirección correspondiente para que su acceso al sistema sea liberado Caso no reciba el correo en el máximo 30 minutos, entre en contacto con el soporte para que su acceso sea liberado.<br><br>";
		echo "<div id='primeiro'style='padding-left:50px;'>";
		echo "<u>Datos catastrales:</u><br>";
		echo "<label><b>Planta</b></label> $fabrica_nome<br>"; 
		echo "<label><b>Servicio</b></label> $posto_nome<br>";
		echo "<label><b>Identificación</b></label> $posto_cnpj<br>";
	
		echo "<br><u> Datos de acceso:</u><br>"; 
	

		echo "<label><b>Código Servicio </b></label> <font color='FF0000'>Confirmar correo</font><br>";
		echo "<label><b>Clave </b></label> <font color='FF0000'>Confirmar correo</font><br>";

	
		echo "<br><u>Acceso:</u><br>";
		
		if($fabrica == 20) echo "<label><b>Página</b></label><i>www.bosch.com.br/assist</i><br>";
		else               echo "<label><b>Página</b></label><i>posvenda.telecontrol.com.br/assist</i><br>";
		echo "</div>";

	}else{

		echo "<table style=' border: #DDDDDD 1px solid; background-color: #FDFDFD ' width='650' align='center'><tr><td class='Info' align='justify' > ";
		echo "<b><font size='2'><center>Parabéns, você fez o primeiro acesso!</center></font></b><br><br>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;Agora você pode acessar o <font color='3366ff'>Assist Telecontrol</font>, um moderno software de gerenciamento de assistências técnicas ON-LINE.<br>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;Foi enviado um email para: <i>$posto_email</i> com seus dados cadastrais para que você possa se logar ao sistema.<br>&nbsp;&nbsp;&nbsp;&nbsp;Junto ao email segue um link de confirmação, você deve acessar o endereço correspondente para que seu acesso ao sistema seja liberado. Caso não receba o email em no máximo 30 minutos, entre em contato com o suporte para que seu acesso seja liberado.<br><br>";
		echo "<div id='primeiro'style='padding-left:50px;'>";
		
		echo "<u>Dados Cadastrais:</u><br>";
		echo "<label><b>Fabrica</b></label> $fabrica_nome<br>";
		echo "<label><b>Posto</b></label> $posto_nome<br>";
		echo "<label><b>CNPJ</b></label> $posto_cnpj<br>";
		
		echo "<br><u>Dados de Acesso:</u><br>";
		
		/* DESABILITADO POR WELLINGTON 24/01/2004 (NÃO EXIBIR SENHA ATÉ QUE PA CONFIRME EMAIL) */
		echo "<label><b>Código Posto</b></label> <font color='FF0000'>Confirmar Email</font><br>";
		echo "<label><b>Senha</b></label> <font color='FF0000'>Confirmar Email</font><br>";
		
		echo "<br><u>Endereço de Acesso:</u><br>";
		
		if($fabrica == 20) echo "<label><b>Página</b></label><i>www.bosch.com.br/assist</i><br>";
		else               echo "<label><b>Página</b></label><i>posvenda.telecontrol.com.br/assist</i><br>";
		echo "</div>";
	
		echo "<br><br>Página de acesso: ";
	
		if($fabrica == 20) echo "<a href='/assist/bosch.php'>";
		else               echo "<a href='/assist/'>"         ;
		echo "Clique aqui</a> para ir a página inicial do Assist Telecontrol";
		echo "</td></tr></table>";
	}

		}
	}
}else{

?>


<form name="frm_cadastro_senha" method="post" action='<? echo $PHP_SELF ?>'>
<input type="hidden" name="posto" value="<? echo $posto ?>">
<input type="hidden" name="pais" value="<? echo $pais ?>">

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
					<td align='right' height='20'> <?if($pais<>'BR') echo "Planta";else echo "Fábrica:";?></td>
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
						//echo $sql;
						if (pg_numrows($res) > 0){
							echo "<SELECT NAME = 'fabrica' class='Caixa'>";
							echo "	<option selected></option>";
							for($i=0; $i<pg_numrows($res); $i++){
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
				<tr>
					<td align='right' height='20' ><?if($pais<>'BR') echo "Nueva Clave";else echo "Senha Nova:";?></td>
					<td><input type="password" name="senha" size="20" maxlength="10" value="<? echo $senha ?>"  class='Caixa'>
					</td>
				</tr>
				<tr>
					<td align='right' height='20'><?if($pais<>'BR') echo "Repetir clave";else echo "Repetir Senha:";?></td>
					<td><input type="password" name="confirmar_senha" size="20" maxlength="10" value="<? echo $xconfirmar_senha ?>" class='Caixa'>
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
				<!-- DESABILITADO POR WELLINGTON 24/01/2007-->
				<!--
				<tr>
					<td align='right' height='20'><?if($pais<>'BR') echo "Email";else echo "Email:";?></td>
					<td><input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>" class='Caixa'></td>
				</tr>
				-->
				<tr><td colspan='2'><?if($pais<>'BR') echo "Su clave debe ser compuesta por mínimo 6 digitos y maximo 10 digitos, minimo 2 letras ( de A a Z) y 2 números (de 0 a 9)";else echo "Sua senha deverá conter no mínimo 6 digitos e no máximo 10 digitos, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9)";?></td></tr>
			</table>
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
}
//include "rodape.php";
?>
