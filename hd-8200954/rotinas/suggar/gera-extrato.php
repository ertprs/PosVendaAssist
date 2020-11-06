<?php
/**
 * @author - William Ap. Brandino
 * @since - 08/12/2016
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require_once dirname(__FILE__) . '/../funcoes.php';

$fabrica     = 24;

$sqlPeriodo = "
    SELECT  to_char(current_date, 'DD')::integer                                                                AS hoje         ,
            date_trunc('month',CURRENT_DATE-interval '1 month')::DATE                                           AS data_inicio  ,
            (date_trunc('month',CURRENT_DATE-interval '1 month') + interval '1 month' - interval '1 day')::DATE AS data_fim
";
$resPeriodo = pg_query($con,$sqlPeriodo);

$hoje           = pg_fetch_result($resPeriodo,0,hoje);
$data_inicio    = pg_fetch_result($resPeriodo,0,data_inicio);
$data_fim       = pg_fetch_result($resPeriodo,0,data_fim);
if (in_array($hoje,array(1,15))) {
    try {
        $vet['fabrica'] = 'suggar';
        $vet['tipo']    = 'extrato';
        $vet['dest']    = 'helpdesk@telecontrol.com.br';
        $vet['log']     = 2;

        $phpCron = new PHPCron($fabrica, __FILE__);
        $phpCron->inicio();

        $sql = "SELECT  posto,
                        COUNT(1) AS qtde
                FROM    tbl_os
                JOIN    tbl_os_extra    ON  tbl_os.os               = tbl_os_extra.os
                                        AND tbl_os_extra.i_fabrica  = $fabrica
                WHERE   tbl_os.fabrica          = $fabrica
                AND     tbl_os_extra.extrato    IS NULL
                AND     tbl_os.excluida         IS NOT TRUE
                AND     tbl_os.finalizada       <=  CURRENT_TIMESTAMP
                AND     tbl_os.posto            <> 6359
          GROUP BY      posto
          ORDER BY      posto ";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            for ($i=0;$i<pg_num_rows($res);$i++) {
                $posto = pg_fetch_result($res,$i,posto);
                $qtde  = pg_fetch_result($res,$i,qtde);

                pg_query($con,"BEGIN TRANSACTION");

                $sqlIns = "INSERT INTO tbl_extrato (
                            posto,
                            fabrica,
                            avulso,
                            total
                        ) VALUES (
                            $posto,
                            $fabrica,
                            0,
                            0
                        ) RETURNING extrato";
                $resIns = pg_query($con,$sqlIns);
                $extrato = pg_fetch_result($resIns,0,0);

                $sqlUpExtra = "
                    UPDATE tbl_os_extra SET extrato = $extrato
                    FROM  tbl_os
                    LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
                    WHERE tbl_os.posto   = $posto
                    AND   tbl_os_campo_extra.os_bloqueada is not true
                    AND   tbl_os.fabrica = $fabrica 
                    AND   tbl_os.os      = tbl_os_extra.os
                    AND   tbl_os_extra.extrato      IS NULL
                    AND   tbl_os.excluida           IS NOT TRUE
                    AND   tbl_os.finalizada         <= CURRENT_TIMESTAMP
                    AND   tbl_os.os NOT IN (
                        SELECT  interv_reinc.os
                        FROM    (
                            SELECT  ultima_reinc.os,
                                    (
                                        SELECT  status_os
                                        FROM    tbl_os_status
                                        WHERE   fabrica_status      = $fabrica
                                        AND     tbl_os_status.os    = ultima_reinc.os
                                        AND     status_os           IN (98,99,100,101)
                                  ORDER BY      data DESC
                                        LIMIT 1
                                    ) AS ultimo_reinc_status
                            FROM    (
                                SELECT  DISTINCT
                                        os
                                FROM    tbl_os_status
                                WHERE   fabrica_status  = $fabrica
                                AND     status_os       IN (98,99,100,101)
                                    ) ultima_reinc
                                ) interv_reinc
                        WHERE interv_reinc.ultimo_reinc_status IN (98)
                    )
                ";
                $resUpExtra = pg_query($con,$sqlUpExtra);

                $sqlFn = "SELECT fn_calcula_extrato ($fabrica,$extrato)";
                $resFn = pg_query($con,$sqlFn);

                if ($hoje == 15) {
                    $sqlParam = "   SELECT  DISTINCT
                                            JSON_FIELD('devolver_pecas',tbl_posto_fabrica.parametros_adicionais) AS devolver_pecas
                                    FROM    tbl_posto_fabrica
                                    WHERE   tbl_posto_fabrica.fabrica           = $fabrica
                                    AND     tbl_posto_fabrica.posto             = $posto
                    ";
                    $resParam       = pg_query($con,$sqlParam);
                    $devolver_pecas = pg_fetch_result($resParam,0,devolver_pecas);

                    if ($devolver_pecas == 't') {
                        $sqlLGR = " UPDATE  tbl_faturamento_item
                                    SET     extrato_devolucao = $extrato
                                    FROM    tbl_peca,
                                            tbl_faturamento,
                                            tbl_extrato,
                                            tbl_os_item
                                    WHERE   tbl_peca.peca               = tbl_faturamento_item.peca
                                    AND     tbl_faturamento.posto       = tbl_extrato.posto
                                    AND     tbl_faturamento.fabrica     = tbl_extrato.fabrica
                                    AND     tbl_faturamento_item.pedido = tbl_os_item.pedido
                                    AND     tbl_os_item.peca            = tbl_faturamento_item.peca
                                    AND     tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                                    AND     tbl_peca.devolucao_obrigatoria          IS TRUE
                                    AND     tbl_faturamento.fabrica                 = $fabrica
                                    AND     tbl_faturamento.emissao                 BETWEEN '$data_inicio' AND '$data_fim'
                                    AND     tbl_faturamento.cancelada               IS NULL
                                    AND     tbl_faturamento_item.extrato_devolucao  IS NULL
                                    AND     (
                                                tbl_faturamento.cfop ILIKE '59%'
                                            OR  tbl_faturamento.cfop ILIKE '69%'
                                            )
                                    AND     tbl_extrato.extrato                     = $extrato
                        ";
                        $resLGR = pg_query($con,$sqlLGR);

                        $sqlLGR2 = "INSERT INTO tbl_extrato_lgr (
                                        extrato,
                                        posto,
                                        peca,
                                        qtde
                                    )
                                    (
                                        SELECT  tbl_extrato.extrato,
                                                tbl_extrato.posto,
                                                tbl_faturamento_item.peca,
                                                SUM (tbl_faturamento_item.qtde)
                                        FROM    tbl_extrato
                                        JOIN    tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
                                        WHERE   tbl_extrato.fabrica = $fabrica
                                        AND     tbl_extrato.extrato = $extrato
                                  GROUP BY      tbl_extrato.extrato,
                                                tbl_extrato.posto,
                                                tbl_faturamento_item.peca
                                    )
                        ";
                        $resLGR2 = pg_query($con,$sqlLGR2);
                    }
                }

                if (pg_last_error($con)) {
                    $erro = pg_last_error($con);
                    pg_query($con,"ROLLBACK TRANSACTION");

                    throw new exception($erro);
                    continue;

                }

                pg_query($con,"COMMIT TRANSACTION");
            }
        }
        $phpCron->termino();
    }catch (Exception $e){
        Log::envia_email($data,Date('d/m/Y H:i:s')." - ".strtoupper($vet['fabrica'])." - Erro na geração de extrato(gera-extrato.php)", $e->getMessage());
    }
}
