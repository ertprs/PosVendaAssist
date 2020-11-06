<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "login_unico_autentica_usuario.php";

if ($_POST['btn_acao']=='Gravar') {
    include 'class/communicator.class.php';
    $mailTc = new TcComm("smtp@posvenda");

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

    $email = anti_injection($_POST['email']);

    $msg_erro = array();
    if (!is_email($email)) $msg_erro[2] = 'Digite um e-mail válido';
    if (strlen($email)==0) $msg_erro[2] = 'Preencha o email';

    if(!count($msg_erro)) {
        //  HD 283313 & 671722
        $sql =  "UPDATE tbl_login_unico
            SET email = '$email'
            WHERE posto = $login_posto
            AND login_unico = $cook_login_unico
            AND TRIM('$email') NOT IN (SELECT TRIM(email) FROM tbl_login_unico)";

        $res = @pg_query($con,$sql);

        if (!is_resource($res)) {
            $msg_erro[3] = pg_last_error($con);
        } else {
            if (pg_affected_rows($res) != 1) {
                $msg_erro[] = 'O e-mail será usado como "Usuário" no login, '.
                            'portanto não pode existir mais de um usuário com o mesmo e-mail.';
                $msg_erro[2]= 'Já existe um usuário com este e-mail';
            }

            if(!count(array_filter($msg_erro))) {
                $chave1=md5($cook_login_unico);
                $email_origem  = "helpdesk@telecontrol.com.br";
                $email_destino = $email;
                $assunto       = "Assist - Login Único";
                $corpo.="<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR
                    NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>

                    <P align=justify>Seu email de login único foi alterado, para <FONT
                    color=#006600><STRONG>validar</STRONG></FONT> seu email,utilize o link abaixo:
                    <br><a href='http://www.telecontrol.com.br/login_unico.php?id=$cook_login_unico&key1=$chave1'><u><b>Clique aqui para validar seu email</b></u></a>.</P>
                    <br>Caso esteja com problemas copie e cole o link abaixo em seu navegador:<br>http://www.telecontrol.com.br/login_unico.php?id=$cook_login_unico&key1=$chave1<br>
                    <P align=justify>Suporte Telecontrol Networking.<BR>helpdesk@telecontrol.com.br
                    </P>";

                $body_top = "--Message-Boundary\n";
                $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                $body_top .= "Content-transfer-encoding: 7BIT\n";
                $body_top .= "Content-description: Mail message body\n\n";

                if (!$mailTc->sendMail($email_destino, $assunto, $corpo, $email_origem)) {
                    $msg_erro[] = "Erro ao enviar a mensagem de validação. Por favor, tente novamente ou entre em contato com o Suporte Técnico.";
                } else {
                    $msg = "<br>Foi enviado um email para: ".$email_destino."<br>";
                }
            }
        }
    }
    if(!count(array_filter($msg_erro))) {
        header("Location: login_unico.php?t=1");
        exit;
    }
}

ob_start();
?>
<script type="text/javascript">
function checa_email(email) {
    if (email.indexOf("@uol.com.br") > -1 || email.indexOf("@bol.com.br") > -1) {
    var url     = "./aviso_email.html";
    var titulo  = "_blank";
    var params  = "height=500,width=550,toolbar=no,location=no,menubar=no,scrollbars=no";
        popup   = window.open(url,titulo,params);
    }
}
</script>
<?php
$headerHTML = ob_get_clean();

$error_alert = true;
$title = traduz('alterar.email') . " &ndash; $login_unico_nome";
include "cabecalho.php";
?>
<br>
<FORM name='frm_os' METHOD='POST' ACTION='<?=$PHP_SELF?>'>
<input type='hidden' name='login_unico' id='login_unico' value='<?=$login_unico?>'>
    <table style=' border:#485989 1px solid; background-color: #e6eef7 ' align='center' width='700' border='0'>
        <tr>
            <td class='Label' align='left' colspan='2'><font size='3'><b>Alterar E-mail - Login Único</font></b><br>&nbsp;</td>
        </tr>
        <tr>
            <td class='Label' align='left' valign='top'>E-mail:*</td>
            <td>
                <input name ="email" id='email' class ="Caixa" type = "text" size = "50" value ="<?=$email ?>" onBlur='checa_email(this.value);'>
                <?if(strlen($msg_erro[2])>0) echo "<span class='Erro'>{$msg_erro[2]}</span>";?>
                <div class='D'>por exemplo, meunome@exemplo.com. Com isso você pode acessar o sistema.</div>
            </td>
        </tr>
        <tr>
            <td colspan='2' align='center'><input name='btn_acao' value='Gravar' type='submit'> <input type='button' name='cancelar' value='Cancelar' onclick="window.location = 'login_unico.php?t=3'"></td>
        </tr>
    </table>
</form>
<?
include "rodape.php";

