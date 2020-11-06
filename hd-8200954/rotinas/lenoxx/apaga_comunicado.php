<?php

error_reporting(E_ALL ^ E_NOTICE);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    define('APP', 'Exclusão Comunicados!');

    $fabricas = array(
        11 => "lenoxx",
        172 => "pacific",
    );

    foreach ($fabricas as $fabrica => $fabrica_nome) {

        $login_fabrica  = $fabrica;
        $vet['fabrica'] = $fabrica_nome;
        $vet['tipo']    = 'excluidos';
        $vet['dest']    = array('helpdesk@telecontrol.com.br', 'andreus@telecontrol.com.br');
        $vet['log']     = 2;

        $sql = "SELECT comunicado
                  FROM tbl_comunicado
                 WHERE fabrica = $login_fabrica
                   AND tipo    IN('Peça Cancelada em Pedido', 'Pedido de Peças', 'LGR', 'Extrato disponível')
                   AND data    < current_date -  INTERVAL ' 6 MONTH'";

        $res = pg_query($con, $sql);
        $tot = pg_num_rows($res);

        $msg_erro = pg_errormessage($con);
        $vet_erro = '';

        if (!empty($msg_erro)) {
            $vet_erro[] = $msg_erro;
        }

        if ($tot) {

            for ($i = 0; $i < $tot; $i++) {

                $comunicado = pg_result($res, $i, 'comunicado');
                $extensao   = array('gif', 'jpg', 'doc', 'pdf', 'rtf', 'xls', 'zip', 'pps', 'ppt');

                foreach ($extensao as $ext) {

                    $file = dirname(__FILE__).'/../../comunicados/'.$comunicado.'.'.$ext;

                    if (file_exists($file)) {

                        if (!unlink($file)) {
                            $vet_erro[] = 'Erro ao excluir arquivo: ' . $file;
                        }

                    }

                }

                $sql_comu = "DELETE FROM tbl_comunicado WHERE comunicado = $comunicado;";
                $res_comu = pg_query($con, $sql_comu);
                $msg_erro = pg_errormessage($con);

                if (!empty($msg_erro)) {
                    $vet_erro[] = $msg_erro;
                }

            }

            if (is_array($vet_erro)) {
                throw new Exception(implode('<br />', $msg_erro));
            }

            Log::envia_email($vet, APP, 'Rotina rodada com sucesso!<br />Total de '.$i.' registros!');

        }

    }

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();

    Log::log2($vet, $msg);
    Log::envia_email($vet, APP, $msg);

}
