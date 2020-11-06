<?php
include __DIR__."/../../class/communicator.class.php";
$mailer = new TcComm($externalId);
$os = $_GET["os"];

if ($areaAdmin === false) {
    $whereOSPosto = "AND posto = {$login_posto}";
}

$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11,172) " : " tbl_os.fabrica = $login_fabrica ";

if ($login_fabrica == 183 AND $login_tipo_posto_codigo == "Rep"){
    $sql = "
        SELECT tbl_os.os FROM tbl_os 
        JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
        JOIN tbl_representante ON tbl_representante.representante = tbl_os_extra.representante AND tbl_representante.cnpj = '{$login_cnpj}'
        WHERE tbl_os.os = {$os}
        AND tbl_representante.fabrica = {$login_fabrica}";
}else{
    $sql = "SELECT sua_os FROM tbl_os WHERE {$cond_pesquisa_fabrica} AND os = {$os} {$whereOSPosto}";
}
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0) {
    $sua_os = pg_fetch_result($res, 0, "sua_os");
} else {
    $os_not_found = true;
}

if (isset($_POST["interacao_submit"])) {
    $msg_erro = array();

    $mensagem           = trim(utf8_decode($_POST["interacao_mensagem"]));
    $interna            = $_POST["interacao_interna"];
    $email              = $_POST["interacao_email"];
    $transferir         = $_POST["interacao_transferir"];
    $transferir_admin   = $_POST["interacao_transferir_admin"];
    $email_consumidor   = $_POST["interacao_email_consumidor"];
    $sms_consumidor     = $_POST["interacao_sms_consumidor"];
    $data_contato       = $_POST["interacao_data_contato"];
    $atendido           = $_POST["interacao_atendido"];
    $email_fabricante   = $_POST["interacao_email_fabricante"];
    $email_setor_enviar = $_POST["interacao_email_setor_enviar"];
    $email_setor        = $_POST["interacao_email_setor"];
    $interacao_obg_gestao_interna == $_POST["interacao_obg_gestao_interna"];

    $parecer_final = false;
    if (isset($_POST['parecer_final'])) {
        $parecer_final = (empty($_POST["parecer_final"])) ? false : $_POST["parecer_final"]; 
    }


    if ($login_fabrica == 11 and false === $areaAdmin) {
        $email_setor_enviar = true;
    }

    if ($interacao_obg_gestao_interna == "on") {
        $interacao_obg_gestao_interna = true;
    }

    if (empty($mensagem)) {
        $msg_erro[] = "Informe a Mensagem";
    }

    if($transferir == "true" AND strlen(trim($transferir_admin)) == ""){ //hd_chamado=2742793 & hd_chamado=2757360
        $msg_erro[] = "Selecione o admin para transferir";
    }

    if($email_setor_enviar == "true" AND strlen(trim($email_setor)) == ""){ //hd_chamado=2742793 & hd_chamado=2757360
        $msg_erro[] = "Selecione o setor";
    }

    if (empty($interna)) {
        $interna = "false";
    }

    if (empty($email)) {
        $email = "false";
    }

    if (empty($sms_consumidor)) {
        $sms_consumidor = "false";
    }

    if (!empty($data_contato)) {
        list($data_contato_dia, $data_contato_mes, $data_contato_ano) = explode("/", $data_contato);

        if (!checkdate($data_contato_mes, $data_contato_dia, $data_contato_ano)) {
            $msg_erro[] = "Data de contato inválida";
        } else {
            $data_contato = "{$data_contato_ano}-{$data_contato_mes}-{$data_contato_dia}";

            if (strtotime($data_contato) < strtotime("today")) {
                $msg_erro[] = "Data de contato não pode ser inferior a data atual";
            }
        }
    }

    if (empty($atendido)) {
        $atendido = "false";
    }

    if (!count($msg_erro)) {
        try {
            pg_query($con, "BEGIN");

            $osData = getOsData($os);
            $posto  = $osData["posto"];

            $respInteracao = call_user_func($insertInteracao, $os, $mensagem, $interna, $email, $parecer_final, $transferir_admin);
            $interacao_mensagem = $mensagem;


            if ($areaAdmin === true) {
                if ($transferir == "true") {
                    $transferir = transferirOs($os, $transferir_admin);
                }

                if ($email == "true") {
                    $email = postoEmail($os, $posto);
                }

                if ($email_consumidor == "true") {
                    $email_consumidor = consumidorEmail($os);
                }

                if ($sms_consumidor == "true") {
                    $sms_consumidor = consumidorSMS();
                }

                if (count($funcoes_fabrica) > 0) {
                    foreach ($funcoes_fabrica as $funcao) {
                        if (function_exists($funcao)) {
                            call_user_func($funcao);
                        }
                    }
                }

            } else {
                //hd-3365460 - fabrica 91
                if ($email_fabricante == "true" || in_array($login_fabrica, array(85, 91, 104, 146))) {
                    $email_fabricante = fabricanteEmail($os);
                }

                if ($email_setor_enviar == "true") {
                    $email_setor_enviar = setorEmail($os, $email_setor);
                }

                if ($envia_email_responsavel_atendimento === true) {
                    $envia_email_responsavel_atendimento = emailResponsavelAtendimento($os);
                }

                if (count($funcoes_fabrica) > 0) {
                    foreach ($funcoes_fabrica as $funcao) {
                        if (function_exists($funcao)) {
                            call_user_func($funcao);
                        }
                    }
                }
            }

            $msg_sucesso = "Interação gravada com sucesso";
            unset($_POST);
            pg_query($con, "COMMIT");

            if ($areaAdmin === true) {

                if ($envia_comunicado_posto === true && $areaAdmin === true) {
                    enviaComunicadoPosto();
                }

                if ($interacao_obg_gestao_interna == "true" && $telecontrol_distrib == "t" ) {
                    $osInteracao = pg_fetch_result($respInteracao, 0, 'os_interacao');
                    enviaComunicadoPosto($osInteracao);
                }

                if ($transferir === true) {
                    enviarEmailTransferir();
                }

                if ($email === true) {
                    enviarEmailPosto();
                }

                if ($email_consumidor === true) {
                    enviarEmailConsumidor();
                }

                if ($sms_consumidor === true) {
                    enviarConsumidorSMS();
                }

            } else {
                if ($email_fabricante === true) {
                    enviarEmailFabricante();
                }

                if ($email_setor_enviar === true) {
                    enviarEmailSetor();
                }

                if ($envia_email_responsavel_atendimento === true) {
                    enviarEmailResponsavelAtendimento();
                }

                if (isset($interacao_envia_email_regiao)) {
                    enviaEmailInteracaoRegiao($interacao_envia_email_regiao, getPostoData($posto));
                }

                if (in_array($login_fabrica, array(148))) {
                    dispara_email_admin_yanmar();
                }

                if ($login_fabrica == 157) {
                    // HD 3771297
                    $sql_posto_info = "SELECT codigo_posto, nome
                       FROM tbl_posto
                       JOIN tbl_posto_fabrica USING(posto)
                       WHERE posto = $login_posto AND fabrica = $login_fabrica";
                    $qry_posto_info = pg_query($con, $sql_posto_info);

                    $mensagem = "O posto autorizado " . pg_fetch_result($qry_posto_info, 0, 'codigo_posto') . ' - ' . pg_fetch_result($qry_posto_info, 0, 'nome');
                    $mensagem .= " inseriu uma interação na OS <strong>$sua_os</strong>: <br><br>{$interacao_mensagem}";

                    $headers = "MIME-Version: 1.0 \r\n";
                    $headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";

                    enviarEmail("amanda.ricetti@wap.ind.br", "Interação na OS $sua_os", $mensagem, $headers);
                }
            }

            if (in_array($login_fabrica, [104])) {
                $interacaoStatus = ($areaAdmin === true) ? true : false;
                
                echo json_encode(array('status' => true, 'mensagem' => utf8_encode($msg_sucesso), 'interacao_admin' => $interacaoStatus));
            } else {
                echo json_encode(array('status' => true, 'mensagem' => utf8_encode($msg_sucesso)));
            }
            
            exit;
        } catch (Exception $e) {
            $msg_erro[] = $e->getMessage();
            pg_query($con, "ROLLBACK");
            echo json_encode(array('status' => false, 'mensagem' => utf8_encode(implode("<br />", $msg_erro))));
            exit;
        }
    } else {
        echo json_encode(array('status' => false, 'mensagem' => utf8_encode(implode("<br />", $msg_erro))));
        exit;
    }
}

