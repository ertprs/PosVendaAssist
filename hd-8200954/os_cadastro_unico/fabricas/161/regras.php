<?php

    $regras["os|aparencia_produto"]["obrigatorio"] = true;
    $regras["os|acessorios"] = array(
        "function" => array("valida_acessorios")
    );
    $regras["os|consumidor_revenda"] = array(
        "function" => array("valida_tipo_os")
    );

    $regras["consumidor|cpf"]["obrigatorio"]       = true;
    $regras["consumidor|cep"]["obrigatorio"]       = true;
    $regras["consumidor|bairro"]["obrigatorio"]    = true;
    $regras["consumidor|endereco"]["obrigatorio"]  = true;
    $regras["consumidor|numero"]["obrigatorio"]    = true;
    $regras["consumidor|estado"]["obrigatorio"]    = false;
    $regras["consumidor|cidade"]["obrigatorio"]    = false;
    $regras["consumidor|email"] = array("function" => array("valida_email"));

    $regras["revenda|nome"]["obrigatorio"]         = false;
    // $regras["revenda|cnpj"]["obrigatorio"]      = false;
    $regras["revenda|cnpj"]                        = array("obrigatorio" => false, "function" => array());
    $regras["revenda|estado"]["obrigatorio"]       = false;
    $regras["revenda|cidade"]["obrigatorio"]       = false;

    $regras["produto|serie"]              = array("obrigatorio" => true, "function" => array('valida_serie_cristofoli'));
    $regras["produto|defeito_constatado"] = array("function" => array("valida_defeito_constatado"));

    $valida_anexo_boxuploader = "valida_anexo_boxuploader";
    
    $valida_garantia = "";
    $auditorias = array();
    $id_orcamento = 0;

    if (!$areaAdmin) {
        if ($login_pais != "BR") {
            unset($regras["consumidor|cep"]["regex"]);
            $regras["consumidor|cpf"]["function"] = [];
            $regras["revenda|cnpj"]["function"] = [];
            $regras["consumidor|cep"]["obrigatorio"] = false;
            $regras["consumidor|cpf"]["obrigatorio"] = false;
            $regras["revenda|cnpj"]["obrigatorio"] = false;
        }
    } else {

        if (getValue("posto[pais]") != "BR") {
            unset($regras["consumidor|cep"]["regex"]);
            $regras["consumidor|cpf"]["function"] = [];
            $regras["revenda|cnpj"]["function"] = [];
            $regras["consumidor|cep"]["obrigatorio"] = false;
            $regras["consumidor|cpf"]["obrigatorio"] = false;
            $regras["revenda|cnpj"]["obrigatorio"] = false;
        }

    }


