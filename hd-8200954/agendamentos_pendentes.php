<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (isset($_REQUEST['ajax_cancelar_visita'])) {
    $os_revenda             = $_REQUEST['os_revenda'];
    $tecnico_agenda         = $_REQUEST['tecnico_agenda'];
    $motivo_cancelamento    = $_REQUEST['motivo_cancelamento'];
    $login_fabrica          = $_REQUEST['login_fabrica'];
    $admin_cancelamento     = $_REQUEST["admin_cancelamento"];

    if (empty($motivo_cancelamento)){
        $retorno = array(
            "erro" => 1,
            "msg" => utf8_encode("Informe o motivo do cancelamento")
        );
    }
    
    if (strlen(trim($retorno["msg"])) == 0) {
        if (!empty($admin_cancelamento)){
            $cond_admin_cancelado = ", admin_cancelado = $admin_cancelamento";
        }

        if ($login_fabrica == 178){
            $cond_update = "AND os_revenda = $os_revenda";
        }else{
            $cond_update = "AND os = $os";
        }

        $sql_up = "
            UPDATE tbl_tecnico_agenda 
            SET justificativa_cancelado = '$motivo_cancelamento',
            data_cancelado = now()
            $cond_admin_cancelado
            WHERE tecnico_agenda = $tecnico_agenda 
            AND fabrica = $login_fabrica 
            $cond_update";
        $res_up = pg_query($con, $sql_up);

        if (strlen(trim(pg_last_error())) > 0){
            $retorno = array(
                "erro" => 1,
                "msg" => utf8_encode("Erro ao cancelar agendamento #C2")
            );
        }else{
            $retorno = array(
                "sucesso" => 1,
                "msg" => "Agendamento cancelado com sucesso.",
                "dados" => array("acao" => "update")
            );
        }
    }
    exit(json_encode($retorno));
}

if (isset($_REQUEST['ajax_confirmar_visita'])) {
    $posto                  = $_REQUEST['posto'];
    $os_revenda             = $_REQUEST['os_revenda'];
    $tecnico_agenda         = $_REQUEST['tecnico_agenda'];
    $data_confirmacao       = $_REQUEST['data_confirmacao'];
    $data_agendada          = $_REQUEST['data_agendada'];
    $login_fabrica          = $_REQUEST['login_fabrica'];
    $os                     = $_REQUEST['os'];

    if (!empty($data_confirmacao) AND !empty($data_agendada)){
        list($dc, $mc, $yc) = explode("/", $data_confirmacao);
        list($da, $ma, $ya) = explode("/", $data_agendada);

        if (!checkdate($mc, $dc, $yc) or !checkdate($ma, $da, $ya)) {
            $retorno = array(
                "erro" => 1,
                "msg" => utf8_encode("Data confirmação inválida")
            );
        } else {
            $aux_data_confirmacao = "{$yc}-{$mc}-{$dc}";
            $aux_data_agendada   = "{$ya}-{$ma}-{$da}";

            if (strtotime($aux_data_confirmacao) < strtotime($aux_data_agendada)) {
               $retorno = array(
                    "erro" => 1,
                    "msg" => utf8_encode("Data confirmação não pode ser menor que a data de agendamento.")
                );
            }
        }
    }else{
        $retorno = array(
            "erro" => 1,
            "msg" => utf8_encode("Erro ao confirmar agendamento #C1")
        );
    }

    if (strlen(trim($retorno["msg"])) == 0) {

        if ($login_fabrica == 178){
            $cond_update = "AND os_revenda = $os_revenda";
        }else{
            $cond_update = "AND os = $os";
        }

        $sql_up = "
            UPDATE tbl_tecnico_agenda 
            SET confirmado = '$aux_data_confirmacao'
            WHERE tecnico_agenda = $tecnico_agenda 
            AND fabrica = $login_fabrica 
            $cond_update";
        $res_up = pg_query($con, $sql_up);

        if (strlen(trim(pg_last_error())) > 0){
            $retorno = array(
                "erro" => 1,
                "msg" => utf8_encode("Erro ao confirmar agendamento #C2")
            );
        }else{
            $retorno = array(
                "sucesso" => 1,
                "msg" => "Agendamento confirmado com sucesso.",
                "dados" => array("acao" => "update")
            );
        }
    }
    exit(json_encode($retorno));
}

