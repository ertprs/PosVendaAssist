<?php
require "../../dbconfig.php";
require "../../includes/dbconnect-inc.php";
require "../../autentica_admin.php";

if (!class_exists("TcComm")) {
    include __DIR__.'/../../../class/communicator.class.php';
}

use Posvenda\Model\GenericModel as Model;
use Posvenda\Cockpit;
use Posvenda\Cockpit\Hd;

function get_technical_maximum_amount_of_calls($technical) {
    global $con, $login_fabrica;

    $sql = "
        SELECT COALESCE(qtde_atendimento, NULL, 0) AS maximum_amount
        FROM tbl_tecnico
        WHERE fabrica = {$login_fabrica}
        AND tecnico = {$technical}
    ";
    $qry = pg_query($con, $sql);

    if (pg_fetch_result($qry, 0, "maximum_amount") == 0) {
        exit(json_encode(array("error" => utf8_encode("Técnico com a quantidade de atendimentos zerada"))));
    } else {
        return pg_fetch_result($qry, 0, "maximum_amount");
    }
}

function get_schedule_amount_of_class($add_days = 0, $technical, $date = null) {
    global $con, $login_fabrica;

    if (!is_null($date)) {
        $whereDate = "AND data_agendamento::date = '{$date}'";

        list($year, $month, $day) = explode("-", $date);
    } else {
        $whereDate = "AND data_agendamento::date = current_date + INTERVAL '$add_days days'";
    }

    $sql = "
        SELECT COUNT(*) AS calls, TO_CHAR(data_agendamento, 'DD-MM-YYYY') AS date
        FROM tbl_tecnico_agenda
        WHERE fabrica = {$login_fabrica}
        AND tecnico = {$technical}
        {$whereDate}
        GROUP BY data_agendamento
    ";
    $qry = pg_query($con, $sql);

    if (!pg_num_rows($res)) {
        $return = array(
            "calls" => 0,
            "date"  => (!is_null($date)) ? "{$day}-{$month}-{$year}" : date("d-m-Y")
        );
    } else {
        $return = array(
            "calls" => pg_fetch_result($qry, 0, "calls"),
            "date"  => pg_fetch_result($qry, 0, "date")
        );
    }

    return $return;
}

function get_ticket_schedule($ticket) {
    global $con, $login_fabrica;

    $sql = "
        SELECT tbl_tecnico_agenda.tecnico, tbl_os.os
        FROM tbl_hd_chamado_cockpit
        INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_cockpit.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
        INNER JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
        INNER JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os AND tbl_os.fabrica = {$login_fabrica}
        INNER JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os.os AND tbl_tecnico_agenda.fabrica = {$login_fabrica}
        WHERE tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
        AND tbl_hd_chamado_cockpit.hd_chamado_cockpit = {$ticket}
    ";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        return array(
            "technical"      => pg_fetch_result($qry, 0, "tecnico"),
            "os_telecontrol" => pg_fetch_result($qry, 0, "os")
        );
    } else {
        return array(
            "technical"      => null,
            "os_telecontrol" => null
        );
    }
}

function verify_technical_assistance_type($id, $param) {
    global $con, $login_fabrica;

    $sql = "SELECT tbl_tipo_posto.{$param} 
            FROM tbl_posto_fabrica
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = {$id}";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return (pg_fetch_result($res, 0, 0) == "t") ? true : false;
    } else {
        return false;
    }
}

function mobile_error($ticket, $message) {
    global $con, $login_fabrica;

    $sql = "
        UPDATE tbl_hd_chamado_cockpit SET
            motivo_erro = '{$message}'
        WHERE fabrica = {$login_fabrica}
        AND hd_chamado_cockpit = {$ticket}
    ";
    $res = pg_query($con, $sql);
}

