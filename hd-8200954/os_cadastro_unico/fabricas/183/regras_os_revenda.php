<?
$regras["revenda_nome"]["obrigatorio"] = true;
$regras["revenda_cidade"]["obrigatorio"] = true;
$regras["revenda_estado"]["obrigatorio"] = true;
$regras["revenda_cnpj"]["obrigatorio"] = true;
$regras["revenda_cep"]["obrigatorio"] = true;
$regras["revenda_fone"]["obrigatorio"] = true;
$regras["revenda_email"]["obrigatorio"] = true;
$regras["consumidor_revenda"]["obrigatorio"] = true;
$regras["tipo_frete"]["obrigatorio"] = true;

$regras["data_abertura"] = array(
    "obrigatorio" => true,
    "regex"       => "date",
    "function"    => array("valida_data_abertura_roca")
);

$grava_defeito_peca  = true;
$antes_valida_campos = "antes_valida_campos_itatiaia";
$auditoria_bloqueia_pedido = "true";


if ($login_tipo_posto_codigo == "Rep"){
    $sql = "SELECT representante FROM tbl_representante WHERE fabrica = {$login_fabrica} AND cnpj = '{$login_cnpj}'";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0){
        $representante = pg_fetch_result($res, 0, "representante");
    }
}

// $auditorias_os_revenda = array(
//     "auditoria_km_roca",
//     "auditoria_produto_fora_garantia_roca"
// );

// $funcoes_fabrica = array(
//     "grava_visita_roca"
// );

// $funcoes_fabrica_email = array(
//     "envia_email_visita"
// );

// $auditorias = array(
//     "auditoria_peca_critica_itatiaia",
//     "auditoria_pecas_excedentes_itatiaia",
//     "auditoria_os_reincidente_itatiaia",
//     "auditoria_numero_de_serie_itatiaia",
//     "auditoria_numero_serie_bloqueado_itatiaia",
// );