if (isset($_REQUEST['ajax_reagendar_os'])) {

    $tecnico                = $_REQUEST['tecnico'];
    $posto                  = $_REQUEST['posto'];
    $tecnico_agenda         = $_REQUEST['tecnico_agenda'];
    $os                     = $_REQUEST['os'];
    $data_agendamento_novo  = $_REQUEST['data_agendamento_novo'];
    $login_fabrica          = $_REQUEST['login_fabrica'];
    $periodo                = $_REQUEST['periodo'];
    #$obs                    = utf8_decode($_REQUEST['obs_motivo_agendamento']);
    $obs                    = $_REQUEST['obs_motivo_agendamento'];
    $xhd_chamado            = $_REQUEST['hd_chamado'];
    $justificativa          = $_REQUEST['justificativa'];
    $os_revenda             = $_REQUEST["os_revenda"];
    $auditoria_visita       = "false";
    
    if ($login_fabrica == 178){// utilizada na tela de auditoria
        $admin_agendamento  = $_REQUEST["admin_agendamento"];
        $programa           = $_REQUEST["programa"];
        $admin_agendamento  = $_REQUEST["admin_agendamento"];
    }
    
    if ($login_fabrica == 178 AND !empty($programa) AND $programa == "auditoria"){
        if (empty($obs)){
            $retorno = array(
                "erro" => 1,
                "msg" => utf8_encode("Preencha o motivo do agendamento para confirmar o agendamento.")
            );
        }

        if (empty($data_agendamento_novo)){
            $retorno = array(
                "erro" => 1,
                "msg" => utf8_encode("Preencha a data do agendamento para continuar.")
            );
        }
        if (strlen(trim($retorno["msg"])) == 0) {
            $countAgenda = "SELECT COUNT(*) FROM tbl_tecnico_agenda WHERE fabrica = {$login_fabrica} AND os_revenda = {$os_revenda}";
            $resCountAgenda = pg_query($con,$countAgenda);
            $ordem = pg_fetch_result($resCountAgenda, 0, 0);
            $ordem += 1;
            
            $obs = addslashes($obs);
            list($di, $mi, $yi) = explode("/", $data_agendamento_novo);
            $xdata_agendamento_novo = "{$yi}-{$mi}-{$di}";
            
            $sql = "
                INSERT INTO tbl_tecnico_agenda (
                    fabrica,
                    os_revenda,
                    data_agendamento,
                    confirmado,
                    ordem,
                    admin,
                    obs
                ) VALUES (
                    {$login_fabrica},
                    {$os_revenda},
                    '{$xdata_agendamento_novo}',
                    '{$xdata_agendamento_novo}',
                    $ordem,
                    $admin_agendamento,
                    E'{$obs}'
                )
            ";
            $res = pg_query($con,$sql);

            if (strlen(pg_last_error()) > 0){
                $retorno = array(
                    "erro" => 1,
                    "msg" => utf8_encode("Erro ao gravar agendamento.")
                );
            }else{
                $retorno = array(
                    "sucesso" => 1,
                    "msg" => "Agendamento confirmado com sucesso.",
                    "dados" => array(
                        "acao" => "insert", 
                        "ordem" => $ordem,
                        "data_agendamento_novo" => "$data_agendamento_novo", 
                        "data_confirmacao" => "$data_agendamento_novo", 
                        "motivo" => utf8_encode($obs)
                    )
                );
            }
        }
    }else{
        if ($periodo == "manha") {
            $xperiodo = "Manhã";
        } else if ($periodo == "tarde") {
            $xperiodo = "Tarde";
        }

        if (in_array($login_fabrica,array(169,170,171,178,183,190,195))) {
            if (empty($tecnico)) {
                $retorno = array(
                    "erro" => 1,
                    "msg" => utf8_encode("Selecione um técnico para confirmar o agendamento.")
                );
            }
            if (empty($obs)){
                $retorno = array(
                    "erro" => 1,
                    "msg" => utf8_encode("Preencha o motivo do reagendamento para confirmar o agendamento.")
                );
            }
        }

        if ($login_fabrica == 35) {
            if (empty($justificativa)) {
                $retorno = array(
                    "erro"  => 1,
                    "msg"   => utf8_encode("Preencha a justificativa do agendamento da visita.")
                );
            }
        }

        if (empty($periodo)){
            $retorno = array(
                "erro" => 1,
                "msg" => utf8_encode("Selecione um período para confirmar o agendamento.")
            );
        }

        if (strlen(trim($retorno["msg"])) == 0) {
            if ($login_fabrica == 178){
                $cond_os_osr = "AND os_revenda = $os_revenda";
            }else{
                $cond_os_osr = "AND os = $os";
            }
            
            $sql = "
                SELECT  
                    TO_CHAR(data_agendamento, 'DD/MM/YYYY') AS data_agendamento,
                    TO_CHAR(data_cancelado, 'DD/MM/YYYY') AS data_cancelado,
                    TO_CHAR(confirmado, 'DD/MM/YYYY') AS confirmado
                FROM    tbl_tecnico_agenda
                WHERE   fabrica = $login_fabrica
                {$cond_os_osr}
                ORDER BY      tecnico_agenda DESC
                LIMIT   1";
            $res = pg_query($con, $sql);

            $data_agendamento = pg_fetch_result($res, 0, 'data_agendamento');
            $data_cancelado   = pg_fetch_result($res, 0, 'data_cancelado');
            $data_confirmado  = pg_fetch_result($res, 0, 'confirmado');

            if (in_array($login_fabrica, array(178,183))){
                if (empty($data_cancelado) AND empty($data_confirmado)){
                    $retorno = array(
                        "erro" => 1,
                        "msg" => utf8_encode("Para realizar um novo agendamento Confirme ou Cancele a visita anterior")
                    );
                }
            }

            if ($data_agendamento == $data_agendamento_novo AND !in_array($login_fabrica, array(35,178,183))) {
                $data_agendamento_novo = null;
            }

            pg_query($con, 'BEGIN;');

            if (!empty($data_agendamento_novo) || $login_fabrica == 35) {
                
                list($di, $mi, $yi) = explode("/", $data_agendamento_novo);
                if (!checkdate($mi, $di, $yi)) {
                    $retorno = array(
                        "erro" => 1,
                        "msg" => utf8_encode("Data reagendamento Inválida.")
                    );
                } else {
                    $data_atual = date("Y-m-d");
                    $xdata_agendamento_novo = "{$yi}-{$mi}-{$di}";

                    if (in_array($login_fabrica, array(169,170))) {
                        $sql = "SELECT '$data_atual'::date + interval '5 days'";
                        $res = pg_query($con,$sql);
                        $data_valida = pg_fetch_result($res, 0, 0);

                        $sqlX = "SELECT '$xdata_agendamento_novo' > '$data_valida'";
                        $resX = pg_query($con,$sqlX);

                        $xvalida_data = pg_fetch_result($resX,0,0);
                        if($xvalida_data == 't'){
                            $retorno = array(
                                "erro" => 1,
                                "msg" => utf8_encode("Data reagendamento deve ser no maxímo de 5 dias.")
                            );
                        }
                    }
                }

                if (strlen(trim($retorno["msg"])) == 0) {
                    $countAgenda = "SELECT COUNT(*) FROM tbl_tecnico_agenda WHERE fabrica = {$login_fabrica} {$cond_os_osr}";
                    $resCountAgenda = pg_query($con,$countAgenda);
                    $ordem = pg_fetch_result($resCountAgenda, 0, 0);
                    $ordem += 1;
                    $obs = addslashes($obs);

                    if (in_array($login_fabrica,array(169,170,171,190,195))) {
                        $campos = "confirmado, tecnico, obs";
                        $valor = "now(), {$tecnico}, E'{$obs}'";
                    }

                    if ($login_fabrica == 178){
                        $sqlVisitaRealizada = "
                            SELECT COUNT(*) FROM tbl_tecnico_agenda
                            WHERE fabrica = $login_fabrica AND os_revenda = $os_revenda 
                            AND data_cancelado IS NULL AND confirmado IS NOT NULL ";
                        $resVisitaRealizada = pg_query($con, $sqlVisitaRealizada);
                        $count_confirmado = pg_fetch_result($resVisitaRealizada, 0, 0);
                        $count_confirmado += 1;

                        $sqlAudVisita = "
                            SELECT tbl_auditoria_os_revenda.auditoria_status, tbl_auditoria_os_revenda.liberada
                            FROM tbl_auditoria_os_revenda
                            INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os_revenda.auditoria_status
                            WHERE os_revenda = {$os_revenda}
                            AND tbl_auditoria_os_revenda.observacao ILIKE '%auditoria de VISITA%'
                            AND cancelada IS NULL
                            ORDER BY data_input DESC";
                        $resAudVisita = pg_query($con, $sqlAudVisita);
                        
                        $countAudVisita = pg_num_rows($resAudVisita);

                        if ($countAudVisita == 0){
                            if ($count_confirmado >= 3 ){
                                $sqlAudStatus = "SELECT auditoria_status FROM tbl_auditoria_status WHERE tbl_auditoria_status.fabricante = 't'";
                                $resAudStatus = pg_query($con,$sqlAudStatus);

                                if (pg_num_rows($resAudStatus) > 0){
                                    $auditoria_status = pg_fetch_result($resAudStatus, 0, 'auditoria_status');
                                
                                    $sqlInsertAud = "
                                        INSERT INTO tbl_auditoria_os_revenda (os_revenda, auditoria_status, observacao, bloqueio_pedido)
                                        VALUES ({$os_revenda}, {$auditoria_status}, 'OS em auditoria de VISITA', false);
                                    ";
                                    $resInsertAud = pg_query($con, $sqlInsertAud);

                                    $auditoria_visita = "true";
                                }
                            }
                        }else{
                            $auditoria_liberada = pg_fetch_result($resAudVisita, 0, "liberada");
                            
                            if (!empty($auditoria_liberada) && $count_confirmado >= 3){
                                $sqlAudStatus = "SELECT auditoria_status FROM tbl_auditoria_status WHERE tbl_auditoria_status.fabricante = 't'";
                                $resAudStatus = pg_query($con,$sqlAudStatus);

                                if (pg_num_rows($resAudStatus) > 0){
                                    $auditoria_status = pg_fetch_result($resAudStatus, 0, 'auditoria_status');
                                
                                    $sqlInsertAud = "
                                        INSERT INTO tbl_auditoria_os_revenda (os_revenda, auditoria_status, observacao, bloqueio_pedido)
                                        VALUES ({$os_revenda}, {$auditoria_status}, 'OS em auditoria de VISITA', false);
                                    ";
                                    $resInsertAud = pg_query($con, $sqlInsertAud);
                                    $auditoria_visita = "true";
                                }
                            }
                        }
                    }
                    if ($login_fabrica == 35) {
                        $opt = utf8_encode("Ao visitar o consumidor, confirme a visita na página inicial.");
                        if ($ordem > 1) {
                            $sqlVerificaVisita = "
                                SELECT  MAX(tbl_tecnico_agenda.tecnico_agenda) AS tecnico_agenda
                                FROM    tbl_tecnico_agenda
                                WHERE   fabrica = $login_fabrica
                                AND     os      = $os
                                AND     confirmado IS NULL
                            ";
                            $resVerificaVisita  = pg_query($con,$sqlVerificaVisita);
                            $semConfirmar       = pg_fetch_result($resVerificaVisita,0,tecnico_agenda);

                            if (!empty($semConfirmar)) {

                                $sqlApagaVisitaSemConfirmar = "DELETE FROM tbl_tecnico_agenda WHERE tecnico_agenda = $semConfirmar";
                                $resApagaVisitaSemConfirmar = pg_query($con,$sqlApagaVisitaSemConfirmar);

                                $opt = utf8_encode("Como a última visita não foi realizada, foi feito um reagendamento.");
                            }
                        }

                        $campos = "
                            confirmado,
                            justificativa
                        ";
                        $valor = "
                            NULL,
                            $justificativa
                        ";
                    }

                    if ($login_fabrica == 178){
                        $campos = "tecnico, obs";
                        $valor = "{$tecnico}, E'{$obs}'";

                        $campo_os_osr = "os_revenda,";
                        $valor_os_osr = "$os_revenda,";
                    }else{
                        if ($login_fabrica == 183){
                            $campos = "tecnico, obs";
                            $valor = "{$tecnico}, E'{$obs}'";
                        }
                        $campo_os_osr = "os,";
                        $valor_os_osr = "$os,";
                    }

                    $sql = "
                        INSERT INTO tbl_tecnico_agenda (
                            fabrica,
                            {$campo_os_osr}
                            data_agendamento,
                            ordem,
                            periodo,
                            $campos
                        ) VALUES (
                            {$login_fabrica},
                            {$valor_os_osr}
                            '{$xdata_agendamento_novo}',
                            $ordem,
                            '{$periodo}',
                            $valor
                        );
                    ";
                    $res = pg_query($con,$sql);
                    
                    if (in_array($login_fabrica,array(169,170,171))) {
                        $sql = "SELECT os FROM tbl_os WHERE os_numero = {$os}";
                        $res = pg_query($con, $sql);

                        $os_numero = pg_fetch_result($res, 0, 'os');

                        if (!empty($os_numero)) {
                            $ordem = $ordem + 1;

                            $sql = "
                                INSERT INTO tbl_tecnico_agenda (fabrica,tecnico,os,data_agendamento,confirmado,ordem,periodo,obs)
                                VALUES ({$login_fabrica},{$tecnico},{$os_numero},'{$xdata_agendamento_novo}',now(),$ordem,'{$periodo}', E'{$obs}');
                            ";
                            $res = pg_query($con,$sql);
                        }
                    }
                }
            } else {
                if (in_array($login_fabrica,array(169,170,171,190,195))) {
                    $camposUpd = "confirmado = now() , tecnico = $tecnico ";
                
                    $sql = "
                        UPDATE  tbl_tecnico_agenda
                        SET     $camposUpd
                        WHERE   tecnico_agenda = {$tecnico_agenda};";
                    $res = pg_query($con,$sql);

                    $sql = "SELECT os FROM tbl_os WHERE os_numero = {$os}";
                    $res = pg_query($con, $sql);

                    $os_numero = pg_fetch_result($res, 0, 'os');

                    if (!empty($os_numero)) {
                        $ordem = $ordem + 1;

                        $sql = "
                            INSERT INTO tbl_tecnico_agenda (fabrica,tecnico,os,data_agendamento,confirmado,ordem,periodo,obs)
                            SELECT {$login_fabrica}, tecnico, {$os_numero}, data_agendamento, confirmado, ordem, periodo, obs
                            FROM tbl_tecnico_agenda
                            WHERE tecnico_agenda = {$tecnico_agenda}
                        ";
                        $res = pg_query($con,$sql);
                    }
                }
            }

            if (strlen(trim($retorno["msg"])) == 0) {
                if (in_array($login_fabrica,array(169,170,171,190,195))) {
                    $sql = "UPDATE tbl_os SET tecnico = {$tecnico} WHERE os = {$os};";
                    $res = pg_query($con,$sql);

                    if (!empty($os_numero)) {
                        $sql = "UPDATE tbl_os SET tecnico = {$tecnico} WHERE os = {$os_numero};";
                        $res = pg_query($con,$sql);
                    }

                    if (!empty($data_agendamento_novo)) {
                        $aux_data_agendamento = $data_agendamento_novo;
                    } else {
                        $aux_data_agendamento = $data_agendamento;
                    }
                }

                if (!empty($xhd_chamado) AND $login_fabrica != 178) {
                    $sql = "
                        INSERT INTO tbl_hd_chamado_item (hd_chamado,os,posto,comentario,interno)
                        VALUES ({$xhd_chamado},{$os},{$posto},'A OS {$os} deste chamado teve a confirmação de agendamento para o dia: {$aux_data_agendamento}',true);
                    ";
                    $res = pg_query($con,$sql);
                }

                $sql_agendamento = "SELECT  TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_agendamento,
                                            TO_CHAR(tbl_tecnico_agenda.confirmado, 'DD/MM/YYYY') AS data_confirmacao,
                                            tbl_tecnico.nome AS nome_tecnico,
                                            tbl_tecnico_agenda.tecnico_agenda
                                    FROM tbl_tecnico_agenda
                                    LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}
                                    WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica} 
                                    $cond_os_osr
                                    ORDER BY tbl_tecnico_agenda.tecnico_agenda DESC";
                $res_agendamento = pg_query($con, $sql_agendamento);

                $data_agendamento_nova = pg_fetch_result($res_agendamento,0,'data_agendamento');
                $data_confirmacao = pg_fetch_result($res_agendamento,0,'data_confirmacao');
                $nome_tecnico = pg_fetch_result($res_agendamento,0,'nome_tecnico');

                if (strlen(pg_last_error()) == 0) {
                    pg_query($con, "COMMIT;");

                    if (in_array($login_fabrica, array(169,170,178,190,195))) {

                        if ($login_fabrica == 178){
                            $sqlConRev = "
                                SELECT os_revenda
                                FROM tbl_os_revenda
                                WHERE tbl_os_revenda.fabrica = {$login_fabrica}
                                AND consumidor_revenda = 'C'
                                AND os_revenda = {$os_revenda}
                            ";
                        }else{
                            $sqlConRev = "
                                SELECT tbl_os.os
                                FROM tbl_os
                                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
                                    AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                                WHERE tbl_tipo_atendimento.grupo_atendimento IS NOT NULL
                                AND tbl_os.fabrica = {$login_fabrica}
                                AND consumidor_revenda = 'C'
                                AND km_google IS TRUE
                                AND tbl_os.os = {$os}
                            ";
                        }
                        $resConRev = pg_query($con, $sqlConRev);
                        if(pg_num_rows($resConRev) > 0){

                            if ($login_fabrica == 178){
                                include_once 'class/ComunicatorMirror.php';
                                $comunicatorMirror = new ComunicatorMirror();
                            }else{
                                include_once 'class/sms/sms.class.php';
                                $sms = new SMS();
                            }

                            $sql_tecnico = "
                                SELECT nome FROM tbl_tecnico WHERE tecnico = {$tecnico} AND posto = {$posto}
                            ";
                            $res_tecnico = pg_query($con, $sql_tecnico);

                            if (pg_num_rows($res_tecnico) > 0){
                                $nome_tecnico = pg_fetch_result($res_tecnico, 0, 'nome');
                            }

                            if ($login_fabrica == 178){
                                $sql_celular = "
                                    SELECT osr.os_revenda, osr.consumidor_nome, osr.consumidor_email, p.nome
                                    FROM tbl_os_revenda osr
                                    JOIN tbl_posto_fabrica pf ON pf.posto = osr.posto AND pf.fabrica = $login_fabrica
                                    JOIN tbl_posto p ON p.posto = pf.posto  
                                    WHERE osr.os_revenda = $os_revenda
                                    AND osr.fabrica = $login_fabrica 
                                ";
                            }else{
                                $sql_celular = "
                                    SELECT consumidor_celular, sua_os, referencia, descricao, nome, consumidor_email
                                    FROM tbl_os
                                    JOIN tbl_produto USING(produto)
                                    JOIN tbl_posto USING(posto)
                                    WHERE os = $os
                                ";
                            }
                            $res_celular = pg_query($con, $sql_celular);

                            $envia_sms = false;

                            if (pg_num_rows($res_celular) > 0) {
                                $consumidor_celular = pg_fetch_result($res_celular, 0, 'consumidor_celular');
                                $sms_os             = pg_fetch_result($res_celular, 0, 'sua_os');
                                $sms_os_revenda     = pg_fetch_result($res_celular, 0, 'os_revenda');
                                $sms_produto        = pg_fetch_result($res_celular, 0, 'referencia') . ' - ' . pg_fetch_result($res_celular, 0, 'descricao');
                                $sms_posto          = pg_fetch_result($res_celular, 0, 'nome');
                                $consumidor_email   = pg_fetch_result($res_celular, 0, 'consumidor_email');

                                if ($login_fabrica == 178){
                                    if (!empty($consumidor_email)){
                                        $titulo_email = "Agendamento Ordem Serviço - $sms_os_revenda";
                                        $corpo_email = "Informamos que o Posto Autorizado $sms_posto, agendou a visita do Técnico: $nome_tecnico " .
                                        "para reparar o seu produto no dia: $aux_data_agendamento no período da: $xperiodo .";
                                        try {
                                            $comunicatorMirror->post($consumidor_email, utf8_encode("$titulo_email"), utf8_encode("$corpo_email"), "smtp@posvenda");
                                        } catch (\Exception $e) {
                                        }
                                    }
                                }else{
                                    if (!empty($consumidor_celular)) {
                                        $envia_sms = true;
                                    }

                                    if (true === $envia_sms) {
                                        $fabnome = $sms->nome_fabrica;

                                        $sms_msg = "Produto $fabnome - OS $sms_os. " .
                                        "Informamos que o Posto Autorizado $sms_posto, agendou a visita do Técnico: $nome_tecnico " .
                                        "para reparar o seu produto no dia: $aux_data_agendamento no período da: $xperiodo";
                                        $sms->enviarMensagem($consumidor_celular, $sms_os, '', $sms_msg);
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($data_agendamento_novo)) {
                        $retorno = array(
                            "sucesso" => 1,
                            "msg" => "Agendamento confirmado com sucesso.",
                            "opt" => $opt,
                            "auditoria_visita" => "$auditoria_visita",
                            "dados" => array("acao" => "insert", "data_agendamento_nova" => "$data_agendamento_nova", "data_confirmacao" => "$data_confirmacao", "nome_tecnico" => "$nome_tecnico")
                        );
                    }else{
                        $retorno = array(
                            "sucesso" => 1,
                            "msg" => "Agendamento confirmado com sucesso.",
                            "dados" => array("acao" => "update")
                        );
                    }

                } else {
                    $retorno = array(
                        "erro" => 1,
                        "msg" => "Ocorreu um erro ao efetuar o agendamento." . pg_last_error()
                    );
                    pg_query($con, "ROLLBACK;");
                }
            }
        }
    }
    exit(json_encode($retorno));
}

if (isset($_REQUEST['ajax_confirmar_agendamento'])) {

    $tecnico                = $_REQUEST['tecnico'];
    $posto                  = $_REQUEST['posto'];
    $tecnico_agenda         = $_REQUEST['tecnico_agenda'];
    $os                     = $_REQUEST['os'];
    $os_revenda             = $_REQUEST["os_revenda"];
    $data_agendamento       = $_REQUEST['data_agendamento'];
    $data_agendamento_novo  = $_REQUEST['data_agendamento_novo'];
    $login_fabrica          = $_REQUEST['login_fabrica'];
    $periodo                = $_REQUEST['periodo'];
    $periodo_novo           = $_REQUEST['periodo_novo'];
    $obs                    = $_REQUEST['obs'];
    $justificativa          = $_REQUEST['justificativa'];

    $obs = utf8_decode($obs);

    if ($data_agendamento == $data_agendamento_novo && $periodo == $periodo_novo) {
        $data_agendamento_novo = "";
    }

    if ($periodo_novo == "manha") {
        $xperiodo = "Manhã";
    } else if ($periodo_novo == "tarde") {
        $xperiodo = "Tarde";
    }

    if (in_array($login_fabrica,array(169,170,171,190,195))) {
        if (empty($tecnico_agenda) || empty($tecnico)) {
            $retorno = array(
                "erro" => 1,
                "msg" => utf8_encode("Selecione um técnico para confirmar o agendamento.")
            );
        }
    }

    if (in_array($login_fabrica, array(178,183)) AND empty($tecnico)){
        $retorno = array(
            "erro" => 1,
            "msg" => utf8_encode("Selecione um técnico para confirmar o agendamento.")
        );
    }

    if (in_array($login_fabrica, array(178,183)) AND empty($data_agendamento_novo)){
        $retorno = array(
            "erro" => 1,
            "msg" => utf8_encode("Selecione uma data para agendamento.")
        );
    }

    if ($login_fabrica == 35) {
        if (empty($justificativa)) {
            $retorno = array(
                "erro"  => 1,
                "msg"   => utf8_encode("Preencha a justificativa do agendamento da visita.")
            );
        }
    }

    if ($data_agendamento == $data_agendamento_novo && $login_fabrica != 35) {
        $data_agendamento_novo = null;
    }

    if (strlen(trim($retorno["msg"])) == 0) {
        pg_query($con, 'BEGIN;');

        if (!empty($data_agendamento_novo)) {
            list($di, $mi, $yi) = explode("/", $data_agendamento_novo);
            if (!checkdate($mi, $di, $yi)) {
                $retorno = array(
                    "erro" => 1,
                    "msg" => utf8_encode("Data reagendamento Inválida.")
                );
            } else {
                $data_atual = date("Y-m-d");
                $xdata_agendamento_novo = "{$yi}-{$mi}-{$di}";

                if (in_array($login_fabrica, array(169,170))) {
                    $sql = "SELECT '$data_atual'::date + interval '5 days'";
                    $res = pg_query($con,$sql);
                    $data_valida = pg_fetch_result($res, 0, 0);

                    $sqlX = "SELECT '$xdata_agendamento_novo' > '$data_valida'";
                    $resX = pg_query($con,$sqlX);

                    $xvalida_data = pg_fetch_result($resX,0,0);
                    if($xvalida_data == 't'){
                        $retorno = array(
                            "erro" => 1,
                            "msg" => utf8_encode("Data reagendamento deve ser no maxímo de 5 dias.")
                        );
                    }

                    if (strlen(trim($obs)) == 0) {
                        $retorno = array(
                            "erro" => 1,
                            "msg" => utf8_encode("O campo motivo do reagendamento é obrigatório.")
                        );
                    }
                }
            }

            if (empty($periodo_novo)){
                $retorno = array(
                    "erro" => 1,
                    "msg" => utf8_encode("Selecione um período para para confirmar o agendamento.")
                );
            }

            if (strlen(trim($retorno["msg"])) == 0){
                if ($login_fabrica == 178){
                    $cond_os_osr = " AND os_revenda = {$os_revenda} ";
                }else{
                    $cond_os_osr = " AND os = {$os} ";
                }

                $countAgenda = "SELECT COUNT(*) FROM tbl_tecnico_agenda WHERE fabrica = {$login_fabrica} $cond_os_osr";
                $resCountAgenda = pg_query($con,$countAgenda);

                $ordem = pg_fetch_result($resCountAgenda, 0, 0);
                $ordem += 1;

                if (in_array($login_fabrica,array(169,170,171,190,195))) {
                    $campos = ", confirmado, tecnico, obs ";
                    $valor = ", now(), {$tecnico}, E'{$obs}' ";
                }else if ($login_fabrica == 183){
                    $campos = ", tecnico";
                    $valor = ", {$tecnico}";
                }

                if ($login_fabrica == 35) {
                    $opt = utf8_encode("Ao visitar o consumidor, confirme a visita na página inicial.");
                    if ($ordem > 1) {
                        $sqlVerificaVisita = "
                            SELECT  MAX(tbl_tecnico_agenda.tecnico_agenda) AS tecnico_agenda
                            FROM    tbl_tecnico_agenda
                            WHERE   fabrica = $login_fabrica
                            AND     os      = $os
                            AND     confirmado IS NULL
                        ";
                        $resVerificaVisita  = pg_query($con,$sqlVerificaVisita);
                        $semConfirmar       = pg_fetch_result($resVerificaVisita,0,tecnico_agenda);

                        if (!empty($semConfirmar)) {

                            $sqlApagaVisitaSemConfirmar = "DELETE FROM tbl_tecnico_agenda WHERE tecnico_agenda = $semConfirmar";
                            $resApagaVisitaSemConfirmar = pg_query($con,$sqlApagaVisitaSemConfirmar);

                            $opt = utf8_encode("Como a última visita não foi realizada, foi feito um reagendamento.");
                        }
                    }

                    $campos = "
                        confirmado,
                        justificativa
                    ";
                    $valor = "
                        NULL,
                        $justificativa
                    ";
                }

                if ($login_fabrica == 178){
                    $sql = "
                        INSERT INTO tbl_tecnico_agenda (fabrica, tecnico, obs, os_revenda, data_agendamento, ordem, periodo)
                        VALUES ({$login_fabrica}, $tecnico, E'$obs' , {$os_revenda}, '{$xdata_agendamento_novo}', $ordem, '$periodo_novo');
                    ";
                    $res = pg_query($con,$sql);
                }else{
                    $sql = "
                        INSERT INTO tbl_tecnico_agenda (fabrica $campos ,os,data_agendamento,ordem, periodo)
                        VALUES ({$login_fabrica} $valor,{$os},'{$xdata_agendamento_novo}',$ordem, '$periodo_novo');
                    ";
                    $res = pg_query($con,$sql);
                }
                
                if (in_array($login_fabrica,array(169,170,171))) {
                    $sql = "SELECT os FROM tbl_os WHERE os_numero = {$os}";
                    $res = pg_query($con, $sql);

                    $os_numero = pg_fetch_result($res, 0, 'os');

                    if (!empty($os_numero)) {
                        $ordem = $ordem + 1;

                        $sql = "
                            INSERT INTO tbl_tecnico_agenda (fabrica,tecnico,os,data_agendamento,confirmado,ordem,periodo,obs)
                            VALUES ({$login_fabrica},{$tecnico},{$os_numero},'{$xdata_agendamento_novo}',now(),$ordem,'{$periodo_novo}', E'{$obs}');
                        ";
                        $res = pg_query($con,$sql);
                    }
                }
            }
        } else {
            if (in_array($login_fabrica,array(169,170,171,183,190,195))) {
                $campos = "
                    tecnico = {$tecnico},
                    obs = '$obs',
                ";
            }
            $sql = "
                UPDATE  tbl_tecnico_agenda
                SET     confirmado = now(),
                        $campos
                        periodo = '$periodo_novo'
                WHERE   tecnico_agenda = {$tecnico_agenda};";
            $res = pg_query($con,$sql);

            $sql = "SELECT os FROM tbl_os WHERE os_numero = {$os}";
            $res = pg_query($con, $sql);

            $os_numero = pg_fetch_result($res, 0, 'os');

            if (!empty($os_numero)) {
                $ordem = $ordem + 1;

                $sql = "
                    INSERT INTO tbl_tecnico_agenda (fabrica,tecnico,os,data_agendamento,confirmado,ordem,periodo,obs)
                    SELECT {$login_fabrica}, tecnico, {$os_numero}, data_agendamento, confirmado, ordem, periodo, obs
                    FROM tbl_tecnico_agenda
                    WHERE tecnico_agenda = {$tecnico_agenda}
                ";
                $res = pg_query($con,$sql);
            }

            if ($login_fabrica == 35) {
                $sqlUpdOs = "
                    SELECT CASE WHEN qtde_diaria IS NULL
                                THEN 0
                                ELSE qtde_diaria
                            END AS qtde_diaria
                    FROM tbl_os WHERE os = $os
                ";
                $resUpdOs = pg_query($con,$sqlUpdOs);
                $soma_diaria = pg_fetch_result($resUpdOs,0,qtde_diaria);

                $sqlSomaDiaria = "
                    UPDATE  tbl_os
                    SET     qtde_diaria = $soma_diaria + 1
                    WHERE   os = $os";
                $resSomaDiaria = pg_query($con,$sqlSomaDiaria);
            }
        }

        if (strlen(trim($retorno["msg"])) == 0) {

            if ($login_fabrica == 178){
                $sqlUpdateOSR = "UPDATE tbl_os_revenda SET retorno_visita = 'f' WHERE os_revenda = $os_revenda AND fabrica = $login_fabrica";
                $resUpdateOSR = pg_query($con, $sqlUpdateOSR);

                $sqlStatusCk = "SELECT status_checkpoint FROM tbl_status_checkpoint WHERE descricao = 'Aguardando Analise'";
                $resStatusCk = pg_query($con, $sqlStatusCk);

                if (pg_num_rows($resStatusCk) > 0){
                    $status_checkpoint_os = pg_fetch_result($resStatusCk, 0, "status_checkpoint");

                    $sqlOsUpdate = "SELECT os FROM tbl_os_campo_extra WHERE os_revenda = $os_revenda AND fabrica = $login_fabrica";
                    $resOsUpdate = pg_query($con, $sqlOsUpdate);
                    
                    if (pg_num_rows($resOsUpdate) > 0){
                        $array_os = pg_fetch_all_columns($resOsUpdate);

                        $sqlUpdateOS = "
                            UPDATE tbl_os SET status_checkpoint = $status_checkpoint_os
                            WHERE fabrica = $login_fabrica
                            AND os IN (".implode(",", $array_os).")" ;
                        $resUpdateOS = pg_query($con, $sqlUpdateOS);
                    }
                }
            }else if ($login_fabrica == 183) {
                $sql = "UPDATE tbl_os SET off_line_reservada = 'f', tecnico = {$tecnico} WHERE fabrica = $login_fabrica AND os = $os";
                $res = pg_query($con, $sql);

                if (!empty($xdata_agendamento_novo)) {
                    $aux_data_agendamento = $data_agendamento_novo;
                } else {
                    $aux_data_agendamento = $data_agendamento;
                }

                $sql = "
                    INSERT INTO tbl_hd_chamado_item (hd_chamado,os,posto,comentario,interno)
                    VALUES ({$hd_chamado},{$os},{$posto},'A OS {$os} deste chamado teve a confirmação de agendamento para o dia: {$aux_data_agendamento}',true);
                ";
                $res = pg_query($con,$sql);

            }else{
                if ($login_fabrica != 35) {
                    $sql = "UPDATE tbl_os SET tecnico = {$tecnico} WHERE os = {$os};";
                    $res = pg_query($con,$sql);

                    if (!empty($os_numero)) {
                        $sql = "UPDATE tbl_os SET tecnico = {$tecnico} WHERE os = {$os_numero};";
                        $res = pg_query($con,$sql);
                    }

                    if (!empty($xdata_agendamento_novo)) {
                        $aux_data_agendamento = $data_agendamento_novo;
                    } else {
                        $aux_data_agendamento = $data_agendamento;
                    }
                    if (in_array($login_fabrica,array(169,170,171,190,195))) {
			if (strlen($hd_chamado) > 0) {
                        $sql = "
                            INSERT INTO tbl_hd_chamado_item (hd_chamado,os,posto,comentario,interno)
                            VALUES ({$hd_chamado},{$os},{$posto},'A OS {$os} deste chamado teve a confirmação de agendamento para o dia: {$aux_data_agendamento}',true);
                        ";
                        $res = pg_query($con,$sql);
			}
                    }
                } else {
                    /*
                     * Faz a verificação para entrada em Auditoria de Km da OS:
                     *    - Se a OS não caiu em auditoria de KM (50 Km)
                     *        + Se a multiplicação do KM percorrido com
                     *        o número de visitas confirmadas atingir 50 Km
                     *
                     *    - Se a OS já teve auditoria de KM aprovada
                     */

                    $sqlVerAud = "
                        SELECT  os          AS os_aud,
                                liberada    AS os_aud_liberada,
                                cancelada   AS os_aud_cancelada,
                                reprovada   AS os_aud_reprovada
                        FROM    tbl_auditoria_os
                        WHERE   os                  = $os
                        AND     auditoria_status    = 2
                        ORDER BY      auditoria_os DESC
                        LIMIT   1
                    ";
                    $resVerAud = pg_query($con,$sqlVerAud);

                    $os_aud           = pg_fetch_result($resVerAud,0,os_aud);
                    $os_aud_liberada  = pg_fetch_result($resVerAud,0,os_aud_liberada);
                    $os_aud_cancelada = pg_fetch_result($resVerAud,0,os_aud_cancelada);
                    $os_aud_reprovada = pg_fetch_result($resVerAud,0,os_aud_reprovada);

                    if (empty($os_aud)) {
                        $sqlVerKm = "
                            SELECT  qtde_km * qtde_diaria AS mult_km
                            FROM    tbl_os
                            WHERE   os = $os
                        ";
                        $resVerKm = pg_query($con,$sqlVerKm);
                        $mult_km = pg_fetch_result($resVerKm,0,mult_km);

                        if ($mult_km > 50) {
                            $sqlAud = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, 'Auditoria por várias visitas pelo posto ao consumidor', false, 2)";
                            $resAud = pg_query($con, $sqlAud);
                        }

                    } else {
                        if (!empty($os_aud_liberada) || !empty($os_aud_cancelada) || !empty($os_aud_reprovada)) {
                            $sqlAud = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, 'Reentrada em auditoria por nova visita do posto ao consumidor', false, 2)";
                            $resAud = pg_query($con, $sqlAud);

                        }
                    }
                }
            }
            //           exit(pg_last_error()); 
            if (!pg_last_error($con)) {
                
                pg_query($con, "COMMIT;");

                if (in_array($login_fabrica, array(169,170,178,190,195))) {
                    if ($login_fabrica == 178){
                        $sqlConRev = "
                            SELECT os_revenda
                            FROM tbl_os_revenda
                            WHERE fabrica = {$login_fabrica}
                            AND consumidor_revenda = 'C'
                            AND os_revenda = {$os_revenda}
                        ";
                    }else{
                        $sqlConRev = "
                            SELECT  tbl_os.os
                            FROM    tbl_os
                            JOIN    tbl_tipo_atendimento    ON  tbl_tipo_atendimento.tipo_atendimento   = tbl_os.tipo_atendimento
                                                            AND tbl_tipo_atendimento.fabrica            = {$login_fabrica}
                            WHERE   tbl_tipo_atendimento.grupo_atendimento IS NOT NULL
                            AND     tbl_os.fabrica = {$login_fabrica}
                            AND     consumidor_revenda = 'C'
                            AND     km_google IS TRUE
                            AND     tbl_os.os = {$os}
                        ";
                    }
                    $resConRev = pg_query($con, $sqlConRev);
                    if(pg_num_rows($resConRev) > 0){

                        if ($login_fabrica == 178){
                            include_once 'class/ComunicatorMirror.php';
                            $comunicatorMirror = new ComunicatorMirror();
                        }else{
                            include_once 'class/sms/sms.class.php';
                            $sms = new SMS();
                        }
    
                        $sql_tecnico = "
                            SELECT nome FROM tbl_tecnico WHERE tecnico = {$tecnico} AND posto = {$posto}
                        ";
                        $res_tecnico = pg_query($con, $sql_tecnico);

                        if (pg_num_rows($res_tecnico) > 0){
                            $nome_tecnico = pg_fetch_result($res_tecnico, 0, 'nome');
                        }

                        if ($login_fabrica == 178){
                            $sql_celular = "
                                SELECT osr.os_revenda, osr.consumidor_nome, osr.consumidor_email, p.nome, pf.contato_fone_comercial
                                FROM tbl_os_revenda osr
                                JOIN tbl_posto_fabrica pf ON pf.posto = osr.posto AND pf.fabrica = $login_fabrica
                                JOIN tbl_posto p ON p.posto = pf.posto  
                                WHERE osr.os_revenda = $os_revenda
                                AND osr.fabrica = $login_fabrica 
                            ";
                        }else{
                            $sql_celular = "
                                SELECT consumidor_celular, sua_os, referencia, descricao, nome, consumidor_email
                                FROM tbl_os
                                JOIN tbl_produto USING(produto)
                                JOIN tbl_posto USING(posto)
                                WHERE os = $os
                            ";
                        }
                        $res_celular = pg_query($con, $sql_celular);
                        $envia_sms = false;

                        if (pg_num_rows($res_celular) > 0) {
                            $consumidor_celular = pg_fetch_result($res_celular, 0, 'consumidor_celular');
                            $sms_os             = pg_fetch_result($res_celular, 0, 'sua_os');
                            $os_revenda_email   = pg_fetch_result($res_celular, 0, 'os_revenda');
                            $sms_produto        = pg_fetch_result($res_celular, 0, 'referencia') . ' - ' . pg_fetch_result($res_celular, 0, 'descricao');
                            $sms_posto          = pg_fetch_result($res_celular, 0, 'nome');
                            $consumidor_email   = pg_fetch_result($res_celular, 0, 'consumidor_email');
                            $contato_fone_comercial = pg_fetch_result($res_celular, 0, 'contato_fone_comercial');

                            if ($login_fabrica == 178){
                                if (!empty($consumidor_email)){
                                    $titulo_email = "Agendamento Ordem Serviço $os_revenda_email - ROCA";
                                    $corpo_email = "Informamos que o Posto Autorizado $sms_posto, agendou a visita do Técnico: $nome_tecnico " .
                                    "para reparar o seu produto no dia: $data_agendamento_novo no período da: $xperiodo . " .
                                    "Qualquer duvida entrar em contato pelo telefone: $contato_fone_comercial";
                                    
                                    try {
                                        $comunicatorMirror->post($consumidor_email, utf8_encode("$titulo_email"), utf8_encode("$corpo_email"), "smtp@posvenda");
                                    } catch (\Exception $e) {
                                    }
                                }
                            }else{
                                if (!empty($consumidor_celular)) {
                                    $envia_sms = true;
                                }

                                if (true === $envia_sms) {
                                    $fabnome = $sms->nome_fabrica;

                                    $sms_msg = "Produto $fabnome - OS $sms_os. " .
                                    "Informamos que o Posto Autorizado $sms_posto, agendou a visita do Técnico: $nome_tecnico " .
                                    "para reparar o seu produto no dia: $aux_data_agendamento no período da: $xperiodo .";
                                    $sms->enviarMensagem($consumidor_celular, $sms_os, '', $sms_msg);
                                }
                            }
                        }
                    }
                }

                if ($login_fabrica == 178){
                    $retorno = array(
                        "sucesso" => 1,
                        "msg" => "Agendado com sucesso."
                    );
                }else{
                    $retorno = array(
                        "sucesso" => 1,
                        "msg" => "Agendamento confirmado com sucesso."
                    );
                }
            } else {
                pg_query($con, "ROLLBACK;");
                $retorno = array(
                    "erro" => 1,
                    "msg" => "Ocorreu um erro ao efetuar o agendamento." . pg_last_error()
                );
            }
        }
    }
    exit(json_encode($retorno));
}