if ($_GET["ajax_set_early_date"]) {
    $ticket    = $_GET["ticket"];
    $technical = $_GET["technical"];

    if (empty($ticket) || empty($technical)) {
        exit(json_encode(array("error" => utf8_encode("Ocorreu um erro ao carregar a agenda do técnico"))));
    }

    $maximum_amount = get_technical_maximum_amount_of_calls($technical);

    $early_date = false;
    $i = 0;

    $oModel = new Model();
    $oHD    = new Hd($login_fabrica, $oModel);
    $json   = $oHD->getDadosTicket($ticket);

    if ($json["tipoOrdem"] == "ZKR6") {
        list($date, $hour)        = explode(" ", $json["dataAbertura"]);
        list($day, $month, $year) = explode("/", $date);

        $date = "{$year}-{$month}-{$day}";

        if (strtotime($date) < strtotime(date("Y-m-d"))) {
                $date = date("Y-m-d");
        }
    }

    while ($early_date === false) {
        $technical_calls = get_schedule_amount_of_class($i, $technical, $date);

        if ($technical_calls["calls"] < $maximum_amount || isset($date)) {
            $early_date = true;
        } else {
            $i++;
        }
    }

    if (empty($technical_calls["date"])) {
        exit(json_encode(array("error" => utf8_encode("Ocorreu um erro ao carregar a agenda do técnico"))));  
    } else {
        $ticket_schedule = get_ticket_schedule($ticket);

        $return = array(
            "success"        => true, 
            "maximum_amount" => $maximum_amount, 
            "date"           => utf8_encode($technical_calls["date"]),
            "technical"      => $ticket_schedule["technical"],
            "os_telecontrol" => $ticket_schedule["os_telecontrol"]
        );

        exit(json_encode($return));
    }
}

if ($_GET["ajax_load_schedule_data"]) {
    $technical       = $_GET["technical"];
    $week_start_date = $_GET["week_start_date"];

    list($day, $month, $year) = explode("/", $week_start_date);

    $week_start_date = "{$year}-{$month}-{$day} 00:00:00";

    if (empty($technical)) {
        exit(json_encode(array("error" => utf8_encode("Ocorreu um erro ao carregar a agenda do técnico"))));
    }

    $sql = "
        SELECT
            TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD-MM-YYYY') AS date,
            tbl_hd_chamado_cockpit.hd_chamado_cockpit AS ticket,
            tbl_hd_chamado.hd_chamado AS telecontrol_protocol,
            tbl_hd_chamado_cockpit.dados AS json,
            tbl_os.data_fechamento AS completed,
            tbl_os.os AS os_telecontrol,
            tbl_tecnico_agenda.ordem AS order,
            tbl_hd_chamado_cockpit_prioridade.hd_chamado_cockpit_prioridade AS priority,
            tbl_status_checkpoint.descricao AS status
        FROM tbl_tecnico_agenda
        INNER JOIN tbl_os ON tbl_os.os = tbl_tecnico_agenda.os AND tbl_os.fabrica = {$login_fabrica}
        INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
        INNER JOIN tbl_hd_chamado_cockpit ON tbl_hd_chamado_cockpit.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_cockpit.fabrica = {$login_fabrica}
        LEFT JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
        LEFT JOIN tbl_hd_chamado_cockpit_prioridade ON tbl_hd_chamado_cockpit_prioridade.hd_chamado_cockpit_prioridade = tbl_hd_chamado_cockpit.hd_chamado_cockpit_prioridade AND tbl_hd_chamado_cockpit_prioridade.fabrica = {$login_fabrica}
        WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica}
        AND tbl_tecnico_agenda.tecnico = {$technical}
        AND tbl_tecnico_agenda.data_agendamento >= '{$week_start_date}'
        ORDER BY tbl_tecnico_agenda.data_agendamento ASC, tbl_tecnico_agenda.ordem ASC
    ";
    $qry = pg_query($con, $sql);

    $result = array();

    if (pg_num_rows($qry) > 0) {
        while ($protocol = pg_fetch_object($qry)) {
            $json = json_decode($protocol->json);

            $result[] = array(
                "date"                 => $protocol->date,
                "ticket"               => $protocol->ticket,
                "telecontrol_protocol" => $protocol->telecontrol_protocol,
                "client_name"          => utf8_encode($json->nomeFantasia),
                "os_telecontrol"       => $protocol->os_telecontrol,
                "os_kof"               => $json->osKof,
                "completed"            => $protocol->completed,
                "order"                => $protocol->order,
                "priority"             => $protocol->priority,
                "status"               => utf8_encode($protocol->status)
            );
        }
    }

    $return = array("success" => true, "result" => $result);

    exit(json_encode($return));
}

