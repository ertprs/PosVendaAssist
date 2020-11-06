<?php

header("X-Frame-Options: SAMEORIGIN");

include_once __DIR__ . DIRECTORY_SEPARATOR . 'dbconfig.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . 'includes/dbconnect-inc.php';

// include('helpdesk/mlg_funciones.php');

if(empty($_GET['redir'])){
    $token_cookie = null;
    $cookie_login = null;
    if(isset($_COOKIE['sess'])){
        unset($_COOKIE['sess']);
        setcookie("sess",FALSE,time()-3600);
    }
}else{
    if(isset($_COOKIE['sess'])){
        $token_cookie = $_COOKIE['sess'];
        $cookie_login = get_cookie_login($token_cookie);
    }
}

if($_POST['loginAcacia']){
	$paramLoginAcacia = "loginAcacia=1";
}

//$somente_login_unico = array(175);

// Variáveis para determinar o servidor de login
$http_server_name = null;
$http_referer = null;
$origem_xhr = null;

if (!empty($_SERVER['SERVER_NAME'])) {
    $http_server_name = $_SERVER['SERVER_NAME'];
}

if (!empty($_SERVER['HTTP_REFERER'])) {
    $http_referer     = $_SERVER['HTTP_REFERER'];
}

if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origem_xhr       = $_SERVER['HTTP_ORIGIN'];
}

$http_login_wp    = "http://$http_server_name" . dirname($_SERVER['PHP_SELF']) . "/index.php";

$allowed_servers  = array(
    'http://www.telecontrol.com.br',
    'http://brasil.telecontrol.com.br',
    'http://local.telecontrol.com.br',
    'http://devel.telecontrol.com.br',
    'http://novodevel.telecontrol.com.br',
    'http://192.168.0.30',
    'http://ww2.telecontrol.com.br',
    'https://ww2.telecontrol.com.br'
);

/*
p_echo("Link de retorno: $http_referer");
p_echo("Link login no-CORS: $http_login_wp");
pre_echo($_SERVER,  "Array _SERVER");
pre_echo($_GET,     "Array _GET");
pre_echo($_POST,    "Array _POST");
pre_echo($_COOKIE,  "Cookies...");
p_echo("Parâmetros GET: " . count($_GET));
p_echo("Parâmetros POST: " . count($_POST));
*/

if(strpos($http_referer,"admin/posto_cadastro") > 0 or strpos($http_referer,"admin/login_posto") > 0){
    $http_referer = "";
 } else{
    if(isset($cookie_login['cook_retorno_url'])){
        $cookie_login = remove_cookie($cookie_login,'cook_retorno_url');
        set_cookie_login($token_cookie,$cookie_login);
    }
 }

// Adiciona o 'www' se o usuário 'esqueceu' de colocar...
if (!isset($_SERVER['HTTP_REFERER']) and $http_server_name == 'telecontrol.com.br')
    header('Location: https://posvenda.telecontrol.com.br/assist/index.php');

/* Se não houver informações para processar, mostrar formulário de login */
if (count(array_filter($_GET))==1 and count(array_filter($_POST))==2) {
    //p_echo("Sem parâmetros para processar...");
    //die();
    if ($_POST['btnErro']=='login') {
        header('Location: ' . $http_login_wp);
        exit;
    }
    if ($http_referer     == '' or in_array($http_server_name, $allowed_servers)) {
        include 'frm_index.html'; //Arquivo que contémin o mesmo formulário do index.html, mas com os textos traduzidos
    } else {
        if(isset($cookie_login['cook_retorno_url'])){
            $cookie_login = remove_cookie($cookie_login,'cook_retorno_url');
            set_cookie_login($token_cookie,$cookie_login);
        }
        header('Location: ' . $http_referer);
        exit;
    }
}

/*******************************************************************
* Esta variável faz com que apareça o conteúdo do arquivo          *
* assist/www/tc_comunicado.php sobre a tela do posto ou            *
* do admin.                                                        *
* Serve para mostrar comunicado para todos os usuários do sistema. *
* Também cria uma 'cookie' de sessão 'cook_comunicado_telecontrol' *
* com o valor 'naoleu'                                             *
********************************************************************/
$mlg_hoje = strtotime('now');
if ($mlg_hoje > strtotime('2011-11-22 10:01:00') and $mlg_hoje < strtotime('2011-11-22 17:00:00'))
$mostra_comunicado_tc = true;

$ip_redir = null;
if (!empty($_GET['ip_redir'])) {
    $ip_redir = $_GET['ip_redir'];
}
/*if(strlen($ip_redir) == 0){
header("Location: http://201.77.210.68/assist/index.php?ip_redir=sim");
}*/

/*echo "<CENTER><h1>ATENÇÃO</h1>";
echo "<h3>O sistema passará por manutenção técnica</h3";
echo "<h3>Dentro de algumas horas será restabelecido</h3";
echo "<h3> </h3";
echo "<p><h3>Agradecemos a compreensão!</h3>";
exit;
*/

if (strlen($_POST["btnAcao"]) > 0) {$btnAcao = trim($_POST["btnAcao"]);}
if (strlen($_GET["btnAcao"]) > 0) {$btnAcao = trim($_GET["btnAcao"]);}

if (strlen($_POST["id"]) > 0)  {$id = trim($_POST["id"]);}
if (strlen($_GET["id"]) > 0)  {$id = trim($_GET["id"]);}

if (strlen($_POST["id2"]) > 0) {$id2 = trim($_POST["id2"]);}
if (strlen($_GET["id2"]) > 0) {$id2 = trim($_GET["id2"]);}

if (strlen($_POST["key1"]) > 0){$key1 = trim($_POST["key1"]);}
if (strlen($_GET["key1"]) > 0){$key1 = trim($_GET["key1"]);}

if (strlen($_POST["key2"]) > 0){$key2 = trim($_POST["key2"]);}
if (strlen($_GET["key2"]) > 0){$key2 = trim($_GET["key2"]);}

/*******************************************************************************
 * Estes headers são para habilitar o Cross Origin Resource Sharing (CORS), ou *
 * o acesso desde outros domínios via XHR (AJAX)                               *
 *******************************************************************************/
if ($_SERVER['REQUEST_METHOD']== 'POST' and $origem_xhr != '' and in_array($origem_xhr, $allowed_servers)) {
    header("Access-Control-Allow-Methods: GET, POST");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, *");
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

if($key1 == md5($id) AND $key2 == md5($id2)){
    if(strlen($id)>0 AND strlen($id2)>0 AND strlen($key1)>0 AND strlen($key2)>0 ){
    /*
    $sql = "SELECT tbl_admin.admin,hd_chamado,login,senha
    FROM tbl_hd_chamado
    JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
    WHERE hd_chamado     = $id
    AND  tbl_admin.admin = $id2
    AND(  status          = 'Resolvido' OR exigir_resposta IS TRUE)
    AND  resolvido IS NULL";
    */

        /*HD - 15025  Acertando a rotina de exigir resposta e chamado resolvido Raphael Giovanini*/
        $sql = "  SELECT tbl_admin.admin, hd_chamado, login, senha, tbl_admin.fabrica
                    FROM tbl_hd_chamado
                    JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
                   WHERE hd_chamado      = $id
                     AND tbl_admin.admin = $id2
                     AND (status         = 'Resolvido'
                      OR exigir_resposta IS TRUE)
                     AND resolvido       IS NULL";
        #if($ip=="201.71.54.144") echo $sql;
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 1) {
            $hd_chamado    = pg_fetch_result($res, 0, 'hd_chamado');
            $admin         = pg_fetch_result($res, 0, 'admin');
            $hd_login      = pg_fetch_result($res, 0, 'login');
            $hd_senha      = pg_fetch_result($res, 0, 'senha');
            $admin_fabrica = pg_fetch_result($res, 0, 'fabrica');
            $hd = "OK";
        }
    }
}

if (trim($_POST["btnAcao"]) == "OK") {

    $cnpj = trim($_POST["cnpj"]);

    if (strlen($_POST["cnpj"]) > 0) {
        $aux_cnpj = trim($_POST["cnpj"]);
        $aux_cnpj = str_replace(".","",$aux_cnpj);
        $aux_cnpj = str_replace("/","",$aux_cnpj);
        $aux_cnpj = str_replace("-","",$aux_cnpj);
        $aux_cnpj = str_replace(" ","",$aux_cnpj);
        header("Location: cadastra_senha.php?cnpj=$aux_cnpj");
        exit;
    }else{
        $msg_erro = "Digite seu CNPJ.";
    }
}

//print_r($_POST); exit;
$botao = strtolower(trim($_POST["btnAcao"]));
$ajax  = $_REQUEST['ajax'];
$acao  = $_REQUEST['acao'];
$redir = $_REQUEST['redir'];
//if ($acao == 'validar') {

