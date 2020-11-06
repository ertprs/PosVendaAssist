<?php

try {
    #produto1 => 615544, DATA FABRI. => 01/11/2017, SERIE => '777888999777'  N.SERIE.ID => 157738499
        #abertura =>  2018-05-03, posto => 630970 -> OK

    #produto2 => 615546, DATA FABRI. => 01/01/2018, SERIE => '77114455223', N.SERIE.ID => 157738500
        #abertura => 2018-04-03, posto =>  630971 -> OK

    #produto3 => 615547, DATA FABRI. => 24/11/2017, SERIE => '8885541147', N.SERIE.ID => 157738501
        #abertura => 2017-12-03, posto => 117160 -> OK

    #produto4 => 615545, DATA FABRI. => 15/12/2017, SERIE => '878787878787', N.SERIE.ID => 157738502
        #abertura => 2018-01-15, posto => 630970 -> OK


    #produto5 => 615548, DATA FABRI. => 30/01/2018, SERIE => '0124890085', N.SERIE.ID => 157736557
        #abertura => 2018-06-15, posto => 630970 -> OK

    #produto6 => 615542, DATA FABRI. => 01/03/2018, SERIE => '1234567', N.SERIE.ID => 157736552
        #abertura => 2018-02-20, posto => 630971 -> OK


    #produto7 => 615541, DATA FABRI. => 11/04/2018, SERIE => '5645646566', N.SERIE.ID => 157738496
        #abertura => 2018-06-10, posto => 117160 -> OK


    #produto8 => 615543, DATA FABRI. => 19/10/2017, SERIE => '4545887322', N.SERIE.ID => 157738503
        #abertura => 2017-11-10, posto => 630971 -> OK
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_175/Extrato.php';
   
    $fabrica = 175;
    $data_digitacao = "2018-05-03 14:27:40.010612";
    $data_abertura  = "2018-05-03";
    $validada       = "2018-05-03 14:27:40.010612";
    $data_nf        = "2018-05-03";
    
    $posto = "630970";
    $produto = "615544";
    $serie_produto = "777888999777";
    
    $cidade = "MARILIA";
    $estado = "SP";
    $defeito_reclamado = "14951";
    $defeito_constatado = "31277";
    $consumidor_revenda = "C";
    $tipo_atendimento = "337";
    $status_checkpoint = "3";
    $mao_de_obra = "5";
    $peca = "2529040";

    for ($i=0; $i < 5; $i++) { 
        $res = @pg_query($con,"BEGIN TRANSACTION");
        $erro = "";
        $sql_insert = "
            INSERT INTO tbl_os (
                    fabrica,
                    data_digitacao,
                    posto,
                    data_abertura,
                    data_nf,
                    serie,
                    consumidor_cidade,
                    consumidor_estado,
                    produto,
                    defeito_reclamado,
                    defeito_constatado,
                    validada,
                    consumidor_revenda,
                    tipo_atendimento,
                    status_checkpoint
                ) VALUES (
                    $fabrica,
                    '$data_digitacao',
                    $posto,
                    '$data_abertura',
                    '$data_nf',
                    '$serie_produto',
                    '$cidade',
                    '$estado',
                    $produto,
                    $defeito_reclamado,
                    $defeito_constatado,
                    '$validada',
                    '$consumidor_revenda',
                    $tipo_atendimento,
                    $status_checkpoint
                ) RETURNING os";
        $res_insert = pg_query($con, $sql_insert);
        if (strlen(pg_last_error()) > 0){
            $erro = "erro";
            $msg_erro .= pg_last_error();
        }

        $os = pg_fetch_result($res_insert, 0, 0);

        $update = "UPDATE tbl_os set sua_os = '$os' WHERE fabrica = 175 AND os = $os ";
        $res_up = pg_query($con, $update);

        $sql = "INSERT INTO tbl_os_produto(
                    os,
                    produto,
                    serie,
                    mao_de_obra,
                    defeito_constatado,
                    data_input
                )VALUES(
                    $os,
                    $produto,
                    '$serie_produto',
                    '$mao_de_obra',
                    $defeito_constatado,
                    '$data_digitacao'
                )RETURNING os_produto ";
        $res = pg_query($con, $sql);

        $os_produto = pg_fetch_result($res, 0, 0);

        if (strlen(pg_last_error()) > 0){
            $erro = "erro";
            $msg_erro .= pg_last_error();
        }

        $sql_insert_o = "
                INSERT INTO tbl_os_item (
                    os_produto       ,
                    peca             ,
                    qtde             ,
                    servico_realizado,
                    preco            ,
                    digitacao_item   ,
                    fabrica_i        ,
                    posto_i          ,
                    produto_i        
                )VALUES(
                    $os_produto,
                    $peca,
                    1,
                    11334,
                    6,
                    '$data_digitacao',
                    $fabrica,
                    $posto,
                    $produto
                )";
        $res_insert_o = pg_query($con, $sql_insert_o);
        
        if (!empty($erro)){
            echo $msg_erro;
            pg_query($con, "ROLLBACK");
        }else{
            pg_query($con, "COMMIT");
        }
    }


} catch (Exception $e) {
    echo $e->getMessage();
}

