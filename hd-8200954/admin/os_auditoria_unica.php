	<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "auditoria";

include "autentica_admin.php";
include "funcoes.php";

if (file_exists("../classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
    include_once "../classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
    $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
    if (in_array($login_fabrica, array(169, 170))) {
        $classOs = new $className($login_fabrica, $os, $con);
    } else {
        $classOs = new $className($login_fabrica, $os);
    }
} else {
    $classOs = new \Posvenda\Os($login_fabrica, $os);
}

if(isset($_POST['ajax_excluir_peca'])){
	include_once __DIR__ . '/../class/AuditorLog.php';
    $os_item = $_POST['os_item'];
    $os      = $_POST['os'];

    $auditorLog = new AuditorLog();
    $auditorLog->retornaDadosSelect(" SELECT os_item, peca from tbl_os_item where os_item = $os_item ");

    $sql = "DELETE FROM tbl_os_item WHERE os_item = $os_item";
    $res = pg_query($con, $sql);
    if(strlen(pg_last_error($con))==0){
        echo json_encode(array('msg' => utf8_encode('Peça excluída com sucesso.')));
    }else{
        echo json_encode(array('error' => utf8_encode('Falha ao excluir peça.')));
    }    

    $auditorLog->retornaDadosSelect()->enviarLog('delete', "tbl_os_item_pecas", $login_fabrica."*".$os);

    exit;
}

if (isset($_POST["ajax_status"])) {
    try {
        $os = $_POST["os"];
        $status = utf8_decode($_POST["status"]);

        if (empty($os)) {
            throw new Exception("OS não informada");
        }

        if (empty($status)) {
            throw new Exception("Status não informado");
        }

        $sql = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("OS não encontrada");
        }

        $sql = "SELECT status_os FROM tbl_status_os WHERE descricao = '$status'";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("Status não encontrado");
        }

        $status_os = pg_fetch_result($res, 0, 'status_os');

        $sql = "INSERT INTO tbl_os_status (os, status_os, observacao, fabrica_status) VALUES ($os, $status_os, '$status', $login_fabrica)";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar status");
        }

        exit(json_encode(array("sucesso" => true)));
    } catch(Exception $e) {
        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if (isset($_POST['ajax'])) {
    if ($_POST['action'] == 'consulta_ida_volta') {
        $os = $_POST['os'];
        $sql = "SELECT
                    JSON_FIELD('qtde_km_ida', tbl_os_campo_extra.campos_adicionais) AS qtde_km_ida,
                    JSON_FIELD('qtde_km_volta', tbl_os_campo_extra.campos_adicionais) AS qtde_km_volta,
                    tbl_os.qtde_diaria
                FROM tbl_os
                    LEFT JOIN tbl_os_campo_extra ON(tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = {$login_fabrica})
                WHERE tbl_os.os = {$os}";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $qtde_km_ida   = pg_fetch_result($res, 0, "qtde_km_ida");
            $qtde_km_volta = pg_fetch_result($res, 0, "qtde_km_volta");
            $qtde_visita   = pg_fetch_result($res, 0, "qtde_diaria");

            $qtde_km_ida   = (empty($qtde_km_ida)) ? 0 : $qtde_km_ida;
            $qtde_km_volta = (empty($qtde_km_volta)) ? 0 : $qtde_km_volta;
            $qtde_visita   = (empty($qtde_visita)) ? 0 : $qtde_visita;
        }else{
            $qtde_km_ida   = 0;
            $qtde_km_volta = 0;
            $qtde_visita   = 0;
        }

        if ($login_fabrica == 35) {
            $sqlAgendamento = "
                SELECT  TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_agendamento,
                        tbl_justificativa.descricao AS justificativa
                FROM    tbl_tecnico_agenda
                JOIN    tbl_justificativa   USING(justificativa)
                WHERE   tbl_tecnico_agenda.os = $os
                ORDER BY      tbl_tecnico_agenda.tecnico_agenda ASC
            ";
            $resAgendamento = pg_query($con,$sqlAgendamento);

            while ($visitas = pg_fetch_object($resAgendamento)) {
                $confirmadas[] = array("data_agendamento" => $visitas->data_agendamento,"justificativa" => utf8_encode($visitas->justificativa));
            }
        }
        echo json_encode(array("qtde_km_ida" => $qtde_km_ida, "qtde_km_volta" => $qtde_km_volta, "qtde_visita" => $qtde_visita,"visitas" => $confirmadas));
        exit;
    }
}