if ($login_fabrica == 178){
    $sql = "
	SELECT DISTINCT
	    os_revenda,
	    hd_chamado,
	    consumidor_nome
	FROM tbl_os_revenda
	WHERE fabrica = {$login_fabrica}
	AND posto = {$login_posto}
	AND os_revenda NOT IN (
	    SELECT os_revenda
	    FROM tbl_tecnico_agenda
	    JOIN tbl_tecnico USING(tecnico,fabrica)
	    WHERE fabrica = {$login_fabrica}
	    AND posto = {$login_posto}
	)
	AND (hd_chamado IS NOT NULL
	OR (hd_chamado IS NULL
	AND visita_por_km IS TRUE))
	AND finalizada IS NULL
	AND excluida IS NOT TRUE
    ";
}else if ($login_fabrica == 183){
    $sql = "
        WITH os_auditoria AS (
            SELECT 
                tbl_os.os 
            FROM tbl_os
            JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os
            WHERE tbl_os.fabrica = $login_fabrica
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_auditoria_os.liberada IS NULL
            AND tbl_auditoria_os.cancelada IS NULL
            AND tbl_auditoria_os.reprovada IS NULL
        )
        SELECT 
            tbl_os.os,
            tbl_os.data_abertura,
            tbl_os.consumidor_nome,
            tbl_os.hd_chamado
        FROM tbl_os
        WHERE tbl_os.fabrica = $login_fabrica
        AND tbl_os.posto = $login_posto
        AND tbl_os.off_line_reservada IS TRUE
        AND tbl_os.os NOT IN (SELECT * FROM os_auditoria)
        AND tbl_os.excluida IS NOT TRUE";
}else{
    $sql = "
        SELECT  xx.*
        FROM    (
                SELECT  DISTINCT ON(x.os) x.*,
                        ta.tecnico_agenda,
                        TO_CHAR(ta.data_agendamento,'DD/MM/YYYY') AS data_agendamento,
                        ta.confirmado,
                        ta.periodo,
                        ta.data_input,
                        ta.justificativa
                FROM    (
                            SELECT  DISTINCT
                                    ta.os,
                                    o.hd_chamado,
                                    o.consumidor_nome,
                                    o.posto,
                                    o.excluida
                            FROM    tbl_tecnico_agenda ta
                            JOIN    tbl_os o                    ON  o.os                            = ta.os
                                                                AND o.fabrica                       = {$login_fabrica}
                            JOIN    tbl_tipo_atendimento ota    ON  ota.tipo_atendimento            = o.tipo_atendimento
                            JOIN    tbl_posto_fabrica pf        ON  pf.posto                        = o.posto
                                                                AND pf.fabrica                      = o.fabrica
                       LEFT JOIN    tbl_os_troca ot             ON  ot.os                           = o.os
                                                                AND ot.fabric                       = {$login_fabrica}
                            WHERE   ta.fabrica          = {$login_fabrica}
                            AND     o.posto             = {$login_posto}
                            AND     ta.confirmado       IS NULL
                            AND     o.finalizada        IS NULL
                            AND     o.excluida          IS NOT TRUE
                            AND     ot.os_troca         IS NULL
                            AND     ota.km_google       IS TRUE
                            AND     pf.credenciamento   = 'CREDENCIADO'
                        ) x
                JOIN    tbl_tecnico_agenda ta ON ta.os = x.os
          ORDER BY      x.os,
                        ta.tecnico_agenda DESC
                ) xx
        WHERE   xx.confirmado IS NULL ";  
}

