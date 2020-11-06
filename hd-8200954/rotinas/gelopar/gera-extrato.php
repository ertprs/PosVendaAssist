<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

define('ENV', 'dev');
$fabrica        = 85;
$fabrica_nome   = "GELOPAR";
$dia_mes        = date('d');
$dia_extrato    = date('Y-m-d H:i:s');

$arquivos = "/tmp";


/*
* Cron Class
*/
$phpCron = new PHPCron($fabrica, __FILE__);
$phpCron->inicio();

/*
* Log Class
*/
$logClass = new Log2();
$logClass->adicionaLog(array("titulo" => "Log de erro - Geração de Extrato GELOPAR")); // Titulo
#$logClass->adicionaEmail("helpdesk@telecontrol.com.br");
$logClass->adicionaEmail("william.brandino@telecontrol.com.br");

/**
 * Postos que terão seus extratos gerados
 */
$sqlPosto = "
        SELECT  DISTINCT
                tbl_posto_fabrica.posto,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto_fabrica.fabrica,
                CURRENT_DATE                                AS dia_extrato,
                (CURRENT_DATE - INTERVAL '60 days')::DATE   AS dia_fatura
        FROM    tbl_posto_fabrica
        JOIN    tbl_os USING(posto,fabrica)
        WHERE   tbl_posto_fabrica.fabrica = $fabrica
        AND     tbl_posto_fabrica.posto <> 6359
        AND     tbl_os.finalizada       IS NOT NULL
        AND     (
                    tbl_os.excluida     IS FALSE
                OR  tbl_os.excluida     IS NULL
                )
        AND     tbl_os.data_fechamento  <= CURRENT_DATE
        AND     tbl_os.finalizada::date <= CURRENT_DATE
  ORDER BY      tbl_posto_fabrica.posto
";

$resPosto = pg_query($con,$sqlPosto);

while ($postos = pg_fetch_object($resPosto)) {
    try {
        pg_query($con,"BEGIN TRANSACTION");
        $sqlFechaExt = "SELECT fn_fechamento_extrato(".$postos->posto.",".$postos->fabrica.",'".$postos->dia_extrato."')";
        $resFechaExt = pg_query($con,$sqlFechaExt);

        if (pg_last_error($con)) {
            $msg_erro .= "<br>Erro ao fechar extrato: Posto:".$postos->codigo_posto." - ".pg_last_error($con);
        } else {

            $sqlExtrato = "
                SELECT  extrato
                FROM    tbl_extrato
                WHERE   fabrica             = $fabrica
                AND     posto               = ".$postos->posto."
                AND     data_geracao::date  = CURRENT_DATE
            ";
            $resExtrato = pg_query($con,$sqlExtrato);
            $extrato = pg_fetch_result($resExtrato,0,extrato);

            $sqlCalcula = "SELECT fn_calcula_extrato(".$postos->fabrica.",$extrato)";
            $resCalcula = pg_query($con,$sqlCalcula);

            if (pg_last_error($con)) {
                $msg_erro .= "<br>Erro ao calcular extrato: Extrato: ".$extrato." - ".pg_last_error($con);
            }

            $sqlLGR = "
                UPDATE  tbl_faturamento_item
                SET     extrato_devolucao = $extrato
                FROM    tbl_peca,
                        tbl_faturamento,
                        tbl_extrato
                WHERE   tbl_peca.peca                           = tbl_faturamento_item.peca
                AND     tbl_faturamento.posto                   = tbl_extrato.posto
                AND     tbl_faturamento.fabrica                 = tbl_extrato.fabrica
                AND     tbl_faturamento.faturamento             = tbl_faturamento_item.faturamento
                AND     tbl_faturamento.fabrica                 = $fabrica
                AND     tbl_faturamento.emissao                 >='2016-01-01'
                AND     tbl_faturamento.emissao                 <='".$postos->dia_fatura."'
                AND     tbl_faturamento.cancelada               IS NULL
                AND     tbl_faturamento_item.extrato_devolucao  IS NULL
                AND     tbl_peca.devolucao_obrigatoria          IS TRUE
                AND     (
                            tbl_faturamento.cfop ILIKE '59%'
                        OR  tbl_faturamento.cfop ILIKE '69%'
                        )
                AND     tbl_extrato.extrato = $extrato";
//                     echo $sqlLGR;exit;
            $resLGR = pg_query($con,$sqlLGR);

            if (pg_last_error($con)) {
                $msg_erro .= "<br>Erro ao gravar valor de extrato em faturamento: Extrato: ".$extrato;
            } else {
                $sqlLGR2 = "
                    INSERT INTO tbl_extrato_lgr (
                        extrato,
                        posto,
                        peca,
                        qtde
                    )
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
                ";
                $resLGR2 = pg_query($con,$sqlLGR2);

                if (pg_last_error($con)) {
                    $msg_erro .= "<br>Erro ao gravar LGR: Extrato: ".$extrato;
                }
            }

        }
        if (strlen($msg_erro)) {
            throw new Exception($msg_erro);
        }
        pg_query($con,"COMMIT TRANSACTION");
    } catch (Exception $ex) {
        pg_query($con,"ROLLBACK TRANSACTION");
        $arrMsgErro = $ex->getMessage();
    }
}


if(count($arrMsgErro) > 0 ){
    enviaEmailLog($logClass, $arrMsgErro);
}

/*
 * Cron Término
 */
$phpCron->termino();

function enviaEmailLog($logClass, $arrMsgErro){

    $logClass->adicionaLog($arrMsgErro);

    if($logClass->enviaEmails() == "200"){
        echo "Log de erro enviado com Sucesso!";
    }else{
        echo $logClass->enviaEmails();
    }

    $fp = fopen("tmp/{$fabrica_nome}/extratos/log-erro.text", "a");
    fwrite($fp, "Data Log: " . date("d/m/Y") . "<br>");
    fwrite($fp, $msg_erro_arq . "<br> <br>");
    fclose($fp);
}
