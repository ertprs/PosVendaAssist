<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$lv_simples    = $_GET['lv_simples'];
$numero_pedido = $_GET['pedido'];
$status        = $_GET['status'];

if (strlen($_POST['btn_acao'])>0) {$btn_acao = trim($_POST['btn_acao']);}
if (strlen($_GET['btn_acao'])>0)  {$btn_acao = trim($_GET['btn_acao']);}

if (strlen($btn_acao)>0){
	$logar     = trim($_GET['logar']);
	$cadastrar = trim($_GET['cadastrar']);
	
	if ($btn_acao=="Acessar" AND $logar=='1'){
		$login = trim($_POST['login']);
		$senha = trim($_POST['senha']);

		if (strlen($login)==0){
			$msg_erro = "Digite seu login!";
		}

		if (strlen($msg_erro)==0 AND strlen($senha)==0){
			$msg_erro = "Digite a senha!";
		}

		if (strlen($msg_erro)==0){
			$sql = "SELECT  tbl_posto_fabrica.oid AS posto_fabrica , 
							tbl_posto_fabrica.posto, 
							tbl_posto_fabrica.fabrica, 
							tbl_posto_fabrica.credenciamento, 
							tbl_posto_fabrica.login_provisorio,
							tbl_posto.email_validado,
							tbl_posto.email
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE  lower (tbl_posto.email)         = lower ('$login')
					AND    lower (tbl_posto_fabrica.senha) = lower ('$senha') ";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows ($res) == 1) {
				if (pg_result ($res,0,credenciamento) == 'DESCREDENCIADO') {
					$msg_erro = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
				}else{
					$validado = trim(pg_result ($res,0,email_validado));
					$email    = trim(pg_result ($res,0,email));
					if (strlen($validado)>0){
						#setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
						setcookie ("cook_posto",pg_result ($res,0,posto));
						#setcookie ("cook_fabrica",pg_result ($res,0,fabrica));
						setcookie ("cook_login_simples","sim");
						echo "<script language='JavaScript'>";
						echo "window.location = 'lv_completa.php';";
						echo "</script>";
						exit;
					}else{
						$msg_erro = '<!--OFFLINE-I-->Você precisa validar seu email. No email enviado de confirmação de cadastro, clique no link para validar seu email. <!--OFFLINE-F-->';
					}
				}
			}else{
				$msg_erro = "Login ou senha inválidos";
			}
		}
	}

	if ($btn_acao=="Cadastrar" AND $cadastrar=='1'){
		$nome   = trim($_POST['nome']);
		$email  = trim($_POST['email']);
		$senha  = trim($_POST['senha']);
		$senha2 = trim($_POST['senha2']);
		header("Location: lv_simples_cadastro.php?nome=$nome&email=$email");
		exit;
	}

	if ($btn_acao=="Autenticar") {
		$posto     = trim($_GET['id']);
		$key1      = trim($_GET['key1']);
		if (strlen($posto)>0){
			if (md5($posto) == $key1){
				$sql = "UPDATE tbl_posto SET email_validado = CURRENT_TIMESTAMP WHERE email_validado IS NULL AND posto = ".$posto;
				$res = pg_exec($con,$sql);
				if(strlen(pg_errormessage($con))==0){
					$email_autenticado = "sim";
				}
			}
		}
	}
}

$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual";

include "login_unico_cabecalho.php";



$cook_fabrica = 10;
$login_fabrica = 10;

echo "<div style='position: absolute;opacity:.90;z-index:1; overflow: auto;position:absolute;top:0px;right: 5px;'><table id='mensagem' style='border: 1px solid rgb(211, 190, 150); background-color: rgb(252, 240, 216);' ><tbody><tr><td><b>Carregando dados...</b></td></tr></tbody></table></div>";

?>

<script language='javascript'>
	function checarNumero(campo){
		var num = campo.value.replace(",",".");
		campo.value = parseInt(num);
		if (campo.value=='NaN') {
			campo.value='';
		}
	}
</script>


