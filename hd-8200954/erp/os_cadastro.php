<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

include '../funcoes.php';

if (strlen($_GET['fabrica']) > 0) {
	$fabrica = trim($_GET['fabrica']);
}

if ($HTTP_COOKIE_VARS['cook_fabrica'] != $fabrica){
	if (strlen($fabrica)>0){
		$sql = "SELECT	tbl_posto_fabrica.fabrica,
						tbl_posto_fabrica.codigo_posto,
						tbl_fabrica.nome,
						tbl_posto_fabrica.oid as posto_fabrica
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
				WHERE tbl_posto_fabrica.fabrica = $fabrica
				AND tbl_posto.posto = $login_posto";
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			$msg_erro .= "Erro. Seu posto não está credenciado a esta Fábrica.";
			$fabrica= "";
		}else{
			$fabrica       = trim(pg_result ($res,0,fabrica));
			$nome_fabrica  = trim(pg_result ($res,0,nome));
			$posto_fabrica = trim(pg_result ($res,0,posto_fabrica));
			add_cookie($cookie_login,"cook_posto_fabrica",$posto_fabrica);
			add_cookie($cookie_login,"cook_posto",$login_posto);
			add_cookie($cookie_login,"cook_fabrica",$fabrica);
			
			set_cookie_login($token_cookie,$cookie_login);
			echo "<script languague='javascript'>window.location='$PHP_SELF?fabrica=$fabrica'</script>";
			exit;
		}
	}
}
include 'autentica_usuario_assist.php';

$title       = "Cadastro de Ordem de Serviço - $nome_fabrica"; 

include "menu.php";
?>

<?

if (strlen($msg_erro)>0){
	echo "$msg_erro";
}else{
	#include '../os_cadastro.php';
	#exit;
	#echo "<script languague='javascript'> window.open('os_cadastro_ajax.php');</script>";
	echo "<iframe src ='os_cadastro_ajax.php' width='100%' height='100%'></iframe>";
	#echo "<iframe src ='../os_cadastro_ajax.php' width='100%' height='100%'></iframe>";
}
?>

<?
 //include "rodape.php";
 ?>