function antes_valida_campos_itatiaia() {
    global $con, $campos, $regras, $msg_erro, $login_fabrica, $login_tipo_posto_codigo;

    if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
        if (!empty($campos["revenda_cnpj"]) AND !empty($campos["revenda_cnpj"])){
            $regras["revenda_cnpj"]["function"] = array("valida_revenda_cnpj_itatiaia");
        }
        
        if (!empty($campos["notas_fiscais_adicionadas"])){
            $sql_venda = "
                SELECT produto 
                FROM tbl_venda 
                WHERE fabrica = {$login_fabrica}
                AND posto = {$campos['posto_id']}
                AND nota_fiscal = '{$campos['notas_fiscais_adicionadas']}'";
            $res_venda = pg_query($con, $sql_venda);

            if (pg_num_rows($res_venda) > 0){
                $array_produto_nota = pg_fetch_all_columns($res_venda);
            }else{
               $msg_erro["msg"][] = "Nota Fiscal lançada não pertence a Revenda"; 
            }
        }
    }

    $consumidor_revenda     = $campos["consumidor_revenda"];
    
    $variaveis = [
        "tem_defeito_reclamado" => true, "tem_tipo_atendimento" => true, "tem_qtde" => true, "tem_nota_fiscal" => true,
        "tem_produto" => true, "tem_serie" => true, "tem_data_nf" => true, "tem_defeito_constatado" => true, "tem_produto_nota" => true,
        "produto_lancado" => false, "qtde_superior" => false
    ];
    extract($variaveis);

    unset($campos["produtos"]["__modelo__"]);

    $numero_nf = $campos["produtos"][0]["nota_fiscal"];
    $posto_id = $campos["posto_id"];

    $sql = "SELECT produto, qtde FROM tbl_venda WHERE fabrica = {$login_fabrica} AND posto = {$posto_id} AND nota_fiscal = '{$numero_nf}'";
    $res = pg_query($con, $sql);
    
    $info_nota = array();
    
    if (pg_num_rows($res) > 0){
        for ($i=0; $i < pg_num_rows($res); $i++) { 
            $produto_nota = pg_fetch_result($res, $i, 'produto');
            $qtde_nota = pg_fetch_result($res, $i, 'qtde');
            $info_nota[$produto_nota] = $qtde_nota;
        }
    }

    foreach ($campos["produtos"] as $key => $dados) {
        if (in_array($login_tipo_posto_codigo, array("Rev", "Rep"))){
            if (!empty($dados["id"])){
                $produto_lancado = true;
                $qtde_produto_nota = $info_nota[$dados["id"]];

                if (empty($dados["id"])){
                    $tem_produto = false;
                    $msg_erro["campos"][] = "produto_$key";
                }
            
                if (empty($dados["tipo_atendimento"])){
                    $tem_tipo_atendimento = false;
                    $msg_erro["campos"][] = "produto_$key";
                }
            
                if (!in_array($dados["id"], $array_produto_nota)){
                    $tem_produto_nota = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["data_nf"])){
                    $tem_data_nf = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["nota_fiscal"])){
                    $tem_nota_fiscal = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["qtde"])){
                    $tem_qtde = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if ($dados["qtde"] > $qtde_produto_nota){
                    $qtde_superior = true;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["defeito_reclamado"])){
                    $tem_defeito_reclamado = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["defeito_constatado"])){
                    $tem_defeito_constatado = false;
                    $msg_erro["campos"][] = "produto_$key";
                }
            }else{
                unset($campos["id"]);
                unset($campos["os_revenda_item"]); 
                unset($campos["nota_fiscal"]);
                unset($campos["data_nf"]);
                unset($campos["info_pecas"]);
                unset($campos["produto_fora_linha"]); 
                unset($campos["tipo_atendimento"]);
                unset($campos["serie"]);
                unset($campos["referencia"]);
                unset($campos["descricao"]);
                unset($campos["qtde"]);
                unset($campos["defeito_reclamado"]);
                unset($campos["defeito_constatado"]);
            }
        }else{
            if (!empty($dados["id"])){
                
                $produto_lancado = true;
                $qtde_produto_nota = $info_nota[$dados["id"]];

                if (empty($dados["id"])){
                    $tem_produto = false;
                    $msg_erro["campos"][] = "produto_$key";
                }
            
                if (empty($dados["tipo_atendimento"])){
                    $tem_tipo_atendimento = false;
                    $msg_erro["campos"][] = "produto_$key";
                }
            
                if (empty($dados["data_nf"]) && $dados["nota_fiscal"] != "semNota"){
                    $tem_data_nf = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["nota_fiscal"]) && $dados["nota_fiscal"] != "semNota"){
                    $tem_nota_fiscal = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["qtde"])){
                    $tem_qtde = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["defeito_reclamado"])){
                    $tem_defeito_reclamado = false;
                    $msg_erro["campos"][] = "produto_$key";
                }

                if (empty($dados["defeito_constatado"])){
                    $tem_defeito_constatado = false;
                    $msg_erro["campos"][] = "produto_$key";
                }
            }else{
                unset($campos["id"]);
                unset($campos["os_revenda_item"]); 
                unset($campos["nota_fiscal"]);
                unset($campos["data_nf"]);
                unset($campos["info_pecas"]);
                unset($campos["produto_fora_linha"]); 
                unset($campos["tipo_atendimento"]);
                unset($campos["serie"]);
                unset($campos["referencia"]);
                unset($campos["descricao"]);
                unset($campos["qtde"]);
                unset($campos["defeito_reclamado"]);
                unset($campos["defeito_constatado"]);
            }
        }
    } 
    
    if ($qtde_superior === true){
        $msg_erro["msg"][] = "Existem produtos com qtde superior a Nota Fiscal lançada";
    }

    if ($produto_lancado === false){
        $msg_erro["msg"][] = "Selecione um produto para abrir a Ordem de Serviço";
    } 

    if ($tem_serie === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Selecione a série do produto";
    } 

    if ($tem_produto_nota === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Existem produtos que não pertence a Nota Fiscal lançada";
    }

    if ($tem_tipo_atendimento === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Selecione o tipo de atendimento do produto";
    }

    if ($tem_defeito_reclamado === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Selecione defeito reclamado do produto";
    }
    
    if ($tem_defeito_constatado === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Selecione defeito constatado do produto";
    }

    if ($tem_qtde === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Selecione a quantidade do produto";
    }

    if ($tem_nota_fiscal === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Digite a nota fiscal do produto";
    }

    if ($tem_data_nf === false AND count($msg_erro["campos"])){
        $msg_erro["msg"][] = "Digite a data da nota fiscal do produto";
    }
}

