<?php
    include_once 'dbconfig.php';
    include_once 'includes/dbconnect-inc.php';
    include_once "autentica_admin.php";
    include_once "../class/communicator.class.php";
    include_once __DIR__.'/funcoes.php';

    $acao = $_POST['acao'];

    // function textoProvidencia($texto, $hd_chamado,$consumidor_nome,$numero_objeto){

    //     $alteracoes["[_consumidor_]"]   = $consumidor_nome;
    //     $alteracoes["[_protocolo_]"]    = $hd_chamado;
    //     $alteracoes["[_rastreio_]"]     = $numero_objeto;

    //     foreach ($alteracoes as $key => $value) {
    //         $texto = str_replace($key, $value, $texto);
    //     }

    //     return $texto;

    // }

    if($acao == "lote"){

        $chamados    = str_replace("\\", "", $_POST['hd_chamado']);
        $chamados    = json_decode($chamados,true);
        $transferir  = $_POST['transferir'];
        $providencia = $_POST['providencia'];
        $status      = $_POST['status'];

        $res = pg_query($con, "BEGIN");

        if(empty($transferir) AND empty($providencia) AND empty($status)){
            $arrayRet = array("statuss" => "error","mensagem" => "Informe a acao que deseja realizar");
            $ret = json_encode($arrayRet);
            echo $ret;
            exit;
        }

        if(count($chamados) ==  0){

            $msg_erro = "Nenhum atendimento foi selecionado";

        }else{

            if(!empty($transferir)){

                $sql = "SELECT login from tbl_admin where admin = $login_admin";
                $res = pg_query($con, $sql);

                $nome_ultimo_atendente = pg_fetch_result($res, 0, 'login');

                $sql = "SELECT login,email from tbl_admin where admin = $transferir";
                $res = pg_query($con, $sql);

                $nome_atendente  = pg_fetch_result($res,0,'login');
                $email_atendente = pg_fetch_result($res,0,'email');

                $sql = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado   ,
                            data         ,
                            comentario   ,
                            admin        ,
                            interno      ,
                            status_item
                        )
                        SELECT  tbl_hd_chamado.hd_chamado,
                        NOW(),
                        E'Atendimento transferido por <b>$login_login</b> de <b>' || tbl_admin.login || '</b> para <b>$nome_atendente</b>',
                        $login_admin,
                        't',
                        tbl_hd_chamado.status
                        FROM tbl_hd_chamado
                        JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin AND tbl_admin.fabrica = {$login_fabrica}
                        WHERE tbl_hd_chamado.hd_chamado IN(".implode(",",$chamados).")
                        AND tbl_hd_chamado.fabrica = {$login_fabrica}";

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                $sql = "UPDATE tbl_hd_chamado set atendente = $transferir
                        WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
                        and tbl_hd_chamado.hd_chamado IN(".implode(",",$chamados).")";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            }

            if(!empty($providencia)){

                if ($login_fabrica == 151) {
                    $sql_prov = "SELECT array_to_string( array(SELECT hd_motivo_ligacao
                                    FROM tbl_hd_chamado_extra
                                    WHERE hd_chamado IN(".implode(",",$chamados).")
                                        AND hd_motivo_ligacao != $providencia),'|') as hd_motivo_ligacao;";
                    $res_prov = pg_query($con,$sql_prov);

                    if (pg_num_rows($res) > 0) {
                        $hd_motivo_ligacao_ant = explode("|", pg_fetch_result($res_prov, 0, hd_motivo_ligacao) );
                    }
                }

                $sql = "SELECT descricao FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = {$providencia} AND fabrica = {$login_fabrica}";
                $res = pg_query($con,$sql);
                $nome_providencia = pg_fetch_result($res,0,'descricao');

                $sql = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado   ,
                            data         ,
                            comentario   ,
                            admin        ,
                            interno      ,
                            status_item
                        )
                        SELECT  tbl_hd_chamado.hd_chamado,
                        NOW(),
                        E'A providência do atendimento foi alterada por <b>$login_login</b> de <b>' || tbl_hd_motivo_ligacao.descricao || '</b> para <b>$nome_providencia</b>',
                        {$login_admin},
                        't',
                        tbl_hd_chamado.status
                        FROM tbl_hd_chamado
                        JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
                        JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = {$login_fabrica}
                        WHERE tbl_hd_chamado.hd_chamado IN(".implode(",",$chamados).")
                        AND tbl_hd_chamado.fabrica = {$login_fabrica}";

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                $sql = "UPDATE tbl_hd_chamado_extra set hd_motivo_ligacao = $providencia
                        WHERE tbl_hd_chamado_extra.hd_chamado IN(".implode(",",$chamados).")";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            }

            if(!empty($status)){

                $sql = "SELECT status FROM tbl_hd_status WHERE hd_status = {$status} AND fabrica = {$login_fabrica}";
                $res = pg_query($con,$sql);
                $novo_status = pg_fetch_result($res,0,'status');

                $sql = "INSERT INTO tbl_hd_chamado_item(
                            hd_chamado   ,
                            data         ,
                            comentario   ,
                            admin        ,
                            interno      ,
                            status_item
                        )
                        SELECT  tbl_hd_chamado.hd_chamado,
                        NOW(),
                        E'O status do atendimento foi alterada por <b>$login_login</b> de <b>' || tbl_hd_chamado.status || '</b> para <b>$novo_status</b>',
                        {$login_admin},
                        't',
                        '{$novo_status}'
                        FROM tbl_hd_chamado
                        WHERE tbl_hd_chamado.hd_chamado IN(".implode(",",$chamados).")
                        AND tbl_hd_chamado.fabrica = {$login_fabrica}";

                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

                $sql = "UPDATE tbl_hd_chamado set status = '{$novo_status}'
                        WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
                        and tbl_hd_chamado.hd_chamado IN(".implode(",",$chamados).")";
                $res = pg_query($con,$sql);
                $msg_erro .= pg_errormessage($con);

            }

        }

        if (strlen($msg_erro) > 0) {
            $arrayRet = array("statuss" => "error","mensagem" => utf8_encode($msg_erro));
            $ret = json_encode($arrayRet);

            $sql = "ROLLBACK TRANSACTION";
            $res = pg_query($con, $sql);


        }else{
            $sucess = "Alterações realizadas com sucesso";
            $arrayRet = array("statuss" => "ok","mensagem" => utf8_encode($sucess));
            $ret = json_encode($arrayRet);

            $sql = "COMMIT TRANSACTION";
            $res = pg_query($con, $sql);

            if ($login_fabrica == 151 AND !in_array($providencia,$hd_motivo_ligacao_ant )) {
                $sql_email = "SELECT destinatarios
                                FROM tbl_hd_motivo_ligacao
                                WHERE destinatarios is not null
                                    AND fabrica = {$login_fabrica}
                                    AND hd_motivo_ligacao = $providencia;";
                $res_email = pg_query($con,$sql_email);

                if (pg_num_rows($res_email) > 0) {
                    $destinatario = pg_fetch_result($res_email, 0, 'destinatarios');
                    $destinatario = json_decode($destinatario,true);

                    $destinatario = implode(";", $destinatario);
                    $text =  "Providência alterada para: {$nome_providencia}";

                    $mail = new TcComm($externalId);

                    $mail->sendMail($destinatario,utf8_encode('Alteração de providência no(s) atendimento(s): '.implode(",",$chamados)),utf8_encode($text), $externalEmail );

                }
            }

            if(!empty($transferir)){

                foreach ($chamados as $key => $hd_chamado) {
                    $links[] = "<a href='https://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado'>https://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado</a>";
                }

                $assunto = "Transferência de atendimentos ".strtoupper($login_fabrica_nome);

                $corpo = "<P align=left><STRONG>Nota: Este e-mail gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
                        <P align=left>$nome_atendente,</P>
                        <P align=justify>
                        Alguns atendimentos foram transferidos por <b>$login_login</b> para você
                        </P>
                        <P>Segue abixo links para acesso aos atendimentos</P>
                        ".implode("<br>",$links);

                $mail = new TcComm($externalId);
                $mail->sendMail($email_atendente, $assunto, $corpo, $externalEmail);

            }

        }

        echo $ret;
        exit;

    } else if($acao == "upload") {

        $arquivo = $_FILES["anexo_upload"];

        $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

        if (strlen($arquivo['tmp_name']) > 0) {

            if (!in_array($ext, array('csv', 'txt'))) {

                $retorno = array("statuss" => "error","mensagem" => 'Arquivo em formato invalido, sao aceitos os seguintes formatos: CSV, TXT');

            } else {
                $dados = file_get_contents($arquivo['tmp_name']);
                if (mb_check_encoding($dados,'UTF-8')) {
                    $dados = utf8_decode($dados);
                }

                $linhas = explode("\n", $dados);

                foreach ($linhas as $ln => $linha) {

                    $res = pg_query($con,"BEGIN");
                    $key = $ln + 1;
                    $erro = 0;

                    $linha = trim($linha);

                    if(strlen($linha) > 0){

                        if ($login_fabrica == 151) {
                            $contador_campos = count(explode(";", $linha));
                            if ($contador_campos >= 5){
                                $xlsResp[] = array($key,$atendimento,"Arquivo inválido. Verificar o modelo do arquivo !");
                                $erro++;
                            } else {
                                list($atendimento,$texto,$novo_status,$providencia) = explode(";", $linha);    
                            }
                        } else {
                            list($atendimento,$data,$texto,$novo_status,$providencia) = explode(";", $linha);
                        }

                        $atendimento = trim($atendimento);
                        $data        = trim($data);
                        if ($login_fabrica == 151) {
                            $data = date("d/m/Y");
                        }
                        $texto       = trim($texto);
                        $texto       = str_replace("'", "", $texto);
                        $texto       = str_replace("\\", "", $texto);
                        $novo_status = trim($novo_status);
                        $novo_status = strtolower($novo_status);
                        $providencia = trim($providencia);
                        list($d,$m,$y) = explode("/", $data);

                        if (!checkdate($m, $d, $y)){
                            $xlsResp[] = array($key,$atendimento,"Data invalida ({$data})");
                            $erro++;
                        } else if (filter_var($atendimento,FILTER_VALIDATE_INT)) {

                            $sql = "SELECT hd_chamado, status,data::date as data FROM tbl_hd_chamado WHERE fabrica = $login_fabrica AND hd_chamado = {$atendimento}";
                            $res = pg_query($con,$sql);

                            if (pg_num_rows($res) == 0) {
                                $xlsResp[] = array($key,$atendimento,"Atendimento {$atendimento} nao encontrado");
                                $erro++;
                            } else {

                                $hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');
                                $status     = pg_fetch_result($res, 0, 'status');
                                $data_chamado   = pg_fetch_result($res, 0, 'data');
                                $hoje = date("Y-m-d");
                                $data_interacao = "$y-$m-$d";
                                if ($login_fabrica == 151) {
                                 $data_interacao = $hoje;   
                                }

                                if (strtotime($data_chamado) > strtotime($data_interacao)) {
                                    $xlsResp[] = array($key,$atendimento,"Data da interacao menor que data de abertura do atendimento $hd_chamado");
                                    $erro++;
                                }

                                if (strtotime($hoje) < strtotime($data_interacao)) {
                                    $xlsResp[] = array($key,$atendimento,"Data da interacao maior que a data atual");
                                    $erro++;
                                }
                               
                                if (!empty($novo_status)) {

                                    if (!in_array($novo_status, array('aberto','cancelado','resolvido'))) {
                                        $xlsResp[] = array($key,$atendimento,"Situacao informada invalida ({$novo_status})");
                                        $erro++;
                                    } else {
                                        switch ($novo_status) {
                                        case 'resolvido':
                                            $status = "Resolvido";
                                            break;
                                        case 'cancelado':
                                            $status = "Cancelado";
                                            break;
                                        case 'aberto':
                                            $status = "Aberto";
                                            break;
                                        }
                                        $sql = "UPDATE tbl_hd_chamado SET status = '{$status}' WHERE hd_chamado = {$hd_chamado}";
                                        $res = pg_query($con,$sql);
                                        $msg_erro = pg_errormessage($con);
                                    }

                                }

                                if (!empty($providencia)) {

                                    $sql = "SELECT tbl_hd_motivo_ligacao.descricao
                                        FROM tbl_hd_motivo_ligacao
                                        JOIN tbl_hd_chamado_extra USING(hd_motivo_ligacao)
                                        WHERE tbl_hd_chamado_extra.hd_chamado = {$hd_chamado}";
                                    $res = pg_query($con,$sql);

                                    $desc_motivo_ligacao_ant = pg_fetch_result($res, 0, 'descricao');


                                    $sql = "SELECT hd_motivo_ligacao, descricao
                                        FROM tbl_hd_motivo_ligacao
                                        WHERE fabrica = {$login_fabrica}
                                        AND upper(fn_retira_especiais(descricao)) = upper(fn_retira_especiais('{$providencia}'))";
                                  
                                    $res = pg_query($con,$sql);

                                    if (pg_num_rows($res) == 0) {

                                        $xlsResp[] = array($key,$atendimento,"Providencia ".strtoupper($providencia)." nao encontrada");
                                        $erro++;

                                    } else {

                                        $hd_motivo_ligacao  = pg_fetch_result($res, 0, 'hd_motivo_ligacao');
                                        $desc_motivo_ligacao    = pg_fetch_result($res, 0, 'descricao');

                                        $sql = "select fn_calcula_previsao_retorno(current_date,prazo_dias,$login_fabrica)::date AS data_providencia from tbl_hd_motivo_ligacao where hd_motivo_ligacao = {$hd_motivo_ligacao}";
                                        $resP = pg_query($con,$sql);
                                        $data_providencia = pg_fetch_result($resP, 0, 'data_providencia');

                                        $sql = "UPDATE tbl_hd_chamado SET data_providencia = '{$data_providencia} 00:00:00' WHERE hd_chamado = {$hd_chamado}";
                                        $res = pg_query($con,$sql);
                                        if ($login_fabrica == 151) {
                                            $sql_prov = "SELECT hd_motivo_ligacao FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado;";
                                            $res_prov = pg_query($con,$sql_prov);

                                            if (pg_num_rows($res_prov) > 0) {
                                                $hd_motivo_ligacao_ant = pg_fetch_result($res_prov, 0, hd_motivo_ligacao);
                                            }
                                        }

                                        $sql = "UPDATE tbl_hd_chamado_extra SET hd_motivo_ligacao = '{$hd_motivo_ligacao}' WHERE hd_chamado = {$hd_chamado}";
                                        $res = pg_query($con,$sql);

                                        if($login_fabrica == 151 && ($hd_motivo_ligacao_ant != $hd_motivo_ligacao)) {

                                            $sql = "INSERT INTO tbl_hd_chamado_item(
                                                hd_chamado   ,
                                                data         ,
                                                comentario   ,
                                                admin        ,
                                                interno      ,
                                                status_item
                                            )
                                            SELECT  tbl_hd_chamado.hd_chamado,
                                            NOW(),
                                            E'A providência do atendimento foi alterada por <b>$login_login</b> de <b> $desc_motivo_ligacao_ant </b> para <b>$desc_motivo_ligacao</b>',
                                            $login_admin,
                                            't',
                                            tbl_hd_chamado.status
                                            FROM tbl_hd_chamado
                                            JOIN tbl_admin ON tbl_hd_chamado.atendente = tbl_admin.admin
                                            AND tbl_admin.fabrica = {$login_fabrica}
                                            WHERE tbl_hd_chamado.hd_chamado IN({$hd_chamado})
                                            AND tbl_hd_chamado.fabrica = {$login_fabrica}";
                                            $resP = pg_query($con,$sql);
                                            $msg_erro .= pg_errormessage($con);

                                            $sql = "SELECT descricao, texto_email, texto_email_admin, texto_sms
                                                FROM tbl_hd_motivo_ligacao
                                                WHERE hd_motivo_ligacao = {$hd_motivo_ligacao}
                                                AND fabrica = {$login_fabrica}";
                                            $resP = pg_query($con,$sql);

                                            $texto_email = pg_fetch_result($resP, 0, 'texto_email');
                                            $texto_email_admin = pg_fetch_result($resP, 0, 'texto_email_admin');
                                            $texto_sms   = pg_fetch_result($resP, 0, 'texto_sms');
                                            $desc_motivo_ligacao = pg_fetch_result($resP, 0, 'descricao');

                                            $texto_email = ($texto_email == "null") ? "" : trim($texto_email);
                                            $texto_email_admin = ($texto_email_admin == "null") ? "" : trim($texto_email_admin);
                                            $texto_sms   = ($texto_sms == "null") ? "" : trim($texto_sms);

                                            $sql_dados_cliente = "SELECT nome, email from tbl_hd_chamado_extra where hd_chamado = $hd_chamado";
                                            $qry = pg_query($con, $sql_dados_cliente);

                                            if (pg_num_rows($qry) == 0) {
                                                $xlsResp[] = array($key,$atendimento,'Nao foi possivel enviar interacao por e-mail - e-mail do consumidor nao cadastrado.');
                                            } else {
                                                $consumidor_nome = pg_fetch_result($qry, 0, 'nome');
                                                $consumidor_email = pg_fetch_result($qry, 0, 'email');
                                            }    

                                            if(strlen($texto_email) > 0){
                                                $texto_email = textoProvidencia_new($texto_email,$hd_chamado,$consumidor_nome);
                                            }

                                            if(strlen($texto_email_admin) > 0){
                                                $texto_email_admin = textoProvidencia_new($texto_email_admin,$hd_chamado,$consumidor_nome);
                                            }

                                            if(strlen($texto_sms) > 0){
                                                $texto_sms   = textoProvidencia_new($texto_sms,$hd_chamado,$consumidor_nome);
                                            }

                                            $enviou = false;
                                            $enviou_email = '';
                                            $enviou_sms = '';
                                            if (strlen($texto_email) > 0) {

                                                $mensagem_email = (strlen($texto_email) > 0) ? $texto_email : $_POST['resposta'];

                                                if($login_fabrica == 151){
                                                    $msg = $texto_email;
                                                }else{
                                                    $msg = 'Prezado ' . $consumidor_nome . ",\n\n" . $mensagem_email;
                                                }

                                                $nome_fab = '';
                                                if ($login_fabrica == '80') {
                                                    $nome_fab = 'Amvox';
                                                }else{
                                                    $nome_fab = $login_fabrica_nome;
                                                }
                                                $mail = new TcComm($externalId);

                                                if ($mail->sendMail($consumidor_email, 'Protocolo de Atendimento ' . $nome_fab . ' '. $hd_chamado, $msg, $externalEmail)) {
                                                    $enviou_email = 'e-mail ';
                                                    $enviou = true;
                                                }
                                            }

                                            if (!empty($_POST['enviar_por_sms']) OR ($login_fabrica == 151 AND strlen($texto_sms) > 0)) {
                                                $sql = "SELECT celular from tbl_hd_chamado_extra where hd_chamado = $hd_chamado";
                                                $qry = pg_query($con, $sql);

                                                if (pg_num_rows($qry) == 0) {
                                                    $xlsResp[] = array($key,$atendimento,'Nao foi possivel enviar interacao por SMS - celular do consumidor nao cadastrado.');
                                                } else {
                                                    $consumidor_celular = pg_fetch_result($qry, 0, 'celular');

                                                    require_once '../class/sms/sms.class.php';
                                                    $sms = new SMS();

                                                    $nome_fab = ($login_fabrica == 80) ? 'Amvox' : $login_fabrica_nome;

                                                    $sms_msg = ($texto_sms) ? : $_POST['resposta'];
                                                    $sms_msg = ($login_fabrica == 151) ?
                                                        "Protocolo de Atendimento $nome_fab $hd_chamado. " . $sms_msg :
                                                        $sms_msg;

                                                    if ($sms->enviarMensagem($consumidor_celular, $sua_os, '', $sms_msg)) {
                                                        $enviou_sms = (empty($enviou_email)) ? 'SMS ' : 'e SMS ';
                                                        $enviou = true;
                                                    }
                                                }
                                            }

                                            if (true === $enviou) {
                                                $interacao = 'Foi enviado ' . $enviou_email . $enviou_sms . 'para o consumidor';
                                                $ins = "INSERT INTO tbl_hd_chamado_item (hd_chamado, comentario, admin, interno) VALUES ($hd_chamado, '$interacao', $login_admin, 't')";
                                                $qry = pg_query($con, $ins);
                                            }
                                        }
                                        
					$data_campo = '';
					$data_valor = '';

                                        if ($login_fabrica != 151) {
                                            $data_campo = 'data         ,';
                                            $data_valor = "'$data_interacao',";
                                        }

                                        $sql = "INSERT INTO tbl_hd_chamado_item(
                                            hd_chamado   ,
                                            $data_campo
                                            comentario   ,
                                            admin        ,
                                            interno      ,
                                            status_item
                                        ) VALUES(
                                            $hd_chamado,
                                            $data_valor   
                                            '$texto',
                                                {$login_admin},
                                                't',
                                                '$status'
                                            )";

                                        $res = pg_query($con,$sql);
                                        $msg_erro .= pg_errormessage($con);
                                        if (strlen($msg_erro) > 0) {

                                            $xlsResp[] = array($key,$atendimento,"Erro ao interagir no atendimento {$atendimento}");
                                            $erro++;

                                        }

                                    }

                                }

                            }

                            if($erro > 0){
                                $res = pg_query($con,"ROLLBACK");

                            } else {
                                $res = pg_query($con,"COMMIT");
                                $xlsResp[] = array($key,$atendimento,"Atendimento atualizado com sucesso");

                                if ($hd_motivo_ligacao_ant != $hd_motivo_ligacao ) {
                                    $sql_email = "SELECT destinatarios
                                                    FROM tbl_hd_motivo_ligacao
                                                    WHERE destinatarios is not null
                                                        AND fabrica = {$login_fabrica}
                                                        AND hd_motivo_ligacao = $hd_motivo_ligacao;";
                                    $res_email = pg_query($con,$sql_email);

                                    if (pg_num_rows($res_email) > 0) {
                                        $destinatario = pg_fetch_result($res_email, 0, 'destinatarios');
                                        $destinatario = json_decode($destinatario,true);

                                        $destinatario = implode(";", $destinatario);

                                    }
                                    $text =  "Providência Alterada!";

                                    $mail = new TcComm($externalId);

                                    $mail->sendMail($email_cliente,utf8_encode('Alteração de providência no atendimento '.$hd_chamado),utf8_encode($texto_email_admin), $externalEmail);
                                }
                            }
                        }
                    }
                }

                /*
                 * - Início da formatação do arquivo
                 * para mostrar os status do Upload realizado
                 */

                $xlsdata    = date ("dmY_His");
                $arqResp    = "resp_manutencao_atendimentos_$xlsdata.csv";
                $cabecalho  = "LINHA;ATENDIMENTO;DESCRICAO\n";

                $fp = fopen ("/tmp/assist/$arqResp",'a');
                fputs($fp,$cabecalho);

                foreach ($xlsResp as $linhas) {
                    $linha = implode(";",$linhas);
                    fputs($fp,$linha."\n");
                }
                fclose($fp);
                rename("/tmp/assist/$arqResp","xls/$arqResp");
                $retorno = array(
                    "statuss"   => "ok",
                    "mensagem"  => "Arquivo importado com sucesso, confira o arquivo com os resultados",
                    "caminho"   => "xls/$arqResp"
                );
            }
        } else {

            $retorno = array("statuss" => "error","mensagem" => 'Selecione um arquivo');

        }
        $ret = json_encode($retorno);
        echo utf8_decode($ret);
        exit;

    }
