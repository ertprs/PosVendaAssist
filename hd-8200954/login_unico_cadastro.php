<?php
// echo "IN<br>";
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
// include 'ajax_cabecalho.php';
include_once 'class/communicator.class.php';
include 'token_cookie.php';
$mailTc     = new TcComm("smtp@posvenda");//classe

$token_cookie = $_COOKIE['sess'];
$cookie_login = get_cookie_login($token_cookie);
// print_r($cookie_login);

if(!empty($_GET['lu'])){
    $posto = $_GET['posto'];
    $fabrica = $_GET['fabrica'];
    $sql = "SELECT oid as posto_fabrica,
            posto,
            fabrica
        FROM tbl_posto_fabrica
        WHERE md5(posto::text) = '$posto'
        AND   md5(fabrica::text) = '$fabrica'";
//      exit(nl2br($sql));
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) == 1) {


        $posto_fabrica = pg_fetch_result($res,0,'posto_fabrica');
        $posto = pg_fetch_result($res,0,'posto');
        $fabrica = pg_fetch_result($res,0,'fabrica');

        add_cookie($cookie_login,'cook_posto_fabrica', $posto_fabrica );
        add_cookie($cookie_login,'cook_posto'        , $posto);
        add_cookie($cookie_login,'cook_fabrica'      , $fabrica);
        add_cookie($cookie_login,'cook_login_unico'  , 'temporario');

        set_cookie_login($token_cookie,$cookie_login);
        // setcookie ('cook_posto_fabrica', $posto_fabrica );
        // setcookie ('cook_posto'        , $posto);
        // setcookie ('cook_fabrica'      , $fabrica);
        // setcookie ('cook_login_unico'  , 'temporario');
    }
}

if(strlen($cookie_login["cook_login_unico"])==0 ){
    header($_SERVER['SERVER_NAME']);
//  echo "aqui";
    exit;
}
// echo "OUT!";
if(strlen($_GET["t"])>0) $t = trim($_GET["t"]);
if(strlen($_POST["t"])>0)$t = trim($_POST["t"]);

if ($t!='lu') { // Se não fizer isso aqui, fica sem informações no cabeçalho em tela
    include 'autentica_usuario.php'; // Se vem "de fora"
} else {
    include 'login_unico_autentica_usuario.php'; // Desde a tela do Login Único.
}

if (!defined('APP_URL')) {
    define ('APP_URL',  '//' . $_SERVER["HTTP_HOST"] .
        preg_replace(
            '#/(admin|admin_es|admin_callcenter|helpdesk)#', '',
            dirname($_SERVER['SCRIPT_NAME'])
        )
    );
}

$elgin_posto_interno = 29482;
$qbex_posto_interno  = 431918;
$jfa_posto_interno   = 627694;
//$yanmar_posto_interno = 56209;

//  Limpa a string para evitar SQL injection
if (!function_exists('anti_injection')) {
    function anti_injection($string) {
        $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
        return strtr(strip_tags(trim($string)), $a_limpa);
    }
}

if (!function_exists('is_email')) {
    function is_email($email=""){   // False se não bate...
        return (preg_match("/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+)*@[0-9a-zA-Z]+[0-9,a-z,A-Z,.,-]*(.){1}[a-zA-Z]{2,4})+$/", $email));
    }
}

function verifica_login_master() {
    global $con,$login_unico;

    $sql = "SELECT master 
            FROM tbl_login_unico
            WHERE login_unico = {$login_unico}";
    $res = pg_query($con, $sql);

    $login_master = pg_fetch_result($res, 0, 'master');

    return $login_master;
}

