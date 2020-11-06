<?php

try{
    require_once dirname(__FILE__).'/../../dbconfig.php';
    require_once dirname(__FILE__).'/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__).'/../funcoes.php';
    require_once dirname(__FILE__).'/../../class/email/mailer/class.phpmailer.php';

    date_default_timezone_set('America/Sao_Paulo');
    $configuracao = array(
        'data_exportacao' => '2017-07-06',
        'login_fabrica'   => 42,
        'arquivo_bk'      => 'extrato_'.Date('Y').'_'.Date('m').'_'.Date('d').'_bk.txt',
        'arquivo'         => 'extrato_'.Date('Y').'_'.Date('m').'_'.Date('d').'.txt',
        'pasta_bk'        => '/home/makita/telecontrol-makita/bkp',
        'caminho_ftp'     => '/home/makita/telecontrol-makita',
        'caminho_arquivo' => '/tmp/makita',
        'email_contato'   => array('vitor.esposito@telecontrol.com.br', 'helpdesk@telecontrol.com.br'),
        'ENV'             => 'producao'
    );
    extract($configuracao);

    $pasta_bk      = (empty($ENV)) ? '/home/vitor/makita/bkp' : $pasta_bk;
    $caminho_ftp   = (empty($ENV)) ? '/home/vitor/makita' : $caminho_ftp;
    $msg_erro      = "";

    $phpCron = new PHPCron($login_fabrica, __FILE__);
    $phpCron->inicio();

    $sql = "SELECT
                tbl_extrato.extrato      ||';'||
                tbl_extrato.data_geracao ||';'||
                tbl_os.os                ||';'||
                tbl_os.data_abertura     ||';'||
                tbl_os.data_fechamento   ||';'||
                CASE
                    WHEN tbl_peca.referencia IS NULL
                THEN
                    ''
                ELSE
                    tbl_peca.referencia
                END                      ||';'||
                CASE
                    WHEN tbl_os_item.qtde IS NULL
                THEN
                    0
                ELSE
                    tbl_os_item.qtde
                END                      ||';'||
                tbl_os.pecas             ||';'||
                tbl_os.mao_de_obra       ||';'||
                tbl_os.posto             ||';'||
                tbl_produto.referencia   ||';'||
                tbl_os.serie             ||';'||
                tbl_os.nota_fiscal       ||';'||
                tbl_os.data_nf           ||';'||
                tbl_os.consumidor_nome   ||';'||
                tbl_os.consumidor_fone   ||';'||
                tbl_os.consumidor_cidade ||';'||
                tbl_os.consumidor_estado ||';'||
                tbl_os.revenda_nome      ||';'||
                tbl_os.revenda_cnpj      ||';'||
                CASE 
                    WHEN tbl_os.cortesia = 't' 
                THEN 
                    'S' 
                ELSE 
                    'N' 
                END AS linha,
                tbl_extrato.extrato
            FROM tbl_extrato
                JOIN tbl_os_extra ON(tbl_os_extra.extrato = tbl_extrato.extrato)
                JOIN tbl_os ON(tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = {$login_fabrica})
                LEFT JOIN tbl_os_produto ON(tbl_os_produto.os = tbl_os.os)
                LEFT JOIN tbl_os_item ON(tbl_os_item.os_produto = tbl_os_produto.os_produto)
                LEFT JOIN tbl_peca ON(tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica})
                JOIN tbl_produto ON(tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica})
            WHERE tbl_extrato.fabrica = {$login_fabrica}
                AND tbl_extrato.exportado IS NULL 
                AND tbl_extrato.data_geracao >= '{$data_exportacao}' ";

    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        pg_prepare($con, 'atualizaExportado', "UPDATE tbl_extrato SET exportado = current_timestamp WHERE extrato = $1");

        pg_query($con, 'BEGIN');
        $arq = fopen("{$caminho_arquivo}/{$arquivo}", 'w');
        while ($fetch = pg_fetch_assoc($res)) {
            if (empty($fetch['linha'])) { continue; }

            $line = "{$fetch['linha']}\n";
            fwrite($arq, $line);

            pg_execute($con, 'atualizaExportado', array($fetch['extrato']));
        }
        fclose($arq);
        if (filesize("{$caminho_arquivo}/{$arquivo}") > 0) {
            if (!copy("{$caminho_arquivo}/{$arquivo}", "{$caminho_ftp}/{$arquivo}")) {
                $error = error_get_last();
                $msg_erro = "- Erro ao tentar copiar o arquivo para o diretório do FTP. Erro: ".$error['message']."<br/>";
            }            
            if (!copy("{$caminho_arquivo}/{$arquivo}", "{$pasta_bk}/{$arquivo_bk}")) {
                $error = error_get_last();
                $msg_erro .= "- Erro ao tentar copiar o arquivo para o diretório de Backup. Erro: ".$error['message'];
            }

            if (!empty($msg_erro)){
                $phpCron->termino();
                throw new Exception ($msg_erro);
            }

            pg_query($con, 'COMMIT');
            /* Deleta o arquivo da pasta temporária */
            system ("rm {$caminho_arquivo}/{$arquivo}");
        }else{
            throw new Exception ("Não foi possível criar o arquivo de exportação. Caminho: {$caminho_arquivo}/{$arquivo}");
        }
    }

    $phpCron->termino();
}catch(Exception $err){
    pg_query($con, 'ROLLBACK');
    $body = "ERRO ROTINA MAKITA - Arquivo: exporta-dados-extrato.php<br/><br/>{$err}";
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->IsHTML();
    $mail->AddReplyTo($email_contato[0], "Suporte Telecontrol");
    $mail->Subject = "Erro ao exportar dados do extrato ";
    $mail->Body = $body;
    $mail->AddAddress($email_contato[1]);
    $mail->Send();

    /* Deleta o arquivo da pasta temporária */
    system ("rm {$caminho_arquivo}/{$arquivo}");
}
