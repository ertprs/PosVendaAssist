<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$posto_de_para = array(
    32174 => 151268,
    32461 => 118540,
    32519 => 18751,
    32602 => 356508,
    32619 => 420258,
    32662 => 360786,
    33104 => 119888,
    33801 => 443268,
    67070 => 443268,
    67310 => 22903,
    69313 => 373075
);

try{
    pg_query($con, "BEGIN");

    pg_query($con, "
        CREATE TEMP TABLE tmp_nf_itens (
            faturamento INTEGER,
            faturamento_item INTEGER,
            posto INTEGER,
            nf TEXT,
            peca INTEGER,
            peca_referencia TEXT,
            qtde INTEGER,
            preco DOUBLE PRECISION,
            base_icms DOUBLE PRECISION,
            valor_icms DOUBLE PRECISION,
            aliq_icms DOUBLE PRECISION,
            base_ipi DOUBLE PRECISION,
            valor_ipi DOUBLE PRECISION,
            aliq_ipi DOUBLE PRECISION
        )
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #1");
    }

    $arquivo = "entrada/faturamento_inicial.csv";

    $conteudo = file_get_contents($arquivo);
    $linhas = explode("\n", $conteudo);

    foreach ($linhas as $l) {
        list(
            $nf,
            $codigo_posto,
            $referencia_peca,
            $preco,
            $base_icms,
            $valor_icms,
            $p_icms,
            $base_ipi,
            $valor_ipi,
	    $p_ipi,
	    $qtde
        ) = explode(";", $l);

        if (empty($nf) || empty($referencia_peca)) {
            continue;
        }

        $nf              = str_replace("'", "", trim($nf));
        $codigo_posto    = str_replace("'", "", trim($codigo_posto));
        $referencia_peca = str_replace("'", "", trim($referencia_peca));
        $qtde            = (integer) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($qtde))));
        $preco           = (float) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($preco))));
        $base_icms       = (float) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($base_icms))));
        $valor_icms      = (float) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($valor_icms))));
        $p_icms          = (float) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($p_icms))));
        $base_ipi        = (float) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($base_ipi))));
        $valor_ipi       = (float) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($valor_ipi))));
        $p_ipi           = (float) str_replace(",", ".", str_replace(".", "", str_replace("'", "", trim($p_ipi))));

        $posto_id = $posto_de_para[$codigo_posto];

        pg_query($con, "
            INSERT INTO tmp_nf_itens
            (posto, nf, peca_referencia, qtde, preco, base_icms, valor_icms, aliq_icms, base_ipi, valor_ipi, aliq_ipi)
            VALUES
            ({$posto_id}, '{$nf}', '{$referencia_peca}', {$qtde}, {$preco}, {$base_icms}, {$valor_icms}, {$p_icms}, {$base_ipi}, {$valor_ipi}, {$p_ipi})
        ");

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro #2");
        }
    }

    echo "\n";

    pg_query($con, "
        UPDATE tmp_nf_itens SET
            peca = tbl_peca.peca
        FROM tbl_peca
        WHERE tbl_peca.fabrica = 158
        AND tbl_peca.referencia = tmp_nf_itens.peca_referencia
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #3");
    }

    $semPeca = pg_query($con, "
        SELECT COUNT(*) FROM tmp_nf_itens WHERE peca IS NULL
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #4");
    }

    echo "Sem Peça\n";
    print_r(pg_fetch_all($semPeca));

    pg_query($con, "
        DELETE FROM tmp_nf_itens WHERE peca IS NULL
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #5");
    }

    pg_query($con, "
        UPDATE tmp_nf_itens SET
            faturamento = tbl_faturamento.faturamento,
            faturamento_item = tbl_faturamento_item.faturamento_item
        FROM tbl_faturamento_item, tbl_faturamento
        WHERE tbl_faturamento.fabrica = 158
        AND tbl_faturamento.nota_fiscal::integer = tmp_nf_itens.nf::integer
	AND tbl_faturamento.posto = tmp_nf_itens.posto
	AND tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
        AND tbl_faturamento_item.peca = tmp_nf_itens.peca
        AND tbl_faturamento_item.qtde = tmp_nf_itens.qtde
        AND ((tbl_faturamento_item.preco = 0 OR tbl_faturamento_item.preco IS NULL) OR (tbl_faturamento_item.aliq_icms = 0 OR tbl_faturamento_item.aliq_icms IS NULL))
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #6");
    }

    $semFaturamento = pg_query($con, "
        SELECT COUNT(*) FROM tmp_nf_itens WHERE faturamento_item IS NULL
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #7");
    }

    echo "Sem Faturamento\n";
    print_r(pg_fetch_all($semFaturamento));

    pg_query($con, "
        DELETE FROM tmp_nf_itens WHERE faturamento_item IS NULL
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #8");
    }

    $semPrecoCsv = pg_query($con, "
        SELECT COUNT(*) FROM tmp_nf_itens
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #9");
    }

    echo "Sem Preço CSV\n";
    print_r(pg_fetch_all($semPrecoCsv));

    $semPreco = pg_query($con, "
        SELECT COUNT(*) 
        FROM tbl_faturamento f 
        INNER JOIN tbl_faturamento_item fi ON fi.faturamento = f.faturamento 
        INNER JOIN tbl_peca p ON p.peca = fi.peca AND p.fabrica = 158 
        INNER JOIN tbl_posto_fabrica pf ON pf.posto = f.posto AND pf.fabrica = 158 
        INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = 158 
        WHERE f.fabrica = 158 
	AND ((fi.preco = 0 OR fi.preco IS NULL) OR (fi.aliq_icms = 0 OR fi.aliq_icms IS NULL)) 
	AND fi.pedido IS NULL
        AND tp.tecnico_proprio IS NOT TRUE 
	AND tp.posto_interno IS NOT TRUE
    ");

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro #10");
    }

    echo "Sem Preço Banco de Dados\n";
    print_r(pg_fetch_all($semPreco));

    $nfFaltante = pg_query($con, "
	            SELECT f.nota_fiscal, p.referencia
	            FROM tbl_faturamento f
                    INNER JOIN tbl_faturamento_item fi ON fi.faturamento = f.faturamento
	            INNER JOIN tbl_peca p ON p.peca = fi.peca AND p.fabrica = 158
	            INNER JOIN tbl_posto_fabrica pf ON pf.posto = f.posto AND pf.fabrica = 158
	            INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = 158
	            WHERE f.fabrica = 158
	            AND ((fi.preco = 0 OR fi.preco IS NULL) OR (fi.aliq_icms = 0 OR fi.aliq_icms IS NULL))
	            AND fi.pedido IS NULL
	            AND tp.tecnico_proprio IS NOT TRUE
	            AND tp.posto_interno IS NOT TRUE
		    AND fi.faturamento_item NOT IN(SELECT faturamento_item FROM tmp_nf_itens)
    ");

    if (strlen(pg_last_error()) > 0) {
	    throw new Exception("Erro #11");
    }

    echo "Itens Faltantes\n";
    print_r(count(pg_fetch_all($nfFaltante)));
    echo "\n\n";

    $update = pg_query($con, "
	    UPDATE tbl_faturamento_item SET
	    	preco = tmp_nf_itens.preco,
		base_icms = tmp_nf_itens.base_icms,
		valor_icms = tmp_nf_itens.valor_icms,
		aliq_icms = tmp_nf_itens.aliq_icms,
		base_ipi = tmp_nf_itens.base_ipi,
		valor_ipi = tmp_nf_itens.valor_ipi,
		aliq_ipi = tmp_nf_itens.aliq_ipi
	    FROM tbl_faturamento, tmp_nf_itens
	    WHERE tbl_faturamento.fabrica = 158
	    AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
	    AND tbl_faturamento_item.faturamento = tmp_nf_itens.faturamento
	    AND tbl_faturamento_item.faturamento_item = tmp_nf_itens.faturamento_item
    ");

    echo "Itens Atualizados\n";
    print_r(pg_affected_rows($update));

    echo "\n";

    pg_query($con, "COMMIT");
} catch(Exception $e) {
    pg_query($con, "ROLLBACK");
    print_r($e->getMessage());
}