function valida_revenda_cnpj_itatiaia() {
    global $con, $campos;

    if (!empty($campos["revenda_cnpj"])){
        $cnpj = preg_replace("/\D/", "", $campos["revenda_cnpj"]);
    }
    
    if (!empty($cnpj)) {
        if(strlen($cnpj) < 14){
            throw new Exception("CNPJ da Revenda é inválido");
        }

        if (strlen($cnpj) > 0) {
            $sql = "SELECT fn_valida_cnpj_cpf('{$cnpj}')";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("CNPJ da Revenda é inválido");
            }
        }

        $sql = "SELECT revenda FROM tbl_revenda WHERE cnpj = '{$cnpj}';";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $campos['revenda'] = pg_fetch_result($res, 0, "revenda");
        }else{
            $sql = "INSERT INTO tbl_revenda(cnpj,nome) values('{$cnpj}','{$campos["revenda_nome"]}') RETURNING revenda;";
            $res = pg_query($con, $sql);

            if(strlen(pg_last_error()) > 0){
                throw new Exception("Erro ao incluir revenda");
            }else{
                $campos['revenda'] = pg_fetch_result($res, 0, "revenda");
            }
        }
    }
}

function grava_os_revenda_fabrica() {
    global $con, $login_fabrica, $campos, $areaAdmin, $login_tipo_posto_codigo, $representante;

    $array_dados = array();

    $campos["revenda_cep"] = preg_replace("/[\-]/", "", $campos["revenda_cep"]);
    $campos["revenda_cnpj"] = preg_replace("/\D/", "", $campos["revenda_cnpj"]);

    $campos["revenda_nome"] = str_replace("'", " ", $campos["revenda_nome"]);
    $campos["revenda_bairro"] = str_replace("'", " ", $campos["revenda_bairro"]);
    $campos["revenda_endereco"] = str_replace("'", " ", $campos["revenda_endereco"]);
    $campos["revenda_complemento"] = str_replace("'", " ", $campos["revenda_complemento"]);
    
    $array_dados = array(
        "consumidor_nome" => "'".$campos["revenda_nome"]."'",
        "consumidor_cpf" => "'".$campos["revenda_cnpj"]."'",
        "consumidor_cep" => "'".$campos["revenda_cep"]."'",
        "consumidor_estado" => "'".$campos["revenda_estado"]."'",
        "consumidor_cidade" => "'".$campos["revenda_cidade"]."'",
        "consumidor_bairro" => "'".$campos["revenda_bairro"]."'",
        "consumidor_endereco" => "'".$campos["revenda_endereco"]."'",
        "consumidor_numero" => "'".$campos["revenda_numero"]."'",
        "consumidor_complemento" => "'".$campos["revenda_complemento"]."'",
        "consumidor_fone" => "'".$campos["revenda_fone"]."'",
        "consumidor_email" => "'".$campos["revenda_email"]."'",
        "consumidor_revenda" => "'".$campos["consumidor_revenda"]."'",
        "visita_por_km" => ((!empty($campos['solicitar_deslocamento']) AND $campos["solicitar_deslocamento"] == "t") ? "'".$campos['solicitar_deslocamento']."'" : "null")
    );

    if(!empty($campos['os_revenda'])){
        $sql = "SELECT campos_extra FROM tbl_os_revenda WHERE os_revenda = {$campos['os_revenda']}";
        $res = pg_query($con, $sql);
        
        if (pg_num_rows($res) > 0){
            $campos_extra = pg_fetch_result($res, 0, "campos_extra");
            $campos_extra = json_decode($campos_extra, true);
        }
    }

    $campos_extra["tipo_frete"] = $campos['tipo_frete'];
    if (!empty($campos['os_revenda'])){
        $campos_extra["revenda_celular"] = $campos['revenda_celular'];
        $campos_extra["inscricao_estadual"] = $campos["inscricao_estadual"];
    }

    if ($login_tipo_posto_codigo == "Rep"){
        $array_dados["representante"] = $representante;
    }

    if($areaAdmin === true){
        if (!empty($campos["os_cortesia"]) AND $campos["os_cortesia"] == "t"){
        $array_dados["cortesia"] = "'".$campos['os_cortesia']."'";
        }else{
        $array_dados["cortesia"] = "'f'";
        }
    }

    if (count($campos_extra)){
        $json_campos_extra = json_encode($campos_extra);
        $array_dados["campos_extra"] = "'".$json_campos_extra."'";
    }
    return $array_dados;
}