if($_POST['btn_acao']=='Gravar') {
    
    $lu_id          = anti_injection($_POST['login_unico']);
    $nome           = anti_injection($_POST['nome']);
    $email          = anti_injection($_POST['email']);
    $senha          = anti_injection($_POST['senha']);
    $confirma_senha = anti_injection($_POST['confirma_senha']);
    $ativo          = anti_injection($_POST['ativo']);
    $abre_os        = anti_injection($_POST['abre_os']);
    $item_os        = anti_injection($_POST['item_os']);
    $fecha_os       = anti_injection($_POST['fecha_os']);
    $compra_peca    = anti_injection($_POST['compra_peca']);
    $extrato        = anti_injection($_POST['extrato']);
    $master         = anti_injection($_POST['master']);
    $troca_senha    = anti_injection($_POST['troca_senha']);
    $tecnico_posto  = anti_injection($_POST['tecnico_posto']);
    $distrib_total  = anti_injection($_POST['distrib_total']);

    $msg_erro = array();
    
    if(strlen($troca_senha)==0) $troca_senha = 'f';

    if(strlen($nome)==0 ) $msg_erro[0] = "Preencha o nome";
    if(!is_email($email)) $msg_erro[1] = "Digite um e-mail válido";
    if(strlen($email)==0) $msg_erro[1] = "Preencha o email";

    if (!$lu_id and (!$senha or $troca_senha == 'f'))
        $msg_erro[2] = traduz('digite.uma.senha', $con);

    if($troca_senha == 't')
        if(strlen($senha)==0) $msg_erro[2] = traduz('digite.uma.senha', $con);

    if (strlen($senha) > 0 AND $troca_senha == 't') {
        if (strlen(trim($senha)) >= 6) {
            $senha          = $senha;
            $confirma_senha = $confirma_senha;

            if($senha<>$confirma_senha) $msg_erro[2] = traduz('as.senhas.nao.sao.iguais.redigite', $con);

            //- verifica qtd de letras e numeros da senha digitada -//
            $count_letras  = 0;
            $count_numeros = 0;
            $letrasM = strtoupper('abcdefghijklmnopqrstuvwxyz');
            $letras  = 'abcdefghijklmnopqrstuvwxyz'.$letrasM;
            $numeros = '0123456789';

            for ($i = 0; $i <= strlen($senha); $i++) {
                if ( strpos($letras, substr($senha, $i, 1)) !== false) $count_letras++;
                if ( strpos ($numeros, substr($senha, $i, 1)) !== false) $count_numeros++;
            }

            if ($count_letras < 2)  {
                $msg_erro[2] = traduz('senha.invalida.a.senha.deve.ter.pelo.menos.2.letras', $con);
            }
            if ($count_numeros < 2){
                $msg_erro[2] = traduz('senha.invalida.a.senha.deve.ter.pelo.menos.2.numeros', $con);
            }
        }else{
            $msg_erro[2] = traduz('a.senha.deve.conter.um.minimo.de.6.caracteres', $con);
        }
        $xsenha = "'".$senha."'";
    }

    if(count($msg_erro)==0){
        $aux_ativo         = ($ativo         == 't') ? 'TRUE' : 'FALSE';
        $aux_abre_os       = ($abre_os       == 't') ? 'TRUE' : 'FALSE';
        $aux_item_os       = ($item_os       == 't') ? 'TRUE' : 'FALSE';
        $aux_fecha_os      = ($fecha_os      == 't') ? 'TRUE' : 'FALSE';
        $aux_compra_peca   = ($compra_peca   == 't') ? 'TRUE' : 'FALSE';
        $aux_extrato       = ($extrato       == 't') ? 'TRUE' : 'FALSE';
        $aux_master        = ($master        == 't') ? 'TRUE' : 'FALSE';
        $aux_tecnico_posto = ($tecnico_posto == 't') ? 'TRUE' : 'FALSE';
        $aux_distrib_total = ($distrib_total == 't') ? 'TRUE' : 'FALSE';

        if(strlen($distrib_total) == 0) $aux_distrib_total = "FALSE";

        $sql = "SELECT login_unico,nome FROM tbl_login_unico WHERE posto = $login_posto AND master IS TRUE AND posto <> $elgin_posto_interno";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res)>0){
            if($lu_id <> pg_fetch_result($res, 0, 0)) $aux_master = "FALSE";
            else{
                $sql = "SELECT email FROM tbl_posto WHERE posto = $login_posto";
                $res = pg_query($con, $sql);
                $posto_email = pg_fetch_result($res, 0, 'email');
                //if($email<>$posto_email) $msg_erro[3]= "O usuário MASTER só poderá ser o mesmo usuário do email do posto";
            }
        }

            $res = pg_query($con,"BEGIN TRANSACTION");
            if (!count($msg_erro)) {
//  HD 283313
                $sql_e = "SELECT login_unico FROM tbl_login_unico WHERE email = '$email'/* AND posto = $login_posto*/";
                $res_e = pg_query($con, $sql_e);
                if (is_resource($res_e)) {
                    if (pg_num_rows($res_e) and ($lu_id == '' or $lu_id != pg_fetch_result($res_e, 0, 0))) {
                    // O e-mail pode ser o mesmo se o usuário é o mesmo!! (UPDATE...)
                        $msg_erro[1]= "Já existe um usuário com este e-mail!";
                        $msg_erro[4]= "<br>O e-mail será usado como 'Usuário' no login, ".
                                     "portanto não pode existir mais de um usuário com o mesmo e-mail.";
                    }
                } else {
                    $msg_erro[5]= 'Erro de acesso. Por favor, tente novamente em uns segundos.';
                }

                if($login_posto == $elgin_posto_interno || $login_posto == $qbex_posto_interno || $login_posto == $jfa_posto_interno){

                

                    // Posto interno Elgin
                    if($login_posto == $qbex_posto_interno){
                        $fabrica = 162;
                        $posto = $qbex_posto_interno;
                    }elseif($login_posto == $elgin_posto_interno){
                        $fabrica = 156;
                        $posto = $elgin_posto_interno;
                    }elseif ($login_posto == $jfa_posto_interno) {
                        $fabrica = 173;
                        $posto   = $jfa_posto_interno;
                    }

                    $sql = "SELECT tecnico FROM tbl_tecnico WHERE fabrica = {$fabrica} AND posto = {$posto} AND nome = '{$nome}'";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){

                        $tecnico = pg_fetch_result($res, 0, 'tecnico');

                        $uTec = "UPDATE tbl_tecnico SET ativo = ".(($aux_ativo == 'FALSE') ? 'FALSE' : $aux_tecnico_posto)." WHERE tecnico = $tecnico";
                        $qTec = pg_query($con, $uTec);

                    }else{

                        if ($aux_tecnico_posto == 'TRUE') {
                            $sql = "INSERT INTO tbl_tecnico(posto,fabrica,nome) VALUES ({$posto},{$fabrica},'{$nome}') RETURNING tecnico";
                            $res = pg_query($con,$sql);
                            $tecnico = pg_fetch_result($res, 0, 'tecnico');
                        }

                    }
                }                

                $aux_tecnico = (empty($tecnico)) ? "null" : $tecnico;

                if (!empty($lu_id)) {
                    $sql = "SELECT master 
                            FROM tbl_login_unico
                            WHERE login_unico = {$lu_id}";
                    $res = pg_query($con, $sql);
                    $admin_master = pg_fetch_result($res, 0, "master");

                    //caso o admin tenha sido desmarcado do master
                    if ($admin_master == "t" && $master != "t") {
                        $sql = "SELECT login_unico
                                FROM tbl_login_unico
                                WHERE posto = $login_posto
                                AND master IS TRUE
                                AND login_unico != {$lu_id}";
                        $res = pg_query($con, $sql);

                        //verifica se existe outro admin diferente do logado marcado como master
                        //pois não pode ficar sem um admin master
                        if (pg_num_rows($res) == 0) {  
                            $msg_erro[6] = "O posto não pode ficar sem um usuário Master.";
                        }
                    }
                }

				if (!empty($login_posto)) {
					$sql = "SELECT * FROM tbl_login_unico WHERE posto = $login_posto LIMIT 1";
					$res_lu = pg_query($con, $sql);

					if (pg_num_rows($res_lu) == 0) {
						$aux_master = 'TRUE';
					}
                }
   

                if (!count($msg_erro)) {
                    if(strlen($lu_id)==0) {
                        $sql = "INSERT INTO tbl_login_unico (
                                nome,
                                email,
                                senha,
                                ativo,
                                abre_os,
                                item_os,
                                fecha_os,
                                compra_peca,
                                extrato,
                                master,
                                tecnico_posto,
                                posto  ,
                                distrib_total,
                                tecnico
                            )VALUES(
                                '$nome',
                                '$email',
                                '$senha',
                                $aux_ativo,
                                $aux_abre_os,
                                $aux_item_os,
                                $aux_fecha_os,
                                $aux_compra_peca,
                                $aux_extrato,
                                $aux_master,
                                $aux_tecnico_posto,
                                $login_posto,
                                $aux_distrib_total,
                                $aux_tecnico
							)
                                RETURNING login_unico
";
                        $res = pg_query($con,$sql);
                        
                        if (!is_resource($res)) {
                            $msg_erro[5] = pg_last_error($con);
                        } else {
                            $login_unico = pg_fetch_result($res, login_unico);
                            $ip_solicitante = $_SERVER['HTTP_X_FORWARDED_FOR'] ? : $_SERVER['REMOTE_ADDR'];

                            // GERA A SENHA
                            $data = new DateTime();
                            $data_solicitacao = $data->format('Y-m-d H:i:s.u');
                            $insert_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (login_unico, token, data_solicitacao,tipo_alteracao, ip) VALUES ($login_unico, '', '$data_solicitacao', 'login_unico_new', '$ip_solicitante')";
                            pg_query($con, $insert_alteracao_senha);

                            $res = pg_query($con,"SELECT CURRVAL ('seq_login_unico')");
                            $lu_id  = pg_result ($res,0,0);
							$sql = "with conta as (select count(1) as conta, posto  from tbl_login_unico where posto = $login_posto group by posto ) update tbl_login_unico set master = true from conta where conta.posto = tbl_login_unico.posto and conta = 1 and login_unico = $lu_id ";
							$res = pg_query($con,$sql);
							$link_validacao = 'https:' . APP_URL . '/externos/login_unico_new.php';
                            $chave1=md5($lu_id);
                            $email_origem  = "helpdesk@telecontrol.com.br";
                            $email_destino = $email;
                            $assunto       = "Assist - Login Único";
                            $corpo.="<P align=left><STRONG>Este e-mail é gerado automaticamente.<br> **** NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

                                    <P align=justify>Parabéns pela sua nova conta de login único. Para <FONT
                                    color=#006600><STRONG>validar</STRONG></FONT> seu email,utilize o link abaixo: 
                                    <br><a href='$link_validacao?id=$lu_id&key1=$chave1'><u><b>Clique aqui para validar seu email</b></u></a>.</P>
                                    <br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>$link_validacao?id=$lu_id&key1=$chave1<br>
                                    <P align=justify>Suporte Telecontrol Networking.<BR>helpdesk@telecontrol.com.br
                                    </P>";

                            $body_top = "--Message-Boundary\n";
                            $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                            $body_top .= "Content-transfer-encoding: 7BIT\n";
                            $body_top .= "Content-description: Mail message body\n\n";

                            $assunto    = stripslashes(utf8_encode($assunto));
                            $corpo      = utf8_encode($corpo);

                            $mailTc->setEmailSubject($assunto);
                            $mailTc->addToEmailBody($corpo);
                            $mailTc->setEmailFrom($email_origem);
                            $mailTc->addEmailDest($email_destino);
                            $resultado = $mailTc->sendMail();

                            if($resultado){
                                $msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
                            }

                        }
                    } else {
                        $sql = "UPDATE tbl_login_unico SET
                                        nome        = '$nome',
                                        email       = '$email',
                                        ativo       = $aux_ativo,
                                        abre_os     = $aux_abre_os,
                                        item_os     = $aux_item_os,
                                        fecha_os    = $aux_fecha_os,
                                        compra_peca = $aux_compra_peca,
                                        extrato     = $aux_extrato,
                                        tecnico_posto = $aux_tecnico_posto,
                                        master      = $aux_master,
                                        distrib_total = $aux_distrib_total,
                                        tecnico     = $aux_tecnico
                                WHERE posto = $login_posto
                                AND login_unico = $lu_id ";
                        $res = pg_query($con,$sql);
                        if (!is_resource($res)) {
                            $msg_erro[] = pg_last_error($con);
                        } else {
                            $ip_solicitante = $_SERVER['HTTP_X_FORWARDED_FOR'] ? : $_SERVER['REMOTE_ADDR'];

                            // GERA A SENHA
                            $data = new DateTime();
                            $data_solicitacao = $data->format('Y-m-d H:i:s.u');
                            $insert_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (login_unico, token, data_solicitacao,tipo_alteracao, ip) VALUES ($lu_id, '', '$data_solicitacao', 'login_unico_new', '$ip_solicitante')";
                            pg_query($con, $insert_alteracao_senha);
                            if($troca_senha =='t'){
                                    $sql = "UPDATE tbl_login_unico SET
                                        senha     = '$senha'
                                    WHERE login_unico = $lu_id AND posto = $login_posto";
                                $res = pg_query($con,$sql);
                                if (!is_resource($res)) $msg_erro[] = pg_last_error($con);
                            }
                        }
                    }

                    $sql_tecnico = "SELECT tecnico FROM tbl_tecnico WHERE posto = {$login_posto} AND email = '{$email}' AND fabrica IS NULL";
                    $res_tecnico = pg_query($con,$sql_tecnico);
                    if (pg_num_rows($res_tecnico) > 0){
                        $id_tecnico = pg_fetch_result($res_tecnico, 0, 'tecnico');
                        $tecnico = pg_fetch_result($res_tecnico, 0, 'tecnico');

                        $uTec = "UPDATE tbl_tecnico SET ativo = $aux_tecnico_posto WHERE tecnico = $id_tecnico";
                        $qTec = pg_query($con, $uTec);
                    }else{
                        if ($aux_tecnico_posto == "TRUE" AND !empty($nome) AND !empty($email)){
                            $sql_in = "INSERT INTO tbl_tecnico (
                                            posto, 
                                            nome, 
                                            email, 
                                            ativo,
                                            codigo_externo
                                        )VALUES(
                                            $login_posto,
                                            '$nome',
                                            '$email',
                                            true,
                                            $lu_id
                                        ) returning tecnico";
                            $res_in = pg_query($con, $sql_in);
							$tecnico = pg_fetch_result($res_in, 0, 'tecnico');
                        }
                    }

                    if(in_array($fabrica, array(156, 162,175)) and !empty($tecnico)) {

                        $sql = "UPDATE tbl_login_unico SET tecnico = {$tecnico}
                                WHERE login_unico = $lu_id AND posto = $posto";
                        $res = pg_query($con,$sql);
                        if (!is_resource($res)) $msg_erro[] = pg_last_error($con);

                    }
                }
            }
        }
    if(!count(array_filter($msg_erro))) {
        $res = pg_query($con,"COMMIT TRANSACTION");
        header("Location: $PHP_SELF?ok=1&t=$t");
        exit;
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
    }
}

