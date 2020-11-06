<?php
$regras["os|nota_fiscal"]["obrigatorio"] = false;
$regras["os|data_compra"]["obrigatorio"] = false;
$regras["os|tipo_atendimento"]["function"] = array("valida_campos_tipo_atendimento");

if (getValue("os[consumidor_revenda]") == 'C' || $consumidor_revenda == 'C') {
    $regras["revenda|nome"]["obrigatorio"] = false;
    $regras["revenda|cnpj"]["obrigatorio"] = false;
    $regras["revenda|cep"]["obrigatorio"] = false;
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
    $regras["revenda|bairro"]["obrigatorio"] = false;
    $regras["revenda|endereco"]["obrigatorio"] = false;
    $regras["revenda|numero"]["obrigatorio"] = false;
    $regras["revenda|complemento"]["obrigatorio"] = false;
    $regras["revenda|telefone"]["obrigatorio"] = false;
}

$regras["consumidor|telefone"]["obrigatorio"] = true;
$regras["consumidor|celular"]["obrigatorio"] = true;
$regras["consumidor|email"]["obrigatorio"] = true;
$regras["produto|serie"]["obrigatorio"] = true;

$regras_pecas = array(
    "lista_basica" => true,
    "servico_realizado" => true,
    'serie_peca' => true
);

if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}

if (strlen(trim(getValue("os[tipo_atendimento]"))) > 0){
    $sql = "SELECT tipo_atendimento
            FROM tbl_tipo_atendimento 
            WHERE fabrica = {$login_fabrica} 
            AND tipo_atendimento =".getValue("os[tipo_atendimento]")." 
            AND fora_garantia IS NOT TRUE";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {
        $regras["os|aparencia_produto"]["obrigatorio"] = true;
        $regras["os|acessorios"]["obrigatorio"] = true;
    
        // $funcoes_fabrica = array(
        //     'verifica_estoque_peca'
        // );
    }
}

$valida_garantia = "valida_garantia_ibramed";
function valida_garantia_ibramed() {
    global $con, $login_fabrica, $campos, $msg_erro, $os;
    
    $tipo_atendimento       = $campos["os"]["tipo_atendimento"];
    $numero_serie_produto   = $campos["produto"]["serie"];
    $produto                = $campos["produto"]["id"];
    $data_abertura          = $campos["os"]["data_abertura"];
    $data_compra            = $campos["os"]["data_compra"];
    $anexo_chave            = $campos["anexo_chave"];

    $sql = "SELECT tipo_atendimento
            FROM tbl_tipo_atendimento 
            WHERE fabrica = {$login_fabrica} 
            AND tipo_atendimento = {$tipo_atendimento}
            AND fora_garantia IS NOT TRUE";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0 AND !empty($numero_serie_produto)){
        $sql_garantia = "
            SELECT  tbl_numero_serie.data_venda,
                    tbl_produto.garantia
            FROM    tbl_numero_serie
            JOIN    tbl_produto USING(produto)
            WHERE   tbl_numero_serie.fabrica = {$login_fabrica}
            AND     tbl_numero_serie.serie = '{$numero_serie_produto}'
            AND     tbl_produto.fabrica_i = $login_fabrica
            AND     tbl_produto.produto = $produto";
        $res_garantia = pg_query($con, $sql_garantia);
        
        if (pg_num_rows($res_garantia) > 0){
            $fora_garantia_venda = true;
            
            $data_venda = pg_fetch_result($res_garantia, 0, 'data_venda');
            $garantia = pg_fetch_result($res_garantia, 0, 'garantia');
            
            if (!empty($data_venda)) {
                $fora_garantia_venda = (strtotime("$data_venda+{$garantia} months") < strtotime(formata_data($data_abertura))) ? true : false;
            }
            
            if ($fora_garantia_venda === true){
                $fora_garantia = (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) ? true : false;
                
                if ($fora_garantia !== true){
                    if (!empty($anexo_chave)){
                        if (!empty($os)){
                            $cond_tdocs = "AND tbl_tdocs.referencia_id = $os";
                        }else{
                            $cond_tdocs = "AND tbl_tdocs.hash_temp = '$anexo_chave'";
                        }

                        $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
                                            FROM tbl_tdocs 
                                            WHERE tbl_tdocs.fabrica = $login_fabrica
                                            AND tbl_tdocs.situacao = 'ativo'
                                            $cond_tdocs";
                        $res_tdocs = pg_query($con,$sql_tdocs);
                        if (pg_num_rows($res_tdocs) > 0){
                            $typeId = pg_fetch_all_columns($res_tdocs);
                            if (!in_array('notafiscal', $typeId)){
                                $msg_erro["msg"][] = traduz("Obrigatório o anexo da nota fiscal do produto");
                            }
                        }else{
                            $msg_erro["msg"][] = traduz("Obrigatório o anexo da nota fiscal do produto");
                        }
                    }else{
                        $msg_erro["msg"][] = traduz("Obrigatório o anexo da nota fiscal do produto");
                    }
                }else{
                    throw new Exception("Produto fora da garantia");
                }
            
                if ($campos['os']['tipo_atendimento'] == 'Garantia' || $campos['os']['tipo_atendimento'] == 337) {
                    $msg_erro["campos"][] = "os[nota_fiscal]";
                    $msg_erro["campos"][] = "os[data_compra]";
                }   
            }
        }else{
            $msg_erro["msg"][] = traduz("Produto fora da garantia");
            $msg_erro["campos"][] = "produto[serie]";
        }
    }
}