//     $funcoes_fabrica = array("verifica_estoque_peca");

    function valida_email(){
        global $login_fabrica, $campos, $msg_erro, $os;
        if(!posto_interno($campos['posto']['id']) AND empty($os)){
            if(strlen(trim($campos['consumidor']['email']))==0){
                $msg_erro["msg"]["obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
                $msg_erro["campos"][] = "consumidor[email]";
            }
        }
    }

    function valida_acessorios(){
        global $con, $login_fabrica, $campos, $login_posto, $msg_erro;

        if($campos['produto']['linhaproduto'] != 1126 and strlen($campos['os']["acessorios"]) == 0){
            $msg_erro["msg"]["obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
            $msg_erro["campos"][] = "os[acessorios]";
        }
    }

    function valida_tipo_os(){
        global $con, $login_fabrica, $campos, $login_posto, $msg_erro;

        if($campos['produto']['linhaproduto'] == 1126){
            $campos['os']['consumidor_revenda'] = "C";
        }else{
            if(strlen($campos['os']["consumidor_revenda"]) == 0){
                $msg_erro["msg"]["obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
                $msg_erro["campos"][] = "os[consumidor_revenda]";   
            }
        }
    }

    function consumidor_revenda(){

        global $con, $os, $login_fabrica;

        if(strlen($os) > 0){

            $sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            return pg_fetch_result($res, 0, "consumidor_revenda");

        }else{
            return false;
        }

    }

    /* Resgata o ID do Tipo de Atendimento Orçamento */
    function id_tipo_atendimento_orcamento(){

        global $con, $login_fabrica;

        $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao ILIKE 'Or%amento'";
        $res = pg_query($con, $sql);

        return (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, "tipo_atendimento") : 0;

    }

    /* Verifica se o posto é do tipo Posto */
    function posto_interno($posto_param = ""){

        global $con, $login_fabrica, $campos, $login_posto;

        if(strlen($posto_param) > 0){
            $posto = $posto_param;
        }else{
            $posto = (strlen($campos["posto"]["id"]) > 0) ? $campos["posto"]["id"] : $login_posto;
        }

        if($areaAdmin == true){
            return false;
        }

        $sql = "SELECT
                    tbl_tipo_posto.posto_interno
                FROM tbl_posto_fabrica
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                WHERE
                    tbl_posto_fabrica.fabrica = {$login_fabrica}
                    AND tbl_posto_fabrica.posto = {$posto}";
        $res = pg_query($con, $sql);

        $posto_interno = pg_fetch_result($res, 0, "posto_interno");

        return ($posto_interno == "t") ? true : false;

    }

    function valida_posto_interno(){

        global $con, $campos, $login_fabrica, $msg_erro;

        $posto = $campos["posto"]["id"];

        $sql = "SELECT
                    tbl_tipo_posto.posto_interno
                FROM tbl_posto_fabrica
                INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                WHERE
                    tbl_posto_fabrica.fabrica = {$login_fabrica}
                    AND tbl_posto_fabrica.posto = {$posto}";
        $res = pg_query($con, $sql);

        $posto_interno = pg_fetch_result($res, 0, "posto_interno");

        if($posto_interno != "t"){
            $msg_erro["msg"]["campo_obrigatorio"] = traduz("Por favor insira o posto Interno para esse Tipo de Atendimento");
            $msg_erro["campos"][] = "posto[id]";
        }

    }

    function valida_defeito_constatado(){

        global $con, $os, $campos, $msg_erro, $login_fabrica;

        $troca_obrigatoria = false;

        $produto = $campos["produto"]["id"];

        if(strlen($produto) > 0){

            $sql = "SELECT troca_obrigatoria FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res) > 0){
                $troca_obrig = pg_fetch_result($res, 0, "troca_obrigatoria");
                $troca_obrigatoria = ($troca_obrig == "t") ? true : false;
            }

        }

        $sqlTroca = "
            SELECT  COUNT(1) AS tem_troca
            FROM    tbl_os_troca
            WHERE   os = $os
        ";
        $resTroca = pg_query($con,$sqlTroca);
        $temTroca = pg_fetch_result($resTroca,0,tem_troca);

        $cont_pecas = 0;

        foreach ($campos["produto_pecas"] as $value) {
            if(isset($value["id"])){
                if(strlen($value["id"]) > 0){
                    $cont_pecas++;
                }
            }
        }

        if($troca_obrigatoria == false && $temTroca == 0){
            if(strlen($campos["produto"]["defeito_constatado"]) > 0 && $cont_pecas == 0){
                $msg_erro["msg"]["campo_obrigatorio"] = traduz("Por favor informe as peças");
            }
        }

    }

    /* Verifica se o Tipo de Atendimento é ORÇAMENTO */
    function tipo_atendimento_orcamento(){

        global $con, $login_fabrica, $os, $campos;

        $posto = $campos["posto"]["id"];
        $tipo_atendimento = $campos["os"]["tipo_atendimento"];

        $sql = "SELECT tipo_atendimento
            FROM tbl_tipo_atendimento
            WHERE fabrica = {$login_fabrica}
            AND tipo_atendimento = {$tipo_atendimento}
            AND descricao = 'Orçamento' ";
        $res = pg_query($con, $sql);

        return (pg_num_rows($res) > 0) ? true : false;

    }

    // function valida_serie_cristofoli($numero_serie){

    //     global $campos;
    //     if ($campos["produto"]['sem_serie'] == 't') {
    //         return false;
    //     } else {
    //         preg_match("/[A-Z][0-9]{0,6}$/i", $numero_serie, $serie_match);
    //         $final_serie = $serie_match[0];
    //         $final_serie = str_replace("R", "", $final_serie);
    //         return (is_numeric($final_serie)) ? true : false;
    //     }

    // }

    /* Validação Numero Série*/
    function valida_serie_cristofoli(){
        global $con, $campos, $login_fabrica, $login_posto, $msg_erro, $areaAdmin;

        if($areaAdmin === true){
            $login_posto = $campos["posto"]["id"];
        }

        $produto_id         = $campos["produto"]["id"];
        $produto_serie      = $campos["produto"]["serie"];
        $produto_sem_serie  = $campos["produto"]["sem_serie"];

        if (!empty($produto_serie)) {
            $sql = "SELECT  tbl_produto.* ,
                            tbl_revenda.nome AS nome_revenda,
                            tbl_revenda.cnpj AS cnpj_revenda,
                            tbl_numero_serie.serie AS serie_produto
                        FROM tbl_produto
                            JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
                            JOIN tbl_numero_serie ON tbl_produto.produto = tbl_numero_serie.produto
                            JOIN tbl_revenda ON tbl_numero_serie.cnpj = tbl_revenda.cnpj
                            LEFT JOIN tbl_produto_idioma ON tbl_produto_idioma.produto = tbl_produto.produto
                            LEFT JOIN tbl_produto_pais ON tbl_produto_pais.produto = tbl_produto.produto
                            JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$login_posto}
                        WHERE LOWER(tbl_numero_serie.serie) = LOWER('{$produto_serie}')
                            AND tbl_numero_serie.fabrica = {$login_fabrica}
                            AND tbl_linha.fabrica = {$login_fabrica}
                            AND tbl_produto.fabrica_i = {$login_fabrica}";
            $res = pg_query($con,$sql);

            if (pg_num_rows($res) == 0) {
                $msg_erro["msg"]["obrigatorio"] = traduz("Número de Série não corresponde com o produto");
                $msg_erro["campos"][] = "produto[serie]";
            }
        }

        if (!$produto_sem_serie && empty($produto_serie)) {
            $msg_erro["msg"]["campo_obrigatorio"] = traduz("Preencha todos os campos obrigatórios");
            $msg_erro["campos"][] = "produto[serie]";
        }

        if ($campos["produto"]['sem_serie']) {
            return false;
        } else {
            preg_match("/[A-Z][0-9]{0,6}$/i", $numero_serie, $serie_match);
            $final_serie = $serie_match[0];
            $final_serie = str_replace("R", "", $final_serie);
            return (is_numeric($final_serie)) ? true : false;
        }
    }

    /* Área do Admin */
    if ($areaAdmin == true) {

        if(isset($_POST["posto"]["id"])){

            if(posto_interno($_POST["posto"]["id"]) == true){

                $regras["consumidor|nome"]["obrigatorio"]     = false;
                $regras["consumidor|cpf"]["obrigatorio"]      = false;
                $regras["consumidor|cpf"]["function"]         = array();
                $regras["consumidor|cidade"]["obrigatorio"]   = false;
                $regras["consumidor|estado"]["obrigatorio"]   = false;
                $regras["consumidor|cep"]["obrigatorio"]      = false;
                $regras["consumidor|bairro"]["obrigatorio"]   = false;
                $regras["consumidor|endereco"]["obrigatorio"] = false;
                $regras["consumidor|numero"]["obrigatorio"]   = false;
                $regras["consumidor|telefone"]["obrigatorio"] = false;

                $valida_anexo = "";

            }
        }

        if ($_POST["os"]["tipo_atendimento"] == id_tipo_atendimento_orcamento()) {

            $regras["posto|id"] = array("function" => array("valida_posto_interno"));

            $funcoes_fabrica[] = "campos_adicionais_cristofoli";

            $grava_os_item_function = "grava_os_item_cristofoli";

        }
        if ($_POST["produto"]["sem_serie"]) {
            $regras["produto|serie"]["obrigatorio"] = false;
        }
    /* Posto Comum */
    } else if (posto_interno() == false || (posto_interno() == true && consumidor_revenda() == "R")) {

        if(consumidor_revenda() == "R"){

            $regras["produto|serie"] = array(
                "obrigatorio" => false,
                "function"    => array('')
            );
        }

        $auditorias = array(
                "auditoria_reincidente_cristofoli",
                "auditoria_peca_critica",
                "auditoria_pecas_excedentes",
                "auditoria_troca_obrigatoria"
            );

        $numero_serie   = trim($_POST["produto"]["serie"]);
        $valida_serie_r = valida_serie_cristofoli($numero_serie);

        if($valida_serie_r == true){
            $auditorias[] = "valida_garantia_cristofoli";
        }else if(consumidor_revenda() == "R"){
            $auditorias[] = "auditoria_revenda";
        }else{
            $valida_garantia = "valida_garantia";
        }

        if ($_POST["produto"]["sem_serie"]) {
            $regras["produto|serie"]["obrigatorio"] = false;
        }

    /* Posto Interno */
    } else if (posto_interno() == true) {
        $regras["consumidor|nome"]["obrigatorio"]     = false;
        $regras["consumidor|cpf"]["obrigatorio"]      = false;
        $regras["consumidor|cpf"]["function"]         = array();
        $regras["consumidor|cidade"]["obrigatorio"]   = false;
        $regras["consumidor|estado"]["obrigatorio"]   = false;
        $regras["consumidor|cep"]["obrigatorio"]      = false;
        $regras["consumidor|bairro"]["obrigatorio"]   = false;
        $regras["consumidor|endereco"]["obrigatorio"] = false;
        $regras["consumidor|numero"]["obrigatorio"]   = false;
        $regras["consumidor|telefone"]["obrigatorio"] = false;

        $valida_anexo = "";

        $regras["produto|defeito_constatado"]         = array("function" => array(""));

        $regras["produto|serie"] = array(
            "obrigatorio" => false,
            "function"    => array()
        );

        /* Tipo Orçamento */
        if ($_POST["os"]["tipo_atendimento"] == id_tipo_atendimento_orcamento()) {

            $funcoes_fabrica[] = "campos_adicionais_cristofoli";

            $grava_os_item_function = "grava_os_item_cristofoli";

        } else {

            $auditorias = array(
                    "auditoria_reincidente_cristofoli",
                    "auditoria_peca_critica",
                    "auditoria_pecas_excedentes",
                    "auditoria_troca_obrigatoria"
            );

            $numero_serie   = trim($_POST["produto"]["serie"]);
            $valida_serie_r = valida_serie_cristofoli($numero_serie);

            if($valida_serie_r == true){
                $auditorias[] = "valida_garantia_cristofoli";
            }else{
                $valida_garantia = "valida_garantia";
            }
        }
    }

    function auditoria_reincidente_cristofoli(){

        global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

        $posto = $campos['posto']['id'];

        $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0 && $campos["produto"]['sem_serie'] != 't') {

            $select = "SELECT tbl_os.os
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.os < {$os}
                    AND tbl_os.posto = $posto
                    AND UPPER(tbl_os.serie) = UPPER('{$campos['produto']['serie']}')
                    AND tbl_os_produto.produto = {$campos['produto']['id']}
                    ORDER BY tbl_os.data_abertura DESC
                    LIMIT 1";

            $resSelect = pg_query($con, $select);

            if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
                $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

                if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                    $insert = "INSERT INTO tbl_os_status
                            (os, status_os, observacao)
                            VALUES
                            ({$os}, 70, 'OS reincidente de Número de Série')";
                    $resInsert = pg_query($con, $insert);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço");
                    } else {
                        $os_reincidente = true;
                    }
                }
            }
        }
    }

    function auditoria_revenda(){

        global $login_fabrica, $campos, $os, $con, $login_admin;

        $posto_id = $campos["posto"]["id"];
        $auditoria_status = 6;

        $sql_posto = "SELECT tipo_revenda FROM tbl_posto_fabrica
                      INNER JOIN tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                      WHERE posto = $posto_id and tbl_posto_fabrica.fabrica = $login_fabrica";
        $res_posto = pg_query($con, $sql_posto);

        if(strlen(trim(pg_last_error($con)))>0){
          $msg_erro .= "Erro ao encontrar tipo do posto - Auditoria de Revenda";
        }

        if(pg_num_rows($res_posto)>0){
            $tipo_revenda = pg_fetch_result($res_posto, 0, tipo_revenda);

            if($tipo_revenda == 't'){
              $sql_update = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
              $res_update = pg_query($con, $sql_update);

              if(pg_num_rows($res_update) == 0){
                $sql_insert = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria_status, 'Auditoria de Revenda')";
                $res_insert = pg_query($con, $sql_insert);
              }
            }
        }
    }

    function valida_garantia_cristofoli(){

        global $campos;

        $data_abertura = $campos["os"]["data_abertura"];
        $data_compra   = $campos["os"]["data_compra"];

        if (strtotime(formata_data($data_compra)." +12 months") < strtotime(formata_data($data_abertura))) {
            throw new Exception("Produto fora de garantia");
        }

    }

    function campos_adicionais_cristofoli() {
        global $os, $campos;

        if(tipo_atendimento_orcamento() == true){

            $valores = array(
                        "Valor Adicional" => number_format($campos["os"]["valor_adicional_mo"], 2),
                        "Desconto" => number_format($campos["os"]["desconto"], 2)
                    );
            $valores = json_encode($valores);

            grava_valor_adicional($valores, $os);

        }

    }

    /* Grava OS Item para pedido de Orçamento */

    function grava_os_item_cristofoli($os_produto, $subproduto = "produto_pecas")
    {

        global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

        if($historico_alteracao === true){
            $historico = array();
        }

        foreach ($campos[$subproduto] as $posicao => $campos_peca) {
            if (strlen($campos_peca["id"]) > 0) {

                if (function_exists("grava_custo_peca")) {
                    /**
                     * A função grava_custo_peca deve ficar dentro do arquivo de regras fábrica
                     * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
                     */
                    $custo_peca = grava_custo_peca();
                    if ($custo_peca == false) {
                        unset($custo_peca);
                    }
                }

                if ($historico_alteracao === true) {
                    include "$login_fabrica/historico_alteracao.php";
                }

                $sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
                $res = pg_query($con, $sql);

                $troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

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
                }

                $login_admin = (empty($login_admin)) ? "null" : $login_admin;

                if (empty($campos_peca["os_item"])) {
                    $sql = "INSERT INTO tbl_os_item (
                                os_produto,
                                peca,
                                qtde,
                                servico_realizado,
                                peca_obrigatoria,
                                admin
                                ".((isset($campos_peca['void'])) ? ", parametros_adicionais" : "")."
                                ".((isset($campos_peca['valor'])) ? ", preco" : "")."
                                ".((isset($custo_peca)) ? ", custo_peca" : "")."
                                ".(($grava_defeito_peca == true) ? ", defeito" : "")."
                            ) VALUES (
                                {$os_produto},
                                {$campos_peca['id']},
                                {$campos_peca['qtde']},
                                {$campos_peca['servico_realizado']},
                                {$devolucao_obrigatoria},
                                {$login_admin}
                                ".((isset($campos_peca['void'])) ? ", '".($campos_peca['void'])."'" : "")."
                                ".((isset($campos_peca['valor'])) ? ", '".str_replace(',','.',$campos_peca['valor'])."'" : "")."
                                ".((isset($custo_peca)) ? ", '".str_replace(',','.',$custo_peca[$campos_peca['id']])."'" : "")."
                                ".(($grava_defeito_peca == true) ? ", ".$campos_peca['defeito_peca'] : "")."
                            )";
//                             exit(nl2br($sql));
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar Ordem de Serviço #9");
                    }
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
                        $sql = "UPDATE tbl_os_item SET
                                    qtde = {$campos_peca['qtde']},
                                    servico_realizado = {$campos_peca['servico_realizado']}
                                    ".((isset($campos_peca['void'])) ? ", parametros_adicionais = '".$campos_peca['void']."'" : "")."
                                    ".(($grava_defeito_peca == true) ? ", defeito = {$campos_peca['defeito_peca']}" : "")."
                                WHERE os_produto = {$os_produto}
                                AND os_item = {$campos_peca['os_item']}";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao gravar Ordem de Serviço #10");
                        }
                    }
                }
            }
        }

        if($historico_alteracao === true){

            if(count($historico) > 0){

                grava_historico($historico, $os, $campos["posto"]["id"], $login_fabrica, $login_admin);

            }

        }

    }

    function grava_custo_peca() {

        global $campos;

        $pecas_valor = array();

        foreach($campos["produto_pecas"] as $key => $peca) {
            $valor                    = $peca["valor_total"];
            $pecas_valor[$peca['id']] = $valor;
        }

        return $pecas_valor;

    }

    /* Insere o ID do tipo atendimento Orçamento para o posto interno */
    if(posto_interno() == true || $areaAdmin == true){

        $id_orcamento = id_tipo_atendimento_orcamento();

    }

    function grava_os_campo_extra_fabrica(){
        global $campos;
        $sem_serie = $campos["produto"]['sem_serie'];
        $semns  = ($sem_serie == 't') ? $sem_serie : 'f';

        return array("sem_serie" => $semns);
    }

    $antes_valida_campos = "verifica_serie_obrigatoria_cristofoli";

    function verifica_serie_obrigatoria_cristofoli() {
        global $campos, $regras;

        $sem_serie = $campos["produto"]['sem_serie'];
        if ($sem_serie == 't') {
            $regras["produto|serie"]["obrigatorio"] = false;
            $regras["produto|serie"]["function"] = array();
        }

    }
?>
