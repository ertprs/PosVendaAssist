<?php
if (!function_exists('getmicrotime')) {
    function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }
}
date_default_timezone_set('Etc/GMT+3');
$micro_time_start = getmicrotime();

// Constantes com diversas rotas de diretórios e outros valores úteis
define ('APP_ENV',      $serverEnvironment);
define ('DEV',          $serverEnvironment === 'development');
define ('PROGRAM_NAME', basename($_SERVER['SCRIPT_FILENAME']));
define ('APP_DIR',      dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR);
define ('BASE_URL',     dirname($_SERVER['PHP_SELF'])        . DIRECTORY_SEPARATOR);
define ('APP_URL',      '//' . $_SERVER["HTTP_HOST"] .
    preg_replace(
        '#/(admin|admin_es|admin_callcenter|helpdesk)#', '',
        dirname($_SERVER['SCRIPT_NAME'])
    ) . DIRECTORY_SEPARATOR
);

$gmtDate = gmdate("D, d M Y H:i:s");
$cookie_login = (!empty($_COOKIE['sess']))
    ? get_cookie_login($_COOKIE['sess'])
    : get_cookie_login($token_cookie);
// echo "_..";
// print_r($cookie_login);exit;
// 14/12/2009 MLG HD:178792 Força o navegador do usuário a requisitar de novo a tela, se o usuário
//                          usar a função de navegação do browser (anterior...), assim, pelo menos,
//                          a tela vai estar com o 'login' atualizado (posto e fábrica)
header("Expires: {$gmtDate} GMT");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
header("Last-Modified: {$gmtDate} GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: text/html; charset=iso-8859-1");

$cook_posto_fabrica = $cookie_login['cook_posto_fabrica'];
$cook_fabrica       = $cookie_login['cook_fabrica'];
$cook_posto         = $cookie_login['cook_posto'];
$cook_login_unico   = $cookie_login['cook_login_unico'];
if ($cookie_login['cook_admin'])
    $cook_admin = $cookie_login['cook_admin'];

// $cook_posto_fabrica = $_COOKIE['cook_posto_fabrica'];
// $cook_fabrica       = $_COOKIE['cook_fabrica'];
// $cook_posto         = $_COOKIE['cook_posto'];
// $cook_login_unico   = $_COOKIE['cook_login_unico'];

$cache_bypass=md5(time());

//HD 783980 - Adicionar a Jacto à lista de fabricantes que usa outros idiomas.
// $fabrica_multinacional = array(14,20,87,46, 35);	// Intelbras, Bosch, Jacto

add_cookie($cookie_login, "cook_posto_fabrica", $cook_posto_fabrica);
add_cookie($cookie_login, "cook_fabrica",       $cook_fabrica);
add_cookie($cookie_login, "cook_posto",         $cook_posto);
add_cookie($cookie_login, "cook_login_unico",   $cook_login_unico);

set_cookie_login($token_cookie, $cookie_login);

// setcookie ("cook_posto_fabrica",$cook_posto_fabrica);
// setcookie ("cook_fabrica"      ,$cook_fabrica);
// setcookie ("cook_posto"        ,$cook_posto);
// setcookie ("cook_login_unico"  ,$cook_login_unico);

if ($cook_posto_fabrica == 'deleted') {
    echo "<center><b>Seu computador está possivelmente infectado por vírus que atrapalha o correto funcionamento deste site. É um vírus que deleta os <i>cookies</i> que o site precisa para trabalhar.<p>Por favor, atualize seu anti-vírus ou entre em contato com o suporte técnico que lhe vendeu este computador.<p>Qualquer dúvida, peça para que seu técnico entre em contato com a TELECONTROL. (14) 3413-6588 ou helpdesk@telecontrol.com.br </b></center>";
    exit;
}

//require_once dirname(realpath(__FILE__)) . DIRECTORY_SEPARATOR . 'token_form.php';

//Cookie gravado para tela: "pesquisa_remington"; HD 397539
//O posto receberá um convite via email para se credenciar na linha Remington ou Salton
$prt = $_GET['prt'];
if (strlen ($prt) > 0) {
    setcookie ("cookie_pesquisa_remington",$prt,time()+60*60*24*30);
    echo "<script language='javascript'> location.href=\"$PHP_SELF\" ; </script>";
}