$layout_menu = "cadastro";
$title = "Login Único";
$aba = 1;
include 'cabecalho.php';
?>
<style>
.Titulo {
    font-family: Verdana;
    font-size: 12px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #485989;
}
.Label {
    font-family: Arial;
    font-size: 12px;
    color: #000000;
}
.Conteudo {
    font-family: Arial;
    font-size: 8pt;
    font-weight: normal;
}
/*  Avisos  */
.erro, .msg {
    border-radius: 8px;
    -moz-border-radius: 8px;
    position: relative;
    display: inline-block;
    _zoom:1;
    left: auto;
    margin: 0 auto 1em auto;
    max-width: 680px;
    font-size: 11px;
    background-color: #ffbfbf;
}
.msg {
    border: 3px solid #138;
    color: #006;
    background-color: #dfccff;
}

span.erro {
    width: auto;
    padding:5px 0.5em;
    border-width: 1px;
    font-size: 10px;
/*  opacity: 0.8;   */
}

.Caixa{
    border: 1px solid #69c;
    font: 8pt Arial;
    background-color: white;
}

.D{ /* CLASSE DE COMENTÁRIO */
    font:  10px normal Arial, Sans Serif;
    color: #777;
}
acronym {
    /*background-color: #FFCC66;
    border: #FF0000 1px solid;*/
    color:#FF9900;
    cursor: help;

}
form table td {text-align: left;}
</style>

