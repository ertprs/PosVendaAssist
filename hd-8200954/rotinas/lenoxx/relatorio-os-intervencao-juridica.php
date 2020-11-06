<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . '/../funcoes.php';

$fabricas = array(
    11 => "lenoxx",
    172 => "pacific",
);

if (array_key_exists(1, $argv)) {
    $fabrica = $argv[1];
} else {
    $fabrica = 11;
}

if (!array_key_exists($fabrica, $fabricas)) {
    die("ERRO: argumento inválido - " . $fabrica . "\n");
}

$sql = "
    SELECT interv.os
        INTO TEMP tmp_interv1_{$fabrica}
        FROM (
            SELECT ultima.os,
            (
                SELECT status_os
                FROM tbl_os_status
                JOIN tbl_os USING(os)
                WHERE status_os IN (158,159,160)
                AND tbl_os_status.os = ultima.os
                AND tbl_os_status.fabrica_status = tbl_os.fabrica
                AND tbl_os.fabrica = {$fabrica}
                AND tbl_os_status.extrato IS NULL
                ORDER BY os_status DESC
                LIMIT 1
            ) AS ultimo_status
            FROM (
                SELECT DISTINCT os
                FROM tbl_os_status
                JOIN tbl_os USING(os)
                WHERE status_os IN (158,159,160)
                AND tbl_os_status.fabrica_status = tbl_os.fabrica
                AND tbl_os.fabrica = {$fabrica}
                ) ultima
            ) interv
        WHERE interv.ultimo_status IN (158, 159);

    CREATE INDEX tmp_interv_OS1_{$fabrica} ON tmp_interv1_{$fabrica}(os);

    SELECT tbl_os.os ,
            tbl_os.serie ,
            tbl_os.sua_os ,
            tbl_os.consumidor_nome ,
            tbl_os.consumidor_fone ,
            tbl_os.data_abertura AS ordena,
            TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
            TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
            TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
            tbl_os.nota_fiscal ,
            TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
            tbl_os.fabrica ,
            tbl_os.posto ,
            tbl_posto.nome AS posto_nome ,
            tbl_posto.estado AS posto_estado ,
            tbl_posto_fabrica.codigo_posto ,
            tbl_posto_fabrica.contato_email AS posto_email ,
            tbl_produto.referencia AS produto_referencia ,
            tbl_produto.descricao AS produto_descricao ,
            tbl_produto.voltagem ,
            tbl_os_extra.os_reincidente ,
            (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (158,159,160) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_os 
        INTO TEMP tmp_result_1
        FROM tmp_interv1_{$fabrica} X
        JOIN tbl_os ON tbl_os.os = X.os
        JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
        JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
        JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
        JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
        WHERE tbl_os.fabrica = {$fabrica}
        AND tbl_os_extra.extrato IS NULL
        GROUP BY tbl_os.os,
                tbl_os.serie ,
                tbl_os.sua_os ,
                tbl_os.consumidor_nome ,
                tbl_os.consumidor_fone ,
                data_abertura,
                data_fechamento,
                data_digitacao,
                tbl_os.nota_fiscal ,
                data_nf,
                tbl_os.fabrica ,
                tbl_os.posto ,
                posto_nome ,
                posto_estado,
                tbl_posto_fabrica.codigo_posto ,
                posto_email ,
                produto_referencia ,
                produto_descricao ,
                tbl_produto.voltagem ,
                tbl_os_extra.os_reincidente
        ORDER BY tbl_posto.nome, tbl_os.os;

        SELECT interv.os
            INTO TEMP tmp_interv2_{$fabrica}
            FROM (
                SELECT ultima.os,
                    (
                        SELECT status_os
                        FROM tbl_os_status
                        JOIN tbl_os_excluida USING(os)
                        WHERE status_os IN (158,159,160)
                        AND tbl_os_status.os = ultima.os
                        AND tbl_os_status.fabrica_status = tbl_os_excluida.fabrica
                        AND tbl_os_excluida.fabrica = 11
                        AND tbl_os_status.extrato IS NULL
                        ORDER BY os_status DESC
                        LIMIT 1
                    ) AS ultimo_status
            FROM (
                    SELECT DISTINCT os
                    FROM tbl_os_status
                    JOIN tbl_os_excluida USING(os)
                    WHERE status_os IN (158,159,160)
                    AND tbl_os_status.fabrica_status = tbl_os_excluida.fabrica
                    AND tbl_os_excluida.fabrica = {$fabrica}
                ) ultima
            ) interv
            WHERE interv.ultimo_status IN (160);

            CREATE INDEX tmp_interv_OS2_{$fabrica} ON tmp_interv2_{$fabrica}(os);

            SELECT tbl_os_excluida.os ,
                    tbl_os_excluida.serie ,
                    tbl_os_excluida.sua_os ,
                    tbl_os_excluida.consumidor_nome ,
                    tbl_os_excluida.consumidor_fone ,
                     tbl_os_excluida.data_abertura AS ordena,
                    TO_CHAR(tbl_os_excluida.data_abertura,'DD/MM/YYYY') AS data_abertura,
                    TO_CHAR(tbl_os_excluida.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                    TO_CHAR(tbl_os_excluida.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
                    tbl_os_excluida.nota_fiscal ,
                    TO_CHAR(tbl_os_excluida.data_nf,'DD/MM/YYYY') AS data_nf,
                    tbl_os_excluida.fabrica ,
                    tbl_os_excluida.posto ,
                    tbl_posto.nome AS posto_nome ,
                    tbl_posto.estado AS posto_estado ,
                    tbl_posto_fabrica.codigo_posto ,
                    tbl_posto_fabrica.contato_email AS posto_email ,
                    tbl_produto.referencia AS produto_referencia ,
                    tbl_produto.descricao AS produto_descricao ,
                    tbl_produto.voltagem ,
                    tbl_os_extra.os_reincidente ,
                    (SELECT status_os FROM tbl_os_status WHERE tbl_os_excluida.os = tbl_os_status.os AND status_os IN (158,159,160) AND tbl_os_status.extrato IS NULL ORDER BY data DESC LIMIT 1) AS status_os
                INTO TEMP tmp_result_2
                FROM tmp_interv2_{$fabrica} X
                JOIN tbl_os_excluida ON tbl_os_excluida.os = X.os
                JOIN tbl_os_extra ON tbl_os_excluida.os = tbl_os_extra.os
                JOIN tbl_produto ON tbl_produto.produto = tbl_os_excluida.produto
                JOIN tbl_posto ON tbl_os_excluida.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_os_excluida.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
                WHERE tbl_os_excluida.fabrica = {$fabrica}
                AND tbl_os_extra.extrato IS NULL
                GROUP BY tbl_os_excluida.os,
                        tbl_os_excluida.serie ,
                        tbl_os_excluida.sua_os ,
                        tbl_os_excluida.consumidor_nome ,
                        tbl_os_excluida.consumidor_fone ,
                        data_abertura,
                        data_fechamento,
                        data_digitacao,
                        tbl_os_excluida.nota_fiscal ,
                        data_nf,
                        tbl_os_excluida.fabrica ,
                        tbl_os_excluida.posto ,
                        posto_nome ,
                        posto_estado,
                        tbl_posto_fabrica.codigo_posto ,
                        posto_email ,
                        produto_referencia ,
                        produto_descricao ,
                        tbl_produto.voltagem ,
                        tbl_os_extra.os_reincidente
                        ORDER BY tbl_posto.nome, tbl_os_excluida.os;

        SELECT * FROM tmp_result_1 UNION SELECT * FROM tmp_result_2 ORDER BY ordena DESC, posto_nome;
";

$qry = pg_query($con, $sql);

if (pg_num_rows($qry) > 0) {
    $data = date('Ymd');
    $destdir = '/tmp/' . $fabricas[$fabrica];
    system("mkdir -p {$destdir}");
    $filename = "relatorio_os_intev_juridica-{$data}.csv";
    $filepath = "{$destdir}/{$filename}";
    $handle = fopen("{$filepath}", "w");

    $head = 'OS;Série;Data Abertura;Posto;UF;Nota Fiscal;Consumidor;Produto';
    fwrite($handle, $head . "\n");

    while ($fetch = pg_fetch_assoc($qry)) {
        $write = $fetch['sua_os'] . ';' . $fetch['serie'] . ';' . $fetch['data_abertura'] . ';' . $fetch['posto_nome'] . ';' . $fetch['posto_estado'] . ';' . $fetch['nota_fiscal'] . ';' . $fetch['consumidor_nome'] . ';' . $fetch['produto_referencia'] . ' - ' . $fetch['produto_descricao'];
        fwrite($handle, utf8_encode($write) . "\n");
    }

    fclose($handle);

    $to = "jurídico@lenoxxsound.com.br";
    $subj = "Intervenção de OS Bloqueada";

    system("/usr/bin/uuencode {$filepath} {$filename} | mail -s \"{$subj}\" {$to}");

}