if ($acao == "busca_cliente_admin") {

    $admin = $_GET["admin"];

    $sqlAdicionais = "SELECT parametros_adicionais
                      FROM tbl_admin
                      WHERE admin = {$admin}";
    $resAdicionais = pg_query($con, $sqlAdicionais);

    $arrParametrosAdicionais = json_decode(pg_fetch_result($resAdicionais, 0, 'parametros_adicionais'), true);

    $retorno = [];
    foreach ($arrParametrosAdicionais["clientes_admin"] as $cliente_admin) {

        $sqlDadosCliente = "SELECT cliente_admin,
                                   SUBSTRING(nome, 1, 20) AS nome, 
                                   SUBSTRING(cidade, 1, 15) AS cidade
                            FROM tbl_cliente_admin
                            WHERE cliente_admin = {$cliente_admin}
                            AND abre_os_admin IS TRUE";
        $resDadosCliente = pg_query($con, $sqlDadosCliente);

        $retorno[] = [
            "cliente_admin" => pg_fetch_result($resDadosCliente, 0, 'cliente_admin'),
            "nome"          => utf8_encode(pg_fetch_result($resDadosCliente, 0, 'nome')),
            "cidade"        => utf8_encode(pg_fetch_result($resDadosCliente, 0, 'cidade')),
        ];

    }

    exit(json_encode($retorno));

}