<script src="https://code.jquery.com/jquery-3.1.1.min.js"
        integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="
        crossorigin="anonymous"></script>

<script>
$(function(){
    $(".nome_login").click(function(){
        var login_master = $(this).data('master');
        var url          = $(this).data('url');

        if (login_master == "t") {
            window.location = url;
        } else {
            alert("Você não tem permissão para alterar o cadastro de funcionários");
        }

    });
});

function habilita(){
    troca = document.getElementById('troca_senha');
    senha = document.getElementById('senha');
    confi = document.getElementById('confirma_senha');

    if(troca.checked == true){
        senha.disabled=0;
        senha.value='';
        confi.disabled=0;
        confi.value='';
    }else{
        senha.disabled=1;
        senha.value='******';
        confi.disabled=1;
        confi.value='******';
    }
}

function checa_email(email) {
    if (email.indexOf("@uol.com.br") > -1||email.indexOf("@bol.com.br") > -1)
    {
    var url     = "./aviso_email.html";
    var titulo  = "_blank";
    var params  = "height=500,width=350,toolbar=no,location=no,menubar=no,scrollbars=no";
    window.open(url,titulo,params);
    }
}


function enviaEmailAutentica(lu)
{
    if (confirm('Enviar email para autenticar usuário?')) {
        $.ajax({
            url: "login_unico_envia_email_autentica.php",
            method: "POST",
            data: { lu: lu }
        }).done(function(response) {
            if (response.msg) {
                alert(response.msg);
            }
        });
    }
}
</script>
<?

