<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

include 'menu.php';

$btn_acao = strtolower($_POST["btn_acao"]);

if ($btn_acao == 'gravar'){

	$qtde_item = trim($_POST["qtde_item"]);
	$programa  = trim($_POST["programa"]);

	$begin = pg_query($con,'BEGIN TRANSACTION');
	for ($i = 0; $i <= $qtde_item; $i ++){
		$admin        = trim($_POST['admin_'.$i]);
		$liberado     = trim($_POST['liberado_'.$i]);

		
		if (strlen ($admin) > 0) {
			if (strlen($liberado) > 0) {
				
				$sql = "SELECT * FROM tbl_programa_restrito WHERE programa = '$programa' AND login_unico = $admin";
				$res = pg_query($con,$sql);
				
				if (pg_numrows ($res) == 0) {
					
					$sql = "INSERT INTO tbl_programa_restrito (programa, login_unico,admin) VALUES ('$programa',$admin,586)";
					//hd 807852 - O CAMPO ADMIN VAI COM 586 QUE É O ADMIN DO RONALDO NA TBL_ADMIN, POIS TEM QUE INSERIR ADMIN PQ O CAMPO É NOT NULL. SOLICITAÇÃO DO ANALISTA
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}else{
				
				$sql = "DELETE FROM tbl_programa_restrito WHERE programa = '$programa' AND login_unico = $admin";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
			
		}
	}

	if (strlen ($msg_erro) == 0) {
		$commit = pg_query($con,'COMMIT TRANSACTION');
		
		header ("Location: $programa");
		exit;
	}else{
		
		$rollback = pg_query($con,'ROLLBACK TRANSACTION');
		
	}
}


$title = "Restrição de Acesso";
$cabecalho = "Programa Restrito";

?>

<style type="text/css">

.border {
	border: 1px solid #ced7e7;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.titulo_coluna{
    background-color:#596d9b;
    font: bold 12px "Arial";
    color:#FFFFFF;
    text-align:center;
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

<?

$sql = "SELECT master FROM tbl_login_unico WHERE login_unico = $login_unico";
$res = pg_exec ($con,$sql);
$master = pg_result ($res,0,0);

if ( $master == 'f' ) {
	echo "<center><h1>Apenas usuário MASTER pode realizar restrições de programas.</h1></center>";
	exit;
}

$programa = $_GET['programa'];
$titulo_programa = $_GET['titulo'];

echo "Restringindo o programa: $titulo_programa";
echo "<div>";
echo "<form name='frm_admin' method='post' action='$PHP_SELF '>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='hidden' name='programa' value='$programa'>";

echo "<table width='250' align='center' border='0' cellpadding='1' cellspacing='1' class='tabela'>";
echo "<tr class='titulo_coluna'>";
echo "<td nowrap>LOGIN</td>";
echo "<td nowrap>ACESSO</td>";
echo "</tr>";

$sql = "SELECT tbl_login_unico.*
		FROM   tbl_login_unico
		
		WHERE tbl_login_unico.posto = $login_unico_posto
		
		AND tbl_login_unico.ativo is true
		ORDER BY tbl_login_unico.nome";

$resx = pg_exec ($con,$sql);

for ($i = 0; $i < pg_numrows($resx); $i ++){
	$liberado = "";
	$login_unico_a = trim(pg_result($resx,$i,'login_unico'));
	$nome = trim(pg_result($resx,$i,'nome'));
	
	$sql = "SELECT tbl_programa_restrito.login_unico AS liberado
			FROM   tbl_programa_restrito
			WHERE  tbl_programa_restrito.login_unico    = $login_unico_a
			AND    tbl_programa_restrito.programa = '$programa'";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$liberado = trim(pg_result ($res,0,liberado));
	}
	
	echo "<tr class='table_line'>\n";
	echo "<input type='hidden' name='admin_$i' value='$login_unico_a'>\n";
	echo "<td nowrap align='left'>$nome</td>\n";
	echo "<td><input type='checkbox' name='liberado_$i' value='1'";
	if (strlen($liberado) > 0) echo " checked ";
	echo "> &nbsp;</TD>\n";
	echo "</tr>\n";
}
?>

<input type='hidden' name='qtde_item' value="<? echo $i?>">

</table> 
</div>
<br>
<center>
<input type="button" style="cursor: pointer;" value="Gravar" onclick="javascript: document.frm_admin.btn_acao.value='gravar' ; document.frm_admin.submit() ; " ALT="Gravar Formulário" border='0'>
</center>
<br>
</form>
<br>
<p>
<? include "rodape.php"; ?>