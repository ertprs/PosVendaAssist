<?php
namespace Posvenda;

class Seguranca
{

    private $_fabrica;
    public $_conn;

    public function __construct($fabrica = null, $conn = null)
    {
         $this->_conn = $conn;
         $this->_fabrica = $fabrica;

    }

    public function getAlteracaoPostoSenha($token)
    {
        
        $sql = "SELECT *
                  FROM tbl_alteracao_posto_senha 
                 WHERE token = '$token'";
        $res = pg_query($this->_conn, $sql);

        if (pg_last_error($this->_conn)) {
            return ["erro" => true, "msg" => pg_last_error($this->_conn)];
        } else {
            if (pg_num_rows($res) > 0) {
                return pg_fetch_assoc($res);
            } else {
                return [];
            }
        }

    }


    public function gravaAlteraSenhaPostoFabrica($postoFabrica, $newSenha, $campoPrimeiroAcesso = '')
    {
        
        $sql = "UPDATE tbl_posto_fabrica SET senha = '{$newSenha}' {$campoPrimeiroAcesso} where posto_fabrica = {$postoFabrica}";
        $res = pg_query($this->_conn, $sql);

        if (pg_last_error($this->_conn)) {
            return ["erro" => true, "msg" => pg_last_error($this->_conn)];
        } else {
            return true;
        }

    }


    public function gravaAlteraSenhaLoginUnico($login_unico, $newSenha)
    {
        
        $sql = "UPDATE tbl_login_unico SET senha = '$newSenha' where login_unico = $login_unico";
        $res = pg_query($this->_conn, $sql);

        if (pg_last_error($this->_conn)) {
            return ["erro" => true, "msg" => pg_last_error($this->_conn)];
        } else {
            return true;
        }

    }

    public function validaSenhaDuplicada($codigo_posto, $new_senha) {

        $sql = "SELECT *
                  FROM tbl_posto_fabrica
                 WHERE codigo_posto='$codigo_posto' AND senha = '$new_senha'";
        $res = pg_query($this->_conn, $sql);
        if (pg_num_rows($res) > 0) {
            return pg_fetch_assoc($res);
        }

        return false;
    }

    public function getPostoFabrica($posto =null ,$login_fabrica = null, $posto_fabrica = null) {
        if (strlen($posto_fabrica) > 0) {
            $cond = " AND posto_fabrica={$posto_fabrica}";
        } 

        if (strlen($posto) > 0 && strlen($login_fabrica) > 0) {
            $cond = " AND posto = $posto AND fabrica = $login_fabrica";
        }

      
        $sql = "SELECT *
                  FROM tbl_posto_fabrica
                 WHERE 1=1 $cond";
        $res = pg_query($this->_conn, $sql);
        if (pg_num_rows($res) > 0) {
            return pg_fetch_assoc($res);
        }

        return false;
    }

    public function gravaAlteracaoPostoSenha($alteracao_posto_senha, $data_alteracao)
    {
        
        $sql = "UPDATE tbl_alteracao_posto_senha SET data_alteracao = '$data_alteracao' where alteracao_posto_senha = $alteracao_posto_senha";
        $res = pg_query($this->_conn, $sql);

        if (pg_last_error($this->_conn)) {
            return ["erro" => true, "msg" => pg_last_error($this->_conn)];
        } else {
            return true;
        }

    }


    public function checaSkipPostoFabrica($login_posto, $login_fabrica) {
        $sqlLU = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica={$login_fabrica} AND posto=".$login_posto;
        $resLU = pg_query($this->_conn, $sqlLU);

        if (pg_last_error($this->_conn) || pg_num_rows($resLU) == 0) {
            return true;
        }
        
        $parametros_adicionais = json_decode(pg_fetch_result($resLU, 0, 'parametros_adicionais'),1);
        if (isset($parametros_adicionais["senha_skip"]) && $parametros_adicionais["senha_skip"] == "true") {
            return false;
        }
        return true;

    }

