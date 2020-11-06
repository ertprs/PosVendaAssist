<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios = "financeiro";
include "autentica_admin.php";
include 'funcoes.php';
include_once __DIR__ . '/../class/AuditorLog.php';
include_once S3CLASS;

$logExtratoSql = "SELECT *
                    FROM tbl_extrato
                        LEFT JOIN tbl_extrato_status ON tbl_extrato_status.extrato = tbl_extrato.extrato
                        LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato
                        LEFT JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
                                            WHERE tbl_extrato.fabrica = {$login_fabrica}
                        AND tbl_extrato.extrato = ";

$logOSExtratoSql = "SELECT * FROM tbl_os_extra WHERE i_fabrica = {$login_fabrica} AND extrato = ";

function verificaExtratoExcluido($acao,$extrato = null) {
    global $login_fabrica, $con, $novaTelaOs;

    if ($novaTelaOs && !empty($acao)) {
        switch ($acao) {
            case 'RECUSAR':
                $zera_extrato_devolucao = true;
                break;
            case 'EXCLUIR':
                $zera_extrato_devolucao = true;
                break;
            case 'ACUMULAR':
                $zera_extrato_devolucao = true;
                break;
            default:
                $zera_extrato_devolucao = false;
                break;
        }

        if ($zera_extrato_devolucao) {
            $sql = "SELECT fabrica
                    FROM tbl_extrato
                    WHERE extrato = {$extrato}
                    AND fabrica = 0";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sql = "UPDATE tbl_faturamento_item
                        SET extrato_devolucao = NULL
                        WHERE tbl_faturamento_item.extrato_devolucao = {$extrato}";
                $res = pg_query($con, $sql);

                $sql = "UPDATE tbl_faturamento
                        SET extrato_devolucao = NULL
                        WHERE tbl_faturamento.extrato_devolucao = {$extrato}";
                $res = pg_query($con, $sql);

                $sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = {$extrato}";
                $res = pg_query($con, $sql);

                if (pg_last_error($con)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
    return true;
}

function comunicar_acumula_os($array_os, $extrato){
    global $con, $login_fabrica, $login_fabrica_nome, $login_admin;

    require_once dirname(__FILE__) . '/../class/email/mailer/class.phpmailer.php';

    // Fábricas que utilizam a comunicação
    $fabricas = array(45);

    if(in_array($login_fabrica, $fabricas)) {

        $res = pg_query ($con,"BEGIN TRANSACTION");

        // Busca o nome do admin e o posto
        $sql = "SELECT tbl_admin.nome_completo AS admin_nome,
                       tbl_extrato.posto,
                       tbl_admin.email,
                       to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao
                FROM tbl_extrato
                JOIN tbl_os_status ON tbl_os_status.extrato = tbl_extrato.extrato
                JOIN tbl_admin     ON tbl_admin.admin       = tbl_os_status.admin
                WHERE tbl_extrato.extrato = $extrato AND
                      tbl_admin.admin     = $login_admin
                GROUP BY tbl_extrato.posto,
                         tbl_admin.nome_completo,
                         tbl_extrato.data_geracao,
                         tbl_admin.email";

        $res = pg_query($con,$sql);

        // Recupera os dados
        $admin_nome         = pg_fetch_result($res, 0, admin_nome);
        $admin_email        = pg_fetch_result($res, 0, email);
        $posto              = pg_fetch_result($res, 0, posto);
        $data_geracao       = pg_fetch_result($res, 0, data_geracao);

        // Formata a mensagem e o título
        $mensagem  = "Prezado Posto Autorizado,<br/><br/>";

        if(count($array_os) > 1) {
            $titulo = "OSs acumuladas no extrato $extrato da $login_fabrica_nome";
            $mensagem .= "As Oss a seguir não serão pagas no extrato $extrato de $data_geracao, devido terem sido acumuladas pelo admin $admin_nome:<br/><br/>";
        } else {
            $titulo = "OS acumulada no extrato $extrato da $login_fabrica_nome";
            $mensagem .= "A Os a seguir não será paga no extrato $extrato de $data_geracao, devido ter sido acumulada pelo admin $admin_nome:<br/><br/>";
        }

        foreach ($array_os as $os) {
            $sql = "SELECT tbl_os.sua_os,
                           tbl_os.consumidor_revenda
                    FROM tbl_os
                    WHERE tbl_os.os = $os->os";

            $res = pg_query($con,$sql);

            $sua_os             = pg_fetch_result($res, 0, sua_os);
            $consumidor_revenda = pg_fetch_result($res, 0, consumidor_revenda);

            $mensagem .= "Os: " . ($consumidor_revenda == 'R' ? $sua_os : $os->os) . "<br/>";
            $mensagem .= "Motivo: $os->obs<br/><br/>";
        }

        if(count($array_os) > 1) {
            $mensagem .= "Favor regularizar as OSs para efetuarmos o pagamento no próximo extrato.<br/><br/>";
        } else {
            $mensagem .= "Favor regularizar a OS para efetuarmos o pagamento no próximo extrato.<br/><br/>";
        }

        $mensagem .= "Qualquer dúvida entrar em contato com a $login_fabrica_nome.";

        // Insere o comunicado para o posto
        $sql = "INSERT INTO tbl_comunicado (mensagem,
                                            fabrica,
                                            posto,
                                            obrigatorio_site,
                                            descricao,
                                            ativo)
                VALUES ('$mensagem',
                        $login_fabrica,
                        $posto,
                        true,
                        '$titulo',
                        true)";

        $res      = pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);

        // Se não houve erro, envia o e-mail
        if(!strlen($msg_erro)) {

            // Busca e-mail do posto
            $sql = "SELECT contato_email
                    FROM tbl_posto_fabrica
                    WHERE posto   = $posto and fabrica = $login_fabrica";

            $res      = pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);

            // Se der erro
            if(strlen($msg_erro)) {
                $msg_erro = "Erro ao buscar e-mail do posto.";
            } else if(pg_num_rows($res) != 1) {
                $msg_erro = "Posto não encontrado";
            } else {

                $posto_email = pg_fetch_result($res, 0, 0);

                $mail = new PHPMailer();

                $mail->IsHTML(true);

                $mail->From     = $admin_email;
                $mail->FromName = $admin_email;
                $mail->Subject  = $titulo;
                $mail->Body     = $mensagem;

                $mail->AddAddress($posto_email);

                if (!$mail->Send()) {
                    $msg_erro = 'Erro ao enviar email: ' . $mail->ErrorInfo;
                }
            }
        }

        if(!strlen($msg_erro)) {
            pg_query($con,"COMMIT TRANSACTION");
        } else {
            pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}
if ($_POST["btn_acao"] == "submit") {
    $data_pagamento = $_POST["data_pagamento"];
    $extrato = $_POST["extrato"];

    if (!empty($data_pagamento)){
        list($di, $mi, $yi) = explode("/", $data_pagamento);
        
        if (!checkdate($mi, $di, $yi)) {
            $msg_erro["msg"][]    = "Data Inválida";
            $msg_erro["campos"][] = "data";
        } else {
            $aux_data_pagamento = "{$yi}-{$mi}-{$di}";
            
            $sql = "SELECT extrato_pagamento FROM tbl_extrato_pagamento WHERE extrato = {$extrato}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0){
                $extrato_pagamento = pg_fetch_result($res, 0, "extrato_pagamento");
                $sql_update = "UPDATE tbl_extrato_pagamento SET data_pagamento = '{$aux_data_pagamento}', admin = {$login_admin} WHERE extrato = {$extrato} AND extrato_pagamento = {$extrato_pagamento}";
                $res_update = pg_query($con, $sql_update);
            }else{
                $sql_insert = "INSERT INTO tbl_extrato_pagamento(extrato, data_pagamento, admin)VALUES({$extrato}, '{$data_pagamento}', {$login_admin})";
                $res_insert = pg_query($con, $sql_insert);

                if (strlen(pg_last_error()) > 0){
                    $msg_erro["msg"][] = "Erro ao gravar data de pagamento";
                }
            }   
        }
    }
}

if (isset($_POST["btn_select"]) AND $_POST["btn_select"] == "enviar") {
    
    $select_acao    = $_POST["select_acao"];
    $extrato        = $_POST["extrato"];
    $os             = $_POST["os"];
    $observacao     = $_POST["observacao"];
    
    if (strtolower($select_acao) == "reabrir"){
        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );
        $auditorLogOS = new AuditorLog();
        
        $qtde_os = count($os);
        
        $x_obs = pg_escape_string(trim($observacao));
        
        if (!strlen($x_obs)) {
            $msg_erro["msg"][] = " Informe a observação para continuar";
        }   

        if (!count($msg_erro)){
            for( $k = 0 ; $k < $qtde_os; $k++ ) {
                pg_query($con,"BEGIN");

                $x_os  = trim($os[$k]);
                $filtro = "$extrato AND os = $x_os ";
                $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );
                
                try {
                    $sql = "
                        UPDATE tbl_os SET
                            finalizada         = NULL,
                            data_fechamento    = NULL,
                            mao_de_obra        = 0,
                            qtde_km_calculada  = 0,
                            pecas              = 0,
                            valores_adicionais = 0
                        WHERE fabrica = {$login_fabrica}
                        AND os = {$x_os}";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao reabrir a Ordem de Serviço {$x_os}");
                    }

                    $sql = "INSERT INTO tbl_os_status
                            (os, status_os, observacao, extrato, admin)
                            VALUES
                            ({$x_os}, 14, '{$x_obs}', {$extrato}, {$login_admin})";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao reabrir a Ordem de Serviço {$x_os}");
                    }

                    $sql = "UPDATE tbl_os_extra SET
                                extrato = NULL
                            WHERE os = {$x_os}";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao reabrir a Ordem de Serviço {$x_os}");
                    }
                } catch(Exception $e) {
                    $msg_erro = $e->getMessage();
                }

                if (!strlen(pg_last_error($con))) {
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os RETIRADA DO EXTRATO $extrato - MOTIVO: $x_obs");
                } else {
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }
        }

        if (!count($msg_erro)) {
            pg_query($con, "BEGIN");
            try {
                $sql = "SELECT
                            SUM(tbl_os.mao_de_obra) as total_mo,
                            SUM(tbl_os.qtde_km_calculada) as total_km,
                            SUM(tbl_os.pecas) as total_pecas,
                            SUM(tbl_os.valores_adicionais) as total_adicionais,
                            tbl_extrato.avulso
                        FROM tbl_os
                        INNER JOIN tbl_os_extra USING(os)
                        INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                        WHERE tbl_os_extra.extrato = {$extrato}
                        GROUP BY tbl_extrato.avulso";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao reabrir Ordem de Serviço");
                }

                $total_mo         = pg_fetch_result($res, 0, "total_mo");
                $total_km         = pg_fetch_result($res, 0, "total_km");
                $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
                $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
                $avulso           = pg_fetch_result($res, 0, "avulso");

                if (!strlen($total_mo)) {
                    $total_mo = 0;
                }

                if (!strlen($total_km)) {
                    $total_km = 0;
                }

                if (!strlen($total_pecas)) {
                    $total_pecas = 0;
                }

                if (!strlen($total_adicionais)) {
                    $total_adicionais = 0;
                }

                if (!strlen($avulso)) {
                    $avulso = 0;
                }

                $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

                $sql_ver_os = "SELECT count(1) as qtde_os_em_extrato FROM tbl_os_extra WHERE extrato = $extrato ";
                $res_ver_os = pg_query($con, $sql_ver_os);
                
                if(pg_num_rows($res_ver_os)> 0 ){
                    $qtde_os_em_extrato = pg_fetch_result($res_ver_os, 0, qtde_os_em_extrato);
                    if($qtde_os_em_extrato == 0){
                        $sql_extrato_lancamento = "UPDATE tbl_extrato_lancamento SET extrato = null WHERE extrato = $extrato ";
                        $res_extrato_lancamento = pg_query($con, $sql_extrato_lancamento);
                    }
                }else{
                    $qtde_os_em_extrato = 0;
                }

                if ($total <= 0 && $qtde_os_em_extrato > 0) {
                    throw new Exception("O valor do extrato não pode ser negativo ou 0");
                } else {
                    $sql = "UPDATE tbl_extrato SET
                                total           = {$total},
                                mao_de_obra     = {$total_mo},
                                pecas           = {$total_pecas},
                                deslocamento    = {$total_km},
                                valor_adicional = {$total_adicionais}
                            WHERE extrato = {$extrato}
                            AND fabrica = {$login_fabrica}";
                    $res = pg_query($con, $sql);
                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao reabrir Ordem de Serviço");
                    }
                }
            } catch(Exception $e) {
                $msg_erro = $e->getMessage();
            }
        }

        if (!count($msg_erro)) {
            pg_query($con, "COMMIT");
            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
            #$link = $_COOKIE["link"];
            header ("Location: extrato_consulta_os_new.php?extrato=$extrato");
            exit;
        } else {
            pg_query($con, "ROLLBACK");
        }
    }

    if(strtolower($select_acao) == "recusar"){
        
        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $qtde_os = count($os);
        $x_obs = pg_escape_string(trim($observacao));
        
        $auditorLogOS = new AuditorLog();

        if (!strlen($x_obs)) {
            $msg_erro["msg"][] = " Informe a observação para continuar";
        }

        if (!count($msg_erro)){

            for( $k = 0 ; $k < $qtde_os; $k++ ) {
                $res     = pg_query($con,"BEGIN TRANSACTION");
                $x_os  = trim($os[$k]);
                
                $filtro = "$extrato AND os = $x_os ";
                $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );

                if (!count($msg_erro)) {
                    
                    try {
                        $sql = "UPDATE tbl_os SET
                                    pecas = 0,
                                    qtde_km_calculada = 0,
                                    mao_de_obra = 0,
                                    valores_adicionais = 0
                                WHERE fabrica = {$login_fabrica}
                                AND os = {$x_os}";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao recusar a Ordem de Serviço {$x_os}");
                        }

                        $sql = "INSERT INTO tbl_os_status
                                (os, status_os, observacao, extrato)
                                VALUES
                                ({$x_os}, 13, '{$x_obs}', {$extrato})";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao recusar a Ordem de Serviço {$x_os}");
                        }

                        $sqlPosto = "SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$x_os}";
                        $resPosto = pg_query($con, $sqlPosto);

                        $posto  = pg_fetch_result($resPosto, 0, "posto");
                        $sua_os = pg_fetch_result($resPosto, 0, "sua_os");

                        $sql = "INSERT INTO tbl_comunicado (
                                    fabrica,
                                    posto,
                                    obrigatorio_site,
                                    tipo,
                                    ativo,
                                    descricao,
                                    mensagem
                                ) VALUES (
                                    {$login_fabrica},
                                    {$posto},
                                    true,
                                    'Com. Unico Posto',
                                    true,
                                    'Ordem de Serviço {$sua_os} teve o pagamento reprovado pela fábrica',
                                    '{$x_obs}'
                                )";
                        $res = pg_query($con, $sql);

                        $retira_extrato_do_faturamento = verificaExtratoExcluido('RECUSAR',$extrato);

                        if (!$retira_extrato_do_faturamento) {
                            throw new Exception("Erro ao recusar Ordem de Serviço");
                        }

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao recusar a Ordem de Serviço {$x_os}");
                        }
                    } catch(Exception $e) {
                        $msg_erro["msg"][] = $e->getMessage();
                    }
                    
                    $sql2 = "
                        UPDATE tbl_os_status set admin = $login_admin
                        WHERE extrato = $extrato
                        AND   os      = $x_os
                        AND   os_status in (
                            SELECT os_status 
                            FROM tbl_os_status 
                            WHERE extrato = $extrato 
                            AND os = $x_os 
                            ORDER BY os_status 
                            DESC LIMIT 1
                        )";
                    $res2 = pg_query($con,$sql2);

	            if (strlen(pg_last_error()) > 0) {
               	    	$msg_erro["msg"][] = pg_errormessage($con);
		    }

                    $sql = "SELECT fn_estoque_recusa_os($x_os,$login_fabrica,$login_admin);";
                    $res = pg_query($con,$sql);
                    if (strlen(pg_last_error()) > 0) {
			$msg_erro["msg"][] = pg_errormessage($con);
		    }
                }

                if (!strlen(pg_last_error($con))) {
                    $res = pg_query($con,"COMMIT TRANSACTION");

                    $teste = $auditorLogOS->retornaDadosSelect();
                    $teste2 = $teste->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os RECUSADA DO EXTRATO $extrato - MOTIVO: $x_obs");
                } else {
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }

            $res = pg_query($con,"BEGIN TRANSACTION");
            $sql = "SELECT
                        SUM(tbl_os.mao_de_obra) as total_mo,
                        SUM(tbl_os.qtde_km_calculada) as total_km,
                        SUM(tbl_os.pecas) as total_pecas,
                        SUM(tbl_os.valores_adicionais) as total_adicionais,
                        tbl_extrato.avulso
                    FROM tbl_os
                    INNER JOIN tbl_os_extra USING(os)
                    INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                    WHERE tbl_os_extra.extrato = {$extrato}
                    GROUP BY tbl_extrato.avulso";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $total_mo         = pg_fetch_result($res, 0, "total_mo");
                $total_km         = pg_fetch_result($res, 0, "total_km");
                $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
                $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
                $avulso           = pg_fetch_result($res, 0, "avulso");

                if (!strlen($total_mo)) {
                    $total_mo = 0;
                }

                if (!strlen($total_km)) {
                    $total_km = 0;
                }

                if (!strlen($total_pecas)) {
                    $total_pecas = 0;
                }

                if (!strlen($total_adicionais)) {
                    $total_adicionais = 0;
                }

                if (!strlen($avulso)) {
                    $avulso = 0;
                }

                $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

                $sql = "UPDATE tbl_extrato SET
                            total           = {$total},
                            mao_de_obra     = {$total_mo},
                            pecas           = {$total_pecas},
                            deslocamento    = {$total_km},
                            valor_adicional = {$total_adicionais}
                        WHERE extrato = {$extrato}";
                $res = pg_query($con, $sql);

                if (($total - $avulso) == 0) {
                    $sql = "UPDATE tbl_extrato SET
                            fabrica = 0
                            WHERE extrato = {$extrato};

                            UPDATE tbl_extrato_lancamento SET
                                extrato = null
                            WHERE extrato = $extrato ";
                    $res = pg_query($con, $sql);

                    /*HD - 6356801*/
                    $sql = "UPDATE tbl_faturamento_item
                            SET extrato_devolucao = NULL
                            WHERE tbl_faturamento_item.extrato_devolucao = {$extrato}";
                    $res = pg_query($con, $sql);

                    $sql = "UPDATE tbl_faturamento
                            SET extrato_devolucao = NULL
                            WHERE tbl_faturamento.extrato_devolucao = {$extrato}";
                    $res = pg_query($con, $sql);

                    $sql = "DELETE FROM tbl_extrato_lgr WHERE extrato = {$extrato}";
                    $res = pg_query($con, $sql);
                }

                if (strlen(pg_last_error()) > 0) {
                    $msg_erro["msg"][] = "Erro ao totalizar Extrato $extrato";
                }
            } else {
                $msg_erro["msg"][] = "Erro ao totalizar Extrato $extrato";
            }
            
            try {
                $sql = "SELECT
                            SUM(tbl_os.mao_de_obra) as total_mo,
                            SUM(tbl_os.qtde_km_calculada) as total_km,
                            SUM(tbl_os.pecas) as total_pecas,
                            SUM(tbl_os.valores_adicionais) as total_adicionais,
                            tbl_extrato.avulso
                        FROM tbl_os
                        INNER JOIN tbl_os_extra USING(os)
                        INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                        WHERE tbl_os_extra.extrato = {$extrato}
                        AND tbl_extrato.fabrica = {$login_fabrica}
                        GROUP BY tbl_extrato.avulso";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao recusar Ordem de Serviço");
                }

                $total_mo         = pg_fetch_result($res, 0, "total_mo");
                $total_km         = pg_fetch_result($res, 0, "total_km");
                $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
                $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
                $avulso           = pg_fetch_result($res, 0, "avulso");

                if (!strlen($total_mo)) {
                    $total_mo = 0;
                }

                if (!strlen($total_km)) {
                    $total_km = 0;
                }

                if (!strlen($total_pecas)) {
                    $total_pecas = 0;
                }

                if (!strlen($total_adicionais)) {
                    $total_adicionais = 0;
                }

                if (!strlen($avulso)) {
                    $avulso = 0;
                }

                $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

                $sql = "UPDATE tbl_extrato SET
                            total           = {$total},
                            mao_de_obra     = {$total_mo},
                            pecas           = {$total_pecas},
                            deslocamento    = {$total_km},
                            valor_adicional = {$total_adicionais}
                        WHERE extrato = {$extrato}
                        AND fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao recusar Ordem de Serviço");
                }
            } catch(Exception $e) {
                $msg_erro = $e->getMessage();
            }
          
            if (!count($msg_erro)) {
                $res = pg_query($con,"COMMIT TRANSACTION");
                $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
                #header ("Location: $link?msg_aviso=$msg_aviso&extrato=$extrato");
                header ("Location: extrato_consulta_os_new.php?extrato=$extrato");
                exit;
            }else{
                $res = pg_query($con,"ROLLBACK TRANSACTION");
            }
        }
    }

    if (strtolower($select_acao) == "excluir"){

        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $auditorLogOS = new AuditorLog();

        $qtde_os = count($os);
        $x_obs = pg_escape_string(trim($observacao));

        if (!strlen($x_obs)) {
            $msg_erro["msg"][] = " Informe a observação para continuar";
        }

        if (!count($msg_erro)){
            for( $k = 0 ; $k < $qtde_os; $k++ ){
                $res          = pg_query($con,"BEGIN TRANSACTION");
                $x_os  = trim($os[$k]);
                
                $filtro = "$extrato AND os = $x_os ";
                $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );

                $sql =  "INSERT INTO tbl_os_status (
                                extrato    ,
                                os         ,
                                observacao ,
                                status_os  ,
                                admin
                            ) VALUES (
                                $extrato ,
                                $x_os    ,
                                '$x_obs' ,
                                15       ,
                                $login_admin
                            );";
                $res = pg_query($con,$sql);
                
                if (strlen(pg_last_error()) > 0){
                    $msg_erro["msg"][] = "Erro ao atualizar status da ordem de serviço";
                }
                
                if (!count($msg_erro)) {
                        $sql = "UPDATE tbl_os_extra SET extrato = null
                                FROM tbl_extrato_extra, tbl_extrato, tbl_os
                                WHERE  tbl_os_extra.os      = $x_os
                                AND    tbl_os_extra.extrato = $extrato
                                AND    tbl_os_extra.os      = tbl_os.os
                                AND    tbl_os_extra.extrato = tbl_extrato.extrato
                                AND    tbl_extrato.extrato  = tbl_extrato_extra.extrato
                                AND    tbl_extrato_extra.baixado IS NULL
                                AND    tbl_os.fabrica  = $login_fabrica";
                    $res = pg_query($con,$sql);
                    
                    if (strlen(pg_last_error()) > 0){
                        $msg_erro["msg"][] = "Erro ao remover extrato da ordem de serviço";
                    }
                }
                
                if(!count($msg_erro)){
                    $sql = "UPDATE tbl_os SET excluida = true
                                WHERE  tbl_os.os           = $x_os
                                AND    tbl_os.fabrica      = $login_fabrica;";
                    $res = @pg_query($con,$sql);
                    
                    if (strlen(pg_last_error()) > 0){
                        $msg_erro["msg"][] = "Erro ao excluir ordem de serviço";
                    }

                    $sql = "SELECT fn_os_excluida_reincidente($x_os,$login_fabrica)";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0){
                        $msg_erro["msg"][] = "Erro ao excluir ordem de serviço";
                    }
                }

                if (!count($msg_erro)) {
                    $res = pg_query($con,"COMMIT TRANSACTION");
                    $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os EXCLUIDA DO EXTRATO $extrato - MOTIVO: $x_obs");
                } else {
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }
        }
        pg_query($con,"BEGIN TRANSACTION");

        if (!count($msg_erro)) {
            $sql = "SELECT posto
                    FROM   tbl_extrato
                    WHERE  extrato = $extrato
                    AND    fabrica = $login_fabrica ;";
            $res = @pg_query($con, $sql);
            
            if (strlen(pg_last_error()) > 0){
                $msg_erro["msg"][] = "Erro inesperado #1";
            }

            if (pg_fetch_result($res,0,posto) > 0 AND !count($msg_erro)){

                try {
                    $sql = "SELECT
                                SUM(tbl_os.mao_de_obra) as total_mo,
                                SUM(tbl_os.qtde_km_calculada) as total_km,
                                SUM(tbl_os.pecas) as total_pecas,
                                SUM(tbl_os.valores_adicionais) as total_adicionais,
                                tbl_extrato.avulso
                            FROM tbl_os
                            INNER JOIN tbl_os_extra USING(os)
                            INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                            WHERE tbl_os_extra.extrato = {$extrato}
                            AND tbl_extrato.fabrica = {$login_fabrica}
                            GROUP BY tbl_extrato.avulso";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao excluir Ordem de Serviço");
                    }

                    $total_mo         = pg_fetch_result($res, 0, "total_mo");
                    $total_km         = pg_fetch_result($res, 0, "total_km");
                    $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
                    $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
                    $avulso           = pg_fetch_result($res, 0, "avulso");

                    if (!strlen($total_mo)) {
                        $total_mo = 0;
                    }

                    if (!strlen($total_km)) {
                        $total_km = 0;
                    }

                    if (!strlen($total_pecas)) {
                        $total_pecas = 0;
                    }

                    if (!strlen($total_adicionais)) {
                        $total_adicionais = 0;
                    }

                    if (!strlen($avulso)) {
                        $avulso = 0;
                    }

                    $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;

                    $sql = "UPDATE tbl_extrato SET
                                total           = {$total},
                                mao_de_obra     = {$total_mo},
                                pecas           = {$total_pecas},
                                deslocamento    = {$total_km},
                                valor_adicional = {$total_adicionais}
                            WHERE extrato = {$extrato}
                            AND fabrica = {$login_fabrica}";
                    $res = pg_query($con, $sql);

                    if (($total - $avulso) == 0) {
                        $sql = "UPDATE tbl_extrato SET
                                fabrica = 0
                                WHERE extrato = {$extrato};

                                UPDATE tbl_extrato_lancamento SET
                                    extrato = null
                                WHERE extrato = $extrato ";
                        $res = pg_query($con, $sql);
                    }

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao excluir Ordem de Serviço");
                    }

                    $retira_extrato_do_faturamento = verificaExtratoExcluido('EXCLUIR',$extrato);

                    if (!$retira_extrato_do_faturamento) {
                        throw new Exception("Erro ao excluir Ordem de Serviço");
                    }

                } catch(Exception $e) {
                    $msg_erro = $e->getMessage();
                }
            }
        }

        if (!count($msg_erro)) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
            $link = $_COOKIE["link"];
            #header ("Location: $link?msg_aviso=$msg_aviso&extrato=$extrato");
            header ("Location: extrato_consulta_os_new.php?extrato=$extrato");
            exit;
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }

    if(strtolower($select_acao) == "acumular" ){
        
        $auditorLog = new AuditorLog();
        $auditorLog->retornaDadosSelect( $logExtratoSql.$extrato );

        $auditorLogOS = new AuditorLog();
        $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$extrato );

        $qtde_os = count($os);
        $x_obs = pg_escape_string(trim($observacao));
        $array_os = array();

        $sql = "SELECT * FROM tbl_extrato_financeiro WHERE extrato = {$extrato}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $msg_erro["msg"][] = "Extrato {$extrato} já enviado para o Financeiro";
        }
        
        if (!strlen($x_obs)) {
            $msg_erro["msg"][] = " Informe a observação para continuar";
        }

        if (!count($msg_erro)) {
            for( $k=0; $k < $qtde_os; $k++ ){
                $res = pg_query($con,"BEGIN TRANSACTION");
                $x_os  = trim($os[$k]);
                
                $filtro = "$extrato AND os = $x_os ";
                $auditorLogOS->retornaDadosSelect( $logOSExtratoSql.$filtro );

                $array_os[] = (object)array("os" => $x_os, "obs" => $x_obs);

                if (!count($msg_erro)) {
                    try {
                        $sql = "INSERT INTO tbl_os_status
                                (os, status_os, observacao, extrato, admin)
                                VALUES
                                ({$x_os}, 14, '{$x_obs}', {$extrato}, {$login_admin})";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao acumular a ordem de serviço {$x_os}");
                        }

                        $sql = "UPDATE tbl_os_extra SET extrato = NULL WHERE os = {$x_os}";
                        $res = pg_query($con, $sql);

                        $retira_extrato_do_faturamento = verificaExtratoExcluido('ACUMULAR',$extrato);

                        if (!$retira_extrato_do_faturamento) {
                            throw new Exception("Erro ao acumular Ordem de Serviço");
                        }

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao acumular a ordem de serviço {$x_os}");
                        }
                    } catch(Exception $e) {
                        $msg_erro = $e->getMessage();
                    }
                }

                if (!strlen(pg_last_error($con))) {
                    $res = pg_query($con,"COMMIT TRANSACTION");

                    $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato, null, "OS $x_os ACUMULADA PARA O PR<D3>XIMO EXTRATO - MOTIVO: $x_obs");
                } else {
                    $res = pg_query($con,"ROLLBACK TRANSACTION");
                }
            }
        }

        $res = pg_query($con,"BEGIN TRANSACTION");

        if (!count($msg_erro)) {
            comunicar_acumula_os($array_os, $extrato, $login_fabrica);
        }

        if(!count($msg_erro)){
         
            $sql = "SELECT posto
                    FROM   tbl_extrato
                    WHERE  extrato = $extrato
                    AND    fabrica = $login_fabrica";
            $res = pg_query($con,$sql);
            $msg_erro["msg"][] = pg_errormessage($con);

            if( pg_num_rows($res) > 0 ){
                if( pg_fetch_result($res, 0, posto) > 0 AND !count($msg_erro)){
                    try {
                        $sql = "SELECT
                                    SUM(tbl_os.mao_de_obra) as total_mo,
                                    SUM(tbl_os.qtde_km_calculada) as total_km,
                                    SUM(tbl_os.pecas) as total_pecas,
                                    SUM(tbl_os.valores_adicionais) as total_adicionais,
                                    tbl_extrato.avulso
                                FROM tbl_os
                                INNER JOIN tbl_os_extra USING(os)
                                INNER JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                                WHERE tbl_os_extra.extrato = {$extrato}
                                AND tbl_extrato.fabrica = {$login_fabrica}
                                GROUP BY tbl_extrato.avulso";
                        $res = pg_query($con, $sql);
                        $total_os = pg_num_rows($res) ;
                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao acumular Ordem de Serviço");
                        }
                        $total_mo         = pg_fetch_result($res, 0, "total_mo");
                        $total_km         = pg_fetch_result($res, 0, "total_km");
                        $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
                        $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
                        $avulso           = pg_fetch_result($res, 0, "avulso");

                        if (!strlen($total_mo)) {
                            $total_mo = 0;
                        }

                        if (!strlen($total_km)) {
                            $total_km = 0;
                        }

                        if (!strlen($total_pecas)) {
                            $total_pecas = 0;
                        }

                        if (!strlen($total_adicionais)) {
                            $total_adicionais = 0;
                        }

                        if (!strlen($avulso)) {
                            $avulso = 0;
                        }

                        $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso ;


                        if ($total <= 0 and $total_os > 0 ) {
                            throw new Exception("O valor do extrato não pode ser negativo ou 0");
                        } else {
                            $sql = "UPDATE tbl_extrato SET
                                        total           = {$total},
                                        mao_de_obra     = {$total_mo},
                                        pecas           = {$total_pecas},
                                        deslocamento    = {$total_km},
                                        valor_adicional = {$total_adicionais}
                                    WHERE extrato = {$extrato}";
                            $res = pg_query($con, $sql);

                            if ($total_os == 0) {
                                $sql = "UPDATE tbl_extrato SET
                                        fabrica = 0
                                        WHERE extrato = {$extrato};

                                        UPDATE tbl_extrato_lancamento SET
                                            extrato = null
                                        WHERE extrato = $extrato ";
                                $res = pg_query($con, $sql);
                            }
                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao totalizar Extrato $extrato");
                            }
                        }
                    } catch (Exception $e) {
                        $msg_erro = $e->getMessage();
                    }
                }
            }
        }

        if (!count($msg_erro)) {
            $res = pg_query($con,"COMMIT TRANSACTION");
            $auditorLog->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os", $login_fabrica."*".$extrato);
            $auditorLogOS->retornaDadosSelect()->enviarLog('update', "tbl_extrato_consulta_os_extra", $login_fabrica."*".$extrato);

            $link = $_COOKIE["link"];
            #header ("Location: $link?msg_aviso=$msg_aviso");
            header ("Location: extrato_consulta_os_new.php?extrato=$extrato");
            exit;
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
        }
    }
}