#echo $cook_posto;
#echo "<br>";
#echo $cook_fabrica;
#echo "<br>";
#echo $cook_posto_fabrica;
#echo "<br>";
#echo $cook_login_unico;

if (strlen($cook_posto_fabrica) == 0 and strlen($cook_login_unico) == 0) {
    if (!empty($validaUsuarioLogadoEmail)) {
        include "mensagem_login.php";
        exit;
    }
    header ("Location: logout_2.php");
    exit;
}

if (PROGRAM_NAME != 'login_unico.php' and strlen ($cook_posto_fabrica) == 0 AND strlen($cook_login_unico) >  0) {
    header ("Location: login_unico.php");
    exit;
}

if (PROGRAM_NAME !== 'login_unico.php' and !$cook_fabrica and $cook_login_unico) {
    header ("Location: login_unico.php");
    exit;
}

if (!$cook_fabrica and !$cook_login_unico) {
    header ("Location: logout_2.php");
    exit;
}

if(strlen($cook_login_unico) > 0 AND $cook_login_unico <> 'temporario' and $cook_login_unico <> 'deleted'){
    $sql = "SELECT  login_unico,
                    posto      ,
                    nome       ,
                    email      ,
                    abre_os    ,
                    item_os    ,
                    fecha_os   ,
                    compra_peca,
                    extrato    ,
                    master     ,
                    distrib_total,
                    tecnico,
                    tecnico_posto
            FROM tbl_login_unico
            WHERE login_unico = $cook_login_unico;";
        $res = pg_query($con,$sql);
        extract(pg_fetch_assoc($res), EXTR_PREFIX_ALL, 'login_unico');
        $login_unico         = $login_unico_login_unico;
        $LU_abre_os          = $login_unico_abre_os       == 't';
        $LU_item_os          = $login_unico_item_os       == 't';
        $LU_fecha_os         = $login_unico_fecha_os      == 't';
        $LU_compra_peca      = $login_unico_compra_peca   == 't';
        $LU_extrato          = $login_unico_extrato       == 't';
        $LU_distrib_total    = $login_unico_distrib_total == 't';
        $LU_tecnico_posto    = $login_unico_tecnico_posto == 't';
        $LU_master           = $login_unico_master        == 't';
        $acessa_distrib      = $login_unico_distrib_total == 't';
        $login_unico_distrib_total = $acessa_distrib;
        $LU_tecnico          = $login_unico_tecnico;

        $LU_abre_os          = ($login_unico_master == 't') ? true : $LU_abre_os          ;
        $LU_item_os          = ($login_unico_master == 't') ? true : $LU_item_os          ;
        $LU_fecha_os         = ($login_unico_master == 't') ? true : $LU_fecha_os         ;
        $LU_compra_peca      = ($login_unico_master == 't') ? true : $LU_compra_peca      ;
        $LU_extrato          = ($login_unico_master == 't') ? true : $LU_extrato          ;
        $LU_tecnico_posto    = ($login_unico_master == 't') ? true : $LU_tecnico_posto    ;

        if(strlen($login_unico_tecnico) > 0){
            $sql = "SELECT fabrica FROM tbl_tecnico WHERE tecnico = $login_unico_tecnico";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $lu_tecnico_fabrica = pg_fetch_result($res,0,fabrica);
            }
        }
        unset($login_unico_login_unico);

        add_cookie($cookie_login,"cook_login_unico",$login_unico);
        add_cookie($cookie_login,"cook_posto",$login_unico_posto);

        set_cookie_login($token_cookie,$cookie_login);
        // setcookie(cook_login_unico,$login_unico);
        // setcookie(cook_posto,$login_unico_posto);
}

pg_query($con, "SET DateStyle TO ISO");

