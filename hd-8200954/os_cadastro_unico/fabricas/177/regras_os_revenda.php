<?
$valida_os_revenda_itens = "valida_os_revenda_itens_anauger";
function valida_os_revenda_itens_anauger()
{
    global $campos, $msg_erro, $login_fabrica, $con;
    $array_msg_erro = array();
    foreach ($campos['produtos'] as $key => $array_produto) {
        if ($key !== "__modelo__" && !empty($array_produto['id'])) {
            
            $id_produto = $array_produto['id'];
            $numero_serie_produto = $array_produto['serie'];
            
            if (empty($array_produto['qtde'])) {
                $array_msg_erro[$key] = "É necessário informar uma quantidade para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                $msg_erro["campos"][] = "produto_".$key;
            }

            $sql_lote = "
                SELECT json_field('lote',parametros_adicionais) AS lote 
                FROM tbl_produto 
                WHERE tbl_produto.fabrica_i = $login_fabrica
                AND tbl_produto.produto = $id_produto ";
            $res_lote = pg_query($con,$sql_lote);

            if (pg_num_rows($res_lote) > 0){
                $produto_lote = pg_fetch_result($res_lote, 0, 'lote');

                if ($produto_lote == 't' AND empty($array_produto['lote'])){
                    $array_msg_erro[$key] = "É necessário informar o lote para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                    $msg_erro["campos"][] = "produto_".$key;
                }
            }

            /*
            if (empty($numero_serie_produto)) {
                $array_msg_erro[$key] = "É necessário informar o número de série para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                $msg_erro["campos"][] = "produto_".$key;
            }else{
                $sql = "SELECT tbl_numero_serie.numero_serie
                        FROM tbl_numero_serie 
                        WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                        AND tbl_numero_serie.produto = {$id_produto}
                        AND tbl_numero_serie.serie = '{$numero_serie_produto}'";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0){
                    $array_msg_erro[$key] = "Número de série inválido para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                    $msg_erro["campos"][] = "produto_".$key;
                }
            }
            */
        }
    }

    if (count($array_msg_erro) > 0) {
        throw new Exception(implode("<br />", $array_msg_erro));
    }
}

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
        for ($i=0; $i < pg_num_rows($res_tdocs); $i++) { 
            $type_id = pg_fetch_result($res_tdocs, $i, 'type_id');
            $descricao = pg_fetch_result($res_tdocs, $i, 'descricao');

            if ($type_id == "notafiscal"){
                $xdescricao = preg_replace("/\D/","",$descricao);
                $notas_anexo[] = $xdescricao;
            }
        }
    }
    
    foreach ($produtos as $key => $value) {
        if (empty($value["id"])){
            continue;
        }
        if ($value['nota_fiscal'] != 'semNota'){
            $notas_lancadas[] = $value['nota_fiscal'];
        }
    }
    $notas_lancadas = array_unique($notas_lancadas);
    $notas_sem_anexo = array_diff($notas_lancadas, $notas_anexo);
    
    if (count($notas_sem_anexo) > 0){
        foreach ($notas_sem_anexo as $key => $value) {
            $msg_erro["msg"][] = "Anexar imagem da nota fiscal $value";
        }
    }
}