$layout_menu = "financeiro";
$title = "Relação de Ordens de Serviços ";
include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable"
);

include("plugin_loader.php");

$extrato = $_REQUEST['extrato'];

$s3_extrato = new AmazonTC("extrato", (int) $login_fabrica);

$sql2 = "
    SELECT
        tbl_os_revenda.os_revenda,
        tbl_os_revenda.qtde_km AS qtde_km_os,
        tbl_extrato.extrato,
        tbl_extrato.liberado,
        tbl_extrato.total AS total_extrato,
        TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao ,
        TO_CHAR(tbl_os_revenda.data_abertura, 'DD/MM/YYYY') AS data_abertura,
        tbl_os_revenda.consumidor_nome,
        tbl_os.qtde_km_calculada AS qtde_km_calculada,
        tbl_posto.nome AS nome_posto ,
        tbl_posto_fabrica.codigo_posto AS codigo_posto ,
        (
            SELECT COUNT(*) 
            FROM tbl_os 
            JOIN tbl_os_extra oe USING(os)
            JOIN tbl_os_campo_extra oce ON oce.os = tbl_os.os
            WHERE oe.extrato = tbl_extrato.extrato 
        ) AS qtde_os_extrato,
        (
            SELECT COUNT(*) 
            FROM tbl_os 
            JOIN tbl_os_extra oe USING(os)
            JOIN tbl_os_campo_extra oce ON oce.os = tbl_os.os AND oce.os_revenda = tbl_os_revenda.os_revenda
            WHERE oe.extrato = tbl_extrato.extrato 
        ) AS qtde_produto,
        (
            SELECT SUM(tbl_os.mao_de_obra) 
            FROM tbl_os 
            JOIN tbl_os_extra oe USING(os)
            JOIN tbl_os_campo_extra oce ON oce.os = tbl_os.os AND oce.os_revenda = tbl_os_revenda.os_revenda 
            WHERE oe.extrato = tbl_extrato.extrato 
            AND (tbl_os.qtde_visitas IS NULL OR tbl_os.qtde_visitas = 0)
        ) AS revisoes,
        (
            SELECT SUM(tbl_os.mao_de_obra) 
            FROM tbl_os 
            JOIN tbl_os_extra oe USING(os) 
            JOIN tbl_os_campo_extra oce ON oce.os = tbl_os.os AND oce.os_revenda = tbl_os_revenda.os_revenda 
            WHERE oe.extrato = tbl_extrato.extrato AND tbl_os.qtde_visitas > 0
        ) AS valor_visita,
        (
            SELECT SUM (case when tbl_os.valores_adicionais > 0 then tbl_os.valores_adicionais else 0 end + case when tbl_os.qtde_km_calculada > 0 then tbl_os.qtde_km_calculada else 0 end + tbl_os.mao_de_obra)
            FROM tbl_os 
            JOIN tbl_os_extra oe USING(os) 
            JOIN tbl_os_campo_extra oce ON oce.os = tbl_os.os AND oce.os_revenda = tbl_os_revenda.os_revenda 
            WHERE oe.extrato = tbl_extrato.extrato
        ) AS total_mo,
        (
            SELECT SUM (tbl_os.valores_adicionais)
            FROM tbl_os 
            JOIN tbl_os_extra oe USING(os) 
            JOIN tbl_os_campo_extra oce ON oce.os = tbl_os.os AND oce.os_revenda = tbl_os_revenda.os_revenda 
            WHERE oe.extrato = tbl_extrato.extrato
        ) AS valores_adicionais
    FROM tbl_os_revenda
    JOIN tbl_os_campo_extra USING(os_revenda,fabrica)
    JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = {$login_fabrica}
    JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
    JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato AND tbl_extrato.fabrica = {$login_fabrica}
    JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    WHERE tbl_os_revenda.fabrica = {$login_fabrica}
    AND tbl_extrato.extrato = {$extrato}
    GROUP BY tbl_os_revenda.os_revenda, tbl_os_revenda.qtde_km, tbl_extrato.extrato, tbl_os.valores_adicionais, tbl_os.qtde_km_calculada, tbl_posto.nome, tbl_posto_fabrica.codigo_posto";
