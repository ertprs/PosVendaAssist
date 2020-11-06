<?php 

$sql = "SELECT gera_pedido, troca_de_peca
        FROM tbl_servico_realizado
        WHERE fabrica = $login_fabrica
        AND servico_realizado = '$xservico'
        AND troca_de_peca IS TRUE
        AND (gera_pedido IS TRUE OR peca_estoque IS TRUE)";
$res = pg_query($con, $sql);

if(pg_num_rows($res) > 0){

    $sql = "SELECT servico_realizado
            FROM tbl_servico_realizado
            WHERE fabrica = $login_fabrica
            AND troca_de_peca IS TRUE
            AND peca_estoque IS TRUE";
    $res = pg_query($con, $sql);

    $novo_servico = pg_fetch_result($res, 0, 'servico_realizado');
    $tipo_estoque = "estoque";

    /*Verificar se o posto possui a peзa em estoque*/
    $sql = "SELECT qtde
            FROM tbl_estoque_posto
            WHERE peca = $xpeca
            AND posto = $login_posto
            AND fabrica = $login_fabrica
            AND tipo = '$tipo_estoque'";
    $res = pg_query($con, $sql);

    /* Se tiver Qtde no estoque */
    if(pg_num_rows($res) > 0){
        /* Qtde no estoque */
        $qtde_estoque = pg_fetch_result($res, 0, 'qtde');

        /* Verifica se hб movimento para aquela peзa com o pedido */
        $sql_verifica_movimento = "SELECT peca, qtde_saida
                                    FROM tbl_estoque_posto_movimento
                                    WHERE
                                    peca = $xpeca
                                    AND posto = $login_posto
                                    AND fabrica = $login_fabrica
                                    AND tipo = '$tipo_estoque'
                                    AND os = $os
                                    AND os_item = $xos_item";
        $res_verifica_movimento = pg_query($con, $sql_verifica_movimento);

        /* Se tiver movimentaзгo */
        if(pg_num_rows($res_verifica_movimento) > 0){
            $qtde_pecas_movimento = pg_fetch_result($res_verifica_movimento, 0, 'qtde_saida');
        }else{
            $qtde_pecas_movimento = 0;
        }

        /*Verifica se a quantidade movimentaзгo й da mesma que estб sendo enviada*/
        $sql = "SELECT peca
                FROM tbl_estoque_posto_movimento
                WHERE fabrica = $login_fabrica
                AND posto = $login_posto
                AND os = $os
                AND peca = $xpeca
                AND qtde_saida = $xqtde
                AND tipo = '$tipo_estoque'";
        $resS = pg_query($con, $sql);

        if(pg_num_rows($resS) == 0){

            if($qtde_pecas_movimento != $xqtde){

                /* Se a qtde do estoque for maior do ele estб passando e ainda nгo haver movimentaзгo.. insere na tbl_estoque_posto_movimentacao */
                if($qtde_estoque >= $xqtde && $qtde_pecas_movimento == 0){

                    $sql_posto_movimento = "INSERT INTO tbl_estoque_posto_movimento
                    (fabrica, posto, os, peca, qtde_saida, os_item, tipo,obs,data) VALUES
                    ($login_fabrica, $login_posto, $os, $xpeca, $xqtde, $xos_item, '$tipo_estoque','Saнda automбtica, peзa solicitada em Ordem de Serviзo OS: $os',current_date)";
                    $res_posto_movimento = pg_query($con, $sql_posto_movimento);

                    // Atualiza a quantidade da peзa no estoque
                    $sql_qtde_update = "UPDATE tbl_estoque_posto
                                        SET qtde = qtde - $xqtde
                                        WHERE
                                        fabrica = $login_fabrica
                                        AND posto = $login_posto
                                        AND tipo = '$tipo_estoque'
                                        AND peca = $xpeca";
                    $res_servico_update = pg_query($con, $sql_qtde_update);

                    /*Altera o serviзo realizado da peзa*/
                    $update_servico_realizado = "UPDATE tbl_os_item
                                                    SET servico_realizado = $novo_servico
                                                    WHERE tbl_os_item.os_item = $xos_item";
                    $res_update_servico_realizado = pg_query($con, $update_servico_realizado);
                }
            }
        }
    }
}




?>