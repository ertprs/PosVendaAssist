<?php
#exit("certeza?");
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$servico_ajuste      = 11203;
$servico_gera_pedido = 11201;
$servico_estoque     = 11202;

$arquivo = "entrada/pecas_os_mobile_nao_lancadas.csv";

$conteudo = file_get_contents($arquivo);
$linhas = explode("\n", $conteudo);

foreach ($linhas as $l) {
    list(
        $os_telecontrol,
        $os_kof,
        $termino_atendimento,
        $peca,
        $referencia,
        $qtde,
        $servico
    ) = explode(";", $l);

    echo "\n";
    echo "OS: {$os_telecontrol}\n";
    echo "OS KOF: {$os_kof}\n";
    echo "PEÇA: {$referencia}\n";
    echo "QTDE: {$qtde}\n";
    echo "SERVIÇO: {$servico}\n";
    echo "#####################\n";

    if (!empty($termino_atendimento)) {
        /*$sqlData = "SELECT TO_CHAR(data_digitacao, 'YYYY-MM-DD HH24:MI') AS data_digitacao FROM tbl_os WHERE fabrica = 158 AND os = {$os_telecontrol}";
        $qryData = pg_query($con, $sqlData);

        $resData = pg_fetch_assoc($qryData);
        $termino_atendimento = $resData["data_digitacao"];
    } else {*/
	/*    list($data, $hora)     = explode(" ", $termino_atendimento);
        list($dia, $mes, $ano) = explode("/", $data);

	$termino_atendimento = "{$ano}-{$mes}-{$dia} {$hora}";*/
    }

    #echo "$termino_atendimento\n";

    /*if (!empty($termino_atendimento) && strtotime($termino_atendimento) < strtotime("2016-12-17 00:00")) {
        echo "Ignorando registro, data anterior a 2016-12-17\n";
        continue;
    }*/

    $sqlOs = "
        SELECT op.os_produto, o.posto, o.data_fechamento, o.finalizada
        FROM tbl_os o 
        INNER JOIN tbl_os_produto op ON op.os = o.os
        WHERE o.os = {$os_telecontrol}
        AND o.fabrica = 158
    ";
    $qryOs = pg_query($con, $sqlOs);

    if (!pg_num_rows($qryOs)) {
        echo "Ignorando registro, OS não encontrada\n";
        continue;
    }

    $resOs = pg_fetch_assoc($qryOs);
    $resOs = (object) $resOs;

    $sqlOsItem = "
        SELECT os_item
        FROM tbl_os_item
        WHERE os_produto = {$resOs->os_produto}
        AND peca = {$peca}
    ";
    $qryOsItem = pg_query($con, $sqlOsItem);

    if (pg_num_rows($qryOsItem) > 0) {
        echo "Ignorando registro, peça já lançada na OS\n";
        continue;
    }

    if ($servico == "Ajuste") {
        $servico = $servico_ajuste;
    } else if (preg_match("/\(gera pedido\)/", $servico)) {
        $servico = $servico_gera_pedido;
    } else if (preg_match("/\(estoque\)/", $servico)) {
        $servico = $servico_estoque;
    }

    pg_query($con, "BEGIN");

    $updateOs = "
        UPDATE tbl_os SET data_fechamento = null, finalizada = null WHERE fabrica = 158 AND os = {$os_telecontrol}
    ";
    $qryUpdateOs = pg_query($con, $updateOs);

    switch ($servico) {
        case $servico_ajuste:
            $insPeca = "
                INSERT INTO tbl_os_item 
                (os_produto, peca, qtde, servico_realizado)
                VALUES 
                ({$resOs->os_produto}, {$peca}, {$qtde}, {$servico})
            ";
            $qryInsPeca = pg_query($con, $insPeca);

	    if (strlen(pg_last_error()) > 0) {
		pg_query($con, "ROLLBACK");
		echo "Erro ao gravar peça\n";
		continue;
            }
            break;
        
        case $servico_gera_pedido:
            $sqlEstoquePosto = "
                SELECT qtde FROM tbl_estoque_posto WHERE fabrica = 158 AND posto = {$resOs->posto} AND peca = {$peca}
            ";
            $qryEstoquePosto = pg_query($con, $sqlEstoquePosto);
            $resEstoquePosto = pg_fetch_assoc($qryEstoquePosto);
            
            if ($resEstoquePosto["qtde"] >= $qtde) {
                echo "Serviço alterado para estoque\n";
                $servico = $servico_estoque;
            }
            
            $insertPeca = "
                INSERT INTO tbl_os_item 
                (os_produto, peca, qtde, servico_realizado)
                VALUES 
                ({$resOs->os_produto}, {$peca}, {$qtde}, {$servico})
                RETURNING os_item
            ";
            $qryInsertPeca = pg_query($con, $insertPeca);

            if (strlen(pg_last_error()) > 0) {
                pg_query($con, "ROLLBACK");
                echo "Erro ao gravar peça\n";
                continue;
            } else {
                $os_item = pg_fetch_result($qryInsertPeca, 0, "os_item");
            }
            
            if ($servico == $servico_estoque) {
                $insertMovimentoEstoque = "
                    INSERT INTO tbl_estoque_posto_movimento
                    (fabrica, posto, peca, qtde_saida, os, os_item, obs)
                    VALUES
                    (158, {$resOs->posto}, {$peca}, {$qtde}, {$os_telecontrol}, {$os_item}, 'Peça utilizada na OS <strong>{$os_telecontrol}</strong>')
                ";
                $qryInsertMovimentoEstoque = pg_query($con, $insertMovimentoEstoque);
            
                if (strlen(pg_last_error()) > 0) {
                    pg_query($con, "ROLLBACK");
                    echo "Erro ao lançar movimentação no estoque\n";
                    continue;
		}

		echo "Lançou movimentação\n";

                $updateSaldoEstoque = "
                    UPDATE tbl_estoque_posto SET
                        qtde = qtde - {$qtde}
                    WHERE fabrica = 158
                    AND posto = {$resOs->posto}
                    AND peca = {$peca}
                ";
                $qryUpdateSaldoEstoque = pg_query($con, $updateSaldoEstoque);

                if (strlen(pg_last_error()) > 0) {
                    pg_query($con, "ROLLBACK");
                    echo "Erro ao atualizar saldo do estoque\n";
                    continue;
		}

		echo "Atualizou estoque\n";
            }

            break;

        case $servico_estoque:
            $sqlEstoquePosto = "
                SELECT qtde FROM tbl_estoque_posto WHERE fabrica = 158 AND posto = {$resOs->posto} AND peca = {$peca}
            ";
            $qryEstoquePosto = pg_query($con, $sqlEstoquePosto);
            $resEstoquePosto = pg_fetch_assoc($qryEstoquePosto);
            
            if ($resEstoquePosto["qtde"] < $qtde) {
                echo "Serviço alterado para gera pedido\n";
                $servico = $servico_gera_pedido;
            }
            
            $insertPeca = "
                INSERT INTO tbl_os_item 
                (os_produto, peca, qtde, servico_realizado)
                VALUES 
                ({$resOs->os_produto}, {$peca}, {$qtde}, {$servico})
                RETURNING os_item
            ";
            $qryInsertPeca = pg_query($con, $insertPeca);

            if (strlen(pg_last_error()) > 0) {
                pg_query($con, "ROLLBACK");
                echo "Erro ao gravar peça\n";
                continue;
            } else {
                $os_item = pg_fetch_result($qryInsertPeca, 0, "os_item");
            }
            
            if ($servico == $servico_estoque) {
                $insertMovimentoEstoque = "
                    INSERT INTO tbl_estoque_posto_movimento
                    (fabrica, posto, peca, qtde_saida, os, os_item, obs)
                    VALUES
                    (158, {$resOs->posto}, {$peca}, {$qtde}, {$os_telecontrol}, {$os_item}, 'Peça utilizada na OS <strong>{$os_telecontrol}</strong>')
                ";
                $qryInsertMovimentoEstoque = pg_query($con, $insertMovimentoEstoque);
            
                if (strlen(pg_last_error()) > 0) {
                    pg_query($con, "ROLLBACK");
                    echo "Erro ao lançar movimentação no estoque\n";
                    continue;
		}

		echo "Lançou movimentação\n";

                $updateSaldoEstoque = "
                    UPDATE tbl_estoque_posto SET
                        qtde = qtde - {$qtde}
                    WHERE fabrica = 158
                    AND posto = {$resOs->posto}
                    AND peca = {$peca}
                ";
                $qryUpdateSaldoEstoque = pg_query($con, $updateSaldoEstoque);

                if (strlen(pg_last_error()) > 0) {
                    pg_query($con, "ROLLBACK");
                    echo "Erro ao atualizar saldo do estoque\n";
                    continue;
		}

		echo "Atualizou estoque\n";
	    }

            break;
    }

    $updateOs = "UPDATE tbl_os SET data_fechamento = '{$resOs->data_fechamento}', finalizada = '{$resOs->finalizada}' WHERE fabrica = 158 AND os = {$os_telecontrol}";
    $qryUpdateOs = pg_query($con, $updateOs);

    if (strlen(pg_last_error()) > 0) {
	    pg_query($con, "ROLLBACK");
	    echo "Erro ao atualizar datas da OS\n";
	    continue;
    }

    pg_query($con, "COMMIT");
    echo "Peça gravada com sucesso\n";
    continue;
}