$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
    $sqlTecnico = "SELECT * FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND ativo IS TRUE";
    $resTecnico = pg_query($con,$sqlTecnico);
    $countTecnico = pg_num_rows($resTecnico); ?>
    <style type="text/css">

        .btn_confirmar{
            display: inline-block;
            padding: 4px 12px;
            margin-bottom: 0;
            color: #333333;
            text-align: center;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
            vertical-align: middle;
            cursor: pointer;
            background-color: #f5f5f5;
            background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
            background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
            background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
            background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
            background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
            background-repeat: repeat-x;
            border: 1px solid #bbbbbb;
            border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
            border-color: #e6e6e6 #e6e6e6 #bfbfbf;
            border-bottom-color: #a2a2a2;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
            filter: progid:dximagetransform.microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe6e6e6', GradientType=0);
            filter: progid:dximagetransform.microsoft.gradient(enabled=false);
            -webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
            -moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .class_select{
            background: white;
            display: inline-block;
            height: 25px !important;
            padding: 4px 6px;
            font-size: 14px;
            line-height: 20px;
            color: #555555;
            vertical-align: middle;
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
        }
        .class_input{
            border-radius: 3px !important;
            height: 23px !important;
            width: 96px !important;
            border: none !important;
        }
        .table_tc{
            text-align: center !important;
            /*background-color: #f9f6f6 !important;*/
            background-color: #e8eff9 !important;
        }
        .titulo_tabela th{
            border: none !important;
            color: white !important;
            background-color: #494994 !important;
        }
    </style>
    <script type="text/javascript" src="plugins/jquery.maskedinput_new.js"></script>
    <script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
    <script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
    <link rel="stylesheet" type="text/css" href="plugins/jquery/datepick/telecontrol.datepick.css" />
    <script type="text/javascript">
        $(function() {
	    <?php if ($login_fabrica == 183){ ?>
		$("input[name^=data_agendamento_novo_]").datepick({minDate: 0, maxDate:20, dateFormat: "dd/mm/yyyy" }).mask("99/99/9999");
	    <?php }else{ ?>
            	$("input[name^=data_agendamento_novo_]").datepick({minDate: 0,<?=(!in_array($login_fabrica, array(35,178)))? "maxDate:5," : ""?> dateFormat: "dd/mm/yyyy" }).mask("99/99/9999");
	    <?php } ?>

            $(".btn_confirmar").click(function() {
                var that                    = $(this);
                var posto                   = $('#posto').val();
                var tecnico_agenda          = $(that).data('tecnico-agenda');
                var tecnico                 = $('#tecnico_'+tecnico_agenda).val();
                var linha                   = $(".linha_agenda_"+tecnico_agenda);
                var data_agendamento        = $("#data_agendamento_"+tecnico_agenda).val();
                var data_agendamento_novo   = $("#data_agendamento_novo_"+tecnico_agenda).val();
                var hd_chamado              = $("#hd_chamado_"+tecnico_agenda).val();
                var os                      = $(that).data('os');
                var os_revenda              = $(that).data('os_revenda');
                //var login_fabrica           = <?=$login_fabrica?>;
                var periodo                 = $("#periodo_"+tecnico_agenda).val();
                var periodo_novo            = $("#periodo_novo_"+tecnico_agenda).val();
                var obs                     = $("#obs_"+tecnico_agenda).val();
                var justificativa           = $("#justificativa_"+tecnico_agenda).val();

                <?php if (in_array($login_fabrica, array(169,170,171,178,183,190,195))){ ?>
                    if (tecnico == '') {
                        alert('É necessário selecionar um técnico para efetuar o agendamento.')
                        return false;
                    }
                <?php } ?>

                <?php if (in_array($login_fabrica, array(178,183))){ ?>
                    var tr_tabela = $("#tr_"+os_revenda);
                    if(data_agendamento_novo == "" || data_agendamento_novo == undefined){
                        alert("Selecione uma data para o agendamento");
                        return false;
                    }

                    if (periodo_novo == "" || periodo_novo == undefined){
                        alert("Selecione um periodo para o agendamento");
                        return false;
                    }
                <?php } ?>
                
                <?php if ($login_fabrica == 35){ ?>
                    if (justificativa == '') {
                        alert("Selecione a Justificativa do agendamento da visita.");
                        return false;
                    }
                <?php } ?>

                if (obs == '' && data_agendamento_novo != '' && (data_agendamento != data_agendamento_novo || periodo != periodo_novo)){
                    alert('Descreva o motivo do reagendamento.')
                    return false;
                }

                if(confirm('Tem certeza que deseja confirmar o agendamento?')) {
                    $.ajax({
                        type: "POST",
                        url: "agendamentos_pendentes.php",
                        dataType:"JSON",
                        data: {
                            ajax_confirmar_agendamento: true,
                            posto: posto,
                            tecnico: tecnico,
                            os: os,
                            hd_chamado: hd_chamado,
                            tecnico_agenda: tecnico_agenda,
                            data_agendamento: data_agendamento,
                            data_agendamento_novo: data_agendamento_novo,
                            periodo: periodo,
                            login_fabrica: <?=$login_fabrica?>,
                            periodo_novo: periodo_novo,
                            obs: obs,
                            justificativa:justificativa,
                            os_revenda: os_revenda
                        },
                        beforeSend: function() {
                            $(that).text("Confirmando...").prop({ disabled: true });
                        },
                    })
                    .done(function (retorno) {
                        if (retorno.sucesso == 1) {
                            <?php if (in_array($login_fabrica, array(178,183))){ ?>
                                $(tr_tabela).hide();
                            <?php } else {?>
                                $(linha).hide();
                            <?php } ?>
                            alert(retorno.msg);
                        } else {
                            $(that).text("Confirmar").prop({ disabled: false });
                            alert(retorno.msg);
                        }
                    });
                } else {
                    return false;
                }
            });
        });
    </script>
    <table class="table_tc table-bordered" style="min-width:700px !important; max-width: 1024px !important; margin:auto !important; margin-top: 30px !important;" cellspacing="1" id="rel_agenda">
        <input type="hidden" id="posto" name="posto" value="<?= $login_posto; ?>" />
        <thead>
            <tr class="titulo_tabela">
                <th colspan="8"> <?=(in_array($login_fabrica, array(178,183))) ? "Ordens de Serviço pendentes de agendamento" : "Ordens de Serviço agendadas pendentes de confirmação" ?></th>
            </tr>
            <tr class="titulo_coluna">
                <th>OS</th>
                <th>Cliente</th>
                <?php if (!in_array($login_fabrica, array(178,183))){ ?>
                <th>Agendamento<?=(in_array($login_fabrica,array(169,170,171,190,195))) ? " Callcenter" : ""?></th>
                <?php } ?>
<?php
    if (in_array($login_fabrica,array(169,170,171,178,183,190,195))) {
?>
                <th>Técnico</th>
<?php
    }
?>
                <th><?=(in_array($login_fabrica, array(178,183))) ? "Agendar Para" : "Reagendar Para"  ?></th>
                <th><?=(in_array($login_fabrica, array(178,183))) ? "Período do Agendamento" : "Período Reagendamento"  ?></th>
<?php
    if (in_array($login_fabrica,array(169,170,171,190,195))) {
?>
                <th>Motivo Reagendamento</th>
<?php
    }
    if ($login_fabrica == 35) {
?>
                <th>Justificativa</th>
<?php
    }
?>
                <th>Opções</th>
            </tr>
        </thead>
        <tbody>
<?php
    for ($i = 0; $i < pg_num_rows($res); $i++) {
        $tecnico_agenda     = pg_fetch_result($res, $i, tecnico_agenda);
        $os                 = pg_fetch_result($res, $i, os);
        
        if ($login_fabrica == 183){
            $data_abertura      = pg_fetch_result($res, $i, data_abertura);
            $data_abertura =  date('d/m/Y', strtotime("+2 days",strtotime($data_abertura)));
            $os_revenda = $os;
        }
        
        if ($login_fabrica == 178){
            $os_revenda = pg_fetch_result($res, $i, os_revenda);
            $sua_os = $os_revenda; // se for por agendamento por OS Mae
        }
        $hd_chamado         = pg_fetch_result($res, $i, hd_chamado);
        $consumidor         = pg_fetch_result($res, $i, consumidor_nome);
        $data_agendamento   = pg_fetch_result($res, $i, data_agendamento);
        $periodo            = pg_fetch_result($res, $i, periodo);
        $justificativa      = pg_fetch_result($res, $i, justificativa);

        if ($periodo == 'manha') {
            $selected_manha = "selected";
        } else if ($periodo == 'tarde'){
            $selected_tarde = "selected";
        } else {
            $selected_manha = "";
            $selected_tarde = "";
        }
?>
                <tr class="texto_avulso linha_agenda_<?=$tecnico_agenda?>" id="tr_<?=$os_revenda?>">
                    <td>
                        <?php if ($login_fabrica == 178){ ?>
                            <a href="os_revenda_press.php?os_revenda=<?=$os_revenda?>" target="_blank"><?php echo $os_revenda; ?></a>
                        <?php }else{ ?>
                            <a href="os_press.php?os=<?=$os?>" target="_blank"><?php echo $os; ?></a>
                        <?php } ?>
                    </td>
                    <td style="text-align: left;"><?=$consumidor?></td>
                    <?php if (!in_array($login_fabrica, array(178,183))){ ?>
                    <td>
                        <?php 
                            if (!empty($data_agendamento)){
                                echo $data_agendamento;
                                if ($periodo == "manha"){
                                    echo " (Manhã)";
                                }else{
                                    echo " (Tarde)";
                                }
                            }
                        ?>
                    </td>
                    <?php } ?>
<?php
        if (in_array($login_fabrica,array(169,170,171,178,183,190,195))) {
            if (in_array($login_fabrica, array(178,183))){
                $tecnico_agenda = $os_revenda;
            }
?>

                    <td>
                        <select id="tecnico_<?=$tecnico_agenda?>" name="tecnico_<?=$tecnico_agenda?>" class="class_select">
                            <option value="">Selecione</option>
<?php
            for ($t = 0; $t < $countTecnico; $t++) {
                $resIdTecnico   = pg_fetch_result($resTecnico, $t, tecnico);
                $resNome        = pg_fetch_result($resTecnico, $t, nome);
                $select         = ($tecnico == $resIdTecnico) ? "SELECTED" : "";
?>
                                <option value="<?=$resIdTecnico?>"><?=$resNome?></option>
<?php
            }
?>
                        </select>
                    </td>
<?php
        }
?>
                    <td><input class='class_input data_agendamento_novo' type="text" value="<?=$data_abertura?>" readonly id="data_agendamento_novo_<?=$tecnico_agenda?>" name="data_agendamento_novo_<?= $tecnico_agenda; ?>" max-length="10" size="10" /></td>
                    <td>
                        <select id="periodo_novo_<?=$tecnico_agenda?>" name="periodo_novo_<?=$tecnico_agenda?>" class="class_select">
                            <option value="">Selecione</option>
                            <option value="manha" <?=$selected_manha?> >Manhã</option>
                            <option value="tarde" <?=$selected_tarde?> >Tarde</option>
                        </select>
                    </td>
<?php
        if (in_array($login_fabrica,array(169,170,171,190,195))) {
?>
                    <td>
                        <textarea id="obs_<?=$tecnico_agenda?>" name="obs_<?=$tecnico_agenda?>"></textarea>
                    </td>
<?php
        }
        if ($login_fabrica == 35) {
?>
                        <td>
                            <select value="justificativa" id="justificativa_<?=$tecnico_agenda?>" class="frm">
                                <option value="">Selecione</option>
<?php
            $sqlJust = "
                SELECT  tbl_justificativa.justificativa,
                        tbl_justificativa.descricao
                FROM    tbl_justificativa
                WHERE   tbl_justificativa.fabrica = $login_fabrica
                AND     tbl_justificativa.ativa IS TRUE
          ORDER BY      descricao
            ";
            $resJust = pg_query($con,$sqlJust);

            while ($just = pg_fetch_object($resJust)) {
?>
                                <option value="<?=$just->justificativa?>" <?=($just->justificativa == $justificativa) ? "selected" : ""?>><?=$just->descricao?></option>
<?php
            }
?>
                            </select>
                        </td>
<?php
        }
?>
                    <td>
                        <input type="hidden" id="hd_chamado_<?=$tecnico_agenda?>" name="hd_chamado_<?=$tecnico_agenda?>" value="<?=$hd_chamado?>" />
                        <input type="hidden" id="data_agendamento_<?=$tecnico_agenda?>" name="data_agendamento_<?=$tecnico_agenda?>" value="<?=$data_agendamento?>" />
                        <input type="hidden" id="periodo_<?=$tecnico_agenda?>" name="periodo_<?=$tecnico_agenda?>" value="<?=$periodo?>" />
                        <button type="button" class="frm btn_confirmar" data-tecnico-agenda="<?=$tecnico_agenda?>" data-os_revenda="<?=$os_revenda?>" data-os="<?=$os?>"><?=(in_array($login_fabrica, array(178,183)) ? "Agendar" : "Confirmar")?></button>
                    </td>
                </tr>
<?php
    }
?>
        </tbody>
    </table>
<? } ?>