$antes_valida_campos = "antes_valida_campos";
$valida_anexo = "";
function antes_valida_campos() {
    global $campos, $os, $con, $login_fabrica, $login_unico, $valida_garantia, $regras, $msg_erro, $auditoria_bloqueia_pedido, $_POST, $_GET;

    $tipo_atendimento       = $campos['os']['tipo_atendimento'];
    $defeito_constatado     = $campos["produto"]["defeito_constatado"];
    $produto                = $campos["produto"]["id"];
    $numero_serie_produto   = $campos["produto"]["serie"];
    $qtde_disparos_produto  = $campos["os"]["quantidade_disparos"];
    $anexo_chave            = $campos["anexo_chave"];
    $array_pecas            = $campos["produto_pecas"];
    $tecnico                = $campos["os"]["tecnico"];
    $fora_garantia          = false;
    $tem_peca               = false;
    $id_os                  = $_GET["os_id"];
    $posto_id               = $campos["posto"]["id"];

    $sql_tecnico_logado = "
        SELECT 
            tecnico, 
            nome,
            codigo_externo,
            ativo 
        FROM tbl_tecnico 
        WHERE posto = {$posto_id} 
        AND ativo IS TRUE
        AND codigo_externo = '{$login_unico}'
        AND fabrica IS NULL";
    $res_tecnico_logado = pg_query($con, $sql_tecnico_logado);

    if (pg_num_rows($res_tecnico_logado) > 0){
        $tecnico_logado = pg_fetch_result($res_tecnico_logado, 0, 'tecnico');
    }

    if (empty($tecnico) AND strlen($defeito_constatado) > 0){
        $msg_erro["msg"][] = traduz("Selecione um técnico para lançar o defeito constatado");
        $msg_erro["campos"][] = "os['tecnico']";
    }else if (!empty($os) AND !empty($tecnico) AND !empty($tecnico_logado)){
        if ($tecnico != $tecnico_logado){
            throw new Exception(traduz("Somente o técnico da OS pode lançar o defeito constatado"));
        }
    }

    if (!empty($produto) AND !empty($numero_serie_produto)){
        $sql_serie_produto = "SELECT tbl_numero_serie.numero_serie
                                FROM tbl_numero_serie
                                WHERE tbl_numero_serie.produto = $produto
                                AND tbl_numero_serie.fabrica = $login_fabrica
                                AND tbl_numero_serie.serie = '$numero_serie_produto' ";
        $res_serie_produto = pg_query($con, $sql_serie_produto);
        
        if (pg_num_rows($res_serie_produto) == 0){
            $msg_erro["msg"][] = traduz("Número de série inválido para o produto");
            $msg_erro["campos"][] = "produto[serie]";
        }
    }

    $sql = "SELECT tipo_atendimento 
            FROM tbl_tipo_atendimento 
            WHERE fabrica = {$login_fabrica} 
            AND tipo_atendimento = {$tipo_atendimento}
            AND fora_garantia IS NOT TRUE";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0){
        $fora_garantia = true;
    }
    
    if ($fora_garantia === true){
        if (!empty($produto)){
            $sql_produto = "SELECT tbl_produto.capacidade
                    FROM tbl_produto 
                    WHERE tbl_produto.fabrica_i = {$login_fabrica}
                    AND tbl_produto.produto = {$produto}
                    AND COALESCE(tbl_produto.capacidade, 0) > 0";
            $res_produto = pg_query($con, $sql_produto);
            
            if (pg_num_rows($res_produto) > 0){
                $capacidade = pg_fetch_result($res_produto, 0, 'capacidade');
                
                if (empty($qtde_disparos_produto)){
                    $msg_erro["msg"][] = traduz("Preencha o campo quantidade de disparos");
                    $msg_erro["campos"][] = "quantidade_disparos";
                }else{
                    if ($qtde_disparos_produto > $capacidade){
                        $msg_erro["msg"][] = traduz("Produto fora de garantia pela quantidade de disparos");
                    }
                }
                
                if (!empty($anexo_chave)){

                    if (!empty($os)){
                        $cond_tdocs = "AND tbl_tdocs.referencia_id = $os";
                    }else{
                        $cond_tdocs = "AND tbl_tdocs.hash_temp = '$anexo_chave'";
                    }

                    $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
                                        FROM tbl_tdocs 
                                        WHERE tbl_tdocs.fabrica = $login_fabrica
                                        AND tbl_tdocs.situacao = 'ativo'
                                        $cond_tdocs";
                    $res_tdocs = pg_query($con,$sql_tdocs); 
                    
                    if (pg_num_rows($res_tdocs) > 0){
                        $typeId = pg_fetch_all_columns($res_tdocs);
                        if (!in_array('display', $typeId)){
                            $msg_erro["msg"][] = traduz("obrigatório o anexo do display do produto");
                        }
                    }else{
                        $msg_erro["msg"][] = traduz("obrigatório o anexo do display do produto");
                    }
                }else{
                    $msg_erro["msg"][] = traduz("obrigatório o anexo do display do produto");
                }
            }
        }
    }

    foreach ($array_pecas as $key => $value) {
        $peca                   = $value["id"];
        $referencia             = $value["referencia"];
        $qtde_disparos_pecas    = $value["quantidade_disparos"];
        $numero_serie           = $value["numero_serie"];

        if (empty($peca)){
            continue;
        }

        $tem_peca = true;
        if (!empty($tecnico)){

            if (!empty($os) AND $tecnico != $tecnico_logado){
                throw new Exception(traduz("Somente o técnico da OS pode lançar peças"));
            }

            if ($fora_garantia === true AND !empty($defeito_constatado)){
                $sql_peca = "SELECT tbl_peca.reducao,
                                tbl_peca.numero_serie_peca
                            FROM tbl_peca
                            WHERE tbl_peca.peca = $peca
                            AND tbl_peca.fabrica = $login_fabrica
                            AND COALESCE(tbl_peca.reducao, 0) > 0";
                $res_peca = pg_query($con, $sql_peca);

                if (pg_num_rows($res_peca) > 0){
                    $reducao = pg_fetch_result($res_peca, 0, 'reducao');
                    
                    if (empty($qtde_disparos_pecas)){
                        $msg_erro["msg"][] = traduz("Preencha o campo quantidade de disparos da peça")." ".$referencia;
                        $msg_erro["campos"][] = "produto_pecas[$key]";
                    }else{
                        if ($qtde_disparos_pecas > $reducao){
                            $msg_erro["msg"][] = traduz("Produto fora de garantia pela quantidade de disparos da peça")." ".$referencia;
                            $msg_erro["campos"][] = "produto_pecas[$key]";
                        }
                    }
                }
            }

            $sql_peca_serie = "SELECT tbl_peca.peca
                        FROM tbl_peca
                        WHERE tbl_peca.peca = $peca
                        AND tbl_peca.fabrica = $login_fabrica
                        AND tbl_peca.numero_serie_peca IS TRUE";
            $res_peca_serie = pg_query($con, $sql_peca_serie);
            
            if (pg_num_rows($res_peca_serie) > 0){
                if (empty($numero_serie)){
                    $msg_erro["msg"][] = traduz("Favor preencher o número de série da peça")." ".$referencia;
                    $msg_erro["campos"][] = "produto_pecas[$key]";
                }
		//Retirada validação até as regras serem definidas pela IBRAMED - 16/01/2019
		/*
		else{
                    $sql_serie = "SELECT numero_serie_peca
                            FROM tbl_numero_serie_peca
                            WHERE fabrica = $login_fabrica
                            AND peca = $peca
                            AND serie_peca = '$numero_serie'";
                    $res_serie = pg_query($con, $sql_serie);

                    if (pg_num_rows($res_serie) == 0){
                        $msg_erro["msg"][] = traduz("Número de série inválido para a peça")." ".$referencia;
                        $msg_erro["campos"][] = "produto_pecas[$key]";
                    }
                }
		*/
            }
        }else{
            throw new Exception(traduz("Selecione um técnico para lançar peças na os"));
        }
    }
    
    if ($tem_peca === true AND !empty($tipo_atendimento) AND !empty($id_os)){
        $sql = "SELECT tipo_atendimento FROM tbl_os WHERE os = $id_os AND fabrica = $login_fabrica";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0 ){
            $tipo_atendimento_os = pg_fetch_result($res, 0, 'tipo_atendimento');
            if ($tipo_atendimento_os != $tipo_atendimento){
                throw new Exception(traduz("Não é possível alterar o tipo de atendimento da os. Já existe peça lançada na os"));
            }
        }
    }
}

