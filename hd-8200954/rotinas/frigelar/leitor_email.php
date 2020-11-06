<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/communicator.class.php';
include dirname(__FILE__) . '/../../class/imapClass.php';
include dirname(__FILE__) . '/../funcoes.php';

$header  = "MIME-Version: 1.0\n";
$header .= "Content-type: text/html; charset=iso-8859-1\n";
$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

mail("lucas.bicaleto@telecontrol.com.br", "TESTE DE ROTINA", "TESTE DE ROTINA ANTES", $header);

/* Inicio Processo */
$fabrica_telecontrol = 198;
$phpCron = new PHPCron($fabrica_telecontrol, __FILE__);
$phpCron->inicio();


include dirname(__FILE__) . "/../../class/tdocs.class.php";

function passwordDecrypt($enc) {
    $key1 = preg_replace("/\/.+/", "", $enc);
    $key2 = preg_replace("/.+\//", "", $enc);
    $key = $key2.$key1;
    $key = hex2bin($key);
    $enc = str_replace($key1."/", "", $enc);
    $enc = str_replace("/".$key2, "", $enc);
    return openssl_decrypt($enc, 'aes-128-cbc', $key);
}

function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function flushLog($msg,$fabrica,$arq) {
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

function insere_anexo_atendimento($hd_chamado,$attachment,$fabrica,$nome_fabrica){
    global  $con, $fabrica_nome;
    $tDocs       = new TDocs($con, $fabrica);


    if (!count($attachment)){
        return true;
    }

    $arq = 0;
    $arquivo_invalido = false;


    foreach ($attachment as $attachments) {
        $temporario = '/tmp/'.$attachments['filename'];

        if (!file_put_contents($temporario, $attachments['filedata'])){
          flushLog("Não será possível enviar, arquivo temporário não foi criado corretamente. Chamado: $hd_chamado",$fabrica,$fabrica_nome);
        }

        $types      = array('png','odt', 'jpg', 'jpeg', 'bmp', 'pdf', 'doc', 'docx', 'txt');
        $type       = trim(strtolower(preg_replace('/.+\./', '', $attachments['filename'])));
        $type       = preg_replace('/\W/', '', $type);
        $file       = array(
            'tmp_name' => $temporario,
            'name'     => $attachments['filename'],
            'error'    => 0,
            'type'     => mime_content_type($temporario),
            'size'     => filesize($temporario)
        );

//        if ($file['size'] <= 4718592) {
            $type = ($type == 'jpeg') ? 'jpg' : $type;

            if (!in_array($type, $types)) {
                $arquivo_invalido = true;
                system("rm $temporario");
                flushLog("Não será possível enviar, arquivo no formato incorreto. Chamado: $hd_chamado",$fabrica,$fabrica_nome);
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
                    flushLog("Não será possível enviar, erro ao tentar inserir uma nova interação. ".pg_last_error()." Chamado: $hd_chamado",$fabrica,$fabrica_nome);
                }else{
//                    $s3->upload("{$hd_chamado}-{$i}", $file);

                   $anexoID = $tDocs->uploadFileS3($file, $hd_chamado, false, "callcenter");
                   if (!$anexoID) {
                        pg_query($con, 'ROLLBACK');
                        system("rm $temporario");
                        flushLog("Não será possível enviar, erro na função de upload. Chamado: $hd_chamado",$fabrica,$fabrica_nome);
                    } else {


                        pg_query($con, 'COMMIT;');
                        system("rm $temporario");
                        $arq++;
                    }
                }
            }
  //      } else {
    //        system("rm $temporario");
      //      flushLog("Não será possível enviar, arquivo muito pesado. Chamado: $hd_chamado",$fabrica,$fabrica_nome);
        //}
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

$fabricas = array("frigelar" => 198);
foreach ($fabricas as $nome_fabrica => $fabrica) {

    /* VERIFICA SE A ROTINA AINDA ESTA PROCESSANDO */
    $arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

    $count_routine = 0;
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/{$nome_fabrica}\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }
    if ($count_routine > 2) {
        continue;
    }
    /* FIM VERIFICAÇÃO */


    /* PREPARES */
    pg_prepare($con, 'inclui_atendente', "UPDATE tbl_hd_chamado SET atendente = $1 WHERE hd_chamado = $2 AND fabrica = $fabrica");

    pg_prepare($con, 'verifica_atendimento_aberto', "SELECT
                            tbl_hd_chamado_extra.email,
                            tbl_hd_chamado.hd_chamado,
                            tbl_admin.email AS email_admin,
                            tbl_admin.nome_completo
                        FROM tbl_hd_chamado
                            JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                            JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica = {$fabrica}
                        WHERE tbl_hd_chamado.fabrica = {$fabrica}
                            AND tbl_hd_chamado_extra.email = $1
                            AND tbl_hd_chamado.status <> 'Resolvido'");

    pg_prepare($con, 'consulta_admins', "SELECT
                                            tbl_callcenter_email_admin.admin,
                                            tbl_callcenter_email.limite_atendimento
                                        FROM tbl_callcenter_email
                                            JOIN tbl_callcenter_email_admin USING(callcenter_email)
                                            JOIN tbl_admin ON tbl_admin.admin = $1 AND tbl_admin.email = tbl_callcenter_email.email AND tbl_admin.fabrica = {$fabrica}
                                        WHERE tbl_callcenter_email.fabrica = {$fabrica}");

    pg_prepare($con, 'qtde_atend_admin', "SELECT
                                            COUNT(tbl_hd_chamado_extra.hd_chamado) AS qtde_chamado
                                        FROM tbl_hd_origem_admin
                                            JOIN tbl_hd_chamado_origem ON tbl_hd_chamado_origem.hd_chamado_origem = tbl_hd_origem_admin.hd_chamado_origem AND tbl_hd_chamado_origem.fabrica = {$fabrica}
                                            JOIN tbl_admin ON tbl_admin.admin = tbl_hd_origem_admin.admin AND tbl_admin.fabrica = {$fabrica} AND tbl_admin.admin = $1
                                            LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.atendente = tbl_hd_origem_admin.admin AND tbl_hd_chamado.fabrica = {$fabrica} AND lower(tbl_hd_chamado.status) not in ('resolvido', 'cancelado')
    					LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_extra.hd_chamado_origem = tbl_hd_chamado_origem.hd_chamado_origem
                                        WHERE tbl_hd_origem_admin.fabrica = {$fabrica}
                                            AND tbl_admin.ativo IS TRUE
                                            AND tbl_hd_chamado_origem.descricao = 'Email'");

    $fila_distribuicao = array();
    try{
        /* LISTA TODOS OS CHAMADOS SEM ATENDENTES */
        $sql = "SELECT DISTINCT hd_chamado, admin, data
                FROM tbl_hd_chamado WHERE fabrica = {$fabrica} AND atendente IS NULL ORDER BY data, admin";
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
                FROM tbl_callcenter_email JOIN tbl_admin ON(tbl_callcenter_email.callcenter_email = tbl_admin.callcenter_email AND tbl_admin.fabrica = {$fabrica})
                WHERE ativa IS TRUE AND tbl_callcenter_email.fabrica = {$fabrica}";
        $res  = pg_query($con, $sql);
        $rows = pg_num_rows($res);
        if ($rows > 0) {
            for ($i = 0; $i < $rows; $i++) {
                $hostname = '{'.pg_fetch_result($res, $i, 'hostname').'}INBOX';
                $username = pg_fetch_result($res, $i, 'email');
                $password = passwordDecrypt(pg_fetch_result($res, $i, 'senha'));
                $admin    = pg_fetch_result($res, $i, 'admin');

                $imap           = new Imap();
                $imapConnection = $imap->connect($hostname, $username, $password);

                if ($imapConnection != true) {
                    throw new Exception("Erro ao tentar iniciar conexão com o email: $username. Erro: $imapConnection\n");
                }

                $imapEmails     = $imap->getMessages('UNSEEN');

                if (is_array($imapEmails)) {
                    foreach ($imapEmails['data'] as $imapEmail) {                      
                        $infoEmail = [
                            'nome'     => $imapEmail['from']['name'],
                            'email'    => $imapEmail['from']['address'],
                            'assunto'  => $imapEmail['subject'],
                            'mensagem' => $imapEmail['message'],
                            'temAnexo' => (count($imapEmail['attachments']) > 0) ? true : false
                        ];

                        $attachment = [];
                        $message    = $infoEmail['mensagem'];

                        foreach ($imapEmail['attachments'] as $key => $img_name) {
                            if (is_array($img_name)) {
                                $img_name = $img_name['file'];
                            }
                            
                            $path_attach  = "/tmp/" . $img_name;    
                            $imagedata    = file_get_contents($path_attach);
                            $attachment[] = array("filename" => addslashes($img_name), "filedata" => $imagedata);
                        }

                        /* 
                        * RETIRA ASSINATURA DO EMAIL SE POSSUIR 
                        */
                        $assinatura = strpos($message, '--=20');
                        $message    = ($assinatura) ? trim(substr($message, 0, $assinatura)) : $message;
                        $message    = str_replace("'","",$message);
                        $message    = (mb_detect_encoding($message, 'ASCII') === 'ASCII') ? utf8_encode(html_entity_decode($message)) : $message;
                        
                        /*
                        * SE FOR E-MAIL DE RESPOSTA, REMOVE O HISTÓRICO DE RESPOSTA
                        * DEPOIS DE 'escreveu:'
                        * EXEMPLO: 'Em seg., 18 de mai. de 2020 às 15:42, <lucas.bicaleto@telecontrol.com.br> escreveu: ...'
                        */
                        $newMessage = explode("escreveu:", $message);
                        
                        if (!empty($newMessage[0])) {
                            /*
                            * TENTA REMOVER A DATA DE RESPOSTA
                            * QUE VEM DO PROVEDOR DE E-MAIL
                            * EXEMPLO: 'Em seg., 18 de mai. de 2020 às 15:42, <lucas.bicaleto@telecontrol.com.br> escreveu:'
                            */
                            $arrayDiasPT = array("seg","ter","qua","qui","sex","sab","dom");
                            $arrayDiasEN = array("mon","tue","wed","thu","fri","sat","sun");
                            $arrayDias   = array_merge($arrayDiasPT,$arrayDiasEN);

                            foreach ($arrayDias AS $dia) {
                                $preg        = "Em " . $dia . ".";
                                $newMessage2 = strstr($newMessage[0], $preg, true);
                                $message     = (!empty($newMessage2)) ? $newMessage2 : $message;
                            }
                        }

                        /*
                        * SE FOR UM E-MAIL DE NÃO RESPOSTA, CANCELA O ENVIO...
                        */
                        $valida     = strstr($infoEmail['email'], 'noreply');
                        if ($valida == true) {
                            continue;
                        }

                        /*
                        * VERIFICA SE JÁ POSSUI UM ATENDIMENTO ABERTO QUE NÃO ESTA RESOLVIDO 
                        */
                        $res_verifica = pg_execute($con, 'verifica_atendimento_aberto', array($infoEmail['email']));
                        pg_query($con, 'BEGIN TRANSACTION');

                        if (pg_num_rows($res_verifica) > 0) {
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
                                                'Enviado via Email: ".utf8_decode($message)."',
                                                't',
                                                'Aberto',
                                                'f'
                                            )";
                            pg_query($con, $sql_insert);

                            if (strlen(pg_last_error()) > 0) {
                                pg_query($con, 'ROLLBACK TRANSACTION');
                                throw new Exception("Erro ao tentar inserir uma nova interação no atendimento: $hd_chamado. Erro: ".pg_last_error());
                            }

                            $ret_anexo = insere_anexo_atendimento($hd_chamado,$attachment,$fabrica,$nome_fabrica);
                            
                            if (!$ret_anexo) {
                                pg_query($con, 'ROLLBACK TRANSACTION');
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

                            pg_query($con, 'COMMIT TRANSACTION');
                            $imap->setEmailLido($imapEmail['uid']);
                        } else {
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
                                                $fabrica,
                                                $fabrica,
                                                'Atendimento interativo',
                                                'Aberto',
                                                null,
                                                '$data_providencia',
                                                'reclamacao_produto'
                                            )RETURNING hd_chamado";
                            $res_insert = pg_query($con, $sql_insert);

                            if (strlen(pg_last_error()) > 0) {
                                pg_query($con, 'ROLLBACK TRANSACTION');
                                throw new Exception("(1) Erro ao tentar inserir uma novo atendimento do email de origem: ".$infoEmail['email'].". Erro: ".pg_last_error());
                            }

                            $hd_chamado              = pg_result($res_insert, 0, 'hd_chamado');
                            $array_campos_adicionais = array('admin_agendamento' => $admin, 'data_programada'   => date('d/m/Y'));
                            $array_campos_adicionais = json_encode($array_campos_adicionais);

                            $sql_origem = "SELECT hd_chamado_origem FROM tbl_hd_chamado_origem WHERE fabrica = {$fabrica} AND descricao = 'Email' ";
                            $res_origem = pg_query($con, $sql_origem);

                            if (pg_num_rows($res_origem) == 0) {
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
                                                '".$infoEmail['nome']."',
                                                '".$infoEmail['email']."',
                                                'f',
                                                't',
                                                $hd_chamado_origem,
                                                't',
                                                '$array_campos_adicionais',
                                                E'".utf8_decode($message)."'
                                            )";
                            pg_query($con, $sql_insert);

                            if (strlen(pg_last_error()) > 0) {
                                pg_query($con, 'ROLLBACK TRANSACTION');
                                throw new Exception("(2) Erro ao tentar inserir uma novo atendimento do email de origem: ".$infoEmail['email'].". Erro: ".pg_last_error());
                            }

                            $sql_insert = "INSERT INTO tbl_hd_chamado_item (
                                                hd_chamado,
                                                comentario,
                                                interno,
                                                status_item,
                                                atendimento_telefone
                                            )VALUES(
                                                $hd_chamado,
                                                E'Enviado via Email: ".utf8_decode($message)."',
                                                't',
                                                'Aberto',
                                                'f'
                                            )";
                            pg_query($con, $sql_insert);

                            if (strlen(pg_last_error()) > 0) {
                                pg_query($con, 'ROLLBACK TRANSACTION');
                                throw new Exception("Erro ao tentar inserir uma nova interação no atendimento: $hd_chamado. Erro: ".pg_last_error());
                            }


                            $ret_anexo = insere_anexo_atendimento($hd_chamado,$attachment,$fabrica,$nome_fabrica);
                            if (!$ret_anexo) {
                                pg_query($con, 'ROLLBACK TRANSACTION');
                                throw new Exception("Erro ao tentar inserir um novo anexo ao atendimento do email de origem: ".$infoEmail['email'].".");
                            }

                            pg_query($con, 'COMMIT TRANSACTION');
                            $fila_distribuicao[$admin][] = $hd_chamado;
                            $imap->setEmailLido($imapEmail['uid']);
                        }
                        
                    }
                }
            }
        }
    }catch(Exception $e){
        flushLog($e->getMessage(),$fabrica,$fabrica_nome);
        mail("lucas.bicaleto@telecontrol.com.br", "TESTE DE ROTINA", "{$e->getMessage()}", $header);
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
                        flushLog("Erro ao tentar distribuir o chamado $hd_chamado. Erro: ".pg_last_error(),$fabrica,$fabrica_nome);
                    }
                    break;
                }
            }
        }
    }
}

$phpCron->termino();

?>

