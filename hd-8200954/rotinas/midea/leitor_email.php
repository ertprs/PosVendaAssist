<?php
/**
 * 2017.07.12
 * @author  Guilherme Monteiro / Vitor Esposito
 * @version 2.0
 *
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/communicator.class.php';
include dirname(__FILE__) . '/../funcoes.php';

/* AmazonTC */
include dirname(__FILE__) . '/../../class/aws/s3_config.php';
include S3CLASS;

$login_fabrica = 169;

/* VERIFICA SE A ROTINA AINDA ESTA PROCESSANDO */
$arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
$processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
$arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

$count_routine = 0;
foreach ($processos as $value) {
    if (preg_match("/(.*)php (.*)\/midea\/{$arquivo_rotina}/", $value)) {
        $count_routine += 1;
    }
}
if ($count_routine > 2) { exit; }
/* FIM VERIFICAÇÃO */

function passwordDecrypt($enc) {
    $key1 = preg_replace("/\/.+/", "", $enc);
    $key2 = preg_replace("/.+\//", "", $enc);
    $key = $key2.$key1;
    $key = hex2bin($key);
    $enc = str_replace($key1."/", "", $enc);
    $enc = str_replace("/".$key2, "", $enc);
    return openssl_decrypt($enc, 'aes-128-cbc', $key);
}

function flushLog($msg) {
    global $login_fabrica;
    $arq = 'midea';
    $arquivo_log = "/tmp/leitor-email-$arq-".date("Ymd").".txt";

    ob_start();
    if (!file_exists($arquivo_log)) {
        system("touch {$arquivo_log}");
    }else{
        echo "\n";
    }

    echo date('H:m')." - $msg";
    $b = ob_get_contents();

    file_put_contents($arquivo_log, $b, FILE_APPEND);
    ob_end_flush();
    ob_clean();
}

function insere_anexo_atendimento($hd_chamado){
    global $attachment, $login_fabrica, $con;

    if (!count($attachment)){
        return true;
    }

    $arq = 0;
    $s3  = new AmazonTC('callcenter', (int) $login_fabrica);
    $i   = count($s3->getObjectList($hd_chamado));

    $arquivo_invalido = false;

    foreach ($attachment as $attachments) {
        $temporario = '/tmp/'.$attachments['filename'];

        if (!file_put_contents($temporario, $attachments['filedata'])){
            flushLog("Não será possível enviar, arquivo temporário não foi criado corretamente. Chamado: $hd_chamado");
        }

        $types      = array('png', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx', 'txt');
        $type       = trim(strtolower(preg_replace('/.+\./', '', $attachments['filename'])));
        $type       = preg_replace('/\W/', '', $type);
        $file       = array(
            'tmp_name' => $temporario,
            'name'     => $attachments['filename'],
            'error'    => 0,
            'type'     => mime_content_type($temporario),
            'size'     => filesize($temporario)
        );

        if ($file['size'] <= 4718592) {
            $type = ($type == 'jpeg') ? 'jpg' : $type;

            if (!in_array($type, $types)) {
                $arquivo_invalido = true;
                system("rm $temporario");
                flushLog("Não será possível enviar, arquivo no formato incorreto. Chamado: $hd_chamado");
            } else {
                pg_query($con, 'BEGIN');
                $sql = "INSERT INTO tbl_hd_chamado_item (
                                    hd_chamado  ,
                                    comentario  ,
                                    interno     ,
                                    status_item ,
                                    atendimento_telefone
                             ) VALUES (
                                    $hd_chamado                          ,
                                    'Arquivo anexado: ".$attachments['filename']."',
                                    't'                                  ,
                                    'Aberto'                             ,
                                    'f'
                             ) ";

                $res = pg_query($con, $sql);
                if(strlen(pg_last_error()) > 0){
                    pg_query($con, 'ROLLBACK');
                    system("rm $temporario");
                    flushLog("Não será possível enviar, erro ao tentar inserir uma nova interação. Chamado: $hd_chamado");
                }else{
                    $s3->upload("{$hd_chamado}-{$i}", $file);
                    if ($s3->result == false) {
                        pg_query($con, 'ROLLBACK');
                        system("rm $temporario");
                        flushLog("Não será possível enviar, erro na função de upload. Chamado: $hd_chamado");
                    } else {
                        pg_query($con, 'COMMIT;');
                        system("rm $temporario");
                        $arq++;
                    }
                }
            }
        } else {
            system("rm $temporario");
            flushLog("Não será possível enviar, arquivo muito pesado. Chamado: $hd_chamado");
        }
        $i++;
    }

    if ((count($attachment) !== $arq && $arq !== 0) || $arquivo_invalido) {
        if ($arquivo_invalido) {
            $msg = "Anexo do email: Um ou mais anexos não foram inseridos por ser um arquivo inválido";
        } else {
            $msg = "Anexo do email: Um ou mais anexos não foram inseridos";
        }
        $sql = "INSERT INTO tbl_hd_chamado_item (
                            hd_chamado  ,
                            comentario  ,
                            interno     ,
                            status_item ,
                            atendimento_telefone
                     ) VALUES (
                            $hd_chamado                          ,
                            '{$msg}',
                            't'                                  ,
                            'Aberto'                             ,
                            'f'
                     ) ";
        $res = pg_query($con, $sql);
    }elseif ($arq == 0 && !$arquivo_invalido) {
        return false;
    }
    return true;
}

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

