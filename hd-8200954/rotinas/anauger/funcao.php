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
                                                'Gera��o de Pedido de Pe�a', 
                                                '{$mensagem}'
                                            )";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                pg_query($con,"ROLLBACK");
                return "ERRO: N�o foi poss�vel gravar o comunicado do posto.";
            } else {
                pg_query($con,"COMMIT");
                return "Comunicado gravado com sucesso.";
            }
        }
    }