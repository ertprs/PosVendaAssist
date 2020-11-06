<?
$valida_anexo = "valida_anexo_aquarius";

function valida_anexo_aquarius() {
    global $campos, $msg_erro, $login_fabrica, $con, $anexos_inseridos;

    $posto = $campos['posto_id'];

    if (!empty($posto)) {
        $sql = "SELECT tp.tipo_posto FROM tbl_posto_fabrica pf INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica} WHERE pf.fabrica = {$login_fabrica} AND pf.posto = {$posto} AND tp.posto_interno IS TRUE";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) == 0) {
            $qtde_anexo = 1;
            $anexos_inseridos = $anexos_inseridos();

            if(count($anexos_inseridos) < $qtde_anexo){
                $msg_erro["msg"][] = traduz("Anexo é obrigatório.");
            }
        }
    }
}
