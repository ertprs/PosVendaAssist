<?php 
if (in_array(verifica_tipo_atendimento(), ["Garantia Balcão", "Garantia com deslocamento"])) {
    $valida_garantia = "valida_garantia_syllent";
}


function verifica_tipo_atendimento() {
    global $con, $login_fabrica;

    if (getValue("tipo_atendimento")) {
        $sqlTipo = "SELECT descricao 
                      FROM tbl_tipo_atendimento 
                     WHERE fabrica = {$login_fabrica} 
                       AND tipo_atendimento=".getValue("tipo_atendimento");
        $resTipo = pg_query($con, $sqlTipo);
        $descricaoTipo = pg_fetch_result($resTipo, 0, 'descricao');
        return $descricaoTipo;
    }

}

function valida_garantia_syllent() {
    global $con, $login_fabrica, $campos, $msg_erro;

    unset($campos["produtos"]["__modelo__"]);
    
    if (count($campos["produtos"])) {
    
        foreach ($campos["produtos"] as $key => $value) {
         
            $xdata_compra       = $campos["data_nf"];
            $data_fabricacao    = $value["data_fabricacao"];
            $data_abertura      = $campos["data_abertura"];
            $produto            = $value["id"];

            if (strlen($xdata_compra) > 0 && strlen($data_fabricacao) > 0) {

                if (strtotime($xdata_compra) > strtotime($data_fabricacao)) {
                    $data_compra   = $xdata_compra;
                } else {
                    $data_compra   = $data_fabricacao;
                }

            } else if (strlen($xdata_compra) > 0 && strlen($data_fabricacao) == 0) {
                $data_compra   = $xdata_compra;
            }  else if (strlen($xdata_compra) == 0 && strlen($data_fabricacao) > 0) {
                $data_compra   = $data_fabricacao;
            }

            if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
                $sql = "SELECT garantia
                          FROM tbl_produto 
                         WHERE fabrica_i = {$login_fabrica} 
                           AND produto = {$produto}";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                 
                    $garantia = pg_fetch_result($res, 0, "garantia");

                    if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                            $msg_erro["msg"][] = traduz("Produto fora de garantia");
                    } 
                }
            }
        }
    }

}