function getOsData($os, $returnProduto = false) {
    global $con, $login_fabrica;

    if (empty($os)) {
        throw new Exception("OS não informada");
    }

    $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11,172) " : " tbl_os.fabrica = $login_fabrica ";

    if ($returnProduto) {
        $campoProduto = ", tbl_produto.descricao as nome_produto,
                         tbl_produto.referencia as referencia_produto";
        $joinProduto  = "JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                         JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto";
    }

    $sql = "SELECT
                sua_os,
                posto,
                consumidor_nome,
                consumidor_email,
                consumidor_celular,
                TO_CHAR(data_abertura, 'DD/MM/YYYY') as data_abertura
                {$campoProduto}
            FROM tbl_os
            {$joinProduto}
            WHERE {$cond_pesquisa_fabrica}
            AND tbl_os.os = {$os}";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        throw new Exception("OS não encontrada");
    }

    $array_dados_os = [
        "sua_os"             => pg_fetch_result($res, 0, "sua_os"),
        "posto"              => pg_fetch_result($res, 0, "posto"),
        "consumidor_nome"    => pg_fetch_result($res, 0, "consumidor_nome"),
        "consumidor_email"   => pg_fetch_result($res, 0, "consumidor_email"),
        "consumidor_celular" => pg_fetch_result($res, 0, "consumidor_celular"),
        "data_abertura"      => pg_fetch_result($res, 0, "data_abertura")
    ];

    if ($returnProduto) {

        $array_dados_os['referencia_produto'] = pg_fetch_result($res, 0, 'referencia_produto');
        $array_dados_os['nome_produto']       = pg_fetch_result($res, 0, 'nome_produto');

    }

    return $array_dados_os;
}

