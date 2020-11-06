<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'token_cookie.php';

// echo '<pre>';
// echo 'POST DATA:';
// var_export($_POST);
// var_export($_SERVER);
// die('</pre>');

header('Content-Type: text/html; charset=utf-8');

if (count($_POST)) {
    $page_return = 'http://www.telecontrol.com.br';

    if (strpos($_SERVER['HTTP_REFERER'], 'ww2.telecontrol.com.br/mlg/consultas.php') == 0)
		die('URL de Origem inválida!');

	// header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	// header('Vary: Origin');

	$POST_tok         = $_POST['tok'];
	$POST_login       = $_POST['login'];
	$POST_login_unico = $_POST['lu'];
	$POST_posto       = $_POST['posto'];

	$sql = "SELECT login_unico, posto, senha
              FROM tbl_login_unico
             WHERE login_unico = $POST_login_unico
               AND email   = '$POST_login'
               AND posto   = $POST_posto
               AND ativo  IS TRUE
               AND email_autenticado IS NOT NULL";
    $res = $pdo->query($sql);

    if (!$res->rowCount())
        die("Erro de login!");
        // goto REDIRECT;

	if ($res->rowCount() !== 1) {
		die('Problemas no banco de dados!');
	}

    $rec         = $res->fetch();
    $login_unico = $rec['login_unico'];
    $tok_tail    = substr($rec['senha'], -10);
    $posto       = $rec['posto'];
    $validacao   = base64_encode("TOK:$login;$login_unico;$posto;$tok_tail;".date('d'));

	if ($tok !== $validacao) {
        if ($_POST['debug']) {
            $remoto = base64_decode($tok);
            $local  = base64_decode($validacao);
            echo "<br><pre>LOGIN: $remoto\nCHECK: $local</pre>";
        }
        die('Login inválido!');
    }

    $page_return = $_POST['page_return'];

    $token_cookie = gera_token(0,$login_unico,$posto);
    $cookie_login = get_cookie_login($token_cookie);

    $cookie_login = add_cookie($cookie_login, 'cook_login_unico', $login_unico);
    $cookie_login = add_cookie($cookie_login, 'cook_posto',       $posto);

    set_cookie_login($token_cookie, $cookie_login);
    setcookie("sess", $token_cookie);

    goto REDIRECT;
}

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$page_return = $_GET['page_return'];
unset($_GET['page_return']);

foreach ($_GET as $key => $value) {
	$$key = $value;
	add_cookie($cookie_login,$key,$value);
}
if(!empty($hd_chamado) and !empty($cook_fabrica)) {
	$sql = "UPDATE tbl_hd_chamado set fabrica = $cook_fabrica , fabrica_responsavel = $cook_fabrica where hd_chamado = $hd_chamado and fabrica <> $cook_fabrica ";
	$res = pg_query($con, $sql);
}
set_cookie_login($token_cookie,$cookie_login);

REDIRECT:
header("Location: ".$page_return);
exit;