if ($botao == 'entrar' or $botao == 'enviar') {
    $login = trim($_POST["login"]);
    $senha = trim($_POST["senha"]);
    $idClienteAdmin = trim($_POST['cliente_admin']);

    if (!empty($login)) {
        // Só para Imbera
        $qry_imb = pg_query(
            $con,
            "SELECT * FROM tbl_admin WHERE login = '$login' AND fabrica = 158"
        );

        if (pg_num_rows($qry_imb)) {
            $ip_origem = $_SERVER["REMOTE_ADDR"];
            $qry_tentativa_acesso = pg_query(
                $con,
                "SELECT count(*) AS tentativas
                FROM tbl_tentativa_acesso
                WHERE login = '$login'
                AND data >= (current_timestamp - interval '15 minutes')"
            );

            $tentativas = pg_fetch_result($qry_tentativa_acesso, 0, 'tentativas');

            if ($tentativas >= 5) {
                $ins_tentativa = pg_query(
                    $con,
                    "INSERT INTO tbl_tentativa_acesso (
                        ip, login, senha
                    ) VALUES (
                        '$ip_origem', '$login', '$senha'
                    )"
                );

                exit("1|Conta bloqueada para acesso.");
            }
        }
    }

    //HD 415691 - Não permitir acesso sem senha (quando senha == '*')
    if ($senha == '*') {
        $xlogin = str_replace("/","",$login);
        $xlogin = str_replace("-","",$xlogin);
        $xlogin = strtolower ($xlogin);

        $xsenha = strtolower($senha);


        #------------- Pesquisa credenciamento do posto ---------------#
        $sql_cred = "SELECT  tbl_posto_fabrica.fabrica,
                        tbl_posto_fabrica.credenciamento
                FROM   tbl_posto
                JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                JOIN   tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
                AND tbl_fabrica.ativo_fabrica IS TRUE
                WHERE  (LOWER (tbl_posto_fabrica.codigo_posto) = LOWER ('$xlogin') or tbl_posto_fabrica.codigo_posto = '$login')
                AND    tbl_posto_fabrica.senha = '$senha'";
        $res_cred = pg_query($con,$sql_cred);
        $cred         = pg_fetch_result($res_cred, 0, 'credenciamento');
        $fabrica_cred = pg_fetch_result($res_cred, 0, 'fabrica');

        if ($fabrica_cred != 1 && $cred != 'Descred apr') {
            header('Content-Type: text/html; charset=utf-8');
            exit(utf8_encode("ko|<b>Senha inválida!</b><br />Se ainda não fez o primeiro acesso, acesse a tela <a href='http://posvenda.telecontrol.com.br/assist/externos/primeiro_acesso.php'>Primeiro Acesso</a> para escolher sua senha e liberar o acesso."));
        }
    }

    $tempsenha = explode("|",$senha);
    $tempemail = explode("@",$login);

    //login_unico
    if(count($tempemail)==2){

        $login = trim($_POST["login"]);
        $senha = trim($_POST["senha"]);
        $sql = " SELECT login_unico,posto
            FROM tbl_login_unico
            WHERE email = '$login'
            AND   senha = 'md5' || md5('$senha')
            AND   ativo IS TRUE
            AND   email_autenticado IS NOT NULL";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1 ) {

            $imp_sql=$sql;
            $posto       = pg_fetch_result($res,0,posto);
            $login_unico = pg_fetch_result($res,0,login_unico);

            if($token_cookie == null){
                $token_cookie = gera_token(0,$login_unico,$posto);
                setcookie("sess",$token_cookie,0,"","",false,true);
            }
            $cookie_login = get_cookie_login($token_cookie);
			$ip = get_ip();
            $cookie_login = add_cookie($cookie_login,"cook_login_unico",$login_unico);
            $cookie_login = add_cookie($cookie_login,"cook_posto",$posto);
            $cookie_login = add_cookie($cookie_login,"ip_login_unico",$ip);

            set_cookie_login($token_cookie,$cookie_login);

            if (strlen($http_referer)) {
                $cookie_login = add_cookie($cookie_login,"cook_retorno_url", $http_referer);
                set_cookie_login($token_cookie,$cookie_login);
            }

            $pagina = "login_unico.php?$paramLoginAcacia";
          
			if ($mostra_comunicado_tc)
				setcookie('cook_comunicado_telecontrol', 'naoleu');
            if ($ajax =="sim") {
                echo "ok|$pagina|$login_unico";
                exit;
            }else{

                header ("Location: $pagina");
                exit;
            }

        }else{
            $sql = " SELECT login_unico,email
                FROM tbl_login_unico
                WHERE email = '$login'
                AND   senha = 'md5' || md5('$senha')
                AND   ativo IS TRUE
                AND   email_autenticado IS NULL";

            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1 ) {
                $login_unico  = pg_fetch_result($res,0,0);
                $email        = pg_fetch_result($res,0,1);

                $chave1=md5($login_unico);
                $email_origem  = "helpdesk@telecontrol.com.br";
                $email_destino = $email;
                $assunto       = "Assist - Login Único";
                $corpo.="<P align=left><STRONG>Este e-mail é gerado automaticamente.<br>**** NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

                        <P align=justify>Você está recebendo novamente um email para validar sua conta. Para <FONT
                        color=#006600><STRONG>validar</STRONG></FONT> seu email,utilize o link abaixo:
                        <br><a href='http://posvenda.telecontrol.com.br/assist/login_unico_valida.php?id=$login_unico&key1=$chave1'><u><b>Clique aqui para validar seu email</b></u></a>.</P>
                        <br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>http://posvenda.telecontrol.com.br/assist/login_unico_valida.php?id=$login_unico&key1=$chave1<br>
                        <P align=justify>Suporte Telecontrol Networking.<BR>helpdesk@telecontrol.com.br
                        </P>";

                $body_top = "--Message-Boundary\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                $body_top .= "Content-transfer-encoding: 7BIT\n";
                $body_top .= "Content-description: Mail message body\n\n";

                if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ) {
                    $msg_erro = "Seu email não está autenticado, foi enviado um email para confirmação em: ".$email_destino;
                }

                if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
                if ($ajax=="sim") {
                    header('Content-Type: text/html; charset=utf-8');
                    exit(utf8_encode("1|$msg_erro"));
                }else{
                    header('Content-Type: text/html; charset=utf-8');
                    setcookie('errLogin', $msg_erro);
                    if ($_POST['btnErro'] == 'login') {
                        header('Location: ' . $http_login_wp . "?errLogin=$msg_erro");
                    } else {
                        header ("Location: ". $http_referer . "?errLogin=$msg_erro");
                    }
                    exit;
                }
            }else{
                $msg_erro = "Login ou senha invalidos";
            }
        }
    }

    if(count($tempemail)==2){

        $login = trim($_POST["login"]);
        $senha = trim($_POST["senha"]);

        $sql = "SELECT  fn_erp_autentica('$login','$senha');";
        $res = @pg_query ($con,$sql);
        if(@pg_num_rows($res)>0){
            $pagina = "time/index2.php?ajax=sim&acao=validar&redir=sim&login=$login&senha=$senha";
            if ($ajax=='sim') {
                exit("time|$pagina");
            } else {
                header('Location: /' . $pagina);
                exit;
            }
        }
        $sql = " SELECT pessoa,
                empregado,
                loja,
                tbl_empregado.empresa
            FROM tbl_pessoa
            JOIN tbl_empregado USING(pessoa)
            WHERE tbl_pessoa.email = '$login'
            AND tbl_empregado.senha = '$senha'
            AND tbl_empregado.ativo IS TRUE
            ";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) == 1) {
            $imp_sql=$sql;
            $pessoa     = pg_fetch_result($res,0,pessoa);
            $empregado  = pg_fetch_result($res,0,empregado);
            $empresa    = pg_fetch_result($res,0,empresa);
            $loja       = pg_fetch_result($res,0,loja);

            if($token_cookie == null){
                $token_cookie = gera_token(0,$login_unico,$posto);
                setcookie("sess",$token_cookie,0,"","",false,true);
            }
            $cookie_login = get_cookie_login($token_cookie);
            $cookie_login = add_cookie($cookie_login,"cook_empresa",$empresa);
            $cookie_login = add_cookie($cookie_login,"cook_loja",$loja);
            $cookie_login = add_cookie($cookie_login,"cook_admin",$empregado);
            $cookie_login = add_cookie($cookie_login,"cook_empregado",$empregado);
            $cookie_login = add_cookie($cookie_login,"cook_pessoa",$pessoa);
            set_cookie_login($token_cookie,$cookie_login);


			if(($empresa<>10 and $empresa <> 27 and $empresa<>49 ) or $login=="takashi@telecontrol.com.br"){
                $pagina = "time/index2.php?ajax=sim&acao=validar&redir=sim&login=$login&senha=$senha";
                if ($ajax=='sim') {
                    exit("time|$pagina");
                } else {
                    header('Location: /' . $pagina);
                    exit;
                }
            }else{
                $pagina = "erp/index.php";
                if ($ajax=='sim') {
                    exit("ok|$pagina");
                } else {
                    header('Location: ' . $pagina);
                    exit;
                }
            }
        }
    } else {
        $login = trim($_POST["login"]);
        $senha = trim($_POST["senha"]);

        if($hd=='OK'){
            $login = $hd_login;
            $senha = $hd_senha;
        }
        $tempsenha = $senha;

        if (isset($tempsenha)) {
            $temp_login = $login;

            if (isset($temp_login)) {

                $sql = " SELECT fabrica, login
                           FROM tbl_fabrica
                           JOIN tbl_admin USING(fabrica)
                          WHERE lower(tbl_admin.login)  = lower('$temp_login')
                            AND lower (tbl_admin.senha) = lower ('$tempsenha')
                            AND tbl_fabrica.ativo_fabrica IS TRUE
                            AND tbl_admin.ativo IS TRUE
                       ORDER BY privilegios";
                $res = pg_query ($con,$sql);
                if (pg_num_rows ($res) == 1) {
        			$fabrica = pg_fetch_result($res,0,fabrica);

                    #------------------- Pesquisa acesso ADMIN ------------------
                    $sql = "SELECT  tbl_admin.admin
                            FROM tbl_admin
                            WHERE  lower (tbl_admin.login) = lower ('$temp_login')
                            AND    lower (tbl_admin.senha) = lower ('$temp_senha')
                            AND    ativo IS TRUE
                            AND fabrica=10";
                    $res = pg_query ($con,$sql);
                    if (pg_num_rows ($res) == 1) {
                        $sql = "SELECT  tbl_admin.login,
                                    tbl_admin.senha
                                FROM tbl_admin
                                 WHERE  lower (tbl_admin.login) = lower ('$login')
                                AND fabrica <> 10 ORDER BY privilegios";
                        $res = pg_query ($con,$sql);
                        if (pg_num_rows ($res) > 0) {
                            $login = pg_fetch_result($res,0,login);
                            $senha = pg_fetch_result($res,0,senha);
                        }
                    }
                }
            } else {

                #------------------- Pesquisa acesso ADMIN ------------------
                $sql = "SELECT  tbl_admin.admin
                        FROM tbl_admin
                        WHERE  lower (tbl_admin.login) = lower ('$temp_login')
                        AND    lower (tbl_admin.senha) = lower ('$temp_senha')
                        AND    ativo IS TRUE
                        AND fabrica=10";
                $res = pg_query ($con,$sql);
                if (pg_num_rows ($res) == 1) {
                    $sql = "SELECT  tbl_admin.login,
                                tbl_admin.senha
                            FROM tbl_admin
                            WHERE  lower (tbl_admin.login) = lower ('$login')
                            AND fabrica<>10 ORDER BY privilegios";
                    $res = pg_query ($con,$sql);
                    if (pg_num_rows ($res) > 0) {
                        $senha = pg_fetch_result($res,0,senha);
                    }
                }
            }
        }
    }

    if (strlen($login) == 0) {
        $msg = "Informe seu CNPJ ou Login !!!";
    }else{
        if (strlen($senha) == 0) {
            $msg = "Informe sua senha !!!";
        }
    }

    if (strlen($msg) == 0) {
        $xlogin = str_replace("/","",$login);
        $xlogin = str_replace("-","",$xlogin);
        $xlogin = strtolower ($xlogin);

        $xsenha = strtolower($senha);

        #------------- Pesquisa posto pelo Login ---------------#
        $sql = "SELECT  tbl_posto_fabrica.posto_fabrica as posto_fabrica ,
                        tbl_posto_fabrica.posto,
                        tbl_posto_fabrica.fabrica,
                        tbl_posto_fabrica.credenciamento,
                        tbl_posto_fabrica.login_provisorio
                FROM   tbl_posto
                JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                JOIN   tbl_fabrica ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
                AND tbl_fabrica.ativo_fabrica IS TRUE
                WHERE  (lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin') or tbl_posto_fabrica.codigo_posto = '$login')
                -- AND    tbl_posto_fabrica.fabrica NOT IN(0, 168)
                AND    tbl_posto_fabrica.senha = '$senha'";
        $res = pg_query($con,$sql);
        #------- TULIO 04/05 - No usar mais validacao de email, at fazer uma tela que preste

        if (pg_num_rows ($res) == 1) {

            $fabrica = pg_fetch_result($res,0,fabrica);
            if($fabrica == 1){
                $arr_status_negativo = array('DESCREDENCIADO', 'pre_cadastro', 'Pr&eacute; Cadastro', 'Pr&eacute; Cad rpr', 'Pre Cadastro em apr');
            }else{
                $arr_status_negativo = array('DESCREDENCIADO');
            }

            // 2017-10-02 - Bloquear AkaciaEletro-M temporáriamente.
            /* if (pg_fetch_result($res, 0, 'fabrica') == 168) {
                $msg = 'Acesso temporáriamente desabilitado pelo fabricante.';
            } else */
            if (in_array(pg_fetch_result($res,0,credenciamento), $arr_status_negativo)) {
                if(pg_fetch_result($res,0,credenciamento) == 'DESCREDENCIADO'){
                    $msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
                }else{
                    $msg = '<!--OFFLINE-I-->Posto sem permissão de acesso. !<!--OFFLINE-F-->';
                }                
            }else{
                if($token_cookie == null){
                    $token_cookie = gera_token(pg_fetch_result($res,0,fabrica),"",pg_fetch_result($res,0,posto));
                    setcookie("sess",$token_cookie,0,"","",false,true);
                }
                
                $cookie_login = get_cookie_login($token_cookie);
				$ip = get_ip();
                $cookie_login = add_cookie($cookie_login,"cook_posto_fabrica", pg_fetch_result($res,0,posto_fabrica));
                $cookie_login = add_cookie($cookie_login,"cook_posto",         pg_fetch_result($res,0,posto));
                $cookie_login = add_cookie($cookie_login,"cook_fabrica",       pg_fetch_result($res,0,fabrica));
                $cookie_login = add_cookie($cookie_login,"ip_posto",       $ip);
                set_cookie_login($token_cookie,$cookie_login);

                if (strlen($http_referer)){
                    $cookie_login = add_cookie($cookie_login,"cook_retorno_url", $http_referer);
                    set_cookie_login($token_cookie,$cookie_login);
                }

                if(strlen($pedido)>0 and (strlen($login_admin)>0 OR strlen($login_unico)>0)){
                    if( strlen($login_admin) > 0 ){
                        $cookie_login = add_cookie($cookie_login,"cook_admin",$login_admin);
                    }
                    if( strlen($login_unico) > 0 ){
                        $cookie_login = add_cookie($cookie_login,"cook_login_unico",$cook_login_unico);
                    }

                    $cookie_login = add_cookie($cookie_login,"cook_plv",$pedido);
                    set_cookie_login($token_cookie,$cookie_login);
                } elseif (in_array($fabrica, [169, 170])) {
                    setcookie("cook_admin", $login_admin);
                }

                $pagina = "login.php?".$paramLoginAcacia;

                if($redir=='sim'){
                    header("Location: $pagina");
                    exit;
                }

                if (strlen($http_referer)){
                    add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                    set_cookie_login($token_cookie,$cookie_login);
                }

                $postoFab = pg_fetch_result($res,0,posto_fabrica);
                if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
               
                if ($ajax=="sim"){
                    exit("ok|$pagina|$postoFab");
                }else{
                    header ("Location: $pagina");
                    exit;
                }
            }
        }

        #------------- Pesquisa posto pelo CNPJ ---------------#
        $sql = "SELECT  tbl_posto_fabrica.posto_fabrica as posto_fabrica,
                        tbl_posto_fabrica.posto,
                        tbl_posto_fabrica.fabrica ,
                        tbl_posto_fabrica.credenciamento
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica = 11
                WHERE tbl_posto.cnpj                  = '$xlogin'
                AND   tbl_posto_fabrica.senha = '$senha'";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            if (pg_fetch_result($res,0,credenciamento) == 'DESCREDENCIADO') {
                $msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
            }else{
                //Wellington - Trocar aqui por "if (pg_fetch_result($res,0,fabrica)==11)" no dia 04/01 aps atualizar os cdigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
                if ( pg_fetch_result($res,0,posto)<>6359 and pg_fetch_result($res,0,fabrica)<>11 ) {
                    if($token_cookie == null){
                        $token_cookie = gera_token(pg_fetch_result($res,0,fabrica),"",pg_fetch_result($res,0,posto));
                        setcookie("sess",$token_cookie,0,"","",false,true);
                    }
                    $cookie_login = get_cookie_login($token_cookie);

                    $cookie_login = add_cookie($cookie_login, "cook_posto_fabrica",pg_fetch_result($res,0,posto_fabrica));
                    $cookie_login = add_cookie($cookie_login, "cook_posto",pg_fetch_result($res,0,posto));
                    $cookie_login = add_cookie($cookie_login, "cook_fabrica",pg_fetch_result($res,0,fabrica));

                    set_cookie_login($token_cookie,$cookie_login);

                    $pagina = "login.php?".$paramLoginAcacia;
                    if ($mostra_comunicado_tc) {
                        setcookie('cook_comunicado_telecontrol', 'naoleu');
                    }
                    if (strlen($http_referer)){
                        add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                        set_cookie_login($token_cookie,$cookie_login);
                    }
                    $postoFab = pg_fetch_result($res,0,posto_fabrica);

                    if ($ajax=="sim"){
                        exit("ok|$pagina|$postoFab");

                    }else{
                        header ("Location: $pagina");
                        exit;
                    }
                }else{
                    $sql = "SELECT codigo_posto
                            FROM   tbl_posto_fabrica
                            WHERE  posto   =". pg_fetch_result($res,0,posto)."
                            AND    fabrica =". pg_fetch_result($res,0,fabrica);
                    $res = pg_query ($con,$sql);
                    $novo_login = pg_fetch_result($res,0,0);
                    $msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
                }
            }
        }


        #------------------- Pesquisa acesso ADMIN ------------------
        #HD 233213 - Novo campo: responsavel_postos
        #2011-09-13 MLG - Novo campo Grupo ADMIN (Waldir)
        $sql = "SELECT tbl_admin.admin,
                       tbl_admin.fabrica,
                       tbl_admin.login,
                       tbl_admin.senha,
                       tbl_admin,admin_sap,
                       tbl_admin.privilegios,
                       tbl_admin.cliente_admin,
                       tbl_admin.grupo_admin,
                       tbl_admin.cliente_admin_master,
                       tbl_admin.representante_admin,
                       tbl_admin.representante_admin_master,
                       tbl_admin.responsavel_postos,
                       tbl_admin.help_desk_supervisor,
                       tbl_admin.atendente_callcenter,
                       (select tdocs_id from tbl_tdocs where tbl_tdocs.referencia = 'adminfoto' AND tbl_tdocs.referencia_id = tbl_admin.admin and tbl_tdocs.fabrica = tbl_admin.fabrica order by tdocs desc limit 1 ) as avatar,
                       tbl_admin.pais,
                       tbl_admin.parametros_adicionais
                  FROM tbl_admin
                  JOIN tbl_fabrica
                    ON tbl_fabrica.fabrica = tbl_admin.fabrica
                   AND tbl_fabrica.ativo_fabrica IS TRUE
                 WHERE LOWER(tbl_admin.login) = LOWER('$xlogin')
                   AND LOWER(tbl_admin.senha) = LOWER('$senha')
                   AND ativo IS TRUE";
        $res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {

			//Cria token para setar cookie
			if(($_REQUEST['mlg'] != 'sim' or empty($_REQUEST['mlg'])) and $http_server_name != "novodevel.telecontrol.com.br") {
				$sql = "SELECT admin FROM tbl_login_cookie where fabrica != 10 and admin = " . pg_fetch_result($res,0,'admin') ;
				$resa = pg_query($con, $sql);
				if(pg_num_rows($resa) > 0) {
					$msg_erro = ("<font color='#856404'>Existe uma sessão ativa</font><br /><a href = 'javascript: void(0);' onclick=\"loginDestroyLogged(".pg_fetch_result($resa,0,'admin') . ")\";><font color='#856404'>Clique <b>aqui</b> para finalizar e logar novamente!</font></a>");
					exit("ambiguous|{$msg_erro}|".pg_fetch_result($resa,0,'admin'));
				}
			}

            if (in_array(pg_fetch_result($res, 0, 'fabrica'), [158])) {

                $admId = pg_fetch_result($res, 0, "admin");

                if (empty($idClienteAdmin)) {

                    $arrParametrosAdicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
                    
                    if (count($arrParametrosAdicionais["clientes_admin"]) > 1) {

                        exit("cliente_admin_multiplo|".pg_fetch_result($res, 0, 'admin'));

                    } else if (count($arrParametrosAdicionais["clientes_admin"]) == 1) {

                        $sqlUpd = "UPDATE tbl_admin 
                                   SET cliente_admin = ".$arrParametrosAdicionais["clientes_admin"][0]."
                                   WHERE admin = {$admId}";
                        pg_query($con, $sqlUpd);

                        $idClienteAdmin = $arrParametrosAdicionais["clientes_admin"][0];

                    } else {

                        $sqlUpd = "UPDATE tbl_admin 
                                   SET cliente_admin = null
                                   WHERE admin = {$admId}";
                        pg_query($con, $sqlUpd);

                    }

                } else {

                    $admCliente = pg_fetch_result($res, 0, "admin");

                    $sqlUpd = "UPDATE tbl_admin 
                               SET cliente_admin = {$idClienteAdmin}
                               WHERE admin = {$admId}";
                    pg_query($con, $sqlUpd);

                }

            }

            if ($token_cookie == null) {
				if($_REQUEST['mlg'] == 'sim') {
					$token_cookie = gera_token(pg_fetch_result($res, 0, 'fabrica'),pg_fetch_result($res,0,'admin'), null);
				}else{
					$token_cookie = gera_token(pg_fetch_result($res, 0, 'fabrica'),pg_fetch_result($res,0,'admin'), null, pg_fetch_result($res,0,'admin'));
				}
				setcookie("sess",$token_cookie,0,"","",false,true);
            }

            /*
            * SESSION
            * Será criado um esquema de sessão dentro do sistema para controlar o login dos admin.
            * HD 955758 - Éderson Sandre
            */
            @session_destroy();
            session_start();
            $_SESSION['session_admin'] = array(
                'fabrica'    => pg_fetch_result($res, 0, 'fabrica'),
                'login'      => pg_fetch_result($res, 0, 'login'),
                'admin'      => pg_fetch_result($res, 0, 'admin'),
                'programa'   => $_SERVER['PHP_SELF'],
                'session_id' => session_id()
            );

            $timestamp = date("Y-m-d H:i:s");
            $difftime  =  date('Y-m-d H:i:s', strtotime("- {$time_user_online} minutes", strtotime($timestamp)));



            $pais  = pg_fetch_result($res,0,pais) ;
            $admin = pg_fetch_result($res,0,admin);
            $responsavel_postos   = pg_fetch_result($res, 0, 'responsavel_postos'); #HD 233213
            $help_desk_supervisor = pg_fetch_result($res, 0, 'help_desk_supervisor');
            $atendente_callcenter = pg_fetch_result($res, 0, 'atendente_callcenter');
            $admin_sap            = pg_fetch_result($res, 0, 'admin_sap');

            $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ?
                $_SERVER['HTTP_X_FORWARDED_FOR'] :
                $_SERVER['REMOTE_ADDR'];

            $sql2 = "UPDATE tbl_admin
                        SET ultimo_ip = '$ip' ,
                            ultimo_acesso = CURRENT_TIMESTAMP
                      WHERE admin = $admin";

            $res2 = pg_query($con,$sql2);


            if ($token_cookie == null) {
				if($_REQUEST['mlg'] == 'sim') {
					$token_cookie = gera_token(pg_fetch_result($res, 0, 'fabrica'),pg_fetch_result($res,0,'admin'), null);
				}else{
					$token_cookie = gera_token(pg_fetch_result($res, 0, 'fabrica'),pg_fetch_result($res,0,'admin'), null, pg_fetch_result($res,0,'admin'));
				}
                setcookie("sess",$token_cookie,0,"","",false,true);
            }

            $login_admin = pg_fetch_result($res, 0, 'admin');
            $cookie_login = get_cookie_login($token_cookie);

            if ($pais<>'BR') {
                $cookie_login = add_cookie($cookie_login, "cook_admin_es", $login_admin);
                $cookie_login = add_cookie($cookie_login, 'cook_idioma',   substr(strtolower($pais), 0, 2));
            }else{
                $cookie_login = add_cookie($cookie_login, "cook_admin",  $login_admin);
                $cookie_login = add_cookie($cookie_login, 'cook_idioma', 'pt-br');
            }

            // HD 3765114 - Foto na 'cookie'
            if ($fotoID = pg_fetch_result($res, 0, 'avatar')) {
                include_once('class/tdocs.class.php');
                $tDocs = new TDocs($con, pg_fetch_result($res, 0, 'fabrica'), 'adminfoto');
                $linkAvatar = $tDocs->getDocumentLocation($fotoID, true);

                if ($linkAvatar) {
                    $cookie_login = add_cookie($cookie_login, 'cook_avatar', $linkAvatar);
                }
            }

            $cookie_login = add_cookie($cookie_login, "cook_grupo_admin",   pg_fetch_result($res, 0, grupo_admin));
            $cookie_login = add_cookie($cookie_login, "cook_fabrica",       pg_fetch_result($res, 0, fabrica));
            $cookie_login = add_cookie($cookie_login, "cook_posto_fabrica", "");
            $cookie_login = add_cookie($cookie_login, "cook_posto",         "");


            if (strlen($http_referer)) {
                $cookie_login = add_cookie($cookie_login,"cook_retorno_url",$http_referer);
            }

            set_cookie_login($token_cookie,$cookie_login);
            // echo "->".$token_cookie."<-";exit;

            $privilegios = pg_fetch_result($res,0,privilegios);
            $acesso = explode(",",$privilegios);

            if($hd=='OK'){
                if($admin_fabrica == 10){
                    $pagina = "helpdesk/adm_chamado_detalhe.php?hd_chamado=$hd_chamado";
                }else{
                    $pagina = "helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado";
                }
                header("Location:$pagina");
                exit;
            }

            if($pais<>'BR'){
                $pagina = "admin_es/menu_gerencia.php";
                if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
                if ($ajax=="sim"){
                    exit("ok|$pagina");
                }else{
                    header ("Location: $pagina");
                    exit;
                }
                exit;
            }

            if($fabrica == 86){
               if (in_array($admin,array(6306,6017,2339,5415))){
                   $responsabilidade = "alerta_pedido";
               }
            }
            for($i = 0; $i < count($acesso); $i++) {

                if(strlen($acesso[$i]) > 0) {

                    if($fabrica == 86 AND $responsabilidade == "alerta_pedido"){
                        $pagina="admin/pedidos_abertos.php";
                    }else if ($fabrica == 183 AND ($admin_sap == "t" OR $privilegios == '*')) {
                        $pagina = "admin/relatorio_agendamentos_pendentes.php";
                    } else if ($responsavel_postos == 't') {
                        $pagina="admin/em_descredenciamento.php";
                    } else if ( $help_desk_supervisor=='t'){
                        $pagina="admin/hd_aguarda_aprovacao.php";
                    } else if($fabrica == 189 AND $acesso[$i] == "*"){
			            $pagina = "admin/acompanhamento_atendimentos.php";
		            } else {
                        if ($acesso[$i] == "gerencia") {
                            $pagina = "admin/menu_gerencia.php";
                        } elseif ($acesso[$i] == "call_center") {
                        if($atendente_callcenter == 't' && in_array($fabrica,array(122,125))){
                            $pagina = "admin_callcenter/menu_callcenter.php";
                        } else {
                            $pagina = "admin/menu_callcenter.php";
                        }
                        } elseif ($acesso[$i] == "cadastros") {
                            $pagina = "admin/menu_cadastro.php";
                        } elseif ($acesso[$i] == "info_tecnica") {
                            $pagina = "admin/menu_tecnica.php";
                        } elseif ($acesso[$i] == "financeiro") {
                            $pagina = "admin/menu_financeiro.php";
                        } elseif ($acesso[$i] == "auditoria") {
                            $pagina  = "admin/menu_auditoria.php";
                        } elseif ($acesso[$i] == "*") {
                            $pagina="admin/menu_cadastro.php";
                        }

                    }

                }

                if (empty($idClienteAdmin)) {
                    $cliente_admin        = pg_fetch_result($res,0,cliente_admin);
                } else {
                    $cliente_admin = $idClienteAdmin;
                }

                $cliente_admin_master = pg_fetch_result($res,0,cliente_admin_master);
                
                if (strlen($cliente_admin)>0) {
                    $cookie_login = add_cookie($cookie_login,"cook_cliente_admin",$cliente_admin);
                    $cookie_login = add_cookie($cookie_login,"cook_cliente_admin_master",$cliente_admin_master);

                    set_cookie_login($token_cookie,$cookie_login);
                if (strlen($http_referer))
                    $cookie_login = add_cookie($cookie_login,"cook_retorno_url",$http_referer);
                    set_cookie_login($token_cookie,$cookie_login);
                    if ($fabrica == 190) {
                        $pagina = "admin_cliente/menu_contrato.php";
                    } else {
                        $pagina = "admin_cliente/menu_callcenter.php";
                    }

                }


                $representante_admin        = pg_fetch_result($res,0,representante_admin);
                $representante_admin_master = pg_fetch_result($res,0,representante_admin_master);

                if (strlen($representante_admin)>0) {
                    $cookie_login = add_cookie($cookie_login,"cook_representante_admin",$representante_admin);
                    $cookie_login = add_cookie($cookie_login,"cook_representante_admin_master",$representante_admin_master);

                    set_cookie_login($token_cookie,$cookie_login);
                    if (strlen($http_referer))
                        $cookie_login = add_cookie($cookie_login,"cook_retorno_url",$http_referer);
                        set_cookie_login($token_cookie,$cookie_login);

                        $pagina = "admin_representante/menu_contrato.php";
                }

                if ($admin == 1152) {
                    $pagina = 'admin/relatorio_peca_sem_preco.php?tabela=215';
                }

                if (strlen($pagina) == 0) { /*HD - 4417123*/
                    $pagina = "admin/menu_callcenter.php";
                }

                if ($mostra_comunicado_tc){
                    $cookie_login = add_cookie($cookie_login,"cook_comunicado_telecontrol","naoleu");
                    set_cookie_login($token_cookie,$cookie_login);
                }
                if ($ajax=="sim") {
                    exit("ok|$pagina");
                }else{
                    header ("Location: $pagina");
                    exit;
                }
            }

        } else {
            if (!empty($login)) {
                $qry_imb = pg_query(
                    $con,
                    "SELECT * FROM tbl_admin WHERE login = '$login' AND fabrica = 158"
                );

                if (pg_num_rows($qry_imb)) {
                    $ins_tentativa = pg_query(
                        $con,
                        "INSERT INTO tbl_tentativa_acesso (
                            ip, login, senha
                        ) VALUES (
                            '$ip_origem', '$login', '$senha'
                        )"
                    );
                }
            }
        }

        if (strlen ($msg) == 0) {
            $msg = "Login ou senha inválidos !!!";
        }

        $cookie_login = get_cookie_login($token_cookie);
        $cookie_login = add_cookie($cookie_login,"cook_posto_fabrica","");
        $cookie_login = add_cookie($cookie_login,"cook_admin","");
        set_cookie_login($token_cookie,$cookie_login);
    }

}