if ($_POST["ajax_cancela_os"]) {
    try {
        $begin         = false;
        $os            = $_POST["os"];
        $justificativa = $_POST["justificativa"];
        $tipo_os       = trim($_POST["tipo_os"]);

        $justificativa = str_replace('\'', '', $justificativa);
        $justificativa = str_replace('"', '', $justificativa);
        
        $sql = "
            SELECT data_fechamento
            FROM tbl_os
            WHERE os = {$os}
        ";
        $res = pg_query($con, $sql);
        
        $data_fechamento = pg_fetch_result($res, 0, "data_fechamento");

        pg_query($con, "BEGIN");
        $begin = true;
        
        if ($login_fabrica == 178){
            if ($tipo_os == "OSR"){
                $classOs->cancelaOs($con, $os, $justificativa);
            }else{
                $classOs->cancelaOs($con, $os, $justificativa, $login_admin);
            }
        }else{
            
            if ($cancelaOS) {
                $sql = "
                    UPDATE tbl_os SET
                        excluida = TRUE
                    WHERE fabrica = {$login_fabrica}
                    AND os = {$os}
                ";
                pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao cancelar ordem de serviço #1");
                }

                if (in_array($login_fabrica, [131])) {
                    atualiza_status_checkpoint($os, 'OS Cancelada');
                }

                if (empty($data_fechamento)) {
                    $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = $login_fabrica AND LOWER(descricao) = 'cancelado'";
                    $res = pg_query($con, $sql);

                    if (!pg_num_rows($res)) {
                        throw new Exception("Erro ao cancelar ordem de serviço #2");
                    }

                    $servico_realizado = pg_fetch_result($res, 0, "servico_realizado");

                    $sql = "
                        SELECT oi.os_item, oi.pedido_item, pi.pedido, (pi.qtde - (COALESCE(pi.qtde_faturada, 0) + COALESCE(pi.qtde_cancelada, 0))) AS qtde_pendente
                        FROM tbl_os_item oi
                        INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
                        LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
                        WHERE op.os = {$os}
                    ";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) > 0) {
                        foreach (pg_fetch_all($res) as $i => $row) {
                            $row = (object) $row;

                            if (!empty($row->pedido_item) && $row->qtde_pendente == 0) {
                                continue;
                            }

                            $update = "
                                UPDATE tbl_os_item SET
                                    servico_realizado = {$servico_realizado}
                                WHERE os_item = {$row->os_item}
                            ";
                            pg_query($update);

                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Erro ao cancelar ordem de serviço #3");
                            }

                            if (!empty($row->pedido_item) && $row->qtde_pendente > 0) {
                                $update = "
                                    UPDATE tbl_pedido_item SET
                                        qtde_cancelada = {$row->qtde_pendente}
                                    WHERE pedido_item = {$row->pedido_item};

                                    SELECT fn_atualiza_status_pedido({$login_fabrica}, {$row->pedido});
                                ";
                                pg_query($con, $update);

                                if (strlen(pg_last_error()) > 0) {
                                    throw new Exception("Erro ao cancelar ordem de serviço #4");
                                }
                            }
                        }
                    }
                }
            } else {
                $sql = "SELECT fn_os_excluida($os, $login_fabrica, $login_admin)";
                pg_query($con, $sql);
            }
        }

        if (in_array($login_fabrica, [104])) {

            $addJustificativa = "UPDATE tbl_os_excluida 
                                 SET motivo_exclusao = '{$justificativa}' 
                                 WHERE os = $os";

            $res = pg_query($con, $addJustificativa);
        }

        if (strlen(pg_last_error()) > 0) {
            if ($login_fabrica == 171) {
                $erro = "Erro ao cancelar auditorias.Verifique se a Ordem de serviço já possui pedido lançado e faturado";
			} else {
				$msg_erro = pg_last_error(); 
				if (strpos ($msg_erro,"consta em extrato") > 0){
	                $erro = "Essa OS já consta no extrato, não pode ser cancelada ";
				}else{
	                $erro = "Erro ao cancelar auditorias " ;
				}

            }
            throw new Exception($erro);
        }

        $sql = "
            UPDATE tbl_auditoria_os SET
                cancelada = current_timestamp,
                admin         = {$login_admin},
                justificativa = E'{$justificativa}'
            WHERE os = {$os }
            AND (liberada IS NULL AND cancelada IS NULL)
        ";
        pg_query($con, $sql);
        
        
        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao cancelar auditorias");
        }

        if($login_fabrica == 35){
            $sql = "UPDATE tbl_os_excluida SET motivo_exclusao = '$justificativa' WHERE os = {$os }";
            pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao cancelar auditorias");
            }
        }
        
        if ($login_fabrica <> 178){        
            $sql = "SELECT posto, os_numero FROM tbl_os WHERE os = {$os}";
            $resPosto = pg_query($con,$sql);
            
            $posto      = pg_fetch_result($resPosto, 0, "posto");
            $id_externo = pg_fetch_result($resPosto, 0, "os_numero");

            $sql = "INSERT INTO tbl_comunicado
                    (
                        fabrica,
                        posto,
                        obrigatorio_site,
                        tipo,
                        ativo,
                        descricao,
                        mensagem
                    )
                    VALUES
                    (
                        {$login_fabrica},
                        {$posto},
                        true,
                        'Com. Unico Posto',
                        true,
                        'Auditoria',
                        E'Ordem de Serviço {$os} cancelada pela fábrica: {$justificativa}'
                    )
            ";
            pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao enviar o comunicado para o posto autorizado");
            }
        }

        if ($login_fabrica == 175){
            $sql = "INSERT INTO tbl_comunicado
                    (
                        fabrica,
                        posto,
                        obrigatorio_site,
                        tipo,
                        ativo,
                        descricao,
                        mensagem, 
                        tecnico
                    )
                    VALUES
                    (
                        {$login_fabrica},
                        {$posto},
                        true,
                        'Com. Unico Posto',
                        true,
                        'Auditoria',
                        E'Ordem de Serviço {$os} cancelada pela fábrica: {$justificativa}',
                        true
                    )
            ";
            pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao enviar o comunicado para técnico do posto autorizado");
            }
        }

        if ($tipo_os != "OSR"){
            $sql = "
                INSERT INTO tbl_os_interacao
                (os, data, admin, comentario, interno, fabrica)
                VALUES
                ({$os}, CURRENT_TIMESTAMP, {$login_admin}, E'Ordem de Serviço cancelada pela fábrica: {$justificativa}', false, $login_fabrica)
            ";
            $res = pg_query($con, $sql);
        }

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao interagir na ordem de serviço");
        }

        if ($usaMobile) {
            $cockpit = new \Posvenda\Cockpit($login_fabrica);
            $cockpit->cancelaOsMobile($os, $con);
        }

        pg_query($con, "COMMIT");

        exit(json_encode(array(
            "sucesso"       => true,
            "admin"         => $login_login,
            "justificativa" => $justificativa
        )));
    } catch(Exception $e) {
        if ($begin == true) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax_aprova_auditoria"]) {
    try {
        $begin         = false;
        $os            = $_POST["os"];
        $justificativa = utf8_decode($_POST["justificativa"]);

	    $auxJustificativa = addslashes($justificativa);
        $auditoria     = $_POST["auditoria"];
        $auditorias    = array();
        $tipo_os       = $_POST["tipo_os"];

        if ($login_fabrica == 177){
            $defeito_constatado = $_POST["defeito_constatado"];
        }

        if (!empty($auditoria)) {
            $whereAuditoria = "AND auditoria_os.auditoria_os = {$auditoria}";
            $whereLiberada  = "AND liberada IS NOT NULL";
        }

        if ($tipo_os == "OSR"){
            $sql = "SELECT
                auditoria_os.auditoria_os,
                auditoria_status.descricao,
                auditoria_os.observacao,
                auditoria_os.data_input,
                os.posto,
                NULL AS data_fechamento
                FROM tbl_auditoria_os_revenda AS auditoria_os
                JOIN tbl_auditoria_status AS auditoria_status ON auditoria_status.auditoria_status = auditoria_os.auditoria_status
                JOIN tbl_os_revenda AS os ON os.os_revenda = auditoria_os.os_revenda AND os.fabrica = {$login_fabrica}
                WHERE auditoria_os.os_revenda = {$os}
                {$whereAuditoria}
                AND (auditoria_os.liberada IS NULL AND auditoria_os.cancelada IS NULL)";
        }else{
            $sql = "
                SELECT
                    auditoria_os.auditoria_os,
                    auditoria_status.descricao,
                    auditoria_os.observacao,
                    auditoria_os.data_input,
                    os.posto,
                    auditoria_os.os,
                    os.data_fechamento
                FROM tbl_auditoria_os AS auditoria_os
                INNER JOIN tbl_auditoria_status AS auditoria_status ON auditoria_status.auditoria_status = auditoria_os.auditoria_status
                INNER JOIN tbl_os AS os ON os.os = auditoria_os.os AND os.fabrica = {$login_fabrica}
                WHERE auditoria_os.os = {$os}
                {$whereAuditoria}
                AND (auditoria_os.liberada IS NULL AND auditoria_os.cancelada IS NULL)
            ";
        }
        $res = pg_query($con, $sql);

        if ($login_fabrica == 178 AND $tipo_os != "OSR"){
            for ($i=0; $i < pg_num_rows($res); $i++) { 
                $os_auditoria = pg_fetch_result($res, $i, "os");
                $descricao    = strtolower(retira_acentos(pg_fetch_result($res, $i, "descricao")));
                $observacao   = strtolower(retira_acentos(pg_fetch_result($res, $i, "observacao")));

                // if ($descricao == "auditoria de pecas" AND $observacao == "os em auditoria de peca sem preco"){
                //     $sql_preco_peca = "
                //         SELECT 
                //             tti.preco,
                //             p.peca,
                //             p.referencia,
                //             p.descricao
                //         FROM tbl_os o
                //         JOIN tbl_os_produto op ON op.os = o.os AND op.produto = o.produto
                //         JOIN tbl_os_item oi ON oi.os_produto = op.os_produto AND oi.fabrica_i = $login_fabrica
                //         JOIN tbl_peca p ON p.peca = oi.peca AND p.fabrica = $login_fabrica
                //         LEFT JOIN tbl_tabela_item tti ON tti.peca = p.peca
                //         LEFT JOIN tbl_tabela tt ON tt.tabela = tti.tabela AND tt.fabrica = $login_fabrica
                //         WHERE o.os = $os_auditoria
                //         AND o.fabrica = $login_fabrica";
                //     $res_preco_peca = pg_query($con, $sql_preco_peca);
                    
                //     if (pg_num_rows($res_preco_peca) > 0){
                //         for ($j=0; $j < pg_num_rows($res_preco_peca); $j++) { 
                //             $preco = pg_fetch_result($res_preco_peca, $j, "preco");
                //             $peca = pg_fetch_result($res_preco_peca, $j, "peca");
                //             $referencia = pg_fetch_result($res_preco_peca, $j, "referencia");
                //             $descricao = pg_fetch_result($res_preco_peca, $j, "descricao");
                //             if (!empty($peca) AND empty($preco)){
                //                 throw new Exception("Erro ao aprovar auditoria. OS esta com a PEÇA: $referencia-$descricao sem preço", 1);
                //             }
                //         }
                //     }
                // }
                if ($descricao == "auditoria de pecas" AND $observacao == "os em auditoria de peca sem preco"){
                    $sql_servico = "
                        SELECT servico_realizado, descricao
                        FROM tbl_servico_realizado
                        WHERE fabrica = $login_fabrica
                        AND descricao ilike '%Direta%'";
                    $res_servico = pg_query($con, $sql_servico);
                    
                    if (pg_num_rows($res_servico) > 0) {
                        $servico_realizado = pg_fetch_result($res_servico, 0, 'servico_realizado');
                    }

                    $sql_os_item = "
                        SELECT 
                            oi.os_item
                        FROM tbl_os o
                        JOIN tbl_os_produto op ON op.os = o.os AND op.produto = o.produto
                        JOIN tbl_os_item oi ON oi.os_produto = op.os_produto AND oi.fabrica_i = $login_fabrica
                        WHERE o.os = $os_auditoria
                        AND o.fabrica = $login_fabrica";
                    $res_os_item = pg_query($con, $sql_os_item);
                    
                    if (pg_num_rows($res_os_item) > 0){
                        for ($j=0; $j < pg_num_rows($res_os_item); $j++) { 
                            $os_item = pg_fetch_result($res_os_item, $j, "os_item");
                            
                            $sql_update = "UPDATE tbl_os_item SET servico_realizado = $servico_realizado WHERE os_item = $os_item AND fabrica_i = $login_fabrica";
                            $res_update = pg_query($con, $sql_update);

                            if (strlen(pg_last_error()) > 0){
                                throw new Exception("Erro ao aprovar auditoria. PEÇA: $referencia-$descricao sem preço", 1);
                            }
                        }
                    }
                }
            }
        }

        if (strlen(pg_last_error()) > 0 || !pg_num_rows($res)) {
            throw new Exception("Erro ao buscar auditorias #001");
        }

	    $posto           = pg_fetch_result($res, 0, "posto");
        $data_fechamento = pg_fetch_result($res, 0, "data_fechamento");

        pg_query($con, "BEGIN");
        $begin = true;

        if (isset($_POST["sem_mao_de_obra"])) {
            $sem_mao_de_obra = $_POST["sem_mao_de_obra"];

            if ($sem_mao_de_obra == "true" || $sem_mao_de_obra == "t") {
                $aprovado_sem_mo = true;
            } else {
                $aprovado_sem_mo = false;
            }
        } else {
            if ($tipo_os == "OSR"){
                $sql = "SELECT DISTINCT paga_mao_obra FROM tbl_auditoria_os_revenda WHERE os_revenda = {$os} AND liberada IS NOT NULL";
            }else{
                $sql = "SELECT DISTINCT paga_mao_obra FROM tbl_auditoria_os WHERE os = {$os} AND liberada IS NOT NULL";
            }
            $res_mb = pg_query($con, $sql);

            if (pg_num_rows($res_mb) > 0) {
                $paga_mao_obra = pg_fetch_result($res_mb, 0, "paga_mao_obra");

                if ($paga_mao_obra == "t") {
                    $aprovado_sem_mo = false;
                } else {
                    $aprovado_sem_mo = true;
                }
            }
        }

        if ($login_fabrica == 177){
            if (!empty($defeito_constatado)){
                $updateDefeito = "UPDATE tbl_os_produto set defeito_constatado = {$defeito_constatado} WHERE os = {$os};UPDATE tbl_os set defeito_constatado = {$defeito_constatado} WHERE os = {$os};";
                $resDefeito = pg_query($con, $updateDefeito);
                if (strlen(pg_last_error()) > 0){
                    throw new Exception("Favor informar o defeito constatado.");
                }
            }else{
                throw new Exception("Favor informar o defeito constatado.");
            }
        }

        if ($aprovado_sem_mo == true) {
            $updateSemMO = "paga_mao_obra = false";
        } else {
            $updateSemMO = "paga_mao_obra = true";
        }
        
        $envia_comunicado = true;
        while ($row = pg_fetch_object($res)) {
            $auditorias[] = "{$row->descricao} - {$row->observacao}";

            if ($tipo_os == "OSR"){
                $update = "
                    UPDATE tbl_auditoria_os_revenda SET
                        liberada = current_timestamp,
                        justificativa = E'{$auxJustificativa}',
                        admin = {$login_admin},
                        bloqueio_pedido = false
                    WHERE os_revenda = {$os}
                    AND reprovada isnull and cancelada isnull
                    AND auditoria_os = {$row->auditoria_os} ";
                $resUpdate = pg_query($con, $update);

                if ($login_fabrica == 178){

                    $sql = "
                        SELECT 
                            tbl_auditoria_os.auditoria_status 
                        FROM tbl_auditoria_os
                        JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                        WHERE os = {$os}
                        AND tbl_auditoria_status.produto = 't' 
                        AND tbl_auditoria_os.observacao ILIKE '%Produtos fora de garantia%'
                        ORDER BY data_input DESC";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) > 0){
                        $sql_up = "
                            UPDATE tbl_os SET cortesia = 't'
                            WHERE fabrica = $login_fabrica
                            AND os IN (SELECT os FROM tbl_os_campo_extra WHERE fabrica = $login_fabrica AND os_revenda = $os)";
                        $res_up = pg_query($con, $sql_up);
                    }
                }
            }else{
                $update = "
                    UPDATE tbl_auditoria_os SET
                        liberada = current_timestamp,
                        justificativa = E'{$auxJustificativa}',
                        admin = {$login_admin},
                        bloqueio_pedido = false
                    WHERE os = {$os}
                    AND reprovada isnull and cancelada isnull
                    AND auditoria_os = {$row->auditoria_os} ";
                $resUpdate = pg_query($con, $update);
            }

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao aprovar auditorias #002");
            }

            if (in_array($login_fabrica, array(171,190)) && strtolower($row->observacao) == 'auditoria de fechamento') {
                $finaliza_os_grohe = true;
                $finaliza_os_auditoria = true;
            }
            
            if (in_array($login_fabrica, array(169,170))) {
            	$sqlAuditoria = "SELECT auditoria_os FROM tbl_auditoria_os WHERE auditoria_os = {$row->auditoria_os} AND LOWER(observacao) ~ 'os em auditoria de ressarcimento';";
            	$resAuditoria = pg_query($con, $sqlAuditoria);

            	if (pg_num_rows($resAuditoria) > 0) {
                    $sqlRessarcimento = "
                    	UPDATE tbl_ressarcimento SET
                            aprovado = CURRENT_TIMESTAMP
                    	WHERE os = {$os}
                    	AND aprovado IS NULL
                    	AND cancelado IS NULL
                    ";
		            $resRessarcimento = pg_query($con, $sqlRessarcimento);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao aprovar auditorias #003");
                    }
                }
            }

            if (in_array($login_fabrica, [184,200]) && strtolower($row->observacao) == 'troca de peça usando estoque') {

                $sqlPosto = "SELECT posto
                         FROM tbl_os
                         WHERE os = {$os}";
                $resPosto = pg_query($con, $sqlPosto);

                $idPosto = pg_fetch_result($resPosto, 0, 'posto');

                $sqlCom = "INSERT INTO tbl_comunicado
                        (
                            fabrica,
                            posto,
                            obrigatorio_site,
                            tipo,
                            ativo,
                            descricao,
                            mensagem
                        )
                        VALUES
                        (
                            {$login_fabrica},
                            {$idPosto},
                            true,
                            'Com. Unico Posto',
                            true,
                            'Auditoria - Troca de peça usando estoque',
                            'OS {$os}: O valor das peças de reposição será pago no extrato.'
                        )  RETURNING comunicado
                    ";
                $resCom = pg_query($con, $sqlCom);

                $sqlInteracaoOs = "INSERT INTO tbl_os_interacao (os,fabrica,posto,comentario)
                                   VALUES ({$os},{$login_fabrica},{$idPosto},'O valor das peças de reposição será pago no extrato.')";
                $resInteracaoOs = pg_query($con, $sqlInteracaoOs);

                $envia_comunicado = false;
            }


            if ($tipo_os == "OSR"){

                $sqlVerificaExplodida = "SELECT os_revenda, 
                                                revenda, 
                                                posto,
                                                TO_CHAR(data_abertura, 'dd/mm/yyyy') as data_abertura,
                                                quem_abriu_chamado,
                                                tbl_revenda.cnpj,
                                                tbl_revenda.nome,
                                                tbl_revenda.fone
                                         FROM tbl_os_revenda
                                         LEFT JOIN tbl_revenda USING(revenda)
                                         WHERE explodida IS NULL
                                         AND os_revenda = {$os}";
                $resVerificaExplodida = pg_query($con, $sqlVerificaExplodida);

                if (pg_num_rows($resVerificaExplodida) > 0) {

                    include_once "../os_cadastro_unico/fabricas/regras_os_revenda.php";
                    include_once "../os_cadastro_unico/fabricas/{$login_fabrica}/regras_os_revenda.php";

                    $os_revenda = $os;

                    $campos = [
                        'revenda'           => pg_fetch_result($resVerificaExplodida, 0, 'revenda'),
                        'posto_id'          => pg_fetch_result($resVerificaExplodida, 0, 'posto'),
                        'data_abertura'     => pg_fetch_result($resVerificaExplodida, 0, 'data_abertura'),
                        'revenda_cnpj'      => pg_fetch_result($resVerificaExplodida, 0, 'cnpj'),
                        'revenda_nome'      => pg_fetch_result($resVerificaExplodida, 0, 'nome'),
                        'revenda_fone'      => pg_fetch_result($resVerificaExplodida, 0, 'fone'),
                        'revenda_contato'   => pg_fetch_result($resVerificaExplodida, 0, 'quem_abriu_chamado')
                    ];

                    grava_os();
                    
                }

            }

        }

        if (in_array($login_fabrica, array(169,170)) && !empty($data_fechamento)) {
            $sqlAuditoria = "
                SELECT auditoria_os FROM tbl_auditoria_os WHERE os = {$os} AND liberada IS NULL AND cancelada IS NULL
            ";
            $resAuditoria = pg_query($con, $sqlAuditoria);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao aprovar auditorias");
            }

            if (pg_num_rows($resAuditoria) == 0) {
                $updateBaixada = "UPDATE tbl_os_extra SET baixada = NULL WHERE os = {$os}";
                $resUpdateBaixada = pg_query($con, $updateBaixada);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao aprovar auditorias #004");
                }
            }
        }

        if ($login_fabrica == 176 && in_array("Auditoria da Fábrica - Auditoria de Fábrica: Os de Instalação", $auditorias)) {
            $sqlOs = "
                SELECT op.serie, op.produto, p.referencia
                FROM tbl_os_produto op
                INNER JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = {$login_fabrica}
                WHERE op.os = {$os}
            ";
            $resOs = pg_query($con, $sqlOs);

            $serie      = pg_fetch_result($resOs, 0, 'serie');
            $produto    = pg_fetch_result($resOs, 0, 'produto');
            $referencia = pg_fetch_result($resOs, 0, 'referencia');

            $sqlSerie = "
                SELECT numero_serie
                FROM tbl_numero_serie
                WHERE fabrica = {$login_fabrica}
                AND produto = {$produto}
                AND UPPER(serie) = UPPER('{$serie}')
            ";
            $resSerie = pg_query($con, $sqlSerie);

            if (!pg_num_rows($resSerie)) {
                $sqlSerie = "
                    INSERT INTO tbl_numero_serie
                    (fabrica, serie, referencia_produto, produto, garantia_extendida)
                    VALUES
                    ({$login_fabrica}, '{$serie}', '{$referencia}', {$produto}, true)
                ";
                $resSerie = pg_query($con, $sqlSerie);
            } else {
                $numero_serie = pg_fetch_result($resSerie, 0, "numero_serie");

                $sqlSerie = "
                    UPDATE tbl_numero_serie SET
                        garantia_extendida = true
                    WHERE fabrica = {$login_fabrica}
                    AND numero_serie = {$numero_serie}
                ";
                $resSerie = pg_query($con, $sqlSerie);
            }

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao aprovar auditorias #005");
            }
        }

        if ($tipo_os == "OSR"){
            $update = "
                UPDATE tbl_auditoria_os_revenda SET
                    {$updateSemMO}
                WHERE os_revenda = {$os}
                {$whereLiberada}
            ";
        }else{
            $update = "
                UPDATE tbl_auditoria_os SET
                    {$updateSemMO}
                WHERE os = {$os}
                {$whereLiberada}
            ";
        }
        $resUpdate = pg_query($con, $update);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao aprovar auditorias #006");
	    }

        if(in_array($login_fabrica, [165, 177])) {
            $sql = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os({$os}) WHERE os = {$os}";
            $res = pg_query($con,$sql);
        }

        if ($login_fabrica == 158 && isset($_POST["sem_mao_de_obra_kof"])) {
            $sem_mao_de_obra_kof = $_POST["sem_mao_de_obra_kof"];

            if ($sem_mao_de_obra_kof == "true") {
                $update = "
                    UPDATE tbl_os_extra SET
                        admin_paga_mao_de_obra = true
                    WHERE os = {$os}
                ";
                $resUpdate = pg_query($con, $update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao aprovar auditorias #007");
                }
            }
        }

        if ($envia_comunicado) {
            $sql = "
                INSERT INTO tbl_comunicado
                (
                    fabrica,
                    posto,
                    obrigatorio_site,
                    tipo,
                    ativo,
                    descricao,
                    mensagem
                )
                VALUES
                (
                    {$login_fabrica},
                    {$posto},
                    true,
                    'Com. Unico Posto',
                    true,
                    'Auditoria',
                    'OS {$os} teve as seguintes auditorias aprovadas pela fábrica:<br />".implode("<br />", $auditorias)."'
                )  RETURNING comunicado
            ";

    	    $res = pg_query($con, $sql);
            $id_comunicado = pg_fetch_result($res, 0, comunicado);
        }

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao enviar o comunicado para o posto autorizado");
        }

        if ($login_fabrica == 175){
            $sql = "
                INSERT INTO tbl_comunicado
                (
                    fabrica,
                    posto,
                    obrigatorio_site,
                    tipo,
                    ativo,
                    descricao,
                    mensagem,
                    tecnico
                )
                VALUES
                (
                    {$login_fabrica},
                    {$posto},
                    true,
                    'Com. Unico Posto',
                    true,
                    'Auditoria',
                    'OS {$os} teve as seguintes auditorias aprovadas pela fábrica:<br />".implode("<br />", $auditorias)."',
                    true
                )  RETURNING comunicado
            ";
            $res = pg_query($con, $sql);
            $id_comunicado_tecnico = pg_fetch_result($res, 0, comunicado);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao enviar o comunicado para o técnico do posto autorizado");
            }
        }

        if ($login_fabrica == 123) {
            $sql = "SELECT  tbl_produto.referencia AS ref,
                            consumidor_celular
                    FROM tbl_os 
                    JOIN tbl_produto USING(produto) 
                    WHERE os = $os 
                    AND fabrica = $login_fabrica
                    AND status_checkpoint = 3";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                $ref      = pg_fetch_result($res, 0, 'ref'); 
                $contato  = pg_fetch_result($res, 0, 'consumidor_celular');

                if (!empty($contato)) {
                    include_once "../class/sms/sms.class.php";

                    $sms = new SMS();
                    $mensagem_sms = "Informamos que as peças para reparo de seu equipamento $ref já chegaram na Assistência. Favor aguardar o reparo e contato da Assistência Técnica.";
                    $enviar_sms = $sms->enviarMensagem($contato, $os, '', $mensagem_sms);

                    if (!$enviar_sms) {
                        $msg_erro = "Erro ao enviar SMS ao consumidor";
                    }
                }
            }
        }

        pg_query($con, "COMMIT");
        $begin = false;
        
        if ($login_fabrica == 175) {
            $sqlAuditoriaFechamento = "
                SELECT * FROM tbl_auditoria_os WHERE os = {$os} AND observacao = 'Auditoria de Fechamento' AND liberada IS NOT NULL
            ";
            $resAuditoriaFechamento = pg_query($con, $sqlAuditoriaFechamento);
            
            if (pg_num_rows($resAuditoriaFechamento) > 0) {
                $fecha_os = true;
                
                $classOs->calculaOs();
                $classOs->finaliza($con);
            }
        }
        
        if ($login_fabrica == 163) {
            $fecha_os = true;

            // Finaliza OS se auditoria for OS Consertada
            $sql = "SELECT  tbl_os.posto,
                            tbl_os.data_conserto,
                            tbl_os.status_checkpoint,
                            tbl_auditoria_os.*
                        FROM tbl_os
                        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
                            AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                            AND tbl_tipo_posto.fabrica = {$login_fabrica}
                            AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                        JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os
                            AND tbl_os.fabrica = {$login_fabrica}
                        WHERE tbl_os.os = {$os}
                            AND (
                            UPPER(fn_retira_especiais(TRIM(tbl_auditoria_os.observacao ))) = UPPER(fn_retira_especiais(TRIM('Auditoria OS Consertada' )))
                            OR
                            UPPER(fn_retira_especiais(TRIM(tbl_auditoria_os.observacao ))) = UPPER(fn_retira_especiais(TRIM('OS em auditoria de lançamento de peça')))
                            )
                            AND (tbl_tipo_posto.posto_interno IS NOT TRUE OR tbl_tipo_posto.tipo_revenda IS NOT TRUE);";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $data_conserto = pg_fetch_result($res, 0, data_conserto);
                $observacao_f = pg_fetch_result($res, 0, observacao);
                $status_checkpoint_f = pg_fetch_result($res, 0, status_checkpoint);

                if (empty($data_conserto)) {

                    $sql = "UPDATE tbl_os SET data_conserto = current_timestamp WHERE os = $os";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao finalizar OS ".$os." !");
                    }

                }

                $classOs->calculaOs();
                $classOs->finaliza($con);

                $sql = "SELECT fn_os_status_checkpoint_os($os)";
                $res = pg_query($con, $sql);

                if (!strlen(pg_last_error())) {
                    $status_checkpoint = pg_fetch_result($res, 0, 0);

                    $sql = "UPDATE tbl_os SET status_checkpoint = {$status_checkpoint} WHERE os = $os";
                    $res = pg_query($con, $sql);
                } else {
                    throw new Exception("Erro ao finalizar OS ".$os." !");
                }
            }

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao finalizar OS ".$os." !");
            }

            //Verifica se a Auditoria é de Produto de troca para direcionar para tela de Troca Produto
            $sql = "SELECT tbl_auditoria_status.auditoria_status
                        FROM tbl_auditoria_status
                            INNER JOIN tbl_auditoria_os using(auditoria_status)
                        WHERE produto is true
                        AND os = {$os}
                        AND tbl_auditoria_os.observacao ILIKE '%produto de troca%';";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) > 0) {
                $troca_obrigatoria = true;
            }
        }

        if (in_array($login_fabrica, array(171)) && $finaliza_os_grohe) {

            $sql_tp_atendimento = "
                SELECT tbl_os.os
                FROM tbl_os
                JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                WHERE tbl_tipo_atendimento.fora_garantia IS NOT TRUE
                AND tbl_os.os = {$os}
            ";
            $res_tp_atendimento = pg_query($con, $sql_tp_atendimento);

            if (pg_num_rows($res_tp_atendimento) > 0) {
                $classOs->calculaOs();
            }

            $classOs->finaliza($con);
        }

        if ($login_fabrica == 190 AND $finaliza_os_auditoria == true){
            $sql_aud = "
                SELECT tbl_auditoria_os.auditoria_os
                FROM tbl_auditoria_os
                WHERE tbl_auditoria_os.os = $os
                AND tbl_auditoria_os.liberada IS NULL";
            $res_aud = pg_query($con, $sql_aud);

            if (pg_num_rows($res_aud) == 0){
                $sql_update_os = "UPDATE tbl_os SET data_fechamento = now() WHERE os = $os AND fabrica = $login_fabrica";
                $res_update_os = pg_query($con, $sql_update_os);

                $classOs->calculaOs();
                $classOs->finaliza($con);
            }
        }
        
        if (in_array($login_fabrica, array(169,170)) && !empty($data_fechamento)) {
            $classOs->calculaOs();
        }

        exit(json_encode(array(
            "sucesso"         => true,
            "aprovado_sem_mo" => $aprovado_sem_mo,
            "data"            => date("d/m/Y"),
            "admin"           => $login_login,
            "justificativa"   => utf8_encode($justificativa),
            "troca_obrigatoria" => $troca_obrigatoria,
            "aprovacao_unica" => $auditoria
        )));
    } catch(Exception $e) {
        if ($begin == true) {
            pg_query($con, "ROLLBACK");
        }

        if ($fecha_os == true) {

            pg_query($con,'BEGIN');

            $sql = "DELETE FROM tbl_comunicado WHERE comunicado = {$id_comunicado};";
            $res = pg_query($con,$res);
            
            if ($login_fabrica == 175) {
                $sql = "DELETE FROM tbl_comunicado WHERE comunicado = {$id_comunicado_tecnico};";
                $res = pg_query($con,$res);
                
                $sql = "
                    UPDATE tbl_auditoria_os SET
                        liberada = NULL
                    WHERE os = {$os}
                    AND observacao = 'Auditoria de Fechamento'
                ";
                $res = pg_query($con, $sql);
                
                $sql = "
                    UPDATE tbl_os SET
                        status_checkpoint = 14
                    WHERE os = {$os}
					AND finalizada isnull
                ";
                $res = pg_query($con, $sql);
            } else {
                $sql = "UPDATE tbl_auditoria_os SET
                        liberada = null,
                        justificativa = null,
                        admin = null
                    WHERE os = {$os}
                    AND observacao = 'Auditoria OS Consertada';";
                $res = pg_query($con,$sql);

                $sql = "UPDATE tbl_os SET
                        data_conserto = null,
                        status_checkpoint = $status_checkpoint_f,
                        admin = null
                    WHERE os = {$os}
                    AND observacao = 'Auditoria OS Consertada';";
                $res = pg_query($con,$sql);
            }
            
            if (pg_last_error()) {
                pg_query($con,'ROLLBACK');
            } else {
                pg_query($con,'COMMIT');
            }
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax_reprova_auditoria"]) {
include_once "../class/tdocs.class.php"; 

$tDocs       = new TDocs($con, $login_fabrica);
$reprovaAud  = true;

    try {
        $begin         = false;
        $os            = $_POST["os"];
        $justificativa = utf8_decode($_POST["justificativa"]);
	    $auxJustificativa = addslashes($justificativa);
        $auditoria     = $_POST["auditoria"];
        $anexo_cancela = $_POST['anexo_cancela'];
        $zerar_mo      = $_POST['zerar_mo'];
        $tipo_os       = $_POST["tipo_os"];

        if (!empty($auditoria)) {
            $whereAuditoria = "AND auditoria_os.auditoria_os = {$auditoria}";
        }

        if ($tipo_os == "OSR"){
            $sql = "
                SELECT
                    auditoria_os.auditoria_os,
                    auditoria_status.descricao,
                    auditoria_status.auditoria_status as a, 
                    osr.posto,
                    auditoria_os.observacao
                FROM tbl_auditoria_os_revenda AS auditoria_os
                INNER JOIN tbl_auditoria_status AS auditoria_status ON auditoria_status.auditoria_status = auditoria_os.auditoria_status
                INNER JOIN tbl_os_revenda AS osr ON osr.os_revenda = auditoria_os.os_revenda AND osr.fabrica = {$login_fabrica}
                WHERE auditoria_os.os_revenda = {$os}
                {$whereAuditoria}
                AND (auditoria_os.reprovada IS NULL AND auditoria_os.cancelada IS NULL)
            ";
            $res = pg_query($con, $sql);
        }else{
            $sql = "
                SELECT
                    auditoria_os.auditoria_os,
                    auditoria_status.descricao,
                    auditoria_status.auditoria_status as a, 
                    os.posto,
                    auditoria_os.observacao
                FROM tbl_auditoria_os AS auditoria_os
                INNER JOIN tbl_auditoria_status AS auditoria_status ON auditoria_status.auditoria_status = auditoria_os.auditoria_status
                INNER JOIN tbl_os AS os ON os.os = auditoria_os.os AND os.fabrica = {$login_fabrica}
                WHERE auditoria_os.os = {$os}
                {$whereAuditoria}
                AND (auditoria_os.reprovada IS NULL AND auditoria_os.cancelada IS NULL)
            ";
            $res = pg_query($con, $sql);
        }
        
        if (strlen(pg_last_error()) > 0 || !pg_num_rows($res)) {
            throw new Exception("Erro ao buscar auditorias");
        }

        $posto      = pg_fetch_result($res, 0, 'posto');
        $aud        = pg_fetch_result($res, 0 , 'a');
        $observacao = pg_fetch_result($res, 0, 'observacao');

        $IdAud = [];

        if (($login_fabrica == 35 && $aud != 2) || $zerar_mo == "sim") {
            $mo = ", paga_mao_obra = false";
        }
        pg_query($con, "BEGIN");
        $begin = true;

        while ($row = pg_fetch_object($res)) {

            $auditorias_comunicados[] = "{$row->descricao} - {$row->observacao}";

            $IdAud[] = $row->auditoria_os;

            if ($tipo_os == "OSR"){
                $update = "
                    UPDATE tbl_auditoria_os_revenda SET
                        reprovada = current_timestamp,
                        justificativa = E'{$auxJustificativa}',
                        admin = {$login_admin}
                    WHERE os_revenda = {$os}
					AND liberada isnull
                    AND auditoria_os = {$row->auditoria_os}
                ";
                $resUpdate = pg_query($con, $update);
            }else{
                $update = "
                    UPDATE tbl_auditoria_os SET
                        reprovada = current_timestamp,
                        justificativa = E'{$auxJustificativa}',
                        admin = {$login_admin}
                        {$mo}    
                    WHERE os = {$os}
					AND liberada isnull
                    AND auditoria_os = {$row->auditoria_os}
                ";
                $resUpdate = pg_query($con, $update);
            }
            
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao reprovar auditorias");
            }
        }

        if (in_array($login_fabrica, [131, 157])) {

            $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND LOWER(descricao) = 'cancelado'";
            $res = pg_query($con, $sql);

            if (!pg_num_rows($res)) {
                throw new Exception("Erro ao cancelar ordem de serviço #2");
            }

            $servico_realizado = pg_fetch_result($res, 0, "servico_realizado");

            $sql = "SELECT tbl_os_item.os_item, tbl_os_item.servico_realizado FROM tbl_os
                        JOIN tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
                        JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                        JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
                            AND tbl_servico_realizado.gera_pedido IS TRUE
                    WHERE tbl_os.os = {$os} AND tbl_os.fabrica = {$login_fabrica}";

            //die(nl2br($sql));
            $res = pg_query($con, $sql);
            $resServ = [];

            if(pg_num_rows($res)){
				foreach (pg_fetch_all($res) as $i => $row) {
					$row = (object) $row;

                    $resServ[] = ["os_item"=>$row->os_item, "servico_realizado"=>$row->servico_realizado];

					pg_query($con, "BEGIN");
					$update = "UPDATE tbl_os_item SET 
							servico_realizado = {$servico_realizado}
						WHERE os_item = $row->os_item";
					pg_query($con, $update);

					if (strlen(pg_last_error()) > 0) {
						pg_query($con, "ROLLBACK");
						throw new Exception("Erro ao cancelar ordem de serviço #1");
					}
				}
			}
            if($login_fabrica == 157){

                $update_fechar = "UPDATE tbl_os SET
                                    --data_hora_fechamento = CURRENT_TIMESTAMP,
                                    data_fechamento = CURRENT_DATE,
                                    finalizada = CURRENT_TIMESTAMP,
                                    excluida = 'f'
                                    WHERE fabrica = {$login_fabrica}
                                    AND os = {$os}";                        
                //die(nl2br($update_fechar));
                $res_update_fechar =  pg_query($con, $update_fechar);
            }

			if (strlen(pg_last_error()) > 0) {
				pg_query($con, "ROLLBACK");
                $begin = false;
				throw new Exception("Erro ao cancelar ordem de serviço #1");
			}
		}

        if (in_array($login_fabrica, [184,200]) && strtolower($observacao) == 'troca de peça usando estoque') {

            if ($login_fabrica <> 200){
                $sql = "SELECT obs_adicionais
                        FROM tbl_os_extra
                        WHERE os = {$os}";
                $res = pg_query($con, $sql);

                $obs_adicionais_arr = json_decode(pg_fetch_result($res, 0, 'obs_adicionais'), true);
                $obs_adicionais_arr["gera_pedido_obrigatorio"] = true;
                $obs_adicionais_json = json_encode($obs_adicionais_arr);

                $sql = "UPDATE tbl_os_extra
                        SET obs_adicionais = '{$obs_adicionais_json}'
                        WHERE os = {$os}";
                $res = pg_query($con, $sql);
           }
           
            $sqlPosto = "SELECT posto
                         FROM tbl_os
                         WHERE os = {$os}";
            $resPosto = pg_query($con, $sqlPosto);
            $idPosto = pg_fetch_result($resPosto, 0, 'posto');

            $sql = "INSERT INTO tbl_comunicado
                    (
                        fabrica,
                        posto,
                        obrigatorio_site,
                        tipo,
                        ativo,
                        descricao,
                        mensagem
                    )
                    VALUES
                    (
                        {$login_fabrica},
                        {$idPosto},
                        true,
                        'Com. Unico Posto',
                        true,
                        'Auditoria - Troca de peça usando estoque',
                        'OS {$os}: as peças serão enviadas pelo fabricante.'
                    )  RETURNING comunicado
                ";
            $res = pg_query($con, $sql);

            $sqlInteracaoOs = "INSERT INTO tbl_os_interacao (os,fabrica,posto,comentario)
                               VALUES ({$os},{$login_fabrica},{$idPosto},'As peças serão enviadas pelo fabricante.')";
            $resInteracaoOs = pg_query($con, $sqlInteracaoOs);

        }

        if (in_array($login_fabrica, array(169,170)) && in_array(strtolower($observacao), array("os em auditoria de troca de produto", "os em auditoria de ressarcimento"))) {
            $sql = "SELECT admin, ressarcimento FROM tbl_os_troca WHERE os = {$os} ORDER BY os_troca DESC LIMIT 1";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $admin = pg_fetch_result($res, 0, 'admin');
                $ressarcimento = pg_fetch_result($res, 0, 'ressarcimento');

                $sql = "SELECT tbl_hd_chamado_extra.hd_chamado  AS hd_ext,
                               tbl_os.hd_chamado AS hd_os 
                          FROM tbl_os
                     LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.os=tbl_os.os
                         WHERE tbl_os.os = {$os}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {

                    $hd_chamado = pg_fetch_result($res, 0, 'hd_os');
                    if (strlen($hd_chamado) == 0) {
                        $hd_chamado = pg_fetch_result($res, 0, 'hd_ext');
                    }

            if(!empty($hd_chamado)){
                if (strtolower($observacao) == "os em auditoria de troca de produto") {
                $sql = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} AND UPPER(descricao) = '03. TROCA REPROVADA'";
                } else {
                $sql = "SELECT hd_motivo_ligacao, descricao FROM tbl_hd_motivo_ligacao WHERE fabrica = {$login_fabrica} AND UPPER(descricao) = '03. RESSARCIMENTO REPROVADO'";
                }
                $res = pg_query($con, $sql);

                if (!pg_num_rows($res)) {
                throw new Exception("Erro ao reprovar auditorias provid¿ncias n¿o configuradas");
                }

                $providencia = pg_fetch_result($res, 0, 'hd_motivo_ligacao');
                $providencia_descricao = pg_fetch_result($res, 0, 'descricao');

                $sql = "
                UPDATE tbl_hd_chamado SET
                    atendente = $admin
                WHERE hd_chamado = $hd_chamado
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao reprovar auditorias".$sql);
                }

                $sql = "
                UPDATE tbl_hd_chamado_extra SET
                    hd_motivo_ligacao = $providencia
                WHERE hd_chamado = $hd_chamado
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao reprovar auditorias");
                }

                $sql = "
                INSERT INTO tbl_hd_chamado_item
                (hd_chamado, data, comentario, admin, interno, status_item, hd_motivo_ligacao)
                VALUES
                ($hd_chamado, current_timestamp, '{$providencia_descricao}: {$justificativa}', $login_admin, true, 'Aberto', $providencia)
                ";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao reprovar auditorias");
                }
            }
                }

                if (strtolower($observacao) == "os em auditoria de troca de produto") {
                    $sql = "SELECT servico_realizado
                        FROM tbl_servico_realizado
                        WHERE fabrica = {$login_fabrica}
                        AND UPPER(descricao) = UPPER('CANCELADO')";
                    $res = pg_query($con, $sql);

                    $servico_realizado_cancela_peca = pg_fetch_result($res, 0, "servico_realizado");

                    if (empty($servico_realizado_cancela_peca)) {
                        throw new Exception("Erro ao reprovar auditorias");
                    }

                    $sql = "
                        SELECT tbl_os_item.os_item, tbl_os_item.pedido_item, tbl_os_item.qtde, tbl_os_item.pedido, tbl_os.posto, tbl_os.os, tbl_os_item.peca
                        FROM tbl_os_item
                        INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                        LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = {$login_fabrica}
                        WHERE tbl_os.os = $os
                        AND (tbl_os_item.pedido IS NULL OR tbl_pedido.status_pedido IN(1, 2,12,5))
                    ";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) > 0) {
                        while ($osItem = pg_fetch_object($res)) {
                            $updateOsItem = "UPDATE tbl_os_item SET servico_realizado = {$servico_realizado_cancela_peca} WHERE os_item = {$osItem->os_item} and pedido is null";
                            $resUpdateOsItem = pg_query($con, $updateOsItem);

                            if (!empty($osItem->pedido_item)) {
                                $updatePedidoItem = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,{$osItem->pedido},{$osItem->peca},{$osItem->os_item},'Produto Trocado',$login_admin) from tbl_pedido_item
                                                    WHERE pedido_item = {$osItem->pedido_item} and qtde -(qtde_faturada+qtde_cancelada) > 0 ";
                                $resUpdatePedidoItem = pg_query($con, $updatePedidoItem);

                                $atualizaStatusPedido = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$osItem->pedido})";
                                $resAtualizaStatusPedido = pg_query($con, $atualizaStatusPedido);
                            }

                            if (pg_last_error($con)) {
                                $msg_erro["msg"]["grava_os_troca"] = "Erro ao lan¿ar troca/ressarcimento";
                                break;
                            }
                        }
                    }
                } else {
                    $sql = "
                        UPDATE tbl_ressarcimento SET
                            cancelado = CURRENT_TIMESTAMP
                        WHERE os = $os
                        AND cancelado IS NULL
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao reprovar auditorias");
                    }
                }
            } else {
                throw new Exception("Erro ao reprovar auditorias");
            }
        }

        if ($login_fabrica == 131 OR ($login_fabrica == 200 AND strtolower($observacao) == 'troca de peça usando estoque')) {
            $sql = "SELECT servico_realizado, descricao, peca_estoque
                    FROM tbl_servico_realizado
                    WHERE fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            for ($z=0; $z < pg_num_rows($res); $z++) { 
                $servico_realizado = pg_fetch_result($res, $z, "servico_realizado");
                $descricao = pg_fetch_result($res, $z, "descricao");
                $peca_estoque = pg_fetch_result($res, $z, "peca_estoque");

                if (strtolower($descricao) == "cancelado"){
                    $servico_realizado_cancela_peca = pg_fetch_result($res, $z, "servico_realizado");
                }

                if ($peca_estoque == "t" AND $login_fabrica == 200){
                    $servico_realizado_estoque = pg_fetch_result($res, $z, "servico_realizado");
                    $cond_servico_estoque = " AND tbl_os_item.servico_realizado = $servico_realizado_estoque ";
                }
            }
            
            $sql = "
                SELECT tbl_os_item.os_item, tbl_peca.referencia AS ref_peca, tbl_os.posto AS posto_id
                FROM tbl_os_item
                INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                WHERE tbl_os.os = $os
                AND tbl_os_item.pedido IS NULL $cond_servico_estoque";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                if ($login_fabrica == 200){
                    $array_ref_peca = array();
                    while ($osItem = pg_fetch_object($res)) {
                        $updateOsItem = "UPDATE tbl_os_item SET servico_realizado = {$servico_realizado_cancela_peca} WHERE os_item = {$osItem->os_item} and pedido is null";
                        $resUpdateOsItem = pg_query($con, $updateOsItem);
                        $array_ref_peca[] = $osItem->ref_peca;
                        $posto_id = $osItem->posto_id;
                    }

                    $msg_interacao = "AS seguintes peças: Ref: ".implode(" - Ref: ", $array_ref_peca)." foram usadas do estoque do Posto";
                        
                    $insert_interacao ="INSERT INTO tbl_os_interacao ( os, comentario, fabrica, posto )VALUES($os, '$msg_interacao', $login_fabrica, $posto_id)";
                    $res_insert_intercao = pg_query($con, $insert_interacao);
                }else{
                    while ($osItem = pg_fetch_object($res)) {
                        $updateOsItem = "UPDATE tbl_os_item SET servico_realizado = {$servico_realizado_cancela_peca} WHERE os_item = {$osItem->os_item} and pedido is null";
                        $resUpdateOsItem = pg_query($con, $updateOsItem);
                    }
                }
            }
        }

        if (in_array($login_fabrica, [35, 157])) {
            $sql = "INSERT INTO tbl_comunicado
                    (
                        fabrica,
                        posto,
                        obrigatorio_site,
                        tipo,
                        ativo,
                        descricao,
                        mensagem
                    )
                    VALUES
                    (
                        {$login_fabrica},
                        {$posto},
                        true,
                        'Com. Unico Posto',
                        true,
                        'Auditoria',
                        'OS {$os} teve as seguintes auditorias reprovadas pela fábrica:<br />".implode("<br />", $auditorias_comunicados)."'
                    )  RETURNING comunicado
                ";

            $res = pg_query($con, $sql);
            $id_comunicado = pg_fetch_result($res, 0, comunicado);
        }

        pg_query($con, "COMMIT");

        if ($login_fabrica == 157) {
            $classOsNew = new \Posvenda\Os($login_fabrica, $os);
            
            $classOsNew->calculaOs($os);
            #$classOsNew->finaliza($con);
        }

        if (!empty($anexo_cancela)) {

            $dadosAnexo = json_decode($anexo_cancela, 1);
            $anexoID = $tDocs->setDocumentReference($dadosAnexo, $os, "anexar", false, "oscancela");
            
        }

        exit(json_encode(array(
            "sucesso"          => true,
            "data"             => date("d/m/Y"),
            "admin"            => $login_login,
            "justificativa"    => utf8_encode($justificativa),
            "reprovacao_unica" => $auditoria,
            "os"               => $os
        )));
    } catch(Exception $e) {
        if ($begin == true) {
            pg_query($con, "ROLLBACK");
        }

        if ($reprovaAud && $login_fabrica == 157) {

            pg_query($con,'BEGIN');

            $sql = "DELETE FROM tbl_comunicado WHERE comunicado = {$id_comunicado};";
            $res = pg_query($con,$res);

            if ($zerar_mo == "sim") {
                $mo = ", paga_mao_obra = true";
            }

            if (count($IdAud) > 0) {
                foreach ($IdAud as $key => $value) {
                    if ($tipo_os == "OSR"){
                        $update = "
                            UPDATE tbl_auditoria_os_revenda SET
                                reprovada = null,
                                justificativa = null,
                                admin = null
                            WHERE os_revenda = {$os}
                            AND auditoria_os = {$value}
                        ";
                        $resUpdate = pg_query($con, $update);
                    }else{
                        $update = "
                            UPDATE tbl_auditoria_os SET
                                reprovada = null,
                                justificativa = null,
                                admin = null
                                $mo
                            WHERE os = {$os}
                            AND auditoria_os = {$value}
                        ";
                        $resUpdate = pg_query($con, $update);
                    }
                }
            }

            if (count($resServ) > 0) {
                foreach ($resServ as $key => $value) {
                    $update = "UPDATE tbl_os_item SET 
                               servico_realizado = ".$value["servico_realizado"]."
                               WHERE os_item = ".$value["os_item"];
                    pg_query($con, $update);
                }
            }

            $update_fechar = "UPDATE tbl_os SET
                    data_fechamento = null,
                    finalizada = null,
                    WHERE fabrica = {$login_fabrica}
                    AND os = {$os}";                        
            $res_update_fechar =  pg_query($con, $update_fechar);

            $sql = "DELETE FROM tbl_comunicado WHERE comunicado = {$id_comunicado};";
            $res = pg_query($con,$res);
            
            if (pg_last_error()) {
                pg_query($con,'ROLLBACK');
            } else {
                pg_query($con,'COMMIT');
            }
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax_altera_valores_adicionais"]) {
    try {
        $begin = false;

        $os = $_POST["os"];
        $valores_adicionais = $_POST["valores_adicionais"];

        if (empty($os)) {
            throw new Exception("OS não informada");
        }

        $sql = "SELECT os_extra.extrato, os.data_fechamento, os_campo_extra.valores_adicionais
                FROM tbl_os AS os
                INNER JOIN tbl_os_extra AS os_extra ON os_extra.os = os.os
                INNER JOIN tbl_os_campo_extra AS os_campo_extra ON os_campo_extra.os = os.os AND os_campo_extra.fabrica = {$login_fabrica}
                WHERE os.fabrica = {$login_fabrica}
                AND os.os = {$os}";
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("OS não encontrada");
        }

        $data_fechamento           = pg_fetch_result($res, 0, "data_fechamento");
        $extrato                   = pg_fetch_result($res, 0, "extrato");
        $valores_adicionais_antigo = json_decode(pg_fetch_result($res, 0, "valores_adicionais"), true);

        $valores_adicionais = array_map(function($v) {
            return array($v["descricao"] => $v["valor"]);
        }, $valores_adicionais);

        $diff = array();

        foreach ($valores_adicionais as $valor) {
            $valor_antigo = array_filter($valores_adicionais_antigo, function($v) use($valor) {
                if (key($v) == key($valor)) {
                    return true;
                }

                return false;
            });

            if (count($valor_antigo) > 0) {
                $valor_antigo = $valor_antigo[key($valor_antigo)];

                $va = $valor_antigo[key($valor_antigo)];
                $v  = $valor[key($valor)];

                if ($va != $v) {
                    $diff[] = key($valor)." de {$va} para {$v}";
                }
            }
        }

        if (count($diff) > 0) {
            pg_query($con, "BEGIN");
            $begin = true;

            $valores_adicionais = json_encode($valores_adicionais);

            $sql = "
                UPDATE tbl_os_campo_extra SET
                    valores_adicionais = '{$valores_adicionais}'
                WHERE fabrica = {$login_fabrica}
                AND os = {$os}
            ";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) == 0) {
                throw new Exception("Erro ao alterar os Valores Adicionais da OS");
            }

            $mensagem = "Valores Adicionais alterados pela fábrica: ".implode(", ", $diff);

            $sql = "
                INSERT INTO tbl_os_interacao
                (programa, fabrica, os, admin, comentario, interno, exigir_resposta)
                VALUES
                ('{$_SERVER["PHP_SELF"]}', {$login_fabrica}, {$os}, {$login_admin}, '{$mensagem}', false, false)
            ";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) == 0) {
                throw new Exception("Erro ao alterar os Valores Adicionais da OS");
            }

            if (!empty($data_fechamento) && empty($extrato)) {
                $classOs->_model->calculaValorAdicional($os, $con);
            }

            pg_query($con, "COMMIT");
        }

        exit(json_encode(array("sucesso" => true)));
    } catch(Exception $e) {
        if ($begin == true) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax_altera_km"]) {
    try {
        $begin = false;

        $os = $_POST["os"];
        $km = $_POST["km"];
        $tipo_os = $_POST["tipo_os"];

        if (empty($os)) {
            throw new Exception("OS não informada");
        }

        if ($tipo_os == "OSR"){
            $sql = "
                SELECT
                    os.qtde_km,
                    NULL AS extrato,
                    NULL AS data_fechamento,
                    NULL AS qtde_diaria,
                    os.posto
                FROM tbl_os_revenda AS os
                WHERE os.fabrica = {$login_fabrica}
                AND os.os_revenda = {$os} ";
        }else{
            $sql = "
                SELECT 
                    os.qtde_km, 
                    os_extra.extrato, 
                    os.data_fechamento, 
                    os.qtde_diaria
                FROM tbl_os AS os
                INNER JOIN tbl_os_extra AS os_extra ON os_extra.os = os.os
                WHERE os.fabrica = {$login_fabrica}
                AND os.os = {$os}";
        }
        $res = pg_query($con, $sql);

        if (!pg_num_rows($res)) {
            throw new Exception("OS não encontrada");
        }

        $km_antigo       = pg_fetch_result($res, 0, "qtde_km");
        $visita_antiga   = pg_fetch_result($res, 0, "qtde_diaria");
        $data_fechamento = pg_fetch_result($res, 0, "data_fechamento");
        $extrato         = pg_fetch_result($res, 0, "extrato");
        $posto           = pg_fetch_result($res, 0, "posto");

        pg_query($con, "BEGIN");
        $begin = true;

        $visita = '';
        if (in_array($login_fabrica, array(171))) {
            $visita = ($_POST['visita'] == 0) ? "qtde_diaria = null" : "qtde_diaria = ".$_POST['visita'];
            $visita = ", {$visita}";

            $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                $campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");
                $campos_adicionais = json_decode($campos_adicionais, true);
                $campos_adicionais_antigo = $campos_adicionais;
                $campos_adicionais['qtde_km_ida']   = $_POST['km_ida'];
                $campos_adicionais['qtde_km_volta'] = $_POST['km_volta'];
                $campos_adicionais = json_encode($campos_adicionais);

                $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$campos_adicionais}' WHERE os = ${os}";
                pg_query($con, $sql);
            }
        }

        if ($tipo_os == "OSR"){
            $sql = "UPDATE tbl_os_revenda SET qtde_km = {$km} WHERE fabrica = {$login_fabrica} AND os_revenda = {$os}";
            $res = pg_query($con, $sql);
        }else{
            $sql = "UPDATE tbl_os SET qtde_km = {$km} {$visita} WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $res = pg_query($con, $sql);
        }
        
        if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) == 0) {
            throw new Exception("Erro ao alterar o KM da OS");
        }

        $km_antigo = number_format($km_antigo, 2, '.', '');
        $mensagem = "KM alterado pela fábrica de {$km_antigo} para {$km}";
        if (in_array($login_fabrica, array(171))) {
            if ($km_antigo == $km)
                unset($mensagem);

            if ($_POST['visita'] !== $visita_antiga) {
                $visita_antiga = (empty($visita_antiga)) ? 0 : $visita_antiga;
                $mensagem .= (!empty($mensagem)) ? ' / ' : '';
                $mensagem .= "Quantidade de visitas alteradas de {$visita_antiga} para ".$_POST['visita'];
            }
            if (count($campos_adicionais_antigo)) {
                $campos_adicionais_antigo['qtde_km_ida']   = number_format($campos_adicionais_antigo['qtde_km_ida'], 2, '.', '');
                $campos_adicionais_antigo['qtde_km_volta'] = number_format($campos_adicionais_antigo['qtde_km_volta'], 2, '.', '');

                if ($campos_adicionais_antigo['qtde_km_ida'] !== $_POST['km_ida']) {
                    $mensagem .= " / Quantidade de KM de ida alterado de ".$campos_adicionais_antigo['qtde_km_ida']." para ".$_POST['km_ida'];
                }
                if ($campos_adicionais_antigo['qtde_km_volta'] !== $_POST['km_volta']) {
                    $mensagem .= " / Quantidade de KM de volta alterado de ".$campos_adicionais_antigo['qtde_km_volta']." para ".$_POST['km_volta'];
                }
            }
        }

        if (!empty($mensagem)) {
            if ($tipo_os == "OSR"){
                $sql = "
                    INSERT INTO tbl_comunicado (
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
                        'Auditoria de KM',
                        'OS {$os}, {$mensagem}'
                    ) RETURNING comunicado ";
            }else{
                $sql = "
                    INSERT INTO tbl_os_interacao
                    (programa, fabrica, os, admin, comentario, interno, exigir_resposta)
                    VALUES
                    ('{$_SERVER["PHP_SELF"]}', {$login_fabrica}, {$os}, {$login_admin}, '{$mensagem}', false, false)
                ";
            }
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) == 0) {
                throw new Exception("Erro ao alterar o KM da OS");
            }

            if (!empty($data_fechamento) && empty($extrato)) {
                $classOs->_model->calculaKM($os, $con);
            }
        }
        pg_query($con, "COMMIT");

        exit(json_encode(array("sucesso" => true)));
    } catch(Exception $e) {
        if ($begin == true) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}

