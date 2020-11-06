<?php
/**
 *
 * gera-extrato.php
 *
 * Geração de Extrato
 *
 */

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim
#define('ENV','dev');
try {
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $fabricas = array(
        11 => "lenoxx",
        172 => "pacific",
    );

    if (array_key_exists(1, $argv)) {
        $data['fabrica'] = $argv[1];
    } else {
        $data['fabrica']      = 11;
    }

    if (!array_key_exists($data['fabrica'], $fabricas)) {
        die("ERRO: argumento inválido - " . $data['fabrica'] . "\n");
    }

    $data['fabrica_nome'] = $fabricas[$data['fabrica']];
    $data['arquivo_log']  = 'gera_pedido_os';
    $data['log']          = 2;
    $data['arquivos']     = "/tmp";
    $logs                 = array();
    $log_cliente          = array();
    $erro                 = false;

    $phpCron = new PHPCron($data['fabrica'], __FILE__);
    $phpCron->inicio();

    $data_sistema = Date('Y-m-d');

    if (ENV == 'producao' ) {
        $data['dest']          = 'helpdesk@telecontrol.com.br';
        $data['dest_cliente']  = 'erasmo@lenoxxsound.com.br';
    } else {
        $data['dest']          = 'manolo@telecontrol.com.br';
        $data['dest_cliente']  = 'manuel.lopez@telecontrol.com.br';
    }

    extract($data);

    $arquivo_log = "{$arquivos}/{$fabrica_nome}/{$data_sistema}_{$arquivo_log}.err";

    $fl          = fopen($arquivo_log,"w+");

    system ("mkdir {$arquivos}/{$fabrica_nome}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica_nome}/" );

    $sql = "SET DateStyle TO 'SQL,EUROPEAN'";
    $res = pg_query($con, $sql);
    if (pg_last_error($con)) {
        $logs[] = $sql;
        $logs[] = pg_last_error($con);
        $erro   = true;
    }

    $sql    = "SELECT posto , current_date as data_limite
        FROM tbl_posto_fabrica
        WHERE  tbl_posto_fabrica.fabrica = $fabrica
        AND ( credenciamento='CREDENCIADO'  OR credenciamento='EM DESCREDENCIAMENTO') ORDER BY posto";

    $resOs = pg_query($con, $sql);
    $arr   = pg_fetch_all($resOs);

    foreach ($arr as $item) {
        $posto = $item['posto'];
        $data_limite = $item['data_limite'];


        $condDiaGera = ((int)date('d') == 16) ? '' :
            "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto
                                   AND tbl_posto_fabrica.fabrica = $fabrica
                                   AND tbl_posto_fabrica.extrato_programado = CURRENT_DATE";

        $sql = "SELECT tbl_os.os,tbl_os.posto,
            (select status_os from tbl_os_status where tbl_os_status.fabrica_status=$fabrica and tbl_os_status.os = tbl_os.os and status_os in (64,67,68,70,19,13,139,155,15) order by data desc limit 1) as status_os
            INTO TEMP tmp_extrato_{$fabrica_nome}_{$posto}
            FROM tbl_os
            JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os and tbl_os_extra.i_fabrica=$fabrica
            $condDiaGera
            WHERE  tbl_os.posto    = $posto
            AND    tbl_os.fabrica  = $fabrica
            AND    tbl_os_extra.extrato    IS NULL
            AND    NOT (tbl_os.data_fechamento  IS NULL)
            AND    NOT (tbl_os.finalizada       IS NULL)
            AND    tbl_os.excluida IS NOT TRUE
			AND		tbl_os.cancelada is not true
            AND    tbl_os.data_fechamento  > current_date - interval '12 months'
            AND    tbl_os.finalizada::date < current_date ;

        SELECT DISTINCT posto
            FROM tmp_extrato_{$fabrica_nome}_{$posto}
            WHERE 1 = 1";

        $res = pg_query($con, $sql);

        $rows = pg_num_fields($res);;;
        if($rows > 0) {
            $res = pg_query($con, 'BEGIN TRANSACTION');

            $sql_extrato = "INSERT INTO tbl_extrato (fabrica, posto, mao_de_obra, pecas, total) VALUES ($fabrica, $posto, 0, 0, 0);
            SELECT CURRVAL ('seq_extrato') as extrato; ";
            $res_extrato = pg_query($con, $sql_extrato);

            $extrato = pg_fetch_result($res_extrato, 'extrato');
            if(strlen(pg_last_error($con))){
                throw new Exception('Erro ao gravar extrato');
            }

            $sql_extrato = "UPDATE tbl_os_extra SET extrato = $extrato
                    FROM tmp_extrato_{$fabrica_nome}_{$posto}
                    WHERE  tbl_os_extra.os = tmp_extrato_{$fabrica_nome}_{$posto}.os AND tbl_os_extra.i_fabrica=$fabrica
                    AND  ((tmp_extrato_{$fabrica_nome}_{$posto}.status_os NOT IN (67,68,70) AND tmp_extrato_{$fabrica_nome}_{$posto}.status_os IS NOT NULL) OR tmp_extrato_{$fabrica_nome}_{$posto}.status_os IS NULL) ";

            $res_extrato = pg_query($con,$sql_extrato);

            if(strlen(pg_last_error($con)) > 0){
                throw new Exception('Erro ao vincular extrato com OS: '. pg_last_error($con));
            }

            $sql_extrato = "UPDATE tbl_extrato_lancamento SET extrato = $extrato
                WHERE tbl_extrato_lancamento.fabrica = $fabrica
                AND   tbl_extrato_lancamento.extrato IS NULL
                AND   tbl_extrato_lancamento.posto = $posto ; ";

            $res_extrato = pg_query($con, $sql_extrato);

            if(strlen(pg_last_error($con))){
                throw new Exception('Erro ao atualizar tbl_extrato_lançamento');
            }

            if(strlen($extrato) > 0){
                $sql_extrato = "SELECT fn_calcula_extrato($fabrica, $extrato);";
                $res_extrato = pg_query($con, $sql_extrato);

                if(strlen(pg_last_error($con)) > 0 ){

                    throw new Exception('Erro ao calcular extrato');
                }

                $sqle = "SELECT total FROM tbl_extrato WHERE extrato = $extrato";
                $rese = pg_query($con, $sqle);
                $total = pg_fetch_result($rese,0,'total');

                $total_extrato = ($fabrica == 11) ? 100 : 50;

                if (date('d') == '16' and $total < $total_extrato) {
                    $sql = "ROLLBACK TRANSACTION";
                    $result = pg_query($con, $sql);
                }
            }

            $sql = "COMMIT TRANSACTION";
            $result = pg_query($con, $sql);
        }
    }

    $phpCron->termino();

} catch (Exception $e) {
    $sql = "ROLLBACK TRANSACTION";
    $result = pg_query($con, $sql);

    $msg = "Arquivo: ".__FILE__."\r\nErro na linha: " . $e->getLine() . "\r\nErro descrição: " . $e->getMessage();

    if (posix_geteuid() != 0) { // not is root
        echo "$msg\n";
    }
    $data_log = array('dest'=>'helpdesk@telecontrol.com.br');
    Log::envia_email($data_log,Date('d/m/Y H:i:s')." - Erro ao executar gera extrato Lenoxx", $msg);
    $phpCron->termino();

}