if(strlen($acao_unico)>0){
    if (strlen($msg) == 0) {
        $xlogin = str_replace(".","",$login);
        $xlogin = str_replace("/","",$xlogin);
        $xlogin = str_replace("-","",$xlogin);
        $xlogin = strtolower ($xlogin);

        $xsenha = strtolower($senha);

        #------------- Pesquisa posto pelo Login ---------------#
        $sql = "SELECT  tbl_posto_fabrica.posto_fabrica as posto_fabrica ,
                        tbl_posto_fabrica.posto,
                        tbl_posto_fabrica.fabrica,
                        tbl_posto_fabrica.credenciamento,
                        tbl_posto_fabrica.login_provisorio
                FROM   tbl_posto
                JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE  (lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin') or tbl_posto_fabrica.codigo_posto = '$login')
                AND    tbl_posto_fabrica.senha = '$senha'";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            if (pg_fetch_result($res,0,credenciamento) == 'DESCREDENCIADO') {
                $msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
            } elseif (pg_fetch_result($res,0,login_provisorio) == 't' AND 1==2 ) {
                $msg = '<!--OFFLINE-I-->Para acessar  necessrio realizar a confirmao no email.<!--OFFLINE-F-->';
            }else{
                if($token_cookie == null){
                    $token_cookie = gera_token(pg_fetch_result($res,0,fabrica),"",pg_fetch_result($res,0,posto));
                    setcookie("sess",$token_cookie,0,"","",false,true);
                }
                $cookie_login = get_cookie_login($token_cookie);

                $cookie_login = add_cookie($cookie_login,"cook_posto_fabrica",pg_fetch_result($res,0,posto_fabrica));
                $cookie_login = add_cookie($cookie_login,"cook_posto",pg_fetch_result($res,0,posto));
                $cookie_login = add_cookie($cookie_login,"cook_fabrica",pg_fetch_result($res,0,fabrica));
                $cookie_login = add_cookie($cookie_login,"cook_login_unico","temporario");
                set_cookie_login($token_cookie,$cookie_login);

                $pagina = "login_unico_cadastro.php";
                if (strlen($http_referer)){
                    add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                    set_cookie_login($token_cookie,$cookie_login);
                }

                header("Location: $pagina");
                exit;
            }
        }

        #------------- Pesquisa posto pelo CNPJ ---------------#
        $sql = "SELECT  tbl_posto_fabrica.posto_fabrica as posto_fabrica,
                        tbl_posto_fabrica.posto,
                        tbl_posto_fabrica.fabrica ,
                        tbl_posto_fabrica.credenciamento
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica = 11
                WHERE tbl_posto.cnpj                  = '$xlogin'
                AND   tbl_posto_fabrica.senha = '$senha'";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            if (pg_fetch_result($res,0,credenciamento) == 'DESCREDENCIADO') {
                $msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
            }else{
                //Wellington - Trocar aqui por "if (pg_fetch_result($res,0,fabrica)==11)" no dia 04/01 aps atualizar os cdigos dos postos da tabela tbl_posto_fabrica com os dados da tabela temp_lenoxx_posto_fabrica
                if ( pg_fetch_result($res,0,posto)<>6359 and pg_fetch_result($res,0,fabrica)<>11 ) {

                    add_cookie ($cookie_login,"cook_posto_fabrica",pg_fetch_result($res,0,posto_fabrica));
                    add_cookie ($cookie_login,"cook_posto",pg_fetch_result($res,0,posto));
                    add_cookie ($cookie_login,"cook_fabrica",pg_fetch_result($res,0,fabrica));
                    if (strlen($http_referer)){
                        add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                    }
                    set_cookie_login($token_cookie,$cookie_login);

                    $pagina = "login_unico_cadastro.php";
                    header ("Location: $pagina");
                    exit;
                }else{
                    $sql = "SELECT codigo_posto
                            FROM   tbl_posto_fabrica
                            WHERE  posto   =". pg_fetch_result($res,0,posto)."
                            AND    fabrica =". pg_fetch_result($res,0,fabrica);
                    $res = pg_query ($con,$sql);
                    $novo_login = pg_fetch_result($res,0,0);
                    $msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
                }
            }
        }
        header("Location: ../login_unico.php?msg=1&$paramLoginAcacia");
        exit;
    }
}