$valida_pecas = "valida_pecas_ibramed";
function valida_pecas_ibramed($nome = "produto_pecas") {
    global $con, $msg_erro, $login_fabrica, $regras_pecas, $campos, $os;
    if(verifica_peca_lancada(false) === true){

        $anexo_chave  = $campos["anexo_chave"];

        $pecas_os = array();
        $produto = $campos["produto"]["id"];
        /*$produto_serie = $campos["produto"]["serie"];

        $ordem_producao = substr($produto_serie, 0, 6);
        
        $sql_producao = "
            SELECT xxx.* 
            FROM (
                SELECT DISTINCT ON(xx.ordem_producao) xx.ordem_producao::float, CASE WHEN xx.proxima_ordem IS NULL THEN float8'+infinity' ELSE xx.proxima_ordem - 1 END AS proxima_ordem
                FROM (
                    SELECT x.ordem_producao, lt.ordem_producao::integer AS proxima_ordem FROM(
                        SELECT DISTINCT ordem_producao::integer
                        FROM tbl_lista_basica
                        WHERE fabrica = {$login_fabrica}
                        AND produto = {$produto}
                        ORDER BY ordem_producao ASC
                    ) x
                    LEFT JOIN tbl_lista_basica lt ON lt.ordem_producao::integer > x.ordem_producao AND lt.fabrica = {$login_fabrica} AND lt.produto = {$produto}
                    ORDER BY x.ordem_producao ASC, proxima_ordem ASC
                ) xx
                ORDER BY xx.ordem_producao, xx.proxima_ordem ASC
            ) xxx
            WHERE '{$ordem_producao}'::float BETWEEN xxx.ordem_producao AND xxx.proxima_ordem
        ";
        $res_producao = pg_query($con, $sql_producao);

        if (pg_num_rows($res_producao) > 0){
            $op_pesquisa = pg_fetch_result($res_producao, 0, 'ordem_producao');
            
            if (preg_match("/^0{1,}/", $ordem_producao, $zero_op)) {
                $op_pesquisa = $zero_op[0].$op_pesquisa;
            }
        } else {
            $msg_erro["msg"][] = traduz("Erro ao pesquisar lista básica do produto");
        }*/

        if (!empty($anexo_chave)){
                if (!empty($os)){
                    $cond_tdocs = "AND tbl_tdocs.referencia_id = $os";
                }else{
                    $cond_tdocs = "AND tbl_tdocs.hash_temp = '$anexo_chave'";
                }

                $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
                              FROM tbl_tdocs 
                              WHERE tbl_tdocs.fabrica = $login_fabrica
                              AND LOWER(tbl_tdocs.situacao) = 'ativo'
                              $cond_tdocs";
                $res_tdocs = pg_query($con,$sql_tdocs);
                if (pg_num_rows($res_tdocs) > 0){
                    $typeId = pg_fetch_all_columns($res_tdocs);
                }
        }

        if (!count($msg_erro["msg"])) {
            foreach ($campos[$nome] as $posicao => $campos_peca) {
                $peca       = $campos_peca["id"];
                $cancelada  = $campos_peca["cancelada"];
                $pedido     = $campos_peca["pedido"];
                $referencia = $campos_peca["referencia"];

                if (empty($peca)) {
                    continue;
                }

                if (!empty($peca) && empty($campos_peca["qtde"])) {
                    $msg_erro["msg"]["peca_qtde"] = traduz('informe.uma.quantidade.para.a.peca.%', null, null, $referencia);
                    $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                    continue;
                }

                $regra_validar = $regras_pecas;

                if(isset($campos_peca["defeito_peca"]) && empty($campos_peca["defeito_peca"])){
                    $msg_erro["msg"]["peca_qtde"] = traduz('favor.informar.o.defeito.da.peca.%', null, null, $referencia);
                    $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                    continue;
                }

                foreach ($regra_validar as $tipo_regra => $regra) {
                    switch ($tipo_regra) {
                        case 'lista_basica':
                            
                            $peca_qtde = $campos_peca["qtde"];

                            if ($regra == true && !empty($produto)) {
                                $sql = "SELECT qtde
                                        FROM tbl_lista_basica
                                        WHERE fabrica = {$login_fabrica}
                                        AND produto = {$produto}
                                        AND peca = {$peca};
                                        -- AND ordem_producao = '$op_pesquisa'";
                                $res = pg_query($con, $sql);

                                if (!pg_num_rows($res)) {
                                    if(strlen(trim($pedido))>0){
                                        continue;
                                    }
                                    $msg_erro["msg"][]    = traduz("Peça não consta na lista básica do produto");
                                    $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                                } else {
                                    $lista_basica_qtde = pg_fetch_result($res, 0, "qtde");

                                    if(array_key_exists($peca, $pecas_os)){
                                        $pecas_os[$peca]["qtde"] += $peca_qtde;
                                    }else{
                                        $pecas_os[$peca]["qtde"] = $peca_qtde;
                                    }

                                    if($cancelada > 0){
                                        $pecas_os[$peca]["qtde"] -= $cancelada;
                                    }

                                    if ($pecas_os[$peca]["qtde"] > $lista_basica_qtde) {
                                        $msg_erro["msg"]["lista_basica_qtde"] = traduz("Quantidade da peça maior que a permitida na lista básica");
                                        $msg_erro["campos"][]                 = "{$nome}[{$posicao}]";
                                    }
                                }
                            }
                            break;
                        case 'servico_realizado':
                            if ($regra === true && !empty($campos_peca["id"]) && empty($campos_peca["servico_realizado"])) {
                                $msg_erro["msg"]["servico_realizado"] = traduz("Selecione o serviço da peça".$cont);
                                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                            }
                            break;
                        case 'serie_peca':
                            if(strlen(trim($campos_peca['id'])) > 0 AND $regra === true){ //HD-3428297
                                $sql_serie = "SELECT tbl_peca.peca
                                              FROM tbl_peca
                                              WHERE peca = {$campos_peca['id']}
                                              AND fabrica = {$login_fabrica} 
                                              AND numero_serie_peca IS TRUE ";
                                $res_serie = pg_query($con, $sql_serie);
                                if(pg_num_rows($res_serie) > 0){
                                    
                                    if (strlen(trim($campos_peca["numero_serie"])) == 0) {
                                        $msg_erro["msg"][] = traduz("Preencha a série da peça");
                                        $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                                    }

                                    if (pg_num_rows($res_tdocs) > 0) {

                                        if (!in_array('peca', $typeId)){
                                            $msg_erro["msg"][] = traduz("Favor anexar a foto da etiqueta com a série da peça");
                                            $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                                        }

                                    } else {
                                        $msg_erro["msg"][] = traduz("Favor anexar a foto da etiqueta com a série da peça");
                                        $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                                    }
                                }
                            }
                        break;
                    }
                }
            }
        }
    }
}