    public function checaSkipLoginUnico($login_unico) {
        $sqlLU = "SELECT parametros_adicionais FROM tbl_login_unico WHERE login_unico=".$login_unico;
        $resLU = pg_query($this->_conn, $sqlLU);

        if (pg_last_error($this->_conn) || pg_num_rows($resLU) == 0) {
            return true;
        }
        
        $parametros_adicionais = json_decode(pg_fetch_result($resLU, 0, 'parametros_adicionais'),1);
        if (isset($parametros_adicionais["senha_skip"]) && $parametros_adicionais["senha_skip"] == "true") {
            return false;
        }
        return true;
    }

    public function getLoginUnico($login_unico = null, $email=null) {
        if (strlen($login_unico) > 0) {
            $cond = " AND login_unico = $login_unico ";
        }
        if (strlen($email) > 0) {
            $cond = " AND email = '$email' ";
        }
        $sql = "SELECT *
                  FROM tbl_login_unico
                 WHERE ativo IS TRUE $cond";
        $res = pg_query($this->_conn, $sql);

        if (pg_num_rows($res) > 0) {
            return pg_fetch_assoc($res);
        }

        return false;

    }

    public function getIpConfiavel($posto_fabrica = null, $login_unico=null) {
        if (strlen($posto_fabrica) > 0) {
            $cond = " AND posto_fabrica = $posto_fabrica ";
        }
        if (strlen($email) > 0) {
            $cond = " AND login_unico = $login_unico ";
        }
        $sql = "SELECT ip 
                  FROM tbl_ip_confiavel
                 WHERE 1=1 $cond";
        $res = pg_query($this->_conn, $sql);

        if (pg_num_rows($res) > 0) {
            foreach (pg_fetch_all($res) as $key => $value) {
                $retorno[] = $value["ip"];
            }
            return $retorno;
        }

        return false;

    }

    public function getLoginUnicoByEmailCodigoSeguranca($login_unico,$codigo_seguranca) {

        $sql = "SELECT parametros_adicionais,login_unico FROM tbl_login_unico WHERE login_unico = $login_unico AND parametros_adicionais::jsonb->>'codigo_seguranca' = '{$codigo_seguranca}'";
        $res = pg_query($this->_conn, $sql);
        if (pg_num_rows($res) > 0) {
            return pg_fetch_assoc($res);
        }

        return false;

    }

    public function getLoginByEmailCodigoSeguranca($posto_fabrica,$codigo_seguranca) {

        $sql = "SELECT parametros_adicionais,posto_fabrica FROM tbl_posto_fabrica WHERE posto_fabrica = $posto_fabrica AND parametros_adicionais::jsonb->>'codigo_seguranca' = '{$codigo_seguranca}'";
        $res = pg_query($this->_conn, $sql);

        if (pg_num_rows($res) > 0) {
            return pg_fetch_assoc($res);
        }

        return false;

    }

    public function atualizaParametrosAdicionaisLoginUnico($parametros_adicionais,$login_unico)
    {
        $sql = "UPDATE tbl_login_unico SET parametros_adicionais='{$parametros_adicionais}' WHERE login_unico=$login_unico";

        $res = pg_query($this->_conn, $sql);

        if (pg_last_error()) {
            return false;
        }
        return true;

    }
    
    public function atualizaParametrosAdicionaisLogin($parametros_adicionais,$posto_fabrica)
    {
        $sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais='{$parametros_adicionais}' WHERE posto_fabrica=$posto_fabrica";
        $res = pg_query($this->_conn, $sql);
        if (pg_last_error()) {
            return false;
        }
        return true;

    }

    public function gravaIpconfiavel($campos,$valores)
    {
        $sql = "INSERT INTO tbl_ip_confiavel($campos) VALUES ($valores)";
        $res = pg_query($this->_conn, $sql);
        if (pg_last_error()) {
            return false;
        }
        return true;

    }

    public function updateSenha($senha_old, $senha_new, $ip, $tipo, $posto_fabrica = null, $login_unico = null){

        if ($tipo == "posto") {

            $campo = "posto_fabrica";
            $valor = "$posto_fabrica";

            $retornoPostoFabrica = $this->updateSenhaPostoFabrica($senha_old, $senha_new, $ip, $posto_fabrica);

            if (!$retornoPostoFabrica) {
                $erro = true;
            } else {
                $erro = false;
            }

        }
        if ($tipo == "login_unico") {

            $campo = "login_unico";
            $valor = "$login_unico";

            $retornoLoginUnico = $this->updateSenhaLoginUnico($senha_old, $senha_new, $ip, $login_unico);
            if (!$retornoLoginUnico) {
                $erro = true;
            } else {
                $erro = false;
            }

        }

        if (!$erro) {

            $dadosLog = $this->gravaLogAlteracaoSenha($campo,$valor,$senha_old,$senha_new,$ip);
            if (!$dadosLog) {
                return false;
            }
            return true;

        } else {
            return false;
        }
    }