function grava_os_fabrica() {
    global $campos;
    
        $campos["revenda_cep"] = preg_replace("/[\-]/", "", $campos["revenda_cep"]);
        
        $dados = array(
            "consumidor_nome" => "'".$campos["revenda_nome"]."'",
            "consumidor_cpf" => "'".$campos["revenda_cnpj"]."'",
            "consumidor_cep" => "'".$campos["revenda_cep"]."'",
            "consumidor_estado" => "'".$campos["revenda_estado"]."'",
            "consumidor_cidade" => "'".$campos["revenda_cidade"]."'",
            "consumidor_bairro" => "'".$campos["revenda_bairro"]."'",
            "consumidor_endereco" => "'".$campos["revenda_endereco"]."'",
            "consumidor_numero" => "'".$campos["revenda_numero"]."'",
            "consumidor_complemento" => "'".$campos["revenda_complemento"]."'",
            "consumidor_fone" => "'".$campos["revenda_fone"]."'",
            "consumidor_email" => "'".$campos["revenda_email"]."'"
        );

    // if ($campos["consumidor_revenda"] == "R"){
    //     $campos["revenda"] = "null";
    //     unset($campos["revenda_nome"]);
    //     unset($campos["revenda_cnpj"]);
    // }
    return $dados;
}

function grava_os_explodida_fabrica($array_produto){
    global $campos, $con, $login_fabrica, $msg_erro;

    $array_dados = array();
    $campos_os = "";
    $valor_os = "";

    foreach ($campos["produtos"] as $key_p => $value_p) {
        if ($value_p["id"] == $array_produto["id"] AND $value_p["os_revenda_item"] == $array_produto["os_revenda_item"]){
            $array_produto["defeito_constatado"] = $value_p["defeito_constatado"];
            $array_produto["defeito_constatado_grupo"] = $value_p["defeito_constatado_grupo"];
            $array_produto["info_pecas"] = $value_p["info_pecas"];
        }
    }
    
    if (!empty($campos["os_cortesia"]) AND $campos["os_cortesia"] == "t"){
        $array_dados = array_merge($array_dados, array("cortesia" => "'".$campos["os_cortesia"]."'"));
    }

    if (!empty($array_produto["defeito_constatado"])){
        $array_dados = array_merge($array_dados, array("defeito_constatado" => $array_produto["defeito_constatado"]));
    }
    return $array_dados;
}

function grava_os_item ($os, $os_produto, $os_revenda, $os_revenda_item, $array_produto) {
    global $con, $login_fabrica, $campos, $login_admin;

    $param_adicionais = $array_produto["parametros_adicionais"];
    $param_adicionais = json_decode($param_adicionais,true);



    if (strlen($campos["tipo_frete"]) > 0) {
        $campoADD = "campos_adicionais,";
        $valueADD = "'".json_encode(["tipo_frete" => $campos["tipo_frete"]])."',";
    }

    $sql = "
        INSERT INTO tbl_os_campo_extra (
            {$campoADD} os, fabrica, os_revenda, os_revenda_item, marca
        ) VALUES (
            {$valueADD} {$os}, {$login_fabrica}, {$os_revenda}, {$os_revenda_item}, ".((!empty($array_produto['marca'])) ? $array_produto['marca'] : "null")."
        );
    ";
    $res = pg_query($con, $sql);

    if (!empty($param_adicionais["info_pecas"])){

        $info_pecas = json_decode($param_adicionais["info_pecas"], true);
        
        foreach ($info_pecas as $key_pecas => $value_pecas) {
            
            $sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$value_pecas['servico_realizado']}";
            $res = pg_query($con, $sql);

            $troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

            if ($troca_de_peca == "t") {
                $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$value_pecas['id_peca']}";
                $res = pg_query($con, $sql);
                
                $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");

                if ($devolucao_obrigatoria == "t") {
                    $devolucao_obrigatoria = "TRUE";
                } else {
                    $devolucao_obrigatoria = "FALSE";
                }
            } else {
                $devolucao_obrigatoria = "FALSE";
            }
            $login_admin = (empty($login_admin)) ? "null" : $login_admin;

            $sql = "INSERT INTO tbl_os_item (
                        os_produto,
                        peca,
                        qtde,
                        servico_realizado,
                        peca_obrigatoria,
                        admin
                    ) VALUES (
                        {$os_produto},
                        {$value_pecas['id_peca']},
                        {$value_pecas['qtde_lancada']},
                        {$value_pecas['servico_realizado']},
                        {$devolucao_obrigatoria},
                        {$login_admin}
                    ) RETURNING os_item";
            $res = pg_query($con, $sql);
            $id_os_item = pg_fetch_result($res, 0, "os_item");

            verifica_estoque_peca_itatiaia($os, $value_pecas['id_peca'], $value_pecas['qtde_lancada'], $value_pecas['servico_realizado'], $id_os_item, $array_produto['nota_fiscal'], $array_produto['data_nf']);
        }
    }
}