$sql = "SELECT
            tbl_posto.posto,
            tbl_posto.cnpj,
            tbl_posto.nome,
            tbl_posto.estado AS posto_estado,
            tbl_posto.pais,
            tbl_posto.cep,
            tbl_posto.parametros_adicionais as parametros_adicionais_posto, 
            tbl_fabrica.fabrica,
            tbl_fabrica.multimarca,
            tbl_fabrica.nome AS fabrica_nome,
            tbl_fabrica.parametros_adicionais AS fabrica_parametros_adicionais,
            tbl_fabrica.pedir_causa_defeito_os_item,
            tbl_fabrica.pedir_defeito_constatado_os_item,
            tbl_fabrica.pedir_solucao_os_item,
            tbl_fabrica.site,
            tbl_fabrica.logo,
            tbl_posto_fabrica.atendimento,
            tbl_posto_fabrica.atualizacao,
            tbl_posto_fabrica.categoria,
            tbl_posto_fabrica.codigo_posto,
            tbl_posto_fabrica.coleta_peca,
            tbl_posto_fabrica.contato_email,
            tbl_posto_fabrica.contato_estado,
            tbl_posto_fabrica.contato_cidade,
            tbl_posto_fabrica.contato_fone_comercial,
            tbl_posto_fabrica.controla_estoque,
            tbl_posto_fabrica.credenciamento,
            tbl_posto_fabrica.digita_os,
            tbl_posto_fabrica.distribuidor,
            tbl_posto_fabrica.entrega_tecnica,
            tbl_posto_fabrica.parametros_adicionais,
            tbl_posto_fabrica.pedido_em_garantia,
            tbl_posto_fabrica.pedido_faturado,
            tbl_posto_fabrica.pedido_via_distribuidor,
            tbl_posto_fabrica.reembolso_peca_estoque,
            tbl_posto_fabrica.tipo_posto,
            tbl_posto_fabrica.data_input,
            tbl_posto_fabrica.contato_endereco,
            tbl_posto_fabrica.contato_numero,
            tbl_posto_fabrica.contato_complemento,
            tbl_posto_fabrica.contato_bairro,
            tbl_posto_fabrica.contato_cep,
            tbl_posto_fabrica.contato_cidade,
            tbl_posto_fabrica.contato_estado,
            tbl_tipo_posto.distribuidor AS e_distribuidor,
            tbl_tipo_posto.tipo_revenda,
            tbl_tipo_posto.codigo AS tipo_posto_codigo,
            tbl_tipo_posto.descricao AS tipo_posto_descricao
       FROM tbl_posto
       JOIN tbl_posto_fabrica ON tbl_posto.posto              = tbl_posto_fabrica.posto
                             AND tbl_posto_fabrica.fabrica    = $cook_fabrica
       JOIN tbl_fabrica       ON tbl_posto_fabrica.fabrica    = tbl_fabrica.fabrica and tbl_fabrica.ativo_fabrica
       JOIN tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
      WHERE tbl_posto_fabrica.fabrica = $cook_fabrica
        AND tbl_posto_fabrica.posto   = $cook_posto";
$res = pg_query($con,$sql);

if (@pg_num_rows($res) == 0) {
    header ("Location: index.php");
    exit;
}

$atualizacao                      = trim(pg_fetch_result($res, 0, 'atualizacao'));
$login_categoria                  = trim(pg_fetch_result($res, 0, 'categoria'));
$login_cnpj                       = trim(pg_fetch_result($res, 0, 'cnpj'));
$login_codigo_posto               = trim(pg_fetch_result($res, 0, 'codigo_posto'));
$login_data_input                 = trim(pg_fetch_result($res, 0, 'data_input'));
$login_credenciamento             = trim(pg_fetch_result($res, 0, 'credenciamento'));
$login_distribuidor               = trim(pg_fetch_result($res, 0, 'distribuidor'));
$login_e_distribuidor             = trim(pg_fetch_result($res, 0, 'e_distribuidor'));
$login_email                      = pg_fetch_result($res,      0, "contato_email");
$login_cep                        = pg_fetch_result($res,      0, "cep");
$login_fabrica                    = trim(pg_fetch_result($res, 0, 'fabrica'));
$login_fabrica_logo               = trim(pg_fetch_result($res, 0, 'logo'));
$login_fabrica_nome               = trim(pg_fetch_result($res, 0, 'fabrica_nome'));
$login_nome                       = trim(pg_fetch_result($res, 0, 'nome'));
$login_posto_estado               = trim(pg_fetch_result($res, 0, 'posto_estado'));
$login_contato_estado             = trim(pg_fetch_result($res, 0, 'contato_estado'));
$login_contato_cidade             = trim(pg_fetch_result($res, 0, 'contato_cidade'));

