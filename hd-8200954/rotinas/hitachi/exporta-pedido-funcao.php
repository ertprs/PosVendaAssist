<?php 

    function updateExportaPedido($pedido,$fabrica = null){
        global $login_fabrica , $con;

        if (strlen($fabrica)) {
            if (!strlen($login_fabrica) and !strlen($fabrica) ) {
                return  "Informe a fabrica.";
                exit;
            }else{
                $login_fabrica = $fabrica ;
            }
        } 


        if (empty($pedido)) {
             return "Pedido não informado";
             exit;
        }

        $sql = "UPDATE tbl_pedido
                    SET exportado = current_date ,
                    status_pedido = 2
            WHERE   pedido        = $pedido
            AND     fabrica       = $login_fabrica";

        $res = pg_query($con, $sql);

        if(pg_affected_rows($res)==1){
            return true;
            exit;
        }else{
            return false;
            exit;
        }

    }

function exportaPedido($pedido,$fabrica = null) {
    global $login_fabrica , $con;
    if (strlen($fabrica)) {
        if (!strlen($login_fabrica) and !strlen($fabrica) ) {
            return  "Informe a fabrica.";
            exit;
        }else{
            $login_fabrica = $fabrica ;
        }
    } 

    if (empty($pedido)) {
        return  "Erro ao exportar pedido #1";
        exit;
    } else {

       $sql  = " SELECT    TO_CHAR(tbl_pedido.data,'YYYYMMDD')       AS data,
                            tbl_posto.cnpj,
                            tbl_tipo_pedido.descricao,
                            tbl_condicao.codigo_condicao
                FROM tbl_pedido
                INNER JOIN    tbl_posto_fabrica  ON tbl_pedido.posto = tbl_posto_fabrica.posto
                INNER JOIN    tbl_posto          ON tbl_posto.posto = tbl_pedido.posto
                INNER JOIN    tbl_tipo_pedido    ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                INNER JOIN    tbl_condicao       ON tbl_condicao.condicao = tbl_pedido.condicao
                WHERE    tbl_pedido.pedido = $pedido
                -- AND    tbl_pedido.posto    <> 6359
                AND    tbl_pedido.exportado IS NULL
                AND    tbl_pedido.status_pedido <> 14
                AND    tbl_pedido.fabrica  = $login_fabrica";

        $res = pg_query($con, $sql);
       
        if (pg_num_rows($res) == 0) {
            return  "Erro ao exportar pedido #2";
            exit; 
        } else {
            $res = pg_fetch_array($res);

            $data_emissao     = $res["data"];
            $cnpj             = $res["cnpj"];
            $descricao_pedido = $res["descricao"];
            $codigo_condicao  = $res["codigo_condicao"];

            $sql = " SELECT
                          tbl_pedido_item.pedido_item,
                          tbl_pedido_item.qtde_cancelada,
                          tbl_pedido_item.qtde,
                          tbl_pedido_item.preco,
                          tbl_os_produto.produto,
                          tbl_peca.referencia,
                          tbl_peca.peca
                    FROM    tbl_pedido
                  INNER JOIN    tbl_pedido_item     ON tbl_pedido_item.pedido    = tbl_pedido.pedido
                  INNER JOIN    tbl_peca            ON tbl_peca.peca             = tbl_pedido_item.peca
                  LEFT  JOIN    tbl_os_item         ON tbl_os_item.pedido_item   = tbl_pedido_item.pedido_item
                  LEFT  JOIN    tbl_os_produto      ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                  WHERE tbl_pedido.pedido  = $pedido
                  AND   tbl_pedido.fabrica = $login_fabrica";

            $res_item = pg_query($con , $sql);
            $rest = pg_affected_rows($res);
            
       
            if (pg_num_rows($res_item) == 0) {
                return  "Erro ao exportar pedido #3";
                exit;
            }

            $nome_fabrica = \Posvenda\Fabrica::fabricaNome($login_fabrica);
            $nome_fabrica = strtolower($nome_fabrica);
            ///home/hitachi/pos-vendas/telecontrol-hitachi/pedidos
            // $dir = "/tmp/".$nome_fabrica;
            $dir = "/home/".$nome_fabrica."/pos-vendas/telecontrol-".$nome_fabrica."/pedidos";
            
            if (!is_dir($dir)) {
                if (!mkdir($dir)) {
                    return  'Erro ao exportar pedido #4';
                    exit;
                } 

                if (!chmod($dir, 0777)) {
                    return  'Erro ao exportar pedido #5';
                    exit;
                }

            }

            $file = $dir."/pedido-$pedido.txt";
            $fp   = fopen($file, 'w');
            
            fputs($fp, "$data_emissao");
            fputs($fp, "\t$cnpj");
            fputs($fp, "\t$descricao_pedido");
            fputs($fp, "\t$pedido");
            fputs($fp, "\t$codigo_condicao\n");
            // fputs($fp, "");

            
            while ($itens = pg_fetch_array($res_item)) {
                

                $pedido_item    = $itens['pedido_item'];
                $qtde_cancelada = $itens['qtde_cancelada'];
                $qtde           = $itens['qtde'];
                $preco          = $itens['preco'];
                $referencia     = $itens['referencia'];
               
                $qtde = $qtde - $qtde_cancelada;

                fputs($fp, "$referencia");
                fputs($fp, "\t$qtde");
                fputs($fp, "\t$pedido_item");
                fputs($fp, "\t$preco");
                fputs($fp, "\r\n");


            }
        
            fclose($fp);
        
        
            $teste = updateExportaPedido($pedido,$login_fabrica);
            if ( $teste  != true){
                return  "Pedido não exportado ";
                exit;
            }else{
                return true;
                exit;
            }
        }

        return true;
        exit;

    }
}

php?>
