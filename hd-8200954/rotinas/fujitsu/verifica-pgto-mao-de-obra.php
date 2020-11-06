<?php
/**
**  Verifica pagamento de mão-de-obra
**/
error_reporting(E_ALL ^ E_NOTICE);

define('ENV', 'producao');

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    // fujitsu
    $fabrica = 138;
    $fabrica_nome = 'fujitsu';

    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    if(ENV == 'teste'){
        $log_dir = dirname(__FILE__) . '/fujitsu_teste/logs';;
        $arq_log = $log_dir . '/relatorio-pgto-mao-de-obra-' . $now . '.log';
        $err_log = $log_dir . '/relatorio-pgto-mao-de-obra-err-' . $now . '.log';
    }else{
        $log_dir = '/tmp/' . $fabrica_nome;
        $arq_log = $log_dir . '/relatorio-pgto-mao-de-obra-' . $now . '.log';
        $err_log = $log_dir . '/relatorio-pgto-mao-de-obra-err-' . $now . '.log';
    }

    if (!is_dir($log_dir)) {
        if (!mkdir($log_dir, 0777, true)) {
            throw new Exception("ERRO: Não foi possível criar logs. Falha ao criar diretório: $log_dir");
        }
    }

    $sql = "SELECT tbl_os.os
                FROM tbl_os
                JOIN tbl_os_extra USING(os)
                JOIN tbl_resposta ON tbl_resposta.os = tbl_os.os
                WHERE tbl_os.fabrica = {$fabrica}
                AND tbl_os_extra.admin_paga_mao_de_obra IS FALSE
                AND tbl_os_extra.extrato IS NULL
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.cancelada IS NOT TRUE
                AND tbl_os.finalizada IS NOT NULL";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $contadorRes = pg_num_rows($res);

        for($x=0; $x<$contadorRes; $x++){

            $os = pg_fetch_result($res, $x, os);

            // Altera Mão-de-Obra
            pg_query($con,"BEGIN TRANSACTION");

            $sqlAtualiza = "UPDATE tbl_os_extra SET admin_paga_mao_de_obra = TRUE WHERE os = {$os}";
            $resAtualiza = pg_query($con, $sqlAtualiza);

            $sqladdmsgos = "UPDATE tbl_os SET obs = 'Pagamento de Mão de Obra liberado, pois o consumidor respondeu a pesquisa de satisfação' WHERE os = {$os}";
            $resaddmsgos = pg_query($con, $sqladdmsgos);

            if (pg_last_error($con)) {
                pg_query($con,"ROLLBACK TRANSACTION");
                return false;
            } else {
                pg_query($con,"COMMIT TRANSACTION");         
                //return true;            
            }
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