$login_contato_bairro             = trim(pg_fetch_result($res, 0, 'contato_bairro'));
$login_contato_endereco           = trim(pg_fetch_result($res, 0, 'contato_endereco'));
$login_contato_numero             = trim(pg_fetch_result($res, 0, 'contato_numero'));
$login_contato_complemento        = trim(pg_fetch_result($res, 0, 'contato_complemento'));

$login_pais                       = trim(pg_fetch_result($res, 0, 'pais'));
$login_posto                      = trim(pg_fetch_result($res, 0, 'posto'));
$login_posto_atendimento          = pg_fetch_result($res,      0, 'atendimento');
$login_telefone                   = pg_fetch_result($res,      0, "contato_fone_comercial");
$login_tipo_posto                 = trim(pg_fetch_result($res, 0, 'tipo_posto'));
$site_fabrica                     = trim(pg_fetch_result($res, 0, 'site'));
$multimarca                       = trim(pg_fetch_result($res, 0, 'multimarca'));
$pedir_causa_defeito_os_item      = trim(pg_fetch_result($res, 0, 'pedir_causa_defeito_os_item'));
$pedir_defeito_constatado_os_item = trim(pg_fetch_result($res, 0, 'pedir_defeito_constatado_os_item'));
$pedir_solucao_os_item            = trim(pg_fetch_result($res, 0, 'pedir_solucao_os_item'));
$cook_tipo_posto_et               = pg_fetch_result($res,      0, 'tipo_revenda');
$fabrica_parametros_adicionais    = pg_fetch_result($res,      0, 'fabrica_parametros_adicionais');
$posto_parametros_adicionais      = pg_fetch_result($res,      0, 'parametros_adicionais');
$login_tipo_posto_codigo          = pg_fetch_result($res,      0, 'tipo_posto_codigo');
$login_tipo_posto_descricao       = pg_fetch_result($res,      0, 'tipo_posto_descricao');
// Campos BOOLEAN
$posto_controla_estoque           = pg_fetch_result($res, 0, 'controla_estoque')        == 't';
$login_posto_digita_os            = pg_fetch_result($res, 0, "digita_os")               == 't';
$login_pede_peca_garantia         = pg_fetch_result($res, 0, 'pedido_em_garantia')      ;
$pedido_via_distribuidor          = pg_fetch_result($res, 0, 'pedido_via_distribuidor') == 't';
$pedido_faturado                  = pg_fetch_result($res, 0, 'pedido_faturado')         == 't';
$coleta_peca                      = pg_fetch_result($res, 0, 'coleta_peca')             == 't';
$cook_tipo_posto_et               = pg_fetch_result($res, 0, 'tipo_revenda')            == 't';
$cook_entrega_tecnica             = pg_fetch_result($res, 0, "entrega_tecnica") ;
$login_reembolso_peca_estoque     = pg_fetch_result($res, 0, 'reembolso_peca_estoque')  == 't';
$mostra_vista_expodida_auto       = (bool)pg_fetch_result($res, 0, 'vista_explodida_automatica');

$loja_posto_endereco              = trim(pg_fetch_result($res, 0, 'contato_endereco'));
$loja_posto_numero                = trim(pg_fetch_result($res, 0, 'contato_numero'));
$loja_posto_complemento           = trim(pg_fetch_result($res, 0, 'contato_complemento'));
$loja_posto_bairro                = trim(pg_fetch_result($res, 0, 'contato_bairro'));
$loja_posto_cep                   = trim(pg_fetch_result($res, 0, 'contato_cep'));
$loja_posto_cidade                = trim(pg_fetch_result($res, 0, 'contato_cidade'));
$loja_posto_estado                = trim(pg_fetch_result($res, 0, 'contato_estado'));