function grava_os_fabrica() {
    global $campos;
    
    #$justificativa_adicionais = (strlen($campos["os"]["motivo_visita"]) > 0) ? array("motivo_visita" => utf8_encode($campos["os"]["motivo_visita"])) : array("motivo_visita" => "");
    #$justificativa_adicionais = json_encode($justificativa_adicionais);

    return array(
        "capacidade" => (!empty($campos["os"]["quantidade_disparos"])) ? $campos["os"]["quantidade_disparos"] : "null",
	"tecnico" => (!empty($campos["os"]["tecnico"])) ? $campos['os']['tecnico'] : "null"
    );
}

$grava_os_item_function = "grava_os_item_ibramed";
function grava_os_item_ibramed($os_produto, $subproduto = "produto_pecas") {

    global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

    if (function_exists("grava_custo_peca") ) {
        /**
         * A função grava_custo_peca deve ficar dentro do arquivo de regras fábrica
         * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
         */
        $custo_peca = grava_custo_peca();
        if($custo_peca==false){
            unset($custo_peca);
        }
    }
    
    if($historico_alteracao === true){
        $historico = array();
    }

    foreach ($campos[$subproduto] as $posicao => $campos_peca) {

        if (strlen($campos_peca["id"]) > 0) {
        
            if($historico_alteracao === true){
                include "$login_fabrica/historico_alteracao.php";
            }
            
            if (!empty($campos_peca['servico_realizado'])) {
                $sql = "SELECT troca_de_peca, peca_estoque FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
                $res = pg_query($con, $sql);

                $troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");
                $peca_estoque = pg_fetch_result($res, 0, "peca_estoque");
            }

            if ($troca_de_peca == "t") {
                $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$campos_peca['id']}";
                $res = pg_query($con, $sql);

                $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");

                if ($devolucao_obrigatoria == "t") {
                    $devolucao_obrigatoria = "TRUE";
                } else {
                    $devolucao_obrigatoria = "FALSE";
                }
            } else {
                $devolucao_obrigatoria = "FALSE";

                if (empty($campos_peca['servico_realizado'])) {
                    $campos_peca['servico_realizado'] = (empty($campos_peca['servico_realizado'])) ? "null" : $campos_peca['servico_realizado'];
                }

            }

            if ($peca_estoque == 't'){
                $devolucao_obrigatoria = "TRUE";
            }

            $login_admin = (empty($login_admin)) ? "null" : $login_admin;

            $campo_valor = $campos_peca['valor'];
            if (!empty($campo_valor)){
                $campo_valor = str_replace(".", "", $campo_valor);
                $campo_valor = str_replace(",", ".", $campo_valor);
                $campos_peca['valor'] = $campo_valor;
            }else{
                $campos_peca['valor'] = 0;
            }
            
            if (empty($campos_peca["os_item"])) {
                $sql = "INSERT INTO tbl_os_item
                        (
                            os_produto,
                            peca,
                            qtde,
                            servico_realizado,
                            peca_obrigatoria,
                            admin
                            ".(($grava_defeito_peca == true) ? ", defeito" : "")."
                            ".((strlen(trim($campos_peca['numero_serie'])) > 0) ? ", peca_serie" : "")."
                            ".((strlen(trim($campos_peca['valor'])) > 0) ? ", preco" : "")."
                            ".((strlen(trim($campos_peca['quantidade_disparos'])) > 0) ? ", porcentagem_garantia" : "")."
                            ".((!empty($campos_peca["componente_raiz"])) ? ", os_por_defeito" : "")."
                        )
                        VALUES
                        (
                            {$os_produto},
                            {$campos_peca['id']},
                            {$campos_peca['qtde']},
                            {$campos_peca['servico_realizado']},
                            {$devolucao_obrigatoria},
                            {$login_admin}
                            ".(($grava_defeito_peca == true) ? ", ".$campos_peca['defeito_peca'] : "")."
                            ".((strlen(trim($campos_peca['numero_serie'])) > 0) ? ", '".$campos_peca['numero_serie']."'" : "")."
                            ".((strlen(trim($campos_peca['valor'])) > 0) ? ", ".$campos_peca['valor'] : "")."
                            ".((strlen(trim($campos_peca['quantidade_disparos'])) > 0) ? ", ".$campos_peca['quantidade_disparos'] : "")."
                            ".((!empty($campos_peca["componente_raiz"])) ? ", '".$campos_peca['componente_raiz']."'" : "")."
                        )
                        RETURNING os_item";
                $acao = "insert";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar Ordem de Serviço #9".$sql);
                }

                $campos[$subproduto][$posicao]["os_item_insert"] = pg_fetch_result($res, 0, "os_item");
            } else {
                $sql = "SELECT tbl_os_item.os_item
                        FROM tbl_os_item
                        INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                        WHERE tbl_os_item.os_produto = {$os_produto}
                        AND tbl_os_item.os_item = {$campos_peca['os_item']}
                        AND tbl_os_item.pedido IS NULL
                        AND UPPER(tbl_servico_realizado.descricao) NOT IN('CANCELADO', 'TROCA PRODUTO')";
                $res = pg_query($con, $sql);

                if (verificaPecaCancelada($campos_peca["os_item"]) === true) {
                    continue;
                }

                if (verificaTrocaProduto($campos_peca["os_item"]) === true) {
                    continue;
                }

                if (pg_num_rows($res) > 0) {
                    if (strlen(trim($campos_peca["valor"])) > 0) {
                        $campo_valor = ", preco = {$campos_peca['valor']}";
                    } else {
                        $campo_valor = "";
                    }

                    if ($grava_defeito_peca == true) {
                        $campo_defeito = ", defeito = {$campos_peca['defeito_peca']}";
                    } else {
                        $campo_defeito = "";
                    }
                    
                    if (strlen(trim($campos_peca["serie_peca"])) > 0) {
                        $campo_serie = ", peca_serie = {$campos_peca['serie_peca']}";
                    } else {
                        $campo_serie = "";
                    }

                    if (strlen(trim($campos_peca["quantidade_disparos"])) > 0) {
                        $campo_quantidade_disparos = ", porcentagem_garantia = {$campos_peca['quantidade_disparos']}";
                    } else {
                        $campo_serie = "";
                    }

                    if (!empty($campos_peca["componente_raiz"])) {
                        $campo_componente_raiz = ", os_por_defeito = '{$campos_peca['componente_raiz']}'";
                    } else {
                        $campo_componente_raiz = "";
                    }

                    $sql = "UPDATE tbl_os_item SET
                                qtde = {$campos_peca['qtde']},
                                servico_realizado = {$campos_peca['servico_realizado']}
                                {$campo_valor}
                                {$campo_defeito}
                                {$campo_serie}
                                {$campo_componente_raiz}
                            WHERE os_produto = {$os_produto}
                            AND os_item = {$campos_peca['os_item']}";
                    $acao = "update";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar Ordem de Serviço #10 $sql");
                    }
                }
            }
        }
    }

    if (!empty($objLog)) {//logositem
        $objLog->retornaDadosSelect()->enviarLog($acao, "tbl_os_item", $login_fabrica."*".$os);
    }
    unset($objLog);

    if($historico_alteracao === true){

        if(count($historico) > 0){
            grava_historico($historico, $os, $campos["posto"]["id"], $login_fabrica, $login_admin);
        }
    }
}

