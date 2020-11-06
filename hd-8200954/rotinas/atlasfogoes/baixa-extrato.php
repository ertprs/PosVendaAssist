<?php
/**
* baixa-extrato.php
*  - Realiza a baixa dos extratos
*    por base de arquivos importados
* @author William Ap. Brandino
* @since 2014-05-30
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV', 'producao');

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    $bug            = '';
    $login_fabrica  = 74;
    $fabrica        = 'atlas';
    $arquivo_log    = 'baixa_extratos';
    $log            = 2;
    $arquivos       = "/tmp";
    $phpCron    = new PHPCron($login_fabrica, __FILE__);
    $phpCron->inicio();

    $origem = "/home/atlas/atlas-telecontrol";

    $tmp     = "/tmp/atlas";

    if (ENV == 'teste') {
        $origem = dirname(__FILE__) ."/entrada";
    }

    $arquivo = "$origem/baixa-extratos.txt";
    date_default_timezone_set('America/Sao_Paulo');
    $now = date('Ymd_His');

    $arquivo_err = "$tmp/{$arquivo_log}-{$now}.err";
    $arquivo_log = "$tmp/{$arquivo_log}-{$now}.log";
    system ("mkdir $tmp/ 2> /dev/null ; chmod 0777 $tmp/" );

    if (file_exists($arquivo) and (filesize($arquivo) > 0)) {
        $fp = fopen ($arquivo,"r");
        $conteudo_arquivo = fread($fp, filesize($arquivo));
        $linhas = explode("\n",$conteudo_arquivo);
        fclose($fp);

        $sql = "DROP TABLE IF EXISTS atlas_baixa_extrato";
        $result = pg_query($con,$sql);

        $sql = "CREATE TABLE atlas_baixa_extrato (
                    texto_extrato           varchar(20),
                    texto_valor_total       varchar(20),
                    texto_valor_acrescimo   varchar(20),
                    texto_valor_desconto    varchar(20),
                    texto_valor_liquido     varchar(20),
                    texto_data_vencimento   varchar(10),
                    texto_data_pagamento    varchar(10),
                    texto_numero_nf         varchar(20),
                    texto_numero_aut        varchar(20),
                    texto_observacao        text
                )";
        $result = pg_query($con,$sql);
        $erro.= pg_last_error();

        $linhas = file_get_contents($arquivo);
        $linhas = explode("\n",$linhas);

        $erro = $msg_erro;

        foreach($linhas as $linha){
            list(   $extrato,
                    $valor_total,
                    $acrescimo,
                    $desconto,
                    $liquido,
                    $vencimento,
                    $pagamento,
                    $nota_fiscal,
                    $autorizacao,
                    $obs
                ) = explode("|",$linha);

            $res = pg_query($con,"BEGIN");
            $sql = "INSERT INTO atlas_baixa_extrato (
                        texto_extrato        ,
                        texto_valor_total    ,
                        texto_valor_acrescimo,
                        texto_valor_desconto ,
                        texto_valor_liquido ,
                        texto_data_vencimento,
                        texto_data_pagamento ,
                        texto_numero_nf      ,
                        texto_numero_aut     ,
                        texto_observacao
                    ) VALUES (
                        '$extrato'      ,
                        '$valor_total'  ,
                        '$acrescimo'    ,
                        '$desconto'     ,
                        '$liquido'      ,
                        '$vencimento'   ,
                        '$pagamento'    ,
                        '$nota_fiscal'  ,
                        '$autorizacao'  ,
                        '$obs'
                    );
            ";
            $res = pg_query($con,$sql);
            $msg_erro .= pg_errormessage($con);

            if(!empty($msg_erro)){
                $res = pg_query($con,"ROLLBACK");
                $erro .= $msg_erro;
                echo $erro;
            } else {
                $res = pg_query($con,"COMMIT");
            }
        }
    }
    $msg_erro = $erro;

    $sql = "UPDATE  atlas_baixa_extrato
            SET     texto_extrato         = TRIM(texto_extrato)                 ,
                    texto_valor_total     = TRIM(texto_valor_total)             ,
                    texto_valor_acrescimo = TRIM(texto_valor_acrescimo)         ,
                    texto_valor_desconto  = TRIM(texto_valor_desconto)          ,
                    texto_valor_liquido   = TRIM(texto_valor_liquido)           ,
                    texto_data_vencimento = TRIM(texto_data_vencimento)         ,
                    texto_data_pagamento  = TRIM(texto_data_pagamento)          ,
                    texto_numero_nf       = lpad(TRIM(texto_numero_nf),9,'0')   ,
                    texto_numero_aut      = TRIM(texto_numero_aut)              ,
                    texto_observacao      = TRIM(texto_observacao)
    ";
    $res = pg_query($con,$sql);

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN extrato int4";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN valor_total FLOAT";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN valor_acrescimo FLOAT";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN valor_desconto FLOAT";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN valor_liquido FLOAT";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN data_vencimento DATE";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN data_pagamento DATE";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "UPDATE  atlas_baixa_extrato
            SET     extrato         = texto_extrato::int4,
                    valor_total     = REPLACE(texto_valor_total,',','.')::numeric,
                    valor_acrescimo = REPLACE(texto_valor_acrescimo,',','.')::numeric,
                    valor_desconto  = REPLACE(texto_valor_desconto,',','.')::numeric,
                    valor_liquido   = REPLACE(texto_valor_liquido,',','.')::numeric,
                    data_vencimento = TO_DATE(texto_data_vencimento,'YYYY-MM-DD'),
                    data_pagamento  = TO_DATE(texto_data_pagamento,'YYYY-MM-DD')
    ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    /**
    * - Verificação da veracidade do extrato enviado
    * 1º -> Verifica-se o extrato sendo da Atlas
    * 2º -> Verifica-se o extrato não estando baixado
    *   Tendo uma dessas negações, o extrato é retirado
    */

    $sql = "ALTER TABLE atlas_baixa_extrato ADD COLUMN fabrica int4";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    $sql = "UPDATE  atlas_baixa_extrato
            SET     fabrica = (
                        SELECT  tbl_extrato.fabrica
                        FROM    tbl_extrato
                   LEFT JOIN    tbl_extrato_pagamento USING (extrato)
                        WHERE   atlas_baixa_extrato.extrato = tbl_extrato.extrato
                        AND     tbl_extrato_pagamento.extrato IS NULL
            );
    ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    /**
    * - Tendo verificado, exclui os extratos inválidos
    */
    $sql = "DELETE  FROM atlas_baixa_extrato
            WHERE   fabrica IS NULL
    ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);


    /**
    * - Início da importação
    */
    $res = pg_query($con,"BEGIN");
    $sql = "INSERT INTO tbl_extrato_pagamento (
                extrato,
                valor_total,
                acrescimo,
                desconto,
                valor_liquido,
                data_vencimento,
                data_pagamento,
                autorizacao_pagto,
                nf_autorizacao,
                obs
            )
            SELECT  extrato,
                    valor_total,
                    valor_acrescimo,
                    valor_desconto,
                    valor_liquido,
                    data_vencimento,
                    data_pagamento,
                    texto_numero_aut,
                    texto_numero_nf,
                    texto_observacao
            FROM    atlas_baixa_extrato
            JOIN    tbl_extrato USING (extrato)
            WHERE   tbl_extrato.fabrica = $login_fabrica
            ;
    ";
    $res = pg_query($con,$sql);
    $msg_erro .= pg_errormessage($con);

    if(!empty($msg_erro)){
        $res = pg_query($con,"ROLLBACK");
        $erro .= $msg_erro;
        $erro .= "\n Erros importação $data";
        echo $erro;
        $fp = fopen($arquivo_err,"w");
        fwrite($fp,$erro);
        fclose($fp);
    } else {
        $res = pg_query($con,"COMMIT");
    }

    system ("cp $origem/baixa-extratos.txt $origem/bkp/baixa-extratos-$data.txt;");
    system ("mv $origem/baixa-extratos.txt $tmp/baixa-extratos-$data.txt;");

    $phpCron->termino();

}catch(Exception $e) {
    echo $e->getMessage();
}
?>