function verifica_estoque_peca_itatiaia($os, $peca, $qtde, $servico, $os_item, $nota_fiscal, $data_nf){
    global $login_fabrica, $campos, $con, $areaAdmin, $login_posto;
    
    #include "classes/Posvenda/Os.php";
    
    $posto = ($areaAdmin === false) ? $login_posto : $campos["posto_id"];
    $Os = new \Posvenda\Os($login_fabrica);
    
    $status_posto_controla_estoque = $Os->postoControlaEstoque($posto);

    if($status_posto_controla_estoque == true){
        if(!empty($peca)){
            $status_servico = $Os->verificaServicoUsaEstoque($servico);
        
            if($status_servico == true){
                $sqlEstoque = "SELECT qtde_saida FROM tbl_estoque_posto_movimento WHERE os_item = {$os_item}";
                $resEstoque = pg_query($con, $sqlEstoque);

                if (pg_num_rows($resEstoque) > 0) {
                    $qtde_saida = pg_fetch_result($resEstoque, 0, "qtde_saida");
                    $diferenca = $qtde - $qtde_saida;

                    if ($diferenca != 0) {
                        $$Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);

                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                        if($status_estoque == false){
                            $novo_servico_realizado = buscaServicoRealizadoRoca("gera_pedido");
                            $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                            $res = pg_query($con, $sql);
                        }else{
                            $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                        }
                    }
                } else {
                    $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);
                    
                    if(!$status_estoque){
                        $novo_servico_realizado = buscaServicoRealizadoRoca("gera_pedido");
    
                        if(!empty($novo_servico_realizado)){
                            $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                            $res = pg_query($con, $sql);
                        }else{
                            throw new Exception("O posto não tem estoque suficiente para a Peça {$peca_referencia}");
                        }
                    }else{
                        $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                    }
                }
            } else {
                $status_exclusao = $Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);
                $status_servico = $Os->verificaServicoGeraPedido($servico);

                if($status_servico == true){

                    $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                    if($status_estoque == true){
                        $novo_servico_realizado = buscaServicoRealizadoRoca("estoque");
                        if(!empty($novo_servico_realizado)){
                            $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                            $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                            $res = pg_query($con, $sql);
                        }
                    }
                }
            }
        }
    }
}

function buscaServicoRealizadoRoca($tipo){
    global $login_fabrica, $con;

    switch($tipo){
        case "gera_pedido"   :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS NOT TRUE"; break;
        case "estoque"       :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS TRUE"; break;
        case "troca_produto" :  $cond = " AND troca_produto IS TRUE"; break;
    }

    $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND gera_pedido IS TRUE $cond";
    $query = pg_query($con, $sql);
    $res = pg_fetch_all($query);
    return (is_array($res) && count($res) > 0) ? $res[0]['servico_realizado'] : false;
}

function grava_os_extra(){
    global $representante;

    $array_dados = array();

    if (!empty($representante)){
        $array_dados["representante"] = $representante;
    }
    return $array_dados;
}

// function auditoria_peca_critica_itatiaia($os, $array_produto){
//     global $con, $login_fabrica, $auditoria_bloqueia_pedido;
//     $sql = "
//         SELECT
//             tbl_os_item.os_item
//         FROM tbl_os_item
//         JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
//         JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
//         JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
//         JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
//         JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
//         WHERE tbl_os.fabrica = {$login_fabrica}
//         AND tbl_os_produto.os = {$os}
//         AND tbl_peca.peca_critica IS TRUE;
//     ";
//     $res = pg_query($con, $sql);

//     if(pg_num_rows($res) > 0){
//         $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

//         if($busca['resultado']){
//             $auditoria_status = $busca['auditoria'];
//         }

//         if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'", $os) === true) {

//             $sql = "
//                 INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
//                 VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', {$auditoria_bloqueia_pedido});
//             ";
//             $res = pg_query($con, $sql);

//             if (strlen(pg_last_error()) > 0) {
//                 throw new Exception("Erro ao lançar ordem de serviço #AUD002");
//             }
//         }
//     }
// }

// function auditoria_pecas_excedentes_itatiaia($os, $array_produto){
//     global $con, $login_fabrica, $auditoria_bloqueia_pedido;