if(strlen($_GET["id"])>0){
    $lu_id = $_GET["id"];
    $sql = "SELECT * FROM tbl_login_unico WHERE posto =  $login_posto AND login_unico = $lu_id";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $lu_id         = pg_fetch_result($res, 0, 'login_unico');
        $nome          = pg_fetch_result($res, 0, 'nome');
        $email         = pg_fetch_result($res, 0, 'email');
        $ativo         = pg_fetch_result($res, 0, 'ativo');
        $abre_os       = pg_fetch_result($res, 0, 'abre_os');
        $item_os       = pg_fetch_result($res, 0, 'item_os');
        $fecha_os      = pg_fetch_result($res, 0, 'fecha_os');
        $compra_peca   = pg_fetch_result($res, 0, 'compra_peca');
        $extrato       = pg_fetch_result($res, 0, 'extrato');
        $master        = pg_fetch_result($res, 0, 'master');
        $tecnico_posto = pg_fetch_result($res, 0, 'tecnico_posto');
        $distrib_total = pg_fetch_result($res, 0, 'distrib_total');
    }
}
if(isset($msg_erro[3]) or isset($msg_erro[4]) or isset($msg_erro[5])) {
    echo "<p class='erro' style='max-width:700px;'>".$msg_erro[3] . $msg_erro[4] . $msg_erro[5].'</p>';
    echo '<p>&nbsp;</p>';
}
if(strlen($_GET["ok"])>0) echo "<p class='msg'>USUÁRIO GRAVADO COM SUCESSO</p>";

