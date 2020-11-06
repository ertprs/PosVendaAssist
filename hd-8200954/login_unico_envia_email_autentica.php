<?php
header("Content-type: application/json");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once 'class/communicator.class.php';
include 'login_unico_autentica_usuario.php';

if (!empty($_POST['lu'])) {
    $lu = (int) $_POST['lu'];
    $sql = "SELECT email FROM tbl_login_unico WHERE login_unico = $lu";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) == 0) {
        die('{"msg": "ERRO: Login �nico n�o encontrado"}');
    }

    $email = pg_fetch_result($qry, 0, 'email');

    if (empty($email)) {
        die('{"msg": "ERRO: Email n�o encontrado"}');
    }

    if (!defined('APP_URL')) {
        define ('APP_URL',  '//' . $_SERVER["HTTP_HOST"] .
            preg_replace(
                '#/(admin|admin_es|admin_callcenter|helpdesk)#', '',
                dirname($_SERVER['SCRIPT_NAME'])
            )
        );

    }

    $mailTc         = new TcComm("smtp@posvenda");
    $chave1         = md5($lu);

    $link_validacao = 'https:' . APP_URL . '/externos/login_unico_new.php' .  "?id=$lu&key1=$chave1";
    $email_origem   = "helpdesk@telecontrol.com.br";
    $email_destino  = $email;
    $assunto        = "Assist - Login �nico";
    $corpo         .= "<P align=left><STRONG>Este e-mail � gerado automaticamente.<br> **** N�O RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
            <P align=justify>Parab�ns pela sua nova conta de login �nico. Para <FONT
            color=#006600><STRONG>validar</STRONG></FONT> seu email,utilize o link abaixo:
            <br><a href='$link_validacao'><u><b>Clique aqui para validar seu email</b></u></a>.</P>
            <br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>$link_validacao<br>
            <P align=justify>Suporte Telecontrol Networking.<BR>helpdesk@telecontrol.com.br
            </P>";

    $assunto = stripslashes(utf8_encode($assunto));
    $corpo = utf8_encode($corpo);

    $mailTc->setEmailSubject($assunto);
    $mailTc->addToEmailBody($corpo);
    $mailTc->setEmailFrom($email_origem);
    $mailTc->addEmailDest($email_destino);
    $resultado = $mailTc->sendMail();

    if ($resultado) {
        die('{"msg": "Email enviado com sucesso."}');
    }
}

echo '[]';
