<?
header("Content-Type: text/html; charset=ISO-8859-1",true);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_admin.php';

$serie   = $_GET['serie'];
$produto = $_GET['produto'];
$fabrica = $_GET['fabrica'];

if($fabrica==30){
	echo $sql = "SELECT fn_valida_esmaltec_serie('$serie',$produto,$fabrica)";
	$res      = @pg_exec($con,$sql);
	$msg_erro =  pg_errormessage($con);
}

#HD 260769
if($fabrica==85){
	$sql = "SELECT fn_valida_gelopar_serie('$serie',$produto,$fabrica)";
	$res      = @pg_exec($con,$sql);
	$msg_erro =  pg_errormessage($con);
}

if (strlen($msg_erro)==0) {
	$sql="SELECT tbl_numero_serie.numero_serie
		FROM   tbl_numero_serie
		WHERE  tbl_numero_serie.fabrica = $fabrica
		AND    tbl_numero_serie.produto = $produto
		AND    tbl_numero_serie.serie   = '$serie'";
	$res = @pg_exec($con,$sql);
	if (pg_numrows($res)==0){
		echo 'erro 1|'.$serie;
	}
	else{
		echo 'ok';
	}
}
else{ 
	$msg_erro = str_replace('ERROR: ','',$msg_erro);

	$msg_erro = trim(substr($msg_erro,0,16));

	if ($msg_erro=='Número de série'){
		echo 'erro 1|'.$serie.'|'.$msg_erro;
	}
	else{
		echo 'erro 2|'.$serie.'|'.$msg_erro;
	}
}

#Número de série inválido para o produto 060201520012
#ERROR: Número de série 6xx23456789012 inválido para o produto 060201520012!

?>
