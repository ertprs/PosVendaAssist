<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica     = 115;
$dia_mes     = date('d');

$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();
print($dia_mes);
if ($dia_mes == '01') {
// if ($dia_mes == '01') {
    /**
     * - Todo dia 01, esta rotina fará
     * a atualização dos postos NORDTECH,
     * para pagamento de mão-de-obra diferenciada
     */
    $sqlPostos = "
        SELECT  DISTINCT
                posto,
                tbl_tipo_posto.descricao AS descricao_tipo_posto
        FROM    tbl_os
        JOIN    tbl_os_extra        USING (os)
        JOIN    tbl_posto_fabrica   USING(posto,fabrica)
        JOIN    tbl_tipo_posto  USING(tipo_posto)
        WHERE   tbl_os.fabrica                  = $fabrica
        AND     tbl_os_extra.extrato            IS NULL
        AND     tbl_os.excluida                 IS NOT TRUE
        AND     tbl_tipo_posto.posto_interno    IS NOT TRUE
        AND     tbl_tipo_posto.ativo            IS TRUE
        AND     tbl_os.finalizada <= CURRENT_DATE - INTERVAL '10 days'
	AND     (tbl_posto_fabrica.parametros_adicionais::jsonb->>'categoria_manual' = 'f'::text OR tbl_posto_fabrica.parametros_adicionais::jsonb->>'categoria_manual' IS NULL)
  ORDER BY      posto
    ";

    $resPostos = pg_query($con,$sqlPostos);

    while ($postos = pg_fetch_object($resPostos)) {
        $sqlCategoria = "
            SELECT  COUNT(DISTINCT tbl_os.os)                                                                                                                                                                               AS total_os,
                    COUNT(DISTINCT tbl_os.os) FILTER( WHERE tbl_os.data_conserto::date - tbl_os.data_abertura <= 30)                                                                                                        AS abertas_menos_30,
                    COUNT(DISTINCT tbl_os.os) FILTER(WHERE tbl_os.data_fechamento - tbl_os.data_conserto::date < 5)                                                                                                         AS fechadas_menos_5,
                    COUNT(tbl_os_item.os_item) FILTER(WHERE tbl_os_item.digitacao_item::date - tbl_os.data_abertura <= 3)                                                                                                   AS itens_ate_3,
                    COUNT(tbl_os_item.os_item) FILTER(WHERE tbl_os_item.digitacao_item::date - tbl_os.data_abertura > 3 AND tbl_os_item.digitacao_item::date - tbl_os.data_abertura <= 5)                                   AS itens_ate_5,
                    COUNT(tbl_os_item.os_item) FILTER(WHERE tbl_os_item.digitacao_item::date - tbl_os.data_abertura > 5)                                                                                                    AS itens_mais_5,
                    COUNT(DISTINCT tbl_os.os) FILTER(WHERE lower(tbl_os.consumidor_email) = lower(tbl_posto_fabrica.contato_email) AND tbl_os.consumidor_email IS NOT NULL AND tbl_posto_fabrica.contato_email IS NOT NULL) AS email_identico,
                    COUNT(tbl_faturamento_item.faturamento_item) FILTER(WHERE tbl_servico_realizado.troca_produto IS NOT TRUE)                                                                                              AS itens_faturados,
                    COUNT(OSA.os_status)                                                                                                                                                                                    AS total_auditoria,
                    COUNT(OSR.os_status)                                                                                                                                                                                    AS total_reprovada
            FROM    tbl_os
            JOIN    tbl_os_produto          USING(os)
            JOIN    tbl_os_item             USING(os_produto)
            JOIN    tbl_os_extra            ON  tbl_os.os                       = tbl_os_extra.os
            JOIN    tbl_posto_fabrica       ON  tbl_os.posto                    = tbl_posto_fabrica.posto
                                            AND tbl_posto_fabrica.fabrica       = $fabrica
            JOIN    tbl_servico_realizado   ON  tbl_os_item.servico_realizado   = tbl_servico_realizado.servico_realizado
       LEFT JOIN    tbl_faturamento_item    ON  tbl_os_item.os_item             = tbl_faturamento_item.os_item
       LEFT JOIN    tbl_os_status OSA       ON  tbl_os.os                       = OSA.os
                                            AND OSA.fabrica_status              = $fabrica
                                            AND OSA.status_os                   = 62
       LEFT JOIN    tbl_os_status OSR       ON  tbl_os.os                       = OSR.os
                                            AND OSR.fabrica_status              = $fabrica
                                            AND OSR.status_os                   = 81
            WHERE   tbl_os.fabrica          = $fabrica
            AND     tbl_os.posto            = ".$postos->posto."
            AND     tbl_os_extra.extrato    IS NULL
            AND     tbl_os.excluida         IS NOT TRUE
            AND     tbl_os.finalizada <= CURRENT_DATE - INTERVAL '10 days'
        ";

        $resCategoria = pg_query($con,$sqlCategoria);

        $total_os           = pg_fetch_result($resCategoria,0,total_os);
        $abertas_menos_30   = pg_fetch_result($resCategoria,0,abertas_menos_30);
        $fechadas_menos_5   = pg_fetch_result($resCategoria,0,fechadas_menos_5);
        $itens_ate_3        = pg_fetch_result($resCategoria,0,itens_ate_3);
        $itens_ate_5        = pg_fetch_result($resCategoria,0,itens_ate_5);
        $itens_mais_5       = pg_fetch_result($resCategoria,0,itens_mais_5);
        $email_identico     = pg_fetch_result($resCategoria,0,email_identico);
        $itens_faturados    = pg_fetch_result($resCategoria,0,itens_faturados);
        $total_auditoria    = pg_fetch_result($resCategoria,0,total_auditoria);
        $total_reprovada    = pg_fetch_result($resCategoria,0,total_reprovada);

        /*
         * - Se o Posto ter o mesmo email que o
         * consumidor, não consegue participar
         * do restante da classificação
         */

        if ($email_identico > 0) {
            $novaCategoria[$postos->posto]['lvl'] = "standard";
            $novaCategoria[$postos->posto]['motivo'] = "email";

            continue;
        }

        /*
         * - Se existir OS com peças
         * incluídas após 5 dias da abertura
         */

        if ($itens_mais_5 > 0) {
            $novaCategoria[$postos->posto]['lvl'] = "standard";
            $novaCategoria[$postos->posto]['motivo'] = "ItemMais5";
            $novaCategoria[$postos->posto]['dias'] = $itens_mais_5;

            continue;
        } else if ($itens_ate_3 > 0 || $itens_ate_5 > 0) {

            /*
             * - Comparação de quantos dias de diferença
             * entre a abertura da OS e o lançamento das peças
             */

            if ($itens_ate_5 > 0) {
                $novaCategoria[$postos->posto]['lvl'] = "master";

            }

            if ($itens_ate_3 > 0 && $itens_ate_5 == 0) {
                $novaCategoria[$postos->posto]['lvl'] = "premium";
            }
        }

        /*
         * - Se existir OS com data de fechamento
         * mmenor que 30 dias da abertura
         */

        if ($abertas_menos_30 >= 0) {

            $pcAbertas = ($abertas_menos_30 * 100) / $total_os;

            if ($pcAbertas == 100 && $novaCategoria[$postos->posto] == "premium") {
                $novaCategoria[$postos->posto]['lvl'] = "premium";
            } else if ($pcAbertas >= 90 && $pcAbertas < 100) {
                $novaCategoria[$postos->posto]['lvl'] = "master";
            } else if ($pcAbertas < 90) {
                $novaCategoria[$postos->posto]['lvl'] = "standard";
                $novaCategoria[$postos->posto]['motivo'] = "AbertaMenos30";
                $novaCategoria[$postos->posto]['menos30'] = $abertas_menos_30;
                $novaCategoria[$postos->posto]['totalOs'] = $total_os;
                $novaCategoria[$postos->posto]['result'] = $pcAbertas;
                continue;
            }
        }

        /*
         * - Se existir OS com data de fechamento
         * menor que 5 dias do conserto
         */

        if ($fechadas_menos_5 >= 0) {

            $pcFechadas = ($fechadas_menos_5 * 100) / $total_os;

            if ($pcFechadas >= 90 && $novaCategoria[$postos->posto] == "premium") {
                $novaCategoria[$postos->posto]['lvl'] = "premium";
            } else if ($pcFechadas >= 70 && $pcFechadas < 90) {
                $novaCategoria[$postos->posto]['lvl'] = "master";
            } else if ($pcFechadas < 70){
                $novaCategoria[$postos->posto]['lvl'] = "standard";
                $novaCategoria[$postos->posto]['motivo'] = "FechadaMenos5";
                continue;
            }
        }

        /*
         * - Se existir OS que entrou
         * em intervenções técnicas
         */

        if ($total_auditoria > 0) {
            $pcReprova = ($total_reprovada * 100) / $total_auditoria;

            if ($pcReprova <= 10 && $novaCategoria[$postos->posto] == "premium") {
                $novaCategoria[$postos->posto]['lvl'] = "premium";
            } else if ($pcReprova > 10 && $pcReprova <= 20) {
                $novaCategoria[$postos->posto]['lvl'] = "master";
            } else if ($pcReprova > 20) {
                $novaCategoria[$postos->posto]['lvl'] = "standard";
                $novaCategoria[$postos->posto]['motivo'] = "TotalAuditoria";
                continue;
            }
        }

        /*
         * - Somente Postos do tipo ASSISTÊNCIA TÉCNICA
         * pode ser PREMIUM. Postos de SERVIÇO serão rebaixados
         */

        if ($novaCategoria[$postos->posto] == "premium" && $postos->descricao_tipo_posto == "Posto de Serviço") {
            $novaCategoria[$postos->posto] = "master";
        }
    }

    /*
     * - Atualização do tbl_posto_fabrica
     * com a nova categoria e o acréscimo do tempo em que
     * permanece na categoria
     */

    foreach ($novaCategoria as $posto => $dados) {
        $sqlAtt = "
            SELECT  tbl_posto_fabrica.parametros_adicionais::JSONB->>'ultima_categoria' AS ultima_categoria,
                    tbl_posto_fabrica.parametros_adicionais::JSONB->'tempo'             AS meses_categoria
            FROM    tbl_posto_fabrica
            WHERE   posto   = $posto
            AND     fabrica = $fabrica
        ";
        $resAtt = pg_query($con,$sqlAtt);

        $ultima_categoria   = pg_fetch_result($resAtt,0,ultima_categoria);
        $meses_categoria    = pg_fetch_result($resAtt,0,meses_categoria);
        pg_query($con,"BEGIN TRANSACTION");

		$sqlUpd = "
                UPDATE  tbl_posto_fabrica
                SET     parametros_adicionais = parametros_adicionais::JSONB - 'manual_mes'
                WHERE   posto   = $posto
                AND     fabrica = $fabrica;
            ";
        $resUpd = pg_query($con,$sqlUpd);
        if ($ultima_categoria == $dados['lvl']) {
            $meses_categoria++;

            $sqlUpd = "
                UPDATE  tbl_posto_fabrica
                SET     parametros_adicionais = JSONB_SET(parametros_adicionais::JSONB - 'manual','{tempo}','".$meses_categoria."')
                WHERE   posto   = $posto
                AND     fabrica = $fabrica
            ";
        } else {
            $sqlUpd = "
                UPDATE  tbl_posto_fabrica
                SET     parametros_adicionais = JSONB_SET(
                                                    JSONB_SET(
                                                        JSONB_SET(
                                                            JSONB_SET(parametros_adicionais::JSONB  - 'manual','{tempo}','0')::JSONB,'{ultima_categoria}','\"".$dados['lvl']."\"'
                                                        )::JSONB,'{anterior_categoria}','\"".$ultima_categoria."\"'
                                                    )::JSONB,'{anterior_tempo}','".$meses_categoria."'),
                        categoria = '".$dados['lvl']."'
                WHERE   posto   = $posto
                AND     fabrica = $fabrica
            ";
        }
        $resUpd = pg_query($con,$sqlUpd);

        if (pg_last_error($con)) {
            echo "Erro no posto: $posto".pg_last_error($con)."\n";
            pg_query($con,"ROLLBACK TRANSACTION");
            continue;
        }

        pg_query($con,"COMMIT TRANSACTION");
    }

    $phpCron->termino();
}