if(strlen($msg)>0 OR strlen($pagina)>0){
    if(strlen($msg)>0){
        if ($ajax=="sim"){
            header('Content-Type: text/html; charset=utf-8');
            exit(utf8_encode("1|$msg"));
        }else{
            header('Content-Type: text/html; charset=utf-8');
            setcookie('errLogin', utf8_encode($msg), 0, '/');
            if ($_POST['btnErro'] == 'login') {
                header('Location: ' . $http_login_wp . "?errLogin=$msg");
            } else {
                header ("Location: ". $http_referer);
            }
            exit;
        }
    }else{
        if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
        if ($ajax=="sim"){
            echo "ok|$pagina";
        }else{
            header ("Location: $pagina");
        exit;
        }
    }
    exit;
}

foreach ($_COOKIE as $k => $v) {
    setcookie($k, '');
}
unset($_COOKIE);


$ip_redir = $_GET['ip_redir'];
/*if(strlen($ip_redir) == 0){
    header("Location: http://201.77.210.68/assist/index.php?ip_redir=sim");
}*/

if (strlen($_POST["btnAcao"]) > 0) {
    $btnAcao = trim($_POST["btnAcao"]);
}

if (strlen($_POST["id"]) > 0) {
    $id = trim($_POST["id"]);
}
if (strlen($_POST["id2"]) > 0) {
    $id2 = trim($_POST["id2"]);
}
if (strlen($_POST["key1"]) > 0) {
    $key1 = trim($_POST["key1"]);
}
if (strlen($_POST["key2"]) > 0) {
    $key2 = trim($_POST["key2"]);
}
if($key1 == md5($id) AND $key2 == md5($id2)){
    if(strlen($id)>0 AND strlen($id2)>0 AND strlen($key1)>0 AND strlen($key2)>0 ){

        $sql = "SELECT tbl_admin.admin,hd_chamado,login,senha
                FROM tbl_hd_chamado
                JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
                WHERE hd_chamado     = $id
                AND  tbl_admin.admin = $id2
                AND  status          = 'Resolvido'
                AND  resolvido IS NULL";

        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
            $hd_chamado = pg_fetch_result($res,0,hd_chamado);
            $admin      = pg_fetch_result($res,0,admin);
            $hd_login   = pg_fetch_result($res,0,login);
            $hd_senha   = pg_fetch_result($res,0,senha);
            $hd = "OK";
        }



    }
}