/* PREPARES */
pg_prepare($con, 'inclui_atendente', "UPDATE tbl_hd_chamado SET atendente = $1 WHERE hd_chamado = $2 AND fabrica = $login_fabrica");

pg_prepare($con, 'verifica_atendimento_aberto', "SELECT
                        tbl_hd_chamado_extra.email,
                        tbl_hd_chamado.hd_chamado,
                        tbl_admin.email AS email_admin,
                        tbl_admin.nome_completo
                    FROM tbl_hd_chamado
                        JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica = {$login_fabrica}
                    WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
                        AND tbl_hd_chamado_extra.email = $1
                        AND tbl_hd_chamado.status <> 'Resolvido'");

pg_prepare($con, 'consulta_admins', "SELECT
                                        tbl_callcenter_email_admin.admin,
                                        tbl_callcenter_email.limite_atendimento
                                    FROM tbl_callcenter_email
                                        JOIN tbl_callcenter_email_admin USING(callcenter_email)
                                        JOIN tbl_admin ON tbl_admin.admin = $1 AND tbl_admin.email = tbl_callcenter_email.email AND tbl_admin.fabrica = {$login_fabrica}
                                    WHERE tbl_callcenter_email.fabrica = {$login_fabrica}");

pg_prepare($con, 'qtde_atend_admin', "SELECT
                                        COUNT(tbl_hd_chamado_extra.hd_chamado) AS qtde_chamado
                                    FROM tbl_hd_origem_admin
                                        JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_origem_admin.hd_chamado_origem AND tbl_hd_chamado_origem.fabrica = {$login_fabrica}
                                        JOIN tbl_admin ON tbl_admin.admin = tbl_hd_origem_admin.admin AND tbl_admin.fabrica = {$login_fabrica} AND tbl_admin.admin = $1
                                        LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_hd_origem_admin.admin AND tbl_hd_chamado.fabrica = {$login_fabrica} AND lower(tbl_hd_chamado.status) not in ('resolvido', 'cancelado')
					LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_extra.hd_chamado_origem = tbl_hd_chamado_origem.hd_chamado_origem
                                    WHERE tbl_hd_origem_admin.fabrica = {$login_fabrica}
                                        AND tbl_admin.ativo IS TRUE
                                        AND tbl_hd_chamado_origem.descricao = 'Email'");

$fila_distribuicao = array();
try{
    /* LISTA TODOS OS CHAMADOS SEM ATENDENTES */
    $sql = "SELECT DISTINCT hd_chamado, admin, data
            FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND atendente IS NULL ORDER BY data, admin";
    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);
    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $admin = pg_fetch_result($res, $i, 'admin');
            $hd_chamado = pg_fetch_result($res, $i, 'hd_chamado');
            $fila_distribuicao[$admin][] = $hd_chamado;
        }
    }

    $sql = "SELECT
                tbl_callcenter_email.email,
                tbl_callcenter_email.hostname,
                tbl_callcenter_email.senha,
                tbl_admin.admin
            FROM tbl_callcenter_email JOIN tbl_admin ON(tbl_callcenter_email.callcenter_email = tbl_admin.callcenter_email AND tbl_admin.fabrica = {$login_fabrica})
            WHERE ativa IS TRUE AND tbl_callcenter_email.fabrica = {$login_fabrica}";
    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);
    if ($rows > 0) {
        for ($i = 0; $i < $rows; $i++) {
            $hostname = '{'.pg_fetch_result($res, $i, 'hostname').'}INBOX';
            $username = pg_fetch_result($res, $i, 'email');
            $password = passwordDecrypt(pg_fetch_result($res, $i, 'senha'));
            $admin    = pg_fetch_result($res, $i, 'admin');
            $inbox = imap_open($hostname, $username, $password);
            if (strlen(imap_last_error()) > 0) {
                throw new Exception("Erro ao tentar iniciar conexão com o email: $username. Erro: ".imap_last_error());
            }
            $emails = imap_search($inbox,'UNSEEN');
            if(is_array($emails)) {

                foreach($emails as $email_number) {

                    $struct   = imap_fetchstructure($inbox, $email_number);

                    if (count($struct->parts) == 0) { /* EMAIL TEXTO SIMPLES */
                        $message = imap_body($inbox, $email_number, FT_PEEK);
                    }else{ /* MULTI-PART */
                        $partstring     = '';
                        $partattachment = array();
                        $filename       = '';
                        $attachment     = array();
                        for ($k=0; $k < count($struct->parts); $k++) {
                            if (count($struct->parts[$k]->parts)) { /* MULTIDIMENSIONAL */
                                for ($j=0; $j < count($struct->parts[$k]->parts); $j++) {
                                    if ($struct->parts[$k]->parts[$j]->subtype == 'PLAIN') {
                                        $partstring = (1 + $k).".".($j + 1);
                                    }
                                    if ($struct->parts[$k]->parts[$j]->disposition == 'ATTACHMENT') {
                                        for ($aux=0; $aux < count($struct->parts[$k]->parts[$j]->parameters); $aux++) {
                                            if ($struct->parts[$k]->parts[$j]->parameters[$aux]->attribute == 'NAME') {
                                                $filename = $struct->parts[$k]->parts[$j]->parameters[$aux]->value;
                                                $partattachment[] = array((1 + $k).".".($j + 1), $filename);
                                            }
                                        }
                                    }
                                }
                            }else{
                                if ($struct->parts[$k]->subtype == 'PLAIN') {
                                    $partstring = $k + 1;
                                }
                                if ($struct->parts[$k]->disposition == 'ATTACHMENT') {
                                    for ($aux=0; $aux < count($struct->parts[$k]->parameters); $aux++) {
                                        if ($struct->parts[$k]->parameters[$aux]->attribute == 'NAME') {
                                            $filename = $struct->parts[$k]->parameters[$aux]->value;
                                            $partattachment[] = array($k + 1, $filename);
                                        }
                                    }
                                }
                            }
                        }

                        $message = imap_qprint(imap_fetchbody($inbox, $email_number, $partstring, FT_PEEK));

                        if (count($partattachment)) {
                            foreach ($partattachment as $part) {
                                $file = imap_fetchbody($inbox, $email_number, $part[0], FT_PEEK);

                                $decoded_data = base64_decode($file);
                                if ($decoded_data == false) {
                                    $attachment[] = array("filename" => addslashes($part[1]), "filedata" => $file);
                                } else {
                                    $attachment[] = array("filename" => addslashes($part[1]), "filedata" => $decoded_data);
                                }
                            }
                        }
                    }

                    /* RETIRA ASSINATURA DO EMAIL SE POSSUIR */
                    $assinatura = strpos($message, '--=20');
                    if ($assinatura) {
                        $message = trim(substr($message, 0, $assinatura));
                    }

                    $message = str_replace("'","",$message);
                    $overview = imap_fetch_overview($inbox, $email_number);
                    $email    = TcComm::parseEmail($overview[0]->from);
                    $email    = $email[0];
                    $nome     = $overview[0]->from;
                    $nome     = iconv_mime_decode(preg_replace('/<.+$/','',$nome));

                    $valida = strstr($email, 'noreply');
                    if($valida == true){
                        continue;
                    }

                    /* VERIFICA SE JÁ POSSUI UM ATENDIMENTO ABERTO QUE NÃO ESTA RESOLVIDO */
                    $res_verifica = pg_execute($con, 'verifica_atendimento_aberto', array($email));
                    pg_query($con, 'BEGIN');
                    if(pg_num_rows($res_verifica) > 0){
                        $hd_chamado     = pg_fetch_result($res_verifica, 0, 'hd_chamado');
                        #$email_admin    = pg_fetch_result($res_verifica, 0, 'email');
                        $email_admin    = pg_fetch_result($res_verifica, 0, 'email_admin');
                        $nome_completo  = pg_fetch_result($res_verifica, 0, 'nome_completo');

                        $sql_insert = "INSERT INTO tbl_hd_chamado_item (
                                            hd_chamado,
                                            comentario,
                                            interno,
                                            status_item,
                                            atendimento_telefone
                                        )VALUES(
                                            $hd_chamado,
                                            'Enviado via Email: $message',
                                            't',
                                            'Aberto',
                                            'f'
                                        )";
                        pg_query($con, $sql_insert);
                        if (strlen(pg_last_error()) > 0) {
                            pg_query($con, 'ROLLBACK');
                            throw new Exception("Erro ao tentar inserir uma nova interação no atendimento: $hd_chamado. Erro: ".pg_last_error());
                        }

                        $ret_anexo = insere_anexo_atendimento($hd_chamado);
                        if (!$ret_anexo) {
                            pg_query($con, 'ROLLBACK');
                            throw new Exception("Erro ao tentar anexar um arquivo no chamado: $hd_chamado.");
                        }

                        $assunto       = $nome_completo.' - Atendimento '.$hd_chamado;
                        $mensagem      = "<strong>Foi feita uma interação no Atendimento: $hd_chamado via Email.</strong><br><br>";
                        $externalId    = 'smtp@posvenda';
                        $externalEmail = 'noreply@telecontrol.com.br';

                        $mailTc = new TcComm($externalId);
                        $res = $mailTc->sendMail(
                            $email_admin,
                            $assunto,
                            $mensagem,
                            $externalEmail
                        );

                        imap_setflag_full($inbox, $email_number, "\\Seen");
                        pg_query($con, 'COMMIT');
                    }else{
                        /* INSERE NOVO ATENDIMENTO SEM VINCULAR PARA NENHUM ATENDENTE */
                        $data_providencia = date('Y-m-d').' 00:00:00';
                        $sql_insert = "INSERT INTO tbl_hd_chamado(
                                            admin,
                                            fabrica_responsavel,
                                            fabrica,
                                            titulo,
                                            status,
                                            atendente,
                                            data_providencia,
                                            categoria
                                        )VALUES(
                                            $admin,
                                            $login_fabrica,
                                            $login_fabrica,
                                            'Atendimento interativo',
                                            'Aberto',
                                            null,
                                            '$data_providencia',
                                            'reclamacao_produto'
                                        )RETURNING hd_chamado";

                        $res_insert = pg_query($con, $sql_insert);
                        if (strlen(pg_last_error()) > 0) {
                            pg_query($con, 'ROLLBACK');
                            throw new Exception("(1) Erro ao tentar inserir uma novo atendimento do email de origem: $email. Erro: ".pg_last_error());
                        }
                        $hd_chamado = pg_result($res_insert, 0, 'hd_chamado');
                        $array_campos_adicionais = array(
                                                    'admin_agendamento' => $admin,
                                                    'data_programada'   => date('d/m/Y')
                                                );
                        $array_campos_adicionais = json_encode($array_campos_adicionais);

                        $sql_origem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$login_fabrica} AND descricao = 'Email' ";
                        $res_origem = pg_query($con, $sql_origem);
                        if(pg_num_rows($res_origem) == 0){
                            throw new Exception("Não foi possível encontrar o código da origem do chamado do tipo Email");
                        }
                        $hd_chamado_origem = pg_fetch_result($res_origem, 0, 'hd_chamado_origem');

                        $sql_insert = "INSERT INTO tbl_hd_chamado_extra(
                                            hd_chamado,
                                            origem,
                                            nome,
                                            email,
                                            abre_os,
                                            atendimento_callcenter,
                                            hd_chamado_origem,
                                            leitura_pendente,
                                            array_campos_adicionais,
                                            reclamado
                                        )VALUES(
                                            $hd_chamado,
                                            'Email',
                                            '$nome',
                                            '$email',
                                            'f',
                                            't',
                                            $hd_chamado_origem,
                                            't',
                                            '$array_campos_adicionais',
                                            E'$message'
                                        )";

                        pg_query($con, $sql_insert);
                        if (strlen(pg_last_error()) > 0) {
                            pg_query($con, 'ROLLBACK');
                            throw new Exception("(2) Erro ao tentar inserir uma novo atendimento do email de origem: $email. Erro: ".pg_last_error());
                        }

			$sql_insert = "INSERT INTO tbl_hd_chamado_item (
                                            hd_chamado,
                                            comentario,
                                            interno,
                                            status_item,
                                            atendimento_telefone
                                        )VALUES(
                                            $hd_chamado,
                                            E'Enviado via Email: $message',
                                            't',
                                            'Aberto',
                                            'f'
                                        )";
                        pg_query($con, $sql_insert);
                        if (strlen(pg_last_error()) > 0) {
                            pg_query($con, 'ROLLBACK');
                            throw new Exception("Erro ao tentar inserir uma nova interação no atendimento: $hd_chamado. Erro: ".pg_last_error());
                        }


                        $ret_anexo = insere_anexo_atendimento($hd_chamado);
                        if (!$ret_anexo) {
                            pg_query($con, 'ROLLBACK');
                            throw new Exception("Erro ao tentar inserir um novo anexo ao atendimento do email de origem: $email.");
                        }

                        imap_setflag_full($inbox, $email_number, "\\Seen");
                        pg_query($con, 'COMMIT');
                        $fila_distribuicao[$admin][] = $hd_chamado;
                    }
                }
            }
        }
    }
}catch(Exception $e){
    flushLog($e->getMessage());
}

