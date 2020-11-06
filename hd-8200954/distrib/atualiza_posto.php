<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$tipo          = trim ($_GET['tipo']);
$dado          = trim ($_GET['dado']);
$posto         = trim ($_GET['posto']);




#HD 34993
$btn_altera = $_POST['btn_altera'];

if(strlen($btn_altera) > 0){
	$tipo          = trim($_POST['tipo']);
	$dado          = trim($_POST['dado']);
	$posto         = trim($_POST['posto']);

	$sql="UPDATE tbl_posto set ";
	if($tipo =='nome') $sql.= " nome='$dado' ,data_expira_sintegra = CURRENT_DATE";
	elseif($tipo =='cnpj') $sql.= " cnpj='$dado' ,data_expira_sintegra = CURRENT_DATE";
	elseif($tipo =='ie')   $sql.= " ie=$dado ,data_expira_sintegra = CURRENT_DATE";
	$sql.=" WHERE posto=$posto ";
	$res=pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	if(strlen($msg_erro)==0){
		echo "<center><h3>Atualizado com Sucesso!</h3></center>";
		echo "<script>";
		echo "opener.window.location.reload();";
		echo "window.close()";
		echo "</script>";
	}else{
		echo "Ocorreu o seguinte erro $msg_erro";
	}
	exit;
}
if(strlen($tipo) > 0 and strlen($posto) > 0){
	echo "<form method='post' name='frm_altera' action='$PHP_SELF?tipo=$tipo&posto=$posto'>";
	echo "<table width='100%' border='0' cellspacing='1' cellpadding='1' class='tabela'>";
	echo "<input type='hidden' name=posto value=$posto>";
	echo "<input type='hidden' name=tipo value=$tipo>";
	echo "<caption>Alteração de ";
		if($tipo =='nome') echo "Razão Social";
		if($tipo =='cnpj') echo "CNPJ";
		if($tipo =='ie')   echo "INSCRIÇÃO ESTADUAL";
	echo "</caption>";
	echo "<tr><td align='center'>DE<b> $dado </b></td></tr>";
	echo "<tr><td align='center'>PARA <input type='text' name='dado' value='' >";
	echo "</tr>";
	echo "<tr><td align='center'><input type='submit' name='btn_altera' value='Alterar'></td></tr>";
	echo "</table>";
	echo "</form>";
	exit;
}