if (trim($_POST["btnAcao"]) == "OK") {

    $cnpj = trim($_POST["cnpj"]);

    if (strlen($_POST["cnpj"]) > 0) {
        $aux_cnpj = trim($_POST["cnpj"]);
        $aux_cnpj = str_replace(".","",$aux_cnpj);
        $aux_cnpj = str_replace("/","",$aux_cnpj);
        $aux_cnpj = str_replace("-","",$aux_cnpj);
        $aux_cnpj = str_replace(" ","",$aux_cnpj);
        header("Location: cadastra_senha.php?cnpj=$aux_cnpj");
        exit;
    }else{
        $msg_erro = "Digite seu CNPJ.";
    }
}

$botao = strtolower(trim($_POST["btnAcao"]));
if ($botao == "enviar"  OR $botao == "entrar" OR $hd=="OK") {

    $login = trim($_POST["login"]);
    $senha = trim($_POST["senha"]);

    $sql = " SELECT fabrica
           FROM tbl_fabrica
           WHERE lower(nome )= lower('$login');";
    $res = pg_query ($con,$sql);

    $tempsenha = explode("|",$senha);
    if ((pg_num_rows ($res) == 1) and (count($tempsenha)==2) and 1 == 2) {

        $senha = trim($_POST["senha"]);

        $tempsenha = explode("|",$senha);
        if (count($tempsenha)==2){
            $temp_login = $tempsenha[0];
            $temp_senha = $tempsenha[1];
            #------------------- Pesquisa acesso ADMIN ------------------
            $sql = "SELECT  tbl_admin.admin,
                        tbl_admin.privilegios
                    FROM tbl_admin
                    WHERE  lower (tbl_admin.login) = lower ('$temp_login')
                    AND    lower (tbl_admin.senha) = lower ('$temp_senha')
                    AND    ativo IS TRUE
                    AND fabrica=10";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1) {
                $sql = "select nome,fabrica
                        from tbl_fabrica
                        where lower (nome) = lower ('$login');";
                $res = pg_query ($con,$sql);
                if (pg_num_rows ($res) > 0) {
                    $xlogin= $temp_login;
                    $senha = $temp_senha;
                    $fabrica_master = pg_fetch_result($res,0,fabrica);
                    $login_master= pg_fetch_result($res,0,nome);
                }
            }else{
                $msg="erro de login";
            }
        }

        if (strlen($login) == 0) {
            $msg = "Informe seu CNPJ ou Login !!!";
        }else{
            if (strlen($senha) == 0) {
                $msg = "Informe sua senha !!!";
            }
        }

        if (strlen($msg) == 0) {
            #------------------- Pesquisa acesso ADMIN ------------------
            $sql = "SELECT  tbl_admin.admin       ,
                        tbl_admin.login       ,
                        tbl_admin.senha       ,
                        tbl_admin.privilegios ,
                        tbl_admin.pais
                    FROM tbl_admin
                    JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica AND tbl_fabrica.ativo_fabrica is true
                    WHERE  lower (tbl_admin.login) = lower ('$temp_login')
                    AND    lower (tbl_admin.senha) = lower ('$temp_senha')
                    AND    ativo IS TRUE";

            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1) {

                $pais  = pg_fetch_result($res,0,pais) ;
                $admin = pg_fetch_result($res,0,admin);
                $ip    = $_SERVER['REMOTE_ADDR'] ;
                $sql2 = "UPDATE tbl_admin SET
                            ultimo_ip = '$ip' ,
                            ultimo_acesso = CURRENT_TIMESTAMP
                         WHERE admin = $admin";

                $res2 = pg_query($con,$sql2);

                if ($pais<>'BR') setcookie ("cook_admin_es",pg_fetch_result($res,0,admin));
                else             setcookie ("cook_admin",pg_fetch_result($res,0,admin))   ;



                // $cookie_login = get_cookie_login($token_cookie);
                // $cookie_login = add_cookie($cookie_login,"cook_posto_fabrica","");
                // $cookie_login = add_cookie($cookie_login,"cook_admin","");


                if($token_cookie == null){
                    $token_cookie = gera_token(pg_fetch_result($res,0,fabrica),"",pg_fetch_result($res,0,posto));
                    setcookie("sess",$token_cookie,0,"","",false,true);
                }
                $cookie_login = get_cookie_login($token_cookie);

                add_cookie($cookie_login,"cook_master",$login_master);
                add_cookie($cookie_login,"cook_fabrica",$fabrica_master);
                add_cookie($cookie_login,"cook_admin",$admin);
                set_cookie_login($token_cookie,$cookie_login);

                if (strlen($http_referer)){
                    add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                    set_cookie_login($token_cookie,$cookie_login);
                }

                $privilegios = pg_fetch_result($res,0,privilegios);
                $acesso = explode(",",$privilegios);

                if($pais<>'BR'){
                    header("Location: admin_es/menu_gerencia.php");
                    exit;
                }

                for($i=0; $i < count($acesso); $i++){
                    if(strlen($acesso[$i]) > 0){
                        if ($acesso[$i] == "gerencia"){
                            $pagina = "admin/menu_gerencia.php";
                        }elseif ($acesso[$i] == "call_center"){
                            $pagina = "admin/menu_callcenter.php";
                        }elseif ($acesso[$i] == "cadastros"){
                            $pagina = "admin/menu_cadastro.php";
                        }elseif ($acesso[$i] == "info_tecnica"){
                            $pagina = "admin/menu_tecnica.php";
                        }elseif ($acesso[$i] == "financeiro"){
                            $pagina = "admin/menu_financeiro.php";
                        }elseif ($acesso[$i] == "auditoria"){
                            $pagina = "admin/menu_auditoria.php";
                        }elseif ($acesso[$i] == "*"){
                            $pagina = "admin/menu_cadastro.php";
                        }else if($fabrica == 189 AND $acesso[$i] == "*"){
			    $pagina = "admin/acompanhamento_atendimentos.php";
			}
                        if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
                        if ($ajax=="sim"){
                            echo "ok|$pagina";
                            exit;
                        }else{
                            header ("Location: $pagina");
                            exit;
                        }
                    }
                }

            }else{
                $msg = "<!--OFFLINE//-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE//-F-->";
            }
            if (strlen ($msg) == 0) {
                $msg = "<!--OFFLINE//-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE//-F-->";
            }
        }else{
            $msg = "<!--OFFLINE//-I-->ERRO MESMO!!!<!--OFFLINE//-F-->";
        }
    }else{
        $tempemail = explode("@",$login);
        if(count($tempemail)==2){

            $login = trim($_POST["login"]);
            $senha = trim($_POST["senha"]);


            $sql = " SELECT pessoa,
                    empregado,
                    loja,
                    tbl_empregado.empresa
                FROM tbl_pessoa
                JOIN tbl_empregado USING(pessoa)
                WHERE tbl_pessoa.email = '$login'
                AND tbl_empregado.senha = '$senha'
                AND tbl_empregado.ativo IS TRUE
                ";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1) {
                $imp_sql=$sql;
                $pessoa     = pg_fetch_result($res,0,pessoa);
                $empregado  = pg_fetch_result($res,0,empregado);
                $empresa    = pg_fetch_result($res,0,empresa);
                $loja       = pg_fetch_result($res,0,loja);

                if($token_cookie == null){
                    $token_cookie = gera_token(pg_fetch_result($res,0,fabrica),"",pg_fetch_result($res,0,posto));
                    setcookie("sess",$token_cookie,0,"","",false,true);
                }
                $cookie_login = get_cookie_login($token_cookie);

                add_cookie($cookie_login,"cook_empresa",$empresa);
                add_cookie($cookie_login,"cook_loja",$loja);
                add_cookie($cookie_login,"cook_admin",$empregado);
                add_cookie($cookie_login,"cook_empregado",$empregado);
                add_cookie($cookie_login,"cook_pessoa",$pessoa);
                set_cookie_login($token_cookie,$cookie_login);

                 header("Location: erp/index.php");
            }else{
                $msg_erro ="Login ou senha invalidos.";

                $login = trim($_POST["login"]);
                $senha = trim($_POST["senha"]);
                $sql = " SELECT revenda
                    FROM tbl_revenda
                    WHERE email = '$login'
                    AND   senha = '$senha'";

                $res = pg_query ($con,$sql);

                if (pg_num_rows ($res) == 1) {
                    $imp_sql=$sql;
                    $revenda     = pg_fetch_result($res,0,revenda);

                    setcookie ("cook_revenda",$revenda);
                    if (strlen($http_referer)){
                        add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                        set_cookie_login($token_cookie,$cookie_login);
                    }
                    header("Location: revend/index.php");
                }else{
                    $msg_erro ="Login ou senha invalidos.";
                }
            }
        }else{

            $login = trim($_POST["login"]);
            $senha = trim($_POST["senha"]);
            if($hd=='OK'){
                $login = $hd_login   ;
                $senha = $hd_senha   ;
            }

            $tempsenha = explode("|",$senha);
            if (count($tempsenha)==2 and 1==2){
            }
        }

        if (strlen($login) == 0) {
            $msg = "Informe seu CNPJ ou Login !!!";
        }else{
            if (strlen($senha) == 0) {
                $msg = "Informe sua senha !!!";
            }
        }

        if (strlen($msg) == 0) {
            $xlogin = str_replace(".","",$login);
            $xlogin = str_replace("/","",$xlogin);
            $xlogin = str_replace("-","",$xlogin);
            $xlogin = strtolower ($xlogin);

            $xsenha = strtolower($senha);

            #------------- Pesquisa posto pelo Login ---------------#
            $sql = "SELECT  tbl_posto_fabrica.posto_fabrica as posto_fabrica ,
                            tbl_posto_fabrica.posto,
                            tbl_posto_fabrica.fabrica,
                            tbl_posto_fabrica.credenciamento,
                            tbl_posto_fabrica.login_provisorio
                    FROM   tbl_posto
                    JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    JOIN   tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica AND tbl_fabrica.ativo_fabrica is true
					WHERE  (lower (tbl_posto_fabrica.codigo_posto) = lower ('$xlogin') or tbl_posto_fabrica.codigo_posto = '$login')
                    AND    tbl_posto_fabrica.senha = '$senha'";
            $res = pg_query ($con,$sql);

            #------- TULIO 04/05 - Não usar mais validaçãoo de email, até fazer uma tela que preste

            if (pg_num_rows ($res) == 1) {
                if (pg_fetch_result($res,0,credenciamento) == 'DESCREDENCIADO') {
                    $msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
                } elseif (pg_fetch_result($res,0,login_provisorio) == 't' AND 1==2 ) {
                    $msg = '<!--OFFLINE-I-->Para acessar é necessário realizar a confirmação no email.<!--OFFLINE-F-->';
                }else{
                    if($token_cookie == null){
                        $token_cookie = gera_token(pg_fetch_result($res,0,fabrica),"",pg_fetch_result($res,0,posto));
                        setcookie("sess",$token_cookie,0,"","",false,true);
                    }
                    $cookie_login = get_cookie_login($token_cookie);

                    add_cookie($cookie_login,"cook_posto_fabrica",pg_fetch_result($res,0,posto_fabrica));
                    add_cookie($cookie_login,"cook_posto",pg_fetch_result($res,0,posto));
                    add_cookie($cookie_login,"cook_fabrica",pg_fetch_result($res,0,fabrica));
                    set_cookie_login($token_cookie,$cookie_login);

                    if (strlen($http_referer)){
                        add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                        set_cookie_login($token_cookie,$cookie_login);
                    }
                    header ("Location: login.php?$paramLoginAcacia");
                    exit;
                }
            }

            #------------- Pesquisa posto pelo CNPJ ---------------#
            $sql = "SELECT  tbl_posto_fabrica.posto_fabrica as posto_fabrica,
                            tbl_posto_fabrica.posto,
                            tbl_posto_fabrica.fabrica ,
                            tbl_posto_fabrica.credenciamento
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                                            AND tbl_posto_fabrica.fabrica = 11
                    WHERE tbl_posto.cnpj                  = '$xlogin'
                    AND   tbl_posto_fabrica.senha = '$senha'";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) == 1) {
                if (pg_fetch_result($res,0,credenciamento) == 'DESCREDENCIADO') {
                    $msg = '<!--OFFLINE-I-->Posto descredenciado !<!--OFFLINE-F-->';
                }else{
                    if ( pg_fetch_result($res,0,posto)<>6359 and pg_fetch_result($res,0,fabrica)<>11 ) {
                    
                        add_cookie($cookie_login,"cook_posto_fabrica",pg_fetch_result($res,0,posto_fabrica));
                        add_cookie($cookie_login,"cook_posto",pg_fetch_result($res,0,posto));
                        add_cookie($cookie_login,"cook_fabrica",pg_fetch_result($res,0,fabrica));
                        set_cookie_login($token_cookie,$cookie_login);


                        if (strlen($http_referer)){
                            add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                            set_cookie_login($token_cookie,$cookie_login);
                        }
                        header ("Location: login.php?$paramLoginAcacia");
                        exit;
                    }else{
                        $sql = "SELECT codigo_posto
                                FROM   tbl_posto_fabrica
                                WHERE  posto   =". pg_fetch_result($res,0,posto)."
                                AND    fabrica =". pg_fetch_result($res,0,fabrica);
                        $res = pg_query ($con,$sql);
                        $novo_login = pg_fetch_result($res,0,0);
                        $msg = '<!--OFFLINE-I--> Seu login mudou para <font size=3px><B>'.$novo_login.'</B></font>, utilize este novo login para acessar o sistema. <!--OFFLINE-F-->';
                    }
                }
            }


            #------------------- Pesquisa acesso ADMIN ------------------
            $sql = "SELECT  tbl_admin.admin       ,
                        tbl_admin.fabrica     ,
                        tbl_admin.login       ,
                        tbl_admin.senha       ,
                        tbl_admin.privilegios ,
                        tbl_admin.pais
                        FROM tbl_admin
                        JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica AND tbl_fabrica.ativo_fabrica is true
                    WHERE  lower (tbl_admin.login) = lower ('$xlogin')
                    AND    lower (tbl_admin.senha) = lower ('$senha')
                    AND    ativo IS TRUE";
            $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) == 1) {
                if (strtolower('$xlogin') == "luis") {
                    if (pg_fetch_result($res,0,fabrica) == 6) {
                        if (
                            $_SERVER['REMOTE_ADDR'] <> '201.0.9.216'     AND
                            $_SERVER['REMOTE_ADDR'] <> '200.247.64.130'  AND
                            $_SERVER['REMOTE_ADDR'] <> '200.204.201.218' AND
                            $_SERVER['REMOTE_ADDR'] <> '200.205.138.115'
                        ) {

                        $ip = $_SERVER['REMOTE_ADDR'];
                        echo "<h1>IP Invalido para ADMIN: $ip</h1>";
                        exit;
                        }
                    }
                }

                $pais       = pg_fetch_result($res,0,'pais') ;
                $admin      = pg_fetch_result($res,0,'admin');
                $ip    = $_SERVER['REMOTE_ADDR'] ;
                $sql2 = "UPDATE tbl_admin SET
                             ultimo_ip = '$ip' ,
                             ultimo_acesso = CURRENT_TIMESTAMP
                        WHERE admin = $admin";

                $res2 = pg_query($con,$sql2);

                if ($pais<>'BR') setcookie ("cook_admin_es",pg_fetch_result($res,0,admin));
                else             setcookie ("cook_admin",pg_fetch_result($res,0,admin))   ;

                if($token_cookie == null){
                    $token_cookie = gera_token(pg_fetch_result($res,0,fabrica),"",pg_fetch_result($res,0,posto));
                    setcookie("sess",$token_cookie,0,"","",false,true);
                }
                $cookie_login = get_cookie_login($token_cookie);

                add_cookie($cookie_login,"cook_fabrica",pg_fetch_result($res,0,fabrica));
                set_cookie_login($token_cookie,$cookie_login);

                if (strlen($http_referer)){
                    add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                    set_cookie_login($token_cookie,$cookie_login);
                }

                $privilegios = pg_fetch_result($res,0,privilegios);
                $acesso = explode(",",$privilegios);

                if (strlen($http_referer)){
                    add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                    set_cookie_login($token_cookie,$cookie_login);
                }
                if($hd=='OK'){
                    header("Location: helpdesk/chamado_detalhe.php?hd_chamado=$hd_chamado");
                    exit;
                }

    //--=== ADMINS AMRICA LATINA ========================RAPHAEL===============--\\
                if($pais<>'BR'){

                    if (strlen($http_referer)){
                        add_cookie($cookie_login,'cook_retorno_url', $http_referer);
                        set_cookie_login($token_cookie,$cookie_login);
                    }
                    header("Location: admin_es/menu_gerencia.php");
                    exit;
                }
    //--========================================================================--\\

                for($i=0; $i < count($acesso); $i++){
                    if(strlen($acesso[$i]) > 0){
                        if ($acesso[$i] == "gerencia"){
                            $pagina = "admin/menu_gerencia.php";
                        }elseif ($acesso[$i] == "call_center"){
                            $pagina = "admin/menu_callcenter.php";
                        }elseif ($acesso[$i] == "cadastros"){
                            $pagina = "admin/menu_cadastro.php";
                        }elseif ($acesso[$i] == "info_tecnica"){
                            $pagina = "admin/menu_tecnica.php";
                        }elseif ($acesso[$i] == "financeiro"){
                            $pagina = "admin/menu_financeiro.php";
                        }elseif ($acesso[$i] == "auditoria"){
                            $pagina = "admin/menu_auditoria.php";
                        }elseif ($acesso[$i] == "*"){
                            $pagina = "admin/menu_cadastro.php";
                        }else if($fabrica == 189 AND $acesso[$i] == "*"){
                            $pagina = "admin/acompanhamento_atendimentos.php";
                        }

                        if ($mostra_comunicado_tc) setcookie('cook_comunicado_telecontrol', 'naoleu');
                        if ($ajax=="sim"){
                            echo "ok|$pagina";
                            exit;
                        }else{
                            header ("Location: $pagina");
                            exit;
                        }
                    }
                }
            }

            if (strlen ($msg) == 0) {
                $msg = "<!--OFFLINE-I-->Login ou senha inv&aacute;lidos !!!<!--OFFLINE-F-->";
            }

        }
    }
}