/* INICIALIZA DISTRIBUIÇÃO */
if (count($fila_distribuicao)) {
    foreach ($fila_distribuicao as $admin => $array_chamados) {
        $array_admins = array();
        $res_admins   = pg_execute($con, 'consulta_admins', array($admin));
        for ($i = 0; $i < pg_num_rows($res_admins); $i++) {
            $atendente = pg_fetch_result($res_admins, $i, 'admin');
            $limite_atendimento = pg_fetch_result($res_admins, $i, 'limite_atendimento');

            $res_qtde = pg_execute($con, 'qtde_atend_admin', array($atendente));
            $limite_atendimento = $limite_atendimento - pg_fetch_result($res_qtde, 0, 'qtde_chamado');
            $array_admins[$admin][$atendente] = ($limite_atendimento < 0) ? 0 : $limite_atendimento;
        }
        arsort($array_admins[$admin]);

        foreach ($array_chamados as $hd_chamado) {
            foreach ($array_admins[$admin] as $atendente => $limite_atendimento) {
                if (($limite_atendimento - 1) < 0) { continue; }

                /* ATUALIZA VALOR E ORDENA NOVAMENTE */
                $array_admins[$admin][$atendente] = $limite_atendimento - 1;
                arsort($array_admins[$admin]);

                $res = pg_execute($con, 'inclui_atendente',array($atendente, $hd_chamado));
                if (strlen(pg_last_error()) > 0) {
                    flushLog("Erro ao tentar distribuir o chamado $hd_chamado. Erro: ".pg_last_error());
                }
                break;
            }
        }
    }
}

$phpCron->termino();

?>
