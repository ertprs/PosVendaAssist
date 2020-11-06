<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

if( $_POST['key1']) $key1 = $_POST['key1'];
if( $_POST['key2']) $key2 = $_POST['key2'];
if( $_POST['key3']) $key3 = $_POST['key3'];
if( $_POST['key4']) $key4 = $_POST['key4'];


if(strlen($key2)>0){
	if(strlen($key1)>0){

		$treinamento_posto = $key4;
		$login_posto       = $key2;

		$key2 = md5($key2);
		$key4 = md5($key4);

		if($key1 == $key2 and $key3 == $key4){
			$sql = "BEGIN";
			$res = @pg_exec ($con,$sql);

			$sql =	"UPDATE tbl_treinamento_posto SET
					confirma_inscricao = 't'
					WHERE posto             = $login_posto
					AND   treinamento_posto = $treinamento_posto";
			
			$res = @pg_exec ($con,$sql);
			
			$msg_erro = pg_errormessage($con);

			if(strlen($msg_erro)>0){
				$sql = "rollback";
				$res = @pg_exec ($con,$sql);
				$msg_erro = 'Não foi possível confirmar sua inscrição';
					
			}
			else{
				$sql = "commit";
				$res = @pg_exec ($con,$sql);
				$msg = "<br>Inscrição Confirmado!<br> Clique abaixo para ir para tela inicial da Telecontrol";
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