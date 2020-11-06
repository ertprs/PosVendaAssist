<?php

if (((int) $_GET["f"] and (int) $_GET["user"]) || (isset($_GET["cc"]) && strlen($_GET["cc"]) > 0)) {
	if (isset($_GET["cc"]) && strlen($_GET["cc"]) > 0) {
		//remove_login_cookie($_COOKIE['sess']);
		$base64 = base64_decode($_GET["cc"]);
		$parts = explode("|", $base64);
//		echo "<pre>".print_r($parts,1)."</pre>";exit;
		$fabrica = (int) $parts[0];
		$user = (int) $parts[1];

	} else {

		$fabrica = (int) $_GET["f"];
		$user = (int) $_GET["user"];
	}
	$token = gera_token($fabrica, $user);
	setcookie("sess", $token);

	if(empty($_COOKIE['sess'])) {
		$_COOKIE['sess'] = $token;
	}
	$arr = array();

	add_cookie($arr, "cook_fabrica", $fabrica);
	add_cookie($arr, "cook_admin", $user);

	set_cookie_login($token, $arr);

	unset($fabrica);
	unset($user);
	unset($arr);
	unset($token);
}

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_fabrica    = $cookie_login['cook_fabrica'];
$cook_admin      = $cookie_login['cook_admin'];
$cook_admin_es   = $cookie_login['cook_admin_es'];

if ($_GET["assist"] == "assist" and $cookie_login["cook_pedidoweb"] == "pedidoweb") {
    // setcookie("cook_admin", "", time() - 28800);
    // setcookie("cook_fabrica", "", time() - 28800);
    // setcookie("cook_pedidoweb", "", time() - 28800);

    add_cookie($cookie_login,"cook_admin","");
    add_cookie($cookie_login,"cook_fabrica","");
    add_cookie($cookie_login,"cook_pedidoweb","");

    set_cookie_login($token_cookie,$cookie_login);

    $cook_fabrica    = $cookie_login['cook_fabrica'];
    $cook_admin      = $cookie_login['cook_admin'];
    $cook_admin_es   = $cookie_login['cook_admin_es'];
}

if ($_GET["pedidoweb"] == "pedidoweb") {
    // setcookie("cook_admin", "", time() - 28800);
    // setcookie("cook_fabrica", "", time() - 28800);
    // setcookie("cook_pedidoweb", "", time() - 28800);

    add_cookie($cookie_login,"cook_admin", "");
    add_cookie($cookie_login,"cook_fabrica", "");
    add_cookie($cookie_login,"cook_pedidoweb", "");

    set_cookie_login($token_cookie,$cookie_login);

    unset($cook_admin);
    unset($cook_fabrica);
}

#  20/2/2009 MLG - Adicionado para a Olivier Joias, não funciona o setcookie desde outro domain...
if (strlen ($cook_admin) == 0 and $_GET['user']=='1599' and $_GET['f']=='78' and $_GET['admin']=='olivier') {
    // setcookie('cook_fabrica',$_GET['f']  , time() + 28800, "/");
    // setcookie('cook_admin', $_GET['user'], time() + 28800, "/");

    add_cookie($cookie_login,'cook_fabrica',$_GET['f']  );
    add_cookie($cookie_login,'cook_admin', $_GET['user']);

    set_cookie_login($token_cookie,$cookie_login);


    header ("Location: $PHP_SELF");
    exit;
}#  fim

if (strlen ($cook_admin) == 0 and strlen($_GET['user'])>0 and $_GET['f']=='76' and $_GET['admin']=='filizola') {
    // setcookie('cook_fabrica',$_GET['f']  , time() + 28800, "/");
    // setcookie('cook_admin', $_GET['user'], time() + 28800, "/");

    add_cookie($cookie_login,'cook_fabrica',$_GET['f']  );
    add_cookie($cookie_login,'cook_admin', $_GET['user']);

    set_cookie_login($token_cookie,$cookie_login);


    header ("Location: $PHP_SELF");
    exit;
}#  fim

if (strlen ($cook_admin) == 0 and in_array($_GET['user'],array(3178,13377))  and $_GET['f']=='75' and $_GET['admin']=='thermoking') {
    // setcookie('cook_fabrica',$_GET['f']  , time() + 28800, "/");
    // setcookie('cook_admin', $_GET['user'], time() + 28800, "/");

    add_cookie($cookie_login,'cook_fabrica',$_GET['f']  );
    add_cookie($cookie_login,'cook_admin', $_GET['user']);

    set_cookie_login($token_cookie,$cookie_login);


    header ("Location: $PHP_SELF");
    exit;
}#  fim