function getAdminData($admin) {
    global $con, $login_fabrica;

    if (empty($admin)) {
        throw new Exception("Admin não informado");
    }

    $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_admin.fabrica IN (11,172) " : " tbl_admin.fabrica = $login_fabrica ";

    $sql = "SELECT email, nome_completo FROM tbl_admin WHERE {$cond_pesquisa_fabrica} AND admin = {$admin}";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        throw new Exception("Admin não encontrado");
    }

    return array(
        "email" => pg_fetch_result($res, 0, "email"),
        "nome_completo" => pg_fetch_result($res, 0, "nome_completo")
    );
}

function getPostoData($posto) {
    global $con, $login_fabrica;

    if (empty($posto)) {
        throw new Exception("Posto não informado");
    }

    $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_posto_fabrica.fabrica IN (11,172) " : " tbl_posto_fabrica.fabrica = $login_fabrica ";

    $sql = "SELECT contato_email, contato_estado FROM tbl_posto_fabrica WHERE {$cond_pesquisa_fabrica} AND posto = {$posto}";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        throw new Exception("Posto não encontrado");
    }

    return array(
        "contato_email" => pg_fetch_result($res, 0, "contato_email"),
        "contato_estado" => pg_fetch_result($res, 0, "contato_estado")
    );
}

function insertInteracao($os, $mensagem, $interna, $email, $parecer_final = null, $admin = null) {
    global $con, $login_fabrica, $login_admin, $areaAdmin, $login_posto;

    $admin = empty($admin) ? 'null' : $admin; 

    if (empty($os)) {
        throw new Exception("OS não informada");
    }

    $programa_insert = $_SERVER['PHP_SELF'];

    $mensagem = pg_escape_literal($con, $mensagem);

    if (!empty($parecer_final)) {
        $campo_atendido = ', atendido';
        $valor_atendido = ", ".$parecer_final;
    }

    if ($areaAdmin === true) {
        $sql = "INSERT INTO tbl_os_interacao
                (programa,fabrica, os, admin, transferido_para, comentario, interno, exigir_resposta $campo_atendido)
                VALUES
                ('{$programa_insert}',{$login_fabrica}, {$os}, {$login_admin}, {$admin}, $mensagem, '{$interna}', '{$email}' {$valor_atendido}) RETURNING os_interacao";
    } else {
        $admin = $_POST['interacao_email_setor'];
        $admin = (strlen($admin) > 0) ? $admin : "NULL";
        $fields = "programa, fabrica, os, posto, comentario, interno, exigir_resposta";
        $values = "'{$programa_insert}',{$login_fabrica}, {$os}, {$login_posto}, {$mensagem}, false, false";
        if ($login_fabrica != 72) {
            $fields .= ", admin";
            $values .= ", {$admin}";
        }

        $sql = "INSERT INTO tbl_os_interacao
                (" . $fields . ") 
                VALUES
                (" . $values . ")";
    }

    
    
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao gravar interação");
    }

    return $res;
}

function enviaComunicadoPosto($osInteracao = null) {
    global $con, $osData, $login_fabrica, $interacao_mensagem, $interacao_obg_gestao_interna, $telecontrol_distrib;

    $posto  = $osData["posto"];
    $sua_os = $osData["sua_os"];

    $columns = "";
    $values = "";

    if (strlen($osInteracao) > 0 && $interacao_obg_gestao_interna == "true" && $telecontrol_distrib == "t") {
        $columns = ", parametros_adicionais";
        $values = ", '{\"os_interacao\": \"" . $osInteracao . "\"}'";
    }

    $sql = "INSERT INTO tbl_comunicado (
                fabrica,
                posto,
                obrigatorio_site,
                tipo,
                ativo,
                descricao,
                mensagem
                {$columns}
            ) VALUES (
                {$login_fabrica},
                {$posto},
                true,
                'Com. Unico Posto',
                true,
                'Interação na Ordem de Serviço',
                ".pg_escape_literal($con,"A Fábrica interagiu na Ordem de Serviço <strong>{$sua_os}</strong>, mensagem: {$interacao_mensagem}")."
                {$values}
            ) RETURNING comunicado";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao gravar interação");
    }
}

/**
 * TRANSFERIR ORDEM DE SERVIÇO
 */