//     $sql = "SELECT qtde_pecas_intervencao FROM tbl_fabrica WHERE fabrica = {$login_fabrica};";
//     $res = pg_query($con, $sql);

//     $qtde_pecas_intervencao = pg_fetch_result($res, 0, "qtde_pecas_intervencao");

//     if(!strlen($qtde_pecas_intervencao)){
//         $qtde_pecas_intervencao = 0;
//     }

//     if ($qtde_pecas_intervencao > 0) {

//         $sql = "
//             SELECT
//                 COUNT(tbl_os_item.os_item) AS qtde_pecas
//             FROM tbl_os_item
//             JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
//             JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
//             JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
//             JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
//             WHERE tbl_os.fabrica = {$login_fabrica}
//             AND tbl_os_produto.os = {$os}
//             AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE";
//         $res = pg_query($con, $sql);
//         if(pg_num_rows($res) > 0){
//             $qtde_pecas = pg_fetch_result($res, 0, "qtde_pecas");
//         }else{
//             $qtde_pecas = 0;
//         }

//         if ($qtde_pecas > $qtde_pecas_intervencao) {
//             $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

//             if($busca['resultado']){
//                 $auditoria_status = $busca['auditoria'];
//             }

//             if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'")) {
                
//                 $sql = "
//                     INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
//                     VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de peças excedentes', {$auditoria_bloqueia_pedido});
//                 ";
//                 $res = pg_query($con, $sql);

//                 if (strlen(pg_last_error()) > 0) {
//                     throw new Exception("Erro ao lançar ordem de serviço #AUD002");
//                 }
//             }
//         }
//     }
// }

// function auditoria_os_reincidente_itatiaia($os, $array_produto) {
//     global $con, $login_fabrica, $campos, $os_reincidente, $os_reincidente_numero;

//     $posto = $campos['posto_id'];
    
//     $sql = "SELECT  os
//             FROM    tbl_os
//             WHERE   fabrica         = {$login_fabrica}
//             AND     os              = {$os}
//             AND     os_reincidente  IS NOT TRUE
//             AND     cancelada       IS NOT TRUE";
//     $res = pg_query($con, $sql);
    
//     if(pg_num_rows($res) > 0 && strlen($array_produto['serie']) > 0 && strlen($array_produto['id']) > 0){
//         $select = "SELECT tbl_os.os
//                 FROM tbl_os
//                 INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
//                 WHERE tbl_os.fabrica = {$login_fabrica}
//                 AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
//                 AND tbl_os.excluida IS NOT TRUE
//                 AND tbl_os.os < {$os}
//                 AND tbl_os.posto = $posto
//                 AND tbl_os.serie =  '{$array_produto['serie']}'
//                 AND tbl_os_produto.produto = {$array_produto['id']}
//                 ORDER BY tbl_os.data_abertura DESC
//                 LIMIT 1";
//         $resSelect = pg_query($con, $select);

//         if (pg_num_rows($resSelect) > 0) {
//             $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
//             echo nl2br($select);
//             if (verifica_os_reincidente_finalizada_itatiaia($os_reincidente_numero)) {
//                 $insert = "INSERT INTO tbl_os_status
//                         (os, status_os, observacao)
//                         VALUES
//                         ({$os}, 70, 'OS reincidente de cnpj, nota fiscal e produto')";
//                 $resInsert = pg_query($con, $insert);

//                 if (strlen(pg_last_error()) > 0) {
//                     throw new Exception("Erro ao lançar ordem de serviço");
//                 } else {
//                     $os_reincidente = true;
//                 }
//             }
//         }
//     }
// }

// function verifica_os_reincidente_finalizada_itatiaia($os) {
//     global $con, $login_fabrica, $campos;
//     $posto = $campos['posto_id'];
  
//     $sql = "
//         SELECT os
//         FROM tbl_os
//         WHERE fabrica = {$login_fabrica}
//         AND os = {$os}
//         AND finalizada IS NOT NULL
//         AND data_fechamento IS NOT NULL
//     ";
//     $res = pg_query($con, $sql);

//     if (pg_num_rows($res) > 0) {
//         return true;
//     } else {
//         $sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND posto = {$posto} AND excluida IS NOT TRUE";
//         $res = pg_query($con, $sql);

//         if (pg_num_rows($res) > 0 ){
//             $sua_os = pg_fetch_result($res, 0, "sua_os");
//             throw new Exception("Já existe uma Ordem de Serviço aberta com os dados informados, os: {$sua_os}");
//         } else {
//             return true;
//         }
//     }
// }
