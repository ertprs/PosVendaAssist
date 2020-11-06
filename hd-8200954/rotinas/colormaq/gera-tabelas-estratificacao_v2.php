<?php
/**
 * gera-tabelas-estratificacao_v2.php
 *
 * Gera as tabelas usadas no Relatório de Estratificação v201505
 *
 */


include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 50;

// $sqlFamilias = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$fabrica} and ativo";
// $qryFamilias = pg_query($con, $sqlFamilias);
//
// while ($fetch = pg_fetch_assoc($qryFamilias)) {
//     $familia = $fetch['familia'];
//     $descricao = $fetch['descricao'];
//     $suffix = $fabrica . '_' . $familia;

$erro='OK';

$sqlOSSerie = "SELECT fabrica,os,
                                    data_abertura,
                                    to_char(data_abertura,'YYYY-MM') AS data_mes_ano,
                                    serie,
                                    tbl_os.produto,
                                    defeito_constatado,
                                    posto,
                                    tbl_os.revenda_cnpj AS cnpj,
                                    familia
                        INTO colormaq_os_serie_novo
                        FROM tbl_os
                            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                                AND tbl_os.fabrica = tbl_produto.fabrica_i
		WHERE tbl_os.fabrica = $fabrica
                            AND excluida IS NOT TRUE
                            AND (data_abertura >= current_timestamp - interval '60 months'); ";

$qry = pg_query($con, $sqlOSSerie);

if (!pg_last_error($con)) {
    $msg = '1 TABELA foi atualizada com sucesso';
} else {
    $msg = '1 TABELA não foi atualizada: ' . pg_last_error($con);
    $erro='ERRO';
}
	

if ($erro=='OK') {
    $sqlNS1 = "SELECT  tbl_numero_serie.numero_serie,
                                    tbl_numero_serie.serie,
                                    tbl_numero_serie.produto,
                                    tbl_numero_serie.data_fabricacao,
                                    tbl_produto.familia
                        INTO colormaq_numero_serie1_novo
                        FROM tbl_produto
                        JOIN tbl_numero_serie ON tbl_numero_serie.produto    = tbl_produto.produto AND tbl_numero_serie.fabrica    = tbl_produto.fabrica_i
                        WHERE tbl_produto.fabrica_i    = $fabrica
                        AND tbl_produto.produto IN (select produto from colormaq_os_serie_novo where colormaq_os_serie_novo.fabrica = $fabrica);";

	$qry = pg_query($con, $sqlNS1);

    if (!pg_last_error($con)) {
        $msg = '2 TABELA foi atualizada com sucesso';
    } else {
        $msg = '2 TABELA não foi atualizada: ' . pg_last_error($con);
        $erro='ERRO';
    }

    $sqlChkNS2 = "SELECT * FROM colormaq_numero_serie2 LIMIT 1";
	$qryChkNS2 = pg_query($con, $sqlChkNS2);

	if (pg_last_error($con)) {
		// Cria a colormaq_numero_serie2 caso não exista
		$sqlNS2 = "SELECT  tbl_numero_serie.numero_serie,
										tbl_numero_serie.serie,
										tbl_numero_serie.produto,
										tbl_numero_serie.data_fabricacao,
										tbl_produto.familia
							INTO colormaq_numero_serie2
							FROM tbl_produto
							JOIN tbl_numero_serie ON tbl_numero_serie.produto = tbl_produto.produto AND tbl_numero_serie.fabrica    = tbl_produto.fabrica_i
							WHERE tbl_produto.fabrica_i = $fabrica
							AND tbl_produto.produto IN (select produto from colormaq_os_serie where colormaq_os_serie.fabrica = $fabrica)
							AND data_fabricacao between '2013-07-25' and '2013-09-13'";
		$qry = pg_query($con, $sqlNS2);

		$sqlindex="CREATE INDEX idx_ns2ns ON colormaq_numero_serie2 (numero_serie);
							CREATE INDEX idx_ns2s ON colormaq_numero_serie2 (serie);
							CREATE INDEX idx_ns2p ON colormaq_numero_serie2 (produto);
							CREATE INDEX idx_ns2df ON colormaq_numero_serie2 (data_fabricacao);
							CREATE INDEX idx_ns2fa ON colormaq_numero_serie2 (familia); ";
		$qry = pg_query($con, $sqlindex);
	}

}


