<?php
error_reporting(E_ALL ^ E_NOTICE);
//define('ENV','producao');  // producao Alterar para produção ou algo assim
define('ENV','teste');  // producao Alterar para produção ou algo assim

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $msg_erro       = array();
    $login_fabrica  = 42;
    $fabrica        = 42;
    $fabrica_nome   = 'makita';
    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'calcula-os-por-posto';
    $vet['dest']    = array('helpdesk@telecontrol.com.br');
    $vet['log']     = 1;

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $log = new Log2();

    $data_arquivo = date('Y-m-d-H-i');


    $sql = "SELECT (count(os) / 6) AS media, posto
              FROM tbl_os
             WHERE fabrica={$login_fabrica}
               AND finalizada > current_date - interval '6 months'
               AND excluida IS NOT TRUE
               GROUP BY posto";
    $res = pg_query($con, $sql);

    if (pg_last_error($con)) {
        throw new Exception(pg_last_error($con));
    }

    $osMediaFinalizadas = pg_fetch_all($res);

    foreach ($osMediaFinalizadas as $key => $valuePorPostos) {
        $data_inicio = date("Y-m-01", strtotime("today -1 month"));
        $data_final = date("Y-m-t", strtotime("today -1 month"));

        $sql = "SELECT count(tbl_os.os) total , tbl_os.posto
                  FROM tbl_os
                 WHERE tbl_os.fabrica={$login_fabrica}
                   AND tbl_os.finalizada BETWEEN '".$data_inicio." 00:00:00' AND '".$data_final." 23:59:59'
                   AND tbl_os.posto={$valuePorPostos['posto']}
                   AND excluida IS NOT TRUE
              GROUP BY tbl_os.posto";
        $res = pg_query($con, $sql);

        if (pg_last_error($con)) {
            throw new Exception(pg_last_error($con));
        }

        $osFinalizadasPorPostos = pg_fetch_result($res, 0, total);

        $osMediaFinalizadas[$key]['total'] = $osFinalizadasPorPostos;

    }

    foreach ($osMediaFinalizadas as $key => $rowOs) {

        $total = (empty($rowOs['total'])) ? 0 : $rowOs['total'];
        $media = (empty($rowOs['media'])) ? 0 : $rowOs['media'];
		
		$sql = "DELETE FROM tbl_posto_media_atendimento where fabrica = $login_fabrica and posto = ".$rowOs['posto']. ";

				INSERT INTO tbl_posto_media_atendimento (
                                                            fabrica,
                                                            posto,
                                                            qtde_finalizadas_30,
                                                            qtde_media
                                                        ) VALUES (
                                                            ".$login_fabrica.",
                                                            ".$rowOs['posto'].",
                                                            ".$total.",
                                                            ".$media."
                                                        )";
        $res = pg_query($con, $sql);

        if (pg_last_error($con)) {
            $msg_erro[] = pg_last_error($con);
        }

    }

    if (count($msg_erro) > 0) {
        throw new Exception(implode("\n ", $msg_erro));
    }

    $phpCron->termino();


} catch (Exception $e) {

   $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
   Log::envia_email($vet, "Erro Calculo de OS por Posto", $msg);

}
?>
