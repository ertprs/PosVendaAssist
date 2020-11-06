<?php
$antes_valida_campos = "valida_numero_de_serie_lofra";

function valida_numero_de_serie_lofra () {
    global $campos, $msg_erro, $login_fabrica, $con;
    unset($campos["produtos"]["__modelo__"]);
    $produtos    = $campos["produtos"];

    foreach ($produtos as $key => $rows) {

        $sql = "SELECT produto
                FROM tbl_produto WHERE fabrica_i = $login_fabrica 
                AND referencia = '".$rows["referencia"]."'
                AND numero_serie_obrigatorio IS TRUE";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0 && empty($rows["serie"])) {
            $msg_erro["msg"][] = traduz("Preencha o Nº de Série");
            $msg_erro["campos"][] = "produto_".$key;
        }

    }
}