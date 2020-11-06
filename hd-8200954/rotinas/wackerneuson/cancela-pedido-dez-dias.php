<?php
try {
	$login_fabrica = 143;
	$fabrica_nome  = 'wackerneuson';

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    define('APP', 'Cancela Pedido aguardando aprovação a mais de 10 dias - '.$fabrica_nome);
	define('ENV','producao');


    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'cancela-pedido-exportado';
    $vet['dest']    = ENV == 'testes' ? 'guilherme.monteiro@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
    $vet['log']     = 1;

    $logs[] = "";
    $data_sistema = Date('Y-m-d');

    $sql = "SELECT  tbl_pedido.pedido
            FROM    tbl_pedido
            WHERE   status_pedido = 18
            AND     fabrica = $login_fabrica
            AND     exportado IS NOT NULL
            AND     finalizado IS NOT NULL
            AND     posto <> 6359
            AND     data + INTERVAL '10 days' < CURRENT_TIMESTAMP
            ORDER BY      pedido DESC ";

    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){

        pg_query ($con,"BEGIN TRANSACTION");
        for($i = 0; $i < pg_num_rows($res); $i++){
            $log_cliente[]  = "";
            $pedido = pg_fetch_result($res,$i,pedido);
            $sqlQ = "
                    SELECT  descricao
                    FROM    tbl_tipo_pedido
                    JOIN    tbl_pedido  ON  tbl_pedido.tipo_pedido  = tbl_tipo_pedido.tipo_pedido
                                        AND tbl_tipo_pedido.fabrica = $login_fabrica
                    WHERE   tbl_pedido.pedido   = $pedido
                    AND     tbl_pedido.fabrica  = $login_fabrica
            ";
            $resQ = pg_query($con,$sqlQ);
            $msg_erro .= pg_errormessage($con);
            if (pg_numrows($resQ) > 0) {
                $tipo_pedido = pg_result($resQ, 0, 'descricao');

                $sqlT = "
                        SELECT  tbl_pedido_item.pedido_item,
                                tbl_pedido_item.peca,
                                (
                                    tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)
                                ) AS qtde,
                                tbl_pedido.posto
                        FROM    tbl_pedido
                        JOIN    tbl_pedido_item USING(pedido)
                        WHERE   tbl_pedido.fabrica  = $login_fabrica
                        AND     tbl_pedido.pedido   = $pedido
                ";
                $resT = pg_query($con,$sqlT);
                $msg_erro .= pg_errormessage($con);

                if (pg_numrows($resT) > 0) {

                    $total = pg_numrows($resT);

                    for ($j = 0; $j < $total; $j++) {

                        $pedido_item = pg_result($resT, $j, 'pedido_item');
                        $qtde        = pg_result($resT, $j, 'qtde');
                        $peca        = pg_result($resT, $j, 'peca');
                        $posto       = pg_result($resT, $j, 'posto');

                        $os = (empty($os)) ? "null" : $os;

                        $motivo = "Prazo para atendimento expirou.";
                        $login_admin = "null";
                        $sqlSemOS = "SELECT fn_pedido_cancela_gama(null,$login_fabrica,$pedido,$peca,$qtde,'$motivo',$login_admin)";
                        $resSemOS = pg_query ($con,$sqlSemOS);
                        $msg_erro .= pg_errormessage($con);

                    }

                    $sqlCan = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";

                    $resCan = pg_exec ($con,$sqlCan);
                    $msg_erro .= pg_errormessage($con);
                }
            }
            $log_cliente[] = $msg_error = Date('d/m/Y H:i:s ')."O pedido {$pedido} foi cancelado - Motivo: $motivo";
        }

        if (strlen($msg_erro) == 0) {

            if(count($log_cliente) > 0){

                system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
                system("mkdir /tmp/{$fabrica_nome}/log_cancela_pedido/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/log_cancela_pedido/" );
                $arquivo_log_cliente = "/tmp/{$fabrica_nome}/log_cancela_pedido/cancela-pedido-dez-dias-".$data_sistema."-cliente.txt";

                $logs[] = "################################## PEDIDOS CANCELADOS ##################################";
                $logs[] = implode("\r\n", $log_cliente);
                $logs[] = "########################################################################################\r\n";
            }

            if (count($logs) > 0) {
                $fl_cliente = fopen($arquivo_log_cliente, "w+");
                fputs($fl_cliente, implode("\r\n", $logs));
                fclose($fl_cliente);
            }

            $email = "vanilde.sartorelli@wackerneuson.com";
            $mailer = new PHPMailer();
            $mailer->IsHTML();
            $mailer->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");
            $mailer->AddAddress($email);
            $mailer->AddAttachment("{$arquivo_log_cliente}");
            $mensagem .= "Logs 'Cancelamento pedido sem aprovação'<br>";
            $mensagem .= "Mensagem segue em anexo!<br><br>";
            $mensagem .= "<br><br>Att.<br>Telecontrol Networking";
            $mailer->Body = $mensagem;
            if(!$mailer->Send())
            throw new Exception ($mailer->ErrorInfo);
            pg_query($con,"COMMIT TRANSACTION");

        }else{

            pg_query($con,"ROLLBACK TRANSACTION");
            $dir = "/tmp/$fabrica_nome/log_cancela_pedido";
            $file = $dir.'/cancela-pedido-exportado.err';

            $fp   = fopen($file, 'w');
            fputs($fp, $msg_erro);
            fclose($fp);

            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($vet, APP, $msg);
        }
    }
}catch(Exception $e){
    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);
}
?>