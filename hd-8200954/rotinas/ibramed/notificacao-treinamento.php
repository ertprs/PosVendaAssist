<?php
/**
 * 2018.07.12
 * @author  Lucas Bicalleto
 * @version 1.0
 *
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/communicator.class.php';
include dirname(__FILE__) . '/../../class/ComunicatorMirror.php';
include dirname(__FILE__) . '/../funcoes.php';

if (empty($login_fabrica)){
    $login_fabrica = 175;
}

/*********************** BUSCANDO OS TREINAMENTOS ***********************/
$sql_busca = "SELECT DISTINCT
                tbl_treinamento.treinamento,
                tbl_treinamento.treinamento_tipo,
                tbl_treinamento.prazo_inscricao,
                tbl_treinamento.data_input,
                tbl_treinamento.linha,
                ARRAY_TO_STRING(array_agg(DISTINCT(tbl_linha.linha)), ', ', null)         AS linhas,
                ARRAY_TO_STRING(array_agg(DISTINCT(tbl_produto.produto)), ', ', null)     AS produtos,
                ARRAY_TO_STRING(array_agg(DISTINCT(tbl_posto_fabrica.posto)), ', ', null) AS postos
            FROM tbl_treinamento
                LEFT JOIN tbl_treinamento_produto ON tbl_treinamento_produto.treinamento = tbl_treinamento.treinamento    
                LEFT JOIN tbl_linha               ON tbl_linha.linha                     = tbl_treinamento_produto.linha
                LEFT JOIN tbl_produto             ON tbl_produto.produto                 = tbl_treinamento_produto.produto
                     JOIN tbl_posto_fabrica       ON tbl_posto_fabrica.fabrica           = {$login_fabrica}
            WHERE   (
                        tbl_treinamento.treinamento_tipo          = 12
                        AND tbl_treinamento.prazo_inscricao::DATE = CURRENT_DATE
                    ) OR 
                    (
                        tbl_treinamento.treinamento_tipo     = 13
                        AND tbl_treinamento.data_input::DATE = (CURRENT_DATE - INTERVAL '1' DAY)
                    )
                AND tbl_treinamento.fabrica   = {$login_fabrica}
                AND tbl_posto_fabrica.fabrica = {$login_fabrica}
            GROUP BY
                tbl_treinamento.treinamento,
                tbl_treinamento.treinamento_tipo,
                tbl_treinamento.prazo_inscricao,
                tbl_treinamento.data_input,
                tbl_treinamento.linha;";
$res_busca = pg_query($con,$sql_busca);
$msg_erro  = pg_last_error($con);

if (!strlen($msg_erro) > 0){

    /******************* CLASSE/ TITULO EMAIL /CORPO EMAIL *******************/
    $comunicatorMirror = new ComunicatorMirror();
    $titulo_email      = "";
    $corpo_email       = "";

    for ($i=0; $i<pg_num_rows($res_busca); $i++)
    {
        $postos   = pg_fetch_result($res_busca,$i,'postos');
        $postos   = explode(",", $postos);
        $linhas   = pg_fetch_result($res_busca,$i,'linhas');
        $produtos = pg_fetch_result($res_busca,$i,'produtos');
        if (empty($produtos)){
            $produtos = 0;
        }
        if (empty($linhas)){
            $linhas = 0;
        }

        /******************* POSTOS *******************/
        foreach ($postos AS $posto)
        { 
            $sql_posto = "SELECT DISTINCT
                            tbl_posto_fabrica.contato_email AS posto_email,
                            tbl_tecnico.email               AS tecnico_email
                        FROM tbl_posto_fabrica
                            INNER JOIN tbl_tecnico             ON tbl_tecnico.posto     = {$posto}
                            INNER JOIN tbl_posto               ON tbl_posto.posto       = tbl_posto_fabrica.posto                    
                            INNER JOIN tbl_posto_linha         ON tbl_posto_linha.posto = tbl_posto_fabrica.posto 
                            INNER JOIN tbl_treinamento_produto ON tbl_treinamento_produto.produto IN ({$produtos}) 
                                                               OR tbl_treinamento_produto.linha   IN ({$linhas}) 
                        WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                            AND tbl_posto_fabrica.posto = {$posto};";                           

            $res_posto      = pg_query($con,$sql_posto);
            $msg_erro_posto = pg_last_error($con);

            if (!strlen($msg_erro_posto) > 0)
            {
                for ($i2=0; $i2<pg_num_rows($res_posto); $i2++)
                {
                    $tecnico_email = pg_fetch_result($res_posto, $i2, 'tecnico_email');
                    $posto_email   = pg_fetch_result($res_posto, $i2, 'posto_email');

                    if (empty($tecnico_email))
                    {
                        if (filter_var($posto_email, FILTER_VALIDATE_EMAIL)){
                            $destinatario = $posto_email;
                        }
                    }else if (empty($posto_email))
                    {
                        if (filter_var($tecnico_email, FILTER_VALIDATE_EMAIL)){
                            $destinatario = $tecnico_email;
                        }
                        
                    }

                    /******************* ENVIANDO EMAIL *******************/
                    try{
                        $comunicatorMirror->post($destinatario, utf8_encode("$titulo_email"), utf8_encode("$corpo_email"));
                    }catch (Exception $e) {
                        echo 'Exceção capturada: ',  $e->getMessage(), "\n";
                    }
                }
            }
        }
    }
}

?>