if ($_POST["ajax_altera_horas"]) {
    try {
        $begin = false;

        $os = $_POST["os"];
        $horas = $_POST["horas"];
        
        if (empty($os)) {
            throw new Exception("OS não informada");
        }

        pg_query($con, "BEGIN");
        $begin = true;

        $sql = "
            SELECT 
                tbl_os_campo_extra.campos_adicionais, 
                tbl_os.posto 
            FROM tbl_os_campo_extra 
            JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = $login_fabrica
            WHERE tbl_os_campo_extra.os = $os AND tbl_os_campo_extra.fabrica = $login_fabrica";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0){
            $posto = pg_fetch_result($res, 0, "posto");
            $campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");
            $campos_adicionais = json_decode($campos_adicionais, true);
            
            $campos_adicionais["horas_trabalhadas"] = $horas;
            $campos_adicionais = json_encode($campos_adicionais);
        }
        
        $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$campos_adicionais}' WHERE fabrica = {$login_fabrica} AND os = {$os}";
        $res = pg_query($con, $sql);
        
        if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) == 0) {
            throw new Exception("Erro ao alterar o quantidade de horas da OS");
        }

        $sql = "
            INSERT INTO tbl_comunicado (
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
                'Auditoria de Fechamento',
                'OS {$os}, com quantidade de horas alterada pela Fábrica'
            ) RETURNING comunicado ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0 || pg_affected_rows($res) == 0) {
            throw new Exception("Erro ao alterar o quantidade de horas da OS");
        }

        pg_query($con, "COMMIT");
        exit(json_encode(array("sucesso" => true)));
    } catch(Exception $e) {
        if ($begin == true) {
            pg_query($con, "ROLLBACK");
        }
        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
}


function procura_auditoria_tipo($auditorias, $tipo, $observacao = null) {
    $achou = false;
    foreach ($auditorias as $row => $auditoria) {
        if (is_null($observacao) && $auditoria[$tipo] == "t") {
            $achou = true;
            break;
        }else if ($auditoria[$tipo] == "t" && strtolower($auditoria["observacao"]) == strtolower(trim($observacao))) {
            $achou = true;
        }else if ($auditoria[$tipo] == "t" && strpos(strtolower($auditoria["observacao"]), strtolower($observacao))) {
            $achou = true;
            break;
        }
    }
    return $achou;
}

if ($_POST) {
    $msg_erro = array(
        "msg"    => array(),
        "campos" => array()
    );

    if ($_POST["acao"] == "pesquisar") {
        $data_inicial = $_POST["data_inicial"];
        $data_final   = $_POST["data_final"];
        $os           = trim($_POST["os"]);
        $status       = $_POST["status"];

        if (in_array($login_fabrica, [123,160])) {
            $tipo_aud = $_POST['tipo_aud'];
        }

        if (empty($os) && (empty($data_inicial) || empty($data_final)) && ($login_fabrica != 158 || ($login_fabrica == 158 && in_array($_POST["os_aberta_fechada"], array("ambas", "fechada"))))) {
            $msg_erro["msg"]["campos_obrigatorios"] = "Preencha os campos obrigatórios";
            $msg_erro["campos"][]                   = "data_inicial";
            $msg_erro["campos"][]                   = "data_final";
        }

        if (empty($os) && (!empty($data_inicial) && !empty($data_final))) {
            list($dia, $mes, $ano) = explode("/", $data_inicial);

            if (!strtotime("$ano-$mes-$dia")) {
                $msg_erro["msg"]["data_invalida"] = "Data inválida";
                $msg_erro["campos"][]             = "data_inicial";
            } else {
                $data_inicial = "$ano-$mes-$dia";
            }

            list($dia, $mes, $ano) = explode("/", $data_final);

            if (!strtotime("$ano-$mes-$dia")) {
                $msg_erro["msg"]["data_invalida"] = "Data inválida";
                $msg_erro["campos"][]             = "data_final";
            } else {
                $data_final = "$ano-$mes-$dia";
            }

            if (!$msg_erro["msg"]["data_invalida"] && strtotime($data_final) < strtotime($data_inicial)) {
                $msg_erro["msg"]["data_invalida"] = "Data Final não pode ser maior que a Data Inicial";
                $msg_erro["campos"][]             = "data_inicial";
                $msg_erro["campos"][]             = "data_final";
            }
        }
    }

    $estado   = $_POST["estado"];
    $posto_id = $_POST["posto_id"];

    if (!empty($_POST['status_auditoria'])) {
        $status_auditoria = $_POST['status_auditoria'];
    }

    if (!empty($_POST['linha'])) {
        $linha = $_POST['linha'];
    }

    if (!empty($_POST['tipo_atendimento'])) {
        $tipo_atendimento = $_POST['tipo_atendimento'];
    }

    if (!empty($_POST['unidade_negocio'])) {
        $unidade_negocio = $_POST['unidade_negocio'];
    }

    if (!empty($_POST['consumidor_revenda'])) {
        $consumidor_revenda = $_POST['consumidor_revenda'];
    }

    if (in_array($login_fabrica, array(139,169,170,175,178))) {
        if (!empty($_POST["tipo_auditoria"])) {
            $tipo_auditoria = $_POST["tipo_auditoria"];
        }

        if (!empty($_POST["inspetor"])) {
            $inspetor = $_POST["inspetor"];
        }
    }

    if (empty($msg_erro["msg"])) {
        if ($_POST["acao"] == "pesquisar") {
            if (empty($os)) {
                $tipo_data = $_POST['data_pesquisa'];
                if(empty($tipo_data) || $tipo_data == 'data_abertura') {
                    $whereData = " AND os.data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}' ";
                }elseif($tipo_data == 'data_fechamento') {
                    $whereData = " AND os.data_fechamento BETWEEN '{$data_inicial}' AND '{$data_final}' ";
                }elseif($tipo_data == 'data_auditoria') {
                    $status_pesquisa = $_POST['status'];
                    switch ($status_pesquisa) {
                        case 'aprovada':
                            $data = 'liberada';
                            break;
                        case 'reprovada':
                            $data = 'reprovada';
                            break;  
                        case 'cancelada':
                            $data = 'cancelada';
                            break;  
                        default:
                            $data = 'data_input';
                            break;  
                    }
                   // $data_final = date('Y-m-d', strtotime("+1 days",strtotime($data_final)));
                    $whereData = " AND auditoria_os.$data BETWEEN '{$data_inicial} 00:00:00 ' AND '{$data_final} 23:59:59' ";
                }
            } else {
                #$whereOs = " AND (os.os = {$os} OR os.sua_os = '{$os}') ";
                #$whereOsRevenda = " AND (os.os_revenda = {$os} OR os.sua_os = '{$os}') ";
            
                $whereOs = " AND (os.os = {$os} OR os.sua_os ILIKE '%{$os}%') ";
                $whereOsRevenda = " AND (os.os_revenda = {$os} OR os.sua_os ILIKE '%{$os}%') ";
            }

            if ($login_fabrica == 158 && $_POST["os_aberta_fechada"] != "ambas") {
                $os_aberta_fechada = $_POST["os_aberta_fechada"];

                if ($os_aberta_fechada == "fechada") {
                    $whereData = " AND (os.data_fechamento BETWEEN '{$data_inicial}' AND '{$data_final}') ";
                } elseif ($os_aberta_fechada == "aberta") {
                    $whereData = " AND os.data_fechamento IS NULL AND os.finalizada IS NULL ";
                }
            }
        }

        if (!empty($estado)) {
            if (is_array($estado)) {
                $estado = array_map(function($e) {
                    return "'".strtolower($e)."'";
                }, $estado);

                $whereEstado = "
                    AND LOWER(posto_fabrica.contato_estado) IN(".implode(", ", $estado).")
                ";
            } else {
                $whereEstado = "
                    AND LOWER(posto_fabrica.contato_estado) = LOWER('{$estado}')
                ";
            }
        }

        if (!empty($posto_id)) {
            $wherePosto = "
                AND posto_fabrica.posto = {$posto_id}
            ";
        }

        if (!empty($consumidor_revenda)) {
            $whereTipoOrdem = "AND os.consumidor_revenda = '{$consumidor_revenda}'";
        }

        if (!empty($tipo_atendimento)) {
            $whereTipoAtendimento = "AND os.tipo_atendimento = {$tipo_atendimento}";
        }

        if (!empty($unidade_negocio) && $login_fabrica == 158) {
            foreach ($unidade_negocio as $key => $value) {
                $unidade_negocios[] = "'$value'";
            }
            $whereUnidadeNegocio = "AND JSON_FIELD('unidadeNegocio', os_campo_extra.campos_adicionais) IN (".implode(',', $unidade_negocios).")";
        }

        if (count($linha) > 0) {
            $whereLinha = "AND produto.linha IN (".implode(',', $linha).")";
			$joinOsRevenda = " JOIN tbl_os_revenda_item on os.os_revenda = tbl_os_revenda_item.os_revenda join tbl_produto produto using(produto) ";
        }

        if (in_array($login_fabrica, array(139,169,170,175))) {
            if (!empty($tipo_auditoria)) {
                foreach ($tipo_auditoria as $key => $value) {
                    $dados_like[] = "'%".$value."%'";
                }
                $whereTipoAuditoria = " AND auditoria_os.observacao ilike any (array[".implode(',', $dados_like)."])";
                $whereTipoAuditoriaOSR = " AND auditoria_os.observacao ilike any (array[".implode(',', $dados_like)."])";

                // $tipos_auditoria = array_map(function($value) {
                //     return "'{$value}'";
                // }, $tipo_auditoria);
                // $whereTipoAuditoria = "AND auditoria_os.observacao IN(".implode(", ", $tipos_auditoria).")";

            }

            if (!empty($inspetor)) {
                $whereInspetor = "AND posto_fabrica.admin_sap = {$inspetor}";
            }
        }

        if ($login_fabrica == 178){
            if (!empty($tipo_auditoria)){
                foreach ($tipo_auditoria as $key => $value) {
                    $dados_like[] = "'%".$value."%'";
                }
                $whereTipoAuditoria = " AND auditoria_os.observacao ilike any (array[".implode(',', $dados_like)."])";
                $whereTipoAuditoriaOSR = " AND auditoria_os.observacao ilike any (array[".implode(',', $dados_like)."])";
            }
        }
        if($login_fabrica == 35 and strlen(trim($status_auditoria))>0){
            switch ($status_auditoria) {
                case 'pc':
                    $auditoria_status = 4;
                    $observacao_pesquisa = "OS em intervenção da fábrica por Peça Crí­tica";
                break;

                case 'ia':
                    $status_auditoria = 4;
                    $observacao_pesquisa = " Item de Aparência";
                break;

                case 'km':
                    $auditoria_status = 2;
                    $observacao_pesquisa = "";
                break;
                case 'avp':
                    $auditoria_status = 8;
                    $observacao_pesquisa = "";
                break;
                case 'af':
                    $auditoria_status = 6;
                    $observacao_pesquisa = "";
                break;

                case 'osr':
                    $auditoria_status = 1;
                    $observacao_pesquisa = "";
                break;

                case 'pe':
                    $auditoria_status = 4;
                    $observacao_pesquisa = "OS em auditoria de peças excedentes";
                break;
                case 'ap':
                    $auditoria_status = 3;
                    $observacao_pesquisa = " Produto Crítico";
                break;
                case 'pf':
                    $auditoria_status = 4;
                    $observacao_pesquisa = "Auditoria de peça fora de linha";                    
                break;
                case 'tc':
                    $auditoria_status = 3;
                    $observacao_pesquisa = "OS em auditoria de troca de produto";
                break;
            }
            if(strlen(trim($observacao_pesquisa))>0){
                $cond_obs = " and  auditoria_os.observacao ilike '%$observacao_pesquisa%' ";
            }
            $whereTipoAuditoria = " AND auditoria_os.auditoria_status = $auditoria_status  ".$cond_obs;
        }

        if ($login_fabrica == 158) {
            $colunaStatusOsMobile = "
                , (SELECT os_mobile.status_os_mobile FROM tbl_os_mobile AS os_mobile WHERE os_mobile.os = os.os ORDER BY os_mobile.data_input DESC LIMIT 1) AS status_os_mobile
            ";

            $colunaStatusOsMobileRevenda = "
                , '' AS status_os_mobile
            ";

            $colunaClienteAdmin = "
                , cliente_admin.codigo AS cliente_admin
            ";

            $colunaClienteAdminRevenda = "
                , '' AS cliente_admin
            ";

            $colunaContatoCidade = "
                , posto_fabrica.contato_cidade
            ";

            $colunaContatoCidadeRevenda = "
                , '' AS contato_cidade
            ";

            $colunaContatoEstado = "
                , posto_fabrica.contato_estado
            ";

            $colunaContatoEstadoRevenda = "
                , '' AS contato_estado
            ";

            $leftJoinHdChamado = "
                LEFT JOIN tbl_hd_chamado AS hd_chamado ON hd_chamado.hd_chamado = os.hd_chamado AND hd_chamado.fabrica = {$login_fabrica}
            ";

            $leftJoinClienteAdmin = "
                LEFT JOIN tbl_cliente_admin AS cliente_admin ON cliente_admin.cliente_admin = hd_chamado.cliente_admin AND cliente_admin.fabrica = {$login_fabrica}
            ";

            if (!empty($_POST["cliente_admin"])) {
                $cliente_admin     = $_POST["cliente_admin"];
                $whereClienteAdmin = " AND hd_chamado.cliente_admin IN (".implode(',', $_POST["cliente_admin"]).")";
            }
        }

        if (in_array($login_fabrica, array(167,203))) {
            $campoUltimoStatusOs = "
                , (SELECT tbl_status_os.descricao FROM tbl_os_status INNER JOIN tbl_status_os ON tbl_status_os.status_os = tbl_os_status.status_os WHERE tbl_os_status.os = os.os ORDER BY data DESC LIMIT 1) AS status_os
            ";
            $campoUltimoStatusOsRevenda = ", '' AS status_os";
        }

        switch ($status) {
             case 'recusada':
                $joinOs = "
                    INNER JOIN tbl_os AS os ON os.os = auditoria_os.os AND os.fabrica = {$login_fabrica}
                ";
                $whereStatus = "(auditoria_os.reprovada IS NOT NULL) AND auditoria_os.justificativa = 'Garantia recusada' ";
                break;
            case 'aprovada':
                $joinOs = "
                    INNER JOIN tbl_os AS os ON os.os = auditoria_os.os AND os.fabrica = {$login_fabrica}
					and os.excluida is not true			
                ";
                $whereStatus = "(auditoria_os.liberada IS NOT NULL)";
                $whereStatusRevenda = "(auditoria_os.liberada IS NOT NULL)";
                break;
            case 'reprovada':
                $joinOs = "
                    INNER JOIN tbl_os AS os ON os.os = auditoria_os.os AND os.fabrica = {$login_fabrica}
                ";
                $whereStatus = "(auditoria_os.reprovada IS NOT NULL)";
                $whereStatusRevenda = "(auditoria_os.reprovada IS NOT NULL)";
                break;

            case 'cancelada':
                $joinOsExcluida = "
                    LEFT JOIN tbl_os_excluida AS os_excluida ON os_excluida.os = auditoria_os.os AND os_excluida.fabrica = {$login_fabrica}
                ";
                $joinOs = "
                    INNER JOIN tbl_os AS os ON (os.os = os_excluida.os OR os.os = auditoria_os.os)
                ";
                $whereStatus = "(auditoria_os.cancelada IS NOT NULL)";
                $whereStatusRevenda = "(auditoria_os.cancelada IS NOT NULL)";
                break;

            case 'todas':
                $whereStatus = "";
                $whereData   = preg_replace("/^\sAND\s/", "", $whereData);
                $joinOs      = " INNER JOIN tbl_os AS os ON os.os = auditoria_os.os 
					and os.excluida is not true	";		
                break;

            default:
                $joinOs = "
                    INNER JOIN tbl_os AS os ON os.os = auditoria_os.os AND os.fabrica = {$login_fabrica}
					and os.excluida is not true			
                ";
                $whereStatus = "(auditoria_os.liberada IS NULL AND auditoria_os.cancelada IS NULL AND auditoria_os.reprovada IS NULL)";
                $whereStatusRevenda = "(auditoria_os.liberada IS NULL AND auditoria_os.cancelada IS NULL AND auditoria_os.reprovada IS NULL)";
                break;
        }

        if (in_array($login_fabrica, [123,160])) {
            switch ($tipo_aud) {
                case 'termo':
                    $whereAud = "AND upper(auditoria_os.observacao) = upper('Auditoria de Termo')";
                    break;
                default:
                    $whereAud = "";
                    break;
            }
        }

        if (in_array($login_fabrica, array(167,203))) {
            $status_auditoria = $_POST["status_auditoria"];

            if (!empty($status_auditoria)) {
                $selectStatusAuditoria = "SELECT x.* FROM (";
                $whereStatusAuditoria = " WHERE x.status_os = '{$status_auditoria}'";
            }
        }

	if (in_array($login_fabrica, array(104,187))) {
		$campo_serie = "os.serie,";
		$os_produto_campo = " NULL::INT AS os_produto, ";
	}else{
		$campo_serie = "os_produto.serie,";
	}

        if(!in_array($login_fabrica,array(104,123,187))){
            $os_produto_campo = "os_produto.os_produto, ";
        }

        $inner_join = " INNER JOIN tbl_os_produto AS os_produto ON os_produto.os = os.os
                        INNER JOIN tbl_produto AS produto ON produto.produto = os_produto.produto AND produto.fabrica_i = {$login_fabrica}
                        INNER JOIN tbl_os_extra AS os_extra ON os_extra.os = os.os";

        if (!isset($novaTelaOs) and $login_fabrica <> 104) {
            $inner_join = " LEFT JOIN tbl_os_produto AS os_produto ON os_produto.os = os.os
                            LEFT JOIN tbl_produto AS produto ON produto.produto = os_produto.produto AND produto.fabrica_i = {$login_fabrica}
                            LEFT JOIN tbl_os_extra AS os_extra ON os_extra.os = os.os";            
            $os_produto_campo = " NULL::INT AS os_produto, ";
        }

        $sql = "
            SELECT x.*
            FROM (
                SELECT DISTINCT
                    'OS' AS tipo_os,
                    os.os,
                    os.sua_os,
                    os.consumidor_cidade,
                    os.consumidor_estado,
                    os.posto,
                    TO_CHAR(os.data_abertura, 'DD/MM/YYYY') AS data_os,
                    os.data_abertura,
                    posto_fabrica.codigo_posto || ' - ' || posto.nome AS posto_descricao,
                    (produto.referencia || ' - ' || produto.descricao) AS produto,
                    produto.referencia_fabrica AS produto_referencia_fabrica,
                    produto.produto AS produto_id,
                    os_extra.os_reincidente,
                    $campo_serie
                    os.qtde_km,
                    os_campo_extra.valores_adicionais,
                    os_campo_extra.campos_adicionais,
                    (SELECT COUNT(*) FROM tbl_os_item AS os_item WHERE os_item.os_produto = os_produto.os_produto) AS qtde_pecas,
                    $os_produto_campo
                    os_extra.admin_paga_mao_de_obra AS paga_mao_de_obra_kof,
                    os.data_fechamento,
                    (SELECT tbl_os_troca.os_troca FROM tbl_os_troca WHERE tbl_os_troca.fabric = {$login_fabrica} AND tbl_os_troca.os = os.os ORDER BY tbl_os_troca.data DESC LIMIT 1) AS os_troca,
                    (SELECT tbl_os_troca.ressarcimento FROM tbl_os_troca WHERE tbl_os_troca.fabric = {$login_fabrica} AND tbl_os_troca.os = os.os ORDER BY tbl_os_troca.data DESC LIMIT 1) AS os_troca_ressarcimento
                    {$colunaClienteAdmin}
                    {$colunaContatoCidade}
                    {$colunaContatoEstado}
                    {$colunaStatusOsMobile}
                    {$campoUltimoStatusOs}
                FROM tbl_auditoria_os AS auditoria_os
                {$joinOsExcluida}
                {$joinOs}
                JOIN tbl_posto_fabrica AS posto_fabrica ON posto_fabrica.posto = os.posto AND posto_fabrica.fabrica = {$login_fabrica}
                JOIN tbl_posto AS posto ON posto.posto = posto_fabrica.posto
                {$inner_join}
                LEFT JOIN tbl_os_campo_extra AS os_campo_extra ON os_campo_extra.os = os.os AND os_campo_extra.fabrica = {$login_fabrica}
                {$leftJoinHdChamado}
                {$leftJoinClienteAdmin}
                WHERE {$whereStatus}
                {$whereData}
                {$whereOs}
                {$whereEstado}
                {$wherePosto}
                {$whereLinha}
                {$whereTipoAtendimento}
                {$whereUnidadeNegocio}
                {$whereTipoOrdem}
                {$whereTipoAuditoria}
                {$whereInspetor}
                {$whereClienteAdmin}
                {$whereAud}
                UNION
                SELECT DISTINCT
                    'OSR' AS tipo_os,
                    os.os_revenda AS os,
                    os.sua_os,
                    os.consumidor_cidade,
                    os.consumidor_estado,
                    os.posto,
                    TO_CHAR(os.data_abertura, 'DD/MM/YYYY') AS data_os,
                    os.data_abertura,
                    posto_fabrica.codigo_posto || ' - ' || posto.nome AS posto_descricao,
                    '' AS produto,
                    '' AS produto_referencia_fabrica,
                    NULL::INT AS produto_id,
                    NULL::INT AS os_reincidente,
                    '' AS serie,
                    os.qtde_km,
                    '' AS valores_adicionais,
                    '' AS campos_adicionais,
                    0 AS qtde_pecas, 
                    NULL::INT AS os_produto,
                    FALSE AS paga_mao_de_obra_kof,
                    NULL::DATE AS data_fechamento,
                    NULL::INT AS os_troca,
                    FALSE AS os_troca_ressarcimento
                    $colunaClienteAdminRevenda
                    $colunaContatoCidadeRevenda
                    $colunaContatoEstadoRevenda
                    $colunaStatusOsMobileRevenda
                    $campoUltimoStatusOsRevenda
                FROM tbl_auditoria_os_revenda AS auditoria_os
                {$joinOsExcluida}
                JOIN tbl_os_revenda os ON os.os_revenda = auditoria_os.os_revenda AND os.fabrica = {$login_fabrica}
                JOIN tbl_posto_fabrica AS posto_fabrica ON posto_fabrica.posto = os.posto AND posto_fabrica.fabrica = {$login_fabrica}
                JOIN tbl_posto AS posto ON posto.posto = posto_fabrica.posto
				$joinOsRevenda
                {$leftJoinHdChamado}
                {$leftJoinClienteAdmin}
                WHERE {$whereStatusRevenda} ";
                if($_POST['data_pesquisa'] == 'data_auditoria'){
                    $sql .= str_replace('auditoria_os', 'auditoria_os', $whereData);
                }else{
                    $sql .= "{$whereData}";
                }
                $sql .= "{$whereOsRevenda}
                {$whereTipoAuditoriaOSR}
                {$whereEstado}
                {$wherePosto}
                {$whereLinha}
                {$whereInspetor}
                {$whereTipoAtendimento}
                {$whereTipoOrdem}
                {$whereTipoAuditoria}
                {$whereInspetor}
                {$whereClienteAdmin}
            ) x
            $whereStatusAuditoria
            ORDER BY data_abertura ASC, posto ASC, consumidor_cidade ASC
        ";
        $resAuditoria = pg_query($con, $sql);
        
	if (!pg_num_rows($resAuditoria)) {
            $msg_erro["msg"][] = "Não foram encontradas Ordens de Serviço em auditoria";
        } elseif(empty($telecontrol_distrib)) {
            if ($_POST["gerar_excel"]) {
                $data = date("d-m-Y-H:i");

                $fileName = "auditoria_ordem_servico-{$data}.csv";

                $file = fopen("/tmp/{$fileName}", "w");
                $thead = "";
                if($login_fabrica == 158){
                    $thead .= "\"UNIDADE DE NEGÓCIO\""              . ";";
                }
                $thead .= "\"TIPO DE AUDITORIA\""               . ";";
                if($login_fabrica == 158){
                    $thead .= "\"CLIENTE ADMIN\""                   . ";";
                }
                $thead .= "\"NÚMERO DA O.S.\""                  . ";";
                $thead .= "\"DATA ABERTURA\""                   . ";";
                $thead .= "\"DATA DE FECHAMENTO DA OS\""         . ";";
                $thead .= "\"DATA DE ENTRADA DE AUDITORIA\""    . ";";
                $thead .= "\"DATA DA APROVAÇÃO OU REPROVAÇÃO\"" . ";";
                $thead .= "\"STATUS DA AUDITORIA\""             . ";";
                $thead .= "\"VALOR DA AUDITORIA\""              . ";";
                $thead .= "\"QTDE DE KM\""                      . ";";
                $thead .= "\"JUSTIFICATIVA\""                   . ";";
                $thead .= "\"Nº DA O.S. REINCIDENTE\""          . ";";
                $thead .= "\n";
                fwrite($file, $thead);

                $whereData            = "";
                $whereOS              = "";
                $whereEstado          = "";
                $wherePosto           = "";
                $whereTipoAtendimento = "";
                $whereUnidadeNegocio  = "";
                $whereStatus          = "";
                $joinOsExcluida       = "";
                $where                = " o.fabrica = $login_fabrica ";

                if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
                        $tipo_data = $_POST['data_pesquisa'];
                if(empty($tipo_data) || $tipo_data == 'data_abertura') {
                    $whereData = " AND o.data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}' ";
                }elseif($tipo_data == 'data_fechamento') {
                    $whereData = " AND o.data_fechamento BETWEEN '{$data_inicial}' AND '{$data_final}' ";
                }elseif($tipo_data == 'data_auditoria') {
                    $status_pesquisa = $_POST['status'];
                    switch ($status_pesquisa) {
                        case 'aprovada':
                            $data = 'liberada';
                            break;
                        case 'reprovada':
                            $data = 'reprovada';
                            break;  
                        case 'cancelada':
                            $data = 'cancelada';
                            break;  
                        default:
                            $data = 'data_input';
                            break;  
                    }
                    $data_final = date('Y-m-d', strtotime("+1 days",strtotime($data_final)));
                    $whereData = " AND ao.$data BETWEEN '{$data_inicial}' AND '{$data_final}' ";
                }
                }

                if ($_POST["os_aberta_fechada"] != "ambas") {
                    $os_aberta_fechada = $_POST["os_aberta_fechada"];

                    if ($os_aberta_fechada == "fechada") {
                        $whereData = " AND (o.data_fechamento BETWEEN '{$data_inicial}' AND '{$data_final}') ";
                    } elseif ($os_aberta_fechada == "aberta") {
                        $whereData = " AND o.data_fechamento IS NULL AND o.finalizada IS NULL ";
                    }
                }

                if (strlen($os) > 0) {
                    $whereOS = " AND (o.os = $os OR o.sua_os = '$os') ";
                }
                
                if (strlen($estado) > 0 || !empty($estado)) {
                    if (is_array($estado)) {
                        $estado = array_map(function($e) {
                            return "'".strtolower($e)."'";
                        }, $estado);

                        $whereEstado = " AND LOWER(pf.contato_estado) IN(".implode(", ", $estado).") ";
                    } else {
                        $whereEstado = " AND LOWER(pf.contato_estado) = LOWER('{$estado}') ";
                    }
                }

                if (strlen($posto_id) > 0) {
                    $wherePosto = " AND pf.posto = {$posto_id} ";
                }

                if (strlen($tipo_atendimento) > 0) {
                    $whereTipoAtendimento = " AND o.tipo_atendimento = {$tipo_atendimento} ";
                }

                if (!empty($unidade_negocio)) {
                    foreach ($unidade_negocio as $key => $value) {
                        $unidade_negocios[] = "'$value'";
                    }
                    $whereUnidadeNegocio = " AND JSON_FIELD('unidadeNegocio', oce.campos_adicionais) IN (".implode(',', $unidade_negocios).") ";
                }

                if (!empty($_POST["cliente_admin"])) {
                    $whereClienteAdmin = " AND hc.cliente_admin IN (".implode(',', $_POST["cliente_admin"]).")";
                }

                switch ($status) {
                    case 'aprovada':
                        $whereStatus = " AND ao.liberada IS NOT NULL 
								and o.excluida is not true			";
                        break;

                    case 'cancelada':
                        $whereStatus = " AND ao.cancelada IS NOT NULL ";
                        break;

                    case 'todas':
                        $whereStatus    = "";
                        $joinOsExcluida = "LEFT JOIN tbl_os_excluida AS osex ON osex.fabrica = $login_fabrica AND osex.os = o.os ";
                        $where          = " ((osex.os IS NOT NULL AND osex.fabrica = $login_fabrica) OR (osex.os IS NULL AND o.fabrica = $login_fabrica)) ";
                        break;

                    default:
                        $whereStatus = " AND (ao.liberada IS NULL AND ao.cancelada IS NULL AND ao.reprovada IS NULL) 
											and o.excluida is not true			";
                        break;
                }
            
                if($login_fabrica == 158){
                    $camposjoin = "INNER JOIN tbl_os_campo_extra oce ON oce.os = o.os";
                }else{
                    $camposjoin = "LEFT JOIN tbl_os_campo_extra oce ON oce.os = o.os";
                }
                $aux_sql = "
                    SELECT distinct 
                    (oce.campos_adicionais::json->'unidadeNegocio')::text AS unidadeNegocio,
                    a.descricao || ' - ' || ao.observacao AS tipo_auditoria,
                    ca.nome AS cliente_admin,
                    o.sua_os AS os,
                    TO_CHAR(o.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                    TO_CHAR(oe.termino_atendimento, 'DD/MM/YYYY') AS data_fim_atendimento,
                    TO_CHAR(ao.data_input, 'DD/MM/YYYY') AS data_entrada_auditoria,
                    CASE WHEN ao.cancelada IS NOT NULL THEN
                      'cancelada'
                    WHEN ao.reprovada IS NOT NULL THEN
                      'reprovada'
                    WHEN ao.liberada IS NOT NULL THEN
                      'liberada'
                    ELSE
                      'pendente'
                    END AS status_auditoria,
                    oce.valores_adicionais,
                    o.qtde_km,
                    ao.justificativa,
                    oe.os_reincidente AS numero_os_reincidente,
                    CASE WHEN ao.cancelada IS NOT NULL THEN
                      TO_CHAR(ao.cancelada, 'DD/MM/YYYY')
                    WHEN ao.reprovada IS NOT NULL THEN
                      TO_CHAR(ao.reprovada, 'DD/MM/YYYY')
                    WHEN ao.liberada IS NOT NULL THEN
                      TO_CHAR(ao.liberada, 'DD/MM/YYYY')
                    ELSE
                      NULL
                    END AS data_acao_auditoria
                    FROM tbl_os o
                    INNER JOIN tbl_os_extra oe ON oe.os = o.os
                    {$camposjoin}
                    INNER JOIN tbl_auditoria_os ao ON ao.os = o.os
                    INNER JOIN tbl_auditoria_status a ON a.auditoria_status = ao.auditoria_status
                    LEFT JOIN tbl_hd_chamado hc ON hc.fabrica = $login_fabrica AND hc.hd_chamado = o.hd_chamado
                    LEFT JOIN tbl_cliente_admin ca ON ca.fabrica = $login_fabrica AND ca.cliente_admin = hc.cliente_admin
                    INNER JOIN tbl_posto_fabrica pf ON pf.fabrica = $login_fabrica AND pf.posto = o.posto
                    {$joinOsExcluida}
                    WHERE
                    {$where}
                    {$whereData}
                    {$whereOS}
                    {$whereEstado}
                    {$wherePosto}
                    {$whereTipoAtendimento}
                    {$whereUnidadeNegocio}
                    {$whereStatus}
                    {$whereClienteAdmin}";
                $aux_res = pg_query($con, $aux_sql);
                $aux_row = pg_num_rows($aux_res);

                $quebra = array('"', "'", "<br>", "<br/>", "<br />", ";", "\n", "\nr", "[", "]", "{", "}");

                for ($wx=0; $wx < $aux_row; $wx++) {
                    $unidadenegocio         = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'unidadenegocio'));
                    $tipo_auditoria         = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'tipo_auditoria'));
                    $cliente_admin          = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'cliente_admin'));
                    $os                     = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'os'));
                    $data_abertura          = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'data_abertura'));
                    $data_fim_atendimento   = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'data_fim_atendimento'));
                    $data_entrada_auditoria = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'data_entrada_auditoria'));
                    $status_auditoria       = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'status_auditoria'));
                    $valores_adicionais     = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'valores_adicionais'));
                    $qtde_km                = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'qtde_km'));
                    $justificativa          = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'justificativa'));
                    $numero_os_reincidente  = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'numero_os_reincidente'));
                    $data_acao_auditoria    = str_replace($quebra, "", pg_fetch_result($aux_res, $wx, 'data_acao_auditoria'));

                    if($login_fabrica == 158){
                        $body  .= "\"$unidadenegocio\""         . ";";
                    }
                    $body .= "\"$tipo_auditoria\""         . ";";
                    if($login_fabrica == 158){
                        $body .= "\"$cliente_admin\""          . ";";
                    }
                    $body .= "\"$os\""                     . ";";
                    $body .= "\"$data_abertura\""          . ";";
                    $body .= "\"$data_fim_atendimento\""   . ";";
                    $body .= "\"$data_entrada_auditoria\"" . ";";
                    $body .= "\"$data_acao_auditoria\""    . ";";
                    $body .= "\"$status_auditoria\""       . ";";
                    $body .= "\"$valores_adicionais\""     . ";";
                    $body .= "\"$qtde_km\""                . ";";
                    $body .= "\"$justificativa\""          . ";";
                    $body .= "\"$numero_os_reincidente\""  . ";";
                    $body .= "\n";
                }
                fwrite($file, $body);

                fclose($file);
                if (file_exists("/tmp/{$fileName}")) {
                    system("mv /tmp/{$fileName} xls/{$fileName}");

                    echo "xls/{$fileName}";
                }
                exit;
            }
        } else if ($telecontrol_distrib == "t" && $_POST["gerar_excel"]) {

            $data = date("d-m-Y-H:i");

            $fileName = "auditoria_ordem_servico-{$data}.csv";

            $file = fopen("/tmp/{$fileName}", "w");
            
            $thead = "OS;Data Abertura;Data Digitação;Data Última Aprovação;Aprovador;Paga MO;\n";

            fwrite($file, $thead);

            $sql = "SELECT os.sua_os,
                           TO_CHAR(os.data_abertura, 'dd/mm/yyyy') as data_abertura,
                           TO_CHAR(os.data_digitacao, 'dd/mm/yyyy') as data_digitacao,
                           TO_CHAR(dados_ultima_auditoria.liberada, 'dd/mm/yyyy') as liberada,
                           dados_ultima_auditoria.nome_completo,
                           dados_ultima_auditoria.paga_mao_obra
                    FROM tbl_os as os
                    JOIN LATERAL (
                        SELECT tbl_auditoria_os.liberada,
                               tbl_auditoria_os.paga_mao_obra,
                               tbl_admin.nome_completo
                        FROM tbl_auditoria_os
                        JOIN tbl_admin ON tbl_auditoria_os.admin = tbl_admin.admin
                        AND tbl_admin.fabrica = {$login_fabrica}
                        WHERE tbl_auditoria_os.os = os.os
                        AND tbl_auditoria_os.liberada IS NOT NULL
                        ORDER BY tbl_auditoria_os.liberada DESC
                        LIMIT 1
                    ) AS dados_ultima_auditoria ON true
                    WHERE os.fabrica = {$login_fabrica}
                    {$whereData}";
            $res = pg_query($con, $sql);

            $tbody = "";
            while ($dados = pg_fetch_object($res)) {

                $pagaMo = ($dados->paga_mao_obra == "t") ? "Sim" : "Não";

                $tbody .= "{$dados->sua_os};{$dados->data_abertura};{$dados->data_digitacao};{$dados->liberada};{$dados->nome_completo};{$pagaMo}\n";

            }

            fwrite($file, $tbody);

            fclose($file);
            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
            exit;

        }
    }
}

