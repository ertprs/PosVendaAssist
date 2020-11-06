<?php

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

    $fabrica = 52;

    $sql = "SELECT  
        tbl_cliente_admin.cliente_admin,
        tbl_cliente_admin.cnpj,
        tbl_cliente_admin.nome,
        tbl_cliente_admin.endereco,
        tbl_cliente_admin.numero,
        tbl_cliente_admin.cep,
        tbl_cliente_admin.cidade,
        tbl_cliente_admin.estado 
    FROM 
        tbl_cliente_admin 
    LEFT JOIN tbl_admin ON tbl_admin.cliente_admin = tbl_cliente_admin.cliente_admin AND tbl_admin.fabrica = {$fabrica} 
    WHERE 
        tbl_cliente_admin.fabrica = {$fabrica}";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $linhas = pg_num_rows($res);

        for($i = 0; $i < $linhas; $i++){

            $cliente_admin = pg_fetch_result($res, $i, "cliente_admin");
            $cnpj          = pg_fetch_result($res, $i, "cnpj");
            $nome          = pg_fetch_result($res, $i, "nome");
            $endereco      = pg_fetch_result($res, $i, "endereco");
            $numero        = pg_fetch_result($res, $i, "numero");
            $cep           = pg_fetch_result($res, $i, "cep");
            $cidade        = pg_fetch_result($res, $i, "cidade");
            $estado        = pg_fetch_result($res, $i, "estado");

            // echo $cliente_admin." / ".$endereco." / ".$numero." / ".$cep." / ".$cidade." / ".$estado." \n ";

            if(strlen(trim($cep)) > 0){

                $sql_cep = "SELECT logradouro, bairro, cidade, estado FROM tbl_cep WHERE cep = '{$cep}'";
                $res_cep = pg_query($con, $sql_cep);

                if (pg_num_rows($res_cep) > 0) {

                    $end_cep    = pg_fetch_result($res_cep, 0, "logradouro");
                    $bairro_cep = pg_fetch_result($res_cep, 0, "bairro");
                    $cidade_cep = pg_fetch_result($res_cep, 0, "cidade");
                    $estado_cep = pg_fetch_result($res_cep, 0, "estado");

                    $sql_upt = "UPDATE tbl_cliente_admin SET cidade = '{$cidade_cep}', estado = '{$estado_cep}' WHERE cliente_admin = {$cliente_admin} AND fabrica = {$fabrica}";
                    $res_upt = pg_query($con, $sql_upt);

                    if(strlen(pg_last_error($con)) > 0){
                        echo "Erro ao localizar o CEP para o Cliente Admin - ".$cliente_admin." - ".$cnpj." - ".$nome." \n ";
                    }

                }

            }else{

                echo "Cliente Admin sem CEP - ".$cliente_admin." - ".$cnpj." - ".$nome." \n ";

            }

            if(strlen(trim($endereco)) > 0){

                if(strstr($endereco, ",") == true){

                    // echo $cliente_admin." tem o. \n ";

                    // echo $endereco." \n ";

                    list($end, $num) = explode(",", $endereco);

                    $end = trim($end);
                    $num = trim($num);

                    $sql_num = "SELECT numero FROM tbl_cliente_admin WHERE cliente_admin = {$cliente_admin} AND fabrica = {$fabrica}";
                    $res_num = pg_query($con, $sql_num);

                    $num_bd = pg_fetch_result($res_num, 0, "numero");

                    if(strlen(trim($num_bd)) == 0){

                        $sql_num = "UPDATE tbl_cliente_admin SET endereco = '{$end}', numero = '{$num}' WHERE cliente_admin = {$cliente_admin} AND fabrica = {$fabrica}";
                        $res_num = pg_query($con, $sql_num);

                    }

                }else{

                    // echo "sem virgula no endereço - ".$cliente_admin." \n ";

                }

            }

        }

    }

?>