    public function gravaLogAlteracaoSenha($campo,$valor,$senha_old,$senha_new,$ip)
    {
        $sql = "INSERT INTO  tbl_log_alteracao_senha ({$campo}, senha_old,senha_new,ip) VALUES({$valor},'".$senha_old."','".$senha_new."','".$ip."')";
        $res = pg_query($this->_conn, $sql);
        if (pg_last_error()) {
            return false;
        }
        return true;
    }

    public function updateSenhaPostoFabrica($senha_old, $senha_new, $ip, $posto_fabrica) {

        $sqlPosto = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto_fabrica=".$posto_fabrica;
        $resPosto = pg_query($this->_conn, $sqlPosto);

        if (pg_last_error($this->_conn) || pg_num_rows($resPosto) == 0) {
            return false;
        }
   
        $parametros_adicionais = json_decode(pg_fetch_result($resPosto, 0, 'parametros_adicionais'),1);
        unset($parametros_adicionais["senha_skip"]);
        unset($parametros_adicionais["senha_start"]);
        $upparametros_adicionais = json_encode($parametros_adicionais);
        $sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais='".$upparametros_adicionais."',senha='".$senha_new."' WHERE posto_fabrica=".$posto_fabrica;
        $res = pg_query($this->_conn, $sql);
        if (pg_last_error($this->_conn)) {
            return false;
        } 

        return true;
    }

    public function updateSenhaLoginUnico($senha_old, $senha_new, $ip, $login_unico) {

        $sqlLU = "SELECT parametros_adicionais FROM tbl_login_unico WHERE login_unico=".$login_unico;
        $resLU = pg_query($this->_conn, $sqlLU);

        if (pg_last_error($this->_conn) || pg_num_rows($resLU) == 0) {
            return false;
        }

        $parametros_adicionais = json_decode(pg_fetch_result($resLU, 0, 'parametros_adicionais'),1);
        unset($parametros_adicionais["senha_skip"]);
        unset($parametros_adicionais["senha_start"]);
        $upparametros_adicionais = json_encode($parametros_adicionais);

        $sql = "UPDATE tbl_login_unico 
                   SET parametros_adicionais='".$upparametros_adicionais."',
                   senha='".$senha_new."' 
                 WHERE login_unico=".$login_unico;
        $res = pg_query($this->_conn, $sql);
        if (pg_last_error($this->_conn)) {
            return false;
        } 

        return true;
    }

    public function getAlteracaoSenha($posto = null, $login_unico = null) {
        
        if (strlen($posto) > 0) {
            $cond  = " AND tbl_log_alteracao_senha.posto_fabrica={$posto}";
        }
        
        if (strlen($login_unico) > 0) {
            $cond  = " AND tbl_log_alteracao_senha.login_unico={$login_unico}";
        }

        $sql = "SELECT tbl_log_alteracao_senha.data_alteracao
                  FROM tbl_log_alteracao_senha 
                 WHERE 1=1 
                    {$cond} 
              ORDER BY tbl_log_alteracao_senha.data_alteracao 
            DESC LIMIT 1";
        $res = pg_query($this->_conn, $sql);

        if (pg_num_rows($res) > 0) {

            $data_alteracao = pg_fetch_result($res, 0, 'data_alteracao');

            $sqlData     = "SELECT current_timestamp - interval '90 days';";
            $resData     = pg_query($this->_conn, $sqlData);
            $data_limite = pg_fetch_result($resData, 0, 0);

            if (strtotime($data_alteracao) < strtotime($data_limite)) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }

    }

