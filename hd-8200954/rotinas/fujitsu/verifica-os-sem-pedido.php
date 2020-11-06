<?php
/**
 *
 * verifica-os-sem-pedido
 *
 * Importação de peças Fujitsu
 */
error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';
    include_once dirname(__FILE__) .'/../../class/communicator.class.php';
    // fujitsu
    $fabrica = 138;
    $fabrica_nome = 'fujitsu';

    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    if(ENV == 'teste'){
        $log_dir = dirname(__FILE__) . '/fujitsu_teste/logs';;
        $arq_log = $log_dir . '/relatorio-os-sem-pedido-' . $now . '.log';
        $err_log = $log_dir . '/relatorio-os-sem-pedido-err-' . $now . '.log';
    }else{
        $log_dir = '/tmp/' . $fabrica_nome;
        $arq_log = $log_dir . '/relatorio-os-sem-pedido-' . $now . '.log';
        $err_log = $log_dir . '/relatorio-os-sem-pedido-err-' . $now . '.log';
    }

    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. Falha ao criar diretório: $log_dir");
        }
    }

    // ####################################################
    // INTERVENCAO OS's com intervenção da Fábrica
    // ####################################################
    $sql = "SELECT intervencao.os
              INTO TEMP tmp_intervencao_fabrica
              FROM (
                    SELECT ultima_serie.os, (
                            SELECT status_os
                              FROM tbl_os_status
                             WHERE tbl_os_status.os             = ultima_serie.os
                               AND tbl_os_status.fabrica_status = $fabrica
                               AND status_os IN (13,19,67)
                     ORDER BY os_status DESC LIMIT 1) AS ultimo_serie_status
                      FROM (
                            SELECT DISTINCT os
                              FROM tbl_os_status
                             WHERE tbl_os_status.fabrica_status = $fabrica
                               AND status_os IN (13,19,67)
                      ) ultima_serie) intervencao
             WHERE intervencao.ultimo_serie_status IN (13);";
    $res = pg_query($con, $sql);

    if (pg_last_error()) {
        // $elog = fopen($err_log, "w");
        // $erro = "==============================\n\n";
        // $erro.= $sql . "\n\n";
        // $erro.= pg_last_error();
        // $erro.= "Erro ao montar tmp_intervencao_fabrica";
        // $erro.= "\n\n";
        // fwrite($elog, $erro);
        $msg_erro = "Erro ao montar tmp_intervencao_fabrica";
        $erro["msg"][] = "Erro ao montar tmp_intervencao_fabrica\n";
        $erro["msg"][] = pg_last_error();
        $erro["msg"][] = "\n=================================================\n\n";
    }

    $sql = "SELECT
                DISTINCT tbl_os.os,
                tbl_os.sua_os,
                tbl_os.posto,
                tbl_posto.nome,
                tbl_posto_fabrica.contato_email
                INTO TEMP tmp_postos
            FROM tbl_os
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$fabrica}
            INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$fabrica}
            INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
            INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
            WHERE tbl_os.fabrica = {$fabrica}
            AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')
            AND tbl_servico_realizado.gera_pedido IS TRUE
            AND tbl_servico_realizado.peca_estoque IS NOT TRUE
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.finalizada IS NULL
            AND tbl_os.data_fechamento IS NULL
            AND tbl_peca.produto_acabado IS NOT TRUE
            AND tbl_os_item.pedido IS NULL
            AND tbl_os.os NOT IN(SELECT os FROM tmp_intervencao_fabrica)
            AND tbl_os_item.digitacao_item < current_timestamp - interval '6 days'
            AND tbl_posto_fabrica.posto NOT IN (6359)
            ORDER BY tbl_os.posto";
    $res = pg_query($con, $sql);
    if (pg_last_error($con)) {
        // $erro = "==============================\n\n";
        // $erro.= $sql . "\n\n";
        // $erro.= pg_last_error();
        // $erro.= "Erro ao montar tmp_postos";
        // $erro.= "\n\n";
        // fwrite($elog, $erro);
        $msg_erro = "Erro ao montar tmp_postos";
        $erro["msg"][] = "Erro ao montar tmp_postos\n";
        $erro["msg"][] = pg_last_error();
        $erro["msg"][] = "\n=================================================\n\n";
    }
    if(strlen($msg_erro) == 0){
        $sql = "SELECT DISTINCT posto, contato_email, nome FROM tmp_postos";
        $res = pg_query($con, $sql);

        if (pg_last_error()) {
            // $erro = "==============================\n\n";
            // $erro.= $sql . "\n\n";
            // $erro.= pg_last_error();
            // $erro.= "Erro na consulta posto, contato_email, nome";
            // $erro.= "\n\n";
            // fwrite($elog, $erro);
            $msg_erro = "Erro na consulta posto, contato_email, nome";
            $erro["msg"][] = "Erro na consulta posto, contato_email, nome\n";
            $erro["msg"][] = pg_last_error();
            $erro["msg"][] = "\n=================================================\n\n";

        }

        if(strlen($msg_erro) == 0){
            if(pg_num_rows($res) > 0){
                $nlog = fopen($arq_log, "w");
                $rows = pg_num_rows($res);
                for ($i=0; $i < $rows; $i++) {
                    $posto  = pg_fetch_result($res, $i, 'posto');
                    $email  = pg_fetch_result($res, $i, 'contato_email');
                    $nome   = pg_fetch_result($res, $i, 'nome');

                    $sql_os = "SELECT DISTINCT sua_os from tmp_postos WHERE posto = $posto";
                    $res_os = pg_query($con, $sql_os);

                    if (pg_last_error()) {
                        // $erro = "==============================\n\n";
                        // $erro.= $sql . "\n\n";
                        // $erro.= pg_last_error();
                        // $erro.= "Erro ao selecionar OSs";
                        // $erro.= "\n\n";
                        // fwrite($elog, $erro);
                        $msg_erro = "Erro ao selecionar OSs para posto";
                        $erro["msg"][] = "Erro ao selecionar OSs para posto\n";
                        $erro["msg"][] = pg_last_error();
                        $erro["msg"][] = "\n=================================================\n\n";
                    }
                    if(strlen($msg_erro) == 0){
                        if(pg_num_rows($res_os) > 0){
                            $log = "";
                            $os = pg_fetch_all_columns($res_os);
                            $os = implode(', ', $os);
                            if ($email != "") {
                                $remetente    = "Suporte <suporte@telecontrol.com.br>";
                                $destinatario = $email;
                                $assunto      = "OS que estão sem pedidos \n";
                                $mensagem     = "Prezado(a) {$nome}\n";
                                $mensagem    .="<br /><br />Segue abaixo a relação de OSs que não geraram pedido\n";
                                $mensagem    .="<br /><br />OS: $os\n";
                                $mensagem    .="<br /><br />----------\n";
                                $mensagem    .="<br />Atenciosamente,\n";
                                $mensagem    .="<br />Suporte Telecontrol\n";
                                $mensagem    .="<br />www.telecontrol.com.br\n";
                                $mensagem    .="<br /><b>Esta é uma mensagem automática, não responda este e-mail.</b>\n";
                                $headers="Return-Path: <suporte@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
                                mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
                            }
                        }
                    }
                }
            }

            $sql_geral = "SELECT * FROM tmp_postos";
            $res_geral = pg_query($con, $sql_geral);

            $dados = pg_fetch_all($res_geral);

            foreach ($dados as $key => $value) {
                $log = "OS: ".$value['sua_os']." Posto: ".$value["posto"]." Email: ".$value['contato_email'];
                fwrite($nlog, $log . "\n");
            }

        }
    }

    if (count($erro["msg"]) > 0) {
        $elog = fopen($err_log, "w");
        $error_log = implode("<br />", $erro["msg"]);
        fwrite($elog, $error_log);
        fclose($elog);
    }
    fclose($nlog);

    if (filesize($arq_log) > 0) {

        /* TESTE COMUNICATOR */

            // #$externalId = "fujitsu.telecontrol";
            // #$externalId = "noreply@tc";
            // $externalEmail = "garantia@br.fujitsu-general.com";
            // $email_destino = "guilherme.monteiro@telecontrol.com.br";
            // $from_fabrica   = $externalEmail;
            // $assunto        = ucfirst($fabrica_nome) . utf8_decode(': Relatorio OSs sem pedido ') . date('d/m/Y');

            // $msg_email = "Prezado(a) admin, Segue anexo arquivo de OSs sem pedido...<br/><br/>";

            // $mailTc = new TcComm($externalId);
            // $mailTc->AddAttachment($log_dir . '/relatorio-os-sem-pedido-' . $now . '.log', 'relatorio-os-sem-pedido-' . $now . '.log');
            // $res = $mailTc->sendMail(
            //     $email_destino,
            //     $assunto,
            //     $msg_email,
            //     $from_fabrica
            // );
            // if($res === true){
            //     echo "Foi enviado um email ";exit;
            // }

        /* ---XXXX--- */
        require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

        $assunto = ucfirst($fabrica_nome) . utf8_decode(': Relatorio OSs sem pedido ') . date('d/m/Y');

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->FromName = 'Telecontrol';

        /* ESTA OK ENVIANDO PELO EMAIL TELECONTROL*/
        if (ENV == 'producao') {
            $mail->AddAddress('alan.gregorio@br.fujitsu-general.com');
            $mail->AddAddress('garantia@br.fujitsu-general.com');
        } else {
            $mail->AddAddress(DEV_EMAIL);
        }
        $mail->Subject = $assunto;
        $mail->Body = "Segue anexo arquivo de OSs sem pedido...<br/><br/>";

        $mail->AddAttachment($log_dir . '/relatorio-os-sem-pedido-' . $now . '.log', 'relatorio-os-sem-pedido-' . $now . '.log');

        if (!$mail->Send()) {
            echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
        }
    }

    if (filesize($err_log) > 0) {
        require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

        $assunto = ucfirst($fabrica_nome) . utf8_decode(': Erro: Relatorio OSs sem pedido ') . date('d/m/Y');

        $mail = new PHPMailer();
        $mail->IsHTML(true);
        $mail->FromName = 'Telecontrol';

        /* ESTA OK ENVIANDO PELO EMAIL TELECONTROL*/
        if (ENV == 'producao') {
            $mail->AddAddress('marisa.silvana@telecontrol.com.br');
        } else {
            $mail->AddAddress(DEV_EMAIL);
        }

        $mail->Subject = $assunto;
        $mail->Body = "Segue anexo log de erros da rotina verifica-os-sem-pedido Fujitsu...<br/><br/>";

        $mail->AddAttachment($log_dir . '/relatorio-os-sem-pedido-err-' . $now . '.log', 'relatorio-os-sem-pedido-err-' . $now . '.log');

        if (!$mail->Send()) {
            echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

