<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

echo "\n";
echo "Iniciando ".date("Y-m-d H:i")."\n";

pg_query($con, "
    CREATE TEMP TABLE tmp_imbera_saldo_sap (
        peca_id int,
        peca_referencia text,
        qtde int,
        qtde_usada int,
        saldo int,
        posto_id int,
        posto_codigo text,
        nota_fiscal text
    );
");
echo "Tabela temporária criada\n";

echo "Iniciando processamento do arquivo...\n";

$arquivo  = "entrada/sap_saldo.csv";
$conteudo = file_get_contents($arquivo);
$linhas   = explode("\n", $conteudo);

echo count($linhas)." peças\n";

echo "Processando...";
foreach ($linhas as $linha) {
    list($peca, $qtde, $qtde_usada, $saldo, $posto, $nota_fiscal) = explode(";", $linha);

    $qtde = str_replace(",", ".", $qtde);
    $qtde = (int) $qtde;

    $qtde_usada = str_replace(",", ".", $qtde_usada);
    $qtde_usada = (int) $qtde_usada;

    $saldo = str_replace(",", ".", $saldo);
    $saldo = (int) $saldo;

    pg_query($con, "
        INSERT INTO tmp_imbera_saldo_sap 
        (peca_referencia, qtde, qtde_usada, saldo, posto_codigo, nota_fiscal)
        VALUES
        ('{$peca}', {$qtde}, {$qtde_usada}, {$saldo}, '{$posto}', '{$nota_fiscal}');
    ");

    echo ".";
}
echo "\n";
echo "Arquivo processado\n";

echo "Iniciando atualização das peças...\n";

pg_query($con, "
    UPDATE tmp_imbera_saldo_sap SET
        peca_id = tbl_peca.peca
    FROM tbl_peca
    WHERE tbl_peca.fabrica = 158
    AND tmp_imbera_saldo_sap.peca_referencia = tbl_peca.referencia;
");

echo "Atualização de peças finalizadas\n";

echo "Procurando por registros que não foi possível encontrar a peça...\n";

$res = pg_query($con, "
    SELECT * FROM tmp_imbera_saldo_sap WHERE peca_id IS NULL;
");

echo "Registros sem peça: ".count(pg_fetch_all($res))."\n";

echo "Iniciando atualização dos postos...\n";

pg_query($con, "
    UPDATE tmp_imbera_saldo_sap SET
        posto_id = tbl_posto_fabrica.posto
    FROM tbl_posto_fabrica
    WHERE tbl_posto_fabrica.fabrica = 158
    AND tmp_imbera_saldo_sap.posto_codigo = tbl_posto_fabrica.codigo_posto;
");

ob_get_contents()

echo "Atualização de postos finalizadas\n";

echo "Procurando por registros que não foi possível encontrar o posto...\n";

$res = pg_query($con, "
    SELECT * FROM tmp_imbera_saldo_sap WHERE posto_id IS NULL;
");

echo "Registros sem posto: ".count(pg_fetch_all($res))."\n";

echo "Deletando registros sem peça ou posto...\n";

$delete = pg_query($con, "
    DELETE FROM tmp_imbera_saldo_sap WHERE peca_id IS NULL OR posto_id IS NULL;
");

echo pg_affected_rows($delete)." registros deletados\n";

echo "Iniciando atualização do saldo...\n";

$estoque = pg_query($con, "
    SELECT DISTINCT posto_id, peca_id, peca_referencia, posto_codigo
    FROM tmp_imbera_saldo_sap;
");

while ($e = pg_fetch_object($estoque)) {
    try {
        pg_query($con, "BEGIN");

        echo "\n";
        echo "Peça: {$e->peca_referencia}\n";
        echo "Posto: {$e->posto_codigo}\n";
        echo "\n";

        echo "Buscando nota fiscal mais antiga com saldo...\n";

        $qryNfAntigaSaldo = pg_query($con, "
            SELECT MIN(nota_fiscal::integer) AS nf
            FROM tmp_imbera_saldo_sap
            WHERE peca_id = {$e->peca_id}
            AND posto_id = {$e->posto_id}
            AND saldo > 0;
        ");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro interno");
        }

        if (!pg_num_rows($qryNfAntigaSaldo)) {
            echo "Nenhuma nota fiscal encontrada\n";
            echo "Marcando todo o estoque como devolvido...";

            pg_query($con, "
                UPDATE tbl_estoque_posto_movimento SET
                    qtde_usada = qtde_usada
                WHERE fabrica = 158
                AND posto = {$e->posto_id}
                AND peca = {$e->peca_id}
                AND qtde_entrada IS NOT NULL;
            ");

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro interno");
            }

            echo "Finalizado\n";
        } else {
            $nf_antiga_saldo = (int) pg_fetch_result($qryNfAntigaSaldo, 0, "nf");

            echo "Nota Fiscal mais antiga com saldo: {$nf_antiga_saldo}\n";
            echo "Matando o saldo de todas as notas anteriores a {$nf_antiga_saldo}...";

            pg_query($con, "
                UPDATE tbl_estoque_posto_movimento SET
                    qtde_usada = qtde_entrada
                WHERE fabrica = 158
                AND posto = {$e->posto_id}
                AND peca = {$e->peca_id}
                AND qtde_entrada IS NOT NULL
                AND nf::integer < {$nf_antiga_saldo};
            ");

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro interno");
            }

            echo "Finalizado\n";

            echo "Atualizando saldo fiscal do estoque...";

            $qryNfSaldo = pg_query($con, "
                SELECT *
                FROM tmp_imbera_saldo_sap
                WHERE posto_id = {$e->posto_id}
                AND peca_id = {$e->peca_id}
                AND nota_fiscal::integer >= {$nf_antiga_saldo}
                ORDER BY nota_fiscal::integer ASC;
            ");

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro interno");
            }

            foreach (pg_fetch_all($qryNfSaldo) as $nf) {
                $nf = (object) $nf;

                if ($nf->saldo > 0) {
                    $update = pg_query($con, "
                        UPDATE tbl_estoque_posto_movimento SET
                            qtde_usada = qtde_entrada - {$nf->saldo}
                        WHERE fabrica = 158
                        AND posto = {$nf->posto_id}
                        AND peca = {$nf->peca_id}
                        AND nf::integer = {$nf->nota_fiscal}::integer
                        AND qtde_entrada IS NOT NULL;
                    ");
                } else {
                    $update = pg_query($con, "
                        UPDATE tbl_estoque_posto_movimento SET
                            qtde_usada = qtde_entrada
                        WHERE fabrica = 158
                        AND posto = {$nf->posto_id}
                        AND peca = {$nf->peca_id}
                        AND nf::integer = {$nf->nota_fiscal}::integer
                        AND qtde_entrada IS NOT NULL;
                    ");
                }

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro interno");
                }

                if (pg_affected_rows($update) == 0) {
                    throw new Exception("Nota Fiscal {$nf->nota_fiscal} não encontrada");
                }
            }

            echo "Finalizado\n";
        }

        echo "Procurando pedidos de peças aguardando faturamento...";

        $qryPedidosPendentes = pg_query($con, "
            SELECT p.pedido, pi.pedido_item, (pi.qtde - (pi.qtde_faturada + pi.qtde_cancelada)) AS qtde
            FROM tbl_pedido_item pi
            INNER JOIN tbl_pedido p ON p.pedido = pi.pedido AND p.fabrica = 158
            INNER JOIN tbl_tipo_pedido tp ON tp.tipo_pedido = p.tipo_pedido AND tp.fabrica = 158
            LEFT JOIN tbl_faturamento_item fi ON fi.pedido_item = pi.pedido_item
            WHERE tp.codigo = 'BON'
            AND p.status_pedido IN(2, 5)
            AND p.exportado IS NOT NULL
            AND p.posto = {$e->posto_id}
            AND pi.peca = {$e->peca_id}
            AND fi.faturamento_item IS NULL
            AND (pi.qtde_cancelada + pi.qtde_faturada) < pi.qtde
            ORDER BY p.data ASC;
        ");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro interno");
        }

        if (!pg_num_rows($qryPedidosPendentes)) {
            echo "Nenhum pedido de peça encontrado\n";
        } else {
            $pedidos = pg_fetch_all($qryPedidosPendentes);

            echo count($pedidos)." pedidos encontrados\n";

            echo "Abatendo saldo do estoque de acordo com os pedidos...\n";

            foreach ($pedidos as $pedido) {
                $qtde = $pedido["qtde"];

                while ($qtde > 0) {
                    $qrySaldoEstoque = pg_query($con, "
                        SELECT qtde, COALESCE(qtde_usada, 0) AS qtde_usada, faturamento, nf::integer
                        FROM tbl_estoque_posto_movimento
                        WHERE fabrica = 158
                        AND posto = {$e->posto_id}
                        AND peca = {$e->peca_id}
                        AND COALESCE(qtde_usada, 0) < qtde
                        AND qtde_entrada IS NOT NULL
                        ORDER BY nf::integer ASC;
                    ");

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro interno");
                    }

                    if (!pg_num_rows($qrySaldoEstoque)) {
                        throw new Exception("Não foi encontrado nota fiscal para abater o saldo\n");
                    }

                    $saldo_estoque = pg_fetch_assoc($qrySaldoEstoque);

                    if ($saldo_estoque["qtde_usada"] < $qtde) {
                        $qtde_update = $qtde - $saldo_estoque["qtde_usada"];
                    } else {
                        $qtde_update = $qtde;
                    }

                    $qryUpdateSaldoEstoque = pg_query($con, "
                        UPDATE tbl_estoque_posto_movimento SET
                            qtde_usada = COALESCE(qtde_usada, 0) + {$qtde_update}
                        WHERE fabrica = 158
                        AND posto = {$e->posto_id}
                        AND peca = {$e->peca_id}
                        AND faturamento = {$saldo_estoque['faturamento']}
                        AND nf::integer = {$saldo_estoque['nf']};
                    ");

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro interno");
                    }

                    $qtde -= $qtde_update;
                }
            }

            echo "Finalizado\n";
        }

        echo "Saldo atualizado!\n";

        pg_query($con, "ROLLBACK");
    } catch(Exception $e) {
        pg_query($con, "ROLLBACK");

        echo "\n";
        echo "Erro ao atualizar saldo: ".$e->getMessage()."\n";
    }

    echo "#############################\n";
}