$res2 = pg_query($con, $sql2);

$info_os_extrato = array();
foreach (pg_fetch_all($res2) as $key => $value) {
    $sql = "
        SELECT DISTINCT
            tbl_os.os ,
            tbl_os.sua_os ,
            tbl_os.consumidor_revenda ,
            tbl_os.revenda_nome ,
            tbl_produto.produto ,
            tbl_produto.referencia ,
            tbl_produto.descricao ,
            tbl_os.qtde_visitas ,
            tbl_os.valores_adicionais ,
            tbl_os_extra.os_reincidente ,
            tbl_os.mao_de_obra AS total_mo ,
            tbl_extrato.pecas AS pecas ,
            tbl_extrato.deslocamento AS total_km ,
            tbl_extrato.admin AS admin_aprovou ,
            tbl_extrato.recalculo_pendente ,
            to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento ,
            to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf ,
            (
                (DATE_PART('year', tbl_os.data_abertura) - DATE_PART('year', tbl_os.data_nf)) * 12 +
                (DATE_PART('month', tbl_os.data_abertura) - DATE_PART('month', tbl_os.data_nf))
            ) AS qtde_mes 
        FROM tbl_extrato
        JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
        JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = {$login_fabrica}
        JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}
        JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda AND tbl_os_revenda.fabrica = {$login_fabrica}
        JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
        WHERE tbl_os_extra.extrato = {$extrato} 
        AND tbl_os_revenda.os_revenda = {$value["os_revenda"]}";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0){
        while ($result = pg_fetch_object($res)) {
            unset($total_geral2);
            unset($valor_mo2);
            unset($revisoes2);
            unset($visitas2);

            if (empty($result->qtde_visitas)){
                $revisoes2 += $result->total_mo;
            }

            if (!empty($result->qtde_visitas)){
                $visitas2 = $result->total_mo;
            }

            $valor_mo = ($revisoes+$visitas);
            $total_geral = $valor_mo+$result->valores_adicionais;

            $valor_mo2 = ($revisoes2+$visitas2);
            $total_geral2 = $valor_mo2+$result->valores_adicionais;

            $info_extrato = array(
                "extrato" => $value["extrato"],
                "data_geracao" => $value["data_geracao"],
                "qtde_os_extrato" => $value["qtde_os_extrato"],
                "nome_posto" => $value["nome_posto"],
                "codigo_posto" => $value["codigo_posto"],
                "liberado" => $value["liberado"],
                "total_extrato" => $value["total_extrato"]
            );

            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["os_revenda"] =  $value["os_revenda"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["data_abertura"] = $value["data_abertura"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["consumidor"] = $value["consumidor_nome"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["revisoes"] = $value["revisoes"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["visitas"] = $value["valor_visita"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["qtde_km"] = $value["qtde_km_os"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["valor_adicional"] = $value["valores_adicionais"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["total_mo"] = $value["total_mo"];
            $info_os_extrato[$value["os_revenda"]]["cabecalho"]["qtde_produto"] = $value["qtde_produto"];
            
            if (strlen(trim($value["qtde_km_calculada"])) > 0){
                $info_os_extrato[$value["os_revenda"]]["cabecalho"]["qtde_km_calculada"] = $value["qtde_km_calculada"];
            }
            
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["os"] = $result->os;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["sua_os"] = $result->sua_os;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["data_abertura"] = $result->abertura;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["consumidor"] = $result->consumidor_nome;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["produto_referencia"] = $result->referencia;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["produto_descricao"] = $result->descricao;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["valor_mo"] = $result->total_mo;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["valor_adicional"] = $result->valor_adicional;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["qtde_km_calculada"] = $result->qtde_km_calculada;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["valor_adicional"] = $result->valores_adicionais;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["total_mo"] = $total_geral2;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["os_reincidente"] = $result->os_reincidente;
            $info_os_extrato[$value["os_revenda"]]["itens"][$result->sua_os]["qtde_mes"] = $result->qtde_mes;
        }
    }
}

$sql_avulso = "
    SELECT 
        tbl_extrato_lancamento.extrato_lancamento,
        tbl_lancamento.descricao,
        tbl_extrato_lancamento.historico,
        tbl_extrato_lancamento.valor,
        tbl_extrato_lancamento.automatico
    FROM tbl_extrato_lancamento
    JOIN tbl_lancamento USING (lancamento)
    WHERE tbl_extrato_lancamento.extrato = $extrato
    AND tbl_lancamento.fabrica = $login_fabrica
    ORDER BY tbl_extrato_lancamento.os_sedex, tbl_extrato_lancamento.descricao, tbl_extrato_lancamento.extrato_lancamento";
$res_avulso = pg_query($con, $sql_avulso);

if (pg_num_rows($res_avulso) > 0){
    $dados_avulso = pg_fetch_all($res_avulso);
}
#echo "<pre>".print_r($info_os_extrato[$value["os_revenda"]]["cabecalho"],1)."</pre>";exit;
?>
<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<table class='table table-bordered table-fixed' >
    <thead>
        <tr>
            <th class="tal titulo_coluna">Extrato: <?=$info_extrato["extrato"]?></th>
            <th class="tal titulo_coluna">Data Geração: <?=$info_extrato["data_geracao"]?></th>
            <th class="tal titulo_coluna">Qtde de OS: <?=$info_extrato["qtde_os_extrato"]?></th>
            <th class="tal titulo_coluna">Total: <?=number_format($info_extrato["total_extrato"],2,',','.')?></th>
        </tr>
        <tr>
            <th class="tal titulo_coluna">Código posto: <?=$info_extrato["codigo_posto"]?></th>
            <th colspan="4" class="tal titulo_coluna">Posto: <?=$info_extrato["nome_posto"]?></th>
        </tr>
    </thead>
</table>
<br/>
<?php if (count($dados_avulso)){ ?>
<table class='table table-bordered table-fixed'>
    <thead>
        <tr>
            <th colspan="4" class="titulo_tabela">LANÇAMENTO DE EXTRATO AVULSO</th>
        </tr>
        <tr>
            <th class="titulo_coluna">Descrição</th>
            <th class="titulo_coluna">Histórico</th>
            <th class="titulo_coluna">Valor</th>
            <th class="titulo_coluna">Automático</th>
        </tr>
    </thead>
    <tbody>
        <?php 
            foreach ($dados_avulso as $key => $value) { 
                if ($value["automatico"] == "t"){
                    $value_automatico = "Sim";
                }else{
                    $value_automatico = "Não";
                }
        ?>
            <tr>
                <td><?=$value["descricao"]?></td>
                <td><?=$value["historico"]?></td>
                <td><?=$value["valor"]?></td>
                <td><?=$value_automatico?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
<?php } ?>
<?php
$nota_fiscal_servico = $s3_extrato->getObjectList($extrato."-nota_fiscal_servico.");
if(count($nota_fiscal_servico) > 0){
    $nota_fiscal_servico = basename($nota_fiscal_servico[0]);
?>
    <br/>
    <table class='table table-striped table-bordered table-fixed' >
        <thead>
            <th class="titulo_tabela">Nota Fiscal de Serviço</th>
        </thead>
        <tbody>
            <tr>
                <td align="center" >
                    <table class="table table-bordered">
                        <tr>
                        <?php
                            $anexos = $s3_extrato->getObjectList("{$extrato}-", false);
                            $cont = 1;
                            if(count($anexos)>0){
                                foreach($anexos as $anexo){
                                    $dados = $s3_extrato->getFileInfo($anexo);
                                    $ext = preg_replace("/.+\./", "", $anexo);
                                    $nome_arquivo = basename($anexo);

                                    if(!in_array($ext, array("pdf", "doc", "docx"))){
                                        $thumb_nota_fiscal_servico = $s3_extrato->getLink("thumb_".$nome_arquivo);
                                        if(strlen(trim($thumb_nota_fiscal_servico))==0){
                                            $nome_arquivo = "$extrato-nota_fiscal_servico". ".$ext";;
                                            $thumb_nota_fiscal_servico = $s3_extrato->getLink("thumb_".$nome_arquivo);
                                        }
                                    }else{
                                        switch ($ext) {
                                            case 'pdf':
                                                $thumb_nota_fiscal_servico = 'imagens/pdf_icone.png';
                                                break;
                                            case 'doc':
                                            case 'docx':
                                                $thumb_nota_fiscal_servico = 'imagens/docx_icone.png';
                                                break;
                                        }
                                    }
                                    $nota_fiscal_servico = $s3_extrato->getLink($nome_arquivo);

                                    ?>
                                        <td class='anexos'>
                                            <b><?=substr($dados['LastModified'],0,16)?></b> <br>
                                            <a href="<?=$nota_fiscal_servico?>" target='_blank'><img src="<?=$thumb_nota_fiscal_servico?>" style="border:1px solid; margin:5px;" /></a>
                                        </td>
                                    <?php
                                    $cont++;
                                }
                            }
                            ?>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
<?php
}else{
?>
    <div class="row-fluid tac">
        <br />
        <a rel='shadowbox; width= 550; height= 250;' class="btn-anexar-nf" href="upload_nf_servico_extrato.php?extrato=<?=$extrato;?>">
            <button class="btn btn-primary">Anexar Nota Fiscal de Serviço</button>
        </a><br /><br />
    </div>
<?php    
}
?>
<br/>
<div class="row-fluid">
    <div class="span6">
        <span style="background-color: #FFCCCC; padding-left: 14px; padding-top: 1px; padding-bottom: 1px;">&nbsp;</span> <b>REINCIDÊNCIAS</b>
        <br/>
        <span style="background-color: #D7FFE1; padding-left: 14px; padding-top: 1px; padding-bottom: 1px;">&nbsp;</span> <b>OS MAIOR QUE 12 MESES</b>
        <br/>
        <span style="background-color: #7eb8ff; padding-left: 14px; padding-top: 1px; padding-bottom: 1px;">&nbsp;</span> <b>VISITA PAGA EXTRATO ANTERIOR</b>
    </div>
</div>
<form name='frm_extrato' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline'>
    <input type="hidden" name="extrato" value="<?=$info_extrato["extrato"]?>">
    <table class='table table-bordered table-fixed'>
        <thead>
            <tr>
                <th colspan="11" class="titulo_tabela">ORDENS DE SERVIÇO</th>
            </tr>
            <tr>
                <th class="titulo_coluna"></th>
                <th class="titulo_coluna">OS Principal</th>
                <th class="titulo_coluna">Abertura</th>
                <th class="titulo_coluna">Consumidor</th>
                <th class="titulo_coluna">Qtde Produto</th>
                <th class="titulo_coluna">Revisões</th>
                <th class="titulo_coluna">Visitas</th>
                <th class="titulo_coluna">Qtde KM</th>
                <th class="titulo_coluna">Valor KM</th>
                <th class="titulo_coluna">Valor Adicional</th>
                <th class="titulo_coluna">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
            foreach ($info_os_extrato as $key => $value) {
                $iconMostrar = (!empty($checked_ativo)) ? "icon-minus" : "icon-plus";

                if (in_array($value["cabecalho"]["os_revenda"], $_POST["os_revenda"])){
                    $iconMostrar = "icon-minus";
                    $checked = "checked";
                    $display = "";
                }else{
                    $checked = "";
                    $iconMostrar = "icon-plus";
                    $display = "style='display:none;'";
                }

            ?>
                <tr style="background: #eee">
                    <td><i class="<?=$iconMostrar?> toogle-os" rel="<?=$value["cabecalho"]["os_revenda"]?>"></i></td>
                    <td class="tac"><a href='os_revenda_press.php?os_revenda=<?=$value["cabecalho"]["os_revenda"]?>' target="_blank"><?=$value["cabecalho"]["os_revenda"]?></a></td>
                    <td><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Data Abertura"><?=$value["cabecalho"]["data_abertura"]?></span></td>    
                    <td><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Consumidor"><?=$value["cabecalho"]["consumidor"]?></span></td>
                    <td class="tac"><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Qtde Produto"><?=$value["cabecalho"]["qtde_produto"]?></span></td>
                    <td class="tac"><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Revisões"><?=number_format($value["cabecalho"]["revisoes"],2,',','.')?></span></td>
                    <td class="tac"><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Visitas"><?=number_format($value["cabecalho"]["visitas"],2,',','.')?></span></td>
                    <td class="tac"><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Qtde KM"><?=$value["cabecalho"]["qtde_km"]?></span></td>    
                    <td class="tac"><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Valor KM"><?=number_format($value["cabecalho"]["qtde_km_calculada"],2,',','.')?></td></span></td>
                    <td class="tac"><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Valor Adicional"><?=number_format($value["cabecalho"]["valor_adicional"],2,',','.')?></span></td>
                    <td class="tac"><span data-toggle="tooltip" data-placement="right" title="" data-original-title="Total"><?=number_format($value["cabecalho"]["total_mo"],2,',','.')?><span></td>
                </tr>
                <tr rel="pai_<?=$value["cabecalho"]["os_revenda"]?>" <?=$display?> >
                    <td colspan="11">
                        <table class='table table-bordered table-fixed'>
                            <thead>
                                <th class="titulo_coluna">
                                    <input type="checkbox" class="check_pai" data-check_revenda="<?=$value["cabecalho"]["os_revenda"]?>">
                                </th>
                                <th class="titulo_coluna">OS</th>
                                <th class="titulo_coluna">Produto</th>
                                <th class="titulo_coluna">Revisão</th>
                                <th class="titulo_coluna">Valor Adicional</th>
                                <th class="titulo_coluna">Total</th>
                            </thead>
                            <tbody>
                        <?php 
                            foreach ($value["itens"] as $keyx => $valuex) { 
                                $cor_reincidente = (!empty($valuex["os_reincidente"])) ? "bgcolor='#FFCCCC'" : "";
                                $cor_meses = ($valuex["qtde_mes"] > 12) ? "bgcolor='#D7FFE1'" : "";

                                if (in_array($valuex["os"], $_POST["os"])){
                                    $checked = "checked";
                                }else{
                                    $checked = "";
                                }
                        ?>
                            <tr <?=$cor_reincidente?> <?=$cor_meses?> rel='filhas_<?=$value["cabecalho"]["os_revenda"]?>'>
                                <td>
                                    <input rel="checkbox_os" type="checkbox" class="check" name="os[]" value="<?=$valuex["os"]?>" <?=$checked?> />
                                    <input style="display: none;" rel="checkbox_os" type="checkbox" class="check" name="os_revenda[]" value="<?=$value["cabecalho"]["os_revenda"]?>" <?=$checked?> />
                                </td>
                                <td>
                                    <a href="os_press.php?os=<?=$valuex["os"]?>" target="_blank"><?=$valuex["sua_os"]?></a>
                                </td>
                                <td><?=$valuex["produto_referencia"]?>-<?=$valuex["produto_descricao"]?></td>
                                <td class="tac"><?=number_format($valuex["valor_mo"],2,',','.')?></td>
                                <td class="tac"><?=number_format($valuex["valor_adicional"],2,',','.')?></td>
                                <td class="tac"><?=number_format($valuex["total_mo"],2,',','.')?></td>
                            </tr>
                        <?php
                        } 
                        ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?php
            }
        ?>
        </tbody>
    </table>
    <?php if (empty($info_extrato["liberado"])){ ?>
        <br/>
        <div class="tc_formulario">
            <div class="titulo_tabela">AÇÃO PARA OS's MARCADAS</div>
            </br>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class='span6'>
                    <div class='control-group <?=(in_array("select_acao", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='observacao'>Ações</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <select name="select_acao" class="span12" id="select_acao">
                                    <option value=""></option>
                                    <option value="REABRIR">REABRIR OS (RETIRA DO EXTRATO)</option>
                                    <option value="RECUSAR">RECUSAR OS (ZERAR VALOR)</option>
                                    <option value="EXCLUIR">EXCLUIR OS</option>
                                    <option value="ACUMULAR">ACUMULAR PARA PRÓXIMO EXTRATO</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row-fluid">
                <div class="span1"></div>
                <div class='span10'>
                    <div class='control-group <?=(in_array("select_acao", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='observacao'>Observação</label>
                        <div class='controls controls-row'>
                            <div class='span12'>
                                <textarea class="span12" name="observacao" value=""></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span1"></div>
            </div>
            <p><br/>
                <button type='submit' class="btn btn-primary" name='btn_select' value='enviar'/>Enviar</button>
            </p><br/>
        </div>
    <?php } ?>
    <br/>
    <div class="tc_formulario">
        <div class="titulo_tabela">Data envio financeiro</div>
        </br>
        <div class="row-fluid tc_formulario">
            <div class="span1"></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data_pagamento", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_pagamento'>Data envio</label>
                    <div class='controls controls-row'>
                        <div class='span12'>
                            <input type="text" id="data_pagamento" name="data_pagamento" class='span6' value="<? echo $data_pagamento ?>" >
                            <button class='btn btn-primary' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
                            <input type='hidden' id="btn_click" name='btn_acao' value='' />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<br>

<div class="row-fluid tac">
    <span class="label label-info">
        <a style="color: #ffffff" rel='shadowbox' href="relatorio_log_alteracao_new.php?parametro=tbl_extrato_consulta_os_extra&id=<?=$extrato;?>" name="btnAuditorLog">Visualizar Log Auditor</a>
    </span>
</div>
<br>

<script type="text/javascript">
    $(function() {
        $.datepickerLoad(Array("data_pagamento"));
        Shadowbox.init();

        $("select[name='select_acao']").change(function(){
            let observacao = $("input[name='observacao']").val();
            if (observacao == "" || observacao == undefined){
                alert("Preencha o campo observação");
            }
        });

        $('[data-toggle="tooltip"]').tooltip();
    
    });
    $(document).on("click", ".toogle-os", function() {
        var numero_os = $(this).attr('rel');
        if ($("tr[rel=pai_"+numero_os).is(":visible")) {
            $(this).removeClass("icon-minus").addClass("icon-plus");
            $("tr[rel=pai_"+numero_os).fadeOut(800);
        } else {
            $(this).removeClass("icon-plus").addClass("icon-minus");
            $("tr[rel=pai_"+numero_os).fadeIn(800);
        }
    });

    $(document).on("click", ".check_pai", function () {
        var numero = $(this).data('check_revenda');
        
        if ($(this).is(":checked")) {
            $("tr[rel=filhas_"+numero+"] > td > input[type=checkbox]").prop('checked', 'checked');
        } else {
            $("tr[rel=filhas_"+numero+"] > td > input[type=checkbox]").prop('checked', false);
        }
    });

    $(document).on("click", ".check", function () {
        
        if ($(this).is(":checked")) {
            $(this).next().prop('checked', 'checked');
        } else {
            $(this).next().prop('checked', false);
        }
    });
</script>

<?php include "rodape.php"; ?>

<!-- SELECT DISTINCT tbl_os.os AS os, tbl_os.os , lpad (tbl_os.sua_os,10,'0') AS ordem , tbl_os.sua_os , to_char (tbl_os.data_digitacao,'DD/MM/YYYY') AS data , to_char (tbl_os.data_abertura ,'DD/MM/YYYY') AS abertura , to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento , to_char (tbl_os.finalizada ,'DD/MM/YYYY') AS finalizada , to_char (tbl_os.data_conserto ,'DD/MM/YYYY') AS conserto , tbl_os.data_abertura - tbl_os.data_conserto::DATE AS dias , tbl_os.consumidor_revenda , tbl_os.codigo_fabricacao , tbl_os.consumidor_nome , tbl_os.consumidor_cidade , tbl_os.consumidor_fone , tbl_os.revenda_nome , tbl_os.troca_garantia , tbl_os.custo_peca , ( (DATE_PART('year', tbl_os.data_abertura) - DATE_PART('year', tbl_os.data_nf)) * 12 + (DATE_PART('month', tbl_os.data_abertura) - DATE_PART('month', tbl_os.data_nf)) ) as qtde_mes, tbl_os.data_fechamento , ( SELECT SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) JOIN tbl_servico_realizado USING (servico_realizado) WHERE tbl_os_produto.os = tbl_os.os ) AS total_pecas , tbl_os.mao_de_obra AS total_mo , tbl_os.qtde_km AS qtde_km , tbl_os.qtde_km_calculada AS qtde_km_calculada, COALESCE(tbl_os.pedagio, 0) AS pedagio, tbl_os.cortesia , COALESCE(tbl_os.qtde_diaria,0) AS qtde_visitas , tbl_os.nota_fiscal , to_char(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf , tbl_os.nota_fiscal_saida , tbl_os.posto , tbl_produto.produto , tbl_produto.referencia , tbl_produto.descricao , tbl_os_extra.extrato , tbl_os_extra.os_reincidente , tbl_os.observacao , tbl_os.motivo_atraso , tbl_os_extra.motivo_atraso2 , tbl_os_extra.mao_de_obra_desconto , tbl_os_extra.taxa_visita , tbl_os_extra.valor_total_deslocamento AS entrega_tecnica , tbl_os.obs_reincidencia , tbl_os.valores_adicionais , to_char (tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao , tbl_extrato.total AS total , tbl_extrato.mao_de_obra AS mao_de_obra , tbl_extrato.pecas AS pecas , tbl_extrato.deslocamento AS total_km , tbl_extrato.admin AS admin_aprovou , tbl_extrato.recalculo_pendente , lpad (tbl_extrato.protocolo::text,6,'0') AS protocolo, tbl_posto.nome AS nome_posto , tbl_posto_fabrica.codigo_posto AS codigo_posto , tbl_posto_fabrica.prestacao_servico , tbl_extrato_pagamento.valor_total , tbl_extrato_pagamento.acrescimo , tbl_extrato_pagamento.desconto , tbl_extrato_pagamento.valor_liquido , tbl_extrato_pagamento.nf_autorizacao , tbl_extrato_pagamento.baixa_extrato , to_char (tbl_extrato.previsao_pagamento,'DD/MM/YYYY') AS previsao_pagamento , to_char (tbl_extrato.data_recebimento_nf,'DD/MM/YYYY') AS data_recebimento_nf , to_char (tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento , to_char (tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY') AS data_pagamento , tbl_extrato_pagamento.autorizacao_pagto , tbl_os_extra.obs_adicionais , tbl_os_extra.valor_total_hora_tecnica , tbl_posto_fabrica.valor_km, tbl_extrato_pagamento.obs , tbl_extrato_pagamento.extrato_pagamento , ( SELECT COUNT(1) FROM tbl_os_item JOIN tbl_os_produto USING (os_produto) WHERE tbl_os_produto.os = tbl_os.os ) AS os_sem_item, ( SELECT peca_sem_estoque FROM tbl_os_item JOIN tbl_os_produto using(os_produto) WHERE tbl_os_produto.os = tbl_os.os AND peca_sem_estoque is true LIMIT 1 ) AS peca_sem_estoque , tbl_os.data_fechamento - tbl_os.data_abertura as intervalo , ( SELECT login FROM tbl_admin WHERE tbl_admin.admin = tbl_os.admin AND tbl_admin.fabrica = 178 ) AS admin, tbl_familia.descricao as familia_descr, tbl_familia.familia as familia_id, tbl_familia.codigo_familia as familia_cod, tbl_marca.nome as marca, tbl_os_produto.serie FROM tbl_extrato LEFT JOIN tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato = tbl_extrato.extrato LEFT JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato LEFT JOIN tbl_os ON tbl_os.os = tbl_os_extra.os LEFT JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_extrato.fabrica AND tbl_fabrica.fabrica = 178 JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = 178 LEFT JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia AND tbl_familia.fabrica = 178 LEFT JOIN tbl_marca ON tbl_produto.marca = tbl_marca.marca WHERE tbl_extrato.fabrica = 178 AND 
tbl_extrato.extrato = 3998494 ORDER BY tbl_os_extra.os_reincidente, tbl_os.os, data ASC; -->
