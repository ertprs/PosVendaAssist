<?php
/**
 *
 * importa-estoque.php
 *
 * Importação de peças einhell
 */

error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');
define('DEV_EMAIL', 'lucas.carlos@telecontrol.com.br');

try {

    #include dirname(__FILE__) . '/../../dbconfig_bc_teste.php';
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/conexao_ftp_einhell.php';

    $local_file = dirname(__FILE__) . '/entrada/item-estoque.txt';
    $server_file = "Telecontrol/Sent Data/item-estoque.txt";
    
    $conn_id = ftp_connect($ftp_server);
    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
    ftp_pasv($conn_id, true);

    ftp_get($conn_id, $local_file, $server_file, FTP_BINARY); 

    ftp_close($conn_id);
    

    // einhell
    $fabrica = 160;
    $fabrica_nome = 'einhell';

    $sql_pega_postoEinhell = "SELECT posto_fabrica FROM tbl_fabrica WHERE fabrica = $fabrica";
    $res_pega_postoEinhell = pg_query($con, $sql_pega_postoEinhell);
    if(pg_num_rows($res_pega_postoEinhell)> 0){
        $posto = pg_fetch_result($res_pega_postoEinhell, 0, posto_fabrica);
    }else{
        $msg_erro .= "Não foi possivel encontrar o posto Einhell ";
        throw new Exception($msg_erro);    
    }


    function strtim($var)
    {
        if (!empty($var)) {
            $var = trim($var);
            $var = str_replace("'", "\'", $var);
        }

        return $var;
    }   
    
    $diretorio_origem =  dirname(__FILE__) . '/entrada/';    
        
    $arquivo_origem = 'item-estoque.txt';

    $ftp = '/tmp/einhell';

    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    $log_dir = '/tmp/' . $fabrica_nome .'/logs';
    $arq_log = $log_dir . '/importa-estoque-' . $now . '.txt';
    $err_log = $log_dir . '/importa-estoque-err-' . $now . '.txt';

    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. Falha ao criar diretório: $log_dir");
        }
    }

    $arquivo = $diretorio_origem . $arquivo_origem;

    $nlog = fopen($arq_log, "w");
    $elog = fopen($err_log, "w");

    if (!file_exists($arquivo)){
        fwrite($elog, "Arquivo não encontrado - $arquivo" . "\n");

    }elseif(filesize($arquivo) == 0) {
        fwrite($elog, "Arquivo vazio - $arquivo" . "\n");

    }else {
        $conteudo = file_get_contents($arquivo);
        $conteudo = explode("\n", $conteudo);

        foreach ($conteudo as $linha) {

            if (!empty($linha)) {
                list ($referencia, $qtde) = explode (";",$linha);
                $original = array($referencia, $qtde);

                $not_null = array($referencia, $qtde);
                foreach ($not_null as $value) {
                    //verifica os que estão com qtde 0(zero);
                    if (strlen(trim($value)) == 0) {
                        array_push($original, 'erro de falta de referencia ou quantidade');
                        $log = implode(";", $original);
                        fwrite($nlog, $log . "\n\n");
                        continue 2;
                    }
                }

                $referencia = strtim($referencia);
                $qtde       = strtim($qtde);

                $sql_peca = "SELECT peca from tbl_peca WHERE referencia = '$referencia' and fabrica = $fabrica";
                $res_peca = pg_query($con, $sql_peca);

                if(pg_num_rows($res_peca)==0){                    
                    $log = "$referencia;$qtde;peca nao encontrada";
                    fwrite($nlog, $log . "\n\n");

                }else{
                    $peca = pg_fetch_result($res_peca, 0, peca);

                    $sql_estoque = "SELECT peca FROM tbl_estoque_posto WHERE peca = $peca and fabrica = $fabrica";
                    $res_estoque = pg_query($con, $sql_estoque);

                    $res = pg_query($con,"BEGIN");

                    if ( pg_num_rows($res_estoque) ==0) {
                        $sql = "INSERT INTO tbl_estoque_posto (
                                       fabrica,
                                       posto,
                                       peca,
                                       qtde
                                    ) VALUES (
                                       $fabrica,
                                       $posto,
                                       $peca,
                                       $qtde
                                    )";

                        $sql2 = "INSERT INTO tbl_estoque_posto_movimento (
                                        fabrica,
                                        posto,
                                        peca,
                                        qtde_entrada,
                                        data
                                    ) 
                                    VALUES (
                                        $fabrica,
                                        $posto,
                                        $peca,
                                        $qtde,
                                        now()
                                    )";
                    } else {
                        $sql = "UPDATE tbl_estoque_posto SET
                                       qtde = $qtde
                                    WHERE peca = $peca";

                        $sql2 = "UPDATE tbl_estoque_posto_movimento SET 
                                        qtde_entrada = $qtde
                                 WHERE peca = $peca";
                    }

                    $query = pg_query($con, $sql);
                    $erro1 = pg_last_error();

                    $query2 = pg_query($con, $sql2);
                    $erro2 = pg_last_error();

                    if(!empty($erro1) or !empty($erro2)){
                        $res = pg_query($con,"ROLLBACK");
                    } else {
                        $res = pg_query($con,"COMMIT");
                    }                    
                    
                   if(!empty($erro1) or !empty($erro2)){
                        array_push($original, 'erro');
                        $log = implode(";", $original);
                        fwrite($nlog, $log . "\n");

                        $erro = "==============================\n\n";
                        $erro.= $sql . "\n\n";
                        $erro.= $sql2 . "\n\n";
                        $erro.= $erro1;
                        $erro.= $erro2;
                        $erro.= "\n\n";
                        fwrite($elog, $erro);
                    } else {
                        array_push($original, 'ok');
                        $log = implode(";", $original);
                        fwrite($nlog, $log . "\n\n");
                    }                    
                }                
            }            
        }
    }

    fclose($nlog);
    fclose($elog);

        if (filesize($arq_log) > 0) {

            require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

            $assunto = ucfirst($fabrica_nome) . utf8_decode(': Importação de Estoque Posto ') . date('d/m/Y');

            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            if (ENV == 'producao') {
                $mail->AddAddress('daniel.pereira@einhell.com', 'luiz.munoz@einhell.com', 'lucas.carlos@telecontrol.com.br');
            } else {
                $mail->AddAddress('lucas.carlos@telecontrol.com.br');
            }

            $mail->Subject = $assunto;
            $mail->Body = "Segue anexo arquivo de estoque importado na rotina...<br/><br/>";
            $mail->AddAttachment("$arq_log");

            if (!$mail->Send()) {
                echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
            } else {
                unlink($log_dir . '/estoque' . $data_arq_enviar . '.txt');
                unlink($log_dir . '/estoque' . $data_arq_enviar . '.zip');
            }
        }

        if (filesize($err_log) > 0) {

            require_once dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

            $assunto = ucfirst($fabrica_nome) . utf8_decode(': Erros na importação de peças ') . date('d/m/Y');

            $mail = new PHPMailer();
            $mail->IsHTML(true);
            $mail->From = 'helpdesk@telecontrol.com.br';
            $mail->FromName = 'Telecontrol';

            if (ENV == 'producao') {
                $mail->AddAddress('helpdesk@telecontrol.com.br', 'lucas.carlos@telecontrol.com.br');
            } else {
                $mail->AddAddress('lucas.carlos@telecontrol.com.br');
            }

            $mail->Subject = $assunto;
            $mail->Body = "Segue anexo log de erro na importação de estoque...<br/><br/>";
            $mail->AddAttachment("$err_log");                

            if (!$mail->Send()) {
                echo 'Erro ao enviar email: ' , $mail->ErrorInfo;
            } else {
                unlink($log_dir . '/importa-estoque-err-' . $now . '.zip');
            }          
        }

        $data_arq_process = date('Ymd-His');
        system("mv $arquivo /tmp/$fabrica_nome/telecontrol-estoque-$data_arq_process.txt");
            

} catch (Exception $e) {
    echo $e->getMessage();
}