function auditoria_os_reincidente_ibramed() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero, $os_reincidente_justificativa;

    $posto = $campos['posto']['id'];
    $data_abertura = $campos["os"]["data_abertura"];
    $data_abertura = formata_data($data_abertura);
    $numero_serie_produto = $campos["produto"]["serie"];
    $produto_id    = $campos["produto"]["id"];

    $sql = "SELECT  os
            FROM    tbl_os
            WHERE   fabrica         = {$login_fabrica}
            AND     os              = {$os}
            AND     os_reincidente  IS NOT TRUE
            AND     cancelada       IS NOT TRUE
    ";
    $res = pg_query($con, $sql);
    
    $os_reincidente_justificativa = false;
    if(pg_num_rows($res) > 0){
        $select = "SELECT tbl_os.os,
                ('$data_abertura' - tbl_os.data_abertura) AS intervalo
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                JOIN tbl_numero_serie ON tbl_numero_serie.produto = tbl_os.produto 
                    AND tbl_numero_serie.fabrica = {$login_fabrica}
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '149 days')
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.serie = '{$numero_serie_produto}'
                AND tbl_os.produto = {$produto_id}
                AND tbl_os.os < {$os}
                ORDER BY tbl_os.data_abertura DESC
                LIMIT 1";
        $resSelect = pg_query($con, $select);
        
        if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {

            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
            $intervalo = pg_fetch_result($resSelect, 0, 'intervalo');
            $intervalo = intval($intervalo);
            
            if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                if ($intervalo < 90){
                    $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                    if($busca['resultado']){
                        $auditoria_status = $busca['auditoria'];
                    }

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                            ({$os}, $auditoria_status, 'OS Reincidente por NÚMERO DE SÉRIE')";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço");
                    } else {
                        $os_reincidente_justificativa = true;
                    }
                }
                $os_reincidente = true;
            }
        }
    }
}

