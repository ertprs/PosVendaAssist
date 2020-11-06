<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'chat';
    $vet['dest']    = 'andreus@telecontrol.com.br';
    $vet['log']     = 2;

    $sql = "SELECT fabrica FROM tbl_fabrica WHERE ativo_fabrica IS TRUE;";
    $res = pg_query($con, $sql);
    $tot = pg_num_rows($res);

    $msg_erro[] = pg_last_error($con);

    for ($i = 0; $i < $tot; $i++) {

        $fabrica = pg_result($res, $i, 'fabrica');

        $sql2 = "SELECT fn_fabrica_chat($fabrica, tbl_fabrica_chat.admin)
                   FROM tbl_fabrica_chat
                   JOIN tbl_fabrica ON tbl_fabrica_chat.fabrica = tbl_fabrica.fabrica
                  WHERE DATE_PART('MONTH', tbl_fabrica_chat.data) = DATE_PART('MONTH', CURRENT_TIMESTAMP - INTERVAL '1 MONTH')
                    AND tbl_fabrica.ativo_fabrica IS TRUE
                  ORDER BY fabrica_chat DESC LIMIT 1;";

         $res2       = pg_query($con, $sql2);
         $msg_erro[] = pg_last_error($con);

    }

    if (strlen($msg_erro) > 0) {

        $bug1 = implode("\n", $msg_erro);
        $bug2 = implode("<br />", $msg_erro);
        Log::log2($vet, $bug1);
        Log::envia_email($vet, 'Log Rotina Chat Fabrica', $bug2);

    }

} catch (Exception $e) {

    echo $e->getMessage();

}

?>
