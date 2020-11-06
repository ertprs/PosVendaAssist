<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$arquivo = "entrada/peca-2017-01-01.csv";

$conteudo = file_get_contents($arquivo);
$linhas = explode("\n", $conteudo);

$arquivo_peca_nao_lancada = fopen("persys_peca_nao_lancada_janeiro.csv", "w");

foreach ($linhas as $l) {
	$l = str_replace("\n", "", $l);
	$l = str_replace("\r", "", $l);

    list(
        $os,
	$data,
	$servico_codigo,
	$servico_nome,
	$peca_referencia,
	$peca_nome,
	$qtde
    ) = explode(";", $l);

    echo "\n";
    echo "OS: {$os}\n";
    echo "PECA: {$peca_referencia}\n";

    $resOs = pg_query($con, "SELECT oe.termino_atendimento, o.data_digitacao  FROM tbl_os o INNER JOIN tbl_os_extra oe ON oe.os = o.os WHERE o.os = {$os}");
    $finalizada = pg_fetch_result($resOs, 0, "termino_atendimento");
    $data = pg_fetch_result($resOs, 0, "data_digitacao");

    $resPeca = pg_query($con, "SELECT peca FROM tbl_peca WHERE fabrica = 158 AND referencia = '{$peca_referencia}'");
    $peca_id = pg_fetch_result($resPeca, 0, "peca");

    $resOsItem = pg_query($con, "
    	SELECT oi.os_item
        FROM tbl_os_item oi 
        INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
        WHERE op.os = {$os}
        AND oi.peca = {$peca_id}
    ");

    if (!pg_num_rows($resOsItem)) {
    	echo "Não lançada\n";
        $resOsMobile = pg_query($con, "SELECT dados FROM tbl_os_mobile WHERE os = {$os}");

        $recebida = false;

        foreach (pg_fetch_all($resOsMobile) as $row) {
            $json = json_decode($row["dados"], true);

            if (count($json["pecas"]) > 0) {
                foreach ($json["pecas"] as $json_peca) {
                    if ($json_peca["referencia"] == $peca_referencia) {
                        $recebida = true;
                        break;
                    }
                }
            }

            if ($recebida == true) {
                break;
            }
        }

        if ($recebida == true) {
            echo "Recebida\n";
        } else {
            echo "Não Recebida\n";
        }
            
        fwrite($arquivo_peca_nao_lancada, "{$os};{$servico_codigo};{$servico_nome};{$peca_referencia};{$peca_nome};{$peca_id};{$qtde};{$recebida};{$finalizada};{$data}\n");
    } else {
        echo "Lançada\n";
    }

    echo "################";
}

fclose($arquivo_peca_nao_lancada);
