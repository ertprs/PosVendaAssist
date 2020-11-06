<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
$cookie_login = get_cookie_login($token_cookie = $_COOKIE['sess']);

session_start();

if(isset($_SESSION['admin_loja']) && !empty($_SESSION['admin_loja'])){
    unset($_SESSION['admin_loja']);
    header("Location: externos/loja/admin");
    exit;
}

$cook_posto         = $cookie_login['cook_posto'];
$cook_login_unico   = $cookie_login['cook_login_unico'];
$cook_fabrica       = $cookie_login['cook_fabrica'];

// echo "Autenitca";exit;
if(trim($cook_login_unico) == ''      or
//  $cook_login_unico == 'temporario' or
    $cook_login_unico == 'deleted') {
    header("Location: logout_2.php");
    exit;
}

// Constantes com diversas rotas de diretórios e outros valores úteis
define ('APP_DIR',  dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR);
define ('BASE_URL', dirname($_SERVER['PHP_SELF']) . DIRECTORY_SEPARATOR);
define ('APP_URL',  '//' . $_SERVER["HTTP_HOST"] .
    preg_replace(
        '#/(admin|admin_es|admin_callcenter|helpdesk)#', '',
        dirname($_SERVER['SCRIPT_NAME'])
    )
);
define ('APP_ENV', $serverEnvironment);
define ('DEV',     $serverEnvironment === 'development');
define ('PROGRAM_NAME', basename($_SERVER['SCRIPT_FILENAME']));

$sql = "SELECT pedido FROM tbl_pedido WHERE fabrica = 10 AND finalizado IS NULL AND posto=$cook_posto";

$res = @pg_query ($con,$sql);
$msg_erro = pg_last_error($con);
if(pg_num_rows($res)>0){
    $cook_pedido_lu = pg_fetch_result($res, 0, pedido);
    setcookie ("cook_pedido_lu",$cook_pedido_lu);
}

if(is_numeric($cook_login_unico)) {
    $sql = "SELECT LU.*,
                   tbl_posto.nome AS nome_posto,
                   tbl_posto.cnpj,
                   tbl_posto.fantasia,
                   tbl_posto.pais
              FROM tbl_login_unico AS LU
              JOIN tbl_posto USING (posto)
			 WHERE login_unico = $cook_login_unico 
			 AND LU.ativo";

    $res = pg_query($sql);
    if (pg_num_rows($res)>0) {
        global $login_nome, $login_email, $login_master, $nome_posto,
            $login_unico_nome, $posto_nome, $posto_fantasia, $posto_cnpj,
            $acessa_distrib, $login_unico_tecnico;
        $login_unico         = pg_fetch_result($res, 0, 'login_unico');
        $login_unico_nome    = pg_fetch_result($res, 0, 'nome');
        $login_email         = pg_fetch_result($res, 0, 'email');
        $login_master        = pg_fetch_result($res, 0, 'master');
        $nome_posto          = pg_fetch_result($res, 0, 'nome_posto');
        $posto_nome          = pg_fetch_result($res, 0, 'nome_posto');
        $posto_fantasia      = pg_fetch_result($res, 0, 'fantasia');
        $posto_cnpj          = pg_fetch_result($res, 0, 'cnpj');
        $login_pais          = pg_fetch_result($res, 0, 'pais');
        $LU_abre_os          = pg_fetch_result($res, 0, 'abre_os')       == 't';
        $LU_item_os          = pg_fetch_result($res, 0, 'item_os')       == 't';
        $LU_fecha_os         = pg_fetch_result($res, 0, 'fecha_os')      == 't';
        $LU_compra_peca      = pg_fetch_result($res, 0, 'compra_peca')   == 't';
        $LU_extrato          = pg_fetch_result($res, 0, 'extrato')       == 't';
        $LU_distrib_total    = pg_fetch_result($res, 0, 'distrib_total') == 't';
        $LU_tecnico_posto    = pg_fetch_result($res, 0, 'tecnico_posto') == 't';
        $acessa_distrib      = pg_fetch_result($res, 0, 'distrib_total') == 't';
        $login_unico_tecnico = pg_fetch_result($res, 0, 'tecnico');
        $login_unico_distrib_total = $acessa_distrib;
		$LU_abre_os = ($login_master) ? true : $LU_abre_os;
        if(strlen($login_unico_tecnico) > 0){
            $sql = "SELECT fabrica FROM tbl_tecnico WHERE tecnico = $login_unico_tecnico";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $lu_tecnico_fabrica = pg_fetch_result($res,0,fabrica);
            }
        }
    }
}

$login_posto = $cook_posto;
$login_unico = $cook_login_unico;

include_once S3CLASS;

/* 26-07-2012 - MLG - Movi o include e as funções de tradução para um arquivo, assim fica mais fácil alterar
 * mantendo as atualizações num único lugar */
include_once 'fn_traducao.php';