$parametros_adicionais_posto               = trim(pg_fetch_result($res, 0, 'parametros_adicionais_posto'));
$parametros_adicionais_posto = json_decode($parametros_adicionais_posto, true);

// Parâmetros adicionais do Posto Autorizado, variéveis extraídas do JSON
if (strlen($posto_parametros_adicionais)) {
    $pa_posto = json_decode($posto_parametros_adicionais, true);
    extract($pa_posto);
}

//Código que verifica o campo parametros_adicionais na tbl_fábrica e que monta as variáveis de verificação
if (strlen($fabrica_parametros_adicionais)) {
    $pa_fabrica = json_decode($fabrica_parametros_adicionais, true);
    // var_dump($login_fabrica, $fabrica_parametros_adicionais, $pa_fabrica);
    extract($pa_fabrica);
}

$pais = (empty($pais)) ? "BR" : $pais;

// TcComm da Telecontrol se não estiver configurada na tbl_fabrica.
// Código que depende dos parâmetros adicionais da fábrica:
if (!$externalId) {
    $externalId    = 'smtp@posvenda';
    if (in_array($login_fabrica, array(169,170))) {
        $externalEmail = 'naorespondablueservice@carrier.com.br';
    }else{
        $externalEmail = 'noreply@telecontrol.com.br';
    }

}

if ($tipo_posto_multiplo) {
    $sqlT = "SELECT tipo_posto, descricao,
                    distribuidor, locadora, tipo_revenda, montadora, posto_interno
               FROM tbl_posto_tipo_posto AS ptp
               JOIN tbl_tipo_posto USING(tipo_posto)
              WHERE ptp.fabrica = $login_fabrica
                AND posto = $login_posto";
} else {
    $sqlT = "SELECT tbl_tipo_posto.*
               FROM tbl_posto_fabrica
               JOIN tbl_tipo_posto USING(tipo_posto, fabrica)
              WHERE fabrica = $login_fabrica
                AND ativo  IS TRUE
                AND posto   = $login_posto";
}


$resT = pg_query($con, $sqlT);

$login_posto_interno = false;

if (pg_num_rows($resT)) {
    $TipoPosto = array();
    while (is_array($tipoInfo = pg_fetch_assoc($resT))) {
        $tipo_posto = $tipoInfo['tipo_posto'];
        unset($tipoInfo['tipo_posto']);

        $TipoPosto[$tipo_posto] = $tipoInfo;
        $login_posto_interno = ($login_posto_interno or ($tipoInfo['posto_interno'] == 't'));
    }
    $json_info_posto = json_encode($TipoPosto);

    if ($login_fabrica == 24) {
        $login_posto_interno = pg_fetch_result($resT,0,'posto_interno');
    }
    unset($tipoInfo, $resT);
}


/*
if ($login_fabrica==11 and $login_posto<>6359) {
    echo "<TABLE border='0' width='100%' height='100%'>";
    echo "<TR><TD>";
    echo "<center><img src='logos/telecontrol2.jpg'>";
    echo "<BR>";
    echo "<font face='verdana' size='2'>ATENÇÃO<BR><BR>";
    echo "ESTAREMOS IMPLANTANDO NOVO CÓDIGO DE LOGIN PARA FACILITAR A DIGITAÇÃO DE ORDENS DE SERVIÇOS.<BR>";
    echo "O SISTEMA FICARÁ FORA DO AR, COM PREVISÂO DE RETORNO ÀS 10:00h.<BR>";
    echo "QUANDO CONSEGUIR ENTRAR NO SISTEMA E DIGITAR SEU LOGIN E SENHA, VOCÊ RECEBERÀ UM AVISO COM O <BR>";
    echo "SEU NOVO LOGIN. VOCÊ DEVERÁ UTILIZAR ESTE CÓDIGO PARA FAZER OS PRÓXIMOS ACESSOS AO SITE.<BR><BR>";
    echo "TELECONTROL NETWORKING</font></center>";
    exit;
}
*/

