<?php

    try{
        require_once dirname(__FILE__).'/../../dbconfig.php';
        require_once dirname(__FILE__).'/../../includes/dbconnect-inc.php';
        require_once dirname(__FILE__).'/../funcoes.php';
        require_once dirname(__FILE__).'/../../class/email/mailer/class.phpmailer.php';

        date_default_timezone_set('America/Sao_Paulo');
        $configuracao = array(
            'login_fabrica'   => 42,
            'arquivo'         => 'faturamento_'.Date('Y').'_'.Date('m').'_'.Date('d').'.txt',
            'caminho_arquivo' => '/www/cgi-bin/makita/entrada',
            'email_contato'   => array('vitor.esposito@telecontrol.com.br', 'helpdesk@telecontrol.com.br'),
            'ENV'             => 'producao'
        );
        extract($configuracao);

        $phpCron = new PHPCron($login_fabrica, __FILE__);
        $phpCron->inicio();

        $caminho_arquivo = (empty($ENV)) ? '/home/vitor/makita' : $caminho_arquivo;

        if (file_exists("{$caminho_arquivo}/{$arquivo}") and (filesize("{$caminho_arquivo}/{$arquivo}") > 0)) {

            pg_prepare($con, 'ConsultaExtrato', 'SELECT extrato FROM tbl_extrato_pagamento WHERE extrato = $1');
            pg_query($con, 'BEGIN');

            $conteudo = file_get_contents("{$caminho_arquivo}/{$arquivo}");
            $conteudo = explode("\n", $conteudo);
            foreach ($conteudo as $linha) {
                if (!empty($linha)) {
                    $Array_linha = array_map(function($val){
                        $val = trim($val);
                        if ($val == '' || (is_string($val) && empty($val)) || (is_string($val) && $val == "''") || (is_numeric($val) && $val == 0)) {
                            return 'NULL';
                        }else{
                            trim($val);
                            if (is_string($val) && !is_numeric($val) && !strpos($val, ','))
                                $val = "'{$val}'";

                            if (strpos($val, ','))
                                $val = str_replace(array('.', ','), array('', '.'), $val);

                            return $val;
                        }
                    }, explode(";",$linha));

                    list (
                        $extrato,
                        $numero_nf_mo,
                        $valor_nf_mo,
                        $acrescimo_nf_mo,
                        $desconto_nf_mo,
                        $numero_nf_peca,
                        $valor_nf_peca,
                        $acrescimo_nf_peca,
                        $desconto_nf_peca,
                        $data_pagamento,
                        $justificativa,
                        $observacao
                    ) = $Array_linha;

                    $res = pg_execute($con, 'ConsultaExtrato', array($extrato));
                    if (pg_num_rows($res) !== 0) {
                        $sql = "UPDATE tbl_extrato_pagamento SET
                                    nf_autorizacao    = $numero_nf_mo,
                                    valor_total       = $valor_nf_mo,
                                    acrescimo         = $acrescimo_nf_mo,
                                    desconto          = $desconto_nf_mo,
                                    nf_peca           = $numero_nf_peca,
                                    valor_nf_peca     = $valor_nf_peca,
                                    acrescimo_nf_peca = $acrescimo_nf_peca,
                                    desconto_nf_peca  = $desconto_nf_peca,
                                    data_pagamento    = $data_pagamento,
                                    justificativa     = $justificativa,
                                    obs               = $observacao
                                WHERE extrato = $extrato";

                        pg_query($con, $sql);
                        if (strlen(pg_last_error()) > 0) {
                            $phpCron->termino();
                            throw new Exception("Ocorreu um erro ao tentar importar o arquivo. Extrato: {$extrato} - Update - Arquivo: {$caminho_arquivo}/{$arquivo}");
                            
                        }
                    }else{
                        $sql = "INSERT INTO tbl_extrato_pagamento(
                                                extrato,
                                                nf_autorizacao,
                                                valor_total,
                                                acrescimo,
                                                desconto,
                                                nf_peca,
                                                valor_nf_peca,
                                                acrescimo_nf_peca,
                                                desconto_nf_peca,
                                                data_pagamento,
                                                justificativa,
                                                obs
                                            )
                                            VALUES(
                                                $extrato,
                                                $numero_nf_mo,
                                                $valor_nf_mo,
                                                $acrescimo_nf_mo,
                                                $desconto_nf_mo,
                                                $numero_nf_peca,
                                                $valor_nf_peca,
                                                $acrescimo_nf_peca,
                                                $desconto_nf_peca,
                                                $data_pagamento,
                                                $justificativa,
                                                $observacao
                                            )";

                        pg_query($con, $sql);
                        if (strlen(pg_last_error()) > 0) {
                            $phpCron->termino();
                            throw new Exception("Ocorreu um erro ao tentar importar o arquivo. Extrato: {$extrato} - Insert - Arquivo: {$caminho_arquivo}/{$arquivo}");
                            
                        }                        
                    }
                }
            }
            pg_query($con, 'COMMIT');
            $phpCron->termino();
        }
    }catch(Exception $err){
        pg_query($con, 'ROLLBACK');
        $body = "ERRO ROTINA MAKITA - Arquivo: importa-extrato-pagamento.php<br/><br/>{$err}";
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->IsHTML();
        $mail->AddReplyTo($email_contato[0], "Suporte Telecontrol");
        $mail->Subject = "Erro ao importar dados do extrato de pagamento ";
        $mail->Body = $body;
        $mail->AddAddress($email_contato[1]);
        $mail->Send();
    }