function auditoria_valor_adicional_ibramed(){
    global $con, $campos, $os, $login_fabrica;
    
    if(!empty($os) && count($campos["os"]["valor_adicional"]) > 0){
        
        if(verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais'", $os) === true){
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca["resultado"]){
                $auditoria = $busca["auditoria"];

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Valores Adicionais')";
                pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de valores adicionais para a OS");
                }else{
                    return true;
                }
            }else{
                throw new Exception("Erro ao buscar auditoria valores adicionais");
            }
        }
    }
}

$auditorias = array(
    "auditoria_os_reincidente_ibramed",
    "os_jornada_ibramed",
    "auditoria_peca_critica",
    "auditoria_troca_obrigatoria",
    "auditoria_pecas_excedentes",
    "auditoria_valor_adicional_ibramed"
);

function grava_os_reincidente_ibramed($os_reincidente_numero) {
    global $con, $login_fabrica, $os, $areaAdmin, $os_reincidente_justificativa;
    
    $sql = "UPDATE tbl_os SET os_reincidente = TRUE WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res = pg_query($con, $sql);

    $sql = "UPDATE tbl_os_extra SET os_reincidente = {$os_reincidente_numero} WHERE os = {$os}";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao lançar ordem de serviço reincidente");
    }

    if ($os_reincidente_justificativa === true) {
        header("Location: os_motivo_atraso.php?os={$os}&justificativa=ok");
    } else {
        header("Location: os_press.php?os={$os}");
    }
}

$grava_os_reincidente = "grava_os_reincidente_ibramed";

