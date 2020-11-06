<?php 
 function gerarComunicadoPosto($posto, $mensagem){
        global $con, $fabrica;
        if (!empty($posto)) {

            pg_query($con,"BEGIN");

            $sql = "INSERT INTO tbl_comunicado (
                                                fabrica,
                                                posto,
                                                obrigatorio_site, 
                                                tipo, 
                                                ativo, 
                                                descricao, 
                                                mensagem 
                                            ) VALUES (
                                                {$fabrica},
                                                {$posto}, 
                                                't', 
                                                'Com. Unico Posto', 
                                                't',
                                                'Geração de Pedido de Peça', 
                                                '{$mensagem}'
                                            )";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                pg_query($con,"ROLLBACK");
                return "ERRO: Não foi possível gravar o comunicado do posto.";
            } else {
                pg_query($con,"COMMIT");
                return "Comunicado gravado com sucesso.";
            }
        }
    }