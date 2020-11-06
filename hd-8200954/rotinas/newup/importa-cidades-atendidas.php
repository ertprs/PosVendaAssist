<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 201;
$fabrica_nome = 'newup';

$diretorio_origem = '/home/newup/newup-telecontrol';
$arquivo_origem = 'telecontrol-cidades-atendidas.txt';

date_default_timezone_set('America/Sao_Paulo');
$now = date('Ymd_His');

$log_dir = '/tmp/' . $fabrica_nome .'/logs';
$arq_log = $log_dir . '/importa-cidades-atendidas-' . $now . '.log';
$err_log = $log_dir . '/importa-cidades-atendidas-err-' . $now . '.log';
$log = true;

if (!is_dir($log_dir)) {
    if (!mkdir($log_dir, 0777, true)) {
        echo "ERRO: Não foi possível criar logs: diretório $log_dir não pôde ser criado.\n";
        $log = false;
    }
}

$arquivo = $diretorio_origem . '/' . $arquivo_origem;

if (file_exists($arquivo) and (filesize($arquivo) > 0)) {
    $conteudo = file_get_contents($arquivo);
    $conteudo = explode("\n", $conteudo);

    $nlog = null;
    $elog = null;

    if (true === $log) {
        $nlog = fopen($arq_log, "w");
        $elog = fopen($err_log, "w");
    }
    
    foreach ($conteudo as $linha) {
        if (!empty($linha)) {
            list($cnpj, $cidade, $estado) = explode("\t", $linha);

            $cnpj = trim($cnpj);
            $cidade = trim($cidade);
            $estado = trim($estado);

            $original = array(
                $cnpj,
                $cidade,
                $estado
            );

            $sql = "SELECT posto FROM tbl_posto
                JOIN tbl_posto_fabrica USING(posto)
                WHERE cnpj = '$cnpj'
                AND fabrica = $fabrica";
            $qry = pg_query($con, $sql);

            if (pg_num_rows($qry) == 0) {
                if ($elog) {
                    array_push($original, 'Posto não encontrado');
                    fwrite($elog, implode(";", $original) . "\n");
                } else {
                    echo "ERRO: Posto $cnpj não encontrado.\n";
                }

                continue;
            }

            $posto = pg_fetch_result($qry, 0, 'posto');
			$cidade = str_replace("'","",$cidade);
            $sql = "SELECT cidade, cod_ibge
                FROM tbl_cidade
                WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))
                AND UPPER(estado) = UPPER('{$estado}')";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $cidade_id = pg_fetch_result($res, 0, "cidade");
                $cod_ibge = pg_fetch_result($res,0,"cod_ibge");
            } else {
                $sql = "SELECT cidade,
                        estado,
                        cod_ibge
                    FROM tbl_ibge
                    WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}'))
                    AND UPPER(estado) = UPPER('{$estado}')";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $cidade = pg_fetch_result($res, 0, "cidade");
                    $estado = pg_fetch_result($res, 0, "estado");
                    $cod_ibge = pg_fetch_result($res, 0, "cod_ibge");

                    $sql = "INSERT INTO tbl_cidade (
                                nome, estado, cod_ibge
                            ) VALUES (
                                '{$cidade}', '{$estado}', {$cod_ibge}
                            ) RETURNING cidade";
                    $res = pg_query($con, $sql);

                    $cidade_id = pg_fetch_result($res, 0, "cidade");
                } else {
                    if ($elog) {
                        array_push($original, 'Cidade não encontrada');
                        fwrite($elog, implode(";", $original) . "\n");
                    } else {
                        echo "ERRO: Cidade $cidade não encontrada.\n";
                    }

                    continue;
                }
            }

            $sql = "SELECT posto_fabrica_ibge
                FROM tbl_posto_fabrica_ibge
                WHERE fabrica = $fabrica
                AND posto = $posto
                AND cidade = $cidade_id";
			$res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                if ($elog) {
                    array_push($original, 'Cidade já cadastrada');
                    fwrite($elog, implode(";", $original) . "\n");
                } else {
                    echo "ERRO: Cidade $cidade já cadastrada.\n";
                }

                continue;
            }

            if (empty($cod_ibge)) {
               $cod_ibge = 0; 
            }

            $sql = "INSERT INTO tbl_posto_fabrica_ibge (
                        posto,
                        fabrica,
                        cidade,
                        cod_ibge,
                        posto_fabrica_ibge_tipo
                    ) VALUES (
                        {$posto},
                        {$fabrica},
                        {$cidade_id},
                        {$cod_ibge},
                        4)";
            $res = pg_query($con, $sql);

            if (!pg_last_error()) {
                if ($nlog) {
                    array_push($original, 'ok');
                    fwrite($nlog, implode(";", $original) . "\n");
                } else {
                    echo "LOG: " . implode(";", $original) . "\n";
                }
            }
        }
    }
}

if (file_exists($arq_log) and (filesize($arq_log) == 0)) {
    unlink($arq_log);
}

if (file_exists($err_log) and (filesize($err_log) == 0)) {
    unlink($err_log);
}

rename($arquivo, '/tmp/' . $fabrica_nome . '/cidades-atendidas-' . date('Ymd') . '.txt');

