<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if (strlen($_GET['acao'])) $acao       = $_GET['acao'];
if (strlen($_POST['acao'])) $acao       = $_POST['acao'];

$btn_gravar = $_POST['btn_gravar'];

if (strlen($btn_gravar) > 0) {

	$senha_nova  = trim($_POST['senha_nova']);
	$senha_nova2 = trim($_POST['senha_nova2']);


	if(strlen($senha_nova) ==0) $msg_erro = "El campo de nueva clave no puede estar vacío";
	if(strlen($senha_nova2)==0) $msg_erro = "El campo para repetir una nueva clave no puede estar vacío";

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
	
				if ($count_letras < 2)  $msg_erro = "Clave inválida, la clave debe tener al minus 2 letras.";
				if ($count_numeros < 2) $msg_erro = "Clave inválida, la clave debe tener al minus 2 numeros.";
			}else{
				$msg_erro = "La clave debe tener el mínimo de 6 caracteres.";
			}


			if(strlen($msg_erro) == 0){

				$sql = "UPDATE tbl_admin SET
						senha = '$senha_nova',
						data_expira_senha = current_date + interval '90day'
						WHERE admin = $login_admin";
				$res = @pg_exec ($con,$sql);
				
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				if(strlen($msg_erro)==0) 
					if($acao=='inserir'){ $info = "La clave fue catastrada con éxito!";
					}else{ 
						$msg_validade_cadastro = "La clave fue cambiada con éxito!";
						
						header("Location: menu_cadastro.php");
					}
			}
		}
	}
}


$title = "Cambiar clave";

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

echo "<center><img src='../logos/telecontrol_new.gif'><BR></center>";
$info = $_GET["ok"];
	
if(strlen($info)>0){
	echo "<br><table style=' border: #D3BE96 1px solid; background-color: #FCF0D8; font-size: 14px;' align='center' width='90%'><tr><td class='Exibe'>";
	echo "<b>Su clave fue catastrada!</b><br>Fue encaminado para su email: $info con el asunto: Validación de claves del ASSIST<br>Favor ingresar en su correo de mensaje y validar la nueva clave.!<br><br><center><a href='login.php'>Click aqui para volver al menu inicial</a></center>";
	echo "</td></tr></table>";
}else{
	if(strlen($msg_erro)>0) echo "<h1 class='Erro'><center>$msg_erro</center></h1>";
	echo "<center><h3>Su clave de acceso caducou, por favor catastre una nueva clave.</h3></center>";
	echo "<FORM name='frm_gravar' METHOD='POST' ACTION='$PHP_SELF' align='center'>";
	echo "<table width='350' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>";

	echo "<tr >";
	echo "<td class='Titulo' background='imagens_admin/azul.gif'>Clave del usuario</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td bgcolor='#DBE5F5'>";

		echo "<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>";
	
		echo "<tr>";
		echo "<td colspan='4'>Su clave deberá contener el mínimo 6 dígitos y máximo 10 digitos, minímo 2 letras (de A a Z) y 2 números (de 0 a 9)<br><br></td>";
		echo "</tr>";
	
		echo "<tr width='100%' ";
		if($msg_erro == "las claves no confieren!") echo "bgcolor='#FFCC00'";
		echo " >";
		echo "<td colspan='2'  align='right' height='20'>Nueva Clave:&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' CLASS='Caixa' maxlength></td>";
		echo "</tr>";
	
		echo "<tr width='100%' ";
		if($msg_erro == "las claves no confieren!") echo "bgcolor='#FFCC00'";
		echo " >";
		echo "<td colspan='2' align='right' height='20' >Repetir Clave:&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' CLASS='Caixa'>";
		echo "</td>";
		echo "</tr>";

		echo "</table>";
	echo "</td>";
	echo "</tr>";


	echo "</table>";
	echo "<center><br><input type='submit' name='btn_gravar' value='Grabar'><input type='hidden' name='acao' value=$acao></center>";
	echo "</form>";


}