$transferirEmailConfig = array(
    "assunto"  => "Telecontrol - Ordem de Serviço transferida",
    "mensagem" => "",
    "headers"  => "",
    "email"    => ""
);

function enviarEmailTransferir() {
    global $transferirEmailConfig;

    enviarEmail($transferirEmailConfig["email"], $transferirEmailConfig["assunto"], $transferirEmailConfig["mensagem"], $transferirEmailConfig["headers"]);
}

/**
 * Retorna ser irá enviar email ou não
 */
function transferirOs($os, $admin) {
    global $con, $transferirEmailConfig, $osData, $interacao_mensagem, $insertInteracao, $login_fabrica;

    $adminData               = getAdminData($admin);
    $admin_transferido       = $adminData["nome_completo"];
    $admin_transferido_email = $adminData["email"];

    $sua_os = $osData["sua_os"];

    call_user_func($insertInteracao, $os, "Transferido para o admin {$admin_transferido}", true, "f", null, $admin);

    if (!empty($admin_transferido_email)) {
        if ($login_fabrica == 146) {
            $transferirEmailConfig["email"] = $admin_transferido_email.', helpdesk@telecontrol.com.br';
        } else {
            $transferirEmailConfig["email"] = $admin_transferido_email;            
        }

        $transferirEmailConfig["mensagem"] = "
            <h3>Olá {$admin_transferido}, a Ordem de Serviço <strong>{$sua_os}</strong> foi transferida para você.</h3><br />
            <strong style='color: #FF0000;' >Interação:</strong> {$interacao_mensagem}<br />
            <a href='http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Serviço</a>
        ";

        $transferirEmailConfig["headers"] = "MIME-Version: 1.0 \r\n";
        $transferirEmailConfig["headers"] .= "Content-type: text/html; charset=iso-8859-1 \r\n";
        if (in_array($login_fabrica, array(169,170))){
            $transferirEmailConfig["headers"] .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
        }else{
            $transferirEmailConfig["headers"] .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
        }

        /*HD - 6454225*/
        $aux_sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = $login_fabrica";
        $aux_res = pg_query($con, $aux_sql);
        $aux_val = pg_fetch_result($aux_res, 0, 'nome');


        $transferirEmailConfig["assunto"]  = "$aux_val - OS $os transferida para você ";
        
        if ($login_fabrica == 174) {
             
             $transferirEmailConfig["assunto"] = "Telecontrol - Ordem de Serviço transferida - $aux_val - OS $sua_os";
        }
        
        return true;
    }

    return false;
}
/**
 * FIM TRANSFERIR ORDEM DE SERVIÇO
 */

/**
 * ENVIAR EMAIL POSTO
 */
$postoEmailConfig = array(
    "assunto"  => "Telecontrol - Interação na Ordem de Serviço",
    "mensagem" => "",
    "headers"  => "",
    "email"    => ""
);

function enviarEmailPosto() {
    global $postoEmailConfig;
    
    enviarEmail($postoEmailConfig["email"], $postoEmailConfig["assunto"], $postoEmailConfig["mensagem"], $postoEmailConfig["headers"]);
}

/**
 * Retorna ser irá enviar email ou não
 */
function postoEmail($os, $posto) {
    global $con, $login_fabrica, $postoEmailConfig, $osData, $interacao_mensagem;

    $postoData = getPostoData($posto);
    $posto_email = $postoData["contato_email"];

    if (empty($posto_email)) {
        throw new Exception("Email do Posto Autorizado não está cadastrado");
    }

    $sua_os = $osData["sua_os"];

    if(in_array($login_fabrica, array(11,172))){
        $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica IN (SELECT fabrica FROM tbl_os WHERE os = {$os})";
    }else{
        $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
    }

    $res          = pg_query($con, $sql);
    $nome_fabrica = pg_fetch_result($res, 0, "nome");

    $postoEmailConfig["email"] = $posto_email;
    $postoEmailConfig["assunto"] ='Telecontrol - Interação na Ordem de Serviço';

    $postoEmailConfig['assunto'] = utf8_encode("Telecontrol - Interação na Ordem de Serviço");

    $postoEmailConfig["mensagem"] = "
        <h3>A Fábrica {$nome_fabrica} interagiu na Ordem de Serviço <strong>{$sua_os}</strong>.</h3><br />
        <strong style='color: #FF0000;' >Interação:</strong> {$interacao_mensagem}<br />
        <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Serviço</a>
    ";

    $postoEmailConfig["headers"]  = "MIME-Version: 1.0 \r\n";
    $postoEmailConfig["headers"] .= "Content-type: text/html; charset=iso-8859-1 \r\n";
    if (in_array($login_fabrica, array(169,170))){
        $postoEmailConfig["headers"] .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
    }else{
        $postoEmailConfig["headers"] .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
    }


    return true;
}
/**
 * FIM ENVIAR EMAIL POSTO
 */