if(verifica_login_master() == "t" OR $_GET['lu'] == "temp"){
?>
	<FORM name='frm_os' METHOD='POST' ACTION='<?=$PHP_SELF?>'>
	<input type='hidden' name='login_unico' id='login_unico' value='<?=$lu_id?>'>
	<input type='hidden' name='t' id='t' value='<?=$t?>'>
	    <table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='700' border='0'>
		<tr>
		    <td class='Label' align='left' colspan='2'><font size='3'><b>Cadastro de Usuário - Login Único</font></b><br>&nbsp;</td>
		</tr>
		<tr>
		    <td class='Label' align='left' valign='top'><?=traduz('nome', $con)?>: *</td>
		    <td>
			<input name='nome' id='nome' class='Caixa' type='text' size='50' value='<?=$nome?>'>
			<?if(strlen($msg_erro[0])>0) echo "<span class='erro'>$msg_erro[0]</span>";?>
		    </td>
		</tr>
		<tr>
		    <td class='Label' align='left' valign='top'><?=traduz('e-mail', $con)?>: <acronym title="O e-mail deve ser ÚNICO para cada usuário">*</acronym></td>
		    <td>
			<input name ="email" id='email' class ="Caixa" type = "text" size = "50"
			      value ="<?=$email ?>" onChange='checa_email(this.value);'>
			<?if(strlen($msg_erro[1])>0) echo "<span class='erro'>{$msg_erro[1]}</span>";?>
			<div class='D'>por exemplo, <U>meunome@exemplo.com</U>. Com isso você pode acessar o sistema.</div>
		    </td>
		</tr>
		<?
		if(strlen($lu_id)>0){
		?>
		<tr>
		    <td class='Label' align='left' valign='top' colspan='2'><hr width='90%'>
			<input name='troca_senha' id='troca_senha' type='checkbox' value='t' onclick="habilita();">
			<label for="troca_senha">Alterar Senha deste usuário</label>
			<div class='D'>Com esta opção desmarcada você irá manter a senha atual deste usuário.</div>
		    </td>
		</tr>
		<?}else{?>
		<tr>
		    <td class='Label' align='left' valign='top' colspan='2'><hr width='90%'>
			<input name='troca_senha' id='troca_senha' type='hidden' value='t'>
			<label for="troca_senha">Cadastrar Senha para este usuário</label>
		    </td>
		</tr>
		<?}?>
		<tr>
		    <td class='Label' align='left' valign='top'><?=traduz('digite.uma.senha', $con)?>:*</td>
		    <td>
			<input name ="senha" id='senha' class="Caixa" type="password" size="20" value="<?=$senha?>" >
			<?if(strlen($msg_erro[2])>0) echo "<span class='erro'>$msg_erro[2]</span>";?>
			<div class='D'>Mínimo de seis caracteres e no máximo dez, sendo no minímo 2 letras (de A a Z) e 2 números (de 0 a 9)</div>
		    </td>
		</tr>
		<tr>
		    <td class='Label' align='left' nowrap  valign='top'><?=traduz('repita.nova.senha', $con)?>:*</td>
		    <td><input name="confirma_senha" id='confirma_senha' class="Caixa" type="password" size="20" value="<?=$confirma_senha?>" ></td>
		</tr>
		<?if(strlen($lu_id)>0)echo "<script language='javascript'>habilita();</script>";?>
		<tr>
		    <td class='Label' align='left' colspan='2'><br><?=traduz('selecione.as.areas.do.sistema.que.poderao.ser.acessadas', $con)?>:</td>
		</tr>
		<tr>
		    <td colspan='2' class='Conteudo' style='margin-left:20px;'>
			<?
			if ($master == 't' AND empty($msg_erro)) {
			    #$disabled = " disabled='disabled' ";
			    $disabled = 'onclick="return false;"';
			}

			// Posto interno Elgin não tem limite de masters
			$sql = "SELECT login_unico,nome FROM tbl_login_unico WHERE posto = $login_posto AND master IS TRUE AND posto <> $elgin_posto_interno";
			$res = pg_query($con, $sql);
			if(pg_num_rows($res)>0){
			    echo "Já existe um usuário Master<br>";
			    if($master=='t') echo "<input name='master' id='master' type='checkbox' value='t' CHECKED> <u>Usuário Master</u> <acronym title='Este usuário tem acesso e privilégio a qualquer informação'>[?]</acronym><br><br>";
				if (!empty($msg_erro[6])) {
				?>
				    <span class="erro"><?= $msg_erro[6] ?></span><br />
				<?php
				}
			}else{
			?>
			&nbsp;<input name='master' <?= $disabled ?>  id='master' type='checkbox' value='t' <?if($master=='t') echo "CHECKED";?>>
				<label for="master"><u>Usuário Master</u></label> <acronym title='Este usuário tem acesso e privilégio a qualquer informação'>[?]</acronym><br><br>
			<?}?>
			&nbsp;<input name='abre_os' <?= $disabled ?> id='abre_os'        type='checkbox' value='t' <?if($abre_os=='t')       echo " CHECKED";?>>
				<label for="abre_os">Digitar Ordem de Serviço</label><br>
			&nbsp;<input name='item_os' <?= $disabled ?> id='item_os'        type='checkbox' value='t' <?if($item_os=='t')       echo "CHECKED";?>>
				<label for="item_os">Incluir Item na Ordem de Serviço</label><br>
			&nbsp;<input name='fecha_os' <?= $disabled ?> id='fecha_os'       type='checkbox' value='t' <?if($fecha_os=='t')      echo "CHECKED";?>>
				<label for="fecha_os">Fechar Ordem de Serviço</label><br>
			&nbsp;<input name='compra_peca' <?= $disabled ?> id='compra_peca'    type='checkbox' value='t' <?if($compra_peca=='t')   echo "CHECKED";?>>
				<label for="compra_peca">Comprar Peça</label><br>
			&nbsp;<input name='extrato' <?= $disabled ?> id='extrato'        type='checkbox' value='t' <?if($extrato=='t')       echo "CHECKED";?>>
				<label for="extrato">Ver Extrato</label><br>
			<? if ($login_posto==4311) { ?>
			&nbsp;<input name='distrib_total' <?= $disabled ?> id='distrib_total' type='checkbox' value='t' <?if($distrib_total=='t') echo "CHECKED";?>>
				<label for="distrib_total">Distrib Total</label><br />
			<? } ?>
			&nbsp;<input name='tecnico_posto' id='tecnico_posto' type='checkbox' value='t' <?if($tecnico_posto=='t')        echo "CHECKED";?>>
				<label for="tecnico_posto">Técnico</label><br>
		    </td>
		</tr>
		<tr>
		    <td class='Label' align='left' nowrap colspan='2'><hr width='90%'><input  name='ativo' id='ativo' type='checkbox' value='t' <?if($ativo=='t' or !$lu_id) echo "CHECKED";?>><label for="ativo">Usuário ativo</label><div class='D'>Com esta opção desmarcado o usuário não terá mais acesso ao sistema</div></td>
		</tr>
		<tr>
		    <td class='Label' align='left' nowrap colspan='2'><br>* <i>Dados Obrigatórios</i></td>
		</tr>
		<tr>
		    <td colspan='2' style="text-align:center"><input name='btn_acao' value='Gravar' type='submit'>&nbsp;<input type='button' value='Limpar' onclick="javascript:window.location='<?echo $PHP_SELF;?>?t=lu';"></td>
		</tr>
	    </table>
<?
}
if($login_posto==4311) $cond = " AND ativo IS TRUE "; //HD 39852 11/9/2008