    public function gravaAlterarDepois($login_posto = null, $login_fabrica = null, $login_unico = null) {

        if (strlen($login_posto) > 0) {
            $sqlPosto = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica={$login_fabrica} AND posto=".$login_posto;
            $resPosto = pg_query($this->_conn, $sqlPosto);

            if (pg_last_error($this->_conn) || pg_num_rows($resPosto) == 0) {
                return false;
            }

            $parametros_adicionais = json_decode(pg_fetch_result($resPosto, 0, 'parametros_adicionais'),1);
            $parametros_adicionais["senha_skip"] = "true";
            $upparametros_adicionais = json_encode($parametros_adicionais);
            $sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais='".$upparametros_adicionais."'  WHERE fabrica={$login_fabrica} AND posto=".$login_posto;
            $res = pg_query($this->_conn, $sql);
            if (pg_last_error($this->_conn)) {
                return false;
            } 

            return true;
        } else {

            $sqlLU = "SELECT parametros_adicionais FROM tbl_login_unico WHERE login_unico=".$login_unico;
            $resLU = pg_query($this->_conn, $sqlLU);

            if (pg_last_error($this->_conn) || pg_num_rows($resLU) == 0) {
                return false;
            }

            $parametros_adicionais = json_decode(pg_fetch_result($resLU, 0, 'parametros_adicionais'),1);
            $parametros_adicionais["senha_skip"] = "true";
            $upparametros_adicionais = json_encode($parametros_adicionais);
            $sql = "UPDATE tbl_login_unico 
                       SET parametros_adicionais='".$upparametros_adicionais."'
                     WHERE login_unico=".$login_unico;
            $res = pg_query($this->_conn, $sql);
            if (pg_last_error($this->_conn)) {
                return false;
            } 

            return true;

        }

    } 

    public function envio_email($tipo_email, $id_solicitante, $nome, $fabrica_nome, $cook_login, $posto_email, $esqueci_senha, $mailer, $con){
        $insert_alteracao_senha = null;
        $token = $this->token($email_destino, $fabrica);
        $data = new \DateTime();
        $data_solicitacao = $data->format('Y-m-d H:i:s.u');
        //ip que solicito alteração de senha
        $ip_solicitante = $_SERVER['HTTP_X_FORWARDED_FOR'] ? : $_SERVER['REMOTE_ADDR'];

        if($tipo_email == 'normal'){
            $insert_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (posto_fabrica, token, data_solicitacao, tipo_alteracao, ip) VALUES ($id_solicitante, '$token', '$data_solicitacao','esqueceu_senha', '$ip_solicitante')";
        }else if($tipo_email == 'login_unico'){
            $insert_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (login_unico, token, data_solicitacao, tipo_alteracao, ip) VALUES ($id_solicitante, '$token', '$data_solicitacao', 'esqueceu_senha_login_unico', '$ip_solicitante')";
        }
        pg_query($con, $insert_alteracao_senha);

        $email_origem  = "suporte@telecontrol.com.br";
        $email_destino = $posto_email;
        $assunto       = "Telecontrol - " . $esqueci_senha;
        $corpo         = $this->email_senha($tipo_email, $nome, $fabrica_nome, $token, $cook_login);

        $corpo = (mb_detect_encoding($corpo, "UTF-8")) ? $corpo : utf8_encode($corpo);
        $mailer->blackListVerify($email_destino);
        $res = $mailer->sendMail(
            $email_destino,
            $assunto,
            $corpo,
            'noreply2@telecontrol.com.br'
        );

        return $res;
    }