if ($erro=='OK') {
    $sqlNS = "SELECT    numero_serie,
                                    serie,
                                    produto,
                                    data_fabricacao,
                                    to_char(data_fabricacao,'YYYY-MM') AS data_mes_ano,
                                    familia
                    INTO colormaq_numero_serie_novo
                    FROM (select * from colormaq_numero_serie1_novo UNION select * from colormaq_numero_serie2 ) x;
                    ";

	    #$qry = pg_query($con, $sqlOSSerie . $sqlNS1 . $sqlNS2 . $sqlNS);
    $qry = pg_query($con, $sqlNS);

    if (!pg_last_error($con)) {
        $msg = '4 TABELA foi atualizada com sucesso';
    } else {
        $msg = '4 TABELA não foi atualizada: ' . pg_last_error($con);
        $erro='ERRO';
    }
}


if ($erro=='OK') {
    $drops = "DROP TABLE IF EXISTS colormaq_os_serie;
              DROP TABLE IF EXISTS colormaq_numero_serie1;
              DROP TABLE IF EXISTS colormaq_numero_serie; ";
$qry = pg_query($con, $drops);

if (!pg_last_error($con)) {
    $alter = "ALTER TABLE colormaq_os_serie_novo RENAME TO colormaq_os_serie;
              ALTER TABLE colormaq_numero_serie1_novo RENAME TO colormaq_numero_serie1;
              ALTER TABLE colormaq_numero_serie_novo RENAME TO colormaq_numero_serie; ";
        
    $qry = pg_query($con, $alter);

    $index = "  CREATE INDEX idx_ossda ON colormaq_os_serie (data_abertura);
                CREATE INDEX idx_osss ON colormaq_os_serie (serie);
                CREATE INDEX idx_osspr ON colormaq_os_serie (produto);
                CREATE INDEX idx_ossd ON colormaq_os_serie (defeito_constatado);
                CREATE INDEX idx_osspo ON colormaq_os_serie (posto);
                CREATE INDEX idx_ossre ON colormaq_os_serie (cnpj);
                CREATE INDEX idx_osfabrica ON colormaq_os_serie (fabrica);
                CREATE INDEX idx_ossfa ON colormaq_os_serie (familia);
                CREATE INDEX idx_ns1ns ON colormaq_numero_serie1 (numero_serie);
                CREATE INDEX idx_ns1s ON colormaq_numero_serie1 (serie);
                CREATE INDEX idx_ns1p ON colormaq_numero_serie1 (produto);
                CREATE INDEX idx_ns1df ON colormaq_numero_serie1 (data_fabricacao);
                CREATE INDEX idx_ns1fa ON colormaq_numero_serie1 (familia);
                CREATE INDEX idx_nsns ON colormaq_numero_serie (numero_serie);
                CREATE INDEX idx_nss ON colormaq_numero_serie (serie);
                CREATE INDEX idx_nsp ON colormaq_numero_serie (produto);
                CREATE INDEX idx_nsdf ON colormaq_numero_serie (data_fabricacao);
                CREATE INDEX idx_nsfa ON colormaq_numero_serie (familia); ";

    $qry = pg_query($con, $index);
    if (!pg_last_error($con)) {
            $to = 'estratificacao@colormaq.com.br, alex.gallardo@colormaq.com.br';
            $subj = '[Colormaq] ';
            $msg = 'A base BI foi atualizada com sucesso';
        }else{
            $msg = 'ERRO AO ALTERAR TABELA ' . pg_last_error($con);
            $to = 'estratificacao@colormaq.com.br, alex.gallardo@colormaq.com.br';
            $subj = '[ERRO Colormaq] ';
            $msg = $msg . ' - A base BI não foi atualizada: ' . pg_last_error($con);
        }

} else {
    $msg = 'ERRO AO DROPAR TABELA ' . pg_last_error($con);
    $to = 'estratificacao@colormaq.com.br, alex.gallardo@colormaq.com.br';
    $subj = '[ERRO Colormaq] ';
    $msg = $msg . ' - A base BI não foi atualizada: ' . pg_last_error($con);
}

} else {
    $to = 'estratificacao@colormaq.com.br, alex.gallardo@colormaq.com.br';
    $subj = '[ERRO Colormaq] ';
    $msg = $msg . ' - A base BI não foi atualizada: ' . pg_last_error($con);
}

mail($to, $subj, $msg);

echo $msg . "\n";