/**
 * ENVIAR EMAIL PARA O CONSUMIDOR
 */
$consumidorEmailConfig = array(
    "assunto"  => "",
    "mensagem" => "",
    "headers"  => "",
    "email"    => ""
);

function enviarEmailConsumidor() {
    global $consumidorEmailConfig;

    enviarEmail($consumidorEmailConfig["email"], $consumidorEmailConfig["assunto"], $consumidorEmailConfig["mensagem"], $consumidorEmailConfig["headers"]);
}

/**
 * Retorna ser irá enviar email ou não
 */
function consumidorEmail($os) {
    global $con, $login_fabrica, $consumidorEmailConfig, $osData, $interacao_mensagem, $insertInteracao;

    $consumidor_email = $osData["consumidor_email"];
    $consumidor_nome  = $osData["consumidor_nome"];

    if (empty($consumidor_email) || !filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email do consumidor inválido");
    }

    call_user_func($insertInteracao, $os, "Enviou email para o consumidor, email: {$consumidor_email}", true, true);

    $sua_os = $osData["sua_os"];

    if(in_array($login_fabrica, array(11,172))){
        $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica IN (SELECT fabrica FROM tbl_os WHERE os = {$os})";
    }else{
        $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
    }

    $res = pg_query($con, $sql);

    $nome_fabrica = pg_fetch_result($res, 0, "nome");

    $consumidorEmailConfig["email"] = $consumidor_email;

    $consumidorEmailConfig["assunto"] = utf8_encode("Protocolo de Atendimento {$nome_fabrica} - Ordem de Serviço {$sua_os}");

    $consumidorEmailConfig["mensagem"] = "
        <h3>Prezado {$consumidor_nome}</h3><br />
        {$interacao_mensagem}<br />
    ";

    $consumidorEmailConfig["headers"]  = "MIME-Version: 1.0 \r\n";
    $consumidorEmailConfig["headers"] .= "Content-type: text/html; charset=iso-8859-1 \r\n";
    if (in_array($login_fabrica, array(169,170))){
        $consumidorEmailConfig["headers"] .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
    }else{
        $consumidorEmailConfig["headers"] .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
    }

    return true;
}
/**
 * FIM ENVIAR EMAIL PARA O CONSUMIDOR
 */

/**
 * ENVIAR SMS PARA O CONSUMIDOR
 */
$consumidorSMSMensagem = array(
    "celular"  => "",
    "mensagem" => ""
);

function enviarConsumidorSMS() {
    global $osData, $login_fabrica, $con, $consumidorSMSMensagem;
    $sua_os             = $osData["sua_os"];

    include "../class/sms/sms.class.php";
    $smsClass = new SMS();

    $smsClass->enviarMensagem($consumidorSMSMensagem["celular"], $sua_os, "", $consumidorSMSMensagem["mensagem"]);

}

function consumidorSMS() {
    global $osData, $login_fabrica, $con, $consumidorSMSMensagem, $interacao_mensagem;

    $sua_os             = $osData["sua_os"];
    $consumidor_celular = $osData["consumidor_celular"];

    if (empty($consumidor_celular)) {
        throw new Exception("Celular do consumidor não cadastrado");
    } else {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

        try {
            $celular          = $phoneUtil->parse("+55".$consumidor_celular, "BR");
            $isValid          = $phoneUtil->isValidNumber($celular);
            $numberType       = $phoneUtil->getNumberType($celular);
            $mobileNumberType = \libphonenumber\PhoneNumberType::MOBILE;

            if (!$isValid || $numberType != $mobileNumberType) {
                throw new Exception("Número de Celular inválido");
            }
        } catch (\libphonenumber\NumberParseException $e) {
            throw new Exception("Número de Celular inválido");
        }
    }

    if(in_array($login_fabrica, array(11,172))){
        $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica IN (SELECT fabrica FROM tbl_os WHERE sua_os = '{$sua_os}')";
    }else{
        $sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = {$login_fabrica}";
    }

    $res          = pg_query($con, $sql);
    $nome_fabrica = pg_fetch_result($res, 0, "nome");

    $consumidorSMSMensagem["mensagem"] = utf8_encode("Protocolo de Atendimento {$nome_fabrica} - OS {$sua_os}: {$interacao_mensagem}");
    $consumidorSMSMensagem["celular"]  = $consumidor_celular;

    return true;
}
/**
 * FIM ENVIAR SMS PARA O CONSUMIDOR
 */

