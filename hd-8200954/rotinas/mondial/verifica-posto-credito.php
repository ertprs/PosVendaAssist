<?php

$erros = array();

try {

    include dirname(__FILE__) . "/../../dbconfig.php";
    include dirname(__FILE__) . "/../../includes/dbconnect-inc.php";
    include dirname(__FILE__) . "/../funcoes.php";
    include dirname(__FILE__) . "/../../os_cadastro_unico/fabricas/151/classes/Participante.php";

    date_default_timezone_set("America/Sao_Paulo");

    $login_fabrica = 151;

    $phpCron = new PHPCron($login_fabrica, __FILE__);
    $phpCron->inicio();

    $participanteClass = new Participante();
    $logClass = new Log2();

    if ($_serverEnvironment == "production") {
        $logClass->adicionaEmail("rogerio.soares@mondialline.com.br");
        $logClass->adicionaEmail("jefferson.nogueira@mondialline.com.br");
        $logClass->adicionaEmail("arnaldo.furtado@mondialline.com.br");
    } else {
        $logClass->adicionaEmail("guilherme.curcio@telecontrol.com.br");
    }

    $sql = "
        SELECT
            pf.posto,
            p.cnpj,
            p.nome
        FROM tbl_posto_fabrica pf
        INNER JOIN tbl_posto p ON p.posto = pf.posto
        INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
        WHERE pf.fabrica = {$login_fabrica}
        AND pf.credenciamento = 'CREDENCIADO'
        AND tp.posto_interno IS NOT TRUE
    ";
    $qry = pg_query($con, $sql);

    if (pg_num_rows($qry) > 0) {
        while ($row = pg_fetch_object($qry)) {
            $dadosParticipante = array(
                "SdParmParticipante" => array(
                    "RelacionamentoCodigo"      => "AssistTecnica",
                    "ParticipanteTipoPessoa"    => (strlen($row->cnpj) > 11) ? "J" : "F",
                    "ParticipanteFilialCPFCNPJ" => $row->cnpj
                )
            );

            $retorno = true;
            $participante = $participanteClass->verificaParticipante($dadosParticipante, $retorno);

            if ($participante == false) {
                $erros[] = "Erro ao verificar o crédito do posto autorizado {$row->nome} - {$row->cnpj}";
            } else {
                $limite_credito = $participante["SdSaiParticipante"]["ParticipanteLimiteCreditoDispValor"];
                $inadimplencia  = strtoupper($participante["SdSaiParticipante"]["ParticipanteStatusInadimplencia"]);

                if ($limite_credito == 0 || $inadimplencia == "S") {
                    $update = "
                        UPDATE tbl_posto_fabrica SET
                            pedido_faturado = FALSE
                        WHERE fabrica = {$login_fabrica}
                        AND posto = {$row->posto}
                    ";
                } else {
                    $update = "
                        UPDATE tbl_posto_fabrica SET
                            pedido_faturado = TRUE
                        WHERE fabrica = {$login_fabrica}
                        AND posto = {$row->posto}
                    ";
                }
                $qryUpdate = pg_query($con, $update);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao atualizar habilitar/desabilitar pedido do posto {$row->cnpj} - {$row->nome}");
                }
            }
        }
    }

    if (count($erros) > 0) {
        $logClass->adicionaLog(array("titulo" => "Telecontrol - Erro na verificação de crédito dos postos autorizados"));

        $fp = fopen("/tmp/mondial/verifica-posto-credito-erro".date("dmYHi").".txt", "w");

        $erro = "Ocorreram erros aos verificar o crédito dos postos autorizados - ".date("d/m/Y")."\n\n".implode("\n", $erros);

        fwrite($fp, $erro);
        fclose($fp);

        $logClass->adicionaLog($erro);
        $logClass->enviaEmails();
    }

    $phpCron->termino();
} catch(Exception $e) {
    $erros[] = $e->getMessage();

    $logClass->adicionaLog(array("titulo" => "Telecontrol - Erro na verificação de crédito dos postos autorizados"));

    $fp = fopen("/tmp/mondial/verifica-posto-credito-erro".date("dmYHi").".txt", "w");

    $erro = "Ocorreram erros aos verificar o crédito dos postos autorizados - ".date("d/m/Y")."\n\n".implode("\n", $erros);

    fwrite($fp, $erro);
    fclose($fp);

    $logClass->adicionaLog($erro);
    $logClass->enviaEmails();

    $phpCron->termino();
}