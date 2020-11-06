<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';

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

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>


<?

$sql = "SELECT privilegios FROM tbl_admin WHERE admin = $login_admin";
$res = pg_exec ($con,$sql);
$privilegios = pg_result ($res,0,0);

if (strpos ($privilegios,'*') === false ) {
	echo "<center><h1>Apenas usuário MASTER pode realizar restrições de programas.</h1></center>";
	exit;
}

$programa = $_GET['programa'];
?>
<div style='width:700px;' class='texto_avulso'>
	Quando você selecionar um usuário estará incluindo-o em um grupo restrito de usuários que podem acessar o programa : <? echo $programa; ?>
</div>
<br />

<form name='frm_admin' method='post' action='<?=$PHP_SELF?>'>
<center>
	<input type="button" value="Copiar Permissões de Outros Usuários" onclick="window.location='programa_restrito_gerencia_test.php'">

<input type='button' value='Gravar' style="cursor: pointer;" onclick="javascript: if(confirm('SOMENTE OS USUÁRIOS SELECIONADOS ABAIXO PODERÃO ACESSAR O PROGRAMA: <?= $programa ?>')){document.frm_admin.btn_acao.value='gravar' ; document.frm_admin.submit() ;} " ALT="Gravar Formulário" border='0'>
</center>
<br />

<?
if(strlen($msg_erro) > 0){
?>

<table width='700px' align='center' border='0' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='msg_erro'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} ?>


	<input type='hidden' name='btn_acao' value=''>
	<input type='hidden' name='programa' value='<?=$programa?>'>
	
	<table width='700' align='center' border='0' cellpadding='1' cellspacing='1' class='tabela'>
		<tr class='titulo_coluna'>
			<td nowrap>LOGIN</td>
			<td nowrap>ACESSO</td>
		</tr>

<?
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
	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
	echo "<tr bgcolor='$cor'>\n";
	echo "<td nowrap align='left'><a href='./programa_restrito_gerencia.php?edit_admin=$admin'>$login</td>\n";
	echo "<td><input type='checkbox' name='liberado_$i' value='1'";
	if (strlen($liberado) > 0) echo " checked >";
	echo "<input type='hidden' name='admin_$i' value='$admin'> </TD>\n";
	echo "</tr>\n";
}
?>

<input type='hidden' name='qtde_item' value="<? echo $i?>">

</table> 


</form>

<? include "rodape_test.php"; ?>