if ($_GET['s'] == 1){
    echo "<script> alert('Seus dados de acesso foram enviados para seu e-Mail');</script>";
}

if (!isset($_SERVER['HTTP_REFERER'])                    or
    $http_server_name == 'testes.telecontrol.com.br'    or
    $http_server_name == 'telecontrol.no-ip.org'        or
    $http_server_name == 'local.telecontrol.com.br'     or
    // $http_server_name == 'urano.telecontrol.com.br'  or
    $http_server_name == 'devel.telecontrol.com.br'     or
    $http_server_name == 'novodevel.telecontrol.com.br' or
    $http_server_name == '192.168.0.199') {
    //include 'frm_index.html'; //Arquivo que contém o mesmo formulário do index.html, mas com os textos traduzidos

	if (strpos($_SERVER['REQUEST_URI'], 'externos')) {
		header('Location: ./login_posvenda_new.php');
	} else {
		header('Location: externos/login_posvenda_new.php');
	}
    exit;

} else {
    header('Content-Type: text/html; charset=utf-8');
    setcookie('errLogin', $msg_erro);
    if ($_POST['btnErro'] == 'login') {
        header("Location: http://posvenda.telecontrol.com.br/assist/index.php?errLogin=$msg_erro");
    } else {
        header ("Location: ". $http_referer . "?errLogin=$msg_erro");
    }
    exit;
}
