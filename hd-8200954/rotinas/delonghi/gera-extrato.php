<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

try {

    $bug         = '';
    $fabrica     = 101;
    $dia_mes     = date('d');
    $dia_extrato = date('Y-m-d H:i:s');

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $vet['fabrica'] = 'delonghi';
    $vet['tipo']    = 'extrato';
    $vet['dest']    = 'helpdesk@telecontrol.com.br';
    $vet['log']     = 2;

    $sql = "SELECT posto, COUNT(*) AS qtde
              FROM tbl_os
              JOIN tbl_os_extra USING (os)
             WHERE tbl_os.fabrica = $fabrica
               AND tbl_os_extra.extrato IS NULL
               AND tbl_os.excluida      IS NOT TRUE
               AND tbl_os.cancelada IS NOT TRUE
               AND tbl_os.posto <> 6359
               AND tbl_os.finalizada <= '$dia_extrato'
               AND tbl_os.finalizada::date <= current_date
          GROUP BY posto
          ORDER BY posto ";

    $res       = pg_query($con, $sql);
    $msg_erro .= pg_last_error($con);

    if (pg_num_rows($res) > 0 && strlen($msg_erro) == 0) {

        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $posto  = pg_result($res, $i, 'posto');
            $qtde   = pg_result($res, $i, 'qtde');

            $resP   = pg_query($con,"BEGIN TRANSACTION");
            #Cria um extrato para o posto
            $sql2   = "INSERT INTO tbl_extrato (
                                                posto, 
                                                fabrica, 
                                                avulso, 
                                                total
                                            ) VALUES (
                                                $posto,
                                                $fabrica, 
                                                0, 
                                                0
                                            )";
            $res2      = pg_query($con, $sql2);
            $msg_erro .= pg_last_error($con);

            $sql3      = "SELECT CURRVAL ('seq_extrato');";
            $res3      = pg_query($con, $sql3);
            $extrato   = pg_result($res3, 0, 0);
            $msg_erro .= pg_last_error($con);
            
            # HD17669
            $sql4 = "UPDATE tbl_extrato_lancamento 
                        SET extrato = $extrato
                      WHERE tbl_extrato_lancamento.fabrica = $fabrica
                        AND tbl_extrato_lancamento.extrato IS NULL
                        AND tbl_extrato_lancamento.posto = $posto; ";
            $res4 = pg_query($con, $sql4);
            $msg_erro .= pg_last_error($con);

            #Seta o número do extrato em que as OS pertencem.
            $sql4 = "UPDATE tbl_os_extra 
                        SET extrato=$extrato
                       FROM tbl_os
                      WHERE tbl_os.posto=$posto
                        AND tbl_os.fabrica=$fabrica
                        AND tbl_os.os=tbl_os_extra.os
                        AND tbl_os_extra.extrato IS NULL
                        AND tbl_os.excluida IS NOT TRUE
                        AND tbl_os.finalizada <= '$dia_extrato' 
                        AND tbl_os.finalizada::date <= current_date";
            $res4      = pg_query($con, $sql4);
            $msg_erro .= pg_last_error($con);


            $sql5 = "UPDATE tbl_extrato
                        SET avulso=(
                                        SELECT SUM (valor)
                                          FROM tbl_extrato_lancamento
                                         WHERE tbl_extrato_lancamento.extrato = tbl_extrato.extrato
                                      )
                      WHERE tbl_extrato.fabrica = $fabrica
                        AND tbl_extrato.total < 0
                        AND tbl_extrato.data_geracao > CURRENT_DATE;
                     
                     UPDATE tbl_extrato
                        SET total = mao_de_obra + avulso
                      WHERE tbl_extrato.fabrica = $fabrica
                        AND tbl_extrato.total < 0
                        AND tbl_extrato.data_geracao > CURRENT_DATE;";
            $res5      = pg_query($con, $sql5);

            $sql6      = "SELECT fn_calcula_extrato ($fabrica, $extrato)";
            $res6      = pg_query($con, $sql6);
            $msg_erro .= pg_last_error($con);

            $sqlLGR = "UPDATE tbl_extrato 
                          SET aprovado = CURRENT_TIMESTAMP, liberado = CURRENT_DATE 
                        WHERE fabrica = $fabrica 
                          AND extrato = $extrato";
            $resLGR = pg_query($con,$sqlLGR);
            $msg_erro .= pg_last_error($con);

            $sql9 = "SELECT ('$dia_extrato'::date - INTERVAL '1 month' + INTERVAL '14 days')::date";
            $res9 = pg_query($con,$sql9);
            $data_15 = pg_fetch_result($res9, 0, 0);

            $sql7 = "UPDATE tbl_faturamento_item SET
                        extrato_devolucao = $extrato,
                        devolucao_obrig = tbl_os_item.peca_obrigatoria
                    FROM tbl_os_item, tbl_os_produto, tbl_os_extra, tbl_faturamento, tbl_peca, tbl_os
                    WHERE (
                        tbl_os_item.os_item = tbl_faturamento_item.os_item
                        OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido_item = tbl_faturamento_item.pedido_item AND tbl_faturamento_item.os_item IS NULL )
                        OR ( tbl_os_item.peca = tbl_faturamento_item.peca AND tbl_os_item.pedido = tbl_faturamento_item.pedido AND tbl_faturamento_item.os_item IS NULL )
                        )
                        AND tbl_peca.peca = tbl_os_item.peca
                        AND tbl_os_item.os_produto = tbl_os_produto.os_produto
                        AND tbl_os_produto.os = tbl_os_extra.os
                        AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                        AND tbl_faturamento.posto = $posto
                        AND tbl_faturamento.fabrica = $fabrica
                        AND tbl_faturamento.emissao <='$data_15'
                        AND tbl_faturamento.cancelada IS NULL
                        AND tbl_faturamento_item.extrato_devolucao IS NULL
                        AND tbl_peca.produto_acabado IS TRUE
                        AND tbl_peca.aguarda_inspecao IS NOT TRUE
                        AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%'
                        AND tbl_os.os = tbl_faturamento_item.os AND tbl_os.prateleira_box = 'troca_lgr'
                    )";

            $res7 = pg_query($con,$sql7);
            $msg_erro .= pg_last_error($con);

            $sql8 = "INSERT INTO tbl_extrato_lgr (
                        extrato,
                        posto,
                        peca,
                        qtde
                    )
                    SELECT
                        tbl_extrato.extrato,
                        tbl_extrato.posto,
                        tbl_faturamento_item.peca,
                        SUM (tbl_faturamento_item.qtde)
                    FROM tbl_extrato
                        JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
                    WHERE tbl_extrato.fabrica = $fabrica
                        AND tbl_extrato.extrato = $extrato
                    GROUP BY tbl_extrato.extrato,
                        tbl_extrato.posto,
                        tbl_faturamento_item.peca";

            $res8 = pg_query($con,$sql8);
            $msg_erro .= pg_last_error($con);

            if (strlen($msg_erro) > 0) {
                $resP = pg_query('ROLLBACK;');
                $bug .= $msg_erro;
                Log::log2($vet, $msg_erro);
            } else {
                $resP = pg_query('COMMIT;');
            }

        }

    }

    if (strlen($bug) > 0) {
        Log::envia_email($vet, 'Log - Extrato DeLonghi', $bug);
    }

    $phpCron->termino();

} catch (Exception $e) {
    Log::envia_email($data,Date('d/m/Y H:i:s')." - DeLonghi - Erro na geração de extrato(gera-extrato.php)", $e->getMessage());
}