    //  função que cria o e-mail para enviar, com texto diferenciado dependendo do idioma
    public function email_senha($tipo,$nome, $f_nome, $token, $idioma = 'pt-br') {

        if ($demo = strpos($tipo, 'demo')) {
            $tipo = str_replace('_demo', '', $tipo);
        }

        //if ($demo) echo("Mostrar DEMO <u>$tipo</u> en $idioma");

        if ($tipo == 'normal'){

            switch ($idioma) {
              case "es":
                    $body = "<p>
                                <strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
                                <br/><br/>
                                Apreciado/a $nome,
                                <br/>
                                Se nos ha solicitado el envío de los datos de acceso (usuario y clave) para acceder a la fábrica $f_nome:
                                <br/><br/>
                                Para restablecer su contraseña, haga clic en el enlace a continuación:
                            ";
                break;
             case "en":
                    $body = "<p>
                                <strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
                                <br/><br/>
                                Dear $nome,
                                <br/>
                                The following login and password has been requested to access the system for {$f_nome}:
                                <br/><br/>
                                To reset your password, click the link below:
                            ";
                break;
                case "de":
                    $body = "<p>
                                    <strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
                                    <br/><br/>
                                    Sehr geehrte $nome,
                                    <br/>
                                    Wir bestätgen den Eingang der Beantragung von Login und Kennwort zwecks Zugang zum System der $f_nome GmbH:
                                    <br/></br>
                                    Klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:
                                ";
                break;
                default:
                    $body = "<p>
                                    Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
                                    <br/><br/>
                                    Caro {$nome},
                                    <br/>
                                    Foi solicitado a recuperação de senha para acessar o sistema na fábrica ${f_nome}:
                                    <br/><br/>
                                    Para redefinir a senha, clique no link abaixo:
                                ";
            }

            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $url_primaria = str_replace('esqueci_senha_new.php', 'alterar_senha.php', $url);
            $link = "<a href='".$url_primaria."?token=".$token."' target='_blank'>Clique Aqui</a>";

            $msg_link = "<br><br>Se o link não funcionar, copie e cole o link abaixo no seu navegador:<br>";

            $body.= "<br>".$link.$msg_link.$url_primaria."?token=". $token."</p><br>\n";

            switch ($idioma) {
              case "es":
                    $body .= "  <b>Atención:</b> fecha límite máxima para cambiar la contraseña con este token: 1h.
                                <br>
                                Después de este período, el enlace estará inactivo por razones de seguridad.
                                <br>
                                En caso de enlace expirado, es necesario volver a realizar el proceso de recuperación de contraseña.
                                <br><br>
                                Si no solicitó un restablecimiento de contraseña, ignore este correo electrónico.
                                <br><br><br>
                                Sinceramente.,
                                <br><br>\n";
                break;
             case "en":
                    $body .= "  <b>Attention:</b> Maximum deadline for changing your password using this token: 1h.
                                <br>
                                After this period, the link will be inactive for security reasons.
                                <br>
                                In case of expired link, it is necessary to do the password recovery process again.
                                <br><br>
                                If you did not request a password reset, please disregard this email.
                                <br><br><br>
                                Graciously.,
                                <br><br>\n";
                break;
                case "de":
                    $body .= "  <b>Achtung:</b> Maximale Frist für die Änderung des Passworts mit diesem Token: 1 Stunde.
                                <br>
                                Nach diesem Zeitraum ist der Link aus Sicherheitsgründen inaktiv.
                                <br>
                                Im Falle eines abgelaufenen Links muss der Kennwortwiederherstellungsprozess erneut durchgeführt werden.
                                <br><br>
                                Wenn Sie kein Zurücksetzen des Passworts beantragt haben, ignorieren Sie diese E-Mail.
                                <br><br><br>
                                Mit freundlichen Grüßen.,
                                <br><br>\n";
                break;
                default:
                    $body .= "  <b>Atenção:</b> Prazo máximo para troca de senha através deste token: 1h.
                                <br>
                                Após este período, o link ficará inativo por questões de segurança.
                                <br>
                                Em caso de link expirado, é necessário fazer novamente o processo de recuperação de senha.
                                <br><br>
                                Se não tiver solicitado a redefinição de senha, desconsidere este e-mail.
                                <br><br><br>
                                Atenciosamente.,
                                <br><br>\n";
            }

            $body .="Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
                    "</p>";


        }elseif($tipo == 'login_unico'){

            switch ($idioma) {
            case "es":
                    $body = "
                        <p>
                                <strong>Nota: Este mensaje es automático. **** POR FAVOR NO RESPONDA ESTE MENSAJE ****</strong>
                                <br/><br/>
                                Apreciado/a $nome,
                                <br/>
                                Se ha solicitado la recuperación de la contraseña de su Login Único:
                                <br/><br/>
                                Para restablecer su contraseña, haga clic en el enlace a continuación:
                        ";
                break;
            case "en":
                    $body = "
                            <p>
                                <strong>Note: This e-mail is sent automatically. **** PLEASE DO NOT ANSWER THIS MESSAGE ****</strong>
                                <br/><br/>
                                Dear/a $nome,
                                <br/>
                                You asked for password recovery for your Unique Login:
                                <br/><br/>
                                To reset your password, click the link below:
                            ";
                break;
            case "de":
                    $body ="
                            <p>
                                <strong>Anm.: Diese mail wurde automatisch erstellt. **** BITTE NICHT ANTWORTEN ****</strong>
                                <br/><br/>
                                Sehr geehrte $nome,
                                <br/>
                                Wir bestätgen den Eingang der Beantragung von Login und Kennwort zwecks Zugang zum System der $f_nome GmbH:
                                <br/><br/>
                                Klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:
                            ";
                break;
                default:
                    $body = "
                            <p>
                                Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****
                                <br/><br/>
                                Caro $nome,
                                <br/>
                                Foi solicitada a recuperação de senha para o seu Login Único:
                                <br/><br/>
                                Para redefinir a senha, clique no link abaixo:
                            ";
            }


            $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $url_primaria = str_replace('esqueci_senha_new.php', 'alterar_senha.php', $url);
            $link = "<a href='".$url_primaria."?token=".$token."' target='_blank'>Clique Aqui</a>";

            $msg_link = "<br><br>Se o link não funcionar, copie e cole o link abaixo no seu navegador:<br>";

            $body.= "<br>".$link.$msg_link.$url_primaria."?token=". $token."</p><br>\n";

            switch ($idioma) {
              case "es":
                    $body .= "  <b>Atención:</b> fecha límite máxima para cambiar la contraseña con este token: 1h.
                                <br>
                                Después de este período, el enlace estará inactivo por razones de seguridad.
                                <br>
                                En caso de enlace expirado, es necesario volver a realizar el proceso de recuperación de contraseña.
                                <br><br>
                                Si no solicitó un restablecimiento de contraseña, ignore este correo electrónico.
                                <br><br><br>
                                Sinceramente.,
                                <br><br>\n";
                break;
             case "en":
                    $body .= "  <b>Attention:</b> Maximum deadline for changing your password using this token: 1h.
                                <br>
                                After this period, the link will be inactive for security reasons.
                                <br>
                                In case of expired link, it is necessary to do the password recovery process again.
                                <br><br>
                                If you did not request a password reset, please disregard this email.
                                <br><br><br>
                                Graciously.,
                                <br><br>\n";
                break;
                case "de":
                    $body .= "  <b>Achtung:</b> Maximale Frist für die Änderung des Passworts mit diesem Token: 1 Stunde.
                                <br>
                                Nach diesem Zeitraum ist der Link aus Sicherheitsgründen inaktiv.
                                <br>
                                Im Falle eines abgelaufenen Links muss der Kennwortwiederherstellungsprozess erneut durchgeführt werden.
                                <br><br>
                                Wenn Sie kein Zurücksetzen des Passworts beantragt haben, ignorieren Sie diese E-Mail.
                                <br><br><br>
                                Mit freundlichen Grüßen.,
                                <br><br>\n";
                break;
                default:
                    $body .= "  <b>Atenção:</b> Prazo máximo para troca de senha através deste token: 1h.
                                <br>
                                Após este período, o link ficará inativo por questões de segurança.
                                <br>
                                Em caso de link expirado, é necessário fazer novamente o processo de recuperação de senha.
                                <br><br>
                                Se não tiver solicitado a redefinição de senha, desconsidere este e-mail.
                                <br><br><br>
                                Atenciosamente.,
                                <br><br>\n";
            }

            $body .= "Suporte Telecontrol Networking.<br>suporte@telecontrol.com.br\n".
                    "</p>";

        }

        //Tira os links reais quando solicitado e-mail de demonstração
        if ($demo) {
            $body = preg_replace('/href=[\'"]["\'].+\s/', "href='javascript:void(0);' ", $body);
        }

        return $body;

    }

    public function token($email, $fabrica){
        $token = hash('sha256', $email . ':' . $fabrica . ':' . microtime() . mt_rand());
        return $token;
    }

    public function countDigits( $str )
    {
        return preg_match_all( "/[0-9]/", $str );
    }

    public function countLetters( $str )
    {
        return preg_match_all( "/[a-zA-ZÀ-ú]/", $str );
    }

}

