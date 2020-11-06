<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

if( $_POST['key1']) $key1 = $_POST['key1'];
if( $_POST['key2']) $key1 = $_POST['key2'];

if(strlen($key2)>0){
	if(strlen($key1)>0){

		$login_posto=$key2;
		$key2=md5($key2);

		if($key1==$key2){
			$sql = "BEGIN";
			$res = @pg_exec ($con,$sql);

			$sql =	"UPDATE tbl_posto SET
					email_validado = CURRENT_TIMESTAMP
					WHERE posto = $login_posto";
			
			$res = @pg_exec ($con,$sql);
			
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro)>0){
				$sql = "rollback";
				$res = @pg_exec ($con,$sql);
				if($sistema_lingua == "ES") $msg_erro = 'No fue posible atualizar la fecha de validación';
				else                        $msg_erro = 'Não foi possível atualizar a Data de Validação';
					
			}
			else{
				$sql = "commit";
				$res = @pg_exec ($con,$sql);
				if($sistema_lingua == "ES") $msg = "<br>Código de verificación correcto!<br>Clic abajo para ir a la tela inicial da  pantalla inicial de Telecontrol";
				else                        $msg = "<br>Código de verificação correto!<br> Clique abaixo para ir para tela inicial da Telecontrol";
			}
		}else{
		$msg_erro ='Chave Inválida';
		}
	}
}
?>
<html>
<head>
<title>Verificação de ID - Telecontrol</title>

<style type="text/css">
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
a{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10 px;
 	text-decoration: none;
	color: #003399;
	font-weight: bold;
}
a:hover{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10 px;
	color: #FF9900;
	font-weight: bold;
}

a:active {
	color: #333399;
	font-weight: bold;
	text-decoration: none;
}
-->
</style>
</head>
<body>
<center><img src='logos/telecontrol_new.gif'><BR></center><br>
<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='400'>
	<tr>
<?
if(strlen($msg)>0){
	echo "<td class='Exibe'>$msg";
}
if(strlen($msg_erro)>0)echo "<td class='Exibe'><br><font color='990000'><b>$msg_erro</b></font>";
?>
		</td>
	</tr>
	<tr>
		<td align = "CENTER">
<?
echo "<br><a href='http://www.telecontrol.com.br'><font size='3'>www.telecontrol.com.br</a>"; 
?>
		</td>
	</tr>
</table>
</body>