<style type="text/css">
	ul#intro,ul#intro li{list-style-type:none;margin:0;padding:0}
	ul#intro{width:100%;overflow:hidden;margin-bottom:10px}
	ul#intro li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
	li#produto{background: #CEDFF0}
	ul#intro li#more{margin-right:0;background: #7D63A9}
	ul#intro p,ul#intro h3{margin:0;padding: 0 10px}
	
	ul#intro2,ul#intro2 li{list-style-type:none;margin:0;padding:0}
	ul#intro2{width:100%;overflow:hidden;margin-bottom:10px}
	ul#intro2 li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
	li#infor{text-align:left;background: #0082d7;color:#FFFFFF;}
	ul#intro2 li#more{margin-right:0;background: #7D63A9}
	ul#intro p,ul#intro2 h3{margin:0;padding: 0 10px}

	ul#intro3,ul#intro3 li{list-style-type:none;margin:0;padding:0}
	ul#intro3{width:100%;overflow:hidden;margin-bottom:10px}
	ul#intro3 li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
	li#maisprod{background: #FFBA75}
	ul#intro3 li#more{margin-right:0;background: #7D63A9}
	ul#intro p,ul#intro3 h3{margin:0;padding: 0 10px}

	.sucesso{
		font-size:12px;
		color:#0000FF;
		font-weight:bold;
	}
</style>
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript" src="js/niftyLayout.js"></script>


<?

include 'lv_menu.php';
# AVISO
echo "<BR>"; 
# BUSCA POR PEÇA
echo "<table width='98%' border='0' align='center' cellpadding='2' cellspacing='2'>\n";
echo "<tr>\n";
echo "<td width='170' valign='top'>\n";
/*MENU*/
include "lv_menu_lateral.php";

echo "</td>\n";
echo "<td valign='top' align='right'>\n";
//echo "	<center><img src='imagens/liquidacao2.png' border='0'></center>";
?>
<table width='95%' border='0' align='center' cellpadding='0' cellspacing='0'>

<tr>
<td align='center'>
	<ul id="intro2">
		<li id="infor">
			<font size='3'><B>Loja Virtual - Faça seu login!</b></font>
		</li>
	</ul>
</td>
</tr>

<tr>
<td align='center'>

<?
	if($email_autenticado=="sim"){
		echo "<p class='sucesso'>Seu email foi atenticado com sucesso! Você já pode efetuar o login.</p>";
	}
?>

<div align='center'>

<table cellspacing='20px' border="0" width='100%'>
	<tr>
		<td bgcolor='#D9E8FF' align='center'>
			<form name='frm_login' method='POST' action='<?=$PHP_SELF?>?logar=1'>
			<table >
				<tr>
					<td colspan='2'><p class='titulo2'>Já é cadastrado? Faça seu login!</p><br></td>
				</tr>
				<?
				if (strlen($msg_erro)>0){
					echo "<tr><td colspan='3'><p><b style='color:#FF1515'>".$msg_erro."</b></p><br></td></tr>
					";
				}
				?>
				<tr>
					<td align='right'><label>E-mail: </label></td>
					<td><input type='text' size='30' name='login' value='<?=$login?>'></td>
				</tr>
				<tr>
					<td align='right'>Senha: </td>
					<td><input type='password' size='20' name='senha' value=''></td>
				</tr>
				<tr>
					<td></td>
					<td><input type='submit' name='btn_acao' value='Acessar'></td>
				</tr>
			</table>
			</form>
		</td>
		<td bgcolor='#FEF5DA' align='center'>
			<form name='frm_login' method='POST' action='<?=$PHP_SELF?>?cadastrar=1'>
			<table>
				<tr>
					<td colspan='2'>
						<p class='titulo2'>Não é cadastrado? Faça seu Cadastro!</p><br>
						<p class='aviso'>Antes de efetuar compras na Loja Virtual é preciso que você faça um cadastro.</p></td>
				</tr>
					<td><input type='submit' name='btn_acao' value='Cadastrar'></td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
</table>
</div>
</td>
</tr>
</table>
<script>document.getElementById('mensagem').style.visibility = 'hidden';</script>
<?
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";

include "login_unico_rodape.php";
?>