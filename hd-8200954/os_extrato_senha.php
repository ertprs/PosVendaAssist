<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
//include 'autentica_usuario_financeiro.php';


if (strlen($_GET['acao'])) $acao       = $_GET['acao'];
if (strlen($_POST['acao'])) $acao       = $_POST['acao'];
$btn_gravar = $_POST['btn_gravar'];

if (strlen($btn_gravar) > 0) {

	$senha_nova= $_POST['senha_nova'];
	$senha_nova2= $_POST['senha_nova2'];

	if($senha_nova == $senha_nova2){
		$sql = "UPDATE tbl_posto_fabrica SET senha_financeiro = '$senha_nova'
				WHERE posto   = $login_posto
				  AND fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		if(strlen($msg_erro)==0) 
			if($acao=='inserir') {
				if($sistema_lingua == "ES") $info = "¡Clave creada con éxito!";
				else                        $info = "A senha foi cadastrada com sucesso!";
			}else {
				if($sistema_lingua == "ES") $info = "¡Clave alterada!";
				else                        $info = "A senha foi alterada com sucesso!";
			}
	}else{
		if($sistema_lingua == "ES") $msg_erro = "¡Las claves no coinciden!";
		else                        $msg_erro = "Senhas não conferem!";
	}
}
if (strlen($_GET['aceita']))	$aceita	= $_GET['aceita'];
if (strlen($_POST['aceita']))	$aceita	= $_POST['aceita'];

if($acao=='libera' and $aceita=='s'){
	$sql = "UPDATE tbl_posto_fabrica SET senha_financeiro = NULL
			WHERE posto   = $login_posto
			  AND fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	$msg_erro = pg_last_error($con);
	$msg_erro = substr($msg_erro, 6);
	if(strlen($msg_erro)==0){
		if($sistema_lingua == "ES") $info = "¡Acceso libre!";
		else                        $info = "Acesso Liberado!";
	}
}

$sql_sf = "SELECT senha_financeiro FROM tbl_posto_fabrica
			WHERE posto = $login_posto
			  AND fabrica = $login_fabrica
			  AND senha_financeiro IS NOT NULL
			  AND senha_financeiro != ''";
if (pg_num_rows(pg_query($con, $sql_sf))>0) $tem_senha_financeiro = true;

$layout_menu = "os";
$title = "Senha do Financeiro";
if($sistema_lingua == "ES") $title = "Clave del área Administrativa";
include "cabecalho.php";
?>


<style type="text/css">

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.TituloConsulta {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: 10px;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #D9E2EF;
}
.Mensagem{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#7192C4;
	font-weight: bold;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
}

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>

<? 

if($acao<>'inserir' AND $acao<>'libera'){ ?>
<table style='font-family: verdana; font-size: 10px; color:#A8A7AD' width='200' align='center'>
	<tr>
		<td><a href='<?=$PHP_SELF?>?acao=<?=($tem_senha_financeiro)?'alterar':'inserir'?>'><?
			if ($tem_senha_financeiro) {    // Se tem senha, alterar
				echo ($sistema_lingua == "ES") ? "Alterar Clave" : "Alterar Senha";
			} else {    // Se não tem, cadastrar
				echo ($sistema_lingua == "ES") ? "Crear Clave" : "Cadastrar Senha";
			} ?>
			</a>
		</td>
		<td><? echo"<a href='$PHP_SELF?acao=libera'>Liberar tela</a>"; ?></td>
	</tr>
</table>
<?
} 

if($acao=='inserir' OR strlen($info)>0 OR $acao=='libera'){
	echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF'  width='60'><img src='imagens/info.jpg' align='middle'></td><td  class='Mensagem' bgcolor='FFFFFF'>";
	
	if(strlen($info)>0)      echo $info;
	elseif($acao=='inserir') {
		if($sistema_lingua == "ES") echo "Al crear una clave usted bloqueará el acceso de visualización del extracto, y sólo podrá acceder con la clave que escriba en el campo abajo. ¡Cree su clave!";
		else                        echo "Ao inserir uma senha você irá bloquear o acesso à visualização do extrato do posto, e conseguirá acessa-lo somente através da senha que você irá preencher nos campos abaixo. Cadastre sua senha!";
	}elseif($acao=='libera') {
		if($sistema_lingua == "ES") echo "Al hacer clic abajo usted está DE ACUERDO con LIBERAR el acceso a los EXTRACTOS sin que sea solicitada una clave de acceso.";
		else                        echo "Ao clicar no botão abaixo você vai estar CONCORDANDO e LIBERANDO o acesso aos EXTRATOS sem que seja digitada a senha para o acesso.";
	}
	echo "</td>";
	echo "</tr>";
	echo "</table><br>";
}

if(strlen($info)==0){
	if(strlen($msg_erro)>0){
		echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
		echo "<tr >";
		echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF'> $msg_erro</td>";
		echo "</tr>";
		echo "</table><br>";
	}

	if($acao=='alterar' OR $acao=='inserir') {
		echo "<form name='frm_gravar' method='post' action='$PHP_SELF' align='center'>";
		echo "<table class='Tabela' width='300' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>";
		echo "<tr >";
		echo "<td class='Titulo'>";
		if($sistema_lingua == "ES") echo "Clave de Usuario";
		else                        echo "Senha do Usuário";
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td bgcolor='#F3F8FE'>";
		echo "<TABLE width='100%' border='0' cellspacing='1' cellpadding='2' CLASS='table_line' bgcolor='#F3F8FE'>";
		echo "<tr class='Conteudo' >";
		echo "<TD colspan='4' style='text-align: center;'><br>";
		if($sistema_lingua == "ES") echo "Por favor teclee la nueva clave.";
		else                        echo "Por favor digite a nova senha.";
		echo "</TD>";
		echo "</tr>";
		echo "<TR width='100%'  >";
		echo "<td colspan='2'  align='right' height='40'>";
		if($sistema_lingua == "ES") echo "Clave";
		else                        echo "Senha";
		echo "&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova' maxlength='10' ></td>";
		echo "</tr>";
		echo "<TR width='100%'  >";
		echo "<td colspan='2'  align='right' height='40'>";
		if($sistema_lingua == "ES") echo "Repetir Clave:";
		else                        echo "Repetir Senha:";
		echo "&nbsp;</td>";
		echo "<td colspan='2'><INPUT TYPE='password' NAME='senha_nova2' maxlength='10' ></td>";
		echo "</tr>";
		echo "<tr class='Conteudo' >";
		echo "<TD colspan='4' style='text-align: center;'>";
		echo "<br><input type='submit' name='btn_gravar' value='";if($sistema_lingua == "ES")echo "Grabar"; else echo "Gravar";echo "'><input type='hidden' name='acao' value=$acao>";
		echo "</TD>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	//alterar senha
	}elseif($acao=='libera' ){
		echo "<a href='$PHP_SELF?aceita=s&acao=libera'>";
		if($sistema_lingua == "ES")echo " Yo quiero liberar el acceso";
		else echo " Eu quero liberar o acesso";

		echo "</a>";
	}
}
include 'rodape.php';