// 01/02/2012 - Barrado
/*
if($login_posto == 7104) {
    header("Location: https://www.telecontrol.com.br/");
    exit;
}
*/

add_cookie($cookie_login, 'cook_login_posto',        $login_posto);
add_cookie($cookie_login, 'cook_login_codigo_posto', $login_codigo_posto);
add_cookie($cookie_login, 'cook_login_fabrica',      $login_fabrica);
add_cookie($cookie_login, 'cook_login_tipo_posto',   $login_tipo_posto);

/**
 * Por enquanto, a decisão do idioma será baseada no país do posto.
 * Porém, deveria ser decisão do posto ou do login único qual idioma
 * usar.
 * Também, hoje NÃO TEMOS COMO SABER qual é o idioma oficial de cada país.
 * Assim, se não for Brasil, vai para espanhol.
 */
$cook_idioma = ($login_pais == 'BR' or trim($login_pais) == '') ? 'pt-br' : 'es';

if ($login_pais == 'US') {
    $cook_idioma = 'en-US';
}

$sistema_lingua = strtoupper(substr($cook_idioma, -2));

/**
 * Alterações para testes
 */
$get_idioma  = $_GET['idioma'] ? : $cook_idioma;
if (strlen ($get_idioma) > 0) {
    $cook_idioma = $get_idioma;
}

add_cookie($cookie_login, 'cook_sistema_lingua', $sistema_lingua);
add_cookie($cookie_login, 'cook_idioma',         $cook_idioma);
setcookie('cook_sistema_lingua', $sistema_lingua);
setcookie('cook_idioma',         $cook_idioma);

set_cookie_login($token_cookie, $cookie_login);

if (strlen ($login_distribuidor) == 0)
    $login_distribuidor = "null";

###########################################
### Monta variáveis para ajudar LOG     ###
###########################################
$var_post = "";
foreach($_POST as $key => $val) {
    $var_post .= "[" . $key . "]=" . $val . "; ";
}
foreach($_GET as $key => $val) {
    $var_get .= "[" . $key . "]=" . $val . "; ";
}


$sql = "/* PROGRAMA $PHP_SELF  # FABRICA $login_fabrica  #  POSTO $login_posto  # POST-FORM $var_post # GET-DATA $var_get  */";
$resX = @pg_exec ($con,$sql);

if (false) {    // Mudar para 'true' para ativar a mensagem
    echo "<CENTER><h1>ATENÇÃO</h1>";
    echo "<h3>O sistema passara por manutencao tecnica</h3>";
#   echo "<h3>Dentro de 10 minutos será restabelecido</h3>";
    echo "<h3>Sera desativado dentro de 5 minutos</h3>";
    echo "<p>&nbsp;</p>";
    echo "<h3>Agradecemos a compreensao !</h3>";
#   exit;
}


//include "log_inicio.php";

/*************************************************************
 * Define quais postos (values) de quais fábricas (keys) tem *
 * habilitada a opção de upload de OS.                       *
 *************************************************************/
$postosFazemUploadOS = array(
      3 => array(1537,1773),
     11 => array(5932,1537,21595),
     14 => array(6032,1773,39480),
    172 => array(5932,1537,21595),
);

/* 26-07-2012 - MLG - Movi o include e as funções de tradução para um arquivo, assim fica mais fácil alterar
 * mantendo as atualizações num único lugar */
include_once 'fn_traducao.php';
include_once 'valida_campos_obrigatorios.php';
#include_once 'funcoes.php';

$login_unico_log = $login_unico ? intval($login_unico) : 'NULL';
$login_fabrica_log = $login_fabrica ? : 'NULL';

$sql = "INSERT INTO tbl_log_conexao (
        programa, login_unico, fabrica, posto, pid
    ) VALUES (
        '$PHP_SELF', $login_unico_log, $login_fabrica, $login_posto, pg_backend_pid()
    )";
$res = pg_query($con, $sql);

/*
 *  Registra IP do usuario para posterior pesquisa WHOIS e PING
 *  06 sep 2017 IP doclient se está no LoadBalance
 */
