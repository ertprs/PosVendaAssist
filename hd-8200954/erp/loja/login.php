<?

include 'dbconfig.php';
include 'dbconnect-inc.php';
include '../../token_cookie.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);


$usuario = pg_escape_string($_POST['usuario']);
$senha   = pg_escape_string($_POST['senha']); 

  $sql = "SELECT  tbl_pessoa.pessoa ,
				tbl_pessoa.nome   ,
				tbl_pessoa.email  ,
				tbl_pessoa.senha
		FROM tbl_pessoa
		WHERE tbl_pessoa.email = '$usuario'
		AND   tbl_pessoa.senha = '$senha'";

$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
		$pessoa = trim (pg_result ($res,0,pessoa));
		$nome   = trim (pg_result ($res,0,nome));
		$email  = trim (pg_result ($res,0,email));
		$senha  = trim (pg_result ($res,0,senha));

	// setcookie ('cook_pessoa',$pessoa);
	// setcookie ('cook_nome'  ,$nome);
	// setcookie ('cook_email' ,$email);
	// setcookie ('cook_senha' ,$senha);

	add_cookie($cookie_login,'cook_pessoa',$pessoa);
	add_cookie($cookie_login,'cook_nome'  ,$nome);
	add_cookie($cookie_login,'cook_email' ,$email);
	add_cookie($cookie_login,'cook_senha' ,$senha);

	
	set_cookie_login($token_cookie,$cookie_login);

	header("Location:finaliza.php");
}else{
	header("Location:identificacao.php?status=erro");
}

?>