$layout_menu = "auditoria";
$title       = "AUDITORIA DE ORDEM DE SERVIÇO";

include "cabecalho_new.php";

$plugins = array(
    "multiselect",
    "datepicker",
    "shadowbox",
    "maskedinput",
    "mask",
    "alphanumeric",
    "price_format",
    "select2",
    "tooltip"
);

include "plugin_loader.php";

if (count($msg_erro["msg"]) > 0) { ?>
    <br />

    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>

    <br />
<? } ?>

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios</b>
</div>

<form method="POST" class="form-search form-inline tc_formulario" >
    <div class="titulo_tabela" >Parâmetros de Pesquisa</div>

    <br />

    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span2">
            <div class="control-group <?=(in_array('data_inicial', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="data_inicial" >Data Inicial</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="data_inicial" class="span12" name="data_inicial" value="<?=getValue('data_inicial')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('data_final', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="data_final" >Data Final</label>
                <div class="controls controls-row" >
                    <div class="span12" >
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="data_final" class="span12" name="data_final" value="<?=getValue('data_final')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span2">
            <div class="control-group <?=(in_array('os', $msg_erro['campos'])) ? 'error' : '' ?>" >
                <label class="control-label" for="os">Ordem de Serviço</label>
                <div class="controls controls-row">
                    <div class="span10 input-append" >
                        <input type="text" id="os" class="span12" name="os" value="<?=getValue('os')?>" />
                        <span class="add-on" title="Para pesquisar por Ordem de Serviço não é necessário informar as datas" >
                            <i class="icon-info-sign" ></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="estado" >Estado</label>
                <div class="controls control-row">
                    <select id="estado" name="<?=(in_array($login_fabrica, array(169, 170))) ? 'estado[]' : 'estado'?>" class="span12" <?=(in_array($login_fabrica, array(169, 170))) ? "multiple" : ""?> >
                        <?php
                        if (!in_array($login_fabrica, array(169, 170))) {
                        ?>
                            <option value="" >Selecione</option>
                        <?php
                        }

                        foreach ($array_estados() as $sigla => $estado_nome) {
                            $selected = "";

                            if (is_array($_POST["estado"]) && in_array($sigla, $_POST["estado"])) {
                                $selected = "selected";
                            } else if (!is_array($_POST["estado"]) && $estado == $sigla) {
                                $selected = "selected";
                            }
                            ?>
                            <option value="<?= $sigla; ?>" <?= $selected; ?> ><?= $estado_nome; ?></option>
                        <? } ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <?php
    if (strlen(getValue("posto_id")) > 0) {
        $posto_input_readonly     = "readonly";
        $posto_span_rel           = "trocar_posto";
        $posto_input_append_icon  = "remove";
        $posto_input_append_title = "title='Trocar Posto'";
    } else {
        $posto_input_readonly     = "";
        $posto_span_rel           = "lupa";
        $posto_input_append_icon  = "search";
        $posto_input_append_title = "";
    }
    ?>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span3" >
            <div class="control-group" >
                <label class="control-label" for="posto_codigo" >Código do Posto</label>
                <div class="controls controls-row">
                    <div class="span10 input-append">
                        <input id="posto_codigo" name="posto_codigo" class="span12" type="text" value="<?=getValue('posto_codigo')?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                        <input type="hidden" id="posto_id" name="posto_id" value="<?=getValue('posto_id')?>" />
                    </div>
                </div>
            </div>
        </div>
        <div class="span4" >
            <div class="control-group" >
                <label class="control-label" for="posto_nome" >Nome do Posto</label>
                <div class="controls controls-row" >
                    <div class="span10 input-append" >
                        <input id="posto_nome" name="posto_nome" class="span12" type="text" value="<?=getValue('posto_nome')?>" <?=$posto_input_readonly?> />
                        <span class="add-on" rel="<?=$posto_span_rel?>" >
                            <i class="icon-<?=$posto_input_append_icon?>" <?=$posto_input_append_title?> ></i>
                        </span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <?php if (in_array($login_fabrica, [167, 203])) { ?>
                <div class="span3" >
                    <div class="control-group" >
                        <label class="control-label" for="busca_interacao" >Última Interação</label>
                        <div class="controls control-row">
                            <select id="busca_interacao" name="busca_interacao" class="span12">
                                <option value="" >Selecione</option>
                                <option value="admin" <?=($busca_interacao == 'admin') ? "selected" : ""?>>Admin</option>
                                <option value="posto" <?=($busca_interacao == 'posto') ? "selected" : ""?>>Posto</option>
                            </select>
                        </div>
                    </div>
                </div>
        <?php } ?>
    <?php if($login_fabrica == 35){ ?>
            <div class='span3'>
                <div class='control-group <?=(in_array('status_auditoria', $msg_erro['campos'])) ? "error" : "" ?>' >
                    <label class='control-label' for='status_auditoria'>Tipo de Auditoria</label>
                    <div class='controls controls-row'>
                        <select name="status_auditoria" id="status_auditoria" class="span12">
                            <option value=''>Selecione</option>
                            <option value='km' <?php if($status_auditoria == 'km') echo " selected "; ?>>Auditoria de KM</option>
                            <option value='osr' <?php if($status_auditoria == 'osr') echo " selected "; ?>>Auditoria de OS Reincidente</option>
                            <option value='pc' <?php if($status_auditoria == 'pc') echo " selected "; ?>>Auditoria de Peca Crítica</option>
                            <option value='pe' <?php if($status_auditoria == 'pe') echo " selected "; ?>>Auditoria de Peça Excedentes</option>
                            <option value='ia' <?php if($status_auditoria == 'ia') echo " selected "; ?>>Auditoria de Peca Item de Aparência</option>
                            <option value='ap' <?php if($status_auditoria == 'ap') echo " selected "; ?>>Auditoria de Produto</option>
                            <option value='avp' <?php if($status_auditoria == 'avp') echo " selected "; ?>>Auditoria Valor Pecas</option>
                            <option value='pf' <?php if($status_auditoria == 'pf') echo " selected "; ?>>Auditoria de Peça fora de linha</option>
                            <option value='tc' <?php if($status_auditoria == 'tc') echo " selected "; ?>>Auditoria de Troca de Produto</option>
                        </select>
                    </div>
                </div>
            </div>
    <?php } ?>
        <div class="span1" ></div>
    </div>
    <?php
    if (in_array($login_fabrica, array(167,203))) {
    ?>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span4" >
                <div class="control-group" >
                    <label class="control-label" for="status_auditoria" >Status da Auditoria</label>
                    <div class="controls controls-row" >
                        <select class="span12 form-control" id="status_auditoria" name="status_auditoria" >
                            <option value="" >Selecione</option>
                            <option value="Em analise" >Em análise</option>
                            <option value="Aguardando Diagnostico" <?=($_POST["status_auditoria"] == "Aguardando Diagnostico") ? "selected" : ""?> >Aguardando Diagnóstico</option>
                            <option value="Aguardando Pecas" <?=($_POST["status_auditoria"] == "Aguardando Pecas") ? "selected" : ""?> >Aguardando Peças</option>
                            <option value="Pendência de documento" <?=($_POST["status_auditoria"] == "Pendência de documento") ? "selected" : ""?> >Pendência de documento</option>
                            <option value="Troca pendente" <?=($_POST["status_auditoria"] == "Troca pendente") ? "selected" : ""?> >Troca pendente</option>
                            <option value="Ressarcimento pendente" <?=($_POST["status_auditoria"] == "Ressarcimento pendente") ? "selected" : ""?> >Ressarcimento pendente</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    if (in_array($login_fabrica, array(158,169,170))) { ?>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <? $spanAtend = (in_array($login_fabrica, array(169,170))) ? "span3" : "span4"; ?>
            <div class="<?= $spanAtend; ?>" >
                <div class="control-group" >
                    <label class="control-label" for="tipo_atendimento" >Tipo de Atendimento</label>
                    <div class="controls control-row">
                        <select id="tipo_atendimento" name="tipo_atendimento" class="span12" >
                            <option value="" >Selecione</option>
                            <?php
                            $sql = "SELECT tipo_atendimento, descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
                            $resTipoAtendimento = pg_query($con, $sql);
                            $countTipoAtendimento = pg_num_rows($resTipoAtendimento);

                            if ($countTipoAtendimento > 0) {
                                for ($ta = 0; $ta < $countTipoAtendimento; $ta++) {
                                    $xtipo_atendimento = pg_fetch_result($resTipoAtendimento, $ta, tipo_atendimento);
                                    $xdesc_atendimento = pg_fetch_result($resTipoAtendimento, $ta, descricao);
                                    $selected = ($xtipo_atendimento == $tipo_atendimento) ? "selected" : ""; ?>
                                    <option value="<?= $xtipo_atendimento; ?>" <?= $selected; ?> ><?= $xdesc_atendimento; ?></option>
                                <? }
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
            <? if ($login_fabrica == 158) { ?>
                <script>
                    $(document).ready(function(){
                        $("#unidade_negocio").change( function(){
                            var cliente_admin = $('#cliente_admin').val();

                            if (cliente_admin != null && cliente_admin != "") {
                                $('#cliente_admin').val('').select2();
                            }
                        });

                        $("#cliente_admin").change( function(){
                            var unidade_negocio = $('#unidade_negocio').val();

                            if (unidade_negocio != null && unidade_negocio != "") {
                                $('#unidade_negocio').val('').select2();
                            }
                        });
                    });
                </script>
                <div class="span3" >
                    <div class="control-group" >
                        <label class="control-label" for="unidade_negocio" >Unidade de Negócio</label>
                        <div class="controls control-row">
                            <select id="unidade_negocio" multiple="multiple" name="unidade_negocio[]" class="span12" >
                                <?php
                                $sql = "
                                    SELECT DISTINCT
                                        ds.unidade_negocio,
                                        ds.unidade_negocio||' - '||c.nome AS descricao_unidade
                                    FROM tbl_distribuidor_sla ds
                                    --JOIN tbl_cidade c USING(cidade)
                                    JOIN tbl_unidade_negocio c ON c.codigo = ds.unidade_negocio
                                    WHERE ds.fabrica = {$login_fabrica};";
                                $resUnidadeNegocio = pg_query($con, $sql);
                                $countUnidadeNegocio = pg_num_rows($resUnidadeNegocio);

                                if ($countUnidadeNegocio > 0) {
                                    for ($un = 0; $un < $countUnidadeNegocio; $un++) {
                                        $xunidade_negocio = pg_fetch_result($resUnidadeNegocio, $un, unidade_negocio);
                                        $xdesc_unidade = pg_fetch_result($resUnidadeNegocio, $un, descricao_unidade);
                                        $selected = (in_array($xunidade_negocio, $unidade_negocio)) ? "selected" : ""; ?>
                                        <option value="<?= $xunidade_negocio; ?>" <?= $selected; ?> ><?= $xdesc_unidade; ?></option>
                                    <? }
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- HD - 6115865 -->
                <div class="span3" >
                    <div class="control-group" >
                        <label class="control-label" for="cliente_admin" >Cliente Admin</label>
                        <div class="controls control-row">
                            <select id="cliente_admin" multiple="multiple" name="cliente_admin[]" class="span12" >
                                <?php
                                $sql = "SELECT cliente_admin, nome FROM tbl_cliente_admin WHERE fabrica = $login_fabrica ORDER BY nome ASC";
                                $resClienteAdmin   = pg_query($con, $sql);
                                $countClienteAdmin = pg_num_rows($resClienteAdmin);

                                if ($countClienteAdmin > 0) {
                                    for ($wx = 0; $wx < $countClienteAdmin; $wx++) {
                                        $id            = pg_fetch_result($resClienteAdmin, $wx, 'cliente_admin');
                                        $nome          = strtoupper(pg_fetch_result($resClienteAdmin, $wx, 'nome'));
                                        $selected      = (in_array($id, $cliente_admin)) ? "selected" : ""; ?>
                                        <option value="<?= $id; ?>" <?= $selected; ?> ><?= $nome; ?></option>
                                    <? }
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
            <? }
            if (in_array($login_fabrica, array(169,170))) { ?>
                <div class="span3" >
                    <div class="control-group" >
                        <label class="control-label" for="consumidor_revenda" >Tipo de OS</label>
                        <div class="controls control-row">
                            <? if ($consumidor_revenda == "R") {
                                $selected_revenda = "SELECTED";
                            } else if ($consumidor_revenda == "C") {
                                $selected_consumidor = "SELECTED";
                            } ?>
                            <select id="consumidor_revenda" name="consumidor_revenda" class="span12" >
                                <option value="" >Selecione</option>
                                <option value="C" <?= $selected_consumidor; ?> >Consumidor</option>
                                <option value="R" <?= $selected_revenda; ?> >Revenda</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="span4" >
                    <div class="control-group" >
                        <label class="control-label" for="linha" >Linha</label>
                        <div class="controls controls-row" >
                            <select id="linha" name="linha[]" class="span12" multiple="multiple">
                                <? $sqlLinha = "SELECT * FROM tbl_linha WHERE fabrica = {$login_fabrica};";
                                $resLinha = pg_query($con,$sqlLinha);
                                for ($l = 0; $l < pg_num_rows($resLinha); $l++) {
                                    $xLinha = pg_fetch_result($resLinha, $l, linha);
                                    $xDesc = pg_fetch_result($resLinha, $l, nome);
                                    $selLinha = (in_array($xLinha, $linha)) ? "SELECTED" : ""; ?>
                                    <option value="<?= $xLinha; ?>" <?= $selLinha; ?>><?= $xDesc; ?></option>
                                <? } ?>
                            </select>
                        </div>
                    </div>
                </div>
            <? } ?>
            <div class="span1" ></div>
        </div>
        <?php
        if (in_array($login_fabrica, array(169, 170))) {
        ?>
            <div class="row-fluid" >
                <div class="span1" ></div>
                <div class="span6">
                    <div class="control-group" >
                        <label class="control-label" for="linha" >Tipo de Auditoria</label>
                        <div class="controls controls-row" >
                            <select id="linha" name="tipo_auditoria[]" class="span12" multiple="multiple">
                                <?php
                                $sqlTipoAuditoria = "
										SELECT *
										FROM (
										SELECT DISTINCT ao.observacao
										FROM tbl_auditoria_os ao
										JOIN tbl_os o ON o.os = ao.os AND o.fabrica = {$login_fabrica} AND o.excluida IS NOT TRUE
										WHERE ao.observacao NOT LIKE '%manualmente%'
										AND finalizada isnull
										AND ao.data_input > current_timestamp - interval '6 months'
										UNION
										SELECT DISTINCT ao.observacao
										FROM tbl_auditoria_os ao
										JOIN tbl_os o ON o.os = ao.os AND o.fabrica = {$login_fabrica} AND o.excluida IS NOT TRUE
										WHERE ao.observacao LIKE '%manualmente%'
										AND ao.liberada IS NULL
										AND ao.reprovada IS NULL
										AND ao.cancelada IS NULL
										AND finalizada isnull
                                        UNION
                                        SELECT DISTINCT ao.observacao
                                        FROM tbl_auditoria_os_revenda ao
                                        JOIN tbl_os_revenda o ON o.os_revenda = ao.os_revenda AND o.fabrica = {$login_fabrica} AND o.excluida IS NOT TRUE
                                        WHERE ao.observacao NOT LIKE '%manualmente%'
                                        AND finalizada isnull
										) x
										ORDER BY observacao ASC;
                                ";
                                $resTipoAuditoria = pg_query($con,$sqlTipoAuditoria);

                                for ($l = 0; $l < pg_num_rows($resTipoAuditoria); $l++) {
                                    $xTipoAuditoria = pg_fetch_result($resTipoAuditoria, $l, 'observacao');
                                    $xDesc = pg_fetch_result($resTipoAuditoria, $l, 'observacao');
                                    $selTipoAuditoria = (in_array($xTipoAuditoria, $tipo_auditoria)) ? "SELECTED" : "";

                                    $find = 'OS em auditoria de KM';
                                    $pos = strpos($xDesc, $find);

                                    if ($pos !== false){
                                        if ($continue == "continue"){
                                            continue;
                                        }
                                        $xDesc = "OS em auditoria de KM";
                                        $continue = "continue";
                                    }

                                    ?>
                                    <option value="<?= $xTipoAuditoria; ?>" <?= $selTipoAuditoria; ?>><?= $xDesc; ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="span4" >
                    <div class="control-group" >
                        <label class="control-label" >Inspetor</label></label>
                        <div class="controls controls-row" >
                            <div class="span12" >
                                <select id="inspetor" name="inspetor" >
                                    <option value="" >Selecione</option>
                                    <?php
                                    $sqlInspetor = "
                                        SELECT admin, nome_completo, login
                                        FROM tbl_admin
                                        WHERE fabrica = {$login_fabrica}
                                        AND ativo IS TRUE
                                        AND admin_sap IS TRUE
                                        ORDER BY login
                                    ";
                                    $resInspetor = pg_query($con, $sqlInspetor);

                                    while ($row = pg_fetch_object($resInspetor)) {
                                        $descricao = (!empty($row->nome_completo)) ? $row->nome_completo : $row->login;
                                        $selected = ($row->admin == $inspetor) ? "selected" : "";
                                        echo "<option value='{$row->admin}' {$selected} >{$descricao}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        }
    }

    if (in_array($login_fabrica, [139,175])) { ?>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span6">
                <div class="control-group" >
                    <label class="control-label" for="linha" >Tipo de Auditoria</label>
                    <div class="controls controls-row" >
                        <select id="linha" name="tipo_auditoria[]" class="span12" multiple="multiple">
                            <?php

                            $wh = " ao.liberada IS NULL AND ao.reprovada IS NULL AND ao.cancelada IS NULL ";
                            $lm = "";
                            
                            if ($login_fabrica == 139) {
                                $wh = "1=1";
                                $lm = " LIMIT 5";
                            }

                            $sqlTipoAuditoria = "
                                SELECT DISTINCT ao.observacao
                                FROM tbl_auditoria_os ao
                                INNER JOIN tbl_os o ON o.os = ao.os AND o.fabrica = {$login_fabrica} AND o.excluida IS NOT TRUE
                                WHERE (
                                    $wh     
                                )
                                ORDER BY ao.observacao ASC
                                $lm
                            ";
                            $resTipoAuditoria = pg_query($con,$sqlTipoAuditoria);

                            for ($l = 0; $l < pg_num_rows($resTipoAuditoria); $l++) {

                                $xTipoAuditoria = pg_fetch_result($resTipoAuditoria, $l, 'observacao');
                                $xDesc = pg_fetch_result($resTipoAuditoria, $l, 'observacao');
                                $selTipoAuditoria = (in_array($xTipoAuditoria, $tipo_auditoria)) ? "SELECTED" : "";

                                ?>
                                <option value="<?= $xTipoAuditoria; ?>" <?= $selTipoAuditoria; ?>><?= $xDesc; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }


    if ($login_fabrica == 178){
    ?>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span6">
                <div class="control-group" >
                    <label class="control-label" for="linha" >Tipo de Auditoria</label>
                    <div class="controls controls-row" >
                        <select id="linha" name="tipo_auditoria[]" class="span12" multiple="multiple">
                            <?php
                            $sqlTipoAuditoria = "
                                SELECT DISTINCT ao.observacao
                                FROM tbl_auditoria_os ao
                                JOIN tbl_os o ON o.os = ao.os AND o.fabrica = {$login_fabrica} AND o.excluida IS NOT TRUE
                                WHERE (
                                    ao.liberada IS NULL
                                    AND ao.reprovada IS NULL
                                    AND ao.cancelada IS NULL
                                )
                                UNION
                                SELECT DISTINCT aor.observacao
                                FROM tbl_auditoria_os_revenda aor
                                JOIN tbl_os_campo_extra oce ON oce.os_revenda = aor.os_revenda AND oce.fabrica = $login_fabrica
                                WHERE (
                                    aor.liberada IS NULL
                                    AND aor.reprovada IS NULL
                                    AND aor.cancelada IS NULL
                                )
                                ORDER BY observacao ASC
                            ";
                            $resTipoAuditoria = pg_query($con,$sqlTipoAuditoria);

                            for ($l = 0; $l < pg_num_rows($resTipoAuditoria); $l++) {
                                $xTipoAuditoria = pg_fetch_result($resTipoAuditoria, $l, 'observacao');
                                $xDesc = pg_fetch_result($resTipoAuditoria, $l, 'observacao');
                                $selTipoAuditoria = (in_array($xTipoAuditoria, $tipo_auditoria)) ? "SELECTED" : "";
                            ?>
                                <option value="<?= $xTipoAuditoria; ?>" <?= $selTipoAuditoria; ?>><?= $xDesc; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    <?php    
    }

    if ($login_fabrica == 158) { /*HD - 6115865*/ ?>
        <div class="row-fluid" >
            <div class="span1" ></div>
            <div class="span10" >
                <div class="control-group" >
                    <div class="controls controls-row">
                        <div class="span12">
                            <label class="radio" >
                                <input type="radio" name="os_aberta_fechada" value="ambas" <?php echo ($_POST["os_aberta_fechada"] == "ambas") ? "checked" : ""; ?> />Ambas
                            </label>
                            <label class="radio" >
                                <input type="radio" name="os_aberta_fechada" value="aberta" <?php echo ($_POST["os_aberta_fechada"] == "aberta") ? "checked" : ""; ?> />Somente OS's Abertas
                            </label>
                            <label class="radio" >
                                <input type="radio" name="os_aberta_fechada" value="fechada" <?php echo ($_POST["os_aberta_fechada"] == "fechada") ? "checked" : ""; ?> />Somente OS's Fechadas
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span10" >
            <div class="control-group" >
                <label class="control-label" >Tipos de Data</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <label class="radio" >
                            <input type="radio" class="status-auditoria-pesquisa" name="data_pesquisa" value="data_abertura" <?=($_POST["data_pesquisa"] == "data_abertura") ? "checked" : ""?> />Data de Abertura
                        </label>
                        <label class="radio" >
                            <input type="radio" class="status-auditoria-pesquisa" name="data_pesquisa" value="data_fechamento" <?=($_POST["data_pesquisa"] == "data_fechamento") ? "checked" : ""?> />Data de Fechamento
                        </label>
                        <label class="radio" >
                            <input type="radio" class="status-auditoria-pesquisa" name="data_pesquisa" value="data_auditoria" <?=($_POST["data_pesquisa"] == "data_auditoria") ? "checked" : ""?> />Data de Auditoria
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row-fluid" >
        <div class="span1" ></div>
        <div class="span10" >
            <div class="control-group" >
                <label class="control-label" >Status da OS</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <span class="label label-info" >
                            <label class="radio" >
                                    <input type="radio" class="status-auditoria-pesquisa" name="status" value="pendente" checked />Auditoria Pendente
                            </label>
                        </span>

                        <span class="label label-success" >
                            <label class="radio" >
                                    <input type="radio" class="status-auditoria-pesquisa" name="status" value="aprovada" <?=(getValue("status") == "aprovada") ? "checked" : ""?> />Auditoria Aprovada
                            </label>
                        </span>

                        <?php if($login_fabrica == 35){ ?>
                            <span class="label label-important" >
                                <label class="radio" >
                                        <input type="radio" class="status-auditoria-pesquisa" name="status" value="reprovada" <?=(getValue("status") == "reprovada") ? "checked" : ""?> />Auditoria Reprovada
                                </label>
                            </span>
                        <?php  }else{ ?>

                        <span class="label label-important" >
                            <label class="radio" >
                                    <input type="radio" class="status-auditoria-pesquisa" name="status" value="cancelada" <?=(getValue("status") == "cancelada") ? "checked" : ""?> />Auditoria Cancelada
                            </label>
                        </span>

                       <?php if (in_array($login_fabrica, [104, 157])) { ?>
                            <span class="label label-default">
                                <label class="radio" >
                                        <input type="radio" class="status-auditoria-pesquisa" name="status" value="reprovada" <?=(getValue("status") == "reprovada") ? "checked" : ""?> />Auditoria Reprovada
                                </label>
                            </span>
                        <?php  }?>

                        <?php }
                        if ($login_fabrica == 158) { /*HD - 6115865*/ ?>
                            <span class="label label-warning" >
                                <label class="radio" >
                                        <input type="radio" class="status-auditoria-pesquisa" name="status" value="todas" <?=(getValue("status") == "todas") ? "checked" : ""?> />Todas as Auditorias 
                                </label>
                            </span>
                        <?php } 

                        /**
                         * @author William Castro (william.castro@telecontrol.com.br)
                         * hd-6010107 : Campo Auditoria Recusada
                         *
                         */

                        if ($login_fabrica == 177) { ?>
                            <span class="label label-warning" >
                                <label class="radio" >
                                    <input type="radio" class="status-auditoria-pesquisa" name="status" value="recusada" <?=(getValue("status") == "recusada") ? "checked" : ""?> />Auditoria Recusada 
                                </label>
                            </span>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br />
    <?php if (in_array($login_fabrica, [123,160])) { ?>
            <div class="row-fluid" >
                <div class="span1" ></div>
                <div class="span10" >
                    <div class="control-group" >
                        <label class="control-label" >Tipo de Auditoria</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <span class="label label-warning" >
                                    <label class="radio" >
                                            <input type="checkbox" class="status-auditoria-pesquisa" name="tipo_aud" value="termo" <?=(getValue("tipo_aud") == "termo") ? "checked" : ""?> />Termo
                                    </label>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br />
    <?php }
    ?>
    <?php
        if ($telecontrol_distrib == "t") { ?>
        <label>
            <input type="checkbox" name="gerar_csv_detalhado" value="t" <?= $_POST['gerar_csv_detalhado'] == "t" ? "checked" : "" ?> /> Gerar CSV Auditorias Aprovadas
        </label><br /><br />
    <?php
    }
    ?>
    <p>
        <button class="btn" type="submit" name="acao" value="pesquisar" >Pesquisar</button>
        <button class="btn btn-primary listar-todos" type="submit" name="acao" value="listar_todas" title="Somente os filtros de estado e posto autorizado irão funcionar em conjunto com está ação" >Listar Todas</button>
    </p>

    <br />
</form>

<?php

if ($telecontrol_distrib == "t" && isset($_POST['gerar_csv_detalhado']) && $_POST['acao'] == "pesquisar") {
        $jsonPOST = excelPostToJson($_POST); ?>
        <center>
            <button id='gerar_excel' class="btn btn-success">Download arquivo CSV</button>
            <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
        </center>
        <br>
<?php
}

if (pg_num_rows($resAuditoria) > 0) {
    if($login_fabrica == 35){
        $style_cadence = "style='width:1200px; margin: 0 auto;'";
    }

    $jsonPOST = excelPostToJson($_POST);?>

    <center>
        <button id='gerar_excel' class="btn btn-success">Download arquivo CSV</button>
        <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
    </center>
    <br>

    <div class="alert alert-info" >
        <strong>Clique no número da Ordem de Serviço para visualizar os detalhes da auditoria</strong>
    </div>
</div>

<?php if (in_array($login_fabrica, [123,160])) { ?>
        <div class="container">
            <table>
                <tbody>
                    <tr>
                        <td width="18">
                            <div style="background-color: #FFFF00">&nbsp;</div>
                        </td>
                        <td align="left">
                            <b>OS Reprovada de Auditoria de Termo Até 4 Vezes</b>
                        </td>
                    </tr>
                    <tr>
                        <td width="18">
                            <div style="background-color: #D90000">&nbsp;</div>
                        </td>
                        <td align="left">
                            <b>OS Reprovada de Auditoria de Termo Mais de 4 Vezes</b>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <br />
<?php } ?>

    <?php if (in_array($login_fabrica, [104,157,167,203])) { ?>
        <div class="container">
            <table>
                <tbody>
                    <tr>
                        <td width="18">
                            <div style="background-color: #75DC75">&nbsp;</div>
                        </td>
                        <td align="left">
                            <b>Última Interação do Admin</b>
                        </td>
                    </tr>
                    <tr>
                        <td width="18">
                            <div style="background-color: #FF4040">&nbsp;</div>
                        </td>
                        <td align="left">
                            <b>Última Interação do Posto</b>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <br />
    <?php } ?>

    <div style="font-size: 15px">
        <center>
            <?= "Total de OS ". pg_num_rows($resAuditoria) ?>
        </center>
        <br>
        <br>
    </div>

    <div class="accordion" id="oss_auditoria" <?=$style_cadence ?> >
        <?php
        while ($os = pg_fetch_object($resAuditoria)) {
            $campos_adicionais = $os->campos_adicionais;
            $campos_adicionais = json_decode($campos_adicionais, true);

            if($login_fabrica == 35 and strlen(trim($status_auditoria))>0){
                $condicao = $whereTipoAuditoria;
            }

            if (in_array($login_fabrica, array(104,157,163,167,203))) {
                
                $busca_interacao = $_POST['busca_interacao'];
                
                $sql_in = "SELECT
                            tbl_os_interacao.os_interacao AS id,
                            (CASE WHEN tbl_os_interacao.admin IS NULL THEN
                                'Posto Autorizado'
                            ELSE
                                tbl_admin.nome_completo
                            END) AS admin
                            -- ,TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY HH24:MI') AS data,
                            -- TO_CHAR(tbl_os_interacao.data_contato, 'DD/MM/YYYY') AS data_contato,
                            -- tbl_os_interacao.comentario AS mensagem,
                            -- tbl_os_interacao.interno,
                            -- tbl_os_interacao.posto,
                            -- tbl_os_interacao.sms,
                            -- tbl_os_interacao.exigir_resposta,
                            -- tbl_os_interacao.atendido,
                            -- TO_CHAR(tbl_os_interacao.confirmacao_leitura, 'DD/MM/YYYY HH24:MI') AS confirmacao_leitura
                        FROM tbl_os_interacao
                            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin AND tbl_admin.fabrica = $login_fabrica
                        WHERE tbl_os_interacao.fabrica = $login_fabrica
                            AND tbl_os_interacao.os = {$os->os}
                            AND tbl_os_interacao.interno IS NOT TRUE
                        ORDER BY tbl_os_interacao.data desc
                        LIMIT 1;";
                $res_in = pg_query($con,$sql_in);
                if (pg_num_rows($res_in) > 0 && in_array($login_fabrica, [167, 203])) {
                    $interacao_admin = pg_fetch_result($res_in, 0, 'admin');
                    if ($busca_interacao == "admin") {
                        if ($interacao_admin == "Posto Autorizado") {
                            continue;
                        }
                    } else if ($busca_interacao == "posto") {
                        if ($interacao_admin != "Posto Autorizado") {
                            continue;
                        }
                    }
                }
            }

            if ($os->tipo_os == "OSR"){
                $sqlOsRevendaAuditorias = "
                    SELECT
                        auditoria_os.auditoria_os,
                        auditoria_status.descricao,
                        auditoria_os.observacao,
                        TO_CHAR(auditoria_os.data_input, 'DD/MM/YYYY') AS data,
                        auditoria_os.data_input,
                        auditoria_os.bloqueio_pedido,
                        auditoria_os.paga_mao_obra,
                        TO_CHAR(auditoria_os.liberada, 'DD/MM/YYYY') AS liberada,
                        TO_CHAR(auditoria_os.cancelada, 'DD/MM/YYYY') AS cancelada,
                        TO_CHAR(auditoria_os.reprovada, 'DD/MM/YYYY') AS reprovada,
                        auditoria_os.justificativa,
                        admin.login,
                        auditoria_status.reincidente,
                        auditoria_status.km,
                        auditoria_status.peca,
                        auditoria_status.numero_serie,
                        auditoria_status.produto,
                        auditoria_status.fabricante
                    FROM tbl_auditoria_os_revenda AS auditoria_os
                    JOIN tbl_auditoria_status AS auditoria_status ON auditoria_status.auditoria_status = auditoria_os.auditoria_status
                    LEFT JOIN tbl_admin AS admin ON admin.admin = auditoria_os.admin AND admin.fabrica = {$login_fabrica}
                    WHERE auditoria_os.os_revenda = {$os->os}
                    ORDER BY auditoria_os.data_input ASC ";
                $resOsRevendaAuditorias = pg_query($con, $sqlOsRevendaAuditorias);
                $auditorias = pg_fetch_all($resOsRevendaAuditorias);
            }else{
                $sqlOsAuditorias = "
                    SELECT
                        auditoria_os.auditoria_os,
                        auditoria_status.descricao,
                        auditoria_os.observacao,
                        TO_CHAR(auditoria_os.data_input, 'DD/MM/YYYY') AS data,
                        auditoria_os.data_input,
                        auditoria_os.bloqueio_pedido,
                        auditoria_os.paga_mao_obra,
                        TO_CHAR(auditoria_os.liberada, 'DD/MM/YYYY') AS liberada,
                        TO_CHAR(auditoria_os.cancelada, 'DD/MM/YYYY') AS cancelada,
                        TO_CHAR(auditoria_os.reprovada, 'DD/MM/YYYY') AS reprovada,
                        auditoria_os.justificativa,
                        admin.login,
                        auditoria_status.reincidente,
                        auditoria_status.km,
                        auditoria_status.peca,
                        auditoria_status.numero_serie,
                        auditoria_status.produto,
                        auditoria_os.auditoria_status,
                        auditoria_status.fabricante
                    FROM tbl_auditoria_os AS auditoria_os
                    INNER JOIN tbl_auditoria_status AS auditoria_status ON auditoria_status.auditoria_status = auditoria_os.auditoria_status
                    LEFT JOIN tbl_admin AS admin ON admin.admin = auditoria_os.admin AND admin.fabrica = {$login_fabrica}
                    WHERE auditoria_os.os = {$os->os}
                    $condicao
                    ORDER BY auditoria_os.data_input ASC
                ";
                $resOsAuditorias = pg_query($con, $sqlOsAuditorias);
                $auditorias = pg_fetch_all($resOsAuditorias);
            }
            
            $os_auditoria_reincidente        = procura_auditoria_tipo($auditorias, "reincidente");
            $os_auditoria_pecas              = procura_auditoria_tipo($auditorias, "peca");
            $os_auditoria_km                 = procura_auditoria_tipo($auditorias, "km");
            $os_auditoria_produto            = procura_auditoria_tipo($auditorias, "produto");
            $os_auditoria_serie              = procura_auditoria_tipo($auditorias, "numero_serie");
    	    $os_auditoria_valores_adicionais = procura_auditoria_tipo($auditorias, "fabricante", "valores adicionais");
    	    $os_auditoria_valor_pecas	     = procura_auditoria_tipo($auditorias, "produto");

            $os_auditoria_analise_garantia = false;
            if(in_array($login_fabrica, [177])){
                $os_auditoria_analise_garantia   = procura_auditoria_tipo($auditorias, "fabricante", "AUDITORIA DE ANÁLISE DA GARANTIA");    
            }

            if ($login_fabrica == 190){
                $os_auditoria_fechamento = procura_auditoria_tipo($auditorias, "fabricante", "auditoria de fechamento"); 
            }

            $produto_referencia_fabrica = "";

            if (in_array($login_fabrica, array(171)) && $os_auditoria_km !== true) {
                $sql = "SELECT
                            tbl_os.os
                        FROM tbl_os
                            JOIN tbl_tipo_atendimento USING(tipo_atendimento)
                        WHERE tbl_tipo_atendimento.km_google IS TRUE AND
                            tbl_tipo_atendimento.fora_garantia IS NOT TRUE AND
                            tbl_os.os = {$os->os};";
                $res = pg_query($con, $sql);
                if (pg_num_rows($res) !== 0)
                    $os_auditoria_km = true;
            }

            $auditorias_pendentes = array();

            $span = 5;
            if ($login_fabrica == 171) {
                $span = 4;
            }
            array_map(function($r) {
                global $auditorias_pendentes, $login_fabrica;

                if (empty($r["liberada"]) && empty($r["cancelada"]) && empty($r["reprovada"])) {
                    if($login_fabrica == 35){
                        $observacao_auditoria = (strlen(trim($r['observacao']))>0) ? " - ".$r['observacao'] : "";
                        $auditorias_pendentes[] = $r["descricao"]. $observacao_auditoria;
                    }else{
                        $auditorias_pendentes[] = $r["descricao"];
                    }

                }
            }, $auditorias);

            $auditorias_pendentes = array_unique($auditorias_pendentes);
            $cor_linha = "";

            if (in_array($login_fabrica, [123,160])) {
                $sql_aud_termo = "  SELECT COUNT(auditoria_os) AS qtde_auditoria
                                    FROM tbl_auditoria_os
                                    WHERE os = $os->os 
                                    AND liberada IS NULL 
                                    AND cancelada IS NULL 
                                    AND reprovada NOTNULL
                                    AND upper(observacao) = 'AUDITORIA DE TERMO'";
                $res_aud_termo = pg_query($con, $sql_aud_termo);
                if (pg_num_rows($res_aud_termo) > 0) {
                    $qtde_auditoria = pg_fetch_result($res_aud_termo, 0, 'qtde_auditoria');
                    if ($qtde_auditoria > 0 && $qtde_auditoria <= 4) {
                        $cor_linha = 'style="background-color: #FFFF00"';
                    } else if ($qtde_auditoria > 4) {
                        $cor_linha = 'style="background-color: #D90000"';
                    }
                }
            }
            ?>
            <div id="<?=$os->os?>" class="accordion-group" >
                <div class="accordion-heading os_auditoria_titulo" <?=$cor_linha?> >
                    <a class="accordion-toggle" data-toggle="collapse" data-parent="#oss_auditoria" href="#os_auditoria_<?=$os->os?>" >
                        <span class="icon-resize-full" ></span>
                        OS <?= empty($os->sua_os) ? $os->os : $os->sua_os ?>
                    </a>
                    <?php
                    if ($status == "pendente" || ($login_fabrica == 158 && $status == "todas" && !empty($auditorias_pendentes))) {
                        $title_cancelar = "Esta ação irá cancelar a Ordem de Serviço, a mesma será excluída do sistema";

                        if ($login_fabrica == 158) {
                            unset($disabled_cancelar);

                            if ($os->status_os_mobile == "PS5") {
                                $disabled_cancelar       = "disabled";
                                $title_cancelar = "Esta OS não pode ser cancelada pois já esta finalizada no dispositivo móvel";
                            }
                        }

                        if($login_fabrica != 158){
                            if (!empty($os->os_troca) && $os->os_troca_ressarcimento == "t") {
                                $disabled_troca_produto = "disabled";
                                $title_troca_produto = "Esta OS não pode ter uma troca/ressarcimento realizado, pois já possui um ressarcimento lançado";
                            } else {
                                $disabled_troca_produto = "";
                                $title_troca_produto = "Troca de Produto/Ressarcimento";
                            }

                            if (in_array($login_fabrica, array(169, 170))) {
                                $disabled_troca_produto = "style='display: none;'";
                            }
                            $dataTrocaAnt = "";
                            if (in_array($login_fabrica, array(195))) {

                                $dataTrocaAnt = " data-trocaantecipada='false'";
                                if (isset($campos_adicionais["solicita_troca_antecipada"]) && $campos_adicionais["solicita_troca_antecipada"] == "troca_produto_antecipado"){
                                    $dataTrocaAnt = " data-trocaantecipada='true'";
                                }
                            }

                            ?>
                            <?php if ($os->tipo_os != "OSR" AND !in_array($login_fabrica, array(157))){ ?>
                            <button type="button" class="btn btn-mini btn-warning pull-right trocar-produto" <?=$dataTrocaAnt?> data-os="<?=$os->os?>" title="<?=$title_troca_produto?>" data-fabrica="<?php echo $login_fabrica;?>" <?=$disabled_troca_produto?> >
                                <i class="icon-refresh icon-white"></i> Trocar Produto
                            </button>
                            <?php } ?>
                        <?php
                        }if(!in_array($login_fabrica, [35])) {
                            if ($os->tipo_os != "OSR" OR $login_fabrica == 178){

                                if (in_array($login_fabrica, [104,131])) {

                                    $title_cancelar = "Esta ação irá cancelar a Ordem de Serviço";

                                ?>
                                    <button type="button" class="btn btn-mini btn-inverse pull-right reprova-os-laudo" data-os="<?=$os->os?>" title="Esta ação irá reprovar a OS em auditoria possibilitando anexo do laudo e opção de zerar mão de obra" <?=$disabled_cancelar?> >
                                        <i class="icon-remove-circle icon-white"></i> Reprovar OS 
                                    </button>
                                <?php
                                }
                                if (!in_array($login_fabrica, [131])) {
                                ?>
                                    <button type="button" class="btn btn-mini btn-inverse pull-right cancelar-os" data-tipo_os="<?=$os->tipo_os?>" data-os="<?=$os->os?>" title="<?=$title_cancelar?>" <?=$disabled_cancelar?> >
                                        <i class="icon-remove-circle icon-white"></i> Cancelar OS
                                    </button>
							<?php 
								}
                            }
                        ?>
                            <?php if ($login_fabrica == 157) { 
                                $botao_aprovar = "Aprovar";
                            ?>

                                <button type="button" class="btn btn-mini btn-danger pull-right reprova-os" data-os="<?=$os->os?>" title="Esta ação irá reprovar a Ordem de Serviço,">
                                    <i class="icon-remove-circle icon-white"></i> Reprovar
                                </button>

                            <?php } else {
                                $botao_aprovar = "Aprovar Todas";
                                }
                            ?>  

                            <button type="button" class="btn btn-mini btn-success pull-right aprova-os" data-tipo_os="<?=$os->tipo_os?>" data-os="<?=$os->os?>" data-cliente-admin="<?=$os->cliente_admin?>" title="Esta ação irá aprovar a Ordem de Serviço, irá possibilitar a geração de pedido e o pagamento de mão de obra">
                                <i class="icon-ok-circle icon-white"></i> <?=$botao_aprovar ?>
                            </button>                      

                        <?php }
                        /* if ((in_array($login_fabrica, [123,160]) && trim(observacao_auditoria($os->os)) == 'Auditoria de Termo') || ($login_fabrica == 131)) { ?>
                                <button type="button" class="btn btn-mini btn-danger pull-right reprova-os-laudo" data-os="<?=$os->os?>" title="<?=$title_cancelar?>" <?=$disabled_cancelar?> >
                                    <i class="icon-remove-circle icon-white"></i> Reprovar OS
                                </button>
                        <?php
                        }*/
                        if (!in_array($login_fabrica, array(157, 175)) AND $os->tipo_os != "OSR"){ ?>
                        <button type="button" class="btn btn-mini btn-info pull-right interagir-os" data-os="<?=$os->os?>" >
                            <i class="icon-comment icon-white"></i> Interagir na OS
                        </button>

                        <?php } ?>
                    <?php
                    }

                    if (in_array($login_fabrica, [169,170]) && $os->tipo_os == "OSR") { ?>
                        <button type="button" data-tipo_os="<?=$os->tipo_os?>" class="btn btn-mini btn-danger pull-right reprova-os" data-os="<?=$os->os?>" title="Esta ação irá reprovar a Ordem de Serviço,">
                            <i class="icon-remove-circle icon-white"></i> Reprovar OS
                        </button>
                    <?php
                    }
                    ?>
                   <?php if (in_array($login_fabrica, array(195))) {?>
                    <button type="button" class="btn btn-mini pull-right alterar-os" data-os="<?=$os->os?>" >
                        <i class="icon-edit"></i> Lançar Itens na OS
                    </button>                    


                   <?php }?>
                    <button type="button" class="btn btn-mini btn-primary pull-right visualizar-os" data-tipo_os="<?=$os->tipo_os?>" data-os="<?=$os->os?>" >
                        <i class="icon-search icon-white"></i> Visualizar OS
                    </button>                    

                    <?php
                    if (in_array($login_fabrica, array(104,157,163,167,203))) {
                        if (pg_num_rows($res_in) > 0 ) {
                            $interacao_admin = pg_fetch_result($res_in, 0, admin);

                            if ($interacao_admin == 'Posto Autorizado') { ?>

                                <span class="cor-interacao-posto label label-warning pull-right" rel="<?= $os->os ?>" style="height: 19px; width: 240px; line-height: 20px;">Interação do Posto Autorizado</span>
                            
                            <?php } else { ?>

                                <span class="cor-interacao-admin label  label-info pull-right"   rel="<?= $os->os ?>" style="height: 19px; width: 240px; line-height: 20px;">Interação do Admin: <?=$interacao_admin?></span>
                            <?php
                            }
                        }
                    }

                    
                   /**
                    * @author William Castro <william.castro@telecontrol.com.br>
                    * hd-
                    *
                    */

                    if ($telecontrol_distrib == "t" || $interno_telecontrol == "t") {
                       
                        $queryTransferencia = "
                            SELECT
                                tbl_os_interacao.os_interacao AS id,
                                tbl_admin.nome_completo AS admin,
                                tbl_os_interacao.comentario AS mensagem,
                                tbl_os_interacao.posto,
                                TO_CHAR(tbl_os_interacao.data, 'DD/MM/YYYY HH24:MI') AS data,
                                TO_CHAR(tbl_os_interacao.data_contato, 'DD/MM/YYYY') AS data_contato
                            FROM 
                                tbl_os_interacao
                            LEFT JOIN 
                                tbl_admin ON tbl_admin.admin = tbl_os_interacao.admin AND tbl_admin.fabrica = {$login_fabrica}
                            WHERE 
                                tbl_os_interacao.fabrica = {$login_fabrica}
                            AND 
                                tbl_os_interacao.os = {$os->os} and comentario ~'Transferido' order by 1 desc limit 1";

                        $resTransferencia = pg_query($con, $queryTransferencia);

                        $interacoes = pg_fetch_all($resTransferencia);

                        $transferencias = [];
                     
                        foreach ($interacoes as $interacao) {

                            $frase = explode("admin", $interacao["mensagem"]);
                            
                            if ($frase[0] == "Transferido para o ") { 
                               
                                $transferencias[] = [ 'msg'  => $interacao["mensagem"],
                                                      'data' => $interacao["data"]
                                                    ];

                            }
                          
                        }
    
                        $msgTransferencia = $transferencias[0];

                        foreach ($transferencias as $transferencia) {
                          
                            if (strtotime($msgTransferencia['data']) < strtotime($transferencia['data'])) {
                        
                                $msgTransferencia = $transferencia;
                            } 

                        } 
                            $msg = explode("admin", $msgTransferencia['msg']);
                            $msg = $msg[1];
                           
                        ?>

                        <span class="label label-info pull-right" style="height: 19px; width: auto; line-height: 20px;">
                            Transferido para: <?= $msg ?>
                        </span> 

                    <?php } ?>
                    
                    <br />

                    <span style="padding-left: auto; color: #000000;" >

                    <?php
                        if ($login_fabrica == 157) {                             
                            echo substr($os->posto_descricao, strpos($os->posto_descricao, "-") + strlen("-")) . ", " . substr($os->produto, strpos($os->produto, "-") + strlen("-"));
                            //echo $os->posto_descricao  . ", " . $os->produto;
                        } else { 
                            echo $os->data_os . ", " . 
                                 $os->consumidor_cidade . " " . 
                                 $os->consumidor_estado . ", " . 
                                 $os->posto_descricao  . ", " . 
                                 $os->produto;
                        }
                    ?>
                        <?php if ($login_fabrica == 158) { /*HD - 6115865*/
                            echo "(Localização do Posto: " . $os->contato_cidade . " - " . $os->contato_estado . ")";
                        } ?>
                    </span>                    
                    <br />

                    <?php
                    if (in_array($login_fabrica, array(167,177,203))) {
                    ?>
                        <br />

                        <span>
                            <label style="padding-left: 15px;" >
                                <?php if ($login_fabrica == 177){ ?>
                                    <strong>Defeito Constatado:</strong>&nbsp;
                                    <?php 
                                        $sqlDefeitos = "
                                            SELECT DISTINCT
                                                tbl_defeito_constatado.defeito_constatado,
                                                tbl_defeito_constatado.descricao,
                                                tbl_defeito_constatado.lancar_peca,
                                                tbl_defeito_constatado.codigo,
                                                tbl_defeito_constatado.lista_garantia,
                                                tbl_defeito_constatado.defeito_constatado_grupo
                                            FROM tbl_diagnostico
                                            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
                                            JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                                            JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                                            WHERE tbl_diagnostico.fabrica = $login_fabrica
                                            AND tbl_produto.produto = {$os->produto_id}
                                            AND tbl_diagnostico.ativo IS TRUE
                                            ORDER BY tbl_defeito_constatado.descricao ASC ";
                                        $resDefeitos = pg_query($con, $sqlDefeitos);

                                        if (pg_num_rows($resDefeitos) > 0){
                                            $defeitos = pg_fetch_all($resDefeitos);
                                        }
                                    ?>
                                        <select class="form-control" id="defeito_constatado_<?=$os->os?>" data-os="<?=$os->os?>" data-status="<?=$os->status_os?>" >
                                            <option value="" >Selecione</option>
                                            <?php 
                                                foreach ($defeitos as $key => $value) { 
                                                    $selected_defeito = ($os->defeito_constatado == $value['defeito_constatado']) ? "selected" : "";
                                                    
                                            ?>
                                                <option <?=$selected_defeito?> value="<?=$value['defeito_constatado']?>"><?=$value['descricao']?></option>
                                            <?php } ?>
                                        </select>
                                <?php } else { ?>
                                    <strong>Status:</strong>&nbsp;
                                    <select class="form-control status_auditoria" data-os="<?=$os->os?>" data-status="<?=$os->status_os?>" >
                                        <option value="" >Selecione</option>
                                        <option value="Em analise" <?=($os->status_os == "Em analise") ? "selected" : ""?> >Em análise</option>
                                        <option value="Aguardando Diagnostico" <?=($os->status_os == "Aguardando Diagnostico") ? "selected" : ""?> >Aguardando Diagnóstico</option>
                                        <option value="Aguardando Pecas" <?=($os->status_os == "Aguardando Pecas") ? "selected" : ""?> >Aguardando Peças</option>
                                        <option value="Pendência de documento" <?=($os->status_os == "Pendência de documento") ? "selected" : ""?> >Pendência de documento</option>
                                        <option value="Troca pendente" <?=($os->status_os == "Troca pendente") ? "selected" : ""?> >Troca pendente</option>
                                        <option value="Ressarcimento pendente" <?=($os->status_os == "Ressarcimento pendente") ? "selected" : ""?> >Ressarcimento pendente</option>
                                    </select>
                                <?php } ?>
                            </label>
                        </span>

                        <br />
                    <?php
                    }
                    ?>

                    <span style="padding-left: 15px;" >
                        <?php
                        foreach ($auditorias_pendentes as $auditoria_pendente) {
                            echo "<span class='label label-info' style='width:auto' >{$auditoria_pendente}</span>";
                        }
                        ?>
                    </span>
                </div>
                <div id="os_auditoria_<?=$os->os?>" data-fechamento="<?=$os->data_fechamento?>" data-aprovado-sem-mo-kof="<?=$os->paga_mao_de_obra_kof?>" class="accordion-body collapse" >
                    <div class="accordion-inner">
                        <div class="row-fluid" >
                            <div class="span2" >
                                <div class="control-group" >
                                    <label class="control-label" >Data Abertura</label>
                                    <div class="controls controls-row" >
                                        <?=$os->data_os?>
                                    </div>
                                </div>
                            </div>
                            <div class="span<?php echo $span;?>" >
                                <div class="control-group" >
                                    <label class="control-label" >Posto</label>
                                    <div class="controls controls-row" >
                                        <?=$os->posto_descricao?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($login_fabrica == 171) {?>
                            <div class="span2" >
                                <div class="control-group" >
                                    <label class="control-label" >Referência Fábrica</label>
                                    <div class="controls controls-row" >
                                        <?=$os->produto_referencia_fabrica;?>
                                    </div>
                                </div>
                            </div>
                            <?php }?>
                            <?php if ($os->tipo_os != "OSR"){ ?>
                            <div class="span<?php echo $span;?>" >
                                <div class="control-group" >
                                    <label class="control-label" >Produto</label>
                                    <div class="controls controls-row" >
                                        <?=$os->produto?>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>

                        <?php
                        if (in_array($login_fabrica, [35])) {

                            $sqlJustificativaPosto = "SELECT campos_adicionais
                                                      FROM tbl_os_campo_extra
                                                      WHERE os = {$os->os}";
                            $resJustificativaPosto = pg_query($con, $sqlJustificativaPosto);

                            $adicionais_justificativa = json_decode(pg_fetch_result($resJustificativaPosto, 0, 'campos_adicionais'), true);

                            if (pg_num_rows($resJustificativaPosto) > 0 && !empty($adicionais_justificativa['resposta_reincidencia'])) { ?>
                                <div class="row-fluid">
                                    <div class="span8">
                                        <div class="control-group" >
                                            <strong>Justificativa do Posto:</strong> &nbsp;&nbsp;<?= utf8_decode($adicionais_justificativa['resposta_reincidencia']) ?>
                                            <div class="controls controls-row">
                                                <textarea rows="3" disabled class="span10"><?= utf8_decode($adicionais_justificativa['justificativa_reincidencia']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php
                            }

                        }
                        ?>

                        <table class="table table-bordered table-striped" >
                            <tbody>
                                <tr class="warning" >
                                <?php if($login_fabrica == 35){
                                    $colspan = 11;
                                }else{
                                    $colspan = 7;
                                }
                                ?>

                                    <td class="table_auditoria_titulo" colspan="<?=$colspan?>" >Auditorias</td>
                                </tr>
                                <?php
                                if ($status == "pendente") {
                                    if ($os_auditoria_reincidente == true) {
                                    ?>
                                        <tr>
                                            <th class="titulo_coluna" >OS Reincidente</th>
                                            <td colspan="6" >
                                                <?=$os->os_reincidente?>
                                                <?php if($login_fabrica == 35){?>
                                                <button type="button" class="btn btn-small btn-primary alterar-os" data-os="<?=$os->os?>" ><i class="icon-edit icon-white" ></i> Alterar OS</button>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php
                                    }

                                    if ($os_auditoria_serie == true) {
                                    ?>
                                        <tr>
                                            <th class="titulo_coluna" >Número de Série</th>
                                            <td colspan="6" >
                                                <?=$os->serie?>
                                            </td>
                                        </tr>
                                    <?php
                                    }

                                    if($os_auditoria_produto == true){ ?>
                                        <tr>
                                            <th class="titulo_coluna" >Produto</th>
                                            <td colspan="<?=$colspan?>" >
                                                <?=$os->produto ?>
                                                <?php if($login_fabrica == 35){?>
                                                <button type="button" class="btn btn-small btn-primary alterar-os" data-os="<?=$os->os?>" ><i class="icon-edit icon-white" ></i> Alterar OS</button>
                                                <?php } ?>
                                            </td>
                                        </tr>

                                    <?php
                                    }
                                    if ($os_auditoria_km == true) {
                                    ?>
                                        <tr>
                                            <th class="titulo_coluna" >KM</th>
                                            <td colspan="6" >
                                                <span class="os_km" ><?=number_format($os->qtde_km, 2, ".", "")?> km</span>

                                                <button type="button" class="btn btn-small btn-primary alterar-km" data-tipo_os="<?=$os->tipo_os?>" data-os="<?=$os->os?>" data-km="<?=$os->qtde_km?>" ><i class="icon-edit icon-white" ></i> Alterar KM</button>
                                                <?php if($login_fabrica == 35){?>
                                                <button type="button" class="btn btn-small btn-primary alterar-os" data-os="<?=$os->os?>" ><i class="icon-edit icon-white" ></i> Alterar OS</button>

                                                <button type="button" class="btn btn-small btn-primary endereco" data-os="<?=$os->os?>" ><i class="icon-map-marker icon-white" ></i> Endereço</button>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php
                                    }

                                    if ($os_auditoria_fechamento == true){
                                    ?>
                                        <tr>
                                            <th class="titulo_coluna" >Qtde Horas</th>
                                            <td colspan="6" >
                                                <span class="os_km" ><?=$campos_adicionais['horas_trabalhadas']?> Qtde Horas</span>
                                                <button type="button" class="btn btn-small btn-primary alterar-horas" data-tipo_os="<?=$os->tipo_os?>" data-os="<?=$os->os?>" data-horas="<?=$campos_adicionais['horas_trabalhadas']?>" ><i class="icon-edit icon-white" ></i> Alterar Qtde Horas</button>
                                            </td>
                                        </tr>
                                    <?php
                                    }

                                    if ($os_auditoria_pecas == true || $os_auditoria_valor_pecas) {
                                    ?>
                                        <tr>
                                            <th class="titulo_coluna" >Peças</th>
                                            <td colspan="<?=$colspan?>" >
                                                <strong><?=$os->qtde_pecas?> Peça(s)</strong> 
                                                <?if($login_fabrica == 35){?>
                                                <span>
                                                    <a href='#' data-os="<?=$os->os?>" class='auditorlog_pecas' name="btnAuditorLog">Visualizar Log Auditor</a>
                                                </span>
                                                <?php } ?> 

                                                <button type="button" class="btn btn-small btn-primary ver-pecas" data-os="<?=$os->os?>" ><i class="icon-search icon-white" ></i> Ver Peça(s)</button>

                                                <div class="modal hide fade modal-pecas" >
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                                        <h4>OS <?=$os->os?></h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php
														if(!empty($os->os_produto)) {
                                                        $sqlPecas = "
                                                            SELECT
                                                                peca.referencia_fabrica peca_referencia_fabrica,
                                                                peca.referencia || ' - ' || peca.descricao AS peca,
                                                                os_item.qtde,
                                                                os_item.os_item,
                                                                os_item.preco,
                                                                peca.peca_critica AS critica,
                                                                peca.devolucao_obrigatoria,
                                                                os_item.pedido,
                                                                servico.descricao AS servico
                                                            FROM tbl_os_item AS os_item
                                                            INNER JOIN tbl_peca AS peca ON peca.peca = os_item.peca AND peca.fabrica = {$login_fabrica}
                                                            INNER JOIN tbl_servico_realizado AS servico ON servico.servico_realizado = os_item.servico_realizado AND servico.fabrica = {$login_fabrica}
                                                            WHERE os_item.os_produto = {$os->os_produto}                                                         
                                                        ";
                                                        $resPecas = pg_query($con, $sqlPecas);

														$pecas = pg_fetch_all($resPecas);
														}
                                                        ?>
                                                        <table class="table table-bordered table-striped" >
                                                            <thead>
                                                                <tr class="titulo_coluna" >
                                                                    <th>Peça</th>
                                                                    <th>Qtde</th>
                                                                    <th>Crítica</th>
                                                                    <th>Devolução Obrigatória</th>
                                                                    <th>Serviço</th>
                                                                    <th>Pedido</th>
                                                                    <?php if($login_fabrica == 35){?>
                                                                        <th>Preço</th>
                                                                        <th>Excluir</th>
                                                                    <?php } ?>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                foreach ($pecas as $peca) {
                                                                ?>
                                                                    <tr class="<?="os_item_".$peca['os_item']?>" >
                                                                        <td><?=$peca["peca"]?> <?php echo ($login_fabrica == 171 && !empty($peca["peca_referencia_fabrica"])) ? " / ".$peca["peca_referencia_fabrica"] : "";?></td>
                                                                        <td class="tac" ><?=$peca["qtde"]?></td>
                                                                        <td class="tac" >
                                                                            <?php
                                                                            if ($peca["critica"] == "t") {
                                                                            ?>
                                                                                <span class="label label-success" ><i class="icon-ok icon-white"></i></span>
                                                                            <?php
                                                                            } else {
                                                                            ?>
                                                                                <span class="label label-important" ><i class="icon-remove icon-white"></i></span>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </td>
                                                                        <td class="tac" >
                                                                            <?php
                                                                            if ($peca["devolucao_obrigatoria"] == "t") {
                                                                            ?>
                                                                                <span class="label label-success" ><i class="icon-ok icon-white"></i></span>
                                                                            <?php
                                                                            } else {
                                                                            ?>
                                                                                <span class="label label-important" ><i class="icon-remove icon-white"></i></span>
                                                                            <?php
                                                                            }
                                                                            ?>
                                                                        </td>
                                                                        <td><?=$peca["servico"]?></td>
                                                                        <td><?=$peca["pedido"]?></td>
                                                                        <?php if($login_fabrica == 35){
                                                                         ?>            
                                                                        <td class="tac">R$ <?=number_format($peca["preco"], 2, ',', ' ')?></td>
                                                                        <td class='tac'> 
                                                                            <span class="label label-important" ><i class="icon-remove icon-white excluir_peca" data-os-item= "<?=$peca['os_item']?>" data-os="<?=$os->os?>"></i></span>
                                                                        </td>
                                                                        <?php } ?>
                                                                    </tr>
                                                                <?php
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <?php if($login_fabrica == 35){?>
                                                <button type="button" class="btn btn-small btn-primary alterar-os" data-os="<?=$os->os?>" ><i class="icon-edit icon-white" ></i> Alterar OS</button>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php
                                    }

                                    if ($os_auditoria_valores_adicionais) {
                                        $valores_adicionais = json_decode($os->valores_adicionais, true);
                                        ?>
                                        <th class="titulo_coluna" nowrap >Valores Adicionais</th>
                                        <td colspan="6" >
                                            <ul class="os_valores_adicionais" >
                                                <?php
                                                foreach ($valores_adicionais as $valor) {
                                                ?>
                                                    <li data-descricao="<?=key($valor)?>" data-valor="<?=$valor[key($valor)]?>" ><?=key($valor)?>: R$ <?=number_format($valor[key($valor)], 2, ",", ".")?></li>
                                                <?php
                                                }
                                                ?>
                                            </ul>

                                            <br />

                                            <button type="button" class="btn btn-small btn-primary alterar-valores-adicionais" data-os="<?=$os->os?>" ><i class="icon-edit icon-white" ></i> Alterar Valores Adicionais</button>
                                        </td>
                                    <?php
                                    }
                                }
                                ?>
                                <tr class="titulo_coluna">
                                    <th>Auditoria</th>
                                    <th>Observação</th>
                                    <th>Data</th>
                                    <th>Bloqueia Pedido</th>
                                    <th>Status</th>
                                    <th>Admin</th>
                                    <th>Justificativa</th>
                                    <?php 

                                    if (in_array($login_fabrica, [35])) {

                                         foreach ($auditorias as $audObs) {

                                            $arrayObservacoes[] = $audObs["observacao"];

                                        }

                                        if (!in_array('Valor de Peças', $arrayObservacoes)) {

                                            $os_auditoria_valor_pecas = "f";

                                        }

                                    }

                                    if($login_fabrica == 35 and $os_auditoria_valor_pecas == 't'){?>
                                        <th>Qtde Peças</th>
                                        <th>Valor Peças + M.O Posto</th>
                                        <th>Valor Produto + M.O Troca</th>
                                        <th>Peças X Produto</th>
                                    <?php } ?>
                                </tr>
                                <?php
                                foreach ($auditorias as $auditoria) {
                                    if($login_fabrica == 35 and $os_auditoria_valor_pecas == 't' and $auditoria['observacao'] == 'Valor de Peças'){
                                        $sql_dados_pecas = "SELECT sum(tbl_os_item.preco) as valor_total_pecas, sum(tbl_os_item.qtde) as qtde_total_pecas, tbl_os.os, tbl_produto.referencia, case when troca_garantia_mao_obra > 0 then troca_garantia_mao_obra else tbl_produto.mao_de_obra_troca end as mao_de_obra_troca, tbl_produto.mao_de_obra as mao_de_obra_posto , tbl_os_extra.custo_produto_troca_faturada as preco
                                            FROM tbl_os_item
                                            join tbl_os_produto using(os_produto)
                                            join tbl_os using(os)
                                            join tbl_os_extra using(os)
                                            join tbl_servico_realizado using(servico_realizado)
                                            join tbl_produto ON tbl_produto.produto = tbl_os_produto.produto and tbl_produto.fabrica_i = $login_fabrica
                                            where tbl_os_produto.os = ".$os->os." 
                                            and tbl_servico_realizado.troca_de_peca is true
                                            AND tbl_servico_realizado.ativo is true
                                            And tbl_os.fabrica = $login_fabrica 
                                            AND tbl_os_item.digitacao_item <= '".$auditoria['data_input']."'
                                            group by 3,4,5,6,7";
                                        $res_dados_pecas = pg_query($con, $sql_dados_pecas);

                                        if(pg_num_rows($res_dados_pecas)>0){
                                            $valor_total_pecas  = pg_fetch_result($res_dados_pecas, 0, valor_total_pecas);
                                            $qtde_total_pecas = pg_fetch_result($res_dados_pecas, 0, qtde_total_pecas);
                                            $mao_de_obra_troca = pg_fetch_result($res_dados_pecas, 0, mao_de_obra_troca);
                                            $mao_de_obra_posto = pg_fetch_result($res_dados_pecas, 0, mao_de_obra_posto);
                                            $produto_referencia = pg_fetch_result($res_dados_pecas, 0, referencia);
                                            $preco               = pg_fetch_result($res_dados_pecas, 0, preco);
                                        }

										if(empty($preco)) {
											$sql_prd = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_tabela_item.preco from tbl_peca 
												inner join tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
												inner join tbl_tabela using(tabela)
												where referencia = '$produto_referencia'
												and tbl_peca.fabrica = $login_fabrica
												and tbl_tabela.ativa = 't' and tbl_tabela.tabela_garantia = 't' ";
											$res_prd = pg_query($con, $sql_prd);
											if(pg_num_rows($res_prd)>0){
												$preco = pg_fetch_result($res_prd, 0, preco);
											}
										}
                                    }


                                ?>
                                    <tr>
                                        <td><?=$auditoria["descricao"]?></td>
                                        <td><?=$auditoria["observacao"] ?></td>
                                        <td class="tac" ><?=$auditoria["data"]?></td>
                                        <td class="tac" >
                                            <?php
                                            if ($auditoria["bloqueio_pedido"] == "t") {
                                            ?>
                                                <span class="label label-success" ><i class="icon-ok icon-white"></i></span>
                                            <?php
                                            } else {
                                            ?>
                                                <span class="label label-important" ><i class="icon-remove icon-white"></i></span>
                                            <?php
                                            }
                                            ?>
                                        </td>
                                        <td class="tac status-auditoria-os" >
                                            <?php
                                            if (!empty($auditoria["liberada"]) && $auditoria["paga_mao_obra"] == "f") {
                                            ?>
                                                <span class="label label-warning" >Aprovada sem Mão de Obra <?=$auditoria["liberada"]?></span>
                                            <?php
                                            } else if (!empty($auditoria["liberada"]) && $auditoria["paga_mao_obra"] == "t") {
                                            ?>
                                                <span class="label label-success" >Aprovada <?=$auditoria["liberada"]?></span>
                                            <?php
                                            } else if (!empty($auditoria["cancelada"])) {
                                            ?>
                                                <span class="label label-important" >Cancelada <?=$auditoria["cancelada"]?></span>
                                            <?php
                                            } else if(!empty($auditoria["reprovada"])){
                                            ?>
                                                <span class="label label-important" >Reprovada <?=$auditoria["reprovada"]?></span>
                                            <?php
                                            } else {
                                            ?>
                                                <span class="label label-info" >Aguardando Auditoria</span>
                                                <br />

                                        <?php  if ((in_array($login_fabrica, [123,160]) && trim(observacao_auditoria($os->os)) == 'Auditoria de Termo')) { ?>
                                                    <button type="button" class="btn btn-mini btn-success aprova-os" data-os="<?=$os->os?>" data-tipo_os="<?=$os->tipo_os?>" data-cliente-admin="<?=$os->cliente_admin?>" data-aprovacao-unica="<?=$auditoria["auditoria_os"]?>" data-aprovacao-unica-descricao="<?=$auditoria["descricao"]?> - <?=$auditoria["observacao"]?>" >
                                                        <i class="icon-ok-circle icon-white"></i> Aprovar
                                                    </button>
                                                    <button type="button" class="btn btn-mini btn-danger reprova-os-laudo" data-os="<?=$os->os?>" title="<?=$title_cancelar?>" <?=$disabled_cancelar?> >
                                                        <i class="icon-remove-circle icon-white"></i> Reprovar OS 
                                                    </button>
                                        <?php   } else { 

                                                    if(in_array($login_fabrica, [184,200]) AND $auditoria['observacao'] == "Troca de peça usando estoque") {

                                                        $labelAprovar = "Pagar peças";
                                                        ?>
                                                        <button type="button" class="btn btn-mini btn-block btn-danger pull-right reprova-os" data-os="<?=$os->os?>" data-reprovacao-unica="<?=$auditoria["auditoria_os"]?>" data-reprovacao-unica-descricao="<?=$auditoria["descricao"]?> - <?=$auditoria["observacao"]?>" >
                                                            <i class="icon-remove-circle icon-white"></i>Reprovar/Gerar Pedido
                                                        </button>
                                                    <?php
                                                    } else {

                                                        $labelAprovar = "Aprovar";

                                                    }

                                            ?>
                                                    <button type="button" class="btn btn-mini btn-block btn-success pull-right aprova-os" data-os="<?=$os->os?>" data-tipo_os="<?=$os->tipo_os?>" data-cliente-admin="<?=$os->cliente_admin?>" data-aprovacao-unica="<?=$auditoria["auditoria_os"]?>" data-aprovacao-unica-descricao="<?=$auditoria["descricao"]?> - <?=$auditoria["observacao"]?>" >
                                                        <i class="icon-ok-circle icon-white"></i> <?= $labelAprovar ?>
                                                    </button>
                                        <?php   }
                                
                                                if ($login_fabrica == 178 AND $os->tipo_os == "OSR"){ ?>
                                                   <button type="button" class="btn btn-mini btn-block btn-danger pull-right reprova-os" data-os="<?=$os->os?>" data-tipo_os="<?=$os->tipo_os?>" data-reprovacao-unica="<?=$auditoria["auditoria_os"]?>" data-reprovacao-unica-descricao="<?=$auditoria["descricao"]?> - <?=$auditoria["observacao"]?>" >
                                                        <i class="icon-remove-circle icon-white"></i> Reprovar
                                                    </button>
                                                <?php    
                                                }

                                                if (in_array($login_fabrica, array(169,170)) && in_array(strtolower($auditoria["observacao"]), array("os em auditoria de troca de produto", "os em auditoria de ressarcimento"))) {
                                                ?>
                                                    <button type="button" class="btn btn-mini btn-block btn-danger pull-right reprova-os" data-os="<?=$os->os?>" data-reprovacao-unica="<?=$auditoria["auditoria_os"]?>" data-reprovacao-unica-descricao="<?=$auditoria["descricao"]?> - <?=$auditoria["observacao"]?>" >
                                                        <i class="icon-remove-circle icon-white"></i> Reprovar
                                                    </button>
                                                <?php
                                                }if(in_array($login_fabrica, array(35, 104, 157))) {?>
                                                    <button type="button" class="btn btn-mini btn-block btn-danger pull-right reprova-os" data-os="<?=$os->os?>" data-reprovacao-unica="<?=$auditoria["auditoria_os"]?>" data-reprovacao-unica-descricao="<?=$auditoria["descricao"]?> - <?=$auditoria["observacao"]?>" >
                                                        <i class="icon-remove-circle icon-white"></i> Reprovar 
                                                    </button>
                                                <?php }

                                                if(in_array($login_fabrica, [167,177,203]) AND $auditoria['descricao'] == "Auditoria da Fábrica"){
                                                    if(in_array($login_fabrica, [177])){
                                                        if($auditoria['observacao'] == 'Auditoria de Garantia' || ($auditoria['observacao'] == 'AUDITORIA DE ANÁLISE DA GARANTIA' AND $os_auditoria_analise_garantia == true)){
                                                        ?>
                                                            <button type="button" class="btn btn-mini btn-block btn-danger pull-right recusar-os-laudo" data-os="<?=$os->os?>" data-aprovacao-unica="<?=$auditoria["auditoria_os"]?>" >
                                                                <i class="icon-remove-circle icon-white"></i> Recusar
                                                            </button>
                                                        <?php
                                                        }
                                                    }else{
                                                        if($auditoria['observacao'] == 'Auditoria de Suprimento' OR $auditoria['observacao'] == 'Auditoria de Garantia'){
                                                        ?>
                                                            <button type="button" class="btn btn-mini btn-block btn-danger pull-right recusar-os" data-os="<?=$os->os?>" data-aprovacao-unica="<?=$auditoria["auditoria_os"]?>" >
                                                                <i class="icon-remove-circle icon-white"></i> Recusar
                                                            </button>
                                                        <?php
                                                        }    
                                                    }
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td><?=$auditoria["login"]?></td>
                                        <td><?=$auditoria["justificativa"]?></td>
                                        <?php if($login_fabrica == 35 and $os_auditoria_valor_pecas == 't' and $auditoria['observacao'] == 'Valor de Peças'){
                                            
                                            $percentual = ( ($valor_total_pecas+$mao_de_obra_posto) * 100 ) / ($preco + $mao_de_obra_troca)  ;

                                            $percentual = number_format($percentual, 2, ',', '');

                                            ?>
                                            <td class="tac"><?=$qtde_total_pecas?></td>
                                            <td class="tac">R$<?= number_format(($valor_total_pecas+$mao_de_obra_posto), 2, ',', ' ');?></td>
                                            <td class="tac">R$<?= number_format(($preco + $mao_de_obra_troca), 2, ',', ' '); ?></td>
                                            <td class="tac"><?=$percentual?>%</td>
                                        <?php }else{ //echo "<td></td><td></td><td></td><td></td>"; 
                                    } ?>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php 
                            if ($login_fabrica == 178){ 
                                $sqlTecnico = "SELECT * FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND ativo IS TRUE;";
                                $resTecnico = pg_query($con,$sqlTecnico);
                                $countTecnico = pg_num_rows($resTecnico);

                                $sql_agendamento = "
                                    SELECT  TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_agendamento,
                                            TO_CHAR(tbl_tecnico_agenda.confirmado, 'DD/MM/YYYY')       AS data_confirmacao,
                                            TO_CHAR(tbl_tecnico_agenda.data_cancelado, 'DD/MM/YYYY')   AS data_cancelado,
                                            tbl_tecnico.nome AS nome_tecnico,
                                            tbl_tecnico_agenda.tecnico_agenda,
                                            tbl_tecnico_agenda.periodo,
                                            tbl_tecnico_agenda.obs,
                                            tbl_tecnico_agenda.ordem,
                                            tbl_tecnico_agenda.justificativa_cancelado AS motivo_cancelamento
                                    FROM    tbl_tecnico_agenda
                                    LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}
                                    WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica}
                                    AND tbl_tecnico_agenda.os_revenda = $os->os
                                    ORDER BY tbl_tecnico_agenda.tecnico_agenda ASC";
                                $res_agendamento = pg_query($con, $sql_agendamento);

                                $count_agendamento = pg_num_rows($res_agendamento);
                                $xdata_agendamento = pg_fetch_result($res_agendamento, 0, 'data_agendamento');
                                if ($count_agendamento > 0) {
                        ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th colspan="9" class="titulo_tabela">Visitas</th>
                                        </tr>
                                        <tr>
                                            <th class="titulo_coluna">#</th>
                                            <th class="titulo_coluna"><?=traduz("data.agendamento")?></th>
                                            <th class="titulo_coluna"><?=traduz("periodo")?></th>
                                            <th class="titulo_coluna"><?=traduz("data.confirmacao")?></th>
                                            <th class="titulo_coluna"><?=traduz("nome.tecnico")?></th>
                                            <th class="titulo_coluna"><?=traduz("motivo")?></th>
                                            <th class="titulo_coluna"><?=traduz("data.cancelamento")?></th>
                                            <th class="titulo_coluna"><?=traduz("motivo.cancelamento")?></th>
                                            <th class="titulo_coluna"><?=traduz("adicionar.visita").'/'.traduz("remover.visita")?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php 
                                        $agendamento_confirmado = false;
                                        $reagendamento = false;
                                        for ($x = ($count_agendamento - 1); $x >= 0; $x--) {
                                            $data_agendamento    = pg_fetch_result($res_agendamento, $x, 'data_agendamento');
                                            $data_confirmacao    = pg_fetch_result($res_agendamento, $x, 'data_confirmacao');
                                            $nome_tecnico        = pg_fetch_result($res_agendamento, $x, 'nome_tecnico');
                                            $periodo             = pg_fetch_result($res_agendamento, $x, 'periodo');
                                            $obs                 = pg_fetch_result($res_agendamento, $x, 'obs');
                                            $justificativa       = pg_fetch_result($res_agendamento, $x, 'justificativa');
                                            $xtecnico_agenda     = pg_fetch_result($res_agendamento, $x, 'tecnico_agenda');
                                            $motivo_cancelamento = pg_fetch_result($res_agendamento, $x, 'motivo_cancelamento');
                                            $data_cancelado      = pg_fetch_result($res_agendamento, $x, 'data_cancelado');
                                            $ordem               = pg_fetch_result($res_agendamento, $x, 'ordem');
                                            
                                            if ($periodo == "manha"){
                                                $txt_periodo = "Manhã";
                                            } else if ($periodo == "tarde") {
                                                $txt_periodo = "Tarde";
                                            } else {
                                                $txt_periodo = "";
                                            }

                                            if (!empty($motivo_cancelamento)){
                                                $tr_color = "style='background-color: #ff6159;'";
                                            }else{
                                                $tr_color = "";
                                            }

                                            if ($agendamento_confirmado) {
                                                if (!empty($data_confirmacao)) {
                                                    $confirmacao = $data_confirmacao;
                                                } else {
                                                    $confirmacao = "Agendamento Alterado";
                                                }
                                            } else {
                                                if (strlen(trim($data_confirmacao)) > 0) {
                                                    $confirmacao = $data_confirmacao;
                                                    $agendamento_confirmado = true;
                                                }else{
                                                    $confirmacao = "";
                                                    $reagendamento = true;
                                                }
                                            }
                                    ?>
                                            <tr <?=$tr_color?> id="tr_<?=$xtecnico_agenda?>">
                                                <td><?=$ordem?></td>
                                                <td><?=$data_agendamento?></td>
                                                <td><?=$txt_periodo?></td>
                                                <?php if (!empty($motivo_cancelamento)){ ?>
                                                <td>Visita cancelada</td>
                                                <?php }else{ ?>
                                                <td><?=$confirmacao?></td>
                                                <?php } ?>
                                                <td><?=$nome_tecnico?></td>
                                                <td><?=utf8_decode($obs)?></td>
                                                <td><?=$data_cancelado?></td>
                                                <td class='td_motivo_cancelamento'><?=utf8_decode($motivo_cancelamento)?></td>
                                                <td>
                                                    <?php if (empty($motivo_cancelamento)){ ?>
                                                    <button type="button" class="btn btn-mini btn-block btn-primary pull-right aprova-agendamento" data-os="<?=$os->os?>" data-acao="aprovar" data-tecnico_agenda="<?=$xtecnico_agenda?>" data-admin="<?=$login_admin?>">
                                                        Adicionar visita
                                                    </button>
                                                    <button type="button" class="btn btn-mini btn-block btn-danger pull-right cancela-agendamento" data-os="<?=$os->os?>" data-acao="cancelar" data-tecnico_agenda="<?=$xtecnico_agenda?>" data-admin="<?=$login_admin?>">
                                                        Remover visita
                                                    </button>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    ?>    
                                    </tbody>
                                    <?php if (empty($data_fechamento) AND $areaAdmin === false) { ?>
                                    <tfoot>
                                        <tr>
                                            <td class="titulo_coluna" colspan="9" align="center">
                                                <button id="reagendar_os" class="btn btn-primary" type="button"> <?=traduz("reagendar.os")?></button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                    <?php } ?>
                                </table>
                            <?php
                                } 
                            }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            flush();
        }
        ?>
    </div>
    
    <?php if (in_array($login_fabrica, [123,160])) { ?>
            <div id="loading"  >
                <img src="imagens/loading_img.gif" style="z-index:11" />
                <input type="hidden" id="loading_action" value="f" />
                <div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;"></div>
            </div>
    <?php } ?>
    
    <div id="modal-aprova-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
            <form>
                <fieldset>
                    <legend>Opções de aprovação</legend>
                        <label class="checkbox">
                            <input type="checkbox" id="input-aprovar-os-sem-mo" /> Zerar mão de obra do Posto Autorizado
                        </label>
                    <?php
                    if ($login_fabrica == 158) {
                    ?>
                        <label class="checkbox mo-kof">
                            <input type="checkbox" id="input-aprovar-os-sem-mo-kof" /> Zerar mão de obra da KOF
                        </label>
                    <?php
                    }
                    ?>

                    <br />

                    <label>Observação</label>
                    <input type="text" id="input-aprovar-os-justificativa" maxlength="200" style="width: 98%;" value="" />
                    <input type="hidden" name="input_aprovar_tipo_os" id="input_aprovar_tipo_os" value="" />
                </fieldset>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-close-modal-aprova-os" class="btn">Fechar</button>
            <button type="button" id="btn-aprovar-os" class="btn btn-success">Aprovar</button>
        </div>
    </div>

    <div id="modal-cancela-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
            <div class="alert alert-info"><strong>Para cancelar a OS é obrigatório informar o motivo</strong></div>
            <form>
                <fieldset>
                    <label>Motivo</label>
                    <input type="text" id="input-cancelar-os-justificativa" maxlength="200" style="width: 98%;" value="" />
                    <input type="hidden" name="input_cancelar_tipo_os" id="input_cancelar_tipo_os" value="" />
                </fieldset>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-close-modal-cancela-os" class="btn">Fechar</button>
            <button type="button" id="btn-cancelar-os" class="btn btn-danger">Cancelar</button>
        </div>
    </div>

    <div id="modal-reprova-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
            <div class="alert alert-info"><strong>Para reprova é obrigatório informar o motivo</strong></div>
            <form>
                <fieldset>
                    <label>Motivo</label>
                    <input type="text" id="input-reprovar-os-justificativa" maxlength="200" style="width: 98%;" value="" />
                    <input type="hidden" name="input_reprovar_tipo_os" id="input_reprovar_tipo_os" value="" />
                </fieldset>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-close-modal-reprova-os" class="btn">Fechar</button>
            <button type="button" id="btn-reprovar-os" class="btn btn-danger">Reprova</button>
        </div>
    </div>

    <div id="modal-aprova-agendamento" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
            <form>
                <fieldset>
                    <legend>Adicionar visita</legend>
                    <br />

                    <label>Data visita</label>
                    <input type="text" id="input-data-visita" maxlength="200" style="width: 20%;" value="" />
                    <label>Motivo</label>
                    <input type="text" id="input-aprovar-agendamento-justificativa" maxlength="200" style="width: 78%;" value="" />
                </fieldset>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-close-modal-aprova-agendamento" class="btn">Fechar</button>
            <button type="button" id="btn-adicionar-agendamento" class="btn btn-success">Gravar</button>
        </div>
    </div>

    <div id="modal-cancela-agendamento" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
            <div class="alert alert-info"><strong>Para cancelar o agendamento é obrigatório informar o motivo</strong></div>
            <form>
                <fieldset>
                    <label>Motivo</label>
                    <input type="text" id="input-cancelar-agendamento-justificativa" maxlength="200" style="width: 98%;" value="" />
                    <input type="hidden" name="id_tecnico_agenda" id="id_tecnico_agenda" value="">
                </fieldset>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-close-modal-cancela-agendamento" class="btn">Fechar</button>
            <button type="button" id="btn-cancelar-agendamento" class="btn btn-danger">Cancelar</button>
        </div>
    </div>

    <?php
    $km_label = (!in_array($login_fabrica, array(171))) ? 'KM' : 'KM Total';
    $readonly = (!in_array($login_fabrica, array(171))) ? '' : 'readonly';
    ?>
    <div id="modal-alterar-km" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
            <?php if (in_array($login_fabrica, array(171))) { ?>
            <div class="row-fluid" >
                <div class="span6" >
                    <div class="control-group" >
                        <div class="controls controls-row">
                            <div class="span6 input-append">
                                <input id="input-km-ida" class="span12 price_format input-km" type="text" />
                                <span class="add-on" ><strong>KM Ida</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span6" >
                    <div class="control-group" >
                        <div class="controls controls-row">
                            <div class="span6 input-append">
                                <input id="input-km-volta" class="span12 price_format input-km" type="text" />
                                <span class="add-on" ><strong>KM Volta</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
            <div class="row-fluid" >
                <div class="span6" >
                    <div class="control-group" >
                        <div class="controls controls-row">
                            <div class="span6 input-append">
                                <input type="hidden" name="input-alterar-km-aux" id="input-alterar-km-aux" value="">
                                <input id="input-alterar-km" class="span12 price_format" type="text" <?=$readonly;?>/>
                                <span class="add-on" ><strong><?=$km_label;?></strong></span>
                            </div>
                        </div>
                    </div>
                </div>
<?php
                if ($login_fabrica == 35) {
?>
                <div class="span6 tabela_visita"></div>
<?php
                }
?>
            </div>
            <?php if (in_array($login_fabrica, array(171))) {
            ?>
            <div class="row-fluid" >
                <div class="span6" >
                    <div class="control-group" >
                        <div class="controls controls-row">
                            <div class="span6 input-append">
                                <input id="input-visita" class="span12" type="number" />
                                <span class="add-on" ><strong>Visitas</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
        <div class="modal-footer">
            <input type="hidden" name="input_km_tipo_os" id="input_km_tipo_os" value="">
            <button type="button" id="btn-close-modal-alterar-km" class="btn">Fechar</button>
            <button type="button" id="btn-alterar-km" class="btn btn-primary">Gravar</button>
        </div>
    </div>

    <div id="modal-alterar-horas" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
            <div class="row-fluid" >
                <div class="span6" >
                    <div class="control-group" >
                        <div class="controls controls-row">
                            <div class="span6 input-append">
                                <input type="hidden" name="input-alterar-horas-aux" id="input-alterar-horas-aux" value="">
                                <input id="input-alterar-horas" class="span12" value="" type="text" <?=$readonly;?>/>
                                <span class="add-on" ><strong>Qtde Horas</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <input type="hidden" name="input_horas_tipo_os" id="input_horas_tipo_os" value="">
            <button type="button" id="btn-close-modal-alterar-horas" class="btn">Fechar</button>
            <button type="button" id="btn-alterar-horas" class="btn btn-primary">Gravar</button>
        </div>
    </div>


    <div id="modal-alterar-valores-adicionais" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
        <div class="modal-header">
        </div>
        <div class="modal-body">
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-close-modal-alterar-valores-adicionais" class="btn">Fechar</button>
            <button type="button" id="btn-alterar-valores-adicionais" class="btn btn-primary">Gravar</button>
        </div>
    </div>
<?php

    echo "<center><strong>Total:</strong> ".pg_num_rows($resAuditoria)."</center>";

}
?>

<!-- CSS -->
<style>

div.accordion-inner label.control-label {
    font-weight: bold;
}

td.table_auditoria_titulo {
    font-weight: bold;
}

div.os_auditoria_titulo {
    background-color: #DDDDDD;
    text-align: left;
}

div.os_auditoria_titulo a {
    text-decoration: none;
    color: #000000;
    font-weight: bold;
}

th.titulo_coluna {
    background-color: #596D9B !important;
    color: #FFFFFF !important;
    font-weight: bold !important;
}

span.os_km {
    font-weight: bold;
}

div.modal-pecas {
    width: 750px;
    margin-left: -375px;
}

a.accordion-toggle {
    display: inline-block !important;
}

div.os_auditoria_titulo button, div.os_auditoria_titulo span.label {
    margin-top: 8px;
    margin-right: 5px;
}

div.os_auditoria_titulo span.label {
    width: 170px;
    text-align: center;
}

</style>

<!-- JavaScript -->
<script>

<?php if (in_array($login_fabrica,[104,157,167,203])) { ?>

    $(function() {

        $(document).find(".cor-interacao-posto").each(function() {

            let campo_interacao = $(this).attr("rel");
     
            $("#" + campo_interacao + " > .accordion-heading").css("background-color", "#FF4040 !important");
            
        });

    });

    $(function() {

        $(document).find(".cor-interacao-admin").each(function() {

            let campo_interacao = $(this).attr("rel");

            $("#" + campo_interacao + " > .accordion-heading").css("background-color", "#75DC75 !important");
            
        });

    });

<?php } ?>

$(function() {
    Shadowbox.init();
});


$("select").select2();

$("#data_inicial, #data_final").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
$("#input-alterar-horas").mask("00:00");
$("input.price_format").priceFormat({
    prefix: "",
    thousandsSeparator: "",
    centsSeparator: ".",
    centsLimit: 2
});

$(".excluir_peca").click(function(){
     var os_item = $(this).data("os-item");
     var num_os = $(this).data("os");

    $.ajax({
        url: "os_auditoria_unica.php",
        type: "POST",
        data: { ajax_excluir_peca: 'sim', os_item: os_item, os: num_os },
        timeout: 8000
    }).done(function(data){
        console.log(data);
        $(".os_item_"+os_item).remove();

    });
});


$("input.status-auditoria-pesquisa").change(function() {
    var checked = $("input.status-auditoria-pesquisa:checked").val();

    if (checked != "pendente") {
        $("button.listar-todos").hide();
    } else {
        $("button.listar-todos").show();
    }
});

var checked = $("input.status-auditoria-pesquisa:checked").val();

if (checked != "pendente") {
    $("button.listar-todos").hide();
} else {
    $("button.listar-todos").show();
}

$(document).on("click", "span[rel=lupa]", function() {
    $.lupa($(this));
});

$(document).on("click", "span[rel=trocar_posto]", function() {
    $("#posto_id, #posto_codigo, #posto_nome").val("");

    $("#posto_codigo, #posto_nome")
    .prop({ readonly: false })
    .next("span[rel=trocar_posto]")
    .attr({ rel: "lupa" })
    .find("i")
    .removeClass("icon-remove")
    .addClass("icon-search")
    .removeAttr("title");
});

$("#oss_auditoria").collapse();

function retorna_posto(retorno) {
    $("#posto_id").val(retorno.posto);
    $("#posto_codigo").val(retorno.codigo);
    $("#posto_nome").val(retorno.nome);

    $("#posto_codigo, #posto_nome")
    .prop({ readonly: true })
    .next("span[rel=lupa]")
    .attr({ rel: "trocar_posto" })
    .find("i")
    .removeClass("icon-search")
    .addClass("icon-remove")
    .attr({ title: "Trocar Posto" });
}

$('#input-km-ida').on('change', function(){
    var km_final = parseFloat($(this).val()) + parseFloat($('#input-km-volta').val());
    $('#input-alterar-km').val(km_final.toFixed(2));
});
$('#input-km-volta').on('change', function(){
    var km_final = parseFloat($(this).val()) + parseFloat($('#input-km-ida').val());
    $('#input-alterar-km').val(km_final.toFixed(2));
});

var modal_km;
var modal_km_os;
var modal_km_tipo_os
$("button.alterar-km").on("click", function() {
    modal_km    = $("#modal-alterar-km");
    modal_km_os = $(this).data("os");
    modal_km_tipo_os = $(this).data("tipo_os");
    var km      = $(this).data("km");

    $(modal_km).find("div.modal-body > div.alert").remove();
    if (typeof $('#input-km-ida') !== undefined) {
        $('#input-alterar-km').val(km.toFixed(2));
        $('#input-alterar-km-aux').val(km.toFixed(2));
        $.ajax({
            url: window.location.href,
            type: "POST",
            dataType:"JSON",
            data: { ajax: 'sim', action: 'consulta_ida_volta', os: modal_km_os },
            timeout: 8000
        }).fail(function(){
            alert('Ocorreu um erro ao tentar carregar o KM de ida e de volta');
        }).done(function(data){
            var km_ida = parseFloat(data.qtde_km_ida);
            var km_volta = parseFloat(data.qtde_km_volta);
            $('#input-km-ida').val(km_ida.toFixed(2));
            $('#input-km-volta').val(km_volta.toFixed(2));
            $('#input-visita').val(data.qtde_visita);
<?php
if ($login_fabrica == 35) {
?>
            var tabela = "<table border='1' cellspacing='1' cellpadding='1'><tr><th>Data Agendamento</th><th>Justificativa</th></tr>";
            var total_km = parseFloat(data.qtde_visita) * parseFloat(km);
            data.visitas.forEach(function(v){
                tabela += "<tr><td class='tac'>"+v.data_agendamento+"</td>";
                tabela += "<td class='tac'>"+v.justificativa+"</td></tr>";
            });
            tabela += "<tr><td colspan='2' class='tar total_km'><strong>Total Km</strong>: "+total_km.toFixed(2)+"</td></tr></table>";
            $(".tabela_visita").html(tabela);
<?php
}
?>
        });
    }else{
        $(modal_km).find("input").val(km.toFixed(2));
    }
    $("#input_km_tipo_os").val(modal_km_tipo_os);
    $(modal_km).find("div.modal-header").html("<h4>OS "+modal_km_os+"</h4>");
    $(modal_km).modal("show");
});

$("#btn-close-modal-alterar-km").on("click", function() {
    $(modal_km).modal("hide");
});

$(".endereco").click(function(){
    var numos = $(this).data("os");
    Shadowbox.open({
        content: "rota_endereco.php?num+os="+numos,
        player: "iframe",
        title:  "Interações da OS "+os,
        width:  1200,
        height: 700
    });
});


$(".alterar-os").on("click", function(){
    var numos = $(this).data("os");
    window.open("cadastro_os.php?os_id="+numos, '_blank');
});

$("#btn-alterar-km").on("click", function() {
    var btn        = $(this);
    var btn_fechar = $("#btn-close-modal-alterar-km");
    var tipo_os = $("#input_km_tipo_os").val();

    if (typeof $('#input-km-ida') !== undefined) {
        var km = $('#input-alterar-km').val();
    }else{
        var km = $(modal_km).find("input").val();
    }

    if (tipo_os == undefined){
        tipo_os = "";
    }

    var km_ida =0; var km_volta = 0; var visita = 0;
    if (typeof $('#input-km-ida') !== undefined) {
        km_ida   = $('#input-km-ida').val();
        km_volta = $('#input-km-volta').val();
        visita   = $('#input-visita').val();
    }

    $.ajax({
        url: "os_auditoria_unica.php",
        type: "post",
        dataType:"JSON",
        data: { "ajax_altera_km": true, "os": modal_km_os, "km": km, "km_ida": km_ida, "km_volta": km_volta, "visita": visita, "tipo_os": tipo_os },
        beforeSend: function() {
            $(modal_km).find("div.modal-body > div.alert").remove();
            $(btn).prop({ disabled: true }).text("Gravando...");
            $(btn_fechar).prop({ disabled: true });
        },
        async: false,
        timeout: 10000
    }).fail(function(res) {
        $(modal_km).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao gravar o KM</div>");
        $(btn).prop({ disabled: false }).text("Gravar");
        $(btn_fechar).prop({ disabled: false });
    }).done(function(res) {

        if (res.erro) {
            $(modal_km).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.erro+"</div>");
        } else {
            $(modal_km).find("div.modal-body").prepend("<div class='alert alert-success' >KM alterado com sucesso</div>");
            $("#"+modal_km_os).find("span.os_km").text(km+" km");
        }

        $(btn).prop({ disabled: false }).text("Gravar");
        $(btn_fechar).prop({ disabled: false });
    });
});

var modal_valores_adicionais;
var modal_valores_adicionais_os;

$("button.alterar-valores-adicionais").on("click", function() {
    modal_valores_adicionais    = $("#modal-alterar-valores-adicionais");
    modal_valores_adicionais_os = $(this).data("os");

    $(modal_valores_adicionais).find("div.modal-body").html("");
    $(modal_valores_adicionais).find("div.modal-header").html("<h4>OS "+modal_valores_adicionais_os+"</h4>");

    valores_adicionais = [];

    $(this).parent("td").find("ul.os_valores_adicionais > li").each(function(){
        var descricao = $(this).data("descricao");
        var valor     = $(this).data("valor");

        $(modal_valores_adicionais).find("div.modal-body").append("\
            <div class='row-fluid valor-adicional' >\
                <div class='span11' >\
                    <div class='control-group' >\
                        <label class='control-label descricao' >"+descricao+"</label>\
                        <div class='controls controls-row' >\
                            <div class='span4 input-prepend' >\
                                <span class='add-on' ><strong>R$</strong></span>\
                                <input class='span12 price_format valor' type='text' value='"+valor+"' />\
                            </div>\
                        </div>\
                    </div>\
                </div>\
            </div>\
        ");
    });

    $(modal_valores_adicionais).find("input.price_format").priceFormat({
        prefix: "",
        thousandsSeparator: "",
        centsSeparator: ".",
        centsLimit: 2
    });

    $(modal_valores_adicionais).modal("show");
});

$("#btn-close-modal-alterar-valores-adicionais").on("click", function() {
    $(modal_valores_adicionais).modal("hide");
});

$("#btn-alterar-valores-adicionais").on("click", function() {
    var btn        = $(this);
    var btn_fechar = $("#btn-close-modal-alterar-valores-adicionais");
    var valores_adicionais = [];

    $("div.valor-adicional").each(function() {
        var descricao = $(this).find("label.descricao").text();
        var valor     = $(this).find("input.valor").val();

        valores_adicionais.push({
            descricao: descricao,
            valor: valor
        });
    });

    $.ajax({
        url: "os_auditoria_unica.php",
        type: "post",
        data: {
            "ajax_altera_valores_adicionais": true,
            "os": modal_valores_adicionais_os,
            "valores_adicionais": valores_adicionais
        },
        beforeSend: function() {
            $(modal_valores_adicionais).find("div.modal-body > div.alert").remove();
            $(btn).prop({ disabled: true }).text("Gravando...");
            $(btn_fechar).prop({ disabled: true });
        },
        async: false,
        timeout: 10000
    }).fail(function(res) {
        $(modal_valores_adicionais).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao gravar os Valores Adicionais</div>");
        $(btn).prop({ disabled: false }).text("Gravar");
        $(btn_fechar).prop({ disabled: false });
    }).done(function(res) {
        res = JSON.parse(res);

        if (res.erro) {
            $(modal_valores_adicionais).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.erro+"</div>");
        } else {
            $(modal_valores_adicionais).find("div.modal-body").prepend("<div class='alert alert-success' >Valores Adicionais alterados com sucesso</div>");

            var ul = $("#"+modal_valores_adicionais_os).find("ul.os_valores_adicionais");
            $(ul).find("li").remove();

            $.each(valores_adicionais, function(i, valor_adicional) {
                $(ul).append("\
                    <li data-descricao='"+valor_adicional.descricao+"' data-valor='"+valor_adicional.valor+"'>"+valor_adicional.descricao+": R$ "+accounting.formatNumber(valor_adicional.valor, 2, "," ,".")+"</li>\
                ");
            });
        }

        $(btn).prop({ disabled: false }).text("Gravar");
        $(btn_fechar).prop({ disabled: false });
    });
});

$("button.ver-pecas").on("click", function() {
    $(this).next("div.modal-pecas").modal("show");
});

$("button.visualizar-os").on("click", function() {
    var os = $(this).data("os");
    var tipo_os = $(this).data("tipo_os");
    
    if ("<?=$status?>" == "cancelada") {
        window.open("relatorio_os_excluida.php?os="+os);
    } else {
        if (tipo_os == "OSR"){
            window.open("os_revenda_press.php?os_revenda="+os);
        }else{
            window.open("os_press.php?os="+os);
        }
        
    }
});

$("button.interagir-os").on("click", function() {
    var os = $(this).data("os");

    Shadowbox.open({
        content: "interacao_os.php?os="+os,
        player: "iframe",
        width: 1024,
        title: "Ordem de Serviço "+os,
        options: {
            enableKeys: false
        }
    });
});

$(".auditorlog_pecas").on("click", function() {
    var os = $(this).data("os");
    var fabrica = <?=$login_fabrica?>;

    Shadowbox.open({
        content: "relatorio_log_alteracao_new.php?parametro=tbl_os_item_pecas&id="+fabrica+"*"+os,
        player: "iframe",
        width: 1024,
        title: " Logs de Alteração - Peças ",
        options: {
            enableKeys: false
        }
    });
});

<?php if($login_fabrica == 190){ ?>
    var modal_horas;
    var modal_horas_os;
    var modal_horas_tipo_os;

    $("button.alterar-horas").on("click", function() {
        modal_horas    = $("#modal-alterar-horas");
        modal_horas_os = $(this).data("os");
        modal_horas_tipo_os = $(this).data("tipo_os");
        var horas      = $(this).data("horas");

        $(modal_km).find("div.modal-body > div.alert").remove();
        
        $(modal_horas).find("input").val(horas);
        $("#input_km_tipo_os").val(modal_horas_tipo_os);
        $(modal_horas).find("div.modal-header").html("<h4>OS "+modal_horas_os+"</h4>");
        $(modal_horas).modal("show");
    });

    $("#btn-close-modal-alterar-horas").on("click", function() {
        $(modal_horas).modal("hide");
    });

    $("#btn-alterar-horas").on("click", function() {
        var btn        = $(this);
        var btn_fechar = $("#btn-close-modal-alterar-horas");
        var horas = $('#input-alterar-horas').val();

        $.ajax({
            url: "os_auditoria_unica.php",
            type: "post",
            dataType:"JSON",
            data: { "ajax_altera_horas": true, "os": modal_horas_os, "horas": horas},
            beforeSend: function() {
                $(modal_horas).find("div.modal-body > div.alert").remove();
                $(btn).prop({ disabled: true }).text("Gravando...");
                $(btn_fechar).prop({ disabled: true });
            },
            async: false,
            timeout: 10000
        }).fail(function(res) {
            $(modal_horas).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao gravar qtde de horas</div>");
            $(btn).prop({ disabled: false }).text("Gravar");
            $(btn_fechar).prop({ disabled: false });
        }).done(function(res) {

            if (res.erro) {
                $(modal_horas).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.erro+"</div>");
            } else {
                $(modal_horas).find("div.modal-body").prepend("<div class='alert alert-success' >Qtde de horas alterada com sucesso</div>");
                $("#"+modal_horas_os).find("span.horas_km").text(horas+" qtde horas");
            }

            $(btn).prop({ disabled: false }).text("Gravar");
            $(btn_fechar).prop({ disabled: false });
        });
    });
<?php } ?>

function retorno_laudo(respondido, auditoria,numero_os){
    if(respondido == true){
        //$("button.recusar-os[data-aprovacao-unica="+auditoria+"]").parents('tr').remove();
        $("#"+numero_os+" .status-auditoria-os").html('<span class="label label-important">Reprovada</span>');
    }
    Shadowbox.close();
}

$("button.recusar-os").on("click", function() {
    var os = $(this).data("os");
    var auditoria = $(this).data("aprovacao-unica");

    Shadowbox.open({
        content: "laudo_brother.php?os="+os+"&auditoria="+auditoria,
        player: "iframe",
        width: 1024,
        title: "Ordem de Serviço "+os,
        options: {
            modal: true,
            enableKeys: false,
            displayNav: false
        }
    });
});

//recusar-os-laudo
$("button.recusar-os-laudo").on("click", function() {
    var os = $(this).data("os");
    var auditoria = $(this).data("aprovacao-unica");
    var defeito_constatado = "";

    <?php if ($login_fabrica == 177){ ?>
        defeito_constatado = $("#defeito_constatado_"+os+" option:selected", window.parent.document).val();
        if (defeito_constatado == ""){
            alert("Favor selecione o defeito constatado");
            return;
        }
    <?php  } ?>
    
    Shadowbox.open({
        content: "laudo_anauger.php?os="+os+"&auditoria="+auditoria+"&defeito_constatado="+defeito_constatado,
        player: "iframe",
        width: 1024,
        title: "Ordem de Serviço "+os,
        options: {
            modal: true,
            enableKeys: false,
            displayNav: false
        }
    });
});

var modal_aprova_os_tipo_os;
var modal_aprova_os;
var modal_aprova_os_os;
var modal_aprova_os_finalizada;
var modal_aprova_os_aprovacao_unica;

<?php
if ($login_fabrica == 158) {
?>
    var modal_aprova_os_cliente_admin;
<?php
}
?>

<?php if ($login_fabrica == 178){ ?>

    // Adicionar Visita 
    var modal_aprova_agendamento;
    var modal_aprova_agendamento_os;
    var modal_tecnico_agenda;
    var admin_agendamento = <?=$login_admin?>;
    $(function(){
        $("#input-data-visita").datepicker({minDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    });

    $("button.aprova-agendamento").on("click", function() {
        modal_aprova_agendamento    = $("#modal-aprova-agendamento");
        modal_aprova_agendamento_os = $(this).data("os");
        modal_tecnico_agenda        = $(this).data("tecnico_agenda");

        $(modal_aprova_agendamento).find("div.modal-body > div.alert").remove();
        $(modal_aprova_agendamento).find("div.modal-header").html("<h4>OS "+modal_aprova_agendamento_os+"</h4>");

        $(modal_aprova_agendamento).find("input[type=text]").val("");
        $("#btn-adicionar-agendamento").prop({ disabled: false }).text("Gravar");
        
        $(modal_aprova_agendamento).modal("show");
    });

    $("#btn-close-modal-aprova-agendamento").on("click", function() {
        $(modal_aprova_agendamento).modal("hide");
    });

    $("#btn-adicionar-agendamento").on("click", function() {
        var justificativa = String($("#input-aprovar-agendamento-justificativa").val()).trim();
        var data_visita   = $("#input-data-visita").val();
        var btn           = $(this);
        var btn_fechar    = $("#btn-close-modal-aprova-agendamento");

        if (justificativa == "undefined" || justificativa == "") {
            $(modal_aprova_agendamento).find("div.modal-body").prepend("<div class='alert alert-danger' >Favor preencher o motivo</div>");
            $(modal_aprova_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").delay(3000).fadeTo("slow","0", function(){
                $(modal_aprova_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
            });
            return;
        }

        if (data_visita == "undefined" || data_visita == ""){
            $(modal_aprova_agendamento).find("div.modal-body").prepend("<div class='alert alert-danger' >Favor selecione a data da visita</div>");
            $(modal_aprova_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").delay(3000).fadeTo("slow","0", function(){
                $(modal_aprova_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
            });
            return;
        }

        var newRow = $("<tr>");
        var cols = "";

        var data_ajax = {
            ajax_reagendar_os: true,
            os_revenda: modal_aprova_agendamento_os,
            obs_motivo_agendamento: justificativa,
            data_agendamento_novo: data_visita,
            admin_agendamento: admin_agendamento,
            login_fabrica: <?=$login_fabrica?>,
            programa: "auditoria"
        };
        
        $.ajax({
            url: "../agendamentos_pendentes.php",
            type: "post",
            data: data_ajax,
            beforeSend: function() {
                $(modal_aprova_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
                $(btn).prop({ disabled: true }).text("Gravando...");
                $(btn_fechar).prop({ disabled: true });
            },
            async: false,
            timeout: 10000
        }).fail(function(res) {
            $(modal_aprova_agendamento).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao aprovar a OS</div>");
            $(btn).prop({ disabled: false }).text("Gravar");
            $(btn_fechar).prop({ disabled: false });
        }).done(function(res) {
            res = JSON.parse(res);
            if (res.sucesso == 1){
                $(modal_aprova_agendamento).find("div.modal-body").prepend("<div class='alert alert-success' >Visita cadastrada</div>");
                $(btn).text("Gravado");
                
                cols += '<td>'+res.dados["ordem"]+'</td>';
                cols += '<td>'+res.dados["data_agendamento_novo"]+'</td>';
                cols += '<td>&nbsp;</td>';
                cols += '<td>'+res.dados["data_agendamento_novo"]+'</td>';
                cols += '<td>&nbsp;</td>';
                cols += '<td>'+res.dados["motivo"]+'</td>';
                cols += '<td>&nbsp;</td>';
                cols += '<td>&nbsp;</td>';
                cols += '<td>&nbsp;</td>';

                newRow.append(cols);       
                $(".aprova-agendamento").parents("tbody").prepend(newRow);
            }else{
                $(modal_aprova_agendamento).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.msg+"</div>");
                $(btn).prop({ disabled: false }).text("Aprovar");
            }
            $(btn_fechar).prop({ disabled: false });
        });
    });

    // Remover Visita
    var modal_cancela_agendamento;
    var modal_cancela_agendamento_os;

    $("button.cancela-agendamento").on("click", function() {
        modal_cancela_agendamento    = $("#modal-cancela-agendamento");
        modal_cancela_agendamento_os = $(this).data("os");
        modal_cancela_tecnico_agenda = $(this).data("tecnico_agenda");

        $(modal_cancela_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
        $(modal_cancela_agendamento).find("div.modal-header").html("<h4>OS "+modal_cancela_agendamento_os+"</h4>");
        $(modal_cancela_agendamento).find("input[type=text]").val("");
        
        $("#id_tecnico_agenda").val(modal_cancela_tecnico_agenda);

        $("#btn-cancelar-agendamento").prop({ disabled: false }).text("Cancelar");

        $(modal_cancela_agendamento).modal("show");
    });

    $("#btn-cancelar-agendamento").on("click", function() {
        var justificativa = String($("#input-cancelar-agendamento-justificativa").val()).trim();
        var tecnico_agenda = $("#id_tecnico_agenda").val();

        if (justificativa == "undefined" || justificativa.length < 8) {
            $(modal_cancela_agendamento).find("div.modal-body").prepend("<div class='alert alert-danger' >Para cancelar o agendamento é necessário digitar pelo menos 8 caracteres no motivo</div>");
            $(modal_cancela_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").delay(3000).fadeTo("slow","0", function(){
                $(modal_cancela_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
            });
        }

        var btn        = $(this);
        var btn_fechar = $("#btn-close-modal-cancela-agendamento");

        var data_ajax = {
            ajax_cancelar_visita: true,
            os_revenda: modal_cancela_agendamento_os,
            motivo_cancelamento: justificativa,
            admin_cancelamento: admin_agendamento,
            tecnico_agenda: tecnico_agenda,
            login_fabrica: <?=$login_fabrica?>,
            programa: "auditoria"
        };

        $.ajax({
            url: "../agendamentos_pendentes.php",
            type: "post",
            data: data_ajax,
            beforeSend: function() {
                $(modal_cancela_agendamento).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
                $(btn).prop({ disabled: true }).text("Cancelando...");
                $(btn_fechar).prop({ disabled: true });
            },
            async: false,
            timeout: 10000
        }).fail(function(res) {
            $(modal_cancela_agendamento).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao cancelar a OS</div>");
            $(btn).prop({ disabled: false }).text("Cancelar");
            $(btn_fechar).prop({ disabled: false });
        }).done(function(res) {
            res = JSON.parse(res);

            if (res.sucesso == 1){
                $(modal_cancela_os).find("div.modal-body").prepend("<div class='alert alert-success' >Visita Cancelada</div>");
                $(btn).text("Cancelado");
                $("#tr_"+tecnico_agenda).remove();

            }else{
                $(modal_cancela_agendamento).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.msg+"</div>");
                $(btn).prop({ disabled: false }).text("Cancelar");
            }
            $(btn_fechar).prop({ disabled: false });
        });
    });

    $("#btn-close-modal-cancela-agendamento").on("click", function() {
        $(modal_cancela_agendamento).modal("hide");
    });
<?php } ?>

$("button.aprova-os").on("click", function() {
    modal_aprova_os                 = $("#modal-aprova-os");
    modal_aprova_os_os              = $(this).data("os");
    modal_aprova_os_aprovacao_unica = $(this).data("aprovacao-unica");
    modal_aprova_os_tipo_os         = $(this).data("tipo_os");

    $(modal_aprova_os).find("div.modal-body > div.alert").remove();



    if (modal_aprova_os_aprovacao_unica) {
        var title = $(this).data("aprovacao-unica-descricao");
        $(modal_aprova_os).find("div.modal-header").html("<h4>OS "+modal_aprova_os_os+"<br />"+title+"</h4>");
    } else {
        $(modal_aprova_os).find("div.modal-header").html("<h4>OS "+modal_aprova_os_os+"</h4>");
    }

    $(modal_aprova_os).find("label.checkbox, legend").show();
    $(modal_aprova_os).find("input[type=checkbox]").prop({ checked: false });
    $(modal_aprova_os).find("input[type=text]").val("");
    $("#btn-aprovar-os").prop({ disabled: false }).text("Aprovar");
    $("#input_aprovar_tipo_os").val(modal_aprova_os_tipo_os);
    <?php
    if ($login_fabrica == 158) {
    ?>
        modal_aprova_os_cliente_admin = $(this).data("cliente-admin");

        if (modal_aprova_os_cliente_admin == "158-KOF") {
            $(modal_aprova_os).find("label.mo-kof").show();
        } else {
            $(modal_aprova_os).find("label.mo-kof").hide();
        }
    <?php
    }
    ?>

    modal_aprova_os_finalizada = $("#os_auditoria_"+modal_aprova_os_os).data("fechamento");

    if (modal_aprova_os_finalizada.length > 0 && $.inArray(<?=$login_fabrica?>, [169, 170]) == -1) {
        $(modal_aprova_os).find("div.modal-body").prepend("<div class='alert alert-info' ><strong>Essa OS já está finalizada, portanto não será possível alterar as opções de aprovação</strong></div>");
        $(modal_aprova_os).find("label.checkbox, legend").hide();
    } else {
        var aprovado_sem_mo = false;

        $("#os_auditoria_"+modal_aprova_os_os).find("td.status-auditoria-os").each(function() {
            if ($(this).find("span.label-warning").length > 0) {
                aprovado_sem_mo = true;
                return false;
            }
        });

        if (aprovado_sem_mo == true) {
            $(modal_aprova_os).find("div.modal-body").prepend("<div class='alert alert-info' ><strong>Essa OS já foi aprovada sem pagamento de mão de obra, se a opção de zerar a mão de obra do posto autorizado for alterada, o pagamento da mão de obra dessa OS será habilitado</strong></div>");
            $("#input-aprovar-os-sem-mo").prop({ checked: true });
        }

        <?php
        if ($login_fabrica == 158) {
        ?>
            if ($("#os_auditoria_"+modal_aprova_os_os).data("aprovado-sem-mo-kof") == "t") {
                $(modal_aprova_os).find("div.modal-body").prepend("<div class='alert alert-info' ><strong>Essa OS já foi aprovada sem pagamento de mão de obrad da KOF, se a opção de zerar a mão de obra da KOF for alterada, o pagamento da mão de obra da KOF dessa OS será habilitado</strong></div>");
                $("#input-aprovar-os-sem-mo-kof").prop({ checked: true });
            }
        <?php
        }
        ?>
    }

    $(modal_aprova_os).modal("show");
});

$("#btn-aprovar-os").on("click", function() {
    var justificativa = String($("#input-aprovar-os-justificativa").val()).trim();
    var tipo_os = $("#input_aprovar_tipo_os").val();

    if (tipo_os == undefined){
        tipo_os = "";
    }

    if (justificativa == "undefined") {
        justificativa = "";
    }

    var btn        = $(this);
    var btn_fechar = $("#btn-close-modal-aprova-os");

    var data_ajax = {
        ajax_aprova_auditoria: true,
        os: modal_aprova_os_os,
        justificativa: justificativa,
        tipo_os: tipo_os
    };

    <?php if ($login_fabrica == 177){ ?>
        var defeito_constatado = $("#defeito_constatado_"+modal_aprova_os_os+" option:selected", window.parent.document).val();
        
        if (defeito_constatado != "" && defeito_constatado != undefined){
            data_ajax.defeito_constatado = defeito_constatado
        }
    <?php  } ?>

    <?php if (in_array($login_fabrica, array(169,170))){ ?>
            if ($("#input-aprovar-os-sem-mo").is(":checked")) {
                data_ajax.sem_mao_de_obra = true;
            } else {
                data_ajax.sem_mao_de_obra = false;
            }
    <?php }else{ ?>

            if (modal_aprova_os_finalizada.length == 0) {

                if ($("#input-aprovar-os-sem-mo").is(":checked")) {
                    data_ajax.sem_mao_de_obra = true;
                } else {
                    data_ajax.sem_mao_de_obra = false;
                }

                <?php
                if ($login_fabrica == 158) {
                ?>
                    if (modal_aprova_os_cliente_admin == "158-KOF") {
                        if ($("#input-aprovar-os-sem-mo-kof").is(":checked")) {
                            data_ajax.sem_mao_de_obra_kof = true;
                        } else {
                            data_ajax.sem_mao_de_obra_kof = false;
                        }
                    }
                <?php
                }
                ?>
            }

    <?php } ?>

    if (modal_aprova_os_aprovacao_unica) {
        data_ajax.auditoria = modal_aprova_os_aprovacao_unica;
    }

    $.ajax({
        url: "os_auditoria_unica.php",
        type: "post",
        data: data_ajax,
        beforeSend: function() {
            $(modal_aprova_os).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
            $(btn).prop({ disabled: true }).text("Aprovando...");
            $(btn_fechar).prop({ disabled: true });
        },
        async: false,
        timeout: 10000
    }).fail(function(res) {
        $(modal_aprova_os).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao aprovar a OS</div>");
        $(btn).prop({ disabled: false }).text("Aprovar");
        $(btn_fechar).prop({ disabled: false });
    }).done(function(res) {
        res = JSON.parse(res);

        if (res.erro) {
            $(modal_aprova_os).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.erro+"</div>");
            $(btn).prop({ disabled: false }).text("Aprovar");
        } else {
            $(modal_aprova_os).find("div.modal-body").prepend("<div class='alert alert-success' >OS Aprovada</div>");
            $(btn).text("Aprovado");

            if (res.aprovado_sem_mo == true) {
                var classAprovado = "label-warning";
                var textAprovado  = "Aprovado sem Mão de Obra "+res.data;
            } else {
                var classAprovado = "label-success";
                var textAprovado  = "Aprovada "+res.data;
            }

            var quantidade_pendente = 0;
            var os_aprovada         = true;

            $("#"+modal_aprova_os_os).find("td.status-auditoria-os").each(function() {
                var pendente = $(this).find("span.label-info");

                if (pendente.length > 0) {
                    var btn_aprovacao_unica = $(this).find("button.aprova-os");

                    <?php if(in_array($login_fabrica, [167, 203])){ ?>
                        var btn_recusar = $(this).find("button.recusar-os");
                        $(btn_recusar).remove();
                    <?php } ?>
                    if (res.aprovacao_unica && res.aprovacao_unica != $(btn_aprovacao_unica).data("aprovacao-unica")) {
                        quantidade_pendente++;
                        return;
                    }

                    $(pendente).removeClass("label-info").addClass(classAprovado).text(textAprovado);

                    $(btn_aprovacao_unica).nextAll("button").remove();
                    $(btn_aprovacao_unica).prevAll("button").remove();

                    $(btn_aprovacao_unica).remove();
                    $(this).next("td").text(res.admin);
                    $(this).next("td").next("td").text(res.justificativa);
                }
            });

            if (res.aprovacao_unica && quantidade_pendente > 0) {
                os_aprovada = false;
            }

            if (os_aprovada) {
                var div_titulo = $("#"+modal_aprova_os_os).find("div.os_auditoria_titulo");

                $(div_titulo).find("button.aprova-os, button.cancelar-os, button.reprova-os").remove();
                $(div_titulo).prepend("\
                    <span class='label label-success pull-right'>Aprovado</span>\
                ");
            }
        }

        $(btn_fechar).prop({ disabled: false });

        if (res.troca_obrigatoria == true) {
            window.location.href = "os_troca_subconjunto.php?os="+modal_aprova_os_os;
        }

        $(modal_aprova_os).modal("hide");

    });
});

$("#btn-close-modal-aprova-os").on("click", function() {
    $(modal_aprova_os).modal("hide");
});

var modal_cancela_os;
var modal_cancela_os_os;

$("button.cancelar-os").on("click", function() {
    modal_cancela_os    = $("#modal-cancela-os");
    modal_cancela_os_os = $(this).data("os");
    modal_cancela_os_tipo_os = $(this).data("tipo_os");

    $(modal_cancela_os).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
    $(modal_cancela_os).find("div.modal-header").html("<h4>OS "+modal_cancela_os_os+"</h4>");
    $(modal_cancela_os).find("input[type=text]").val("");
    $("#btn-cancelar-os").prop({ disabled: false }).text("Cancelar");
    $("#input_cancelar_tipo_os").val(modal_cancela_os_tipo_os);


    $(modal_cancela_os).modal("show");
});

$("button.reprova-os-laudo").click(function(){

    let os = $(this).data("os");

    Shadowbox.open({
        content: "cancela_os_auditoria.php?os="+os,
        player: "iframe",
        title:  "Cancelar OS "+os,
        width:  600,
        height: 400
    });
});


$("#btn-cancelar-os").on("click", function() {

    var justificativa = String($("#input-cancelar-os-justificativa").val()).trim();
    var tipo_os = $("#input_cancelar_tipo_os").val();

    if (tipo_os == undefined){
        tipo_os = "";
    }

    if (justificativa == "undefined" || justificativa.length < 8) {
        alert("Para cancelar a OS é necessário digitar pelo menos 8 caracteres no motivo");
        return false;
    }

    var btn        = $(this);
    var btn_fechar = $("#btn-close-modal-cancela-os");

    var data_ajax = {
        ajax_cancela_os: true,
        os: modal_cancela_os_os,
        justificativa: justificativa,
        tipo_os: tipo_os
    };

    $.ajax({
        url: "os_auditoria_unica.php",
        type: "post",
        data: data_ajax,
        beforeSend: function() {
            $(modal_cancela_os).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
            $(btn).prop({ disabled: true }).text("Cancelando...");
            $(btn_fechar).prop({ disabled: true });
        },
        async: false,
        timeout: 10000
    }).fail(function(res) {
        $(modal_cancela_os).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao cancelar a OS</div>");
        $(btn).prop({ disabled: false }).text("Cancelar");
        $(btn_fechar).prop({ disabled: false });
    }).done(function(res) {
        res = JSON.parse(res);

        if (res.erro) {
            $(modal_cancela_os).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.erro+"</div>");
            $(btn).prop({ disabled: false }).text("Cancelar");
        } else {
            $(modal_cancela_os).find("div.modal-body").prepend("<div class='alert alert-success' >OS Cancelada</div>");
            $(btn).text("Cancelado");

            $("#"+modal_cancela_os_os).find("td.status-auditoria-os").each(function() {
                $(this).html("<span class='label label-important' >Cancelado</span>");

                $(this).next("td").text(res.admin);
                $(this).next("td").next("td").text(res.justificativa);
            });

            var div_titulo = $("#"+modal_cancela_os_os).find("div.os_auditoria_titulo");
            
            $(div_titulo).find("button.aprova-os, button.cancelar-os, button.reprova-os, button.trocar-produto").remove();
            $(div_titulo).prepend("\
                <span class='label label-important pull-right'>Cancelado</span>\
            ");
        }

        $(btn_fechar).prop({ disabled: false });
    });
});

$("#btn-close-modal-cancela-os").on("click", function() {
    $(modal_cancela_os).modal("hide");
});

var modal_reprova_os;
var modal_reprova_os_os;
var modal_reprova_os_reprovacao_unica;

$("button.reprova-os").on("click", function() {
    modal_reprova_os                  = $("#modal-reprova-os");
    modal_reprova_os_os               = $(this).data("os");
    modal_reprova_os_reprovacao_unica = $(this).data("reprovacao-unica");
    modal_reprova_os_tipo_os          = $(this).data("tipo_os");

    $(modal_reprova_os).find("div.modal-body > div.alert").remove();

    if (modal_reprova_os_reprovacao_unica) {
        var title = $(this).data("reprovacao-unica-descricao");
        $(modal_reprova_os).find("div.modal-header").html("<h4>OS "+modal_reprova_os_os+"<br />"+title+"</h4>");
    } else {
        $(modal_reprova_os).find("div.modal-header").html("<h4>OS "+modal_reprova_os_os+"</h4>");
    }

    $(modal_reprova_os).find("label.checkbox, legend").show();
    $(modal_reprova_os).find("input[type=checkbox]").prop({ checked: false });
    $(modal_reprova_os).find("input[type=text]").val("");
    $("#btn-reprovar-os").prop({ disabled: false }).text("Reprovar");
    $("#input_reprovar_tipo_os").val(modal_reprova_os_tipo_os);

    $(modal_reprova_os).modal("show");
});

//botao reprovar vermelho denrto da div
$("#btn-reprovar-os").on("click", function() {
  
    var justificativa = String($("#input-reprovar-os-justificativa").val()).trim();
    var tipo_os = $("#input_reprovar_tipo_os").val();

    if (tipo_os == undefined){
        tipo_os = "";
    }

    if (justificativa == "undefined" || justificativa.length < 8) {
        alert("Para reprovar é necessário digitar pelo menos 8 caracteres no motivo");
        return false;
    }

    var btn        = $(this);
    var btn_fechar = $("#btn-close-modal-reprova-os");

    var data_ajax = {
        ajax_reprova_auditoria: true,
        os: modal_reprova_os_os,
        justificativa: justificativa,
        tipo_os: tipo_os
    };

    if (modal_reprova_os_reprovacao_unica) {
        data_ajax.auditoria = modal_reprova_os_reprovacao_unica;
    }

    $.ajax({
        url: "os_auditoria_unica.php",
        type: "post",
        data: data_ajax,
        beforeSend: function() {
            $(modal_reprova_os).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
            $(btn).prop({ disabled: true }).text("Reprovando...");
            $(btn_fechar).prop({ disabled: true });
        },
        async: false,
        timeout: 10000
    }).fail(function(res) {
        $(modal_reprova_os).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao reprovar</div>");
        $(btn).prop({ disabled: false }).text("Reprovar");
        $(btn_fechar).prop({ disabled: false });
    }).done(function(res) {
        res = JSON.parse(res);
        if (res.erro) {
            $(modal_reprova_os).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.erro+"</div>");
            $(btn).prop({ disabled: false }).text("Reprovar");
        } else {
            $(modal_reprova_os).find("div.modal-body").prepend("<div class='alert alert-success' >Reprovada</div>");
            $(btn).text("Reprovado");
            var quantidade_pendente = 0;
            var os_reprovada         = true;

            $("#"+modal_reprova_os_os).find("td.status-auditoria-os").each(function() {
                var pendente = $(this).find("span.label-info");

                if (pendente.length > 0) {
                    var btn_reprovacao_unica = $(this).find("button.reprova-os");

                    if (res.reprovacao_unica && res.reprovacao_unica != $(btn_reprovacao_unica).data("reprovacao-unica")) {
                        quantidade_pendente++;
                        return;
                    }

                    $(pendente).removeClass("label-info").addClass("label-important").text("Reprovado "+res.data);

                    $(pendente).closest("td").find("button").hide();

                    $(btn_reprovacao_unica).nextAll("button").remove();
                    $(btn_reprovacao_unica).prevAll("button").remove();

                    $(btn_reprovacao_unica).remove();
                    $(this).next("td").text(res.admin);
                    $(this).next("td").next("td").text(res.justificativa);
                }
            });

            if (res.reprovacao_unica && quantidade_pendente > 0) {
                os_reprovada = false;
            }

            if (os_reprovada) {
                
                var div_titulo = $("#"+res.os).find("div.os_auditoria_titulo");
                $(div_titulo).find("button.aprova-os, button.cancelar-os, button.reprova-os, button.trocar-produto").remove();
                $(div_titulo).prepend("<span class='label label-important pull-right'>Reprovado</span>");
            }

            console.log("se chegou aqui, tem que ter magia");
        }

        $(btn_fechar).prop({ disabled: false });
    });
});

$("#btn-close-modal-reprova-os").on("click", function() {
    $(modal_reprova_os).modal("hide");
});

$("button.trocar-produto").on("click", function() {
    var os = $(this).data("os");
    var troca_antecipada = $(this).data("trocaantecipada");
    var xfabrica = $(this).data("fabrica");
    if (xfabrica == 195) {

        Shadowbox.open({
            content: "<div style='background:#fff;'>\
                      <div style='text-align:center;padding:20px;font-weight:bold;'>O\ produto será trocado pelo mesmo Modelo?</div>\
                      <div style='text-align:center;padding:20px;'>\
                      <button type='button' onclick='trocaMesmoProduto("+os+","+troca_antecipada+")' class='btn'>Sim</button>\
                      <button type='button' onclick='window.open(\"os_troca_subconjunto.php?os="+os+"\");Shadowbox.close();' class='btn'>Não</button>\
                      </div></div>",
            player: "html",
            title:  "OS "+os,
            width:  500,
            height: 140
        });


        /*if (confirm(")) {
        alert(xfabrica)

        } else {
            window.open("os_troca_subconjunto.php?os="+os);
        }*/

    } else {
        window.open("os_troca_subconjunto.php?os="+os);
    }
});

<?php
if (in_array($login_fabrica, array(167,203))) {
?>
    $("select.status_auditoria").on("change", function() {
        var e = $(this);
        var os = $(this).data("os");
        var status = $(this).data("status");
        var novo_status = $(this).val();

        if (novo_status != status) {
            if (novo_status != null && novo_status.length > 0) {
                $.ajax({
                    url: window.location,
                    async: true,
                    timeout: 10000,
                    type: "post",
                    data: {
                        ajax_status: true,
                        os: os,
                        status: novo_status
                    },
                    beforeSend: function() {
                        $(e).parent().append("<span class='label label-info'>Gravando Status...</span>");
                    }
                }).fail(function(res) {
                    $(e).val(status).trigger("change");
                    $(e).nextAll("span.label").remove();
                    alert("Erro ao atualizar status, tempo limite esgotado");
                }).done(function(res) {
                    res = JSON.parse(res);

                    if (res.erro) {
                        $(e).val(status).trigger("change");
                        $(e).nextAll("span.label").remove();
                        alert(res.erro);
                    } else {
                        $(e).data("status", novo_status);
                        $(e).nextAll("span.label").removeClass("label-info").addClass("label-success").text("Status Gravado com sucesso");

                        setTimeout(function() {
                            $(e).nextAll("span.label").remove();
                        }, 3000);
                    }
                });
            } else {
                alert("Selecione um status");
                $(e).val(status).trigger("change");
            }
        }
    });
<?php
}
?>

function trocaMesmoProduto(os,troca_antecipada) {
    if (troca_antecipada == true){
        window.open("os_troca_subconjunto.php?troca_antecipada=true&os="+os);
    } else {
        window.open("os_troca_subconjunto.php?os="+os);
    }
    Shadowbox.close();
}
</script>

<?php

include "rodape.php";

?>
