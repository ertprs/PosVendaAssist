<?php
try {
	$login_fabrica = 88;
	$fabrica_nome  = 'orbis';

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    define('APP', 'Cancela Pedido exportado a mais de 30 dias - '.$fabrica_nome);
	define('ENV','testes');

    $vet['fabrica'] = $fabrica_nome;
    $vet['tipo']    = 'cancela-pedido-exportado';
    $vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
    $vet['log']     = 1;
    
    $sql = "SELECT  tbl_pedido.pedido
            FROM    tbl_pedido
            WHERE   status_pedido = 2
            AND     fabrica = $login_fabrica
            AND     exportado IS NOT NULL
            AND     exportado + INTERVAL '30 days' < CURRENT_TIMESTAMP
      ORDER BY      pedido DESC
    ";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){
        pg_query ($con,"BEGIN TRANSACTION");
        for($i = 0; $i < pg_num_rows($res); $i++){
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

                if (strtoupper($tipo_pedido) == "GARANTIA") {

                    $campo = ", tbl_os_produto.os";
                    $joins = "JOIN tbl_os_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                              JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto";

                }else{
                    $campo = ", null AS os ";
                }
                
                $sqlT = "
                        SELECT  tbl_pedido_item.pedido_item,
                                tbl_pedido_item.peca,
                                (
                                    tbl_pedido_item.qtde - (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada)
                                ) AS qtde,
                                tbl_pedido.posto
                                $campo
                        FROM    tbl_pedido
                        JOIN    tbl_pedido_item USING(pedido)
                        $joins
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
                        $os          = pg_result($resT, $j, 'os');

                        $os = (empty($os)) ? "null" : $os;

                        $motivo = "Prazo para atendimento expirou.";
                        $login_admin = "null";
                        if(!empty($os) AND $os != 'null'){
                            $sqlOS = "
                                    SELECT  os_item
                                    FROM    tbl_os_item
                                    JOIN    tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                    WHERE   os          = $os
                                    AND     pedido_item = $pedido_item
                            ";
                            $resOS = pg_query($con,$sqlOS);
                            $msg_erro .= pg_errormessage($con);
                            $os_item = pg_result($resOS,0,'os_item');

                            $sqlOS2  = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,$pedido,$peca,$os_item,'$motivo',$login_admin)";
                            $resOS2 = pg_query ($con,$sqlOS2);
                            $msg_erro .= pg_errormessage($con);
                        }else{
                            $sqlSemOS = "SELECT fn_pedido_cancela_gama(null,$login_fabrica,$pedido,$peca,$qtde,'$motivo',$login_admin)";
                            $resSemOS = pg_query ($con,$sqlSemOS);
                            $msg_erro .= pg_errormessage($con);
                        }

                    }
                    
                    $sqlCan = "SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);";
                    $resCan = pg_exec ($con,$sqlCan);
                    $msg_erro .= pg_errormessage($con);

                }

            }
        }
        if (strlen($msg_erro) == 0) {
            pg_query($con,"COMMIT TRANSACTION");
        }else{
            pg_query($con,"ROLLBACK TRANSACTION");
            $dir = "/tmp/$fabrica_nome/pedidos";
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