function enviarEmail($email, $assunto, $mensagem, $headers) {
    global $smtpEmail,$con,$login_fabrica, $mailer;

    $sql = "SELECT parametros_adicionais FROM tbl_fabrica WHERE fabrica = $login_fabrica";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'), true); // Array
        $externalId    = ($parametros_adicionais['externalId'])    ? : 'smtp@posvenda';
        if (in_array($login_fabrica, array(169,170))){
            $externalEmail = ($parametros_adicionais['externalEmail']) ? $parametros_adicionais['externalEmail'] : 'naorespondablueservice@carrier.com.br';
        }else{
            $externalEmail = ($parametros_adicionais['externalEmail']) ? $parametros_adicionais['externalEmail'] : 'noreply@telecontrol.com.br';
        }
    }
    $mailer->Subject($assunto);
    $res = $mailer->sendMail(
        $email,
        $assunto,
        $mensagem,
        $externalEmail
    );
    
    if($res !== true) {
        mail($email, $assunto, $mensagem, $headers);
    }
}

/**
 * ENVIAR EMAIL PARA A FÁBRICA
 */
$fabricanteEmailConfig = array(
    "assunto"  => "",
    "mensagem" => "",
    "headers"  => "",
    "email"    => ""    
);

function enviarEmailFabricante() {
    global $fabricanteEmailConfig;

    enviarEmail($fabricanteEmailConfig["email"], $fabricanteEmailConfig["assunto"], $fabricanteEmailConfig["mensagem"], $fabricanteEmailConfig["headers"]);
}

/**
 * Retorna ser irá enviar email ou não
 */
function fabricanteEmail($os) {
    global $con, $fabricanteEmailConfig, $fabrica_email, $osData, $interacao_mensagem, $login_fabrica;

    if ($login_fabrica == 85) {
        $sql = "SELECT status_os FROM tbl_os_status WHERE os = {$os} AND fabrica_status = {$login_fabrica} ORDER BY os_status DESC LIMIT 1";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            return false;
        } else {
            $status_os = pg_fetch_result($res, 0, "status_os");

            if ($status_os != 212) {
                return false;
            }
        }
    } else if ($login_fabrica == 104) {
        $sql = "SELECT tbl_os_interacao.admin,
                        tbl_os_interacao.os_interacao
                FROM tbl_os_interacao
                WHERE tbl_os_interacao.fabrica = {$login_fabrica}
                AND tbl_os_interacao.os = {$os}
                AND tbl_os_interacao.admin IS NOT NULL
                ORDER BY tbl_os_interacao DESC
                LIMIT 1";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            return false;
        }

        $admin = pg_fetch_result($res, 0, "admin");

        $sql = "SELECT email
                FROM tbl_admin
                WHERE admin = {$admin}
                AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            return false;
        }

        $fabrica_email = pg_fetch_result($res, 0, "email");
    }

    if (!is_array($fabrica_email)) { 

        if (empty($fabrica_email) || !filter_var($fabrica_email, FILTER_VALIDATE_EMAIL)) {

            return false;
        }

    } else { 
    
        foreach ($fabrica_email as $email) {

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                
                return false;
            }
        } 
    }

    $sua_os = $osData["sua_os"];

    $fabricanteEmailConfig["email"] = $fabrica_email;

    $fabricanteEmailConfig["assunto"] = utf8_encode("Telecontrol - Interação na Ordem de Serviço {$sua_os}");

    $fabricanteEmailConfig["mensagem"] = "
        <h3>O Posto Autorizado interagiu na Ordem de Serviço <strong>{$sua_os}</strong>.</h3><br />
        <strong style='color: #FF0000;' >Interação:</strong> {$interacao_mensagem}<br />
        <a href='http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Serviço</a>
    ";

    $fabricanteEmailConfig["headers"]  = "MIME-Version: 1.0 \r\n";
    $fabricanteEmailConfig["headers"] .= "Content-type: text/html; charset=iso-8859-1 \r\n";
    if (in_array($login_fabrica, array(169,170))){
        $fabricanteEmailConfig["headers"] .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
    }else{
        $fabricanteEmailConfig["headers"] .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
    }

    return true;
}
/**
 * FIM ENVIAR EMAIL PARA A FÁBRICA
 */

/**
 * ENVIAR EMAIL PARA A FÁBRICA
 */
$setorEmailConfig = array(
    "assunto"  => "",
    "mensagem" => "",
    "headers"  => "",
    "email"    => ""
);

function enviarEmailSetor() {
    global $setorEmailConfig;

    enviarEmail($setorEmailConfig["email"], $setorEmailConfig["assunto"], $setorEmailConfig["mensagem"], $setorEmailConfig["headers"]);
}

/**
 * Retorna ser irá enviar email ou não
 */