if(verifica_login_master() != "t"){
	$cond .= " AND login_unico = $login_unico ";
}

$sql = "SELECT * FROM tbl_login_unico WHERE posto = $login_posto $cond ORDER BY nome";
#echo $sql;
$res = pg_query($con, $sql);
if(pg_num_rows($res)>0){    ?>
    <br>
    <table style='border:#485989 1px solid; background-color: #e6eef7' align='center' width='750' border='0' id='tbl_pecas'>
        <thead style='font: normal bold 11px Arial, Helvetica, sans-serif;background-color:#bccbe0'>
        <tr height='20'>
            <th>Nome</th>
            <th>Email</th>
            <th>Ativo</th>
            <th>Abre OS</th>
            <th>Lança Item</th>
            <th>Fecha OS</th>
            <th>Compra Peça</th>
            <th>Acessa Extrato</th>
            <th>Técnico Posto</th>
<?  if($login_posto==4311) {
        echo "\t\t\t<th align='center' class='Conteudo'width='60'>Distrib Total</th>";
    }?>
            <th>MASTER</th>
            <th>Autenticado</th>
        </tr>
        </thead>
        <tbody>
<?  for($i = 0 ; $i < pg_num_rows($res) ; $i++){
        $lu_id         = pg_fetch_result($res, $i, 'login_unico');
        $nome          = pg_fetch_result($res, $i, 'nome');
        $email         = pg_fetch_result($res, $i, 'email');
        $ativo         = pg_fetch_result($res, $i, 'ativo');
        $abre_os       = pg_fetch_result($res, $i, 'abre_os');
        $item_os       = pg_fetch_result($res, $i, 'item_os');
        $fecha_os      = pg_fetch_result($res, $i, 'fecha_os');
        $compra_peca   = pg_fetch_result($res, $i, 'compra_peca');
        $extrato       = pg_fetch_result($res, $i, 'extrato');
        $master        = pg_fetch_result($res, $i, 'master');
        $distrib_total = pg_fetch_result($res, $i, 'distrib_total');
        $autenticado   = pg_fetch_result($res, $i, 'email_autenticado');
        $tecnico_posto = pg_fetch_result($res, $i, 'tecnico_posto');

        $ok = "<img src='imagens/icone_ok.gif'>";
        $ativo          = ($ativo       == 't') ? $ok : '&ndash;';
        $abre_os        = ($abre_os     == 't') ? $ok : '&ndash;';
        $item_os        = ($item_os     == 't') ? $ok : '&ndash;';
        $fecha_os       = ($fecha_os    == 't') ? $ok : '&ndash;';
        $compra_peca    = ($compra_peca == 't') ? $ok : '&ndash;';
        $extrato        = ($extrato     == 't') ? $ok : '&ndash;';
        $distrib_total  = ($distrib_total=='t') ? $ok : '&ndash;';
        $master         = ($master      == 't') ? $ok : '&ndash;';
        $autenticado    = (!empty($autenticado)) ? $ok : '<button style="font-size: 8px; font-weight: bold; cursor: pointer;" type="button" onClick="enviaEmailAutentica(' . $lu_id . ')">Enviar email p/ autenticar</button>';
        $tecnico_posto  = ($tecnico_posto      == 't') ? $ok : '&ndash;';

        $cor = ($cor == "#FFFFFF") ? "#EEEEEE" : "#FFFFFF";
?>
        <tr bgcolor='<?=$cor?>'>
        <td class='Conteudo' nowrap>
            <a class="nome_login" data-url="<?="$PHP_SELF?id=$lu_id&t=$t"?>" data-master="<?= verifica_login_master() ?>"><?=$nome?>
            </a>
        </td>
        <td class='Conteudo' nowrap><?=$email?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$ativo?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$abre_os?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$item_os?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$fecha_os?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$compra_peca?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$extrato?></td>
<?      if($login_posto==4311) { // HD 49866
            echo "<td class='Conteudo' nowrap align='center' width='60'>$distrib_total</td>";
        }?>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$tecnico_posto?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$master?></td>
        <td class='Conteudo' nowrap style="text-align:center" width='60'><?=$autenticado?></td>
        </tr>
<?  }
    echo "</table>";
}

include "rodape.php" ;

