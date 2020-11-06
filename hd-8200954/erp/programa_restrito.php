<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';

$admin_privilegios="gerencia";
//include 'autentica_admin.php';

$btn_acao = strtolower($_POST["btn_acao"]);

if ($btn_acao == 'gravar'){
	$qtde_item = trim($_POST["qtde_item"]);
	$programa  = trim($_POST["programa"]);

	for ($i = 0; $i <= $qtde_item; $i ++){

		$empregado        = trim($_POST['empregado_'.$i]);
		$liberado     = trim($_POST['liberado_'.$i]);
		
		if (strlen ($empregado) > 0) {
			if (strlen($liberado) > 0) {
				$sql = "SELECT * FROM tbl_erp_programa_restrito WHERE programa = '$programa' AND empregado = $empregado";
				$res = pg_exec($con,$sql);
				
				if (pg_numrows ($res) == 0) {
					$sql = "INSERT INTO tbl_erp_programa_restrito (fabrica, programa, empregado) VALUES ($login_empresa, '$programa',$empregado)";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

			}

			}else{
				$sql = "DELETE FROM tbl_erp_programa_restrito WHERE programa = '$programa' AND empregado = $empregado";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}

	if (strlen ($msg_erro) == 0) {
		header ("Location: $programa");
	exit;
	}

}

$title = "Programa Restrito";
$cabecalho = "Programa Restrito";
$layout_menu = "gerencia";
include 'menu.php';
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
<p>

<?

$sql = "SELECT privilegios FROM tbl_empregado WHERE empregado = $login_empregado";
$res = pg_exec ($con,$sql);
$privilegios = pg_result ($res,0,0);

if (strpos ($privilegios,'*') === false ) {
	echo "<div class='error'>Apenas usuário MASTER pode realizar restrições de programas.</div>";
	exit;
}

$programa = $_GET['programa'];

echo "Restringinto programa $programa";
echo "<BR>";
echo "<form name='frm_empregado' method='post' action='$PHP_SELF '>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='hidden' name='programa' value='$programa'>";

echo "<table class='border' width='250' align='center' border='1' cellpadding='1' cellspacing='3'>";
echo "<tr class='menu_top'>";
echo "<td nowrap>LOGIN</td>";
echo "<td nowrap>ACESSO</td>";
echo "</tr>";

$sql = "SELECT tbl_empregado.*, tbl_erp_programa_restrito.empregado AS liberado,
		tbl_pessoa.nome
		FROM   tbl_empregado
		LEFT JOIN tbl_erp_programa_restrito USING (empregado)
		JOIN tbl_pessoa USING(pessoa)
		WHERE tbl_empregado.empresa = $login_empresa
		AND   (
			tbl_erp_programa_restrito.programa = '$programa'
			OR tbl_erp_programa_restrito.programa IS NULL
		)
		ORDER BY tbl_empregado.empregado";
$resx = pg_exec ($con,$sql);

for ($i = 0; $i < pg_numrows($resx); $i ++){
	$liberado  = trim(pg_result($resx,$i, liberado));
	$empregado = trim(pg_result($resx,$i,empregado));
	$login     = trim(pg_result($resx,$i, nome));
	
	echo "<tr class='table_line'>\n";
	echo "<input type='hidden' name='empregado_$i' value='$empregado'>\n";
	echo "<td nowrap align='left'>$login</td>\n";
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

<INPUT TYPE="submit" value='Gravar' name='btn_acao' onclick="javascript: document.frm_empregado.btn_acao.value='gravar' ; document.frm_empregado.submit() ; " ALT="Gravar Formulário" border='0'>
</center>
<br>
</form>
<p>

<? include "rodape.php"; ?>
