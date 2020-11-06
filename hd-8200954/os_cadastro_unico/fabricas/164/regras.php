<?php

    /* $regras["os|aparencia_produto"]["obrigatorio"] = true;
    $regras["os|acessorios"]["obrigatorio"]        = true; */

    $data_abertura_fixa = true;

    $regras["consumidor|cpf"]["obrigatorio"]      = true;
    $regras["consumidor|cep"]["obrigatorio"]      = true;
    $regras["consumidor|bairro"]["obrigatorio"]   = true;
    $regras["consumidor|endereco"]["obrigatorio"] = true;
    $regras["consumidor|numero"]["obrigatorio"]   = true;
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"]  = true;
    $regras["os|data_entrada"]["obrigatorio"]     = true;

    if(posto_interno()){
        $valida_anexo_boxuploader = "";
    } else {
        $valida_anexo_boxuploader = "valida_anexo_boxuploader";
    }

    /*
    $regras["revenda|nome"]["obrigatorio"]   = false;
    $regras["revenda|cnpj"]["obrigatorio"]   = false;
    $regras["revenda|cnpj"]                  = array("obrigatorio" => false, "function" => array());
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
    */
    
    $regras["produto|referencia"]["function"] = array("valida_produto_nacional");
    $regras["produto|serie"]                  = array("obrigatorio" => true, "function" => array("valida_serie_gama_italy"));
    $regras["produto|defeito_constatado"]     = array("function" => array("valida_defeito_constatado"));

    function vetifica_os_revenda(){

        global $con, $login_fabrica, $_POST, $regras;

        if(isset($_POST["os"]["os_id"])){

            $os_id = $_POST["os"]["os_id"];
            if(!empty($os_id)) {
                $sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = {$os_id} AND fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);

                if(pg_num_rows($res) > 0){

                    $consumidor_revenda = pg_fetch_result($res, 0, "consumidor_revenda");

                    if($consumidor_revenda == "R"){
                        $regras["produto|serie"] = array("obrigatorio" => false, "function" => array());
                    }

                }
            }
        }

        return;

    }

    vetifica_os_revenda();

    function id_tipo_atendimento_cortesia(){

        global $con, $login_fabrica;

        $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao = 'Cortesia'";
        $res = pg_query($con, $sql);

        return (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, "tipo_atendimento") : 0;

    }

    if($areaAdmin == true && $_POST["os"]["tipo_atendimento"] == id_tipo_atendimento_cortesia()){

        $valida_garantia = "";

    }

    /* Verifica se o posto é do tipo Posto Interno */
    function posto_interno($posto_param = ""){

        global $con, $login_fabrica, $campos, $login_posto, $areaAdmin;

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

    function verifica_os_troca(){

        global $con, $login_fabrica, $os;
         
        if(strlen($os) > 0){

            $sql_os_troca = "SELECT 
                                tbl_os_troca.os_troca 
                            FROM tbl_os_troca 
                            INNER JOIN tbl_os ON tbl_os.os = tbl_os_troca.os AND tbl_os.fabrica = {$login_fabrica} 
                            WHERE 
                                tbl_os_troca.os = {$os}";
            $res_os_troca = pg_query($con, $sql_os_troca);

            if(pg_num_rows($res_os_troca) > 0){
                return true;
            }

        }

        return false;

    }

    /* Validações */

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
            $msg_erro["msg"]["campo_obrigatorio"] = "Por favor insira o posto Interno para esse Tipo de Atendimento";
            $msg_erro["campos"][] = "posto[id]";
        }

    }

    function valida_defeito_constatado(){

        global $con, $campos, $msg_erro, $login_fabrica;

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

        $cont_pecas = 0;

        if (is_array($campos["produto_pecas"])) {
            foreach ($campos["produto_pecas"] as $value) {
                if(isset($value["id"])){
                    if(strlen($value["id"]) > 0){
                        $cont_pecas++;
                    }
                }
            }
        }

        if($troca_obrigatoria == false && verifica_os_troca() == false){
            if(strlen($campos["produto"]["defeito_constatado"]) > 0 && $cont_pecas == 0 && $campos["posto"]["xtipo_posto"] != "Autorizada MODELO"){
                $msg_erro["msg"]["campo_obrigatorio"] = "Por favor informe as peças";
            }
        }

    }

    function valida_serie_gama_italy($numero_serie){

        global $campos, $posto_interno;

        $numero_serie = $campos["produto"]["serie"];

        if($numero_serie == "123456789"){

            throw new Exception("Núemro de Série {$numero_serie} inválido!");

        }else{

            $arr_num = array(1,2,3,4,5,6,7,8,9,0);

            for ($n = 0; $n < count($arr_num); $n++) {

                $numero_comp = $arr_num[$n];

                for ($m = 6; $m <= 12; $m++) {

                    $sequencia = "";

                    for ($q = 0; $q < $m; $q++) {

                        $sequencia .= $numero_comp;

                    }

                    if($numero_serie == $sequencia){

                        throw new Exception("Núemro de Série {$numero_serie} inválido!");

                    }

                }

            }

        }

        return;

    }

    function valida_produto_nacional(){

        global $con, $campos, $login_fabrica, $msg_erro;

        if(isset($campos["produto"]["origem_produto"])){

            $origem_produto = $campos["produto"]["origem_produto"];

            if($origem_produto == "NAC"){ /* Produto Nacional */

                if(strlen($campos["produto"]["numero_serie_calefator"]) == 0){

                    $msg_erro["campos"][] = "produto[numero_serie_calefator]";
                    throw new Exception("Informe o Núemro de Série do Calefator / Motor!");

                }

                if(strlen($campos["produto"]["cor_indicativa_carcaca"]) == 0){

                    $msg_erro["campos"][] = "produto[cor_indicativa_carcaca]";
                    throw new Exception("Informe a Cor Indicativa da Carcaça!");

                }

            }

        }

    }

    /* Auditorias */

    function auditoria_reincidente_gama_italy(){

        global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

        $posto        = $campos['posto']['id'];
        $nota_fiscal  = $campos["os"]["nota_fiscal"];
        $cnpj_revenda = $campos["revenda"]["cnpj"];
        $numero_serie = $campos["produto"]["serie"];
        $produto_id   = $campos["produto"]["id"];

        $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){

            $select = "SELECT tbl_os.os
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '30 days')
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.os < {$os}
                    AND (tbl_os.serie = '{$numero_serie}'
                    OR (
                        tbl_os.nota_fiscal = '{$nota_fiscal}'
                        AND tbl_os.revenda_cnpj = '{$cnpj_revenda}'
                        AND tbl_os_produto.produto = {$produto_id}
                    ))
                    ORDER BY tbl_os.data_abertura DESC
                    LIMIT 1";
            $resSelect = pg_query($con, $select);

            if (pg_num_rows($resSelect) > 0) {
                $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
                $os_reincidente = true;
            }
        }
    }

    /* Valores Adicionais */

    function grava_os_campo_extra_fabrica() {

        global $campos;

        $origem_produto = $campos["produto"]["origem_produto"];

        if($origem_produto == "NAC"){

            $return = array(
                "numero_serie_calefator"           => $campos["produto"]["numero_serie_calefator"],
                "cor_indicativa_carcaca"           => $campos["produto"]["cor_indicativa_carcaca"],
                "numero_serie_interno_placa_motor" => $campos["produto"]["numero_serie_interno_placa_motor"]
            );
        }
        
        $return["data_entrada"]  = $campos["os"]["data_entrada"];
        $return["troca_produto"] = $campos['produto']['troca_produto'];
        return $return;
    }


    /* Chamadas Auditorias */

    if(posto_interno() == true){

        $auditorias = array();

        $funcoes_fabrica = array("auditoria_troca_produto_gama_italy");

        $regras["revenda|nome"]["obrigatorio"]   = false;
        $regras["revenda|cnpj"]["obrigatorio"]   = false;
        $regras["revenda|cnpj"]                  = array("obrigatorio" => false, "function" => array());
        $regras["revenda|estado"]["obrigatorio"] = false;
        $regras["revenda|cidade"]["obrigatorio"] = false;

        $regras["produto|serie"]                 = array("obrigatorio" => false, "function" => array(""));

        $regras["produto|defeito_constatado"]    = array("function" => array(""));

        $valida_anexo = "";        

    }else{

        if(verifica_os_troca() == true){

            $auditorias = array();

        }else{

            $auditorias = array(
                "auditoria_reincidente_gama_italy",
                "auditoria_peca_critica",
                "auditoria_troca_obrigatoria_gama_italy",
                /* "auditoria_peca_sem_saldo", */
                "auditoria_pecas_excedentes"
            );

        }

    }

    function grava_os_fabrica(){

        global $campos;

        $defeito_reclamado = $campos['os']['defeito_reclamado'];

        if (empty($defeito_reclamado)) {
            $defeito_reclamado = "null";
        }        

        $campos_arr = array("defeito_reclamado" => "{$defeito_reclamado}");

        if(posto_interno() == true){
            if(strlen($campos["os"]["destinacao"]) > 0){
                $campos_arr["segmento_atuacao"] = $campos["os"]["destinacao"];
            }
        }

        return $campos_arr;

    }

    #Troca obrigatória
    function auditoria_troca_obrigatoria_gama_italy() {
        global $con, $os, $login_fabrica;

        if (verifica_posto_modelo()) {
            $sqlVer = "
                SELECT  tbl_auditoria_os.liberada
                FROM    tbl_auditoria_os
                WHERE   os = $os
                AND     auditoria_status = 6
            ";
            $resVer = pg_query($con,$sqlVer);
            $verLiberada = pg_fetch_result($resVer,0,liberada);

            if (pg_num_rows($resVer) == 0) {

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                            ({$os}, 6, 'Auditoria de Autorizada MODELO')";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                }
            }

        } else {

            $sql = "SELECT tbl_produto.produto
                    FROM tbl_os_produto
                    INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
                    WHERE tbl_os_produto.os = {$os}
                    AND tbl_produto.troca_obrigatoria IS TRUE";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sqlVer = "
                    SELECT  liberada, cancelada, reprovada
                    FROM    tbl_auditoria_os
                    WHERE   os = $os
		    AND     auditoria_status = 6
		    AND     observacao = 'Auditoria de Produto com Troca Autorizada'
		    ORDER BY data_input DESC LIMIT 1
                ";
                $resVer = pg_query($con,$sqlVer);
		$verLiberada = pg_fetch_result($resVer,0,'liberada');
		$verCancelada = pg_fetch_result($resVer,0,'cancelada');
		$verReprovada = pg_fetch_result($resVer,0,'reprovada');

                if (pg_num_rows($resVer) == 0 OR strlen($verCancelada) > 0 OR strlen($verReprovada) > 0) {

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                                ({$os}, 6, 'Auditoria de Produto com Troca Autorizada')";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço");
                    }
                }
            }
        }
    }

    function verifica_posto_modelo($posto_param = "") {
        global $con, $login_fabrica, $campos, $login_posto;

        if(strlen($posto_param) > 0){
            $posto = $posto_param;
        }else{
            $posto = (strlen($campos["posto"]["id"]) > 0) ? $campos["posto"]["id"] : $login_posto;
        }

        $sql = "SELECT tbl_tipo_posto.descricao
                 FROM tbl_posto_fabrica
           INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                  AND tbl_tipo_posto.descricao = 'Autorizada MODELO'
                  AND tbl_posto_fabrica.posto = {$posto}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $retorno = true;
        } else {
            $retorno = false;
        }

        return $retorno;
    }

    /**
     * - auditoria_troca_produto_gama_italy()
     * Entra em auditoria Ordem de Serviço
     * que foi selecionado o tipo de atendimento
     * solicitação de troca
     */
    function auditoria_troca_produto_gama_italy()
    {
        global $con, $campos, $login_fabrica, $os;

        $troca_produto = $campos['produto']['troca_produto'];

        if ($troca_produto == 't' && verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't'", $os)) {
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }
            $sqlGrava = "
                INSERT INTO tbl_auditoria_os (
                    os,
                    auditoria_status,
                    observacao
                ) VALUES (
                    $os,
                    $auditoria_status,
                    'OS em auditoria por Solicitação de Troca'
                )
            ";
            $resGrava = pg_query($con,$sqlGrava);

            if (pg_last_error($con)) {
                throw new Exception("Erro ao lançar Ordem de Servico");
            }
        }
    }

    function valida_anexo_gama()
    {
        global $con, $os, $campos, $anexos_inseridos, $msg_erro;

        $sqlDataOs = "SELECT os.os, sp.status_pedido, sp.descricao, cp.campos_adicionais::jsonb->>'troca_produto' AS troca_produto
                        FROM tbl_os AS os
                        LEFT JOIN tbl_os_produto     AS op ON op.os           = os.os 
                        LEFT JOIN tbl_os_item        AS oi ON oi.os_produto   = op.os_produto
                        LEFT JOIN tbl_pedido         AS p  ON oi.pedido       = p.pedido
                        LEFT JOIN tbl_status_pedido  AS sp ON p.status_pedido = sp.status_pedido
                        LEFT JOIN tbl_os_campo_extra AS cp ON cp.os           = os.os AND cp.fabrica = 164
                    WHERE os.os    = {$os}
                        AND os.fabrica = 164
                        AND data_abertura >= '2019-11-01'";
        /*$sqlDataOs = "SELECT data_abertura FROM tbl_os WHERE os = $os AND fabrica = 164 AND data_abertura >= '2019-11-01'";*/
        $resDataOs = pg_query($con,$sqlDataOs);

        if (pg_num_rows($resDataOs) > 0) {
            $status_pedido      = pg_fetch_result($resDataOs, 0, 'status_pedido');
            $status_pedido_desc = pg_fetch_result($resDataOs, 0, 'descricao');
            $troca_produto      = pg_fetch_result($resDataOs, 0, 'troca_produto'); 

            if ($status_pedido != 14 || $status_pedido_desc != 'Cancelado Total') {
                $sqlDataEntrada = "SELECT sr.descricao, op.os_produto, oi.servico_realizado, oi.parametros_adicionais::jsonb->>'data_recebimento' as data_recebimento
                                    FROM tbl_os_produto AS op 
                                        INNER JOIN tbl_os_item AS oi ON oi.os_produto = op.os_produto AND oi.fabrica_i = 164 
                                        INNER JOIN tbl_servico_realizado AS sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = 164
                                    WHERE op.os = {$os}";
                $resDataEntrada   = pg_query($con, $sqlDataEntrada);
                
                if (pg_num_rows($resDataEntrada) > 0) {
                    $data_recebimento          = pg_fetch_result($resDataEntrada, 0, data_recebimento);
                    $os_servico_realizado      = pg_fetch_result($resDataEntrada, 0, servico_realizado);
                    $os_servico_realizado_desc = strtolower(pg_fetch_result($resDataEntrada, 0, descricao));
                    $servicoIsAjuste           = ($os_servico_realizado == 11233 || $os_servico_realizado_desc == 'ajuste') ? true : false;    
                }        

                if (in_array('notafiscal', $anexos_inseridos())) {
                    $sql_tdocs_anexo  = "SELECT tdocs, obs FROM tbl_tdocs WHERE contexto = 'os' AND situacao = 'ativo' ";
                    $sql_tdocs_anexo .= (!empty($os)) ? "AND referencia_id = {$os} AND fabrica = 164" : "AND hash_temp = '$anexo_chave'";
                    $res_tdocs_anexo  = pg_query($con, $sql_tdocs_anexo);

                    if (pg_num_rows($res_tdocs_anexo) > 0) {
                        $tdocs_num    = [];

                        for ($i_anexo = 0; $i_anexo < pg_num_rows($res_tdocs_anexo); $i_anexo++) {
                            $tdocs_i     = pg_fetch_result($res_tdocs_anexo, $i_anexo, 'tdocs'); 
                            $obs         = json_decode(pg_fetch_result($res_tdocs_anexo, $i_anexo, 'obs'), true);
                            
                            if (in_array($obs[$i_anexo]['typeId'], ['evidencia', 'comprovante_entrada'])) {
                                array_push($tdocs_num, $tdocs_i);
                            }
                        }

                        $tdocs_num = implode(",", $tdocs_num);
                    }

                    if (in_array('evidencia', $anexos_inseridos()) && !$data_recebimento) {
                        if (!$servicoIsAjuste && !in_array($os_servico_realizado, [11235,11237])) {
                            $anexo_chave = $campos['anexo_chave'];
                            $sql         = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE contexto = 'os' AND tdocs IN ($tdocs_num)";
                            $sql        .= (!empty($os)) ? " AND referencia_id = {$os} AND fabrica = 164" : " AND hash_temp = '{$anexo_chave}'";
                            $res         = pg_query($con, $sql);
                            
                            throw new Exception('Não é possível inserir comprovante de evidência sem a data de conferência da peça');    
                        }
                    }

                    if (in_array('comprovante_entrada', $anexos_inseridos()) && in_array('evidencia', $anexos_inseridos() && !$data_recebimento)) {
                        if (!$servicoIsAjuste &&!in_array($os_servico_realizado, [11235,11237])) {
                            $anexo_chave = $campos['anexo_chave'];
                            $sql         = "UPDATE tbl_tdocs SET situacao = 'inativo' WHERE contexto = 'os' AND tdocs IN ($tdocs_num)";
                            $sql        .= (!empty($os)) ? " AND referencia_id = {$os} AND fabrica = 164" : " AND hash_temp = '{$anexo_chave}'";
                            $res         = pg_query($con, $sql);
                            
                            throw new Exception('Não é possível inserir comprovante de evidência sem a data de conferência da peça');
                        }
                    }

                } else {
                    throw new Exception('Não é possível gravar OS sem nota fiscal.');
                }
            }

        }
    }

    if(posto_interno()){
        $valida_anexo = '';
        
    } else {
        $valida_anexo = 'valida_anexo_gama';
    }
?>
