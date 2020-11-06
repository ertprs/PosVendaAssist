<?php

$attCfg = array(
    'labels' => array(
        0 => 'Anexar',
        1 => 'Nota Fiscal',
        2 => 'Etiqueta',
        3 => 'Produto (1)',
        4 => 'Produto (2)',
        5 => 'Produto (3)',
    )
   
);

$fabrica_qtde_anexos = count($attCfg['labels']);
$GLOBALS['attCfg'] = $attCfg;

$regras['anexo']['function'] = array('validaAnexosMallory');
// Valida anexos
function validaAnexosMallory() {
    global $campos, $attCfg, $login_fabrica, $con;

    $sql_bloqueados = "SELECT JSON_FIELD('anexo_obrigatorio', campo_obrigatorio), JSON_FIELD('anexos', informacoes_adicionais) from tbl_tipo_solicitacao where tipo_solicitacao = ".$campos["tipo_solicitacao"]." AND fabrica = $login_fabrica" ;
    $res_bloqueados = pg_query($con, $sql_bloqueados);

    if(pg_num_rows($res_bloqueados) > 0){
        $campo_obrigatorio      = json_decode(pg_fetch_result($res_bloqueados, 0, 0), true);
        $informacoes_adicionais = json_decode(pg_fetch_result($res_bloqueados, 0, 1), true);
    }

    if (empty($campos['anexo']) && !empty($_POST['anexo_chave'])) {
        $sql_anexos = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND hash_temp = '".$_POST['anexo_chave']."'";
        $res_anexos = pg_query($con, $sql_anexos);
        if (pg_num_rows($res_anexos) > 0) {
            $anexos_hash = pg_fetch_all($res_anexos);
        }
    }

        for ($i=0; $i < count($informacoes_adicionais['anexos']); $i++) {

            if ($campo_obrigatorio['anexo_obrigatorio'][$i] == 1 && ((empty($campos['anexo'][$i]) && empty($anexos_hash[$i]['tdocs'])) || ($campos['anexo'][$i] == 'null' && empty($anexos_hash[$i]['tdocs'])))) {
                $msg .= 'Anexo <strong>' . $informacoes_adicionais['anexos'][$i] . '</strong> é obrigatório.<br />';
            }

        }

    
    if ($msg)
        throw new Exception ($msg);
}


?>