if (empty($login_ip)) {
    $login_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ? : $_SERVER['REMOTE_ADDR'];
}

$sql = "SELECT * FROM tbl_ip_acesso WHERE posto = $login_posto AND data::DATE = CURRENT_DATE and ip = '$login_ip'";
$res = pg_query($con, $sql);
if (pg_num_rows($res) == 0 and !empty($login_ip)) {
    $login_ip = explode(',',$login_ip);
    $login_ip = (strlen($login_ip[1]) > 0) ? $login_ip[1] : $login_ip[0];
    $sql = "INSERT INTO tbl_ip_acesso (posto, ip) VALUES ($login_posto, (TRIM('$login_ip'))::cidr)";
    $res = pg_query($con, $sql);
}


/* ---------- Inicio do Auditor ----------- */
/*
$auditor_ip = $_SERVER['REMOTE_ADDR'];
if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARTDED_FOR'] != '') {
    $auditor_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $auditor_ip = $_SERVER['REMOTE_ADDR'];
}
if (strlen ($auditor_ip) == 0) {
    $auditor_ip = "0.0.0.0";
};
$auditor_url_api = "https://api2.telecontrol.com.br/auditor/auditor";

$array_dados = array (
    "application" => "02b970c30fa7b8748d426f9b9ec5fe70",
    "table" => "tbl_ip_acesso",
    "ip_access" => "$auditor_ip",
    "owner" => "$login_fabrica",
    "action" => "insert",
    "program_url" => "http://www.telecontrol.com.br/autentica_usuario.php",
    "primary_key" => "0",
    "user" => "$login_posto",
    "user_level" => "posto",
    "content" => array ("login" => "$login_fabrica - $login_posto")
);

$json_dados = json_encode ($array_dados);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $auditor_url_api);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_dados);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
curl_exec($ch);
curl_close($ch);

*/
/* --------- Fim do Auditor --------- */

include_once 'class/aws/s3_config.php';

//Para testes!!!
//$cook_idioma = 'es';

/**
 * 31/08/2015 MLG
 * Fábricas que têm habilitado o HD Posto
 * Movido aos autentica_*, pois é usando em pelo menos 6 scripts
 **/
$fabrica_hd_posto = array(1,3,11,30,35,42,72,151,153,163,172);
$libera_hd_posto_new = array(11,30,35,72,123,151,153,163,172,175,203); // libera a boia para o posto abrir chamados

if (in_array($login_fabrica, $fabrica_hd_posto)) {
    $helpdeskPostoAutorizado = true;
}

$fabrica_at_regiao = array(1, 151); // Fábricas que têm atendentes para diferentes regiões (cidade ou UF)
$posto_hd_alerta   = array(1, 42, 151);

// Pinsard/Ronald - 2015-06-22
// Em resposta à solicitação da Marisa sobre proteger o banco contra inteiros espúrios,
// o Ronald deu a ideia de filtrar o conteúdo de URLs que contenham o parâmetro "os"
// Pode não cobrir todos os casos, especialmente se o parâmetro não for nomeado "os".
// A solução definitiva acontecerá quando o banco for atualizado para versões maiores que 9.0
// Depois da atualização do banco, o bloco abaixo poderá ser removido do código.

if (!empty($_REQUEST['os']) and strlen($_REQUEST['os']) > 12 and is_numeric($_REQUEST['os'])) {
    $desabilita_tela = 'N&uacute;mero de ordem de servi&ccedil;o informado &eacute; inv&aacute;lido.';
}

if(((strtotime('now') > strtotime('2019-03-01 00:00:00') and strtotime('now') < strtotime('2019-03-07 08:00:00'))) and $login_fabrica == 117 ) {
	echo "<br><br><br><br><center style='top: 50%'><img src='logos/elgin_admin1.jpg' >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src='https://www.telecontrol.com.br/images/logo.png' height='56px'> ";
	echo "<br><br><h3>O sistema Telecontrol está em manutenção para a integração com o SAP da ELGIN com a finalidade de prover um serviço ainda mais eficiente e integrado.<br><br>Previsão do término será até 07/03/2019 08:00 </h3></center>";
	exit;
}


