<?php
/**
 *
 * gera-pedido-troca.php
 *
 * Geração de pedidos de troca com base na OS
 *
 * @author  William Ap. Brandino
 * @version 2015.04.15
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','teste');  // producao Alterar para produção ou algo assim

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    include dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $vet['fabrica'] = 'esmaltec';
    $vet['tipo']    = 'pedido';
    $vet['log']     = 2;
    $fabrica        = 30;
    $data_sistema   = Date('Y-m-d');
    $logs_erro      = array();

    if (ENV != 'teste' ) {
        $vet['dest']        = 'helpdesk@telecontrol.com.br';
    } else {
        $vet['dest']        = 'ronald.santos@telecontrol.com.br';
    }

    $arquivo_err = "/tmp/esmaltec/gera-pedido-troca-{$data_sistema}.err";
    $arquivo_log = "/tmp/esmaltec/gera-pedido-troca-{$data_sistema}.log";
    system ("mkdir  /tmp/esmaltec/ 2> /dev/null ; chmod 777 /tmp/esmaltec/" );

    // ####################################################
    // INTERVENCAO KM REINCIDENTE
    // ####################################################
    $sql = "SELECT  interv_reinc.os
       INTO TEMP    tmp_interv_reinc
            FROM    (
                        SELECT  ultima_reinc.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os             = ultima_reinc.os
                                    AND     tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (13, 19, 68, 67, 70, 115, 118)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_reinc_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (13, 19, 68, 67, 70, 115, 118)
                                ) ultima_reinc
                    ) interv_reinc
            WHERE   interv_reinc.ultimo_reinc_status IN (13, 68, 67, 70, 115, 118);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Intervenção Reincidente (13,19,68,67,70,115,118)'";
        $logs_erro[] = $sql;
        $logs[]      = pg_last_error($con);
        $erro        = true;
        throw new Exception ($msg_erro);
    }

    // ####################################################
    // INTERVENCAO NUMERO DE SERIE
    // ####################################################
    $sql = "SELECT  interv_serie.os
       INTO TEMP    tmp_interv_serie
            FROM    (
                        SELECT  ultima_serie.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os             = ultima_serie.os
                                    AND     tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (102, 103, 104)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_serie_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (102, 103, 104)
                                ) ultima_serie
                    ) interv_serie
            WHERE   interv_serie.ultimo_serie_status IN (102, 104);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'Intervenção de Série (102,103,104)'";
        $logs_erro[] = $sql;
        $logs[]      = pg_last_error($con);
        $erro        = true;
        throw new Exception ($msg_erro);
    }

    // ####################################################
    // INTERVENCAO LGI
    // ####################################################
    $sql = "SELECT  interv_lgi.os
       INTO TEMP    tmp_interv_lgi
            FROM    (
                        SELECT ultima_lgi.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os                = ultima_lgi.os
                                    AND     tbl_os_status.fabrica_status    = $fabrica
                                    AND     status_os IN (105,106,107)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_lgi_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (105,106,107)
                                ) ultima_lgi
                    ) interv_lgi
            WHERE   interv_lgi.ultimo_lgi_status IN (105,107);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'intervenção de LGI (105,106,107)'";
        $logs_erro[] = $sql;
        $logs[]      = pg_last_error($con);
        $erro        = true;
        throw new Exception ($msg_erro);
    }

    // ####################################################
    // LAUDO DE TROCA
    // ####################################################
    $sql = "SELECT  laudo_troca.os
       INTO TEMP    tmp_laudo_troca
            FROM    (
                        SELECT ultima_troca.os,
                                (
                                    SELECT  status_os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.os                = ultima_troca.os
                                    AND     tbl_os_status.fabrica_status    = $fabrica
                                    AND     status_os IN (192,193,194)
                              ORDER BY      os_status DESC
                                    LIMIT   1
                                ) AS ultimo_troca_status
                        FROM    (
                                    SELECT  DISTINCT
                                            os
                                    FROM    tbl_os_status
                                    WHERE   tbl_os_status.fabrica_status = $fabrica
                                    AND     status_os IN (192,193,194)
                                ) ultima_troca
                    ) laudo_troca
            WHERE   laudo_troca.ultimo_troca_status IN (192,194);";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs[]      = $msg_erro = date("Y-m-d H:i:s")." - Erro SQL: 'LAUDO DE TROCA (192,193,194)'";
        $logs_erro[] = $sql;
        $logs[]      = pg_last_error($con);
        $erro        = true;
        throw new Exception ($msg_erro);
    }
    $lista_posto_teste = '6359';
    $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND tipo_posto IN(SELECT tipo_posto FROM tbl_tipo_posto WHERE descricao = 'SAC' AND fabrica = {$fabrica})";

    $res = pg_query($con, $sql);
    for ($i=0; $i < pg_num_rows($res); $i++) {
        $lista_posto_teste .= ','.pg_fetch_result($res, $i, 'posto');
    }        
    $sql_posto_teste = " NOT IN ({$lista_posto_teste})";

    $sql = "SELECT DISTINCT tbl_posto.posto,
		tbl_produto.linha,
		tbl_tipo_posto.tipo_posto
	FROM    tbl_os_item
	JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
	AND tbl_servico_realizado.fabrica = $fabrica
	JOIN    tbl_os_produto        ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
	JOIN    tbl_os                ON tbl_os.os                               = tbl_os_produto.os
	JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
	JOIN    tbl_posto ON tbl_posto.posto = tbl_os.posto
	JOIN    tbl_posto_fabrica     ON tbl_posto_fabrica.posto                 = tbl_os.posto
	AND tbl_posto_fabrica.fabrica = $fabrica
	JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $fabrica
	JOIN    tbl_tipo_posto ON  tbl_posto_fabrica.tipo_posto    = tbl_tipo_posto.tipo_posto
	AND tbl_tipo_posto.posto_interno IS NOT TRUE
	JOIN (
	SELECT estado,
	tipo_posto,
	tipo_pedido
	FROM tbl_gera_pedido_dia
	WHERE dia_semana = (SELECT to_char(current_date - interval '1 day','D')::integer )
	AND fabrica = $fabrica AND ativo
	) as tbl_pedido_dia on tbl_posto_fabrica.contato_estado = tbl_pedido_dia.estado AND tbl_posto_fabrica.tipo_posto = tbl_pedido_dia.tipo_posto AND tbl_pedido_dia.tipo_pedido IN (231)
	WHERE   tbl_os_item.pedido IS NULL
	AND     tbl_os.excluida           IS NOT TRUE
	AND     tbl_os.validada           IS NOT NULL
	AND     tbl_posto_fabrica.posto   {$sql_posto_teste}
	AND     tbl_os_troca.gerar_pedido IS TRUE
	AND     tbl_os_troca.ressarcimento IS NOT TRUE
	AND     tbl_os.fabrica =  $fabrica
	AND     (credenciamento = 'CREDENCIADO' OR credenciamento = 'EM DESCREDENCIAMENTO')
	AND     tbl_servico_realizado.troca_produto   IS TRUE
	AND     tbl_servico_realizado.ressarcimento IS NOT TRUE
	AND     tbl_os.data_digitacao >= '2015-03-13 00:00:00'";
    $res = pg_query($con, $sql);

    if(pg_last_error($con)){
        $logs_erro[] = $sql."<br>".pg_last_error($con);
    }

    #Garantia
    $sql = "select condicao from tbl_condicao where fabrica = ".$fabrica." and lower(descricao) = 'garantia';";
    $resultG = pg_query($con, $sql);
    if(pg_last_error($con)){
        $logs_erro[] = $sql."<br>".pg_last_error($con);
    }else{
        $condicao = pg_result($resultG,0,'condicao');
    }


    if(pg_num_rows($res) > 0 AND count($logs_erro) == 0){

        for($i = 0; $i < pg_num_rows($res); $i++){
            $posto = pg_result($res,$i,'posto');
            $linha = pg_result($res,$i,'linha');

            unset($logs_erro);

            $resultX = pg_query($con,"BEGIN TRANSACTION");

            $sql = "SELECT  tbl_os_troca.peca,
                        tbl_os.os
                    FROM    tbl_os
                    JOIN    tbl_os_troca          ON tbl_os_troca.os = tbl_os.os
                    JOIN    tbl_produto           ON tbl_os.produto  = tbl_produto.produto
                    WHERE   tbl_os_troca.gerar_pedido IS TRUE
                    AND     tbl_os_troca.pedido       IS NULL
                    AND     tbl_os.fabrica    = $fabrica
                    AND     tbl_os.posto      = $posto
                    AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_reinc )
                    AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_serie )
                    AND     tbl_os.os NOT IN ( SELECT os FROM tmp_interv_lgi )
                    AND     tbl_os.os NOT IN ( SELECT os FROM tmp_laudo_troca )
                    AND     tbl_produto.linha = $linha ";
            $result = pg_query($con, $sql);

            if(pg_last_error($con)){
                $logs_erro[] = $sql."<br>".pg_last_error($con);
            }

            if(pg_num_rows($result) > 0 AND count($logs_erro) == 0){

                for($x = 0; $x < pg_num_rows($result); $x++){
                    $peca = pg_result($result,$x,'peca');
                    $os   = pg_result($result,$x,'os');

                    $sql = "INSERT INTO tbl_pedido (
                                                    posto     ,
                                                    fabrica   ,
                                                    linha     ,
                                                    condicao  ,
                                                    tipo_pedido,
                                                    troca      ,
                                                    total
                                                ) VALUES (
                                                    $posto    ,
                                                    $fabrica  ,
                                                    $linha    ,
                                                    $condicao ,
                                                    '231'     ,
                                                    TRUE      ,
                                                    0
                                                ) RETURNING pedido;";
                    $resultX = pg_query($con, $sql);
                    if(pg_last_error($con)){
                        $logs_erro[] = $sql."<br>".pg_last_error($con);
                    } else {
                        $pedido = pg_result($resultX,0,0);

                        $sql = "SELECT total_troca FROM tbl_os_troca WHERE os = $os";
                        $resultX = pg_query($con, $sql);

                        if(pg_num_rows($resultX) > 0){
                            $total_troca = pg_result($resultX,0,'total_troca');
                        }


                        $sql = "INSERT INTO tbl_pedido_item (
                                                            pedido,
                                                            peca  ,
                                                            qtde  ,
                                                            qtde_faturada,
                                                            qtde_cancelada,
                                                            troca_produto
                                                        ) VALUES (
                                                            $pedido,
                                                            $peca  ,
                                                            1      ,
                                                            0      ,
                                                            0      ,
                                                            't'
                                                        ) RETURNING pedido_item";
                        $resultX = pg_query($con, $sql);

                        if(pg_last_error($con)){
                            $logs_erro[] = $sql."<br>".pg_last_error($con);
                        } else {
                            $pedido_item = pg_result($resultX,0,0);

                            $sql = "UPDATE tbl_os_troca SET pedido = $pedido, pedido_item = $pedido_item WHERE os = $os";
                            $resultX = pg_query($con, $sql);
                            if(pg_last_error($con)){
                                $logs_erro[] = $sql."<br>".pg_last_error($con);
                            }


                            $sql = "SELECT fn_atualiza_os_item_pedido_item (os_item,$pedido,$pedido_item,$fabrica)
                                    FROM tbl_os_item
                                    WHERE peca = $peca
                                    AND os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = $os)";
                            $resultX = pg_query($con, $sql);
                            if(pg_last_error($con)){
                                $logs_erro[] = $sql."<br>".pg_last_error($con);
                            }

                            $sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
                            $resultX = pg_query($con, $sql);

                            if(pg_last_error($con)){
                                $logs_erro[] = $sql."<br>".pg_last_error($con);
                            }
                        }
                    }
                }
            }

            if (count($logs_erro)>0){
                $resultX = pg_query($con, "ROLLBACK TRANSACTION");
            }else{
                $resultX = pg_query($con,"COMMIT TRANSACTION");
            }
        }
    }

    if (count($logs_erro) > 0 ) {
        $logs_erro = implode("<br>", $logs_erro);
        Log::log2($vet, $logs_erro);

    }

    if ($logs_erro) {

        Log::envia_email($vet, "Log de ERROS - Geração de Pedido de Troca de OS ThermoSystem", $logs_erro);

    }


} catch (Exception $e) {
    echo $e->getMessage();
}