if (strlen ($cook_admin) == 0 and $_GET['user']=='6049' and $_GET['f']=='135' and $_GET['admin']=='hitachi') {
    // setcookie('cook_fabrica',$_GET['f']  , time() + 28800, "/");
    // setcookie('cook_admin', $_GET['user'], time() + 28800, "/");

    add_cookie($cookie_login,'cook_fabrica',$_GET['f']  , time() + 28800, "/");
    add_cookie($cookie_login,'cook_admin', $_GET['user'], time() + 28800, "/");


    set_cookie_login($token_cookie,$cookie_login);

    header ("Location: $PHP_SELF");
    exit;
}#  fim

if ($_GET["pedidoweb"] == "pedidoweb" and empty($cook_admin)) {
	$array_pedidoweb = array(
		75 => array(
			"f" => "thermoking",
			"adm" => array(3178,13377)
		),
                113 => array(
                        "f"   => "rinnai",
                        "adm" => array(4334, 4349)
                       ),
                107 => array(
                        "f"   => "orbis",
                        "adm" => array(3950, 4100, 4101, 4102)
                       ),
                159 => array(
                        "f"   => "ingersoll-rand",
                        "adm" => array(7930, 8287, 8609, 8610,11597,12882, 13270)
                       ),
                110 => array(
                        "f"   => "mallory-comercial",
                        "adm" => array(4046)
                       ),
                192 => array(
                        "f"   => "mondial",
                        "adm" => array(13342, 13343)
                       ),

               );

    if ($array_pedidoweb["{$_GET['f']}"] and $_GET["admin"] == $array_pedidoweb["{$_GET['f']}"]["f"] and in_array($_GET["user"], $array_pedidoweb["{$_GET['f']}"]["adm"])) {
        // setcookie("cook_pedidoweb", "pedidoweb", time() + 28800);
        // setcookie("cook_fabrica", $_GET["f"], time() + 28800);
        // setcookie("cook_admin", $_GET["user"], time() + 28800);

        add_cookie($cookie_login,"cook_pedidoweb", "pedidoweb", time() + 28800);
        add_cookie($cookie_login,"cook_fabrica", $_GET["f"], time() + 28800);
        add_cookie($cookie_login,"cook_admin", $_GET["user"], time() + 28800);

        set_cookie_login($token_cookie,$cookie_login);

        header("Location: {$_SERVER['PHP_SELF']}");
        exit;
    }
}

if(strlen($cook_admin_es)>0)$cook_admin = $cook_admin_es;

if (strlen($cook_admin) == 0) {
    /* HD 20640 */
    $cook_bosch = $_COOKIE['cook_bosch'];
    if (strlen($cook_bosch)>0){
        header ("Location: ../bosch.php");
	}else{
	remove_login_cookie($_COOKIE['sess']);
        header ("Location: ../externos/login_posvenda_new.php");
    }
    exit;
}
//if ($ip <> "201.0.9.216" and $cook_fabrica == 3) {
//  header ("Location: ../index.php");
//  exit;
//}

$sql = "SELECT  tbl_admin.admin,
                tbl_admin.fabrica,
                tbl_admin.login,
				tbl_admin.senha,
				tbl_admin.email,
				tbl_admin.nome_completo,
                tbl_admin.live_help,
                tbl_admin.privilegios,
                tbl_admin.grupo_admin,
                tbl_admin.help_desk_supervisor,
                tbl_admin.parametros_adicionais as parametros,
                tbl_admin.pais,
                tbl_fabrica.nome AS fabrica_nome,
                tbl_fabrica.logo AS fabrica_logo,
                tbl_fabrica.site AS fabrica_site,
                tbl_fabrica.multimarca,
                tbl_fabrica.acrescimo_tabela_base,
                tbl_fabrica.acrescimo_financeiro,
                tbl_fabrica.pedir_causa_defeito_os_item,
                tbl_fabrica.pedir_defeito_constatado_os_item,
                tbl_fabrica.parametros_adicionais,
                tbl_fabrica.pedir_solucao_os_item,
                tbl_l10n.nome
        FROM    tbl_admin
        JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
        LEFT JOIN tbl_l10n ON tbl_l10n.l10n = tbl_admin.l10n
        WHERE   tbl_admin.admin   = $cook_admin
        AND     tbl_admin.fabrica = $cook_fabrica";
$res = @pg_exec ($con,$sql);
//if ($ip == "187.39.215.9") {
//  echo $sql;
//  exit;
//}

if (@pg_numrows ($res) == 0) {
    /* HD 20640 */
    $cook_bosch = $_COOKIE['cook_bosch'];
    if (strlen($cook_bosch)>0){
        header ("Location: ../bosch.php");
    }else{
		remove_login_cookie($_COOKIE['sess']);
        header ("Location: ../externos/login_posvenda_new.php");
    }
    exit;
}

