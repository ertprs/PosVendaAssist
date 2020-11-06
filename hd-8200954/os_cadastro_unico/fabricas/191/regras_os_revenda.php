<?php

$valida_anexo = "valida_anexo_revenda";
function valida_anexo_revenda()
{
    global $campos, $msg_erro, $login_fabrica, $con;

    $produtos = $campos["produtos"];
    $notas_lancadas = array();
    $anexo_chave = $campos["anexo_chave"];

    $sql_tdocs = "SELECT json_field('typeId',obs) AS type_id,
                        json_field('descricao',obs) AS descricao
                        FROM tbl_tdocs 
                        WHERE tbl_tdocs.fabrica = $login_fabrica
                        AND tbl_tdocs.situacao = 'ativo'
                        AND tbl_tdocs.hash_temp = '$anexo_chave' ";
    $res_tdocs = pg_query($con,$sql_tdocs);
    $notas_anexo = array();
    if (pg_num_rows($res_tdocs) > 0){
        $nf_geral = array();
        for ($i=0; $i < pg_num_rows($res_tdocs); $i++) {
            $type_id = pg_fetch_result($res_tdocs, $i, 'type_id');
            $descricao = pg_fetch_result($res_tdocs, $i, 'descricao');

            if ($type_id == "notafiscal"){
                $xdescricao = preg_replace("/\D/","",$descricao);
                $notas_anexo[] = $xdescricao;

                if (empty($xdescricao)){
                    $nf_geral[] = "nota_geral";
                }
            }
        }
    }

    foreach ($produtos as $key => $value) {
        if (empty($value["id"])){
            continue;
        }
        $notas_lancadas[] = $value['nota_fiscal'];
    }

    $notas_lancadas = array_unique($notas_lancadas);
    $notas_sem_anexo = array_diff($notas_lancadas, $notas_anexo);

    if (count($notas_sem_anexo) > 0 AND empty($nf_geral)){
        foreach ($notas_sem_anexo as $key => $value) {
            if ($value != "semNota") {
                $msg_erro["msg"][] = "Anexar imagem da nota fiscal $value";
            }
        }
    }
}

?>
