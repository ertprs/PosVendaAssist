<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include '../funcoes.php';

$nota_fiscal = trim($_GET['nota_fiscal']);
//$login_posto = 6359;
$ajax= trim($_GET["ajax"]);
if($ajax=="sim"){
	$sql="select faturamento 
	  from tbl_faturamento 
	  where fabrica = 3 
		and posto = $login_posto
		and nota_fiscal='$nota_fiscal'";

	$res = pg_exec ($con,$sql);

	//SE JA EXISTIR O FATURAMENTO, REDIRECIONA PARA A TELA DA NOTA FISCAL
	if(pg_numrows($res)>0){
		echo "ok|<font color='red'>Nota Fiscal:\"$nota_fiscal\" já cadastrada!</font>";
	}else{
		echo "ok|<font color='blue'>Nota Fiscal:\"$nota_fiscal\" não cadastrada pela Britania!</font>";
	}

exit();
}
?>