global $login_admin;
global $login_login;
global $login_email;
global $login_nome_completo;
global $login_fabrica;
global $login_pais;
global $login_live_help;
global $login_privilegios;
global $grupo_admin;
global $login_help_desk_supervisor;
global $login_fabrica_nome;
global $login_fabrica_logo;
global $login_fabrica_site;
global $multimarca;
global $acrescimo_tabela_base;
global $acrescimo_financeiro;
global $pedir_causa_defeito_os_item;
global $pedir_defeito_constatado_os_item;
global $pedir_solucao_os_item;
global $sistema_lingua;
global $fabricas_sla;
global $cook_idioma;

//BUSCA FABRICAS QUE POSSUI SLA
$sqlFabSLA  = "SELECT fabrica FROM tbl_fabrica WHERE json_field('fabricante_sla',parametros_adicionais) = 't'";
$resFabSLA  = pg_query($con, $sqlFabSLA);

if (pg_num_rows($resFabSLA) > 0) {
    $fabricante_sla = pg_fetch_all($resFabSLA);
    foreach ($fabricante_sla as $key_sla => $value_sla) {
        $fabricas_sla[] = $value_sla['fabrica'];
    }
}

$login_admin                       = trim (pg_result ($res,0,'admin'));
$login_login                       = trim (pg_result ($res,0,'login'));
$login_fabrica                     = trim (pg_result ($res,0,'fabrica'));
$login_help_desk_supervisor        = trim (pg_result ($res,0,'help_desk_supervisor'));
$login_live_help                   = trim (pg_result ($res,0,'live_help'));
$login_pais                        = trim (pg_result ($res,0,'pais'));
$login_email                       = trim (pg_result ($res,0,'email'));
$login_privilegios                 = trim (pg_result ($res,0,'privilegios'));
$login_nome_completo               = trim (pg_result ($res,0,'nome_completo'));
$grupo_admin                       = trim (pg_result ($res,0,'grupo_admin'));
$login_fabrica_nome                = trim (pg_result ($res,0,'fabrica_nome'));
$login_fabrica_logo                = trim (pg_result ($res,0,'fabrica_logo'));
$login_fabrica_site                = trim (pg_result ($res,0,'fabrica_site'));
$multimarca                        = trim (pg_result ($res,0,'multimarca'));
$acrescimo_tabela_base             = trim (pg_result ($res,0,'acrescimo_tabela_base'));
$acrescimo_financeiro              = trim (pg_result ($res,0,'acrescimo_financeiro'));
$pedir_causa_defeito_os_item       = trim (pg_result ($res,0,'pedir_causa_defeito_os_item'));
$pedir_defeito_constatado_os_item  = trim (pg_result ($res,0,'pedir_defeito_constatado_os_item'));
$pedir_solucao_os_item             = trim (pg_result ($res,0,'pedir_solucao_os_item'));
$cook_idioma                       = trim (pg_result ($res,0,'nome'));
$parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true); // true para retornar ARRAY e não OBJETO
$admin_parametros = json_decode(pg_fetch_result($res, 0, 'parametros'), true); // true para retornar ARRAY e não OBJETO
extract($parametros_adicionais); // igual o foreach, mais eficiente (processo interno do PHP)
extract($admin_parametros); // igual o foreach, mais eficiente (processo interno do PHP)

// Envio de e-mails
include_once '../class/communicator.class.php';
include_once 'funcoes.php';
$mailer = new TcComm('smtp@posvenda');

if($login_pais != 'BR' || $cook_idioma == 'es') {
	$sistema_lingua = 'ES';
} else {
	$cook_idioma = 'pt-BR';
}


if (strlen ($admin_privilegios) > 0) {
    $admin_autorizado = 0;
    $array_privilegios = split (",",$admin_privilegios);
    for ($i = 0 ; $i < count($array_privilegios) ; $i++) {
        $cabecalho_privilegio = $array_privilegios[$i];
        if (strpos ($login_privilegios , trim($cabecalho_privilegio)) !== false) {
            $admin_autorizado = 1;
        }
    }

    if (strpos ($login_privilegios,"*") !== false) {
        $admin_autorizado = 1;
    }

    if ($admin_autorizado == 0) {
        $title = "MENU GERÊNCIA";
        $layout_menu = "gerencia";
        include 'cabecalho.php';
        echo "<p><hr><center><h1>Sem permissão para acessar este programa</h1></center><p><hr>";
        exit;
    }
}

include_once '../fn_traducao.php';

$sql = "/* PROGRAMA $PHP_SELF  #   FABRICA $login_fabrica   #  ADMIN $login_admin */";
$resX = @pg_exec ($con,$sql);
include_once '../funcoes.php';
