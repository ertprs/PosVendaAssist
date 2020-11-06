<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($login_unico)>0 AND $login_unico_master <>'t'){
	if($login_unico_distrib_total <>'t') {
		echo "<center><h1>Você não tem autorização para acessar este programa!</h1><br><br><a href='javascript:history.back();'>Voltar</a></center>";
		exit;
	}
}

$codigo_barras = $_POST['codigo_barras'];
$qtde = $_POST['qtde'];
if (strlen ($codigo_barras) > 0 AND strlen ($qtde) > 0){
	$sql = "SELECT fn_desembarca_item ($codigo_barras,$qtde)";
	$res = pg_exec ($con,$sql);
	
	if (strlen (pg_errormessage ($con)) == 0) {
		echo "<center><h1> Item Desembarcado </h1> </center>";
		$codigo_barras = "";
		$qtde = "";
	}
}



$cancelar = $_GET['cancelar'];
if ($cancelar == 'S') {
	$posto    = $_GET['posto'];
	$embarque = $_GET['embarque'];
	
	$sql = "SELECT fn_cancela_embarque ($login_posto, $posto, $embarque)";
	$res = pg_exec ($con,$sql);

	header ("Location: embarque.php");
	exit;
}
$title = "Desembarque de Itens";
?>

<html>
<head>
<title><?php echo $title ?></title>
</head>

<body onload='document.frm_desembarque.codigo_barras.focus()'>

<? include 'menu.php' ?>

<?
$embarque = $_POST['embarque'];
?>

<center><h1>Desembarque de Itens</h1></center>

<p>
<center>

<form method='post' action='<? echo $PHP_SELF ?>' name='frm_desembarque'>

Código de Barras <input type='text' name='codigo_barras' size='10' value='<? echo $codigo_barras ?>'>
<br>
Qtde <input type='text' name='qtde' size='5' value='<? echo $qtde ?>'>

<p>

<input type='submit' name='btn_acao' value='Desembarcar'>

</form>

</center>

<p>

<? include "rodape.php"; ?>

</body>
</html>
