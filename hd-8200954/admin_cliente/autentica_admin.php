<?php
include_once '../token_cookie.php';
include_once '../fn_traducao.php';
$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);

$cook_fabrica = $cookie_login['cook_fabrica'];
$cook_admin   = $cookie_login['cook_admin'];
$cook_master  = $cookie_login['cook_master'];
$cook_empresa = $cookie_login['cook_empresa'];

if (strlen($cook_empresa) > 0) {
	include 'autentica_admin2.php';
	//exit;
} else {

    $cache_bypass=md5(time());


    //SE FOR ADMIN - SISTEMA "http://www.telecontrol.com.br/assist/admin/"
    //PARA NENHUM ENTRAR NA AREA DO OUTRO
    if (empty($cookie_login['cook_cliente_admin'])) {
        foreach ($_COOKIE as $k => $v) {
            setcookie($k, '');
        }
        unset($_COOKIE);
        header("Location: http://www.telecontrol.com.br/");
        echo 'Redirecionando para http://www.telecontrol.com.br/';
        die;
    }

    /*
    $cook_empresa = $_COOKIE['cook_empresa'];
    if ((strlen ($cook_admin) == 0 )or (strlen($cook_empresa) == 0)) {
        header ("Location: ../index.php");
        exit;
    }

    if ((strlen($cook_master) > 0) or (strlen($cook_empresa) > 0)) {
        include 'autentica_admin2.php';
        //exit;
    }else{
    */
    if (strlen ($cook_admin) == 0) {
        header ("Location: ../index.php");
        exit;
    }


    if (strlen($cook_master) > 0) {
        include 'autentica_admin2.php';
        //exit;
    } else {


    //if ($ip <> "201.0.9.216" and $cook_fabrica == 3) {
    //	header ("Location: ../index.php");
    //	exit;
    //}


        $sql = "SELECT  tbl_admin.admin                             ,
                        tbl_admin.fabrica                           ,
                        tbl_admin.email                           ,
                        tbl_admin.login                             ,
                        tbl_admin.senha                             ,
                        tbl_admin.cliente_admin                     ,
                        tbl_admin.privilegios                       ,
                        tbl_fabrica.nome as fabrica_nome            ,
                        tbl_fabrica.logo AS fabrica_logo            ,
                        tbl_fabrica.site AS fabrica_site            ,
                        tbl_fabrica.multimarca                      ,
                        tbl_fabrica.acrescimo_tabela_base           ,
                        tbl_fabrica.acrescimo_financeiro            ,
                        tbl_fabrica.pedir_causa_defeito_os_item     ,
                        tbl_fabrica.pedir_defeito_constatado_os_item,
                        tbl_fabrica.pedir_solucao_os_item
                FROM    tbl_admin
                JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
                WHERE   tbl_admin.admin   = $cook_admin
                AND     tbl_admin.fabrica = $cook_fabrica";
        $res = @pg_exec ($con,$sql);

        if (@pg_numrows ($res) == 0) {
            header ("Location: ../index.php");
            exit;
        }


        global $login_admin;
        global $login_cliente_admin;
        global $login_login;
        global $login_fabrica;
        global $login_privilegios;
        global $login_fabrica_nome;
        global $login_fabrica_logo;
        global $login_fabrica_site;
        global $multimarca;
        global $acrescimo_tabela_base;
        global $acrescimo_financeiro;
        global $pedir_causa_defeito_os_item;
        global $pedir_defeito_constatado_os_item;
        global $pedir_solucao_os_item;
        global $login_cliente_admin_email;


        $login_cliente_admin_email        = trim(pg_fetch_result($res, '0', 'email'));
        $login_admin                      = trim(pg_fetch_result($res, '0', 'admin'));
        $login_cliente_admin              = trim(pg_fetch_result($res, '0', 'cliente_admin'));
        $login_login                      = trim(pg_fetch_result($res, '0', 'login'));
        $login_fabrica                    = trim(pg_fetch_result($res, '0', 'fabrica'));
        $login_privilegios                = trim(pg_fetch_result($res, '0', 'privilegios'));
        $login_fabrica_nome               = trim(pg_fetch_result($res, '0', 'fabrica_nome'));
        $login_fabrica_logo               = trim(pg_fetch_result($res, '0', 'fabrica_logo'));
        $login_fabrica_site               = trim(pg_fetch_result($res, '0', 'fabrica_site'));
        $multimarca                       = trim(pg_fetch_result($res, '0', 'multimarca'));
        $acrescimo_tabela_base            = trim(pg_fetch_result($res, '0', 'acrescimo_tabela_base'));
        $acrescimo_financeiro             = trim(pg_fetch_result($res, '0', 'acrescimo_financeiro'));
        $pedir_causa_defeito_os_item      = trim(pg_fetch_result($res, '0', 'pedir_causa_defeito_os_item'));
        $pedir_defeito_constatado_os_item = trim(pg_fetch_result($res, '0', 'pedir_defeito_constatado_os_item'));
        $pedir_solucao_os_item            = trim(pg_fetch_result($res, '0', 'pedir_solucao_os_item'));

        if (strlen ($admin_privilegios) > 0) {
            $admin_autorizado = 0;
            $array_privilegios = explode(",",$admin_privilegios);
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



        $sql = "/* PROGRAMA $PHP_SELF  #   FABRICA $login_fabrica   #  ADMIN $login_admin */";
        $resX = @pg_exec ($con,$sql);

    }

}


$sql = "INSERT INTO log_programa (programa, admin) VALUES ('$PHP_SELF',$login_admin)";
$resX = @pg_exec ($con,$sql);

#if ($login_admin <> 189 and strlen ($login_admin) > 0 ) {
#
#echo "<CENTER><h1>ATENÇÃO</h1>";
#echo "<h3>O sistema passará por manutenção técnica</h3";
#echo "<h3>Dentro de 10 minutos será restabelecido</h3";
#echo "<h3> </h3";
#echo "<p><h3>Agradecemos a compreensão!</h3>";
#exit;
#}

$sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica AND parametros_adicionais IS NOT NULL ";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
    $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true); // true para retornar ARRAY e não OBJETO
    extract($parametros_adicionais); // igual o foreach, mais eficiente (processo interno do PHP)

    if (!$externalId) {
        $externalId    = 'smtp@posvenda';
        $externalEmail = 'noreply@telecontrol.com.br';
    }
}

if (!defined('APP_DIR')) {
    define('BASE_URL',  dirname(pathinfo($_SERVER['REQUEST_URI'], PATHINFO_DIRNAME)) . DIRECTORY_SEPARATOR);
    define('APP_DIR',   dirname(__FILE__)    . DIRECTORY_SEPARATOR);
    define('BASE_DIR',  dirname(__DIR__)     . DIRECTORY_SEPARATOR);
    define('ADMIN_URL', BASE_URL . 'admin'   . DIRECTORY_SEPARATOR);
    define('ADMIN_DIR', APP_DIR  . 'admin'    . DIRECTORY_SEPARATOR);
    define('HD_DIR',    APP_DIR  . 'helpdesk' . DIRECTORY_SEPARATOR);
}