function os_jornada_ibramed(){

    global $con, $login_fabrica, $os, $campos, $_serverEnvironment;

    $data_abertura_os   = $campos["os"]["data_abertura"];
    $consumidor_cidade  = $campos["consumidor"]["cidade"];
    $consumidor_estado  = $campos["consumidor"]["estado"];
    $produto_id         = $campos["produto"]["id"];
    $produto_serie      = $campos["produto"]["serie"]; 
    $pecas_lancadas     = $campos["produto_pecas"];
    $tipo_atendimento   = $campos["os"]["tipo_atendimento"];
    
    $sql_serie_produto = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto_id} AND serie = '{$produto_serie}'";
    $res_serie_produto = pg_query($con, $sql_serie_produto);

    if (pg_num_rows($res_serie_produto) > 0){
        $produto_serie = pg_fetch_result($res_serie_produto, 0, 'numero_serie');
    }

    $sqlAtendimento = "
            SELECT tipo_atendimento
            FROM tbl_tipo_atendimento 
            WHERE fabrica = {$login_fabrica}
            AND fora_garantia IS NOT TRUE
            AND tipo_atendimento = {$tipo_atendimento}
            AND descricao = 'Garantia' ";
    $resAtendimento = pg_query($con, $sqlAtendimento);
    
    if (pg_num_rows($resAtendimento) > 0){

        $data_abertura_os = fnc_formata_data_pg($data_abertura_os);
        $whereJornada = array();
        
        if (!empty($consumidor_cidade) AND !empty($consumidor_estado)){
            $sql_cidade = "
                SELECT cidade 
                FROM tbl_cidade 
                WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) 
                AND UPPER(estado) = UPPER('{$consumidor_estado}')";
            $res_cidade = pg_query($con, $sql_cidade);

            if (pg_num_rows($res_cidade) > 0) {
                $cidade_id = pg_fetch_result($res_cidade, 0, "cidade");
            }
        } 
        
        foreach ($pecas_lancadas as $key => $value) {
            if (strlen($value["id"]) > 0) {
                if (strlen(trim($value["numero_serie"])) > 0){
                    $sql = "
                        SELECT numero_serie_peca
                        FROM tbl_numero_serie_peca 
                        WHERE peca = ".$value['id']."
                        AND serie_peca = '".$value['numero_serie']."'
                        AND fabrica = $login_fabrica ";
                    $res = pg_query($con, $sql);
                    if (pg_num_rows($res) > 0){
                        $numero_serie_peca = pg_fetch_result($res, 0, 'numero_serie_peca');

                        #1
                        if (!empty($produto_serie) AND !empty($consumidor_cidade) AND !empty($consumidor_estado)){
                            $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND numero_serie_produto = {$produto_serie} AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca})";
                        }

                        #2 
                        if (!empty($consumidor_estado) AND !empty($produto_serie)){
                            $whereJornada[] = "(estado = '{$consumidor_estado}' AND numero_serie_produto = {$produto_serie} AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca} AND cidade IS NULL)";
                        }
                        
                        #3
                        if (!empty($produto_id) AND !empty($consumidor_cidade) AND !empty($consumidor_estado)){
                            $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND produto = {$produto_id} AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca})";
                        }

                        #3
                        if (!empty($cidade_id) AND !empty($consumidor_estado)){
                            $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca} AND produto IS NULL)";
                        }

                        #4
                        if (!empty($consumidor_estado) AND !empty($produto_id)){
                            $whereJornada[] = "(estado = '{$consumidor_estado}' AND produto = {$produto_id} AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca} AND cidade IS NULL)";
                        }

                        #5
                        if (!empty($consumidor_estado)){
                            $whereJornada[] = "(estado = '{$consumidor_estado}' AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca} AND cidade IS NULL AND produto IS NULL)";
                        }

                        if (!empty($produto_serie)){
                            $whereJornada[] = "(numero_serie_produto = {$produto_serie} AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca} AND cidade IS NULL AND estado IS NULL)";
                        }

                        #6
                        if (!empty($produto_id)){
                            $whereJornada[] = "(produto = {$produto_id} AND peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca} AND cidade IS NULL AND estado IS NULL)";
                        }

                        #7
                        $whereJornada[] = "(peca = {$value['id']} AND numero_serie_peca = {$numero_serie_peca} AND cidade IS NULL AND estado IS NULL AND produto IS NULL)";
                    }
                }else{

                    #8
                    if (!empty($produto_serie) AND !empty($consumidor_cidade) AND !empty($consumidor_estado)){
                        $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND numero_serie_produto = {$produto_serie} AND peca = {$value['id']} AND numero_serie_peca IS NULL)";
                    }

                    #9
                    if (!empty($consumidor_estado) AND !empty($produto_serie)){
                        $whereJornada[] = "(estado = '{$consumidor_estado}' AND numero_serie_produto = {$produto_serie} AND peca = {$value['id']} AND cidade IS NULL AND numero_serie_peca IS NULL)";
                    }
                    
                    #10
                    if (!empty($produto_id) AND !empty($consumidor_cidade) AND !empty($consumidor_estado)){
                        $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND produto = {$produto_id} AND peca = {$value['id']} AND numero_serie_peca IS NULL)";
                    }

                    #11
                    if (!empty($consumidor_estado) AND !empty($produto_id)){
                        $whereJornada[] = "(estado = '{$consumidor_estado}' AND produto = {$produto_id} AND peca = {$value['id']} AND cidade IS NULL AND numero_serie_peca IS NULL)";
                    }

                    #12
                    if (!empty($consumidor_cidade) AND !empty($consumidor_estado)){
                        $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND peca = {$value['id']} AND produto IS NULL AND numero_serie_peca IS NULL)";
                    }

                    #13
                    if (!empty($produto_serie)){
                        $whereJornada[] = "(numero_serie_produto = {$produto_serie} AND peca = {$value['id']} AND cidade IS NULL AND estado IS NULL AND numero_serie_peca IS NULL)";
                    }

                    #14
                    if (!empty($produto_id)){
                        $whereJornada[] = "(produto = {$produto_id} AND peca = {$value['id']} AND cidade IS NULL AND estado IS NULL AND numero_serie_peca IS NULL)";
                    }

                    #15
                    if (!empty($consumidor_estado)){
                        $whereJornada[] = "(estado = '{$consumidor_estado}' AND peca = {$value['id']} AND produto IS NULL AND cidade IS NULL AND numero_serie_peca IS NULL)";
                    }

                    #16
                    $whereJornada[] = "(peca = {$value['id']} AND cidade IS NULL AND estado IS NULL AND produto IS NULL AND numero_serie_peca IS NULL)";
                    
                }
            }
        }

        #17
        if (!empty($produto_serie) AND !empty($cidade_id) AND !empty($consumidor_estado)){
            $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND numero_serie_produto = {$produto_serie} AND peca IS NULL AND numero_serie_peca IS NULL)";
        }

        #18
        if (!empty($produto_serie) AND !empty($consumidor_estado)){
            $whereJornada[] = "(estado = '{$consumidor_estado}' AND numero_serie_produto = {$produto_serie} AND cidade IS NULL AND peca IS NULL AND numero_serie_peca IS NULL)";
        }

        #19
        if (!empty($produto_id) AND !empty($cidade_id) AND !empty($consumidor_estado)){
            $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND produto = {$produto_id} AND peca IS NULL AND numero_serie_peca IS NULL AND numero_serie_produto IS NULL)";
        }

        #20
        if (!empty($produto_id) AND !empty($consumidor_estado)){
            $whereJornada[] = "(estado = '{$consumidor_estado}' AND produto = {$produto_id} AND cidade IS NULL AND peca IS NULL AND numero_serie_peca IS NULL AND numero_serie_produto IS NULL)";
        }

        #21
        if (!empty($cidade_id) AND !empty($consumidor_estado)){
            $whereJornada[] = "(estado = '{$consumidor_estado}' AND cidade = {$cidade_id} AND produto IS NULL AND peca IS NULL AND numero_serie_peca IS NULL AND numero_serie_produto IS NULL)";
        }

        #22
        if (!empty($produto_serie)){
            $whereJornada[] = "(numero_serie_produto = $produto_serie AND cidade IS NULL AND estado IS NULL AND peca IS NULL AND numero_serie_peca IS NULL)";
        }

        #23
        if (!empty($produto_id)){
            $whereJornada[] = "(produto = $produto_id AND cidade IS NULL AND estado IS NULL AND peca IS NULL AND numero_serie_peca IS NULL AND numero_serie_produto IS NULL)";
        }

        #24
        if (!empty($consumidor_estado)){
            $whereJornada[] = "(estado = '{$consumidor_estado}' AND produto IS NULL AND cidade IS NULL AND  peca IS NULL AND numero_serie_peca IS NULL AND numero_serie_produto IS NULL)";
        }

        $sqlJornada = "
                SELECT hd_jornada
                FROM tbl_hd_jornada
                WHERE tbl_hd_jornada.fabrica = {$login_fabrica}
                AND $data_abertura_os >= tbl_hd_jornada.data_inicio
                AND $data_abertura_os <= tbl_hd_jornada.data_fim
                AND  (".implode(" OR ", $whereJornada).") 
                ORDER BY hd_jornada ASC LIMIT 1";
        $resJornada = pg_query($con, $sqlJornada);
        
        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao gravar Ordem de Serviço #1010".$sql);
        }

        if (pg_num_rows($resJornada) > 0){
            $hd_jornada = pg_fetch_result($resJornada, 0, 'hd_jornada');
            
            include_once (__DIR__ . '/../../../class/communicator.class.php');

            $sql = "UPDATE tbl_os SET auditar = 't' , segmento_atuacao = $hd_jornada WHERE os = $os AND fabrica = $login_fabrica ";
            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao gravar Ordem de Serviço #1010".$sql);
            } 

            $TcMail = new TcComm($GLOBALS['externalId'], $GLOBALS['externalEmail']);

            if ($_serverEnvironment == 'development'){
                $email_admin = "guilherme.monteiro@telecontrol.com.br";
            }else{
                $email_admin = "at@ibramed.com.br";
            }

            $assunto = "Acompanhamento de Ordem de Serviço: $os";
            $mensagem = "Prezado Fabricante <br/> Informamos que a Ordem de Serviço: $os está disponível para acompanhamento.";
            $enviado = $TcMail->sendMail($email_admin, $assunto, $mensagem, $externalEmail);
            
            if (!$enviado){
                throw new Exception("Erro ao gravar Ordem de Serviço #1010");
            }
        }
    }
}