if ($_POST["ajax_save_technical_schedule"]) {

    $oModel = new Model();
    $oHD = new Hd($login_fabrica, $oModel);

    include "../api/persys.php";

    $technical          = $_POST["technical"];
    $distance           = $_POST["distance"];
    $returning_distance = $_POST["returning_distance"];
    $schedule           = $_POST["schedule"];
    $os_kof             = $_POST["os_kof"];
    $destiny_latitude   = $_POST["destiny_latitude"];
    $destiny_longitude  = $_POST["destiny_longitude"];

    $return = array(
        "error"   => array(),
        "success" => array()
    );

    if (count($schedule) > 0) {
        $token = generateToken($applicationKey);

        $cockpit = new Cockpit($login_fabrica);

        $technical_assistance = $cockpit->getPostoTecnico($technical);

        foreach ($schedule as $date => $protocol_array) {
            if (empty($protocol_array)) {
                continue;
            }

            foreach ($protocol_array as $protocol) {
                if (empty($protocol)) {
                    continue;
                }

                unset($persys);

                $sql = "
                    SELECT
                        dados
                    FROM tbl_hd_chamado_cockpit
                    WHERE fabrica = {$login_fabrica}
                    AND hd_chamado_cockpit = {$protocol['ticket']}
                ";
                $qry = pg_query($con, $sql);

                $json = json_decode(pg_fetch_result($qry, 0, "dados"), true);

                $centroDistribuidor = $json['centroDistribuidor'];

                try {
                    unset($os, $telecontrol_protocol);

                    if (empty($protocol['telecontrol_protocol'])) {
                        $sqlHdChamado = "
                            SELECT hd_chamado AS telecontrol_protocol
                            FROM tbl_hd_chamado_cockpit
                            WHERE fabrica = {$login_fabrica}
                            AND hd_chamado_cockpit = {$protocol['ticket']}
                        ";
                        $qryHdChamado = pg_query($con, $sqlHdChamado);

                        if (!pg_num_rows($qryHdChamado)) {
                            throw new Exception("Erro ao buscar informações do ticket");
                        } else {
                            $telecontrol_protocol = pg_fetch_result($qryHdChamado, 0, "telecontrol_protocol");

                            if (empty($telecontrol_protocol)) {
                                if (verify_technical_assistance_type($technical_assistance, "tecnico_proprio") == true) {
                                    $json["km"] = $distance;
                                } else {
                                    //se não for um técnico de um posto autorizado de terceiro, deve calcular ida e volta
                                    $json["km"] = $distance + $returning_distance;
                                }

                                $telecontrol_protocol = $oHD->abreHD($protocol['ticket'], $json);

                                if (!array_key_exists("hd_chamado", $telecontrol_protocol) || empty($telecontrol_protocol["hd_chamado"])) {
                                    throw new \Exception($telecontrol_protocol["message"]);
                                }

                                $telecontrol_protocol = $telecontrol_protocol["hd_chamado"];

                                setHdChamadoCockpit(
                                    array(
                                        "hd_chamado_cockpit" => $protocol['ticket'],
                                        "hdChamado" => $telecontrol_protocol
                                    ),
                                    $applicationKey,
                                    $token,
                                    $accessEnv
                                );
                            }
                        }
                    } else {
                        $telecontrol_protocol = $protocol['telecontrol_protocol'];

                        $sqlFinalizada = "
                            SELECT os.finalizada, fn_retira_especiais(status.descricao) AS status
                            FROM tbl_os AS os
                            INNER JOIN tbl_status_checkpoint AS status ON status.status_checkpoint = os.status_checkpoint
                            WHERE os.fabrica = {$login_fabrica}
                            AND os.hd_chamado = {$telecontrol_protocol}
                        ";
                        $resFinalizada  = pg_query($con, $sqlFinalizada);

                        if (pg_num_rows($resFinalizada) > 0) {
                            if (strlen(pg_fetch_result($resFinalizada, 0, "finalizada")) > 0) {
                                continue;
                            }

                            if (in_array(strtolower(pg_fetch_result($resFinalizada, 0, "status")), array("em deslocamento", "em execucao"))) {
                                continue;
                            }
                        }
                    }

                    if (!$cockpit->setPostoHD($telecontrol_protocol, $technical_assistance)) {
                        throw new Exception("Erro ao definir posto autorizado do atendimento");
                    }

                    if (!empty($telecontrol_protocol)) {
                        $sqlOS = "
                            SELECT tbl_os.os, tbl_os_extra.tecnico
                            FROM tbl_os
                            LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                            INNER JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
                            WHERE tbl_os.fabrica = {$login_fabrica}
                            AND tbl_hd_chamado.hd_chamado = {$telecontrol_protocol}
                        ";
                        $qryOS = pg_query($con, $sqlOS);

                        if (!pg_num_rows($qryOS)) {
                            $data      = $cockpit->getDadosTicket($protocol['ticket'], "patrimonioKof");
                            $patrimony = $data["patrimonioKof"];
                            
                            $os = $cockpit->abreOS($telecontrol_protocol, $patrimony, $os_kof, $destiny_latitude, $destiny_longitude);
                            $erro = $os["error"];
                            $os = $os["os"];

                            if (empty($os)) {
                                throw new Exception("Erro ao abrir ordem de serviço {$erro}");
                            }

                            if (!$cockpit->updateHDChamadoOs($telecontrol_protocol, $os)) {
                                throw new Exception("Erro ao relacionar atendimento callcenter com a ordem de serviço");
                            }

                            if (!$cockpit->updateOsTecnico($os, $technical)) {
                                throw new Exception("Erro ao relacionar o técnico a ordem de serviço");
                            }
                        } else {
                            $os           = pg_fetch_result($qryOS, 0, "os");
                            $os_technical = pg_fetch_result($qryOS, 0, "tecnico");

                            if (!$cockpit->updateHDChamadoOs($telecontrol_protocol, $os)) {
                                throw new Exception("Erro ao relacionar atendimento callcenter com a ordem de serviço");
                            }
                        }

                        if (!empty($os)) {
                            $scheduled = $cockpit->verificaAgendaByOS($os);

                            if (empty($scheduled)) {
                                if ($cockpit->insereAgenda($technical, $os, $date, $protocol["order"],$con) === false) {
                                    throw new Exception("Erro ao agendar atendimento");
                                }

                                if (!$cockpit->setPostoOS($os, $technical_assistance)) {
                                    throw new Exception("Erro ao relacionar o posto autorizado a ordem de serviço");
                                }

                                if (!$cockpit->updateOsTecnico($os, $technical)) {
                                    throw new Exception("Erro ao relacionar o técnico a ordem de serviço");
                                }
                            } else {
                                list($d, $h) = explode(" ", $scheduled[0]["data_agendamento"]);
                                list($ano, $mes, $dia) = explode("-", $d);

                                $scheduled[0]["data_agendamento"] = "{$dia}-{$mes}-{$ano}";
                            }
                        }

                        if (verify_technical_assistance_type($technical_assistance, "tecnico_proprio") == true) {
                            $id_externo = $cockpit->getOsIdExterno($os);

                            //adicionado pois os ambev esta caindo aqui e não mudava a data de agendamento
                            if ( (empty($scheduled) || empty($id_externo)) and $centroDistribuidor != "AMBV" ) {
                                $persys = $cockpit->exportaOs($os);

                            } else if ($technical != $scheduled[0]["tecnico"]) {
                                $old_technical_assistance = $cockpit->getPostoTecnico($scheduled[0]["tecnico"]);
                                $old_technical_type = verify_technical_assistance_type($old_technical_assistance, "tecnico_proprio");

                                $persys = $cockpit->transferirOs($os, $technical, $date, $old_technical_type);

                                if (!(empty($persys) || $persys["error"])) {
                                    if ($cockpit->atualizarAgenda($scheduled[0]["tecnico_agenda"], $technical, $date, $protocol["order"], $os) === false) {
                                        throw new Exception("Erro ao agendar atendimento");
                                    }

                                    if (!$cockpit->setPostoOS($os, $technical_assistance)) {
                                        throw new Exception("Erro ao relacionar o posto autorizado a ordem de serviço");
                                    }

                                    if (!$cockpit->updateOsTecnico($os, $technical)) {
                                        throw new Exception("Erro ao relacionar o técnico a ordem de serviço");
                                    }
                                }
                            } else if ($date != $scheduled[0]["data_agendamento"]) {
                                $persys = $cockpit->reagendaOs($os, $date);

                                if ($cockpit->atualizarAgenda($scheduled[0]["tecnico_agenda"], $technical, $date, $protocol["order"], $os) === false) {
                                    throw new Exception("Erro ao agendar atendimento");
                                }
                            } else {
                                $persys = true;
                            }                           

                            if (empty($persys) and 1 == 2  ) {
                                mobile_error($protocol["ticket"], "Erro ao enviar OS para o dispositivo mobile");
                                throw new Exception("Erro ao enviar OS para o dispositivo mobile");
                            } else if ($persys["error"]) {
                                if (strtolower(utf8_decode($persys["error"]["message"])) == "informação já cadastrada") {
                                    mobile_error($protocol["ticket"], "Erro ao salvar informações");
                                    throw new Exception("Erro ao salvar informações");
                                } else {
                                    mobile_error($protocol["ticket"], $persys["error"]["message"]);
                                    throw new Exception($persys["error"]["message"]);
                                }
                            } else {
                                $return["success"][$protocol["ticket"]] = array(
                                    "os"                   => $os,
                                    "telecontrol_protocol" => $telecontrol_protocol
                                );


				mobile_error($protocol["ticket"], "");
                            }
                        } else {

                            if (!empty($scheduled)) {
                                $old_technical_assistance = $cockpit->getPostoTecnico($scheduled[0]["tecnico"]);

                                if ($technical != $scheduled[0]["tecnico"] && verify_technical_assistance_type($old_technical_assistance, "tecnico_proprio") == true) {
                                    $id_externo = $cockpit->getOsIdExterno($os);

                                    if (strlen($id_externo) > 0) {
                                        $cockpit->desagendarOs($id_externo);
                                    }
                                }

                                if ($cockpit->atualizarAgenda($scheduled[0]["tecnico_agenda"], $technical, $date, $protocol["order"], $os) === false) {
                                    throw new Exception("Erro ao agendar atendimento");
                                }

                                if (!$cockpit->setPostoOS($os, $technical_assistance)) {
                                    throw new Exception("Erro ao relacionar o posto autorizado a ordem de serviço");
                                }

                                if (!$cockpit->updateOsTecnico($os, $technical)) {
                                    throw new Exception("Erro ao relacionar o técnico a ordem de serviço");
                                }
                            }

			    mobile_error($protocol["ticket"], "");

                            $return["success"][$protocol["ticket"]] = array(
                                "os"                   => $os,
                                "telecontrol_protocol" => $telecontrol_protocol
                            );

                            if ($technical != $scheduled[0]["tecnico"]) {
                                $comunicado = "
                                    INSERT INTO tbl_comunicado (mensagem, tipo, fabrica, descricao, posto, obrigatorio_site, ativo)
                                    VALUES ('OS {$os} transferida através da integração KOF', 'Comunicado Inicial', $login_fabrica, 'Nova OS KOF transferida', {$technical_assistance}, 't', 't');";
                                $qryComunicado = pg_query($con, $comunicado);

                                $sqlTechnicalData = "
                                    SELECT
                                        tbl_posto.posto,
                                        tbl_posto.nome,
                                        tbl_posto_fabrica.contato_email
                                    FROM tbl_posto_fabrica
                                    JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                                    WHERE tbl_posto_fabrica.posto = {$technical_assistance}
                                    AND tbl_posto_fabrica.fabrica = {$login_fabrica};
                                ";

                                $qryTechnicalData = pg_query($con, $sqlTechnicalData);

                                if (pg_num_rows($qryTechnicalData) > 0) {

                                    $technicalName = pg_fetch_result($qryTechnicalData, 0, nome);
                                    $technicalEmail = pg_fetch_result($qryTechnicalData, 0, contato_email);

                                    if(empty($externalId)) {
                                        $externalId = "smtp@posvenda";
                                        $remetente = "noreply@telecontrol.com.br";
                                    }else{
                                        $remetente = $externalEmail;
                                    }

                                    $mailer = new TcComm($externalId);

                                    $assunto = "OS {$os} KOF transferida";
                                    $mensagem = "Olá, Posto Autorizado {$technicalName},<br /><br />\n";
                                    $mensagem .= "Existe uma OS {$os} transferida através da integração KOF.<br />\n";
                                    $mensagem .= "Para visualizar a OS, faça login no sistema e <a href='http://posvenda.telecontrol.com.br/assist/os_press.php?os={$os}' target='_blank'>clique aqui</a>.";

                                    $res = $mailer->sendMail(
                                        trim($technicalEmail),
                                        $assunto,
                                        utf8_encode($mensagem),
                                        $remetente
                                    );
                                }
                            }
                        }
                        if (!empty($scheduled) && $technical != $scheduled[0]["tecnico"]) {
                            $sql = "
                                SELECT nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND tecnico = {$scheduled[0]['tecnico']}
                            ";
                            $qry = pg_query($con, $sql);

                            $old_technical = pg_fetch_result($qry, 0, "nome");

                            $sql = "
                                SELECT nome FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND tecnico = {$technical}
                            ";
                            $qry = pg_query($con, $sql);

                            $new_technical = pg_fetch_result($qry, 0, "nome");

                            $sql = "
                                INSERT INTO tbl_os_interacao 
                                    (os, admin, comentario, fabrica)
                                VALUES 
                                    ({$os}, {$login_admin}, 'OS transferida do posto autorizado/técnico {$old_technical} para {$new_technical}', {$login_fabrica})
                            ";
                            $qry = pg_query($con, $sql);
                        }
                    } else {
                        throw new Exception("Erro ao abrir um protocolo para o atendimento");
                    }
                } catch (Exception $e) {
                    if (isset($os)) {
                        $return["error"][] = array(
                            "os" => $os,
                            "telecontrol_protocol" => $telecontrol_protocol,
                            "ticket" => $protocol["ticket"],
                            "message" => (preg_match("/\\/", $e->getMessage())) ? $e->getMessage() : utf8_encode($e->getMessage())
                        );
                    } else {
                        $return["error"][] = array(
                            "ticket" => $protocol["ticket"],
                            "message" => (preg_match("/\\/", $e->getMessage())) ? $e->getMessage() : utf8_encode($e->getMessage())
                        );
                    }
                }
            }
        }
        
        exit(json_encode($return));
    }
}

if ($_GET["ajax_load_priorities"]) {
    $sql = "
        SELECT
            hd_chamado_cockpit_prioridade AS id,
            descricao AS description,
            cor AS color,
            peso AS weight
        FROM tbl_hd_chamado_cockpit_prioridade
        WHERE fabrica = {$login_fabrica}
        AND ativo IS TRUE
        ORDER BY id ASC
    ";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        $return = array("priorities" => array());

        while ($priority = pg_fetch_object($qry)) {
            $return["priorities"][] = array(
                "id"          => $priority->id,
                "description" => utf8_encode($priority->description),
                "color"       => $priority->color,
                "weight"      => $priority->weight
            );
        }
    } else {
        $return = array("error" => utf8_encode("Nenhuma prioridade foi encontrada"));
    }
    
    exit(json_encode($return));
}
