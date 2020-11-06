<?
$valida_os_revenda_itens = "valida_os_revenda_itens_ibramed";
function valida_os_revenda_itens_ibramed()
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
            if (empty($numero_serie_produto)) {
                $array_msg_erro[$key] = "É necessário informar o número de série para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                $msg_erro["campos"][] = "produto_".$key;
            }else{
                $sql = "SELECT  tbl_numero_serie.data_venda,
                                tbl_produto.garantia
                        FROM    tbl_numero_serie
                        JOIN    tbl_produto USING(produto)
                        WHERE tbl_numero_serie.fabrica = {$login_fabrica}
                        AND tbl_numero_serie.produto = {$id_produto}
                        AND tbl_numero_serie.serie = '{$numero_serie_produto}'";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0){
                    $array_msg_erro[$key] = "Número de série inválido para o produto {$array_produto['referencia']} - {$array_produto['descricao']}";
                    $msg_erro["campos"][] = "produto_".$key;
               
                } else {
                    $data_abertura = $campos['data_abertura'];
                    $data_venda    = pg_fetch_result($res, 0, 'data_venda');
                    $garantia      = pg_fetch_result($res, 0, 'garantia');
                    $data_nf       = $array_produto['data_nf'];
                    $nota_fiscal   = $array_produto['nota_fiscal'];

                    if ($nota_fiscal != "semNota") {
                        $fora_garantia = false;

                        if (!empty($data_nf) && !empty($data_venda)) {
                            $fora_garantia = (strtotime($data_venda) < strtotime(formata_data($data_abertura))) ? true : false;
                        }

                        if (empty($data_venda) || $fora_garantia === true) {
                            $fora_garantia = (strtotime(formata_data($data_nf)." +{$garantia} months") < strtotime(formata_data($data_abertura))) ? true : false;
                        }
                    } else {
                        $fora_garantia = (strtotime($data_venda) < strtotime(formata_data($data_abertura))) ? true : false;
                    }

                    if ($fora_garantia === true) {

                        $array_msg_erro[$key] = "O Produto \"{$array_produto['referencia']} - {$array_produto['descricao']}\", esta fora de garantia";
                        $msg_erro["campos"][] = "produto_".$key;
                    }
                }
            }
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