function setorEmail($os, $setor) {
    global $setorEmailConfig, $fabrica_setor_email, $osData, $interacao_mensagem, $login_fabrica;

    $sua_os = $osData["sua_os"];

    $setorEmailConfig["email"] = $fabrica_setor_email[$setor]["email"];

    $setorEmailConfig["assunto"] = utf8_encode("Telecontrol - Interação na Ordem de Serviço {$sua_os}");

    $setorEmailConfig["mensagem"] = "
        <h3>O Posto Autorizado interagiu na Ordem de Serviço <strong>{$sua_os}</strong>.</h3><br />
        <strong style='color: #FF0000;' >Interação:</strong> {$interacao_mensagem}<br />
        <a href='http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Serviço</a>
    ";

    $setorEmailConfig["headers"]  = "MIME-Version: 1.0 \r\n";
    $setorEmailConfig["headers"] .= "Content-type: text/html; charset=iso-8859-1 \r\n";
    if (in_array($login_fabrica, array(169,170))){
        $setorEmailConfig["headers"] .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
    }else{
        $setorEmailConfig["headers"] .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
    }

    return true;
}
/**
 * FIM ENVIAR EMAIL PARA A FÁBRICA
 */

function enviaEmailInteracaoRegiao($regioes, $posto) {
    global $con, $os, $osData, $interacao_mensagem, $_serverEnvironment, $login_fabrica;

    $sua_os = $osData["sua_os"];

    if ($_serverEnvironment == "development") {
        $email = "guilherme.curcio@telecontrol.com.br";
    } else {
        $contato_estado = strtoupper($posto["contato_estado"]);

        if ($regioes === true) {
            if (empty($contato_estado)) {
                return false;
            }

            $sql = "SELECT email
                    FROM tbl_admin
                    INNER JOIN tbl_admin_atendente_estado ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin_atendente_estado.fabrica = {$login_fabrica}
                    WHERE tbl_admin.fabrica = {$login_fabrica}
                    AND tbl_admin.nao_disponivel is null
                    AND UPPER(estado) = '{$contato_estado}'";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $email = pg_fetch_result($res, 0, "email");
            } else {
                return false;
            }
        } else {
            if (empty($contato_estado)) {
                $email = $regioes["default"];
            } else {
                $regioes_filter = array_keys(array_filter($regioes, function($array_estados) use($contato_estado) {
                    return (is_array($array_estados) && in_array($contato_estado, $array_estados)) ? true : false;
                }));

                if (!count($regioes_filter)) {
                    $email = $regioes["default"];
                } else {
                    $email = $regioes_filter[0];
                }
            }
        }
    }

    $assunto = "Telecontrol - Interação na Ordem de Serviço {$sua_os}";

    $mensagem = "
        <h3>O Posto Autorizado interagiu na Ordem de Serviço <strong>{$sua_os}</strong>.</h3><br />
        <strong style='color: #FF0000;' >Interação:</strong> {$interacao_mensagem}<br />
        <a href='http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Serviço</a>
    ";

    $headers  = "MIME-Version: 1.0 \r\n";
    $headers .= "content-type: text/html; charset=iso-8859-1 \r\n";
    if (in_array($login_fabrica, array(169,170))){
        $headers .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
    }else{
        $headers .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
    }

    enviarEmail($email, $assunto, $mensagem, $headers);

    return true;
}


#

/**
 * ENVIAR EMAIL PARA A FÁBRICA
 */
$atendimentoEmailConfig = array(
    "assunto"  => "",
    "mensagem" => "",
    "headers"  => "",
    "email"    => ""
);

function enviarEmailResponsavelAtendimento() {
    global $atendimentoEmailConfig;

    enviarEmail($atendimentoEmailConfig["email"], $atendimentoEmailConfig["assunto"], $atendimentoEmailConfig["mensagem"], $atendimentoEmailConfig["headers"]);
}

/**
 * Retorna ser irá enviar email ou não
 */
function emailResponsavelAtendimento($os) {
    global $con, $atendimentoEmailConfig, $osData, $interacao_mensagem, $login_fabrica;

    $sql = "SELECT hd_chamado FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res = pg_query($con, $sql);

    $hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

    if (empty($hd_chamado)) {
        return false;
    }

    $sql = "SELECT tbl_admin.email
            FROM tbl_hd_chamado
            INNER JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica = {$login_fabrica}
            WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
            AND tbl_hd_chamado.hd_chamado = {$hd_chamado}";
    $res = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        return false;
    }

    $email = pg_fetch_result($res, 0, "email");

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $sua_os = $osData["sua_os"];

    $atendimentoEmailConfig["email"] = $email;

    $atendimentoEmailConfig["assunto"] = utf8_encode("Telecontrol - Interação na Ordem de Serviço {$sua_os}");

    $atendimentoEmailConfig["mensagem"] = "
        <h3>O Posto Autorizado interagiu na Ordem de Serviço <strong>{$sua_os}</strong>.</h3><br />
        <strong style='color: #FF0000;' >Interação:</strong> {$interacao_mensagem}<br />
        <a href='http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os={$os}' target='_blank' >clique aqui para visualizar a Ordem de Serviço</a>
    ";

    $atendimentoEmailConfig["headers"]  = "MIME-Version: 1.0 \r\n";
    $atendimentoEmailConfig["headers"] .= "Content-type: text/html; charset=iso-8859-1 \r\n";
    if (in_array($login_fabrica, array(169,170))){
        $atendimentoEmailConfig["headers"] .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
    }else{
        $atendimentoEmailConfig["headers"] .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
    }
    return true;
}
/**
 * FIM ENVIAR EMAIL PARA A FÁBRICA
 */

