<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "../monitora.php";


$btn_acao = strtolower($_POST["btn_acao"]);

if ($btn_acao == 'gravar'){

	$qtde_item = trim($_POST["qtde_item"]);
	$programa  = trim($_POST["programa"]);

	for ($i = 0; $i <= $qtde_item; $i ++){
		$admin        = trim($_POST['admin_'.$i]);
		$liberado     = trim($_POST['liberado_'.$i]);
		
		if (strlen ($admin) > 0) {
			if (strlen($liberado) > 0) {
				$sql = "SELECT * FROM tbl_programa_restrito WHERE programa = '$programa' AND admin = $admin";
				$res = pg_exec($con,$sql);
				
				if (pg_numrows ($res) == 0) {
					$sql = "INSERT INTO tbl_programa_restrito (fabrica, programa, admin) VALUES ($login_fabrica, '$programa',$admin)";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}else{
				$sql = "DELETE FROM tbl_programa_restrito WHERE programa = '$programa' AND admin = $admin";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		header ("Location: $programa");
		exit;
	}
}


$title = "Restrição de Acesso";
$cabecalho = "Programa Restrito";
$layout_menu = "gerencia";
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
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff;
}

</style>

<?
if($msg_erro){
?>

<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} 
?> 
<br>
<p>Clique no nome de um usu&aacute;rio para acessar<br>
   &agrave; p&aacute;gina de <a href='./programa_restrito_gerencia.php'>Gerenciamento de Restri&ccedil;&otilde;es</a>.</p>
<p>

<?

$sql = "SELECT privilegios FROM tbl_admin WHERE admin = $login_admin";
$res = pg_exec ($con,$sql);
$privilegios = pg_result ($res,0,0);

if (strpos ($privilegios,'*') === false ) {
	echo "<center><h1>Apenas usuário MASTER pode realizar restrições de programas.</h1></center>";
	exit;
}

$programa = $_GET['programa'];

echo "Restringindo programa $programa";

echo "<form name='frm_admin' method='post' action='$PHP_SELF '>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='hidden' name='programa' value='$programa'>";

echo "<table class='border' width='250' align='center' border='1' cellpadding='1' cellspacing='3'>";
echo "<tr class='menu_top'>";
echo "<td nowrap>LOGIN</td>";
echo "<td nowrap>ACESSO</td>";
echo "</tr>";

$sql = "SELECT tbl_admin.*, tbl_programa_restrito.admin AS liberado
		FROM   tbl_admin
		LEFT JOIN tbl_programa_restrito USING (admin)
		WHERE tbl_admin.fabrica = $login_fabrica
		AND   (
			tbl_programa_restrito.programa = '$programa'
			OR tbl_programa_restrito.programa IS NULL
		)
		ORDER BY tbl_admin.login";

$sql = "SELECT tbl_admin.*
		FROM   tbl_admin
		WHERE tbl_admin.fabrica = $login_fabrica
		ORDER BY tbl_admin.login";
$resx = pg_exec ($con,$sql);

for ($i = 0; $i < pg_numrows($resx); $i ++){
	$liberado = "";
	$admin = trim(pg_result($resx,$i,admin));
	$login = trim(pg_result($resx,$i,login));
	
	$sql = "SELECT tbl_programa_restrito.admin AS liberado
			FROM   tbl_programa_restrito
			WHERE  tbl_programa_restrito.admin    = $admin
			AND    tbl_programa_restrito.programa = '$programa'";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$liberado = trim(pg_result ($res,0,liberado));
	}
	
	echo "<tr class='table_line'>\n";
	echo "<input type='hidden' name='admin_$i' value='$admin'>\n";
	echo "<td nowrap align='left'><a href='./programa_restrito_gerencia.php?edit_admin=$admin'>$login</td>\n";
	echo "<td><input type='checkbox' name='liberado_$i' value='1'";
	if (strlen($liberado) > 0) echo " checked ";
	echo "> &nbsp;</TD>\n";
	echo "</tr>\n";
}
?>

<input type='hidden' name='qtde_item' value="<? echo $i?>">

</table> 
<br>
<center>
<img src="imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: document.frm_admin.btn_acao.value='gravar' ; document.frm_admin.submit() ; " ALT="Gravar Formulário" border='0'>
</center>
<br>
</form>
<br>
<p>
<? include "rodape.php"; ?>