function dispara_email_admin_yanmar() {
    global $con, $os ,$login_fabrica;
    $retorno = array();

    $sql= " SELECT
                     tbl_tipo_atendimento.tipo_atendimento,
                     tbl_tipo_atendimento.descricao AS nome_atendimento,
                     tbl_familia.descricao AS nome_familia,
                     tbl_familia.familia,
                     tbl_linha.linha,
                     tbl_linha.nome AS nome_linha,
                     tbl_os.os
                FROM tbl_os
                JOIN tbl_os_produto USING (os)
                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento=tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                JOIN tbl_produto ON tbl_produto.produto=tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
                JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
               WHERE tbl_os.fabrica = {$login_fabrica}
                 AND tbl_os.os = {$os};";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {

        $xos                = pg_fetch_result($res, 0, "os");
        $tipoAtendimento    = pg_fetch_result($res, 0, "tipo_atendimento");
        $nomeTipo           = pg_fetch_result($res, 0, "nome_atendimento");
        $nomeFamilia        = pg_fetch_result($res, 0, "nome_familia");
        $linha              = pg_fetch_result($res, 0, "linha");
        $familia            = pg_fetch_result($res, 0, "familia");
        $nomeLinha          = pg_fetch_result($res, 0, "nome_linha");

        $tipoAt             = array(219,220,278);
        $linhaPd            = array(875,876);
        $tipoAt2            = array(217);
        $tipoA3             = array(219,220,278);
        $familiaPd          = array(5133,5146,5147,5149,5666,5668,5148,5670,5142,5143,5664);

        $sql_email = "  SELECT email,parametros_adicionais
                        FROM tbl_admin
                        WHERE (parametros_adicionais ~ 'email_tipo_atendimento' OR parametros_adicionais ~ 'email_linha')
                        AND fabrica = $login_fabrica";

        $res_email = pg_query($con, $sql_email);
 
        if (pg_num_rows($res_email) > 0) {

            $emails = [];

            for ($i = 0; $i < pg_num_rows($res_email); $i++) {

                $parametros_adicionais = json_decode(pg_fetch_result($res_email, $i, 'parametros_adicionais'), true);

                if (count($parametros_adicionais["email_tipo_atendimento"]) > 0) {
                    if (!in_array($tipoAtendimento, $parametros_adicionais['email_tipo_atendimento'])) {
                        continue;
                    }
                }

                if (count($parametros_adicionais["email_linha"]) > 0) {
                    if (!in_array($linha, $parametros_adicionais['email_linha'])) {
                        continue;
                    }
                }

                $emails[] = pg_fetch_result($res_email, $i, 'email');
            }
        }

        if(!empty($emails)){

            $retorno["email"]            = $emails;
            $retorno["linha"]            = $nomeLinha;
            $retorno["tipo_atendimento"] = $nomeTipo;
            $retorno["familia"]          = $nomeFamilia;
            $retorno["os"]               = $xos;
            $retorno["msg_interacao"]    = "";

            $sql =  "SELECT tbl_os_interacao.comentario AS mensagem
                     FROM tbl_os_interacao
                     WHERE tbl_os_interacao.os = {$xos} AND fabrica = {$login_fabrica}
                     ORDER BY tbl_os_interacao.data DESC LIMIT 1";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) > 0){
                 $retorno['msg_interacao'] = pg_fetch_result($res, 0, 'mensagem');
            }
        }

    }  else {
        $retorno["erro"] = true;
    }

    if (!isset($retorno["erro"])) {

        $headers  = "MIME-Version: 1.0 \r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
        if (in_array($login_fabrica, array(169,170))){
            $headers .= "From: Telecontrol <naorespondablueservice@carrier.com.br> \r\n";
        }else{
            $headers .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";
        }

        $mensagem .= "Foi feito uma interação na Ordem de Seviço: <b>" .$retorno["os"]."</b><br />";
        $mensagem .= "<br /> <b>Tipo Atendimento: </b>".$retorno["tipo_atendimento"];
        $mensagem .= "<br /> <b>Família: </b>".$retorno["familia"]. "<br /><br />";
        $mensagem .= "<b><span style='color:red'>Interação: </span></b>".$retorno["msg_interacao"] ."<br />";

        $mensagem .= "<br /><a href='http://posvenda.telecontrol.com.br/assist/admin/os_press.php?os={$os}'>Clique aqui para visualizar a Ordem de Serviço</a>";

        if(isset($retorno["email"])){
            foreach($retorno["email"] as $email){
                enviarEmail($email, "Interação na OS " . $retorno["os"], $mensagem, $headers);
            }
        }
        
        return true;
    }
}
